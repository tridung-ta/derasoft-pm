# Deployment preparation log — FileZilla

## Phase 11 local candidate — 06/10/2026 (NOT DEPLOYED)

Current preparation: 0.11.0-phase11-local-rc1, branch
feature/pm-phase11-requirements, runtime commit 1cad212 (auth fix 7227906,
Bootstrap control states and interaction polish included). Earlier Phase 9b entries
below are historical; do not mix their ZIP/hash with the current manifest.
UAT: docs/UAT_PHASE11.md — user deferred remaining manual testing until after
deployment on 06/10/2026. Agent reran local verification (46 regression scripts,
14 browser suites PASS); evidence/gaps in docs/LOCAL_VERIFY_PHASE11.md.
This is not UAT PASS or Phase 11 merge/push/deploy authorization.
Update: user approved local merge and production preparation. On 06/10/2026,
`git merge --ff-only feature/pm-phase11-requirements` advanced local develop
from b0eca4c to e2f21dd with no merge commit/conflict. No push/upload/production
SQL was performed. Migration 009 production authorization remains separate.
Manifest/ZIP regenerated after Windows checkout normalization to match actual
transfer bytes. Use only this regenerated candidate, not an older ZIP/hash.
Current manifest: 86 runtime files including Inter and Bootstrap license/CSS;
all file SHA256 values verified against the transfer workspace. The ZIP was
rebuilt after the auth/Bootstrap changes, with inventory/CRC/per-file checks.
Never substitute the earlier 85-file candidate or the isolated auth patch for
this full Phase 11 candidate. Historical patch instructions below retain their
original scope and are not authorization to deploy Phase 11.

Mandatory schema prerequisite: database/migrations/009_add_pm_project_task_metadata.sql.
Only ADD nullable client_name/project_role/start_date/completed_at, no backfill.
Production requires separate review of table/column types, fresh affected-table
backup and maintenance before 009. The user's prior UI-only backup waiver does
not authorize skipping this new schema review. Execute complete versioned file
in ONE connection (PREPARE/EXECUTE cannot be split across phpMyAdmin requests).
Verify four expected nullable columns and unchanged existing rows before upload.
If any same-name column has a different definition, STOP; do not ALTER MODIFY.
Keep existing dc_users engine and config. Rollback old code, retain additive data.

ZIP is a reviewed local runtime delta from Phase 0, NOT an inspected live-host delta.
SQL excluded from web payload; never upload SQL/DB backup to public_html. All
previous maintenance/allowlist/verification gates still apply. No production PASS.

## UI follow-up patch — 06/10/2026

After reviewing production screenshots, prepared a three-file update for the D
sidebar brand, prominent logout and permission-derived overview workspace.
Upload ONLY css/pmui.css, templates/admin/pm-navigation.tpl.html and
templates/admin/pm.tpl.html, preserving paths. No SQL or config changes.
Operator still needs to upload and verify; agent has not accessed production.
User explicitly chose to waive a new backup for this rollout; this is not a claim
that rollback files exist. Full candidate archive must be rebuilt from the current
manifest; the earlier b0eca4c archive lacks this follow-up.

## Release candidate

| Field | Prepared value |
| --- | --- |
| Version | 0.10.0-phase9b-local-rc1 — local candidate, not a production release |
| Preparation date | 04/10/2026; Phase 9b packaging updated 06/10/2026, Asia/Saigon |
| Baseline before Phase 1 | `3df33493da81f53e2fdc67d826998850812dbd2f` — Phase 0 audit; first-parent predecessor of Phase 1 merge `c732a324` |
| Application base commit | `d4cde62` — Phase 9b Step 9; includes Phase 9 `df76268` and Phase 10 fixes. Per-file hashes identify packaged runtime bytes |
| Release commit/ref | `feature/pm-phase9b-ui-theme`; resolve `git rev-parse HEAD` after preparation commit and record the full hash before any approved upload |
| Production status | Operator uploaded to pm.dung.derasoft.com / dung_pm on 05/10/2026; maintenance enabled, smoke testing incomplete. Agent did not access production. |
| User UAT | User confirmed Phase 9b acceptance on 06/10/2026 for candidate cf21eff/runtime d4cde62; per-case/role evidence not supplied. Merge/push/deploy approval remains separate |

The preparation document belongs to the release commit itself, so its own hash is resolved
from Git rather than recursively embedded. The final response reports the created commit.
Before manual deployment fill: **actual commit: ______; deploy date/time: ______; operator: ______**.

## Phase 9b release gate — 06/10/2026

