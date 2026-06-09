# 寄卖招募模块 API 接口文档（前端对接）

> 本文档面向前后端对接，涵盖自营转寄卖 PRD 补齐任务中所有新增/修改的 API 接口。

---

## 通用约定

### Content-Type
所有 POST 请求统一使用 `Content-Type: application/json`，参数放在 JSON Body 中。
所有 GET 请求使用 URL Query 传参。

### groupId + supplierId 唯一性
- **groupId**：寄卖商集团ID，与 supplierId 为多对一关系（一个集团下可能有多个寄卖商）
- 所有涉及 supplierId 的请求（招募车列表、招募清单列表、加入招募车、撤回招募车、招募结果详情、开样品CE单）**必须同时传入 groupId**
- supplierId + groupId 联合标识一个寄卖商的唯一身份

### 内部 Request DTO
以下所有 POST 请求的 DTO 均在 `ConsignmentRecruitSupplierService` 内部定义（`SupplierService` 的内部静态类），
DTO 名称约定为 `*ReqDTO`，统一通过 `@RequestBody` 接收。

### 字段颜色标注说明

> 本文档采用颜色标注来区分字段用途，方便前端开发对照开发：
> - <font color="red">🔴 红色</font> = **列表展示字段**（按前端表格展示顺序排列）
> - <font color="green">🟢 绿色</font> = **筛选条件字段**（前端筛选/查询条件）
> - <font color="#800080">🟣 紫色</font> = **关联入参**（该字段的值将作为其他API的请求入参）
> - <font color="#DAA520">🟡 黄色</font> = **接口入参字段**（必传/选传入参）
> - 无标记 = **多余字段**（响应中返回但前端无需展示，向下列出供参考）

---

## 目录

