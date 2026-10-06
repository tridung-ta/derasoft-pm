# DeraSoft PM — Progress

## Phase 11 final local handoff — 2026-10-06

UAT checklist now covers the latest runtime 2bb9665, shared email/username password
changes and Bootstrap states. Every manual acceptance item remains pending.
Release manifest/application base and deployment notes updated to the same runtime;
86-file ZIP rebuilt and verified (inventory, CRC, SHA256 for each runtime file).
`python -B tests/pm_deploy_manifest.py --check` PASS;
`python -B tests/pm_deploy_manifest_test.py`: 5 tests PASS. No runtime changes
in this handoff; previously verified 45 regression scripts/10 browser suites stand.
Next gate: user local UAT, then explicit merge/push decision; migration 009 and
production deployment require separate approval. No production access or SQL.

## Bootstrap form controls completed locally — 2026-10-06

Applied Bootstrap `form-control`, `form-select`, and `btn` to active PM templates
and form partials. Hidden/checkbox/radio controls remain unchanged. Kept every
field name, ID, Smarty condition, CSRF token and confirmation. No controller/DAO,
permission, database or Bootstrap JS changes. Mapped Bootstrap button variables
to the existing Inter theme, including destructive/disabled states; neutral input
focus, disabled controls and file chooser. Fixed an observed transparent/default
Bootstrap button contrast regression before completing this change.

VERIFY: `powershell -NoProfile -ExecutionPolicy Bypass -File tests/pm_regression.ps1`
45 scripts PASS; 9 Phase 9b browser fixture suites PASS with current CSS. New
`playwright-cli -s=pmbootstrap run-code --filename=../tests/pm_bootstrap_controls_browser.js`
PASS: real classes, disabled colors after transition, 3px focus, >=44px select,
1440/390/720px and 200% text scaling. Inspected mobile personnel-dialog screenshot.
Template diff normalized by removing added classes matches HEAD exactly; no
business form content changes. Existing class tests now check class membership,
preserving lock/unlock/destructive assertions. Synthetic local fixtures are not UAT.
No merge/push/deploy; migration 009 still local and requires separate production approval.

## Shared email/username credentials fix — 2026-10-06

Both identifiers resolve one tenant-scoped credential row; ambiguous cross-account
email/username matches fail closed rather than selecting another account. Legacy
profile password changes now call the same secure password update as PM, instead
of updating only the obsolete MD5 column. PM login explicitly labels both identifiers.
Existing modern hashes remain authoritative; an old MD5 cannot bypass a changed password.

Verified `.tools/php83/php.exe tests/pm_auth_identity_smoke.php`: 20 checks PASS
(legacy upgrade, both identifiers before/after change, old/wrong passwords, disabled
accounts, tenant isolation, collisions). A connection-local TEMPORARY dc_users table
shadows the real table; no real account passwords changed.
`powershell -NoProfile -ExecutionPolicy Bypass -File tests/pm_regression.ps1`:
45 scripts PASS. Diff/security review: prepared identity lookup, same user ID/store
for updates, no password/secret output or credential fallback after modern hash.
Production behavior has not been reproduced directly; these are confirmed code
defects, not a claim that the production incident's exact cause was established.
Local only; no push/merge/production changes.

## Phase 11 — local requirements implementation verified — 2026-10-06

Completed remaining XLSX directory modes (users/projects), retaining existing
hours/tasks/costs exports, field whitelists, literal strings, CSRF/no-store and
permissions matching read surfaces. Projects directory omits finance entirely.
Added completed-task weekly totals and delay days; no performance ratios.
Bootstrap 5.3.8 CSS self-hosted with official SHA384 and MIT license; loaded once
before PM base/theme, card/btn/table-responsive integration, no new JS runtime.
Preserved shared Inter/neutral tokens and existing native dialogs/details.

Final verification: tests/pm_regression.ps1 44 PASS; 101 actual prepared query
shapes EXPLAIN PASS. 9 existing Phase 9b browser suites PASS after Bootstrap;
pm_sidebar_overview_browser.js and pm_phase11_reports_browser.js PASS.
New HTTP directory/task modes and invalid array filters PASS; XLSX directory
formula-like text round-trip stays string, no password/hash or budget exported.
Security/code review: scoped joins, no duplicate sums from multi-role EXISTS,
CSRF preserved, escaped templates, no secret/cache/dump in commit. See
docs/PHASE11_REQUIREMENTS.md for matrix and honest limits.

Phase 11 BUILD/automated verification local complete; user UAT and production
remain pending. Migration 009 mandatory before new code; not production-approved
by the previous UI-only backup waiver. No push/merge/deploy performed here.
Prepared local review ZIP 0.11.0-phase11-local-rc1: 85 runtime files, SHA256/CRC
verified; 5 manifest classification tests PASS. Source runtime b57e7ba.
See docs/UAT_PHASE11.md. Manifest requires 009 but excludes SQL from ZIP.

## Phase 11 — task/weekly and cost reporting — 2026-10-06

Reports now include weekly user hours/regular/OT, task completion in date range,
overdue unfinished tasks, late completion and unknown legacy completion dates;
group task counts by project/user without performance ratios. Added role filters
for hours/tasks and user/role filters for cost actual/estimate while preserving
single-currency guards and DECIMAL. Exposed existing department/primary-role/task
cost groups and included new statistics in literal-text XLSX.
No new permissions: Admin tenant, PM managed projects, HR/Employee own reports.
Task inclusion: completion date OR due date in filter; overdue uses current status
and min(today, to), not a historical state reconstruction. Current role/department
classification explicitly labeled; filtered costs don't change project budget.
Security review: prepared filters/EXISTS avoids multi-role duplicate sums; escaped
templates; report/export permissions and CSRF/no-store unchanged. No production.
Verification: pm_reports_smoke.php and pm_costs_smoke.php PASS, task/weekly/role/
unknown-date/DECIMAL assertions; 43 regression PASS. Playwright
pm_phase11_reports_browser.js PASS desktop/mobile/200% text scaling/pageerror.
XLSX personnel/project export and Bootstrap integration remain open.

## Phase 11 — project/task metadata complete locally — 2026-10-06

Added customer name to project forms/service, task start date/range validation,
member project-role selector/display (manager/developer/qa/analyst/other, distinct
from RBAC), and completion datetime tracking for accurate future reports.
Versioned migration 009 adds four nullable columns. Before DDL, backed up affected
local PM tables using mysqldump under ignored .local/checkpoints; guarded host and
database. Executed complete file twice on same local connection: idempotent PASS.
No dc_users engine/data change, no backfill, no production SQL.
Tests: tests/pm_regression.ps1 — 43 PASS; project smoke verifies customer/role,
invalid range/role rejection, completed timestamp stability and reopen clearing;
metadata test verifies types/nullability. Smarty rendering PASS.
Review: prepared statements, existing CSRF/ownership preserved, labels/output
escaped; project role does not grant permission. Schema rollback retains columns.
Reporting/export/Bootstrap remain pending; not a complete Phase 11 release.

## Phase 11 — task audit complete, remaining requirements in progress — 2026-10-06

Plan: docs/PHASE11_REQUIREMENTS.md. User approved self-hosted Bootstrap 5 and
statistics only (no performance ratio). Branch starts from the Phase 9b UI fix
65cfbd5; that fix has not been integrated/pushed yet.

Task create/update/soft-delete now writes actor/time and before/after snapshots
atomically with the task mutation. PM audit limited to managed projects in tenant,
checking both snapshots; HR remains timesheet-only. Task filter exposed only to
Admin/PM. No schema migration, production access or push/deployment in this step.
Security review: prepared writes/reads, tenant/project checks before mutation,
escaped Smarty output; no financial fields added to non-admin timesheet snapshots.
Verification: pm_projects_smoke.php, pm_audit_smoke.php, smoke_pm_audit.php PASS;
tests/pm_regression.ps1 42 PASS. New cases prove snapshots, soft-delete audit,
rollback on injected audit failure, PM task details and HR task audit exclusion.
Other requirements remain open; Phase 11 is NOT declared complete.

## Phase 9b — sidebar and overview follow-up — 2026-10-06

Fixed the missing D brand on standalone module sidebars (real template markup,
replacing the text-only CSS pseudo-element); emphasized both logout forms with
the existing primary token and an accessible decorative icon. Extended overview
with descriptive workspace links derived solely from permitted pmNavigation.
No new queries, counts, routes, controller/DAO or permission changes.

Changed runtime: css/pmui.css, templates/admin/pm-navigation.tpl.html,
templates/admin/pm.tpl.html. Logout remains POST with CSRF; output remains escaped.
Review: no scripts/event handlers, secrets, business writes or new dependencies.

Verification: tests/pm_regression.ps1 — 42 PASS; smoke_pm_admin.php --preview,
smoke_pm_ui.php --preview, smoke_pm_users.php --preview — PASS.
Playwright tests/pm_sidebar_overview_browser.js — PASS for logo uniqueness,
computed logout colors/44px target/keyboard focus/CSRF, restricted navigation,
1440/390/720px and 200% text scaling. Text scaling is not browser zoom UAT.
Impeccable detector warnings: linked CSS relative resolution limitation,
existing warning border and the explicitly required Inter; browser verified layout.
Production verification not performed. New patch is local only until separately
uploaded by operator; old screenshot comparison remains a historical illustration.

## Phase 9b — reconstructed comparison completed — 2026-10-06

User authorized creating suitable images. Added 18 reconstructed before captures
from 1dc761f and refreshed 18 after captures from 471950d with matching synthetic
fixtures; desktop/mobile for all nine pages. See [comparison](PHASE9B_UI_COMPARISON.md).
Playwright capture: 36/36 without pageerror or whole-page horizontal overflow.
Original 05/10 images remain unavailable; these labeled illustrations are not UAT
or production evidence. No application/DB change, push or deployment.

## Phase 9b — approved local integration — 2026-10-06

After explicit confirmation “tôi đồng ý hãy tiếp tục”, merged
feature/pm-phase9b-ui-theme into develop with --ff-only: 1dc761f → 624c085.
Fast-forward succeeded; no merge commit/conflict, no rebase/force. Phase 9 df76268
is an ancestor of develop, which now contains all nine Phase 9b build steps,
release preparation and user acceptance. Before-image evidence remains recorded
as missing; approval to merge is not a claim that screenshots were supplied.

Post-checkout manifest check found SHA256 differences because core.autocrlf=true
materialized CRLF for 14 runtime text files. Source semantics unchanged; manifest
and local ZIP regenerated against current on-disk bytes, rather than ignoring the
failed check. Font binary unchanged. Integration record is committed on the phase
branch, then develop fast-forwarded to include this record; no direct develop commit.
No push, deployment, production access or branch deletion.

Post-integration verification: ./tests/pm_regression.ps1 42 scripts PASS;
python -B tests/pm_deploy_manifest_test.py 5 tests PASS; regenerated manifest
--check and --package PASS (78 runtime files, SHA256/CRC). Phase 9 ancestor check
PASS. No application logic changed for this integration record.

## Phase 9b — user acceptance recorded — 2026-10-06

User explicitly stated “tôi đã nghiệm thu xong” after candidate cf21eff/runtime
d4cde62 was handed over. Record overall Phase 9b acceptance as user-reported;
no new defect reported with that statement. Detailed per-case/role/browser-zoom
results and original before screenshots were not supplied; no invented test evidence.
Historical automated coverage remains 42 regression scripts / 10 browser suites.

Read-only Git check: develop is 1dc761f and merge-base(develop, cf21eff) is exactly
1dc761f. Develop is an ancestor, so proposed local merge can use --ff-only.
No merge attempted. Original before evidence disposition and explicit merge approval
remain pending; push and deployment are separate decisions. No runtime change.

## Phase 9b — release preparation / manual UAT gate — 2026-10-06

Prepared candidate 0.10.0-phase9b-local-rc1; runtime base d4cde62 on
feature/pm-phase9b-ui-theme. Updated the Phase 10 release metadata/lists and final
UAT checklist to include all nine Phase 9b UI steps and the two approved read-only
aggregates. Historical operator production evidence retained separately.

Manifest now explicitly includes assets/fonts/inter/InterVariable.woff2 and OFL.txt;
the assets directory is not generally allowlisted. Added an optional local ZIP artifact
under ignored .local/releases, verified against per-file SHA256 and ZIP CRC. All
runtime paths retained; protected config/license directory/uploads/SQL/logs excluded.
This is the recorded Phase 0 baseline delta, not an independently inspected live-host
delta or standalone fresh install. Font license ships with the font.

Executed checks:
- python -B tests/pm_deploy_manifest_test.py: 5 tests PASS covering font/license,
  unreviewed assets, protected paths, deleted-remote keep and existing runtime/docs.
- python -B tests/pm_deploy_manifest.py then --check --package: PASS, 78 runtime
  entries, identical ZIP inventory, matching SHA256 for every extracted entry and CRC.
- Inter CSS relative URL and exact font/license hashes verified; font 352240 bytes.
- Copied 18 existing synthetic after screenshots for nine pages into
  docs/screenshots/after, with provenance README; PNG signatures checked.
  Before screenshots not fabricated. Original nine before files still unavailable.
