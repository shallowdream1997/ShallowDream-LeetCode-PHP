<?php

/**
 * 快速排序法
 * 1.将无序数组取第一个数为基数，通过基数对后面的数进行比较
 * 2.如果比较的数字大于基数，站在右边，构成一个新的数组，作为偏向于大的分区
 * 3.如果比较的数字小于技术，站在左边，构成一个新的数组，作为偏向于小的分区
 * 4.再次递归，对新的数组，即分区做快排，方法上述一样
 * 5.直到最终每个分区都是只有一个数字，返回因为这时候第一个数基数已经没有可以比较的数了
 * Class QuickSort
 */
class QuickSort
{
    function quick($arr)
    {
        if (count($arr) <= 1) {
            return $arr;
        }

        $middle = $arr[0];
        $leftArray = array();
        $rightArray = array();

        for ($i = 1; $i < count($arr); $i++) {
            if ($arr[$i] > $middle) {
                $rightArray[] = $arr[$i];
            } else {
                $leftArray[] = $arr[$i];
            }
        }
        $leftArray = $this->quick($leftArray);
        $leftArray[] = $middle;

        $rightArray = $this->quick($rightArray);
        return array_merge($leftArray, $rightArray);
    }
}

var_dump((new QuickSort())->quick([5, 2, 6, 8, 3, 1, 6, 8, 4, 54, 78, 123, 564, 44]));
var_dump((new QuickSort())->quick([1, 40, 9, 4, 6, 213, 43, 88346, 852, 31, 8456, 3237, 88346, 92, 35, 2342, 537]));