<?php

class Solution
{
    /***
     * eg1: s = "abcabcbb"
     * print: 3
     *
     * eg2: s = "bbbb"
     * print: 1
     *
     *
     * eg3: s = "pwwkew"
     * print: 3
     *
     * eg4: s = " "
     * print: 0
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

}

var_export((new Solution())->lengthOfLongestSubstring("abcabcbb"));
//var_export((new Solution())->lengthOfLongestSubstring("bbbb"));
//var_export((new Solution())->lengthOfLongestSubstring("pwwkew"));
//var_export((new Solution())->lengthOfLongestSubstring(" "));
//var_export((new Solution())->lengthOfLongestSubstring("au"));