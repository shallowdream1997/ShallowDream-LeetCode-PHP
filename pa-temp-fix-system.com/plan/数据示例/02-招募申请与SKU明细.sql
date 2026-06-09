-- ==============================================================
-- 自营转寄卖招募清单关联数据
-- 招募申请(apply) + SKU明细(list_sku)
-- ==============================================================

-- ==============================================================
-- 3. scms_consignment_recruit_apply 招募申请表（招募车）
-- ==============================================================
-- 只对有申请记录的清单插入数据:
--   招募中(20)     → 各2~3个供应商申请（含2586）
--   已抢完(25)     → 10个供应商（已满）
--   分配中(50)     → 3~4个供应商（2586获胜）
--   清单完成(60)   → 2~3个供应商（2586获胜并完成）
--   作废(100)      → 部分有申请记录（已超时/放弃）
-- 注意: recruit_public_id 指向对应的发布记录ID

-- ===================== 供应商ID映射 =====================
-- 2586 = 测试寄卖商A (主账号), groupId=1
-- 2587 = 测试寄卖商B, groupId=1
-- 2588 = 测试寄卖商C, groupId=2
-- 2589 = 测试寄卖商D, groupId=1
-- 2590 = 测试寄卖商E, groupId=3
-- 2591 = 测试寄卖商F, groupId=4
-- 2592 = 测试寄卖商G, groupId=2
-- 2593 = 测试寄卖商H, groupId=5
-- 2594 = 测试寄卖商I, groupId=3
-- 2595 = 测试寄卖商J, groupId=4

