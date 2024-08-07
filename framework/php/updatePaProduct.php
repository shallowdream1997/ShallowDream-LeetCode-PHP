<?php
//echo json_encode($_REQUEST);
require_once ("/var/www/html/testProject/php/common.php");

$batch = $_REQUEST['batch'] ?? "";
$editFields = $_REQUEST['field'] ?? ["modifiedBy"=>"zhouangang"];

$condition = http_build_query([
    "limit" => 1,
    "page" => 1,
    "batch_in" => $batch
]);
//$getBatchByBatch = "http://master.nodejs.poms.ux168.cn:60009/api/market-analysis-reports/getMainSkuIdInfo?{$condition}";
$getBatchByBatch = "http://172.16.10.62:30015/api/pa_products/queryPage?{$condition}";
echo "请求pa_product：$getBatchByBatch.\n";
$resp = _getNodeJs($getBatchByBatch);
if ($resp && count($resp) > 0){
    $info = $resp[0];
    echo "查询出pa_product的_id：$info.\n";
    foreach ($editFields as $field => $value){
        $info[$field] = $value;
    }
    echo json_encode($info);
    $res = _create_post("http://172.16.10.62:30015/api/pa_products/{$info['_id']}", $info);
    echo json_encode($res);
}