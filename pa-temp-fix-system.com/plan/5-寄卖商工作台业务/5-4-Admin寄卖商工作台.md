# 5-4 Admin寄卖商工作台

## 一、概述

| 项目 | 说明 |
|------|------|
| **PRD章节** | 2.1.5 Admin寄卖商工作台 |
| **面向用户** | 后台运营人员（Admin视角查看寄卖商工作台） |
| **功能** | Admin只读查看招募市场和招募车，无操作权限 |

### Admin版与普通寄卖商版区别

| 功能模块 | 普通寄卖商 | Admin版 |
|---------|-----------|---------|
| 招募市场列表 | 有"加入招募车"按钮 | 有"下载招募清单"按钮，无"加入招募车" |
| 招募车管理 | 可加入/撤回/移除/开CE单/追加SKU | 只读查看，无操作按钮 |
| 招募结果弹窗 | 查看排名 | 查看排名 |
| 覆盖率 | 查看 | 查看 |
| 同源供应商 | 查看 | 查看 |

---

## 二、接口清单

**基路径**: `/api/scms/consignment/recruit/admin-supplier`
**Controller**: `ConsignmentRecruitAdminSupplierController`
**Service**: `ConsignmentRecruitAdminSupplierService`（委托 `ConsignmentRecruitSupplierService` 读方法实现）

> 所有 POST 请求参数统一放在 JSON Body 中，DTO 复用 `ConsignmentRecruitSupplierService` 内部定义的 `*ReqDTO`。

| 接口 | 方法 | URL | 请求 DTO | 说明 |
|------|------|-----|---------|------|
| 市场头部统计 | POST | `/v1/market/stat` | `MarketStatReqDTO`（supplierId, groupId） | Admin只读查看统计卡片 |
| 市场列表分页 | POST | `/v1/market/page` | `MarketPageReqDTO`（supplierId, groupId + 分页） | Admin只读查看市场列表 |
| 市场SKU列表 | POST | `/v1/market/skuList` | `SkuListReqDTO`（recruitId） | 查看招募清单SKU明细 |
| 招募车列表分页 | POST | `/v1/cart/page` | `CartPageReqDTO`（supplierId, groupId + 分页） | Admin只读查看寄卖商招募车 |
| 招募结果详情 | POST | `/v1/cart/awardDetail` | `AwardDetailReqDTO`（recruitId） | 招募结果排名弹窗 |
| 覆盖率详情 | GET | `/v1/coverage/detail` | Query: applyId | 查看覆盖率详情 |
| 同源供应商列表 | POST | `/v1/sameSource/list` | `SameSourceListReqDTO`（recruitId） | 查看同货源供应商列表 |

---

## 三、代码位置

```
pa-biz-application/pa-scms-application/pa-scms-application-biz/src/main/java/com/ux168/pa/application/scms/biz/
├── apiimpl/consignment/recruit/admin/
│   └── ConsignmentRecruitAdminSupplierController.java  ← Admin寄卖商工作台Controller
└── service/consignment/recruit/
    └── ConsignmentRecruitAdminSupplierService.java      ← Admin寄卖商工作台Service
```

---

## 四、CRUD API 映射

| 数据操作 | CRUD ServiceApi | 说明 |
|---------|----------------|------|
| 清单查询 | `ConsignmentRecruitListServiceApi` | 市场列表/统计 |
| SKU明细 | `ConsignmentRecruitListSkuServiceApi` | SKU列表 |
| 申请查询 | `ConsignmentRecruitApplyServiceApi` | 招募车/覆盖率/同源 |
| 操作日志 | `ConsignmentActionLogServiceApi` | 操作日志（只读） |
