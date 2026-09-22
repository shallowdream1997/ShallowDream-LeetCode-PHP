<?php
require_once(dirname(__FILE__) . "/../../../php/requiredfile/requiredfile.php");
require_once(dirname(__FILE__) . "/../../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../../php/utils/ExcelUtils.php");

/**
 * 修复Ce回写QD单主表
 * 读取xlsx(qdBillNo/CEBillno)：
 * 1. 逐条调用 writeBackPmoCeSkuToPrePurchase 回写CE单到QD单主表（pa-biz-application）
 * 2. 回写成功后调用 createConsignmentCeBillByQdBillNo 创建寄卖CE单（pa-biz-service，同一QD仅一次）
 * 用法: php WriteBackCeToQdController.php -env pro -dry
 *       php WriteBackCeToQdController.php  (默认pro环境，真实执行)
 */
class WriteBackCeToQdController
{
    /**
     * @var string[] 命令行参数
     */
    private array $args;

    private $log;

    public function __construct()
    {
        $this->args = $this->parseArgs();
        $this->log = new MyLogger("write_back_ce_to_qd");
    }

    private function log(string $string = "")
    {
        $this->log->log2($string);
    }

    private function parseArgs(): array
    {
        $args = [];
        // 兼容 -key value 与 --key=value 两种写法
        for ($i = 1; $i < $GLOBALS['argc']; $i++) {
            $arg = $GLOBALS['argv'][$i];
            if (strpos($arg, '--') === 0) {
                $parts = explode('=', substr($arg, 2), 2);
                $args[$parts[0]] = isset($parts[1]) ? $parts[1] : true;
            } elseif (strpos($arg, '-') === 0) {
                $key = ltrim($arg, '-');
                $next = $GLOBALS['argv'][$i + 1] ?? '';
                if ($next !== '' && strpos($next, '-') !== 0) {
                    $args[$key] = $next;
                    $i++;
                } else {
                    $args[$key] = true;
                }
            }
        }
        return $args;
    }

