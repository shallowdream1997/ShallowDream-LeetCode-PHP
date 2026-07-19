<?php
/**
 * 聊天指令 API
 *
 * POST /api/chat
 * Body: { "message": "...", "env": "pro", "sessionId": "..." }
 *
 * 支持的指令：
 *   - list / ls                  列出所有脚本
 *   - help                       显示帮助
 *   - 搜索 xxx / search xxx      搜索脚本
 *   - 执行 sp.keyword.pause channel=amazon_us  直接执行脚本
 *   - api GET s3015 path params  代理API调用
 *   - 自然语言                    关键词匹配+参数提取
 *
 * 交互式参数收集：
 *   - 当脚本需要参数时，自动通过对话逐步收集
 *   - 使用 Redis 存储会话状态
 */

require_once dirname(__FILE__) . '/../bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

// 加载参数元数据
$scriptParamsConfig = require dirname(__FILE__) . '/script_params.php';

// 会话常量
define('CHAT_SESSION_PREFIX', 'pa_chat_session_');
define('CHAT_SESSION_TTL', 1800); // 30分钟

// 读取请求
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['message'])) {
    echo json_encode(['type' => 'error', 'message' => '请提供 message 参数'], JSON_UNESCAPED_UNICODE);
    exit;
}

$message = trim($input['message']);
$env = isset($input['env']) ? $input['env'] : 'pro';
$sessionId = isset($input['sessionId']) ? trim($input['sessionId']) : 'default';

// 解析指令
$result = parseAndExecute($message, $env, $sessionId);

echo json_encode($result, JSON_UNESCAPED_UNICODE);

// ========== 会话状态管理 ==========

function saveChatSession($sessionId, $state)
{
    $redis = new RedisService();
    $redis->set(CHAT_SESSION_PREFIX . $sessionId, json_encode($state), CHAT_SESSION_TTL);
}

function loadChatSession($sessionId)
{
    $redis = new RedisService();
    $data = $redis->get(CHAT_SESSION_PREFIX . $sessionId);
    return $data ? json_decode($data, true) : null;
}

function clearChatSession($sessionId)
{
    $redis = new RedisService();
    $redis->del(CHAT_SESSION_PREFIX . $sessionId);
}

// ========== 指令解析 ==========

/**
 * 解析并执行指令
 */
function parseAndExecute($message, $env, $sessionId)
{
    $message = trim($message);

    // 0. 检查是否有进行中的参数收集会话
    $session = loadChatSession($sessionId);
    if ($session && !empty($session['missing'])) {
        // 内置指令优先（list/help/search 不受参数收集影响）
        if ($message === 'list' || $message === 'ls' || $message === 'help' || $message === '帮助') {
            // 不中断参数收集会话，但执行指令
        } elseif (preg_match('/^(?:搜索|search|查找|find)\s+(.+)$/iu', $message)) {
            // 搜索指令也不中断
        } else {
            return handleParamInput($message, $session, $env, $sessionId);
        }
    }

    // 1. 内置指令
    if ($message === 'list' || $message === 'ls') {
        return handleList();
    }
    if ($message === 'help' || $message === '帮助') {
        return handleHelp();
    }

    // 2. 搜索指令
    if (preg_match('/^(?:搜索|search|查找|find)\s+(.+)$/iu', $message, $m)) {
        return handleSearch(trim($m[1]));
    }

    // 3. 直接执行指令
    if (preg_match('/^(?:执行|run|exec)\s+(\S+)(?:\s+(.+))?$/iu', $message, $m)) {
        return handleRun(trim($m[1]), isset($m[2]) ? trim($m[2]) : '', $env, $sessionId);
    }

    // 4. API 代理指令
    if (preg_match('/^api\s+(GET|POST|PUT|DELETE)\s+(\S+)(?:\s+(\S+))?(?:\s+(.+))?$/i', $message, $m)) {
        return handleApiProxy(
            $m[1],
            $m[2],
            isset($m[3]) ? $m[3] : '',
            isset($m[4]) ? $m[4] : '',
            $env
        );
    }

    // 5. 自然语言 → 关键词匹配 + 参数检查
    return handleNaturalLanguage($message, $env, $sessionId);
}

