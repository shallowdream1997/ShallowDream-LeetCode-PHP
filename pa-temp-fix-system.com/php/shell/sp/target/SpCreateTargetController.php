<?php
require_once(dirname(__FILE__) . "/../../../../php/requiredfile/requiredfile.php");
require_once(dirname(__FILE__) . "/../../../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/RequestUtils.php");
require_once(dirname(__FILE__) . "/../SpApi.php");

class SpCreateTargetController
{
    private $log;

    public function __construct()
    {
        $this->log = new MyLogger("sp/target");
    }

    private function log(string $string = "")
    {
        $this->log->log2($string);
    }

    /**
     * 读取Excel创建target(ASIN)广告，已存在则跳过并补写Mongo
     * Excel格式: channel | seller_id | campaign_id | ad_group_id | keywordtext | 增投类型 | 匹配方式 | BID
     * 用法: php SpCreateTargetController.php file="M6增投asin.xlsx" channel=amazon_us
     *       php SpCreateTargetController.php file="M6增投asin.xlsx"  (处理全部channel)
     * @param string $file Excel文件名(在./excel/目录下)
     * @param string $channel 可选，按channel过滤数据，不传则处理全部
     */
    public function createTargets($file = "", $channel = "")
    {
        $channelLabel = empty($channel) ? '全部' : $channel;
        $this->log("createTargets 开始处理 file:{$file} channel:{$channelLabel}");
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
                $bid = trim($item['BID'] ?? '');

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
                    'bid' => $bid,
                ];
                $totalCount++;
            });
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }

        $this->log("channel:{$channelLabel} 共 " . count($groupedData) . " 个ad group, {$totalCount} 条数据");

        if (count($groupedData) <= 0) {
            $this->log("createTargets channel:{$channelLabel} 无数据");
            return;
        }

        $exportList = [];
        $createdCount = 0;
        $skippedCount = 0;
        $updatedCount = 0;

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
                        "asin" => $item['asin'],
                        "bid" => $item['bid'],
                        "error" => "ad group not found",
                    ];
                }
                continue;
            }
            $defaultBid = $adGroupInfo['defaultBid'] ?? null;

            // 查询Amazon已有的target（所有状态）
            $existingTargets = $spApi->listTargetAsin($sellerId, $campaignId, $adGroupId, "", "", "");
            $this->log("{$sellerId} adGroupId:{$adGroupId} 已有target " . count($existingTargets) . "个");

            // 检查哪些需要新建，哪些需要更新状态
            $createPayloads = [];
            $updatePayloads = []; // 需要更新状态为enabled的target
            foreach ($items as $item) {
                $asin = $item['asin'];
                if (isset($existingTargets[$asin])) {
                    $existingInfo = $existingTargets[$asin];
                    $existingState = $existingInfo['state'] ?? '';
                    if ($existingState !== 'enabled') {
                        // 已存在但非enabled，更新状态为enabled
                        $updatePayloads[] = [
                            "targetId" => (int)$existingInfo['targetId'],
                            "state" => "enabled",
                        ];
                        $this->log("🔄 {$sellerId} target已存在但非enabled({$existingState})，将更新: {$asin} targetId:{$existingInfo['targetId']}");
                    } else {
                        // 已存在且enabled，跳过
                        $skippedCount++;
                        $this->log("⏭️ {$sellerId} target已存在且enabled: {$asin}");
                    }
                    continue;
                }

                $bid = $item['bid'] !== "" ? (float)$item['bid'] : null;
                $expressionGroup = [
                    "value" => $asin,
                    "type" => "asinSameAs",
                ];
                $createPayloads[] = [
                    "campaignId" => (int)$campaignId,
                    "adGroupId" => (int)$adGroupId,
                    "state" => "enabled",
                    "expressionType" => "manual",
                    "bid" => $bid,
                    "expression" => [$expressionGroup],
                    "resolvedExpression" => [$expressionGroup],
                ];
            }

            // 批量更新target状态为enabled
            if (count($updatePayloads) > 0) {
                foreach (array_chunk($updatePayloads, 1000) as $chunk) {
                    $this->log("{$sellerId} adGroupId:{$adGroupId} 更新target状态为enabled: " . count($chunk) . "个");
                    $result = $spApi->updateTarget($sellerId, $chunk);
                    $updatedCount += count($result['success'] ?? []);
                    foreach ($result['success'] ?? [] as $targetId) {
                        $this->log("✅ {$sellerId} 更新target状态成功: targetId:{$targetId}");
                    }
                    foreach ($result['error'] ?? [] as $targetId) {
                        $this->log("❌ {$sellerId} 更新target状态失败: targetId:{$targetId}");
                        $exportList[] = [
                            "seller_id" => $sellerId,
                            "ad_group_id" => $adGroupId,
                            "asin" => "",
                            "bid" => "",
                            "error" => "更新状态失败 targetId:{$targetId}",
                        ];
                    }
                }
            }

            // 批量创建target
            if (count($createPayloads) > 0) {
                foreach (array_chunk($createPayloads, 1000) as $chunk) {
                    $this->log("{$sellerId} adGroupId:{$adGroupId} 创建target: " . count($chunk) . "个");
                    $result = $spApi->createTargets($sellerId, $chunk);

                    foreach ($result['success'] as $successItem) {
                        $createdCount++;
                        $payload = $successItem['payload'];
                        $targetId = $successItem['id'];
                        $asin = $payload['expression'][0]['value'];
                        $this->log("✅ {$sellerId} 创建target成功: {$targetId} - {$asin}");
                        // 写入Mongo
                        $targetConfig = [
                            'targetId' => $targetId,
                            'bid' => $payload['bid'] !== null ? $payload['bid'] : $defaultBid,
                        ];
                        $spApi->mongoCreateTargetAsin($sellerId, $campaignId, $adGroupId, $asin, $targetConfig);
                    }

                    foreach ($result['error'] as $errorItem) {
                        $payload = $errorItem['payload'];
                        $asin = $payload['expression'][0]['value'];
                        $this->log("❌ {$sellerId} 创建target失败: {$asin}");
                        $exportList[] = [
                            "seller_id" => $sellerId,
                            "ad_group_id" => $adGroupId,
                            "asin" => $asin,
                            "bid" => $payload['bid'],
                            "error" => json_encode($errorItem['response'], JSON_UNESCAPED_UNICODE),
                        ];
                    }
                }
            }
        }

        $this->log("========== 处理汇总 ==========");
        $this->log("总数据数: {$totalCount}");
        $this->log("✅ 创建成功: {$createdCount}");
        $this->log("🔄 更新状态为enabled: {$updatedCount}");
        $this->log("⏭️ 已存在且enabled跳过: {$skippedCount}");
        $this->log("❌ 失败: " . count($exportList));

        if (count($exportList) > 0) {
            $excelUtils = new ExcelUtils("sp/target/");
            $filePath = $excelUtils->downloadXlsx([
                "seller_id",
                "ad_group_id",
                "asin",
                "bid",
                "error",
            ], $exportList, "创建target失败_{$channelLabel}_" . date("YmdHis") . ".xlsx");
            $this->log("失败数据已导出: {$filePath}");
        }

        $this->log("createTargets channel:{$channelLabel} 处理完毕");
    }

    /**
     * 校验Excel中的target(ASIN)投放数据是否已在Amazon上成功投放
     * Excel格式: channel | seller_id | campaign_id | ad_group_id | keywordtext | 增投类型 | 匹配方式 | BID
     * 用法: php SpCreateTargetController.php method=verify file="M6增投asin.xlsx" channel=amazon_us
     *       php SpCreateTargetController.php method=verify file="M6增投asin.xlsx"  (校验全部channel)
     * @param string $file Excel文件名(在./excel/目录下)
     * @param string $channel 可选，按channel过滤数据，不传则校验全部
     */
    public function verifyTargets($file = "", $channel = "")
    {
        $channelLabel = empty($channel) ? '全部' : $channel;
        $this->log("verifyTargets 开始校验 file:{$file} channel:{$channelLabel}");
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
                $bid = trim($item['BID'] ?? '');

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
                    'bid' => $bid,
                ];
                $totalCount++;
            });
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }

        $this->log("channel:{$channelLabel} 共 " . count($groupedData) . " 个ad group, {$totalCount} 条数据");

        if (count($groupedData) <= 0) {
            $this->log("verifyTargets channel:{$channelLabel} 无数据");
            return;
        }

        $exportList = [];
        $verifiedCount = 0;
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
                        "asin" => $item['asin'],
                        "actual_state" => "",
                        "expected_state" => "enabled",
                        "actual_bid" => "",
                        "expected_bid" => $item['bid'],
                        "error" => "ad group not found",
                    ];
                }
                continue;
            }

            // 查询Amazon已有的target（所有状态）
            $existingTargets = $spApi->listTargetAsin($sellerId, $campaignId, $adGroupId, "", "", "");
            $this->log("{$sellerId} adGroupId:{$adGroupId} Amazon已有target " . count($existingTargets) . "个");

            // 逐条校验
            foreach ($items as $item) {
                $verifiedCount++;
                $asin = $item['asin'];
                $expectedBid = $item['bid'] !== "" ? (float)$item['bid'] : null;

                if (!isset($existingTargets[$asin])) {
                    // 未投放
                    $notFoundCount++;
                    $this->log("❌ {$sellerId} target未投放: {$asin}");
                    $exportList[] = [
                        "seller_id" => $sellerId,
                        "ad_group_id" => $adGroupId,
                        "asin" => $asin,
                        "actual_state" => "not_found",
                        "expected_state" => "enabled",
                        "actual_bid" => "",
                        "expected_bid" => $item['bid'],
                        "error" => "target未投放",
                    ];
                    continue;
                }

                // 已投放，校验state和bid
                $existingInfo = $existingTargets[$asin];
                $actualState = $existingInfo['state'] ?? '';
                $actualBid = isset($existingInfo['bid']) ? (float)$existingInfo['bid'] : null;

                $stateMatch = ($actualState === "enabled");
                $bidMatch = ($expectedBid === null) || (abs($actualBid - $expectedBid) < 0.001);

                if ($stateMatch && $bidMatch) {
                    $matchCount++;
                    $this->log("✅ {$sellerId} target已投放且一致: {$asin} state:{$actualState} bid:{$actualBid}");
                } else {
                    if (!$stateMatch) {
                        $stateMismatchCount++;
                        $this->log("⚠️ {$sellerId} target状态异常: {$asin} 期望enabled, 实际{$actualState}");
                    }
                    if (!$bidMatch) {
                        $bidMismatchCount++;
                        $this->log("⚠️ {$sellerId} target bid不一致: {$asin} 期望{$expectedBid}, 实际{$actualBid}");
                    }
                    $exportList[] = [
                        "seller_id" => $sellerId,
                        "ad_group_id" => $adGroupId,
                        "asin" => $asin,
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
            $excelUtils = new ExcelUtils("sp/target/");
            $filePath = $excelUtils->downloadXlsx([
                "seller_id",
                "ad_group_id",
                "asin",
                "actual_state",
                "expected_state",
                "actual_bid",
                "expected_bid",
                "error",
            ], $exportList, "校验异常_target_{$channelLabel}_" . date("YmdHis") . ".xlsx");
            $this->log("异常数据已导出: {$filePath}");
        } else {
            $this->log("所有target投放校验通过，无异常数据");
        }

        $this->log("verifyTargets channel:{$channelLabel} 校验完毕");
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
$con = new SpCreateTargetController();
if ($method == 'verify') {
    $con->verifyTargets($file, $channel);
} else {
    $con->createTargets($file, $channel);
}
