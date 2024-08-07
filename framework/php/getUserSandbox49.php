<?php
header("Access-Control-Allow-Origin: *");
if (isset($_REQUEST['user']) && ! empty($_REQUEST['user']) || true) {
    $manualUserList = array(
        "172.16.5.217" => "huanglibiao",
        "192.168.252.113" => "huanglibiao",
        "172.16.3.72"=> "zhengyusheng",
        "172.16.3.75" => "zhengyusheng",
        "172.16.28.43" => "zhengyusheng",
        "172.16.253.16"  => "zhengyusheng",
        "172.16.5.39" => "zhengyusheng",
        "172.16.5.237" => "zhengyusheng",
        "172.16.5.31" => "zhengyusheng",
        "172.16.5.44" => "zhengyusheng",
        "172.16.7.161" => "zhengyusheng",
        "172.16.2.213" => "zhengyusheng",
        "172.16.7.56" => "zhengyusheng",
        "172.16.5.160" => "wenjingrong",
        "172.16.7.69" => "zhengyusheng",  //wxy
        "172.16.24.39" => "zengyulin",  // zyl
        "192.168.252.194" => "zhengyusheng",
        "172.16.5.65" => "zhengyusheng",
        "172.16.2.26" => "zhengyusheng",
        "172.16.29.74" =>"duqiliang",//c
        "172.16.20.233" => "zhengyuseng",
        "172.16.25.79" => "zhengyusheng",
        //	"172.16.2.35" =>"luoxiuxia",
        "172.16.2.12" => "wenjingrong",
        "172.16.2.90" => "zhengyusheng",
        "172.16.24.108" => "wenjingrong",
        "172.16.25.137"=>"linweihong",
        "172.16.25.111" => "linweihong",
        "172.16.24.234" => "wenjingrong",
        "192.168.253.170" => "liangpei",
        "192.168.252.218" => "zhengyusheng",
        "192.168.253.10"  => "zhengyusheng",
        "172.16.5.57" => "zhengyusheng",
        "172.16.2.43" => "meiying",
        "172.16.2.68" => "zhengyusheng",
        "172.16.5.65" => "linweihong",
        "172.16.5.117" => "zhengyusheng",
        "192.168.22.78" => "zhengyusheng",
        "192.168.252.187"=> "zhengyusheng",
        "192.168.245.1"=> "wangyunyun",
        "192.168.1.5"=> "zhengyusheng",
        "10.7.6.130"=> "zhengyusheng",
        "172.16.11.186"=> "zhengyusheng",
        // "172.16.5.77"=> "zhuojia",
        "192.168.2.104"=> "zhengyusheng",
        "172.16.2.42"=>"zhengyusheng",
        "172.16.24.74"=>"wenjingrong",
        "172.16.2.52"=>"zhengyusheng",
        "172.16.5.44"=>"zhengyusheng",
        "172.16.5.42"=>"zhengyusheng",
        "172.16.29.71"=>"zhengyusheng",//yuan
        "172.16.27.48"=>"duqiliang",//xiao
        "172.16.29.103"=>"chenqilian",//he
        "172.16.29.2"=>"lixiaomin",//zhou
        "172.16.29.56"=>"zhengyusheng",//liu
        "192.168.253.169"=>"zhengyusheng",
        "172.16.2.162"=>"zhengyusheng",
        "172.16.5.39"=> "wumingxi",
        "172.16.5.207"=> "leipeng",
        "172.16.20.231" => "wenjingrong",
        "172.16.2.42"=>"luoxiuxia",
        "172.16.5.207"=> "zhengyusheng",
        '172.16.5.204'=>'zhengyusheng',//cxx

    );
    if (isset($manualUserList[get_client_ip()])) {
        echo $manualUserList[get_client_ip()];
    } else {
        echo "zhouangang";
    }
} else {
    define("COOKIE_AUTH", "product_operation_auth");
    check_cookie();
}
function check_cookie() {
    /**
     * 获取当前用户的 UID 和 用户名
     * Cookie 解密直接用 uc_authcode 函数，用户使用自己的函数
     */
    $uid = $userName = $baseurl = '';
    $userName = 'no user found.';
    if(!empty($_COOKIE[COOKIE_AUTH])) {
        list($uid, $userName, $baseurl) = explode("\t", uc_authcode($_COOKIE[COOKIE_AUTH], 'DECODE', '123456'));
    }
    echo $userName;
}

function uc_authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {
    $ckey_length = 4;
    $key = md5($key ? $key : UC_KEY);
    $keya = md5(substr($key, 0, 16));
    $keyb = md5(substr($key, 16, 16));
    $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';

    $cryptkey = $keya.md5($keya.$keyc);
    $key_length = strlen($cryptkey);

    $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
    $string_length = strlen($string);

    $result = '';
    $box = range(0, 255);

    $rndkey = array();
    for($i = 0; $i <= 255; $i++) {
        $rndkey[$i] = ord($cryptkey[$i % $key_length]);
    }

    for($j = $i = 0; $i < 256; $i++) {
        $j = ($j + $box[$i] + $rndkey[$i]) % 256;
        $tmp = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }

    for($a = $j = $i = 0; $i < $string_length; $i++) {
        $a = ($a + 1) % 256;
        $j = ($j + $box[$a]) % 256;
        $tmp = $box[$a];
        $box[$a] = $box[$j];
        $box[$j] = $tmp;
        $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }

    if($operation == 'DECODE') {
        if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
            return substr($result, 26);
        } else {
            return '';
        }
    } else {
        return $keyc.str_replace('=', '', base64_encode($result));
    }
}

function get_client_ip()
{
    if (getenv('HTTP_CLIENT_IP')) {
        $client_ip = getenv('HTTP_CLIENT_IP');
    } elseif (getenv('HTTP_X_FORWARDED_FOR')) {
        $client_ip = getenv('HTTP_X_FORWARDED_FOR');
    } elseif (getenv('REMOTE_ADDR')) {
        $client_ip = getenv('REMOTE_ADDR');
    } else {
        $client_ip = $_SERVER['REMOTE_ADDR'];
    }
    return $client_ip;
}
?>