// ========== 交互式参数收集 ==========

/**
 * 处理参数输入
 */
function handleParamInput($message, $session, $env, $sessionId)
{
    global $scriptParamsConfig;

    $scriptName = $session['script'];
    $value = trim($message);

    // 取消参数收集
    if (in_array(strtolower($value), ['取消', 'cancel', '退出', 'exit', '算了'])) {
        clearChatSession($sessionId);
        return ['type' => 'cancelled', 'reply' => '已取消参数收集。'];
    }

    // 跳过当前可选参数
    if (in_array(strtolower($value), ['跳过', 'skip', '默认', '空', ''])) {
        $currentParam = $session['missing'][0];
        $methodConfig = $scriptParamsConfig[$scriptName]['methods'][$session['method']];
        $paramDef = $methodConfig['params'][$currentParam];
        if (!empty($paramDef['required'])) {
            return buildAskParamReply($scriptName, $session, $currentParam, '此参数为必填，不能跳过');
        }
        // 使用默认值
        if (isset($paramDef['default'])) {
            $session['collected'][$currentParam] = $paramDef['default'];
        }
        // 从缺失列表移除
        $session['missing'] = array_values(array_diff($session['missing'], [$currentParam]));
    } else {
        // 解析用户输入
        if (preg_match('/^(\w+)=(.+)$/', $value, $m)) {
            // key=value 格式
            $session['collected'][$m[1]] = trim($m[2]);
        } else {
            // 将整个输入作为当前参数的值
            $currentParam = $session['missing'][0];
            $session['collected'][$currentParam] = $value;
        }
    }

    // 更新缺失参数列表（移除已收集的）
    $session['missing'] = array_values(array_diff($session['missing'], array_keys($session['collected'])));

    // 如果还有缺失参数，继续询问
    if (!empty($session['missing'])) {
        $nextParam = $session['missing'][0];
        saveChatSession($sessionId, $session);
        return buildAskParamReply($scriptName, $session, $nextParam);
    }

    // 所有参数收集完毕，执行脚本
    clearChatSession($sessionId);
    $argsStr = buildArgsString($session['collected']);
    return handleRun($scriptName, $argsStr, $env);
}

/**
 * 构建参数询问回复
 */
function buildAskParamReply($scriptName, $session, $paramName, $error = '')
{
    global $scriptParamsConfig;

    $method = $session['method'];
    $methodConfig = $scriptParamsConfig[$scriptName]['methods'][$method];
    $paramDef = $methodConfig['params'][$paramName];
    $collected = $session['collected'];
    $missing = $session['missing'];

    // 计算进度
    $totalParams = count($methodConfig['params']);
    $collectedCount = count($collected);

    $reply = "📋 脚本: {$scriptName} — {$methodConfig['label']}\n";
    $reply .= "📊 参数进度: {$collectedCount}/{$totalParams}\n\n";

    if ($error) {
        $reply .= "⚠️ {$error}\n\n";
    }

    $reply .= "请输入 **{$paramDef['label']}** ({$paramName})";
    if (!empty($paramDef['required'])) {
        $reply .= " *必填*";
    } else {
        $defaultDisplay = isset($paramDef['default']) ? var_export($paramDef['default'], true) : '空';
        $reply .= " (可选，默认: {$defaultDisplay})";
    }

    // 提示信息
    if (isset($paramDef['hint'])) {
        $reply .= "\n💡 {$paramDef['hint']}";
    }

    // 如果是 select 类型，列出选项
    if ($paramDef['type'] === 'select' && !empty($paramDef['options'])) {
        $reply .= "\n\n可选值：";
        foreach ($paramDef['options'] as $val => $label) {
            $reply .= "\n  • {$val} — {$label}";
        }
    }

    // 如果是 boolean 类型
    if ($paramDef['type'] === 'boolean') {
        $reply .= "\n\n输入 true/false 或 是/否";
    }

    // 显示已收集的参数
    if (!empty($collected)) {
        $reply .= "\n\n✅ 已填写：";
        foreach ($collected as $k => $v) {
            $reply .= "\n  • {$k} = {$v}";
        }
    }

    // 跳过提示
    if (empty($paramDef['required'])) {
        $reply .= "\n\n💡 输入「跳过」使用默认值";
    }
    $reply .= " | 输入「取消」退出";

    return [
        'type' => 'ask_param',
        'script' => $scriptName,
        'method' => $method,
        'param' => $paramName,
        'paramDef' => $paramDef,
        'collected' => $collected,
        'missing' => $missing,
        'progress' => ['collected' => $collectedCount, 'total' => $totalParams],
        'reply' => $reply,
    ];
}

