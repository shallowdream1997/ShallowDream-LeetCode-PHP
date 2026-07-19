<?php

require_once dirname(__FILE__) . '/../../../../php/bootstrap.php';

/**
 * 广告回迁文件校验与提交控制器
 * 校验 xlsx 文件中 sellerId 和 campaignId 列的存在性及非空性
 * 校验通过后调用 platform-sms-support-application 接口提交迁移任务
 * Class MigrationSpDataController
 */
class MigrationSpDataController
{
    /** 必填列名 */
    const REQUIRED_COLUMNS = ['sellerId', 'campaignId'];

    /** excel 文件目录 */
    private $excelDir;

    /** 环境：test / uat / pro */
    private $environment;

    /** @var MyLogger */
    private $log;

    /** @var CurlService */
    private $curlService;

    /**
     * @param string $environment 环境：test / uat / pro，默认 test
     */
    public function __construct($environment = 'test')
    {
        $this->environment = $environment;
        $this->excelDir = dirname(__FILE__) . '/excel/';
        $this->log = new MyLogger("migration_sp_data");

        // 初始化 CurlService
        $this->curlService = new CurlService();
        $this->curlService->setEnvironment($environment)->smsSupport();

        $this->log("MigrationSpDataController 初始化完成，环境：{$environment}");
    }

    /**
     * 记录日志
     * @param string $message
     */
    private function log($message = "")
    {
        $this->log->log("[" . date("Y-m-d H:i:s") . "] {$message}");
    }

