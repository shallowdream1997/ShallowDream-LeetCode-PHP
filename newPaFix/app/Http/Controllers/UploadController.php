<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Utils\DataUtils;
use App\Utils\ExcelUtils;

class UploadController
{
    private string $excelDir;
    private string $ossDir;

    public function __construct()
    {
        $this->excelDir = STORAGE_PATH . '/exports/uploads/';
        $this->ossDir = STORAGE_PATH . '/exports/uploads/oss/';
        $this->ensureDirectory($this->excelDir);
        $this->ensureDirectory($this->ossDir);
    }

    public function handleExcelUpload(array $files): array
    {
        if (!isset($files['fileToUpload'])) {
            return [
                'code' => 400,
                'message' => '缺少 fileToUpload 文件字段',
                'fileName' => null,
                'excelList' => []
            ];
        }

        return $this->uploadExcel($files['fileToUpload']);
    }

    public function handleOssUpload(array $files): array
    {
        if (!isset($files['fileToUploadOss'])) {
            return [
                'code' => 400,
                'message' => '缺少 fileToUploadOss 文件字段',
            ];
        }

        return $this->uploadOssFiles($files['fileToUploadOss']);
    }

    private function uploadExcel(array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return [
                "code" => 400,
                "message" => "错误: " . ($file['error'] ?? '未知错误'),
                "fileName" => null,
                "excelList" => []
            ];
        }

        $originalName = basename($file['name']);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($extension, ["xlsx", "xls"], true)) {
                return [
                "code" => 400,
                    "message" => "上传文件必须为xlsx或xls类型",
                    "fileName" => "",
                    "excelList" => []
                ];
            }

        $uniqueName = uniqid('', true) . '-' . $originalName;
        $targetFile = $this->excelDir . $uniqueName;

        if (!move_uploaded_file($file['tmp_name'], $targetFile)) {
            return [
                "code" => 400,
                "message" => "上传文件出错",
                "fileName" => null,
                "excelList" => []
            ];
        }

                $excel = new ExcelUtils();
        $excelList = $excel->_readXlsFile($targetFile);
        $list = reset($excelList) ?: [];

                return [
                    "code" => 200,
                    "message" => "上传文件成功",
            "fileName" => $uniqueName,
                    "excelList" => $list
                ];
    }

    private function uploadOssFiles(array $files): array
    {
        $resultList = [];
        $fileCount = is_array($files['name']) ? count($files['name']) : 0;

        for ($i = 0; $i < $fileCount; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                return [
                    "code" => 400,
                    "message" => "错误: {$files['name'][$i]} " . $files['error'][$i],
                    "fileName" => null,
                    "excelList" => []
                ];
            }

            $fileName = basename($files['name'][$i]);
            $ext = pathinfo($fileName, PATHINFO_EXTENSION);
            $storedName = DataUtils::buildGenerateUuidLike() . ".{$ext}";
            $targetFile = $this->ossDir . $fileName;

            if (!move_uploaded_file($files['tmp_name'][$i], $targetFile)) {
                            return [
                                "code" => 400,
                                "message" => "加载文件出错",
                    "fileName" => $fileName,
                                "excelList" => []
                            ];
                    }

            $resultList[] = [
                "actualFileName" => $fileName,
                "fileName" => $storedName,
                "fullPath" => $targetFile,
            ];
                }

        if (count($resultList) === 0) {
            return [
                "code" => 400,
                "message" => "未收到任何文件",
            ];
            }

                return [
                    "code" => 200,
                    "message" => "加载文件成功",
                    "fileCollect" => $resultList
                ];
            }

    private function ensureDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }
}

// 使用示例
if (isset($_FILES['fileToUpload'])) {
    $target_dir = __DIR__ . "/../export/uploads/";
    $fileUploader = new upload($target_dir);

    $upload_result = $fileUploader->upload($_FILES['fileToUpload']);
    echo json_encode($upload_result,JSON_UNESCAPED_UNICODE);
}else if (isset($_FILES['fileToUploadOss'])){
    $target_dir = __DIR__ . "/../export/uploads/oss/";
    $fileUploader = new upload($target_dir);
    $return = $fileUploader->uploadOss($_FILES['fileToUploadOss']);
    echo json_encode($return,JSON_UNESCAPED_UNICODE);
}