1. [招募清单列表分页（增强）](#11-招募清单列表分页增强)
2. [加入招募车](#12-加入招募车)
3. [撤回招募车](#13-撤回招募车)
4. [招募车列表分页](#14-招募车列表分页)
5. [招募结果详情](#15-招募结果详情)
6. [开样品CE单](#16-开样品ce单)
    - [批量开CE单](#161-批量开ce单appendskutoce)
    - [按清单ID开CE单（对外API）](#162-按清单id开ce单createce对外预留api)
7. [下拉选项API](#17-下拉选项api)
8. [下载附件](#18-下载附件)
9. [公共枚举说明](#二公共枚举说明)

---

## 一、寄卖商工作台 - 招募管理 API

**Base URL**: `/scms/consignment/recruit/supplier`  
**Content-Type**: `application/json`

---

### 1.1 招募清单列表分页（增强）

寄卖商查看招募清单列表，含丰富筛选条件、竞争池大小、同源母鸡数、统计栏，支持展开SKU行。

- **请求方式**: `POST`
- **URL**: `/scms/consignment/recruit/supplier/v1/list/page`

#### 请求参数（JSON Body）

**DTO**: `SupplierRecruitListPageReqDTO`（继承 `ReqPageDTO`）

| 字段名 | 类型 | 必填 | 说明 | 标注 |
|--------|------|:----:|------|:----:|
| pageNo | Integer | 否 | 页码，默认 1 | <font color="#DAA520">🟡入参</font> |
| pageSize | Integer | 否 | 每页条数，默认 10 | <font color="#DAA520">🟡入参</font> |
| supplierId | Long | **是** | 寄卖商ID | <font color="#DAA520">🟡入参</font> |
| groupId | Long | **是** | 寄卖商集团ID | <font color="#DAA520">🟡入参</font> |
| publishBeginTimeGe | Date | 否 | 发布时间-起（时间范围筛选） | <font color="green">🟢筛选</font> |
| publishBeginTimeLe | Date | 否 | 发布时间-止 | <font color="green">🟢筛选</font> |
| factoryId | Long | 否 | 货源工厂ID（精确匹配） | <font color="green">🟢筛选</font> |
| categoryId | Long | 否 | 产品线分类ID（精确匹配） | <font color="green">🟢筛选</font> |
| estimatedCostMin | BigDecimal | 否 | 预计寄卖总价最小值 | <font color="green">🟢筛选</font> |
| estimatedCostMax | BigDecimal | 否 | 预计寄卖总价最大值 | <font color="green">🟢筛选</font> |
| skuIdList | List\<Long\> | 否 | SKU ID列表（textarea输入，最多1000个） | <font color="green">🟢筛选</font> |
| sameSourceOnly | Boolean | 否 | 是否只查看我的同源清单（默认false） | <font color="green">🟢筛选</font> |
| supplierApplyCount | Integer | 否 | 竞争池人数筛选 | <font color="green">🟢筛选</font> |

#### 响应参数

**DTO**: `SupplierRecruitListPageRespDTO`（继承 `RespPageDTO<SupplierRecruitListItemRespDTO>`，增加 `stat` 统计）

**外层结构**：

| 字段名 | 类型 | 说明 |
|--------|------|------|
| list | Array\<SupplierRecruitListItemRespDTO\> | 清单列表，每项见下表 |
| page | RespPageDO | 分页信息（含 currentSize、nextPage、totalPage、totalSize） |
| stat | SupplierRecruitListStatDTO | 头部统计信息 |

**list 每项 - 展示字段**（`SupplierRecruitListItemRespDTO`，按前端表格展示顺序排列）：

| 字段名 | 类型 | 说明 | 标注 |
|--------|------|------|:----:|
| sequenceNo | Integer | 序号（从1开始，根据分页位置计算） | <font color="red">🔴展示</font> |
| publishBeginTime | Date | 发布开始时间 | <font color="red">🔴展示</font> |
| recruitNo | String | 招募清单编号（用于1.2加入招募车/1.3撤回招募车/1.5招募结果详情/1.6开样品CE单/1.8下载附件） | <font color="red">🔴展示</font> <font color="#800080">🟣关联入参</font> |
| factoryName | String | 货源工厂名称 | <font color="red">🔴展示</font> |
| productCategoryName | String | 产品分类名称 | <font color="red">🔴展示</font> |
| lastCategoryName | String | 产品线 | <font color="red">🔴展示</font> |
| skuCount | Integer | 清单SKU数量 | <font color="red">🔴展示</font> |
| estimatedCost | BigDecimal | 预计寄卖总价(元) | <font color="red">🔴展示</font> |
| estimatedMonthSaleQty | Integer | 预估月销量 | <font color="red">🔴展示</font> |
| estimatedMonthSaleAmount | BigDecimal | 预估月销售额 | <font color="red">🔴展示</font> |
| avgMoq | BigDecimal | 平均MOQ | <font color="red">🔴展示</font> |
| supplierApplyCount | Integer | 竞争池人数 | <font color="red">🔴展示</font> |
| sameSourceCount | Integer | 同源母鸡数（该寄卖商在该货源工厂下已有的SKU总数） | <font color="red">🔴展示</font> |
| listStatusName | String | 清单状态名称 | <font color="red">🔴展示</font> |
| joined | Boolean | 当前供应商是否已加入该清单的招募车（用于控制是否展示"加入招募车"操作按钮） | <font color="red">🔴展示</font> |
| sameSource | Boolean | 当前供应商与该清单是否同源（用于控制是否展示同源标识） | <font color="red">🔴展示</font> |

**list 每项 - 多余字段**（响应中返回但前端无需展示在表格中）：

| 字段名 | 类型 | 说明 |
|--------|------|------|
| id | Long | 招募清单ID |
| factoryId | Long | 货源工厂ID |
| categoryId | Long | 末级分类ID |
| listStatus | Integer | 清单状态编码（20=招募中, 25=已抢完） |
| applyBeginTime | Date | 开放申请时间 |
| applyEndTime | Date | 申请结束时间 |
| publishEndTime | Date | 发布结束时间 |
| **skuList** | Array\<ConsignmentRecruitListSkuRespDTO\> | **清单SKU明细列表（展开行显示）** |

**skuList 每项字段**（`ConsignmentRecruitListSkuRespDTO`，展开行SKU明细）：

| 字段名 | 类型 | 说明 | 标注 |
|--------|------|------|:----:|
| skuId | Long | SKU ID | <font color="red">🔴展示</font> |
| skuName | String | SKU名称/中文描述 | <font color="red">🔴展示</font> |
| productModel | String | 产品型号 | <font color="red">🔴展示</font> |
| vehicleModel | String | 车型 | <font color="red">🔴展示</font> |
| salesQuantity | String | 卖数 | <font color="red">🔴展示</font> |
| saleQty90d | Integer | 近90天销量 | <font color="red">🔴展示</font> |
| unit | String | 单位 | <font color="red">🔴展示</font> |
| packageInfo | String | 包装 | <font color="red">🔴展示</font> |
| grossWeightG | BigDecimal | 毛重(g) | <font color="red">🔴展示</font> |
| packageSizeCm | String | 包装尺寸(cm) | <font color="red">🔴展示</font> |
| sourceModel | String | 货源型号 | <font color="red">🔴展示</font> |
| sourceUrl | String | 货源链接 | <font color="red">🔴展示</font> |
| deliveryDays | Integer | 交货周期(天) | <font color="red">🔴展示</font> |
| moq | Integer | 最小起订量 | <font color="red">🔴展示</font> |
| costPrice | BigDecimal | 寄卖单价(元) | <font color="red">🔴展示</font> |
| ceReceiveCount | Integer | CE单来货数 | <font color="red">🔴展示</font> |
| factoryName | String | 货源工厂名称 | <font color="red">🔴展示</font> |
| ceBillNo | String | CE单号（当前供应商有申请且已开CE时返回） | <font color="red">🔴展示</font> |
| ceStatus | String | CE单状态（从当前申请推断，如未开单/已开单/已发货/处理完成） | <font color="red">🔴展示</font> |

**stat 字段**（`SupplierRecruitListStatDTO`，头部统计栏）：

| 字段名 | 类型 | 说明 | 标注 |
|--------|------|------|:----:|
| totalSourceCount | Integer | 总符合条件的清单总数（分页前） | <font color="red">🔴展示</font> |
| totalSkuCount | Integer | 总符合条件的SKU数（所有清单 skuCount 之和） | <font color="red">🔴展示</font> |
| myCartCount | Integer | 当前供应商的招募车申请总数 | <font color="red">🔴展示</font> |

#### 筛选说明

> - `sameSourceOnly=true` 时，仅返回当前供应商为同源的清单
> - `skuIdList` 传入时，后台通过 SKU 明细表关联查询匹配的 recruitId，最多支持 1000 个 SKU ID

#### 请求示例

```json
POST /scms/consignment/recruit/supplier/v1/list/page
Content-Type: application/json

{
  "pageNo": 1,
  "pageSize": 10,
  "supplierId": 1001,
  "groupId": 2001,
  "factoryId": 201,
  "categoryId": 101,
  "estimatedCostMin": 10000,
  "estimatedCostMax": 500000
}
```

#### 响应示例

```json
{
  "code": 200,
  "message": "success",
  "data": {
    "list": [
      {
        "id": 1,
        "sequenceNo": 1,
        "recruitNo": "SC20260520001",
        "factoryId": 201,
        "factoryName": "浙江XX工厂",
        "categoryId": 101,
        "skuCount": 30,
        "estimatedCost": 150000.00,
        "estimatedMonthSaleQty": 500,
        "estimatedMonthSaleAmount": 75000.00,
        "avgMoq": 100.00,
        "supplierApplyCount": 3,
        "listStatus": 20,
        "listStatusName": "招募中",
        "publishBeginTime": "2026-05-20 10:00:00",
        "applyBeginTime": "2026-05-20 14:00:00",
        "applyEndTime": "2026-05-27 21:00:00",
        "publishEndTime": "2026-05-27 23:59:59",
        "productCategoryName": "汽车配件",
        "lastCategoryName": "发动机系统",
        "sameSourceCount": 1,
        "joined": false,
        "sameSource": true,
        "skuList": [
          {
            "skuId": 5001,
            "skuName": "SKU名称A",
            "productModel": "PM-001",
            "vehicleModel": "丰田卡罗拉",
            "salesQuantity": null,
            "saleQty90d": 1200,
            "unit": "个",
            "packageInfo": "标准包装",
            "grossWeightG": 500.00,
            "packageSizeCm": "30x20x10",
            "sourceModel": "MFR-001",
            "sourceUrl": "https://example.com/purchase/001",
            "deliveryDays": 15,
            "moq": 100,
            "costPrice": 25.50,
            "ceReceiveCount": 0,
            "factoryName": "浙江XX工厂",
            "ceBillNo": null,
            "ceStatus": null
          }
        ]
      }
    ],
    "page": {
      "currentSize": 10,
      "nextPage": 2,
      "totalPage": 5,
      "totalSize": 50
    },
    "stat": {
      "totalSourceCount": 48,
      "totalSkuCount": 1500,
      "myCartCount": 5
    }
  }
}
```

### 1.2 加入招募车

寄卖商加入一张招募清单的招募车（含Redis锁并发保护、竞争池满员更新、操作日志）。

- **请求方式**: `POST`
- **URL**: `/scms/consignment/recruit/supplier/v1/cart/join`

#### 请求参数（JSON Body）

**DTO**: `CartJoinReqDTO`

| 字段名 | 类型 | 必填 | 说明 | 标注 |
|--------|------|:----:|------|:----:|
| recruitNo | String | 是 | 招募清单编号 | <font color="#DAA520">🟡入参</font> |
| supplierId | Long | 是 | 寄卖商ID | <font color="#DAA520">🟡入参</font> |
| groupId | Long | 是 | 寄卖商集团ID | <font color="#DAA520">🟡入参</font> |

#### 响应参数

| 字段名 | 类型 | 说明 |
|--------|------|------|
| value | Boolean | 是否加入成功（true/false） |

#### 请求示例

```json
POST /scms/consignment/recruit/supplier/v1/cart/join
Content-Type: application/json

{
  "recruitNo": "SC20260520001",
  "supplierId": 1001,
  "groupId": 2001
}
```

#### 响应示例

```json
{
  "code": 200,
  "message": "success",
  "data": {
    "value": true
  }
}
```

---

### 1.3 撤回招募车

寄卖商在申请结束前撤回招募车（含Redis锁并发保护 + 竞争池降级更新）。该接口同时替代原有的"移除招募车"功能。

- **请求方式**: `POST`
- **URL**: `/scms/consignment/recruit/supplier/v1/cart/withdraw`

#### 请求参数（JSON Body）

**DTO**: `CartWithdrawReqDTO`

| 字段名 | 类型 | 必填 | 说明 | 标注 |
|--------|------|:----:|------|:----:|
| recruitNo | String | 是 | 招募清单编号 | <font color="#DAA520">🟡入参</font> |
| supplierId | Long | 是 | 寄卖商ID | <font color="#DAA520">🟡入参</font> |
| groupId | Long | 是 | 寄卖商集团ID | <font color="#DAA520">🟡入参</font> |

#### 响应参数

| 字段名 | 类型 | 说明 |
|--------|------|------|
| value | Boolean | 是否撤回成功 |

#### 请求示例

```json
POST /scms/consignment/recruit/supplier/v1/cart/withdraw
Content-Type: application/json

{
  "recruitNo": "SC20260520001",
  "supplierId": 1001,
  "groupId": 2001
}
```

#### 响应示例

```json
{
  "code": 200,
  "message": "success",
  "data": {
    "value": true
  }
}
```

---

### 1.4 招募车列表分页

查询该寄卖商的招募车列表，支持多维度筛选、头部统计、竞争池/同源数、SKU展开行。

- **请求方式**: `POST`
- **URL**: `/scms/consignment/recruit/supplier/v1/cart/page`

#### 请求参数（JSON Body）

**DTO**: `CartPageReqDTO`（继承 `ConsignmentRecruitApplyPageReqDTO`）

| 字段名 | 类型 | 必填 | 说明 | 标注 |
|--------|------|:----:|------|:----:|
| pageNo | Integer | 否 | 页码，默认 1 | <font color="#DAA520">🟡入参</font> |
| pageSize | Integer | 否 | 每页条数，默认 10 | <font color="#DAA520">🟡入参</font> |
| supplierId | Long | 否 | 寄卖商ID（过滤当前寄卖商的申请） | <font color="#DAA520">🟡入参</font> |
| groupId | Long | 是 | 寄卖商集团ID | <font color="#DAA520">🟡入参</font> |
| recruitNo | String | 否 | 清单编号 | <font color="green">🟢筛选</font> |
| factoryId | Long | 否 | 货源工厂ID（精确匹配，关联招募清单表） | <font color="green">🟢筛选</font> |
| categoryId | Long | 否 | 产品线分类ID（精确匹配，关联招募清单表） | <font color="green">🟢筛选</font> |
| applyStatusList | List\<Integer\> | 否 | 申请状态复选筛选（支持多选），取值来自1.8.3通用下拉选项 field=applyStatus | <font color="green">🟢筛选</font> |
| listStatusList | List\<Integer\> | 否 | 清单招募状态复选筛选（支持多选），取值来自1.8.3通用下拉选项 field=listStatus | <font color="green">🟢筛选</font> |
| **estimatedCostMin** | **BigDecimal** | **否** | **预计寄卖总价最小值（关联招募清单表）** | <font color="green">🟢筛选</font> |
| **estimatedCostMax** | **BigDecimal** | **否** | **预计寄卖总价最大值（关联招募清单表）** | <font color="green">🟢筛选</font> |
| **publishBeginTimeGe** | **Date** | **否** | **发布招采时间-起（关联招募清单表）** | <font color="green">🟢筛选</font> |
| **publishBeginTimeLe** | **Date** | **否** | **发布招采时间-止（关联招募清单表）** | <font color="green">🟢筛选</font> |
| **skuIdList** | **List\<Long\>** | **否** | **SKU ID列表（通过SKU明细表关联查询）** | <font color="green">🟢筛选</font> |
| **awardResult** | **Integer** | **否** | **评选结果筛选（0=未评选, 1=获胜/已中标, 2=落选），对分页结果做精确匹配过滤** | <font color="green">🟢筛选</font> |

#### 响应参数

**DTO**: `CartPageRespDTO`（继承 `RespPageDTO<CartItemRespDTO>`，增加 `stat` 统计）

**外层结构**：

| 字段名 | 类型 | 说明 |
|--------|------|------|
| list | Array\<CartItemRespDTO\> | 申请列表，每项见下表 |
| page | RespPageDO | 分页信息（含 currentSize、nextPage、totalPage、totalSize） |
| **stat** | **CartStatDTO** | **头部统计信息** |

**stat 字段**（`CartStatDTO`，头部统计栏）：

| 字段名 | 类型 | 说明 | 标注 |
|--------|------|------|:----:|
| totalSourceCount | Integer | 招募中的货源总数（分页前总申请数） | <font color="red">🔴展示</font> |
| totalSkuCount | Integer | 总SKU数（所有匹配清单的 skuCount 之和） | <font color="red">🔴展示</font> |

**list 每项 - 展示字段**（`CartItemRespDTO`，按前端表格展示顺序排列）：

| 字段名 | 类型 | 说明 | 标注 |
|--------|------|------|:----:|
| sequenceNo | Integer | 序号（从1开始，根据分页位置计算） | <font color="red">🔴展示</font> |
| publishBeginTime | Date | 发布招采时间（关联招募清单） | <font color="red">🔴展示</font> |
| recruitNo | String | 招募清单编号（用于1.2加入招募车/1.3撤回招募车/1.5招募结果详情/1.6开样品CE单/1.8下载附件） | <font color="red">🔴展示</font> <font color="#800080">🟣关联入参</font> |
| factoryName | String | 货源工厂名称（关联招募清单） | <font color="red">🔴展示</font> |
| categoryFullPathId | String | 分类完整路径ID（关联招募清单） | <font color="red">🔴展示</font> |
| skuCount | Integer | 招募清单SKU数量（关联招募清单） | <font color="red">🔴展示</font> |
| estimatedCost | BigDecimal | 预计寄卖总价(元)（关联招募清单） | <font color="red">🔴展示</font> |
| estimatedMonthSaleQty | Integer | 预估月销售件数（关联招募清单） | <font color="red">🔴展示</font> |
| estimatedMonthSaleAmount | BigDecimal | 预估月销售金额（关联招募清单） | <font color="red">🔴展示</font> |
| supplierApplyCount | Integer | 竞争池人数（由竞争池大小映射而来，原competitionPoolSize已合并至此字段） | <font color="red">🔴展示</font> |
| sameSourceCount | Integer | 同源母鸡数（该寄卖商在该货源工厂下已有的SKU总数） | <font color="red">🔴展示</font> |
| skuCoverageRate | BigDecimal | SKU覆盖率 | <font color="red">🔴展示</font> |
| sameSourceWeightRate | BigDecimal | 同源加权率 | <font color="red">🔴展示</font> |
| finalCoverageRate | BigDecimal | 最终覆盖率 | <font color="red">🔴展示</font> |
| listStatusName | String | 清单状态名称（关联招募清单） | <font color="red">🔴展示</font> |
| applyStatusName | String | 申请状态名称（如"已加入"、"已开CE"） | <font color="red">🔴展示</font> |
| awardResultName | String | 招募结果名称（如"未评选"、"获胜"、"落选"） | <font color="red">🔴展示</font> |
| ceStatus | String | CE状态（推断值：未开单/已开单/已发货/处理完成） | <font color="red">🔴展示</font> |

**list 每项 - 多余字段**（响应中返回但前端无需展示在表格中）：

| 字段名 | 类型 | 说明 |
|--------|------|------|
| id | Long | 申请ID |
| recruitId | Long | 招募清单ID |
| supplierId | Long | 寄卖商ID |
| supplierName | String | 寄卖商名称 |
| groupId | Long | 寄卖商集团ID |
| factoryId | Long | 货源工厂ID |
| applyStatus | Integer | 申请状态编码 |
| sameSourceFlag | Integer | 同货源标识（0=非同源, 1=同源） |
| ceBillNo | String | CE单号 |
| ceCreateTime | Date | CE开单时间 |
| ceSendTime | Date | CE发货时间 |
| firstQcPassTime | Date | 首次质检通过时间 |
| inboundSkuCount | Integer | 入库SKU数量 |
| rankNo | Integer | 评选排名 |
| awardResult | Integer | 评选结果编码（0=未评选, 1=获胜, 2=落选） |
| awardReason | String | 评选原因 |
| cancelType | String | 取消类型 |
| cancelReason | String | 取消原因 |
| sameSourceSkuCount | Integer | 已有同源SKU数（该寄卖商在该货源下已有的同源SKU数量） |
| publishEndTime | Date | 发布结束时间（关联招募清单） |
| applyEndTime | Date | 申请结束时间（关联招募清单） |
| listStatus | Integer | 清单状态编码（关联招募清单） |
| skuList | Array\<ConsignmentRecruitListSkuRespDTO\> | 清单SKU明细列表（展开行） |

**skuList 每项字段**（`ConsignmentRecruitListSkuRespDTO`，展开行SKU明细）：

| 字段名 | 类型 | 说明 | 标注 |
|--------|------|------|:----:|
| skuId | Long | SKU ID | <font color="red">🔴展示</font> |
| skuName | String | SKU名称/中文描述 | <font color="red">🔴展示</font> |
| productModel | String | 产品型号 | <font color="red">🔴展示</font> |
| vehicleModel | String | 车型 | <font color="red">🔴展示</font> |
| salesQuantity | String | 卖数 | <font color="red">🔴展示</font> |
| saleQty90d | Integer | 近90天销量 | <font color="red">🔴展示</font> |
| unit | String | 单位 | <font color="red">🔴展示</font> |
| packageInfo | String | 包装 | <font color="red">🔴展示</font> |
| grossWeightG | BigDecimal | 毛重(g) | <font color="red">🔴展示</font> |
| packageSizeCm | String | 包装尺寸(cm) | <font color="red">🔴展示</font> |
| sourceModel | String | 货源型号 | <font color="red">🔴展示</font> |
| sourceUrl | String | 货源链接 | <font color="red">🔴展示</font> |
| deliveryDays | Integer | 交货周期(天) | <font color="red">🔴展示</font> |
| moq | Integer | 最小起订量 | <font color="red">🔴展示</font> |
| costPrice | BigDecimal | 寄卖单价(元) | <font color="red">🔴展示</font> |
| ceReceiveCount | Integer | CE单来货数 | <font color="red">🔴展示</font> |
| factoryName | String | 货源工厂名称 | <font color="red">🔴展示</font> |
| ceBillNo | String | CE单号（从当前申请继承） | <font color="red">🔴展示</font> |
| ceStatus | String | CE单状态（从当前申请推断，如未开单/已开单/已发货/处理完成） | <font color="red">🔴展示</font> |

#### 请求示例

```json
POST /scms/consignment/recruit/supplier/v1/cart/page
Content-Type: application/json

{
  "pageNo": 1,
  "pageSize": 10,
  "supplierId": 1001,
  "groupId": 2001,
  "factoryId": 201,
  "publishBeginTimeGe": "2026-05-01 00:00:00",
  "publishBeginTimeLe": "2026-05-31 23:59:59"
}
```

#### 响应示例

```json
{
  "code": 200,
  "message": "success",
  "data": {
    "list": [
      {
        "id": 501,
        "sequenceNo": 1,
        "recruitId": 1,
        "recruitNo": "SC20260520001",
        "supplierId": 1001,
        "supplierName": "XX贸易有限公司",
        "factoryId": 201,
        "applyStatus": 10,
        "applyStatusName": "已加入",
        "sameSourceFlag": 0,
        "ceBillNo": null,
        "ceCreateTime": null,
        "finalCoverageRate": null,
        "awardResult": 0,
        "awardResultName": "未评选",
        "ceStatus": "未开单",
        "factoryName": "浙江XX工厂",
        "categoryFullPathId": "1-10-100",
        "skuCount": 30,
        "sameSourceCount": 1,
        "estimatedCost": 150000.00,
        "publishBeginTime": "2026-05-20 10:00:00",
        "publishEndTime": "2026-05-27 23:59:59",
        "applyEndTime": "2026-05-27 21:00:00",
        "estimatedMonthSaleQty": 500,
        "estimatedMonthSaleAmount": 75000.00,
        "listStatus": 20,
        "listStatusName": "招募中",
        "categoryId": 101,
        "productCategoryName": "汽车配件",
        "lastCategoryName": "发动机系统",
        "skuList": [
          {
            "skuId": 5001,
            "skuName": "SKU名称A",
            "productModel": "PM-001",
            "vehicleModel": "丰田卡罗拉",
            "salesQuantity": null,
            "saleQty90d": 1200,
            "unit": "个",
            "packageInfo": "标准包装",
            "grossWeightG": 500.00,
            "packageSizeCm": "30x20x10",
            "sourceModel": "MFR-001",
            "sourceUrl": "https://example.com/purchase/001",
            "deliveryDays": 15,
            "moq": 100,
            "costPrice": 25.50,
            "ceReceiveCount": 0,
            "factoryName": "浙江XX工厂",
            "ceBillNo": "CE20260520001",
            "ceStatus": "已开单"
          }
        ]
      }
    ],
    "page": {
      "currentSize": 10,
      "nextPage": 2,
      "totalPage": 5,
      "totalSize": 50
    },
    "stat": {
      "totalSourceCount": 48,
      "totalSkuCount": 1500
    }
  }
}
```

#### 筛选说明

> - `factoryId`、`categoryId`、`estimatedCostMin/Max`、`publishBeginTimeGe/Le` 属于招募清单表字段，后台通过先查清单表获取匹配的 recruitId 列表，再过滤申请记录
> - `skuIdList` 传入时，后台通过 SKU 明细表关联查询匹配的 recruitId
> - `awardResult` 在内存中做后过滤（申请表字段）

---

### 1.5 招募结果详情

查询招募结果排名详情（按覆盖率排序，供应商名称隐藏）。

- **请求方式**: `POST`
- **URL**: `/scms/consignment/recruit/supplier/v1/cart/awardDetail`

#### 请求参数（JSON Body）

**DTO**: `AwardDetailReqDTO`

| 字段名 | 类型 | 必填 | 说明 | 标注 |
|--------|------|:----:|------|:----:|
| recruitNo | String | 是 | 招募清单编号 | <font color="#DAA520">🟡入参</font> |
| supplierId | Long | 是 | 寄卖商ID | <font color="#DAA520">🟡入参</font> |
| groupId | Long | 是 | 寄卖商集团ID | <font color="#DAA520">🟡入参</font> |

#### 响应参数

| 字段名 | 类型 | 说明 |
|--------|------|------|
| items | Array | 排名列表，每项见下表 |

**items 每项 - 展示字段**：

| 字段名 | 类型 | 说明 | 标注 |
|--------|------|------|:----:|
| rankNo | Integer | 排名序号（1-based） | <font color="red">🔴展示</font> |
| supplierName | String | 供应商名称（已隐藏，如"张**"） | <font color="red">🔴展示</font> |
| skuCoverageRate | BigDecimal | SKU覆盖率 | <font color="red">🔴展示</font> |
| applyStatusName | String | 申请状态名称（如"已加入"、"已开CE"等） | <font color="red">🔴展示</font> |
| awardResult | Integer | 评选结果（0=未评选, 1=获胜, 2=落选） | <font color="red">🔴展示</font> |
| firstQcPassTime | Date | 首次质检通过时间 | <font color="red">🔴展示</font> |

**items 每项 - 多余字段**：

| 字段名 | 类型 | 说明 |
|--------|------|------|
| applyStatus | Integer | 申请状态编码 |

#### 请求示例

```json
POST /scms/consignment/recruit/supplier/v1/cart/awardDetail
Content-Type: application/json

{
  "recruitNo": "SC20260520001",
  "supplierId": 1001,
  "groupId": 2001
}
```

#### 响应示例

```json
{
  "code": 200,
  "message": "success",
  "data": {
    "items": [
      {
        "rankNo": 1,
        "supplierName": "XX**",
        "applyStatus": 40,
        "applyStatusName": "招募完成",
        "skuCoverageRate": 95.50,
        "awardResult": 1,
        "firstQcPassTime": "2026-05-22 14:30:00"
      },
      {
        "rankNo": 2,
        "supplierName": "YY**",
        "applyStatus": 30,
        "applyStatusName": "招募完成",
        "skuCoverageRate": 82.30,
        "awardResult": 2,
        "firstQcPassTime": "2026-05-23 09:15:00"
      },
      {
        "rankNo": 3,
        "supplierName": "ZZ**",
        "applyStatus": 10,
        "applyStatusName": "已加入",
        "skuCoverageRate": 65.00,
        "awardResult": null,
        "firstQcPassTime": null
      }
    ]
  }
}
```

---

### 1.6 开样品CE单

寄卖商在招募车中为清单开样品CE单，提供两种调用方式：
- **批量开CE单**（按清单编号列表，适用于前端招募车页面选择多个清单开单）
- **按清单ID开CE单**（按招募清单ID，适用于外部系统/其他工程调用）

---

#### 1.6.1 批量开CE单（appendSkuToCe）

寄卖商在招募车中为一个或多个清单编号开样品CE单，每个申请创建一个CE单（不再支持追加SKU到已有CE单）。

- **请求方式**: `POST`
- **URL**: `/scms/consignment/recruit/supplier/v1/cart/appendSku`

##### 请求参数（JSON Body）

**DTO**: `AppendSkuReqDTO`

| 字段名 | 类型 | 必填 | 说明 | 标注 |
|--------|------|:----:|------|:----:|
| recruitNoList | List\<String\> | 是 | 招募清单编号列表（每个编号会创建一个CE单） | <font color="#DAA520">🟡入参</font> |
| supplierId | Long | 是 | 寄卖商ID | <font color="#DAA520">🟡入参</font> |
| groupId | Long | 是 | 寄卖商集团ID | <font color="#DAA520">🟡入参</font> |

##### 业务逻辑

- 遍历 `recruitNoList` 中的每个清单编号
- 通过清单编号查找对应的招募清单，获取 `recruitId`
- 通过 `recruitId + supplierId` 查找申请记录
- 若申请已有CE单号 → **跳过**（一个申请只创建一个CE单，不重复开）
- 若申请状态为 **10（已加入）** → 获取该清单**全部SKU** → 创建CE主表（`CeMaster`）→ 为所有SKU创建CE明细（`CeDetails`）→ 更新申请状态为 `20（已开CE）`，记录 `ceBillNo`、`ceCreateTime`
- 创建CE明细时从招募清单SKU表获取成本价（`costPrice`），从样品信息表获取 `tempId` 和 `ux168Id`

##### 约束条件

- 清单编号列表不能为空
- 申请记录状态必须为 **10（已加入）** 且无已有CE单号
- 清单状态不为空且在招募中/已抢完状态

##### 响应参数

| 字段名 | 类型 | 说明 |
|--------|------|------|
| value | Boolean | 是否有CE单创建成功（至少一个创建成功返回true） |

##### 请求示例

```json
POST /scms/consignment/recruit/supplier/v1/cart/appendSku
Content-Type: application/json

{
  "recruitNoList": ["SC20260520001", "SC20260520002"],
  "supplierId": 1001,
  "groupId": 2001
}
```

##### 响应示例

```json
{
  "code": 200,
  "message": "success",
  "data": {
    "value": true
  }
}
```

---

#### 1.6.2 按清单ID开CE单（createCe，对外预留API）

根据招募清单ID和寄卖商ID直接开样品CE单，专为外部系统/其他工程预留的独立API，不依赖前端招募车页面的清单编号列表。

- **请求方式**: `POST`
- **URL**: `/scms/consignment/recruit/supplier/v1/cart/createCe`

##### 请求参数（JSON Body）

**DTO**: `CeCreateReqDTO`

| 字段名 | 类型 | 必填 | 说明 | 标注 |
|--------|------|:----:|------|:----:|
| recruitId | Long | **是** | 招募清单ID | <font color="#DAA520">🟡入参</font> |
| supplierId | Long | **是** | 寄卖商ID | <font color="#DAA520">🟡入参</font> |
| groupId | Long | 否 | 寄卖商集团ID | <font color="#DAA520">🟡入参</font> |
| operatorName | String | 否 | 操作人（默认system） | <font color="#DAA520">🟡入参</font> |

##### 业务逻辑

- 直接通过 `recruitId + supplierId` 查找申请记录
- 若申请已有CE单号 → 返回失败消息（不重复开）
- 若申请状态为 **10（已加入）** → 获取该清单**全部SKU** → 创建CE主表（`CeMaster`）→ 为所有SKU创建CE明细（`CeDetails`）→ 更新申请单
- 核心逻辑与 `appendSkuToCe` 共享同一段代码（`createCeCore` 私有方法）

##### 响应参数

**DTO**: `CeCreateResultDTO`

| 字段名 | 类型 | 说明 |
|--------|------|------|
| ceBillNo | String | 创建的CE单号 |
| success | Boolean | 是否全部成功 |
| message | String | 结果消息（失败时返回原因） |

##### 请求示例

```json
POST /scms/consignment/recruit/supplier/v1/cart/createCe
Content-Type: application/json

{
  "recruitId": 1,
  "supplierId": 1001
}
```

##### 响应示例（成功）

```json
{
  "code": 200,
  "message": "success",
  "data": {
    "ceBillNo": "CE20260520001",
    "success": true,
    "message": null
  }
}
```

##### 响应示例（失败）

```json
{
  "code": 200,
  "message": "success",
  "data": {
    "ceBillNo": null,
    "success": false,
    "message": "该申请已有CE单"
  }
}
```

---

### 1.7 下拉选项API

提供货源工厂、产品线分类以及通用枚举的下拉选项接口。

- **请求方式**: `GET`
- **Base URL**: `/scms/consignment/recruit/supplier`

#### 1.7.1 货源工厂下拉选项

- **URL**: `/v1/dropdown/factories`
- **响应**: `CommResponse<List<FactoryDropdownDTO>>`

```json
{
  "code": 200,
  "message": "success",
  "data": []
}
```

**DTO**: `FactoryDropdownDTO`

| 字段名 | 类型 | 说明 | 标注 |
|--------|------|------|:----:|
| id | Long | 工厂ID | <font color="green">🟢筛选</font> |
| name | String | 工厂名称 | <font color="green">🟢筛选</font> |

#### 1.7.2 产品线分类下拉选项

- **URL**: `/v1/dropdown/categories`
- **响应**: `CommResponse<List<CategoryDropdownDTO>>`

```json
{
  "code": 200,
  "message": "success",
  "data": []
}
```

**DTO**: `CategoryDropdownDTO`

| 字段名 | 类型 | 说明 | 标注 |
|--------|------|------|:----:|
| id | Long | 分类ID | <font color="green">🟢筛选</font> |
| name | String | 分类名称 | <font color="green">🟢筛选</font> |

#### 1.7.3 通用下拉选项（selectItemList）

提供招募清单相关枚举/状态的下拉选项数据，用于前端下拉筛选框。

- **URL**: `/v1/selectItemList`

**请求参数（Query Params）**

| 字段名 | 类型 | 必填 | 说明 | 标注 |
|--------|------|:----:|------|:----:|
| field | String | **是** | 下拉选项类型（见下方支持列表） | <font color="green">🟢筛选</font> |
| relatedValue | String | 否 | 关联值（预留，暂未使用） | <font color="#DAA520">🟡入参</font> |

**支持的 field 值**

| field 值 | 说明 | 数据来源 |
|----------|------|----------|
| awardResult | 评选结果 | 固定值：0=未评选, 1=获胜, 2=落选 |
| listStatus | 招募清单状态 | 遍历 [2.1 招募清单状态](#21-招募清单状态liststatus) 枚举 |
| applyStatus | 申请状态 | 遍历 [2.2 申请状态](#22-申请状态applystatus) 枚举 |
| ceStatus | CE状态 | 固定值：未开单, 已开单, 已发货, 处理完成 |

**响应参数**

**DTO**: `SelectItemDTO`

| 字段名 | 类型 | 说明 | 标注 |
|--------|------|------|:----:|
| key | String | 选项显示名称 | <font color="green">🟢筛选</font> |
| value | Object | 选项编码值 | <font color="green">🟢筛选</font> |

**请求示例**

```
GET /scms/consignment/recruit/supplier/v1/selectItemList?field=awardResult
```

**响应示例**

```json
{
  "code": 200,
  "message": "success",
  "data": [
    {
      "key": "未评选",
      "value": 0
    },
    {
      "key": "获胜",
      "value": 1
    },
    {
      "key": "落选",
      "value": 2
    }
  ]
}
```

---

### 1.8 下载附件

寄卖商点击招募清单的下载按钮，获取附件URL并记录操作日志。

- **请求方式**: `POST`
- **URL**: `/scms/consignment/recruit/supplier/v1/download`

#### 请求参数（JSON Body）

**DTO**: `DownloadReqDTO`

| 字段名 | 类型 | 必填 | 说明 | 标注 |
|--------|------|:----:|------|:----:|
| recruitNo | String | **是** | 招募清单编号 | <font color="#DAA520">🟡入参</font> |
| supplierId | Long | **是** | 寄卖商ID | <font color="#DAA520">🟡入参</font> |
| groupId | Long | **是** | 寄卖商集团ID | <font color="#DAA520">🟡入参</font> |

#### 响应参数

| 字段名 | 类型 | 说明 |
|--------|------|------|
| value | String | 附件下载URL |

#### 请求示例

```json
POST /scms/consignment/recruit/supplier/v1/download
Content-Type: application/json

{
  "recruitNo": "SC20260520001",
  "supplierId": 1001,
  "groupId": 2001
}
```

#### 响应示例

```json
{
  "code": 200,
  "message": "success",
  "data": {
    "value": "https://oss.example.com/consignment/recruit/2026/05/SC20260520001.xlsx"
  }
}
```

---

## 二、公共枚举说明

### 2.1 招募清单状态（`listStatus`）

| 编码 | 名称 | 说明 |
|:----:|------|------|
| 10 | 待发布 | 等待定时任务自动发布或后台手动发布 |
| 20 | 招募中 | 开放招募，竞争池未满 |
| 25 | 已抢完 | 竞争池已满5人，不可再加入 |
| 30 | 待审核 | 招募结束，等待审核 |
| 40 | 审核通过 | 审核通过 |
| 50 | 评选中 | 正在执行评选 |
| 60 | 评选完成 | 评选已完成 |
| 70 | 已分配 | 已分配供应商 |
| 90 | 已过期 | 招募已过期 |
| 100 | 已作废 | 后台作废 |

### 2.2 申请状态（`applyStatus`）

| 编码 | 名称 | 说明 |
|:----:|------|------|
| 10 | 已加入 | 寄卖商已加入招募车 |
| 20 | 已开CE | 已创建CE单 |
| 30 | 等待评选 | 已进入评选池 |
| 40 | 分配完成 | 评选获胜，分配完成 |
| 90 | 超时清出 | 超时未开CE被清出 |
| 100 | 放弃/作废 | 主动撤回、手动移除或作废 |

### 2.3 评选结果（`awardResult`）

| 编码 | 名称 | 说明 |
|:----:|------|------|
| 0 | 未评选 | 尚未执行评选 |
| 1 | 获胜 | 评选获胜，获得该清单 |
| 2 | 落选 | 评选落选 |

### 2.4 同货源标识（`sameSourceFlag`）

| 编码 | 名称 | 说明 |
|:----:|------|------|
| 0 | 非同源 | 该寄卖商与该货源无合作母鸡SKU |
| 1 | 同源 | 该寄卖商与该货源已有合作母鸡SKU |

### 2.5 CE状态（`ceStatus`，推断值）

| 值 | 推断条件 | 说明 |
|------|----------|------|
| 未开单 | `ceBillNo IS NULL` | 尚未创建CE单 |
| 已开单 | `ceBillNo IS NOT NULL` | 已创建CE单号 |
| 已发货 | `ceSendTime IS NOT NULL` | CE已发货 |
| 处理完成 | `firstQcPassTime IS NOT NULL` | 首次质检已通过 |

---

## 三、通用响应格式

```json
{
  "code": 200,
  "message": "success",
  "data": { ... }
}
```

| 字段 | 类型 | 说明 |
|------|------|------|
| code | Integer | 状态码（200=成功） |
| message | String | 提示信息 |
| data | Object | 业务数据（根据接口不同结构不同） |

### 分页响应通用格式

```json
{
  "code": 200,
  "message": "success",
  "data": {
    "total": 100,
    "pageNo": 1,
    "pageSize": 10,
    "list": [ ... ]
  }
}
```

---

### `ValueDTO` 包装格式（用于单值返回）

```json
{
  "code": 200,
  "message": "success",
  "data": {
    "value": true
  }
}
```

---

> 本文档由代码生成，于 2026-05-20 更新。
