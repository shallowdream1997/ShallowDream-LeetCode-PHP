-- ==============================================================
-- 自营转寄卖招募清单测试数据
-- 状态覆盖: 10-待发布, 20-招募中, 25-已抢完, 30-无人申请已回收,
--           50-分配中, 60-清单完成, 100-作废
-- 每个状态生成5条数据，共35条
-- 寄卖商 supplierId=2586, groupId=1
-- 分类 categoryId=26240, 全路径=10312,14707,14708,26240
-- 注意: 主键ID为占位符，请替换为实际雪花ID
-- ==============================================================

-- ==============================================================
-- 先清理已存在的测试数据（按子表到主表顺序）
-- ==============================================================
DELETE FROM scms_consignment_recruit_list_sku WHERE id >= 400000;
DELETE FROM scms_consignment_recruit_apply WHERE id >= 300000;
DELETE FROM scms_consignment_recruit_publish WHERE id >= 200000;
DELETE FROM scms_consignment_recruit_list WHERE id >= 100000;

-- ==============================================================

-- ==============================================================
-- 1. scms_consignment_recruit_list 招募清单主表
-- ==============================================================

-- ==================== 1.1 待发布(10) ====================
-- 已组单但未发布，publish_begin_time在未来
INSERT INTO scms_consignment_recruit_list (id, recruit_no, factory_id, factory_name,
    category_id, category_full_path_id, sku_count, file_url, estimated_cost,
    estimated_month_sale_qty, estimated_month_sale_amount, avg_moq,
    supplier_apply_count, list_status, list_type, group_time,
    publish_begin_time, apply_begin_time, apply_end_time, publish_end_time,
    tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(101001, 'SC2606260001', 1000, '测试工厂A',
 26240, '10312,14707,14708,26240', 3,
 'https://platform-files.oss-cn-shenzhen.aliyuncs.com/pa/purchase/qd20251028/QD202510270009.xlsx?Expires=3338447630&OSSAccessKeyId=LTAI5tE6bfvwvW4J3DUgRDdx&Signature=Xnr3a%2Fm2fMC3LPpvqomzKSSPxMk%3D',
 1550.00, 257, 12485.00, 26.67,
 0, 10, 1, '2026-06-25 16:30:00.000000',
 '2026-06-28 14:00:00.000000', '2026-06-28 14:00:00.000000', '2026-06-28 21:00:00.000000', '2026-06-28 23:59:59.000000',
 0, 0, 0, 'admin', 'admin', '2026-06-25 16:30:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list (id, recruit_no, factory_id, factory_name,
    category_id, category_full_path_id, sku_count, file_url, estimated_cost,
    estimated_month_sale_qty, estimated_month_sale_amount, avg_moq,
    supplier_apply_count, list_status, list_type, group_time,
    publish_begin_time, apply_begin_time, apply_end_time, publish_end_time,
    tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(101002, 'SC2606260002', 1000, '测试工厂A',
 26240, '10312,14707,14708,26240', 3,
 'https://platform-files.oss-cn-shenzhen.aliyuncs.com/pa/purchase/qd20251028/QD202510270009.xlsx?Expires=3338447630&OSSAccessKeyId=LTAI5tE6bfvwvW4J3DUgRDdx&Signature=Xnr3a%2Fm2fMC3LPpvqomzKSSPxMk%3D',
 1225.00, 265, 10520.00, 23.33,
 0, 10, 1, '2026-06-25 17:00:00.000000',
 '2026-06-28 14:00:00.000000', '2026-06-28 14:00:00.000000', '2026-06-28 21:00:00.000000', '2026-06-28 23:59:59.000000',
 0, 0, 0, 'admin', 'admin', '2026-06-25 17:00:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list (id, recruit_no, factory_id, factory_name,
    category_id, category_full_path_id, sku_count, file_url, estimated_cost,
    estimated_month_sale_qty, estimated_month_sale_amount, avg_moq,
    supplier_apply_count, list_status, list_type, group_time,
    publish_begin_time, apply_begin_time, apply_end_time, publish_end_time,
    tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(101003, 'SC2606260003', 1000, '测试工厂A',
 26240, '10312,14707,14708,26240', 3,
 'https://platform-files.oss-cn-shenzhen.aliyuncs.com/pa/purchase/qd20251028/QD202510270009.xlsx?Expires=3338447630&OSSAccessKeyId=LTAI5tE6bfvwvW4J3DUgRDdx&Signature=Xnr3a%2Fm2fMC3LPpvqomzKSSPxMk%3D',
 1980.00, 230, 13200.00, 30.00,
 0, 10, 1, '2026-06-25 17:30:00.000000',
 '2026-06-28 14:00:00.000000', '2026-06-28 14:00:00.000000', '2026-06-28 21:00:00.000000', '2026-06-28 23:59:59.000000',
 0, 0, 0, 'admin', 'admin', '2026-06-25 17:30:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list (id, recruit_no, factory_id, factory_name,
    category_id, category_full_path_id, sku_count, file_url, estimated_cost,
    estimated_month_sale_qty, estimated_month_sale_amount, avg_moq,
    supplier_apply_count, list_status, list_type, group_time,
    publish_begin_time, apply_begin_time, apply_end_time, publish_end_time,
    tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(101004, 'SC2606260004', 1000, '测试工厂A',
 26240, '10312,14707,14708,26240', 3,
 'https://platform-files.oss-cn-shenzhen.aliyuncs.com/pa/purchase/qd20251028/QD202510270009.xlsx?Expires=3338447630&OSSAccessKeyId=LTAI5tE6bfvwvW4J3DUgRDdx&Signature=Xnr3a%2Fm2fMC3LPpvqomzKSSPxMk%3D',
 1820.00, 185, 10950.00, 23.33,
 0, 10, 1, '2026-06-25 18:00:00.000000',
 '2026-06-28 14:00:00.000000', '2026-06-28 14:00:00.000000', '2026-06-28 21:00:00.000000', '2026-06-28 23:59:59.000000',
 0, 0, 0, 'admin', 'admin', '2026-06-25 18:00:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list (id, recruit_no, factory_id, factory_name,
    category_id, category_full_path_id, sku_count, file_url, estimated_cost,
    estimated_month_sale_qty, estimated_month_sale_amount, avg_moq,
    supplier_apply_count, list_status, list_type, group_time,
    publish_begin_time, apply_begin_time, apply_end_time, publish_end_time,
    tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(101005, 'SC2606260005', 1000, '测试工厂A',
 26240, '10312,14707,14708,26240', 3,
 'https://platform-files.oss-cn-shenzhen.aliyuncs.com/pa/purchase/qd20251028/QD202510270009.xlsx?Expires=3338447630&OSSAccessKeyId=LTAI5tE6bfvwvW4J3DUgRDdx&Signature=Xnr3a%2Fm2fMC3LPpvqomzKSSPxMk%3D',
 1420.00, 220, 9850.00, 20.00,
 0, 10, 1, '2026-06-25 18:30:00.000000',
 '2026-06-28 14:00:00.000000', '2026-06-28 14:00:00.000000', '2026-06-28 21:00:00.000000', '2026-06-28 23:59:59.000000',
 0, 0, 0, 'admin', 'admin', '2026-06-25 18:30:00.000000', 0, 1);

-- ==================== 1.2 招募中(20) ====================
-- 已发布正在招募，apply_end_time > now
INSERT INTO scms_consignment_recruit_list (id, recruit_no, factory_id, factory_name,
    category_id, category_full_path_id, sku_count, file_url, estimated_cost,
    estimated_month_sale_qty, estimated_month_sale_amount, avg_moq,
    supplier_apply_count, list_status, list_type, group_time,
    publish_begin_time, apply_begin_time, apply_end_time, publish_end_time,
    tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(102001, 'SC2606190001', 1000, '测试工厂A',
 26240, '10312,14707,14708,26240', 3,
 'https://platform-files.oss-cn-shenzhen.aliyuncs.com/pa/purchase/qd20251028/QD202510270009.xlsx?Expires=3338447630&OSSAccessKeyId=LTAI5tE6bfvwvW4J3DUgRDdx&Signature=Xnr3a%2Fm2fMC3LPpvqomzKSSPxMk%3D',
 1550.00, 257, 12485.00, 26.67,
 2, 20, 1, '2026-06-18 16:00:00.000000',
 '2026-06-19 14:00:00.000000', '2026-06-19 14:00:00.000000', '2026-06-21 21:00:00.000000', '2026-06-21 23:59:59.000000',
 0, 0, 0, 'admin', 'admin', '2026-06-18 16:00:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list (id, recruit_no, factory_id, factory_name,
    category_id, category_full_path_id, sku_count, file_url, estimated_cost,
    estimated_month_sale_qty, estimated_month_sale_amount, avg_moq,
    supplier_apply_count, list_status, list_type, group_time,
    publish_begin_time, apply_begin_time, apply_end_time, publish_end_time,
    tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(102002, 'SC2606190002', 1000, '测试工厂A',
 26240, '10312,14707,14708,26240', 3,
 'https://platform-files.oss-cn-shenzhen.aliyuncs.com/pa/purchase/qd20251028/QD202510270009.xlsx?Expires=3338447630&OSSAccessKeyId=LTAI5tE6bfvwvW4J3DUgRDdx&Signature=Xnr3a%2Fm2fMC3LPpvqomzKSSPxMk%3D',
 1225.00, 265, 10520.00, 23.33,
 3, 20, 1, '2026-06-18 16:30:00.000000',
 '2026-06-19 14:00:00.000000', '2026-06-19 14:00:00.000000', '2026-06-21 21:00:00.000000', '2026-06-21 23:59:59.000000',
 0, 0, 0, 'admin', 'admin', '2026-06-18 16:30:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list (id, recruit_no, factory_id, factory_name,
    category_id, category_full_path_id, sku_count, file_url, estimated_cost,
    estimated_month_sale_qty, estimated_month_sale_amount, avg_moq,
    supplier_apply_count, list_status, list_type, group_time,
    publish_begin_time, apply_begin_time, apply_end_time, publish_end_time,
    tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(102003, 'SC2606190003', 1000, '测试工厂A',
 26240, '10312,14707,14708,26240', 3,
 'https://platform-files.oss-cn-shenzhen.aliyuncs.com/pa/purchase/qd20251028/QD202510270009.xlsx?Expires=3338447630&OSSAccessKeyId=LTAI5tE6bfvwvW4J3DUgRDdx&Signature=Xnr3a%2Fm2fMC3LPpvqomzKSSPxMk%3D',
 1980.00, 230, 13200.00, 30.00,
 1, 20, 1, '2026-06-18 17:00:00.000000',
 '2026-06-19 14:00:00.000000', '2026-06-19 14:00:00.000000', '2026-06-21 21:00:00.000000', '2026-06-21 23:59:59.000000',
 0, 0, 0, 'admin', 'admin', '2026-06-18 17:00:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list (id, recruit_no, factory_id, factory_name,
    category_id, category_full_path_id, sku_count, file_url, estimated_cost,
    estimated_month_sale_qty, estimated_month_sale_amount, avg_moq,
    supplier_apply_count, list_status, list_type, group_time,
    publish_begin_time, apply_begin_time, apply_end_time, publish_end_time,
    tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(102004, 'SC2606190004', 1000, '测试工厂A',
 26240, '10312,14707,14708,26240', 3,
 'https://platform-files.oss-cn-shenzhen.aliyuncs.com/pa/purchase/qd20251028/QD202510270009.xlsx?Expires=3338447630&OSSAccessKeyId=LTAI5tE6bfvwvW4J3DUgRDdx&Signature=Xnr3a%2Fm2fMC3LPpvqomzKSSPxMk%3D',
 1820.00, 185, 10950.00, 23.33,
 2, 20, 1, '2026-06-18 17:30:00.000000',
 '2026-06-19 14:00:00.000000', '2026-06-19 14:00:00.000000', '2026-06-21 21:00:00.000000', '2026-06-21 23:59:59.000000',
 0, 0, 0, 'admin', 'admin', '2026-06-18 17:30:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list (id, recruit_no, factory_id, factory_name,
    category_id, category_full_path_id, sku_count, file_url, estimated_cost,
    estimated_month_sale_qty, estimated_month_sale_amount, avg_moq,
    supplier_apply_count, list_status, list_type, group_time,
    publish_begin_time, apply_begin_time, apply_end_time, publish_end_time,
    tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(102005, 'SC2606190005', 1000, '测试工厂A',
 26240, '10312,14707,14708,26240', 3,
 'https://platform-files.oss-cn-shenzhen.aliyuncs.com/pa/purchase/qd20251028/QD202510270009.xlsx?Expires=3338447630&OSSAccessKeyId=LTAI5tE6bfvwvW4J3DUgRDdx&Signature=Xnr3a%2Fm2fMC3LPpvqomzKSSPxMk%3D',
 1420.00, 220, 9850.00, 20.00,
 2, 20, 1, '2026-06-18 18:00:00.000000',
 '2026-06-19 14:00:00.000000', '2026-06-19 14:00:00.000000', '2026-06-21 21:00:00.000000', '2026-06-21 23:59:59.000000',
 0, 0, 0, 'admin', 'admin', '2026-06-18 18:00:00.000000', 0, 1);

-- ==================== 1.3 已抢完(25) ====================
-- 竞争池已满(10人), 提前结束申请
INSERT INTO scms_consignment_recruit_list (id, recruit_no, factory_id, factory_name,
    category_id, category_full_path_id, sku_count, file_url, estimated_cost,
    estimated_month_sale_qty, estimated_month_sale_amount, avg_moq,
    supplier_apply_count, list_status, list_type, group_time,
    publish_begin_time, apply_begin_time, apply_end_time, publish_end_time,
    tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(102501, 'SC2606160001', 1000, '测试工厂A',
 26240, '10312,14707,14708,26240', 3,
 'https://platform-files.oss-cn-shenzhen.aliyuncs.com/pa/purchase/qd20251028/QD202510270009.xlsx?Expires=3338447630&OSSAccessKeyId=LTAI5tE6bfvwvW4J3DUgRDdx&Signature=Xnr3a%2Fm2fMC3LPpvqomzKSSPxMk%3D',
 1680.00, 310, 15350.00, 26.67,
 10, 25, 1, '2026-06-15 16:00:00.000000',
 '2026-06-16 14:00:00.000000', '2026-06-16 14:00:00.000000', '2026-06-16 18:30:00.000000', '2026-06-16 23:59:59.000000',
 0, 0, 0, 'admin', 'admin', '2026-06-15 16:00:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list (id, recruit_no, factory_id, factory_name,
    category_id, category_full_path_id, sku_count, file_url, estimated_cost,
    estimated_month_sale_qty, estimated_month_sale_amount, avg_moq,
    supplier_apply_count, list_status, list_type, group_time,
    publish_begin_time, apply_begin_time, apply_end_time, publish_end_time,
    tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(102502, 'SC2606160002', 1000, '测试工厂A',
 26240, '10312,14707,14708,26240', 3,
 'https://platform-files.oss-cn-shenzhen.aliyuncs.com/pa/purchase/qd20251028/QD202510270009.xlsx?Expires=3338447630&OSSAccessKeyId=LTAI5tE6bfvwvW4J3DUgRDdx&Signature=Xnr3a%2Fm2fMC3LPpvqomzKSSPxMk%3D',
 1850.00, 290, 14200.00, 30.00,
 10, 25, 1, '2026-06-15 16:30:00.000000',
 '2026-06-16 14:00:00.000000', '2026-06-16 14:00:00.000000', '2026-06-16 17:15:00.000000', '2026-06-16 23:59:59.000000',
 0, 0, 0, 'admin', 'admin', '2026-06-15 16:30:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list (id, recruit_no, factory_id, factory_name,
    category_id, category_full_path_id, sku_count, file_url, estimated_cost,
    estimated_month_sale_qty, estimated_month_sale_amount, avg_moq,
    supplier_apply_count, list_status, list_type, group_time,
    publish_begin_time, apply_begin_time, apply_end_time, publish_end_time,
    tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(102503, 'SC2606160003', 1000, '测试工厂A',
 26240, '10312,14707,14708,26240', 3,
 'https://platform-files.oss-cn-shenzhen.aliyuncs.com/pa/purchase/qd20251028/QD202510270009.xlsx?Expires=3338447630&OSSAccessKeyId=LTAI5tE6bfvwvW4J3DUgRDdx&Signature=Xnr3a%2Fm2fMC3LPpvqomzKSSPxMk%3D',
 1420.00, 185, 8950.00, 23.33,
 10, 25, 1, '2026-06-15 17:00:00.000000',
 '2026-06-16 14:00:00.000000', '2026-06-16 14:00:00.000000', '2026-06-16 19:00:00.000000', '2026-06-16 23:59:59.000000',
 0, 0, 0, 'admin', 'admin', '2026-06-15 17:00:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list (id, recruit_no, factory_id, factory_name,
    category_id, category_full_path_id, sku_count, file_url, estimated_cost,
    estimated_month_sale_qty, estimated_month_sale_amount, avg_moq,
    supplier_apply_count, list_status, list_type, group_time,
    publish_begin_time, apply_begin_time, apply_end_time, publish_end_time,
    tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(102504, 'SC2606160004', 1000, '测试工厂A',
 26240, '10312,14707,14708,26240', 3,
 'https://platform-files.oss-cn-shenzhen.aliyuncs.com/pa/purchase/qd20251028/QD202510270009.xlsx?Expires=3338447630&OSSAccessKeyId=LTAI5tE6bfvwvW4J3DUgRDdx&Signature=Xnr3a%2Fm2fMC3LPpvqomzKSSPxMk%3D',
 1960.00, 275, 13850.00, 26.67,
 10, 25, 1, '2026-06-15 17:30:00.000000',
 '2026-06-16 14:00:00.000000', '2026-06-16 14:00:00.000000', '2026-06-16 18:45:00.000000', '2026-06-16 23:59:59.000000',
 0, 0, 0, 'admin', 'admin', '2026-06-15 17:30:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list (id, recruit_no, factory_id, factory_name,
    category_id, category_full_path_id, sku_count, file_url, estimated_cost,
    estimated_month_sale_qty, estimated_month_sale_amount, avg_moq,
    supplier_apply_count, list_status, list_type, group_time,
    publish_begin_time, apply_begin_time, apply_end_time, publish_end_time,
    tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(102505, 'SC2606160005', 1000, '测试工厂A',
 26240, '10312,14707,14708,26240', 3,
 'https://platform-files.oss-cn-shenzhen.aliyuncs.com/pa/purchase/qd20251028/QD202510270009.xlsx?Expires=3338447630&OSSAccessKeyId=LTAI5tE6bfvwvW4J3DUgRDdx&Signature=Xnr3a%2Fm2fMC3LPpvqomzKSSPxMk%3D',
 1100.00, 210, 9250.00, 20.00,
 10, 25, 1, '2026-06-15 18:00:00.000000',
 '2026-06-16 14:00:00.000000', '2026-06-16 14:00:00.000000', '2026-06-16 19:20:00.000000', '2026-06-16 23:59:59.000000',
 0, 0, 0, 'admin', 'admin', '2026-06-15 18:00:00.000000', 0, 1);

-- ==================== 1.4 无人申请已回收(30) ====================
-- 发布后无人申请，已回收
INSERT INTO scms_consignment_recruit_list (id, recruit_no, factory_id, factory_name,
    category_id, category_full_path_id, sku_count, file_url, estimated_cost,
    estimated_month_sale_qty, estimated_month_sale_amount, avg_moq,
    supplier_apply_count, list_status, list_type, group_time,
    publish_begin_time, apply_begin_time, apply_end_time, publish_end_time,
    tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(103001, 'SC2606120001', 1001, '测试工厂B',
 26240, '10312,14707,14708,26240', 3,
 'https://platform-files.oss-cn-shenzhen.aliyuncs.com/pa/purchase/qd20251028/QD202510270009.xlsx?Expires=3338447630&OSSAccessKeyId=LTAI5tE6bfvwvW4J3DUgRDdx&Signature=Xnr3a%2Fm2fMC3LPpvqomzKSSPxMk%3D',
 1350.00, 175, 8250.00, 23.33,
 0, 30, 1, '2026-06-11 15:00:00.000000',
 '2026-06-12 14:00:00.000000', '2026-06-12 14:00:00.000000', '2026-06-12 21:00:00.000000', '2026-06-13 23:59:59.000000',
 0, 0, 0, 'admin', 'admin', '2026-06-11 15:00:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list (id, recruit_no, factory_id, factory_name,
    category_id, category_full_path_id, sku_count, file_url, estimated_cost,
    estimated_month_sale_qty, estimated_month_sale_amount, avg_moq,
    supplier_apply_count, list_status, list_type, group_time,
    publish_begin_time, apply_begin_time, apply_end_time, publish_end_time,
    tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(103002, 'SC2606120002', 1001, '测试工厂B',
 26240, '10312,14707,14708,26240', 3,
 'https://platform-files.oss-cn-shenzhen.aliyuncs.com/pa/purchase/qd20251028/QD202510270009.xlsx?Expires=3338447630&OSSAccessKeyId=LTAI5tE6bfvwvW4J3DUgRDdx&Signature=Xnr3a%2Fm2fMC3LPpvqomzKSSPxMk%3D',
 1650.00, 195, 9350.00, 20.00,
 0, 30, 1, '2026-06-11 15:30:00.000000',
 '2026-06-12 14:00:00.000000', '2026-06-12 14:00:00.000000', '2026-06-12 21:00:00.000000', '2026-06-13 23:59:59.000000',
 0, 0, 0, 'admin', 'admin', '2026-06-11 15:30:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list (id, recruit_no, factory_id, factory_name,
    category_id, category_full_path_id, sku_count, file_url, estimated_cost,
    estimated_month_sale_qty, estimated_month_sale_amount, avg_moq,
    supplier_apply_count, list_status, list_type, group_time,
    publish_begin_time, apply_begin_time, apply_end_time, publish_end_time,
    tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(103003, 'SC2606120003', 1001, '测试工厂B',
 26240, '10312,14707,14708,26240', 3,
 'https://platform-files.oss-cn-shenzhen.aliyuncs.com/pa/purchase/qd20251028/QD202510270009.xlsx?Expires=3338447630&OSSAccessKeyId=LTAI5tE6bfvwvW4J3DUgRDdx&Signature=Xnr3a%2Fm2fMC3LPpvqomzKSSPxMk%3D',
 1200.00, 150, 7200.00, 26.67,
 0, 30, 1, '2026-06-11 16:00:00.000000',
 '2026-06-12 14:00:00.000000', '2026-06-12 14:00:00.000000', '2026-06-12 21:00:00.000000', '2026-06-13 23:59:59.000000',
 0, 0, 0, 'admin', 'admin', '2026-06-11 16:00:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list (id, recruit_no, factory_id, factory_name,
    category_id, category_full_path_id, sku_count, file_url, estimated_cost,
    estimated_month_sale_qty, estimated_month_sale_amount, avg_moq,
    supplier_apply_count, list_status, list_type, group_time,
    publish_begin_time, apply_begin_time, apply_end_time, publish_end_time,
    tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(103004, 'SC2606120004', 1001, '测试工厂B',
 26240, '10312,14707,14708,26240', 3,
 'https://platform-files.oss-cn-shenzhen.aliyuncs.com/pa/purchase/qd20251028/QD202510270009.xlsx?Expires=3338447630&OSSAccessKeyId=LTAI5tE6bfvwvW4J3DUgRDdx&Signature=Xnr3a%2Fm2fMC3LPpvqomzKSSPxMk%3D',
 1480.00, 165, 7980.00, 23.33,
 0, 30, 1, '2026-06-11 16:30:00.000000',
 '2026-06-12 14:00:00.000000', '2026-06-12 14:00:00.000000', '2026-06-12 21:00:00.000000', '2026-06-13 23:59:59.000000',
 0, 0, 0, 'admin', 'admin', '2026-06-11 16:30:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list (id, recruit_no, factory_id, factory_name,
    category_id, category_full_path_id, sku_count, file_url, estimated_cost,
    estimated_month_sale_qty, estimated_month_sale_amount, avg_moq,
    supplier_apply_count, list_status, list_type, group_time,
    publish_begin_time, apply_begin_time, apply_end_time, publish_end_time,
    tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(103005, 'SC2606120005', 1001, '测试工厂B',
 26240, '10312,14707,14708,26240', 3,
 'https://platform-files.oss-cn-shenzhen.aliyuncs.com/pa/purchase/qd20251028/QD202510270009.xlsx?Expires=3338447630&OSSAccessKeyId=LTAI5tE6bfvwvW4J3DUgRDdx&Signature=Xnr3a%2Fm2fMC3LPpvqomzKSSPxMk%3D',
 1750.00, 205, 10150.00, 20.00,
 0, 30, 1, '2026-06-11 17:00:00.000000',
 '2026-06-12 14:00:00.000000', '2026-06-12 14:00:00.000000', '2026-06-12 21:00:00.000000', '2026-06-13 23:59:59.000000',
 0, 0, 0, 'admin', 'admin', '2026-06-11 17:00:00.000000', 0, 1);

-- ==================== 1.5 分配中(50) ====================
-- 已分配/评选进行中, supplier 2586 获胜
INSERT INTO scms_consignment_recruit_list (id, recruit_no, factory_id, factory_name,
    category_id, category_full_path_id, sku_count, file_url, estimated_cost,
    estimated_month_sale_qty, estimated_month_sale_amount, avg_moq,
    supplier_apply_count, list_status, list_type, group_time,
    publish_begin_time, apply_begin_time, apply_end_time, publish_end_time,
    award_time, award_supplier_id, award_group_id, award_apply_id, award_ce_bill_no, award_by,
    tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(105001, 'SC2606090001', 1000, '测试工厂A',
 26240, '10312,14707,14708,26240', 3,
 'https://platform-files.oss-cn-shenzhen.aliyuncs.com/pa/purchase/qd20251028/QD202510270009.xlsx?Expires=3338447630&OSSAccessKeyId=LTAI5tE6bfvwvW4J3DUgRDdx&Signature=Xnr3a%2Fm2fMC3LPpvqomzKSSPxMk%3D',
 1550.00, 257, 12485.00, 26.67,
 3, 50, 1, '2026-06-08 16:00:00.000000',
 '2026-06-09 14:00:00.000000', '2026-06-09 14:00:00.000000', '2026-06-09 21:00:00.000000', '2026-06-12 23:59:59.000000',
 '2026-06-10 10:00:00.000000', 2586, 1, 305001, 'CE20260610001', 'system',
 0, 0, 0, 'admin', 'admin', '2026-06-08 16:00:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list (id, recruit_no, factory_id, factory_name,
    category_id, category_full_path_id, sku_count, file_url, estimated_cost,
    estimated_month_sale_qty, estimated_month_sale_amount, avg_moq,
    supplier_apply_count, list_status, list_type, group_time,
    publish_begin_time, apply_begin_time, apply_end_time, publish_end_time,
    award_time, award_supplier_id, award_group_id, award_apply_id, award_ce_bill_no, award_by,
    tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(105002, 'SC2606090002', 1000, '测试工厂A',
 26240, '10312,14707,14708,26240', 3,
 'https://platform-files.oss-cn-shenzhen.aliyuncs.com/pa/purchase/qd20251028/QD202510270009.xlsx?Expires=3338447630&OSSAccessKeyId=LTAI5tE6bfvwvW4J3DUgRDdx&Signature=Xnr3a%2Fm2fMC3LPpvqomzKSSPxMk%3D',
 1225.00, 265, 10520.00, 23.33,
 4, 50, 1, '2026-06-08 16:30:00.000000',
 '2026-06-09 14:00:00.000000', '2026-06-09 14:00:00.000000', '2026-06-09 21:00:00.000000', '2026-06-12 23:59:59.000000',
 '2026-06-10 10:15:00.000000', 2586, 1, 305002, 'CE20260610002', 'system',
 0, 0, 0, 'admin', 'admin', '2026-06-08 16:30:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list (id, recruit_no, factory_id, factory_name,
    category_id, category_full_path_id, sku_count, file_url, estimated_cost,
    estimated_month_sale_qty, estimated_month_sale_amount, avg_moq,
    supplier_apply_count, list_status, list_type, group_time,
    publish_begin_time, apply_begin_time, apply_end_time, publish_end_time,
    award_time, award_supplier_id, award_group_id, award_apply_id, award_ce_bill_no, award_by,
    tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(105003, 'SC2606090003', 1000, '测试工厂A',
 26240, '10312,14707,14708,26240', 3,
 'https://platform-files.oss-cn-shenzhen.aliyuncs.com/pa/purchase/qd20251028/QD202510270009.xlsx?Expires=3338447630&OSSAccessKeyId=LTAI5tE6bfvwvW4J3DUgRDdx&Signature=Xnr3a%2Fm2fMC3LPpvqomzKSSPxMk%3D',
 1980.00, 230, 13200.00, 30.00,
 3, 50, 1, '2026-06-08 17:00:00.000000',
 '2026-06-09 14:00:00.000000', '2026-06-09 14:00:00.000000', '2026-06-09 21:00:00.000000', '2026-06-12 23:59:59.000000',
 '2026-06-10 10:30:00.000000', 2586, 1, 305003, 'CE20260610003', 'system',
 0, 0, 0, 'admin', 'admin', '2026-06-08 17:00:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list (id, recruit_no, factory_id, factory_name,
    category_id, category_full_path_id, sku_count, file_url, estimated_cost,
    estimated_month_sale_qty, estimated_month_sale_amount, avg_moq,
    supplier_apply_count, list_status, list_type, group_time,
    publish_begin_time, apply_begin_time, apply_end_time, publish_end_time,
    award_time, award_supplier_id, award_group_id, award_apply_id, award_ce_bill_no, award_by,
    tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(105004, 'SC2606090004', 1000, '测试工厂A',
 26240, '10312,14707,14708,26240', 3,
 'https://platform-files.oss-cn-shenzhen.aliyuncs.com/pa/purchase/qd20251028/QD202510270009.xlsx?Expires=3338447630&OSSAccessKeyId=LTAI5tE6bfvwvW4J3DUgRDdx&Signature=Xnr3a%2Fm2fMC3LPpvqomzKSSPxMk%3D',
 1820.00, 185, 10950.00, 23.33,
 3, 50, 1, '2026-06-08 17:30:00.000000',
 '2026-06-09 14:00:00.000000', '2026-06-09 14:00:00.000000', '2026-06-09 21:00:00.000000', '2026-06-12 23:59:59.000000',
 '2026-06-10 10:45:00.000000', 2586, 1, 305004, 'CE20260610004', 'system',
 0, 0, 0, 'admin', 'admin', '2026-06-08 17:30:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list (id, recruit_no, factory_id, factory_name,
    category_id, category_full_path_id, sku_count, file_url, estimated_cost,
    estimated_month_sale_qty, estimated_month_sale_amount, avg_moq,
    supplier_apply_count, list_status, list_type, group_time,
    publish_begin_time, apply_begin_time, apply_end_time, publish_end_time,
    award_time, award_supplier_id, award_group_id, award_apply_id, award_ce_bill_no, award_by,
    tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(105005, 'SC2606090005', 1000, '测试工厂A',
 26240, '10312,14707,14708,26240', 3,
 'https://platform-files.oss-cn-shenzhen.aliyuncs.com/pa/purchase/qd20251028/QD202510270009.xlsx?Expires=3338447630&OSSAccessKeyId=LTAI5tE6bfvwvW4J3DUgRDdx&Signature=Xnr3a%2Fm2fMC3LPpvqomzKSSPxMk%3D',
 1420.00, 220, 9850.00, 20.00,
 3, 50, 1, '2026-06-08 18:00:00.000000',
 '2026-06-09 14:00:00.000000', '2026-06-09 14:00:00.000000', '2026-06-09 21:00:00.000000', '2026-06-12 23:59:59.000000',
 '2026-06-10 11:00:00.000000', 2586, 1, 305005, 'CE20260610005', 'system',
 0, 0, 0, 'admin', 'admin', '2026-06-08 18:00:00.000000', 0, 1);

-- ==================== 1.6 清单完成(60) ====================
-- 已完成整个流程，所有数据完整
INSERT INTO scms_consignment_recruit_list (id, recruit_no, factory_id, factory_name,
    category_id, category_full_path_id, sku_count, file_url, estimated_cost,
    estimated_month_sale_qty, estimated_month_sale_amount, avg_moq,
    supplier_apply_count, list_status, list_type, group_time,
    publish_begin_time, apply_begin_time, apply_end_time, publish_end_time,
    award_time, award_supplier_id, award_group_id, award_apply_id, award_ce_bill_no, award_by,
    tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(106001, 'SC2606020001', 1000, '测试工厂A',
 26240, '10312,14707,14708,26240', 3,
 'https://platform-files.oss-cn-shenzhen.aliyuncs.com/pa/purchase/qd20251028/QD202510270009.xlsx?Expires=3338447630&OSSAccessKeyId=LTAI5tE6bfvwvW4J3DUgRDdx&Signature=Xnr3a%2Fm2fMC3LPpvqomzKSSPxMk%3D',
 1550.00, 257, 12485.00, 26.67,
 3, 60, 1, '2026-06-01 15:00:00.000000',
 '2026-06-02 14:00:00.000000', '2026-06-02 14:00:00.000000', '2026-06-02 21:00:00.000000', '2026-06-09 23:59:59.000000',
 '2026-06-03 10:00:00.000000', 2586, 1, 306001, 'CE20260603001', 'system',
 0, 0, 0, 'admin', 'admin', '2026-06-01 15:00:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list (id, recruit_no, factory_id, factory_name,
    category_id, category_full_path_id, sku_count, file_url, estimated_cost,
    estimated_month_sale_qty, estimated_month_sale_amount, avg_moq,
    supplier_apply_count, list_status, list_type, group_time,
    publish_begin_time, apply_begin_time, apply_end_time, publish_end_time,
    award_time, award_supplier_id, award_group_id, award_apply_id, award_ce_bill_no, award_by,
    tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(106002, 'SC2606020002', 1000, '测试工厂A',
 26240, '10312,14707,14708,26240', 3,
 'https://platform-files.oss-cn-shenzhen.aliyuncs.com/pa/purchase/qd20251028/QD202510270009.xlsx?Expires=3338447630&OSSAccessKeyId=LTAI5tE6bfvwvW4J3DUgRDdx&Signature=Xnr3a%2Fm2fMC3LPpvqomzKSSPxMk%3D',
 1225.00, 265, 10520.00, 23.33,
 4, 60, 1, '2026-06-01 15:30:00.000000',
 '2026-06-02 14:00:00.000000', '2026-06-02 14:00:00.000000', '2026-06-02 21:00:00.000000', '2026-06-09 23:59:59.000000',
 '2026-06-03 10:15:00.000000', 2586, 1, 306002, 'CE20260603002', 'system',
 0, 0, 0, 'admin', 'admin', '2026-06-01 15:30:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list (id, recruit_no, factory_id, factory_name,
    category_id, category_full_path_id, sku_count, file_url, estimated_cost,
    estimated_month_sale_qty, estimated_month_sale_amount, avg_moq,
    supplier_apply_count, list_status, list_type, group_time,
    publish_begin_time, apply_begin_time, apply_end_time, publish_end_time,
    award_time, award_supplier_id, award_group_id, award_apply_id, award_ce_bill_no, award_by,
    tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(106003, 'SC2606020003', 1000, '测试工厂A',
 26240, '10312,14707,14708,26240', 3,
 'https://platform-files.oss-cn-shenzhen.aliyuncs.com/pa/purchase/qd20251028/QD202510270009.xlsx?Expires=3338447630&OSSAccessKeyId=LTAI5tE6bfvwvW4J3DUgRDdx&Signature=Xnr3a%2Fm2fMC3LPpvqomzKSSPxMk%3D',
 1980.00, 230, 13200.00, 30.00,
 2, 60, 1, '2026-06-01 16:00:00.000000',
 '2026-06-02 14:00:00.000000', '2026-06-02 14:00:00.000000', '2026-06-02 21:00:00.000000', '2026-06-09 23:59:59.000000',
 '2026-06-03 10:30:00.000000', 2586, 1, 306003, 'CE20260603003', 'system',
 0, 0, 0, 'admin', 'admin', '2026-06-01 16:00:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list (id, recruit_no, factory_id, factory_name,
    category_id, category_full_path_id, sku_count, file_url, estimated_cost,
    estimated_month_sale_qty, estimated_month_sale_amount, avg_moq,
    supplier_apply_count, list_status, list_type, group_time,
    publish_begin_time, apply_begin_time, apply_end_time, publish_end_time,
    award_time, award_supplier_id, award_group_id, award_apply_id, award_ce_bill_no, award_by,
    tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(106004, 'SC2606020004', 1000, '测试工厂A',
 26240, '10312,14707,14708,26240', 3,
 'https://platform-files.oss-cn-shenzhen.aliyuncs.com/pa/purchase/qd20251028/QD202510270009.xlsx?Expires=3338447630&OSSAccessKeyId=LTAI5tE6bfvwvW4J3DUgRDdx&Signature=Xnr3a%2Fm2fMC3LPpvqomzKSSPxMk%3D',
 1820.00, 185, 10950.00, 23.33,
 3, 60, 1, '2026-06-01 16:30:00.000000',
 '2026-06-02 14:00:00.000000', '2026-06-02 14:00:00.000000', '2026-06-02 21:00:00.000000', '2026-06-09 23:59:59.000000',
 '2026-06-03 10:45:00.000000', 2586, 1, 306004, 'CE20260603004', 'system',
 0, 0, 0, 'admin', 'admin', '2026-06-01 16:30:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list (id, recruit_no, factory_id, factory_name,
    category_id, category_full_path_id, sku_count, file_url, estimated_cost,
    estimated_month_sale_qty, estimated_month_sale_amount, avg_moq,
    supplier_apply_count, list_status, list_type, group_time,
    publish_begin_time, apply_begin_time, apply_end_time, publish_end_time,
    award_time, award_supplier_id, award_group_id, award_apply_id, award_ce_bill_no, award_by,
    tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(106005, 'SC2606020005', 1000, '测试工厂A',
 26240, '10312,14707,14708,26240', 3,
 'https://platform-files.oss-cn-shenzhen.aliyuncs.com/pa/purchase/qd20251028/QD202510270009.xlsx?Expires=3338447630&OSSAccessKeyId=LTAI5tE6bfvwvW4J3DUgRDdx&Signature=Xnr3a%2Fm2fMC3LPpvqomzKSSPxMk%3D',
 1420.00, 220, 9850.00, 20.00,
 3, 60, 1, '2026-06-01 17:00:00.000000',
 '2026-06-02 14:00:00.000000', '2026-06-02 14:00:00.000000', '2026-06-02 21:00:00.000000', '2026-06-09 23:59:59.000000',
 '2026-06-03 11:00:00.000000', 2586, 1, 306005, 'CE20260603005', 'system',
 0, 0, 0, 'admin', 'admin', '2026-06-01 17:00:00.000000', 0, 1);

-- ==================== 1.7 作废(100) ====================
-- 已作废/取消，原因各不相同
INSERT INTO scms_consignment_recruit_list (id, recruit_no, factory_id, factory_name,
    category_id, category_full_path_id, sku_count, file_url, estimated_cost,
    estimated_month_sale_qty, estimated_month_sale_amount, avg_moq,
    supplier_apply_count, list_status, list_type, group_time,
    publish_begin_time, apply_begin_time, apply_end_time, publish_end_time,
    cancel_user_name, cancel_time, cancel_type, cancel_reason,
    tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(110001, 'SC2606010001', 1002, '测试工厂C',
 26240, '10312,14707,14708,26240', 3,
 'https://platform-files.oss-cn-shenzhen.aliyuncs.com/pa/purchase/qd20251028/QD202510270009.xlsx?Expires=3338447630&OSSAccessKeyId=LTAI5tE6bfvwvW4J3DUgRDdx&Signature=Xnr3a%2Fm2fMC3LPpvqomzKSSPxMk%3D',
 1350.00, 175, 8250.00, 23.33,
 0, 100, 1, '2026-06-01 14:00:00.000000',
 '2026-06-02 14:00:00.000000', '2026-06-02 14:00:00.000000', '2026-06-02 21:00:00.000000', '2026-06-02 23:59:59.000000',
 'admin', '2026-06-03 09:00:00.000000', 'manual', '供应商主动放弃，手动作废',
 0, 0, 0, 'admin', 'admin', '2026-06-01 14:00:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list (id, recruit_no, factory_id, factory_name,
    category_id, category_full_path_id, sku_count, file_url, estimated_cost,
    estimated_month_sale_qty, estimated_month_sale_amount, avg_moq,
    supplier_apply_count, list_status, list_type, group_time,
    publish_begin_time, apply_begin_time, apply_end_time, publish_end_time,
    cancel_user_name, cancel_time, cancel_type, cancel_reason,
    tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(110002, 'SC2606010002', 1002, '测试工厂C',
 26240, '10312,14707,14708,26240', 3,
 'https://platform-files.oss-cn-shenzhen.aliyuncs.com/pa/purchase/qd20251028/QD202510270009.xlsx?Expires=3338447630&OSSAccessKeyId=LTAI5tE6bfvwvW4J3DUgRDdx&Signature=Xnr3a%2Fm2fMC3LPpvqomzKSSPxMk%3D',
 1480.00, 195, 9350.00, 20.00,
 2, 100, 1, '2026-06-01 14:30:00.000000',
 '2026-06-02 14:00:00.000000', '2026-06-02 14:00:00.000000', '2026-06-02 21:00:00.000000', '2026-06-02 23:59:59.000000',
 'system', '2026-06-05 09:00:00.000000', 'no_apply', '无人申请超时自动作废',
 0, 0, 0, 'admin', 'admin', '2026-06-01 14:30:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list (id, recruit_no, factory_id, factory_name,
    category_id, category_full_path_id, sku_count, file_url, estimated_cost,
    estimated_month_sale_qty, estimated_month_sale_amount, avg_moq,
    supplier_apply_count, list_status, list_type, group_time,
    publish_begin_time, apply_begin_time, apply_end_time, publish_end_time,
    cancel_user_name, cancel_time, cancel_type, cancel_reason,
    tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(110003, 'SC2606010003', 1002, '测试工厂C',
 26240, '10312,14707,14708,26240', 3,
 'https://platform-files.oss-cn-shenzhen.aliyuncs.com/pa/purchase/qd20251028/QD202510270009.xlsx?Expires=3338447630&OSSAccessKeyId=LTAI5tE6bfvwvW4J3DUgRDdx&Signature=Xnr3a%2Fm2fMC3LPpvqomzKSSPxMk%3D',
 1200.00, 150, 7200.00, 26.67,
 1, 100, 1, '2026-06-01 15:00:00.000000',
 '2026-06-02 14:00:00.000000', '2026-06-02 14:00:00.000000', '2026-06-02 21:00:00.000000', '2026-06-02 23:59:59.000000',
 'admin', '2026-06-03 09:30:00.000000', 'manual', '产品线调整取消发布',
 0, 0, 0, 'admin', 'admin', '2026-06-01 15:00:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list (id, recruit_no, factory_id, factory_name,
    category_id, category_full_path_id, sku_count, file_url, estimated_cost,
    estimated_month_sale_qty, estimated_month_sale_amount, avg_moq,
    supplier_apply_count, list_status, list_type, group_time,
    publish_begin_time, apply_begin_time, apply_end_time, publish_end_time,
    cancel_user_name, cancel_time, cancel_type, cancel_reason,
    tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(110004, 'SC2606010004', 1002, '测试工厂C',
 26240, '10312,14707,14708,26240', 3,
 'https://platform-files.oss-cn-shenzhen.aliyuncs.com/pa/purchase/qd20251028/QD202510270009.xlsx?Expires=3338447630&OSSAccessKeyId=LTAI5tE6bfvwvW4J3DUgRDdx&Signature=Xnr3a%2Fm2fMC3LPpvqomzKSSPxMk%3D',
 1750.00, 205, 10150.00, 20.00,
 3, 100, 1, '2026-06-01 15:30:00.000000',
 '2026-06-02 14:00:00.000000', '2026-06-02 14:00:00.000000', '2026-06-02 21:00:00.000000', '2026-06-02 23:59:59.000000',
 'system', '2026-06-10 09:00:00.000000', 'recycle', '评选后异常自动回收',
 0, 0, 0, 'admin', 'admin', '2026-06-01 15:30:00.000000', 0, 1);

INSERT INTO scms_consignment_recruit_list (id, recruit_no, factory_id, factory_name,
    category_id, category_full_path_id, sku_count, file_url, estimated_cost,
    estimated_month_sale_qty, estimated_month_sale_amount, avg_moq,
    supplier_apply_count, list_status, list_type, group_time,
    publish_begin_time, apply_begin_time, apply_end_time, publish_end_time,
    cancel_user_name, cancel_time, cancel_type, cancel_reason,
    tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(110005, 'SC2606010005', 1002, '测试工厂C',
 26240, '10312,14707,14708,26240', 3,
 'https://platform-files.oss-cn-shenzhen.aliyuncs.com/pa/purchase/qd20251028/QD202510270009.xlsx?Expires=3338447630&OSSAccessKeyId=LTAI5tE6bfvwvW4J3DUgRDdx&Signature=Xnr3a%2Fm2fMC3LPpvqomzKSSPxMk%3D',
 1620.00, 185, 8950.00, 26.67,
 1, 100, 1, '2026-06-01 16:00:00.000000',
 '2026-06-02 14:00:00.000000', '2026-06-02 14:00:00.000000', '2026-06-02 21:00:00.000000', '2026-06-02 23:59:59.000000',
 'admin', '2026-06-04 10:00:00.000000', 'manual', '供应商资格审核不通过',
 0, 0, 0, 'admin', 'admin', '2026-06-01 16:00:00.000000', 0, 1);

-- ==============================================================
-- 2. scms_consignment_recruit_publish 发布记录表
-- ==============================================================

-- 待发布(10) 发布记录 - publish_status同list_status
INSERT INTO scms_consignment_recruit_publish (id, recruit_id, recruit_no, publish_round, publish_status, publish_begin_time, apply_begin_time, apply_end_time, publish_end_time, tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(201001, 101001, 'SC2606260001', 1, 10, '2026-06-28 14:00:00.000000', '2026-06-28 14:00:00.000000', '2026-06-28 21:00:00.000000', '2026-06-28 23:59:59.000000', 0, 0, 0, 'admin', 'admin', '2026-06-26 00:00:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_publish (id, recruit_id, recruit_no, publish_round, publish_status, publish_begin_time, apply_begin_time, apply_end_time, publish_end_time, tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(201002, 101002, 'SC2606260002', 1, 10, '2026-06-28 14:00:00.000000', '2026-06-28 14:00:00.000000', '2026-06-28 21:00:00.000000', '2026-06-28 23:59:59.000000', 0, 0, 0, 'admin', 'admin', '2026-06-26 00:00:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_publish (id, recruit_id, recruit_no, publish_round, publish_status, publish_begin_time, apply_begin_time, apply_end_time, publish_end_time, tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(201003, 101003, 'SC2606260003', 1, 10, '2026-06-28 14:00:00.000000', '2026-06-28 14:00:00.000000', '2026-06-28 21:00:00.000000', '2026-06-28 23:59:59.000000', 0, 0, 0, 'admin', 'admin', '2026-06-26 00:00:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_publish (id, recruit_id, recruit_no, publish_round, publish_status, publish_begin_time, apply_begin_time, apply_end_time, publish_end_time, tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(201004, 101004, 'SC2606260004', 1, 10, '2026-06-28 14:00:00.000000', '2026-06-28 14:00:00.000000', '2026-06-28 21:00:00.000000', '2026-06-28 23:59:59.000000', 0, 0, 0, 'admin', 'admin', '2026-06-26 00:00:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_publish (id, recruit_id, recruit_no, publish_round, publish_status, publish_begin_time, apply_begin_time, apply_end_time, publish_end_time, tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(201005, 101005, 'SC2606260005', 1, 10, '2026-06-28 14:00:00.000000', '2026-06-28 14:00:00.000000', '2026-06-28 21:00:00.000000', '2026-06-28 23:59:59.000000', 0, 0, 0, 'admin', 'admin', '2026-06-26 00:00:00.000000', 0, 1);

-- 招募中(20) 发布记录
INSERT INTO scms_consignment_recruit_publish (id, recruit_id, recruit_no, publish_round, publish_status, publish_begin_time, apply_begin_time, apply_end_time, publish_end_time, tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(202001, 102001, 'SC2606190001', 1, 20, '2026-06-19 14:00:00.000000', '2026-06-19 14:00:00.000000', '2026-06-21 21:00:00.000000', '2026-06-21 23:59:59.000000', 0, 0, 0, 'admin', 'admin', '2026-06-19 14:00:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_publish (id, recruit_id, recruit_no, publish_round, publish_status, publish_begin_time, apply_begin_time, apply_end_time, publish_end_time, tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(202002, 102002, 'SC2606190002', 1, 20, '2026-06-19 14:00:00.000000', '2026-06-19 14:00:00.000000', '2026-06-21 21:00:00.000000', '2026-06-21 23:59:59.000000', 0, 0, 0, 'admin', 'admin', '2026-06-19 14:00:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_publish (id, recruit_id, recruit_no, publish_round, publish_status, publish_begin_time, apply_begin_time, apply_end_time, publish_end_time, tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(202003, 102003, 'SC2606190003', 1, 20, '2026-06-19 14:00:00.000000', '2026-06-19 14:00:00.000000', '2026-06-21 21:00:00.000000', '2026-06-21 23:59:59.000000', 0, 0, 0, 'admin', 'admin', '2026-06-19 14:00:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_publish (id, recruit_id, recruit_no, publish_round, publish_status, publish_begin_time, apply_begin_time, apply_end_time, publish_end_time, tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(202004, 102004, 'SC2606190004', 1, 20, '2026-06-19 14:00:00.000000', '2026-06-19 14:00:00.000000', '2026-06-21 21:00:00.000000', '2026-06-21 23:59:59.000000', 0, 0, 0, 'admin', 'admin', '2026-06-19 14:00:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_publish (id, recruit_id, recruit_no, publish_round, publish_status, publish_begin_time, apply_begin_time, apply_end_time, publish_end_time, tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(202005, 102005, 'SC2606190005', 1, 20, '2026-06-19 14:00:00.000000', '2026-06-19 14:00:00.000000', '2026-06-21 21:00:00.000000', '2026-06-21 23:59:59.000000', 0, 0, 0, 'admin', 'admin', '2026-06-19 14:00:00.000000', 0, 1);

-- 已抢完(25) 发布记录
INSERT INTO scms_consignment_recruit_publish (id, recruit_id, recruit_no, publish_round, publish_status, publish_begin_time, apply_begin_time, apply_end_time, publish_end_time, tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(202501, 102501, 'SC2606160001', 1, 25, '2026-06-16 14:00:00.000000', '2026-06-16 14:00:00.000000', '2026-06-16 18:30:00.000000', '2026-06-16 23:59:59.000000', 0, 0, 0, 'admin', 'admin', '2026-06-16 14:00:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_publish (id, recruit_id, recruit_no, publish_round, publish_status, publish_begin_time, apply_begin_time, apply_end_time, publish_end_time, tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(202502, 102502, 'SC2606160002', 1, 25, '2026-06-16 14:00:00.000000', '2026-06-16 14:00:00.000000', '2026-06-16 17:15:00.000000', '2026-06-16 23:59:59.000000', 0, 0, 0, 'admin', 'admin', '2026-06-16 14:00:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_publish (id, recruit_id, recruit_no, publish_round, publish_status, publish_begin_time, apply_begin_time, apply_end_time, publish_end_time, tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(202503, 102503, 'SC2606160003', 1, 25, '2026-06-16 14:00:00.000000', '2026-06-16 14:00:00.000000', '2026-06-16 19:00:00.000000', '2026-06-16 23:59:59.000000', 0, 0, 0, 'admin', 'admin', '2026-06-16 14:00:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_publish (id, recruit_id, recruit_no, publish_round, publish_status, publish_begin_time, apply_begin_time, apply_end_time, publish_end_time, tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(202504, 102504, 'SC2606160004', 1, 25, '2026-06-16 14:00:00.000000', '2026-06-16 14:00:00.000000', '2026-06-16 18:45:00.000000', '2026-06-16 23:59:59.000000', 0, 0, 0, 'admin', 'admin', '2026-06-16 14:00:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_publish (id, recruit_id, recruit_no, publish_round, publish_status, publish_begin_time, apply_begin_time, apply_end_time, publish_end_time, tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(202505, 102505, 'SC2606160005', 1, 25, '2026-06-16 14:00:00.000000', '2026-06-16 14:00:00.000000', '2026-06-16 19:20:00.000000', '2026-06-16 23:59:59.000000', 0, 0, 0, 'admin', 'admin', '2026-06-16 14:00:00.000000', 0, 1);

-- 无人申请已回收(30) 发布记录
INSERT INTO scms_consignment_recruit_publish (id, recruit_id, recruit_no, publish_round, publish_status, publish_begin_time, apply_begin_time, apply_end_time, publish_end_time, tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(203001, 103001, 'SC2606120001', 1, 30, '2026-06-12 14:00:00.000000', '2026-06-12 14:00:00.000000', '2026-06-12 21:00:00.000000', '2026-06-13 23:59:59.000000', 0, 0, 0, 'admin', 'admin', '2026-06-12 14:00:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_publish (id, recruit_id, recruit_no, publish_round, publish_status, publish_begin_time, apply_begin_time, apply_end_time, publish_end_time, tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(203002, 103002, 'SC2606120002', 1, 30, '2026-06-12 14:00:00.000000', '2026-06-12 14:00:00.000000', '2026-06-12 21:00:00.000000', '2026-06-13 23:59:59.000000', 0, 0, 0, 'admin', 'admin', '2026-06-12 14:00:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_publish (id, recruit_id, recruit_no, publish_round, publish_status, publish_begin_time, apply_begin_time, apply_end_time, publish_end_time, tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(203003, 103003, 'SC2606120003', 1, 30, '2026-06-12 14:00:00.000000', '2026-06-12 14:00:00.000000', '2026-06-12 21:00:00.000000', '2026-06-13 23:59:59.000000', 0, 0, 0, 'admin', 'admin', '2026-06-12 14:00:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_publish (id, recruit_id, recruit_no, publish_round, publish_status, publish_begin_time, apply_begin_time, apply_end_time, publish_end_time, tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(203004, 103004, 'SC2606120004', 1, 30, '2026-06-12 14:00:00.000000', '2026-06-12 14:00:00.000000', '2026-06-12 21:00:00.000000', '2026-06-13 23:59:59.000000', 0, 0, 0, 'admin', 'admin', '2026-06-12 14:00:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_publish (id, recruit_id, recruit_no, publish_round, publish_status, publish_begin_time, apply_begin_time, apply_end_time, publish_end_time, tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(203005, 103005, 'SC2606120005', 1, 30, '2026-06-12 14:00:00.000000', '2026-06-12 14:00:00.000000', '2026-06-12 21:00:00.000000', '2026-06-13 23:59:59.000000', 0, 0, 0, 'admin', 'admin', '2026-06-12 14:00:00.000000', 0, 1);

-- 分配中(50) 发布记录
INSERT INTO scms_consignment_recruit_publish (id, recruit_id, recruit_no, publish_round, publish_status, publish_begin_time, apply_begin_time, apply_end_time, publish_end_time, tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(205001, 105001, 'SC2606090001', 1, 50, '2026-06-09 14:00:00.000000', '2026-06-09 14:00:00.000000', '2026-06-09 21:00:00.000000', '2026-06-12 23:59:59.000000', 0, 0, 0, 'admin', 'admin', '2026-06-09 14:00:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_publish (id, recruit_id, recruit_no, publish_round, publish_status, publish_begin_time, apply_begin_time, apply_end_time, publish_end_time, tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(205002, 105002, 'SC2606090002', 1, 50, '2026-06-09 14:00:00.000000', '2026-06-09 14:00:00.000000', '2026-06-09 21:00:00.000000', '2026-06-12 23:59:59.000000', 0, 0, 0, 'admin', 'admin', '2026-06-09 14:00:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_publish (id, recruit_id, recruit_no, publish_round, publish_status, publish_begin_time, apply_begin_time, apply_end_time, publish_end_time, tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(205003, 105003, 'SC2606090003', 1, 50, '2026-06-09 14:00:00.000000', '2026-06-09 14:00:00.000000', '2026-06-09 21:00:00.000000', '2026-06-12 23:59:59.000000', 0, 0, 0, 'admin', 'admin', '2026-06-09 14:00:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_publish (id, recruit_id, recruit_no, publish_round, publish_status, publish_begin_time, apply_begin_time, apply_end_time, publish_end_time, tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(205004, 105004, 'SC2606090004', 1, 50, '2026-06-09 14:00:00.000000', '2026-06-09 14:00:00.000000', '2026-06-09 21:00:00.000000', '2026-06-12 23:59:59.000000', 0, 0, 0, 'admin', 'admin', '2026-06-09 14:00:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_publish (id, recruit_id, recruit_no, publish_round, publish_status, publish_begin_time, apply_begin_time, apply_end_time, publish_end_time, tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(205005, 105005, 'SC2606090005', 1, 50, '2026-06-09 14:00:00.000000', '2026-06-09 14:00:00.000000', '2026-06-09 21:00:00.000000', '2026-06-12 23:59:59.000000', 0, 0, 0, 'admin', 'admin', '2026-06-09 14:00:00.000000', 0, 1);

-- 清单完成(60) 发布记录
INSERT INTO scms_consignment_recruit_publish (id, recruit_id, recruit_no, publish_round, publish_status, publish_begin_time, apply_begin_time, apply_end_time, publish_end_time, tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(206001, 106001, 'SC2606020001', 1, 60, '2026-06-02 14:00:00.000000', '2026-06-02 14:00:00.000000', '2026-06-02 21:00:00.000000', '2026-06-09 23:59:59.000000', 0, 0, 0, 'admin', 'admin', '2026-06-02 14:00:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_publish (id, recruit_id, recruit_no, publish_round, publish_status, publish_begin_time, apply_begin_time, apply_end_time, publish_end_time, tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(206002, 106002, 'SC2606020002', 1, 60, '2026-06-02 14:00:00.000000', '2026-06-02 14:00:00.000000', '2026-06-02 21:00:00.000000', '2026-06-09 23:59:59.000000', 0, 0, 0, 'admin', 'admin', '2026-06-02 14:00:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_publish (id, recruit_id, recruit_no, publish_round, publish_status, publish_begin_time, apply_begin_time, apply_end_time, publish_end_time, tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(206003, 106003, 'SC2606020003', 1, 60, '2026-06-02 14:00:00.000000', '2026-06-02 14:00:00.000000', '2026-06-02 21:00:00.000000', '2026-06-09 23:59:59.000000', 0, 0, 0, 'admin', 'admin', '2026-06-02 14:00:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_publish (id, recruit_id, recruit_no, publish_round, publish_status, publish_begin_time, apply_begin_time, apply_end_time, publish_end_time, tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(206004, 106004, 'SC2606020004', 1, 60, '2026-06-02 14:00:00.000000', '2026-06-02 14:00:00.000000', '2026-06-02 21:00:00.000000', '2026-06-09 23:59:59.000000', 0, 0, 0, 'admin', 'admin', '2026-06-02 14:00:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_publish (id, recruit_id, recruit_no, publish_round, publish_status, publish_begin_time, apply_begin_time, apply_end_time, publish_end_time, tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(206005, 106005, 'SC2606020005', 1, 60, '2026-06-02 14:00:00.000000', '2026-06-02 14:00:00.000000', '2026-06-02 21:00:00.000000', '2026-06-09 23:59:59.000000', 0, 0, 0, 'admin', 'admin', '2026-06-02 14:00:00.000000', 0, 1);

-- 作废(100) 发布记录
INSERT INTO scms_consignment_recruit_publish (id, recruit_id, recruit_no, publish_round, publish_status, publish_begin_time, apply_begin_time, apply_end_time, publish_end_time, tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(210001, 110001, 'SC2606010001', 1, 100, '2026-06-02 14:00:00.000000', '2026-06-02 14:00:00.000000', '2026-06-02 21:00:00.000000', '2026-06-02 23:59:59.000000', 0, 0, 0, 'admin', 'admin', '2026-06-02 14:00:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_publish (id, recruit_id, recruit_no, publish_round, publish_status, publish_begin_time, apply_begin_time, apply_end_time, publish_end_time, tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(210002, 110002, 'SC2606010002', 1, 100, '2026-06-02 14:00:00.000000', '2026-06-02 14:00:00.000000', '2026-06-02 21:00:00.000000', '2026-06-02 23:59:59.000000', 0, 0, 0, 'admin', 'admin', '2026-06-02 14:00:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_publish (id, recruit_id, recruit_no, publish_round, publish_status, publish_begin_time, apply_begin_time, apply_end_time, publish_end_time, tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(210003, 110003, 'SC2606010003', 1, 100, '2026-06-02 14:00:00.000000', '2026-06-02 14:00:00.000000', '2026-06-02 21:00:00.000000', '2026-06-02 23:59:59.000000', 0, 0, 0, 'admin', 'admin', '2026-06-02 14:00:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_publish (id, recruit_id, recruit_no, publish_round, publish_status, publish_begin_time, apply_begin_time, apply_end_time, publish_end_time, tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(210004, 110004, 'SC2606010004', 1, 100, '2026-06-02 14:00:00.000000', '2026-06-02 14:00:00.000000', '2026-06-02 21:00:00.000000', '2026-06-02 23:59:59.000000', 0, 0, 0, 'admin', 'admin', '2026-06-02 14:00:00.000000', 0, 1);
INSERT INTO scms_consignment_recruit_publish (id, recruit_id, recruit_no, publish_round, publish_status, publish_begin_time, apply_begin_time, apply_end_time, publish_end_time, tenant_id, instance_id, application_id, create_by, update_by, create_time, is_deleted, version)
VALUES
(210005, 110005, 'SC2606010005', 1, 100, '2026-06-02 14:00:00.000000', '2026-06-02 14:00:00.000000', '2026-06-02 21:00:00.000000', '2026-06-02 23:59:59.000000', 0, 0, 0, 'admin', 'admin', '2026-06-02 14:00:00.000000', 0, 1);
