<?php
require_once(dirname(__FILE__) . "/../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../php/utils/RequestUtils.php");

/**
 * 仅限用于同步生产数据到测试环境数据mongo的增删改查，其中delete和create只有test环境有，而find查询是pro和test有
 * Class SyncCurlController
 */
class SyncCurlController
{
    /**
     * @var CurlService
     */
    public CurlService $curl;
    private MyLogger $log;
    private $module = "platform-wms-application";

    private RedisService $redis;
    public function __construct()
    {
        $this->log = new MyLogger("common-curl/curl");

        $curlService = new CurlService();
        $this->curl = $curlService;

        $this->redis = new RedisService();
    }
    public function getModule($modlue){
        switch ($modlue){
            case "wms":
                $this->module = "platform-wms-application";
                break;
            case "pa":
                $this->module = "pa-biz-application";
                break;
            case "config":
                $this->module = "platform-config-service";
                break;
        }

        return $this;
    }
    /**
     * 日志记录
     * @param string $message 日志内容
     */
    private function log($message = "")
    {
        $this->log->log2($message);
    }

    public function commonDelete($port, $model, $id,$env = "test")
    {
        $curlService = new CurlService();
        $resp = DataUtils::getResultData($curlService->$env()->$port()->delete($model, $id));
        $this->log("删除{$model}，{$id}返回结果：" . json_encode($resp, JSON_UNESCAPED_UNICODE));
    }

    public function commonFindById($port, $model, $id, $env = 'test')
    {
        $curlService = new CurlService();
        $resp = $curlService->$env()->$port()->get("{$model}/{$id}");
        $data = isset($resp['result']) ? $resp['result'] : null;
        $this->log("查询{$model}，{$id}返回结果：" . json_encode($data, JSON_UNESCAPED_UNICODE));
        return $data;
    }

    public function commonFindOneByParams($port, $model, $params, $env = 'test')
    {
        if (!isset($params['limit'])) {
            $params['limit'] = 1;
        }
        $curlService = new CurlService();
        $resp = $curlService->$env()->$port()->get("{$model}/queryPage", $params);
        $data = [];
        if ($port == 's3044') {
            $data = DataUtils::getArrHeadData(DataUtils::getPageDocList($resp));
        } else {
            $data = DataUtils::getPageListInFirstData($resp);
        }
        $this->log("查询{$model}，" . json_encode($params, JSON_UNESCAPED_UNICODE) . "返回结果：" . json_encode($data, JSON_UNESCAPED_UNICODE));
        return $data;
    }

    public function commonFindByParams($port, $model, $params, $env = 'test')
    {
        if (!isset($params['limit'])) {
            $params['limit'] = 999;
        }
        $curlService = new CurlService();
        $resp = $curlService->$env()->$port()->get("{$model}/queryPage", $params);
        $data = [];
        if ($port == 's3044') {
            $data = DataUtils::getPageDocList($resp);
        } else {
            $data = DataUtils::getPageList($resp);
        }
        $this->log("查询{$model}，" . json_encode($params, JSON_UNESCAPED_UNICODE) . "返回结果：" . json_encode($data, JSON_UNESCAPED_UNICODE));
        return $data;
    }

