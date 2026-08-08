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

            // 先查询当前bid和state，跳过archived和bid相同的keyword
            $currentBidMap = [];
            $archivedCount = 0;
            foreach (array_chunk($keywordIds, 100) as $chunk) {
                $keywordIdsStr = implode(",", $chunk);
                $keywordListInfo = $spApi->listKeywordV2($sellerId, $keywordIdsStr);
                if ($keywordListInfo) {
                    foreach ($keywordListInfo as $kid => $info) {
                        if (isset($info['state']) && $info['state'] == 'archived') {
                            $archivedCount++;
                            $this->log("⏭️ {$sellerId} keywordId:{$kid} 状态为archived，跳过");
                            continue;
                        }
                        $currentBidMap[$kid] = (float)$info['bid'];
                    }
                }
            }

            $updateList = [];
            $skipCount = 0;
            foreach ($keywordBidMap as $keywordId => $bid) {
                $newBid = (float) $bid;
                if (!isset($currentBidMap[$keywordId])) {
                    // archived或未查到，跳过
                    continue;
                }
                if (bccomp($currentBidMap[$keywordId], $newBid, 2) === 0) {
                    $skipCount++;
                    $this->log("⏭️ {$sellerId} keywordId:{$keywordId} bid相同({$newBid})，跳过");
                    continue;
                }
                $updateList[] = [
                    "keywordId" => $keywordId,
                    "bid" => $newBid,
                ];
            }
            if ($archivedCount > 0) {
                $this->log("{$sellerId} 跳过archived的keyword: {$archivedCount}个");
            }
            if ($skipCount > 0) {
                $this->log("{$sellerId} 跳过bid相同的keyword: {$skipCount}个");
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
                        $batchUpdateList = [];
                        foreach ($chunk as $item) {
                            if (in_array($item['keywordId'], $updateKeywordResult['success']) && isset($sellerKeywordList[$item['keywordId']]) && $sellerKeywordList[$item['keywordId']]) {
                                $batchUpdateList[] = [
                                    '_id' => $sellerKeywordList[$item['keywordId']],
                                    'keywordId' => $item['keywordId'],
                                    'bid' => $item['bid']
                                ];
                            } elseif (in_array($item['keywordId'], $updateKeywordResult['success'])) {
                                $this->log("mongo不存在keyword但Amazon已处理成功: {$sellerId} - {$item['keywordId']}");
                            }
                        }
                        if (!empty($batchUpdateList)) {
                            $spApi->batchMongoUpdateKeyword($batchUpdateList);
                        }
                    }
                    if (isset($updateKeywordResult['error']) && count($updateKeywordResult['error']) > 0) {
                        $this->log("{$sellerId} 调整bid失败: " . count($updateKeywordResult['error']) . "个");
                        foreach ($chunk as $item) {
                            if (in_array($item['keywordId'], $updateKeywordResult['error'])) {
                                $exportList[] = [
                                    "seller_id" => $sellerId,
                                    "keyword_id" => (string)$item['keywordId'],
                                    "bid" => $item['bid'],
                                    "message" => $updateKeywordResult['errorMsg'][$item['keywordId']] ?? "API操作失败",
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
                "keyword_id",
                "bid",
                "message",
            ], $exportList, "调整keywordBid失败_" . date("YmdHis") . ".xlsx", [1]);
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

            // ===== 第一步：查询当前bid和state，跳过archived和bid相同的，剩余的先作为keyword尝试调整bid =====
            $currentKeywordBidMap = [];
            $keywordArchivedCount = 0;
            foreach (array_chunk($allIds, 100) as $chunk) {
                $keywordIdsStr = implode(",", $chunk);
                $keywordListInfo = $spApi->listKeywordV2($sellerId, $keywordIdsStr);
                if ($keywordListInfo) {
                    foreach ($keywordListInfo as $kid => $info) {
                        if (isset($info['state']) && $info['state'] == 'archived') {
                            $keywordArchivedCount++;
                            $this->log("⏭️ {$sellerId} id:{$kid} (keyword) 状态为archived，跳过");
                            continue;
                        }
                        $currentKeywordBidMap[$kid] = (float)$info['bid'];
                    }
                }
            }

            $keywordUpdateList = [];
            $keywordSkipCount = 0;
            $keywordNotFoundIds = []; // keyword查不到的id，需要顺延到target查询
            foreach ($idBidMap as $id => $bid) {
                $newBid = (float) $bid;
                if (!isset($currentKeywordBidMap[$id])) {
                    // keyword查不到，顺延到target查询
                    $keywordNotFoundIds[] = $id;
                    continue;
                }
                if (bccomp($currentKeywordBidMap[$id], $newBid, 2) === 0) {
                    $keywordSkipCount++;
                    continue;
                }
                $keywordUpdateList[] = [
                    "keywordId" => $id,
                    "bid" => $newBid,
                ];
            }
            if ($keywordArchivedCount > 0) {
                $this->log("{$sellerId} 跳过archived的keyword: {$keywordArchivedCount}个");
            }
            if (count($keywordNotFoundIds) > 0) {
                $this->log("{$sellerId} keyword未查到的id: " . count($keywordNotFoundIds) . "个，将顺延到target查询");
            }
            if ($keywordSkipCount > 0) {
                $this->log("{$sellerId} bid已一致的keyword: {$keywordSkipCount}个，跳过");
            }

            $keywordSuccessIds = [];
            $keywordFailedIds = [];
            $keywordErrorMsg = [];
            if (count($keywordUpdateList) > 0) {
                foreach (array_chunk($keywordUpdateList, 200) as $chunk) {
                    $this->log("{$sellerId} 调整keyword bid: " . count($chunk) . "个");
                    $updateKeywordResult = $spApi->updateKeyword($sellerId, $chunk);
                    if (isset($updateKeywordResult['success']) && count($updateKeywordResult['success']) > 0) {
                        $this->log("{$sellerId} keyword调整bid成功: " . count($updateKeywordResult['success']) . "个");
                        $batchUpdateList = [];
                        foreach ($chunk as $item) {
                            if (in_array($item['keywordId'], $updateKeywordResult['success'])) {
                                $keywordSuccessIds[] = $item['keywordId'];
                                if (isset($sellerKeywordList[$item['keywordId']]) && $sellerKeywordList[$item['keywordId']]) {
                                    $batchUpdateList[] = [
                                        '_id' => $sellerKeywordList[$item['keywordId']],
                                        'keywordId' => $item['keywordId'],
                                        'bid' => $item['bid']
                                    ];
                                }
                            }
                        }
                        if (!empty($batchUpdateList)) {
                            $spApi->batchMongoUpdateKeyword($batchUpdateList);
                        }
                    }
                    if (isset($updateKeywordResult['error']) && count($updateKeywordResult['error']) > 0) {
                        $keywordFailedIds = array_merge($keywordFailedIds, $updateKeywordResult['error']);
                        $keywordErrorMsg = array_merge($keywordErrorMsg, $updateKeywordResult['errorMsg'] ?? []);
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
                                $bid = $idBidMap[$info['keywordId']];
                                $batchUpdateList[] = [
                                    '_id' => $info['_id'],
                                    'keywordId' => $info['keywordId'],
                                    'bid' => (float)$bid
                                ];
                            }
                            if (!empty($batchUpdateList)) {
                                $spApi->batchMongoUpdateKeyword($batchUpdateList);
                            }
                        }
                    }
                }
            }

            // ===== 第二步：keyword调整bid失败的id + keyword查不到的id，查询target当前bid和state，跳过archived和bid相同的，再尝试作为target调整bid =====
            $keywordFailedIds = array_values(array_unique(array_merge($keywordFailedIds, $keywordNotFoundIds)));
            if (count($keywordFailedIds) > 0) {
                $this->log("{$sellerId} 有 " . count($keywordFailedIds) . " 个id (keyword失败" . count(array_unique(array_diff($keywordFailedIds, $keywordNotFoundIds))) . "个 + keyword未查到" . count($keywordNotFoundIds) . "个)，尝试作为target调整bid");

                // 查询target当前bid和state
                $currentTargetBidMap = [];
                $targetArchivedCount = 0;
                foreach (array_chunk($keywordFailedIds, 100) as $chunk) {
                    $targetIdsStr = implode(",", $chunk);
                    $targetListInfo = $spApi->listTargetV2($sellerId, $targetIdsStr);
                    if ($targetListInfo) {
                        foreach ($targetListInfo as $tid => $info) {
                            if (isset($info['state']) && $info['state'] == 'archived') {
                                $targetArchivedCount++;
                                $this->log("⏭️ {$sellerId} id:{$tid} (target) 状态为archived，跳过");
                                continue;
                            }
                            $currentTargetBidMap[$tid] = (float)$info['bid'];
                        }
                    }
                }

                $targetUpdateList = [];
                $targetSkipCount = 0;
                $targetNotFoundIds = []; // target也查不到的id
                foreach ($keywordFailedIds as $id) {
                    $newBid = (float) $idBidMap[$id];
                    if (!isset($currentTargetBidMap[$id])) {
                        // target也查不到，记录下来
                        $targetNotFoundIds[] = $id;
                        continue;
                    }
                    if (bccomp($currentTargetBidMap[$id], $newBid, 2) === 0) {
                        $targetSkipCount++;
                        continue;
                    }
                    $targetUpdateList[] = [
                        "targetId" => $id,
                        "bid" => $newBid,
                    ];
                }
                if ($targetArchivedCount > 0) {
                    $this->log("{$sellerId} 跳过archived的target: {$targetArchivedCount}个");
                }
                if (count($targetNotFoundIds) > 0) {
                    $this->log("{$sellerId} keyword和target都查不到的id: " . count($targetNotFoundIds) . "个");
                }
                if ($targetSkipCount > 0) {
                    $this->log("{$sellerId} bid已一致的target: {$targetSkipCount}个，跳过");
                }

                $targetSuccessIds = [];
                $targetFailedIds = [];
                $targetErrorMsg = [];
                if (count($targetUpdateList) > 0) {
                    foreach (array_chunk($targetUpdateList, 200) as $chunk) {
                        $this->log("{$sellerId} 调整target bid: " . count($chunk) . "个");
                        $updateTargetResult = $spApi->updateTarget($sellerId, $chunk);
                        if (isset($updateTargetResult['success']) && count($updateTargetResult['success']) > 0) {
                            $this->log("{$sellerId} target调整bid成功: " . count($updateTargetResult['success']) . "个");
                            $batchUpdateList = [];
                            foreach ($chunk as $item) {
                                if (in_array($item['targetId'], $updateTargetResult['success'])) {
                                    $targetSuccessIds[] = $item['targetId'];
                                    if (isset($sellerTargetList[$item['targetId']]) && $sellerTargetList[$item['targetId']]) {
                                        $batchUpdateList[] = [
                                            '_id' => $sellerTargetList[$item['targetId']],
                                            'targetId' => $item['targetId'],
                                            'bid' => $item['bid']
                                        ];
                                    }
                                }
                            }
                            if (!empty($batchUpdateList)) {
                                $spApi->batchMongoUpdateTarget($batchUpdateList);
                            }
                        }
                        if (isset($updateTargetResult['error']) && count($updateTargetResult['error']) > 0) {
                            $targetFailedIds = array_merge($targetFailedIds, $updateTargetResult['error']);
                            $targetErrorMsg = array_merge($targetErrorMsg, $updateTargetResult['errorMsg'] ?? []);
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
                                    $bid = $idBidMap[$info['targetId']];
                                    $batchUpdateList[] = [
                                        '_id' => $info['_id'],
                                        'targetId' => $info['targetId'],
                                        'bid' => (float)$bid
                                    ];
                                }
                                if (!empty($batchUpdateList)) {
                                    $spApi->batchMongoUpdateTarget($batchUpdateList);
                                }
                            }
                        }
                    }
                }

                // ===== 第三步：keyword和target都调整失败的id（不含not_found，状态异常不统计） =====
                if (count($targetFailedIds) > 0) {
                    $this->log("{$sellerId} 有 " . count($targetFailedIds) . " 个id更新bid失败");
                    $sellerChannel = $spApi->sellerConfig($sellerId);
                    foreach ($targetFailedIds as $id) {
                        $exportList[] = [
                            "channel" => $sellerChannel ?: $channelLabel,
                            "seller_id" => $sellerId,
                            "keyword_id" => (string)$id,
                            "bid" => $idBidMap[$id],
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
                "bid",
                "message",
            ], $exportList, "调整bid失败_{$channelLabel}_" . date("YmdHis") . ".xlsx", [2]);
        }

        $this->log("updateKeywordBidV2s channel:{$channelLabel} 处理完毕");
        $this->dingTalk();
    }

    /**
     * 重试更新bid失败的数据（也可由verify内部自动调用）
     * 输入数据格式: [{"channel"=>"xxx", "seller_id"=>"xxx", "keyword_id"=>"xxx", "bid"=>"xxx"}, ...]
     * 先作为keyword重试，失败的再作为target重试，成功则更新mongo，仍然失败的导出Excel
     *
     * 用法(由verify内部调用，也可单独使用):
     *   php SpUpdateKeywordBidController.php method=retry file="调整bid失败_xxx.xlsx" channel=amazon_us
     *       文件先从export/目录查找（verify导出的文件在此），找不到再从excel/目录查找
     *
     * @param array $failedList 失败数据列表，每项包含 channel/seller_id/keyword_id/bid
     * @param string $channelLabel channel标签，用于日志和导出文件名
     */
    public function retryUpdateKeywordBid($failedList = [], $channelLabel = '全部')
    {
        $this->log("retryUpdateKeywordBid 开始重试 " . count($failedList) . " 条数据");

        $curlService = (new CurlService())->pro();
        $redisService = new RedisService();
        $spApi = new SpApi();

        // 按seller_id分组: $sellerIdBidMap[$sellerId][$id] = $bid，并记录每条的channel
        $sellerIdBidMap = [];
        $adIdChannelMap = [];
        $totalIdCount = 0;
        foreach ($failedList as $item) {
            $sellerId = trim($item['seller_id'] ?? '');
            // 兼容两种格式：新格式用keyword_id，旧格式用id
            $id = trim(sprintf('%.0f', (float)($item['keyword_id'] ?? ($item['id'] ?? 0))), "'");
            $bid = trim((string)($item['bid'] ?? ''));
            if ($sellerId === "" || $id === "" || $id === "0" || $bid === "") {
                continue;
            }
            $sellerIdBidMap[$sellerId][$id] = $bid;
            $adIdChannelMap[$sellerId . '_' . $id] = trim($item['channel'] ?? '');
            $totalIdCount++;
        }

        $this->log("共 " . count($sellerIdBidMap) . " 个seller, {$totalIdCount} 个id待重试");

        if (count($sellerIdBidMap) <= 0) {
            $this->log("retryUpdateKeywordBid 无数据");
            return;
        }

        $exportList = [];
        foreach ($sellerIdBidMap as $sellerId => $idBidMap) {
            $allIds = array_keys($idBidMap);
            $this->log("{$sellerId} 共 " . count($allIds) . " 个id待重试");

            // 预加载Redis缓存
            $sellerKeywordList = $redisService->hGetAll("spKeyword_{$sellerId}");
            $sellerTargetList = $redisService->hGetAll("spTarget_{$sellerId}");

            // ===== 第一步：查询当前bid和state，跳过archived和bid相同的，先作为keyword尝试 =====
            $currentKeywordBidMap = [];
            $keywordArchivedCount = 0;
            foreach (array_chunk($allIds, 100) as $chunk) {
                $keywordIdsStr = implode(",", $chunk);
                $keywordListInfo = $spApi->listKeywordV2($sellerId, $keywordIdsStr);
                if ($keywordListInfo) {
                    foreach ($keywordListInfo as $kid => $info) {
                        if (isset($info['state']) && $info['state'] == 'archived') {
                            $keywordArchivedCount++;
                            $this->log("⏭️ {$sellerId} id:{$kid} (keyword) 状态为archived，跳过");
                            continue;
                        }
                        $currentKeywordBidMap[$kid] = (float)$info['bid'];
                    }
                }
            }

            $keywordUpdateList = [];
            $keywordSkipCount = 0;
            $keywordNotFoundIds = []; // keyword查不到的id，需要顺延到target查询
            foreach ($idBidMap as $id => $bid) {
                $newBid = (float) $bid;
                if (!isset($currentKeywordBidMap[$id])) {
                    // keyword查不到，顺延到target查询
                    $keywordNotFoundIds[] = $id;
                    continue;
                }
                if (bccomp($currentKeywordBidMap[$id], $newBid, 2) === 0) {
                    $keywordSkipCount++;
                    continue;
                }
                $keywordUpdateList[] = [
                    "keywordId" => $id,
                    "bid" => $newBid,
                ];
            }
            if ($keywordArchivedCount > 0) {
                $this->log("{$sellerId} 跳过archived的keyword: {$keywordArchivedCount}个");
            }
            if (count($keywordNotFoundIds) > 0) {
                $this->log("{$sellerId} keyword未查到的id: " . count($keywordNotFoundIds) . "个，将顺延到target查询");
            }
            if ($keywordSkipCount > 0) {
                $this->log("{$sellerId} bid已一致的keyword: {$keywordSkipCount}个，跳过");
            }

            $keywordSuccessIds = [];
            $keywordFailedIds = [];
            $keywordErrorMsg = [];
            if (count($keywordUpdateList) > 0) {
                foreach (array_chunk($keywordUpdateList, 200) as $chunk) {
                    $this->log("{$sellerId} 重试调整keyword bid: " . count($chunk) . "个");
                    $updateKeywordResult = $spApi->updateKeyword($sellerId, $chunk);
                    if (isset($updateKeywordResult['success']) && count($updateKeywordResult['success']) > 0) {
                        $this->log("{$sellerId} keyword重试成功: " . count($updateKeywordResult['success']) . "个");
                        $batchUpdateList = [];
                        foreach ($chunk as $item) {
                            if (in_array($item['keywordId'], $updateKeywordResult['success'])) {
                                $keywordSuccessIds[] = $item['keywordId'];
                                if (isset($sellerKeywordList[$item['keywordId']]) && $sellerKeywordList[$item['keywordId']]) {
                                    $batchUpdateList[] = [
                                        '_id' => $sellerKeywordList[$item['keywordId']],
                                        'keywordId' => $item['keywordId'],
                                        'bid' => $item['bid']
                                    ];
                                }
                            }
                        }
                        if (!empty($batchUpdateList)) {
                            $spApi->batchMongoUpdateKeyword($batchUpdateList);
                        }
                    }
                    if (isset($updateKeywordResult['error']) && count($updateKeywordResult['error']) > 0) {
                        $keywordFailedIds = array_merge($keywordFailedIds, $updateKeywordResult['error']);
                        $keywordErrorMsg = array_merge($keywordErrorMsg, $updateKeywordResult['errorMsg'] ?? []);
                    }
                }
            }

            // 补查mongo中keyword的_id
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
                                $bid = $idBidMap[$info['keywordId']];
                                $batchUpdateList[] = [
                                    '_id' => $info['_id'],
                                    'keywordId' => $info['keywordId'],
                                    'bid' => (float)$bid
                                ];
                            }
                            if (!empty($batchUpdateList)) {
                                $spApi->batchMongoUpdateKeyword($batchUpdateList);
                            }
                        }
                    }
                }
            }

            // ===== 第二步：keyword仍然失败的 + keyword查不到的，查询target当前bid，跳过bid相同的，尝试作为target =====
            $keywordFailedIds = array_values(array_unique(array_merge($keywordFailedIds, $keywordNotFoundIds)));
            if (count($keywordFailedIds) > 0) {
                $this->log("{$sellerId} 有 " . count($keywordFailedIds) . " 个id (keyword重试失败" . count(array_unique(array_diff($keywordFailedIds, $keywordNotFoundIds))) . "个 + keyword未查到" . count($keywordNotFoundIds) . "个)，尝试作为target调整bid");

                $currentTargetBidMap = [];
                $targetArchivedCount = 0;
                foreach (array_chunk($keywordFailedIds, 100) as $chunk) {
                    $targetIdsStr = implode(",", $chunk);
                    $targetListInfo = $spApi->listTargetV2($sellerId, $targetIdsStr);
                    if ($targetListInfo) {
                        foreach ($targetListInfo as $tid => $info) {
                            if (isset($info['state']) && $info['state'] == 'archived') {
                                $targetArchivedCount++;
                                $this->log("⏭️ {$sellerId} id:{$tid} (target) 状态为archived，跳过");
                                continue;
                            }
                            $currentTargetBidMap[$tid] = (float)$info['bid'];
                        }
                    }
                }

                $targetUpdateList = [];
                $targetSkipCount = 0;
                $targetNotFoundIds = []; // target也查不到的id
                foreach ($keywordFailedIds as $id) {
                    $newBid = (float) $idBidMap[$id];
                    if (!isset($currentTargetBidMap[$id])) {
                        // target也查不到，记录下来
                        $targetNotFoundIds[] = $id;
                        continue;
                    }
                    if (bccomp($currentTargetBidMap[$id], $newBid, 2) === 0) {
                        $targetSkipCount++;
                        $this->log("⏭️ {$sellerId} id:{$id} (target) bid已一致({$newBid})，跳过");
                        continue;
                    }
                    $targetUpdateList[] = [
                        "targetId" => $id,
                        "bid" => $newBid,
                    ];
                }
                if ($targetArchivedCount > 0) {
                    $this->log("{$sellerId} 跳过archived的target: {$targetArchivedCount}个");
                }
                if (count($targetNotFoundIds) > 0) {
                    $this->log("{$sellerId} keyword和target都查不到的id: " . count($targetNotFoundIds) . "个");
                }
                if ($targetSkipCount > 0) {
                    $this->log("{$sellerId} 跳过bid已一致的target: {$targetSkipCount}个");
                }

                $targetSuccessIds = [];
                $targetFailedIds = [];
                $targetErrorMsg = [];
                if (count($targetUpdateList) > 0) {
                    foreach (array_chunk($targetUpdateList, 200) as $chunk) {
                        $this->log("{$sellerId} 重试调整target bid: " . count($chunk) . "个");
                        $updateTargetResult = $spApi->updateTarget($sellerId, $chunk);
                        if (isset($updateTargetResult['success']) && count($updateTargetResult['success']) > 0) {
                            $this->log("{$sellerId} target重试成功: " . count($updateTargetResult['success']) . "个");
                            $batchUpdateList = [];
                            foreach ($chunk as $item) {
                                if (in_array($item['targetId'], $updateTargetResult['success'])) {
                                    $targetSuccessIds[] = $item['targetId'];
                                    if (isset($sellerTargetList[$item['targetId']]) && $sellerTargetList[$item['targetId']]) {
                                        $batchUpdateList[] = [
                                            '_id' => $sellerTargetList[$item['targetId']],
                                            'targetId' => $item['targetId'],
                                            'bid' => $item['bid']
                                        ];
                                    }
                                }
                            }
                            if (!empty($batchUpdateList)) {
                                $spApi->batchMongoUpdateTarget($batchUpdateList);
                            }
                        }
                        if (isset($updateTargetResult['error']) && count($updateTargetResult['error']) > 0) {
                            $targetFailedIds = array_merge($targetFailedIds, $updateTargetResult['error']);
                            $targetErrorMsg = array_merge($targetErrorMsg, $updateTargetResult['errorMsg'] ?? []);
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
                                    $bid = $idBidMap[$info['targetId']];
                                    $batchUpdateList[] = [
                                        '_id' => $info['_id'],
                                        'targetId' => $info['targetId'],
                                        'bid' => (float)$bid
                                    ];
                                }
                                if (!empty($batchUpdateList)) {
                                    $spApi->batchMongoUpdateTarget($batchUpdateList);
                                }
                            }
                        }
                    }
                }

                // ===== 仍然失败的（不含not_found，状态异常不统计） =====
                if (count($targetFailedIds) > 0) {
                    $this->log("{$sellerId} 有 " . count($targetFailedIds) . " 个id重试仍失败");
                    $sellerChannel = $spApi->sellerConfig($sellerId);
                    foreach ($targetFailedIds as $id) {
                        $itemChannel = $adIdChannelMap[$sellerId . '_' . $id] ?: ($sellerChannel ?: $channelLabel);
                        $exportList[] = [
                            "channel" => $itemChannel,
                            "seller_id" => $sellerId,
                            "keyword_id" => (string)$id,
                            "bid" => $idBidMap[$id],
                            "message" => $targetErrorMsg[$id] ?? $keywordErrorMsg[$id] ?? "API调整bid失败",
                        ];
                    }
                }
            }
        }

        if (count($exportList) > 0) {
            $excelUtils = new ExcelUtils("sp/keyword/");
            $filePath = $excelUtils->downloadXlsx([
                "channel",
                "seller_id",
                "keyword_id",
                "bid",
                "message",
            ], $exportList, "重试bid仍失败_{$channelLabel}_" . date("YmdHis") . ".xlsx", [2]);
            $this->log("仍失败数据已导出: {$filePath}");
        } else {
            $this->log("所有bid重试成功，无失败数据");
        }

        $this->log("retryUpdateKeywordBid 处理完毕");
    }

    /**
     * 校验keyword/target广告的bid是否正确调整（不传channel则校验全部）
     * 先尝试作为keyword校验，查不到的再尝试作为target校验
     * archived状态的数据不统计，只统计bid不一致的数据并导出
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

        if (count($sellerIdBidMap) <= 0) {
            $this->log("verifyKeywordBidStates channel:{$channelLabel} 无数据");
            return;
        }

        $exportList = [];
        $verifiedCount = 0;
        $bidMatchCount = 0;
        $bidMismatchCount = 0;
        $archivedCount = 0;
        $notFoundCount = 0;

        foreach ($sellerIdBidMap as $sellerId => $idBidMap) {
            $allIds = array_keys($idBidMap);
            $this->log("{$sellerId} 开始校验 " . count($allIds) . " 个id");
            $sellerChannel = $spApi->sellerConfig($sellerId);

            // ===== 第一步：作为keyword查询 =====
            $keywordVerifiedIds = [];
            $keywordArchivedIds = [];
            foreach (array_chunk($allIds, 100) as $chunk) {
                $keywordIdsStr = implode(",", $chunk);
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
                        $expectedBid = $idBidMap[$id];
                        $actualBid = $keywordListInfo[$id]['bid'];

                        if (bccomp((string)$expectedBid, (string)$actualBid, 2) === 0) {
                            $bidMatchCount++;
                        } else {
                            $bidMismatchCount++;
                            $this->log("❌ {$sellerId} id:{$id} (keyword) bid不一致: 期望{$expectedBid}, 实际{$actualBid}");
                            $exportList[] = [
                                "channel" => $sellerChannel ?: $channelLabel,
                                "seller_id" => $sellerId,
                                "keyword_id" => (string)$id,
                                "bid" => (string)$expectedBid,
                                "actual_bid" => (string)$actualBid,
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
            $targetCandidateIds = array_values(array_diff($allIds, $keywordVerifiedIds, $keywordArchivedIds));
            if (count($targetCandidateIds) > 0) {
                $this->log("{$sellerId} 有 " . count($targetCandidateIds) . " 个id未匹配keyword，尝试作为target校验");
                $targetVerifiedIds = [];
                $targetArchivedIds = [];
                foreach (array_chunk($targetCandidateIds, 100) as $chunk) {
                    $targetIdsStr = implode(",", $chunk);
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
                            $expectedBid = $idBidMap[$id];
                            $actualBid = $targetListInfo[$id]['bid'];

                            if (bccomp((string)$expectedBid, (string)$actualBid, 2) === 0) {
                                $bidMatchCount++;
                            } else {
                                $bidMismatchCount++;
                                $this->log("❌ {$sellerId} id:{$id} (target) bid不一致: 期望{$expectedBid}, 实际{$actualBid}");
                                $exportList[] = [
                                    "channel" => $sellerChannel ?: $channelLabel,
                                    "seller_id" => $sellerId,
                                    "keyword_id" => (string)$id,
                                    "bid" => (string)$expectedBid,
                                    "actual_bid" => (string)$actualBid,
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
        $this->log("⚠️ 未找到: {$notFoundCount}");
        $this->log("已校验: {$verifiedCount}");
        $this->log("✅ bid一致: {$bidMatchCount}");
        $this->log("❌ bid不一致: {$bidMismatchCount}");

        // 导出bid不一致的数据到Excel
        if (count($exportList) > 0) {
            $excelUtils = new ExcelUtils("sp/keyword/");
            $filePath = $excelUtils->downloadXlsx([
                "channel",
                "seller_id",
                "keyword_id",
                "bid",
                "actual_bid",
            ], $exportList, "校验bid不一致_{$channelLabel}_" . date("YmdHis") . ".xlsx", [2]);
            $this->log("bid不一致数据已导出: {$filePath}");
        } else {
            $this->log("所有bid校验一致，无不一致数据");
        }

        // 对bid不一致的数据重新调整bid
        if (count($exportList) > 0) {
            $this->retryUpdateKeywordBid($exportList, $channelLabel);
        }

        $this->log("verifyKeywordBidStates channel:{$channelLabel} 校验完毕");
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
} elseif ($method == 'retry') {
    // 从导出的失败Excel读取数据，重新执行bid调整
    $channelLabel = empty($channel) ? '全部' : $channel;
    $excelUtils = new ExcelUtils();
    $failedList = [];
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
        $excelUtils->eachXlsxRow($filePath, function ($item) use (&$failedList, $channel) {
            $sellerId = trim($item['seller_id'] ?? '');
            // 兼容两种格式：新格式用keyword_id，旧格式用id
            $id = trim(sprintf('%.0f', (float)($item['keyword_id'] ?? ($item['id'] ?? 0))), "'");
            $bid = trim((string)($item['bid'] ?? ''));
            $ch = trim($item['channel'] ?? '');
            if ($sellerId === "" || $id === "" || $id === "0" || $bid === "") {
                return;
            }
            if (!empty($channel) && $ch !== $channel) {
                return;
            }
            $failedList[] = [
                "channel" => $ch,
                "seller_id" => $sellerId,
                "keyword_id" => $id,
                "bid" => $bid,
            ];
        });
    } catch (Exception $e) {
        die($e->getLine() . " : " . $e->getMessage());
    }
    $con->retryUpdateKeywordBid($failedList, $channelLabel);
} else {
    $con->updateKeywordBid($channel, $page);
}
