<?php
require_once '../../vendor/autoload.php';

require_once("/var/www/html/testProject/php/common.php");
require_once("/var/www/html/testProject/php/env/env.php");

//echo phpinfo();

//$API = 'http://172.16.10.62:30015/api';
$API = 'https://master-angular-nodejs-poms-list-manage.ux168.cn/api';

//查询
$condition = http_build_query([
    "limit" => 1,
    "page" => 1,
    "optionName" => "pa_fba_channel_seller_config"
]);

$getBatchByBatch = $API . "/option-val-lists/queryPage?{$condition}";
$resp = _getNodeJs($getBatchByBatch);

log_message("请求返回：".json_encode($resp,JSON_UNESCAPED_UNICODE));

if ($resp && isset($resp['data']) && count($resp['data']) > 0) {
    $info = $resp['data'][0];
    $list = [];
    foreach ($info['optionVal']['amazon'] as $channel => $stocks){
        $list[] = [
            "channel" => $channel,
            "nowStocks" => $stocks
        ];
    }
    echo json_encode($list,JSON_UNESCAPED_UNICODE);
}else{
    echo json_encode([]);
}


log_message("查询：end");