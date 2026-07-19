<?php
/**
 * SKU修复
 * 从 SyncCurlController 拆分
 * Class SkuFix
 */

require_once dirname(__FILE__) . '/../../../php/bootstrap.php';

class SkuFix extends CrudService
{
    public function __construct()
    {
        parent::__construct("sync/skufix");
    }

    public function fixSkuVerticalId()
        {
            $curlService = (new CurlService())->pro();

            $fileFitContent = (new ExcelUtils())->getXlsxData("../export/修复SKU.xlsx");

            if (sizeof($fileFitContent) > 0) {

                $companyIdMap = [
                    "PA" => "CR201706060001",
                    "MRO" => "CR201706080001",
                    "CSA" => "CR201706260001",
                    "HG" => "CR201706060002",
                    "PLG" => "CR2024052100001",
                    "运营" => "CR201706080003",
                ];
                foreach ($fileFitContent as $info) {
                    $productInfo = DataUtils::getPageListInFirstData($curlService->s3015()->get("product-skus/queryPage", [
                        "productId" => $info['skuid'],
                        "limit" => 1
                    ]));
                    if ($productInfo) {
                        if (!$productInfo['verticalId']) {
                            $productInfo['verticalId'] = $companyIdMap[$info['company']] ?? null;
                            $productInfo['action'] = "修复商家ID";
                            $productInfo['userName'] = "system(zhouangang)";

                            $resp = $curlService->s3015()->post("product-skus/updateProductSku?_id={$productInfo['_id']}", $productInfo);
                            if ($resp) {

                            }
                        } else {
                            $this->log("有商家ID，不需要修");
                        }

                    }
                }

            }


        }


        /**
         * 修复垂直ID
         * @throws Exception
         */

    public function fixAmazonSpRuleId()
        {
            $curlService = (new CurlService())->pro();

            $productInfo = DataUtils::getPageList($curlService->s3023()->get("amazon_sp_sellers/queryPage", [
                "company_in" => "CR201706060001",
                "limit" => 500
            ]));
            if ($productInfo) {
                foreach ($productInfo as &$info) {
                    if (isset($info['bindRule']) && count($info['bindRule']) > 0) {
                        $update = false;
                        foreach ($info['bindRule'] as &$bind) {
                            if ($bind['status'] == 0) {
                                $startDate = '2025-06-18';
                                if (strpos($info['modifiedOn'], $startDate) === 0) {
                                    $this->log("{$info['sellerId']} - {$bind['spType']} - {$info['modifiedOn']}");
                                }

                                foreach ($bind['ruleTypeAndId'] as &$sss) {
                                    if ($sss['ruleType'] == 'campaignRuleBySystem' && $sss['ruleId']) {
                                        $sss['ruleId'] = "";
                                        $update = true;
                                    }
                                }
                            }
                        }
                        if ($update) {
                            $this->log("更新");
                            $this->log(json_encode($info, JSON_UNESCAPED_UNICODE));
                            //$curlService->s3023()->put("amazon_sp_sellers/{$info['_id']}",$info);
                        }
                    }
                }
            }

        }

        /**
         * 查询本身就是未设置的数据，但是依然投放了campaign的数据
         * @throws Exception
         */

