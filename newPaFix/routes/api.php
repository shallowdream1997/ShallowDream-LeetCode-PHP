<?php

declare(strict_types=1);

use App\Http\Controllers\ApiTestController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\UpdateController;
use App\Http\Controllers\UploadChunkController;
use App\Http\Controllers\UploadController;

return [
    [
        'method' => 'POST',
        'path' => '/api/search',
        'handler' => function () {
            $payload = json_decode(file_get_contents('php://input'), true) ?? [];
            $controller = new SearchController();
            return $controller->handle($payload);
        },
    ],
    [
        'method' => 'POST',
        'path' => '/api/update',
        'handler' => function () {
            $payload = json_decode(file_get_contents('php://input'), true) ?? [];
            $controller = new UpdateController();
            return $controller->handle($payload);
        },
    ],
    [
        'method' => 'POST',
        'path' => '/api/upload/excel',
        'handler' => function () {
            $controller = new UploadController();
            return $controller->handleExcelUpload($_FILES);
        },
    ],
    [
        'method' => 'POST',
        'path' => '/api/upload/oss',
        'handler' => function () {
            $controller = new UploadController();
            return $controller->handleOssUpload($_FILES);
        },
    ],
    [
        'method' => 'POST',
        'path' => '/api/upload/chunk',
        'handler' => function () {
            $controller = new UploadChunkController();
            return $controller->handle($_POST, $_FILES);
        },
    ],
    [
        'method' => 'POST',
        'path' => '/api/test/incr',
        'handler' => function () {
            $payload = json_decode(file_get_contents('php://input'), true) ?? [];
            $controller = new ApiTestController();
            return $controller->handle($payload);
        },
    ],
];

