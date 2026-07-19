<?php

require_once dirname(__FILE__) . '/../../../../php/bootstrap.php';

class SpDelRepeatCampaignController
{
    private $log;

    public function __construct()
    {
        $this->log = new MyLogger("sp/campaign");
    }

    private function log(string $string = "")
    {
        $this->log->log2($string);
    }

    public function delRepeatCampaign(){
        $redisService = new RedisService();
        $spApi = new SpApi();


    }




}

$con = new SpDelRepeatCampaignController();
