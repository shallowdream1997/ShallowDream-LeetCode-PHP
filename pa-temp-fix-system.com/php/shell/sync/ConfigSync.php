<?php
/**
 * 配置同步
 * 从 SyncCurlController 拆分
 * Class ConfigSync
 */

require_once dirname(__FILE__) . '/../../../php/bootstrap.php';

class ConfigSync extends CrudService
{
    public function __construct()
    {
        parent::__construct("sync/configsync");
    }

    public function updateSkuSellerConfig()
        {
            $c = (new CurlService())->pro();

            $skus = [
                "a26012900ux1714",
                "a26012900ux1715",
                "a26012900ux1717",
                "a26012900ux1720",
                "a26012900ux1723"
            ];
            $originSku = "a23022300ux0090";

            $channelAndSellerMap = ["amazon_us" => "amazon", "amazon_uk" => "amazon_uk2", "amazon_ca" => "amazon_ca2", "amazon_au" => "amazon_au"];


            $channels = array_keys($channelAndSellerMap);
            $list = DataUtils::getPageList($c->s3015()->get("sku-seller-configs/queryPage", [
                "skuId" => implode(",", $skus),
                "channel" => implode(",", $channels),
                "limit" => 1000
            ]));

            if ($list) {

                $skuChannelMap = [];
                foreach ($list as $item) {
                    $skuChannelMap[$item['skuId']][$item['channel']] = $item;
                }
                $orign = DataUtils::getPageList($c->s3015()->get("sku-seller-configs/queryPage", [
                    "skuId" => $originSku,
                    "channel" => implode(",", $channels),
                    "limit" => 1000
                ]));
                $map = [];
                if ($orign) {
                    foreach ($orign as $item) {
                        $map[$item['skuId']][$item['channel']] = $item;
                    }
                }

                foreach ($skuChannelMap as $skuId => $channelMap) {
                    foreach ($channelMap as $channel => $item) {
                        if (isset($map[$originSku][$channel])) {
                            $cankao = $map[$originSku][$channel];

                            $cankao['_id'] = $item['_id'];
                            $cankao['skuId'] = $skuId;
                            $cankao['brand'] = "X AUTOHAUX";
                            $cankao['createdOn'] = $item['createdOn'];
                            $cankao['modifiedOn'] = $item['modifiedOn'];
                            $cankao['createdBy'] = $item['createdBy'];
                            $cankao['modifiedBy'] = 'system(zhouangang)';

                            //先删后增
                            $this->log(json_encode($cankao, JSON_UNESCAPED_UNICODE));


                            $c->s3015()->delete("sku-seller-configs/{$item['_id']}");
                            $c->s3015()->post("sku-seller-configs", $cankao);
                        }
                    }
                }


            }

        }

    public function syncSkuSellerConfig()
        {
            $curlService = new CurlService();
            $info = DataUtils::getPageListInFirstData($curlService->pro()->s3015()->get("seller-configs/queryPage", [
                "sellerId" => "amazon_ca_ifn",
            ]));
            if ($info) {
                unset($info['_id']);
                $curlService->uat()->s3015()->post("seller-configs", $info);
            }


            $info1 = DataUtils::getPageListInFirstData($curlService->pro()->s3015()->get("sku-seller-configs/queryPage", [
                "sellerId" => "amazon_ca_ifn",
                "skuId" => "a24101800ux0691",
            ]));
            if ($info1) {
                unset($info1['_id']);
                $curlService->uat()->s3015()->post("sku-seller-configs", $info1);
            }
        }

