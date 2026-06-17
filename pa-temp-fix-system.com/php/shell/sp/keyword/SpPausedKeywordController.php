<?php
require_once(dirname(__FILE__) . "/../../../../php/requiredfile/requiredfile.php");
require_once(dirname(__FILE__) . "/../../../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../../../php/utils/RequestUtils.php");
require_once(dirname(__FILE__) . "/../SpApi.php");

class SpPausedKeywordController
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
            'title' => "【keyword广告写入暂停完毕】提醒",
            'msg' => [
                [
                    "key" => "",
                    "value" => "{$datetime} keyword广告写入暂停完毕"
                ]
            ]
        );
        $ali->post("dingding/sendOaNotice", $postData);
        return $this;
    }

    public function pausedKeywords($channel = "",$page = 0)
    {
        $this->log("开始处理:{$channel}");
        $excelUtils = new ExcelUtils();
        $curlService = (new CurlService())->pro();
        $redisService = new RedisService();
        $spApi = new SpApi();
        $sellerKeywordIds = [];
        try {
            $excelUtils->eachXlsxRow("./excel/keyword_Id关停清单_{$channel}_{$page}.xlsx", function ($item) use (&$sellerKeywordIds) {
                $sellerId = trim($item['seller_id'] ?? '');
                $keywordId = trim((string)($item['keyword_id'] ?? ''), "'");
                if ($sellerId !== "" && $keywordId !== "") {
                    $sellerKeywordIds[$sellerId][] = $keywordId;
                }
            });
        } catch (Exception $e) {
            die($e->getLine() . " : " . $e->getMessage());
        }

        if (count($sellerKeywordIds) <= 0) {
            $this->log("没有可处理的keyword");
            return;
        }

        $exportList = [];
        foreach ($sellerKeywordIds as $sellerId => $keywordIds) {
            $keywordIds = array_values(array_unique($keywordIds));
            $sellerKeywordList = $redisService->hGetAll("spKeyword_{$sellerId}");
            $this->log("{$sellerId} 数量: " . count($sellerKeywordList) . "个");

            $updateList = [];
            foreach ($keywordIds as $keywordId) {
                $updateList[] = [
                    "keywordId" => $keywordId,
                    "state" => "paused",
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
                    $this->log("{$sellerId} 关停keyword: " . count($chunk) . "个");
                    $pausedKeywordResult = $spApi->updateKeyword($sellerId, $chunk);
                    if (isset($pausedKeywordResult['success']) && count($pausedKeywordResult['success']) > 0) {
                        $this->log("{$sellerId} 关停成功: " . count($pausedKeywordResult['success']) . "个");
                        foreach ($pausedKeywordResult['success'] as $keywordId) {
                            if (isset($sellerKeywordList[$keywordId]) && $sellerKeywordList[$keywordId]) {
                                $spApi->mongoUpdateKeyword($sellerKeywordList[$keywordId], $keywordId, "paused");
                            } else {
                                $this->log("mongo不存在keyword但Amazon已处理成功: {$sellerId} - {$keywordId}");
                            }
                        }
                    }
                    if (isset($pausedKeywordResult['error']) && count($pausedKeywordResult['error']) > 0) {
                        $this->log("{$sellerId} 关停失败: " . count($pausedKeywordResult['error']) . "个");
                        foreach ($pausedKeywordResult['error'] as $keywordId) {
                            $exportList[] = [
                                "sellerId" => $sellerId,
                                "keywordId" => "'" . $keywordId,
                            ];
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
            ], $exportList, "关停失败的keywordId_" . date("YmdHis") . ".xlsx");
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
$con = new SpPausedKeywordController();
$con->pausedKeywords($channel,$page);
