<?php
require_once("/var/www/html/testProject/php/common.php");

class getFirstHome
{
    public function main($hourAndMin,$config = "OA")
    {
        $messageConfig = array(
            "09:00" => "早上好！打卡了吗？",
            "11:30" => "想吃外卖的，不要忘记点外卖喔~",
            "12:00" => "再忍忍！还有30分钟就可以吃饭啦~",
            "12:30" => "快跑！美味的午饭在等着你们~",
            "14:00" => "摸鱼time~",
            "16:00" => "饿了就吃下午茶吧~",
            "18:00" => "离下班时间还有30分钟~",
            "18:30" => "下班啦~",
            "19:00" => "别卷了，没钱的~",
            "11:00" => "干个锤子"
        );
        $url = getDingUrl($config);

        if (isset($messageConfig[$hourAndMin])) {
            $message = "~  {$messageConfig[$hourAndMin]}";
            $data = array(
                "msgtype" => "text",
                "text" => ["content" => $message],
                "at" => [
                    "isAtAll" => false
                ]
            );
            $resp = post($url, $data);
            setLog(json_encode($resp, JSON_UNESCAPED_UNICODE) . "\n");
        } else {
            setLog("不需要发送\n");
        }
    }
}

$p = new getFirstHome();
$hourAndMin = date("H:i", time());
//$hourAndMin = "test";
$p->main($hourAndMin,"OA");

