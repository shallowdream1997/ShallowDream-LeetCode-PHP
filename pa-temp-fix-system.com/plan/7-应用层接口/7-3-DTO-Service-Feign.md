# 7-3 DTO / Service Feign / 常量定义

## 一、概述

- **功能说明**: 统一管理自营转寄卖功能涉及的 DTO（请求/响应）、Feign 接口、枚举常量定义的规范和位置说明，作为前后端对接和模块间调用的契约
- **面向用户**: 后端开发人员（Controller层和Service层的接口对接）
- **关键原则**: 
  - DTO 统一在 `pa-scms-service-api` 模块定义，Controller 层直接使用 service 层 DTO，不再定义应用层 DTO
  - 枚举和常量统一在 `pa-common-service-api` 模块定义
  - Controller 不再 `implements` 或直接调用 Feign 接口，改为注入 Application Service（单例 Spring Bean）
  - Application Service 封装业务逻辑，内部调用 CRUD ServiceApi Feign 操作数据

## 二、模块依赖关系

```
前端(Admin/Supplier UI)
     ↓  HTTP JSON
pa-scms-application-biz (Controller层 + Application Service)
     ├── apiimpl/consignment/recruit/ → Controller（纯@RestController，无implements）
     │   ├── admin/ConsignmentRecruitAdminController.java
     │   ├── admin/ConsignmentRecruitAdminSupplierController.java  ← Admin寄卖商工作台
     │   ├── supplier/ConsignmentRecruitSupplierController.java
     │   └── pool/ConsignmentRecruitPoolController.java
     └── service/consignment/recruit/ → Application Service（业务逻辑封装）
         ├── ConsignmentRecruitAdminService.java
         ├── ConsignmentRecruitAdminSupplierService.java  ← Admin寄卖商工作台
         ├── ConsignmentRecruitSupplierService.java
         └── ConsignmentRecruitPoolService.java
                    ↓  Feign 调用
pa-scms-service-api     (CRUD ServiceApi + DTO定义)
     └── api/consignment/recruit/{module}/ → CRUD ServiceApi（细粒度数据操作）
         ├── list/ConsignmentRecruitListServiceApi.java
         ├── listsku/ConsignmentRecruitListSkuServiceApi.java
         ├── publish/ConsignmentRecruitPublishServiceApi.java
         ├── apply/ConsignmentRecruitApplyServiceApi.java
         └── actionlog/ConsignmentActionLogServiceApi.java
                    ↓  实现
pa-scms-service-biz     (Service实现层 + Repository层)
     ↓  引用
pa-common-service-api   (枚举/常量)

说明：
- Controller 注入 Application Service，不再直接调用 Feign
- Application Service 封装跨 CRUD 模块的组合业务逻辑
- 原有的粗粒度业务 Feign API（AdminApi/SupplierApi/PoolApi）已移除
- Controller 和 Application Service 均使用 pa-scms-service-api 中的 DTO


## 三、Feign 接口定义

### 3.1 供应链管理 Feign（已移除）

> ⚠ **已移除**：粗粒度业务 Feign 接口 `ConsignmentRecruitAdminApi` 已删除，
> 改用 Application Service `ConsignmentRecruitAdminService` 封装业务逻辑，
> 内部调用 CRUD ServiceApi。

### 3.2 寄卖商工作台 Feign（已移除）

> ⚠ **已移除**：粗粒度业务 Feign 接口 `ConsignmentRecruitSupplierApi` 已删除，
> 改用 Application Service `ConsignmentRecruitSupplierService` 封装业务逻辑，
> 内部调用 CRUD ServiceApi。

### 3.3 招募池管理 Feign（已移除）

> ⚠ **已移除**：粗粒度业务 Feign 接口 `ConsignmentRecruitPoolApi` 已删除，
> 改用 Application Service `ConsignmentRecruitPoolService` 封装业务逻辑，
> 内部调用 CRUD ServiceApi。

## 四、DTO 定义

### 4.1 包路径总览

DTO 分为两个层级：

**层级1 - CRUD ServiceApi DTO**（每个模块独立包，标准11方法 + 自定义DTO）

```
pa-biz-service/pa-scms-service/pa-scms-service-api/src/main/java/com/ux168/pa/service/scms/api/consignment/recruit/
├── list/dto/
│   ├── ConsignmentRecruitListBaseDTO.java
│   ├── request/
│   │   ├── ConsignmentRecruitListCreateReqDTO.java
│   │   ├── ConsignmentRecruitListUpdateReqDTO.java
│   │   ├── ConsignmentRecruitListListReqDTO.java
│   │   ├── ConsignmentRecruitListPageReqDTO.java
│   │   └── ConsignmentRecruitListBatchUpdateStatusReqDTO.java
│   └── response/
│       ├── ConsignmentRecruitListRespDTO.java
│       └── ConsignmentRecruitListPageRespDTO.java
├── listsku/dto/     (同list模式 + BatchUpdateReqDTO)
├── publish/dto/     (同list模式)
├── apply/dto/       (同list模式)
└── actionlog/dto/   (同list模式)
```

**层级2 - 业务操作DTO**（已移除，不再需要）

> ⚠ **已移除**：由于粗粒度业务 Feign API 被删除，对应的业务操作 DTO 不再需要。
> Controller 直接使用 CRUD ServiceApi 返回的 DTO（如 `ConsignmentRecruitListRespDTO` 等）。

### 4.2 请求DTO清单

| 类名 | 说明 | 关键字段 | 状态 |
|------|------|---------|------|
| `RecruitListQueryDTO` | 清单查询请求（已移除） | - | ⚠ 已删除 |
| `RecruitDetailQueryDTO` | 清单详情查询（已移除） | - | ⚠ 已删除 |
| `RecruitPublishDTO` | 发布请求（已移除） | - | ⚠ 已删除 |
| `BatchCancelDTO` | 批量作废请求（已移除） | - | ⚠ 已删除 |
| `RecruitMarketQueryDTO` | 招募市场查询（已移除） | - | ⚠ 已删除 |
| `RecruitCartQueryDTO` | 招募车查询（已移除） | - | ⚠ 已删除 |
| `JoinCartDTO` | 加入招募车（已移除） | - | ⚠ 已删除 |
| `WithdrawCartDTO` | 撤回招募车（已移除） | - | ⚠ 已删除 |
| `RemoveCartDTO` | 移除招募车（已移除） | - | ⚠ 已删除 |
| `SameSourceQueryDTO` | 同源查询（已移除） | - | ⚠ 已删除 |
| `CoverageDetailQueryDTO` | 覆盖率详情查询（已移除） | - | ⚠ 已删除 |
| `RecruitPoolQueryDTO` | 招募池查询（已移除） | - | ⚠ 已删除 |

> 以上业务 DTO 随粗粒度 Feign API 一并删除，Controller 直接使用 CRUD ServiceApi DTO。

## 五、枚举与常量

### 5.1 枚举

**包路径**: `com.ux168.pa.service.common.constants.consignment.recruit.enums`

统一在 `pa-common-service-api` 中定义，前端 `pa-scms-application` 及其他模块直接引用。

核心枚举列表（以 `pa-common-service-api` 中实际定义的为准）：

| 枚举类 | 说明 | 关键枚举值 |
|--------|------|-----------|
| `ConsignmentRecruitListStatusEnum` | 清单状态 | WAIT_PUBLISH(10), RECRUITING(20), FULL_SNAPPED(25), NO_APPLY_RECYCLED(30), AWARDING(50), COMPLETED(60), CANCELLED(100) |
| `ConsignmentRecruitSkuStatusEnum` | SKU状态 | PENDING_GROUP(10), GROUPED(20), PUBLISHED(30), DELETED(100) |
| `ConsignmentRecruitPublishStatusEnum` | 发布状态 | WAIT_PUBLISH(10), RECRUITING(20), FULL_SNAPPED(25), NO_APPLY_RECYCLED(30), AWARDING(50), COMPLETED(60), CANCELLED(100) |
| `ConsignmentRecruitApplyStatusEnum` | 申请状态 | JOINED(10), CE_CREATED(20), WAIT_AWARD(30), AWARD_DONE(40), LIST_COMPLETED(50), TIMEOUT_CLEANED(90), ABANDONED(100) |
| `ConsignmentActionEnum` | 动作枚举 | IMPORT_SKU, GROUP_LIST, PUBLISH, APPLY, CREATE_CE, CE_SHIP, CE_QC, CALC_COVERAGE, AWARD, CANCEL_LIST, CANCEL_APPLY, WITHDRAW, TIMEOUT_CLEAN, RECYCLE |
| `ConsignmentOperatorTypeEnum` | 操作人类型 | SYSTEM(1), ADMIN(2), SUPPLIER(3) |
| `ConsignmentCancelTypeEnum` | 作废来源 | MANUAL, NO_APPLY, RECYCLE, EXCEPTION |
| `ConsignmentAwardResultEnum` | 评选结果 | SUCCESS(1), FAIL(0) |

### 5.2 常量

**包路径**: `com.ux168.pa.service.common.constants.consignment.recruit`

```java
public class ConsignmentRecruitConstants {
    /** 清单编号前缀 */
    public static final String RECRUIT_NO_PREFIX = "SC";
    /** 招募车最大容量 */
    public static final int CART_MAX_COUNT = 5;
    /** 组单最小SKU数 */
    public static final int GROUP_MIN_SKU = 11;
    /** 组单最大SKU数 */
    public static final int GROUP_MAX_SKU = 200;
    /** CE超时天数 */
    public static final int CE_TIMEOUT_DAYS = 3;
    /** 评选等待最大天数 */
    public static final int AWARD_WAIT_DAYS = 14;
    /** 自动发布每批上限 */
    public static final int PUBLISH_BATCH_LIMIT = 20;
    /** 市场可见时间(每周三10:00) */
    public static final String MARKET_VISIBLE_TIME = "Wednesday 10:00";
    /** 市场可抢时间(每周三14:00) */
    public static final String MARKET_GRAB_TIME = "Wednesday 14:00";
    /** 市场截止时间(每周三21:00) */
    public static final String MARKET_DEADLINE = "Wednesday 21:00";
}
```

## 六、代码位置汇总

```
# ===== 应用层 Controller + Application Service (pa-scms-application-biz) =====
pa-biz-application/pa-scms-application/pa-scms-application-biz/src/main/java/com/ux168/pa/application/scms/biz/
├── apiimpl/consignment/recruit/   ← Controller（纯@RestController，注入Application Service）
│   ├── admin/ConsignmentRecruitAdminController.java
│   ├── admin/ConsignmentRecruitAdminSupplierController.java  ← Admin寄卖商工作台
│   ├── supplier/ConsignmentRecruitSupplierController.java
│   └── pool/ConsignmentRecruitPoolController.java
└── service/consignment/recruit/   ← Application Service（封装业务逻辑 + 内部DTO）
    ├── ConsignmentRecruitAdminService.java
    │   └── 内部 DTO: RecruitListDetailRespDTO, RecruitListDetailApplyItem
    ├── ConsignmentRecruitAdminSupplierService.java  ← Admin寄卖商工作台（委托SupplierService）
    ├── ConsignmentRecruitSupplierService.java
    │   └── 内部 DTO: MarketHeaderStatRespDTO, RecruitMarketItem, AwardDetailRespDTO, AwardDetailItem,
    │              MarketItemRespDTO, MarketPageRespDTO, CartItemRespDTO, CartPageRespDTO,
    │              MarketStatReqDTO, MarketPageReqDTO, SkuListReqDTO,
    │              CartPageReqDTO, CartJoinReqDTO, CartWithdrawReqDTO,
    │              AwardDetailReqDTO, AppendSkuReqDTO, SameSourceListReqDTO
    └── ConsignmentRecruitPoolService.java

