<?php

/**
 * 冒泡排序
 * 两两相邻，互相比较，谁大站右边，两边互换，直到最后
 * Class BubbleSort
 */
class BubbleSort
{
    function bubble($arr)
    {
        $len = count($arr);
        for ($i = 0; $i < $len; $i++) {
            for ($j = 0; $j < $len - 1 - $i; $j++) {
                if ($arr[$j] > $arr[$j + 1]) {
                    $temp = $arr[$j];
                    $arr[$j] = $arr[$j + 1];
                    $arr[$j + 1] = $temp;
                }
            }
        }
        return $arr;
    }
}

var_dump((new BubbleSort())->bubble([5, 2, 6, 8, 3, 1, 6, 8, 4, 54, 78, 123, 564, 44]));
var_dump((new BubbleSort())->bubble([1, 40, 9, 4, 6, 213, 43, 88346, 852, 31, 8456, 3237, 88346, 92, 35, 2342, 537]));