# 招募清单全流程Job测试文档

> 涵盖6个定时Job的执行顺序、逻辑、数据交互与状态走向
> 所有Job API 路由：`/scms/consignment/recruit/autojob/v1/*`
> ⚠️ CeTimeoutClean(03:00)已合并入EvalStart(00:05)；AwardWaitCheck(21:30)已删除(14天等待由CeArrivalCheck兜底)

---

## 一、状态流转总览

### 1.1 各实体状态机

```
recruit_list（清单）:
  10(待发布) ─AutoPublish→ 20(招募中) ──→ 25(已抢完) ─EvalStart→ 35(评选中) ─AutoAward→ 50(分配中) → 60(已完成)
                                      ↘AutoAward或AwardWaitCheck直接评选（跳过评选中）───────────┘
                                      30(无人申请已回收) ─NoApplyClean→
                                                                          50/60 ─RecycleCheck→ 100(作废)
                                          
recruit_apply（招募车）:
  10(已加入) ─开CE单→ 20(已开CE) ─EvalStart(合并超时清理)→ 30(等待评选) ─AutoAward→ 40(分配完成/获胜)
              ─超3天无CE单 (EvalStart清理) → 90(超时清出)                        → 100(放弃/落选者)
    
recruit_publish（发布轮次）:
  20(招募中) ──EvalStart──→ 35(评选中) ──AutoAward──→ 50(分配中)
  25(已抢完) ──EvalStart──→ 35(评选中)
```

### 1.2 两套状态流的"合体"关系

```
         清单流转                             招募车流转
    ┌──────────────┐                   ┌──────────────────┐
    │ 10-待发布     │                   │ 10-已加入         │
    │     ↓        │                   │     ↓ 开CE单      │
    │ 20-招募中     │                   │ 20-已开CE         │
    │    [25-已抢完]│                   │     ↓ 质检通过    │
    │     ↓ Eval   │←──合体──→ 30-等待评选                 │
    │ 35-评选中     │←──同步──→                             │
    │     ↓ Award  │←──合体──→                             │
    │ 50-分配中     │                   │ 40-分配完成(获胜) │
    │     ↓        │                   │ 90/100(落选/清出) │
    │ 60-已完成     │                   └──────────────────┘
    └──────────────┘

    EvalStart 同时处理两侧：清单→35 + apply质检已过→30
    AutoAward 同时处理两侧：清单→50 + apply获胜/落选
```

---

## 二、Job 详细说明

### Job 1. 自动发布 —— AutoPublishJob

| 属性 | 值 |
|------|-----|
| **Handler** | `consignmentRecruitAutoPublishJobHandler` |
| **Service** | `ConsignmentRecruitAutoPublishService` |
| **调度时间** | 每周三 10:00 |
| **API** | `POST /v1/triggerAutoPublish` |
| **操作者** | `system(自动发布)` |

**核心逻辑**：

从 `recruit_list` 读取 `list_status=10(待发布)` 的清单，发布为 `20(招募中)`，并创建 `recruit_publish` 发布轮次记录。

**前置条件**：
- 存在 `list_status=10` 的招募清单（由 AutoGroupJob 组单生成）
- 当天为配置的发布日（默认周三，可配 `publishDayOfWeek`）
- 今日发布量未达上限（`dailyMaxPublishCount`，默认20）

**详细流程**：

```
Step 1 → 判断是否为可发布日期（匹配星期 + 非节假日黑名单）
Step 2 → 计算时间节点（publishBegin/applyBegin/applyEnd/publishEnd）
Step 3 → 查询待发布清单（list_status=10，按配置排序，限上限）
Step 4 → 逐条发布（事务内）：
    4a. 查询 publish 表：已有活跃轮次则跳过
    4b. 计算轮次号：MAX(非CANCELLED的 publish_round) + 1，旧轮次作废
    4c. INSERT publish 记录（publish_status=20）
    4d. 乐观锁 UPDATE list（list_status=10→20，设时间字段）
    4e. UPDATE sku（sku_status=20→30）
    4f. INSERT action_log
```

