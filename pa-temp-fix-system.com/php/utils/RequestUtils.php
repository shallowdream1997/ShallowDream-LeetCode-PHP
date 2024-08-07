<?php
require_once("../requiredfile/requiredfile.php");
require_once("ProductUtils.php");


class RequestUtils
{
    /**
     * @var CurlService
     */
    private $curlService;

    public function __construct($port){
        $this->curlService = new CurlService();
        $this->curlService->$port();
    }
    // ===================================== pa_product 表的增删改查 基本接口 start =====================================

    public function getPaProductPageList($params): array
    {
        return DataUtils::getPageList($this->curlService->s3015()->get("pa_products/queryPage", $params));
    }

    public function readPaProductInfo(string $id): array
    {
        return DataUtils::getQueryList($this->curlService->s3015()->get("pa_products/{$id}"));
    }

    public function updatePaProductInfo($params): array
    {
        return DataUtils::getResultData($this->curlService->s3015()->put("pa_products/{$params['_id']}",$params));
    }

    public function deletePaProduct($id): array
    {
        return DataUtils::getResultData($this->curlService->s3015()->delete("pa_products/{$id}"));
    }

    /**
     * 根据批次号获取主表和明细表的数据
     * @param $batchName
     * @return array
     */
    public function getPaProductInfoByBatchName($batchName): array
    {
        $paProductIdInfo = DataUtils::getArrHeadData($this->getPaProductPageList(["batchName"=>$batchName]));
        $detailList = [];
        if (DataUtils::checkArrFilesIsExist($paProductIdInfo,'_id')){
            $detailList = $this->getPaProductDetailPageList(["paProductId"=>$paProductIdInfo['_id'],"limit" => 1000]);
        }
        return compact(['paProductIdInfo','detailList']);
    }

    /**
     * 根据批次号获取主表和明细表的数据(多个)
     * @param $batchNameList
     * @return array
     */
    public function getPaProductInfoByBatchNameList($batchNameList): array
    {
        //拿pa_product主表
        $paProductIdInfoList = [];
        foreach (array_chunk($batchNameList, 150) as $chunk) {
            $paProductIdInfoList = array_merge($paProductIdInfoList, $this->getPaProductPageList(["batchName_in" => implode(",", $chunk), "limit" => 150]));
        }
        $detailIdList = array_unique(array_column($paProductIdInfoList,'_id'));

        //拿pa_product_detail明细表
        $paProductDetailList = [];
        foreach (array_chunk($detailIdList,150) as $dChunk){
            $page = 1;
            do {
                $detailList = $this->getPaProductDetailPageList(["paProductId_in" => implode(",",$dChunk), "limit" => 2000, "page" => $page]);
                if (count($detailList) == 0) {
                    break;
                }
                $paProductDetailList = array_merge($paProductDetailList, $detailList);
                $page++;
            } while (true);
        }

        //主表Id 分组
        $grouped = [];
        foreach ($paProductDetailList as $detail) {
            $groupKey = $detail['paProductId'];
            if (!isset($grouped[$groupKey])) {
                $grouped[$groupKey] = [];
            }
            $grouped[$groupKey][] = $detail;
        }
        //组合数据，返回主表和明细数据集合
        $paProductIdCollectorList = [];
        foreach ($paProductIdInfoList as $batchNameInfo) {
            $detailId = $batchNameInfo['_id'];
            $paProductIdCollector = [];
            $paProductIdCollector['paProductInfo'] = $batchNameInfo;
            $paProductIdCollector['paProductDetailList'] = [];
            if (DataUtils::checkArrFilesIsExist($grouped,$detailId)){
                $paProductIdCollector['paProductDetailList'] = $grouped[$detailId];
            }
            $paProductIdCollectorList[$batchNameInfo['batchName']] = $paProductIdCollector;
        }

        return $paProductIdCollectorList;
    }

    // ===================================== pa_product 表的增删改查 基本接口 end =====================================

