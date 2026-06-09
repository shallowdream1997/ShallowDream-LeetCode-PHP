# 8-CRUD 数据操作层技术方案

## 一、架构概述

### 1.1 数据访问层四层架构

```
┌─────────────────────────────────────────────────────────┐
│  Service 业务层                                         │
│  (service/consignment/recruit/)                         │
├─────────────────────────────────────────────────────────┤
│  Repository 仓储层接口 + 实现                            │
│  (dao/mysql/consignment/recruit/repository/)            │
│  ┌─────────────────────────────────────────────────────┐│
│  │  Repository 接口  ←  BaseRepository<PO, Long>      ││
│  │  RepositoryImpl  ←  BaseRepositoryImpl<PO, Long>   ││
│  └─────────────────────────────────────────────────────┘│
├─────────────────────────────────────────────────────────┤
│  Mapper 接口层                                          │
│  (dao/mysql/consignment/recruit/mapper/)                │
│  BaseMapper<PO> + 自定义方法                            │
├─────────────────────────────────────────────────────────┤
│  PO 实体层                                              │
│  (dao/mysql/consignment/recruit/po/)                    │
│  PO 继承 CommonPO → 继承 BasePO                         │
└─────────────────────────────────────────────────────────┘
```

### 1.2 基础类说明

| 类名 | 来源 | 说明 |
|------|------|------|
| `BasePO` | `platform-infrastructure-mysql` | 基础PO，包含 `id`(主键)、`version`(乐观锁) |
| `CommonPO` | `po/CommonPO.java` | 继承BasePO，增加 `create_by`、`update_by`、`create_time`、`update_time`、`is_deleted`(逻辑删除) |
| `BaseRepository<PO, Long>` | `platform-infrastructure-mysql` | 仓储接口基类，定义 getById/list/save/saveBatch/updateById/remove 等 |
| `BaseRepositoryImpl<PO, Long>` | `platform-infrastructure-mysql` | 仓储实现基类，继承 MyBatis-Plus `ServiceImpl`，实现 BaseRepository |
| `BizLambdaQueryWrapper<PO>` | `platform-infrastructure-mysql` | 扩展 LambdaQueryWrapper，增加 `eqIfPresent`/`inIfPresent`/`likeIfPresent`/`geIfPresent`/`leIfPresent` 等方法 |
| `ListResult<T>` | `platform-infrastructure-common` | 分页响应包装，包含 `list`(数据列表) + `page`(RespPageDO) |

### 1.3 模块包路径

```
pa-biz-service/pa-scms-service/pa-scms-service-biz/src/main/java/com/ux168/pa/service/scms/biz/
├── dao/mysql/consignment/recruit/
│   ├── po/            ← PO实体类
│   ├── mapper/        ← Mapper接口
│   └── repository/    ← Repository仓储层
│       └── impl/      ← Repository实现
└── service/consignment/recruit/
    └── bo/            ← 查询条件BO
```

---

## 二、PO 实体类

### 2.1 CommonPO 基类（已存在）

```java
package com.ux168.pa.service.scms.biz.common.po;

import com.baomidou.mybatisplus.annotation.FieldFill;
import com.baomidou.mybatisplus.annotation.TableField;
import com.baomidou.mybatisplus.annotation.TableLogic;
import com.ux168.platform.infrastructure.mysql.po.BasePO;
import lombok.Data;
import java.util.Date;

@Data
public class CommonPO extends BasePO {
    private static final long serialVersionUID = 1L;

    @TableField(value = "create_by", fill = FieldFill.INSERT)
    protected String createBy;

    @TableField(value = "update_by", fill = FieldFill.INSERT_UPDATE)
    protected String updateBy;

    @TableField(value = "create_time", fill = FieldFill.INSERT)
    protected Date createTime;

    @TableField(value = "update_time", fill = FieldFill.INSERT_UPDATE)
    protected Date updateTime;

    @TableField("is_deleted")
    @TableLogic  // 逻辑删除注解
    protected Integer isDeleted;
}
```

**继承 CommonPO 后 PO 自动获得的字段**：
- `id` (Long, PK) — 来自 BasePO
- `createBy` (String) — 创建人
- `updateBy` (String) — 更新人
- `createTime` (Date) — 创建时间（自动填充 INSERT）
- `updateTime` (Date) — 更新时间（自动填充 INSERT_UPDATE）
- `isDeleted` (Integer) — 逻辑删除标记（@TableLogic）
- `version` (Integer) — 乐观锁版本号（来自 BasePO）

### 2.2 招募清单主表 PO

```java
package com.ux168.pa.service.scms.biz.dao.mysql.consignment.recruit.po;

import com.baomidou.mybatisplus.annotation.TableName;
import com.ux168.pa.service.scms.biz.common.po.CommonPO;
import lombok.Data;
import lombok.EqualsAndHashCode;
import java.math.BigDecimal;
import java.util.Date;

@Data
@EqualsAndHashCode(callSuper = true)
@TableName("scms_consignment_recruit_list")
public class ConsignmentRecruitListPO extends CommonPO {

    private String recruitNo;
    private Long factoryId;
    private String factoryName;
    private Long categoryId;
    private String categoryFullPathId;
    private Integer skuCount;
    private String fileUrl;
    private BigDecimal estimatedCost;
    private Integer estimatedMonthSaleQty;
    private BigDecimal estimatedMonthSaleAmount;
    private BigDecimal avgMoq;
    private Integer listStatus;
    private Integer listType;
    private Date groupTime;
    private Date publishBeginTime;
    private Date applyBeginTime;
    private Date applyEndTime;
    private Date publishEndTime;
    private Date awardTime;
    private Long awardSupplierId;
    private Long awardGroupId;
    private Long awardApplyId;
    private String awardCeBillNo;
    private String publishBy;
    private String auditBy;
    private String awardBy;
    private String cancelUserName;
    private Date cancelTime;
    private String cancelType;
    private String cancelReason;
    private String remark;
    private Long tenantId;
    private Long instanceId;
    private Long applicationId;
}
```

### 2.3 招募清单SKU明细 PO

```java
package com.ux168.pa.service.scms.biz.dao.mysql.consignment.recruit.po;

import com.baomidou.mybatisplus.annotation.TableName;
import com.ux168.pa.service.scms.biz.common.po.CommonPO;
import lombok.Data;
import lombok.EqualsAndHashCode;
import java.math.BigDecimal;
import java.util.Date;

@Data
@EqualsAndHashCode(callSuper = true)
@TableName("scms_consignment_recruit_list_sku")
public class ConsignmentRecruitListSkuPO extends CommonPO {

    private Long recruitId;       // =0 表示在招募池中未组单
    private String recruitNo;
    private Long skuId;
    private String skuName;
    private Long categoryId;
    private Long factoryId;
    private String factoryName;
    private String purchaseUrl;
    private String productModel;
    private String vehicleModel;
    private String unit;
    private String packageInfo;
    private BigDecimal grossWeightG;
    private String packageSizeCm;
    private String sourceModel;
    private Integer deliveryDays;
    private Integer moq;
    private BigDecimal costPrice;
    private Integer saleQty90d;
    private Integer saleQty30d;
    private Integer replenishRemind21d;
    private Integer sourceType;
    private Integer skuStatus;
    private String importBatchNo;
    private String importUser;
    private Date importTime;
    private String failReason;
    private Long tenantId;
    private Long instanceId;
    private Long applicationId;
}
```

### 2.4 发布记录 PO

```java
package com.ux168.pa.service.scms.biz.dao.mysql.consignment.recruit.po;

import com.baomidou.mybatisplus.annotation.TableName;
import com.ux168.pa.service.scms.biz.common.po.CommonPO;
import lombok.Data;
import lombok.EqualsAndHashCode;
import java.util.Date;

@Data
@EqualsAndHashCode(callSuper = true)
@TableName("scms_consignment_recruit_publish")
public class ConsignmentRecruitPublishPO extends CommonPO {

    private Long recruitId;
    private String recruitNo;
    private Integer publishRound;
    private Integer publishStatus;
    private Date publishBeginTime;
    private Date applyBeginTime;
    private Date applyEndTime;
    private Date publishEndTime;
    private Long publishJobId;
    private Long tenantId;
    private Long instanceId;
    private Long applicationId;
}
```

### 2.5 招募申请表 PO

```java
package com.ux168.pa.service.scms.biz.dao.mysql.consignment.recruit.po;

import com.baomidou.mybatisplus.annotation.TableName;
import com.ux168.pa.service.scms.biz.common.po.CommonPO;
import lombok.Data;
import lombok.EqualsAndHashCode;
import java.math.BigDecimal;
import java.util.Date;

@Data
@EqualsAndHashCode(callSuper = true)
@TableName("scms_consignment_recruit_apply")
public class ConsignmentRecruitApplyPO extends CommonPO {

    private Long recruitId;
    private String recruitNo;
    private Long supplierId;
    private String supplierName;
    private Long groupId;
    private Long factoryId;
    private Integer applyStatus;
    private Integer sameSourceFlag;
    private String ceBillNo;
    private Date ceCreateTime;
    private Date ceSendTime;
    private Date firstQcPassTime;
    private String removedType;
    private Integer inboundSkuCount;
    private BigDecimal baseCoverageRate;
    private BigDecimal sameSourceWeightRate;
    private BigDecimal finalCoverageRate;
    private Integer rankNo;
    private Integer awardResult;
    private String awardReason;
    private String cancelType;
    private String cancelReason;
    private Long tenantId;
    private Long instanceId;
    private Long applicationId;
}
```

### 2.6 动作日志表 PO

```java
package com.ux168.pa.service.scms.biz.dao.mysql.consignment.recruit.po;

import com.baomidou.mybatisplus.annotation.TableName;
import com.ux168.pa.service.scms.biz.common.po.CommonPO;
import lombok.Data;
import lombok.EqualsAndHashCode;
import java.util.Date;

@Data
@EqualsAndHashCode(callSuper = true)
@TableName("scms_consignment_action_log")
public class ConsignmentActionLogPO extends CommonPO {

    private Long recruitId;
    private String recruitNo;
    private Long applyId;
    private Long supplierId;
    private String action;
    private Integer beforeStatus;
    private Integer afterStatus;
    private Integer operatorType;
    private String operatorId;
    private String operatorName;
    private String content;
    private String requestId;
    private Long tenantId;
    private Long instanceId;
    private Long applicationId;
}
```

---

## 三、Mapper 接口

### 3.1 Mapper 通用模式

每个 Mapper 继承 `BaseMapper<PO>`，获得 MyBatis-Plus 基础 CRUD 方法：
- `insert(entity)` / `deleteById(id)` / `updateById(entity)` / `selectById(id)`
- `selectList(wrapper)` / `selectCount(wrapper)` / `selectPage(page, wrapper)`
- `insertBatch` (需自行加 @Transactional)

自定义复杂查询（联表、统计、指定字段）通过 Mapper XML 实现。

### 3.2 清单主表 Mapper

```java
package com.ux168.pa.service.scms.biz.dao.mysql.consignment.recruit.mapper;

import com.baomidou.mybatisplus.core.mapper.BaseMapper;
import com.ux168.pa.service.scms.biz.dao.mysql.consignment.recruit.po.ConsignmentRecruitListPO;

public interface ConsignmentRecruitListMapper extends BaseMapper<ConsignmentRecruitListPO> {
    // 基础CRUD由BaseMapper提供，无需额外声明
    // 复杂联表查询在XML中定义
}
```

XML 映射文件: `resources/mapper/consignment/recruit/ConsignmentRecruitListMapper.xml`

### 3.3 SKU明细 Mapper

```java
package com.ux168.pa.service.scms.biz.dao.mysql.consignment.recruit.mapper;

import com.baomidou.mybatisplus.core.mapper.BaseMapper;
import com.ux168.pa.service.scms.biz.dao.mysql.consignment.recruit.po.ConsignmentRecruitListSkuPO;

public interface ConsignmentRecruitListSkuMapper extends BaseMapper<ConsignmentRecruitListSkuPO> {
    // 基础CRUD由BaseMapper提供
}
```

XML 映射文件: `resources/mapper/consignment/recruit/ConsignmentRecruitListSkuMapper.xml`

### 3.4 发布记录 Mapper

```java
package com.ux168.pa.service.scms.biz.dao.mysql.consignment.recruit.mapper;

import com.baomidou.mybatisplus.core.mapper.BaseMapper;
import com.ux168.pa.service.scms.biz.dao.mysql.consignment.recruit.po.ConsignmentRecruitPublishPO;

public interface ConsignmentRecruitPublishMapper extends BaseMapper<ConsignmentRecruitPublishPO> {
}
```

XML 映射文件: `resources/mapper/consignment/recruit/ConsignmentRecruitPublishMapper.xml`

### 3.5 申请表 Mapper

```java
package com.ux168.pa.service.scms.biz.dao.mysql.consignment.recruit.mapper;

import com.baomidou.mybatisplus.core.mapper.BaseMapper;
import com.ux168.pa.service.scms.biz.dao.mysql.consignment.recruit.po.ConsignmentRecruitApplyPO;

public interface ConsignmentRecruitApplyMapper extends BaseMapper<ConsignmentRecruitApplyPO> {
}
```

XML 映射文件: `resources/mapper/consignment/recruit/ConsignmentRecruitApplyMapper.xml`

### 3.6 日志表 Mapper

```java
package com.ux168.pa.service.scms.biz.dao.mysql.consignment.recruit.mapper;

import com.baomidou.mybatisplus.core.mapper.BaseMapper;
import com.ux168.pa.service.scms.biz.dao.mysql.consignment.recruit.po.ConsignmentActionLogPO;

public interface ConsignmentActionLogMapper extends BaseMapper<ConsignmentActionLogPO> {
}
```

XML 映射文件: `resources/mapper/consignment/recruit/ConsignmentActionLogMapper.xml`

---

## 四、Repository 仓储层

### 4.1 仓储层设计原则

- **接口** 继承 `BaseRepository<PO, Long>`，声明自定义方法
- **实现** 继承 `BaseRepositoryImpl<PO, Long>`，实现接口方法
- 继承得到的通用方法：`getById(id)`、`list()`、`list(wrapper)`、`save(entity)`、`saveBatch(list)`、`updateById(entity)`、`update(wrapper)`、`removeById(id)`、`page(page, wrapper)`
- 自定义方法遵循命名规范：`findList`(列表)、`pageList`(分页)、`count`(计数)、`batchUpdate/BatchInsert`(批量)

### 4.2 ConsignmentRecruitListRepository

#### 4.2.1 接口

```java
package com.ux168.pa.service.scms.biz.dao.mysql.consignment.recruit.repository;

import com.ux168.pa.service.scms.biz.dao.mysql.consignment.recruit.po.ConsignmentRecruitListPO;
import com.ux168.pa.service.scms.biz.service.consignment.recruit.bo.RecruitListQueryBO;
import com.ux168.platform.infrastructure.common.api.domain.ListResult;
import com.ux168.platform.infrastructure.mysql.repository.BaseRepository;

import java.util.List;

public interface ConsignmentRecruitListRepository extends BaseRepository<ConsignmentRecruitListPO, Long> {

    /** 根据状态查询清单列表 */
    List<ConsignmentRecruitListPO> findByStatus(Integer listStatus);

    /** 根据多状态查询清单列表 */
    List<ConsignmentRecruitListPO> findByStatusIn(List<Integer> statusList);

    /** 查询待发布的清单（按组单时间升序） */
    List<ConsignmentRecruitListPO> findWaitPublishList();

    /** 批量更新清单状态 */
    int batchUpdateStatus(List<Long> ids, Integer oldStatus, Integer newStatus);

    /** 获得招募清单记录数 */
    Long count(RecruitListQueryBO queryBO);

    /** 获得招募清单列表 */
    List<ConsignmentRecruitListPO> findList(RecruitListQueryBO queryBO);

    /** 获得招募清单分页 */
    ListResult<ConsignmentRecruitListPO> pageList(RecruitListQueryBO pageBO, Integer pageNo, Integer pageSize);

    /** 批量插入招募清单 */
    void insertBatch(List<ConsignmentRecruitListPO> list);

    /** 批量更新招募清单 */
    Boolean updateBatch(List<ConsignmentRecruitListPO> list);
}
```

#### 4.2.2 实现

