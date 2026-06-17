<?php
require dirname(__FILE__) . '/../../vendor/autoload.php';

//use PhpOffice\PhpSpreadsheet\Spreadsheet;
//use PhpOffice\PhpSpreadsheet\Writer\Xlsx as WriteXlsx;
//use PhpOffice\PhpSpreadsheet\Reader\Xlsx as ReaderXlsx;
//use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * 导入导出文件工具类
 * Class ExcelUtils
 */
class ExcelUtils
{
    public $downPath;
    private const XLSX_MAIN_NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    private const XLSX_REL_NS = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
    private const XLSX_PACKAGE_REL_NS = 'http://schemas.openxmlformats.org/package/2006/relationships';

    public function __construct($downPath = "")
    {
        $downDefaultFile = __DIR__ . "/../export/uploads/";
        $this->downPath = !empty($downPath) ? $downDefaultFile . $downPath : $downDefaultFile . "default/";
        $this->ensureExportDirectory();
    }

    /**
     * 数据写入xls文件,下载文件
     * @param array $titleList $header = [
     * '_id' => '主键',
     * 'channel' => '渠道',
     * ];
     * @param array $data $export = [
     * [
     * "_id" => "sasdadada",
     * "channel" => "amazon_us"
     * ]
     * ];
     * @param string $fileName "开发清单_".date("YmdHis").".xlsx"
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Writer_Exception
     */
    public function download(array $titleList, array $data, $fileName = "")
    {
        $this->ensurePHPExcelLoaded();
        $downDefaultFileName = "导出默认文件_" . date('YmdHis') . ".xlsx";
        $downFileName = !empty($fileName) ? $fileName : $downDefaultFileName;

        if (count($data) > 0) {
            $obj = new \PHPExcel();
            $obj->removeSheetByIndex(0);
            $index = 0;
            // 获取表头
            $title = array();
            $obj->createSheet();
            $obj->setActiveSheetIndex($index);
            $obj->getActiveSheet($index)->setTitle('Sheet' . ($index + 1));
            $titleNum = 1;
            $dataNum = 2;
            $keyNum = 'A';
            $checkTitle = array();
            foreach ($data[0] as $key => $item) {
                $titleName = isset($titleList[$key]) ? $titleList[$key] : $key;
                $obj->getActiveSheet($index)->setCellValue($keyNum . $titleNum, $titleName);
                $keyNum++;
                unset($titleName);
            }
            foreach ($data as $item) {
                $keyNum = 'A';
                foreach ($item as $key => $itemSon) {
                    $obj->getActiveSheet($index)->setCellValue($keyNum . $dataNum, $itemSon);
                    $keyNum++;
                }
                $dataNum++;
                unset($item);
            }
            unset($data);
            $tmpName = $this->downPath . $downFileName;
            $objWriter = new \PHPExcel_Writer_Excel5($obj);
            $objWriter->save($tmpName);

        }
    }

    /**
     * 导出xlsx文件
     * @param $customHeaders
     * @param $list
     * @param string $fileName
     * @return false|string
     * @throws PHPExcel_Exception
     * @throws PHPExcel_Reader_Exception
     * @throws PHPExcel_Writer_Exception
     */
    public function downloadXlsx($customHeaders,$list,$fileName = "", $textColumns = [])
    {
        $this->ensurePHPExcelLoaded();
        if (empty($fileName)){
            $fileName  = "默认导出文件_".date("YmdHis").".xlsx";
        }
        // 创建一个新的 PHPExcel 对象
        $objPHPExcel = new PHPExcel();

        // 设置当前活动的工作表
        $objPHPExcel->setActiveSheetIndex(0);
        $sheet = $objPHPExcel->getActiveSheet();

        // 设置表头
        $columnIndex = 0;

        foreach ($customHeaders as $header) {
            // 设置自定义表头
            $sheet->setCellValueByColumnAndRow($columnIndex, 1, $header);
            $columnIndex++;
        }

        // 填充数据
        $rowIndex = 2; // 从第二行开始填充数据
        foreach ($list as $row) {
            $columnIndex = 0;
            foreach ($row as $cellValue) {
                if (in_array($columnIndex, $textColumns)) {
                    $sheet->setCellValueExplicitByColumnAndRow($columnIndex, $rowIndex, $cellValue, PHPExcel_Cell_DataType::TYPE_STRING);
                } else {
                    $sheet->setCellValueByColumnAndRow($columnIndex, $rowIndex, $cellValue);
                }
                $columnIndex++;
            }
            $rowIndex++;
        }

        // 设置文件格式和保存路径
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

        // 保存文件到指定路径
        $filePath = $this->downPath ."{$fileName}";
        $objWriter->save($filePath);

        return $filePath;

        //// 保存文件
        //$objWriter->save($this->downPath ."{$fileName}_".date("YmdHis").".xlsx");

    }


