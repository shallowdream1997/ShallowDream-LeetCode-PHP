<?php
require_once(dirname(__FILE__) . "/../../../php/requiredfile/requiredfile.php");
require_once(dirname(__FILE__) . "/../../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../../php/utils/RequestUtils.php");

class SpApi
{
    private $log;

    private $curlService;
    private $messages = "system(zhouangang)";

    private $redis;

    public function __construct()
    {
        $this->log = new MyLogger("sp");
        $this->curlService = (new CurlService())->pro();
        $this->redis = new RedisService();
    }

    private function log(string $string = "")
    {
        $this->log->log2($string);
    }

    //===================================== campaign start ===================================================
    public function getMongoCampaignInfo($sellerId,$campaign)
    {
        $key = "{$sellerId}_{$campaign}";
        $str = $this->redis->hGet("campaignSpData",$key);
        $res = [];
        if (!$str){
            $res = DataUtils::getPageListInFirstData($this->curlService->s3023()->get("amazon_sp_campaigns/queryPage",[
                "company" => "CR201706060001",
                "channel" => $this->specialSellerIdConver($sellerId),
                "campaignName" => $campaign,
                "limit" => 1
            ]));
            if ($res){
                $this->redis->hSet("campaignSpData",$key,json_encode($res,JSON_UNESCAPED_UNICODE));
            }
        }else{
            $res = json_decode($str,true);
        }
        return $res;
    }
    public function mongoCreateCampaignInfo($sellerId,$fixCampaign,$campaignId,$oldCampaignInfo)
    {
        $createCampaign = [
            "company" => "CR201706060001",
            "channel" => $this->specialSellerIdConver($sellerId),
            "status" => "2",
            "messages" => $this->messages,
            "productLineId" => "",
            "campaignName" => $fixCampaign,
            "campaignId" => $campaignId,
            "targetingType" => "manual",
            "state" => "enabled",
            "dailyBudget" => $oldCampaignInfo['dailyBudget'],
            "bidStrategy" => isset($oldCampaignInfo['bidStrategy']) && $oldCampaignInfo['bidStrategy'] != "" ? $oldCampaignInfo['bidStrategy'] : "",
            "placementTop" => isset($oldCampaignInfo['placementTop']) && $oldCampaignInfo['placementTop'] != "" ? $oldCampaignInfo['placementTop'] : "",
            "placementProductPage" => isset($oldCampaignInfo['placementProductPage']) && $oldCampaignInfo['placementProductPage'] != "" ? $oldCampaignInfo['placementProductPage'] : "",
            "placementRestOfSearch" => isset($oldCampaignInfo['placementRestOfSearch']) && $oldCampaignInfo['placementRestOfSearch'] != "" ? $oldCampaignInfo['placementRestOfSearch'] : "",
            "startDate" => date("Ymd",time()),
            "createdBy" => $this->messages
        ];
        return DataUtils::getResultData($this->curlService->s3023()->post("amazon_sp_campaigns/",$createCampaign));
    }
    public function mongoUpdateCampaignInfo($_id,$campaignId)
    {
        $resp1 = $this->curlService->s3023()->post("amazon_sp_campaigns/updateCampaigns",[
            "id" => $_id,
            "isPassNotification" => "false",
            "from" => $this->messages,
            "updateParams" => [
                "campaignId" => $campaignId,
                "messages" => $this->messages
            ],
        ]);
        if ($resp1['result'] && isset($resp1['result']['campaign']) && count($resp1['result']['campaign']) > 0){
            $this->log("updateCampaign 成功：{$resp1['result']['campaign']['channel']} - {$resp1['result']['campaign']['campaignName']}");
        }else{
            $this->log("updateCampaign 失败：{$resp1['result']['campaign']['channel']} - {$resp1['result']['campaign']['campaignName']}");
        }

    }
    public function listCampaign($sellerId,$campaignName)
    {
        $condition = ["name" => $campaignName];

        $resp = DataUtils::getResultData($this->curlService->phphk()->get("amazon/ad/campaigns/getCampaigns/{$sellerId}", $condition));
        $campaignNameInfo = [];
        if ($resp && isset($resp['data']) && count($resp['data']) > 0){
            $campaignNameInfo = $resp['data'][0];
        }
        return $campaignNameInfo;
    }
    
