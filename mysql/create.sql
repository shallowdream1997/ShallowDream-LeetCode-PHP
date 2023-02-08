CREATE TABLE pa_walmart_weekly_data
(
    channel             VARCHAR(225) NULL COMMENT '销售渠道',
    sellerId            VARCHAR(225) NULL COMMENT '销售账号',
    scuId               VARCHAR(225) NULL COMMENT 'scuId(上架编号)',
    skuId               VARCHAR(225) NULL COMMENT 'skuId',
    cnCategory          VARCHAR(225) NULL COMMENT '中文分类全路径',
    catLevel1           VARCHAR(225) NULL COMMENT '一级中文分类',
    catLevel2           VARCHAR(225) NULL COMMENT '二级中文分类',
    leafCategoryId      VARCHAR(225) NULL COMMENT '末级分类ID',
    developerUserName   VARCHAR(225) NULL COMMENT '开发人员',
    salesUserName       VARCHAR(225) NULL COMMENT '销售人员',
    SZ_stockQty         INT NULL COMMENT 'SZ仓在仓库存',
    SZ_onwayQty         INT NULL COMMENT 'SZ仓补货在途数',
    US_04_stockQty      INT NULL COMMENT 'US_04仓在仓库存',
    US_04_moveOnwayQty  INT NULL COMMENT 'US_04仓移库在途数',
    US_WM1_stockQty     INT NULL COMMENT 'US_WM1仓在仓库存',
    US_WM1_moveOnwayQty INT NULL COMMENT 'US_WM1仓移库在途数',
    W1_sold             INT NULL COMMENT '历史第1周sold',
    W1_sales            INT NULL COMMENT '历史第1周sales',
    W1_PL               INT NULL COMMENT '历史第1周PL',
    W1_margin           INT NULL COMMENT 'W1 Margin',
    W1_eff              INT NULL COMMENT 'W1 销售效率',
    W2_sold             INT NULL COMMENT '历史第2周sold',
    W2_sales            INT NULL COMMENT '历史第2周sales',
    W2_margin           INT NULL COMMENT 'W2 Margin',
    W2_PL               INT NULL COMMENT '历史第2周PL',
    W2_eff              INT NULL COMMENT 'W2 销售效率',
    W3_sold             INT NULL COMMENT '历史第3周sold',
    W3_sales            INT NULL COMMENT '历史第3周sales',
    W3_margin           INT NULL COMMENT 'W3 Margin',
    W3_PL               INT NULL COMMENT '历史第3周PL',
    W3_eff              INT NULL COMMENT 'W3 销售效率',
    W4_sold             INT NULL COMMENT '历史第4周sold',
    W4_sales            INT NULL COMMENT '历史第4周sales',
    W4_margin           INT NULL COMMENT 'W4 Margin',
    W4_PL               INT NULL COMMENT '历史第4周PL',
    W4_eff              INT NULL COMMENT 'W4 销售效率',
    W5_sold             INT NULL COMMENT '历史第5周sold',
    W5_sales            INT NULL COMMENT '历史第5周sales',
    W5_margin           INT NULL COMMENT 'W5 Margin',
    W5_PL               INT NULL COMMENT '历史第5周PL',
    W5_eff              INT NULL COMMENT 'W5 销售效率',
    diffSold            INT NULL COMMENT 'W1较W2的sold变化',
    diffSales           INT NULL COMMENT 'W1较W2的sales变化',
    diffMargin          INT NULL COMMENT 'W1较W2的Margin变化',
    diffPL              INT NULL COMMENT 'W1较W2的PL变化',
    diffEff             INT NULL COMMENT 'W1较W2的Eff变化',
    isOOS               VARCHAR(225) NULL COMMENT '是否OOS：Yes、No',
    createdBy           VARCHAR(225) NULL COMMENT '创建人',
    createdOn           date NULL COMMENT '创建时间',
    modifiedBy          VARCHAR(225) NULL COMMENT '修改人',
    modifiedOn          date NULL COMMENT '修改时间'
) ENGINE = InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE pa_noconsignment_to_consignment_list
(
    cenumber           varchar(255) NULL COMMENT 'ce单号',
    monthnumber        INT NULL COMMENT '手动月数',
    business_type      varchar(255) NULL COMMENT '业务类型',
    supplier           varchar(255) NULL COMMENT '供应商',
    salesusername      varchar(255) NULL COMMENT '销售',
    developer          varchar(255) NULL COMMENT '开发者',
    category_full_path varchar(255) NULL COMMENT '中文分类',
    sold               INT NULL COMMENT 'CE单平均销量',
    skunumber          INT NULL COMMENT 'CE单内sku数量',
    eff                INT NULL COMMENT '销售效率',
    pl                 INT NULL COMMENT 'pl',
    cost               INT NULL COMMENT '成本',
    cogs               INT NULL COMMENT 'cogs',
    sales              INT NULL COMMENT '销售额',
    margin             INT NULL COMMENT '毛利率',
    createdBy          VARCHAR(225) NULL COMMENT '创建人',
    createdOn          datetime NULL COMMENT '创建时间',
    modifiedBy         VARCHAR(225) NULL COMMENT '修改人',
    modifiedOn         datetime NULL COMMENT '修改时间'
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
    the_year                    VARCHAR(255) NULL COMMENT '年份',
    timedimension               VARCHAR(255) NULL COMMENT '按时间查询的维度',
    timeInterval                VARCHAR(255) NULL COMMENT '时间区间',
    platform                    VARCHAR(255) NULL COMMENT '平台',
    channel                     VARCHAR(255) NULL COMMENT '渠道',
    vertical                    VARCHAR(255) NULL COMMENT '垂直',
    topCategory                 VARCHAR(255) NULL COMMENT '一级中文分类',
    topCategoryid               VARCHAR(255) NULL COMMENT '一级中文分类ID',
    isFba                       VARCHAR(255) NULL COMMENT '卖法(fba或nonFba)',
    startDate                   DATETIME NULL COMMENT '时间区间对应的开始时间',
    endDate                     DATETIME NULL COMMENT '时间区间对应的结束时间',
    sold                        INT NULL COMMENT 'sold',
    grossRevenue                INT NULL COMMENT 'GR(就是sales)',
    pl1                         INT NULL COMMENT '总PL',
    pl2                         INT NULL COMMENT 'pl后求和',
    translationfee              INT NULL COMMENT '翻译费',
    margin1                     INT NULL COMMENT 'margin1',
    margin2                     INT NULL COMMENT 'margin2',
    normalASP                   INT NULL COMMENT 'normal订单的ASP(normalgrossrevenue/normalsold)',
    normalgrossrevenue          INT NULL COMMENT 'normal订单的GR',
    normalsold                  INT NULL COMMENT 'normal订单的sold',
    grossrevenuenew             INT NULL COMMENT '修复后的gr',
    plnew1                      INT NULL COMMENT '修复后的pl1',
    plnew2                      INT NULL COMMENT '修复后的pl2',
    normalgrossrevenuenew       INT NULL COMMENT '修复后的normal gr',
    localCurrency               VARCHAR(255) NULL COMMENT '当前币种',
    grossrevenue_local          INT NULL COMMENT '币种下的gr',
    pl1_local                   INT NULL COMMENT '币种下的pl1',
    pl2_local                   INT NULL COMMENT '币种下的pl2',
    translationfee_local        INT NULL COMMENT '币种下的翻译费',
    normalgrossrevenue_local    INT NULL COMMENT '币种下的normal gr',
    grossrevenuenew_local       INT NULL COMMENT '币种下的修复后的gr',
    plnew1_local                INT NULL COMMENT '币种下的修复后的pl1',
    plnew2_local                INT NULL COMMENT '币种下的修复后的pl2',
    normalgrossrevenuenew_local INT NULL COMMENT '币种下的修复后的normal gr',
    normalpl                    INT NULL COMMENT 'normal pl',
    normalplnew                 INT NULL COMMENT '修复gr后的normal',
    normalplnew_local           INT NULL COMMENT '当地币种的修复后的normalpl',
    normalpl_local              INT NULL COMMENT '当地币种的normalpl',
    shippingfee                 INT NULL COMMENT 'shipping费用',
    shippingfee_local           INT NULL COMMENT '当地币种shipping费用',
    ads                         INT NULL COMMENT '广告费',
    ads_local                   INT NULL COMMENT '当地币种广告费',
    refund                      INT NULL COMMENT '退款',
    refund_local                INT NULL COMMENT '当地币种退款',
    createdon                   DATETIME NULL COMMENT '创建时间',
    createdby                   VARCHAR(255) NULL COMMENT '创建人',
    modifiedon                  DATETIME NULL COMMENT '修改时间',
    modifiedby                  VARCHAR(255) NULL COMMENT '修改人'
)ENGINE = InnoDB DEFAULT CHARSET = utf8;


