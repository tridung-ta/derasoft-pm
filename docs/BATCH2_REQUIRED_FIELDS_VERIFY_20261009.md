# Lô 2 — 0.3/0.4, 09/10/2026

Lệnh thực chạy: `.tools/php83/php.exe tests/pm_required_fields_http_smoke.php`; exit 1.

Output: `RESULT 0.3/0.4: 7 PASS / 14 FAIL; 21 comparisons.` và `PASS cleanup: 21 persistent tables unchanged; owned server stopped; temporary fixtures destroyed.`

0.3 PASS: POST nhân sự thật qua controller nhận tên `akjshdkjasdkj`, email `123123123@gmail.vn`; observer đọc dòng trong mirror xác nhận đúng cả hai giá trị. Status200, có đúng1tracking,0audit; fingerprint mirror đổi đúng do tạo fixture, bảng thật không đổi. Không xác minh hộp thư tồn tại, đúng quyết định gốc.

0.4 FAIL: mọi ca trống đều được chặn HTTP200, không ghi/audit/tracking; 14 ca thông báo gộp hoặc không chỉ rõ trường. Không báo lỗi lưu trái phép. Admin để trống manager_id đã bị chặn, có thông báo người phụ trách rõ ràng, giữ nguyên hành vi. Tiêu chí message nhận diện trường đúng, không nhắc trường khác thay cho lỗi; không bắt buộc câu chữ chính xác.

| Case | Kết quả | HTTP | Output/thông báo |
|---|---|---|---|
| 0.3 accepted random-looking fullname and syntactically valid email | PASS | 200 | Dữ liệu tên/email fixture được lưu đúng |
| 0.4 pmusers save create empty username | FAIL | 200 | Username hoặc mật khẩu không hợp lệ/trùng. |
| 0.4 pmusers save create empty password | PASS | 200 | Vui lòng nhập mật khẩu. |
| 0.4 pmusers save create empty fullname | FAIL | 200 | Họ tên hoặc email không hợp lệ. |
| 0.4 pmusers save create empty email | PASS | 200 | Email không đúng định dạng hoặc vượt quá 50 ký tự. |
| 0.4 pmusers save create empty weekly_limit_hours | PASS | 200 | Giới hạn giờ/tuần không hợp lệ. |
| 0.4 pmusers save edit #900000041 empty fullname | FAIL | 200 | Họ tên hoặc email không hợp lệ. |
| 0.4 pmusers save edit #900000041 empty email | FAIL | 200 | Họ tên hoặc email không hợp lệ. |
| 0.4 pmusers save edit #900000041 empty weekly_limit_hours | PASS | 200 | Giới hạn giờ/tuần không hợp lệ. |
| 0.4 pmprojects project_save edit #900000001 empty code | FAIL | 200 | Tên, mã hoặc mô tả dự án không hợp lệ. |
| 0.4 pmprojects project_save edit #900000001 empty name | FAIL | 200 | Tên, mã hoặc mô tả dự án không hợp lệ. |
| 0.4 pmprojects project_save edit #900000001 empty manager_id | PASS | 200 | Người phụ trách phải là Admin/PM đang hoạt động trong tenant. |
| 0.4 pmprojects project_save edit #900000001 empty budget | FAIL | 200 | Số tiền hoặc số giờ không hợp lệ. |
| 0.4 pmprojects project_save create empty code | FAIL | 200 | Tên, mã hoặc mô tả dự án không hợp lệ. |
| 0.4 pmprojects project_save create empty name | FAIL | 200 | Tên, mã hoặc mô tả dự án không hợp lệ. |
| 0.4 pmprojects project_save create empty manager_id | PASS | 200 | Người phụ trách phải là Admin/PM đang hoạt động trong tenant. |
| 0.4 pmprojects project_save create empty budget | FAIL | 200 | Số tiền hoặc số giờ không hợp lệ. |
| 0.4 pmprojects task_save edit #900000011 empty name | FAIL | 200 | Thông tin task không hợp lệ. |
| 0.4 pmprojects task_save edit #900000011 empty estimated_hours | FAIL | 200 | Số tiền hoặc số giờ không hợp lệ. |
| 0.4 pmprojects task_save create empty name | FAIL | 200 | Thông tin task không hợp lệ. |
| 0.4 pmprojects task_save create empty estimated_hours | FAIL | 200 | Số tiền hoặc số giờ không hợp lệ. |

Nguồn cần sửa: Phase3 `modules/admin/pmusers.module.php` kiểm tra username/fullname/email gộp, trước DAO validator; Phase4 `classes/services/pmprojectservice.class.php` saveProject/saveTask/number trả message gộp khi name/code/budget/estimated_hours rỗng. Template primary entity required fields được đọc để tạo cả case create/edit; weekly_limit_hours thêm theo backend. Không tuyên bố đã bao phủ các form phụ role/rate/member trong mục này.

Bằng chứng raw giữ local: `.local/required-fields-command-20261009.txt`, `.local/required-fields-http-20261009.json`. Các bảng hash trước/sau bằng nhau; server do suite tạo đã dừng. Code sản phẩm không sửa. Probe test chưa thêm vào regression/commit vì FAIL còn mở.

Dừng lô2 tại0.4, chờ PLAN sửa. 2.2/3.10/5.5/8.3 chưa thực hiện bổ sung assertions; lô3 9.5/10.3 chưa chạy. Không nâng từ regression cũ.


## Sau PLAN được duyệt và BUILD — trạng thái hiện tại PASS

