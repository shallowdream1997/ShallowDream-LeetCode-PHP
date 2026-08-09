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
            $excelUtils->eachXlsxRow(__DIR__."/excel/keyword_Id关停清单_{$channel}_{$page}.xlsx", function ($item) use (&$sellerKeywordIds) {
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
                        $batchUpdateList = [];
                        foreach ($pausedKeywordResult['success'] as $keywordId) {
                            if (isset($sellerKeywordList[$keywordId]) && $sellerKeywordList[$keywordId]) {
                                $batchUpdateList[] = [
                                    '_id' => $sellerKeywordList[$keywordId],
                                    'keywordId' => $keywordId,
                                    'state' => 'paused'
                                ];
                            } else {
                                $this->log("mongo不存在keyword但Amazon已处理成功: {$sellerId} - {$keywordId}");
                            }
                        }
                        if (!empty($batchUpdateList)) {
                            $spApi->batchMongoUpdateKeyword($batchUpdateList);
                        }
                    }
                    if (isset($pausedKeywordResult['error']) && count($pausedKeywordResult['error']) > 0) {
                        $this->log("{$sellerId} 关停失败: " . count($pausedKeywordResult['error']) . "个");
                        foreach ($pausedKeywordResult['error'] as $keywordId) {
                            $exportList[] = [
                                "seller_id" => $sellerId,
                                "keyword_id" => (string)$keywordId,
                                "message" => $pausedKeywordResult['errorMsg'][$keywordId] ?? "API操作失败",
                            ];
                        }
                    }
                }
            }
        }

        if (count($exportList) > 0) {
            $excelUtils = new ExcelUtils("sp/keyword/");
            $excelUtils->downloadXlsx([
                "seller_id",
                "keyword_id",
                "message",
            ], $exportList, "关停失败的keywordId_" . date("YmdHis") . ".xlsx", [1]);
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
            $excelUtils->eachXlsxRow(__DIR__."/excel/{$file}", function ($item) use (&$sellerIds, &$totalIdCount, $channel) {
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
            $keywordErrorMsg = [];
            if (count($keywordUpdateList) > 0) {
                foreach (array_chunk($keywordUpdateList, 200) as $chunk) {
                    $this->log("{$sellerId} 关停keyword: " . count($chunk) . "个");
                    $pausedKeywordResult = $spApi->updateKeyword($sellerId, $chunk);
                    if (isset($pausedKeywordResult['success']) && count($pausedKeywordResult['success']) > 0) {
                        $this->log("{$sellerId} keyword关停成功: " . count($pausedKeywordResult['success']) . "个");
                        $batchUpdateList = [];
                        foreach ($pausedKeywordResult['success'] as $keywordId) {
                            $keywordSuccessIds[] = $keywordId;
                            // 更新mongo（有缓存就更新，没有也无所谓，Amazon已关停成功）
                            if (isset($sellerKeywordList[$keywordId]) && $sellerKeywordList[$keywordId]) {
                                $batchUpdateList[] = [
                                    '_id' => $sellerKeywordList[$keywordId],
                                    'keywordId' => $keywordId,
                                    'state' => 'paused'
                                ];
                            }
                        }
                        if (!empty($batchUpdateList)) {
                            $spApi->batchMongoUpdateKeyword($batchUpdateList);
                        }
                    }
                    if (isset($pausedKeywordResult['error']) && count($pausedKeywordResult['error']) > 0) {
                        $keywordFailedIds = array_merge($keywordFailedIds, $pausedKeywordResult['error']);
                        $keywordErrorMsg = $keywordErrorMsg + ($pausedKeywordResult['errorMsg'] ?? []);
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
                            $batchUpdateList = [];
                            foreach ($list as $info) {
                                $seller = $spApi->specialSellerIdReverseConver($info['channel']);
                                $redisService->hSet("spKeyword_{$seller}", $info['keywordId'], $info['_id']);
                                $sellerKeywordList[$info['keywordId']] = $info['_id'];
                                $batchUpdateList[] = [
                                    '_id' => $info['_id'],
                                    'keywordId' => $info['keywordId'],
                                    'state' => 'paused'
                                ];
                            }
                            if (!empty($batchUpdateList)) {
                                $spApi->batchMongoUpdateKeyword($batchUpdateList);
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
                $targetErrorMsg = [];
                if (count($targetUpdateList) > 0) {
                    foreach (array_chunk($targetUpdateList, 200) as $chunk) {
                        $this->log("{$sellerId} 关停target: " . count($chunk) . "个");
                        $pausedTargetResult = $spApi->updateTarget($sellerId, $chunk);
                        if (isset($pausedTargetResult['success']) && count($pausedTargetResult['success']) > 0) {
                            $this->log("{$sellerId} target关停成功: " . count($pausedTargetResult['success']) . "个");
                            $batchUpdateList = [];
                            foreach ($pausedTargetResult['success'] as $targetId) {
                                $targetSuccessIds[] = $targetId;
                                if (isset($sellerTargetList[$targetId]) && $sellerTargetList[$targetId]) {
                                    $batchUpdateList[] = [
                                        '_id' => $sellerTargetList[$targetId],
                                        'targetId' => $targetId,
                                        'state' => 'paused'
                                    ];
                                }
                            }
                            if (!empty($batchUpdateList)) {
                                $spApi->batchMongoUpdateTarget($batchUpdateList);
                            }
                        }
                        if (isset($pausedTargetResult['error']) && count($pausedTargetResult['error']) > 0) {
                            $targetFailedIds = array_merge($targetFailedIds, $pausedTargetResult['error']);
                            $targetErrorMsg = $targetErrorMsg + ($pausedTargetResult['errorMsg'] ?? []);
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
                                $batchUpdateList = [];
                                foreach ($list as $info) {
                                    $seller = $spApi->specialSellerIdReverseConver($info['channel']);
                                    $redisService->hSet("spTarget_{$seller}", $info['targetId'], $info['_id']);
                                    $sellerTargetList[$info['targetId']] = $info['_id'];
                                    $batchUpdateList[] = [
                                        '_id' => $info['_id'],
                                        'targetId' => $info['targetId'],
                                        'state' => 'paused'
                                    ];
                                }
                                if (!empty($batchUpdateList)) {
                                    $spApi->batchMongoUpdateTarget($batchUpdateList);
                                }
                            }
                        }
                    }
                }

                // ===== 第三步：keyword和target都关停失败的id =====
                $targetFailedIds = array_values(array_unique($targetFailedIds));
                if (count($targetFailedIds) > 0) {
                    $this->log("{$sellerId} 有 " . count($targetFailedIds) . " 个id keyword和target都关停失败");
                    $sellerChannel = $spApi->sellerConfig($sellerId);
                    foreach ($targetFailedIds as $id) {
                        $exportList[] = [
                            "channel" => $sellerChannel ?: $channelLabel,
                            "seller_id" => $sellerId,
                            "keyword_id" => (string)$id,
                            "message" => $targetErrorMsg[$id] ?? $keywordErrorMsg[$id] ?? "API操作失败",
                        ];
                    }
                }
            }
        }

        if (count($exportList) > 0) {
            $excelUtils = new ExcelUtils("sp/keyword/");
            $excelUtils->downloadXlsx([
                "channel",
                "seller_id",
                "keyword_id",
                "message",
            ], $exportList, "关停失败_{$channelLabel}_" . date("YmdHis") . ".xlsx", [2]);
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
        $adIdChannelMap = [];
        $totalIdCount = 0;
        try {
            $excelUtils->eachXlsxRow(__DIR__."/excel/{$file}", function ($item) use (&$sellerIds, &$adIdChannelMap, &$totalIdCount, $channel) {
                $sellerId = trim($item['seller_id'] ?? '');
                $id = trim(sprintf('%.0f', (float)($item['keyword_id'] ?? 0)), "'");
                $ch = trim($item['channel'] ?? '');
                if ($sellerId !== "" && $id !== "" && $id !== "0" && (empty($channel) || $ch === $channel)) {
                    $sellerIds[$sellerId][] = $id;
                    $adIdChannelMap[$sellerId . '_' . $id] = $ch;
                    $totalIdCount++;
                }
            });
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }

        $this->log("channel:{$channelLabel} 共 " . count($sellerIds) . " 个seller, {$totalIdCount} 个id");

        if (count($sellerIds) <= 0) {
            $this->log("verifyKeywordStates channel:{$channelLabel} 无数据");
            return;
        }

        $exportList = [];
        $verifiedCount = 0;
        $archivedCount = 0;
        $pausedCount = 0;
        $notPausedCount = 0;
        $notFoundCount = 0;

        foreach ($sellerIds as $sellerId => $ids) {
            $ids = array_values(array_unique($ids));
            $this->log("{$sellerId} 开始校验 " . count($ids) . " 个id");
            $sellerChannel = $spApi->sellerConfig($sellerId);

            // ===== 第一步：作为keyword查询 =====
            $keywordVerifiedIds = [];
            $keywordArchivedIds = [];
            foreach (array_chunk($ids, 100) as $chunk) {
                $keywordIdsStr = implode(",", $chunk);
                $this->log("查询Amazon API(keyword): {$sellerId} ids: {$keywordIdsStr}");

                $keywordListInfo = $spApi->listKeywordV2($sellerId, $keywordIdsStr);

                foreach ($chunk as $id) {
                    if (isset($keywordListInfo[$id])) {
                        $actualState = $keywordListInfo[$id]['state'];
                        // archived状态不统计
                        if ($actualState === 'archived') {
                            $keywordArchivedIds[] = $id;
                            continue;
                        }
                        $verifiedCount++;
                        $keywordVerifiedIds[] = $id;
                        if ($actualState === "paused") {
                            $pausedCount++;
                        } else {
                            $notPausedCount++;
                            $this->log("❌ {$sellerId} id:{$id} (keyword) 关停失败: 期望paused, 实际{$actualState}");
                            $exportList[] = [
                                "channel" => $sellerChannel ?: $channelLabel,
                                "seller_id" => $sellerId,
                                "keyword_id" => (string)$id,
                            ];
                        }
                    }
                }
            }
            if (count($keywordArchivedIds) > 0) {
                $archivedCount += count($keywordArchivedIds);
                $this->log("{$sellerId} 跳过archived(keyword): " . count($keywordArchivedIds) . "个");
            }

            // ===== 第二步：对未匹配keyword的id（排除archived），作为target查询 =====
            $targetCandidateIds = array_values(array_diff($ids, $keywordVerifiedIds, $keywordArchivedIds));
            if (count($targetCandidateIds) > 0) {
                $this->log("{$sellerId} 有 " . count($targetCandidateIds) . " 个id未匹配keyword，尝试作为target校验");
                $targetVerifiedIds = [];
                $targetArchivedIds = [];
                foreach (array_chunk($targetCandidateIds, 100) as $chunk) {
                    $targetIdsStr = implode(",", $chunk);
                    $this->log("查询Amazon API(target): {$sellerId} ids: {$targetIdsStr}");

                    $targetListInfo = $spApi->listTargetV2($sellerId, $targetIdsStr);

                    foreach ($chunk as $id) {
                        if (isset($targetListInfo[$id])) {
                            $actualState = $targetListInfo[$id]['state'];
                            // archived状态不统计
                            if ($actualState === 'archived') {
                                $targetArchivedIds[] = $id;
                                continue;
                            }
                            $verifiedCount++;
                            $targetVerifiedIds[] = $id;
                            if ($actualState === "paused") {
                                $pausedCount++;
                            } else {
                                $notPausedCount++;
                                $this->log("❌ {$sellerId} id:{$id} (target) 关停失败: 期望paused, 实际{$actualState}");
                                $exportList[] = [
                                    "channel" => $sellerChannel ?: $channelLabel,
                                    "seller_id" => $sellerId,
                                    "keyword_id" => (string)$id,
                                ];
                            }
                        }
                    }
                }
                if (count($targetArchivedIds) > 0) {
                    $archivedCount += count($targetArchivedIds);
                    $this->log("{$sellerId} 跳过archived(target): " . count($targetArchivedIds) . "个");
                }

                // ===== 第三步：keyword和target都查不到的id（排除archived） =====
                $notFoundIds = array_values(array_diff($targetCandidateIds, $targetVerifiedIds, $targetArchivedIds));
                if (count($notFoundIds) > 0) {
                    $notFoundCount += count($notFoundIds);
                    $this->log("{$sellerId} keyword和target都查不到的id: " . count($notFoundIds) . "个");
                }
            }
        }

        // 输出校验汇总
        $this->log("========== 校验汇总 ==========");
        $this->log("总数据: {$totalIdCount}");
        $this->log("⏭️ archived跳过: {$archivedCount}");
        $this->log("⚠️ 未找到(not_found): {$notFoundCount}");
        $this->log("已校验: {$verifiedCount}");
        $this->log("✅ 已暂停(paused): {$pausedCount}");
        $this->log("❌ 关停失败(非paused): {$notPausedCount}");

        // 导出关停失败的数据，格式与关停输入一致，可直接重新执行关停
        if (count($exportList) > 0) {
            $excelUtils = new ExcelUtils("sp/keyword/");
            $filePath = $excelUtils->downloadXlsx([
                "channel",
                "seller_id",
                "keyword_id",
            ], $exportList, "关停失败_keyword_{$channelLabel}_" . date("YmdHis") . ".xlsx", [2]);
            $this->log("关停失败数据已导出: {$filePath}");
        } else {
            $this->log("所有广告状态校验通过，无关停失败数据");
        }

        // 对关停失败的数据重新执行关停
        if (count($exportList) > 0) {
            $this->retryPausedKeyword($exportList, $channelLabel, $adIdChannelMap);
        }

        $this->log("verifyKeywordStates channel:{$channelLabel} 校验完毕");
    }


    /**
     * 对关停失败的keyword/target数据重新执行关停
     * 输入数据格式: [{"channel"=>"xxx", "seller_id"=>"xxx", "keyword_id"=>"xxx"}, ...]
     * 先作为keyword重试，失败的再作为target重试，成功则更新mongo，仍然失败的导出Excel
     *
     * 用法(由verify内部调用，也可单独使用):
     *   php SpPausedKeywordController.php method=retry file="关停失败_keyword_xxx.xlsx" channel=amazon_us
     *
     * @param array $failedList 关停失败数据列表，每项包含 channel/seller_id/keyword_id
     * @param string $channelLabel channel标签，用于日志和导出文件名
     * @param array $adIdChannelMap sellerId_keywordId => channel 映射，用于获取channel（从Excel读取时传入）
     */
    public function retryPausedKeyword($failedList = [], $channelLabel = '全部', $adIdChannelMap = []){
        if (count($failedList) <= 0) {
            $this->log("retryPausedKeyword 无需重试");
            return;
        }

        $this->log("========== 开始重新关停失败数据 ==========");
        $curlService = (new CurlService())->pro();
        $redisService = new RedisService();
        $spApi = new SpApi();

        // 按seller_id分组
        $retrySellerIds = [];
        foreach ($failedList as $item) {
            $retrySellerIds[$item['seller_id']][] = $item['keyword_id'];
        }

        $retrySuccessCount = 0;
        $retryFailedList = [];

        foreach ($retrySellerIds as $sellerId => $ids) {
            $sellerChannel = $spApi->sellerConfig($sellerId);
            $this->log("重新关停 {$sellerId} 共 " . count($ids) . " 个id");

            // 预加载Redis缓存，用于后续更新mongo
            $sellerKeywordList = $redisService->hGetAll("spKeyword_{$sellerId}");
            $sellerTargetList = $redisService->hGetAll("spTarget_{$sellerId}");

            // ===== 第一步：所有id先作为keyword重新关停 =====
            $keywordUpdateList = [];
            foreach ($ids as $id) {
                $keywordUpdateList[] = [
                    "keywordId" => $id,
                    "state" => "paused",
                ];
            }

            $keywordSuccessIds = [];
            $keywordFailedIds = [];
            $keywordErrorMsg = [];
            if (count($keywordUpdateList) > 0) {
                foreach (array_chunk($keywordUpdateList, 200) as $chunk) {
                    $this->log("{$sellerId} 重新关停keyword: " . count($chunk) . "个");
                    $pausedKeywordResult = $spApi->updateKeyword($sellerId, $chunk);
                    if (isset($pausedKeywordResult['success']) && count($pausedKeywordResult['success']) > 0) {
                        $this->log("{$sellerId} keyword重新关停成功: " . count($pausedKeywordResult['success']) . "个");
                        $batchUpdateList = [];
                        foreach ($pausedKeywordResult['success'] as $keywordId) {
                            $keywordSuccessIds[] = $keywordId;
                            $retrySuccessCount++;
                            if (isset($sellerKeywordList[$keywordId]) && $sellerKeywordList[$keywordId]) {
                                $batchUpdateList[] = [
                                    '_id' => $sellerKeywordList[$keywordId],
                                    'keywordId' => $keywordId,
                                    'state' => 'paused'
                                ];
                            }
                        }
                        if (!empty($batchUpdateList)) {
                            $spApi->batchMongoUpdateKeyword($batchUpdateList);
                        }
                    }
                    if (isset($pausedKeywordResult['error']) && count($pausedKeywordResult['error']) > 0) {
                        $keywordFailedIds = array_merge($keywordFailedIds, $pausedKeywordResult['error']);
                        $keywordErrorMsg = $keywordErrorMsg + ($pausedKeywordResult['errorMsg'] ?? []);
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
                            $batchUpdateList = [];
                            foreach ($list as $info) {
                                $seller = $spApi->specialSellerIdReverseConver($info['channel']);
                                $redisService->hSet("spKeyword_{$seller}", $info['keywordId'], $info['_id']);
                                $sellerKeywordList[$info['keywordId']] = $info['_id'];
                                $batchUpdateList[] = [
                                    '_id' => $info['_id'],
                                    'keywordId' => $info['keywordId'],
                                    'state' => 'paused'
                                ];
                            }
                            if (!empty($batchUpdateList)) {
                                $spApi->batchMongoUpdateKeyword($batchUpdateList);
                            }
                        }
                    }
                }
            }

            // ===== 第二步：keyword重试失败的id，尝试作为target重新关停 =====
            $keywordFailedIds = array_values(array_unique($keywordFailedIds));
            if (count($keywordFailedIds) > 0) {
                $this->log("{$sellerId} 有 " . count($keywordFailedIds) . " 个id keyword重试失败，尝试作为target重新关停");

                $targetUpdateList = [];
                foreach ($keywordFailedIds as $id) {
                    $targetUpdateList[] = [
                        "targetId" => $id,
                        "state" => "paused",
                    ];
                }

                $targetSuccessIds = [];
                $targetFailedIds = [];
                $targetErrorMsg = [];
                if (count($targetUpdateList) > 0) {
                    foreach (array_chunk($targetUpdateList, 200) as $chunk) {
                        $this->log("{$sellerId} 重新关停target: " . count($chunk) . "个");
                        $pausedTargetResult = $spApi->updateTarget($sellerId, $chunk);
                        if (isset($pausedTargetResult['success']) && count($pausedTargetResult['success']) > 0) {
                            $this->log("{$sellerId} target重新关停成功: " . count($pausedTargetResult['success']) . "个");
                            $batchUpdateList = [];
                            foreach ($pausedTargetResult['success'] as $targetId) {
                                $targetSuccessIds[] = $targetId;
                                $retrySuccessCount++;
                                if (isset($sellerTargetList[$targetId]) && $sellerTargetList[$targetId]) {
                                    $batchUpdateList[] = [
                                        '_id' => $sellerTargetList[$targetId],
                                        'targetId' => $targetId,
                                        'state' => 'paused'
                                    ];
                                }
                            }
                            if (!empty($batchUpdateList)) {
                                $spApi->batchMongoUpdateTarget($batchUpdateList);
                            }
                        }
                        if (isset($pausedTargetResult['error']) && count($pausedTargetResult['error']) > 0) {
                            $targetFailedIds = array_merge($targetFailedIds, $pausedTargetResult['error']);
                            $targetErrorMsg = $targetErrorMsg + ($pausedTargetResult['errorMsg'] ?? []);
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
                                $batchUpdateList = [];
                                foreach ($list as $info) {
                                    $seller = $spApi->specialSellerIdReverseConver($info['channel']);
                                    $redisService->hSet("spTarget_{$seller}", $info['targetId'], $info['_id']);
                                    $sellerTargetList[$info['targetId']] = $info['_id'];
                                    $batchUpdateList[] = [
                                        '_id' => $info['_id'],
                                        'targetId' => $info['targetId'],
                                        'state' => 'paused'
                                    ];
                                }
                                if (!empty($batchUpdateList)) {
                                    $spApi->batchMongoUpdateTarget($batchUpdateList);
                                }
                            }
                        }
                    }
                }

                // ===== 第三步：keyword和target都重试失败的id =====
                $targetFailedIds = array_values(array_unique($targetFailedIds));
                if (count($targetFailedIds) > 0) {
                    $this->log("{$sellerId} 有 " . count($targetFailedIds) . " 个id keyword和target都重试失败");
                    foreach ($targetFailedIds as $id) {
                        $itemChannel = !empty($adIdChannelMap) ? ($adIdChannelMap[$sellerId . '_' . $id] ?: ($sellerChannel ?: $channelLabel)) : ($sellerChannel ?: $channelLabel);
                        $retryFailedList[] = [
                            "channel" => $itemChannel,
                            "seller_id" => $sellerId,
                            "keyword_id" => (string)$id,
                            "message" => $targetErrorMsg[$id] ?? $keywordErrorMsg[$id] ?? "API关停失败",
                        ];
                    }
                }
            }
        }

        // 输出重新关停汇总
        $this->log("========== 重新关停汇总 ==========");
        $this->log("重新关停总数: " . count($failedList));
        $this->log("✅ 重新关停成功: {$retrySuccessCount}");
        $this->log("❌ 重新关停仍然失败: " . count($retryFailedList));

        // 导出仍然失败的数据
        if (count($retryFailedList) > 0) {
            $excelUtilsRetry = new ExcelUtils("sp/keyword/");
            $retryFilePath = $excelUtilsRetry->downloadXlsx([
                "channel",
                "seller_id",
                "keyword_id",
                "message",
            ], $retryFailedList, "重新关停仍失败_keyword_{$channelLabel}_" . date("YmdHis") . ".xlsx", [2]);
            $this->log("重新关停仍失败数据已导出: {$retryFilePath}");
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
} elseif ($method == 'retry') {
    // 从导出的关停失败Excel读取数据，重新执行关停
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
            $id = trim(sprintf('%.0f', (float)($item['keyword_id'] ?? 0)), "'");
            $sellerId = trim($item['seller_id'] ?? '');
            $ch = trim($item['channel'] ?? '');
            if ($id === '' || $id === '0' || $sellerId === '') {
                return;
            }
            if (!empty($channel) && $ch !== $channel) {
                return;
            }
            $failedList[] = [
                "channel" => $ch,
                "seller_id" => $sellerId,
                "keyword_id" => $id,
            ];
            $adIdChannelMap[$sellerId . '_' . $id] = $ch;
        });
    } catch (Exception $e) {
        die($e->getLine() . " : " . $e->getMessage());
    }
    $con->retryPausedKeyword($failedList, $channelLabel, $adIdChannelMap);
} else {
    $con->pausedKeywords($channel, $page);
}
