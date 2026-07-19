<?php
/**
 * 广告+寄卖同步
 * 从 SyncCurlController 拆分
 * Class AdSync
 */

require_once dirname(__FILE__) . '/../../../php/bootstrap.php';

class AdSync extends CrudService
{
    public function __construct()
    {
        parent::__construct("sync/adsync");
    }

    public function getAmazonSpKeyword()
        {
    //        $curlService = new CurlService();
    //        $list = [];
    //        for ($page = 1; $page < 500; $page++) {
    //            $this->log("{$page}");
    //            $resp = DataUtils::getPageDocList($curlService->pro()->s3044()->get("pa_sku_materials/queryPage", [
    //                "limit" => 5000,
    //                "createdOn_gt" => "2023-01-01",
    //                "page" => $page
    //            ]));
    //            if ($resp) {
    //                foreach ($resp as $info) {
    //                    if (isset($info['fitment']) && !empty($info['fitment'])) {
    //                        foreach ($info['fitment'] as $keyword) {
    //                            $list[] = [
    //                                "skuId" => $info['skuId'],
    //                                "model" => $keyword['model']
    //                            ];
    //                        }
    //                    }
    //                }
    //            } else {
    //                break;
    //            }
    //        }
    //
    //        if (count($list) > 0){
    ////            $this->log(count($models));
    ////            foreach ($models as $info){
    ////
    ////            }
    ////
    ////            $curlService->pro()->s3023()->get("amazon_sp_keywords/queryPage",[
    ////                "keywordText_in" => implode(",",$models)
    ////            ]);
    //            $excelUtils = new ExcelUtils();
    //            $filePath = $excelUtils->downloadXlsx(["skuId","model"],$list,"sku资料呈现热销词_".date("YmdHis").".xlsx");
    //            $this->log($filePath);
    //        }

            $curlService = new CurlService();

            $fileContent = (new ExcelUtils())->getXlsxData("../export/uploads/default/sku资料呈现热销词_20250429223726.xlsx");

            $curlService = new CurlService();
            $curlService = $curlService->pro();

            if (sizeof($fileContent) > 0) {
                $keywordTexts = [];
                foreach ($fileContent as $info) {
                    if ($info['model']) {
                        $keywordTexts[] = $info['model'];
                    }
                }
                $keywordInfoList = [];
                foreach (array_chunk($keywordTexts, 300) as $chunk) {
                    $list = DataUtils::getPageList($curlService->pro()->s3023()->get("amazon_sp_keywords/queryPage", [
                        "keywordText_in" => implode(",", $chunk),
                        "columns" => "channel,keywordId,campaignId,adGroupId,state,keywordText,matchType,bid,createdOn",
                        "createdBy" => "php_restful_commonPaNewCreateKeywordsByType",
                        "state" => "enabled",
                        "limit" => 10000
                    ]));
                    if (count($list) > 0) {
                        foreach ($list as $info) {
                            $keywordInfoList[] = [
    //                            "_id" => $info['_id'],
    //                            "messages" => $info['messages'],
                                "channel" => $info['channel'],
                                "keywordId" => "'{$info['keywordId']}",
                                "campaignId" => "'{$info['campaignId']}",
                                "adGroupId" => "'{$info['adGroupId']}",
                                "state" => $info['state'],
                                "keywordText" => $info['keywordText'],
                                "matchType" => $info['matchType'],
                                "bid" => $info['bid'],
                                "createdOn" => $info['createdOn']
                            ];
                        }

                    }
                }


                if (count($keywordInfoList) > 0) {
                    foreach (array_chunk($keywordInfoList, 15000) as $chunk) {
                        $excelUtils = new ExcelUtils();
                        $filePath = $excelUtils->downloadXlsx([
    //                        "_id",
    //                        "messages",
                            "channel",
                            "keywordId",
                            "campaignId",
                            "adGroupId",
                            "state",
                            "keywordText",
                            "matchType",
                            "bid",
                            "createdOn",
                        ], $chunk, "新热词keyword投放_" . date("YmdHis") . ".xlsx");
                    }

                }


            }


        }

