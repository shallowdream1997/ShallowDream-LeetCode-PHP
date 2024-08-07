<?php
require_once '../../vendor/autoload.php';

require_once("/var/www/html/testProject/php/common.php");
require_once("/var/www/html/testProject/php/env/env.php");


$env = "test";
$S3044 = "";
$S3015 = "";
if ($env == "test"){
    $S3044 = 'http://172.16.10.62:30044/api';
    $S3015 = 'http://172.16.10.62:30015/api';
}else if ($env == "pro"){
    $S3044 = 'https://master-nodejs-poms-list-nest.ux168.cn/api';
    $S3015 = 'https://master-angular-nodejs-poms-list-manage.ux168.cn/api';
}



// 假设使用$_POST或$_GET全局数组接收参数
$json = file_get_contents('php://input');
$data = json_decode($json, true); // 将JSON字符串转换为关联数组

if (in_array("lundan",$data['scenceList'])){
    if (count($data['brandList']) > 0 && count($data['cnCategoryFirstList']) > 0 && count($data['businessTypeList']) > 0){
        //检查
        $condition = http_build_query([
            "limit" => 2000,
            "page" => 1,
            "status" => 10,
        ]);
        $r = _getNodeJs($S3044 . "/pa_product_brand_score_bases/queryPage?{$condition}");
        $map = [];
        if ($r && isset($r['data']) && count($r['data']['data']) > 0) {
            foreach ($r['data']['data'] as $item){
                $map["{$item['salesBrand']}_{$item['cnCategoryFirst']}_{$item['businessType']}"] = $item['_id'];
            }
        }

        $errorList = [];
        foreach ($data['brandList'] as $salesBrand) {
            foreach ($data['cnCategoryFirstList'] as $categoryFirst) {
                foreach ($data['businessTypeList'] as $businessType) {

                    if (isset($map["{$salesBrand}_{$categoryFirst}_{$businessType}"])){
                        continue;
                    }

                    $info = [
                        "salesBrand" => $salesBrand,
                        "businessType" => $businessType,
                        "cnCategoryFirst" => $categoryFirst,
                        "scoreNow" => 0,
                        "addScore" => 1,
                        "baseScore" => 1,
                        "skuNum" => 0,
                        "LastAddOn" => null,
                        "status" => 10,
                        "modifiedBy" => "",
                        "createdBy" => "system",
                    ];
                    $res = post($S3044 . "/pa_product_brand_score_bases/",$info);
                    if ($res && $res['status'] == 'success'){

                    }else{
                        $errorList[] = $salesBrand;
                    }
                }
            }
        }
    }
}

if (in_array("pa_product", $data['scenceList'])) {
    if (count($data['brandList']) > 0) {
        $c1 = http_build_query([
            "limit" => 1,
            "page" => 1,
            "optionName" => "pa_product_brand_config"
        ]);
        $g1 = $S3015 . "/option-val-lists/queryPage?{$c1}";
        $r1 = _getNodeJs($g1);
        $saleBrandList = [];
        if ($r1 && isset($r1['data']) && count($r1['data']) > 0) {
            $info = $r1['data'][0];
            $saleBrandList = $info['optionVal']['product'];
            foreach ($data['brandList'] as $brand) {
                if (!in_array($brand, $saleBrandList)) {
                    $saleBrandList[] = $brand;
                }
            }
            $info['optionVal']['product'] = $saleBrandList;
            $putUrl = $S3015 . "/option-val-lists/{$info['_id']}";
            $updateMainRes = put($putUrl, $info);
        }
    }
}

echo json_encode(true);