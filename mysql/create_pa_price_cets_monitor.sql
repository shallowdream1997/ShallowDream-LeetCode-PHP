CREATE TABLE pa_price_cets_monitor
(
    monitorname            VARCHAR(225) NULL COMMENT '监控批次名',
    channel                VARCHAR(225) NULL COMMENT '价格测试的渠道',
    sellerid               VARCHAR(255) NULL COMMENT '价格测试的账号',
    sold                   INT NULL COMMENT '总的sold',
    pl                     INT NULL COMMENT '总PL',
    gr                     INT NULL COMMENT '总grossrevenue',
    margin                 INT NULL COMMENT '总margin',
    testBeginTime          DATETIME NULL COMMENT '测试开始时间',
    testEndTime            DATETIME NULL COMMENT '测试结束时间',
    operationName          VARCHAR NULL COMMENT '价格测试的操作人',
    weekday1_price_rules   VARCHAR NULL COMMENT '第一周调价规则',
    weekday1_sku_num       INT NULL COMMENT '第一周统计的skuNum数量',
    weekday1_sold          INT NULL COMMENT '第一周的sold总数',
    weekday1_pl            INT NULL COMMENT '第一周的pl数',
    weekday1_gr            INT NULL COMMENT '第一周的gr数',
    weekday1_margin        DOUBLE NULl COMMENT '第一周的margin',
    weekday1_sales_number  INT NULL COMMENT '第一周售动数(卖出的数量)',
    weekday1_sales_percent DOUBLE NULL COMMENT '第一周售动率(weekday1_sales_number/weekday1_sku_num)',
    weekday2_price_rules   VARCHAR NULL COMMENT '第二周调价规则',
    weekday2_sku_num       INT NULL COMMENT '第二周统计的skuNum数量',
    weekday2_sold          INT NULL COMMENT '第二周的sold总数',
    weekday2_pl            INT NULL COMMENT '第二周的pl数',
    weekday2_gr            INT NULL COMMENT '第二周的gr数',
    weekday2_margin        DOUBLE NULl COMMENT '第二周的margin',
    weekday2_sales_number  INT NULL COMMENT '第二周售动数(卖出的数量)',
    weekday2_sales_percent DOUBLE NULL COMMENT '第二周售动率(weekday2_sales_number/weekday2_sku_num)',
)ENGINE = InnoDB DEFAULT CHARSET = utf8;


CREATE TABLE pa_price_cets_monitor
(
    monitor_name          VARCHAR(225) NULL COMMENT '监控批次名',
    channel               VARCHAR(225) NULL COMMENT '价格测试的渠道',
    seller                VARCHAR(255) NULL COMMENT '价格测试的账号',
    tag                   VARCHAR NULL COMMENT 'tag',
    testBeginTime         DATETIME NULL COMMENT '测试开始时间',
    testEndTime           DATETIME NULL COMMENT '测试结束时间',
    operationName         VARCHAR NULL COMMENT '价格测试的操作人',
    weekday               INT NULL COMMENT '周数',
    weekday_price_rules   VARCHAR NULL COMMENT '周调价规则',
    weekday_sku_num       INT NULL COMMENT '周统计的skuNum数量',
    weekday_sold          INT NULL COMMENT '周的sold总数',
    weekday_pl            INT NULL COMMENT '周的pl数',
    weekday_gr            INT NULL COMMENT '周的gr数',
    weekday_margin        DOUBLE NULl COMMENT '周的margin',
    weekday_sales_number  INT NULL COMMENT '周售动数(卖出的数量)',
    weekday_sales_percent DOUBLE NULL COMMENT '周售动率(weekday2_sales_number/weekday2_sku_num)',
)ENGINE = InnoDB DEFAULT CHARSET = utf8;


