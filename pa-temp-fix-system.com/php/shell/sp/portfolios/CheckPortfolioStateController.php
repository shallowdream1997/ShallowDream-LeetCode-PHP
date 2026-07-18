<?php
require_once(dirname(__FILE__) . "/../../../../php/requiredfile/requiredfile.php");
require_once(dirname(__FILE__) . "/../../../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/RequestUtils.php");

class CheckPortfolioStateController
{
    private $log;

    public function __construct()
    {
        $this->log = new MyLogger("sp/portfolios");
    }

    private function log(string $string = "")
    {
        $this->log->log2($string);
    }

    public function checkPortfolioState()
    {
        $excelUtils = new ExcelUtils();
        $curlService = (new CurlService())->pro();

        try {
            $contentList = $excelUtils->getXlsxData(__DIR__."/excel/portfolios.xlsx");
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }

        if (count($contentList) == 0) {
            $this->log("Excel 没有数据");
            return;
        }

        $this->log("共读取到 " . count($contentList) . " 条记录");

        // 按 channel + sellerId 分组
        $groupMap = [];
        foreach ($contentList as $item) {
            $channel = trim($item['channel']);
            $sellerId = trim($item['sellerId']);
            $portfolioId = trim($item['portfolioId']);
            $key = "{$channel}|{$sellerId}";
            if (!isset($groupMap[$key])) {
                $groupMap[$key] = [
                    'channel' => $channel,
                    'sellerId' => $sellerId,
                    'portfolioIds' => [],
                ];
            }
            $groupMap[$key]['portfolioIds'][] = $portfolioId;
        }

        $this->log("按 channel+sellerId 分组后共 " . count($groupMap) . " 组");

        // 记录每条原始记录顺序，用于最终导出
        $originRecords = [];
        foreach ($contentList as $item) {
            $originRecords[] = [
                'portfolioId' => trim($item['portfolioId']),
                'channel' => trim($item['channel']),
                'sellerId' => trim($item['sellerId']),
            ];
        }

        // 遍历每组，批量查询
        $portfolioResultMap = []; // portfolioId => info
        foreach ($groupMap as $key => $group) {
            $channel = $group['channel'];
            $sellerId = $group['sellerId'];
            $portfolioIds = array_unique($group['portfolioIds']);

            $this->log("正在查询组 {$key}，共 " . count($portfolioIds) . " 个 portfolioId");

            try {
                $res = DataUtils::getResultData($curlService->phphk()->post(
                    "amazon/ad/portfolios/listPortfolios/{$channel}/{$sellerId}",
                    [
                        "portfolioIdFilter" => [
                            "include" => $portfolioIds
                        ]
                    ]
                ));

                if ($res && isset($res['status']) && $res['status'] == 'success'
                    && isset($res['data']['portfolios']) && count($res['data']['portfolios']) > 0) {
                    foreach ($res['data']['portfolios'] as $portfolioInfo) {
                        $pid = $portfolioInfo['portfolioId'];
                        $portfolioResultMap[$pid] = [
                            'name' => $portfolioInfo['name'] ?? '',
                            'state' => $portfolioInfo['state'] ?? 'UNKNOWN',
                            'currencyCode' => $portfolioInfo['budget']['currencyCode'] ?? '',
                            'policy' => $portfolioInfo['budget']['policy'] ?? '',
                            'inBudget' => $portfolioInfo['inBudget'] ? 'true' : 'false',
                        ];
                    }
                } else {
                    $this->log("组 {$key} 查询无结果: " . json_encode($res, JSON_UNESCAPED_UNICODE));
                }
            } catch (Exception $e) {
                $this->log("组 {$key} 查询异常: " . $e->getMessage());
            }

            // 间隔一下，避免请求过快
            usleep(200000);
        }

        // 组装导出数据（保持原始顺序）
        $exportList = [];
        foreach ($originRecords as $record) {
            $portfolioId = $record['portfolioId'];
            $channel = $record['channel'];
            $sellerId = $record['sellerId'];

            if (isset($portfolioResultMap[$portfolioId])) {
                $info = $portfolioResultMap[$portfolioId];
                $exportList[] = [
                    "portfolioId" => $portfolioId,
                    "channel" => $channel,
                    "sellerId" => $sellerId,
                    "name" => $info['name'],
                    "state" => $info['state'],
                    "currencyCode" => $info['currencyCode'],
                    "policy" => $info['policy'],
                    "inBudget" => $info['inBudget'],
                    "errorMsg" => "",
                ];
            } else {
                $exportList[] = [
                    "portfolioId" => $portfolioId,
                    "channel" => $channel,
                    "sellerId" => $sellerId,
                    "name" => "",
                    "state" => "NOT_FOUND",
                    "currencyCode" => "",
                    "policy" => "",
                    "inBudget" => "",
                    "errorMsg" => "接口返回未包含该 portfolioId",
                ];
            }
        }

        // 导出结果
        if (count($exportList) > 0) {
            $excelUtils = new ExcelUtils("sp/portfolios/");
            $filePath = $excelUtils->downloadXlsx([
                "portfolioId",
                "channel",
                "sellerId",
                "name",
                "state",
                "currencyCode",
                "policy",
                "inBudget",
                "errorMsg",
            ], $exportList, "portfolio_state_" . date("YmdHis") . ".xlsx", [0, 2]);
            $this->log("导出完成，文件路径: " . $filePath);
        }

        $this->log("处理完成");
    }

