-- ============================================================
-- 寄卖招募 - 招募清单测试数据SQL
-- 适用表: scms_consignment_recruit_list / _publish / _list_sku / _apply
-- 对应前端原型展示的4条招募记录
-- 注意: category_id 需替换为系统中真实存在的末级分类ID
--       factory_id 需替换为工厂表中真实存在的供应商工厂ID
-- ============================================================

-- ============================================================
-- 1. 招募清单主表数据 (scms_consignment_recruit_list)
-- ============================================================

-- 清单1: SC202604100029 - 招募中（竞争池3人，同源2）
INSERT INTO scms_consignment_recruit_list (
    id, recruit_no, factory_id, factory_name, category_id, category_full_path_id,
    sku_count, file_url, estimated_cost, estimated_month_sale_qty,
    estimated_month_sale_amount, avg_moq, list_status, list_type,
    group_time, publish_begin_time, apply_begin_time, apply_end_time,
    publish_end_time, award_time, award_supplier_id, award_group_id,
    award_apply_id, award_ce_bill_no,
    publish_by, audit_by, award_by, cancel_user_name, cancel_time,
    cancel_type, cancel_reason, remark, supplier_apply_count,
    tenant_id, instance_id, application_id,
    create_by, update_by, create_time, update_time, is_deleted, version
) VALUES (
    100001, 'SC202604100029', 1001, '瑞安市高风汽车部件有限公司',
    -- category_id: 请替换为"交通工具配件及附件->发动机配件->进气软管"的末级分类ID（如14400等）
    -- category_full_path_id: 请替换为实际分类路径ID，格式如 "10312,14304,14398,14400"
    999999, '999999,999999,999999',
    12, null, 12.00, 12, 3920.00, 0, 20, 1,
    '2026-03-26 18:00:00', '2026-03-27 09:00:00', '2026-03-27 09:00:00', '2026-04-03 09:00:00',
    '2026-04-03 09:00:00', null, null, null,
    null, null,
    'admin', null, null, null, null,
    null, null, null, 3,
    1, 1, 1,
    'admin', 'admin', NOW(), NOW(), 0, 1
);

-- 清单2: SC202604100028 - 招募完成（成功）
INSERT INTO scms_consignment_recruit_list (
    id, recruit_no, factory_id, factory_name, category_id, category_full_path_id,
    sku_count, file_url, estimated_cost, estimated_month_sale_qty,
    estimated_month_sale_amount, avg_moq, list_status, list_type,
    group_time, publish_begin_time, apply_begin_time, apply_end_time,
    publish_end_time, award_time, award_supplier_id, award_group_id,
    award_apply_id, award_ce_bill_no,
    publish_by, audit_by, award_by, cancel_user_name, cancel_time,
    cancel_type, cancel_reason, remark, supplier_apply_count,
    tenant_id, instance_id, application_id,
    create_by, update_by, create_time, update_time, is_deleted, version
) VALUES (
    100002, 'SC202604100028', 1001, '瑞安市高风汽车部件有限公司',
    -- category_id: 请替换为"交通工具配件及附件->汽车专用眼车附件->专车专用眼镜盒"的末级分类ID
    -- category_full_path_id: 请替换为实际分类路径ID
    999999, '999999,999999,999999',
    5, null, 5.00, 5, 875.00, 0, 60, 1,
    '2026-03-26 18:00:00', '2026-03-27 09:00:00', '2026-03-27 09:00:00', '2026-04-02 09:00:00',
    '2026-04-02 09:00:00', '2026-04-02 10:00:00',
    -- award_supplier_id / award_group_id / award_apply_id: 请替换为实际ID
    2001, 3001, 5001,
    'CE202604010001',
    'admin', null, 'admin', null, null,
    null, null, null, 2,
    1, 1, 1,
    'admin', 'admin', NOW(), NOW(), 0, 1
);

-- 清单3: SC202604100027 - 招募中（竞争池2人）
INSERT INTO scms_consignment_recruit_list (
    id, recruit_no, factory_id, factory_name, category_id, category_full_path_id,
    sku_count, file_url, estimated_cost, estimated_month_sale_qty,
    estimated_month_sale_amount, avg_moq, list_status, list_type,
    group_time, publish_begin_time, apply_begin_time, apply_end_time,
    publish_end_time, award_time, award_supplier_id, award_group_id,
    award_apply_id, award_ce_bill_no,
    publish_by, audit_by, award_by, cancel_user_name, cancel_time,
    cancel_type, cancel_reason, remark, supplier_apply_count,
    tenant_id, instance_id, application_id,
    create_by, update_by, create_time, update_time, is_deleted, version
) VALUES (
    100003, 'SC202604100027', 1001, '瑞安市高风汽车部件有限公司',
    -- category_id: 同清单1（进气软管）
    -- category_full_path_id: 同清单1
    999999, '999999,999999,999999',
    12, null, 12.00, 12, 3920.00, 0, 20, 1,
    '2026-03-26 18:00:00', '2026-03-27 09:00:00', '2026-03-27 09:00:00', '2026-04-03 09:00:00',
    '2026-04-03 09:00:00', null, null, null,
    null, null,
    'admin', null, null, null, null,
    null, null, null, 2,
    1, 1, 1,
    'admin', 'admin', NOW(), NOW(), 0, 1
);