    public function fixLossSku()
        {
            $curlService = (new CurlService())->pro();

            $fileFitContent = (new ExcelUtils())->getXlsxData("../export/uploads/default/22-26sku后续问题_20250901183523.xlsx");
            if (sizeof($fileFitContent) > 0) {
                $skuIdList = [];
                foreach ($fileFitContent as $item) {
                    if ($item['producttype'] == 'SKU' && $item['有无产品线'] == "无") {
                        $skuIdList[] = $item['productid'];
                    }
                }
                $productLineNameSkuIdList = [];
                foreach (array_chunk($skuIdList, 200) as $chunk) {

                    $productList = DataUtils::getPageList($curlService->s3015()->get("product-skus/queryPage", [
                        "limit" => 200,
                        "productId" => implode(",", $chunk)
                    ]));
                    foreach ($productList as $info) {
                        $cnCategoryList = explode(" -> ", $info['cn_Category']);
                        $endCategoryName = end($cnCategoryList);

                        $productLineNameSkuIdList[$endCategoryName . "-" . $info['category']][$info['developerUserName']][$info['salesUserName']][] = $info['productId'];
                    }
                }


                foreach ($productLineNameSkuIdList as $aProductLineName => $firstObj) {
                    foreach ($firstObj as $developName => $secondObj) {
                        foreach ($secondObj as $salesUserName => $skuIdList) {
                            $this->log("{$aProductLineName} - {$developName} - {$salesUserName} ：" . json_encode($skuIdList, JSON_UNESCAPED_UNICODE));


                            if (count($skuIdList) > 0) {
                                $list = [];

                                foreach (array_chunk($skuIdList, 200) as $chunk) {
                                    $getProductMainResp = DataUtils::getQueryList($curlService->s3009()->get("product-operation-lines/queryPage", [
                                        "skuId" => implode(",", $chunk),
                                        "limit" => 200
                                    ]));
                                    if ($getProductMainResp && count($getProductMainResp['data']) > 0) {
                                        $list = array_merge($list, $getProductMainResp['data']);
                                    }
                                }

                                $skuIdProductLineMap = [];
                                if (count($list) > 0) {
                                    $skuIdProductLineMap = array_column($list, null, "skuId");
                                }

                                $resp = $curlService->s3009()->get("product-operation-lines/getProductOperatorMainInfoByProductLineName", [
                                    "productLineName" => $aProductLineName
                                ]);
                                if (empty($resp['result'])) {
                                    $uuid = DataUtils::buildGenerateUuidLike();
                                    $this->log("生成product_line_id：{$uuid}");
                                    //echo $uuid;
                                    //没有产品线，创建产品线
                                    $createProductMainResp = $curlService->s3009()->post("product-operation-lines/createProductOperatorMainInfo", [
                                        "modifiedBy" => "pa_fix_system",
                                        "createdBy" => "pa_fix_system",
                                        "traceMan" => $salesUserName,
                                        "developer" => $developName,
                                        "product_line_id" => "PA_NEW_" . $uuid,
                                        "productLineName" => $aProductLineName,
                                        "companySequenceId" => "CR201706060001",
                                    ]);
                                    if ($createProductMainResp) {

                                        foreach ($skuIdList as $skuId) {

                                            if (isset($skuIdProductLineMap[$skuId])) {
                                                //先删除
    //                                                $delResp = $curlService->s3009()->post("product-operation-lines/removeSkuIdBySkuId", [
    //                                                    "skuIdArray" => $skuId
    //                                                ]);
    //                                                $this->logger->log2("已删除：".json_encode($delResp,JSON_UNESCAPED_UNICODE));

                                                //更新
                                                $skuData = $skuIdProductLineMap[$skuId];
                                                $skuData['developer'] = $developName;
                                                $skuData['traceMan'] = $salesUserName;
                                                $skuData['operatorName'] = $salesUserName;
                                                $skuData['userName'] = $salesUserName;
                                                $curlService->s3009()->put("product-operation-lines/{$skuData['_id']}", $skuData);
                                                continue;
                                            } else {
                                                $mainInfo = $createProductMainResp['result'];
                                                $curlService->s3009()->post("product-operation-lines", [
                                                    "companySequenceId" => $mainInfo['companySequenceId'],
                                                    "productLineName" => $mainInfo['productLineName'],
                                                    "product_line_id" => $mainInfo['product_line_id'],
                                                    "sign" => "NP",
                                                    "developer" => $developName,
                                                    "traceMan" => $salesUserName,
                                                    "createdBy" => $developName,
                                                    "modifiedBy" => $developName,
                                                    "createdOn" => date("Y-m-d H:i:s", time()) . "Z",
                                                    "verticalName" => "PA",
                                                    "operatorName" => $salesUserName,
                                                    "skuId" => $skuId,
                                                    "userName" => $salesUserName,
                                                    "product_operator_mainInfo_id" => $mainInfo['_id'],
                                                    "batch" => "",
                                                    "factoryId" => "",
                                                    "supplyType" => null,
                                                    "styleId" => ""
                                                ]);
                                            }

                                        }
                                    }

                                } else {
                                    $mainInfo = $resp['result'][0];
                                    foreach ($skuIdList as $skuId) {

                                        if (isset($skuIdProductLineMap[$skuId])) {
                                            $skuData = $skuIdProductLineMap[$skuId];
                                            $skuData['developer'] = $developName;
                                            $skuData['traceMan'] = $salesUserName;
                                            $skuData['operatorName'] = $salesUserName;
                                            $skuData['userName'] = $salesUserName;
                                            $curlService->s3009()->put("product-operation-lines/{$skuData['_id']}", $skuData);
                                        } else {
                                            $skuData = [
                                                "companySequenceId" => $mainInfo['companySequenceId'],
                                                "productLineName" => $mainInfo['productLineName'],
                                                "product_line_id" => $mainInfo['product_line_id'],
                                                "sign" => "NP",
                                                "developer" => $developName,
                                                "traceMan" => $salesUserName,
                                                "createdBy" => $developName,
                                                "modifiedBy" => $developName,
                                                "createdOn" => date("Y-m-d H:i:s", time()) . "Z",
                                                "verticalName" => "PA",
                                                "operatorName" => $salesUserName,
                                                "skuId" => $skuId,
                                                "userName" => $salesUserName,
                                                "product_operator_mainInfo_id" => $mainInfo['_id'],
                                                "batch" => "",
                                                "factoryId" => "",
                                                "supplyType" => null,
                                                "styleId" => ""
                                            ];

                                            $curlService->s3009()->post("product-operation-lines", $skuData);
                                        }

                                    }
                                }
                            }

                        }
                    }
                }


            }

        }