```java
package com.ux168.pa.service.scms.biz.dao.mysql.consignment.recruit.repository.impl;

import com.ux168.pa.service.scms.biz.dao.mysql.consignment.recruit.mapper.ConsignmentRecruitListMapper;
import com.ux168.pa.service.scms.biz.dao.mysql.consignment.recruit.po.ConsignmentRecruitListPO;
import com.ux168.pa.service.scms.biz.dao.mysql.consignment.recruit.repository.ConsignmentRecruitListRepository;
import com.ux168.pa.service.scms.biz.service.consignment.recruit.bo.RecruitListQueryBO;
import com.ux168.platform.infrastructure.common.api.domain.ListResult;
import com.ux168.platform.infrastructure.mysql.mapper.BizLambdaQueryWrapper;
import com.ux168.platform.infrastructure.mysql.repository.impl.BaseRepositoryImpl;
import org.apache.commons.collections4.CollectionUtils;
import org.springframework.stereotype.Repository;

import javax.annotation.Resource;
import java.util.List;

@Repository
public class ConsignmentRecruitListRepositoryImpl
        extends BaseRepositoryImpl<ConsignmentRecruitListPO, Long>
        implements ConsignmentRecruitListRepository {

    @Resource
    private ConsignmentRecruitListMapper consignmentRecruitListMapper;

    // ===== 自定义查询方法 =====

    @Override
    public List<ConsignmentRecruitListPO> findByStatus(Integer listStatus) {
        return findList(new BizLambdaQueryWrapper<ConsignmentRecruitListPO>()
                .eq(ConsignmentRecruitListPO::getListStatus, listStatus)
                .orderByDesc(ConsignmentRecruitListPO::getUpdateTime));
    }

    @Override
    public List<ConsignmentRecruitListPO> findByStatusIn(List<Integer> statusList) {
        return findList(new BizLambdaQueryWrapper<ConsignmentRecruitListPO>()
                .in(ConsignmentRecruitListPO::getListStatus, statusList)
                .orderByDesc(ConsignmentRecruitListPO::getUpdateTime));
    }

    @Override
    public List<ConsignmentRecruitListPO> findWaitPublishList() {
        return findList(new BizLambdaQueryWrapper<ConsignmentRecruitListPO>()
                .eq(ConsignmentRecruitListPO::getListStatus, 10) // WAIT_PUBLISH
                .isNotNull(ConsignmentRecruitListPO::getGroupTime)
                .orderByAsc(ConsignmentRecruitListPO::getGroupTime));
    }

    @Override
    public int batchUpdateStatus(List<Long> ids, Integer oldStatus, Integer newStatus) {
        ConsignmentRecruitListPO updateEntity = new ConsignmentRecruitListPO();
        updateEntity.setListStatus(newStatus);
        return consignmentRecruitListMapper.update(updateEntity,
                new BizLambdaQueryWrapper<ConsignmentRecruitListPO>()
                        .in(ConsignmentRecruitListPO::getId, ids)
                        .eq(ConsignmentRecruitListPO::getListStatus, oldStatus));
    }

    // ===== BO条件查询标准方法 =====

    @Override
    public Long count(RecruitListQueryBO queryBO) {
        return count(buildQueryWrapper(queryBO));
    }

    @Override
    public List<ConsignmentRecruitListPO> findList(RecruitListQueryBO queryBO) {
        return findList(buildQueryWrapper(queryBO)
                .orderByDesc(ConsignmentRecruitListPO::getUpdateTime));
    }

    @Override
    public ListResult<ConsignmentRecruitListPO> pageList(RecruitListQueryBO queryBO, Integer pageNo, Integer pageSize) {
        return pageList(buildQueryWrapper(queryBO)
                .orderByDesc(ConsignmentRecruitListPO::getUpdateTime), pageNo, pageSize);
    }

    @Override
    public void insertBatch(List<ConsignmentRecruitListPO> list) {
        if (CollectionUtils.isEmpty(list)) {
            return;
        }
        saveOrUpdateBatch(list);
    }

    @Override
    public Boolean updateBatch(List<ConsignmentRecruitListPO> list) {
        if (CollectionUtils.isEmpty(list)) {
            return true;
        }
        return super.updateBatch(list);
    }

    // ===== 私有方法 =====

    private BizLambdaQueryWrapper<ConsignmentRecruitListPO> buildQueryWrapper(RecruitListQueryBO bo) {
        return new BizLambdaQueryWrapper<ConsignmentRecruitListPO>()
                .eqIfPresent(ConsignmentRecruitListPO::getRecruitNo, bo.getRecruitNo())
                .eqIfPresent(ConsignmentRecruitListPO::getFactoryId, bo.getFactoryId())
                .inIfPresent(ConsignmentRecruitListPO::getFactoryId, bo.getFactoryIdList())
                .eqIfPresent(ConsignmentRecruitListPO::getCategoryId, bo.getCategoryId())
                .inIfPresent(ConsignmentRecruitListPO::getCategoryId, bo.getCategoryIdList())
                .eqIfPresent(ConsignmentRecruitListPO::getSkuCount, bo.getSkuCount())
                .eqIfPresent(ConsignmentRecruitListPO::getListStatus, bo.getListStatus())
                .inIfPresent(ConsignmentRecruitListPO::getListStatus, bo.getListStatusList())
                .eqIfPresent(ConsignmentRecruitListPO::getListType, bo.getListType())
                .eqIfPresent(ConsignmentRecruitListPO::getFactoryName, bo.getFactoryName())
                .eqIfPresent(ConsignmentRecruitListPO::getCategoryFullPathId, bo.getCategoryFullPathId())
                .eqIfPresent(ConsignmentRecruitListPO::getFileUrl, bo.getFileUrl())
                .eqIfPresent(ConsignmentRecruitListPO::getEstimatedCost, bo.getEstimatedCost())
                .eqIfPresent(ConsignmentRecruitListPO::getEstimatedMonthSaleQty, bo.getEstimatedMonthSaleQty())
                .eqIfPresent(ConsignmentRecruitListPO::getEstimatedMonthSaleAmount, bo.getEstimatedMonthSaleAmount())
                .eqIfPresent(ConsignmentRecruitListPO::getAvgMoq, bo.getAvgMoq())
                .eqIfPresent(ConsignmentRecruitListPO::getAwardSupplierId, bo.getAwardSupplierId())
                .eqIfPresent(ConsignmentRecruitListPO::getAwardGroupId, bo.getAwardGroupId())
                .eqIfPresent(ConsignmentRecruitListPO::getAwardApplyId, bo.getAwardApplyId())
                .eqIfPresent(ConsignmentRecruitListPO::getAwardCeBillNo, bo.getAwardCeBillNo())
                .eqIfPresent(ConsignmentRecruitListPO::getPublishBy, bo.getPublishBy())
                .eqIfPresent(ConsignmentRecruitListPO::getAuditBy, bo.getAuditBy())
                .eqIfPresent(ConsignmentRecruitListPO::getAwardBy, bo.getAwardBy())
                .eqIfPresent(ConsignmentRecruitListPO::getCancelUserName, bo.getCancelUserName())
                .eqIfPresent(ConsignmentRecruitListPO::getCancelType, bo.getCancelType())
                .eqIfPresent(ConsignmentRecruitListPO::getCancelReason, bo.getCancelReason())
                .eqIfPresent(ConsignmentRecruitListPO::getRemark, bo.getRemark())
                .gtIfPresent(ConsignmentRecruitListPO::getGroupTime, bo.getGroupTimeGt())
                .geIfPresent(ConsignmentRecruitListPO::getGroupTime, bo.getGroupTimeGe())
                .ltIfPresent(ConsignmentRecruitListPO::getGroupTime, bo.getGroupTimeLt())
                .leIfPresent(ConsignmentRecruitListPO::getGroupTime, bo.getGroupTimeLe())
                .gtIfPresent(ConsignmentRecruitListPO::getPublishBeginTime, bo.getPublishBeginTimeGt())
                .geIfPresent(ConsignmentRecruitListPO::getPublishBeginTime, bo.getPublishBeginTimeGe())
                .ltIfPresent(ConsignmentRecruitListPO::getPublishBeginTime, bo.getPublishBeginTimeLt())
                .leIfPresent(ConsignmentRecruitListPO::getPublishBeginTime, bo.getPublishBeginTimeLe())
                .gtIfPresent(ConsignmentRecruitListPO::getApplyEndTime, bo.getApplyEndTimeGt())
                .geIfPresent(ConsignmentRecruitListPO::getApplyEndTime, bo.getApplyEndTimeGe())
                .ltIfPresent(ConsignmentRecruitListPO::getApplyEndTime, bo.getApplyEndTimeLt())
                .leIfPresent(ConsignmentRecruitListPO::getApplyEndTime, bo.getApplyEndTimeLe())
                .gtIfPresent(ConsignmentRecruitListPO::getPublishEndTime, bo.getPublishEndTimeGt())
                .geIfPresent(ConsignmentRecruitListPO::getPublishEndTime, bo.getPublishEndTimeGe())
                .ltIfPresent(ConsignmentRecruitListPO::getPublishEndTime, bo.getPublishEndTimeLt())
                .leIfPresent(ConsignmentRecruitListPO::getPublishEndTime, bo.getPublishEndTimeLe())
                .gtIfPresent(ConsignmentRecruitListPO::getCreateTime, bo.getCreateTimeGt())
                .geIfPresent(ConsignmentRecruitListPO::getCreateTime, bo.getCreateTimeGe())
                .ltIfPresent(ConsignmentRecruitListPO::getCreateTime, bo.getCreateTimeLt())
                .leIfPresent(ConsignmentRecruitListPO::getCreateTime, bo.getCreateTimeLe())
                .gtIfPresent(ConsignmentRecruitListPO::getUpdateTime, bo.getUpdateTimeGt())
                .geIfPresent(ConsignmentRecruitListPO::getUpdateTime, bo.getUpdateTimeGe())
                .ltIfPresent(ConsignmentRecruitListPO::getUpdateTime, bo.getUpdateTimeLt())
                .leIfPresent(ConsignmentRecruitListPO::getUpdateTime, bo.getUpdateTimeLe())
                .eqIfPresent(ConsignmentRecruitListPO::getApplyBeginTime, bo.getApplyBeginTime())
                .gtIfPresent(ConsignmentRecruitListPO::getApplyBeginTime, bo.getApplyBeginTimeGt())
                .geIfPresent(ConsignmentRecruitListPO::getApplyBeginTime, bo.getApplyBeginTimeGe())
                .ltIfPresent(ConsignmentRecruitListPO::getApplyBeginTime, bo.getApplyBeginTimeLt())
                .leIfPresent(ConsignmentRecruitListPO::getApplyBeginTime, bo.getApplyBeginTimeLe())
                .eqIfPresent(ConsignmentRecruitListPO::getAwardTime, bo.getAwardTime())
                .gtIfPresent(ConsignmentRecruitListPO::getAwardTime, bo.getAwardTimeGt())
                .geIfPresent(ConsignmentRecruitListPO::getAwardTime, bo.getAwardTimeGe())
                .ltIfPresent(ConsignmentRecruitListPO::getAwardTime, bo.getAwardTimeLt())
                .leIfPresent(ConsignmentRecruitListPO::getAwardTime, bo.getAwardTimeLe())
                .eqIfPresent(ConsignmentRecruitListPO::getCancelTime, bo.getCancelTime())
                .gtIfPresent(ConsignmentRecruitListPO::getCancelTime, bo.getCancelTimeGt())
                .geIfPresent(ConsignmentRecruitListPO::getCancelTime, bo.getCancelTimeGe())
                .ltIfPresent(ConsignmentRecruitListPO::getCancelTime, bo.getCancelTimeLt())
                .leIfPresent(ConsignmentRecruitListPO::getCancelTime, bo.getCancelTimeLe());
    }
}
```

### 4.3 ConsignmentRecruitListSkuRepository

#### 4.3.1 接口

```java
package com.ux168.pa.service.scms.biz.dao.mysql.consignment.recruit.repository;

import com.ux168.pa.service.scms.biz.dao.mysql.consignment.recruit.po.ConsignmentRecruitListSkuPO;
import com.ux168.pa.service.scms.biz.service.consignment.recruit.bo.RecruitSkuQueryBO;
import com.ux168.platform.infrastructure.common.api.domain.ListResult;
import com.ux168.platform.infrastructure.mysql.repository.BaseRepository;

import java.util.List;

public interface ConsignmentRecruitListSkuRepository extends BaseRepository<ConsignmentRecruitListSkuPO, Long> {

    /** 根据招募清单ID查询SKU列表 */
    List<ConsignmentRecruitListSkuPO> findByRecruitId(Long recruitId);

    /** 查询招募池中的SKU列表（recruit_id = 0） */
    List<ConsignmentRecruitListSkuPO> findPoolSkuList(Long factoryId, Long categoryId);

    /** 批量更新SKU所属清单 */
    int batchUpdateRecruitId(List<Long> skuIds, Long recruitId, String recruitNo);

    /** 获得SKU记录数 */
    Long count(RecruitSkuQueryBO queryBO);

    /** 获得SKU列表 */
    List<ConsignmentRecruitListSkuPO> findList(RecruitSkuQueryBO queryBO);

    /** 获得SKU分页 */
    ListResult<ConsignmentRecruitListSkuPO> pageList(RecruitSkuQueryBO pageBO, Integer pageNo, Integer pageSize);

    /** 批量插入SKU */
    void insertBatch(List<ConsignmentRecruitListSkuPO> list);

    /** 批量更新SKU */
    Boolean updateBatch(List<ConsignmentRecruitListSkuPO> list);
}
```

#### 4.3.2 实现

```java
@Repository
public class ConsignmentRecruitListSkuRepositoryImpl
        extends BaseRepositoryImpl<ConsignmentRecruitListSkuPO, Long>
        implements ConsignmentRecruitListSkuRepository {

    @Resource
    private ConsignmentRecruitListSkuMapper consignmentRecruitListSkuMapper;

    // ===== 自定义查询方法 =====

    @Override
    public List<ConsignmentRecruitListSkuPO> findByRecruitId(Long recruitId) {
        return findList(new BizLambdaQueryWrapper<ConsignmentRecruitListSkuPO>()
                .eq(ConsignmentRecruitListSkuPO::getRecruitId, recruitId));
    }

    @Override
    public List<ConsignmentRecruitListSkuPO> findPoolSkuList(Long factoryId, Long categoryId) {
        return findList(new BizLambdaQueryWrapper<ConsignmentRecruitListSkuPO>()
                .eq(ConsignmentRecruitListSkuPO::getRecruitId, 0L)
                .eq(ConsignmentRecruitListSkuPO::getFactoryId, factoryId)
                .eq(ConsignmentRecruitListSkuPO::getCategoryId, categoryId)
                .eq(ConsignmentRecruitListSkuPO::getSkuStatus, 10)); // PENDING_GROUP
    }

    @Override
    public int batchUpdateRecruitId(List<Long> skuIds, Long recruitId, String recruitNo) {
        ConsignmentRecruitListSkuPO updateEntity = new ConsignmentRecruitListSkuPO();
        updateEntity.setRecruitId(recruitId);
        updateEntity.setRecruitNo(recruitNo);
        updateEntity.setSkuStatus(20); // GROUPED
        return consignmentRecruitListSkuMapper.update(updateEntity,
                new BizLambdaQueryWrapper<ConsignmentRecruitListSkuPO>()
                        .in(ConsignmentRecruitListSkuPO::getId, skuIds));
    }

    // ===== BO条件查询标准方法 =====

    @Override
    public Long count(RecruitSkuQueryBO queryBO) {
        return count(buildQueryWrapper(queryBO));
    }

    @Override
    public List<ConsignmentRecruitListSkuPO> findList(RecruitSkuQueryBO queryBO) {
        return findList(buildQueryWrapper(queryBO)
                .orderByDesc(ConsignmentRecruitListSkuPO::getUpdateTime));
    }

    @Override
    public ListResult<ConsignmentRecruitListSkuPO> pageList(RecruitSkuQueryBO queryBO, Integer pageNo, Integer pageSize) {
        return pageList(buildQueryWrapper(queryBO)
                .orderByDesc(ConsignmentRecruitListSkuPO::getUpdateTime), pageNo, pageSize);
    }

    @Override
    public void insertBatch(List<ConsignmentRecruitListSkuPO> list) {
        if (CollectionUtils.isEmpty(list)) {
            return;
        }
        saveOrUpdateBatch(list);
    }

    @Override
    public Boolean updateBatch(List<ConsignmentRecruitListSkuPO> list) {
        if (CollectionUtils.isEmpty(list)) {
            return true;
        }
        return super.updateBatch(list);
    }

    // ===== 私有方法 =====

    private BizLambdaQueryWrapper<ConsignmentRecruitListSkuPO> buildQueryWrapper(RecruitSkuQueryBO bo) {
        return new BizLambdaQueryWrapper<ConsignmentRecruitListSkuPO>()
                .eqIfPresent(ConsignmentRecruitListSkuPO::getRecruitId, bo.getRecruitId())
                .inIfPresent(ConsignmentRecruitListSkuPO::getRecruitId, bo.getRecruitIdList())
                .eqIfPresent(ConsignmentRecruitListSkuPO::getRecruitNo, bo.getRecruitNo())
                .eqIfPresent(ConsignmentRecruitListSkuPO::getSkuId, bo.getSkuId())
                .inIfPresent(ConsignmentRecruitListSkuPO::getSkuId, bo.getSkuIdList())
                .eqIfPresent(ConsignmentRecruitListSkuPO::getCategoryId, bo.getCategoryId())
                .eqIfPresent(ConsignmentRecruitListSkuPO::getFactoryId, bo.getFactoryId())
                .eqIfPresent(ConsignmentRecruitListSkuPO::getSkuStatus, bo.getSkuStatus())
                .inIfPresent(ConsignmentRecruitListSkuPO::getSkuStatus, bo.getSkuStatusList())
                .eqIfPresent(ConsignmentRecruitListSkuPO::getSourceType, bo.getSourceType())
                .eqIfPresent(ConsignmentRecruitListSkuPO::getSkuName, bo.getSkuName())
                .inIfPresent(ConsignmentRecruitListSkuPO::getSkuName, bo.getSkuNameList())
                .eqIfPresent(ConsignmentRecruitListSkuPO::getFactoryName, bo.getFactoryName())
                .inIfPresent(ConsignmentRecruitListSkuPO::getFactoryName, bo.getFactoryNameList())
                .eqIfPresent(ConsignmentRecruitListSkuPO::getPurchaseUrl, bo.getPurchaseUrl())
                .eqIfPresent(ConsignmentRecruitListSkuPO::getProductModel, bo.getProductModel())
                .eqIfPresent(ConsignmentRecruitListSkuPO::getVehicleModel, bo.getVehicleModel())
                .eqIfPresent(ConsignmentRecruitListSkuPO::getUnit, bo.getUnit())
                .eqIfPresent(ConsignmentRecruitListSkuPO::getPackageInfo, bo.getPackageInfo())
                .eqIfPresent(ConsignmentRecruitListSkuPO::getGrossWeightG, bo.getGrossWeightG())
                .eqIfPresent(ConsignmentRecruitListSkuPO::getPackageSizeCm, bo.getPackageSizeCm())
                .eqIfPresent(ConsignmentRecruitListSkuPO::getSourceModel, bo.getSourceModel())
                .eqIfPresent(ConsignmentRecruitListSkuPO::getDeliveryDays, bo.getDeliveryDays())
                .eqIfPresent(ConsignmentRecruitListSkuPO::getMoq, bo.getMoq())
                .eqIfPresent(ConsignmentRecruitListSkuPO::getCostPrice, bo.getCostPrice())
                .eqIfPresent(ConsignmentRecruitListSkuPO::getSaleQty90d, bo.getSaleQty90d())
                .eqIfPresent(ConsignmentRecruitListSkuPO::getSaleQty30d, bo.getSaleQty30d())
                .eqIfPresent(ConsignmentRecruitListSkuPO::getReplenishRemind21d, bo.getReplenishRemind21d())
                .eqIfPresent(ConsignmentRecruitListSkuPO::getFailReason, bo.getFailReason())
                .eqIfPresent(ConsignmentRecruitListSkuPO::getImportBatchNo, bo.getImportBatchNo())
                .eqIfPresent(ConsignmentRecruitListSkuPO::getImportUser, bo.getImportUser())
                .gtIfPresent(ConsignmentRecruitListSkuPO::getImportTime, bo.getImportTimeGt())
                .geIfPresent(ConsignmentRecruitListSkuPO::getImportTime, bo.getImportTimeGe())
                .ltIfPresent(ConsignmentRecruitListSkuPO::getImportTime, bo.getImportTimeLt())
                .leIfPresent(ConsignmentRecruitListSkuPO::getImportTime, bo.getImportTimeLe())
                .gtIfPresent(ConsignmentRecruitListSkuPO::getCreateTime, bo.getCreateTimeGt())
                .geIfPresent(ConsignmentRecruitListSkuPO::getCreateTime, bo.getCreateTimeGe())
                .ltIfPresent(ConsignmentRecruitListSkuPO::getCreateTime, bo.getCreateTimeLt())
                .leIfPresent(ConsignmentRecruitListSkuPO::getCreateTime, bo.getCreateTimeLe())
                .gtIfPresent(ConsignmentRecruitListSkuPO::getUpdateTime, bo.getUpdateTimeGt())
                .geIfPresent(ConsignmentRecruitListSkuPO::getUpdateTime, bo.getUpdateTimeGe())
                .ltIfPresent(ConsignmentRecruitListSkuPO::getUpdateTime, bo.getUpdateTimeLt())
                .leIfPresent(ConsignmentRecruitListSkuPO::getUpdateTime, bo.getUpdateTimeLe());
    }
}
```

### 4.4 ConsignmentRecruitPublishRepository

#### 4.4.1 接口

