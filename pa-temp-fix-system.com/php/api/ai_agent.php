<?php
/**
 * AI Agent 核心逻辑
 *
 * 负责：
 * 1. 构建 system prompt（包含脚本列表和参数信息）
 * 2. 定义工具函数（run_script / list_scripts / read_file / list_uploaded_files / read_script_output）
 * 3. Agent 对话循环（LLM → tool_call → 执行 → 结果返回 LLM → 最终回复）
 * 4. 对话上下文管理（Redis 存储）
 */

require_once dirname(__FILE__) . '/../bootstrap.php';
require_once dirname(__FILE__) . '/ai_client.php';

// ========== 对话上下文管理 ==========

function aiSaveConversation($sessionId, $messages)
{
    $allConfig = require dirname(__FILE__) . '/ai_config.php';
    $max = $allConfig['conversation_max_messages'];

    // 只保留最近 N 条消息（控制 token 消耗）
    if (count($messages) > $max) {
        // 保留 system prompt + 最近的消息
        $systemMsgs = array();
        $otherMsgs = array();
        foreach ($messages as $msg) {
            if (isset($msg['role']) && $msg['role'] === 'system') {
                $systemMsgs[] = $msg;
            } else {
                $otherMsgs[] = $msg;
            }
        }
        $messages = array_merge($systemMsgs, array_slice($otherMsgs, -($max - count($systemMsgs))));
    }

    try {
        $redis = new RedisService();
        $redis->set($allConfig['conversation_prefix'] . $sessionId, json_encode($messages, JSON_UNESCAPED_UNICODE), $allConfig['conversation_ttl']);
    } catch (Exception $e) {
        // Redis 不可用时静默失败
    }
}

function aiLoadConversation($sessionId)
{
    $allConfig = require dirname(__FILE__) . '/ai_config.php';
    try {
        $redis = new RedisService();
        $data = $redis->get($allConfig['conversation_prefix'] . $sessionId);
        return $data ? json_decode($data, true) : array();
    } catch (Exception $e) {
        return array();
    }
}

function aiClearConversation($sessionId)
{
    $allConfig = require dirname(__FILE__) . '/ai_config.php';
    try {
        $redis = new RedisService();
        $redis->del($allConfig['conversation_prefix'] . $sessionId);
    } catch (Exception $e) {
    }
}

// ========== System Prompt 构建 ==========

function buildSystemPrompt()
{
    $registry = buildScriptRegistry();
    $scriptParamsConfig = require dirname(__FILE__) . '/script_params.php';

    $scriptList = "可用脚本列表：\n\n";
    $currentGroup = '';
    foreach ($registry as $name => $path) {
        $parts = explode('.', $name);
        $group = count($parts) > 1 ? $parts[0] : 'other';
        if ($group !== $currentGroup) {
            $scriptList .= "\n【{$group}】\n";
            $currentGroup = $group;
        }
        $desc = getScriptDescription($path);
        $scriptList .= "  - {$name}" . ($desc ? "：{$desc}" : "") . "\n";

        // 添加参数信息
        if (isset($scriptParamsConfig[$name])) {
            foreach ($scriptParamsConfig[$name]['methods'] as $method => $methodConfig) {
                $methodLabel = $method === 'default' ? '' : " (方法:{$method})";
                $scriptList .= "    {$methodLabel} {$methodConfig['label']}\n";
                foreach ($methodConfig['params'] as $paramName => $paramDef) {
                    $required = !empty($paramDef['required']) ? '必填' : '可选';
                    $type = $paramDef['type'];
                    $default = isset($paramDef['default']) ? "，默认:{$paramDef['default']}" : '';
                    $options = '';
                    if ($type === 'select' && !empty($paramDef['options'])) {
                        $opts = array();
                        foreach ($paramDef['options'] as $val => $label) {
                            $opts[] = "{$val}({$label})";
                        }
                        $options = '，可选: ' . implode('/', $opts);
                    }
                    $scriptList .= "      - {$paramName} [{$type},{$required}{$default}{$options}]\n";
                }
            }
        }
    }

    $prompt = "你是 PA 运营助手，帮助用户管理 Amazon SP 广告运营系统。你可以执行脚本、读取文件、分析数据、给出建议。

## 你的能力

1. **执行脚本** — 通过 run_script 工具执行系统中的脚本
2. **查看脚本列表** — 通过 list_scripts 工具查看可用脚本
3. **读取 Excel/CSV 文件** — 通过 read_file 工具读取上传的文件内容
4. **列出上传文件** — 通过 list_uploaded_files 工具查看当前会话上传的文件
5. **读取脚本输出** — 通过 read_script_output 工具读取脚本执行后生成的导出文件

## {$scriptList}

## 工作原则

1. **理解意图**：用户可能用自然语言描述需求，你需要理解并匹配到正确的脚本
2. **确认参数**：执行脚本前，确保必填参数已提供。如果缺少参数，先询问用户
3. **安全提醒**：涉及生产环境(pro)操作时，提醒用户确认
4. **分析结果**：脚本执行后，解读输出内容，给出操作建议
5. **数据校验**：读取 Excel 文件时，检查数据格式、重复值、异常值
6. **分步执行**：复杂任务拆分为多步，逐步执行并汇报进度
7. **中文回复**：始终用中文回复用户

## 注意事项

- channel 参数格式为 amazon_us / amazon_uk / amazon_ca / amazon_de / amazon_fr / amazon_it / amazon_es / amazon_jp
- file 参数是 excel/ 目录下的文件名（不含路径），或用户上传的文件 ID（以 f_ 开头）
- method 参数可选值：default（默认方法）、v2（增强版）、verify（校验模式）
- 执行脚本可能需要较长时间，请耐心等待结果
- 如果脚本输出包含错误信息，分析原因并给出解决建议";

    return $prompt;
}

