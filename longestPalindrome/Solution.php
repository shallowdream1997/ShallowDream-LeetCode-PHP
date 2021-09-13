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
        $s_len = strlen($s);
        if ($s_len < 1) {
            return '';
        } elseif ($s_len <= 2) {
            return $s[0] == $s[1] ? $s : $s[0];
        }

        $symbol = '#';

        $s_with_symbol = "\$" . $symbol;
        for ($i = 0; $i < $s_len; $i++) {
            $sub_s = $s[$i] . $symbol;
            $s_with_symbol .= $sub_s;
        }

        $p = [];
        $mx = 0;
        $id = 0;
        $resLen = 0;
        $resCenter = 0;
        $s_with_symbol_len = strlen($s_with_symbol);

        for ($i = 1; $i < $s_with_symbol_len; $i++) {
            $p[$i] = $mx > $i ? min($p[2 * $id - $i], $mx - $i) : 1;

            while ($s_with_symbol[$i + $p[$i]] == $s_with_symbol[$i - $p[$i]]) {
                ++$p[$i];

                if ($mx < $p[$i] + $i) {
                    $mx = $p[$i] + $i;
                    $id = $i;
                }

                if ($resLen < $p[$i]) {
                    $resLen = $p[$i];
                    $resCenter = $i;
                }
            }
        }

        return substr($s, ($resCenter - $resLen) / 2, $resLen - 1);
    }
}

echo (new Solution())->longestPalindrome("babbd");