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


CREATE TABLE pa_ebay_fcu_apply
(
    applyBatch    VARCHAR(255) NULL COMMENT '申请批次,QS+YYYYMMDD+五位',
    applyStatus   VARCHAR(255) NULL COMMENT '审核状态.新建-0:导入后默认状态;待审核-1:提交审核后,尚有sku未审核;已审核-2:所有sku状态均不为待审核',
    projectRemark VARCHAR(255) NULL COMMENT '项目备注.用户导入（小于20字符）',
    applyPurpose  VARCHAR(255) NULL COMMENT '创建目的',
    totalNum      INT NULL COMMENT '总数量(成功导入的总条数)',
    fcuNum        INT NULL COMMENT 'FCU数量(成功生成的fcu数量)',
    modifiedBy    VARCHAR(255) NULL COMMENT '修改人',
    modifiedOn    DATETIME NULL COMMENT '修改日期',
    createdOn     DATETIME NULL COMMENT '用户导入时间',
    createdBy     VARCHAR(255) NULL COMMENT '导入的用户'
)ENGINE = InnoDB DEFAULT CHARSET = utf8;

CREATE TABLE pa_ebay_fcu_apply_detail
(
    applyBatch    VARCHAR(255) NULL COMMENT '申请批次,QS+YYYYMMDD+五位',
    skuList       VARCHAR(255) NULL COMMENT 'sku相关信息',
    projectRemark VARCHAR(255) NULL COMMENT '项目备注.用户导入（小于20字符）',
    applyPurpose  VARCHAR(255) NULL COMMENT '创建目的',
    stockGroup    VARCHAR(255) NULL COMMENT '目的仓库:US_04,US_4PX_06,SZ,UK_06,AU_06,DE_EF01,FR_06,ES_06',
    fcuType       VARCHAR(255) NULL COMMENT 'FCU类型[综合,高档,中档,低档]',
    fitType       VARCHAR(255) NULL COMMENT '适配类型[专用,通用]',
    make          VARCHAR(255) NULL COMMENT 'Make',
    model         VARCHAR(255) NULL COMMENT 'Model',
    year          VARCHAR(255) NULL COMMENT 'Year',
    moveQty       INT NULL COMMENT '移库数量,整数,stockGroup = SZ时,值为0,为其他仓库时,值大于等于0',
    sellerId      VARCHAR(255) NULL COMMENT '上架账号,stockGroup+sellerId+Channel在seller_config',
    channel       VARCHAR(255) NULL COMMENT '可售渠道',
    isAssemble    VARCHAR(255) NULL COMMENT '是否fcu组合(sku为单个&fcu数量为1是=是，其他为否)',
    status        VARCHAR(255) NULL COMMENT '审核结果',
    applyRemark   VARCHAR(255) NULL COMMENT '审核备注(不通过时，用户填写)',
    fcuId         VARCHAR(255) NULL COMMENT 'fcuId(调生成fcu的接口生成后回写)',
    modifiedBy    VARCHAR(255) NULL COMMENT '修改人',
    modifiedOn    DATETIME NULL COMMENT '修改日期',
    createdOn     DATETIME NULL COMMENT '用户导入时间',
    createdBy     VARCHAR(255) NULL COMMENT '导入的用户'
)ENGINE = InnoDB DEFAULT CHARSET = utf8;


CREATE TABLE pa_ebay_fcu_processing_monitor
(
    applyBatch    VARCHAR(255) NULL COMMENT '申请批次,QS+YYYYMMDD+五位',
    stockGroup    VARCHAR(255) NULL COMMENT '目的仓库:US_04,US_4PX_06,SZ,UK_06,AU_06,DE_EF01,FR_06,ES_06',
    moveQty       INT NULL COMMENT '移库数量,整数',
    sellerId      VARCHAR(255) NULL COMMENT '上架账号',
    channel       VARCHAR(255) NULL COMMENT '可售渠道',
    fcuId         VARCHAR(255) NULL COMMENT 'fcuId(调生成fcu的接口生成后回写)',
    applyBy       VARCHAR(255) NULL COMMENT '申请人',
    applyDate     DATETIME NULL COMMENT '申请时间',
    onWayQty      INT NULL COMMENT '目的仓在途库存',
    inStockQty    INT NULL COMMENT '目的仓在库库存',
    moveStatus    VARCHAR(255) NULL COMMENT '移库状态',
    materialBy    VARCHAR(255) NULL COMMENT '资料负责人',
    photoBy       VARCHAR(255) NULL COMMENT '图片负责人',
    publishStatus VARCHAR(255) NULL COMMENT '资料状态',
    publishTime   DATETIME NULL COMMENT '发布日期',
    uploadTime    DATETIME NULL COMMENT '上架日期',
    modifiedBy    VARCHAR(255) NULL COMMENT '修改人',
    modifiedOn    DATETIME NULL COMMENT '修改日期',
    createdOn     DATETIME NULL COMMENT '用户导入时间',
    createdBy     VARCHAR(255) NULL COMMENT '导入的用户'
)ENGINE = InnoDB DEFAULT CHARSET = utf8;


CREATE TABLE pa_ebay_fcu_processing_collect
(
    applyDate       DATETIME NULL COMMENT '申请时间',
    applyBy         VARCHAR(255) NULL COMMENT '申请人',
    applyBatch      VARCHAR(255) NULL COMMENT '申请批次,QS+YYYYMMDD+五位',
    stockGroup      VARCHAR(255) NULL COMMENT '目的仓库:US_04,US_4PX_06,SZ,UK_06,AU_06,DE_EF01,FR_06,ES_06',
    sellerId        VARCHAR(255) NULL COMMENT '上架账号',
    fcuTotal        INT NULL COMMENT '审核通过',
    cancel          INT NULL COMMENT '作废sku',
    inStockSku      INT NULL COMMENT '已入库',
    onWaySku        INT NULL COMMENT '移库中',
    waitOnway       INT NULL COMMENT '待移库',
    msProgress      INT NULL COMMENT '移库分项进度',
    infoDrafting    INT NULL COMMENT '资料制作中',
    picDrafting     INT NULL COMMENT '图片制作中',
    completed       INT NULL COMMENT '资料检查完成',
    publishProgress INT NULL COMMENT '出版分项进度',
    upload          INT NULL COMMENT '已上架',
    offline         INT NULL COMMENT '未上架',
    uploadProgress  INT NULL COMMENT '上架分项进度',
    modifiedBy      VARCHAR(255) NULL COMMENT '修改人',
    modifiedOn      DATETIME NULL COMMENT '修改日期',
    createdOn       DATETIME NULL COMMENT '用户导入时间',
    createdBy       VARCHAR(255) NULL COMMENT '导入的用户'
)ENGINE = InnoDB DEFAULT CHARSET = utf8;