// ========== 工具定义 ==========

function buildToolDefinitions()
{
    return array(
        array(
            'type' => 'function',
            'function' => array(
                'name' => 'run_script',
                'description' => '执行指定的脚本。返回脚本执行结果（输出文本、耗时、参数等）。',
                'parameters' => array(
                    'type' => 'object',
                    'properties' => array(
                        'script' => array(
                            'type' => 'string',
                            'description' => '脚本名称，如 sp.keyword.paused_keyword',
                        ),
                        'method' => array(
                            'type' => 'string',
                            'description' => '方法名：default(默认)、v2(增强版)、verify(校验模式)',
                        ),
                        'args' => array(
                            'type' => 'object',
                            'description' => '脚本参数，如 {"channel":"amazon_us","file":"暂停投放清单.xlsx"}',
                            'properties' => new \stdClass(),
                        ),
                    ),
                    'required' => array('script'),
                ),
            ),
        ),
        array(
            'type' => 'function',
            'function' => array(
                'name' => 'list_scripts',
                'description' => '列出所有可用脚本及其描述。可按关键词过滤。',
                'parameters' => array(
                    'type' => 'object',
                    'properties' => array(
                        'filter' => array(
                            'type' => 'string',
                            'description' => '过滤关键词（可选），如 keyword、campaign、暂停',
                        ),
                    ),
                ),
            ),
        ),
        array(
            'type' => 'function',
            'function' => array(
                'name' => 'read_file',
                'description' => '读取上传的 Excel/CSV 文件内容。返回列名和前N行数据。file_id 以 f_ 开头的是上传文件，否则是 excel/ 目录下的文件名。',
                'parameters' => array(
                    'type' => 'object',
                    'properties' => array(
                        'file_id' => array(
                            'type' => 'string',
                            'description' => '文件ID（f_开头）或 excel/ 目录下的文件名',
                        ),
                        'rows' => array(
                            'type' => 'integer',
                            'description' => '读取行数，默认20',
                        ),
                        'sheet' => array(
                            'type' => 'string',
                            'description' => 'Sheet名，默认Sheet1',
                        ),
                    ),
                    'required' => array('file_id'),
                ),
            ),
        ),
        array(
            'type' => 'function',
            'function' => array(
                'name' => 'list_uploaded_files',
                'description' => '列出当前会话上传的所有文件。',
                'parameters' => array(
                    'type' => 'object',
                    'properties' => new \stdClass(),
                ),
            ),
        ),
        array(
            'type' => 'function',
            'function' => array(
                'name' => 'read_script_output',
                'description' => '读取脚本执行后生成的导出文件（export目录下最新的xlsx文件）。',
                'parameters' => array(
                    'type' => 'object',
                    'properties' => array(
                        'script' => array(
                            'type' => 'string',
                            'description' => '脚本名称，如 sp.keyword.paused_keyword',
                        ),
                        'rows' => array(
                            'type' => 'integer',
                            'description' => '读取行数，默认50',
                        ),
                    ),
                    'required' => array('script'),
                ),
            ),
        ),
    );
}

