<?php


//$heap = new SplMinHeap();
//$heap->insert(38);
//$heap->insert(1);
//$heap->insert(5);
//$heap->insert(3);
//$heap->insert(7);
//$heap->insert(14);
//$heap->insert(36);
//
//echo $heap->extract();
//echo $heap->extract();
//foreach ($heap as $value)
//{
//    var_dump($value);
//}
echo "<pre>";
//var_dump($_SERVER);

//var_dump(function_exists('print'));

//$a = 12;
//$b = 012;
//$c = 0x12;
//0 1 2
//0 001 010
//001010
//1*2^1 + 1*2^3 = 10
//echo $a."\n".$b."\n".$c."\n";
//
//$a = array(1=>5,5=>8,22,2=>'8',81);
//var_dump($a);


$nums = [1,40,9,4,6,213,43,88346,852,31,8456,3237,88346,92,35,2342,537];
//$heap = new SplMinHeap();
//foreach ($nums as $item){
//    $heap->insert($item);
//}
//$temp = [];
//foreach ($heap as $value){
//    $temp[] = $value;
//}
$len = count($nums);
for ($i=0;$i<$len;$i++){
    for ($j=0;$j<$len-1-$i;$j++){
        if ($nums[$j] > $nums[$j+1]){
            $temp = $nums[$j];
            $nums[$j]=$nums[$j+1];
            $nums[$j+1]=$temp;
        }
    }
}
var_export($nums);
//echo "ALTER TABLE `thj_order_record`
//ADD COLUMN `is_delete` tinyint(2) NOT NULL DEFAULT 0 COMMENT '0-未删除 10-已删除' AFTER `update_time`;";