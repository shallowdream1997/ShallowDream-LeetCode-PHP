CREATE TABLE pa_walmart_weekly_data
(
    channel VARCHAR(225) NULL COMMENT '销售渠道',
    sellerId VARCHAR(225) NULL COMMENT '销售账号',
    scuId VARCHAR(225) NULL COMMENT 'scuId(上架编号)',
    skuId VARCHAR(225) NULL COMMENT 'skuId',
    cnCategory VARCHAR(225) NULL COMMENT '中文分类全路径',
    catLevel1 VARCHAR(225) NULL COMMENT '一级中文分类',
    catLevel2 VARCHAR(225) NULL COMMENT '二级中文分类',
    leafCategoryId VARCHAR(225) NULL COMMENT '末级分类ID',
    developerUserName VARCHAR(225) NULL COMMENT '开发人员',
    salesUserName VARCHAR(225) NULL COMMENT '销售人员',
    SZ_stockQty INT NULL COMMENT 'SZ仓在仓库存',
    SZ_onwayQty INT NULL COMMENT 'SZ仓补货在途数',
    US_04_stockQty INT NULL COMMENT 'US_04仓在仓库存',
    US_04_moveOnwayQty INT NULL COMMENT 'US_04仓移库在途数',
    US_WM1_stockQty INT NULL COMMENT 'US_WM1仓在仓库存',
    US_WM1_moveOnwayQty INT NULL COMMENT 'US_WM1仓移库在途数',
    W1_sold INT NULL COMMENT '历史第1周sold',
    W1_sales INT NULL COMMENT '历史第1周sales',
    W1_PL INT NULL COMMENT '历史第1周PL',
    W1_margin INT NULL COMMENT 'W1 Margin',
    W1_eff INT NULL COMMENT 'W1 销售效率',
    W2_sold INT NULL COMMENT '历史第2周sold',
    W2_sales INT NULL COMMENT '历史第2周sales',
    W2_margin INT NULL COMMENT 'W2 Margin',
    W2_PL INT NULL COMMENT '历史第2周PL',
    W2_eff INT NULL COMMENT 'W2 销售效率',
    W3_sold INT NULL COMMENT '历史第3周sold',
    W3_sales INT NULL COMMENT '历史第3周sales',
    W3_margin INT NULL COMMENT 'W3 Margin',
    W3_PL INT NULL COMMENT '历史第3周PL',
    W3_eff INT NULL COMMENT 'W3 销售效率',
    W4_sold INT NULL COMMENT '历史第4周sold',
    W4_sales INT NULL COMMENT '历史第4周sales',
    W4_margin INT NULL COMMENT 'W4 Margin',
    W4_PL INT NULL COMMENT '历史第4周PL',
    W4_eff INT NULL COMMENT 'W4 销售效率',
    W5_sold INT NULL COMMENT '历史第5周sold',
    W5_sales INT NULL COMMENT '历史第5周sales',
    W5_margin INT NULL COMMENT 'W5 Margin',
    W5_PL INT NULL COMMENT '历史第5周PL',
    W5_eff INT NULL COMMENT 'W5 销售效率',
    diffSold INT NULL COMMENT 'W1较W2的sold变化',
    diffSales INT NULL COMMENT 'W1较W2的sales变化',
    diffMargin INT NULL COMMENT 'W1较W2的Margin变化',
    diffPL INT NULL COMMENT 'W1较W2的PL变化',
    diffEff INT NULL COMMENT 'W1较W2的Eff变化',
    isOOS VARCHAR(225) NULL COMMENT '是否OOS：Yes、No',
    createdBy VARCHAR(225) NULL COMMENT '创建人',
    createdOn date NULL COMMENT '创建时间',
    modifiedBy VARCHAR(225) NULL COMMENT '修改人',
    modifiedOn date NULL COMMENT '修改时间'
) ENGINE = InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE pa_noconsignment_to_consignment_list
(
    cenumber varchar(255) NULL COMMENT 'ce单号',
    monthnumber INT NULL COMMENT '手动月数',
    business_type varchar(255) NULL COMMENT '业务类型',
    supplier varchar(255) NULL COMMENT '供应商',
    salesusername varchar(255) NULL COMMENT '销售',
    developer varchar(255) NULL COMMENT '开发者',
    category_full_path varchar(255) NULL COMMENT '中文分类',
    sold INT NULL COMMENT 'CE单平均销量',
    skunumber INT NULL COMMENT 'CE单内sku数量',
    eff INT NULL COMMENT '销售效率',
    pl INT NULL COMMENT 'pl',
    cost INT NULL COMMENT '成本',
    cogs INT NULL COMMENT 'cogs',
    sales INT NULL COMMENT '销售额',
    margin INT NULL COMMENT '毛利率',
    createdBy VARCHAR(225) NULL COMMENT '创建人',
    createdOn datetime NULL COMMENT '创建时间',
    modifiedBy VARCHAR(225) NULL COMMENT '修改人',
    modifiedOn datetime NULL COMMENT '修改时间'
)ENGINE = InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE amazon_EU_non_Registered_monitor
(
    skuid           VARCHAR(255) NULL COMMENT 'skuid',
    deasinnonfba    VARCHAR(255) NULL COMMENT '德国nonfba的asin号',
    frasinnonfba    VARCHAR(255) NULL COMMENT '法国nonfba的asin号',
    esasinnonfba    VARCHAR(255) NULL COMMENT '西班牙nonfba的asin号',
    itasinnonfba    VARCHAR(255) NULL COMMENT '意大利nonfba的asin号',
    seller_sku      VARCHAR(255) NULL COMMENT '销售号',
    de_asin         VARCHAR(255) NULL COMMENT '德国asin',
    fr_asin         VARCHAR(255) NULL COMMENT '法国asin',
    es_asin         VARCHAR(255) NULL COMMENT '西班牙asin',
    it_asin         VARCHAR(255) NULL COMMENT '意大利asin',
    de_sales_status VARCHAR(255) NULL COMMENT '德国可售状态',
    fr_sales_status VARCHAR(255) NULL COMMENT '法国可售状态',
    es_sales_status VARCHAR(255) NULL COMMENT '西班牙可售状态',
    it_sales_status VARCHAR(255) NULL COMMENT '意大利可售状态',
    destockslot     VARCHAR(255) NULL COMMENT '德国库位',
    frstockslot     VARCHAR(255) NULL COMMENT '法国库位',
    esstockslot     VARCHAR(255) NULL COMMENT '西班牙库位',
    itstockslot     VARCHAR(255) NULL COMMENT '意大利库位',
    de_inventory    INT NULL COMMENT '德国库存',
    fr_inventory    INT NULL COMMENT '法国库存',
    es_inventory    INT NULL COMMENT '西班牙库存',
    it_inventory    INT NULL COMMENT '意大利库存',
    pl_inventory    INT NULL COMMENT '波兰库存',
    pan_eu_status   VARCHAR(255) NULL COMMENT 'paneu状态',
    tag             VARCHAR(255) NULL COMMENT '分组标签',
    salesjudge      VARCHAR(255) NULL COMMENT '可售状态判断',
    scuidjudge      VARCHAR(255) NULL COMMENT 'fbascu号判断',
    createdBy       VARCHAR(225) NULL COMMENT '创建人',
    createdOn       datetime NULL COMMENT '创建时间',
    modifiedBy      VARCHAR(225) NULL COMMENT '修改人',
    modifiedOn      datetime NULL COMMENT '修改时间'
)ENGINE = InnoDB DEFAULT CHARSET = utf8;


