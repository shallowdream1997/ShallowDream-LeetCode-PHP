<?php
/**
 * SKU资料同步
 * 从 SyncCurlController 拆分
 * Class SkuMaterialSync
 */

require_once dirname(__FILE__) . '/../../../php/bootstrap.php';

class SkuMaterialSync extends CrudService
{
    public function __construct()
    {
        parent::__construct("sync/skumaterialsync");
    }

    public function syncSkuMaterialToAudit()
        {
            $curlService = (new CurlService())->pro();
            $curlService->gateway();
            $this->getModule('pa');

            $resp1 = DataUtils::getNewResultData($curlService->getWayPost($this->module . "/sms/sku/material/changed_doc/v1/page", [
                "pageNum" => 1,
                "pageSize" => 500,
                "applyStatus" => 30
            ]));

            $batchNameList = [];
            if ($resp1 && count($resp1['list']) > 0) {
                foreach ($resp1['list'] as $info) {
    //                if ($info['afterChangedTranslationAttributeValue'] == "<p></p>\n"){
    //                    $batchNameList[] = $info['docNumber'];
    //                }
                    $batchNameList[] = $info['docNumber'];
                }
            }
    //        $batchNameList = [
    //            "2025080700056",
    //        ];
            if (count($batchNameList) > 0) {
                $this->log("一共：" . count($batchNameList) . "个单据翻译失败，");
                $this->log(json_encode($batchNameList, JSON_UNESCAPED_UNICODE));
                foreach ($batchNameList as $item) {
                    $postParams = [
                        "docNumbers" => [$item],
                        "operatorName" => "P3-fixTranslationFail"
                    ];
                    $resp = DataUtils::getNewResultData($curlService->getWayPost($this->module . "/sms/sku/material/changed_doc/v1/syncSkuMaterialToAudit", $postParams));

                    $this->log(json_encode($resp, JSON_UNESCAPED_UNICODE));
                }

            }
        }

    public function updateSkuMaterial()
        {
            $curlService = (new CurlService())->pro();
            $list = [];

    //        $fileFitContent = (new ExcelUtils())->getXlsxData("../export/CEB.xlsx");
            $fitmentSkuMap = [];

            $fileFitContent = [
                ['ce_bill_no' => 'CE202512260065'],
            ];
            if (sizeof($fileFitContent) > 0) {
                foreach ($fileFitContent as $item) {


                    $main = DataUtils::getPageDocListInFirstDataV1($curlService->s3044()->get("pa_ce_materials/queryPage", [
                        "limit" => 1,
                        "ceBillNo" => $item['ce_bill_no'],
                    ]));
                    if (count($main) > 0) {
                        $this->log("{$item['ce_bill_no']}");
                        $main['status'] = 'materialComplete';
                        $curlService->s3044()->put("pa_ce_materials/{$main['_id']}", $main);

                        if (count($main['skuIdList']) > 0) {
                            foreach ($main['skuIdList'] as $sku) {

                                $detail = DataUtils::getPageDocListInFirstDataV1($curlService->s3044()->get("pa_sku_materials/queryPage", [
                                    "limit" => 1,
                                    "page" => 1,
                                    "skuId" => $sku,
                                    "ceBillNo" => $main['ceBillNo'],
                                ]));
                                if ($detail) {
                                    $detail['status'] = "materialComplete";
                                    $curlService->s3044()->put("pa_sku_materials/{$detail['_id']}", $detail);
                                }
                            }

                        }

                    }


                }


            }


        }