    public function enabledCampaign($sellerId,$campaignName,$targetingType,$budget,$bidStrategy)
    {
        $createParams = [
            "name" => $campaignName,
            "campaignType" => "sponsoredProducts",
            "state" => "enabled",
            "targetingType" => $targetingType,
            "startDate" => date("Ymd",time()),
            "dailyBudget" => $budget
        ];

        $bidding = [];
        $bidding["strategy"] = $bidStrategy;
        if (count($bidding) > 0) {
            $createParams["bidding"] = $bidding;
        }


        $returnMessage = DataUtils::getResultData($this->curlService->phphk()->post("amazon/ad/campaigns/postCampaigns/{$sellerId}", [$createParams]));
        if($returnMessage['status'] == 'success' && count($returnMessage['data']) > 0 && $returnMessage['data'][0]['code'] == "SUCCESS"){
            //创建成功
            $amazonSpCampaignMsg = $returnMessage['data'][0];
            return $amazonSpCampaignMsg['campaignId'];
        }else{
            //这里如果失败的原因是DUPLICATE_VALUE ，则说明是campaign重复创建失败，需要递归，重新创建
            $this->log("创建campaign失败：{$sellerId} - {$campaignName}");
            return "";
        }
    }
    //===================================== campaign end ===================================================///


    //============================Target start==============================================///
    public function getMongoTargetAsin($sellerId,$campaignId,$adGroupId)
    {

        $mongoList = DataUtils::getPageList($this->curlService->s3023()->get("amazon_sp_targets/queryPage",[
            "companyId" => "CR201706060001",
            "channel" => $this->specialSellerIdConver($sellerId),
            "campaignId" => $campaignId,
            "adGroupId" => $adGroupId,
            "limit" => 1000
        ]));
        $mongoTargetMap = [];
        foreach ($mongoList as $mongo){
            $mongoTargetMap[$mongo['value']] = $mongo;
        }
        return $mongoTargetMap;
    }
    public function mongoCreateTargetAsin($sellerId, $campaignId, $adGroupId, $asin,$targetConfig)
    {
        $this->log("{$sellerId} - {$campaignId} - {$adGroupId} - {$asin} - create mongo");
        //需要新增mongo target
        $createMongoTarget = array(
            "companyId" => "CR201706060001",
            "campaignId" => $campaignId,
            "adGroupId" => $adGroupId,
            "targetId" => $targetConfig['targetId'],
            "channel" => $this->specialSellerIdConver($sellerId),
            "type" => "asinSameAs",
            "value" => $asin,
            "expressionType" => "manual",
            "state" => "enabled",
            "bid" => $targetConfig['bid'],
            "createdBy" => $this->messages,
            "modifiedBy" => $this->messages,
            "status" => 2,
            "targetName" => $asin
        );

        $resp1 = DataUtils::getResultData($this->curlService->s3023()->post("amazon_sp_targets/", $createMongoTarget));
        return $resp1;
    }

    public function mongoUpdateTarget($_id,$targetId,$bid)
    {

        $resp1 = $this->curlService->s3023()->post("amazon_sp_targets/updateBiddableTargets", [
            "id" => $_id,
            "isPassNotification" => "false",
            "from" => $this->messages,
            "updateParams" => [
                "targetId" => $targetId,
                "bid" => $bid,
                "modifiedBy" => $this->messages,
            ],
        ]);
        if ($resp1['result'] && isset($resp1['result']['target']) && count($resp1['result']['target']) > 0) {
            $this->log("updateBiddableTargets：成功：{$resp1['result']['target']['channel']} - {$resp1['result']['target']['value']} - {$resp1['result']['target']['type']}");
        }else{
            $this->log("updateBiddableTargets: 失败");
        }
    }

