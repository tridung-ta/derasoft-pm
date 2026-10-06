-- Phase 11: additive project/task metadata. Backup LOCAL/staging first.
-- Execute entire file in one connection. Never backfill unknown completion dates.
-- Rollback: restore old code, retain additive columns/data; no DROP.
SET @pm_exists=(SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='dc_pm_projects' AND column_name='client_name');
SET @pm_sql=IF(@pm_exists=0,'ALTER TABLE dc_pm_projects ADD COLUMN client_name VARCHAR(150) NULL', 'SELECT 1 AS already_present');
PREPARE pm_metadata FROM @pm_sql;
EXECUTE pm_metadata;
DEALLOCATE PREPARE pm_metadata;
SET @pm_exists=(SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='dc_pm_project_members' AND column_name='project_role');
SET @pm_sql=IF(@pm_exists=0,'ALTER TABLE dc_pm_project_members ADD COLUMN project_role VARCHAR(50) NULL', 'SELECT 1 AS already_present');
PREPARE pm_metadata FROM @pm_sql;
EXECUTE pm_metadata;
DEALLOCATE PREPARE pm_metadata;
SET @pm_exists=(SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='dc_pm_tasks' AND column_name='start_date');
SET @pm_sql=IF(@pm_exists=0,'ALTER TABLE dc_pm_tasks ADD COLUMN start_date DATE NULL', 'SELECT 1 AS already_present');
PREPARE pm_metadata FROM @pm_sql;
EXECUTE pm_metadata;
DEALLOCATE PREPARE pm_metadata;
SET @pm_exists=(SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='dc_pm_tasks' AND column_name='completed_at');
SET @pm_sql=IF(@pm_exists=0,'ALTER TABLE dc_pm_tasks ADD COLUMN completed_at DATETIME NULL', 'SELECT 1 AS already_present');
PREPARE pm_metadata FROM @pm_sql;
EXECUTE pm_metadata;
DEALLOCATE PREPARE pm_metadata;