- Local UAT config guard PASS (derasoft_pm_local, localhost/127.0.0.1); PHP server
  on 127.0.0.1:18770 started and admin.php returned HTTP 200 with login password form.
  No login bypass, business POST or production access during this preparation.
- Application verification from Step 9: 42 local regression scripts and 10 browser
  suites PASS. No application code changed in this packaging/documentation work.

Only manual UAT remains unconfirmed: run docs/UAT_FINAL_LOCAL.md with actual local
accounts, real browser zoom and whole-flow review. PM/HR authenticated browser cases
still need appropriate local actors; do not substitute service fixtures for manual UAT.
User must decide whether to supply original before images or accept missing evidence.
After UAT and this decision, request explicit approval before merge into develop;
push/deploy separately. No merge, push, deployment or production modification performed.

## Phase 9b Step 9 — Overview, audit and reports — 2026-10-06

BUILD/automated VERIFY complete on feature/pm-phase9b-ui-theme. Overview combines
the existing three metrics into one responsive strip (only personnel count is numeric;
no new measurements invented). One shortcut panel uses existing permission-filtered
navigation entries. Audit has Inter/tabular timestamps, compact neutral/destructive
badges and native inline before/after details. Reports retain both tables and filters;
the unchanged XLSX POST form moves beside the title, with report/export permission
conditions retained. Summary hours use Inter/tabular/right alignment. Pager spacing
and touch targets improved. No backend, route, DAO, permission or calculation change.

Exact files: templates/admin/pm.tpl.html, templates/admin/pm-audit.tpl.html,
templates/admin/pm-reports.tpl.html, css/pmui.css, tests/smoke_pm_admin.php,
tests/smoke_pm_audit.php, tests/smoke_pm_reports.php,
tests/pm_phase9b_final_browser.js, docs/PHASE9B_STEP9_PLAN.md, docs/PROGRESS.md.

Executed verification:
- ./tests/pm_regression.ps1: 42 scripts PASS, local only. Includes real report HTTP
  XLSX response, workbook round-trip/DECIMAL and permission/security regression.
- PHP lint three changed smoke tests and their --preview renders PASS. Restricted
  personnel count/navigation, escaped audit actions/JSON, missing/denied export,
  hours/costs/error/empty states covered. All original forms identical to HEAD
  (comparison allows export relocation), including CSRF and hidden filter markup.
- playwright-cli -s=pmnav run-code --filename=../tests/pm_phase9b_final_browser.js:
  PASS for overview/restricted shortcuts, audit keyboard/no-JS details, export header
  and scope/filter/CSRF fields, exact cost string and numeric styles. Header button
  clicked; POST intercepted to a synthetic XLSX download using a local workbook,
  with correct route/mode/from/to/CSRF verified. No business write or production UAT.
  360/390/768/1440 and 200% text scaling PASS. Badge contrast 7.60–18.10:1.
  Desktop/mobile captures of overview, audit, hours/cost reports generated;
  overview/audit/hours desktop and mobile inspected.
- Shared-CSS regression: pm_ui_browser.js (13 Phase 9 fixtures), plus all nine
  pm_phase9b_{buttons,users,theme,allocation,costs,projects,timesheets,imports,final}_browser.js
  suites PASS. Theme suite includes self-hosted Inter rendering checks.
- Diff/security review and git diff --check PASS. Escaping, permission-derived menus,
  native disclosure, forms and filters retained; no new dependency or inline code.
  The reused parent allocation preview later emitted a 401 polling response without
  an authenticated session; isolated Step 9 context had no JS exceptions. This is
  a synthetic-preview limitation, not an authenticated production console claim.

Preview: http://127.0.0.1:18767/.local/phase9-dashboard.html and
http://127.0.0.1:18767/.local/phase9b-reports-hours.html (synthetic; do not submit).

All nine Phase 9b BUILD steps now implemented and automatically verified. Phase
closure remains pending: user's whole-flow visual UAT on nine pages, actual browser
zoom, before/after evidence, Phase 10 release checklist/manifest update (include
assets/fonts/inter), and approved merge into develop. Existing manifest generator
does not yet include the new font directory; do not reuse it for this release as-is.
No push, merge, deployment or production access performed.

## Phase 9b Step 8 — Personnel import steps — 2026-10-06

BUILD/automated VERIFY complete on feature/pm-phase9b-ui-theme. Ordered three-step
navigation links to template download, upload/preview and Apply history. Upload gets
neutral emphasis; instructions use neutral text rather than a business warning color.
History is native details, closed by default. Current Preview/Apply results and inactive
account password provisioning stay outside the collapse. No invented completion state.
No controller/service/DAO/route/permission/schema/engine or import behavior change.

Exact files: templates/admin/pm-imports.tpl.html, css/pmui.css,
tests/smoke_pm_imports.php, tests/pm_phase9b_imports_browser.js,
docs/PHASE9B_STEP8_PLAN.md, docs/PROGRESS.md.

Executed verification:
- ./tests/pm_regression.ps1: 42 scripts PASS; includes import multipart HTTP,
  staging/Apply/security/1062/retry/two-connection checks. Local fixtures only;
  core dc_users not populated by Apply tests, target engine remains MyISAM.
- PHP lint tests/smoke_pm_imports.php and --preview PASS: initial/empty, escaped
  validation errors, staged/in-progress/partial/completed action conditions and
  inactive-only password form. All original form markup identical to HEAD.
- playwright-cli -s=pmnav run-code --filename=../tests/pm_phase9b_imports_browser.js:
  PASS for three anchors, native file input/label/required/multipart, CSRF, empty/error,
  keyboard/no-JS collapse, visible Apply results/password, history action gates,
  360/390/768/1440 responsive, 200% text scaling and CSP/no JS exceptions.
  Synthetic fixtures only, no business POST; desktop/mobile screenshots inspected.
- pm_ui_browser.js: 13 Phase 9 screen/state fixtures PASS for responsive, labels,
  keyboard skip/focus, text scaling and CSP. git diff --check PASS.
- Diff/security review: conditional action/password visibility and escaping retained,
  no inline scripts, no dependency and no backend change.

Preview: http://127.0.0.1:18767/.local/phase9b-import-initial.html
(synthetic; do not submit). Manual UAT/actual browser zoom pending.
No push, merge, deployment or production access. Step 9 remains.

## Phase 9b Step 7 — Timesheet ledger — 2026-10-06

BUILD/automated VERIFY complete on feature/pm-phase9b-ui-theme. Seven-day ledger
above the entry form shows persisted total/regular/OT hours, today and textual OT
with amber accent. Mobile reflows all seven days without page overflow. Admin OT
settings remain at the end in native details, closed by default/open on errors.
Missing-rate history warning uses the shared business-warning treatment.

User explicitly approved the read-only service/controller addition. One prepared
weekly aggregate uses tenant, authorized user, Monday–Sunday and deleted_at IS NULL.
Default scope is actor; Admin editing another user's record sees that edit target.
Stored DECIMAL strings retained; no recomputation of OT or rates, no writes/schema,
DAO, route or permission changes. Unavailable/invalid-date state never fabricates zero.

Files: classes/services/pmtimesheetservice.class.php,
modules/admin/pmtimesheets.module.php, templates/admin/pm-timesheets.tpl.html,
css/pmui.css, tests/pm_timesheets_smoke.php, tests/smoke_pm_timesheets.php,
tests/pm_phase9b_timesheets_browser.js, docs/PHASE9B_STEP7_PLAN.md, docs/PROGRESS.md.

Executed verification:
- ./tests/pm_regression.ps1: 42 scripts PASS, local database only, rollback fixtures.
- PHP lint changed service/controller and database test PASS; Smarty preview PASS.
- pm_timesheets_smoke.php: real aggregate over 21 rows beyond one history page,
  stored regular/OT values, Monday/Sunday and year boundary, empty days, invalid date,
  deleted rows, other user/tenant exclusion, employee denial and Admin target PASS.
  Initial added fixture lacked required snapshot columns; corrected fixture and reran.
- playwright-cli -s=pmnav run-code --filename=../tests/pm_phase9b_timesheets_browser.js:
  PASS for 360/390/768/1440, 200% text scaling, labelled days, today/OT text,
  Inter/tabular/right-aligned numerals, keyboard/no-JS collapse, CSRF, Admin/employee
  visibility and unavailable state. Desktop/mobile screenshots inspected.
- pm_ui_browser.js: 13 Phase 9 synthetic screen/state fixtures PASS including
  keyboard skip/focus, labels, responsive, text scaling and CSP compatibility.
- Original form markup compared byte-for-byte against HEAD: identical. Diff/security
  review and git diff --check PASS; new query bound, tenant/user checks before query.

Preview: http://127.0.0.1:18767/.local/phase9-timesheets-1.html (synthetic, do not submit).
Manual UAT/actual browser zoom pending. No production access, push, merge or deployment.

## Phase 9b Step 6 - Project cards and real task progress - 2026-10-05

BUILD/automated VERIFY complete on feature/pm-phase9b-ui-theme. Project list is a
1/2/3-column card grid with status, manager, dates, budget and native progress bar.
Create-project form is collapsed by default, expanded on server errors; works without JS.
Original filter/pagination, detail/task/member forms, confirmation and permissions retained.

User explicitly approved the necessary backend addition: completed task count / total
task count. PmProjectService::listProjects now executes one additional prepared aggregate
for the visible page (maximum 20 project IDs), scoped by tenant and existing task-view
permission. Soft-deleted tasks excluded consistently with tasks(). Percent rounded to
one decimal. No tasks => null progress and explicit no-data text; no task-view permission
=> no aggregate query or exposed counts. No schema/controller/route/grant changes.

Exact files: templates/admin/pm-projects.tpl.html, css/pmui.css,
classes/services/pmprojectservice.class.php, tests/pm_projects_smoke.php,
tests/smoke_pm_projects.php, tests/pm_phase9b_projects_browser.js,
docs/PHASE9B_STEP6_PLAN.md, docs/PROGRESS.md.

Executed verification:
- ./tests/pm_regression.ps1: 42 scripts PASS, local fixture database only.
- PHP lint service and both changed PHP tests PASS.
- pm_projects_smoke.php: real prepared aggregate, multiple projects/one query,
  no-task/zero/33.3/50/100, soft-deleted tasks, foreign tenant, member scope and empty
  page PASS, with rollback fixtures. Task-view denial uses a restricted in-memory
  permission snapshot and verifies null fields/zero aggregate queries. Aggregate EXPLAIN
  executed successfully; small local dataset, not a production performance benchmark.
- smoke_pm_projects.php --preview: escaped names/search, cards, native progress,
  no-data/restricted states, read-only/create/error/empty and existing task UI PASS.
- playwright-cli -s=pmnav run-code --filename=../tests/pm_phase9b_projects_browser.js:
  PASS at 360/390/768/1440, columns 1/1/2/3, 200% text scaling, labelled progress,
  keyboard native collapse, CSRF field, query-preserving pagination, escaped names,
  restricted/read-only states, no-JS and numeric Inter/tabular/right-alignment.
  Project navigation intercepted to synthetic detail fixture; no business POST or UAT claim.
  Status text contrast measured 15.68:1 to 19.90:1 across treatments.
- pm_ui_browser.js: 13 Phase 9 screen/state fixtures PASS for keyboard skip/focus,
  labels, CSP, responsive and text scaling. Desktop/mobile screenshots inspected.
- Exact original form markup compared against HEAD; diff/security review and
  git diff --check PASS. SQL parameters remain bound; no cross-tenant task count leak.

Preview http://127.0.0.1:18767/.local/phase9b-project-list.html (synthetic; do not submit).
Manual visual UAT/real browser zoom pending. No push, merge, deployment or production access.


## Phase 9b Step 5 - Cost KPI/tabs/empty charts - 2026-10-05

BUILD/automated VERIFY complete on feature/pm-phase9b-ui-theme. Three KPIs form one
neutral strip; project and task details and existing group summaries share an accessible
tab block. Arrow Left/Right, Home/End, focus and selected/panel state supported. No-JS
shows both panels without inert tabs. Current selection survives polling refresh.
Empty/incompatible charts show an explanation with canvas hidden and Chart.js instance
destroyed; chart returns when compatible data returns. Legitimate zero samples retained.
No new backend, API, permission, schema, filters, pagination or monetary calculation.
Existing authoritative decimal strings untouched; numeric conversion only in charts.

Files: templates/admin/pm-costs.tpl.html, js/pmcosts.js, css/pmui.css,
tests/smoke_pm_costs.php, tests/pm_phase9b_costs_browser.js,
docs/PHASE9B_STEP5_PLAN.md and docs/PROGRESS.md.
Executed verification:
- ./tests/pm_regression.ps1: final complete run 42 scripts PASS. Initial run failed
  existing pm_import_apply_smoke connection-close-lock check; targeted rerun PASS,
  then full rerun PASS. No import implementation or test changed for this UI work.
- PHP lint tests/smoke_pm_costs.php and smoke_pm_costs.php --preview PASS:
  mixed/empty/error/escaping, task history, original filter/page links retained.