Update: user confirmed acceptance and explicitly approved local merge; --ff-only
advanced develop from 1dc761f to 624c085 on 06/10/2026. Source branch retained.
Original before screenshots still missing; no push/deploy approval inferred.
Windows checkout normalized some text to CRLF, so the local manifest and ZIP were
regenerated to match current bytes. Always run --check against the actual transfer
workspace after checkout; an older ZIP/hash must not be mixed with a fresh manifest.

This candidate adds all nine UI steps, the shared `css/pmui.css`, PM template/JS
changes and the approved read-only project-progress/weekly-timesheet aggregates.
It does not require a new Phase 9b SQL migration or changing the dc_users engine.
The historical 05/10 operator evidence below is retained; it is not evidence that
this new UI candidate has been deployed or manually accepted.

Inter must ship together with the stylesheet, preserving these exact relative paths:

- `assets/fonts/inter/InterVariable.woff2`
- `assets/fonts/inter/OFL.txt` (font distribution license)
- `css/pmui.css` resolves `../assets/fonts/inter/InterVariable.woff2`.

The manifest explicitly allowlists these two assets rather than the whole assets
directory. Run `python tests/pm_deploy_manifest_test.py`, regenerate/check the lists,
then create the local review artifact with `python tests/pm_deploy_manifest.py --check --package`.
The ZIP lives under ignored `.local/releases/`; it contains runtime files only,
preserves paths, and is verified against manifest SHA256 and ZIP CRC. It is a delta
from the recorded Phase 0 baseline, **not** a standalone fresh-install image or a
verified delta from the uninspected live host. Do not upload the archive itself or
perform blind directory synchronization. No production config, SQL, logs or uploads
are included. Reconcile the actual host/backup before any separately approved rollout.

Before merge: user's whole-flow [UAT_FINAL_LOCAL.md](UAT_FINAL_LOCAL.md) must be
reviewed and accepted. Before push/deploy: separate explicit approval, resolved commit,
fresh manifest check, source/DB backup and operator plan. Agent has not merged,
pushed, uploaded, executed production SQL or marked manual UAT PASS.

## Complete file lists

### Operator rollout evidence — 05/10/2026

User confirmed DB/source backups and maintenance blocking outside the allowed network.
001 and initial seed verified: four active roles/store 1, ADMIN 7 / EMPLOYEE 1.
002 approved separately: **backfill dựa trên xác nhận production chỉ có 1 store_id tại thời điểm 05/10/2026, không phải logic tổng quát**.
002 columns/tenant indexes, 18 permissions/49 grants and core MyISAM verified by supplied results.
003/004 and subsequent permission seeds verified. 005 was subsequently found absent;
operator imported it again after confirming all three tables missing, then supplied
three InnoDB tables and successful allocation/overlap/adjacent smoke results.
006/import grants and 007/008 verified through supplied results/operator confirmations.

**007 exception:** user explicitly waived benchmark before production execution.
Test-copy UNIQUE creation passed; ALTER duration and lock interval were not recorded.
Production UNIQUE, unchanged eight users/MyISAM were confirmed by operator; no lock-time
estimate is claimed. The benchmark procedure below remains guidance, with this recorded
exception for the current rollout. Existing test DB disposal is operator work.

Source was uploaded manually; working-tree fixes mean a commit alone does not identify
all deployed bytes. Preserve per-file SHA256 manifest and record actual transfer results.
Confirmed smoke: Admin routes open, project/task created, timesheet 9h (8+1 OT) changed to
3h (3+0 OT), audit before/after, allocations save and overlap/adjacent intervals.
Pending: costs after MariaDB fix, financial calculation with rate, XLSX export, four-role
authenticated checks, import behavior, HTTPS and final cleanup/reopening.
Isolation compatibility fix now requires **both classes/database/pmdb.class.php and
classes/services/pmcostservice.class.php** from the same current candidate.
No final production PASS or reopening approval is recorded.

Command run from repository root:

```powershell
git -c safe.directory=D:/derasoft-pm diff --name-only --no-renames 3df33493da81f53e2fdc67d826998850812dbd2f
python tests/pm_deploy_manifest.py
python tests/pm_deploy_manifest.py --check
```

- [DEPLOY_DIFF_ALL.txt](DEPLOY_DIFF_ALL.txt): complete baseline diff, no truncated list.
- [DEPLOY_FILES.txt](DEPLOY_FILES.txt): complete runtime file upload allowlist.
- [DEPLOY_MANIFEST.json](DEPLOY_MANIFEST.json): every path, Git status, upload/exclusion
  classification and SHA256 of runtime files. Check this again after any runtime edit.