// ========== 工具执行 ==========

function executeTool($toolCall, $sessionId, $env)
{
    $name = $toolCall['function']['name'];
    $argsStr = isset($toolCall['function']['arguments']) ? $toolCall['function']['arguments'] : '{}';
    $args = json_decode($argsStr, true);
    if (!is_array($args)) {
        $args = array();
    }

    switch ($name) {
        case 'run_script':
            return executeRunScript($args, $env);
        case 'list_scripts':
            return executeListScripts($args);
        case 'read_file':
            return executeReadFile($args, $sessionId);
        case 'list_uploaded_files':
            return executeListFiles($sessionId);
        case 'read_script_output':
            return executeReadOutput($args);
        default:
            return array('error' => "Unknown tool: {$name}");
    }
}

/**
 * 执行脚本
 */
function executeRunScript($args, $env)
{
    $scriptName = isset($args['script']) ? $args['script'] : '';
    $method = isset($args['method']) ? $args['method'] : 'default';
    $scriptArgs = isset($args['args']) && is_array($args['args']) ? $args['args'] : array();

    if (empty($scriptName)) {
        return array('error' => '缺少 script 参数');
    }

    // 合并 method 到 args
    if ($method !== 'default') {
        $scriptArgs['method'] = $method;
    }

    // 构建参数字符串
    $argsStr = '';
    foreach ($scriptArgs as $k => $v) {
        $argsStr .= "{$k}={$v} ";
    }
    $argsStr = trim($argsStr);

    // 调用 handleRun（chat.php 中的函数）
    $result = handleRun($scriptName, $argsStr, $env);

    // 返回精简结果给 AI
    $toolResult = array(
        'script' => $scriptName,
        'method' => $method,
        'args' => $scriptArgs,
        'type' => $result['type'],
    );

    if ($result['type'] === 'script_result') {
        $toolResult['elapsed'] = $result['elapsed'];
        // 截断过长的输出（AI 上下文有限）
        $output = $result['output'];
        if (mb_strlen($output) > 3000) {
            $output = mb_substr($output, 0, 3000) . "\n... (输出已截断，共" . mb_strlen($result['output']) . "字符)";
        }
        $toolResult['output'] = $output;
    } elseif ($result['type'] === 'ask_param') {
        $toolResult['missing_params'] = $result['missing'];
        $toolResult['collected_params'] = $result['collected'];
        $toolResult['message'] = '脚本需要更多参数才能执行';
    } elseif ($result['type'] === 'not_found') {
        $toolResult['error'] = $result['reply'];
    } elseif ($result['type'] === 'ambiguous') {
        $toolResult['error'] = $result['reply'];
    } else {
        $toolResult['message'] = isset($result['reply']) ? $result['reply'] : '执行完成';
    }

    return $toolResult;
}

/**
 * 列出脚本
 */
function executeListScripts($args)
{
    $filter = isset($args['filter']) ? $args['filter'] : '';
    $registry = buildScriptRegistry();
    $scriptParamsConfig = require dirname(__FILE__) . '/script_params.php';

    $scripts = array();
    foreach ($registry as $name => $path) {
        if (!empty($filter)) {
            if (strpos($name, $filter) === false
                && strpos(strtolower(getScriptDescription($path)), strtolower($filter)) === false
            ) {
                continue;
            }
        }
        $entry = array(
            'name' => $name,
            'description' => getScriptDescription($path),
        );
        if (isset($scriptParamsConfig[$name])) {
            $entry['methods'] = array();
            foreach ($scriptParamsConfig[$name]['methods'] as $method => $methodConfig) {
                $entry['methods'][$method] = array(
                    'label' => $methodConfig['label'],
                    'params' => array_keys($methodConfig['params']),
                );
            }
        }
        $scripts[] = $entry;
    }

    return array(
        'total' => count($scripts),
        'scripts' => $scripts,
    );
}

/**
 * 读取文件
 */
