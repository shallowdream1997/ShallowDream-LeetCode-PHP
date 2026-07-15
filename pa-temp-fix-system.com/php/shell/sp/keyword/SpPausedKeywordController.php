<?php
require_once(dirname(__FILE__) . "/../../../../php/requiredfile/requiredfile.php");
require_once(dirname(__FILE__) . "/../../../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/RequestUtils.php");
require_once(dirname(__FILE__) . "/../SpApi.php");

class SpPausedKeywordController
{
    private $log;

    public function __construct()
    {
        $this->log = new MyLogger("sp");
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
            'title' => "【keyword广告写入暂停完毕】提醒",
            'msg' => [
                [
                    "key" => "",
                    "value" => "{$datetime} keyword广告写入暂停完毕"
                ]
            ]
        );
        $ali->post("dingding/sendOaNotice", $postData);
        return $this;
    }

    public function pausedKeywords($channel = "",$page = 0)
    {
        $this->log("开始处理:{$channel}");
        $excelUtils = new ExcelUtils();
        $curlService = (new CurlService())->pro();
        $redisService = new RedisService();
        $spApi = new SpApi();
        $sellerKeywordIds = [];
        try {
            $excelUtils->eachXlsxRow("./excel/keyword_Id关停清单_{$channel}_{$page}.xlsx", function ($item) use (&$sellerKeywordIds) {
                $sellerId = trim($item['seller_id'] ?? '');
                $keywordId = trim(sprintf('%.0f', (float)($item['keyword_id'] ?? 0)), "'");
                if ($sellerId !== "" && $keywordId !== "" && $keywordId !== "0") {
                    $sellerKeywordIds[$sellerId][] = $keywordId;
                }
            });
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }

        if (count($sellerKeywordIds) <= 0) {
            $this->log("没有可处理的keyword");
            return;
        }

        $exportList = [];
        foreach ($sellerKeywordIds as $sellerId => $keywordIds) {
            $keywordIds = array_values(array_unique($keywordIds));
            $sellerKeywordList = $redisService->hGetAll("spKeyword_{$sellerId}");
            $this->log("{$sellerId} 数量: " . count($sellerKeywordList) . "个");

            $updateList = [];
            foreach ($keywordIds as $keywordId) {
                $updateList[] = [
                    "keywordId" => $keywordId,
                    "state" => "paused",
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
                    $this->log("{$sellerId} 关停keyword: " . count($chunk) . "个");
                    $pausedKeywordResult = $spApi->updateKeyword($sellerId, $chunk);
                    if (isset($pausedKeywordResult['success']) && count($pausedKeywordResult['success']) > 0) {
                        $this->log("{$sellerId} 关停成功: " . count($pausedKeywordResult['success']) . "个");
                        foreach ($pausedKeywordResult['success'] as $keywordId) {
                            if (isset($sellerKeywordList[$keywordId]) && $sellerKeywordList[$keywordId]) {
                                $spApi->mongoUpdateKeyword($sellerKeywordList[$keywordId], $keywordId, "paused");
                            } else {
                                $this->log("mongo不存在keyword但Amazon已处理成功: {$sellerId} - {$keywordId}");
                            }
                        }
                    }
                    if (isset($pausedKeywordResult['error']) && count($pausedKeywordResult['error']) > 0) {
                        $this->log("{$sellerId} 关停失败: " . count($pausedKeywordResult['error']) . "个");
                        foreach ($pausedKeywordResult['error'] as $keywordId) {
                            $exportList[] = [
                                "sellerId" => $sellerId,
                                "keywordId" => $keywordId,
                            ];
                        }
                    }
                }
            }
        }

        if (count($exportList) > 0) {
            $excelUtils = new ExcelUtils("sp/");
            $excelUtils->downloadXlsx([
                "seller_id",
                "keywordId",
            ], $exportList, "关停失败的keywordId_" . date("YmdHis") . ".xlsx");
        }
    }

    /**
     * 读取混合channel的Excel文件，按channel参数过滤后关停keyword/target广告（不传channel则处理全部）
     * 先尝试作为keyword关停，Amazon API返回失败的再尝试作为target关停
     * Excel格式: channel | seller_id | keyword_id
     * 用法: php SpPausedKeywordController.php method=v2 file="暂停投放清单.xlsx" channel=amazon_us
     *       php SpPausedKeywordController.php method=v2 file="暂停投放清单.xlsx"  (处理全部channel)
     * @param string $file Excel文件名(在./excel/目录下)
     * @param string $channel 可选，按channel过滤数据，不传则处理全部
     */
    public function pausedKeywordV2s($file = "", $channel = "")
    {
        $channelLabel = empty($channel) ? '全部' : $channel;
        $this->log("pausedKeywordV2s 开始处理 file:{$file} channel:{$channelLabel}");
        $excelUtils = new ExcelUtils();
        $curlService = (new CurlService())->pro();
        $redisService = new RedisService();
        $spApi = new SpApi();
        $sellerIds = [];
        $totalIdCount = 0;
        try {
            $excelUtils->eachXlsxRow("./excel/{$file}", function ($item) use (&$sellerIds, &$totalIdCount, $channel) {
                $sellerId = trim($item['seller_id'] ?? '');
                $id = trim(sprintf('%.0f', (float)($item['keyword_id'] ?? 0)), "'");
                if ($sellerId !== "" && $id !== "" && $id !== "0" && (empty($channel) || (isset($item['channel']) && $item['channel'] == $channel))) {
                    $sellerIds[$sellerId][] = $id;
                    $totalIdCount++;
                }
            });
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }

        $this->log("channel:{$channelLabel} 共 " . count($sellerIds) . " 个seller, {$totalIdCount} 个id");

        if (count($sellerIds) <= 0) {
            $this->log("pausedKeywordV2s channel:{$channelLabel} 无数据");
            return;
        }

        $exportList = [];
        foreach ($sellerIds as $sellerId => $ids) {
            $ids = array_values(array_unique($ids));
            $this->log("{$sellerId} 共 " . count($ids) . " 个id待处理");

            // 预加载Redis缓存，用于后续更新mongo
            $sellerKeywordList = $redisService->hGetAll("spKeyword_{$sellerId}");
            $sellerTargetList = $redisService->hGetAll("spTarget_{$sellerId}");

            // ===== 第一步：所有id先作为keyword尝试关停 =====
            $keywordUpdateList = [];
            foreach ($ids as $id) {
                $keywordUpdateList[] = [
                    "keywordId" => $id,
                    "state" => "paused",
                ];
            }

            $keywordSuccessIds = [];
            $keywordFailedIds = [];
            if (count($keywordUpdateList) > 0) {
                foreach (array_chunk($keywordUpdateList, 200) as $chunk) {
                    $this->log("{$sellerId} 关停keyword: " . count($chunk) . "个");
                    $pausedKeywordResult = $spApi->updateKeyword($sellerId, $chunk);
                    if (isset($pausedKeywordResult['success']) && count($pausedKeywordResult['success']) > 0) {
                        $this->log("{$sellerId} keyword关停成功: " . count($pausedKeywordResult['success']) . "个");
                        foreach ($pausedKeywordResult['success'] as $keywordId) {
                            $keywordSuccessIds[] = $keywordId;
                            // 更新mongo（有缓存就更新，没有也无所谓，Amazon已关停成功）
                            if (isset($sellerKeywordList[$keywordId]) && $sellerKeywordList[$keywordId]) {
                                $spApi->mongoUpdateKeyword($sellerKeywordList[$keywordId], $keywordId, "paused");
                            }
                        }
                    }
                    if (isset($pausedKeywordResult['error']) && count($pausedKeywordResult['error']) > 0) {
                        $keywordFailedIds = array_merge($keywordFailedIds, $pausedKeywordResult['error']);
                    }
                }
            }

            // 补查mongo中keyword的_id（用于后续可能需要的操作）
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
                                $spApi->mongoUpdateKeyword($info['_id'], $info['keywordId'], "paused");
                            }
                        }
                    }
                }
            }

            // ===== 第二步：keyword关停失败的id，尝试作为target关停 =====
            $keywordFailedIds = array_values(array_unique($keywordFailedIds));
            if (count($keywordFailedIds) > 0) {
                $this->log("{$sellerId} 有 " . count($keywordFailedIds) . " 个id keyword关停失败，尝试作为target关停");

                $targetUpdateList = [];
                foreach ($keywordFailedIds as $id) {
                    $targetUpdateList[] = [
                        "targetId" => $id,
                        "state" => "paused",
                    ];
                }

                $targetSuccessIds = [];
                $targetFailedIds = [];
                if (count($targetUpdateList) > 0) {
                    foreach (array_chunk($targetUpdateList, 200) as $chunk) {
                        $this->log("{$sellerId} 关停target: " . count($chunk) . "个");
                        $pausedTargetResult = $spApi->updateTarget($sellerId, $chunk);
                        if (isset($pausedTargetResult['success']) && count($pausedTargetResult['success']) > 0) {
                            $this->log("{$sellerId} target关停成功: " . count($pausedTargetResult['success']) . "个");
                            foreach ($pausedTargetResult['success'] as $targetId) {
                                $targetSuccessIds[] = $targetId;
                                if (isset($sellerTargetList[$targetId]) && $sellerTargetList[$targetId]) {
                                    $spApi->mongoUpdateTarget($sellerTargetList[$targetId], $targetId, "paused");
                                }
                            }
                        }
                        if (isset($pausedTargetResult['error']) && count($pausedTargetResult['error']) > 0) {
                            $targetFailedIds = array_merge($targetFailedIds, $pausedTargetResult['error']);
                        }
                    }
                }

                // 补查mongo中target的_id
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
                                    $spApi->mongoUpdateTarget($info['_id'], $info['targetId'], "paused");
                                }
                            }
                        }
                    }
                }

                // ===== 第三步：keyword和target都关停失败的id =====
                $targetFailedIds = array_values(array_unique($targetFailedIds));
                if (count($targetFailedIds) > 0) {
                    $this->log("{$sellerId} 有 " . count($targetFailedIds) . " 个id keyword和target都关停失败");
                    foreach ($targetFailedIds as $id) {
                        $exportList[] = [
                            "sellerId" => $sellerId,
                            "id" => $id,
                            "type" => "failed",
                        ];
                    }
                }
            }
        }

        if (count($exportList) > 0) {
            $excelUtils = new ExcelUtils("sp/");
            $excelUtils->downloadXlsx([
                "seller_id",
                "id",
                "type",
            ], $exportList, "关停失败_{$channelLabel}_" . date("YmdHis") . ".xlsx");
        }

        $this->log("pausedKeywordV2s channel:{$channelLabel} 处理完毕");
        $this->dingTalk();
    }

    /**
     * 校验keyword/target广告状态是否正确修改为paused（不传channel则校验全部）
     * 先尝试作为keyword校验，查不到的再尝试作为target校验
     * 用法: php SpPausedKeywordController.php method=verify file="暂停投放清单.xlsx" channel=amazon_us
     *       php SpPausedKeywordController.php method=verify file="暂停投放清单.xlsx"  (校验全部channel)
     * @param string $file Excel文件名(在./excel/目录下)
     * @param string $channel 可选，按channel过滤数据，不传则校验全部
     */
    public function verifyKeywordStates($file = "", $channel = "")
    {
        $channelLabel = empty($channel) ? '全部' : $channel;
        $this->log("verifyKeywordStates 开始校验 file:{$file} channel:{$channelLabel}");
        $excelUtils = new ExcelUtils();
        $spApi = new SpApi();
        $sellerIds = [];
        $totalIdCount = 0;
        try {
            $excelUtils->eachXlsxRow("./excel/{$file}", function ($item) use (&$sellerIds, &$totalIdCount, $channel) {
                $sellerId = trim($item['seller_id'] ?? '');
                $id = trim(sprintf('%.0f', (float)($item['keyword_id'] ?? 0)), "'");
                if ($sellerId !== "" && $id !== "" && $id !== "0" && (empty($channel) || (isset($item['channel']) && $item['channel'] == $channel))) {
                    $sellerIds[$sellerId][] = $id;
                    $totalIdCount++;
                }
            });
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }

        $this->log("channel:{$channelLabel} 共 " . count($sellerIds) . " 个seller, {$totalIdCount} 个id");

        if (count($sellerIds) > 0) {
            $exportList = [];
            $verifiedCount = 0;
            $pausedCount = 0;
            $notPausedCount = 0;
            $notFoundCount = 0;

            foreach ($sellerIds as $sellerId => $ids) {
                $ids = array_values(array_unique($ids));
                $this->log("{$sellerId} 开始校验 " . count($ids) . " 个id");

                // ===== 第一步：作为keyword查询 =====
                $keywordVerifiedIds = [];
                foreach (array_chunk($ids, 100) as $chunk) {
                    $keywordIdsStr = implode(",", $chunk);
                    $this->log("查询Amazon API(keyword): {$sellerId} ids: {$keywordIdsStr}");

                    $keywordListInfo = $spApi->listKeywordV2($sellerId, $keywordIdsStr);

                    foreach ($chunk as $id) {
                        if (isset($keywordListInfo[$id])) {
                            $verifiedCount++;
                            $keywordVerifiedIds[] = $id;
                            $actualState = $keywordListInfo[$id]['state'];
                            if ($actualState == "paused") {
                                $pausedCount++;
                                $this->log("✅ {$sellerId} id:{$id} (keyword) 状态正确: {$actualState}");
                            } else {
                                $notPausedCount++;
                                $this->log("❌ {$sellerId} id:{$id} (keyword) 状态异常: 期望paused, 实际{$actualState}");
                                $exportList[] = [
                                    "seller_id" => $sellerId,
                                    "id" => $id,
                                    "type" => "keyword",
                                    "actual_state" => $actualState,
                                    "expected_state" => "paused",
                                ];
                            }
                        }
                    }
                }

                // ===== 第二步：对未匹配keyword的id，作为target查询 =====
                $targetCandidateIds = array_values(array_diff($ids, $keywordVerifiedIds));
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
                                $actualState = $targetListInfo[$id]['state'];
                                if ($actualState == "paused") {
                                    $pausedCount++;
                                    $this->log("✅ {$sellerId} id:{$id} (target) 状态正确: {$actualState}");
                                } else {
                                    $notPausedCount++;
                                    $this->log("❌ {$sellerId} id:{$id} (target) 状态异常: 期望paused, 实际{$actualState}");
                                    $exportList[] = [
                                        "seller_id" => $sellerId,
                                        "id" => $id,
                                        "type" => "target",
                                        "actual_state" => $actualState,
                                        "expected_state" => "paused",
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
                                "expected_state" => "paused",
                            ];
                        }
                    }
                }
            }

            // 输出校验汇总
            $this->log("========== 校验汇总 ==========");
            $this->log("总校验数: {$verifiedCount}");
            $this->log("✅ 已暂停(paused): {$pausedCount}");
            $this->log("❌ 未暂停(非paused状态): {$notPausedCount}");
            $this->log("⚠️ 未找到(not_found): {$notFoundCount}");

            // 导出异常数据到Excel
            if (count($exportList) > 0) {
                $excelUtils = new ExcelUtils("sp/");
                $filePath = $excelUtils->downloadXlsx([
                    "seller_id",
                    "id",
                    "type",
                    "actual_state",
                    "expected_state",
                ], $exportList, "校验异常_paused_{$channelLabel}_" . date("YmdHis") . ".xlsx");
                $this->log("异常数据已导出: {$filePath}");
            } else {
                $this->log("所有广告状态校验通过，无异常数据");
            }

            $this->log("verifyKeywordStates channel:{$channelLabel} 校验完毕");
        } else {
            $this->log("verifyKeywordStates channel:{$channelLabel} 无数据");
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
$con = new SpPausedKeywordController();
if ($method == 'v2') {
    $con->pausedKeywordV2s($file, $channel);
} elseif ($method == 'verify') {
    $con->verifyKeywordStates($file, $channel);
} else {
    $con->pausedKeywords($channel, $page);
}
