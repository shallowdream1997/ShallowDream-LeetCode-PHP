<?php
require_once(dirname(__FILE__) . "/../../../../php/requiredfile/requiredfile.php");
require_once(dirname(__FILE__) . "/../../../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/RequestUtils.php");
require_once(dirname(__FILE__) . "/../SpApi.php");

class SpArchivedErrorAdGroupV2Controller
{
    private $log;

    private $redis;

    public function __construct()
    {
        $this->log = new MyLogger("sp");
        $this->redis = new RedisService();
    }

    private function log(string $string = "")
    {
        $this->log->log2($string);
    }


    public function archivedErrorAdGroup(){
        $excelUtils = new ExcelUtils();
        $curlService = (new CurlService())->pro();

        $spApi = new SpApi();
        try {
            $contentList = $excelUtils->getXlsxData("./excel/要归档的adgroupid广告.xlsx");
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }
        if (count($contentList) > 0) {
            $adGroupIds = [];
            foreach ($contentList as $content) {
                if ($content["adgroupid"]) {
                    $adGroupIds[] = $content["adgroupid"];
                }
            }
            $adGroupIdInfoList = $spApi->getMongoAdGroups($adGroupIds);
            foreach ($adGroupIdInfoList as $adGroupInfo){

            }


            foreach ($contentList as $content){
                if (!$content["adgroupid"]){
                    continue;
                }
                $adGroupId = $content["adgroupid"];
                $sellerId = $spApi->specialSellerIdReverseConver($content["seller_id"]);
                $channel = $content['channel'];


                $a = $this->redis->hGet("adGroupAdGroupId", $adGroupId);
                $adGroupInfo = [];
                if (!$a){
                    $adGroupInfo = $spApi->getMongoAdGroupInfo($sellerId, '', '',$adGroupId);
                    $this->redis->hSet("adGroupAdGroupId", $adGroupId, json_encode($adGroupInfo,JSON_UNESCAPED_UNICODE));
                }else{
                    $adGroupInfo = json_decode($a,true);
                }
                if ($adGroupInfo){
                    $adGroupName = $adGroupInfo['adGroupName'];
                    $this->log("{$sellerId} {$adGroupInfo['campaignId']} {$adGroupName}");

                    $a1 = $this->redis->hGet("productAdGroupId", $adGroupId);
                    $a2 = $this->redis->hGet("keywordAdGroupId", $adGroupId);
                    $a3 = $this->redis->hGet("negativeKeywordAdGroupId", $adGroupId);
                    $a4 = $this->redis->hGet("targetAdGroupId", $adGroupId);



                    //找到product
                    $products = [];
                    if (!$a1){
                        $products = $spApi->getMongoProductInfo($sellerId, $adGroupInfo['campaignId'], $adGroupInfo['adGroupId'],'',500);
                        $this->redis->hSet("productAdGroupId", $adGroupId, json_encode($products,JSON_UNESCAPED_UNICODE));
                    }else{
                        $products = json_decode($a1,true);
                    }
                    $this->log("product数量：" . count($products));
                    foreach ($products as $product){
                        //该删还是要删
                        $spApi->deleteMongoProductAdsInfo($product['_id']);
                        if (!$product['adId']){
                            $this->log("sku：{$product['sku']},没有adId");
                            continue;
                        }
                        $this->log("sku：{$product['sku']},adId：{$product['adId']}");
                    }

                    //找到keyword
                    $keywords = [];
                    if (!$a2){
                        $keywords = $spApi->getMongoKeywordInfoV2($sellerId, $adGroupInfo['campaignId'], $adGroupInfo['adGroupId']);
                        $this->redis->hSet("keywordAdGroupId", $adGroupId, json_encode($keywords,JSON_UNESCAPED_UNICODE));
                    }else{
                        $keywords = json_decode($a2,true);
                    }
                    $this->log("keyword数量：" . count($keywords));
                    foreach ($keywords as $keyword){
                        //该删还是要删
                        $spApi->deleteMongoKeywordInfo($keyword['_id']);
                        if (!$keyword['keywordId']){
                            $this->log("matchType：{$keyword['matchType']}，keywordText：{$keyword['keywordText']},没有keywordId");
                            continue;
                        }
                        $this->log("matchType：{$keyword['matchType']}，keywordText：{$keyword['keywordText']},keywordId：{$keyword['keywordId']}");
                    }


                    //找到negativeKeyword
                    $negativeKeywords = [];
                    if (!$a3){
                        $negativeKeywords = $spApi->getMongoNegativeKeywordInfoV2($sellerId, $adGroupInfo['campaignId'], $adGroupInfo['adGroupId']);
                        $this->redis->hSet("negativeKeywordAdGroupId", $adGroupId, json_encode($negativeKeywords,JSON_UNESCAPED_UNICODE));
                    }else{
                        $negativeKeywords = json_decode($a3,true);
                    }
                    $this->log("否定keyword数量：" . count($negativeKeywords));
                    foreach ($negativeKeywords as $keyword){
                        //该删还是要删
                        $spApi->deleteMongoNegativeKeywordInfo($keyword['_id']);
                        if (!$keyword['keywordId']){
                            $this->log("matchType：{$keyword['matchType']}，keywordText：{$keyword['keywordText']},没有keywordId");
                            continue;
                        }
                        $this->log("matchType：{$keyword['matchType']}，keywordText：{$keyword['keywordText']},keywordId：{$keyword['keywordId']}");
                    }

                    //找到target
                    $targets = [];
                    if (!$a4){
                        $targets = $spApi->getMongoTargetAsinV2($sellerId, $adGroupInfo['campaignId'], $adGroupInfo['adGroupId']);
                        $this->redis->hSet("targetAdGroupId", $adGroupId, json_encode($targets,JSON_UNESCAPED_UNICODE));
                    }else{
                        $targets = json_decode($a4,true);
                    }
                    $this->log("target数量：" . count($targets));
                    foreach ($targets as $target){
                        //该删还是要删
                        $spApi->deleteMongoTargetInfo($target['_id']);
                        if (!$target['targetId']){
                            $this->log("type：{$target['type']}，value：{$target['value']},没有targetId");
                            continue;
                        }
                        $this->log("type：{$target['type']}，value：{$target['value']},targetId：{$target['targetId']}");
                    }

                    $spApi->deleteMongoAdGroupInfo($adGroupInfo['_id']);





                }else{
                    $this->log("没有adGroupInfo");
                }

            }

            $adGroupIds = [];
            foreach ($contentList as $content){
                if ($content['adgroupid']){
                    $adGroupIds[$content['seller_id']][] = $content['adgroupid'];
                }
            }
            if ($adGroupIds){
                $exportList = [];
                foreach ($adGroupIds as $sellerId=>$asgids){

                    foreach (array_chunk($asgids,100) as $adGroupIdsChunk){
                        $spApi = new SpApi();
                        $last = $spApi->archivedAdGroup($sellerId,$adGroupIdsChunk);
                        foreach ($last as $i){

                            $exportList[] = [
                                "sellerId" => $sellerId,
                                "adGroupId" => "'" . $i['adGroupId'],
                                "msg" => $i['msg'],
                            ];


                        }
                    }
                }



                if (count($exportList) > 0){
                    $excelUtils = new ExcelUtils("sp/");
                    $filePath = $excelUtils->downloadXlsx([
                        "sellerId",
                        "adGroupId",
                        "msg",
                    ], $exportList, "归档adGroupId结果_" . date("YmdHis") . ".xlsx");
                }


            }


        }

    }


