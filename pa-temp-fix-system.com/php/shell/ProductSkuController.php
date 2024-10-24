<?php
require_once(dirname(__FILE__) . "/../../php/requiredfile/requiredfile.php");

class ProductSkuController
{
    private $log;
    private $requestUtils;

    private $module = "platform-wms-application";
    public function __construct($port = 'test')
    {
        $this->log = new MyLogger("product_sku");
        $this->requestUtils = new RequestUtils($port);
    }
    public function getModule($modlue){
        switch ($modlue){
            case "wms":
                $this->module = "platform-wms-application";
                break;
            case "pa":
                $this->module = "pa-biz-application";
                break;
            case "config":
                $this->module = "platform-config-service";
                break;
        }

        return $this;
    }
    /**
     * 日志记录
     * @param string $message 日志内容
     */
    private function log($message = "")
    {
        $this->log->log2($message);
    }

    public function getXlsxByFile($file = "")
    {
        $excelUtils = new ExcelUtils();
        $fileName = "../export/";
        switch ($file) {
            case "UpdatePaProduct.xlsx":
            case "UpdateProductSku.xlsx":
            case "UpdatePaSkuInfo.xlsx":
            case "sampleSku.xlsx":
                break;
            default:
                die("请选择文件!");
        }

        try {
            return $excelUtils->getXlsxData($fileName . $file);
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

        //读取品牌配置化
        $brandMap = $this->getBrandAttributeByPaPomsSkuBrandInitConfig();
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
                    if (isset($brandMap[$excelInfo['salesbrand']])){
                        $updateAttribute = $brandMap[$excelInfo['salesbrand']];
                    }else{
                        $updateAttribute[] = [
                            "channel" => "local",
                            "label" => "salesBrand",
                            "value" => $excelInfo['salesbrand'],
                        ];
                        $updateAttribute[] = [
                            "channel" => "amazon_us",
                            "label" => "brand",
                            "value" => $excelInfo['salesbrand'],
                        ];
                    }
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
     * @param string $file
     * @param false $score
     * @param string $updateBrandRemark
     * @return array|mixed
     */
    public function updatePaProductAndDetail($file, $score = false, $updateBrandRemark = "指定修改品牌")
    {
        //$list = $this->getXlsxByFile($file);
        if (!$file){
            die("请选择文件");
        }
        $excelUtils = new ExcelUtils();
        $fileName = "../export/";
        try {
            $list = $excelUtils->getXlsxData($fileName . $file);
        } catch (Exception $e) {
            die("获取数据失败");
        }

        $batchNameList = array_column($list, 'batchName');
        $chunkBatchNameList = [$batchNameList];
        if (count($batchNameList) > 500) {
            $this->log("批次号太多了，要爆炸啦，分批次处理");
            $chunkBatchNameList = array_chunk($batchNameList, 150);
        }
        //读取品牌配置化
        $brandMap = $this->getBrandAttributeByPaPomsSkuBrandInitConfig();

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
                    if ($score) {
                        if (DataUtils::checkArrFilesIsExist($updateData, 'salesBrand')) {
                            $scoreDetailList = [];
                            foreach ($batchInfo['paProductDetailList'] as $detailInfo) {
                                if (DataUtils::checkArrFilesIsExist($detailInfo, 'skuId') &&
                                    !DataUtils::checkArrFilesIsExistEqualValue($detailInfo, 'status', 'delete')) {
                                    $scoreDetailList[] = $detailInfo;
                                }
                            }
                            if (count($scoreDetailList) > 0) {
                                $this->log("{$updateData['salesBrand']}: 扣分加分");
                                //修改品牌要扣分的
                                $resp = $this->requestUtils->updateBrandByPaProduct($scoreDetailList, $updateData['salesBrand'], $updateBrandRemark);
                                $this->log(json_encode($resp, JSON_UNESCAPED_UNICODE));
                            } else {
                                $this->log("{$updateData['salesBrand']}: 没有要扣分加分更新品牌的数据");
                            }

                        }

                    } else {
                        $this->updatePPDetail($batchInfo['paProductDetailList'], $updateData,$brandMap);
                    }

                }
            }
        }

