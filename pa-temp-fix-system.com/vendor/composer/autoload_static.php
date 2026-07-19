<?php

// autoload_static.php — 手动更新，添加项目类 classmap

namespace Composer\Autoload;

class ComposerStaticInitfc7abbc31951ae212dbf4e95e280e335
{
    public static $files = array (
        '0e6d7bf4a5811bfa5cf40c5ccd6fae6a' => __DIR__ . '/..' . '/symfony/polyfill-mbstring/bootstrap.php',
        '2cffec82183ee1cea088009cef9a6fc3' => __DIR__ . '/..' . '/ezyang/htmlpurifier/library/HTMLPurifier.composer.php',
    );

    public static $prefixLengthsPsr4 = array (
        'Z' =>
        array (
            'ZipStream\\' => 10,
        ),
        'S' =>
        array (
            'Symfony\\Polyfill\\Mbstring\\' => 26,
        ),
        'P' =>
        array (
            'Psr\\SimpleCache\\' => 16,
            'Psr\\Http\\Message\\' => 17,
            'PhpOffice\\PhpSpreadsheet\\' => 25,
        ),
        'M' =>
        array (
            'MyCLabs\\Enum\\' => 13,
            'Matrix\\' => 7,
        ),
        'C' =>
        array (
            'Composer\\Pcre\\' => 14,
            'Complex\\' => 8,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'ZipStream\\' =>
        array (
            0 => __DIR__ . '/..' . '/maennchen/zipstream-php/src',
        ),
        'Symfony\\Polyfill\\Mbstring\\' =>
        array (
            0 => __DIR__ . '/..' . '/symfony/polyfill-mbstring',
        ),
        'Psr\\SimpleCache\\' =>
        array (
            0 => __DIR__ . '/..' . '/psr/simple-cache/src',
        ),
        'Psr\\Http\\Message\\' =>
        array (
            0 => __DIR__ . '/..' . '/psr/http-message/src',
        ),
        'PhpOffice\\PhpSpreadsheet\\' =>
        array (
            0 => __DIR__ . '/..' . '/phpoffice/phpspreadsheet/src/PhpSpreadsheet',
        ),
        'MyCLabs\\Enum\\' =>
        array (
            0 => __DIR__ . '/..' . '/myclabs/php-enum/src',
        ),
        'Matrix\\' =>
        array (
            0 => __DIR__ . '/..' . '/markbaker/matrix/classes/src',
        ),
        'Composer\\Pcre\\' =>
        array (
            0 => __DIR__ . '/..' . '/composer/pcre/src',
        ),
        'Complex\\' =>
        array (
            0 => __DIR__ . '/..' . '/markbaker/complex/classes/src',
        ),
    );

    public static $prefixesPsr0 = array (
        'H' =>
        array (
            'HTMLPurifier' =>
            array (
                0 => __DIR__ . '/..' . '/ezyang/htmlpurifier/library',
            ),
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'Stringable' => __DIR__ . '/..' . '/myclabs/php-enum/stubs/Stringable.php',

        // php/core/ - 核心服务
        'MyLogger' => __DIR__ . '/../..' . '/php/core/Logger.php',
        'CurlService' => __DIR__ . '/../..' . '/php/core/CurlService.php',
        'RedisService' => __DIR__ . '/../..' . '/php/core/RedisService.php',
        'ExcelUtils' => __DIR__ . '/../..' . '/php/core/ExcelUtils.php',
        'MysqlService' => __DIR__ . '/../..' . '/php/core/MysqlService.php',
        'CrudService' => __DIR__ . '/../..' . '/php/core/CrudService.php',

        // php/utils/ - 工具类
        'DataUtils' => __DIR__ . '/../..' . '/php/utils/DataUtils.php',
        'ProductUtils' => __DIR__ . '/../..' . '/php/utils/ProductUtils.php',

        // php/service/ - 业务服务
        'RequestUtils' => __DIR__ . '/../..' . '/php/service/RequestUtils.php',
        'SpApi' => __DIR__ . '/../..' . '/php/service/SpApi.php',

        // php/shell/ - 顶层脚本
        'Calc' => __DIR__ . '/../..' . '/php/shell/Calc.php',

        // php/shell/ebay/
        'DelEbayBillRoundController' => __DIR__ . '/../..' . '/php/shell/ebay/DelEbayBillRoundController.php',
        'ExecuteEbaySellerAllocationController' => __DIR__ . '/../..' . '/php/shell/ebay/ExecuteEbaySellerAllocationController.php',
        'ExportEbaySellerAllocationRecordController' => __DIR__ . '/../..' . '/php/shell/ebay/ExportEbaySellerAllocationRecordController.php',
        'FillSellerAllocationCategoryConfigController' => __DIR__ . '/../..' . '/php/shell/ebay/FillSellerAllocationCategoryConfigController.php',

        // php/shell/fix/
        'FixCeSkuMaterial' => __DIR__ . '/../..' . '/php/shell/fix/FixCeSkuMaterial.php',
        'FixPaSkuMaterialSpDataController' => __DIR__ . '/../..' . '/php/shell/fix/FixPaSkuMaterialSpDataController.php',
        'FixPmoSkuController' => __DIR__ . '/../..' . '/php/shell/fix/FixPmoSkuController.php',
        'FixSkuSupplierQuotePrice' => __DIR__ . '/../..' . '/php/shell/fix/FixSkuSupplierQuotePrice.php',

        // php/shell/gateway/
        'GatWayRequestController' => __DIR__ . '/../..' . '/php/shell/gateway/GatWayRequestController.php',

        // php/shell/product/
        'ProductSkuController' => __DIR__ . '/../..' . '/php/shell/product/ProductSkuController.php',

        // php/shell/sync/ - 同步脚本（含从 SyncCurlController 拆分）
        'AdSync' => __DIR__ . '/../..' . '/php/shell/sync/AdSync.php',
        'CeMaterialSync' => __DIR__ . '/../..' . '/php/shell/sync/CeMaterialSync.php',
        'ConfigSync' => __DIR__ . '/../..' . '/php/shell/sync/ConfigSync.php',
        'DataExport' => __DIR__ . '/../..' . '/php/shell/sync/DataExport.php',
        'PmoSync' => __DIR__ . '/../..' . '/php/shell/sync/PmoSync.php',
        'ProductSync' => __DIR__ . '/../..' . '/php/shell/sync/ProductSync.php',
        'SguSync' => __DIR__ . '/../..' . '/php/shell/sync/SguSync.php',
        'SkuFix' => __DIR__ . '/../..' . '/php/shell/sync/SkuFix.php',
        'SkuMaterialSync' => __DIR__ . '/../..' . '/php/shell/sync/SkuMaterialSync.php',
        'Sync' => __DIR__ . '/../..' . '/php/shell/sync/Sync.php',
        'SyncAiCategoryRecommand' => __DIR__ . '/../..' . '/php/shell/sync/SyncAiCategoryRecommand.php',
        'SyncJob1Controller' => __DIR__ . '/../..' . '/php/shell/sync/SyncJob1Controller.php',
        'SyncProductSku' => __DIR__ . '/../..' . '/php/shell/sync/SyncProductSku.php',
        'SyncSkuMaterialToAudit' => __DIR__ . '/../..' . '/php/shell/sync/SyncSkuMaterialToAudit.php',

        // php/shell/sp/ - SP 广告脚本（顶层）
        'SpController' => __DIR__ . '/../..' . '/php/shell/sp/SpController.php',
        'SpEnabledController' => __DIR__ . '/../..' . '/php/shell/sp/SpEnabledController.php',
        'SpFindCanNotCreateController' => __DIR__ . '/../..' . '/php/shell/sp/SpFindCanNotCreateController.php',
        'SpPausedController' => __DIR__ . '/../..' . '/php/shell/sp/SpPausedController.php',
        'SpRuleController' => __DIR__ . '/../..' . '/php/shell/sp/SpRuleController.php',

        // php/shell/sp/adgroup/
        'SpArchivedErrorAdGroupController' => __DIR__ . '/../..' . '/php/shell/sp/adgroup/SpArchivedErrorAdGroupController.php',
        'SpPausedAdGroupController' => __DIR__ . '/../..' . '/php/shell/sp/adgroup/SpPausedAdGroupController.php',
        'SpUpdateAdGroupController' => __DIR__ . '/../..' . '/php/shell/sp/adgroup/SpUpdateAdGroupController.php',

        // php/shell/sp/campaign/
        'SpDelRepeatCampaignController' => __DIR__ . '/../..' . '/php/shell/sp/campaign/SpDelRepeatCampaignController.php',
        'SpUpdateCampaignBudgetController' => __DIR__ . '/../..' . '/php/shell/sp/campaign/SpUpdateCampaignBudgetController.php',
        'SpUpdateCampaignController' => __DIR__ . '/../..' . '/php/shell/sp/campaign/SpUpdateCampaignController.php',

        // php/shell/sp/common/
        'SpEnabledCampaignController' => __DIR__ . '/../..' . '/php/shell/sp/common/SpEnabledCampaignController.php',
        'SpEnabledNKeywordAndTargetByAdGroupController' => __DIR__ . '/../..' . '/php/shell/sp/common/SpEnabledNKeywordAndTargetByAdGroupController.php',
        'SpPausedNKeywordAndNTargetByAdGroupController' => __DIR__ . '/../..' . '/php/shell/sp/common/SpPausedNKeywordAndNTargetByAdGroupController.php',
        'SpSyncPomsController' => __DIR__ . '/../..' . '/php/shell/sp/common/SpSyncPomsController.php',

        // php/shell/sp/keyword/
        'SpCreateKeywordController' => __DIR__ . '/../..' . '/php/shell/sp/keyword/SpCreateKeywordController.php',
        'SpEnabledKeywordController' => __DIR__ . '/../..' . '/php/shell/sp/keyword/SpEnabledKeywordController.php',
        'SpPausedKeywordController' => __DIR__ . '/../..' . '/php/shell/sp/keyword/SpPausedKeywordController.php',
        'SpUpdateKeywordBidController' => __DIR__ . '/../..' . '/php/shell/sp/keyword/SpUpdateKeywordBidController.php',

        // php/shell/sp/negativeKeyword/
        'SpCreateNegativeKeywordController' => __DIR__ . '/../..' . '/php/shell/sp/negativeKeyword/SpCreateNegativeKeywordController.php',
        'SpEnabledNegativeKeywordController' => __DIR__ . '/../..' . '/php/shell/sp/negativeKeyword/SpEnabledNegativeKeywordController.php',
        'SpPausedNegativeKeywordController' => __DIR__ . '/../..' . '/php/shell/sp/negativeKeyword/SpPausedNegativeKeywordController.php',

        // php/shell/sp/negativeTarget/
        'SpCreateNegativeTargetController' => __DIR__ . '/../..' . '/php/shell/sp/negativeTarget/SpCreateNegativeTargetController.php',
        'SpEnabledNegativeTargetController' => __DIR__ . '/../..' . '/php/shell/sp/negativeTarget/SpEnabledNegativeTargetController.php',
        'SpPausedNegativeTargetController' => __DIR__ . '/../..' . '/php/shell/sp/negativeTarget/SpPausedNegativeTargetController.php',

        // php/shell/sp/portfolios/
        'CheckPortfolioStateController' => __DIR__ . '/../..' . '/php/shell/sp/portfolios/CheckPortfolioStateController.php',

        // php/shell/sp/product/
        'SpPausedProductController' => __DIR__ . '/../..' . '/php/shell/sp/product/SpPausedProductController.php',

        // php/shell/sp/seller/
        'SpInitSellerController' => __DIR__ . '/../..' . '/php/shell/sp/seller/SpInitSellerController.php',

        // php/shell/sp/sync/
        'MigrationSpDataController' => __DIR__ . '/../..' . '/php/shell/sp/sync/MigrationSpDataController.php',

        // php/shell/sp/target/
        'SpCreateTargetController' => __DIR__ . '/../..' . '/php/shell/sp/target/SpCreateTargetController.php',
        'SpPausedTargetController' => __DIR__ . '/../..' . '/php/shell/sp/target/SpPausedTargetController.php',
        'SpUpdateTargetBidController' => __DIR__ . '/../..' . '/php/shell/sp/target/SpUpdateTargetBidController.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitfc7abbc31951ae212dbf4e95e280e335::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitfc7abbc31951ae212dbf4e95e280e335::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInitfc7abbc31951ae212dbf4e95e280e335::$prefixesPsr0;
            $loader->classMap = ComposerStaticInitfc7abbc31951ae212dbf4e95e280e335::$classMap;

        }, null, ClassLoader::class);
    }
}
