<?php
/**
 * AI 模型配置
 * 支持多模型切换：DeepSeek / GPT-4o / Claude
 * API Key 通过界面设置后存储在 Redis 中，不写入文件
 */

return [
    // 默认模型
    'default' => 'deepseek',

    // 模型配置
    'models' => [
        'deepseek' => [
            'name' => 'DeepSeek Chat',
            'api_url' => 'https://api.deepseek.com/v1/chat/completions',
            'api_key' => '',
            'model' => 'deepseek-chat',
            'max_tokens' => 4096,
            'temperature' => 0.3,
            'type' => 'openai',  // OpenAI 兼容协议
        ],
        'gpt4o' => [
            'name' => 'GPT-4o',
            'api_url' => 'https://api.openai.com/v1/chat/completions',
            'api_key' => '',
            'model' => 'gpt-4o',
            'max_tokens' => 4096,
            'temperature' => 0.3,
            'type' => 'openai',
        ],
        'gpt4o_mini' => [
            'name' => 'GPT-4o Mini',
            'api_url' => 'https://api.openai.com/v1/chat/completions',
            'api_key' => '',
            'model' => 'gpt-4o-mini',
            'max_tokens' => 4096,
            'temperature' => 0.3,
            'type' => 'openai',
        ],
        'claude' => [
            'name' => 'Claude Sonnet',
            'api_url' => 'https://api.anthropic.com/v1/messages',
            'api_key' => '',
            'model' => 'claude-sonnet-4-20250514',
            'max_tokens' => 4096,
            'temperature' => 0.3,
            'type' => 'anthropic',
        ],
    ],

    // Redis 存储前缀
    'config_redis_prefix' => 'pa_ai_config_',
    'model_redis_prefix' => 'pa_ai_model_',
    'mode_redis_prefix' => 'pa_ai_mode_',

    // 对话上下文
    'conversation_prefix' => 'pa_ai_conversation_',
    'conversation_ttl' => 3600,       // 1小时
    'conversation_max_messages' => 30, // 最多保留30条消息

    // Agent 配置
    'agent_max_rounds' => 5,          // 最大工具调用轮次
    'agent_timeout' => 120,           // 单次 API 调用超时(秒)
];
