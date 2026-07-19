<?php
/**
 * 文件上传 API
 * POST /api/upload
 * Content-Type: multipart/form-data
 * Fields: file (文件), sessionId (会话ID)
 * 支持 xlsx/xls/csv 格式
 */

require_once dirname(__FILE__) . '/../bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

// CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$sessionId = isset($_POST['sessionId']) ? trim($_POST['sessionId']) : '';
if (empty($sessionId)) {
    echo json_encode(['error' => '缺少 sessionId'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $errMsg = '上传失败';
    if (isset($_FILES['file'])) {
        switch ($_FILES['file']['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $errMsg = '文件大小超出限制';
                break;
            case UPLOAD_ERR_NO_FILE:
                $errMsg = '没有选择文件';
                break;
        }
    }
    echo json_encode(['error' => $errMsg], JSON_UNESCAPED_UNICODE);
    exit;
}

$file = $_FILES['file'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowedExts = array('xlsx', 'xls', 'csv');

if (!in_array($ext, $allowedExts)) {
    echo json_encode(['error' => '仅支持 xlsx/xls/csv 格式文件'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 文件大小限制 (50MB)
$maxSize = 50 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    echo json_encode(['error' => '文件大小不能超过 50MB'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 存储到 php/export/uploads/{sessionId}/
$uploadDir = dirname(__FILE__) . '/../export/uploads/' . $sessionId . '/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// 生成文件 ID
$fileId = 'f_' . time() . '_' . bin2hex(openssl_random_pseudo_bytes(4));
$savePath = $uploadDir . $fileId . '.' . $ext;

if (!move_uploaded_file($file['tmp_name'], $savePath)) {
    echo json_encode(['error' => '文件保存失败'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 读取 Excel 预览
$preview = array();
$columns = array();
$totalRows = 0;
try {
    $excelUtils = new ExcelUtils();
    $rows = $excelUtils->getXlsxData($savePath);
    $totalRows = count($rows);
    $preview = array_slice($rows, 0, 5);
    if ($totalRows > 0) {
        $columns = array_keys($rows[0]);
    }
} catch (Exception $e) {
    // 预览失败不影响上传
}

// 记录文件元信息到 Redis
$fileMeta = array(
    'id' => $fileId,
    'name' => $file['name'],
    'path' => $savePath,
    'size' => $file['size'],
    'ext' => $ext,
    'columns' => $columns,
    'rows' => $totalRows,
    'upload_time' => date('Y-m-d H:i:s'),
);

try {
    $redis = new RedisService();
    $redis->hSet('pa_ai_files_' . $sessionId, $fileId, json_encode($fileMeta, JSON_UNESCAPED_UNICODE));
} catch (Exception $e) {
    // Redis 不可用时仍可上传，但无法在 AI 会话中引用
}

echo json_encode(array(
    'success' => true,
    'file' => $fileMeta,
    'preview' => $preview,
), JSON_UNESCAPED_UNICODE);