        $this->log("结束");
    }

    public function updatePPMain($paProductInfo, $updateData, $score)
    {
        $this->log("{$updateData['batchName']} 开始更新");
        foreach (['developer', 'platform', 'productlineId', 'salesBrand', 'tag', 'tag2', 'traceMan', 'ebayTraceMan','categoryId'] as $field) {
            if (DataUtils::checkArrFilesIsExist($updateData, $field)) {
                if ($score && $field == 'salesBrand') {
                    //品牌扣分的话，不能更新品牌
                    continue;
                }
                $this->log("{$field}: {$paProductInfo[$field]} -> {$updateData[$field]}");
                $paProductInfo[$field] = $updateData[$field];
                if ($field === 'traceMan') {
                    //如果是traceMan的更新，还需要更新amazonTraceMan
                    $paProductInfo['amazonTraceMan'] = $updateData[$field];
                }
            }
        }
        $updateResp = $this->requestUtils->updatePaProductInfo($paProductInfo);
        if ($updateResp) {
            $this->log("success");
            return true;
        } else {
            $this->log("fail");
            return false;
        }
    }

    public function updatePPDetail($paProductDetailList, $updateData, $brandMap = array())
    {
        $this->log("明细 开始更新");

        $updateSkuIdInfoGroupByProductId = [];
        if (count($paProductDetailList) > 0) {
            $updateSkuIdsList = [];
            foreach ($paProductDetailList as $detail) {
                if (!empty($detail['skuId'])) {
                    $updateSkuIdsList[] = $detail['skuId'];
                }
            }
            if (count($updateSkuIdsList) > 0) {
                $updateSkuIdInfoList = $this->requestUtils->getProductSkuList($updateSkuIdsList);
                $updateSkuIdInfoGroupByProductId = array_column($updateSkuIdInfoList, null, "productId");
            }
        }

        foreach ($paProductDetailList as $detailInfo) {
            $this->log("{$detailInfo['productName']}");
            $fixDetail = false;
            if (DataUtils::checkArrFilesIsExist($updateData, 'salesBrand') && $detailInfo['salesBrand'] != $updateData['salesBrand']) {
                $this->log("salesBrand: {$detailInfo['salesBrand']} -> {$updateData['salesBrand']}");
                $detailInfo['salesBrand'] = $updateData['salesBrand'];
                $fixDetail = true;
            }


            if (DataUtils::checkArrFilesIsExist($updateData, 'categoryId') && $detailInfo['categoryId'] != $updateData['categoryId']) {
                $categoryIdInfo = $this->requestUtils->getCategoryIdInfoV2($updateData['categoryId']);

                $this->log("categoryId: {$detailInfo['categoryId']} -> {$categoryIdInfo['categoryId']}");
                $this->log("cnCategory: {$detailInfo['cnCategory']} -> {$categoryIdInfo['cnCategoryFullPath']}");
                $detailInfo['categoryId'] = $categoryIdInfo['categoryId'];
                $detailInfo['cnCategory'] = $categoryIdInfo['cnCategoryFullPath'];
                $fixDetail = true;
            }


            if ($fixDetail) {
                $updateResp = $this->requestUtils->updatePaProductDetailInfo($detailInfo);
                if ($updateResp) {
                    $this->log("update pa product detail success");
                    return true;
                } else {
                    $this->log("update pa product detail fail");
                    return false;
                }
            } else {
                $this->log("不需要更新明细");
            }


            //更新product-sku资料表
            if (!DataUtils::checkArrFilesIsExist($updateSkuIdInfoGroupByProductId, $detailInfo['skuId'])) {
                $this->log("{$detailInfo['skuId']} 不存在POMS");
                continue;
            }
            $this->log("{$detailInfo['skuId']} 开始修改");
            $productInfo = $updateSkuIdInfoGroupByProductId[$detailInfo['skuId']];

            //category的修改
            $fixCategory = true;
            $oldCategory = "";
            if (DataUtils::checkArrFilesIsExist($updateData, 'categoryId')) {
                //获取分类的信息
                $oldCategory = $productInfo['category'];
                $categoryIdInfo = $this->requestUtils->getCategoryIdInfoV2($updateData['categoryId']);
                $this->log("category: {$productInfo['category']} -> {$categoryIdInfo['categoryId']}");
                $this->log("categoryPaths: {$productInfo['categoryPaths']} -> {$categoryIdInfo['categoryIds']}");
                $this->log("cn_Category: {$productInfo['cn_Category']} -> {$categoryIdInfo['cnCategoryFullPath']}");

                $productInfo['category'] = $categoryIdInfo['categoryId'];
                $productInfo['categoryPaths'] = $categoryIdInfo['categoryIds'];
                $productInfo['cn_Category'] = $categoryIdInfo['cnCategoryFullPath'];

                if ($oldCategory == $productInfo['category']){
                    $fixCategory = false;
                }else{
                    $this->log("分类不一样可以修改：{$oldCategory} -> {$productInfo['category']}");
                }
            }

            $updateAttribute = [];
            //有品牌的修改
            $fixSaleBrand = true;
            $oldSaleBrand = "";
            if (DataUtils::checkArrFilesIsExist($updateData, 'salesBrand')) {

                $filter = DataUtils::findIndexInArray($productInfo['attribute'], [
                    "label" => "salesBrand",
                    "channel" => "local"
                ]);
                if (!empty($filter)) {
                    foreach ($filter as $index => $array) {
                        $oldSaleBrand = $productInfo['attribute'][$index]['value'];
                        if ($productInfo['attribute'][$index]['value'] == $updateData['salesBrand']) {
                            //一样的品牌，不做修改
                            $fixSaleBrand = false;
                            continue;
                        }
                    }
                }

                if (isset($brandMap[$updateData['salesBrand']])){
                    $updateAttribute = $brandMap[$updateData['salesBrand']];
                }else{
                    $updateAttribute[] = [
                        "channel" => "local",
                        "label" => "salesBrand",
                        "value" => $updateData['salesBrand'],
                    ];
                    $updateAttribute[] = [
                        "channel" => "amazon_us",
                        "label" => "brand",
                        "value" => $updateData['salesBrand'],
                    ];
                }
                $this->log("品牌不一样可以修改：{$oldSaleBrand} --> {$updateData['salesBrand']}");
            } else {
                $fixSaleBrand = false;
            }

            $fixSaleBusinessType = true;
            $oldSaleBusinessType = "";
            if (DataUtils::checkArrFilesIsExist($updateData, 'tag')) {

                $filter = DataUtils::findIndexInArray($productInfo['attribute'], [
                    "label" => "Business Type",
                    "channel" => "local"
                ]);
                if (!empty($filter)) {
                    foreach ($filter as $index => $array) {
                        $oldSaleBusinessType = $productInfo['attribute'][$index]['value'];
                        if ($productInfo['attribute'][$index]['value'] == $updateData['tag']) {
                            $fixSaleBusinessType = false;
                            continue;
                        }
                    }
                }

                $updateAttribute[] = [
                    "channel" => "local",
                    "label" => "Business Type",
                    "value" => $updateData['tag'],
                ];
                $this->log("业务不一样可以修改：{$oldSaleBusinessType} --> {$updateData['tag']}");
            } else {
                $fixSaleBusinessType = false;
            }

            if (!$fixSaleBrand && !$fixSaleBusinessType && !$fixCategory) {
                continue;
            }

            if (!empty($updateAttribute)) {
                //要修改的字段里面有业务类型，品牌，
                if (DataUtils::checkArrFilesIsExist($productInfo, "attribute")) {
                    ProductUtils::editProductAttributeByArr($productInfo['attribute'], $updateAttribute);
                }
            }

            $updateProductResp = $this->requestUtils->updateProductSku($productInfo);
            if ($updateProductResp) {
                $this->log("update sku success");
                return true;
            } else {
                $this->log("update sku fail");
                return false;
            }
        }

    }

    /**
     * 同步生产环境sku -> sit环境
     */
    public function syncProSkuSPInfoToTest()
    {
        $skuIdList = [
            "a24051600ux0001"
        ];

        $proRequestUtils = new RequestUtils("pro");

        $skuIdListInfoArrayPro = $proRequestUtils->getProductSkuList($skuIdList);

        $skuIdListInfoArrayTest = $this->requestUtils->getProductSkuList($skuIdList);
        $testProductSkuInfoMap = array_column($skuIdListInfoArrayTest, null, 'productId');
        foreach ($skuIdListInfoArrayPro as $info) {
            if (DataUtils::checkArrFilesIsExist($testProductSkuInfoMap, $info['productId'])) {
                $deleteResp = $this->requestUtils->deleteProductSku($testProductSkuInfoMap[$info['productId']]['_id']);
                if ($deleteResp) {
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

    public function test()
    {
        $proCurlService = new CurlService();
        $res = $proCurlService->pro()->s3015()->post("pa_products/queryPagePost", ["batchName_like" => "20240809 - 林泽键 - 13", "limit" => 1, "page" => 1]);
        print_r($res);
    }

    //拼接广告关键词
    public function combineKeyword()
    {
        $json = '[{"status":1,"matchType":"broad","rule":["make"]}]';
        $arr = json_decode($json, true);
        $proCurlService = new CurlService();
        $list = DataUtils::getArrHeadData(DataUtils::getPageDocList($proCurlService->test()->s3044()->get("pa_sku_materials/queryPage", ["skuId" => "a24051600ux0001"])));
        $return = [
            "keywords" => $list['keywords'],
            "cpAsin" => $list['cpAsin'],
            "fitment" => $list['fitment']
        ];
        echo json_encode($return, JSON_UNESCAPED_UNICODE) . "\n";

        $index = 1;
        foreach ($arr as $info) {
            echo json_encode($info['rule'], JSON_UNESCAPED_UNICODE) . "：\n";
            $canPipe = true;
            $order = array();
            $this->log("====正在读取规则 {$index} ====");
            $this->log("==>规则是：" . implode(" ", $info['rule']));
            $this->log("==>fitment的值有：" . json_encode($return['fitment'], JSON_UNESCAPED_UNICODE));
            $this->log("==>核心词的值有：" . json_encode($return['keywords'], JSON_UNESCAPED_UNICODE));
            $this->log("====开始组装====");
            $returnData = $this->getLastContent(0, $info['rule'], 0, $return['fitment'], 0, $return['keywords']);
            if ($returnData) {
                foreach ($returnData as $combine) {
                    $this->log(implode(" ", $combine));
                }
            }
            $this->log("====结束组装====");
            $this->log("\n");
            $index++;
        }


    }

    private function getLastContent($ruleStart, $fieldRule, $fitmentIndex, $fitmentList, $wordIndex, $wordsList, $combine = [], $returnData = [])
    {
        //从0开始，拿到当前规则的第一个属性
        $field = isset($fieldRule[$ruleStart]) ? $fieldRule[$ruleStart] : null;
        if (!$field) {
            //没有下一个指标属性，要开始组装了
            //$returnData[] = $combine;
            $ruleStart = 0;
            $fitmentIndex++;
            $combine = [];
            if ($fitmentIndex >= count($fitmentList)) {
                //fitment已经用完了，直接导出来
                return $returnData;
            }

            return $this->getLastContent($ruleStart, $fieldRule, $fitmentIndex, $fitmentList, $wordIndex, $wordsList, $combine, $returnData);
        }

        //判断属性开始获取属性值
        if ($field === 'make' || $field === 'model') {
            //因为这个字段的特殊性，需要记录下标

            //获取当前的make model的下标
            if (isset($fitmentList[$fitmentIndex])) {
                //查找该字段 - 获取字段值,放在这里
                $combine[] = $fitmentList[$fitmentIndex][$field] ? $fitmentList[$fitmentIndex][$field] : "";
                $tempCombine = $combine;
                $returnData[] = $tempCombine;

                //到下一个属性
                $ruleStart++;
                return $this->getLastContent($ruleStart, $fieldRule, $fitmentIndex, $fitmentList, $wordIndex, $wordsList, $combine, $returnData);
            }


        } elseif ($field === 'word') {
            //word 核心词先不顺序，直接用全部的数据

            if (isset($wordsList[$wordIndex])) {
                //
                $tempCombine = $combine;
                $tempCombine[] = $wordsList[$wordIndex];
                $returnData[] = $tempCombine;
                $wordIndex++;
                return $this->getLastContent($ruleStart, $fieldRule, $fitmentIndex, $fitmentList, $wordIndex, $wordsList, $combine, $returnData);
            } else {
                //没有了
                $ruleStart++;
                $wordIndex = 0;
                return $this->getLastContent($ruleStart, $fieldRule, $fitmentIndex, $fitmentList, $wordIndex, $wordsList, $combine, $returnData);
            }
        }

        return [];
    }



    //生成PMO单 - 新(可重复试行)
    public function savePaPmo()
    {
        $requestUtils = new RequestUtils("test");
        //$list = $requestUtils->getPaProductDetailPageList(['paProductId'=>"66977efa5305ef6e841db297","limit"=>1000]);

        $batchNameData = $requestUtils->getPaProductInfoByBatchName("20240717 - 郑雨生 - 3");
        $paProductInfo = $batchNameData['paProductIdInfo'];
        $list = $batchNameData['detailList'];

        $supplierId = $paProductInfo['supplierType'] == '好彩汽配2013u' ? 1278 : 1990;
        $factoryInfo = $requestUtils->getFactoryInfoByFactoryFullName($supplierId, '南宫市固卡汽车配件有限公司');

        $skuIdInfoList = [];
        foreach ($list as $info) {
            $skuIdInfoList[] = [
                "skuId" => $info['skuId'],
                "tempId" => $info['tempId'],
                "productLineName" => $info['productName'],
                "productModel" => $info['productModel'] ?: $info['tempSkuId'],
                "purchasePrice" => $info['itemPrice'],
                "quantity" => $info['itemMoq'],
                "sellingNum" => $info['itemUnitQuantity'],
                "unit" => $info['itemUnitQuantityUnit'],
                "purchaseLink" => $info['purchaseLink'],
                "factoryName" => $factoryInfo['factoryFullName'] ?? $info['suppliersCompanyName'],
                "factoryId" => $factoryInfo['id'] ?? "",
                "salesRegion" => $info['salesRegion'] ?? [],
                "photoAddress" => $info['picUrl'],
                "supplierName" => $paProductInfo['supplierType'] == '好彩汽配2013u' ? "好彩汽配2013u" : "个人护理2016u",
                "supplierSequenceId" => $supplierId,
                "titleEn" => "Not filled",
                "brand" => $paProductInfo['salesBrand'],
                "developer" => $paProductInfo['developer'],
                "traceman" => $paProductInfo['traceMan'],
                "ceBillNo" => $info['ceBillNo'] ?? "",
            ];
        }
        $this->log(json_encode($skuIdInfoList, JSON_UNESCAPED_UNICODE));
        $this->log(date("Ymd", time()));

        $userName = "zhouangang";
        $pmoResult = ["sequenceId" => "PMO2024081900006"];
//        $pmoResult = $requestUtils->getSequenceId("PMO");
        if ($pmoResult) {
            $pmoBillNo = $pmoResult['sequenceId'];
            $traceManInfo = $requestUtils->getUserSheetByUserName($paProductInfo['traceMan']);
            $pmoMainTableInfo = [
                "pmoBillNo" => $pmoBillNo,
                "verticalName" => "PA",
                "operatorName" => $traceManInfo['cName'],
                "batch" => $paProductInfo['batchName'],
                "traceman" => $paProductInfo['traceMan'],
                "purchaseType" => "new",
                "type" => "normal",
                "remark" => "",
                "departmentId" => $traceManInfo['verticalSequenceId'],
                "departmentCn" => $traceManInfo['verticalName'],
            ];
            $curlService = new CurlService();
            $savePAPmoRes = DataUtils::getResultData($curlService->local()->s3009()->post("market-analysis-reports/savePaPmoMainTableAndSkuIdInfo", [
                'pmoMainTableInfo' => $pmoMainTableInfo,
                'skuIdInfoList' => $skuIdInfoList,
                'userName' => $userName,
            ]));
            if ($savePAPmoRes) {
                $this->log($savePAPmoRes['message']);
                if ($savePAPmoRes['isAddMainTableSuccess']) {
                    $this->log(json_encode($savePAPmoRes['mainSkuIdInfo'], JSON_UNESCAPED_UNICODE));
                    $this->log("共有sku：" . count($savePAPmoRes['skuIdList']) . " 个，为：" . implode(',', $savePAPmoRes['skuIdList']));
                }
            }
        }
//
    }

    public function getQms()
    {
        $theDayAfterTomorrow = date("Y-m-d 08:00:00", strtotime("+1 day"));
        echo $theDayAfterTomorrow;
    }


    public function updatePaSkuInfoReplenishManBySkuIds()
    {
        $requestService = (new CurlService())->test();
        $list = $this->getXlsxByFile("UpdatePaSkuInfo.xlsx");
        $lists = array_column($list,"skuId");
        foreach (array_chunk($lists, 150) as $skuIdList) {
            $resp = DataUtils::getPageList($requestService->s3015()->get("pa_sku_infos/queryPage", [
                "limit" => 1000,
                "skuId_in" => implode(",", $skuIdList)
            ]));
            if ($resp) {
                foreach ($resp as $info){
                    $info['replenishMan'] = "huangzheng";
                    $requestService->s3015()->put("pa_sku_infos/{$info['_id']}",$info);
                }
            }
        }
    }

    public function downloadSampleSku(){
        $data = $this->getXlsxByFile("sampleSku.xlsx");
        $list = array_column($data,"skuid");
        $curlService = (new CurlService())->pro();

        if (count($list) > 0) {
            $curlService->gateway();
            $this->getModule('wms');
            $sampleSkuIdList = [];
            foreach (array_chunk($list,500) as $skuIdList){
                $resp = DataUtils::getNewResultData($curlService->getWayPost($this->module . "/receive/sample/expect/v1/page", [
                    "skuIdIn" => $skuIdList,
                    "vertical" => "PA",
                    "category" => "dataTeam",
                    "pageSize" => 500,
                    "pageNum" => 1,
                ]));
                $sampleSkuIdList = array_merge($sampleSkuIdList,$resp['list']);
            }

            $skuMap = [];
            if (count($sampleSkuIdList) > 0){
                foreach ($sampleSkuIdList as $info){
                    $skuMap[$info['skuId']] = $info;
                }
            }
            $downData = [];
            foreach ($list as $sku){
                if (isset($skuMap[$sku])){
                    $downData[] = [
                        "id" => $skuMap[$sku]['id'],
                        "skuId" => $skuMap[$sku]['skuId'],
                        "isSample" => "有留样",
                        "createBy" => $skuMap[$sku]['createBy'],
                        "createTime" => $skuMap[$sku]['createTime'],
                    ];
                }else{
                    $downData[] = [
                        "id" => "",
                        "skuId" => $sku,
                        "isSample" => "未留样",
                        "createBy" => "",
                        "createTime" => "",
                    ];
                }
            }
            if (count($downData) > 0){
                $this->log("导出文件");
                $excelUtils = new ExcelUtils();
                $excelUtils->download([
                    "id" => "id",
                    "skuId" => "skuId",
                    "isSample" => "是否预计留样",
                    "createBy" => "创建人",
                    "createTime" => "创建日期",
                ],$downData,"检测是否预计留样_" . date('YmdHis') . ".xlsx");
            }else{
                $this->log("没有文件可以导出");
            }


        }


    }

    /**
     * 读取配置中心数据
     * @param $configKey
     * @return array|mixed
     */
    public function getPlatformConfigByKey($configKey){
        $curlService = (new CurlService())->pro();
        $curlService->gateway();
        $this->getModule('config');

        $resp = DataUtils::getNewResultData($curlService->getWayPost($this->module . "/business/config/v1/getConfigByKey", [
            "configKey" => $configKey,
        ]));
        if (!$resp['configValue']){
            return [];
        }
        return $resp['configValue'];
    }

    /**
     * 资料初始化品牌配置化
     * @return array
     */
    public function getBrandAttributeByPaPomsSkuBrandInitConfig(){
        $redisService = new RedisService();
        $get = $redisService->hGet(REDIS_SKU_INIT_BRAND_ID_KEY, REDIS_SKU_INIT_BRAND_KEY);
        if (empty($get)) {
            $configValue = $this->getPlatformConfigByKey("PA_POMS_SKU_BRAND_INIT_CONFIG");
            $redisService->hSet(REDIS_SKU_INIT_BRAND_ID_KEY, REDIS_SKU_INIT_BRAND_KEY, json_encode($configValue, JSON_UNESCAPED_UNICODE), 60 * 60 * 12);
        }else {
            $configValue = json_decode($get, true);
        }

        $brandMap = [];
        foreach ($configValue as $brandList){
            if (!isset($brandList['brand'])){
                continue;
            }
            $brandMap[$brandList['brand']] = [];
            $updateAttribute = [];
            foreach ($brandList as $key => $value){
                if($key !== "brand" && $key !== "categoryId"){
                    if ($key === "local"){
                        $updateAttribute[] = [
                            "channel" => $key,
                            "label" => "salesBrand",
                            "value" => $value,
                        ];
                    }else{
                        $updateAttribute[] = [
                            "channel" => $key,
                            "label" => "brand",
                            "value" => $value,
                        ];
                    }
                }
            }
            $brandMap[$brandList['brand']] = $updateAttribute;
        }
        $this->log("品牌配置化读取：" . json_encode($brandMap,JSON_UNESCAPED_UNICODE));
        return $brandMap;
    }

}

$s = new ProductSkuController("test");
//$s->updateProductSku();
//$s->updatePaProductAndDetail("UpdatePaProduct.xlsx");
//$s->downloadSampleSku();
//$s->syncProSkuSPInfoToTest();
//$s->buildScuSkuProductMap();
//$s->combineKeyword();
//$s->savePaPmo();
//$s->getQms();
//$s->updatePaSkuInfoReplenishManBySkuIds();

$s->getBrandAttributeByPaPomsSkuBrandInitConfig();