<?php
require_once(dirname(__FILE__) . "/../../../../php/requiredfile/requiredfile.php");
require_once(dirname(__FILE__) . "/../../../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/RequestUtils.php");
require_once(dirname(__FILE__) . "/../SpApi.php");

class SpPausedNegativeTargetController
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

    public function dingTalk(){
        $proCurlService = new CurlService();
        $ali = $proCurlService->test()->phpali();

        $datetime = date("Y-m-d H:i:s",time());
        $postData = array(
            'userType' => 'userName',
            'userIdList' => "zhouangang",
            'title' => "【否定target广告写入暂停完毕】提醒",
            'msg' => [
                [
                    "key" => "",
                    "value" => "{$datetime} 否定target广告写入暂停完毕"
                ]
            ]
        );
        $ali->post("dingding/sendOaNotice",$postData);
        return $this;
    }

    /**
     * 暂停否定target广告
     * Excel格式: channel | seller_id | target_id
     * 用法: php SpPausedNegativeTargetController.php file="否定target记录.xlsx" channel=amazon_us
     *       php SpPausedNegativeTargetController.php file="否定target记录.xlsx"  (处理全部channel)
     * @param string $file Excel文件名(在./excel/目录下)
     * @param string $channel 可选，按channel过滤数据，不传则处理全部
     */
    public function pausedNegativeTarget($file = "", $channel = ""){
        $channelLabel = empty($channel) ? '全部' : $channel;
        $this->log("pausedNegativeTarget 开始处理 file:{$file} channel:{$channelLabel}");
        $excelUtils = new ExcelUtils();
        $curlService = (new CurlService())->pro();
        $redisService = new RedisService();
        $spApi = new SpApi();
        $sellerIdAdId = [];
        $targetIdChannelMap = [];
        $totalTargetIdCount = 0;
        try {
            $excelUtils->eachXlsxRow(__DIR__."/excel/{$file}", function ($item) use (&$sellerIdAdId, &$targetIdChannelMap, &$totalTargetIdCount, $channel) {
                $targetId = trim(sprintf('%.0f', (float)($item['target_id'] ?? $item['targetid'] ?? 0)), "'");
                $sellerId = trim($item['seller_id'] ?? '');
                $ch = trim($item['channel'] ?? '');
                if ($targetId === '' || $targetId === '0' || $sellerId === '') {
                    return;
                }
                if (!empty($channel) && $ch !== $channel) {
                    return;
                }
                $sellerIdAdId[$sellerId][] = $targetId;
                $targetIdChannelMap[$sellerId . '_' . $targetId] = $ch;
                $totalTargetIdCount++;
            });
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }

        $this->log("channel:{$channelLabel} 共 " . count($sellerIdAdId) . " 个seller, {$totalTargetIdCount} 个targetId");

        if (count($sellerIdAdId) > 0) {
            $exportList = [];
            foreach ($sellerIdAdId as $sellerId => $adIds){
                $sellerChannel = $spApi->sellerConfig($sellerId);
                $sellerAdList = $redisService->hGetAll("spNegativeTarget_{$sellerId}");
                $this->log("{$sellerId} 数量: " . count($sellerAdList) . "个");

                $lastIds = [];
                $idWithAdId = [];
                foreach ($adIds as $adId){
                    if (!isset($sellerAdList[$adId]) || !$sellerAdList[$adId]){
                        $lastIds[] = $adId;
                    }
                    $idWithAdId[] = [
                        "targetId" => $adId,
                        "state" => "paused"
                    ];
                }


                foreach (array_chunk($lastIds,200) as $chunk){
                    $list = DataUtils::getPageList($curlService->s3023()->get("amazon_sp_negative_targets/queryPage", [
                        "channel" => $spApi->specialSellerIdConver($sellerId),
                        "targetId_in" => implode(',', $chunk),
                        "limit" => 200
                    ]));
                    if (count($list) > 0){
                        foreach ($list as &$info){
                            $seller = $spApi->specialSellerIdReverseConver($info['channel']);
                            $redisService->hSet("spNegativeTarget_{$seller}",$info['targetId'],$info['_id']);
                            $sellerAdList[$info['targetId']] = $info['_id'];
                        }
                    }
                }


                if (count($idWithAdId) > 0){
                    foreach (array_chunk($idWithAdId,200) as $chunk){
                        $this->log(json_encode($chunk, JSON_UNESCAPED_UNICODE));
                        $pausedAdIdResult = $spApi->updateNegativeTarget($sellerId,$chunk);
                        if (isset($pausedAdIdResult['success']) && count($pausedAdIdResult['success']) > 0){
                            //成功的targetId
                            $this->log("{$sellerId} 关停成功: " . count($pausedAdIdResult['success']) . "个");
                            foreach ($pausedAdIdResult['success'] as $targetId){
                                if (isset($sellerAdList[$targetId]) && $sellerAdList[$targetId]){
                                    $_id = $sellerAdList[$targetId];
                                    $spApi->mongoUpdateNegativeTarget($_id, $targetId, "paused");
                                }
                            }
                        }
                        if (isset($pausedAdIdResult['error']) && count($pausedAdIdResult['error']) > 0){
                            //失败的targetId
                            $this->log("{$sellerId} 关停失败: " . count($pausedAdIdResult['error']) . "个");
                            foreach ($pausedAdIdResult['error'] as $targetId){
                                $itemChannel = $targetIdChannelMap[$sellerId . '_' . $targetId] ?: ($sellerChannel ?: ($channel ?: '全部'));
                                $exportList[] = [
                                    "channel" => $itemChannel,
                                    "seller_id" => $sellerId,
                                    "target_id" => (string)$targetId,
                                ];
                            }
                        }


                    }
                }
            }

            if (count($exportList) > 0){
                $excelUtils = new ExcelUtils("sp/negativeTarget/");
                $filePath = $excelUtils->downloadXlsx([
                    "channel",
                    "seller_id",
                    "target_id",
                ], $exportList, "关停失败_negative_target_{$channelLabel}_" . date("YmdHis") . ".xlsx", [2]);
                $this->log("关停失败数据已导出: {$filePath}");
            } else {
                $this->log("所有negativeTarget关停成功，无失败数据");
            }

            $this->log("pausedNegativeTarget channel:{$channelLabel} 处理完毕");
        } else {
            $this->log("pausedNegativeTarget channel:{$channelLabel} 无数据");
        }
        $this->dingTalk();
    }


    /**
     * 校验否定target广告状态是否正确修改为paused
     * 通过Amazon API查询否定target的实际状态，与期望状态对比
     * 只导出关停失败（状态不等于paused）的数据，格式与关停输入一致，可直接重新执行关停
     * 用法: php SpPausedNegativeTargetController.php method=verify file="否定target记录.xlsx" channel=amazon_us
     * @param string $file Excel文件名(在./excel/目录下)
     * @param string $channel 必填，按channel过滤数据，可选值: amazon_us, amazon_uk, amazon_ca等
     */
    public function verifyPausedNegativeTargetStates($file = "", $channel = ""){
        $channelLabel = empty($channel) ? '全部' : $channel;
        $this->log("verifyPausedNegativeTargetStates 开始校验 file:{$file} channel:{$channelLabel}");
        $excelUtils = new ExcelUtils();
        $spApi = new SpApi();

        // 读取Excel，按seller_id分组收集target_id，同时记录每条数据的channel
        $sellerIdTargetIds = [];
        $targetIdChannelMap = [];
        $totalTargetIdCount = 0;
        try {
            $excelUtils->eachXlsxRow(__DIR__."/excel/{$file}", function ($item) use (&$sellerIdTargetIds, &$targetIdChannelMap, &$totalTargetIdCount, $channel) {
                $targetId = trim(sprintf('%.0f', (float)($item['target_id'] ?? $item['targetid'] ?? 0)), "'");
                $sellerId = trim($item['seller_id'] ?? '');
                $ch = trim($item['channel'] ?? '');
                if ($targetId === '' || $targetId === '0' || $sellerId === '') {
                    return;
                }
                if (!empty($channel) && $ch !== $channel) {
                    return;
                }
                $sellerIdTargetIds[$sellerId][] = $targetId;
                $targetIdChannelMap[$sellerId . '_' . $targetId] = $ch;
                $totalTargetIdCount++;
            });
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }

        $this->log("channel:{$channelLabel} 共 " . count($sellerIdTargetIds) . " 个seller, {$totalTargetIdCount} 个targetId");

        if (count($sellerIdTargetIds) <= 0) {
            $this->log("verifyPausedNegativeTargetStates channel:{$channelLabel} 无数据");
            return;
        }

        $exportList = [];
        $verifiedCount = 0;
        $archivedCount = 0;
        $pausedCount = 0;
        $notPausedCount = 0;
        $notFoundCount = 0;

        foreach ($sellerIdTargetIds as $sellerId => $targetIds){
            $sellerChannel = $spApi->sellerConfig($sellerId);
            $this->log("{$sellerId} 开始校验 " . count($targetIds) . " 个targetId");

            // 分批查询Amazon API，每批最多100个targetId（Amazon API限制）
            foreach (array_chunk($targetIds, 100) as $chunk){
                $targetIdsStr = implode(",", $chunk);
                $this->log("查询Amazon API: {$sellerId} targetIds: {$targetIdsStr}");

                $targetListInfo = $spApi->listNegativeTarget($sellerId, [], [], $targetIdsStr);

                $targetStateMap = [];
                foreach ($targetListInfo as $info) {
                    if (isset($info['targetId'])) {
                        $targetStateMap[$info['targetId']] = $info['state'] ?? '';
                    }
                }

                foreach ($chunk as $targetId){
                    if (isset($targetStateMap[$targetId])){
                        $actualState = $targetStateMap[$targetId];
                        // archived状态不统计，直接跳过
                        if ($actualState === 'archived') {
                            $archivedCount++;
                            $this->log("⏭️ {$sellerId} targetId:{$targetId} archived状态，跳过");
                            continue;
                        }
                        $verifiedCount++;
                        if ($actualState === "paused"){
                            $pausedCount++;
                        } else {
                            $notPausedCount++;
                            $this->log("❌ {$sellerId} targetId:{$targetId} 关停失败: 期望paused, 实际{$actualState}");
                            $itemChannel = $targetIdChannelMap[$sellerId . '_' . $targetId] ?: ($sellerChannel ?: $channelLabel);
                            $exportList[] = [
                                "channel" => $itemChannel,
                                "seller_id" => $sellerId,
                                "target_id" => (string)$targetId,
                                "actual_state" => $actualState,
                                "expected_state" => "paused",
                            ];
                        }
                    } else {
                        $notFoundCount++;
                        $this->log("⚠️ {$sellerId} targetId:{$targetId} Amazon API未返回该targetId数据");
                    }
                }
            }
        }

        // 输出校验汇总
        $this->log("========== 校验汇总 ==========");
        $this->log("总数据: {$totalTargetIdCount}");
        $this->log("⏭️ archived跳过: {$archivedCount}");
        $this->log("⚠️ 未找到(not_found): {$notFoundCount}");
        $this->log("已校验: {$verifiedCount}");
        $this->log("✅ 已暂停(paused): {$pausedCount}");
        $this->log("❌ 关停失败(非paused): {$notPausedCount}");

        // 导出关停失败的数据，格式与关停输入一致，可直接重新执行关停
        if (count($exportList) > 0){
            $excelUtilsExport = new ExcelUtils("sp/negativeTarget/");
            $filePath = $excelUtilsExport->downloadXlsx([
                "channel",
                "seller_id",
                "target_id",
                "actual_state",
                "expected_state",
            ], $exportList, "关停失败_negative_target_{$channelLabel}_" . date("YmdHis") . ".xlsx", [2]);
            $this->log("关停失败数据已导出: {$filePath}");
        } else {
            $this->log("所有negativeTarget状态校验通过，无关停失败数据");
        }

        // 对关停失败的数据重新执行关停
        if (count($exportList) > 0) {
            $this->retryPausedNegativeTarget($exportList, $channelLabel, $targetIdChannelMap);
        }

        $this->log("verifyPausedNegativeTargetStates channel:{$channelLabel} 校验完毕");
    }


    /**
     * 对关停失败的negativeTarget数据重新执行关停
     * 输入数据格式: [{"channel"=>"xxx", "seller_id"=>"xxx", "target_id"=>"xxx"}, ...]
     * 关停成功则更新mongo，仍然失败的导出Excel
     *
     * 用法(由verify内部调用，也可单独使用):
     *   php SpPausedNegativeTargetController.php method=retry file="关停失败_negative_target_xxx.xlsx" channel=amazon_us
     *
     * @param array $failedList 关停失败数据列表，每项包含 channel/seller_id/target_id
     * @param string $channelLabel channel标签，用于日志和导出文件名
     * @param array $targetIdChannelMap sellerId_targetId => channel 映射，用于获取channel（从Excel读取时传入）
     */
    public function retryPausedNegativeTarget($failedList = [], $channelLabel = '全部', $targetIdChannelMap = []){
        if (count($failedList) <= 0) {
            $this->log("retryPausedNegativeTarget 无需重试");
            return;
        }

        $this->log("========== 开始重新关停失败数据 ==========");
        $curlService = (new CurlService())->pro();
        $redisService = new RedisService();
        $spApi = new SpApi();

        // 按seller_id分组
        $retrySellerTargetIds = [];
        foreach ($failedList as $item) {
            $retrySellerTargetIds[$item['seller_id']][] = $item['target_id'];
        }

        $retrySuccessCount = 0;
        $retryFailedList = [];

        foreach ($retrySellerTargetIds as $sellerId => $targetIds) {
            $sellerChannel = $spApi->sellerConfig($sellerId);
            $sellerAdList = $redisService->hGetAll("spNegativeTarget_{$sellerId}");
            $this->log("重新关停 {$sellerId} 共 " . count($targetIds) . " 个targetId");

            // 补查redis中缺失的targetId映射
            $lastIds = [];
            foreach ($targetIds as $targetId) {
                if (!isset($sellerAdList[$targetId]) || !$sellerAdList[$targetId]) {
                    $lastIds[] = $targetId;
                }
            }
            foreach (array_chunk($lastIds, 200) as $chunk) {
                $list = DataUtils::getPageList($curlService->s3023()->get("amazon_sp_negative_targets/queryPage", [
                    "channel" => $spApi->specialSellerIdConver($sellerId),
                    "targetId_in" => implode(',', $chunk),
                    "limit" => 200
                ]));
                if (count($list) > 0) {
                    foreach ($list as &$info) {
                        $seller = $spApi->specialSellerIdReverseConver($info['channel']);
                        $redisService->hSet("spNegativeTarget_{$seller}", $info['targetId'], $info['_id']);
                        $sellerAdList[$info['targetId']] = $info['_id'];
                    }
                }
            }

            // 构建关停请求参数
            $idWithTargetId = [];
            foreach ($targetIds as $targetId) {
                $idWithTargetId[] = [
                    "targetId" => $targetId,
                    "state" => "paused"
                ];
            }

            // 分批调用关停API
            foreach (array_chunk($idWithTargetId, 200) as $chunk) {
                $pausedAdIdResult = $spApi->updateNegativeTarget($sellerId, $chunk);
                if (isset($pausedAdIdResult['success']) && count($pausedAdIdResult['success']) > 0) {
                    $this->log("{$sellerId} 重新关停成功: " . count($pausedAdIdResult['success']) . "个");
                    foreach ($pausedAdIdResult['success'] as $targetId) {
                        $retrySuccessCount++;
                        if (isset($sellerAdList[$targetId]) && $sellerAdList[$targetId]) {
                            $_id = $sellerAdList[$targetId];
                            $spApi->mongoUpdateNegativeTarget($_id, $targetId, "paused");
                        }
                    }
                }
                if (isset($pausedAdIdResult['error']) && count($pausedAdIdResult['error']) > 0) {
                    $this->log("{$sellerId} 重新关停仍然失败: " . count($pausedAdIdResult['error']) . "个");
                    foreach ($pausedAdIdResult['error'] as $targetId) {
                        $itemChannel = !empty($targetIdChannelMap) ? ($targetIdChannelMap[$sellerId . '_' . $targetId] ?: ($sellerChannel ?: $channelLabel)) : ($sellerChannel ?: $channelLabel);
                        $retryFailedList[] = [
                            "channel" => $itemChannel,
                            "seller_id" => $sellerId,
                            "target_id" => (string)$targetId,
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
            $excelUtilsRetry = new ExcelUtils("sp/negativeTarget/");
            $retryFilePath = $excelUtilsRetry->downloadXlsx([
                "channel",
                "seller_id",
                "target_id",
            ], $retryFailedList, "重新关停仍失败_negative_target_{$channelLabel}_" . date("YmdHis") . ".xlsx", [2]);
            $this->log("重新关停仍失败数据已导出: {$retryFilePath}");
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
$con = new SpPausedNegativeTargetController();
if ($method == 'verify') {
    $con->verifyPausedNegativeTargetStates($file, $channel);
} elseif ($method == 'retry') {
    // 从导出的关停失败Excel读取数据，重新执行关停
    $channelLabel = empty($channel) ? '全部' : $channel;
    $excelUtils = new ExcelUtils();
    $failedList = [];
    $targetIdChannelMap = [];
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
        $excelUtils->eachXlsxRow($filePath, function ($item) use (&$failedList, &$targetIdChannelMap, $channel) {
            $targetId = trim(sprintf('%.0f', (float)($item['target_id'] ?? $item['targetid'] ?? 0)), "'");
            $sellerId = trim($item['seller_id'] ?? '');
            $ch = trim($item['channel'] ?? '');
            if ($targetId === '' || $targetId === '0' || $sellerId === '') {
                return;
            }
            if (!empty($channel) && $ch !== $channel) {
                return;
            }
            $failedList[] = [
                "channel" => $ch,
                "seller_id" => $sellerId,
                "target_id" => $targetId,
            ];
            $targetIdChannelMap[$sellerId . '_' . $targetId] = $ch;
        });
    } catch (Exception $e) {
        die($e->getLine() . " : " . $e->getMessage());
    }
    $con->retryPausedNegativeTarget($failedList, $channelLabel, $targetIdChannelMap);
} else {
    $con->pausedNegativeTarget($file, $channel);
}