- playwright-cli -s=pmnav run-code --filename=../tests/pm_phase9b_costs_browser.js
  PASS with real local Chart.js: empty/mixed/zero/recovery, instance lifecycle,
  decimal display, tab keyboard/focus/selection, retained data on 500, stop on 403,
  no-JS and missing-library fallback, labels/skip/CSP, four widths, 200% text scaling.
  HTTP polling responses mocked; not authenticated cost UAT or actual browser zoom.
- pm_ui_browser.js: 13 Phase 9 fixtures keyboard/labels/skip/CSP/responsive PASS;
  pm_phase9b_theme_browser.js: seven fixtures/font/navigation/numeric checks PASS.
- Desktop/mobile data/empty screenshots inspected. Chart colors dark/neutral with
  white-background contrast 17.72, 4.83, 3.46 (computed WCAG formula).
  Empty text #52525b on #f4f4f5: 7.03:1. Inter applied to axes, legend and tooltip.
- Every original template form compared against HEAD: exact markup unchanged.
  Code/security review: no unsafe HTML assembly or permission/data-scope changes;
  git diff --check PASS. No new library or second theme CSS.

Preview http://127.0.0.1:18767/.local/phase6-preview.html (synthetic data).
Manual visual UAT/real browser 200% zoom pending. No push/merge/deploy/production access.


## Phase 9b Step 4 - Allocation week view - 2026-10-05

BUILD/automated VERIFY complete on feature/pm-phase9b-ui-theme. Seven day columns,
rows grouped by visible employee, server-provided total load and capacity thresholds.
Day/week overbooking includes text and amber accent. No visible allocation detail is
labelled unknown rather than 0; filtered row hours are never summed as tenant totals.
Existing detail table and all forms/actions retained, collapsed only after JS enhancement;
no-JS keeps table open. Three management sections grouped in one native-details panel.
Existing polling supplies row metadata and MutationObserver refreshes the week view;
no new request, route, permission, DAO, schema, capacity or business calculation.
Fixed polling-created hide buttons to retain destructive color after refresh.

Files: templates/admin/pm-allocations.tpl.html, css/pmui.css, js/pmallocations.js,
js/pmallocationweek.js, tests/smoke_pm_allocations.php,
tests/pm_phase9b_allocation_browser.js, docs/PHASE9B_STEP4_PLAN.md, docs/PROGRESS.md.
Executed checks:
- ./tests/pm_regression.ps1: 42 scripts PASS on local fixture database.
- PHP lint and tests/smoke_pm_allocations.php --preview PASS: Smarty escaping,
  attribute metadata, read-only Employee and error state.
- playwright-cli -s=pmnav run-code --filename=../tests/pm_phase9b_allocation_browser.js
  PASS: year rollover, repeated rows/server totals, multiple employees, warning text,
  polling render and empty refresh, unsafe-string text escaping, original CSRF forms,
  keyboard panel/detail focus, 360/390/768/1440, 200% text scaling, no-JS fallback.
- playwright-cli -s=pmnav run-code --filename=../tests/pm_ui_browser.js:
  13 Phase 9 screen/state fixtures PASS, keyboard skip/focus, labels, CSP, responsive.
- All original template forms compared against HEAD: exact form markup unchanged.
  Code/security review and git diff --check PASS; no backend files changed.
- Desktop/mobile synthetic screenshots inspected in ignored .local. Initial browser
  checks found fixture anchor navigation and panel closing tag issues; fixed and rerun.

Preview http://127.0.0.1:18767/.local/phase7-preview.html (synthetic; do not submit).
Actual browser zoom/manual visual UAT and authenticated use of new week controls not
claimed. Existing service/HTTP regression covers backend allocation actions. No push,
merge, deployment or production access. Final release manifest must include new JS.


## Phase 9b Step 3 ? Unified theme ? 2026-10-05

BUILD/automated VERIFY complete on feature/pm-phase9b-ui-theme. User authorized
self-directed planning/build within the agreed UI scope, without routine approval.
White neutral theme, consistent light navigation with icon + text, self-hosted Inter,
rounded cards/controls and tabular right-aligned numeric tables. Shared css/pmui.css;
no controller/DAO/routes/permissions/database/business changes or external font calls.
Exact 19 files listed in docs/PHASE9B_STEP3_PLAN.md, including this progress entry.

Executed: ./tests/pm_regression.ps1 ? 42 scripts PASS; PHP lint smoke_pm_ui.php PASS.
playwright-cli -s=pmnav run-code --filename=../tests/pm_ui_browser.js and
pm_phase9b_buttons_browser.js, pm_phase9b_users_browser.js,
pm_phase9b_theme_browser.js ? PASS. Browser fixtures verify keyboard/focus/labels,
CSP, modal/no-JS fallback, button contrast/states, seven theme fixtures, full nav,
360/390/768/1440 widths and 200% text scaling (not actual browser zoom).
Chromium platform-font inspection verifies Inter renders all 90 U+1EA0?U+1EF9 glyphs.
Desktop/mobile screenshots inspected; corrected dashboard muted-label contrast.
Code/security review and git diff --check PASS. Synthetic tests are not production UAT.

Font: official rsms/inter docs/font-files/InterVariable.woff2, 352240 bytes;
SHA256 693b77d4f32ee9b8bfc995589b5fad5e99adf2832738661f5402f9978429a8e3.
OFL license included. Before final release, update historical deployment manifest
classification to include both new assets/fonts/inter files and regenerate package.
Preview http://127.0.0.1:18767/.local/phase9-theme-nav.html (synthetic; do not submit).
Manual visual acceptance and actual browser zoom pending. No push/merge/deploy.


## Phase 9b — Step 2 personnel BUILD/VERIFY — 2026-10-05

Branch feature/pm-phase9b-ui-theme. Compact six-column personnel list now precedes
auxiliary sections. Add-person/add-department forms are closed details by default.
One-person editor enhanced to native modal with separate profile/role/rate POST forms,
permission gates, Tab/Shift+Tab containment, Escape/close and focus-return. Without JS
or showModal the details editor is usable inline. Errors remain full-page as before;
unsaved edits are not retained across server reload. No controller/DAO/routes/permissions,
schema or business logic changes. No additional CSS or library.

Exact files: templates/admin/pm-users-v2.tpl.html, css/pmui.css, js/pmui.js,
tests/smoke_pm_users.php, tests/pm_phase9b_users_browser.js,
tests/pm_phase9b_buttons_browser.js, docs/PHASE9B_STEP2_PLAN.md, docs/PROGRESS.md.

Executed verification:
- ./tests/pm_regression.ps1: 42 scripts PASS. Independent user/role/rate render gates,
  escaping and lock/unlock assertions PASS. HTTP active actor coverage Admin/Employee;
  does not claim full-role UAT or native user edit through the new modal.
- playwright-cli -s=pmnav run-code --filename=../tests/pm_phase9b_users_browser.js
  from .local: two distinct users/form IDs, CSRF, no nested forms, Tab/Shift+Tab,
  Escape/close/focus-return, cancelled destructive submit, no-JS fallback PASS;
  360/390/768/1440 and 200% text scaling PASS. Confirmation test stubs confirm=false
  and verifies original message plus zero POST; not a native dialog acceptance test.
- playwright-cli -s=pmnav run-code --filename=../tests/pm_ui_browser.js:
  13 synthetic Phase 9 screen/state fixtures at four widths PASS, keyboard skip/focus,
  labels, no overflow, CSP and text scaling; no new page JS exceptions.
- playwright-cli -s=pmnav run-code --filename=../tests/pm_phase9b_buttons_browser.js:
  Step 1 computed token/contrast, states, semantic and extra responsive fixtures PASS.
- Compared every POST form against f490621: exact markup set unchanged, including
  CSRF/actions/fields/confirmation. PHP lint test file and git diff --check PASS.
- Synthetic desktop/mobile list/modal screenshots inspected; no production access.

Preview http://127.0.0.1:18767/.local/phase9-users-two.html — synthetic, do not submit.
BUILD/automated VERIFY complete; user visual acceptance/real browser 200% zoom pending.
Await user check and request for Step 3 PLAN. No push/merge/deploy. Sidebar/font/global
theme application remains a separate step, not delivered by this layout change.

## Phase 9b — Step 1 replacement tokens — 2026-10-05

User replaced the previous visual direction with a single neutral design system for all
PM screens. This supersedes the old palette in d9c026f; that commit is retained as history.
BUILD and automated VERIFY complete; visual acceptance and real browser zoom pending.
No Step 2, push, merge or deployment.

Exactly five files changed: `css/pmui.css`, `tests/pm_phase9b_buttons_browser.js`,
`docs/PLAN_FINAL.md`, `docs/PHASE9B_STEP1_PLAN.md`, `docs/PROGRESS.md`.
PLAN_FINAL received the user's full replacement Part 13; audited business decisions kept.
Primary #18181b/#fafafa; destructive #dc2626/#ffffff (approved accessibility exception:
#fef2f2 only gives 4.41:1); neutral logout, 6px button radius, updated state/focus colors.
Shared theme tokens declared; sidebar/background/cards/Inter application is deferred to
the separate theme step. No Lora or IBM Plex introduced; no second stylesheet.
Warning #d97706 is a token only in this step, not applied as low-contrast small text.
No template/controller/DAO/permission/route/business changes in this replacement.

Executed checks:
- `./tests/pm_regression.ps1`: 42 scripts PASS; authenticated HTTP actors Admin/Employee,
  not a claim of four-role hosted verification or UAT.
- `playwright-cli -s=pmnav run-code --filename=../tests/pm_phase9b_buttons_browser.js`
  from `.local`: computed tokens and default/hover/active/focus/disabled PASS,
  primary 16.97:1, destructive 4.83:1, all tested states >=4.5:1, targets >=44px;
  lock/unlock/restore and extra allocation/cost/import responsive fixtures PASS.
- `playwright-cli -s=pmnav run-code --filename=../tests/pm_ui_browser.js`: 13 synthetic
  screen/state fixtures PASS at 360/390/768/1440; keyboard/labels/skip/overflow/CSP,
  200% text scaling (not browser zoom). Desktop/mobile screenshots inspected.
- `git diff --check`: PASS. Browser matrix reports no JS page exceptions; known synthetic
  favicon 404 is not an application behavior test. No production verification claimed.

View synthetic preview: http://127.0.0.1:18767/.local/phase9-users-1.html (do not submit).
Wait for user visual check and request to start Step 2.

## Phase 9b — Step 1 BUILD/VERIFY — 2026-10-05

Branch: `feature/pm-phase9b-ui-theme`, based on develop after fast-forward to Phase 10.
BUILD/automated VERIFY complete; user visual acceptance pending. No Step 2 BUILD,
push, merge or deployment. User subsequently authorized commits of verified changes;
this commit records implementation, not UAT acceptance.

Runtime files: `css/pmui.css`, `templates/admin/pm-users-v2.tpl.html`,
`templates/admin/pm-allocations.tpl.html`, `templates/admin/pm-rates-panel.tpl.html`,
`templates/admin/pm-timesheets.tpl.html`. One shared CSS file; no controller/DAO/routes,
permission, form fields, confirmation messages or business logic changed.
Ordinary action #B98A2E/#161A20, destructive #A6432D/#E7E9EC; lock/unlock dynamic,
hide/deactivate destructive, restore ordinary; logout remains neutral.

Verification commands/results:
- `./tests/pm_regression.ps1`: 42 scripts PASS, including Smarty lock/unlock assertions.
- `playwright-cli -s=pmnav run-code --filename=../tests/pm_ui_browser.js` from `.local`:
  13 synthetic screen/state fixtures PASS at 360/390/768/1440, labels, keyboard skip/focus,
  no page overflow/inline code, 200% text scaling (not real browser zoom).
- `playwright-cli -s=pmnav run-code --filename=../tests/pm_phase9b_buttons_browser.js`:
  computed contrast ordinary 5.60:1, destructive 4.99:1; hover/focus/disabled >=4.5,
  tested targets >=44px, unlock/restore semantics, reduced motion;
  extra costs/allocation/import fixtures no page overflow at all four widths.
- Desktop/mobile user screenshots inspected; synthetic preview has fallback navigation,
  not a complete authenticated menu. Missing favicon produces a local 404; no new JS
  page exceptions in Phase 9 matrix. Live financial/polling behavior is covered separately
  by regression HTTP tests, not the synthetic preview.

Preview: `http://127.0.0.1:18767/.local/phase9-users-1.html` (synthetic, do not submit).
Real browser zoom 200% and user visual acceptance remain pending. No physical copies of
the user's original nine before screenshots have been stored; no before evidence invented.

## UAT follow-up — 2026-10-05

UI follow-up: thống nhất navigation desktop thành rail trái 260px cho các module PM,
mobile dùng menu wrap, không đổi permissions/controller. Smarty render PASS; browser
synthetic Nhân sự 1440/390px không overflow, screenshots đã xem; detector CSS không có
finding. Regression 42 scripts PASS. Chưa xác nhận giao diện sau upload hosting.

