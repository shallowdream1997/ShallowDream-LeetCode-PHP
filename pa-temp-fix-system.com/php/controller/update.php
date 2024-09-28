<?php
//require_once dirname(__FILE__) .'/../../vendor/autoload.php';

require_once dirname(__FILE__) .'/../requiredfile/requiredChorm.php';

class update{


    public $logger;

    public function __construct(){
        $this->logger = new MyLogger("option/updateLog");
    }

    public function pageSwitchConfig($params){
        $curlService = (new CurlService())->test();

        $env = $curlService->environment;

        $batchNameList = $params['batchNameList'];
        $batchNameList = array_unique($batchNameList);
        $status = $params['status'] ?? 2;
        $oldList = DataUtils::getPageList($curlService->s3015()->post("pa_products/queryPagePost", [
                "limit" => count($batchNameList),
                "page" => 1,
                "batchName_in" => implode(",",$batchNameList)
            ]
        ));
        $ids = [];
        if (!empty($oldList)){
            $ids = array_column($oldList,"_id");
        }
        if (count($ids) > 0){
            $this->logger->log("准备修改：".json_encode($batchNameList,JSON_UNESCAPED_UNICODE));
            $info = DataUtils::getPageListInFirstData($curlService->s3015()->get("option-val-lists/queryPage", [
                "optionName" => "page_switch_config",
                "limit" => 1
            ]));
            if ($info) {
                if ($status == 1){
                    $info['optionVal']['pa_product_detail_submit_leader_review']['paProductIds'] = $ids;
                }elseif ($status == 2){
                    $info['optionVal']['pa_product_detail_submit_leader_review']['paProductIds'] = array_merge($info['optionVal']['pa_product_detail_submit_leader_review']['paProductIds'],$ids);
                    $info['optionVal']['pa_product_detail_submit_leader_review']['paProductIds'] = array_unique($info['optionVal']['pa_product_detail_submit_leader_review']['paProductIds']);
                }
                $curlService->s3015()->put("option-val-lists/{$info['_id']}",$info);

                return true;
            }else {
                return false;
            }

        }else {
           return false;
        }

    }

    public function fixTranslationManagements($params){
        $curlService = (new CurlService())->test();
        $env = $curlService->environment;

        if (!DataUtils::checkArrFilesIsExist($params,"status")){
            return false;
        }
        $status = $params['status'];

        if (DataUtils::checkArrFilesIsExist($params,"title")){
            $mainInfo = DataUtils::getPageListInFirstData($curlService->s3015()->get("translation_managements/queryPage",[
                "limit" => 100,
                "page" => 1,
                "title_in" => $params['title'],
            ]));
            if ($mainInfo['status'] != "5") {
                $mainInfo['status'] = $status;
                foreach ($mainInfo['skuIdList'] as &$detailInfo) {
                    $detailInfo['status'] = $status;
                }
                if ($status == '4' && !empty($applyName) && !empty($applyTime)){
                    //翻译完成的需要审核人
                    $mainInfo['applyUserName'] = $applyName;
                    $mainInfo['applyTime'] = $applyTime;
                }
                $updateMainRes = DataUtils::getResultData($curlService->s3015()->put("translation_managements/{$mainInfo['_id']}",$mainInfo));
                $this->logger->log("修改成功" . json_encode($updateMainRes, JSON_UNESCAPED_UNICODE));

                $detailList = DataUtils::getPageList($curlService->s3015()->get("translation_management_skus/queryPage",[
                    "limit" => 1000,
                    "translationMainId" => $mainInfo['_id']
                ]));
                if ($detailList) {
                    foreach ($detailList as $detail) {
                        if ($detail['status'] != "5") {
                            $detail['status'] = $status;

                            DataUtils::getResultData($curlService->s3015()->put("translation_management_skus/{$detail['_id']}",$detail));
                        }
                    }
                }

                return true;
            }
        }else{
            return false;
        }

    }

}


$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['action']) || empty($data['action'])){
    return json_encode([],JSON_UNESCAPED_UNICODE);
}

$class = new update();
$return = [];

switch ($data['action']){
    case "pageSwitchConfig":
        $params = isset($data['params']) ? $data['params'] : [];
        $return = $class->pageSwitchConfig($params);
        break;
    case "fixTranslationManagements":
        $params = isset($data['params']) ? $data['params'] : [];
        $return = $class->fixTranslationManagements($params);
        break;
}

echo json_encode($return,JSON_UNESCAPED_UNICODE);