    // ===================================== pa_product_detail 表的增删改查 基本接口 start =====================================
    public function getPaProductDetailPageList($params): array
    {
        return DataUtils::getPageList($this->curlService->s3015()->get("pa_product_details/queryPage", $params));
    }
    public function readPaProductDetailInfo(string $id): array
    {
        return DataUtils::getQueryList($this->curlService->s3015()->get("pa_product_details/{$id}"));
    }

    public function updatePaProductDetailInfo($params): array
    {
        return DataUtils::getResultData($this->curlService->s3015()->put("pa_product_details/{$params['_id']}",$params));
    }

    public function deletePaProductDetail($id): array
    {
        return DataUtils::getResultData($this->curlService->s3015()->delete("pa_product_details/{$id}"));
    }

    public function updateBrandByPaProduct($paProductDetailList, $salesBrand, $reason): array
    {
        return DataUtils::getResultData($res = $this->curlService->s3044()->post("pa_product_brand_score_bases/updateBrandByPaProduct", [
            "paProductDetailList" => $paProductDetailList,
            "newSalesBrand" => $salesBrand,
            "updateSalesBrandReason" => $reason,
            "userName" => "system"
        ]));
    }
    // ===================================== pa_product_detail 表的增删改查 基本接口 end =====================================

    // ===================================== option_val_list 表的 基本接口 start =====================================
    public function getOptionValListByName($optionName): array
    {
        if (empty($optionName)){
            return [];
        }
        return DataUtils::getPageListInFirstData($this->curlService->s3015()->get("option-val-lists/queryPage", [
            "optionName" => $optionName,
            "limit" => 1
        ]));
    }


    public function updateOptionValListInfo($params): array
    {
        return DataUtils::getResultData($this->curlService->s3015()->put("option-val-lists/{$params['_id']}",$params));
    }
    // ===================================== option_val_list 表的 基本接口 end =====================================



    // ===================================== system-manages 接口 start =====================================

    /**
     *
     * @param $userName
     * @return array
     */
    public function getUserSheetByUserName($userName): array
    {
        $redisService = new RedisService();
        $get = $redisService->hGet(REDIS_USER_NAME_KEY, $userName);
        $userInfo = [];
        if (empty($get)) {
            //缓存不存在就读表并写入redis
            $resp = DataUtils::getArrHeadData(DataUtils::getResultData($this->curlService->s3009()->get("system-manages/getUserSheetByUserName", ["userName" => $userName, "status" => "启用"])));
            if (!empty($resp)){
                if ($resp['status'] == '启用'){
                    $userInfo = [
                        "userName" => $resp['userName'],
                        "cName" => $resp['cName'],
                        "verticalName" => $resp['verticalName'],
                        "companySequenceId" => $resp['companySequenceId'],
                        "verticalSequenceId" => $resp['verticalSequenceId'],
                        "dingUserId" => $resp['dingUserId'],
                    ];
                    $redisService->hSet(REDIS_USER_NAME_KEY, $userName, json_encode($userInfo, JSON_UNESCAPED_UNICODE), 60 * 60 * 12);
                }
            }

        } else {
            $userInfo = json_decode($get, true);
        }
        return $userInfo;
    }

    /**
     * 根据垂直Id 获取 所有的用户信息，并且初始化写入用户redis缓存
     * @param string $companySequenceId 垂直Id
     * @return CurlService|RequestUtils
     */
    public function getEmployeeByCompanySequenceId(string $companySequenceId)
    {
        $resp = DataUtils::getResultData($this->curlService->s3009()->get("system-manages/getEmployee",["companySequenceId" => $companySequenceId]));
        $redisService = new RedisService();
        foreach ($resp as $info){
            if ($info['status'] == '启用'){
                $userInfo = [
                    "userName" => $info['userName'],
                    "cName" => $info['cName'],
                    "verticalName" => $info['verticalName'],
                    "companySequenceId" => $info['companySequenceId'],
                    "verticalSequenceId" => $info['verticalSequenceId'],
                    "dingUserId" => $info['dingUserId'],
                ];
                $redisService->hSet(REDIS_USER_NAME_KEY, $info['userName'], json_encode($userInfo, JSON_UNESCAPED_UNICODE), 60 * 60 * 12);
            }
        }
        return $this;
    }