```java
package com.ux168.pa.service.scms.biz.dao.mysql.consignment.recruit.repository;

import com.ux168.pa.service.scms.biz.dao.mysql.consignment.recruit.po.ConsignmentRecruitPublishPO;
import com.ux168.pa.service.scms.biz.service.consignment.recruit.bo.RecruitPublishQueryBO;
import com.ux168.platform.infrastructure.common.api.domain.ListResult;
import com.ux168.platform.infrastructure.mysql.repository.BaseRepository;

import java.util.List;

public interface ConsignmentRecruitPublishRepository extends BaseRepository<ConsignmentRecruitPublishPO, Long> {

    /** 根据招募清单ID查询发布记录列表 */
    List<ConsignmentRecruitPublishPO> findByRecruitId(Long recruitId);

    /** 查询某清单的最新发布记录 */
    ConsignmentRecruitPublishPO findLatestByRecruitId(Long recruitId);

    /** 获得发布记录数 */
    Long count(RecruitPublishQueryBO queryBO);

    /** 获得发布记录列表 */
    List<ConsignmentRecruitPublishPO> findList(RecruitPublishQueryBO queryBO);

    /** 获得发布记录分页 */
    ListResult<ConsignmentRecruitPublishPO> pageList(RecruitPublishQueryBO pageBO, Integer pageNo, Integer pageSize);

    /** 批量插入发布记录 */
    void insertBatch(List<ConsignmentRecruitPublishPO> list);

    /** 批量更新发布记录 */
    Boolean updateBatch(List<ConsignmentRecruitPublishPO> list);
}
```

#### 4.4.2 实现

```java
@Repository
public class ConsignmentRecruitPublishRepositoryImpl
        extends BaseRepositoryImpl<ConsignmentRecruitPublishPO, Long>
        implements ConsignmentRecruitPublishRepository {

    // ===== 自定义查询方法 =====

    @Override
    public List<ConsignmentRecruitPublishPO> findByRecruitId(Long recruitId) {
        return findList(new BizLambdaQueryWrapper<ConsignmentRecruitPublishPO>()
                .eq(ConsignmentRecruitPublishPO::getRecruitId, recruitId)
                .orderByDesc(ConsignmentRecruitPublishPO::getPublishRound));
    }

    @Override
    public ConsignmentRecruitPublishPO findLatestByRecruitId(Long recruitId) {
        List<ConsignmentRecruitPublishPO> list = findList(new BizLambdaQueryWrapper<ConsignmentRecruitPublishPO>()
                .eq(ConsignmentRecruitPublishPO::getRecruitId, recruitId)
                .orderByDesc(ConsignmentRecruitPublishPO::getPublishRound)
                .last("LIMIT 1"));
        return CollectionUtils.isEmpty(list) ? null : list.get(0);
    }

    // ===== BO条件查询标准方法 =====

    @Override
    public Long count(RecruitPublishQueryBO queryBO) {
        return count(buildQueryWrapper(queryBO));
    }

    @Override
    public List<ConsignmentRecruitPublishPO> findList(RecruitPublishQueryBO queryBO) {
        return findList(buildQueryWrapper(queryBO)
                .orderByDesc(ConsignmentRecruitPublishPO::getUpdateTime));
    }

    @Override
    public ListResult<ConsignmentRecruitPublishPO> pageList(RecruitPublishQueryBO queryBO, Integer pageNo, Integer pageSize) {
        return pageList(buildQueryWrapper(queryBO)
                .orderByDesc(ConsignmentRecruitPublishPO::getUpdateTime), pageNo, pageSize);
    }

    @Override
    public void insertBatch(List<ConsignmentRecruitPublishPO> list) {
        if (CollectionUtils.isEmpty(list)) {
            return;
        }
        saveOrUpdateBatch(list);
    }

    @Override
    public Boolean updateBatch(List<ConsignmentRecruitPublishPO> list) {
        if (CollectionUtils.isEmpty(list)) {
            return true;
        }
        return super.updateBatch(list);
    }

    // ===== 私有方法 =====

    private BizLambdaQueryWrapper<ConsignmentRecruitPublishPO> buildQueryWrapper(RecruitPublishQueryBO bo) {
        return new BizLambdaQueryWrapper<ConsignmentRecruitPublishPO>()
                .eqIfPresent(ConsignmentRecruitPublishPO::getRecruitId, bo.getRecruitId())
                .inIfPresent(ConsignmentRecruitPublishPO::getRecruitId, bo.getRecruitIdList())
                .eqIfPresent(ConsignmentRecruitPublishPO::getRecruitNo, bo.getRecruitNo())
                .eqIfPresent(ConsignmentRecruitPublishPO::getPublishRound, bo.getPublishRound())
                .eqIfPresent(ConsignmentRecruitPublishPO::getPublishStatus, bo.getPublishStatus())
                .inIfPresent(ConsignmentRecruitPublishPO::getPublishStatus, bo.getPublishStatusList())
                .eqIfPresent(ConsignmentRecruitPublishPO::getPublishJobId, bo.getPublishJobId())
                .gtIfPresent(ConsignmentRecruitPublishPO::getPublishBeginTime, bo.getPublishBeginTimeGt())
                .geIfPresent(ConsignmentRecruitPublishPO::getPublishBeginTime, bo.getPublishBeginTimeGe())
                .ltIfPresent(ConsignmentRecruitPublishPO::getPublishBeginTime, bo.getPublishBeginTimeLt())
                .leIfPresent(ConsignmentRecruitPublishPO::getPublishBeginTime, bo.getPublishBeginTimeLe())
                .gtIfPresent(ConsignmentRecruitPublishPO::getApplyBeginTime, bo.getApplyBeginTimeGt())
                .geIfPresent(ConsignmentRecruitPublishPO::getApplyBeginTime, bo.getApplyBeginTimeGe())
                .ltIfPresent(ConsignmentRecruitPublishPO::getApplyBeginTime, bo.getApplyBeginTimeLt())
                .leIfPresent(ConsignmentRecruitPublishPO::getApplyBeginTime, bo.getApplyBeginTimeLe())
                .gtIfPresent(ConsignmentRecruitPublishPO::getApplyEndTime, bo.getApplyEndTimeGt())
                .geIfPresent(ConsignmentRecruitPublishPO::getApplyEndTime, bo.getApplyEndTimeGe())
                .ltIfPresent(ConsignmentRecruitPublishPO::getApplyEndTime, bo.getApplyEndTimeLt())
                .leIfPresent(ConsignmentRecruitPublishPO::getApplyEndTime, bo.getApplyEndTimeLe())
                .gtIfPresent(ConsignmentRecruitPublishPO::getPublishEndTime, bo.getPublishEndTimeGt())
                .geIfPresent(ConsignmentRecruitPublishPO::getPublishEndTime, bo.getPublishEndTimeGe())
                .ltIfPresent(ConsignmentRecruitPublishPO::getPublishEndTime, bo.getPublishEndTimeLt())
                .leIfPresent(ConsignmentRecruitPublishPO::getPublishEndTime, bo.getPublishEndTimeLe())
                .gtIfPresent(ConsignmentRecruitPublishPO::getCreateTime, bo.getCreateTimeGt())
                .geIfPresent(ConsignmentRecruitPublishPO::getCreateTime, bo.getCreateTimeGe())
                .ltIfPresent(ConsignmentRecruitPublishPO::getCreateTime, bo.getCreateTimeLt())
                .leIfPresent(ConsignmentRecruitPublishPO::getCreateTime, bo.getCreateTimeLe())
                .gtIfPresent(ConsignmentRecruitPublishPO::getUpdateTime, bo.getUpdateTimeGt())
                .geIfPresent(ConsignmentRecruitPublishPO::getUpdateTime, bo.getUpdateTimeGe())
                .ltIfPresent(ConsignmentRecruitPublishPO::getUpdateTime, bo.getUpdateTimeLt())
                .leIfPresent(ConsignmentRecruitPublishPO::getUpdateTime, bo.getUpdateTimeLe());
    }
}
```

### 4.5 ConsignmentRecruitApplyRepository

#### 4.5.1 接口

```java
package com.ux168.pa.service.scms.biz.dao.mysql.consignment.recruit.repository;

import com.ux168.pa.service.scms.biz.dao.mysql.consignment.recruit.po.ConsignmentRecruitApplyPO;
import com.ux168.pa.service.scms.biz.service.consignment.recruit.bo.RecruitApplyQueryBO;
import com.ux168.platform.infrastructure.common.api.domain.ListResult;
import com.ux168.platform.infrastructure.mysql.repository.BaseRepository;

import java.util.List;

public interface ConsignmentRecruitApplyRepository extends BaseRepository<ConsignmentRecruitApplyPO, Long> {

    /** 根据招募清单ID查询申请列表 */
    List<ConsignmentRecruitApplyPO> findByRecruitId(Long recruitId);

    /** 根据寄卖商ID查询申请列表 */
    List<ConsignmentRecruitApplyPO> findBySupplierId(Long supplierId);

    /** 查询清单的活跃申请列表 */
    List<ConsignmentRecruitApplyPO> findActiveByRecruitId(Long recruitId);

    /** 获得申请记录数 */
    Long count(RecruitApplyQueryBO queryBO);

    /** 获得申请记录列表 */
    List<ConsignmentRecruitApplyPO> findList(RecruitApplyQueryBO queryBO);

    /** 获得申请记录分页 */
    ListResult<ConsignmentRecruitApplyPO> pageList(RecruitApplyQueryBO pageBO, Integer pageNo, Integer pageSize);

    /** 批量插入申请记录 */
    void insertBatch(List<ConsignmentRecruitApplyPO> list);

    /** 批量更新申请记录 */
    Boolean updateBatch(List<ConsignmentRecruitApplyPO> list);
}
```

#### 4.5.2 实现

```java
@Repository
public class ConsignmentRecruitApplyRepositoryImpl
        extends BaseRepositoryImpl<ConsignmentRecruitApplyPO, Long>
        implements ConsignmentRecruitApplyRepository {

    // ===== 自定义查询方法 =====

    @Override
    public List<ConsignmentRecruitApplyPO> findByRecruitId(Long recruitId) {
        return findList(new BizLambdaQueryWrapper<ConsignmentRecruitApplyPO>()
                .eq(ConsignmentRecruitApplyPO::getRecruitId, recruitId)
                .orderByDesc(ConsignmentRecruitApplyPO::getCreateTime));
    }

    @Override
    public List<ConsignmentRecruitApplyPO> findBySupplierId(Long supplierId) {
        return findList(new BizLambdaQueryWrapper<ConsignmentRecruitApplyPO>()
                .eq(ConsignmentRecruitApplyPO::getSupplierId, supplierId)
                .orderByDesc(ConsignmentRecruitApplyPO::getCreateTime));
    }

    @Override
    public List<ConsignmentRecruitApplyPO> findActiveByRecruitId(Long recruitId) {
        return findList(new BizLambdaQueryWrapper<ConsignmentRecruitApplyPO>()
                .eq(ConsignmentRecruitApplyPO::getRecruitId, recruitId)
                .in(ConsignmentRecruitApplyPO::getApplyStatus, 10, 20, 30) // JOINED, CE_CREATED, WAIT_AWARD
                .orderByDesc(ConsignmentRecruitApplyPO::getFinalCoverageRate));
    }

    // ===== BO条件查询标准方法 =====

    @Override
    public Long count(RecruitApplyQueryBO queryBO) {
        return count(buildQueryWrapper(queryBO));
    }

    @Override
    public List<ConsignmentRecruitApplyPO> findList(RecruitApplyQueryBO queryBO) {
        return findList(buildQueryWrapper(queryBO)
                .orderByDesc(ConsignmentRecruitApplyPO::getUpdateTime));
    }

    @Override
    public ListResult<ConsignmentRecruitApplyPO> pageList(RecruitApplyQueryBO queryBO, Integer pageNo, Integer pageSize) {
        return pageList(buildQueryWrapper(queryBO)
                .orderByDesc(ConsignmentRecruitApplyPO::getUpdateTime), pageNo, pageSize);
    }

    @Override
    public void insertBatch(List<ConsignmentRecruitApplyPO> list) {
        if (CollectionUtils.isEmpty(list)) {
            return;
        }
        saveOrUpdateBatch(list);
    }

    @Override
    public Boolean updateBatch(List<ConsignmentRecruitApplyPO> list) {
        if (CollectionUtils.isEmpty(list)) {
            return true;
        }
        return super.updateBatch(list);
    }

    // ===== 私有方法 =====

    private BizLambdaQueryWrapper<ConsignmentRecruitApplyPO> buildQueryWrapper(RecruitApplyQueryBO bo) {
        return new BizLambdaQueryWrapper<ConsignmentRecruitApplyPO>()
                .eqIfPresent(ConsignmentRecruitApplyPO::getRecruitId, bo.getRecruitId())
                .inIfPresent(ConsignmentRecruitApplyPO::getRecruitId, bo.getRecruitIdList())
                .eqIfPresent(ConsignmentRecruitApplyPO::getRecruitNo, bo.getRecruitNo())
                .eqIfPresent(ConsignmentRecruitApplyPO::getSupplierId, bo.getSupplierId())
                .inIfPresent(ConsignmentRecruitApplyPO::getSupplierId, bo.getSupplierIdList())
                .eqIfPresent(ConsignmentRecruitApplyPO::getGroupId, bo.getGroupId())
                .eqIfPresent(ConsignmentRecruitApplyPO::getFactoryId, bo.getFactoryId())
                .eqIfPresent(ConsignmentRecruitApplyPO::getApplyStatus, bo.getApplyStatus())
                .inIfPresent(ConsignmentRecruitApplyPO::getApplyStatus, bo.getApplyStatusList())
                .eqIfPresent(ConsignmentRecruitApplyPO::getCeBillNo, bo.getCeBillNo())
                .eqIfPresent(ConsignmentRecruitApplyPO::getRankNo, bo.getRankNo())
                .eqIfPresent(ConsignmentRecruitApplyPO::getAwardResult, bo.getAwardResult())
                .eqIfPresent(ConsignmentRecruitApplyPO::getSupplierName, bo.getSupplierName())
                .inIfPresent(ConsignmentRecruitApplyPO::getSupplierName, bo.getSupplierNameList())
                .eqIfPresent(ConsignmentRecruitApplyPO::getSameSourceFlag, bo.getSameSourceFlag())
                .eqIfPresent(ConsignmentRecruitApplyPO::getRemovedType, bo.getRemovedType())
                .inIfPresent(ConsignmentRecruitApplyPO::getRemovedType, bo.getRemovedTypeList())
                .eqIfPresent(ConsignmentRecruitApplyPO::getInboundSkuCount, bo.getInboundSkuCount())
                .eqIfPresent(ConsignmentRecruitApplyPO::getBaseCoverageRate, bo.getBaseCoverageRate())
                .eqIfPresent(ConsignmentRecruitApplyPO::getSameSourceWeightRate, bo.getSameSourceWeightRate())
                .eqIfPresent(ConsignmentRecruitApplyPO::getFinalCoverageRate, bo.getFinalCoverageRate())
                .eqIfPresent(ConsignmentRecruitApplyPO::getAwardReason, bo.getAwardReason())
                .eqIfPresent(ConsignmentRecruitApplyPO::getCancelType, bo.getCancelType())
                .inIfPresent(ConsignmentRecruitApplyPO::getCancelType, bo.getCancelTypeList())
                .eqIfPresent(ConsignmentRecruitApplyPO::getCancelReason, bo.getCancelReason())
                .gtIfPresent(ConsignmentRecruitApplyPO::getCeCreateTime, bo.getCeCreateTimeGt())
                .geIfPresent(ConsignmentRecruitApplyPO::getCeCreateTime, bo.getCeCreateTimeGe())
                .ltIfPresent(ConsignmentRecruitApplyPO::getCeCreateTime, bo.getCeCreateTimeLt())
                .leIfPresent(ConsignmentRecruitApplyPO::getCeCreateTime, bo.getCeCreateTimeLe())
                .gtIfPresent(ConsignmentRecruitApplyPO::getCeSendTime, bo.getCeSendTimeGt())
                .geIfPresent(ConsignmentRecruitApplyPO::getCeSendTime, bo.getCeSendTimeGe())
                .ltIfPresent(ConsignmentRecruitApplyPO::getCeSendTime, bo.getCeSendTimeLt())
                .leIfPresent(ConsignmentRecruitApplyPO::getCeSendTime, bo.getCeSendTimeLe())
                .gtIfPresent(ConsignmentRecruitApplyPO::getFirstQcPassTime, bo.getFirstQcPassTimeGt())
                .geIfPresent(ConsignmentRecruitApplyPO::getFirstQcPassTime, bo.getFirstQcPassTimeGe())
                .ltIfPresent(ConsignmentRecruitApplyPO::getFirstQcPassTime, bo.getFirstQcPassTimeLt())
                .leIfPresent(ConsignmentRecruitApplyPO::getFirstQcPassTime, bo.getFirstQcPassTimeLe())
                .gtIfPresent(ConsignmentRecruitApplyPO::getCreateTime, bo.getCreateTimeGt())
                .geIfPresent(ConsignmentRecruitApplyPO::getCreateTime, bo.getCreateTimeGe())
                .ltIfPresent(ConsignmentRecruitApplyPO::getCreateTime, bo.getCreateTimeLt())
                .leIfPresent(ConsignmentRecruitApplyPO::getCreateTime, bo.getCreateTimeLe())
                .gtIfPresent(ConsignmentRecruitApplyPO::getUpdateTime, bo.getUpdateTimeGt())
                .geIfPresent(ConsignmentRecruitApplyPO::getUpdateTime, bo.getUpdateTimeGe())
                .ltIfPresent(ConsignmentRecruitApplyPO::getUpdateTime, bo.getUpdateTimeLt())
                .leIfPresent(ConsignmentRecruitApplyPO::getUpdateTime, bo.getUpdateTimeLe());
    }
}
```

### 4.6 ConsignmentActionLogRepository

#### 4.6.1 接口

```java
package com.ux168.pa.service.scms.biz.dao.mysql.consignment.recruit.repository;

import com.ux168.pa.service.scms.biz.dao.mysql.consignment.recruit.po.ConsignmentActionLogPO;
import com.ux168.pa.service.scms.biz.service.consignment.recruit.bo.ActionLogQueryBO;
import com.ux168.platform.infrastructure.common.api.domain.ListResult;
import com.ux168.platform.infrastructure.mysql.repository.BaseRepository;

import java.util.List;

public interface ConsignmentActionLogRepository extends BaseRepository<ConsignmentActionLogPO, Long> {

    /** 根据招募清单ID查询日志列表 */
    List<ConsignmentActionLogPO> findByRecruitId(Long recruitId);

    /** 根据申请ID查询日志列表 */
    List<ConsignmentActionLogPO> findByApplyId(Long applyId);

    /** 获得日志记录数 */
    Long count(ActionLogQueryBO queryBO);

    /** 获得日志列表 */
    List<ConsignmentActionLogPO> findList(ActionLogQueryBO queryBO);

    /** 获得日志分页 */
    ListResult<ConsignmentActionLogPO> pageList(ActionLogQueryBO pageBO, Integer pageNo, Integer pageSize);

    /** 批量插入日志 */
    void insertBatch(List<ConsignmentActionLogPO> list);

    /** 批量更新日志 */
    Boolean updateBatch(List<ConsignmentActionLogPO> list);
}
```

