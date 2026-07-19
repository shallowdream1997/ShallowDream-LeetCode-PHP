<?php
/**
 * PA Chat 路由分发器
 * PHP 内置服务器入口文件
 *
 * 路由规则：
 *   /api/chat     → chat.php     (聊天指令解析+执行)
 *   /api/scripts  → scripts.php  (脚本列表+搜索)
 *   /api/proxy    → proxy.php    (微服务API代理)
 *   其他静态文件  → PHP内置服务器处理
 */

// CORS 头（开发环境允许跨域）
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// OPTIONS 预检请求直接返回
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// 去除尾部斜杠（根路径除外）
if ($uri !== '/' && substr($uri, -1) === '/') {
    $uri = rtrim($uri, '/');
}

// 静态文件直接返回（PHP 内置服务器处理）
if (preg_match('/\.(html|css|js|png|jpg|jpeg|gif|ico|xlsx|csv|pdf|woff2?|ttf|eot|svg)$/', $uri)) {
    return false;
}

// API 路由
switch ($uri) {
    case '/api/chat':
        require __DIR__ . '/chat.php';
        break;

    case '/api/scripts':
        require __DIR__ . '/scripts.php';
        break;

    case '/api/proxy':
        require __DIR__ . '/proxy.php';
        break;

    case '/api/upload':
        require __DIR__ . '/upload.php';
        break;

    case '/api/file_preview':
        require __DIR__ . '/file_preview.php';
        break;

    case '/api/ai_config':
        require __DIR__ . '/ai_config_api.php';
        break;

    case '/':
        // 根路径重定向到聊天页面
        header('Location: /template/chat.html');
        exit;

    default:
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Not Found', 'path' => $uri], JSON_UNESCAPED_UNICODE);
}