    public function reloadEnabledAdGroup($channel)
    {
        $excelUtils = new ExcelUtils();
        $curlService = (new CurlService())->pro();

        $spApi = new SpApi();
        try {
            $contentList = $excelUtils->getXlsxData("./excel/要归档的广告非US.xlsx");
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }
        if (count($contentList) > 0) {

            $placement = [];
            foreach ($contentList as $content){
                if ($content['channel'] === $channel){
                    $placement[$content['channel']][$content['seller_id']][] = $content['scu_id'];
                }
            }

            foreach ($placement as $channel => $data){
                foreach ($data as $sellerId=>$scuIds){
                    $scuIds = array_unique($scuIds);
                    $this->log("channel: {$channel}，sellerId：{$sellerId}，scuIds：" . count($scuIds) . "个");

                    foreach ($scuIds as $scuId){
                        //auto
                        $spApi->paPlacementAmazonSp($channel, $sellerId, 1,'auto','auto',$scuId);
                        //keyword
                        $spApi->paPlacementAmazonSp($channel, $sellerId, 2,'manual','keyword',$scuId);
                        //asin
                        $spApi->paPlacementAmazonSp($channel, $sellerId, 3,'manual','asin',$scuId);
                        //category
                        $spApi->paPlacementAmazonSp($channel, $sellerId, 4,'manual','category',$scuId);
                    }
                }


            }
        }

    }


    public function archivedErrorAdGroupV2()
    {
        $spApi = new SpApi();
        $last = $spApi->archivedAdGroup('amazon_us_sopro',['297566589454203']);
        $this->log(json_encode($last,JSON_UNESCAPED_UNICODE));
    }


}

$parameters = DataUtils::ExplainArgv(@$argv, array());
$params = (count(@$argv) > 1) ? $parameters : $_REQUEST;
$channel = "";
if (isset($params['channel']) && trim($params['channel'] != '')) {
    $channel = $params['channel'];
}
$con = new SpArchivedErrorAdGroupV2Controller();
$con->archivedErrorAdGroup();
//$con->reloadEnabledAdGroup($channel);
//$con->archivedErrorAdGroupV2();
