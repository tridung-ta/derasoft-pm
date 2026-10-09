# Bảng 2 — Endpoint/op và ID so với80 cặp

Ngày 08/10/2026. **Chỉ rà soát code; chờ duyệt bốn bảng trước BUILD tiếp.** HEAD `26ca279095e26c30960cf82294842fefe1f548b0`; working tree đã có sửa local theo lượt duyệt trước, không commit/push/merge/deploy. Các vị trí dòng bên dưới theo working tree hiện tại, không giả định đây là code đã phát hành.

Router đọc trực tiếp: `admin.php` allowlist9PMop; `pm_ajax.php` allowlist3op. `modules/admin/pm.module.php` có4act. Bảng dưới ghi đủ80 cặp đang có; coverage không có nghĩa đã phủ mọi role/method/định dạng.

| Ca | Endpoint/action | Actorfixture | Loại ID | Tham số thay |
|---|---|---|---|---|
| 1 | /admin.php pmprojects GET read / project_id | PM | project | `project_id` |
| 2 | /admin.php pmprojects POST project_save / project_id | PM | project | `project_id` |
| 3 | /admin.php pmprojects POST project_delete / project_id | PM | project | `project_id` |
| 4 | /admin.php pmprojects POST member_save / project_id | PM | project | `project_id` |
| 5 | /admin.php pmprojects POST task_save / project_id | PM | project | `project_id` |
| 6 | /admin.php pmprojects POST task_status / project_id | PM | project | `project_id` |
| 7 | /admin.php pmprojects POST task_delete / project_id | PM | project | `project_id` |
| 8 | /admin.php pmprojects POST task_save / task_id | PM | task | `task_id` |
| 9 | /admin.php pmprojects POST task_status / task_id | PM | task | `task_id` |
| 10 | /admin.php pmprojects POST task_delete / task_id | PM | task | `task_id` |
| 11 | /admin.php pmprojects POST member_save / member_id | PM | user | `member_id` |
| 12 | /admin.php pmprojects POST task_save / assignee_id | PM | user | `assignee_id` |
| 13 | /admin.php pmprojects POST project_save / manager_id | ADMIN | user | `manager_id` |
| 14 | /pm_ajax.php pmtaskstatus POST status / project_id | PM | project | `project_id` |
| 15 | /pm_ajax.php pmtaskstatus POST status / task_id | PM | task | `task_id` |
| 16 | /admin.php pmtimesheets GET read / id | ADMIN | timesheet | `id` |
| 17 | /admin.php pmtimesheets POST save / id | ADMIN | timesheet | `id` |
| 18 | /admin.php pmtimesheets POST delete / id | ADMIN | timesheet | `id` |
| 19 | /admin.php pmtimesheets POST save / task_id | ADMIN | task | `task_id` |
| 20 | /admin.php pmallocations GET read / id | ADMIN | allocation | `id` |
| 21 | /admin.php pmallocations POST save / id | ADMIN | allocation | `id` |
| 22 | /admin.php pmallocations POST delete / id | ADMIN | allocation | `id` |
| 23 | /admin.php pmallocations POST save / task_id | ADMIN | task | `task_id` |
| 24 | /admin.php pmallocations POST save / user_id | ADMIN | user | `user_id` |
| 25 | /admin.php pmallocations POST capacity / id | ADMIN | capacity | `id` |
| 26 | /admin.php pmallocations POST capacity / user_id | ADMIN | user | `user_id` |
| 27 | /admin.php pmallocations POST capacity_delete / id | ADMIN | capacity | `id` |
| 28 | /admin.php pmallocations GET read / project_id | PM | project | `project_id` |
| 29 | /admin.php pmallocations GET read / user_id | ADMIN | user | `user_id` |
| 30 | /pm_ajax.php pmallocations GET read / project_id | PM | project | `project_id` |
| 31 | /pm_ajax.php pmallocations GET read / user_id | ADMIN | user | `user_id` |
| 32 | /pm_ajax.php pmallocations GET suggestions / project_id | PM | project | `project_id` |
| 33 | /admin.php pmusers POST save / id | ADMIN | user | `id` |
| 34 | /admin.php pmusers POST status / id | ADMIN | user | `id` |
| 35 | /admin.php pmusers POST roles / id | ADMIN | user | `id` |
| 36 | /admin.php pmusers POST roles / role_ids | ADMIN | role | `role_ids` |
| 37 | /admin.php pmusers POST save / department_id | ADMIN | department | `department_id` |
| 38 | /admin.php pmusers POST department_save / id | ADMIN | department | `id` |
| 39 | /admin.php pmusers POST department_status / id | ADMIN | department | `id` |
| 40 | /admin.php pmusers POST department_delete / id | ADMIN | department | `id` |
| 41 | /admin.php pmusers POST department_restore / id | ADMIN | hidden_department | `id` |
| 42 | /admin.php pmusers POST rate / id | ADMIN | user | `id` |
| 43 | /admin.php pmusers POST rate_save / rate_user_id | ADMIN | user | `rate_user_id` |
| 44 | /admin.php pmusers POST rate_save / rate_role_id | ADMIN | role | `rate_role_id` |
| 45 | /admin.php pmusers POST rate_save / rate_id | ADMIN | rate | `rate_id` |
| 46 | /admin.php pmusers POST rate_deactivate / id | ADMIN | rate | `id` |
| 47 | /admin.php pmusers GET read / department_id | ADMIN | department | `department_id` |
| 48 | /admin.php pmusers GET read / role_id | ADMIN | role | `role_id` |
| 49 | /admin.php pmcosts GET read / project_id | ADMIN | project | `project_id` |
| 50 | /pm_ajax.php pmcosts GET read / project_id | ADMIN | project | `project_id` |
| 51 | /admin.php pmreports GET hours / project_id | ADMIN | project | `project_id` |
| 52 | /admin.php pmreports POST export / project_id | ADMIN | project | `project_id` |
| 53 | /admin.php pmreports GET hours / user_id | ADMIN | user | `user_id` |
| 54 | /admin.php pmreports POST export / user_id | ADMIN | user | `user_id` |
| 55 | /admin.php pmreports GET tasks / project_id | ADMIN | project | `project_id` |
| 56 | /admin.php pmreports POST export / project_id | ADMIN | project | `project_id` |
| 57 | /admin.php pmreports GET tasks / user_id | ADMIN | user | `user_id` |
| 58 | /admin.php pmreports POST export / user_id | ADMIN | user | `user_id` |
| 59 | /admin.php pmreports GET costs / project_id | ADMIN | project | `project_id` |
| 60 | /admin.php pmreports POST export / project_id | ADMIN | project | `project_id` |
| 61 | /admin.php pmreports GET costs / user_id | ADMIN | user | `user_id` |
| 62 | /admin.php pmreports POST export / user_id | ADMIN | user | `user_id` |
| 63 | /admin.php pmreports GET users / user_id | ADMIN | user | `user_id` |
| 64 | /admin.php pmreports POST export / user_id | ADMIN | user | `user_id` |
| 65 | /admin.php pmreports GET projects / project_id | ADMIN | project | `project_id` |
| 66 | /admin.php pmreports POST export / project_id | ADMIN | project | `project_id` |
| 67 | /admin.php pmimports POST detail / import_id | ADMIN | import | `import_id` |
| 68 | /admin.php pmimports POST apply / import_id | ADMIN | import | `import_id` |
| 69 | /admin.php pmimports POST resume / import_id | ADMIN | import | `import_id` |
| 70 | /admin.php pmimports POST provision_password / import_id | ADMIN | import | `import_id` |
| 71 | /admin.php pmprojects GET read / project_id [Admin read] | ADMIN | project | `project_id` |
| 72 | /admin.php pmprojects POST member_save / member_id [remove member] | PM | user | `member_id` |
| 73 | /admin.php pmprojects POST project_save / manager_id [create project] | ADMIN | user | `manager_id` |
| 74 | /admin.php pmprojects POST task_save / assignee_id [create task] | PM | user | `assignee_id` |
| 75 | /admin.php pmtimesheets POST save / task_id [create timesheet] | ADMIN | task | `task_id` |
| 76 | /admin.php pmallocations POST save / task_id [edit allocation] | ADMIN | task | `task_id` |
| 77 | /admin.php pmallocations POST save / user_id [edit allocation] | ADMIN | user | `user_id` |
| 78 | /admin.php pmallocations POST capacity / user_id [edit capacity] | ADMIN | user | `user_id` |
| 79 | /admin.php pmusers POST roles / primary_role_id | ADMIN | role | `primary_role_id` |
| 80 | /admin.php pmusers POST save / department_id [create personnel] | ADMIN | department | `department_id` |

