-- READ ONLY, LOCAL manual verification. Use before fixture and after ROLLBACK.
-- Baseline is AFTER applying approved012: adding a column may change the checksum
-- of notifications itself; don't compare different column layouts as equal.
SELECT DATABASE() AS must_be_derasoft_pm_local;
CHECKSUM TABLE dc_users,
 dc_pm_allocation_locks,dc_pm_allocations,dc_pm_audit_logs,
 dc_pm_capacity_overrides,dc_pm_departments,dc_pm_hourly_rates,
 dc_pm_import_logs,dc_pm_import_results,dc_pm_import_staging,
 dc_pm_notifications,dc_pm_permissions,dc_pm_project_members,
 dc_pm_projects,dc_pm_role_permissions,dc_pm_roles,
 dc_pm_system_settings,dc_pm_tasks,dc_pm_timesheet_days,
 dc_pm_timesheets,dc_pm_user_roles EXTENDED;
-- Every table checksum before/after must match. NULL/unsupported is NOT PASS;
-- use the application fixture observer SHA256 tool during BUILD if necessary.