    public function syncPaSkuMaterial()
        {
            $curlService = (new CurlService())->pro();


            $skuIdList = ["a25031700ux0462"];

            $list = [];

            $resp1 = DataUtils::getPageDocList($curlService->s3044()->get("pa_sku_materials/queryPage", [
                "limit" => 5000,
                "page" => 1,
                "skuId_in" => implode(",", $skuIdList)
            ]));
            if (count($resp1) > 0) {
                $list = array_merge($list, $resp1);
            }

            if (count($list) > 0) {
                $curlService = (new CurlService())->test();
                $testList = DataUtils::getPageDocList($curlService->s3044()->get("pa_sku_materials/queryPage", [
                    "limit" => 5000,
                    "page" => 1,
                    "skuId_in" => implode(",", $skuIdList)
                ]));
                $skuIdMap = array_column($testList, null, "skuId");

                foreach ($list as $info) {
                    if (isset($skuIdMap[$info['skuId']])) {
                        $curlService->s3044()->delete("pa_sku_materials/{$skuIdMap[$info['skuId']]['_id']}");
                    }
                    $curlService->s3044()->post("pa_sku_materials", $info);
                }

            }

        }

    public function skuMaterialDocCreate()
        {
    //        $curlService = new CurlService();
    //        $curlService->local()->gateway()->getModule('pa');
    //
    //        $info = [
    //            "createBy" => "zhouangang",
    //            "skuId" => "a23051500ux0518",
    //            "updateType" => "UpdateAttribute",
    //        ];
    //
    //
    //        $resp3 = DataUtils::getNewResultData($curlService->getWayPost("/sms/sku/material/changed_doc/v1/initSkuMaterialChangedDoc", $info));
    //        if ($resp3){
    //            $this->log(json_encode($resp3,JSON_UNESCAPED_UNICODE));
    //        }

            $curlService = (new CurlService())->pro();

            $list = [
                "g25042500ux8095",
                "g25042500ux8129",
                "g25042500ux8109",
                "g25042500ux8108",
                "g25042500ux8119",
                "g25042500ux8118",
                "g25042500ux8093",
                "g25042500ux8094",
                "g25042500ux8128",
                "s25042501ux0957",
                "s25042501ux0954",
                "s25042501ux0958",
                "s25042501ux0953",
                "s25042501ux0945",
                "s25042501ux0998",
                "s25042501ux0991",
                "s25042501ux0987",
                "s25042501ux0968",
                "s25042501ux0974",
                "s25042501ux0969",
                "s25042501ux0983",
                "s25042501ux0978",
                "s25042501ux0977",
                "s25042501ux0944",
                "s25042501ux0955",
                "s25042501ux0946",
                "s25042501ux0995",
                "s25042501ux0986",
            ];
            foreach ($list as $sku) {
                $info = DataUtils::getPageListInFirstData($curlService->s3015()->get("product-skus/queryPage", [
                    "productId" => $sku
                ]));
                if ($info) {
                    foreach ($info['attribute'] as &$item) {
                        if ($item['channel'] == "walmart_us" && $item['label'] == "brand") {
                            $item['value'] = "NOMADIC NOOK";
                        }
                    }
                    $info['userName'] = "system(zhouangang)";

                    $resp = $curlService->s3015()->post("product-skus/updateProductSku?_id={$info['_id']}", $info);
                    if ($resp) {

                    }
                }
            }


        }

    public function skuMaterialSyncToProductSku()
        {
            $curlService = (new CurlService())->pro();
            $curlService->gateway();
            $curlService->getModule('pa');

            $resp1 = DataUtils::getNewResultData($curlService->getWayPost($curlService->module . "/sms/sku/material/changed_doc/v1/page", [
                "pageNum" => 1,
                "pageSize" => 500,
                "applyStatus" => 30
            ]));

            $batchNameList = [];
            if ($resp1 && count($resp1['list']) > 0) {
                foreach ($resp1['list'] as $info) {
    //                if ($info['afterChangedTranslationAttributeValue'] == "<p></p>\n"){
    //                    $batchNameList[] = $info['docNumber'];
    //                }
                    $batchNameList[] = $info['docNumber'];
                }
            }
    //        $batchNameList = [
    //            "2025080700056",
    //        ];
            if (count($batchNameList) > 0) {
                $this->log("一共：" . count($batchNameList) . "个单据翻译失败，");
                $this->log(json_encode($batchNameList, JSON_UNESCAPED_UNICODE));
                foreach ($batchNameList as $item) {
                    $postParams = [
                        "docNumbers" => [$item],
                        "operatorName" => "P3-fixTranslationFail"
                    ];
                    $resp = DataUtils::getNewResultData($curlService->getWayPost($this->module . "/sms/sku/material/changed_doc/v1/skuMaterialSyncToProductSku", $postParams));

                    $this->log(json_encode($resp, JSON_UNESCAPED_UNICODE));
                }

            }
        }

