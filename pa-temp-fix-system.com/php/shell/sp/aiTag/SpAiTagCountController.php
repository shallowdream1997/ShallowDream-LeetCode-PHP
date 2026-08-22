<?php
require_once(dirname(__FILE__) . "/../../../../php/requiredfile/requiredfile.php");

// 显式指定时区：本机 php.ini 未配置 date.timezone（默认 UTC），否则通知时间会差 8 小时
date_default_timezone_set('Asia/Shanghai');

/**
 * AI打标数量 定时统计 + 钉钉通知
 *
 * 调用 sre-sql.ux168.cn 统计两个数量：
 *   1. 已完成数量：distinct sku_id 数量（有 AI 打标记录的 SKU 总数）
 *   2. 图位维度总数量：打标记录总数（一个 sku 的一张图的一个图位算一条）
 * 每次统计记录到 Redis（上次数量 + 时间），计算已完成数量的较上次增量，
 * 通过钉钉 OA 通知 zhouangang：图位总数 / 已完成数量 / 较上次增量 / 通知时间。
 *
 * 定时：每 1 小时执行一次，crontab 配置见 shell/count.sh 文件头注释（路径按实际部署替换）
 *
 * 用法：
 *   php SpAiTagCountController.php
 *
 * 注意：cookie 与 csrftoken 为登录态凭证，过期后需重新从浏览器复制更新（见下方常量）
 */
class SpAiTagCountController
{
    private $log;

    /** sre-sql 查询接口地址 */
    private const SRE_SQL_URL = 'https://sre-sql.ux168.cn/query/';

    /** sre-sql 登录态 Cookie（过期后从浏览器 DevTools 重新复制整段 Cookie 更新） */
    private const SRE_SQL_COOKIE = '_ga=GA1.2.919965065.1781167423; _hjSessionUser_1119089=eyJpZCI6IjAyMWNhNjg0LTkzMjQtNTYzYi1iNDUzLWY0YmM1ODE4ZWJkYiIsImNyZWF0ZWQiOjE3ODEyMzY2MzY0NzAsImV4aXN0aW5nIjp0cnVlfQ==; _ga_4KSXYTZS67=GS2.2.s1781776268$o4$g1$t1781776303$j25$l0$h0; ph_phc_VFn4CkEGHRdlVyOOw8mfkoj1DKVoG6y1007EClvzAnS_posthog=%7B%22distinct_id%22%3A%22019f6dc9-b40b-7cbc-a7b5-7efc23fc88ea%22%2C%22%24sesid%22%3A%5B1786524886299%2C%22019ff52e-572f-7997-9127-1d8b672941db%22%2C1786524882735%5D%7D; uc_token_production=70f78cdb-bc07-4890-be3c-9cc8a70c0b42; csrftoken=Ik78ZOf792f4xD6SLsNDUew4CGh9pun4Ex0PQBhIDWRngiLXVDU8WsQpnl4L4WOZ; sessionid=r1wg4iwrj574xalrmwtyvsp5up263e7f';

    /** sre-sql 请求头 X-CSRFToken（与 Cookie 中的 csrftoken 一致，过期后一并更新） */
    private const SRE_SQL_CSRF = 'Ik78ZOf792f4xD6SLsNDUew4CGh9pun4Ex0PQBhIDWRngiLXVDU8WsQpnl4L4WOZ';

    /** Redis 记录 key：存 json {completed: 上次已完成数量, total: 上次图位总数, time: 上次统计时间戳} */
    private const REDIS_KEY = 'spAiTagCount';

    /** 已完成数量 SQL：distinct sku_id 数量（有 AI 打标记录的 SKU 总数） */
    private const COMPLETED_COUNT_SQL = "select count(1) from (select count(1) from common_sku_image_ai_tag group by sku_id ) a";

    /** 图位维度总数量 SQL：打标记录总数（一个 sku 的一张图的一个图位算一条） */
    private const TOTAL_COUNT_SQL = "select count(1) from common_sku_image_ai_tag order by create_time desc";