- Git/development files, docs/tests and SQL are classified separately; do not upload the
  whole repository. The manifest describes this baseline, not an inspected production host.
  If the host does not match the baseline, reconcile its inventory/backup before applying
  a delta; do not assume missing libraries/files can be skipped.
- Deleted paths, if any, are review-only: no automatic remote deletion or legacy removal.

## Files never uploaded or overwritten

`includes/config.inc.php`, `license/license.inc.php` and the production `license/` directory;
`.env`/`.env.*`; `templates_c/` (including `.gitkeep`); real user `upload/`/`uploads/`;
database dumps, all `.sql`, `.bak`/`.bak-*`, archives, logs/cache/debug; `.git/`, `.local/`,
`.tools/`, editor folders, tests/docs/AGENTS/README. Third-party bundled Chart.js LICENSE.md
is a distribution notice, distinct from the protected application's license directory.

Migration SQL stays on the operator's computer and is executed manually through a DB tool,
not uploaded into the web document root. Keep production DB/domain/license values unchanged.
Do not use FileZilla directory synchronization, delete-remote actions, or “overwrite all”
against a mixed directory containing production configuration or uploads.

## Manual schema/seed order — Phase 2 through Phase 8

Backup production files + DB first; maintenance window, verified restore path, PHP 8.3 and
supported MySQL/JSON/DECIMAL/InnoDB/GET_LOCK capabilities. This is preparation only: the
agent has not inspected production schema or approved execution there.

| Order | File | Depends on / action |
| ---: | --- | --- |
| 1 | `database/migrations/001_create_pm_auth_rbac.sql` | Existing core dc_users; adds password_hash and RBAC tables. Preflight password_hash absent before the ALTER; don't rerun blindly. |
| 2 | `database/seeds/001_seed_pm_auth_rbac.sql` | 001, initial Phase 2 schema; seed roles/permissions/grants from existing tenant users before 002's permission backfill. Review ADMIN compatibility mappings. |
| GATE 002 — separate mandatory review, outside the sequential CREATE/ADD run | `database/migrations/002_create_pm_users_rates.sql` | STOP after 001 + initial seed 001. Complete the separate review below and record explicit approval before executing 002. Never include 002 in a batch of migration scripts. |
| 4 — only after GATE 002 passes and approved 002 is verified | `database/migrations/003_create_pm_projects_tasks.sql` | 001–002; projects, membership, tasks. |
| 5 | `database/migrations/004_create_pm_timesheets_audit.sql` | 001–003; settings/day locks/timesheets/audit. |
| 6 | `database/seeds/002_seed_pm_phase5_audit_permissions.sql` | 001–004 and tenant permissions; additive Admin/PM/HR audit grants. No approval workflow. |
| 7 | `database/seeds/003_seed_pm_cost_permissions.sql` | 001–004; Phase 6 has no schema migration, additive Admin/PM costs grant. |
| 8 | `database/migrations/005_create_pm_allocations.sql` | 001–004; allocations, time-bound capacity, mutation lock tables. |
| 9 | `database/seeds/004_seed_pm_allocation_permissions.sql` | 005 + tenant roles/permissions; Admin/PM manage, Employee view. |
| 10 | `database/seeds/005_seed_pm_report_permissions.sql` | 001–005 and cost scopes; report/export permissions, HR/Employee have no financial dashboard scope. |
| 11 | `database/migrations/006_create_pm_import_staging.sql` | 001–004 for tenant role/user/audit reference; import_logs and staging. Use alongside 005/report seed for the full release. |
| 12 | `database/seeds/006_seed_pm_import_permissions.sql` | 006 + tenant role/permission schema; Admin import grant. |
| 13 | `database/migrations/007_add_pm_users_email_unique.sql` | Core users + tenant columns; required before Apply. Full UNIQUE(store_id,email), preserves MyISAM. Separate local approval exists; production execution requires the operator's schema/data review and approval. |
| 14 | `database/migrations/008_create_pm_import_results.sql` | 006 staging and 007 uniqueness for safe Apply; per-row intent/outcome/provenance journal. |

For an already-upgraded host, check each actual table/column/index/seed grant and skip steps
already satisfied; record the evidence. Do not replay initial seed 001 against final tenant
schema: it was written for the initial pre-002 schema. Existing grants must be preserved.

### GATE 002 — mandatory separate review and approval

