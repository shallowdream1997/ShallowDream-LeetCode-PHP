-- ====================================================
-- 调整招募清单发布时间窗口，使所有数据在当前可见
-- API条件: publish_begin_time <= now <= publish_end_time
-- 默认状态过滤: [20-招募中, 25-已抢完]
-- 其他状态需传 listStatusList 参数
-- ====================================================

-- 1. 招募清单主表：开始时间改为2天前，结束时间改为5天后
UPDATE scms_consignment_recruit_list
SET publish_begin_time = '2026-06-18 14:00:00.000000',
    apply_begin_time   = '2026-06-18 14:00:00.000000',
    apply_end_time     = '2026-06-25 21:00:00.000000',
    publish_end_time   = '2026-06-25 23:59:59.000000';

-- 2. 发布记录表：与主表保持同步
UPDATE scms_consignment_recruit_publish
SET publish_begin_time = '2026-06-18 14:00:00.000000',
    apply_begin_time   = '2026-06-18 14:00:00.000000',
    apply_end_time     = '2026-06-25 21:00:00.000000',
    publish_end_time   = '2026-06-25 23:59:59.000000';
