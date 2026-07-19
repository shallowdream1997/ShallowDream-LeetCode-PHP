<?php
/**
 * LLM API 客户端
 * 统一封装 OpenAI 协议（DeepSeek/GPT-4o）和 Anthropic 协议（Claude）
 * 支持 function calling / tool use
 */

class AiClient
{
    private $config;
    private $modelKey;
    private $apiKey;
    private $lastError = '';

    public function __construct($modelKey = null)
    {
        $allConfig = require dirname(__FILE__) . '/ai_config.php';
        $this->modelKey = $modelKey ?: $allConfig['default'];

        if (!isset($allConfig['models'][$this->modelKey])) {
            $this->modelKey = $allConfig['default'];
        }

        $this->config = $allConfig['models'][$this->modelKey];
        $this->apiKey = $this->config['api_key'];

        // 从 Redis 读取用户自定义 API Key（优先于配置文件）
        try {
            $redis = new RedisService();
            $userKey = $redis->get($allConfig['config_redis_prefix'] . $this->modelKey);
            if ($userKey) {
                $this->apiKey = $userKey;
            }
        } catch (Exception $e) {
            // Redis 不可用时使用配置文件中的 Key
        }
    }

    /**
     * 检查 API Key 是否已配置
     */
    public function hasApiKey()
    {
        return !empty($this->apiKey);
    }

    /**
     * 获取当前模型名称
     */
    public function getModelName()
    {
        return $this->config['name'];
    }

    /**
     * 获取当前模型 key
     */
    public function getModelKey()
    {
        return $this->modelKey;
    }

    /**
     * 获取最后一次错误
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * 发送对话请求（支持 function calling）
     * @param array $messages 对话历史
     * @param array $tools 可用工具定义
     * @return array ['content' => string|null, 'tool_calls' => array|null, 'raw' => array]
     */
    public function chat($messages, $tools = array())
    {
        if (!$this->hasApiKey()) {
            $this->lastError = 'API Key 未配置，请在设置中配置 ' . $this->config['name'] . ' 的 API Key';
            return [
                'content' => $this->lastError,
                'tool_calls' => null,
                'raw' => [],
            ];
        }

        if ($this->config['type'] === 'openai') {
            return $this->callOpenAi($messages, $tools);
        } elseif ($this->config['type'] === 'anthropic') {
            return $this->callAnthropic($messages, $tools);
        }

        $this->lastError = "Unknown API type: " . $this->config['type'];
        return ['content' => $this->lastError, 'tool_calls' => null, 'raw' => []];
    }

    /**
     * OpenAI 协议调用（DeepSeek / GPT-4o 通用）
     */
    private function callOpenAi($messages, $tools)
    {
        $payload = array(
            'model' => $this->config['model'],
            'messages' => $messages,
            'max_tokens' => $this->config['max_tokens'],
            'temperature' => $this->config['temperature'],
        );

        // 添加工具定义
        if (!empty($tools)) {
            $payload['tools'] = $tools;
            $payload['tool_choice'] = 'auto';
        }

        $response = $this->httpPost($this->config['api_url'], $payload, array(
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
        ));

        if ($response === false) {
            $this->lastError = 'API 请求失败：网络错误或超时';
            return ['content' => $this->lastError, 'tool_calls' => null, 'raw' => []];
        }

        $data = json_decode($response, true);
        if (!$data) {
            $this->lastError = 'API 响应解析失败';
            return ['content' => $this->lastError, 'tool_calls' => null, 'raw' => []];
        }

        // 检查 API 错误
        if (isset($data['error'])) {
            $errMsg = isset($data['error']['message']) ? $data['error']['message'] : json_encode($data['error']);
            $this->lastError = 'API 错误：' . $errMsg;
            return ['content' => $this->lastError, 'tool_calls' => null, 'raw' => $data];
        }

        if (!isset($data['choices']) || empty($data['choices'])) {
            $this->lastError = 'API 返回空响应';
            return ['content' => $this->lastError, 'tool_calls' => null, 'raw' => $data];
        }

        $choice = $data['choices'][0];
        $message = $choice['message'];

        $content = isset($message['content']) ? $message['content'] : '';
        $toolCalls = null;

        // 检查是否有工具调用
        if (isset($message['tool_calls']) && !empty($message['tool_calls'])) {
            $toolCalls = $message['tool_calls'];
        }

        return array(
            'content' => $content,
            'tool_calls' => $toolCalls,
            'raw' => $data,
            'finish_reason' => isset($choice['finish_reason']) ? $choice['finish_reason'] : '',
        );
    }