-- 清单4: SC202604100026 - 招募完成（失败）
INSERT INTO scms_consignment_recruit_list (
    id, recruit_no, factory_id, factory_name, category_id, category_full_path_id,
    sku_count, file_url, estimated_cost, estimated_month_sale_qty,
    estimated_month_sale_amount, avg_moq, list_status, list_type,
    group_time, publish_begin_time, apply_begin_time, apply_end_time,
    publish_end_time, award_time, award_supplier_id, award_group_id,
    award_apply_id, award_ce_bill_no,
    publish_by, audit_by, award_by, cancel_user_name, cancel_time,
    cancel_type, cancel_reason, remark, supplier_apply_count,
    tenant_id, instance_id, application_id,
    create_by, update_by, create_time, update_time, is_deleted, version
) VALUES (
    100004, 'SC202604100026', 1002, '北京文浩轩智能科技有限公司',
    -- category_id: 请替换为"交通工具配件及附件->汽车附件->专车专用眼镜盒"的末级分类ID
    -- category_full_path_id: 请替换为实际分类路径ID
    999999, '999999,999999,999999',
    5, null, 5.00, 5, 875.00, 0, 60, 1,
    '2026-03-26 18:00:00', '2026-03-27 09:00:00', '2026-03-27 09:00:00', '2026-04-01 09:00:00',
    '2026-04-01 09:00:00', '2026-04-02 10:00:00',
    -- award_supplier_id / award_group_id / award_apply_id: 请替换为实际ID
    null, null, null,
    null,
    'admin', null, 'admin', null, null,
    null, null, '招募失败-无符合条件供应商', 2,
    1, 1, 1,
    'admin', 'admin', NOW(), NOW(), 0, 1
);


-- ============================================================
-- 2. 发布记录表数据 (scms_consignment_recruit_publish)
--    每个清单对应一条发布记录
-- ============================================================

-- 清单1 发布记录
INSERT INTO scms_consignment_recruit_publish (
    id, recruit_id, recruit_no, publish_round, publish_status,
    publish_begin_time, apply_begin_time, apply_end_time, publish_end_time,
    publish_job_id, tenant_id, instance_id, application_id,
    create_by, update_by, create_time, update_time, is_deleted, version
) VALUES (
    200001, 100001, 'SC202604100029', 1, 20,
    '2026-03-27 09:00:00', '2026-03-27 09:00:00', '2026-04-03 09:00:00', '2026-04-03 09:00:00',
    null, 1, 1, 1,
    'admin', 'admin', NOW(), NOW(), 0, 1
);

-- 清单2 发布记录（已完成）
INSERT INTO scms_consignment_recruit_publish (
    id, recruit_id, recruit_no, publish_round, publish_status,
    publish_begin_time, apply_begin_time, apply_end_time, publish_end_time,
    publish_job_id, tenant_id, instance_id, application_id,
    create_by, update_by, create_time, update_time, is_deleted, version
) VALUES (
    200002, 100002, 'SC202604100028', 1, 60,
    '2026-03-27 09:00:00', '2026-03-27 09:00:00', '2026-04-02 09:00:00', '2026-04-02 09:00:00',
    null, 1, 1, 1,
    'admin', 'admin', NOW(), NOW(), 0, 1
);

-- 清单3 发布记录
INSERT INTO scms_consignment_recruit_publish (
    id, recruit_id, recruit_no, publish_round, publish_status,
    publish_begin_time, apply_begin_time, apply_end_time, publish_end_time,
    publish_job_id, tenant_id, instance_id, application_id,
    create_by, update_by, create_time, update_time, is_deleted, version
) VALUES (
    200003, 100003, 'SC202604100027', 1, 20,
    '2026-03-27 09:00:00', '2026-03-27 09:00:00', '2026-04-03 09:00:00', '2026-04-03 09:00:00',
    null, 1, 1, 1,
    'admin', 'admin', NOW(), NOW(), 0, 1
);

-- 清单4 发布记录（已完成）
INSERT INTO scms_consignment_recruit_publish (
    id, recruit_id, recruit_no, publish_round, publish_status,
    publish_begin_time, apply_begin_time, apply_end_time, publish_end_time,
    publish_job_id, tenant_id, instance_id, application_id,
    create_by, update_by, create_time, update_time, is_deleted, version
) VALUES (
    200004, 100004, 'SC202604100026', 1, 60,
    '2026-03-27 09:00:00', '2026-03-27 09:00:00', '2026-04-01 09:00:00', '2026-04-01 09:00:00',
    null, 1, 1, 1,
    'admin', 'admin', NOW(), NOW(), 0, 1
);


-- ============================================================
-- 3. 招募申请记录 (scms_consignment_recruit_apply)
--    清单1(招募中): 3家供应商申请（同源2家）
--    清单2(成功):   2家供应商申请，1家中标
--    清单3(招募中): 2家供应商申请
--    清单4(失败):   2家供应商申请，均未成功
-- ============================================================

-- ===== 清单1: SC202604100029 - 3家供应商 =====

