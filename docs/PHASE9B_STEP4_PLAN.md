# Phase 9b Step 4 ? Allocation week view

Scope: templates/admin/pm-allocations.tpl.html, css/pmui.css,
js/pmallocations.js, js/pmallocationweek.js (new), tests/smoke_pm_allocations.php,
tests/pm_phase9b_allocation_browser.js (new), docs/PHASE9B_STEP4_PLAN.md,
docs/PROGRESS.md. Autonomous BUILD authorized by user.

Progressive enhancement: seven day columns grouped by employee from visible rows.
Use server day_total, daily_limit, week_total, weekly_limit; do not sum filtered rows
or manufacture zero for dates with no visible detail. No extra API or permission change.
Warning includes text and amber accent, not color alone. Existing table stays available
and preserves every action/form. JS off keeps original table usable. MutationObserver
refreshes projection after existing polling; polling endpoint/timing/error handling stay.
Three management sections inside one panel with native details; editor open by default.
No backend, route, DAO, schema or business logic changes. One CSS remains pmui.css.
VERIFY: Smarty escaping/read-only/error, browser date rollover/multiple users/warnings,
polling DOM update/empty/no-JS, keyboard/responsive/text scaling, full regression.

Status: BUILD/automated VERIFY complete; detailed commands/results in PROGRESS.
