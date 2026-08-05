<?php
require_once(dirname(__FILE__) . "/../../../../php/requiredfile/requiredfile.php");
require_once(dirname(__FILE__) . "/../../../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/RequestUtils.php");
require_once(dirname(__FILE__) . "/../SpApi.php");

class SpPlacementAutoController
{
    private $log;

    /**
     * type映射: Excel中的type值 => [placementType, targetingType, campaignType]
     */
    private static $typeMap = [
        'auto'     => [1, 'auto',   'auto'],
        'keyword'  => [2, 'manual', 'keyword'],
        'asin'     => [3, 'manual', 'asin'],
        'category' => [4, 'manual', 'category'],
    ];

    public function __construct()
    {
        $this->log = new MyLogger("sp/common");
    }

    private function log(string $string = "")
    {
        $this->log->log2($string);
    }

    /**
     * 读取触发自动化清单Excel，按行调用paPlacementAmazonSp投放
     * Excel格式: product_id | scu_id | channel | seller_id | type
     * type可选: auto / keyword / asin / category
     * 以scu_id直接投放，无需再查fba
     *
     * 用法:
     *   php SpPlacementAutoController.php file="触发自动化清单.xlsx"
     *   php SpPlacementAutoController.php file="触发自动化清单.xlsx" channel=amazon_us  (只处理指定channel)
     *   php SpPlacementAutoController.php file="触发自动化清单.xlsx" type=keyword        (只处理指定type)
     *
     * @param string $file Excel文件名(在./excel/目录下，或绝对路径)
     * @param string $channel 可选，按channel过滤，不传则处理全部
     * @param string $type 可选，按type过滤，不传则处理全部
     */
    public function placementAuto($file = "", $channel = "", $type = "")
    {
        $channelLabel = empty($channel) ? '全部' : $channel;
        $typeLabel = empty($type) ? '全部' : $type;
        $this->log("placementAuto 开始处理 file:{$file} channel:{$channelLabel} type:{$typeLabel}");

        // 文件路径：先查excel/目录，如果是绝对路径则直接使用
        $filePath = __DIR__ . "/excel/{$file}";
        if (!file_exists($filePath) && file_exists($file)) {
            $filePath = $file;
        }
        if (!file_exists($filePath)) {
            $this->log("❌ 文件不存在: excel/{$file} 或 {$file}");
            return;
        }
        $this->log("读取文件: {$filePath}");

        $excelUtils = new ExcelUtils();
        $spApi = new SpApi();

        // 读取Excel
        $dataList = [];
        try {
            $excelUtils->eachXlsxRow($filePath, function ($item) use (&$dataList, $channel, $type) {
                $productId = trim($item['product_id'] ?? '');
                $scuId = trim($item['scu_id'] ?? '');
                $ch = trim($item['channel'] ?? '');
                $sellerId = trim($item['seller_id'] ?? '');
                $tp = strtolower(trim($item['type'] ?? ''));

                if ($scuId === '' || $scuId === '0' || $ch === '' || $sellerId === '' || $tp === '') {
                    return;
                }
                // 按channel过滤
                if (!empty($channel) && $ch !== $channel) {
                    return;
                }
                // 按type过滤
                if (!empty($type) && $tp !== strtolower($type)) {
                    return;
                }
                // 校验type是否合法
                if (!isset(self::$typeMap[$tp])) {
                    return;
                }

                $dataList[] = [
                    'productId' => $productId,
                    'scuId'     => $scuId,
                    'channel'   => $ch,
                    'sellerId'  => $sellerId,
                    'type'      => $tp,
                ];
            });
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }

        $this->log("channel:{$channelLabel} type:{$typeLabel} 共 " . count($dataList) . " 条数据待处理");

        if (count($dataList) <= 0) {
            $this->log("placementAuto 无数据");
            return;
        }

        // 按 channel + sellerId + type 分组统计
        $groupStats = [];
        foreach ($dataList as $item) {
            $key = "{$item['channel']}_{$item['sellerId']}_{$item['type']}";
            if (!isset($groupStats[$key])) {
                $groupStats[$key] = 0;
            }
            $groupStats[$key]++;
        }
        foreach ($groupStats as $key => $count) {
            $this->log("分组 {$key}: {$count}条");
        }

        // 逐条处理
        $successCount = 0;
        $skipCount = 0;
        $exportList = [];

        foreach ($dataList as $index => $item) {
            $productId = $item['productId'];
            $scuId = $item['scuId'];
            $ch = $item['channel'];
            $sellerId = $item['sellerId'];
            $tp = $item['type'];

            $num = $index + 1;
            $this->log("[{$num}/" . count($dataList) . "] 处理 {$ch} - {$sellerId} - scuId:{$scuId} - type:{$tp}");

            $typeConfig = self::$typeMap[$tp];
            $placementType = $typeConfig[0];
            $targetingType = $typeConfig[1];
            $campaignType = $typeConfig[2];

            // 直接以scu_id调用投放接口
            $spApi->paPlacementAmazonSp($ch, $sellerId, $placementType, $targetingType, $campaignType, $scuId);
            $successCount++;
            $this->log("✅ {$ch} - {$sellerId} - scuId:{$scuId} type:{$tp} 投放成功");
        }

        // 输出汇总
        $this->log("========== 处理汇总 ==========");
        $this->log("总数据: " . count($dataList));
        $this->log("✅ 投放成功: {$successCount}");
        $this->log("⏭️ 跳过: {$skipCount}");

        // 导出跳过/失败的数据
        if (count($exportList) > 0) {
            $excelUtilsExport = new ExcelUtils("sp/common/");
            $exportFilePath = $excelUtilsExport->downloadXlsx([
                "product_id",
                "scu_id",
                "channel",
                "seller_id",
                "type",
                "result",
            ], $exportList, "投放跳过_{$channelLabel}_{$typeLabel}_" . date("YmdHis") . ".xlsx");
            $this->log("跳过数据已导出: {$exportFilePath}");
        }

        $this->log("placementAuto channel:{$channelLabel} type:{$typeLabel} 处理完毕");
    }
}

$parameters = DataUtils::ExplainArgv(@$argv, array());
$params = (count(@$argv) > 1) ? $parameters : $_REQUEST;
$file = "";
$channel = "";
$type = "";
if (isset($params['file']) && trim($params['file']) != '') {
    $file = $params['file'];
}
if (isset($params['channel']) && trim($params['channel']) != '') {
    $channel = $params['channel'];
}
if (isset($params['type']) && trim($params['type']) != '') {
    $type = $params['type'];
}
$con = new SpPlacementAutoController();
$con->placementAuto($file, $channel, $type);
