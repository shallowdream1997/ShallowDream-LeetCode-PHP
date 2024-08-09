<?php

/**
 * 响应数据处理工具类 - 针对那些接口返回的数据格式做处理，封装好后直接用
 * Class DataUtils
 */
class DataUtils
{
    public function __construct(){

    }

    /**
     * 接收响应数据， 返回结果
     * @param null $response
     * @return array
     */
    public static function getResultData($response = null): array
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
    public static function getPageList($response = null): array
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
    public static function getQueryList($response = null): array
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
    public static function getPageDocList($response = null): array
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
    public static function getPageListInFirstData($response = null): array
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
     * 取值
     * @param $data
     * @return array|mixed
     */
    public static function getOptionVal($data): array
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
    public static function getArrHeadData(array $list): array
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
    public static function findIndexInArray(array $array,array $conditions): array
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
    public static function getNewResultData($response = null): array
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
}