    /**
     * Anthropic 协议调用（Claude）
     */
    private function callAnthropic($messages, $tools)
    {
        // Anthropic 协议：system prompt 单独传，messages 不含 system role
        $systemPrompt = '';
        $apiMessages = array();
        foreach ($messages as $msg) {
            if (isset($msg['role']) && $msg['role'] === 'system') {
                $systemPrompt .= (empty($systemPrompt) ? '' : "\n") . $msg['content'];
            } else {
                $apiMessages[] = $msg;
            }
        }

        $payload = array(
            'model' => $this->config['model'],
            'max_tokens' => $this->config['max_tokens'],
            'messages' => $apiMessages,
        );

        if (!empty($systemPrompt)) {
            $payload['system'] = $systemPrompt;
        }

        // 添加工具定义
        if (!empty($tools)) {
            $payload['tools'] = array();
            foreach ($tools as $tool) {
                $payload['tools'][] = array(
                    'name' => $tool['function']['name'],
                    'description' => $tool['function']['description'],
                    'input_schema' => $tool['function']['parameters'],
                );
            }
        }

        $response = $this->httpPost($this->config['api_url'], $payload, array(
            'x-api-key: ' . $this->apiKey,
            'anthropic-version: 2023-06-01',
            'Content-Type: application/json',
        ));

        if ($response === false) {
            $this->lastError = 'API 请求失败：网络错误或超时';
            return ['content' => $this->lastError, 'tool_calls' => null, 'raw' => []];
        }

        $data = json_decode($response, true);
        if (!$data) {
            $this->lastError = 'API 响应解析失败';
            return ['content' => $this->lastError, 'tool_calls' => null, 'raw' => []];
        }

        // 检查 API 错误
        if (isset($data['error'])) {
            $errMsg = isset($data['error']['message']) ? $data['error']['message'] : json_encode($data['error']);
            $this->lastError = 'API 错误：' . $errMsg;
            return ['content' => $this->lastError, 'tool_calls' => null, 'raw' => $data];
        }

        // 解析 Anthropic 响应
        $content = '';
        $toolCalls = null;
        $toolCallsList = array();

        if (isset($data['content']) && is_array($data['content'])) {
            foreach ($data['content'] as $block) {
                if (isset($block['type']) && $block['type'] === 'text') {
                    $content .= $block['text'];
                } elseif (isset($block['type']) && $block['type'] === 'tool_use') {
                    // 转换为 OpenAI 兼容格式
                    $toolCallsList[] = array(
                        'id' => $block['id'],
                        'type' => 'function',
                        'function' => array(
                            'name' => $block['name'],
                            'arguments' => json_encode(isset($block['input']) ? $block['input'] : new \stdClass()),
                        ),
                    );
                }
            }
        }

        if (!empty($toolCallsList)) {
            $toolCalls = $toolCallsList;
        }

        return array(
            'content' => $content,
            'tool_calls' => $toolCalls,
            'raw' => $data,
            'finish_reason' => isset($data['stop_reason']) ? $data['stop_reason'] : '',
        );
    }

    /**
     * HTTP POST 请求
     */
    private function httpPost($url, $payload, $headers = array())
    {
        $allConfig = require dirname(__FILE__) . '/ai_config.php';
        $timeout = isset($allConfig['agent_timeout']) ? $allConfig['agent_timeout'] : 120;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return false;
        }

        if ($httpCode >= 400) {
            // 尝试解析错误信息
            $errData = json_decode($response, true);
            if ($errData && isset($errData['error']['message'])) {
                $this->lastError = "HTTP {$httpCode}: " . $errData['error']['message'];
            } else {
                $this->lastError = "HTTP {$httpCode}: " . substr($response, 0, 500);
            }
            return $response; // 仍然返回，让上层解析
        }

        return $response;
    }
}