    public function updatePaGoodsSourceManage()
        {
    //        $curlService = new CurlService();
    //        $list = [];
    //        for ($page = 1; $page < 25; $page++) {
    //            $this->log("{$page}");
    //            $resp = DataUtils::getPageDocList($curlService->pro()->s3044()->get("pa_sku_materials/queryPage", [
    //                "limit" => 5000,
    //                "createdOn_gt" => "2024-06-01",
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
    //            $filePath = $excelUtils->downloadXlsx(["skuId","model"],$list,"sku资料呈现热销词.xlsx");
    //            $this->log($filePath);
    //        }
            $fileContent = (new ExcelUtils())->getXlsxData("../export/uploads/default/lianjie.xlsx");

            $curlService = new CurlService();
            $curlService = $curlService->pro();

            if (sizeof($fileContent) > 0) {

                $curlService = new CurlService();

                $curlService = $curlService->pro();

                $skuIdList = array_column($fileContent, "skuid");
                $map = [];
                foreach ($fileContent as $info) {
                    $map[$info['skuid']] = $info['ppmcaigoulianjie'];
                }
                $list = [];
                foreach (array_chunk($skuIdList, 200) as $chunk) {
                    $list = DataUtils::getPageList($curlService->s3015()->get("pa_goods_source_manages/queryPage", [
                        "limit" => 200,
                        "skuId_in" => implode(",", $chunk),
                    ]));
                    if (count($list) > 0) {
                        foreach ($list as $item) {
                            if (isset($map[$item['skuId']]) && $map[$item['skuId']]) {
                                $item['purchaseLink'] = $map[$item['skuId']];
                                $item['modifiedBy'] = "zhouangang(修复采购链接)";
                                $curlService->s3015()->put("pa_goods_source_manages/{$item['_id']}", $item);
                            } else {
                                $this->log("{$item['skuId']}没有采购链接");
                            }
                        }
                    }
                }

            }


        }

    public function updateEuSharedWarehouseFlowTypePriority()
        {
            $curlService = new CurlService();
            $curlService = $curlService->test();
            $info = DataUtils::getPageListInFirstData($curlService->s3015()->get("option-val-lists/queryPage", [
                "optionName" => "EuSharedWarehouseFlowTypePriority"
            ]));
            $config = [
                "DE",
                "FR",
                "ES",
                "IT",
                "NL",
                "BE",
                "PL",
                "SE"
            ];
            if ($info) {
                $list = [];

                $ssss = $config;
                for ($i = 0; $i < count($config); $i++) {
                    $platform = $config[$i];
                    $list[$platform] = [];
                    // 检查 $platform 是否在 $config 中
                    // 创建一个新的数组
                    $newConfig = $config; // 复制原始数组
                    // 检查 $platform 是否在 $newConfig 中
                    if (in_array($platform, $newConfig)) {
                        // 移除元素并将其放在数组首位
                        $newConfig = array_diff($newConfig, [$platform]); // 移除 $platform
                        array_unshift($newConfig, $platform); // 将 $platform 添加到首位
                    }
                    $list[$platform] = $newConfig;
                }
                $list["defult"] = $config;

                $info['optionVal'] = $list;
                $ssss[] = 'defult';
                $optionValCn = $ssss;
                $info['optionValCn'] = $optionValCn;
                $info['modifiedBy'] = "system(zhouangang)";
                $curlService->s3015()->put("option-val-lists/{$info['_id']}", $info);
            }
        }

    public function updateFcuProductLine()
        {
            //todo 同步前请清空表pa_all_vertical_monthly_target 和pa_all_vertical_monthly_sales

            $env = "pro";
            $fileContent = (new ExcelUtils())->getXlsxData("../export/skufcu.xlsx");

            $curlService = new CurlService();
            $curlService = $curlService->pro();

            if (sizeof($fileContent) > 0) {
                $skuIdList = array_column($fileContent, "skuId");
                $fcuIdList = array_column($fileContent, "fcuId");

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


                $fculist = [];
                foreach (array_chunk($fcuIdList, 200) as $chunk) {
                    $fcuResult = DataUtils::getPageDocList($curlService->s3044()->get("fcu_sku_maps/queryPage", [
                        "fcuId_in" => implode(",", $chunk),
                        "limit" => 200
                    ]));
                    if ($fcuResult && count($fcuResult) > 0) {
                        foreach ($fcuResult as $info) {
                            $fculist[$info['fcuId']] = $info;
                        }
                    }
                }


                foreach ($fileContent as $item) {
                    $skuId = $item['skuId'];
                    if (isset($skuIdProductLineMap[$skuId])) {
                        $product_operator_mainInfo_id = $skuIdProductLineMap[$skuId]['product_operator_mainInfo_id'];

                        if (isset($fculist[$item['fcuId']])) {
                            $fcuInfo = $fculist[$item['fcuId']];
                            if ($fcuInfo['productLineId']) {
                                continue;
                            }
                            $fcuInfo['productLineId'] = $product_operator_mainInfo_id;
                            $fcuInfo['modifiedBy'] = "zhouangang";

                            $sss = $curlService->s3044()->put("fcu_sku_maps/{$fcuInfo['_id']}", $fcuInfo);
                            $this->log("更新产品线id成功" . json_encode($sss, JSON_UNESCAPED_UNICODE));
                        } else {
                            $this->log("找不到fcu：{$fcuInfo['fcuId']}");
                        }

                    } else {
                        $this->log("找不到产品线：{$skuId} - {$item['fcuId']}");
                    }

                    //https://master-nodejs-poms-list-nest.ux168.cn/api/fcu_sku_maps/queryPage

                }

            }


        }

