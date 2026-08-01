<?php
require_once(dirname(__FILE__) . "/../../../php/requiredfile/requiredfile.php");
require_once(dirname(__FILE__) . "/../../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../../php/utils/ExcelUtils.php");

class CheckQdCsvController
{
    private $log;

    public function __construct()
    {
        $this->log = new MyLogger("check_qd_csv");
    }

    private function log(string $string = "")
    {
        $this->log->log2($string);
    }

    /**
     * 读取CSV中的QD单和文件链接，下载文件并校验内容是否正常，异常的重新上传OSS生成新链接
     * CSV格式: qd_bill_no_a,oss_qd_file_url,qd_bill_no_b,ftp_file_url
     * 用法: php CheckQdCsvController.php -file qd.csv -env pro
     *       php CheckQdCsvController.php  (默认读取excel/qd.csv，pro环境)
     * @param string $file CSV文件名(在excel/目录下)
     * @param string $env 环境(test/pro/uat)，默认pro
     */
    public function checkQdCsvFiles($file = "qd.csv", $env = "pro")
    {
        $this->log("checkQdCsvFiles 开始处理 file:{$file} env:{$env}");

        $csvPath = __DIR__ . "/excel/{$file}";
        if (!file_exists($csvPath)) {
            $this->log("❌ CSV文件不存在: {$csvPath}");
            return;
        }

        // 1. 读取CSV
        $csvData = $this->readCsv($csvPath);
        $this->log("读取到 " . count($csvData) . " 条QD单数据");

        if (count($csvData) <= 0) {
            $this->log("CSV无数据");
            return;
        }

        // 2. 创建下载目录（与excel同级）
        $saveDir = __DIR__ . "/excel/qd_files/";
        if (!is_dir($saveDir)) {
            mkdir($saveDir, 0755, true);
            $this->log("创建目录: {$saveDir}");
        }

        // 3. 逐条下载OSS文件并校验
        $excelUtils = new ExcelUtils();
        $ftpBaseUrl = "https://ali-productimages.ux168.com/ux168/hg/";
        $normalCount = 0;
        $abnormalCount = 0;
        $downloadFailCount = 0;
        $emptyCount = 0;
        $noUrlCount = 0;
        $exportList = [];
        $abnormalItems = []; // 异常的QD单，后续需要从ftp下载并重新上传oss

        foreach ($csvData as $index => $item) {
            $qdBillNo = $item['qd_bill_no_a'] ?? ('unknown_' . $index);
            $ossFileUrl = $item['oss_qd_file_url'] ?? '';
            $ftpFilePath = $item['ftp_file_url'] ?? '';

            if (empty($ossFileUrl)) {
                $noUrlCount++;
                $this->log("⚠️ {$qdBillNo} 无oss_qd_file_url");
                $exportList[] = [
                    "qd_bill_no" => $qdBillNo,
                    "oss_file_url" => "",
                    "ftp_file_url" => $ftpFilePath,
                    "status" => "无OSS链接",
                    "error" => "oss_qd_file_url为空",
                    "new_oss_url" => "",
                ];
                // 无OSS链接但有ftp路径，也尝试重新上传
                if (!empty($ftpFilePath)) {
                    $abnormalItems[] = ['qdBillNo' => $qdBillNo, 'ftpFilePath' => $ftpFilePath, 'ossFileUrl' => $ossFileUrl];
                }
                continue;
            }

            // 下载OSS文件
            $this->log("📥 [{$index}] 下载OSS {$qdBillNo}: {$ossFileUrl}");

            $fileContent = $this->downloadFile($ossFileUrl);
            if ($fileContent === false) {
                $downloadFailCount++;
                $this->log("❌ {$qdBillNo} OSS下载失败");
                $exportList[] = [
                    "qd_bill_no" => $qdBillNo,
                    "oss_file_url" => $ossFileUrl,
                    "ftp_file_url" => $ftpFilePath,
                    "status" => "下载失败",
                    "error" => "OSS下载返回false",
                    "new_oss_url" => "",
                ];
                if (!empty($ftpFilePath)) {
                    $abnormalItems[] = ['qdBillNo' => $qdBillNo, 'ftpFilePath' => $ftpFilePath, 'ossFileUrl' => $ossFileUrl];
                }
                continue;
            }

            // 保存文件
            $urlPath = parse_url($ossFileUrl, PHP_URL_PATH);
            $fileName = $qdBillNo . "_oss_" . basename($urlPath);
            $savePath = $saveDir . $fileName;
            file_put_contents($savePath, $fileContent);
            $this->log("💾 {$qdBillNo} OSS已保存: {$savePath} (" . strlen($fileContent) . " bytes)");

            // 校验文件内容
            $checkResult = $this->checkXlsxContent($excelUtils, $savePath);

            if ($checkResult['status'] === 'empty') {
                $emptyCount++;
                $this->log("⚠️ {$qdBillNo} OSS文件为空或无法解析");
                $exportList[] = [
                    "qd_bill_no" => $qdBillNo,
                    "oss_file_url" => $ossFileUrl,
                    "ftp_file_url" => $ftpFilePath,
                    "status" => "文件为空",
                    "error" => "xlsx无数据行",
                    "new_oss_url" => "",
                ];
                if (!empty($ftpFilePath)) {
                    $abnormalItems[] = ['qdBillNo' => $qdBillNo, 'ftpFilePath' => $ftpFilePath, 'ossFileUrl' => $ossFileUrl];
                }
            } elseif ($checkResult['status'] === 'abnormal') {
                $abnormalCount++;
                $reason = $checkResult['reason'] ?? '内容异常';
                $tagsStr = implode(", ", $checkResult['htmlTags']);
                $this->log("❌ {$qdBillNo} OSS内容异常: {$reason}" . ($tagsStr ? " (HTML:{$tagsStr})" : ""));
                $exportList[] = [
                    "qd_bill_no" => $qdBillNo,
                    "oss_file_url" => $ossFileUrl,
                    "ftp_file_url" => $ftpFilePath,
                    "status" => "内容异常",
                    "error" => $reason . ($tagsStr ? " (HTML:{$tagsStr})" : ""),
                    "new_oss_url" => "",
                ];
                // 异常，需要从ftp重新上传
                if (!empty($ftpFilePath)) {
                    $abnormalItems[] = ['qdBillNo' => $qdBillNo, 'ftpFilePath' => $ftpFilePath, 'ossFileUrl' => $ossFileUrl];
                }
            } else {
                $normalCount++;
                $this->log("✅ {$qdBillNo} OSS正常 ({$checkResult['rowCount']}行数据)");
            }
        }

        // 4. 输出OSS校验汇总
        $this->log("========== OSS校验汇总 ==========");
        $this->log("总数据数: " . count($csvData));
        $this->log("✅ 正常: {$normalCount}");
        $this->log("❌ 内容异常: {$abnormalCount}");
        $this->log("⚠️ 文件为空/损坏: {$emptyCount}");
        $this->log("⚠️ 下载失败: {$downloadFailCount}");
        $this->log("⚠️ 无OSS链接: {$noUrlCount}");
        $this->log("需重新上传OSS: " . count($abnormalItems) . " 个");

        // 5. 对异常的QD单，从ftp下载文件并重新上传OSS
        if (count($abnormalItems) > 0) {
            $this->reuploadFromFtp($env, $abnormalItems, $saveDir, $exportList);
        }

        // 6. 导出结果到excel同级目录
        if (count($exportList) > 0) {
            $exportFileName = "QD文件校验及修复_" . date("YmdHis") . ".xlsx";
            $exportPath = __DIR__ . "/excel/" . $exportFileName;
            $this->writeXlsx($exportPath, [
                "qd_bill_no",
                "oss_file_url",
                "ftp_file_url",
                "status",
                "error",
                "new_oss_url",
            ], $exportList);
            $this->log("结果已导出: {$exportPath}");
        } else {
            $this->log("所有QD文件校验通过，无异常数据");
        }

        $this->log("checkQdCsvFiles file:{$file} env:{$env} 处理完毕");
    }

