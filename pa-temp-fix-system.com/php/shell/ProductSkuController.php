<?php
require_once(dirname(__FILE__) ."/../../php/requiredfile/requiredfile.php");

class ProductSkuController
{
    private $log;
    private $requestUtils;

    public function __construct()
    {
        $this->log = new MyLogger("product_sku");
        $this->requestUtils = new RequestUtils("test");
    }

    /**
     * 日志记录
     * @param string $message 日志内容
     */
    private function log($message = "")
    {
        $this->log->log2($message);
    }
    public function getXlsxByFile($file = ""): array
    {
        $excelUtils = new ExcelUtils();
        $fileName = "../export/";
        switch ($file) {
            case "UpdatePaProduct.xlsx":
            case "UpdateProductSku.xlsx":
                break;
            default:
                die("请选择文件!");
        }

        try {
            return $excelUtils->getXlsxData($fileName.$file);
        } catch (Exception $e) {
            die("获取数据失败");
        }
    }
    /**
     * 批量修改product-sku表关键字段：品牌，业务类型，分类，销售，开发
     */
    public function updateProductSku()
    {
        $list = $this->getXlsxByFile("UpdateProductSku.xlsx");
//        $list = [
//            ["productid" => "a24062100ux0174", "salesbrand" => "Motoforti", "businesstype" => "产品目录", "categoryid" => 26468, "salesusername" => "huangnaxuan", "developerusername" => "linzejian"],//Motoforti
//        ];
        $updateSkuIdsList = array_column($list, "productid");
        $updateSkuIdInfoList = $this->requestUtils->getProductSkuList($updateSkuIdsList);

        $updateSkuIdInfoGroupByProductId = array_column($updateSkuIdInfoList, null, "productId");
        if ($updateSkuIdInfoGroupByProductId) {
            foreach ($list as $excelInfo) {
                if (!DataUtils::checkArrFilesIsExist($updateSkuIdInfoGroupByProductId, $excelInfo['productid'])) {
                    $this->log("{$excelInfo['productid']} 不存在POMS");
                    continue;
                }
                $this->log("{$excelInfo['productid']} 开始修改");
                $productInfo = $updateSkuIdInfoGroupByProductId[$excelInfo['productid']];
                if (DataUtils::checkArrFilesIsExist($excelInfo, 'salesusername')) {
                    $this->log("salesUserName: {$productInfo['salesUserName']} -> {$excelInfo['salesusername']}");
                    $productInfo['salesUserName'] = $excelInfo['salesusername'];
                }
                if (DataUtils::checkArrFilesIsExist($excelInfo, 'developerusername')) {
                    $this->log("developerUserName: {$productInfo['developerUserName']} -> {$excelInfo['developerusername']}");
                    $productInfo['developerUserName'] = $excelInfo['developerusername'];
                }
                //category
                if (DataUtils::checkArrFilesIsExist($excelInfo, 'categoryid')) {
                    //获取分类的信息
                    $categoryIdInfo = $this->requestUtils->getCategoryIdInfoV2($excelInfo['categoryid']);
                    $this->log("category: {$productInfo['category']} -> {$categoryIdInfo['categoryId']}");
                    $this->log("categoryPaths: {$productInfo['categoryPaths']} -> {$categoryIdInfo['categoryIds']}");
                    $this->log("cn_Category: {$productInfo['cn_Category']} -> {$categoryIdInfo['cnCategoryFullPath']}");

                    $productInfo['category'] = $categoryIdInfo['categoryId'];
                    $productInfo['categoryPaths'] = $categoryIdInfo['categoryIds'];
                    $productInfo['cn_Category'] = $categoryIdInfo['cnCategoryFullPath'];
                }

                $updateAttribute = [];
                //有品牌的修改
                if (DataUtils::checkArrFilesIsExist($excelInfo, 'salesbrand')) {
                    $updateAttribute[] = [
                        "channel" => "local",
                        "label" => "salesBrand",
                        "value" => $excelInfo['salesbrand'],
                    ];
                    $this->log("local salesBrand: {$excelInfo['salesbrand']}");
                }
                if (DataUtils::checkArrFilesIsExist($excelInfo, 'businesstype')) {
                    $updateAttribute[] = [
                        "channel" => "local",
                        "label" => "Business Type",
                        "value" => $excelInfo['businesstype'],
                    ];
                    $this->log("local Business Type: {$excelInfo['businesstype']}");
                }
                if (!empty($updateAttribute)) {
                    //要修改的字段里面有业务类型，品牌，
                    if (DataUtils::checkArrFilesIsExist($productInfo, "attribute")) {
                        ProductUtils::editProductAttributeByArr($productInfo['attribute'], $updateAttribute);
                    }
                }
                $updateProductResp = $this->requestUtils->updateProductSku($productInfo);
                if ($updateProductResp) {
                    $this->log("success");
                } else {
                    $this->log("fail");
                }
            }
        }

    }