    public function listTargetAsin($sellerId,$campaignId,$adGroupId,$targetIdList = "")
    {
        $condition = ["campaignIdFilter" => $campaignId, "adGroupIdFilter" => $adGroupId];
        if($targetIdList != ""){
            $condition["targetIdFilter"] = $targetIdList;
        }

        $resp = DataUtils::getResultData($this->curlService->phphk()->get("amazon/ad/productTargeting/getTargets/{$sellerId}", $condition));
        $targetAsinMap = [];
        if ($resp && isset($resp['data']) && count($resp['data']) > 0){
            foreach ($resp['data'] as $spInfo){
                $asin = $spInfo['expression'][0]['value'];
                $targetAsinMap[$asin] = ["targetId" => $spInfo['targetId'],"bid" => $spInfo['bid']];
            }
        }
        return $targetAsinMap;
    }

    public function enabledAsinSp($createTargetList,$sellerId)
    {

        $res = DataUtils::getResultData($this->curlService->phphk()->post("amazon/ad/productTargeting/postTargets/{$sellerId}",$createTargetList));
        $targetIds = [];
        if ($res && $res['data']){
            $this->log("成功");
            foreach($res['data'] as $targetInfo){
                if ($targetInfo['code'] == "SUCCESS"){
                    $this->log("成功：{$targetInfo['targetId']}");
                    $targetIds[] = $targetInfo['targetId'];
                }
            }
        }else{
            $this->log("失败");
        }
        return $targetIds;
    }

    public function buildCreateTargetAsinList($campaignId,$adGroupId,$bid,$cpAsin)
    {
        $createTargetList = [];
        foreach ($cpAsin as $asin){
            //重新投放
            $condition = array(
                "campaignId" => (int)$campaignId,
                "adGroupId" => (int)$adGroupId,
                "state" => "enabled",
                "expressionType" => "manual",
                "bid" => $bid ? $bid : null,
            );
            $expressionGroup = array(
                "value" => $asin,
                "type" => "asinSameAs"
            );
            $condition['expression'] = array($expressionGroup);
            $condition['resolvedExpression'] = array($expressionGroup);
            $createTargetList[] = $condition;
        }
        return $createTargetList;
    }
    //============================Target end==============================================///


    //============================== AdGroup start==============================================///
    public function getMongoAdGroupInfo($sellerId,$campaignId,$adGroupName)
    {

        return DataUtils::getPageListInFirstData($this->curlService->s3023()->get("amazon_sp_adgroups/queryPage",[
            "channel" => $this->specialSellerIdConver($sellerId),
            "campaignId" => $campaignId,
            "adGroupName" => $adGroupName,
            "limit" => 1
        ]));
    }
    public function mongoCreateAdGroup($sellerId,$campaignId,$adGroupId,$adGroupName,$oldAdGroupInfo)
    {
        $createMongoAdGroup = [
            "channel" => $this->specialSellerIdConver($sellerId),
            "adGroupName" => $adGroupName,
            "status" => "2",
            "messages" => $this->messages,
            "campaignId" => $campaignId,
            "adGroupId" => $adGroupId,
            "state" => "enabled",
            "defaultBid" => $oldAdGroupInfo['defaultBid'],
            "createdBy" => $this->messages,
            "selectType" => $oldAdGroupInfo['selectType'] ?? []
        ];
        return DataUtils::getResultData($this->curlService->s3023()->post("amazon_sp_adgroups/", $createMongoAdGroup));
    }

