<?php
require_once '../../vendor/autoload.php';

require_once("/var/www/html/testProject/php/common.php");
require_once("/var/www/html/testProject/php/env/env.php");

//echo phpinfo();

//$API = S3015TEST;
//$API = 'http://172.16.10.62:30015/api';
//$API = 'http://172.16.11.221:30015/api';
$API = 'https://master-angular-nodejs-poms-list-manage.ux168.cn/api';

//查询
//查询
$condition = http_build_query([
    "limit" => 1,
    "page" => 1,
    "optionName" => "page_switch_config"
]);

$getBatchByBatch = $API . "/option-val-lists/queryPage?{$condition}";
$r = _getNodeJs($getBatchByBatch);

log_message("请求返回：".json_encode($r,JSON_UNESCAPED_UNICODE));

if ($r && isset($r['data']) && count($r['data']) > 0) {
    $info = $r['data'][0];

    $paProductIds = [];
    $paProductIds  = $info['optionVal']['pa_product_detail_submit_leader_review']['paProductIds'];
    //log_message("paProductIds：".json_encode($paProductIds,JSON_UNESCAPED_UNICODE));

    $condition = http_build_query([
        "limit" => count($paProductIds),
        "page" => 1,
        "id_in" => implode(",",$paProductIds)
    ]);
    $getBatchByPaProducts = $API . "/pa_products/queryPage?{$condition}";
    $resp = _getNodeJs($getBatchByPaProducts);
    //print_r($resp);
    $batchNameList = [];
    if ($resp && isset($resp['data']) && count($resp['data']) > 0){
        foreach ($resp['data'] as $item){
            $batchNameList[] = [
                "_id" => $item['_id'],
                "batchName" => $item['batchName'],
            ];
        }
    }
    echo json_encode($batchNameList,JSON_UNESCAPED_UNICODE);
}else{
    echo json_encode([]);
}


log_message("查询：end");