    /**
     * 修改开发清单和明细表
     * @param false $score
     * @param string $updateBrandRemark
     */
    public function updatePaProductAndDetail($score = false,$updateBrandRemark = "指定修改品牌")
    {
        $list = $this->getXlsxByFile("UpdatePaProduct.xlsx");
        $batchNameList = array_column($list, 'batchName');
        $chunkBatchNameList = [$batchNameList];
        if (count($batchNameList) > 500) {
            $this->log("批次号太多了，要爆炸啦，分批次处理");
            $chunkBatchNameList = array_chunk($batchNameList, 150);
        }


        foreach ($chunkBatchNameList as $chunk) {
            $paProductIdCollectorList = $this->requestUtils->getPaProductInfoByBatchNameList($chunk);
            if (count($paProductIdCollectorList) > 0) {
                foreach ($list as $updateData) {
                    if (!DataUtils::checkArrFilesIsExist($paProductIdCollectorList, $updateData['batchName'])) {
                        $this->log("{$updateData['batchName']} 不存在清单列表");
                        continue;
                    }
                    $batchInfo = $paProductIdCollectorList[$updateData['batchName']];
                    $this->updatePPMain($batchInfo['paProductInfo'], $updateData,$score);
                    if ($score){
                        if (DataUtils::checkArrFilesIsExist($updateData, 'salesBrand')) {
                            $scoreDetailList = [];
                            foreach ($batchInfo['paProductDetailList'] as $detailInfo){
                                if (DataUtils::checkArrFilesIsExist($detailInfo,'skuId') &&
                                    !DataUtils::checkArrFilesIsExistEqualValue($detailInfo,'status','delete')){
                                    $scoreDetailList[] = $detailInfo;
                                }
                            }
                            if (count($scoreDetailList) > 0){
                                $this->log("{$updateData['salesBrand']}: 扣分加分");
                                //修改品牌要扣分的
                                $resp = $this->requestUtils->updateBrandByPaProduct($scoreDetailList, $updateData['salesBrand'], $updateBrandRemark);
                                $this->log(json_encode($resp,JSON_UNESCAPED_UNICODE));
                            }else{
                                $this->log("{$updateData['salesBrand']}: 没有要扣分加分更新品牌的数据");
                            }

                        }

                    }else{
                        $this->updatePPDetail($batchInfo['paProductDetailList'], $updateData);
                    }

                }
            }
        }

        $this->log("结束");
    }