#### 4.6.2 实现

```java
@Repository
public class ConsignmentActionLogRepositoryImpl
        extends BaseRepositoryImpl<ConsignmentActionLogPO, Long>
        implements ConsignmentActionLogRepository {

    // ===== 自定义查询方法 =====

    @Override
    public List<ConsignmentActionLogPO> findByRecruitId(Long recruitId) {
        return findList(new BizLambdaQueryWrapper<ConsignmentActionLogPO>()
                .eq(ConsignmentActionLogPO::getRecruitId, recruitId)
                .orderByDesc(ConsignmentActionLogPO::getCreateTime));
    }

    @Override
    public List<ConsignmentActionLogPO> findByApplyId(Long applyId) {
        return findList(new BizLambdaQueryWrapper<ConsignmentActionLogPO>()
                .eq(ConsignmentActionLogPO::getApplyId, applyId)
                .orderByDesc(ConsignmentActionLogPO::getCreateTime));
    }

    // ===== BO条件查询标准方法 =====

    @Override
    public Long count(ActionLogQueryBO queryBO) {
        return count(buildQueryWrapper(queryBO));
    }

    @Override
    public List<ConsignmentActionLogPO> findList(ActionLogQueryBO queryBO) {
        return findList(buildQueryWrapper(queryBO)
                .orderByDesc(ConsignmentActionLogPO::getCreateTime));
    }

    @Override
    public ListResult<ConsignmentActionLogPO> pageList(ActionLogQueryBO queryBO, Integer pageNo, Integer pageSize) {
        return pageList(buildQueryWrapper(queryBO)
                .orderByDesc(ConsignmentActionLogPO::getCreateTime), pageNo, pageSize);
    }

    @Override
    public void insertBatch(List<ConsignmentActionLogPO> list) {
        if (CollectionUtils.isEmpty(list)) {
            return;
        }
        saveOrUpdateBatch(list);
    }

    @Override
    public Boolean updateBatch(List<ConsignmentActionLogPO> list) {
        if (CollectionUtils.isEmpty(list)) {
            return true;
        }
        return super.updateBatch(list);
    }

    // ===== 私有方法 =====

    private BizLambdaQueryWrapper<ConsignmentActionLogPO> buildQueryWrapper(ActionLogQueryBO bo) {
        return new BizLambdaQueryWrapper<ConsignmentActionLogPO>()
                .eqIfPresent(ConsignmentActionLogPO::getRecruitId, bo.getRecruitId())
                .inIfPresent(ConsignmentActionLogPO::getRecruitId, bo.getRecruitIdList())
                .eqIfPresent(ConsignmentActionLogPO::getRecruitNo, bo.getRecruitNo())
                .eqIfPresent(ConsignmentActionLogPO::getApplyId, bo.getApplyId())
                .inIfPresent(ConsignmentActionLogPO::getApplyId, bo.getApplyIdList())
                .eqIfPresent(ConsignmentActionLogPO::getSupplierId, bo.getSupplierId())
                .eqIfPresent(ConsignmentActionLogPO::getAction, bo.getAction())
                .inIfPresent(ConsignmentActionLogPO::getAction, bo.getActionList())
                .eqIfPresent(ConsignmentActionLogPO::getBeforeStatus, bo.getBeforeStatus())
                .eqIfPresent(ConsignmentActionLogPO::getAfterStatus, bo.getAfterStatus())
                .eqIfPresent(ConsignmentActionLogPO::getOperatorType, bo.getOperatorType())
                .inIfPresent(ConsignmentActionLogPO::getOperatorType, bo.getOperatorTypeList())
                .eqIfPresent(ConsignmentActionLogPO::getOperatorId, bo.getOperatorId())
                .eqIfPresent(ConsignmentActionLogPO::getOperatorName, bo.getOperatorName())
                .eqIfPresent(ConsignmentActionLogPO::getContent, bo.getContent())
                .inIfPresent(ConsignmentActionLogPO::getContent, bo.getContentList())
                .eqIfPresent(ConsignmentActionLogPO::getRequestId, bo.getRequestId())
                .gtIfPresent(ConsignmentActionLogPO::getCreateTime, bo.getCreateTimeGt())
                .geIfPresent(ConsignmentActionLogPO::getCreateTime, bo.getCreateTimeGe())
                .ltIfPresent(ConsignmentActionLogPO::getCreateTime, bo.getCreateTimeLt())
                .leIfPresent(ConsignmentActionLogPO::getCreateTime, bo.getCreateTimeLe())
                .gtIfPresent(ConsignmentActionLogPO::getUpdateTime, bo.getUpdateTimeGt())
                .geIfPresent(ConsignmentActionLogPO::getUpdateTime, bo.getUpdateTimeGe())
                .ltIfPresent(ConsignmentActionLogPO::getUpdateTime, bo.getUpdateTimeLt())
                .leIfPresent(ConsignmentActionLogPO::getUpdateTime, bo.getUpdateTimeLe());
    }
}
```

---

## 五、查询条件 BO

### 5.1 通用设计原则

- 所有字段使用**包装类型**（Long/Integer 而非 long/int），配合 `eqIfPresent` 自动过滤 null 条件
- 列表查询条件用 `List<X>` + `inIfPresent`
- 时间范围用 `Gt`/`Ge`/`Lt`/`Le` 后缀字段，每个时间字段生成4个查询变体，支持灵活范围查询
- 所有 BO 继承 `BaseBO`，使用 `@ApiModelProperty(name=, value=)` 标注
- 每个 PO 字段在 BO 中都有对应的 `eqIfPresent` 或 `inIfPresent` 查询条件

### 5.2 清单查询条件 BO

```java
package com.ux168.pa.service.scms.biz.service.consignment.recruit.bo;

import com.ux168.platform.infrastructure.common.api.bo.BaseBO;
import io.swagger.annotations.ApiModelProperty;
import lombok.Data;
import lombok.EqualsAndHashCode;
import lombok.ToString;

import java.math.BigDecimal;
import java.util.Date;
import java.util.List;

@Data
@EqualsAndHashCode(callSuper = true)
@ToString(callSuper = true)
public class RecruitListQueryBO extends BaseBO {

    @ApiModelProperty(name = "ids", value = "清单ID列表")
    private List<Long> ids;

    @ApiModelProperty(name = "recruitNo", value = "招募清单编号")
    private String recruitNo;

    @ApiModelProperty(name = "factoryId", value = "货源工厂ID")
    private Long factoryId;

    @ApiModelProperty(name = "factoryIdList", value = "货源工厂ID列表")
    private List<Long> factoryIdList;

    @ApiModelProperty(name = "categoryId", value = "末级分类ID（产品线ID）")
    private Long categoryId;

    @ApiModelProperty(name = "categoryIdList", value = "末级分类ID列表")
    private List<Long> categoryIdList;

    @ApiModelProperty(name = "skuCount", value = "清单SKU数量")
    private Integer skuCount;

    @ApiModelProperty(name = "listStatus", value = "清单状态")
    private Integer listStatus;

    @ApiModelProperty(name = "listStatusList", value = "清单状态列表")
    private List<Integer> listStatusList;

    @ApiModelProperty(name = "listType", value = "清单类型")
    private Integer listType;

    @ApiModelProperty(name = "factoryName", value = "货源工厂名称")
    private String factoryName;

    @ApiModelProperty(name = "categoryFullPathId", value = "分类完整路径ID")
    private String categoryFullPathId;

    @ApiModelProperty(name = "fileUrl", value = "文件URL")
    private String fileUrl;

    @ApiModelProperty(name = "estimatedCost", value = "预估成本")
    private BigDecimal estimatedCost;

    @ApiModelProperty(name = "estimatedMonthSaleQty", value = "预估月销量")
    private Integer estimatedMonthSaleQty;

    @ApiModelProperty(name = "estimatedMonthSaleAmount", value = "预估月销售额")
    private BigDecimal estimatedMonthSaleAmount;

    @ApiModelProperty(name = "avgMoq", value = "平均MOQ")
    private BigDecimal avgMoq;

    @ApiModelProperty(name = "awardSupplierId", value = "中标供应商ID")
    private Long awardSupplierId;

    @ApiModelProperty(name = "awardGroupId", value = "中标集团ID")
    private Long awardGroupId;

    @ApiModelProperty(name = "awardApplyId", value = "中标申请ID")
    private Long awardApplyId;

    @ApiModelProperty(name = "awardCeBillNo", value = "中标CE单号")
    private String awardCeBillNo;

    @ApiModelProperty(name = "publishBy", value = "发布人")
    private String publishBy;

    @ApiModelProperty(name = "auditBy", value = "审核人")
    private String auditBy;

    @ApiModelProperty(name = "awardBy", value = "定标人")
    private String awardBy;

    @ApiModelProperty(name = "cancelUserName", value = "取消人")
    private String cancelUserName;

    @ApiModelProperty(name = "cancelType", value = "取消类型")
    private String cancelType;

    @ApiModelProperty(name = "cancelReason", value = "取消原因")
    private String cancelReason;

    @ApiModelProperty(name = "remark", value = "备注")
    private String remark;

    @ApiModelProperty(name = "groupTimeGt", value = "大于组单完成时间")
    private Date groupTimeGt;

    @ApiModelProperty(name = "groupTimeGe", value = "大于等于组单完成时间")
    private Date groupTimeGe;

    @ApiModelProperty(name = "groupTimeLt", value = "小于组单完成时间")
    private Date groupTimeLt;

    @ApiModelProperty(name = "groupTimeLe", value = "小于等于组单完成时间")
    private Date groupTimeLe;

    @ApiModelProperty(name = "publishBeginTimeGt", value = "大于发布开始时间")
    private Date publishBeginTimeGt;

    @ApiModelProperty(name = "publishBeginTimeGe", value = "大于等于发布开始时间")
    private Date publishBeginTimeGe;

    @ApiModelProperty(name = "publishBeginTimeLt", value = "小于发布开始时间")
    private Date publishBeginTimeLt;

    @ApiModelProperty(name = "publishBeginTimeLe", value = "小于等于发布开始时间")
    private Date publishBeginTimeLe;

    @ApiModelProperty(name = "applyEndTimeGt", value = "大于申请结束时间")
    private Date applyEndTimeGt;

    @ApiModelProperty(name = "applyEndTimeGe", value = "大于等于申请结束时间")
    private Date applyEndTimeGe;

    @ApiModelProperty(name = "applyEndTimeLt", value = "小于申请结束时间")
    private Date applyEndTimeLt;

    @ApiModelProperty(name = "applyEndTimeLe", value = "小于等于申请结束时间")
    private Date applyEndTimeLe;

    @ApiModelProperty(name = "publishEndTimeGt", value = "大于发布结束时间")
    private Date publishEndTimeGt;

    @ApiModelProperty(name = "publishEndTimeGe", value = "大于等于发布结束时间")
    private Date publishEndTimeGe;

    @ApiModelProperty(name = "publishEndTimeLt", value = "小于发布结束时间")
    private Date publishEndTimeLt;

    @ApiModelProperty(name = "publishEndTimeLe", value = "小于等于发布结束时间")
    private Date publishEndTimeLe;

    @ApiModelProperty(name = "createTimeGt", value = "大于创建时间")
    private Date createTimeGt;

    @ApiModelProperty(name = "createTimeGe", value = "大于等于创建时间")
    private Date createTimeGe;

    @ApiModelProperty(name = "createTimeLt", value = "小于创建时间")
    private Date createTimeLt;

    @ApiModelProperty(name = "createTimeLe", value = "小于等于创建时间")
    private Date createTimeLe;

    @ApiModelProperty(name = "updateTimeGt", value = "大于更新时间")
    private Date updateTimeGt;

    @ApiModelProperty(name = "updateTimeGe", value = "大于等于更新时间")
    private Date updateTimeGe;

    @ApiModelProperty(name = "updateTimeLt", value = "小于更新时间")
    private Date updateTimeLt;

    @ApiModelProperty(name = "updateTimeLe", value = "小于等于更新时间")
    private Date updateTimeLe;

    @ApiModelProperty(name = "applyBeginTime", value = "开放申请时间")
    private Date applyBeginTime;

    @ApiModelProperty(name = "applyBeginTimeGt", value = "大于开放申请时间")
    private Date applyBeginTimeGt;

    @ApiModelProperty(name = "applyBeginTimeGe", value = "大于等于开放申请时间")
    private Date applyBeginTimeGe;

    @ApiModelProperty(name = "applyBeginTimeLt", value = "小于开放申请时间")
    private Date applyBeginTimeLt;

    @ApiModelProperty(name = "applyBeginTimeLe", value = "小于等于开放申请时间")
    private Date applyBeginTimeLe;

    @ApiModelProperty(name = "awardTime", value = "定标时间")
    private Date awardTime;

    @ApiModelProperty(name = "awardTimeGt", value = "大于定标时间")
    private Date awardTimeGt;

    @ApiModelProperty(name = "awardTimeGe", value = "大于等于定标时间")
    private Date awardTimeGe;

    @ApiModelProperty(name = "awardTimeLt", value = "小于定标时间")
    private Date awardTimeLt;

    @ApiModelProperty(name = "awardTimeLe", value = "小于等于定标时间")
    private Date awardTimeLe;

    @ApiModelProperty(name = "cancelTime", value = "取消时间")
    private Date cancelTime;

    @ApiModelProperty(name = "cancelTimeGt", value = "大于取消时间")
    private Date cancelTimeGt;

    @ApiModelProperty(name = "cancelTimeGe", value = "大于等于取消时间")
    private Date cancelTimeGe;

    @ApiModelProperty(name = "cancelTimeLt", value = "小于取消时间")
    private Date cancelTimeLt;

    @ApiModelProperty(name = "cancelTimeLe", value = "小于等于取消时间")
    private Date cancelTimeLe;
}
```

### 5.3 SKU查询条件 BO

```java
package com.ux168.pa.service.scms.biz.service.consignment.recruit.bo;

import com.ux168.platform.infrastructure.common.api.bo.BaseBO;
import io.swagger.annotations.ApiModelProperty;
import lombok.Data;
import lombok.EqualsAndHashCode;
import lombok.ToString;

import java.math.BigDecimal;
import java.util.Date;
import java.util.List;

@Data
@EqualsAndHashCode(callSuper = true)
@ToString(callSuper = true)
public class RecruitSkuQueryBO extends BaseBO {

    @ApiModelProperty(name = "ids", value = "ID列表")
    private List<Long> ids;

    @ApiModelProperty(name = "recruitId", value = "招募清单ID")
    private Long recruitId;

    @ApiModelProperty(name = "recruitIdList", value = "招募清单ID列表")
    private List<Long> recruitIdList;

    @ApiModelProperty(name = "recruitNo", value = "招募清单编号")
    private String recruitNo;

    @ApiModelProperty(name = "skuId", value = "自营SKU ID")
    private Long skuId;

    @ApiModelProperty(name = "skuIdList", value = "自营SKU ID列表")
    private List<Long> skuIdList;

    @ApiModelProperty(name = "categoryId", value = "末级分类ID")
    private Long categoryId;

    @ApiModelProperty(name = "factoryId", value = "货源工厂ID")
    private Long factoryId;

    @ApiModelProperty(name = "skuStatus", value = "SKU状态")
    private Integer skuStatus;

    @ApiModelProperty(name = "skuStatusList", value = "SKU状态列表")
    private List<Integer> skuStatusList;

    @ApiModelProperty(name = "sourceType", value = "来源")
    private Integer sourceType;

    @ApiModelProperty(name = "skuName", value = "SKU名称")
    private String skuName;

    @ApiModelProperty(name = "skuNameList", value = "SKU名称列表")
    private List<String> skuNameList;

    @ApiModelProperty(name = "factoryName", value = "工厂名称")
    private String factoryName;

    @ApiModelProperty(name = "factoryNameList", value = "工厂名称列表")
    private List<String> factoryNameList;

    @ApiModelProperty(name = "purchaseUrl", value = "采购链接")
    private String purchaseUrl;

    @ApiModelProperty(name = "productModel", value = "产品型号")
    private String productModel;

    @ApiModelProperty(name = "vehicleModel", value = "车型")
    private String vehicleModel;

    @ApiModelProperty(name = "unit", value = "单位")
    private String unit;

    @ApiModelProperty(name = "packageInfo", value = "包装信息")
    private String packageInfo;

    @ApiModelProperty(name = "grossWeightG", value = "毛重(g)")
    private BigDecimal grossWeightG;

    @ApiModelProperty(name = "packageSizeCm", value = "包装尺寸(cm)")
    private String packageSizeCm;

    @ApiModelProperty(name = "sourceModel", value = "来源型号")
    private String sourceModel;

    @ApiModelProperty(name = "deliveryDays", value = "交期(天)")
    private Integer deliveryDays;

    @ApiModelProperty(name = "moq", value = "最小起订量")
    private Integer moq;

    @ApiModelProperty(name = "costPrice", value = "成本价")
    private BigDecimal costPrice;

    @ApiModelProperty(name = "saleQty90d", value = "90天销量")
    private Integer saleQty90d;

    @ApiModelProperty(name = "saleQty30d", value = "30天销量")
    private Integer saleQty30d;

    @ApiModelProperty(name = "replenishRemind21d", value = "21天补货提醒")
    private Integer replenishRemind21d;

    @ApiModelProperty(name = "failReason", value = "失败原因")
    private String failReason;

    @ApiModelProperty(name = "importBatchNo", value = "导入批次号")
    private String importBatchNo;

    @ApiModelProperty(name = "importUser", value = "导入人")
    private String importUser;

    @ApiModelProperty(name = "importTimeGt", value = "大于导入时间")
    private Date importTimeGt;

    @ApiModelProperty(name = "importTimeGe", value = "大于等于导入时间")
    private Date importTimeGe;

    @ApiModelProperty(name = "importTimeLt", value = "小于导入时间")
    private Date importTimeLt;

    @ApiModelProperty(name = "importTimeLe", value = "小于等于导入时间")
    private Date importTimeLe;

    @ApiModelProperty(name = "createTimeGt", value = "大于创建时间")
    private Date createTimeGt;

    @ApiModelProperty(name = "createTimeGe", value = "大于等于创建时间")
    private Date createTimeGe;

    @ApiModelProperty(name = "createTimeLt", value = "小于创建时间")
    private Date createTimeLt;

    @ApiModelProperty(name = "createTimeLe", value = "小于等于创建时间")
    private Date createTimeLe;

    @ApiModelProperty(name = "updateTimeGt", value = "大于更新时间")
    private Date updateTimeGt;

    @ApiModelProperty(name = "updateTimeGe", value = "大于等于更新时间")
    private Date updateTimeGe;

    @ApiModelProperty(name = "updateTimeLt", value = "小于更新时间")
    private Date updateTimeLt;

    @ApiModelProperty(name = "updateTimeLe", value = "小于等于更新时间")
    private Date updateTimeLe;
}
```

### 5.4 发布记录查询条件 BO