function executeReadFile($args, $sessionId)
{
    $fileId = isset($args['file_id']) ? $args['file_id'] : '';
    $rows = isset($args['rows']) ? intval($args['rows']) : 20;
    $sheet = isset($args['sheet']) ? $args['sheet'] : 'Sheet1';

    if (empty($fileId)) {
        return array('error' => '缺少 file_id 参数');
    }

    $filePath = '';

    // 判断是上传文件还是 excel/ 目录下的文件
    if (strpos($fileId, 'f_') === 0) {
        // 上传文件：从 Redis 获取元信息
        try {
            $redis = new RedisService();
            $fileMeta = json_decode($redis->hGet('pa_ai_files_' . $sessionId, $fileId), true);
            if ($fileMeta && file_exists($fileMeta['path'])) {
                $filePath = $fileMeta['path'];
            }
        } catch (Exception $e) {
        }
    } else {
        // excel/ 目录下的文件：尝试在各脚本目录的 excel/ 下查找
        $registry = buildScriptRegistry();
        foreach ($registry as $name => $scriptPath) {
            $excelPath = dirname($scriptPath) . '/excel/' . $fileId;
            if (file_exists($excelPath)) {
                $filePath = $excelPath;
                break;
            }
        }
    }

    if (empty($filePath) || !file_exists($filePath)) {
        return array('error' => "文件不存在: {$fileId}");
    }

    try {
        $excelUtils = new ExcelUtils();
        $data = $excelUtils->getXlsxData($filePath, $sheet);
        $columns = count($data) > 0 ? array_keys($data[0]) : array();
        $result = array_slice($data, 0, $rows);

        return array(
            'file' => basename($filePath),
            'columns' => $columns,
            'total_rows' => count($data),
            'showing_rows' => count($result),
            'data' => $result,
        );
    } catch (Exception $e) {
        return array('error' => '读取文件失败: ' . $e->getMessage());
    }
}

/**
 * 列出上传文件
 */
function executeListFiles($sessionId)
{
    try {
        $redis = new RedisService();
        $all = $redis->hGetAll('pa_ai_files_' . $sessionId);
        $files = array();
        if ($all) {
            foreach ($all as $id => $meta) {
                $f = json_decode($meta, true);
                if ($f) {
                    $files[] = array(
                        'id' => $f['id'],
                        'name' => $f['name'],
                        'size' => $f['size'],
                        'columns' => isset($f['columns']) ? $f['columns'] : array(),
                        'rows' => isset($f['rows']) ? $f['rows'] : 0,
                        'upload_time' => isset($f['upload_time']) ? $f['upload_time'] : '',
                    );
                }
            }
        }
        return array('files' => $files, 'total' => count($files));
    } catch (Exception $e) {
        return array('files' => array(), 'total' => 0);
    }
}

/**
 * 读取脚本输出文件
 */
function executeReadOutput($args)
{
    $scriptName = isset($args['script']) ? $args['script'] : '';
    $rows = isset($args['rows']) ? intval($args['rows']) : 50;

    if (empty($scriptName)) {
        return array('error' => '缺少 script 参数');
    }

    // 根据脚本名定位 export 目录
    $registry = buildScriptRegistry();
    if (!isset($registry[$scriptName])) {
        return array('error' => "脚本不存在: {$scriptName}");
    }

    $scriptPath = $registry[$scriptName];
    $exportDir = dirname($scriptPath) . '/export/';

    if (!is_dir($exportDir)) {
        return array('error' => '导出目录不存在');
    }

    // 找最新的 xlsx 文件
    $files = glob($exportDir . '*.xlsx');
    if (empty($files)) {
        return array('error' => '没有找到导出文件');
    }

    usort($files, function ($a, $b) {
        return filemtime($b) - filemtime($a);
    });

    $latestFile = $files[0];

    try {
        $excelUtils = new ExcelUtils();
        $data = $excelUtils->getXlsxData($latestFile);
        $columns = count($data) > 0 ? array_keys($data[0]) : array();
        $result = array_slice($data, 0, $rows);

        return array(
            'file' => basename($latestFile),
            'columns' => $columns,
            'total_rows' => count($data),
            'showing_rows' => count($result),
            'data' => $result,
            'modified' => date('Y-m-d H:i:s', filemtime($latestFile)),
        );
    } catch (Exception $e) {
        return array('error' => '读取导出文件失败: ' . $e->getMessage());
    }
}

// ========== Agent 对话循环 ==========

/**
 * 处理 AI 消息
 * @param string $message 用户消息
 * @param string $env 环境
 * @param string $sessionId 会话ID
 * @return array 响应结果
 */