    /**
     * 从ftp下载文件并重新上传OSS，生成新链接
     * @param string $env 环境
     * @param array $abnormalItems 异常QD单列表 [['qdBillNo' => ..., 'ftpFilePath' => ..., 'ossFileUrl' => ...]]
     * @param string $saveDir 保存目录
     * @param array &$exportList 导出列表引用
     */
    private function reuploadFromFtp($env, $abnormalItems, $saveDir, &$exportList)
    {
        $this->log("========== 开始从FTP重新上传OSS ==========");
        $ftpBaseUrl = "https://ali-productimages.ux168.com/ux168/hg/";
        $curlService = (new CurlService())->$env();

        $reuploadSuccess = 0;
        $reuploadFail = 0;

        foreach ($abnormalItems as $item) {
            $qdBillNo = $item['qdBillNo'];
            $ftpFilePath = $item['ftpFilePath'];
            $oldOssUrl = $item['ossFileUrl'];

            $ftpFullUrl = $ftpBaseUrl . $ftpFilePath;
            $this->log("📥 从FTP下载 {$qdBillNo}: {$ftpFullUrl}");

            // 下载ftp文件
            $fileContent = $this->downloadFile($ftpFullUrl);
            if ($fileContent === false) {
                $reuploadFail++;
                $this->log("❌ {$qdBillNo} FTP下载失败");
                // 更新exportList中对应的new_oss_url
                $this->updateExportListNewUrl($exportList, $qdBillNo, "", "FTP下载失败");
                continue;
            }

            // 保存ftp文件
            $savePath = $saveDir . $qdBillNo . "_ftp_" . basename($ftpFilePath);
            file_put_contents($savePath, $fileContent);
            $this->log("💾 {$qdBillNo} FTP已保存: {$savePath} (" . strlen($fileContent) . " bytes)");

            // 上传到OSS
            // OSS路径格式: pa/purchase/qd{datePart}/{qdBillNo}.xlsx
            // 从ftpFilePath提取datePart，如 202606/QD202607220007.xlsx -> datePart=202607
            $datePart = '';
            if (preg_match('#^(\d{6})/#', $ftpFilePath, $m)) {
                $datePart = $m[1]; // 如 202606
            }
            $ossKey = "pa/purchase/qd{$datePart}/{$qdBillNo}.xlsx";

            $newOssUrl = $this->uploadToOss($curlService, $savePath, $ossKey, $qdBillNo . ".xlsx");

            if (!empty($newOssUrl)) {
                $reuploadSuccess++;
                $this->log("✅ {$qdBillNo} 重新上传OSS成功: {$newOssUrl}");
                $this->updateExportListNewUrl($exportList, $qdBillNo, $newOssUrl, "");
            } else {
                $reuploadFail++;
                $this->log("❌ {$qdBillNo} 重新上传OSS失败");
                $this->updateExportListNewUrl($exportList, $qdBillNo, "", "上传OSS失败");
            }
        }

        $this->log("========== 重新上传OSS汇总 ==========");
        $this->log("成功: {$reuploadSuccess}");
        $this->log("失败: {$reuploadFail}");
    }

