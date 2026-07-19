<?php
/**
 * PMO/采购同步
 * 从 SyncCurlController 拆分
 * Class PmoSync
 */

require_once dirname(__FILE__) . '/../../../php/bootstrap.php';

class PmoSync extends CrudService
{
    public function __construct()
    {
        parent::__construct("sync/pmosync");
    }

    public function createPmo()
        {
            ///scms/pmo_plan/v1/createPmo

            $curlService = (new CurlService())->pro();
            $curlService->gateway();
            $curlService->getModule('pa');
            $ids = [
                "1995855479292080128",
                "1996202792271745024",
                "1996565180330409984",
                "1996565180775006208",
                "1996565181248962560",
                "1996565181689364480",
                "1996565182133960704",
                "1996565182670831616",
                "1996565183220285440",
                "1996565183576801280",
                "1996565183979454464",
                "1996580252532473856",
                "1996580260438736896",
                "1996580262074515456",
            ];

            foreach ($ids as $id) {
    //            $pmoArr = [
    //                "id" => $id,
    //            ];
    //            $resp = DataUtils::getNewResultData($curlService->getWayPost($curlService->module . "/scms/pmo_plan/v1/createPmo", $pmoArr));
    //            if ($resp){
    //
    //            }


                $pmoArr = [
                    "id" => $id,
                ];
                $resp = DataUtils::getNewResultData($curlService->getWayPost($curlService->module . "/scms/pmo_plan/v1/createPmo", $pmoArr));
                if ($resp) {

                }
            }


        }

    public function updateZhixiao()
        {
            $curlService = (new CurlService())->pro();
            //$curlService->gateway();
            $fileFitContent = (new ExcelUtils())->getXlsxData("../export/滞销定价sku_1.xlsx");
            $fitmentSkuMap = [];
            if (sizeof($fileFitContent) > 0) {
                $sellerIdProductIds = [];
                foreach ($fileFitContent as $info) {
                    $sellerIdProductIds[$info['账号']][] = $info['scuId'];
                }
                foreach ($sellerIdProductIds as $sellerId => $sellerProductIds) {

                    foreach (array_chunk($sellerProductIds, 100) as $chunk) {
                        $fbaInfo = DataUtils::getPageList($curlService->s3015()->get("channel-price-customizes/queryPage", [
                            "productId" => implode(",", $chunk),
                            "seller" => $sellerId,
                            "priceType" => "unsale",
                            "limit" => 100,
                        ]));

                        foreach ($fbaInfo as $info) {
                            if ($info['status'] == "inactive") {
                                $this->log("{$info['productId']} 已经取消滞销，不需要再取消");
                                continue;
                            }
                            $info['status'] = "inactive";
                            $info['endTime'] = "2025-06-12T01:00:00.000Z";
                            $info['modifiedBy'] = "system(zhouangang)";
                            $res = DataUtils::getResultData($curlService->s3015()->put("channel-price-customizes/{$info['_id']}", $info));
                            $this->log("取消滞销价:{$info['productId']}");
                        }
                    }
                }

            }


        }

    public function writeProductBaseFba()
        {
            $env = "pro";
            $rs = $this->commonFindByParams("s3015", "product_fba_bases", [
                "sequenceId" => "CR201706060001",
                "productLineGroup" => "PA",
                "status_in" => "Y",
                "channel_in" => "ebay_us",
                "limit" => 2500
            ], $env);
            if ($rs) {
                foreach ($rs as $info) {

                    $curlSsl = (new CurlService())->pro();
                    $getKeyResp = DataUtils::getNewResultData($curlSsl->gateway()->getModule("pa")->getWayPost($curlSsl->module . "/scms/ce_bill_no/v1/getCeDetailBySkuIdList", [
                        "skuIdList" => [$info['skuId']],
                        "orderBy" => "id asc",
                        "pageNumber" => 1,
                        "entriesPerPage" => 1
                    ]));
                    if ($getKeyResp && count($getKeyResp) > 0) {
                        $ceInfo = $getKeyResp[0];
                        $batchName = "";
                        if ($ceInfo && isset($ceInfo['ceBillNo']) && $ceInfo['ceBillNo']) {
                            $batchName = $ceInfo['ceBillNo'];

                            $respssss = (new CurlService())->pro()->s3044()->post("ebay_bilino_add_rounds/setSellerIdAndAddCountByBatchName", [
                                "batchName" => $batchName,
                                "skuId" => $info['skuId'],
                                "userName" => "pa-fix-sys",
                                "source" => "海外仓轮单"
                            ]);
                            $this->log("{$batchName} - {$info['skuId']} - 进入轮单：" . json_encode($respssss, JSON_UNESCAPED_UNICODE));
                        }
                    }

                }
            }

            //https://master-angular-nodejs-poms-list-manage.ux168.cn/api/product_fba_bases/queryPage?&page=1&limit=10&sequenceId=CR201706060001&productLineGroup=PA&channel_in=ebay_us&api_key=


        }