CREATE TABLE pa_high_efficiency_sku_management
(
    businessType         VARCHAR(255) NULL COMMENT '业务类型',
    topCategory          VARCHAR(255) NULL COMMENT '一级分类',
    date                 DATETIME NULL COMMENT '统计周',
    reviewCount          INT NULL COMMENT '前端市场复核',
    waitIpCount          INT NULL COMMENT 'IP待检查',
    waitLeaderCheckCount INT NULL COMMENT '组长待审核',
    waitInfoCountBefore  INT NULL COMMENT '来货中',
    waitInfoCountAfter   INT NULL COMMENT '待资料制作中',
    waitPicCount         INT NULL COMMENT '待图片制作中',
    waitManageCount      INT NULL COMMENT '待产品经理审核',
    waitFirstCount       INT NULL COMMENT '待首次侵权检查',
    waitSecondCount      INT NULL COMMENT '待二次侵权检查',
    completedCount       INT NULL COMMENT '历史资料检查完成',
    retiredCount         INT NULL COMMENT '历史资料作废',
    modifiedBy           VARCHAR(255) NULL COMMENT '修改人',
    modifiedOn           DATETIME NULL COMMENT '修改日期',
    createdOn            DATETIME NULL COMMENT '创建人',
    createdBy            VARCHAR(255) NULL COMMENT '创建日期'
)ENGINE = InnoDB DEFAULT CHARSET = utf8;

CREATE TABLE pa_high_efficiency_sku_management_detail
(
    skuid        VARCHAR(255) NULL COMMENT 'skuid',
    businessType VARCHAR(255) NULL COMMENT '业务类型',
    topCategory  VARCHAR(255) NULL COMMENT '一级分类',
    date         DATETIME NULL COMMENT '日期（周日）',
    developer    VARCHAR(255) NULL COMMENT '开发人员',
    status       VARCHAR(255) NULL COMMENT '状态',
    brand        VARCHAR(255) NULL COMMENT '亚马逊上架账号',
    modifiedBy   VARCHAR(255) NULL COMMENT '修改人',
    modifiedOn   DATETIME NULL COMMENT '修改日期',
    createdOn    DATETIME NULL COMMENT '创建人',
    createdBy    VARCHAR(255) NULL COMMENT '创建日期'
)ENGINE = InnoDB DEFAULT CHARSET = utf8;


CREATE TABLE pa_high_efficiency_sales_detail
(
    skuid VARCHAR(255) NULL COMMENT 'skuid',
    year VARCHAR(255) NULL COMMENT '统计年',
    date DATETIME NULL COMMENT '统计周',
    topCategory VARCHAR(255) NULL COMMENT '一级分类',
    businessType VARCHAR(255) NULL COMMENT '业务类型',
    developer VARCHAR(255) NULL COMMENT '开发人员',
    salesman VARCHAR(255) NULL COMMENT '销售人员',
    cnCategory VARCHAR(255) NULL COMMENT '中文全分类',
    warehouse VARCHAR(255) NULL COMMENT '仓库',
    inventoryStatus VARCHAR(255) NULL COMMENT 'inventory状态',
    publishStatus VARCHAR(255) NULL COMMENT '发布状态',
    publishTime DATETIME NULL COMMENT '发布时间',
    cost INT NULL COMMENT 'sku单价',
    inventory INT NULL COMMENT '总库存件数（含在途）',
    inventoryOnway INT NULL COMMENT '补货在途库存',
    soldHis INT NULL COMMENT '历史sold',
    salesHis INT NULL COMMENT '历史sales',
    plHis INT NULL COMMENT '历史pl',
    sold180 INT NULL COMMENT '近180天sold',
    complaintNum180 INT NULL COMMENT '近180天质量投诉件数',
    complaintRatio180 INT NULL COMMENT '近180天质量投诉率',
    sold7 INT NULL COMMENT '近1周sold',
    sold14 INT NULL COMMENT '近2周sold',
    sold21 INT NULL COMMENT '近3周sold',
    sold28 INT NULL COMMENT '近4周sold',
    modifiedBy   VARCHAR(255) NULL COMMENT '修改人',
    modifiedOn   DATETIME NULL COMMENT '修改日期',
    createdOn    DATETIME NULL COMMENT '创建人',
    createdBy    VARCHAR(255) NULL COMMENT '创建日期'
)ENGINE = InnoDB DEFAULT CHARSET = utf8;


CREATE TABLE pa_high_efficiency_sales
(
    activeSku            INT NULL COMMENT '在售skuid数',
    Year                 VARCHAR(255) NULL COMMENT '统计年',
    date                 DATETIME NULL COMMENT '统计周',
    topCategory          VARCHAR(255) NULL COMMENT '一级分类',
    businessType         VARCHAR(255) NULL COMMENT '业务类型',
    developer            VARCHAR(255) NULL COMMENT '开发人员',
    salesman             VARCHAR(255) NULL COMMENT '销售人员',
    warehouse            VARCHAR(255) NULL COMMENT '仓库',
    inStockTime          INT NULL COMMENT '入库时长',
    publishDays          INT NULL COMMENT '发布时长',
    sold                 INT NULL COMMENT '销量',
    sold30               INT NULL COMMENT '销量（近30天）',
    sold180              INT NULL COMMENT '销量（近180天）',
    sales                INT NULL COMMENT '销售额',
    pl                   INT NULL COMMENT '毛利',
    margin               INT NULL COMMENT '利润率',
    eff                  INT NULL COMMENT 'eff',
    ads                  INT NULL COMMENT 'ads',
    adsRatio             INT NULL COMMENT 'asd占比',
    nSku                 INT NULL COMMENT '售动sku数',
    nSkuRatio            INT NULL COMMENT '售动率',
    nSkuHis              INT NULL COMMENT '历史售动sku数',
    nSkuRatioHis         INT NULL COMMENT '历史售动率',
    inventory            INT NULL COMMENT '总库存件数（含在途）',
    inventoryAmount      INT NULL COMMENT '总库存金额（含在途）',
    inventoryOnway       INT NULL COMMENT '补货在途库存（SZ）',
    inventoryOnwayAmount INT NULL COMMENT '补货在途金额（SZ）',
    inventoryRatio       INT NULL COMMENT '库存比',
    complaintNum180      INT NULL COMMENT '近180天质量投诉件数（全渠道）',
    complaintRatio180    INT NULL COMMENT '近180天质量投诉率（全渠道）',
    modifiedBy           VARCHAR(255) NULL COMMENT '修改人',
    modifiedOn           DATETIME NULL COMMENT '修改日期',
    createdOn            DATETIME NULL COMMENT '创建人',
    createdBy            VARCHAR(255) NULL COMMENT '创建日期'
)ENGINE = InnoDB DEFAULT CHARSET = utf8;

