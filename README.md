<p align="center">
  <img src="docs/assets/repository-cover.svg" alt="DeraSoft PM — Project and Timesheet Management" width="100%">
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&amp;logoColor=white" alt="Local PHP 8.3">
  <img src="https://img.shields.io/badge/Smarty-4.5-2563EB" alt="Smarty 4.5">
  <img src="https://img.shields.io/badge/MySQL-InnoDB-4479A1?logo=mysql&amp;logoColor=white" alt="MySQL InnoDB">
  <img src="https://img.shields.io/badge/Architecture-Custom_MVC-334155" alt="Custom MVC">
</p>

# DeraSoft PM

**Project & timesheet management built by extending an existing PHP application.**

DeraSoft PM connects workforce management, projects, tasks and time tracking in a tenant-scoped workspace for Admin, Project Manager, HR and employees. It preserves the existing DeraSoft architecture while adding prepared queries, access boundaries and auditable business operations.

**Author:** [Tạ Trí Dũng](https://github.com/tridung-ta) · **Focus:** backend development, business rules and legacy integration.

> **Development status — 04 Oct 2026:** Phase 6 locally built and automated-verified. Local checkpoint only; latest published code remains Phase 5. User UAT/production acceptance pending.

## What the application does

| Area | Implemented on the Phase 5 branch |
| --- | --- |
| Authentication & access | Legacy password upgrade, secure sessions, four roles and tenant-scoped permissions |
| Workforce | User and department management, multiple roles and effective hourly rates |
| Projects & tasks | Project members, task assignment, priorities, deadlines and four-column Kanban |
| Timesheets | Task/date/shift entries, a 24-hour daily limit and recalculation after changes |
| Overtime & costing | Daily OT across projects, stored rate snapshots and MySQL DECIMAL cost calculation |
| Automatic locking | Three calendar days to edit; Admin corrections after locking receive separate audit actions |
| Audit viewer | Tenant/project/department access scopes, financial-field redaction, filters and pagination |

Timesheet approval is intentionally deferred. Phase 5 has no Submitted/Approved/Rejected workflow. Cost dashboards, resource allocation and reporting are planned for later phases.

## Engineering highlights

- **Tenant isolation:** new business queries and access checks use the session's `store_id`.
- **Consistent daily OT:** a unique user/day ledger is locked before writes; editing, moving or hiding an entry recalculates the affected days.
- **Historical costing:** each timesheet stores its rate snapshot and cost; editing the same date preserves the original rate.
- **Auditable transactions:** the business write, affected-row recalculation and JSON audit records commit or roll back together.
- **Safe legacy extension:** additive migrations, soft deletes and compatibility paths preserve existing functionality.
- **Server-side enforcement:** ownership, role permissions, CSRF checks and the edit window accompany escaped Smarty output.

## Architecture

```text
modules/admin/         Controllers and permission / CSRF checks
classes/dao/           Prepared data access for new PM modules
classes/services/      Business rules, transactions, OT and rate resolution
templates/admin/       Smarty views
database/migrations/   Versioned additive schema changes
database/seeds/        Role and permission mappings
tests/                 Local service and template smoke checks
docs/                  Audit, plans, schema and verification records
```

**Stack:** plain PHP · Smarty · MySQL/InnoDB · custom MVC · embedded PhpSpreadsheet.

## Explore the work

- [Latest implementation](https://github.com/tridung-ta/derasoft-pm/tree/feature/pm-phase5-timesheet-ot)
- [Progress and commands actually run](https://github.com/tridung-ta/derasoft-pm/blob/feature/pm-phase5-timesheet-ot/docs/PROGRESS.md)
- [Phase 5 scope and business rules](https://github.com/tridung-ta/derasoft-pm/blob/feature/pm-phase5-timesheet-ot/docs/PHASE5_PLAN.md)
- [Architecture and security audit](https://github.com/tridung-ta/derasoft-pm/blob/feature/pm-phase5-timesheet-ot/docs/AUDIT.md)
- [Database schema](https://github.com/tridung-ta/derasoft-pm/blob/feature/pm-phase5-timesheet-ot/docs/DB_SCHEMA.md)
- [Portfolio / CV summary](docs/PORTFOLIO.md)

## Local verification

Use PHP 8.3 with the required extensions and a backed-up local database. See the [environment baseline](https://github.com/tridung-ta/derasoft-pm/blob/feature/pm-phase5-timesheet-ot/docs/BASELINE.md) and [migration conventions](database/migrations/README.md) before setup. Environment configuration and license files are supplied separately; the public repository is not a one-command installation package.

After checking out the implementation branch and preparing the local schema, representative checks are:

```sh
php tests/pm_timesheets_smoke.php
php tests/pm_timesheet_window_smoke.php
php tests/pm_audit_smoke.php
php tests/smoke_pm_timesheets.php
php tests/smoke_pm_audit.php
```

These checks passed on the documented local environment. Database smoke fixtures use transactions and rollback. Automated service/template verification does not replace browser UAT or concurrent multi-connection testing.

## Roadmap

| Phase | Status |
| --- | --- |
| 0–2 · Audit, local baseline, auth & RBAC | Documented and verified |
| 3–5 · Workforce, projects, timesheets & audit | BUILD / automated verification completed; UAT pending |
| 6 · Cost aggregation & charts | Planned |
| 7 · Resource allocation & overbooking | Planned |
| 8 · Reports & Excel | Planned |
| Release acceptance | Pending UAT and explicit deployment approval |

## Tiếng Việt

Hệ thống quản lý dự án và chấm công phát triển trên nền DeraSoft hiện có, không viết lại framework. Điểm chính gồm phân quyền theo tenant, quản lý nhân sự/dự án/task, tính OT theo tổng giờ ngày, snapshot đơn giá và audit log trong transaction. Chấm công được sửa/xóa trong ba ngày lịch tính từ ngày làm việc; Admin sửa sau khóa có audit riêng.

Mã BUILD mới nhất nằm trên nhánh Phase 5 được dẫn ở trên. Công việc đang tạm dừng, chưa xác nhận UAT hoặc triển khai production. Các file cấu hình bảo mật, license, dữ liệu upload, database dump, cache và log không được đưa vào Git.

## Local checkpoint

Cost dashboard: stored DECIMAL actual cost, currency guards, local pinned Chart.js and scoped polling.

Commands/results and limitations are recorded in docs/PROGRESS.md. No UAT/production-ready claim.
