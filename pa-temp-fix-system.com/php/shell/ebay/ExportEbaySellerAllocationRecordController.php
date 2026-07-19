<?php

require_once dirname(__FILE__) . '/../../../php/bootstrap.php';
/**
 * 导出 eBay 卖家分配记录 - 全部分页数据
 *
 * 调用接口：sms/ebay/seller/allocation/record/v1/page (gateway POST)
 * 自动分页拉取全部数据，导出为 XLSX 文件
 *
 * 用法：
 *   php ExportEbaySellerAllocationRecordController.php [环境] [pageSize]
 *   环境:     pro | test | uat (默认 pro)
 *   pageSize: 每页数量 (默认 500)
 *
 * 示例：
 *   php ExportEbaySellerAllocationRecordController.php pro 500
 */

// 大数据量导出需要更多内存，设置高上限
if (function_exists('ini_set')) {
    @ini_set('memory_limit', '4096M');
}
if (function_exists('set_time_limit')) {
    @set_time_limit(0);
}


class ExportEbaySellerAllocationRecordController
{
    private MyLogger $log;
    private CurlService $curlService;
    private string $env;

    /**
     * 字段映射表 —— 与前端 pa-biz-web ebay-seller-allocation-record/index.vue 保持一致
     */

    // 来源类型: sourceType → 中文
    private const SOURCE_TYPE_MAP = [
        'seller_assign' => '账号分配',
        'sku_upload' => '产品上架',
    ];

    // 分流槽位: allocationSlot → 中文
    private const ALLOCATION_SLOT_MAP = [
        'SELLER_ASSIGN' => '直发账号分配',
        'LOCAL' => '本土海外仓',
        'GREATER_CHINA' => '大中华海外仓',
    ];

    // 结果类型: resultType → 中文
    private const RESULT_TYPE_MAP = [
        'CREATED' => '本次创建/写入',
        'REUSED_EXISTING' => '复用已有结果',
        'SKIPPED' => '跳过',
        'FAILED' => '失败',
    ];

    // 分流状态: allocationStatus → 中文
    private const ALLOCATION_STATUS_MAP = [
        'SUCCESS' => '成功',
        'FAILED' => '失败',
        'WAIT_STOCK_CHECK' => '等待海外仓检查',
        'FAILED_RETRYABLE' => '可重试失败',
        'SKIPPED' => '跳过',
    ];

    /**
     * 导出列定义 —— 与前端页面 ebay-seller-allocation-record/index.vue 表格列完全一致
     *
     * key: 原始字段名
     * label: 表头文字（与页面列名一致）
     * transformMap: 有值时，单元格显示中文翻译（与页面显示逻辑一致），而非原始编码
     */
    private const COLUMN_DEFS = [
        // === 页面可见的13列，顺序与前端 <el-table-column> 一致 ===
        ['key' => 'skuId',            'label' => 'SKU'],
        ['key' => 'scuId',            'label' => 'SCU'],
        ['key' => 'channel',          'label' => '渠道'],
        ['key' => 'categoryPaths',    'label' => '二级分类路径'],
        ['key' => 'targetSellerId',   'label' => '目标账号(分配账号)'],
        ['key' => 'sourceType',       'label' => '来源类型',    'transformMap' => 'SOURCE_TYPE_MAP'],
        ['key' => 'allocationSlot',   'label' => '分流槽位',    'transformMap' => 'ALLOCATION_SLOT_MAP'],
        ['key' => 'resultType',       'label' => '类型',        'transformMap' => 'RESULT_TYPE_MAP'],
        ['key' => 'scuCreateTime',    'label' => 'SCU创建时间'],
        ['key' => 'allocationTime',   'label' => '分流时间'],
        ['key' => 'failureCode',      'label' => '失败编码'],
        ['key' => 'failureMessage',   'label' => '失败原因'],
        ['key' => 'allocationStatus', 'label' => '状态',        'transformMap' => 'ALLOCATION_STATUS_MAP'],

        // === 补充字段（页面未展示，但对数据分析有用） ===
        ['key' => 'id',               'label' => '记录ID'],
        ['key' => 'allocationStage',  'label' => '分流阶段'],
        ['key' => 'regionType',       'label' => '区域类型'],
        ['key' => 'originalSellerId', 'label' => '原始账号'],
        ['key' => 'secondCategoryId', 'label' => '二级分类ID'],
        ['key' => 'retryCount',       'label' => '重试次数'],
        ['key' => 'actualChanged',    'label' => '是否实际变更'],
        ['key' => 'statisticDate',    'label' => '统计日期'],
        ['key' => 'createTime',       'label' => '创建时间'],
        ['key' => 'updateTime',       'label' => '更新时间'],
        ['key' => 'createBy',         'label' => '创建人'],
        ['key' => 'updateBy',         'label' => '更新人'],
        ['key' => 'remark',           'label' => '备注'],
        ['key' => 'ruleSnapshot',     'label' => '规则快照(JSON)'],
    ];