CREATE TABLE pa_consignor_order_receiving_participation
(
    year                 VARCHAR(255) NULL COMMENT '年份',
    supplierId           INT NULL COMMENT '供应商id',
    supplierName         VARCHAR(255) NULL COMMENT '供应商名称',
    draftDate            VARCHAR(255) null COMMENT '发布日期',
    bidNumber            INT NULL COMMENT '投标总数',
    bidSkuNumber         INT NULL COMMENT '投标sku数',
    bidMoney             INT NULL COMMENT '投标总金额',
    bidQD                VARCHAR(255) NULL COMMENT 'qd单号',
    bidQdAndProductLine  VARCHAR(255) NULL COMMENT 'qd单号和产品线',
    bidProductLine       VARCHAR(255) NULL COMMENT '产品线',
    wbidNumber           INT NULL COMMENT '中标总数',
    wbidSkuNumber        INT NULL COMMENT '中标sku数',
    wbidMoney            INT NULL COMMENT '中标总金额',
    wbidPercent          INT NULL COMMENT '中标率',
    wbidQD               VARCHAR(255) NULL COMMENT '中标qd单号',
    wbidQdAndProductLine VARCHAR(255) NULL COMMENT '中标qd单号和产品线',
    wbidProductLine      VARCHAR(255) NULL COMMENT '中标产品线',
    createdBy            VARCHAR(225) NULL COMMENT '创建人',
    createdOn            datetime NULL COMMENT '创建时间',
    modifiedBy           VARCHAR(225) NULL COMMENT '修改人',
    modifiedOn           datetime NULL COMMENT '修改时间'
)ENGINE = InnoDB DEFAULT CHARSET = utf8;

