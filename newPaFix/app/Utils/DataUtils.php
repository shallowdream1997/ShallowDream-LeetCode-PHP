<?php

declare(strict_types=1);

namespace App\Utils;

class DataUtils
{
    public function __construct(){

    }

    /**
     * 接收响应数据， 返回结果
     * @param null $response
     * @return array
     */
    public static function getResultData($response = null)
    {
        $data = [];
        if (!$response){
            return $data;
        }
        if ($response && isset($response['httpCode']) &&
            $response['httpCode'] && isset($response['result']) && !empty($response['result'])){
            $data = $response['result'];
        }
        return $data;
    }

    /**
     * 接收响应数据，返回分页下的列表，不包含分页页码信息
     * @param null $response
     * @return array
     */
    public static function getPageList($response = null)
    {
        $data = [];
        if (!$response){
            return $data;
        }
        if ($response && isset($response['httpCode']) &&
            $response['httpCode'] === 200 && isset($response['result']) && !empty($response['result']) && isset($response['result']['data']) && count($response['result']['data']) > 0){
            $data = $response['result']['data'];
        }
        return $data;
    }

    /**
     * 接收响应数据，返回query查询下的全局列表，
     * @param null $response 响应内容
     * @return array
     */
    public static function getQueryList($response = null)
    {
        $data = [];
        if (!$response){
            return $data;
        }
        if ($response && isset($response['httpCode']) &&
            $response['httpCode'] === 200 && isset($response['result']) && !empty($response['result']) && isset($response['result']) && count($response['result']) > 0){
            $data = $response['result'];
        }
        return $data;
    }

    /**
     * node后端请求的分页接口里面，有一个特殊的返回格式，就是doc
     * @param null $response
     * @return array
     */
    public static function getPageDocList($response = null)
    {
        $data = [];
        if (!$response){
            return $data;
        }
        if ($response && isset($response['httpCode']) &&
            $response['httpCode'] === 200 && isset($response['result']) && !empty($response['result']) && isset($response['result']['data'])
            && isset($response['result']['data']['docs']) && count($response['result']['data']['docs']) > 0){
            $data = $response['result']['data']['docs'];
        }
        return $data;
    }

    /**
     * 接收响应数据，返回分页下的列表，返回列表里面的第一个数据
     * @param null $response
     * @return array
     */
    public static function getPageListInFirstData($response = null)
    {
        $data = [];
        if (!$response){
            return $data;
        }
        if ($response && isset($response['httpCode']) &&
            $response['httpCode'] === 200 && isset($response['result']) && !empty($response['result']) && isset($response['result']['data']) && count($response['result']['data']) > 0){
            $data = $response['result']['data'][0];
        }
        return $data;
    }
    /**
     * 接收响应数据，返回分页下的列表，返回列表里面的第一个数据
     * @param null $response
     * @return array
     */
    public static function getPageListInFirstDataV2($response = null)
    {
        $data = [];
        if (!$response){
            return $data;
        }
        if ($response && isset($response['httpCode']) &&
            $response['httpCode'] === 200 && isset($response['result']) && !empty($response['result']) && isset($response['result']['data']) && count($response['result']['data']['data']) > 0){
            $data = $response['result']['data']['data'][0];
        }
        return $data;
    }
    /**
     * 接收响应数据，返回query的列表，返回列表里面的第一个数据
     * @param null $response
     * @return array
     */
    public static function getQueryListInFirstDataV3($response = null)
    {
        $data = [];
        if (!$response){
            return $data;
        }
        if ($response && isset($response['httpCode']) &&
            $response['httpCode'] === 200 && isset($response['result']) && !empty($response['result']) && count($response['result']) > 0){
            $data = $response['result'][0];
        }
        return $data;
    }

    /**
     * 取值
     * @param $data
     * @return array|mixed
     */
    public static function getOptionVal($data)
    {
        $info = [];
        if (self::checkArrFilesIsExist($data,'optionName')){
            $info = $data['optionVal'];
        }
        return $info;
    }

    /**
     * 获取数组里面的第一个值
     * @param array $list 数组
     * @return array
     */
    public static function getArrHeadData(array $list)
    {
        $data = [];
        if (!empty($list) && count($list) > 0){
            $data = $list[0];
        }
        return $data;
    }