```java
package com.ux168.pa.service.scms.biz.service.consignment.recruit.bo;

import com.ux168.platform.infrastructure.common.api.bo.BaseBO;
import io.swagger.annotations.ApiModelProperty;
import lombok.Data;
import lombok.EqualsAndHashCode;
import lombok.ToString;

import java.util.Date;
import java.util.List;

@Data
@EqualsAndHashCode(callSuper = true)
@ToString(callSuper = true)
public class RecruitPublishQueryBO extends BaseBO {

    @ApiModelProperty(name = "ids", value = "ID列表")
    private List<Long> ids;

    @ApiModelProperty(name = "recruitId", value = "招募清单ID")
    private Long recruitId;

    @ApiModelProperty(name = "recruitIdList", value = "招募清单ID列表")
    private List<Long> recruitIdList;

    @ApiModelProperty(name = "recruitNo", value = "招募清单编号")
    private String recruitNo;

    @ApiModelProperty(name = "publishRound", value = "发布轮次")
    private Integer publishRound;

    @ApiModelProperty(name = "publishStatus", value = "发布状态")
    private Integer publishStatus;

    @ApiModelProperty(name = "publishStatusList", value = "发布状态列表")
    private List<Integer> publishStatusList;

    @ApiModelProperty(name = "publishJobId", value = "发布任务ID")
    private Long publishJobId;

    @ApiModelProperty(name = "publishBeginTimeGt", value = "大于发布开始时间")
    private Date publishBeginTimeGt;

    @ApiModelProperty(name = "publishBeginTimeGe", value = "大于等于发布开始时间")
    private Date publishBeginTimeGe;

    @ApiModelProperty(name = "publishBeginTimeLt", value = "小于发布开始时间")
    private Date publishBeginTimeLt;

    @ApiModelProperty(name = "publishBeginTimeLe", value = "小于等于发布开始时间")
    private Date publishBeginTimeLe;

    @ApiModelProperty(name = "applyBeginTimeGt", value = "大于开放申请时间")
    private Date applyBeginTimeGt;

    @ApiModelProperty(name = "applyBeginTimeGe", value = "大于等于开放申请时间")
    private Date applyBeginTimeGe;

    @ApiModelProperty(name = "applyBeginTimeLt", value = "小于开放申请时间")
    private Date applyBeginTimeLt;

    @ApiModelProperty(name = "applyBeginTimeLe", value = "小于等于开放申请时间")
    private Date applyBeginTimeLe;

    @ApiModelProperty(name = "applyEndTimeGt", value = "大于申请结束时间")
    private Date applyEndTimeGt;

    @ApiModelProperty(name = "applyEndTimeGe", value = "大于等于申请结束时间")
    private Date applyEndTimeGe;

    @ApiModelProperty(name = "applyEndTimeLt", value = "小于申请结束时间")
    private Date applyEndTimeLt;

    @ApiModelProperty(name = "applyEndTimeLe", value = "小于等于申请结束时间")
    private Date applyEndTimeLe;

    @ApiModelProperty(name = "publishEndTimeGt", value = "大于发布结束时间")
    private Date publishEndTimeGt;

    @ApiModelProperty(name = "publishEndTimeGe", value = "大于等于发布结束时间")
    private Date publishEndTimeGe;

    @ApiModelProperty(name = "publishEndTimeLt", value = "小于发布结束时间")
    private Date publishEndTimeLt;

    @ApiModelProperty(name = "publishEndTimeLe", value = "小于等于发布结束时间")
    private Date publishEndTimeLe;

    @ApiModelProperty(name = "createTimeGt", value = "大于创建时间")
    private Date createTimeGt;

    @ApiModelProperty(name = "createTimeGe", value = "大于等于创建时间")
    private Date createTimeGe;

    @ApiModelProperty(name = "createTimeLt", value = "小于创建时间")
    private Date createTimeLt;

    @ApiModelProperty(name = "createTimeLe", value = "小于等于创建时间")
    private Date createTimeLe;

    @ApiModelProperty(name = "updateTimeGt", value = "大于更新时间")
    private Date updateTimeGt;

    @ApiModelProperty(name = "updateTimeGe", value = "大于等于更新时间")
    private Date updateTimeGe;

    @ApiModelProperty(name = "updateTimeLt", value = "小于更新时间")
    private Date updateTimeLt;

    @ApiModelProperty(name = "updateTimeLe", value = "小于等于更新时间")
    private Date updateTimeLe;
}
```

### 5.5 申请查询条件 BO

```java
package com.ux168.pa.service.scms.biz.service.consignment.recruit.bo;

import com.ux168.platform.infrastructure.common.api.bo.BaseBO;
import io.swagger.annotations.ApiModelProperty;
import lombok.Data;
import lombok.EqualsAndHashCode;
import lombok.ToString;

import java.math.BigDecimal;
import java.util.Date;
import java.util.List;

@Data
@EqualsAndHashCode(callSuper = true)
@ToString(callSuper = true)
public class RecruitApplyQueryBO extends BaseBO {

    @ApiModelProperty(name = "ids", value = "申请ID列表")
    private List<Long> ids;

    @ApiModelProperty(name = "recruitId", value = "招募清单ID")
    private Long recruitId;

    @ApiModelProperty(name = "recruitIdList", value = "招募清单ID列表")
    private List<Long> recruitIdList;

    @ApiModelProperty(name = "recruitNo", value = "招募清单编号")
    private String recruitNo;

    @ApiModelProperty(name = "supplierId", value = "寄卖商ID")
    private Long supplierId;

    @ApiModelProperty(name = "supplierIdList", value = "寄卖商ID列表")
    private List<Long> supplierIdList;

    @ApiModelProperty(name = "groupId", value = "寄卖商集团ID")
    private Long groupId;

    @ApiModelProperty(name = "factoryId", value = "货源工厂ID")
    private Long factoryId;

    @ApiModelProperty(name = "applyStatus", value = "申请状态")
    private Integer applyStatus;

    @ApiModelProperty(name = "applyStatusList", value = "申请状态列表")
    private List<Integer> applyStatusList;

    @ApiModelProperty(name = "ceBillNo", value = "CE单号")
    private String ceBillNo;

    @ApiModelProperty(name = "rankNo", value = "评选排名")
    private Integer rankNo;

    @ApiModelProperty(name = "awardResult", value = "评选结果")
    private Integer awardResult;

    @ApiModelProperty(name = "supplierName", value = "寄卖商名称")
    private String supplierName;

    @ApiModelProperty(name = "supplierNameList", value = "寄卖商名称列表")
    private List<String> supplierNameList;

    @ApiModelProperty(name = "sameSourceFlag", value = "同货源标识")
    private Integer sameSourceFlag;

    @ApiModelProperty(name = "removedType", value = "移除类型")
    private String removedType;

    @ApiModelProperty(name = "removedTypeList", value = "移除类型列表")
    private List<String> removedTypeList;

    @ApiModelProperty(name = "inboundSkuCount", value = "入库SKU数量")
    private Integer inboundSkuCount;

    @ApiModelProperty(name = "baseCoverageRate", value = "基础覆盖率")
    private BigDecimal baseCoverageRate;

    @ApiModelProperty(name = "sameSourceWeightRate", value = "同货源权重覆盖率")
    private BigDecimal sameSourceWeightRate;

    @ApiModelProperty(name = "finalCoverageRate", value = "最终覆盖率")
    private BigDecimal finalCoverageRate;

    @ApiModelProperty(name = "awardReason", value = "评选原因")
    private String awardReason;

    @ApiModelProperty(name = "cancelType", value = "取消类型")
    private String cancelType;

    @ApiModelProperty(name = "cancelTypeList", value = "取消类型列表")
    private List<String> cancelTypeList;

    @ApiModelProperty(name = "cancelReason", value = "取消原因")
    private String cancelReason;

    @ApiModelProperty(name = "ceCreateTimeGt", value = "大于CE开单时间")
    private Date ceCreateTimeGt;

    @ApiModelProperty(name = "ceCreateTimeGe", value = "大于等于CE开单时间")
    private Date ceCreateTimeGe;

    @ApiModelProperty(name = "ceCreateTimeLt", value = "小于CE开单时间")
    private Date ceCreateTimeLt;

    @ApiModelProperty(name = "ceCreateTimeLe", value = "小于等于CE开单时间")
    private Date ceCreateTimeLe;

    @ApiModelProperty(name = "ceSendTimeGt", value = "大于CE发货时间")
    private Date ceSendTimeGt;

    @ApiModelProperty(name = "ceSendTimeGe", value = "大于等于CE发货时间")
    private Date ceSendTimeGe;

    @ApiModelProperty(name = "ceSendTimeLt", value = "小于CE发货时间")
    private Date ceSendTimeLt;

    @ApiModelProperty(name = "ceSendTimeLe", value = "小于等于CE发货时间")
    private Date ceSendTimeLe;

    @ApiModelProperty(name = "firstQcPassTimeGt", value = "大于首次质检通过时间")
    private Date firstQcPassTimeGt;

    @ApiModelProperty(name = "firstQcPassTimeGe", value = "大于等于首次质检通过时间")
    private Date firstQcPassTimeGe;

    @ApiModelProperty(name = "firstQcPassTimeLt", value = "小于首次质检通过时间")
    private Date firstQcPassTimeLt;

    @ApiModelProperty(name = "firstQcPassTimeLe", value = "小于等于首次质检通过时间")
    private Date firstQcPassTimeLe;

    @ApiModelProperty(name = "createTimeGt", value = "大于创建时间")
    private Date createTimeGt;

    @ApiModelProperty(name = "createTimeGe", value = "大于等于创建时间")
    private Date createTimeGe;

    @ApiModelProperty(name = "createTimeLt", value = "小于创建时间")
    private Date createTimeLt;

    @ApiModelProperty(name = "createTimeLe", value = "小于等于创建时间")
    private Date createTimeLe;

    @ApiModelProperty(name = "updateTimeGt", value = "大于更新时间")
    private Date updateTimeGt;

    @ApiModelProperty(name = "updateTimeGe", value = "大于等于更新时间")
    private Date updateTimeGe;

    @ApiModelProperty(name = "updateTimeLt", value = "小于更新时间")
    private Date updateTimeLt;

    @ApiModelProperty(name = "updateTimeLe", value = "小于等于更新时间")
    private Date updateTimeLe;
}
```

### 5.6 日志查询条件 BO

```java
package com.ux168.pa.service.scms.biz.service.consignment.recruit.bo;

import com.ux168.platform.infrastructure.common.api.bo.BaseBO;
import io.swagger.annotations.ApiModelProperty;
import lombok.Data;
import lombok.EqualsAndHashCode;
import lombok.ToString;

import java.util.Date;
import java.util.List;

@Data
@EqualsAndHashCode(callSuper = true)
@ToString(callSuper = true)
public class ActionLogQueryBO extends BaseBO {

    @ApiModelProperty(name = "ids", value = "ID列表")
    private List<Long> ids;

    @ApiModelProperty(name = "recruitId", value = "招募清单ID")
    private Long recruitId;

    @ApiModelProperty(name = "recruitIdList", value = "招募清单ID列表")
    private List<Long> recruitIdList;

    @ApiModelProperty(name = "recruitNo", value = "招募清单编号")
    private String recruitNo;

    @ApiModelProperty(name = "applyId", value = "申请ID")
    private Long applyId;

    @ApiModelProperty(name = "applyIdList", value = "申请ID列表")
    private List<Long> applyIdList;

    @ApiModelProperty(name = "supplierId", value = "寄卖商ID")
    private Long supplierId;

    @ApiModelProperty(name = "action", value = "动作")
    private String action;

    @ApiModelProperty(name = "actionList", value = "动作列表")
    private List<String> actionList;

    @ApiModelProperty(name = "beforeStatus", value = "变更前状态")
    private Integer beforeStatus;

    @ApiModelProperty(name = "afterStatus", value = "变更后状态")
    private Integer afterStatus;

    @ApiModelProperty(name = "operatorType", value = "操作人类型")
    private Integer operatorType;

    @ApiModelProperty(name = "operatorTypeList", value = "操作人类型列表")
    private List<Integer> operatorTypeList;

    @ApiModelProperty(name = "operatorId", value = "操作人ID")
    private String operatorId;

    @ApiModelProperty(name = "operatorName", value = "操作人名称")
    private String operatorName;

    @ApiModelProperty(name = "requestId", value = "请求ID")
    private String requestId;

    @ApiModelProperty(name = "content", value = "日志内容")
    private String content;

    @ApiModelProperty(name = "contentList", value = "日志内容列表")
    private List<String> contentList;

    @ApiModelProperty(name = "createTimeGt", value = "大于创建时间")
    private Date createTimeGt;

    @ApiModelProperty(name = "createTimeGe", value = "大于等于创建时间")
    private Date createTimeGe;

    @ApiModelProperty(name = "createTimeLt", value = "小于创建时间")
    private Date createTimeLt;

    @ApiModelProperty(name = "createTimeLe", value = "小于等于创建时间")
    private Date createTimeLe;

    @ApiModelProperty(name = "updateTimeGt", value = "大于更新时间")
    private Date updateTimeGt;

    @ApiModelProperty(name = "updateTimeGe", value = "大于等于更新时间")
    private Date updateTimeGe;

    @ApiModelProperty(name = "updateTimeLt", value = "小于更新时间")
    private Date updateTimeLt;

    @ApiModelProperty(name = "updateTimeLe", value = "小于等于更新时间")
    private Date updateTimeLe;
}
```

---

## 六、特殊场景操作方案

### 6.1 查询指定字段

**场景**: 列表查询无需全部字段，只查 ID + 状态 以减少数据传输。

```java
// 方案1：LambdaQueryWrapper.select() — 返回值仍是PO，未选字段为null
List<ConsignmentRecruitListPO> list = listRepository.findList(
    new BizLambdaQueryWrapper<ConsignmentRecruitListPO>()
        .select(ConsignmentRecruitListPO::getId,
                ConsignmentRecruitListPO::getRecruitNo,
                ConsignmentRecruitListPO::getListStatus,
                ConsignmentRecruitListPO::getSkuCount)
        .eq(ConsignmentRecruitListPO::getListStatus, status)
);

// 方案2：通过自定义Mapper + DTO（复杂查询用）
// Mapper接口
@Select("SELECT id, recruit_no, list_status FROM scms_consignment_recruit_list " +
        "WHERE list_status = #{status}")
List<RecruitListSimpleDTO> selectSimpleList(@Param("status") Integer status);

// DTO类
@Data
public class RecruitListSimpleDTO {
    private Long id;
    private String recruitNo;
    private Integer listStatus;
}
```

### 6.2 更新 NULL 字段

**场景**: 作废清单时，需要将 `cancel_reason` 从有值更新为 NULL（清空）。

```java
// 方案：使用 LambdaUpdateWrapper.set(column, null)
// MyBatis-Plus 默认 updateById 会忽略null字段，因此必须用 UpdateWrapper

boolean success = listRepository.update(
    new LambdaUpdateWrapper<ConsignmentRecruitListPO>()
        .set(ConsignmentRecruitListPO::getCancelTime, null)
        .set(ConsignmentRecruitListPO::getCancelType, null)
        .set(ConsignmentRecruitListPO::getCancelReason, null)
        .eq(ConsignmentRecruitListPO::getId, id)
);

// 如果不知道字段名，可以用字符串形式（不推荐，编译不安全）
wrapper.set("cancel_time", null);
```

### 6.3 乐观锁状态更新

**场景**: 并发场景下（自动发布 + 手动发布可能同时操作同一清单）。

```java
// 核心模式：UPDATE ... WHERE id=? AND list_status=oldStatus
// 影响行数 = 0 表示状态已被其他线程修改，本次更新失效

// 例：待发布 → 招募中（仅当当前状态是 10-待发布）
boolean updated = listRepository.update(
    new LambdaUpdateWrapper<ConsignmentRecruitListPO>()
        .set(ConsignmentRecruitListPO::getListStatus,
                ConsignmentRecruitListStatusEnum.RECRUITING.getCode())
        .set(ConsignmentRecruitListPO::getPublishBeginTime, new Date())
        .set(ConsignmentRecruitListPO::getPublishBy, "系统")
        .eq(ConsignmentRecruitListPO::getId, listId)
        .eq(ConsignmentRecruitListPO::getListStatus,
                ConsignmentRecruitListStatusEnum.WAIT_PUBLISH.getCode())
);

if (!updated) {
    // 幂等返回：说明已经被其他人处理了，不抛异常
    log.warn("清单状态已变更，跳过更新: listId={}", listId);
}
```

### 6.4 批量插入

```java
// 方案1：继承自BaseRepositoryImpl的saveBatch（推荐）
// 自动分批，默认每批 1000 条
listSkuRepository.saveBatch(skuList);  // List<ConsignmentRecruitListSkuPO>

// 指定每批500条
listSkuRepository.saveBatch(skuList, 500);

// 方案2：通过 Mapper XML foreach 批量插入（超大批量 > 5000 条时用）
// XML:
// <insert id="batchInsert">
//     INSERT INTO scms_consignment_recruit_list_sku (...) VALUES
//     <foreach collection="list" item="item" separator=",">
//         (#{item.skuId}, #{item.skuName}, ...)
//     </foreach>
// </insert>
```

### 6.5 批量更新（逐条更新 + 事务）

**场景**: 批量更新多条记录，每条更新不同字段值。

```java
@Service
public class RecruitListService {

    @Resource
    private ConsignmentRecruitListRepository listRepository;

    /**
     * 批量更新清单（事务内逐条updateById）
     * MyBatis-Plus updateById 默认只更新非null字段
     */
    @Transactional(rollbackFor = Exception.class)
    public void batchUpdateList(List<ConsignmentRecruitListPO> updateList) {
        if (CollectionUtils.isEmpty(updateList)) {
            return;
        }
        for (ConsignmentRecruitListPO po : updateList) {
            boolean updated = listRepository.updateById(po);
            if (!updated) {
                log.warn("批量更新失败（可能已被删除）: id={}", po.getId());
            }
        }
    }
}
```

### 6.6 批量更新到相同值（条件更新）

**场景**: 将某批记录的同一字段更新为相同值（如批量作废、批量状态变更）。

```java
// 方案：使用 UpdateWrapper 批量条件更新，一条SQL完成

// 例：将某清单的所有 SKU 状态从 20 改为 30
int affectedRows = listSkuRepository.update(
    new LambdaUpdateWrapper<ConsignmentRecruitListSkuPO>()
        .set(ConsignmentRecruitListSkuPO::getSkuStatus,
                ConsignmentRecruitSkuStatusEnum.PUBLISHED.getCode())
        .eq(ConsignmentRecruitListSkuPO::getRecruitId, recruitId)
        .eq(ConsignmentRecruitListSkuPO::getSkuStatus,
                ConsignmentRecruitSkuStatusEnum.GROUPED.getCode())
);

log.info("批量更新SKU状态: recruitId={}, from=20, to=30, affected={}", recruitId, affectedRows);
```

### 6.7 增量更新（SET column = column + delta）

**场景**: CoverageCalcTrigger 更新覆盖率时，需要累加 `inbound_sku_count`。

