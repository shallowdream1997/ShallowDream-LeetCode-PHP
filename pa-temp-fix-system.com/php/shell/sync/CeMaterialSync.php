<?php
/**
 * CE资料同步
 * 从 SyncCurlController 拆分
 * Class CeMaterialSync
 */

require_once dirname(__FILE__) . '/../../../php/bootstrap.php';

class CeMaterialSync extends CrudService
{
    public function __construct()
    {
        parent::__construct("sync/cematerialsync");
    }

    public function ceWrite()
        {
            $env = "pro";

            $data = [
                "prePurchaseBillNo" => "DPMO251231003",
                "ceBillNo" => "CE202601050017",
                "operatorName" => "system(PA-CE回写)"
            ];
            $curlService1 = new CurlService();
            $curlService1->$env()->gateway()->getModule('pa');
            $resp3 = DataUtils::getNewResultData($curlService1->getWayPost($curlService1->module . "/scms/ce_bill_no/v1/writeBackAutarkyCeSkuToPrePurchase", $data));
            if ($resp3) {
                $this->log(json_encode($resp3, JSON_UNESCAPED_UNICODE));
            }
            die(1111);
            $pmoList = [
                "DPMO251231005-黎乾海",
            ];
            $curlService = new CurlService();
            foreach ($pmoList as $pmoBillNo) {
                $resp = $curlService->$env()->s3009()->get("market-analysis-reports/getMainSkuIdInfo", [
                    "batch" => $pmoBillNo
                ]);
                if (count($resp['result']) > 0) {
                    $pmoInfo = $resp['result'][0];
                    if ($pmoInfo) {
                        $resp1 = $curlService->$env()->s3009()->post("cmo-managements/masterQuery", [
                            "conditionsJsonEncode" => json_encode(["pmoBillNoList" => [$pmoInfo['pmoBillNo']]], JSON_UNESCAPED_UNICODE),
                            "entriesPerPage" => 10,
                            "orderBy" => "cmoBillNo desc",
                            "pageNumber" => 1
                        ]);
                        if ($resp1 && $resp1['result'] && $resp1['result']['cmoMasterResponse'] && $resp1['result']['cmoMasterResponse']['cmoMasters'] && count($resp1['result']['cmoMasterResponse']['cmoMasters']) > 0) {
                            foreach ($resp1['result']['cmoMasterResponse']['cmoMasters'] as $cmoBillNoInfo) {
                                if ($cmoBillNoInfo) {
                                    $resp2 = $curlService->$env()->s3009()->get("cmo-managements/cmoMasterProgress", [
                                        "cmoBillNo" => $cmoBillNoInfo['cmoBillNo'],
                                    ]);
                                    $list = [];
                                    if ($resp2['result'] && $resp2['result']['data']) {
                                        foreach ($resp2['result']['data'] as $sourceId => $ceList) {
                                            foreach ($ceList as $ceBillNo => $ceProcess) {
                                                if (strpos($ceBillNo, "CE") === 0) {

                                                    $prePurchaseBillNo = $pmoBillNo;
                                                    $position = strpos($pmoBillNo, '-');
                                                    if ($position !== false) {
                                                        // 从开始到 '-' 的位置截取字符串
                                                        $prePurchaseBillNo = substr($pmoBillNo, 0, $position);
                                                    }

                                                    $list[] = [
                                                        "prePurchaseBillNo" => $prePurchaseBillNo,
                                                        "ceBillNo" => $ceBillNo,
                                                        "operatorName" => "system(PA-CE回写)"
                                                    ];
                                                    break;
                                                }
                                            }
                                            break;
                                        }
                                    }

                                    if (count($list) > 0) {
                                        foreach ($list as $info) {
                                            if ($info['ceBillNo'] == "CE202508060047") {
                                                continue;
                                            }
                                            $this->log(json_encode($info, JSON_UNESCAPED_UNICODE));

                                            $curlService1 = new CurlService();
                                            $curlService1->$env()->gateway()->getModule('pa');
                                            $resp3 = DataUtils::getNewResultData($curlService1->getWayPost($curlService1->module . "/scms/ce_bill_no/v1/writeBackAutarkyCeSkuToPrePurchase", $info));
                                            if ($resp3) {
                                                $this->log(json_encode($resp3, JSON_UNESCAPED_UNICODE));
                                            }

                                        }

                                    }

                                }
                            }
                        }
                    }
                }

            }


        }

        //同步全公司年度目标看板:

    public function deleteCeMaterial()
        {
            $curlService = (new CurlService())->pro();
            $mainList = DataUtils::getPageDocList($curlService->s3044()->get("pa_ce_materials/queryPage", [
                "limit" => 1000,
                "batchName" => "QD202602240015",
            ]));
            if (count($mainList) > 0) {

                foreach ($mainList as $index => $main) {
                    if ($index == 1) {
                        continue;
                    }
                    $curlService->s3044()->delete("pa_ce_materials/{$main['_id']}");
                }
            }


        }

    public function updateCeMaterialPlatform()
        {
            $curlService = (new CurlService())->pro();
            $curlService->gateway();
            $this->getModule('pa');
            $list = $this->commonFindByParams("s3044", "pa_ce_materials", ["createdBy" => "P3-CreateCeSkuMaterialJob"], "pro");
            $batchNameList = [];
            if ($list) {
                $batchNameList = array_column($list, "batchName");
            }
            if (count($batchNameList) > 0) {
                $resp = DataUtils::getNewResultData($curlService->getWayPost($this->module . "/sms/sku/info/material/v1/findPrePurchaseBillWithSkuForSkuMaterialInfo", $batchNameList));
                $platformMap = [];
                if ($resp) {
                    foreach ($resp as $item) {
                        $platformMap[$item['prePurchaseBillNo']] = $item;
                    }
                }
                foreach (array_chunk($batchNameList, 150) as $chunkList) {
                    $mainInfoList = $this->commonFindByParams("s3044", "pa_ce_materials", ["batchName_in" => implode(",", $chunkList)], "pro");
                    if (count($mainInfoList) > 0) {
                        foreach ($mainInfoList as $mainInfo) {
                            $canUpdate = false;
                            if (!$mainInfo['platform'] && isset($platformMap[$mainInfo['batchName']]) && isset($platformMap[$mainInfo['batchName']]['platform'])) {
                                $mainInfo['platform'] = $platformMap[$mainInfo['batchName']]['platform'];
                                $canUpdate = true;
                            }
                            if (!$mainInfo['ebayTraceMan'] && isset($platformMap[$mainInfo['batchName']]) && isset($platformMap[$mainInfo['batchName']]['minorSalesUserName'])) {
                                $mainInfo['ebayTraceMan'] = $platformMap[$mainInfo['batchName']]['minorSalesUserName'];
                                $canUpdate = true;
                            }
                            if ($canUpdate) {
                                $this->commonUpdate("s3044", "pa_ce_materials", $mainInfo, "pro");
                                $this->log("更新批次的平台数据：{$mainInfo['batchName']} - {$mainInfo['platform']} - {$mainInfo['ebayTraceMan']}");
                            }

                        }
                    }
                }
            }


        }

