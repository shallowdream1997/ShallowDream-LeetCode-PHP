<?php
require_once(dirname(__FILE__) . "/../../../../php/requiredfile/requiredfile.php");
require_once(dirname(__FILE__) . "/../../../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/RequestUtils.php");
require_once(dirname(__FILE__) . "/../SpApi.php");

class SpUpdateCampaignController
{
    private $log;

    public function __construct()
    {
        $this->log = new MyLogger("sp");
    }

    private function log(string $string = "")
    {
        $this->log->log2($string);
    }

    public function updateCampaign(){
        $redisService = new RedisService();
        $spApi = new SpApi();

        $list = $spApi->getMongoCampaignInfoV3();
        foreach ($list as $item){
            $this->log("开始处理：campaignId：{$item['campaignId']} {$item['campaignName']}");

            //替换字符串$item['campaignName']内容里的aqd 改成SWI
            $item['campaignName'] = str_replace("aqd", "SWI", $item['campaignName']);

            $this->log("{$item['campaignName']}");

            if ($item['campaignId']){
                $spApi->updateAmazonCampaignName($item['channel'], $item['campaignId'], $item['campaignName']);
            }

            $spApi->mongoUpdateCampaignInfoV2($item['_id'],[
                "campaignName" => $item['campaignName'],
                "messages" => "修改adq为SWI"
            ]);
        }
    }




}

$con = new SpUpdateCampaignController();
$con->updateCampaign();
