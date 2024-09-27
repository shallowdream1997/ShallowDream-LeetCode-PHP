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
}

echo json_encode($return,JSON_UNESCAPED_UNICODE);