    public function findCampaign()
        {
            $curlService = (new CurlService())->pro();

            $productInfo = DataUtils::getPageList($curlService->s3023()->get("amazon_sp_sellers/queryPage", [
                "company_in" => "CR201706060001",
                "limit" => 500
            ]));
            if ($productInfo) {
                $export = [];
                $sameCampaign = [];
                $manualAllZero = [];
                foreach ($productInfo as &$info) {
                    if (isset($info['bindRule']) && count($info['bindRule']) > 0) {
                        $update = false;
                        foreach ($info['bindRule'] as &$bind) {
                            if ($bind['status'] == 0) {

                                $targetingType = $bind['spType'];
                                if (strpos($bind['spType'], "manual") === 0) {
                                    $targetingType = "manual";
                                    $this->log("{$info['sellerId']} - {$bind['spType']} - {$info['createdOn']} - {$info['modifiedOn']}");
                                    //$
                                    $manualAllZero[$info['sellerId']][$bind['spType']] = $bind['status'];

                                }
    //                                $list = DataUtils::getPageList($curlService->s3023()->get("amazon_sp_campaigns/queryPage",[
    //                                    "company" => "CR201706060001",
    //                                    "channel" => $info['sellerId'],
    //                                    "targetingType" => $targetingType,
    //                                    "createdOn_gte" => $info['createdOn'],
    //                                    "limit" => 2000
    //                                ]));
    //                                if (count($list) > 0){
    //                                    foreach ($list as $info){
    //                                        if (!isset($sameCampaign[$info['campaignName']])){
    //                                            $export[] = [
    ////                                                "_id" => $info['_id'],
    //                                                "status" => $info['status'],
    //                                                "channel" => $info['channel'],
    //                                                "campaignName" => $info['campaignName'],
    //                                                "campaignId" => $info['campaignId'],
    //                                                "targetingType" => $info['targetingType'],
    //                                                "state" => $info['state'],
    //                                                "createdOn" => $info['createdOn'],
    //                                            ];
    //                                            $sameCampaign[$info['campaignName']] = 1;
    //                                            $this->log("唉有投放，错误的");
    //                                        }
    //                                    }
    //                                }


                            }
                        }
                    }
                }
    //            if (count($export) > 0){
    //                $excelUtils = new ExcelUtils();
    //                $downloadOssLink = "未设置广告已经投放的_" . date("YmdHis") . ".xlsx";
    //                $filePath = $excelUtils->downloadXlsx(["status","channel","campaignName","campaignId","targetingType","state","createdOn"],$export,$downloadOssLink);
    //                $this->log($filePath);
    //            }

                $this->log(json_encode($manualAllZero, JSON_UNESCAPED_UNICODE));


            }

        }

    public function deleteCampaign()
        {
            $list = $this->commonFindByParams("s3023", "amazon_sp_campaigns", [
                "status" => "3",
                "company" => "CR201706060001",
                "state" => "enabled",
                "limit" => 2000
            ], "pro");
            $needDeleteList = [];
            if (count($list) > 0) {
                foreach ($list as $item) {
                    if (empty($item['campaignId'])) {
                        $this->log("campaignId 为空,删除");
                        $needDeleteList[] = $item['_id'];
                        //$this->commonDelete("s3023","amazon_sp_campaigns",$item['_id'],"pro");
                    }
                }
            }
            if (count($needDeleteList) > 0) {
                foreach ($needDeleteList as $_id) {
                    $this->commonDelete("s3023", "amazon_sp_campaigns", $_id, "pro");
                }
                (new RequestUtils("test"))->dingTalk("删除重复创建campaign结束");
            } else {
                (new RequestUtils("test"))->dingTalk("没有重复创建campaign可删除");
            }

        }

