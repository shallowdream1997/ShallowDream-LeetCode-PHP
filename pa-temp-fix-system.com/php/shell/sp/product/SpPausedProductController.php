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
        $this->log = new MyLogger("sp");
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
            'title' => "【product广告写入暂停完毕】提醒",
            'msg' => [
                [
                    "key" => "",
                    "value" => "{$datetime} product广告写入暂停完毕"
                ]
            ]
        );
        $ali->post("dingding/sendOaNotice",$postData);
        return $this;
    }

    public function pausedProducts($channel = "",$page = 0){
        $this->log("开始处理:{$channel}_$page");
        $excelUtils = new ExcelUtils();
        $curlService = (new CurlService())->pro();
        $redisService = new RedisService();
        $spApi = new SpApi();
        $sellerIdAdId = [];
        try {
            $excelUtils->eachXlsxRow("./excel/广告关停清单{$channel}_{$page}.xlsx", function ($item) use (&$sellerIdAdId) {
                if (!empty($item['adid'])) {
                    $sellerIdAdId[$item['sellerid']][] = $item['adid'];
                }
            });
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }
        if (count($sellerIdAdId) > 0) {
            $exportList = [];
            foreach ($sellerIdAdId as $sellerId => $adIds){
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
                        "channel" => $sellerId,
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
                            //成功的adId；
                            $this->log("{$sellerId} 关停成功: " . count($pausedAdIdResult['success']) . "个");
                            foreach ($pausedAdIdResult['success'] as $adId){
                                if (isset($sellerAdList[$adId]) && $sellerAdList[$adId]){
                                    $_id = $sellerAdList[$adId];
                                    $spApi->mongoUpdateProduct($_id, $adId, "paused");
                                }
                            }
                        }
                        if (isset($pausedAdIdResult['error']) && count($pausedAdIdResult['error']) > 0){
                            //失败的adId
                            $this->log("{$sellerId} 关停失败: " . count($pausedAdIdResult['error']) . "个");
                            foreach ($pausedAdIdResult['error'] as $adId){
                                $exportList[] = [
                                    "sellerId" => $sellerId,
                                    "adId" => "'" . $adId,
                                ];
                            }
                        }


                    }
                }
            }

            if (count($exportList) > 0){
                $excelUtils = new ExcelUtils("sp/");
                $filePath = $excelUtils->downloadXlsx([
                    "seller_id",
                    "adid",
                ], $exportList, "关停失败的adId_" . date("YmdHis") . ".xlsx");
            }

        }

    }


    public function findNoArchivedPausedProducts($channel = "",$page = 0){
        $this->log("开始处理:{$channel}_$page");
        $excelUtils = new ExcelUtils();
        $curlService = (new CurlService())->pro();
        $redisService = new RedisService();
        $spApi = new SpApi();
        $sellerIdAdId = [];
        try {
            $excelUtils->eachXlsxRow("./excel/广告关停清单{$channel}_{$page}.xlsx", function ($item) use (&$sellerIdAdId) {
                if (!empty($item['adid'])) {
                    $sellerIdAdId[$item['sellerid']][] = $item['adid'];
                }
            });
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }
        if (count($sellerIdAdId) > 0) {
            $exportList = [];
            foreach ($sellerIdAdId as $sellerId => $adIds){
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
                        "channel" => $sellerId,
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
                            //成功的adId；
                            $this->log("{$sellerId} 关停成功: " . count($pausedAdIdResult['success']) . "个");
                            foreach ($pausedAdIdResult['success'] as $adId){
                                if (isset($sellerAdList[$adId]) && $sellerAdList[$adId]){
                                    $_id = $sellerAdList[$adId];
                                    $spApi->mongoUpdateProduct($_id, $adId, "paused");
                                }
                            }
                        }
                        if (isset($pausedAdIdResult['error']) && count($pausedAdIdResult['error']) > 0){
                            //失败的adId
                            $this->log("{$sellerId} 关停失败: " . count($pausedAdIdResult['error']) . "个");
                            foreach ($pausedAdIdResult['error'] as $adId){
                                $exportList[] = [
                                    "sellerId" => $sellerId,
                                    "adId" => "'" . $adId,
                                ];
                            }
                        }


                    }
                }
            }

            if (count($exportList) > 0){
                $excelUtils = new ExcelUtils("sp/");
                $filePath = $excelUtils->downloadXlsx([
                    "seller_id",
                    "adid",
                ], $exportList, "关停失败的adId_" . date("YmdHis") . ".xlsx");
            }

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
        if (empty($channel)) {
            $this->log("channel参数必填，可选值: amazon_us, amazon_uk, amazon_ca");
            die("channel参数必填，可选值: amazon_us, amazon_uk, amazon_ca\n");
        }
        $this->log("pausedProductV2s 开始处理 file:{$file} channel:{$channel}");
        $excelUtils = new ExcelUtils();
        $curlService = (new CurlService())->pro();
        $redisService = new RedisService();
        $spApi = new SpApi();
        $sellerIdAdId = [];
        $totalAdIdCount = 0;
        try {
            $excelUtils->eachXlsxRow("./excel/{$file}", function ($item) use (&$sellerIdAdId, &$totalAdIdCount, $channel) {
                if (!empty($item['ad_id']) && isset($item['channel']) && $item['channel'] == $channel) {
                    $sellerIdAdId[$item['seller_id']][] = $item['ad_id'];
                    $totalAdIdCount++;
                }
            });
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }

        $this->log("channel:{$channel} 共 " . count($sellerIdAdId) . " 个seller, {$totalAdIdCount} 个adId");

        if (count($sellerIdAdId) > 0) {
            $exportList = [];
            foreach ($sellerIdAdId as $sellerId => $adIds){
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
                            //成功的adId；
                            $this->log("{$sellerId} 关停成功: " . count($pausedAdIdResult['success']) . "个");
                            foreach ($pausedAdIdResult['success'] as $adId){
                                if (isset($sellerAdList[$adId]) && $sellerAdList[$adId]){
                                    $_id = $sellerAdList[$adId];
                                    $spApi->mongoUpdateProduct($_id, $adId, "paused");
                                }
                            }
                        }
                        if (isset($pausedAdIdResult['error']) && count($pausedAdIdResult['error']) > 0){
                            //失败的adId
                            $this->log("{$sellerId} 关停失败: " . count($pausedAdIdResult['error']) . "个");
                            foreach ($pausedAdIdResult['error'] as $adId){
                                $exportList[] = [
                                    "sellerId" => $sellerId,
                                    "adId" => "'" . $adId,
                                ];
                            }
                        }


                    }
                }
            }

            if (count($exportList) > 0){
                $excelUtils = new ExcelUtils("sp/");
                $filePath = $excelUtils->downloadXlsx([
                    "seller_id",
                    "adid",
                ], $exportList, "关停失败的adId_{$channel}_" . date("YmdHis") . ".xlsx");
            }

            $this->log("pausedProductV2s channel:{$channel} 处理完毕");
        } else {
            $this->log("pausedProductV2s channel:{$channel} 无数据");
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
if ($method == 'v2') {
    $con->pausedProductV2s($file, $channel);
} else {
    $con->pausedProducts($channel, $page);
}