    public function fixSkuPhotoProcess()
        {
            $curlService = new CurlService();
            $curlService = $curlService->pro();

            $ceBillNo = "CE202509250127";

    //        $curlService->s3015()->delete("sku_photography_progresss","67d0f8fe4e0359ccd8168459");
    //        $curlService->s3015()->delete("sku_photography_progresss","67d0f8fe4e0359ccd8168514");
    //        $curlService->s3015()->delete("sku_photography_progresss","67d0f8fe4e0359ccd816855d");
    //
    //        die("1111");
    //        $res = DataUtils::getResultData($curlService->s3015()->get("soaps/ux168/getCeDetailByCeBillNo",[
    //           "ceBillNo" => $ceBillNo
    //        ]));
    //        $skuIdList = array_column($res,"skuId");

            $skuIdList = [
                "a25092500ux0903",
                "a25092500ux0904",
                "a25092500ux0902",
            ];

            $ceMasterCreatedOn = "2025-09-25T20:53:15.000Z";
            $skuMap = [];
            foreach (array_chunk($skuIdList, 200) as $chunk) {
                $list = DataUtils::getPageList($curlService->s3015()->get("product-skus/queryPage", [
                    "limit" => 200,
                    "productId" => implode(",", $chunk)
                ]));
                foreach ($list as $info) {
                    if ($info['status'] == "completed") {
                        $secondVeroDateTime = "";
                        foreach ($info['reviewingList'] as $val) {
                            if ($val['reviewingName'] == "secondVero") {
                                $secondVeroDateTime = $val['createdOn'];
                                break;
                            }
                        }
                        $skuMap[$info['productId']] = $secondVeroDateTime;
                    }

                }
            }

            $batch = [];
            foreach ($skuMap as $skuId => $dateTime) {
                $ss = DataUtils::getPageListInFirstData($curlService->s3015()->get("sku_photography_progresss/queryPage", [
                    "skuId" => $skuId,
                    "ceBillNo_in" => $ceBillNo,
                    "limit" => 1,
                ]));
                if ($ss) {
                    continue;
                }
                $data = [
                    "skuId" => $skuId,
                    "batchName" => "",
                    "ceBillNo" => $ceBillNo,
                    "createdBy" => "zhouangang",
                    "status" => "待拍摄",
                    "createCeBillNoOn" => $ceMasterCreatedOn,
                    "tempSkuId" => "",
                    "salesType" => "寄卖",
                    "infoCompletedOn" => $dateTime,
                    "isInfoDrafted" => ""
                ];
                $batch[] = $data;
            }

            if (count($batch) > 0) {
                $this->log(count($batch) . "个新增");
                $curlService->s3015()->post("sku_photography_progresss/createBatch", $batch);
            }
        }

    public function fixPaSkuPhotoGress()
        {
            $list = $this->commonFindByParams("s3015", "sku_photography_progresss", [
                "ceBillNo_in" => "CE202509250127"
            ], "pro");
            foreach ($list as &$item) {
                $item['createCeBillNoOn'] = "2025-03-28T16:47:58.000Z";
                $this->commonUpdate("s3015", "sku_photography_progresss", $item, "pro");
            }

        }