Migration 002 contains historical PM compatibility `MODIFY`, permission backfill and
replacement of old PM unique indexes. It is outside the sequential CREATE/ADD run.
The operator must review the exact production schema and obtain separate approval for
this step; local approval or approval of this guide does not approve production backfill.

Before running 002, with maintenance already enabled, the operator runs this read-only
query in the production DB tool and records the result privately:

```sql
SELECT COUNT(DISTINCT store_id) AS tenant_count FROM dc_users;
```

- If `tenant_count > 1`: **STOP. Do not run migration 002 as currently written.** Report
  the tenant/schema findings so the backfill can be redesigned and separately approved.
- If `tenant_count = 0`, NULL store IDs, partial upgrades or inconsistent tenant mappings
  exist: STOP as well; there is no confirmed single-tenant basis for this script.
- If `tenant_count = 1`: still review the authoritative tenant registry (if present),
  `dc_pm_roles` store IDs and existing permission/grant mappings. Confirm the sole user
  store matches the role store used by `MIN(store_id)`; one user tenant alone does not
  prove that stale/other role tenants are absent. Record evidence and separate approval.
  Do not invent SQL, change data or change ENGINE to make this check pass.

Required operator record before approved execution:
**tenant_count: ______; sole store_id / role mapping: ______; checked date/time: ______;
reviewer/approval: ______; schema/backup evidence: ______**.
Include the exact statement, filling in the confirmed date:
**"backfill dựa trên xác nhận production chỉ có 1 store_id tại thời điểm <ngày>, không phải logic tổng quát"**.
This statement is not a current production assertion; the agent has not checked production.
Only after separately approved 002 finishes and schema/tenant grants are verified may
the operator resume the remaining migration sequence at 003.

Before 007: inspect `SHOW INDEX FROM dc_users`, NULL/empty email policy and duplicate groups
under the actual collation, e.g. `SELECT store_id,email,COUNT(*) FROM dc_users WHERE email
IS NOT NULL GROUP BY store_id,email HAVING COUNT(*)>1` in a private DB tool. Do not paste PII
into public logs. Duplicates block rollout; no automatic cleanup. MyISAM ADD UNIQUE may
rebuild/lock the core table and block all personnel writes, so schedule maintenance.
After every step verify SHOW CREATE/COLUMNS/INDEX and grant scope; record below.

### GATE 007 — production-sized lock benchmark before scheduling execution

The operator records the actual production row count and table characteristics privately:

```sql
SELECT COUNT(*) AS actual_user_rows FROM dc_users;
SHOW TABLE STATUS LIKE 'dc_users';
SHOW INDEX FROM dc_users;
```

Do not estimate lock time from the nine local users. Before scheduling 007, benchmark the
exact reviewed migration on a protected, isolated copy of production with equivalent row
count, email lengths/collation, existing indexes and MyISAM engine. Use comparable MySQL
version, disk/storage and available resources; document any differences. Keep real copied
data outside Git/web roots and restrict access. The agent does not create this copy or
run this benchmark on hosting.

For each rehearsal use a fresh restored copy of the pre-007 schema; do not drop the index
from production or replay ALTER against an already-upgraded table. Measure elapsed time
from ALTER submission through completion and the interval during which an independent
test connection cannot access the table because of its lock. Record metadata-lock wait
separately where observable; include time for duplicate preflight and post-index checks.
Repeat on comparable fresh copies to capture variation. If equivalence or measurement
cannot be established, lock duration is unknown: STOP scheduling 007 until reviewed.

Choose and record a maintenance window covering backup, all migrations, uploads, checks,
smoke and application rollback contingency. For the 007 portion reserve at least twice
the longest measured ALTER/lock interval plus measured preflight/verification time; this
is a planning margin, not a guarantee of production duration. Increase it for slower
production storage or contention. If measured duration exceeds the available window,
reschedule before beginning; never silently proceed with a local-size estimate.

Required record: **production row count/date: ______; copy row count/schema/engine: ______;
environment differences: ______; rehearsal ALTER/lock durations: ______;
chosen window / margin / rollback allowance: ______; operator approval: ______**.
No numeric production estimate has been made by the agent.

Prepared migration status: **files reviewed; NOT EXECUTED ON PRODUCTION**.
Operator record: **steps run/skipped: ______; backup ID: ______; verification: ______**.

## FileZilla manual sequence

1. Complete local UAT and approve this candidate; resolve full Git commit, verify manifest
   hashes and save a copy of the allowlist. Backup affected remote files and production DB.
