-- Phase 5 increment 1. Additive only, local/staging backup required.
-- Application rollback leaves new unused tables intact; no DROP/legacy rewrite.
-- Prerequisites: migrations 001-003, MySQL JSON support, verified local/staging backup.
-- Verify: PHP 8.3 tests/pm_phase5_migration.php; rerunning --apply is idempotent.
-- Deploy schema before enabling pmtimesheets. Never execute against production in development.
CREATE TABLE IF NOT EXISTS dc_pm_system_settings (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 store_id BIGINT UNSIGNED NOT NULL,
 setting_key VARCHAR(50) NOT NULL,
 setting_value VARCHAR(100) NOT NULL,
 PRIMARY KEY(id), UNIQUE KEY uq_pm_settings_store_key(store_id,setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS dc_pm_timesheet_days (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 store_id BIGINT UNSIGNED NOT NULL,
 user_id INT UNSIGNED NOT NULL,
 work_date DATE NOT NULL,
 standard_hours DECIMAL(4,2) NOT NULL,
 ot_multiplier DECIMAL(4,2) NOT NULL,
 PRIMARY KEY(id), UNIQUE KEY uq_pm_days_store_user_date(store_id,user_id,work_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS dc_pm_timesheets (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 store_id BIGINT UNSIGNED NOT NULL,
 user_id INT UNSIGNED NOT NULL,
 project_id BIGINT UNSIGNED NOT NULL,
 task_id BIGINT UNSIGNED NOT NULL,
 work_date DATE NOT NULL,
 shift_label VARCHAR(30) NOT NULL,
 hours DECIMAL(4,2) NOT NULL,
 description TEXT NULL,
 status VARCHAR(20) NOT NULL DEFAULT 'draft',
 rate_snapshot DECIMAL(15,2) NOT NULL,
 rate_source VARCHAR(20) NOT NULL,
 currency CHAR(3) NOT NULL,
 rate_warning VARCHAR(255) NULL,
 regular_hours DECIMAL(4,2) NOT NULL DEFAULT 0,
 ot_hours DECIMAL(4,2) NOT NULL DEFAULT 0,
 ot_multiplier_snapshot DECIMAL(4,2) NOT NULL,
 cost DECIMAL(18,2) NOT NULL DEFAULT 0,
 deleted_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 PRIMARY KEY(id), KEY idx_pm_timesheets_store_user_day(store_id,user_id,work_date,deleted_at),
 KEY idx_pm_timesheets_store_project(store_id,project_id,work_date,deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS dc_pm_audit_logs (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 store_id BIGINT UNSIGNED NOT NULL,
 actor_id INT UNSIGNED NOT NULL,
 entity_type VARCHAR(40) NOT NULL,
 entity_id BIGINT UNSIGNED NOT NULL,
 action VARCHAR(40) NOT NULL,
 old_values JSON NULL,
 new_values JSON NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY(id), KEY idx_pm_audit_store_entity(store_id,entity_type,entity_id),
 KEY idx_pm_audit_store_date(store_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