CREATE TABLE amazon_us_nonfba_stock_settings
(
    firstmovebillno          VARCHAR(255) NULL COMMENT '首次移库US_01仓的移库单号',
    outsuredate              datetime NULL COMMENT '首次移库US_01仓的出库时间',
    fbastatus2               VARCHAR(255) NULL COMMENT 'FBA上架申请状态',
    modifiedby               VARCHAR(255) NULL COMMENT '修改人',
    salestatus               VARCHAR(255) NULL COMMENT 'Amazon_us渠道的可售状态',
    applyon                  datetime NULL COMMENT '上架前FBA申请时间',
    stock1                   VARCHAR(255) NULL COMMENT 'Amazon_us渠道nonfba发货仓',
    skuid                    VARCHAR(255) NULL COMMENT 'skuid',
    businesstype             VARCHAR(255) NULL COMMENT '业务类型',
    applyby                  VARCHAR(255) NULL COMMENT '上架前FBA申请人',
    dcid                     VARCHAR(255) NULL COMMENT 'FBA发货仓',
    checkusername            VARCHAR(255) NULL COMMENT '审核人',
    fbastatus1               VARCHAR(255) NULL COMMENT '上架前FBA申请状态',
    createdby                VARCHAR(255) NULL COMMENT '创建人',
    channel                  VARCHAR(255) NULL COMMENT 'FBA申请渠道',
    lastmodifiedon           VARCHAR(255) NULL COMMENT 'nonfba发货仓最后修改时间',
    createdon                datetime NULL COMMENT '创建时间',
    expectabroadoutbounddate datetime NULL COMMENT '首次移库US_01仓的预计入库时间',
    modifiedon               datetime NULL COMMENT '修改时间',
    moveindate               datetime NULL COMMENT '首次移库US_01仓的实际入库时间',
    lastmodifiedby           VARCHAR(255) NULL COMMENT 'nonfba发货仓最后修改人',
    reviewtime               datetime NULL COMMENT '审核完成时间',
    salesusername            VARCHAR(255) NULL COMMENT '销售人员',
    developerusername        VARCHAR(255) NULL COMMENT '开发人员',
    status                   VARCHAR(255) NULL COMMENT '资料状态',
    ismoveinus01             VARCHAR(255) NULL COMMENT 'US_01仓是否已入库'
)ENGINE = InnoDB DEFAULT CHARSET = utf8;