    /**
     * 读取 xls 文件
     * @param $fileName
     * @return array
     * @throws Exception
     */
    public function _readXlsFile($fileName)
    {
        if ($this->isXlsxFile($fileName)) {
            return $this->_readXlsxFileStream($fileName);
        }

        $this->ensurePHPExcelLoaded();
        $returnArray = array();
        $cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
        $cacheSettings = array(
            'memoryCacheSize' => '64MB'
        );
        PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);
        $objReader = PHPExcel_IOFactory::createReaderForFile($fileName);
        $objReader->setReadDataOnly(true);
        $objPHPExcel = $objReader->load($fileName);
        $sheetNames = $objPHPExcel->getSheetNames();
        foreach ($sheetNames as $sheetId => $sheetName) {
            $sheetData = array();
            $sheet = $objPHPExcel->getSheet($sheetId);
            $highestColumn = $sheet->getHighestColumn(); // 获取最后一列的列名
            $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn); // 取得excel中的列数

            $columnArray = array();
            for ($excelColumnIndex = 0; $excelColumnIndex < $highestColumnIndex; $excelColumnIndex++) {
                $columnArray[] = trim($sheet->getCellByColumnAndRow($excelColumnIndex, 1)->getValue());
            }

            $rowCount = $sheet->getHighestRow(); // 行数
            for ($j = 2; $j <= $rowCount; $j++) {
                $emptyColumn = 0;
                $data = array();
                foreach ($columnArray as $key => $columnName) {
                    $value = trim($sheet->getCellByColumnAndRow($key, $j)->getValue());
                    $data[$columnName] = $value;
                }
                $sheetData[] = $data;
            }
            $returnArray[$sheetName] = $sheetData;
        }
        $objPHPExcel->disconnectWorksheets();
        unset($objPHPExcel);

        return $returnArray;
    }

    public function eachXlsxRow($filename, callable $callback, $sheet = null)
    {
        if ($this->isXlsxFile($filename)) {
            $this->_streamXlsxRows($filename, $callback, $sheet);
            return;
        }

        $rows = $this->getXlsxData($filename, $sheet ?: 'Sheet1');
        foreach ($rows as $row) {
            $callback($row);
        }
    }

    /**
     * 读取csv文件数据
     * @param $filename
     * @param string $sheet
     * @return array|mixed
     * @throws Exception
     */
    public function getXlsxData($filename, $sheet = 'Sheet1')
    {
        $fileContent = $this->_readXlsFile($filename);
        if (sizeof($fileContent) == 1){
            return isset($fileContent[$sheet]) ? $fileContent[$sheet] : [];
        }else{
            return $fileContent;
        }
    }

    public function getXlsxDataV2($filename, $sheet = 'Sheet1')
    {
        $fileContent = $this->_readXlsFileV2($filename);
        if (sizeof($fileContent) == 1){
            return isset($fileContent[$sheet]) ? $fileContent[$sheet] : [];
        }else{
            return $fileContent;
        }
    }

    /**
     * 读取json文件数据
     * @param $filename
     * @return mixed|null
     */
    private function getJsonDate($filename)
    {
        $json_content = null;
        if (file_exists($filename)) {
            $content = file_get_contents($filename);
            $json_content = json_decode($content, true);
        }
        return $json_content;
    }


