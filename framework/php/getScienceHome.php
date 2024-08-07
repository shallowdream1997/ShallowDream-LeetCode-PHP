<?php
//echo json_encode($_REQUEST);
require_once ("/var/www/html/testProject/php/common.php");

$message = $_REQUEST['name'] ?? "空";
//$message = str_replace(array("渣","垃","圾"),array("*","*","*"),$message);

$url = getDingUrl("OA");
$data = array(
    "msgtype" => "text",
    "text" => ["content" => $message],
    "at" => [
        "isAtAll" => false
    ]
);
$resp = post($url, $data);
echo json_encode($resp,JSON_UNESCAPED_UNICODE);
//setLog($message);