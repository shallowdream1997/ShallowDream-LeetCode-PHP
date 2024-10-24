<?php
//require_once dirname(__FILE__) .'/../../vendor/autoload.php';

require_once dirname(__FILE__) . '/../requiredfile/requiredChorm.php';

/**
 * 更新接口
 * Class update
 */
class update
{


    public $logger;

    private $module = "pa-biz-application";

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
        $curlService = (new CurlService())->pro();

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
        $curlService = (new CurlService())->pro();
        $env = $curlService->environment;

        $status = $params['status'];

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
        $curlService = (new CurlService())->pro();
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
        $curlService = (new CurlService())->pro();
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
        $curlService = (new CurlService())->pro();
        $env = $curlService->environment;
        if (isset($params['addskuIdList']) && $params['addskuIdList']) {
            $skuIdList = $params['addskuIdList'];

            $curlService->gateway();
            $this->getModule('wms');
            $resp = DataUtils::getNewResultData($curlService->getWayPost($this->module . "/receive/sample/expect/v1/page", [
                "skuIdIn" => $skuIdList,
                "vertical" => "PA",
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
                    "vertical" => "PA"
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


}


$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['action']) || empty($data['action'])) {
    return json_encode([], JSON_UNESCAPED_UNICODE);
}

$class = new update();
$return = [];

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
}

echo json_encode($return, JSON_UNESCAPED_UNICODE);