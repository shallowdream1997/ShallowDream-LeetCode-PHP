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

//查询
$condition = http_build_query([
    "limit" => 100,
    "page" => 1,
    "status" => 10
]);

$getBatchByBatch = $S3044 . "/pa_product_brand_score_bases/queryPage?{$condition}";
$r = _getNodeJs($getBatchByBatch);

if ($r && isset($r['data']) && count($r['data']['data']) > 0) {

    $c1 = http_build_query([
        "limit" => 1,
        "page" => 1,
        "optionName" => "pa_product_brand_config"
    ]);
    $g1 = $S3015. "/option-val-lists/queryPage?{$c1}";
    $r1 = _getNodeJs($g1);
    $saleBrandList = [];
    if ($r1 && isset($r1['data']) && count($r1['data']) > 0) {
        $info = $r1['data'][0];
        $saleBrandList = $info['optionVal']['product'];
    }
    $lundanBrandList = $r['data']['data'];
    //print_r($resp);
    echo json_encode([
        "list" => $lundanBrandList,
        "list1" => $saleBrandList,
        "env" => $env,
    ],JSON_UNESCAPED_UNICODE);
}else{
    echo json_encode([]);
}


log_message("查询：end");