    public function fixLossSkuV2()
        {

            $curlService = (new CurlService())->pro();

            $fileFitContent = (new ExcelUtils())->getXlsxData("../export/修成30.xlsx");
            if (sizeof($fileFitContent) > 0) {
                //$skuIdList = array_column($fileFitContent,"productid");
                $skuIdList = [];
                foreach ($fileFitContent as $item) {
                    if ($item['是否留样'] == '修成30') {
                        $skuIdList[] = $item['productid'];
                    }
                }

                if (count($skuIdList) > 0) {
                    $sampleMap = [];
                    foreach (array_chunk($skuIdList, 200) as $chunk) {
                        $curlServiceS = (new CurlService())->pro();
                        $curlServiceS->gateway()->getModule('wms');
                        $resp = DataUtils::getNewResultData($curlServiceS->getWayPost($curlServiceS->module . "/receive/sample/expect/v1/page", [
                            "skuIdIn" => $chunk,
                            "vertical" => "PA",
                            "pageSize" => 500,
                            "pageNum" => 1,
                        ]));
                        if ($resp['list']) {
                            foreach ($resp['list'] as $item) {
                                $sampleMap[$item['skuId']][$item['category']] = $item;
                            }
                        }

                    }


                    $needSampleSkuIdList = [];
                    foreach ($skuIdList as $skuId) {
                        if (!isset($sampleMap[$skuId])) {
                            $needSampleSkuIdList[] = [
                                "category" => "dataTeam",
                                "createBy" => "pa-fix-system",
                                "remark" => "",
                                "skuId" => $skuId,
                                "vertical" => "PA",
                                "state" => 30
                            ];
                            $needSampleSkuIdList[] = [
                                "category" => "bg",
                                "createBy" => "pa-fix-system",
                                "remark" => "",
                                "skuId" => $skuId,
                                "vertical" => "PA",
                                "state" => 30
                            ];
                        } else {

                            if (!isset($sampleMap[$skuId]['bg'])) {
                                $needSampleSkuIdList[] = [
                                    "category" => "bg",
                                    "createBy" => "pa-fix-system",
                                    "remark" => "",
                                    "skuId" => $skuId,
                                    "vertical" => "PA",
                                    "state" => 30
                                ];
                            }

                            if (!isset($sampleMap[$skuId]['dataTeam'])) {
                                $needSampleSkuIdList[] = [
                                    "category" => "dataTeam",
                                    "createBy" => "pa-fix-system",
                                    "remark" => "",
                                    "skuId" => $skuId,
                                    "vertical" => "PA",
                                    "state" => 30
                                ];
                            }

                        }

                    }
                    if (count($needSampleSkuIdList) > 0) {
                        $curlServiceSS = (new CurlService())->pro();
                        $curlServiceSS->gateway()->getModule('wms');
                        $createResp = DataUtils::getNewResultData($curlServiceSS->getWayPost($curlServiceSS->module . "/receive/sample/expect/v1/batchCreate", $needSampleSkuIdList));
                        if ($createResp && $createResp['value']) {
                            $this->log("剩余sku：" . implode(',', array_column($needSampleSkuIdList, 'skuId')) . " 留样打标成功...");
                        } else {
                            $this->log("留样打标失败");
                        }
                    } else {
                        $this->log("预计留样的数据都已存在，无需留样");
                    }


                }


            }


        }