    public function updatePaProductTempSkuIdNew()
        {
            //切换环境，test - 测试。pro - 生产
            $env = "pro";

            //todo 先修复空T号 以及 T号为数字，字母等无正确正则匹配的T号，重新生成T号
    //        $page = 1;
    //        do{
    //            $teRes = $this->commonFindByParams("s3015", "pa_product_details", array(
    //                "limit" => 1000,
    //                "page" => $page
    //            ), $env);
    //            if(count($teRes) > 0){
    //                foreach ($teRes as &$info){
    //                    if (!isset($info['tempSkuId']) || (isset($info['tempSkuId']) && empty($info['tempSkuId'])) || (strpos($info['tempSkuId'], 'T') !== 0)){
    //                        $newTempSkuId = $this->getTempSkuIdByRedis();
    //                        $info['tempSkuId'] = $newTempSkuId;
    //                        $info['modifiedBy'] = "T号修复(zhouangang)";
    //
    //                        $up = $this->commonUpdate("s3015","pa_product_details",$info,$env);
    //                        if ($up) {
    //                            $this->log("没有T号，生成新T号：{$newTempSkuId}");
    //                        }
    //                    }else{
    //                        $this->log("有T号：{$info['tempSkuId']}");
    //                    }
    //                }
    //                $page++;
    //                $this->log("第 {$page} 页");
    //            }else{
    //                break;
    //            }
    //        }while(true);


            //todo 再使用mongo语句，去arc-sql 那里: https://sre-sql.ux168.cn/sqlquery/
            // 用以下查询条件，查询重复T号的数据，导出来，转xslx，放在export目录下,修改里面的内容（可能totalCount左边的列都得删除，不然会读空），修改sheet名称为Sheet1
            /*
             *
              db.pa_product_detail.aggregate([
                  {
                    $group: {
                      _id: {tempSkuId:"$tempSkuId"},
                      count: { $sum: 1 }
                    }
                  },
                  {
                    $match:{count:{$gt:1}}
                },
                  {
                    $project: {
                      _id: 0,
                      tempSkuId:"$_id.tempSkuId",
                      totalCount: "$count"
                    }
                  }
                ])
             */
            //$fileContent = (new ExcelUtils())->getXlsxData("../export/重复T号test.xlsx");

            $fileContent = [
                [
                    "tempSkuId" => "T241223605620ux001"
                ]
            ];
            if (sizeof($fileContent) > 0) {
                foreach ($fileContent as $info) {
                    $oldTMapNewT = array();
                    $this->log("旧T号：{$info['tempSkuId']}");
                    $teRes = $this->commonFindByParams("s3015", "pa_product_details", array(
                        "orderBy" => "-_id",
                        "tempSkuId" => $info['tempSkuId'],
                        "limit" => 1000
                    ), $env);
                    //
                    if (count($teRes) > 0) {
                        //暴力一点吧全部重写
                        foreach ($teRes as $tinfo) {
                            $dteRes = $this->commonFindByParams("s3015", "pa_product_details", array(
                                "orderBy" => "_id",
                                "productName" => $tinfo['productName'],
                                "limit" => 5
                            ), $env);

                            //用productName来找到以前的T号
                            $oldTempSkuId = "";
                            if (count($dteRes) > 0) {
                                foreach ($dteRes as $dteInfo) {
                                    if ($dteInfo['tempSkuId'] != $info['tempSkuId']) {
                                        $oldTempSkuId = $dteInfo['tempSkuId'];
                                        break;
                                    }
                                }
                            }
                            //如果有旧T号
                            if (!empty($oldTempSkuId)) {
                                //用旧T号查到技术维度
                                $paSkuAttributeInfo = $this->commonFindOneByParams("s3015", "pa_sku_attributes", array(
                                    "tmepSkuId" => $oldTempSkuId,
                                ), $env);

                                if ($paSkuAttributeInfo) {
                                    //新的T号
                                    $uniqueId = $this->getTempSkuIdByRedis();
                                    //替换成新的
                                    $paSkuAttributeInfo['tempSkuId'] = $uniqueId;
                                    //$paSkuAttributeInfo['skuId'] = $uniqueId;
                                    $rs = $this->commonUpdate("s3015", "pa_sku_attributes", $paSkuAttributeInfo, $env);
                                    if ($rs) {
                                        $this->log("存在技术维度：{$oldTempSkuId}，已更改为：{$uniqueId}");
                                        $tinfo['tempSkuId'] = $uniqueId;
                                        $rss = $this->commonUpdate("s3015", "pa_product_details", $tinfo, $env);
                                    }
                                } else {
                                    $this->log("没有技术维度：{$oldTempSkuId}，直接更新T号");
                                    //没有技术维度直接
                                    //新的T号
                                    $uniqueId = $this->getTempSkuIdByRedis();
                                    $tinfo['tempSkuId'] = $uniqueId;
                                    $rss = $this->commonUpdate("s3015", "pa_product_details", $tinfo, $env);

                                }

                            } else {
                                $this->log("{$tinfo['tempSkuId']} 查不到旧名称：{$tinfo['productName']}");
                                //新的T号
                                $uniqueId = $this->getTempSkuIdByRedis();
                                $tinfo['tempSkuId'] = $uniqueId;
                                $rss = $this->commonUpdate("s3015", "pa_product_details", $tinfo, $env);
                            }

                        }

                    }

                }


            }


        }

