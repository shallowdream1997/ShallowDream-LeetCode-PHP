<?php
require_once(dirname(__FILE__) . "/../../../../php/requiredfile/requiredfile.php");
require_once(dirname(__FILE__) . "/../../../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/RequestUtils.php");
require_once(dirname(__FILE__) . "/../SpApi.php");

class SpCreateKeywordController
{
    private $log;

    public function __construct()
    {
        $this->log = new MyLogger("sp/keyword");
    }

    private function log(string $string = "")
    {
        $this->log->log2($string);
    }

    /**
     * 读取Excel创建keyword广告，已存在则跳过并补写Mongo
     * Excel格式: channel | seller_id | campaign_id | ad_group_id | keywordtext | 增投类型 | 匹配方式 | BID
     * 用法: php SpCreateKeywordController.php file="M6增投keyword.xlsx" channel=amazon_us
     *       php SpCreateKeywordController.php file="M6增投keyword.xlsx"  (处理全部channel)
     * @param string $file Excel文件名(在./excel/目录下)
     * @param string $channel 可选，按channel过滤数据，不传则处理全部
     */
    public function createKeywords($file = "", $channel = "")
    {
        $channelLabel = empty($channel) ? '全部' : $channel;
        $this->log("createKeywords 开始处理 file:{$file} channel:{$channelLabel}");
        $excelUtils = new ExcelUtils();
        $spApi = new SpApi();

        // 读取Excel，按 seller_id + ad_group_id 分组
        $groupedData = [];
        $invalidItems = []; // 匹配方式为空的行，当作投放失败
        $totalCount = 0;
        try {
            $excelUtils->eachXlsxRow(__DIR__."/excel/{$file}", function ($item) use (&$groupedData, &$invalidItems, &$totalCount, $channel) {
                $itemChannel = trim($item['channel'] ?? '');
                $sellerId = trim($item['seller_id'] ?? '');
                $campaignId = trim(sprintf('%.0f', (float)($item['campaign_id'] ?? 0)), "'");
                $adGroupId = trim(sprintf('%.0f', (float)($item['ad_group_id'] ?? 0)), "'");
                $keywordText = trim($item['keywordtext'] ?? '');
                $matchType = strtolower(trim($item['匹配方式'] ?? ''));
                $bid = trim($item['BID'] ?? '');

                if ($sellerId === "" || $adGroupId === "" || $adGroupId === "0" || $keywordText === "") {
                    return;
                }
                if (!empty($channel) && $itemChannel !== $channel) {
                    return;
                }

                // 匹配方式为空，当作投放失败
                if ($matchType === "") {
                    $invalidItems[] = [
                        "seller_id" => $sellerId,
                        "ad_group_id" => $adGroupId,
                        "keyword_text" => $keywordText,
                        "match_type" => "",
                        "bid" => $bid,
                        "error" => "匹配方式为空",
                    ];
                    return;
                }

                $groupKey = "{$sellerId}_{$adGroupId}";
                $groupedData[$groupKey][] = [
                    'channel' => $itemChannel,
                    'sellerId' => $sellerId,
                    'campaignId' => $campaignId,
                    'adGroupId' => $adGroupId,
                    'keywordText' => $keywordText,
                    'matchType' => $matchType,
                    'bid' => $bid,
                ];
                $totalCount++;
            });
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }

        $this->log("channel:{$channelLabel} 共 " . count($groupedData) . " 个ad group, {$totalCount} 条数据");

        if (count($groupedData) <= 0) {
            $this->log("createKeywords channel:{$channelLabel} 无数据");
            return;
        }

        $exportList = $invalidItems; // 匹配方式为空的行直接归入失败列表
        $createdCount = 0;
        $skippedCount = 0;

        foreach ($groupedData as $groupKey => $items) {
            $sellerId = $items[0]['sellerId'];
            $adGroupId = $items[0]['adGroupId'];
            $excelCampaignId = $items[0]['campaignId'] ?? '';

            // 获取ad group信息（campaignId、defaultBid）：优先使用Excel中的campaign_id，否则查Mongo/Amazon API
            $adGroupInfo = null;
            $campaignId = '';
            $defaultBid = null;

            // 先查Mongo获取adGroupInfo（需要defaultBid等信息）
            $adGroupInfo = $spApi->getMongoAdGroupInfo($sellerId, '', '', $adGroupId);
            if (!$adGroupInfo || !isset($adGroupInfo['campaignId'])) {
                $this->log("{$sellerId} adGroupId:{$adGroupId} Mongo未找到，尝试Amazon API查询");
                $amazonAdGroup = $spApi->getAmazonAdGroupInfoById($sellerId, $adGroupId);
                if (!empty($amazonAdGroup) && isset($amazonAdGroup['campaignId'])) {
                    $adGroupInfo = [
                        'campaignId' => $amazonAdGroup['campaignId'],
                        'defaultBid' => $amazonAdGroup['defaultBid'] ?? null,
                    ];
                    $this->log("{$sellerId} adGroupId:{$adGroupId} Amazon API查到 campaignId:{$amazonAdGroup['campaignId']}");
                }
            }

            // 优先使用Excel中的campaign_id
            if ($excelCampaignId !== "" && $excelCampaignId !== "0") {
                $campaignId = $excelCampaignId;
                $this->log("{$sellerId} adGroupId:{$adGroupId} 使用Excel campaignId:{$campaignId}");
            } elseif ($adGroupInfo && isset($adGroupInfo['campaignId'])) {
                $campaignId = $adGroupInfo['campaignId'];
            }

            if ($campaignId === "") {
                $this->log("❌ {$sellerId} adGroupId:{$adGroupId} 未找到ad group信息，跳过");
                foreach ($items as $item) {
                    $exportList[] = [
                        "seller_id" => $sellerId,
                        "ad_group_id" => $adGroupId,
                        "keyword_text" => $item['keywordText'],
                        "match_type" => $item['matchType'],
                        "error" => "ad group not found",
                    ];
                }
                continue;
            }
            $defaultBid = $adGroupInfo['defaultBid'] ?? null;

            // 查询Amazon已有的keyword
            $existingKeywords = $spApi->listKeyword($sellerId, $campaignId, $adGroupId);
            $this->log("{$sellerId} adGroupId:{$adGroupId} 已有keyword " . count($existingKeywords) . "个");

            // 检查哪些需要新建
            $createPayloads = [];
            foreach ($items as $item) {
                $key = "{$item['matchType']}_{$item['keywordText']}";
                if (isset($existingKeywords[$key])) {
                    // 已存在，跳过；如果Mongo中没有则补写
                    $skippedCount++;
                    $this->log("⏭️ {$sellerId} keyword已存在: {$key}");
                    $existingInfo = $existingKeywords[$key];
                    // 补写Mongo（如果有需要的话可以在这里处理）
                    continue;
                }

                $bid = $item['bid'] !== "" ? (float)$item['bid'] : null;
                $createPayloads[] = [
                    "campaignId" => (int)$campaignId,
                    "adGroupId" => (int)$adGroupId,
                    "keywordText" => $item['keywordText'],
                    "matchType" => $item['matchType'],
                    "state" => "enabled",
                    "bid" => $bid,
                ];
            }

            // 批量创建keyword
            if (count($createPayloads) > 0) {
                foreach (array_chunk($createPayloads, 1000) as $chunk) {
                    $this->log("{$sellerId} adGroupId:{$adGroupId} 创建keyword: " . count($chunk) . "个");
                    $result = $spApi->createKeywords($sellerId, $chunk);

                    // 处理创建成功的
                    foreach ($result['success'] as $successItem) {
                        $createdCount++;
                        $payload = $successItem['payload'];
                        $keywordId = $successItem['id'];
                        $this->log("✅ {$sellerId} 创建keyword成功: {$keywordId} - {$payload['matchType']}_{$payload['keywordText']}");
                        // 写入Mongo
                        $bid = $payload['bid'] !== null ? $payload['bid'] : $defaultBid;
                        $spApi->mongoCreateKeyword($sellerId, $campaignId, $adGroupId, $payload['keywordText'], $payload['matchType'], $keywordId, $adGroupInfo);
                    }

                    // 处理创建失败的
                    foreach ($result['error'] as $errorItem) {
                        $payload = $errorItem['payload'];
                        $this->log("❌ {$sellerId} 创建keyword失败: {$payload['matchType']}_{$payload['keywordText']}");
                        $exportList[] = [
                            "seller_id" => $sellerId,
                            "ad_group_id" => $adGroupId,
                            "keyword_text" => $payload['keywordText'],
                            "match_type" => $payload['matchType'],
                            "bid" => $payload['bid'],
                            "error" => json_encode($errorItem['response'], JSON_UNESCAPED_UNICODE),
                        ];
                    }
                }
            }
        }

        // 输出汇总
        $this->log("========== 处理汇总 ==========");
        $this->log("总数据数: {$totalCount}");
        $this->log("✅ 创建成功: {$createdCount}");
        $this->log("⏭️ 已存在跳过: {$skippedCount}");
        $this->log("❌ 失败: " . count($exportList));

        // 导出失败数据
        if (count($exportList) > 0) {
            $excelUtils = new ExcelUtils("sp/keyword/");
            $filePath = $excelUtils->downloadXlsx([
                "seller_id",
                "ad_group_id",
                "keyword_text",
                "match_type",
                "bid",
                "error",
            ], $exportList, "创建keyword失败_{$channelLabel}_" . date("YmdHis") . ".xlsx");
            $this->log("失败数据已导出: {$filePath}");
        }

        $this->log("createKeywords channel:{$channelLabel} 处理完毕");
    }