    public function writeScmsPurchaseBillNo()
        {
            $curlSsl = (new CurlService())->pro();

            $pmoBillNo = "";
    //        $fileContent = (new ExcelUtils())->getXlsxData("../export/pmo/ppms_{$pmoBillNo}.xlsx");
    //        $pmoContent = (new ExcelUtils())->getXlsxData("../export/pmo/{$pmoBillNo}.xlsx");
    //        $pmoSkuMap = [];
    //        if (sizeof($pmoContent) > 0){
    //            $pmoSkuMap = array_column($pmoContent,null,"skuId");
    //        }
    //
    //        if (sizeof($fileContent) > 0) {
    //            $preSkuList = [];
    //            foreach ($fileContent as $info){
    //                $resp = DataUtils::getResultData($curlSsl->s3009()->post("po-composite-services/getSampleSkuInfoByConditions",[
    //                    "conditionsJsonEncode" => ["titleCn" => $info['tempSkuId']],
    //                    "orderBy" => "",
    //                    "page" => 1,
    //                    "limit" => 2
    //                ]));
    //                if ($resp && count($resp) > 0 && $resp[0]['sampleSkuInfoResponse'] && $resp[0]['sampleSkuInfoResponse']['sampleSkuInfos'] && count($resp[0]['sampleSkuInfoResponse']['sampleSkuInfos']) > 0){
    //                   foreach ($resp[0]['sampleSkuInfoResponse']['sampleSkuInfos'] as $i){
    //                       if (isset($pmoSkuMap[$i['skuId']])){
    //                           $preSkuList[] = [
    //                               "devSkuPkId" => $info['id'],
    //                               "skuId" => $i['skuId']
    //                           ];
    //                           $this->log("{$info['id']} {$i['skuId']}");
    //                           break;
    //                       }
    //                   }
    //                }
    //            }

    //        $preSkuList = [];
    //        $preSkuList[] = [
    //            "devSkuPkId" => "1909472435430146070",
    //            "skuId" => "a25050800ux2790"
    //        ];
    //            if (count($preSkuList) > 0){
            $writeData = [
                //                    "prePurchaseBillNo" => $pmoBillNo,
                //                    "pmoBillNo" => "PMO2025050600011",
                //                    "ceBillNo" => "CE202505090158",
                //                    "skuList" => $preSkuList,
                "supplierId" => 5796,
                "operatorName" => "zhouangang",
                "purchaseHandleStatus" => 90,
    //                    "qdBillNo" => "QD202602270003",
                "prePurchaseBillNo" => "QD202602130001",
    //                    "assignedDate" => "2026-03-03 21:30:28Z"
            ];

            $this->log(json_encode($writeData, JSON_UNESCAPED_UNICODE));
            $getKeyResp = DataUtils::getNewResultData($curlSsl->gateway()->getModule("pa")->getWayPost($curlSsl->module . "/scms/pre_purchase/info/v1/writeBackPmoCeSkuToPrePurchase", $writeData));
            if ($getKeyResp) {
                $this->log(json_encode($getKeyResp, JSON_UNESCAPED_UNICODE));
            }
    //            }

    //        }


    //        $ss=$curlSsl->s3009()->post("market-analysis-reports/deleteSkuIdInfoBatchForCombine",[
    //           "skuIdInfoIdList" => ["681c80d451cea82da1a946d4"]
    //        ]);
    //        if ($ss){
    //
    //        }


        }