    public function syncAllVerticalMonthlTargets()
        {
            //todo 同步前请清空表pa_all_vertical_monthly_target 和pa_all_vertical_monthly_sales

            $curlService = new CurlService();
            $page = 1;
            $pages = 1;
            $allList = [];
            do {
                $resp = $curlService->pro()->s3044()->get("pa_all_vertical_monthly_targets/queryPage", [
                    "limit" => 1000,
                    "page" => $page
                ]);
                $list = DataUtils::getPageList($resp);
                if (count($list['data']) > 0) {
                    $allList = array_merge($allList, $list['data']);
                    $pages = $list['pages'];
                } else {
                    break;
                }
                $page++;
            } while ($page <= $pages);

            if (count($allList) > 0) {
                $res = $curlService->uat()->s3044()->post("pa_all_vertical_monthly_targets/createBatch", $allList);
                $this->log("添加：" . json_encode($res, JSON_UNESCAPED_UNICODE));
            }

            $page = $pages = 1;
            $allList = [];
            do {
                $resp = $curlService->pro()->s3047()->get("pa_all_vertical_monthly_saless/queryPage", [
                    "limit" => 1000,
                    "page" => $page
                ]);
                $list = DataUtils::getPageList($resp);
                if (count($list['data']) > 0) {
                    $allList = array_merge($allList, $list['data']);
                    $pages = $list['pages'];
                } else {
                    break;
                }
                $page++;
            } while ($page <= $pages);

            if (count($allList) > 0) {
                foreach ($allList as $info) {
                    $res = $curlService->uat()->s3047()->post("pa_all_vertical_monthly_saless", $info);
                    $this->log("添加：" . json_encode($res, JSON_UNESCAPED_UNICODE));
                }

            }

            $page = $pages = 1;
            $allList = [];
            do {
                $resp = $curlService->pro()->s3047()->get("pa_vertical_daily_saless/queryPage", [
                    "limit" => 1000,
                    "year" => "2025",
                    "page" => $page
                ]);
                $list = DataUtils::getPageList($resp);
                if (count($list['data']) > 0) {
                    $allList = array_merge($allList, $list['data']);
                    $pages = $list['pages'];
                } else {
                    break;
                }
                $page++;
            } while ($page <= $pages);

            if (count($allList) > 0) {
                foreach ($allList as $info) {
                    $res = $curlService->uat()->s3047()->post("pa_vertical_daily_saless", $info);
                    $this->log("添加：" . json_encode($res, JSON_UNESCAPED_UNICODE));
                }
            }

        }

