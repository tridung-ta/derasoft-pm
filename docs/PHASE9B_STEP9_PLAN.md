# Phase 9b Step 9 — Overview, audit and reports

2026-10-06, approved continuing UI scope. Use the current white/neutral/Inter tokens;
the obsolete mono/dark palette does not apply.

Files: templates/admin/pm.tpl.html — combine the three existing metrics into one
responsive strip, without inventing new measurements; combine the two shortcut cards
into one block using existing permission-filtered navigation entries.
templates/admin/pm-audit.tpl.html — Inter tabular timestamp, compact action badges,
native inline before/after details, preserve escaped raw values and filters.
templates/admin/pm-reports.tpl.html — move the existing export form beside the title,
retain its report/permission condition, hidden filters and CSRF; tabular summary hours.
css/pmui.css — all styles scoped to these PM surfaces. No other CSS asset.

Verification: tests/smoke_pm_admin.php, tests/smoke_pm_audit.php,
tests/smoke_pm_reports.php and tests/pm_phase9b_final_browser.js. Cover missing/restricted
metrics/navigation/export, error/empty states, hours/costs, keyboard/no-JS details,
responsive, numeric typography and original form equality. Run full local regression,
Phase 9 browser matrix and the earlier Phase 9b browser suites for shared-CSS regression.
Update docs/PROGRESS.md and commit the completed step locally.

No route/controller/service/DAO/permission/business logic/database changes. No merge,
push or production access. Manual whole-flow UAT and release packaging remain separate.