-- 供应商A（同源）- 已加入
INSERT INTO scms_consignment_recruit_apply (
    id, recruit_id, recruit_no, recruit_public_id,
    supplier_id, supplier_name, group_id, factory_id,
    apply_status, same_source_flag, ce_bill_no, ce_create_time, ce_send_time, ce_status,
    first_qc_pass_time, same_source_sku_count, removed_type,
    inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate,
    rank_no, award_result, award_reason,
    cancel_type, cancel_reason,
    tenant_id, instance_id, application_id,
    create_by, update_by, create_time, update_time, is_deleted, version
) VALUES (
    500001, 100001, 'SC202604100029', 200001,
    -- supplier_id/group_id: 请替换为实际供应商/集团ID
    3001, '上海众晟汽车配件有限公司', 4001, 1001,
    10, 1, null, null, null, null,
    null, 2, null,
    0, 0.0000, 0.0000, 0.0000,
    null, null, null,
    null, null,
    1, 1, 1,
    'admin', 'admin', NOW(), NOW(), 0, 1
);

-- 供应商B（同源）- 已开CE
INSERT INTO scms_consignment_recruit_apply (
    id, recruit_id, recruit_no, recruit_public_id,
    supplier_id, supplier_name, group_id, factory_id,
    apply_status, same_source_flag, ce_bill_no, ce_create_time, ce_send_time, ce_status,
    first_qc_pass_time, same_source_sku_count, removed_type,
    inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate,
    rank_no, award_result, award_reason,
    cancel_type, cancel_reason,
    tenant_id, instance_id, application_id,
    create_by, update_by, create_time, update_time, is_deleted, version
) VALUES (
    500002, 100001, 'SC202604100029', 200001,
    3002, '广州恒达汽车零部件有限公司', 4001, 1001,
    20, 1, 'CE202603280001', '2026-03-28 10:00:00', null, null,
    null, 3, null,
    0, 0.0000, 0.0000, 0.0000,
    null, null, null,
    null, null,
    1, 1, 1,
    'admin', 'admin', NOW(), NOW(), 0, 1
);

-- 供应商C（非同源）- 已加入
INSERT INTO scms_consignment_recruit_apply (
    id, recruit_id, recruit_no, recruit_public_id,
    supplier_id, supplier_name, group_id, factory_id,
    apply_status, same_source_flag, ce_bill_no, ce_create_time, ce_send_time, ce_status,
    first_qc_pass_time, same_source_sku_count, removed_type,
    inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate,
    rank_no, award_result, award_reason,
    cancel_type, cancel_reason,
    tenant_id, instance_id, application_id,
    create_by, update_by, create_time, update_time, is_deleted, version
) VALUES (
    500003, 100001, 'SC202604100029', 200001,
    3003, '深圳金辉汽配有限公司', 4002, 1001,
    10, 0, null, null, null, null,
    null, 0, null,
    0, 0.0000, 0.0000, 0.0000,
    null, null, null,
    null, null,
    1, 1, 1,
    'admin', 'admin', NOW(), NOW(), 0, 1
);


-- ===== 清单2: SC202604100028 - 2家供应商，1家中标 =====

-- 中标供应商 - 分配完成 + 成功
INSERT INTO scms_consignment_recruit_apply (
    id, recruit_id, recruit_no, recruit_public_id,
    supplier_id, supplier_name, group_id, factory_id,
    apply_status, same_source_flag, ce_bill_no, ce_create_time, ce_send_time, ce_status,
    first_qc_pass_time, same_source_sku_count, removed_type,
    inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate,
    rank_no, award_result, award_reason,
    cancel_type, cancel_reason,
    tenant_id, instance_id, application_id,
    create_by, update_by, create_time, update_time, is_deleted, version
) VALUES (
    500004, 100002, 'SC202604100028', 200002,
    3001, '上海众晟汽车配件有限公司', 4001, 1001,
    40, 1, 'CE202603280002', '2026-03-28 14:00:00', '2026-03-30 10:00:00', '已完成',
    '2026-04-01 10:00:00', 2, null,
    5, 100.00, 80.00, 100.00,
    1, 1, '综合评分最优',
    null, null,
    1, 1, 1,
    'admin', 'admin', NOW(), NOW(), 0, 1
);

-- 未中标供应商 - 分配完成 + 失败
INSERT INTO scms_consignment_recruit_apply (
    id, recruit_id, recruit_no, recruit_public_id,
    supplier_id, supplier_name, group_id, factory_id,
    apply_status, same_source_flag, ce_bill_no, ce_create_time, ce_send_time, ce_status,
    first_qc_pass_time, same_source_sku_count, removed_type,
    inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate,
    rank_no, award_result, award_reason,
    cancel_type, cancel_reason,
    tenant_id, instance_id, application_id,
    create_by, update_by, create_time, update_time, is_deleted, version
) VALUES (
    500005, 100002, 'SC202604100028', 200002,
    3004, '杭州博驰汽车用品有限公司', 4003, 1001,
    40, 0, 'CE202603280003', '2026-03-28 15:00:00', '2026-03-30 10:00:00', '已完成',
    '2026-04-01 10:00:00', 0, null,
    5, 80.00, 0, 80.00,
    2, 0, '未达到评选标准',
    null, null,
    1, 1, 1,
    'admin', 'admin', NOW(), NOW(), 0, 1
);


