<?php
require_once(dirname(__FILE__) . "/../../../../php/requiredfile/requiredfile.php");
require_once(dirname(__FILE__) . "/../../../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/RequestUtils.php");
require_once(dirname(__FILE__) . "/../SpApi.php");

/**
 * 自定义投放（系统组装）预演脚本。
 *
 * 默认仅调用 debug=true 的 paPlacementAmazonSp，用于校验系统组装的 bid、预算、productName。
 * 输入的 sku号/product_id 与基础数据的 product_id 对应；基础数据的 scu_id 用于系统组装，
 * msku_id 作为实际传入的 productName。
 *
 * 示例：
 * php SpPlacementCustomizeAutoDebugController.php
 * php SpPlacementCustomizeAutoDebugController.php debug=true
 * php SpPlacementCustomizeAutoDebugController.php asinFile="/path/asin.xlsx" keywordFile="/path/keyword.xlsx" baseFile="/path/base.xlsx"
 */
class SpPlacementCustomizeAutoDebugController
{
    private $log;
    private $spApi;
    private $curlService;
    private $excelUtils;
    private $debug;
    private $combineCache = [];

    public function __construct($debug = true)
    {
        $this->log = new MyLogger("sp/keyword");
        $this->spApi = new SpApi();
        $this->curlService = (new CurlService())->pro();
        $this->excelUtils = new ExcelUtils("sp/keyword/");
        $this->debug = $debug;
    }

    private function log($message)
    {
        $this->log->log2($message);
    }

    public function run($asinFile, $keywordFile, $baseFile)
    {
        $baseFile = $this->resolveFile($baseFile);

        $baseMap = $this->loadBaseMap($baseFile);
        $sourceList = [];
        if ($asinFile !== "") {
            $sourceList = array_merge($sourceList, $this->loadPlacementRows($this->resolveFile($asinFile), "asin"));
        }
        if ($keywordFile !== "") {
            $sourceList = array_merge($sourceList, $this->loadPlacementRows($this->resolveFile($keywordFile), "keyword"));
        }

        $this->log("开始预演：debug=" . ($this->debug ? "true" : "false") . "，基础数据=" . count($baseMap) . " 条，待处理=" . count($sourceList) . " 条");
        $results = [];
        foreach ($sourceList as $source) {
            $results[] = $this->processRow($source, $baseMap);
        }

        $fileName = "自定义系统组装投放_" . ($this->debug ? "debug_" : "execute_") . date("YmdHis") . ".xlsx";
        $headers = [
            "sourceFile", "sourceRow", "sourceType", "channel", "sellerId", "productId", "scu", "baseProductName",
            "combineProductName", "productNameCheck", "campaignName", "adGroupName", "placementAction", "placementType",
            "targetingType", "campaignType", "dailyBudget", "bidStrategy", "defaultBid", "debug", "combineStatus",
            "combineMessage", "placementStatus", "placementMessage", "placementDebug"
        ];
        $path = $this->excelUtils->downloadXlsx($headers, $results, $fileName, [0, 2, 3, 4, 5, 6, 7, 8, 10, 11, 17, 19, 20, 21, 22, 23, 24]);
        $this->log("处理完成：共 " . count($results) . " 条，结果文件：" . $path);
        return $path;
    }

