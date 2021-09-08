<?php


class Solution
{

    /**
     * $nums1 = [1,3,4,9]
     * $nums2 = [1,2,3,4,5,6,7,8,9]
     *
     * 4 + 9 = 13
     * 即中位数是 (13/2) = 6.5 约等于 7
     *
     * 比较两个有序数组的下标为 (k/2)-1的数，即$num1[intval(k/2)-1] $nums2[intval(k/2)-1]
     * if $num1[intval(k/2)-1] > $nums2[intval(k/2)-1]
     *
     * [[1,3],4,9]
     * [[1,2,3],4,5,6,7,8,9]
     *
     * @param $nums1
     * @param $nums2
     */
    function findMedianSortedArrays($nums1, $nums2)
    {
        $heap = new SplMinHeap();
        $length1 = count($nums1); //最大长度
        $length2 = count($nums2); //最大长度
        $len = $length1 + $length2; //总长

        foreach ($nums1 as $item) {
            $heap->insert($item);
        }
        foreach ($nums2 as $item) {
            $heap->insert($item);
        }
        if ($len % 2 == 0) {
            for ($i = 0; $i < ($len / 2) - 1; $i++) {
                $heap->extract();
            }
            $left = $heap->extract();
            $right = $heap->extract();
            return ($left*2)/2.0;
        } else {
            for ($i = 0; $i < floor($len / 2); $i++) {
                $heap->extract();
            }
            return 1.0 * $heap->extract();
        }
    }
}

echo (new Solution())->findMedianSortedArrays([1, 3], [2]);
//echo (new Solution())->findMedianSortedArrays([1, 3, 4, 9], [1, 2, 3, 4, 5, 6, 7, 8, 9]);