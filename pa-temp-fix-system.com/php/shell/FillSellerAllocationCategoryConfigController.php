<?php
require_once(dirname(__FILE__) . "/../../php/requiredfile/requiredfile.php");
require_once(dirname(__FILE__) . "/../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../php/curl/CurlService.php");

class FillSellerAllocationCategoryConfigController
{
    private $log;
    private $curlService;

    public function __construct($env = 'test')
    {
        $this->log = new MyLogger("sellerAllocationConfig");
        $this->curlService = (new CurlService())->setEnvironment($env)->gateway()->getModule("platform_item_service");
    }

    private function log($message)
    {
        $this->log->log2($message);
    }

    public function handle($inputFile, $outputFile)
    {
        if (!is_file($inputFile)) {
            throw new Exception("文件不存在: {$inputFile}");
        }

        $content = file_get_contents($inputFile);
        $config = json_decode($content, true);
        if (!is_array($config)) {
            throw new Exception("JSON解析失败: {$inputFile}");
        }

        $categoryNameMap = $this->collectCategoryNames($config);
        $this->log("待查询分类数量: " . count($categoryNameMap));

        $categoryResultMap = [];
        foreach (array_keys($categoryNameMap) as $categoryName) {
            $categoryResultMap[$categoryName] = $this->queryCategoryInfo($categoryName);
        }

        foreach ($config['accountMappings'] as &$mapping) {
            if (empty($mapping['categories']) || !is_array($mapping['categories'])) {
                continue;
            }
            foreach ($mapping['categories'] as &$category) {
                $categoryName = trim((string)($category['categoryName'] ?? ''));
                if ($categoryName === '') {
                    continue;
                }
                $categoryInfo = $categoryResultMap[$categoryName] ?? null;
                $category['categoryId'] = $categoryInfo['categoryId'] ?? null;
                if (isset($categoryInfo['categoryFullPath'])) {
                    $category['categoryFullPath'] = $categoryInfo['categoryFullPath'];
                }
            }
            unset($category);
        }
        unset($mapping);

        $json = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents($outputFile, $json);
        $this->log("输出完成: {$outputFile}");

        echo $json . PHP_EOL;
    }

    private function collectCategoryNames($config)
    {
        $categoryNameMap = [];
        foreach ($config['accountMappings'] ?? [] as $mapping) {
            foreach ($mapping['categories'] ?? [] as $category) {
                $categoryName = trim((string)($category['categoryName'] ?? ''));
                if ($categoryName !== '') {
                    $categoryNameMap[$categoryName] = true;
                }
            }
        }
        return $categoryNameMap;
    }

    private function queryCategoryInfo($categoryName)
    {
        $response = $this->curlService->getWayGet(
            $this->curlService->module . "/supplier_categories/v1/queryFullPathByNameOrId",
            ["string" => $categoryName]
        );
        $result = $response['result'] ?? [];
        $data = $result['data'] ?? [];

        if (!is_array($data) || count($data) <= 0) {
            $this->log("分类未查到: {$categoryName}");
            return [
                "categoryId" => null,
                "categoryFullPath" => null,
            ];
        }

        $matched = $this->pickBestCategoryMatch($categoryName, $data);
        $this->log("分类匹配: {$categoryName} => " . json_encode($matched, JSON_UNESCAPED_UNICODE));
        return [
            "categoryId" => $matched['categoryId'] ?? null,
            "categoryFullPath" => $matched['categoryFullPath'] ?? null,
        ];
    }

    private function pickBestCategoryMatch($categoryName, $data)
    {
        foreach ($data as $item) {
            $fullPath = (string)($item['categoryFullPath'] ?? '');
            $parts = array_map('trim', explode('->', $fullPath));
            $leafName = trim((string)end($parts));
            if ($leafName === $categoryName) {
                return $item;
            }
        }

        foreach ($data as $item) {
            $fullPath = (string)($item['categoryFullPath'] ?? '');
            if (mb_strpos($fullPath, $categoryName) !== false) {
                return $item;
            }
        }

        return $data[0];
    }
}

$parameters = DataUtils::ExplainArgv(@$argv, array());
$params = (count(@$argv) > 1) ? $parameters : $_REQUEST;
$env = trim((string)($params['env'] ?? 'test'));
$inputFile = trim((string)($params['input'] ?? dirname(__FILE__) . "/../export/sellerAllocationConfig.json"));
$outputFile = trim((string)($params['output'] ?? dirname(__FILE__) . "/../export/sellerAllocationConfig.completed.json"));

$controller = new FillSellerAllocationCategoryConfigController($env);
$controller->handle($inputFile, $outputFile);
