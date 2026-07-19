<?php
/**
 * SGU同步
 * 从 SyncCurlController 拆分
 * Class SguSync
 */

require_once dirname(__FILE__) . '/../../../php/bootstrap.php';

class SguSync extends CrudService
{
    public function __construct()
    {
        parent::__construct("sync/sgusync");
    }

    public function initSguInfo()
        {
            $list = [
    //            "g26042900ux0029",
                "g26042900ux0028",
                "g26042900ux0027",
                "g26042900ux0026",
                "g26042900ux0025",
                "g26042900ux0032",
                "g26042900ux0031",
                "g26042900ux0030",
                "g26042900ux0033",
                "g26042900ux0038",
                "g26042900ux0041",
                "g26042900ux0040",
                "g26042900ux0039",
                "g26042900ux0037",
                "g26042900ux0036",
                "g26042900ux0035",
                "g26042900ux0034"
            ];
            $curlService = (new CurlService())->pro();
            $sguInfoList = DataUtils::getQueryList($curlService->s3015()->get("/sgu-sku-scu-maps/query", [
                "sguId" => implode(",", $list),
                "limit" => 1000,
            ]));

            $sguIdSkuMap = [];
            foreach ($sguInfoList as $sguInfo) {
                $sguIdSkuMap[$sguInfo['sguId']][] = $sguInfo['skuScuId'];
            }

            foreach ($sguIdSkuMap as $sguId => $skus) {
                $this->log("{$sguId} 批量初始化");
                if (count($skus) > 0) {
                    $sku = $skus[0];
                } else {
                    $this->log("{$sguId} 没有sku");
                    continue;
                }

                $this->log("{$sguId} 开始初始化");
                $sssinof = DataUtils::getPageListInFirstData($curlService->s3015()->get("product-skus/queryPage", [
                    "limit" => 1,
                    "productId" => $sguId
                ]));
                if (!$sssinof) {
                    $curlSsl = (new CurlService())->pro()->gateway()->getModule("pa");
                    $resp = DataUtils::getNewResultData($curlSsl->getWayPost($curlSsl->module . "/sms/sku/info/init/v1/initSkuInfo", [
                        "initSkuId" => $sku,
                        "operatorName" => "system(修复sgu初始化)",
                        "productType" => "SGU",
                        "sguId" => $sguId
                    ]));

                    $this->log("{$sguId} 结束初始化");
                } else {
                    $this->log("{$sguId} 已经初始化");
                }
            }


        }

    public function createSguInfo()
        {
            $curlService = (new CurlService())->pro();
            $res = DataUtils::getResultData($curlService->s3015()->get("soaps/inventory/createSguInfo", ['createdBy' => "system(zhouangang)"]));
            if ($res) {
                return $res;
            }
            return "";
        }

    public function bindSgu()
        {
            $curlService = (new CurlService())->pro();
    //        $curlService->gateway();
    //        $curlService->getModule('pa');

            $list = [
                "a25081500ux1375",
                "a25081500ux1376"
            ];
            $curlSsl = (new CurlService())->pro();
            $getKeyResp = DataUtils::getNewResultData($curlSsl->gateway()->getModule("pa")->getWayPost($curlSsl->module . "/ppms/product_dev/sku/v2/findListWithAttr", [
                "skuIdList" => $list,
                "attrCodeList" => [
                    "custom-skuInfo-skuId",
                    "custom-sguInfo-sguId"
                ]
            ]));
            $map = [];
            if ($getKeyResp) {
                foreach ($getKeyResp as $item) {
                    $map[$item['custom-skuInfo-skuId']] = $item['custom-sguInfo-sguId'];
                }

            }
            $res = DataUtils::getResultData($curlService->s3015()->get("/sgu-sku-scu-maps/query", [
                "skuScuId_in" => implode(",", $list),
                "limit" => 200,
            ]));

            foreach ($res as $info) {


                if (isset($map[$info['skuScuId']])) {

                    if ($map[$info['skuScuId']] != $info['sguId']) {
                        $info['sguId'] = $map[$info['skuScuId']];
                        $info['modifiedBy'] = "system(zhouangang)";
                        $curlService->s3015()->put("sgu-sku-scu-maps/{$info['_id']}", $info);
                    } else {
                        $this->log("{$info['skuScuId']} sgu一样无需修复：{$map[$info['skuScuId']]} {$info['sguId']}");
                    }

                } else {
                    $this->log("找不到");
                }

            }
    //        $resp = DataUtils::getNewResultData($curlService->getWayPost($curlService->module . "/sms/sku/info/init/v1/initSkuInfo", [
    //            "initSkuId" => "a25062500ux0135",
    //            "operatorName" => "luowei3",
    //            "productType" => "SGU",
    //            "sguId" => "g25072200ux0040"
    //        ]));


        }