CREATE TABLE amazon_us_nonfba_stock_settings_operation
(
    skuId          VARCHAR(255) NULL COMMENT 'skuid',
    executeStatus1 VARCHAR(255) NULL COMMENT '上架前nonfba发货仓设置脚本执行状态	0-未执行；1-执行中；2-执行完成；3-执行失败',
    execute1On     VARCHAR(255) NULL COMMENT '上架前nonfba发货仓设置脚本执行时间',
    executeStatus2 VARCHAR(255) NULL COMMENT '入库后nonfba发货仓设置脚本执行状态	0-未执行；1-执行中；2-执行完成；3-执行失败',
    execute2On     VARCHAR(255) NULL COMMENT '入库后nonfba发货仓设置脚本执行时间',
    createdBy      VARCHAR(225) NULL COMMENT '创建人',
    createdOn      datetime NULL COMMENT '创建时间',
    modifiedBy     VARCHAR(225) NULL COMMENT '修改人',
    modifiedOn     datetime NULL COMMENT '修改时间'
)ENGINE = InnoDB DEFAULT CHARSET = utf8;

CREATE TABLE pa_new_product_process_monitoring
(
    ce_billno            VARCHAR(255) NULL COMMENT 'CE单号',
    ce_created_on        VARCHAR(255) NULL COMMENT 'CE创建时间',
    checkby              VARCHAR(255) NULL COMMENT '外观质检人',
    checkon              DATETIME NULL COMMENT '外观质检时间',
    createdon            DATETIME NULL COMMENT '深圳发货时间',
    developerusername    VARCHAR(255) NULL COMMENT '开发',
    firstveromodifiedby  VARCHAR(255) NULL COMMENT '首次侵权检查人',
    firstveromodifiedon  DATETIME NULL COMMENT '首次侵权检查时间',
    infomodifiedby       VARCHAR(255) NULL COMMENT '资料完成人',
    infomodifiedon       DATETIME NULL COMMENT '资料完成时间',
    inforeviewmodifiedby VARCHAR(255) NULL COMMENT '资料审核人',
    inforeviewmodifiedon DATETIME NULL COMMENT '资料审核时间',
    iscegoods            VARCHAR(255) NULL COMMENT '是否分批次来货',
    isreject             VARCHAR(255) NULL COMMENT '是否为不良品',
    managermodifiedby    VARCHAR(255) NULL COMMENT '经理审核人',
    managermodifiedon    DATETIME NULL COMMENT '经理审核时间',
    picmodifiedby        VARCHAR(255) NULL COMMENT '图片制作人',
    picmodifiedon        DATETIME NULL COMMENT '图片制作时间',
    picreviewingname     VARCHAR(255) NULL COMMENT '图片审核',
    picreviewmodifiedby  VARCHAR(255) NULL COMMENT '图片审核人',
    picreviewmodifiedon  DATETIME NULL COMMENT '图片审核时间',
    productid            VARCHAR(255) NULL COMMENT '产品id',
    publishby            VARCHAR(255) NULL COMMENT '资料呈现人',
    publishon            DATETIME NULL COMMENT '资料呈现时间',
    receivinggzoby       VARCHAR(255) NULL COMMENT '广州收货人',
    receivinggzon        DATETIME NULL COMMENT '广州收货时间',
    receivingszoby       VARCHAR(255) NULL COMMENT '深圳收货人',
    receivingszon        VARCHAR(255) NULL COMMENT '深圳收货时间',
    salestatus           VARCHAR(255) NULL COMMENT '可售状态',
    salesusername        VARCHAR(255) NULL COMMENT '销售',
    secondveromodifiedby VARCHAR(255) NULL COMMENT '二次侵权检查人',
    secondveromodifiedon DATETIME NULL COMMENT '二次侵权检查时间',
    skubusinesstype      VARCHAR(255) NULL COMMENT 'sku业务类型',
    status               VARCHAR(255) NULL COMMENT 'sku状态',
    statuslevel          VARCHAR(255) NULL COMMENT '状态等级',
    supplier_cnname      VARCHAR(255) NULL COMMENT '供应商中文名',
    supplier_id          INT NULL COMMENT '供应商id',
    winbidon             DATETIME NULL COMMENT '中标日期'
)ENGINE = InnoDB DEFAULT CHARSET = utf8;

