-- Patch: Add project_types column to template tables
-- This allows templates to be filtered by project type

-- Add project_types to ors_task (stores comma-separated project types, NULL means all types)
ALTER TABLE `ors_task`
ADD COLUMN `project_types` VARCHAR(500) DEFAULT NULL
COMMENT 'Comma-separated project types this template applies to. NULL means all types.'
AFTER `template_source`;

-- Add project_types to ors_item (stores comma-separated project types, NULL means all types)
ALTER TABLE `ors_item`
ADD COLUMN `project_types` VARCHAR(500) DEFAULT NULL
COMMENT 'Comma-separated project types this template applies to. NULL means all types.'
AFTER `template_source`;

-- Add project_types to ors_lesson (stores comma-separated project types, NULL means all types)
ALTER TABLE `ors_lesson`
ADD COLUMN `project_types` VARCHAR(500) DEFAULT NULL
COMMENT 'Comma-separated project types this template applies to. NULL means all types.'
AFTER `template_source`;

-- Add indexes for performance
ALTER TABLE `ors_task` ADD INDEX `idx_project_types` (`project_types`(100));
ALTER TABLE `ors_item` ADD INDEX `idx_project_types` (`project_types`(100));
ALTER TABLE `ors_lesson` ADD INDEX `idx_project_types` (`project_types`(100));
