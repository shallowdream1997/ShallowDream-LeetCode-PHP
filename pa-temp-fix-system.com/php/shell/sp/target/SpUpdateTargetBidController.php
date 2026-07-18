<?php
require_once(dirname(__FILE__) . "/../../../../php/requiredfile/requiredfile.php");
require_once(dirname(__FILE__) . "/../../../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/RequestUtils.php");
require_once(dirname(__FILE__) . "/../SpApi.php");

class SpUpdateTargetBidController
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
            'title' => "【target广告bid调整完毕】提醒",
            'msg' => [
                [
                    "key" => "",
                    "value" => "{$datetime} target广告bid调整完毕"
                ]
            ]
        );
        $ali->post("dingding/sendOaNotice", $postData);
        return $this;
    }

    public function updateTargetBid($channel = "", $page = 0)
    {
        $this->log("开始处理bid:{$channel}_{$page}");
        $excelUtils = new ExcelUtils();
        $curlService = (new CurlService())->pro();
        $redisService = new RedisService();
        $spApi = new SpApi();
        $sellerTargetBidMap = [];
        try {
            $excelUtils->eachXlsxRow(__DIR__."/excel/target_id调整bid清单_{$channel}_{$page}.xlsx", function ($item) use (&$sellerTargetBidMap) {
                $sellerId = trim($item['seller_id'] ?? '');
                $targetId = trim((string)($item['target_id'] ?? ''), "'");
                $bid = trim((string)($item['bid'] ?? ''));
                if ($sellerId !== "" && $targetId !== "" && $bid !== "") {
                    $sellerTargetBidMap[$sellerId][$targetId] = $bid;
                }
            });
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }

        if (count($sellerTargetBidMap) <= 0) {
            $this->log("没有可处理的target bid");
            return;
        }

        $exportList = [];
        foreach ($sellerTargetBidMap as $sellerId => $targetBidMap) {
            $targetIds = array_keys($targetBidMap);
            $sellerTargetList = $redisService->hGetAll("spTarget_{$sellerId}");
            $this->log("{$sellerId} 数量: " . count($sellerTargetList) . "个");

            $updateList = [];
            foreach ($targetBidMap as $targetId => $bid) {
                $updateList[] = [
                    "targetId" => $targetId,
                    "state" => "enabled",
                    "bid" => (float) $bid,
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
                    $this->log("{$sellerId} 调整target bid: " . count($chunk) . "个");
                    $updateTargetResult = $spApi->updateTarget($sellerId, $chunk);
                    if (isset($updateTargetResult['success']) && count($updateTargetResult['success']) > 0) {
                        $this->log("{$sellerId} 调整bid成功: " . count($updateTargetResult['success']) . "个");
                        foreach ($chunk as $item) {
                            if (in_array($item['targetId'], $updateTargetResult['success']) && isset($sellerTargetList[$item['targetId']]) && $sellerTargetList[$item['targetId']]) {
                                $spApi->mongoUpdateTarget($sellerTargetList[$item['targetId']], $item['targetId'], $item['state'], $item['bid']);
                            } elseif (in_array($item['targetId'], $updateTargetResult['success'])) {
                                $this->log("mongo不存在target但Amazon已处理成功: {$sellerId} - {$item['targetId']}");
                            }
                        }
                    }
                    if (isset($updateTargetResult['error']) && count($updateTargetResult['error']) > 0) {
                        $this->log("{$sellerId} 调整bid失败: " . count($updateTargetResult['error']) . "个");
                        foreach ($chunk as $item) {
                            if (in_array($item['targetId'], $updateTargetResult['error'])) {
                                $exportList[] = [
                                    "sellerId" => $sellerId,
                                    "targetId" => "'" . $item['targetId'],
                                    "bid" => $item['bid'],
                                ];
                            }
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
                "bid",
            ], $exportList, "调整targetBid失败_" . date("YmdHis") . ".xlsx");
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
$con = new SpUpdateTargetBidController();
$con->updateTargetBid($channel, $page);