    public function get30PpmsByTempskuid()
        {
            $t = [
                "a09052200ux0075",
                "a09052200ux0065",
                "a09052300ux0012",
            ];
            $curlSsl = (new CurlService())->test();
            $getKeyResp = DataUtils::getNewResultData($curlSsl->gateway()->getModule("pa")->getWayPost($curlSsl->module . "/ppms/product_dev/sku/v2/findListWithAttr", [
                "skuIdList" => $t,
                "attrCodeList" => [
    //                "custom-skuInfo-tempSkuId",
    //                "custom-skuInfo-consignmentPrice",
    //                "min_arrival_quantity",
    //                "custom-common-salesUserName",
    //                "custom-skuInfo-factoryId",
    //                "custom-skuInfo-supplierProductNo",
    //                "custom-skuInfo-outsideTitle",
    //                "custom-skuInfo-supplierId",
                    "custom-common-salesUserName",
                    "custom-skuInfo-skuId",
                    "custom-common-minorSalesUserName"
                ]
            ]));
            if ($getKeyResp) {
    //            $tempIdsList = [
    //                [
    //                    "id" => "1879052194089963565",
    //                    "temp_sku_id" => "T250114000191"
    //                ], [
    //                    "id" => "1879052194089963718",
    //                    "temp_sku_id" => "T250114000344"
    //                ], [
    //                    "id" => "1879052194089963719",
    //                    "temp_sku_id" => "T250114000345"
    //                ], [
    //                    "id" => "1879052194089963722",
    //                    "temp_sku_id" => "T250114000348"
    //                ], [
    //                    "id" => "1879052194089963723",
    //                    "temp_sku_id" => "T250114000349"
    //                ], [
    //                    "id" => "1879052194089963724",
    //                    "temp_sku_id" => "T250114000350"
    //                ], [
    //                    "id" => "1879052194089963725",
    //                    "temp_sku_id" => "T250114000351"
    //                ], [
    //                    "id" => "1879052194089963726",
    //                    "temp_sku_id" => "T250114000352"
    //                ], [
    //                    "id" => "1879052194089963728",
    //                    "temp_sku_id" => "T250114000354"
    //                ], [
    //                    "id" => "1879052194089963733",
    //                    "temp_sku_id" => "T250114000359"
    //                ]
    //            ];
    //            $tempIdsIdMap = [];
    //            foreach ($tempIdsList as $sss.txt){
    //                $tempIdsIdMap[$sss.txt['temp_sku_id']] = $sss.txt['id'];
    //            }
    //            $this->log(json_encode($getKeyResp,JSON_UNESCAPED_UNICODE));
    //            $preSkuList = [];
    //            foreach ($getKeyResp as $info){
    //                $resp = DataUtils::getResultData($curlSsl->s3009()->post("po-composite-services/getSampleSkuInfoByConditions",[
    //                    "conditionsJsonEncode" => ["titleCn" => $info['custom-skuInfo-outsideTitle']],
    //                    "orderBy" => "",
    //                    "page" => 1,
    //                    "limit" => 2
    //                ]));
    //                if ($resp && count($resp) > 0 && $resp[0]['sampleSkuInfoResponse'] && $resp[0]['sampleSkuInfoResponse']['sampleSkuInfos'] && count($resp[0]['sampleSkuInfoResponse']['sampleSkuInfos']) > 0){
    //                    foreach ($resp[0]['sampleSkuInfoResponse']['sampleSkuInfos'] as $i){
    //                        $preSkuList[] = [
    //                            "devSkuPkId" => $tempIdsIdMap[$info['custom-skuInfo-tempSkuId']],
    //                            "skuId" => $i['skuId']
    //                        ];
    //                    }
    //                }
    //            }

    //            $fileContent = (new ExcelUtils())->getXlsxData("../export/qd/补充的T号.xlsx");
    //            $titleCnMap = [];
    //            foreach ($fileContent as $info){
    //                $titleCnMap[$info['titleCn']] = $info;
    //            }
    //            foreach ($getKeyResp as $info){
    //                if (isset($titleCnMap[$info['custom-skuInfo-outsideTitle']])){
    //                    $preSkuList[] = [
    //                        "devSkuPkId" => $tempIdsIdMap[$info['custom-skuInfo-tempSkuId']],
    //                        "skuId" => $titleCnMap[$info['custom-skuInfo-outsideTitle']]['skuId']
    //                    ];
    //                }
    //            }
    //            if ($preSkuList){
    //                $writeData = [
    //                    "prePurchaseBillNo" => "QD202504080024",
    //                    "ceBillNo" => "CE202505050082",
    ////                    "skuList" => $preSkuList,
    //                    "operatorName" => "zhouangang",
    ////                    "purchaseHandleStatus" => 70$tempIdsIdMap = {数组} [10]
    //                ];
    //
    //                $this->log(json_encode($writeData,JSON_UNESCAPED_UNICODE));
    //                $getKeyResp = DataUtils::getNewResultData($curlSsl->gateway()->getModule("pa")->getWayPost($curlSsl->module . "/scms/pre_purchase/info/v1/writeBackPmoCeSkuToPrePurchase", $writeData));
    //                if ($getKeyResp){
    //                    $this->log(json_encode($getKeyResp,JSON_UNESCAPED_UNICODE));
    //                }
    //            }
            }

        }

