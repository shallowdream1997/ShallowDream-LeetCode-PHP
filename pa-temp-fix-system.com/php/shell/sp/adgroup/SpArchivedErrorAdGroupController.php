<?php
require_once(dirname(__FILE__) . "/../../../../php/requiredfile/requiredfile.php");
require_once(dirname(__FILE__) . "/../../../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/RequestUtils.php");
require_once(dirname(__FILE__) . "/../SpApi.php");

class SpArchivedErrorAdGroupController
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

    private function isCategory($campaignName) {
        // 转换为小写以便统一处理英文关键词
        $lowerName = strtolower($campaignName);

        // 检查是否包含 cate（这样也涵盖了 category）
        if (strpos($lowerName, 'cate') !== false) {
            return true;
        }

        // 检查是否包含中文“分类”
        if (strpos($campaignName, '分类') !== false) {
            return true;
        }

        return false;
    }


    private function isAsin($campaignName) {
        // 转换为小写以便统一处理英文关键词
        $lowerName = strtolower($campaignName);

        // 检查是否包含 cate（这样也涵盖了 category）
        if (strpos($lowerName, 'asin') !== false) {
            return true;
        }

        // 检查是否包含中文“分类”
        if (strpos($campaignName, '分类') !== false) {
            return true;
        }

        return false;
    }



    public function archivedErrorAdGroup(){
        $excelUtils = new ExcelUtils();
        $curlService = (new CurlService())->pro();

        $spApi = new SpApi();
        try {
            $contentList = $excelUtils->getXlsxData("./excel/要归档的广告.xlsx");
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }
        if (count($contentList) > 0) {
            foreach ($contentList as $content){
                if (!$content["adgroupid"]){
                    continue;
                }
                $adGroupId = $content["adgroupid"];
                $sellerId = $spApi->specialSellerIdReverseConver($content["seller_id"]);
                $channel = $content['channel'];


                $adGroupInfo = $spApi->getMongoAdGroupInfo($sellerId, '', '',$adGroupId);
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
                        if (!$target['targetId']){
                            $this->log("type：{$target['type']}，value：{$target['value']},没有targetId");
                            continue;
                        }
                        $this->log("type：{$target['type']}，value：{$target['value']},targetId：{$target['targetId']}");
                    }


                }

            }



        }

    }




}

$con = new SpArchivedErrorAdGroupController();
$con->archivedErrorAdGroup();
