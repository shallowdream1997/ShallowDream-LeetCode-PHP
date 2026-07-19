<?php
/**
 * CRUD 基类
 * 提供通用的增删改查操作，供 SyncCurlController 拆分后的子脚本继承
 * Class CrudService
 */
class CrudService
{
    /**
     * @var CurlService
     */
    public $curl;

    /**
     * @var MyLogger
     */
    protected $log;

    /**
     * @var string
     */
    protected $module = "platform-wms-application";

    /**
     * @var RedisService
     */
    protected $redis;

    public function __construct($logName = "crud")
    {
        $this->log = new MyLogger($logName);
        $this->curl = new CurlService();
        $this->redis = new RedisService();
    }

    public function getModule($modlue)
    {
        switch ($modlue) {
            case "wms":
                $this->module = "platform-wms-application";
                break;
            case "pa":
                $this->module = "pa-biz-application";
                break;
            case "config":
                $this->module = "platform-config-service";
                break;
            case "pomsgoods":
                $this->module = "platform-pomsgoods-service";
                break;
            case "configmgmt":
                $this->module = "platform-config-mgmt-application";
                break;
        }
        return $this;
    }

    /**
     * 日志记录
     * @param string $message 日志内容
     */
    protected function log($message = "")
    {
        $this->log->log2($message);
    }

    /**
     * 通用删除
     */
    public function commonDelete($port, $model, $id, $env = "test")
    {
        $curlService = new CurlService();
        $resp = DataUtils::getResultData($curlService->$env()->$port()->delete($model, $id));
        $this->log("删除{$model}，{$id}返回结果：" . json_encode($resp, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 通用按ID查询
     */
    public function commonFindById($port, $model, $id, $env = 'test')
    {
        $curlService = new CurlService();
        $resp = $curlService->$env()->$port()->get("{$model}/{$id}");
        $data = isset($resp['result']) ? $resp['result'] : null;
        $this->log("查询{$model}，{$id}返回结果：" . json_encode($data, JSON_UNESCAPED_UNICODE));
        return $data;
    }

    /**
     * 通用按条件查询单条
     */
    public function commonFindOneByParams($port, $model, $params, $env = 'test')
    {
        if (!isset($params['limit'])) {
            $params['limit'] = 1;
        }
        $curlService = new CurlService();
        $resp = $curlService->$env()->$port()->get("{$model}/queryPage", $params);
        $data = [];
        if ($port == 's3044') {
            $data = DataUtils::getArrHeadData(DataUtils::getPageDocList($resp));
        } else {
            $data = DataUtils::getPageListInFirstData($resp);
        }
        $this->log("查询{$model}，" . json_encode($params, JSON_UNESCAPED_UNICODE) . "返回结果：" . json_encode($data, JSON_UNESCAPED_UNICODE));
        return $data;
    }

    /**
     * 通用按条件查询列表
     */
    public function commonFindByParams($port, $model, $params, $env = 'test')
    {
        if (!isset($params['limit'])) {
            $params['limit'] = 999;
        }
        $curlService = new CurlService();
        $resp = $curlService->$env()->$port()->get("{$model}/queryPage", $params);
        $data = [];
        if ($port == 's3044') {
            $data = DataUtils::getPageDocList($resp);
        } else {
            $data = DataUtils::getPageList($resp);
        }
        $this->log("查询{$model}，" . json_encode($params, JSON_UNESCAPED_UNICODE) . "返回结果：" . json_encode($data, JSON_UNESCAPED_UNICODE));
        return $data;
    }

    /**
     * 通用创建
     */
    public function commonCreate($port, $model, $params, $env = "test")
    {
        $curlService = new CurlService();
        $resp = DataUtils::getResultData($curlService->$env()->$port()->post("{$model}", $params));
        $this->log("创建{$model}，" . json_encode($params, JSON_UNESCAPED_UNICODE) . "返回结果：" . json_encode($resp, JSON_UNESCAPED_UNICODE));
        return $resp;
    }

    /**
     * 通用更新
     */
    public function commonUpdate($port, $model, $params, $env = "test")
    {
        $curlService = new CurlService();
        $resp = DataUtils::getResultData($curlService->$env()->$port()->put("{$model}/{$params['_id']}", $params));
        $this->log("更新{$model}，" . json_encode($params, JSON_UNESCAPED_UNICODE) . "返回结果：" . json_encode($resp, JSON_UNESCAPED_UNICODE));
        return $resp;
    }
}
