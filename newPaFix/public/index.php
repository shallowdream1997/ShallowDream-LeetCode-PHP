<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';
require BASE_PATH . '/bootstrap/http.php';

$routes = require BASE_PATH . '/routes/api.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

foreach ($routes as $route) {
    if ($route['method'] === $method && $route['path'] === $path) {
        $response = $route['handler']();
        send_json_response($response);
        return;
    }
}

http_response_code(404);
send_json_response([
    'success' => false,
    'message' => '接口不存在'
]);

function send_json_response($data): void
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
}