    public function __construct()
    {
        $this->log = new MyLogger("sp");
    }

    private function log(string $string = "")
    {
        $this->log->log2($string);
    }

    /**
     * 统计数量 + 记录 Redis + 发送钉钉
     */
    public function run()
    {
        $this->log("========== AI打标数量统计开始 ==========");

        // 1. 查询两个数量：已完成数量、图位维度总数量
        $completedCount = $this->queryCount(self::COMPLETED_COUNT_SQL);
        $totalCount = $this->queryCount(self::TOTAL_COUNT_SQL);
        $this->log("已完成数量: {$completedCount}，图位维度总数量: {$totalCount}");

        // 2. 读取上次记录，计算已完成数量增量
        $redis = new RedisService();
        $lastJson = $redis->get(self::REDIS_KEY);
        $last = $lastJson ? json_decode($lastJson, true) : [];
        $lastCompleted = isset($last['completed']) ? (int)$last['completed'] : 0;
        $lastTime = isset($last['time']) ? (int)$last['time'] : 0;

        $isFirst = $lastCompleted <= 0;
        $increase = $isFirst ? 0 : $completedCount - $lastCompleted;

        // 3. 写回 Redis
        $redis->set(self::REDIS_KEY, json_encode([
            'completed' => $completedCount,
            'total' => $totalCount,
            'time' => time(),
        ]));
        $this->log("已记录Redis: spAiTagCount completed={$completedCount} total={$totalCount}");

        // 4. 组装并发送钉钉 OA 通知
        $now = date("Y-m-d H:i:s", time());
        $lastTimeStr = $lastTime ? date("Y-m-d H:i:s", $lastTime) : "-";
        $increaseStr = $isFirst ? "首次记录（建立基线）" : "+{$increase}";
        $content = "图位维度总数量：{$totalCount}\n"
            . "已完成数量：{$completedCount}\n"
            . "较上次增加：{$increaseStr}\n"
            . "上次统计：{$lastTimeStr}\n"
            . "通知时间：{$now}";
        $this->sendDingTalk($content);
        $this->log("钉钉通知已发送");

        // 5. 追加历史记录（每次执行一条，不覆盖）
        $this->appendHistory($completedCount, $totalCount, $increaseStr);

        $this->log("========== AI打标数量统计结束 ==========");
    }

    /**
     * 查询统计数量（data.rows 第一个元素的第一个值）
     * @param string $sql 统计 SQL
     * @return int
     */
    private function queryCount($sql)
    {
        $resp = $this->querySreSql($sql);

        $res = json_decode($resp, true);
        if (!is_array($res)) {
            $this->failAndNotify("响应解析失败: " . substr($resp, 0, 300));
        }

        $data = $res['data'] ?? $res;
        $rows = $data['rows'] ?? [];
        if (!is_array($rows) || !isset($rows[0])) {
            $this->failAndNotify("响应中无 rows 数据: " . substr($resp, 0, 300));
        }

        $row = $rows[0];
        $count = is_array($row) ? reset($row) : $row;
        if (!is_numeric($count)) {
            $this->failAndNotify("统计结果非数字: " . var_export($count, true));
        }
        return (int)$count;
    }

    /**
     * 统计失败：发钉钉告警后退出
     * @param string $reason
     */
    private function failAndNotify($reason)
    {
        $this->log("❌ 统计失败: {$reason}");
        $this->sendDingTalk("【AI打标统计失败】\n{$reason}\n时间：" . date("Y-m-d H:i:s", time()));
        die("统计失败: {$reason}\n");
    }

    /**
     * 发送钉钉 OA 通知给 zhouangang
     * @param string $content
     */
    private function sendDingTalk($content)
    {
        $proCurlService = new CurlService();
        $ali = $proCurlService->test()->phpali();

        $postData = [
            'userType' => 'userName',
            'userIdList' => "zhouangang",
            'title' => "【AI打标数量】提醒",
            'msg' => [
                [
                    "key" => "",
                    "value" => $content,
                ]
            ]
        ];
        $ali->post("dingding/sendOaNotice", $postData);
    }

