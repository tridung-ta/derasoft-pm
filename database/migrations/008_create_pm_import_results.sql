-- Local only after backup. Additive journal; dc_users ENGINE remains unchanged.
-- Safe rollback: hide Apply route/buttons; retain all journal/staging/user data.
CREATE TABLE IF NOT EXISTS dc_pm_import_results (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 store_id BIGINT UNSIGNED NOT NULL,
 import_id BIGINT UNSIGNED NOT NULL,
 source_row INT UNSIGNED NOT NULL,
 status VARCHAR(20) NOT NULL DEFAULT 'pending',
 reason_code VARCHAR(50) NOT NULL DEFAULT 'pending',
 user_id BIGINT UNSIGNED NULL,
 provenance_token CHAR(64) NOT NULL,
 payload_sha256 CHAR(64) NOT NULL,
 attempt_count INT UNSIGNED NOT NULL DEFAULT 0,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 PRIMARY KEY(id),
 UNIQUE KEY uq_pm_import_result_row(store_id,import_id,source_row)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
