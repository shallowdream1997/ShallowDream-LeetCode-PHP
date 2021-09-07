<?php

/**
 * 两数之和
 * Class Solution
 */
class Solution
{
    /**
     * 两数之和解法
     * @param $nums
     * @param $target
     * @return array
     */
    function twoSum($nums, $target)
    {
        $map = [];
        foreach ($nums as $key => $item) {
            $sub = $target - $item;
            if (isset($map[$sub])) {
                return [$map[$sub], $key];
            } else {
                $map[$item] = $key;
            }
        }
    }

    /***
     * 两数之和解法一
     * @param $nums
     * @param $target
     * @return array
     */
    function twoSum1($nums, $target)
    {
        $temp = [];
        foreach ($nums as $key => $item) {
            //获取差值
            $sub = $target - $item;
            //如果差值存在差值数组中，且对应的数字合当前数字 之和等于目标值,是相同数字，直接返回
            if (isset($temp[$sub]) && ($nums[$temp[$sub]] + $item) == $target) {
                return [$temp[$sub], $key];
            }
            //不存在差值，记录：键值为差值，键名为nums的键值
            $temp[$sub] = $key;
            //存在差值在差值数组中，且键值不为当前nums键值，即不是同一个数组，一定是和等于目标值的数字，返回
            if (isset($temp[$item]) && $temp[$item] != $key) {
                return [$temp[$item], $key];
            }
        }
    }

    /**
     * 两数之和解法二
     * @param $nums
     * @param $target
     * @return array
     */
    function twoSum2($nums, $target)
    {
        $temp = [];
        foreach ($nums as $key => $item) {
            //获取差值
            $sub = $target - $item;
            //如果当前值存在差值数组中，且对应的下标不属于当前下标，直接返回
            if (isset($temp[$item]) && $temp[$item] != $key) {
                return [$temp[$item], $key];
            } else {
                $temp[$sub] = $key;
            }
        }
    }

    /**
     * Definition for a singly-linked list.
     * class ListNode {
     *     public $val = 0;
     *     public $next = null;
     *     function __construct($val = 0, $next = null) {
     *         $this->val = $val;
     *         $this->next = $next;
     *     }
     * }
     */

    /**
     * 两数相加
     * @param $l1
     * @param $l2
     */
    function addTwoNumbers($l1, $l2)
    {
        var_dump($l1->next->val);
        var_dump($l2);
        while(isset($l1->val) && isset($l2->val)){
            $next = $l1->next;
            $l1 = $next;
            $next2 = $l2->next;
            $l2 = $next2;
        }
    }
}


class ListNode
{
    public $val = 0;
    public $next = null;

    function __construct($val = 0, $next = null)
    {
        $this->val = $val;
        $this->next = $next;
    }
}

$nums1 = [2, 15, 11, 7];
$target1 = 9;

$nums2 = [3, 3];
$target2 = 6;

$nums3 = [3, 2, 4];
$target3 = 6;

$nums4 = [1, 1, 1, 1, 1, 4, 1, 1, 1, 1, 1, 7, 1, 1, 1, 1, 1];
$target4 = 11;
//var_dump((new Solution())->twoSum($nums1, $target1));
//var_dump((new Solution())->twoSum($nums2, $target2));
//var_dump((new Solution())->twoSum($nums3, $target3));
var_dump((new Solution())->twoSum2($nums1, $target1));
var_dump((new Solution())->twoSum2($nums2, $target2));
var_dump((new Solution())->twoSum2($nums3, $target3));
var_dump((new Solution())->twoSum2($nums4, $target4));

$l1 = [2, 4, 3];
$l2 = [5, 6, 4];
//[7,0,8]
$l11 = [9, 9, 9, 9, 9, 9, 9];
$l21 = [9, 9, 9, 9];
//[8,9,9,9,0,0,0,1];

var_dump((new Solution())->addTwoNumbers($l1, $l2));