/**
 * 构建参数字符串（key=value 格式）
 */
function buildArgsString($collected)
{
    $parts = [];
    foreach ($collected as $k => $v) {
        $parts[] = "{$k}={$v}";
    }
    return implode(' ', $parts);
}

/**
 * 检查脚本参数并启动收集流程（如果需要）
 * @return array|null 返回 ask_param 回复，或 null 表示参数齐全
 */
function checkAndCollectParams($scriptName, $providedArgs, $env, $sessionId, $method = 'default')
{
    global $scriptParamsConfig;

    if (!isset($scriptParamsConfig[$scriptName])) {
        return null; // 无参数定义，直接执行
    }

    if (!isset($scriptParamsConfig[$scriptName]['methods'][$method])) {
        $method = 'default'; // 降级到 default
    }

    $methodConfig = $scriptParamsConfig[$scriptName]['methods'][$method];

    $collected = [];
    $missing = [];

    foreach ($methodConfig['params'] as $name => $def) {
        if (isset($providedArgs[$name])) {
            $collected[$name] = $providedArgs[$name];
        } elseif (!empty($def['required']) && !isset($def['default'])) {
            $missing[] = $name;
        }
        // 可选参数且有默认值的，不加入 missing
    }

    if (empty($missing)) {
        return null; // 参数齐全
    }

    // 保存会话状态，开始参数收集
    $session = [
        'script' => $scriptName,
        'method' => $method,
        'collected' => $collected,
        'missing' => $missing,
        'step' => 0,
    ];
    saveChatSession($sessionId, $session);
    return buildAskParamReply($scriptName, $session, $missing[0]);
}

// ========== 原有指令处理 ==========

/**
 * 列出所有脚本
 */
function handleList()
{
    $registry = buildScriptRegistry();
    $scripts = [];
    foreach ($registry as $name => $path) {
        $parts = explode('.', $name);
        $group = count($parts) > 1 ? $parts[0] : 'other';
        $scripts[] = [
            'name' => $name,
            'group' => $group,
            'description' => getScriptDescription($path),
        ];
    }

    return [
        'type' => 'script_list',
        'scripts' => $scripts,
        'total' => count($scripts),
        'reply' => '共有 ' . count($scripts) . ' 个可用脚本。输入"搜索 关键词"来查找特定脚本。',
    ];
}

/**
 * 帮助信息
 */
function handleHelp()
{
    return [
        'type' => 'help',
        'reply' => "可用指令：\n" .
            "• list / ls — 列出所有脚本\n" .
            "• 搜索 关键词 — 搜索脚本\n" .
            "• 执行 脚本名 [参数] — 执行脚本（缺少参数会逐步询问）\n" .
            "• api GET/POST 服务 路径 [参数] — 代理API调用\n" .
            "• 自然语言 — 描述你想做什么，我会匹配脚本\n\n" .
            "参数收集：\n" .
            "• 执行脚本时，缺少的必填参数会逐步询问\n" .
            "• 可选参数可输入「跳过」使用默认值\n" .
            "• 输入「取消」可随时退出参数收集\n\n" .
            "示例：\n" .
            "• 暂停 amazon_us 关键词\n" .
            "• 执行 sp.campaign.update_campaign_budget\n" .
            "• 执行 sp.keyword.paused_keyword channel=amazon_us\n" .
            "• api GET s3015 pa_products/queryPage limit=10",
    ];
}

/**
 * 搜索脚本
 */