-- ===== 清单3: SC202604100027 - 2家供应商 =====

-- 供应商A - 已加入
INSERT INTO scms_consignment_recruit_apply (
    id, recruit_id, recruit_no, recruit_public_id,
    supplier_id, supplier_name, group_id, factory_id,
    apply_status, same_source_flag, ce_bill_no, ce_create_time, ce_send_time, ce_status,
    first_qc_pass_time, same_source_sku_count, removed_type,
    inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate,
    rank_no, award_result, award_reason,
    cancel_type, cancel_reason,
    tenant_id, instance_id, application_id,
    create_by, update_by, create_time, update_time, is_deleted, version
) VALUES (
    500006, 100003, 'SC202604100027', 200003,
    3001, '上海众晟汽车配件有限公司', 4001, 1001,
    10, 1, null, null, null, null,
    null, 2, null,
    0, 0.0000, 0.0000, 0.0000,
    null, null, null,
    null, null,
    1, 1, 1,
    'admin', 'admin', NOW(), NOW(), 0, 1
);

-- 供应商B - 已开CE
INSERT INTO scms_consignment_recruit_apply (
    id, recruit_id, recruit_no, recruit_public_id,
    supplier_id, supplier_name, group_id, factory_id,
    apply_status, same_source_flag, ce_bill_no, ce_create_time, ce_send_time, ce_status,
    first_qc_pass_time, same_source_sku_count, removed_type,
    inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate,
    rank_no, award_result, award_reason,
    cancel_type, cancel_reason,
    tenant_id, instance_id, application_id,
    create_by, update_by, create_time, update_time, is_deleted, version
) VALUES (
    500007, 100003, 'SC202604100027', 200003,
    3002, '广州恒达汽车零部件有限公司', 4001, 1001,
    20, 1, 'CE202603290001', '2026-03-29 09:00:00', null, null,
    null, 3, null,
    0, 0.0000, 0.0000, 0.0000,
    null, null, null,
    null, null,
    1, 1, 1,
    'admin', 'admin', NOW(), NOW(), 0, 1
);


-- ===== 清单4: SC202604100026 - 2家供应商，均未成功 =====

-- 供应商A - 分配完成 + 失败
INSERT INTO scms_consignment_recruit_apply (
    id, recruit_id, recruit_no, recruit_public_id,
    supplier_id, supplier_name, group_id, factory_id,
    apply_status, same_source_flag, ce_bill_no, ce_create_time, ce_send_time, ce_status,
    first_qc_pass_time, same_source_sku_count, removed_type,
    inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate,
    rank_no, award_result, award_reason,
    cancel_type, cancel_reason,
    tenant_id, instance_id, application_id,
    create_by, update_by, create_time, update_time, is_deleted, version
) VALUES (
    500008, 100004, 'SC202604100026', 200004,
    3005, '北京天元汽配有限公司', 4004, 1002,
    40, 0, 'CE202603300001', '2026-03-30 10:00:00', '2026-04-01 09:00:00', '已完成',
    null, 0, null,
    5, 60.00, 0, 60.00,
    1, 0, 'SKU覆盖率不达标',
    null, null,
    1, 1, 1,
    'admin', 'admin', NOW(), NOW(), 0, 1
);

-- 供应商B - 超时清出
INSERT INTO scms_consignment_recruit_apply (
    id, recruit_id, recruit_no, recruit_public_id,
    supplier_id, supplier_name, group_id, factory_id,
    apply_status, same_source_flag, ce_bill_no, ce_create_time, ce_send_time, ce_status,
    first_qc_pass_time, same_source_sku_count, removed_type,
    inbound_sku_count, sku_coverage_rate, same_source_weight_rate, final_coverage_rate,
    rank_no, award_result, award_reason,
    cancel_type, cancel_reason,
    tenant_id, instance_id, application_id,
    create_by, update_by, create_time, update_time, is_deleted, version
) VALUES (
    500009, 100004, 'SC202604100026', 200004,
    3006, '天津华丰汽车零部件有限公司', 4005, 1002,
    90, 0, 'CE202603300002', '2026-03-30 14:00:00', null, null,
    null, 0, 'timeout',
    0, 0.0000, 0.0000, 0.0000,
    null, null, null,
    'timeout', '超时未完成CE',
    1, 1, 1,
    'admin', 'admin', NOW(), NOW(), 0, 1
);


-- ============================================================
-- 4. SKU明细数据 (scms_consignment_recruit_list_sku)
--    清单1: 12条SKU（进气软管）
--    清单2:  5条SKU（专车专用眼镜盒）
--    清单3: 12条SKU（进气软管，不同型号）
--    清单4:  5条SKU（专车专用眼镜盒）
-- ============================================================

