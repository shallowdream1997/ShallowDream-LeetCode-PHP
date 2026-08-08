<?php
require_once(dirname(__FILE__) . "/../../../../php/requiredfile/requiredfile.php");
require_once(dirname(__FILE__) . "/../../../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/RequestUtils.php");
require_once(dirname(__FILE__) . "/../SpApi.php");

class SpPausedProductController
{
    private $log;

    public function __construct()
    {
        $this->log = new MyLogger("sp/product");
    }

    private function log(string $string = "")
    {
        $this->log->log2($string);
    }


    /**
     * 校验product广告状态是否正确修改为paused
     * 通过Amazon API查询product ad的实际状态，与期望状态对比
     * 只导出关停失败（状态不等于paused）的数据，格式与关停输入一致，可直接重新执行关停
     * archived状态不统计，not_found只记录日志不导出
     * 用法: php SpPausedProductController.php method=verify file="M4-M6 关停清单v4.xlsx" channel=amazon_us
     * @param string $file Excel文件名(在./excel/目录下)
     * @param string $channel 必填，按channel过滤数据，可选值: amazon_us, amazon_uk, amazon_ca等
     */
    public function verifyPausedProducts($file = "", $channel = ""){
        $channelLabel = empty($channel) ? '全部' : $channel;
        $this->log("verifyPausedProducts 开始校验 file:{$file} channel:{$channelLabel}");
        $excelUtils = new ExcelUtils();
        $spApi = new SpApi();

        // 读取Excel，按seller_id分组收集ad_id，同时记录每条数据的channel
        $sellerIdAdId = [];
        $adIdChannelMap = [];
        $totalAdIdCount = 0;
        try {
            $excelUtils->eachXlsxRow(__DIR__."/excel/{$file}", function ($item) use (&$sellerIdAdId, &$adIdChannelMap, &$totalAdIdCount, $channel) {
                $adId = trim(sprintf('%.0f', (float)($item['ad_id'] ?? 0)), "'");
                $sellerId = trim($item['seller_id'] ?? '');
                $ch = trim($item['channel'] ?? '');
                if ($adId === '' || $adId === '0' || $sellerId === '') {
                    return;
                }
                if (!empty($channel) && $ch !== $channel) {
                    return;
                }
                $sellerIdAdId[$sellerId][] = $adId;
                $adIdChannelMap[$sellerId . '_' . $adId] = $ch;
                $totalAdIdCount++;
            });
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }

        $this->log("channel:{$channelLabel} 共 " . count($sellerIdAdId) . " 个seller, {$totalAdIdCount} 个adId");

        if (count($sellerIdAdId) <= 0) {
            $this->log("verifyPausedProducts channel:{$channelLabel} 无数据");
            return;
        }

        $exportList = [];
        $verifiedCount = 0;
        $archivedCount = 0;
        $pausedCount = 0;
        $notPausedCount = 0;
        $notFoundCount = 0;

        foreach ($sellerIdAdId as $sellerId => $adIds){
            $sellerChannel = $spApi->sellerConfig($sellerId);
            $this->log("{$sellerId} 开始校验 " . count($adIds) . " 个adId");

            // 分批查询Amazon API，每批最多100个adId（Amazon API限制）
            foreach (array_chunk($adIds, 100) as $chunk){
                $adIdsStr = implode(",", $chunk);
                $this->log("查询Amazon API: {$sellerId} adIds: {$adIdsStr}");

                $adListInfo = $spApi->listProductV2($sellerId, $adIdsStr);

                foreach ($chunk as $adId){
                    if (isset($adListInfo[$adId])){
                        $actualState = $adListInfo[$adId];
                        // archived状态不统计，直接跳过
                        if ($actualState === 'archived') {
                            $archivedCount++;
                            $this->log("⏭️ {$sellerId} adId:{$adId} archived状态，跳过");
                            continue;
                        }
                        $verifiedCount++;
                        if ($actualState === "paused"){
                            $pausedCount++;
                        } else {
                            $notPausedCount++;
                            $this->log("❌ {$sellerId} adId:{$adId} 关停失败: 期望paused, 实际{$actualState}");
                            $itemChannel = $adIdChannelMap[$sellerId . '_' . $adId] ?: ($sellerChannel ?: $channelLabel);
                            $exportList[] = [
                                "channel" => $itemChannel,
                                "seller_id" => $sellerId,
                                "ad_id" => (string)$adId,
                            ];
                        }
                    } else {
                        $notFoundCount++;
                        $this->log("⚠️ {$sellerId} adId:{$adId} Amazon API未返回该adId数据");
                    }
                }
            }
        }

        // 输出校验汇总
        $this->log("========== 校验汇总 ==========");
        $this->log("总数据: {$totalAdIdCount}");
        $this->log("⏭️ archived跳过: {$archivedCount}");
        $this->log("⚠️ 未找到(not_found): {$notFoundCount}");
        $this->log("已校验: {$verifiedCount}");
        $this->log("✅ 已暂停(paused): {$pausedCount}");
        $this->log("❌ 关停失败(非paused): {$notPausedCount}");

        // 导出关停失败的数据，格式与关停输入一致，可直接重新执行关停
        if (count($exportList) > 0){
            $excelUtilsExport = new ExcelUtils("sp/product/");
            $filePath = $excelUtilsExport->downloadXlsx([
                "channel",
                "seller_id",
                "ad_id",
            ], $exportList, "关停失败_product_{$channelLabel}_" . date("YmdHis") . ".xlsx", [2]);
            $this->log("关停失败数据已导出: {$filePath}");
        } else {
            $this->log("所有product广告状态校验通过，无关停失败数据");
        }

        // 对关停失败的数据重新执行关停
        if (count($exportList) > 0) {
            $this->retryPausedProducts($exportList, $channelLabel, $adIdChannelMap);
        }

        $this->log("verifyPausedProducts channel:{$channelLabel} 校验完毕");
    }


