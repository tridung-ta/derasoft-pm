# Phase 9 — CSP inventory

Active PM templates inspected before setting CSP.

- `pm.tpl.html`: 1 inline style block(s), 0 inline confirmation handler(s).
- `pm-login.tpl.html`: 1 inline style block(s), 0 inline confirmation handler(s).
- `pm-users-v2.tpl.html`: 1 inline style block(s), 2 inline confirmation handler(s).
- `pm-projects.tpl.html`: 1 inline style block(s), 2 inline confirmation handler(s).
- `pm-timesheets.tpl.html`: 1 inline style block(s), 0 inline confirmation handler(s).
- `pm-audit.tpl.html`: 1 inline style block(s), 0 inline confirmation handler(s).
- `pm-costs.tpl.html`: 0 inline style block(s), 0 inline confirmation handler(s).
- `pm-allocations.tpl.html`: 0 inline style block(s), 0 inline confirmation handler(s).
- `pm-reports.tpl.html`: 0 inline style block(s), 0 inline confirmation handler(s).
- `pm-imports.tpl.html`: 0 inline style block(s), 0 inline confirmation handler(s).
- `pm-rates-panel.tpl.html`: 0 inline style block(s), 1 inline confirmation handler(s).

Inline CSS moved to local stylesheets; confirmation handlers moved to pmui.js.
Costs uses non-executable application/json plus pinned local Chart.js and pmcosts.js;
allocations uses local pmallocations.js. No remote fonts/scripts, object/embed or inline style
attributes found in the active templates. Pre-policy browser checks passed before enabling CSP.
Legacy templates remain unchanged.

Confirmation after enabling the policy: the 13-screen synthetic UI matrix plus costs,
allocations, reports/export and imports browser scripts passed. The UI matrix collects
`securitypolicyviolation` events and found none. Chart.js is pinned and local. Confirmation
handler cancel/accept was tested with a mocked confirm return value and intercepted POST.
Policy and remaining coverage are documented in PHASE9_VERIFY.md; no unsafe-inline grant.