Release follow-up: operator đã deploy thủ công dưới maintenance, smoke timesheet/audit
và allocation overlap/adjacent đạt theo ảnh/xác nhận. Chưa production PASS; costs/export,
four-role hosting, HTTPS và cleanup chưa hoàn tất. User duyệt bỏ benchmark lock 007;
DEPLOY_LOG ghi ngoại lệ, không suy diễn thời gian lock từ query đọc.
Isolation lookup chuyển vào PmDb, test fixture kiểm tra fallback chỉ cho 1193 và lỗi
khác được truyền lên. `./tests/pm_regression.ps1`: PASS 42 scripts; PHP lint/diff check PASS.
Nhánh MariaDB là driver-response fixture, chưa native MariaDB verification. Upload bản
sửa cần cả pmdb.class.php và pmcostservice.class.php; manifest đã cập nhật.

Production smoke do người dùng thực hiện: costs báo lỗi tải. Code đọc
`@@transaction_isolation` không tương thích MariaDB 10.6; bổ sung fallback
`@@tx_isolation` chỉ cho lỗi unknown-variable 1193, giữ isolation/currency guard.
PHP lint và regression local 41 scripts PASS; nhánh fallback chưa kiểm thử trực tiếp
trên MariaDB hosting, cần người dùng upload và xác minh lại. Không thay schema.

Người dùng xác nhận đăng nhập Admin/mở trang nghiệp vụ/logout và phòng ban đạt;
chưa xác nhận toàn bộ UAT. Phát hiện mã phòng ban đã ẩn vẫn được giữ bởi UNIQUE.
Đã bổ sung thông báo mã thuộc phòng ban đã ẩn và mục khôi phục dành cho người có
`pm.users.manage`; khôi phục cùng ID/mã, giữ lịch sử, không tạo bản ghi mới.
POST có CSRF và tenant scope; không đổi schema hoặc xóa dữ liệu.
`php tests/pm_departments_smoke.php`: PASS hide/restore, cross-tenant và rollback.
`php tests/smoke_pm_users.php --preview`: PASS render, escaping và read-only không thấy
khôi phục. `./tests/pm_regression.ps1`: PASS 41 scripts; PHP lint/diff check PASS.
Bổ sung DEPLOY_LOG review bắt buộc 002, maintenance liên tục và benchmark lock 007;
không chạy trên production. Follow-up đã commit local trên nhánh Phase 10: debb73c (phòng ban), 5ca1b14 (MariaDB isolation), c860e02 (navigation). Không push; chưa xác nhận các bản sửa đã deploy.

## Phase 0 — Discovery & Audit

### Status

COMPLETED — chờ merge theo quyết định của người dùng.

### Completed

- Audit kiến trúc, auth/session, DAO, Smarty, pagination, Tracking Data và Excel.
- Ghi project brief, plan chính thức và quy tắc làm việc.
- Chốt bảy quyết định kỹ thuật; ghi ba quyết định nghiệp vụ chờ mentor cùng mặc định an toàn.

### Database

Không tạo hoặc chạy migration. Không kết nối production.

### Git

- Branch: `feature/pm-phase0-audit`
- Commit: `3df3349 docs(pm-phase0): record audit and delivery plan`

## Phase 1 — Môi trường & Baseline

### Status

COMPLETED — local PHP/DB/web baseline và login/logout thủ công đều đạt.

### Environment

- PHP 8.3.35 portable đã được chuẩn bị trong `.tools/` bị Git ignore; PHP mặc định của máy vẫn là 8.5.10.
- MySQL client 8.4.11 và service `MySQL84` có sẵn; chưa có credential/DB copy local để test ứng dụng.
- Smarty 4.5.5 được nhúng trong source.
- PHP 8.3 portable đã bật `mysqli`, `mbstring`, `openssl`, `fileinfo`, `zip`, `gd`, `intl`.
- Chưa phát hiện XAMPP/Laragon hoặc local web root đã cấu hình.

### Completed

- Tạo `develop` và `feature/pm-phase1-baseline` tại checkpoint Phase 0.
- Xác nhận file cấu hình và license thật đang được Git ignore.
- Chuẩn hóa thư mục và quy tắc migration tại `database/migrations/README.md`.
- Lập hồ sơ môi trường và ma trận baseline tại `docs/BASELINE.md`.
- PM template smoke test đạt cho dashboard, profile, password và team.
- Smarty 4.5.5 và PhpSpreadsheet smoke test ghi XLSX tạm thời thành công trên PHP 8.3, không có warning.
- Lint 1.401 file PHP bằng PHP 8.3: 1.396 đạt, 5 file mail legacy/third-party không tương thích và đã ghi rõ trong `docs/BASELINE.md`.
- Import dump vào database mới `derasoft_pm_local`; 76 bảng và 8 user, không ghi đè database khác.
- Ứng dụng kết nối database local bằng user chỉ có quyền trên database dự án.
- Public landing và Admin login trả HTTP 200 trên PHP 8.3; ca login sai hiển thị lỗi đúng và không fatal.
- Sửa route logout bị loại khỏi danh sách operation hợp lệ; logout hiện trả 302, xóa session/cookie và truy cập lại dashboard sẽ về trang login.
- Người dùng xác nhận login, dashboard và logout hoạt động đúng trên local ngày 2026-10-02.

### Database

Không tạo migration SQL. Dump đã được import vào database local mới `derasoft_pm_local`; không tác động production hoặc database cũ. Schema baseline nằm tại `docs/DB_SCHEMA.md`.

### Known Issues

- PHP local khác phiên bản production.
- Chưa có local DB đã ẩn dữ liệu và web server/virtual host.
- `vendor/autoload.php` không có; các thư viện nhúng cần được kiểm tra riêng.
- Năm file PHPMailer/mail legacy không parse trên PHP 8.3; không nằm trong PM hiện tại nhưng phải xử lý có chọn lọc trước khi dùng email.
- Auth legacy dùng MD5 và DAO legacy ghép chuỗi SQL; dự kiến xử lý Phase 2, chưa sửa Phase 1.

### Remaining Before Phase 2

- CRUD legacy, tracking có ghi dữ liệu và upload vẫn để manual check vì không tạo dữ liệu giả vào bản import.
- Chốt ba quyết định nghiệp vụ với mentor trước phase liên quan; không cần đóng cứng chúng trong Phase 2.

### Git

- Branch: `feature/pm-phase1-baseline`
- Commit: chưa tạo.

### Next Step

Thực hiện PLAN Phase 2 — Auth, Role & Permission. Không triển khai migration/auth trước khi plan Phase 2 được duyệt.

## Phase 2 — Auth, Role & Permission

### Status

COMPLETED — build, automated verification và kiểm thử thủ công tài khoản thật đều đạt.

### Completed

- Mở rộng `dc_users` bằng cột nullable `password_hash`; giữ nguyên cột MD5 để tương thích tài khoản cũ.
- Tạo bốn bảng RBAC InnoDB: `dc_pm_roles`, `dc_pm_permissions`, `dc_pm_user_roles`, `dc_pm_role_permissions`.
- Seed idempotent bốn role `ADMIN`, `PM`, `HR`, `EMPLOYEE`, 18 permission và mapping ban đầu cho user legacy active.
- Thêm adapter prepared statement riêng cho code PM mới, không rewrite lớp `Model` legacy.
- Login hỗ trợ username hoặc email duy nhất trong store; tài khoản MD5 được nâng hash sau lần xác thực thành công.
- Mật khẩu đổi từ PM chỉ ghi `password_hash`; không xóa hash legacy.
- Regenerate session ID sau login; cookie dùng HttpOnly, SameSite=Lax, strict mode và Secure trên HTTPS.
- Thêm `requirePermission()` và `requireProjectAccess()`; project access fail closed cho tới khi bảng thành viên dự án được tạo ở phase dự án.
- Tích hợp permission vào dashboard, hồ sơ, đổi mật khẩu và danh sách nhân sự; menu nhân sự được ẩn khi không có quyền.

### Database

- Migration: `database/migrations/001_create_pm_auth_rbac.sql`.
- Seed: `database/seeds/001_seed_pm_auth_rbac.sql`.
- Đã chạy và xác minh trên `derasoft_pm_local`; không kết nối hoặc thay đổi production.
- Migration chỉ cộng thêm schema. Không có `DROP`, `RENAME`, `TRUNCATE` hoặc xóa dữ liệu.
- Rollback ứng dụng: có thể quay lại code cũ và để nguyên cột/bảng mới chưa dùng; project policy không cho rollback bằng DROP.

### Verification

- Phase 2 schema và seed: PASS.
- Password primitives, 4 role, permission boundaries và legacy mappings: PASS.
- PM Admin template smoke: PASS.
- Smarty/PhpSpreadsheet regression smoke: PASS.
- PHP 8.3 lint toàn bộ file PHP thay đổi trong Phase 2: PASS.
- Người dùng xác nhận đăng nhập username, dashboard, cập nhật hồ sơ, đổi mật khẩu và logout hoạt động đúng trên local ngày 2026-10-02.
- Đăng nhập bằng email đã có automated coverage ở tầng truy vấn nhưng chưa manual test vì người dùng chưa cấu hình email dùng để thử.

### Git

- Branch: `feature/pm-phase2-auth-role`.
- `b231629 feat(pm-auth): add RBAC schema and default roles`
- `ef047d4 feat(pm-rbac): add prepared role and permission data access`
- `69d3e04 feat(pm-auth): upgrade legacy login and session security`
- `a41fc96 feat(pm-rbac): enforce permissions in admin workspace`
- `d8ca15e test(pm-auth): verify Phase 2 security boundaries`
- `e2a11c0 fix(smarty): use writable local compile cache`
- `2e5c55b fix(pm-auth): preserve form routes on account updates`

### Next Step

Merge Phase 2 vào `develop`, sau đó thực hiện PLAN Phase 3 — User, Role & Hourly Rate. Không tạo migration hoặc code Phase 3 trước khi plan được duyệt.

## Phase 3 — User, Role & Hourly Rate

### Status

BUILD/VERIFY COMPLETED — bổ sung CRUD phòng ban và siết permission render/action; automation Phase 2–3 PASS. Chờ kiểm thử thủ công User CRUD, role và rate trước khi merge.

### Completed

- Chốt và ghi nhận kiến trúc PM multi-tenant theo `store_id` kế thừa DeraSoft; hiện không thêm UI chọn tenant.
- Thêm migration department, thuộc tính user và hourly rate; tenant hóa/widen schema RBAC Phase 2 theo ngoại lệ được duyệt.
- Thêm prepared DAO cho danh sách, tìm kiếm, tạo/sửa, khóa/mở khóa, soft delete user và gán nhiều role/role chính.
- Thêm `PmRateService` với XOR owner ở application layer, kiểm tra overlap đối xứng và khóa owner rows `FOR UPDATE` trong transaction.
- `resolveRate()` ưu tiên user rate, role chính rồi fallback 0 VND kèm cảnh báo.
- Bổ sung màn hình thêm rate theo role, xem lịch sử, sửa khoảng hiệu lực và ngừng áp dụng rate; quyền xem/ghi được kiểm tra riêng.
- Rate input được validate dạng số thập phân theo giới hạn schema; không cho đổi owner của rate khi sửa.
- Thêm giao diện quản lý nhân sự responsive, role, rate, trạng thái và phân trang trong PM Admin.
- Mọi mutation qua module có CSRF, permission, tenant check và ghi Tracking Data mô tả.

### Verification

- Phase 3 tenant schema: PASS trên `derasoft_pm_local`.
- Owner XOR và overlap đối xứng, gồm rate mới mở vô hạn đè rate cũ có ngày kết thúc: PASS.
- Phase 2 auth/RBAC regression: PASS.
- PM Admin và PM users Smarty smoke: PASS.
- PHP 8.3 lint các file Phase 3: PASS.
- Không kết nối hoặc thay đổi production.

### Next Step

Kiểm thử thủ công trên local: danh sách/search, create/edit, lock/unlock, soft delete, multi-role/role chính và user rate. Sau khi được xác nhận mới commit phần nghiệm thu, push/merge Phase 3 và dừng trước Phase 4.

### Follow-up 2026-10-02

- Người dùng yêu cầu tiếp tục BUILD Phase 4 sau rà soát; lưu checkpoint source bổ sung Phase 3 bằng commit `417c6e6 feat(pm-workforce): complete department and hourly rate management`.
- Lỗi include rate panel đã sửa; smoke render dùng template root `templates/` giống Admin thực tế.
- Kiểm thử thủ công bằng tài khoản thật vẫn chưa được xác nhận; không ghi nhận UAT PASS.
- Phase 4 branch kế thừa checkpoint Phase 3; chưa merge develop hoặc push remote.

## Phase 4 — Project & Task

### Status

BUILD và automated VERIFY đạt trên local; chờ nghiệm thu thao tác trình duyệt bằng tài khoản thật.

### Completed

- Kế hoạch chi tiết: `docs/PHASE4_PLAN.md`; branch `feature/pm-phase4-project-task`.
- Migration `003_create_pm_projects_tasks.sql` tạo ba bảng InnoDB tenant-scoped mới; đã áp dụng trên `derasoft_pm_local`, không sửa bảng legacy.
- CRUD dự án với search/phân trang, manager, trạng thái, ngân sách và ngày; soft delete giữ dữ liệu.
- Quản lý thành viên active; chặn gỡ manager hoặc người còn task được giao.
- CRUD task, assignee trong phạm vi membership active, trạng thái/ưu tiên/giờ dự kiến/hạn.
- Kanban bốn cột với form chỉnh sửa và chuyển trạng thái POST/CSRF.
- PM chỉ quản lý dự án mình phụ trách; Admin vẫn phải lọc tenant; helper project access từ chối dự án không tồn tại/soft deleted.
- Các view dùng relative includes, escape output và ẩn mutation forms theo quyền.

