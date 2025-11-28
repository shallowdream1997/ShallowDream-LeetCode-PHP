<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Utils\DataUtils;

class UploadChunkController
{
    private string $targetDir;

    public function __construct()
    {
        $this->targetDir = STORAGE_PATH . '/exports/uploads/oss/';
        $this->ensureDirectory($this->targetDir);
    }

    public function handle(array $post, array $files): array
    {
        $filename = $post['filename'] ?? null;
        $chunkIndex = isset($post['chunkIndex']) ? (int)$post['chunkIndex'] : null;
        $totalChunks = isset($post['totalChunks']) ? (int)$post['totalChunks'] : null;
        $originalName = $post['name'] ?? null;

        if (!$filename || $chunkIndex === null || !$totalChunks || !$originalName) {
            return [
                "code" => 400,
                "message" => "缺少必要的分片上传参数"
            ];
        }

        if (!isset($files['file']) || $files['file']['error'] !== UPLOAD_ERR_OK) {
            return [
                "code" => 400,
                "message" => "分片上传失败",
            ];
        }

        $tempDir = $this->targetDir . "temp/";
        $this->ensureDirectory($tempDir);

        $partPath = $tempDir . $filename . '.part' . $chunkIndex;
        move_uploaded_file($files['file']['tmp_name'], $partPath);

        $resultList = [];
        if ($chunkIndex + 1 === $totalChunks) {
            $finalFile = $this->mergeChunks($filename, $totalChunks, $tempDir);
            $resultList[] = [
                "actualFileName" => basename($originalName),
                "fileName" => basename($finalFile),
                "fullPath" => $finalFile,
            ];
        }

        if (count($resultList) > 0){
            return [
                "code" => 200,
                "message" => "加载文件成功",
                "fileCollect" => $resultList
            ];
        }

        return [
            "code" => 200,
            "message" => "分片上传成功"
        ];
    }

    private function mergeChunks(string $filename, int $totalChunks, string $tempDir): string
    {
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $finalName = DataUtils::buildGenerateUuidLike() . ".{$ext}";
        $finalPath = $this->targetDir . $finalName;
        $out = fopen($finalPath, 'wb');

        for ($i = 0; $i < $totalChunks; $i++) {
            $partFilePath = $tempDir . $filename . '.part' . $i;
            if (file_exists($partFilePath)) {
                $in = fopen($partFilePath, 'rb');
                stream_copy_to_stream($in, $out);
                fclose($in);
                unlink($partFilePath);
            }
        }

        fclose($out);
        @rmdir($tempDir);
        return $finalPath;
    }

    private function ensureDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }
}