<?php
/**
 * 脚本参数元数据定义
 *
 * 只定义有参数的脚本。无参数脚本（硬编码入口）不需要定义。
 *
 * 每个脚本的结构：
 *   'script_name' => [
 *       'methods' => [
 *           'default' => [
 *               'label' => '方法中文描述',
 *               'params' => [
 *                   'paramName' => [
 *                       'label'    => '参数中文标签',
 *                       'type'     => 'select|number|string|boolean',
 *                       'required' => true|false,
 *                       'default'  => 默认值（可选）,
 *                       'options'  => ['value' => '中文描述', ...],  // select 类型
 *                       'hint'     => '输入提示',  // 可选
 *                   ],
 *               ],
 *           ],
 *       ],
 *   ],
 *
 * 对于有 method 参数分派不同操作的脚本，定义多个方法组。
 */

return [

    // ===== SP Keyword =====
    'sp.keyword.paused_keyword' => [
        'methods' => [
            'default' => [
                'label' => '暂停关键词',
                'params' => [
                    'channel' => [
                        'label' => '渠道',
                        'type' => 'select',
                        'required' => true,
                        'options' => [
                            'amazon_us' => '美国站',
                            'amazon_uk' => '英国站',
                            'amazon_ca' => '加拿大站',
                            'amazon_de' => '德国站',
                            'amazon_fr' => '法国站',
                            'amazon_it' => '意大利站',
                            'amazon_es' => '西班牙站',
                            'amazon_jp' => '日本站',
                        ],
                    ],
                    'page' => [
                        'label' => '页码',
                        'type' => 'number',
                        'required' => false,
                        'default' => 0,
                        'hint' => 'Excel文件的序号，从1开始',
                    ],
                ],
            ],
            'v2' => [
                'label' => '暂停关键词V2(按Excel文件)',
                'params' => [
                    'file' => [
                        'label' => 'Excel文件',
                        'type' => 'string',
                        'required' => true,
                        'hint' => 'excel/目录下的文件名，如"暂停投放清单.xlsx"',
                    ],
                    'channel' => [
                        'label' => '渠道',
                        'type' => 'select',
                        'required' => false,
                        'default' => '',
                        'options' => [
                            'amazon_us' => '美国站',
                            'amazon_uk' => '英国站',
                            'amazon_ca' => '加拿大站',
                        ],
                    ],
                ],
            ],
            'verify' => [
                'label' => '校验关键词暂停状态',
                'params' => [
                    'file' => [
                        'label' => 'Excel文件',
                        'type' => 'string',
                        'required' => true,
                        'hint' => 'excel/目录下的文件名',
                    ],
                    'channel' => [
                        'label' => '渠道',
                        'type' => 'select',
                        'required' => false,
                        'default' => '',
                        'options' => [
                            'amazon_us' => '美国站',
                            'amazon_uk' => '英国站',
                            'amazon_ca' => '加拿大站',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'sp.keyword.update_keyword_bid' => [
        'methods' => [
            'default' => [
                'label' => '调整关键词bid',
                'params' => [
                    'channel' => [
                        'label' => '渠道',
                        'type' => 'select',
                        'required' => true,
                        'options' => [
                            'amazon_us' => '美国站',
                            'amazon_uk' => '英国站',
                            'amazon_ca' => '加拿大站',
                        ],
                    ],
                    'page' => [
                        'label' => '页码',
                        'type' => 'number',
                        'required' => false,
                        'default' => 0,
                        'hint' => 'Excel文件的序号，从1开始',
                    ],
                ],
            ],
            'v2' => [
                'label' => '调整关键词bid V2(按Excel文件)',
                'params' => [
                    'file' => [
                        'label' => 'Excel文件',
                        'type' => 'string',
                        'required' => true,
                        'hint' => 'excel/目录下的文件名，如"降bid清单.xlsx"',
                    ],
                    'channel' => [
                        'label' => '渠道',
                        'type' => 'select',
                        'required' => false,
                        'default' => '',
                        'options' => [
                            'amazon_us' => '美国站',
                            'amazon_uk' => '英国站',
                            'amazon_ca' => '加拿大站',
                        ],
                    ],
                ],
            ],
            'verify' => [
                'label' => '校验关键词bid调整状态',
                'params' => [
                    'file' => [
                        'label' => 'Excel文件',
                        'type' => 'string',
                        'required' => true,
                        'hint' => 'excel/目录下的文件名',
                    ],
                    'channel' => [
                        'label' => '渠道',
                        'type' => 'select',
                        'required' => false,
                        'default' => '',
                        'options' => [
                            'amazon_us' => '美国站',
                            'amazon_uk' => '英国站',
                            'amazon_ca' => '加拿大站',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'sp.keyword.enabled_keyword' => [
        'methods' => [
            'default' => [
                'label' => '重新投放关键词',
                'params' => [
                    'channel' => [
                        'label' => '渠道',
                        'type' => 'select',
                        'required' => true,
                        'options' => [
                            'amazon_us' => '美国站',
                            'amazon_uk' => '英国站',
                            'amazon_ca' => '加拿大站',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'sp.keyword.create_keyword' => [
        'methods' => [
            'default' => [
                'label' => '创建关键词',
                'params' => [
                    'file' => [
                        'label' => 'Excel文件',
                        'type' => 'string',
                        'required' => true,
                        'hint' => 'excel/目录下的文件名',
                    ],
                    'channel' => [
                        'label' => '渠道',
                        'type' => 'select',
                        'required' => false,
                        'default' => '',
                        'options' => [
                            'amazon_us' => '美国站',
                            'amazon_uk' => '英国站',
                            'amazon_ca' => '加拿大站',
                        ],
                    ],
                ],
            ],
        ],
    ],

    // ===== SP Campaign =====
    'sp.campaign.update_campaign_budget' => [
        'methods' => [
            'default' => [
                'label' => '更新campaign预算',
                'params' => [
                    'channel' => [
                        'label' => '渠道',
                        'type' => 'select',
                        'required' => true,
                        'options' => [
                            'amazon_us' => '美国站',
                            'amazon_uk' => '英国站',
                            'amazon_ca' => '加拿大站',
                        ],
                    ],
                    'page' => [
                        'label' => '页码',
                        'type' => 'number',
                        'required' => false,
                        'default' => 0,
                        'hint' => 'Excel文件的序号',
                    ],
                    'file' => [
                        'label' => 'Excel文件',
                        'type' => 'string',
                        'required' => false,
                        'default' => '',
                        'hint' => 'excel/目录下的文件名，不填则自动查找',
                    ],
                    'dry_run' => [
                        'label' => '预览模式',
                        'type' => 'boolean',
                        'required' => false,
                        'default' => false,
                        'hint' => 'true=只预览不执行，false=实际执行',
                    ],
                ],
            ],
        ],
    ],

    // ===== SP Target =====
    'sp.target.paused_target' => [
        'methods' => [
            'default' => [
                'label' => '暂停投放目标',
                'params' => [
                    'channel' => [
                        'label' => '渠道',
                        'type' => 'select',
                        'required' => true,
                        'options' => [
                            'amazon_us' => '美国站',
                            'amazon_uk' => '英国站',
                            'amazon_ca' => '加拿大站',
                        ],
                    ],
                    'page' => [
                        'label' => '页码',
                        'type' => 'number',
                        'required' => false,
                        'default' => 0,
                    ],
                ],
            ],
        ],
    ],

    'sp.target.update_target_bid' => [
        'methods' => [
            'default' => [
                'label' => '调整目标bid',
                'params' => [
                    'channel' => [
                        'label' => '渠道',
                        'type' => 'select',
                        'required' => true,
                        'options' => [
                            'amazon_us' => '美国站',
                            'amazon_uk' => '英国站',
                            'amazon_ca' => '加拿大站',
                        ],
                    ],
                    'page' => [
                        'label' => '页码',
                        'type' => 'number',
                        'required' => false,
                        'default' => 0,
                    ],
                ],
            ],
        ],
    ],

    'sp.target.create_target' => [
        'methods' => [
            'default' => [
                'label' => '创建投放目标',
                'params' => [
                    'file' => [
                        'label' => 'Excel文件',
                        'type' => 'string',
                        'required' => true,
                        'hint' => 'excel/目录下的文件名',
                    ],
                    'channel' => [
                        'label' => '渠道',
                        'type' => 'select',
                        'required' => false,
                        'default' => '',
                        'options' => [
                            'amazon_us' => '美国站',
                            'amazon_uk' => '英国站',
                            'amazon_ca' => '加拿大站',
                        ],
                    ],
                ],
            ],
        ],
    ],

    // ===== SP Negative Keyword =====
    'sp.negativekeyword.create_negative_keyword' => [
        'methods' => [
            'default' => [
                'label' => '创建否定关键词',
                'params' => [
                    'file' => [
                        'label' => 'Excel文件',
                        'type' => 'string',
                        'required' => true,
                        'hint' => 'excel/目录下的文件名',
                    ],
                    'channel' => [
                        'label' => '渠道',
                        'type' => 'select',
                        'required' => false,
                        'default' => '',
                        'options' => [
                            'amazon_us' => '美国站',
                            'amazon_uk' => '英国站',
                            'amazon_ca' => '加拿大站',
                        ],
                    ],
                ],
            ],
        ],
    ],

    // ===== SP Negative Target =====
    'sp.negativetarget.create_negative_target' => [
        'methods' => [
            'default' => [
                'label' => '创建否定目标',
                'params' => [
                    'file' => [
                        'label' => 'Excel文件',
                        'type' => 'string',
                        'required' => true,
                        'hint' => 'excel/目录下的文件名',
                    ],
                    'channel' => [
                        'label' => '渠道',
                        'type' => 'select',
                        'required' => false,
                        'default' => '',
                        'options' => [
                            'amazon_us' => '美国站',
                            'amazon_uk' => '英国站',
                            'amazon_ca' => '加拿大站',
                        ],
                    ],
                ],
            ],
        ],
    ],

    // ===== SP Common =====
    'sp.common.enabled_nkeyword_and_target_by_ad_group' => [
        'methods' => [
            'default' => [
                'label' => '按广告组启用否定关键词和目标',
                'params' => [
                    'channel' => [
                        'label' => '渠道',
                        'type' => 'select',
                        'required' => true,
                        'options' => [
                            'amazon_us' => '美国站',
                            'amazon_uk' => '英国站',
                            'amazon_ca' => '加拿大站',
                        ],
                    ],
                    'page' => [
                        'label' => '页码',
                        'type' => 'number',
                        'required' => false,
                        'default' => 0,
                    ],
                    'ad_type' => [
                        'label' => '广告类型',
                        'type' => 'select',
                        'required' => true,
                        'options' => [
                            'keyword' => '关键词广告',
                            'asin' => 'ASIN广告',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'sp.common.enabled_campaign' => [
        'methods' => [
            'default' => [
                'label' => '启用campaign',
                'params' => [
                    'channel' => [
                        'label' => '渠道',
                        'type' => 'select',
                        'required' => true,
                        'options' => [
                            'amazon_us' => '美国站',
                            'amazon_uk' => '英国站',
                            'amazon_ca' => '加拿大站',
                        ],
                    ],
                ],
            ],
        ],
    ],

    // ===== SP Ad Group =====
    'sp.adgroup.update_ad_group' => [
        'methods' => [
            'default' => [
                'label' => '更新广告组',
                'params' => [
                    'sellerId' => [
                        'label' => '卖家ID',
                        'type' => 'string',
                        'required' => true,
                        'hint' => '如 amazon_us_ac1, amazon_uk_hope',
                    ],
                ],
            ],
        ],
    ],

    'sp.adgroup.archived_error_ad_group' => [
        'methods' => [
            'default' => [
                'label' => '归档错误的广告组',
                'params' => [
                    'channel' => [
                        'label' => '渠道',
                        'type' => 'select',
                        'required' => false,
                        'default' => '',
                        'options' => [
                            'amazon_us' => '美国站',
                            'amazon_uk' => '英国站',
                            'amazon_ca' => '加拿大站',
                        ],
                    ],
                ],
            ],
        ],
    ],

    // ===== SP Product =====
    'sp.product.paused_product' => [
        'methods' => [
            'default' => [
                'label' => '暂停产品广告',
                'params' => [
                    'channel' => [
                        'label' => '渠道',
                        'type' => 'select',
                        'required' => true,
                        'options' => [
                            'amazon_us' => '美国站',
                            'amazon_uk' => '英国站',
                            'amazon_ca' => '加拿大站',
                        ],
                    ],
                    'page' => [
                        'label' => '页码',
                        'type' => 'number',
                        'required' => false,
                        'default' => 0,
                    ],
                    'file' => [
                        'label' => 'Excel文件',
                        'type' => 'string',
                        'required' => false,
                        'default' => '',
                    ],
                ],
            ],
            'v2' => [
                'label' => '暂停产品广告V2(按Excel文件)',
                'params' => [
                    'file' => [
                        'label' => 'Excel文件',
                        'type' => 'string',
                        'required' => true,
                        'hint' => 'excel/目录下的文件名',
                    ],
                    'channel' => [
                        'label' => '渠道',
                        'type' => 'select',
                        'required' => false,
                        'default' => '',
                        'options' => [
                            'amazon_us' => '美国站',
                            'amazon_uk' => '英国站',
                            'amazon_ca' => '加拿大站',
                        ],
                    ],
                ],
            ],
            'verify' => [
                'label' => '校验产品暂停状态',
                'params' => [
                    'file' => [
                        'label' => 'Excel文件',
                        'type' => 'string',
                        'required' => true,
                    ],
                    'channel' => [
                        'label' => '渠道',
                        'type' => 'select',
                        'required' => false,
                        'default' => '',
                        'options' => [
                            'amazon_us' => '美国站',
                            'amazon_uk' => '英国站',
                            'amazon_ca' => '加拿大站',
                        ],
                    ],
                ],
            ],
        ],
    ],

    // ===== SP Portfolios =====
    'sp.portfolios.check_portfolio_state' => [
        'methods' => [
            'default' => [
                'label' => '检查组合状态',
                'params' => [
                    'action' => [
                        'label' => '操作',
                        'type' => 'select',
                        'required' => false,
                        'default' => 'check',
                        'options' => [
                            'check' => '检查状态',
                            'fix' => '修复策略',
                        ],
                    ],
                ],
            ],
        ],
    ],

    // ===== eBay =====
    'ebay.execute_ebay_seller_allocation' => [
        'methods' => [
            'default' => [
                'label' => '执行eBay卖家分配',
                'params' => [
                    'env' => [
                        'label' => '环境',
                        'type' => 'select',
                        'required' => false,
                        'default' => 'pro',
                        'options' => [
                            'pro' => '生产环境',
                            'test' => '测试环境',
                            'uat' => 'UAT环境',
                        ],
                    ],
                    'input' => [
                        'label' => '输入文件',
                        'type' => 'string',
                        'required' => false,
                        'default' => 'ebay_seller_allocation.csv',
                        'hint' => 'export/目录下的CSV文件名',
                    ],
                    'output' => [
                        'label' => '输出文件',
                        'type' => 'string',
                        'required' => false,
                        'default' => 'ebay_seller_allocation.result.json',
                        'hint' => 'export/目录下的JSON文件名',
                    ],
                ],
            ],
        ],
    ],

    'ebay.fill_seller_allocation_category_config' => [
        'methods' => [
            'default' => [
                'label' => '填充卖家分配分类配置',
                'params' => [
                    'env' => [
                        'label' => '环境',
                        'type' => 'select',
                        'required' => false,
                        'default' => 'pro',
                        'options' => [
                            'pro' => '生产环境',
                            'test' => '测试环境',
                        ],
                    ],
                    'input' => [
                        'label' => '输入文件',
                        'type' => 'string',
                        'required' => false,
                        'default' => 'sellerAllocationConfig.json',
                        'hint' => 'export/目录下的JSON文件名',
                    ],
                    'output' => [
                        'label' => '输出文件',
                        'type' => 'string',
                        'required' => false,
                        'default' => 'sellerAllocationConfig.completed.json',
                        'hint' => 'export/目录下的JSON文件名',
                    ],
                ],
            ],
        ],
    ],

    // ===== Sync =====
    'sync.sync_product_sku' => [
        'methods' => [
            'default' => [
                'label' => '同步产品SKU',
                'params' => [
                    'skuIdList' => [
                        'label' => 'SKU ID列表',
                        'type' => 'string',
                        'required' => false,
                        'default' => '',
                        'hint' => '逗号分隔的SKU ID，不填则处理全部',
                    ],
                ],
            ],
        ],
    ],

];
