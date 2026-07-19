<?php
/**
 * 文件预览 API
 * GET /api/file_preview?sessionId=xxx&fileId=xxx&rows=50&sheet=Sheet1
 * 读取上传的 Excel/CSV 文件内容
 */

require_once dirname(__FILE__) . '/../bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$sessionId = isset($_GET['sessionId']) ? trim($_GET['sessionId']) : '';
$fileId = isset($_GET['fileId']) ? trim($_GET['fileId']) : '';
$rows = isset($_GET['rows']) ? intval($_GET['rows']) : 50;
$sheet = isset($_GET['sheet']) ? trim($_GET['sheet']) : 'Sheet1';

if (empty($sessionId) || empty($fileId)) {
    echo json_encode(['error' => '缺少 sessionId 或 fileId'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 从 Redis 获取文件元信息
$filePath = '';
$fileMeta = null;
try {
    $redis = new RedisService();
    $meta = $redis->hGet('pa_ai_files_' . $sessionId, $fileId);
    if ($meta) {
        $fileMeta = json_decode($meta, true);
        if ($fileMeta && file_exists($fileMeta['path'])) {
            $filePath = $fileMeta['path'];
        }
    }
} catch (Exception $e) {
}

if (empty($filePath) || !file_exists($filePath)) {
    echo json_encode(['error' => '文件不存在'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $excelUtils = new ExcelUtils();
    $data = $excelUtils->getXlsxData($filePath, $sheet);
    $columns = count($data) > 0 ? array_keys($data[0]) : array();
    $result = array_slice($data, 0, $rows);

    echo json_encode(array(
        'file' => $fileMeta['name'],
        'columns' => $columns,
        'total_rows' => count($data),
        'showing_rows' => count($result),
        'data' => $result,
    ), JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['error' => '读取文件失败: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
