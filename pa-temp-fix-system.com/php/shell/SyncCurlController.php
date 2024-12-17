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
    public function __construct()
    {
        $this->log = new MyLogger("common-curl/curl");

        $curlService = new CurlService();
        $this->curl = $curlService;
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

    public function commonCreate($port, $model, $params)
    {
        $curlService = new CurlService();
        $resp = DataUtils::getResultData($curlService->test()->$port()->post("{$model}", $params));
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
        $dpmoList = $this->commonFindByParams("s3044","pa_sku_materials",[
            "limit" => 1000,
            "createdBy" => "P3-CreateCeSkuMaterialJob"
        ],"pro");
        $_idList = [];
        if (count($dpmoList)){
            foreach ($dpmoList as &$item){
                if ($item['parentSkuId'] === null){
                    $item['parentSkuId'] = "";
                    $this->commonUpdate("s3044","pa_sku_materials",$item,"pro");
                }
            }

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
            "ceBillNo_in" => "CE202410180103"
        ],"pro");
        foreach ($list as &$item){
            $item['createCeBillNoOn'] = "2024-10-18 15:11:02Z";
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


    public function updateTempSkuId20()
    {
        $excelUtils = new ExcelUtils();
        $fileName = "../export/";
        try {
            $list = $excelUtils->getXlsxData($fileName . "重复T号.xlsx");
        } catch (Exception $e) {
            die("获取数据失败");
        }
        if (count($list) > 0){
            $mainRealList = [];
            $cancelMainList = [];
            $paProductIds = array_unique(array_column($list, "paProductId"));
            $rq1 = new RequestUtils("pro");
            foreach (array_chunk($paProductIds, 200) as $chunk) {
                $mainList = $rq1->getPaProductPageList([
                    "limit" => 200,
                    "_id_in" => implode(",", $chunk)
                ]);
                if (count($mainList) > 0) {
                    foreach ($mainList as $info) {
                        if ($info['status'] == "cancel") {
                            $cancelMainList[$info['_id']] = $info['batchName'];
                        } else {
                            $mainRealList[$info['_id']] = $info['batchName'];
                        }
                    }
                }
            }

            foreach ($list as $info) {
                if (isset($cancelMainList[$info['paProductId']])) {
                    //已经作废的
                    $this->log("已经作废了：{$cancelMainList[$info['paProductId']]}");
                }else{
                    //没有作废的


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


}

$curlController = new SyncCurlController();
//$curlController->updateCeMaterialPlatform();
$curlController->getCompanyByCompanyId();
//$curlController->commonFindOneByParams("s3044", "pa_ce_materials", ["batchName" => "20201221 - 李锦烽 - 1"]);
//$curlController->deleteCampaign();