function handleAiMessage($message, $env, $sessionId)
{
    $allConfig = require dirname(__FILE__) . '/ai_config.php';

    // 获取用户选择的模型
    $modelKey = $allConfig['default'];
    try {
        $redis = new RedisService();
        $saved = $redis->get($allConfig['model_redis_prefix'] . $sessionId);
        if ($saved && isset($allConfig['models'][$saved])) {
            $modelKey = $saved;
        }
    } catch (Exception $e) {
    }

    $client = new AiClient($modelKey);

    if (!$client->hasApiKey()) {
        return array(
            'type' => 'ai_error',
            'reply' => "⚠️ AI 模式需要配置 API Key。\n\n请点击右上角「⚙️ 设置」按钮，配置 {$client->getModelName()} 的 API Key。\n\n你也可以切换到其他模型，或关闭 AI 模式使用关键词匹配。",
            'model' => $modelKey,
        );
    }

    // 加载对话历史
    $messages = aiLoadConversation($sessionId);

    // 如果是新对话，添加 system prompt
    $hasSystem = false;
    foreach ($messages as $msg) {
        if (isset($msg['role']) && $msg['role'] === 'system') {
            $hasSystem = true;
            break;
        }
    }
    if (!$hasSystem) {
        array_unshift($messages, array(
            'role' => 'system',
            'content' => buildSystemPrompt(),
        ));
    }

    // 添加用户消息
    $messages[] = array('role' => 'user', 'content' => $message);

    // 工具定义
    $tools = buildToolDefinitions();

    // Agent 循环
    $maxRounds = $allConfig['agent_max_rounds'];
    $toolCallResults = array(); // 记录工具调用过程，用于前端展示

    for ($i = 0; $i < $maxRounds; $i++) {
        $response = $client->chat($messages, $tools);

        // 检查错误
        if (!empty($client->getLastError()) && empty($response['content']) && empty($response['tool_calls'])) {
            aiSaveConversation($sessionId, $messages);
            return array(
                'type' => 'ai_error',
                'reply' => "⚠️ AI 调用失败：" . $client->getLastError() . "\n\n已自动切换到关键词匹配模式，你可以继续使用。",
                'model' => $modelKey,
            );
        }

        // 情况1：AI 直接回复文本（无工具调用）
        if (empty($response['tool_calls'])) {
            $content = $response['content'] ? $response['content'] : '(AI 无回复)';

            // 保存对话历史
            $messages[] = array('role' => 'assistant', 'content' => $content);
            aiSaveConversation($sessionId, $messages);

            return array(
                'type' => 'ai_reply',
                'content' => $content,
                'reply' => $content,
                'model' => $modelKey,
                'tool_calls' => $toolCallResults,
            );
        }

        // 情况2：AI 调用工具
        $toolCalls = $response['tool_calls'];
        $content = isset($response['content']) ? $response['content'] : '';

        // 将 AI 的工具调用消息加入历史
        $messages[] = array(
            'role' => 'assistant',
            'content' => $content,
            'tool_calls' => $toolCalls,
        );

        // 逐个执行工具
        foreach ($toolCalls as $toolCall) {
            $toolName = $toolCall['function']['name'];
            $toolArgs = json_decode($toolCall['function']['arguments'], true);

            // 记录工具调用（前端展示用）
            $toolCallResults[] = array(
                'name' => $toolName,
                'args' => $toolArgs,
                'status' => 'running',
            );

            // 执行工具
            $toolResult = executeTool($toolCall, $sessionId, $env);

            // 更新工具调用记录
            $toolCallResults[count($toolCallResults) - 1]['status'] = 'completed';
            $toolCallResults[count($toolCallResults) - 1]['result'] = $toolResult;

            // 将工具结果加入对话
            $toolResultStr = json_encode($toolResult, JSON_UNESCAPED_UNICODE);
            // 截断过长的结果
            if (mb_strlen($toolResultStr) > 8000) {
                $toolResultStr = mb_substr($toolResultStr, 0, 8000) . '... (结果已截断)';
            }

            $messages[] = array(
                'role' => 'tool',
                'tool_call_id' => isset($toolCall['id']) ? $toolCall['id'] : ('call_' . $i),
                'content' => $toolResultStr,
            );
        }
    }

    // 达到最大轮次，返回最后的 AI 回复
    $finalResponse = $client->chat($messages, array()); // 最后一轮不带工具，强制文本回复
    $content = isset($finalResponse['content']) ? $finalResponse['content'] : '处理轮次已达上限，请简化请求或分步操作。';

    $messages[] = array('role' => 'assistant', 'content' => $content);
    aiSaveConversation($sessionId, $messages);

    return array(
        'type' => 'ai_reply',
        'content' => $content,
        'reply' => $content,
        'model' => $modelKey,
        'tool_calls' => $toolCallResults,
    );
}
