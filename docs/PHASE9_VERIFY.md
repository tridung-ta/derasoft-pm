# Phase 9 — local verification — 2026-10-04

BUILD / automated VERIFY completed on `feature/pm-phase9-ui-security`.
This is not user UAT, independent penetration testing or production acceptance.

## Delivered

- Shared permission-aware navigation, current-page state, skip link, visible labels,
  keyboard focus and scrollable table regions. Existing routes and business rules retained.
- One prepared tenant-scoped role query per populated user page; PHP groups all roles by
  user ID. Only `is_primary = 1` determines the primary role. Multiple primaries show a
  warning instead of choosing an arbitrary role.
- PM login/logout CSRF, POST-only logout, active/tenant session revalidation and disabled
  legacy file manager in PM mode. Malformed array input is rejected before controllers.
- Local styles and confirmation script replace inline code before enabling PM-only CSP;
  no-store, nosniff, same-origin referrer and framing headers retained on errors too.
- Costs/allocations stop polling after 401/403, including subsequent visibility changes;
  transient errors preserve the previous data and retain the 60-second retry cycle.

## Evidence and coverage

| Acceptance group | Commands / actual result |
| --- | --- |
| 1. UI/accessibility | `playwright-cli -s=pmphase9 run-code --filename=../tests/pm_ui_browser.js`: PASS 13 synthetic screen/state fixtures at 360/390/768/1440px, labels, table regions, first-tab skip link, main focus and no page overflow. Desktop/mobile screenshots captured and inspected. 200% text scaling passed; this is not real browser/OS zoom. |
| 2. Exact batch equivalence | `.tools/php83/php.exe tests/pm_roles_batch_smoke.php`: PASS full role rows and primary IDs for 20 users with 2–3 roles, varied ordering, empty users, cross-tenant IDs and inconsistent primaries. Old 20 role queries → 1; one user → 1; empty page → 0. PM fixtures rolled back; no core users created. |
| 3. Query evidence/regression | `.tools/php83/php.exe tests/pm_explain_smoke.php --report`: PASS 62 prepared query shapes in six families. See PHASE9_EXPLAIN.md. Existing money/currency/history/filter/overbooking/locking tests passed; no index or schema changes. |
| 4. Security | `tests/pm_ui_http_smoke.php`: PASS 18 authenticated role/route cases for Admin/Employee, headers/menu, malformed arrays, SQLi/XSS search, wrong tenant/missing actor, login/logout negative CSRF and file-manager guard. `tests/pm_session_guard_smoke.php`: PASS active/inactive/deleted/missing/revoked/cross-tenant actors using temporary mirror. Existing service/HTTP suites cover permissions, ownership, export/import CSRF, uploads and provenance. |
| 5. Browser/CSP/polling | `pm_costs_browser.js`, `pm_allocations_browser.js`, `pm_reports_browser.js`, `pm_import_browser.js`: PASS synthetic browser checks with CSP enabled, local Chart.js/table fallback, visibility/polling/expiry, downloads and POST fields. `pm_confirmation_browser.js`: PASS cancel/accept handler with mocked confirm result and intercepted fixture POST. |
| 6. Whole PM regression | `./tests/pm_regression.ps1`: PASS 41 scripts, no migrations applied by the runner. PHP 8.3 lint of changed/new PHP files, Node syntax of new/local JavaScript and `git diff --check` passed. |

Security/code review traced Admin/AJAX entrypoints, session and permission revalidation,
tenant scope, CSRF, escaping/JSON, prepared batch SQL, money guards, upload/export and logs.
No outstanding demonstrated defect was identified in that reviewed scope. This does not
replace an independent penetration test.

## Remaining coverage and release limits

- The local DB has no active PM/HR actor: authenticated UI HTTP coverage for these roles
  is unavailable. Rollback permission/service fixtures cover their boundaries, but are
  explicitly distinct from an authenticated browser session.
- The browser matrix uses synthetic data; actual HTTP checks exercise read-only routes
  and negative mutations. No successful real-account login/logout or every CRUD flow was
  driven end to end by browser in Phase 9. Screen-reader testing, full contrast tooling,
  real 200% browser zoom and a second browser engine remain for final local acceptance.
- EXPLAIN uses nine local users plus small rollback fixtures; it proves query shape and
  observed index selection, not production throughput or latency.
- Phase 8's untested kill-process window after a successful native MyISAM INSERT remains;
  a transactional mirror is not proof of MyISAM atomicity or physical crash durability.
- UAT and production remain pending. No push, merge, deployment or production access.
