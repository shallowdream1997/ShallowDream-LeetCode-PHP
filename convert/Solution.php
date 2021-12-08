<?php

/**
 * 将一个给定字符串 s 根据给定的行数 numRows ，以从上往下、从左到右进行Z 字形排列。
 * 比如输入字符串为 "PAYPALISHIRING"行数为 3 时，排列如下：
 *
 * P   A   H   N
 * A P L S I I G
 * Y   I   R
 * 之后，你的输出需要从左往右逐行读取，产生出一个新的字符串，比如："PAHNAPLSIIGYIR"。
 *
 * 请你实现这个将字符串进行指定行数变换的函数：
 * string convert(string s, int numRows);
 *
 * 示例 1：
 * 输入：s = "PAYPALISHIRING", numRows = 3
 * 输出："PAHNAPLSIIGYIR"
 * PAHNAPLSIIGYIR
 * 示例 2：
 * 输入：s = "PAYPALISHIRING", numRows = 4
 * 输出："PINALSIGYAHRPI"
 * 解释：
 * P     I    N
 * A   L S  I G
 * Y A   H R
 * P     I
 * 示例 3：
 *
 * 输入：s = "A", numRows = 1
 * 输出："A"
 */
class Solution
{
    /**
     * @param String $s
     * @param Integer $numRows
     * @return String
     */
    function convert($s, $numRows)
    {
        $len = strlen($s);
        if ($len == 1) {
            return $s;
        }
        $map = [];
        $row = 0; // 行
        $col = 0; // 列
        $newCol = 0;
        for ($i = 0; $i < $len; $i++) {
            if ($row < $numRows) {
                // 在行<要求的行数内，将字符写入对应的行列数组里
                $map[$row][$col] = $s[$i];
                $row++;
                continue;
            }

            if ($row == $numRows) {
                //在行数等于目标数，也就是达到底层的时候，设置一个新的行，因为row需要保持原样保证不跳进上面的if
                $newCol++;
                $newRow = $row - $newCol - 1;
                $col++;
                if ($newRow == 0) {
                    $row = 1;
                    $newCol = 0;
                }
                $map[$newRow][$col] = $s[$i];
            }
        }

        $string = "";
        foreach ($map as $value) {
            foreach ($value as $item) {
                $string .= $item;
            }
        }
        return $string;
    }
}

echo "<pre>";
//00P 10A 20Y 30P 21A 12L 03I 13S 23H 33I 24R 15I 06

var_dump((new Solution())->convert("PAYPALISHIRING", 4));
var_dump((new Solution())->convert("PAYPALISHIRING", 3));
var_dump((new Solution())->convert("A", 1));