    public function fixCeMaterial()
        {
            $curlService = (new CurlService())->pro();


            $fileFitContent = (new ExcelUtils())->getXlsxData("../export/uploads/default/资呈_20250717203220.xlsx");
            $fitmentSkuMap = [];
            if (sizeof($fileFitContent) > 0) {
                $list = [];
                foreach ($fileFitContent as $info) {
                    if ($info['修改前'] == '/' && $info['修改后'] == '/') {
                        $this->log("没有任何修改");
                        continue;
                    }

                    $ceBillNo = $info['ceBillNo'];
                    $this->log("{$info['备注']}");

                    // 提取 "操作类型"
                    $colonPos = strpos($info['备注'], '：');
                    if ($colonPos !== false) {
                        $action = substr($info['备注'], 0, $colonPos);
                        $remaining = substr($info['备注'], $colonPos + 3); // 跳过 "：a"
                    } else {
                        // 处理 "保存a25062700ux2136" 这种情况
                        $action = substr($info['备注'], 0, 6); // 取前6个字符（"保存"）
                        $remaining = substr($info['备注'], 6); // 剩下的部分
                    }

                    // 提取 "a编号" 和 "后缀"
                    $hyphenPos = strpos($remaining, '-');
                    if ($hyphenPos !== false) {
                        $aNumber = substr($remaining, 0, $hyphenPos);
                        $suffix = substr($remaining, $hyphenPos + 1);
                    } else {
                        $aNumber = $remaining;
                        $suffix = '';
                    }

                    $this->log("{$action}");
                    $this->log("{$aNumber}");
                    $this->log("{$suffix}");
                    if ($action == '保存') {
                        $this->log("保存，不读");
                        continue;
                    }
                    if (!isset($list[$aNumber])) {
                        $list[$aNumber] = [
                            "ceBillNo" => $ceBillNo
                        ];
                    }
                    if ($action == '导入') {
                        $this->log("导入");
                        if ($suffix == '车型') {
                            $list[$aNumber]["fitment"] = $info['修改后'];
                        } else if ($suffix == '核心词') {
                            $list[$aNumber]["keywords"] = $info['修改后'];
                        } else if ($suffix == 'cpAsin') {
                            $list[$aNumber]["cpAsin"] = $info['修改后'];
                        }
                    }
                    if ($action == '更新') {
                        $this->log("更新");
                        if ($suffix == '车型') {
                            $list[$aNumber]["fitment"] = $info['修改后'];
                        } else if ($suffix == '核心词') {
                            $list[$aNumber]["keywords"] = $info['修改后'];
                        } else if ($suffix == 'cpAsin') {
                            $list[$aNumber]["cpAsin"] = $info['修改后'];
                        }
                    }


                }


                $exportList = [];
                foreach ($list as $skuId => $item) {
                    $exportList[] = [
                        "skuId" => $skuId,
                        "ceBillNo" => $item['ceBillNo'] ?? "",
                        "fitment" => $item['fitment'] ?? '[]',
                        "keywords" => $item['keywords'] ?? '[]',
                        "cpAsin" => $item['cpAsin'] ?? '[]',
                    ];
                }

                if (count($exportList) > 0) {

                    $excelUtils = new ExcelUtils();
                    $downloadOssLink = "导出资呈数据_" . date("YmdHis") . ".xlsx";
                    $downloadOssPath = $excelUtils->downloadXlsx(["skuId", "ceBillNo", "fitment", "keywords", "cpAsin"], $exportList, $downloadOssLink);
                    $this->log("导出内容");


                }

            }

        }

    public function fixCeMaterialT()
        {
            $curlService = (new CurlService())->pro();

            foreach ([
                         "CE202507180079"
                     ] as $ceBillNo) {

                $resp = DataUtils::getPageDocList(
                    $curlService->s3044()->get("pa_ce_materials/queryPage", [
                        "ceBillNo" => $ceBillNo,
                    ])
                );
                if ($resp) {
                    $info = $resp[0];

                } else {
                    $this->log("找不到数据：{$ceBillNo}");
                }
            }

        }

    public function fixCeMaterialS()
        {
            $curlService = (new CurlService())->pro();

            $fileFitContent = (new ExcelUtils())->getXlsxData("../export/uploads/default/导出资呈数据_20250718113804.xlsx");
            if (sizeof($fileFitContent) > 0) {
                $ceBillNoMap = [];
                foreach ($fileFitContent as $info) {
                    $ceBillNoMap[$info['ceBillNo']][] = [
                        "skuId" => $info['skuId'],
                        "fitment" => json_decode($info['fitment'], true),
                        "keywords" => json_decode($info['keywords'], true),
                        "cpAsin" => json_decode($info['cpAsin'], true),
                    ];
                }

                if (count($ceBillNoMap) > 0) {

                    foreach ($ceBillNoMap as $ceBillNo => $list) {

                        $resp = DataUtils::getPageDocList(
                            $curlService->s3044()->get("pa_ce_materials/queryPage", [
                                "ceBillNo" => $ceBillNo,
                            ])
                        );
                        if ($resp) {
                            $info = $resp[0];
                            if ($info['status'] == "materialComplete") {
                                //资料发布的需要修复数据
                                if (count($list) > 0) {
                                    $this->log("资料发布了需要修复：{$ceBillNo}");
                                    foreach ($list as $dataInfo) {
                                        $respD = DataUtils::getPageDocList(
                                            $curlService->s3044()->get("pa_sku_materials/queryPage", [
                                                "ceBillNo" => $ceBillNo,
                                                "skuId" => $dataInfo['skuId'],
                                                "limit" => 1
                                            ])
                                        );
                                        if ($respD) {
                                            $detailInfo = $respD[0];
                                            $detailInfo['fitment'] = $dataInfo['fitment'];
                                            $detailInfo['keywords'] = $dataInfo['keywords'];
                                            $detailInfo['cpAsin'] = $dataInfo['cpAsin'];
                                            $detailInfo['modifiedBy'] = "system(fix-angang)";


                                            $this->log(json_encode($detailInfo, JSON_UNESCAPED_UNICODE));


                                            $ss = $curlService->s3044()->put("pa_sku_materials/{$detailInfo['_id']}", $detailInfo);
                                            if ($ss) {
                                                $this->log("更新完毕");
                                            }
                                        }
                                    }

                                }

                            } else {
                                $this->log("资料未发布，可以不用修复：{$ceBillNo}");
                            }
                        } else {
                            $this->log("找不到数据：{$ceBillNo}");
                        }

                    }


                }

            }

        }