CREATE TABLE pa_price_test_monitor
(
    monitorName   VARCHAR(255) NULL COMMENT '监控批次名',
    channel       VARCHAR(255) NULL COMMENT '渠道',
    sellerId      VARCHAR(255) NULL COMMENT '账号',
    tag           VARCHAR(255) NULL COMMENT '标准(90天标准/180天标准/270天标准)',
    priceRules    VARCHAR(255) NULL COMMENT '调价标准(调价规则)',
    buyboxRate    INT NULL COMMENT 'buybox率',
    sold          INT NULL COMMENT 'sold',
    pl            INT NULL COMMENT 'pl',
    gr            INT NULL COMMENT 'gr',
    margin        INT NULL COMMENT 'margin',
    testStartTime DATETIME NULL COMMENT '测试开始时间',
    testEndTime   DATETIME NULL COMMENT '测试结束时间',
    operator      VARCHAR(255) NULL COMMENT '操作人',
)ENGINE = InnoDB DEFAULT CHARSET = utf8;

CREATE TABLE mro_fcu_list
(
    sequenceId    VARCHAR(255) NULL COMMENT '清单编号',
    batch         VARCHAR(255) NULL COMMENT '自然周-系统自动生成;年份加两位周数',
    mroBatch      VARCHAR(255) NULL COMMENT '批次号',
    createdBy     VARCHAR(255) NULL COMMENT '创建人',
    createdOn     DATETIME NULL COMMENT '创建时间',
    modifiedBy    VARCHAR(255) NULL COMMENT '修改人',
    modifiedOn    DATETIME NULL COMMENT '修改时间',
    cancler       VARCHAR(255) NULL COMMENT '作废人',
    cancleOn      DATETIME NULL COMMENT '作废时间',
    status        VARCHAR(255) NULL COMMENT '清单状态：1-新FCU清单；2-产品经理组长审核；3-审核通过；4-部分创建成功；5-创建成功；6-创建失败；0-驳回；-2-作废',
    managerBy     VARCHAR(255) NULL COMMENT '产品经理组长审核人',
    managerOn     DATETIME NULL COMMENT '产品经理组长审核时间',
    managerResult VARCHAR(255) NULL COMMENT '产品经理组长审核结果:1-通过(status=3)，0-驳回(status=0)',
    FCUmap        VARCHAR(255) NULL COMMENT 'FCU批次号：FCUbatch、FCU创建状态:status',
)ENGINE = InnoDB DEFAULT CHARSET = utf8;


CREATE TABLE mro_fcu_list_detail
(
    marin_id                  VARCHAR(255) NULL COMMENT '主表id',
    fcu_index                 VARCHAR(255) NULL COMMENT 'fcu序号',
    make_sure                 VARCHAR(255) NULL COMMENT '是否已确认',
    fcuId                     VARCHAR(255) NULL COMMENT 'fcu号',
    title                     VARCHAR(255) NULL COMMENT '中文标题',
    channel                   VARCHAR(255) NULL COMMENT '渠道信息',
    productlineid             VARCHAR(255) NULL COMMENT 'FCU产品线id',
    productlinename           VARCHAR(255) NULL COMMENT 'FCU产品线名称',
    fcu_first_assembly_amount INT NULL COMMENT 'FCU首次组装数量',
    index_status              VARCHAR(255) NULL COMMENT '创建状态',
    status                    VARCHAR(255) NULL COMMENT '组装状态:若fcuid当前中国仓库存大于等于1，则组装状态为“1-完成”',
    skuId                     VARCHAR(255) NULL COMMENT '数组包括skuId、quantity、sku_cost、sku_cost_discount、sku_cost_after_discount',
    createdBy                 VARCHAR(255) NULL COMMENT '创建人',
    createdOn                 DATETIME NULL COMMENT '创建时间',
    modifiedBy                VARCHAR(255) NULL COMMENT '修改人',
    modifiedOn                DATETIME NULL COMMENT '修改时间',
)ENGINE = InnoDB DEFAULT CHARSET = utf8;

CREATE TABLE mro_fcu_batch
(
    batch    VARCHAR(255) NULL COMMENT '自然周',
    mroBatch VARCHAR(255) NULL COMMENT 'fcu批次号',
)ENGINE = InnoDB DEFAULT CHARSET = utf8;


