# Phase 9b Step 6 — Project cards

Autonomous UI PLAN/BUILD authorized. Exact files initially in scope:
templates/admin/pm-projects.tpl.html, css/pmui.css, tests/smoke_pm_projects.php,
tests/pm_phase9b_projects_browser.js (new), docs/PHASE9B_STEP6_PLAN.md,
docs/PROGRESS.md. Reuse existing shared CSS and native details; no extra library.

Project list becomes a 1/2/3-column grid, with name/code, textual status badge,
manager, dates and existing budget. Cards have clear project links; pagination stays
outside the grid. Status treatments use the current neutral palette and visible text.
Create-project form closes by default and opens on a server error. Original forms,
CSRF, fields, permission checks, routes and detail/task operations retained.

Important scope finding: listProjects returns p.* and manager_name, but no task counts
or progress percentage. Do not fabricate progress or map status to an invented percent.
User explicitly approved backend task aggregation: completed tasks / total tasks;
projects without tasks show no data. Exclude soft-deleted tasks consistently with tasks().
Add classes/services/pmprojectservice.class.php and tests/pm_projects_smoke.php to scope.
One prepared aggregate for the visible page (up to 20 projects), tenant-scoped and
conditional on existing pm.tasks.view permission. No task counts leak to users lacking
that permission; no schema, controller, route or permission grants change. Percentage
rounded to one decimal for display; task counts remain authoritative.

VERIFY: populated/empty/read-only/error and escaped long names; 360/390/768/1440,
keyboard details/link navigation, numeric styles/contrast, no-JS, existing project/task
regression and full Phase 2–9 suite, security/diff review before focused local commit.

Status: BUILD/automated VERIFY complete. Approved backend exception and executed results recorded in PROGRESS.md.
