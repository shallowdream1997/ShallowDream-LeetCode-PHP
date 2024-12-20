<?php
require dirname(__FILE__) . '/../../vendor/autoload.php';

require_once dirname(__FILE__) . '/../requiredfile/requiredChorm.php';
require_once dirname(__FILE__) . '/EnvironmentConfig.php';
require_once dirname(__FILE__) . '/../shell/ProductSkuController.php';

/**
 * 更新接口
 * Class update
 */
class update
{


    public $logger;

    private $module = "pa-biz-application";

    /**
     * @var CurlService
     */
    public $envService;
    public function __construct()
    {
        $this->logger = new MyLogger("option/updateLog");
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

    /**
     * 重复品豁免
     * @param $params
     * @return bool
     */
    public function pageSwitchConfig($params)
    {
        $curlService = $this->envService;

        $env = $curlService->environment;

        $batchNameList = $params['batchNameList'];
        $batchNameList = array_unique($batchNameList);

        $oldList = DataUtils::getPageList($curlService->s3015()->post("pa_products/queryPagePost", [
                "limit" => count($batchNameList),
                "page" => 1,
                "batchName_in" => implode(",", $batchNameList)
            ]
        ));
        $ids = [];
        if (!empty($oldList)) {
            $ids = array_column($oldList, "_id");
        }
        if (count($ids) > 0) {
            $this->logger->log("准备修改：" . json_encode($batchNameList, JSON_UNESCAPED_UNICODE));
            $info = DataUtils::getPageListInFirstData($curlService->s3015()->get("option-val-lists/queryPage", [
                "optionName" => "page_switch_config",
                "limit" => 1
            ]));
            if ($info) {
                $info['optionVal']['pa_product_detail_submit_leader_review']['paProductIds'] = array_merge($info['optionVal']['pa_product_detail_submit_leader_review']['paProductIds'], $ids);
                $info['optionVal']['pa_product_detail_submit_leader_review']['paProductIds'] = array_unique($info['optionVal']['pa_product_detail_submit_leader_review']['paProductIds']);

                $curlService->s3015()->put("option-val-lists/{$info['_id']}", $info);

                return true;
            } else {
                return false;
            }

        } else {
            return false;
        }

    }

    /**
     * 翻译
     * @param $params
     * @return array
     */
    public function fixTranslationManagements($params)
    {
        $curlService = $this->envService;
        $env = $curlService->environment;

        $status = $params['status'];
        $applyName = $params['applyName'];
        $applyTime = $params['applyTime'];
        if (DataUtils::checkArrFilesIsExist($params, "title")) {
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
                $this->logger->log("修改成功" . json_encode($updateMainRes, JSON_UNESCAPED_UNICODE));

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

                return true;
            }
        } else {
            return false;
        }

    }

    /**
     * 资料呈现
     * @param $params
     * @return array
     */
    public function fixCeMaterials($params)
    {
        $curlService = $this->envService;
        $env = $curlService->environment;

        $_id = $params['_id'];
        $status = $params['status'];
        if (empty($_id) && empty($status)) {
            return false;
        }

        $res = DataUtils::getResultData($curlService->s3044()->get("pa_ce_materials/{$_id}"));

        if ($res && $res['status'] == 'success' && DataUtils::checkArrFilesIsExist($res, 'data')) {
            $mainInfo = $res['data'];
            $mainInfo['status'] = $status;

            $updateRes = DataUtils::getResultData($curlService->s3044()->put("pa_ce_materials/{$mainInfo['_id']}", $mainInfo));
            if ($updateRes && $updateRes['status'] == 'success' && DataUtils::checkArrFilesIsExist($updateRes, 'data')) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }

    }


    /**
     * 上架前海外仓移库申请
     * @param $params
     * @return array|false
     */
    public function paFbaChannelSellerConfig($params)
    {
        $curlService = $this->envService;
        $env = $curlService->environment;

        if (isset($params['channel']) && $params['channel'] && isset($params['stocksList']) && $params['stocksList']) {
            $channel = $params['channel'];
            $stocksList = $params['stocksList'];

            $info = DataUtils::getPageListInFirstData($curlService->s3015()->get("option-val-lists/queryPage", [
                "optionName" => "pa_fba_channel_seller_config",
                "limit" => 1
            ]));
            if (!empty($info)) {

                if (isset($info['optionVal']['amazon'][$channel]) && !empty($info['optionVal']['amazon'][$channel])) {
                    $info['optionVal']['amazon'][$channel] = array_merge($info['optionVal']['amazon'][$channel], $stocksList);
                } else {
                    $info['optionVal']['amazon'][$channel] = array_merge([], $stocksList);
                }
                $info['optionVal']['amazon'][$channel] = array_unique($info['optionVal']['amazon'][$channel]);

                $curlService->s3015()->put("option-val-lists/{$info['_id']}", $info);

                return true;
            }
            return false;
        } else {
            return false;
        }

    }