```java
// 方案1：使用 setSql 直接写 SQL 片段
int updated = applyRepository.update(
    new LambdaUpdateWrapper<ConsignmentRecruitApplyPO>()
        .setSql("inbound_sku_count = inbound_sku_count + " + deltaCount)
        .setSql("base_coverage_rate = (inbound_sku_count + " + deltaCount + ") / " + totalSkuCount)
        .eq(ConsignmentRecruitApplyPO::getId, applyId)
);

// 方案2：先查后改（适合需要读取旧值做判断的场景）
ConsignmentRecruitApplyPO apply = applyRepository.getById(applyId);
int newInbound = apply.getInboundSkuCount() + deltaCount;
apply.setInboundSkuCount(newInbound);
// 计算新覆盖率
apply.setBaseCoverageRate(BigDecimal.valueOf(newInbound)
        .divide(BigDecimal.valueOf(totalSkuCount), 4, RoundingMode.HALF_UP));
applyRepository.updateById(apply);  // 只更新非null字段
```

---

## 七、表关系与联表查询

### 7.1 表关系图

```
┌──────────────────────────────┐       1:N
│  recruit_list (主表)          │───────────────────────┐
│  id (PK)                     │                       │
│  recruit_no (UK)             │                       │
│  list_status                 │                       │
│  award_supplier_id (FK)      │                       │
└──────────────┬───────────────┘                       │
               │                                       │
               │ 1:N                                   │
               │                                       │
               ├──────────────────────────────────┐    │
               │                                   │    │
               ▼                                   ▼    │
┌──────────────────────────────┐   ┌────────────────────┐│
│  recruit_list_sku (明细)      │   │  recruit_publish   ││
│  recruit_id (FK → list.id)   │   │  (发布记录)         ││
│  sku_id                      │   │  recruit_id (FK)   ││
│  sku_status                  │   │  publish_round     ││
│  (recruit_id=0 表示在招募池)   │   └────────────────────┘│
└──────────────────────────────┘                       │
                                                       │
               ┌──────────────────────────────────────┐│
               │  recruit_apply (申请)                  ││
               │  recruit_id (FK → list.id)           ││
               │  supplier_id                         ││
               │  apply_status                        ││
               │  final_coverage_rate                 ││
               │  (recruit_id + supplier_id 唯一)      ││
               └──────────────────────────────────────┘│
                                                       │
               ┌──────────────────────────────────────┐│
               │  action_log (操作日志)                 ││
               │  recruit_id / apply_id (FK)          │◄┘
               │  action / before_status / after_status│
               └──────────────────────────────────────┘
```

### 7.2 表关系说明

| 主表 | 从表 | 关联键 | 关系 | 说明 |
|------|------|--------|------|------|
| recruit_list | recruit_list_sku | recruit_id = id | 1:N | 一张清单包含多个SKU |
| recruit_list | recruit_publish | recruit_id = id | 1:N | 一张清单可发布多轮 |
| recruit_list | recruit_apply | recruit_id = id | 1:N | 一张清单可有多供应商申请 |
| recruit_apply | action_log | apply_id = id | 1:N | 一个申请有多条操作日志 |
| recruit_list | action_log | recruit_id = id | 1:N | 一张清单有多条操作日志 |
| recruit_list_sku | — | recruit_id = 0 | 特殊 | 招募池模式 |

### 7.3 联表查询方案

#### 7.3.1 Mapper XML 联查（适合复杂联表）

```xml
<!-- resources/mapper/consignment/recruit/ConsignmentRecruitListMapper.xml -->
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE mapper PUBLIC "-//mybatis.org//DTD Mapper 3.0//EN"
        "http://mybatis.org/dtd/mybatis-3-mapper.dtd">
<mapper namespace="com.ux168.pa.service.scms.biz.dao.mysql.consignment.recruit.mapper.ConsignmentRecruitListMapper">

    <!-- 查询清单详情（含申请信息、发布信息） -->
    <select id="selectDetailById" resultType="com.ux168.pa.service.scms.biz.service.consignment.recruit.bo.RecruitDetailBO">
        SELECT
            l.*,
            a.id AS applyId,
            a.supplier_id,
            a.supplier_name,
            a.apply_status,
            a.final_coverage_rate,
            p.id AS publishId,
            p.publish_round,
            p.publish_status
        FROM scms_consignment_recruit_list l
        LEFT JOIN scms_consignment_recruit_apply a ON l.id = a.recruit_id AND a.is_deleted = 0
        LEFT JOIN scms_consignment_recruit_publish p ON l.id = p.recruit_id AND p.is_deleted = 0
        WHERE l.id = #{listId} AND l.is_deleted = 0
    </select>

    <!-- 统计某工厂某分类的清单数量 -->
    <select id="countByFactoryAndCategory" resultType="java.lang.Integer">
        SELECT COUNT(*)
        FROM scms_consignment_recruit_list
        WHERE factory_id = #{factoryId}
          AND category_id = #{categoryId}
          AND list_status IN (20, 25)
          AND is_deleted = 0
    </select>

</mapper>
```

```java
// Mapper 接口中对应声明
@Select("selectDetailById")
RecruitDetailBO selectDetailById(@Param("listId") Long listId);

@Select("countByFactoryAndCategory")
Integer countByFactoryAndCategory(@Param("factoryId") Long factoryId,
                                   @Param("categoryId") Long categoryId);
```

#### 7.3.2 Repository 层分步查询（适合简单关联）

```java
// Service 层组合多个 Repository 查询
// 推荐优先使用，代码可读性好，易于维护

public RecruitDetailBO getRecruitDetail(Long listId) {
    // Step 1: 查主表
    ConsignmentRecruitListPO list = listRepository.getById(listId);
    if (list == null) {
        return null;
    }

    // Step 2: 查申请记录
    List<ConsignmentRecruitApplyPO> applies = applyRepository.getActiveByRecruitId(listId);

    // Step 3: 查发布记录
    List<ConsignmentRecruitPublishPO> publishes = publishRepository.getByRecruitId(listId);

    // Step 4: 查SKU明细
    RecruitSkuQueryBO skuQuery = new RecruitSkuQueryBO();
    skuQuery.setRecruitId(listId);
    List<ConsignmentRecruitListSkuPO> skus = skuRepository.findList(skuQuery);

    // Step 5: 组装BO
    RecruitDetailBO detail = new RecruitDetailBO();
    BeanUtils.copyProperties(list, detail);
    detail.setApplies(applies);
    detail.setPublishes(publishes);
    detail.setSkus(skus);
    return detail;
}
```

#### 7.3.3 Mapper XML 分页联查（适合列表场景）

```java
// Mapper 接口
Page<RecruitListPageBO> selectPageList(Page<?> page, @Param("query") RecruitListQueryBO queryBO);

// 实现
@Override
public ListResult<RecruitListPageBO> pageListWithApplyCount(RecruitListQueryBO queryBO,
                                                              Integer pageNo, Integer pageSize) {
    Page<RecruitListPageBO> page = new Page<>(pageNo, pageSize);
    Page<RecruitListPageBO> result = consignmentRecruitListMapper.selectPageList(page, queryBO);

    ListResult<RecruitListPageBO> listResult = new ListResult<>();
    listResult.setList(result.getRecords());

    RespPageDO respPage = new RespPageDO();
    respPage.setTotalSize(result.getTotal());
    respPage.setTotalPage(result.getPages());
    respPage.setCurrentSize(pageSize);
    respPage.setNextPage(result.getCurrent() + 1);
    listResult.setPage(respPage);
    return listResult;
}
```

---

## 八、分页查询通用方案

#### 8.1 标准分页模板

```java
// 所有 Repository 的分页查询统一遵循此模式

@Override
public ListResult<ConsignmentRecruitListPO> pageList(RecruitListQueryBO queryBO,
                                                      Integer pageNo, Integer pageSize) {
    // 调用继承 BaseRepositoryImpl 的 pageList(wrapper, pageNo, pageSize)
    // 先通过 buildQueryWrapper 构建条件，再传入 pageList
    return pageList(buildQueryWrapper(queryBO)
            .orderByDesc(ConsignmentRecruitListPO::getUpdateTime), pageNo, pageSize);
}
```

### 8.2 BizLambdaQueryWrapper 支持的方法

| 方法 | 说明 | SQL效果 |
|------|------|---------|
| `.eqIfPresent(func, value)` | value!=null时等值条件 | `column = ?` |
| `.neIfPresent(func, value)` | value!=null时不等条件 | `column <> ?` |
| `.inIfPresent(func, list)` | list不空时IN条件 | `column IN (?,?,?)` |
| `.notInIfPresent(func, list)` | list不空时NOT IN | `column NOT IN (?,?,?)` |
| `.likeIfPresent(func, value)` | value!=null时LIKE | `column LIKE '%value%'` |
| `.likeLeftIfPresent(func, value)` | 左LIKE | `column LIKE '%value'` |
| `.likeRightIfPresent(func, value)` | 右LIKE | `column LIKE 'value%'` |
| `.geIfPresent(func, value)` | value!=null时>= | `column >= ?` |
| `.gtIfPresent(func, value)` | value!=null时> | `column > ?` |
| `.leIfPresent(func, value)` | value!=null时<= | `column <= ?` |
| `.ltIfPresent(func, value)` | value!=null时< | `column < ?` |
| `.isNullIfPresent(func, flag)` | flag=true时IS NULL | `column IS NULL` |
| `.orderByAsc(func)` | 升序排序 | `ORDER BY column ASC` |
| `.orderByDesc(func)` | 降序排序 | `ORDER BY column DESC` |

### 8.3 分页响应结构

```java
// ListResult 结构（来自 platform-infrastructure-common）
public class ListResult<T> {
    private List<T> list;           // 当前页数据
    private RespPageDO page;        // 分页信息
}

public class RespPageDO {
    private Long totalSize;         // 总记录数
    private Long totalPage;         // 总页数
    private Long nextPage;          // 下一页页码
    private Integer currentSize;    // 当前页大小
}
```

---

## 九、完整文件路径清单

### 9.1 PO 实体类（5个文件）

```
pa-biz-service/pa-scms-service/pa-scms-service-biz/src/main/java/com/ux168/pa/service/scms/biz/
└── dao/mysql/consignment/recruit/po/
    ├── ConsignmentRecruitListPO.java
    ├── ConsignmentRecruitListSkuPO.java
    ├── ConsignmentRecruitPublishPO.java
    ├── ConsignmentRecruitApplyPO.java
    └── ConsignmentActionLogPO.java
```

### 9.2 Mapper 接口（5个文件）

```
pa-biz-service/pa-scms-service/pa-scms-service-biz/src/main/java/com/ux168/pa/service/scms/biz/
└── dao/mysql/consignment/recruit/mapper/
    ├── ConsignmentRecruitListMapper.java
    ├── ConsignmentRecruitListSkuMapper.java
    ├── ConsignmentRecruitPublishMapper.java
    ├── ConsignmentRecruitApplyMapper.java
    └── ConsignmentActionLogMapper.java
```

### 9.3 Mapper XML 映射文件（5个文件）

```
pa-biz-service/pa-scms-service/pa-scms-service-biz/src/main/resources/
└── mapper/consignment/recruit/
    ├── ConsignmentRecruitListMapper.xml
    ├── ConsignmentRecruitListSkuMapper.xml
    ├── ConsignmentRecruitPublishMapper.xml
    ├── ConsignmentRecruitApplyMapper.xml
    └── ConsignmentActionLogMapper.xml
```

### 9.4 Repository 接口（5个文件）

```
pa-biz-service/pa-scms-service/pa-scms-service-biz/src/main/java/com/ux168/pa/service/scms/biz/
└── dao/mysql/consignment/recruit/repository/
    ├── ConsignmentRecruitListRepository.java
    ├── ConsignmentRecruitListSkuRepository.java
    ├── ConsignmentRecruitPublishRepository.java
    ├── ConsignmentRecruitApplyRepository.java
    └── ConsignmentActionLogRepository.java
```

### 9.5 Repository 实现（5个文件）

```
pa-biz-service/pa-scms-service/pa-scms-service-biz/src/main/java/com/ux168/pa/service/scms/biz/
└── dao/mysql/consignment/recruit/repository/impl/
    ├── ConsignmentRecruitListRepositoryImpl.java
    ├── ConsignmentRecruitListSkuRepositoryImpl.java
    ├── ConsignmentRecruitPublishRepositoryImpl.java
    ├── ConsignmentRecruitApplyRepositoryImpl.java
    └── ConsignmentActionLogRepositoryImpl.java
```

### 9.6 查询条件 BO（5个文件）

```
pa-biz-service/pa-scms-service/pa-scms-service-biz/src/main/java/com/ux168/pa/service/scms/biz/
└── service/consignment/recruit/bo/
    ├── RecruitListQueryBO.java
    ├── RecruitSkuQueryBO.java
    ├── RecruitPublishQueryBO.java
    ├── RecruitApplyQueryBO.java
    └── ActionLogQueryBO.java
```

### 9.7 开放API接口（5个ServiceApi + 40个DTO文件）

```
pa-biz-service/pa-scms-service/pa-scms-service-api/src/main/java/com/ux168/pa/service/scms/
└── api/consignment/recruit/
    ├── list/
    │   ├── ConsignmentRecruitListServiceApi.java
    │   └── dto/
    │       ├── ConsignmentRecruitListBaseDTO.java
    │       ├── request/
    │       │   ├── ConsignmentRecruitListCreateReqDTO.java
    │       │   ├── ConsignmentRecruitListUpdateReqDTO.java
    │       │   ├── ConsignmentRecruitListListReqDTO.java
    │       │   └── ConsignmentRecruitListPageReqDTO.java
    │       └── response/
    │           ├── ConsignmentRecruitListRespDTO.java
    │           └── ConsignmentRecruitListPageRespDTO.java
    ├── listsku/
    │   ├── ConsignmentRecruitListSkuServiceApi.java
    │   └── dto/
    │       ├── ConsignmentRecruitListSkuBaseDTO.java
    │       ├── request/
    │       │   ├── ConsignmentRecruitListSkuCreateReqDTO.java
    │       │   ├── ConsignmentRecruitListSkuUpdateReqDTO.java
    │       │   ├── ConsignmentRecruitListSkuListReqDTO.java
    │       │   └── ConsignmentRecruitListSkuPageReqDTO.java
    │       └── response/
    │           ├── ConsignmentRecruitListSkuRespDTO.java
    │           └── ConsignmentRecruitListSkuPageRespDTO.java
    ├── publish/
    │   ├── ConsignmentRecruitPublishServiceApi.java
    │   └── dto/
    │       ├── ConsignmentRecruitPublishBaseDTO.java
    │       ├── request/
    │       │   ├── ConsignmentRecruitPublishCreateReqDTO.java
    │       │   ├── ConsignmentRecruitPublishUpdateReqDTO.java
    │       │   ├── ConsignmentRecruitPublishListReqDTO.java
    │       │   └── ConsignmentRecruitPublishPageReqDTO.java
    │       └── response/
    │           ├── ConsignmentRecruitPublishRespDTO.java
    │           └── ConsignmentRecruitPublishPageRespDTO.java
    ├── apply/
    │   ├── ConsignmentRecruitApplyServiceApi.java
    │   └── dto/
    │       ├── ConsignmentRecruitApplyBaseDTO.java
    │       ├── request/
    │       │   ├── ConsignmentRecruitApplyCreateReqDTO.java
    │       │   ├── ConsignmentRecruitApplyUpdateReqDTO.java
    │       │   ├── ConsignmentRecruitApplyListReqDTO.java
    │       │   └── ConsignmentRecruitApplyPageReqDTO.java
    │       └── response/
    │           ├── ConsignmentRecruitApplyRespDTO.java
    │           └── ConsignmentRecruitApplyPageRespDTO.java
    └── actionlog/
        ├── ConsignmentActionLogServiceApi.java
        └── dto/
            ├── ConsignmentActionLogBaseDTO.java
            ├── request/
            │   ├── ConsignmentActionLogCreateReqDTO.java
            │   ├── ConsignmentActionLogUpdateReqDTO.java
            │   ├── ConsignmentActionLogListReqDTO.java
            │   └── ConsignmentActionLogPageReqDTO.java
            └── response/
                ├── ConsignmentActionLogRespDTO.java
                └── ConsignmentActionLogPageRespDTO.java
```

**总计 70 个文件**（PO×5 + Mapper×5 + XML×5 + Repository接口×5 + RepositoryImpl×5 + BO×5 + ServiceApi×5 + DTO×40）

---

## 十、代码实现建议

### 10.1 开发优先级

| 优先级 | 层 | 原因 |
|--------|------|------|
| P0 | 5个PO实体类 | 其他层依赖PO定义 |
| P0 | 5个Mapper接口 | 基础CRUD的入口 |
| P0 | 5个Repository接口 + 实现 | Service层依赖 |
| P0 | 5个ServiceApi接口 + 40个DTO | 外部API依赖 |
| P1 | 5个BO查询条件 | 配合Repository查询 |
| P1 | 5个Mapper XML | 复杂查询场景 |
| P1 | 5个ServiceApi实现 | API实现层 |
| P2 | DTO响应类 | 联表查询返回值 |

### 10.2 注意事项

1. **枚举常量替代硬编码**: 所有状态值使用 `枚举类.getCode()`，禁止直接写数字
2. **乐观锁条件**: 所有状态变更操作必须带 `WHERE old_status` 条件保障并发安全
3. **事务管理**: 批量操作在 RepositoryImpl 层加 `@Transactional(rollbackFor = Exception.class)`
4. **空安全**: 查询条件BO使用包装类型 + `eqIfPresent` 自动过滤 null
5. **分页默认值**: `pageNo` 默认1，`pageSize` 默认20，最大值限制200
6. **逻辑删除**: 所有查询自动带 `is_deleted = 0` 条件（@TableLogic 自动处理）
7. **时间处理**: 数据库使用 `datetime(6)`，Java 使用 `java.util.Date`（与 CommonPO 一致）
8. **禁用 `baseMapper`**: `BaseRepositoryImpl` 不暴露 `baseMapper`，自定义更新需通过 `@Resource` 注入具体 Mapper 后调用 `mapper.update(entity, wrapper)`
9. **禁用 `getOne`**: `BaseRepositoryImpl` 没有 `getOne` 方法，单记录查询使用 `findList(wrapper.last("LIMIT 1"))` + `CollectionUtils.isEmpty` 判断
10. **BO 继承 `BaseBO`**: 所有查询条件 BO 需继承 `com.ux168.platform.infrastructure.common.api.bo.BaseBO`
11. **时间后缀命名**: 时间范围查询使用 `Gt`/`Ge`/`Lt`/`Le` 后缀（非 `Start`/`End`），每个时间字段生成4个查询变体
12. **buildQueryWrapper 全覆盖**: 所有 PO 字段（包括通用字段 `create_time`/`update_time`）都必须在 `buildQueryWrapper` 中有对应的 `eqIfPresent`/`inIfPresent`/`gtIfPresent`/`geIfPresent`/`ltIfPresent`/`leIfPresent` 条件

### 10.3 最佳实践总结

