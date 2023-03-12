<?php

class maxBackText
{
    public function maxBackText($text){
        $length = strlen($text);
        if ($length > 1000 || $length < 1){
            return '';
        }
        $string = '';
        for ($i=0;$i<$length;$i++){
            $s1 = $s2 = "";
            $right = $left = $i;
            while($left >= 0 && $right < $length && $text[$left] == $text[$right]){
                $left--;
                $right++;
            }
            $s1 = substr($text,$left+1,$right-$left-1);

            $right = $i+1;
            $left = $i;
            while($left >= 0 && $right < $length && $text[$left] == $text[$right]){
                $left--;
                $right++;
            }
            $s2 = substr($text,$left+1,$right-$left-1);
            $string = strlen($string) > strlen($s1) ? $string : $s1;
            $string = strlen($string) > strlen($s2) ? $string : $s2;
        }
        return $string;
    }
}

$p = new maxBackText();
var_dump($p->maxBackText("babad"));