    public function fixCeMaterialSSSS()
        {
            $curlService = (new CurlService())->pro();

            $fileFitContent = (new ExcelUtils())->getXlsxData("../export/uploads/20250731修复资呈数据.xlsx");
            if (sizeof($fileFitContent) > 0) {
                $ceBillNoSkuIdMap = [];

                if (isset($fileFitContent['核心词'])) {
                    foreach ($fileFitContent['核心词'] as $info) {
                        $ceBillNoSkuIdMap[$info['ceBillNo']][$info['skuId']]["keywords"][] = $info['核心词'];
                    }
                }

                if (isset($fileFitContent['asin'])) {
                    foreach ($fileFitContent['asin'] as $info) {
                        $ceBillNoSkuIdMap[$info['ceBillNo']][$info['skuId']]["cpAsin"][] = $info['asin'];
                    }
                }

                if (isset($fileFitContent['车型'])) {
                    $uniqFitmentMap = [];
                    foreach ($fileFitContent['车型'] as $info) {

                        $uniqFitment = md5($info['ceBillNo'] . $info['skuId'] . $info['make'] . $info['model']);
                        if (!isset($uniqFitmentMap[$uniqFitment])) {
                            $ceBillNoSkuIdMap[$info['ceBillNo']][$info['skuId']]["fitment"][] = [
                                "make" => $info['make'],
                                "model" => $info['model']
                            ];

                            $uniqFitmentMap[$uniqFitment] = 1;
                        }

                    }
                }

                if (count($ceBillNoSkuIdMap) > 0) {

                    foreach ($ceBillNoSkuIdMap as $ceBillNo => $list) {

                        //资料发布的需要修复数据
                        if (count($list) > 0) {
                            $this->log("资料发布了需要修复：{$ceBillNo}");

                            foreach ($list as $skuId => $dataInfo) {
                                $detailInfo = DataUtils::getPageDocListInFirstDataV1(
                                    $curlService->s3044()->get("pa_sku_materials/queryPage", [
                                        "ceBillNo" => $ceBillNo,
                                        "skuId" => $skuId,
                                        "limit" => 1
                                    ])
                                );
                                if ($detailInfo) {
                                    $detailInfo['fitment'] = $dataInfo['fitment'] ?? [];
                                    $detailInfo['keywords'] = array_unique($dataInfo['keywords'] ?? []);
                                    $detailInfo['cpAsin'] = array_unique($dataInfo['cpAsin'] ?? []);
                                    $detailInfo['modifiedBy'] = "system(sp-fix-angang)";


                                    $this->log(json_encode($detailInfo, JSON_UNESCAPED_UNICODE));


                                    $ss = $curlService->s3044()->put("pa_sku_materials/{$detailInfo['_id']}", $detailInfo);
                                    if ($ss) {
                                        $this->log("更新完毕");
                                    }
                                }
                            }

                        }


                    }


                }

            }

        }

    public function ceMaterialObjectLog()
        {
            $curlService = (new CurlService())->pro();
            $curlLogService = (new CurlService())->pro();
            $curlLogService->gateway();
            $curlLogService->getModule('ux168log');

            $list = [];

            $page = 1;
            do {
                $this->log($page);
                $l = DataUtils::getPageDocList($curlService->s3044()->get("pa_ce_materials/queryPage", [
                    "limit" => 1000,
                    "page" => $page,
                    "orderBy" => "-_id"
                ]));
                if (count($l) == 0) {
                    break;
                }
                foreach ($l as $info) {
                    if (preg_match('/^(QD|DPMO)/', $info['batchName'])) {
                        $logListResp = DataUtils::getNewResultData($curlLogService->getWayPost($curlLogService->module . "/log/v1/query", [
                            "page" => [
                                "pageNum" => 1,
                                "pageSize" => 1000,
                            ],
                            "condition" => [
                                "opId" => $info['_id'],
                                "logSource" => "pa-sku-material",
                                "logType" => "pa-sku-material"
                            ]
                        ]));
                        if ($logListResp && isset($logListResp['list']) && $logListResp['list'] && count($logListResp['list']) > 0) {
                            foreach ($logListResp['list'] as $logInfo) {
                                $list[] = [
                                    "ceBillNo" => $info['ceBillNo'],
                                    "opType" => $logInfo['opType'],
                                    "opBeforeContent" => $logInfo['opBeforeContent'],
                                    "opAfterContent" => $logInfo['opAfterContent'],
                                    "opRemark" => $logInfo['opRemark'],
                                ];
                            }
                            $this->log("有日志：{$info['_id']}");
                        } else {
                            $this->log("没有日志：{$info['_id']}");
                        }

                    } else {
                        $this->log("结束了");
                        break 2;
                    }
                }

                $page++;
            } while (true);

            if (count($list) > 0) {
                $excelUtils = new ExcelUtils();
                $downloadOssLink = "资呈_" . date("YmdHis") . ".xlsx";
                $downloadOssPath = $excelUtils->downloadXlsx(["ceBillNo", "opType", "修改前", "修改后", "备注"], $list, $downloadOssLink);
                $this->log("导出内容");
            }

        }

