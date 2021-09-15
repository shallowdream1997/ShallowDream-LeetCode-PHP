<?php

/**
 * 笛卡尔积sku算法
 * Class Sku
 */
class Sku
{
    /**
     * 实现二维数组的笛卡尔积组合
     * @param array $arr 要进行笛卡尔积的二维数组
     * @param array $str 最终实现的笛卡尔积组合,可不写
     * @return array
     */
    function cartesian(array $arr, $str = array()): array
    {
        //去除第一个元素
        $first = array_shift($arr);
        //判断是否是第一次进行拼接
        if (count($str) > 1) {
            foreach ($str as $k => $val) {
                foreach ($first as $key => $value) {
                    //最终实现的格式 1,3,76
                    //可根据具体需求进行变更
                    $str2[] = $val . ',' . $value;
                }
            }
        } else {
            foreach ($first as $key => $value) {
                //最终实现的格式 1,3,76
                //可根据具体需求进行变更
                $str2[] = $value;
            }
        }

        //递归进行拼接
        if (count($arr) > 0) {
            $str2 = $this->cartesian($arr, $str2);
        }
        //返回最终笛卡尔积
        return $str2;
    }
}

$arr = array(
    array(1, 3, 4, 5),
    array(3, 5, 7, 9),
    array(76, 6, 1, 0)
);
var_dump((new Sku())->cartesian($arr));