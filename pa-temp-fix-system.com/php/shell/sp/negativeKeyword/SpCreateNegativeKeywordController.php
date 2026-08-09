<?php
require_once(dirname(__FILE__) . "/../../../../php/requiredfile/requiredfile.php");
require_once(dirname(__FILE__) . "/../../../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/RequestUtils.php");
require_once(dirname(__FILE__) . "/../SpApi.php");

class SpCreateNegativeKeywordController
{
    private $log;

    public function __construct()
    {
        $this->log = new MyLogger("sp/negativeKeyword");
    }

    private function log(string $string = "")
    {
        $this->log->log2($string);
    }

    /**
     * 读取Excel创建negativeKeyword广告，已存在则跳过并补写Mongo
     * Excel格式: channel | seller_id | campaign_id | ad_group_id | keywordtext
     * 用法: php SpCreateNegativeKeywordController.php file="M6精准否定keyword.xlsx" channel=amazon_us
     *       php SpCreateNegativeKeywordController.php file="M6精准否定keyword.xlsx"  (处理全部channel)
     * @param string $file Excel文件名(在./excel/目录下)
     * @param string $channel 可选，按channel过滤数据，不传则处理全部
     */
    public function createNegativeKeywords($file = "", $channel = "")
    {
        $channelLabel = empty($channel) ? '全部' : $channel;
        $this->log("createNegativeKeywords 开始处理 file:{$file} channel:{$channelLabel}");
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
                $keywordText = trim($item['keywordtext'] ?? '');

                if ($sellerId === "" || $adGroupId === "" || $adGroupId === "0" || $keywordText === "") {
                    return;
                }
                if (!empty($channel) && $itemChannel !== $channel) {
                    return;
                }

                // M6精准否定keyword无否定类型列，全部为negativeExact精准否定
                $matchType = 'negativeExact';

                $groupKey = "{$sellerId}_{$adGroupId}";
                $groupedData[$groupKey][] = [
                    'channel' => $itemChannel,
                    'sellerId' => $sellerId,
                    'campaignId' => $campaignId,
                    'adGroupId' => $adGroupId,
                    'keywordText' => $keywordText,
                    'matchType' => $matchType,
                ];
                $totalCount++;
            });
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }

        $this->log("channel:{$channelLabel} 共 " . count($groupedData) . " 个ad group, {$totalCount} 条数据");

        if (count($groupedData) <= 0) {
            $this->log("createNegativeKeywords channel:{$channelLabel} 无数据");
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
                        "keyword_text" => $item['keywordText'],
                        "match_type" => $item['matchType'],
                        "error" => "ad group not found",
                    ];
                }
                continue;
            }

            // 查询Amazon已有的negativeKeyword（所有状态）
            $existingList = $spApi->listNegativeKeyword($sellerId, [$campaignId], [$adGroupId], "");
            $existingMap = [];
            foreach ($existingList as $info) {
                $key = "{$info['matchType']}_{$info['keywordText']}";
                $existingMap[$key] = $info;
            }
            $this->log("{$sellerId} adGroupId:{$adGroupId} 已有negativeKeyword " . count($existingMap) . "个");

            // 检查哪些需要新建，哪些需要更新状态
            $createPayloads = [];
            $updatePayloads = []; // 需要更新状态为enabled的negativeKeyword
            foreach ($items as $item) {
                $key = "{$item['matchType']}_{$item['keywordText']}";
                if (isset($existingMap[$key])) {
                    $existingState = $existingMap[$key]['state'] ?? '';
                    if ($existingState !== 'enabled') {
                        // 已存在但非enabled，更新状态为enabled
                        $updatePayloads[] = [
                            "keywordId" => (int)$existingMap[$key]['keywordId'],
                            "state" => "enabled",
                        ];
                        $this->log("🔄 {$sellerId} negativeKeyword已存在但非enabled({$existingState})，将更新: {$key} keywordId:{$existingMap[$key]['keywordId']}");
                    } else {
                        // 已存在且enabled，跳过
                        $skippedCount++;
                        $this->log("⏭️ {$sellerId} negativeKeyword已存在且enabled: {$key}");
                    }
                    continue;
                }

                $createPayloads[] = [
                    "campaignId" => (int)$campaignId,
                    "adGroupId" => (int)$adGroupId,
                    "keywordText" => $item['keywordText'],
                    "matchType" => $item['matchType'],
                    "state" => "enabled",
                ];
            }

            // 批量更新negativeKeyword状态为enabled
            if (count($updatePayloads) > 0) {
                foreach (array_chunk($updatePayloads, 1000) as $chunk) {
                    $this->log("{$sellerId} adGroupId:{$adGroupId} 更新negativeKeyword状态为enabled: " . count($chunk) . "个");
                    $result = $spApi->updateNegativeKeyword($sellerId, $chunk);
                    $updatedCount += count($result['success'] ?? []);
                    foreach ($result['success'] ?? [] as $keywordId) {
                        $this->log("✅ {$sellerId} 更新negativeKeyword状态成功: keywordId:{$keywordId}");
                    }
                    foreach ($result['error'] ?? [] as $keywordId) {
                        $this->log("❌ {$sellerId} 更新negativeKeyword状态失败: keywordId:{$keywordId}");
                        $exportList[] = [
                            "seller_id" => $sellerId,
                            "ad_group_id" => $adGroupId,
                            "keyword_text" => "",
                            "match_type" => "",
                            "error" => "更新状态失败 keywordId:{$keywordId}",
                        ];
                    }
                }
            }

            // 批量创建negativeKeyword
            if (count($createPayloads) > 0) {
                foreach (array_chunk($createPayloads, 1000) as $chunk) {
                    $this->log("{$sellerId} adGroupId:{$adGroupId} 创建negativeKeyword: " . count($chunk) . "个");
                    $result = $spApi->createNegativeKeywords($sellerId, $chunk);

                    foreach ($result['success'] as $successItem) {
                        $createdCount++;
                        $payload = $successItem['payload'];
                        $keywordId = $successItem['id'];
                        $this->log("✅ {$sellerId} 创建negativeKeyword成功: {$keywordId} - {$payload['matchType']}_{$payload['keywordText']}");
                        $spApi->mongoCreateNegativeKeyword($sellerId, $campaignId, $adGroupId, $payload['keywordText'], $payload['matchType'], $keywordId);
                    }

                    foreach ($result['error'] as $errorItem) {
                        $payload = $errorItem['payload'];
                        $this->log("❌ {$sellerId} 创建negativeKeyword失败: {$payload['matchType']}_{$payload['keywordText']}");
                        $exportList[] = [
                            "seller_id" => $sellerId,
                            "ad_group_id" => $adGroupId,
                            "keyword_text" => $payload['keywordText'],
                            "match_type" => $payload['matchType'],
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
            $excelUtils = new ExcelUtils("sp/negativeKeyword/");
            $filePath = $excelUtils->downloadXlsx([
                "seller_id",
                "ad_group_id",
                "keyword_text",
                "match_type",
                "error",
            ], $exportList, "创建negativeKeyword失败_{$channelLabel}_" . date("YmdHis") . ".xlsx", [1]);
            $this->log("失败数据已导出: {$filePath}");
        }

        $this->log("createNegativeKeywords channel:{$channelLabel} 处理完毕");
    }

    /**
     * 校验Excel中的negativeKeyword投放数据是否已在Amazon上成功投放
     * Excel格式: channel | seller_id | campaign_id | ad_group_id | keywordtext
     * 用法: php SpCreateNegativeKeywordController.php method=verify file="M6精准否定keyword.xlsx" channel=amazon_us
     *       php SpCreateNegativeKeywordController.php method=verify file="M6精准否定keyword.xlsx"  (校验全部channel)
     * @param string $file Excel文件名(在./excel/目录下)
     * @param string $channel 可选，按channel过滤数据，不传则校验全部
     */
    public function verifyNegativeKeywords($file = "", $channel = "")
    {
        $channelLabel = empty($channel) ? '全部' : $channel;
        $this->log("verifyNegativeKeywords 开始校验 file:{$file} channel:{$channelLabel}");
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
                $keywordText = trim($item['keywordtext'] ?? '');

                if ($sellerId === "" || $adGroupId === "" || $adGroupId === "0" || $keywordText === "") {
                    return;
                }
                if (!empty($channel) && $itemChannel !== $channel) {
                    return;
                }

                // M6精准否定keyword无否定类型列，全部为exact精准否定
                $matchType = 'negativeExact';

                $groupKey = "{$sellerId}_{$adGroupId}";
                $groupedData[$groupKey][] = [
                    'channel' => $itemChannel,
                    'sellerId' => $sellerId,
                    'campaignId' => $campaignId,
                    'adGroupId' => $adGroupId,
                    'keywordText' => $keywordText,
                    'matchType' => $matchType,
                ];
                $totalCount++;
            });
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }

        $this->log("channel:{$channelLabel} 共 " . count($groupedData) . " 个ad group, {$totalCount} 条数据");

        if (count($groupedData) <= 0) {
            $this->log("verifyNegativeKeywords channel:{$channelLabel} 无数据");
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
                        "keyword_id" => "",
                        "ad_group_id" => $adGroupId,
                        "keyword_text" => $item['keywordText'],
                        "match_type" => $item['matchType'],
                        "actual_state" => "",
                        "expected_state" => "enabled",
                        "error" => "ad group not found",
                    ];
                }
                continue;
            }

            // 查询Amazon已有的negativeKeyword（所有状态）
            $existingList = $spApi->listNegativeKeyword($sellerId, [$campaignId], [$adGroupId], "");
            $existingMap = [];
            foreach ($existingList as $info) {
                $key = "{$info['matchType']}_{$info['keywordText']}";
                $existingMap[$key] = $info;
            }
            $this->log("{$sellerId} adGroupId:{$adGroupId} Amazon已有negativeKeyword " . count($existingMap) . "个");

            // 逐条校验
            foreach ($items as $item) {
                $verifiedCount++;
                $key = "{$item['matchType']}_{$item['keywordText']}";

                if (!isset($existingMap[$key])) {
                    // 未投放
                    $notFoundCount++;
                    $this->log("❌ {$sellerId} negativeKeyword未投放: {$key}");
                    $exportList[] = [
                        "channel" => $item['channel'] ?: ($sellerChannel ?: $channelLabel),
                        "seller_id" => $sellerId,
                        "keyword_id" => "",
                        "ad_group_id" => $adGroupId,
                        "keyword_text" => $item['keywordText'],
                        "match_type" => $item['matchType'],
                        "actual_state" => "not_found",
                        "expected_state" => "enabled",
                        "error" => "negativeKeyword未投放",
                    ];
                    continue;
                }

                // 已投放，校验state
                $actualState = $existingMap[$key]['state'] ?? '';
                // archived状态不统计，直接跳过
                if ($actualState === 'archived') {
                    $archivedCount++;
                    $this->log("⏭️ {$sellerId} negativeKeyword archived状态，跳过: {$key}");
                    continue;
                }
                if ($actualState === "enabled") {
                    $matchCount++;
                    $this->log("✅ {$sellerId} negativeKeyword已投放且一致: {$key}");
                } else {
                    $stateMismatchCount++;
                    $this->log("⚠️ {$sellerId} negativeKeyword状态异常: {$key} 期望enabled, 实际{$actualState}");
                    $keywordId = (string)($existingMap[$key]['keywordId'] ?? '');
                    $exportList[] = [
                        "channel" => $item['channel'] ?: ($sellerChannel ?: $channelLabel),
                        "seller_id" => $sellerId,
                        "keyword_id" => $keywordId,
                        "ad_group_id" => $adGroupId,
                        "keyword_text" => $item['keywordText'],
                        "match_type" => $item['matchType'],
                        "actual_state" => $actualState,
                        "expected_state" => "enabled",
                        "error" => "状态异常",
                    ];
                    if ($keywordId !== "") {
                        $adIdChannelMap[$sellerId . '_' . $keywordId] = $item['channel'] ?: ($sellerChannel ?: $channelLabel);
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
            $excelUtils = new ExcelUtils("sp/negativeKeyword/");
            $filePath = $excelUtils->downloadXlsx([
                "channel",
                "seller_id",
                "keyword_id",
                "ad_group_id",
                "keyword_text",
                "match_type",
                "actual_state",
                "expected_state",
                "error",
            ], $exportList, "校验异常_negativeKeyword_{$channelLabel}_" . date("YmdHis") . ".xlsx", [2, 3]);
            $this->log("异常数据已导出: {$filePath}");
        } else {
            $this->log("所有negativeKeyword投放校验通过，无异常数据");
        }

        // 对失败的数据重新执行创建
        if (count($exportList) > 0) {
            $this->retryCreateNegativeKeyword($exportList, $channelLabel, $adIdChannelMap);
        }

        $this->log("verifyNegativeKeywords channel:{$channelLabel} 校验完毕");
    }

    /**
     * 对创建失败的negativeKeyword数据重新执行创建
     * 输入数据格式: [{"channel"=>"xxx", "seller_id"=>"xxx", "keyword_id"=>"xxx",
     *                "ad_group_id"=>"xxx", "keyword_text"=>"xxx", "match_type"=>"xxx",
     *                "actual_state"=>"xxx", "expected_state"=>"xxx"}, ...]
     * 重试逻辑与createNegativeKeywords一致：已存在且enabled跳过，已存在但非enabled更新为enabled，
     * 不存在的重新创建（negativeExact精准否定）；创建/更新成功后补写Mongo，仍然失败的导出Excel
     *
     * 用法(由verify内部调用，也可单独使用):
     *   php SpCreateNegativeKeywordController.php method=retry file="校验异常_negativeKeyword_xxx.xlsx" channel=amazon_us
     *       文件先从export/目录查找（verify导出的文件在此），找不到再从excel/目录查找
     *
     * @param array $failedList 创建失败数据列表，每项包含 channel/seller_id/keyword_id/ad_group_id/keyword_text/match_type
     * @param string $channelLabel channel标签，用于日志和导出文件名
     * @param array $adIdChannelMap sellerId_keywordId => channel 映射，用于获取channel（从Excel读取时传入）
     */
    public function retryCreateNegativeKeyword($failedList = [], $channelLabel = '全部', $adIdChannelMap = [])
    {
        if (count($failedList) <= 0) {
            $this->log("retryCreateNegativeKeyword 无需重试");
            return;
        }

        $this->log("========== 开始重新创建失败数据 ==========");
        $curlService = (new CurlService())->pro();
        $redisService = new RedisService();
        $spApi = new SpApi();

        // 按 seller_id + ad_group_id 分组（与createNegativeKeywords一致）
        $groupedData = [];
        $totalCount = 0;
        foreach ($failedList as $item) {
            $sellerId = trim($item['seller_id'] ?? '');
            $adGroupId = trim(sprintf('%.0f', (float)($item['ad_group_id'] ?? 0)), "'");
            $keywordText = trim($item['keyword_text'] ?? '');
            // 兼容旧文件缺失match_type列，默认negativeExact精准否定
            $matchType = strtolower(trim($item['match_type'] ?? ''));
            if ($matchType === "") {
                $matchType = 'negativeExact';
            }
            if ($sellerId === "" || $adGroupId === "" || $adGroupId === "0" || $keywordText === "") {
                $this->log("⚠️ retryCreateNegativeKeyword 跳过数据不完整的失败项: " . json_encode($item, JSON_UNESCAPED_UNICODE));
                continue;
            }
            $groupKey = "{$sellerId}_{$adGroupId}";
            $groupedData[$groupKey][] = [
                'channel' => trim($item['channel'] ?? ''),
                'sellerId' => $sellerId,
                'adGroupId' => $adGroupId,
                'keywordText' => $keywordText,
                'matchType' => $matchType,
                'keywordId' => trim(sprintf('%.0f', (float)($item['keyword_id'] ?? 0)), "'"),
                'actualState' => trim($item['actual_state'] ?? ''),
            ];
            $totalCount++;
        }

        $this->log("channel:{$channelLabel} 共 " . count($groupedData) . " 个ad group, {$totalCount} 条数据待重试");

        if (count($groupedData) <= 0) {
            $this->log("retryCreateNegativeKeyword channel:{$channelLabel} 无有效数据");
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
                        "channel" => !empty($adIdChannelMap) ? ($adIdChannelMap[$sellerId . '_' . $item['keywordId']] ?: ($sellerChannel ?: $channelLabel)) : ($sellerChannel ?: $channelLabel),
                        "seller_id" => $sellerId,
                        "keyword_id" => $item['keywordId'],
                        "ad_group_id" => $adGroupId,
                        "keyword_text" => $item['keywordText'],
                        "match_type" => $item['matchType'],
                        "actual_state" => "",
                        "expected_state" => "enabled",
                        "error" => "ad group not found",
                        "message" => "API创建失败",
                    ];
                }
                continue;
            }

            // 查询Amazon已有的negativeKeyword（所有状态）
            $existingList = $spApi->listNegativeKeyword($sellerId, [$campaignId], [$adGroupId], "");
            $existingMap = [];
            foreach ($existingList as $info) {
                $key = "{$info['matchType']}_{$info['keywordText']}";
                $existingMap[$key] = $info;
            }
            $this->log("{$sellerId} adGroupId:{$adGroupId} 重试前已有negativeKeyword " . count($existingMap) . "个");

            // 分类：需要更新状态为enabled的 / 需要重新创建的
            $updatePayloads = [];
            $updateItemMap = []; // keywordId => 原始失败项，用于失败导出
            $createPayloads = [];
            foreach ($items as $item) {
                $key = "{$item['matchType']}_{$item['keywordText']}";
                if (isset($existingMap[$key])) {
                    $existingState = $existingMap[$key]['state'] ?? '';
                    if ($existingState !== 'enabled') {
                        // 已存在但非enabled，更新状态为enabled
                        $updatePayloads[] = [
                            "keywordId" => (int)$existingMap[$key]['keywordId'],
                            "state" => "enabled",
                        ];
                        $updateItemMap[(string)$existingMap[$key]['keywordId']] = $item;
                        $this->log("🔄 {$sellerId} 重试发现negativeKeyword已存在但非enabled({$existingState})，将更新: {$key} keywordId:{$existingMap[$key]['keywordId']}");
                    } else {
                        // 已存在且enabled，跳过
                        $retrySkippedCount++;
                        $this->log("⏭️ {$sellerId} 重试发现negativeKeyword已存在且enabled: {$key}");
                    }
                    continue;
                }

                // 不存在，重新创建
                $createPayloads[] = [
                    "campaignId" => (int)$campaignId,
                    "adGroupId" => (int)$adGroupId,
                    "keywordText" => $item['keywordText'],
                    "matchType" => $item['matchType'],
                    "state" => "enabled",
                ];
            }

            // 预加载Redis缓存，用于后续更新mongo
            $sellerKeywordList = $redisService->hGetAll("spNegativeKeyword_{$sellerId}");

            // 批量更新negativeKeyword状态为enabled
            $updateSuccessIds = [];
            $updateFailedIds = [];
            $updateErrorMsg = [];
            if (count($updatePayloads) > 0) {
                foreach (array_chunk($updatePayloads, 1000) as $chunk) {
                    $this->log("{$sellerId} adGroupId:{$adGroupId} 重试更新negativeKeyword状态为enabled: " . count($chunk) . "个");
                    $result = $spApi->updateNegativeKeyword($sellerId, $chunk);
                    $updateErrorMsg = $updateErrorMsg + ($result['errorMsg'] ?? []);
                    $batchUpdateList = [];
                    foreach ($result['success'] ?? [] as $keywordId) {
                        $updateSuccessIds[] = $keywordId;
                        $retrySuccessCount++;
                        $this->log("✅ {$sellerId} 重试更新negativeKeyword状态成功: keywordId:{$keywordId}");
                        if (isset($sellerKeywordList[$keywordId]) && $sellerKeywordList[$keywordId]) {
                            $batchUpdateList[] = [
                                '_id' => $sellerKeywordList[$keywordId],
                                'keywordId' => $keywordId,
                                'state' => 'enabled'
                            ];
                        }
                    }
                    if (!empty($batchUpdateList)) {
                        $spApi->batchMongoUpdateNegativeKeyword($batchUpdateList);
                    }
                    foreach ($result['error'] ?? [] as $keywordId) {
                        $updateFailedIds[] = $keywordId;
                        $this->log("❌ {$sellerId} 重试更新negativeKeyword状态仍然失败: keywordId:{$keywordId}");
                    }
                }
            }

            // 补查mongo中negativeKeyword的_id，补充更新
            if (count($updateSuccessIds) > 0) {
                $missingKeywordIds = array_values(array_diff($updateSuccessIds, array_keys($sellerKeywordList)));
                if (count($missingKeywordIds) > 0) {
                    foreach (array_chunk($missingKeywordIds, 200) as $chunk) {
                        $list = DataUtils::getPageList($curlService->s3023()->get("amazon_sp_negativeKeywords/queryPage", [
                            "channel" => $spApi->specialSellerIdConver($sellerId),
                            "keywordId_in" => implode(',', $chunk),
                            "limit" => 200
                        ]));
                        if (count($list) > 0) {
                            $batchUpdateList = [];
                            foreach ($list as $info) {
                                $seller = $spApi->specialSellerIdReverseConver($info['channel']);
                                $redisService->hSet("spNegativeKeyword_{$seller}", $info['keywordId'], $info['_id']);
                                $sellerKeywordList[$info['keywordId']] = $info['_id'];
                                $batchUpdateList[] = [
                                    '_id' => $info['_id'],
                                    'keywordId' => $info['keywordId'],
                                    'state' => 'enabled'
                                ];
                            }
                            if (!empty($batchUpdateList)) {
                                $spApi->batchMongoUpdateNegativeKeyword($batchUpdateList);
                            }
                        }
                    }
                }
            }

            // 更新仍然失败的加入失败列表
            foreach ($updateFailedIds as $keywordId) {
                $item = $updateItemMap[(string)$keywordId] ?? [];
                $retryFailedList[] = [
                    "channel" => !empty($adIdChannelMap) ? ($adIdChannelMap[$sellerId . '_' . $keywordId] ?: ($sellerChannel ?: $channelLabel)) : ($sellerChannel ?: $channelLabel),
                    "seller_id" => $sellerId,
                    "keyword_id" => (string)$keywordId,
                    "ad_group_id" => $adGroupId,
                    "keyword_text" => $item['keywordText'] ?? "",
                    "match_type" => $item['matchType'] ?? "",
                    "actual_state" => $item['actualState'] ?? "",
                    "expected_state" => "enabled",
                    "error" => "更新状态失败",
                    "message" => $updateErrorMsg[$keywordId] ?? "API创建失败",
                ];
            }

            // 批量重新创建negativeKeyword
            if (count($createPayloads) > 0) {
                foreach (array_chunk($createPayloads, 1000) as $chunk) {
                    $this->log("{$sellerId} adGroupId:{$adGroupId} 重试创建negativeKeyword: " . count($chunk) . "个");
                    $result = $spApi->createNegativeKeywords($sellerId, $chunk);

                    foreach ($result['success'] as $successItem) {
                        $retrySuccessCount++;
                        $payload = $successItem['payload'];
                        $keywordId = $successItem['id'];
                        $this->log("✅ {$sellerId} 重试创建negativeKeyword成功: {$keywordId} - {$payload['matchType']}_{$payload['keywordText']}");
                        // 写入Mongo
                        $spApi->mongoCreateNegativeKeyword($sellerId, $campaignId, $adGroupId, $payload['keywordText'], $payload['matchType'], $keywordId);
                    }

                    foreach ($result['error'] as $errorItem) {
                        $payload = $errorItem['payload'];
                        $this->log("❌ {$sellerId} 重试创建negativeKeyword仍然失败: {$payload['matchType']}_{$payload['keywordText']}");
                        $retryFailedList[] = [
                            "channel" => !empty($adIdChannelMap) ? ($adIdChannelMap[$sellerId . '_' . ($payload['keywordId'] ?? "")] ?: ($sellerChannel ?: $channelLabel)) : ($sellerChannel ?: $channelLabel),
                            "seller_id" => $sellerId,
                            "keyword_id" => "",
                            "ad_group_id" => $adGroupId,
                            "keyword_text" => $payload['keywordText'],
                            "match_type" => $payload['matchType'],
                            "actual_state" => "not_found",
                            "expected_state" => "enabled",
                            "error" => json_encode($errorItem['response'], JSON_UNESCAPED_UNICODE),
                            "message" => $errorItem['response']['description'] ?? "API创建失败",
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
            $excelUtilsRetry = new ExcelUtils("sp/negativeKeyword/");
            $retryFilePath = $excelUtilsRetry->downloadXlsx([
                "channel",
                "seller_id",
                "keyword_id",
                "ad_group_id",
                "keyword_text",
                "match_type",
                "actual_state",
                "expected_state",
                "error",
                "message",
            ], $retryFailedList, "重新创建仍失败_negativeKeyword_{$channelLabel}_" . date("YmdHis") . ".xlsx", [2, 3]);
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
$con = new SpCreateNegativeKeywordController();
if ($method == 'verify') {
    $con->verifyNegativeKeywords($file, $channel);
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
            $keywordId = trim(sprintf('%.0f', (float)($item['keyword_id'] ?? 0)), "'");
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
                "keyword_id" => $keywordId,
                "ad_group_id" => trim(sprintf('%.0f', (float)($item['ad_group_id'] ?? 0)), "'"),
                "keyword_text" => trim($item['keyword_text'] ?? ''),
                "match_type" => trim($item['match_type'] ?? ''),
                "actual_state" => trim($item['actual_state'] ?? ''),
                "expected_state" => trim($item['expected_state'] ?? ''),
            ];
            if ($keywordId !== '' && $keywordId !== '0') {
                $adIdChannelMap[$sellerId . '_' . $keywordId] = $ch;
            }
        });
    } catch (Exception $e) {
        die($e->getLine() . " : " . $e->getMessage());
    }
    $con->retryCreateNegativeKeyword($failedList, $channelLabel, $adIdChannelMap);
} else {
    $con->createNegativeKeywords($file, $channel);
}