        //生成T号

    public function getTempSkuIdByRedis()
        {
            // 获取当前年月日
            $currentDate = date('ymd');
            // 构建编号的前缀
            $prefix = "T{$currentDate}ux";
            // 初始化计数器，如果不存在则设置为0
            $counterKey = "counter_{$currentDate}";
            $counter = $this->redis->incr($counterKey);
            // 如果计数器为0，说明是第一次使用，设置过期时间为1天
            if ($counter == 1) {
                $this->redis->expire($counterKey, 86400); // 86400秒 = 1天
            }
            // 格式化计数器为5位数
            $counterFormatted = str_pad($counter, 5, '0', STR_PAD_LEFT);
            // 生成唯一编号
            $uniqueId = $prefix . $counterFormatted;
            return $uniqueId;
        }

    public function consignmentQD($params)
        {
            $curlService = new CurlService();
            $curlService = $curlService->gateway();
            $env = $curlService->environment;
            $params['qdList'] = [
                "QD202510270009",
                "QD202511060015",
                "QD202511060013",
                "QD202511060012",
                "QD202511060006"
            ];

            $curlService->getModule("pa");
            $createResp = DataUtils::getResultData($curlService->getWayPost($curlService->module . "/scms/consignment/workflow/v1/autoHandleWaitAssign", $params['qdList']));
            if ($createResp) {

                $this->log(json_encode($createResp, JSON_UNESCAPED_UNICODE));
            }


        }