    public function mongoUpdateAdGroup($_id,$adGroupId)
    {
        $resp = $this->curlService->s3023()->post("amazon_sp_adgroups/updateAdGroups", [
            "id" => $_id,
            "isPassNotification" => "false",
            "from" => $this->messages,
            "updateParams" => [
                "state" => "enabled",
                "adGroupId" => $adGroupId,
                "modifiedBy" => $this->messages,
                "status" => "2"
            ],
        ]);
        if ($resp['result'] && isset($resp['result']['adgroup']) && count($resp['result']['adgroup']) > 0) {
            $this->log("updateAdGroups：成功：{$resp['result']['adgroup']['channel']} - {$resp['result']['adgroup']['adGroupName']} - {$resp['result']['adgroup']['state']} - {$resp['result']['adgroup']['defaultBid']}");
        }else{
            $this->log("updateAdGroups：失败：{$resp['result']['adgroup']['channel']} - {$resp['result']['adgroup']['adGroupName']} - {$resp['result']['adgroup']['state']} - {$resp['result']['adgroup']['defaultBid']}",true);
        }
    }

    public function listAdGroup($sellerId,$campaignId,$adGroupName)
    {
        $condition = [
            "campaignIdFilter" => $campaignId,
            "name" => $adGroupName,
        ];
        $resp = DataUtils::getResultData($this->curlService->phphk()->get("amazon/ad/adGroups/getAdGroupsExtend/{$sellerId}", $condition));
        $adGroupNameInfo = [];
        if ($resp && isset($resp['data']) && count($resp['data']) > 0){
            $adGroupNameInfo = $resp['data'][0];
        }
        return $adGroupNameInfo;
    }

    public function enabledAdGroup($sellerId,$campaignId,$adGroupName,$defaultBid)
    {
        $returnMessage = DataUtils::getResultData($this->curlService->phphk()->post("amazon/ad/adGroups/postAdGroups/{$sellerId}", [[
            "name" => $adGroupName,
            "campaignId" => $campaignId,
            "defaultBid" => $defaultBid,
            "state" => "enabled"
        ]]));

        if ($returnMessage['status'] == 'success' && count($returnMessage['data']) > 0 && $returnMessage['data'][0]['code'] == "SUCCESS") {
            //创建成功
            return $returnMessage['data'][0]['adGroupId'];
        }else{
            $this->log("创建adGroup失败：{$sellerId} {$adGroupName}");
            return "";
        }
    }

    public function pausedAdGroup($sellerId,$adGroupId,$adGroupName)
    {
        $returnMessage = DataUtils::getResultData($this->curlService->phphk()->put("amazon/ad/adGroups/putAdGroups/{$sellerId}", [[
            "adGroupId" => $adGroupId,
            "state" => "paused"
        ]]));

        if ($returnMessage['status'] == 'success' && count($returnMessage['data']) > 0 && $returnMessage['data'][0]['code'] == "SUCCESS") {
            //创建成功
            return true;
        }else{
            $this->log("关停adGroup失败：{$sellerId} {$adGroupName}");
            return false;
        }
    }

    //=============================AdGroup end==============================================///


    //============================== Product start==============================================///
    public function getMongoProductInfo($sellerId,$campaignId,$adGroupId,$sku)
    {
        return DataUtils::getPageListInFirstData($this->curlService->s3023()->get("amazon_sp_products/queryPage",[
            "channel" => $this->specialSellerIdConver($sellerId),
            "campaignId" => $campaignId,
            "adGroupId" => $adGroupId,
            "sku" => $sku,
            "limit" => 1
        ]));
    }
    public function mongoCreateProduct($sellerId,$campaignId,$adGroupId,$sku,$adId,$oldProductInfo)
    {
        $createMongoAdGroup = [
            "channel" => $this->specialSellerIdConver($sellerId),
            "adId" => $adId,
            "status" => "2",
            "messages" => $this->messages,
            "campaignId" => $campaignId,
            "adGroupId" => $adGroupId,
            "state" => "enabled",
            "sku" => $sku,
            "asin" => $oldProductInfo['asin'] ?? "",
            "fixAsinDate" => $oldProductInfo['fixAsinDate'] ?? "",
            "createdBy" => $this->messages
        ];
        return DataUtils::getResultData($this->curlService->s3023()->post("amazon_sp_products/", $createMongoAdGroup));
    }