CREATE TABLE minor_lang_weekly_data
(
    the_year VARCHAR(255) NULL COMMENT '年份',
    timedimension VARCHAR(255) NULL COMMENT '按时间查询的维度',
    timeInterval VARCHAR(255) NULL COMMENT '时间区间',
    platform VARCHAR(255) NULL COMMENT '平台',
    channel VARCHAR(255) NULL COMMENT '渠道',
    vertical VARCHAR(255) NULL COMMENT '垂直',
    topCategory VARCHAR(255) NULL COMMENT '一级中文分类',
    topCategoryid VARCHAR(255) NULL COMMENT '一级中文分类ID',
    isFba VARCHAR(255) NULL COMMENT '卖法(fba或nonFba)',
    startDate DATETIME NULL COMMENT '时间区间对应的开始时间',
    endDate DATETIME NULL COMMENT '时间区间对应的结束时间',
    sold INT NULL COMMENT 'sold',
    grossRevenue INT NULL COMMENT 'GR(就是sales)',
    pl1 INT NULL COMMENT '总PL',
    pl2 INT NULL COMMENT 'pl后求和',
    translationfee INT NULL COMMENT '翻译费',
    margin1 INT NULL COMMENT 'margin1',
    margin2 INT NULL COMMENT 'margin2',
    normalASP INT NULL COMMENT 'normal订单的ASP(normalgrossrevenue/normalsold)',
    normalgrossrevenue INT NULL COMMENT 'normal订单的GR',
    normalsold INT NULL COMMENT 'normal订单的sold',
    createdon DATETIME NULL COMMENT '创建时间',
    createdby VARCHAR(255) NULL COMMENT '创建人',
    modifiedon DATETIME NULL COMMENT '修改时间',
    modifiedby VARCHAR(255) NULL COMMENT '修改人'
)ENGINE = InnoDB DEFAULT CHARSET = utf8;


CREATE TABLE pa_sku_life_cycle_management_collect
(
    getinfoon     DATETIME NULL COMMENT '最新数据',
    weekday       VARCHAR(255) NULL COMMENT '周几',
    salesusername VARCHAR(255) NULL COMMENT '销售',
    usergroup     VARCHAR(255) NULL COMMENT '组别',
    userposition  VARCHAR(255) NULL COMMENT '岗位',
    groupleader   VARCHAR(255) NULL COMMENT '组长',
    qty_all       INT NULL COMMENT 'sku总数',
    qty_period1   INT NULL COMMENT '培养期sku总数',
    qty_period2   INT NULL COMMENT '成长期sku总数',
    qty_period3   INT NULL COMMENT '成熟期sku总数',
    qty_period4   INT NULL COMMENT '衰退期sku总数',
    qty_period5   INT NULL COMMENT '淘汰期sku总数',
    qty_improved1 INT NULL COMMENT '培养期不需要优化的sku总数',
    qty_improved2 INT NULL COMMENT '成长期不需要优化的sku总数',
    qty_improved3 INT NULL COMMENT '成熟期不需要优化的sku总数',
    qty_improved4 INT NULL COMMENT '衰退期不需要优化的sku总数',
    qty_improved5 INT NULL COMMENT '淘汰期不需要优化的sku总数',
    rate1         INT NULL COMMENT '培养期达成率',
    rate2         INT NULL COMMENT '成长期达成率',
    rate3         INT NULL COMMENT '成熟期达成率',
    rate4         INT NULL COMMENT '衰退期达成率',
    rate5         INT NULL COMMENT '淘汰期达成率',
    sold_30days   INT NULL COMMENT '近30天Sold',
    sales_30days  INT NULL COMMENT '近30天Sales',
    pl_30days     INT NULL COMMENT '近30天PL',
    margin_30days INT NULL COMMENT '近30天Margin',
    createdby     VARCHAR(255) NULL COMMENT '创建者',
    createdon     DATETIME NULL COMMENT '创建日期',
    modifiedby    VARCHAR(255) NULL COMMENT '更新者',
    modifiedon    DATETIME NULL COMMENT '更新时间',
)ENGINE = InnoDB DEFAULT CHARSET = utf8;