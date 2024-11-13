<?php
require_once(dirname(__FILE__) ."/../../php/class/Logger.php");
require_once(dirname(__FILE__) ."/../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) ."/../../php/curl/CurlService.php");

/**
 * 仅限用于同步生产数据到测试环境数据mongo的增删改查，其中delete和create只有test环境有，而find查询是pro和test有
 * Class SyncCurlController
 */
class SyncCurlController
{
    /**
     * @var CurlService
     */
    public CurlService $curl;
    private MyLogger $log;

    public function __construct()
    {
        $this->log = new MyLogger("common-curl/curl");

        $curlService = new CurlService();
        $this->curl = $curlService;
    }
    /**
     * 日志记录
     * @param string $message 日志内容
     */
    private function log($message = "")
    {
        $this->log->log2($message);
    }

    public function commonDelete($port,$model,$id)
    {
        $curlService = new CurlService();
        $resp = DataUtils::getResultData($curlService->test()->$port()->delete($model,$id));
        $this->log("删除{$model}，{$id}返回结果：" . json_encode($resp,JSON_UNESCAPED_UNICODE));
    }

    public function commonFindById($port,$model,$id,$env = 'test')
    {
        $curlService = new CurlService();
        $resp = $curlService->$env()->$port()->get("{$model}/{$id}");
        $data = isset($resp['result'])?$resp['result']:null;
        $this->log("查询{$model}，{$id}返回结果：" . json_encode($data,JSON_UNESCAPED_UNICODE));
        return $data;
    }

    public function commonFindByParams($port,$model,$params,$env = 'test')
    {
        if (isset($params['limit'])){
            $params['limit'] = 1;
        }
        $curlService = new CurlService();
        $resp = $curlService->$env()->$port()->get("{$model}/queryPage",$params);
        $data = [];
        if ($port == 's3044'){
            $data = DataUtils::getArrHeadData(DataUtils::getPageDocList($resp));
        }else{
            $data = DataUtils::getPageListInFirstData($resp);
        }
        $this->log("查询{$model}，" . json_encode($params,JSON_UNESCAPED_UNICODE). "返回结果：" . json_encode($data,JSON_UNESCAPED_UNICODE));
        return $data;
    }

    public function commonCreate($port,$model,$params){
        $curlService = new CurlService();
        $resp = DataUtils::getResultData($curlService->test()->post("{$model}",$params));
        $this->log("创建{$model}，" . json_encode($params,JSON_UNESCAPED_UNICODE). "返回结果：" . json_encode($resp,JSON_UNESCAPED_UNICODE));
        return $resp;
    }

}

$curlController = new SyncCurlController();
$curlController->commonFindByParams("s3044","pa_ce_materials",["batchName"=>"20201221 - 李锦烽 - 1"]);