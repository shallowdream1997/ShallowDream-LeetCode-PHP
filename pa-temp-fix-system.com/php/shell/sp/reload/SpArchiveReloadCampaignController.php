<?php
require_once(dirname(__FILE__) . "/../../../../php/requiredfile/requiredfile.php");
require_once(dirname(__FILE__) . "/../../../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/ExcelUtils.php");
require_once(dirname(__FILE__) . "/../../../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../SpApi.php");

/**
 * 从 SpReloadArchivedAutoPlacementController 导出的 xlsx/CSV 读取 campaignId，
 * 将 B2B campaign 的 Mongo 数据按广告类型和广告 ID 写入 Redis，再删除 Mongo 数据。
 */
class SpArchiveReloadCampaignController
{
    // 每次最多只把 1000 条 Mongo 文档载入内存；删除后继续固定读取第 1 页。
    private const MONGO_PAGE_SIZE = 1000;
    private const REDIS_INDEX_KEY = 'spReloadArchive0921CampaignIndex';
    private const REDIS_KEY_PREFIX = 'spReloadArchive0921';

    private $log;
    private $mongoCurl;
    private $spApi;
    private $redis;
    private $inputFile;
    private $resultFile;
    private $resultCsvPath;
    private $resultXlsxFileName;
    private $processedCampaignIds = array();

