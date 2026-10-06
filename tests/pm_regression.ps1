$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
Push-Location $projectRoot
try {
    $php = Join-Path $projectRoot '.tools/php83/php.exe'
    $tests = @(
        'pm_auth_rbac_smoke', 'pm_auth_identity_smoke', 'pm_users_crud_smoke', 'pm_rates_smoke', 'pm_departments_smoke', 'pm_projects_smoke',
        'pm_phase5_migration', 'pm_phase5_audit_permissions', 'pm_timesheets_smoke',
        'pm_timesheet_window_smoke', 'pm_audit_smoke', 'smoke_pm_timesheets',
        'smoke_pm_audit', 'smoke_pm_admin', 'smoke_pm_users', 'smoke_pm_projects',
        'smoke_pm_dependencies', 'pm_phase6_permissions', 'pm_costs_smoke',
        'pm_costs_http_smoke', 'smoke_pm_costs', 'pm_phase7_migration',
        'pm_allocations_smoke', 'pm_allocations_concurrency', 'pm_allocations_http_smoke',
        'smoke_pm_allocations', 'pm_phase8_permissions', 'pm_reports_smoke',
        'pm_reports_http_smoke', 'smoke_pm_reports', 'pm_import_migration',
        'pm_import_smoke', 'pm_import_http_smoke', 'smoke_pm_imports',
        'pm_email_unique_migration', 'pm_import_results_migration',
        'pm_import_apply_smoke', 'pm_import_native_duplicate',
        'pm_roles_batch_smoke', 'pm_ui_http_smoke', 'smoke_pm_ui', 'pm_explain_smoke',
        'pm_session_guard_smoke', 'pm_db_isolation_smoke', 'pm_phase11_metadata', 'pm_phase11_bootstrap'
    )
    foreach ($test in $tests) {
        & $php "tests/$test.php"
        if ($LASTEXITCODE -ne 0) { throw "Failed: $test" }
    }
    Write-Output "PASS: $($tests.Count) regression scripts. No migrations applied by this runner."
} finally {
    Pop-Location
}
