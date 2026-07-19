<?php
/**
 * 微服务 API 代理
 *
 * POST /api/proxy
 * Body: {
 *   "env": "pro",
 *   "service": "s3015",
 *   "method": "GET",
 *   "path": "pa_products/queryPage",
 *   "params": {"limit": 10},
 *   "module": "pa",
 *   "isGateway": false
 * }
 */

require_once dirname(__FILE__) . '/../bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

// 只接受 POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['error' => 'Invalid JSON'], JSON_UNESCAPED_UNICODE);
    exit;
}

$env = isset($input['env']) ? $input['env'] : 'pro';
$service = isset($input['service']) ? $input['service'] : '';
$method = isset($input['method']) ? strtoupper($input['method']) : 'GET';
$path = isset($input['path']) ? $input['path'] : '';
$params = isset($input['params']) ? $input['params'] : [];
$module = isset($input['module']) ? $input['module'] : 'pa';
$isGateway = isset($input['isGateway']) ? $input['isGateway'] : false;

if (empty($service) || empty($path)) {
    echo json_encode(['error' => '缺少 service 或 path 参数'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $curlService = new CurlService();
    $curlService->setEnvironment($env);

    // 设置 module
    if (!empty($module)) {
        $curlService->getModule($module);
    }

    // 设置服务端口
    $availableServices = ['s3015', 's3047', 's3044', 's3009', 's3023', 's3013',
        'phphk', 'phpali', 'ux168', 's3010', 's3016', 'gateway', 'aiCategoryApi', 'smsSupport'];

    if ($isGateway || $service === 'gateway') {
        $curlService->gateway();
    } elseif (in_array($service, $availableServices)) {
        $curlService->$service();
    } else {
        echo json_encode(['error' => "未知服务: {$service}", 'available' => $availableServices], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 执行请求
    switch ($method) {
        case 'GET':
            if ($isGateway || $service === 'gateway') {
                $result = $curlService->getWayGet($path, $params);
            } else {
                $result = $curlService->get($path, $params);
            }
            break;
        case 'POST':
            if ($isGateway || $service === 'gateway') {
                $result = $curlService->getWayPost($path, $params);
            } else {
                $result = $curlService->post($path, $params);
            }
            break;
        case 'PUT':
            $result = $curlService->put($path, $params);
            break;
        case 'DELETE':
            $result = $curlService->delete($path);
            break;
        default:
            echo json_encode(['error' => "不支持的请求方法: {$method}"], JSON_UNESCAPED_UNICODE);
            exit;
    }

    echo json_encode([
        'success' => true,
        'env' => $env,
        'service' => $service,
        'method' => $method,
        'path' => $path,
        'httpCode' => $result['httpCode'] ?? 0,
        'data' => $result['result'] ?? null,
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
