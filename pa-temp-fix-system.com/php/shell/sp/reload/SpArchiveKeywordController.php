<?php
require_once(dirname(__FILE__) . "/../../../../php/requiredfile/requiredfile.php");
require_once(dirname(__FILE__) . "/../../../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/ExcelUtils.php");
require_once(dirname(__FILE__) . "/../../../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../SpApi.php");

/**
 * 从 keywordId xlsx 读取关键词，先备份 Mongo，再调用 Amazon 归档，最后删除 Mongo。
 */
class SpArchiveKeywordController
{
    // Mongo 查询接口按 200 条分页，避免单次 keywordId_in/返回数据过大。
    private const MONGO_PAGE_SIZE = 200;
    private const AMAZON_BATCH_SIZE = 100;
    private const REDIS_INDEX_KEY = 'spReloadArchive0921KeywordIndex';
    private const REDIS_KEY_PREFIX = 'spReloadArchive0921Keyword';

    private $log;
    private $mongoCurl;
    private $spApi;
    private $redis;
    private $inputFile;
    private $resultFile;
    private $resultCsvPath;
    private $resultXlsxFileName;
    private $inputGroups = array();
    private $invalidRows = array();
    private $resultKeys = array();

    public function __construct($inputFile = '')
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(0);
        $this->inputFile = $this->resolveInputFile($inputFile);
        $this->log = new MyLogger('sp/reload');
        $this->mongoCurl = (new CurlService())->pro();
        $this->spApi = new SpApi();
        $this->redis = new RedisService();
    }

    public function run()
    {
        if (!is_file($this->inputFile)) {
            throw new RuntimeException('找不到输入文件：' . $this->inputFile);
        }
        $this->openResultFile();
        try {
            (new ExcelUtils())->eachXlsxRow($this->inputFile, function ($row) {
                $this->collectInput($row);
            });
            foreach ($this->invalidRows as $row) {
                $this->writeResult('', $row['keywordId'], '', 'FAILED', $row['message'], 'SKIPPED', 'FAILED');
            }
            $this->processInputGroups();
        } finally {
            $this->closeResultFile();
        }
    }

    private function collectInput(array $row)
    {
        $keywordId = $this->normalizeId($this->value($row, array('keywordId', 'keyword_id', 'id')));
        $sellerId = $this->toString($this->value($row, array('sellerId', 'seller_id', 'seller', 'channel')));
        if ($sellerId !== '') {
            $sellerId = $this->spApi->specialSellerIdReverseConver($sellerId);
        }
        if ($keywordId === '') {
            $this->invalidRows[] = array('keywordId' => '', 'message' => '输入行缺少 keywordId');
            return;
        }

        // 没有 sellerId 时先按 keywordId 查 Mongo，再从 Mongo channel 反推账号。
        $groupKey = $sellerId === '' ? '*' : $sellerId;
        if (!isset($this->inputGroups[$groupKey])) {
            $this->inputGroups[$groupKey] = array();
        }
        $this->inputGroups[$groupKey][$keywordId] = true;
    }

    private function processInputGroups()
    {
        foreach ($this->inputGroups as $inputSellerId => $keywordMap) {
            $keywordIds = array_keys($keywordMap);
            $records = array();
            foreach (array_chunk($keywordIds, self::MONGO_PAGE_SIZE) as $chunk) {
                $page = 1;
                while (true) {
                    $list = $this->queryMongoKeywords($chunk, $inputSellerId, $page);
                    if (count($list) === 0) {
                        break;
                    }
                    foreach ($list as $document) {
                        if (!is_array($document) || empty($document['_id'])) {
                            continue;
                        }
                        $keywordId = $this->normalizeId($this->value($document, array('keywordId')));
                        if ($keywordId === '') {
                            continue;
                        }
                        $sellerId = $inputSellerId === '*'
                            ? $this->spApi->specialSellerIdReverseConver($this->toString($this->value($document, array('channel'))))
                            : $inputSellerId;
                        if (!isset($records[$sellerId][$keywordId])) {
                            $records[$sellerId][$keywordId] = array();
                        }
                        $records[$sellerId][$keywordId][] = $document;
                    }
                    if (count($list) < self::MONGO_PAGE_SIZE) {
                        break;
                    }
                    $page++;
                }
            }

            foreach ($keywordIds as $keywordId) {
                $found = false;
                foreach ($records as $sellerId => $sellerRecords) {
                    if (isset($sellerRecords[$keywordId])) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    if ($inputSellerId === '*') {
                        $this->writeResult('', $keywordId, '', 'FAILED', 'Amazon: SKIPPED；Mongo: FAILED；Mongo 中未找到 keyword，且输入缺少 sellerId，无法确定 Amazon 账号', 'SKIPPED', 'FAILED');
                    } else {
                        // Mongo 没有记录时仍继续调用 Amazon 归档，只是不执行 Mongo 删除。
                        if (!isset($records[$inputSellerId])) {
                            $records[$inputSellerId] = array();
                        }
                        $records[$inputSellerId][$keywordId] = array();
                    }
                }
            }

            foreach ($records as $sellerId => $sellerRecords) {
                $this->archiveSellerKeywords($sellerId, $sellerRecords);
            }
        }
    }

    private function queryMongoKeywords(array $keywordIds, $inputSellerId, $page)
    {
        $params = array(
            'keywordId_in' => implode(',', $keywordIds),
            'page' => $page,
            'limit' => self::MONGO_PAGE_SIZE,
        );
        if ($inputSellerId !== '*') {
            $params['channel'] = $this->spApi->specialSellerIdConver($inputSellerId);
        }
        return DataUtils::getPageList($this->mongoCurl->s3023()->get('amazon_sp_keywords/queryPage', $params));
    }

    private function archiveSellerKeywords($sellerId, array $sellerRecords)
    {
        foreach (array_chunk(array_keys($sellerRecords), self::AMAZON_BATCH_SIZE) as $keywordIds) {
            foreach ($keywordIds as $keywordId) {
                $this->ensureRedisCheckpoint($sellerId, $keywordId);
                foreach ($sellerRecords[$keywordId] as $document) {
                    $this->backupToRedis($sellerId, $keywordId, $document);
                }
            }

            $amazonStatus = array();
            $amazonMessage = array();
            $pending = array();
            foreach ($keywordIds as $keywordId) {
                if ($this->isAmazonArchived($sellerId, $keywordId)) {
                    $amazonStatus[$keywordId] = 'SUCCESS';
                    $amazonMessage[$keywordId] = 'Redis 已记录 Amazon 归档成功';
                } else {
                    $pending[] = $keywordId;
                }
            }

            if ($pending) {
                try {
                    $results = $this->spApi->archivedKeyword($sellerId, $pending);
                    foreach ($results as $result) {
                        if (is_array($result) && isset($result['keywordId'])) {
                            $keywordId = $this->normalizeId($result['keywordId']);
                            $isSuccess = ($result['msg'] ?? '') === 'success';
                            $amazonStatus[$keywordId] = $isSuccess ? 'SUCCESS' : 'FAILED';
                            $amazonMessage[$keywordId] = $isSuccess
                                ? 'Amazon keyword 归档成功'
                                : 'Amazon keyword 归档失败：' . ($result['msg'] ?? '未返回错误信息');
                        }
                    }
                    foreach ($pending as $keywordId) {
                        if (!isset($amazonStatus[$keywordId])) {
                            $amazonStatus[$keywordId] = 'FAILED';
                            $amazonMessage[$keywordId] = 'Amazon keyword 归档失败或未返回结果';
                        }
                    }
                } catch (Exception $exception) {
                    foreach ($pending as $keywordId) {
                        $amazonStatus[$keywordId] = 'FAILED';
                        $amazonMessage[$keywordId] = 'Amazon keyword 归档请求异常：' . $exception->getMessage();
                    }
                }
            }

            // Amazon 归档和 Mongo 删除互不阻断：无论 Amazon 成功还是失败，都继续处理 Mongo。
            foreach ($keywordIds as $keywordId) {
                $redisKey = $this->redisKey($sellerId, $keywordId);
                $amazonResult = $amazonStatus[$keywordId] ?? 'FAILED';
                $amazonResultMessage = $amazonMessage[$keywordId] ?? 'Amazon keyword 归档失败或未返回结果';

                if ($amazonResult === 'SUCCESS') {
                    try {
                        $this->markAmazonArchived($sellerId, $keywordId);
                    } catch (Exception $exception) {
                        $amazonResult = 'FAILED';
                        $amazonResultMessage = 'Amazon 已返回成功，但 Redis 记录失败：' . $exception->getMessage();
                    }
                }

                $mongoResult = 'SKIPPED';
                $mongoResultMessage = 'Mongo 中无记录，无需删除';
                try {
                    if (!empty($sellerRecords[$keywordId])) {
                        foreach ($sellerRecords[$keywordId] as $document) {
                            $this->spApi->deleteMongoKeywordInfo($document['_id']);
                        }
                        if ($this->queryMongoKeywords(array($keywordId), $sellerId, 1)) {
                            throw new RuntimeException('Mongo 删除后仍能查到 keyword');
                        }
                        $mongoResult = 'SUCCESS';
                        $mongoResultMessage = 'Mongo keyword 删除成功';
                    }
                } catch (Exception $exception) {
                    $mongoResult = 'FAILED';
                    $mongoResultMessage = 'Mongo keyword 删除失败：' . $exception->getMessage();
                }

                $overallStatus = ($amazonResult === 'SUCCESS' && $mongoResult !== 'FAILED') ? 'SUCCESS' : 'FAILED';
                $message = 'Amazon: ' . $amazonResult . '（' . $amazonResultMessage . '）；Mongo: ' . $mongoResult . '（' . $mongoResultMessage . '）';
                $this->writeResult($sellerId, $keywordId, $redisKey, $overallStatus, $message, $amazonResult, $mongoResult);
            }
        }
    }

    private function backupToRedis($sellerId, $keywordId, array $document)
    {
        $redisKey = $this->redisKey($sellerId, $keywordId);
        $hashKey = 'keyword:' . $keywordId;
        $info = array(
            '_id' => (string)$document['_id'],
            'mongoId' => (string)$document['_id'],
            'keywordId' => (string)$keywordId,
            'sellerId' => (string)$sellerId,
            'campaignId' => $this->toString($this->value($document, array('campaignId'))),
            'adGroupId' => $this->toString($this->value($document, array('adGroupId'))),
            'mongoIds' => array((string)$document['_id']),
            'amazonArchived' => false,
        );
        $old = $this->redis->hGet($redisKey, $hashKey);
        $old = $old ? json_decode($old, true) : array();
        if (is_array($old)) {
            if (!empty($old['mongoIds'])) {
                $info['mongoIds'] = array_values(array_unique(array_merge($old['mongoIds'], $info['mongoIds'])));
            }
            if (!empty($old['amazonArchived'])) {
                $info['amazonArchived'] = true;
                $info['amazonArchivedAt'] = $old['amazonArchivedAt'] ?? date('c');
            }
        }
        $this->redis->hSet($redisKey, $hashKey, json_encode($info, JSON_UNESCAPED_UNICODE));
        $this->redis->hSet(self::REDIS_INDEX_KEY, $sellerId . ':' . $keywordId, $redisKey);
    }

    private function ensureRedisCheckpoint($sellerId, $keywordId)
    {
        $redisKey = $this->redisKey($sellerId, $keywordId);
        $hashKey = 'keyword:' . $keywordId;
        if ($this->redis->hGet($redisKey, $hashKey)) {
            return;
        }
        $this->redis->hSet($redisKey, $hashKey, json_encode(array(
            'keywordId' => (string)$keywordId,
            'sellerId' => (string)$sellerId,
            'mongoIds' => array(),
            'amazonArchived' => false,
        ), JSON_UNESCAPED_UNICODE));
        $this->redis->hSet(self::REDIS_INDEX_KEY, $sellerId . ':' . $keywordId, $redisKey);
    }

    private function redisKey($sellerId, $keywordId)
    {
        return self::REDIS_KEY_PREFIX . ':' . $sellerId . ':' . $keywordId;
    }

    private function isAmazonArchived($sellerId, $keywordId)
    {
        $value = $this->redis->hGet($this->redisKey($sellerId, $keywordId), 'keyword:' . $keywordId);
        $info = $value ? json_decode($value, true) : array();
        return is_array($info) && !empty($info['amazonArchived']);
    }

    private function markAmazonArchived($sellerId, $keywordId)
    {
        $redisKey = $this->redisKey($sellerId, $keywordId);
        $hashKey = 'keyword:' . $keywordId;
        $value = $this->redis->hGet($redisKey, $hashKey);
        $info = $value ? json_decode($value, true) : array();
        if (!is_array($info) || !$info) {
            throw new RuntimeException('Redis 中不存在 keyword 检查点');
        }
        $info['amazonArchived'] = true;
        $info['amazonArchivedAt'] = date('c');
        $this->redis->hSet($redisKey, $hashKey, json_encode($info, JSON_UNESCAPED_UNICODE));
    }

    private function openResultFile()
    {
        $directory = __DIR__ . '/export';
        if (!is_dir($directory) && !mkdir($directory, 0775, true)) {
            throw new RuntimeException('无法创建结果目录');
        }
        $tag = preg_replace('/[^\p{L}\p{N}_-]+/u', '_', pathinfo($this->inputFile, PATHINFO_FILENAME));
        $base = '归档keywordid结果_' . $tag . '_' . date('YmdHis');
        $this->resultCsvPath = $directory . '/.' . $base . '.tmp.csv';
        $this->resultXlsxFileName = $base . '.xlsx';
        $this->resultFile = fopen($this->resultCsvPath, 'wb');
        if (!$this->resultFile) {
            throw new RuntimeException('无法创建结果文件');
        }
        fwrite($this->resultFile, "\xEF\xBB\xBF");
        fputcsv($this->resultFile, array('sellerId', 'keywordId', 'redisKey', 'amazonStatus', 'mongoStatus', 'status', 'result'));
    }

    private function closeResultFile()
    {
        if (is_resource($this->resultFile)) {
            fclose($this->resultFile);
            $this->resultFile = null;
        }
        if ($this->resultCsvPath && is_file($this->resultCsvPath)) {
            try {
                (new ExcelUtils('sp/reload/'))->downloadXlsxFromCsv($this->resultCsvPath, $this->resultXlsxFileName, array(0, 1, 2));
            } finally {
                @unlink($this->resultCsvPath);
            }
        }
    }

    private function writeResult($sellerId, $keywordId, $redisKey, $status, $result, $amazonStatus = '', $mongoStatus = '')
    {
        // 每个账号下的 keyword 只保留一条最终结果，避免异常路径重复写结果。
        $key = $sellerId . ':' . $keywordId;
        if (isset($this->resultKeys[$key])) {
            return;
        }
        $this->resultKeys[$key] = true;
        fputcsv($this->resultFile, array($this->excelText($sellerId), $this->excelText($keywordId), $redisKey, $amazonStatus, $mongoStatus, $status, $result));
        $this->log->log2($status . ' sellerId=' . $sellerId . ' keywordId=' . $keywordId . ' ' . $result);
    }

    private function resolveInputFile($inputFile)
    {
        $inputFile = trim((string)$inputFile);
        if ($inputFile === '') {
            throw new InvalidArgumentException('必须传入 keyword 归档文件名或绝对路径');
        }
        return $inputFile[0] === '/' ? $inputFile : __DIR__ . '/excel/' . $inputFile;
    }

    private function value(array $data, array $keys)
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                return $data[$key];
            }
        }
        return '';
    }

    private function normalizeId($value)
    {
        $value = trim((string)$value);
        if (preg_match('/^="(.*)"$/s', $value, $matches)) {
            $value = str_replace('""', '"', $matches[1]);
        }
        return ltrim($value, "' ");
    }

    private function toString($value)
    {
        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        return trim((string)$value);
    }

    private function excelText($value)
    {
        $value = $this->toString($value);
        return $value === '' ? '' : '="' . str_replace('"', '""', $value) . '"';
    }
}

$inputFile = isset($argv[1]) ? $argv[1] : '';
(new SpArchiveKeywordController($inputFile))->run();