**状态变更**：
| 实体 | 变更 |
|------|------|
| `recruit_list.list_status` | `10(待发布)` → `20(招募中)` |
| `recruit_publish.publish_status` | 新轮次 → `20(招募中)`，旧轮次 → `100(作废)` |
| `recruit_list_sku.sku_status` | `20(已组单)` → `30(已发布)` |

---

### Job 2. （已合并）CE单超时清出 —— 已并入评选开始Job

> **合并说明**：
> CeTimeoutCleanJob（原调度时间 03:00）已合并到 EvalStartJob（00:05）。
> EvalStartJob 在执行评选开始前，会先全局清理超时未开CE单的申请（status=10 超过配置天数→90）。
> 详见下方 **Job 3. 评选开始** 的 Step 0。

---

### Job 3. 评选开始 —— EvalStartJob（合并超时清理）

| 属性 | 值 |
|------|-----|
| **Handler** | `consignmentRecruitEvalStartJobHandler` |
| **Service** | `ConsignmentRecruitEvalStartService` |
| **调度时间** | 每天 00:05（原CeTimeoutClean 03:00同步执行） |
| **API** | `POST /v1/triggerEvalStart` |
| **操作者** | `system(评选开始)` |

**核心逻辑**：

先全局清理超时未开CE单的 apply（CeTimeoutClean 合并），然后将 `publishEndTime` 已到期的清单从招募状态转为评选中，同时**同步更新招募车状态**——已开CE单 (status=20) 的 apply 设为 `30(等待评选)`，无需等待质检。

**合并处理流程**（共5步）：

```
Step 0 → 全局清理超时 apply（status=10 超过 ceCreateTimeoutDays 天→90）
Step 1 → 查询可评选清单（list_status IN [20,25] AND publish_end_time <= NOW）
Step 2 → 逐张流转（事务，一次处理清单+apply+轮次）：
    2a. 乐观锁 UPDATE list（list_status 20/25 → 35）
    2b. 同步 UPDATE publish（活跃轮次 publish_status 20/25 → 35）
    2c. 同步 UPDATE apply（已开CE单 status=20 → apply_status 30）
    2d. INSERT action_log（含 apply 同步更新数）
```

**apply 同步规则**（`syncApplyStatusToWaitAward`）：
| apply 当前状态 | 是否更新到30 | 说明 |
|:---:|:---:|:---|
| `10(已加入)` | ❌ | 未开CE单，不动 |
| `20(已开CE)` | ✅ | 开CE单成功即可→30，无需等质检 |
| `30(等待评选)` | ❌ | 已是目标状态 |

**状态变更**：
| 实体 | 变更 |
|------|------|
| `recruit_list.list_status` | `20(招募中)/25(已抢完)` → `35(评选中)` |
| `recruit_publish.publish_status` | 活跃轮次 → `35(评选中)` |
| `recruit_apply.apply_status` | `20(已开CE)` → `30(等待评选)` |
| `recruit_apply`（超时） | `10(已加入)` → `90(超时清出)` |

---

### Job 4. CE到货/质检查询 —— CeArrivalCheckJob

| 属性 | 值 |
|------|-----|
| **Handler** | `consignmentRecruitCeArrivalCheckJobHandler` |
| **Service** | `ConsignmentRecruitCeArrivalCheckService` |
| **调度时间** | 每天 12:00 / 18:00（两次） |
| **API** | `POST /v1/triggerCeArrivalCheck` |
| **操作者** | `system(CE到货质检)` |

**核心逻辑**：

仅查 status=30(等待评选) 的 apply，通过 CE 系统 `surfaceOn` 字段判断质检完成，计算覆盖率+同源加权，14天等待期到期则触发评选。

**前置条件**：存在 `apply_status = 30(等待评选)` 的申请（`ce_bill_no` 非空）

**详细流程**：

```
Step 1 → 查询待检查申请（apply_status = 30 AND ce_bill_no非空）
Step 2 → 逐条检查：
    2a. 解析 ce_bill_no（逗号分隔多个CE单号）
    2b. 获取清单SKU列表
    2c. 按CE单号批量查询（getBillDetailByBillNo，M次Feign调用）：
        ← 过滤清单内SKU且 surfaceOn 非空（质检完成）→ qcPassedSkuIds
    2d. 无质检完成SKU → 跳过（等下次轮询）
    2e. 首次质检通过 → 设 firstQcPassTime
    2f. 触发覆盖率计算（handleQcPassEvent）
    2g. INSERT action_log
    2h. 判断14天等待期到期 → 调 AutoAwardService.executeAward()
```

