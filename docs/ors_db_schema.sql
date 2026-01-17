-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- 主机： mhdlmskp2kpxguj.mysql.db
-- 生成日期： 2026-01-17 13:09:13
-- 服务器版本： 8.4.6-6
-- PHP 版本： 8.1.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `mhdlmskp2kpxguj`
--
CREATE DATABASE IF NOT EXISTS `mhdlmskp2kpxguj` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `mhdlmskp2kpxguj`;

-- --------------------------------------------------------

--
-- 表的结构 `ors_check_item`
--

DROP TABLE IF EXISTS `ors_check_item`;
CREATE TABLE `ors_check_item` (
  `id` int UNSIGNED NOT NULL,
  `project_id` int UNSIGNED NOT NULL,
  `lesson_id` int UNSIGNED DEFAULT NULL COMMENT '来源Lesson',
  `check_content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `check_date` date DEFAULT NULL COMMENT '检查日期',
  `status` enum('pending','passed','failed','skipped') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `checked_by` int UNSIGNED DEFAULT NULL,
  `checked_at` datetime DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `ors_fx_rate`
--

DROP TABLE IF EXISTS `ors_fx_rate`;
CREATE TABLE `ors_fx_rate` (
  `id` int UNSIGNED NOT NULL,
  `from_currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL,
  `to_currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EUR',
  `rate` decimal(10,6) NOT NULL,
  `rate_date` date NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `ors_fx_rate`
--

INSERT INTO `ors_fx_rate` (`id`, `from_currency`, `to_currency`, `rate`, `rate_date`, `created_at`) VALUES
(1, 'CNY', 'EUR', 0.130000, '2026-01-16', '2026-01-16 21:24:23'),
(2, 'USD', 'EUR', 0.920000, '2026-01-16', '2026-01-16 21:24:23');

-- --------------------------------------------------------

--
-- 表的结构 `ors_item`
--

DROP TABLE IF EXISTS `ors_item`;
CREATE TABLE `ors_item` (
  `id` int UNSIGNED NOT NULL,
  `item_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'it_devices/furniture/consumables/equipment/other',
  `unit` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'pcs' COMMENT 'pcs/set/pack/m/kg',
  `must_buy_level` enum('must','recommended','optional') COLLATE utf8mb4_unicode_ci DEFAULT 'recommended',
  `description` text COLLATE utf8mb4_unicode_ci,
  `estimated_unit_price_eur` decimal(12,2) DEFAULT NULL,
  `long_lead_flag` tinyint(1) DEFAULT '0' COMMENT '是否长周期采购',
  `lead_time_days` int DEFAULT NULL COMMENT '采购提前期',
  `template_flag` tinyint(1) DEFAULT '0' COMMENT '是否为模板',
  `template_source` enum('global','project') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `ors_item`
--

INSERT INTO `ors_item` (`id`, `item_name`, `category`, `unit`, `must_buy_level`, `description`, `estimated_unit_price_eur`, `long_lead_flag`, `lead_time_days`, `template_flag`, `template_source`, `created_by`, `created_at`, `updated_at`) VALUES
(1, '收银机', 'it_devices', 'pcs', 'must', NULL, NULL, 1, 14, 1, 'global', NULL, '2026-01-16 21:24:23', NULL),
(2, '钱箱', 'it_devices', 'pcs', 'must', NULL, NULL, 0, 7, 1, 'global', NULL, '2026-01-16 21:24:23', NULL),
(3, '店用手机', 'it_devices', 'pcs', 'must', NULL, NULL, 0, 3, 1, 'global', NULL, '2026-01-16 21:24:23', NULL),
(4, '香薰机', 'equipment', 'pcs', 'recommended', NULL, NULL, 0, 7, 1, 'global', NULL, '2026-01-16 21:24:23', NULL),
(5, '广告灯箱', 'equipment', 'pcs', 'recommended', NULL, NULL, 1, 21, 1, 'global', NULL, '2026-01-16 21:24:23', NULL),
(6, '小票打印机', 'it_devices', 'pcs', 'must', NULL, NULL, 1, 14, 1, 'global', NULL, '2026-01-16 21:24:23', NULL),
(7, '音箱', 'equipment', 'pcs', 'recommended', NULL, NULL, 0, 7, 1, 'global', NULL, '2026-01-16 21:24:23', NULL),
(8, '平板支架', 'it_devices', 'pcs', 'must', NULL, NULL, 0, 5, 1, 'global', NULL, '2026-01-16 21:24:23', NULL),
(9, 'KDS平板', 'it_devices', 'pcs', 'must', NULL, NULL, 1, 14, 1, 'global', NULL, '2026-01-16 21:24:23', NULL),
(10, '印章', 'equipment', 'pcs', 'must', NULL, NULL, 0, 7, 1, 'global', NULL, '2026-01-16 21:24:23', NULL),
(11, '遮挡帘', 'furniture', 'pcs', 'recommended', NULL, NULL, 0, 14, 1, 'global', NULL, '2026-01-16 21:24:23', NULL),
(12, '工作衣帽', 'consumables', 'set', 'must', NULL, NULL, 1, 21, 1, 'global', NULL, '2026-01-16 21:24:23', NULL),
(13, '路由器', 'it_devices', 'pcs', 'must', NULL, NULL, 0, 3, 1, 'global', NULL, '2026-01-16 21:24:23', NULL);

-- --------------------------------------------------------

--
-- 表的结构 `ors_lesson`
--

DROP TABLE IF EXISTS `ors_lesson`;
CREATE TABLE `ors_lesson` (
  `id` int UNSIGNED NOT NULL,
  `project_id` int UNSIGNED DEFAULT NULL,
  `title` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `category` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'it/power/fire_safety/permit/procurement/other',
  `severity` enum('low','medium','high','critical') COLLATE utf8mb4_unicode_ci DEFAULT 'medium',
  `root_cause` text COLLATE utf8mb4_unicode_ci,
  `prevention_check_item` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '预防检查项-必填',
  `check_timing` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '检查时间点描述',
  `check_days_before_open` int DEFAULT NULL COMMENT '开业前N天检查',
  `check_days_after_sign` int DEFAULT NULL COMMENT '签约后N天检查',
  `template_flag` tinyint(1) DEFAULT '1' COMMENT 'Lesson默认为模板',
  `template_source` enum('global','project') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `ors_lesson`
--

INSERT INTO `ors_lesson` (`id`, `project_id`, `title`, `description`, `category`, `severity`, `root_cause`, `prevention_check_item`, `check_timing`, `check_days_before_open`, `check_days_after_sign`, `template_flag`, `template_source`, `created_by`, `created_at`, `updated_at`) VALUES
(1, NULL, '收银链路漏项', '开业前发现POS/钱箱/打印机/网络/KDS/支架等收银链路设备未齐全或未联调', 'it', 'critical', NULL, '开业前7天必须完成POS全链路联调实测出票，包括：POS主机、钱箱、小票打印机、网络连接、KDS平板、平板支架全部到位并测试通过', '开业前7天', 7, NULL, 1, 'global', NULL, '2026-01-16 21:24:23', NULL),
(2, NULL, '电力升级未前置', '装修后期发现电力不足需要增容，导致工期延误', 'power', 'high', NULL, '签约后3天内必须完成电力负载评估，确认是否需要增容', '签约后3天', NULL, 3, 1, 'global', NULL, '2026-01-16 21:24:23', NULL),
(3, NULL, '消防合同遗漏', '开业前才发现未签订消防维护合同或未获取消防证书', 'fire_safety', 'critical', NULL, '开业前14天必须确认已获取消防验收证书和灭火器合格证', '开业前14天', 14, NULL, 1, 'global', NULL, '2026-01-16 21:24:23', NULL);

-- --------------------------------------------------------

--
-- 表的结构 `ors_phase`
--

DROP TABLE IF EXISTS `ors_phase`;
CREATE TABLE `ors_phase` (
  `id` int UNSIGNED NOT NULL,
  `phase_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phase_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int DEFAULT '0',
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `ors_phase`
--

INSERT INTO `ors_phase` (`id`, `phase_code`, `phase_name`, `sort_order`, `description`, `created_at`) VALUES
(1, 'site_selection', '选址', 10, '选址与租约谈判阶段', '2026-01-16 21:24:23'),
(2, 'contract', '签约', 20, '合同签署阶段', '2026-01-16 21:24:23'),
(3, 'design', '设计', 30, '店铺设计阶段', '2026-01-16 21:24:23'),
(4, 'permit', '证照', 40, '证照办理阶段', '2026-01-16 21:24:23'),
(5, 'decoration', '装修', 50, '店铺装修阶段', '2026-01-16 21:24:23'),
(6, 'procurement', '采购', 60, '设备与物品采购阶段', '2026-01-16 21:24:23'),
(7, 'installation', '安装', 70, '设备安装阶段', '2026-01-16 21:24:23'),
(8, 'training', '培训', 80, '员工培训阶段', '2026-01-16 21:24:23'),
(9, 'pre_opening', '开业筹备', 90, '开业前准备阶段', '2026-01-16 21:24:23'),
(10, 'opening', '开业', 100, '正式开业阶段', '2026-01-16 21:24:23');

-- --------------------------------------------------------

--
-- 表的结构 `ors_project`
--

DROP TABLE IF EXISTS `ors_project`;
CREATE TABLE `ors_project` (
  `id` int UNSIGNED NOT NULL,
  `project_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `project_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'cafeteria' COMMENT 'cafeteria/restaurant/retail/bubble_tea/ice_cream/dessert/fried_chicken/poke/sushi',
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `area_m2` decimal(10,2) DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `target_open_date` date DEFAULT NULL,
  `actual_open_date` date DEFAULT NULL,
  `status` enum('planning','active','completed','archived') COLLATE utf8mb4_unicode_ci DEFAULT 'planning',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` int UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `ors_purchase`
--

DROP TABLE IF EXISTS `ors_purchase`;
CREATE TABLE `ors_purchase` (
  `id` int UNSIGNED NOT NULL,
  `project_id` int UNSIGNED DEFAULT NULL,
  `item_id` int UNSIGNED DEFAULT NULL COMMENT '关联物品库',
  `free_text_item` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '自由文本物品名（未归一化）',
  `quantity` decimal(10,2) DEFAULT '1.00',
  `unit` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'pcs',
  `unit_price` decimal(12,2) DEFAULT NULL,
  `currency` enum('EUR','CNY','USD') COLLATE utf8mb4_unicode_ci DEFAULT 'EUR',
  `fx_rate_to_eur` decimal(10,6) DEFAULT NULL COMMENT '汇率快照',
  `total_price_eur` decimal(12,2) DEFAULT NULL COMMENT '折算EUR总价',
  `vendor_id` int UNSIGNED DEFAULT NULL,
  `status` enum('planned','ordered','shipped','received','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'planned',
  `order_date` date DEFAULT NULL,
  `expected_delivery` date DEFAULT NULL,
  `actual_delivery` date DEFAULT NULL,
  `latest_order_date` date DEFAULT NULL COMMENT '最晚下单日',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `template_flag` tinyint(1) DEFAULT '0',
  `template_source` enum('global','project') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `ors_task`
--

DROP TABLE IF EXISTS `ors_task`;
CREATE TABLE `ors_task` (
  `id` int UNSIGNED NOT NULL,
  `project_id` int UNSIGNED DEFAULT NULL,
  `title` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `phase_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('todo','doing','blocked','done') COLLATE utf8mb4_unicode_ci DEFAULT 'todo',
  `block_reason` enum('waiting_vendor','waiting_approval','waiting_material','waiting_budget','technical_issue','other') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `block_reason_detail` text COLLATE utf8mb4_unicode_ci,
  `priority` enum('low','medium','high','urgent') COLLATE utf8mb4_unicode_ci DEFAULT 'medium',
  `assigned_to` int UNSIGNED DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `latest_start_date` date DEFAULT NULL COMMENT '最晚启动日',
  `completed_at` datetime DEFAULT NULL,
  `blocking_flag` tinyint(1) DEFAULT '0' COMMENT '是否阻塞关键路径',
  `lead_time_days` int DEFAULT NULL COMMENT '提前期（天）',
  `template_flag` tinyint(1) DEFAULT '0' COMMENT '是否为模板',
  `template_source` enum('global','project') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_task_id` int UNSIGNED DEFAULT NULL COMMENT '模板来源任务ID',
  `created_by` int UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `ors_task`
--

INSERT INTO `ors_task` (`id`, `project_id`, `title`, `description`, `phase_code`, `status`, `block_reason`, `block_reason_detail`, `priority`, `assigned_to`, `due_date`, `latest_start_date`, `completed_at`, `blocking_flag`, `lead_time_days`, `template_flag`, `template_source`, `source_task_id`, `created_by`, `created_at`, `updated_at`) VALUES
(1, NULL, '灭火器维护合同签订', '签订灭火器维护服务合同', 'permit', 'todo', NULL, NULL, 'medium', NULL, NULL, NULL, NULL, 1, 30, 1, 'global', NULL, NULL, '2026-01-16 21:24:23', '2026-01-16 23:37:10'),
(2, NULL, '灭火器证书获取', '获取灭火器检测合格证书', 'permit', 'todo', NULL, NULL, 'medium', NULL, NULL, NULL, NULL, 1, 14, 1, 'global', NULL, NULL, '2026-01-16 21:24:23', NULL),
(3, NULL, '消防验收证书获取', '完成消防验收并获取证书', 'permit', 'todo', NULL, NULL, 'medium', NULL, NULL, NULL, NULL, 1, 21, 1, 'global', NULL, NULL, '2026-01-16 21:24:23', NULL),
(4, NULL, '电力负载评估', '评估店铺电力负载需求', 'design', 'todo', NULL, NULL, 'medium', NULL, NULL, NULL, NULL, 1, 3, 1, 'global', NULL, NULL, '2026-01-16 21:24:23', NULL),
(5, NULL, '电力增容报价', '获取电力增容报价', 'design', 'todo', NULL, NULL, 'medium', NULL, NULL, NULL, NULL, 1, 7, 1, 'global', NULL, NULL, '2026-01-16 21:24:23', NULL),
(6, NULL, '电力增容施工排期', '确定电力增容施工时间', 'decoration', 'todo', NULL, NULL, 'medium', NULL, NULL, NULL, NULL, 1, 14, 1, 'global', NULL, NULL, '2026-01-16 21:24:23', NULL),
(7, NULL, '电力增容施工', '执行电力增容施工', 'decoration', 'todo', NULL, NULL, 'medium', NULL, NULL, NULL, NULL, 1, 21, 1, 'global', NULL, NULL, '2026-01-16 21:24:23', NULL),
(8, NULL, '电力增容验收', '完成电力增容验收', 'decoration', 'todo', NULL, NULL, 'medium', NULL, NULL, NULL, NULL, 1, 7, 1, 'global', NULL, NULL, '2026-01-16 21:24:23', NULL),
(9, NULL, '印章制作', '制作公司印章', 'contract', 'todo', NULL, NULL, 'medium', NULL, NULL, NULL, NULL, 0, 7, 1, 'global', NULL, NULL, '2026-01-16 21:24:23', NULL),
(10, NULL, '印章收货确认', '确认收到印章', 'contract', 'todo', NULL, NULL, 'medium', NULL, NULL, NULL, NULL, 0, 3, 1, 'global', NULL, NULL, '2026-01-16 21:24:23', NULL),
(11, NULL, 'POS系统安装', '安装POS收银系统', 'installation', 'todo', NULL, NULL, 'medium', NULL, NULL, NULL, NULL, 1, 7, 1, 'global', NULL, NULL, '2026-01-16 21:24:23', NULL),
(12, NULL, 'KDS系统安装', '安装厨房显示系统', 'installation', 'todo', NULL, NULL, 'medium', NULL, NULL, NULL, NULL, 1, 7, 1, 'global', NULL, NULL, '2026-01-16 21:24:23', NULL),
(13, NULL, '网络布线与调试', '完成网络布线和路由器配置', 'installation', 'todo', NULL, NULL, 'medium', NULL, NULL, NULL, NULL, 1, 5, 1, 'global', NULL, NULL, '2026-01-16 21:24:23', NULL),
(14, NULL, '收银全链路联调测试', '测试POS-打印机-钱箱-KDS全链路', 'pre_opening', 'todo', NULL, NULL, 'medium', NULL, NULL, NULL, NULL, 1, 7, 1, 'global', NULL, NULL, '2026-01-16 21:24:23', NULL);

-- --------------------------------------------------------

--
-- 表的结构 `ors_task_dependency`
--

DROP TABLE IF EXISTS `ors_task_dependency`;
CREATE TABLE `ors_task_dependency` (
  `id` int UNSIGNED NOT NULL,
  `task_id` int UNSIGNED NOT NULL,
  `depends_on_task_id` int UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `ors_template_tag`
--

DROP TABLE IF EXISTS `ors_template_tag`;
CREATE TABLE `ors_template_tag` (
  `id` int UNSIGNED NOT NULL,
  `entity_type` enum('task','item','purchase','lesson') COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_id` int UNSIGNED NOT NULL,
  `tag_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `ors_user`
--

DROP TABLE IF EXISTS `ors_user`;
CREATE TABLE `ors_user` (
  `id` int UNSIGNED NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','staff') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'staff',
  `display_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `token_expires_at` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转存表中的数据 `ors_user`
--

INSERT INTO `ors_user` (`id`, `username`, `password_hash`, `role`, `display_name`, `email`, `remember_token`, `token_expires_at`, `last_login_at`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '管理员', NULL, '7033a06efc6bea9238dc33b82b2eceac94a0a33f946065dcc6db8846dff6e598', '2026-01-24 04:29:02', '2026-01-17 04:29:02', '2026-01-16 21:24:23', '2026-01-16 21:29:02'),
(2, 'staff', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', '员工', NULL, NULL, NULL, NULL, '2026-01-16 21:24:23', NULL);

-- --------------------------------------------------------

--
-- 表的结构 `ors_vendor`
--

DROP TABLE IF EXISTS `ors_vendor`;
CREATE TABLE `ors_vendor` (
  `id` int UNSIGNED NOT NULL,
  `vendor_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'it/furniture/decoration/food/service/other',
  `contact_person` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `website` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rating` tinyint DEFAULT NULL COMMENT '1-5星',
  `rating_comment` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '一句话评价',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` int UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- 转储表的索引
--

--
-- 表的索引 `ors_check_item`
--
ALTER TABLE `ors_check_item`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_project_id` (`project_id`),
  ADD KEY `idx_lesson_id` (`lesson_id`),
  ADD KEY `idx_check_date` (`check_date`),
  ADD KEY `idx_status` (`status`);

--
-- 表的索引 `ors_fx_rate`
--
ALTER TABLE `ors_fx_rate`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_currencies` (`from_currency`,`to_currency`),
  ADD KEY `idx_rate_date` (`rate_date`);

--
-- 表的索引 `ors_item`
--
ALTER TABLE `ors_item`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_template_flag` (`template_flag`),
  ADD KEY `idx_item_name` (`item_name`);

--
-- 表的索引 `ors_lesson`
--
ALTER TABLE `ors_lesson`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_project_id` (`project_id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_template_flag` (`template_flag`);

--
-- 表的索引 `ors_phase`
--
ALTER TABLE `ors_phase`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phase_code` (`phase_code`),
  ADD KEY `idx_sort_order` (`sort_order`);

--
-- 表的索引 `ors_project`
--
ALTER TABLE `ors_project`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_target_open_date` (`target_open_date`);

--
-- 表的索引 `ors_purchase`
--
ALTER TABLE `ors_purchase`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_project_id` (`project_id`),
  ADD KEY `idx_item_id` (`item_id`),
  ADD KEY `idx_vendor_id` (`vendor_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_template_flag` (`template_flag`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- 表的索引 `ors_task`
--
ALTER TABLE `ors_task`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_project_id` (`project_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_phase_code` (`phase_code`),
  ADD KEY `idx_template_flag` (`template_flag`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- 表的索引 `ors_task_dependency`
--
ALTER TABLE `ors_task_dependency`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_task_depends` (`task_id`,`depends_on_task_id`),
  ADD KEY `idx_task_id` (`task_id`),
  ADD KEY `idx_depends_on` (`depends_on_task_id`);

--
-- 表的索引 `ors_template_tag`
--
ALTER TABLE `ors_template_tag`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_entity` (`entity_type`,`entity_id`),
  ADD KEY `idx_tag_name` (`tag_name`);

--
-- 表的索引 `ors_user`
--
ALTER TABLE `ors_user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_remember_token` (`remember_token`);

--
-- 表的索引 `ors_vendor`
--
ALTER TABLE `ors_vendor`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_vendor_name` (`vendor_name`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `ors_check_item`
--
ALTER TABLE `ors_check_item`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `ors_fx_rate`
--
ALTER TABLE `ors_fx_rate`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- 使用表AUTO_INCREMENT `ors_item`
--
ALTER TABLE `ors_item`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- 使用表AUTO_INCREMENT `ors_lesson`
--
ALTER TABLE `ors_lesson`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- 使用表AUTO_INCREMENT `ors_phase`
--
ALTER TABLE `ors_phase`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- 使用表AUTO_INCREMENT `ors_project`
--
ALTER TABLE `ors_project`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `ors_purchase`
--
ALTER TABLE `ors_purchase`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `ors_task`
--
ALTER TABLE `ors_task`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- 使用表AUTO_INCREMENT `ors_task_dependency`
--
ALTER TABLE `ors_task_dependency`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `ors_template_tag`
--
ALTER TABLE `ors_template_tag`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `ors_user`
--
ALTER TABLE `ors_user`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- 使用表AUTO_INCREMENT `ors_vendor`
--
ALTER TABLE `ors_vendor`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
