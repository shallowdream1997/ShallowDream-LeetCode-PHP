<?php

/**
 * Class Solution
 *
 * 示例 1：
 * 输入：x = 121
 * 输出：true
 *
 * 示例2：
 * 输入：x = -121
 * 输出：false
 * 解释：从左向右读, 为 -121 。 从右向左读, 为 121- 。因此它不是一个回文数。

 * 示例 3：
 * 输入：x = 10
 * 输出：false
 * 解释：从右向左读, 为 01 。因此它不是一个回文数。

 * 示例 4：
 * 输入：x = -101
 * 输出：false
 */
class Solution
{
    /**
     * @param Integer $x
     * @return Boolean
     */
    function isPalindrome($x) {
        if ($x < 0){
            return false;
        }
        $revString = strrev($x);
        if ($revString == $x){
            return true;
        }else{
            return false;
        }
    }

    function isPalindrome2($x) {
        $x = (string)$x;
        $len = strlen($x);
        $end = $len - 1;
        for ($i=0;$i<$len;$i++){
            if ($x[$i] != $x[$end]){
                return false;
            }
            if (($len%2==1 && $end == $i) || ($len%2==0 && ($len/2)-1 == $i)){
                // 奇数，到中间就不用读了
                return true;
            }else{
                $end--;
            }
        }
        return true;
    }

    /**
     * @param $x
     * @return bool
     * 双指针
     */
    function isPalindrome3($x)
    {
        if ($x < 0){
            return false;
        }
        $x = (string)$x;
        $len = strlen($x);
        for ($i=0,$j= $len - 1;$i <= $j;){
            if ($x[$i] == $x[$j]){
                ++$i;
                --$j;
            }else{
                return false;
            }
        }
        return true;
    }

}

var_export((new Solution())->isPalindrome3(121));
