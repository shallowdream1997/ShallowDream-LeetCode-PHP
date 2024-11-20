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

    public function __construct()
    {
        $this->log = new MyLogger("common-curl/curl");

        $curlService = new CurlService();
        $this->curl = $curlService;
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
        $resp = DataUtils::getResultData($curlService->test()->post("{$model}", $params));
        $this->log("创建{$model}，" . json_encode($params, JSON_UNESCAPED_UNICODE) . "返回结果：" . json_encode($resp, JSON_UNESCAPED_UNICODE));
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
}

//$curlController = new SyncCurlController();
//$curlController->commonFindOneByParams("s3044", "pa_ce_materials", ["batchName" => "20201221 - 李锦烽 - 1"]);
//$curlController->deleteCampaign();