<?php

/**
 * 插入算法
 * Class InsertionSort
 */
class InsertionSort
{
    function insertion($arr)
    {
        $len = count($arr);
        for ($i = 1; $i < $len; $i++) {
            $preIndex = $i - 1;
            $current = $arr[$i];
            while ($preIndex >= 0 && $arr[$preIndex] > $current) {
                $arr[$preIndex + 1] = $arr[$preIndex];
                $preIndex--;
            }
            $arr[$preIndex + 1] = $current;
        }
        return $arr;
    }
}

var_dump((new InsertionSort())->insertion([5, 2, 6, 8, 3, 1, 6, 8, 4, 54, 78, 123, 564, 44]));
var_dump((new InsertionSort())->insertion([1, 40, 9, 4, 6, 213, 43, 88346, 852, 31, 8456, 3237, 88346, 92, 35, 2342, 537]));