    public function paSampleSku($params)
    {
        $curlService = $this->envService;
        $env = $curlService->environment;
        if (isset($params['addskuIdList']) && $params['addskuIdList']) {
            $skuIdList = $params['addskuIdList'];

            $curlService->gateway();
            $this->getModule('wms');
            $resp = DataUtils::getNewResultData($curlService->getWayPost($this->module . "/receive/sample/expect/v1/page", [
                "skuIdIn" => $skuIdList,
                "vertical" => "PA",
                "state" => 10,
                "category" => "dataTeam",
                "pageSize" => 500,
                "pageNum" => 1,
            ]));
            $hasSampleSkuIdList = [];
            if (DataUtils::checkArrFilesIsExist($resp, 'list')) {
                $hasSampleSkuIdList = array_column($resp['list'], 'skuId');
                $this->logger->log("部分sku：" . implode(",", $hasSampleSkuIdList) . " 均已经留样，过滤....");
            }
            $skuIdList = array_diff($skuIdList, $hasSampleSkuIdList);

            $needSampleSkuIdList = [];
            foreach ($skuIdList as $skuId) {
                $needSampleSkuIdList[] = [
                    "category" => "dataTeam",
                    "createBy" => "pa-fix-system",
                    "remark" => "",
                    "skuId" => $skuId,
                    "vertical" => "PA",
                    "state" => 10
                ];
            }
            if (count($needSampleSkuIdList) > 0) {
                $createResp = DataUtils::getNewResultData($curlService->getWayPost($this->module . "/receive/sample/expect/v1/batchCreate", $needSampleSkuIdList));
                if ($createResp && $createResp['value']) {
                    $this->logger->log("剩余sku：" . implode(',', array_column($needSampleSkuIdList, 'skuId')) . " 留样打标成功...");
                    return true;
                } else {
                    $this->logger->log("留样打标失败");
                    return false;
                }
            } else {
                $this->logger->log("预计留样的数据都已存在，无需留样");
                return true;
            }
        }else{
            return false;
        }

    }

    public function paProductList($params){
        $curlService = $this->envService;

        if (isset($params['excel']) && $params['excel']) {
            $env = $curlService->environment;
            $requestUtils = new RequestUtils($env);
            $productSkuController = new ProductSkuController($env);

            //获取批次号
            if (!$params['excel']['batchName']){
                return [
                    "updateSuccess" => false,
                    "messages" => "批次号不存在"
                ];
            }
            //读取品牌配置化
            $brandMap = $productSkuController->getBrandAttributeByPaPomsSkuBrandInitConfig();

            $paProductIdCollectorList = $requestUtils->getPaProductInfoByBatchNameList([$params['excel']['batchName']]);
            if (count($paProductIdCollectorList) > 0) {

                if (!DataUtils::checkArrFilesIsExist($paProductIdCollectorList, $params['excel']['batchName'])) {
                    $this->logger->log("{$params['excel']['batchName']} 不存在清单列表");
                    return [
                        "updateSuccess" => false,
                        "messages" => "{$params['excel']['batchName']} 不存在清单列表"
                    ];
                }
                $batchInfo = $paProductIdCollectorList[$params['excel']['batchName']];
                $updatePPMainBoolean = $productSkuController->updatePPMain($batchInfo['paProductInfo'], $params['excel'],false);
                if (!$updatePPMainBoolean){
                    return [
                        "updateSuccess" => false,
                        "messages" => "开发清单主表更新失败"
                    ];
                }

                $updatePPDetailBoolean = $productSkuController->updatePPDetail($batchInfo['paProductDetailList'], $params['excel'],$brandMap);
                if (!$updatePPDetailBoolean || !$updatePPDetailBoolean['code']){
                    return [
                        "updateSuccess" => false,
                        "messages" => "开发清单明细或sku资料更新失败：" . json_encode($updatePPDetailBoolean['messages'],JSON_UNESCAPED_UNICODE)
                    ];
                }

            }

            return [
                "updateSuccess" => true,
                "messages" => "{$params['excel']['batchName']} 修改成功"
            ];

        }else{
            return [
                "updateSuccess" => false,
                "messages" => "{$params['excel']['batchName']} 修改失败"
            ];
        }
    }

