<?php
/**
 * 脚本注册表 API
 *
 * GET /api/scripts       — 列出所有脚本
 * GET /api/scripts?q=关键词 — 搜索脚本
 */

require_once dirname(__FILE__) . '/../bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$registry = buildScriptRegistry();
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

// 搜索过滤
if ($q !== '') {
    $registry = array_filter($registry, function ($path, $name) use ($q) {
        return strpos($name, $q) !== false
            || strpos(basename($path), $q) !== false
            || matchKeywordMap($name, $q);
    }, ARRAY_FILTER_USE_BOTH);
}

// 构建输出
$scripts = [];
foreach ($registry as $name => $path) {
    $parts = explode('.', $name);
    $group = count($parts) > 1 ? $parts[0] : 'other';
    $subGroup = count($parts) > 2 ? $parts[1] : '';

    // 计算相对路径：从项目根目录到脚本文件
    $projectRoot = dirname(__FILE__) . '/../..';
    $relPath = str_replace($projectRoot . '/', '', $path);

    $scripts[] = [
        'name' => $name,
        'file' => $relPath,
        'group' => $group,
        'subGroup' => $subGroup,
        'description' => getScriptDescription($path),
        'params' => getScriptParams($path),
    ];
}

echo json_encode([
    'scripts' => $scripts,
    'total' => count($scripts),
], JSON_UNESCAPED_UNICODE);

/**
 * 构建脚本注册表（与 bin/pa 一致）
 */
function buildScriptRegistry()
{
    $baseDir = dirname(__FILE__) . '/../shell';
    $registry = [];

    // shell/ 下的直接脚本
    foreach (glob($baseDir . '/*.php') as $file) {
        $name = basename($file, '.php');
        $registry[strtolower($name)] = $file;
    }

    // shell/ 下的子目录脚本（1层：ebay/, fix/, gateway/, product/, sync/）
    foreach (glob($baseDir . '/*/*.php') as $file) {
        $group = basename(dirname($file));
        $className = basename($file, '.php');
        $shortName = preg_replace('/Controller$/', '', $className);
        $shortName = preg_replace('/([a-z])([A-Z])/', '$1_$2', $shortName);
        $registry[strtolower($group) . '.' . strtolower($shortName)] = $file;
    }

    // shell/sp/ 下的直接脚本
    foreach (glob($baseDir . '/sp/*.php') as $file) {
        $className = basename($file, '.php');
        $shortName = preg_replace('/Controller$/', '', $className);
        $shortName = preg_replace('/([a-z])([A-Z])/', '$1_$2', $shortName);
        $registry['sp.' . strtolower($shortName)] = $file;
    }

    // shell/sp/ 下的子模块脚本（2层）
    foreach (glob($baseDir . '/sp/*/*.php') as $file) {
        $type = basename(dirname($file));
        $className = basename($file, '.php');
        $shortName = preg_replace('/^Sp|Controller$/', '', $className);
        $shortName = preg_replace('/([a-z])([A-Z])/', '$1_$2', $shortName);
        $registry['sp.' . strtolower($type) . '.' . strtolower($shortName)] = $file;
    }

    ksort($registry);
    return $registry;
}

/**
 * 从脚本文件提取描述（类注释的第一行）
 */
