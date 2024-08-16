<?php

require_once(dirname(__FILE__) ."/../../php/requiredfile/requiredfile.php");
//require_once(dirname(__FILE__) ."/../../php/curl/CurlService.php");

class CeMaterialController
{
    private $curlService;

    public function __construct($port = 'test'){
        $this->curlService = new CurlService();

        $this->curlService->$port();
    }

    public function search($title){
        if (empty($title)) {
            return [];
        }
        $res = $this->curlService->s3044()->get("pa_ce_materials/queryPage",[
            "limit" => 100,
            "page" => 1,
            "ceBillNo_in" => $title,
        ]);
        return DataUtils::getPageDocList($res);
    }

    public function update($_id, $status = 'materialComplete')
    {
        if (empty($_id) && empty($status)) {
            return false;
        }

        $res = DataUtils::getResultData($this->curlService->s3044()->get("pa_ce_materials/{$_id}"));

        if ($res && $res['status'] == 'success' && DataUtils::checkArrFilesIsExist($res, 'data')) {
            $mainInfo = $res['data'];
            $mainInfo['status'] = $status;

            $updateRes = DataUtils::getResultData($this->curlService->s3044()->put("pa_ce_materials/{$mainInfo['_id']}", $mainInfo));
            if ($updateRes && $updateRes['status'] == 'success' && DataUtils::checkArrFilesIsExist($updateRes, 'data')) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }

    }
}

$json = file_get_contents('php://input');
$data = json_decode($json, true); // 将JSON字符串转换为关联数组
var_export($data);