2. Confirm the correct subdomain/document root in FileZilla. Use the hosting provider's
   protected transfer method. Enable maintenance through the hosting/admin arrangement.
   Previously recorded destination: `pm.dung.derasoft.com`,
   `/domains/pm.dung.derasoft.com/public_html`; operator must confirm it is still correct.
   **Keep maintenance enabled continuously from before the first migration until every
   migration, upload, verification and required post-deploy smoke check passes.** Block
   public access and ordinary personnel writes, including alternate entrypoints/jobs;
   permit only the operator's controlled access for checks. Confirm this arrangement
   before starting; if hosting cannot provide controlled smoke access, stop and arrange
   it rather than turn maintenance off to test. There is no intermediate public reopening.
3. Run only reviewed missing migrations/seeds manually in their dependency order,
   stopping at the mandatory separate GATE 002. Complete the production-sized GATE 007
   benchmark/window approval before executing 007. Keep new routes unavailable and
   maintenance enabled throughout both gates and the remaining schema/permission checks.
4. Upload allowlisted assets/includes/classes first, then templates/modules and entrypoints
   last. Upload complete dependency folders when baseline inventory requires them. Inspect
   the transfer queue: failed transfers must be retried before restoring service.
   Use binary transfer mode to preserve bytes for manifest SHA256 verification.
5. Preserve config/license/env/uploads. Invalidate only compiled Smarty cache artifacts
   using the host's approved cache operation; don't overwrite or replace templates_c itself.
6. Run the post-deploy smoke checklist below. Restore service only when required cases pass.
   Record actual results, transfers/migrations and application commit in this log.
   If any step fails, keep maintenance enabled during investigation/rollback. Disable it
   only after the candidate passes smoke, or the restored previous application is verified
   safe to reopen. Record maintenance start/end time and the selected outcome.

## Post-deploy smoke — operator executes after upload

These are production checks after deployment, separate from pre-deploy local UAT. Use
approved test accounts/data, mark business records clearly and soft-delete test records
through the UI afterward; never delete real data or use a shared Admin credential.

- [ ] HTTPS login/logout with Admin, PM, HR and Employee; correct menus, failed CSRF and
  expired sessions handled, PM legacy screens remain inaccessible; no debug/session output.
- [ ] Admin creates project, assigns PM/members; PM creates task in own project; Employee
  sees assigned work. Foreign project/tenant IDs denied, HR/Employee management denied.
- [ ] Employee records hours on assigned task; edit/delete inside three-day window,
  read-only after lock. Admin correction after lock has separate audit entry. Daily OT and
  stored rate_snapshot/cost remain consistent. Use an approved historic fixture for lock checks.
- [ ] Admin/PM costs page and local chart/table load, date/project filters persist; HR and
  Employee financial routes denied. Mixed currencies show warning rather than invalid SUM.
- [ ] Timesheet/audit and weekly allocations read/write scopes work; overload/adjacent
  intervals display correct warnings, no out-of-scope project details leak.
- [ ] Reports: Admin/PM scoped cost export; HR/Employee allowed hours scope only. XLSX opens
  in Excel with date/DECIMAL values and formula-like text as literal strings; CSRF/no-store
  download verified. No fake export data beyond approved test records.
- [ ] Admin import template/preview available, bad file rejected; do not import personnel
  into production just to smoke-test Apply without an approved test-data plan.
- [ ] Assets return 200, no mixed content/CSP errors, desktop/mobile no page overflow;
  polling stops when hidden and stops on permission/session expiry.
- [ ] Operator records results/errors and restores service or performs rollback.

Actual post-deploy results: **NOT RUN — operator fills after manual deployment**.

## Rollback if deployment fails

Keep maintenance enabled. Record failure time and candidate commit. Restore the previous
backed-up application files for the changed paths only, preserving current production
config/license/env/uploads. Do not delete legacy directories or replace the whole root.
Invalidate only compiled Smarty cache artifacts and verify login/old application routes.

Retain additive PM tables/columns/grants and email UNIQUE; disable new application routes/
menus/Apply using the previous application version. No DROP/TRUNCATE/DELETE/DROP INDEX or
ENGINE conversion. Schema rollback and a whole-DB restore can discard post-backup writes
and break import provenance, so neither is an automatic rollback action. If data recovery
is necessary, stop writes and agree a separately authorized recovery plan with the operator.
Record rollback file version, whether writes occurred, outcome and unresolved data issues.
# Screenshot supplement — 2026-10-06

Completed the user-authorized [illustrative comparison](PHASE9B_UI_COMPARISON.md):
36 local captures, reconstructed before and synthetic after, with source labels.
No new runtime files or deployment action; original screenshots remain unavailable.
