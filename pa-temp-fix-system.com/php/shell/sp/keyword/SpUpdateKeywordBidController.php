<?php
require_once(dirname(__FILE__) . "/../../../../php/requiredfile/requiredfile.php");
require_once(dirname(__FILE__) . "/../../../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/RequestUtils.php");
require_once(dirname(__FILE__) . "/../SpApi.php");

class SpUpdateKeywordBidController
{
    private $log;

    public function __construct()
    {
        $this->log = new MyLogger("sp/keyword");
    }

    private function log(string $string = "")
    {
        $this->log->log2($string);
    }

    public function dingTalk()
    {
        $proCurlService = new CurlService();
        $ali = $proCurlService->test()->phpali();

        $datetime = date("Y-m-d H:i:s", time());
        $postData = array(
            'userType' => 'userName',
            'userIdList' => "zhouangang",
            'title' => "【keyword广告bid调整完毕】提醒",
            'msg' => [
                [
                    "key" => "",
                    "value" => "{$datetime} keyword广告bid调整完毕"
                ]
            ]
        );
        $ali->post("dingding/sendOaNotice", $postData);
        return $this;
    }

    public function updateKeywordBid($channel = "",$page = 0)
    {
        $this->log("开始处理bid:{$channel}");
        $excelUtils = new ExcelUtils();
        $curlService = (new CurlService())->pro();
        $redisService = new RedisService();
        $spApi = new SpApi();
        $sellerKeywordBidMap = [];
        try {
            $excelUtils->eachXlsxRow(__DIR__."/excel/keyword_id调整bid清单_{$channel}_{$page}.xlsx", function ($item) use (&$sellerKeywordBidMap) {
                $sellerId = trim($item['seller_id'] ?? '');
                $keywordId = trim(sprintf('%.0f', (float)($item['keyword_id'] ?? 0)), "'");
                $bid = trim((string)($item['bid'] ?? ''));
                if ($sellerId !== "" && $keywordId !== "" && $keywordId !== "0" && $bid !== "") {
                    $sellerKeywordBidMap[$sellerId][$keywordId] = $bid;
                }
            });
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }

        if (count($sellerKeywordBidMap) <= 0) {
            $this->log("没有可处理的keyword bid");
            return;
        }

        $exportList = [];
        foreach ($sellerKeywordBidMap as $sellerId => $keywordBidMap) {
            $keywordIds = array_keys($keywordBidMap);
            $sellerKeywordList = $redisService->hGetAll("spKeyword_{$sellerId}");
            $this->log("{$sellerId} 数量: " . count($sellerKeywordList) . "个");

            $updateList = [];
            foreach ($keywordBidMap as $keywordId => $bid) {
                $updateList[] = [
                    "keywordId" => $keywordId,
                    "state" => "enabled",
                    "bid" => (float) $bid,
                ];
            }

            $keywordDocMap = [];
            foreach (array_chunk($keywordIds, 200) as $chunk) {
                $list = DataUtils::getPageList($curlService->s3023()->get("amazon_sp_keywords/queryPage", [
                    "channel" => $spApi->specialSellerIdConver($sellerId),
                    "keywordId_in" => implode(',', $chunk),
                    "limit" => 200
                ]));
                if (count($list) > 0) {
                    foreach ($list as $info) {
                        $seller = $spApi->specialSellerIdReverseConver($info['channel']);
                        $redisService->hSet("spKeyword_{$seller}", $info['keywordId'], $info['_id']);
                        $sellerKeywordList[$info['keywordId']] = $info['_id'];
                        $keywordDocMap[$info['keywordId']] = $info;
                    }
                }
            }

            if (count($updateList) > 0) {
                foreach (array_chunk($updateList, 200) as $chunk) {
                    $this->log("{$sellerId} 调整keyword bid: " . count($chunk) . "个");
                    $updateKeywordResult = $spApi->updateKeyword($sellerId, $chunk);
                    if (isset($updateKeywordResult['success']) && count($updateKeywordResult['success']) > 0) {
                        $this->log("{$sellerId} 调整bid成功: " . count($updateKeywordResult['success']) . "个");
                        foreach ($chunk as $item) {
                            if (in_array($item['keywordId'], $updateKeywordResult['success']) && isset($sellerKeywordList[$item['keywordId']]) && $sellerKeywordList[$item['keywordId']]) {
                                $spApi->mongoUpdateKeyword($sellerKeywordList[$item['keywordId']], $item['keywordId'], $item['state'], $item['bid']);
                            } elseif (in_array($item['keywordId'], $updateKeywordResult['success'])) {
                                $this->log("mongo不存在keyword但Amazon已处理成功: {$sellerId} - {$item['keywordId']}");
                            }
                        }
                    }
                    if (isset($updateKeywordResult['error']) && count($updateKeywordResult['error']) > 0) {
                        $this->log("{$sellerId} 调整bid失败: " . count($updateKeywordResult['error']) . "个");
                        foreach ($chunk as $item) {
                            if (in_array($item['keywordId'], $updateKeywordResult['error'])) {
                                $exportList[] = [
                                    "sellerId" => $sellerId,
                                    "keywordId" => $item['keywordId'],
                                    "bid" => $item['bid'],
                                ];
                            }
                        }
                    }
                }
            }
        }

        if (count($exportList) > 0) {
            $excelUtils = new ExcelUtils("sp/keyword/");
            $excelUtils->downloadXlsx([
                "seller_id",
                "keywordId",
                "bid",
            ], $exportList, "调整keywordBid失败_" . date("YmdHis") . ".xlsx");
        }
    }

