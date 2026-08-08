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
        $updatedCount = 0;

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

            // 查询Amazon已有的negativeTarget（所有状态）
            $existingList = $spApi->listNegativeTarget($sellerId, [$campaignId], [$adGroupId]);
            $existingAsins = [];
            foreach ($existingList as $info) {
                if (isset($info['expression'][0]['value'])) {
                    $existingAsins[$info['expression'][0]['value']] = $info;
                }
            }
            $this->log("{$sellerId} adGroupId:{$adGroupId} 已有negativeTarget " . count($existingAsins) . "个");

            // 检查哪些需要新建，哪些需要更新状态
            $createPayloads = [];
            $updatePayloads = []; // 需要更新状态为enabled的negativeTarget
            $asinItemMap = [];
            foreach ($items as $item) {
                $asin = $item['asin'];
                if (isset($existingAsins[$asin])) {
                    $existingState = $existingAsins[$asin]['state'] ?? '';
                    if ($existingState !== 'enabled') {
                        // 已存在但非enabled，更新状态为enabled
                        $updatePayloads[] = [
                            "targetId" => (int)$existingAsins[$asin]['targetId'],
                            "state" => "enabled",
                        ];
                        $this->log("🔄 {$sellerId} negativeTarget已存在但非enabled({$existingState})，将更新: {$asin} targetId:{$existingAsins[$asin]['targetId']}");
                    } else {
                        // 已存在且enabled，跳过
                        $skippedCount++;
                        $this->log("⏭️ {$sellerId} negativeTarget已存在且enabled: {$asin}");
                    }
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

            // 批量更新negativeTarget状态为enabled
            if (count($updatePayloads) > 0) {
                foreach (array_chunk($updatePayloads, 1000) as $chunk) {
                    $this->log("{$sellerId} adGroupId:{$adGroupId} 更新negativeTarget状态为enabled: " . count($chunk) . "个");
                    $result = $spApi->updateNegativeTarget($sellerId, $chunk);
                    $updatedCount += count($result['success'] ?? []);
                    foreach ($result['success'] ?? [] as $targetId) {
                        $this->log("✅ {$sellerId} 更新negativeTarget状态成功: targetId:{$targetId}");
                    }
                    foreach ($result['error'] ?? [] as $targetId) {
                        $this->log("❌ {$sellerId} 更新negativeTarget状态失败: targetId:{$targetId}");
                        $exportList[] = [
                            "seller_id" => $sellerId,
                            "ad_group_id" => $adGroupId,
                            "asin" => "",
                            "error" => "更新状态失败 targetId:{$targetId}",
                        ];
                    }
                }
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
        $this->log("🔄 更新状态为enabled: {$updatedCount}");
        $this->log("⏭️ 已存在且enabled跳过: {$skippedCount}");
        $this->log("❌ 失败: " . count($exportList));

        if (count($exportList) > 0) {
            $excelUtils = new ExcelUtils("sp/negativeTarget/");
            $filePath = $excelUtils->downloadXlsx([
                "seller_id",
                "ad_group_id",
                "asin",
                "error",
            ], $exportList, "创建negativeTarget失败_{$channelLabel}_" . date("YmdHis") . ".xlsx", [1]);
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
        $adIdChannelMap = [];
        $verifiedCount = 0;
        $archivedCount = 0;
        $matchCount = 0;
        $notFoundCount = 0;
        $stateMismatchCount = 0;

        foreach ($groupedData as $groupKey => $items) {
            $sellerId = $items[0]['sellerId'];
            $adGroupId = $items[0]['adGroupId'];
            $excelCampaignId = $items[0]['campaignId'] ?? '';
            $sellerChannel = $spApi->sellerConfig($sellerId);

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
                        "channel" => $item['channel'] ?: ($sellerChannel ?: $channelLabel),
                        "seller_id" => $sellerId,
                        "target_id" => "",
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
                        "channel" => $item['channel'] ?: ($sellerChannel ?: $channelLabel),
                        "seller_id" => $sellerId,
                        "target_id" => "",
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
                // archived状态不统计，直接跳过
                if ($actualState === 'archived') {
                    $archivedCount++;
                    $this->log("⏭️ {$sellerId} negativeTarget archived状态，跳过: {$asin}");
                    continue;
                }
                if ($actualState === "enabled") {
                    $matchCount++;
                    $this->log("✅ {$sellerId} negativeTarget已投放且一致: {$asin}");
                } else {
                    $stateMismatchCount++;
                    $this->log("⚠️ {$sellerId} negativeTarget状态异常: {$asin} 期望enabled, 实际{$actualState}");
                    $targetId = (string)($existingAsins[$asin]['targetId'] ?? '');
                    $exportList[] = [
                        "channel" => $item['channel'] ?: ($sellerChannel ?: $channelLabel),
                        "seller_id" => $sellerId,
                        "target_id" => $targetId,
                        "ad_group_id" => $adGroupId,
                        "asin" => $asin,
                        "actual_state" => $actualState,
                        "expected_state" => "enabled",
                        "error" => "状态异常",
                    ];
                    if ($targetId !== "") {
                        $adIdChannelMap[$sellerId . '_' . $targetId] = $item['channel'] ?: ($sellerChannel ?: $channelLabel);
                    }
                }
            }
        }

        // 输出校验汇总
        $this->log("========== 校验汇总 ==========");
        $this->log("总校验数: {$verifiedCount}");
        $this->log("⏭️ archived跳过: {$archivedCount}");
        $this->log("✅ 已投放且一致: {$matchCount}");
        $this->log("❌ 未投放: {$notFoundCount}");
        $this->log("⚠️ 状态异常(非enabled): {$stateMismatchCount}");

        // 导出异常数据
        if (count($exportList) > 0) {
            $excelUtils = new ExcelUtils("sp/negativeTarget/");
            $filePath = $excelUtils->downloadXlsx([
                "channel",
                "seller_id",
                "target_id",
                "ad_group_id",
                "asin",
                "actual_state",
                "expected_state",
                "error",
            ], $exportList, "校验异常_negativeTarget_{$channelLabel}_" . date("YmdHis") . ".xlsx", [2, 3]);
            $this->log("异常数据已导出: {$filePath}");
        } else {
            $this->log("所有negativeTarget投放校验通过，无异常数据");
        }

        // 对失败的数据重新执行创建
        if (count($exportList) > 0) {
            $this->retryCreateNegativeTarget($exportList, $channelLabel, $adIdChannelMap);
        }

        $this->log("verifyNegativeTargets channel:{$channelLabel} 校验完毕");
    }

    /**
     * 对创建失败的negativeTarget数据重新执行创建
     * 输入数据格式: [{"channel"=>"xxx", "seller_id"=>"xxx", "target_id"=>"xxx",
     *                "ad_group_id"=>"xxx", "asin"=>"xxx",
     *                "actual_state"=>"xxx", "expected_state"=>"xxx"}, ...]
     * 重试逻辑与createNegativeTargets一致：已存在且enabled跳过，已存在但非enabled更新为enabled，
     * 不存在的重新创建（asinSameAs精准否定）；创建/更新成功后补写Mongo，仍然失败的导出Excel
     *
     * 用法(由verify内部调用，也可单独使用):
     *   php SpCreateNegativeTargetController.php method=retry file="校验异常_negativeTarget_xxx.xlsx" channel=amazon_us
     *       文件先从export/目录查找（verify导出的文件在此），找不到再从excel/目录查找
     *
     * @param array $failedList 创建失败数据列表，每项包含 channel/seller_id/target_id/ad_group_id/asin
     * @param string $channelLabel channel标签，用于日志和导出文件名
     * @param array $adIdChannelMap sellerId_targetId => channel 映射，用于获取channel（从Excel读取时传入）
     */
    public function retryCreateNegativeTarget($failedList = [], $channelLabel = '全部', $adIdChannelMap = [])
    {
        if (count($failedList) <= 0) {
            $this->log("retryCreateNegativeTarget 无需重试");
            return;
        }

        $this->log("========== 开始重新创建失败数据 ==========");
        $curlService = (new CurlService())->pro();
        $redisService = new RedisService();
        $spApi = new SpApi();

        // 按 seller_id + ad_group_id 分组（与createNegativeTargets一致）
        $groupedData = [];
        $totalCount = 0;
        foreach ($failedList as $item) {
            $sellerId = trim($item['seller_id'] ?? '');
            $adGroupId = trim(sprintf('%.0f', (float)($item['ad_group_id'] ?? 0)), "'");
            $asin = trim($item['asin'] ?? '');
            if ($sellerId === "" || $adGroupId === "" || $adGroupId === "0" || $asin === "") {
                $this->log("⚠️ retryCreateNegativeTarget 跳过数据不完整的失败项: " . json_encode($item, JSON_UNESCAPED_UNICODE));
                continue;
            }
            $groupKey = "{$sellerId}_{$adGroupId}";
            $groupedData[$groupKey][] = [
                'channel' => trim($item['channel'] ?? ''),
                'sellerId' => $sellerId,
                'adGroupId' => $adGroupId,
                'asin' => $asin,
                'targetId' => trim(sprintf('%.0f', (float)($item['target_id'] ?? 0)), "'"),
                'actualState' => trim($item['actual_state'] ?? ''),
            ];
            $totalCount++;
        }

        $this->log("channel:{$channelLabel} 共 " . count($groupedData) . " 个ad group, {$totalCount} 条数据待重试");

        if (count($groupedData) <= 0) {
            $this->log("retryCreateNegativeTarget channel:{$channelLabel} 无有效数据");
            return;
        }

        $retrySuccessCount = 0;
        $retrySkippedCount = 0;
        $retryFailedList = [];

        foreach ($groupedData as $groupKey => $items) {
            $sellerId = $items[0]['sellerId'];
            $adGroupId = $items[0]['adGroupId'];
            $sellerChannel = $spApi->sellerConfig($sellerId);
            $this->log("重新创建 {$sellerId} adGroupId:{$adGroupId} 共 " . count($items) . " 条数据");

            // 获取ad group信息（campaignId）
            $adGroupInfo = null;
            $campaignId = '';

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
            if ($adGroupInfo && isset($adGroupInfo['campaignId'])) {
                $campaignId = $adGroupInfo['campaignId'];
            }

            if ($campaignId === "") {
                $this->log("❌ {$sellerId} adGroupId:{$adGroupId} 重试时未找到ad group信息，跳过");
                foreach ($items as $item) {
                    $retryFailedList[] = [
                        "channel" => !empty($adIdChannelMap) ? ($adIdChannelMap[$sellerId . '_' . $item['targetId']] ?: ($sellerChannel ?: $channelLabel)) : ($sellerChannel ?: $channelLabel),
                        "seller_id" => $sellerId,
                        "target_id" => $item['targetId'],
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
            $this->log("{$sellerId} adGroupId:{$adGroupId} 重试前已有negativeTarget " . count($existingAsins) . "个");

            // 分类：需要更新状态为enabled的 / 需要重新创建的
            $updatePayloads = [];
            $updateItemMap = []; // targetId => 原始失败项，用于失败导出
            $createPayloads = [];
            foreach ($items as $item) {
                $asin = $item['asin'];
                if (isset($existingAsins[$asin])) {
                    $existingState = $existingAsins[$asin]['state'] ?? '';
                    if ($existingState !== 'enabled') {
                        // 已存在但非enabled，更新状态为enabled
                        $updatePayloads[] = [
                            "targetId" => (int)$existingAsins[$asin]['targetId'],
                            "state" => "enabled",
                        ];
                        $updateItemMap[(string)$existingAsins[$asin]['targetId']] = $item;
                        $this->log("🔄 {$sellerId} 重试发现negativeTarget已存在但非enabled({$existingState})，将更新: {$asin} targetId:{$existingAsins[$asin]['targetId']}");
                    } else {
                        // 已存在且enabled，跳过
                        $retrySkippedCount++;
                        $this->log("⏭️ {$sellerId} 重试发现negativeTarget已存在且enabled: {$asin}");
                    }
                    continue;
                }

                // 不存在，重新创建
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
            }

            // 预加载Redis缓存，用于后续更新mongo
            $sellerTargetList = $redisService->hGetAll("spNegativeTarget_{$sellerId}");

            // 批量更新negativeTarget状态为enabled
            $updateSuccessIds = [];
            $updateFailedIds = [];
            if (count($updatePayloads) > 0) {
                foreach (array_chunk($updatePayloads, 1000) as $chunk) {
                    $this->log("{$sellerId} adGroupId:{$adGroupId} 重试更新negativeTarget状态为enabled: " . count($chunk) . "个");
                    $result = $spApi->updateNegativeTarget($sellerId, $chunk);
                    foreach ($result['success'] ?? [] as $targetId) {
                        $updateSuccessIds[] = $targetId;
                        $retrySuccessCount++;
                        $this->log("✅ {$sellerId} 重试更新negativeTarget状态成功: targetId:{$targetId}");
                        if (isset($sellerTargetList[$targetId]) && $sellerTargetList[$targetId]) {
                            $spApi->mongoUpdateNegativeTarget($sellerTargetList[$targetId], $targetId, "enabled");
                        }
                    }
                    foreach ($result['error'] ?? [] as $targetId) {
                        $updateFailedIds[] = $targetId;
                        $this->log("❌ {$sellerId} 重试更新negativeTarget状态仍然失败: targetId:{$targetId}");
                    }
                }
            }

            // 补查mongo中negativeTarget的_id，补充更新
            if (count($updateSuccessIds) > 0) {
                $missingTargetIds = array_values(array_diff($updateSuccessIds, array_keys($sellerTargetList)));
                if (count($missingTargetIds) > 0) {
                    foreach (array_chunk($missingTargetIds, 200) as $chunk) {
                        $list = DataUtils::getPageList($curlService->s3023()->get("amazon_sp_negative_targets/queryPage", [
                            "channel" => $spApi->specialSellerIdConver($sellerId),
                            "targetId_in" => implode(',', $chunk),
                            "limit" => 200
                        ]));
                        if (count($list) > 0) {
                            foreach ($list as $info) {
                                $seller = $spApi->specialSellerIdReverseConver($info['channel']);
                                $redisService->hSet("spNegativeTarget_{$seller}", $info['targetId'], $info['_id']);
                                $sellerTargetList[$info['targetId']] = $info['_id'];
                                $spApi->mongoUpdateNegativeTarget($info['_id'], $info['targetId'], "enabled");
                            }
                        }
                    }
                }
            }

            // 更新仍然失败的加入失败列表
            foreach ($updateFailedIds as $targetId) {
                $item = $updateItemMap[(string)$targetId] ?? [];
                $retryFailedList[] = [
                    "channel" => !empty($adIdChannelMap) ? ($adIdChannelMap[$sellerId . '_' . $targetId] ?: ($sellerChannel ?: $channelLabel)) : ($sellerChannel ?: $channelLabel),
                    "seller_id" => $sellerId,
                    "target_id" => (string)$targetId,
                    "ad_group_id" => $adGroupId,
                    "asin" => $item['asin'] ?? "",
                    "actual_state" => $item['actualState'] ?? "",
                    "expected_state" => "enabled",
                    "error" => "更新状态失败",
                ];
            }

            // 批量重新创建negativeTarget
            if (count($createPayloads) > 0) {
                foreach (array_chunk($createPayloads, 1000) as $chunk) {
                    $this->log("{$sellerId} adGroupId:{$adGroupId} 重试创建negativeTarget: " . count($chunk) . "个");
                    $result = $spApi->createNegativeTargets($sellerId, $chunk);

                    foreach ($result['success'] as $successItem) {
                        $retrySuccessCount++;
                        $payload = $successItem['payload'];
                        $targetId = $successItem['id'];
                        $asin = $payload['expression'][0]['value'];
                        $this->log("✅ {$sellerId} 重试创建negativeTarget成功: {$targetId} - {$asin}");
                        // 写入Mongo
                        $spApi->mongoCreateNegativeTarget($sellerId, $campaignId, $adGroupId, $asin, $targetId);
                    }

                    foreach ($result['error'] as $errorItem) {
                        $payload = $errorItem['payload'];
                        $asin = $payload['expression'][0]['value'];
                        $this->log("❌ {$sellerId} 重试创建negativeTarget仍然失败: {$asin}");
                        $retryFailedList[] = [
                            "channel" => !empty($adIdChannelMap) ? ($adIdChannelMap[$sellerId . '_' . ($payload['targetId'] ?? "")] ?: ($sellerChannel ?: $channelLabel)) : ($sellerChannel ?: $channelLabel),
                            "seller_id" => $sellerId,
                            "target_id" => "",
                            "ad_group_id" => $adGroupId,
                            "asin" => $asin,
                            "actual_state" => "not_found",
                            "expected_state" => "enabled",
                            "error" => json_encode($errorItem['response'], JSON_UNESCAPED_UNICODE),
                        ];
                    }
                }
            }
        }

        // 输出重新创建汇总
        $this->log("========== 重新创建汇总 ==========");
        $this->log("重新创建总数: " . count($failedList));
        $this->log("✅ 重新创建成功: {$retrySuccessCount}");
        $this->log("⏭️ 已存在且enabled跳过: {$retrySkippedCount}");
        $this->log("❌ 重新创建仍然失败: " . count($retryFailedList));

        // 导出仍然失败的数据
        if (count($retryFailedList) > 0) {
            $excelUtilsRetry = new ExcelUtils("sp/negativeTarget/");
            $retryFilePath = $excelUtilsRetry->downloadXlsx([
                "channel",
                "seller_id",
                "target_id",
                "ad_group_id",
                "asin",
                "actual_state",
                "expected_state",
                "error",
            ], $retryFailedList, "重新创建仍失败_negativeTarget_{$channelLabel}_" . date("YmdHis") . ".xlsx", [2, 3]);
            $this->log("重新创建仍失败数据已导出: {$retryFilePath}");
        }
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
} elseif ($method == 'retry') {
    // 从导出的失败Excel读取数据，重新执行创建
    $channelLabel = empty($channel) ? '全部' : $channel;
    $excelUtils = new ExcelUtils();
    $failedList = [];
    $adIdChannelMap = [];
    try {
        // 优先查export目录（verify导出的文件在此），其次查excel目录，最后当绝对路径
        $filePath = __DIR__ . "/export/{$file}";
        if (!file_exists($filePath)) {
            $filePath = __DIR__ . "/excel/{$file}";
        }
        if (!file_exists($filePath) && file_exists($file)) {
            $filePath = $file;
        }
        if (!file_exists($filePath)) {
            die("❌ 文件不存在: export/{$file} 或 excel/{$file} 或 {$file}");
        }
        $excelUtils->eachXlsxRow($filePath, function ($item) use (&$failedList, &$adIdChannelMap, $channel) {
            $targetId = trim(sprintf('%.0f', (float)($item['target_id'] ?? 0)), "'");
            $sellerId = trim($item['seller_id'] ?? '');
            $ch = trim($item['channel'] ?? '');
            if ($sellerId === '') {
                return;
            }
            if (!empty($channel) && $ch !== $channel) {
                return;
            }
            $failedList[] = [
                "channel" => $ch,
                "seller_id" => $sellerId,
                "target_id" => $targetId,
                "ad_group_id" => trim(sprintf('%.0f', (float)($item['ad_group_id'] ?? 0)), "'"),
                "asin" => trim($item['asin'] ?? ''),
                "actual_state" => trim($item['actual_state'] ?? ''),
                "expected_state" => trim($item['expected_state'] ?? ''),
            ];
            if ($targetId !== '' && $targetId !== '0') {
                $adIdChannelMap[$sellerId . '_' . $targetId] = $ch;
            }
        });
    } catch (Exception $e) {
        die($e->getLine() . " : " . $e->getMessage());
    }
    $con->retryCreateNegativeTarget($failedList, $channelLabel, $adIdChannelMap);
} else {
    $con->createNegativeTargets($file, $channel);
}
