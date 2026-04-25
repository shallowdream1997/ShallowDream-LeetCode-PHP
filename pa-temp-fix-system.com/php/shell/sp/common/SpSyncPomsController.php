<?php
require_once(dirname(__FILE__) . "/../../../../php/requiredfile/requiredfile.php");
require_once(dirname(__FILE__) . "/../../../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/RequestUtils.php");
require_once(dirname(__FILE__) . "/../SpApi.php");


class SpSyncPomsController
{
    private $log;

    public function __construct()
    {
        $this->log = new MyLogger("sp");
    }

    public function updatePaSpSellerRules()
    {
        $excelUtils = new ExcelUtils();
        $spApi = new SpApi();
        try {
            $contentList = $excelUtils->getXlsxData("excel/PA-amazon广告自动化配置-20260416.xlsx");
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }
        if (count($contentList) > 0) {

            $jsonString = <<<JSON
[
    {
        "skuRuleId": 1,
        "name": "S级/开发渠道",
        "condition": [
            {"field": "product_level", "value": "S级"},
            {"field": "is_dev_channel", "value": true}
        ],
        "oeNumber": 5,
        "oneOeGetTopAsinNumber": 20
    },
    {
        "skuRuleId": 2,
        "name": "S级/非开发渠道",
        "condition": [
            {"field": "product_level", "value": "S级"},
            {"field": "is_dev_channel", "value": false}
        ],
        "oeNumber": 2,
        "oneOeGetTopAsinNumber": 20
    },
    {
        "skuRuleId": 3,
        "name": "A级/开发渠道",
        "condition": [
            {"field": "product_level", "value": "A级"},
            {"field": "is_dev_channel", "value": true}
        ],
        "oeNumber": 2,
        "oneOeGetTopAsinNumber": 20
    },
    {
        "skuRuleId": 4,
        "name": "A级/非开发渠道",
        "condition": [
            {"field": "product_level", "value": "A级"},
            {"field": "is_dev_channel", "value": false}
        ],
        "oeNumber": 2,
        "oneOeGetTopAsinNumber": 20
    },
    {
        "skuRuleId": 5,
        "name": "B级/开发渠道",
        "condition": [
            {"field": "product_level", "value": "B级"},
            {"field": "is_dev_channel", "value": true}
        ],
        "oeNumber": 1,
        "oneOeGetTopAsinNumber": 20
    },
    {
        "skuRuleId": 6,
        "name": "B级/非开发渠道",
        "condition": [
            {"field": "product_level", "value": "B级"},
            {"field": "is_dev_channel", "value": false}
        ],
        "oeNumber": 1,
        "oneOeGetTopAsinNumber": 20
    }
]
JSON;

            $ruleData = json_decode($jsonString, true);
            $ruleMap = [];
            foreach ($ruleData as $rule){
                $ruleMap[$rule['name']] = $rule['skuRuleId'];
            }

            $sellerRuleList = $spApi->getMongoSellerRuleList();
            $sellerRuleMap = [];
            $sellerRuleBindRuleMap = [];
            if ($sellerRuleList){
                foreach ($sellerRuleList as $sellerRule){
                    $sellerRuleMap[$sellerRule['sellerId']] = $sellerRule;

                    foreach ($sellerRule['bindRule'] as $bindRuleItem){
                        $sellerRuleBindRuleMap[$sellerRule['sellerId']]["{$bindRuleItem['spType']}_{$bindRuleItem['skuRuleId']}"] = $bindRuleItem;
                    }
                }
            }


            $mongoSpRuleConfigList = $spApi->getMongoSpRuleConfigList();

            $ruleNameMap = [];
            foreach ($mongoSpRuleConfigList as $mongoSpRuleConfig){
                $ruleName = preg_replace('/\s+/', ' ', $mongoSpRuleConfig['ruleName']);
                $ruleName = trim($ruleName);
                $ruleNameMap[$ruleName] = $mongoSpRuleConfig['ruleId'];
            }

            $mongoSpBudgetBidRuleList = $spApi->getMongoSpBudgetBidRuleList();
            $ruleBudgetBidNameMap = [];
            foreach ($mongoSpBudgetBidRuleList as $mongoSpRuleConfig){
                $ruleName = preg_replace('/\s+/', ' ', $mongoSpRuleConfig['ruleName']);
                $ruleName = trim($ruleName);
                $ruleBudgetBidNameMap[$ruleName] = $mongoSpRuleConfig['bidRuleId'];
            }

            $spData = [];
            $ruleTypeAndId = [];
            foreach ($contentList as $content){
                $spData[$content['适用账号']] = [
                    "company" => "CR201706060001",
                    "channel" => $content['适用渠道'],
                    "sellerId" => $content['适用账号'],
                    "brand" => $content['品牌'],
                    "isIndependenceSeller" => 1,
                    "asinNumberLimit" => 0,
                    "modifiedBy" => "system(zhouangang)",
                    "createdBy" => "system(zhouangang)",
                    "oeNumberRule" => [],
                    "bindRule" => []
                ];
                $spType = $content['广告类型'];
                if ($spType == "manual keyword"){
                    $spType = "manual";
                }else if ($spType == "category"){
                    $spType = "manual category";
                }

                if (!isset($ruleNameMap[$content['系统创建campaign广告']])){
                    $this->log->log2("系统创建campaign广告不存在：{$content['系统创建campaign广告']}");
                    continue;
                }
                $ruleId = $ruleNameMap[$content['系统创建campaign广告']];

                if ($content['adgroup广告规则']){
                    if (!isset($ruleNameMap[$content['adgroup广告规则']])){
                        $this->log->log2("adgroup广告规则不存在：{$content['adgroup广告规则']}");
                    }
                    $agroupRuleId = $ruleNameMap[$content['adgroup广告规则']];
                    $ruleTypeAndId[$content['适用账号']]["{$spType}_{$ruleMap[$content['开发渠道']]}"]["adGroupRule"] = $agroupRuleId;
                }

                if ($content['人工创建campaign广告']){
                    if (!isset($ruleNameMap[$content['人工创建campaign广告']])){
                        $this->log->log2("人工创建campaign广告不存在：{$content['人工创建campaign广告']}");
                    }
                    $campaignRuleByManualRuleId = $ruleNameMap[$content['人工创建campaign广告']];
                    $ruleTypeAndId[$content['适用账号']]["{$spType}_{$ruleMap[$content['开发渠道']]}"]["campaignRuleByManual"] = $campaignRuleByManualRuleId;
                }

                $bidRuleId = "";
                if (!empty($content['bid规则(此列留空的不作上传)'])){
                    if (!isset($ruleBudgetBidNameMap[$content['bid规则(此列留空的不作上传)']])){
                        $this->log->log2("bid规则不存在：{$content['bid规则(此列留空的不作上传)']}");
                        continue;
                    }
                    $bidRuleId = $ruleBudgetBidNameMap[$content['bid规则(此列留空的不作上传)']];
                }

                $ruleTypeAndId[$content['适用账号']]["{$spType}_{$ruleMap[$content['开发渠道']]}"]["campaignRuleBySystem"] = $ruleId;
                $ruleTypeAndId[$content['适用账号']]["{$spType}_{$ruleMap[$content['开发渠道']]}"]["bidRule"] = $bidRuleId;

            }

            foreach ($spData as $sellerId => $spInfo){
                if(isset($sellerRuleMap[$sellerId])){
                    $mongosellerRule = $sellerRuleMap[$sellerId];
                    $mongosellerRule['modifiedBy'] = "system(zhouangang)";

                    foreach ($mongosellerRule['bindRule'] as &$bindRuleItem){
                        $key = "{$bindRuleItem['spType']}_{$bindRuleItem['skuRuleId']}";

                        foreach ($bindRuleItem['ruleTypeAndId'] as &$ruleTypeAndIdItem){
                            if ($ruleTypeAndIdItem['ruleType'] == "campaignRuleBySystem"){
                                $ruleTypeAndIdItem['ruleId'] = $ruleTypeAndId[$sellerId][$key]['campaignRuleBySystem'];
                            }
                            if ($ruleTypeAndIdItem['ruleType'] == "campaignRuleByManual" && isset($ruleTypeAndId[$sellerId][$key]['campaignRuleByManual']) && !empty($ruleTypeAndId[$sellerId][$key]['campaignRuleByManual'])){
                                $ruleTypeAndIdItem['ruleId'] = $ruleTypeAndId[$sellerId][$key]['campaignRuleByManual'];
                            }
                            if ($ruleTypeAndIdItem['ruleType'] == "adGroupRule" && isset($ruleTypeAndId[$sellerId][$key]['adGroupRule']) && !empty($ruleTypeAndId[$sellerId][$key]['adGroupRule'])){
                                $ruleTypeAndIdItem['ruleId'] = $ruleTypeAndId[$sellerId][$key]['adGroupRule'];
                            }
                            if ($ruleTypeAndIdItem['ruleType'] == "bidRule"){
                                $ruleTypeAndIdItem['ruleId'] = $ruleTypeAndId[$sellerId][$key]['bidRule'];
                            }
                        }

                        if (empty($ruleTypeAndId[$sellerId][$key]['bidRule'])){
                            $bindRuleItem['status'] = 0;
                        }else if (!empty($ruleTypeAndId[$sellerId][$key]['bidRule']) && !empty($ruleTypeAndId[$sellerId][$key]['campaignRuleBySystem'])){
                            $bindRuleItem['status'] = 1;
                        }
                    }

                    $this->log->log2("修改后：" . json_encode($mongosellerRule));
                    $spApi->updateMongoSellerRule($mongosellerRule);
                }else{
                    $mongosellerRule = $spData[$sellerId];
                    $mongosellerRule['createdBy'] = "system(zhouangang)";
                    $mongosellerRule['modifiedBy'] = "system(zhouangang)";

                    $oeNumberRule = [];
                    $bindRule = [];
                    foreach ($ruleData as $ruleTypeAndIdValue){
                        foreach (["auto","manual","manual asin","manual category"] as $sptype){
                            $oeNumberRule[] = [
                                "skuRuleId" => $ruleTypeAndIdValue['skuRuleId'],
                                "spType" => $sptype,
                                "oeNumber" => $ruleTypeAndIdValue['oeNumber'],
                                "oneOeGetTopAsinNumber" => $ruleTypeAndIdValue['oneOeGetTopAsinNumber'],
                            ];

                            $bidRuleData = [
                                "spType" => $sptype,
                                "status" => 0,
                                "skuRuleId" => $ruleTypeAndIdValue['skuRuleId'],
                                "skuRuleName" => $ruleTypeAndIdValue['name'],
                                "ruleTypeAndId" => []
                            ];

                            $key = "{$sptype}_{$ruleTypeAndIdValue['skuRuleId']}";

                            $ruleTypeAndIdList = [];
                            foreach (["campaignRuleBySystem","campaignRuleByManual","adGroupRule","bidRule"] as $item){
                                $ruleTypeAndIdItem = [
                                    "ruleType" => $item,
                                    "ruleId" => ""
                                ];
                                if ($item == "campaignRuleBySystem"){
                                    $ruleTypeAndIdItem['ruleId'] = $ruleTypeAndId[$sellerId][$key]['campaignRuleBySystem'];
                                } elseif ($item == "bidRule"){
                                    $ruleTypeAndIdItem['ruleId'] = $ruleTypeAndId[$sellerId][$key]['bidRule'];
                                } elseif ($item == "campaignRuleByManual"){
                                    $ruleTypeAndIdItem['ruleId'] = isset($ruleTypeAndId[$sellerId][$key]['campaignRuleByManual']) && !empty($ruleTypeAndId[$sellerId][$key]['campaignRuleByManual']) ? $ruleTypeAndId[$sellerId][$key]['campaignRuleByManual'] : "";
                                } elseif ($item == "adGroupRule"){
                                    $ruleTypeAndIdItem['ruleId'] = isset($ruleTypeAndId[$sellerId][$key]['adGroupRule']) && !empty($ruleTypeAndId[$sellerId][$key]['adGroupRule']) ? $ruleTypeAndId[$sellerId][$key]['adGroupRule'] : "";
                                }
                                $ruleTypeAndIdList[] = $ruleTypeAndIdItem;
                            }

                            if (empty($ruleTypeAndId[$sellerId][$key]['bidRule'])){
                                $bidRuleData['status'] = 0;
                            }else if (!empty($ruleTypeAndId[$sellerId][$key]['bidRule']) && !empty($ruleTypeAndId[$sellerId][$key]['campaignRuleBySystem'])){
                                $bidRuleData['status'] = 1;
                            }

                            $bidRuleData['ruleTypeAndId'] = $ruleTypeAndIdList;
                            $bindRule[] = $bidRuleData;
                        }
                    }

                    $mongosellerRule['oeNumberRule'] = $oeNumberRule;
                    $mongosellerRule['bindRule'] = $bindRule;

                    $this->log->log2("新增：" . json_encode($mongosellerRule));
                    $spApi->createMongoSellerRule($mongosellerRule);
                }
            }
        }
    }