    public function fallBackQD()
        {

            $fileFitContent = (new ExcelUtils())->getXlsxData("../export/要改的单.xlsx");
            if (sizeof($fileFitContent) > 0) {

                $list = [];
                foreach ($fileFitContent as $item) {
                    if (!empty($item['产品序号'])) {
                        $list[] = $item['产品序号'];
                    }
                }
    //            $curlSsl = (new CurlService())->pro()->gateway()->getModule("pa");
    //            $getKeyResp = DataUtils::getNewResultData($curlSsl->getWayPost($curlSsl->module . "/ppms/product_dev/sku/v2/findListWithAttr", [
    //                "skuIdList" => $list,
    //                "attrCodeList" => [
    //                    "custom-skuInfo-supplierId",
    //                    "custom-skuInfo-factoryId",
    //                    "custom-skuInfo-supplierType",
    //                    "custom-prePurchase-prePurchaseMainId",
    //                    "custom-prePurchase-prePurchaseBillNo"
    //                ]
    //            ]));
    //            $map  =[];
    //            if ($getKeyResp){
    //                foreach ($getKeyResp as $item){
    //                    $map[$item['custom-skuInfo-skuId']] = $item;
    //                }
    //            }

                $fix30List = [];

                foreach ($fileFitContent as $item) {

                    $fix30List[] = [
                        "tempSkuId" => $item['产品序号'],
                        "skuAttrList" => [
                            [
                                "name" => "custom-skuInfo-supplierId",
                                "value" => 0
                            ],
                            [
                                "name" => "custom-skuInfo-factoryId",
                                "value" => 0
                            ],
                            [
                                "name" => "custom-skuInfo-supplierType",
                                "value" => "consignment"
                            ],
                            [
                                "name" => "custom-prePurchase-prePurchaseMainId",
                                "value" => "1988592639574650880"
                            ],
                            [
                                "name" => "custom-prePurchase-prePurchaseBillNo",
                                "value" => "QD202511120017"
                            ],
                            [
                                "name" => "custom-common-qdBillNo",
                                "value" => "QD202511120017"
                            ]
                        ]
                    ];

                }

                if ($fix30List) {
                    foreach (array_chunk($fix30List, 200) as $chunkFix30List) {
                        $curlSsll = (new CurlService())->pro()->gateway()->getModule("pa");
                        $getKeyResp = DataUtils::getNewResultData($curlSsll->getWayPost($curlSsll->module . "/ppms/product_dev/sku/v1/directUpdateBatch", [
                            "operator" => "zhouangang",
                            "skuList" => $chunkFix30List
                        ]));
                    }
                }


            }


        }

    public function fallBack30()
        {


            $fileFitContent = (new ExcelUtils())->getXlsxData("../export/uploads/default/g号修复的被更新的_20250826180811.xlsx");
            if (sizeof($fileFitContent) > 0) {

                $list = [];
                foreach ($fileFitContent as $item) {
                    if (!empty($item['sku_id'] && $item['有分组的'] == '有')) {
                        $list[] = $item['sku_id'];
                    }
                }
                $curlSsl = (new CurlService())->pro()->gateway()->getModule("pa");
                $getKeyResp = DataUtils::getNewResultData($curlSsl->getWayPost($curlSsl->module . "/ppms/product_dev/sku/v2/findListWithAttr", [
                    "skuIdList" => $list,
                    "attrCodeList" => [
                        "custom-skuInfo-skuId",
                        "custom-common-batchNo",
                        "custom-sguInfo-sguId",
                        "custom-sguInfo-groupTag",
                        "custom-sguInfo-channel",
                        "custom-skuInfo-tempSkuId"
                    ]
                ]));
                $map = [];
                if ($getKeyResp) {
                    foreach ($getKeyResp as $item) {
                        $map[$item['custom-skuInfo-skuId']] = $item;
                    }
                }

                $fix30List = [];

                foreach ($fileFitContent as $item) {
                    if (!empty($item['sku_id'] && $item['有分组的'] == '有')) {
                        if (!isset($map[$item['sku_id']])) {
                            continue;
                        }
                        $tempSkuId = $map[$item['sku_id']]['custom-skuInfo-tempSkuId'];
                        $sguId = $item['旧G号'];

                        $curlService = (new CurlService())->pro();
                        $sguInfo = DataUtils::getQueryListInFirstDataV3($curlService->s3015()->get("/sgu-sku-scu-maps/query", [
                            "skuScuId" => $item['sku_id'],
                            "limit" => 1,
                        ]));
                        if ($sguInfo) {
                            $sguInfo['sguId'] = $sguId;
                            $sguInfo['modifiedBy'] = "system(zhouangang)";
                            $curlService->s3015()->put("sgu-sku-scu-maps/{$sguInfo['_id']}", $sguInfo);
                        }


                        $fix30List[] = [
                            "tempSkuId" => $tempSkuId,
                            "skuAttrList" => [
                                [
                                    "name" => "custom-sguInfo-sguId",
                                    "value" => $sguId
                                ]
                            ]
                        ];


                    }
                }


    //            if ($fix30List){
    //                foreach (array_chunk($fix30List,200) as $chunkFix30List){
    //                    $curlSsll = (new CurlService())->pro()->gateway()->getModule("pa");
    //                    $getKeyResp = DataUtils::getNewResultData($curlSsll->getWayPost($curlSsll->module . "/ppms/product_dev/sku/v1/directUpdateBatch", [
    //                        "operator" => "zhouangang",
    //                        "skuList" => $chunkFix30List
    //                    ]));
    //                }
    //            }


            }


        }