    public function searchLossSku()
        {
            $curlService = (new CurlService())->pro();

            $fileFitContent = (new ExcelUtils())->getXlsxData("../export/22-26生成的sku数据.xlsx");
            if (sizeof($fileFitContent) > 0) {
                $skuIdList = array_column($fileFitContent, "productid");

                $list = [];
                foreach (array_chunk($skuIdList, 200) as $chunk) {
                    $getProductMainResp = DataUtils::getQueryList($curlService->s3009()->get("product-operation-lines/queryPage", [
                        "skuId" => implode(",", $chunk),
                        "limit" => 200
                    ]));
                    if ($getProductMainResp && count($getProductMainResp['data']) > 0) {
                        $list = array_merge($list, $getProductMainResp['data']);
                    }
                }
                $skuIdProductLineMap = [];
                if (count($list) > 0) {
                    $skuIdProductLineMap = array_column($list, null, "skuId");
                }

                $sampleMap = [];
                foreach (array_chunk($skuIdList, 200) as $chunk) {
                    $curlServiceS = (new CurlService())->pro();
                    $curlServiceS->gateway()->getModule('wms');
                    $resp = DataUtils::getNewResultData($curlServiceS->getWayPost($curlServiceS->module . "/receive/sample/expect/v1/page", [
                        "skuIdIn" => $chunk,
                        "vertical" => "PA",
                        "pageSize" => 500,
                        "pageNum" => 1,
                    ]));
                    if ($resp['list']) {
                        foreach ($resp['list'] as $item) {
                            $sampleMap[$item['skuId']][$item['category']] = $item;
                        }
                    }

                }


                $export = [];
                foreach ($fileFitContent as $item) {
                    $data = $item;
                    if (isset($skuIdProductLineMap[$item['productid']])) {
                        $data['noProductLineId'] = "有";
                    } else {
                        $data['noProductLineId'] = "无";
                    }

                    if (!isset($sampleMap[$item['productid']])) {
                        $data['noSampleBg'] = "无";
                        $data['noSampleDt'] = "无";
                    } else {
                        if (isset($sampleMap[$item['productid']]['bg'])) {
                            $data['noSampleBg'] = "有";
                        } else {
                            $data['noSampleBg'] = "无";
                        }

                        if (isset($sampleMap[$item['productid']]['dataTeam'])) {
                            $data['noSampleDt'] = "有";
                        } else {
                            $data['noSampleDt'] = "无";
                        }
                    }

                    $export[] = $data;

                }

                if (count($export) > 0) {
                    $excelUtils = new ExcelUtils();
                    $downloadOssLink = "22-26sku后续问题HHHHHHHH_" . date("YmdHis") . ".xlsx";
                    $downloadOssPath = $excelUtils->downloadXlsx(["productid", "producttype", "status", "createdon", "有无产品线", "有无bg留样", "有无资料留样"], $export, $downloadOssLink);

                }


            }

        }