CREATE TABLE pa_life_cycle_score_kpi
(
    theYear          VARCHAR(255) NULL COMMENT "年",
    theMonth         VARCHAR(255) NULL COMMENT "月",
    salesman         VARCHAR(255) NULL COMMENT "销售人员",
    skuId            VARCHAR(255) NULL COMMENT "skuId",
    uploadScore      INT NULL COMMENT "上架失败处理",
    uploadSitting    INT NULL COMMENT "上架失败处理跟进分",
    moveStockScore   INT NULL COMMENT "移库AM US FBA仓",
    moveStockSitting INT NULL COMMENT "移库AM US FBA仓跟进分",
    fitmentScore     INT NULL COMMENT "上传fitment",
    fitmentSitting   INT NULL COMMENT "上传fitment跟进分",
    autoSPScore      INT NULL COMMENT "自动SP投放",
    autoSPSitting    INT NULL COMMENT "自动SP投放跟进分",
    aPlusScore       INT NULL COMMENT "上传A+图",
    aPlusSitting     INT NULL COMMENT "上传A+图跟进分",
    modifiedBy       VARCHAR(255) NULL COMMENT '修改人',
    modifiedOn       DATETIME NULL COMMENT '修改日期',
    createdOn        DATETIME NULL COMMENT '创建人',
    createdBy        VARCHAR(255) NULL COMMENT '创建日期'
)ENGINE = InnoDB DEFAULT CHARSET = utf8;



CREATE TABLE pa_salesman_kpi
(
    salesman              VARCHAR(255) NULL COMMENT "销售人员",
    batchName             VARCHAR(255) NULL COMMENT "批次号",
    categoryCheckOn       DATETIME NULL COMMENT "中文类目审核完成时间",
    categoryCheckYear     VARCHAR(255) NULL COMMENT "中文类目审核完成年份",
    categoryCheckMonth    VARCHAR(255) NULL COMMENT "中文类目审核完成月份",
    reviewEndOn           DATETIME NULL COMMENT "前端市场复核完成时间",
    reviewCompletedStatus VARCHAR(255) NULL COMMENT "前端市场复核完成状态",
    ceBillno              VARCHAR(255) NULL COMMENT "CE单",
    developerCompletedOn  DATETIME NULL COMMENT "开发资料完成时间",
    salesCompletedOn      DATETIME NULL COMMENT "销售资料完成时间",
    hzReciveOn            DATETIME NULL COMMENT "惠州到货时间",
    hzReciveYear          VARCHAR(255) NULL COMMENT "惠州到货年份",
    hzReciveMonth         VARCHAR(255) NULL COMMENT "惠州到货月份",
    salesCompletedStatus  VARCHAR(255) NULL COMMENT "销售资料完成状态",
    modifiedBy            VARCHAR(255) NULL COMMENT '修改人',
    modifiedOn            DATETIME NULL COMMENT '修改日期',
    createdOn             DATETIME NULL COMMENT '创建人',
    createdBy             VARCHAR(255) NULL COMMENT '创建日期'
)ENGINE = InnoDB DEFAULT CHARSET = utf8;

CREATE TABLE pa_manager_kpi
(
    theMonth      VARCHAR(255) NULL COMMENT '年月',
    businessGroup VARCHAR(255) NULL COMMENT '业务组别',
    label         VARCHAR(255) NULL COMMENT '标签',
    targetGr      INT NULL COMMENT '目标GR',
    actualGr      INT NULL COMMENT '实际GR',
    actualPL      INT NULL COMMENT '实际PL',
    modifiedBy    VARCHAR(255) NULL COMMENT '修改人',
    modifiedOn    DATETIME NULL COMMENT '修改日期',
    createdOn     DATETIME NULL COMMENT '创建人',
    createdBy     VARCHAR(255) NULL COMMENT '创建日期'
)ENGINE = InnoDB DEFAULT CHARSET = utf8;

CREATE TABLE amazon_sp_params_config
(
    company    VARCHAR(255) NULL COMMENT '垂直ID:CR201706060001-汽配,CR201706080001-MRO',
    label      VARCHAR(255) NULL COMMENT '标签',
    value      VARCHAR(255) NULL COMMENT '参数名称',
    status     INT NULL COMMENT '状态:1-启用，2-禁用',
    modifiedBy VARCHAR(255) NULL COMMENT '修改人',
    modifiedOn DATETIME NULL COMMENT '修改日期',
    createdOn  DATETIME NULL COMMENT '创建人',
    createdBy  VARCHAR(255) NULL COMMENT '创建日期'
)ENGINE = InnoDB DEFAULT CHARSET = utf8;

CREATE TABLE amazon_sp_rule_config
(
    company        VARCHAR(255) NULL COMMENT '垂直ID',
    channel        VARCHAR(255) NULL COMMENT '渠道：amazon_us',
    sellerId       VARCHAR(255) NULL COMMENT '账号：amazon_us_ac1',
    brand          VARCHAR(255) NULL COMMENT '品牌: Barrina',
    skuType        VARCHAR(255) NULL COMMENT '允许创建创个的sku号类型，s号，a号',
    bidRule        VARCHAR(255) NULL COMMENT 'bid规则',
    egSku          VARCHAR(255) NULL COMMENT '示例sku',
    restfulRule   VARCHAR(255) NULL COMMENT 'campaign的restful规则 - 这里是数组',
    pomsRule     VARCHAR(255) NULL COMMENT 'campaign的poms规则 - 这里是数组',
    pipe           VARCHAR(255) NULL COMMENT 'campaign命名拼接符号',
    numSymbol      VARCHAR(255) NULL COMMENT 'campaign命名序号符号-如果没有默认和pipe一样',
    adGroupRule    VARCHAR(255) NULL COMMENT 'adgroup规则 - ',--根据'优先级'设置参数值获取的顺序，用英文逗号隔开，
    productRule    VARCHAR(255) NULL COMMENT 'product规则 - 根据sku获取',
    status         INT NULL COMMENT '状态:1-启用，2-禁用，3-未设置',
    modifiedBy     VARCHAR(255) NULL COMMENT '修改人',
    modifiedOn     DATETIME NULL COMMENT '修改日期',
    createdOn      DATETIME NULL COMMENT '创建人',
    createdBy      VARCHAR(255) NULL COMMENT '创建日期'
)ENGINE = InnoDB DEFAULT CHARSET = utf8;