    public function initQdActionLog()
        {
            $curlPaService = (new CurlService())->pro()->getModule("pa")->gateway();
            $curlLogService = (new CurlService())->pro()->getModule('ux168log')->gateway();

            $list = [];

            $fileFitContent = (new ExcelUtils())->getXlsxData("../export/qd/迁移数据.xlsx");
            if (sizeof($fileFitContent) > 0) {
                $qdBillNoList = array_column($fileFitContent, 'qd_bill_no');
                if (count($qdBillNoList) == 0) {
                    $this->log("没有迁移数据");
                    return;
                }

                foreach (array_chunk($qdBillNoList, 200) as $qdBillNoChunkList) {
                    $qdlist = DataUtils::getNewResultData($curlPaService->getWayPost($curlPaService->module . "/scms/consignmentqdlist/v1/qdPageList", [
                        "pageNum" => 1,
                        "pageSize" => 200,
                        "qdBillNoList" => $qdBillNoChunkList,
                    ]));
                    if ($qdlist && isset($qdlist['list']) && $qdlist['list'] && count($qdlist['list']) > 0) {
                        $list = array_merge($list, array_column($qdlist['list'], 'consignmentQdId'));
                    }
                }
            }

            if ($list) {
                //这里可以读表搞一个映射出来


                foreach ($list as $qdId) {
                    //记录日志步骤
                    //1. 首次发布; 读取第一个publish_record_id 的数据; 但是要根据寄卖商类型，达标供应商(自动发布的) 和 货源供应商(指定发布)
                    //操作了清单发布
                    $log = [];
                    $detailResp = DataUtils::getNewResultData($curlPaService->getWayFormDataPost($curlPaService->module . "/scms/consignmentqdlist/v1/getQdDetail", [
                        "consignmentQdId" => $qdId
                    ]));


                    if ($detailResp) {

                        $logListResp = DataUtils::getNewResultData($curlLogService->getWayPost($curlLogService->module . "/log/v1/query", [
                            "page" => [
                                "pageNum" => 1,
                                "pageSize" => 100,
                            ],
                            "condition" => [
                                "opId" => $qdId,
                                "logSource" => "pa-scms-service",
                                "logType" => "consignment_qd_action"
                            ]
                        ]));
                        $logActionList = [];
                        $existPeoplePublishMap = [];
                        if ($logListResp && isset($logListResp['list']) && $logListResp['list'] && count($logListResp['list']) > 0) {
                            //$this->log("有日志：{$info['_id']}");
                            $logActionList = DataUtils::parseAndTransformQdLogList($logListResp['list']);
                            foreach ($logActionList as &$item) {
                                $item['consignmentQdId'] = $qdId;
                                $item['qdBillNo'] = $detailResp['qdBillNo'];
                                if ($item['action'] == "清单发布") {
                                    $existPeoplePublishMap[$item['afterConsignmentQdPublishRecordId']] = 1;
                                }
                            }
                        }


                        if ($detailResp['consignmentPublishRecordDetailBOList']) {
                            $publishCountMap = [];
                            foreach ($detailResp['consignmentPublishRecordDetailBOList'] as $index => $detail) {
                                $publishCountMap[$index] = $detail;
                                if ($index == 0) {
                                    $log[] = [
                                        "consignmentQdId" => $qdId,
                                        "qdBillNo" => $detailResp['qdBillNo'],
                                        "beforeConsignmentQdPublishRecordId" => null,
                                        "afterConsignmentQdPublishRecordId" => $detail['consignmentQdPublishRecordId'],
                                        "beforeBidBillNo" => null,
                                        "afterBidBillNo" => $detail['bidBillNo'],
                                        "beforeGroupId" => null,
                                        "beforeSupplierId" => null,
                                        "afterGroupId" => null,
                                        "afterSupplierId" => null,
                                        "action" => "首次发布",
                                        "remark" => $detail['createBy'] === 'ConsignmentWorkFlow' ? "清单自动发布" : "操作了清单发布",
                                        "createTime" => $detail['createTime'],
                                        "createBy" => $detail['createBy'],
                                    ];
                                } else {
                                    //>0 有多个清单轮次，这个轮次可以
                                    //拿到上一个轮次的数据
                                    if (isset($existPeoplePublishMap[$detail['consignmentQdPublishRecordId']])) {
                                        //有人工发布的就不需要认为是重新发布

                                    } else {
                                        $beforeDetail = $publishCountMap[$index - 1];
                                        $log[] = [
                                            "consignmentQdId" => $qdId,
                                            "qdBillNo" => $detailResp['qdBillNo'],
                                            "beforeConsignmentQdPublishRecordId" => $beforeDetail['consignmentQdPublishRecordId'],
                                            "afterConsignmentQdPublishRecordId" => $detail['consignmentQdPublishRecordId'],
                                            "beforeBidBillNo" => $beforeDetail['bidBillNo'],
                                            "afterBidBillNo" => $detail['bidBillNo'],
                                            "beforeGroupId" => null,
                                            "beforeSupplierId" => null,
                                            "afterGroupId" => null,
                                            "afterSupplierId" => null,
                                            "action" => "重新发布",
                                            "remark" => $detail['createBy'] === 'ConsignmentWorkFlow' ? "因寄卖商未参与竞标且满足重新发布条件，清单自动发布" : "操作了重新发布",
                                            "createTime" => $detail['createTime'],
                                            "createBy" => $detail['createBy'],
                                        ];
                                    }

                                }


                                if (count($detail['supplierQdApplyRecordDetailBOList']) > 0) {
                                    usort($detail['supplierQdApplyRecordDetailBOList'], function ($a, $b) {
                                        return $b['totalScore'] <=> $a['totalScore'];
                                    });
                                    $groupId = $detail['supplierQdApplyRecordDetailBOList'][0]['groupId'];
                                    $supplierId = $detail['supplierQdApplyRecordDetailBOList'][0]['supplierId'];

                                    $log[] = [
                                        "consignmentQdId" => $qdId,
                                        "qdBillNo" => $detailResp['qdBillNo'],
                                        "beforeConsignmentQdPublishRecordId" => $detail['consignmentQdPublishRecordId'],
                                        "afterConsignmentQdPublishRecordId" => $detail['consignmentQdPublishRecordId'],
                                        "beforeBidBillNo" => $detail['bidBillNo'],
                                        "afterBidBillNo" => $detail['bidBillNo'],
                                        "beforeGroupId" => null,
                                        "beforeSupplierId" => null,
                                        "afterGroupId" => $groupId,
                                        "afterSupplierId" => $supplierId,
                                        "action" => "自动分配",
                                        "remark" => "执行自动分配任务",
                                        "createTime" => date('Y-m-d H:i:s', strtotime('+1 minute', strtotime($detail['createTime']))),
                                        "createBy" => "ConsignmentWorkFlow",
                                    ];
                                }
                            }

                            foreach ($logActionList as $logAction) {
                                $log[] = $logAction;
                            }

                            $log = DataUtils::removeDuplicateRepublishLogs($log);
                            $log = DataUtils::refineLogActionListV2($log);
                        }


                        if ($log) {


                            $this->log(json_encode($log, JSON_UNESCAPED_UNICODE));

                            DataUtils::getNewResultData($curlPaService->getWayPost($curlPaService->module . "/scms/consignment/workflow/v1/batchInsertLog", $log));
                        }


                    }


                }


            }


        }