    public function getCEBillNo()
        {
            $fileFitContent = (new ExcelUtils())->getXlsxData("../export/无ce单的自营sku数据_2025.xlsx");

            if (sizeof($fileFitContent) > 0) {
                $list = array_unique(array_column($fileFitContent, "sku_id"));


                $curlSsl = (new CurlService())->pro();
                $getKeyResp = DataUtils::getNewResultData($curlSsl->gateway()->getModule("pa")->getWayPost($curlSsl->module . "/scms/ce_bill_no/v1/getCeDetailBySkuIdList", [
                    "skuIdList" => $list,
                    "orderBy" => "",
                    "pageNumber" => 1,
                    "entriesPerPage" => 500
                ]));
                if ($getKeyResp && count($getKeyResp) > 0) {

                    $skuIdCeMap = [];
                    foreach ($getKeyResp as $item) {
                        $skuIdCeMap[$item['skuId']] = $item['ceBillNo'];
                    }

                    $curlSsl = (new CurlService())->pro();
                    $getKeyResp1 = DataUtils::getNewResultData($curlSsl->gateway()->getModule("pa")->getWayPost($curlSsl->module . "/ppms/product_dev/sku/v2/findListWithAttr", [
                        "skuIdList" => $list,
                        "attrCodeList" => [
                            "custom-skuInfo-skuId",
                            "custom-prePurchase-prePurchaseBillNo"
                        ]
                    ]));
                    $map = [];
                    if ($getKeyResp1) {
                        foreach ($getKeyResp1 as $item) {
                            $ceBillNo = "";
                            if (isset($skuIdCeMap[$item['custom-skuInfo-skuId']])) {
                                $ceBillNo = $skuIdCeMap[$item['custom-skuInfo-skuId']];
                            }
                            if (!empty($ceBillNo)) {
                                $map[$item['custom-prePurchase-prePurchaseBillNo']][$ceBillNo][] = $item['custom-skuInfo-skuId'];
                            }
                        }
                    }

                    if ($map) {
                        foreach ($map as $prePurchaseBillNo => $ceBillNoMap) {
                            foreach ($ceBillNoMap as $ceBillNo => $skuIds) {
                                $this->log("{$prePurchaseBillNo} 开始回写: {$ceBillNo}" . json_encode($skuIds, JSON_UNESCAPED_UNICODE));
                                $data = [
                                    "prePurchaseBillNo" => $prePurchaseBillNo,
                                    "ceBillNo" => $ceBillNo,
                                    "operatorName" => "system(PA-CE回写)"
                                ];
                                $curlService1 = (new CurlService())->pro();
                                $curlService1->gateway()->getModule('pa');
                                $resp3 = DataUtils::getNewResultData($curlService1->getWayPost($curlService1->module . "/scms/ce_bill_no/v1/writeBackAutarkyCeSkuToPrePurchase", $data));
                                if ($resp3) {
                                    $this->log(json_encode($resp3, JSON_UNESCAPED_UNICODE));
                                }
                                sleep(3);
                            }
                        }
                    }


                }


            }


        }


        /**
         * 更新欧洲共享仓归属优先级配置
         * @return void
         */

    public function fixRepeatSkuMaterial()
        {
            $ceBillNo = "CE202508040023";

            $curlService = (new CurlService())->pro();
            $list = DataUtils::getPageDocList(
                $curlService->s3044()->get("pa_sku_materials/queryPage", [
                    "ceBillNo" => $ceBillNo,
                    "limit" => 1000
                ])
            );
            if ($list) {
                $sameSkuIdMap = [];
                foreach ($list as &$item) {
                    if (!isset($sameSkuIdMap[$item['skuId']])) {
                        if ($item['keywords']) {
                            $item['keywords'] = array_unique($item['keywords']);
                        }
                        if ($item['cpAsin']) {
                            $item['cpAsin'] = array_unique($item['cpAsin']);
                        }
                        if ($item['fitment']) {
                            $quchongfitment = [];
                            $uniqFitmentMap = [];
                            foreach ($item['fitment'] as $info) {
                                $uniqFitment = md5($info['make'] . $info['model']);
                                if (!isset($uniqFitmentMap[$uniqFitment])) {
                                    $quchongfitment[] = [
                                        "make" => $info['make'],
                                        "model" => $info['model']
                                    ];
                                    $uniqFitmentMap[$uniqFitment] = 1;
                                }
                            }
                            if ($quchongfitment) {
                                $item['fitment'] = $quchongfitment;
                            }
                        }
                        $sameSkuIdMap[$item['skuId']] = 1;
                        $this->log(json_encode($item, JSON_UNESCAPED_UNICODE));

                        $ss = $curlService->s3044()->put("pa_sku_materials/{$item['_id']}", $item);
                        if ($ss) {
                            $this->log("更新完毕");
                        }
                    } else {
                        $this->log("{$item['skuId']} 重复了,删掉一个");
                        $curlService->s3044()->delete("pa_sku_materials/{$item['_id']}");
                    }

                }


            }


        }