-- ==================== 3.1 招募中(20) 申请记录 ====================
-- list 102001: 2586(已加入), 2587(已开CE)
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, same_source_sku_count, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(302001, 102001, 202001, 'SC2606190001', 2586, '测试寄卖商A', 1, 1000, 10, 0, 0, 0, 0.0000, 0.0000, 0.0000, 0, 0, 0, '2586', '2586', '2026-06-19 14:05:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, same_source_sku_count, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(302002, 102001, 202001, 'SC2606190001', 2587, '测试寄卖商B', 1, 1000, 20, 0, 0, 1, 33.3333, 0.0000, 33.3333, 0, 0, 0, '2587', '2587', '2026-06-19 14:10:00.000000', 0, 1);

-- list 102002: 2586(已加入), 2587(已加入), 2588(已开CE)
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, same_source_sku_count, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(302003, 102002, 202002, 'SC2606190002', 2586, '测试寄卖商A', 1, 1000, 10, 0, 0, 0, 0.0000, 0.0000, 0.0000, 0, 0, 0, '2586', '2586', '2026-06-19 14:08:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, same_source_sku_count, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(302004, 102002, 202002, 'SC2606190002', 2587, '测试寄卖商B', 1, 1000, 10, 0, 0, 0, 0.0000, 0.0000, 0.0000, 0, 0, 0, '2587', '2587', '2026-06-19 14:15:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, same_source_sku_count, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(302005, 102002, 202002, 'SC2606190002', 2588, '测试寄卖商C', 2, 1000, 20, 1, 2, 1, 33.3333, 10.0000, 43.3333, 0, 0, 0, '2588', '2588', '2026-06-19 14:20:00.000000', 0, 1);

-- list 102003: 2586(已加入)
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, same_source_sku_count, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(302006, 102003, 202003, 'SC2606190003', 2586, '测试寄卖商A', 1, 1000, 10, 1, 1, 0, 0.0000, 10.0000, 10.0000, 0, 0, 0, '2586', '2586', '2026-06-19 14:30:00.000000', 0, 1);

-- list 102004: 2586(已加入), 2589(已加入)
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, same_source_sku_count, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(302007, 102004, 202004, 'SC2606190004', 2586, '测试寄卖商A', 1, 1000, 10, 0, 0, 0, 0.0000, 0.0000, 0.0000, 0, 0, 0, '2586', '2586', '2026-06-19 15:00:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, same_source_sku_count, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(302008, 102004, 202004, 'SC2606190004', 2589, '测试寄卖商D', 1, 1000, 10, 0, 0, 1, 33.3333, 0.0000, 33.3333, 0, 0, 0, '2589', '2589', '2026-06-19 15:05:00.000000', 0, 1);

-- list 102005: 2586(已加入), 2590(已加入)
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, same_source_sku_count, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(302009, 102005, 202005, 'SC2606190005', 2586, '测试寄卖商A', 1, 1000, 10, 0, 0, 0, 0.0000, 0.0000, 0.0000, 0, 0, 0, '2586', '2586', '2026-06-19 15:30:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, same_source_sku_count, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(302010, 102005, 202005, 'SC2606190005', 2590, '测试寄卖商E', 3, 1000, 10, 0, 0, 0, 0.0000, 0.0000, 0.0000, 0, 0, 0, '2590', '2590', '2026-06-19 15:35:00.000000', 0, 1);

-- ==================== 3.2 已抢完(25) 申请记录 ====================
-- 每个list 10个供应商满池，2586是其中一个
-- list 102501: 2586~2595 全部加入
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, create_by, create_time, is_deleted, version)
VALUES
(312501, 102501, 202501, 'SC2606160001', 2586, '测试寄卖商A', 1, 1000, 10, 0, 0, 0.0000, 0.0000, 0.0000, '2586', '2026-06-16 14:02:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, create_by, create_time, is_deleted, version)
VALUES
(312502, 102501, 202501, 'SC2606160001', 2587, '测试寄卖商B', 1, 1000, 10, 0, 0, 0.0000, 0.0000, 0.0000, '2587', '2026-06-16 14:03:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, create_by, create_time, is_deleted, version)
VALUES
(312503, 102501, 202501, 'SC2606160001', 2588, '测试寄卖商C', 2, 1000, 10, 0, 0, 0.0000, 0.0000, 0.0000, '2588', '2026-06-16 14:05:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, create_by, create_time, is_deleted, version)
VALUES
(312504, 102501, 202501, 'SC2606160001', 2589, '测试寄卖商D', 1, 1000, 10, 0, 0, 0.0000, 0.0000, 0.0000, '2589', '2026-06-16 14:06:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, create_by, create_time, is_deleted, version)
VALUES
(312505, 102501, 202501, 'SC2606160001', 2590, '测试寄卖商E', 3, 1000, 10, 0, 0, 0.0000, 0.0000, 0.0000, '2590', '2026-06-16 14:08:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, create_by, create_time, is_deleted, version)
VALUES
(312506, 102501, 202501, 'SC2606160001', 2591, '测试寄卖商F', 4, 1000, 10, 0, 0, 0.0000, 0.0000, 0.0000, '2591', '2026-06-16 14:10:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, create_by, create_time, is_deleted, version)
VALUES
(312507, 102501, 202501, 'SC2606160001', 2592, '测试寄卖商G', 2, 1000, 10, 0, 0, 0.0000, 0.0000, 0.0000, '2592', '2026-06-16 14:12:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, create_by, create_time, is_deleted, version)
VALUES
(312508, 102501, 202501, 'SC2606160001', 2593, '测试寄卖商H', 5, 1000, 10, 0, 0, 0.0000, 0.0000, 0.0000, '2593', '2026-06-16 14:15:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, create_by, create_time, is_deleted, version)
VALUES
(312509, 102501, 202501, 'SC2606160001', 2594, '测试寄卖商I', 3, 1000, 10, 0, 0, 0.0000, 0.0000, 0.0000, '2594', '2026-06-16 14:18:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, create_by, create_time, is_deleted, version)
VALUES
(312510, 102501, 202501, 'SC2606160001', 2595, '测试寄卖商J', 4, 1000, 20, 0, 2, 66.6667, 0.0000, 66.6667, '2595', '2026-06-16 14:20:00.000000', 0, 1);

-- list 102502: 2586~2595
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, create_by, create_time, is_deleted, version)
VALUES
(312511, 102502, 202502, 'SC2606160002', 2586, '测试寄卖商A', 1, 1000, 10, 0, 0, 0.0000, 0.0000, 0.0000, '2586', '2026-06-16 14:01:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, create_by, create_time, is_deleted, version)
VALUES
(312512, 102502, 202502, 'SC2606160002', 2587, '测试寄卖商B', 1, 1000, 10, 0, 0, 0.0000, 0.0000, 0.0000, '2587', '2026-06-16 14:02:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, create_by, create_time, is_deleted, version)
VALUES
(312513, 102502, 202502, 'SC2606160002', 2588, '测试寄卖商C', 2, 1000, 10, 0, 0, 0.0000, 0.0000, 0.0000, '2588', '2026-06-16 14:03:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, create_by, create_time, is_deleted, version)
VALUES
(312514, 102502, 202502, 'SC2606160002', 2589, '测试寄卖商D', 1, 1000, 10, 0, 0, 0.0000, 0.0000, 0.0000, '2589', '2026-06-16 14:04:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, create_by, create_time, is_deleted, version)
VALUES
(312515, 102502, 202502, 'SC2606160002', 2590, '测试寄卖商E', 3, 1000, 10, 0, 0, 0.0000, 0.0000, 0.0000, '2590', '2026-06-16 14:05:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, create_by, create_time, is_deleted, version)
VALUES
(312516, 102502, 202502, 'SC2606160002', 2591, '测试寄卖商F', 4, 1000, 10, 0, 0, 0.0000, 0.0000, 0.0000, '2591', '2026-06-16 14:06:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, create_by, create_time, is_deleted, version)
VALUES
(312517, 102502, 202502, 'SC2606160002', 2592, '测试寄卖商G', 2, 1000, 10, 0, 0, 0.0000, 0.0000, 0.0000, '2592', '2026-06-16 14:07:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, create_by, create_time, is_deleted, version)
VALUES
(312518, 102502, 202502, 'SC2606160002', 2593, '测试寄卖商H', 5, 1000, 10, 0, 0, 0.0000, 0.0000, 0.0000, '2593', '2026-06-16 14:08:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, create_by, create_time, is_deleted, version)
VALUES
(312519, 102502, 202502, 'SC2606160002', 2594, '测试寄卖商I', 3, 1000, 10, 0, 0, 0.0000, 0.0000, 0.0000, '2594', '2026-06-16 14:09:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, create_by, create_time, is_deleted, version)
VALUES
(312520, 102502, 202502, 'SC2606160002', 2595, '测试寄卖商J', 4, 1000, 20, 0, 1, 33.3333, 0.0000, 33.3333, '2595', '2026-06-16 14:10:00.000000', 0, 1);

-- list 102503~102505: 仅2586参与竞争
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, create_by, create_time, is_deleted, version)
VALUES
(312521, 102503, 202503, 'SC2606160003', 2586, '测试寄卖商A', 1, 1000, 10, 0, 0, 0.0000, 0.0000, 0.0000, '2586', '2026-06-16 14:05:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, create_by, create_time, is_deleted, version)
VALUES
(312522, 102504, 202504, 'SC2606160004', 2586, '测试寄卖商A', 1, 1000, 10, 0, 0, 0.0000, 0.0000, 0.0000, '2586', '2026-06-16 14:08:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, create_by, create_time, is_deleted, version)
VALUES
(312523, 102505, 202505, 'SC2606160005', 2586, '测试寄卖商A', 1, 1000, 10, 0, 0, 0.0000, 0.0000, 0.0000, '2586', '2026-06-16 14:10:00.000000', 0, 1);

-- ==================== 3.3 分配中(50) 申请记录 ====================
-- 2586获胜，有评选结果和CE单
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, ce_bill_no, ce_create_time, ce_status, rank_no, award_result, award_reason, create_by, create_time, is_deleted, version)
VALUES
(305001, 105001, 205001, 'SC2606090001', 2586, '测试寄卖商A', 1, 1000, 40, 0, 2, 66.6667, 0.0000, 66.6667, 'CE20260610001', '2026-06-10 10:00:00.000000', 'CE_CREATED', 1, 1, 'direct', '2586', '2026-06-09 14:05:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, create_by, create_time, is_deleted, version)
VALUES
(305002, 105001, 205001, 'SC2606090001', 2587, '测试寄卖商B', 1, 1000, 30, 0, 1, 33.3333, 0.0000, 33.3333, '2587', '2026-06-09 14:10:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, create_by, create_time, is_deleted, version)
VALUES
(305003, 105001, 205001, 'SC2606090001', 2590, '测试寄卖商E', 3, 1000, 30, 0, 1, 33.3333, 0.0000, 33.3333, '2590', '2026-06-09 14:15:00.000000', 0, 1);

-- list 105002: 2586获胜
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, ce_bill_no, ce_create_time, ce_status, rank_no, award_result, award_reason, create_by, create_time, is_deleted, version)
VALUES
(305004, 105002, 205002, 'SC2606090002', 2586, '测试寄卖商A', 1, 1000, 40, 0, 2, 66.6667, 0.0000, 66.6667, 'CE20260610002', '2026-06-10 10:15:00.000000', 'CE_CREATED', 1, 1, 'highest', '2586', '2026-06-09 14:08:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, create_by, create_time, is_deleted, version)
VALUES
(305005, 105002, 205002, 'SC2606090002', 2589, '测试寄卖商D', 1, 1000, 30, 0, 1, 33.3333, 0.0000, 33.3333, '2589', '2026-06-09 14:12:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, create_by, create_time, is_deleted, version)
VALUES
(305006, 105002, 205002, 'SC2606090002', 2591, '测试寄卖商F', 4, 1000, 30, 0, 1, 33.3333, 0.0000, 33.3333, '2591', '2026-06-09 14:18:00.000000', 0, 1);

-- list 105003~105005: 2586获胜，简化
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, ce_bill_no, ce_create_time, ce_status, rank_no, award_result, award_reason, create_by, create_time, is_deleted, version)
VALUES
(305007, 105003, 205003, 'SC2606090003', 2586, '测试寄卖商A', 1, 1000, 40, 0, 3, 100.0000, 0.0000, 100.0000, 'CE20260610003', '2026-06-10 10:30:00.000000', 'CE_CREATED', 1, 1, 'direct', '2586', '2026-06-09 14:20:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, ce_bill_no, ce_create_time, ce_status, rank_no, award_result, award_reason, create_by, create_time, is_deleted, version)
VALUES
(305008, 105004, 205004, 'SC2606090004', 2586, '测试寄卖商A', 1, 1000, 40, 0, 3, 100.0000, 0.0000, 100.0000, 'CE20260610004', '2026-06-10 10:45:00.000000', 'CE_CREATED', 1, 1, 'direct', '2586', '2026-06-09 14:25:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, ce_bill_no, ce_create_time, ce_status, rank_no, award_result, award_reason, create_by, create_time, is_deleted, version)
VALUES
(305009, 105005, 205005, 'SC2606090005', 2586, '测试寄卖商A', 1, 1000, 40, 0, 3, 100.0000, 0.0000, 100.0000, 'CE20260610005', '2026-06-10 11:00:00.000000', 'CE_CREATED', 1, 1, 'direct', '2586', '2026-06-09 14:30:00.000000', 0, 1);

-- ==================== 3.4 清单完成(60) 申请记录 ====================
-- 已完成全部流程
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, ce_bill_no, ce_create_time, ce_send_time, ce_status, first_qc_pass_time, rank_no, award_result, award_reason, create_by, create_time, is_deleted, version)
VALUES
(306001, 106001, 206001, 'SC2606020001', 2586, '测试寄卖商A', 1, 1000, 50, 0, 3, 100.0000, 0.0000, 100.0000, 'CE20260603001', '2026-06-03 10:00:00.000000', '2026-06-04 14:00:00.000000', 'QC_PASSED', '2026-06-06 10:30:00.000000', 1, 1, 'direct', '2586', '2026-06-02 14:05:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, create_by, create_time, is_deleted, version)
VALUES
(306002, 106001, 206001, 'SC2606020001', 2587, '测试寄卖商B', 1, 1000, 90, 0, 1, 33.3333, 0.0000, 33.3333, '2587', '2026-06-02 14:10:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, ce_bill_no, ce_create_time, ce_send_time, ce_status, first_qc_pass_time, rank_no, award_result, award_reason, create_by, create_time, is_deleted, version)
VALUES
(306003, 106002, 206002, 'SC2606020002', 2586, '测试寄卖商A', 1, 1000, 50, 0, 3, 100.0000, 0.0000, 100.0000, 'CE20260603002', '2026-06-03 10:15:00.000000', '2026-06-04 15:00:00.000000', 'QC_PASSED', '2026-06-06 11:00:00.000000', 1, 1, 'direct', '2586', '2026-06-02 14:08:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, create_by, create_time, is_deleted, version)
VALUES
(306004, 106002, 206002, 'SC2606020002', 2589, '测试寄卖商D', 1, 1000, 90, 0, 1, 33.3333, 0.0000, 33.3333, '2589', '2026-06-02 14:15:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, create_by, create_time, is_deleted, version)
VALUES
(306005, 106002, 206002, 'SC2606020002', 2590, '测试寄卖商E', 3, 1000, 90, 0, 1, 33.3333, 0.0000, 33.3333, '2590', '2026-06-02 14:18:00.000000', 0, 1);

-- list 106003~106005: 2586获胜并完成
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, ce_bill_no, ce_create_time, ce_send_time, ce_status, first_qc_pass_time, rank_no, award_result, award_reason, create_by, create_time, is_deleted, version)
VALUES
(306006, 106003, 206003, 'SC2606020003', 2586, '测试寄卖商A', 1, 1000, 50, 0, 3, 100.0000, 0.0000, 100.0000, 'CE20260603003', '2026-06-03 10:30:00.000000', '2026-06-04 16:00:00.000000', 'QC_PASSED', '2026-06-06 14:00:00.000000', 1, 1, 'direct', '2586', '2026-06-02 14:20:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, ce_bill_no, ce_create_time, ce_send_time, ce_status, first_qc_pass_time, rank_no, award_result, award_reason, create_by, create_time, is_deleted, version)
VALUES
(306007, 106004, 206004, 'SC2606020004', 2586, '测试寄卖商A', 1, 1000, 50, 0, 3, 100.0000, 0.0000, 100.0000, 'CE20260603004', '2026-06-03 10:45:00.000000', '2026-06-05 09:00:00.000000', 'QC_PASSED', '2026-06-07 10:00:00.000000', 1, 1, 'direct', '2586', '2026-06-02 14:25:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, ce_bill_no, ce_create_time, ce_send_time, ce_status, first_qc_pass_time, rank_no, award_result, award_reason, create_by, create_time, is_deleted, version)
VALUES
(306008, 106005, 206005, 'SC2606020005', 2586, '测试寄卖商A', 1, 1000, 50, 0, 3, 100.0000, 0.0000, 100.0000, 'CE20260603005', '2026-06-03 11:00:00.000000', '2026-06-05 10:00:00.000000', 'QC_PASSED', '2026-06-07 14:00:00.000000', 1, 1, 'direct', '2586', '2026-06-02 14:30:00.000000', 0, 1);

-- ==================== 3.5 作废(100) 申请记录 ====================
-- 部分作废清单有申请记录（已超时/放弃）
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, create_by, create_time, is_deleted, version)
VALUES
(310001, 110002, 210002, 'SC2606010002', 2586, '测试寄卖商A', 1, 1000, 90, 0, 0, 0.0000, 0.0000, 0.0000, '2586', '2026-06-02 14:05:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, create_by, create_time, is_deleted, version)
VALUES
(310002, 110002, 210002, 'SC2606010002', 2587, '测试寄卖商B', 1, 1000, 90, 0, 0, 0.0000, 0.0000, 0.0000, '2587', '2026-06-02 14:10:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, create_by, create_time, is_deleted, version)
VALUES
(310003, 110003, 210003, 'SC2606010003', 2586, '测试寄卖商A', 1, 1000, 100, 0, 0, 0.0000, 0.0000, 0.0000, '2586', '2026-06-02 14:15:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, create_by, create_time, is_deleted, version)
VALUES
(310004, 110004, 210004, 'SC2606010004', 2586, '测试寄卖商A', 1, 1000, 40, 0, 2, 66.6667, 0.0000, 66.6667, '2586', '2026-06-02 14:20:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, create_by, create_time, is_deleted, version)
VALUES
(310005, 110004, 210004, 'SC2606010004', 2587, '测试寄卖商B', 1, 1000, 90, 0, 0, 0.0000, 0.0000, 0.0000, '2587', '2026-06-02 14:22:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, create_by, create_time, is_deleted, version)
VALUES
(310006, 110004, 210004, 'SC2606010004', 2588, '测试寄卖商C', 2, 1000, 90, 0, 0, 0.0000, 0.0000, 0.0000, '2588', '2026-06-02 14:25:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_apply (id, recruit_id, recruit_public_id, recruit_no, supplier_id, supplier_name, group_id, factory_id, apply_status, same_source_flag, inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate, create_by, create_time, is_deleted, version)
VALUES
(310007, 110005, 210005, 'SC2606010005', 2586, '测试寄卖商A', 1, 1000, 100, 0, 0, 0.0000, 0.0000, 0.0000, '2586', '2026-06-02 14:30:00.000000', 0, 1);

-- ==============================================================
-- 4. scms_consignment_recruit_list_sku 招募清单SKU明细
-- ==============================================================
-- 每个清单3条SKU，共35*3=105条
-- SKU基础数据池:
--   5001-前刹车片套装,5002-后刹车片套装,5003-机油滤清器,5004-空气滤清器,
--   5005-空调滤清器,5006-火花塞,5007-雨刮片,5008-前照灯灯泡,
--   5009-发动机皮带,5010-水泵总成

-- ===== 待发布(10) SKU =====
INSERT INTO scms_consignment_recruit_list_sku (id, recruit_id, recruit_no, sku_id, sku_name, category_id, factory_id, factory_name, source_url, product_model, vehicle_model, sales_quantity, unit, package_info, gross_weight_g, package_size_cm, source_model, delivery_days, moq, cost_price, sale_qty_90d, sale_qty_30d, source_type, sku_status, create_by, create_time, is_deleted, version)
VALUES
(401001, 101001, 'SC2606260001', 5001, '前刹车片套装', 26240, 1000, '测试工厂A', 'https://example.com/sku/5001', 'BP-2201', '丰田卡罗拉/雷凌', '1', 'Set', '刹车片*4', 850.0000, '35*25*8', 'BP-2201-T', 15, 10, 85.0000, 120, 45, 1, 20, 'admin', '2026-06-25 16:30:00.000000', 0, 1),
(401002, 101001, 'SC2606260001', 5002, '后刹车片套装', 26240, 1000, '测试工厂A', 'https://example.com/sku/5002', 'BP-2202', '丰田卡罗拉/雷凌', '1', 'Set', '刹车片*4', 780.0000, '35*25*8', 'BP-2202-T', 15, 10, 72.0000, 95, 32, 1, 20, 'admin', '2026-06-25 16:30:00.000000', 0, 1),
(401003, 101001, 'SC2606260001', 5003, '机油滤清器', 26240, 1000, '测试工厂A', 'https://example.com/sku/5003', 'OF-3101', '通用', '1', 'PCS', '滤清器*1', 250.0000, '20*15*15', 'OF-3101-T', 10, 50, 15.0000, 500, 180, 1, 20, 'admin', '2026-06-25 16:30:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list_sku (id, recruit_id, recruit_no, sku_id, sku_name, category_id, factory_id, factory_name, source_url, product_model, vehicle_model, sales_quantity, unit, package_info, gross_weight_g, package_size_cm, source_model, delivery_days, moq, cost_price, sale_qty_90d, sale_qty_30d, source_type, sku_status, create_by, create_time, is_deleted, version)
VALUES
(401004, 101002, 'SC2606260002', 5004, '空气滤清器', 26240, 1000, '测试工厂A', 'https://example.com/sku/5004', 'AF-4101', '本田思域', '1', 'PCS', '滤清器*1', 300.0000, '28*22*5', 'AF-4101-T', 10, 20, 25.0000, 300, 110, 1, 20, 'admin', '2026-06-25 17:00:00.000000', 0, 1),
(401005, 101002, 'SC2606260002', 5005, '空调滤清器', 26240, 1000, '测试工厂A', 'https://example.com/sku/5005', 'CF-5101', '通用', '1', 'PCS', '滤清器*1', 280.0000, '25*20*4', 'CF-5101-T', 10, 20, 22.0000, 280, 95, 1, 20, 'admin', '2026-06-25 17:00:00.000000', 0, 1),
(401006, 101002, 'SC2606260002', 5006, '火花塞(4支装)', 26240, 1000, '测试工厂A', 'https://example.com/sku/5006', 'SP-6101', '大众朗逸', '4', 'Set', '火花塞*4', 350.0000, '15*10*5', 'SP-6101-T', 12, 20, 45.0000, 200, 75, 1, 20, 'admin', '2026-06-25 17:00:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list_sku (id, recruit_id, recruit_no, sku_id, sku_name, category_id, factory_id, factory_name, source_url, product_model, vehicle_model, sales_quantity, unit, package_info, gross_weight_g, package_size_cm, source_model, delivery_days, moq, cost_price, sale_qty_90d, sale_qty_30d, source_type, sku_status, create_by, create_time, is_deleted, version)
VALUES
(401007, 101003, 'SC2606260003', 5007, '雨刮片(对装)', 26240, 1000, '测试工厂A', 'https://example.com/sku/5007', 'WP-7101', '通用接口', '2', 'Set', '雨刮片*2', 400.0000, '65*8*3', 'WP-7101-T', 12, 30, 35.0000, 350, 120, 1, 20, 'admin', '2026-06-25 17:30:00.000000', 0, 1),
(401008, 101003, 'SC2606260003', 5008, '前照灯灯泡', 26240, 1000, '测试工厂A', 'https://example.com/sku/5008', 'HL-8101', 'H7接口', '2', 'PCS', '灯泡*2', 150.0000, '12*8*8', 'HL-8101-T', 10, 20, 28.0000, 180, 60, 1, 20, 'admin', '2026-06-25 17:30:00.000000', 0, 1),
(401009, 101003, 'SC2606260003', 5009, '发动机皮带', 26240, 1000, '测试工厂A', 'https://example.com/sku/5009', 'EB-9101', '日产轩逸', '1', 'PCS', '皮带*1', 320.0000, '30*20*3', 'EB-9101-T', 14, 10, 55.0000, 150, 50, 1, 20, 'admin', '2026-06-25 17:30:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list_sku (id, recruit_id, recruit_no, sku_id, sku_name, category_id, factory_id, factory_name, source_url, product_model, vehicle_model, sales_quantity, unit, package_info, gross_weight_g, package_size_cm, source_model, delivery_days, moq, cost_price, sale_qty_90d, sale_qty_30d, source_type, sku_status, create_by, create_time, is_deleted, version)
VALUES
(401010, 101004, 'SC2606260004', 5010, '水泵总成', 26240, 1000, '测试工厂A', 'https://example.com/sku/5010', 'WP-10101', '宝马3系', '1', 'PCS', '水泵*1', 1200.0000, '25*20*18', 'WP-10101-T', 20, 5, 120.0000, 80, 28, 1, 20, 'admin', '2026-06-25 18:00:00.000000', 0, 1),
(401011, 101004, 'SC2606260004', 5001, '前刹车片套装', 26240, 1000, '测试工厂A', 'https://example.com/sku/5001', 'BP-2201', '丰田卡罗拉/雷凌', '1', 'Set', '刹车片*4', 850.0000, '35*25*8', 'BP-2201-T', 15, 10, 85.0000, 120, 45, 1, 20, 'admin', '2026-06-25 18:00:00.000000', 0, 1),
(401012, 101004, 'SC2606260004', 5004, '空气滤清器', 26240, 1000, '测试工厂A', 'https://example.com/sku/5004', 'AF-4101', '本田思域', '1', 'PCS', '滤清器*1', 300.0000, '28*22*5', 'AF-4101-T', 10, 20, 25.0000, 300, 110, 1, 20, 'admin', '2026-06-25 18:00:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list_sku (id, recruit_id, recruit_no, sku_id, sku_name, category_id, factory_id, factory_name, source_url, product_model, vehicle_model, sales_quantity, unit, package_info, gross_weight_g, package_size_cm, source_model, delivery_days, moq, cost_price, sale_qty_90d, sale_qty_30d, source_type, sku_status, create_by, create_time, is_deleted, version)
VALUES
(401013, 101005, 'SC2606260005', 5002, '后刹车片套装', 26240, 1000, '测试工厂A', 'https://example.com/sku/5002', 'BP-2202', '丰田卡罗拉/雷凌', '1', 'Set', '刹车片*4', 780.0000, '35*25*8', 'BP-2202-T', 15, 10, 72.0000, 95, 32, 1, 20, 'admin', '2026-06-25 18:30:00.000000', 0, 1),
(401014, 101005, 'SC2606260005', 5005, '空调滤清器', 26240, 1000, '测试工厂A', 'https://example.com/sku/5005', 'CF-5101', '通用', '1', 'PCS', '滤清器*1', 280.0000, '25*20*4', 'CF-5101-T', 10, 20, 22.0000, 280, 95, 1, 20, 'admin', '2026-06-25 18:30:00.000000', 0, 1),
(401015, 101005, 'SC2606260005', 5007, '雨刮片(对装)', 26240, 1000, '测试工厂A', 'https://example.com/sku/5007', 'WP-7101', '通用接口', '2', 'Set', '雨刮片*2', 400.0000, '65*8*3', 'WP-7101-T', 12, 30, 35.0000, 350, 120, 1, 20, 'admin', '2026-06-25 18:30:00.000000', 0, 1);

-- ===== 招募中(20) SKU =====
INSERT INTO scms_consignment_recruit_list_sku (id, recruit_id, recruit_no, sku_id, sku_name, category_id, factory_id, factory_name, source_url, product_model, vehicle_model, sales_quantity, unit, package_info, gross_weight_g, package_size_cm, source_model, delivery_days, moq, cost_price, sale_qty_90d, sale_qty_30d, source_type, sku_status, create_by, create_time, is_deleted, version)
VALUES
(402001, 102001, 'SC2606190001', 5003, '机油滤清器', 26240, 1000, '测试工厂A', 'https://example.com/sku/5003', 'OF-3101', '通用', '1', 'PCS', '滤清器*1', 250.0000, '20*15*15', 'OF-3101-T', 10, 50, 15.0000, 500, 180, 1, 30, 'admin', '2026-06-18 16:00:00.000000', 0, 1),
(402002, 102001, 'SC2606190001', 5006, '火花塞(4支装)', 26240, 1000, '测试工厂A', 'https://example.com/sku/5006', 'SP-6101', '大众朗逸', '4', 'Set', '火花塞*4', 350.0000, '15*10*5', 'SP-6101-T', 12, 20, 45.0000, 200, 75, 1, 30, 'admin', '2026-06-18 16:00:00.000000', 0, 1),
(402003, 102001, 'SC2606190001', 5008, '前照灯灯泡', 26240, 1000, '测试工厂A', 'https://example.com/sku/5008', 'HL-8101', 'H7接口', '2', 'PCS', '灯泡*2', 150.0000, '12*8*8', 'HL-8101-T', 10, 20, 28.0000, 180, 60, 1, 30, 'admin', '2026-06-18 16:00:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list_sku (id, recruit_id, recruit_no, sku_id, sku_name, category_id, factory_id, factory_name, source_url, product_model, vehicle_model, sales_quantity, unit, package_info, gross_weight_g, package_size_cm, source_model, delivery_days, moq, cost_price, sale_qty_90d, sale_qty_30d, source_type, sku_status, create_by, create_time, is_deleted, version)
VALUES
(402004, 102002, 'SC2606190002', 5009, '发动机皮带', 26240, 1000, '测试工厂A', 'https://example.com/sku/5009', 'EB-9101', '日产轩逸', '1', 'PCS', '皮带*1', 320.0000, '30*20*3', 'EB-9101-T', 14, 10, 55.0000, 150, 50, 1, 30, 'admin', '2026-06-18 16:30:00.000000', 0, 1),
(402005, 102002, 'SC2606190002', 5010, '水泵总成', 26240, 1000, '测试工厂A', 'https://example.com/sku/5010', 'WP-10101', '宝马3系', '1', 'PCS', '水泵*1', 1200.0000, '25*20*18', 'WP-10101-T', 20, 5, 120.0000, 80, 28, 1, 30, 'admin', '2026-06-18 16:30:00.000000', 0, 1),
(402006, 102002, 'SC2606190002', 5001, '前刹车片套装', 26240, 1000, '测试工厂A', 'https://example.com/sku/5001', 'BP-2201', '丰田卡罗拉/雷凌', '1', 'Set', '刹车片*4', 850.0000, '35*25*8', 'BP-2201-T', 15, 10, 85.0000, 120, 45, 1, 30, 'admin', '2026-06-18 16:30:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list_sku (id, recruit_id, recruit_no, sku_id, sku_name, category_id, factory_id, factory_name, source_url, product_model, vehicle_model, sales_quantity, unit, package_info, gross_weight_g, package_size_cm, source_model, delivery_days, moq, cost_price, sale_qty_90d, sale_qty_30d, source_type, sku_status, create_by, create_time, is_deleted, version)
VALUES
(402007, 102003, 'SC2606190003', 5004, '空气滤清器', 26240, 1000, '测试工厂A', 'https://example.com/sku/5004', 'AF-4101', '本田思域', '1', 'PCS', '滤清器*1', 300.0000, '28*22*5', 'AF-4101-T', 10, 20, 25.0000, 300, 110, 1, 30, 'admin', '2026-06-18 17:00:00.000000', 0, 1),
(402008, 102003, 'SC2606190003', 5007, '雨刮片(对装)', 26240, 1000, '测试工厂A', 'https://example.com/sku/5007', 'WP-7101', '通用接口', '2', 'Set', '雨刮片*2', 400.0000, '65*8*3', 'WP-7101-T', 12, 30, 35.0000, 350, 120, 1, 30, 'admin', '2026-06-18 17:00:00.000000', 0, 1),
(402009, 102003, 'SC2606190003', 5009, '发动机皮带', 26240, 1000, '测试工厂A', 'https://example.com/sku/5009', 'EB-9101', '日产轩逸', '1', 'PCS', '皮带*1', 320.0000, '30*20*3', 'EB-9101-T', 14, 10, 55.0000, 150, 50, 1, 30, 'admin', '2026-06-18 17:00:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list_sku (id, recruit_id, recruit_no, sku_id, sku_name, category_id, factory_id, factory_name, source_url, product_model, vehicle_model, sales_quantity, unit, package_info, gross_weight_g, package_size_cm, source_model, delivery_days, moq, cost_price, sale_qty_90d, sale_qty_30d, source_type, sku_status, create_by, create_time, is_deleted, version)
VALUES
(402010, 102004, 'SC2606190004', 5002, '后刹车片套装', 26240, 1000, '测试工厂A', 'https://example.com/sku/5002', 'BP-2202', '丰田卡罗拉/雷凌', '1', 'Set', '刹车片*4', 780.0000, '35*25*8', 'BP-2202-T', 15, 10, 72.0000, 95, 32, 1, 30, 'admin', '2026-06-18 17:30:00.000000', 0, 1),
(402011, 102004, 'SC2606190004', 5006, '火花塞(4支装)', 26240, 1000, '测试工厂A', 'https://example.com/sku/5006', 'SP-6101', '大众朗逸', '4', 'Set', '火花塞*4', 350.0000, '15*10*5', 'SP-6101-T', 12, 20, 45.0000, 200, 75, 1, 30, 'admin', '2026-06-18 17:30:00.000000', 0, 1),
(402012, 102004, 'SC2606190004', 5010, '水泵总成', 26240, 1000, '测试工厂A', 'https://example.com/sku/5010', 'WP-10101', '宝马3系', '1', 'PCS', '水泵*1', 1200.0000, '25*20*18', 'WP-10101-T', 20, 5, 120.0000, 80, 28, 1, 30, 'admin', '2026-06-18 17:30:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list_sku (id, recruit_id, recruit_no, sku_id, sku_name, category_id, factory_id, factory_name, source_url, product_model, vehicle_model, sales_quantity, unit, package_info, gross_weight_g, package_size_cm, source_model, delivery_days, moq, cost_price, sale_qty_90d, sale_qty_30d, source_type, sku_status, create_by, create_time, is_deleted, version)
VALUES
(402013, 102005, 'SC2606190005', 5001, '前刹车片套装', 26240, 1000, '测试工厂A', 'https://example.com/sku/5001', 'BP-2201', '丰田卡罗拉/雷凌', '1', 'Set', '刹车片*4', 850.0000, '35*25*8', 'BP-2201-T', 15, 10, 85.0000, 120, 45, 1, 30, 'admin', '2026-06-18 18:00:00.000000', 0, 1),
(402014, 102005, 'SC2606190005', 5003, '机油滤清器', 26240, 1000, '测试工厂A', 'https://example.com/sku/5003', 'OF-3101', '通用', '1', 'PCS', '滤清器*1', 250.0000, '20*15*15', 'OF-3101-T', 10, 50, 15.0000, 500, 180, 1, 30, 'admin', '2026-06-18 18:00:00.000000', 0, 1),
(402015, 102005, 'SC2606190005', 5005, '空调滤清器', 26240, 1000, '测试工厂A', 'https://example.com/sku/5005', 'CF-5101', '通用', '1', 'PCS', '滤清器*1', 280.0000, '25*20*4', 'CF-5101-T', 10, 20, 22.0000, 280, 95, 1, 30, 'admin', '2026-06-18 18:00:00.000000', 0, 1);

-- ===== 已抢完(25) SKU =====
INSERT INTO scms_consignment_recruit_list_sku (id, recruit_id, recruit_no, sku_id, sku_name, category_id, factory_id, factory_name, source_url, product_model, vehicle_model, sales_quantity, unit, package_info, gross_weight_g, package_size_cm, source_model, delivery_days, moq, cost_price, sale_qty_90d, sale_qty_30d, source_type, sku_status, create_by, create_time, is_deleted, version)
VALUES
(402501, 102501, 'SC2606160001', 5007, '雨刮片(对装)', 26240, 1000, '测试工厂A', 'https://example.com/sku/5007', 'WP-7101', '通用接口', '2', 'Set', '雨刮片*2', 400.0000, '65*8*3', 'WP-7101-T', 12, 30, 35.0000, 350, 120, 1, 30, 'admin', '2026-06-15 16:00:00.000000', 0, 1),
(402502, 102501, 'SC2606160001', 5009, '发动机皮带', 26240, 1000, '测试工厂A', 'https://example.com/sku/5009', 'EB-9101', '日产轩逸', '1', 'PCS', '皮带*1', 320.0000, '30*20*3', 'EB-9101-T', 14, 10, 55.0000, 150, 50, 1, 30, 'admin', '2026-06-15 16:00:00.000000', 0, 1),
(402503, 102501, 'SC2606160001', 5010, '水泵总成', 26240, 1000, '测试工厂A', 'https://example.com/sku/5010', 'WP-10101', '宝马3系', '1', 'PCS', '水泵*1', 1200.0000, '25*20*18', 'WP-10101-T', 20, 5, 120.0000, 80, 28, 1, 30, 'admin', '2026-06-15 16:00:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list_sku (id, recruit_id, recruit_no, sku_id, sku_name, category_id, factory_id, factory_name, source_url, product_model, vehicle_model, sales_quantity, unit, package_info, gross_weight_g, package_size_cm, source_model, delivery_days, moq, cost_price, sale_qty_90d, sale_qty_30d, source_type, sku_status, create_by, create_time, is_deleted, version)
VALUES
(402504, 102502, 'SC2606160002', 5002, '后刹车片套装', 26240, 1000, '测试工厂A', 'https://example.com/sku/5002', 'BP-2202', '丰田卡罗拉/雷凌', '1', 'Set', '刹车片*4', 780.0000, '35*25*8', 'BP-2202-T', 15, 10, 72.0000, 95, 32, 1, 30, 'admin', '2026-06-15 16:30:00.000000', 0, 1),
(402505, 102502, 'SC2606160002', 5004, '空气滤清器', 26240, 1000, '测试工厂A', 'https://example.com/sku/5004', 'AF-4101', '本田思域', '1', 'PCS', '滤清器*1', 300.0000, '28*22*5', 'AF-4101-T', 10, 20, 25.0000, 300, 110, 1, 30, 'admin', '2026-06-15 16:30:00.000000', 0, 1),
(402506, 102502, 'SC2606160002', 5008, '前照灯灯泡', 26240, 1000, '测试工厂A', 'https://example.com/sku/5008', 'HL-8101', 'H7接口', '2', 'PCS', '灯泡*2', 150.0000, '12*8*8', 'HL-8101-T', 10, 20, 28.0000, 180, 60, 1, 30, 'admin', '2026-06-15 16:30:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list_sku (id, recruit_id, recruit_no, sku_id, sku_name, category_id, factory_id, factory_name, source_url, product_model, vehicle_model, sales_quantity, unit, package_info, gross_weight_g, package_size_cm, source_model, delivery_days, moq, cost_price, sale_qty_90d, sale_qty_30d, source_type, sku_status, create_by, create_time, is_deleted, version)
VALUES
(402507, 102503, 'SC2606160003', 5001, '前刹车片套装', 26240, 1000, '测试工厂A', 'https://example.com/sku/5001', 'BP-2201', '丰田卡罗拉/雷凌', '1', 'Set', '刹车片*4', 850.0000, '35*25*8', 'BP-2201-T', 15, 10, 85.0000, 120, 45, 1, 30, 'admin', '2026-06-15 17:00:00.000000', 0, 1),
(402508, 102503, 'SC2606160003', 5006, '火花塞(4支装)', 26240, 1000, '测试工厂A', 'https://example.com/sku/5006', 'SP-6101', '大众朗逸', '4', 'Set', '火花塞*4', 350.0000, '15*10*5', 'SP-6101-T', 12, 20, 45.0000, 200, 75, 1, 30, 'admin', '2026-06-15 17:00:00.000000', 0, 1),
(402509, 102503, 'SC2606160003', 5009, '发动机皮带', 26240, 1000, '测试工厂A', 'https://example.com/sku/5009', 'EB-9101', '日产轩逸', '1', 'PCS', '皮带*1', 320.0000, '30*20*3', 'EB-9101-T', 14, 10, 55.0000, 150, 50, 1, 30, 'admin', '2026-06-15 17:00:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list_sku (id, recruit_id, recruit_no, sku_id, sku_name, category_id, factory_id, factory_name, source_url, product_model, vehicle_model, sales_quantity, unit, package_info, gross_weight_g, package_size_cm, source_model, delivery_days, moq, cost_price, sale_qty_90d, sale_qty_30d, source_type, sku_status, create_by, create_time, is_deleted, version)
VALUES
(402510, 102504, 'SC2606160004', 5003, '机油滤清器', 26240, 1000, '测试工厂A', 'https://example.com/sku/5003', 'OF-3101', '通用', '1', 'PCS', '滤清器*1', 250.0000, '20*15*15', 'OF-3101-T', 10, 50, 15.0000, 500, 180, 1, 30, 'admin', '2026-06-15 17:30:00.000000', 0, 1),
(402511, 102504, 'SC2606160004', 5005, '空调滤清器', 26240, 1000, '测试工厂A', 'https://example.com/sku/5005', 'CF-5101', '通用', '1', 'PCS', '滤清器*1', 280.0000, '25*20*4', 'CF-5101-T', 10, 20, 22.0000, 280, 95, 1, 30, 'admin', '2026-06-15 17:30:00.000000', 0, 1),
(402512, 102504, 'SC2606160004', 5010, '水泵总成', 26240, 1000, '测试工厂A', 'https://example.com/sku/5010', 'WP-10101', '宝马3系', '1', 'PCS', '水泵*1', 1200.0000, '25*20*18', 'WP-10101-T', 20, 5, 120.0000, 80, 28, 1, 30, 'admin', '2026-06-15 17:30:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list_sku (id, recruit_id, recruit_no, sku_id, sku_name, category_id, factory_id, factory_name, source_url, product_model, vehicle_model, sales_quantity, unit, package_info, gross_weight_g, package_size_cm, source_model, delivery_days, moq, cost_price, sale_qty_90d, sale_qty_30d, source_type, sku_status, create_by, create_time, is_deleted, version)
VALUES
(402513, 102505, 'SC2606160005', 5004, '空气滤清器', 26240, 1000, '测试工厂A', 'https://example.com/sku/5004', 'AF-4101', '本田思域', '1', 'PCS', '滤清器*1', 300.0000, '28*22*5', 'AF-4101-T', 10, 20, 25.0000, 300, 110, 1, 30, 'admin', '2026-06-15 18:00:00.000000', 0, 1),
(402514, 102505, 'SC2606160005', 5007, '雨刮片(对装)', 26240, 1000, '测试工厂A', 'https://example.com/sku/5007', 'WP-7101', '通用接口', '2', 'Set', '雨刮片*2', 400.0000, '65*8*3', 'WP-7101-T', 12, 30, 35.0000, 350, 120, 1, 30, 'admin', '2026-06-15 18:00:00.000000', 0, 1),
(402515, 102505, 'SC2606160005', 5008, '前照灯灯泡', 26240, 1000, '测试工厂A', 'https://example.com/sku/5008', 'HL-8101', 'H7接口', '2', 'PCS', '灯泡*2', 150.0000, '12*8*8', 'HL-8101-T', 10, 20, 28.0000, 180, 60, 1, 30, 'admin', '2026-06-15 18:00:00.000000', 0, 1);

-- ===== 无人申请已回收(30) SKU (factory_id=1001测试工厂B) =====
INSERT INTO scms_consignment_recruit_list_sku (id, recruit_id, recruit_no, sku_id, sku_name, category_id, factory_id, factory_name, source_url, product_model, vehicle_model, sales_quantity, unit, package_info, gross_weight_g, package_size_cm, source_model, delivery_days, moq, cost_price, sale_qty_90d, sale_qty_30d, source_type, sku_status, create_by, create_time, is_deleted, version)
VALUES
(403001, 103001, 'SC2606120001', 5002, '后刹车片套装', 26240, 1001, '测试工厂B', 'https://example.com/sku/5002', 'BP-2202', '丰田卡罗拉/雷凌', '1', 'Set', '刹车片*4', 780.0000, '35*25*8', 'BP-2202-T', 15, 10, 72.0000, 95, 32, 1, 30, 'admin', '2026-06-11 15:00:00.000000', 0, 1),
(403002, 103001, 'SC2606120001', 5009, '发动机皮带', 26240, 1001, '测试工厂B', 'https://example.com/sku/5009', 'EB-9101', '日产轩逸', '1', 'PCS', '皮带*1', 320.0000, '30*20*3', 'EB-9101-T', 14, 10, 55.0000, 150, 50, 1, 30, 'admin', '2026-06-11 15:00:00.000000', 0, 1),
(403003, 103001, 'SC2606120001', 5010, '水泵总成', 26240, 1001, '测试工厂B', 'https://example.com/sku/5010', 'WP-10101', '宝马3系', '1', 'PCS', '水泵*1', 1200.0000, '25*20*18', 'WP-10101-T', 20, 5, 120.0000, 80, 28, 1, 30, 'admin', '2026-06-11 15:00:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list_sku (id, recruit_id, recruit_no, sku_id, sku_name, category_id, factory_id, factory_name, source_url, product_model, vehicle_model, sales_quantity, unit, package_info, gross_weight_g, package_size_cm, source_model, delivery_days, moq, cost_price, sale_qty_90d, sale_qty_30d, source_type, sku_status, create_by, create_time, is_deleted, version)
VALUES
(403004, 103002, 'SC2606120002', 5001, '前刹车片套装', 26240, 1001, '测试工厂B', 'https://example.com/sku/5001', 'BP-2201', '丰田卡罗拉/雷凌', '1', 'Set', '刹车片*4', 850.0000, '35*25*8', 'BP-2201-T', 15, 10, 85.0000, 120, 45, 1, 30, 'admin', '2026-06-11 15:30:00.000000', 0, 1),
(403005, 103002, 'SC2606120002', 5005, '空调滤清器', 26240, 1001, '测试工厂B', 'https://example.com/sku/5005', 'CF-5101', '通用', '1', 'PCS', '滤清器*1', 280.0000, '25*20*4', 'CF-5101-T', 10, 20, 22.0000, 280, 95, 1, 30, 'admin', '2026-06-11 15:30:00.000000', 0, 1),
(403006, 103002, 'SC2606120002', 5006, '火花塞(4支装)', 26240, 1001, '测试工厂B', 'https://example.com/sku/5006', 'SP-6101', '大众朗逸', '4', 'Set', '火花塞*4', 350.0000, '15*10*5', 'SP-6101-T', 12, 20, 45.0000, 200, 75, 1, 30, 'admin', '2026-06-11 15:30:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list_sku (id, recruit_id, recruit_no, sku_id, sku_name, category_id, factory_id, factory_name, source_url, product_model, vehicle_model, sales_quantity, unit, package_info, gross_weight_g, package_size_cm, source_model, delivery_days, moq, cost_price, sale_qty_90d, sale_qty_30d, source_type, sku_status, create_by, create_time, is_deleted, version)
VALUES
(403007, 103003, 'SC2606120003', 5003, '机油滤清器', 26240, 1001, '测试工厂B', 'https://example.com/sku/5003', 'OF-3101', '通用', '1', 'PCS', '滤清器*1', 250.0000, '20*15*15', 'OF-3101-T', 10, 50, 15.0000, 500, 180, 1, 30, 'admin', '2026-06-11 16:00:00.000000', 0, 1),
(403008, 103003, 'SC2606120003', 5007, '雨刮片(对装)', 26240, 1001, '测试工厂B', 'https://example.com/sku/5007', 'WP-7101', '通用接口', '2', 'Set', '雨刮片*2', 400.0000, '65*8*3', 'WP-7101-T', 12, 30, 35.0000, 350, 120, 1, 30, 'admin', '2026-06-11 16:00:00.000000', 0, 1),
(403009, 103003, 'SC2606120003', 5008, '前照灯灯泡', 26240, 1001, '测试工厂B', 'https://example.com/sku/5008', 'HL-8101', 'H7接口', '2', 'PCS', '灯泡*2', 150.0000, '12*8*8', 'HL-8101-T', 10, 20, 28.0000, 180, 60, 1, 30, 'admin', '2026-06-11 16:00:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list_sku (id, recruit_id, recruit_no, sku_id, sku_name, category_id, factory_id, factory_name, source_url, product_model, vehicle_model, sales_quantity, unit, package_info, gross_weight_g, package_size_cm, source_model, delivery_days, moq, cost_price, sale_qty_90d, sale_qty_30d, source_type, sku_status, create_by, create_time, is_deleted, version)
VALUES
(403010, 103004, 'SC2606120004', 5004, '空气滤清器', 26240, 1001, '测试工厂B', 'https://example.com/sku/5004', 'AF-4101', '本田思域', '1', 'PCS', '滤清器*1', 300.0000, '28*22*5', 'AF-4101-T', 10, 20, 25.0000, 300, 110, 1, 30, 'admin', '2026-06-11 16:30:00.000000', 0, 1),
(403011, 103004, 'SC2606120004', 5006, '火花塞(4支装)', 26240, 1001, '测试工厂B', 'https://example.com/sku/5006', 'SP-6101', '大众朗逸', '4', 'Set', '火花塞*4', 350.0000, '15*10*5', 'SP-6101-T', 12, 20, 45.0000, 200, 75, 1, 30, 'admin', '2026-06-11 16:30:00.000000', 0, 1),
(403012, 103004, 'SC2606120004', 5009, '发动机皮带', 26240, 1001, '测试工厂B', 'https://example.com/sku/5009', 'EB-9101', '日产轩逸', '1', 'PCS', '皮带*1', 320.0000, '30*20*3', 'EB-9101-T', 14, 10, 55.0000, 150, 50, 1, 30, 'admin', '2026-06-11 16:30:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list_sku (id, recruit_id, recruit_no, sku_id, sku_name, category_id, factory_id, factory_name, source_url, product_model, vehicle_model, sales_quantity, unit, package_info, gross_weight_g, package_size_cm, source_model, delivery_days, moq, cost_price, sale_qty_90d, sale_qty_30d, source_type, sku_status, create_by, create_time, is_deleted, version)
VALUES
(403013, 103005, 'SC2606120005', 5001, '前刹车片套装', 26240, 1001, '测试工厂B', 'https://example.com/sku/5001', 'BP-2201', '丰田卡罗拉/雷凌', '1', 'Set', '刹车片*4', 850.0000, '35*25*8', 'BP-2201-T', 15, 10, 85.0000, 120, 45, 1, 30, 'admin', '2026-06-11 17:00:00.000000', 0, 1),
(403014, 103005, 'SC2606120005', 5002, '后刹车片套装', 26240, 1001, '测试工厂B', 'https://example.com/sku/5002', 'BP-2202', '丰田卡罗拉/雷凌', '1', 'Set', '刹车片*4', 780.0000, '35*25*8', 'BP-2202-T', 15, 10, 72.0000, 95, 32, 1, 30, 'admin', '2026-06-11 17:00:00.000000', 0, 1),
(403015, 103005, 'SC2606120005', 5010, '水泵总成', 26240, 1001, '测试工厂B', 'https://example.com/sku/5010', 'WP-10101', '宝马3系', '1', 'PCS', '水泵*1', 1200.0000, '25*20*18', 'WP-10101-T', 20, 5, 120.0000, 80, 28, 1, 30, 'admin', '2026-06-11 17:00:00.000000', 0, 1);

-- ===== 分配中(50) SKU =====
INSERT INTO scms_consignment_recruit_list_sku (id, recruit_id, recruit_no, sku_id, sku_name, category_id, factory_id, factory_name, source_url, product_model, vehicle_model, sales_quantity, unit, package_info, gross_weight_g, package_size_cm, source_model, delivery_days, moq, cost_price, sale_qty_90d, sale_qty_30d, source_type, sku_status, create_by, create_time, is_deleted, version)
VALUES
(405001, 105001, 'SC2606090001', 5003, '机油滤清器', 26240, 1000, '测试工厂A', 'https://example.com/sku/5003', 'OF-3101', '通用', '1', 'PCS', '滤清器*1', 250.0000, '20*15*15', 'OF-3101-T', 10, 50, 15.0000, 500, 180, 1, 30, 'admin', '2026-06-08 16:00:00.000000', 0, 1),
(405002, 105001, 'SC2606090001', 5004, '空气滤清器', 26240, 1000, '测试工厂A', 'https://example.com/sku/5004', 'AF-4101', '本田思域', '1', 'PCS', '滤清器*1', 300.0000, '28*22*5', 'AF-4101-T', 10, 20, 25.0000, 300, 110, 1, 30, 'admin', '2026-06-08 16:00:00.000000', 0, 1),
(405003, 105001, 'SC2606090001', 5007, '雨刮片(对装)', 26240, 1000, '测试工厂A', 'https://example.com/sku/5007', 'WP-7101', '通用接口', '2', 'Set', '雨刮片*2', 400.0000, '65*8*3', 'WP-7101-T', 12, 30, 35.0000, 350, 120, 1, 30, 'admin', '2026-06-08 16:00:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list_sku (id, recruit_id, recruit_no, sku_id, sku_name, category_id, factory_id, factory_name, source_url, product_model, vehicle_model, sales_quantity, unit, package_info, gross_weight_g, package_size_cm, source_model, delivery_days, moq, cost_price, sale_qty_90d, sale_qty_30d, source_type, sku_status, create_by, create_time, is_deleted, version)
VALUES
(405004, 105002, 'SC2606090002', 5005, '空调滤清器', 26240, 1000, '测试工厂A', 'https://example.com/sku/5005', 'CF-5101', '通用', '1', 'PCS', '滤清器*1', 280.0000, '25*20*4', 'CF-5101-T', 10, 20, 22.0000, 280, 95, 1, 30, 'admin', '2026-06-08 16:30:00.000000', 0, 1),
(405005, 105002, 'SC2606090002', 5008, '前照灯灯泡', 26240, 1000, '测试工厂A', 'https://example.com/sku/5008', 'HL-8101', 'H7接口', '2', 'PCS', '灯泡*2', 150.0000, '12*8*8', 'HL-8101-T', 10, 20, 28.0000, 180, 60, 1, 30, 'admin', '2026-06-08 16:30:00.000000', 0, 1),
(405006, 105002, 'SC2606090002', 5009, '发动机皮带', 26240, 1000, '测试工厂A', 'https://example.com/sku/5009', 'EB-9101', '日产轩逸', '1', 'PCS', '皮带*1', 320.0000, '30*20*3', 'EB-9101-T', 14, 10, 55.0000, 150, 50, 1, 30, 'admin', '2026-06-08 16:30:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list_sku (id, recruit_id, recruit_no, sku_id, sku_name, category_id, factory_id, factory_name, source_url, product_model, vehicle_model, sales_quantity, unit, package_info, gross_weight_g, package_size_cm, source_model, delivery_days, moq, cost_price, sale_qty_90d, sale_qty_30d, source_type, sku_status, create_by, create_time, is_deleted, version)
VALUES
(405007, 105003, 'SC2606090003', 5001, '前刹车片套装', 26240, 1000, '测试工厂A', 'https://example.com/sku/5001', 'BP-2201', '丰田卡罗拉/雷凌', '1', 'Set', '刹车片*4', 850.0000, '35*25*8', 'BP-2201-T', 15, 10, 85.0000, 120, 45, 1, 30, 'admin', '2026-06-08 17:00:00.000000', 0, 1),
(405008, 105003, 'SC2606090003', 5010, '水泵总成', 26240, 1000, '测试工厂A', 'https://example.com/sku/5010', 'WP-10101', '宝马3系', '1', 'PCS', '水泵*1', 1200.0000, '25*20*18', 'WP-10101-T', 20, 5, 120.0000, 80, 28, 1, 30, 'admin', '2026-06-08 17:00:00.000000', 0, 1),
(405009, 105003, 'SC2606090003', 5006, '火花塞(4支装)', 26240, 1000, '测试工厂A', 'https://example.com/sku/5006', 'SP-6101', '大众朗逸', '4', 'Set', '火花塞*4', 350.0000, '15*10*5', 'SP-6101-T', 12, 20, 45.0000, 200, 75, 1, 30, 'admin', '2026-06-08 17:00:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list_sku (id, recruit_id, recruit_no, sku_id, sku_name, category_id, factory_id, factory_name, source_url, product_model, vehicle_model, sales_quantity, unit, package_info, gross_weight_g, package_size_cm, source_model, delivery_days, moq, cost_price, sale_qty_90d, sale_qty_30d, source_type, sku_status, create_by, create_time, is_deleted, version)
VALUES
(405010, 105004, 'SC2606090004', 5002, '后刹车片套装', 26240, 1000, '测试工厂A', 'https://example.com/sku/5002', 'BP-2202', '丰田卡罗拉/雷凌', '1', 'Set', '刹车片*4', 780.0000, '35*25*8', 'BP-2202-T', 15, 10, 72.0000, 95, 32, 1, 30, 'admin', '2026-06-08 17:30:00.000000', 0, 1),
(405011, 105004, 'SC2606090004', 5003, '机油滤清器', 26240, 1000, '测试工厂A', 'https://example.com/sku/5003', 'OF-3101', '通用', '1', 'PCS', '滤清器*1', 250.0000, '20*15*15', 'OF-3101-T', 10, 50, 15.0000, 500, 180, 1, 30, 'admin', '2026-06-08 17:30:00.000000', 0, 1),
(405012, 105004, 'SC2606090004', 5007, '雨刮片(对装)', 26240, 1000, '测试工厂A', 'https://example.com/sku/5007', 'WP-7101', '通用接口', '2', 'Set', '雨刮片*2', 400.0000, '65*8*3', 'WP-7101-T', 12, 30, 35.0000, 350, 120, 1, 30, 'admin', '2026-06-08 17:30:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list_sku (id, recruit_id, recruit_no, sku_id, sku_name, category_id, factory_id, factory_name, source_url, product_model, vehicle_model, sales_quantity, unit, package_info, gross_weight_g, package_size_cm, source_model, delivery_days, moq, cost_price, sale_qty_90d, sale_qty_30d, source_type, sku_status, create_by, create_time, is_deleted, version)
VALUES
(405013, 105005, 'SC2606090005', 5004, '空气滤清器', 26240, 1000, '测试工厂A', 'https://example.com/sku/5004', 'AF-4101', '本田思域', '1', 'PCS', '滤清器*1', 300.0000, '28*22*5', 'AF-4101-T', 10, 20, 25.0000, 300, 110, 1, 30, 'admin', '2026-06-08 18:00:00.000000', 0, 1),
(405014, 105005, 'SC2606090005', 5005, '空调滤清器', 26240, 1000, '测试工厂A', 'https://example.com/sku/5005', 'CF-5101', '通用', '1', 'PCS', '滤清器*1', 280.0000, '25*20*4', 'CF-5101-T', 10, 20, 22.0000, 280, 95, 1, 30, 'admin', '2026-06-08 18:00:00.000000', 0, 1),
(405015, 105005, 'SC2606090005', 5009, '发动机皮带', 26240, 1000, '测试工厂A', 'https://example.com/sku/5009', 'EB-9101', '日产轩逸', '1', 'PCS', '皮带*1', 320.0000, '30*20*3', 'EB-9101-T', 14, 10, 55.0000, 150, 50, 1, 30, 'admin', '2026-06-08 18:00:00.000000', 0, 1);

-- ===== 清单完成(60) SKU =====
INSERT INTO scms_consignment_recruit_list_sku (id, recruit_id, recruit_no, sku_id, sku_name, category_id, factory_id, factory_name, source_url, product_model, vehicle_model, sales_quantity, unit, package_info, gross_weight_g, package_size_cm, source_model, delivery_days, moq, cost_price, sale_qty_90d, sale_qty_30d, source_type, sku_status, create_by, create_time, is_deleted, version)
VALUES
(406001, 106001, 'SC2606020001', 5001, '前刹车片套装', 26240, 1000, '测试工厂A', 'https://example.com/sku/5001', 'BP-2201', '丰田卡罗拉/雷凌', '1', 'Set', '刹车片*4', 850.0000, '35*25*8', 'BP-2201-T', 15, 10, 85.0000, 120, 45, 1, 30, 'admin', '2026-06-01 15:00:00.000000', 0, 1),
(406002, 106001, 'SC2606020001', 5006, '火花塞(4支装)', 26240, 1000, '测试工厂A', 'https://example.com/sku/5006', 'SP-6101', '大众朗逸', '4', 'Set', '火花塞*4', 350.0000, '15*10*5', 'SP-6101-T', 12, 20, 45.0000, 200, 75, 1, 30, 'admin', '2026-06-01 15:00:00.000000', 0, 1),
(406003, 106001, 'SC2606020001', 5008, '前照灯灯泡', 26240, 1000, '测试工厂A', 'https://example.com/sku/5008', 'HL-8101', 'H7接口', '2', 'PCS', '灯泡*2', 150.0000, '12*8*8', 'HL-8101-T', 10, 20, 28.0000, 180, 60, 1, 30, 'admin', '2026-06-01 15:00:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list_sku (id, recruit_id, recruit_no, sku_id, sku_name, category_id, factory_id, factory_name, source_url, product_model, vehicle_model, sales_quantity, unit, package_info, gross_weight_g, package_size_cm, source_model, delivery_days, moq, cost_price, sale_qty_90d, sale_qty_30d, source_type, sku_status, create_by, create_time, is_deleted, version)
VALUES
(406004, 106002, 'SC2606020002', 5002, '后刹车片套装', 26240, 1000, '测试工厂A', 'https://example.com/sku/5002', 'BP-2202', '丰田卡罗拉/雷凌', '1', 'Set', '刹车片*4', 780.0000, '35*25*8', 'BP-2202-T', 15, 10, 72.0000, 95, 32, 1, 30, 'admin', '2026-06-01 15:30:00.000000', 0, 1),
(406005, 106002, 'SC2606020002', 5007, '雨刮片(对装)', 26240, 1000, '测试工厂A', 'https://example.com/sku/5007', 'WP-7101', '通用接口', '2', 'Set', '雨刮片*2', 400.0000, '65*8*3', 'WP-7101-T', 12, 30, 35.0000, 350, 120, 1, 30, 'admin', '2026-06-01 15:30:00.000000', 0, 1),
(406006, 106002, 'SC2606020002', 5010, '水泵总成', 26240, 1000, '测试工厂A', 'https://example.com/sku/5010', 'WP-10101', '宝马3系', '1', 'PCS', '水泵*1', 1200.0000, '25*20*18', 'WP-10101-T', 20, 5, 120.0000, 80, 28, 1, 30, 'admin', '2026-06-01 15:30:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list_sku (id, recruit_id, recruit_no, sku_id, sku_name, category_id, factory_id, factory_name, source_url, product_model, vehicle_model, sales_quantity, unit, package_info, gross_weight_g, package_size_cm, source_model, delivery_days, moq, cost_price, sale_qty_90d, sale_qty_30d, source_type, sku_status, create_by, create_time, is_deleted, version)
VALUES
(406007, 106003, 'SC2606020003', 5003, '机油滤清器', 26240, 1000, '测试工厂A', 'https://example.com/sku/5003', 'OF-3101', '通用', '1', 'PCS', '滤清器*1', 250.0000, '20*15*15', 'OF-3101-T', 10, 50, 15.0000, 500, 180, 1, 30, 'admin', '2026-06-01 16:00:00.000000', 0, 1),
(406008, 106003, 'SC2606020003', 5008, '前照灯灯泡', 26240, 1000, '测试工厂A', 'https://example.com/sku/5008', 'HL-8101', 'H7接口', '2', 'PCS', '灯泡*2', 150.0000, '12*8*8', 'HL-8101-T', 10, 20, 28.0000, 180, 60, 1, 30, 'admin', '2026-06-01 16:00:00.000000', 0, 1),
(406009, 106003, 'SC2606020003', 5009, '发动机皮带', 26240, 1000, '测试工厂A', 'https://example.com/sku/5009', 'EB-9101', '日产轩逸', '1', 'PCS', '皮带*1', 320.0000, '30*20*3', 'EB-9101-T', 14, 10, 55.0000, 150, 50, 1, 30, 'admin', '2026-06-01 16:00:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list_sku (id, recruit_id, recruit_no, sku_id, sku_name, category_id, factory_id, factory_name, source_url, product_model, vehicle_model, sales_quantity, unit, package_info, gross_weight_g, package_size_cm, source_model, delivery_days, moq, cost_price, sale_qty_90d, sale_qty_30d, source_type, sku_status, create_by, create_time, is_deleted, version)
VALUES
(406010, 106004, 'SC2606020004', 5004, '空气滤清器', 26240, 1000, '测试工厂A', 'https://example.com/sku/5004', 'AF-4101', '本田思域', '1', 'PCS', '滤清器*1', 300.0000, '28*22*5', 'AF-4101-T', 10, 20, 25.0000, 300, 110, 1, 30, 'admin', '2026-06-01 16:30:00.000000', 0, 1),
(406011, 106004, 'SC2606020004', 5005, '空调滤清器', 26240, 1000, '测试工厂A', 'https://example.com/sku/5005', 'CF-5101', '通用', '1', 'PCS', '滤清器*1', 280.0000, '25*20*4', 'CF-5101-T', 10, 20, 22.0000, 280, 95, 1, 30, 'admin', '2026-06-01 16:30:00.000000', 0, 1),
(406012, 106004, 'SC2606020004', 5010, '水泵总成', 26240, 1000, '测试工厂A', 'https://example.com/sku/5010', 'WP-10101', '宝马3系', '1', 'PCS', '水泵*1', 1200.0000, '25*20*18', 'WP-10101-T', 20, 5, 120.0000, 80, 28, 1, 30, 'admin', '2026-06-01 16:30:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list_sku (id, recruit_id, recruit_no, sku_id, sku_name, category_id, factory_id, factory_name, source_url, product_model, vehicle_model, sales_quantity, unit, package_info, gross_weight_g, package_size_cm, source_model, delivery_days, moq, cost_price, sale_qty_90d, sale_qty_30d, source_type, sku_status, create_by, create_time, is_deleted, version)
VALUES
(406013, 106005, 'SC2606020005', 5001, '前刹车片套装', 26240, 1000, '测试工厂A', 'https://example.com/sku/5001', 'BP-2201', '丰田卡罗拉/雷凌', '1', 'Set', '刹车片*4', 850.0000, '35*25*8', 'BP-2201-T', 15, 10, 85.0000, 120, 45, 1, 30, 'admin', '2026-06-01 17:00:00.000000', 0, 1),
(406014, 106005, 'SC2606020005', 5002, '后刹车片套装', 26240, 1000, '测试工厂A', 'https://example.com/sku/5002', 'BP-2202', '丰田卡罗拉/雷凌', '1', 'Set', '刹车片*4', 780.0000, '35*25*8', 'BP-2202-T', 15, 10, 72.0000, 95, 32, 1, 30, 'admin', '2026-06-01 17:00:00.000000', 0, 1),
(406015, 106005, 'SC2606020005', 5009, '发动机皮带', 26240, 1000, '测试工厂A', 'https://example.com/sku/5009', 'EB-9101', '日产轩逸', '1', 'PCS', '皮带*1', 320.0000, '30*20*3', 'EB-9101-T', 14, 10, 55.0000, 150, 50, 1, 30, 'admin', '2026-06-01 17:00:00.000000', 0, 1);

-- ===== 作废(100) SKU (sku_status=100已删除) =====
INSERT INTO scms_consignment_recruit_list_sku (id, recruit_id, recruit_no, sku_id, sku_name, category_id, factory_id, factory_name, source_url, product_model, vehicle_model, sales_quantity, unit, package_info, gross_weight_g, package_size_cm, source_model, delivery_days, moq, cost_price, sale_qty_90d, sale_qty_30d, source_type, sku_status, create_by, create_time, is_deleted, version)
VALUES
(410001, 110001, 'SC2606010001', 5006, '火花塞(4支装)', 26240, 1002, '测试工厂C', 'https://example.com/sku/5006', 'SP-6101', '大众朗逸', '4', 'Set', '火花塞*4', 350.0000, '15*10*5', 'SP-6101-T', 12, 20, 45.0000, 200, 75, 1, 100, 'admin', '2026-06-01 14:00:00.000000', 0, 1),
(410002, 110001, 'SC2606010001', 5007, '雨刮片(对装)', 26240, 1002, '测试工厂C', 'https://example.com/sku/5007', 'WP-7101', '通用接口', '2', 'Set', '雨刮片*2', 400.0000, '65*8*3', 'WP-7101-T', 12, 30, 35.0000, 350, 120, 1, 100, 'admin', '2026-06-01 14:00:00.000000', 0, 1),
(410003, 110001, 'SC2606010001', 5008, '前照灯灯泡', 26240, 1002, '测试工厂C', 'https://example.com/sku/5008', 'HL-8101', 'H7接口', '2', 'PCS', '灯泡*2', 150.0000, '12*8*8', 'HL-8101-T', 10, 20, 28.0000, 180, 60, 1, 100, 'admin', '2026-06-01 14:00:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list_sku (id, recruit_id, recruit_no, sku_id, sku_name, category_id, factory_id, factory_name, source_url, product_model, vehicle_model, sales_quantity, unit, package_info, gross_weight_g, package_size_cm, source_model, delivery_days, moq, cost_price, sale_qty_90d, sale_qty_30d, source_type, sku_status, create_by, create_time, is_deleted, version)
VALUES
(410004, 110002, 'SC2606010002', 5003, '机油滤清器', 26240, 1002, '测试工厂C', 'https://example.com/sku/5003', 'OF-3101', '通用', '1', 'PCS', '滤清器*1', 250.0000, '20*15*15', 'OF-3101-T', 10, 50, 15.0000, 500, 180, 1, 100, 'admin', '2026-06-01 14:30:00.000000', 0, 1),
(410005, 110002, 'SC2606010002', 5004, '空气滤清器', 26240, 1002, '测试工厂C', 'https://example.com/sku/5004', 'AF-4101', '本田思域', '1', 'PCS', '滤清器*1', 300.0000, '28*22*5', 'AF-4101-T', 10, 20, 25.0000, 300, 110, 1, 100, 'admin', '2026-06-01 14:30:00.000000', 0, 1),
(410006, 110002, 'SC2606010002', 5005, '空调滤清器', 26240, 1002, '测试工厂C', 'https://example.com/sku/5005', 'CF-5101', '通用', '1', 'PCS', '滤清器*1', 280.0000, '25*20*4', 'CF-5101-T', 10, 20, 22.0000, 280, 95, 1, 100, 'admin', '2026-06-01 14:30:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list_sku (id, recruit_id, recruit_no, sku_id, sku_name, category_id, factory_id, factory_name, source_url, product_model, vehicle_model, sales_quantity, unit, package_info, gross_weight_g, package_size_cm, source_model, delivery_days, moq, cost_price, sale_qty_90d, sale_qty_30d, source_type, sku_status, create_by, create_time, is_deleted, version)
VALUES
(410007, 110003, 'SC2606010003', 5001, '前刹车片套装', 26240, 1002, '测试工厂C', 'https://example.com/sku/5001', 'BP-2201', '丰田卡罗拉/雷凌', '1', 'Set', '刹车片*4', 850.0000, '35*25*8', 'BP-2201-T', 15, 10, 85.0000, 120, 45, 1, 100, 'admin', '2026-06-01 15:00:00.000000', 0, 1),
(410008, 110003, 'SC2606010003', 5009, '发动机皮带', 26240, 1002, '测试工厂C', 'https://example.com/sku/5009', 'EB-9101', '日产轩逸', '1', 'PCS', '皮带*1', 320.0000, '30*20*3', 'EB-9101-T', 14, 10, 55.0000, 150, 50, 1, 100, 'admin', '2026-06-01 15:00:00.000000', 0, 1),
(410009, 110003, 'SC2606010003', 5010, '水泵总成', 26240, 1002, '测试工厂C', 'https://example.com/sku/5010', 'WP-10101', '宝马3系', '1', 'PCS', '水泵*1', 1200.0000, '25*20*18', 'WP-10101-T', 20, 5, 120.0000, 80, 28, 1, 100, 'admin', '2026-06-01 15:00:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list_sku (id, recruit_id, recruit_no, sku_id, sku_name, category_id, factory_id, factory_name, source_url, product_model, vehicle_model, sales_quantity, unit, package_info, gross_weight_g, package_size_cm, source_model, delivery_days, moq, cost_price, sale_qty_90d, sale_qty_30d, source_type, sku_status, create_by, create_time, is_deleted, version)
VALUES
(410010, 110004, 'SC2606010004', 5002, '后刹车片套装', 26240, 1002, '测试工厂C', 'https://example.com/sku/5002', 'BP-2202', '丰田卡罗拉/雷凌', '1', 'Set', '刹车片*4', 780.0000, '35*25*8', 'BP-2202-T', 15, 10, 72.0000, 95, 32, 1, 100, 'admin', '2026-06-01 15:30:00.000000', 0, 1),
(410011, 110004, 'SC2606010004', 5005, '空调滤清器', 26240, 1002, '测试工厂C', 'https://example.com/sku/5005', 'CF-5101', '通用', '1', 'PCS', '滤清器*1', 280.0000, '25*20*4', 'CF-5101-T', 10, 20, 22.0000, 280, 95, 1, 100, 'admin', '2026-06-01 15:30:00.000000', 0, 1),
(410012, 110004, 'SC2606010004', 5006, '火花塞(4支装)', 26240, 1002, '测试工厂C', 'https://example.com/sku/5006', 'SP-6101', '大众朗逸', '4', 'Set', '火花塞*4', 350.0000, '15*10*5', 'SP-6101-T', 12, 20, 45.0000, 200, 75, 1, 100, 'admin', '2026-06-01 15:30:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list_sku (id, recruit_id, recruit_no, sku_id, sku_name, category_id, factory_id, factory_name, source_url, product_model, vehicle_model, sales_quantity, unit, package_info, gross_weight_g, package_size_cm, source_model, delivery_days, moq, cost_price, sale_qty_90d, sale_qty_30d, source_type, sku_status, create_by, create_time, is_deleted, version)
VALUES
(410013, 110005, 'SC2606010005', 5007, '雨刮片(对装)', 26240, 1002, '测试工厂C', 'https://example.com/sku/5007', 'WP-7101', '通用接口', '2', 'Set', '雨刮片*2', 400.0000, '65*8*3', 'WP-7101-T', 12, 30, 35.0000, 350, 120, 1, 100, 'admin', '2026-06-01 16:00:00.000000', 0, 1),
(410014, 110005, 'SC2606010005', 5008, '前照灯灯泡', 26240, 1002, '测试工厂C', 'https://example.com/sku/5008', 'HL-8101', 'H7接口', '2', 'PCS', '灯泡*2', 150.0000, '12*8*8', 'HL-8101-T', 10, 20, 28.0000, 180, 60, 1, 100, 'admin', '2026-06-01 16:00:00.000000', 0, 1),
(410015, 110005, 'SC2606010005', 5010, '水泵总成', 26240, 1002, '测试工厂C', 'https://example.com/sku/5010', 'WP-10101', '宝马3系', '1', 'PCS', '水泵*1', 1200.0000, '25*20*18', 'WP-10101-T', 20, 5, 120.0000, 80, 28, 1, 100, 'admin', '2026-06-01 16:00:00.000000', 0, 1);
