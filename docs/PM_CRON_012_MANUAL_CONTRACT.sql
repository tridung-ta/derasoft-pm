-- REVIEW / MANUAL LOCAL TEST ONLY. Agent has NOT executed this file.
-- AFTER backup + approved012. Use ONE interactive mysql connection.
-- Paste each numbered block separately; intentional duplicate errors are expected.
-- Do not import this whole file through short-lived phpMyAdmin requests.
-- Do not use this file on host. No INSERT/UPDATE/DELETE of dc_users or PM task data.

-- 1. Verify correct target,012 and all relevant tables' engines before transaction.
SELECT DATABASE() AS must_be_derasoft_pm_local,@@hostname AS local_server;
SHOW COLUMNS FROM dc_pm_notifications LIKE 'due_date';
SHOW INDEX FROM dc_pm_notifications;
SET @s=900000012; SET @u=900000021; SET @t=900000031;
SET @d=DATE('2026-10-14'); SET @a=DATE_SUB(@d,INTERVAL 1 DAY); SET @b=DATE_SUB(@d,INTERVAL 2 DAY);
SELECT COUNT(*) AS must_be_zero_before_fixture FROM dc_pm_notifications WHERE store_id=@s;
-- STOP if target/schema/fixture-store differs. Never remove pre-existing rows.

-- 2. InnoDB transaction; fixtures never committed. No native user row needed (no FK).
START TRANSACTION;
INSERT INTO dc_pm_notifications(store_id,recipient_id,kind,entity_id,event_date,due_date,created_at)
 VALUES(@s,@u,'task_overdue',@t,@d,@a,CONCAT(@d,' 18:00:00'));
SELECT COUNT(*) AS expected_1 FROM dc_pm_notifications WHERE store_id=@s;

-- 3. Old unique key only: same event_date, different snapshot => expected1062
-- mentioning uq_pm_notification_event; table stays one row.
INSERT INTO dc_pm_notifications(store_id,recipient_id,kind,entity_id,event_date,due_date,created_at)
 VALUES(@s,@u,'task_overdue',@t,@d,@b,CONCAT(@d,' 18:01:00'));
SELECT COUNT(*) AS expected_still_1 FROM dc_pm_notifications WHERE store_id=@s;

-- 3b. Identical non-NULL tuple, different event day: only new unique conflicts.
-- This repeats case4 separately if needed; run case4 once and count expected1.
-- 4. New unique key only: next day, original snapshot => expected1062
-- mentioning uq_pm_notification_deadline; table stays one row.
INSERT INTO dc_pm_notifications(store_id,recipient_id,kind,entity_id,event_date,due_date,created_at)
 VALUES(@s,@u,'task_overdue',@t,DATE_ADD(@d,INTERVAL 1 DAY),@a,CONCAT(DATE_ADD(@d,INTERVAL 1 DAY),' 18:00:00'));
SELECT COUNT(*) AS expected_still_1 FROM dc_pm_notifications WHERE store_id=@s;

-- 5. Changed snapshot next day => succeeds, second row.
INSERT INTO dc_pm_notifications(store_id,recipient_id,kind,entity_id,event_date,due_date,created_at)
 VALUES(@s,@u,'task_overdue',@t,DATE_ADD(@d,INTERVAL 1 DAY),@b,CONCAT(DATE_ADD(@d,INTERVAL 1 DAY),' 18:01:00'));
SELECT COUNT(*) AS expected_2 FROM dc_pm_notifications WHERE store_id=@s;

-- 6. Revert to already-reminded snapshot, a third day => expected1062 new key.
INSERT INTO dc_pm_notifications(store_id,recipient_id,kind,entity_id,event_date,due_date,created_at)
 VALUES(@s,@u,'task_overdue',@t,DATE_ADD(@d,INTERVAL 2 DAY),@a,CONCAT(DATE_ADD(@d,INTERVAL 2 DAY),' 18:00:00'));
SELECT COUNT(*) AS expected_still_2 FROM dc_pm_notifications WHERE store_id=@s;

-- 7. NULL snapshot for timesheet remains deduplicated by old daily key.
INSERT INTO dc_pm_notifications(store_id,recipient_id,kind,entity_id,event_date,due_date,created_at)
 VALUES(@s,@u,'timesheet_missing',0,@d,NULL,CONCAT(@d,' 18:00:00')),
 (@s,@u,'timesheet_missing',0,DATE_ADD(@d,INTERVAL 1 DAY),NULL,CONCAT(DATE_ADD(@d,INTERVAL 1 DAY),' 18:00:00'));
SELECT COUNT(*) AS expected_4 FROM dc_pm_notifications WHERE store_id=@s;

-- 7b. NULL snapshot, SAME event_date as first timesheet row: expected1062
-- uq_pm_notification_event, despite new key allowing NULL. Total remains4.
INSERT INTO dc_pm_notifications(store_id,recipient_id,kind,entity_id,event_date,due_date,created_at)
 VALUES(@s,@u,'timesheet_missing',0,@d,NULL,CONCAT(@d,' 18:02:00'));
SELECT COUNT(*) AS expected_still_4 FROM dc_pm_notifications WHERE store_id=@s;

-- 8. Reference cutoff calculation ONLY, not proof of the application candidate SQL.
-- No new permanent table or task/user fixture writes.
SELECT label,CASE WHEN task_due BETWEEN DATE_SUB(@d,INTERVAL 7 DAY) AND DATE_ADD(@d,INTERVAL 1 DAY)
 THEN 1 ELSE 0 END AS creation_window_expected
 FROM (
 SELECT 'D-400' label,DATE_SUB(@d,INTERVAL 400 DAY) task_due UNION ALL
 SELECT 'D-8',DATE_SUB(@d,INTERVAL 8 DAY) UNION ALL
 SELECT 'D-7',DATE_SUB(@d,INTERVAL 7 DAY) UNION ALL
 SELECT 'today',@d UNION ALL SELECT 'tomorrow',DATE_ADD(@d,INTERVAL 1 DAY)
 ) cases;
-- Expected D-400=0,D-8=0,D-7=1,today=1,tomorrow=1.

-- 9. Always rollback, including after an unexpected result. Closing this connection
-- also rolls back an unfinished InnoDB transaction; never COMMIT these fixtures.
ROLLBACK;
SELECT COUNT(*) AS expected_0_after_cleanup FROM dc_pm_notifications WHERE store_id=@s;
-- Now rerun complete before/after checksum on an independent, unmodified connection.
-- AUTO_INCREMENT may advance; don't reset it. Compare row content, not allocation counters.