### Verification

- `tests/pm_phase4_migration.php`: PASS schema InnoDB và store_id.
- `tests/pm_projects_smoke.php`: PASS CRUD, membership, assignee, Kanban, tenant và PM boundaries; fixture rollback được xác minh.
- `tests/smoke_pm_projects.php`: PASS render các form/create/edit/read-only và escaping.
- Regression auth/RBAC, users, rates, departments, Admin và dependencies Phase 2–3: PASS.
- PHP 8.3 lint file mới/thay đổi và `git diff --check`: PASS.

### Next Step

Nghiệm thu local tại `admin.php?op=pmprojects`: tạo/sửa dự án, thêm thành viên, tạo/giao task,
chuyển trạng thái Kanban, thử gỡ thành viên còn task và soft delete. Thử thêm tài khoản PM
và Employee để xác nhận trải nghiệm theo quyền. Chưa triển khai Phase 5 hoặc production.

## Phase 5 — Timesheet, OT & Audit

### Status

BUILD và automated VERIFY hoàn tất theo phạm vi cập nhật ngày 2026-10-02: timesheet, OT,
audit viewer, khóa tự động sau cửa sổ 3 ngày và ngoại lệ Admin. Workflow duyệt bị hoãn theo
yêu cầu người dùng; Submitted/Approved/Rejected không thuộc phạm vi nghiệm thu Phase 5.
Chưa xác nhận UAT bằng trình duyệt/tài khoản thật; không ghi nhận UAT PASS.

### Completed

- Giữ phần BUILD có sẵn trên `feature/pm-phase5-timesheet-ot`; không commit hoặc push.
- Route `admin.php?op=pmtimesheets`, menu theo permission, giao diện Smarty responsive để tạo/sửa/ẩn timesheet của chính user; Admin xem và sửa bản ghi của mọi user trong tenant.
- Chỉ ghi công vào task được giao, membership active và cùng tenant; các thao tác ghi kiểm tra CSRF.
- Tổng giờ tối đa 24/ngày, phân bổ giờ thường/OT theo id trên mọi dự án; sửa/chuyển ngày/xóa mềm tính lại ngày liên quan.
- Snapshot rate khi tạo/chuyển ngày; sửa cùng ngày giữ rate. Chi phí tính bằng CAST DECIMAL trong MySQL, không dùng float PHP để tính tiền.
- Ledger khóa ngày theo tenant/user/date, snapshot ngưỡng và hệ số. Cửa sổ sửa/xóa là 3 ngày lịch theo giờ Việt Nam (work_date là ngày thứ nhất), tự khóa từ ngày thứ tư; kiểm tra server cả ngày cũ/mới, chặn backdate/future của non-Admin.
- Admin sửa/xóa sau khóa, vẫn giữ owner và tính lại OT trên ngày của owner; audit riêng `admin_update_locked`/`admin_delete_locked`. Task lịch sử đã ẩn/đổi assignee vẫn sửa được khi Admin giữ nguyên task_id.
- Route `admin.php?op=pmaudit`, lọc entity/action và phân trang. Admin xem toàn tenant và settings; PM xem dự án mình quản lý; HR tạm xem phòng ban active hiện tại, chưa có phòng ban chỉ xem cá nhân. PM/HR không nhận financial fields trong audit JSON. Phạm vi HR toàn công ty chưa được chốt.
- Không xây endpoint duyệt, không reject-cả-ngày; cột status/permission approve đã có giữ nguyên không sử dụng. Quyết định hoãn workflow ghi trong `docs/AUDIT.md`.
- Admin cấu hình ngưỡng/hệ số cho ngày mới; audit JSON nằm cùng transaction với mutation và recalculation.

### Database

- Backup `derasoft_pm_local` trước migration trong `.local/` được Git ignore; không in credential hoặc stage backup.
- `004_create_pm_timesheets_audit.sql` chỉ CREATE TABLE IF NOT EXISTS cho settings, ledger, timesheets, audit logs.
- Áp dụng hai lần trên local đều PASS; không thay đổi production hoặc dữ liệu legacy.
- Backup local thêm trước seed `002_seed_pm_phase5_audit_permissions.sql`; seed cộng thêm permission `pm.audit.view` cho Admin/PM/HR theo tenant, chạy hai lần đều PASS. Không xóa grant hoặc sửa schema legacy.
- Rollback ứng dụng giữ bảng cộng thêm và ngừng sử dụng route mới; không DROP bảng.

### Verification — lệnh đã chạy

Tất cả lệnh PHP dưới đây dùng `.tools/php83/php.exe` (PHP 8.3):

- `tests/pm_phase5_migration.php --apply` (hai lần), sau đó `tests/pm_phase5_migration.php`: PASS schema InnoDB và tenant.
- `tests/pm_timesheets_smoke.php`: PASS OT, tính lại khi sửa/xóa/chuyển ngày, rate và settings snapshot, chi phí DECIMAL với đơn giá lớn, rollback vượt 24 giờ, ownership/tenant/task boundaries, Admin settings và audit. Fixture transaction rollback, kiểm tra project fixture không còn tồn tại.
- `tests/pm_timesheet_window_smoke.php`: PASS ranh giới ngày thứ ba/thứ tư, sửa/xóa/chuyển ngày/backdate sau khóa, future denial, Admin sửa/xóa bản ghi của user khác và task đã ẩn, tính lại OT đúng owner, audit riêng và fixture rollback.
- `tests/pm_audit_smoke.php`: PASS tenant/role/PM project scopes, HR own/same/other/inactive department scopes, financial redaction, filter validation và fixture rollback.
- `tests/pm_phase5_audit_permissions.php --apply` (hai lần): PASS tenant-scoped grants.
- `tests/smoke_pm_timesheets.php`, `tests/smoke_pm_audit.php`: PASS render create/locked/read-only, escaping, Admin settings, audit details/filter/pagination/empty state.
- Regression `tests/pm_auth_rbac_smoke.php`, `tests/pm_rates_smoke.php`, `tests/pm_departments_smoke.php`, `tests/pm_projects_smoke.php`, `tests/smoke_pm_admin.php`, `tests/smoke_pm_users.php`, `tests/smoke_pm_projects.php`, `tests/smoke_pm_dependencies.php`: PASS.
- `-l` trên toàn bộ PHP mới/thay đổi của Phase 5: PASS.
- `git -c safe.directory=D:/derasoft-pm diff --check`: PASS.

### Security review

Rà soát controller/service/view/migration: prepared queries, tenant + ownership scope, POST/CSRF, escape HTML,
Admin-only settings, khóa 3 ngày kiểm tra server kể cả sau chờ ledger lock, override Admin có audit riêng,
audit scopes và financial redaction, audit trong transaction. Không phát hiện lỗi còn mở trong phạm vi đã kiểm tra.
Chưa chạy kiểm thử concurrency nhiều connection hoặc E2E HTTP/CSRF; không coi smoke service/template là UAT.

### Next Step

Nghiệm thu local tại `admin.php?op=pmtimesheets` và `admin.php?op=pmaudit`: tạo hai ca tổng trên 8 giờ,
sửa/chuyển ngày/ẩn, thử vượt 24 giờ, xem bản ghi bị khóa và Admin sửa sau khóa, thử PM/HR audit scope.
Xác nhận phạm vi đọc HR tạm thời, UAT trước merge. Workflow duyệt bị hoãn;
không tự triển khai submit/approve/reject, production, commit hoặc merge.

### Checkpoint tạm dừng — 2026-10-02

Người dùng yêu cầu tạm dừng BUILD, tạo commit và push nhánh Phase 5 lên GitHub.
Lưu phạm vi BUILD đã automated VERIFY tại `feature/pm-phase5-timesheet-ot`; chưa merge,
chưa deploy và chưa ghi nhận UAT PASS. Tiếp tục nghiệm thu khi người dùng yêu cầu resume.
Không đưa config bảo mật, backup database, log hoặc cache vào commit.

### Resume — 2026-10-02

- Người dùng yêu cầu tiếp tục công việc và ghi nhớ cách trình bày GitHub chuyên nghiệp cho CV.
- Lưu yêu cầu danh tính Git, commit có scope, staged files an toàn, README/portfolio trung thực và cập nhật theo phase trong `AGENTS.md`.
- Danh tính Git local xác minh đúng Tạ Trí Dũng / GitHub noreply; nhánh Phase 5 đang sạch tại checkpoint trước thay đổi tài liệu resume.
- Chuẩn bị `docs/PHASE6_PLAN.md` cho chi phí/biểu đồ. Status PLAN, chưa BUILD/seed/migration Phase 6, chờ duyệt theo quy trình phase.
- Phase 5 chưa merge, chưa có xác nhận UAT. Resume không tự coi UAT đã hoàn tất.

## Phase 6 — Cost & Chart

### Status

BUILD / automated VERIFY COMPLETED trên `feature/pm-phase6-cost-chart` theo plan đã duyệt,
có bổ sung currency guard. Chờ UAT nghiệp vụ; chưa commit/push/merge/deploy Phase 6.

### Completed

- Prepared DAO `PmCosts` và `PmCostService`, route `admin.php?op=pmcosts`, menu theo permission.
- Actual dùng cost đã lưu trên timesheet; tổng hours/regular/OT và chi phí theo project/task/user/department/primary role/ngày; không resolve lại rate cho actual.
- Kiểm tra DISTINCT currency toàn tập trước mỗi SUM(cost), cùng transaction REPEATABLE READ/SERIALIZABLE. Mixed hoặc currency không hợp lệ: cost=null + warning, vẫn trả giờ. Kiểm tra riêng theo group, khoảng lọc và lifetime.
- Estimate tại ngày định giá hiển thị, từ estimated_hours × current user/primary-role rate, SQL DECIMAL; thiếu assignee/rate hoặc mixed currency không trả tổng ước tính sai. Budget VND chỉ so sánh với actual lifetime/estimate VND đủ dữ liệu.
- Actual của task đã ẩn vẫn giữ; project đã ẩn không vào view thường. Khoảng lọc inclusive, empty/error states và pagination project 20 dòng/trang.
- Cảnh báo phân loại phòng ban/role hiện tại chưa có snapshot lịch sử. JOIN rate/primary role chọn một record, không JOIN membership vào aggregate để tránh double-counting.
- Chart.js 4.5.1 local tại `js/vendor/chartjs-4.5.1/`, MIT license, npm SHA-512 integrity đã xác minh; hash asset ghi trong vendor README. Không runtime CDN.
- Polling GET tại `pm_ajax.php?op=pmcosts`, module nội bộ `modules/ajax/pmcosts.module.php`, 60 giây khi tab hiện; abort khi ẩn, giữ bộ lọc và số liệu cũ khi lỗi. Endpoint legacy ajax.php không thay đổi.
- Permission kiểm tra ở service/list/detail/polling; Admin toàn tenant, PM chỉ dự án phụ trách, HR/Employee fail closed. Session user active và tenant được xác minh lại ở endpoint JSON; no-store và allowlist cố định.
- Smarty và JSON escape an toàn, refresh dùng textContent; estimate valuation date cập nhật cả khi polling sang ngày mới. Bảng hoạt động khi chart không tải được; sửa canvas overflow trên mobile.

### Database

Backup local trước seed đã tạo trong `.local/` được Git ignore. Seed `003_seed_pm_cost_permissions.sql`
cộng thêm permission `pm.costs.view` và grant Admin/PM theo tenant, chạy idempotent hai lần PASS.
Không migration schema mới, không rewrite legacy, không production. Fixture kiểm thử rollback.
Rollback ứng dụng: ẩn route/menu costs, để nguyên grant không dùng; không xóa dữ liệu/bảng.

### Acceptance — đủ 6 nhóm

PHP dùng `.tools/php83/php.exe`:

| Nhóm | Lệnh / bằng chứng | Kết quả |
| --- | --- | --- |
| 1. Actual / estimate / OT / rate | `tests/pm_costs_smoke.php`: actual DECIMAL, cost đã lưu không đổi khi rate_snapshot thay đổi, OT, valuation date, lifetime vs range, missing/mixed estimate | PASS |
| 2. Filter / empty / pagination / JOIN | Cùng smoke: ngày invalid/reversed, input array, empty range, inclusive date, page 2, thêm membership/multi-role không nhân đôi chi phí | PASS |
| 3. Permissions / IDOR / polling | Cùng smoke + `tests/pm_costs_http_smoke.php`: Admin/PM list/detail, HR/Employee denied, foreign tenant, live HTTP page/polling, auth/bad filters/allowlist/no-store/GET-only | PASS |
| 4. DECIMAL / soft delete / currency | Cùng smoke: tiền lớn `1234567890123958.03`, task lịch sử, project hidden, mixed currency ở total/project/task/user/department/role/date/lifetime, homogeneous subrange, USD không so budget VND | PASS |
| 5. Render / browser / polling | `tests/smoke_pm_costs.php`; `playwright-cli -s=pmphase6 run-code --filename=../tests/pm_costs_browser.js` từ `.local/`, server preview localhost:18767, dữ liệu giả | PASS |
| 6. Regression / lint / review | Bộ regression Phase 2–5 bên dưới, PHP lint, Node syntax check, diff check và security review | PASS |