    /**
     * 获取 excel 目录下所有 xlsx 文件
     * @param bool $fullPath 是否返回完整路径
     * @return array
     */
    public function getXlsxFiles($fullPath = true)
    {
        $files = [];
        if (!is_dir($this->excelDir)) {
            $this->log("excel 目录不存在：{$this->excelDir}");
            return $files;
        }

        $iterator = new DirectoryIterator($this->excelDir);
        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isFile()) {
                $ext = strtolower($fileInfo->getExtension());
                if (in_array($ext, ['xlsx', 'xls'])) {
                    $files[] = $fullPath ? $fileInfo->getPathname() : $fileInfo->getFilename();
                }
            }
        }

        sort($files);
        $this->log("找到 " . count($files) . " 个 excel 文件");
        return $files;
    }

    /**
     * 校验 xlsx 文件：检查必填列是否存在且数据不为空
     * @param string $filePath 文件路径
     * @return array ['valid' => true/false, 'errors' => [...]]
     */
    public function validateXlsxFile($filePath)
    {
        $result = [
            'valid' => true,
            'errors' => [],
            'totalRows' => 0,
        ];

        if (!file_exists($filePath)) {
            $result['valid'] = false;
            $result['errors'][] = "文件不存在：{$filePath}";
            return $result;
        }

        $fileName = basename($filePath);

        try {
            $excelUtils = new ExcelUtils();
            $excelData = $excelUtils->_readXlsFileV2($filePath);

            if (empty($excelData)) {
                $result['valid'] = false;
                $result['errors'][] = "[{$fileName}] 无法读取 excel 文件内容或文件为空";
                return $result;
            }

            // 取第一个工作表的数据
            $sheetData = reset($excelData);
            if (empty($sheetData)) {
                $result['valid'] = false;
                $result['errors'][] = "[{$fileName}] excel 文件中没有数据";
                return $result;
            }

            $totalRows = count($sheetData);
            $result['totalRows'] = $totalRows;

            // 获取表头（第一行数据的 keys）
            $headers = array_keys($sheetData[0]);

            // 检查必填列是否存在
            $missingColumns = [];
            foreach (self::REQUIRED_COLUMNS as $column) {
                if (!in_array($column, $headers)) {
                    $missingColumns[] = $column;
                }
            }

            if (!empty($missingColumns)) {
                $result['valid'] = false;
                $result['errors'][] = "[{$fileName}] 缺少必填列：" . implode('、', $missingColumns);
                return $result;
            }

            // 遍历每一行检查必填字段是否为空
            $emptyRows = [];
            foreach ($sheetData as $rowIndex => $row) {
                $excelRowNum = $rowIndex + 2; // excel 行号从第2行开始（第1行是表头）
                $emptyFields = [];

                foreach (self::REQUIRED_COLUMNS as $column) {
                    // 检查字段是否为空（null、空字符串、仅空白字符）
                    $value = isset($row[$column]) ? trim($row[$column]) : '';
                    if ($value === '' || $value === null) {
                        $emptyFields[] = $column;
                    }
                }

                if (!empty($emptyFields)) {
                    $emptyRows[] = "第 {$excelRowNum} 行缺少字段：" . implode('、', $emptyFields);
                }
            }

            if (!empty($emptyRows)) {
                $result['valid'] = false;
                $result['errors'][] = "[{$fileName}] 共 {$totalRows} 行数据，存在 " . count($emptyRows) . " 行数据不完整：";
                $result['errors'] = array_merge($result['errors'], $emptyRows);
                return $result;
            }

            $this->log("[{$fileName}] 校验通过，共 {$totalRows} 行数据，所有必填字段均不为空");
            return $result;

        } catch (Exception $e) {
            $result['valid'] = false;
            $result['errors'][] = "[{$fileName}] 读取 excel 文件异常：" . $e->getMessage();
            return $result;
        }
    }

    /**
     * 调用接口提交回迁任务
     * @param string $filePath 文件路径
     * @param string $dingTalkUser 钉钉通知人
     * @param string $revisedDate 是否请求 amazon 接口获取数据（true/false）
     * @return array
     */
    public function submitMigration($filePath, $dingTalkUser = '', $revisedDate = 'false')
    {
        $fileName = basename($filePath);

        // 构建 CURLFile
        $mimeType = mime_content_type($filePath);
        if (function_exists('curl_file_create')) {
            $file = curl_file_create($filePath, $mimeType, $fileName);
        } else {
            $file = "@{$filePath}";
        }

        // 构建参数
        $params = [
            'file' => $file,
            'dingTalkUser' => $dingTalkUser,
            'revisedDate' => $revisedDate,
        ];

        $this->log("提交回迁任务：file={$fileName}, dingTalkUser={$dingTalkUser}, revisedDate={$revisedDate}");

        try {
            $response = $this->curlService->getWayFormDataPost('/campaign/v1/migrationSpData', $params);

            $httpCode = isset($response['httpCode']) ? $response['httpCode'] : 0;
            $result = isset($response['result']) ? $response['result'] : [];

            $this->log("回迁任务提交结果：httpCode={$httpCode}, result=" . json_encode($result, JSON_UNESCAPED_UNICODE));

            if ($httpCode >= 200 && $httpCode < 300 && isset($result['taskId'])) {
                return [
                    'success' => true,
                    'taskId' => $result['taskId'],
                    'message' => "回迁任务提交成功，taskId：{$result['taskId']}",
                    'data' => $result,
                ];
            }

            $errorMsg = isset($result['message']) ? $result['message'] : (isset($result['msg']) ? $result['msg'] : json_encode($result, JSON_UNESCAPED_UNICODE));
            return [
                'success' => false,
                'taskId' => '',
                'message' => "接口响应异常（{$httpCode}）：{$errorMsg}",
                'data' => $result,
            ];

        } catch (Exception $e) {
            $this->log("提交回迁任务异常：" . $e->getMessage());
            return [
                'success' => false,
                'taskId' => '',
                'message' => "提交异常：" . $e->getMessage(),
                'data' => [],
            ];
        }
    }

    /**
     * 主处理流程：收集文件 -> 校验全部 -> 全部通过后提交
     * @param string $dingTalkUser 钉钉通知人
     * @param string $revisedDate 是否请求 amazon 接口（true/false），默认 false
     * @return array
     */
    public function processMigration($dingTalkUser = '', $revisedDate = 'false')
    {
        $this->log("========== 开始广告回迁任务处理 ==========");
        $this->log("参数：dingTalkUser={$dingTalkUser}, revisedDate={$revisedDate}");

        // 步骤1：获取所有 xlsx 文件
        $files = $this->getXlsxFiles(true);
        if (empty($files)) {
            $this->log("excel 目录下没有找到 xlsx 文件：{$this->excelDir}");
            return [
                'success' => false,
                'message' => "excel 目录下没有找到 xlsx 文件",
                'results' => [],
            ];
        }

        // 步骤2：逐一校验文件
        $validFiles = [];
        $invalidFiles = [];

        foreach ($files as $filePath) {
            $validation = $this->validateXlsxFile($filePath);
            if ($validation['valid']) {
                $validFiles[] = [
                    'filePath' => $filePath,
                    'fileName' => basename($filePath),
                    'totalRows' => $validation['totalRows'],
                ];
                $this->log("文件 [{$validation['totalRows']}行] " . basename($filePath) . " 校验通过");
            } else {
                $invalidFiles[] = [
                    'filePath' => $filePath,
                    'fileName' => basename($filePath),
                    'errors' => $validation['errors'],
                ];
                foreach ($validation['errors'] as $error) {
                    $this->log("校验失败：" . $error);
                }
            }
        }

        // 如果有任何文件校验失败，中止流程
        if (!empty($invalidFiles)) {
            $failCount = count($invalidFiles);
            $errorDetail = [];
            foreach ($invalidFiles as $invalid) {
                $errorDetail[] = "{$invalid['fileName']}:\n  - " . implode("\n  - ", $invalid['errors']);
            }

            $this->log("==== 校验结果：{$failCount} 个文件校验失败，中止提交 ====");

            return [
                'success' => false,
                'message' => "有 {$failCount} 个文件校验未通过，请修正后重试",
                'details' => $errorDetail,
                'validFiles' => $validFiles,
                'invalidFiles' => $invalidFiles,
            ];
        }

        // 步骤3：校验全部通过，逐一提交
        $this->log("==== 全部文件校验通过，准备提交 " . count($validFiles) . " 个文件 ====");
        $submitResults = [];

        foreach ($validFiles as $fileInfo) {
            $this->log("正在提交：{$fileInfo['fileName']}（{$fileInfo['totalRows']} 行数据）");
            $result = $this->submitMigration(
                $fileInfo['filePath'],
                $dingTalkUser,
                $revisedDate
            );

            $submitResults[] = [
                'fileName' => $fileInfo['fileName'],
                'totalRows' => $fileInfo['totalRows'],
                'success' => $result['success'],
                'taskId' => $result['taskId'],
                'message' => $result['message'],
            ];

            $this->log($result['message']);
        }

        $successCount = count(array_filter($submitResults, function ($r) { return $r['success']; }));
        $failCount = count($submitResults) - $successCount;

        $this->log("========== 广告回迁任务处理完成 ==========");
        $this->log("提交 " . count($submitResults) . " 个文件，成功 {$successCount} 个，失败 {$failCount} 个");

        return [
            'success' => $failCount === 0,
            'message' => "处理完成：成功 {$successCount} 个，失败 {$failCount} 个",
            'results' => $submitResults,
            'validFiles' => $validFiles,
            'invalidFiles' => $invalidFiles,
        ];
    }
}