//    public function _readXlsFileV2($fileName){
//        // 载入 Excel 文件
//        $spreadsheet = IOFactory::load($fileName);
//        $worksheet = $spreadsheet->getActiveSheet();
//
//        if (count($worksheet->toArray()) == 0){
//            return [];
//        }
//
//        $headerArray = $worksheet->toArray()[0];
//        $list = [];
//        if (count($worksheet->toArray()) >= 1){
//            for ($index = 1;$index < count($worksheet->toArray());$index++){
//                $list[] = array_combine($headerArray,$worksheet->toArray()[$index]);
//            }
//        }
//        return $list;
//    }


    public function _readCSV($csvPath)
    {
        try {
            $this->ensurePHPExcelLoaded();
            $reader = new PHPExcel_Reader_CSV();
            $reader->setInputEncoding('UTF-8');
            $reader->setDelimiter(',');

            // 指定从第2行开始读取标题（跳过首行空数据）
            $reader->setRowIteratorStart(2);

            $phpExcel = $reader->load($csvPath);
            $sheet = $phpExcel->getActiveSheet();

            // 读取真实标题行（图片中的第2行）
            $headerRow = $sheet->getRowIterator()->current();
            $headerKeys = [];
            foreach ($headerRow->getCellIterator() as $cell) {
                $headerKeys[] = $cell->getValue(); // 标题如campaign_id, adgroup_name等
            }

            // 强制指定需要文本格式的列（D/E列的adgroup_id）
            $textColumns = ['D', 'E'];

            $data = [];
            $rowIterator = $sheet->getRowIterator();
            $rowIterator->resetStart(3); // 数据从第3行开始

            foreach ($rowIterator as $row) {
                $rowData = [];
                foreach ($row->getCellIterator() as $col => $cell) {
                    $key = $headerKeys[PHPExcel_Cell::columnIndexFromString($col) - 1] ?? $col;

                    // 针对D/E列强制文本格式读取
                    if (in_array($col, $textColumns)) {
                        $value = $cell->getFormattedValue(); // 直接获取显示值（如5.48474E+14原文）
                        $value = (string)$value;
                    } else {
                        $value = $cell->getValue();
                    }

                    // 修复图片中数字粘连问题（如311196306576001411arrc250326）
                    if (is_numeric($value) && strlen($value) > 15) {
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



    public function _readXlsFileV2($fileName)
    {
        $returnArray = array();

        // 设置缓存以提高大文件处理性能
        $cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
        $cacheSettings = array('memoryCacheSize' => '1024MB');
        PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);

        // 加载文件
        $objPHPExcel = PHPExcel_IOFactory::load($fileName);

        // 获取所有工作表名称
        $sheetNames = $objPHPExcel->getSheetNames();

        foreach ($sheetNames as $sheetId => $sheetName) {
            $sheetData = array();
            $sheet = $objPHPExcel->getSheet($sheetId);

            // 获取列数和行数
            $highestColumn = $sheet->getHighestColumn();
            $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
            $rowCount = $sheet->getHighestRow();

            // 读取列标题（第一行）
            $columnArray = array();
            for ($excelColumnIndex = 0; $excelColumnIndex < $highestColumnIndex; $excelColumnIndex++) {
                $cell = $sheet->getCellByColumnAndRow($excelColumnIndex, 1);
                $columnArray[] = $this->_getCellValue($cell);
            }

            // 读取数据行（从第二行开始）
            for ($j = 2; $j <= $rowCount; $j++) {
                $data = array();
                foreach ($columnArray as $key => $columnName) {
                    $cell = $sheet->getCellByColumnAndRow($key, $j);
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
     * @param PHPExcel_Cell $cell 单元格对象
     * @return mixed 处理后的值
     */
    protected function _getCellValue(PHPExcel_Cell $cell)
    {
        $value = $cell->getValue();

        // 处理富文本
        if ($value instanceof PHPExcel_RichText) {
            $value = $value->getPlainText();
        }

        // 处理长数字
        if (is_numeric($value)) {
            // 获取单元格格式
            $format = $cell->getStyle()->getNumberFormat()->getFormatCode();

            // 如果是常规格式且数字长度超过10位，转为字符串保持原样
            if ($format == PHPExcel_Style_NumberFormat::FORMAT_GENERAL &&
                strlen((string)$value) > 10) {
                return (string)$value;
            }

            // 如果是文本格式，直接返回字符串形式
            if ($format == PHPExcel_Style_NumberFormat::FORMAT_TEXT) {
                return (string)$value;
            }
        }

        // 去除前后空格
        return is_string($value) ? trim($value) : $value;
    }

    private function isXlsxFile($fileName)
    {
        return strtolower(pathinfo($fileName, PATHINFO_EXTENSION)) === 'xlsx';
    }

    private function _readXlsxFileStream($fileName)
    {
        $returnArray = array();
        $this->_streamXlsxRows($fileName, function ($row, $sheetName) use (&$returnArray) {
            if (!isset($returnArray[$sheetName])) {
                $returnArray[$sheetName] = array();
            }
            $returnArray[$sheetName][] = $row;
        });

        return $returnArray;
    }

    private function _streamXlsxRows($fileName, callable $callback, $targetSheet = null)
    {
        $zip = new ZipArchive();
        if ($zip->open($fileName) !== true) {
            throw new Exception("无法打开Excel文件: {$fileName}");
        }

        try {
            $sharedStrings = $this->_loadXlsxSharedStrings($zip);
            $sheetPaths = $this->_getXlsxSheetPaths($zip);

            foreach ($sheetPaths as $sheetName => $sheetPath) {
                if ($targetSheet !== null && $sheetName !== $targetSheet) {
                    continue;
                }
                $this->_streamXlsxSheetRows($zip, $sheetPath, $sharedStrings, $sheetName, $callback);
            }
        } finally {
            $zip->close();
        }
    }

    private function _loadXlsxSharedStrings(ZipArchive $zip)
    {
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsXml === false) {
            return array();
        }

        $reader = new XMLReader();
        $reader->XML($sharedStringsXml, null, LIBXML_NONET | LIBXML_COMPACT);
        $sharedStrings = array();

        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 'si') {
                $sharedStrings[] = $this->_readXlsxSharedStringItem($reader);
            }
        }

        $reader->close();

        return $sharedStrings;
    }

    private function _readXlsxSharedStringItem(XMLReader $reader)
    {
        $depth = $reader->depth;
        $value = '';

        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 't') {
                $value .= $reader->readString();
                continue;
            }

            if ($reader->nodeType === XMLReader::END_ELEMENT && $reader->localName === 'si' && $reader->depth === $depth) {
                break;
            }
        }

        return trim($value);
    }

    private function _getXlsxSheetPaths(ZipArchive $zip)
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($workbookXml === false || $relsXml === false) {
            throw new Exception('Excel工作簿结构不完整');
        }

        $workbook = simplexml_load_string($workbookXml);
        $rels = simplexml_load_string($relsXml);
        if ($workbook === false || $rels === false) {
            throw new Exception('Excel工作簿解析失败');
        }

        $workbook->registerXPathNamespace('main', self::XLSX_MAIN_NS);
        $workbook->registerXPathNamespace('rel', self::XLSX_REL_NS);
        $rels->registerXPathNamespace('rel', self::XLSX_PACKAGE_REL_NS);

        $relationshipMap = array();
        $relationshipNodes = $rels->xpath('/rel:Relationships/rel:Relationship');
        if ($relationshipNodes !== false) {
            foreach ($relationshipNodes as $relationshipNode) {
                $attributes = $relationshipNode->attributes();
                $target = (string) $attributes['Target'];
                $relationshipMap[(string) $attributes['Id']] = strpos($target, 'xl/') === 0 ? $target : 'xl/' . ltrim($target, '/');
            }
        }

        $sheetPaths = array();
        $sheetNodes = $workbook->xpath('/main:workbook/main:sheets/main:sheet');
        if ($sheetNodes !== false) {
            foreach ($sheetNodes as $sheetNode) {
                $attributes = $sheetNode->attributes('r', true);
                $relationshipId = (string) $attributes['id'];
                $sheetName = (string) $sheetNode['name'];
                if (isset($relationshipMap[$relationshipId])) {
                    $sheetPaths[$sheetName] = $relationshipMap[$relationshipId];
                }
            }
        }

        return $sheetPaths;
    }

    private function _streamXlsxSheetRows(ZipArchive $zip, $sheetPath, array $sharedStrings, $sheetName, callable $callback)
    {
        $sheetXml = $zip->getFromName($sheetPath);
        if ($sheetXml === false) {
            return;
        }

        $reader = new XMLReader();
        $reader->XML($sheetXml, null, LIBXML_NONET | LIBXML_COMPACT);
        $headers = array();
        $headerRowIndex = null;

        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'row') {
                continue;
            }

            $rowIndex = (int) $reader->getAttribute('r');
            $cells = $this->_readXlsxRowCells($reader, $sharedStrings);
            if (empty($cells)) {
                continue;
            }

            if ($headerRowIndex === null) {
                $headers = $cells;
                $headerRowIndex = $rowIndex;
                continue;
            }

            $rowData = array();
            foreach ($headers as $columnIndex => $columnName) {
                if ($columnName === '' || $columnName === null) {
                    continue;
                }
                $rowData[$columnName] = isset($cells[$columnIndex]) ? $cells[$columnIndex] : '';
            }

            $callback($rowData, $sheetName, $rowIndex);
        }

        $reader->close();
    }

    private function _readXlsxRowCells(XMLReader $reader, array $sharedStrings)
    {
        $rowDepth = $reader->depth;
        $cells = array();

        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 'c') {
                $cellReference = (string) $reader->getAttribute('r');
                $columnLetters = rtrim($cellReference, '0123456789');
                $columnIndex = $this->_columnLettersToIndex($columnLetters);
                $cellType = (string) $reader->getAttribute('t');
                $cells[$columnIndex] = $this->_readXlsxCellValue($reader, $cellType, $sharedStrings);
                continue;
            }

            if ($reader->nodeType === XMLReader::END_ELEMENT && $reader->localName === 'row' && $reader->depth === $rowDepth) {
                break;
            }
        }

        ksort($cells);

        return $cells;
    }

    private function _readXlsxCellValue(XMLReader $reader, $cellType, array $sharedStrings)
    {
        $cellDepth = $reader->depth;
        $rawValue = '';

        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT && ($reader->localName === 'v' || $reader->localName === 't')) {
                $rawValue .= $reader->readString();
                continue;
            }

            if ($reader->nodeType === XMLReader::END_ELEMENT && $reader->localName === 'c' && $reader->depth === $cellDepth) {
                break;
            }
        }

        if ($cellType === 's') {
            $sharedStringIndex = (int) $rawValue;
            return isset($sharedStrings[$sharedStringIndex]) ? $sharedStrings[$sharedStringIndex] : '';
        }

        if ($cellType === 'b') {
            return $rawValue === '1';
        }

        return trim($rawValue);
    }

    private function _columnLettersToIndex($columnLetters)
    {
        $columnLetters = strtoupper($columnLetters);
        $length = strlen($columnLetters);
        $index = 0;

        for ($i = 0; $i < $length; $i++) {
            $index = ($index * 26) + (ord($columnLetters[$i]) - 64);
        }

        return $index - 1;
    }

    private function ensurePHPExcelLoaded()
    {
        if (!class_exists('PHPExcel', false)) {
            require_once(dirname(__FILE__) . "/../../extends/PHPExcel-1.8/Classes/PHPExcel.php");
        }
    }

    private function ensureExportDirectory()
    {
        $exportRootDir = __DIR__ . "/../export/uploads";
        if (!is_dir($exportRootDir)) {
            mkdir($exportRootDir, 0777, true);
        }
        @chmod($exportRootDir, 0777);

        if (!is_dir($this->downPath)) {
            mkdir($this->downPath, 0777, true);
        }
        @chmod($this->downPath, 0777);
    }



}
