# Phase 9b Step 7 — Timesheet ledger

Approved ongoing local build; additional read-only weekly aggregate explicitly approved
on 2026-10-06. No change to writes, OT calculation, rates, routes, DAO, permissions or schema.

Files: templates/admin/pm-timesheets.tpl.html and css/pmui.css for the ledger,
seven labelled days, Inter tabular hours, today treatment, textual OT plus amber accent,
and native collapsed Admin OT settings at the end. Existing forms/CSRF remain intact.
Small-screen days reflow rather than overflowing the page; history retains its scroll region.

classes/services/pmtimesheetservice.class.php adds a prepared store/user-scoped weekly
SUM of persisted hours/regular_hours/ot_hours, Monday–Sunday in Vietnam time.
modules/admin/pmtimesheets.module.php assigns the summary for the selected form date,
actor by default or the authorized edit target. Invalid date/query failure has explicit
unavailable text rather than fabricated zero totals. No pagination dependency.

Tests: tests/pm_timesheets_smoke.php for aggregate isolation, date boundaries,
permissions and persisted values; tests/smoke_pm_timesheets.php for escaped markup,
forms and summary states; tests/pm_phase9b_timesheets_browser.js for keyboard,
responsive, numeric styles, no-JS and settings visibility. Run Phase 9 browser matrix
and full local regression; review diffs/security before one focused local commit.