    public function __construct($env = 'pro')
    {
        $this->env = $env;
        $this->log = new MyLogger("export_ebay_seller_allocation_record");
        // 遵循现有模式: 环境 -> gateway -> getModule
        $this->curlService = (new CurlService())->setEnvironment($env)->gateway()->getModule('pa');
    }

    private function log($message = "")
    {
        $this->log->log2($message);
        echo $message . "\n";
    }

    /**
     * 请求单页数据
     */
    private function fetchPage($pageNum, $pageSize)
    {
        return $this->curlService->getWayPost(
            $this->curlService->module . "/sms/ebay/seller/allocation/record/v1/page",
            [
                "pageNum" => $pageNum,
                "pageSize" => $pageSize,
                "condition" => new stdClass(),
            ]
        );
    }

    /**
     * 将单个值转为单元格可写入的字符串
     */
    private function cellValue($value)
    {
        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        } elseif (is_bool($value)) {
            return $value ? 'true' : 'false';
        } elseif ($value === null) {
            return '';
        }
        return (string)$value;
    }

    /**
     * 获取字段的中文翻译
     */
    private function translate($value, $mapName)
    {
        if ($mapName === 'SOURCE_TYPE_MAP') {
            return self::SOURCE_TYPE_MAP[$value] ?? '';
        } elseif ($mapName === 'ALLOCATION_SLOT_MAP') {
            return self::ALLOCATION_SLOT_MAP[$value] ?? '';
        } elseif ($mapName === 'RESULT_TYPE_MAP') {
            return self::RESULT_TYPE_MAP[$value] ?? '';
        } elseif ($mapName === 'ALLOCATION_STATUS_MAP') {
            return self::ALLOCATION_STATUS_MAP[$value] ?? '';
        }
        return '';
    }

    /**
     * PhpSpreadsheet 自带缓存管理，无需手动设置磁盘缓存
     * 保留此方法作为空操作，以兼容已有调用
     */
    private function enablePHPExcelCache()
    {
        // PhpSpreadsheet 自动管理内存，无需手动缓存
    }

    /**
     * 导出到 XLSX
     *
     * - 有 transformMap 的字段：只输出中文翻译（与前端页面显示一致）
     * - 无 transformMap 的字段：输出原始值
     * - 列顺序由 COLUMN_DEFS 决定
     */
    private function exportToXlsx($allData, $filePath)
    {
        if (empty($allData)) {
            return;
        }

        // 1. 构建表头
        $headers = [];
        foreach (self::COLUMN_DEFS as $def) {
            $headers[] = $def['label'];
        }

        // 2. 逐行填充数据
        $exportRows = [];
        foreach ($allData as $row) {
            $exportRow = [];
            foreach (self::COLUMN_DEFS as $def) {
                $key = $def['key'];
                $rawValue = $row[$key] ?? '';

                if (!empty($def['transformMap'])) {
                    // 编码字段 → 只输出中文翻译（与页面显示一致）
                    $translated = $this->translate($rawValue, $def['transformMap']);
                    $exportRow[] = $translated !== '' ? $translated : $rawValue;
                } else {
                    $exportRow[] = $this->cellValue($rawValue);
                }
            }
            $exportRows[] = $exportRow;
        }

        // 3. 标记需要文本格式的列（ID类和时间类）
        $textColumns = [];
        foreach ($headers as $index => $header) {
            $lowerHeader = strtolower($header);
            if (strpos($lowerHeader, 'id') !== false || strpos($lowerHeader, '时间') !== false) {
                $textColumns[] = $index;
            }
        }

        // 4. 使用 ExcelUtils 导出
        $excelUtils = new ExcelUtils();
        return $excelUtils->downloadXlsx($headers, $exportRows, basename($filePath), $textColumns);
    }

    /**
     * 主流程
     */
    public function handle($pageSize = 500)
    {
        $this->log("========== 开始导出 eBay 卖家分配记录 ==========");
        $this->log("环境: {$this->env}, 每页数量: {$pageSize}");

        // 1. 先拉第一页，获取分页信息
        $this->log("[第1步] 请求第1页，获取分页信息...");
        $firstResp = $this->fetchPage(1, $pageSize);

        if (!$firstResp || ($firstResp['httpCode'] ?? 0) !== 200) {
            $this->log("[错误] 请求失败，httpCode: " . ($firstResp['httpCode'] ?? 'N/A'));
            $this->log("原始响应: " . json_encode($firstResp, JSON_UNESCAPED_UNICODE));
            return;
        }

        $resultData = $firstResp['result'] ?? [];
        $state = $resultData['state'] ?? [];
        if (($state['code'] ?? 0) !== 2000000) {
            $this->log("[错误] 接口返回非成功状态: " . json_encode($state, JSON_UNESCAPED_UNICODE));
            return;
        }

        $data = $resultData['data'] ?? [];
        $allData = $data['list'] ?? [];
        $pageInfo = $data['page'] ?? [];
        $totalPage = (int)($pageInfo['totalPage'] ?? 0);
        $totalSize = (int)($pageInfo['totalSize'] ?? 0);

        $this->log("总记录数: {$totalSize}, 总页数: {$totalPage}, 第1页获取: " . count($allData) . " 条");

        // 2. 循环拉取剩余页
        if ($totalPage > 1) {
            $this->log("[第2步] 开始拉取第2~{$totalPage}页...");

            for ($pageNum = 2; $pageNum <= $totalPage; $pageNum++) {
                $this->log("  正在拉取第 {$pageNum}/{$totalPage} 页...");
                $resp = $this->fetchPage($pageNum, $pageSize);

                if (!$resp || ($resp['httpCode'] ?? 0) !== 200) {
                    $this->log("  [警告] 第 {$pageNum} 页请求失败，跳过");
                    continue;
                }

                $pageResultData = $resp['result'] ?? [];
                $pageState = $pageResultData['state'] ?? [];
                if (($pageState['code'] ?? 0) !== 2000000) {
                    $this->log("  [警告] 第 {$pageNum} 页返回非成功状态，跳过");
                    continue;
                }

                $pageData = $pageResultData['data'] ?? [];
                $pageList = $pageData['list'] ?? [];

                if (empty($pageList)) {
                    $this->log("  [警告] 第 {$pageNum} 页无数据，跳过");
                    continue;
                }

                $allData = array_merge($allData, $pageList);
                $this->log("  第 {$pageNum} 页获取 " . count($pageList) . " 条，累计: " . count($allData) . " 条");
            }
        }

        $this->log("");
        $this->log("========== 数据拉取完成 ==========");
        $this->log("总计获取: " . count($allData) . " 条记录");

        if (empty($allData)) {
            $this->log("无数据可导出，退出");
            return;
        }

        // 3. 导出 XLSX
        $this->log("");
        $this->log("[第3步] 导出 XLSX 文件（大数据量，请耐心等待）...");
        $fileName = "eBay卖家分配记录_" . date("YmdHis") . ".xlsx";
        $filePath = dirname(__FILE__) . "/../export/" . $fileName;

        try {
            // 启用磁盘缓存，避免内存溢出
            $this->enablePHPExcelCache();

            $startTime = microtime(true);
            $resultPath = $this->exportToXlsx($allData, $filePath);
            $elapsed = round(microtime(true) - $startTime, 1);

            $fileSize = is_file($resultPath) ? filesize($resultPath) : 0;
            $this->log("导出成功！耗时: {$elapsed}s, 文件大小: " . $this->formatBytes($fileSize));
            $this->log("文件路径: {$resultPath}");
        } catch (Exception $e) {
            $this->log("[错误] XLSX 导出失败: " . $e->getMessage());
            // 兜底：保存为 CSV
            $csvName = str_replace('.xlsx', '.csv', $fileName);
            $csvPath = dirname(__FILE__) . "/../export/" . $csvName;
            $this->log("尝试导出 CSV 作为兜底...");
            $this->exportToCsvFallback($allData, $csvPath);
            $this->log("CSV 兜底文件: {$csvPath}");
        }

        $this->log("========== 导出完成 ==========");
    }

    /**
     * CSV 兜底导出（列定义与 XLSX 一致，编码字段只显示中文翻译）
     */
    private function exportToCsvFallback($allData, $filePath)
    {
        $headers = [];
        foreach (self::COLUMN_DEFS as $def) {
            $headers[] = $def['label'];
        }

        $fp = fopen($filePath, 'w');
        if ($fp === false) {
            throw new Exception("无法创建文件: {$filePath}");
        }

        fwrite($fp, "\xEF\xBB\xBF"); // BOM
        fputcsv($fp, $headers);

        foreach ($allData as $row) {
            $csvRow = [];
            foreach (self::COLUMN_DEFS as $def) {
                $key = $def['key'];
                $rawValue = $row[$key] ?? '';

                if (!empty($def['transformMap'])) {
                    $translated = $this->translate($rawValue, $def['transformMap']);
                    $csvRow[] = $translated !== '' ? $translated : $rawValue;
                } else {
                    $csvRow[] = $this->cellValue($rawValue);
                }
            }
            fputcsv($fp, $csvRow);
        }

        fclose($fp);
    }

    private function formatBytes($bytes)
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}

// ========== 脚本入口 ==========

$env = isset($argv[1]) ? $argv[1] : 'pro';
$pageSize = isset($argv[2]) ? (int)$argv[2] : 500;

if ($pageSize <= 0) {
    $pageSize = 500;
}
if ($pageSize > 1000) {
    echo "注意: pageSize 不建议超过 1000，已自动调整为 1000\n";
    $pageSize = 1000;
}

echo "环境: {$env}, 每页数量: {$pageSize}\n";

$controller = new ExportEbaySellerAllocationRecordController($env);
$controller->handle($pageSize);
