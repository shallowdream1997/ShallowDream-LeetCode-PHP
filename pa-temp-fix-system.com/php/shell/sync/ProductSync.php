<?php
/**
 * 产品同步
 * 从 SyncCurlController 拆分
 * Class ProductSync
 */

require_once dirname(__FILE__) . '/../../../php/bootstrap.php';

class ProductSync extends CrudService
{
    public function __construct()
    {
        parent::__construct("sync/productsync");
    }

    public function syncProduct()
        {
            $skuIdList = [

            ];

            $fromHost = "";

            foreach ($skuIdList as $skuId) {
                //同步product-sku

                //同步product-base-info

                //同步sku-sales-status

                //同步amazon_asin

                //同步amazon_active_listing

                //同步channel_sku_image

                //同步channel_price

                //同步pid_scu_map

                //同步sku_images

                //同步sku_sale_status

                //同步sku_seller_config

                //同步sgu_sku_scu_map

                //同步sgu_sku_scu_map_by_sguId

                //同步sgu_sku_scu_channel_map

                //同步sku_price

                //同步scu_sku_map

            }
        }

    public function syncDevSkuInfoToProductSku()
        {
            $curlService = (new CurlService())->pro();
            $curlService->gateway();
            $curlService->getModule('pa');
            $skuIdList = [
                "a25051800ux0237",
                "a25051800ux0238",
                "a25051800ux0239",
                "a25051800ux0240",
                "a25051800ux0242",
                "a25051800ux0243",
                "a25051800ux0244",
                "a25051800ux0245",
                "a25051800ux0246",
                "a25051800ux0247",
                "a25051800ux0248",
                "a25051800ux0249",
                "a25051800ux0250",
                "a25051800ux0251",
                "a25051800ux0252",
                "a25051800ux0253",
                "a25051800ux0254",
                "a25051800ux0255",
                "a25051800ux0256",
                "a25051800ux0257",
                "a25051800ux0258",
                "a25051800ux0259",
                "a25051800ux0260",
                "a25051800ux0261",
                "a25051800ux0262",
                "a25051800ux0263",
                "a25051800ux0264",
                "a25051800ux0265",
                "a25051800ux0266",
                "a25051800ux0267"
            ];
            foreach ($skuIdList as $sku) {
                $pmoArr = [
                    "initSkuId" => $sku,
                    "operatorName" => "system(zhouangang)",
                    "prePurchaseBillNo" => "QD202505130005"
                ];
                $resp = DataUtils::getNewResultData($curlService->getWayPost($curlService->module . "/sms/sku/info/init/v1/syncDevSkuInfoToProductSku", $pmoArr));
                if ($resp) {

                }
            }

        }

    public function copyNewChannel()
        {
            $curlService = (new CurlService())->pro();


            $oldChannel = "amazon_de";
            $newChannel = "amazon_nl";

            $list = [];
            for ($page = 1; $page < 10; $page++) {
                $resp1 = DataUtils::getPageList($curlService->s3015()->get("channel-amazon-attributes/queryPage", [
                    "limit" => 5000,
                    "page" => $page,
                    "channel" => $oldChannel
                ]));
                if (count($resp1) > 0) {
                    $list = array_merge($list, $resp1);
                } else {
                    break;
                }
            }
            if (count($list) > 0) {
                //$curlService = (new CurlService())->test();
                foreach ($list as $info) {
                    unset($info['_id']);
                    $info['channel'] = $newChannel;
                    $curlService->s3015()->post("channel-amazon-attributes", $info);
                }

            }
        }

    public function fixProductSku()
        {
            $fileFitContent = (new ExcelUtils())->getXlsxData("../export/需要删除来货的.xlsx");
            $fitmentSkuMap = [];
            if (sizeof($fileFitContent) > 0) {

                $curlService = (new CurlService())->pro();

                $list = array_unique(array_column($fileFitContent, "skuId"));


                $infoList = DataUtils::getPageList($curlService->s3015()->get("product-skus/queryPage", [
                    "productId" => implode(",", $list),
                    "limit" => 500
                ]));
                $map = [];
                if ($infoList) {
                    foreach ($infoList as $info) {
                        $map[$info['productId']] = $info;
                    }
                }

                foreach ($fileFitContent as $info) {

                    if (isset($map[$info['skuId']])) {
                        $deleteAttributeArray = [];
                        $channel = $info['channel'];

                        foreach ($info as $key => $value) {
                            //判断$key的开头是delete
                            if (strpos($key, "delete") === 0) {
                                $deleteAttributeArray[] = [
                                    "channel" => $channel,
                                    "label" => $value,
                                ];
                            }
                        }
                        if (empty($deleteAttributeArray)) {
                            $this->log("没有需要删除的attribute");
                            continue;
                        }
                        $productInfo = $map[$info['skuId']];
                        ProductUtils::deleteProductAttributeByArr($productInfo['attribute'], $deleteAttributeArray);

                        $productInfo['action'] = "system(删除错误的attribute)251212";
                        $productInfo['userName'] = "system(zhouangang)";

                        $this->log(json_encode($productInfo, JSON_UNESCAPED_UNICODE));
                        $resp = $curlService->s3015()->post("product-skus/updateProductSku?_id={$productInfo['_id']}", $productInfo);
                        if ($resp) {

                        }
                    }
                }
            }


        }

