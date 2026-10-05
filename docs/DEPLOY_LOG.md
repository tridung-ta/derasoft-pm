# Deployment preparation log — FileZilla

## Release candidate

| Field | Prepared value |
| --- | --- |
| Version | 0.10.0-local-rc1 — local candidate, not a production release |
| Preparation date | 04/10/2026; verification updated 05/10/2026, Asia/Saigon |
| Baseline before Phase 1 | `3df33493da81f53e2fdc67d826998850812dbd2f` — Phase 0 audit; first-parent predecessor of Phase 1 merge `c732a324` |
| Application base commit | `df76268` — Phase 9; Phase 10 runtime fixes are included in the manifest by tracked working-tree diff |
| Release commit/ref | `feature/pm-phase10-test-release`; resolve `git rev-parse HEAD` on this branch after the completed Phase 10 commit and record that full hash below before upload |
| Production status | NOT DEPLOYED; no upload, DB migration or production access performed by the agent |
| User UAT | Pending user confirmation; automated verification is not UAT |

The preparation document belongs to the release commit itself, so its own hash is resolved
from Git rather than recursively embedded. The final response reports the created commit.
Before manual deployment fill: **actual commit: ______; deploy date/time: ______; operator: ______**.

## Complete file lists

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
| 3 | `database/migrations/002_create_pm_users_rates.sql` | 001 + initial seed 001. Tenant-scope permission tables, departments/rates, user attributes. Existing ADD columns/indexes and PM compatibility operations are not wholly idempotent. See warning below. |
| 4 | `database/migrations/003_create_pm_projects_tasks.sql` | 001–002; projects, membership, tasks. |
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

Migration 002 contains historical PM compatibility `MODIFY`, permission backfill and
replacement of old PM unique indexes. It is not simply CREATE/ADD and must not be run as an
unreviewed universal script. Review the exact existing schema and historical approval;
its `MIN(store_id)` backfill is not a generic multi-tenant upgrade. If multiple production
tenants or partial schema exist, stop and prepare a separately reviewed migration; do not
invent SQL, change user data, or change ENGINE to make it pass.

Before 007: inspect `SHOW INDEX FROM dc_users`, NULL/empty email policy and duplicate groups
under the actual collation, e.g. `SELECT store_id,email,COUNT(*) FROM dc_users WHERE email
IS NOT NULL GROUP BY store_id,email HAVING COUNT(*)>1` in a private DB tool. Do not paste PII
into public logs. Duplicates block rollout; no automatic cleanup. MyISAM ADD UNIQUE may
rebuild/lock the core table and block all personnel writes, so schedule maintenance.
After every step verify SHOW CREATE/COLUMNS/INDEX and grant scope; record below.

Prepared migration status: **files reviewed; NOT EXECUTED ON PRODUCTION**.
Operator record: **steps run/skipped: ______; backup ID: ______; verification: ______**.

## FileZilla manual sequence

1. Complete local UAT and approve this candidate; resolve full Git commit, verify manifest
   hashes and save a copy of the allowlist. Backup affected remote files and production DB.
2. Confirm the correct subdomain/document root in FileZilla. Use the hosting provider's
   protected transfer method. Enable maintenance through the hosting/admin arrangement.
   Previously recorded destination: `pm.dung.derasoft.com`,
   `/domains/pm.dung.derasoft.com/public_html`; operator must confirm it is still correct.
3. Run only reviewed missing migrations/seeds manually in their dependency order. Keep
   new routes unavailable until all required schema/permission checks pass.
4. Upload allowlisted assets/includes/classes first, then templates/modules and entrypoints
   last. Upload complete dependency folders when baseline inventory requires them. Inspect
   the transfer queue: failed transfers must be retried before restoring service.
   Use binary transfer mode to preserve bytes for manifest SHA256 verification.
5. Preserve config/license/env/uploads. Invalidate only compiled Smarty cache artifacts
   using the host's approved cache operation; don't overwrite or replace templates_c itself.
6. Run the post-deploy smoke checklist below. Restore service only when required cases pass.
   Record actual results, transfers/migrations and application commit in this log.

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
