<?php
require_once(dirname(__FILE__) . "/../../php/requiredfile/requiredfile.php");
require_once(dirname(__FILE__) . "/../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../php/curl/CurlService.php");

class ExecuteEbaySellerAllocationController
{
    private $log;
    private $curlService;

    public function __construct($env = 'test')
    {
        $this->log = new MyLogger("ebay_seller_allocation");
        $this->curlService = (new CurlService())->setEnvironment($env)->phpali();
    }

    private function log($message)
    {
        $this->log->log2($message);
    }

    public function handle($inputFile, $outputFile = '')
    {
        $rows = $this->readCsvRows($inputFile);
        if (count($rows) <= 0) {
            throw new Exception("CSV无有效数据: {$inputFile}");
        }

        $skuMap = [];
        foreach ($rows as $row) {
            $skuId = trim((string)($row['sku_id'] ?? ''));
            $channel = trim((string)($row['channel'] ?? ''));
            if ($skuId === '') {
                continue;
            }
            $skuMap[$skuId] = [
                "sku_id" => $skuId,
                "channel" => $channel,
            ];
        }
        if (count($skuMap) <= 0) {
            throw new Exception("CSV未解析到有效sku_id: {$inputFile}");
        }

        $this->log("待执行SKU数量: " . count($skuMap));
        $resultList = [];
        $skuIds = array_keys($skuMap);
        foreach ($skuIds as $index => $skuId) {
            $channel = $skuMap[$skuId]['channel'] ?? '';
            $path = "product_operation_php_restful/listing-msg-distribute/calculateSkuSeller/" . rawurlencode($skuId);
            if ($channel !== '') {
                $path .= "?channel=" . rawurlencode($channel);
            }
            $this->log("执行第" . ($index + 1) . "条: " . $skuId . ($channel !== '' ? " channel={$channel}" : ""));
            $response = $this->curlService->get($path);
            $resultList[] = [
                "skuId" => $skuId,
                "channel" => $channel,
                "response" => $response,
            ];
        }

        $json = json_encode($resultList, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($outputFile !== '') {
            file_put_contents($outputFile, $json);
            $this->log("结果输出: {$outputFile}");
        }

        echo $json . PHP_EOL;
    }

    private function readCsvRows($inputFile)
    {
        if (!is_file($inputFile)) {
            throw new Exception("文件不存在: {$inputFile}");
        }

        $handle = fopen($inputFile, 'r');
        if ($handle === false) {
            throw new Exception("文件打开失败: {$inputFile}");
        }

        $rows = [];
        $headers = [];
        try {
            while (($data = fgetcsv($handle)) !== false) {
                if (empty($headers)) {
                    $headers = array_map([$this, 'normalizeCsvHeader'], $data);
                    continue;
                }

                if (count(array_filter($data, function ($item) {
                    return trim((string)$item) !== '';
                })) <= 0) {
                    continue;
                }

                $row = [];
                foreach ($headers as $index => $header) {
                    if ($header === '') {
                        continue;
                    }
                    $row[$header] = isset($data[$index]) ? trim((string)$data[$index]) : '';
                }
                $rows[] = $row;
            }
        } finally {
            fclose($handle);
        }

        return $rows;
    }

    private function normalizeCsvHeader($header)
    {
        $header = trim((string)$header);
        $header = preg_replace('/^\xEF\xBB\xBF/', '', $header);
        return $header;
    }
}

$parameters = DataUtils::ExplainArgv(@$argv, array());
$params = (count(@$argv) > 1) ? $parameters : $_REQUEST;
$env = trim((string)($params['env'] ?? 'pro'));
$inputFile = trim((string)($params['input'] ?? dirname(__FILE__) . "/../export/ebay_seller_allocation.csv"));
$outputFile = trim((string)($params['output'] ?? dirname(__FILE__) . "/../export/ebay_seller_allocation.result.json"));

$controller = new ExecuteEbaySellerAllocationController($env);
$controller->handle($inputFile, $outputFile);