```java
// 1. 单表简单查询 → BizLambdaQueryWrapper + eqIfPresent
// 2. 单表复杂聚合/多表联查 → Mapper XML
// 3. 状态变更 → LambdaUpdateWrapper + 乐观锁条件
// 4. 批量更新相同值 → 条件更新（一条SQL）
// 5. 批量更新不同值 → 逐条 updateById + @Transactional（或 super.updateBatch）
// 6. 大批量插入（>5000条） → Mapper XML foreach
// 7. 查询指定字段 → wrapper.select()
// 8. 更新NULL字段 → wrapper.set(column, null)
// 9. 增量更新 → wrapper.setSql("column = column + delta")
// 10. 分页查询 → 统一返回 ListResult，统一使用 BizLambdaQueryWrapper
// 11. 自定义更新 → @Resource 注入具体 Mapper 调用 mapper.update(entity, wrapper)
// 12. 单记录查询 → findList(wrapper.last("LIMIT 1")) + CollectionUtils.isEmpty(list) ? null : list.get(0)
// 13. 批量插入 → saveOrUpdateBatch(list)
// 14. 批量更新 → super.updateBatch(list)
// 15. 时间范围查询 → bo 中使用 Gt/Ge/Lt/Le 后缀字段，buildQueryWrapper 中配合 gtIfPresent/geIfPresent/ltIfPresent/leIfPresent
```

---

## 十一、开放API接口层

### 11.1 API设计原则

- 使用 FeignClient 定义，通过 `@FeignClient` 暴露为标准 REST API，统一返回 `CommResponse<T>` 包装
- 每个模块独立定义 ServiceApi 接口、BaseDTO、CreateReqDTO、UpdateReqDTO、ListReqDTO、PageReqDTO、RespDTO、PageRespDTO
- 遵循统一命名规范：`Consignment{ModuleName}ServiceApi`、`Consignment{ModuleName}{Type}DTO`
- 标准CRUD方法统一使用 `/v1/{method}` 路径前缀
- 模块特有查询方法使用更具体的路径（如 `/v1/findByStatus`）
- 请求方法遵循 POST 用于写操作、GET 用于查询操作的原则

### 11.2 API路径常量

在 `ScmsServiceApiConstants.java` 中新增以下路径常量：

| 常量名 | 值 | 对应模块 |
|--------|------|---------|
| `CONSIGNMENT_RECRUIT_LIST_API_PATH` | `/scms/consignment/recruit/list` | 招募清单主表 |
| `CONSIGNMENT_RECRUIT_LIST_SKU_API_PATH` | `/scms/consignment/recruit/listsku` | 招募清单SKU明细 |
| `CONSIGNMENT_RECRUIT_PUBLISH_API_PATH` | `/scms/consignment/recruit/publish` | 发布记录 |
| `CONSIGNMENT_RECRUIT_APPLY_API_PATH` | `/scms/consignment/recruit/apply` | 招募申请 |
| `CONSIGNMENT_RECRUIT_ACTION_LOG_API_PATH` | `/scms/consignment/recruit/actionlog` | 动作日志 |

### 11.3 DTO继承结构与标准方法

#### 11.3.1 DTO继承体系

```
BaseReqDTO (com.ux168.platform.infrastructure.common.api)
  ├── {Module}BaseDTO (模块专属，包含业务字段 + 校验注解)
  │     ├── CreateReqDTO (extends {Module}BaseDTO, 业务字段 + createBy/updateBy)
  │     ├── UpdateReqDTO (extends {Module}BaseDTO, 增加 @NotNull id)
  │     └── RespDTO (extends {Module}BaseDTO, 增加框架字段 createBy/createTime/updateBy/updateTime)
  ├── ListReqDTO (extends BaseDTO, 列表/计数查询条件)
  └── PageReqDTO (extends ReqPageDTO, 分页查询条件)

RespPageDTO<RespDTO> (com.ux168.platform.infrastructure.common.api)
  └── PageRespDTO (分页响应，包含 list + page 信息)
```

#### 11.3.2 标准API方法

每个 ServiceApi 接口均包含以下 11 个标准方法：

| HTTP方法 | 路径 | 方法 | 请求体 | 响应 |
|----------|------|------|--------|------|
| POST | /v1/create | create | CreateReqDTO | `ValueDTO<Long>` (ID) |
| POST | /v1/update | update | UpdateReqDTO | `ValueDTO<Boolean>` |
| POST | /v1/delete | delete | @RequestParam id | `ValueDTO<Boolean>` |
| POST | /v1/batchDelete | batchDelete | @RequestParam ids | `ValueDTO<Boolean>` |
| POST | /v1/logicDelete | logicDelete | @RequestParam id | `ValueDTO<Boolean>` |
| POST | /v1/batchLogicDelete | batchLogicDelete | @RequestParam ids | `ValueDTO<Boolean>` |
| GET | /v1/findById | findById | @RequestParam id | RespDTO |
| GET | /v1/findByIds | findByIds | @RequestParam ids | `List<RespDTO>` |
| POST | /v1/count | count | ListReqDTO | `ValueDTO<Long>` |
| POST | /v1/list | list | ListReqDTO | `List<RespDTO>` |
| POST | /v1/page | page | PageReqDTO | PageRespDTO |

### 11.4 ConsignmentRecruitListServiceApi

#### 11.4.1 ServiceApi接口

```java
package com.ux168.pa.service.scms.api.consignment.recruit.list;

import com.ux168.pa.service.scms.api.consignment.recruit.list.dto.request.*;
import com.ux168.pa.service.scms.api.consignment.recruit.list.dto.response.ConsignmentRecruitListPageRespDTO;
import com.ux168.pa.service.scms.api.consignment.recruit.list.dto.response.ConsignmentRecruitListRespDTO;
import com.ux168.pa.service.scms.constants.ScmsServiceApiConstants;
import com.ux168.platform.infrastructure.common.api.CommResponse;
import com.ux168.platform.infrastructure.common.api.ValueDTO;
import com.ux168.platform.infrastructure.common.constants.FeignConstants;
import io.swagger.annotations.ApiImplicitParam;
import io.swagger.annotations.ApiOperation;
import org.springframework.cloud.openfeign.FeignClient;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestParam;

import javax.validation.Valid;
import java.util.Collection;
import java.util.List;

@FeignClient(name = ScmsServiceApiConstants.NAME, contextId = ScmsServiceApiConstants.CONTEXT_ID,
        path = ScmsServiceApiConstants.CONSIGNMENT_RECRUIT_LIST_API_PATH,
        url = FeignConstants.DELEGATE_CONFIG)
public interface ConsignmentRecruitListServiceApi {

    @PostMapping("/v1/create")
    @ApiOperation(value = "创建 招募清单", notes = "创建 招募清单")
    CommResponse<ValueDTO<Long>> create(@Valid @RequestBody ConsignmentRecruitListCreateReqDTO createReqDTO);

    @PostMapping("/v1/update")
    @ApiOperation(value = "更新 招募清单", notes = "更新 招募清单")
    CommResponse<ValueDTO<Boolean>> update(@Valid @RequestBody ConsignmentRecruitListUpdateReqDTO updateReqDTO);

    @PostMapping("/v1/delete")
    @ApiOperation(value = "删除 招募清单", notes = "根据id , 删除 招募清单")
    @ApiImplicitParam(name = "id", value = "编号", required = true)
    CommResponse<ValueDTO<Boolean>> delete(@RequestParam("id") Long id);

    @PostMapping("/v1/batchDelete")
    @ApiOperation(value = "批量删除 招募清单", notes = "根据id列表 , 批量删除 招募清单")
    @ApiImplicitParam(name = "ids", value = "编号列表", required = true, example = "1024,2048")
    CommResponse<ValueDTO<Boolean>> batchDelete(@RequestParam("ids") Collection<Long> ids);

    @PostMapping("/v1/logicDelete")
    @ApiOperation(value = "逻辑删除 招募清单", notes = "根据id , 逻辑删除招募清单")
    @ApiImplicitParam(name = "id", value = "编号", required = true)
    CommResponse<ValueDTO<Boolean>> logicDelete(@RequestParam("id") Long id);

    @PostMapping("/v1/batchLogicDelete")
    @ApiOperation(value = "批量逻辑删除 招募清单", notes = "根据id列表 , 批量逻辑删除招募清单")
    @ApiImplicitParam(name = "ids", value = "编号列表", required = true, example = "1024,2048")
    CommResponse<ValueDTO<Boolean>> batchLogicDelete(@RequestParam("ids") Collection<Long> ids);

    @GetMapping("/v1/findById")
    @ApiOperation(value = "查询 招募清单", notes = "根据id , 查询 招募清单")
    @ApiImplicitParam(name = "id", value = "编号", required = true, example = "1024")
    CommResponse<ConsignmentRecruitListRespDTO> findById(@RequestParam("id") Long id);

    @GetMapping("/v1/findByIds")
    @ApiOperation(value = "批量查询 招募清单", notes = "根据id列表 , 批量查询 招募清单")
    @ApiImplicitParam(name = "ids", value = "编号列表", required = true, example = "1024,2048")
    CommResponse<List<ConsignmentRecruitListRespDTO>> findByIds(@RequestParam("ids") Collection<Long> ids);

    @PostMapping("/v1/count")
    @ApiOperation(value = "查询 招募清单 数据总量", notes = "根据查询条件 , 查询 招募清单 数据总量")
    CommResponse<ValueDTO<Long>> count(@Valid @RequestBody ConsignmentRecruitListListReqDTO listReqDTO);

    @PostMapping("/v1/list")
    @ApiOperation(value = "查询 招募清单", notes = "根据查询条件 , 查询 招募清单")
    CommResponse<List<ConsignmentRecruitListRespDTO>> list(@Valid @RequestBody ConsignmentRecruitListListReqDTO listReqDTO);

    @PostMapping("/v1/page")
    @ApiOperation(value = "查询 招募清单 分页数据", notes = "根据查询条件 , 查询 招募清单 分页数据")
    CommResponse<ConsignmentRecruitListPageRespDTO> page(@Valid @RequestBody ConsignmentRecruitListPageReqDTO pageReqDTO);

    // ===== 模块特有查询方法 =====

    @GetMapping("/v1/findByStatus")
    @ApiOperation(value = "根据状态查询清单列表", notes = "根据清单状态查询列表")
    @ApiImplicitParam(name = "listStatus", value = "清单状态", required = true)
    CommResponse<List<ConsignmentRecruitListRespDTO>> findByStatus(@RequestParam("listStatus") Integer listStatus);

    @GetMapping("/v1/findByStatusIn")
    @ApiOperation(value = "根据多状态查询清单列表", notes = "根据多状态查询清单列表")
    @ApiImplicitParam(name = "statusList", value = "状态列表", required = true, example = "10,20,30")
    CommResponse<List<ConsignmentRecruitListRespDTO>> findByStatusIn(@RequestParam("statusList") Collection<Integer> statusList);

    @GetMapping("/v1/findWaitPublishList")
    @ApiOperation(value = "查询待发布清单", notes = "查询待发布的清单（按组单时间升序）")
    CommResponse<List<ConsignmentRecruitListRespDTO>> findWaitPublishList();

    @PostMapping("/v1/batchUpdateStatus")
    @ApiOperation(value = "批量更新清单状态", notes = "批量更新清单状态")
    CommResponse<ValueDTO<Boolean>> batchUpdateStatus(@Valid @RequestBody ConsignmentRecruitListBatchUpdateStatusReqDTO batchReqDTO);
}
```

#### 11.4.2 BaseDTO

```java
package com.ux168.pa.service.scms.api.consignment.recruit.list.dto;

import com.ux168.platform.infrastructure.common.api.BaseReqDTO;
import io.swagger.annotations.ApiModel;
import io.swagger.annotations.ApiModelProperty;
import lombok.Data;

import javax.validation.constraints.NotNull;
import java.math.BigDecimal;
import java.util.Date;

@Data
@ApiModel(value = "招募清单 基本信息 DTO")
public class ConsignmentRecruitListBaseDTO extends BaseReqDTO {

    @ApiModelProperty(name = "recruitNo", value = "招募清单编号", required = true)
    @NotNull(message = "招募清单编号不能为空") private String recruitNo;

    @ApiModelProperty(name = "factoryId", value = "货源工厂ID", required = true)
    @NotNull(message = "货源工厂ID不能为空") private Long factoryId;

    @ApiModelProperty(name = "factoryName", value = "货源工厂名称") private String factoryName;
    @ApiModelProperty(name = "categoryId", value = "末级分类ID（产品线ID）") private Long categoryId;
    @ApiModelProperty(name = "categoryFullPathId", value = "分类完整路径ID") private String categoryFullPathId;
    @ApiModelProperty(name = "skuCount", value = "清单SKU数量") private Integer skuCount;
    @ApiModelProperty(name = "fileUrl", value = "文件URL") private String fileUrl;
    @ApiModelProperty(name = "estimatedCost", value = "预估成本") private BigDecimal estimatedCost;
    @ApiModelProperty(name = "estimatedMonthSaleQty", value = "预估月销量") private Integer estimatedMonthSaleQty;
    @ApiModelProperty(name = "estimatedMonthSaleAmount", value = "预估月销售额") private BigDecimal estimatedMonthSaleAmount;
    @ApiModelProperty(name = "avgMoq", value = "平均MOQ") private BigDecimal avgMoq;
    @ApiModelProperty(name = "listStatus", value = "清单状态") private Integer listStatus;
    @ApiModelProperty(name = "listType", value = "清单类型") private Integer listType;
    @ApiModelProperty(name = "groupTime", value = "组单完成时间") private Date groupTime;
    @ApiModelProperty(name = "publishBeginTime", value = "发布开始时间") private Date publishBeginTime;
    @ApiModelProperty(name = "applyBeginTime", value = "开放申请时间") private Date applyBeginTime;
    @ApiModelProperty(name = "applyEndTime", value = "申请结束时间") private Date applyEndTime;
    @ApiModelProperty(name = "publishEndTime", value = "发布结束时间") private Date publishEndTime;
    @ApiModelProperty(name = "awardTime", value = "定标时间") private Date awardTime;
    @ApiModelProperty(name = "awardSupplierId", value = "中标供应商ID") private Long awardSupplierId;
    @ApiModelProperty(name = "awardGroupId", value = "中标集团ID") private Long awardGroupId;
    @ApiModelProperty(name = "awardApplyId", value = "中标申请ID") private Long awardApplyId;
    @ApiModelProperty(name = "awardCeBillNo", value = "中标CE单号") private String awardCeBillNo;
    @ApiModelProperty(name = "publishBy", value = "发布人") private String publishBy;
    @ApiModelProperty(name = "auditBy", value = "审核人") private String auditBy;
    @ApiModelProperty(name = "awardBy", value = "定标人") private String awardBy;
    @ApiModelProperty(name = "cancelUserName", value = "取消人") private String cancelUserName;
    @ApiModelProperty(name = "cancelTime", value = "取消时间") private Date cancelTime;
    @ApiModelProperty(name = "cancelType", value = "取消类型") private String cancelType;
    @ApiModelProperty(name = "cancelReason", value = "取消原因") private String cancelReason;
    @ApiModelProperty(name = "remark", value = "备注") private String remark;
    @ApiModelProperty(name = "tenantId", value = "租户ID") private Long tenantId;
    @ApiModelProperty(name = "instanceId", value = "实例ID") private Long instanceId;
    @ApiModelProperty(name = "applicationId", value = "应用ID") private Long applicationId;
}
```

#### 11.4.3 标准DTO模式说明

以下 DTO 遵循统一模式，各模块仅替换模块名，不再依次重复列出：

| DTO类 | 继承 | 特有字段 |
|-------|------|---------|
| `Consignment{Module}CreateReqDTO` | extends `{Module}BaseDTO` | `@NotNull createBy`, `@NotNull updateBy` |
| `Consignment{Module}UpdateReqDTO` | extends `{Module}BaseDTO` | `@NotNull id` |
| `Consignment{Module}ListReqDTO` | extends `{Module}BaseDTO` | 含 BaseDTO 全部字段作为查询条件 |
| `Consignment{Module}PageReqDTO` | extends `ReqPageDTO` | 含 BaseDTO 全部字段作为查询条件 + 分页参数 |
| `Consignment{Module}RespDTO` | extends `{Module}BaseDTO` | `createBy`, `createTime`, `updateBy`, `updateTime` |
| `Consignment{Module}PageRespDTO` | extends `RespPageDTO<RespDTO>` | 无额外字段 |

### 11.5 ConsignmentRecruitListSkuServiceApi

#### 11.5.1 ServiceApi接口

```java
package com.ux168.pa.service.scms.api.consignment.recruit.listsku;

import com.ux168.pa.service.scms.api.consignment.recruit.listsku.dto.request.*;
import com.ux168.pa.service.scms.api.consignment.recruit.listsku.dto.response.ConsignmentRecruitListSkuPageRespDTO;
import com.ux168.pa.service.scms.api.consignment.recruit.listsku.dto.response.ConsignmentRecruitListSkuRespDTO;
import com.ux168.pa.service.scms.constants.ScmsServiceApiConstants;
import com.ux168.platform.infrastructure.common.api.CommResponse;
import com.ux168.platform.infrastructure.common.api.ValueDTO;
import com.ux168.platform.infrastructure.common.constants.FeignConstants;
import io.swagger.annotations.ApiImplicitParam;
import io.swagger.annotations.ApiOperation;
import org.springframework.cloud.openfeign.FeignClient;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestParam;

import javax.validation.Valid;
import java.util.Collection;
import java.util.List;

@FeignClient(name = ScmsServiceApiConstants.NAME, contextId = ScmsServiceApiConstants.CONTEXT_ID,
        path = ScmsServiceApiConstants.CONSIGNMENT_RECRUIT_LIST_SKU_API_PATH,
        url = FeignConstants.DELEGATE_CONFIG)
public interface ConsignmentRecruitListSkuServiceApi {

    // ===== 标准CRUD方法（同11.4.1模式，略） =====
    // 包含 create / update / delete / batchDelete / logicDelete / batchLogicDelete
    //        findById / findByIds / count / list / page 共11个标准方法
    // 此处不再重复列出，详见11.4.1节

    // ===== 模块特有查询方法 =====

    @GetMapping("/v1/findByRecruitId")
    @ApiOperation(value = "根据招募清单ID查询SKU列表", notes = "根据招募清单ID查询SKU列表")
    @ApiImplicitParam(name = "recruitId", value = "招募清单ID", required = true)
    CommResponse<List<ConsignmentRecruitListSkuRespDTO>> findByRecruitId(@RequestParam("recruitId") Long recruitId);

    @GetMapping("/v1/findPoolSkuList")
    @ApiOperation(value = "查询招募池SKU列表", notes = "查询招募池中的SKU列表（recruit_id = 0）")
    CommResponse<List<ConsignmentRecruitListSkuRespDTO>> findPoolSkuList(
            @RequestParam("factoryId") Long factoryId,
            @RequestParam("categoryId") Long categoryId);

    @PostMapping("/v1/batchUpdateRecruitId")
    @ApiOperation(value = "批量更新SKU所属清单", notes = "批量更新SKU所属清单")
    CommResponse<ValueDTO<Boolean>> batchUpdateRecruitId(@Valid @RequestBody ConsignmentRecruitListSkuBatchUpdateReqDTO batchReqDTO);
}
```