    public function downloadPaSkuMaterialSP()
        {
            $curlService = new CurlService();
            $curlService = $curlService->pro();

            foreach ([
                         "CE202503",
                         "CE202504",
                         "CE202505",
                         "CE202506",
                     ] as $ceBillNoLike) {
                $ceBillNoMap = [];
                $this->log($ceBillNoLike);
                $page = 1;
                do {
                    $this->log($page);
                    $l = DataUtils::getPageDocList($curlService->s3044()->get("pa_ce_materials/queryPage", [
                        "ceBillNo_like" => $ceBillNoLike,
                        "limit" => 1000,
                        "page" => $page
                    ]));
                    if (count($l) == 0) {
                        break;
                    }
                    foreach ($l as $info) {
                        $ceBillNoMap[$info['ceBillNo']] = [
                            'developer' => $info['developer'],
                            'saleName' => $info['saleName']
                        ];
                    }

                    $page++;
                } while (true);


                if (count($ceBillNoMap) > 0) {
                    $keywordsList = [];
                    $cpAsinList = [];
                    $fitmentList = [];
                    foreach ($ceBillNoMap as $ceBillNo => $info) {
                        $this->log($ceBillNo . "查询资料呈现");
                        $ll = DataUtils::getPageDocList($curlService->s3044()->get("pa_sku_materials/queryPage", [
                            "ceBillNo" => $ceBillNo,
                            "limit" => 1500,
                            "page" => 1
                        ]));
                        if (count($ll) == 0) {
                            break;
                        }
                        foreach ($ll as $item) {
                            foreach ($item['keywords'] as $k) {
                                $keywordsList[] = [
                                    'ceBillNo' => $ceBillNo,
                                    'saleName' => $info['saleName'],
                                    'skuId' => $item['skuId'],
                                    'keywords' => $k
                                ];
                            }
                            foreach ($item['cpAsin'] as $cp) {
                                $cpAsinList[] = [
                                    'ceBillNo' => $ceBillNo,
                                    'saleName' => $info['saleName'],
                                    'skuId' => $item['skuId'],
                                    'cpAsin' => $cp
                                ];
                            }
                            foreach ($item['fitment'] as $fit) {
                                $fitmentList[] = [
                                    'ceBillNo' => $ceBillNo,
                                    'saleName' => $info['saleName'],
                                    'skuId' => $item['skuId'],
                                    'make' => $fit['make'],
                                    'model' => $fit['model'],
                                ];
                            }
                        }
                    }

                    if (count($keywordsList) > 0) {
                        $this->redis->hSet(REDIS_MATERIAL_KEY . "_keywords", $ceBillNoLike, json_encode($keywordsList, JSON_UNESCAPED_UNICODE));

                        $excelUtils = new ExcelUtils();
                        $filePath = $excelUtils->downloadXlsx([
                            "CE单号",
                            "产品运营",
                            "skuId",
                            "核心词",
                        ], $keywordsList, "{$ceBillNoLike}_核心词导出_" . date("YmdHis") . ".xlsx");
                        $this->log("导出核心词");
                    } else {
                        $this->log("没有核心词");
                    }

                    if (count($cpAsinList) > 0) {
                        $this->redis->hSet(REDIS_MATERIAL_KEY . "_cpasins", $ceBillNoLike, json_encode($cpAsinList, JSON_UNESCAPED_UNICODE));
                        $excelUtils = new ExcelUtils();
                        $filePath = $excelUtils->downloadXlsx([
                            "CE单号",
                            "产品运营",
                            "skuId",
                            "CP ASIN",
                        ], $cpAsinList, "{$ceBillNoLike}_CP_Asin导出_" . date("YmdHis") . ".xlsx");
                        $this->log("导出CP ASIN");
                    } else {
                        $this->log("没有CP ASIN");
                    }

                    if (count($fitmentList) > 0) {
                        $this->redis->hSet(REDIS_MATERIAL_KEY . "_fitment", $ceBillNoLike, json_encode($fitmentList, JSON_UNESCAPED_UNICODE));
                        $excelUtils = new ExcelUtils();
                        $filePath = $excelUtils->downloadXlsx([
                            "CE单号",
                            "产品运营",
                            "skuId",
                            "make",
                            "model",
                        ], $fitmentList, "{$ceBillNoLike}_fitment导出_" . date("YmdHis") . ".xlsx");
                        $this->log("导出fitment");
                    } else {
                        $this->log("没有fitment");
                    }

                } else {
                    $this->log("{$ceBillNoLike}没有数据");
                }


            }


        }