function handleSearch($keyword)
{
    $registry = buildScriptRegistry();
    $results = [];

    foreach ($registry as $name => $path) {
        if (strpos($name, $keyword) !== false
            || strpos(basename($path), $keyword) !== false
            || matchKeywordMap($name, $keyword)
        ) {
            $results[] = [
                'name' => $name,
                'description' => getScriptDescription($path),
                'params' => getScriptParams($path),
            ];
        }
    }

    if (empty($results)) {
        return [
            'type' => 'search_empty',
            'reply' => "没有找到与「{$keyword}」相关的脚本。输入 list 查看所有脚本。",
        ];
    }

    $reply = "找到 " . count($results) . " 个相关脚本：\n";
    foreach ($results as $i => $r) {
        $reply .= ($i + 1) . ". {$r['name']}" . ($r['description'] ? " ({$r['description']})" : "") . "\n";
    }
    $reply .= "\n输入「执行 脚本名」来执行，如：执行 {$results[0]['name']}";

    return [
        'type' => 'search_result',
        'scripts' => $results,
        'reply' => $reply,
    ];
}

/**
 * 执行脚本
 */
function handleRun($scriptName, $argsStr, $env, $sessionId = null)
{
    global $scriptParamsConfig;

    $registry = buildScriptRegistry();

    // 精确匹配
    if (!isset($registry[$scriptName])) {
        // 模糊匹配
        $matches = [];
        foreach ($registry as $name => $path) {
            if (strpos($name, $scriptName) !== false) {
                $matches[$name] = $path;
            }
        }
        if (count($matches) === 1) {
            $scriptName = array_key_first($matches);
        } elseif (count($matches) > 1) {
            $reply = "「{$scriptName}」匹配到多个脚本：\n";
            foreach ($matches as $name => $path) {
                $reply .= "  - {$name}\n";
            }
            return [
                'type' => 'ambiguous',
                'matches' => array_keys($matches),
                'reply' => $reply . '请使用更精确的名称。',
            ];
        } else {
            return [
                'type' => 'not_found',
                'reply' => "脚本「{$scriptName}」不存在。输入 list 查看所有脚本。",
            ];
        }
    }

    $filePath = $registry[$scriptName];

    // 解析已有参数
    $args = parseArgs($argsStr);

    // 检查是否需要 method 参数（从 script_params 获取）
    $method = 'default';
    if (isset($args['method']) && !empty($args['method'])) {
        $method = $args['method'];
        unset($args['method']);
    }

    // 检查参数完整性，缺少则启动交互收集
    if ($sessionId) {
        $askResult = checkAndCollectParams($scriptName, $args, $env, $sessionId, $method);
        if ($askResult !== null) {
            return $askResult;
        }
    }

    // 合并 method 参数到 args
    if ($method !== 'default') {
        $args['method'] = $method;
    }
    if (isset($args['env'])) {
        $env = $args['env'];
        unset($args['env']);
    }

    // 构造 $_SERVER['argv'] 和 $_REQUEST
    $argv = ['php'];
    foreach ($args as $key => $value) {
        $argv[] = "-{$key}";
        $argv[] = "{$value}";
    }
    $_SERVER['argv'] = $argv;
    $_REQUEST = array_merge($_REQUEST, $args);

    // 捕获输出
    ob_start();
    $startTime = microtime(true);

    try {
        require $filePath;
        $output = ob_get_clean();
    } catch (Exception $e) {
        $output = ob_get_clean();
        $output .= "\n[异常] " . $e->getMessage();
    } catch (Throwable $e) {
        $output = ob_get_clean();
        $output .= "\n[错误] " . $e->getMessage();
    }

    $elapsed = round(microtime(true) - $startTime, 2);

    return [
        'type' => 'script_result',
        'script' => $scriptName,
        'output' => $output,
        'elapsed' => $elapsed,
        'env' => $env,
        'args' => $args,
        'reply' => "脚本 {$scriptName} 执行完成 ({$elapsed}s)\n\n{$output}",
    ];
}

/**
 * API 代理
 */