    /**
     * 读取混合channel的Excel文件，按channel参数过滤后调整keyword/target bid（不传channel则处理全部）
     * 先尝试作为keyword调整bid，Amazon API返回失败的再尝试作为target调整bid
     * Excel格式: channel | seller_id | keyword_id | bid
     * 用法: php SpUpdateKeywordBidController.php method=v2 file="降bid清单.xlsx" channel=amazon_us
     *       php SpUpdateKeywordBidController.php method=v2 file="降bid清单.xlsx"  (处理全部channel)
     * @param string $file Excel文件名(在./excel/目录下)
     * @param string $channel 可选，按channel过滤数据，不传则处理全部
     */
    public function updateKeywordBidV2s($file = "", $channel = "")
    {
        $channelLabel = empty($channel) ? '全部' : $channel;
        $this->log("updateKeywordBidV2s 开始处理 file:{$file} channel:{$channelLabel}");
        $excelUtils = new ExcelUtils();
        $curlService = (new CurlService())->pro();
        $redisService = new RedisService();
        $spApi = new SpApi();
        $sellerIdBidMap = [];
        $totalIdCount = 0;
        try {
            $excelUtils->eachXlsxRow(__DIR__."/excel/{$file}", function ($item) use (&$sellerIdBidMap, &$totalIdCount, $channel) {
                $sellerId = trim($item['seller_id'] ?? '');
                $id = trim(sprintf('%.0f', (float)($item['keyword_id'] ?? 0)), "'");
                $bid = trim((string)($item['bid'] ?? ''));
                if ($sellerId !== "" && $id !== "" && $id !== "0" && $bid !== "" && (empty($channel) || (isset($item['channel']) && $item['channel'] == $channel))) {
                    $sellerIdBidMap[$sellerId][$id] = $bid;
                    $totalIdCount++;
                }
            });
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }

        $this->log("channel:{$channelLabel} 共 " . count($sellerIdBidMap) . " 个seller, {$totalIdCount} 个id");

        if (count($sellerIdBidMap) <= 0) {
            $this->log("updateKeywordBidV2s channel:{$channelLabel} 无数据");
            return;
        }

        $exportList = [];
        foreach ($sellerIdBidMap as $sellerId => $idBidMap) {
            $allIds = array_keys($idBidMap);
            $this->log("{$sellerId} 共 " . count($allIds) . " 个id待处理");

            // 预加载Redis缓存，用于后续更新mongo
            $sellerKeywordList = $redisService->hGetAll("spKeyword_{$sellerId}");
            $sellerTargetList = $redisService->hGetAll("spTarget_{$sellerId}");

            // ===== 第一步：所有id先作为keyword尝试调整bid =====
            $keywordUpdateList = [];
            foreach ($idBidMap as $id => $bid) {
                $keywordUpdateList[] = [
                    "keywordId" => $id,
                    "state" => "enabled",
                    "bid" => (float) $bid,
                ];
            }

            $keywordSuccessIds = [];
            $keywordFailedIds = [];
            if (count($keywordUpdateList) > 0) {
                foreach (array_chunk($keywordUpdateList, 200) as $chunk) {
                    $this->log("{$sellerId} 调整keyword bid: " . count($chunk) . "个");
                    $updateKeywordResult = $spApi->updateKeyword($sellerId, $chunk);
                    if (isset($updateKeywordResult['success']) && count($updateKeywordResult['success']) > 0) {
                        $this->log("{$sellerId} keyword调整bid成功: " . count($updateKeywordResult['success']) . "个");
                        foreach ($chunk as $item) {
                            if (in_array($item['keywordId'], $updateKeywordResult['success'])) {
                                $keywordSuccessIds[] = $item['keywordId'];
                                if (isset($sellerKeywordList[$item['keywordId']]) && $sellerKeywordList[$item['keywordId']]) {
                                    $spApi->mongoUpdateKeyword($sellerKeywordList[$item['keywordId']], $item['keywordId'], $item['state'], $item['bid']);
                                }
                            }
                        }
                    }
                    if (isset($updateKeywordResult['error']) && count($updateKeywordResult['error']) > 0) {
                        $keywordFailedIds = array_merge($keywordFailedIds, $updateKeywordResult['error']);
                    }
                }
            }

            // 补查mongo中keyword的_id，补充更新
            if (count($keywordSuccessIds) > 0) {
                $missingKeywordIds = array_values(array_diff($keywordSuccessIds, array_keys($sellerKeywordList)));
                if (count($missingKeywordIds) > 0) {
                    foreach (array_chunk($missingKeywordIds, 200) as $chunk) {
                        $list = DataUtils::getPageList($curlService->s3023()->get("amazon_sp_keywords/queryPage", [
                            "channel" => $spApi->specialSellerIdConver($sellerId),
                            "keywordId_in" => implode(',', $chunk),
                            "limit" => 200
                        ]));
                        if (count($list) > 0) {
                            foreach ($list as $info) {
                                $seller = $spApi->specialSellerIdReverseConver($info['channel']);
                                $redisService->hSet("spKeyword_{$seller}", $info['keywordId'], $info['_id']);
                                $sellerKeywordList[$info['keywordId']] = $info['_id'];
                                $bid = $idBidMap[$info['keywordId']];
                                $spApi->mongoUpdateKeyword($info['_id'], $info['keywordId'], "enabled", (float)$bid);
                            }
                        }
                    }
                }
            }

            // ===== 第二步：keyword调整bid失败的id，尝试作为target调整bid =====
            $keywordFailedIds = array_values(array_unique($keywordFailedIds));
            if (count($keywordFailedIds) > 0) {
                $this->log("{$sellerId} 有 " . count($keywordFailedIds) . " 个id keyword调整失败，尝试作为target调整bid");

                $targetUpdateList = [];
                foreach ($keywordFailedIds as $id) {
                    $targetUpdateList[] = [
                        "targetId" => $id,
                        "state" => "enabled",
                        "bid" => (float) $idBidMap[$id],
                    ];
                }

                $targetSuccessIds = [];
                $targetFailedIds = [];
                if (count($targetUpdateList) > 0) {
                    foreach (array_chunk($targetUpdateList, 200) as $chunk) {
                        $this->log("{$sellerId} 调整target bid: " . count($chunk) . "个");
                        $updateTargetResult = $spApi->updateTarget($sellerId, $chunk);
                        if (isset($updateTargetResult['success']) && count($updateTargetResult['success']) > 0) {
                            $this->log("{$sellerId} target调整bid成功: " . count($updateTargetResult['success']) . "个");
                            foreach ($chunk as $item) {
                                if (in_array($item['targetId'], $updateTargetResult['success'])) {
                                    $targetSuccessIds[] = $item['targetId'];
                                    if (isset($sellerTargetList[$item['targetId']]) && $sellerTargetList[$item['targetId']]) {
                                        $spApi->mongoUpdateTarget($sellerTargetList[$item['targetId']], $item['targetId'], $item['state'], $item['bid']);
                                    }
                                }
                            }
                        }
                        if (isset($updateTargetResult['error']) && count($updateTargetResult['error']) > 0) {
                            $targetFailedIds = array_merge($targetFailedIds, $updateTargetResult['error']);
                        }
                    }
                }

                // 补查mongo中target的_id，补充更新
                if (count($targetSuccessIds) > 0) {
                    $missingTargetIds = array_values(array_diff($targetSuccessIds, array_keys($sellerTargetList)));
                    if (count($missingTargetIds) > 0) {
                        foreach (array_chunk($missingTargetIds, 200) as $chunk) {
                            $list = DataUtils::getPageList($curlService->s3023()->get("amazon_sp_targets/queryPage", [
                                "channel" => $spApi->specialSellerIdConver($sellerId),
                                "targetId_in" => implode(',', $chunk),
                                "limit" => 200
                            ]));
                            if (count($list) > 0) {
                                foreach ($list as $info) {
                                    $seller = $spApi->specialSellerIdReverseConver($info['channel']);
                                    $redisService->hSet("spTarget_{$seller}", $info['targetId'], $info['_id']);
                                    $sellerTargetList[$info['targetId']] = $info['_id'];
                                    $bid = $idBidMap[$info['targetId']];
                                    $spApi->mongoUpdateTarget($info['_id'], $info['targetId'], "enabled", (float)$bid);
                                }
                            }
                        }
                    }
                }

                // ===== 第三步：keyword和target都调整失败的id =====
                $targetFailedIds = array_values(array_unique($targetFailedIds));
                if (count($targetFailedIds) > 0) {
                    $this->log("{$sellerId} 有 " . count($targetFailedIds) . " 个id keyword和target调整bid都失败");
                    foreach ($targetFailedIds as $id) {
                        $exportList[] = [
                            "sellerId" => $sellerId,
                            "id" => $id,
                            "type" => "failed",
                            "bid" => $idBidMap[$id],
                        ];
                    }
                }
            }
        }

        if (count($exportList) > 0) {
            $excelUtils = new ExcelUtils("sp/keyword/");
            $excelUtils->downloadXlsx([
                "seller_id",
                "id",
                "type",
                "bid",
            ], $exportList, "调整bid失败_{$channelLabel}_" . date("YmdHis") . ".xlsx");
        }

        $this->log("updateKeywordBidV2s channel:{$channelLabel} 处理完毕");
        $this->dingTalk();
    }