function getScriptDescription($filePath)
{
    static $descMap = [
        'SpPausedKeywordController' => '暂停关键词投放',
        'SpEnabledKeywordController' => '启用关键词投放',
        'SpCreateKeywordController' => '创建关键词',
        'SpUpdateKeywordBidController' => '调整关键词bid',
        'SpPausedTargetController' => '暂停投放目标',
        'SpEnabledTargetController' => '启用投放目标',
        'SpCreateTargetController' => '创建投放目标',
        'SpUpdateTargetBidController' => '调整目标bid',
        'SpUpdateCampaignBudgetController' => '更新campaign预算',
        'SpUpdateCampaignController' => '更新campaign',
        'SpDelRepeatCampaignController' => '删除重复campaign',
        'SpPausedNKeywordAndNTargetByAdGroupController' => '按广告组暂停否定关键词和否定目标',
        'SpEnabledNKeywordAndTargetByAdGroupController' => '按广告组启用否定关键词和目标',
        'SpEnabledCampaignController' => '启用campaign',
        'SpSyncPomsController' => '同步POMS数据',
        'SpArchivedErrorAdGroupController' => '归档错误的广告组',
        'SpPausedProductController' => '暂停产品广告',
        'SpInitSellerController' => '初始化卖家数据',
        'CheckPortfolioStateController' => '检查组合状态',
        'SpCreateNegativeKeywordController' => '创建否定关键词',
        'SpEnabledNegativeKeywordController' => '启用否定关键词',
        'SpPausedNegativeKeywordController' => '暂停否定关键词',
        'SpCreateNegativeTargetController' => '创建否定目标',
        'SpEnabledNegativeTargetController' => '启用否定目标',
        'SpPausedNegativeTargetController' => '暂停否定目标',
        'MigrationSpDataController' => '迁移SP数据',
        'SpController' => 'SP广告投放',
        'SpEnabledController' => '启用SP广告',
        'SpPausedController' => '暂停SP广告',
        'SpPausedAdGroupController' => '暂停广告组',
        'SpRuleController' => 'SP规则管理',
        'SpUpdateAdGroupController' => '更新广告组',
        'SpFindCanNotCreateController' => '查找无法创建的广告',
        'ExportEbaySellerAllocationRecordController' => '导出eBay卖家分配记录',
        'ExecuteEbaySellerAllocationController' => '执行eBay卖家分配',
        'FillSellerAllocationCategoryConfigController' => '填充卖家分配分类配置',
        'ProductSkuController' => '产品SKU操作',
        'OptionConfigController' => '选项配置',
        'GatWayRequestController' => 'Gateway请求',
        'SyncCurlController' => 'Curl同步',
        'SyncCurlV2Controller' => 'Curl同步V2',
        'SyncJob1Controller' => '同步任务1',
        'SyncProductSku' => '同步产品SKU',
        'SyncSkuMaterialToAudit' => '同步SKU资料到审核',
        'SyncAiCategoryRecommand' => '同步AI分类推荐',
        'DelEbayBillRoundController' => '删除eBay账单轮次',
        'FixCeSkuMaterial' => '修复CE SKU资料',
        'FixPaSkuMaterialSpDataController' => '修复PA SKU资料SP数据',
        'FixPmoSkuController' => '修复PMO SKU',
        'FixSkuSupplierQuotePrice' => '修复SKU供应商报价',
        'Calc' => '计算工具',
        'Shell' => 'Shell工具',
        'Sync' => '同步工具',
        'CreatedTarget' => '创建投放目标',
    ];

    $className = basename($filePath, '.php');
    return $descMap[$className] ?? '';
}

/**
 * 从脚本文件提取参数信息
 */
function getScriptParams($filePath)
{
    $content = @file_get_contents($filePath);
    if ($content === false) {
        return [];
    }

    $params = [];

    // 匹配 $argv 参数提取模式
    if (preg_match_all('/\$argv\[(\d+)\]\s*(?:\?\s*:\s*|=\s*)[\'"]?(\w+)[\'"]?/i', $content, $matches)) {
        foreach ($matches[2] as $param) {
            if (!in_array($param, $params)) {
                $params[] = $param;
            }
        }
    }

    // 匹配 $_REQUEST / $_GET / $_POST 参数
    if (preg_match_all('/\$_(?:REQUEST|GET|POST)\[[\'"](\w+)[\'"]\]/i', $content, $matches)) {
        foreach ($matches[1] as $param) {
            if (!in_array($param, $params)) {
                $params[] = $param;
            }
        }
    }

    // 匹配 $params['xxx'] 模式
    if (preg_match_all('/\$params\[[\'"](\w+)[\'"]\]/i', $content, $matches)) {
        foreach ($matches[1] as $param) {
            if (!in_array($param, $params) && !in_array($param, ['_id', 'limit', 'page', 'pageSize'])) {
                $params[] = $param;
            }
        }
    }

    return array_unique($params);
}

/**
 * 关键词映射匹配
 */
function matchKeywordMap($scriptName, $keyword)
{
    static $keywordMap = [
        '暂停' => ['pause', 'paused'],
        '启用' => ['enabled', 'enable'],
        '预算' => ['budget'],
        '关键词' => ['keyword'],
        '投放' => ['campaign', 'controller'],
        '目标' => ['target'],
        '否定' => ['negative'],
        '导出' => ['export'],
        '同步' => ['sync'],
        'ebay' => ['ebay'],
        '卖家' => ['seller'],
        '广告组' => ['adgroup'],
        '组合' => ['portfolio'],
        '创建' => ['create'],
        '删除' => ['del'],
        '修复' => ['fix'],
        '产品' => ['product'],
        '分类' => ['category', 'fill'],
        '迁移' => ['migration'],
        '归档' => ['archived'],
        'bid' => ['bid', 'updatebid'],
        'campaign' => ['campaign'],
        'keyword' => ['keyword'],
        'target' => ['target'],
    ];

    $keyword = strtolower($keyword);
    $scriptName = strtolower($scriptName);

    foreach ($keywordMap as $key => $matches) {
        if ($keyword === $key || strpos($keyword, $key) !== false) {
            foreach ($matches as $m) {
                if (strpos($scriptName, $m) !== false) {
                    return true;
                }
            }
        }
    }

    return false;
}
