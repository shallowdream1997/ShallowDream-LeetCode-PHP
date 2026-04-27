<?php
require dirname(__FILE__) . '/../../vendor/autoload.php';
require_once dirname(__FILE__) . '/../requiredfile/requiredChorm.php';
require_once dirname(__FILE__) . '/../utils/DataUtils.php';
require_once dirname(__FILE__) . '/../curl/CurlService.php';
require_once dirname(__FILE__) . '/../class/Logger.php';

/**
 * 广告规则数据同步控制器
 * 用于同步生产环境的广告规则数据到test/uat环境
 * Class adRuleSync
 */
class adRuleSync
{
    private $logger;
    
    // 同步模块配置
    private $syncModules = [
        'amazon_sp_sellers' => [
            'uniqueKey' => ['company', 'sellerId'], // 唯一标识字段
            'queryParams' => ['company' => 'CR201706060001', 'limit' => 1000],
            'port' => 's3023', // 使用端口
            'needPagination' => false // 是否需要分页查询
        ],
        'amazon_sp_rule_configs' => [
            'uniqueKey' => ['ruleName'], // 唯一标识字段
            'queryParams' => ['limit' => 1000],
            'port' => 's3023',
            'needPagination' => false
        ],
        'amazon_sp_budget_and_bid_rule_configs' => [
            'uniqueKey' => ['ruleName'], // 唯一标识字段
            'queryParams' => ['limit' => 1000],
            'port' => 's3023',
            'needPagination' => false
        ],
        'seller-channel-platforms' => [
            'uniqueKey' => ['company', 'sellerId'], // 唯一标识字段
            'queryParams' => ['company' => 'PA', 'limit' => 500],
            'port' => 's3015', // 使用s3015端口
            'needPagination' => true // 需要分页查询全部数据
        ]
    ];
    
    public function __construct()
    {
        $this->logger = new MyLogger('ad_rule_sync');
        $this->logger->log("广告规则同步控制器初始化完成");
    }
    