    private function processRow($source, $baseMap)
    {
        $result = [
            "sourceFile" => $source["sourceFile"],
            "sourceRow" => $source["sourceRow"],
            "sourceType" => $source["campaignType"],
            "channel" => $source["channel"],
            "sellerId" => $source["sellerId"],
            "productId" => $source["productId"],
            "scu" => "",
            "baseProductName" => "",
            "combineProductName" => "",
            "productNameCheck" => "",
            "campaignName" => $source["campaignName"],
            "adGroupName" => $source["adGroupName"],
            "placementAction" => 4,
            "placementType" => $source["placementType"],
            "targetingType" => "manual",
            "campaignType" => $source["campaignType"],
            "dailyBudget" => "",
            "bidStrategy" => "",
            "defaultBid" => "",
            "debug" => $this->debug ? "true" : "false",
            "combineStatus" => "",
            "combineMessage" => "",
            "placementStatus" => "",
            "placementMessage" => "",
            "placementDebug" => "",
        ];

        $baseKey = $this->mapKey($source["sellerId"], $source["productId"]);
        if (!isset($baseMap[$baseKey])) {
            $result["combineStatus"] = "SKIPPED";
            $result["combineMessage"] = "基础数据未找到 seller_id + product_id";
            $result["placementStatus"] = "SKIPPED";
            $result["placementMessage"] = "未调用 paPlacementAmazonSp";
            return $result;
        }

        $base = $baseMap[$baseKey];
        $result["scu"] = $base["scuId"];
        $result["baseProductName"] = $base["mskuId"];

        $combineResponse = $this->getCombineData($source, $base["scuId"]);
        $result["combineStatus"] = $this->responseStatus($combineResponse);
        $result["combineMessage"] = $this->responseMessage($combineResponse);
        $combineData = $this->extractCombineData($combineResponse);

        $dailyBudget = isset($combineData["campaignConfig"]["dailyBudget"]) ? $combineData["campaignConfig"]["dailyBudget"] : "";
        $bidStrategy = isset($combineData["campaignConfig"]["bidStrategy"]) ? $combineData["campaignConfig"]["bidStrategy"] : "";
        $defaultBid = isset($combineData["adGroupConfig"]["defaultBid"]) ? $combineData["adGroupConfig"]["defaultBid"] : "";
        $combineProductName = isset($combineData["productConfig"]["sku"]) ? (string)$combineData["productConfig"]["sku"] : "";
        $result["dailyBudget"] = $dailyBudget;
        $result["bidStrategy"] = $bidStrategy;
        $result["defaultBid"] = $defaultBid;
        $result["combineProductName"] = $combineProductName;
        $productName = $combineProductName !== "" ? $combineProductName : $base["mskuId"];
        $result["productNameCheck"] = $combineProductName === "" ? "COMBINE_EMPTY_BASE_MSKU_FALLBACK" : ($combineProductName === $base["mskuId"] ? "MATCH_COMBINE_USED" : "MISMATCH_COMBINE_USED");

        if (!$this->isSuccess($combineResponse) || $dailyBudget === "" || $bidStrategy === "" || $defaultBid === "" || $productName === "") {
            $result["placementStatus"] = "SKIPPED";
            $result["placementMessage"] = "combineSpData 未返回完整的预算、bid策略、默认bid或productName，未调用 paPlacementAmazonSp";
            return $result;
        }

        $request = [
            "placementAction" => 4,
            "placementType" => $source["placementType"],
            "channel" => $source["channel"],
            "sellerId" => $source["sellerId"],
            "targetingType" => "manual",
            "campaignType" => $source["campaignType"],
            "scu" => $base["scuId"],
            "campaignName" => $source["campaignName"],
            "dailyBudget" => $dailyBudget,
            "bidStrategy" => $bidStrategy,
            "adGroupName" => $source["adGroupName"],
            "defaultBid" => $defaultBid,
            "productName" => $productName,
            "createdBy" => "system(SpCustomizeAutoPlacement)",
            "modifiedBy" => "system(SpCustomizeAutoPlacement)",
            "debug" => $this->debug,
        ];
        $placementResponse = DataUtils::getResultData($this->curlService->phphk()->post("amazonSpApi/paPlacementAmazonSp", $request));
        $result["placementStatus"] = $this->responseStatus($placementResponse);
        $result["placementMessage"] = $this->responseMessage($placementResponse);
        $result["placementDebug"] = $this->jsonForExcel($placementResponse);
        return $result;
    }

    private function getCombineData($source, $scuId)
    {
        $key = implode("|", [$source["channel"], $source["sellerId"], $source["campaignType"], $scuId]);
        if (!isset($this->combineCache[$key])) {
            $this->combineCache[$key] = $this->spApi->combineSpData($source["channel"], $source["sellerId"], "manual", $source["campaignType"], $scuId);
        }
        return $this->combineCache[$key];
    }

    /**
     * combineSpData 在不同网关包装下可能直接返回配置，或包装在 data / data.success 内。
     */
    private function extractCombineData($response)
    {
        if (!is_array($response)) {
            return [];
        }
        $candidates = [
            $response,
            isset($response["data"]) && is_array($response["data"]) ? $response["data"] : [],
            isset($response["data"]["success"]) && is_array($response["data"]["success"]) ? $response["data"]["success"] : [],
        ];
        foreach ($candidates as $candidate) {
            if (isset($candidate["campaignConfig"]) || isset($candidate["adGroupConfig"]) || isset($candidate["productConfig"])) {
                return $candidate;
            }
        }
        return [];
    }

    private function loadBaseMap($file)
    {
        $map = [];
        $this->excelUtils->eachXlsxRow($file, function ($row) use (&$map) {
            $sellerId = $this->value($row, "seller_id");
            $productId = $this->value($row, "product_id");
            $scuId = $this->value($row, "scu_id");
            $mskuId = $this->value($row, "msku_id");
            if ($sellerId === "" || $productId === "" || $scuId === "" || $mskuId === "") {
                return;
            }
            $map[$this->mapKey($sellerId, $productId)] = ["scuId" => $scuId, "mskuId" => $mskuId];
        });
        return $map;
    }