    public function getRepeatSkuMaterial()
        {
            $curlService = (new CurlService())->pro();
            $ceBillNoList = [];
            $page = 1;
            do {
                $this->log($page);
                $l = DataUtils::getPageDocList($curlService->s3044()->get("pa_ce_materials/queryPage", [
                    "limit" => 1000,
                    "page" => $page,
                    "status" => "materialComplete",
                    "modifiedOn_lte" => "2025-07-18",
                    "orderBy" => "-_id"
                ]));
                if (count($l) == 0) {
                    break;
                }
                foreach ($l as $info) {
                    if (preg_match('/^(QD|DPMO)/', $info['batchName']) && preg_match('/^(CE)/', $info['ceBillNo'])) {
                        $ceBillNoList[] = $info['ceBillNo'];
                    } else {
                        $this->log("结束了");
                        break 2;
                    }
                }
                $page++;
            } while (true);

            $exportList = [];
            if (count($ceBillNoList) > 0) {
                $this->log(count($ceBillNoList) . "个CE单");
                $cccTime = "2025-07-18T00:00:00.000Z";

                foreach (array_chunk($ceBillNoList, 10) as $chunk) {
                    $ll = DataUtils::getPageDocList($curlService->s3044()->get("pa_sku_materials/queryPage", [
                        "limit" => 10000,
                        "ceBillNo_in" => implode(",", $chunk),
                        "status" => "materialComplete",
                        "orderBy" => "-_id"
                    ]));
                    if (count($ll) == 0) {
                        continue;
                    }

                    foreach ($ll as $info) {
                        $cc = new DateTime($cccTime);
                        $ss = new DateTime($info['publishOn']);
                        $this->log("{$info['ceBillNo']}-{$info['skuId']}" . $info['publishOn']);
                        if (!empty($info['publishOn']) && !empty($info['publishBy']) && ($ss < $cc)) {

                            if (!empty($info['keywords']) || !empty($info['cpAsin']) || !empty($info['fitment'])) {
                                $key = $info['ceBillNo'] . $info['skuId'];
                                $data = [
                                    "ceBillNo" => $info['ceBillNo'],
                                    "skuId" => $info['skuId'],
    //                                "keywords" => $info['keywords'],
    //                                "cpAsin" => $info['cpAsin'],
    //                                "fitment" => $info['fitment'],
                                    "publishOn" => $info['publishOn'],
                                ];
                                $this->redis->hSet(REDIS_MATERIAL_REPT_KEY, $key, json_encode($data, JSON_UNESCAPED_UNICODE));

                                $dataJ = [
                                    "ceBillNo" => $info['ceBillNo'],
                                    "skuId" => $info['skuId'],
    //                                "keywords" => json_encode($info['keywords'],JSON_UNESCAPED_UNICODE),
    //                                "cpAsin" => json_encode($info['cpAsin'],JSON_UNESCAPED_UNICODE),
    //                                "fitment" => json_encode($info['fitment'],JSON_UNESCAPED_UNICODE),
                                    "publishOn" => $info['publishOn'],
                                ];
                                $exportList[] = $dataJ;
                            }

                        }
                    }

                }

            }

    //        $list = $this->redis->hGetAll(REDIS_MATERIAL_REPT_KEY);
    //
    //        $ceBillNoMap = [];
    //        foreach ($list as $key => $value){
    //            $data = json_decode($value,true);
    //            $ceBillNoMap[$data['ceBillNo']][$data['skuId']]['keywords'] = $data['keywords'];
    //            $ceBillNoMap[$data['ceBillNo']][$data['skuId']]['cpAsin'] = $data['cpAsin'];
    //            $ceBillNoMap[$data['ceBillNo']][$data['skuId']]['fitment'] = $data['fitment'];
    //            $ceBillNoMap[$data['ceBillNo']][$data['skuId']]['publishOn'] = $data['publishOn'];
    //        }
    //
    //        if ($ceBillNoMap){
    //            $exportList = [];
    //            foreach ($ceBillNoMap as $ceBillNo => $skuMap){
    //                $skuNumber = count($skuMap);
    //                $sameKeywords = false;
    //                $sameCpAsin = false;
    //                $sameFitments = false;
    //                $firstKeywords = "";
    //                $firstCpAsins = "";
    //                $firstFitments = "";
    //
    //                $index = 0;
    //                $sameKeywordsNumber = 0;
    //                $sameCpAsinNumber = 0;
    //                $sameFitmentsNumber = 0;
    //
    //                $publishOn = "";
    //                foreach ($skuMap as $skuId => $info){
    //
    //                    if ($index == 0){
    //                        $publishOn = $info['publishOn'];
    //                        $firstKeywords = json_encode($info['keywords'],JSON_UNESCAPED_UNICODE);
    //                        $firstCpAsins = json_encode($info['cpAsin'],JSON_UNESCAPED_UNICODE);
    //                        $firstFitments = json_encode($info['fitment'],JSON_UNESCAPED_UNICODE);
    //                        $index++;
    //                        continue;
    //                    }
    //
    //                    if ($firstKeywords == json_encode($info['keywords'],JSON_UNESCAPED_UNICODE)){
    //                        $sameKeywordsNumber++;
    //                    }else{
    //
    //                    }
    //                    if ($firstCpAsins == json_encode($info['cpAsin'],JSON_UNESCAPED_UNICODE)){
    //                        $sameCpAsinNumber++;
    //                    }else{
    //
    //                    }
    //                    if ($firstFitments == json_encode($info['fitment'],JSON_UNESCAPED_UNICODE)){
    //                        $sameFitmentsNumber++;
    //                    }else{
    //
    //                    }
    //
    //                    $index++;
    //                }
    //                if ($skuNumber > 1){
    //                    if ($sameKeywordsNumber == ($skuNumber - 1)){
    //                        $sameKeywords = true;
    //                    }
    //                    if ($sameCpAsinNumber == ($skuNumber - 1)){
    //                        $sameCpAsin = true;
    //                    }
    //                    if ($sameFitmentsNumber == ($skuNumber - 1)){
    //                        $sameFitments = true;
    //                    }
    //                }else{
    //                    $sameKeywords = true;
    //                    $sameCpAsin = true;
    //                    $sameFitments = true;
    //                }
    //
    //                $exportList[] = [
    //                    "ceBillNo" => $ceBillNo,
    //                    "publishOn"=> $publishOn,
    //                    "skuNumber" => $skuNumber,
    //                    "sameKeywords" => $sameKeywords == true ? "全部一致" : "不一致",
    //                    "sameCpAsin" => $sameCpAsin == true ? "全部一致" : "不一致",
    //                    "sameFitments" => $sameFitments == true ? "全部一致" : "不一致",
    //                ];
    //
    //
    //            }
    //        }

            if (count($exportList) > 0) {
                $excelUtils = new ExcelUtils();
                $filePath = $excelUtils->downloadXlsx(["ceBillNo", "skuId", "发布日期"], $exportList, "sku资料呈现资料发布后广告信息内容_" . date("YmdHis") . ".xlsx");
                $this->log($filePath);
            } else {
                $this->log("没有数据");

            }


        }

    public function getRepeatSkuMaterialByAliSls()
        {
            $curlService = (new CurlService())->pro();

            //$list = $this->redis->hGetAll(REDIS_MATERIAL_REPT_KEY);


            $fileContent = (new ExcelUtils())->getXlsxData("../export/uploads/default/sku资料呈现资料发布后广告信息内容_20250731100344.xlsx");
    //        $fileContent = (new ExcelUtils())->getXlsxData("../export/uploads/default/sku资料呈现资料发布后广告信息内容_test.xlsx");

            $ceBillNoMap = [];
            foreach ($fileContent as $info) {
                $ceBillNoMap[$info['ceBillNo']][] = [
                    "skuId" => $info['skuId'],
                    "publishOn" => date("Y-m-d", strtotime($info['发布日期']))
                ];
            }


            $exportList = [];
            foreach ($ceBillNoMap as $ceBillNo => $skuMap) {
                $skuNumber = count($skuMap);

                foreach ($skuMap as $skuId) {

                    $jixianTime = "2025-04-12T08:00:00.000Z";
                    if (strtotime($skuId['publishOn']) <= strtotime($jixianTime)) {
                        $this->log("{$skuId['skuId']} {$skuId['publishOn']} 超过4个月，无法获取阿里云数据");
                        continue;
                    }
                    $query = "{$ceBillNo} and pa_sku_materials and RequestMethod: PUT and {$skuId['skuId']} not fix-angang";
                    $this->log("{$query}");
                    $res = (new RequestUtils("test"))->callAliCloudSls($query);

                    if ($res && $res['code'] == "200" && $res['data'] && $res['data']['logs'] && count($res['data']['logs']) > 0) {
                        //按时间倒序
                        usort($res['data']['logs'], function ($a, $b) {
                            return $b['__time__'] <=> $a['__time__'];
                        });
                        $nextLog = $this->findNoMaterialStatusDate($res['data']['logs'], 0);
                        if ($nextLog) {
                            $key = $nextLog['ceBillNo'] . $nextLog['skuId'];
                            $data1 = [
                                "ceBillNo" => $nextLog['ceBillNo'],
                                "skuId" => $nextLog['skuId'],
                                "keywords" => $nextLog['keywords'],
                                "cpAsin" => $nextLog['cpAsin'],
                                "fitment" => $nextLog['fitment']
                            ];
                            $this->redis->hSet(REDIS_MATERIAL_REPT_CORRET_KEY, $key, json_encode($data1, JSON_UNESCAPED_UNICODE));

                            $dataJ1 = [
                                "ceBillNo" => $nextLog['ceBillNo'],
                                "skuId" => $nextLog['skuId'],
                                "keywords" => json_encode($nextLog['keywords'], JSON_UNESCAPED_UNICODE),
                                "cpAsin" => json_encode($nextLog['cpAsin'], JSON_UNESCAPED_UNICODE),
                                "fitment" => json_encode($nextLog['fitment'], JSON_UNESCAPED_UNICODE),
                            ];
                            $exportList[] = $dataJ1;
                            $this->log("有日志：" . json_encode($dataJ1));
                        }


                    } else {
                        $this->log("没有log日志");
                    }


                }

            }

            if (count($exportList) > 0) {
                $excelUtils = new ExcelUtils();
                $filePath = $excelUtils->downloadXlsx(["ceBillNo", "skuId", "核心词", "asin", "车型"], $exportList, "sku资料呈现资料发布前广告信息内容_" . date("YmdHis") . ".xlsx");
                $this->log($filePath);
            } else {
                $this->log("没有数据");

            }


        }