#### 11.5.2 BaseDTO

```java
package com.ux168.pa.service.scms.api.consignment.recruit.listsku.dto;

import com.ux168.platform.infrastructure.common.api.BaseReqDTO;
import io.swagger.annotations.ApiModel;
import io.swagger.annotations.ApiModelProperty;
import lombok.Data;

import javax.validation.constraints.NotNull;
import java.math.BigDecimal;
import java.util.Date;

@Data
@ApiModel(value = "招募清单SKU明细 基本信息 DTO")
public class ConsignmentRecruitListSkuBaseDTO extends BaseReqDTO {

    @ApiModelProperty(name = "recruitId", value = "招募清单ID") private Long recruitId;
    @ApiModelProperty(name = "recruitNo", value = "招募清单编号") private String recruitNo;
    @ApiModelProperty(name = "skuId", value = "自营SKU ID", required = true)
    @NotNull(message = "自营SKU ID不能为空") private Long skuId;
    @ApiModelProperty(name = "skuName", value = "SKU名称") private String skuName;
    @ApiModelProperty(name = "categoryId", value = "末级分类ID") private Long categoryId;
    @ApiModelProperty(name = "factoryId", value = "货源工厂ID") private Long factoryId;
    @ApiModelProperty(name = "factoryName", value = "货源工厂名称") private String factoryName;
    @ApiModelProperty(name = "purchaseUrl", value = "采购链接") private String purchaseUrl;
    @ApiModelProperty(name = "productModel", value = "产品型号") private String productModel;
    @ApiModelProperty(name = "vehicleModel", value = "车型") private String vehicleModel;
    @ApiModelProperty(name = "unit", value = "单位") private String unit;
    @ApiModelProperty(name = "packageInfo", value = "包装信息") private String packageInfo;
    @ApiModelProperty(name = "grossWeightG", value = "毛重(g)") private BigDecimal grossWeightG;
    @ApiModelProperty(name = "packageSizeCm", value = "包装尺寸(cm)") private String packageSizeCm;
    @ApiModelProperty(name = "sourceModel", value = "来源型号") private String sourceModel;
    @ApiModelProperty(name = "deliveryDays", value = "交期(天)") private Integer deliveryDays;
    @ApiModelProperty(name = "moq", value = "最小起订量") private Integer moq;
    @ApiModelProperty(name = "costPrice", value = "成本价") private BigDecimal costPrice;
    @ApiModelProperty(name = "saleQty90d", value = "90天销量") private Integer saleQty90d;
    @ApiModelProperty(name = "saleQty30d", value = "30天销量") private Integer saleQty30d;
    @ApiModelProperty(name = "replenishRemind21d", value = "21天补货提醒") private Integer replenishRemind21d;
    @ApiModelProperty(name = "sourceType", value = "来源") private Integer sourceType;
    @ApiModelProperty(name = "skuStatus", value = "SKU状态") private Integer skuStatus;
    @ApiModelProperty(name = "importBatchNo", value = "导入批次号") private String importBatchNo;
    @ApiModelProperty(name = "importUser", value = "导入人") private String importUser;
    @ApiModelProperty(name = "importTime", value = "导入时间") private Date importTime;
    @ApiModelProperty(name = "failReason", value = "失败原因") private String failReason;
    @ApiModelProperty(name = "tenantId", value = "租户ID") private Long tenantId;
    @ApiModelProperty(name = "instanceId", value = "实例ID") private Long instanceId;
    @ApiModelProperty(name = "applicationId", value = "应用ID") private Long applicationId;
}
```

### 11.6 ConsignmentRecruitPublishServiceApi

#### 11.6.1 ServiceApi接口

```java
package com.ux168.pa.service.scms.api.consignment.recruit.publish;

import com.ux168.pa.service.scms.api.consignment.recruit.publish.dto.request.*;
import com.ux168.pa.service.scms.api.consignment.recruit.publish.dto.response.ConsignmentRecruitPublishPageRespDTO;
import com.ux168.pa.service.scms.api.consignment.recruit.publish.dto.response.ConsignmentRecruitPublishRespDTO;
import com.ux168.pa.service.scms.constants.ScmsServiceApiConstants;
import com.ux168.platform.infrastructure.common.api.CommResponse;
import com.ux168.platform.infrastructure.common.api.ValueDTO;
import com.ux168.platform.infrastructure.common.constants.FeignConstants;
import io.swagger.annotations.ApiImplicitParam;
import io.swagger.annotations.ApiOperation;
import org.springframework.cloud.openfeign.FeignClient;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestParam;

import javax.validation.Valid;
import java.util.Collection;
import java.util.List;

@FeignClient(name = ScmsServiceApiConstants.NAME, contextId = ScmsServiceApiConstants.CONTEXT_ID,
        path = ScmsServiceApiConstants.CONSIGNMENT_RECRUIT_PUBLISH_API_PATH,
        url = FeignConstants.DELEGATE_CONFIG)
public interface ConsignmentRecruitPublishServiceApi {

    // ===== 标准CRUD方法（同11.4.1模式，略） =====
    // 包含 create / update / delete / batchDelete / logicDelete / batchLogicDelete
    //        findById / findByIds / count / list / page 共11个标准方法

    // ===== 模块特有查询方法 =====

    @GetMapping("/v1/findByRecruitId")
    @ApiOperation(value = "根据招募清单ID查询发布记录", notes = "根据招募清单ID查询发布记录列表")
    @ApiImplicitParam(name = "recruitId", value = "招募清单ID", required = true)
    CommResponse<List<ConsignmentRecruitPublishRespDTO>> findByRecruitId(@RequestParam("recruitId") Long recruitId);

    @GetMapping("/v1/findLatestByRecruitId")
    @ApiOperation(value = "查询最新发布记录", notes = "查询某清单的最新发布记录")
    @ApiImplicitParam(name = "recruitId", value = "招募清单ID", required = true)
    CommResponse<ConsignmentRecruitPublishRespDTO> findLatestByRecruitId(@RequestParam("recruitId") Long recruitId);
}
```

#### 11.6.2 BaseDTO

```java
package com.ux168.pa.service.scms.api.consignment.recruit.publish.dto;

import com.ux168.platform.infrastructure.common.api.BaseReqDTO;
import io.swagger.annotations.ApiModel;
import io.swagger.annotations.ApiModelProperty;
import lombok.Data;

import javax.validation.constraints.NotNull;
import java.util.Date;

@Data
@ApiModel(value = "发布记录 基本信息 DTO")
public class ConsignmentRecruitPublishBaseDTO extends BaseReqDTO {

    @ApiModelProperty(name = "recruitId", value = "招募清单ID", required = true)
    @NotNull(message = "招募清单ID不能为空") private Long recruitId;

    @ApiModelProperty(name = "recruitNo", value = "招募清单编号") private String recruitNo;
    @ApiModelProperty(name = "publishRound", value = "发布轮次") private Integer publishRound;
    @ApiModelProperty(name = "publishStatus", value = "发布状态") private Integer publishStatus;
    @ApiModelProperty(name = "publishBeginTime", value = "发布开始时间") private Date publishBeginTime;
    @ApiModelProperty(name = "applyBeginTime", value = "开放申请时间") private Date applyBeginTime;
    @ApiModelProperty(name = "applyEndTime", value = "申请结束时间") private Date applyEndTime;
    @ApiModelProperty(name = "publishEndTime", value = "发布结束时间") private Date publishEndTime;
    @ApiModelProperty(name = "publishJobId", value = "发布任务ID") private Long publishJobId;
    @ApiModelProperty(name = "tenantId", value = "租户ID") private Long tenantId;
    @ApiModelProperty(name = "instanceId", value = "实例ID") private Long instanceId;
    @ApiModelProperty(name = "applicationId", value = "应用ID") private Long applicationId;
}
```

### 11.7 ConsignmentRecruitApplyServiceApi

#### 11.7.1 ServiceApi接口

```java
package com.ux168.pa.service.scms.api.consignment.recruit.apply;

import com.ux168.pa.service.scms.api.consignment.recruit.apply.dto.request.*;
import com.ux168.pa.service.scms.api.consignment.recruit.apply.dto.response.ConsignmentRecruitApplyPageRespDTO;
import com.ux168.pa.service.scms.api.consignment.recruit.apply.dto.response.ConsignmentRecruitApplyRespDTO;
import com.ux168.pa.service.scms.constants.ScmsServiceApiConstants;
import com.ux168.platform.infrastructure.common.api.CommResponse;
import com.ux168.platform.infrastructure.common.api.ValueDTO;
import com.ux168.platform.infrastructure.common.constants.FeignConstants;
import io.swagger.annotations.ApiImplicitParam;
import io.swagger.annotations.ApiOperation;
import org.springframework.cloud.openfeign.FeignClient;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestParam;

import javax.validation.Valid;
import java.util.Collection;
import java.util.List;

@FeignClient(name = ScmsServiceApiConstants.NAME, contextId = ScmsServiceApiConstants.CONTEXT_ID,
        path = ScmsServiceApiConstants.CONSIGNMENT_RECRUIT_APPLY_API_PATH,
        url = FeignConstants.DELEGATE_CONFIG)
public interface ConsignmentRecruitApplyServiceApi {

    // ===== 标准CRUD方法（同11.4.1模式，略） =====

    // ===== 模块特有查询方法 =====

    @GetMapping("/v1/findByRecruitId")
    @ApiOperation(value = "根据招募清单ID查询申请列表", notes = "根据招募清单ID查询申请列表")
    @ApiImplicitParam(name = "recruitId", value = "招募清单ID", required = true)
    CommResponse<List<ConsignmentRecruitApplyRespDTO>> findByRecruitId(@RequestParam("recruitId") Long recruitId);

    @GetMapping("/v1/findBySupplierId")
    @ApiOperation(value = "根据寄卖商ID查询申请列表", notes = "根据寄卖商ID查询申请列表")
    @ApiImplicitParam(name = "supplierId", value = "寄卖商ID", required = true)
    CommResponse<List<ConsignmentRecruitApplyRespDTO>> findBySupplierId(@RequestParam("supplierId") Long supplierId);

    @GetMapping("/v1/findActiveByRecruitId")
    @ApiOperation(value = "查询活跃申请列表", notes = "查询清单的活跃申请列表（按最终覆盖率降序）")
    @ApiImplicitParam(name = "recruitId", value = "招募清单ID", required = true)
    CommResponse<List<ConsignmentRecruitApplyRespDTO>> findActiveByRecruitId(@RequestParam("recruitId") Long recruitId);
}
```

#### 11.7.2 BaseDTO

```java
package com.ux168.pa.service.scms.api.consignment.recruit.apply.dto;

import com.ux168.platform.infrastructure.common.api.BaseReqDTO;
import io.swagger.annotations.ApiModel;
import io.swagger.annotations.ApiModelProperty;
import lombok.Data;

import javax.validation.constraints.NotNull;
import java.math.BigDecimal;
import java.util.Date;

@Data
@ApiModel(value = "招募申请 基本信息 DTO")
public class ConsignmentRecruitApplyBaseDTO extends BaseReqDTO {

    @ApiModelProperty(name = "recruitId", value = "招募清单ID", required = true)
    @NotNull(message = "招募清单ID不能为空") private Long recruitId;

    @ApiModelProperty(name = "recruitNo", value = "招募清单编号") private String recruitNo;
    @ApiModelProperty(name = "supplierId", value = "寄卖商ID") private Long supplierId;
    @ApiModelProperty(name = "supplierName", value = "寄卖商名称") private String supplierName;
    @ApiModelProperty(name = "groupId", value = "寄卖商集团ID") private Long groupId;
    @ApiModelProperty(name = "factoryId", value = "货源工厂ID") private Long factoryId;
    @ApiModelProperty(name = "applyStatus", value = "申请状态") private Integer applyStatus;
    @ApiModelProperty(name = "sameSourceFlag", value = "同货源标识") private Integer sameSourceFlag;
    @ApiModelProperty(name = "ceBillNo", value = "CE单号") private String ceBillNo;
    @ApiModelProperty(name = "ceCreateTime", value = "CE开单时间") private Date ceCreateTime;
    @ApiModelProperty(name = "ceSendTime", value = "CE发货时间") private Date ceSendTime;
    @ApiModelProperty(name = "firstQcPassTime", value = "首次质检通过时间") private Date firstQcPassTime;
    @ApiModelProperty(name = "removedType", value = "移除类型") private String removedType;
    @ApiModelProperty(name = "inboundSkuCount", value = "入库SKU数量") private Integer inboundSkuCount;
    @ApiModelProperty(name = "baseCoverageRate", value = "基础覆盖率") private BigDecimal baseCoverageRate;
    @ApiModelProperty(name = "sameSourceWeightRate", value = "同货源权重覆盖率") private BigDecimal sameSourceWeightRate;
    @ApiModelProperty(name = "finalCoverageRate", value = "最终覆盖率") private BigDecimal finalCoverageRate;
    @ApiModelProperty(name = "rankNo", value = "评选排名") private Integer rankNo;
    @ApiModelProperty(name = "awardResult", value = "评选结果") private Integer awardResult;
    @ApiModelProperty(name = "awardReason", value = "评选原因") private String awardReason;
    @ApiModelProperty(name = "cancelType", value = "取消类型") private String cancelType;
    @ApiModelProperty(name = "cancelReason", value = "取消原因") private String cancelReason;
    @ApiModelProperty(name = "tenantId", value = "租户ID") private Long tenantId;
    @ApiModelProperty(name = "instanceId", value = "实例ID") private Long instanceId;
    @ApiModelProperty(name = "applicationId", value = "应用ID") private Long applicationId;
}
```

### 11.8 ConsignmentActionLogServiceApi

#### 11.8.1 ServiceApi接口

```java
package com.ux168.pa.service.scms.api.consignment.recruit.actionlog;

import com.ux168.pa.service.scms.api.consignment.recruit.actionlog.dto.request.*;
import com.ux168.pa.service.scms.api.consignment.recruit.actionlog.dto.response.ConsignmentActionLogPageRespDTO;
import com.ux168.pa.service.scms.api.consignment.recruit.actionlog.dto.response.ConsignmentActionLogRespDTO;
import com.ux168.pa.service.scms.constants.ScmsServiceApiConstants;
import com.ux168.platform.infrastructure.common.api.CommResponse;
import com.ux168.platform.infrastructure.common.api.ValueDTO;
import com.ux168.platform.infrastructure.common.constants.FeignConstants;
import io.swagger.annotations.ApiImplicitParam;
import io.swagger.annotations.ApiOperation;
import org.springframework.cloud.openfeign.FeignClient;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestParam;

import javax.validation.Valid;
import java.util.Collection;
import java.util.List;

@FeignClient(name = ScmsServiceApiConstants.NAME, contextId = ScmsServiceApiConstants.CONTEXT_ID,
        path = ScmsServiceApiConstants.CONSIGNMENT_RECRUIT_ACTION_LOG_API_PATH,
        url = FeignConstants.DELEGATE_CONFIG)
public interface ConsignmentActionLogServiceApi {

    // ===== 标准CRUD方法（同11.4.1模式，略） =====

    // ===== 模块特有查询方法 =====

    @GetMapping("/v1/findByRecruitId")
    @ApiOperation(value = "根据招募清单ID查询日志", notes = "根据招募清单ID查询日志列表")
    @ApiImplicitParam(name = "recruitId", value = "招募清单ID", required = true)
    CommResponse<List<ConsignmentActionLogRespDTO>> findByRecruitId(@RequestParam("recruitId") Long recruitId);

    @GetMapping("/v1/findByApplyId")
    @ApiOperation(value = "根据申请ID查询日志", notes = "根据申请ID查询日志列表")
    @ApiImplicitParam(name = "applyId", value = "申请ID", required = true)
    CommResponse<List<ConsignmentActionLogRespDTO>> findByApplyId(@RequestParam("applyId") Long applyId);
}
```

#### 11.8.2 BaseDTO

```java
package com.ux168.pa.service.scms.api.consignment.recruit.actionlog.dto;

import com.ux168.platform.infrastructure.common.api.BaseReqDTO;
import io.swagger.annotations.ApiModel;
import io.swagger.annotations.ApiModelProperty;
import lombok.Data;

import javax.validation.constraints.NotNull;

@Data
@ApiModel(value = "动作日志 基本信息 DTO")
public class ConsignmentActionLogBaseDTO extends BaseReqDTO {

    @ApiModelProperty(name = "recruitId", value = "招募清单ID", required = true)
    @NotNull(message = "招募清单ID不能为空") private Long recruitId;

    @ApiModelProperty(name = "recruitNo", value = "招募清单编号") private String recruitNo;
    @ApiModelProperty(name = "applyId", value = "申请ID") private Long applyId;
    @ApiModelProperty(name = "supplierId", value = "寄卖商ID") private Long supplierId;
    @ApiModelProperty(name = "action", value = "动作") private String action;
    @ApiModelProperty(name = "beforeStatus", value = "变更前状态") private Integer beforeStatus;
    @ApiModelProperty(name = "afterStatus", value = "变更后状态") private Integer afterStatus;
    @ApiModelProperty(name = "operatorType", value = "操作人类型") private Integer operatorType;
    @ApiModelProperty(name = "operatorId", value = "操作人ID") private String operatorId;
    @ApiModelProperty(name = "operatorName", value = "操作人名称") private String operatorName;
    @ApiModelProperty(name = "content", value = "日志内容") private String content;
    @ApiModelProperty(name = "requestId", value = "请求ID") private String requestId;
    @ApiModelProperty(name = "tenantId", value = "租户ID") private Long tenantId;
    @ApiModelProperty(name = "instanceId", value = "实例ID") private Long instanceId;
    @ApiModelProperty(name = "applicationId", value = "应用ID") private Long applicationId;
}
```

### 11.9 实现注意

1. **DTO层不直接映射PO**: BaseDTO 应仅包含业务需要的字段，不包含 `is_deleted`、`version` 等框架字段（这些由框架自动处理）
2. **`@NotNull` 注解**: 创建时必须提供的字段在 BaseDTO 中加 `@NotNull` 注解，允许为空的字段不加
3. **ListReqDTO vs PageReqDTO**: ListReqDTO 用于 list/count 接口，继承 BaseDTO；PageReqDTO 用于 page 接口，继承 ReqPageDTO
4. **RespDTO 框架字段**: `createBy`、`createTime`、`updateBy`、`updateTime` 在 RespDTO 层补充，不在 BaseDTO 中
5. **请求幂等性**: create 接口返回 `ValueDTO<Long>`（新记录ID），调用方可通过该 ID 判断是否重复创建
6. **Feign 配置**: 所有 ServiceApi 使用 `FeignConstants.DELEGATE_CONFIG` 作为动态 URL，支持 Nacos 配置中心
7. **Swagger 注解规范**: 所有 ServiceApi 方法必须标注 `@ApiOperation`，带 @RequestParam 的方法标注 `@ApiImplicitParam`；所有 DTO 类标注 `@ApiModel`，所有字段标注 `@ApiModelProperty`。命名规则详见 7-3-DTO-Service-Feign.md 第八节
