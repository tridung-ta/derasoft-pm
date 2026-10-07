# Phase 11 release follow-up — 07/10/2026

## Review scope

Runtime `1f1ef1c` on `feature/pm-phase11-requirements`; previous GitHub tip
`8c78801`, local develop `a24860b`. Later commits add controller HTTP fixtures,
workflow memory and the personnel false-success fix. No new migration.

Personnel UPDATE previously reported success/tracking for nonexistent, hidden
or cross-tenant IDs. The DAO now distinguishes unchanged valid forms from invalid
targets; controller checks its return before success notice/tracking.

Latest verification: 47 regression scripts PASS, including 73 request-local HTTP
fixture cases; PHP lint PASS. Earlier browser results remain separately dated.
Tests use temporary tables; not real-account password login or browser write UAT.
Persistent business/user rows and core user engine unchanged during these tests.

## Prepared upload choices

- Existing full Phase 11 installation: `.local/releases/derasoft-pm-personnel-target-fix.zip`,
  two files: `classes/dao/pmusers.class.php`, `modules/admin/pmusers.module.php`.
- Full reviewed candidate: `.local/releases/0.11.0-phase11-local-rc2.zip`;
  use `DEPLOY_FILES.txt` and per-file SHA256 in `DEPLOY_MANIFEST.json`.
  This is a Phase 0 runtime delta, not an inspected live-host delta.

Preserve relative paths and production config. No SQL/config/tests/docs/cache/uploads
in ZIP. Production screenshots already confirm all four nullable columns of 009;
do not rerun ALTER on that evidence. No production upload in this preparation.

## Approval gate and exact next actions

After approval, push the reviewed Phase 11 branch to origin, then fast-forward
local develop using `git merge --ff-only feature/pm-phase11-requirements`.
If fast-forward fails, stop; no normal merge/rebase/force fallback.
Do not push develop or merge into main without explicit scope approval.
Production patch upload and remaining manual acceptance are separate actions.

Main review/PR should describe the completed ten functional areas and truthful
verification limits. Do not merge solely for contribution counts. Remaining
manual cases: real PM/HR account workflows, Excel opening and real browser zoom.

## Suggested review description

Phase 11 completes project metadata, task audit, scoped statistics, directory
XLSX and self-hosted Bootstrap, preserving DeraSoft MVC and permission boundaries.
Follow-up fixes shared email/username credentials, login legibility and personnel
save-result handling. Controller fixtures cover project/task, daily OT/cost and
allocation mutations plus role/CSRF/ownership rejection. Full regression 47 PASS;
specific manual UAT and the latest production patch remain outstanding.