    public function mergeSkuMaterialXlsx()
        {
            $curlService = (new CurlService())->pro();

            $keywords = [];
            $cpasin = [];
            $fitments = [];
            for ($page = 7; $page <= 7; $page++) {
                $fileFitContent = (new ExcelUtils())->getXlsxDataV2("../export/skuMaterial/zicheng/{$page}.xlsx");
                if (sizeof($fileFitContent) > 0) {
                    foreach ($fileFitContent as $sheet => $sheetList) {
                        if ($sheet === '核心词') {
                            $keywords = array_merge($keywords, $sheetList);
                        } else if ($sheet === '热销车型') {
                            $fitments = array_merge($fitments, $sheetList);
                        } else if ($sheet === 'CP asin') {
                            $cpasin = array_merge($cpasin, $sheetList);
                        }
                    }
                }
            }

            if (count($keywords) > 0) {
                $excelUtils = new ExcelUtils("skuMaterial/");
                $filePath = $excelUtils->downloadXlsx(["运营人员", "CE#", "skuId", "核心词"], $keywords, "sku资料呈现核心词_" . date("YmdHis") . ".xlsx");
            }
            if (count($cpasin) > 0) {
                $excelUtils = new ExcelUtils("skuMaterial/");
                $filePath = $excelUtils->downloadXlsx(["运营人员", "CE#", "skuId", "asin"], $cpasin, "sku资料呈现CP_Asin_" . date("YmdHis") . ".xlsx");
            }
            if (count($fitments) > 0) {
                $excelUtils = new ExcelUtils("skuMaterial/");
                $filePath = $excelUtils->downloadXlsx(["运营人员", "CE#", "skuId", "make", "model"], $fitments, "sku资料呈现热销车型_" . date("YmdHis") . ".xlsx");
            }

        }

    public function exportBeforeSkuMaterial()
        {
            $curlService = (new CurlService())->pro();


            $fileContent = (new ExcelUtils())->getXlsxData("../export/uploads/default/sku资料呈现资料发布前广告信息内容_20250731110340.xlsx");

            $exportListKeywords = [];
            $exportListAsins = [];
            $exportListFitments = [];

            foreach ($fileContent as $info) {


                if ($info['核心词']) {
                    $keywords = json_decode($info['核心词'], true);
                    foreach ($keywords as $keyword) {

                        $exportListKeywords[] = [
                            "ceBillNo" => $info['ceBillNo'],
                            "skuId" => $info['skuId'],
                            "keyword" => $keyword,
                        ];

                    }
                }

                if ($info['asin']) {
                    $asins = json_decode($info['asin'], true);
                    foreach ($asins as $asin) {
                        $exportListAsins[] = [
                            "ceBillNo" => $info['ceBillNo'],
                            "skuId" => $info['skuId'],
                            "asin" => $asin,
                        ];
                    }
                }

                if ($info['车型']) {
                    $fitments = json_decode($info['车型'], true);
                    foreach ($fitments as $fitment) {
                        $exportListFitments[] = [
                            "ceBillNo" => $info['ceBillNo'],
                            "skuId" => $info['skuId'],
                            "make" => $fitment['make'],
                            "model" => $fitment['model'],
                        ];
                    }
                }

            }


            if (count($exportListKeywords) > 0) {
                $excelUtils = new ExcelUtils();
                $filePath = $excelUtils->downloadXlsx(["ceBillNo", "skuId", "核心词"], $exportListKeywords, "sku资料呈现资料发布前广告信息核心词_" . date("YmdHis") . ".xlsx");
                $this->log($filePath);
            } else {
                $this->log("没有数据");

            }
            if (count($exportListAsins) > 0) {
                $excelUtils = new ExcelUtils();
                $filePath = $excelUtils->downloadXlsx(["ceBillNo", "skuId", "asin"], $exportListAsins, "sku资料呈现资料发布前广告信息CP_Asin_" . date("YmdHis") . ".xlsx");
                $this->log($filePath);
            } else {
                $this->log("没有数据");

            }
            if (count($exportListFitments) > 0) {
                $excelUtils = new ExcelUtils();
                $filePath = $excelUtils->downloadXlsx(["ceBillNo", "skuId", "make", "model"], $exportListFitments, "sku资料呈现资料发布前广告信息车型_" . date("YmdHis") . ".xlsx");
                $this->log($filePath);
            } else {
                $this->log("没有数据");

            }


        }

    public function findPaCeSkuMaterialStatusNotSync()
        {
            $curlService = new CurlService();
            $curlService = $curlService->pro();


            $ceMap = [];
            $page = 1;
            do {
                $this->log($page);
                $l = DataUtils::getPageDocList($curlService->s3044()->get("pa_ce_materials/queryPage", [
                    "limit" => 1000,
                    "page" => $page,
    //                "status" => "developerComplete",
                    "orderBy" => "-_id"
                ]));
                if (count($l) == 0) {
                    break;
                }
                foreach ($l as $info) {
                    if (preg_match('/^(QD|DPMO)/', $info['batchName'])) {
                        $ceMap[$info['ceBillNo']] = $info['status'];
                    } else {
                        $this->log("结束了");
                        break 2;
                    }
                }
                $page++;
            } while (true);


            if (count($ceMap) > 0) {
                $curlService = new CurlService();
                $curlService = $curlService->pro();

                $ceList = array_keys($ceMap);

                $ceSkuMap = [];
                foreach (array_chunk($ceList, 200) as $chunkBatchNameList) {
                    $l = DataUtils::getPageDocList($curlService->s3044()->get("pa_sku_materials/queryPage", [
                        "limit" => 1000,
                        "page" => 1,
                        "ceBillNo_in" => implode(",", $chunkBatchNameList)
                    ]));
                    if (count($l) == 0) {
                        continue;
                    }
                    foreach ($l as $item) {
                        $ceSkuMap[$item['ceBillNo']][$item['status']] = 1;
                    }
                }

                $list = [];
                foreach ($ceMap as $ceBillNo => $status) {
                    if (isset($ceSkuMap[$ceBillNo])) {

                        $ceSkuStatus = implode(",", array_keys($ceSkuMap[$ceBillNo]));
                        if ($ceSkuStatus != $status) {
                            $list[] = [
                                "ceBillNo" => $ceBillNo,
                                "mainStatus" => $status,
                                "detailStatus" => implode(",", array_keys($ceSkuMap[$ceBillNo]))
                            ];
                        }
                    }
                }

                if ($list) {
                    $excelUtils = new ExcelUtils();
                    $filePath = $excelUtils->downloadXlsx([
                        "ceBillNo",
                        "主状态",
                        "明细状态",
                    ], $list, "CE单状态不一致的数据_" . date("YmdHis") . ".xlsx");
                }


            } else {
                $this->log("没有可以修改的数据");
            }
        }