    /**
     * 读取xlsx数据，兼容假xlsx（实际为Excel97/OLE2格式，WPS另存导致）
     */
    private function readXlsxRows($file): array
    {
        // 加载PHPExcel核心（对齐ExcelUtils::ensurePHPExcelLoaded）
        if (!class_exists('PHPExcel', false)) {
            require_once(dirname(__FILE__) . "/../../../extends/PHPExcel-1.8/Classes/PHPExcel.php");
        }

        // 读取OLE2魔数判断真实格式（扩展名.xlsx但实为Excel97二进制）
        $head = file_get_contents($file, false, null, 0, 8);
        if ($head === false || strlen($head) < 8) {
            throw new Exception("无法读取文件: {$file}");
        }
        if (substr($head, 0, 4) === "\xd0\xcf\x11\xe0") {
            // Excel97-2003 二进制格式，用Excel5 Reader读取
            $this->log("文件为Excel97(OLE2)格式，使用Excel5 Reader读取: {$file}");
            $reader = new PHPExcel_Reader_Excel5();
            $reader->setReadDataOnly(true);
            $objPHPExcel = $reader->load($file);
        } else {
            $objPHPExcel = PHPExcel_IOFactory::load($file);
        }

        $rows = [];
        foreach ($objPHPExcel->getSheetNames() as $i => $sheetName) {
            $sheet = $objPHPExcel->getSheet($i);
            $highestColIndex = PHPExcel_Cell::columnIndexFromString($sheet->getHighestColumn());
            $headers = [];
            for ($c = 0; $c < $highestColIndex; $c++) {
                $headers[$c] = trim((string)$sheet->getCellByColumnAndRow($c, 1)->getValue());
            }
            for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
                $data = [];
                foreach ($headers as $c => $name) {
                    if ($name === '') {
                        continue;
                    }
                    $data[$name] = trim((string)$sheet->getCellByColumnAndRow($c, $row)->getValue());
                }
                if (implode('', $data) !== '') {
                    $rows[] = $data;
                }
            }
        }
        return $rows;
    }

    public function main()
    {
        $env = $this->args['env'] ?? 'pro';
        $dry = isset($this->args['dry']);
        $file = $this->args['file'] ?? dirname(__FILE__) . "/../../export/qd/修复Ce回写.xlsx";

        $this->log("start 执行WriteBackCeToQd脚本 env:{$env} dry:" . ($dry ? 'true' : 'false') . " file:{$file}");
        if (!file_exists($file)) {
            $this->log("❌ 文件不存在: {$file}");
            return;
        }

        // 1. 读取xlsx数据，提取qdBillNo/CEBillno（表头兼容大小写）
        $rows = $this->readXlsxRows($file);
        $this->log("读取xlsx完成，共 " . count($rows) . " 行");

        $pairs = [];
        $skipCount = 0;
        foreach ($rows as $index => $info) {
            // 表头兼容: qdBillNo/prePurchaseBillNo、CEBillno/ceBillNo/CEBillNo
            $qdBillNo = $info['qdBillNo'] ?? $info['prePurchaseBillNo'] ?? '';
            $ceBillNo = $info['CEBillno'] ?? $info['ceBillNo'] ?? $info['CEBillNo'] ?? '';
            $qdBillNo = strtoupper(trim($qdBillNo));
            $ceBillNo = strtoupper(trim($ceBillNo));

            if ($qdBillNo === '' || $ceBillNo === '') {
                $skipCount++;
                $this->log("⚠️ 第" . ($index + 2) . "行缺失单号，跳过: " . json_encode($info, JSON_UNESCAPED_UNICODE));
                continue;
            }
            $key = $qdBillNo . '_' . $ceBillNo;
            if (isset($pairs[$key])) {
                $skipCount++;
                continue;
            }
            $pairs[$key] = [
                "prePurchaseBillNo" => $qdBillNo,
                "ceBillNo" => $ceBillNo,
            ];
        }
        $this->log("共解析出 " . count($pairs) . " 组QD/CE对应关系（跳过重复/缺失 {$skipCount} 行）");

        // 2. 逐条回写CE单到QD单主表
        $curlService = (new CurlService())->$env();
        $curlService->gateway();
        $curlService->getModule('pa');

        // 3. 创建寄卖CE单服务（pa-biz-service）
        $curlPaService = (new CurlService())->$env();
        $curlPaService->gateway();
        $curlPaService->getModule('pa_service');

        $successCount = 0;
        $failCount = 0;
        $createSuccessCount = 0;
        $createFailCount = 0;
        $createdQdSet = []; // 同一QD只创建一次寄卖CE单
        foreach ($pairs as $pair) {
            $writeData = [
                "prePurchaseBillNo" => $pair['prePurchaseBillNo'],
                "ceBillNo" => $pair['ceBillNo'],
                "operatorName" => "repair_operator",
            ];

            if ($dry) {
                $this->log("[dry-run] 跳过回写请求: " . json_encode($writeData, JSON_UNESCAPED_UNICODE));
                $this->log("[dry-run] 跳过创建寄卖CE请求: " . json_encode([
                    "qdBillNo" => $pair['prePurchaseBillNo'],
                    "operatorName" => "zhouangang",
                    "isDelOldCeBillNo" => true,
                    "isCreateNewCeBillNo" => true,
                ], JSON_UNESCAPED_UNICODE));
                continue;
            }

            // 2.1 回写CE单到QD单主表
            $this->log("➡️ 回写: " . json_encode($writeData, JSON_UNESCAPED_UNICODE));
            $resp = $curlService->getWayPost($curlService->module . "/scms/pre_purchase/info/v1/writeBackPmoCeSkuToPrePurchase", $writeData);
            $data = DataUtils::getNewResultData($resp);

            if ($data !== []) {
                $successCount++;
                $this->log("✅ 成功: QD:{$pair['prePurchaseBillNo']} CE:{$pair['ceBillNo']} 响应: " . json_encode($resp['result'] ?? [], JSON_UNESCAPED_UNICODE));
            } else {
                $failCount++;
                $this->log("❌ 失败: QD:{$pair['prePurchaseBillNo']} CE:{$pair['ceBillNo']} httpCode:" . ($resp['httpCode'] ?? '') . " 响应: " . json_encode($resp['result'] ?? [], JSON_UNESCAPED_UNICODE));
                usleep(200000);
                continue; // 回写失败则不创建寄卖CE单
            }

            // 2.2 回写成功后，调用pa-biz-service创建寄卖CE单（同一QD仅一次）
            $qdBillNo = $pair['prePurchaseBillNo'];
            if (isset($createdQdSet[$qdBillNo])) {
                $this->log("↩️ QD:{$qdBillNo} 已创建过寄卖CE单，跳过");
            } else {
                $createdQdSet[$qdBillNo] = true;
                $createData = [
                    "qdBillNo" => $qdBillNo,
                    "operatorName" => "zhouangang",
                    "isDelOldCeBillNo" => true,
                    "isCreateNewCeBillNo" => true,
                ];
                $this->log("➡️ 创建寄卖CE: " . json_encode($createData, JSON_UNESCAPED_UNICODE));
                $createResp = $curlPaService->getWayPost($curlPaService->module . "/scms/pre_purchase/info/v1/createConsignmentCeBillByQdBillNo", $createData);
                $createDataResult = DataUtils::getNewResultData($createResp);

                if ($createDataResult !== []) {
                    $createSuccessCount++;
                    $this->log("✅ 创建寄卖CE成功: QD:{$qdBillNo} 响应: " . json_encode($createResp['result'] ?? [], JSON_UNESCAPED_UNICODE));
                } else {
                    $createFailCount++;
                    $this->log("❌ 创建寄卖CE失败: QD:{$qdBillNo} httpCode:" . ($createResp['httpCode'] ?? '') . " 响应: " . json_encode($createResp['result'] ?? [], JSON_UNESCAPED_UNICODE));
                }
            }
            usleep(200000); // 200ms，避免请求过快
        }

        $this->log("end 执行WriteBackCeToQd脚本 回写成功:{$successCount} 回写失败:{$failCount} 创建寄卖CE成功:{$createSuccessCount} 创建寄卖CE失败:{$createFailCount}" . ($dry ? '（dry-run未真实执行）' : ''));
    }
}

$controller = new WriteBackCeToQdController();
$controller->main();
