<?php


class Solution
{
    /**
     * @param Integer[] $nums1
     * @param Integer[] $nums2
     * @return Float
     */
    function findMedianSortedArrays($nums1, $nums2) {
        $length1 = count($nums1); //最大长度
        $length2 = count($nums2); //最大长度

        $len = $length1 + $length2; //总长

        //找nums1数组中的最大值
        $rightNum = $nums1[$length1-1];
        //找nums2数组的最小值
        $leftNum = $nums2[0];


        var_dump($length1);
        var_dump($nums1[$length1-1]);
        var_dump($length2);
    }
}

(new Solution())->findMedianSortedArrays([1,3],[2]);