# ===== CRUD ServiceApi + DTO (pa-scms-service-api) =====
pa-biz-service/pa-scms-service/pa-scms-service-api/src/main/java/com/ux168/pa/service/scms/api/consignment/recruit/
├── list/                                         ← CRUD ServiceApi
│   ├── ConsignmentRecruitListServiceApi.java
│   └── dto/
├── listsku/                                      ← CRUD ServiceApi
│   ├── ConsignmentRecruitListSkuServiceApi.java
│   └── dto/
├── publish/                                      ← CRUD ServiceApi
│   ├── ConsignmentRecruitPublishServiceApi.java
│   └── dto/
├── apply/                                        ← CRUD ServiceApi
│   ├── ConsignmentRecruitApplyServiceApi.java
│   └── dto/
└── actionlog/                                    ← CRUD ServiceApi
    ├── ConsignmentActionLogServiceApi.java
    └── dto/

# ===== 枚举常量 (pa-common-service-api) =====
pa-biz-service/pa-common-service/pa-common-service-api/src/main/java/com/ux168/pa/service/common/constants/consignment/recruit/
├── enums/
│   ├── ConsignmentRecruitListStatusEnum.java
│   ├── ConsignmentRecruitSkuStatusEnum.java
│   ├── ConsignmentRecruitPublishStatusEnum.java
│   ├── ConsignmentRecruitApplyStatusEnum.java
│   ├── ConsignmentActionEnum.java
│   ├── ConsignmentOperatorTypeEnum.java
│   ├── ConsignmentCancelTypeEnum.java
│   └── ConsignmentAwardResultEnum.java
└── ConsignmentRecruitConstants.java
```

## 七、依赖关系

| 依赖模块 | 说明 |
|----------|------|
| pa-scms-service-api | Feign API 接口 + DTO 定义 |
| pa-common-service-api | 枚举常量定义 |
| Spring Cloud OpenFeign | 服务间调用 |
| Lombok | DTO getter/setter/Builder |
| JSR303 | DTO参数校验注解 |
| Servlet API | HttpServletResponse (导出文件流) |
| Swagger (springfox-swagger2) | 接口文档注解（@ApiOperation / @ApiModel / @ApiModelProperty） |
```