-- ===== 清单1: SC202604100029 - 12条进气软管SKU =====
INSERT INTO scms_consignment_recruit_list_sku (id, recruit_id, recruit_no, sku_id, sku_name, category_id, factory_id, factory_name, source_url, product_model, vehicle_model, sales_quantity, unit, package_info, gross_weight_g, package_size_cm, source_model, delivery_days, moq, cost_price, ce_receive_count, sale_qty_90d, sale_qty_30d, replenish_remind_21d, source_type, sku_status, import_batch_no, import_user, import_time, fail_reason, tenant_id, instance_id, application_id, create_by, update_by, create_time, update_time, is_deleted, version) VALUES
(600001, 100001, 'SC202604100029', 7001, '进气软管-奔驰W204', 999999, 1001, '瑞安市高风汽车部件有限公司', 'http://example.com/sku1', 'IR-204-001', '奔驰W204', '120', '根', '纸箱', 350.00, '30*20*15', 'OEM-204-001', 7, 50, 85.00, 0, 360, 120, 15, 1, 30, 'BATCH-001', 'admin', NOW(), null, 1, 1, 1, 'admin', 'admin', NOW(), NOW(), 0, 1),
(600002, 100001, 'SC202604100029', 7002, '进气软管-宝马E90', 999999, 1001, '瑞安市高风汽车部件有限公司', 'http://example.com/sku2', 'IR-90-001', '宝马E90', '95', '根', '纸箱', 380.00, '32*22*15', 'OEM-90-001', 7, 50, 92.00, 0, 280, 95, 12, 1, 30, 'BATCH-001', 'admin', NOW(), null, 1, 1, 1, 'admin', 'admin', NOW(), NOW(), 0, 1),
(600003, 100001, 'SC202604100029', 7003, '进气软管-奥迪A4L', 999999, 1001, '瑞安市高风汽车部件有限公司', 'http://example.com/sku3', 'IR-A4-001', '奥迪A4L', '88', '根', '纸箱', 320.00, '28*18*12', 'OEM-A4-001', 5, 50, 78.00, 0, 260, 88, 10, 1, 30, 'BATCH-001', 'admin', NOW(), null, 1, 1, 1, 'admin', 'admin', NOW(), NOW(), 0, 1),
(600004, 100001, 'SC202604100029', 7004, '进气软管-大众迈腾', 999999, 1001, '瑞安市高风汽车部件有限公司', 'http://example.com/sku4', 'IR-MT-001', '大众迈腾', '75', '根', '纸箱', 340.00, '31*20*14', 'OEM-MT-001', 7, 30, 72.00, 0, 220, 75, 8, 1, 30, 'BATCH-001', 'admin', NOW(), null, 1, 1, 1, 'admin', 'admin', NOW(), NOW(), 0, 1),
(600005, 100001, 'SC202604100029', 7005, '进气软管-丰田卡罗拉', 999999, 1001, '瑞安市高风汽车部件有限公司', 'http://example.com/sku5', 'IR-CR-001', '丰田卡罗拉', '110', '根', '编织袋', 280.00, '26*16*12', 'OEM-CR-001', 5, 60, 65.00, 0, 320, 110, 20, 1, 30, 'BATCH-001', 'admin', NOW(), null, 1, 1, 1, 'admin', 'admin', NOW(), NOW(), 0, 1),
(600006, 100001, 'SC202604100029', 7006, '进气软管-本田雅阁', 999999, 1001, '瑞安市高风汽车部件有限公司', 'http://example.com/sku6', 'IR-ACC-001', '本田雅阁', '68', '根', '纸箱', 300.00, '27*17*13', 'OEM-ACC-001', 7, 40, 70.00, 0, 200, 68, 6, 1, 30, 'BATCH-001', 'admin', NOW(), null, 1, 1, 1, 'admin', 'admin', NOW(), NOW(), 0, 1),
(600007, 100001, 'SC202604100029', 7007, '进气软管-日产轩逸', 999999, 1001, '瑞安市高风汽车部件有限公司', 'http://example.com/sku7', 'IR-SY-001', '日产轩逸', '82', '根', '纸箱', 290.00, '25*15*12', 'OEM-SY-001', 5, 40, 62.00, 0, 240, 82, 9, 1, 30, 'BATCH-001', 'admin', NOW(), null, 1, 1, 1, 'admin', 'admin', NOW(), NOW(), 0, 1),
(600008, 100001, 'SC202604100029', 7008, '进气软管-福特福克斯', 999999, 1001, '瑞安市高风汽车部件有限公司', 'http://example.com/sku8', 'IR-FOC-001', '福特福克斯', '55', '根', '纸箱', 310.00, '29*19*13', 'OEM-FOC-001', 7, 30, 68.00, 0, 160, 55, 5, 1, 30, 'BATCH-001', 'admin', NOW(), null, 1, 1, 1, 'admin', 'admin', NOW(), NOW(), 0, 1),
(600009, 100001, 'SC202604100029', 7009, '进气软管-现代伊兰特', 999999, 1001, '瑞安市高风汽车部件有限公司', 'http://example.com/sku9', 'IR-EL-001', '现代伊兰特', '90', '根', '纸箱', 270.00, '24*14*11', 'OEM-EL-001', 5, 50, 60.00, 0, 270, 90, 11, 1, 30, 'BATCH-001', 'admin', NOW(), null, 1, 1, 1, 'admin', 'admin', NOW(), NOW(), 0, 1),
(600010, 100001, 'SC202604100029', 7010, '进气软管-起亚K5', 999999, 1001, '瑞安市高风汽车部件有限公司', 'http://example.com/sku10', 'IR-K5-001', '起亚K5', '62', '根', '纸箱', 260.00, '23*13*11', 'OEM-K5-001', 5, 40, 58.00, 0, 180, 62, 7, 1, 30, 'BATCH-001', 'admin', NOW(), null, 1, 1, 1, 'admin', 'admin', NOW(), NOW(), 0, 1),
(600011, 100001, 'SC202604100029', 7011, '进气软管-别克君威', 999999, 1001, '瑞安市高风汽车部件有限公司', 'http://example.com/sku11', 'IR-RW-001', '别克君威', '78', '根', '纸箱', 330.00, '30*20*14', 'OEM-RW-001', 7, 40, 75.00, 0, 230, 78, 8, 1, 30, 'BATCH-001', 'admin', NOW(), null, 1, 1, 1, 'admin', 'admin', NOW(), NOW(), 0, 1),
(600012, 100001, 'SC202604100029', 7012, '进气软管-路虎发现', 999999, 1001, '瑞安市高风汽车部件有限公司', 'http://example.com/sku12', 'IR-DIS-001', '路虎发现', '45', '根', '木箱', 520.00, '45*30*20', 'OEM-DIS-001', 10, 20, 120.00, 0, 130, 45, 3, 1, 30, 'BATCH-001', 'admin', NOW(), null, 1, 1, 1, 'admin', 'admin', NOW(), NOW(), 0, 1);

