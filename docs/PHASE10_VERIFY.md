# Phase 10 — verification — 05/10/2026

BUILD / automated VERIFY completed. User approved test/UAT scope and FileZilla preparation.
Local user UAT and production deployment remain unconfirmed. One local phase commit follows
the verified code/security review and documentation; no push/merge/deploy.

| Area | Actual result / evidence |
| --- | --- |
| Current PM regression | `./tests/pm_regression.ps1`: PASS 41 scripts after GET logout-link and generic project-error logging fixes |
| Deployment manifest | `python tests/pm_deploy_manifest.py` and `--check`: PASS 177 tracked baseline-diff paths, 75 runtime upload files, every path classified, SHA256 recorded; regenerate after later staged runtime/files changes |
| Baseline | First-parent Git history identifies `3df33493...` (Phase 0) immediately before Phase 1 merge `c732a324...` |
| FileZilla preparation | DEPLOY_LOG.md includes full manifests, 001–008 migrations/001–006 seeds and dependencies, excluded protected files, post-deploy smoke and non-destructive application rollback. No upload or SQL execution on production |
| Contrast sample | `playwright-cli -s=pm10-contrast run-code --filename=../tests/pm_phase10_contrast_browser.js`: first run found dashboard muted text ratio 4.39; changed local color variable, repeat PASS on four synthetic screens. Solid-color sample only |
| UI regression | `playwright-cli -s=pm10-contrast run-code --filename=../tests/pm_ui_browser.js`: PASS 13 synthetic screen/state fixtures at 360/390/768/1440px, labels/keyboard skip/CSP/overflow; 200% text scaling, not real browser zoom |
| Second browser engine | Attempted `playwright-cli -s=pm10-firefox open --browser=firefox`: unavailable, Firefox executable not installed. Not PASS; no dependency installed |
| Isolated DB setup | `.tools/php83/php.exe tests/pm_phase10_fixture.php`: PASS after operator-created local DB/grant. Copied schema/permission definitions only, synthetic accounts; engine MyISAM and email UNIQUE preserved, no real personnel copied |
| Authenticated four-role browser/CRUD | `playwright-cli -s=pm10-auth run-code --filename=../tests/pm_phase10_browser.js`: PASS 36 role/route cases with actual password login and SID regeneration/logout, 390/1440px; Admin creates project/membership, PM creates assigned task, Employee records 9h and downloads XLSX. HR project denial corrected in test expectation to match approved seed; no extra grants |
| Native MyISAM crash/resume | `.tools/php83/php.exe tests/pm_phase10_native_crash.php`: PASS successful native INSERT then terminated PHP worker; pending intent/in_progress survived, connection lock released, fresh-connection resume recovered one inactive account, replay idempotent. No transactional user mirror |
| Result / cleanup / expiry | `.tools/php83/php.exe tests/pm_phase10_verify_cleanup.php`: PASS stored 9h/8 regular/1 OT, workbook contents, existing-session role revocation 403/inactive actor 401; all fixture projects/tasks/timesheets soft deleted and synthetic accounts disabled in isolated DB, source personnel count unchanged |
| Browser zoom / screen reader | Real 200% browser zoom and screen-reader checks NOT RUN; final manual UAT checklist retains them |

Code/security review traced test DB guards, source read-only invariants, CSRF/ownership,
permission revalidation, file manifests/exclusions, logs and safe cleanup. No demonstrated
outstanding defect in that reviewed scope; not an independent penetration test.
Dashboard copy no longer reports a fixed four modules or projects as a future feature;
targeted Smarty/HTTP/UI matrix checks passed after that refinement. Screenshots inspected.

The native worker test targets process termination after successful INSERT, not mysqld
termination, OS failure or physical power-loss durability. This closes the PHP-worker crash
gap from Phase 8; physical crash durability is still untested. Browser CRUD coverage is the
named end-to-end path, not every edit/delete screen; other operations have service/HTTP
regression evidence. Firefox unavailable, real zoom and screen-reader manual checks pending.
Do not rerun browser/crash scripts against cleaned inactive actors without a fresh isolated
fixture environment. Existing fixture DB is retained with history, never implicitly reset.

Deployment status: NOT DEPLOYED. Post-deploy smoke is operator work after manual deployment;
UAT_FINAL_LOCAL.md is the distinct pre-deploy UAT checklist.
