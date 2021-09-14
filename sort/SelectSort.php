<?php

/**
 * 选择排序
 * 1.外层循环，选第一个数作为最小下标，对内循环除第一个数开始的比较，最小下标的数大于这个数时，更新最小下标
 *
 * Class SelectSort
 */
class SelectSort
{
    function select($arr)
    {
        $len = count($arr);
        for ($i = 0; $i < $len - 1; $i++) {
            $minIndex = $i;
            for ($j = $i + 1; $j < $len; $j++) {
                if ($arr[$j] < $arr[$minIndex]) {
                    $minIndex = $j;
                }
            }
            $temp = $arr[$i];
            $arr[$i] = $arr[$minIndex];
            $arr[$minIndex] = $temp;
        }
        return $arr;
    }
}

//取0为最小数下标,a[0]=5
//a[0] 和 a[1]~a[len]右边的数逐个对比
//a[0] > a[1] 对换下标,1为最小下标，a[1] < a[2] 不对换,1还是最小下标，逐个对比....a[1] > a[5]，5成立新的最小下标,a[5]成i=0的循环里最小数，将a[5]对换到a[0]
var_dump((new SelectSort())->select([5, 2, 6, 8, 3, 1, 6, 8, 4, 54, 78, 123, 564, 44]));
var_dump((new SelectSort())->select([1, 40, 9, 4, 6, 213, 43, 88346, 852, 31, 8456, 3237, 88346, 92, 35, 2342, 537]));