    public function __construct($inputFile)
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
            throw new RuntimeException('找不到导出明细文件：' . $this->inputFile);
        }

        $this->openResultFile();
        if (strtolower(pathinfo($this->inputFile, PATHINFO_EXTENSION)) === 'xlsx') {
            try {
                (new ExcelUtils())->eachXlsxRow($this->inputFile, function ($row) {
                    $this->archiveRow($row);
                });
            } finally {
                $this->closeResultFile();
            }
            return;
        }

        $input = fopen($this->inputFile, 'rb');
        if (!$input) {
            throw new RuntimeException('无法读取导出明细文件');
        }

        try {
            $headers = fgetcsv($input);
            if (!is_array($headers)) {
            throw new RuntimeException('导出明细 CSV 缺少表头');
            }
            $headers = array_map(array($this, 'normalizeCsvValue'), $headers);
            while (($row = fgetcsv($input)) !== false) {
                if (count($row) !== count($headers)) {
                    $this->writeResult('', '', '', 'FAILED', 'CSV 列数与表头不一致');
                    continue;
                }
                $this->archiveRow(array_combine($headers, $row));
            }
        } finally {
            fclose($input);
            $this->closeResultFile();
        }
    }

    private function archiveRow(array $row)
    {
        $campaignId = $this->normalizeCsvValue($this->value($row, array('campaignId', 'campaign_id')));
        $sellerId = $this->normalizeCsvValue($this->value($row, array('sellerId', 'seller_id')));
        $mainId = $this->normalizeCsvValue($this->value($row, array('spAmazonAutoPlacementMainId', 'input_main_id')));
        if ($campaignId === '') {
            $this->writeResult($mainId, '', '', 'FAILED', '输入行缺少 campaignId');
            return;
        }
        if (isset($this->processedCampaignIds[$campaignId])) {
            $this->writeResult($mainId, $campaignId, '', 'SKIPPED', '重复 campaignId，已按首次记录处理');
            return;
        }
        $this->processedCampaignIds[$campaignId] = true;

        $campaign = $this->getMongoCampaign($campaignId);
        if (empty($campaign['_id'])) {
            $this->writeResult($mainId, $campaignId, '', 'SKIPPED', 'Mongo 中未找到 campaign');
            return;
        }
        if (!$this->hasSiteRestrictions($campaign)) {
            $this->writeResult($mainId, $campaignId, '', 'SKIPPED', 'Mongo campaign 的 siteRestrictions 为空');
            return;
        }

        if ($sellerId === '') {
            $sellerId = $this->toString($this->value($campaign, array('sellerId', 'seller_id', 'channel')));
        }
        $redisKey = self::REDIS_KEY_PREFIX . ':' . $sellerId . ':' . $campaignId;

        try {
            $this->redis->hSet(self::REDIS_INDEX_KEY, $sellerId . ':' . $campaignId, json_encode(array(
                'redisKey' => $redisKey,
                'spAmazonAutoPlacementMainId' => $mainId,
                'campaignId' => $campaignId,
                'sellerId' => $sellerId,
                'siteRestrictions' => $campaign['siteRestrictions'],
            ), JSON_UNESCAPED_UNICODE));
            $this->backupAndDeleteCampaign($redisKey, $sellerId, $campaign, $campaignId);
            $this->writeResult($mainId, $campaignId, $redisKey, 'ARCHIVED', 'Amazon 已归档，Redis 已记录，Mongo 数据已删除');
        } catch (Exception $exception) {
            $this->writeResult($mainId, $campaignId, $redisKey, 'FAILED', $exception->getMessage());
        }
    }

    private function backupAndDeleteCampaign($redisKey, $sellerId, array $campaign, $campaignId)
    {
        $collections = array(
            'product' => array('endpoint' => 'amazon_sp_products', 'idField' => 'adId', 'delete' => 'deleteMongoProductAdsInfo'),
            'keyword' => array('endpoint' => 'amazon_sp_keywords', 'idField' => 'keywordId', 'delete' => 'deleteMongoKeywordInfo'),
            'negativeKeyword' => array('endpoint' => 'amazon_sp_negativeKeywords', 'idField' => 'keywordId', 'delete' => 'deleteMongoNegativeKeywordInfo'),
            'target' => array('endpoint' => 'amazon_sp_targets', 'idField' => 'targetId', 'delete' => 'deleteMongoTargetInfo'),
            'negativeTarget' => array('endpoint' => 'amazon_sp_negative_targets', 'idField' => 'targetId', 'delete' => 'deleteMongoNegativeTargetInfo'),
            'adGroup' => array('endpoint' => 'amazon_sp_adgroups', 'idField' => 'adGroupId', 'delete' => 'deleteMongoAdGroupInfo'),
        );

        foreach ($collections as $type => $config) {
            $this->backupCollection($redisKey, $campaignId, $type, $config);
        }

        $this->backupToRedis($redisKey, 'campaign', $campaignId, $campaign);
        $this->archiveAmazonDocuments($redisKey, $sellerId, 'campaign', array($campaignId), 'archivedCampaign');
        if (!$this->isAmazonArchived($redisKey, 'campaign', $campaignId)) {
            throw new RuntimeException('campaign Amazon 归档未确认成功，停止删除 Mongo');
        }

        foreach ($collections as $type => $config) {
            $this->deleteCollectionAfterCampaignArchived($redisKey, $campaignId, $type, $config);
        }
        $this->spApi->deleteMongoCampaignInfo($campaign['_id']);
        if (!empty($this->getMongoCampaign($campaignId)['_id'])) {
            throw new RuntimeException('campaign Mongo 删除未确认成功');
        }
    }

    private function backupCollection($redisKey, $campaignId, $type, array $config)
    {
        $page = 1;
        $lastPageFingerprint = '';
        while (true) {
            $list = DataUtils::getPageList($this->mongoCurl->s3023()->get($config['endpoint'] . '/queryPage', array(
                'campaignId' => $campaignId,
                'page' => $page,
                'limit' => self::MONGO_PAGE_SIZE,
            )));
            if (count($list) === 0) {
                return;
            }

            $mongoIds = array();
            foreach ($list as $document) {
                if (!is_array($document) || empty($document['_id'])) {
                    throw new RuntimeException($type . ' 缺少 Mongo _id，停止归档');
                }
                $adId = $this->toString($this->value($document, array($config['idField'])));
                if ($adId === '') {
                    throw new RuntimeException($type . ' 缺少 ' . $config['idField'] . '，停止归档');
                }
                $mongoIds[] = (string)$document['_id'];
                $this->backupToRedis($redisKey, $type, $adId, $document);
            }
            $pageFingerprint = md5(implode(',', $mongoIds));
            if ($pageFingerprint === $lastPageFingerprint) {
                throw new RuntimeException($type . ' 分页查询未生效，停止归档');
            }
            $lastPageFingerprint = $pageFingerprint;
            $page++;
        }
    }

    private function deleteCollectionAfterCampaignArchived($redisKey, $campaignId, $type, array $config)
    {
        // 固定读取第 1 页，避免删除后数据前移造成分页漏删。
        $lastPageFingerprint = '';
        while (true) {
            $list = DataUtils::getPageList($this->mongoCurl->s3023()->get($config['endpoint'] . '/queryPage', array(
                'campaignId' => $campaignId,
                'page' => 1,
                'limit' => self::MONGO_PAGE_SIZE,
            )));
            if (count($list) === 0) {
                return;
            }

            $mongoIds = array();
            foreach ($list as $document) {
                if (!is_array($document) || empty($document['_id'])) {
                    throw new RuntimeException($type . ' 缺少 Mongo _id，停止删除');
                }
                $mongoIds[] = (string)$document['_id'];
            }
            $pageFingerprint = md5(implode(',', $mongoIds));
            if ($pageFingerprint === $lastPageFingerprint) {
                throw new RuntimeException($type . ' 删除请求未生效，停止循环');
            }
            $lastPageFingerprint = $pageFingerprint;

            foreach ($list as $document) {
                $adId = $this->toString($this->value($document, array($config['idField'])));
                if ($adId === '') {
                    throw new RuntimeException($type . ' 缺少 ' . $config['idField'] . '，停止删除');
                }
                $this->backupToRedis($redisKey, $type, $adId, $document);
                $this->spApi->{$config['delete']}($document['_id']);
            }
        }
    }

    /**
     * Redis 是 Amazon 调用前的检查点。调用结果按广告 ID 回写成功标记后，才允许删除 Mongo。
     */
    private function archiveAmazonDocuments($redisKey, $sellerId, $type, array $adIds, $archiveMethod)
    {
        $pendingIds = array();
        foreach ($adIds as $adId) {
            $adId = $this->toString($adId);
            if ($adId !== '' && !$this->isAmazonArchived($redisKey, $type, $adId)) {
                $pendingIds[] = $adId;
            }
        }

        foreach (array_chunk(array_values(array_unique($pendingIds)), 100) as $idChunk) {
            $results = $this->spApi->{$archiveMethod}($sellerId, $idChunk);
            $successIds = array();
            foreach ($results as $result) {
                if (!is_array($result) || ($result['msg'] ?? '') !== 'success') {
                    continue;
                }
                foreach (array('campaignId', 'adGroupId', 'adId', 'keywordId', 'targetId') as $idName) {
                    if (isset($result[$idName])) {
                        $successIds[$this->toString($result[$idName])] = true;
                    }
                }
            }
            foreach ($idChunk as $adId) {
                if (isset($successIds[$adId])) {
                    $this->markAmazonArchived($redisKey, $type, $adId);
                }
            }
            foreach ($idChunk as $adId) {
                if (!isset($successIds[$adId])) {
                    throw new RuntimeException($type . ' Amazon 归档失败或未返回成功：' . $adId);
                }
            }
        }
    }

    private function getMongoCampaign($campaignId)
    {
        $list = DataUtils::getPageList($this->mongoCurl->s3023()->get('amazon_sp_campaigns/queryPage', array(
            'campaignId' => $campaignId,
            'limit' => 1,
        )));
        return isset($list[0]) && is_array($list[0]) ? $list[0] : array();
    }

    private function backupToRedis($redisKey, $type, $adId, array $document)
    {
        $archiveInfo = array(
            '_id' => (string)$document['_id'],
            'mongoId' => (string)$document['_id'],
            'mongoIds' => array((string)$document['_id']),
            'type' => $type,
            'adId' => (string)$adId,
            'campaignId' => $this->toString($this->value($document, array('campaignId'))),
            'adGroupId' => $this->toString($this->value($document, array('adGroupId'))),
            // false 表示尚未收到 Amazon 的明确成功回执。
            'amazonArchived' => false,
        );
        $existing = $this->getRedisArchiveInfo($redisKey, $type, $adId);
        if (!empty($existing['mongoIds']) && is_array($existing['mongoIds'])) {
            $archiveInfo['mongoIds'] = array_values(array_unique(array_merge($existing['mongoIds'], $archiveInfo['mongoIds'])));
        } elseif (!empty($existing['mongoId'])) {
            $archiveInfo['mongoIds'] = array_values(array_unique(array($existing['mongoId'], $archiveInfo['mongoId'])));
        }
        if (!empty($existing['amazonArchived'])) {
            $archiveInfo['amazonArchived'] = true;
            if (!empty($existing['amazonArchivedAt'])) {
                $archiveInfo['amazonArchivedAt'] = $existing['amazonArchivedAt'];
            }
        }
        $this->redis->hSet($redisKey, $type . ':' . $adId, json_encode($archiveInfo, JSON_UNESCAPED_UNICODE));
    }

    private function getRedisArchiveInfo($redisKey, $type, $adId)
    {
        $value = $this->redis->hGet($redisKey, $type . ':' . $adId);
        if (!$value) {
            return array();
        }
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : array();
    }

    private function isAmazonArchived($redisKey, $type, $adId)
    {
        $archiveInfo = $this->getRedisArchiveInfo($redisKey, $type, $adId);
        return !empty($archiveInfo['amazonArchived']);
    }

    private function markAmazonArchived($redisKey, $type, $adId)
    {
        $archiveInfo = $this->getRedisArchiveInfo($redisKey, $type, $adId);
        if (!$archiveInfo) {
            throw new RuntimeException($type . ' Redis 检查点不存在，停止删除 Mongo');
        }
        $archiveInfo['amazonArchived'] = true;
        $archiveInfo['amazonArchivedAt'] = date('c');
        $this->redis->hSet($redisKey, $type . ':' . $adId, json_encode($archiveInfo, JSON_UNESCAPED_UNICODE));
    }

    private function hasSiteRestrictions(array $campaign)
    {
        if (!array_key_exists('siteRestrictions', $campaign)) {
            return false;
        }
        $value = $campaign['siteRestrictions'];
        if (is_array($value)) {
            return count($value) > 0;
        }
        $value = trim((string)$value);
        return $value !== '' && $value !== '[]' && strtolower($value) !== 'null';
    }

    private function openResultFile()
    {
        $directory = __DIR__ . '/export';
        if (!is_dir($directory) && !mkdir($directory, 0775, true)) {
            throw new RuntimeException('无法创建结果目录：' . $directory);
        }
        $fileTag = preg_replace('/[^\p{L}\p{N}_-]+/u', '_', pathinfo($this->inputFile, PATHINFO_FILENAME));
        $fileBaseName = '自动投放归档结果_' . $fileTag . '_' . date('YmdHis');
        $this->resultCsvPath = $directory . '/' . $fileBaseName . '.csv';
        $this->resultXlsxFileName = $fileBaseName . '.xlsx';
        $this->resultFile = fopen($this->resultCsvPath, 'wb');
        if (!$this->resultFile) {
            throw new RuntimeException('无法创建归档结果文件');
        }
        fwrite($this->resultFile, "\xEF\xBB\xBF");
        fputcsv($this->resultFile, array('spAmazonAutoPlacementMainId', 'campaignId', 'redisKey', 'status', 'result'));
    }

    private function closeResultFile()
    {
        if (is_resource($this->resultFile)) {
            fclose($this->resultFile);
            $this->resultFile = null;
        }
        if (!$this->resultCsvPath || !is_file($this->resultCsvPath)) {
            return;
        }

        (new ExcelUtils('sp/reload/'))->downloadXlsxFromCsv(
            $this->resultCsvPath,
            $this->resultXlsxFileName,
            array(0, 1, 2)
        );
        @unlink($this->resultCsvPath);
    }

    private function writeResult($mainId, $campaignId, $redisKey, $status, $message)
    {
        fputcsv($this->resultFile, array($this->excelText($mainId), $this->excelText($campaignId), $redisKey, $status, $message));
        $this->log->log2($status . ' campaignId=' . $campaignId . ' ' . $message);
    }

    private function resolveInputFile($inputFile)
    {
        $inputFile = trim((string)$inputFile);
        if ($inputFile === '') {
            throw new InvalidArgumentException('必须传入导出脚本生成的 xlsx 或 CSV 文件路径');
        }
        return $inputFile[0] === '/' ? $inputFile : __DIR__ . '/export/' . $inputFile;
    }

    private function normalizeCsvValue($value)
    {
        $value = preg_replace('/^\xEF\xBB\xBF/', '', trim((string)$value));
        if (preg_match('/^="(.*)"$/s', $value, $matches)) {
            return str_replace('""', '"', $matches[1]);
        }
        return $value;
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
$controller = new SpArchiveReloadCampaignController($inputFile);
$controller->run();
