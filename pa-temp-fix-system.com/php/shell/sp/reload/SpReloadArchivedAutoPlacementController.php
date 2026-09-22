<?php
require_once(dirname(__FILE__) . "/../../../../php/requiredfile/requiredfile.php");
require_once(dirname(__FILE__) . "/../../../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/ExcelUtils.php");
require_once(dirname(__FILE__) . "/../../../../php/curl/CurlService.php");

/**
 * 读取需要归档重投的自动投放主单，并导出接口明细。
 */
class SpReloadArchivedAutoPlacementController
{
    private $log;
    private $gateway;
    private $exportFile;
    private $exportCsvPath;
    private $exportXlsxFileName;
    private $auditFile;
    private $inputFile;
    private $exportDir;

    private const DETAIL_PAGE_SIZE = 200;

    public function __construct($inputFile = '')
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(0);

        $this->log = new MyLogger('sp/reload');
        $this->gateway = (new CurlService())->pro()->gateway()->getModule('pa');
        $this->inputFile = $this->resolveInputFile($inputFile);
        $this->exportDir = __DIR__ . '/export';
    }

    public function run()
    {
        if (!is_file($this->inputFile)) {
            throw new RuntimeException('找不到输入文件：' . $this->inputFile);
        }

        $this->openExportFiles();
        $excel = new ExcelUtils();
        $count = 0;

        try {
            $excel->eachXlsxRow($this->inputFile, function ($row) use (&$count) {
                $mainId = $this->toString($this->value($row, array('id')));
                if ($mainId === '') {
                    $this->audit('', '', '', '输入行缺少 id，跳过');
                    return;
                }
                $count++;
                $this->processMain(
                    $mainId,
                    $this->toString($this->value($row, array('channel'))),
                    $this->toString($this->value($row, array('doc_number'))),
                    $this->toString($this->value($row, array('type'))),
                    $this->toString($this->value($row, array('action')))
                );
            });
        } finally {
            $this->closeExportFiles();
        }

        $this->writeLog('处理结束，主单数：' . $count);
    }

    private function processMain($mainId, $channel, $docNumber, $type, $action)
    {
        $pageNum = 1;
        $hasDetail = false;

        while (true) {
            $data = $this->getDetailPage($mainId, $pageNum);
            if ($data === null) {
                $this->audit($mainId, '', '', '自动投放明细接口请求失败');
                return;
            }

            $list = $this->getList($data);
            if (count($list) === 0) {
                break;
            }

            foreach ($list as $detail) {
                if (!is_array($detail)) {
                    continue;
                }
                $hasDetail = true;
                $this->exportDetail($mainId, $channel, $docNumber, $type, $action, $detail);
                $returnedMainId = $this->toString($this->value($detail, array('spAmazonAutoPlacementMainId')));
                if ($returnedMainId !== '' && $returnedMainId !== $mainId) {
                    $this->audit($mainId, $this->toString($this->value($detail, array('campaignId', 'campaign_id'))), '', '接口返回的 spAmazonAutoPlacementMainId 不匹配');
                }
            }

            if (count($list) < self::DETAIL_PAGE_SIZE) {
                break;
            }
            $pageNum++;
        }

        if (!$hasDetail) {
            $this->audit($mainId, '', '', '接口未返回明细');
        }
    }

    private function getDetailPage($mainId, $pageNum)
    {
        $response = $this->gateway->getWayPost(
            $this->gateway->module . '/sms/sp_amazon_auto_placement_common/v1/getPageSpAmazonAutoPlacementDetail',
            array(
                'pageNum' => $pageNum,
                'pageSize' => self::DETAIL_PAGE_SIZE,
                'spAmazonAutoPlacementMainId' => $mainId,
            )
        );
        $data = DataUtils::getNewResultData($response);
        if (!is_array($data)) {
            $this->writeLog('明细接口失败：mainId=' . $mainId . ' page=' . $pageNum . ' response=' . json_encode(DataUtils::getResultData($response), JSON_UNESCAPED_UNICODE));
            return null;
        }
        return $data;
    }

    private function exportDetail($inputMainId, $channel, $docNumber, $type, $action, array $detail)
    {
        fputcsv($this->exportFile, array(
            $docNumber,
            $inputMainId,
            $channel,
            $type,
            $action,
            $this->toString($this->value($detail, array('spAmazonAutoPlacementMainId'))),
            $this->toString($this->value($detail, array('sellerId', 'seller_id'))),
            $this->toString($this->value($detail, array('sku'))),
            $this->toString($this->value($detail, array('scu'))),
            $this->toString($this->value($detail, array('msku'))),
            $this->toString($this->value($detail, array('campaignName', 'campaign_name'))),
            $this->toString($this->value($detail, array('campaignId', 'campaign_id'))),
            $this->toString($this->value($detail, array('budget'))),
            $this->toString($this->value($detail, array('bidStrategy', 'bid_strategy'))),
            $this->toString($this->value($detail, array('siteRestrictions', 'site_restrictions'))),
            $this->toString($this->value($detail, array('adgroupName', 'adGroupName', 'ad_group_name'))),
            $this->toString($this->value($detail, array('adGroupId', 'adgroupId', 'ad_group_id'))),
            $this->toString($this->value($detail, array('bid'))),
            $this->toString($this->value($detail, array('productName', 'product_name'))),
            $this->toString($this->value($detail, array('content'))),
            $this->toString($this->value($detail, array('contentBid', 'content_bid'))),
            $this->toString($this->value($detail, array('contentType', 'content_type'))),
            $this->toString($this->value($detail, array('contentTypeDescribe', 'content_type_describe'))),
        ));
    }

    private function openExportFiles()
    {
        if (!is_dir($this->exportDir) && !mkdir($this->exportDir, 0775, true)) {
            throw new RuntimeException('无法创建导出目录：' . $this->exportDir);
        }
        $suffix = date('YmdHis');
        $fileTag = preg_replace('/[^\p{L}\p{N}_-]+/u', '_', pathinfo($this->inputFile, PATHINFO_FILENAME));
        $this->exportXlsxFileName = '自动投放归档重投明细_' . $fileTag . '_' . $suffix . '.xlsx';
        $this->exportCsvPath = $this->exportDir . '/.' . $fileTag . '_' . $suffix . '.tmp.csv';
        $this->exportFile = fopen($this->exportCsvPath, 'wb');
        $this->auditFile = fopen($this->exportDir . '/自动投放归档重投处理结果_' . $fileTag . '_' . $suffix . '.csv', 'wb');
        if (!$this->exportFile || !$this->auditFile) {
            throw new RuntimeException('无法创建导出文件');
        }

        fwrite($this->exportFile, "\xEF\xBB\xBF");
        fwrite($this->auditFile, "\xEF\xBB\xBF");
        fputcsv($this->exportFile, array('doc_number', 'input_main_id', 'channel', 'type', 'action', 'spAmazonAutoPlacementMainId', 'sellerId', 'sku', 'scu', 'msku', 'campaignName', 'campaignId', 'budget', 'bidStrategy', 'siteRestrictions', 'adgroupName', 'adGroupId', 'bid', 'productName', 'content', 'contentBid', 'contentType', 'contentTypeDescribe'));
        fputcsv($this->auditFile, array('spAmazonAutoPlacementMainId', 'campaignId', 'context', 'result'));
    }

    private function closeExportFiles()
    {
        if (is_resource($this->exportFile)) {
            fclose($this->exportFile);
            $this->exportFile = null;
        }
        if ($this->exportCsvPath && is_file($this->exportCsvPath)) {
            try {
                (new ExcelUtils('sp/reload/'))->downloadXlsxFromCsv(
                    $this->exportCsvPath,
                    $this->exportXlsxFileName,
                    array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 13, 14, 15, 16, 18, 19, 21, 22)
                );
            } finally {
                @unlink($this->exportCsvPath);
            }
        }
        if (is_resource($this->auditFile)) {
            fclose($this->auditFile);
        }
    }

    private function audit($mainId, $campaignId, $context, $result)
    {
        if (is_resource($this->auditFile)) {
            fputcsv($this->auditFile, array($this->excelText($mainId), $this->excelText($campaignId), $context, $result));
        }
        $this->writeLog($mainId . ' ' . $campaignId . ' ' . $result);
    }

    private function getList(array $data)
    {
        foreach (array('list', 'records', 'data') as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                return $data[$key];
            }
        }
        return $this->isList($data) ? $data : array();
    }

    private function isList(array $value)
    {
        $index = 0;
        foreach ($value as $key => $unused) {
            if ($key !== $index++) {
                return false;
            }
        }
        return true;
    }

    private function value(array $data, array $keys)
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                return $data[$key];
            }
        }
        return '';
    }

    private function toString($value)
    {
        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        return trim((string)$value);
    }

    private function excelText($value)
    {
        $value = $this->toString($value);
        return $value === '' ? '' : '="' . str_replace('"', '""', $value) . '"';
    }

    private function writeLog($message)
    {
        $this->log->log2($message);
    }

    private function resolveInputFile($inputFile)
    {
        $inputFile = trim((string)$inputFile);
        if ($inputFile === '') {
            return __DIR__ . '/excel/广告需要归档重投的0921.xlsx';
        }
        if ($inputFile[0] === '/') {
            return $inputFile;
        }
        return __DIR__ . '/excel/' . $inputFile;
    }
}

$inputFile = isset($argv[1]) ? $argv[1] : '';
$controller = new SpReloadArchivedAutoPlacementController($inputFile);
$controller->run();