    /**
     * 对关停失败的product数据重新执行关停
     * 输入数据格式: [{"channel"=>"xxx", "seller_id"=>"xxx", "ad_id"=>"xxx"}, ...]
     * 关停成功则更新mongo，仍然失败的导出Excel
     *
     * 用法(由verify内部调用，也可单独使用):
     *   php SpPausedProductController.php method=retry file="关停失败_product_xxx.xlsx" channel=amazon_us
     *
     * @param array $failedList 关停失败数据列表，每项包含 channel/seller_id/ad_id
     * @param string $channelLabel channel标签，用于日志和导出文件名
     * @param array $adIdChannelMap sellerId_adId => channel 映射，用于获取channel（从Excel读取时传入）
     */
    public function retryPausedProducts($failedList = [], $channelLabel = '全部', $adIdChannelMap = []){
        if (count($failedList) <= 0) {
            $this->log("retryPausedProducts 无需重试");
            return;
        }

        $this->log("========== 开始重新关停失败数据 ==========");
        $curlService = (new CurlService())->pro();
        $redisService = new RedisService();
        $spApi = new SpApi();

        // 按seller_id分组
        $retrySellerAdIds = [];
        foreach ($failedList as $item) {
            $retrySellerAdIds[$item['seller_id']][] = $item['ad_id'];
        }

        $retrySuccessCount = 0;
        $retryFailedList = [];

        foreach ($retrySellerAdIds as $sellerId => $adIds) {
            $sellerChannel = $spApi->sellerConfig($sellerId);
            $sellerAdList = $redisService->hGetAll("spProduct_{$sellerId}");
            $this->log("重新关停 {$sellerId} 共 " . count($adIds) . " 个adId");

            // 补查redis中缺失的adId映射
            $lastIds = [];
            foreach ($adIds as $adId) {
                if (!isset($sellerAdList[$adId]) || !$sellerAdList[$adId]) {
                    $lastIds[] = $adId;
                }
            }
            foreach (array_chunk($lastIds, 200) as $chunk) {
                $list = DataUtils::getPageList($curlService->s3023()->get("amazon_sp_products/queryPage", [
                    "channel" => $spApi->specialSellerIdConver($sellerId),
                    "adId_in" => implode(',', $chunk),
                    "limit" => 200
                ]));
                if (count($list) > 0) {
                    foreach ($list as &$info) {
                        $seller = $spApi->specialSellerIdReverseConver($info['channel']);
                        $redisService->hSet("spProduct_{$seller}", $info['adId'], $info['_id']);
                        $sellerAdList[$info['adId']] = $info['_id'];
                    }
                }
            }

            // 构建关停请求参数
            $idWithAdId = [];
            foreach ($adIds as $adId) {
                $idWithAdId[] = [
                    "adId" => $adId,
                    "state" => "paused"
                ];
            }

            // 分批调用关停API
            foreach (array_chunk($idWithAdId, 200) as $chunk) {
                $pausedAdIdResult = $spApi->pausedProduct($sellerId, $chunk);
                if (isset($pausedAdIdResult['success']) && count($pausedAdIdResult['success']) > 0) {
                    $this->log("{$sellerId} 重新关停成功: " . count($pausedAdIdResult['success']) . "个");
                    $batchUpdateList = [];
                    foreach ($pausedAdIdResult['success'] as $adId) {
                        $retrySuccessCount++;
                        if (isset($sellerAdList[$adId]) && $sellerAdList[$adId]) {
                            $batchUpdateList[] = [
                                '_id' => $sellerAdList[$adId],
                                'adId' => $adId,
                                'state' => 'paused'
                            ];
                        }
                    }
                    if (!empty($batchUpdateList)) {
                        $spApi->batchMongoUpdateProduct($batchUpdateList);
                    }
                }
                if (isset($pausedAdIdResult['error']) && count($pausedAdIdResult['error']) > 0) {
                    $this->log("{$sellerId} 重新关停仍然失败: " . count($pausedAdIdResult['error']) . "个");
                    foreach ($pausedAdIdResult['error'] as $adId) {
                        $itemChannel = !empty($adIdChannelMap) ? ($adIdChannelMap[$sellerId . '_' . $adId] ?: ($sellerChannel ?: $channelLabel)) : ($sellerChannel ?: $channelLabel);
                        $retryFailedList[] = [
                            "channel" => $itemChannel,
                            "seller_id" => $sellerId,
                            "ad_id" => (string)$adId,
                            "message" => $pausedAdIdResult['errorMsg'][$adId] ?? "API关停失败",
                        ];
                    }
                }
            }
        }

        // 输出重新关停汇总
        $this->log("========== 重新关停汇总 ==========");
        $this->log("重新关停总数: " . count($failedList));
        $this->log("✅ 重新关停成功: {$retrySuccessCount}");
        $this->log("❌ 重新关停仍然失败: " . count($retryFailedList));

        // 导出仍然失败的数据
        if (count($retryFailedList) > 0) {
            $excelUtilsRetry = new ExcelUtils("sp/product/");
            $retryFilePath = $excelUtilsRetry->downloadXlsx([
                "channel",
                "seller_id",
                "ad_id",
                "message",
            ], $retryFailedList, "重新关停仍失败_product_{$channelLabel}_" . date("YmdHis") . ".xlsx", [2]);
            $this->log("重新关停仍失败数据已导出: {$retryFilePath}");
        }
    }


