<?php
/**
 * AI 配置管理 API
 * GET  /api/ai_config — 读取配置（API Key 脱敏）
 * POST /api/ai_config — 保存 API Key / 模型选择 / AI 模式
 */

require_once dirname(__FILE__) . '/../bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Session-Id');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$allConfig = require dirname(__FILE__) . '/ai_config.php';

// 从 header 或 query 获取 sessionId
$sessionId = '';
if (isset($_SERVER['HTTP_X_SESSION_ID'])) {
    $sessionId = trim($_SERVER['HTTP_X_SESSION_ID']);
}
if (empty($sessionId)) {
    $sessionId = isset($_GET['sessionId']) ? trim($_GET['sessionId']) : 'default';
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // 读取配置
    $result = array(
        'default' => $allConfig['default'],
        'models' => array(),
    );

    foreach ($allConfig['models'] as $key => $model) {
        $hasKey = !empty($model['api_key']);
        // 检查 Redis 中的用户自定义 Key
        try {
            $redis = new RedisService();
            $userKey = $redis->get($allConfig['config_redis_prefix'] . $key);
            if ($userKey) {
                $hasKey = true;
            }
        } catch (Exception $e) {
        }

        $result['models'][$key] = array(
            'name' => $model['name'],
            'type' => $model['type'],
            'has_key' => $hasKey,
        );
    }

    // 读取当前 AI 模式和模型选择
    try {
        $redis = new RedisService();
        $mode = $redis->get($allConfig['mode_redis_prefix'] . $sessionId);
        $model = $redis->get($allConfig['model_redis_prefix'] . $sessionId);
        $result['mode'] = $mode ?: 'off';
        $result['current_model'] = $model ?: $allConfig['default'];
    } catch (Exception $e) {
        $result['mode'] = 'off';
        $result['current_model'] = $allConfig['default'];
    }

    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        echo json_encode(['error' => 'Invalid JSON'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $apiKey = isset($input['api_key']) ? trim($input['api_key']) : '';
    $model = isset($input['model']) ? trim($input['model']) : '';
    $mode = isset($input['mode']) ? trim($input['mode']) : '';

    try {
        $redis = new RedisService();

        // 保存 API Key（按模型存储）
        if ($apiKey && $model && isset($allConfig['models'][$model])) {
            $redis->set($allConfig['config_redis_prefix'] . $model, $apiKey, 86400 * 30); // 30天
        }

        // 保存模型选择
        if ($model && isset($allConfig['models'][$model])) {
            $redis->set($allConfig['model_redis_prefix'] . $sessionId, $model, 86400);
        }

        // 保存 AI 模式
        if ($mode && in_array($mode, array('on', 'off'))) {
            $redis->set($allConfig['mode_redis_prefix'] . $sessionId, $mode, 86400);
        }

        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Redis 不可用: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

echo json_encode(['error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
