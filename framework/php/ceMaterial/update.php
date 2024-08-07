<?php
//echo json_encode($_REQUEST);
require_once("/var/www/html/testProject/php/common.php");
require_once("/var/www/html/testProject/php/env/env.php");

//$API = 'http://172.16.10.62:30044/api';
$API = 'https://master-nodejs-poms-list-nest.ux168.cn/api';

$_id = $_REQUEST['_id'] ?? "";
$status = "materialComplete";

//var_dump($_REQUEST);
log_message("更新：start");

log_message("更新请求参数：" . json_encode($_REQUEST, JSON_UNESCAPED_UNICODE));


if (empty($_id) && empty($status)) {
    log_message("没有数据");
    echo json_encode(false);
    return;
}


//制作查询
$getBatchByBatch = $API . "/pa_ce_materials/{$_id}";

$mainRes = _getNodeJs($getBatchByBatch);

if ($mainRes && isset($mainRes['data'])) {
    $mainInfo = $mainRes['data'];
    $mainInfo['status'] = $status;
    $putTranslationManagementUrl = $API . "/pa_ce_materials/{$mainInfo['_id']}";
    $updateMainRes = put($putTranslationManagementUrl, $mainInfo);
    log_message(json_encode($updateMainRes, JSON_UNESCAPED_UNICODE));

    echo json_encode(true);
} else {
    echo json_encode(false);
}


log_message("翻译更新：end");