    public function fixDengyiyi()
        {
            $pmo = [
                "DPMO250626003",
                "DPMO250626004",
                "DPMO250627004",
                "DPMO250707006",
                "DPMO250707011",
                "DPMO250707012",
                "DPMO250707013",
                "DPMO250721007",
                "DPMO250729010",
                "DPMO250819009",
            ];
            $curlService = (new CurlService())->pro();
            $list = DataUtils::getPageDocList($curlService->s3044()->get("pa_ce_materials/queryPage", [
                "batchName_in" => implode(",", $pmo),
                "limit" => 500
            ]));
            if ($list) {
                foreach ($list as $info) {
                    if ($info['ebayTraceMan'] == 'dengyiyi2') {
                        $info['ebayTraceMan'] = 'dengyiyi';
                        foreach ($info['ebayTraceManList'] as &$item) {
                            if ($item == 'dengyiyi2') {
                                $item = 'dengyiyi';
                            }
                        }
                        $this->log(json_encode($info, JSON_UNESCAPED_UNICODE));
                        $curlService->s3044()->put("pa_ce_materials/{$info['_id']}", $info);
                    }
                }
            }
        }

    public function fixTranslationManagementCategory()
        {
            $curlService = (new CurlService())->pro();

            $list = DataUtils::getPageList($curlService->s3015()->get("translation_management_categorys/queryPage", [
                "exampleCategory" => 1342943031,
                "exampleChannel" => "amazon_uk",
                "orderBy" => "-modifiedOn",
                "limit" => 500
            ]));

            $data = [
                "exampleChannel" => "amazon_uk",
                "exampleCategory" => "1342943031",
                "exampleCategoryName" => "Electronics & Photo > Car & Vehicle Electronics > Car Electronics > Reversing Cameras",
                "correspondData" => [],
                "createdBy" => "system(zhouangang)",
                "modifiedBy" => "system(zhouangang)",
                "createdOn" => date("Y-m-d H:i:s", time()) . "Z",
                "modifiedOn" => date("Y-m-d H:i:s", time()) . "Z"
            ];
            $correspondData = [];

            $sameChannelData = [];
            foreach ($list as $info) {
                if ($info['correspondData']) {
                    foreach ($info['correspondData'] as $tree) {
                        if (!isset($sameChannelData[$tree['channel']])) {
                            $sameChannelData[$tree['channel']] = 1;
                            $correspondData[] = $tree;
                        }
                    }
                    $curlService->s3015()->delete("translation_management_categorys/{$info['_id']}");
                }
            }
            $data['correspondData'] = $correspondData;

            $this->log(json_encode($data, JSON_UNESCAPED_UNICODE));
            $curlService->s3015()->post("translation_management_categorys", $data);
        }

