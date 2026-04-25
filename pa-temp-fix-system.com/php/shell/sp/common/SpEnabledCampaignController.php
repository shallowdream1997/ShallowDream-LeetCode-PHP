<?php
require_once(dirname(__FILE__) . "/../../../../php/requiredfile/requiredfile.php");
require_once(dirname(__FILE__) . "/../../../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/RequestUtils.php");
require_once(dirname(__FILE__) . "/../SpApi.php");

class SpEnabledCampaignController
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

    public function placementCampaign($sellerId){
        $redisService = new RedisService();
        $excelUtils = new ExcelUtils();
        $spApi = new SpApi();

        try {
            $contentList = $excelUtils->getXlsxData("excel/20260418补充asin广告.xlsx");
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }
        if (count($contentList) > 0) {

            $channel = $spApi->sellerConfig($sellerId);
            $this->log("开始处理渠道：{$channel} - {$sellerId}");

            foreach ($contentList as $content){
                if ($channel){
                    $fbaData = $spApi->pidScuMapProductIdFindScuId($channel,$content['FCU']);
                    if (!$fbaData || !$fbaData['scuId']){
                        $this->log("FCU: {$content['FCU']}，不存在fba");
                        continue;
                    }
                    $spApi->paPlacementAmazonSp($channel, $sellerId, 1,'auto','auto',$fbaData['scuId']);
                }
            }



        }




    }


    public function placementCampaignV2($channel){
        $redisService = new RedisService();
        $excelUtils = new ExcelUtils();
        $spApi = new SpApi();

        foreach ([
                     "归档重开_1",
                     "归档重开_2"
                 ] as $title) {
            try {
                $contentList = $excelUtils->getXlsxData("excel/{$title}.xlsx");
            } catch (Exception $e) {
                die($e->getLine() . " : " . $e->getMessage());
            }
            if (count($contentList) > 0) {

                $list = [];
                foreach ($contentList as $content){
                    if ($content['channel'] == $channel){
                        $list[] = $content;
                    }
                }
                $this->log("开始处理渠道：{$channel} - 共有：" . count($list) . " 个数据");
                foreach ($list as $item){
                    if ($channel){
                        $fbaData = $spApi->pidScuMapProductIdFindScuId($channel,$item['product_id']);
                        if (!$fbaData || !$fbaData['scuId']){
                            $this->log("SKU: {$item['product_id']}，不存在fba");
                            continue;
                        }
                        $spApi->paPlacementAmazonSp($channel, $item['seller_id'], 3,'manual','asin',$fbaData['scuId']);
                    }
                }



            }

        }




    }


    public function getSellerId($channel)
    {

    }


}
$parameters = DataUtils::ExplainArgv(@$argv, array());
$params = (count(@$argv) > 1) ? $parameters : $_REQUEST;
//$sellerId = "";
//if (isset($params['sellerId']) && trim($params['sellerId'] != '')) {
//    $sellerId = $params['sellerId'];
//}
//$con = new SpEnabledCampaignController();
//$con->placementCampaign($sellerId);




$channel= "";
if (isset($params['channel']) && trim($params['channel'] != '')) {
    $channel = $params['channel'];
}
$con = new SpEnabledCampaignController();
$con->placementCampaignV2($channel);