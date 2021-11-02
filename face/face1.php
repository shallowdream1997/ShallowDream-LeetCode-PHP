<?php

$arr1 = [2,3,7,5,1];
$arr2 = [3,8,1,6,7];
$arr3 = [3,5,7,2,1];

//eg: [3,7]
//$intersect_array = array_intersect($arr1,$arr2,$arr3);
//print_r($intersect_array);
//
//function array_instrect($arr1,$arr2,$arr3)
//{
//    foreach ($arr1 as $value) {
//        if (in_array($value, $arr2) && in_array($value, $arr3)) {
//            $temp[] = $value;
//        }
//    }
//    return $temp;
//}
//var_dump(array_instrect($arr1,$arr2,$arr3));

function trues()
{
    return (1 !== '1');
}
var_dump(trues());