**覆盖率更新**（`CoverageCalcService.handleQcPassEvent`）：
```
inboundSkuCount = 累计入仓SKU数（增量累加）
skuCoverageRate = inboundSkuCount * 100 / skuCount（百分比）
sameSourceWeight = sameSourceSkuCount > 0 ? 10.00 : 0（同源SKU数>0时+10%）
finalCoverageRate = skuCoverageRate + sameSourceWeight
```

> 同源加权规则：已有同货源SKU数 (`sameSourceSkuCount`) > 0 时，固定 +10%，不再从配置读取加权比例。

**14天等待期检查**（`checkWaitPeriodAndAward`）：
```
1. firstQcPassTime IS NULL → 跳过
2. 计算 waitDeadline = 当前时间 - awardWaitDays(14)
3. apply.firstQcPassTime < waitDeadline → 等待期已过
   查询清单下所有apply的最早QC时间
   earliestQcTime < waitDeadline → 调 executeAward(recruitId)
```

**状态变更**：
| 实体 | 变更 |
|------|------|
| `recruit_apply` | `inbound_sku_count`, `final_coverage_rate` 等覆盖率字段增量更新 |
| `recruit_apply` | `first_qc_pass_time` 首次质检完成时设置 |
| `recruit_list.list_status` | -> `50(分配中)`（间接触发，等待期到期且评选时） |

> **Feign调优**：按CE单号批量查询 (`getBillDetailByBillNo`)，M个CE单号每次查询返回该单号所有SKU明细，避免逐SKU×逐CE单号 N×M 次调用。

---

### Job 5. 自动评选分配 —— AutoAwardJob

| 属性 | 值 |
|------|-----|
| **Handler** | `consignmentRecruitAutoAwardJobHandler` |
| **Service** | `ConsignmentRecruitAutoAwardService` |
| **调度时间** | 每天 21:00 |
| **API** | `POST /v1/triggerAutoAward` |
| **操作者** | `system(自动评选)` |

**核心逻辑**：

对已截止申请的清单（apply_end_time <= now），读取各寄卖商的覆盖率数据，按PRD规则决策获胜者。

**前置条件**：存在 `list_status IN [20,25,35]` 且 `apply_end_time <= NOW` 且 `award_time IS NULL` 的清单

**详细流程**：

```
Step 1 → 查询可评选清单（list_status IN [20,25,35] AND apply_end_time <= NOW）
Step 2 → 读取活跃 apply（apply_status >= 20(已开CE) AND < 90(超时清出)）
Step 3 → 计算最早QC时间（MIN(活跃apply.first_qc_pass_time)）
Step 4 → 查找覆盖率最高者（finalCoverageRate最高，同分按 createTime 更早）
Step 5 → 决策三岔口：
    CASE A（有人达标）: maxCoverageRate >= directAwardCoverageRate*100(默认80%)
      动作：覆盖率最高者获胜，award_reason="direct"
    
    CASE B（无人达标，等待期已过）: earliestQcTime < 当前时间 - awardWaitDays(14)
      动作：覆盖率最高者获胜，award_reason="highest"
    
    CASE C（条件不满足）: 无人>=80% 且 (无QC通过 或 QC未满14天)
      动作：不做更新，返回false。CeArrivalCheckJob后续轮询到货时会间接触发，也可手动调triggerAutoAward重试
Step 6 → CASE A/B 时执行更新（事务）：
    获胜 apply:  apply_status=40, award_result=1(WINNER), rank_no=1
    未获胜 apply: award_result=2(LOSER), rank_no=0
    recruit_list: list_status=50, award_time=now, award_supplier_id=获胜者
Step 7 → INSERT action_log
```

**评选规则**：
```
同分兜底：覆盖率相同 → createTime（加入招募车时间）更早者胜
⚠️ 注意不是 firstQcPassTime，是申请加入招募车的时间
```

