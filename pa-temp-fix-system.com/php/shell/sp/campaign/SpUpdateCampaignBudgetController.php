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
            $campaignId = trim(sprintf('%.0f', (float)$this->findValue($item, [
                "campaign_id",
                "campaignId",
                "广告活动id",
                "广告活动ID",
                "campaign_id ",
            ])), "'");
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

            if ($campaignId === "" || $campaignId === "0" || $budget === null) {
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
                    "campaign_id" => (string)$campaignId,
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
                        "budgetIsSame" => ($currentBudget !== null && bccomp((string)$currentBudget, (string)$targetBudget, 2) === 0) ? "Y" : "N",
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
                    $batchUpdateList = [];
                    foreach ($chunk as $item) {
                        if (!in_array($item['campaignId'], $updateResult['success'])) {
                            continue;
                        }
                        $mongoInfo = $mongoCampaignMap[$item['campaignId']] ?? [];
                        if (isset($mongoInfo['_id']) && trim((string)$mongoInfo['_id']) !== "") {
                            $batchUpdateList[] = [
                                '_id' => $mongoInfo['_id'],
                                'campaignId' => $item['campaignId'],
                                'updateParams' => [
                                    "dailyBudget" => $item['dailyBudget'],
                                    "modifiedBy" => "system(zhouangang)",
                                    "modifiedOn" => date("Y-m-d H:i:s", time()) . "Z",
                                    "status" => "2",
                                    "messages" => "system(zhouangang)"
                                ]
                            ];
                        } else {
                            $this->log("mongo不存在campaign但Amazon已处理成功: {$sellerId} - {$item['campaignId']}");
                        }
                    }
                    if (!empty($batchUpdateList)) {
                        $this->spApi->batchMongoUpdateCampaignInfo($batchUpdateList);
                    }
                }

                if (isset($updateResult['error']) && count($updateResult['error']) > 0) {
                    foreach ($chunk as $item) {
                        if (in_array($item['campaignId'], $updateResult['error'])) {
                            $exportList[] = [
                                "seller_id" => $sellerId,
                                "campaign_id" => (string)$item['campaignId'],
                                "daily_budget" => $item['dailyBudget'],
                                "message" => $updateResult['errorMsg'][$item['campaignId']] ?? "API操作失败",
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
            ], $exportList, "调整campaign预算失败_" . date("YmdHis") . ".xlsx", [1]);
        }
    }

    /**
     * 校验campaign每日预算是否已更新为期望值
     * 通过Amazon API查询campaign的实际每日预算，与Excel中的期望值对比(bccomp)
     * 只导出预算不匹配的数据，格式可用于retry重新执行
     * archived状态不统计，not_found只记录日志不导出
     * 用法: php SpUpdateCampaignBudgetController.php method=verify file="campaign预算调整清单_amazon_us_1.xlsx" channel=amazon_us
     * @param string $file Excel文件名(在./excel/目录下)
     * @param string $channel 可选，按channel过滤数据
     */
    public function verifyCampaignBudgetStates($file = "", $channel = "")
    {
        $channelLabel = empty($channel) ? '全部' : $channel;
        $this->log("verifyCampaignBudgetStates 开始校验 file:{$file} channel:{$channelLabel}");

        $filePath = $this->resolveExcelFile($channel, 0, $file);
        $sellerCampaignMap = [];
        $campaignChannelMap = [];
        $totalCount = 0;
        try {
            $this->excelUtils->eachXlsxRow($filePath, function ($item) use (&$sellerCampaignMap, &$campaignChannelMap, &$totalCount, $channel) {
                $campaignId = trim(sprintf('%.0f', (float)$this->findValue($item, [
                    "campaign_id",
                    "campaignId",
                    "广告活动id",
                    "广告活动ID",
                    "campaign_id ",
                ])), "'");
                $budget = $this->normalizeBudget($this->findValue($item, [
                    "目标预算",
                    "campaign_budget_amount",
                    "dailyBudget",
                    "daily_budget",
                    "budget",
                    "预算",
                    "expected_budget",
                ]));
                $sellerId = $this->normalizeId(trim((string)$this->findValue($item, [
                    "seller_id",
                    "sellerId",
                    "账号",
                ])));
                $ch = trim($item['channel'] ?? '');
                if ($campaignId === "" || $campaignId === "0" || $budget === null) {
                    return;
                }
                if ($sellerId === "" && $channel !== "") {
                    $sellerId = $this->spApi->specialSellerIdReverseConver($channel);
                }
                if ($sellerId === "") {
                    return;
                }
                if ($channel !== "" && $this->spApi->specialSellerIdConver($sellerId) !== $channel) {
                    return;
                }
                $sellerCampaignMap[$sellerId][] = [
                    "campaignId" => $campaignId,
                    "dailyBudget" => $budget,
                ];
                $campaignChannelMap[$sellerId . '_' . $campaignId] = $ch;
                $totalCount++;
            });
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }

        $this->log("channel:{$channelLabel} 共 " . count($sellerCampaignMap) . " 个seller, {$totalCount} 个campaign");

        if (count($sellerCampaignMap) <= 0) {
            $this->log("verifyCampaignBudgetStates channel:{$channelLabel} 无数据");
            return;
        }

        $exportList = [];
        $verifiedCount = 0;
        $archivedCount = 0;
        $matchCount = 0;
        $notMatchCount = 0;
        $notFoundCount = 0;

        foreach ($sellerCampaignMap as $sellerId => $campaignList) {
            $sellerChannel = $this->spApi->sellerConfig($sellerId);
            $this->log("{$sellerId} 开始校验 " . count($campaignList) . " 个campaign");

            // 分批查询Amazon API，每批最多100个campaign
            foreach (array_chunk($campaignList, 100) as $chunk) {
                $campaignIds = array_column($chunk, 'campaignId');
                $campaignIdsStr = implode(",", $campaignIds);
                $this->log("查询Amazon API: {$sellerId} campaignIds: {$campaignIdsStr}");

                $campaignInfoMap = $this->spApi->getAmazonCampaignInfoMapByCampaignIds($sellerId, $campaignIds);

                foreach ($chunk as $item) {
                    $campaignId = $item['campaignId'];
                    $expectedBudget = (float)$item['dailyBudget'];
                    if (isset($campaignInfoMap[$campaignId])) {
                        $campaignInfo = $campaignInfoMap[$campaignId];
                        // archived状态不统计，直接跳过
                        if (isset($campaignInfo['state']) && $campaignInfo['state'] === 'archived') {
                            $archivedCount++;
                            $this->log("⏭️ {$sellerId} campaignId:{$campaignId} archived状态，跳过");
                            continue;
                        }
                        $verifiedCount++;
                        $actualBudget = isset($campaignInfo['dailyBudget']) && $campaignInfo['dailyBudget'] !== ""
                            ? (float)$campaignInfo['dailyBudget']
                            : null;
                        if ($actualBudget !== null && bccomp((string)$actualBudget, (string)$expectedBudget, 2) === 0) {
                            $matchCount++;
                        } else {
                            $notMatchCount++;
                            $this->log("❌ {$sellerId} campaignId:{$campaignId} 预算不匹配: 期望{$expectedBudget}, 实际{$actualBudget}");
                            $itemChannel = $campaignChannelMap[$sellerId . '_' . $campaignId] ?: ($sellerChannel ?: $channelLabel);
                            $exportList[] = [
                                "channel" => $itemChannel,
                                "seller_id" => $sellerId,
                                "campaign_id" => (string)$campaignId,
                                "actual_budget" => $actualBudget,
                                "expected_budget" => $expectedBudget,
                            ];
                        }
                    } else {
                        $notFoundCount++;
                        $this->log("⚠️ {$sellerId} campaignId:{$campaignId} Amazon API未返回该campaign数据");
                    }
                }
            }
        }

        // 输出校验汇总
        $this->log("========== 校验汇总 ==========");
        $this->log("总数据: {$totalCount}");
        $this->log("⏭️ archived跳过: {$archivedCount}");
        $this->log("⚠️ 未找到(not_found): {$notFoundCount}");
        $this->log("已校验: {$verifiedCount}");
        $this->log("✅ 预算符合预期: {$matchCount}");
        $this->log("❌ 预算不符合预期: {$notMatchCount}");

        // 导出预算不匹配的数据，格式与更新输入一致，可直接重新执行更新
        if (count($exportList) > 0) {
            $excelUtilsExport = new ExcelUtils("sp/campaign/");
            $filePath = $excelUtilsExport->downloadXlsx([
                "channel",
                "seller_id",
                "campaign_id",
                "actual_budget",
                "expected_budget",
            ], $exportList, "预算校验失败_campaign_{$channelLabel}_" . date("YmdHis") . ".xlsx", [2]);
            $this->log("预算不匹配数据已导出: {$filePath}");
        } else {
            $this->log("所有campaign预算校验通过，无不匹配数据");
        }

        // 对预算不匹配的数据重新执行更新
        if (count($exportList) > 0) {
            $this->retryUpdateCampaignBudget($exportList, $channelLabel, $campaignChannelMap);
        }

        $this->log("verifyCampaignBudgetStates channel:{$channelLabel} 校验完毕");
    }


    /**
     * 对预算更新失败的campaign数据重新执行更新
     * 输入数据格式: [{"channel"=>"xxx", "seller_id"=>"xxx", "campaign_id"=>"xxx", "expected_budget"=>"xxx"}, ...]
     * 更新成功则更新mongo，仍然失败的导出Excel
     *
     * 用法(由verify内部调用，也可单独使用):
     *   php SpUpdateCampaignBudgetController.php method=retry file="预算校验失败_campaign_xxx.xlsx" channel=amazon_us
     *
     * @param array $failedList 预算更新失败数据列表，每项包含 channel/seller_id/campaign_id/expected_budget
     * @param string $channelLabel channel标签，用于日志和导出文件名
     * @param array $adIdChannelMap sellerId_campaignId => channel 映射，用于获取channel（从Excel读取时传入）
     */
    public function retryUpdateCampaignBudget($failedList = [], $channelLabel = '全部', $adIdChannelMap = [])
    {
        if (count($failedList) <= 0) {
            $this->log("retryUpdateCampaignBudget 无需重试");
            return;
        }

        $this->log("========== 开始重新更新失败数据 ==========");
        $spApi = new SpApi();

        // 按seller_id分组
        $retrySellerCampaigns = [];
        foreach ($failedList as $item) {
            $retrySellerCampaigns[$item['seller_id']][] = $item;
        }

        $retrySuccessCount = 0;
        $retryFailedList = [];

        foreach ($retrySellerCampaigns as $sellerId => $campaignList) {
            $sellerChannel = $spApi->sellerConfig($sellerId);
            $this->log("重新更新 {$sellerId} 共 " . count($campaignList) . " 个campaign");

            // 补查mongo中campaign的_id映射(内部含redis缓存，缺失自动批量查询)
            $campaignIds = [];
            foreach ($campaignList as $item) {
                $campaignId = trim(sprintf('%.0f', (float)($item['campaign_id'] ?? 0)), "'");
                if ($campaignId !== "" && $campaignId !== "0") {
                    $campaignIds[] = $campaignId;
                }
            }
            $mongoCampaignMap = $spApi->getMongoCampaignInfoListByCampaignIds(array_values(array_unique($campaignIds)));

            // 构建更新请求参数
            $updatePayload = [];
            foreach ($campaignList as $item) {
                $campaignId = trim(sprintf('%.0f', (float)($item['campaign_id'] ?? 0)), "'");
                $budget = $this->normalizeBudget($this->findValue($item, [
                    "daily_budget",
                    "expected_budget",
                    "目标预算",
                    "dailyBudget",
                ]));
                if ($budget === null) {
                    $this->log("❌ {$sellerId} campaignId:{$campaignId} 缺少期望预算，无法重试");
                    $itemChannel = !empty($adIdChannelMap) ? ($adIdChannelMap[$sellerId . '_' . $campaignId] ?: ($sellerChannel ?: $channelLabel)) : ($sellerChannel ?: $channelLabel);
                    $retryFailedList[] = [
                        "channel" => $itemChannel,
                        "seller_id" => $sellerId,
                        "campaign_id" => (string)$campaignId,
                        "actual_budget" => "",
                        "expected_budget" => "",
                    ];
                    continue;
                }
                $updatePayload[] = [
                    "campaignId" => $campaignId,
                    "dailyBudget" => $budget,
                ];
            }

            // 分批调用更新API
            foreach (array_chunk($updatePayload, 100) as $chunk) {
                $this->log("{$sellerId} 重新更新campaign预算: " . count($chunk) . "个");
                $updateResult = $spApi->updateCampaignBudget($sellerId, $chunk);

                if (isset($updateResult['success']) && count($updateResult['success']) > 0) {
                    $this->log("{$sellerId} 重新更新成功: " . count($updateResult['success']) . "个");
                    $batchUpdateList = [];
                    foreach ($chunk as $payload) {
                        if (!in_array($payload['campaignId'], $updateResult['success'])) {
                            continue;
                        }
                        $retrySuccessCount++;
                        $mongoInfo = $mongoCampaignMap[$payload['campaignId']] ?? [];
                        if (isset($mongoInfo['_id']) && trim((string)$mongoInfo['_id']) !== "") {
                            $batchUpdateList[] = [
                                '_id' => $mongoInfo['_id'],
                                'campaignId' => $payload['campaignId'],
                                'updateParams' => [
                                    "dailyBudget" => $payload['dailyBudget'],
                                    "modifiedBy" => "system(zhouangang)",
                                    "modifiedOn" => date("Y-m-d H:i:s", time()) . "Z",
                                    "status" => "2",
                                    "messages" => "system(zhouangang)"
                                ]
                            ];
                        } else {
                            $this->log("mongo不存在campaign但Amazon已处理成功: {$sellerId} - {$payload['campaignId']}");
                        }
                    }
                    if (!empty($batchUpdateList)) {
                        $spApi->batchMongoUpdateCampaignInfo($batchUpdateList);
                    }
                }

                if (isset($updateResult['error']) && count($updateResult['error']) > 0) {
                    $this->log("{$sellerId} 重新更新仍然失败: " . count($updateResult['error']) . "个");
                    foreach ($updateResult['error'] as $campaignId) {
                        $itemChannel = !empty($adIdChannelMap) ? ($adIdChannelMap[$sellerId . '_' . $campaignId] ?: ($sellerChannel ?: $channelLabel)) : ($sellerChannel ?: $channelLabel);
                        $budget = "";
                        foreach ($chunk as $payload) {
                            if ($payload['campaignId'] === $campaignId) {
                                $budget = $payload['dailyBudget'];
                                break;
                            }
                        }
                        $retryFailedList[] = [
                            "channel" => $itemChannel,
                            "seller_id" => $sellerId,
                            "campaign_id" => (string)$campaignId,
                            "actual_budget" => "",
                            "expected_budget" => $budget,
                            "message" => $updateResult['errorMsg'][$campaignId] ?? "API更新预算失败",
                        ];
                    }
                }
            }
        }

        // 输出重新更新汇总
        $this->log("========== 重新更新汇总 ==========");
        $this->log("重新更新总数: " . count($failedList));
        $this->log("✅ 重新更新成功: {$retrySuccessCount}");
        $this->log("❌ 重新更新仍然失败: " . count($retryFailedList));

        // 导出仍然失败的数据
        if (count($retryFailedList) > 0) {
            $excelUtilsRetry = new ExcelUtils("sp/campaign/");
            $retryFilePath = $excelUtilsRetry->downloadXlsx([
                "channel",
                "seller_id",
                "campaign_id",
                "actual_budget",
                "expected_budget",
                "message",
            ], $retryFailedList, "重新操作仍失败_campaign_{$channelLabel}_" . date("YmdHis") . ".xlsx", [2]);
            $this->log("重新操作仍失败数据已导出: {$retryFilePath}");
        }
    }
}