    public function fixTranslationManagement()
        {
            $curlService = (new CurlService())->pro();


            foreach ([
                         [
                             "titleList" => [
                                 "2025 W49 MRO pat 人工翻译 SKU JP 0",
                                 "(新) 2025 W49 MRO ux AI翻译 SKU JP 3",
                                 "(新) 2025 W49 MRO ux AI翻译 SKU JP 23",
                                 "(新) 2025 W49 MRO ux AI翻译 SKU JP 61",
                                 "(新) 2025 W49 MRO ux AI翻译 SKU JP 46"
                             ],
                             "status" => "4",
                             "applyName" => "huangannan",
                             "applyTime" => "2025-12-22 12:30:00Z"],
                         [
                             "titleList" => [
                                 "2025 W45 MRO EU4 AI翻译 SKU FR 65",
                                 "2025 W46 MRO PAT AI翻译 SKU DE 31",
                             ],
                             "status" => "4",
                             "applyName" => "shaoanlin",
                             "applyTime" => "2025-12-26 12:30:00Z"
                         ]
                     ] as $info) {

                $status = $info['status'];
                $applyName = $info['applyName'];
                $applyTime = $info['applyTime'];
                $skuIdList = [];


                foreach ($info['titleList'] as $title) {


                    $params = [
                        "title" => $title,
                    ];

                    if (DataUtils::checkArrFilesIsExist($params, "title")) {

                        if (!empty($skuIdList)) {
                            $mainInfo = DataUtils::getPageListInFirstData($curlService->s3015()->get("translation_managements/queryPage", [
                                "limit" => 100,
                                "page" => 1,
                                "title_in" => $params['title'],
                            ]));
                            if ($mainInfo['status'] != "5") {
                                foreach (array_chunk($skuIdList, 200) as $chunk) {
                                    $detailList = DataUtils::getPageList($curlService->s3015()->get("translation_management_skus/queryPage", [
                                        "limit" => 1000,
                                        "skuId_in" => implode(",", $chunk),
                                        "translationMainId" => $mainInfo['_id']
                                    ]));
                                    if ($detailList) {
                                        foreach ($detailList as $detail) {
                                            if ($detail['status'] != "5") {
                                                $detail['status'] = $status;

                                                DataUtils::getResultData($curlService->s3015()->put("translation_management_skus/{$detail['_id']}", $detail));
                                            }
                                        }
                                    }
                                }


                                foreach ($mainInfo['skuIdList'] as &$detailInfo) {
                                    if (in_array($detailInfo['skuId'], $skuIdList)) {
                                        $detailInfo['status'] = $status;
                                    }
                                }

                                $mainInfo['status'] = $status;
                                if ($status == '4' && !empty($applyName) && !empty($applyTime)) {
                                    //翻译完成的需要审核人
                                    $mainInfo['applyUserName'] = $applyName;
                                    $mainInfo['applyTime'] = $applyTime;
                                }

                                $updateMainRes = DataUtils::getResultData($curlService->s3015()->put("translation_managements/{$mainInfo['_id']}", $mainInfo));
                                $this->log("修改成功" . json_encode($updateMainRes, JSON_UNESCAPED_UNICODE));


                            }

                        } else {
                            //全sku的逻辑

                            $mainInfo = DataUtils::getPageListInFirstData($curlService->s3015()->get("translation_managements/queryPage", [
                                "limit" => 100,
                                "page" => 1,
                                "title_in" => $params['title'],
                            ]));
                            if ($mainInfo['status'] != "5") {
                                $mainInfo['status'] = $status;
                                foreach ($mainInfo['skuIdList'] as &$detailInfo) {
                                    $detailInfo['status'] = $status;
                                }
                                if ($status == '4' && !empty($applyName) && !empty($applyTime)) {
                                    //翻译完成的需要审核人
                                    $mainInfo['applyUserName'] = $applyName;
                                    $mainInfo['applyTime'] = $applyTime;
                                }
                                $updateMainRes = DataUtils::getResultData($curlService->s3015()->put("translation_managements/{$mainInfo['_id']}", $mainInfo));
                                $this->log("修改成功" . json_encode($updateMainRes, JSON_UNESCAPED_UNICODE));

                                $detailList = DataUtils::getPageList($curlService->s3015()->get("translation_management_skus/queryPage", [
                                    "limit" => 1000,
                                    "translationMainId" => $mainInfo['_id']
                                ]));
                                if ($detailList) {
                                    foreach ($detailList as $detail) {
                                        if ($detail['status'] != "5") {
                                            $detail['status'] = $status;

                                            DataUtils::getResultData($curlService->s3015()->put("translation_management_skus/{$detail['_id']}", $detail));
                                        }
                                    }
                                }

                            }


                        }
                    } else {

                    }


                }


            }


        }

