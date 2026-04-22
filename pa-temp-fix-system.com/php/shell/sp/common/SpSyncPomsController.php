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
        $curlService = (new CurlService())->pro();
        $redisService = new RedisService();
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
                // 可选：如果字符串开头或结尾也可能有多余空格，建议加上 trim()
                $ruleName = trim($ruleName);
                $ruleNameMap[$ruleName] = $mongoSpRuleConfig['ruleId'];
            }

            $mongoSpBudgetBidRuleList = $spApi->getMongoSpBudgetBidRuleList();
            $ruleBudgetBidNameMap = [];
            foreach ($mongoSpBudgetBidRuleList as $mongoSpRuleConfig){
                $ruleName = preg_replace('/\s+/', ' ', $mongoSpRuleConfig['ruleName']);
                // 可选：如果字符串开头或结尾也可能有多余空格，建议加上 trim()
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
                    //没有账号，要新增
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



}
$con = new SpSyncPomsController();
$con->updatePaSpSellerRules();