$parameters = DataUtils::ExplainArgv(@$argv, array());
$params = (count(@$argv) > 1) ? $parameters : $_REQUEST;
$channel = "";
$page = 0;
$file = "";
$dryRun = false;
$method = "";
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
if (isset($params['method']) && trim($params['method']) != '') {
    $method = $params['method'];
}
$con = new SpUpdateCampaignBudgetController();
if ($method == 'verify') {
    $con->verifyCampaignBudgetStates($file, $channel);
} elseif ($method == 'retry') {
    // 从导出的预算校验失败Excel读取数据，重新执行更新
    $channelLabel = empty($channel) ? '全部' : $channel;
    $excelUtils = new ExcelUtils();
    $failedList = [];
    $adIdChannelMap = [];
    try {
        // 优先查export目录（verify导出的文件在此），其次查excel目录，最后当绝对路径
        $filePath = __DIR__ . "/export/{$file}";
        if (!file_exists($filePath)) {
            $filePath = __DIR__ . "/excel/{$file}";
        }
        if (!file_exists($filePath) && file_exists($file)) {
            $filePath = $file;
        }
        if (!file_exists($filePath)) {
            die("❌ 文件不存在: export/{$file} 或 excel/{$file} 或 {$file}");
        }
        $excelUtils->eachXlsxRow($filePath, function ($item) use (&$failedList, &$adIdChannelMap, $channel) {
            $campaignId = trim(sprintf('%.0f', (float)($item['campaign_id'] ?? 0)), "'");
            $sellerId = trim($item['seller_id'] ?? '');
            $ch = trim($item['channel'] ?? '');
            if ($campaignId === '' || $campaignId === '0' || $sellerId === '') {
                return;
            }
            if (!empty($channel) && $ch !== $channel) {
                return;
            }
            $failedList[] = [
                "channel" => $ch,
                "seller_id" => $sellerId,
                "campaign_id" => $campaignId,
            ];
            $adIdChannelMap[$sellerId . '_' . $campaignId] = $ch;
        });
    } catch (Exception $e) {
        die($e->getLine() . " : " . $e->getMessage());
    }
    $con->retryUpdateCampaignBudget($failedList, $channelLabel, $adIdChannelMap);
} else {
    $con->updateCampaignBudget($channel, $page, $file, $dryRun);
}