    /**
     * 检查数组里面是否存在该字段且不为空，存在则true，不存在则false
     * @param array $array 数组
     * @param string $field 字段名称
     * @return bool
     */
    public static function checkArrFilesIsExist(array $array,string $field, $checkEmpty = true): bool
    {
        return (isset($array[$field]) && ($checkEmpty ? !empty($array[$field]) : true));
    }

    /**
     * 根据条件查询数组的下标以及下标所在的数据
     * @param array $array 数组
     * @param array $conditions 查询条件
     * @return array
     */
    public static function findIndexInArray(array $array,array $conditions)
    {
        // 使用array_filter根据多个条件过滤数组
        return array_filter($array, function ($item) use ($conditions) {
            foreach ($conditions as $key => $value) {
                if (!isset($item[$key])){
                    return false;
                }
                if ($item[$key] !== $value) {
                    return false;
                }
            }
            return true;
        });
    }

    /**
     * 根据条件查询数组的下标以及下标所在的数据
     * @param array $array 数组
     * @param array $conditions 查询条件
     * @return array
     */
    public static function findIndexDataInArray(array $array,array $conditions)
    {
        // 使用array_filter根据多个条件过滤数组
        return array_filter($array, function ($item) use ($conditions) {
            foreach ($conditions as $key => $value) {
                if (!isset($item[$key])){
                    return [];
                }
                if ($item[$key] !== $value) {
                    return [];
                }
            }
            return true;
        });
    }

    /**
     * 检查数组里面是否存在该字段且等于指定值，存在则true，不存在则false
     * @param array $array 数组
     * @param string $field 字段名称
     * @param $value
     * @return bool
     */
    public static function checkArrFilesIsExistEqualValue(array $array,string $field, string $value): bool
    {
        return (isset($array[$field]) && $array[$field] == $value);
    }

    /**
     * 接收create方法响应数据， 返回主键Id
     * @param null $response
     * @return string
     */
    public static function getCreateReturnId($response = null): string
    {
        $data = "";
        if (!$response){
            return $data;
        }
        if ($response && isset($response['httpCode']) &&
            $response['httpCode'] === 200 && isset($response['result']) && !empty($response['result'])){
            $data = $response['result'];
            if (is_array($data)){
                $data = $data['_id'];
            }
        }
        return $data;
    }


    /**
     * 接收新架构的响应数据， 返回结果
     * @param null $response
     * @return array
     */
    public static function getNewResultData($response = null)
    {
        $data = [];
        if (!$response){
            return $data;
        }
        if ($response && isset($response['httpCode']) &&
            $response['httpCode'] && isset($response['result']) && !empty($response['result']) && isset($response['result']['state']) && $response['result']['state']['code'] === 2000000){
            $data = $response['result']['data'];
        }
        return $data;
    }

    public static function jsonEncode($list,$option = JSON_UNESCAPED_UNICODE)
    {
        echo json_encode($list,$option);
    }

    /**
     * 去重数组对象重复数据
     * @param $array
     * @return array|mixed
     */
    public static function clearRepeatData($array){
        // 使用array_reduce和array_column去重
        $uniqueArray = array_reduce($array, function ($carry, $item) {
            // 创建一个用于比较的唯一键
            $key = md5(serialize($item));
            if (!isset($carry[$key])) {
                $carry[$key] = $item;
            }
            return $carry;
        }, []);
        // 将结果转换为数组
        $uniqueArray = array_values($uniqueArray);
        return $uniqueArray;
    }

    /**
     * 判断数组对象是否有重复，有重复就true，没有就false
     * @param $array
     * @return bool
     */
    public static function hasDuplicates($array) {
        $seen = [];
        foreach ($array as $item) {
            // 将对象转换为字符串，作为唯一标识
            $serializedItem = serialize($item);
            if (isset($seen[$serializedItem])) {
                // 如果已存在，则返回true
                return true;
            }
            // 将当前元素的序列化版本作为键存储，值为任意值
            $seen[$serializedItem] = true;
        }
        // 如果没有发现重复，则返回false
        return false;
    }