    /**
     * 校验Excel中的keyword投放数据是否已在Amazon上成功投放
     * Excel格式: channel | seller_id | campaign_id | ad_group_id | keywordtext | 增投类型 | 匹配方式 | BID
     * 用法: php SpCreateKeywordController.php method=verify file="M6增投keyword.xlsx" channel=amazon_us
     *       php SpCreateKeywordController.php method=verify file="M6增投keyword.xlsx"  (校验全部channel)
     * @param string $file Excel文件名(在./excel/目录下)
     * @param string $channel 可选，按channel过滤数据，不传则校验全部
     */
    public function verifyKeywords($file = "", $channel = "")
    {
        $channelLabel = empty($channel) ? '全部' : $channel;
        $this->log("verifyKeywords 开始校验 file:{$file} channel:{$channelLabel}");
        $excelUtils = new ExcelUtils();
        $spApi = new SpApi();

        // 读取Excel，按 seller_id + ad_group_id 分组
        $groupedData = [];
        $invalidItems = [];
        $totalCount = 0;
        try {
            $excelUtils->eachXlsxRow(__DIR__."/excel/{$file}", function ($item) use (&$groupedData, &$invalidItems, &$totalCount, $channel) {
                $itemChannel = trim($item['channel'] ?? '');
                $sellerId = trim($item['seller_id'] ?? '');
                $campaignId = trim(sprintf('%.0f', (float)($item['campaign_id'] ?? 0)), "'");
                $adGroupId = trim(sprintf('%.0f', (float)($item['ad_group_id'] ?? 0)), "'");
                $keywordText = trim($item['keywordtext'] ?? '');
                $matchType = strtolower(trim($item['匹配方式'] ?? ''));
                $bid = trim($item['BID'] ?? '');

                if ($sellerId === "" || $adGroupId === "" || $adGroupId === "0" || $keywordText === "") {
                    return;
                }
                if (!empty($channel) && $itemChannel !== $channel) {
                    return;
                }

                // 匹配方式为空，当作校验失败
                if ($matchType === "") {
                    $invalidItems[] = [
                        "seller_id" => $sellerId,
                        "ad_group_id" => $adGroupId,
                        "keyword_text" => $keywordText,
                        "match_type" => "",
                        "actual_state" => "",
                        "expected_state" => "enabled",
                        "actual_bid" => "",
                        "expected_bid" => $bid,
                        "error" => "匹配方式为空",
                    ];
                    return;
                }

                $groupKey = "{$sellerId}_{$adGroupId}";
                $groupedData[$groupKey][] = [
                    'channel' => $itemChannel,
                    'sellerId' => $sellerId,
                    'campaignId' => $campaignId,
                    'adGroupId' => $adGroupId,
                    'keywordText' => $keywordText,
                    'matchType' => $matchType,
                    'bid' => $bid,
                ];
                $totalCount++;
            });
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }

        $this->log("channel:{$channelLabel} 共 " . count($groupedData) . " 个ad group, {$totalCount} 条数据");

        if (count($groupedData) <= 0 && count($invalidItems) <= 0) {
            $this->log("verifyKeywords channel:{$channelLabel} 无数据");
            return;
        }

        $exportList = $invalidItems;
        $verifiedCount = count($invalidItems);
        $matchCount = 0;
        $notFoundCount = 0;
        $stateMismatchCount = 0;
        $bidMismatchCount = 0;

        foreach ($groupedData as $groupKey => $items) {
            $sellerId = $items[0]['sellerId'];
            $adGroupId = $items[0]['adGroupId'];
            $excelCampaignId = $items[0]['campaignId'] ?? '';

            // 获取campaignId
            $adGroupInfo = null;
            $campaignId = '';

            $adGroupInfo = $spApi->getMongoAdGroupInfo($sellerId, '', '', $adGroupId);
            if (!$adGroupInfo || !isset($adGroupInfo['campaignId'])) {
                $amazonAdGroup = $spApi->getAmazonAdGroupInfoById($sellerId, $adGroupId);
                if (!empty($amazonAdGroup) && isset($amazonAdGroup['campaignId'])) {
                    $adGroupInfo = [
                        'campaignId' => $amazonAdGroup['campaignId'],
                        'defaultBid' => $amazonAdGroup['defaultBid'] ?? null,
                    ];
                }
            }

            if ($excelCampaignId !== "" && $excelCampaignId !== "0") {
                $campaignId = $excelCampaignId;
            } elseif ($adGroupInfo && isset($adGroupInfo['campaignId'])) {
                $campaignId = $adGroupInfo['campaignId'];
            }

            if ($campaignId === "") {
                $this->log("❌ {$sellerId} adGroupId:{$adGroupId} 未找到ad group信息");
                foreach ($items as $item) {
                    $verifiedCount++;
                    $exportList[] = [
                        "seller_id" => $sellerId,
                        "ad_group_id" => $adGroupId,
                        "keyword_text" => $item['keywordText'],
                        "match_type" => $item['matchType'],
                        "actual_state" => "",
                        "expected_state" => "enabled",
                        "actual_bid" => "",
                        "expected_bid" => $item['bid'],
                        "error" => "ad group not found",
                    ];
                }
                continue;
            }

            // 查询Amazon已有的keyword
            $existingKeywords = $spApi->listKeyword($sellerId, $campaignId, $adGroupId);
            $this->log("{$sellerId} adGroupId:{$adGroupId} Amazon已有keyword " . count($existingKeywords) . "个");

            // 逐条校验
            foreach ($items as $item) {
                $verifiedCount++;
                $key = "{$item['matchType']}_{$item['keywordText']}";
                $expectedBid = $item['bid'] !== "" ? (float)$item['bid'] : null;

                if (!isset($existingKeywords[$key])) {
                    // 未投放
                    $notFoundCount++;
                    $this->log("❌ {$sellerId} keyword未投放: {$key}");
                    $exportList[] = [
                        "seller_id" => $sellerId,
                        "ad_group_id" => $adGroupId,
                        "keyword_text" => $item['keywordText'],
                        "match_type" => $item['matchType'],
                        "actual_state" => "not_found",
                        "expected_state" => "enabled",
                        "actual_bid" => "",
                        "expected_bid" => $item['bid'],
                        "error" => "keyword未投放",
                    ];
                    continue;
                }

                // 已投放，校验state和bid
                $existingInfo = $existingKeywords[$key];
                $actualState = $existingInfo['state'] ?? '';
                $actualBid = isset($existingInfo['bid']) ? (float)$existingInfo['bid'] : null;

                $stateMatch = ($actualState === "enabled");
                $bidMatch = ($expectedBid === null) || (abs($actualBid - $expectedBid) < 0.001);

                if ($stateMatch && $bidMatch) {
                    $matchCount++;
                    $this->log("✅ {$sellerId} keyword已投放且一致: {$key} state:{$actualState} bid:{$actualBid}");
                } else {
                    if (!$stateMatch) {
                        $stateMismatchCount++;
                        $this->log("⚠️ {$sellerId} keyword状态异常: {$key} 期望enabled, 实际{$actualState}");
                    }
                    if (!$bidMatch) {
                        $bidMismatchCount++;
                        $this->log("⚠️ {$sellerId} keyword bid不一致: {$key} 期望{$expectedBid}, 实际{$actualBid}");
                    }
                    $exportList[] = [
                        "seller_id" => $sellerId,
                        "ad_group_id" => $adGroupId,
                        "keyword_text" => $item['keywordText'],
                        "match_type" => $item['matchType'],
                        "actual_state" => $actualState,
                        "expected_state" => "enabled",
                        "actual_bid" => $actualBid,
                        "expected_bid" => $expectedBid ?? "",
                        "error" => (!$stateMatch ? "状态异常" : "") . (!$bidMatch ? " bid不一致" : ""),
                    ];
                }
            }
        }

        // 输出校验汇总
        $this->log("========== 校验汇总 ==========");
        $this->log("总校验数: {$verifiedCount}");
        $this->log("✅ 已投放且一致: {$matchCount}");
        $this->log("❌ 未投放: {$notFoundCount}");
        $this->log("⚠️ 状态异常(非enabled): {$stateMismatchCount}");
        $this->log("⚠️ bid不一致: {$bidMismatchCount}");

        // 导出异常数据
        if (count($exportList) > 0) {
            $excelUtils = new ExcelUtils("sp/keyword/");
            $filePath = $excelUtils->downloadXlsx([
                "seller_id",
                "ad_group_id",
                "keyword_text",
                "match_type",
                "actual_state",
                "expected_state",
                "actual_bid",
                "expected_bid",
                "error",
            ], $exportList, "校验异常_keyword_{$channelLabel}_" . date("YmdHis") . ".xlsx");
            $this->log("异常数据已导出: {$filePath}");
        } else {
            $this->log("所有keyword投放校验通过，无异常数据");
        }

        $this->log("verifyKeywords channel:{$channelLabel} 校验完毕");
    }
}

$parameters = DataUtils::ExplainArgv(@$argv, array());
$params = (count(@$argv) > 1) ? $parameters : $_REQUEST;
$file = "";
$channel = "";
$method = "";
if (isset($params['file']) && trim($params['file']) != '') {
    $file = $params['file'];
}
if (isset($params['channel']) && trim($params['channel']) != '') {
    $channel = $params['channel'];
}
if (isset($params['method']) && trim($params['method']) != '') {
    $method = $params['method'];
}
$con = new SpCreateKeywordController();
if ($method == 'verify') {
    $con->verifyKeywords($file, $channel);
} else {
    $con->createKeywords($file, $channel);
}
