<?php

// autoload_classmap.php — 自动生成，包含所有项目类

$vendorDir = dirname(__DIR__);
$baseDir = dirname($vendorDir);

return array(
    // vendor
    'Composer\\InstalledVersions' => $vendorDir . '/composer/InstalledVersions.php',
    'Stringable' => $vendorDir . '/myclabs/php-enum/stubs/Stringable.php',

    // php/core/ - 核心服务
    'MyLogger' => $baseDir . '/php/core/Logger.php',
    'CurlService' => $baseDir . '/php/core/CurlService.php',
    'RedisService' => $baseDir . '/php/core/RedisService.php',
    'ExcelUtils' => $baseDir . '/php/core/ExcelUtils.php',
    'MysqlService' => $baseDir . '/php/core/MysqlService.php',
    'CrudService' => $baseDir . '/php/core/CrudService.php',

    // php/utils/ - 工具类
    'DataUtils' => $baseDir . '/php/utils/DataUtils.php',
    'ProductUtils' => $baseDir . '/php/utils/ProductUtils.php',

    // php/service/ - 业务服务
    'RequestUtils' => $baseDir . '/php/service/RequestUtils.php',
    'SpApi' => $baseDir . '/php/service/SpApi.php',

    // php/shell/ - 顶层脚本
    'Calc' => $baseDir . '/php/shell/Calc.php',

    // php/shell/ebay/
    'DelEbayBillRoundController' => $baseDir . '/php/shell/ebay/DelEbayBillRoundController.php',
    'ExecuteEbaySellerAllocationController' => $baseDir . '/php/shell/ebay/ExecuteEbaySellerAllocationController.php',
    'ExportEbaySellerAllocationRecordController' => $baseDir . '/php/shell/ebay/ExportEbaySellerAllocationRecordController.php',
    'FillSellerAllocationCategoryConfigController' => $baseDir . '/php/shell/ebay/FillSellerAllocationCategoryConfigController.php',

    // php/shell/fix/
    'FixCeSkuMaterial' => $baseDir . '/php/shell/fix/FixCeSkuMaterial.php',
    'FixPaSkuMaterialSpDataController' => $baseDir . '/php/shell/fix/FixPaSkuMaterialSpDataController.php',
    'FixPmoSkuController' => $baseDir . '/php/shell/fix/FixPmoSkuController.php',
    'FixSkuSupplierQuotePrice' => $baseDir . '/php/shell/fix/FixSkuSupplierQuotePrice.php',

    // php/shell/gateway/
    'GatWayRequestController' => $baseDir . '/php/shell/gateway/GatWayRequestController.php',

    // php/shell/product/
    'ProductSkuController' => $baseDir . '/php/shell/product/ProductSkuController.php',

    // php/shell/sync/ - 从 SyncCurlController 拆分
    'AdSync' => $baseDir . '/php/shell/sync/AdSync.php',
    'CeMaterialSync' => $baseDir . '/php/shell/sync/CeMaterialSync.php',
    'ConfigSync' => $baseDir . '/php/shell/sync/ConfigSync.php',
    'DataExport' => $baseDir . '/php/shell/sync/DataExport.php',
    'PmoSync' => $baseDir . '/php/shell/sync/PmoSync.php',
    'ProductSync' => $baseDir . '/php/shell/sync/ProductSync.php',
    'SguSync' => $baseDir . '/php/shell/sync/SguSync.php',
    'SkuFix' => $baseDir . '/php/shell/sync/SkuFix.php',
    'SkuMaterialSync' => $baseDir . '/php/shell/sync/SkuMaterialSync.php',
    'Sync' => $baseDir . '/php/shell/sync/Sync.php',
    'SyncAiCategoryRecommand' => $baseDir . '/php/shell/sync/SyncAiCategoryRecommand.php',
    'SyncJob1Controller' => $baseDir . '/php/shell/sync/SyncJob1Controller.php',
    'SyncProductSku' => $baseDir . '/php/shell/sync/SyncProductSku.php',
    'SyncSkuMaterialToAudit' => $baseDir . '/php/shell/sync/SyncSkuMaterialToAudit.php',

    // php/shell/sp/ - SP 广告脚本（顶层）
    'SpController' => $baseDir . '/php/shell/sp/SpController.php',
    'SpEnabledController' => $baseDir . '/php/shell/sp/SpEnabledController.php',
    'SpFindCanNotCreateController' => $baseDir . '/php/shell/sp/SpFindCanNotCreateController.php',
    'SpPausedController' => $baseDir . '/php/shell/sp/SpPausedController.php',
    'SpRuleController' => $baseDir . '/php/shell/sp/SpRuleController.php',

    // php/shell/sp/adgroup/
    'SpArchivedErrorAdGroupController' => $baseDir . '/php/shell/sp/adgroup/SpArchivedErrorAdGroupController.php',
    'SpPausedAdGroupController' => $baseDir . '/php/shell/sp/adgroup/SpPausedAdGroupController.php',
    'SpUpdateAdGroupController' => $baseDir . '/php/shell/sp/adgroup/SpUpdateAdGroupController.php',

    // php/shell/sp/campaign/
    'SpDelRepeatCampaignController' => $baseDir . '/php/shell/sp/campaign/SpDelRepeatCampaignController.php',
    'SpUpdateCampaignBudgetController' => $baseDir . '/php/shell/sp/campaign/SpUpdateCampaignBudgetController.php',
    'SpUpdateCampaignController' => $baseDir . '/php/shell/sp/campaign/SpUpdateCampaignController.php',

    // php/shell/sp/common/
    'SpEnabledCampaignController' => $baseDir . '/php/shell/sp/common/SpEnabledCampaignController.php',
    'SpEnabledNKeywordAndTargetByAdGroupController' => $baseDir . '/php/shell/sp/common/SpEnabledNKeywordAndTargetByAdGroupController.php',
    'SpPausedNKeywordAndNTargetByAdGroupController' => $baseDir . '/php/shell/sp/common/SpPausedNKeywordAndNTargetByAdGroupController.php',
    'SpSyncPomsController' => $baseDir . '/php/shell/sp/common/SpSyncPomsController.php',

    // php/shell/sp/keyword/
    'SpCreateKeywordController' => $baseDir . '/php/shell/sp/keyword/SpCreateKeywordController.php',
    'SpEnabledKeywordController' => $baseDir . '/php/shell/sp/keyword/SpEnabledKeywordController.php',
    'SpPausedKeywordController' => $baseDir . '/php/shell/sp/keyword/SpPausedKeywordController.php',
    'SpUpdateKeywordBidController' => $baseDir . '/php/shell/sp/keyword/SpUpdateKeywordBidController.php',

    // php/shell/sp/negativeKeyword/
    'SpCreateNegativeKeywordController' => $baseDir . '/php/shell/sp/negativeKeyword/SpCreateNegativeKeywordController.php',
    'SpEnabledNegativeKeywordController' => $baseDir . '/php/shell/sp/negativeKeyword/SpEnabledNegativeKeywordController.php',
    'SpPausedNegativeKeywordController' => $baseDir . '/php/shell/sp/negativeKeyword/SpPausedNegativeKeywordController.php',

    // php/shell/sp/negativeTarget/
    'SpCreateNegativeTargetController' => $baseDir . '/php/shell/sp/negativeTarget/SpCreateNegativeTargetController.php',
    'SpEnabledNegativeTargetController' => $baseDir . '/php/shell/sp/negativeTarget/SpEnabledNegativeTargetController.php',
    'SpPausedNegativeTargetController' => $baseDir . '/php/shell/sp/negativeTarget/SpPausedNegativeTargetController.php',

    // php/shell/sp/portfolios/
    'CheckPortfolioStateController' => $baseDir . '/php/shell/sp/portfolios/CheckPortfolioStateController.php',

    // php/shell/sp/product/
    'SpPausedProductController' => $baseDir . '/php/shell/sp/product/SpPausedProductController.php',

    // php/shell/sp/seller/
    'SpInitSellerController' => $baseDir . '/php/shell/sp/seller/SpInitSellerController.php',

    // php/shell/sp/sync/
    'MigrationSpDataController' => $baseDir . '/php/shell/sp/sync/MigrationSpDataController.php',

    // php/shell/sp/target/
    'SpCreateTargetController' => $baseDir . '/php/shell/sp/target/SpCreateTargetController.php',
    'SpPausedTargetController' => $baseDir . '/php/shell/sp/target/SpPausedTargetController.php',
    'SpUpdateTargetBidController' => $baseDir . '/php/shell/sp/target/SpUpdateTargetBidController.php',
);