    public function syncBusinessModulesToTest()
        {

            $curlService = (new CurlService())->pro();

            $list = DataUtils::getPageList($curlService->ux168()->get("business_modules/queryPage", [
                "vertical" => "PA",
                "activeStatus" => 1,
                "limit" => 1000,
                "page" => 1
            ]));
            if (sizeof($list) > 0) {

                $proListMap = [];
                foreach ($list as $info) {
                    $proListMap[$info['groupId'] . $info['supplierId']] = $info;
                }

                $curlServicet = (new CurlService())->uat();
                $testList = DataUtils::getPageList($curlServicet->ux168()->get("business_modules/queryPage", [
                    "vertical" => "PA",
                    "activeStatus" => 1,
                    "limit" => 1000,
                    "page" => 1
                ]));
                if (sizeof($testList) > 0) {
                    $testListMap = [];
                    foreach ($testList as $info) {
                        $testListMap[$info['groupId'] . $info['supplierId']] = $info;
                    }

                    foreach ($proListMap as $key => $info) {
                        if (!isset($testListMap[$key])) {
                            $curlServicet->ux168()->post("business_modules", $info);
                        } else {
                            $curlServicet->ux168()->delete("business_modules/{$testListMap[$key]['_id']}");
                            $curlServicet->ux168()->post("business_modules", $info);
                        }


                    }

                }


            }


        }

    public function deleltePlatformFees()
        {

    //        http://master-angular-nodejs-poms-list-manage.ux168.cn:60015/api/channel-platform-fees/queryPage

            $curlService = (new CurlService())->pro();
            $fileFitContent = (new ExcelUtils())->getXlsxData("../export/US定价参数修改 20260206.xlsx");


            $list = [];
            if (sizeof($fileFitContent) > 0) {
                foreach ($fileFitContent as $info) {


                    $data = DataUtils::getResultData($curlService->s3015()->get("channel-platform-fees/{$info['_id']}", []));
                    //$this->redis->hSet("channelPlatformFeeBak", $info['_id'], json_encode($data,JSON_UNESCAPED_UNICODE));

                    $this->log(json_encode($data));

    //                $da = $this->redis->hGet("channelPlatformFeeBak", $info['_id']);
    //
    //                if ($da){
    //                    $data = json_decode($da,true);
    //                    if ($data){
    //                        $curlService->s3015()->post("channel-platform-fees",$data);
    //                    }
    //
    //                }

                }
            }

            //$this->log(json_encode($list,JSON_UNESCAPED_UNICODE));

        }

        /**
         */

    public function deleteSpmoDetails()
        {
            $curlService = new CurlService();
            $curlService = $curlService->pro();

            $page = 1;
            $list = [];
            do {
                $this->log($page);
                $ss = DataUtils::getPageList($curlService->s3044()->get("pa_spmo_details/queryPage", [
                    "batchNo" => "20251013_张桂源_001",
                    "limit" => 1000,
                    "page" => $page
                ]));
                if (count($ss['data']) == 0) {
                    break;
                }
                foreach ($ss['data'] as $info) {
                    $createDate = new DateTime($info['createdOn']);
                    $today = new DateTime('now');
                    $today->setTime(0, 0, 0); // 设置时间为 00:00:00 以便比较日期

                    if ($createDate->format('Y-m-d') === $today->format('Y-m-d')) {
                        // 如果等于今天，则进入 if 语句块
                        $this->log("createOn 是今天的日期" . $info['skuId']);
                        $curlService->s3044()->delete("pa_spmo_details/{$info['_id']}");
                    }

                }

                $page++;
            } while (true);


        }

    public function saveReceiveIpCheck()
        {
            $curlSsl = (new CurlService())->pro();
            $info = DataUtils::getPageListInFirstData($curlSsl->s3010()->get("problem-product-infos/queryPage", [
                "skuId" => "a24080100ux0303",
                "type" => "tort",
            ]));
            if ($info) {
    //            $info['remark'] = "Product IP Issue";
    //            $info['newBrandName'] = "K LOGO";
    //            $info['url'] = "https://branddb.wipo.int/en/advancedsearch/brand/US502015086736339";
                $info['status'] = 'new';
                $result = DataUtils::getPageListInFirstData($curlSsl->s3010()->put("problem-product-infos/{$info['_id']}", $info));
                $this->log(json_encode($result, JSON_UNESCAPED_UNICODE));
                //再查
    //            $infoAft = DataUtils::getPageListInFirstData($curlSsl->s3010()->get("problem-product-infos/queryPage",[
    //                "skuId" => "a24080100ux0303",
    //                "type" => "tort",
    //            ]));
    //
    //            $data = [
    //                "id" => $infoAft['_id'],
    //                "lastModifiedOn" => $infoAft['modifiedOn'],
    //                "remark" => "Product IP Issue",
    //                "newBrandName" => "unbranded",
    //                "url" => "/",
    //                "description" => "/",
    //                "userName" => "fangxiaojuan"
    //            ];
    //            $resp = DataUtils::getResultData($curlSsl->s3010()->post("problem-product-infos/saveReceiveIpCheck",$data));
    //            if ($resp){
    //                $this->log(json_encode($resp,JSON_UNESCAPED_UNICODE));
    //            }
            }
        }

