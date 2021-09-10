<?php

/**
 * 最长回文子串
 * Class Solution
 *
 * 输入：s = "babad"
 * 输出："bab"
 * 解释："aba" 同样是符合题意的答案。
 *
 *
 * 输入：s = "cbbd"
 * 输出："bb"
 *
 *
 * 输入：s = "a"
 * 输出："a"
 *
 *
 * 输入：s = "ac"
 * 输出："a"
 */
class Solution
{
    function longestPalindrome($s)
    {
        $len = strlen($s);
        if ($len == 1) return $s;

        return 1;
    }
}

echo (new Solution())->longestPalindrome("babbd");