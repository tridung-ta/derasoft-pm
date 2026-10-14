-- Exactly two READ-ONLY result sets. Zero due_date rows or !=2 index rows = STOP.
SELECT COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,COLUMN_DEFAULT,
 CASE WHEN DATA_TYPE='date' AND IS_NULLABLE='YES' AND COLUMN_DEFAULT IS NULL
 THEN 'OK' ELSE 'STOP: due_date mismatch' END AS definition_status
 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE()
 AND TABLE_NAME='dc_pm_notifications' AND COLUMN_NAME='due_date';

SELECT INDEX_NAME,NON_UNIQUE,COUNT(*) AS column_count,
 GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS ordered_columns,
 CASE WHEN COUNT(*)=5 AND MAX(NON_UNIQUE)=0 AND SUM(SUB_PART IS NOT NULL)=0
 AND GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX)=
 CASE INDEX_NAME WHEN 'uq_pm_notification_event' THEN 'store_id,recipient_id,kind,entity_id,event_date'
 ELSE 'store_id,recipient_id,kind,entity_id,due_date' END
 THEN 'OK' ELSE 'STOP: unique mismatch' END AS definition_status
 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE()
 AND TABLE_NAME='dc_pm_notifications'
 AND INDEX_NAME IN ('uq_pm_notification_event','uq_pm_notification_deadline')
 GROUP BY INDEX_NAME,NON_UNIQUE ORDER BY INDEX_NAME;