    /**
     * 每次执行追加一条历史记录（时间 / 已完成 / 图位总数 / 较上次），不覆盖
     * @param int $completedCount 已完成数量
     * @param int $totalCount 图位维度总数量
     * @param string $increaseStr 较上次增加文案
     */
    private function appendHistory($completedCount, $totalCount, $increaseStr)
    {
        $historyFile = dirname(__FILE__) . "/export/aiTagCount_history.log";
        $line = date("Y-m-d H:i:s") . " | 已完成:{$completedCount} | 图位总数:{$totalCount} | 较上次:{$increaseStr}" . PHP_EOL;
        file_put_contents($historyFile, $line, FILE_APPEND);
        $this->log("已追加历史记录: {$historyFile}");
    }

    /**
     * 调用 sre-sql 查询接口
     * @param string $sql 完整 SQL
     * @return string 接口返回的原始响应内容
     */
    private function querySreSql($sql)
    {
        $postData = [
            'instance_name' => 'aliyun_platform_pa_file_sms_bill',
            'db_name' => 'pa_biz_service',
            'schema_name' => '',
            'tb_name' => '',
            'sql_content' => $sql,
            'limit_num' => 0,
        ];

        $headers = [
            'Accept: application/json, text/javascript, */*; q=0.01',
            'Accept-Language: zh-CN,zh;q=0.9,sq;q=0.8',
            'Connection: keep-alive',
            'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
            'Origin: https://sre-sql.ux168.cn',
            'Referer: https://sre-sql.ux168.cn/sqlquery/',
            'Sec-Fetch-Dest: empty',
            'Sec-Fetch-Mode: cors',
            'Sec-Fetch-Site: same-origin',
            'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',
            'X-CSRFToken: ' . self::SRE_SQL_CSRF,
            'X-Requested-With: XMLHttpRequest',
            'sec-ch-ua: "Not=A?Brand";v="99", "Google Chrome";v="151", "Chromium";v="151"',
            'sec-ch-ua-mobile: ?0',
            'sec-ch-ua-platform: "macOS"',
            'Cookie: ' . self::SRE_SQL_COOKIE,
        ];

        $ch = curl_init();
        $curlOptions = [
            CURLOPT_URL => self::SRE_SQL_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($postData),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 600,
            CURLOPT_SSL_VERIFYPEER => true,
        ];
        // 显式指定 CA 证书：PHP 编译时硬编码的 Homebrew Cellar 路径会因升级被清理而失效（errno 77），
        // 这里探测本机实际存在的证书文件
        $caFile = $this->findCaFile();
        if ($caFile) {
            $curlOptions[CURLOPT_CAINFO] = $caFile;
        }
        curl_setopt_array($ch, $curlOptions);
        $resp = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($errno) {
            $this->failAndNotify("curl 请求失败(errno={$errno}): {$error}");
        }
        return $resp;
    }

    /**
     * 探测本机可用的 CA 证书文件路径
     * 按优先级探测常见路径，返回第一个存在的文件；都找不到返回 false（交给 curl 默认行为）
     * @return string|false
     */
    private function findCaFile()
    {
        $candidates = [
            '/opt/homebrew/etc/ca-certificates/cert.pem',
            '/opt/homebrew/opt/ca-certificates/share/ca-certificates/cacert.pem',
            '/usr/local/etc/ca-certificates/cert.pem',
            '/etc/ssl/cert.pem',
        ];
        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }
        return false;
    }
}

// ===== 以下为 CLI 直接执行脚本时的入口(被 autoload/require 或 web 访问时不会执行) =====
if (PHP_SAPI === 'cli' && realpath($argv[0] ?? '') === __FILE__) {
    $con = new SpAiTagCountController();
    $con->run();
}