Nhóm 5 kiểm tra desktop 1440×1000, mobile 390×844, Chart.js version, không page overflow,
polling 60s dừng/resume qua visibilitychange, giữ filter, mixed-money chart không vẽ sai,
escaping refresh, giữ số liệu khi 403 và table fallback khi asset chart bị chặn.
Screenshot đã xem trong `.local/phase6-desktop.png`, `.local/phase6-mobile.png`; không công bố dữ liệu thật.
Console lỗi 403/asset abort là tình huống cố ý trong test; favicon localhost 404 không thuộc lỗi JS ứng dụng.

- `tests/pm_phase6_permissions.php --apply` hai lần, sau đó không --apply: PASS.
- Regression: `pm_auth_rbac_smoke.php`, `pm_rates_smoke.php`, `pm_departments_smoke.php`, `pm_projects_smoke.php`, `pm_phase5_migration.php`, `pm_phase5_audit_permissions.php`, `pm_timesheets_smoke.php`, `pm_timesheet_window_smoke.php`, `pm_audit_smoke.php`, `smoke_pm_timesheets.php`, `smoke_pm_audit.php`, `smoke_pm_admin.php`, `smoke_pm_users.php`, `smoke_pm_projects.php`, `smoke_pm_dependencies.php` trong tests/: PASS.
- PHP `-l`: admin.php, pm_ajax.php, pmcosts DAO/service, pm controller thay đổi, costs controller/AJAX và bốn PHP test Phase 6: PASS.
- `node --check js/pmcosts.js`, `node --check tests/pm_costs_browser.js`, `node --check js/vendor/chartjs-4.5.1/chart.umd.js`: PASS.
- `git -c safe.directory=D:/derasoft-pm diff --check` và kiểm tra whitespace cho file mới: PASS.

### Security review / giới hạn

Rà soát theo skill code-review: prepared filters, role/tenant/project boundaries, active session,
fixed AJAX allowlist, GET-only/no-store, HTML/JSON escaping, DOM textContent, currency guards,
consistent snapshot và không JOIN nhiều membership/role làm tăng số tiền. Không phát hiện lỗi còn mở trong phạm vi đã kiểm tra.
Chưa UAT bằng thao tác tài khoản thật; browser dùng synthetic preview và HTTP dùng session test local.
Chưa chạy benchmark dữ liệu lớn hoặc test concurrency nhiều connection; không tuyên bố performance/production-ready.

### Next Step

UAT local `admin.php?op=pmcosts` bằng Admin/PM: bộ lọc, project detail, actual vs lifetime,
estimate và missing rate/currency warnings, responsive/polling. HR tài chính vẫn chưa mở.
Chờ người dùng cho phép commit/push/merge/deploy; không tự chuyển Phase 7.

### Phase 5 — sửa lỗi trang chấm công Forbidden (2026-10-02)

Người dùng phát hiện `admin.php?op=pmtimesheets` trả Forbidden. Controller gọi
`requirePermission()` trước khi khởi tạo `$pmAccess`, khiến kiểm tra quyền fail closed
ngay cả với tài khoản được cấp quyền. Đã khởi tạo PmAccess trước kiểm tra quyền;
không bỏ kiểm tra permission, ownership hoặc tenant.

Bổ sung regression HTTP trong `tests/pm_costs_http_smoke.php` cho trang chấm công
bằng session Admin và tài khoản không có role chi phí. Test tái hiện HTTP 403 trước sửa,
sau sửa trả HTTP 200 và render lịch sử chấm công ở cả hai session.

Lệnh `.tools/php83/php.exe` với `tests/pm_costs_http_smoke.php`,
`tests/pm_timesheets_smoke.php`, `tests/pm_timesheet_window_smoke.php`,
`tests/smoke_pm_timesheets.php`: PASS. PHP `-l` controller và HTTP test: PASS.
`git -c safe.directory=D:/derasoft-pm diff --check`: PASS (chỉ cảnh báo LF/CRLF).
Chưa xác nhận UAT trên session trình duyệt của người dùng tại cổng 8088.
Giữ nguyên thay đổi Phase 6; chưa commit/push/merge/deploy.

### Tiếp tục VERIFY / chuẩn bị UAT — 2026-10-02

- Kiểm tra các controller PM: PmAccess được khởi tạo trước requirePermission.
- Mở rộng `tests/pm_costs_http_smoke.php`: chấm công Admin/tài khoản không có role chi phí HTTP 200;
  ID không tồn tại HTTP 403; Admin Audit Viewer HTTP 200, filter entity_type sai HTTP 400.
- `.tools/php83/php.exe tests/pm_costs_http_smoke.php`, `tests/pm_audit_smoke.php`,
  `tests/smoke_pm_audit.php`: PASS. Test không thay đổi dữ liệu nghiệp vụ tồn tại.
- Bổ sung `docs/UAT_PHASE5_6.md`: checklist theo role, OT/khóa 3 ngày/audit,
  actual/estimate/currency/scope/polling và kết quả chưa kiểm tra để người dùng nghiệm thu.
- Chưa xác nhận UAT trên session thật tại cổng 8088; không chuyển Phase 7 hoặc push/merge/deploy.

### Chuẩn bị Phase 7 — 2026-10-02

Người dùng yêu cầu tiếp tục BUILD. Phase 6 đã hoàn tất BUILD/automated VERIFY;
đã chuẩn bị `docs/PHASE7_PLAN.md` cho phân bổ nguồn lực/overbooking theo PLAN_FINAL.
Status PLAN, chờ duyệt phạm vi quyền, ngưỡng ngày, overlap và lịch sử trước BUILD
theo quy trình AGENTS.md. Chưa tạo nhánh Phase 7, chưa migration/seed hoặc thay đổi ứng dụng.
Các thay đổi Phase 6 và bản sửa Forbidden vẫn được giữ nguyên; UAT Phase 5–6 chưa xác nhận.

## Phase 7 — Resource Allocation & Overbooking

### Status — 2026-10-03

Tiếp tục sau yêu cầu tạm dừng ngày 02/10. BUILD và automated VERIFY trên
`feature/pm-phase7-resource-allocation`; giữ thay đổi Phase 6 và bản sửa Forbidden.
Plan đã được duyệt, có hai điểm làm rõ capacity time-bound và overlap giờ trước BUILD.
Chưa commit/push/merge/deploy; chưa xác nhận UAT Phase 5–7.

### Completed

- DAO/service prepared, controller/Smarty/menu `pmallocations`, polling qua allowlist PM.
- Allocation theo tuần, task/assignee/membership active cùng tenant; CRUD và soft delete/audit transactional.
- Capacity hữu hạn/vô hạn, overlap đối xứng trong hasOverlappingCapacity(), Admin quản lý;
  daily theo ngày, weekly theo thứ Hai; không dùng threshold OT.
- Overlap nửa mở, hai đầu bắt buộc có cùng nhau, duration khớp giờ, không ca qua đêm;
  thiếu lịch chi tiết không khẳng định không trùng. Quá tải/trùng là cảnh báo, vẫn được lưu.
- Khóa sentinel user rồi các ngày theo thứ tự cố định; project khóa theo ID cố định;
  membership/task mutation đọc current dưới khóa. Tổng tải giữ cả historical task/project.
- Admin toàn tenant, PM dự án phụ trách, Employee chỉ đọc own, HR chưa có grant riêng.
  PM nhận tổng tải/busy ngoài scope nhưng không chi tiết; gợi ý không tự sửa assignment.
- Responsive, escaping, POST op/CSRF theo router legacy, polling 60s dừng khi tab ẩn,
  lỗi giữ dữ liệu cũ. Audit Viewer Admin thêm filter allocation/capacity; PM/HR vẫn giữ scope cũ.
- Migration 005/seed 004 đã chạy idempotent hai lần trên local sau backup ngày 02/10;
  không schema legacy/production. Rollback ẩn feature, giữ dữ liệu/bảng/grant.

### Acceptance và lệnh kiểm thử

PHP: `.tools/php83/php.exe`, fixture transaction rollback.

| Nhóm | Lệnh / bằng chứng | Kết quả |
| --- | --- | --- |
| 1. CRUD/validation/audit | tests/pm_allocations_smoke.php | PASS |
| 2. Capacity/tuần/overlap hiệu lực | Cùng smoke: hữu hạn/vô hạn, inclusive biên, excludeId, soft delete, midweek, đúng bằng/vượt ngưỡng, tuần qua năm | PASS |
| 3. Trùng giờ/ngoài scope | Cùng smoke: partial hai phía, containment hai chiều, exact, liền kề hai đầu, separate, NULL, chuyển ngày/user, tổng tải ngoài project | PASS |
| 4. Scope/CSRF/history/escaping | Cùng smoke + tests/pm_allocations_http_smoke.php: page/poll/detail/suggestions/session tenant, rejected POST và no-store | PASS |
| 5. UI/gợi ý/polling | tests/smoke_pm_allocations.php; playwright-cli -s=pmphase7 run-code --filename=../tests/pm_allocations_browser.js từ .local | PASS ngày 02/10 |
| 6. Lock/regression | tests/pm_allocations_concurrency.php: hai connection, input khóa đảo thứ tự, chờ rồi tiếp tục sau nhả khóa; regression Phase 2–6 | PASS |

Browser dùng synthetic preview local: desktop 1440×1000/mobile 390×844, không page overflow,
suggestions và refresh XSS-safe, route/CSRF sau refresh, filter/timer/visibility/error/empty.
Đã xem screenshot .local/phase7-desktop.png và phase7-mobile.png (dữ liệu giả).
Console 403 chủ ý khi test lỗi, favicon 404; không pageerror trong test.

Regression đã chạy ngày 02/10: pm_auth_rbac_smoke, pm_rates_smoke, pm_departments_smoke,
pm_projects_smoke, pm_phase5_migration, pm_phase5_audit_permissions, pm_timesheets_smoke,
pm_timesheet_window_smoke, pm_audit_smoke, smoke_pm_timesheets, smoke_pm_audit, smoke_pm_admin,
smoke_pm_users, smoke_pm_projects, smoke_pm_dependencies, pm_phase6_permissions,
pm_costs_smoke, pm_costs_http_smoke, smoke_pm_costs: PASS.

### Security review / giới hạn

Rà soát prepared input, role/tenant/project/owner, current reads sau mutex, transaction/audit,
CSRF và POST op, HTML escape/DOM textContent, fixed AJAX allowlist/GET-only/no-store.
Không phát hiện vấn đề bảo mật còn mở trong các đường dẫn đã rà soát.
Concurrency test xác minh mutex thực bằng hai connection; chưa kiểm thử hai mutation nghiệp vụ
đầy đủ đồng thời, benchmark tải lớn hoặc UAT session người dùng. Không tuyên bố production-ready.
List giới hạn 1000 allocation (cảnh báo), 1000 task chọn và 200 capacity mới nhất;
hướng dẫn nghiệm thu tại docs/UAT_PHASE7.md. Không tự chuyển Phase 8.

### Resume verification — 2026-10-03

Chạy lại `.tools/php83/php.exe` cho pm_phase7_migration, pm_allocations_smoke,
pm_allocations_concurrency, pm_allocations_http_smoke, smoke_pm_allocations,
pm_audit_smoke, smoke_pm_audit, pm_costs_smoke và pm_costs_http_smoke trong tests/: PASS.
PHP `-l` 14 file DAO/service/controller/router/test liên quan Phase 7: PASS.
`node --check js/pmallocations.js` và `node --check tests/pm_allocations_browser.js`: PASS.
`git -c safe.directory=D:/derasoft-pm diff --check`: PASS (cảnh báo chuyển LF/CRLF).
Browser không chạy lại ngày 03/10 vì không thay đổi UI/JS từ lần browser PASS ngày 02/10.
Hoàn tất DB_SCHEMA, PORTFOLIO, PROGRESS và UAT_PHASE7; giữ nguyên giới hạn nêu trên.

## Quy trình kiểm thử mới / Phase 8 increment — 2026-10-03

Người dùng yêu cầu tự kiểm thử chức năng/regression/code/security sau mỗi phase;
chỉ UAT thủ công khi toàn bộ local hoàn tất. Không chờ UAT từng phase để tiếp tục local,
không tự production hoặc ghi UAT PASS. Đã lưu trong AGENTS.md.
Chạy lại đầy đủ 24 PHP test Phase 2–7 hiện có: PASS. Dùng code-review rà soát
route/controller/DAO/service và quyền, mutex, CSRF, escaping; không phát hiện vấn đề còn mở
trong phạm vi đã xem, không coi đây là cam kết không còn lỗi.

### Phase 8: báo cáo / XLSX