    private function updatePPMain($paProductInfo, $updateData,$score)
    {
        $this->log("{$updateData['batchName']} 开始更新");
        foreach (['developer', 'platform', 'productlineId', 'salesBrand', 'tag', 'tag2', 'traceMan','ebayTraceMan'] as $field) {
            if (DataUtils::checkArrFilesIsExist($updateData, $field)) {
                if ($score && $field == 'salesBrand'){
                    //品牌扣分的话，不能更新品牌
                    continue;
                }
                $this->log("{$field}: {$paProductInfo[$field]} -> {$updateData[$field]}");
                $paProductInfo[$field] = $updateData[$field];
                if ($field === 'traceMan'){
                    //如果是traceMan的更新，还需要更新amazonTraceMan
                    $paProductInfo['amazonTraceMan'] = $updateData[$field];
                }
            }
        }
        $updateResp = $this->requestUtils->updatePaProductInfo($paProductInfo);
        if ($updateResp) {
            $this->log("success");
        } else {
            $this->log("fail");
        }
    }
    private function updatePPDetail($paProductDetailList,$updateData){
        $this->log("明细 开始更新");
        foreach ($paProductDetailList as $detailInfo) {
            $this->log("{$detailInfo['productName']}");

            if (DataUtils::checkArrFilesIsExist($updateData, 'salesBrand')) {
                $this->log("salesBrand: {$detailInfo['salesBrand']} -> {$updateData['salesBrand']}");
                $detailInfo['salesBrand'] = $updateData['salesBrand'];
            }

            $updateResp = $this->requestUtils->updatePaProductDetailInfo($detailInfo);
            if ($updateResp) {
                $this->log("success");
            } else {
                $this->log("fail");
            }
        }

    }

    /**
     * 同步生产环境sku -> sit环境
     */
    public function syncProSkuSPInfoToTest(){
        $skuIdList = [
            "a24051600ux0001"
        ];

        $proRequestUtils = new RequestUtils("pro");

        $skuIdListInfoArrayPro = $proRequestUtils->getProductSkuList($skuIdList);

        $skuIdListInfoArrayTest = $this->requestUtils->getProductSkuList($skuIdList);
        $testProductSkuInfoMap = array_column($skuIdListInfoArrayTest,null,'productId');
        foreach ($skuIdListInfoArrayPro as $info) {
            if (DataUtils::checkArrFilesIsExist($testProductSkuInfoMap, $info['productId'])) {
                $deleteResp = $this->requestUtils->deleteProductSku($testProductSkuInfoMap[$info['productId']]['_id']);
                if ($deleteResp){
                    $this->log("delete test product-skus {$info['productId']}");
                }
            }
            $this->requestUtils->createProductSku($info);
            $this->log("create test product-skus {$info['productId']}");
        }

        $proCurlService = new CurlService();
//        $proCurlService->pro()->s3015()->get("product_base_infos/queryPage",["productId_in"=>implode(",",$skuIdList)]);

        $productBaseInfoArrayPro = $proRequestUtils->getProductBaseInfoList($skuIdList);
        $productBaseInfoArrayTest = $this->requestUtils->getProductBaseInfoList($skuIdList);

        $productBaseInfoMapTest = array_column($productBaseInfoArrayTest, null, 'productId');
        foreach ($productBaseInfoArrayPro as $info) {
            if (DataUtils::checkArrFilesIsExist($productBaseInfoMapTest, $info['productId'])) {
                $deleteResp = $this->requestUtils->deleteProductBaseInfo($productBaseInfoMapTest[$info['productId']]['_id']);
                if ($deleteResp) {
                    $this->log("delete test product_base_info {$info['productId']}");
                }
            }
            $this->requestUtils->createProductBaseInfo($info);
            $this->log("create test product_base_info {$info['productId']}");
        }

//        $proCurlService->pro()->s3044()->get("amazon_sp_sellers/queryPage",[
//            "company_in" => "CR201706060001",
//        ]);

    }

    public function test(){
        $proCurlService = new CurlService();
        $res = $proCurlService->pro()->s3015()->post("pa_products/queryPagePost",["batchName_like"=>"20240809 - 林泽键 - 13","limit"=>1,"page"=>1]);
        print_r($res);
    }

