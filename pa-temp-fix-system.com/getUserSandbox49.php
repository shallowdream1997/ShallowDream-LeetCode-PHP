<?php
header("Access-Control-Allow-Origin: *");
if (isset($_REQUEST['user']) && ! empty($_REQUEST['user']) || true) {
    $manualUserList = array(
        "172.16.29.23"=>"liyongshan2",//zhou
        "172.16.24.64"=>"linqinxiang",//祥
        "172.16.24.44"=>"xiaoan",//老肖
    );
    if (isset($manualUserList[get_client_ip()])) {
        echo $manualUserList[get_client_ip()];
    } else {
        echo "lixuehui";
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
