-- READ ONLY. Select the correct existing host database first.
-- No INSERT/UPDATE/DELETE/ALTER/CREATE; never creates a test database.
SELECT DATABASE() AS current_database, VERSION() AS mysql_version, @@sql_mode AS sql_mode;
SELECT store_id, COUNT(*) AS user_count FROM dc_users GROUP BY store_id ORDER BY store_id;
SELECT table_name, engine FROM information_schema.tables
 WHERE table_schema=DATABASE() AND table_name IN
 ('dc_users','dc_pm_roles','dc_pm_permissions','dc_pm_user_roles','dc_pm_role_permissions',
  'dc_pm_projects','dc_pm_tasks','dc_pm_project_members','dc_pm_timesheets','dc_pm_timesheet_days',
  'dc_pm_system_settings','dc_pm_hourly_rates','dc_pm_audit_logs','dc_pm_allocations',
  'dc_pm_capacity_overrides','dc_pm_allocation_locks','dc_pm_import_logs','dc_pm_import_staging','dc_pm_import_results')
 ORDER BY table_name;
SELECT table_name,column_name,column_type,is_nullable,column_default FROM information_schema.columns
 WHERE table_schema=DATABASE() AND
 ((table_name='dc_users' AND column_name IN ('store_id','password_hash','username','fullname','email','tel'))
 OR (table_name='dc_pm_projects' AND column_name='client_name')
 OR (table_name='dc_pm_project_members' AND column_name='project_role')
 OR (table_name='dc_pm_tasks' AND column_name IN ('start_date','completed_at'))
 OR (table_name='dc_pm_timesheet_days' AND column_name='max_total_hours'))
 ORDER BY table_name,ordinal_position;
SELECT index_name,non_unique,seq_in_index,column_name FROM information_schema.statistics
 WHERE table_schema=DATABASE() AND table_name='dc_users' ORDER BY index_name,seq_in_index;
