# Tenant1.3 — nhóm B: nhân sự

Lệnh .tools/php83/php.exe tests/pm_tenant_matrix_smoke.php b:18/18 cặp PASS,36 negative requests;1control+9behavioral PASS;cleanup21bảng PASS;exit0. Artifacts .local/tenant-matrix-b-20261008.json, .local/tenant-group-b-command-20261008.json.

pm_users_crud_smoke, pm_user_input_smoke (21 boundaries), pm_user_identity_smoke (43 cases+two-connection lock checks), pm_departments_smoke, pm_roles_batch_smoke, pm_rates_smoke đềuexit0/stderr rỗng. .local/tenant-group-b-suites-20261008.json. PHP lint6file PASS.

Lượt đầu có1behavioralFAIL: fixture yêu cầu phone-invalid trên UPDATE trong khi quy tắc10số/không chữ đã duyệt cho CREATE. Giữ output .local/tenant-group-b-first-command-20261008.json/.local/tenant-group-b-first-data-20261008.json; đổi fixture sang CREATE với mật khẩu hợp lệ, giữ assertion unchanged/audit0/tracking0. Không sửa validator hoặc đổi hành vi UPDATE ngoài TenantPLAN. Lượt cuốiPASS phản ánh đúng luồng CREATE; không phủ phonepolicyUPDATE.

Review scoped prepared DAO lookup; không dùng affected_rows=0 để kết luận thiếu. Hidden-userstatus/restoredepartment không thay chính sách. Role foreign/missing404 prevalidate trướcDELETE; inactive local/primary local notselected200. ID malformed không bị cast thành0/tạo mới. Departmentfilter vàrolefilter foreign/missing404 chung message; validation nhập liệu/duplicate business giữ200. Khôngmigration/enginechange/legacyremoval.

Nhóm A đã commit b70b441. Nhóm C chưa VERIFY;1.3 chưa nângPASS. Khôngpush/merge/deploy;P3chờA/B/C.