## 八、Swagger 注解规范

### 8.1 ServiceApi 层注解

| 注解 | 使用位置 | 说明 |
|------|---------|------|
| `@ApiOperation(value, notes)` | 每个方法上 | value 描述操作概要，notes 补充说明 |
| `@ApiImplicitParam` | 带 @RequestParam 参数的方法 | 标注参数名称、说明、是否必填 |

**命名规范**：
- value 格式：`"{操作} {模块名}"`，如 `"创建 招募清单"`、`"查询 招募清单 分页数据"`
- notes 格式：`"根据{条件} , {操作说明}"`，如 `"根据id , 删除 招募清单"`
- 前缀统一使用 `"履约服务 - "`

### 8.2 DTO 层注解

| 注解 | 使用位置 | 说明 |
|------|---------|------|
| `@ApiModel(value)` | DTO 类上 | value 格式：`"履约服务 - {模块名} {DTO用途}"` |
| `@ApiModelProperty(name, value, required)` | 每个字段上 | name 为字段名，value 为中文说明，必填字段加 required = true |

**命名规范**：
- value 格式：`"履约服务 - {模块名} {DTO用途}"`，如 `"履约服务 - 招募清单基本信息 DTO 提供给添加、修改、详细业务的子DTO"`
- 字段说明使用中文业务术语，如 `"招募清单编号"`、`"清单状态"`、`"中标供应商ID"`
- @NotNull 字段同步加 required = true

### 8.3 代码位置

所有 Swagger 注解相关依赖由 `pa-scms-service-api` 模块的 pom.xml 统一管理：
```xml
<dependency>
    <groupId>io.springfox</groupId>
    <artifactId>springfox-swagger2</artifactId>
</dependency>
```