    /**
     * 校验keyword/target广告的bid是否正确调整（不传channel则校验全部）
     * 先尝试作为keyword校验，查不到的再尝试作为target校验
     * 用法: php SpUpdateKeywordBidController.php method=verify file="降bid清单.xlsx" channel=amazon_us
     *       php SpUpdateKeywordBidController.php method=verify file="降bid清单.xlsx"  (校验全部channel)
     * @param string $file Excel文件名(在./excel/目录下)
     * @param string $channel 可选，按channel过滤数据，不传则校验全部
     */
    public function verifyKeywordBidStates($file = "", $channel = "")
    {
        $channelLabel = empty($channel) ? '全部' : $channel;
        $this->log("verifyKeywordBidStates 开始校验 file:{$file} channel:{$channelLabel}");
        $excelUtils = new ExcelUtils();
        $spApi = new SpApi();
        $sellerIdBidMap = [];
        $totalIdCount = 0;
        try {
            $excelUtils->eachXlsxRow(__DIR__."/excel/{$file}", function ($item) use (&$sellerIdBidMap, &$totalIdCount, $channel) {
                $sellerId = trim($item['seller_id'] ?? '');
                $id = trim(sprintf('%.0f', (float)($item['keyword_id'] ?? 0)), "'");
                $bid = trim((string)($item['bid'] ?? ''));
                if ($sellerId !== "" && $id !== "" && $id !== "0" && $bid !== "" && (empty($channel) || (isset($item['channel']) && $item['channel'] == $channel))) {
                    $sellerIdBidMap[$sellerId][$id] = $bid;
                    $totalIdCount++;
                }
            });
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }

        $this->log("channel:{$channelLabel} 共 " . count($sellerIdBidMap) . " 个seller, {$totalIdCount} 个id");

        if (count($sellerIdBidMap) > 0) {
            $exportList = [];
            $verifiedCount = 0;
            $matchCount = 0;
            $stateMismatchCount = 0;
            $bidMismatchCount = 0;
            $notFoundCount = 0;

            foreach ($sellerIdBidMap as $sellerId => $idBidMap) {
                $allIds = array_keys($idBidMap);
                $this->log("{$sellerId} 开始校验 " . count($allIds) . " 个id");

                // ===== 第一步：作为keyword查询 =====
                $keywordVerifiedIds = [];
                foreach (array_chunk($allIds, 100) as $chunk) {
                    $keywordIdsStr = implode(",", $chunk);
                    $this->log("查询Amazon API(keyword): {$sellerId} ids: {$keywordIdsStr}");

                    $keywordListInfo = $spApi->listKeywordV2($sellerId, $keywordIdsStr);

                    foreach ($chunk as $id) {
                        if (isset($keywordListInfo[$id])) {
                            $verifiedCount++;
                            $keywordVerifiedIds[] = $id;
                            $expectedBid = (float) $idBidMap[$id];
                            $actualState = $keywordListInfo[$id]['state'];
                            $actualBid = (float) $keywordListInfo[$id]['bid'];

                            $stateMatch = ($actualState == "enabled");
                            $bidMatch = (abs($actualBid - $expectedBid) < 0.001);

                            if ($stateMatch && $bidMatch) {
                                $matchCount++;
                                $this->log("✅ {$sellerId} id:{$id} (keyword) 状态:{$actualState} bid:{$actualBid} 一致");
                            } else {
                                if (!$stateMatch) {
                                    $stateMismatchCount++;
                                    $this->log("❌ {$sellerId} id:{$id} (keyword) 状态异常: 期望enabled, 实际{$actualState}");
                                }
                                if (!$bidMatch) {
                                    $bidMismatchCount++;
                                    $this->log("❌ {$sellerId} id:{$id} (keyword) bid异常: 期望{$expectedBid}, 实际{$actualBid}");
                                }
                                $exportList[] = [
                                    "seller_id" => $sellerId,
                                    "id" => $id,
                                    "type" => "keyword",
                                    "actual_state" => $actualState,
                                    "expected_state" => "enabled",
                                    "actual_bid" => $actualBid,
                                    "expected_bid" => $expectedBid,
                                ];
                            }
                        }
                    }
                }

                // ===== 第二步：对未匹配keyword的id，作为target查询 =====
                $targetCandidateIds = array_values(array_diff($allIds, $keywordVerifiedIds));
                if (count($targetCandidateIds) > 0) {
                    $this->log("{$sellerId} 有 " . count($targetCandidateIds) . " 个id未匹配keyword，尝试作为target校验");
                    $targetVerifiedIds = [];
                    foreach (array_chunk($targetCandidateIds, 100) as $chunk) {
                        $targetIdsStr = implode(",", $chunk);
                        $this->log("查询Amazon API(target): {$sellerId} ids: {$targetIdsStr}");

                        $targetListInfo = $spApi->listTargetV2($sellerId, $targetIdsStr);

                        foreach ($chunk as $id) {
                            if (isset($targetListInfo[$id])) {
                                $verifiedCount++;
                                $targetVerifiedIds[] = $id;
                                $expectedBid = (float) $idBidMap[$id];
                                $actualState = $targetListInfo[$id]['state'];
                                $actualBid = (float) $targetListInfo[$id]['bid'];

                                $stateMatch = ($actualState == "enabled");
                                $bidMatch = (abs($actualBid - $expectedBid) < 0.001);

                                if ($stateMatch && $bidMatch) {
                                    $matchCount++;
                                    $this->log("✅ {$sellerId} id:{$id} (target) 状态:{$actualState} bid:{$actualBid} 一致");
                                } else {
                                    if (!$stateMatch) {
                                        $stateMismatchCount++;
                                        $this->log("❌ {$sellerId} id:{$id} (target) 状态异常: 期望enabled, 实际{$actualState}");
                                    }
                                    if (!$bidMatch) {
                                        $bidMismatchCount++;
                                        $this->log("❌ {$sellerId} id:{$id} (target) bid异常: 期望{$expectedBid}, 实际{$actualBid}");
                                    }
                                    $exportList[] = [
                                        "seller_id" => $sellerId,
                                        "id" => $id,
                                        "type" => "target",
                                        "actual_state" => $actualState,
                                        "expected_state" => "enabled",
                                        "actual_bid" => $actualBid,
                                        "expected_bid" => $expectedBid,
                                    ];
                                }
                            }
                        }
                    }

                    // ===== 第三步：keyword和target都查不到的id =====
                    $notFoundIds = array_values(array_diff($targetCandidateIds, $targetVerifiedIds));
                    if (count($notFoundIds) > 0) {
                        foreach ($notFoundIds as $id) {
                            $verifiedCount++;
                            $notFoundCount++;
                            $this->log("⚠️ {$sellerId} id:{$id} 既不是keyword也不是target");
                            $exportList[] = [
                                "seller_id" => $sellerId,
                                "id" => $id,
                                "type" => "not_found",
                                "actual_state" => "not_found",
                                "expected_state" => "enabled",
                                "actual_bid" => "",
                                "expected_bid" => (float) $idBidMap[$id],
                            ];
                        }
                    }
                }
            }

            // 输出校验汇总
            $this->log("========== 校验汇总 ==========");
            $this->log("总校验数: {$verifiedCount}");
            $this->log("✅ 状态和bid一致: {$matchCount}");
            $this->log("❌ 状态异常(非enabled): {$stateMismatchCount}");
            $this->log("❌ bid不一致: {$bidMismatchCount}");
            $this->log("⚠️ 未找到(not_found): {$notFoundCount}");

            // 导出异常数据到Excel
            if (count($exportList) > 0) {
                $excelUtils = new ExcelUtils("sp/keyword/");
                $filePath = $excelUtils->downloadXlsx([
                    "seller_id",
                    "id",
                    "type",
                    "actual_state",
                    "expected_state",
                    "actual_bid",
                    "expected_bid",
                ], $exportList, "校验异常_bid_{$channelLabel}_" . date("YmdHis") . ".xlsx");
                $this->log("异常数据已导出: {$filePath}");
            } else {
                $this->log("所有广告bid状态校验通过，无异常数据");
            }

            $this->log("verifyKeywordBidStates channel:{$channelLabel} 校验完毕");
        } else {
            $this->log("verifyKeywordBidStates channel:{$channelLabel} 无数据");
        }
    }
}

$parameters = DataUtils::ExplainArgv(@$argv, array());
$params = (count(@$argv) > 1) ? $parameters : $_REQUEST;
$channel = "";
$page = 0;
$file = "";
$method = "";
if (isset($params['channel']) && trim($params['channel']) != '') {
    $channel = $params['channel'];
}
if (isset($params['page']) && trim($params['page']) != '') {
    $page = $params['page'];
}
if (isset($params['file']) && trim($params['file']) != '') {
    $file = $params['file'];
}
if (isset($params['method']) && trim($params['method']) != '') {
    $method = $params['method'];
}
$con = new SpUpdateKeywordBidController();
if ($method == 'v2') {
    $con->updateKeywordBidV2s($file, $channel);
} elseif ($method == 'verify') {
    $con->verifyKeywordBidStates($file, $channel);
} else {
    $con->updateKeywordBid($channel, $page);
}
