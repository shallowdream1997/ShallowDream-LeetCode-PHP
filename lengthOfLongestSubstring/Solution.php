<?php

/**
 * 无重复字符的最长子串
 * Class Solution
 */
class Solution
{
    /***
     * eg1: s = "abcabcbb"
     * print: 3
     *
     * eg2: s = "bbbb"
     * print: 1
     *
     * eg3: s = "pwwkew"
     * print: 3
     *
     * eg4: s = " "
     * print: 0
     *
     * eg5: s = "iqbtbscgdztpgfp"
     * print: 8
     *
     * eg6: s = "wobgrovw"
     * print: 6
     *
     * eg7: s = "tmmzuxt"
     * print: 5
     *
     * eg8: s = "dvdf"
     * print: 3
     */
    function lengthOfLongestSubstring($s)
    {
        $len = strlen($s);
        if ($len == 1 || $len == 0) return $len;
        $arr = str_split($s,1); //拆分字符串转成数组
        $max = [];//保存可能的最大值数组
        while (!empty($arr)){
            $temp = [];
            foreach ($arr as $key => $item){
                //如果temp不存在字母，入栈
                if (!isset($temp[$item])){
                    $temp[$item] = 1;
                }else{
                    //当有字母存在temp中，已有重复，统计当前temp的大小即为字符串的长度
                    break;
                }
            }
            $max[] = count($temp);
            array_shift($arr);//删除数组的开头，并偏移量向右
        }
        return max($max);
    }

    function opLengthOfLongestSubstring($s)
    {
        $len = strlen($s);
        if ($len == 1 || $len == 0) return $len;
        $temp = [];
        $left = $max = 0;
        for ($i = 0;$i < $len;$i++){
            $char = $s[$i];
            if (isset($temp[$char])) {
                $left = max($left,$temp[$char] + 1);
            }

            if ($left + $max >= $len){
                break;
            }

            $temp[$char] = $i;
            $max = max($max,$i - $left + 1);
        }
        return $max;
    }

    function opLengthOfLongestSubstring1($s)
    {
        $len = strlen($s);
        if ($len == 1 || $len == 0) return $len;
        $max = []; //
        $newS = '';
        for ($i = 0; $i < $len; $i++) {
            $char = $s[$i];
            $oldLeft = strpos($newS, $char);
            if ($oldLeft !== false) {
                //如果存在字符
                $max[] = strlen($newS);
                $newS = substr($newS, $oldLeft + 1);
            }
            $newS .= $char;
        }
        $max[] = strlen($newS); //循环结束后，最终的字串
        return max($max);
    }

}

//var_export((new Solution())->opLengthOfLongestSubstring1("pwwkew"));
//var_export((new Solution())->opLengthOfLongestSubstring1("abcabcbb"));
//var_export((new Solution())->opLengthOfLongestSubstring1("bbbb"));
//var_export((new Solution())->opLengthOfLongestSubstring1(" "));
//var_export((new Solution())->opLengthOfLongestSubstring1("au"));
var_export((new Solution())->opLengthOfLongestSubstring1("dvdf"));
//var_export((new Solution())->opLengthOfLongestSubstring1("tmmzuxt"));
//var_export((new Solution())->opLengthOfLongestSubstring1("wobgrovw"));
//var_export((new Solution())->opLengthOfLongestSubstring1("iqbtbscgdztpgfp"));