    public function createSkuConsignmentCe()
        {

            $list = [
                "QD202602270003"
            ];
            foreach ($list as $qdBillNo) {

                $curlSsl = (new CurlService())->pro()->gateway()->getModule("pa");
                $getKeyResp = DataUtils::getNewResultData($curlSsl->getWayPost($curlSsl->module . "/scms/pre_purchase/info/v1/createConsignmentCeBillByQdBillNo", [
                    "isCreateNewCeBillNo" => false,
                    "isDelOldCeBillNo" => true,
                    "qdBillNo" => $qdBillNo,
                    "updateBy" => "system(zhouangang)"
                ]));
                $map = [];
                if ($getKeyResp) {
                    $this->log(json_encode($getKeyResp));
                }
            }

        }

    public function deleteCeSku()
        {

    //        $curlSsl = (new CurlService())->pro()->gateway()->getModule("pa");
    //
    //        foreach ([
    //            "CE202605070032",
    //            "CE202605070033",
    //            "CE202605070027",
    //            "CE202605070045",
    //            "CE202605070049",
    //
    //            "CE202605070036",
    //            "CE202605070037",
    //            "CE202605070039",
    //            "CE202605070042",
    //            "CE202605070043",
    //            "CE202605070047",
    //            "CE202605070028",
    //            "CE202605070030",
    //            "CE202605070046",
    //            "CE202605070050",
    //            "CE202605070040",
    //            "CE202605070044"
    //                 ] as $ceBillNo){
    //
    //            $getKeyResp = DataUtils::getNewResultData($curlSsl->getWayPost($curlSsl->module . "/scms/ce_bill_no/v1/deleteCeDetailByCeBillNo", [
    //                "ceBillNo" => $ceBillNo,
    //                "operatorName" => "system(zhouangang)",
    //                "reason"=>"删除重复CE单"
    //            ]));
    //            $map  =[];
    //            if ($getKeyResp){
    //                $this->log(json_encode($getKeyResp));
    //            }
    //        }
    //        die(11) ;
            $skuList = [
                "a26050700ux0452",
                "a26050700ux0453",
                "a26050700ux0454",
                "a26050700ux0455",
                "a26050700ux0456",
                "a26050700ux0457",
                "a26050700ux0458",
                "a26050700ux0459",
                "a26050700ux0460",
                "a26050700ux0461",
                "a26050700ux0462",
                "a26050700ux0463",
                "a26050700ux0464",
                "a26050700ux0465",
                "a26050700ux0466",
                "a26050700ux0467",
                "a26050700ux0468",
                "a26050700ux0469",
                "a26050700ux0470",
                "a26050700ux0471",
                "a26050700ux0472",
                "a26050700ux0473",
                "a26050700ux0474",
                "a26050700ux0485",
                "a26050700ux0487",
                "a26050700ux0489",
                "a26050700ux0491",
                "a26050700ux0493",
                "a26050700ux0495",
                "a26050700ux0504",
                "a26050700ux0505",
                "a26050700ux0506",
                "a26050700ux0507",
                "a26050700ux0508",
                "a26050700ux0509",
                "a26050700ux0514",
                "a26050700ux0516",
                "a26050700ux0518",
                "a26050700ux0519",
                "a26050700ux0520",
                "a26050700ux0521",
                "a26050700ux0522",
                "a26050700ux0523",
                "a26050700ux0526",
                "a26050700ux0528",
                "a26050700ux0530",
                "a26050700ux0531",
                "a26050700ux0532",
                "a26050700ux0533",
                "a26050700ux0534",
                "a26050700ux0535",
                "a26050700ux0536",
                "a26050700ux0537",
                "a26050700ux0538",
                "a26050700ux0539",
                "a26050700ux0546",
                "a26050700ux0547",
                "a26050700ux0548",
                "a26050700ux0549",
                "a26050700ux0550",
                "a26050700ux0552",
                "a26050700ux0554",
                "a26050700ux0551",
                "a26050700ux0553",
                "a26050700ux0555",
                "a26050700ux0556",
                "a26050700ux0557",
                "a26050700ux0558",
                "a26050700ux0560",
                "a26050700ux0562",
                "a26050700ux0564",
                "a26050700ux0566",
                "a26050700ux0568",
                "a26050700ux0570",
                "a26050700ux0573",
                "a26050700ux0571",
                "a26050700ux0574",
                "a26050700ux0576",
                "a26050700ux0579",
                "a26050700ux0582",
                "a26050700ux0586",
                "a26050700ux0590",
                "a26050700ux0559",
                "a26050700ux0561",
                "a26050700ux0563",
                "a26050700ux0565",
                "a26050700ux0567",
                "a26050700ux0569",
                "a26050700ux0572",
                "a26050700ux0575",
                "a26050700ux0578",
                "a26050700ux0581",
                "a26050700ux0584",
                "a26050700ux0589",
                "a26050700ux0594",
                "a26050700ux0598",
                "a26050700ux0601",
                "a26050700ux0605",
                "a26050700ux0587",
                "a26050700ux0592",
                "a26050700ux0596",
                "a26050700ux0602",
                "a26050700ux0606",
                "a26050700ux0609",
                "a26050700ux0612",
                "a26050700ux0615",
                "a26050700ux0618",
                "a26050700ux0621",
                "a26050700ux0624",
                "a26050700ux0627",
                "a26050700ux0631",
                "a26050700ux0585",
                "a26050700ux0591",
                "a26050700ux0595",
                "a26050700ux0599",
                "a26050700ux0604",
                "a26050700ux0607",
                "a26050700ux0610",
                "a26050700ux0613",
                "a26050700ux0616",
                "a26050700ux0619",
                "a26050700ux0622",
                "a26050700ux0625",
                "a26050700ux0628",
                "a26050700ux0632",
                "a26050700ux0635",
                "a26050700ux0638",
                "a26050700ux0641",
                "a26050700ux0577",
                "a26050700ux0580",
                "a26050700ux0583",
                "a26050700ux0588",
                "a26050700ux0593",
                "a26050700ux0597",
                "a26050700ux0600",
                "a26050700ux0603",
                "a26050700ux0608",
                "a26050700ux0611",
                "a26050700ux0614",
                "a26050700ux0617",
                "a26050700ux0620",
                "a26050700ux0623",
                "a26050700ux0626",
                "a26050700ux0630",
                "a26050700ux0634",
                "a26050700ux0637",
                "a26050700ux0640",
                "a26050700ux0643",
                "a26050700ux0645",
                "a26050700ux0647",
                "a26050700ux0649",
                "a26050700ux0651",
                "a26050700ux0654",
                "a26050700ux0656",
                "a26050700ux0658",
                "a26050700ux0660",
                "a26050700ux0662",
                "a26050700ux0629",
                "a26050700ux0633",
                "a26050700ux0636",
                "a26050700ux0639",
                "a26050700ux0642",
                "a26050700ux0644",
                "a26050700ux0646",
                "a26050700ux0648",
                "a26050700ux0650",
                "a26050700ux0652",
                "a26050700ux0653",
                "a26050700ux0655",
                "a26050700ux0657",
                "a26050700ux0659",
                "a26050700ux0661",
                "a26050700ux0663",
                "a26050700ux0669",
                "a26050700ux0671",
                "a26050700ux0673",
                "a26050700ux0675",
                "a26050700ux0677",
                "a26050700ux0679",
                "a26050700ux0680",
                "a26050700ux0683",
                "a26050700ux0686",
                "a26050700ux0689",
                "a26050700ux0692",
                "a26050700ux0695",
                "a26050700ux0698",
                "a26050700ux0701",
                "a26050700ux0704",
                "a26050700ux0707",
                "a26050700ux0710",
                "a26050700ux0664",
                "a26050700ux0665",
                "a26050700ux0666",
                "a26050700ux0667",
                "a26050700ux0668",
                "a26050700ux0670",
                "a26050700ux0672",
                "a26050700ux0674",
                "a26050700ux0676",
                "a26050700ux0678",
                "a26050700ux0682",
                "a26050700ux0684",
                "a26050700ux0687",
                "a26050700ux0690",
                "a26050700ux0693",
                "a26050700ux0696",
                "a26050700ux0699",
                "a26050700ux0702",
                "a26050700ux0705",
                "a26050700ux0708",
                "a26050700ux0711",
                "a26050700ux0713",
                "a26050700ux0715",
                "a26050700ux0717",
                "a26050700ux0719",
                "a26050700ux0721",
                "a26050700ux0723",
                "a26050700ux0725",
                "a26050700ux0728"
            ];

            $curlService = (new CurlService())->pro();
            $infoList = DataUtils::getPageList($curlService->s3015()->get("product-skus/queryPage", [
                "productId" => implode(",", $skuList),
                "limit" => 500
            ]));
            $map = [];
            if ($infoList) {
                foreach ($infoList as $info) {
                    $map[$info['productId']] = $info;
                }
            }

            foreach ($skuList as $sku) {

                if (isset($map[$sku])) {
                    $productInfo = [
                        "status" => "retired",
                        "userName" => "system(zhouangang)",
                        "action" => "删除CE联动作废SKU",
                        "modifiedOn" => $map[$sku]['modifiedOn'],
                        "modifiedBy" => "system(zhouangang)",
                        "_id" => $map[$sku]['_id'],
                    ];
                    $this->log(json_encode($productInfo, JSON_UNESCAPED_UNICODE));
                    $resp = $curlService->s3015()->post("product-skus/updateProductSku?_id={$productInfo['_id']}", $productInfo);
                    if ($resp) {

                    }
                }
            }

        }