    //拼接广告关键词
    public function combineKeyword(){
        $json = '[{"status":1,"matchType":"broad","rule":["make","model","word"]},{"status":1,"matchType":"broad","rule":["model","word"]},{"status":1,"matchType":"broad","rule":["model","word","make"]}]';
        $arr = json_decode($json,true);
        $proCurlService = new CurlService();
        $list = DataUtils::getArrHeadData(DataUtils::getPageDocList($proCurlService->test()->s3044()->get("pa_sku_materials/queryPage",["skuId"=>"a24051600ux0001"])));
        $return = [
            "keywords" => $list['keywords'],
            "cpAsin" => $list['cpAsin'],
            "fitment" => $list['fitment']
        ];
        echo json_encode($return,JSON_UNESCAPED_UNICODE)."\n";

        $index = 1;
        foreach ($arr as $info) {
            echo json_encode($info['rule'],JSON_UNESCAPED_UNICODE)."：\n";
            $canPipe = true;
            $order = array();
            $this->log("====正在读取规则 {$index} ====");
            $this->log("==>规则是：".implode(" ",$info['rule']));
            $this->log("==>fitment的值有：".json_encode($return['fitment'],JSON_UNESCAPED_UNICODE));
            $this->log("==>核心词的值有：".json_encode($return['keywords'],JSON_UNESCAPED_UNICODE));
            $this->log("====开始组装====");
            $returnData = $this->getLastContent(0,$info['rule'],0,$return['fitment'],0,$return['keywords']);
            if ($returnData){
                foreach ($returnData as $combine){
                    $this->log(implode(" ",$combine));
                }
            }
            $this->log("====结束组装====");
            $this->log("\n");
            $index++;
        }


    }
    private function getLastContent($ruleStart, $fieldRule, $fitmentIndex,$fitmentList, $wordIndex,$wordsList, $combine = [], $returnData = []){
        //从0开始，拿到当前规则的第一个属性
        $field = isset($fieldRule[$ruleStart]) ? $fieldRule[$ruleStart] : null;
        if (!$field){
            //没有下一个指标属性，要开始组装了
            //$returnData[] = $combine;
            $ruleStart = 0;
            $fitmentIndex++;
            $combine = [];
            if ($fitmentIndex >= count($fitmentList)){
                //fitment已经用完了，直接导出来
                return $returnData;
            }

            return $this->getLastContent($ruleStart, $fieldRule, $fitmentIndex,$fitmentList, $wordIndex,$wordsList, $combine,$returnData);
        }

        //判断属性开始获取属性值
        if ($field === 'make' || $field === 'model'){
            //因为这个字段的特殊性，需要记录下标

            //获取当前的make model的下标
            if (isset($fitmentList[$fitmentIndex])){
                //查找该字段 - 获取字段值,放在这里
                $combine[] = $fitmentList[$fitmentIndex][$field] ? $fitmentList[$fitmentIndex][$field] : "";
                //到下一个属性
                $ruleStart++;
                return $this->getLastContent($ruleStart, $fieldRule, $fitmentIndex,$fitmentList, $wordIndex,$wordsList, $combine,$returnData);
            }


        }elseif($field === 'word'){
            //word 核心词先不顺序，直接用全部的数据

            if (isset($wordsList[$wordIndex])){
                //
                $tempCombine = $combine;
                $tempCombine[] = $wordsList[$wordIndex];
                $returnData[] = $tempCombine;
                $wordIndex++;
                return $this->getLastContent($ruleStart, $fieldRule, $fitmentIndex,$fitmentList, $wordIndex,$wordsList, $combine,$returnData);
            }else{
                //没有了
                $ruleStart++;
                $wordIndex = 0;
                return $this->getLastContent($ruleStart, $fieldRule, $fitmentIndex,$fitmentList, $wordIndex,$wordsList, $combine,$returnData);
            }
        }

        return [];
    }


    public function buildScuSkuProductMap(){

        $list = $this->getXlsxByFile("productIds.xlsx");
        $batchNameList = array_column($list, 'productId');


        $request = new CurlService();
        $request->test()->s3015()->get("");



    }
}

$s = new ProductSkuController();
//$s->updateProductSku();
//$s->updatePaProductAndDetail();
//$s->syncProSkuSPInfoToTest();
$s->buildSamePaProduct();
//$s->combineKeyword();