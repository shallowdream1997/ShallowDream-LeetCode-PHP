<?php
require_once(dirname(__FILE__) . "/../../../../php/requiredfile/requiredfile.php");
require_once(dirname(__FILE__) . "/../../../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/RequestUtils.php");
require_once(dirname(__FILE__) . "/../SpApi.php");

class SpUpdateCampaignBudgetController
{
    private $log;
    private $spApi;
    private $excelUtils;

    public function __construct()
    {
        $this->log = new MyLogger("sp/campaign");
        $this->spApi = new SpApi();
        $this->excelUtils = new ExcelUtils();
    }

    private function log(string $string = "")
    {
        $this->log->log2($string);
    }

    private function findValue($row, $fields, $default = "")
    {
        foreach ($fields as $field) {
            if (isset($row[$field]) && trim((string)$row[$field]) !== "") {
                return $row[$field];
            }
        }
        return $default;
    }

    private function normalizeId($value)
    {
        $value = trim((string)$value);
        $value = trim($value, "'");
        if (substr($value, -2) === ".0") {
            $value = substr($value, 0, -2);
        }
        return trim($value);
    }

    private function normalizeBudget($value)
    {
        if ($value === null) {
            return null;
        }
        $value = trim((string)$value);
        if ($value === "") {
            return null;
        }
        $value = str_replace([",", " "], "", $value);
        return is_numeric($value) ? (float)$value : null;
    }

    private function resolveExcelFile($channel = "", $page = 0, $file = "")
    {
        $baseDir = __DIR__ . "/excel/";
        if ($file !== "") {
            if (is_file($file)) {
                return $file;
            }
            $relativeFile = $baseDir . ltrim($file, "/");
            if (is_file($relativeFile)) {
                return $relativeFile;
            }
        }

        $candidates = [
            $baseDir . "campaign预算调整清单_{$channel}_{$page}.xlsx",
            $baseDir . "campaign预算回调清单_{$channel}_{$page}.xlsx",
            $baseDir . "广告活动预算调整_{$channel}_{$page}.xlsx",
            $baseDir . "广告活动预算回调_{$channel}_{$page}.xlsx",
        ];
        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        $files = glob($baseDir . "*.xlsx");
        sort($files);
        if (count($files) === 1) {
            return $files[0];
        }
        if ($page > 0 && isset($files[$page - 1])) {
            return $files[$page - 1];
        }

        throw new Exception("未找到可用的campaign预算excel文件，请传-file参数指定文件");
    }

    private function isTruthy($value)
    {
        $value = strtolower(trim((string)$value));
        return in_array($value, ["1", "true", "yes", "y", "on"]);
    }