    public function updateProductListNo()
        {
            $env = "pro";
            $fileContent = (new ExcelUtils())->getXlsxData("../export/productListNo.xlsx");

            $curlService = new CurlService();
            $curlService = $curlService->pro();

            if (sizeof($fileContent) > 0) {
                $batchList = array_unique(array_column($fileContent, "productList"));
    //            foreach (array_chunk($batchList,200) as $chunk){
    //                $list = DataUtils::getPageList($curlService->ux168()->get("product_development_lists/queryPage",[
    //                    "productListNo_in" => implode(",",$chunk),
    //                    "verticalDepartment" => "PA",
    //                    "limit" => 200,
    //                ]));
    //                if (!empty($list)){
    //                    foreach ($list as $v){
    //                        $v['cancelDate'] = null;
    //                        $v['assignedDate'] = "2025-03-06T14:00:02.000Z";
    //                        $v['draftNum'] = 1;
    //                        $v['supplierId'] = [4397];
    //                        $res = DataUtils::getResultData($curlService->ux168()->put("product_development_lists/{$v['_id']}",$v));
    //                        $this->log("更新：".json_encode($res,JSON_UNESCAPED_UNICODE));
    //                    }
    //                }
    //            }

    //            foreach (array_chunk($batchList,200) as $chunk) {
    //                $list1 = DataUtils::getPageList($curlService->ux168()->get("consignment_applys/queryPage", [
    //                    "productListNo_in" => implode(",", $chunk),
    //                    "limit" => 200,
    //                ]));
    //                if (!empty($list1)){
    //                    foreach ($list1 as $v){
    //                        $v['status'] = 2;
    //                        $res = DataUtils::getResultData($curlService->ux168()->put("consignment_applys/{$v['_id']}",$v));
    //                        $this->log("更新：".json_encode($res,JSON_UNESCAPED_UNICODE));
    //                    }
    //                }
    //            }


    //            foreach ($batchList as $productListNo) {
    //                $list = DataUtils::getPageList($curlService->ux168()->get("product_development_lists/queryPage", [
    //                    "productListNo" => $productListNo,
    //                    "limit" => 200,
    //                ]));
    //                if (!empty($list)) {
    //                    foreach ($list as $v) {
    //                        $v['status'] = "6";
    //                        $v['supplierId'] = "4397";
    //                        $res = DataUtils::getResultData($curlService->ux168()->put("product_development_lists/{$v['_id']}", $v));
    //                        $this->log("更新：" . json_encode($res, JSON_UNESCAPED_UNICODE));
    //                    }
    //                }
    //            }


    //            $res = DataUtils::getResultData($curlService->ux168()->get("product_development_logs/67cfe27d7601c30ae7aaee9f",[]));
    //
    //            if ($res){
    //                //$res['supplierId'] = "[4397]";
    //                $res['remark'] = "{\"draftBeginDate\":\"2025-03-06 14:00:00\",\"draftOverDate\":\"2025-03-06 21:00:00\",\"applyDate\":\"2025-03-06 14:00:02\",\"reason\":\"\"}";
    //                DataUtils::getResultData($curlService->ux168()->put("product_development_logs/{$res['_id']}",$res));
    //            }
                $list = DataUtils::getPageList($curlService->ux168()->get("product_development_lists/queryPage", [
                    "productListNo" => "QD202503040025",
                    "limit" => 1,
                ]));
                if (!empty($list)) {
                    foreach ($list as $res) {
    //                    $res['cancelDate'] = null;
    //                    $res['assignedDate'] = "2025-03-06T14:00:02.000Z";
    //                    $res['draftNum'] = 1;
                        $res['draftInfos']['supplierId'] = [4397];
                        $ss = DataUtils::getResultData($curlService->ux168()->put("product_development_lists/{$res['_id']}", $res));
                        if ($ss) {

                        }
                    }
                }


    //            $app = [
    //                "productListNo" => "QD202503040025",
    //                "createdBy" => "poms_limin",
    //                "modifiedBy" => "poms_limin",
    //                "status" => "finish",
    //                "remark" => "{\"draftBeginDate\":\"2025-03-06 14:00:00\",\"draftOverDate\":\"2025-03-06 21:00:00\",\"reason\":\"\"}}",
    //                "type" => "寄卖",
    //                "supplierId" => "4397",
    //                "draftNum" => 1,
    //                "url" => "",
    //            ];
    //            DataUtils::getResultData($curlService->ux168()->post("product_development_logs",$app));
            }

        }

