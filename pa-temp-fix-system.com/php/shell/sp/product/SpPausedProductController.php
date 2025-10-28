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

    public function pausedProducts(){
        $excelUtils = new ExcelUtils();
        $curlService = (new CurlService())->pro();
        $redisService = new RedisService();
        $spApi = new SpApi();
        try {
            $contentList = $excelUtils->getXlsxData("/xp/www/ShallowDream-LeetCode-PHP/pa-temp-fix-system.com/php/export/sp/关停products_2.xlsx");
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }
        if (count($contentList) > 0) {
            $sellerIdAdId = [];
            foreach ($contentList as $item){
                $sellerIdAdId[$item['seller_id']][] = $item['adid'];
            }

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
                        $boolean = $spApi->pausedProduct($sellerId,$chunk);
                        if ($boolean){
                            $this->log("{$sellerId} 关停成功: " . count($chunk) . "个");
                            foreach ($chunk as $item){
                                if (isset($sellerAdList[$item['adId']]) && $sellerAdList[$item['adId']]){
                                    $_id = $sellerAdList[$item['adId']];
                                    $spApi->mongoUpdateProduct($_id, $item['adId'], "paused");
                                }
                            }
                        }

                    }
                }

            }

        }

    }



}

$con = new SpPausedProductController();
$con->pausedProducts();