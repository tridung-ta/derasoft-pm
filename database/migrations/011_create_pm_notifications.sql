-- Additive PM reminders. Requires migrations 001-004; verify backup/schema first.
-- IF NOT EXISTS never certifies an existing table: run CLI schema preflight.
-- Rollback: disable PM_REMINDERS_ENABLED and cron; restore code, retain this table.
CREATE TABLE IF NOT EXISTS dc_pm_notifications (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 store_id BIGINT UNSIGNED NOT NULL,
 recipient_id INT UNSIGNED NOT NULL,
 kind VARCHAR(32) NOT NULL,
 entity_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
 event_date DATE NOT NULL,
 read_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY(id),
 UNIQUE KEY uq_pm_notification_event(store_id,recipient_id,kind,entity_id,event_date),
 KEY idx_pm_notification_recipient(store_id,recipient_id,read_at,id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