-- ===== 清单2: SC202604100028 - 5条专车专用眼镜盒SKU =====
INSERT INTO scms_consignment_recruit_list_sku (id, recruit_id, recruit_no, sku_id, sku_name, category_id, factory_id, factory_name, source_url, product_model, vehicle_model, sales_quantity, unit, package_info, gross_weight_g, package_size_cm, source_model, delivery_days, moq, cost_price, ce_receive_count, sale_qty_90d, sale_qty_30d, replenish_remind_21d, source_type, sku_status, import_batch_no, import_user, import_time, fail_reason, tenant_id, instance_id, application_id, create_by, update_by, create_time, update_time, is_deleted, version) VALUES
(601001, 100002, 'SC202604100028', 7013, '专用眼镜盒-黑色-奔驰E级', 999999, 1001, '瑞安市高风汽车部件有限公司', 'http://example.com/sku13', 'EH-BENZ-E-001', '奔驰E级', '35', '个', '彩盒', 180.00, '20*15*10', 'OEM-BENZ-EH-001', 5, 100, 45.00, 5, 100, 35, 5, 1, 30, 'BATCH-002', 'admin', NOW(), null, 1, 1, 1, 'admin', 'admin', NOW(), NOW(), 0, 1),
(601002, 100002, 'SC202604100028', 7014, '专用眼镜盒-米色-宝马5系', 999999, 1001, '瑞安市高风汽车部件有限公司', 'http://example.com/sku14', 'EH-BMW-5-001', '宝马5系', '28', '个', '彩盒', 190.00, '21*16*10', 'OEM-BMW-EH-001', 5, 100, 48.00, 3, 80, 28, 4, 1, 30, 'BATCH-002', 'admin', NOW(), null, 1, 1, 1, 'admin', 'admin', NOW(), NOW(), 0, 1),
(601003, 100002, 'SC202604100028', 7015, '专用眼镜盒-棕色-奥迪A6L', 999999, 1001, '瑞安市高风汽车部件有限公司', 'http://example.com/sku15', 'EH-AUDI-A6-001', '奥迪A6L', '42', '个', '彩盒', 175.00, '20*14*9', 'OEM-AUDI-EH-001', 5, 100, 42.00, 8, 120, 42, 6, 1, 30, 'BATCH-002', 'admin', NOW(), null, 1, 1, 1, 'admin', 'admin', NOW(), NOW(), 0, 1),
(601004, 100002, 'SC202604100028', 7016, '专用眼镜盒-黑色-奔驰S级', 999999, 1001, '瑞安市高风汽车部件有限公司', 'http://example.com/sku16', 'EH-BENZ-S-001', '奔驰S级', '22', '个', '皮盒', 220.00, '22*18*10', 'OEM-BENZ-S-EH-001', 7, 50, 68.00, 2, 60, 22, 2, 1, 30, 'BATCH-002', 'admin', NOW(), null, 1, 1, 1, 'admin', 'admin', NOW(), NOW(), 0, 1),
(601005, 100002, 'SC202604100028', 7017, '专用眼镜盒-灰色-宝马7系', 999999, 1001, '瑞安市高风汽车部件有限公司', 'http://example.com/sku17', 'EH-BMW-7-001', '宝马7系', '18', '个', '皮盒', 230.00, '23*19*10', 'OEM-BMW-7-EH-001', 7, 50, 72.00, 1, 50, 18, 1, 1, 30, 'BATCH-002', 'admin', NOW(), null, 1, 1, 1, 'admin', 'admin', NOW(), NOW(), 0, 1);

