<?php
require dirname(__FILE__) . '/../../vendor/autoload.php';

require_once(dirname(__FILE__) . "/../../extends/PHPExcel-1.8/Classes/PHPExcel.php");

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
    private $downPath;

    public function __construct($downPath = "")
    {
        $downDefaultFile = "/var/www/html/testProject/php/download/default/";
        $this->downPath = !empty($downPath) ? "/var/www/html/testProject/php/download/" . $downPath . "/" : $downDefaultFile;
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
     * 读取 xls 文件
     * @param $fileName
     * @return array
     * @throws Exception
     */
    private function _readXlsFile($fileName)
    {
        $returnArray = array();
        $objPHPExcel = PHPExcel_IOFactory::load($fileName);
        $cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
        $cacheSettings = array(
            'memoryCacheSize' => '512MB'
        );
        PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);
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

        return $returnArray;
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
        if (sizeof($fileContent[$sheet]) > 0) {
            return $fileContent[$sheet];
        } else {
            return [];
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
}