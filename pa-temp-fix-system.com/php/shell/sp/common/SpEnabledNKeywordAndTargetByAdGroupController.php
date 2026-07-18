<?php
require_once(dirname(__FILE__) . "/../../../../php/requiredfile/requiredfile.php");
require_once(dirname(__FILE__) . "/../../../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/RequestUtils.php");
require_once(dirname(__FILE__) . "/../SpApi.php");

class SpEnabledNKeywordAndTargetByAdGroupController
{
    private $log;
    private $spApi;
    private $excelUtils;
    private $redis;

    private $adGroupCache = [];
    private $keywordCache = [];
    private $targetCache = [];
    private $negativeKeywordCache = [];
    private $negativeTargetCache = [];

    private $fileMap = [];
    private $adTypeAliasMap = [];

    public function __construct()
    {
        $this->bootstrapRuntimeSettings();
        $this->log = new MyLogger("sp/common");
        $this->spApi = new SpApi();
        $this->excelUtils = new ExcelUtils();
        $this->redis = new RedisService();
        $this->fileMap = [
            "target" => "M5增投asin广告.xlsx",
            "keyword" => "M5增投关键词广告.xlsx",
            "negativeTarget" => "M5增投否定asin.xlsx",
            "negativeKeyword" => "M5增投否定关键词.xlsx",
        ];
        $this->adTypeAliasMap = [
            "keyword" => "keyword",
            "keywords" => "keyword",
            "target" => "target",
            "asin" => "target",
            "negativekeyword" => "negativeKeyword",
            "negative_keyword" => "negativeKeyword",
            "negative-keyword" => "negativeKeyword",
            "negkeyword" => "negativeKeyword",
            "neg_kw" => "negativeKeyword",
            "negativetarget" => "negativeTarget",
            "negative_target" => "negativeTarget",
            "negative-target" => "negativeTarget",
            "negativeasin" => "negativeTarget",
            "negtarget" => "negativeTarget",
            "neg_asin" => "negativeTarget",
        ];
    }