## Ngoài hoặc chưa đầy đủ trong80 cặp

| Endpoint | Nhánh | Tham số | Khoảng trống |
|---|---|---|---|
| admin.php op=pm | dashboard/profile/password/team | Không nhận target ID; actor/store từ session | Không phủ; cần role/session/account HTTP riêng |
| admin.php op=pmusers | save tạo user không department, status local hidden, role inactive/primary local | id, role_ids[], primary_role_id, department_id | 80 phủ foreign/missing ở vị trí ID; chưa phủ cùng tenant không thuộc quyền/định dạng/no-op |
| admin.php op=pmprojects | danh sách, local project nonmanager, task dưới dự án local khác, date-range/Save/Hide | project_id,task_id,member_id,manager_id,assignee_id | Có11controls chung; chưa phủ hết ownership/local relation/malformed/redirect |
| admin.php op=pmtimesheets | settings; task local của người khác/task local không tồn tại assignment; task hidden | id,task_id; settings không ID ngoài store session | Không phủ settings/task local unassigned/hidden; còn thiếu phân loại ba trường hợp task |
| admin.php op=pmaudit | list/filter | page; entity_type/action là enum, không nhận entity ID | Chỉ control200; chưa có foreign/missing ID vì endpoint không nhận ID |
| admin.php op=pmcosts và pm_ajax.php op=pmcosts | HTML/AJAX không nhận user/role filter; service dùng qua reports | project_id,page; user_id/role_code ở service qua reports | HTML/AJAX cost chỉ nhận from/to/project_id/page; user_id/role_code không được chuyển tới service. Service nhận chúng qua pmreports đã có matrix |
| admin.php op=pmallocations và pm_ajax.php op=pmallocations | Employee scope, project local nonmanager, task local unassigned | id,task_id,user_id,project_id; capacity id/user_id | 80 phủ foreign/missing ID nhưng không phủ mọi role/local ownership/malformed |
| admin.php op=pmreports | mode users/project filters không hỗ trợ; role scope ownership | project_id,user_id,page,role_code | 80 phủ các tổ hợp mode hợp lệ; không phủ invalid combination/role own/local project permission |
| admin.php op=pmimports | template/preview/stage/upload/history | department_id trong payload; import_id/source_row trong provision | template/preview/stage không có cặp matrix; source_row chưa phủ row ngoài batch; workbook reference scope chưa phủ |
| pm_ajax.php | unknown op/method/anonymous/CSRF/input array | GETop/POSTID/session store | Có suite khác; 80 không phủ router gate |
| admin.php login/logout; ajax.php legacy count; index.php | auth/public/legacy PM mode | session store/actor; identity không numeric ID | Không thuộc80; cần suite auth/legacy/session riêng |

`page` là phân trang, `source_row` là số dòng trong batch, `role_code` là enum; không coi chúng là ID entity độc lập. Tuy nhiên phải kiểm tra ownership của batch/dòng trước provision. Import payload department_id phải so cùng store ở validator. Ngày/giờ/hours không phải ID; P3 không đổi.

Không thể gọi matrix hiện tại là full tenant endpoint/role matrix: pmaudit read kiểm tra bằng control+fixture foreign; pm profile/password không có trong router fixture; cost user/role không phải tham số được endpoint cost nhận. Cần duyệt cách mở rộng test trước khi nâng1.3.
