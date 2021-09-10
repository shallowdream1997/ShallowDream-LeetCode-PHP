<?php

/**
 * Class Solution
 * 整数反转
 *
 * 输入：x = 123
 * 输出：321
 *
 * 输入：x = -123
 * 输出：-321
 *
 * 输入：x = 120
 * 输出：21
 *
 * 输入：x = 0
 * 输出：0
 */
class Solution
{
    function reverse($x)
    {
        if ($x == 0) return $x;
        $rev = 0;
        while ($x != 0) {
            $digit = $x % 10;
            $x = intval($x/10);
            $rev = $rev * 10 + $digit;
        }
        $max = pow(2, 31);
        if (-$max > $rev || ($rev+1) > $max) {
            return 0;
        }
        return $rev;
    }

    /**
     * 函数都是c写的，执行效率会是100%，但是内存占用率会很高
     * @param $x
     * @return int|string
     */
    function opt_reverse($x)
    {
        $max = pow(2, 31);
        $s = intval(strrev(abs($x)));
        return $x >= 0 ? ($s + 1 > $max ? 0 : $s) : ($s > $max ? 0 : '-' . $s);
    }
}

//echo (new Solution())->opt_reverse(-123);
echo (new Solution())->reverse(120);