    /**
     * 处理请求
     * @param array $params 请求参数
     * @return array
     */
    public function handleRequest($params = [])
    {
        try {
            $action = isset($params['action']) ? $params['action'] : '';
            
            $this->logger->log("========================================");
            $this->logger->log("接收到请求 - Action: {$action}");
            $this->logger->log("请求参数: " . json_encode($params, JSON_UNESCAPED_UNICODE));
            
            switch ($action) {
                case 'syncModule':
                    return $this->syncModule($params);
                case 'getModules':
                    return $this->getModuleList();
                default:
                    return [
                        'success' => false,
                        'message' => '无效的操作类型',
                        'data' => []
                    ];
            }
            
        } catch (Exception $e) {
            $this->logger->log("请求处理异常: " . $e->getMessage());
            return [
                'success' => false,
                'message' => '处理请求时发生错误: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }
    
    /**
     * 获取可同步的模块列表
     * @return array
     */
    private function getModuleList()
    {
        $modules = [];
        foreach ($this->syncModules as $moduleName => $config) {
            $modules[] = [
                'name' => $moduleName,
                'displayName' => $this->getModuleDisplayName($moduleName),
                'uniqueKey' => $config['uniqueKey']
            ];
        }
        
        return [
            'success' => true,
            'message' => '获取模块列表成功',
            'data' => ['modules' => $modules]
        ];
    }
    
    /**
     * 获取模块显示名称
     * @param string $moduleName
     * @return string
     */
    private function getModuleDisplayName($moduleName)
    {
        $names = [
            'amazon_sp_sellers' => '广告账号',
            'amazon_sp_rule_configs' => '广告规则配置',
            'amazon_sp_budget_and_bid_rule_configs' => '广告预算和出价规则配置',
            'seller-channel-platforms' => '卖家渠道平台配置'
        ];
        return $names[$moduleName] ?? $moduleName;
    }
    
    /**
     * 分页查询获取全部数据
     * @param CurlService $curlService Curl服务实例
     * @param string $module 模块名称
     * @param array $queryParams 查询参数
     * @param string $port 端口
     * @return array 全部数据
     */
    private function getAllDataWithPagination($curlService, $module, $queryParams, $port)
    {
        $allData = [];
        $page = 1;
        $limit = isset($queryParams['limit']) ? $queryParams['limit'] : 500;
        
        $this->logger->log("开始分页查询 {$module}，每页 {$limit} 条数据");
        
        do {
            $queryParams['page'] = $page;
            $this->logger->log("查询第 {$page} 页，参数: " . json_encode($queryParams, JSON_UNESCAPED_UNICODE));
            
            $response = $curlService->$port()->get("{$module}/queryPage", $queryParams);
            $pageData = DataUtils::getPageList($response);
            
            $this->logger->log("第 {$page} 页返回数据数量: " . count($pageData));
            
            if (count($pageData) > 0) {
                $allData = array_merge($allData, $pageData);
            }
            
            $page++;
            
            // 如果返回数据少于limit，说明已经是最后一页
            if (count($pageData) < $limit) {
                break;
            }
            
            // 安全限制，防止无限循环
            if ($page > 100) {
                $this->logger->log("分页查询超过100页，停止查询");
                break;
            }
            
        } while (count($pageData) > 0);
        
        $this->logger->log("分页查询完成，共获取 " . count($allData) . " 条数据");
        return $allData;
    }
    
    /**
     * 同步单个模块数据
     * @param array $params 同步参数
     * @return array
     */
    private function syncModule($params)
    {
        $module = isset($params['module']) ? trim($params['module']) : '';
        $targetEnv = isset($params['targetEnv']) ? trim($params['targetEnv']) : 'test';
        
        // 验证模块名称
        if (empty($module) || !isset($this->syncModules[$module])) {
            return [
                'success' => false,
                'message' => '无效的模块名称',
                'data' => []
            ];
        }
        
        // 验证目标环境
        if (!in_array($targetEnv, ['test', 'uat'])) {
            return [
                'success' => false,
                'message' => '目标环境只能是test或uat',
                'data' => []
            ];
        }
        
        $this->logger->log("========== 开始同步模块: {$module} ==========");
        $this->logger->log("目标环境: {$targetEnv}");
        
        try {
            // 创建CurlService实例
            $proCurlService = (new CurlService())->pro();
            $targetCurlService = ($targetEnv === 'test') 
                ? (new CurlService())->test() 
                : (new CurlService())->uat();
            
            $moduleConfig = $this->syncModules[$module];
            $port = $moduleConfig['port']; // 获取模块配置的端口
            $needPagination = $moduleConfig['needPagination']; // 是否需要分页查询
            
            // 1. 从生产环境查询数据
            $this->logger->log("---------- 从PRO环境查询 {$module} 数据 ----------");
            $this->logger->log("使用端口: {$port}");
            
            if ($needPagination) {
                // 需要分页查询全部数据
                $proData = $this->getAllDataWithPagination($proCurlService, $module, $moduleConfig['queryParams'], $port);
            } else {
                // 单次查询
                $proData = DataUtils::getPageList($proCurlService->$port()->get("{$module}/queryPage", $moduleConfig['queryParams']));
            }
            $this->logger->log("PRO环境查询结果数量: " . count($proData));
            
            if (count($proData) === 0) {
                $this->logger->log("PRO环境无数据，同步完成");
                return [
                    'success' => true,
                    'message' => "{$module} 无数据需要同步",
                    'data' => [
                        'module' => $module,
                        'displayName' => $this->getModuleDisplayName($module),
                        'targetEnv' => $targetEnv,
                        'sourceCount' => 0,
                        'deletedCount' => 0,
                        'createdCount' => 0
                    ]
                ];
            }
            
            // 2. 从目标环境查询数据
            $this->logger->log("---------- 从{$targetEnv}环境查询 {$module} 数据 ----------");
            if ($needPagination) {
                // 需要分页查询全部数据
                $targetData = $this->getAllDataWithPagination($targetCurlService, $module, $moduleConfig['queryParams'], $port);
            } else {
                // 单次查询
                $targetData = DataUtils::getPageList($targetCurlService->$port()->get("{$module}/queryPage", $moduleConfig['queryParams']));
            }
            $this->logger->log("目标环境查询结果数量: " . count($targetData));
            
            // 3. 根据唯一标识匹配并删除目标环境数据
            $deletedCount = 0;
            $uniqueKeyFields = $moduleConfig['uniqueKey'];
            
            $this->logger->log("---------- 开始删除目标环境匹配数据 ----------");
            $this->logger->log("唯一标识字段: " . json_encode($uniqueKeyFields, JSON_UNESCAPED_UNICODE));
            
            foreach ($proData as $proItem) {
                // 构建匹配条件
                $matchConditions = [];
                foreach ($uniqueKeyFields as $keyField) {
                    if (isset($proItem[$keyField])) {
                        $matchConditions[$keyField] = $proItem[$keyField];
                    }
                }
                
                $this->logger->log("匹配条件: " . json_encode($matchConditions, JSON_UNESCAPED_UNICODE));
                
                // 在目标环境查找匹配的数据
                foreach ($targetData as $targetItem) {
                    $isMatch = true;
                    foreach ($matchConditions as $key => $value) {
                        if (!isset($targetItem[$key]) || $targetItem[$key] !== $value) {
                            $isMatch = false;
                            break;
                        }
                    }
                    
                    if ($isMatch) {
                        // 删除匹配的数据
                        $itemId = $targetItem['_id'];
                        $this->logger->log("删除数据: {$module}/{$itemId}");
                        
                        try {
                            $deleteResult = $targetCurlService->$port()->delete($module, $itemId);
                            $this->logger->log("删除结果: " . json_encode($deleteResult, JSON_UNESCAPED_UNICODE));
                            $deletedCount++;
                        } catch (Exception $e) {
                            $this->logger->log("删除失败: " . $e->getMessage());
                        }
                    }
                }
            }
            
            $this->logger->log("删除完成，共删除 {$deletedCount} 条数据");
            
            // 4. 将生产环境数据新增到目标环境
            $createdCount = 0;
            $this->logger->log("---------- 开始新增数据到目标环境 ----------");
            
            foreach ($proData as $proItem) {
                // 移除_id和__v字段（如果存在）
                $newData = $proItem;
                unset($newData['_id']);
                unset($newData['__v']);
                
                $this->logger->log("新增数据: " . json_encode($newData, JSON_UNESCAPED_UNICODE));
                
                try {
                    $createResult = $targetCurlService->$port()->post($module, $newData);
                    $this->logger->log("新增结果: " . json_encode($createResult, JSON_UNESCAPED_UNICODE));
                    $createdCount++;
                } catch (Exception $e) {
                    $this->logger->log("新增失败: " . $e->getMessage());
                }
            }
            
            $this->logger->log("新增完成，共新增 {$createdCount} 条数据");
            $this->logger->log("========== 模块同步完成: {$module} ==========");
            
            return [
                'success' => true,
                'message' => "{$this->getModuleDisplayName($module)} 同步成功",
                'data' => [
                    'module' => $module,
                    'displayName' => $this->getModuleDisplayName($module),
                    'targetEnv' => $targetEnv,
                    'sourceCount' => count($proData),
                    'deletedCount' => $deletedCount,
                    'createdCount' => $createdCount
                ]
            ];
            
        } catch (Exception $e) {
            $errorMsg = "同步失败: Module={$module}, Error=" . $e->getMessage();
            $this->logger->log($errorMsg);
            $this->logger->log("异常堆栈: " . $e->getTraceAsString());
            
            return [
                'success' => false,
                'message' => "同步 {$module} 到{$targetEnv}环境时发生错误: " . $e->getMessage(),
                'data' => [
                    'module' => $module,
                    'displayName' => $this->getModuleDisplayName($module),
                    'targetEnv' => $targetEnv
                ]
            ];
        }
    }
}

// API入口点
$controller = new adRuleSync();

try {
    header('Content-Type: application/json; charset=utf-8');
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = file_get_contents('php://input');
        $params = json_decode($input, true) ?: $_POST;
        $result = $controller->handleRequest($params);
    } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $result = $controller->handleRequest($_GET);
    } else {
        $result = [
            'success' => false,
            'message' => '只支持POST和GET请求',
            'data' => []
        ];
    }
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => '服务器内部错误: ' . $e->getMessage(),
        'data' => []
    ], JSON_UNESCAPED_UNICODE);
}