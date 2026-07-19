<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as WriterXlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Reader\Csv as ReaderCsv;

/**
 * 导入导出文件工具类（基于PhpSpreadsheet）
 * SP脚本导出路径：sp/{type}/ → php/shell/sp/{type}/export/（自包含）
 * Class ExcelUtils
 */
class ExcelUtils
{
    public $downPath;

    public function __construct($downPath = "")
    {
        $downDefaultFile = __DIR__ . "/../export/uploads/";
        // SP脚本导出路径：sp/{type}/ → php/shell/sp/{type}/export/（自包含，便于独立提取）
        if (!empty($downPath) && strpos($downPath, 'sp/') === 0) {
            $downDefaultFile = __DIR__ . "/../shell/" . $downPath . "export/";
        }
        $this->downPath = !empty($downPath) ? $downDefaultFile : $downDefaultFile . "default/";
        $this->ensureExportDirectory();
    }

    private function ensureExportDirectory()
    {
        if (!is_dir($this->downPath)) {
            mkdir($this->downPath, 0777, true);
        }
        @chmod($this->downPath, 0777);
    }

    /**
     * 数据写入xlsx文件
     * @param array $titleList
     * @param array $data
     * @param string $fileName
     */
    public function download(array $titleList, array $data, $fileName = "")
    {
        $downDefaultFileName = "导出默认文件_" . date('YmdHis') . ".xlsx";
        $downFileName = !empty($fileName) ? $fileName : $downDefaultFileName;

        if (count($data) > 0) {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Sheet1');

            // 写入表头
            $keyNum = 'A';
            foreach ($data[0] as $key => $item) {
                $titleName = isset($titleList[$key]) ? $titleList[$key] : $key;
                $sheet->setCellValue($keyNum . '1', $titleName);
                $keyNum++;
            }

            // 写入数据
            $dataNum = 2;
            foreach ($data as $item) {
                $keyNum = 'A';
                foreach ($item as $itemSon) {
                    $sheet->setCellValue($keyNum . $dataNum, $itemSon);
                    $keyNum++;
                }
                $dataNum++;
            }

            $tmpName = $this->downPath . $downFileName;
            $objWriter = new WriterXlsx($spreadsheet);
            $objWriter->save($tmpName);
        }
    }

