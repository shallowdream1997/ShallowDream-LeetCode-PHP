<?php
require_once("../requiredfile/requiredfile.php");

class GatWayRequestController
{
    private $log;
    private $curlService;

    private $module = "platform-wms-application";

    public function __construct($port = 'test')
    {
        $this->log = new MyLogger("get_way_log");
        $this->curlService = new CurlService();
        $this->curlService->$port();

        $this->curlService->gateway();
    }

    public function getModule($modlue){
        switch ($modlue){
            case "wms":
                $this->module = "platform-wms-application";
                break;
            case "pa":
                $this->module = "pa-biz-application";
                break;
        }

        return $this;
    }


    public function getReceiveSampleExpectPage()
    {
        $skuIdList = [
            'a24051700ux1253',
            "a24073100ux0456",
            "a24073100ux0465",
            "a24061700ux0413",
            "a24061700ux0415",
            "a24061700ux0424",
            "a24061700ux0426",
            "a24061700ux0429",
            "a24051700ux1245",
            "a24051700ux1249",
            "a24051700ux1253",
        ];
        $resp = DataUtils::getNewResultData($this->getModule('wms')->curlService->getWayPost($this->module . "/receive/sample/expect/v1/page", [
            "skuIdIn" => $skuIdList,
            "vertical" => "PA",
            "category" => "dataTeam",
            "pageSize" => 500,
            "pageNum" => 1,
        ]));
        $hasSampleSkuIdList = [];
        if (DataUtils::checkArrFilesIsExist($resp, 'list')) {
            $hasSampleSkuIdList = array_column($resp['list'], 'skuId');
            $this->log->log2("部分sku：" . implode(",", $hasSampleSkuIdList) . " 均已经留样，过滤....");
        }
        $skuIdList = array_diff($skuIdList, $hasSampleSkuIdList);

        $needSampleSkuIdList = [];
        foreach ($skuIdList as $skuId) {
            $needSampleSkuIdList[] = [
                "category" => "dataTeam",
                "createBy" => "zhouangang",
                "remark" => "",
                "skuId" => $skuId,
                "vertical" => "PA"
            ];
        }
        if (count($needSampleSkuIdList) > 0) {
            $createResp = DataUtils::getNewResultData($this->getModule('wms')->curlService->getWayPost($this->module . "/receive/sample/expect/v1/batchCreate", $needSampleSkuIdList));
            if ($createResp && $createResp['value']) {
                $this->log->log2("剩余sku：" . implode(',', array_column($needSampleSkuIdList, 'skuId')) . " 留样打标成功...");
            } else {
                $this->log->log2("留样打标失败");
            }
        } else {
            $this->log->log2("预计留样的数据都已存在，无需留样");
        }

    }

}

$getWay = new GatWayRequestController('test');
$getWay->getReceiveSampleExpectPage();