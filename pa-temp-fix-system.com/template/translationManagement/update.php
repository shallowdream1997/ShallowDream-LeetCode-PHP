<?php
//echo json_encode($_REQUEST);
require_once("/var/www/html/testProject/php/common.php");
require_once("/var/www/html/testProject/php/env/env.php");

//$API = 'http://172.16.10.62:30015/api';
$API = 'https://master-angular-nodejs-poms-list-manage.ux168.cn/api';

$title = $_REQUEST['title'] ?? "";
$status = $_REQUEST['status'] ?? "";
$applyName = $_REQUEST['applyName'] ?? "";
$applyTime = $_REQUEST['applyTime'] ?? "";
//var_dump($_REQUEST);
log_message("翻译更新：start");

log_message("翻译更新请求参数：" . json_encode($_REQUEST, JSON_UNESCAPED_UNICODE));


if (empty($title) && empty($status)) {
    log_message("没有数据");
    echo json_encode(false);
    return;
}


//制作查询
$condition = http_build_query([
    "limit" => 1,
    "page" => 1,
    "title" => $title,
]);

$getBatchByBatch = $API . "/translation_managements/queryPage?{$condition}";

$mainResp = _getNodeJs($getBatchByBatch);

if ($mainResp && isset($mainResp['data']) && count($mainResp['data']) > 0) {
    $mainInfo = $mainResp['data'][0];

    if ($mainInfo['status'] != "5") {
        $mainInfo['status'] = $status;
        foreach ($mainInfo['skuIdList'] as &$detailInfo) {
            $detailInfo['status'] = $status;
        }
        if ($status == '4' && !empty($applyName) && !empty($applyTime)){
            //翻译完成的需要审核人
            $mainInfo['applyUserName'] = $applyName;
            $mainInfo['applyTime'] = $applyTime;
        }
        $putTranslationManagementUrl = $API . "/translation_managements/{$mainInfo['_id']}";

        $updateMainRes = put($putTranslationManagementUrl, $mainInfo);

        log_message(json_encode($updateMainRes, JSON_UNESCAPED_UNICODE));
//
        $dcontion = http_build_query([
            "limit" => 1000,
            "translationMainId" => $mainInfo['_id']
        ]);
        $searchDetailUrl = $API . "/translation_management_skus/queryPage?{$dcontion}";
        $detailRes = _getNodeJs($searchDetailUrl);
        if ($detailRes && isset($detailRes['data']) && count($detailRes['data']) > 0) {

            foreach ($detailRes['data'] as $detail) {
                if ($detail['status'] != "5") {
                    $detail['status'] = $status;

                    $putTranslationManagementDetailUrl = $API . "/translation_management_skus/{$detail['_id']}";

                    $updateDetailRes = put($putTranslationManagementDetailUrl, $detail);

                }
            }
        }

    }
    echo json_encode(true);
} else {
    echo json_encode(false);
}


log_message("翻译更新：end");