CREATE TABLE pa_sku_life_cycle_management_collect
(
    getinfoon     DATETIME NULL COMMENT '最新数据',
    getdate       DATETIME NULL COMMENT '最新日期',
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

CREATE TABLE pa_pp_funnel_analysis
(
    createdOn                 VARCHAR(255) NULL COMMENT '开发月份',
    developer                 VARCHAR(255) NULL COMMENT '开发人员',
    developSkuNum             INT NULL COMMENT '开发sku数量',
    productIn                 INT NULL COMMENT '新品导入',
    categoryCausedReject      INT NULL COMMENT '分类审核驳回',
    ipToBeChecked             INT NULL COMMENT '待ip检查',
    ipChecked                 INT NULL COMMENT 'ip检查完成',
    productDetailIn           INT NULL COMMENT '已导入产品详情',
    marketCausedReject        INT NULL COMMENT '前端市场复核驳回',
    ipVeroBeforeStockin       INT NULL COMMENT 'ip(侵权)来货前',
    delete                    INT NULL COMMENT '删除不开发数量',
    leaderCausedReject        INT NULL COMMENT '组长审核驳回',
    developCancel             INT NULL COMMENT '开发作废',
    leaderApproved            INT NULL COMMENT '组长审核通过',
    qdPublishingOrNotbid      INT NULL COMMENT '发布中或未接单',
    qdbidedStockNotIn         INT NULL COMMENT '已接单未来货',
    qdPublishedOrNotbidcancel INT NULL COMMENT '发布后未接单作废',
    bilSkuNum                 INT NULL COMMENT '开单来货sku数量',
    stockInActual             INT NULL COMMENT '实际到仓',
    retiredAfterStockIn       INT NULL COMMENT '来货后资料作废',
    safetyCausedRetire        INT NULL COMMENT '安全问题作废',
    regularCausedRetire       INT NULL COMMENT '法规问题作废',
    quantityCausedRetire      INT NULL COMMENT '质量问题作废',
    ipVeroAfterStockIn        INT NULL COMMENT 'ip(侵权)来货后',
    sameProductCausedRetire   INT NULL COMMENT '产品重复作废',
    OtherCausedRetire         INT NULL COMMENT '其他问题作废',
    inUploadPipeline          INT NULL COMMENT '上架管道中',
    uploadSKU                 INT NULL COMMENT '上架sku数量',
    createdby                 VARCHAR(255) NULL COMMENT '创建者',
    modifiedby                VARCHAR(255) NULL COMMENT '更新者',
    modifiedon                DATETIME NULL COMMENT '更新时间',
)ENGINE = InnoDB DEFAULT CHARSET = utf8;


CREATE TABLE pa_monthly_sales
(
    year VARCHAR(255) NULL COMMENT '年',
    month VARCHAR(255) NULL COMMENT '月',
    topcategoryname VARCHAR(255) NULL COMMENT '分类名称',
    topcategoryid VARCHAR(255) NULL COMMENT '分类id',
    business_type VARCHAR(255) NULL COMMENT '产品目录类型',
    corr_channel VARCHAR(255) NULL COMMENT '渠道',
    turnover INT NULL COMMENT '销售gr',
    createdby VARCHAR(255) NULL COMMENT '创建人',
    createdon DATETIME NULL COMMENT '创建时间',
    modifiedby VARCHAR(255) NULL COMMENT '修改人',
    modifiedon DATETIME NULL COMMENT '修改时间',
)ENGINE = InnoDB DEFAULT CHARSET = utf8;

CREATE TABLE pa_efficient_sku_life_cycle_management
(
    skuid              VARCHAR(255) NULL COMMENT 'skuid',
    businessTypeNew    VARCHAR(255) NULL COMMENT '业务类型',
    businessType       VARCHAR(255) NULL COMMENT '业务类型',
    cebillno           VARCHAR(255) NULL COMMENT 'CE单',
    cntitle            VARCHAR(255) NULL COMMENT '标题',
    category_full_path VARCHAR(255) NULL COMMENT '分类全路径',
    leafcategoryid     VARCHAR(255) NULL COMMENT '末级分类ID',
    salesusername      VARCHAR(255) NULL COMMENT '销售人员',
    developerusername  VARCHAR(255) NULL COMMENT '开发人员',
    usergroup          VARCHAR(255) NULL COMMENT '运营小组',
    userposition       VARCHAR(255) NULL COMMENT '销售人员岗位',
    publishtime        DATETIME NULL COMMENT '资料发布时间',
    fittype            VARCHAR(255) NULL COMMENT '适配类型',
    limitquantity      VARCHAR(255) NULL COMMENT '是否限量：1-限量，0-不限量',
    testimatemonthsold INT NULL COMMENT '月预估销量（若estimateMonthSold="NULL"，则默认为15）',
    sold_30days        INT NULL COMMENT '近30天sold',
    pl_30days          INT NULL COMMENT '近30天PL',
    sales_30days       INT NULL COMMENT '近30天sales',
    margin_30days      INT NULL COMMENT '近30天margin',
    stockqty           INT NULL COMMENT '当前总库存(各仓在仓及在途)',
    firstmoveindate    VARCHAR(255) NULL COMMENT 'US_01仓的首次移库入库时间',
    onlinedays         INT NULL COMMENT '上架天数',
    daysinus_01        VARCHAR(255) NULL COMMENT '已入库天数(首次移库)',
    estimatemontheff   INT NULL COMMENT '预估月eff',
    montheff           INT NULL COMMENT '实际月eff',
    stockratio         INT NULL COMMENT '库存比=当前总库存/近30天sold',
    issatisfied        VARCHAR(255) NULL COMMENT '月eff是否符合预期：Y/N',
    ismovetous_01      VARCHAR(255) NULL COMMENT 'US_01仓首次移库是否已入库：Y/N',
    basicscore         INT NULL COMMENT '到库前得分',
    score1             INT NULL COMMENT '到库后第1个月得分',
    score2             INT NULL COMMENT '到库后第2个月得分',
    score3             INT NULL COMMENT '到库后第3个月得分',
    score4             INT NULL COMMENT '到库后第4个月得分',
    score5             INT NULL COMMENT '到库后第5个月得分',
    score6             INT NULL COMMENT '到库后第6个月得分',
    totalscore         INT NULL COMMENT '总得分',
    lifecycle          VARCHAR(255) NULL COMMENT '生命周期',
    timeperiod         VARCHAR(255) NULL COMMENT '已入库时间阶段',
    status             VARCHAR(255) NULL COMMENT '状态',
    isimproved         VARCHAR(255) NULL COMMENT '是否需要优化',
    checkdate          DATETIME NULL COMMENT '系统检查日期',
    advise             VARCHAR(255) NULL COMMENT '处理建议',
    outScore           INT NULL COMMENT '应得分',
    createdby          VARCHAR(255) NULL COMMENT '创建人',
    createdon          DATETIME NULL COMMENT '创建时间',
    modifiedby         VARCHAR(255) NULL COMMENT '修改人',
    modifiedon         DATETIME NULL COMMENT '修改时间'
)ENGINE = InnoDB DEFAULT CHARSET = utf8;


CREATE TABLE pa_efficient_sku_life_cycle_management_log
(
    sku        VARCHAR(255) NULL COMMENT 'sku',
    checkDate  DATETIME NULL COMMENT '系统检查时间',
    lifeCycle  VARCHAR(255) NULL COMMENT '生命周期',
    status     VARCHAR(255) NULL COMMENT '状态',
    timeperiod VARCHAR(255) NULL COMMENT '入库时间阶段',
    remarks    VARCHAR(255) NULL COMMENT '处理内容',
    createdBy  VARCHAR(255) NULL COMMENT '处理人',
    createdOn  DATETIME NULL COMMENT '处理时间',
    modifiedBy VARCHAR(255) NULL COMMENT '修改人',
    modifiedOn DATETIME NULL COMMENT '修改时间'
)ENGINE = InnoDB DEFAULT CHARSET = utf8;

CREATE TABLE pa_efficient_sku_life_cycle_index_score
(
    skuid            VARCHAR(255) NULL COMMENT 'skuid',
    isallupload      INT NULL COMMENT '可售英语渠道均上架',
    manualprice1     INT NULL COMMENT '上架7天有人工定价',
    movetous_01      INT NULL COMMENT '上架7天有移库记录',
    manualsp         INT NULL COMMENT '投放manual广告',
    autosp           INT NULL COMMENT '投放auto广告',
    uploadfitment    INT NULL COMMENT '上传Fitment',
    manualprice2     INT NULL COMMENT '到库后有人工定价',
    deliveredfromsz  INT NULL COMMENT 'nonfba发货仓是SZ',
    impr_15days      INT NULL COMMENT '入库15天SP曝光量',
    clicks_15days    INT NULL COMMENT '入库15天SP点击量',
    cr_15days        INT NULL COMMENT '入库15天SP转化率',
    monthacos1       INT NULL COMMENT '近30天ACOS(入库第1个月)',
    monthsold        INT NULL COMMENT '入库30天Amazon_us销量',
    instockratio1    INT NULL COMMENT '近30天库存可售时间比(入库第2个月)',
    newpricedate     INT NULL COMMENT 'Amazon_us有新定价(入库第2个月)',
    montheff1        INT NULL COMMENT '近30天销售效率(入库第2个月)',
    monthacos2       INT NULL COMMENT '近30天ACOS(入库第2个月)',
    isnce            INT NULL COMMENT '90天内NCE通知(入库第3个月)',
    amazonoos        INT NULL COMMENT '近30天OOS情况(入库第3个月)',
    instockratio2    INT NULL COMMENT '近30天库存可售时间比(入库第3个月)',
    montheff2        INT NULL COMMENT '近30天销售效率(入库第3个月)',
    monthacos3       INT NULL COMMENT '近30天ACOS(入库第3个月)',
    monthrefundratio INT NULL COMMENT '月refund占比(入库第3个月)',
    monthkbsqty1     INT NULL COMMENT '近30天KBS投诉(入库第3个月)',
    monthmargin1     INT NULL COMMENT '近30天margin(入库第3个月)',
    montheff3        INT NULL COMMENT '近30天销售效率(入库第4个月)',
    monthonmontheff1 INT NULL COMMENT '环比月EFF(入库第4个月)',
    instockdays1     INT NULL COMMENT '近30天库存有效天数(入库第4个月)',
    refundratio1     INT NULL COMMENT '历史refund比例(入库第4个月)',
    monthmargin2     INT NULL COMMENT '近30天margin(入库第4个月)',
    monthkbsqty2     INT NULL COMMENT '近30天KBS投诉(入库第4个月)',
    montheff4        INT NULL COMMENT '近30天销售效率(入库第5个月)',
    monthonmontheff2 INT NULL COMMENT '环比月EFF(入库第5个月)',
    instockdays2     INT NULL COMMENT '近30天库存有效天数(入库第5个月)',
    refundratio2     INT NULL COMMENT '历史refund比例(入库第5个月)',
    monthmargin3     INT NULL COMMENT '近30天margin(入库第5个月)',
    monthkbsqty3     INT NULL COMMENT '近30天KBS投诉(入库第5个月)',
    montheff5        INT NULL COMMENT '近30天销售效率(入库第6个月)',
    monthonmontheff3 INT NULL COMMENT '环比月EFF(入库第6个月)',
    instockdays3     INT NULL COMMENT '近30天库存有效天数(入库第6个月)',
    refundratio3     INT NULL COMMENT '历史refund比例(入库第6个月)',
    monthmargin4     INT NULL COMMENT '近30天margin(入库第6个月)',
    monthkbsqty4     INT NULL COMMENT '近30天KBS投诉(入库第6个月)',
    createdBy        VARCHAR(255) NULL COMMENT '处理人',
    createdOn        DATETIME NULL COMMENT '处理时间',
    modifiedBy       VARCHAR(255) NULL COMMENT '修改人',
    modifiedOn       DATETIME NULL COMMENT '修改时间'
)ENGINE = InnoDB DEFAULT CHARSET = utf8;

CREATE TABLE amazon_category_productType
(
    -- 字段内容大都从（channel_amazon_category表里的）
    "channel" : "amazon_jp", -- 渠道
    "categoryId" : "3573918051", -- 渠道目录末级ID
    "categoryParentId" : "3573712051", -- categoryId的父级ID
    "browsePathId" : "2017305051,2045052051,2045053051,3573712051,3573918051", -- 渠道目录全路径的全部分类ID
    "browsePathName" : "車＆バイク > カーパーツ > 外装・エアロパーツ > ドア・パーツ > 室内ドアハンドル", -- 渠道目录全路径名称
    "ptName" : "AutoPart", --产品类型
    "xsdCategory" : "AutoAccessory", --xsd名称
    "ptFullPath" : "AutoAccessory>AutoPart", --产品类型全路径
    "ptNameEqualsAmazonSuggestedPt" : true, --系统推荐PT和amazon推荐PT是否一致
    "amazonSuggestedPt" : "AutoPart",--amazon推荐PT
    "variationTheme" : ["Color-Patternname"], -- 变体：分组名称
)ENGINE = InnoDB DEFAULT CHARSET = utf8;

CREATE TABLE pa_product_screening_criteria_pp
(
    taskId     VARCHAR(255) NULL COMMENT '任务ID',
    taskTitle  VARCHAR(255) NULL COMMENT '任务标题',
    channel    VARCHAR(255) NULL COMMENT 'Amazon渠道',
    category   VARCHAR(255) NULL COMMENT 'Amazon分类ID',
    status     VARCHAR(255) NULL COMMENT '状态',
    createdBy  VARCHAR(255) NULL COMMENT '处理人',
    createdOn  DATETIME NULL COMMENT '处理时间',
    modifiedBy VARCHAR(255) NULL COMMENT '修改人',
    modifiedOn DATETIME NULL COMMENT '修改时间'
)ENGINE = InnoDB DEFAULT CHARSET = utf8;

CREATE TABLE pa_product_screening_criteria_pp_log
(
    taskId     VARCHAR(255) NULL COMMENT '任务ID',
    operatedOn VARCHAR(255) NULL COMMENT '操作时间',
    operatedBy VARCHAR(255) NULL COMMENT '操作人',
    remark     VARCHAR(255) NULL COMMENT '操作内容',
    createdBy  VARCHAR(255) NULL COMMENT '处理人',
    createdOn  DATETIME NULL COMMENT '处理时间',
    modifiedBy VARCHAR(255) NULL COMMENT '修改人',
    modifiedOn DATETIME NULL COMMENT '修改时间'
)ENGINE = InnoDB DEFAULT CHARSET = utf8;

CREATE TABLE pa_product_screening_criteria_pp_detail
(
    taskId                  VARCHAR(255) NULL COMMENT '任务ID',
    asin                    VARCHAR(255) NULL COMMENT 'asin',
    categoryId              VARCHAR(255) NULL COMMENT '分类id',
    categoryPath            VARCHAR(255) NULL COMMENT '分类全路径',
    listedSince             DATETIME NULL COMMENT '上架时间',
    title                   VARCHAR(255) NULL COMMENT '标题',
    description             VARCHAR(255) NULL COMMENT '描述',
    features                VARCHAR(255) NULL COMMENT '特征',
    parentAsin              VARCHAR(255) NULL COMMENT '父asin',
    variationCSV            VARCHAR(255) NULL COMMENT '子asin',
    color                   VARCHAR(255) NULL COMMENT '颜色',
    size                    VARCHAR(255) NULL COMMENT '尺寸',
    buyboxPrice             INT NULL COMMENT '售价',
    buyboxIsFba             VARCHAR(255) NULL COMMENT '是否fba',
    dateRankMap             INT NULL COMMENT '大类排名',
    dateRankMapLastCategory INT NULL COMMENT '小类排名',
    productGroup            VARCHAR(255) NULL COMMENT '产品类别',
    sold                    INT NULL COMMENT ' 近30天sold ',
    review                  INT NULL COMMENT ' 评论数 ',
    rating                  INT NULL COMMENT ' 评分 ',
    brand                   VARCHAR(255) NULL COMMENT ' 品牌 ',
    packageLength           INT NULL COMMENT ' 包装尺寸-长/mm ',
    packageWidth            INT NULL COMMENT ' 包装尺寸-宽/mm ',
    packageHeight           INT NULL COMMENT ' 包装尺寸-高/mm ',
    packageWeight           INT NULL COMMENT ' 重量/g ',
    pickAndPackFee          INT NULL COMMENT ' 拣货费用 ',
    itemLength              INT NULL COMMENT ' 产品尺寸-长/mm ',
    itemWidth               INT NULL COMMENT ' 产品尺寸-宽/mm ',
    itemHeight              INT NULL COMMENT ' 产品尺寸-高/mm ',
    itemWeight              INT NULL COMMENT ' 产品重量/g ',
    isRight                 VARCHAR(255) NULL COMMENT '是否满足筛选条件:Y/N',
    isDeleted               VARCHAR(255) NULL COMMENT '是否排除:Y/N',
    createdBy               VARCHAR(255) NULL COMMENT ' 处理人 ',
    createdOn               DATETIME NULL COMMENT '处理时间',
    modifiedBy              VARCHAR(255) NULL COMMENT '修改人',
    modifiedOn              DATETIME NULL COMMENT '修改时间'
)ENGINE = InnoDB DEFAULT CHARSET = utf8;

CREATE TABLE amazon_sp_negative_target
(
    campaignId     VARCHAR(255) NULL COMMENT '广告活动Id',
    adGroupId      VARCHAR(255) NULL COMMENT '广告组Id',
    targetId       VARCHAR(255) NULL COMMENT 'targetId',
    channel        VARCHAR(255) NULL COMMENT '渠道',
    type           VARCHAR(255) NULL COMMENT '定位类型: 调接口的express 的value',
    value          VARCHAR(255) NULL COMMENT '定位值：调接口的express 的value',
    expressionType VARCHAR(255) NULL COMMENT '定位表达式: auto,manual',
    state          VARCHAR(255) NULL COMMENT 'state状态：enabled,paused,archived',
    bid            INT NULL COMMENT 'bid',
    status         INT NULL COMMENT 'mongodb的状态1:正在进行 2:任务完成 3:失败重试',
    targetName     VARCHAR(255) NULL COMMENT 'target的名称:是value或者是type',
    createdBy      VARCHAR(255) NULL COMMENT '创建人',
    modifiedBy     VARCHAR(255) NULL COMMENT '修改人',
    modifiedOn     DATETIME NULL COMMENT '修改时间',
    createdOn      DATETIME NULL COMMENT '创建时间'
)ENGINE = InnoDB DEFAULT CHARSET = utf8;

CREATE TABLE amazon_sp_target
(
    campaignId     VARCHAR(255) NULL COMMENT '广告活动Id',
    adGroupId      VARCHAR(255) NULL COMMENT '广告组Id',
    targetId       VARCHAR(255) NULL COMMENT 'targetId',
    channel        VARCHAR(255) NULL COMMENT '渠道',
    type           VARCHAR(255) NULL COMMENT '定位类型: 调接口的express 的value',
    value          VARCHAR(255) NULL COMMENT '定位值：调接口的express 的value',
    expressionType VARCHAR(255) NULL COMMENT '定位表达式: auto,manual',
    state          VARCHAR(255) NULL COMMENT 'state状态：enabled,paused,archived',
    bid            INT NULL COMMENT 'bid',
    status         INT NULL COMMENT 'mongodb的状态1:正在进行 2:任务完成 3:失败重试',
    targetName     VARCHAR(255) NULL COMMENT 'target的名称:是value或者是type',
    createdBy      VARCHAR(255) NULL COMMENT '创建人',
    modifiedBy     VARCHAR(255) NULL COMMENT '修改人',
    modifiedOn     DATETIME NULL COMMENT '修改时间',
    createdOn      DATETIME NULL COMMENT '创建时间'
)ENGINE = InnoDB DEFAULT CHARSET = utf8;

CREATE TABLE amazon_order_report
(
    channel             VARCHAR(255) NULL COMMENT '渠道',
    sellerid            VARCHAR(255) NULL COMMENT '账号',
    fulfillment_channel VARCHAR(255) NULL COMMENT '配送渠道(Amazon：FBA,Merchant：nonFBA)',
    sku                 VARCHAR(255) NULL COMMENT '售出ID',
    skuid               VARCHAR(255) NULL COMMENT 'skuId',
    asin                VARCHAR(255) NULL COMMENT 'asin',
    product_name        VARCHAR(255) NULL COMMENT '标题',
    lowername           VARCHAR(255) NULL COMMENT '标题小写',
    item_price          INT NULL COMMENT '售价',
    amazon_order_id     VARCHAR(255) NULL COMMENT '亚马逊订单号',
    order_status        VARCHAR(255) NULL COMMENT '订单状态:Pending、Shipped、Cancelled、Shipping',
    orderStatusUpdateOn DATETIME NULL COMMENT '订单状态更新日期',
    quantity            INT NULL COMMENT '销售件数',
    purchase_date       DATETIME NULL COMMENT '下单时间',
    timeInterval        INT NULL COMMENT '近n天',
    salesHK             INT NULL COMMENT 'HK销售金额',
    salesLocal          INT NULL COMMENT '当地销售金额',
    createdBy           VARCHAR(255) NULL COMMENT '创建人',
    modifiedBy          VARCHAR(255) NULL COMMENT '修改人',
    modifiedOn          DATETIME NULL COMMENT '修改时间',
    createdOn           DATETIME NULL COMMENT '创建时间'
)ENGINE = InnoDB DEFAULT CHARSET = utf8;

CREATE TABLE pa_efficient_sku_life_cycle_index_follow_score
(
    skuid VARCHAR(15) NULL COMMENT 'skuId',
    isallupload INT NULL COMMENT '',
    manualprice1  INT NULL COMMENT '',
    movetous_01  INT NULL COMMENT '',
    manualsp  INT NULL COMMENT '',
    autosp  INT NULL COMMENT '',
    uploadfitment  INT NULL COMMENT '',
    manualprice2  INT NULL COMMENT '',
    deliveredfromsz  INT NULL COMMENT '',
    impr_15days  INT NULL COMMENT '',
    clicks_15days  INT NULL COMMENT '',
    cr_15days  INT NULL COMMENT '',
    monthacos1  INT NULL COMMENT '',
    monthsold  INT NULL COMMENT '',
    instockratio1  INT NULL COMMENT '',
    newpricedate  INT NULL COMMENT '',
    montheff1  INT NULL COMMENT '',
    monthacos2  INT NULL COMMENT '',
    isnce  INT NULL COMMENT '',
    amazonoos  INT NULL COMMENT '',
    instockratio2  INT NULL COMMENT '',
    montheff2  INT NULL COMMENT '',
    monthacos3  INT NULL COMMENT '',
    monthrefundratio  INT NULL COMMENT '',
    monthkbsqty1  INT NULL COMMENT '',
    monthmargin1  INT NULL COMMENT '',
    montheff3  INT NULL COMMENT '',
    monthonmontheff1  INT NULL COMMENT '',
    instockdays1  INT NULL COMMENT '',
    refundratio1  INT NULL COMMENT '',
    monthmargin2  INT NULL COMMENT '',
    monthkbsqty2  INT NULL COMMENT '',
    montheff4  INT NULL COMMENT '',
    monthonmontheff2  INT NULL COMMENT '',
    instockdays2  INT NULL COMMENT '',
    refundratio2  INT NULL COMMENT '',
    monthmargin3  INT NULL COMMENT '',
    monthkbsqty3  INT NULL COMMENT '',
    montheff5  INT NULL COMMENT '',
    monthonmontheff3  INT NULL COMMENT '',
    instockdays3  INT NULL COMMENT '',
    refundratio3  INT NULL COMMENT '',
    monthmargin4  INT NULL COMMENT '',
    monthkbsqty4  INT NULL COMMENT '',
    followpointtimeslist VARCHAR(255) NULL COMMENT '跟进分key-value时间记录',
)ENGINE = InnoDB DEFAULT CHARSET = utf8;


CREATE TABLE pa_efficient_sku_life_cycle_index_follow_score
(
    skuid                VARCHAR(15) NULL COMMENT 'skuId',
    salesusername        VARCHAR(20) NULL COMMENT '销售',
    followpointtimeslist VARCHAR(255) NULL COMMENT '指标详情',
    createdBy            VARCHAR(255) NULL COMMENT '创建人',
    modifiedBy           VARCHAR(255) NULL COMMENT '修改人',
    modifiedOn           DATETIME NULL COMMENT '修改时间',
    createdOn            DATETIME NULL COMMENT '创建时间'
)ENGINE = InnoDB DEFAULT CHARSET = utf8;


CREATE TABLE amazon_sp_targeting_collect
(
    type               VARCHAR(255) NULL COMMENT '时间类型',
    sellerid           VARCHAR(255) NULL COMMENT '账号',
    adgroupid          VARCHAR(255) NULL COMMENT 'adGroupId',
    campaignid         VARCHAR(255) NULL COMMENT 'campaignId',
    targetid           VARCHAR(255) NULL COMMENT 'targetId',
    targetingtype      VARCHAR(255) NULL COMMENT 'target类别',
    targetingtext      VARCHAR(255) NULL COMMENT 'target名称',
    spend              INT NULL COMMENT '广告花费',
    seventotalorders   INT NULL COMMENT '7天总订单数',
    impressions        INT NULL COMMENT '曝光量',
    clicks             INT NULL COMMENT '点击量',
    sevendaytotalsales INT NULL COMMENT '7天总销售额',
    createdBy          VARCHAR(255) NULL COMMENT '创建人',
    modifiedBy         VARCHAR(255) NULL COMMENT '修改人',
    modifiedon         DATETIME NULL COMMENT '修改时间',
    createdOn          DATETIME NULL COMMENT '创建时间'
)ENGINE = InnoDB DEFAULT CHARSET = utf8;


CREATE TABLE amazon_sp_rule_customize
(
    channel       VARCHAR(255) NULL COMMENT '适用渠道',
    company       VARCHAR(255) NULL COMMENT '垂直',
    sellerId      VARCHAR(255) NULL COMMENT '账号',
    targetingType VARCHAR(255) NULL COMMENT '广告类型:auto;manual',
    ruleRange     VARCHAR(255) NULL COMMENT '规则范围:adgroup;keyword;targeting',
    matchType     VARCHAR(255) NULL COMMENT '匹配类型',
    ruleId        VARCHAR(255) NULL COMMENT '规则id',
    ruleStatus    VARCHAR(255) NULL COMMENT '规则状态',
    imprLower     INT NULL COMMENT 'impr下限,sql里，spend≥右边的值',
    imprUpper     INT NULL COMMENT 'impr上限,sql里，spend≤右边的值',
    clicksLower   INT NULL COMMENT 'clicks下限',
    clicksUpper   INT NULL COMMENT 'clicks上限',
    spendLower    INT NULL COMMENT 'spend下限',
    spendUpper    INT NULL COMMENT 'spend上限',
    ordersLower   INT NULL COMMENT 'orders下限',
    ordersUpper   INT NULL COMMENT 'orders上限',
    salesLower    INT NULL COMMENT 'sales下限',
    salesUpper    INT NULL COMMENT 'sales上限',
    acosLower     INT NULL COMMENT 'acos下限',
    acosUpper     INT NULL COMMENT 'acos上限',
    ctrLower      INT NULL COMMENT 'ctr下限',
    ctrUpper      INT NULL COMMENT 'ctr上限',
    cpcLower      INT NULL COMMENT 'cpc下限',
    cpcUpper      INT NULL COMMENT 'cpc上限',
    crLower       INT NULL COMMENT 'cr下限',
    crUpper       INT NULL COMMENT 'cr上限',
    cpaLower      INT NULL COMMENT 'cpa下限',
    cpaUpper      INT NULL COMMENT 'cpa上限',
    bidLower      INT NULL COMMENT 'bid下限',
    bidUpper      INT NULL COMMENT 'bid上限',
    firstActDate  INT NULL COMMENT '首次执行时间',
    dateInterval  INT NULL COMMENT '日期间隔/天,每次执行的频率设置 >= 1',
    action        VARCHAR(255) NULL COMMENT '规则命中后的行为,sp_notification的action字段值',
    actStatus     VARCHAR(255) NULL COMMENT '动作状态,调用接口时,status的传参,若规则命中后需要暂停广告,则值为paused',
    bid           INT NULL COMMENT 'bid降低值',
    createdBy     VARCHAR(255) NULL COMMENT '创建人',
    modifiedBy    VARCHAR(255) NULL COMMENT '修改人',
    modifiedOn    DATETIME NULL COMMENT '修改时间',
    createdOn     DATETIME NULL COMMENT '创建时间'
)ENGINE = InnoDB DEFAULT CHARSET = utf8;

CREATE TABLE pa_amazon_sp_rules_result
(
    channel    VARCHAR(255) NULL COMMENT '渠道',
    sellerId   VARCHAR(255) NULL COMMENT '账号',
    campaignId VARCHAR(255) NULL COMMENT 'campaignId',
    adgroupId  VARCHAR(255) NULL COMMENT 'adgroupId',
    ruleId     VARCHAR(255) NULL COMMENT '规则Id',
    createdBy  VARCHAR(255) NULL COMMENT '创建人',
    modifiedBy VARCHAR(255) NULL COMMENT '修改人',
    modifiedOn DATETIME NULL COMMENT '修改时间',
    createdOn  DATETIME NULL COMMENT '创建时间'
)ENGINE = InnoDB DEFAULT CHARSET = utf8;

CREATE TABLE amazon_sp_rule_customize_log
(
    amazon_sp_rule_customize_id VARCHAR(255) NULL COMMENT '主表id',
    ruleId                      VARCHAR(255) NULL COMMENT '规则ID',
    tab                         VARCHAR(255) NULL COMMENT '日志类型',
    logContent                  VARCHAR(255) NULL COMMENT '日志内容',
    createdBy                   VARCHAR(255) NULL COMMENT '创建人',
    modifiedBy                  VARCHAR(255) NULL COMMENT '修改人',
    modifiedOn                  DATETIME NULL COMMENT '修改时间',
    createdOn                   DATETIME NULL COMMENT '创建时间'
)ENGINE = InnoDB DEFAULT CHARSET = utf8;

CREATE TABLE ebay_bilino_add_round
(
    tempSkuId    VARCHAR(255) NULL COMMENT '临时sku',
    skuId        VARCHAR(255) NULL COMMENT 'skuId',
    CEBiliNo     VARCHAR(255) NULL COMMENT 'CE单号',
    batchName    VARCHAR(255) NULL COMMENT '批次号',
    moveToEbayUs VARCHAR(255) NULL COMMENT 'ebay_us可售状态',
    status       VARCHAR(255) NULL COMMENT '状态',
    sellerId     VARCHAR(255) NULL COMMENT '分配账号',
    addBy        VARCHAR(255) NULL COMMENT '分配人',
    remark       VARCHAR(255) NULL COMMENT '日志',
    modifiedBy   VARCHAR(255) NULL COMMENT '记录修改人',
    modifiedOn   DATETIME NULL COMMENT '记录修改时间',
    createdOn    DATETIME NULL COMMENT '记录创建时间',
    createdBy    VARCHAR(255) NULL COMMENT '记录创建时间'
)ENGINE = InnoDB DEFAULT CHARSET = utf8;

CREATE TABLE ebay_sellerId_add_count
(
    sellerId   VARCHAR(255) NULL COMMENT 'ebay账号',
    scoreNow   INT NULL COMMENT '当前得分',
    addScore   INT NULL COMMENT '单次分配得分',
    baseScore  INT NULL COMMENT '基础得分',
    skuNum     INT NULL COMMENT '已分配sku数量',
    LastAddOn  DATETIME NULL COMMENT '最后一次分配时间',
    modifiedBy VARCHAR(255) NULL COMMENT '记录修改人',
    modifiedOn DATETIME NULL COMMENT '记录修改时间',
    createdOn  DATETIME NULL COMMENT '记录创建时间',
    createdBy  VARCHAR(255) NULL COMMENT '记录创建时间'
)ENGINE = InnoDB DEFAULT CHARSET = utf8;

CREATE TABLE pa_fitment_confirm
(
    skuId           VARCHAR(255) NULL COMMENT '首次导入时可能是临时编号tempId，当生成sku号，需要更新为skuId',
    inventoryStatus VARCHAR(255) NULL COMMENT 'inventory状态,记录用户导入的状态，并非实际可售状态',
    fitmentType     VARCHAR(255) NULL COMMENT '适配类型',
    isConfirm       VARCHAR(255) NULL COMMENT '是否确认',
    confirmWay      VARCHAR(255) NULL COMMENT '确认途径',
    source          VARCHAR(255) NULL COMMENT '来源',
    modifiedBy      VARCHAR(255) NULL COMMENT '修改人',
    modifiedOn      DATETIME NULL COMMENT '修改日期',
    createdOn       DATETIME NULL COMMENT '创建日期',
    createdBy       VARCHAR(255) NULL COMMENT '创建人'
)ENGINE = InnoDB DEFAULT CHARSET = utf8;

CREATE TABLE pa_refer_sku
(
    categoryPath     VARCHAR(255) NULL COMMENT '中文分类全路径',
    lastCategoryName VARCHAR(255) NULL COMMENT '末级中文分类名称',
    lastCategoryId   VARCHAR(255) NULL COMMENT '末级中文分类ID',
    refSku           VARCHAR(255) NULL COMMENT '推荐sku',
    isValid          VARCHAR(255) NULL COMMENT '是否有效',
    modifiedBy       VARCHAR(255) NULL COMMENT '修改人',
    modifiedOn       DATETIME NULL COMMENT '修改日期',
    createdOn        DATETIME NULL COMMENT '创建日期',
    createdBy        VARCHAR(255) NULL COMMENT '创建人'
)ENGINE = InnoDB DEFAULT CHARSET = utf8;