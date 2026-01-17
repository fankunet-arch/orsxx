-- ORS (Opening Roadmap System) Database Schema
-- Version: 1.0
-- All tables must have prefix 'ors_'

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table: ors_user (用户表)
-- ----------------------------
DROP TABLE IF EXISTS `ors_user`;
CREATE TABLE `ors_user` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
  `display_name` VARCHAR(100) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `remember_token` VARCHAR(100) DEFAULT NULL,
  `token_expires_at` DATETIME DEFAULT NULL,
  `last_login_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_username` (`username`),
  KEY `idx_remember_token` (`remember_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: ors_project (项目表)
-- ----------------------------
DROP TABLE IF EXISTS `ors_project`;
CREATE TABLE `ors_project` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_name` VARCHAR(200) NOT NULL,
  `project_type` VARCHAR(50) DEFAULT 'cafeteria' COMMENT 'cafeteria/restaurant/retail',
  `city` VARCHAR(100) DEFAULT NULL,
  `area_m2` DECIMAL(10,2) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `target_open_date` DATE DEFAULT NULL,
  `actual_open_date` DATE DEFAULT NULL,
  `status` ENUM('planning', 'active', 'completed', 'archived') DEFAULT 'planning',
  `notes` TEXT DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_target_open_date` (`target_open_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: ors_phase (阶段表)
-- ----------------------------
DROP TABLE IF EXISTS `ors_phase`;
CREATE TABLE `ors_phase` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `phase_code` VARCHAR(50) NOT NULL UNIQUE,
  `phase_name` VARCHAR(100) NOT NULL,
  `sort_order` INT DEFAULT 0,
  `description` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: ors_task (任务表)
-- ----------------------------
DROP TABLE IF EXISTS `ors_task`;
CREATE TABLE `ors_task` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` INT UNSIGNED DEFAULT NULL,
  `title` VARCHAR(500) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `phase_code` VARCHAR(50) DEFAULT NULL,
  `status` ENUM('todo', 'doing', 'blocked', 'done') DEFAULT 'todo',
  `block_reason` ENUM('waiting_vendor', 'waiting_approval', 'waiting_material', 'waiting_budget', 'technical_issue', 'other') DEFAULT NULL,
  `block_reason_detail` TEXT DEFAULT NULL,
  `priority` ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
  `assigned_to` INT UNSIGNED DEFAULT NULL,
  `due_date` DATE DEFAULT NULL,
  `latest_start_date` DATE DEFAULT NULL COMMENT '最晚启动日',
  `completed_at` DATETIME DEFAULT NULL,
  `blocking_flag` TINYINT(1) DEFAULT 0 COMMENT '是否阻塞关键路径',
  `lead_time_days` INT DEFAULT NULL COMMENT '提前期（天）',
  `template_flag` TINYINT(1) DEFAULT 0 COMMENT '是否为模板',
  `template_source` ENUM('global', 'project') DEFAULT NULL,
  `source_task_id` INT UNSIGNED DEFAULT NULL COMMENT '模板来源任务ID',
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_status` (`status`),
  KEY `idx_phase_code` (`phase_code`),
  KEY `idx_template_flag` (`template_flag`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: ors_task_dependency (任务依赖表 - 最多3个依赖)
-- ----------------------------
DROP TABLE IF EXISTS `ors_task_dependency`;
CREATE TABLE `ors_task_dependency` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `task_id` INT UNSIGNED NOT NULL,
  `depends_on_task_id` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_task_depends` (`task_id`, `depends_on_task_id`),
  KEY `idx_task_id` (`task_id`),
  KEY `idx_depends_on` (`depends_on_task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: ors_item (物品库表)
-- ----------------------------
DROP TABLE IF EXISTS `ors_item`;
CREATE TABLE `ors_item` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_name` VARCHAR(200) NOT NULL,
  `category` VARCHAR(50) DEFAULT NULL COMMENT 'it_devices/furniture/consumables/equipment/other',
  `unit` VARCHAR(20) DEFAULT 'pcs' COMMENT 'pcs/set/pack/m/kg',
  `must_buy_level` ENUM('must', 'recommended', 'optional') DEFAULT 'recommended',
  `description` TEXT DEFAULT NULL,
  `estimated_unit_price_eur` DECIMAL(12,2) DEFAULT NULL,
  `long_lead_flag` TINYINT(1) DEFAULT 0 COMMENT '是否长周期采购',
  `lead_time_days` INT DEFAULT NULL COMMENT '采购提前期',
  `template_flag` TINYINT(1) DEFAULT 0 COMMENT '是否为模板',
  `template_source` ENUM('global', 'project') DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_template_flag` (`template_flag`),
  KEY `idx_item_name` (`item_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: ors_vendor (供应商表)
-- ----------------------------
DROP TABLE IF EXISTS `ors_vendor`;
CREATE TABLE `ors_vendor` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `vendor_name` VARCHAR(200) NOT NULL,
  `category` VARCHAR(50) DEFAULT NULL COMMENT 'it/furniture/decoration/food/service/other',
  `contact_person` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `website` VARCHAR(255) DEFAULT NULL,
  `rating` TINYINT DEFAULT NULL COMMENT '1-5星',
  `rating_comment` VARCHAR(500) DEFAULT NULL COMMENT '一句话评价',
  `notes` TEXT DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_vendor_name` (`vendor_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: ors_purchase (采购表)
-- ----------------------------
DROP TABLE IF EXISTS `ors_purchase`;
CREATE TABLE `ors_purchase` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` INT UNSIGNED DEFAULT NULL,
  `item_id` INT UNSIGNED DEFAULT NULL COMMENT '关联物品库',
  `free_text_item` VARCHAR(200) DEFAULT NULL COMMENT '自由文本物品名（未归一化）',
  `quantity` DECIMAL(10,2) DEFAULT 1,
  `unit` VARCHAR(20) DEFAULT 'pcs',
  `unit_price` DECIMAL(12,2) DEFAULT NULL,
  `currency` ENUM('EUR', 'CNY', 'USD') DEFAULT 'EUR',
  `fx_rate_to_eur` DECIMAL(10,6) DEFAULT NULL COMMENT '汇率快照',
  `total_price_eur` DECIMAL(12,2) DEFAULT NULL COMMENT '折算EUR总价',
  `vendor_id` INT UNSIGNED DEFAULT NULL,
  `status` ENUM('planned', 'ordered', 'shipped', 'received', 'cancelled') DEFAULT 'planned',
  `order_date` DATE DEFAULT NULL,
  `expected_delivery` DATE DEFAULT NULL,
  `actual_delivery` DATE DEFAULT NULL,
  `latest_order_date` DATE DEFAULT NULL COMMENT '最晚下单日',
  `notes` TEXT DEFAULT NULL,
  `template_flag` TINYINT(1) DEFAULT 0,
  `template_source` ENUM('global', 'project') DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_item_id` (`item_id`),
  KEY `idx_vendor_id` (`vendor_id`),
  KEY `idx_status` (`status`),
  KEY `idx_template_flag` (`template_flag`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: ors_lesson (踩坑记录表)
-- ----------------------------
DROP TABLE IF EXISTS `ors_lesson`;
CREATE TABLE `ors_lesson` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` INT UNSIGNED DEFAULT NULL,
  `title` VARCHAR(500) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `category` VARCHAR(50) DEFAULT NULL COMMENT 'it/power/fire_safety/permit/procurement/other',
  `severity` ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
  `root_cause` TEXT DEFAULT NULL,
  `prevention_check_item` TEXT NOT NULL COMMENT '预防检查项-必填',
  `check_timing` VARCHAR(200) DEFAULT NULL COMMENT '检查时间点描述',
  `check_days_before_open` INT DEFAULT NULL COMMENT '开业前N天检查',
  `check_days_after_sign` INT DEFAULT NULL COMMENT '签约后N天检查',
  `template_flag` TINYINT(1) DEFAULT 1 COMMENT 'Lesson默认为模板',
  `template_source` ENUM('global', 'project') DEFAULT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_category` (`category`),
  KEY `idx_template_flag` (`template_flag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: ors_check_item (检查项表 - 由Lesson生成)
-- ----------------------------
DROP TABLE IF EXISTS `ors_check_item`;
CREATE TABLE `ors_check_item` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` INT UNSIGNED NOT NULL,
  `lesson_id` INT UNSIGNED DEFAULT NULL COMMENT '来源Lesson',
  `check_content` TEXT NOT NULL,
  `check_date` DATE DEFAULT NULL COMMENT '检查日期',
  `status` ENUM('pending', 'passed', 'failed', 'skipped') DEFAULT 'pending',
  `checked_by` INT UNSIGNED DEFAULT NULL,
  `checked_at` DATETIME DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_lesson_id` (`lesson_id`),
  KEY `idx_check_date` (`check_date`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: ors_template_tag (模板标签表)
-- ----------------------------
DROP TABLE IF EXISTS `ors_template_tag`;
CREATE TABLE `ors_template_tag` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `entity_type` ENUM('task', 'item', 'purchase', 'lesson') NOT NULL,
  `entity_id` INT UNSIGNED NOT NULL,
  `tag_name` VARCHAR(50) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_entity` (`entity_type`, `entity_id`),
  KEY `idx_tag_name` (`tag_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------
-- Table: ors_fx_rate (汇率快照表)
-- ----------------------------
DROP TABLE IF EXISTS `ors_fx_rate`;
CREATE TABLE `ors_fx_rate` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `from_currency` VARCHAR(3) NOT NULL,
  `to_currency` VARCHAR(3) NOT NULL DEFAULT 'EUR',
  `rate` DECIMAL(10,6) NOT NULL,
  `rate_date` DATE NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_currencies` (`from_currency`, `to_currency`),
  KEY `idx_rate_date` (`rate_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------
-- Insert default phases
-- ----------------------------
INSERT INTO `ors_phase` (`phase_code`, `phase_name`, `sort_order`, `description`) VALUES
('site_selection', '选址', 10, '选址与租约谈判阶段'),
('contract', '签约', 20, '合同签署阶段'),
('design', '设计', 30, '店铺设计阶段'),
('permit', '证照', 40, '证照办理阶段'),
('decoration', '装修', 50, '店铺装修阶段'),
('procurement', '采购', 60, '设备与物品采购阶段'),
('installation', '安装', 70, '设备安装阶段'),
('training', '培训', 80, '员工培训阶段'),
('pre_opening', '开业筹备', 90, '开业前准备阶段'),
('opening', '开业', 100, '正式开业阶段');

-- ----------------------------
-- Insert default admin user (password: admin123)
-- ----------------------------
INSERT INTO `ors_user` (`username`, `password_hash`, `role`, `display_name`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '管理员'),
('staff', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', '员工');

-- ----------------------------
-- Insert default FX rates
-- ----------------------------
INSERT INTO `ors_fx_rate` (`from_currency`, `to_currency`, `rate`, `rate_date`) VALUES
('CNY', 'EUR', 0.13, CURDATE()),
('USD', 'EUR', 0.92, CURDATE());

-- ----------------------------
-- Insert default template items (物品库模板)
-- ----------------------------
INSERT INTO `ors_item` (`item_name`, `category`, `unit`, `must_buy_level`, `template_flag`, `template_source`, `long_lead_flag`, `lead_time_days`) VALUES
('收银机', 'it_devices', 'pcs', 'must', 1, 'global', 1, 14),
('钱箱', 'it_devices', 'pcs', 'must', 1, 'global', 0, 7),
('店用手机', 'it_devices', 'pcs', 'must', 1, 'global', 0, 3),
('香薰机', 'equipment', 'pcs', 'recommended', 1, 'global', 0, 7),
('广告灯箱', 'equipment', 'pcs', 'recommended', 1, 'global', 1, 21),
('小票打印机', 'it_devices', 'pcs', 'must', 1, 'global', 1, 14),
('音箱', 'equipment', 'pcs', 'recommended', 1, 'global', 0, 7),
('平板支架', 'it_devices', 'pcs', 'must', 1, 'global', 0, 5),
('KDS平板', 'it_devices', 'pcs', 'must', 1, 'global', 1, 14),
('印章', 'equipment', 'pcs', 'must', 1, 'global', 0, 7),
('遮挡帘', 'furniture', 'pcs', 'recommended', 1, 'global', 0, 14),
('工作衣帽', 'consumables', 'set', 'must', 1, 'global', 1, 21),
('路由器', 'it_devices', 'pcs', 'must', 1, 'global', 0, 3);

-- ----------------------------
-- Insert default template tasks (任务模板)
-- ----------------------------
INSERT INTO `ors_task` (`title`, `description`, `phase_code`, `template_flag`, `template_source`, `blocking_flag`, `lead_time_days`) VALUES
('灭火器维护合同签订', '签订灭火器维护服务合同', 'permit', 1, 'global', 1, 30),
('灭火器证书获取', '获取灭火器检测合格证书', 'permit', 1, 'global', 1, 14),
('消防验收证书获取', '完成消防验收并获取证书', 'permit', 1, 'global', 1, 21),
('电力负载评估', '评估店铺电力负载需求', 'design', 1, 'global', 1, 3),
('电力增容报价', '获取电力增容报价', 'design', 1, 'global', 1, 7),
('电力增容施工排期', '确定电力增容施工时间', 'decoration', 1, 'global', 1, 14),
('电力增容施工', '执行电力增容施工', 'decoration', 1, 'global', 1, 21),
('电力增容验收', '完成电力增容验收', 'decoration', 1, 'global', 1, 7),
('印章制作', '制作公司印章', 'contract', 1, 'global', 0, 7),
('印章收货确认', '确认收到印章', 'contract', 1, 'global', 0, 3),
('POS系统安装', '安装POS收银系统', 'installation', 1, 'global', 1, 7),
('KDS系统安装', '安装厨房显示系统', 'installation', 1, 'global', 1, 7),
('网络布线与调试', '完成网络布线和路由器配置', 'installation', 1, 'global', 1, 5),
('收银全链路联调测试', '测试POS-打印机-钱箱-KDS全链路', 'pre_opening', 1, 'global', 1, 7);

-- ----------------------------
-- Insert default template lessons (踩坑记录模板)
-- ----------------------------
INSERT INTO `ors_lesson` (`title`, `description`, `category`, `severity`, `prevention_check_item`, `check_timing`, `check_days_before_open`, `check_days_after_sign`, `template_flag`, `template_source`) VALUES
('收银链路漏项', '开业前发现POS/钱箱/打印机/网络/KDS/支架等收银链路设备未齐全或未联调', 'it', 'critical', '开业前7天必须完成POS全链路联调实测出票，包括：POS主机、钱箱、小票打印机、网络连接、KDS平板、平板支架全部到位并测试通过', '开业前7天', 7, NULL, 1, 'global'),
('电力升级未前置', '装修后期发现电力不足需要增容，导致工期延误', 'power', 'high', '签约后3天内必须完成电力负载评估，确认是否需要增容', '签约后3天', NULL, 3, 1, 'global'),
('消防合同遗漏', '开业前才发现未签订消防维护合同或未获取消防证书', 'fire_safety', 'critical', '开业前14天必须确认已获取消防验收证书和灭火器合格证', '开业前14天', 14, NULL, 1, 'global');