    // ===================================== system-manages 接口 end =====================================


    public function getProductSkuList(array $skuIdList): array
    {
        $skuIdListInfoArray = [];
        if (!is_array($skuIdList)){
            return [];
        }
        foreach (array_chunk($skuIdList, 150) as $chunk) {
            $skuIdListInfoArray = array_merge($skuIdListInfoArray, DataUtils::getPageList($this->curlService->s3015()->get("product-skus/queryPage", [
                "productId" => implode(",", $chunk),
                "limit" => count($chunk)
            ])));
        }
        return $skuIdListInfoArray;
    }

    public function updateProductSku($info): array
    {
        return DataUtils::getResultData($this->curlService->s3015()->put("product-skus/{$info['_id']}",$info));
    }

    public function deleteProductSku($id): array
    {
        return DataUtils::getResultData($this->curlService->s3015()->delete("product-skus/{$id}"));
    }

    public function createProductSku($info): string
    {
        return DataUtils::getCreateReturnId($this->curlService->s3015()->post("product-skus",$info));
    }
    //

    public function getProductBaseInfoList(array $skuIdList): array
    {
        $skuIdListInfoArray = [];
        if (!is_array($skuIdList)){
            return [];
        }
        foreach (array_chunk($skuIdList, 150) as $chunk) {
            $skuIdListInfoArray = array_merge($skuIdListInfoArray, DataUtils::getPageList($this->curlService->s3015()->get("product_base_infos/queryPage", [
                "productId_in" => implode(",", $chunk),
                "limit" => count($chunk)
            ])));
        }
        return $skuIdListInfoArray;
    }
    public function updateProductBaseInfo($info): array
    {
        return DataUtils::getResultData($this->curlService->s3015()->put("product_base_infos/{$info['_id']}",$info));
    }

    public function deleteProductBaseInfo($id): array
    {
        return DataUtils::getResultData($this->curlService->s3015()->delete("product_base_infos/{$id}"));
    }

    public function createProductBaseInfo($info): string
    {
        return DataUtils::getCreateReturnId($this->curlService->s3015()->post("product_base_infos",$info));
    }
    ///

    /**
     * version2 末级分类Id获取 一级分类，分类全路径id，分类全路径
     * @param int $categoryId 末级分类Id
     * @return array
     */
    public function getCategoryIdInfoV1(int $categoryId): array
    {
        return DataUtils::getArrHeadData(DataUtils::getResultData($this->curlService->s3009()->get("development-directions/getAllCategory",["id"=>$categoryId])));
    }

    /**
     * version2 末级分类Id获取 一级分类，分类全路径id，分类全路径
     * @param int $categoryId 末级分类Id
     * @return array
     */
    public function getCategoryIdInfoV2(int $categoryId): array
    {
        $redisService = new RedisService();
        $get = $redisService->hGet(REDIS_CATEGORY_ID_KEY, $categoryId);
        $categoryIdInfo = [];
        if (empty($get)) {
            $categoryInfo = DataUtils::getArrHeadData(DataUtils::getResultData($this->curlService->s3009()->get("development-directions/getSupplierCategoryPathByCategoryIdList", ["id" => $categoryId])));
            $cnCategoryNameFirst = "";
            $categoryIds = "";
            $cnCategoryFullPath = "";
            if (DataUtils::checkArrFilesIsExist($categoryInfo, 'supplierCategories')) {
                usort($categoryInfo['supplierCategories'], function ($a, $b) {
                    return $a['categoryLevel'] - $b['categoryLevel'];
                });
                $cnCategoryNameFirst = $categoryInfo['supplierCategories'][0]['categoryNameCn'];
                $categoryIds = implode(",", array_column($categoryInfo['supplierCategories'], 'id'));
                $cnCategoryFullPath = implode(" -> ", array_column($categoryInfo['supplierCategories'], 'categoryNameCn'));
            }
            $categoryIdInfo = compact(['categoryId','cnCategoryNameFirst', 'categoryIds', 'cnCategoryFullPath']);
            $redisService->hSet(REDIS_CATEGORY_ID_KEY, $categoryId, json_encode($categoryIdInfo, JSON_UNESCAPED_UNICODE), 60 * 60 * 1);
        }else{
            $categoryIdInfo = json_decode($get, true);
        }

        return $categoryIdInfo;
    }