CREATE TABLE amazon_sp_rule_config.bidRule
(
    type        VARCHAR(255) NULL COMMENT '广告类型',
    dailyBudget INT NULL COMMENT 'campaign广告预算',
    defaultBid  INT NULL COMMENT '默认bid值',
    minBid      INT NULL COMMENT 'bid最小值',
    maxBid      INT NULL COMMENT 'bid最大值',
    bidWarning  INT NULL COMMENT 'bid警告值',
)ENGINE = InnoDB DEFAULT CHARSET = utf8;


CREATE TABLE amazon_sp_rule_config.restfulRule
(
    parameterArray  VARCHAR(255) NULL COMMENT '参数值组合', --这是个数组
    targetParameter VARCHAR(255) NULL COMMENT '定义的参数值',
)ENGINE = InnoDB DEFAULT CHARSET = utf8;
CREATE TABLE amazon_sp_rule_config.restfulRule.parameterArray
(
    value  VARCHAR(255) NULL COMMENT '参数值', --和pomsRule.targetParameter的参数值对应
    label  VARCHAR(255) NULL COMMENT '标签',
    sort INT NULL COMMENT '排序',
)ENGINE = InnoDB DEFAULT CHARSET = utf8;

CREATE TABLE amazon_sp_rule_config.pomsRule
(
    parameterArray  VARCHAR(255) NULL COMMENT '参数值组合', --这是个数组
    targetParameter VARCHAR(255) NULL COMMENT '定义的参数值',
)ENGINE = InnoDB DEFAULT CHARSET = utf8;
CREATE TABLE amazon_sp_rule_config.pomsRule.parameterArray
(
    value  VARCHAR(255) NULL COMMENT '参数值', --和pomsRule.targetParameter的参数值对应
    label  VARCHAR(255) NULL COMMENT '标签',
    sort INT NULL COMMENT '排序',
)ENGINE = InnoDB DEFAULT CHARSET = utf8;


