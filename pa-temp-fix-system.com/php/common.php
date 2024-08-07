<?php
//require_once '../vendor/autoload.php';
// 设置允许其他域名访问
header('Access-Control-Allow-Origin:*');
// 设置允许的响应类型
header('Access-Control-Allow-Methods:POST, GET');
// 设置允许的响应头
header('Access-Control-Allow-Headers:x-requested-with,content-type');

header("Content-type: text/html; charset=utf-8");
require_once("class/Logger.php");



function _getNodeJs($url, $method = "GET")
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
    $res = curl_exec($ch);
    curl_close($ch);
    $resDecode = json_decode($res, true);
    return $resDecode;
}

function _create_post($url, $args, $method = "POST")
{
    $ch2 = curl_init();
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true); // 获取数据返回
    curl_setopt($ch2, CURLOPT_BINARYTRANSFER, true); // 在启用 CURLOPT_RETURNTRANSFER 时候将获取数据返回
    curl_setopt($ch2, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch2, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch2, CURLOPT_HTTPHEADER, array(
        'request-trace-id: product_operation_php_restful_auth_' . date("Ymd_His") . '_' . rand(100000, 999999),
        'request-trace-level: 1',
        'Content-Type: application/json; charset=UTF-8',
        'Expect:',
    ));
    curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch2, CURLOPT_URL, $url);
    curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode($args));
    $res = curl_exec($ch2);
    $resDecode = json_decode($res, true);
    return $resDecode;
}

function put($url,$args){
    $ch2 = curl_init();
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true); // 获取数据返回
    curl_setopt($ch2, CURLOPT_BINARYTRANSFER, true); // 在启用 CURLOPT_RETURNTRANSFER 时候将获取数据返回
    curl_setopt($ch2, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch2, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch2, CURLOPT_HTTPHEADER, array(
        'request-trace-id: ' . 'ali_product_operation_client_auth_' .  date("His") . '_' .  uniqid('', true),
        'request-trace-level: 1',
        'Content-Type: application/json; charset=UTF-8',
        //'Authorization: Bearer ' . $key
        'X-Api-Key: ' . '',
    ));
    curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch2, CURLOPT_URL, $url);
    curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode($args));
    $res = curl_exec($ch2);
    $resDecode = json_decode($res, true);
    return $resDecode;
}

function post($url,$args,$method = "POST"){
    $ch2 = curl_init();
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true); // 获取数据返回
    curl_setopt($ch2, CURLOPT_BINARYTRANSFER, true); // 在启用 CURLOPT_RETURNTRANSFER 时候将获取数据返回
    curl_setopt($ch2, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch2, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch2, CURLOPT_HTTPHEADER, array(
        'request-trace-id: product_operation_php_restful_auth_' . date("Ymd_His") . '_' . rand(100000, 999999),
        'request-trace-level: 1',
        'Content-Type: application/json; charset=UTF-8',
        'Expect:',
    ));
    curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch2, CURLOPT_URL, $url);
    curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode($args));
    $res = curl_exec($ch2);
    $resDecode = json_decode($res, true);
    return $resDecode;
}
function setLog($message){
    $logFileName = "log/log_". date('Ymd') . ".log";
    $messages = "[" . date("Y-m-d H:i:s") . "] - INFO : " . $message . "\n";
    if ($f = file_put_contents($logFileName, $messages, FILE_APPEND)) {// 这个函数支持版本(PHP 5)
       echo $messages."\n";
    }
//    var_dump($logFileName);
//    var_dump($messages);
}
function log_message($message,$type = 'info') {
    $logger = new MyLogger();
    $logger->log($message);
}


function curl($url, $params, $headers, $method = "GET", $timeout = 30, $tryTimes = 1)
{
    $result = $httpCode = $headerResponse = $body = "";
    $t = 1;
    do{
        try{
            $connection = curl_init();
            curl_setopt($connection, CURLOPT_URL, $url);
            curl_setopt($connection,CURLOPT_HTTPHEADER, $headers);//头请求
            curl_setopt($connection,CURLOPT_HEADER, true);//响应头请求
            curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, false);//跳过证书的验证
            curl_setopt($connection, CURLOPT_SSL_VERIFYHOST, false);//跳过证书的验证
            curl_setopt($connection, CURLOPT_RETURNTRANSFER, true);//把curl_exec()结果转化为字串，而不是直接输出
            curl_setopt($connection, CURLOPT_TIMEOUT, $timeout);

            $method = strtoupper($method);
            switch ($method){
                case "POST":
                    curl_setopt($connection, CURLOPT_CUSTOMREQUEST, "POST");
                    curl_setopt($connection, CURLOPT_POSTFIELDS, $params);
                    break;
                case "PUT":
                    curl_setopt($connection, CURLOPT_CUSTOMREQUEST, "PUT");
                    curl_setopt($connection, CURLOPT_POSTFIELDS, $params);
                    break;
                case "DELETE":
                    curl_setopt($connection, CURLOPT_CUSTOMREQUEST, "DELETE");
                    curl_setopt($connection, CURLOPT_POSTFIELDS, $params);
                    break;
            }

            $result = curl_exec($connection);

            //头信息
            $httpCode = intval(curl_getinfo($connection, CURLINFO_HTTP_CODE));
            $headerSize = curl_getinfo($connection, CURLINFO_HEADER_SIZE);
            $headerResponse = substr($result, 0, $headerSize);
            $body = substr($result, $headerSize);

            if(!in_array($httpCode, array(401,404,429,)) && ($httpCode<200 || 300<$httpCode)){
                throw new \Exception("http {$httpCode}");
            }else{
                break;
            }
        }catch (\Exception $e){
            if($t<$tryTimes){ sleep(3); }
        }
    }while($t++<$tryTimes);

    curl_close($connection);

    return array(
        "httpCode" => $httpCode,
        "header" => $headerResponse,
        "result" => $body,
    );
}



/**
 * 获取钉钉url(PA 或者 OA)
 * @param $config
 * @return string
 */
function getDingUrl($config): string
{
    $DingDingTalk = array(
        "OA" => array(
            'access_token' => '479baefad7cc62d117ee3b85796b7b60116abfc6b06e1a092b76cb658b9b88ed',
            'secret' => 'SECf78de3fbce2d480b71c47d74b270d676c615eff9898a6264e36b927c671530b1',
        ),
        "PA" => array(
            'access_token' => '3edc780d35397bf62c5f257018d10ecab04bf050616594a116c521c386a5ffb0',
            'secret' => 'SECbee5929826dfe29d8589cb1fabc37a4756ad1366813e519cdbadd68172395fd8',
        )
    );
    $access_token = $DingDingTalk[$config]['access_token'];
    $secret = $DingDingTalk[$config]['secret'];   // 钉钉给出的密钥
    // 获取微秒数时间戳
    $Temptime = explode(' ', microtime());
    // 转换成毫秒数时间戳
    $msectime = (float)sprintf('%.0f', (floatval($Temptime[0]) + floatval($Temptime[1])) * 1000);
    // 拼装成待加密字符串
    // 格式：毫秒数+"\n"+密钥
    $stringToSign = $msectime . "\n" . $secret;
    // 进行加密操作 并输出二进制数据
    $sign = hash_hmac('sha256', $stringToSign, $secret, true);
    // 加密后进行base64编码 以及url编码
    $sign = urlencode(base64_encode($sign));
    // 拼接url
    $url = "https://oapi.dingtalk.com/robot/send?access_token={$access_token}&timestamp={$msectime}&sign={$sign}";

    return $url;
}