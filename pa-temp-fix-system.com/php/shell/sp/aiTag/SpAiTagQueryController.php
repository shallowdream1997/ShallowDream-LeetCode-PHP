<?php
require_once(dirname(__FILE__) . "/../../../../php/requiredfile/requiredfile.php");

// 显式指定时区：本机 php.ini 未配置 date.timezone（默认 UTC），否则导出文件名/时间会差 8 小时
date_default_timezone_set('Asia/Shanghai');

/**
 * AI打标完成数据 查询导出
 *
 * 调用 sre-sql.ux168.cn 的查询接口，导出 AI 打标完成的数据为 xlsx
 * 导出列：商品编号 / 图片类型 / 图片图位 / 图片路径 / AI图片标记 / 标记完成人 / 标记完成时间
 *
 * 注意：cookie 与 csrftoken 为登录态凭证，过期后需重新从浏览器复制更新（见下方常量）
 *
 * 用法：
 *   php SpAiTagQueryController.php
 */
class SpAiTagQueryController
{
    private $log;

    /** sre-sql 查询接口地址 */
    private const SRE_SQL_URL = 'https://sre-sql.ux168.cn/query/';

    /** sre-sql 登录态 Cookie（过期后从浏览器 DevTools 重新复制整段 Cookie 更新） */
    private const SRE_SQL_COOKIE = '_ga=GA1.2.919965065.1781167423; _hjSessionUser_1119089=eyJpZCI6IjAyMWNhNjg0LTkzMjQtNTYzYi1iNDUzLWY0YmM1ODE4ZWJkYiIsImNyZWF0ZWQiOjE3ODEyMzY2MzY0NzAsImV4aXN0aW5nIjp0cnVlfQ==; _ga_4KSXYTZS67=GS2.2.s1781776268$o4$g1$t1781776303$j25$l0$h0; ph_phc_VFn4CkEGHRdlVyOOw8mfkoj1DKVoG6y1007EClvzAnS_posthog=%7B%22distinct_id%22%3A%22019f6dc9-b40b-7cbc-a7b5-7efc23fc88ea%22%2C%22%24sesid%22%3A%5B1786524886299%2C%22019ff52e-572f-7997-9127-1d8b672941db%22%2C1786524882735%5D%7D; uc_token_production=70f78cdb-bc07-4890-be3c-9cc8a70c0b42; csrftoken=Ik78ZOf792f4xD6SLsNDUew4CGh9pun4Ex0PQBhIDWRngiLXVDU8WsQpnl4L4WOZ; sessionid=r1wg4iwrj574xalrmwtyvsp5up263e7f';

    /** sre-sql 请求头 X-CSRFToken（与 Cookie 中的 csrftoken 一致，过期后一并更新） */
    private const SRE_SQL_CSRF = 'Ik78ZOf792f4xD6SLsNDUew4CGh9pun4Ex0PQBhIDWRngiLXVDU8WsQpnl4L4WOZ';

    /** 导出表头（与查询 SQL 的 SELECT 列顺序一致） */
    private const EXPORT_HEADERS = [
        '商品编号',
        '图片类型',
        '图片图位',
        '图片路径',
        'AI图片标记',
        '标记完成人',
        '标记完成时间',
    ];

    /** 接口单次最大返回行数，SQL 按此分页（LIMIT offset, PAGE_SIZE） */
    private const PAGE_SIZE = 5000;

    /** 最大翻页数（防死循环，5000 * 200 = 100万行） */
    private const MAX_PAGE = 200;

    /** 每页请求间隔秒数（避免触发接口限流，0 表示不等待） */
    private const PAGE_SLEEP = 1;