    public function fixProductSkuCurrent()
        {
            $fileFitContent = (new ExcelUtils())->getXlsxData("../export/product_sku存在币种属性值为false的.xlsx");


            if (sizeof($fileFitContent) > 0) {
                $skuIdList = array_unique(array_column($fileFitContent, "productid"));

                $channelSkuMap = [];
                foreach ($fileFitContent as $info) {
                    $channelSkuMap[$info['productid']] = $info['channel'];
                }
    //            $skuIdList = [
    //
    //                "a20112600ux0155",
    //
    //                "a20112600ux0156",
    //
    //            ];
                $curlService = (new CurlService())->pro();

                $productIdMap = [];
                foreach (array_chunk($skuIdList, 100) as $chunk) {
                    $productSkuList = DataUtils::getPageList($curlService->s3015()->get("product-skus/queryPage", [
                        "productId" => implode(",", $chunk),
                        "limit" => count($chunk)
                    ]));
                    if ($productSkuList) {
                        foreach ($productSkuList as $info) {
                            $productIdMap[$info['productId']] = $info;
                        }
                    }
                }


                $export = [];
                foreach ($skuIdList as $sku) {
                    if (isset($productIdMap[$sku])) {
                        $productInfo = $productIdMap[$sku];

                        $deleteMap = [];
                        foreach ($productInfo['attribute'] as $info) {
                            if (in_array($info['label'], [
                                    'MSRPWithTax_currency',
                                    'MSRP_currency',
    //                                'MSRP',
    //                                'MSRPWithTax'
                                ]) && isset($channelSkuMap[$productInfo['productId']]) && $info['channel'] == $channelSkuMap[$productInfo['productId']]) {
                                $this->log($sku . "渠道：{$info['channel']} {$info['label']} 值为: " . $info['value']);
                                $export[] = [
                                    "skuId" => $sku,
                                    "channel" => $info['channel'],
                                    "MSRPWithTax_currency" => $info['value']
                                ];
                                $key = $info['label'] . '|' . $info['channel'];
                                $deleteMap[$key] = true;
                            }
                        }

                        if (count($deleteMap) == 0) {
                            $this->log($sku . "不存在币种属性值的");
                            continue;
                        }
                        $filtered = [];
                        foreach ($productInfo['attribute'] as $item) {
                            $currentKey = $item['label'] . '|' . $item['channel'];
                            // 不在删除列表中的元素保留
                            if (!isset($deleteMap[$currentKey])) {
                                $filtered[] = $item;
                            }
                        }
                        $productInfo['attribute'] = $filtered;

                        $productInfo['userName'] = "system(zhouangang)";
                        $productInfo['action'] = "修复币种不一导致上架失败问题";
                        //$this->log(json_encode($productInfo['attribute'],JSON_UNESCAPED_UNICODE));
                        $this->log($sku . "该删");
                        //$this->log(json_encode($productInfo,JSON_UNESCAPED_UNICODE));
                        $resp = $curlService->s3015()->post("product-skus/updateProductSku?_id={$productInfo['_id']}", $productInfo);
                        $this->log(json_encode($resp, JSON_UNESCAPED_UNICODE));
                    }
                }

    //            if (sizeof($export) > 0){
    //                $excelUtils = new ExcelUtils();
    //                $filePath = $excelUtils->downloadXlsx([
    //                    "skuId",
    //                    "channel",
    //                    "MSRPWithTax_currency"
    //                ], $export, "币种修复_" . date("YmdHis") . ".xlsx");
    //            }


            }


        }

