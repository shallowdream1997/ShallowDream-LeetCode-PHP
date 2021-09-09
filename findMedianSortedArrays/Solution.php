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
            return ($left + $right) / 2.0;
        } else {
            for ($i = 0; $i < floor($len / 2); $i++) {
                $heap->extract();
            }
            return 1.0 * $heap->extract();
        }
    }

    /**
     * 划分数组法
     * 时间复杂度 O(log min(m,n)))
     * 空间复杂度 O(1)
     * @param $nums1
     * @param $nums2
     * @return float|int|mixed
     */
    function optFindMedianSortedArrays($nums1, $nums2)
    {
        if (count($nums1) > count($nums2)) {
            return $this->findMedianSortedArrays($nums2, $nums1);
        }

        $m = count($nums1);
        $n = count($nums2);
        $left = 0;
        $right = $m;

        // median1：前一部分的最大值
        // median2：后一部分的最小值
        $median1 = $median2 = 0;

        while ($left <= $right) {
            // 前一部分包含 nums1[0 .. i-1] 和 nums2[0 .. j-1]
            // 后一部分包含 nums1[i .. m-1] 和 nums2[j .. n-1]
            $i = ($left + $right) / 2;
            $j = ($m + $n + 1) / 2 - $i;

            // nums_im1, nums_i, nums_jm1, nums_j 分别表示 nums1[i-1], nums1[i], nums2[j-1], nums2[j]
            $nums_im1 = ($i == 0 ? PHP_INT_MAX : $nums1[$i - 1]);
            $nums_i = ($i == $m ? PHP_INT_MAX : $nums1[$i]);
            $nums_jm1 = ($j == 0 ? PHP_INT_MAX : $nums2[$j - 1]);
            $nums_j = ($j == $n ? PHP_INT_MAX : $nums2[$j]);

            if ($nums_im1 <= $nums_j) {
                $median1 = max($nums_im1, $nums_jm1);
                $median2 = min($nums_i, $nums_j);
                $left = $i + 1;
            } else {
                $right = $i - 1;
            }
        }

        return ($m + $n) % 2 == 0 ? ($median1 + $median2) / 2.0 : $median1;
    }

}

echo (new Solution())->optFindMedianSortedArrays([1, 3], [2]);
//echo (new Solution())->findMedianSortedArrays([1, 3, 4, 9], [1, 2, 3, 4, 5, 6, 7, 8, 9]);