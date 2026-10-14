$ErrorActionPreference='Stop'
$root=Split-Path -Parent $PSScriptRoot
Push-Location $root
try {
 $php=Join-Path $root '.tools/php83/php.exe'
 $tests=@('pm_notification_bootstrap_smoke','pm_cron_throttle_smoke','pm_reminders_smoke','pm_user_input_smoke','pm_shift_label_smoke','pm_vietnamese_messages_smoke','smoke_pm_dependencies','pm_phase11_bootstrap','pm_workspace_dashboard_smoke','pm_allocation_summary_contract_smoke',
  'smoke_pm_admin','smoke_pm_users','smoke_pm_projects','smoke_pm_timesheets','smoke_pm_audit',
  'smoke_pm_costs','smoke_pm_allocations','smoke_pm_reports','smoke_pm_imports','smoke_pm_ui')
 foreach($test in $tests){& $php "tests/$test.php" --preview;if($LASTEXITCODE -ne 0){throw "Failed: $test"}}
 $paths=@('classes/services/pmreminderoptions.class.php','tests/pm_cron_throttle_db_smoke.php','tests/pm_cron_throttle_worker.php','tests/pm_cron_throttle_http_router.php','admin.php','cron/pm-reminders.php','includes/pm_reminders.inc.php','classes/dao/pmnotifications.class.php','classes/dao/pmnotificationinfo.class.php','classes/services/pmreminderservice.class.php','classes/services/pmnotificationservice.class.php','modules/admin/pmnotifications.module.php','tests/pm_reminders_explain.php','classes/dao/pmdashboard.class.php','classes/services/pmdashboardservice.class.php',
  'classes/services/pmuiservice.class.php','classes/services/pmallocationservice.class.php','modules/admin/pm.module.php','pm_ajax.php','modules/ajax/pmtaskstatus.module.php','classes/services/pmshiftlabelservice.class.php','classes/services/pmtimesheetservice.class.php','modules/admin/pmtimesheets.module.php')
 foreach($path in $paths){& $php -l $path;if($LASTEXITCODE -ne 0){throw "Lint failed: $path"}}
 & python tests/pm_deploy_manifest_test.py
 if($LASTEXITCODE -ne 0){throw 'Manifest allowlist failed'}
 Write-Output "PASS: $($tests.Count) no-DB scripts, $($paths.Count) PHP lint checks, manifest allowlist. Full DB regression/SQL/session integration NOT RUN."
}finally{Pop-Location}
