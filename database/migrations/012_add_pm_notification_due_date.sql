-- Phase12 throttle: approved file; operator applies manually. NOT executed by agent.
-- Requires verified migration011, backup and maintenance/operator approval.
-- Adds snapshot + additive unique key; retains both existing indexes and all rows.
-- No backfill: historical notifications keep due_date=NULL.
-- Run whole file in one connection. STOP if any status below starts with STOP.
-- Rollback application/disable reminders; retain column/index/data, never DROP.

SET @pm012_table_ok=(SELECT COUNT(*) FROM information_schema.TABLES
 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='dc_pm_notifications' AND ENGINE='InnoDB');
SET @pm012_column_exists=(SELECT COUNT(*) FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='dc_pm_notifications' AND COLUMN_NAME='due_date');
SET @pm012_sql=IF(@pm012_table_ok<>1,
 'SELECT ''STOP: verify migration011 table first'' AS migration012_status',
 IF(@pm012_column_exists=0,
 'ALTER TABLE dc_pm_notifications ADD COLUMN due_date DATE NULL DEFAULT NULL AFTER event_date',
 'SELECT ''Cột due_date đã tồn tại; kiểm tra định nghĩa'' AS migration012_status'));
PREPARE pm012_add_column FROM @pm012_sql;
EXECUTE pm012_add_column;
DEALLOCATE PREPARE pm012_add_column;

SET @pm012_column_ok=(SELECT COUNT(*) FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='dc_pm_notifications' AND COLUMN_NAME='due_date'
 AND DATA_TYPE='date' AND IS_NULLABLE='YES' AND COLUMN_DEFAULT IS NULL);
SET @pm012_index_exists=(SELECT COUNT(*) FROM information_schema.STATISTICS
 WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='dc_pm_notifications' AND INDEX_NAME='uq_pm_notification_deadline');
SET @pm012_sql=IF(@pm012_table_ok<>1 OR @pm012_column_ok<>1,
 'SELECT ''STOP: due_date definition mismatch; no index added'' AS migration012_status',
 IF(@pm012_index_exists=0,
 'ALTER TABLE dc_pm_notifications ADD UNIQUE KEY uq_pm_notification_deadline (store_id,recipient_id,kind,entity_id,due_date)',
 'SELECT ''Khóa deadline đã tồn tại; kiểm tra cấu trúc'' AS migration012_status'));
PREPARE pm012_add_index FROM @pm012_sql;
EXECUTE pm012_add_index;
DEALLOCATE PREPARE pm012_add_index;

SELECT @pm012_column_ok AS due_date_definition_ok;
SELECT CASE WHEN @pm012_table_ok=1 AND @pm012_column_ok=1
 AND EXISTS(SELECT 1 FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='dc_pm_notifications' AND INDEX_NAME='uq_pm_notification_deadline'
  GROUP BY INDEX_NAME HAVING COUNT(*)=5 AND MAX(NON_UNIQUE)=0
  AND SUM(SUB_PART IS NOT NULL)=0
  AND GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX)='store_id,recipient_id,kind,entity_id,due_date')
 AND EXISTS(SELECT 1 FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='dc_pm_notifications' AND INDEX_NAME='uq_pm_notification_event'
  GROUP BY INDEX_NAME HAVING COUNT(*)=5 AND MAX(NON_UNIQUE)=0
  AND SUM(SUB_PART IS NOT NULL)=0
  AND GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX)='store_id,recipient_id,kind,entity_id,event_date')
 THEN '012 structural checks OK; still requires manual verification'
 ELSE 'STOP: column or old/new unique definition mismatch' END AS migration012_status;
SELECT INDEX_NAME,NON_UNIQUE,SEQ_IN_INDEX,COLUMN_NAME,SUB_PART
 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE()
 AND TABLE_NAME='dc_pm_notifications'
 AND INDEX_NAME IN ('uq_pm_notification_event','uq_pm_notification_deadline')
 ORDER BY INDEX_NAME,SEQ_IN_INDEX;
-- Expected: column_ok=1; both keys NON_UNIQUE=0, five columns, no prefixes.
-- Old: store_id,recipient_id,kind,entity_id,event_date.
-- New: store_id,recipient_id,kind,entity_id,due_date.
-- Existing mismatch is NOT repaired automatically: stop and report for review.
