# Lô 2 — kiểm chứng dữ liệu ngày 09/10/2026

Chỉ local; không migration, push, merge hay deploy. Không thay runtime trong lô này.

| Mục | Lệnh đã chạy | Output thực tế |
|---|---|---|
| 2.2 | `.tools/php83/php.exe tests/pm_auth_identity_smoke.php` | PASS 2.2: MD5 login persisted modern hash and last_login; exact-schema users mirror; persistent accounts unchanged. PASS: 25 shared identity/password checks. |
| 3.10 | `.tools/php83/php.exe tests/pm_users_crud_smoke.php` | PASS 12 filter cases: list/count exact IDs; PASS name/email/username/department/role/combined filters and 4 pagination pages; persistent users/reference tables/engine unchanged. |
| 5.5 | `.tools/php83/php.exe tests/pm_timesheets_smoke.php` | PASS 5.5: stored cost and rate_snapshot unchanged after rate edit and same-date resave (exact DECIMAL strings). Fixtures rolled back. |
| 8.3 | `.tools/php83/php.exe tests/pm_reports_smoke.php` | PASS từng prefix =, +, -, @ round-trips as literal text in shift and description; fixtures rolled back. |

Các assertion cũ được giữ. 2.2 kiểm tra trực tiếp hash đã lưu bằng password_verify, không in hash. 3.10 có bản ghi ẩn và khác tenant, kiểm tra cả list/count và không nhân bản khi nhiều role. 5.5 so sánh chuỗi DECIMAL trước/sau thay đơn giá và lưu lại cùng ngày; không sửa logic OT. 8.3 đọc lại workbook và kiểm tra type string, giá trị literal và calculated value.

Regression sau bốn bổ sung: `powershell -NoProfile -ExecutionPolicy Bypass -File tests/pm_regression.ps1` → `PASS: 59 regression scripts. No migrations applied by this runner.` Output local: `.local/batch2-data-regression-20261009.txt`.
