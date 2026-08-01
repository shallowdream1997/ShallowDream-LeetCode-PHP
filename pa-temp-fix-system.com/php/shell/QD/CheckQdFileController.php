<?php
require_once(dirname(__FILE__) . "/../../../php/requiredfile/requiredfile.php");
require_once(dirname(__FILE__) . "/../../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../../php/utils/ExcelUtils.php");

class CheckQdFileController
{
    private $log;

    public function __construct()
    {
        $this->log = new MyLogger("check_qd_file");
    }

    private function log(string $string = "")
    {
        $this->log->log2($string);
    }

    /**
     * 校验寄卖清单文件内容是否正常
     * 1. 调用qdPageList分页查询qdStatus=30的清单，获取qdFileUrl(OSS签名链接)
     * 2. 调用pre_purchase/info/v1/page获取原始文件链接(dataIndex=11)
     * 3. 分别下载两个链接的文件，校验内容是否正常
     * 用法: php CheckQdFileController.php -env pro -status 30
     *       php CheckQdFileController.php  (默认pro环境，状态30)
     * @param string $env 环境(test/pro/uat)，默认pro
     * @param string $status qd状态，默认30
     */
    public function checkQdFiles($env = "pro", $status = "30")
    {
        $this->log("checkQdFiles 开始处理 env:{$env} status:{$status}");

        // 1. 分页查询所有符合条件的清单数据
        $curlPaService = (new CurlService())->$env()->getModule("pa")->gateway();

        $pageNum = 1;
        $pageSize = 200;
        $allList = [];
        $pages = 1;

        do {
            $this->log("查询qdPageList第 {$pageNum}/{$pages} 页...");
            $qdlist = DataUtils::getNewResultData($curlPaService->getWayPost(
                $curlPaService->module . "/scms/consignmentqdlist/v1/qdPageList",
                [
                    "qdBillNoList" => [],
                    "qdStatusList" => [$status],
                    "pageNum" => $pageNum,
                    "pageSize" => $pageSize,
                ]
            ));

            if ($qdlist && isset($qdlist['list']) && count($qdlist['list']) > 0) {
                $allList = array_merge($allList, $qdlist['list']);
                $pages = $qdlist['pages'] ?? ceil(($qdlist['total'] ?? count($qdlist['list'])) / $pageSize);
                $this->log("第 {$pageNum} 页获取 " . count($qdlist['list']) . " 条，total:" . ($qdlist['total'] ?? 'unknown') . " pages:{$pages}");
            } else {
                $this->log("第 {$pageNum} 页无数据，停止查询");
                break;
            }
            $pageNum++;
        } while ($pageNum <= $pages);

        $this->log("共查询到 " . count($allList) . " 条清单数据");

        if (count($allList) <= 0) {
            $this->log("无符合条件的清单数据");
            return;
        }

        // 2. 批量查询原始文件链接（通过pre_purchase/info/v1/page）
        $qdBillNos = array_column($allList, 'qdBillNo');
        $originalUrlMap = $this->fetchOriginalFileUrls($curlPaService, $qdBillNos);

        // 3. 创建下载目录
        $saveDir = __DIR__ . "/../../../export/qd_check/" . date("Ymd") . "/";
        if (!is_dir($saveDir)) {
            mkdir($saveDir, 0755, true);
            $this->log("创建目录: {$saveDir}");
        }

        // 4. 逐条下载并校验
        $excelUtils = new ExcelUtils();
        $normalCount = 0;
        $abnormalCount = 0;
        $downloadFailCount = 0;
        $emptyCount = 0;
        $noUrlCount = 0;
        $exportList = [];

        foreach ($allList as $index => $qdItem) {
            $qdBillNo = $qdItem['qdBillNo'] ?? ('unknown_' . $index);
            $qdFileUrl = $qdItem['qdFileUrl'] ?? '';
            $originalUrl = $originalUrlMap[$qdBillNo] ?? '';

            // 下载OSS签名链接文件
            $ossResult = $this->downloadAndCheck($excelUtils, $saveDir, $qdBillNo, $qdFileUrl, 'oss');
            // 下载原始链接文件
            $origResult = $this->downloadAndCheck($excelUtils, $saveDir, $qdBillNo, $originalUrl, 'orig');

            // 综合判断
            $ossStatus = $ossResult['status'];
            $origStatus = $origResult['status'];

            if ($ossStatus === 'no_url' && $origStatus === 'no_url') {
                $noUrlCount++;
                $this->log("⚠️ {$qdBillNo} 两个链接均为空");
                $exportList[] = [
                    "qd_bill_no" => $qdBillNo,
                    "oss_file_url" => $qdFileUrl,
                    "orig_file_url" => $originalUrl,
                    "oss_status" => "无链接",
                    "orig_status" => "无链接",
                    "error" => "两个文件链接均为空",
                ];
            } elseif ($ossStatus === 'normal' && $origStatus === 'normal') {
                $normalCount++;
                $this->log("✅ {$qdBillNo} 两个文件均正常 (OSS:{$ossResult['rowCount']}行, 原始:{$origResult['rowCount']}行)");
            } elseif ($ossStatus !== 'normal' && $origStatus === 'normal') {
                // OSS文件异常但原始文件正常
                $abnormalCount++;
                $this->log("❌ {$qdBillNo} OSS文件异常但原始文件正常: OSS={$ossStatus}, 原始=normal");
                $exportList[] = [
                    "qd_bill_no" => $qdBillNo,
                    "oss_file_url" => $qdFileUrl,
                    "orig_file_url" => $originalUrl,
                    "oss_status" => $ossStatus,
                    "orig_status" => "normal",
                    "error" => "OSS文件异常({$ossResult['reason']})，原始文件正常",
                ];
            } elseif ($ossStatus === 'normal' && $origStatus !== 'normal') {
                // OSS文件正常但原始文件异常
                $abnormalCount++;
                $this->log("❌ {$qdBillNo} OSS文件正常但原始文件异常: OSS=normal, 原始={$origStatus}");
                $exportList[] = [
                    "qd_bill_no" => $qdBillNo,
                    "oss_file_url" => $qdFileUrl,
                    "orig_file_url" => $originalUrl,
                    "oss_status" => "normal",
                    "orig_status" => $origStatus,
                    "error" => "OSS文件正常，原始文件异常({$origResult['reason']})",
                ];
            } else {
                // 两个都异常
                $abnormalCount++;
                $this->log("❌ {$qdBillNo} 两个文件均异常: OSS={$ossStatus}, 原始={$origStatus}");
                $exportList[] = [
                    "qd_bill_no" => $qdBillNo,
                    "oss_file_url" => $qdFileUrl,
                    "orig_file_url" => $originalUrl,
                    "oss_status" => $ossStatus,
                    "orig_status" => $origStatus,
                    "error" => "OSS({$ossResult['reason']}) + 原始({$origResult['reason']})",
                ];
            }
        }

        // 5. 输出汇总
        $this->log("========== 校验汇总 ==========");
        $this->log("总清单数: " . count($allList));
        $this->log("✅ 正常: {$normalCount}");
        $this->log("❌ 异常: {$abnormalCount}");
        $this->log("⚠️ 无文件链接: {$noUrlCount}");

        // 6. 导出异常数据
        if (count($exportList) > 0) {
            $excelUtilsExport = new ExcelUtils("qd_check/");
            $filePath = $excelUtilsExport->downloadXlsx([
                "qd_bill_no",
                "oss_file_url",
                "orig_file_url",
                "oss_status",
                "orig_status",
                "error",
            ], $exportList, "清单文件校验异常_" . date("YmdHis") . ".xlsx");
            $this->log("异常数据已导出: {$filePath}");
        } else {
            $this->log("所有清单文件校验通过，无异常数据");
        }

        $this->log("checkQdFiles env:{$env} status:{$status} 处理完毕");
    }

