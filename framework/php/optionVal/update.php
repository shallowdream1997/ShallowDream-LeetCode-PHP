<?php
require_once '../../vendor/autoload.php';

require_once("/var/www/html/testProject/php/common.php");
require_once("/var/www/html/testProject/php/env/env.php");

//echo phpinfo();

//$API = 'http://172.16.10.62:30015/api';
$API = 'https://master-angular-nodejs-poms-list-manage.ux168.cn/api';

$channel = $_REQUEST['channel'] ?? "";
$stocks = $_REQUEST['stocks'] ?? "";
//var_dump($_REQUEST);

log_message("更新：start");
log_message("更新请求参数：" . json_encode($_REQUEST, JSON_UNESCAPED_UNICODE));


if (empty($channel) && empty($stocks)) {
    log_message("请填写渠道以及对应的仓库");
    echo json_encode(false);
    return;
}

//查询
$condition = http_build_query([
    "limit" => 1,
    "page" => 1,
    "optionName" => "pa_fba_channel_seller_config"
]);

$getBatchByBatch = $API . "/option-val-lists/queryPage?{$condition}";
$resp = _getNodeJs($getBatchByBatch);

log_message("请求返回：" . json_encode($resp, JSON_UNESCAPED_UNICODE));

if ($resp && isset($resp['data']) && count($resp['data']) > 0) {
    $info = $resp['data'][0];
    if (isset($info['optionVal']['amazon'][$channel]) && !empty($info['optionVal']['amazon'][$channel])) {
        $info['optionVal']['amazon'][$channel] = array_merge($info['optionVal']['amazon'][$channel], explode(",", $stocks));
    }else{
        $info['optionVal']['amazon'][$channel] = array_merge([], explode(",", $stocks));
    }
    $info['optionVal']['amazon'][$channel] = array_unique($info['optionVal']['amazon'][$channel]);
    $putTranslationManagementUrl = $API . "/option-val-lists/{$info['_id']}";

    $updateMainRes = put($putTranslationManagementUrl, $info);

    log_message(json_encode($updateMainRes, JSON_UNESCAPED_UNICODE));
    echo json_encode(true);
} else {
    echo json_encode(false);
}


log_message("查询：end");