<?php
require_once '../../vendor/autoload.php';

require_once("/var/www/html/testProject/php/common.php");
require_once("/var/www/html/testProject/php/env/env.php");

//echo phpinfo();

//$API = 'http://172.16.10.62:30015/api';
$API = 'https://master-angular-nodejs-poms-list-manage.ux168.cn/api';

$title = $_REQUEST['title'] ?? [];
//var_dump($_REQUEST);
log_message("翻译查询：start");
log_message("翻译查询请求参数：".json_encode($_REQUEST,JSON_UNESCAPED_UNICODE));

//查询
if (!empty($title)) {
    $condition = http_build_query([
        "limit" => 100,
        "page" => 1,
        "title_in" => $title,
    ]);

    $getBatchByBatch = $API . "/translation_managements/queryPage?{$condition}";
    $resp = _getNodeJs($getBatchByBatch);

    log_message("请求返回：".json_encode($resp,JSON_UNESCAPED_UNICODE));

    if ($resp && isset($resp['data']) && count($resp['data']) > 0) {
        echo json_encode($resp['data'],JSON_UNESCAPED_UNICODE);
    }else{
        echo json_encode([]);
    }
}else{
    echo json_encode([]);
}

log_message("翻译查询：end");