CREATE TABLE pa_walmart_weekly_data_category
(
    channel          VARCHAR(255) NULL COMMENT '销售渠道',
    sellerId         VARCHAR(255) NULL COMMENT '销售账号',
    cncategory       VARCHAR(255) NULL COMMENT '中文分类全路径--产品线',
    catlevel1        VARCHAR(255) NULL COMMENT '一级中文分类',
    catlevel2        VARCHAR(255) NULL COMMENT '二级中文分类',
    leafcategoryid   VARCHAR(255) NULL COMMENT '末级分类ID',
    skunumber        INT NULL COMMENT '该产品线含是sku数量（sku去重计数）',
    sold1            INT NULL COMMENT '上个自然周：该末级分类的sold',
    sales1           INT NULL COMMENT '上个自然周：该末级分类的sales',
    pl1              INT NULL COMMENT '上个自然周：该末级分类的PL',
    margin1          INT NULL COMMENT '上个自然周：改末级分类的margin',
    normalmargin1    INT NULL COMMENT '上个自然周：normal类型的PL/normal sales',
    eff1             INT NULL COMMENT '上个自然周：销售效率',
    sold2            INT NULL COMMENT '上上个自然周：该末级分类的sold',
    sales2           INT NULL COMMENT '上上个自然周：该末级分类的sales',
    pl2              INT NULL COMMENT '上上个自然周：该末级分类的PL',
    margin2          INT NULL COMMENT '上上个自然周：改末级分类的margin',
    normalmargin2    INT NULL COMMENT '上上个自然周：normal类型的PL/normal sales',
    eff2             INT NULL COMMENT '上上个自然周：销售效率',
    diffsold         INT NULL COMMENT 'diffSold=sold1-sold2',
    diffsales        INT NULL COMMENT 'diffSales=sales1-sales2',
    diffpl           INT NULL COMMENT 'diffPL=PL1-PL2',
    diffmargin       INT NULL COMMENT 'diffMargin=margin1-margin2',
    diffnormalmargin INT NULL COMMENT 'diffNormalMargin=normalMargin1-normalMargin2',
    diffeff          INT NULL COMMENT 'diffEff=eff1-eff2',
    createdby        DATETIME NULL COMMENT '创建人',
    createdon        DATETIME NULL COMMENT '创建时间',
    modifiedby       DATETIME NULL COMMENT '修改人',
    modifiedon       DATETIME NULL COMMENT '修改时间'
)ENGINE = InnoDB DEFAULT CHARSET = utf8;


CREATE TABLE non_languages_source_data
(
    year         INI NULL COMMENT '年份',
    verticalname VARCHAR(255) NULL COMMENT '商家',
    channel      VARCHAR(255) NULL COMMENT '渠道',
    m1           INT NULL COMMENT '1月份',
    m2           INT NULL COMMENT '2月份',
    m3           INT NULL COMMENT '3月份',
    m4           INT NULL COMMENT '4月份',
    m5           INT NULL COMMENT '5月份',
    m6           INT NULL COMMENT '6月份',
    m7           INT NULL COMMENT '7月份',
    m8           INT NULL COMMENT '8月份',
    m9           INT NULL COMMENT '9月份',
    m10          INT NULL COMMENT '10月份',
    m11          INT NULL COMMENT '11月份',
    m12          INT NULL COMMENT '12月份',
    turnover     INT NULL COMMENT '目标值',
    forecastDate DATETIME NULL COMMENT '数据时间',
    createdBy    DATETIME NULL COMMENT '创建人',
    createdOn    DATETIME NULL COMMENT '创建时间',
    modifiedBy   DATETIME NULL COMMENT '修改人',
    modifiedOn   DATETIME NULL COMMENT '修改时间'
)ENGINE = InnoDB DEFAULT CHARSET = utf8;

