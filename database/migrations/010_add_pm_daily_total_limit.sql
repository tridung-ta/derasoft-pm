-- P3: additive daily total-hours snapshot. Depends on 004.
-- Backup approved LOCAL/staging target first; execute entire file in one connection.
-- Existing ledgers retain the former 24-hour maximum. No hours/cost data rewritten.
-- Deploy schema before new application. Rollback application only; retain column/data.
SET @pm_exists=(SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='dc_pm_timesheet_days' AND column_name='max_total_hours');
SET @pm_sql=IF(@pm_exists=0,'ALTER TABLE dc_pm_timesheet_days ADD COLUMN max_total_hours DECIMAL(4,2) NOT NULL DEFAULT 24.00','SELECT 1 AS already_present');
PREPARE pm_daily_limit FROM @pm_sql;
EXECUTE pm_daily_limit;
DEALLOCATE PREPARE pm_daily_limit;