Người dùng đã duyệt PLAN_REQUIRED_FIELDS_20261009.md. Chạy targeted và regression sau sửa: `PHP tests/pm_required_fields_http_smoke.php` exit0:30 PASS/0 FAIL; `powershell.exe -NoProfile -ExecutionPolicy Bypass -File tests/pm_regression.ps1` exit0:`PASS:59 regression scripts. No migrations applied by this runner.` Matrix80/80+11controls+35behaviors, roleHTTP157, no-role98, forged-store36 đều PASS trong regression. Output .local/required-fields-regression-20261009.txt. Không coi log trướcBUILD còn tại đường .local có thể được ghi đè là failure hiện tại: bảng21case ở trên đã chụp từ lượtFAIL thực trước sửa, giữ làm lịch sử.

| Case sau sửa | Kết quả | HTTP | Thông báo/observer |
|---|---|---|---|
| 0.3 accepted random-looking fullname and syntactically valid email | PASS | 200 | Thao tác hợp lệ / observer xác nhận fixture |
| 0.4 pmusers save create empty username | PASS | 200 | Vui lòng nhập tên đăng nhập. |
| 0.4 pmusers save create empty password | PASS | 200 | Vui lòng nhập mật khẩu. |
| 0.4 pmusers save create empty fullname | PASS | 200 | Vui lòng nhập họ và tên. |
| 0.4 pmusers save create empty email | PASS | 200 | Vui lòng nhập email. |
| 0.4 pmusers save create empty weekly_limit_hours | PASS | 200 | Giới hạn giờ/tuần không hợp lệ. |
| 0.4 pmusers save edit #900000041 empty fullname | PASS | 200 | Vui lòng nhập họ và tên. |
| 0.4 pmusers save edit #900000041 empty email | PASS | 200 | Vui lòng nhập email. |
| 0.4 pmusers save edit #900000041 empty weekly_limit_hours | PASS | 200 | Giới hạn giờ/tuần không hợp lệ. |
| 0.4 pmprojects project_save edit #900000001 empty code | PASS | 200 | Vui lòng nhập mã dự án. |
| 0.4 pmprojects project_save edit #900000001 empty name | PASS | 200 | Vui lòng nhập tên dự án. |
| 0.4 pmprojects project_save edit #900000001 empty manager_id | PASS | 200 | Người phụ trách phải là Admin/PM đang hoạt động trong tenant. |
| 0.4 pmprojects project_save edit #900000001 empty budget | PASS | 200 | Vui lòng nhập ngân sách dự án. |
| 0.4 pmprojects project_save create empty code | PASS | 200 | Vui lòng nhập mã dự án. |
| 0.4 pmprojects project_save create empty name | PASS | 200 | Vui lòng nhập tên dự án. |
| 0.4 pmprojects project_save create empty manager_id | PASS | 200 | Người phụ trách phải là Admin/PM đang hoạt động trong tenant. |
| 0.4 pmprojects project_save create empty budget | PASS | 200 | Vui lòng nhập ngân sách dự án. |
| 0.4 pmprojects task_save edit #900000011 empty name | PASS | 200 | Vui lòng nhập tên công việc. |
| 0.4 pmprojects task_save edit #900000011 empty estimated_hours | PASS | 200 | Vui lòng nhập giờ ước tính công việc. |
| 0.4 pmprojects task_save create empty name | PASS | 200 | Vui lòng nhập tên công việc. |
| 0.4 pmprojects task_save create empty estimated_hours | PASS | 200 | Vui lòng nhập giờ ước tính công việc. |
| 0.4 control all required user fields | PASS | 200 | Vui lòng nhập email. Vui lòng nhập mật khẩu. Vui lòng nhập tên đăng nhập. Vui lòng nhập họ và tên. |
| 0.4 control whitespace username | PASS | 200 | Vui lòng nhập tên đăng nhập. |
| 0.4 control whitespace project name | PASS | 200 | Vui lòng nhập tên dự án. |
| 0.4 control whitespace estimate | PASS | 200 | Vui lòng nhập giờ ước tính công việc. |
| 0.4 control zero budget | PASS | 303 | Thao tác hợp lệ / observer xác nhận fixture |
| 0.4 control zero estimate | PASS | 200 | Thao tác hợp lệ / observer xác nhận fixture |
| 0.4 control negative budget still rejected | PASS | 200 | Số tiền hoặc số giờ không hợp lệ. |
| 0.4 control precision estimate still rejected | PASS | 200 | Số tiền hoặc số giờ không hợp lệ. |
| 0.4 control password and phone errors retained | PASS | 200 | Mật khẩu phải có ít nhất 8 ký tự. Điện thoại chỉ được chứa chữ số từ 0 đến 9, không có chữ, khoảng trắng hoặc ký tự đặc biệt. |

Review: permission/ownership/CSRF đứng nguyên vị trí; không đổi status200/303/403/404, code date/redirect giữ nguyên; chỉ lỗi constant, output escaped theo template hiện có, không thêm SQL runtime/DDL hoặc đổi OT. Probe chỉ metadata keys/booleans và prepared statements trong exact-schema mirrors; 21 bảng thật không đổi. Trường hợp harness control zero-budget ban đầu kỳ vọng sai slash trước admin.php; thực tế redirect hợp lệ `admin.php?op=pmprojects`. Đã giữ artifact .local/required-fields-zero-control-first-20261009.json, sửa expectation đúng contract và chạy lại PASS; không sửa sản phẩm để qua test.
