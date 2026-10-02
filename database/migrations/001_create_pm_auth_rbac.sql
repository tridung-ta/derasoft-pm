-- Phase 2: additive authentication and RBAC foundation for DeraSoft PM.
-- Prerequisite: MySQL 8+, an existing dc_users table, and a verified backup.
-- Preflight: confirm dc_users.password_hash does not already exist before running.

ALTER TABLE `dc_users`
    ADD COLUMN `password_hash` VARCHAR(255) NULL AFTER `password`;

CREATE TABLE IF NOT EXISTS `dc_pm_roles` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `store_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `code` VARCHAR(50) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) NULL,
    `is_system` TINYINT(1) NOT NULL DEFAULT 1,
    `status` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_pm_roles_store_code` (`store_id`, `code`),
    KEY `idx_pm_roles_store_status` (`store_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `dc_pm_permissions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(100) NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `module` VARCHAR(50) NOT NULL,
    `description` VARCHAR(255) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_pm_permissions_code` (`code`),
    KEY `idx_pm_permissions_module` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `dc_pm_user_roles` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `store_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `user_id` INT UNSIGNED NOT NULL,
    `role_id` BIGINT UNSIGNED NOT NULL,
    `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_pm_user_roles_assignment` (`store_id`, `user_id`, `role_id`),
    KEY `idx_pm_user_roles_user` (`store_id`, `user_id`, `is_primary`),
    KEY `idx_pm_user_roles_role` (`role_id`),
    CONSTRAINT `fk_pm_user_roles_role` FOREIGN KEY (`role_id`) REFERENCES `dc_pm_roles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `dc_pm_role_permissions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `role_id` BIGINT UNSIGNED NOT NULL,
    `permission_id` BIGINT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_pm_role_permissions_assignment` (`role_id`, `permission_id`),
    KEY `idx_pm_role_permissions_permission` (`permission_id`),
    CONSTRAINT `fk_pm_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `dc_pm_roles` (`id`),
    CONSTRAINT `fk_pm_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `dc_pm_permissions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verification:
--   SHOW COLUMNS FROM dc_users LIKE 'password_hash';
--   SHOW TABLES LIKE 'dc_pm_%';
-- Rollback note:
--   No automated rollback is supplied because project policy forbids DROP and
--   destructive schema changes. The nullable column and empty tables are safe
--   to leave unused if application deployment is rolled back.
