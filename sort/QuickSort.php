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
        //选择一个基数
        $main = $arr[0];
        //统计长度，要和基数做比较
        $len = count($arr);
        $left = $right = [];
        //对除了基数之外的数做比较
        for ($i = 1; $i < $len; $i++) {
            if ($arr[$i] > $main) {
                //如果大于基数，置右边，做新分区
                $right[] = $arr[$i];
            } else {
                //如果小于基数，置左边，做新分区
                $left[] = $arr[$i];
            }
        }
        //对左边新区按quicksort一样递归排序,直到排序结束,左边的数一定比基数小，且有顺序
        $left = $this->quick($left);
        //基数放左边的最后下标，表示中间数
        $left[] = $main;
        //对右边新区按quicksort递归排序，结束后右边的数一定比基数大，且有顺序
        $right = $this->quick($right);

        return array_merge($left, $right);
    }
}

var_dump((new QuickSort())->quick([5, 2, 6, 8, 3, 1, 6, 8, 4, 54, 78, 123, 564, 44]));
var_dump((new QuickSort())->quick([1, 40, 9, 4, 6, 213, 43, 88346, 852, 31, 8456, 3237, 88346, 92, 35, 2342, 537]));