    public function mongoUpdateProduct($_id,$adId,$state)
    {
        $resp = $this->curlService->s3023()->post("amazon_sp_products/updateProductAds", [
            "id" => $_id,
            "isPassNotification" => "false",
            "from" => $this->messages,
            "updateParams" => [
                "adId" => $adId,
                "state" => $state,
                "modifiedBy" => $this->messages,
                "status" => "2"
            ],
        ]);
        if ($resp['result'] && isset($resp['result']['product']) && count($resp['result']['product']) > 0) {
            $this->log("updateProductAds：成功：{$resp['result']['product']['channel']} - {$resp['result']['product']['sku']}");
        }else{
            $this->log("updateProductAds：失败：{$resp['result']['product']['channel']} - {$resp['result']['product']['sku']}");
        }
    }

    public function listProduct($sellerId,$campaignId,$adGroupId,$sku)
    {
        $condition = [
            "campaignIdFilter" => $campaignId,
            "adGroupIdFilter" => $adGroupId
        ];
        $resp = DataUtils::getResultData($this->curlService->phphk()->get("amazon/ad/productAds/getProductAds/{$sellerId}", $condition));
        $adInfo = [];
        if ($resp && isset($resp['data']) && count($resp['data']) > 0){
            foreach ($resp['data'] as $item) {
                if ($item['sku'] == $sku && $item['campaignId'] == $campaignId && $item['adGroupId'] == $adGroupId) {
                    $adInfo = $item;
                    break;
                }
            }
        }
        return $adInfo;
    }

    public function listProductV2($sellerId,$adIds)
    {
        $condition = [
            "adIdFilter" => $adIds,
        ];
        $resp = DataUtils::getResultData($this->curlService->phphk()->get("amazon/ad/productAds/getProductAds/{$sellerId}", $condition));
        $adListInfo = [];
        if ($resp && isset($resp['data']) && count($resp['data']) > 0){
            foreach ($resp['data'] as $item) {
                $adListInfo[$item['adId']] = $item['state'];
            }
        }
        return $adListInfo;
    }


    public function enabledProduct($sellerId,$campaignId,$adGroupId,$sku)
    {
        $returnMessage = DataUtils::getResultData($this->curlService->phphk()->post("amazon/ad/productAds/postProductAds/{$sellerId}", [[
            "adGroupId" => $adGroupId,
            "campaignId" => $campaignId,
            "sku" => $sku,
            "state" => "enabled"
        ]]));
        if ($returnMessage['status'] == 'success' && count($returnMessage['data']) > 0 && $returnMessage['data'][0]['code'] == "SUCCESS") {
            //创建成功
            return $returnMessage['data'][0]['adId'];
        }else{
            $this->log("创建product失败：{$sellerId} {$sku}");
            return "";
        }
    }


    public function pausedProduct($sellerId,$pausedArr)
    {
        $returnMessage = DataUtils::getResultData($this->curlService->phphk()->put("amazon/ad/productAds/putProductAds/{$sellerId}", $pausedArr));
        $this->log(json_encode($returnMessage,JSON_UNESCAPED_UNICODE));
        $pausedAdIdResult = [];
        $adListInfo = [];
        if (count($returnMessage['data']) > 0) {
            foreach ($returnMessage['data'] as $item){
                if ($item['code'] == "SUCCESS"){
                    $adListInfo[$item['adId']] = $item['code'];
                }
            }
        }
        foreach ($pausedArr as $item){
            if (isset($adListInfo[$item['adId']]) && $adListInfo[$item['adId']] == "SUCCESS"){
                $this->log("暂停product成功：{$sellerId} {$item['adId']}");
                $pausedAdIdResult['success'][] = $item['adId'];
            }else{
                $this->log("暂停product失败：{$sellerId} {$item['adId']}");
                $pausedAdIdResult['error'][] = $item['adId'];
            }
        }

        return $pausedAdIdResult;
    }