    public function updateProductFba()
        {
            //todo 同步前请清空表pa_all_vertical_monthly_target 和pa_all_vertical_monthly_sales

            $env = "pro";
            $fileContent = (new ExcelUtils())->getXlsxData("../export/fba.xlsx");

            $curlService = new CurlService();
            $curlService = $curlService->pro();

            if (sizeof($fileContent) > 0) {
                foreach ($fileContent as $info) {
                    $fbaInfo = DataUtils::getPageListInFirstData($curlService->s3015()->get("product_fba_bases/queryPage", [
                        "skuId" => $info['sku'],
                        "channel" => $info['上架渠道'],
                        "dcId" => $info['仓库'],
                        "limit" => 1,
                    ]));
                    if (!empty($fbaInfo)) {
                        $fbaInfo['status'] = "Y";
                        $res = DataUtils::getResultData($curlService->s3015()->put("product_fba_bases/{$fbaInfo['_id']}", $fbaInfo));
                        $this->log("更新成功" . json_encode($res, JSON_UNESCAPED_UNICODE));

                    }
                }


            }


        }

    public function deleteFC()
        {
            $chunk = [
                "FC2025031003310",
            ];
            $curlService = new CurlService();
            $curlService = $curlService->pro();
            $list = DataUtils::getPageDocList($curlService->s3044()->get("fcu_applys/queryPage", [
                "batch_in" => implode(",", $chunk),
                "company" => "CR201706060001",
                "status" => "0",
                "limit" => 200,
            ]));
            if (!empty($list)) {
                foreach ($list as $v) {
                    $del = DataUtils::getResultData($curlService->s3044()->delete("fcu_applys", $v['_id']));
                    $this->log("删除：" . json_encode($del, JSON_UNESCAPED_UNICODE));
                }
            }
        }

