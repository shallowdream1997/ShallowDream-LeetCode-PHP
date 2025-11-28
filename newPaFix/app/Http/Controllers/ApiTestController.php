<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Infrastructure\Cache\RedisService;
use App\Logging\MyLogger;

class ApiTestController
{
    private MyLogger $logger;

    public function __construct()
    {
        $this->logger = new MyLogger("option/apiTest");
    }

    public function handle(array $payload): array
    {
        $action = $payload['action'] ?? null;
        $params = $payload['params'] ?? [];

        return match ($action) {
            'incr' => $this->incr($params),
            default => [
                'success' => false,
                'message' => '未知的测试 action'
            ],
        };
    }

    private function incr(array $params): array
    {
        if (empty($params['productListNo'])) {
            return [
                'success' => false,
                'message' => 'productListNo 不能为空'
            ];
        }

        $redis = new RedisService();
        $rank = $redis->incr($params["productListNo"]);
        $data = [
            "productListNo" => $params["productListNo"],
            "rank" => $rank,
            "supplierId" => $params['supplierId'] ?? null
        ];
        $this->logger->log2("productListNo {$params['productListNo']} rank => {$rank}");
        return [
            'success' => true,
            'data' => $data
        ];
    }
}