CREATE TABLE pa_sku_life_cycle_management_log
(
    sku VARCHAR(255) NULL COMMENT 'sku',
    checkData DATETIME NULL COMMENT '系统检查时间',
    lifeCycle VARCHAR(255) NULL COMMENT '生命周期',
    status VARCHAR(255) NULL COMMENT '状态',
    remarks VARCHAR(255) NULL COMMENT '处理内容',
    createdBy VARCHAR(255) NULL COMMENT '处理人',
    createdOn DATETIME NULL COMMENT '处理时间',
    modifiedBy VARCHAR(255) NULL COMMENT '修改人',
    modifiedOn DATETIME NULL COMMENT '修改时间'
)ENGINE = InnoDB DEFAULT CHARSET = utf8;

CREATE TABLE pa_sku_life_cycle_management
(
    skuid VARCHAR(255) NULL COMMENT 'skuId（唯一）',
    cntitle VARCHAR(255) NULL COMMENT '产品中文名称',
    salesusername VARCHAR(255) NULL COMMENT '销售人员',
    developerusername VARCHAR(255) NULL COMMENT '开发人员',
    publishtime VARCHAR(255) NULL COMMENT '资料发布时间',
    fittype VARCHAR(255) NULL COMMENT '适配类型',
    limitquantity VARCHAR(255) NULL COMMENT '是否限量：1-限量 0-不限量',
    estimatemonthsold INT NULL COMMENT '月预估量（若estimateMonthSold="NULL"，则默认为1.5）',
    cebillno VARCHAR(255) NULL COMMENT 'CE单号',
    sold_30days INT NULL COMMENT '近30天sold',
    stockqty INT NULL COMMENT '当前总库存(各仓在仓及在途)',
    onlinedays INT NULL COMMENT '上架天数',
    estimatemontheff INT NULL COMMENT '预估月eff',
    montheff INT NULL COMMENT '实际月eff',
    stockratio INT NULL COMMENT '库存比=当前总库存/近30天sold',
    issatisfied VARCHAR(255) COMMENT '月eff是否符合预期：Y/N',
    score VARCHAR(255) COMMENT '培养期得分',
    lifecycle VARCHAR(255) COMMENT '生命周期',
    status VARCHAR(255) COMMENT '状态',
    isimproved VARCHAR(255) COMMENT'是否需要优化',
    checkdate DATETIME NULL COMMENT '系统检查日期',
    advise VARCHAR(255) NULL COMMENT '处理建议',
    createdBy VARCHAR(255) NULL COMMENT '处理人',
    createdOn DATETIME NULL COMMENT '处理时间',
    modifiedBy VARCHAR(255) NULL COMMENT '修改人',
    modifiedOn DATETIME NULL COMMENT '修改时间'
)ENGINE = InnoDB DEFAULT CHARSET = utf8;



CREATE TABLE pa_price_test_monitor
(
    monitorname VARCHAR(255) NULL COMMENT '监控批次',
    channel VARCHAR(255) NULL COMMENT '渠道',
    sellerid VARCHAR(255) NULL COMMENT '账号',
    tag VARCHAR(255) NULL COMMENT 'tag',
    pricerules VARCHAR(255) NULL COMMENT '价格测试规则',
    skunum INT NULL COMMENT 'sku数量',
    sold INT NULL COMMENT 'sold',
    gr INT NULL COMMENT 'gr',
    margin INT NULL COMMENT 'margin',
    pl INT NULL COMMENT 'pl',
    soldsku INT NULL COMMENT '售动sku数',
    soldskurate INT NULL COMMENT '售动率',
    teststarttime DATETIME NULL COMMENT '测试开始时间',
    testendtime DATETIME NULL COMMENT '测试结束时间',
    operator VARCHAR(255) NULL COMMENT '操作人',
    createdby VARCHAR(255) NULL COMMENT '创建人',
    modifiedby VARCHAR(255) NULL COMMENT '更新人',
    createdon DATETIME NULL COMMENT '创建时间',
    modifiedon DATETIME NULL COMMENT '更新时间'
)ENGINE = InnoDB DEFAULT CHARSET = utf8;