    public function pausedProductV2($sellerId,$pausedArr)
    {
        $returnMessage = DataUtils::getResultData($this->curlService->phphk()->put("amazon/ad/productAds/putProductAds/{$sellerId}", $pausedArr));
        $this->log(json_encode($returnMessage,JSON_UNESCAPED_UNICODE));

        $adIds = array_column($pausedArr,'adId');
        $adListInfo = $this->listProductV2($sellerId, implode(",",$adIds));
        $pausedAdIdResult = [];
        foreach ($pausedArr as $item){
            if (isset($adListInfo[$item['adId']]) && $adListInfo[$item['adId']] == "paused"){
                $this->log("暂停product成功：{$sellerId} {$item['adId']}");
                $pausedAdIdResult['success'][] = $item['adId'];
            }else{
                $this->log("暂停product失败：{$sellerId} {$item['adId']}");
                $pausedAdIdResult['error'][] = $item['adId'];
            }
        }
        return $pausedAdIdResult;
    }

    //=============================Product end==============================================///


    //============================== Keyword start==============================================///
    public function getMongoKeywordInfo($sellerId,$campaignId,$adGroupId)
    {
        $list = DataUtils::getPageList($this->curlService->s3023()->get("amazon_sp_keywords/queryPage",[
            "channel" => $this->specialSellerIdConver($sellerId),
            "campaignId" => $campaignId,
            "adGroupId" => $adGroupId,
            "limit" => 500
        ]));
        $keywordMap = [];
        if ($list){
            foreach ($list as $item){
                $key = "{$item['matchType']}_{$item['keywordText']}";
                $keywordMap[$key] = $item;
            }
        }
        return $keywordMap;
    }
    public function mongoCreateKeyword($sellerId,$campaignId,$adGroupId,$kw,$ky,$keywordId,$oldAdGroupInfo)
    {
        $createMongo = [
            "channel" => $this->specialSellerIdConver($sellerId),
            "keywordId" => $keywordId,
            "status" => "2",
            "messages" => $this->messages,
            "campaignId" => $campaignId,
            "adGroupId" => $adGroupId,
            "keywordText" => $kw,
            "keywordType" => "expansion",
            "state" => "enabled",
            "matchType" => $ky,
            "bid" => $oldAdGroupInfo['defaultBid'] ?? "",
            "createdBy" => $this->messages
        ];
        return DataUtils::getResultData($this->curlService->s3023()->post("amazon_sp_keywords/", $createMongo));
    }

    public function mongoUpdateKeyword($_id,$keywordId)
    {
        $resp = $this->curlService->s3023()->post("amazon_sp_keywords/updateBiddableKeywords", [
            "id" => $_id,
            "from" => "php restful",
            "isPassNotification" => "false",
            "updateParams" => [
                "keywordId" => $keywordId,
                "modifiedBy" => $this->messages,
                "modifiedOn" => date("Y-m-d H:i:s",time())."Z",
                "status" => "2",
                "messages" => $this->messages
            ]
        ]);
        if ($resp['result'] && isset($resp['result']['keyword']) && count($resp['result']['keyword']) > 0) {
            $this->log("updateBiddableKeywords：成功：{$resp['result']['keyword']['channel']} - {$resp['result']['keyword']['keywordText']} - {$resp['result']['keyword']['matchType']}");
        }else{
            $this->log("updateBiddableKeywords：失败：{$resp['result']['keyword']['channel']} - {$resp['result']['keyword']['keywordText']} - {$resp['result']['keyword']['matchType']}");
        }
    }

    public function listKeyword($sellerId,$campaignId,$adGroupId,$matchTypeFilter = "",$keywordTextFilter ="")
    {
        $condition = [
            "campaignIdFilter" => $campaignId,
            "adGroupIdFilter" => $adGroupId,
            "stateFilter" => "enabled"
        ];
        if(!empty($matchTypeFilter)){
            $condition["matchTypeFilter"] = $matchTypeFilter;
        }
        if(!empty($keywordTextFilter)){
            $condition["keywordTextFilter"] = (string)$keywordTextFilter;
        }
        $resp = DataUtils::getResultData($this->curlService->phphk()->post("amazon/ad/keywords/getKeywordsExtended/{$sellerId}", $condition));
        $keywordMap = [];
        if ($resp && isset($resp['data']) && count($resp['data']) > 0){
            foreach ($resp['data'] as $spInfo){
                $key = "{$spInfo['matchType']}_{$spInfo['keywordText']}";
                $keywordMap[$key] = ["keywordId" => $spInfo['keywordId'],"bid" => $spInfo['bid']];
            }
        }
        return $keywordMap;
    }