    public function fastProductSkuCurrent()
        {
            $curlService = (new CurlService())->test();

            $skuIdList = [
                "a23100800ux0272"
            ];
            $targetChannel = ["amazon_us", "amazon_it"];
            $targetLabel = [
                'MSRPWithTax_currency',
                'MSRP_currency',
            ];
            $productIdMap = [];
            foreach (array_chunk($skuIdList, 100) as $chunk) {
                $productSkuList = DataUtils::getPageList($curlService->s3015()->get("product-skus/queryPage", [
                    "productId" => implode(",", $chunk),
                    "limit" => count($chunk)
                ]));
                if ($productSkuList) {
                    foreach ($productSkuList as $info) {
                        $productIdMap[$info['productId']] = $info;
                    }
                }
            }


            $export = [];
            foreach ($skuIdList as $sku) {
                if (isset($productIdMap[$sku])) {
                    $productInfo = $productIdMap[$sku];


                    foreach ($targetChannel as $ch) {
                        foreach ($targetLabel as $lb) {

                            $found = false;
                            // 遍历现有属性，查找匹配的channel和label
                            foreach ($productInfo['attribute'] as &$info) {
                                if ($info['channel'] === $ch && $info['label'] === $lb) {
                                    // 找到匹配项，更新value
                                    $oldValue = $info['value'];
                                    $info['value'] = "GBP";
                                    $this->log($sku . " 渠道：{$info['channel']} {$info['label']} 值已更新 - 旧值: {$oldValue}, 新值: GBP");
                                    $found = true;
                                }
                            }

                            // 如果没有找到匹配项，新增属性
                            if (!$found) {
                                $newAttribute = [
                                    'channel' => $ch,
                                    'label' => $lb,
                                    'value' => "GBP"
                                ];
                                $productInfo['attribute'][] = $newAttribute;
                                $this->log($sku . " 新增属性 - 渠道：{$ch} 标签：{$lb} 值：GBP");
                            }


                        }
                    }

                    $productInfo['userName'] = "system(zhouangang)";
                    $productInfo['action'] = "测试币种，增加不一样的币种";
                    $resp = $curlService->s3015()->post("product-skus/updateProductSku?_id={$productInfo['_id']}", $productInfo);
                    $this->log(json_encode($resp, JSON_UNESCAPED_UNICODE));
                }
            }
        }

    public function fixProductSkuCategory()
        {
            $fileFitContent = (new ExcelUtils())->getXlsxData("../export/1005个sgu需导入中文目录.xlsx");
            $fitmentSkuMap = [];
            if (sizeof($fileFitContent) > 0) {

                $issetCategoryMap = [];
                foreach ($fileFitContent as $info) {
                    if ($info['categoryId'] && !isset($issetCategoryMap[$info['categoryId']])) {
                        $request = new RequestUtils('pro');
                        $categoryIdInfo = $request->getCategoryIdInfoV2($info['categoryId']);

                        $issetCategoryMap[$info['categoryId']] = $categoryIdInfo;
                    }
                }


                $curlService = (new CurlService())->pro();

                $list = array_unique(array_column($fileFitContent, "SGU"));
                $map = [];
                foreach (array_chunk($list, 120) as $chunkList) {
                    $infoList = DataUtils::getPageList($curlService->s3015()->get("product-skus/queryPage", [
                        "productId" => implode(",", $chunkList),
                        "limit" => 120
                    ]));
                    if ($infoList) {
                        foreach ($infoList as $info) {
                            $map[$info['productId']] = $info;
                        }
                    }
                }


                foreach ($fileFitContent as $info) {

                    if (isset($map[$info['SGU']])) {
                        $productInfo = $map[$info['SGU']];
                        $categoryIdInfo = $issetCategoryMap[$info['categoryId']] ?? null;

                        if (!$categoryIdInfo) {
                            $this->log("没有找到{$info['categoryId']}对应的中文分类");
                            continue;
                        }
                        $productInfo['category'] = $categoryIdInfo['categoryId'];
                        $productInfo['categoryPaths'] = $categoryIdInfo['categoryIds'];
                        $productInfo['cn_Category'] = $categoryIdInfo['cnCategoryFullPath'];


                        $productInfo['action'] = "业务需要导入中文分类";
                        $productInfo['userName'] = "system(zhouangang)";

                        $this->log(json_encode($productInfo, JSON_UNESCAPED_UNICODE));
                        $resp = $curlService->s3015()->post("product-skus/updateProductSku?_id={$productInfo['_id']}", $productInfo);
                        if ($resp) {

                        }
                    }
                }
            }


        }