    /**
     * 读取混合channel的Excel文件，按channel参数过滤后关停product广告
     * Excel格式: channel | seller_id | ad_id
     * 用法: php SpPausedProductController.php method=v2 file="M4-M6 关停清单v2.xlsx" channel=amazon_us
     * @param string $file Excel文件名(在./excel/目录下)
     * @param string $channel 必填，按channel过滤数据，可选值: amazon_us, amazon_uk, amazon_ca等
     */
    public function pausedProductV2s($file = "",$channel = ""){
        $this->log("pausedProductV2s 开始处理 file:{$file} channel:" . ($channel ?: '全部'));
        $excelUtils = new ExcelUtils();
        $curlService = (new CurlService())->pro();
        $redisService = new RedisService();
        $spApi = new SpApi();
        $sellerIdAdId = [];
        $adIdChannelMap = [];
        $totalAdIdCount = 0;
        try {
            $excelUtils->eachXlsxRow(__DIR__."/excel/{$file}", function ($item) use (&$sellerIdAdId, &$adIdChannelMap, &$totalAdIdCount, $channel) {
                $adId = trim(sprintf('%.0f', (float)($item['ad_id'] ?? 0)), "'");
                $sellerId = trim($item['seller_id'] ?? '');
                $ch = trim($item['channel'] ?? '');
                if ($adId === '' || $adId === '0' || $sellerId === '') {
                    return;
                }
                if (!empty($channel) && $ch !== $channel) {
                    return;
                }
                $sellerIdAdId[$sellerId][] = $adId;
                $adIdChannelMap[$sellerId . '_' . $adId] = $ch;
                $totalAdIdCount++;
            });
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }

        $this->log("channel:" . ($channel ?: '全部') . " 共 " . count($sellerIdAdId) . " 个seller, {$totalAdIdCount} 个adId");

        if (count($sellerIdAdId) > 0) {
            $exportList = [];
            foreach ($sellerIdAdId as $sellerId => $adIds){
                $sellerChannel = $spApi->sellerConfig($sellerId);
                $sellerAdList = $redisService->hGetAll("spProduct_{$sellerId}");
                $this->log("{$sellerId} 数量: " . count($sellerAdList) . "个");

                $lastIds = [];
                $idWithAdId = [];
                foreach ($adIds as $adId){
                    if (!isset($sellerAdList[$adId]) || !$sellerAdList[$adId]){
                        $lastIds[] = $adId;
                    }
                    $idWithAdId[] = [
                        "adId" => $adId,
                        "state" => "paused"
                    ];
                }


                foreach (array_chunk($lastIds,200) as $chunk){
                    $list = DataUtils::getPageList($curlService->s3023()->get("amazon_sp_products/queryPage", [
                        "channel" => $spApi->specialSellerIdConver($sellerId),
                        "adId_in" => implode(',', $chunk),
                        "limit" => 200
                    ]));
                    if (count($list) > 0){
                        foreach ($list as &$info){
                            $seller = $spApi->specialSellerIdReverseConver($info['channel']);
                            $redisService->hSet("spProduct_{$seller}",$info['adId'],$info['_id']);
                            $sellerAdList[$info['adId']] = $info['_id'];
                        }
                    }
                }


                if (count($idWithAdId) > 0){
                    foreach (array_chunk($idWithAdId,200) as $chunk){
                        $this->log(json_encode($chunk, JSON_UNESCAPED_UNICODE));
                        $pausedAdIdResult = $spApi->pausedProduct($sellerId,$chunk);
                        if (isset($pausedAdIdResult['success']) && count($pausedAdIdResult['success']) > 0){
                            //成功的adId；批量更新mongo（30路并发，替代逐条串行调用）
                            $this->log("{$sellerId} 关停成功: " . count($pausedAdIdResult['success']) . "个");
                            $batchUpdateList = [];
                            foreach ($pausedAdIdResult['success'] as $adId){
                                if (isset($sellerAdList[$adId]) && $sellerAdList[$adId]){
                                    $batchUpdateList[] = [
                                        '_id' => $sellerAdList[$adId],
                                        'adId' => $adId,
                                        'state' => 'paused'
                                    ];
                                }
                            }
                            if (!empty($batchUpdateList)) {
                                $spApi->batchMongoUpdateProduct($batchUpdateList);
                            }
                        }
                        if (isset($pausedAdIdResult['error']) && count($pausedAdIdResult['error']) > 0){
                            //失败的adId
                            $this->log("{$sellerId} 关停失败: " . count($pausedAdIdResult['error']) . "个");
                            foreach ($pausedAdIdResult['error'] as $adId){
                                $itemChannel = $adIdChannelMap[$sellerId . '_' . $adId] ?: ($sellerChannel ?: ($channel ?: '全部'));
                                $exportList[] = [
                                    "channel" => $itemChannel,
                                    "seller_id" => $sellerId,
                                    "ad_id" => (string)$adId,
                                    "message" => $pausedAdIdResult['errorMsg'][$adId] ?? "API操作失败",
                                ];
                            }
                        }


                    }
                }
            }

            if (count($exportList) > 0){
                $excelUtils = new ExcelUtils("sp/product/");
                $filePath = $excelUtils->downloadXlsx([
                    "channel",
                    "seller_id",
                    "ad_id",
                    "message",
                ], $exportList, "关停失败_product_" . ($channel ?: 'all') . "_" . date("YmdHis") . ".xlsx", [2]);
            }

            $this->log("pausedProductV2s channel:" . ($channel ?: '全部') . " 处理完毕");
        } else {
            $this->log("pausedProductV2s channel:" . ($channel ?: '全部') . " 无数据");
        }
    }


}

