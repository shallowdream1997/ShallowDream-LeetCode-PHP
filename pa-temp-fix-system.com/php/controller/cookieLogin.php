<?php

class CookieLogin
{
    private $cookieFile;
    private $userAgent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36';
    private $timeout = 30;
    private $baseUrl = 'http://alivpc.login.ux168.cn';

    public function __construct()
    {
        $this->cookieFile = tempnam(sys_get_temp_dir(), 'ux168_login_');
        if (!$this->cookieFile) {
            throw new Exception("无法创建临时 Cookie 文件");
        }
    }

    /**
     * 完整登录流程：先访问入口页获取 Cookie，再提交登录
     *
     * @param string $username
     * @param string $password
     * @return bool
     */
    public function performLogin($username, $password)
    {
        // 步骤 1: 访问入口页（获取初始 Cookie，如 PHPSESSID）
        $this->get($this->baseUrl . '/index_entry.php');

        // 步骤 2: 提交登录表单
        $loginData = [
            'userName' => $username,
            'password' => $password
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->baseUrl . '/sign_in.php',
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($loginData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true, // 为了检查是否跳转
            CURLOPT_FOLLOWLOCATION => false, // 我们自己处理跳转
            CURLOPT_USERAGENT      => $this->userAgent,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_COOKIEFILE     => $this->cookieFile,
            CURLOPT_COOKIEJAR      => $this->cookieFile,
            CURLOPT_HTTPHEADER     => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                'Accept-Language: zh-CN,zh;q=0.9',
                'Cache-Control: max-age=0',
                'Origin: ' . $this->baseUrl,
                'Referer: ' . $this->baseUrl . '/index_entry.php',
                'Upgrade-Insecure-Requests: 1',
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_SSL_VERIFYPEER => false, // 对应 --insecure
            CURLOPT_SSL_VERIFYHOST => 0,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("cURL 登录错误: $error");
        }

        // 登录成功通常会 302 跳转，或返回包含成功信息的页面
        // 这里简单判断：只要不是 200（表单错误页面），就认为成功
        // 更健壮：检查响应中是否包含 "登录成功" 或重定向到 dashboard
        return $httpCode === 302 || $httpCode === 200;
    }

    public function get($url)
    {
        return $this->request('GET', $url);
    }

    private function request($method, $url)
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT      => $this->userAgent,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_COOKIEFILE     => $this->cookieFile,
            CURLOPT_COOKIEJAR      => $this->cookieFile,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_HTTPHEADER     => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: zh-CN,zh;q=0.9',
                'Connection: keep-alive',
            ],
        ]);

        if ($method === 'POST') {
            // 如果需要 POST，可扩展
        }

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("请求错误: $error");
        }

        return $response;
    }

    public function getFinalCookies()
    {
        return file_get_contents($this->cookieFile);
    }

    public function __destruct()
    {
        if ($this->cookieFile && file_exists($this->cookieFile)) {
            unlink($this->cookieFile);
        }
    }

    function extractCookieValue($cookieContent, $cookieName)
    {
        $lines = explode("\n", $cookieContent);
        foreach ($lines as $line) {
            // 跳过空行、注释行（以 # 开头，但 #HttpOnly_ 除外）
            if (trim($line) === '' || (strpos($line, '#') === 0 && strpos($line, '#HttpOnly_') !== 0)) {
                continue;
            }

            // 处理 HttpOnly 前缀行（如：#HttpOnly_.example.com ...）
            if (strpos($line, '#HttpOnly_') === 0) {
                $line = substr($line, 10); // 去掉 #HttpOnly_ 前缀，保留域名等
            }

            $parts = array_map('trim', explode("\t", $line));
            // 标准 Netscape 格式应有 7 个字段
            if (count($parts) >= 7) {
                $name  = $parts[5];
                $value = $parts[6];
                if ($name === $cookieName) {
                    return $value;
                }
            }
        }
        return null; // 未找到
    }
}