    public function combineFC()
        {
            $env = "pro";
            $fileContent = (new ExcelUtils())->getXlsxData("../export/FCU.xlsx");

            $curlService = new CurlService();
            $curlService = $curlService->pro();

            if (sizeof($fileContent) > 0) {
                $batchList = array_unique(array_column($fileContent, "FCU"));
                $mainFCUBatch = [];
                $allFCMap = [];
                foreach (array_chunk($batchList, 200) as $chunk) {
                    $list = DataUtils::getPageDocList($curlService->s3044()->get("fcu_applys/queryPage", [
                        "batch_in" => implode(",", $chunk),
                        "company" => "CR201706060001",
                        "status" => "0",
                        "limit" => 200,
                    ]));
                    if (!empty($list)) {
                        $mainFCUBatch = $list[0];
                        foreach ($list as $v) {
                            $allFCMap[$v['_id']] = $v['batch'];
                        }
                    }
                }

                foreach ($allFCMap as $_id => $batch) {
                    if ($_id != $mainFCUBatch['_id']) {
                        $fcuSkuMapList = DataUtils::getPageDocList($curlService->s3044()->get("fcu_sku_maps/queryPage", [
                            "main_id" => $_id,
                            "limit" => 10000,
                        ]));
                        if (count($fcuSkuMapList) > 0) {
                            foreach ($fcuSkuMapList as &$fcuSkuMapInfo) {
                                if ($fcuSkuMapInfo['main_id'] != $mainFCUBatch['_id']) {
                                    $fcuSkuMapInfo['main_id'] = $mainFCUBatch['_id'];
                                    $res = DataUtils::getResultData($curlService->s3044()->put("fcu_sku_maps/{$fcuSkuMapInfo['_id']}", $fcuSkuMapInfo));
                                    $this->log("更新：" . json_encode($res, JSON_UNESCAPED_UNICODE));
                                }
                            }
                        }
                    }
                }

            }

        }

    public function getPaSkuMaterial()
        {


            $ceBillNo = "CE202502130077";
            $parentSku = "a25010800ux1806";
    //        $dpmoList = $this->commonFindByParams("s3044","pa_sku_materials",[
    //            "limit" => 1000,
    //            "ceBillNo" => $ceBillNo,
    //            "parentSkuId" => $parentSku
    //        ],"pro");
    //        if (count($dpmoList) >0){
    //            foreach ($dpmoList as $itm){
    //                if ($itm['parentSkuId'] == ""){
    //                    continue;
    //                }
    //                $this->commonDelete("s3044","pa_sku_materials",$itm['_id'],"pro");
    //            }
    //        }
    //        die("111");

            $fileFitContent = (new ExcelUtils())->getXlsxData("../export/fitment.xlsx");
            $fitmentSkuMap = [];
            if (sizeof($fileFitContent) > 0) {
                foreach ($fileFitContent as $info) {
                    $fitmentSkuMap[$info['skuId']][] = [
                        "make" => $info['make'],
                        "model" => $info['model']
                    ];
                }
            }

            $fileFitContent = (new ExcelUtils())->getXlsxData("../export/cpasin.xlsx");
            $cpSkuMap = [];
            if (sizeof($fileFitContent) > 0) {
                foreach ($fileFitContent as $info) {
                    $cpSkuMap[$info['skuId']][] = $info['asin'];
                }
            }
            $dpmoList = $this->commonFindByParams("s3044", "pa_sku_materials", [
                "limit" => 1000,
                "ceBillNo" => $ceBillNo,
    //            "skuId" => $parentSku
            ], "pro");
            $_idList = [];
            if (count($dpmoList) > 0) {
                foreach ($dpmoList as $info) {
                    $keywords = [
                        "Gear Shift Knob Cover",
                        "Gear Shift Knob Sticker",
                        "Gear Shift Knob Decal",
                        "Gear Shift Head Cover",
                        "Gear Shift Head Cap",
                    ];
                    $fitment = [];
                    $cpAsin = [];
                    if (isset($fitmentSkuMap[$info['skuId']])) {
                        $fitment = $fitmentSkuMap[$info['skuId']];
                    }
                    if (isset($cpSkuMap[$info['skuId']])) {
                        $cpAsin = $cpSkuMap[$info['skuId']];
                    }


    //                $info['keywords'] = $keywords;
                    $info['cpAsin'] = $cpAsin;
                    $info['fitment'] = $fitment;
                    $info['modifiedBy'] = "zhouangang";
                    $this->commonUpdate("s3044", "pa_sku_materials", $info, "pro");

                }


    //            $parentSkuInfo = $dpmoList[0];
    //            $main = $this->commonFindOneByParams("s3044","pa_ce_materials",[
    //                "limit" => 1,
    //                "ceBillNo" => $ceBillNo
    //            ],'pro');
    //            if (count($main['skuIdList']) > 0){
    //                $keywords = [
    //                    "Gear Shift Knob Cover",
    //                    "Gear Shift Knob Sticker",
    //                    "Gear Shift Knob Decal",
    //                    "Gear Shift Head Cover",
    //                    "Gear Shift Head Cap",
    //                ];
    //                $fitment = [];
    //                $cpAsin = [];
    //
    //                foreach ($main['skuIdList'] as $info){
    //                    if(isset($fitmentSkuMap[$info])){
    //                        $fitment = $fitmentSkuMap[$info];
    //                    }
    //                    if(isset($cpSkuMap[$info])){
    //                        $cpAsin = $cpSkuMap[$info];
    //                    }
    //                    if ($info == $parentSku){
    //                        $parentSkuInfo['keywords'] = $keywords;
    //                        $parentSkuInfo['cpAsin'] = $cpAsin;
    //                        $parentSkuInfo['fitment'] = $fitment;
    //                        $parentSkuInfo['modifiedBy'] = "zhouangang";
    //                        $this->commonUpdate("s3044","pa_sku_materials",$parentSkuInfo,"pro");
    //                        continue;
    //                    }
    //
    //                    $cloneInfo = $parentSkuInfo;
    //                    $cloneInfo['skuId'] = $info;
    //                    $cloneInfo['parentSkuId'] = $parentSkuInfo['skuId'];
    //                    $cloneInfo['keywords'] = $keywords;
    //                    $cloneInfo['cpAsin'] = $cpAsin;
    //                    $cloneInfo['fitment'] = $fitment;
    //                    unset($cloneInfo['_id']);
    //                    $this->commonCreate("s3044","pa_sku_materials",$cloneInfo,"pro");
    //                }
    //            }

            }

        }