    public function fixEbayTranslationMainSku()
        {
            $curlService = (new CurlService())->pro();

    //        foreach (
    //            [
    //                "2025 W43 PA for EU_AUTOFIND ES",
    //                "2025 W36 PA Part to U sku FR",
    //                "2025 W36 PA Motoforti sku FR",
    //                "2025 W36 PA luuxhaha sku FR",
    //                "2025 W36 PA Infincar sku FR",
    //                "2025 W36 PA SOPRO sku FR",
    //                "2025 W36 PA X AUTOHAUX sku FR",
    //                "2025 W36 PA Tuckbold sku FR",
    //                "2025 W36 PA RATCHROLL sku FR 人工",
    //                "2025 W43 PA for EU_AUTOFIND ES",
    //                "2025 W13 PA for EU_HEROCAR ES",
    //            ] as $title
    //        ) {
    //            $mainInfo = DataUtils::getPageListInFirstData($curlService->s3015()->get("translation_managements/queryPage", [
    //                "limit" => 1,
    //                "page" => 1,
    //                "title" => $title,
    //            ]));
    //            if ($mainInfo['status'] != "5") {
    //                $mainInfo['transfer'] = "2";
    //                $updateMainRes = DataUtils::getResultData($curlService->s3015()->put("translation_managements/{$mainInfo['_id']}", $mainInfo));
    //                $this->log("修改成功" . json_encode($updateMainRes, JSON_UNESCAPED_UNICODE));
    //
    //            }
    //        }


            foreach (
                [
                    "2025 W49 MRO EU4 AI翻译 SKU IT 182",
                    "2025 W49 MRO EU4 AI翻译 SKU IT 212"
                ] as $title
            ) {
                $mainInfo = DataUtils::getPageListInFirstData($curlService->s3015()->get("translation_management_ebays/queryPage", [
                    "limit" => 1,
                    "page" => 1,
                    "batch_title" => $title,
                ]));
                if ($mainInfo['status'] != "5") {
                    $mainInfo['status'] = "1";
                    $updateMainRes = DataUtils::getResultData($curlService->s3015()->put("translation_management_ebays/{$mainInfo['_id']}", $mainInfo));
                    $this->log("修改成功" . json_encode($updateMainRes, JSON_UNESCAPED_UNICODE));

                    $detailList = DataUtils::getPageList($curlService->s3015()->get("translation_management_ebay_skus/queryPage", [
                        "limit" => 1000,
                        "translationMainId" => $mainInfo['_id']
                    ]));
                    if ($detailList) {
                        foreach ($detailList as $detail) {
                            if ($detail['status'] != "5") {
                                $detail['status'] = "1";
                                DataUtils::getResultData($curlService->s3015()->put("translation_management_ebay_skus/{$detail['_id']}", $detail));
                            }
                        }
                    }

                }

            }


        }

    public function deleteTranslationManagementEbaySku()
        {
            $curlService = new CurlService();
            $curlService = $curlService->pro();

    //        $fileFitContent = (new ExcelUtils())->getXlsxData("../export/deleteEbaySku.xlsx");
    //
    //        if (sizeof($fileFitContent) > 0) {
    //
    //
    //            $channelSkuIds = [];
    //            foreach ($fileFitContent as $info){
    //                $channelSkuIds[$info['channel']][] = $info['skuId'];
    //            }
    //
    //            foreach ($channelSkuIds as $channel => $skuIds){
    //
    //                $detailList = DataUtils::getPageList($curlService->s3015()->get("translation_management_ebay_skus/queryPage", [
    //                    "skuId_in" => implode(",", $skuIds),
    //                    "status_in" => "0,1",
    //                    "channel" => $channel,
    //                    "columns" => "skuId",
    //                    "limit" => 1000,
    //                    "page" => 1
    //                ]));
    //                if ($detailList) {
    //                    foreach ($detailList as $detail) {
    //                        $info = DataUtils::getResultData($curlService->s3015()->delete("translation_management_ebay_skus/{$detail['_id']}"));
    //                        $this->log(json_encode($info, JSON_UNESCAPED_UNICODE));
    //                    }
    //                }
    //
    //
    //            }
    //
    //        }

            $ids = DataUtils::getResultData($curlService->s3015()->get("translation_management_ebay_skus/distinct", [
                "uxField" => "translationMainId",
            ]));
            if (count($ids) > 0) {
                $mainIds = DataUtils::getResultData($curlService->s3015()->get("translation_management_ebays/distinct", [
                    "uxField" => "_id",
                ]));
                $unsetIds = [];
                foreach ($ids as $id) {
                    if (!in_array($id, $mainIds)) {
                        $this->log("主表不存在: {$id}");
                        $unsetIds[] = $id;
                    } else {
                        $this->log("主表存在");
                    }
                }
                $this->log(json_encode($unsetIds, JSON_UNESCAPED_UNICODE));
            }


        }

