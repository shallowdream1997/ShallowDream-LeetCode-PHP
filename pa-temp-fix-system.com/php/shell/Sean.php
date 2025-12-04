<?php
require_once(dirname(__FILE__) . "/SyncCurlController.php");

// 实例化SyncCurlController类
$syncCurl = new SyncCurlController();

// 调用你想要执行的方法，例如：
$syncCurl->test();  // 测试方法
// $syncCurl->deleteCampaign();  // 删除广告活动
// $syncCurl->syncProduct();  // 同步产品数据
// $syncCurl->getPaSkuMaterial();  // 获取PA SKU材料