    public function getCompanyByCompanyId($userName = 'zhouangang')
        {


            $curlService = new CurlService();
            $resp = $curlService->pro()->s3009()->get("system-manages/getCompany", ["companyId" => "CR201706060001"]);
            $data = DataUtils::getResultData($resp);

            $channelParams = array();
            $channelArray = array();
            if ($data) {
                $info = $data[0];
                $channelDetailParams = array();
                foreach ($info['regional'] as $item) {
                    $channelArr = explode("_", $item);
                    $channelDetailParams[] = array(
                        "platform" => $channelArr[0],
                        "channel" => $item,
                        "saleStatus" => "A",
                        "type" => "",
                        "url" => "",
                        "remark" => "",
                        "isCaught" => "0"
                    );
                    $channelArray[] = $item;
                }

                $channelParams = array(
                    "status" => "A",
                    "createdBy" => $userName,
                    "modifiedBy" => $userName,
                    "channelSales" => $channelDetailParams,
                    "origin" => "2009",
                );

            }

            $productList = $this->commonFindByParams("s3015", "pa_product_details", [
                "ceBillNo_in" => "CE202412100077"
            ], "pro");
            $this->log(json_encode(array_column($productList, "skuId"), JSON_UNESCAPED_UNICODE));
            foreach ($productList as $info) {
                if ($info['skuId']) {
                    $skuSaleStatusList = $this->commonFindOneByParams("s3015", "sku-sale-statuses", ["skuId" => $info['skuId']], "pro");
                    if (count($skuSaleStatusList) == 0) {
                        $channelParams['skuId'] = $info['skuId'];
                        $result = $curlService->pro()->s3015()->post("sku-sale-statuses/createSkuSaleStatusesEx", $channelParams);
                        $this->log(json_encode($result, JSON_UNESCAPED_UNICODE));
                    } else {
                        $this->log("已有可售表");
                    }

                }
            }


        }


        //重新生成tempSkuId编号 - 重复T号问题处理

