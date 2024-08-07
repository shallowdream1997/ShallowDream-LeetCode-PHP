<?php
//echo json_encode($_REQUEST);
require_once ("/var/www/html/testProject/php/common.php");

$ce = $_REQUEST['CE'] ?? "";
$batch = $_REQUEST['batch'] ?? "";
if (empty($ce)){
    echo "请输入CE单.\n";
    return;
}
if (empty($batch)){
    echo "请输入批次号.\n";
    return;
}

echo "CE单：$ce.\n";
echo "批次号：$batch.\n";
$condition = http_build_query([
    "batch_in" => $batch
]);
//$getBatchByBatch = "http://master.nodejs.poms.ux168.cn:60009/api/market-analysis-reports/getMainSkuIdInfo?{$condition}";
$getBatchByBatch = "http://172.16.10.62:30009/api/market-analysis-reports/getMainSkuIdInfo?{$condition}";
echo "请求main_skuId_info：$getBatchByBatch.\n";
$resp = _getNodeJs($getBatchByBatch);
if ($resp && count($resp) > 0){
    $mainSkuIdInfoId = $resp[0]['_id'];
    echo "查询出mainSkuIdInfoId：$mainSkuIdInfoId.\n";

    $res = _create_post("http://172.16.10.40:8000/api/pa-product-info/paProductInfoInit", array(
        "ceBillNo" => $ce,
        "batchName" => $batch,
        "mainSkuIdInfoId" => $mainSkuIdInfoId
    ));
    echo json_encode($res);
}