    /**
     * 上传文件到OSS并返回签名链接
     * @param CurlService $curlService
     * @param string $filePath 本地文件路径
     * @param string $ossKey OSS对象key，如 pa/purchase/qd202606/QD202607220007.xlsx
     * @param string $fileName 文件名
     * @return string 新的OSS签名链接，失败返回空字符串
     */
    private function uploadToOss($curlService, $filePath, $ossKey, $fileName)
    {
        // 1. 获取上传签名
        $resp = DataUtils::getNewResultData($curlService->gateway()->getModule('configmgmt')->getWayPost(
            $curlService->module . "/message/template/v1/getUploadFileSignature", []
        ));

        if (!$resp || !isset($resp['url'])) {
            $this->log("获取OSS上传签名失败");
            return '';
        }

        // 2. 上传文件到OSS
        $curlService->setHeader([
            'request-trace-id: product_operation_client_' . date("Ymd_His") . '_' . rand(100000, 999999),
            'request-trace-level: 1',
            'Content-Type: multipart/form-data',
        ], false);

        $mimeType = function_exists('mime_content_type') ? mime_content_type($filePath) : '';
        if (!$mimeType) {
            $mimeType = 'application/octet-stream';
        }
        $cfile = new CURLFile($filePath, $mimeType, $fileName);

        $uploadResp = $curlService->upload($resp['url'], "", [
            "OSSAccessKeyId" => $resp['ossAccessKeyId'],
            "policy" => $resp['policy'],
            "Signature" => $resp['signature'],
            "expiresTime" => $resp['expiresTime'],
            "key" => $ossKey,
            "success_action_status" => 200,
            "file" => $cfile,
        ]);

        if (!$uploadResp || $uploadResp['httpCode'] !== 200) {
            $this->log("上传文件到OSS失败, httpCode: " . ($uploadResp['httpCode'] ?? 'unknown'));
            return '';
        }

        // 3. 获取签名链接
        $keyResp = DataUtils::getNewResultData($curlService->gateway()->getModule('configmgmt')->getWayGet(
            $curlService->module . "/message/template/v1/getOssUrlByKey", ["key" => $ossKey]
        ));

        if ($keyResp && isset($keyResp['value'])) {
            return $keyResp['value'];
        }

        $this->log("获取OSS签名链接失败, key: {$ossKey}");
        return '';
    }

    /**
     * 更新exportList中对应qdBillNo的new_oss_url和error
     */
    private function updateExportListNewUrl(&$exportList, $qdBillNo, $newUrl, $error)
    {
        foreach ($exportList as &$item) {
            if (($item['qd_bill_no'] ?? '') === $qdBillNo) {
                $item['new_oss_url'] = $newUrl;
                if (!empty($error)) {
                    $item['error'] = ($item['error'] ?? '') . "; " . $error;
                }
                break;
            }
        }
    }

