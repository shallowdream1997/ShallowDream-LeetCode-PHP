<?php
require_once dirname(__FILE__) . '/../requiredfile/requiredChorm.php';

class upload
{
    private $target_dir;

    public function __construct($target_dir) {
        $this->target_dir = $target_dir;
        if (!file_exists($this->target_dir)) {
            mkdir($this->target_dir, 0777, true);
        }
    }

    public function upload($file) {
        // 检查是否有文件被上传
        if ($file['error'] > 0) {
            return [
                "code" => 400,
                "message" => "错误: " . $file['error'],
                "fileName" => null,
                "excelList" => []
            ];
        } else {
            // 获取文件名
            $file_name = basename($file['name']);
            if (!in_array(pathinfo($file_name, PATHINFO_EXTENSION),["xlsx","xls"])){
                return [
                    "code" => 200,
                    "message" => "上传文件必须为xlsx或xls类型",
                    "fileName" => "",
                    "excelList" => []
                ];
            }
            // 生成唯一的文件名
            $unique_name = uniqid() . '-' . $file_name;
            $target_file = $this->target_dir . $unique_name;

            // 移动文件到指定目录
            if (move_uploaded_file($file['tmp_name'], $target_file)) {

                $excel = new ExcelUtils();
                $excelList = $excel->_readXlsFileV2("../export/uploads/{$unique_name}");
                return [
                    "code" => 200,
                    "message" => "上传文件成功",
                    "fileName" => $unique_name,
                    "excelList" => $excelList
                ];
            } else {
                return [
                    "code" => 400,
                    "message" => "上传文件出错",
                    "fileName" => null,
                    "excelList" => []
                ];
            }
        }
    }
}

// 使用示例
$target_dir = __DIR__ . "/../export/uploads/";
$fileUploader = new upload($target_dir);

if (isset($_FILES['fileToUpload'])) {
    $upload_result = $fileUploader->upload($_FILES['fileToUpload']);
    echo json_encode($upload_result,JSON_UNESCAPED_UNICODE);
}