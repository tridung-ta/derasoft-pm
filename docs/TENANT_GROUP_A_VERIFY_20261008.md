# Tenant 1.3 — nhóm A: dự án

PLAN và bốn bảng đã được người dùng duyệt. Chỉ nhóm dự án và hạ tầng kiểm thử chung được commit ở bước này; nhóm B/C vẫn chưa VERIFY.

- Lệnh .tools/php83/php.exe tests/pm_tenant_matrix_smoke.php a: exit0,19/19 cặp PASS (38 negative requests),2 positive controls PASS,11 behavioral controls PASS; checksum21 bảng persistent trước/sau giống. Bằng chứng .local/tenant-matrix-a-20261008.json và .local/tenant-group-a-command-20261008.json.
- Behavioral controls: PM local ngoài quyền GET/POST403; foreign/missing404 và cùng thông báo không chứa loại tài nguyên; malformed project/task/manager200, invalid name200, duplicate code business200; project cùng ngày303; Save/Hide303; task cùng ngày200 và audit/tracking mới đúng số lượng.
- pm_projects_smoke, pm_task_status_http_smoke (33 HTTP cases), smoke_pm_projects, pm_phase11_metadata đềuexit0,stderr rỗng. Output .local/tenant-group-a-suites-20261008.json. Giữ mọi assertion dữ liệu/audit/no-op/completion; chỉ thay ca assignee không tồn tại sang catchOutOfBounds, không gộp với local nonmember validation.
- PHP lint helper/controller/fixture/matrix PASS. Review: prepared scoped lookup, CSRF unchanged, subtype trướcparent, businessDomain200, no migration, no legacy removal. Hàm nhập ID không ép chữ thành0/tạo mới; PM manager vẫn actor.
- Preflight: chỉ PHP8088/PID37372 đã biết, cổng18770/18776 rảnh. Không kill process user. Mỗi request có session fixture riêng và temporary exact-schema mirrors; không coi file session cũ là phiên đang hoạt động. Checksum persistent là gate, không retry để giấu thay đổi ngoài.

1.3 chưa nângPASS cho tới matrix cuối và các nhóm còn lại đạt. P3 vẫn chờA/B/C. Không push/merge/deploy.
