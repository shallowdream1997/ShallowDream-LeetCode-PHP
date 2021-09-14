<?php

/**
 * 堆排序
 * Class HeapSort
 */
class HeapSort
{
    function buildMaxHeap(&$arr)
    {
        global $len;
        for ($i = floor($len / 2); $i >= 0; $i--) {
            $this->heapify($arr, $i);
        }
    }

    function heapify(&$arr, $i)
    {
        global $len;
        $left = 2 * $i + 1;
        $right = 2 * $i + 2;
        $largest = $i;

        if ($left < $len && $arr[$left] > $arr[$largest]) {
            $largest = $left;
        }

        if ($right < $len && $arr[$right] > $arr[$largest]) {
            $largest = $right;
        }

        if ($largest != $i) {
            $this->swap($arr, $i, $largest);
            $this->heapify($arr, $largest);
        }
    }

    function swap(&$arr, $i, $j)
    {
        $temp = $arr[$i];
        $arr[$i] = $arr[$j];
        $arr[$j] = $temp;
    }

    function heap_sort($arr)
    {
        global $len;
        $len = count($arr);
        $this->buildMaxHeap($arr);
        for ($i = count($arr) - 1; $i > 0; $i--) {
            $this->swap($arr, 0, $i);
            $len--;
            $this->heapify($arr, 0);
        }
        return $arr;
    }
}

var_dump((new HeapSort())->heap_sort([5, 2, 6, 8, 3, 1, 6, 8, 4, 54, 78, 123, 564, 44]));
var_dump((new HeapSort())->heap_sort([1, 40, 9, 4, 6, 213, 43, 88346, 852, 31, 8456, 3237, 88346, 92, 35, 2342, 537]));