    /** 查询 SQL（原样保留，发送时由 http_build_query 自动 urlencode，分页由 export() 追加 LIMIT） */
    private const QUERY_SQL = <<<'SQL'
SELECT
    snapshot.sku_id AS '商品编号',
    CASE tag.image_source
        WHEN 'PRODUCT' THEN '商品图片'
        WHEN 'APLUS' THEN 'A+图片'
        ELSE ''
    END AS '图片类型',
    COALESCE(tag.image_type, '') AS '图片图位',
    CASE
        WHEN tag.image_url IS NULL OR tag.image_url = '' THEN ''
        WHEN tag.image_url REGEXP '^https?://' THEN tag.image_url
        WHEN tag.image_url LIKE '/%' THEN
            CONCAT('https://photonew.ux168.cn', tag.image_url)
        ELSE
            CONCAT('https://photonew.ux168.cn/', tag.image_url)
    END AS '图片路径',
    COALESCE(
        GROUP_CONCAT(
            DISTINCT CASE tag.tag_code
                WHEN 'AI_SCENE' THEN 'AI场景图'
                WHEN 'AI_PERSON' THEN 'AI人物图'
                ELSE tag.tag_code
            END
            ORDER BY FIELD(tag.tag_code, 'AI_SCENE', 'AI_PERSON')
            SEPARATOR ','
        ),
        '无标签'
    ) AS 'AI图片标记',
    snapshot.completed_by AS '标记完成人',
    DATE_FORMAT(
        snapshot.completed_time,
        '%Y-%m-%d %H:%i:%s'
    ) AS '标记完成时间'
FROM common_sku_image_ai_tag snapshot
LEFT JOIN common_sku_image_ai_tag tag
    ON tag.sku_id = snapshot.sku_id
   AND tag.record_type = 'TAG'
   AND tag.is_deleted = 0
   AND tag.`status` = 0
   AND tag.tenant_id = 0
   AND tag.instance_id = 0
   AND tag.application_id = 0
WHERE snapshot.record_type = 'SNAPSHOT'
  AND snapshot.manual_tag_completed = 1
  AND snapshot.is_deleted = 0
  AND snapshot.`status` = 0
  AND snapshot.tenant_id = 0
  AND snapshot.instance_id = 0
  AND snapshot.application_id = 0
GROUP BY
    snapshot.sku_id,
    tag.image_source,
    tag.image_type,
    tag.image_url,
    snapshot.completed_by,
    snapshot.completed_time
ORDER BY
    snapshot.sku_id,
    FIELD(tag.image_source, 'PRODUCT', 'APLUS'),
    tag.image_type,
    tag.image_url,
    -- 以下为 GROUP BY 列，追加到 ORDER BY 保证分页排序稳定（否则同一 sku 多张快照翻页会重复/漏行）
    snapshot.completed_by,
    snapshot.completed_time
SQL;

    public function __construct()
    {
        $this->log = new MyLogger("sp");
    }

    private function log(string $string = "")
    {
        $this->log->log2($string);
    }

    /**
     * 分页查询 sre-sql 并导出 xlsx
     * 接口单次最多返回 PAGE_SIZE 条，SQL 追加 LIMIT offset, PAGE_SIZE 循环翻页，直到不足一页为止
     */
    public function export()
    {
        $this->log("========== 开始分页查询 AI打标完成数据 ==========");

        $pageSize = self::PAGE_SIZE;
        $offset = 0;
        $page = 0;
        $exportData = [];

        while (true) {
            $page++;
            if ($page > self::MAX_PAGE) {
                die("已超过最大翻页数 " . self::MAX_PAGE . " 页（" . (self::MAX_PAGE * $pageSize) . " 行），疑似死循环，请检查\n");
            }

            $sql = self::QUERY_SQL . " LIMIT {$offset}, {$pageSize};";
            $this->log("第 {$page} 页 查询中 offset={$offset} ...");

            $resp = $this->querySreSql($sql);

            $res = json_decode($resp, true);
            if (!is_array($res)) {
                $this->log("响应解析失败，前500字符: " . substr($resp, 0, 500));
                die("响应解析失败，请检查登录态是否过期（SRE_SQL_COOKIE）\n");
            }

            $data = $res['data'] ?? $res;
            $rows = $data['rows'] ?? [];
            if (!is_array($rows)) {
                $rows = [];
            }
            $rowCount = count($rows);
            $this->log("第 {$page} 页 返回 {$rowCount} 行，累计 " . (count($exportData) + $rowCount) . " 行");

            // 组装导出数据（兼容 rows 为下标数组 或 关联数组 两种返回格式）
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $exportRow = [];
                foreach (self::EXPORT_HEADERS as $index => $header) {
                    $value = isset($row[$index]) ? $row[$index] : (isset($row[$header]) ? $row[$header] : '');
                    $exportRow[] = $this->decodeUnicode($value);
                }
                $exportData[] = $exportRow;
            }
            unset($rows);

            // 返回不足一页说明已是最后一页
            if ($rowCount < $pageSize) {
                break;
            }
            $offset += $pageSize;

            // 翻页间隔，避免触发接口限流
            if (self::PAGE_SLEEP > 0) {
                sleep(self::PAGE_SLEEP);
            }
        }

