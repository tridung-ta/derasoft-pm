# Phase 9 — EXPLAIN evidence — 2026-10-04

Command: `.tools/php83/php.exe tests/pm_explain_smoke.php --report`.
Result: PASS 62 actual prepared SELECT shapes captured while executing DAO/services;
SQL parameter values are omitted. The detailed local report is ignored at
`.local/phase9-explain.json` and is not committed.

| Family | Query shapes | Scope inspected |
| --- | ---: | --- |
| Users | 2 | Filtered list/count; tenant and department/role filters |
| Audit | 4 | Scope, count and pagination |
| Costs | 31 | Currency guard before aggregation, actual/lifetime/estimate and grouping |
| Allocations | 12 | Week, total workload, capacity and scoped suggestions |
| Reports | 7 | List and bounded export |
| Imports | 6 | History, detail and journal |

All captured plans avoided `type=ALL` in this local run. Existing primary/tenant indexes
were selected; some queries still use temporary tables/filesort for grouping or ordering.
For example, filtered users use `uq_pm_users_store_email` for tenant access and report
`Using where; Using temporary; Using filesort`. This does not imply an email index optimizes
substring search or name ordering. No ADD INDEX was justified by these small-data plans,
so no migration was proposed or executed and there is no invented after-index benchmark.

The fixture runs inside rollback boundaries and leaves no personnel or business data.
Dataset: nine local users plus a small project/task/timesheet/allocation/import fixture.
Do not infer production performance from row estimates here. If future evidence requires
ADD INDEX, stop immediately, report the before plan and proposed versioned migration,
request separate approval, then collect the after plan only in an authorized environment.