    public function getQDDPMOBatchNameCeMaterialList()
        {
            $curlService = (new CurlService())->pro();
            $list = [];

            $page = 1;
            do {
                $this->log($page);
                $l = DataUtils::getPageDocList($curlService->s3044()->get("pa_ce_materials/queryPage", [
                    "limit" => 1000,
                    "page" => $page,
                    "orderBy" => "-_id"
                ]));
                if (count($l) == 0) {
                    break;
                }
                foreach ($l as $info) {
                    if (preg_match('/^(QD|DPMO)/', $info['batchName'])) {
                        if (empty($info['saleNameList'])) {
                            $list[] = $info['batchName'];
                        }
                    } else {
                        $this->log("结束了");
                        break 2;
                    }
                }
                $page++;
            } while (true);

            return $list;
        }

    public function findNoMaterialStatusDate($logsList, $index = 0)
        {
            if (!isset($logsList[$index])) {
                $this->log("没有日志");
                return [];
            }

            $nowLog = json_decode($logsList[$index]['FormString'], true);
            if (isset($nowLog['publishOn']) && $nowLog['publishOn'] && isset($nowLog['status']) && $nowLog['status'] == 'materialComplete') {
                $this->log("是发布完成的日志更新，跳过");
                $index++;
                return $this->findNoMaterialStatusDate($logsList, $index);
            }

            return $nowLog;
        }

    public function findPrePurchaseBillWithSkuForSkuMaterialInfo()
        {
            $curlService = (new CurlService())->pro();
            $curlService->gateway();
            $curlService->getModule('pa');

    //        $resp = DataUtils::getNewResultData($curlService->getWayPost($this->module . "/scms/pre_purchase/info/v1/findPrePurchaseBillWithSkuForWaitHandleSkuMaterial", [
    //            "pageSize" => 100,
    //            "pageNum" => 1
    //        ]));
    //
    //        if ($resp){
    //            $this->log(json_encode($resp,JSON_UNESCAPED_UNICODE));
    //        }

    //        $resp = DataUtils::getNewResultData($curlService->getWayGet($curlService->module . "/sms/sku/info/material/v1/createCeSkuMaterial", [
    //            "operatorName" => "zhouangang"
    //        ]));
    //
    //        if ($resp){
    //
    //        }
            $fileFitContent = (new ExcelUtils())->getXlsxData("../export/uploads/default/补充修复630数据.xlsx");
            if (sizeof($fileFitContent) > 0) {

                foreach ($fileFitContent as $info) {
                    $pmoArr = [
                        "qdBillNo" => $info['qdBillNo'],
                        "operatorName" => "zhouangang",
                        "purchaseHandleStatus" => 20,
                        "supplierId" => $info['supplierId']
                    ];
                    $resp = DataUtils::getNewResultData($curlService->getWayPost($curlService->module . "/scms/pre_purchase/info/v1/writeBackPmoCeSkuToPrePurchase", $pmoArr));
                    if ($resp) {

                    }
                }

            }
        }

}

// === 入口 ===
$method = isset($argv[1]) ? $argv[1] : 'ceWrite';
$controller = new CeMaterialSync();
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