    public function commonCreate($port, $model, $params,$env = "test")
    {
        $curlService = new CurlService();
        $resp = DataUtils::getResultData($curlService->$env()->$port()->post("{$model}", $params));
        $this->log("创建{$model}，" . json_encode($params, JSON_UNESCAPED_UNICODE) . "返回结果：" . json_encode($resp, JSON_UNESCAPED_UNICODE));
        return $resp;
    }
    public function commonUpdate($port, $model, $params,$env = "test")
    {
        $curlService = new CurlService();
        $resp = DataUtils::getResultData($curlService->$env()->$port()->put("{$model}/{$params['_id']}", $params));
        $this->log("更新{$model}，" . json_encode($params, JSON_UNESCAPED_UNICODE) . "返回结果：" . json_encode($resp, JSON_UNESCAPED_UNICODE));
        return $resp;
    }
    /**
     * 删除campaign广告
     */
    public function deleteCampaign(){
        $list = $this->commonFindByParams("s3023","amazon_sp_campaigns",[
            "status" => "3",
            "company" => "CR201706060001",
            "state" => "enabled",
            "limit" => 2000
        ],"pro");
        $needDeleteList = [];
        if (count($list) > 0){
            foreach ($list as $item){
                if (empty($item['campaignId'])){
                    $this->log("campaignId 为空,删除");
                    $needDeleteList[] = $item['_id'];
                    //$this->commonDelete("s3023","amazon_sp_campaigns",$item['_id'],"pro");
                }
            }
        }
        if (count($needDeleteList) > 0){
            foreach ($needDeleteList as $_id){
                $this->commonDelete("s3023","amazon_sp_campaigns",$_id,"pro");
            }
            (new RequestUtils("test"))->dingTalk("删除重复创建campaign结束");
        }else{
            (new RequestUtils("test"))->dingTalk("没有重复创建campaign可删除");
        }

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

    public function getPaSkuMaterial(){



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
            foreach ($fileFitContent as $info){
                $fitmentSkuMap[$info['skuId']][] = [
                    "make" => $info['make'],
                    "model" => $info['model']
                ];
            }
        }

        $fileFitContent = (new ExcelUtils())->getXlsxData("../export/cpasin.xlsx");
        $cpSkuMap = [];
        if (sizeof($fileFitContent) > 0) {
            foreach ($fileFitContent as $info){
                $cpSkuMap[$info['skuId']][] = $info['asin'];
            }
        }
        $dpmoList = $this->commonFindByParams("s3044","pa_sku_materials",[
            "limit" => 1000,
            "ceBillNo" => $ceBillNo,
//            "skuId" => $parentSku
        ],"pro");
        $_idList = [];
        if (count($dpmoList) > 0){
            foreach ($dpmoList as $info){
                $keywords = [
                    "Gear Shift Knob Cover",
                    "Gear Shift Knob Sticker",
                    "Gear Shift Knob Decal",
                    "Gear Shift Head Cover",
                    "Gear Shift Head Cap",
                ];
                $fitment = [];
                $cpAsin = [];
                if(isset($fitmentSkuMap[$info['skuId']])){
                    $fitment = $fitmentSkuMap[$info['skuId']];
                }
                if(isset($cpSkuMap[$info['skuId']])){
                    $cpAsin = $cpSkuMap[$info['skuId']];
                }


//                $info['keywords'] = $keywords;
                $info['cpAsin'] = $cpAsin;
                $info['fitment'] = $fitment;
                $info['modifiedBy'] = "zhouangang";
                $this->commonUpdate("s3044","pa_sku_materials",$info,"pro");

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

    public function addOptionValListData(){
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
        echo json_encode($price_base_fields,JSON_UNESCAPED_UNICODE)."\n";
        echo json_encode($unsalable_base_fields,JSON_UNESCAPED_UNICODE)."\n";

    }


    public function fixPaSkuPhotoGress(){
        $list = $this->commonFindByParams("s3015","sku_photography_progresss",[
            "ceBillNo_in" => "CE202503280130"
        ],"pro");
        foreach ($list as &$item){
            $item['createCeBillNoOn'] = "2025-03-28T16:47:58.000Z";
            $this->commonUpdate("s3015","sku_photography_progresss",$item,"pro");
        }

    }

    public function deleteCeMaterial()
    {
        foreach ([
                     "QD202411190013",
                     "QD202412030020",
                     "QD202412030021"
                 ] as $batchName) {
            $mainInfo = $this->commonFindOneByParams("s3044", "pa_ce_materials", ["batchName" => $batchName], "pro");
            if ($mainInfo) {
                $list = $this->commonFindByParams("s3044", "pa_sku_materials", ["ceBillNo" => $batchName], "pro");
                if ($list) {
                    foreach ($list as $detail) {
                        $this->commonDelete("s3044", "pa_sku_materials", $detail['_id'], "pro");
                        $this->log("删除" . $detail['skuId']);
                    }
                }
                $this->commonDelete("s3044", "pa_ce_materials", $mainInfo['_id'], "pro");
                $this->log("删除" . $mainInfo['batchName'] . "完毕");
            }
        }

    }

    public function updateCeMaterialPlatform()
    {
        $curlService = (new CurlService())->pro();
        $curlService->gateway();
        $this->getModule('pa');
        $list = $this->commonFindByParams("s3044", "pa_ce_materials", ["createdBy"=>"P3-CreateCeSkuMaterialJob"], "pro");
        $batchNameList = [];
        if ($list){
            $batchNameList = array_column($list,"batchName");
        }
        if (count($batchNameList) > 0){
            $resp = DataUtils::getNewResultData($curlService->getWayPost($this->module . "/sms/sku/info/material/v1/findPrePurchaseBillWithSkuForSkuMaterialInfo", $batchNameList));
            $platformMap = [];
            if ($resp){
                foreach ($resp as $item){
                    $platformMap[$item['prePurchaseBillNo']] = $item;
                }
            }
            foreach (array_chunk($batchNameList,150) as $chunkList){
                $mainInfoList = $this->commonFindByParams("s3044", "pa_ce_materials", ["batchName_in" => implode(",",$chunkList)], "pro");
                if (count($mainInfoList) > 0) {
                    foreach ($mainInfoList as $mainInfo){
                        $canUpdate = false;
                        if (!$mainInfo['platform'] && isset($platformMap[$mainInfo['batchName']]) && isset($platformMap[$mainInfo['batchName']]['platform'])){
                            $mainInfo['platform'] = $platformMap[$mainInfo['batchName']]['platform'];
                            $canUpdate = true;
                        }
                        if (!$mainInfo['ebayTraceMan'] && isset($platformMap[$mainInfo['batchName']]) && isset($platformMap[$mainInfo['batchName']]['minorSalesUserName'])){
                            $mainInfo['ebayTraceMan'] = $platformMap[$mainInfo['batchName']]['minorSalesUserName'];
                            $canUpdate = true;
                        }
                        if ($canUpdate){
                            $this->commonUpdate("s3044", "pa_ce_materials", $mainInfo, "pro");
                            $this->log("更新批次的平台数据：{$mainInfo['batchName']} - {$mainInfo['platform']} - {$mainInfo['ebayTraceMan']}");
                        }

                    }
                }
            }
        }


    }


    public function getCompanyByCompanyId($userName = 'zhouangang'){


        $curlService = new CurlService();
        $resp = $curlService->pro()->s3009()->get("system-manages/getCompany", ["companyId" => "CR201706060001"]);
        $data = DataUtils::getResultData($resp);

        $channelParams = array();
        $channelArray = array();
        if ($data){
            $info = $data[0];
            $channelDetailParams = array();
            foreach ($info['regional'] as $item){
                $channelArr = explode("_",$item);
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

        $productList = $this->commonFindByParams("s3015","pa_product_details",[
            "ceBillNo_in" => "CE202412100077"
        ],"pro");
        $this->log(json_encode(array_column($productList,"skuId"),JSON_UNESCAPED_UNICODE));
        foreach ($productList as $info){
            if ($info['skuId']){
                $skuSaleStatusList = $this->commonFindOneByParams("s3015","sku-sale-statuses",["skuId" => $info['skuId']],"pro");
                if (count($skuSaleStatusList) == 0){
                    $channelParams['skuId'] = $info['skuId'];
                    $result = $curlService->pro()->s3015()->post("sku-sale-statuses/createSkuSaleStatusesEx", $channelParams);
                    $this->log(json_encode($result,JSON_UNESCAPED_UNICODE));
                }else{
                    $this->log("已有可售表");
                }

            }
        }



    }


    //重新生成tempSkuId编号 - 重复T号问题处理
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
            foreach ($fileContent as $info){
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
                    foreach ($teRes as $tinfo){
                        $dteRes = $this->commonFindByParams("s3015", "pa_product_details", array(
                            "orderBy" => "_id",
                            "productName" => $tinfo['productName'],
                            "limit" => 5
                        ), $env);

                        //用productName来找到以前的T号
                        $oldTempSkuId = "";
                        if (count($dteRes) > 0) {
                            foreach ($dteRes as $dteInfo){
                                if ($dteInfo['tempSkuId'] != $info['tempSkuId']){
                                    $oldTempSkuId = $dteInfo['tempSkuId'];
                                    break;
                                }
                            }
                        }
                        //如果有旧T号
                        if (!empty($oldTempSkuId)){
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
                                $rs = $this->commonUpdate("s3015","pa_sku_attributes",$paSkuAttributeInfo,$env);
                                if($rs){
                                    $this->log("存在技术维度：{$oldTempSkuId}，已更改为：{$uniqueId}");
                                    $tinfo['tempSkuId'] = $uniqueId;
                                    $rss = $this->commonUpdate("s3015","pa_product_details",$tinfo,$env);
                                }
                            }else{
                                $this->log("没有技术维度：{$oldTempSkuId}，直接更新T号");
                                //没有技术维度直接
                                //新的T号
                                $uniqueId = $this->getTempSkuIdByRedis();
                                $tinfo['tempSkuId'] = $uniqueId;
                                $rss = $this->commonUpdate("s3015","pa_product_details",$tinfo,$env);

                            }

                        }else{
                            $this->log("{$tinfo['tempSkuId']} 查不到旧名称：{$tinfo['productName']}");
                            //新的T号
                            $uniqueId = $this->getTempSkuIdByRedis();
                            $tinfo['tempSkuId'] = $uniqueId;
                            $rss = $this->commonUpdate("s3015","pa_product_details",$tinfo,$env);
                        }

                    }

                }

            }


        }


    }

    //生成T号
    public function getTempSkuIdByRedis(){
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



    public function buildSql(){
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
        $filePath = $excelUtils->downloadXlsx(["文件原名","Oss Key名","OSS链接"],$list,"oss文件");
//        $filePath = $excelUtils->downloadXlsxV2($list,"oss文件");
        $this->log($filePath);
    }

    public function writeProductBaseFba(){
        $env = "pro";
        $rs = $this->commonFindByParams("s3015", "product_fba_bases", [
            "sequenceId" => "CR201706060001",
            "productLineGroup" => "PA",
            "status_in" => "Y",
            "channel_in" => "ebay_us",
            "limit" => 2500
        ], $env);
        if($rs){
            foreach ($rs as $info){

                $curlSsl = (new CurlService())->pro();
                $getKeyResp = DataUtils::getNewResultData($curlSsl->gateway()->getModule("pa")->getWayPost($curlSsl->module . "/scms/ce_bill_no/v1/getCeDetailBySkuIdList", [
                    "skuIdList" => [$info['skuId']],
                    "orderBy" => "id asc",
                    "pageNumber" => 1,
                    "entriesPerPage" => 1
                ]));
                if ($getKeyResp && count($getKeyResp) > 0){
                    $ceInfo = $getKeyResp[0];
                    $batchName = "";
                    if ($ceInfo && isset($ceInfo['ceBillNo']) && $ceInfo['ceBillNo']){
                        $batchName = $ceInfo['ceBillNo'];

                        $respssss = (new CurlService())->pro()->s3044()->post("ebay_bilino_add_rounds/setSellerIdAndAddCountByBatchName", [
                            "batchName" => $batchName,
                            "skuId" => $info['skuId'],
                            "userName" => "pa-fix-sys",
                            "source"=>"海外仓轮单"
                        ]);
                        $this->log("{$batchName} - {$info['skuId']} - 进入轮单：" . json_encode($respssss,JSON_UNESCAPED_UNICODE));
                    }
                }

            }
        }

        //https://master-angular-nodejs-poms-list-manage.ux168.cn/api/product_fba_bases/queryPage?&page=1&limit=10&sequenceId=CR201706060001&productLineGroup=PA&channel_in=ebay_us&api_key=


    }

    public function get30PpmsByTempskuid(){
        $t = [
            "T250114000359",
            "T250114000354",
            "T250114000352",
            "T250114000351",
            "T250114000350",
            "T250114000349",
            "T250114000348",
            "T250114000345",
            "T250114000344",
            "T250114000191",
        ];
        $curlSsl = (new CurlService())->pro();
        $getKeyResp = DataUtils::getNewResultData($curlSsl->gateway()->getModule("pa")->getWayPost($curlSsl->module . "/ppms/product_dev/sku/v2/findListWithAttr", [
            "skuIdList" => $t,
            "attrCodeList" => [
                "custom-skuInfo-tempSkuId",
                "custom-skuInfo-consignmentPrice",
                "min_arrival_quantity",
                "custom-common-salesUserName",
                "custom-skuInfo-factoryId",
                "custom-skuInfo-supplierProductNo",
                "custom-skuInfo-outsideTitle",
                "custom-skuInfo-supplierId",
            ]
        ]));
        if ($getKeyResp){
            $tempIdsList = [
                [
                    "id" => "1879052194089963565",
                    "temp_sku_id" => "T250114000191"
                ], [
                    "id" => "1879052194089963718",
                    "temp_sku_id" => "T250114000344"
                ], [
                    "id" => "1879052194089963719",
                    "temp_sku_id" => "T250114000345"
                ], [
                    "id" => "1879052194089963722",
                    "temp_sku_id" => "T250114000348"
                ], [
                    "id" => "1879052194089963723",
                    "temp_sku_id" => "T250114000349"
                ], [
                    "id" => "1879052194089963724",
                    "temp_sku_id" => "T250114000350"
                ], [
                    "id" => "1879052194089963725",
                    "temp_sku_id" => "T250114000351"
                ], [
                    "id" => "1879052194089963726",
                    "temp_sku_id" => "T250114000352"
                ], [
                    "id" => "1879052194089963728",
                    "temp_sku_id" => "T250114000354"
                ], [
                    "id" => "1879052194089963733",
                    "temp_sku_id" => "T250114000359"
                ]
            ];
            $tempIdsIdMap = [];
            foreach ($tempIdsList as $sss){
                $tempIdsIdMap[$sss['temp_sku_id']] = $sss['id'];
            }
            $this->log(json_encode($getKeyResp,JSON_UNESCAPED_UNICODE));
            $preSkuList = [];
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

            $fileContent = (new ExcelUtils())->getXlsxData("../export/qd/补充的T号.xlsx");
            $titleCnMap = [];
            foreach ($fileContent as $info){
                $titleCnMap[$info['titleCn']] = $info;
            }
            foreach ($getKeyResp as $info){
                if (isset($titleCnMap[$info['custom-skuInfo-outsideTitle']])){
                    $preSkuList[] = [
                        "devSkuPkId" => $tempIdsIdMap[$info['custom-skuInfo-tempSkuId']],
                        "skuId" => $titleCnMap[$info['custom-skuInfo-outsideTitle']]['skuId']
                    ];
                }
            }
            if ($preSkuList){
                $writeData = [
                    "prePurchaseBillNo" => "QD202504080024",
                    "ceBillNo" => "CE202505050082",
//                    "skuList" => $preSkuList,
                    "operatorName" => "zhouangang",
//                    "purchaseHandleStatus" => 70$tempIdsIdMap = {数组} [10]
                ];

                $this->log(json_encode($writeData,JSON_UNESCAPED_UNICODE));
                $getKeyResp = DataUtils::getNewResultData($curlSsl->gateway()->getModule("pa")->getWayPost($curlSsl->module . "/scms/pre_purchase/info/v1/writeBackPmoCeSkuToPrePurchase", $writeData));
                if ($getKeyResp){
                    $this->log(json_encode($getKeyResp,JSON_UNESCAPED_UNICODE));
                }
            }
        }

    }
    public function writeScmsPurchaseBillNo(){
        $curlSsl = (new CurlService())->pro();

        $pmoBillNo = "DPMO250506011";
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
                    "prePurchaseBillNo" => $pmoBillNo,
                    "pmoBillNo" => "PMO2025050600011",
//                    "ceBillNo" => "CE202505090158",
//                    "skuList" => $preSkuList,
                    "operatorName" => "zhouangang",
//                    "purchaseHandleStatus" => 70
                ];

                $this->log(json_encode($writeData,JSON_UNESCAPED_UNICODE));
                $getKeyResp = DataUtils::getNewResultData($curlSsl->gateway()->getModule("pa")->getWayPost($curlSsl->module . "/scms/pre_purchase/info/v1/writeBackPmoCeSkuToPrePurchase", $writeData));
                if ($getKeyResp){
                    $this->log(json_encode($getKeyResp,JSON_UNESCAPED_UNICODE));
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

    public function saveReceiveIpCheck(){
        $curlSsl = (new CurlService())->pro();
        $info = DataUtils::getPageListInFirstData($curlSsl->s3010()->get("problem-product-infos/queryPage",[
            "skuId" => "a24080100ux0303",
            "type" => "tort",
        ]));
        if ($info){
//            $info['remark'] = "Product IP Issue";
//            $info['newBrandName'] = "K LOGO";
//            $info['url'] = "https://branddb.wipo.int/en/advancedsearch/brand/US502015086736339";
            $info['status'] = 'new';
            $result = DataUtils::getPageListInFirstData($curlSsl->s3010()->put("problem-product-infos/{$info['_id']}",$info));
            $this->log(json_encode($result,JSON_UNESCAPED_UNICODE));
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


    public function updateSampleSku(){



        $env = "pro";
        $fileContent = (new ExcelUtils())->getXlsxData("../export/留样CP.xlsx");

        if (sizeof($fileContent) > 0) {
            $skuIdList = array_column($fileContent,"sku_id");

            $skuCPList = [];
            foreach (array_chunk($skuIdList,200) as $chunk){
                $curlService = new CurlService();
                $resp = $curlService->$env()->s3009()->get("market-analysis-reports/getSkuIdInfoByCpBillNoList", [
                    "cpBillNoListJsonEncode" => json_encode($chunk,JSON_UNESCAPED_UNICODE)
                ]);
                $list = DataUtils::getQueryList($resp);

                if (count($list) > 0){
                    foreach ($list as $info){
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
            $downloadOssPath = $excelUtils->downloadXlsx(["CP号", "skuId", "CE单"],$skuCPList,$downloadOssLink);



        }


    }


    /**
     * 回写CE单 到 预计采购清单
     */
    public function ceWrite(){
        $env = "pro";

        $data = [
            "prePurchaseBillNo" => "DPMO250804004",
            "ceBillNo" => "CE202508060170",
            "operatorName" => "system(PA-CE回写)"
        ];
        $curlService1 = new CurlService();
        $curlService1->$env()->gateway()->getModule('pa');
        $resp3 = DataUtils::getNewResultData($curlService1->getWayPost($curlService1->module . "/scms/ce_bill_no/v1/writeBackAutarkyCeSkuToPrePurchase", $data));
        if ($resp3){
            $this->log(json_encode($resp3,JSON_UNESCAPED_UNICODE));
        }
        die(1111);
        $pmoList = [
            "DPMO250804004-陈炜佳",
        ];
        $curlService = new CurlService();
        foreach ($pmoList as $pmoBillNo){
            $resp = $curlService->$env()->s3009()->get("market-analysis-reports/getMainSkuIdInfo", [
                "batch" => $pmoBillNo
            ]);
            if (count($resp['result'])>0){
                $pmoInfo = $resp['result'][0];
                if ($pmoInfo){
                    $resp1 = $curlService->$env()->s3009()->post("cmo-managements/masterQuery", [
                        "conditionsJsonEncode" => json_encode(["pmoBillNoList" => [$pmoInfo['pmoBillNo']]], JSON_UNESCAPED_UNICODE),
                        "entriesPerPage" => 10,
                        "orderBy" => "cmoBillNo desc",
                        "pageNumber" => 1
                    ]);
                    if ($resp1 && $resp1['result'] && $resp1['result']['cmoMasterResponse'] && $resp1['result']['cmoMasterResponse']['cmoMasters'] && count($resp1['result']['cmoMasterResponse']['cmoMasters']) > 0){
                        foreach ($resp1['result']['cmoMasterResponse']['cmoMasters'] as $cmoBillNoInfo){
                            if ($cmoBillNoInfo){
                                $resp2 = $curlService->$env()->s3009()->get("cmo-managements/cmoMasterProgress", [
                                    "cmoBillNo" => $cmoBillNoInfo['cmoBillNo'],
                                ]);
                                $list = [];
                                if ($resp2['result'] && $resp2['result']['data']){
                                    foreach ( $resp2['result']['data'] as $sourceId => $ceList){
                                        foreach ($ceList as $ceBillNo => $ceProcess){
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

                                if (count($list) > 0){
                                    foreach ($list as $info){
                                        if ($info['ceBillNo'] == "CE202508060047"){
                                            continue;
                                        }
                                        $this->log(json_encode($info,JSON_UNESCAPED_UNICODE));

                                        $curlService1 = new CurlService();
                                        $curlService1->$env()->gateway()->getModule('pa');
                                        $resp3 = DataUtils::getNewResultData($curlService1->getWayPost($curlService1->module . "/scms/ce_bill_no/v1/writeBackAutarkyCeSkuToPrePurchase", $info));
                                        if ($resp3){
                                            $this->log(json_encode($resp3,JSON_UNESCAPED_UNICODE));
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
    public function syncAllVerticalMonthlTargets(){
        //todo 同步前请清空表pa_all_vertical_monthly_target 和pa_all_vertical_monthly_sales

        $curlService = new CurlService();
        $page = 1;
        $pages = 1;
        $allList = [];
        do{
            $resp = $curlService->pro()->s3044()->get("pa_all_vertical_monthly_targets/queryPage", [
                "limit" => 1000,
                "page" => $page
            ]);
            $list = DataUtils::getPageList($resp);
            if (count($list['data']) > 0){
                $allList = array_merge($allList,$list['data']);
                $pages = $list['pages'];
            }else{
                break;
            }
            $page++;
        }while($page <= $pages);

        if (count($allList) > 0){
            $res = $curlService->uat()->s3044()->post("pa_all_vertical_monthly_targets/createBatch",$allList);
            $this->log("添加：".json_encode($res,JSON_UNESCAPED_UNICODE));
        }

        $page = $pages = 1;
        $allList = [];
        do{
            $resp = $curlService->pro()->s3047()->get("pa_all_vertical_monthly_saless/queryPage",[
                "limit" => 1000,
                "page" => $page
            ]);
            $list = DataUtils::getPageList($resp);
            if (count($list['data']) > 0){
                $allList = array_merge($allList,$list['data']);
                $pages = $list['pages'];
            }else{
                break;
            }
            $page++;
        }while($page <= $pages);

        if (count($allList) > 0){
            foreach ($allList as $info){
                $res = $curlService->uat()->s3047()->post("pa_all_vertical_monthly_saless",$info);
                $this->log("添加：".json_encode($res,JSON_UNESCAPED_UNICODE));
            }

        }

        $page = $pages = 1;
        $allList = [];
        do{
            $resp = $curlService->pro()->s3047()->get("pa_vertical_daily_saless/queryPage",[
                "limit" => 1000,
                "year" => "2025",
                "page" => $page
            ]);
            $list = DataUtils::getPageList($resp);
            if (count($list['data']) > 0){
                $allList = array_merge($allList,$list['data']);
                $pages = $list['pages'];
            }else{
                break;
            }
            $page++;
        }while($page <= $pages);

        if (count($allList) > 0){
            foreach ($allList as $info){
                $res = $curlService->uat()->s3047()->post("pa_vertical_daily_saless",$info);
                $this->log("添加：".json_encode($res,JSON_UNESCAPED_UNICODE));
            }
        }

    }

    public function updateFcuProductLine(){
        //todo 同步前请清空表pa_all_vertical_monthly_target 和pa_all_vertical_monthly_sales

        $env = "pro";
        $fileContent = (new ExcelUtils())->getXlsxData("../export/skufcu.xlsx");

        $curlService = new CurlService();
        $curlService = $curlService->pro();

        if (sizeof($fileContent) > 0) {
            $skuIdList = array_column($fileContent, "skuId");
            $fcuIdList = array_column($fileContent, "fcuId");

            $list = [];
            foreach (array_chunk($skuIdList,200) as $chunk){
                $getProductMainResp = DataUtils::getQueryList($curlService->s3009()->get("product-operation-lines/queryPage", [
                    "skuId" => implode(",",$chunk),
                    "limit" => 200
                ]));
                if ($getProductMainResp && count($getProductMainResp['data']) > 0){
                    $list = array_merge($list,$getProductMainResp['data']);
                }
            }

            $skuIdProductLineMap = [];
            if (count($list) > 0){
                $skuIdProductLineMap = array_column($list,null,"skuId");
            }


            $fculist = [];
            foreach (array_chunk($fcuIdList,200) as $chunk){
                $fcuResult = DataUtils::getPageDocList($curlService->s3044()->get("fcu_sku_maps/queryPage", [
                    "fcuId_in" => implode(",",$chunk),
                    "limit" => 200
                ]));
                if ($fcuResult && count($fcuResult) > 0){
                    foreach ($fcuResult as $info){
                        $fculist[$info['fcuId']] = $info;
                    }
                }
            }


            foreach ($fileContent as $item){
                $skuId = $item['skuId'];
                if (isset($skuIdProductLineMap[$skuId])){
                    $product_operator_mainInfo_id = $skuIdProductLineMap[$skuId]['product_operator_mainInfo_id'];

                    if (isset($fculist[$item['fcuId']])){
                        $fcuInfo = $fculist[$item['fcuId']];
                        if ($fcuInfo['productLineId']){
                            continue;
                        }
                        $fcuInfo['productLineId'] = $product_operator_mainInfo_id;
                        $fcuInfo['modifiedBy'] = "zhouangang";

                        $sss = $curlService->s3044()->put("fcu_sku_maps/{$fcuInfo['_id']}", $fcuInfo);
                        $this->log("更新产品线id成功" . json_encode($sss,JSON_UNESCAPED_UNICODE));
                    }else{
                        $this->log("找不到fcu：{$fcuInfo['fcuId']}");
                    }

                }else{
                    $this->log("找不到产品线：{$skuId} - {$item['fcuId']}");
                }

                //https://master-nodejs-poms-list-nest.ux168.cn/api/fcu_sku_maps/queryPage

            }

        }


    }


    public function updateProductFba(){
        //todo 同步前请清空表pa_all_vertical_monthly_target 和pa_all_vertical_monthly_sales

        $env = "pro";
        $fileContent = (new ExcelUtils())->getXlsxData("../export/fba.xlsx");

        $curlService = new CurlService();
        $curlService = $curlService->pro();

        if (sizeof($fileContent) > 0) {
            foreach ($fileContent as $info){
                $fbaInfo = DataUtils::getPageListInFirstData($curlService->s3015()->get("product_fba_bases/queryPage",[
                    "skuId" => $info['sku'],
                    "channel" => $info['上架渠道'],
                    "dcId" => $info['仓库'],
                    "limit" => 1,
                ]));
                if (!empty($fbaInfo)){
                    $fbaInfo['status'] = "Y";
                    $res = DataUtils::getResultData($curlService->s3015()->put("product_fba_bases/{$fbaInfo['_id']}",$fbaInfo));
                    $this->log("更新成功".json_encode($res,JSON_UNESCAPED_UNICODE));

                }
            }


        }


    }

    public function deleteFC(){
        $chunk = [
            "FC2025031003310",
        ];
        $curlService = new CurlService();
        $curlService = $curlService->pro();
        $list = DataUtils::getPageDocList($curlService->s3044()->get("fcu_applys/queryPage",[
            "batch_in" => implode(",",$chunk),
            "company" => "CR201706060001",
            "status" => "0",
            "limit" => 200,
        ]));
        if (!empty($list)){
            foreach ($list as $v){
                $del = DataUtils::getResultData($curlService->s3044()->delete("fcu_applys",$v['_id']));
                $this->log("删除：".json_encode($del,JSON_UNESCAPED_UNICODE));
            }
        }
    }

    public function combineFC(){
        $env = "pro";
        $fileContent = (new ExcelUtils())->getXlsxData("../export/FCU.xlsx");

        $curlService = new CurlService();
        $curlService = $curlService->pro();

        if (sizeof($fileContent) > 0) {
            $batchList = array_unique(array_column($fileContent,"FCU"));
            $mainFCUBatch = [];
            $allFCMap = [];
            foreach (array_chunk($batchList,200) as $chunk){
                $list = DataUtils::getPageDocList($curlService->s3044()->get("fcu_applys/queryPage",[
                    "batch_in" => implode(",",$chunk),
                    "company" => "CR201706060001",
                    "status" => "0",
                    "limit" => 200,
                ]));
                if (!empty($list)){
                    $mainFCUBatch = $list[0];
                    foreach ($list as $v){
                        $allFCMap[$v['_id']] = $v['batch'];
                    }
                }
            }

            foreach ($allFCMap as $_id => $batch) {
                if ($_id != $mainFCUBatch['_id']){
                    $fcuSkuMapList = DataUtils::getPageDocList($curlService->s3044()->get("fcu_sku_maps/queryPage", [
                        "main_id" => $_id,
                        "limit" => 10000,
                    ]));
                    if (count($fcuSkuMapList) > 0) {
                        foreach ($fcuSkuMapList as &$fcuSkuMapInfo) {
                            if ($fcuSkuMapInfo['main_id'] != $mainFCUBatch['_id']){
                                $fcuSkuMapInfo['main_id'] = $mainFCUBatch['_id'];
                                $res = DataUtils::getResultData($curlService->s3044()->put("fcu_sku_maps/{$fcuSkuMapInfo['_id']}",$fcuSkuMapInfo));
                                $this->log("更新：".json_encode($res,JSON_UNESCAPED_UNICODE));
                            }
                        }
                    }
                }
            }

        }

    }

    public function updateProductListNo(){
        $env = "pro";
        $fileContent = (new ExcelUtils())->getXlsxData("../export/productListNo.xlsx");

        $curlService = new CurlService();
        $curlService = $curlService->pro();

        if (sizeof($fileContent) > 0) {
            $batchList = array_unique(array_column($fileContent,"productList"));
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
            $list = DataUtils::getPageList($curlService->ux168()->get("product_development_lists/queryPage",[
                "productListNo" => "QD202503040025",
                "limit" => 1,
            ]));
            if (!empty($list)) {
                foreach ($list as $res) {
//                    $res['cancelDate'] = null;
//                    $res['assignedDate'] = "2025-03-06T14:00:02.000Z";
//                    $res['draftNum'] = 1;
                    $res['draftInfos']['supplierId'] = [4397];
                    $ss = DataUtils::getResultData($curlService->ux168()->put("product_development_lists/{$res['_id']}",$res));
                    if ($ss){

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

    public function fixSkuPhotoProcess(){
        $curlService = new CurlService();
        $curlService = $curlService->pro();

        $ceBillNo = "CE202503260168";

//        $curlService->s3015()->delete("sku_photography_progresss","67d0f8fe4e0359ccd8168459");
//        $curlService->s3015()->delete("sku_photography_progresss","67d0f8fe4e0359ccd8168514");
//        $curlService->s3015()->delete("sku_photography_progresss","67d0f8fe4e0359ccd816855d");
//
//        die("1111");
//        $res = DataUtils::getResultData($curlService->s3015()->get("soaps/ux168/getCeDetailByCeBillNo",[
//           "ceBillNo" => $ceBillNo
//        ]));
//        $skuIdList = array_column($res,"skuId");

        $skuIdList = ["a25032600ux2646"];

        $ceMasterCreatedOn = "2025-03-26T15:54:35.000Z";
        $skuMap = [];
        foreach (array_chunk($skuIdList,200) as $chunk){
            $list = DataUtils::getPageList($curlService->s3015()->get("product-skus/queryPage",[
                "limit" => 200,
                "productId" => implode(",",$chunk)
            ]));
            foreach ($list as $info){
                if ($info['status'] == "completed"){
                    $secondVeroDateTime = "";
                    foreach ($info['reviewingList'] as $val){
                        if ($val['reviewingName'] == "secondVero"){
                            $secondVeroDateTime = $val['createdOn'];
                            break;
                        }
                    }
                    $skuMap[$info['productId']] = $secondVeroDateTime;
                }

            }
        }

        $batch = [];
        foreach ($skuMap as $skuId => $dateTime){
            $ss = DataUtils::getPageListInFirstData($curlService->s3015()->get("sku_photography_progresss/queryPage",[
                "skuId" => $skuId,
                "ceBillNo_in" => $ceBillNo,
                "limit" => 1,
            ]));
            if ($ss){
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

        if (count($batch) > 0){
            $this->log(count($batch) . "个新增");
            $curlService->s3015()->post("sku_photography_progresss/createBatch",$batch);
        }
    }

    public function fixProductOpt(){
        $fileContent = (new ExcelUtils())->getXlsxData("../export/资料图片工单.xlsx");

        $curlService = new CurlService();
        $curlService = $curlService->pro();

//        $curlService->s3044()->delete("pa_product_optimizations","65d8477cac548325e88fd2c4");
//        die(1);

        if (sizeof($fileContent) > 0) {



            foreach ($fileContent as $info){
                $dataInfo = DataUtils::getPageListInFirstDataV2($curlService->s3044()->get("pa_product_optimizations/queryPage",[
                    "limit" => 1,
                    "skuId" => $info['skuId'],
                    "applyReasons" => $info['修改来源']
                ]));

                if (empty($dataInfo)){
                    continue;
                }

                $option = "";
                $status = 0;
                $operator = "";

                if ($info['状态'] == "作废"){
                    continue;
                    $option = '执行已作废';
                    $status = 102;
                    $operator = '执行';

                    $dataInfo['statusArrays'][] = [
                        "option" => $option,
                        "status" => $status,
                        "optionBy" => $info['执行人'],
                        "optionTime" => date("Y-m-d H:i:s",time())."Z",
                    ];
                    $dataInfo['remarkArrays'][] = [
                        "option" => $option,
                        "optionBy" => $info['执行人'],
                        "optionTime" => date("Y-m-d H:i:s",time())."Z",
                        "remark" => "运营要求取消执行",
                    ];

                    $updateData = [
                        "executor" => explode(",",$info['执行人']),
                        "modifiedBy" => $info['执行人'],
                        "modifiedOn" => $dataInfo['modifiedOn'],
                        "status" => $status,
                        "deleteBy" => $info['执行人'],
                        "deleteDate" => date("Y-m-d H:i:s", time()) . "Z",
                        "deleteRemarks" => "运营要求取消执行",
                        "statusArrays" => $dataInfo['statusArrays'],
                        "remarkArrays" => $dataInfo['remarkArrays'],
                    ];

                    $curlService->s3044()->put("pa_product_optimizations/{$dataInfo['_id']}",$updateData);



                }else if($info['状态'] == "已完成"){
                    $exectorList = explode(",",$info['执行人']);

                    $updateData = [
                        "executor" => $exectorList,
                        "modifiedBy" => $exectorList[0],
                        "modifiedOn" => $dataInfo['modifiedOn'],
                        "status" => 3,
                        "completedBy" => $exectorList[0],
                        "completedDate" => date("Y-m-d H:i:s", time()) . "Z",
                    ];

                    $curlService->s3044()->put("pa_product_optimizations/{$dataInfo['_id']}",$updateData);

                }



            }



        }






    }


    public function skuMaterialDocCreate(){
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
        foreach ($list as $sku){
            $info = DataUtils::getPageListInFirstData($curlService->s3015()->get("product-skus/queryPage",[
                "productId" => $sku
            ]));
            if ($info){
                foreach ($info['attribute'] as &$item){
                    if ($item['channel'] == "walmart_us" && $item['label'] == "brand"){
                        $item['value'] = "NOMADIC NOOK";
                    }
                }
                $info['userName'] = "system(zhouangang)";

                $resp = $curlService->s3015()->post("product-skus/updateProductSku?_id={$info['_id']}",$info);
                if ($resp){

                }
            }
        }



    }

    public function getAmazonSpKeyword(){
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
            foreach ($fileContent as $info){
                if ($info['model']){
                    $keywordTexts[] = $info['model'];
                }
            }
            $keywordInfoList = [];
            foreach (array_chunk($keywordTexts,300) as $chunk){
                $list = DataUtils::getPageList($curlService->pro()->s3023()->get("amazon_sp_keywords/queryPage",[
                    "keywordText_in" => implode(",",$chunk),
                    "columns" => "channel,keywordId,campaignId,adGroupId,state,keywordText,matchType,bid,createdOn",
                    "createdBy" => "php_restful_commonPaNewCreateKeywordsByType",
                    "state" => "enabled",
                    "limit" => 10000
                ]));
                if (count($list) > 0){
                    foreach ($list as $info){
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
                foreach (array_chunk($keywordInfoList,15000) as $chunk){
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

    public function syncSkuSellerConfig(){
        $curlService = new CurlService();
        $info = DataUtils::getPageListInFirstData($curlService->pro()->s3015()->get("seller-configs/queryPage",[
            "sellerId" => "amazon_ca_ifn",
        ]));
        if ($info){
            unset($info['_id']);
            $curlService->uat()->s3015()->post("seller-configs",$info);
        }


        $info1 = DataUtils::getPageListInFirstData($curlService->pro()->s3015()->get("sku-seller-configs/queryPage",[
            "sellerId" => "amazon_ca_ifn",
            "skuId" => "a24101800ux0691",
        ]));
        if ($info1){
            unset($info1['_id']);
            $curlService->uat()->s3015()->post("sku-seller-configs",$info1);
        }
    }
    public function updatePaGoodsSourceManage(){
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

        $curlService = new CurlService();

        $curlService = $curlService->pro();

        $list = DataUtils::getPageList($curlService->s3015()->get("pa_goods_source_manages/queryPage",[
            "limit" => 1000,
            "ceBillNoOrCeBillNoNew_in" => "CE202503280130",
        ]));

        foreach ($list as $info){
            $info['ceDate'] = "2025-03-28T16:47:58.000Z";
            $curlService->s3015()->put("pa_goods_source_manages/{$info['_id']}",$info);
        }

    }
    public function updateSkuMaterial(){
        $curlService = new CurlService();
        $list = [];

//        $fileFitContent = (new ExcelUtils())->getXlsxData("../export/CEB.xlsx");
//        $fitmentSkuMap = [];
//        if (sizeof($fileFitContent) > 0) {
//            foreach ($fileFitContent as $item){
//
//                $resp = DataUtils::getPageDocList($curlService->pro()->s3044()->get("pa_ce_materials/queryPage", [
//                    "limit" => 1,
//                    "ceBillNo" => $item['ce_bill_no'],
//                ]));
//                if (count($resp) > 0) {
//                    $this->log("{$item['ce_bill_no']}");
//                    $info = $resp[0];
//                    if (count($info['skuIdList']) > 0){
//                        $sku = $info['skuIdList'][0];
//                        $productInfo = DataUtils::getPageListInFirstData($curlService->pro()->s3015()->get("product-skus/queryPage",[
//                            "productId" => $sku
//                        ]));
//
//                        if ($productInfo){
//                            $this->log("修改CE数据：原运营：{$info['saleName']} -> 新运营：{$productInfo['salesUserName']}");
//                            $info['saleName'] = $productInfo['salesUserName'];
//                            $curlService->pro()->s3044()->put("pa_ce_materials/{$info['_id']}",$info);
//                        }
//                    }
//
//                }
//
//
//            }
//        }

//        foreach ($ceMap as $item => $ss){
//            $resp = DataUtils::getPageList($curlService->pro()->s3044()->get("pa_ce_materials/680dfa081eb855713a10b8bd",[]));
//            if (count($resp) > 0) {
//                $resp['ceBillNo'] = "CE202504280011";
//                $resp['ceDate'] = "2025-04-28T00:00:00.000Z";
//
//                $curlService->pro()->s3044()->put("pa_ce_materials/{$resp['_id']}",$resp);
//            }else{
//
//
//            }

            $resp = DataUtils::getPageList($curlService->pro()->s3044()->get("pa_sku_materials/680dfa0951c9ac303c17fe33",[]));
            if (count($resp) > 0) {
                $resp['ceBillNo'] = "CE202504280011";

                $curlService->pro()->s3044()->put("pa_sku_materials/{$resp['_id']}",$resp);
            }else{


            }
//
//
//
//
//        }



    }


    public function syncSkuMaterialToAudit(){
        $curlService = (new CurlService())->pro();
        $curlService->gateway();
        $this->getModule('pa');

//        $resp1 = DataUtils::getNewResultData($curlService->getWayPost($this->module . "/sms/sku/material/changed_doc/v1/page", [
//            "pageNum" => 1,
//            "pageSize" => 200,
//            "applyStatus" => 20
//        ]));
//
//        $batchNameList = [];
//        if ($resp1 && count($resp1['list']) > 0){
//            foreach ($resp1['list'] as $info){
//                if ($info['afterChangedTranslationAttributeValue'] == "<p></p>\n"){
//                    $batchNameList[] = $info['docNumber'];
//                }
//            }
//        }
        $batchNameList = [
            "2025080700056",
        ];
        if (count($batchNameList) > 0) {
            $this->log("一共：".count($batchNameList)."个单据翻译失败，");
            $this->log(json_encode($batchNameList,JSON_UNESCAPED_UNICODE));
            foreach ($batchNameList as $item){
                $postParams = [
                    "docNumbers" => [$item],
                    "operatorName" => "P3-fixTranslationFail"
                ];
                $resp = DataUtils::getNewResultData($curlService->getWayPost($this->module . "/sms/sku/material/changed_doc/v1/syncSkuMaterialToAudit", $postParams));

                $this->log(json_encode($resp,JSON_UNESCAPED_UNICODE));
            }

        }
    }

    public function copyNewChannel(){
        $curlService = (new CurlService())->pro();


        $oldChannel = "amazon_de";
        $newChannel = "amazon_nl";

        $list = [];
        for ($page = 1;$page < 10;$page ++){
            $resp1 = DataUtils::getPageList($curlService->s3015()->get("channel-amazon-attributes/queryPage", [
                "limit" => 5000,
                "page" => $page,
                "channel" => $oldChannel
            ]));
            if (count($resp1) > 0){
                $list = array_merge($list,$resp1);
            }else{
                break;
            }
        }
        if (count($list) > 0) {
            //$curlService = (new CurlService())->test();
            foreach ($list as $info){
                unset($info['_id']);
                $info['channel'] = $newChannel;
                $curlService->s3015()->post("channel-amazon-attributes", $info);
            }

        }
    }


    public function syncPaSkuMaterial(){
        $curlService = (new CurlService())->pro();


        $skuIdList = ["a25031700ux0462"];

        $list = [];

        $resp1 = DataUtils::getPageDocList($curlService->s3044()->get("pa_sku_materials/queryPage", [
            "limit" => 5000,
            "page" => 1,
            "skuId_in" => implode(",",$skuIdList)
        ]));
        if (count($resp1) > 0){
            $list = array_merge($list,$resp1);
        }

        if (count($list) > 0) {
            $curlService = (new CurlService())->test();
            $testList = DataUtils::getPageDocList($curlService->s3044()->get("pa_sku_materials/queryPage",[
                "limit" => 5000,
                "page" => 1,
                "skuId_in" => implode(",",$skuIdList)
            ]));
            $skuIdMap = array_column($testList,null,"skuId");

            foreach ($list as $info){
                if (isset($skuIdMap[$info['skuId']])){
                    $curlService->s3044()->delete("pa_sku_materials/{$skuIdMap[$info['skuId']]['_id']}");
                }
                $curlService->s3044()->post("pa_sku_materials", $info);
            }

        }

    }


    public function fix(){
        $curlService = (new CurlService())->pro();
        $curlService->gateway();
        $curlService->getModule('pa');

        $fileFitContent = (new ExcelUtils())->getXlsxData("../export/pmo.xlsx");

        $fileFitContent1 = (new ExcelUtils())->getXlsxData("../export/DPMO250424008-黎乾海.xlsx");

        $fitmentSkuMap = [];
        $map = [];
        if (sizeof($fileFitContent) > 0) {
            foreach ($fileFitContent as $info){
                $map[$info['outside_title']] = $info['id'];
            }
        }

        $skuList = [];
        if (sizeof($fileFitContent1) > 0){
            foreach ($fileFitContent1 as $info){
                if (isset($map[$info['productLineName']])){
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
        if ($resp){

        }



    }


    public function syncDevSkuInfoToProductSku(){
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
        foreach ($skuIdList as $sku){
            $pmoArr = [
                "initSkuId" => $sku,
                "operatorName" => "system(zhouangang)",
                "prePurchaseBillNo" => "QD202505130005"
            ];
            $resp = DataUtils::getNewResultData($curlService->getWayPost($curlService->module . "/sms/sku/info/init/v1/syncDevSkuInfoToProductSku", $pmoArr));
            if ($resp){

            }
        }

    }


    public function updateSalesUserNameCancel2(){
        //清掉小语种2的
        $env = "pro";
        $userList = [
            [
                "old"=>"dengyiyi2",
                "new"=>"dengyiyi",
            ]
        ];

//        $this->Mongo3009Sql($userList);
//        $this->Mongo3015Sql($userList);
        $this->Mongo3044Sql($userList);






    }


    public function Mongo3009Sql($userList){

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
        foreach ($userList as $user){
            foreach ($dbList as $db){
                $sql = 'db.' . $db['ku'] .'.updateMany({' . $db['field'] . ':"' . $user['old'] . '"},{$set:{' . $db['field'] . ':"'.$user['new'].'"}});';
                $this->log($sql);
            }
        }

    }

    public function Mongo3015Sql($userList){

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
        foreach ($userList as $user){
            foreach ($dbList as $db){
                $sql = 'db.' . $db['ku'] .'.updateMany({' . $db['field'] . ':"' . $user['old'] . '"},{$set:{' . $db['field'] . ':"'.$user['new'].'"}});';
                $this->log($sql);
            }
        }

    }

    public function Mongo3044Sql($userList){

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
        foreach ($userList as $user){
            foreach ($dbList as $db){
                $sql = 'db.' . $db['ku'] .'.updateMany({' . $db['field'] . ':"' . $user['old'] . '"},{$set:{' . $db['field'] . ':"'.$user['new'].'"}});';
                $this->log($sql);
            }
        }

    }


    public function createPmo(){
        ///scms/pmo_plan/v1/createPmo

        $curlService = (new CurlService())->test();
        $curlService->gateway();
        $curlService->getModule('pa');
        $ids = [
            "1927266115552874496",
        ];

        foreach ($ids as $id){
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
            $resp = DataUtils::getNewResultData($curlService->getWayPost($curlService->module . "/scms/pre_purchase/info/v1/createConsignmentCeBill", $pmoArr));
            if ($resp){

            }
        }



    }


    public function updateZhixiao(){
        $curlService = (new CurlService())->pro();
        //$curlService->gateway();
        $fileFitContent = (new ExcelUtils())->getXlsxData("../export/滞销定价sku_1.xlsx");
        $fitmentSkuMap = [];
        if (sizeof($fileFitContent) > 0) {
            $sellerIdProductIds = [];
            foreach ($fileFitContent as $info){
                $sellerIdProductIds[$info['账号']][] = $info['scuId'];
            }
            foreach ($sellerIdProductIds as $sellerId => $sellerProductIds){

                foreach (array_chunk($sellerProductIds,100) as $chunk){
                    $fbaInfo = DataUtils::getPageList($curlService->s3015()->get("channel-price-customizes/queryPage",[
                        "productId" => implode(",",$chunk),
                        "seller" => $sellerId,
                        "priceType" => "unsale",
                        "limit" => 100,
                    ]));

                    foreach ($fbaInfo as $info){
                        if ($info['status'] == "inactive"){
                            $this->log("{$info['productId']} 已经取消滞销，不需要再取消");
                            continue;
                        }
                        $info['status'] = "inactive";
                        $info['endTime'] = "2025-06-12T01:00:00.000Z";
                        $info['modifiedBy'] = "system(zhouangang)";
                        $res = DataUtils::getResultData($curlService->s3015()->put("channel-price-customizes/{$info['_id']}",$info));
                        $this->log("取消滞销价:{$info['productId']}");
                    }
                }
            }

        }


    }

    public function test(){

        $lines = [
            "导入：a25062500ux0563-车型",
            "导入：a25062300ux1376-cpAsin",
            "导入：a25062300ux1376-核心词",
            "导入：a25062300ux1376-车型",
            "更新：a25062700ux2364-车型",
            "导入：a25062700ux2364-cpAsin",
            "导入：a25062700ux2364-核心词",
            "导入：a25062700ux2364-车型",
            "保存a25062500ux1421",
        ];

        foreach ($lines as $line) {
            // 提取 "操作类型"
            $colonPos = strpos($line, '：');
            if ($colonPos !== false) {
                $action = substr($line, 0, $colonPos);
                $remaining = substr($line, $colonPos + 3); // 跳过 "：a"
            } else {
                // 处理 "保存a25062700ux2136" 这种情况
                $action = substr($line, 0, 6); // 取前6个字符（"保存"）
                $remaining = substr($line, 6); // 剩下的部分
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

            echo "操作: $action, a编号: $aNumber, 后缀: $suffix\n";
        }

    }

    public function test1($a,$b,$c,$d){
        echo "{$a} {$b} {$c} {$d}";
    }


    /**
     * 修复垂直ID
     * @throws Exception
     */
    public function fixSkuVerticalId(){
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
            foreach ($fileFitContent as $info){
                $productInfo = DataUtils::getPageListInFirstData($curlService->s3015()->get("product-skus/queryPage",[
                    "productId" => $info['skuid'],
                    "limit" => 1
                ]));
                if ($productInfo){
                    if (!$productInfo['verticalId']){
                        $productInfo['verticalId'] = $companyIdMap[$info['company']] ?? null;
                        $productInfo['action'] = "修复商家ID";
                        $productInfo['userName'] = "system(zhouangang)";

                        $resp = $curlService->s3015()->post("product-skus/updateProductSku?_id={$productInfo['_id']}",$productInfo);
                        if ($resp){

                        }
                    }else{
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
    public function fixAmazonSpRuleId(){
        $curlService = (new CurlService())->pro();

        $productInfo = DataUtils::getPageList($curlService->s3023()->get("amazon_sp_sellers/queryPage",[
            "company_in" => "CR201706060001",
            "limit" => 500
        ]));
        if ($productInfo){
            foreach ($productInfo as &$info){
                if (isset($info['bindRule']) && count($info['bindRule']) > 0){
                    $update = false;
                    foreach ($info['bindRule'] as &$bind){
                        if ($bind['status'] == 0){
                            $startDate = '2025-06-18';
                            if (strpos($info['modifiedOn'], $startDate) === 0) {
                                $this->log("{$info['sellerId']} - {$bind['spType']} - {$info['modifiedOn']}");
                            }

                            foreach ( $bind['ruleTypeAndId'] as &$sss){
                                if ($sss['ruleType'] == 'campaignRuleBySystem' && $sss['ruleId']){
                                    $sss['ruleId'] = "";
                                    $update = true;
                                }
                            }
                        }
                    }
                    if ($update){
                        $this->log("更新");
                        $this->log(json_encode($info,JSON_UNESCAPED_UNICODE));
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
    public function findCampaign(){
        $curlService = (new CurlService())->pro();

        $productInfo = DataUtils::getPageList($curlService->s3023()->get("amazon_sp_sellers/queryPage",[
            "company_in" => "CR201706060001",
            "limit" => 500
        ]));
        if ($productInfo){
            $export = [];
            $sameCampaign = [];
            $manualAllZero = [];
            foreach ($productInfo as &$info){
                if (isset($info['bindRule']) && count($info['bindRule']) > 0){
                    $update = false;
                    foreach ($info['bindRule'] as &$bind){
                        if ($bind['status'] == 0){

                            $targetingType = $bind['spType'];
                            if (strpos($bind['spType'],"manual") === 0){
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

            $this->log(json_encode($manualAllZero,JSON_UNESCAPED_UNICODE));


        }

    }


    public function downloadPaSkuPhotoProgress()
    {
        $curlService = new CurlService();
        $curlService = $curlService->pro();

        $page = 1;
        $list = [];
        do {
            $this->log($page);
            $ss = DataUtils::getResultData($curlService->s3015()->get("sku_photography_progresss/queryPage", [
                "photoOn_lte" => "2025-06-20T23:59:59Z",
                "photoOn_gte" => "2025-05-01T00:00:00Z",
                "limit" => 1000,
                "page" => $page
            ]));
            if (count($ss['data']) == 0) {
                break;
            }
            foreach ($ss['data'] as $info) {
                $list[] = [
                    "batchName" => $info['batchName'],
//                    "salesType" => $info['salesType'],
//                    "tempSkuId" => $info['tempSkuId'],
                    "ceBillNo" => $info['ceBillNo'],
                    "createCeBillNoOn" => $info['createCeBillNoOn'],
                    "skuId" => $info['skuId'],
                    "status" => $info['status'],
                    "photoBy" => $info['photoBy'],
                    "photoOn" => $info['photoOn'],
                ];
            }

            $page++;
        } while (true);

        if (count($list) > 0){
            $excelUtils = new ExcelUtils();
            foreach (array_chunk($list, 10000) as $chunk) {
                $filePath = $excelUtils->downloadXlsx([
                    "批次",
                    "CE单",
                    "CE创建日期",
                    "sku",
                    "状态",
                    "拍摄人",
                    "拍摄完成日期",
                ], $chunk, "图片拍摄进度导出_" . date("YmdHis") . ".xlsx");

            }
        }else{
            $this->log("没有导出");
        }


    }


    public function week(){
        echo date("w",1750806000);
    }

    public function downloadPa()
    {
        $curlService = new CurlService();
        $curlService = $curlService->pro();

        $page = 1;
        $list = [];
        do {
            $this->log($page);
            $ss = DataUtils::getResultData($curlService->s3015()->get("sku_photography_progresss/queryPage", [
                "photoOn_lte" => "2025-06-20T23:59:59Z",
                "photoOn_gte" => "2025-05-01T00:00:00Z",
                "limit" => 1000,
                "page" => $page
            ]));
            if (count($ss['data']) == 0) {
                break;
            }
            foreach ($ss['data'] as $info) {
                $list[] = [
                    "batchName" => $info['batchName'],
//                    "salesType" => $info['salesType'],
//                    "tempSkuId" => $info['tempSkuId'],
                    "ceBillNo" => $info['ceBillNo'],
                    "createCeBillNoOn" => $info['createCeBillNoOn'],
                    "skuId" => $info['skuId'],
                    "status" => $info['status'],
                    "photoBy" => $info['photoBy'],
                    "photoOn" => $info['photoOn'],
                ];
            }

            $page++;
        } while (true);

        if (count($list) > 0){
            $excelUtils = new ExcelUtils();
            foreach (array_chunk($list, 10000) as $chunk) {
                $filePath = $excelUtils->downloadXlsx([
                    "批次",
                    "CE单",
                    "CE创建日期",
                    "sku",
                    "状态",
                    "拍摄人",
                    "拍摄完成日期",
                ], $chunk, "图片拍摄进度导出_" . date("YmdHis") . ".xlsx");

            }
        }else{
            $this->log("没有导出");
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


            if (count($ceBillNoMap) > 0){
                $keywordsList = [];
                $cpAsinList = [];
                $fitmentList = [];
                foreach ($ceBillNoMap as $ceBillNo => $info){
                    $this->log($ceBillNo."查询资料呈现");
                    $ll = DataUtils::getPageDocList($curlService->s3044()->get("pa_sku_materials/queryPage", [
                        "ceBillNo" => $ceBillNo,
                        "limit" => 1500,
                        "page" => 1
                    ]));
                    if (count($ll) == 0) {
                        break;
                    }
                    foreach ($ll as $item) {
                        foreach ($item['keywords'] as $k){
                            $keywordsList[] = [
                                'ceBillNo' => $ceBillNo,
                                'saleName' => $info['saleName'],
                                'skuId' => $item['skuId'],
                                'keywords' => $k
                            ];
                        }
                        foreach ($item['cpAsin'] as $cp){
                            $cpAsinList[] = [
                                'ceBillNo' => $ceBillNo,
                                'saleName' => $info['saleName'],
                                'skuId' => $item['skuId'],
                                'cpAsin' => $cp
                            ];
                        }
                        foreach ($item['fitment'] as $fit){
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

                if (count($keywordsList) > 0){
                    $this->redis->hSet(REDIS_MATERIAL_KEY . "_keywords", $ceBillNoLike,json_encode($keywordsList,JSON_UNESCAPED_UNICODE));

                    $excelUtils = new ExcelUtils();
                    $filePath = $excelUtils->downloadXlsx([
                        "CE单号",
                        "产品运营",
                        "skuId",
                        "核心词",
                    ], $keywordsList, "{$ceBillNoLike}_核心词导出_" . date("YmdHis") . ".xlsx");
                    $this->log("导出核心词");
                }else{
                    $this->log("没有核心词");
                }

                if (count($cpAsinList) > 0){
                    $this->redis->hSet(REDIS_MATERIAL_KEY . "_cpasins", $ceBillNoLike,json_encode($cpAsinList,JSON_UNESCAPED_UNICODE));
                    $excelUtils = new ExcelUtils();
                    $filePath = $excelUtils->downloadXlsx([
                        "CE单号",
                        "产品运营",
                        "skuId",
                        "CP ASIN",
                    ], $cpAsinList, "{$ceBillNoLike}_CP_Asin导出_" . date("YmdHis") . ".xlsx");
                    $this->log("导出CP ASIN");
                }else{
                    $this->log("没有CP ASIN");
                }

                if (count($fitmentList) > 0){
                    $this->redis->hSet(REDIS_MATERIAL_KEY . "_fitment", $ceBillNoLike,json_encode($fitmentList,JSON_UNESCAPED_UNICODE));
                    $excelUtils = new ExcelUtils();
                    $filePath = $excelUtils->downloadXlsx([
                        "CE单号",
                        "产品运营",
                        "skuId",
                        "make",
                        "model",
                    ], $fitmentList, "{$ceBillNoLike}_fitment导出_" . date("YmdHis") . ".xlsx");
                    $this->log("导出fitment");
                }else{
                    $this->log("没有fitment");
                }

            }else{
                $this->log("{$ceBillNoLike}没有数据");
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
                if (preg_match('/^(QD|DPMO)/', $info['batchName'])){
                    if (empty($info['saleNameList'])){
                        $list[] = $info['batchName'];
                    }
                }else{
                    $this->log("结束了");
                    break 2;
                }
            }
            $page++;
        } while (true);

        return $list;
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

        if (count($batchNameList) > 0){
            $batchNameCeBillNoMap = [];
            foreach (array_chunk($batchNameList,100) as $chunkBatchNameList){
                $curlService->gateway();
                $curlService->getModule('pa');
                $resp = DataUtils::getNewResultData($curlService->getWayPost($curlService->module . "/sms/sku/info/material/v1/findPrePurchaseBillWithSkuForSkuMaterialInfo", $chunkBatchNameList));
                if ($resp){
                    $this->log("获取数据");
                    foreach ($resp as $item){
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
            foreach (array_chunk($batchNameList,300) as $chunkBatchNameList){
                $l = DataUtils::getPageDocList($curlService->s3044()->get("pa_ce_materials/queryPage", [
                    "limit" => 1000,
                    "page" => 1,
                    "batchName_in" => implode(",",$chunkBatchNameList)
                ]));
                if (count($l) == 0){
                    continue;
                }

                foreach ($l as $item){
                    $key = $item['batchName'] . $item['ceBillNo'];
                    if (isset($batchNameCeBillNoMap[$key])){
                        //存在数据
                        $this->log("有数据，更新");
                        $item['developerList'] = $batchNameCeBillNoMap[$key]['developerUserName'];
                        $item['saleNameList'] = $batchNameCeBillNoMap[$key]['salesUserName'];
                        $item['ebayTraceManList'] = $batchNameCeBillNoMap[$key]['minorSalesUserName'];
                        $item['platformList'] = $batchNameCeBillNoMap[$key]['platformList'];
                        $item['productLevelList'] = $batchNameCeBillNoMap[$key]['productLevelList'];
                        $res = $curlService->s3044()->put("pa_ce_materials/{$item['_id']}", $item);
                    }else{
                        $this->log("{$item['batchName']}没有数据");
                    }
                }

            }

        }else{
            $this->log("没有可以修改的数据");
        }
    }

    public function getCEBillNo()
    {
        $curlSsl = (new CurlService())->pro();
        $getKeyResp = DataUtils::getNewResultData($curlSsl->gateway()->getModule("pa")->getWayPost($curlSsl->module . "/scms/ce_bill_no/v1/getCeDetailBySkuIdList", [
            "skuIdList" => ["a25050500ux1594"],
            "orderBy" => "",
            "pageNumber" => 1,
            "entriesPerPage" => 500
        ]));
        if ($getKeyResp && count($getKeyResp) > 0){
            print_r($getKeyResp);
        }


    }


    /**
     * 更新欧洲共享仓归属优先级配置
     * @return void
     */
    public function updateEuSharedWarehouseFlowTypePriority(){
        $curlService = new CurlService();
        $curlService = $curlService->test();
        $info = DataUtils::getPageListInFirstData($curlService->s3015()->get("option-val-lists/queryPage",[
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
        if ($info){
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

            foreach ($fileFitContent as $info){
                $pmoArr = [
                    "qdBillNo" => $info['qdBillNo'],
                    "operatorName" => "zhouangang",
                    "purchaseHandleStatus" => 20,
                    "supplierId" => $info['supplierId']
                ];
                $resp = DataUtils::getNewResultData($curlService->getWayPost($curlService->module . "/scms/pre_purchase/info/v1/writeBackPmoCeSkuToPrePurchase", $pmoArr));
                if ($resp){

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
                if (preg_match('/^(QD|DPMO)/', $info['batchName'])){
                    $logListResp = DataUtils::getNewResultData($curlLogService->getWayPost($curlLogService->module . "/log/v1/query",[
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
                    if ($logListResp && isset($logListResp['list']) && $logListResp['list'] && count($logListResp['list']) > 0){
                        foreach ($logListResp['list'] as $logInfo){
                            $list[] = [
                                "ceBillNo" => $info['ceBillNo'],
                                "opType" => $logInfo['opType'],
                                "opBeforeContent" => $logInfo['opBeforeContent'],
                                "opAfterContent" => $logInfo['opAfterContent'],
                                "opRemark" => $logInfo['opRemark'],
                            ];
                        }
                        $this->log("有日志：{$info['_id']}");
                    }else{
                        $this->log("没有日志：{$info['_id']}");
                    }

                }else{
                    $this->log("结束了");
                    break 2;
                }
            }

            $page++;
        } while (true);

        if (count($list) > 0){
            $excelUtils = new ExcelUtils();
            $downloadOssLink = "资呈_" . date("YmdHis") . ".xlsx";
            $downloadOssPath = $excelUtils->downloadXlsx(["ceBillNo", "opType", "修改前","修改后","备注"],$list,$downloadOssLink);
            $this->log("导出内容");
        }

    }


    public function fixCeMaterial()
    {
        $curlService = (new CurlService())->pro();


        $fileFitContent = (new ExcelUtils())->getXlsxData("../export/uploads/default/资呈_20250717203220.xlsx");
        $fitmentSkuMap = [];
        if (sizeof($fileFitContent) > 0) {
            $list = [];
            foreach ($fileFitContent as $info){
                if ($info['修改前'] == '/' && $info['修改后'] == '/'){
                    $this->log("没有任何修改");
                    continue;
                }

                $ceBillNo = $info['ceBillNo'];
                $this->log("{$info['备注']}");

                // 提取 "操作类型"
                $colonPos = strpos($info['备注'], '：');
                if ($colonPos !== false) {
                    $action = substr( $info['备注'], 0, $colonPos);
                    $remaining = substr( $info['备注'], $colonPos + 3); // 跳过 "：a"
                } else {
                    // 处理 "保存a25062700ux2136" 这种情况
                    $action = substr( $info['备注'], 0, 6); // 取前6个字符（"保存"）
                    $remaining = substr( $info['备注'], 6); // 剩下的部分
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
                if ($action == '保存'){
                    $this->log("保存，不读");
                    continue;
                }
                if (!isset($list[$aNumber])){
                    $list[$aNumber] = [
                        "ceBillNo" => $ceBillNo
                    ];
                }
                if ($action == '导入'){
                    $this->log("导入");
                    if ($suffix == '车型'){
                        $list[$aNumber]["fitment"] = $info['修改后'];
                    }else if ($suffix == '核心词'){
                        $list[$aNumber]["keywords"] = $info['修改后'];
                    }else if ($suffix == 'cpAsin'){
                        $list[$aNumber]["cpAsin"] = $info['修改后'];
                    }
                }
                if ($action == '更新'){
                    $this->log("更新");
                    if ($suffix == '车型'){
                        $list[$aNumber]["fitment"] = $info['修改后'];
                    }else if ($suffix == '核心词'){
                        $list[$aNumber]["keywords"] = $info['修改后'];
                    }else if ($suffix == 'cpAsin'){
                        $list[$aNumber]["cpAsin"] = $info['修改后'];
                    }
                }


            }


            $exportList = [];
            foreach ($list as $skuId => $item){
                $exportList[] = [
                    "skuId" => $skuId,
                    "ceBillNo" => $item['ceBillNo'] ?? "",
                    "fitment" => $item['fitment'] ?? '[]',
                    "keywords" => $item['keywords'] ?? '[]',
                    "cpAsin" => $item['cpAsin'] ?? '[]',
                ];
            }

            if (count($exportList) > 0){

                $excelUtils = new ExcelUtils();
                $downloadOssLink = "导出资呈数据_" . date("YmdHis") . ".xlsx";
                $downloadOssPath = $excelUtils->downloadXlsx(["skuId", "ceBillNo", "fitment","keywords","cpAsin"],$exportList,$downloadOssLink);
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
            foreach ($fileFitContent as $info){
                $ceBillNoMap[$info['ceBillNo']][] = [
                    "skuId" => $info['skuId'],
                    "fitment" => json_decode($info['fitment'],true),
                    "keywords" => json_decode($info['keywords'],true),
                    "cpAsin" => json_decode($info['cpAsin'],true),
                ];
            }

            if (count($ceBillNoMap) > 0){

                foreach ($ceBillNoMap as $ceBillNo => $list){

                    $resp = DataUtils::getPageDocList(
                        $curlService->s3044()->get("pa_ce_materials/queryPage", [
                            "ceBillNo" => $ceBillNo,
                        ])
                    );
                    if ($resp){
                        $info = $resp[0];
                        if ($info['status'] == "materialComplete"){
                            //资料发布的需要修复数据
                            if (count($list) > 0){
                                $this->log("资料发布了需要修复：{$ceBillNo}");
                                foreach ($list as $dataInfo){
                                    $respD = DataUtils::getPageDocList(
                                        $curlService->s3044()->get("pa_sku_materials/queryPage", [
                                            "ceBillNo" => $ceBillNo,
                                            "skuId" => $dataInfo['skuId'],
                                            "limit" => 1
                                        ])
                                    );
                                    if ($respD){
                                        $detailInfo = $respD[0];
                                        $detailInfo['fitment'] = $dataInfo['fitment'];
                                        $detailInfo['keywords'] = $dataInfo['keywords'];
                                        $detailInfo['cpAsin'] = $dataInfo['cpAsin'];
                                        $detailInfo['modifiedBy'] = "system(fix-angang)";


                                        $this->log(json_encode($detailInfo,JSON_UNESCAPED_UNICODE));


                                        $ss = $curlService->s3044()->put("pa_sku_materials/{$detailInfo['_id']}", $detailInfo);
                                        if ($ss){
                                            $this->log("更新完毕");
                                        }
                                    }
                                }

                            }

                        }else{
                            $this->log("资料未发布，可以不用修复：{$ceBillNo}");
                        }
                    }else{
                        $this->log("找不到数据：{$ceBillNo}");
                    }

                }


            }

        }

    }

    public function ssss()
    {
        $curlService = (new CurlService())->pro();
//        $curlService->gateway();
//        $curlService->getModule('pa');

        $list = [
            "a25081500ux1375",
            "a25081500ux1376",
            "a25081500ux1377",
            "a25081500ux1378",
            "a25081500ux1379",
            "a25081500ux1380",
            "a25081500ux1381",
            "a25081500ux1382",
            "a25081500ux1383",
            "a25081500ux1384",
            "a25081500ux1385",
            "a25081500ux1386",
            "a25081500ux1387",
            "a25081500ux1388",
            "a25081800ux1872",
            "a25081800ux1873",
            "a25081800ux1874",
            "a25081800ux1875",
            "a25081800ux1876",
            "a25081800ux1877",
            "a25081800ux1878",
            "a25081800ux1879",
            "a25081800ux1880",
            "a25081800ux1881",
            "a25081800ux1882",
            "a25081800ux1883",
            "a25081800ux1884",
            "a25081800ux1885",
            "a25081800ux1886",
            "a25081800ux1887",
            "a25081800ux1888",
            "a25081800ux1889",
            "a25081800ux1890",
            "a25081800ux1891",
            "a25081800ux1892",
            "a25081800ux1893",
            "a25081800ux1894",
            "a25081800ux1895",
            "a25081800ux1896",
            "a25081800ux1897",
            "a25081800ux1898",
            "a25081800ux1899",
            "a25081800ux1900",
            "a25081800ux1901",
            "a25081800ux1902",
            "a25081800ux1903",
            "a25081800ux1904",
            "a25081800ux1905",
            "a25081800ux1906",
            "a25081800ux1907",
            "a25081800ux1908",
            "a25081800ux1909",
            "a25081800ux1910",
            "a25081800ux1911",
            "a25081800ux1912",
            "a25081800ux1913",
            "a25081800ux1914",
            "a25081800ux1915",
            "a25081800ux1916",
            "a25081800ux1917",
            "a25081800ux1918",
            "a25081800ux1919",
            "a25081800ux1920",
            "a25081800ux1921",
            "a25081800ux1922",
            "a25081800ux1923",
            "a25081800ux1924",
            "a25081800ux1925",
            "a25081800ux1926",
            "a25081800ux1927",
            "a25081800ux1928",
            "a25081800ux1929",
            "a25081800ux1930",
            "a25081800ux1931",
            "a25081800ux1932",
            "a25081800ux1933",
            "a25081800ux1934",
            "a25081800ux1935",
            "a25081800ux1936",
            "a25081800ux1937",
            "a25081800ux1938",
            "a25081800ux1939",
            "a25081800ux1940",
            "a25081800ux1941",
            "a25081800ux1942",
            "a25081800ux1943",
            "a25081800ux1944",
            "a25081800ux1945",
            "a25081800ux1946",
            "a25081800ux1947",
        ];
        $curlSsl = (new CurlService())->pro();
        $getKeyResp = DataUtils::getNewResultData($curlSsl->gateway()->getModule("pa")->getWayPost($curlSsl->module . "/ppms/product_dev/sku/v2/findListWithAttr", [
            "skuIdList" => $list,
            "attrCodeList" => [
                "custom-skuInfo-skuId",
                "custom-sguInfo-sguId"
            ]
        ]));
        $map  =[];
        if ($getKeyResp){
            foreach ($getKeyResp as $item){
                $map[$item['custom-skuInfo-skuId']] = $item['custom-sguInfo-sguId'];
            }

        }
        $res = DataUtils::getResultData($curlService->s3015()->get("/sgu-sku-scu-maps/query",[
            "skuScuId_in" => implode(",",$list),
            "limit" => 200,
        ]));

        foreach ($res as $info) {


            if (isset($map[$info['skuScuId']])){

                if ($map[$info['skuScuId']] != $info['sguId']){
                    $info['sguId'] = $map[$info['skuScuId']];
                    $info['modifiedBy'] = "system(zhouangang)";
                    $curlService->s3015()->put("sgu-sku-scu-maps/{$info['_id']}", $info);
                }else{
                    $this->log("{$info['skuScuId']} sgu一样无需修复：{$map[$info['skuScuId']]} {$info['sguId']}");
                }

            }else{
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


    public function exportCEEEEEEEEEEEEEE()
    {
        $fileFitContent = (new ExcelUtils())->getXlsxData("../export/uploads/default/导出资呈数据_20250718113804.xlsx");
        if (sizeof($fileFitContent) > 0) {
            $ceBillNoMap = [];
            foreach ($fileFitContent as $info){
                $ceBillNoMap[$info['ceBillNo']][] = [
                    "skuId" => $info['skuId'],
                    "fitment" => json_decode($info['fitment'],true),
                    "keywords" => json_decode($info['keywords'],true),
                    "cpAsin" => json_decode($info['cpAsin'],true),
                ];
            }

            if (count($ceBillNoMap) > 0){

                foreach ($ceBillNoMap as $ceBillNo => $list){

                }

            }

        }

    }




    public function fixPaSkuMaterialList()
    {
        $curlService = (new CurlService())->pro();

        $fileFitContent = (new ExcelUtils())->getXlsxData("../export/skuMaterial/zicheng/资呈广告异常修复(2025.08.07)-运营确认.xlsx");
        if (sizeof($fileFitContent) > 0) {
            $ceSkuMap = [];
            foreach ($fileFitContent as $sheet => $sheetList) {
                if ($sheet === '核心词'){
                    $uniqueKeyMap = [];
                    foreach ($sheetList as $info){
                        if (!empty($info['核心词'])){
                            $uniqueKey = "{$info['skuId']}{$info['核心词']}";
                            if (!isset($uniqueKeyMap[$uniqueKey])) {

                                $ceSkuMap[$info['CE#']][$info['skuId']]['keywords'][] = $info['核心词'];

                                $uniqueKeyMap[$uniqueKey] = 1;
                            }
                        }
                    }
                }else if ($sheet === '热销车型'){
                    $uniqueKeyMap = [];
                    foreach ($sheetList as $info){
                        $uniqueKey = "{$info['skuId']}{$info['make']}{$info['model']}";
                        if (!isset($uniqueKeyMap[$uniqueKey])){
                            $ceSkuMap[$info['CE#']][$info['skuId']]['fitment'][] = [
                                "make" => $info['make'],
                                "model" => $info['model'],
                            ];
                            $uniqueKeyMap[$uniqueKey] = 1;
                        }
                    }
                }else if ($sheet === 'CP asin'){
                    $uniqueKeyMap = [];
                    foreach ($sheetList as $info){
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


            if ($ceSkuMap){
                $skuNumber = 0;
                $ceNumber = 0;
                foreach ($ceSkuMap as $ceBillNo => $tree1){
                    $ceNumber++;
                    foreach ($tree1 as $skuId => $tree2){
                        $skuNumber++;
                        $skuMaterialInfo = DataUtils::getPageDocListInFirstDataV1($curlService->s3044()->get("pa_sku_materials/queryPage", [
                            "limit" => 1,
                            "ceBillNo" => $ceBillNo,
                            "skuId" => $skuId,
                            "page" => 1
                        ]));
                        if ($skuMaterialInfo){
                            //存在，不管之前的值是怎样的，直接覆盖
                            $skuMaterialInfo['keywords'] = $tree2['keywords']??[];
                            $skuMaterialInfo['cpAsin'] = $tree2['cpAsin']??[];
                            $skuMaterialInfo['fitment'] = $tree2['fitment']??[];
                            $skuMaterialInfo['modifiedBy'] = "system(zhouangang88)";
                            $this->log("更新sku{$ceBillNo}-{$skuId}" . json_encode($skuMaterialInfo,JSON_UNESCAPED_UNICODE));
                            $curlService->s3044()->put("pa_sku_materials/{$skuMaterialInfo['_id']}", $skuMaterialInfo);
                        }else{
                            //不存在，就要创建,先查ce资呈，看看里面的sku，看看里面的sku有没有父类
                            $this->log("{$ceBillNo}{$skuId}不存在sku资呈,等待创建");
                            $skuMaterialList = DataUtils::getPageDocList($curlService->s3044()->get("pa_sku_materials/queryPage", [
                                "limit" => 1000,
                                "ceBillNo" => $ceBillNo,
                                "page" => 1,
                                "orderBy" => "-_id"
                            ]));
                            if ($skuMaterialList){
                                foreach ($skuMaterialList as $item){
                                    if (empty($item['parentSkuId'])){
                                        $syncSkuInfo = [];
                                        //是父类
                                        $syncSkuInfo = $item;
                                        $syncSkuInfo['parentSkuId'] = $item['skuId'];
                                        $syncSkuInfo['skuId'] = $skuId;
                                        $syncSkuInfo['ceBillNo'] = $ceBillNo;
                                        $syncSkuInfo['createdBy'] = "system(zhouangang)";
                                        $syncSkuInfo['keywords'] = $tree2['keywords']??[];
                                        $syncSkuInfo['cpAsin'] = $tree2['cpAsin']??[];
                                        $syncSkuInfo['fitment'] = $tree2['fitment']??[];
                                        unset($syncSkuInfo['_id']);
                                        unset($syncSkuInfo['__v']);
                                        $this->log("同步父sku到子sku{$ceBillNo}-{$skuId}" . json_encode($syncSkuInfo,JSON_UNESCAPED_UNICODE));
                                        $curlService->s3044()->post("pa_sku_materials", $syncSkuInfo);
                                        break;
                                    }
                                }
                            }else{
                                //ce单一个sku都没有的，直接创建吧
                                $syncSkuInfo = [
                                    "skuId" => $skuId,
                                    "ceBillNo" => $ceBillNo,
                                    "createdBy" => "system(zhouangang)",
                                    "keywords" => $tree2['keywords']??[],
                                    "fitment" => $tree2['fitment']??[],
                                    "cpAsin" => $tree2['cpAsin']??[],
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
                                    "developerStartOn" => date("Y-m-d H:i:s",time()) . "Z",
                                    "developerStartBy" => "system(zhouangang)",
                                    "saleStartOn" => null,
                                    "saleStartBy" => "",
                                    "oeNumber" => [],
                                ];
                                $this->log("初始化创建sku{$ceBillNo}-{$skuId}" . json_encode($syncSkuInfo,JSON_UNESCAPED_UNICODE));
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


    public function fixTranslationManagement()
    {
        $skuIdList = [
            "f24090900ux1117",
        ];
        $curlService = (new CurlService())->pro();

        $productSkuList = DataUtils::getPageList($curlService->s3015()->get("product-skus/queryPage",[
            "productId" => implode(",",$skuIdList),
            "limit" => count($skuIdList)
        ]));
        if ($productSkuList){
            $productIdMap = array_column($productSkuList,null,"productId");
        }

        foreach ($skuIdList as $sku){
            if (isset($productIdMap[$sku])){
                $productInfo = $productIdMap[$sku];
                $productInfo['salesUserName'] = "licaihong2";

                $productInfo['userName'] = "system(zhouangang)";
                $productInfo['action'] = "运维修改资料";
                $resp = $curlService->s3015()->post("product-skus/updateProductSku?_id={$productInfo['_id']}",$productInfo);
                $this->log(json_encode($resp,JSON_UNESCAPED_UNICODE));
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
                if (preg_match('/^(QD|DPMO)/', $info['batchName']) && preg_match('/^(CE)/',$info['ceBillNo'])){
                    $ceBillNoList[] = $info['ceBillNo'];
                }else{
                    $this->log("结束了");
                    break 2;
                }
            }
            $page++;
        } while (true);

        $exportList = [];
        if (count($ceBillNoList) > 0){
            $this->log(count($ceBillNoList)."个CE单");
            $cccTime = "2025-07-18T00:00:00.000Z";

            foreach (array_chunk($ceBillNoList,10) as $chunk){
                $ll = DataUtils::getPageDocList($curlService->s3044()->get("pa_sku_materials/queryPage", [
                    "limit" => 10000,
                    "ceBillNo_in" => implode(",",$chunk),
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
                    if (!empty($info['publishOn']) && !empty($info['publishBy']) && ($ss < $cc)){

                        if (!empty($info['keywords']) || !empty($info['cpAsin']) || !empty($info['fitment'])){
                            $key = $info['ceBillNo'] . $info['skuId'];
                            $data = [
                                "ceBillNo" => $info['ceBillNo'],
                                "skuId" => $info['skuId'],
//                                "keywords" => $info['keywords'],
//                                "cpAsin" => $info['cpAsin'],
//                                "fitment" => $info['fitment'],
                                "publishOn" => $info['publishOn'],
                            ];
                            $this->redis->hSet(REDIS_MATERIAL_REPT_KEY, $key,json_encode($data,JSON_UNESCAPED_UNICODE));

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

        if (count($exportList) > 0){
            $excelUtils = new ExcelUtils();
            $filePath = $excelUtils->downloadXlsx(["ceBillNo","skuId","发布日期"],$exportList,"sku资料呈现资料发布后广告信息内容_".date("YmdHis").".xlsx");
            $this->log($filePath);
        }else{
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
        foreach ($fileContent as $info){
            $ceBillNoMap[$info['ceBillNo']][] = [
                "skuId" => $info['skuId'],
                "publishOn" => date("Y-m-d",strtotime($info['发布日期']))
            ];
        }


        $exportList = [];
        foreach ($ceBillNoMap as $ceBillNo => $skuMap) {
            $skuNumber = count($skuMap);

            foreach ($skuMap as $skuId) {

                $jixianTime = "2025-04-12T08:00:00.000Z";
                if (strtotime($skuId['publishOn']) <= strtotime($jixianTime)){
                    $this->log("{$skuId['skuId']} {$skuId['publishOn']} 超过4个月，无法获取阿里云数据");
                    continue;
                }
                $query = "{$ceBillNo} and pa_sku_materials and RequestMethod: PUT and {$skuId['skuId']} not fix-angang";
                $this->log("{$query}");
                $res = (new RequestUtils("test"))->callAliCloudSls($query);

                if ($res && $res['code'] == "200" && $res['data'] && $res['data']['logs'] && count($res['data']['logs']) > 0){
                    //按时间倒序
                    usort($res['data']['logs'], function($a, $b) {
                        return $b['__time__'] <=> $a['__time__'];
                    });
                    $nextLog = $this->findNoMaterialStatusDate($res['data']['logs'],0);
                    if ($nextLog){
                        $key = $nextLog['ceBillNo'] . $nextLog['skuId'];
                        $data1 = [
                            "ceBillNo" => $nextLog['ceBillNo'],
                            "skuId" => $nextLog['skuId'],
                            "keywords" => $nextLog['keywords'],
                            "cpAsin" => $nextLog['cpAsin'],
                            "fitment" => $nextLog['fitment']
                        ];
                        $this->redis->hSet(REDIS_MATERIAL_REPT_CORRET_KEY, $key,json_encode($data1,JSON_UNESCAPED_UNICODE));

                        $dataJ1 = [
                            "ceBillNo" => $nextLog['ceBillNo'],
                            "skuId" => $nextLog['skuId'],
                            "keywords" => json_encode($nextLog['keywords'],JSON_UNESCAPED_UNICODE),
                            "cpAsin" => json_encode($nextLog['cpAsin'],JSON_UNESCAPED_UNICODE),
                            "fitment" => json_encode($nextLog['fitment'],JSON_UNESCAPED_UNICODE),
                        ];
                        $exportList[] = $dataJ1;
                        $this->log("有日志：" .json_encode($dataJ1));
                    }


                }else{
                    $this->log("没有log日志");
                }


            }

        }

        if (count($exportList) > 0){
            $excelUtils = new ExcelUtils();
            $filePath = $excelUtils->downloadXlsx(["ceBillNo","skuId","核心词","asin","车型"],$exportList,"sku资料呈现资料发布前广告信息内容_".date("YmdHis").".xlsx");
            $this->log($filePath);
        }else{
            $this->log("没有数据");

        }



    }


    public function findNoMaterialStatusDate($logsList,$index = 0){
        if (!isset($logsList[$index])){
            $this->log("没有日志");
            return [];
        }

        $nowLog = json_decode($logsList[$index]['FormString'], true);
        if (isset($nowLog['publishOn']) && $nowLog['publishOn'] && isset($nowLog['status']) && $nowLog['status'] == 'materialComplete'){
            $this->log("是发布完成的日志更新，跳过");
            $index++;
            return $this->findNoMaterialStatusDate($logsList,$index);
        }

        return $nowLog;
    }



    public function exportBeforeSkuMaterial()
    {
        $curlService = (new CurlService())->pro();


        $fileContent = (new ExcelUtils())->getXlsxData("../export/uploads/default/sku资料呈现资料发布前广告信息内容_20250731110340.xlsx");

        $exportListKeywords = [];
        $exportListAsins = [];
        $exportListFitments = [];

        foreach ($fileContent as $info){


            if ($info['核心词']){
                $keywords = json_decode($info['核心词'],true);
                foreach ($keywords as $keyword){

                    $exportListKeywords[] = [
                        "ceBillNo" => $info['ceBillNo'],
                        "skuId" => $info['skuId'],
                        "keyword" => $keyword,
                    ];

                }
            }

            if ($info['asin']){
                $asins = json_decode($info['asin'],true);
                foreach ($asins as $asin){
                    $exportListAsins[] = [
                        "ceBillNo" => $info['ceBillNo'],
                        "skuId" => $info['skuId'],
                        "asin" => $asin,
                    ];
                }
            }

            if ($info['车型']){
                $fitments = json_decode($info['车型'],true);
                foreach ($fitments as $fitment){
                    $exportListFitments[] = [
                        "ceBillNo" => $info['ceBillNo'],
                        "skuId" => $info['skuId'],
                        "make" => $fitment['make'],
                        "model" => $fitment['model'],
                    ];
                }
            }

        }




        if (count($exportListKeywords) > 0){
            $excelUtils = new ExcelUtils();
            $filePath = $excelUtils->downloadXlsx(["ceBillNo","skuId","核心词"],$exportListKeywords,"sku资料呈现资料发布前广告信息核心词_".date("YmdHis").".xlsx");
            $this->log($filePath);
        }else{
            $this->log("没有数据");

        }
        if (count($exportListAsins) > 0){
            $excelUtils = new ExcelUtils();
            $filePath = $excelUtils->downloadXlsx(["ceBillNo","skuId","asin"],$exportListAsins,"sku资料呈现资料发布前广告信息CP_Asin_".date("YmdHis").".xlsx");
            $this->log($filePath);
        }else{
            $this->log("没有数据");

        }
        if (count($exportListFitments) > 0){
            $excelUtils = new ExcelUtils();
            $filePath = $excelUtils->downloadXlsx(["ceBillNo","skuId","make","model"],$exportListFitments,"sku资料呈现资料发布前广告信息车型_".date("YmdHis").".xlsx");
            $this->log($filePath);
        }else{
            $this->log("没有数据");

        }



    }




    public function fixCeMaterialSSSS()
    {
        $curlService = (new CurlService())->pro();

        $fileFitContent = (new ExcelUtils())->getXlsxData("../export/uploads/20250731修复资呈数据.xlsx");
        if (sizeof($fileFitContent) > 0) {
            $ceBillNoSkuIdMap = [];

            if (isset($fileFitContent['核心词'])){
                foreach ($fileFitContent['核心词'] as $info){
                    $ceBillNoSkuIdMap[$info['ceBillNo']][$info['skuId']]["keywords"][] = $info['核心词'];
                }
            }

            if (isset($fileFitContent['asin'])){
                foreach ($fileFitContent['asin'] as $info){
                    $ceBillNoSkuIdMap[$info['ceBillNo']][$info['skuId']]["cpAsin"][] = $info['asin'];
                }
            }

            if (isset($fileFitContent['车型'])){
                $uniqFitmentMap = [];
                foreach ($fileFitContent['车型'] as $info){

                    $uniqFitment = md5($info['ceBillNo'] . $info['skuId'] . $info['make'] . $info['model']);
                    if (!isset($uniqFitmentMap[$uniqFitment])){
                        $ceBillNoSkuIdMap[$info['ceBillNo']][$info['skuId']]["fitment"][] = [
                            "make" => $info['make'],
                            "model" => $info['model']
                        ];

                        $uniqFitmentMap[$uniqFitment] = 1;
                    }

                }
            }

            if (count($ceBillNoSkuIdMap) > 0){

                foreach ($ceBillNoSkuIdMap as $ceBillNo => $list){

                    //资料发布的需要修复数据
                    if (count($list) > 0){
                        $this->log("资料发布了需要修复：{$ceBillNo}");

                        foreach ($list as $skuId => $dataInfo){
                            $detailInfo = DataUtils::getPageDocListInFirstDataV1(
                                $curlService->s3044()->get("pa_sku_materials/queryPage", [
                                    "ceBillNo" => $ceBillNo,
                                    "skuId" => $skuId,
                                    "limit" => 1
                                ])
                            );
                            if ($detailInfo){
                                $detailInfo['fitment'] = $dataInfo['fitment'] ?? [];
                                $detailInfo['keywords'] = array_unique($dataInfo['keywords'] ?? []);
                                $detailInfo['cpAsin'] = array_unique($dataInfo['cpAsin'] ?? []);
                                $detailInfo['modifiedBy'] = "system(sp-fix-angang)";


                                $this->log(json_encode($detailInfo,JSON_UNESCAPED_UNICODE));


                                $ss = $curlService->s3044()->put("pa_sku_materials/{$detailInfo['_id']}", $detailInfo);
                                if ($ss){
                                    $this->log("更新完毕");
                                }
                            }
                        }

                    }


                }


            }

        }

    }


    public function mergeSkuMaterialXlsx()
    {
        $curlService = (new CurlService())->pro();

        $keywords = [];
        $cpasin = [];
        $fitments = [];
        for($page = 7;$page <= 7;$page++){
            $fileFitContent = (new ExcelUtils())->getXlsxDataV2("../export/skuMaterial/zicheng/{$page}.xlsx");
            if (sizeof($fileFitContent) > 0) {
                foreach ($fileFitContent as $sheet => $sheetList) {
                    if ($sheet === '核心词'){
                        $keywords = array_merge($keywords,$sheetList);
                    }else if ($sheet === '热销车型'){
                        $fitments = array_merge($fitments,$sheetList);
                    }else if ($sheet === 'CP asin'){
                        $cpasin = array_merge($cpasin,$sheetList);
                    }
                }
            }
        }

        if (count($keywords) > 0){
            $excelUtils = new ExcelUtils("skuMaterial/");
            $filePath = $excelUtils->downloadXlsx(["运营人员","CE#","skuId","核心词"],$keywords,"sku资料呈现核心词_".date("YmdHis").".xlsx");
        }
        if (count($cpasin) > 0){
            $excelUtils = new ExcelUtils("skuMaterial/");
            $filePath = $excelUtils->downloadXlsx(["运营人员","CE#","skuId","asin"],$cpasin,"sku资料呈现CP_Asin_".date("YmdHis").".xlsx");
        }
        if (count($fitments) > 0){
            $excelUtils = new ExcelUtils("skuMaterial/");
            $filePath = $excelUtils->downloadXlsx(["运营人员","CE#","skuId","make","model"],$fitments,"sku资料呈现热销车型_".date("YmdHis").".xlsx");
        }

    }

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
        if ($list){
            $sameSkuIdMap = [];
           foreach ($list as &$item){
               if (!isset($sameSkuIdMap[$item['skuId']])){
                   if ($item['keywords']){
                       $item['keywords'] = array_unique($item['keywords']);
                   }
                   if ($item['cpAsin']){
                       $item['cpAsin'] = array_unique($item['cpAsin']);
                   }
                   if ($item['fitment']){
                       $quchongfitment = [];
                       $uniqFitmentMap = [];
                       foreach ($item['fitment'] as $info){
                           $uniqFitment = md5($info['make'] . $info['model']);
                           if (!isset($uniqFitmentMap[$uniqFitment])){
                               $quchongfitment[] = [
                                   "make" => $info['make'],
                                   "model" => $info['model']
                               ];
                               $uniqFitmentMap[$uniqFitment] = 1;
                           }
                       }
                       if ($quchongfitment){
                           $item['fitment'] = $quchongfitment;
                       }
                   }
                   $sameSkuIdMap[$item['skuId']] = 1;
                   $this->log(json_encode($item,JSON_UNESCAPED_UNICODE));

                   $ss = $curlService->s3044()->put("pa_sku_materials/{$item['_id']}", $item);
                   if ($ss){
                       $this->log("更新完毕");
                   }
               }else{
                   $this->log("{$item['skuId']} 重复了,删掉一个");
                   $curlService->s3044()->delete("pa_sku_materials/{$item['_id']}");
               }

           }



        }


    }



    public function fixMergeADSguId()
    {
        $curlService = (new CurlService())->pro();

        $fileFitContent = (new ExcelUtils())->getXlsxData("../export/QD202508010003.xlsx");
        if (sizeof($fileFitContent) > 0) {

            $list = array_column($fileFitContent,"sku_id");
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
            $map  =[];
            if ($getKeyResp){
                foreach ($getKeyResp as $item){
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

//            $qdScmsPrePurchaseMap = [];
//            $fix30List = [];
//            foreach ($fileFitContent as $info){
//
//                if (isset($map[$info['sku_id']])){
//                    $skuAttrData = $map[$info['sku_id']];
//
//                    if (!empty($skuAttrData['custom-sguInfo-groupTag'])){
//
//
//                        $sguKey = "sgu_init_{$info['batch_no']}_{$skuAttrData['custom-sguInfo-groupTag']}";
//                        $this->log("{$sguKey}");
//                        $sguId = "";
//                        if(!isset($qdScmsPrePurchaseMap[$sguKey])){
//
//                            //当前key重新创建sgu
//                            $sguId = $this->createSguInfo();
//
//                            $this->log("{$info['sku_id']} 绑定 {$sguId}");
//
//
//                            $qdScmsPrePurchaseMap[$sguKey] = $sguId;
//
//                        }else{
//                            $this->log("同key：{$qdScmsPrePurchaseMap[$sguKey]}");
//                            $sguId = $qdScmsPrePurchaseMap[$sguKey];
//
//
//                        }
//
//                        if (!empty($sguId)){
//                            $this->log("{$sguKey} 生成 {$sguId}");
//
//                            $sguInfo = DataUtils::getQueryListInFirstDataV3($curlService->s3015()->get("/sgu-sku-scu-maps/query",[
//                                "skuScuId" => $info['sku_id'],
//                                "limit" => 1,
//                            ]));
//                            if ($sguInfo){
//
//                                $sguInfo['sguId'] = $sguId;
//                                $sguInfo['modifiedBy'] = "system(zhouangang)";
//                                $curlService->s3015()->put("sgu-sku-scu-maps/{$sguInfo['_id']}", $sguInfo);
//
//                            }else{
//                                //创建
//
//                                $channelList = explode(",",$skuAttrData['custom-sguInfo-channel']);
//                                $channelListData = [];
//                                foreach ($channelList as $ch){
//                                    $channelListData[] = [
//                                        "groupName" => "",
//                                        "groupAttrName" => [],
//                                        "groupAttrValue" => [],
//                                        "channel" => $ch,
//                                        "modifiedBy" => "system(zhouangang)",
//                                        "modifiedOn" => date("Y-m-d H:i:s",time ())."Z"
//                                    ];
//                                }
//                                $create = [
//                                    "createdBy" => "system(zhouangang)",
//                                    "modifiedBy" => "system(zhouangang)",
//                                    "skuScuId" => $info['sku_id'],
//                                    "sguId" => $sguId,
//                                    "remark" => "sgu自动绑定和初始化",
//                                    "channel" => $channelListData,
//                                    "createdOn" => date("Y-m-d H:i:s",time ())."Z",
//                                    "modifiedOn" => date("Y-m-d H:i:s",time ())."Z"
//                                ];
//                                $curlService->s3015()->post("/sgu-sku-scu-maps",$create);
//
//                            }
//
//                            $fix30List[] = [
//                                "tempSkuId" => $skuAttrData['custom-skuInfo-tempSkuId'],
//                                "skuAttrList" => [
//                                    [
//                                        "name" => "custom-sguInfo-sguId",
//                                        "value" => $sguId
//                                    ]
//                                ]
//                            ];
//
//                            if(isset($qdScmsPrePurchaseMap[$sguKey])){
//
//                                $resp = DataUtils::getNewResultData($curlService->getWayPost($curlService->module . "/sms/sku/info/init/v1/initSkuInfo", [
//                                    "initSkuId" => $info['sku_id'],
//                                    "operatorName" => "system(修复sgu初始化)",
//                                    "productType" => "SGU",
//                                    "sguId" => $sguId
//                                ]));
//
//                            }
//
//
//                        }else{
//                            $this->log("{$sguKey} 生成g号失败");
//                        }
//
//
//
//                    }else{
//                        $this->log("没有绑定g号，不用初始化");
//                    }
//
//                }
//
//
//
//
//            }
//
//
//
//            //回写3.0
//            if ($fix30List){
//                $curlSsll = (new CurlService())->pro()->gateway()->getModule("pa");
//                $getKeyResp = DataUtils::getNewResultData($curlSsll->getWayPost($curlSsll->module . "/ppms/product_dev/sku/v1/directUpdateBatch", [
//                    "operator" => "zhouangang",
//                    "skuList" => $fix30List
//                ]));
//
//            }


        }





    }


    public function fixMergeADV2SguId()
    {
        $curlService = (new CurlService())->pro();

        $fileFitContent = (new ExcelUtils())->getXlsxData("../export/AD1_2.xlsx");
        if (sizeof($fileFitContent) > 0) {
            $list = [];
            foreach ($fileFitContent as $info){
                if(!empty($info['sku_id'])){
                    $list[] = $info['sku_id'];
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
                    "custom-skuInfo-tempSkuId",
                    "custom-prePurchase-prePurchaseBillNo"
                ]
            ]));
            $map  =[];
            if ($getKeyResp){

                $exportList = [];
                foreach ($getKeyResp as $item){
                    $exportList[] = [
                        "batchNo" => $item['custom-common-batchNo'],
                        "prePurchaseBillNo" => $item['custom-prePurchase-prePurchaseBillNo'],
                        "skuId" => $item['custom-skuInfo-skuId'],
                        "groupTag" => $item['custom-sguInfo-groupTag'],
                        "sguId" => $item['custom-sguInfo-sguId']
                    ];
                }


                $excelUtils = new ExcelUtils();
                $downloadOssLink = "AD250729000020_QD问题_" . date("YmdHis") . ".xlsx";
                $filePath = $excelUtils->downloadXlsx(["batchNo","prePurchaseBillNo","skuId","groupTag","sguId"],$exportList,$downloadOssLink);
                $this->log($filePath);

            }

        }





    }



    public function createSguInfo()
    {
        $curlService = (new CurlService())->pro();
        $res = DataUtils::getResultData($curlService->s3015()->get("soaps/inventory/createSguInfo",['createdBy' => "system(zhouangang)"]));
        if ($res){
            return $res;
        }
        return "";
    }



    public function fixProductSku(){
        $fileFitContent = (new ExcelUtils())->getXlsxData("../export/需要修复的upc.xlsx");
        $fitmentSkuMap = [];
        if (sizeof($fileFitContent) > 0) {

            $curlService = (new CurlService())->pro();

            $list = array_unique(array_column($fileFitContent,"skuId"));


            $infoList = DataUtils::getPageList($curlService->s3015()->get("product-skus/queryPage",[
                "productId" => implode(",",$list),
                "limit" => 500
            ]));
            $map = [];
            if ($infoList){
                foreach ($infoList as $info){
                    $map[$info['productId']] = $info;
                }
            }

            foreach ($fileFitContent as $info){

                if (isset($map[$info['skuId']])){
                    $productInfo = $map[$info['skuId']];
                    ProductUtils::editProductAttribute($productInfo['attribute'], "upc", $info['channel'], $info['upc']);

                    $productInfo['action'] = "system(amazon_es修复upc)250822";
                    $productInfo['userName'] = "system(zhouangang)";

                    $this->log(json_encode($productInfo,JSON_UNESCAPED_UNICODE));
                    $resp = $curlService->s3015()->post("product-skus/updateProductSku?_id={$productInfo['_id']}",$productInfo);
                    if ($resp){

                    }
                }
            }
        }



    }
}

$curlController = new SyncCurlController();
$curlController->fixProductSku();
//$curlController->fixMergeADV2SguId();
//$curlController->fixMergeADSguId();
//$curlController->createSguInfo();
//$curlController->fixRepeatSkuMaterial();
//$curlController->fixRepeatSkuMaterial();
//$curlController->mergeSkuMaterialXlsx();
//$curlController->fixCeMaterialSSSS();
//$curlController->exportBeforeSkuMaterial();
//$curlController->getRepeatSkuMaterialByAliSls();
//$curlController->getRepeatSkuMaterial();
//$curlController->fixTranslationManagement();
//$curlController->fixPaSkuMaterialList();
//$curlController->ssss();
//$curlController->fixCeMaterialS();
//$curlController->fixCeMaterial();
//$curlController->ceMaterialObjectLog();
//$curlController->findPrePurchaseBillWithSkuForSkuMaterialInfo();
//$curlController->updateEuSharedWarehouseFlowTypePriority();
//$curlController->getCEBillNo();
//$curlController->updatePaSkuMaterial();
//$curlController->downloadPaSkuMaterialSP();
//$curlController->test();
//$curlController->fix();
//$curlController->syncSkuMaterialToAudit();
//$curlController->fixPaSkuPhotoGress();
//$curlController->updateSkuMaterial();
//$curlController->syncPaSkuMaterial();
//$curlController->copyNewChannel();
//$curlController->updatePaGoodsSourceManage();
//$curlController->getAmazonSpKeyword();
//$curlController->syncSkuSellerConfig();
//$curlController->skuMaterialDocCreate();
//$curlController->fixProductOpt();
//$curlController->fixSkuPhotoProcess();
//$curlController->updateProductListNo();
//$curlController->deleteFC();
//$curlController->combineFC();
//$curlController->updateProductFba();
//$curlController->updateFcuProductLine();
//$curlController->getPaSkuMaterial();
//$curlController->syncAllVerticalMonthlTargets();
//$curlController->ceWrite();
//$curlController->updateCeMaterialPlatform();
//$curlController->updatePaProductTempSkuIdNew();
//$curlController->writeProductBaseFba();
//$curlController->writeScmsPurchaseBillNo();
//$curlController->get30PpmsByTempskuid();
//$curlController->updateSalesUserNameCancel2();
//$curlController->syncDevSkuInfoToProductSku();
//$curlController->saveReceiveIpCheck();
//$curlController->commonFindOneByParams("s3044", "pa_ce_materials", ["batchName" => "20201221 - 李锦烽 - 1"]);
//$curlController->deleteCampaign();
//$curlController->createPmo();

//$curlController->updateZhixiao();
//$curlController->fixSkuVerticalId();
//$curlController->fixAmazonSpRuleId();
//$curlController->findCampaign();
//$curlController->downloadPaSkuPhotoProgress();
//$curlController->week();
//$curlController->test();