    public function downloadPaSkuMaterialSpData()
        {
            $curlService = new CurlService();
            $curlService = $curlService->pro();


            $list = [];
            $page = 1;
            do {
                $this->log($page);
                $ll = DataUtils::getPageDocList($curlService->s3044()->get("pa_sku_materials/queryPage", [
                    "limit" => 1500,
                    "status_in" => "new,developerComplete,saleComplete,materialComplete",
                    "page" => $page
                ]));
                if (count($ll) == 0) {
                    break;
                }
                foreach ($ll as $info) {
                    if (!isset($list[$info['skuId']]) && (!empty($info['keywords']) || !empty($info['cpAsin']) || !empty($info['fitment']))) {
                        $list[$info['skuId']] = [
                            "skuId" => $info['skuId'],
                            "keywords" => $info['keywords'],
                            "cpAsin" => $info['cpAsin'],
                            "fitment" => $info['fitment'],
                        ];
                    }
                }
                $page++;
            } while (true);


            if (count($list) > 0) {

                $exportList1 = [];
                $exportList2 = [];
                foreach ($list as $sku => $info) {
                    $fitmentDataList = [];

                    if (!empty($info['fitment'])) {
                        foreach ($info['fitment'] as $fitment) {
                            $fitmentData = "{$fitment['make']} {$fitment['model']}";
                            $fitmentDataList[] = $fitmentData;
                        }
                    }
                    $cpAsinList = [];
                    if (!empty($info['cpAsin'])) {
                        $cpAsinList = implode("/", $info['cpAsin']);
                    }
                    $keywordsList = [];
                    if (!empty($info['keywords'])) {
                        $keywordsList = $info['keywords'];
                    }
                    $exportList1[] = [
                        "skuid" => $sku,
                        "fitment" => implode("\n", $fitmentDataList),
                        "cpAsin" => $cpAsinList,
                    ];

                    foreach ($keywordsList as $keywords) {
                        $exportList2[] = [
                            "skuid" => $sku,
                            "keywords" => $keywords,
                        ];
                    }


                }

                if (count($exportList1) > 0) {

                    $excelUtils = new ExcelUtils();
                    $filePath = $excelUtils->downloadXlsx([
                        "skuid",
                        "热销车型",
                        "CPasin",
                    ], $exportList1, "sku热销车型和cpasin迁移数据导出_" . date("YmdHis") . ".xlsx");
                    $this->log("sku热销车型和cpasin迁移数据导出_");
                } else {
                    $this->log("没有sku热销车型和cpasin迁移数据导出_");
                }


                if (count($exportList2) > 0) {

                    $excelUtils = new ExcelUtils();
                    $filePath = $excelUtils->downloadXlsx([
                        "skuid",
                        "keyword"
                    ], $exportList2, "sku-keyword迁移数据导出_" . date("YmdHis") . ".xlsx");
                    $this->log("sku-keyword迁移数据导出_");
                } else {
                    $this->log("没有sku-keyword迁移数据导出_");
                }

            }


        }