BUILD trên `feature/pm-phase8-reports-excel`, giữ toàn bộ thay đổi Phase 6–7.
Plan tại docs/PHASE8_PLAN.md. Đã xây dựng prepared DAO/service, route `pmreports`,
menu/Smarty và export POST có CSRF, role/tenant/project/owner kiểm tra phía backend.

- Báo cáo giờ cá nhân/nhóm theo ngày inclusive, pagination 50 dòng, history task/project,
  soft-deleted timesheet bị loại; không trả cost/rate cho báo cáo giờ.
- Employee/HR chỉ giờ cá nhân; PM dự án quản lý; Admin tenant. Cost report reuse
  PmCostService, pm.costs.view và role Admin/PM; currency null/warnings, actual/lifetime/estimate tách rõ.
- XLSX local bằng PhpSpreadsheet hiện có, TYPE_STRING cho mọi ô (formula-like input không thực thi),
  tiền giữ DECIMAL text; limit 5000 dòng/dự án, vượt thì từ chối thay vì cắt.
  Filename cố định, MIME chuẩn/no-store, file tạm xóa trong finally; không export mật khẩu/hash.
- Backup local trước seed 005; grant report view/export cho 4 role tenant-scoped,
  seed chạy idempotent hai lần, không mở HR cost bằng report permission.

Các lệnh `.tools/php83/php.exe tests/pm_phase8_permissions.php`,
`tests/pm_reports_smoke.php`, `tests/pm_reports_http_smoke.php`, `tests/smoke_pm_reports.php`: PASS.
Smoke có round-trip XLSX, formula-like strings, DECIMAL lớn, mixed currency, own/PM/HR/tenant,
invalid filters, 8192 fixture dòng để test pagination/limit, history và rollback.
HTTP test page/filter/IDOR/cost denial, CSRF và download XLSX MIME/no-store: PASS.
`tests/pm_reports_smoke.php --preview` tạo preview dùng fixture trong .local được ignore.
`playwright-cli -s=pmphase8 run-code --filename=../tests/pm_reports_browser.js` từ .local:
PASS desktop/mobile/escaping/filter/download route+CSRF. Browser dùng synthetic preview/mock download;
HTTP test có download thật với session local. Không thay UAT người dùng.

### Import: chưa BUILD / quyết định đang chờ

Kiểm tra information_schema local xác nhận dc_users ENGINE=MyISAM.
Không thể ghi nhân sự atomic bằng transaction hiện tại; đổi engine không thuộc quy tắc ADD COLUMN/CREATE TABLE.
Đã hỏi người dùng chọn preview/staging theo quy tắc hiện tại hoặc duyệt ngoại lệ InnoDB local sau backup.
Chưa đổi engine, chưa upload/import/staging/migration nhân sự, chưa tuyên bố Phase 8 hoàn tất.
Phần báo cáo không phụ thuộc quyết định này. Chưa commit/push/merge/deploy; production chỉ sau
người dùng nghiệm thu toàn bộ local và cho phép triển khai.

Sau increment báo cáo đã chạy đầy đủ 28 PHP test hiện có (24 Phase 2–7 và 4 report): PASS.
PHP `-l` 9 file route/controller/DAO/service/test report: PASS;
`node --check tests/pm_reports_browser.js` và `git diff --check`: PASS (cảnh báo LF/CRLF).
Rà soát code/security report: prepared filters, role/tenant/owner, financial redaction,
POST op/CSRF, escaping, explicit XLSX text, download headers và cleanup tạm;
không phát hiện vấn đề còn mở trong đường dẫn report đã kiểm tra.

### Phase 8 — import preview/staging increment (2026-10-03)

Tiếp tục BUILD phần độc lập, giữ MyISAM và chưa apply nhân sự. Route `pmimports` Admin-only:
mẫu XLSX, upload/preview, lỗi theo dòng và staging toàn batch hợp lệ; UI luôn ghi chưa áp dụng.
Chỉ đề xuất tạo mới username/email chưa có trong tenant, role EMPLOYEE; không update tài khoản,
không password/hash hoặc quyền Admin/PM/HR. Cần revalidate tất cả khi BUILD apply sau này.

Migration 006/seed 006 sau backup local: thêm import_logs/staging InnoDB, không schema legacy.
Lần đầu gặp reserved word row_number; sửa file trước khi staging table được tạo thành source_row,
sau đó chạy idempotent hai lần PASS. Không dữ liệu cũ bị sửa hoặc xóa.
Batch staging/audit transactional; log/audit metadata không chứa PII, staging payload Admin-only.
Uploads ngoài webroot qua PHP temp, controller xóa trong finally; không giữ workbook.

Security review: role+active actor/tenant, CSRF/POST op, is_uploaded_file, extension/MIME/2MB,
ZIP entry/count/expanded-size/ratio budgets, XML DTD/ENTITY/null/external, macro/embedded content,
1 sheet/6 header/500 row, cell formula, row identity/department/role và HTML escaping.
Không phát hiện vấn đề còn mở trong các đường dẫn đã rà soát; không thay penetration test độc lập.

- `.tools/php83/php.exe tests/pm_import_migration.php --apply` hai lần: PASS.
- `tests/pm_import_smoke.php`: PASS Admin/tenant, valid preview/stage, duplicate/existing identity,
  bad headers/rows, macro/external/DTD/formula/ZIP/size/row limits; lỗi chèn ở staging dòng thứ hai
  rollback log và tất cả rows. dc_users không đổi; fixture rollback.
- `tests/pm_import_http_smoke.php`: PASS multipart upload thực, template download, Admin/non-Admin,
  CSRF/extension/no-store và không staging workbook lỗi. Không ghi dữ liệu nghiệp vụ thử tồn tại.
- `tests/smoke_pm_imports.php`: PASS escaping, upload/CSRF, trạng thái chưa áp dụng.
- Browser `playwright-cli -s=pmimports run-code --filename=../tests/pm_import_browser.js`
  từ .local: PASS desktop/mobile, no page overflow/XSS, fields và chưa áp dụng.
  Preview synthetic, HTTP test kiểm tra upload thật; screenshots .local đã xem. Favicon 404 không pageerror.
- Chạy đầy đủ 32 PHP test Phase 2–8 hiện có: PASS; PHP -l 9 file liên quan imports: PASS;
  node --check tests/pm_import_browser.js và git diff --check: PASS (LF/CRLF warnings).

Admin Audit Viewer có entity import/action stage; PM/HR tiếp tục chỉ timesheet scope cũ.
Chưa đổi engine/apply nhân sự, chưa coi Phase 8 hoàn tất; quyết định database đã hỏi vẫn chưa chốt.
Không dừng chờ UAT từng phase, không commit/push/merge/deploy hoặc truy cập production.

### Quyết định Apply / kiểm tra UNIQUE email — 2026-10-03

Người dùng quyết định chính thức không đổi ENGINE dc_users kể cả local.
Apply thiết kế lại: INSERT từng dòng, duplicate-key 1062, per-row outcome resumable,
import_logs.status=in_progress và khóa chống hai Admin cùng batch. Chưa code Apply.

Read-only `.tools/php83/php.exe tests/pm_import_email_index_audit.php` trên
derasoft_pm_local: dc_users MyISAM, email VARCHAR(50) nullable, utf8mb4_unicode_ci;
index email NON_UNIQUE=1, không UNIQUE(email)/UNIQUE(store_id,email).
9 rows, 0 NULL/empty, 0 duplicate groups global hoặc trong tenant. Không xuất giá trị email.
Không truy cập production; trạng thái production chưa xác minh.

Đã chuẩn bị migration DRAFT `007_add_pm_users_email_unique.sql` cho
UNIQUE(store_id,email) theo multi-tenant, giữ nguyên index email thường, ENGINE và dữ liệu.
Idempotent với definition chính xác; tên index xung đột hoặc duplicate thì native ALTER thất bại,
không tự sửa dữ liệu. MyISAM ALTER có thể rebuild/lock bảng lõi; cần backup/cửa sổ bảo trì.
Chờ duyệt riêng theo yêu cầu người dùng, chưa chạy migration, chưa tuyên bố SQL runtime PASS.

PHASE8_PLAN nêu rõ DB-key scope, NULL policy và thiết kế per-row journal cùng advisory lock
connection-owned để xử lý crash/stale in_progress mà không nhả khóa worker còn chạy.
Không SELECT-check-then-INSERT khi Apply; preview duplicate chỉ là phản hồi trước ghi.
Vấn đề gián đoạn giữa INSERT và log được replay bằng UNIQUE, không giả định transaction MyISAM.
Không cấp role cho account trùng ngoài batch; provenance phải được xác minh nếu hoàn tất side effects.

Đã sửa preview email max 50 cho khớp schema thực, thêm regression email dài quá cột.
Import service/HTTP smoke và lint audit script: PASS; diff check PASS (LF/CRLF warnings).
Giữ nguyên report/export 5000 rows, explicit strings, CSRF/no-store và HR/Employee không tài chính.

### Phase 8 — Apply BUILD / automated VERIFY — 2026-10-04

Người dùng đã duyệt riêng migration UNIQUE email và yêu cầu tiếp tục BUILD.
Nhánh `feature/pm-phase8-reports-excel`; giữ nguyên mọi thay đổi Phase 6–8 hiện có.
Phase 8 đã BUILD / automated VERIFY trong phạm vi và giới hạn kiểm thử dưới đây;
không ghi UAT PASS hoặc production-ready.

Database local đã backup bằng `.tools/php83/php.exe .local/backup-phase5.php` trước DDL.
`.tools/php83/php.exe tests/pm_email_unique_migration.php --apply` chạy hai lần PASS:
full UNIQUE(store_id,email) đã tồn tại; dc_users vẫn MyISAM, 9 users, không sửa dữ liệu.
`.tools/php83/php.exe tests/pm_import_results_migration.php --apply` hai lần PASS:
migration 008 chỉ CREATE TABLE import_results InnoDB với UNIQUE tenant/batch/source_row.
Không kết nối production; không đổi ENGINE, DROP, xóa dữ liệu hoặc sửa index cũ.

Apply Admin-only POST/CSRF, runtime UNIQUE guard, INSERT trực tiếp và catch 1062;
lookup duplicate chỉ sau lỗi INSERT để xác minh dấu nguồn, không SELECT-check-then-INSERT.
import_logs.status=in_progress và GET_LOCK connection-owned theo DB/tenant/batch giữ toàn lượt;
worker khác nhận conflict, resume flag stale chỉ khi lấy được lock; finally release.
Journal intent/token/hash payload trước INSERT, outcome applied/skipped_duplicate/failed,
reason/user_id/attempt/timestamp từng dòng; pending replay, failed retry, completed không ghi lại.
Crash sau INSERT hoặc lỗi transaction role/audit: chỉ hoàn tất tài khoản có provenance khớp,
không tạo tài khoản thứ hai hoặc cấp role cho tài khoản trùng ngoài batch.
Các dòng lỗi không dừng dòng tiếp theo; kết quả partial/completed và số lượng được hiển thị.

Tài khoản mới inactive, legacy password NULL, hash ngẫu nhiên không tiết lộ; role chỉ EMPLOYEE.
Username chưa UNIQUE DB: hậu kiểm xung đột sau INSERT, giữ inactive và báo failed để quản trị
viên xử lý, không tự sửa account cũ. Admin có form cấp mật khẩu 12–72 byte cho applied/owned/
inactive row, CSRF + batch lock + password_hash; sau đó kích hoạt riêng trong Quản lý nhân sự.
Không log/echo password/hash; Audit Viewer Admin có filter apply/provision_password.
PM/HR không được mở scope import. Log lỗi PM users được đổi sang thông báo generic để tránh
exception duplicate-key mới đưa email vào log. Không thêm workflow duyệt timesheet.

| Nhóm acceptance Phase 8 | Kết quả và bằng chứng |
| --- | --- |
| 1. Báo cáo giờ/filter/history/pagination | PASS `pm_reports_smoke.php`; fixture rollback |
| 2. Role/tenant/IDOR/CSRF/no-store | PASS reports/import service + HTTP; Admin/PM/HR/Employee gates |
| 3. XLSX/Unicode/formula/DECIMAL/currency | PASS report round-trip và currency guards; 5000-row rejection |
| 4. Import/row errors/resume/concurrency | PASS preview/upload/ZIP/headers/formulas; Apply mirror crash/retry/1062/immutable payload/independent rows; native 1062 và hai real connections |
| 5. Smarty/browser desktop/mobile | PASS import escaping, không overflow, Apply/resume/detail/credential POST và CSRF; report download/browser đã PASS ở increment trước |
| 6. Regression/lint/code/security | PASS 36 scripts, lint các file Apply liên quan, node check và diff check |

Lệnh/kết quả:

- `./tests/pm_regression.ps1`: PASS 36 script Phase 2–8; không chạy migration trong runner.
- `.tools/php83/php.exe tests/pm_import_apply_smoke.php`: PASS INSERT/1062, inactive/hash/
  DECIMAL, interruption sau INSERT và transaction role/journal, retry, duplicate không grant,
  immutable payload, lỗi dòng độc lập, Admin/tenant/IDOR/UNIQUE gates và credential ownership.
  Hai kết nối MySQL thật kiểm tra lock busy và connection close tự release. Fixture rollback.
