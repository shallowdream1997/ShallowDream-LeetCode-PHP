<?php
require_once(dirname(__FILE__) . "/../../php/class/Logger.php");
require_once(dirname(__FILE__) . "/../../php/utils/DataUtils.php");
require_once(dirname(__FILE__) . "/../../php/curl/CurlService.php");
require_once(dirname(__FILE__) . "/../../php/utils/RequestUtils.php");
require_once dirname(__FILE__) . '/../shell/ProductSkuController.php';
/**
 * 仅限用于同步生产数据到测试环境数据mongo的增删改查，其中delete和create只有test环境有，而find查询是pro和test有
 * Class SyncCurlController
 */
class SyncJobController
{
    /**
     * @var CurlService
     */
    public CurlService $curl;
    private MyLogger $log;
    private RedisService $redis;
    public function __construct()
    {
        $this->log = new MyLogger("common-curl/curl");

        $curlService = new CurlService();
        $this->curl = $curlService;

        $this->redis = new RedisService();
    }

    /**
     * 日志记录
     * @param string $message 日志内容
     */
    private function log($message = "")
    {
        $this->log->log2($message);
    }

    public function fixQdCertificate()
    {
        $this->log("开始处理");
        $curl = new CurlService();
        $params = [
            'ProjectName'       => 'aliyun-hn1-all-log',
            'LogStoreName'      => 'pa-biz-application-new',
            'from'              => 1764777600,
            'query'             => 'content: uploadPurchaseCertificate  | with_pack_meta',
            'to'                => 1764816494,
            'Page'              => 1,
            'Size'              => 100,
            'Reverse'           => 'true',
            'pSql'              => 'false',
            'fullComplete'      => 'false',
            'schemaFree'        => 'false',
            'needHighlight'     => 'true',
        ];
        $postData = http_build_query($params);
        try {
            $data = $curl->specialRequest($postData);
            if ($data){

                $body = $this->getLatestUniqueUploadCertificateRequests($data['data']);
                if ($body){
                    $realBodyList = [];
                    $realMap = [];
                    foreach ($body as $item){
                        $keyItem = [
                            "bidBillNo" => $item['bidBillNo'],
                            "supplierId" => $item['supplierId'],
                        ];
                        $key = md5(json_encode($keyItem,JSON_UNESCAPED_UNICODE));
                        if (!isset($realMap[$key])){
                            $realBodyList[] = $item;
                            $realMap[$key] = 1;
                        }
                    }
                    foreach ($realBodyList as $item){
                        if (isset($item['orderNoByPurchase'])){
                            $this->log("不用管，这个已经修复过了....");
                            continue;
                        }
                        $item['orderNoByPurchase'] = $item['purchaseOrderNo'];
                        unset($item['purchaseOrderNo']);
                        $this->log("开始处理：".json_encode($item,JSON_UNESCAPED_UNICODE));
                        $old = $this->redis->hGet("fixQdCertificate", $item['bidBillNo'] + $item['supplierId']);
                        if ($old){
                            $oldData = json_decode($old,true);
                            if ($oldData){
                                $this->log("已经执行过了");
                            }
                            continue;
                        }
//                        if ($item['qdBillNo'] == 'QD202512010002'){
//                            continue;
//                        }

                        $this->redis->hSet("fixQdCertificate", $item['bidBillNo'] + $item['supplierId'],json_encode($item,JSON_UNESCAPED_UNICODE));

                        $curlService = (new CurlService())->pro()->gateway()->getModule('pa');
                        $resp = DataUtils::getNewResultData($curlService->getWayPost($curlService->module . "/scms/consignment/uxplatform/v1/uploadPurchaseCertificate", $item));
                        if ($resp){
                            $this->log("处理结果：".json_encode($resp,JSON_UNESCAPED_UNICODE));
                        }
                    }

                }
            }
        } catch (Exception $e) {
            $this->log($e->getMessage());
        }

        $this->log("处理完成");

    }



    /**
     * 从 SLS 日志中提取 uploadPurchaseCertificate 的 request body，
     * 对相同内容的 request body 仅保留时间最新的那一条（按 __time__ 降序取首条）。
     *
     * @param array $logs 由 specialRequest() 返回的日志数组（每项是日志对象）
     * @return array 返回去重后的 request body 列表（每个唯一内容只出现一次，且是最新时间的）
     */
    public function getLatestUniqueUploadCertificateRequests($logs)
    {
        $grouped = []; // 用于按 request body 哈希分组，并记录最新时间

        foreach ($logs as $log) {
            if (!isset($log['content']) || !isset($log['__time__'])) {
                continue;
            }

            // 仅处理目标接口日志
            if (strpos($log['content'], '/scms/consignment/uxplatform/v1/uploadPurchaseCertificate') === false) {
                continue;
            }

            // 提取 request body JSON 字符串（更健壮）
            $jsonStr = null;
            if (preg_match('/request body:(\{.*?\})(?:\s*,\s*(?:request chain|response))/', $log['content'], $matches)) {
                $jsonStr = $matches[1];
            } else {
                // 兜底匹配
                if (preg_match('/request body:(\{[^}]*\})/', $log['content'], $matches)) {
                    $jsonStr = $matches[1];
                }
            }

            if (!$jsonStr) continue;

            $body = json_decode($jsonStr, true);
            if (json_last_error() !== JSON_ERROR_NONE) continue;

            // 标准化：排序 key + 统一 JSON 表示（用于去重）
            ksort($body);
            $bodyHash = md5(json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            $time = (int)$log['__time__'];

            // 如果该 body 未出现过，或当前日志时间更新，则更新
            if (!isset($grouped[$bodyHash]) || $time > $grouped[$bodyHash]['time']) {
                $grouped[$bodyHash] = [
                    'time' => $time,
                    'body' => $body
                ];
            }
        }

        // 按时间降序排序（最新在前）
        uasort($grouped, fn($a, $b) => $b['time'] <=> $a['time']);

        // 返回 body 列表
        return array_column($grouped, 'body');
    }

}

//$curlController = new SyncJobController();
//$curlController->fixQdCertificate();