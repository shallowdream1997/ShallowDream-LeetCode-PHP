<?php
//require_once dirname(__FILE__) .'/../../vendor/autoload.php';

require_once dirname(__FILE__) .'/../requiredfile/requiredChorm.php';

class search{

    public $logger;

    public function __construct(){
        $this->logger = new MyLogger("option/searchLog");
    }

    public function pageSwitchConfig($params){
        $curlService = (new CurlService())->test();

        $env = $curlService->environment;

        $info = DataUtils::getPageListInFirstData($curlService->s3015()->get("option-val-lists/queryPage", [
            "optionName" => "page_switch_config",
            "limit" => 1
        ]));

        $paProductIds  = $info['optionVal']['pa_product_detail_submit_leader_review']['paProductIds'];

        $res = DataUtils::getPageList($curlService->s3015()->post("pa_products/queryPagePost", [
                "id_in" => implode(",",$paProductIds), "limit" => count($paProductIds), "page" => 1]
        ));
        $batchNameList = [];
        foreach ($res as $item){
            $batchNameList[] = [
                "_id" => $item['_id'],
                "batchName" => $item['batchName'],
            ];
        }
        $this->logger->log("查询批次号数量：".count($batchNameList)." 个");

        return [
            "env" => $env,
            "data" => $batchNameList
        ];

    }

    public function fixTranslationManagements($params){
        $curlService = (new CurlService())->test();
        $env = $curlService->environment;
        $list = [];
        if (isset($params['title']) && $params['title']){
            $list = DataUtils::getPageList($curlService->s3015()->get("translation_managements/queryPage",[
                "limit" => 100,
                "page" => 1,
                "title_in" => $params['title'],
            ]));
        }

        return [
            "env" => $env,
            "data" => $list
        ];
    }


    public function paProductBrand($params){

        $curlService = (new CurlService())->test();

        $env = $curlService->environment;

        $info = DataUtils::getPageListInFirstData($curlService->s3015()->get("option-val-lists/queryPage", [
            "optionName" => $params['optionName'],
            "limit" => 1
        ]));

        return [
            "env" => $env
        ];
    }


}


$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['action']) || empty($data['action'])){
    return json_encode([],JSON_UNESCAPED_UNICODE);
}

$class = new search();
$return = [];

switch ($data['action']){
    case "paProductBrandSearch":
        $params = isset($data['params']) ? $data['params'] : [];
        $return = $class->paProductBrand($params);
        break;
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