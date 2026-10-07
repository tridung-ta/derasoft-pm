-- Phase 7 additive schema. Apply only to backed-up local/staging database.
-- Rollback: disable allocations route/menu; retain tables and historical data.
CREATE TABLE IF NOT EXISTS dc_pm_allocations (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 store_id BIGINT UNSIGNED NOT NULL, project_id BIGINT UNSIGNED NOT NULL,
 task_id BIGINT UNSIGNED NOT NULL, user_id BIGINT UNSIGNED NOT NULL,
 work_date DATE NOT NULL, hours DECIMAL(5,2) NOT NULL,
 start_time TIME NULL, end_time TIME NULL, deleted_at DATETIME NULL,
 created_by BIGINT UNSIGNED NOT NULL, updated_by BIGINT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 PRIMARY KEY(id), KEY user_day(store_id,user_id,work_date,deleted_at),
 KEY project_week(store_id,project_id,work_date,deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS dc_pm_capacity_overrides (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 store_id BIGINT UNSIGNED NOT NULL, user_id BIGINT UNSIGNED NOT NULL,
 daily_limit_hours DECIMAL(5,2) NOT NULL, weekly_limit_hours DECIMAL(5,2) NOT NULL,
 effective_from DATE NOT NULL, effective_to DATE NULL, deleted_at DATETIME NULL,
 created_by BIGINT UNSIGNED NOT NULL, updated_by BIGINT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 PRIMARY KEY(id), KEY user_effective(store_id,user_id,effective_from,effective_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS dc_pm_allocation_locks (
 store_id BIGINT UNSIGNED NOT NULL, user_id BIGINT UNSIGNED NOT NULL,
 work_date DATE NOT NULL,
 PRIMARY KEY(store_id,user_id,work_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- Sentinel 1000-01-01 serializes all writes for a user, including weekly totals
-- and capacity changes; real user/day keys are also locked in sorted order.
