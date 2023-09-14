<?php
class LastWordLength{
    function lengthOfLastWord($s) {
        $length = strlen($s);
        if ($length < 1 || $length > 10000){
            return 0;
        }
        $size = 0;
        $s = str_split($s);
        for ($i = ($length-1);$i > 0;$i--){
            var_dump($s[$i]);
            $isEmpty = empty($s[$i]);
            if (!$isEmpty){
                $size++;
            }

            if ($size > 0 && $isEmpty){
                return $size;
            }
        }
        echo $size;
    }
}

$p = new LastWordLength();
echo $p->lengthOfLastWord("Hello world");