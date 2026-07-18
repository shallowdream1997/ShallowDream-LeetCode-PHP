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
     * Excel格式: channel | seller_id | ad_group_id | search_term-可直接复制 | BID
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
                $adGroupId = trim(sprintf('%.0f', (float)($item['ad_group_id'] ?? 0)), "'");
                $asin = trim($item['search_term-可直接复制'] ?? '');
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

        foreach ($groupedData as $groupKey => $items) {
            $sellerId = $items[0]['sellerId'];
            $adGroupId = $items[0]['adGroupId'];

            // 获取ad group信息（campaignId、defaultBid）：先查Mongo，查不到查Amazon API
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
            if (!$adGroupInfo || !isset($adGroupInfo['campaignId'])) {
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
            $campaignId = $adGroupInfo['campaignId'];
            $defaultBid = $adGroupInfo['defaultBid'] ?? null;

            // 查询Amazon已有的target
            $existingTargets = $spApi->listTargetAsin($sellerId, $campaignId, $adGroupId);
            $this->log("{$sellerId} adGroupId:{$adGroupId} 已有target " . count($existingTargets) . "个");

            // 检查哪些需要新建
            $createPayloads = [];
            foreach ($items as $item) {
                $asin = $item['asin'];
                if (isset($existingTargets[$asin])) {
                    $skippedCount++;
                    $this->log("⏭️ {$sellerId} target已存在: {$asin}");
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
        $this->log("⏭️ 已存在跳过: {$skippedCount}");
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
$con = new SpCreateTargetController();
$con->createTargets($file, $channel);