    public function fixProductOpt()
        {
            $fileContent = (new ExcelUtils())->getXlsxData("../export/资料图片工单.xlsx");

            $curlService = new CurlService();
            $curlService = $curlService->pro();

    //        $curlService->s3044()->delete("pa_product_optimizations","65d8477cac548325e88fd2c4");
    //        die(1);

            if (sizeof($fileContent) > 0) {


                foreach ($fileContent as $info) {
                    $dataInfo = DataUtils::getPageListInFirstDataV2($curlService->s3044()->get("pa_product_optimizations/queryPage", [
                        "limit" => 1,
                        "skuId" => $info['skuId'],
                        "applyReasons" => $info['修改来源']
                    ]));

                    if (empty($dataInfo)) {
                        continue;
                    }

                    $option = "";
                    $status = 0;
                    $operator = "";

                    if ($info['状态'] == "作废") {
                        continue;
                        $option = '执行已作废';
                        $status = 102;
                        $operator = '执行';

                        $dataInfo['statusArrays'][] = [
                            "option" => $option,
                            "status" => $status,
                            "optionBy" => $info['执行人'],
                            "optionTime" => date("Y-m-d H:i:s", time()) . "Z",
                        ];
                        $dataInfo['remarkArrays'][] = [
                            "option" => $option,
                            "optionBy" => $info['执行人'],
                            "optionTime" => date("Y-m-d H:i:s", time()) . "Z",
                            "remark" => "运营要求取消执行",
                        ];

                        $updateData = [
                            "executor" => explode(",", $info['执行人']),
                            "modifiedBy" => $info['执行人'],
                            "modifiedOn" => $dataInfo['modifiedOn'],
                            "status" => $status,
                            "deleteBy" => $info['执行人'],
                            "deleteDate" => date("Y-m-d H:i:s", time()) . "Z",
                            "deleteRemarks" => "运营要求取消执行",
                            "statusArrays" => $dataInfo['statusArrays'],
                            "remarkArrays" => $dataInfo['remarkArrays'],
                        ];

                        $curlService->s3044()->put("pa_product_optimizations/{$dataInfo['_id']}", $updateData);


                    } else if ($info['状态'] == "已完成") {
                        $exectorList = explode(",", $info['执行人']);

                        $updateData = [
                            "executor" => $exectorList,
                            "modifiedBy" => $exectorList[0],
                            "modifiedOn" => $dataInfo['modifiedOn'],
                            "status" => 3,
                            "completedBy" => $exectorList[0],
                            "completedDate" => date("Y-m-d H:i:s", time()) . "Z",
                        ];

                        $curlService->s3044()->put("pa_product_optimizations/{$dataInfo['_id']}", $updateData);

                    }


                }


            }


        }

    public function deleteProductSku()
        {
            $curlService = new CurlService();
            $curlService = $curlService->pro();

            $list = [

                "a25120900ux0039",
                "a25120900ux0040",
                "a25120900ux0041",
                "a25120900ux0042",
                "a25120900ux0044",
                "a25120900ux0045",
                "a25120900ux0047",
                "a25120900ux0048",
                "a25120900ux0050",
                "a25120900ux0051",
                "a25120900ux0053",
                "a25120900ux0054",
                "a25120900ux0056",
                "a25120900ux0057",
                "a25120900ux0059",
                "a25120900ux0060",
                "a25120900ux0062",
                "a25120900ux0063",
                "a25120900ux0065",
                "a25120900ux0066",
                "a25120900ux0068",
                "a25120900ux0069",
                "a25120900ux0071",
                "a25120900ux0072",
                "a25120900ux0074",
                "a25120900ux0075",
                "a25120900ux0076",
                "a25120900ux0078",
                "a25120900ux0080",
                "a25120900ux0081",

            ];

            $dataLIst = DataUtils::getPageList($curlService->s3015()->get("product-skus/queryPage", [
                "limit" => 1000,
                "page" => 1,
                "productId" => implode(",", $list)
            ]));
            if ($dataLIst) {
                foreach ($dataLIst as $info) {
                    $curlService->s3015()->delete("product-skus/{$info['_id']}");
                }
            }

            $dataLIst1 = DataUtils::getPageList($curlService->s3015()->get("product_base_infos/queryPage", [
                "limit" => 1000,
                "page" => 1,
                "productId_in" => implode(",", $list)
            ]));
            if ($dataLIst1) {
                foreach ($dataLIst1 as $info) {
                    $curlService->s3015()->delete("product_base_infos/{$info['_id']}");
                }
            }

            $dataLIst12 = DataUtils::getQueryList($curlService->s3015()->get("/sgu-sku-scu-maps/query", [
                "skuScuId_in" => implode(",", $list),
                "limit" => 1000,
            ]));
            if ($dataLIst12) {
                foreach ($dataLIst12 as $sguInfo) {
                    $curlService->s3015()->delete("sgu-sku-scu-maps/{$sguInfo['_id']}");
                }
            }

        }

