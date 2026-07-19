<?php

require_once dirname(__FILE__) . '/../../../../php/bootstrap.php';

class SpPausedTargetController
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

    public function dingTalk()
    {
        $proCurlService = new CurlService();
        $ali = $proCurlService->test()->phpali();

        $datetime = date("Y-m-d H:i:s", time());
        $postData = array(
            'userType' => 'userName',
            'userIdList' => "zhouangang",
            'title' => "【target广告写入暂停完毕】提醒",
            'msg' => [
                [
                    "key" => "",
                    "value" => "{$datetime} target广告写入暂停完毕"
                ]
            ]
        );
        $ali->post("dingding/sendOaNotice", $postData);
        return $this;
    }

    public function pausedTargets($channel = "", $page = 0)
    {
        $this->log("开始处理:{$channel}_{$page}");
        $excelUtils = new ExcelUtils();
        $curlService = (new CurlService())->pro();
        $redisService = new RedisService();
        $spApi = new SpApi();
        $sellerTargetIds = [];
        try {
            $excelUtils->eachXlsxRow(__DIR__."/excel/target_Id关停清单_{$channel}_{$page}.xlsx", function ($item) use (&$sellerTargetIds) {
                $sellerId = trim($item['seller_id'] ?? '');
                $targetId = trim((string)($item['target_id'] ?? ''), "'");
                if ($sellerId !== "" && $targetId !== "") {
                    $sellerTargetIds[$sellerId][] = $targetId;
                }
            });
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }

        if (count($sellerTargetIds) <= 0) {
            $this->log("没有可处理的target");
            return;
        }

        $exportList = [];
        foreach ($sellerTargetIds as $sellerId => $targetIds) {
            $targetIds = array_values(array_unique($targetIds));
            $sellerTargetList = $redisService->hGetAll("spTarget_{$sellerId}");
            $this->log("{$sellerId} 数量: " . count($sellerTargetList) . "个");

            $updateList = [];
            foreach ($targetIds as $targetId) {
                $updateList[] = [
                    "targetId" => $targetId,
                    "state" => "paused",
                ];
            }

            foreach (array_chunk($targetIds, 200) as $chunk) {
                $list = DataUtils::getPageList($curlService->s3023()->get("amazon_sp_targets/queryPage", [
                    "channel" => $spApi->specialSellerIdConver($sellerId),
                    "targetId_in" => implode(',', $chunk),
                    "limit" => 200
                ]));
                if (count($list) > 0) {
                    foreach ($list as $info) {
                        $seller = $spApi->specialSellerIdReverseConver($info['channel']);
                        $redisService->hSet("spTarget_{$seller}", $info['targetId'], $info['_id']);
                        $sellerTargetList[$info['targetId']] = $info['_id'];
                    }
                }
            }

            if (count($updateList) > 0) {
                foreach (array_chunk($updateList, 200) as $chunk) {
                    $this->log("{$sellerId} 关停target: " . count($chunk) . "个");
                    $pausedTargetResult = $spApi->updateTarget($sellerId, $chunk);
                    if (isset($pausedTargetResult['success']) && count($pausedTargetResult['success']) > 0) {
                        $this->log("{$sellerId} 关停成功: " . count($pausedTargetResult['success']) . "个");
                        foreach ($pausedTargetResult['success'] as $targetId) {
                            if (isset($sellerTargetList[$targetId]) && $sellerTargetList[$targetId]) {
                                $spApi->mongoUpdateTarget($sellerTargetList[$targetId], $targetId, "paused");
                            } else {
                                $this->log("mongo不存在target但Amazon已处理成功: {$sellerId} - {$targetId}");
                            }
                        }
                    }
                    if (isset($pausedTargetResult['error']) && count($pausedTargetResult['error']) > 0) {
                        $this->log("{$sellerId} 关停失败: " . count($pausedTargetResult['error']) . "个");
                        foreach ($pausedTargetResult['error'] as $targetId) {
                            $exportList[] = [
                                "sellerId" => $sellerId,
                                "targetId" => "'" . $targetId,
                            ];
                        }
                    }
                }
            }
        }

        if (count($exportList) > 0) {
            $excelUtils = new ExcelUtils("sp/target/");
            $excelUtils->downloadXlsx([
                "seller_id",
                "targetId",
            ], $exportList, "关停失败的targetId_" . date("YmdHis") . ".xlsx");
        }

        $this->dingTalk();
    }
}

$parameters = DataUtils::ExplainArgv(@$argv, array());
$params = (count(@$argv) > 1) ? $parameters : $_REQUEST;
$channel = "";
$page = 0;
if (isset($params['channel']) && trim($params['channel']) != '') {
    $channel = $params['channel'];
}
if (isset($params['page']) && trim($params['page']) != '') {
    $page = $params['page'];
}
$con = new SpPausedTargetController();
$con->pausedTargets($channel, $page);