    public function initQdActionLogV2()
        {
            $curlPaService = (new CurlService())->pro()->getModule("pa")->gateway();
            $curlLogService = (new CurlService())->pro()->getModule('ux168log')->gateway();

            $list = [];

            $fileFitContent = (new ExcelUtils())->getXlsxData("../export/qd/自动作废数据.xlsx");
            if (sizeof($fileFitContent) > 0) {
                $qdBillNoList = array_column($fileFitContent, 'qd_bill_no');
                if (count($qdBillNoList) == 0) {
                    $this->log("没有迁移数据");
                    return;
                }

                $qdIdMap = [];
                foreach (array_chunk($qdBillNoList, 200) as $qdBillNoChunkList) {
                    $qdlist = DataUtils::getNewResultData($curlPaService->getWayPost($curlPaService->module . "/scms/consignmentqdlist/v1/qdPageList", [
                        "pageNum" => 1,
                        "pageSize" => 200,
                        "qdBillNoList" => $qdBillNoChunkList,
                    ]));
                    if ($qdlist && isset($qdlist['list']) && $qdlist['list'] && count($qdlist['list']) > 0) {
                        foreach ($qdlist['list'] as $qdItem) {
                            $qdIdMap[$qdItem['qdBillNo']] = $qdItem['consignmentQdId'];
                        }
                    }
                }

                $log = [];
                foreach ($fileFitContent as $fileFitContentItem) {
                    $log[] = [
                        "consignmentQdId" => $qdIdMap[$fileFitContentItem['qd_bill_no']],
                        "qdBillNo" => $fileFitContentItem['qd_bill_no'],
                        "beforeConsignmentQdPublishRecordId" => $fileFitContentItem['consignment_qd_publish_record_id'],
                        "afterConsignmentQdPublishRecordId" => null,
                        "beforeBidBillNo" => $fileFitContentItem['bid_bill_no'],
                        "afterBidBillNo" => null,
                        "beforeGroupId" => null,
                        "beforeSupplierId" => null,
                        "afterGroupId" => null,
                        "afterSupplierId" => null,
                        "action" => "作废",
                        "remark" => $fileFitContentItem['delete_reason'],
                        "createTime" => DateTime::createFromFormat('Y/m/d H:i:s', $fileFitContentItem['delete_time'])->format('Y-m-d H:i:s'),
                        "createBy" => "ConsignmentWorkFlow",
                    ];

                }

                if ($log) {
                    $this->log(json_encode($log, JSON_UNESCAPED_UNICODE));

                    DataUtils::getNewResultData($curlPaService->getWayPost($curlPaService->module . "/scms/consignment/workflow/v1/batchInsertLog", $log));
                }

            }


        }

    public function testDing()
        {
            (new RequestUtils("pro"))->dingTalk("测试钉钉通知是否正常");
        }

}

// === 入口 ===
$method = isset($argv[1]) ? $argv[1] : 'getAmazonSpKeyword';
$controller = new AdSync();
if (method_exists($controller, $method)) {
    $controller->$method();
} else {
    echo "可用方法：\n";
    $ref = new ReflectionClass($controller);
    foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $m) {
        $name = $m->getName();
        if (strpos($name, '__') !== 0 && strpos($name, 'common') !== 0 && $name !== 'getModule') {
            echo "  $name\n";
        }
    }
}