    public function updateSalesUserNameCancel2()
        {
            //清掉小语种2的
            $env = "pro";
            $userList = [
                [
                    "old" => "dengyiyi2",
                    "new" => "dengyiyi",
                ]
            ];

    //        $this->Mongo3009Sql($userList);
    //        $this->Mongo3015Sql($userList);
            $this->Mongo3044Sql($userList);


        }

    public function addOptionValListData()
        {
            $price_base_fields = [
                [
                    "feeType" => "ads",
                    "feeValue" => "0.05",
                    "currency" => "",
                    "remark" => "广告费率",
                    "categoryId" => "",
                    "addCondition" => [
                        [
                            "label" => "publishDate",
                            "value" => "2024-09-01",
                            "unit" => "",
                            "expressions" => ">"
                        ]
                    ]
                ],
                [
                    "feeType" => "ads",
                    "feeValue" => "0.05",
                    "currency" => "",
                    "remark" => "广告费率",
                    "categoryId" => "",
                    "addCondition" => [
                        [
                            "label" => "publishDate",
                            "value" => "2024-09-01",
                            "unit" => "",
                            "expressions" => ">"
                        ]
                    ]
                ],
            ];
            $unsalable_base_fields = [
                [
                    //绑定策略id
                    "strategy_main_id" => "1747933604497825793",
                    //绑定价格挡位设置id
                    "margin_position_main_id" => "1747942777125593089",
                    //滞销类型,1-海外仓滞销;2-中国仓滞销
                    "dull_sale_type" => 1
                ]
            ];
            echo json_encode($price_base_fields, JSON_UNESCAPED_UNICODE) . "\n";
            echo json_encode($unsalable_base_fields, JSON_UNESCAPED_UNICODE) . "\n";

        }

    public function updateSampleSku()
        {


            $env = "pro";
            $fileContent = (new ExcelUtils())->getXlsxData("../export/留样CP.xlsx");

            if (sizeof($fileContent) > 0) {
                $skuIdList = array_column($fileContent, "sku_id");

                $skuCPList = [];
                foreach (array_chunk($skuIdList, 200) as $chunk) {
                    $curlService = new CurlService();
                    $resp = $curlService->$env()->s3009()->get("market-analysis-reports/getSkuIdInfoByCpBillNoList", [
                        "cpBillNoListJsonEncode" => json_encode($chunk, JSON_UNESCAPED_UNICODE)
                    ]);
                    $list = DataUtils::getQueryList($resp);

                    if (count($list) > 0) {
                        foreach ($list as $info) {
                            $skuCPList[] = [
                                "sequenceId" => $info['sequenceId'],
                                "skuId" => $info['skuId'],
                                "ceBillNo" => $info['ceBillNo']
                            ];
                        }
                    }
                }


                $excelUtils = new ExcelUtils();
                $downloadOssLink = "sku和Cp号对应关系_" . date("YmdHis") . ".xlsx";
                $downloadOssPath = $excelUtils->downloadXlsx(["CP号", "skuId", "CE单"], $skuCPList, $downloadOssLink);


            }


        }


        /**
         * 回写CE单 到 预计采购清单
         */

}

// === 入口 ===
$method = isset($argv[1]) ? $argv[1] : 'updateSkuSellerConfig';
$controller = new ConfigSync();
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