    /**
     * 批量查询QD单的原始文件链接（通过pre_purchase/info/v1/page接口）
     * @param CurlService $curlPaService
     * @param array $qdBillNos QD单号列表
     * @return array [qdBillNo => originalUrl]
     */
    private function fetchOriginalFileUrls($curlPaService, $qdBillNos)
    {
        $this->log("开始查询原始文件链接，共 " . count($qdBillNos) . " 个QD单");
        $originalUrlMap = [];

        // 每次查询20个QD单号
        foreach (array_chunk($qdBillNos, 20) as $chunk) {
            $pageNum = 1;
            $totalPage = 1;

            do {
                $resp = DataUtils::getNewResultData($curlPaService->getWayPost(
                    $curlPaService->module . "/scms/pre_purchase/info/v1/page",
                    [
                        "bindParamList" => [
                            [
                                "name" => "acceptCondition",
                                "value" => "apply",
                            ],
                            [
                                "name" => "ignoreOptionListSource",
                                "value" => "dict-ppmsProductDev-source_company_name,dict-ppmsProductDev-sourceDeveloperUserName,dict-ppmsProductDev-developerUserName,dict-ppmsProductDev-salesUserName,dict-ppmsProductDev-minorSalesUserName,dict-base-paBrand",
                            ],
                        ],
                        "filterList" => [
                            [
                                "name" => "custom-prePurchase-prePurchaseBillNo",
                                "valueList" => $chunk,
                            ],
                            [
                                "name" => "custom-prePurchase-prePurchaseSupplierType",
                                "valueList" => ["consignment"],
                            ],
                        ],
                        "pageNum" => $pageNum,
                        "pageSize" => 20,
                    ]
                ));

                if ($resp && isset($resp['list']) && count($resp['list']) > 0) {
                    foreach ($resp['list'] as $item) {
                        $dataList = $item['dataList'] ?? [];
                        $qdBillNo = '';
                        $originalUrl = '';
                        foreach ($dataList as $dataItem) {
                            $dataIndex = $dataItem['dataIndex'] ?? -1;
                            $contentList = $dataItem['contentList'] ?? [];
                            $text = isset($contentList[0]['text']) ? $contentList[0]['text'] : '';
                            if ($dataIndex === 0) {
                                $qdBillNo = $text;
                            }
                            if ($dataIndex === 11) {
                                $originalUrl = $text;
                            }
                        }
                        if (!empty($qdBillNo) && !empty($originalUrl)) {
                            $originalUrlMap[$qdBillNo] = $originalUrl;
                        }
                    }
                    $pageInfo = $resp['page'] ?? [];
                    $totalPage = $pageInfo['totalPage'] ?? 1;
                } else {
                    break;
                }
                $pageNum++;
            } while ($pageNum <= $totalPage);
        }

        $this->log("查询到 " . count($originalUrlMap) . " 个原始文件链接");
        return $originalUrlMap;
    }

