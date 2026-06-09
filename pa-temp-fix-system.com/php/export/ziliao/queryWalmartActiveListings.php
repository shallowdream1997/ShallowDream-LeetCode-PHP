<?php
require_once(dirname(__FILE__) . "/../../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../../php/utils/ExcelUtils.php");

class QueryWalmartActiveListingsController
{
    public CurlService $curlService;
    private MyLogger $log;

    public function __construct()
    {
        $this->log = new MyLogger("query_walmart_active_listings/curl");
        $this->curlService = (new CurlService())->pro();
    }

    /**
     * 日志记录
     */
    private function log($message = "")
    {
        $this->log->log2($message);
        echo $message . "\n";
    }

    /**
     * 读取 getItemId.xlsx，分批查询 walmart-active-listing-news/queryPage 并导出 Excel
     */
    public function queryItemIds()
    {
        $fileFitContent = (new ExcelUtils())->getXlsxData("getItemId.xlsx");

        if (empty($fileFitContent)) {
            $this->log("文件无数据，请检查 getItemId.xlsx 文件内容");
            return;
        }

        // 提取 ItemId - 取每个子数组的第一个值
        $itemIds = array_map('current', $fileFitContent);
        $itemIds = array_values(array_filter($itemIds, function ($v) {
            return $v !== '' && $v !== null;
        }));
        $total = count($itemIds);
        $this->log("共读取到 {$total} 个 ItemId");

        if ($total === 0) {
            $this->log("ItemId 列为空，无数据可查询");
            return;
        }

        // 分批调用接口
        $batchSize = 200;
        $headers = ['channel', 'sellerId', 'skuId', 'quoteId', 'sguId', 'itemId'];
        $allResults = [];
        $totalBatches = (int)ceil($total / $batchSize);
        $batchIndex = 0;

        foreach (array_chunk($itemIds, $batchSize) as $chunk) {
            $batchIndex++;
            $itemIdStr = implode(',', array_map('strval', $chunk));
            $chunkCount = count($chunk);

            $this->log("正在查询第 {$batchIndex}/{$totalBatches} 批，共 {$chunkCount} 个 ItemId...");

            $response = $this->curlService->s3015()->get("walmart-active-listing-news/queryPage", [
                "itemId"  => $itemIdStr,
                "columns" => implode(",", $headers),
                "limit"   => $chunkCount,
            ]);

            $list = DataUtils::getPageList($response);

            if (!empty($list)) {
                $this->log("  本批返回 " . count($list) . " 条结果");
                foreach ($list as $item) {
                    $row = [];
                    foreach ($headers as $field) {
                        $row[] = $item[$field] ?? '';
                    }
                    $allResults[] = $row;
                }
            } else {
                $this->log("  本批无返回结果");
            }
        }

        // 导出 Excel
        if (empty($allResults)) {
            $this->log("未查询到任何数据，未生成 Excel 文件");
            return;
        }

        // 修改输出路径到当前目录（ziliao 目录）
        $exportExcel = new ExcelUtils();
        $exportExcel->downPath = dirname(__FILE__) . '/';

        $fileName = "walmart_active_listings_result_" . date("YmdHis") . ".xlsx";
        $this->log("正在导出 Excel 文件，共 " . count($allResults) . " 条数据...");

        $filePath = $exportExcel->downloadXlsx($headers, $allResults, $fileName);
        $this->log("导出成功！文件路径: {$filePath}");
    }
}

$controller = new QueryWalmartActiveListingsController();
$controller->queryItemIds();