    public function paFixProductLine($params){
        $curlService = $this->envService;
        $env = $curlService->environment;

        $productLineName = "";
        if (isset($params['productLineName']) && $params['productLineName']) {
            $productLineName = $params['productLineName'];
        }else{
            return [
                "updateSuccess" => false,
                "messages" => "要投入的sku产品线不能为空"
            ];
        }


        if (isset($params['skuIdList']) && $params['skuIdList']) {
            $list = [];
            $skuIdList = $params['skuIdList'];
            foreach (array_chunk($skuIdList,200) as $chunk){
                $getProductMainResp = DataUtils::getQueryList($curlService->s3009()->get("product-operation-lines/queryUserBySkuId", [
                    "skuId" => implode(",",$chunk)
                ]));
                if ($getProductMainResp){
                    $list = array_merge($list,$getProductMainResp);
                }
            }

            $skuIdProductLineMap = [];
            if (count($list) > 0){
                $skuIdProductLineMap = array_column($list,null,"skuId");
            }

            $resp = $curlService->s3009()->get("product-operation-lines/getProductOperatorMainInfoByProductLineName",[
                "productLineName" => $productLineName
            ]);
            if (empty($resp['result'])){
                //没有产品线，创建产品线
                $createProductMainResp = $curlService->s3009()->post("product-operation-lines/createProductOperatorMainInfo", [
                    "modifiedBy" => "pa_fix_system",
                    "createdBy" => "pa_fix_system",
                    "traceMan" => "",
                    "developer" => "",
                    "product_line_id" => "PA_NEW" . date("YmdHis",time()),
                    "productLineName" => $productLineName,
                    "companySequenceId" => "CR201706060001",
                ]);
                if ($createProductMainResp){

                    foreach ($skuIdList as $skuId){
                        if (!isset($skuIdProductLineMap[$skuId])){
                            $mainInfo = $createProductMainResp['result'];
                            $curlService->s3009()->post("product-operation-lines", [
                                "companySequenceId" => $mainInfo['companySequenceId'],
                                "productLineName" => $mainInfo['productLineName'],
                                "product_line_id" => "",
                                "sign" => "NP",
                                "developer" => "",
                                "traceMan" => "",
                                "createdBy" => "pa_fix_system",
                                "modifiedBy" => "pa_fix_system",
                                "createdOn" => date("Y-m-d H:i:s",time())."Z",
                                "verticalName" => "PA",
                                "operatorName" => "",
                                "skuId" => $skuId,
                                "userName" => "pa_fix_system",
                                "product_operator_mainInfo_id" => $mainInfo['_id'],
                                "batch" => "",
                                "factoryId" => "",
                                "supplyType" => null,
                                "styleId" => ""
                            ]);
                        }
                    }
                }

            }else{
                foreach ($skuIdList as $skuId){
                    if (!isset($skuIdProductLineMap[$skuId])){
                        $mainInfo = $resp['result'][0];
                        $curlService->s3009()->post("product-operation-lines", [
                            "companySequenceId" => $mainInfo['companySequenceId'],
                            "productLineName" => $mainInfo['productLineName'],
                            "product_line_id" => "",
                            "sign" => "NP",
                            "developer" => "",
                            "traceMan" => "",
                            "createdBy" => "pa_fix_system",
                            "modifiedBy" => "pa_fix_system",
                            "createdOn" => date("Y-m-d H:i:s",time())."Z",
                            "verticalName" => "PA",
                            "operatorName" => "",
                            "skuId" => $skuId,
                            "userName" => "pa_fix_system",
                            "product_operator_mainInfo_id" => $mainInfo['_id'],
                            "batch" => "",
                            "factoryId" => "",
                            "supplyType" => null,
                            "styleId" => ""
                        ]);
                    }
                }
            }

        }else{
            return [
                "updateSuccess" => false,
                "messages" => "要投入产品线的sku不能为空"
            ];
        }

        return [
            "updateSuccess" => true,
            "messages" => "sku和产品线已添加成功"
        ];
    }



}


$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['action']) || empty($data['action'])) {
    return json_encode([], JSON_UNESCAPED_UNICODE);
}

$class = new update();
$return = [];
$class->envService = (new EnvironmentConfig($data['action']))->getCurlService();

switch ($data['action']) {
    case "pageSwitchConfig":
        $params = isset($data['params']) ? $data['params'] : [];
        $return = $class->pageSwitchConfig($params);
        break;
    case "fixTranslationManagements":
        $params = isset($data['params']) ? $data['params'] : [];
        $return = $class->fixTranslationManagements($params);
        break;
    case "fixCeMaterials":
        $params = isset($data['params']) ? $data['params'] : [];
        $return = $class->fixCeMaterials($params);
        break;
    case "paFbaChannelSellerConfig":
        $params = isset($data['params']) ? $data['params'] : [];
        $return = $class->paFbaChannelSellerConfig($params);
        break;
    case "paSampleSku":
        $params = isset($data['params']) ? $data['params'] : [];
        $return = $class->paSampleSku($params);
        break;
    case "paProductList":
        $params = isset($data['params']) ? $data['params'] : [];
        $return = $class->paProductList($params);
        break;
    case "paFixProductLine":
        $params = isset($data['params']) ? $data['params'] : [];
        $return = $class->paFixProductLine($params);
        break;
}

echo json_encode($return, JSON_UNESCAPED_UNICODE);