    public function fix()
        {
            $curlService = (new CurlService())->pro();
            $curlService->gateway();
            $curlService->getModule('pa');

            $fileFitContent = (new ExcelUtils())->getXlsxData("../export/pmo.xlsx");

            $fileFitContent1 = (new ExcelUtils())->getXlsxData("../export/DPMO250424008-黎乾海.xlsx");

            $fitmentSkuMap = [];
            $map = [];
            if (sizeof($fileFitContent) > 0) {
                foreach ($fileFitContent as $info) {
                    $map[$info['outside_title']] = $info['id'];
                }
            }

            $skuList = [];
            if (sizeof($fileFitContent1) > 0) {
                foreach ($fileFitContent1 as $info) {
                    if (isset($map[$info['productLineName']])) {
                        $skuList[] = [
                            "devSkuPkId" => $map[$info['productLineName']],
                            "skuId" => $info['skuId']
                        ];
                    }
                    $map2[$info['productLineName']] = $info['skuId'];
                }
            }


            $pmoArr = [
                "prePurchaseBillNo" => "DPMO250424008",
                "skuList" => $skuList,
    //            "pmoBillNo" => "PMO2025042500159",
                "operatorName" => "zhouangang",
                "purchaseHandleStatus" => 70
            ];
            $resp = DataUtils::getNewResultData($curlService->getWayPost($curlService->module . "/scms/pre_purchase/info/v1/writeBackPmoCeSkuToPrePurchase", $pmoArr));
            if ($resp) {

            }


        }

    public function getssss()
        {
            $curlService = (new CurlService())->pro();
            $list = [
                "689ef98a7fbdf42093ce5d30"
            ];

            $exportList = [];
            foreach ($list as $id) {
                $info = DataUtils::getResultData($curlService->s3015()->get("/sgu-sku-scu-maps/{$id}", []));
                if ($info) {
                    $hasGroupName = false;
                    foreach ($info['channel'] as $channel) {
                        if ($channel['groupName']) {
                            $hasGroupName = true;
                        }
                    }
                    $res = (new RequestUtils("test"))->callAliCloudSls2($info['skuScuId']);
                    $oldSguId = "";
                    $oldSkuId = "";
                    if ($res && $res['data'] && count($res['data']) > 0) {
                        $logs = $res['data'][0]['FormString'];
                        $data = json_decode($logs, true);
                        $oldSkuId = $data['skuScuId'];
                        $oldSguId = $data['sguId'];
                    }
                    $exportList[] = [
                        "sku_id" => $info['skuScuId'],
                        "sgu_id" => $info['sguId'],
                        "old_sku_id" => $oldSkuId,
                        "old_sgu_id" => $oldSguId,
                        "hasGroupName" => $hasGroupName ? "有" : "无",
                        "createTime" => $info['createdOn'],
                        "modifiedOn" => $info['modifiedOn'],
                    ];
                }
            }

            if ($exportList) {
                $excelUtils = new ExcelUtils();
                $downloadOssLink = "g号修复的被更新的_" . date("YmdHis") . ".xlsx";
                $downloadOssPath = $excelUtils->downloadXlsx(["sku_id", "sgu_id", "旧A号", "旧G号", "有分组的", "创建日期", "修改日期"], $exportList, $downloadOssLink);

            }

        }

}

// === 入口 ===
$method = isset($argv[1]) ? $argv[1] : 'fixSkuVerticalId';
$controller = new SkuFix();
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
