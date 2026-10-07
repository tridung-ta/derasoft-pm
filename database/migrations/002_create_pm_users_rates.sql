-- Phase 3: tenant-scoped departments, user attributes and effective hourly rates.
-- Prerequisite: Phase 2 migration applied and a verified local/staging backup.
-- Preflight: confirm the two dc_users columns and both PM tables do not exist.

-- Approved Phase 2 compatibility upgrade: widen tenant identifiers and scope
-- permission records/mappings to the current store without deleting data.
ALTER TABLE `dc_pm_roles`
    MODIFY COLUMN `store_id` BIGINT UNSIGNED NOT NULL DEFAULT 0;

ALTER TABLE `dc_pm_user_roles`
    MODIFY COLUMN `store_id` BIGINT UNSIGNED NOT NULL DEFAULT 0;

ALTER TABLE `dc_pm_permissions`
    ADD COLUMN `store_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER `id`;

UPDATE `dc_pm_permissions`
SET `store_id` = (SELECT MIN(`store_id`) FROM `dc_pm_roles`)
WHERE `store_id` = 0;

ALTER TABLE `dc_pm_permissions`
    DROP INDEX `uq_pm_permissions_code`,
    ADD UNIQUE KEY `uq_pm_permissions_store_code` (`store_id`, `code`),
    ADD KEY `idx_pm_permissions_store_module` (`store_id`, `module`);

ALTER TABLE `dc_pm_role_permissions`
    ADD COLUMN `store_id` BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER `id`;

UPDATE `dc_pm_role_permissions` rp
INNER JOIN `dc_pm_roles` r ON r.`id` = rp.`role_id`
SET rp.`store_id` = r.`store_id`
WHERE rp.`store_id` = 0;

ALTER TABLE `dc_pm_role_permissions`
    ADD KEY `idx_pm_role_permissions_role` (`role_id`),
    DROP INDEX `uq_pm_role_permissions_assignment`,
    ADD UNIQUE KEY `uq_pm_role_permissions_store_assignment` (`store_id`, `role_id`, `permission_id`),
    ADD KEY `idx_pm_role_permissions_store_permission` (`store_id`, `permission_id`);

ALTER TABLE `dc_users`
    ADD COLUMN `department_id` BIGINT UNSIGNED NULL AFTER `store_id`,
    ADD COLUMN `weekly_limit_hours` DECIMAL(5,2) NOT NULL DEFAULT 40.00 AFTER `department_id`,
    ADD INDEX `idx_users_store_department` (`store_id`, `department_id`);

CREATE TABLE IF NOT EXISTS `dc_pm_departments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `store_id` BIGINT UNSIGNED NOT NULL,
    `code` VARCHAR(50) NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `description` VARCHAR(255) NULL,
    `status` TINYINT(1) NOT NULL DEFAULT 1,
    `deleted_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_pm_departments_store_code` (`store_id`, `code`),
    KEY `idx_pm_departments_store_status` (`store_id`, `status`, `deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `dc_pm_hourly_rates` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `store_id` BIGINT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NULL,
    `role_id` BIGINT UNSIGNED NULL,
    `rate` DECIMAL(15,2) UNSIGNED NOT NULL,
    `currency` CHAR(3) NOT NULL DEFAULT 'VND',
    `effective_from` DATE NOT NULL,
    `effective_to` DATE NULL,
    `status` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` INT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_pm_rates_store_user_dates` (`store_id`, `user_id`, `status`, `effective_from`, `effective_to`),
    KEY `idx_pm_rates_store_role_dates` (`store_id`, `role_id`, `status`, `effective_from`, `effective_to`),
    CONSTRAINT `fk_pm_rates_role` FOREIGN KEY (`role_id`) REFERENCES `dc_pm_roles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Owner XOR and overlapping effective periods are validated in PmRateService.
-- The service locks active owner rows with SELECT ... FOR UPDATE before checking.
-- Verification:
--   SHOW COLUMNS FROM dc_users LIKE 'department_id';
--   SHOW COLUMNS FROM dc_users LIKE 'weekly_limit_hours';
--   SHOW CREATE TABLE dc_pm_departments;
--   SHOW CREATE TABLE dc_pm_hourly_rates;
-- Rollback: project policy forbids DROP. Roll back application code and leave the
-- additive nullable/defaulted columns and unused PM tables in place.