    public function buildSql()
        {
            $excelUtils = new ExcelUtils();
            $list = [
                [
                    "actualFileName" => "sasdasd",
                    "key" => "121412",
                    "link" => "eafggggg"
                ]
            ];

    //        $list = [
    //            ["sasdasd",121412,"eafggggg"]
    //        ];
            $filePath = $excelUtils->downloadXlsx(["文件原名", "Oss Key名", "OSS链接"], $list, "oss文件");
    //        $filePath = $excelUtils->downloadXlsxV2($list,"oss文件");
            $this->log($filePath);
        }

    public function Mongo3009Sql($userList)
        {

            $dbList = [
                [
                    "ku" => "product_operator_line",
                    "field" => "operatorName"
                ],
                [
                    "ku" => "product_operator_line",
                    "field" => "developer"
                ],
                [
                    "ku" => "product_operator_line",
                    "field" => "userName"
                ],
                [
                    "ku" => "product_operator_main_info",
                    "field" => "developer"
                ],
                [
                    "ku" => "product_operator_main_info",
                    "field" => "traceMan"
                ],
                [
                    "ku" => "skuId_info",
                    "field" => "developer"
                ],
                [
                    "ku" => "skuId_info",
                    "field" => "traceman"
                ],
                [
                    "ku" => "skuId_info_main_table",
                    "field" => "traceman"
                ],
            ];
            foreach ($userList as $user) {
                foreach ($dbList as $db) {
                    $sql = 'db.' . $db['ku'] . '.updateMany({' . $db['field'] . ':"' . $user['old'] . '"},{$set:{' . $db['field'] . ':"' . $user['new'] . '"}});';
                    $this->log($sql);
                }
            }

        }

    public function Mongo3015Sql($userList)
        {

            $dbList = [
                [
                    "ku" => "translation_management",
                    "field" => "submitUserName"
                ],
                [
                    "ku" => "translation_management",
                    "field" => "applyUserName"
                ],
                [
                    "ku" => "translation_management",
                    "field" => "importUserName"
                ],
                [
                    "ku" => "pa_sku_info",
                    "field" => "ebaySalesUser"
                ],
                [
                    "ku" => "pa_sku_info",
                    "field" => "developerUserName"
                ],
                [
                    "ku" => "pa_sku_info",
                    "field" => "amazonSalesUser"
                ],
                [
                    "ku" => "product_base_info",
                    "field" => "salesUserName"
                ],
                [
                    "ku" => "product_sku",
                    "field" => "salesUserName"
                ],
    //            [
    //                "ku" => "pa_product",
    //                "field" => "amazonTraceMan"
    //            ],
    //            [
    //                "ku" => "pa_product",
    //                "field" => "ebayTraceMan"
    //            ],
    //            [
    //                "ku" => "pa_product",
    //                "field" => "developer"
    //            ],
    //            [
    //                "ku" => "pa_product",
    //                "field" => "traceMan"
    //            ],
    //            [
    //                "ku" => "product_base_info",
    //                "field" => "developerUserName"
    //            ],
    //            [
    //                "ku" => "product_sku",
    //                "field" => "developerUserName"
    //            ],
            ];
            foreach ($userList as $user) {
                foreach ($dbList as $db) {
                    $sql = 'db.' . $db['ku'] . '.updateMany({' . $db['field'] . ':"' . $user['old'] . '"},{$set:{' . $db['field'] . ':"' . $user['new'] . '"}});';
                    $this->log($sql);
                }
            }

        }

    public function Mongo3044Sql($userList)
        {

            $dbList = [
                [
                    "ku" => "pa_ce_material",
                    "field" => "ebayTraceMan"
                ],
                [
                    "ku" => "pa_ce_material",
                    "field" => "developer"
                ],
                [
                    "ku" => "pa_ce_material",
                    "field" => "saleName"
                ]
            ];
            foreach ($userList as $user) {
                foreach ($dbList as $db) {
                    $sql = 'db.' . $db['ku'] . '.updateMany({' . $db['field'] . ':"' . $user['old'] . '"},{$set:{' . $db['field'] . ':"' . $user['new'] . '"}});';
                    $this->log($sql);
                }
            }

        }

}

// === 入口 ===
$method = isset($argv[1]) ? $argv[1] : 'createPmo';
$controller = new PmoSync();
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