    //

    public function getPaCeMaterialByCeBillNoList($ceBillNoList): array
    {
        $list = [];
        foreach (array_chunk($ceBillNoList, 100) as $chunk) {
            $list = array_merge($list, DataUtils::getPageDocList($this->curlService->s3044()->get("pa_ce_materials/queryPage", ["ceBillNo_in" => implode(",", $chunk), "limit" => count($chunk)])));
        }
        return $list;
    }

    public function updatePaCeMaterial($id, $params): array
    {
        return DataUtils::getResultData($this->curlService->s3044()->put("pa_ce_materials/{$id}", $params));
    }

    public function getTranslationMain($params): array
    {
        return DataUtils::getPageList($this->curlService->s3015()->get("translation_managements/queryPage",$params));
    }

    public function updateTranslationMain($info): array
    {
        return DataUtils::getResultData($this->curlService->s3015()->put("translation_managements/{$info['_id']}",$info));
    }

    public function getTranslationSku($params): array
    {
        return DataUtils::getPageList($this->curlService->s3015()->get("translation_management_skus/queryPage",$params));
    }

    public function updateTranslationSku($info): array
    {
        return DataUtils::getResultData($this->curlService->s3015()->put("translation_management_skus/{$info['_id']}",$info));
    }

    public function getAllPaProductBrandBillNoRules(): array
    {
        return DataUtils::getPageList($this->curlService->s3044()->get("pa_product_brand_bilino_rules/queryPage",["limit"=>10000,"status"=>10]));
    }

    public function updatePaProductBrandBillNoRules($info): array
    {
        return DataUtils::getResultData($this->curlService->s3044()->get("pa_product_brand_bilino_rules/{$info['_id']}",$info));
    }

    public function getAllPaProductBrandScoreBase(): array
    {
        return DataUtils::getPageList($this->curlService->s3044()->get("pa_product_brand_score_bases/queryPage",["limit"=>10000,"status"=>10]));
    }

    public function updatePaProductBrandScoreBase($info): array
    {
        return DataUtils::getResultData($this->curlService->s3044()->get("pa_product_brand_score_bases/{$info['_id']}",$info));
    }

    public function getPaSkuAttributePageList($params): array
    {
        return DataUtils::getPageList($this->curlService->s3015()->get("pa_sku_attributes/queryPage", $params));
    }
    public function updatePaSkuAttribute($info): array
    {
        return DataUtils::getResultData($this->curlService->s3015()->put("pa_sku_attributes/{$info['_id']}",$info));
    }

    public function deletePaSkuAttribute($id): array
    {
        return DataUtils::getResultData($this->curlService->s3015()->delete("pa_sku_attributes/{$id}"));
    }

    public function export()
    {

    }

}

$request = new RequestUtils("test");


//if (DataUtils::checkArrFilesIsExist($data, "attribute")) {
//    $info = [
//        [
//            "channel" => "local",
//            "label" => "来货brand类型",
//            "value" => "uxcell",
//        ],
//        [
//            "channel" => "local",
//            "label" => "Business Type",
//            "value" => "产品目录",
//        ],
//    ];
//    ProductUtils::editProductAttributeByArr($data['attribute'], $info);
//
//
//}