$parameters = DataUtils::ExplainArgv(@$argv, array());
$params = (count(@$argv) > 1) ? $parameters : $_REQUEST;
$channel = "";
$page = 0;
$file = "";
$method = "";
if (isset($params['channel']) && trim($params['channel']) != '') {
    $channel = $params['channel'];
}
if (isset($params['page']) && trim($params['page']) != '') {
    $page = $params['page'];
}
if (isset($params['file']) && trim($params['file']) != '') {
    $file = $params['file'];
}
if (isset($params['method']) && trim($params['method']) != '') {
    $method = $params['method'];
}
$con = new SpPausedProductController();
if ($method == 'verify') {
    $con->verifyPausedProducts($file, $channel);
} elseif ($method == 'retry') {
    // 从导出的关停失败Excel读取数据，重新执行关停
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
            $adId = trim(sprintf('%.0f', (float)($item['ad_id'] ?? 0)), "'");
            $sellerId = trim($item['seller_id'] ?? '');
            $ch = trim($item['channel'] ?? '');
            if ($adId === '' || $adId === '0' || $sellerId === '') {
                return;
            }
            if (!empty($channel) && $ch !== $channel) {
                return;
            }
            $failedList[] = [
                "channel" => $ch,
                "seller_id" => $sellerId,
                "ad_id" => $adId,
            ];
            $adIdChannelMap[$sellerId . '_' . $adId] = $ch;
        });
    } catch (Exception $e) {
        die($e->getLine() . " : " . $e->getMessage());
    }
    $con->retryPausedProducts($failedList, $channelLabel, $adIdChannelMap);
} else {
    $con->pausedProductV2s($file, $channel);
}