    /**
     * 下载文件并校验内容
     * @param ExcelUtils $excelUtils
     * @param string $saveDir 保存目录
     * @param string $qdBillNo QD单号
     * @param string $url 文件URL
     * @param string $prefix 文件名前缀(oss/orig)
     * @return array ['status' => 'normal'|'abnormal'|'empty'|'download_fail'|'no_url', 'rowCount' => int, 'reason' => string]
     */
    private function downloadAndCheck($excelUtils, $saveDir, $qdBillNo, $url, $prefix)
    {
        if (empty($url)) {
            return ['status' => 'no_url', 'rowCount' => 0, 'reason' => '链接为空'];
        }

        // 下载文件
        $urlPath = parse_url($url, PHP_URL_PATH);
        $fileName = $qdBillNo . "_" . $prefix . "_" . basename($urlPath);
        $savePath = $saveDir . $fileName;

        $this->log("📥 下载 {$qdBillNo}({$prefix}): {$url}");

        try {
            $fileContent = $this->downloadFile($url);
            if ($fileContent === false) {
                $this->log("❌ {$qdBillNo}({$prefix}) 下载失败");
                return ['status' => 'download_fail', 'rowCount' => 0, 'reason' => '下载返回false'];
            }

            file_put_contents($savePath, $fileContent);
            $this->log("💾 {$qdBillNo}({$prefix}) 已保存: {$savePath} (" . strlen($fileContent) . " bytes)");
        } catch (Exception $e) {
            $this->log("❌ {$qdBillNo}({$prefix}) 下载异常: " . $e->getMessage());
            return ['status' => 'download_fail', 'rowCount' => 0, 'reason' => $e->getMessage()];
        }

        // 校验文件内容
        $checkResult = $this->checkXlsxContent($excelUtils, $savePath);

        if ($checkResult['status'] === 'empty') {
            return ['status' => 'empty', 'rowCount' => 0, 'reason' => 'xlsx无数据行'];
        } elseif ($checkResult['status'] === 'abnormal') {
            $reason = $checkResult['reason'] ?? '内容异常';
            $tagsStr = implode(", ", $checkResult['htmlTags']);
            return ['status' => 'abnormal', 'rowCount' => $checkResult['rowCount'], 'reason' => $reason . ($tagsStr ? " (HTML:{$tagsStr})" : "")];
        }

        return ['status' => 'normal', 'rowCount' => $checkResult['rowCount'], 'reason' => ''];
    }

    /**
     * 下载文件（优先curl扩展，回退file_get_contents）
     * @param string $url 文件URL
     * @return string|false 文件内容或false
     */
    private function downloadFile($url)
    {
        // 优先使用curl扩展
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
        // 回退file_get_contents
        return @file_get_contents($url);
    }

    /**
     * 校验xlsx文件内容是否正常
     * @param ExcelUtils $excelUtils
     * @param string $filePath xlsx文件路径
     * @return array ['status' => 'normal'|'abnormal'|'empty', 'rowCount' => int, 'htmlTags' => array, 'reason' => string]
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
            // 不是ZIP格式，检查是否是HTML
            $content = @file_get_contents($filePath);
            $htmlTags = [];
            if ($content && preg_match('/<(html|!DOCTYPE|body|script|div|head|meta|link|title|table|img|iframe|form|input|button|style)\b/i', $content, $matches)) {
                $htmlTags[] = strtolower($matches[1]);
                // 提取更多HTML标签
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
                    // 检查是否包含HTML标签特征
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
$env = "pro";
$status = "30";
if (isset($params['env']) && trim($params['env']) != '') {
    $env = trim($params['env']);
}
if (isset($params['status']) && trim($params['status']) != '') {
    $status = trim($params['status']);
}

$con = new CheckQdFileController();
$con->checkQdFiles($env, $status);
