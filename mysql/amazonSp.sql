CREATE TABLE amazon_sp_seller
(
    company    VARCHAR(255) NULL COMMENT '垂直ID',
    channel    VARCHAR(255) NULL COMMENT '渠道',
    sellerId   VARCHAR(255) NULL COMMENT '账号',
    brand      VARCHAR(255) NULL COMMENT '品牌',
    bindRule   VARCHAR(255) NULL COMMENT '绑定的规则',

    modifiedBy VARCHAR(255) NULL COMMENT '修改人',
    modifiedOn DATETIME NULL COMMENT '修改日期',
    createdOn  DATETIME NULL COMMENT '创建人',
    createdBy  VARCHAR(255) NULL COMMENT '创建日期'
)ENGINE = InnoDB DEFAULT CHARSET = utf8;

--版本A
CREATE TABLE amazon_sp_rule_config
(
    ruleId        VARCHAR(255) NULL COMMENT '广告规则ID',
    ruleType      VARCHAR(255) NULL COMMENT '广告规则类型-数组',
    ruleName      VARCHAR(255) NULL COMMENT '广告规则名称',
    ruleRegex     VARCHAR(255) NULL COMMENT '广告规则表达式',
    ruleFieldName VARCHAR(255) NULL COMMENT '广告规则参数名称-数组',
    modifiedBy    VARCHAR(255) NULL COMMENT '修改人',
    modifiedOn    DATETIME NULL COMMENT '修改日期',
    createdOn     DATETIME NULL COMMENT '创建人',
    createdBy     VARCHAR(255) NULL COMMENT '创建日期'
)ENGINE = InnoDB DEFAULT CHARSET = utf8;


CREATE TABLE amazon_sp_budget_and_bid_rule_config
(
    bidRuleId   VARCHAR(255) NULL COMMENT 'bid规则ID',
    bidType     VARCHAR(255) NULL COMMENT 'bid类型',
    dailyBudget INT NULL COMMENT 'campaign广告预算',
    maxBudget   INT NULL COMMENT '广告预算最大值',
    defaultBid  INT NULL COMMENT '默认bid值',
    minBid      INT NULL COMMENT 'bid最小值',
    maxBid      INT NULL COMMENT 'bid最大值',
    bidWarning  INT NULL COMMENT 'bid警告阀值',
    modifiedBy  VARCHAR(255) NULL COMMENT '修改人',
    modifiedOn  DATETIME NULL COMMENT '修改日期',
    createdOn   DATETIME NULL COMMENT '创建人',
    createdBy   VARCHAR(255) NULL COMMENT '创建日期'
)ENGINE = InnoDB DEFAULT CHARSET = utf8;