    public function fixMergeADSguId()
        {
            $curlService = (new CurlService())->pro();

            $fileFitContent = (new ExcelUtils())->getXlsxData("../export/g号修复_1.xlsx");
            if (sizeof($fileFitContent) > 0) {


                $list = [];
                foreach ($fileFitContent as $item) {
                    if (!empty($item['sku_id'])) {
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

    //            $ss = [];
    //            foreach ($fileFitContent as $info){
    //                if (isset($map[$info['sku_id']])){
    //                    $skuAttrData = $map[$info['sku_id']];
    //                    if (!isset($ss[$skuAttrData['custom-sguInfo-sguId']])){
    //                        if (!empty($skuAttrData['custom-sguInfo-sguId'])){
    //                            $sssinof = DataUtils::getPageListInFirstData($curlService->s3015()->get("product-skus/queryPage",[
    //                                "limit" => 1,
    //                                "productId" => $skuAttrData['custom-sguInfo-sguId']
    //                            ]));
    //                            if (!$sssinof){
    //                                $curlSsl = (new CurlService())->pro()->gateway()->getModule("pa");
    //                                $resp = DataUtils::getNewResultData($curlSsl->getWayPost($curlSsl->module . "/sms/sku/info/init/v1/initSkuInfo", [
    //                                    "initSkuId" => $info['sku_id'],
    //                                    "operatorName" => "system(修复sgu初始化)",
    //                                    "productType" => "SGU",
    //                                    "sguId" => $skuAttrData['custom-sguInfo-sguId']
    //                                ]));
    //                                $ss[$skuAttrData['custom-sguInfo-sguId']] = 1;
    //
    //                            }else{
    //                                $ss[$skuAttrData['custom-sguInfo-sguId']] = 1;
    //                            }
    //                        }
    //
    //
    //                    }
    //                }
    //            }

                $qdScmsPrePurchaseMap = [];
                $fix30List = [];
                $hasSguInit = [];
                foreach ($fileFitContent as $info) {
                    if (empty($info['sku_id'])) {
                        $this->log("{$info['temp_sku_id']}没有sku_id");
                        continue;
                    }
                    if (isset($map[$info['sku_id']])) {
                        $skuAttrData = $map[$info['sku_id']];

                        if (!empty($skuAttrData['custom-sguInfo-groupTag'])) {


                            $sguKey = "sgu_init_{$info['batch_no']}_{$skuAttrData['custom-sguInfo-groupTag']}";
                            $this->log("{$sguKey}");
                            $sguId = $this->redis->hGet("sgu_fix_redis", $sguKey);
                            if (!$sguId) {
                                //当前key重新创建sgu
                                $sguId = $this->createSguInfo();

                                $this->log("{$info['sku_id']} 绑定 {$sguId}");

                                $this->redis->hSet("sgu_fix_redis", $sguKey, $sguId);
                                //$qdScmsPrePurchaseMap[$sguKey] = $sguId;
                            }

                            if (!empty($sguId)) {

                                $this->log("{$sguKey} 生成 {$sguId}");

                                $sguInfo = DataUtils::getQueryListInFirstDataV3($curlService->s3015()->get("/sgu-sku-scu-maps/query", [
                                    "skuScuId" => $info['sku_id'],
                                    "limit" => 1,
                                ]));
                                if ($sguInfo) {

                                    $sguInfo['sguId'] = $sguId;
                                    $sguInfo['modifiedBy'] = "system(zhouangang)";
                                    $curlService->s3015()->put("sgu-sku-scu-maps/{$sguInfo['_id']}", $sguInfo);

                                } else {
                                    //创建

                                    $channelList = explode(",", $skuAttrData['custom-sguInfo-channel']);
                                    $channelListData = [];
                                    foreach ($channelList as $ch) {
                                        $channelListData[] = [
                                            "groupName" => "",
                                            "groupAttrName" => [],
                                            "groupAttrValue" => [],
                                            "channel" => $ch,
                                            "modifiedBy" => "system(zhouangang)",
                                            "modifiedOn" => date("Y-m-d H:i:s", time()) . "Z"
                                        ];
                                    }
                                    $create = [
                                        "createdBy" => "system(zhouangang)",
                                        "modifiedBy" => "system(zhouangang)",
                                        "skuScuId" => $info['sku_id'],
                                        "sguId" => $sguId,
                                        "remark" => "sgu自动绑定和初始化",
                                        "channel" => $channelListData,
                                        "createdOn" => date("Y-m-d H:i:s", time()) . "Z",
                                        "modifiedOn" => date("Y-m-d H:i:s", time()) . "Z"
                                    ];
                                    $curlService->s3015()->post("/sgu-sku-scu-maps", $create);

                                }

                                $fix30List[] = [
                                    "tempSkuId" => $skuAttrData['custom-skuInfo-tempSkuId'],
                                    "skuAttrList" => [
                                        [
                                            "name" => "custom-sguInfo-sguId",
                                            "value" => $sguId
                                        ]
                                    ]
                                ];

                                if (!isset($hasSguInit[$sguId])) {
                                    $this->log("{$sguId} 开始初始化");
                                    $sssinof = DataUtils::getPageListInFirstData($curlService->s3015()->get("product-skus/queryPage", [
                                        "limit" => 1,
                                        "productId" => $sguId
                                    ]));
                                    if (!$sssinof) {
                                        $curlSsl = (new CurlService())->pro()->gateway()->getModule("pa");
                                        $resp = DataUtils::getNewResultData($curlSsl->getWayPost($curlSsl->module . "/sms/sku/info/init/v1/initSkuInfo", [
                                            "initSkuId" => $info['sku_id'],
                                            "operatorName" => "system(修复sgu初始化)",
                                            "productType" => "SGU",
                                            "sguId" => $sguId
                                        ]));
                                        $hasSguInit[$sguId] = 1;
                                    } else {
                                        $hasSguInit[$sguId] = 1;
                                    }
                                    $this->log("{$sguId} 结束初始化");
                                }


                            } else {
                                $this->log("{$sguKey} 生成g号失败");
                            }


                        } else {
                            $this->log("没有绑定g号，不用初始化");
                        }

                    }


                }


                //回写3.0
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

    public function fixMergeADV2SguId()
        {
            $curlService = (new CurlService())->pro();

            $fileFitContent = (new ExcelUtils())->getXlsxData("../export/g号修复_2.xlsx");
            if (sizeof($fileFitContent) > 0) {


                $listMap = [];
                foreach ($fileFitContent as $item) {
                    if (!empty($item['sku_id'])) {
                        $listMap[$item['original_product_dev_main_id']][] = $item['sku_id'];
                    }
                }
                foreach ($listMap as $mainid => $list) {
                    $curlSsl = (new CurlService())->pro()->gateway()->getModule("pa");
                    $getKeyResp = DataUtils::getNewResultData($curlSsl->getWayPost($curlSsl->module . "/ppms/product_dev/sku/v2/findListWithAttr", [
                        "skuIdList" => $list,
                        "attrCodeList" => [
                            "custom-skuInfo-skuId",
                            "custom-skuInfo-tempSkuId",
                            "custom-sguInfo-channel"
                        ]
                    ]));
                    $map = [];
                    if ($getKeyResp) {
                        foreach ($getKeyResp as $item) {
                            $map[$item['custom-skuInfo-skuId']] = $item;
                        }
                    }

                    foreach ($list as $sku) {
                        if (isset($map[$sku])) {
                            $skuAttrData = $map[$sku];


                            $sguKey = "sgu_init_{$mainid}";
                            $this->log("{$sguKey}");
                            $sguId = $this->redis->hGet("sgu_fix_redis", $sguKey);
                            if (!$sguId) {
                                //当前key重新创建sgu
                                $sguId = $this->createSguInfo();

                                $this->log("{$sku} 绑定 {$sguId}");

                                $this->redis->hSet("sgu_fix_redis", $sguKey, $sguId);
                                //$qdScmsPrePurchaseMap[$sguKey] = $sguId;
                            }

                            if (!empty($sguId)) {

                                $this->log("{$sguKey} 生成 {$sguId}");

                                $sguInfo = DataUtils::getQueryListInFirstDataV3($curlService->s3015()->get("/sgu-sku-scu-maps/query", [
                                    "skuScuId" => $sku,
                                    "limit" => 1,
                                ]));
                                if ($sguInfo) {

                                    $sguInfo['sguId'] = $sguId;
                                    $sguInfo['modifiedBy'] = "system(zhouangang)";
                                    $curlService->s3015()->put("sgu-sku-scu-maps/{$sguInfo['_id']}", $sguInfo);

                                } else {
                                    //创建

                                    $channelList = explode(",", $skuAttrData['custom-sguInfo-channel']);
                                    $channelListData = [];
                                    foreach ($channelList as $ch) {
                                        $channelListData[] = [
                                            "groupName" => "",
                                            "groupAttrName" => [],
                                            "groupAttrValue" => [],
                                            "channel" => $ch,
                                            "modifiedBy" => "system(zhouangang)",
                                            "modifiedOn" => date("Y-m-d H:i:s", time()) . "Z"
                                        ];
                                    }
                                    $create = [
                                        "createdBy" => "system(zhouangang)",
                                        "modifiedBy" => "system(zhouangang)",
                                        "skuScuId" => $sku,
                                        "sguId" => $sguId,
                                        "remark" => "sgu自动绑定和初始化",
                                        "channel" => $channelListData,
                                        "createdOn" => date("Y-m-d H:i:s", time()) . "Z",
                                        "modifiedOn" => date("Y-m-d H:i:s", time()) . "Z"
                                    ];
                                    $curlService->s3015()->post("/sgu-sku-scu-maps", $create);

                                }

                                $fix30List[] = [
                                    "tempSkuId" => $skuAttrData['custom-skuInfo-tempSkuId'],
                                    "skuAttrList" => [
                                        [
                                            "name" => "custom-sguInfo-sguId",
                                            "value" => $sguId
                                        ]
                                    ]
                                ];

                                if (!isset($hasSguInit[$sguId])) {
                                    $this->log("{$sguId} 开始初始化");
                                    $sssinof = DataUtils::getPageListInFirstData($curlService->s3015()->get("product-skus/queryPage", [
                                        "limit" => 1,
                                        "productId" => $sguId
                                    ]));
                                    if (!$sssinof) {
                                        $curlSsl = (new CurlService())->pro()->gateway()->getModule("pa");
                                        $resp = DataUtils::getNewResultData($curlSsl->getWayPost($curlSsl->module . "/sms/sku/info/init/v1/initSkuInfo", [
                                            "initSkuId" => $sku,
                                            "operatorName" => "system(修复sgu初始化)",
                                            "productType" => "SGU",
                                            "sguId" => $sguId
                                        ]));
                                        $hasSguInit[$sguId] = 1;
                                    } else {
                                        $hasSguInit[$sguId] = 1;
                                    }
                                    $this->log("{$sguId} 结束初始化");
                                }


                            } else {
                                $this->log("{$sguKey} 生成g号失败");
                            }


                        }
                    }


                }


                //回写3.0
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

}

// === 入口 ===
$method = isset($argv[1]) ? $argv[1] : 'initSguInfo';
$controller = new SguSync();
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