    public function enabledKeyword($sellerId,$campaignId,$adGroupId,$bid,$keywords)
    {
        //搞成批量的
        $createKeywords = [];
        foreach ($keywords as $pomsKeywordInfo){
            $createKeywords[] = [
                "campaignId" => $campaignId,
                "adGroupId" => $adGroupId,
                "bid" => $bid,
                "matchType" => $pomsKeywordInfo['contentType'],
                "keywordText" => (string)$pomsKeywordInfo['content'],
                "state" => "enabled"
            ];
        }
        $ids = [];
        if (count($createKeywords) > 0){
            $resp = DataUtils::getResultData($this->curlService->phphk()->post("amazon/ad/keywords/postKeywords/{$sellerId}", $createKeywords));
            if ($resp && $resp['data']){
                $this->log("成功");
                foreach($resp['data'] as $info){
                    if ($info['code'] == "SUCCESS"){
                        $this->log("成功：{$info['keywordId']}");
                        $ids[] = $info['keywordId'];
                    }
                }
            }else{
                $this->log("失败");
            }
        }

        return $ids;
    }


    //=================================keyword end===========================================///


    public function listNegativeKeyword($sellerId,$campaignId,$adGroupId,$state)
    {
        $condition = [];
        if ($campaignId){
            $condition['campaignIdFilter'] = implode(",",$campaignId);
        }
        if ($adGroupId){
            $condition['adGroupIdFilter'] = implode(",",$adGroupId);
        }
        if ($state){
            $condition['stateFilter'] = $state;
        }
        $resp = DataUtils::getResultData($this->curlService->phphk()->get("amazon/ad/negativeKeywords/getNegativeKeywordsExtend/{$sellerId}", $condition));
        $list = [];
        if ($resp && isset($resp['data']) && count($resp['data']) > 0){
            $list = $resp['data'];
        }
        return $list;
    }

    public function updateNegativeKeyword($sellerId,$pausedArr)
    {
        $returnMessage = DataUtils::getResultData($this->curlService->phphk()->put("amazon/ad/negativeKeywords/putNegativeKeywords/{$sellerId}", $pausedArr));
        $this->log(json_encode($returnMessage,JSON_UNESCAPED_UNICODE));
        $pausedAdIdResult = [];
        $adListInfo = [];
        if (count($returnMessage['data']) > 0) {
            foreach ($returnMessage['data'] as $item){
                if ($item['code'] == "SUCCESS"){
                    $adListInfo[$item['keywordId']] = $item['code'];
                }
            }
        }
        foreach ($pausedArr as $item){
            if (isset($adListInfo[$item['keywordId']]) && $adListInfo[$item['keywordId']] == "SUCCESS"){
                $this->log("处理negativeKeyword成功：{$sellerId} {$item['keywordId']}");
                $pausedAdIdResult['success'][] = $item['keywordId'];
            }else{
                $this->log("处理negativeKeyword失败：{$sellerId} {$item['keywordId']}");
                $pausedAdIdResult['error'][] = $item['keywordId'];
            }
        }

        return $pausedAdIdResult;
    }