-- ===== 清单3: SC202604100027 - 12条不同型号进气软管SKU =====
INSERT INTO scms_consignment_recruit_list_sku (id, recruit_id, recruit_no, sku_id, sku_name, category_id, factory_id, factory_name, source_url, product_model, vehicle_model, sales_quantity, unit, package_info, gross_weight_g, package_size_cm, source_model, delivery_days, moq, cost_price, ce_receive_count, sale_qty_90d, sale_qty_30d, replenish_remind_21d, source_type, sku_status, import_batch_no, import_user, import_time, fail_reason, tenant_id, instance_id, application_id, create_by, update_by, create_time, update_time, is_deleted, version) VALUES
(602001, 100003, 'SC202604100027', 7018, '进气软管-保时捷卡宴', 999999, 1001, '瑞安市高风汽车部件有限公司', 'http://example.com/sku18', 'IR-CAY-001', '保时捷卡宴', '30', '根', '木箱', 580.00, '50*35*25', 'OEM-CAY-001', 10, 15, 180.00, 0, 90, 30, 2, 1, 30, 'BATCH-003', 'admin', NOW(), null, 1, 1, 1, 'admin', 'admin', NOW(), NOW(), 0, 1),
(602002, 100003, 'SC202604100027', 7019, '进气软管-沃尔沃S60', 999999, 1001, '瑞安市高风汽车部件有限公司', 'http://example.com/sku19', 'IR-S60-001', '沃尔沃S60', '45', '根', '纸箱', 310.00, '28*18*13', 'OEM-S60-001', 7, 30, 82.00, 0, 130, 45, 5, 1, 30, 'BATCH-003', 'admin', NOW(), null, 1, 1, 1, 'admin', 'admin', NOW(), NOW(), 0, 1),
(602003, 100003, 'SC202604100027', 7020, '进气软管-凯迪拉克ATS', 999999, 1001, '瑞安市高风汽车部件有限公司', 'http://example.com/sku20', 'IR-ATS-001', '凯迪拉克ATS', '38', '根', '纸箱', 340.00, '30*20*14', 'OEM-ATS-001', 7, 30, 88.00, 0, 110, 38, 4, 1, 30, 'BATCH-003', 'admin', NOW(), null, 1, 1, 1, 'admin', 'admin', NOW(), NOW(), 0, 1),
(602004, 100003, 'SC202604100027', 7021, '进气软管-雷克萨斯ES', 999999, 1001, '瑞安市高风汽车部件有限公司', 'http://example.com/sku21', 'IR-ES-001', '雷克萨斯ES', '52', '根', '纸箱', 320.00, '28*17*12', 'OEM-ES-001', 7, 40, 77.00, 0, 150, 52, 6, 1, 30, 'BATCH-003', 'admin', NOW(), null, 1, 1, 1, 'admin', 'admin', NOW(), NOW(), 0, 1),
(602005, 100003, 'SC202604100027', 7022, '进气软管-马自达6', 999999, 1001, '瑞安市高风汽车部件有限公司', 'http://example.com/sku22', 'IR-M6-001', '马自达6', '65', '根', '纸箱', 260.00, '24*14*11', 'OEM-M6-001', 5, 40, 55.00, 0, 190, 65, 8, 1, 30, 'BATCH-003', 'admin', NOW(), null, 1, 1, 1, 'admin', 'admin', NOW(), NOW(), 0, 1),
(602006, 100003, 'SC202604100027', 7023, '进气软管-雪佛兰科鲁兹', 999999, 1001, '瑞安市高风汽车部件有限公司', 'http://example.com/sku23', 'IR-CRZ-001', '雪佛兰科鲁兹', '58', '根', '纸箱', 280.00, '26*16*12', 'OEM-CRZ-001', 5, 35, 60.00, 0, 170, 58, 7, 1, 30, 'BATCH-003', 'admin', NOW(), null, 1, 1, 1, 'admin', 'admin', NOW(), NOW(), 0, 1),
(602007, 100003, 'SC202604100027', 7024, '进气软管-斯柯达明锐', 999999, 1001, '瑞安市高风汽车部件有限公司', 'http://example.com/sku24', 'IR-OCT-001', '斯柯达明锐', '72', '根', '纸箱', 290.00, '27*17*12', 'OEM-OCT-001', 5, 40, 63.00, 0, 210, 72, 9, 1, 30, 'BATCH-003', 'admin', NOW(), null, 1, 1, 1, 'admin', 'admin', NOW(), NOW(), 0, 1),
(602008, 100003, 'SC202604100027', 7025, '进气软管-标致408', 999999, 1001, '瑞安市高风汽车部件有限公司', 'http://example.com/sku25', 'IR-408-001', '标致408', '48', '根', '纸箱', 270.00, '25*15*11', 'OEM-408-001', 5, 30, 57.00, 0, 140, 48, 5, 1, 30, 'BATCH-003', 'admin', NOW(), null, 1, 1, 1, 'admin', 'admin', NOW(), NOW(), 0, 1),
(602009, 100003, 'SC202604100027', 7026, '进气软管-大众帕萨特', 999999, 1001, '瑞安市高风汽车部件有限公司', 'http://example.com/sku26', 'IR-PASS-001', '大众帕萨特', '85', '根', '纸箱', 330.00, '30*20*14', 'OEM-PASS-001', 7, 45, 78.00, 0, 250, 85, 10, 1, 30, 'BATCH-003', 'admin', NOW(), null, 1, 1, 1, 'admin', 'admin', NOW(), NOW(), 0, 1),
(602010, 100003, 'SC202604100027', 7027, '进气软管-本田CR-V', 999999, 1001, '瑞安市高风汽车部件有限公司', 'http://example.com/sku27', 'IR-CRV-001', '本田CR-V', '73', '根', '纸箱', 305.00, '28*18*13', 'OEM-CRV-001', 5, 40, 73.00, 0, 215, 73, 8, 1, 30, 'BATCH-003', 'admin', NOW(), null, 1, 1, 1, 'admin', 'admin', NOW(), NOW(), 0, 1),
(602011, 100003, 'SC202604100027', 7028, '进气软管-丰田凯美瑞', 999999, 1001, '瑞安市高风汽车部件有限公司', 'http://example.com/sku28', 'IR-CAM-001', '丰田凯美瑞', '92', '根', '纸箱', 295.00, '27*17*12', 'OEM-CAM-001', 5, 50, 67.00, 0, 270, 92, 12, 1, 30, 'BATCH-003', 'admin', NOW(), null, 1, 1, 1, 'admin', 'admin', NOW(), NOW(), 0, 1),
(602012, 100003, 'SC202604100027', 7029, '进气软管-奔驰GLC', 999999, 1001, '瑞安市高风汽车部件有限公司', 'http://example.com/sku29', 'IR-GLC-001', '奔驰GLC', '36', '根', '木箱', 480.00, '42*28*20', 'OEM-GLC-001', 10, 20, 150.00, 0, 105, 36, 3, 1, 30, 'BATCH-003', 'admin', NOW(), null, 1, 1, 1, 'admin', 'admin', NOW(), NOW(), 0, 1);