function handleApiProxy($method, $service, $path, $paramsStr, $env)
{
    $params = [];
    if ($paramsStr) {
        parse_str($paramsStr, $params);
    }

    try {
        $curlService = new CurlService();
        $curlService->setEnvironment($env);

        // 设置服务端口
        if (method_exists($curlService, $service)) {
            $curlService->$service();
        } else {
            return [
                'type' => 'error',
                'reply' => "未知的服务：{$service}。可用服务：s3015, s3047, s3044, s3009, s3023, s3013, phphk, phpali, ux168, s3010, s3016, gateway, smsSupport",
            ];
        }

        $method = strtoupper($method);
        switch ($method) {
            case 'GET':
                $result = $curlService->get($path, $params);
                break;
            case 'POST':
                $result = $curlService->post($path, $params);
                break;
            case 'PUT':
                $result = $curlService->put($path, $params);
                break;
            case 'DELETE':
                $result = $curlService->delete($path);
                break;
            default:
                return ['type' => 'error', 'reply' => "不支持的请求方法：{$method}"];
        }

        return [
            'type' => 'api_result',
            'method' => $method,
            'service' => $service,
            'path' => $path,
            'env' => $env,
            'result' => $result,
            'reply' => "API 调用完成：{$method} {$service} {$path}\n" .
                "HTTP Code: " . ($result['httpCode'] ?? 'N/A') . "\n" .
                "响应: " . json_encode($result['result'] ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        ];
    } catch (Exception $e) {
        return [
            'type' => 'error',
            'reply' => "API 调用失败：" . $e->getMessage(),
        ];
    }
}

/**
 * 自然语言处理 — 关键词匹配 + 参数提取 + 交互式收集
 */
function handleNaturalLanguage($message, $env, $sessionId)
{
    global $scriptParamsConfig;

    $registry = buildScriptRegistry();

    // 提取参数
    $params = extractParams($message);

    // 关键词匹配脚本
    $matchedScripts = [];
    foreach ($registry as $name => $path) {
        $score = matchScore($name, $path, $message);
        if ($score > 0) {
            $matchedScripts[$name] = [
                'path' => $path,
                'score' => $score,
                'description' => getScriptDescription($path),
                'params' => getScriptParams($path),
            ];
        }
    }

    // 按匹配度排序
    uasort($matchedScripts, function ($a, $b) {
        return $b['score'] - $a['score'];
    });

    if (empty($matchedScripts)) {
        return [
            'type' => 'no_match',
            'reply' => "没有理解你的意图。你可以：\n" .
                "• 输入「list」查看所有脚本\n" .
                "• 输入「搜索 关键词」搜索脚本\n" .
                "• 输入「执行 脚本名」直接执行\n" .
                "• 试试：「暂停 amazon_us 关键词」「预算」「导出」",
        ];
    }

    // 取第一个匹配的脚本
    $firstScript = array_key_first($matchedScripts);
    $firstInfo = $matchedScripts[$firstScript];

    // 检查是否有参数定义
    $askResult = checkAndCollectParams($firstScript, $params, $env, $sessionId);
    if ($askResult !== null) {
        // 在询问参数的回复中，也显示其他匹配的脚本
        $otherCount = count($matchedScripts) - 1;
        if ($otherCount > 0) {
            $askResult['reply'] .= "\n\n📌 其他匹配的脚本（{$otherCount}个）：";
            $i = 0;
            foreach ($matchedScripts as $name => $info) {
                if ($name !== $firstScript && $i < 3) {
                    $askResult['reply'] .= "\n  • {$name}";
                    $i++;
                }
            }
        }
        return $askResult;
    }

    // 参数齐全或无参数定义，直接执行
    $argsStr = buildArgsString($params);
    return handleRun($firstScript, $argsStr, $env, $sessionId);
}

/**
 * 匹配度评分
 */
function matchScore($scriptName, $filePath, $message)
{
    $score = 0;
    $msg = strtolower($message);
    $name = strtolower($scriptName);
    $desc = strtolower(getScriptDescription($filePath));

    // 关键词映射评分
    $keywordScores = [
        '暂停' => ['paused' => 10],
        '启用' => ['enabled' => 10],
        '预算' => ['budget' => 10],
        '关键词' => ['keyword' => 10],
        '投放' => ['campaign' => 5, 'controller' => 2],
        '目标' => ['target' => 10],
        '否定' => ['negative' => 10],
        '导出' => ['export' => 10],
        '同步' => ['sync' => 10],
        'ebay' => ['ebay' => 10],
        '卖家' => ['seller' => 10],
        '广告组' => ['adgroup' => 10],
        '组合' => ['portfolio' => 10],
        '创建' => ['create' => 8],
        '删除' => ['del' => 8],
        '修复' => ['fix' => 8],
        '产品' => ['product' => 8],
        '分类' => ['category' => 5, 'fill' => 5],
        '迁移' => ['migration' => 10],
        '归档' => ['archived' => 10],
        'bid' => ['bid' => 10],
        'campaign' => ['campaign' => 10],
        'keyword' => ['keyword' => 10],
        'target' => ['target' => 10],
    ];

    foreach ($keywordScores as $key => $patterns) {
        if (strpos($msg, $key) !== false) {
            foreach ($patterns as $pattern => $points) {
                if (strpos($name, $pattern) !== false) {
                    $score += $points;
                }
            }
        }
    }

    // 名称精确度加分：脚本名越短越精确，匹配加分越多
    // 如 "暂停关键词" 匹配 sp.keyword.paused_keyword 比 sp.common.paused_n_keyword... 更精确
    $nameParts = explode('.', $name);
    $namePartCount = count($nameParts);
    if ($score > 0) {
        // 否定词减分：如果消息中没有"否定"但脚本名包含"negative"，减分
        if (strpos($name, 'negative') !== false && strpos($msg, '否定') === false && strpos($msg, 'negative') === false) {
            $score -= 15;
        }
        // 如果消息中没有"广告组"但脚本名包含"adgroup"，减分
        if (strpos($name, 'adgroup') !== false && strpos($msg, '广告组') === false && strpos($msg, 'adgroup') === false) {
            $score -= 10;
        }
        // 如果消息中没有"common"但脚本名包含"common"，减分
        if (strpos($name, 'common') !== false && strpos($msg, 'common') === false) {
            $score -= 5;
        }
    }

    // 直接包含脚本名关键词
    $words = preg_split('/\s+/', $msg);
    foreach ($words as $word) {
        if (strlen($word) >= 3 && strpos($name, $word) !== false) {
            $score += 5;
        }
        if (strlen($word) >= 2 && strpos($desc, $word) !== false) {
            $score += 3;
        }
    }

    return $score;
}

/**
 * 从消息中提取参数
 */
function extractParams($message)
{
    $params = [];

    // 提取 channel
    if (preg_match('/(amazon_\w+)/i', $message, $m)) {
        $params['channel'] = $m[1];
    }

    // 提取环境
    if (preg_match('/(pro|test|uat|local)/i', $message, $m)) {
        $params['env'] = strtolower($m[1]);
    }

    // 提取页码
    if (preg_match('/第(\d+)页|page[=:\s]*(\d+)/iu', $message, $m)) {
        $params['page'] = $m[1] ?: $m[2];
    }

    // 提取 dry_run
    if (preg_match('/dry[_\s]?run|模拟|预览/i', $message)) {
        $params['dry_run'] = 'true';
    }

    // 提取 method
    if (preg_match('/(?:方法|method)[=:\s]*(v2|verify|check|fix)/iu', $message, $m)) {
        $params['method'] = strtolower($m[1]);
    } elseif (preg_match('/(v2|校验|验证)/iu', $message, $m)) {
        if (strtolower($m[1]) === 'v2') {
            $params['method'] = 'v2';
        } elseif (in_array(strtolower($m[1]), ['校验', '验证'])) {
            $params['method'] = 'verify';
        }
    }

    // 提取 key=value 格式参数
    if (preg_match_all('/(\w+)=(\S+)/', $message, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $key = $match[1];
            $value = $match[2];
            if (!in_array($key, ['执行', 'run', 'exec', '搜索', 'search', 'api'])) {
                $params[$key] = $value;
            }
        }
    }

    return $params;
}

/**
 * 解析参数字符串
 */
function parseArgs($argsStr)
{
    $args = [];
    if (empty($argsStr)) {
        return $args;
    }

    // key=value 格式
    if (preg_match_all('/(\w+)=(\S+)/', $argsStr, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $args[$match[1]] = $match[2];
        }
    }

    return $args;
}

/**
 * 构建脚本注册表
 */
function buildScriptRegistry()
{
    $baseDir = dirname(__FILE__) . '/../shell';
    $registry = [];

    // shell/ 下的直接脚本
    foreach (glob($baseDir . '/*.php') as $file) {
        $name = basename($file, '.php');
        $registry[strtolower($name)] = $file;
    }

    // shell/ 下的子目录脚本（1层）
    foreach (glob($baseDir . '/*/*.php') as $file) {
        $group = basename(dirname($file));
        $className = basename($file, '.php');
        $shortName = preg_replace('/Controller$/', '', $className);
        $shortName = preg_replace('/([a-z])([A-Z])/', '$1_$2', $shortName);
        $registry[strtolower($group) . '.' . strtolower($shortName)] = $file;
    }

    // shell/sp/ 下的直接脚本
    foreach (glob($baseDir . '/sp/*.php') as $file) {
        $className = basename($file, '.php');
        $shortName = preg_replace('/Controller$/', '', $className);
        $shortName = preg_replace('/([a-z])([A-Z])/', '$1_$2', $shortName);
        $registry['sp.' . strtolower($shortName)] = $file;
    }

    // shell/sp/ 下的子模块脚本（2层）
    foreach (glob($baseDir . '/sp/*/*.php') as $file) {
        $type = basename(dirname($file));
        $className = basename($file, '.php');
        $shortName = preg_replace('/^Sp|Controller$/', '', $className);
        $shortName = preg_replace('/([a-z])([A-Z])/', '$1_$2', $shortName);
        $registry['sp.' . strtolower($type) . '.' . strtolower($shortName)] = $file;
    }

    ksort($registry);
    return $registry;
}

/**
 * 获取脚本描述
 */
function getScriptDescription($filePath)
{
    static $descMap = [
        'SpPausedKeywordController' => '暂停关键词投放',
        'SpEnabledKeywordController' => '启用关键词投放',
        'SpCreateKeywordController' => '创建关键词',
        'SpUpdateKeywordBidController' => '调整关键词bid',
        'SpPausedTargetController' => '暂停投放目标',
        'SpEnabledTargetController' => '启用投放目标',
        'SpCreateTargetController' => '创建投放目标',
        'SpUpdateTargetBidController' => '调整目标bid',
        'SpUpdateCampaignBudgetController' => '更新campaign预算',
        'SpUpdateCampaignController' => '更新campaign',
        'SpDelRepeatCampaignController' => '删除重复campaign',
        'SpPausedNKeywordAndNTargetByAdGroupController' => '按广告组暂停否定关键词和否定目标',
        'SpEnabledNKeywordAndTargetByAdGroupController' => '按广告组启用否定关键词和目标',
        'SpEnabledCampaignController' => '启用campaign',
        'SpSyncPomsController' => '同步POMS数据',
        'SpArchivedErrorAdGroupController' => '归档错误的广告组',
        'SpPausedProductController' => '暂停产品广告',
        'SpInitSellerController' => '初始化卖家数据',
        'CheckPortfolioStateController' => '检查组合状态',
        'SpCreateNegativeKeywordController' => '创建否定关键词',
        'SpEnabledNegativeKeywordController' => '启用否定关键词',
        'SpPausedNegativeKeywordController' => '暂停否定关键词',
        'SpCreateNegativeTargetController' => '创建否定目标',
        'SpEnabledNegativeTargetController' => '启用否定目标',
        'SpPausedNegativeTargetController' => '暂停否定目标',
        'MigrationSpDataController' => '迁移SP数据',
        'SpController' => 'SP广告投放',
        'SpEnabledController' => '启用SP广告',
        'SpPausedController' => '暂停SP广告',
        'SpPausedAdGroupController' => '暂停广告组',
        'SpRuleController' => 'SP规则管理',
        'SpUpdateAdGroupController' => '更新广告组',
        'SpFindCanNotCreateController' => '查找无法创建的广告',
        'ExportEbaySellerAllocationRecordController' => '导出eBay卖家分配记录',
        'ExecuteEbaySellerAllocationController' => '执行eBay卖家分配',
        'FillSellerAllocationCategoryConfigController' => '填充卖家分配分类配置',
        'DelEbayBillRoundController' => '删除eBay账单轮次',
        'ProductSkuController' => '产品SKU操作',
        'GatWayRequestController' => 'Gateway请求',
        'SyncJob1Controller' => '同步任务1',
        'SyncProductSku' => '同步产品SKU',
        'SyncSkuMaterialToAudit' => '同步SKU资料到审核',
        'SyncAiCategoryRecommand' => '同步AI分类推荐',
        'FixCeSkuMaterial' => '修复CE SKU资料',
        'FixPaSkuMaterialSpDataController' => '修复PA SKU资料SP数据',
        'FixPmoSkuController' => '修复PMO SKU',
        'FixSkuSupplierQuotePrice' => '修复SKU供应商报价',
        'Calc' => '计算工具',
        'Sync' => '同步工具',
        'SkuMaterialSync' => 'SKU资料同步',
        'CeMaterialSync' => 'CE资料同步',
        'ProductSync' => '产品同步',
        'SkuFix' => 'SKU修复',
        'DataExport' => '数据导出',
        'SguSync' => 'SGU同步',
        'ConfigSync' => '配置同步',
        'AdSync' => '广告同步',
        'PmoSync' => 'PMO同步',
    ];

    $className = basename($filePath, '.php');
    return $descMap[$className] ?? '';
}

/**
 * 从脚本文件提取参数信息
 */
function getScriptParams($filePath)
{
    $content = @file_get_contents($filePath);
    if ($content === false) {
        return [];
    }

    $params = [];

    if (preg_match_all('/\$argv\[(\d+)\]\s*(?:\?\s*:\s*|=\s*)[\'"]?(\w+)[\'"]?/i', $content, $matches)) {
        foreach ($matches[2] as $param) {
            if (!in_array($param, $params)) {
                $params[] = $param;
            }
        }
    }

    if (preg_match_all('/\$_(?:REQUEST|GET|POST)\[[\'"](\w+)[\'"]\]/i', $content, $matches)) {
        foreach ($matches[1] as $param) {
            if (!in_array($param, $params)) {
                $params[] = $param;
            }
        }
    }

    if (preg_match_all('/\$params\[[\'"](\w+)[\'"]\]/i', $content, $matches)) {
        foreach ($matches[1] as $param) {
            if (!in_array($param, $params) && !in_array($param, ['_id', 'limit', 'page', 'pageSize'])) {
                $params[] = $param;
            }
        }
    }

    return array_unique($params);
}

/**
 * 关键词映射匹配
 */
function matchKeywordMap($scriptName, $keyword)
{
    static $keywordMap = [
        '暂停' => ['paused'],
        '启用' => ['enabled'],
        '预算' => ['budget'],
        '关键词' => ['keyword'],
        '投放' => ['campaign', 'controller'],
        '目标' => ['target'],
        '否定' => ['negative'],
        '导出' => ['export'],
        '同步' => ['sync'],
        'ebay' => ['ebay'],
        '卖家' => ['seller'],
        '广告组' => ['adgroup'],
        '组合' => ['portfolio'],
        '创建' => ['create'],
        '删除' => ['del'],
        '修复' => ['fix'],
        '产品' => ['product'],
        '分类' => ['category', 'fill'],
        '迁移' => ['migration'],
        '归档' => ['archived'],
        'bid' => ['bid'],
        'campaign' => ['campaign'],
        'keyword' => ['keyword'],
        'target' => ['target'],
    ];

    $keyword = strtolower($keyword);
    $scriptName = strtolower($scriptName);

    foreach ($keywordMap as $key => $matches) {
        if ($keyword === $key || strpos($keyword, $key) !== false) {
            foreach ($matches as $m) {
                if (strpos($scriptName, $m) !== false) {
                    return true;
                }
            }
        }
    }

    return false;
}