**状态变更**：
| 实体 | CASE A/B 变更 | CASE C 变更 |
|------|:---:|:---:|
| `recruit_list.list_status` | `20/25/35` → `50(分配中)` | 不变 |
| `recruit_apply(获胜)` | `20/30` → `40(分配完成)` + `award_result=1` | 不变 |
| `recruit_apply(落选)` | `award_result=2(LOSER)`，状态不变 | 不变 |

---

### Job 6. 无人清单作废 —— NoApplyCleanJob

| 属性 | 值 |
|------|-----|
| **Handler** | `consignmentRecruitNoApplyCleanJobHandler` |
| **Service** | `ConsignmentRecruitNoApplyCleanService` |
| **调度时间** | 每天 21:15 |
| **API** | `POST /v1/triggerNoApplyClean` |
| **操作者** | `system(无人清单清理)` |

**核心逻辑**：

21:00 申请截止后，清理已发布但无人申请的清单（没有任何 apply 记录），释放SKU回招募池。

**前置条件**：存在 `list_status IN [20,25]` 且 `apply_end_time <= NOW` 且 `award_time IS NULL` 的清单，且该清单 `findByRecruitId()` 返回空（无任何申请）

**详细流程**：

```
Step 1 → 查询候选清单（list_status IN [20,25] AND apply_end_time <= NOW）
Step 2 → Java 过滤（award_time IS NULL，findByRecruitId 为空）
Step 3 → 逐条回收（事务）：
    3a. 乐观锁 UPDATE list（list_status 20/25 → 30）
    3b. 设置作废人信息（cancel_user_name, cancel_time, cancel_type）
    3c. 释放SKU回池（recruit_id=0, recruit_no=null, sku_status=10）
    3d. INSERT action_log
```

> 查询条件不含 `35(评选中)` — 进入评选的清单必然有申请者。
> 时间窗口：21:00-21:15 之间可能有人申请，本Job会检查 `findByRecruitId()` 确认无人。

**状态变更**：
| 实体 | 变更 |
|------|------|
| `recruit_list.list_status` | `20(招募中)/25(已抢完)` → `30(无人申请已回收)` |
| `recruit_list_sku` | `recruit_id=0, sku_status=10(待组单)` → 回招募池 |

---

---

### Job 6. 清单回收检查 —— RecycleCheckJob

| 属性 | 值 |
|------|-----|
| **Handler** | `consignmentRecruitRecycleCheckJobHandler` |
| **Service** | `ConsignmentRecruitRecycleCheckService` |
| **调度时间** | 每月1日 04:00 |
| **API** | `POST /v1/triggerRecycleCheck` |
| **操作者** | `system(清单回收)` |

**核心逻辑**：

评估已分配清单（50/60）的获胜寄卖商持续覆盖率，连续不达标则回收清单、释放SKU。

**前置条件**：存在 `list_status IN [50(分配中), 60(清单完成)]` 的清单，且 `award_supplier_id` 非空

**详细流程**：

```
Step 1 → 查询已分配清单（list_status IN [50,60]）
          Java 过滤：award_time != null, award_supplier_id != null
Step 2 → 评估覆盖率（evaluateCoverage）：
    2a. 查询获胜申请（award_apply_id）
    2b. 读取 finalCoverageRate
    2c. 判断是否低于阈值（recycleCoverageThreshold * 100 = 默认80%）
        ⚠️ TODO：跨模块查询供应商月度覆盖率，计算连续不达标月数
Step 3 → 不达标 → 回收（事务）：
    3a. 乐观锁 UPDATE list（list_status 50/60 → 100）
    3b. 设置作废人信息（cancel_user_name, cancel_time, cancel_type）
    3c. 释放SKU回池（recruit_id=0, sku_status=10）
    3d. INSERT action_log
    达标 → 不变
```

**状态变更**：
| 实体 | 不达标 | 达标 |
|------|:---:|:---:|
| `recruit_list.list_status` | `50/60` → `100(作废)` | 不变 |
| `recruit_list_sku` | 回招募池 | 不变 |

---

## 三、API 执行顺序与调用关系

### 3.1 正常 Happy Path 执行顺序