648bf6382e37dc1ce99e86fa
648bf6382e37dc1ce99e8702
648bf63bc2a075148455283a
648bf69dc2a0751484552b8d
648bf69e00803a1cdcc423a8
648bf6a200803a1cdcc423f4
648bf6c4c2a0751484552d51
648bf6c42e37dc1ce99e8c58
648bf6c72e37dc1ce99e8c76
648bf682ade57314737becca
648bf6822e37dc1ce99e896d
648bf686c2a0751484552a85
648bf6dec2a0751484552e5d
648bf6df00803a1cdcc42666
648bf6e1c2a0751484552e79
648bf6e9c2a0751484552ecf
648bf6e92e37dc1ce99e8dda
648bf6eb00803a1cdcc426f0
648bf7552e37dc1ce99e93d1
648bf757c2a075148455351b
648bf75900803a1cdcc42cff
648bf73d00803a1cdcc42bb1
648bf73dc2a07514845533e5
648bf73ec2a07514845533e9
648bf7892e37dc1ce99e9622
648bf789c2a0751484553795
648bf78ec2a0751484553814
648bf765ade57314737bf7f3
648bf765ade57314737bf7f5
648bf767c2a07514845535b9
648bf5d400803a1cdcc41b3f
648bf5d6c2a07514845523e8
648bf5dbc2a075148455240a
648bf615ade57314737be899
648bf617ade57314737be8b1
648bf61bade57314737be8d9
648bf60bc2a0751484552625
648bf60d2e37dc1ce99e8510
648bf61100803a1cdcc41e32
648bf6e4ade57314737bf0eb
648bf6e52e37dc1ce99e8dac
648bf6e72e37dc1ce99e8dd2
648bf7312e37dc1ce99e921c
648bf7322e37dc1ce99e9233
648bf736ade57314737bf62d
648bf6eec2a0751484552f0f
648bf6ef00803a1cdcc4270c
648bf6f000803a1cdcc42720
648bf779ade57314737bf94f
648bf77aade57314737bf957
648bf77d2e37dc1ce99e95a8
648bf6f400803a1cdcc42738
648bf6f52e37dc1ce99e8e60
648bf6f900803a1cdcc42768
648bf75bade57314737bf7a7
648bf75bade57314737bf7ab
648bf75d00803a1cdcc42d2e
648bf7902e37dc1ce99e9699
648bf79000803a1cdcc42f88
648bf7902e37dc1ce99e96a5
648bf65d2e37dc1ce99e8828
648bf65e00803a1cdcc42117
648bf65f2e37dc1ce99e884e
648bf6732e37dc1ce99e88f7
648bf6732e37dc1ce99e88f9
648bf675ade57314737bec5e
648bf6cac2a0751484552d85
648bf6ca2e37dc1ce99e8c82
648bf6cb2e37dc1ce99e8c8e
648bf71bade57314737bf475
648bf71e2e37dc1ce99e9139
648bf71f00803a1cdcc42a46
648bf737c2a07514845533ae
648bf73800803a1cdcc42b83
648bf739ade57314737bf63e
648bf705c2a0751484553051
648bf7082e37dc1ce99e8fa0
648bf70a2e37dc1ce99e8fc2
648bf74100803a1cdcc42bc6
648bf743c2a075148455341f
648bf745ade57314737bf6c8
648bf7a1ade57314737bfb51
648bf7a300803a1cdcc430aa
648bf7a5ade57314737bfb6c
648bf776c2a07514845536ca
648bf7762e37dc1ce99e955e
648bf77d2e37dc1ce99e95a6
648bf7832e37dc1ce99e95d6
648bf784ade57314737bf999
648bf787ade57314737bf9a5
648bf6bf00803a1cdcc4253a
648bf6c02e37dc1ce99e8c34
648bf6c22e37dc1ce99e8c40
648bf68700803a1cdcc42298
648bf68800803a1cdcc4229a
648bf68a00803a1cdcc422b0
648bf74d2e37dc1ce99e9381
648bf74d2e37dc1ce99e9383
648bf74f00803a1cdcc42c89
648bf6f4ade57314737bf157
648bf6f9ade57314737bf179
648bf6fac2a0751484552f75
648bf77e2e37dc1ce99e95b0
648bf781c2a0751484553730
648bf7832e37dc1ce99e95e2
648bf76ac2a07514845535d5
648bf76ac2a07514845535d7
648bf76c00803a1cdcc42dbb
648bf7202e37dc1ce99e9155
648bf722c2a0751484553295
648bf725ade57314737bf52f
648bf72bade57314737bf56c
648bf72c2e37dc1ce99e91d7
648bf730ade57314737bf5da
648bf7a2ade57314737bfb5d
648bf7a6c2a075148455394b
648bf7a8ade57314737bfb7a
648bf749ade57314737bf70d
648bf74900803a1cdcc42c3e
648bf74b2e37dc1ce99e9374
648bf5e7c2a075148455246b
648bf5e72e37dc1ce99e8371
648bf5e8ade57314737be69b
648bf6262e37dc1ce99e8614
648bf628c2a0751484552751
648bf62aade57314737be980
648bf70eade57314737bf37a
648bf7102e37dc1ce99e9044
648bf716c2a07514845531e9
648bf67d2e37dc1ce99e894f
648bf67fade57314737becbc
648bf6812e37dc1ce99e8965
648bf697c2a0751484552b69
648bf69800803a1cdcc42374
648bf69bc2a0751484552b79
648bf6fc2e37dc1ce99e8e74
648bf6fec2a0751484552fb5
648bf7022e37dc1ce99e8ef6
648bf6daade57314737bf098
648bf6dac2a0751484552e47
648bf6dec2a0751484552e55
648bf6afade57314737beef4
648bf6b0ade57314737bef02
648bf6b1ade57314737bef1a
648bf79500803a1cdcc42fd0
648bf79600803a1cdcc42fec
648bf797ade57314737bfafc
648bf76f00803a1cdcc42df3
648bf7702e37dc1ce99e94c4
648bf774ade57314737bf906
648bf5ccade57314737be581
648bf5ce2e37dc1ce99e820f
648bf5d2c2a07514845523a6
648bf6102e37dc1ce99e8526
648bf611ade57314737be87d
648bf614c2a0751484552671
648bf61b00803a1cdcc41e8c
648bf61cc2a07514845526bf
648bf61e2e37dc1ce99e8596
648bf6b4c2a0751484552ccb
648bf6b500803a1cdcc424d8
648bf6b6c2a0751484552ce7
648bf6322e37dc1ce99e86ca
648bf634c2a07514845527fb
648bf635ade57314737bea1a
648bf6482e37dc1ce99e8784
648bf64800803a1cdcc4206f
648bf64a00803a1cdcc42075
648bf66dade57314737bec2e
648bf66d2e37dc1ce99e88d3
648bf671c2a07514845529eb
648bf68d2e37dc1ce99e89c5
648bf68e00803a1cdcc422de
648bf68fc2a0751484552ae7
648bf7522e37dc1ce99e93bf
648bf753ade57314737bf768
648bf755c2a075148455350d
648bf692c2a0751484552b19
648bf69400803a1cdcc42348
648bf6982e37dc1ce99e8a69
648bf5ecade57314737be6d1
648bf5ec2e37dc1ce99e83ce
648bf5ef2e37dc1ce99e83de
648bf5f6c2a0751484552501
648bf5f72e37dc1ce99e8423
648bf5f92e37dc1ce99e842b
648bf5e1c2a0751484552433
648bf5e12e37dc1ce99e8333
648bf5e600803a1cdcc41bf7
648bf655ade57314737beb44
648bf656c2a07514845528f9
648bf65800803a1cdcc420d9
648bf5fbc2a0751484552525
648bf5fb2e37dc1ce99e8441
648bf5fe2e37dc1ce99e845b
648bf6a300803a1cdcc423fe
648bf6a4c2a0751484552bff
648bf6a700803a1cdcc42428
648bf6a8ade57314737beeac
648bf6aaade57314737beec6
648bf6ad2e37dc1ce99e8b94
648bf7162e37dc1ce99e90b4
648bf7182e37dc1ce99e90c3
648bf719c2a07514845531f9
648bf79a2e37dc1ce99e975b
648bf79b2e37dc1ce99e976b
648bf79cc2a07514845538df
648bf725ade57314737bf52d
648bf7262e37dc1ce99e9193
648bf72900803a1cdcc42aa2
648bf5c6c2a075148455232c
648bf5cbade57314737be57b
648bf5ccc2a0751484552364
648bf62b00803a1cdcc41f48
648bf62ec2a07514845527b3
648bf631ade57314737be9f6
648bf5c62e37dc1ce99e81cf
648bf5c62e37dc1ce99e81d1
648bf5c62e37dc1ce99e81d3
648bf5daade57314737be605
648bf5db00803a1cdcc41b71
648bf5ddade57314737be61a
648bf6d5c2a0751484552e0b
648bf6d5ade57314737bf076
648bf6d72e37dc1ce99e8d3a
648bf6662e37dc1ce99e88b2
648bf667c2a07514845529b5
648bf669ade57314737bec1e
648bf6cf00803a1cdcc425be
648bf6d0ade57314737bf028
648bf6d0ade57314737bf032
648bf64a2e37dc1ce99e878e
648bf64d2e37dc1ce99e87a8
648bf64fade57314737beb1c
648bf760ade57314737bf7d3
648bf76000803a1cdcc42d43
648bf762ade57314737bf7e7
648bf6bb00803a1cdcc42518
648bf6bbade57314737bef78
648bf6bdc2a0751484552d2b
648bf5f100803a1cdcc41c6c
648bf5f1c2a07514845524d9
648bf5f32e37dc1ce99e83f0
648bf60600803a1cdcc41d94
648bf607ade57314737be815
648bf60900803a1cdcc41dae
648bf63dc2a0751484552840
648bf63dade57314737bea68
648bf641ade57314737bea94
648bf601ade57314737be7bd
648bf601ade57314737be7c9
648bf606ade57314737be80d
648bf621ade57314737be915
648bf622ade57314737be91e
648bf625ade57314737be95c
648bf6682e37dc1ce99e88ba
648bf66b2e37dc1ce99e88c9
648bf66dc2a07514845529cf
648bf64f00803a1cdcc420a5
648bf651c2a07514845528d3
648bf654c2a07514845528ef
648bf643c2a0751484552862
648bf643ade57314737beaa2
648bf643ade57314737beaaa
648bf67a00803a1cdcc42237
648bf67a2e37dc1ce99e893b
648bf67b2e37dc1ce99e893f
648fe8f700803a1cdce51d8c
648fe8f700803a1cdce51d90
648fe8f8c2a07514847593ee
648fe9e3c2a0751484759bf9
648fe9e500803a1cdce52663
648fe9e600803a1cdce5267b
648fe9b700803a1cdce524a4
648fe9b9ade57314739c75a1
648fe9baade57314739c75b1
648fe854c2a0751484758d04
648fe859ade57314739c67a2
648fe85cc2a0751484758d3b
648fea3400803a1cdce52951
648fea3400803a1cdce52955
648fea36ade57314739c7a54
648fe954ade57314739c71ea
648fe955ade57314739c71ec
648fe957c2a0751484759712
648fe94500803a1cdce5203c
648fe94600803a1cdce52042
648fe948ade57314739c7176
648feb182e37dc1ce9bf0e06
648feb19ade57314739c82ea
648feb1d2e37dc1ce9bf0e3c
648fe9912e37dc1ce9beff5c
648fe99100803a1cdce52341
648fe993ade57314739c7457
648fe90f00803a1cdce51e5f
648fe910c2a07514847594b9
648fe91100803a1cdce51e6d
648fe90ac2a0751484759475
648fe90aade57314739c6f76
648fe90cc2a075148475948d
648fea932e37dc1ce9bf08d8
648fea98ade57314739c7dd5
648fea9fc2a075148475a2c7
648fe8cd2e37dc1ce9bef7b6
648fe8ce00803a1cdce51b9b
648fe8cf00803a1cdce51bab
648fe981ade57314739c73bd
648fe984c2a07514847598e8
648fe9852e37dc1ce9beff0e
648fe93fade57314739c7132
648fe94100803a1cdce52028
648fe9442e37dc1ce9befc6e
648fe84d00803a1cdce51611
648fe8512e37dc1ce9bef2a0
648fe85200803a1cdce51656
648fe809c2a07514847589d2
648fe80cc2a07514847589e8
648fe80d2e37dc1ce9beefd8
648fea38c2a0751484759f6c
648fea39c2a0751484759f72
648fea3b00803a1cdce52983
648fe89a2e37dc1ce9bef5a8
648fe89a00803a1cdce5190a
648fe89cc2a0751484758f84
648fe950c2a07514847596e2
648fe95100803a1cdce520bf
648fe95300803a1cdce520d3
648fe9c6c2a0751484759b00
648fe9c72e37dc1ce9bf014a
648fe9c9ade57314739c7655
648fe8c300803a1cdce51b3d
648fe8c3c2a07514847591a7
648fe8c6c2a07514847591b4
648fe89600803a1cdce518d7
648fe89600803a1cdce518d9
648fe89700803a1cdce518e2
648fe9ecc2a0751484759c49
648fe9edc2a0751484759c4b
648fe9f100803a1cdce526cf
648fe88c00803a1cdce5186a
648fe88cade57314739c698d
648fe88dc2a0751484758eea
648feada00803a1cdce52f04
648feadbc2a075148475a57d
648feaddade57314739c80b1
648fe935c2a07514847595de
648fe9372e37dc1ce9befbe0
648fe939c2a0751484759604
648fe9ddc2a0751484759bc8
648fe9e02e37dc1ce9bf025e
648fe9e2ade57314739c7737
648fe8b92e37dc1ce9bef70f
648fe8bac2a0751484759143
648fe8bcade57314739c6be8
648fea4dc2a075148475a051
648fea4f2e37dc1ce9bf06c7
648fea4f00803a1cdce52a4f
648fea422e37dc1ce9bf0645
648fea43c2a0751484759fde
648fea45ade57314739c7ae7
648feacd2e37dc1ce9bf0b24
648feacdade57314739c7fbf
648fead3ade57314739c8047
648fe7f000803a1cdce5127a
648fe7f1ade57314739c6351
648fe7f6ade57314739c6397
648feb00c2a075148475a6dc
648feb00c2a075148475a6e0
648feb03c2a075148475a6fd
648fe7e6ade57314739c62ba
648fe7e62e37dc1ce9beeddf
648fe7e8ade57314739c62ca
648fea3ec2a0751484759fc4
648fea3e00803a1cdce529ad
648fea40c2a0751484759fd4
648fe8812e37dc1ce9bef481
648fe881ade57314739c691a
648fe88400803a1cdce51813
648fe8dcade57314739c6d94
648fe8de00803a1cdce51c91
648fe8e100803a1cdce51c9b
648fe8772e37dc1ce9bef444
648fe879ade57314739c68cb
648fe87aade57314739c68d5
648fea1900803a1cdce5283d
648fea1bc2a0751484759e3b
648fea1e00803a1cdce52893
648fea48ade57314739c7afb
648fea4900803a1cdce529f9
648fea4a2e37dc1ce9bf06a7
648fea0c2e37dc1ce9bf0421
648fea0dc2a0751484759dad
648fea0eade57314739c78d0
648feb1e2e37dc1ce9bf0e44
648feb202e37dc1ce9bf0e4a
648feb222e37dc1ce9bf0e6e
648fe8f1ade57314739c6e83
648fe8f200803a1cdce51d57
648fe8f4c2a07514847593cd
648fe862ade57314739c67ef
648fe86500803a1cdce516e5
648fe8652e37dc1ce9bef35d
648fe872c2a0751484758e12
648fe87300803a1cdce51771
648fe877c2a0751484758e3a
648fe9e82e37dc1ce9bf02c4
648fe9eb2e37dc1ce9bf02dc
648fe9eb2e37dc1ce9bf02e2
648feaabc2a075148475a34b
648feaac2e37dc1ce9bf09c6
648feab3c2a075148475a389
648fe8ed00803a1cdce51d3c
648fe8ed00803a1cdce51d3e
648fe8f0c2a07514847593ac
648fe974c2a075148475985e
648fe9752e37dc1ce9befe82
648fe9762e37dc1ce9befe88
648feb05c2a075148475a72f
648feb05c2a075148475a731
648feb0aade57314739c827e
648fea1fade57314739c7970
648fea202e37dc1ce9bf04db
648fea22ade57314739c798a
648fe8ae2e37dc1ce9bef671
648fe8ae2e37dc1ce9bef67d
648fe8b300803a1cdce51a6d
648fe93200803a1cdce51f8a
648fe93200803a1cdce51f8e
648fe93500803a1cdce51fa0
648feae5c2a075148475a5c7
648feae5ade57314739c80f7
648feae8ade57314739c8119
648fea882e37dc1ce9bf0872
648fea8800803a1cdce52bcf
648fea8ec2a075148475a248
648fe8a4ade57314739c6aa6
648fe8a52e37dc1ce9bef628
648fe8a6c2a0751484758fec
648fe8e1ade57314739c6dc8
648fe8e100803a1cdce51c9f
648fe8e500803a1cdce51cb9
648fe9a92e37dc1ce9bf0018
648fe9a900803a1cdce5241e
648fe9ab00803a1cdce5243a
648fe99eade57314739c74b7
648fe9a000803a1cdce523dd
648fe9a22e37dc1ce9beffee
648fe959ade57314739c7228
648fe95b2e37dc1ce9befd63
648fe95ec2a075148475978a
648fe90500803a1cdce51df7
648fe905c2a0751484759455
648fe907ade57314739c6f68
648feaba2e37dc1ce9bf0a3e
648feabbade57314739c7ed7
648feac02e37dc1ce9bf0a98
648feaf72e37dc1ce9bf0d2c
648feaf72e37dc1ce9bf0d2e
648feaf9ade57314739c81ee
648fe83fade57314739c669f
648fe84100803a1cdce515ab
648fe84300803a1cdce515c3
648fe8c800803a1cdce51b65
648fe8c8ade57314739c6c6b
648fe8ca00803a1cdce51b84
648fea012e37dc1ce9bf03b3
648fea0200803a1cdce5276f
648fea042e37dc1ce9bf03d7
648fe96900803a1cdce521ef
648fe96ac2a07514847597f2
648fe96dc2a0751484759810
648fe9a42e37dc1ce9befff2
648fe9a52e37dc1ce9befffc
648fe9a700803a1cdce52410
648fe89fc2a0751484758f95
648fe89fc2a0751484758f9f
648fe8a0ade57314739c6a78
648fea1000803a1cdce527f3
648fea16ade57314739c7916
648fea17ade57314739c7920
648fe8b400803a1cdce51a7d
648fe8b42e37dc1ce9bef6d5
648fe8b7ade57314739c6b9b
648fe93aade57314739c70fa
648fe93bc2a075148475960c
648fe93d00803a1cdce51fda
648fea1100803a1cdce527f9
648fea1300803a1cdce5281b
648fea16c2a0751484759dfb
648fe9262e37dc1ce9befb60
648fe927ade57314739c704e
648fe92a00803a1cdce51f58
648fe90000803a1cdce51dcb
648fe90100803a1cdce51dd9
648fe903ade57314739c6f36
648feb2b2e37dc1ce9bf0ee8
648feb302e37dc1ce9bf0f2c
648feb322e37dc1ce9bf0f48
648fe917ade57314739c6fce
648fe919ade57314739c6fd8
648fe91bc2a0751484759512
648fe891ade57314739c69bc
648fe8912e37dc1ce9bef532
648fe892c2a0751484758f18
648fe97700803a1cdce5227d
648fe97900803a1cdce52295
648fe97b2e37dc1ce9befec8
648fe9ae00803a1cdce52452
648fe9af2e37dc1ce9bf0064
648fe9b000803a1cdce52462
648fe91c00803a1cdce51ebb
648fe91ec2a075148475951f
648fe91fc2a0751484759532
648feafa00803a1cdce53052
648feafcade57314739c8210
648feaffc2a075148475a6d6
648fe9d6c2a0751484759b82
648fe9d62e37dc1ce9bf01f2
648fe9d7ade57314739c76d1
648fe8682e37dc1ce9bef36d
648fe86a00803a1cdce51723
648fe86cc2a0751484758de2
648fe94bc2a07514847596a8
648fe94bc2a07514847596aa
648fe94d00803a1cdce5209a
648fe91400803a1cdce51e7f
648fe915c2a07514847594d9
648fe916ade57314739c6fc6
648feab0ade57314739c7e8b
648feab400803a1cdce52d0d
648feab92e37dc1ce9bf0a34
648fe96f2e37dc1ce9befe37
648fe96fc2a075148475981c
648fe970c2a075148475982c
648fe85fade57314739c67c4
648fe85f2e37dc1ce9bef305
648fe86500803a1cdce516e7
648fe99600803a1cdce5237d
648fe997c2a0751484759986
648fe998ade57314739c7477
648fe9fcc2a0751484759ce2
648fe9fc2e37dc1ce9bf0383
648fe9fe00803a1cdce52741
648fea06c2a0751484759d55
648fea072e37dc1ce9bf03df
648fea08c2a0751484759d65
648fe819ade57314739c650d
648fe81a2e37dc1ce9bef054
648fe81cade57314739c6531
648fe96500803a1cdce521c7
648fe966c2a07514847597e0
648fe968ade57314739c72e1
648fe9d1c2a0751484759b44
648fe9d1c2a0751484759b46
648fe9d32e37dc1ce9bf01de
648fea882e37dc1ce9bf0874
648fea8dade57314739c7d5f
648fea90c2a075148475a24e
648fe9cc2e37dc1ce9bf0194
648fe9ccade57314739c766f
648fe9cec2a0751484759b3a
648fe9f700803a1cdce52709
648fe9f8c2a0751484759cc3
648fe9fbade57314739c7819
648fe84700803a1cdce515eb
648fe84bade57314739c6711
648fe84c2e37dc1ce9bef257
648fe855c2a0751484758d0a
648fe859ade57314739c67a0
648fe85ac2a0751484758d2e
648fe8a8ade57314739c6abf
648fe8a900803a1cdce5199e
648fe8ab2e37dc1ce9bef651
648fea57c2a075148475a098
648fea582e37dc1ce9bf070b
648fea58c2a075148475a0a4
648fe80eade57314739c649c
648fe80e2e37dc1ce9beefe4
648fe81300803a1cdce513c2
648fe97cc2a075148475989a
648fe97eade57314739c7397
648fe97fade57314739c73a7
648fe844c2a0751484758c84
648fe8462e37dc1ce9bef225
648fe849c2a0751484758cad
648fe82bade57314739c65ee
648fe82d00803a1cdce514f2
648fe83000803a1cdce51515
648feb0ac2a075148475a759
648feb0a00803a1cdce530da
648feb0c2e37dc1ce9bf0dc6
648fe81e00803a1cdce5143b
648fe81fc2a0751484758ab8
648fe8202e37dc1ce9bef090
648fe832c2a0751484758bc9
648fe83200803a1cdce51529
648fe835ade57314739c6649
648fe9bd00803a1cdce524e7
648fe9bdc2a0751484759ab0
648fe9bfade57314739c75d9
648fea2e00803a1cdce52915
648fea2eade57314739c7a04
648fea31ade57314739c7a14
648fe999ade57314739c747d
648fe99b2e37dc1ce9beffa4
648fe99dc2a07514847599ba
648fe7f9c2a07514847588b4
648fe7fdc2a07514847588e5
648fe7fd00803a1cdce5130d
648fe9892e37dc1ce9beff1c
648fe98a2e37dc1ce9beff1e
648fe98fade57314739c7425
648fe886c2a0751484758e9a
648fe88700803a1cdce51828
648fe8882e37dc1ce9bef4c9
648fe9db2e37dc1ce9bf023e
648fe9dcc2a0751484759bb6
648fe9dd2e37dc1ce9bf0250
648fe9b200803a1cdce5246c
648fe9b300803a1cdce52470
648fe9b52e37dc1ce9bf00b2
648fe8d22e37dc1ce9bef813
648fe8d8ade57314739c6d77
648fe8daade57314739c6d7e
648feb1400803a1cdce53145
648feb15ade57314739c82c4
648feb17ade57314739c82de
648fe8d3c2a075148475924c
648fe8d62e37dc1ce9bef855
648fe8d8ade57314739c6d73
648feb23c2a075148475a835
648feb2600803a1cdce531d9
648feb2a00803a1cdce5321d
648fea2400803a1cdce528c3
648fea26ade57314739c79c8
648fea28ade57314739c79de
648fea9bade57314739c7ded
648feaa0c2a075148475a2cf
648feaa2ade57314739c7e0f
648fe7eb00803a1cdce51235
648fe7ec2e37dc1ce9beee22
648fe7efade57314739c6347
648feb10c2a075148475a783
648feb1000803a1cdce5311b
648feb11c2a075148475a797
648fe7e1ade57314739c627a
648fe7e100803a1cdce511c5
648fe7e5ade57314739c62b6
648fe8fc00803a1cdce51dbd
648fe8fcade57314739c6f0a
648fe8fd2e37dc1ce9bef9ee
648fea5200803a1cdce52a67
648fea52c2a075148475a06d
648fea55ade57314739c7b87
648fead42e37dc1ce9bf0bc6
648fead62e37dc1ce9bf0bdc
648fead92e37dc1ce9bf0bfc
648fe7d7ade57314739c6209
648fe7db2e37dc1ce9beed73
648fe7deade57314739c6248
648fe7feade57314739c63f7
648fe80100803a1cdce51327
648fe80300803a1cdce51349
648feab0ade57314739c7e89
648feab300803a1cdce52cfd
648feab7c2a075148475a39b
648fe837ade57314739c6650
648fe837ade57314739c6652
648fe83800803a1cdce51564
648fe814c2a0751484758a41
648fe8142e37dc1ce9bef009
648fe815ade57314739c64d1
648fea5d00803a1cdce52ad3
648fea5e2e37dc1ce9bf0759
648fea5f00803a1cdce52ae7
648fe828ade57314739c65cb
648fe82c2e37dc1ce9bef12b
648fe82d2e37dc1ce9bef132
648fe87cc2a0751484758e56
648fe87d00803a1cdce517d3
648fe87f2e37dc1ce9bef47a
648fe83cc2a0751484758c2d
648fe83dade57314739c6694
648fe83ec2a0751484758c3e
648fe922ade57314739c7034
648fe92300803a1cdce51f05
648fe924c2a075148475955a
648fe8e72e37dc1ce9bef8f2
648fe8e7ade57314739c6dfc
648fe8ecade57314739c6e52
648fe7ceade57314739c61ce
648fe7cf00803a1cdce51128
648fe7cf2e37dc1ce9beed15
648fe7d6ade57314739c6203
648fe7db00803a1cdce51185
648fe7dc00803a1cdce51189
648fe92d2e37dc1ce9befb94
648fe92e00803a1cdce51f78
648fe92eade57314739c709b
648fea29c2a0751484759edd
648fea2bc2a0751484759ee7
648fea2d2e37dc1ce9bf0555
648fe8bfade57314739c6bfe
648fe8c0ade57314739c6c12
648fe8c1ade57314739c6c26
648fe95f2e37dc1ce9befdbb
648fe9602e37dc1ce9befdbf
648fe96300803a1cdce521b7
648feadfade57314739c80bb
648feae000803a1cdce52f3a
648feae3c2a075148475a5c1
648fe7f200803a1cdce51285
648fe7f700803a1cdce512be
648fe7f8ade57314739c63aa
648fe86dade57314739c6861
648fe870c2a0751484758e0a
648fe8712e37dc1ce9bef419
648fe9f2ade57314739c77b7
648fe9f2c2a0751484759c75
648fe9f4c2a0751484759c7d
648feaea00803a1cdce52f9e
648feaeb2e37dc1ce9bf0c8e
648feaf0c2a075148475a68d
648feaf100803a1cdce53016
648feaf200803a1cdce53024
648feaf3c2a075148475a69f
648fea96c2a075148475a28f
648fea9bc2a075148475a2b7
648feaa2ade57314739c7e05
648fe823ade57314739c656b
648fe8242e37dc1ce9bef0b2
648fe82500803a1cdce51483
648feaa8ade57314739c7e45
648feaa9c2a075148475a33d
648feaa900803a1cdce52ccb
648feac82e37dc1ce9bf0af4
648feac800803a1cdce52ddf
648feac9c2a075148475a453
648feac02e37dc1ce9bf0aa2
648feac100803a1cdce52da5
648feac700803a1cdce52ddd
6495c080c2a0751484aac243
6495c08200803a1cdc1a12b5
6495c0862e37dc1ce9f3f410
648fe7d2c2a0751484758722
648fe7d6c2a0751484758751
648fe7d7ade57314739c6205
