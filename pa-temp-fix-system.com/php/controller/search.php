<?php
require dirname(__FILE__) . '/../../vendor/autoload.php';

require_once dirname(__FILE__) . '/../requiredfile/requiredChorm.php';
require_once dirname(__FILE__) . '/EnvironmentConfig.php';

/**
 * 查询接口
 * Class search
 */
class search
{

    public $logger;

    private $module = "pa-biz-application";

    /**
     * @var CurlService
     */
    public $envService;

    public function __construct()
    {
        $this->logger = new MyLogger("option/searchLog");
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
     * @return array
     */
    public function pageSwitchConfig($params)
    {
        $curlService = $this->envService;

        $env = $curlService->environment;

        $info = DataUtils::getPageListInFirstData($curlService->s3015()->get("option-val-lists/queryPage", [
            "optionName" => "page_switch_config",
            "limit" => 1
        ]));

        $paProductIds = $info['optionVal']['pa_product_detail_submit_leader_review']['paProductIds'];

        $res = DataUtils::getPageList($curlService->s3015()->post("pa_products/queryPagePost", [
                "id_in" => implode(",", $paProductIds), "limit" => count($paProductIds), "page" => 1]
        ));
        $batchNameList = [];
        foreach ($res as $item) {
            $batchNameList[] = [
                "_id" => $item['_id'],
                "batchName" => $item['batchName'],
            ];
        }
        $this->logger->log("查询批次号数量：" . count($batchNameList) . " 个");

        return [
            "env" => $env,
            "data" => $batchNameList
        ];

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
        $list = [];
        if (isset($params['title']) && $params['title']) {
            $list = DataUtils::getPageList($curlService->s3015()->get("translation_managements/queryPage", [
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

    /**
     * 品牌
     * @param $params
     * @return array
     */
    public function paProductBrand($params)
    {

        $curlService = $this->envService;

        $env = $curlService->environment;

        $info = DataUtils::getPageListInFirstData($curlService->s3015()->get("option-val-lists/queryPage", [
            "optionName" => $params['optionName'],
            "limit" => 1
        ]));

        return [
            "env" => $env
        ];
    }

    /**
     * CE资料呈现
     * @param $params
     * @return array
     */
    public function fixCeMaterials($params)
    {
        $curlService = $this->envService;
        $env = $curlService->environment;
        $list = [];
        if (isset($params['title']) && $params['title']) {

            $res = $curlService->s3044()->get("pa_ce_materials/queryPage", [
                "limit" => 100,
                "page" => 1,
                "ceBillNo_in" => $params['title'],
            ]);
            $list = DataUtils::getPageDocList($res);

        }

        return [
            "env" => $env,
            "data" => $list
        ];
    }

    /**
     * 上架前海外仓移库申请
     * @param $params
     * @return array
     */
    public function paFbaChannelSellerConfig($params)
    {
        $curlService = $this->envService;
        $env = $curlService->environment;

        $info = DataUtils::getPageListInFirstData($curlService->s3015()->get("option-val-lists/queryPage", [
            "optionName" => "pa_fba_channel_seller_config",
            "limit" => 1
        ]));
        $list = [];
        foreach ($info['optionVal']['amazon'] as $channel => $stocks){
            $list[] = [
                "channel" => $channel,
                "nowStocks" => $stocks
            ];
        }

        return [
            "env" => $env,
            "data" => $list
        ];
    }

    public function paSampleSku($params){
        $curlService = $this->envService;
        $env = $curlService->environment;
        $list = [];
        if (isset($params['skuIdList']) && $params['skuIdList']) {
            $skuIdList = $params['skuIdList'];

            $curlService->gateway();
            $this->getModule('wms');
            $resp = DataUtils::getNewResultData($curlService->getWayPost($this->module . "/receive/sample/expect/v1/page", [
                "skuIdIn" => $skuIdList,
                "vertical" => "PA",
                "category" => "dataTeam",
                "pageSize" => 500,
                "pageNum" => 1,
            ]));
            $list = $resp['list'];
        }
        return [
            "env" => $env,
            "data" => $list
        ];

    }

    public function paProductList($params){
        $curlService = $this->envService;
        $env = $curlService->environment;
        $list = [];
        if (isset($params['skuIdList']) && $params['skuIdList']) {

        }
        return [
            "env" => $env,
            "data" => $list
        ];
    }

}


$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['action']) || empty($data['action'])) {
    return json_encode([], JSON_UNESCAPED_UNICODE);
}

$class = new search();
$return = [];
$class->envService = (new EnvironmentConfig($data['action']))->getCurlService();

switch ($data['action']) {
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
}

echo json_encode($return, JSON_UNESCAPED_UNICODE);