-- ===== 清单4: SC202604100026 - 5条专车专用眼镜盒SKU =====
INSERT INTO scms_consignment_recruit_list_sku (id, recruit_id, recruit_no, sku_id, sku_name, category_id, factory_id, factory_name, source_url, product_model, vehicle_model, sales_quantity, unit, package_info, gross_weight_g, package_size_cm, source_model, delivery_days, moq, cost_price, ce_receive_count, sale_qty_90d, sale_qty_30d, replenish_remind_21d, source_type, sku_status, import_batch_no, import_user, import_time, fail_reason, tenant_id, instance_id, application_id, create_by, update_by, create_time, update_time, is_deleted, version) VALUES
(603001, 100004, 'SC202604100026', 7030, '专用眼镜盒-黑色-奥迪Q5', 999999, 1002, '北京文浩轩智能科技有限公司', 'http://example.com/sku30', 'EH-AUDI-Q5-001', '奥迪Q5', '15', '个', '彩盒', 200.00, '22*16*10', 'OEM-Q5-EH-001', 5, 80, 55.00, 0, 45, 15, 2, 1, 30, 'BATCH-004', 'admin', NOW(), null, 1, 1, 1, 'admin', 'admin', NOW(), NOW(), 0, 1),
(603002, 100004, 'SC202604100026', 7031, '专用眼镜盒-米色-宝马X5', 999999, 1002, '北京文浩轩智能科技有限公司', 'http://example.com/sku31', 'EH-BMW-X5-001', '宝马X5', '12', '个', '彩盒', 210.00, '23*17*10', 'OEM-X5-EH-001', 5, 80, 58.00, 0, 35, 12, 1, 1, 30, 'BATCH-004', 'admin', NOW(), null, 1, 1, 1, 'admin', 'admin', NOW(), NOW(), 0, 1),
(603003, 100004, 'SC202604100026', 7032, '专用眼镜盒-棕色-奔驰GLE', 999999, 1002, '北京文浩轩智能科技有限公司', 'http://example.com/sku32', 'EH-BENZ-GLE-001', '奔驰GLE', '10', '个', '皮盒', 240.00, '24*18*10', 'OEM-GLE-EH-001', 7, 50, 78.00, 0, 30, 10, 1, 1, 30, 'BATCH-004', 'admin', NOW(), null, 1, 1, 1, 'admin', 'admin', NOW(), NOW(), 0, 1),
(603004, 100004, 'SC202604100026', 7033, '专用眼镜盒-灰色-大众途锐', 999999, 1002, '北京文浩轩智能科技有限公司', 'http://example.com/sku33', 'EH-TG-001', '大众途锐', '20', '个', '彩盒', 195.00, '21*15*9', 'OEM-TG-EH-001', 5, 60, 48.00, 0, 60, 20, 3, 1, 30, 'BATCH-004', 'admin', NOW(), null, 1, 1, 1, 'admin', 'admin', NOW(), NOW(), 0, 1),
(603005, 100004, 'SC202604100026', 7034, '专用眼镜盒-黑色-保时捷Panamera', 999999, 1002, '北京文浩轩智能科技有限公司', 'http://example.com/sku34', 'EH-PAN-001', '保时捷Panamera', '8', '个', '皮盒', 260.00, '25*19*10', 'OEM-PAN-EH-001', 7, 30, 95.00, 0, 25, 8, 1, 1, 30, 'BATCH-004', 'admin', NOW(), null, 1, 1, 1, 'admin', 'admin', NOW(), NOW(), 0, 1);
