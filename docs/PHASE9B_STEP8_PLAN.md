# Phase 9b Step 8 — Personnel import steps

2026-10-06. Continue the approved UI scope without a new approval gate.

Files to change: templates/admin/pm-imports.tpl.html and css/pmui.css.
Add an ordered, labelled three-step navigation linking to the existing template,
upload/preview and history sections. These are navigation aids, not invented completion
states. Emphasize upload with neutral tokens; preserve native file selection and both
Preview/stage actions. Use native details for history, collapsed by default; keep current
preview/Apply results visible outside the collapse, including password provisioning.
Retain all original form markup, names, actions, CSRF and backend status conditions.
No JavaScript dependency, route/controller/service/DAO/permission/database change.

Verification files: tests/smoke_pm_imports.php, tests/pm_phase9b_imports_browser.js.
Cover initial/empty, preview validation errors, staged/in-progress/partial/completed
history and credential visibility, keyboard/no-JS, responsive and text scaling.
Run full local regression and the Phase 9 browser matrix, review/security check,
update docs/PROGRESS.md and create one local commit. No production or deployment.