CREATE TABLE pa_sku_refund_detail
(
    normal_pl_month INT NULL COMMENT 'sku+月的pl',
    supplier_status VARCHAR(255) NULL COMMENT '供应商状态',
    refund_sales INT NULL COMMENT 'sku+订单 pltype为normalsales（gr)',
    the_year VARCHAR(255) NULL COMMENT 'pldata对应的年',
    complainttypeid INT NULL COMMENT '投诉类型id',
    sku_year VARCHAR(255) NULL COMMENT 'sku所属年份（开发年份）',
    normal_margin_week INT NULL COMMENT 'sku+周的margin',
    refund_qty INT NULL COMMENT 'sku+订单的退款件数',
    sellerid VARCHAR(255) NULL COMMENT '账号',
    developer VARCHAR(255) NULL COMMENT '开发人员',
    refund_pl INT NULL COMMENT 'sku+订单 pltype为refund的netpl',
    skuid VARCHAR(255) NULL COMMENT 'skuId',
    trace_man VARCHAR(255) NULL COMMENT '销售人员',
    normal_sold_month INT NULL COMMENT 'sku+月的sold',
    last_supplier VARCHAR(255) NULL COMMENT '最新的供应商',
    normal_margin_month INT NULL COMMENT 'sku+月的margin',
    platform VARCHAR(255) NULL COMMENT '平台',
    pldate VARCHAR(255) NULL COMMENT 'pl产生的日期',
    limitquantity VARCHAR(255) NULL COMMENT '是否限量',
    channel VARCHAR(255) NULL COMMENT '渠道',
    normal_sales_month INT NULL COMMENT 'sku+月的sales',
    publish_time DATETIME NULL COMMENT '出版时间',
    complainttypelabel VARCHAR(255) NULL COMMENT '投诉类型标签',
    complaintdate VARCHAR(255) NULL COMMENT '投诉产生的日期',
    total_date VARCHAR(255) NULL COMMENT '最后更新时间',
    normal_sold_week INT NULL COMMENT 'sku+周的sold',
    normal_sales_week INT NULL COMMENT 'sku+周的sales',
    category_name VARCHAR(255) NULL COMMENT '分类全路径',
    business_type VARCHAR(255) NULL COMMENT '业务类型',
    shipmentid VARCHAR(255) NULL COMMENT '订单id',
    normal_pl_week INT NULL COMMENT 'sku+周的pl',
    week_of_year VARCHAR(255) NULL COMMENT 'pldata对应的周',
    month_of_year VARCHAR(255) NULL COMMENT 'pldata对应的月',
    complaintqty INT NULL COMMENT '投诉件数',
    categoryfullpath VARCHAR(255) NULL COMMENT '投诉类型',
    createdby VARCHAR(255) NULL COMMENT '创建人',
    modifiedby VARCHAR(255) NULL COMMENT '更新人',
    createdon DATETIME NULL COMMENT '创建时间',
    modifiedon DATETIME NULL COMMENT '更新时间'
)ENGINE = InnoDB DEFAULT CHARSET = utf8;