    public function updatePaSkuMaterial()
        {
            $curlService = new CurlService();
            $curlService = $curlService->pro();

            $fileFitContent = (new ExcelUtils())->getXlsxData("../export/1.xlsx");
            $batchNameList = [];
    //        if (sizeof($fileFitContent) > 0) {
    //            $batchNameList = array_unique(array_column($fileFitContent,"batchName"));
    //        }else{
            $batchNameList = $this->getQDDPMOBatchNameCeMaterialList();
    //        }

            if (count($batchNameList) > 0) {
                $batchNameCeBillNoMap = [];
                foreach (array_chunk($batchNameList, 100) as $chunkBatchNameList) {
                    $curlService->gateway();
                    $curlService->getModule('pa');
                    $resp = DataUtils::getNewResultData($curlService->getWayPost($curlService->module . "/sms/sku/info/material/v1/findPrePurchaseBillWithSkuForSkuMaterialInfo", $chunkBatchNameList));
                    if ($resp) {
                        $this->log("获取数据");
                        foreach ($resp as $item) {
                            $billNo = "";
                            if (isset($item['qdBillNo']) && $item['qdBillNo']) {
                                $billNo = $item['qdBillNo'];
                            }
                            if (isset($item['ceBillNo']) && $item['ceBillNo']) {
                                $billNo = $item['ceBillNo'];
                            }
                            $batchNameCeBillNoMap[$item['prePurchaseBillNo'] . $billNo] = [
                                "developerUserName" => $item['developerUserName'] ?? [],
                                "salesUserName" => $item['salesUserName'] ?? [],
                                "minorSalesUserName" => $item['minorSalesUserName'] ?? [],
                                "sourceDeveloperUserName" => $item['sourceDeveloperUserName'] ?? [],
                                "productLevelList" => $item['productLevel'] ?? [],
                                "platformList" => $item['platform'] ?? [],
                            ];
                        }
                    }
                }

                $curlService = new CurlService();
                $curlService = $curlService->pro();
                foreach (array_chunk($batchNameList, 300) as $chunkBatchNameList) {
                    $l = DataUtils::getPageDocList($curlService->s3044()->get("pa_ce_materials/queryPage", [
                        "limit" => 1000,
                        "page" => 1,
                        "batchName_in" => implode(",", $chunkBatchNameList)
                    ]));
                    if (count($l) == 0) {
                        continue;
                    }

                    foreach ($l as $item) {
                        $key = $item['batchName'] . $item['ceBillNo'];
                        if (isset($batchNameCeBillNoMap[$key])) {
                            //存在数据
                            $this->log("有数据，更新");
                            $item['developerList'] = $batchNameCeBillNoMap[$key]['developerUserName'];
                            $item['saleNameList'] = $batchNameCeBillNoMap[$key]['salesUserName'];
                            $item['ebayTraceManList'] = $batchNameCeBillNoMap[$key]['minorSalesUserName'];
                            $item['platformList'] = $batchNameCeBillNoMap[$key]['platformList'];
                            $item['productLevelList'] = $batchNameCeBillNoMap[$key]['productLevelList'];
                            $res = $curlService->s3044()->put("pa_ce_materials/{$item['_id']}", $item);
                        } else {
                            $this->log("{$item['batchName']}没有数据");
                        }
                    }

                }

            } else {
                $this->log("没有可以修改的数据");
            }
        }

    public function updatePaSkuMaterialV2()
        {
            $curlService = new CurlService();
            $curlService = $curlService->pro();

            $fileFitContent = (new ExcelUtils())->getXlsxData("../export/skuMaterial/111111.xlsx");

            if (count($fileFitContent) > 0) {
                $ceBillNoSalesMap = [];
                foreach ($fileFitContent as $info) {
                    $ceBillNoSalesMap[$info['CE/QD单号']] = [
                        "old" => $info['产品运营(调整前)'],
                        "new" => $info['产品运营(调整后)'],
                    ];
                }

                foreach ($ceBillNoSalesMap as $ceBillNo => $salesName) {
                    $l = DataUtils::getPageDocList($curlService->s3044()->get("pa_ce_materials/queryPage", [
                        "limit" => 1,
                        "page" => 1,
                        "batchName" => $ceBillNo
                    ]));
                    if (count($l) == 0) {
                        continue;
                    }

                    foreach ($l as $item) {
                        if (isset($ceBillNoSalesMap[$item['batchName']])) {
                            if ($item['saleName'] == $ceBillNoSalesMap[$item['batchName']]['old']) {
                                $item['saleName'] = $ceBillNoSalesMap[$item['batchName']]['new'];
                            }
                            if ($item['ebayTraceMan'] == $ceBillNoSalesMap[$item['batchName']]['old']) {
                                $item['ebayTraceMan'] = $ceBillNoSalesMap[$item['batchName']]['new'];
                            }


                            // 根据条件替换
                            if (isset($item['saleNameList']) && is_array($item['saleNameList'])) {
                                foreach ($item['saleNameList'] as $key => $value) {
                                    if ($value == $ceBillNoSalesMap[$item['batchName']]['old']) {
                                        $item['saleNameList'][$key] = $ceBillNoSalesMap[$item['batchName']]['new'];
                                    }
                                }
                                $item['saleNameList'] = array_unique($item['saleNameList']);
                            }
                            if (isset($item['ebayTraceManList']) && is_array($item['ebayTraceManList'])) {
                                foreach ($item['ebayTraceManList'] as $key => $value) {
                                    if ($value == $ceBillNoSalesMap[$item['batchName']]['old']) {
                                        $item['ebayTraceManList'][$key] = $ceBillNoSalesMap[$item['batchName']]['new'];
                                    }
                                }
                                $item['ebayTraceManList'] = array_unique($item['ebayTraceManList']);
                            }
                            $curlService->s3044()->put("pa_ce_materials/{$item['_id']}", $item);

                            $this->log("修改{$item['ceBillNo']}的负责人为：{$item['saleName']}");
                            $this->log(json_encode($item['saleNameList'], JSON_UNESCAPED_UNICODE));
                            $this->log(json_encode($item['ebayTraceManList'], JSON_UNESCAPED_UNICODE));
                        } else {
                            $this->log("{$item['batchName']}没有数据");
                        }
                    }

                }

            } else {
                $this->log("没有可以修改的数据");
            }
        }

