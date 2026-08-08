<?php
require_once(dirname(__FILE__) . "/../../../../php/requiredfile/requiredfile.php");
require_once(dirname(__FILE__) . "/../../../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/RequestUtils.php");
require_once(dirname(__FILE__) . "/../SpApi.php");

class SpPausedNegativeKeywordController
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

    public function dingTalk(){
        $proCurlService = new CurlService();
        $ali = $proCurlService->test()->phpali();

        $datetime = date("Y-m-d H:i:s",time());
        $postData = array(
            'userType' => 'userName',
            'userIdList' => "zhouangang",
            'title' => "【否定词广告写入暂停完毕】提醒",
            'msg' => [
                [
                    "key" => "",
                    "value" => "{$datetime} 否定词广告写入暂停完毕"
                ]
            ]
        );
        $ali->post("dingding/sendOaNotice",$postData);
        return $this;
    }

    /**
     * 暂停否定关键词（paused）
     * Excel格式: channel | keywordid（兼容keyword_id/keywordId列名）
     * 用法: php SpPausedNegativeKeywordController.php method=paused file="否定keyword记录.xlsx" channel=amazon_us
     *       php SpPausedNegativeKeywordController.php file="否定keyword记录.xlsx" (默认方法，处理全部channel)
     * @param string $file Excel文件名(在./excel/目录下，不传则回退服务器原路径)
     * @param string $channel 可选，按channel过滤数据，不传则处理全部
     */
    public function pausedNegativeKeywords($file = "", $channel = ""){
        $channelLabel = empty($channel) ? '全部' : $channel;
        $this->log("pausedNegativeKeywords 开始处理 file:{$file} channel:{$channelLabel}");
        $excelUtils = new ExcelUtils();
        $curlService = (new CurlService())->pro();
        $redisService = new RedisService();
        $spApi = new SpApi();
        $filePath = $this->resolveNegativeKeywordExcelFile($file);
        $sellerIdKeywordIds = [];
        $keywordIdChannelMap = [];
        $totalCount = 0;
        try {
            $excelUtils->eachXlsxRow($filePath, function ($item) use (&$sellerIdKeywordIds, &$keywordIdChannelMap, &$totalCount, $channel, $spApi) {
                $keywordId = trim(sprintf('%.0f', (float)$this->findKeywordId($item)), "'");
                $ch = trim($item['channel'] ?? '');
                if ($keywordId === '' || $keywordId === '0' || $ch === '') {
                    return;
                }
                if (!empty($channel) && $ch !== $channel) {
                    return;
                }
                $sellerId = $spApi->specialSellerIdReverseConver($ch);
                $sellerIdKeywordIds[$sellerId][] = $keywordId;
                $keywordIdChannelMap[$sellerId . '_' . $keywordId] = $ch;
                $totalCount++;
            });
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }

        $this->log("channel:{$channelLabel} 共 " . count($sellerIdKeywordIds) . " 个seller, {$totalCount} 个keywordId");

        if (count($sellerIdKeywordIds) > 0) {
            $exportList = [];
            foreach ($sellerIdKeywordIds as $sellerId => $adIds){
                $sellerChannel = $spApi->sellerConfig($sellerId);
                $sellerAdList = $redisService->hGetAll("spNegativeKeyword_{$sellerId}");
                $this->log("{$sellerId} 数量: " . count($sellerAdList) . "个");

                $lastIds = [];
                $idWithAdId = [];
                foreach ($adIds as $adId){
                    if (!isset($sellerAdList[$adId]) || !$sellerAdList[$adId]){
                        $lastIds[] = $adId;
                    }
                    $idWithAdId[] = [
                        "keywordId" => $adId,
                        "state" => "paused"
                    ];
                }


                foreach (array_chunk($lastIds,200) as $chunk){
                    $list = DataUtils::getPageList($curlService->s3023()->get("amazon_sp_negativeKeywords/queryPage", [
                        "channel" => $spApi->specialSellerIdConver($sellerId),
                        "keywordId_in" => implode(',', $chunk),
                        "limit" => 200
                    ]));
                    if (count($list) > 0){
                        foreach ($list as &$info){
                            $seller = $spApi->specialSellerIdReverseConver($info['channel']);
                            $redisService->hSet("spNegativeKeyword_{$seller}",$info['keywordId'],$info['_id']);
                            $sellerAdList[$info['keywordId']] = $info['_id'];
                        }
                    }
                }


                if (count($idWithAdId) > 0){
                    foreach (array_chunk($idWithAdId,200) as $chunk){
                        $this->log(json_encode($chunk, JSON_UNESCAPED_UNICODE));
                        $pausedAdIdResult = $spApi->updateNegativeKeyword($sellerId,$chunk);
                        if (isset($pausedAdIdResult['success']) && count($pausedAdIdResult['success']) > 0){
                            //成功的adId；
                            $this->log("{$sellerId} 关停成功: " . count($pausedAdIdResult['success']) . "个");
                            foreach ($pausedAdIdResult['success'] as $keywordId){
                                if (isset($sellerAdList[$keywordId]) && $sellerAdList[$keywordId]){
                                    $_id = $sellerAdList[$keywordId];
                                    $spApi->mongoUpdateNegativeKeyword($_id, $keywordId, "paused");
                                }
                            }
                        }
                        if (isset($pausedAdIdResult['error']) && count($pausedAdIdResult['error']) > 0){
                            //失败的adId
                            $this->log("{$sellerId} 关停失败: " . count($pausedAdIdResult['error']) . "个");
                            foreach ($pausedAdIdResult['error'] as $keywordId){
                                $itemChannel = $keywordIdChannelMap[$sellerId . '_' . $keywordId] ?: ($sellerChannel ?: $channelLabel);
                                $exportList[] = [
                                    "channel" => $itemChannel,
                                    "seller_id" => $sellerId,
                                    "keyword_id" => (string)$keywordId,
                                ];
                            }
                        }


                    }
                }
            }

            if (count($exportList) > 0){
                $excelUtils = new ExcelUtils("sp/negativeKeyword/");
                $filePath = $excelUtils->downloadXlsx([
                    "channel",
                    "seller_id",
                    "keyword_id",
                ], $exportList, "关停失败_negativeKeyword_{$channelLabel}_" . date("YmdHis") . ".xlsx", [2]);
                $this->log("关停失败数据已导出: {$filePath}");
            }
        }
        $this->dingTalk();
    }

    /**
     * 校验negativeKeyword状态是否正确修改为paused
     * 通过Amazon API查询negativeKeyword的实际状态，与期望状态对比
     * 只导出关停失败（状态不等于paused）的数据，格式与关停输入一致，可直接重新执行关停
     * archived状态不统计，not_found只记录日志不导出
     * 用法: php SpPausedNegativeKeywordController.php method=verify file="否定keyword记录.xlsx" channel=amazon_us
     * @param string $file Excel文件名(在./excel/目录下)
     * @param string $channel 可选，按channel过滤数据，不传则校验全部
     */
    public function verifyPausedNegativeKeywordStates($file = "", $channel = ""){
        $channelLabel = empty($channel) ? '全部' : $channel;
        $this->log("verifyPausedNegativeKeywordStates 开始校验 file:{$file} channel:{$channelLabel}");
        $excelUtils = new ExcelUtils();
        $spApi = new SpApi();
        $curlService = (new CurlService())->pro();
        $filePath = $this->resolveNegativeKeywordExcelFile($file);

        $sellerIdKeywordIds = [];
        $keywordIdChannelMap = [];
        $totalCount = 0;
        try {
            $excelUtils->eachXlsxRow($filePath, function ($item) use (&$sellerIdKeywordIds, &$keywordIdChannelMap, &$totalCount, $channel, $spApi) {
                $keywordId = trim(sprintf('%.0f', (float)$this->findKeywordId($item)), "'");
                $ch = trim($item['channel'] ?? '');
                if ($keywordId === '' || $keywordId === '0' || $ch === '') {
                    return;
                }
                if (!empty($channel) && $ch !== $channel) {
                    return;
                }
                $sellerId = $spApi->specialSellerIdReverseConver($ch);
                $sellerIdKeywordIds[$sellerId][] = $keywordId;
                $keywordIdChannelMap[$sellerId . '_' . $keywordId] = $ch;
                $totalCount++;
            });
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }

        $this->log("channel:{$channelLabel} 共 " . count($sellerIdKeywordIds) . " 个seller, {$totalCount} 个keywordId");

        if (count($sellerIdKeywordIds) <= 0) {
            $this->log("verifyPausedNegativeKeywordStates channel:{$channelLabel} 无数据");
            return;
        }

        $exportList = [];
        $verifiedCount = 0;
        $archivedCount = 0;
        $pausedCount = 0;
        $notPausedCount = 0;
        $notFoundCount = 0;

        foreach ($sellerIdKeywordIds as $sellerId => $keywordIds){
            $sellerChannel = $spApi->sellerConfig($sellerId);
            $this->log("{$sellerId} 开始校验 " . count($keywordIds) . " 个negativeKeyword");

            // 分批查询Amazon API，每批最多100个keywordId（Amazon API限制）
            foreach (array_chunk($keywordIds, 100) as $chunk){
                $keywordIdsStr = implode(",", $chunk);
                $this->log("查询Amazon API: {$sellerId} keywordIds: {$keywordIdsStr}");

                $keywordListInfo = $this->listNegativeKeywordV2($curlService, $sellerId, $keywordIdsStr);

                foreach ($chunk as $keywordId){
                    if (isset($keywordListInfo[$keywordId])){
                        $actualState = $keywordListInfo[$keywordId]['state'];
                        // archived状态不统计，直接跳过
                        if ($actualState === 'archived') {
                            $archivedCount++;
                            $this->log("⏭️ {$sellerId} keywordId:{$keywordId} archived状态，跳过");
                            continue;
                        }
                        $verifiedCount++;
                        if ($actualState === "paused"){
                            $pausedCount++;
                        } else {
                            $notPausedCount++;
                            $this->log("❌ {$sellerId} keywordId:{$keywordId} 关停失败: 期望paused, 实际{$actualState}");
                            $itemChannel = $keywordIdChannelMap[$sellerId . '_' . $keywordId] ?: ($sellerChannel ?: $channelLabel);
                            $exportList[] = [
                                "channel" => $itemChannel,
                                "seller_id" => $sellerId,
                                "keyword_id" => (string)$keywordId,
                            ];
                        }
                    } else {
                        $notFoundCount++;
                        $this->log("⚠️ {$sellerId} keywordId:{$keywordId} Amazon API未返回该negativeKeyword数据");
                    }
                }
            }
        }

        // 输出校验汇总
        $this->log("========== 校验汇总 ==========");
        $this->log("总数据: {$totalCount}");
        $this->log("⏭️ archived跳过: {$archivedCount}");
        $this->log("⚠️ 未找到(not_found): {$notFoundCount}");
        $this->log("已校验: {$verifiedCount}");
        $this->log("✅ 已暂停(paused): {$pausedCount}");
        $this->log("❌ 关停失败(非paused): {$notPausedCount}");

        // 导出关停失败的数据，格式与关停输入一致，可直接重新执行关停
        if (count($exportList) > 0){
            $excelUtilsExport = new ExcelUtils("sp/negativeKeyword/");
            $filePath = $excelUtilsExport->downloadXlsx([
                "channel",
                "seller_id",
                "keyword_id",
            ], $exportList, "关停失败_negativeKeyword_{$channelLabel}_" . date("YmdHis") . ".xlsx", [2]);
            $this->log("关停失败数据已导出: {$filePath}");
        } else {
            $this->log("所有negativeKeyword状态校验通过，无关停失败数据");
        }

        // 对关停失败的数据重新执行关停
        if (count($exportList) > 0) {
            $this->retryPausedNegativeKeyword($exportList, $channelLabel, $keywordIdChannelMap);
        }

        $this->log("verifyPausedNegativeKeywordStates channel:{$channelLabel} 校验完毕");
    }


    /**
     * 对关停失败的negativeKeyword数据重新执行关停
     * 输入数据格式: [{"channel"=>"xxx", "seller_id"=>"xxx", "keyword_id"=>"xxx"}, ...]
     * 关停成功则更新mongo，仍然失败的导出Excel
     *
     * 用法(由verify内部调用，也可单独使用):
     *   php SpPausedNegativeKeywordController.php method=retry file="关停失败_negativeKeyword_xxx.xlsx" channel=amazon_us
     *
     * @param array $failedList 关停失败数据列表，每项包含 channel/seller_id/keyword_id
     * @param string $channelLabel channel标签，用于日志和导出文件名
     * @param array $adIdChannelMap sellerId_keywordId => channel 映射，用于获取channel（从Excel读取时传入）
     */
    public function retryPausedNegativeKeyword($failedList = [], $channelLabel = '全部', $adIdChannelMap = []){
        if (count($failedList) <= 0) {
            $this->log("retryPausedNegativeKeyword 无需重试");
            return;
        }

        $this->log("========== 开始重新关停失败数据 ==========");
        $curlService = (new CurlService())->pro();
        $redisService = new RedisService();
        $spApi = new SpApi();

        // 按seller_id分组
        $retrySellerKeywordIds = [];
        foreach ($failedList as $item) {
            $retrySellerKeywordIds[$item['seller_id']][] = $item['keyword_id'];
        }

        $retrySuccessCount = 0;
        $retryFailedList = [];

        foreach ($retrySellerKeywordIds as $sellerId => $keywordIds) {
            $sellerChannel = $spApi->sellerConfig($sellerId);
            $sellerAdList = $redisService->hGetAll("spNegativeKeyword_{$sellerId}");
            $this->log("重新关停 {$sellerId} 共 " . count($keywordIds) . " 个negativeKeyword");

            // 补查redis中缺失的keywordId映射
            $lastIds = [];
            foreach ($keywordIds as $keywordId) {
                if (!isset($sellerAdList[$keywordId]) || !$sellerAdList[$keywordId]) {
                    $lastIds[] = $keywordId;
                }
            }
            foreach (array_chunk($lastIds, 200) as $chunk) {
                $list = DataUtils::getPageList($curlService->s3023()->get("amazon_sp_negativeKeywords/queryPage", [
                    "channel" => $spApi->specialSellerIdConver($sellerId),
                    "keywordId_in" => implode(',', $chunk),
                    "limit" => 200
                ]));
                if (count($list) > 0) {
                    foreach ($list as &$info) {
                        $seller = $spApi->specialSellerIdReverseConver($info['channel']);
                        $redisService->hSet("spNegativeKeyword_{$seller}", $info['keywordId'], $info['_id']);
                        $sellerAdList[$info['keywordId']] = $info['_id'];
                    }
                }
            }

            // 构建关停请求参数
            $idWithAdId = [];
            foreach ($keywordIds as $keywordId) {
                $idWithAdId[] = [
                    "keywordId" => $keywordId,
                    "state" => "paused"
                ];
            }

            // 分批调用关停API
            foreach (array_chunk($idWithAdId, 200) as $chunk) {
                $updateResult = $spApi->updateNegativeKeyword($sellerId, $chunk);
                if (isset($updateResult['success']) && count($updateResult['success']) > 0) {
                    $this->log("{$sellerId} 重新关停成功: " . count($updateResult['success']) . "个");
                    foreach ($updateResult['success'] as $keywordId) {
                        $retrySuccessCount++;
                        if (isset($sellerAdList[$keywordId]) && $sellerAdList[$keywordId]) {
                            $_id = $sellerAdList[$keywordId];
                            $spApi->mongoUpdateNegativeKeyword($_id, $keywordId, "paused");
                        }
                    }
                }
                if (isset($updateResult['error']) && count($updateResult['error']) > 0) {
                    $this->log("{$sellerId} 重新关停仍然失败: " . count($updateResult['error']) . "个");
                    foreach ($updateResult['error'] as $keywordId) {
                        $itemChannel = !empty($adIdChannelMap) ? ($adIdChannelMap[$sellerId . '_' . $keywordId] ?: ($sellerChannel ?: $channelLabel)) : ($sellerChannel ?: $channelLabel);
                        $retryFailedList[] = [
                            "channel" => $itemChannel,
                            "seller_id" => $sellerId,
                            "keyword_id" => (string)$keywordId,
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
            $excelUtilsRetry = new ExcelUtils("sp/negativeKeyword/");
            $retryFilePath = $excelUtilsRetry->downloadXlsx([
                "channel",
                "seller_id",
                "keyword_id",
            ], $retryFailedList, "重新操作仍失败_negativeKeyword_{$channelLabel}_" . date("YmdHis") . ".xlsx", [2]);
            $this->log("重新操作仍失败数据已导出: {$retryFilePath}");
        }
    }


    /**
     * 兼容多列名的keywordId读取（keywordid/keyword_id/keywordId）
     */
    private function findKeywordId($item)
    {
        foreach (["keywordid", "keyword_id", "keywordId"] as $field) {
            if (isset($item[$field]) && trim((string)$item[$field]) !== "") {
                return $item[$field];
            }
        }
        return 0;
    }

    /**
     * 解析Excel文件路径，file为空时回退到服务器原路径
     */
    private function resolveNegativeKeywordExcelFile($file = "")
    {
        if ($file !== "") {
            if (is_file($file)) {
                return $file;
            }
            $relativeFile = __DIR__ . "/excel/" . ltrim($file, "/");
            if (is_file($relativeFile)) {
                return $relativeFile;
            }
            $exportFile = __DIR__ . "/export/" . ltrim($file, "/");
            if (is_file($exportFile)) {
                return $exportFile;
            }
        }
        return "/xp/www/ShallowDream-LeetCode-PHP/pa-temp-fix-system.com/php/export/sp/negativeKeyword/否定keyword记录.xlsx";
    }

    /**
     * 通过keywordId批量查询negativeKeyword的实际状态
     * @param CurlService $curlService
     * @param string $sellerId
     * @param string $keywordIds 逗号分隔的keywordId
     * @return array [keywordId => ['state' => 'paused'|'enabled'|'archived']]
     */
    private function listNegativeKeywordV2($curlService, $sellerId, $keywordIds)
    {
        $condition = [
            "keywordIdFilter" => $keywordIds,
        ];
        $resp = DataUtils::getResultData($curlService->phphk()->get("amazon/ad/negativeKeywords/getNegativeKeywordsExtend/{$sellerId}", $condition));
        $keywordListInfo = [];
        if ($resp && isset($resp['data']) && count($resp['data']) > 0) {
            foreach ($resp['data'] as $item) {
                $keywordListInfo[$item['keywordId']] = [
                    'state' => $item['state'],
                ];
            }
        }
        return $keywordListInfo;
    }


}

$parameters = DataUtils::ExplainArgv(@$argv, array());
$params = (count(@$argv) > 1) ? $parameters : $_REQUEST;
$channel = "";
$file = "";
$method = "";
if (isset($params['channel']) && trim($params['channel']) != '') {
    $channel = $params['channel'];
}
if (isset($params['file']) && trim($params['file']) != '') {
    $file = $params['file'];
}
if (isset($params['method']) && trim($params['method']) != '') {
    $method = $params['method'];
}
$con = new SpPausedNegativeKeywordController();
if ($method == 'verify') {
    $con->verifyPausedNegativeKeywordStates($file, $channel);
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
            $keywordId = "";
            foreach (["keywordid", "keyword_id", "keywordId"] as $field) {
                if (isset($item[$field]) && trim((string)$item[$field]) !== "") {
                    $keywordId = trim(sprintf('%.0f', (float)$item[$field]), "'");
                    break;
                }
            }
            $sellerId = trim($item['seller_id'] ?? '');
            $ch = trim($item['channel'] ?? '');
            if ($keywordId === '' || $keywordId === '0' || $sellerId === '') {
                return;
            }
            if (!empty($channel) && $ch !== $channel) {
                return;
            }
            $failedList[] = [
                "channel" => $ch,
                "seller_id" => $sellerId,
                "keyword_id" => $keywordId,
            ];
            $adIdChannelMap[$sellerId . '_' . $keywordId] = $ch;
        });
    } catch (Exception $e) {
        die($e->getLine() . " : " . $e->getMessage());
    }
    $con->retryPausedNegativeKeyword($failedList, $channelLabel, $adIdChannelMap);
} else {
    $con->pausedNegativeKeywords($file, $channel);
}