    /**
     * 导出xlsx文件
     * @param array $customHeaders
     * @param array $list
     * @param string $fileName
     * @return string
     */
    public function downloadXlsx($customHeaders, $list, $fileName = "")
    {
        if (empty($fileName)) {
            $fileName = "默认导出文件_" . date("YmdHis") . ".xlsx";
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Sheet1');

        // 设置表头 (PhpSpreadsheet 列索引从1开始)
        $columnIndex = 1;
        foreach ($customHeaders as $header) {
            $sheet->setCellValueByColumnAndRow($columnIndex, 1, $header);
            $columnIndex++;
        }

        // 填充数据
        $rowIndex = 2;
        foreach ($list as $row) {
            $columnIndex = 1;
            foreach ($row as $cellValue) {
                // 长数字ID写入为字符串，防止Excel科学计数法
                if (is_numeric($cellValue) && strlen((string)$cellValue) > 10) {
                    $sheet->getCellByColumnAndRow($columnIndex, $rowIndex)
                          ->setValueExplicit((string)$cellValue, DataType::TYPE_STRING);
                } else {
                    $sheet->setCellValueByColumnAndRow($columnIndex, $rowIndex, $cellValue);
                }
                $columnIndex++;
            }
            $rowIndex++;
        }

        // 保存文件
        $objWriter = new WriterXlsx($spreadsheet);
        $filePath = $this->downPath . "{$fileName}";
        $objWriter->save($filePath);

        return $filePath;
    }

    /**
     * 读取 xlsx/xls 文件
     * @param string $fileName
     * @return array
     * @throws Exception
     */
    public function _readXlsFile($fileName)
    {
        $returnArray = array();
        $spreadsheet = IOFactory::load($fileName);
        $sheetNames = $spreadsheet->getSheetNames();

        foreach ($sheetNames as $sheetId => $sheetName) {
            $sheetData = array();
            $sheet = $spreadsheet->getSheet($sheetId);
            $highestColumn = $sheet->getHighestColumn();
            $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

            // 读取列标题（第一行）— PhpSpreadsheet 列索引从1开始
            $columnArray = array();
            for ($excelColumnIndex = 1; $excelColumnIndex <= $highestColumnIndex; $excelColumnIndex++) {
                $columnArray[] = trim((string)$sheet->getCellByColumnAndRow($excelColumnIndex, 1)->getValue());
            }

            // 读取数据行
            $rowCount = $sheet->getHighestRow();
            for ($j = 2; $j <= $rowCount; $j++) {
                $data = array();
                foreach ($columnArray as $key => $columnName) {
                    $value = trim((string)$sheet->getCellByColumnAndRow($key + 1, $j)->getValue());
                    $data[$columnName] = $value;
                }
                $sheetData[] = $data;
            }
            $returnArray[$sheetName] = $sheetData;
        }

        return $returnArray;
    }

    /**
     * 逐行读取xlsx文件并通过回调处理每一行
     * @param string $filename 文件路径
     * @param callable $callback 回调函数，参数为每行数据（关联数组）
     * @param string $sheet 工作表名，默认Sheet1
     * @throws Exception
     */
    public function eachXlsxRow($filename, $callback, $sheet = 'Sheet1')
    {
        $rows = $this->getXlsxData($filename, $sheet);
        foreach ($rows as $row) {
            $callback($row);
        }
    }

    /**
     * 读取xlsx数据（按sheet名）
     * @param string $filename
     * @param string $sheet
     * @return array|mixed
     * @throws Exception
     */
    public function getXlsxData($filename, $sheet = 'Sheet1')
    {
        $fileContent = $this->_readXlsFile($filename);
        if (sizeof($fileContent) == 1) {
            // 单sheet时直接返回该sheet的数据，兼容任意sheet名
            return reset($fileContent) ?: [];
        } else {
            return isset($fileContent[$sheet]) ? $fileContent[$sheet] : [];
        }
    }

    public function getXlsxDataV2($filename, $sheet = 'Sheet1')
    {
        $fileContent = $this->_readXlsFileV2($filename);
        if (sizeof($fileContent) == 1) {
            // 单sheet时直接返回该sheet的数据，兼容任意sheet名
            return reset($fileContent) ?: [];
        } else {
            return isset($fileContent[$sheet]) ? $fileContent[$sheet] : [];
        }
    }

    /**
     * 读取CSV文件
     * @param string $csvPath
     * @return array
     */
    public function _readCSV($csvPath)
    {
        try {
            $reader = new ReaderCsv();
            $reader->setInputEncoding('UTF-8');
            $reader->setDelimiter(',');

            $spreadsheet = $reader->load($csvPath);
            $sheet = $spreadsheet->getActiveSheet();

            $highestColumn = $sheet->getHighestColumn();
            $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);
            $rowCount = $sheet->getHighestRow();

            // 读取标题行（第2行，跳过第1行空数据）
            $headerKeys = [];
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $headerKeys[] = $sheet->getCellByColumnAndRow($col, 2)->getValue();
            }

            // 强制指定需要文本格式的列（D/E列的adgroup_id）
            $textColumns = [4, 5]; // D=4, E=5 (1-based)

            $data = [];
            for ($row = 3; $row <= $rowCount; $row++) {
                $rowData = [];
                for ($col = 1; $col <= $highestColumnIndex; $col++) {
                    $key = $headerKeys[$col - 1] ?? $col;

                    if (in_array($col, $textColumns)) {
                        // 文本列：获取格式化值，强制字符串
                        $value = (string)$sheet->getCellByColumnAndRow($col, $row)->getFormattedValue();
                    } else {
                        $value = $sheet->getCellByColumnAndRow($col, $row)->getValue();
                    }

                    // 修复数字粘连问题
                    if (is_numeric($value) && strlen((string)$value) > 15) {
                        $value = (string)$value;
                    }

                    $rowData[$key] = $value;
                }
                $data[] = $rowData;
            }

            return $data;
        } catch (Exception $e) {
            die("读取CSV失败: " . $e->getMessage());
        }
    }

    /**
     * 读取xlsx文件（V2版本，处理科学计数法）
     * @param string $fileName
     * @return array
     */
    public function _readXlsFileV2($fileName)
    {
        $returnArray = array();

        if (function_exists('ini_set')) {
            @ini_set('memory_limit', '2048M');
        }
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        // 加载文件
        $spreadsheet = IOFactory::load($fileName);

        // 获取所有工作表名称
        $sheetNames = $spreadsheet->getSheetNames();

        foreach ($sheetNames as $sheetId => $sheetName) {
            $sheetData = array();
            $sheet = $spreadsheet->getSheet($sheetId);

            // 获取列数和行数
            $highestColumn = $sheet->getHighestColumn();
            $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);
            $rowCount = $sheet->getHighestRow();

            // 读取列标题（第一行）— PhpSpreadsheet 列索引从1开始
            $columnArray = array();
            for ($excelColumnIndex = 1; $excelColumnIndex <= $highestColumnIndex; $excelColumnIndex++) {
                $cell = $sheet->getCellByColumnAndRow($excelColumnIndex, 1);
                $columnArray[] = $this->_getCellValue($cell);
            }

            // 读取数据行（从第二行开始）
            for ($j = 2; $j <= $rowCount; $j++) {
                $data = array();
                foreach ($columnArray as $key => $columnName) {
                    $cell = $sheet->getCellByColumnAndRow($key + 1, $j);
                    $data[$columnName] = $this->_getCellValue($cell);
                }
                $sheetData[] = $data;
            }

            $returnArray[$sheetName] = $sheetData;
        }

        return $returnArray;
    }

    /**
     * 获取单元格值，处理长数字不转为科学计数法
     * @param Cell $cell 单元格对象
     * @return mixed 处理后的值
     */
    protected function _getCellValue(Cell $cell)
    {
        $value = $cell->getValue();

        // 处理富文本
        if ($value instanceof RichText) {
            $value = $value->getPlainText();
        }

        // 处理长数字（科学计数法防护）
        if (is_numeric($value)) {
            // 获取单元格格式
            $format = $cell->getStyle()->getNumberFormat()->getFormatCode();

            // 如果是常规格式且数字长度超过10位，转为字符串保持原样
            if ($format == NumberFormat::FORMAT_GENERAL &&
                strlen((string)$value) > 10) {
                return (string)$value;
            }

            // 如果是文本格式，直接返回字符串形式
            if ($format == NumberFormat::FORMAT_TEXT) {
                return (string)$value;
            }
        }

        // 去除前后空格
        return is_string($value) ? trim($value) : $value;
    }
}