    public function fixPaSkuMaterialList()
        {
            $curlService = (new CurlService())->pro();

            $fileFitContent = (new ExcelUtils())->getXlsxData("../export/skuMaterial/zicheng/资呈广告异常修复(2025.08.07)-运营确认.xlsx");
            if (sizeof($fileFitContent) > 0) {
                $ceSkuMap = [];
                foreach ($fileFitContent as $sheet => $sheetList) {
                    if ($sheet === '核心词') {
                        $uniqueKeyMap = [];
                        foreach ($sheetList as $info) {
                            if (!empty($info['核心词'])) {
                                $uniqueKey = "{$info['skuId']}{$info['核心词']}";
                                if (!isset($uniqueKeyMap[$uniqueKey])) {

                                    $ceSkuMap[$info['CE#']][$info['skuId']]['keywords'][] = $info['核心词'];

                                    $uniqueKeyMap[$uniqueKey] = 1;
                                }
                            }
                        }
                    } else if ($sheet === '热销车型') {
                        $uniqueKeyMap = [];
                        foreach ($sheetList as $info) {
                            $uniqueKey = "{$info['skuId']}{$info['make']}{$info['model']}";
                            if (!isset($uniqueKeyMap[$uniqueKey])) {
                                $ceSkuMap[$info['CE#']][$info['skuId']]['fitment'][] = [
                                    "make" => $info['make'],
                                    "model" => $info['model'],
                                ];
                                $uniqueKeyMap[$uniqueKey] = 1;
                            }
                        }
                    } else if ($sheet === 'CP asin') {
                        $uniqueKeyMap = [];
                        foreach ($sheetList as $info) {
                            if (!empty($info['asin'])) {
                                $uniqueKey = "{$info['skuId']}{$info['asin']}";

                                if (!isset($uniqueKeyMap[$uniqueKey])) {
                                    $ceSkuMap[$info['CE#']][$info['skuId']]['cpAsin'][] = $info['asin'];

                                    $uniqueKeyMap[$uniqueKey] = 1;
                                }
                            }

                        }
                    }
                }


                if ($ceSkuMap) {
                    $skuNumber = 0;
                    $ceNumber = 0;
                    foreach ($ceSkuMap as $ceBillNo => $tree1) {
                        $ceNumber++;
                        foreach ($tree1 as $skuId => $tree2) {
                            $skuNumber++;
                            $skuMaterialInfo = DataUtils::getPageDocListInFirstDataV1($curlService->s3044()->get("pa_sku_materials/queryPage", [
                                "limit" => 1,
                                "ceBillNo" => $ceBillNo,
                                "skuId" => $skuId,
                                "page" => 1
                            ]));
                            if ($skuMaterialInfo) {
                                //存在，不管之前的值是怎样的，直接覆盖
                                $skuMaterialInfo['keywords'] = $tree2['keywords'] ?? [];
                                $skuMaterialInfo['cpAsin'] = $tree2['cpAsin'] ?? [];
                                $skuMaterialInfo['fitment'] = $tree2['fitment'] ?? [];
                                $skuMaterialInfo['modifiedBy'] = "system(zhouangang88)";
                                $this->log("更新sku{$ceBillNo}-{$skuId}" . json_encode($skuMaterialInfo, JSON_UNESCAPED_UNICODE));
                                $curlService->s3044()->put("pa_sku_materials/{$skuMaterialInfo['_id']}", $skuMaterialInfo);
                            } else {
                                //不存在，就要创建,先查ce资呈，看看里面的sku，看看里面的sku有没有父类
                                $this->log("{$ceBillNo}{$skuId}不存在sku资呈,等待创建");
                                $skuMaterialList = DataUtils::getPageDocList($curlService->s3044()->get("pa_sku_materials/queryPage", [
                                    "limit" => 1000,
                                    "ceBillNo" => $ceBillNo,
                                    "page" => 1,
                                    "orderBy" => "-_id"
                                ]));
                                if ($skuMaterialList) {
                                    foreach ($skuMaterialList as $item) {
                                        if (empty($item['parentSkuId'])) {
                                            $syncSkuInfo = [];
                                            //是父类
                                            $syncSkuInfo = $item;
                                            $syncSkuInfo['parentSkuId'] = $item['skuId'];
                                            $syncSkuInfo['skuId'] = $skuId;
                                            $syncSkuInfo['ceBillNo'] = $ceBillNo;
                                            $syncSkuInfo['createdBy'] = "system(zhouangang)";
                                            $syncSkuInfo['keywords'] = $tree2['keywords'] ?? [];
                                            $syncSkuInfo['cpAsin'] = $tree2['cpAsin'] ?? [];
                                            $syncSkuInfo['fitment'] = $tree2['fitment'] ?? [];
                                            unset($syncSkuInfo['_id']);
                                            unset($syncSkuInfo['__v']);
                                            $this->log("同步父sku到子sku{$ceBillNo}-{$skuId}" . json_encode($syncSkuInfo, JSON_UNESCAPED_UNICODE));
                                            $curlService->s3044()->post("pa_sku_materials", $syncSkuInfo);
                                            break;
                                        }
                                    }
                                } else {
                                    //ce单一个sku都没有的，直接创建吧
                                    $syncSkuInfo = [
                                        "skuId" => $skuId,
                                        "ceBillNo" => $ceBillNo,
                                        "createdBy" => "system(zhouangang)",
                                        "keywords" => $tree2['keywords'] ?? [],
                                        "fitment" => $tree2['fitment'] ?? [],
                                        "cpAsin" => $tree2['cpAsin'] ?? [],
                                        "basicInfo" => "",
                                        "categoryRelation" => "",
                                        "description" => "",
                                        "operationMonitor" => "",
                                        "status" => "developerComplete",
                                        "version" => null,
                                        "parentSkuId" => "",
                                        "saleBasicInfo" => "",
                                        "developerFile" => "",
                                        "saleFile" => "",
                                        "developerStartOn" => date("Y-m-d H:i:s", time()) . "Z",
                                        "developerStartBy" => "system(zhouangang)",
                                        "saleStartOn" => null,
                                        "saleStartBy" => "",
                                        "oeNumber" => [],
                                    ];
                                    $this->log("初始化创建sku{$ceBillNo}-{$skuId}" . json_encode($syncSkuInfo, JSON_UNESCAPED_UNICODE));
                                    $curlService->s3044()->post("pa_sku_materials", $syncSkuInfo);

                                }


                            }

                        }
                    }

                    $this->log("ce单数量：{$ceNumber}");
                    $this->log("sku数量：{$skuNumber}");


                }

            }


        }

}

// === 入口 ===
$method = isset($argv[1]) ? $argv[1] : 'syncSkuMaterialToAudit';
$controller = new SkuMaterialSync();
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