    private function loadPlacementRows($file, $campaignType)
    {
        $placementTypeMap = [
            "keyword" => 2,
            "asin" => 3,
            "category" => 4,
            "auto" => 1,
        ];
        if (!isset($placementTypeMap[$campaignType])) {
            throw new Exception("不支持的 campaignType：" . $campaignType);
        }
        $list = [];
        $sourceRow = 1;
        $this->excelUtils->eachXlsxRow($file, function ($row) use (&$list, &$sourceRow, $file, $campaignType, $placementTypeMap) {
            $sourceRow++;
            $channel = $this->value($row, "渠道");
            $sellerId = $this->value($row, "账号");
            $productId = $this->value($row, "sku号");
            $campaignName = $this->value($row, "campaignName");
            $adGroupName = $this->value($row, "adgroupName");
            if ($channel === "" || $sellerId === "" || $productId === "" || $campaignName === "" || $adGroupName === "") {
                return;
            }
            $list[] = [
                "sourceFile" => basename($file),
                "sourceRow" => $sourceRow,
                "channel" => $channel,
                "sellerId" => $sellerId,
                "productId" => $productId,
                "campaignName" => $campaignName,
                "adGroupName" => $adGroupName,
                "campaignType" => $campaignType,
                "placementType" => $placementTypeMap[$campaignType],
            ];
        });
        return $list;
    }

    private function resolveFile($file)
    {
        if (is_file($file)) {
            return $file;
        }
        $excelFile = __DIR__ . "/excel/" . $file;
        if (is_file($excelFile)) {
            return $excelFile;
        }
        throw new Exception("文件不存在：" . $file . "（已同时检查脚本 excel 目录）");
    }

    private function mapKey($sellerId, $productId)
    {
        return $sellerId . "|" . $productId;
    }

    private function value($row, $key)
    {
        return isset($row[$key]) ? trim((string)$row[$key]) : "";
    }

    private function isSuccess($response)
    {
        $status = strtolower($this->responseStatus($response));
        if ($status !== "unknown") {
            return $status === "success";
        }
        return !empty($this->extractCombineData($response));
    }

    private function responseStatus($response)
    {
        if (isset($response["messageType"])) {
            return (string)$response["messageType"];
        }
        return isset($response["status"]) ? (string)$response["status"] : "UNKNOWN";
    }

    private function responseMessage($response)
    {
        if (isset($response["messageContent"])) {
            return is_scalar($response["messageContent"]) ? (string)$response["messageContent"] : $this->jsonForExcel($response["messageContent"]);
        }
        if (isset($response["message"])) {
            return is_scalar($response["message"]) ? (string)$response["message"] : $this->jsonForExcel($response["message"]);
        }
        return "";
    }

    private function jsonForExcel($value)
    {
        $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return "JSON_ENCODE_FAILED";
        }
        return function_exists("mb_substr") ? mb_substr($json, 0, 30000, "UTF-8") : substr($json, 0, 30000);
    }
}

$parameters = DataUtils::ExplainArgv(@$argv, []);
for ($index = 1; $index < count($argv); $index++) {
    $argument = $argv[$index];
    if (substr($argument, 0, 1) !== "-") {
        continue;
    }
    $argument = ltrim($argument, "-");
    $parts = explode("=", $argument, 2);
    if (count($parts) === 2) {
        $parameters[$parts[0]] = $parts[1];
        continue;
    }
    if (isset($argv[$index + 1]) && substr($argv[$index + 1], 0, 1) !== "-") {
        $parameters[$argument] = $argv[$index + 1];
        $index++;
    }
}
$debugValue = isset($parameters["debug"]) ? strtolower((string)$parameters["debug"]) : "true";
$debug = !in_array($debugValue, ["0", "false", "no"], true);
$excelDir = __DIR__ . "/excel";
$hasAsinFile = isset($parameters["asinFile"]);
$hasKeywordFile = isset($parameters["keywordFile"]);
$asinFile = $hasAsinFile ? $parameters["asinFile"] : "";
$keywordFile = $hasKeywordFile ? $parameters["keywordFile"] : "";
if (!$hasAsinFile && !$hasKeywordFile) {
    $asinFile = $excelDir . "/asin-多账号多类型投放模板2026_9_29.xlsx";
    $keywordFile = $excelDir . "/keyword-多账号多类型投放模板2026_9_29.xlsx";
}
$baseFile = isset($parameters["baseFile"]) ? $parameters["baseFile"] : $excelDir . "/基础数据.xlsx";

try {
    (new SpPlacementCustomizeAutoDebugController($debug))->run($asinFile, $keywordFile, $baseFile);
} catch (Exception $e) {
    echo $e->getMessage() . PHP_EOL;
    exit(1);
}
