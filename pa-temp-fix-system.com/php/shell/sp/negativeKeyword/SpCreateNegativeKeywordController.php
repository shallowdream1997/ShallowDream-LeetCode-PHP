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

                // M6精准否定keyword无否定类型列，全部为exact精准否定
                $matchType = 'exact';

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

            // 查询Amazon已有的negativeKeyword
            $existingList = $spApi->listNegativeKeyword($sellerId, [$campaignId], [$adGroupId], "enabled");
            $existingMap = [];
            foreach ($existingList as $info) {
                $key = "{$info['matchType']}_{$info['keywordText']}";
                $existingMap[$key] = $info;
            }
            $this->log("{$sellerId} adGroupId:{$adGroupId} 已有negativeKeyword " . count($existingMap) . "个");

            // 检查哪些需要新建
            $createPayloads = [];
            foreach ($items as $item) {
                $key = "{$item['matchType']}_{$item['keywordText']}";
                if (isset($existingMap[$key])) {
                    $skippedCount++;
                    $this->log("⏭️ {$sellerId} negativeKeyword已存在: {$key}");
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
        $this->log("⏭️ 已存在跳过: {$skippedCount}");
        $this->log("❌ 失败: " . count($exportList));

        if (count($exportList) > 0) {
            $excelUtils = new ExcelUtils("sp/negativeKeyword/");
            $filePath = $excelUtils->downloadXlsx([
                "seller_id",
                "ad_group_id",
                "keyword_text",
                "match_type",
                "error",
            ], $exportList, "创建negativeKeyword失败_{$channelLabel}_" . date("YmdHis") . ".xlsx");
            $this->log("失败数据已导出: {$filePath}");
        }

        $this->log("createNegativeKeywords channel:{$channelLabel} 处理完毕");
    }
}

$parameters = DataUtils::ExplainArgv(@$argv, array());
$params = (count(@$argv) > 1) ? $parameters : $_REQUEST;
$file = "";
$channel = "";
if (isset($params['file']) && trim($params['file']) != '') {
    $file = $params['file'];
}
if (isset($params['channel']) && trim($params['channel']) != '') {
    $channel = $params['channel'];
}
$con = new SpCreateNegativeKeywordController();
$con->createNegativeKeywords($file, $channel);