    /**
     * 写xlsx文件到指定路径
     * @param string $filePath 输出文件路径
     * @param array $headers 表头
     * @param array $data 数据
     */
    private function writeXlsx($filePath, $headers, $data)
    {
        if (!class_exists('PHPExcel', false)) {
            require_once(dirname(__FILE__) . "/../../../extends/PHPExcel-1.8/Classes/PHPExcel.php");
        }

        $objPHPExcel = new PHPExcel();
        $sheet = $objPHPExcel->setActiveSheetIndex(0);

        // 写表头
        $col = 0;
        foreach ($headers as $header) {
            $sheet->setCellValueByColumnAndRow($col, 1, $header);
            $col++;
        }

        // 写数据
        $row = 2;
        foreach ($data as $item) {
            $col = 0;
            foreach ($headers as $header) {
                $sheet->setCellValueByColumnAndRow($col, $row, $item[$header] ?? '');
                $col++;
            }
            $row++;
        }

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save($filePath);
    }

    /**
     * 读取CSV文件
     * @param string $csvPath CSV文件路径
     * @return array
     */
    private function readCsv($csvPath)
    {
        $result = [];
        $content = file_get_contents($csvPath);

        // 去除BOM头
        $bom = pack('H*', 'EFBBBF');
        $content = preg_replace("/^{$bom}/", '', $content);

        $lines = explode("\n", trim($content));
        if (count($lines) <= 1) {
            return $result;
        }

        $headers = str_getcsv(trim($lines[0]));
        for ($i = 1; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if (empty($line)) continue;
            $values = str_getcsv($line);
            $row = [];
            foreach ($headers as $hi => $header) {
                $row[$header] = isset($values[$hi]) ? $values[$hi] : '';
            }
            if (!empty($row['qd_bill_no_a']) || !empty($row['qd_bill_no_b'])) {
                $result[] = $row;
            }
        }

        return $result;
    }

    /**
     * 下载文件（优先curl扩展，回退file_get_contents）
     */
    private function downloadFile($url)
    {
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            $content = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($httpCode === 200 && $content !== false) {
                return $content;
            }
            return false;
        }
        return @file_get_contents($url);
    }

    /**
     * 校验xlsx文件内容是否正常
     */
    private function checkXlsxContent($excelUtils, $filePath)
    {
        // 先检查文件头：合法xlsx文件是ZIP格式，以PK(0x504B)开头
        $header = '';
        $fp = @fopen($filePath, 'rb');
        if ($fp) {
            $header = fread($fp, 4);
            fclose($fp);
        }
        if (strlen($header) < 2 || substr($header, 0, 2) !== 'PK') {
            $content = @file_get_contents($filePath);
            $htmlTags = [];
            if ($content && preg_match('/<(html|!DOCTYPE|body|script|div|head|meta|link|title|table|img|iframe|form|input|button|style)\b/i', $content)) {
                preg_match_all('/<(html|!DOCTYPE|body|script|div|head|meta|link|title|table|img|iframe|form|input|button|style)\b/i', $content, $allMatches);
                $htmlTags = array_values(array_unique(array_map('strtolower', $allMatches[1])));
            }
            return ['status' => 'abnormal', 'rowCount' => 0, 'htmlTags' => $htmlTags, 'reason' => '非xlsx格式(文件头非PK)'];
        }

        $rowCount = 0;
        $hasHtml = false;
        $htmlTags = [];

        try {
            $excelUtils->eachXlsxRow($filePath, function ($item) use (&$rowCount, &$hasHtml, &$htmlTags) {
                $rowCount++;
                foreach ($item as $value) {
                    $val = (string)$value;
                    if (preg_match('/<(html|!DOCTYPE|body|script|div|head|meta|link|title|table|img|iframe|form|input|button|style)\b/i', $val, $matches)) {
                        $hasHtml = true;
                        $tag = strtolower($matches[1]);
                        if (!in_array($tag, $htmlTags)) {
                            $htmlTags[] = $tag;
                        }
                    }
                }
            });
        } catch (Exception $e) {
            return ['status' => 'abnormal', 'rowCount' => 0, 'htmlTags' => [], 'reason' => 'xlsx解析失败: ' . $e->getMessage()];
        }

        if ($rowCount === 0) {
            return ['status' => 'empty', 'rowCount' => 0, 'htmlTags' => []];
        }

        if ($hasHtml) {
            return ['status' => 'abnormal', 'rowCount' => $rowCount, 'htmlTags' => $htmlTags, 'reason' => '单元格内容包含HTML标签'];
        }

        return ['status' => 'normal', 'rowCount' => $rowCount, 'htmlTags' => []];
    }
}

$parameters = DataUtils::ExplainArgv(@$argv, array());
$params = (count(@$argv) > 1) ? $parameters : $_REQUEST;
$file = "qd.csv";
$env = "pro";
if (isset($params['file']) && trim($params['file']) != '') {
    $file = trim($params['file']);
}
if (isset($params['env']) && trim($params['env']) != '') {
    $env = trim($params['env']);
}

$con = new CheckQdCsvController();
$con->checkQdCsvFiles($file, $env);
