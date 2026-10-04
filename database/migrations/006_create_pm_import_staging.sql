-- Local/staging backup required. Additive only; does not write dc_users.
-- Rollback: disable pmimports route/menu; retain staging/history, no DROP.
CREATE TABLE IF NOT EXISTS dc_pm_import_logs (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, store_id BIGINT UNSIGNED NOT NULL,
 actor_id BIGINT UNSIGNED NOT NULL, status VARCHAR(20) NOT NULL DEFAULT 'staged',
 row_count INT UNSIGNED NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY(id), KEY tenant_actor(store_id,actor_id,id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS dc_pm_import_staging (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, store_id BIGINT UNSIGNED NOT NULL,
 import_id BIGINT UNSIGNED NOT NULL, source_row INT UNSIGNED NOT NULL, payload JSON NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY(id), UNIQUE KEY import_row(store_id,import_id,source_row)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