    public function fixPolicyToNoCap()
    {
        // 先查询所有 portfolio 的状态
        $excelUtils = new ExcelUtils();
        $curlService = (new CurlService())->pro();

        try {
            $contentList = $excelUtils->getXlsxData(__DIR__."/excel/portfolios.xlsx");
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }

        if (count($contentList) == 0) {
            $this->log("Excel 没有数据");
            return;
        }

        $this->log("共读取到 " . count($contentList) . " 条记录");

        // 按 channel + sellerId 分组
        $groupMap = [];
        foreach ($contentList as $item) {
            $channel = trim($item['channel']);
            $sellerId = trim($item['sellerId']);
            $portfolioId = trim($item['portfolioId']);
            $key = "{$channel}|{$sellerId}";
            if (!isset($groupMap[$key])) {
                $groupMap[$key] = [
                    'channel' => $channel,
                    'sellerId' => $sellerId,
                    'portfolioIds' => [],
                ];
            }
            $groupMap[$key]['portfolioIds'][] = $portfolioId;
        }

        // 查询所有 portfolio 当前信息
        $portfolioResultMap = [];
        foreach ($groupMap as $key => $group) {
            $channel = $group['channel'];
            $sellerId = $group['sellerId'];
            $portfolioIds = array_unique($group['portfolioIds']);

            $this->log("正在查询组 {$key}，共 " . count($portfolioIds) . " 个 portfolioId");

            try {
                $res = DataUtils::getResultData($curlService->phphk()->post(
                    "amazon/ad/portfolios/listPortfolios/{$channel}/{$sellerId}",
                    [
                        "portfolioIdFilter" => [
                            "include" => $portfolioIds
                        ]
                    ]
                ));

                if ($res && isset($res['status']) && $res['status'] == 'success'
                    && isset($res['data']['portfolios']) && count($res['data']['portfolios']) > 0) {
                    foreach ($res['data']['portfolios'] as $portfolioInfo) {
                        $pid = $portfolioInfo['portfolioId'];
                        $portfolioResultMap[$pid] = $portfolioInfo;
                    }
                } else {
                    $this->log("组 {$key} 查询无结果: " . json_encode($res, JSON_UNESCAPED_UNICODE));
                }
            } catch (Exception $e) {
                $this->log("组 {$key} 查询异常: " . $e->getMessage());
            }

            usleep(200000);
        }

        // 筛选出 policy != NO_CAP 的，并按 channel+sellerId 分组准备更新
        $needFixGroupMap = []; // key => ['channel','sellerId','portfolios'=>[...]]
        foreach ($contentList as $item) {
            $portfolioId = trim($item['portfolioId']);
            $channel = trim($item['channel']);
            $sellerId = trim($item['sellerId']);

            if (!isset($portfolioResultMap[$portfolioId])) {
                $this->log("portfolioId {$portfolioId} 未查询到信息，跳过");
                continue;
            }

            $portfolioInfo = $portfolioResultMap[$portfolioId];
            $currentPolicy = $portfolioInfo['budget']['policy'] ?? '';

            if ($currentPolicy === 'NO_CAP') {
                continue;
            }

            $this->log("portfolioId {$portfolioId} policy 为 {$currentPolicy}，需要修复");

            $key = "{$channel}|{$sellerId}";
            if (!isset($needFixGroupMap[$key])) {
                $needFixGroupMap[$key] = [
                    'channel' => $channel,
                    'sellerId' => $sellerId,
                    'portfolios' => [],
                ];
            }
            $needFixGroupMap[$key]['portfolios'][] = [
                'portfolioId' => $portfolioId,
                'budget' => [
                    'amount' => null,
                    'currencyCode' => $portfolioInfo['budget']['currencyCode'] ?? '',
                    'endDate' => null,
                    'policy' => 'NO_CAP',
                    'startDate' => null,
                ],
            ];
        }

        if (count($needFixGroupMap) == 0) {
            $this->log("没有需要修复的 portfolio");
            return;
        }

        $this->log("共有 " . count($needFixGroupMap) . " 组需要修复");

        // 逐组调用更新接口
        $updateResults = [];
        foreach ($needFixGroupMap as $key => $fixGroup) {
            $channel = $fixGroup['channel'];
            $sellerId = $fixGroup['sellerId'];
            $portfolios = $fixGroup['portfolios'];

            $this->log("正在更新组 {$key}，共 " . count($portfolios) . " 个 portfolio");

            try {
                $updateRes = DataUtils::getResultData($curlService->phphk()->post(
                    "amazon/ad/portfolios/updatePortfolios/{$channel}/{$sellerId}",
                    [
                        'portfolios' => $portfolios,
                        'totalResults' => count($portfolios),
                    ]
                ));

                foreach ($portfolios as $pf) {
                    $pid = $pf['portfolioId'];
                    $updateResults[] = [
                        'portfolioId' => $pid,
                        'channel' => $channel,
                        'sellerId' => $sellerId,
                        'updateResult' => json_encode($updateRes, JSON_UNESCAPED_UNICODE),
                    ];
                }

                $this->log("组 {$key} 更新完成: " . json_encode($updateRes, JSON_UNESCAPED_UNICODE));
            } catch (Exception $e) {
                $this->log("组 {$key} 更新异常: " . $e->getMessage());
                foreach ($portfolios as $pf) {
                    $updateResults[] = [
                        'portfolioId' => $pf['portfolioId'],
                        'channel' => $channel,
                        'sellerId' => $sellerId,
                        'updateResult' => 'ERROR: ' . $e->getMessage(),
                    ];
                }
            }

            usleep(200000);
        }

        // 导出更新结果
        if (count($updateResults) > 0) {
            $excelUtils = new ExcelUtils("sp/portfolios/");
            $filePath = $excelUtils->downloadXlsx([
                'portfolioId',
                'channel',
                'sellerId',
                'updateResult',
            ], $updateResults, 'portfolio_fix_policy_' . date('YmdHis') . '.xlsx', [0]);
            $this->log('更新结果已导出，文件路径: ' . $filePath);
        }

        $this->log('修复处理完成');
    }
}

$parameters = DataUtils::ExplainArgv(@$argv, []);
$params = (count(@$argv) > 1) ? $parameters : $_REQUEST;
$action = $params['action'] ?? 'check';

$con = new CheckPortfolioStateController();
//if ($action === 'fix') {
//    $con->fixPolicyToNoCap();
//} else {
    $con->checkPortfolioState();
//}