    public function deleteTestSku()
        {
            $c = (new CurlService())->test();

            $list = [
                "g26052100ux0018"
            ];

    //        foreach ($list as $productId) {
    //
    //            $list = DataUtils::getPageList($c->s3015()->get("product-skus/queryPage", [
    //                "limit" => 1,
    //                "productId" => $productId
    //            ]));
    //            foreach ($list as $info) {
    //                $c->s3015()->delete("product-skus/{$info['_id']}");
    //                $this->log(json_encode($info, JSON_UNESCAPED_UNICODE));
    //            }
    //            $resp = $c->s3015()->get("sku-sale-statuses/queryPage", ["skuId" => $productId]);
    //            $skuSaleStatusList = DataUtils::getPageListInFirstData($resp);
    //            if ($skuSaleStatusList) {
    //                $this->log(json_encode($skuSaleStatusList, JSON_UNESCAPED_UNICODE));
    //                $c->s3015()->delete("sku-sale-statuses/{$skuSaleStatusList['_id']}");
    //            }
    //
    //        }

            $c->s3015()->delete("sgu-sku-scu-maps/6a0ec90236e8c67a2b534017");


    //        $sguMapData = DataUtils::getQueryListInFirstDataV3($c->s3015()->get("sgu-sku-scu-maps/query",[
    //            "sguId" => $productId
    //        ]));
    //        if($sguMapData){
    //            $c->s3015()->delete("sgu-sku-scu-maps/{$sguMapData['_id']}");
    //        }


        }

    public function checkPaProduct()
        {
            $c = (new CurlService())->pro();

            $qdBillNoList = [];
            $page = 1;
            do {
                $this->log($page);
                $ll = DataUtils::getPageList($c->ux168()->get("product_development_lists/queryPage", [
                    "status" => 6,
                    "verticalDepartment" => "PA",
    //                "productListNo_in" => "QD20240102001,QD20240102002",
                    "page" => $page,
                    "limit" => 1000
                ]));
                if (count($ll) == 0) {
                    break;
                }
                foreach ($ll as $info) {
                    $qdBillNoList[] = $info['productListNo'];
                }
                $page++;
                sleep(1);
            } while (true);

            exit(1111111111);
            $slist = [];
            foreach (array_chunk($qdBillNoList, 50) as $chunk) {
                $ll = DataUtils::getPageList($c->s3015()->get("pa_products/queryPage", [
                    "limit" => 100,
                    "productListNo_in" => implode(",", $chunk)
                ]));
                if ($ll) {
                    $slist = array_merge($slist, $ll);
                }
            }


            if ($slist) {
                $exportList = [];
                foreach ($slist as $info) {
                    $export = [];
                    $export['productListNo'] = $info['productListNo'];
                    $export['productlineId'] = $info['productlineId'];
                    if (count($info['ceNumber']) == 0) {
                        $export['ceNumber'] = "无";
                    } else {
                        $export['ceNumber'] = implode(",", array_column($info['ceNumber'], 'ceBillNo'));
                    }
                    if ($info['categoryId']) {
                        $export['categoryId'] = $info['categoryId'];
                    } else {
                        //没有分类取详情
                        $detail = DataUtils::getPageListInFirstData($c->s3015()->get("pa_product_details/queryPage", [
                            "paProductId" => $info['_id']
                        ]));
                        if ($detail) {
                            $export['categoryId'] = $detail['categoryId'];
                        }
                    }

                    $export['consignmentName'] = $info['consignmentName'];
                    $export['consignorId'] = $info['consignorId'];
                    $export['developer'] = $info['developer'];
                    $export['traceMan'] = $info['traceMan'];
                    $exportList[] = $export;
                }

                if ($exportList) {
                    $excelUtils = new ExcelUtils();
                    $downloadOssLink = "QD单生产环境数据检查_" . date("YmdHis") . ".xlsx";
                    $downloadOssPath = $excelUtils->downloadXlsx(["productListNo", "产品线", "CE单", "categoryId", "分配寄卖商", "寄卖商id", "开发", "运营"], $exportList, $downloadOssLink);


                }

            }


        }

    public function getProductSku()
        {
            $curlService = new CurlService();
            $curlService = $curlService->test();

            $list = DataUtils::getPageList($curlService->s3015()->get("product-skus/queryPage", [
                "verticalId" => "CR201706060001",
                "productType" => "SKU",
                "columns" => "productId",
                "limit" => 5000,
            ]));
            $this->log(json_encode(array_column($list, "productId"), JSON_UNESCAPED_UNICODE));

        }

}

// === 入口 ===
$method = isset($argv[1]) ? $argv[1] : 'syncProduct';
$controller = new ProductSync();
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