    public function mongoUpdateNegativeKeyword($_id,$keywordId,$state)
    {
        $resp = $this->curlService->s3023()->post("amazon_sp_negativeKeywords/updateNegativeKeywords", [
            "id" => $_id,
            "isPassNotification" => "false",
            "from" => $this->messages,
            "updateParams" => [
                "keywordId" => $keywordId,
                "state" => $state,
                "modifiedBy" => $this->messages,
                "modifiedOn" => date("Y-m-d H:i:s",time())."Z",
                "status" => "2",
                "messages" => $this->messages
            ],
        ]);
        if ($resp['result'] && isset($resp['result']['keyword']) && count($resp['result']['keyword']) > 0) {
            $this->log("updateNegativeKeyword：成功：{$resp['result']['keyword']['channel']} - {$resp['result']['keyword']['keywordText']}");
        }else{
            $this->log("updateNegativeKeyword：失败：{$resp['result']['keyword']['channel']} - {$resp['result']['keyword']['keywordText']}");
        }
    }


    public function updateNegativeTarget($sellerId,$pausedArr)
    {
        $returnMessage = DataUtils::getResultData($this->curlService->phphk()->put("amazon/ad/negativeProductTargeting/putNegativeTargets/{$sellerId}", $pausedArr));
        $this->log(json_encode($returnMessage,JSON_UNESCAPED_UNICODE));
        $pausedAdIdResult = [];
        $adListInfo = [];
        if (count($returnMessage['data']) > 0) {
            foreach ($returnMessage['data'] as $item){
                if ($item['code'] == "SUCCESS"){
                    $adListInfo[$item['targetId']] = $item['code'];
                }
            }
        }
        foreach ($pausedArr as $item){
            if (isset($adListInfo[$item['targetId']]) && $adListInfo[$item['targetId']] == "SUCCESS"){
                $this->log("处理negativeTarget成功：{$sellerId} {$item['targetId']}");
                $pausedAdIdResult['success'][] = $item['targetId'];
            }else{
                $this->log("处理negativeTarget失败：{$sellerId} {$item['targetId']}");
                $pausedAdIdResult['error'][] = $item['targetId'];
            }
        }

        return $pausedAdIdResult;
    }

    public function mongoUpdateNegativeTarget($_id,$targetId,$state)
    {
        $resp = $this->curlService->s3023()->post("amazon_sp_negative_targets/updateBiddableNegativeTargets", [
            "id" => $_id,
            "isPassNotification" => "false",
            "from" => $this->messages,
            "updateParams" => [
                "targetId" => $targetId,
                "state" => $state,
                "modifiedBy" => $this->messages,
                "modifiedOn" => date("Y-m-d H:i:s",time())."Z",
                "status" => "2",
                "remark" => $this->messages
            ],
        ]);
        if ($resp['result'] && isset($resp['result']['target']) && count($resp['result']['target']) > 0) {
            $this->log("mongoUpdateNegativeTarget：成功：{$resp['result']['target']['channel']} - {$resp['result']['target']['targetName']}");
        }else{
            $this->log("mongoUpdateNegativeTarget：失败：{$resp['result']['target']['channel']} - {$resp['result']['target']['targetName']}");
        }
    }

    public function listNegativeTarget($sellerId,$campaignId,$adGroupId,$targetIdList = "",$state = "")
    {
        $condition = [];
        if ($campaignId){
            $condition['campaignIdFilter'] = implode(",",$campaignId);
        }
        if ($adGroupId){
            $condition['adGroupIdFilter'] = implode(",",$adGroupId);
        }
        if ($state){
            $condition['stateFilter'] = $state;
        }
        if($targetIdList){
            $condition["targetIdFilter"] = $targetIdList;
        }

        $resp = DataUtils::getResultData($this->curlService->phphk()->get("amazon/ad/negativeProductTargeting/getNegativeTargets/{$sellerId}", $condition));
        $list = [];
        if ($resp && isset($resp['data']) && count($resp['data']) > 0){
            $list = $resp['data'];
        }
        return $list;
    }

    public function specialSellerIdConver($sellerId){
        return ($sellerId == 'amazon') ? 'amazon_us' : $sellerId;
    }
    public function specialSellerIdReverseConver($sellerId){
        return ($sellerId == 'amazon_us') ? 'amazon' : $sellerId;
    }


}