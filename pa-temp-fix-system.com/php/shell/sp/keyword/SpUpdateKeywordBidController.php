<?php
require_once(dirname(__FILE__) . "/../../../../php/requiredfile/requiredfile.php");
require_once(dirname(__FILE__) . "/../../../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/RequestUtils.php");
require_once(dirname(__FILE__) . "/../SpApi.php");

class SpUpdateKeywordBidController
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

    public function dingTalk()
    {
        $proCurlService = new CurlService();
        $ali = $proCurlService->test()->phpali();

        $datetime = date("Y-m-d H:i:s", time());
        $postData = array(
            'userType' => 'userName',
            'userIdList' => "zhouangang",
            'title' => "【keyword广告bid调整完毕】提醒",
            'msg' => [
                [
                    "key" => "",
                    "value" => "{$datetime} keyword广告bid调整完毕"
                ]
            ]
        );
        $ali->post("dingding/sendOaNotice", $postData);
        return $this;
    }

    public function updateKeywordBid($channel = "",$page = 0)
    {
        $this->log("开始处理bid:{$channel}");
        $excelUtils = new ExcelUtils();
        $curlService = (new CurlService())->pro();
        $redisService = new RedisService();
        $spApi = new SpApi();
        $sellerKeywordBidMap = [];
        try {
            $excelUtils->eachXlsxRow("./excel/keyword_id调整bid清单_{$channel}_{$page}.xlsx", function ($item) use (&$sellerKeywordBidMap) {
                $sellerId = trim($item['seller_id'] ?? '');
                $keywordId = trim((string)($item['keyword_id'] ?? ''), "'");
                $bid = trim((string)($item['bid'] ?? ''));
                if ($sellerId !== "" && $keywordId !== "" && $bid !== "") {
                    $sellerKeywordBidMap[$sellerId][$keywordId] = $bid;
                }
            });
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }

        if (count($sellerKeywordBidMap) <= 0) {
            $this->log("没有可处理的keyword bid");
            return;
        }

        $exportList = [];
        foreach ($sellerKeywordBidMap as $sellerId => $keywordBidMap) {
            $keywordIds = array_keys($keywordBidMap);
            $sellerKeywordList = $redisService->hGetAll("spKeyword_{$sellerId}");
            $this->log("{$sellerId} 数量: " . count($sellerKeywordList) . "个");

            $updateList = [];
            foreach ($keywordBidMap as $keywordId => $bid) {
                $updateList[] = [
                    "keywordId" => $keywordId,
                    "state" => "enabled",
                    "bid" => (float) $bid,
                ];
            }

            $keywordDocMap = [];
            foreach (array_chunk($keywordIds, 200) as $chunk) {
                $list = DataUtils::getPageList($curlService->s3023()->get("amazon_sp_keywords/queryPage", [
                    "channel" => $spApi->specialSellerIdConver($sellerId),
                    "keywordId_in" => implode(',', $chunk),
                    "limit" => 200
                ]));
                if (count($list) > 0) {
                    foreach ($list as $info) {
                        $seller = $spApi->specialSellerIdReverseConver($info['channel']);
                        $redisService->hSet("spKeyword_{$seller}", $info['keywordId'], $info['_id']);
                        $sellerKeywordList[$info['keywordId']] = $info['_id'];
                        $keywordDocMap[$info['keywordId']] = $info;
                    }
                }
            }

            if (count($updateList) > 0) {
                foreach (array_chunk($updateList, 200) as $chunk) {
                    $this->log("{$sellerId} 调整keyword bid: " . count($chunk) . "个");
                    $updateKeywordResult = $spApi->updateKeyword($sellerId, $chunk);
                    if (isset($updateKeywordResult['success']) && count($updateKeywordResult['success']) > 0) {
                        $this->log("{$sellerId} 调整bid成功: " . count($updateKeywordResult['success']) . "个");
                        foreach ($chunk as $item) {
                            if (in_array($item['keywordId'], $updateKeywordResult['success']) && isset($sellerKeywordList[$item['keywordId']]) && $sellerKeywordList[$item['keywordId']]) {
                                $spApi->mongoUpdateKeyword($sellerKeywordList[$item['keywordId']], $item['keywordId'], $item['state'], $item['bid']);
                            } elseif (in_array($item['keywordId'], $updateKeywordResult['success'])) {
                                $this->log("mongo不存在keyword但Amazon已处理成功: {$sellerId} - {$item['keywordId']}");
                            }
                        }
                    }
                    if (isset($updateKeywordResult['error']) && count($updateKeywordResult['error']) > 0) {
                        $this->log("{$sellerId} 调整bid失败: " . count($updateKeywordResult['error']) . "个");
                        foreach ($chunk as $item) {
                            if (in_array($item['keywordId'], $updateKeywordResult['error'])) {
                                $exportList[] = [
                                    "sellerId" => $sellerId,
                                    "keywordId" => "'" . $item['keywordId'],
                                    "bid" => $item['bid'],
                                ];
                            }
                        }
                    }
                }
            }
        }

        if (count($exportList) > 0) {
            $excelUtils = new ExcelUtils("sp/");
            $excelUtils->downloadXlsx([
                "seller_id",
                "keywordId",
                "bid",
            ], $exportList, "调整keywordBid失败_" . date("YmdHis") . ".xlsx");
        }

        $this->dingTalk();
    }
}

$parameters = DataUtils::ExplainArgv(@$argv, array());
$params = (count(@$argv) > 1) ? $parameters : $_REQUEST;
$channel = "";
$page = 0;
if (isset($params['channel']) && trim($params['channel'] != '')) {
    $channel = $params['channel'];
}
if (isset($params['page']) && trim($params['page'] != '')) {
    $page = $params['page'];
}
$con = new SpUpdateKeywordBidController();
$con->updateKeywordBid($channel);
