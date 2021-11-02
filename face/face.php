<?php
//$str="<img src='1.jpg'/><img src='2.jpg'/>";
//$match="/<img.*?src=[\'\"](.*?)[\'\"].*?/";
//preg_match_all($match,$str,$arr);
//print_r($arr[1]);
//
//function clearTxt($string,$array){
//
//    echo str_replace($array, "", $string);
//
//}
//clearTxt("建国是来自东北铁岭的正宗东北汉子",array("东北"));
//
//$arr = parse_url('http://www.sina.com/abc/de/gf.php?id=2');
//$res = pathinfo($arr['path']);
//var_dump($res['extension']);

//$star = "*";
//$arr = [];
//for ($i=0;$i<5;$i++)
//{
//    array_unshift($arr,$star);
//    $star .= "*";
//}
//$arr = array_values($arr);
//foreach ($arr as $key => $i){
//    $black = str_repeat("&nbsp;",$key);
//    echo $black.$i."<br>";
//}


//$a = '/a/b/c/d/a.php';
//$b = '/a/b/12/34/c.php';
//$arr1 = pathinfo($a);
//$arr2 = pathinfo($b);
////var_dump($arr1['dirname']);
////var_dump($arr2['dirname']);
//$str1 = explode("/",$arr1['dirname']);
//$str2 = explode("/",$arr2['dirname']);
//$str = array_merge($str1,$str2);
//print_r($str);

//$a = 1;
//$b = 3;
//function sum(&$a,$b)
//{
//    ++$a;
//    $b++;
//    return $a+$b;
//
//}
//$c = sum($a,$b);
//var_dump($a);
//var_dump($b);
//var_dump($c);

//function test($array)
//{
//    $count = count($array);
//    $sum = [];
//    for ($i=0;$i<$count;$i++)
//    {
//        for ($j=$i+1;$j<$count;$j++){
//            for ($k=$j+1;$k<$count;$k++){
//                $sum = $array[$i] + $array[$j] + $array[$k];
//                if ($sum == 0){
//                    $arr[] = [$array[$i],$array[$j],$array[$k]];
//                }else{
//                    continue;
//                }
//            }
//        }
//    }
//    return $arr;
//}
//
//$array = [-1, 3, -2, 1, 2, -4, 5];
//echo "<pre>";
//print_r(test($array));

//class AllPlayer{
//    private $people;
//
//    public function __construct($people)
//    {
//        $this->people = $people;
//    }
//
//    public function get($name)
//    {
//        $count_array = array_count_values($this->people);
//        if (isset($count_array[$name])){
//            return $count_array[$name];
//        }else{
//            return 0;
//        }
//    }
//
//    public function add($people){
//        foreach ($people as $name){
//            array_unshift($this->people,$name);
//        }
//    }
//}
//
//echo "<pre>";
//$player = new AllPlayer(["李阳", "张天奕", "张爱伦", "李阳", "郭峰", "杨天宝", "郭峰", "李大钊"]);
//$player->add(["张三丰","杨天宝"]);
//
//var_dump($player->get("杨天宝"));


function test($array)
{
    //行长度
    $col = count($array[0]);
    $str = "";
    $bool = 0;
    while ($bool <= $col+1){
        //从0开始
        $j=0;
        for ($i=$bool;$i>=0;$i--){
            if (isset($array[$j][$i])){
                $str .= $array[$j][$i];
            }
            $j++;
        }
        $bool++;
    }
    return $str;
}

var_dump(test([
    ["你","喜","林"],
    ["欢","浩","不"],
    ["是","是","？"]
]));

var_dump(test([
    ["今","天","上","一","新","影"],
    ["晚","我","起","上","《","》"],
    ["们","去","映","流","好","听"],
    ["看","的","浪","不","小","非"],
    ["电","地","好","李","常","得"],
    ["球","？","说","值","看","！"]
]));