    /**
     * 简化版本 - 直接用 seller_id + keywordText 批量查询 keywords
     */
    public function sssss()
    {
        $excelUtils = new ExcelUtils();
        $spApi = new SpApi();
        try {
            $contentList = $excelUtils->getXlsxData("excel/产品清单车型库清单0425.xlsx");
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }
        if (count($contentList) > 0) {
            $exportList = [];
            
            // ========== 第一步：收集 pid-scu-maps 查询参数 ==========
            $this->log->log2("收集 pid-scu-maps 参数...");
            $channelProductIdsMap = [];
            
            foreach ($contentList as $content) {
                if ($content) {
                    $productId = $content['product_id'];
                    $channel = $content['channel'];
                    
                    if (!isset($channelProductIdsMap[$channel])) {
                        $channelProductIdsMap[$channel] = [];
                    }
                    $channelProductIdsMap[$channel][] = $productId;
                }
            }
            
            // ========== 第二步：批量查询 pid-scu-maps ==========
            $this->log->log2("批量查询 pid-scu-maps...");
            $nonFbaInfoMap = [];
            
            foreach ($channelProductIdsMap as $channel => $productIds) {
                $batchMap = $spApi->batchPidScuMapAdGroupFindScuId($channel, $productIds);
                foreach ($batchMap as $pid => $info) {
                    $nonFbaInfoMap[$pid] = $info;
                }
            }
            $this->log->log2("pid-scu-maps 查询完成，映射数量: " . count($nonFbaInfoMap));
            
            // ========== 第三步：收集 keyword 查询参数（seller_id + keywordText） ==========
            $this->log->log2("收集 keyword 查询参数...");
            $sellerKeywordTextsMap = [];
            
            foreach ($contentList as $content) {
                if ($content) {
                    $sellerId = $content['seller_id'];
                    $ss = $content['ss'];
                    
                    if ($ss) {
                        $keywordTexts = explode("\n", $ss);
                        foreach ($keywordTexts as $keywordText) {
                            $keywordText = trim($keywordText);
                            if ($keywordText) {
                                if (!isset($sellerKeywordTextsMap[$sellerId])) {
                                    $sellerKeywordTextsMap[$sellerId] = [];
                                }
                                $sellerKeywordTextsMap[$sellerId][] = $keywordText;
                            }
                        }
                    }
                }
            }
            
            // ========== 第四步：批量查询 keywords ==========
            $this->log->log2("批量查询 keywords...");
            $keywordListMap = [];
            
            foreach ($sellerKeywordTextsMap as $sellerId => $keywordTexts) {
                $batchMap = $spApi->batchGetMongoKeywordByKeywordText($sellerId, array_unique($keywordTexts));
                if (!isset($keywordListMap[$sellerId])) {
                    $keywordListMap[$sellerId] = [];
                }
                foreach ($batchMap as $keywordText => $list) {
                    $keywordListMap[$sellerId][$keywordText] = $list;
                }
            }
            $this->log->log2("keywords 查询完成");
            
            // ========== 第五步：遍历处理，从映射中取数据生成结果 ==========
            $this->log->log2("开始生成结果数据...");
            
            foreach ($contentList as $content) {
                if ($content) {
                    $productId = $content['product_id'];
                    $channel = $content['channel'];
                    $sellerId = $content['seller_id'];
                    
                    // 获取 adGroupName（从 nonFbaInfo）
                    $adGroupName = "";
                    if (isset($nonFbaInfoMap[$productId])) {
                        $adGroupName = $nonFbaInfoMap[$productId]['scuId'];
                    }
                    
                    // 解析 ss 字段中的 keywordText
                    $keywordTexts = explode("\n", $content['ss']);
                    $keywordTexts = array_map('trim', $keywordTexts);
                    $keywordTexts = array_filter($keywordTexts);
                    
                    // 从映射取 keyword 数据
                    foreach ($keywordTexts as $keywordText) {
                        if ($keywordText && isset($keywordListMap[$sellerId][$keywordText])) {
                            $keywordList = $keywordListMap[$sellerId][$keywordText];
                            foreach ($keywordList as $keyword) {
                                if ($keyword['keywordId']) {
                                    $exportList[] = [
                                        "product_id" => $productId,
                                        "channel" => $channel,
                                        "seller_id" => $sellerId,
                                        "adGroupName" => $adGroupName,
                                        "adGroupId" => "'{$keyword['adGroupId']}",
                                        "keywordText" => $keyword['keywordText'],
                                        "keywordId" => "'{$keyword['keywordId']}",
                                        "keywordMatchType" => $keyword['matchType'],
                                        "keywordBid" => $keyword['bid']
                                    ];
                                }
                            }
                        }
                    }
                }
            }
            
            $this->log->log2("结果生成完成，总数: " . count($exportList));

            if ($exportList) {
                $excelUtils = new ExcelUtils("sp/");
                $filePath = $excelUtils->downloadXlsx([
                    "product_id",
                    "channel",
                    "seller_id",
                    "adGroupName",
                    "adGroupId",
                    "keywordText",
                    "keywordId",
                    "keywordMatchType",
                    "keywordBid"
                ], $exportList, "仅投放了hot_fitment的keyword_" . date("YmdHis") . ".xlsx");
            }
        }
    }
}

$con = new SpSyncPomsController();
//$con->updatePaSpSellerRules();
$con->sssss();