- `.tools/php83/php.exe tests/pm_import_native_duplicate.php`: native dc_users INSERT trả
  1062, engine/count không đổi; có thể tạo khoảng trống auto-increment, không thêm nhân sự.
- `.tools/php83/php.exe tests/pm_import_http_smoke.php`: PASS multipart thật, template,
  Admin/non-Admin/wrong tenant, Apply/resume/detail/credential CSRF và IDOR, no-store.
- `.tools/php83/php.exe tests/smoke_pm_imports.php --preview`: PASS Smarty/escaping/forms.
- `playwright-cli -s=pmimports run-code --filename=../tests/pm_import_browser.js` từ `.local`:
  PASS desktop 1440/mobile 390, không page overflow/XSS/script error, các form POST đúng fields.
  Đây là synthetic fixture; screenshot đã xem. Favicon 404, không ảnh hưởng chức năng.
- `.tools/php83/php.exe -l <file>` các DAO/service/controller/tests Apply/import và PM users:
  PASS. `node --check tests/pm_import_browser.js`: PASS.
- `git -c safe.directory=D:/derasoft-pm diff --check`: PASS; chỉ warnings LF/CRLF.
- `.tools/php83/php.exe tests/pm_import_email_index_audit.php`: tenant_email_unique=true,
  MyISAM, 9 users, không duplicate/NULL/empty. Không in giá trị email.

Security/code review đã trace upload/temporary cleanup, prepared SQL, actor active/Admin,
tenant/detail/mutations, CSRF/no-store, token provenance, duplicate không cập nhật tài khoản
ngoài batch, mutex/resume, role/audit rollback và password provisioning/escaping.
Không còn defect xác định trong phạm vi rà soát; không thay penetration test độc lập.

Giới hạn: successful Apply/crash dùng TEMPORARY InnoDB user mirror để không để lại fake user
trong MyISAM. Native duplicate rejection và advisory lock đã kiểm tra thật, nhưng chưa chạy
successful MyISAM INSERT rồi kill process trên bản DB cô lập. Không coi mirror rollback là
transaction/physical durability của MyISAM. Credential write và audit cũng không atomic;
tài khoản giữ inactive khi lỗi và có thể cấp mật khẩu lại. Username conflict cần quản trị xử lý.
Các trường hợp này được ghi trong PHASE8_PLAN/DB_SCHEMA/portfolio; UAT toàn local vẫn chờ
người dùng ở cuối dự án. Không tự commit/push/merge/deploy.

## Phase 9 — PLAN — 2026-10-04

Người dùng yêu cầu tiếp tục sau Phase 8. Đã đọc AGENTS/PROGRESS/PLAN_FINAL và kiểm tra
code hiện có để lập `docs/PHASE9_PLAN.md` (UI/UX, performance, security).
Xác định qua đọc code: trang nhân sự gọi roles từng user (N+1), một số input thiếu label
riêng; cần browser matrix và EXPLAIN để đánh giá tiếp, chưa tuyên bố benchmark/audit PASS.
Plan nêu phạm vi màn hình, batch role lookup, bảo mật/session, 6 nhóm acceptance và giữ
nghiệp vụ/engine/schema đã chốt. ADD INDEX mới nếu cần phải xin duyệt riêng.
Chưa BUILD Phase 9 hoặc đổi branch; chờ duyệt plan theo quy trình AGENTS, không chờ UAT.
Không chạy test lại cho thay đổi chỉ tài liệu này; không commit/push/merge/deploy.

## Phase 9 — BUILD / automated VERIFY — 2026-10-04

Người dùng duyệt UI/accessibility và xác nhận thiết kế batch role: một prepared query lấy
toàn bộ role theo tenant/user IDs trên trang; PHP nhóm theo user, chỉ is_primary=1 quyết
định role chính. Không suy ra từ JOIN hoặc thứ tự kết quả. Multiple-primary fail closed
và hiển thị cảnh báo. Test khớp chính xác rows/primary của 20 user có 2–3 role: N=20 → 1
query, một user → 1, empty → 0; không tạo fake personnel trong dc_users MyISAM.

Đã bổ sung navigation theo quyền, current page/skip link, label/focus/live messages,
vùng cuộn bảng và CSS local. Giữ nghiệp vụ/rate_snapshot/currency/DECIMAL/history/locks.
PM login/logout có CSRF, logout POST-only, session kiểm tra actor active/tenant, PM không
mở KCFINDER. Input array không hợp lệ bị chặn. Inline CSS/confirmation được chuyển local
sau inventory rồi mới bật CSP; headers no-store/nosniff/referrer/frame áp dụng cả lỗi.
Polling 60s dừng khi tab ẩn, giữ dữ liệu cũ khi lỗi; 401/403 dừng hẳn đến khi tải lại.

### Lệnh và kết quả thật

- `./tests/pm_regression.ps1`: PASS 41 scripts (Phase 2–9); runner không chạy migration.
- `.tools/php83/php.exe tests/pm_explain_smoke.php --report`: PASS 62 query shapes / 6
  families; không type=ALL trong run local, có temporary/filesort. Không đề xuất/chạy index,
  không đổi schema/ENGINE. Chi tiết [PHASE9_EXPLAIN.md](PHASE9_EXPLAIN.md).
- `tests/pm_ui_http_smoke.php`: PASS 18 authenticated Admin/Employee route cases, menu,
  headers/CSP, malformed array, search SQLi/XSS, session/tenant, negative login/logout CSRF.
  PM/HR authenticated HTTP chưa có actor active local; không ghi PASS cho phần này.
- `tests/pm_session_guard_smoke.php`: PASS active/inactive/deleted/missing/cross-tenant/
  revoked actor trên temporary mirror; core users không thay đổi.
- `playwright-cli -s=pmphase9 run-code --filename=../tests/pm_ui_browser.js` từ `.local`:
  PASS 13 synthetic fixtures × 360/390/768/1440px, labels, first-tab skip/main focus,
  table regions, không page overflow/inline code/CSP violation. Screenshot đã chụp/xem.
  200% text scaling PASS, không phải browser/OS zoom thật.
- `pm_costs_browser.js`, `pm_allocations_browser.js`, `pm_reports_browser.js`,
  `pm_import_browser.js`: PASS sau bật CSP, chart/table fallback, polling/visibility/
  expiry, export/import forms. `pm_confirmation_browser.js`: PASS handler cancel/accept
  bằng mocked confirm result, intercepted synthetic POST; không gửi mutation vào app.
- PHP 8.3 `-l`: PASS 61 changed/new PHP files. `node --check`: PASS 10 JS files.
  `git -c safe.directory=D:/derasoft-pm diff --check`: PASS, chỉ cảnh báo LF/CRLF.

Code/security review đã trace entrypoints, tenant/permission/session, CSRF, escaping,
prepared batch SQL, currency/DECIMAL, upload/export/provenance và error logs. Không còn
defect được chứng minh trong phạm vi review; không thay penetration test độc lập.
Coverage và các ca chưa kiểm tra ghi ở [PHASE9_VERIFY.md](PHASE9_VERIFY.md).
Giới hạn Phase 8 native MyISAM crash window vẫn giữ nguyên. UAT toàn local chờ người dùng
ở cuối; không production-ready, không push/merge/deploy hoặc truy cập production.

Git delivery: thay đổi Phase 6–8 kế thừa trước quy tắc commit mới được tách thành checkpoint
local theo phase; Phase 9 có commit riêng sau VERIFY. Không rewrite lịch sử đã push;
config local dùng Tạ Trí Dũng / GitHub noreply, không stage secret/config/dump/cache/log/upload.

Checkpoint local đã tạo (không push): Phase 6 `b6ba02b`, Phase 7 `b942231`, Phase 8 `4fcf76c`.
`python .local/verify-checkpoints.py` đã chạy trên ba snapshot tách biệt: PASS lần lượt
19/24/36 regression scripts; config local chỉ sao chép trong thư mục `.local` bị ignore.
Không sửa source đang làm hoặc tạo fake core user. Plan Phase 10 tại PHASE10_PLAN.md chờ duyệt.

## Phase 10 — BUILD đang thực hiện — 2026-10-04

Người dùng duyệt test/UAT và bổ sung chuẩn bị deployment FileZilla; đã cập nhật plan,
tạo nhánh `feature/pm-phase10-test-release`. Không upload, chạy migration trên production,
push/merge/deploy hoặc truy cập production.

- Git baseline trước Phase 1: `3df33493da81f53e2fdc67d826998850812dbd2f`, predecessor
  first-parent của Phase 1 merge. `git diff --name-only --no-renames BASELINE` đã chạy:
  165 tracked paths, 75 runtime uploads. DEPLOY_DIFF_ALL/FILES/MANIFEST lưu đầy đủ và SHA256;
  `python tests/pm_deploy_manifest.py --check`: PASS. Regenerate khi runtime/staged files đổi.
- DEPLOY_LOG có version/ngày/commit-ref, migration 001–008 + seeds 001–006 theo phụ thuộc,
  preflight/schema đã tồn tại, cảnh báo compatibility migration 002 và MyISAM UNIQUE 007;
  config/license/env/cache/dump/uploads không upload, post-deploy smoke riêng và rollback
  ứng dụng không DROP/xóa data. Chỉ chuẩn bị tài liệu, production smoke NOT RUN.
- UAT_FINAL_LOCAL.md có checklist toàn PM cho người dùng, chưa tick UAT PASS.
- Sửa lỗi được chứng minh: GET logout link cũ trên projects không tương thích POST-only;
  raw project exception có thể lộ input trong log; muted dashboard contrast 4.39 thấp hơn
  ngưỡng 4.5, chuyển màu local rồi kiểm tra lại.
- `./tests/pm_regression.ps1`: PASS 41 scripts sau sửa logout/log. Smarty projects/admin,
  PHP lint files sửa/mới và JS syntax PASS. UI matrix 13 synthetic fixtures ở 4 viewport
  PASS; contrast sample bốn screen PASS, screenshot dashboard đã chụp/xem sau sửa.
- Firefox engine attempt: executable chưa cài, NOT COVERED. Real browser zoom/screen reader
  chưa chạy; text scaling không gọi là browser zoom.
- Backup local hiện hành đã tạo trong `.local` bị ignore. Setup `pm_phase10_fixture.php`
  bị MySQL từ chối CREATE DATABASE với user local hiện tại trước khi ghi fixture.
  Đã yêu cầu tạo DB rỗng `derasoft_pm_phase10_20261004` và grant riêng; không gửi mật khẩu.
  Test authenticated bốn role/CRUD và native MyISAM worker kill/resume đã chuẩn bị/lint,
  chưa chạy, không ghi PASS. Chi tiết PHASE10_VERIFY.md.

Phase 10 chưa hoàn tất, chưa commit theo quy tắc không commit phase BUILD dở. Tiếp tục các
test cô lập và cleanup sau khi DB test sẵn sàng, rồi regression/security review cuối và
commit local một lần. Current dc_users không tạo fake users, không đổi ENGINE/constraint.

## Phase 10 ? BUILD / automated VERIFY ho?n t?t ? 2026-10-05

DB local c? l?p ?? c? quy?n sau thao t?c kh?i ph?c root do ng??i d?ng th?c hi?n;
kh?ng d?ng DB hosting, kh?ng ??i config production ho?c ENGINE.

- `php tests/pm_phase10_fixture.php`: PASS schema/permission fixture c? l?p, gi? MyISAM/UNIQUE.
- `php tests/pm_phase10_native_crash.php`: PASS kill worker PHP sau native INSERT r?i resume;
  m?t t?i kho?n inactive, replay idempotent. Kh?ng ki?m tra crash mysqld/OS/m?t ?i?n.
- `playwright-cli -s=pm10-auth run-code --filename=../tests/pm_phase10_browser.js`: PASS
  36 ca authenticated 4 role, SID regeneration/logout v? project/task/9h timesheet/XLSX.
- `php tests/pm_phase10_verify_cleanup.php`: PASS 8h regular + 1h OT, workbook,
  phi?n c? b? revoke role 403/inactive 401; fixture soft delete v? t?i kho?n disabled;
  s? nh?n s? ngu?n v?n 9. DB c? l?p gi? schema/audit, kh?ng t? reset ?? ch?y l?i.
- `./tests/pm_regression.ps1`: PASS 41 scripts cu?i; UI synthetic 13 fixtures ? 4 viewport
  PASS sau s?a dashboard copy, screenshot ?? xem. Review code/security v? lint cu?i ??t.
- Manifest ??y ?? v? hash runtime ???c t?i t?o/ki?m tra tr??c commit; DEPLOY_LOG c?
  migration dependencies, protected exclusions, rollback v? post-deploy smoke ri?ng.

README/PORTFOLIO/PHASE10_VERIFY c?p nh?t theo b?ng ch?ng th?c t?. Firefox ch?a c?i,
zoom browser th?t/screen reader v? UAT ng??i d?ng ch?a ch?y. Chu?n b? commit local m?t l?n
tr?n `feature/pm-phase10-test-release`; kh?ng push/merge/deploy ho?c truy c?p production.
