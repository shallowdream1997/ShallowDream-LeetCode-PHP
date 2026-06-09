# 7-1 供应链管理 Controller

## 一、概述

- **PRD章节**: 2.1.3 后台供应链管理
- **面向用户**: PA业务管理系统后台运营人员
- **功能说明**: 提供招募清单的查询、详情、发布、作废、导出等功能，以及招募池的导入/导出/删除/手动组单等管理操作 Controller 层接口定义
- **所属模块**: `pa-scms-application` → `pa-scms-application-biz`
- **Controller 包路径**: `com.ux168.pa.application.scms.biz.apiimpl.consignment.recruit.admin`
- **Application Service 包路径**: `com.ux168.pa.application.scms.biz.service.consignment.recruit`

## 二、接口清单

### 2.1 招募清单管理 (ConsignmentRecruitAdminController)

- **基路径**: `/api/scms/consignment/recruit/admin`
- **数据来源**: 注入 Application Service（`ConsignmentRecruitAdminService`），Service 内部通过 Feign 调用 CRUD ServiceApi

| 接口 | 方法 | URL | 请求DTO | 响应DTO |
|------|------|-----|---------|---------|
| 分页查询招募清单 | POST | `/page` | `RecruitListQueryDTO` | `PageResult<RecruitListRespDTO>` |
| 获取招募清单详情 | POST | `/detail` | `RecruitDetailQueryDTO` | `RecruitListDetailRespDTO` |
| 获取招募清单详情(聚合) | GET | `/detailWithApply` | `recruitId` | `RecruitListDetailRespDTO` |
| 发布清单 | POST | `/publish` | `RecruitPublishDTO` | `Void` |
| 批量作废清单 | POST | `/batch-cancel` | `BatchCancelDTO` | `Void` |
| 导出清单 | POST | `/export` | `RecruitListQueryDTO` | 文件流 (HttpServletResponse) |
| 导出作废清单及原因 | POST | `/export-cancel` | `RecruitListQueryDTO` | 文件流 (HttpServletResponse) |
| 导出预招募清单(下次发布) | POST | `/export-pre-publish` | 无 | 文件流 (HttpServletResponse) |

### 2.2 招募申请管理 (ConsignmentRecruitAdminController)

| 接口 | 方法 | URL | 请求DTO | 响应DTO |
|------|------|-----|---------|---------|
| 招募申请列表分页 | POST | `/apply/page` | `ConsignmentRecruitApplyPageReqDTO` | `ConsignmentRecruitApplyPageRespDTO` |
| 招募申请列表分页(Admin) | POST | `/apply/listPage` | `ConsignmentRecruitApplyPageReqDTO` | `ConsignmentRecruitApplyPageRespDTO` |
| 招募申请详情 | GET | `/apply/detail` | `id` | `ConsignmentRecruitApplyRespDTO` |

### 2.3 招募池管理 (ConsignmentRecruitPoolController)

- **基路径**: `/api/scms/consignment/recruit/pool`
- **数据来源**: 注入 Application Service（`ConsignmentRecruitPoolService`），Service 内部通过 Feign 调用 CRUD ServiceApi

| 接口 | 方法 | URL | 请求DTO | 响应DTO |
|------|------|-----|---------|---------|
| 分页查询招募池 | POST | `/page` | `RecruitPoolQueryDTO` | `PageResult<RecruitPoolRespDTO>` |
| 导入招募SKU | POST | `/import` | `MultipartFile file` | `RecruitImportResultRespDTO` |
| 下载导入模板 | GET | `/import-template` | 无 | 文件流 (HttpServletResponse) |
| 导出招募池SKU | POST | `/export` | `RecruitPoolQueryDTO` | 文件流 (HttpServletResponse) |
| 删除池内SKU | POST | `/delete` | `List<Long> skuIds` | `Void` |
| 手动组单(后台触发) | POST | `/manual-group` | 无 | `Void` |

## 三、Controller 标准流程

```
前端请求 → Controller接收参数 → 调用Application Service → Service内部封装CRUD调用 → 返回RespDTO → Controller封装Result返回
```

### 3.1 查询类接口流程

```
@PostMapping("/page")
public Result<PageResult<RecruitListRespDTO>> pageQuery(@RequestBody RecruitListQueryDTO queryDTO) {
    // 1. 参数校验 (JSR303 @Valid)
    // 2. 调用Application Service（非Feign）
    PageResult<RecruitListRespDTO> result = adminService.pageQuery(queryDTO);
    // 3. 返回统一包装
    return Result.success(result);
}
```

### 3.2 操作类接口流程

```
@PostMapping("/publish")
public Result<Void> publish(@Valid @RequestBody RecruitPublishDTO publishDTO) {
    // 1. 参数校验 (@Valid)
    // 2. 调用Application Service
    adminService.publish(publishDTO);
    // 3. 返回成功
    return Result.success();
}
```

## 四、状态走向

Controller 层不涉及状态变更，状态枚举定义在 Service 层，Controller 仅透传 DTO。

涉及的状态枚举（参考各功能子文件）：
- **list_status**: 清单状态 10→20→25/30→50→60→100
- **sku_status**: SKU状态 10→20→30→100
- **apply_status**: 申请状态 10→20→30→40→50→90→100

## 五、代码位置

```
pa-biz-application/pa-scms-application/pa-scms-application-biz/src/main/java/com/ux168/pa/application/scms/biz/
├── apiimpl/consignment/recruit/   ← Controller
│   ├── admin/
│   │   └── ConsignmentRecruitAdminController.java    ← 后台供应链管理Controller
│   └── pool/
│       └── ConsignmentRecruitPoolController.java      ← 招募池管理Controller
└── service/consignment/recruit/   ← Application Service
    ├── ConsignmentRecruitAdminService.java
    └── ConsignmentRecruitPoolService.java
```

## 六、难点与解决点

| 难点 | 解决方案 |
|------|---------|
| 导出接口返回文件流 | Controller 中通过 `HttpServletResponse` 直接输出，Application Service 内生成文件内容（字节数组）返回给 Controller 写入流 |
| 文件上传接口`MultipartFile` | Controller 直接接收 `MultipartFile`，传递给 Application Service 处理（调用 CRUD ServiceApi 之前先解析文件内容）|
| 手动组单需同步触发异步任务 | Controller 调用 Application Service 后，Service 内部通过 `@Async` 或 `TaskExecutor` 提交异步任务，Controller 返回成功即可，避免HTTP超时 |
| 批量操作的事务边界 | Application Service 统一处理事务，Controller 仅做参数校验和结果返回 |

---

## 七、CRUD API 依赖

当前 Controller 注入 Application Service，由 Service 内部调用 CRUD ServiceApi：

| 业务功能 | 依赖的 CRUD ServiceApi | API 文档 |
|---------|----------------------|---------|
| 清单列表/详情 | `ConsignmentRecruitListServiceApi` | [8-CRUD第11章](../8-CRUD数据操作层技术方案.md#十一开放-api-接口serviceapi) |
| 招募池管理 | `ConsignmentRecruitListSkuServiceApi` | 同上 |
| 发布记录 | `ConsignmentRecruitPublishServiceApi` | 同上 |
| 申请统计 | `ConsignmentRecruitApplyServiceApi` | 同上 |
| 操作日志 | `ConsignmentActionLogServiceApi` | 同上 |

> 所有 CRUD ServiceApi 定义为 `@FeignClient(name=..., contextId=..., path=..., url = FeignConstants.DELEGATE_CONFIG)`
