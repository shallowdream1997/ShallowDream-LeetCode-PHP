<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Config\EnvironmentConfig;
use App\Constants\RedisKeys;
use App\Services\ProductSkuController;
use App\Infrastructure\Cache\RedisService;
use App\Infrastructure\Http\CurlService;
use App\Logging\MyLogger;
use App\Utils\DataUtils;
use App\Utils\ExcelUtils;

class SearchController
{

    private MyLogger $logger;

    private string $module = "pa-biz-application";

    public CurlService $envService;

    public function __construct()
    {
        $this->logger = new MyLogger("option/searchLog");
    }

    public function handle(array $payload): array
    {
        $action = $payload['action'] ?? null;
        if (!$action) {
            return [
                'success' => false,
                'message' => '缺少 action 参数'
            ];
        }

        $this->envService = (new EnvironmentConfig($action))->getCurlService();
        $params = $payload['params'] ?? [];

        return $this->dispatch($action, $params);
    }

    private function dispatch(string $action, array $params): array
    {
        switch ($action) {
            case "paProductBrandSearch":
                return $this->paProductBrand($params);
            case "pageSwitchConfig":
                return $this->pageSwitchConfig($params);
            case "fixTranslationManagements":
                return $this->fixTranslationManagements($params);
            case "fixCeMaterials":
                return $this->fixCeMaterials($params);
            case "paFbaChannelSellerConfig":
                return $this->paFbaChannelSellerConfig($params);
            case "paSampleSku":
                return $this->paSampleSku($params);
            case "paProductList":
                return $this->paProductList($params);
            case "paFixProductLine":
                return $this->paFixProductLine($params);
            case "addBrandFor":
                return $this->addBrandFor($params);
            case "uploadOss":
                return $this->uploadOss($params);
            case "getPmoData":
                return $this->getPmoData($params);
            case "registerIp":
                return $this->registerIp($params);
            case "fixFcuProductLine":
                return $this->fixFcuProductLine($params);
            case "configPage":
                return $this->configPage($params);
            case "consignmentQD":
                return $this->consignmentQD($params);
            case "skuPhotoFix":
                return $this->skuPhotoFix($params);
            default:
                return [
                    'success' => false,
                    'message' => "未支持的 action：{$action}"
                ];
        }
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
            $curlService->getModule('wms');
            $resp = DataUtils::getNewResultData($curlService->getWayPost($curlService->module . "/receive/sample/expect/v1/page", [
                "skuIdIn" => $skuIdList,
                "vertical" => "PA",
//                "category" => "dataTeam",
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


    public function paFixProductLine($params){
        $curlService = $this->envService;
        $env = $curlService->environment;
        $list = [];
        if (isset($params['skuIdList']) && $params['skuIdList']) {
            $skuIdList = $params['skuIdList'];
            foreach (array_chunk($skuIdList,200) as $chunk){
                $createProductMainResp = DataUtils::getQueryList($curlService->s3009()->get("product-operation-lines/queryUserBySkuId", [
                    "skuId" => implode(",",$chunk)
                ]));
                if ($createProductMainResp){
                    $list = array_merge($list,$createProductMainResp);
                }
            }
        }

        return [
            "env" => $env,
            "data" => $list
        ];
    }

    public function addBrandFor($params){
        $curlService = $this->envService;
        $env = $curlService->environment;

        $info = DataUtils::getPageListInFirstData($curlService->s3015()->get("option-val-lists/queryPage", [
            "optionName" => "pa_amazon_attribute_forbidden",
            "limit" => 1
        ]));
        $list = array_keys($info['optionVal']);

        return [
            "env" => $env,
            "data" => [
                "canSearchChannelList" => $list
            ]
        ];
    }

    public function uploadOss($params){
        $curlService = $this->envService;
        $env = $curlService->environment;
        $dateTime = date("Y-m-d H:i:s",time());
        if (isset($params['searchData']) && $params['searchData']){
            $redisService = new RedisService();
            $dbData = $redisService->hGetAll(RedisKeys::OSS_FILE . "_{$env}");
            $dbDataList = [];
            if ($dbData){
                foreach ($dbData as $key => $keyInfo){
                    $dbDataList[] = json_decode($keyInfo,true);
                }
            }

            if (isset($params['isExport']) && $params['isExport']){
                $excelUtils = new ExcelUtils();
                $downloadOssLink = "上传oss文件_" . date("YmdHis") . ".xlsx";
                $downloadOssPath = $excelUtils->downloadXlsx(["文件原名", "Oss Key名", "OSS链接","上传日期"],$dbDataList,$downloadOssLink);

            }
           // /export/uploads/default/上传oss文件_20241226082547.xlsx
            return [
                "env" => $env,
                "uploadSuccess" => true,
                "messages" => "扫描成功",
                "linkList" => $dbDataList,
                "downloadOssPath" => $downloadOssPath,
                "downloadOssPathUrl" => "/export/uploads/default/" . $downloadOssLink,
            ];
        }

        return [
            "env" => $env,
            "data" => []
        ];
    }

    /**
     * 查询采购清单对应开发
     * @param $params
     * @return array
     */
    public function getPmoData($params)
    {
        $curlService = $this->envService;
        $env = $curlService->environment;
        if (isset($params['batchList']) && $params['batchList']) {

            $productSkuController = new ProductSkuController();
            $downloadOssLink = "PMO开发人员_" . date("YmdHis") . ".xlsx";
            $link = $productSkuController->getPmoData($downloadOssLink,$params['batchList'], $env);

            if ($link){
                return [
                    "env" => $env,
                    "messages" => "下载成功",
                    "data" => [
                        "downLink" => "/export/uploads/default/" . $downloadOssLink
                    ]
                ];
            }

        }

        return [
            "env" => $env,
            "data" => []
        ];
    }

    /**
     * 登记个人IP
     * @param $params
     * @return array
     */
    public function registerIp($params)
    {
        $curlService = $this->envService;
        $env = $curlService->environment;

        $redisService = new RedisService();

        $ipMap = [];
        $dbDataList = $redisService->hGetAll(RedisKeys::USERNAME_IP . "_{$env}");
        if (count($dbDataList) > 0){
            foreach ($dbDataList as $key => $keyInfo){
                $old = json_decode($keyInfo,true);
                $ipMap[$old['ip']] = $ipMap['name'] ?: "";
            }
        }

        $ip = $_SERVER['REMOTE_ADDR'];
        if (!isset($ipMap[$ip])){
            $dbData = [
                "name" => "新用户",
                "ip" => $ip
            ];
            $redisService->hSet(RedisKeys::USERNAME_IP . "_{$env}", $ip,json_encode($dbData,JSON_UNESCAPED_UNICODE));
        }

        $list = [];
        $dbDataList = $redisService->hGetAll(RedisKeys::USERNAME_IP . "_{$env}");
        if (count($dbDataList) > 0){
            foreach ($dbDataList as $key => $keyInfo){
                $list[] = json_decode($keyInfo,true);
            }
        }

        return [
            "env" => $env,
            "data" => $list
        ];
    }

    /**
     * 查询fcu产品线
     * @param $params
     * @return array
     */
    public function fixFcuProductLine($params){
        $curlService = $this->envService;
        $env = $curlService->environment;

        $returnMsg = [];
        if (isset($params['fcuIdList']) && $params['fcuIdList']){
            $fculist = [];
            $skulist = [];
            foreach (array_chunk($params['fcuIdList'],200) as $chunk){
                $fcuResult = DataUtils::getPageDocList($curlService->s3044()->get("fcu_sku_maps/queryPage", [
                    "fcuId_in" => implode(",",$chunk),
                    "limit" => 200
                ]));
                if ($fcuResult && count($fcuResult) > 0){
                    foreach ($fcuResult as $info){
                        $firstSkuId = current($info['skuId']);
                        $skulist[] = $firstSkuId;
                        $fculist[$info['fcuId']] = $info;
                    }
                }
            }

            foreach ($params['fcuIdList'] as $fcuId){
                if (isset($fculist[$fcuId])){
                    $fcuInfo = $fculist[$fcuId];
                    $skuId = current($fcuInfo['skuId']);
                    if ($fcuInfo['productLineId']){
                        $returnMsg[] = [
                            "messages" => "已存在，无需补充",
                            "fcuId" => $fcuId,
                            "skuId" => $skuId,
                            "productLine" => $fcuInfo['productLineId'],
                        ];
                        continue;
                    }
                    $returnMsg[] = [
                        "messages" => "找不到产品线",
                        "fcuId" => $fcuId,
                        "skuId" => $skuId,
                        "productLine" => "",
                    ];

                }else{
                    $this->logger->log2("找不到fcu：{$fcuId}");
                    $returnMsg[] = [
                        "messages" => "找不到fcu",
                        "fcuId" => $fcuId,
                        "skuId" => "",
                        "productLine" => "",
                    ];
                }
            }
        }

        return ["env" => $env, "data" => $returnMsg];
    }


    public function configPage($params){
        $curlService = $this->envService;
        $env = $curlService->environment;
        $curlService->gateway();

        $curlService->getModule('config');
        $params = [
            "condition" => [
                "configKey" => $params['configKey'] ?: "",
                "configValue" => $params['configValue'] ?: "",
                "businessCategoryList" => $params['businessCategoryList'] ?: [],
                "systemNameList" => $params['systemNameList'] ?: []
            ],
            "page" => [
                "pageSize" => 100,
                "pageNum" => 1
            ]
        ];
        $resp = DataUtils::getNewResultData($curlService->getWayPost( $curlService->module . "/business/config/v1/query", $params));
        return ["env" => $env, "data" => $resp['list']];

    }

    /**
     * 寄卖新QD单
     * @param $params
     * @return array
     */
    public function consignmentQD($params){
        $curlService = $this->envService;
        $env = $curlService->environment;



        return ["env" => $env, "data" => []];
    }

    /**
     * sku修补拍摄工单
     * @param $params
     * @return array
     */
    public function skuPhotoFix($params){
        $curlService = $this->envService;
        $env = $curlService->environment;

        $preList = [];
        if (isset($params['skuList']) && !empty($params['skuList'])){
            $preList = (new ProductSkuController())->getSkuPhotoProgress($params['skuList'],$env);
        }
        return ["env" => $env, "data" => $preList];
    }
}