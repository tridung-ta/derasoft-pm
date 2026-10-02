-- Phase 4: additive tenant-scoped project, membership and task tables.
-- Prerequisite: Phase 3 schema and verified local/staging backup.
-- Rollback: restore application code, leave these unused additive tables intact.
CREATE TABLE IF NOT EXISTS dc_pm_projects (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 store_id BIGINT UNSIGNED NOT NULL,
 code VARCHAR(50) NOT NULL,
 name VARCHAR(150) NOT NULL,
 description TEXT NULL,
 manager_id INT UNSIGNED NOT NULL,
 start_date DATE NULL,
 end_date DATE NULL,
 budget DECIMAL(15,2) UNSIGNED NOT NULL DEFAULT 0,
 status VARCHAR(20) NOT NULL DEFAULT 'planned',
 deleted_at DATETIME NULL,
 created_by INT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 PRIMARY KEY(id), UNIQUE KEY uq_pm_projects_store_code(store_id,code),
 KEY idx_pm_projects_store_manager(store_id,manager_id,deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS dc_pm_project_members (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 store_id BIGINT UNSIGNED NOT NULL,
 project_id BIGINT UNSIGNED NOT NULL,
 user_id INT UNSIGNED NOT NULL,
 status TINYINT NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 PRIMARY KEY(id), UNIQUE KEY uq_pm_members_store_project_user(store_id,project_id,user_id),
 KEY idx_pm_members_store_user(store_id,user_id,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS dc_pm_tasks (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 store_id BIGINT UNSIGNED NOT NULL,
 project_id BIGINT UNSIGNED NOT NULL,
 name VARCHAR(150) NOT NULL,
 description TEXT NULL,
 assignee_id INT UNSIGNED NULL,
 status VARCHAR(20) NOT NULL DEFAULT 'todo',
 priority VARCHAR(10) NOT NULL DEFAULT 'normal',
 estimated_hours DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0,
 due_date DATE NULL,
 deleted_at DATETIME NULL,
 created_by INT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 PRIMARY KEY(id), KEY idx_pm_tasks_store_project_status(store_id,project_id,deleted_at,status),
 KEY idx_pm_tasks_store_assignee(store_id,assignee_id,deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Verify SHOW CREATE TABLE for all three tables, then run service smoke checks.
