<?php
require_once(dirname(__FILE__) . "/../../../../php/requiredfile/requiredfile.php");
require_once(dirname(__FILE__) . "/../../../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/RequestUtils.php");
require_once(dirname(__FILE__) . "/../SpApi.php");

class SpCreateNegativeTargetController
{
    private $log;

    public function __construct()
    {
        $this->log = new MyLogger("sp/negativeTarget");
    }

    private function log(string $string = "")
    {
        $this->log->log2($string);
    }

    /**
     * 读取Excel创建negativeTarget广告，已存在则跳过并补写Mongo
     * Excel格式: channel | seller_id | campaign_id | ad_group_id | keywordtext
     * 用法: php SpCreateNegativeTargetController.php file="M6精准否定asin.xlsx" channel=amazon_us
     *       php SpCreateNegativeTargetController.php file="M6精准否定asin.xlsx"  (处理全部channel)
     * @param string $file Excel文件名(在./excel/目录下)
     * @param string $channel 可选，按channel过滤数据，不传则处理全部
     */
    public function createNegativeTargets($file = "", $channel = "")
    {
        $channelLabel = empty($channel) ? '全部' : $channel;
        $this->log("createNegativeTargets 开始处理 file:{$file} channel:{$channelLabel}");
        $excelUtils = new ExcelUtils();
        $spApi = new SpApi();

        // 读取Excel，按 seller_id + ad_group_id 分组
        $groupedData = [];
        $totalCount = 0;
        try {
            $excelUtils->eachXlsxRow(__DIR__."/excel/{$file}", function ($item) use (&$groupedData, &$totalCount, $channel) {
                $itemChannel = trim($item['channel'] ?? '');
                $sellerId = trim($item['seller_id'] ?? '');
                $campaignId = trim(sprintf('%.0f', (float)($item['campaign_id'] ?? 0)), "'");
                $adGroupId = trim(sprintf('%.0f', (float)($item['ad_group_id'] ?? 0)), "'");
                $asin = trim($item['keywordtext'] ?? '');

                if ($sellerId === "" || $adGroupId === "" || $adGroupId === "0" || $asin === "") {
                    return;
                }
                if (!empty($channel) && $itemChannel !== $channel) {
                    return;
                }

                $groupKey = "{$sellerId}_{$adGroupId}";
                $groupedData[$groupKey][] = [
                    'channel' => $itemChannel,
                    'sellerId' => $sellerId,
                    'campaignId' => $campaignId,
                    'adGroupId' => $adGroupId,
                    'asin' => $asin,
                ];
                $totalCount++;
            });
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }

        $this->log("channel:{$channelLabel} 共 " . count($groupedData) . " 个ad group, {$totalCount} 条数据");

        if (count($groupedData) <= 0) {
            $this->log("createNegativeTargets channel:{$channelLabel} 无数据");
            return;
        }

        $exportList = [];
        $createdCount = 0;
        $skippedCount = 0;

        foreach ($groupedData as $groupKey => $items) {
            $sellerId = $items[0]['sellerId'];
            $adGroupId = $items[0]['adGroupId'];
            $excelCampaignId = $items[0]['campaignId'] ?? '';

            // 获取ad group信息（campaignId）：优先使用Excel中的campaign_id，否则查Mongo/Amazon API
            $adGroupInfo = null;
            $campaignId = '';

            // 先查Mongo获取adGroupInfo
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
                        "asin" => $item['asin'],
                        "error" => "ad group not found",
                    ];
                }
                continue;
            }

            // 查询Amazon已有的negativeTarget
            $existingList = $spApi->listNegativeTarget($sellerId, [$campaignId], [$adGroupId]);
            $existingAsins = [];
            foreach ($existingList as $info) {
                if (isset($info['expression'][0]['value'])) {
                    $existingAsins[$info['expression'][0]['value']] = $info;
                }
            }
            $this->log("{$sellerId} adGroupId:{$adGroupId} 已有negativeTarget " . count($existingAsins) . "个");

            // 检查哪些需要新建
            $createPayloads = [];
            $asinItemMap = [];
            foreach ($items as $item) {
                $asin = $item['asin'];
                if (isset($existingAsins[$asin])) {
                    $skippedCount++;
                    $this->log("⏭️ {$sellerId} negativeTarget已存在: {$asin}");
                    continue;
                }
                $expressionGroup = [
                    "value" => $asin,
                    "type" => "asinSameAs",
                ];
                $createPayloads[] = [
                    "campaignId" => (int)$campaignId,
                    "adGroupId" => (int)$adGroupId,
                    "state" => "enabled",
                    "expressionType" => "manual",
                    "expression" => [$expressionGroup],
                    "resolvedExpression" => [$expressionGroup],
                ];
                $asinItemMap[$asin] = $item;
            }

            // 批量创建negativeTarget
            if (count($createPayloads) > 0) {
                foreach (array_chunk($createPayloads, 1000) as $chunk) {
                    $this->log("{$sellerId} adGroupId:{$adGroupId} 创建negativeTarget: " . count($chunk) . "个");
                    $result = $spApi->createNegativeTargets($sellerId, $chunk);

                    foreach ($result['success'] as $successItem) {
                        $createdCount++;
                        $payload = $successItem['payload'];
                        $targetId = $successItem['id'];
                        $asin = $payload['expression'][0]['value'];
                        $this->log("✅ {$sellerId} 创建negativeTarget成功: {$targetId} - {$asin}");
                        $spApi->mongoCreateNegativeTarget($sellerId, $campaignId, $adGroupId, $asin, $targetId);
                    }

                    foreach ($result['error'] as $errorItem) {
                        $payload = $errorItem['payload'];
                        $asin = $payload['expression'][0]['value'];
                        $this->log("❌ {$sellerId} 创建negativeTarget失败: {$asin}");
                        $exportList[] = [
                            "seller_id" => $sellerId,
                            "ad_group_id" => $adGroupId,
                            "asin" => $asin,
                            "error" => json_encode($errorItem['response'], JSON_UNESCAPED_UNICODE),
                        ];
                    }
                }
            }
        }

        $this->log("========== 处理汇总 ==========");
        $this->log("总数据数: {$totalCount}");
        $this->log("✅ 创建成功: {$createdCount}");
        $this->log("⏭️ 已存在跳过: {$skippedCount}");
        $this->log("❌ 失败: " . count($exportList));

        if (count($exportList) > 0) {
            $excelUtils = new ExcelUtils("sp/negativeTarget/");
            $filePath = $excelUtils->downloadXlsx([
                "seller_id",
                "ad_group_id",
                "asin",
                "error",
            ], $exportList, "创建negativeTarget失败_{$channelLabel}_" . date("YmdHis") . ".xlsx");
            $this->log("失败数据已导出: {$filePath}");
        }

        $this->log("createNegativeTargets channel:{$channelLabel} 处理完毕");
    }

    /**
     * 校验Excel中的negativeTarget投放数据是否已在Amazon上成功投放
     * Excel格式: channel | seller_id | campaign_id | ad_group_id | keywordtext
     * 用法: php SpCreateNegativeTargetController.php method=verify file="M6精准否定asin.xlsx" channel=amazon_us
     *       php SpCreateNegativeTargetController.php method=verify file="M6精准否定asin.xlsx"  (校验全部channel)
     * @param string $file Excel文件名(在./excel/目录下)
     * @param string $channel 可选，按channel过滤数据，不传则校验全部
     */
    public function verifyNegativeTargets($file = "", $channel = "")
    {
        $channelLabel = empty($channel) ? '全部' : $channel;
        $this->log("verifyNegativeTargets 开始校验 file:{$file} channel:{$channelLabel}");
        $excelUtils = new ExcelUtils();
        $spApi = new SpApi();

        // 读取Excel，按 seller_id + ad_group_id 分组
        $groupedData = [];
        $totalCount = 0;
        try {
            $excelUtils->eachXlsxRow(__DIR__."/excel/{$file}", function ($item) use (&$groupedData, &$totalCount, $channel) {
                $itemChannel = trim($item['channel'] ?? '');
                $sellerId = trim($item['seller_id'] ?? '');
                $campaignId = trim(sprintf('%.0f', (float)($item['campaign_id'] ?? 0)), "'");
                $adGroupId = trim(sprintf('%.0f', (float)($item['ad_group_id'] ?? 0)), "'");
                $asin = trim($item['keywordtext'] ?? '');

                if ($sellerId === "" || $adGroupId === "" || $adGroupId === "0" || $asin === "") {
                    return;
                }
                if (!empty($channel) && $itemChannel !== $channel) {
                    return;
                }

                $groupKey = "{$sellerId}_{$adGroupId}";
                $groupedData[$groupKey][] = [
                    'channel' => $itemChannel,
                    'sellerId' => $sellerId,
                    'campaignId' => $campaignId,
                    'adGroupId' => $adGroupId,
                    'asin' => $asin,
                ];
                $totalCount++;
            });
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }

        $this->log("channel:{$channelLabel} 共 " . count($groupedData) . " 个ad group, {$totalCount} 条数据");

        if (count($groupedData) <= 0) {
            $this->log("verifyNegativeTargets channel:{$channelLabel} 无数据");
            return;
        }

        $exportList = [];
        $verifiedCount = 0;
        $matchCount = 0;
        $notFoundCount = 0;
        $stateMismatchCount = 0;

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
                        "asin" => $item['asin'],
                        "actual_state" => "",
                        "expected_state" => "enabled",
                        "error" => "ad group not found",
                    ];
                }
                continue;
            }

            // 查询Amazon已有的negativeTarget（所有状态）
            $existingList = $spApi->listNegativeTarget($sellerId, [$campaignId], [$adGroupId]);
            $existingAsins = [];
            foreach ($existingList as $info) {
                if (isset($info['expression'][0]['value'])) {
                    $existingAsins[$info['expression'][0]['value']] = $info;
                }
            }
            $this->log("{$sellerId} adGroupId:{$adGroupId} Amazon已有negativeTarget " . count($existingAsins) . "个");

            // 逐条校验
            foreach ($items as $item) {
                $verifiedCount++;
                $asin = $item['asin'];

                if (!isset($existingAsins[$asin])) {
                    // 未投放
                    $notFoundCount++;
                    $this->log("❌ {$sellerId} negativeTarget未投放: {$asin}");
                    $exportList[] = [
                        "seller_id" => $sellerId,
                        "ad_group_id" => $adGroupId,
                        "asin" => $asin,
                        "actual_state" => "not_found",
                        "expected_state" => "enabled",
                        "error" => "negativeTarget未投放",
                    ];
                    continue;
                }

                // 已投放，校验state
                $actualState = $existingAsins[$asin]['state'] ?? '';
                if ($actualState === "enabled") {
                    $matchCount++;
                    $this->log("✅ {$sellerId} negativeTarget已投放且一致: {$asin}");
                } else {
                    $stateMismatchCount++;
                    $this->log("⚠️ {$sellerId} negativeTarget状态异常: {$asin} 期望enabled, 实际{$actualState}");
                    $exportList[] = [
                        "seller_id" => $sellerId,
                        "ad_group_id" => $adGroupId,
                        "asin" => $asin,
                        "actual_state" => $actualState,
                        "expected_state" => "enabled",
                        "error" => "状态异常",
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

        // 导出异常数据
        if (count($exportList) > 0) {
            $excelUtils = new ExcelUtils("sp/negativeTarget/");
            $filePath = $excelUtils->downloadXlsx([
                "seller_id",
                "ad_group_id",
                "asin",
                "actual_state",
                "expected_state",
                "error",
            ], $exportList, "校验异常_negativeTarget_{$channelLabel}_" . date("YmdHis") . ".xlsx");
            $this->log("异常数据已导出: {$filePath}");
        } else {
            $this->log("所有negativeTarget投放校验通过，无异常数据");
        }

        $this->log("verifyNegativeTargets channel:{$channelLabel} 校验完毕");
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
$con = new SpCreateNegativeTargetController();
if ($method == 'verify') {
    $con->verifyNegativeTargets($file, $channel);
} else {
    $con->createNegativeTargets($file, $channel);
}