    private function bootstrapRuntimeSettings()
    {
        if (function_exists('ini_set')) {
            @ini_set('pcre.jit', '0');
            @ini_set('memory_limit', '2048M');
        }
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }
    }

    private function log(string $string = "")
    {
        $this->log->log2($string);
    }

    public function dingTalk($channel, $page, $message)
    {
        $proCurlService = new CurlService();
        $ali = $proCurlService->test()->phpali();

        $datetime = date("Y-m-d H:i:s", time());
        $postData = array(
            'userType' => 'userName',
            'userIdList' => "zhouangang",
            'title' => "【按广告组补充投放完成】提醒",
            'msg' => [
                [
                    "key" => "",
                    "value" => "{$datetime} channel={$channel} page={$page} {$message}"
                ]
            ]
        );
        $ali->post("dingding/sendOaNotice", $postData);
        return $this;
    }

    public function enabled($channel = '', $page = 0, $adType = '')
    {
        $adType = $this->normalizeAdType($adType);
        $this->log("开始处理补充投放: channel={$channel}, page={$page}, adType={$adType}");
        $tasks = $this->loadTasks($channel, $adType, $page);
        if (count($tasks) <= 0) {
            $this->log("没有可处理的数据");
            return;
        }

        $exportList = [];
        foreach ($tasks as $type => $sellerRows) {
            foreach ($sellerRows as $sellerId => $rows) {
                $this->log("处理 {$type} seller={$sellerId} 数量=" . count($rows));
                $exportList = array_merge($exportList, $this->processTypeRows($type, $sellerId, $rows));
            }
        }

        if (count($exportList) > 0) {
            $exportExcel = new ExcelUtils("sp/common/");
            $exportExcel->downloadXlsx([
                "type",
                "channel",
                "seller_id",
                "ad_group_id",
                "campaign_id",
                "name",
                "match_type",
                "bid",
                "reason",
            ], $exportList, "补充投放失败_{$adType}_{$channel}_{$page}_" . date("YmdHis") . ".xlsx");
        }

        $summary = count($exportList) > 0 ? ("完成，失败 " . count($exportList) . " 条") : "完成，全部处理成功";
        $this->dingTalk($channel, $page, $summary);
    }

    private function loadTasks($channel, $adType, $page)
    {
        $tasks = [];
        $selectedFiles = [$adType => $this->resolveExcelFilePath($adType, $page)];
        foreach ($selectedFiles as $type => $filePath) {
            $tasks[$type] = [];
            $this->log("读取文件: {$filePath}");
            $rowCount = 0;
            $appendRow = function ($row) use (&$tasks, $type, $channel, &$rowCount) {
                $rowCount++;
                $normalized = $this->normalizeRow($type, $row);
                if (!$normalized) {
                    return;
                }
                if ($channel !== '' && $normalized['channel'] !== $channel) {
                    return;
                }
                $tasks[$type][$normalized['seller_id']][] = $normalized;
            };

            $this->excelUtils->eachXlsxRow($filePath, $appendRow);

            if ($rowCount <= 1 && strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'xlsx') {
                $this->log("流式读取仅返回{$rowCount}行，回退到PHPExcel读取: {$filePath}");
                $tasks[$type] = [];
                $rows = $this->excelUtils->getXlsxDataV2($filePath, 'Sheet1');
                foreach ($rows as $row) {
                    $appendRow($row);
                }
            }
        }

        return $tasks;
    }

    private function normalizeRow($type, $row)
    {
        $channel = trim((string)($row['channel'] ?? ''));
        $sellerId = trim((string)($row['seller_id'] ?? ''));
        $adGroupId = trim((string)($row['ad_group_id'] ?? ''), "'");
        if ($channel === '' || $sellerId === '' || $adGroupId === '') {
            return null;
        }

        if ($type === 'keyword') {
            $name = trim((string)($row['keyword'] ?? ''));
            $matchType = $this->normalizeKeywordMatchType($row['匹配方式'] ?? '');
            $bid = $row['BID'] ?? ($row['bid'] ?? '');
            if ($name === '' || $matchType === null) {
                return null;
            }
            return [
                "type" => $type,
                "channel" => $channel,
                "seller_id" => $sellerId,
                "ad_group_id" => $adGroupId,
                "name" => $name,
                "match_type" => $matchType,
                "bid" => $bid,
            ];
        }

        if ($type === 'target') {
            $name = strtoupper(trim((string)($row['asin'] ?? '')));
            $bid = $row['bid'] ?? '';
            if ($name === '') {
                return null;
            }
            return [
                "type" => $type,
                "channel" => $channel,
                "seller_id" => $sellerId,
                "ad_group_id" => $adGroupId,
                "name" => $name,
                "match_type" => "asinSameAs",
                "bid" => $bid,
            ];
        }

        if ($type === 'negativeKeyword') {
            $name = trim((string)($row['keyword'] ?? ''));
            $matchType = $this->normalizeNegativeKeywordMatchType($row['type'] ?? '');
            if ($name === '' || $matchType === null) {
                return null;
            }
            return [
                "type" => $type,
                "channel" => $channel,
                "seller_id" => $sellerId,
                "ad_group_id" => $adGroupId,
                "name" => $name,
                "match_type" => $matchType,
                "bid" => "",
            ];
        }

        $name = strtoupper(trim((string)($row['asin'] ?? '')));
        if ($name === '') {
            return null;
        }
        return [
            "type" => $type,
            "channel" => $channel,
            "seller_id" => $sellerId,
            "ad_group_id" => $adGroupId,
            "name" => $name,
            "match_type" => "asinSameAs",
            "bid" => "",
        ];
    }

    private function processTypeRows($type, $sellerId, $rows)
    {
        $payloads = [];
        $exportList = [];
        $uniqueKeys = [];
        $this->prefetchAdGroupInfos($sellerId, $rows);

        foreach ($rows as $row) {
            $adGroupInfo = $this->resolveAdGroupInfo($sellerId, $row['ad_group_id']);
            if (!$adGroupInfo || empty($adGroupInfo['campaignId'])) {
                $exportList[] = $this->buildExportRow($row, '', '找不到campaignId');
                continue;
            }

            $campaignId = (string)$adGroupInfo['campaignId'];
            $bid = $this->normalizeBid($row['bid'], $adGroupInfo['defaultBid'] ?? null);
            if (($type === 'keyword' || $type === 'target') && $bid === null) {
                $exportList[] = $this->buildExportRow($row, $campaignId, 'bid为空且广告组默认竞价不存在');
                continue;
            }

            if ($this->existsInAmazon($type, $sellerId, $campaignId, $row['ad_group_id'], $row['name'], $row['match_type'])) {
                $this->log("已存在，跳过: {$type} {$sellerId} {$row['ad_group_id']} {$row['name']}");
                continue;
            }

            $uniqueKey = $sellerId . "_" . $campaignId . "_" . $row['ad_group_id'] . "_" . $row['match_type'] . "_" . strtolower($row['name']);
            if (isset($uniqueKeys[$uniqueKey])) {
                continue;
            }
            $uniqueKeys[$uniqueKey] = true;

            $payloads[] = [
                "seller_id" => $sellerId,
                "campaign_id" => $campaignId,
                "ad_group_id" => $row['ad_group_id'],
                "name" => $row['name'],
                "match_type" => $row['match_type'],
                "bid" => $bid,
                "payload" => $this->buildCreatePayload($type, $campaignId, $row['ad_group_id'], $row['name'], $row['match_type'], $bid),
                "row" => $row,
            ];
        }

        foreach (array_chunk($payloads, 100) as $chunk) {
            $exportList = array_merge($exportList, $this->createChunk($type, $sellerId, $chunk));
        }

        return $exportList;
    }

    private function prefetchAdGroupInfos($sellerId, $rows)
    {
        $adGroupIds = [];
        foreach ($rows as $row) {
            if (!empty($row['ad_group_id'])) {
                $adGroupIds[] = (string)$row['ad_group_id'];
            }
        }
        $adGroupIds = array_values(array_unique($adGroupIds));
        if (count($adGroupIds) <= 0) {
            return;
        }

        $missingIds = [];
        foreach ($adGroupIds as $adGroupId) {
            $cacheKey = $sellerId . "_" . $adGroupId;
            if (!isset($this->adGroupCache[$cacheKey])) {
                $missingIds[] = $adGroupId;
            }
        }
        if (count($missingIds) <= 0) {
            return;
        }

        $redisMissingIds = [];
        foreach ($missingIds as $adGroupId) {
            $cacheKey = $sellerId . "_" . $adGroupId;
            $redisValue = $this->redis->hGet("spEnabledAdGroupInfoCache", $cacheKey);
            if ($redisValue) {
                $decoded = json_decode($redisValue, true);
                if (is_array($decoded) && !empty($decoded['campaignId'])) {
                    $this->adGroupCache[$cacheKey] = $decoded;
                    continue;
                }
            }
            $redisMissingIds[] = $adGroupId;
        }
        if (count($redisMissingIds) <= 0) {
            return;
        }

        foreach (array_chunk($redisMissingIds, 200) as $chunk) {
            $mongoList = $this->spApi->getMongoAdGroups($chunk);
            $foundIds = [];
            foreach ($mongoList as $item) {
                $itemSeller = $this->spApi->specialSellerIdReverseConver($item['channel'] ?? '');
                $adGroupId = (string)($item['adGroupId'] ?? '');
                if ($itemSeller === $sellerId && $adGroupId !== '') {
                    $this->rememberAdGroupInfo($sellerId, $adGroupId, $item);
                    $foundIds[$adGroupId] = true;
                }
            }

            $amazonMissingIds = [];
            foreach ($chunk as $adGroupId) {
                if (!isset($foundIds[$adGroupId])) {
                    $amazonMissingIds[] = $adGroupId;
                }
            }

            if (count($amazonMissingIds) > 0) {
                $amazonList = $this->spApi->getAmazonAdGroupInfoByIds($sellerId, $amazonMissingIds);
                foreach ($amazonList as $item) {
                    $adGroupId = (string)($item['adGroupId'] ?? '');
                    if ($adGroupId !== '') {
                        $this->rememberAdGroupInfo($sellerId, $adGroupId, $item);
                        $foundIds[$adGroupId] = true;
                    }
                }
            }

            foreach ($chunk as $adGroupId) {
                $cacheKey = $sellerId . "_" . $adGroupId;
                if (!isset($this->adGroupCache[$cacheKey])) {
                    $this->adGroupCache[$cacheKey] = [];
                }
            }
        }
    }

    private function rememberAdGroupInfo($sellerId, $adGroupId, $info)
    {
        $cacheKey = $sellerId . "_" . $adGroupId;
        $this->adGroupCache[$cacheKey] = $info;
        if (is_array($info) && !empty($info['campaignId'])) {
            $this->redis->hSet("spEnabledAdGroupInfoCache", $cacheKey, json_encode($info, JSON_UNESCAPED_UNICODE));
        }
    }

    private function createChunk($type, $sellerId, $chunk)
    {
        $payloadList = array_column($chunk, 'payload');
        if ($type === 'keyword') {
            $result = $this->spApi->createKeywords($sellerId, $payloadList);
        } elseif ($type === 'target') {
            $result = $this->spApi->createTargets($sellerId, $payloadList);
        } elseif ($type === 'negativeKeyword') {
            $result = $this->spApi->createNegativeKeywords($sellerId, $payloadList);
        } else {
            $result = $this->spApi->createNegativeTargets($sellerId, $payloadList);
        }

        $exportList = [];
        foreach ($result['success'] ?? [] as $success) {
            $meta = $chunk[$success['index']];
            $campaignId = $meta['campaign_id'];
            $adGroupId = $meta['ad_group_id'];
            $name = $meta['name'];
            $matchType = $meta['match_type'];
            $entityId = $success['id'];

            if ($type === 'keyword') {
                $this->spApi->mongoCreateKeyword($sellerId, $campaignId, $adGroupId, $name, $matchType, $entityId, ["defaultBid" => $meta['bid']]);
                $cacheKey = $this->getCacheKey($sellerId, $campaignId, $adGroupId);
                $this->keywordCache[$cacheKey][$matchType . "_" . $name] = ["keywordId" => $entityId, "bid" => $meta['bid']];
            } elseif ($type === 'target') {
                $this->spApi->mongoCreateTargetAsin($sellerId, $campaignId, $adGroupId, $name, [
                    "targetId" => $entityId,
                    "bid" => $meta['bid']
                ]);
                $cacheKey = $this->getCacheKey($sellerId, $campaignId, $adGroupId);
                $this->targetCache[$cacheKey][$name] = ["targetId" => $entityId, "bid" => $meta['bid']];
            } elseif ($type === 'negativeKeyword') {
                $this->spApi->mongoCreateNegativeKeyword($sellerId, $campaignId, $adGroupId, $name, $matchType, $entityId);
                $cacheKey = $this->getCacheKey($sellerId, $campaignId, $adGroupId);
                $this->negativeKeywordCache[$cacheKey][$matchType . "_" . $name] = ["keywordId" => $entityId];
            } else {
                $this->spApi->mongoCreateNegativeTarget($sellerId, $campaignId, $adGroupId, $name, $entityId);
                $cacheKey = $this->getCacheKey($sellerId, $campaignId, $adGroupId);
                $this->negativeTargetCache[$cacheKey]["asinSameAs_" . strtolower($name)] = ["targetId" => $entityId];
            }
        }

        foreach ($result['error'] ?? [] as $error) {
            $meta = $chunk[$error['index']];
            $reason = 'Amazon接口返回失败';
            if (isset($error['response']['details']) && $error['response']['details']) {
                $reason = is_array($error['response']['details']) ? json_encode($error['response']['details'], JSON_UNESCAPED_UNICODE) : $error['response']['details'];
            } elseif (isset($error['response']['code']) && $error['response']['code']) {
                $reason = $error['response']['code'];
            }
            $exportList[] = $this->buildExportRow($meta['row'], $meta['campaign_id'], $reason);
        }

        return $exportList;
    }

    private function resolveAdGroupInfo($sellerId, $adGroupId)
    {
        $cacheKey = $sellerId . "_" . $adGroupId;
        if (isset($this->adGroupCache[$cacheKey])) {
            return $this->adGroupCache[$cacheKey];
        }

        $this->prefetchAdGroupInfos($sellerId, [
            ["ad_group_id" => $adGroupId]
        ]);
        return $this->adGroupCache[$cacheKey];
    }

    private function existsInAmazon($type, $sellerId, $campaignId, $adGroupId, $name, $matchType)
    {
        $cacheKey = $this->getCacheKey($sellerId, $campaignId, $adGroupId);
        if ($type === 'keyword') {
            $keywordKey = $matchType . "_" . $name;
            if (!isset($this->keywordCache[$cacheKey][$keywordKey])) {
                $keywordMap = $this->spApi->listKeyword($sellerId, $campaignId, $adGroupId, $matchType, $name);
                if (!isset($this->keywordCache[$cacheKey])) {
                    $this->keywordCache[$cacheKey] = [];
                }
                foreach ($keywordMap as $key => $keywordInfo) {
                    $this->keywordCache[$cacheKey][$key] = $keywordInfo;
                }
            }
            return isset($this->keywordCache[$cacheKey][$keywordKey]);
        }
        if ($type === 'target') {
            $targetName = strtoupper($name);
            if (!isset($this->targetCache[$cacheKey][$targetName])) {
                $targetMap = $this->spApi->listTargetAsin($sellerId, $campaignId, $adGroupId, "", $targetName);
                if (!isset($this->targetCache[$cacheKey])) {
                    $this->targetCache[$cacheKey] = [];
                }
                foreach ($targetMap as $asin => $targetInfo) {
                    $this->targetCache[$cacheKey][strtoupper($asin)] = $targetInfo;
                }
            }
            return isset($this->targetCache[$cacheKey][$targetName]);
        }
        if ($type === 'negativeKeyword') {
            $keywordKey = $matchType . "_" . $name;
            if (!isset($this->negativeKeywordCache[$cacheKey][$keywordKey])) {
                if (!isset($this->negativeKeywordCache[$cacheKey])) {
                    $this->negativeKeywordCache[$cacheKey] = [];
                }

                $amazonMatchType = $this->normalizeNegativeKeywordFilterType($matchType);
                $list = $this->spApi->listNegativeKeyword($sellerId, [$campaignId], [$adGroupId], null, $amazonMatchType, $name);
                foreach ($list as $item) {
                    $this->negativeKeywordCache[$cacheKey][$item['matchType'] . "_" . $item['keywordText']] = $item;
                }

                if (!isset($this->negativeKeywordCache[$cacheKey][$keywordKey])) {
                    $fallbackList = $this->spApi->listNegativeKeyword($sellerId, [$campaignId], [$adGroupId], null);
                    foreach ($fallbackList as $item) {
                        $this->negativeKeywordCache[$cacheKey][$item['matchType'] . "_" . $item['keywordText']] = $item;
                    }
                }
            }
            return isset($this->negativeKeywordCache[$cacheKey][$keywordKey]);
        }
        $negativeTargetKey = "asinSameAs_" . strtolower($name);
        if (!isset($this->negativeTargetCache[$cacheKey][$negativeTargetKey])) {
            if (!isset($this->negativeTargetCache[$cacheKey])) {
                $this->negativeTargetCache[$cacheKey] = [];
            }

            $list = $this->spApi->listNegativeTarget($sellerId, [$campaignId], [$adGroupId], "", "", $name);
            foreach ($list as $item) {
                if (!empty($item['expression']) && is_array($item['expression'])) {
                    foreach ($item['expression'] as $expression) {
                        $key = $expression['type'] . "_" . strtolower($expression['value']);
                        $this->negativeTargetCache[$cacheKey][$key] = $item;
                    }
                }
            }

            if (!isset($this->negativeTargetCache[$cacheKey][$negativeTargetKey])) {
                $fallbackList = $this->spApi->listNegativeTarget($sellerId, [$campaignId], [$adGroupId]);
                foreach ($fallbackList as $item) {
                    if (!empty($item['expression']) && is_array($item['expression'])) {
                        foreach ($item['expression'] as $expression) {
                            $key = $expression['type'] . "_" . strtolower($expression['value']);
                            $this->negativeTargetCache[$cacheKey][$key] = $item;
                        }
                    }
                }
            }
        }
        return isset($this->negativeTargetCache[$cacheKey][$negativeTargetKey]);
    }

    private function buildCreatePayload($type, $campaignId, $adGroupId, $name, $matchType, $bid)
    {
        if ($type === 'keyword') {
            return [
                "campaignId" => $campaignId,
                "adGroupId" => $adGroupId,
                "bid" => $bid,
                "matchType" => $matchType,
                "keywordText" => (string)$name,
                "state" => "enabled",
            ];
        }
        if ($type === 'target') {
            return [
                "campaignId" => (int)$campaignId,
                "adGroupId" => (int)$adGroupId,
                "state" => "enabled",
                "expressionType" => "manual",
                "bid" => $bid,
                "expression" => [
                    [
                        "value" => $name,
                        "type" => "asinSameAs"
                    ]
                ],
                "resolvedExpression" => [
                    [
                        "value" => $name,
                        "type" => "asinSameAs"
                    ]
                ],
            ];
        }
        if ($type === 'negativeKeyword') {
            return [
                "campaignId" => $campaignId,
                "adGroupId" => $adGroupId,
                "keywordText" => (string)$name,
                "matchType" => $matchType,
                "state" => "enabled",
            ];
        }
        return [
            "campaignId" => (int)$campaignId,
            "adGroupId" => (int)$adGroupId,
            "state" => "enabled",
            "expressionType" => "manual",
            "expression" => [
                [
                    "value" => strtolower($name),
                    "type" => "asinSameAs"
                ]
            ],
            "resolvedExpression" => [
                [
                    "value" => strtolower($name),
                    "type" => "asinSameAs"
                ]
            ],
        ];
    }

    private function buildExportRow($row, $campaignId, $reason)
    {
        return [
            "type" => $row['type'],
            "channel" => $row['channel'],
            "seller_id" => $row['seller_id'],
            "ad_group_id" => "'" . $row['ad_group_id'],
            "campaign_id" => $campaignId ? ("'" . $campaignId) : "",
            "name" => $row['name'],
            "match_type" => $row['match_type'],
            "bid" => $row['bid'],
            "reason" => $reason,
        ];
    }

    private function normalizeKeywordMatchType($value)
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }
        $map = [
            "BROAD" => "broad",
            "PHRASE" => "phrase",
            "EXACT" => "exact",
            "broad" => "broad",
            "phrase" => "phrase",
            "exact" => "exact",
            "广泛" => "broad",
            "广泛匹配" => "broad",
            "词组" => "phrase",
            "词组匹配" => "phrase",
            "精准" => "exact",
            "精准匹配" => "exact",
        ];
        if (isset($map[$value])) {
            return $map[$value];
        }
        return $this->spApi->keywordTypeMap($value);
    }

    private function normalizeNegativeKeywordMatchType($value)
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }
        $map = [
            'negativeExact' => 'negativeExact',
            'NEGATIVE_EXACT' => 'negativeExact',
            '精准否定' => 'negativeExact',
            'negativePhrase' => 'negativePhrase',
            'NEGATIVE_PHRASE' => 'negativePhrase',
            '词组否定' => 'negativePhrase',
        ];
        if (isset($map[$value])) {
            return $map[$value];
        }
        if (stripos($value, 'negativeExact') !== false || mb_strpos($value, '精准') !== false) {
            return 'negativeExact';
        }
        if (stripos($value, 'negativePhrase') !== false || mb_strpos($value, '词组') !== false) {
            return 'negativePhrase';
        }
        return null;
    }

    private function normalizeNegativeKeywordFilterType($matchType)
    {
        $map = [
            'negativeExact' => 'NEGATIVE_EXACT',
            'negativePhrase' => 'NEGATIVE_PHRASE',
            'negativeBroad' => 'NEGATIVE_BROAD',
        ];
        return $map[$matchType] ?? $matchType;
    }

    private function normalizeAdType($adType)
    {
        $adType = strtolower(trim((string)$adType));
        return $this->adTypeAliasMap[$adType] ?? '';
    }

    private function resolveExcelFilePath($adType, $page)
    {
        $baseDir = dirname(__FILE__) . "/excel/";
        $fileName = $this->fileMap[$adType] ?? '';
        if ($fileName === '') {
            throw new Exception("未知广告类型: {$adType}");
        }

        $pathInfo = pathinfo($fileName);
        $candidates = [];
        if ((string)$page !== '' && (string)$page !== '0') {
            $candidates[] = $baseDir . $pathInfo['filename'] . "_" . $page . "." . $pathInfo['extension'];
        }
        $candidates[] = $baseDir . $fileName;

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        throw new Exception("找不到Excel文件: " . implode(" 或 ", $candidates));
    }

    private function normalizeBid($value, $defaultBid)
    {
        $value = trim((string)$value);
        if ($value === '' || mb_strpos($value, '引用广告组默认竞价') !== false) {
            if ($defaultBid === null || $defaultBid === '') {
                return null;
            }
            return (float)$defaultBid;
        }

        $numeric = str_replace([',', "'"], '', $value);
        if (!is_numeric($numeric)) {
            return null;
        }
        return (float)$numeric;
    }

    private function getCacheKey($sellerId, $campaignId, $adGroupId)
    {
        return $sellerId . "_" . $campaignId . "_" . $adGroupId;
    }
}

$parameters = DataUtils::ExplainArgv(@$argv, array());
$params = (count(@$argv) > 1) ? $parameters : $_REQUEST;
if (count(@$argv) > 1) {
    foreach (@$argv as $arg) {
        if (strpos($arg, '=') !== false) {
            $tmp = explode('=', $arg, 2);
            if (count($tmp) == 2 && $tmp[0] !== '') {
                $params[$tmp[0]] = $tmp[1];
            }
        }
    }
}
$channel = "";
$page = 0;
$adType = "";
if (isset($params['channel']) && trim($params['channel']) != '') {
    $channel = trim($params['channel']);
}
if (isset($params['page']) && trim($params['page']) != '') {
    $page = $params['page'];
}
if (isset($params['ad_type']) && trim($params['ad_type']) != '') {
    $adType = trim($params['ad_type']);
} elseif (isset($params['type']) && trim($params['type']) != '') {
    $adType = trim($params['type']);
}

if ($channel === '') {
    echo "channel不能为空\n";
    exit(1);
}
if ($adType === '') {
    echo "ad_type不能为空\n";
    exit(1);
}

$con = new SpEnabledNKeywordAndTargetByAdGroupController();
$con->enabled($channel, $page, $adType);
