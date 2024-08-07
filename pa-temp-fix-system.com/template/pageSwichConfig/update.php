<?php
require_once '../../vendor/autoload.php';

require_once("/var/www/html/testProject/php/common.php");
require_once("/var/www/html/testProject/php/env/env.php");

//echo phpinfo();

//$API = S3015TEST;
//$API = 'http://172.16.10.62:30015/api';
//$API = 'http://172.16.11.221:30015/api';
$API = 'https://master-angular-nodejs-poms-list-manage.ux168.cn/api';

$batchNameList = $_REQUEST['batchNameList'] ?? [
    "20200831 - 施晓文 - 1",
        "20200901 - 李锦烽 - 1"
    ];
$status = $_REQUEST['status'] ?? 2;
//查询
$batchNameList = array_unique($batchNameList);
$condition = http_build_query([
    "limit" => count($batchNameList),
    "page" => 1,
    "batchName_in" => implode(",",$batchNameList)
]);
$getBatchByPaProducts = $API . "/pa_products/queryPage?{$condition}";
$resp = _getNodeJs($getBatchByPaProducts);
$ids = [];
if ($resp && isset($resp['data']) && count($resp['data']) > 0){
    $ids = array_column($resp['data'],"_id");
}

if (count($ids) > 0){

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
        if ($status == 1){
            $info['optionVal']['pa_product_detail_submit_leader_review']['paProductIds'] = $ids;
        }elseif ($status == 2){
            $info['optionVal']['pa_product_detail_submit_leader_review']['paProductIds'] = array_merge($info['optionVal']['pa_product_detail_submit_leader_review']['paProductIds'],$ids);
            $info['optionVal']['pa_product_detail_submit_leader_review']['paProductIds'] = array_unique($info['optionVal']['pa_product_detail_submit_leader_review']['paProductIds']);
        }

        $url = $API . "/option-val-lists/{$info['_id']}";

        $updateMainRes = put($url, $info);

        echo json_encode(true);
    }else {
        echo json_encode(false);
    }


}else {
    echo json_encode(false);
}

log_message("更新：end");