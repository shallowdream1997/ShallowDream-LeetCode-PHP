<?php
require_once(dirname(__FILE__) . "/../../../../php/requiredfile/requiredfile.php");
require_once(dirname(__FILE__) . "/../../../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/RequestUtils.php");
require_once(dirname(__FILE__) . "/../SpApi.php");

class SpEnabledNegativeTargetController
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
     * 启用否定target广告
     * Excel格式: channel | seller_id | target_id
     * 用法: php SpEnabledNegativeTargetController.php file="11-25开广告target.xlsx" channel=amazon_us
     *       php SpEnabledNegativeTargetController.php file="11-25开广告target.xlsx"  (处理全部channel)
     * @param string $file Excel文件名(在./excel/目录下)
     * @param string $channel 可选，按channel过滤数据，不传则处理全部
     */
    public function enabledNegativeTarget($file = "", $channel = ""){
        $channelLabel = empty($channel) ? '全部' : $channel;
        $this->log("enabledNegativeTarget 开始处理 file:{$file} channel:{$channelLabel}");
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
                        "state" => "enabled"
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
                            $this->log("{$sellerId} 开启成功: " . count($pausedAdIdResult['success']) . "个");
                            $batchUpdateList = [];
                            foreach ($pausedAdIdResult['success'] as $targetId){
                                if (isset($sellerAdList[$targetId]) && $sellerAdList[$targetId]){
                                    $batchUpdateList[] = [
                                        '_id' => $sellerAdList[$targetId],
                                        'targetId' => $targetId,
                                        'state' => 'enabled'
                                    ];
                                }
                            }
                            if (!empty($batchUpdateList)) {
                                $spApi->batchMongoUpdateNegativeTarget($batchUpdateList);
                            }
                        }
                        if (isset($pausedAdIdResult['error']) && count($pausedAdIdResult['error']) > 0){
                            //失败的targetId
                            $this->log("{$sellerId} 开启失败: " . count($pausedAdIdResult['error']) . "个");
                            foreach ($pausedAdIdResult['error'] as $targetId){
                                $itemChannel = $targetIdChannelMap[$sellerId . '_' . $targetId] ?: ($sellerChannel ?: ($channel ?: '全部'));
                                $exportList[] = [
                                    "channel" => $itemChannel,
                                    "seller_id" => $sellerId,
                                    "target_id" => (string)$targetId,
                                    "message" => $pausedAdIdResult['errorMsg'][$targetId] ?? "API操作失败",
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
                    "message",
                ], $exportList, "开启失败_negative_target_{$channelLabel}_" . date("YmdHis") . ".xlsx", [2]);
                $this->log("开启失败数据已导出: {$filePath}");
            } else {
                $this->log("所有negativeTarget开启成功，无失败数据");
            }

            $this->log("enabledNegativeTarget channel:{$channelLabel} 处理完毕");
        } else {
            $this->log("enabledNegativeTarget channel:{$channelLabel} 无数据");
        }
        $this->dingTalk();
    }


    /**
     * 校验否定target广告状态是否正确修改为enabled
     * 通过Amazon API查询否定target的实际状态，与期望状态对比
     * 只导出开启失败（状态不等于enabled）的数据，格式与开启输入一致，可直接重新执行开启
     * 用法: php SpEnabledNegativeTargetController.php method=verify file="11-25开广告target.xlsx" channel=amazon_us
     * @param string $file Excel文件名(在./excel/目录下)
     * @param string $channel 必填，按channel过滤数据，可选值: amazon_us, amazon_uk, amazon_ca等
     */
    public function verifyEnabledNegativeTargetStates($file = "", $channel = ""){
        $channelLabel = empty($channel) ? '全部' : $channel;
        $this->log("verifyEnabledNegativeTargetStates 开始校验 file:{$file} channel:{$channelLabel}");
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
            $this->log("verifyEnabledNegativeTargetStates channel:{$channelLabel} 无数据");
            return;
        }

        $exportList = [];
        $verifiedCount = 0;
        $archivedCount = 0;
        $enabledCount = 0;
        $notEnabledCount = 0;
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
                        if ($actualState === "enabled"){
                            $enabledCount++;
                        } else {
                            $notEnabledCount++;
                            $this->log("❌ {$sellerId} targetId:{$targetId} 开启失败: 期望enabled, 实际{$actualState}");
                            $itemChannel = $targetIdChannelMap[$sellerId . '_' . $targetId] ?: ($sellerChannel ?: $channelLabel);
                            $exportList[] = [
                                "channel" => $itemChannel,
                                "seller_id" => $sellerId,
                                "target_id" => (string)$targetId,
                                "actual_state" => $actualState,
                                "expected_state" => "enabled",
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
        $this->log("✅ 已开启(enabled): {$enabledCount}");
        $this->log("❌ 开启失败(非enabled): {$notEnabledCount}");

        // 导出开启失败的数据，格式与开启输入一致，可直接重新执行开启
        if (count($exportList) > 0){
            $excelUtilsExport = new ExcelUtils("sp/negativeTarget/");
            $filePath = $excelUtilsExport->downloadXlsx([
                "channel",
                "seller_id",
                "target_id",
                "actual_state",
                "expected_state",
            ], $exportList, "开启失败_negative_target_{$channelLabel}_" . date("YmdHis") . ".xlsx", [2]);
            $this->log("开启失败数据已导出: {$filePath}");
        } else {
            $this->log("所有negativeTarget状态校验通过，无开启失败数据");
        }

        // 对开启失败的数据重新执行开启
        if (count($exportList) > 0) {
            $this->retryEnabledNegativeTarget($exportList, $channelLabel, $targetIdChannelMap);
        }

        $this->log("verifyEnabledNegativeTargetStates channel:{$channelLabel} 校验完毕");
    }


    /**
     * 对开启失败的negativeTarget数据重新执行开启
     * 输入数据格式: [{"channel"=>"xxx", "seller_id"=>"xxx", "target_id"=>"xxx"}, ...]
     * 开启成功则更新mongo，仍然失败的导出Excel
     *
     * 用法(由verify内部调用，也可单独使用):
     *   php SpEnabledNegativeTargetController.php method=retry file="开启失败_negative_target_xxx.xlsx" channel=amazon_us
     *
     * @param array $failedList 开启失败数据列表，每项包含 channel/seller_id/target_id
     * @param string $channelLabel channel标签，用于日志和导出文件名
     * @param array $targetIdChannelMap sellerId_targetId => channel 映射，用于获取channel（从Excel读取时传入）
     */
    public function retryEnabledNegativeTarget($failedList = [], $channelLabel = '全部', $targetIdChannelMap = []){
        if (count($failedList) <= 0) {
            $this->log("retryEnabledNegativeTarget 无需重试");
            return;
        }

        $this->log("========== 开始重新开启失败数据 ==========");
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
            $this->log("重新开启 {$sellerId} 共 " . count($targetIds) . " 个targetId");

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

            // 构建开启请求参数
            $idWithTargetId = [];
            foreach ($targetIds as $targetId) {
                $idWithTargetId[] = [
                    "targetId" => $targetId,
                    "state" => "enabled"
                ];
            }

            // 分批调用开启API
            foreach (array_chunk($idWithTargetId, 200) as $chunk) {
                $pausedAdIdResult = $spApi->updateNegativeTarget($sellerId, $chunk);
                if (isset($pausedAdIdResult['success']) && count($pausedAdIdResult['success']) > 0) {
                    $this->log("{$sellerId} 重新开启成功: " . count($pausedAdIdResult['success']) . "个");
                    $batchUpdateList = [];
                    foreach ($pausedAdIdResult['success'] as $targetId) {
                        $retrySuccessCount++;
                        if (isset($sellerAdList[$targetId]) && $sellerAdList[$targetId]) {
                            $batchUpdateList[] = [
                                '_id' => $sellerAdList[$targetId],
                                'targetId' => $targetId,
                                'state' => 'enabled'
                            ];
                        }
                    }
                    if (!empty($batchUpdateList)) {
                        $spApi->batchMongoUpdateNegativeTarget($batchUpdateList);
                    }
                }
                if (isset($pausedAdIdResult['error']) && count($pausedAdIdResult['error']) > 0) {
                    $this->log("{$sellerId} 重新开启仍然失败: " . count($pausedAdIdResult['error']) . "个");
                    foreach ($pausedAdIdResult['error'] as $targetId) {
                        $itemChannel = !empty($targetIdChannelMap) ? ($targetIdChannelMap[$sellerId . '_' . $targetId] ?: ($sellerChannel ?: $channelLabel)) : ($sellerChannel ?: $channelLabel);
                        $retryFailedList[] = [
                            "channel" => $itemChannel,
                            "seller_id" => $sellerId,
                            "target_id" => (string)$targetId,
                            "message" => $pausedAdIdResult['errorMsg'][$targetId] ?? "API启用失败",
                        ];
                    }
                }
            }
        }

        // 输出重新开启汇总
        $this->log("========== 重新开启汇总 ==========");
        $this->log("重新开启总数: " . count($failedList));
        $this->log("✅ 重新开启成功: {$retrySuccessCount}");
        $this->log("❌ 重新开启仍然失败: " . count($retryFailedList));

        // 导出仍然失败的数据
        if (count($retryFailedList) > 0) {
            $excelUtilsRetry = new ExcelUtils("sp/negativeTarget/");
            $retryFilePath = $excelUtilsRetry->downloadXlsx([
                "channel",
                "seller_id",
                "target_id",
                "message",
            ], $retryFailedList, "重新启用仍失败_negative_target_{$channelLabel}_" . date("YmdHis") . ".xlsx", [2]);
            $this->log("重新启用仍失败数据已导出: {$retryFilePath}");
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
$con = new SpEnabledNegativeTargetController();
if ($method == 'verify') {
    $con->verifyEnabledNegativeTargetStates($file, $channel);
} elseif ($method == 'retry') {
    // 从导出的开启失败Excel读取数据，重新执行开启
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
    $con->retryEnabledNegativeTarget($failedList, $channelLabel, $targetIdChannelMap);
} else {
    $con->enabledNegativeTarget($file, $channel);
}