    public function updateCampaignBudget($channel = "", $page = 0, $file = "", $dryRun = false)
    {
        $filePath = $this->resolveExcelFile($channel, (int)$page, $file);
        $this->log("开始处理campaign预算：{$filePath}" . ($dryRun ? " [dry_run]" : ""));

        $taskMap = [];
        $campaignIds = [];
        $this->excelUtils->eachXlsxRow($filePath, function ($item) use (&$taskMap, &$campaignIds, $channel) {
            $campaignId = $this->normalizeId($this->findValue($item, [
                "campaign_id",
                "campaignId",
                "广告活动id",
                "广告活动ID",
                "campaign_id ",
            ]));
            $budget = $this->normalizeBudget($this->findValue($item, [
                "目标预算",
                "campaign_budget_amount",
                "dailyBudget",
                "daily_budget",
                "budget",
                "预算",
            ]));
            $sellerId = trim((string)$this->findValue($item, [
                "seller_id",
                "sellerId",
                "账号",
            ]));
            $sellerId = $this->normalizeId($sellerId);

            if ($campaignId === "" || $budget === null) {
                return;
            }

            if ($sellerId === "" && $channel !== "") {
                $sellerId = $this->spApi->specialSellerIdReverseConver($channel);
            }

            if ($channel !== "" && $sellerId !== "" && $this->spApi->specialSellerIdConver($sellerId) !== $channel) {
                return;
            }

            $taskKey = ($sellerId ?: "_") . "_" . $campaignId;
            $taskMap[$taskKey] = [
                "sellerId" => $sellerId,
                "campaignId" => $campaignId,
                "dailyBudget" => $budget,
                "campaignName" => trim((string)$this->findValue($item, ["campaign_name", "campaignName", "广告活动名称"])),
                "row" => $item,
            ];
            $campaignIds[] = $campaignId;
        });

        if (count($taskMap) <= 0) {
            $this->log("没有可处理的campaign预算数据");
            return;
        }

        $mongoCampaignMap = $this->spApi->getMongoCampaignInfoListByCampaignIds(array_values(array_unique($campaignIds)));
        $sellerUpdateMap = [];
        $exportList = [];
        $previewList = [];
        foreach ($taskMap as $task) {
            $campaignId = $task['campaignId'];
            $mongoInfo = $mongoCampaignMap[$campaignId] ?? [];
            $sellerId = $task['sellerId'];

            if ($sellerId === "" && isset($mongoInfo['channel']) && trim((string)$mongoInfo['channel']) !== "") {
                $sellerId = $this->spApi->specialSellerIdReverseConver($mongoInfo['channel']);
            }

            if ($sellerId === "") {
                $this->log("缺少sellerId，无法更新Amazon campaign预算：{$campaignId}");
                $exportList[] = [
                    "seller_id" => "",
                    "campaign_id" => "'" . $campaignId,
                    "daily_budget" => $task['dailyBudget'],
                    "message" => "缺少sellerId，且mongo未查到channel",
                ];
                continue;
            }

            $sellerUpdateMap[$sellerId][] = [
                "campaignId" => $campaignId,
                "dailyBudget" => $task['dailyBudget'],
                "campaignName" => $task['campaignName'],
                "mongoId" => $mongoInfo['_id'] ?? "",
                "mongoChannel" => $mongoInfo['channel'] ?? "",
            ];
        }


        if ($dryRun) {

            foreach ($sellerUpdateMap as $sellerId => $updateList) {
                $campaignInfoMap = $this->spApi->getAmazonCampaignInfoMapByCampaignIds($sellerId, array_column($updateList, 'campaignId'));
                foreach ($updateList as $item) {
                    $amazonCampaignInfo = $campaignInfoMap[(string)$item['campaignId']] ?? [];
                    $currentBudget = isset($amazonCampaignInfo['dailyBudget']) && $amazonCampaignInfo['dailyBudget'] !== ""
                        ? (float)$amazonCampaignInfo['dailyBudget']
                        : null;
                    $targetBudget = (float)$item['dailyBudget'];
                    $previewList[] = [
                        "sellerId" => $sellerId,
                        "campaignId" => $item['campaignId'],
                        "currentDailyBudget" => $currentBudget,
                        "targetDailyBudget" => $targetBudget,
                        "budgetIsSame" => ($currentBudget !== null && abs($currentBudget - $targetBudget) < 0.00001) ? "Y" : "N",
                    ];
                }
            }
            $this->log("dry_run模式，不执行Amazon API和mongo更新；待处理数量: " . count($previewList));
            foreach ($previewList as $item) {
                $this->log("模拟更新 => " . json_encode($item, JSON_UNESCAPED_UNICODE));
            }

            if (count($previewList) > 0) {
                $exportExcelUtils = new ExcelUtils("sp/campaign/");
                $exportExcelUtils->downloadXlsx([
                    "sellerId",
                    "campaignId",
                    "currentDailyBudget",
                    "targetDailyBudget",
                    "budgetIsSame",
                ], $previewList, "模拟调整campaign预算_" . date("YmdHis") . ".xlsx", [1]);
            }
            return;
        }

        foreach ($sellerUpdateMap as $sellerId => $updateList) {
            foreach (array_chunk($updateList, 100) as $chunk) {
                $amazonPayload = [];
                foreach ($chunk as $item) {
                    $amazonPayload[] = [
                        "campaignId" => $item['campaignId'],
                        "dailyBudget" => $item['dailyBudget'],
                    ];
                }

                $this->log("{$sellerId} 更新campaign预算: " . count($amazonPayload) . "个");
                $updateResult = $this->spApi->updateCampaignBudget($sellerId, $amazonPayload);

                if (isset($updateResult['success']) && count($updateResult['success']) > 0) {
                    foreach ($chunk as $item) {
                        if (!in_array($item['campaignId'], $updateResult['success'])) {
                            continue;
                        }
                        $mongoInfo = $mongoCampaignMap[$item['campaignId']] ?? [];
                        if (isset($mongoInfo['_id']) && trim((string)$mongoInfo['_id']) !== "") {
                            $this->spApi->mongoUpdateCampaignInfoV2($mongoInfo['_id'], [
                                "dailyBudget" => $item['dailyBudget'],
                                "modifiedBy" => "system(zhouangang)",
                                "modifiedOn" => date("Y-m-d H:i:s", time()) . "Z",
                                "status" => "2",
                                "messages" => "system(zhouangang)"
                            ]);
                        } else {
                            $this->log("mongo不存在campaign但Amazon已处理成功: {$sellerId} - {$item['campaignId']}");
                        }
                    }
                }

                if (isset($updateResult['error']) && count($updateResult['error']) > 0) {
                    foreach ($chunk as $item) {
                        if (in_array($item['campaignId'], $updateResult['error'])) {
                            $exportList[] = [
                                "seller_id" => $sellerId,
                                "campaign_id" => "'" . $item['campaignId'],
                                "daily_budget" => $item['dailyBudget'],
                                "message" => "Amazon更新失败",
                            ];
                        }
                    }
                }
            }
        }

        if (count($exportList) > 0) {
            $exportExcelUtils = new ExcelUtils("sp/campaign/");
            $exportExcelUtils->downloadXlsx([
                "seller_id",
                "campaign_id",
                "daily_budget",
                "message",
            ], $exportList, "调整campaign预算失败_" . date("YmdHis") . ".xlsx");
        }
    }
}

$parameters = DataUtils::ExplainArgv(@$argv, array());
$params = (count(@$argv) > 1) ? $parameters : $_REQUEST;
$channel = "";
$page = 0;
$file = "";
$dryRun = false;
if (isset($params['channel']) && trim($params['channel']) != '') {
    $channel = $params['channel'];
}
if (isset($params['page']) && trim($params['page']) != '') {
    $page = $params['page'];
}
if (isset($params['file']) && trim($params['file']) != '') {
    $file = trim($params['file']);
}
if (isset($params['dry_run'])) {
    $dryRun = $conDryRun = strtolower(trim((string)$params['dry_run']));
    $dryRun = in_array($conDryRun, ["1", "true", "yes", "y", "on"]);
}
$con = new SpUpdateCampaignBudgetController();
$con->updateCampaignBudget($channel, $page, $file, $dryRun);
