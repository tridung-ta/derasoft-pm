-- MANUAL LOCAL SCRATCH-SCHEMA TEST ONLY. NOT EXECUTED BY AGENT.
-- Requires separate operator approval/CREATE privilege for an EMPTY schema.
-- Never run ADD NOT NULL against the application table or host.
-- Use a new scratch schema name if this name already has tables; never overwrite.
CREATE DATABASE IF NOT EXISTS derasoft_pm_012_bad_column_review_20261014
 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE derasoft_pm_012_bad_column_review_20261014;
SELECT DATABASE() AS must_be_scratch_schema;
SHOW TABLES;
-- STOP if tables already exist. Paste following blocks separately only if EMPTY.
SOURCE D:/derasoft-pm/database/migrations/011_create_pm_notifications.sql;
ALTER TABLE dc_pm_notifications ADD COLUMN due_date DATE NOT NULL;
SHOW CREATE TABLE dc_pm_notifications;
SOURCE D:/derasoft-pm/database/migrations/012_add_pm_notification_due_date.sql;
-- Expected STOP; due_date remains NOT NULL; old unique retained; new key absent.
SELECT COUNT(*) AS expected_0_deadline_index_rows FROM information_schema.STATISTICS
 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='dc_pm_notifications'
 AND INDEX_NAME='uq_pm_notification_deadline';
SHOW COLUMNS FROM dc_pm_notifications LIKE 'due_date';
-- Keep scratch table/schema unused; NO DROP or destructive cleanup here.
USE derasoft_pm_local;
