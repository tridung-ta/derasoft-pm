# Phase 9b Step 5 — Cost presentation

Autonomous PLAN/BUILD authorized. Files: templates/admin/pm-costs.tpl.html,
js/pmcosts.js, css/pmui.css, tests/smoke_pm_costs.php,
tests/pm_phase9b_costs_browser.js (new), docs/PHASE9B_STEP5_PLAN.md,
docs/PROGRESS.md. No route/controller/DAO/schema/permission/business changes.

Combine existing KPI cards into one strip. Projects (including existing task detail)
and groups occupy one block with progressive-enhancement tabs; no-JS shows both.
Tabs support Arrow/Home/End, selected state, focus, panel labels and responsive layout.
Chart creation requires meaningful comparable data: empty or incompatible data gets
an explanation instead of axes. Preserve legitimate zero values. Destroy obsolete chart
instances on empty polling response, recreate when data returns, preserve tab selection.
Decimal source strings and all filters/pagination/endpoints remain unchanged.
Use existing Chart.js only, Inter and neutral chart series, common css/pmui.css.
VERIFY: Smarty mixed/empty/error/escaping; browser chart transitions, tab keyboard,
polling/error/authorization, no-JS/missing Chart, responsive/text scaling/CSP;
full local regression, diff/security review and one focused local commit.

Status: BUILD/automated VERIFY complete; commands and limits in PROGRESS.md.