    /**
     * (记住！是数组对象，不是纯数组)从原数组对象 提取部分字段 拆解组合出 新的数组对象 并返回
     * @param $originArray
     * @param $fieldsToExtract
     * @return array|array[]
     */
    public static function arrayExtractSomeFilesCombineNewArray($originArray, $fieldsToExtract)
    {
        // 只获取这几个字段
        if (empty($fieldsToExtract)){
            return [];
        }
        // 使用 array_map 来创建一个新的数组对象，只包含特定的字段
        return array_map(function ($item) use ($fieldsToExtract) {
            return array_intersect_key($item, array_flip($fieldsToExtract));
        }, $originArray);
    }

    public static function buildGenerateUuidLike() {
        return bin2hex(random_bytes(16));
    }


    /**
     * 解释变量的标准程序
     * @author hello
     * @createdOn 2010-04-19
     *
     * 如果输入 php test.php -a v1 v2 -b v3 -t vt
     * 可以通过公共的函数，解释为:
     *
     * $params = array(
     * 		"a" => array("v1", "v2"),
     * 		"b" => "v3",
     * 		"t" => "vt",
     * 	);
     *
     * 注意，如果输入 php test.php -a v2 -b v3 -t vt
     * 则会变成:
     * $params = array(
     * 		"a" =>"v1",
     * 		"b" => "v3",
     * 		"t" => "vt",
     * 	);
     *
     * 正确的应该是 "a"=>array("v1"),
     * 不过由于无法预知　a　对应的是一个还是多个，所以，只能如此设定。
     * 如果需要，请使用者自行转换一下。
     *
     * e.g.
     * if (array_key_exists("a", $params) && !is_array($params["a"])){
     * 		$value = $params["a"];
     * 		$params["a"] = array($value);
     * }
     *
     * 如果确实需要（比如需要转换的比较多），那么可以带一个参数 keyNamesWhichIsArrayValue
     * e.g. :
     * $re = explainArgv($argv,	array(
     * 								"keyNamesWhichIsArrayValue" => array("a")
     * 							)
     * 					);
     *
     * $options:
     * 		keyNamesWhichIsArrayValue : 要求数值必须是array的key列表
     *
     * @param array $argv
     * @param array $options
     * @return array
     */
    public static function explainArgv($argv, $options = array()){
        $re = array();
        $count = count($argv);
        if ($count <= 1) return $re; // no params($argv[0]　is the execute filename, ignore it.)

        // explain argv
        $index = -1;
        $keyName = "";
        foreach($argv as $value){
            $index ++;
            if ($index == 0) continue; // $argv[0]　is the execute filename, ignore it.

            $keyFlag = substr($value, 0, 1);
            if ($keyFlag == "-"){
                $keyName = substr($value, 1);
                $re[$keyName] = "";
                continue;
            }
            if ($keyName == "") continue;

            if ($re[$keyName] == ""){ 			// not set
                $re[$keyName] = $value;
            }else if (is_array($re[$keyName])){	// already is array
                array_push($re[$keyName], $value);
            }else{
                $arr = array($re[$keyName]);	// single value to array value
                array_push($arr, $value);
                $re[$keyName] = $arr;
            }
        }

        // repair data
        if (array_key_exists("keyNamesWhichIsArrayValue", $options)){
            foreach ($options["keyNamesWhichIsArrayValue"] as $keyName){
                if (array_key_exists($keyName, $re) && !is_array($re[$keyName])){
                    $value = $re[$keyName];
                    $re[$keyName] = array($value);
                }
            }
        }

        return $re;
    }

    /**
     * node后端请求的分页接口里面，有一个特殊的返回格式，就是doc,返回第一个数据
     * @param null $response
     * @return array
     */
    public static function getPageDocListInFirstDataV1($response = null)
    {
        $data = [];
        if (!$response){
            return $data;
        }
        if ($response && isset($response['httpCode']) &&
            $response['httpCode'] === 200 && isset($response['result']) && !empty($response['result']) && isset($response['result']['data'])
            && isset($response['result']['data']['docs']) && count($response['result']['data']['docs']) > 0){
            $data = $response['result']['data']['docs'][0];
        }
        return $data;
    }

}