CREATE table pa_supply_oos_analysis
(
    total_date                     VARCHAR(255) NULL COMMENT '更新日期',
    sku_id                         VARCHAR(255) NULL COMMENT 'skuid',
    stock_group                    VARCHAR(255) NULL COMMENT '仓库',
    cets_over_sea_stock_qty        VARCHAR(255) NULL COMMENT 'cets仓库',
    oos_mark                       VARCHAR(255) NULL COMMENT 'oos_mark',
    oos_start_date                 VARCHAR(255) NULL COMMENT 'oos开始日期',
    oos_days                       VARCHAR(255) NULL COMMENT 'oos天数',
    solt_createdon                 VARCHAR(255) NULL COMMENT '库位创建日期',
    slotid                         VARCHAR(255) NULL COMMENT '库位id',
    first_move_in_date             VARCHAR(255) NULL COMMENT '首次入库日期',
    advice_purchase_qty            VARCHAR(255) NULL COMMENT '建议补货数量',
    isadvice_purchase              VARCHAR(255) NULL COMMENT '是否提醒补货',
    firstmovedate_lenth            VARCHAR(255) NULL COMMENT '首次入库距离移库周期时长',
    last_supplier_status           VARCHAR(255) NULL COMMENT '最后供应商状态',
    vertical                       VARCHAR(255) NULL COMMENT 'Vertical(所属垂直)',
    leadtimebyairforrp             VARCHAR(255) NULL COMMENT '空运补货交货周期',
    leadtimebyshipforrp            VARCHAR(255) NULL COMMENT '海运补货交货周期',
    typeorigin                     VARCHAR(255) NULL COMMENT '移库方式',
    type_origin                    VARCHAR(255) NULL COMMENT '移库补货交货周期',
    typeorigin_date                VARCHAR(255) NULL COMMENT '补货提醒日',
    pmo_status                     VARCHAR(255) NULL COMMENT 'PMO单状态',
    pmo_billno                     VARCHAR(255) NULL COMMENT 'pmo单号',
    in_billno                      VARCHAR(255) NULL COMMENT '入库单号',
    acceptedfinishon               VARCHAR(255) NULL COMMENT 'SZ仓入库上架时间',
    total_return_qty               VARCHAR(255) NULL COMMENT 'total_return_qty(退货数量)',
    sz_upload_days                 VARCHAR(255) NULL COMMENT '提醒补货到SZ仓上架时长',
    isreplenishment                VARCHAR(255) NULL COMMENT 'isreplenishment(当天有无补货)',
    upload_is_timeout              VARCHAR(255) NULL COMMENT 'SZ仓上架是否超时',
    result_tag                     VARCHAR(255) NULL COMMENT '断货原因',
    afn_warehouse_quantity         VARCHAR(255) NULL COMMENT '亚马逊库存数量',
    afn_unsellable_quantity        VARCHAR(255) NULL COMMENT '亚马逊物流不可售数量',
    afn_reserved_quantity          VARCHAR(255) NULL COMMENT '亚马逊物流预留数量',
    afn_inbound_working_quantity   VARCHAR(255) NULL COMMENT '亚马逊物流入库处理数量',
    afn_inbound_receiving_quantity VARCHAR(255) NULL COMMENT '亚马逊物流入库接收数量',
    sold_90days                    VARCHAR(255) NULL COMMENT '最后90天销量',
    eff                            VARCHAR(255) NULL COMMENT 'Eff(销售效率)',
    developerusername              VARCHAR(255) NULL COMMENT '开发人员',
    salesusername                  VARCHAR(255) NULL COMMENT '销售人员',
    createdby                      VARCHAR(255) NULL COMMENT '创建者',
    createdon                      datetime NULL COMMENT '创建日期',
    modifiedby                     VARCHAR(255) NULL COMMENT '修改者',
    modifiedon                     datetime NULL COMMENT '修改日期',
)ENGINE = InnoDB DEFAULT CHARSET = utf8;


select
       substring(to_date(t5.pldate), 1, 4) as year,
       substring(to_date(t5.pldate),6,2) as month,
       t5.channel,
       sum(t5.grossrevenue) as turnover,
       to_date(now()) as calc_date
from (
select t1.skuid as productid
from data_mart.sku_dimension as t1
left join product_operation_listing_management.product_base_info as t3 on t1.skuid=t3.productid
where t3.companyid in ('CR201706260001', 'CR201803130001') and t1.vertical = 'CSA'
) AS t4 left join data_mart.incentive_pl_detail as t5
on t5.skuid = t4.productid
where pldate>='2022-01-01'
  and pldate
    < '2023-01-01'
  and t5.channel in ('amazon_de'
    , 'amazon_fr'
    , 'amazon_es'
    , 'amazon_it'
    , 'ebay_de'
    , 'ebay_fr'
    , 'ebay_es'
    , 'ebay_it'
    , 'amazon_jp'
    , 'rakuten_jp'
    , 'yahoo_jp'
    , 'wowma_jp'
    , 'Qoo10_jp'
    , 'base_jp')
  and t5.company_enname in ('MRO'
    , 'PA'
    , 'CSA'
    , 'HG'
    , 'LT')
group by year, month, t5.channel, calc_date
order by year limit 2000
offset 0