```
按业务逻辑顺序执行（清单→招募→到货→评选→分配）：
Step 1: triggerAutoPublish              10(待发布) → 20(招募中)
Step 2: 手动模拟寄卖商加入+开CE单          apply status → 20(已开CE)
Step 3: triggerEvalStart                 先全局清理超时apply(10→90)，再清单→35，apply已开CE→30
Step 4: triggerCeArrivalCheck            检测CE到货(surfaceOn)→覆盖率+同源加权10%→apply→30
Step 5: triggerAutoAward                 评选→获胜者→清单→50(分配中)
Step 6: triggerRecycleCheck              检查持续达标情况（每月触发）

注：CeTimeoutClean(原Step3) 已合并到 triggerEvalStart，无需单独执行。
AwardWaitCheck(原Step7后) 已删除，由CeArrivalCheck间接触发14天兜底评选。
```

### 3.2 状态时间线

```
Day 1 - 周三 10:00  AutoPublishJob        清单发布 → 供应商开始抢单
Day 1 - 周三 14:00  供应商加入+开CE单      apply: 10→20
Day 1~D - 00:05     EvalStartJob(合并超时) 清理超时apply(10→90) + 清单→35 + apply已开CE→30
Day 2~D   12/18:00 CeArrivalCheckJob      质检通过(surfaceOn) → 覆盖率+同源10% + 14天到评选
Day D 21:00        AutoAwardJob          评选 → 清单: 50，apply获胜→40
Day D 21:15        NoApplyCleanJob       无人申请清单 → 30(回收)
每月1日 04:00     RecycleCheckJob        回收不达标清单

注：Day D 表示清单申请截止日（apply_end_time 到期）。14天等待期内CeArrivalCheck持续轮询到货。
```

---

## 四、测试验证SQL速查

```sql
-- 1. 检查清单状态
SELECT id, recruit_no, list_status, award_time, publish_end_time
FROM scms_consignment_recruit_list WHERE id = ?;

-- 2. 检查招募车状态
SELECT id, recruit_id, supplier_id, apply_status, first_qc_pass_time,
       inbound_sku_count, final_coverage_rate, award_result
FROM scms_consignment_recruit_apply WHERE recruit_id = ?;

-- 3. 检查发布轮次
SELECT id, recruit_id, publish_round, publish_status, publish_end_time
FROM scms_consignment_recruit_publish WHERE recruit_id = ?;

-- 4. 检查SKU状态
SELECT id, recruit_id, sku_id, sku_status
FROM scms_consignment_recruit_list_sku WHERE recruit_id = ?;

-- 5. 检查操作日志
SELECT id, recruit_id, apply_id, action, operator_name, content, create_time
FROM scms_consignment_action_log WHERE recruit_id = ? ORDER BY create_time;
```

---

## 五、API 接口列表

所有 Job 触发 API 无请求体（POST），统一返回 `JobTriggerResultDTO`：

```json
{
  "state": { "code": 200, "msg": "success" },
  "data": {
    "totalCount": 5,
    "successCount": 3,
    "failCount": 2,
    "message": "处理完成"
  }
}
```

| 步骤 | API Path | Job | 自动调度 | 调用前准备 |
|:---:|----------|-----|---------|-----------|
| 1 | `/v1/triggerAutoPublish` | AutoPublish | 周三10:00 | 有 status=10 的清单 |
| 2 | `/v1/triggerEvalStart` | EvalStart（合并CeTimeoutClean） | 00:05 | 清单 publish_end_time <= now |
| 3 | `/v1/triggerCeArrivalCheck` | CeArrivalCheck | 12:00/18:00 | 有 status=30 且有ce_bill_no的apply |
| 4 | `/v1/triggerAutoAward` | AutoAward | 21:00 | 清单 apply_end_time <= now |
| 5 | `/v1/triggerNoApplyClean` | NoApplyClean | 21:15 | 清单无任何apply记录 |
| 6 | `/v1/triggerRecycleCheck` | RecycleCheck | 每月1日 | 有 status=50/60 的清单 |
| - | `/v1/rollbackPublish` | 回滚发布 | - | 传入 recruitNos，回滚发布到待发布 |