        $this->log("查询完成，共 " . count($exportData) . " 行");
        if (count($exportData) <= 0) {
            $this->log("无数据可导出");
            die("查询结果为空\n");
        }

        // 导出 xlsx 到同级 export/ 目录（ExcelUtils 构造参数 "sp/aiTag/" 会自动定位到本目录的 export/）
        $excelUtils = new ExcelUtils("sp/aiTag/");
        // 商品编号(0)、图片路径(3) 按文本导出，避免 Excel 转科学计数法/超链接
        $filePath = $excelUtils->downloadXlsx(
            self::EXPORT_HEADERS,
            $exportData,
            "AI打标完成数据_" . date("YmdHis") . ".xlsx",
            [0, 3]
        );
        $this->log("✅ 导出成功: {$filePath}，共 " . count($exportData) . " 行");

        return $filePath;
    }

    /**
     * 调用 sre-sql 查询接口
     * @param string $sql 完整 SQL（含分页 LIMIT）
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
            die("curl 请求失败(errno={$errno}): {$error}\n");
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

    /**
     * 转化数据中的 unicode 编码（如 商品图片 => 商品图片）
     * json_decode 已解开的正常中文原样返回；仅处理解码后仍带字面 \uXXXX 的字符串
     * @param mixed $value
     * @return mixed
     */
    private function decodeUnicode($value)
    {
        if (!is_string($value) || strpos($value, '\\u') === false) {
            return $value;
        }
        return preg_replace_callback(
            '/\\\\u([dD][89abAB][0-9a-fA-F]{2})\\\\u([dD][c-fC-F][0-9a-fA-F]{2})|\\\\u([0-9a-fA-F]{4})/',
            function ($m) {
                if (isset($m[1]) && $m[1] !== '') {
                    // 代理对（如 emoji），合并成单个码点
                    $hi = hexdec($m[1]);
                    $lo = hexdec($m[2]);
                    return $this->unicodeChar(0x10000 + (($hi - 0xD800) << 10) + ($lo - 0xDC00));
                }
                return $this->unicodeChar(hexdec($m[3]));
            },
            $value
        );
    }

    /**
     * 码点转 UTF-8 字符
     */
    private function unicodeChar(int $code): string
    {
        if (function_exists('mb_chr')) {
            return mb_chr($code, 'UTF-8');
        }
        if ($code < 0x80) {
            return chr($code);
        }
        if ($code < 0x800) {
            return chr(0xC0 | ($code >> 6)) . chr(0x80 | ($code & 0x3F));
        }
        if ($code < 0x10000) {
            return chr(0xE0 | ($code >> 12)) . chr(0x80 | (($code >> 6) & 0x3F)) . chr(0x80 | ($code & 0x3F));
        }
        return chr(0xF0 | ($code >> 18)) . chr(0x80 | (($code >> 12) & 0x3F)) . chr(0x80 | (($code >> 6) & 0x3F)) . chr(0x80 | ($code & 0x3F));
    }
}

// ===== 以下为 CLI 直接执行脚本时的入口(被 autoload/require 或 web 访问时不会执行) =====
if (PHP_SAPI === 'cli' && realpath($argv[0] ?? '') === __FILE__) {
    $con = new SpAiTagQueryController();
    $con->export();
}
