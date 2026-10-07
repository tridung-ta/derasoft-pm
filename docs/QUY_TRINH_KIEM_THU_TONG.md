# Quy trình kiểm thử tổng — PM Timesheet

Gộp toàn bộ ràng buộc đã chốt qua các phase (0–10 + 9b) thành 1 quy trình kiểm thử có thứ
tự, tiêu chí pass/fail rõ ràng và nguồn gốc ràng buộc (để khi fail biết quay lại hỏi phase
nào). Dùng trước khi coi bất kỳ phase/bước nào là "hoàn thiện" thật sự — không chỉ dựa vào
báo cáo "đã VERIFY" bằng lời.

**Nguyên tắc chạy:** mỗi nhóm bên dưới có tiêu chí PASS cụ thể. Agent báo PASS chỉ khi đã
thực sự chạy (có lệnh/output kèm theo), không suy luận "chắc đúng". Test nào không chạy
được (thiếu công cụ, thiếu môi trường) phải ghi rõ "CHƯA CHẠY", không bỏ qua im lặng.

---

## 0. Validate dữ liệu đầu vào (bổ sung sau khi phát hiện qua ảnh thực tế)

| # | Test case | Input | Kỳ vọng | Nguồn |
|---|---|---|---|---|
| 0.1 | Tạo user không chọn phòng ban | Để trống department | Xác nhận: có bắt buộc không? Nếu không bắt buộc, phải hiện rõ "Chưa gán phòng ban" ở UI (đã đúng theo ảnh) — nhưng cần xác nhận đây là **thiết kế có chủ đích**, không phải thiếu validate | Phase 3 |
| 0.2 | Tạo user không gán role nào | Để trống role | Xác nhận: user không role có đăng nhập được không? Nếu được, mọi `requirePermission()` phải fail-closed (từ chối mọi action) — test thử đăng nhập bằng user này, xác nhận bị chặn mọi nơi, không crash | Phase 2, 3 |
| 0.3 | Họ tên/email chỉ gồm ký tự ngẫu nhiên vô nghĩa | `akjshdkjasdkj`, `123123123@gmail.vn` | Xác nhận: hệ thống có nên validate thêm (ví dụ cảnh báo email trông như test data) hay chấp nhận theo đúng format email chuẩn là đủ? Nếu chấp nhận, đây không phải lỗi — chỉ cần xác nhận có chủ đích | Phase 3 |
| 0.4 | Toàn bộ form input chính (user/project/task) | Submit với field bắt buộc để trống | Lỗi validate rõ ràng theo từng field, không submit được, không crash | Toàn hệ thống |
| 0.5 | Input XSS cơ bản vào các field text | `<script>alert(1)</script>` vào họ tên/mô tả | Hiển thị ra là text thường (escaped), không thực thi script | Phase 9 |
| 0.6 | SQL injection cơ bản vào ô tìm kiếm | `' OR 1=1 --` | Không trả về dữ liệu ngoài phạm vi, không lỗi SQL lộ ra | Phase 9 |

---

## 1. Tenant / store_id (ràng buộc nền tảng — nếu sai, mọi test sau vô nghĩa)

| # | Test case | Kỳ vọng | Nguồn |
|---|---|---|---|
| 1.1 | Mọi bảng `pm_*` có cột `store_id` | Kiểm tra `SHOW CREATE TABLE` từng bảng | Quyết định tenant |
| 1.2 | Mọi unique key liên quan dữ liệu tenant có gồm `store_id` | Kiểm tra index | Quyết định tenant |
| 1.3 | Đổi ID trên URL/param sang resource của store khác (project_id, task_id, user_id, timesheet_id, allocation_id) | Bị từ chối (403/404), không lộ dữ liệu | Phase 2–8, mọi endpoint |
| 1.4 | Query tổng hợp (cost, report) không lọc store_id từ input mà từ session | Thử truyền `store_id` giả vào request | Bị bỏ qua, luôn dùng tenant của session đăng nhập | Phase 6, 8 |

## 2. Auth & RBAC

| # | Test case | Kỳ vọng | Nguồn |
|---|---|---|---|
| 2.1 | Login bằng username hoặc email | Cả 2 đều đăng nhập được | Quyết định đã chốt |
| 2.2 | Tài khoản cũ mật khẩu MD5 | Login thành công, hash tự nâng cấp sang `password_hash()` sau khi login | Phase 2 |
| 2.3 | 4 role Admin/PM/HR/Employee — mỗi role chỉ thấy đúng menu | Kiểm tra menu từng role | Phase 2 |
| 2.4 | Backend kiểm tra quyền độc lập với menu | Gọi thẳng URL/endpoint bị ẩn trên menu bằng role không đủ quyền | 403, không chỉ ẩn ở UI | Phase 2 |
| 2.5 | User soft-delete / khóa không đăng nhập được | Thử login | Từ chối | Phase 3 |
| 2.6 | Session regenerate khi login, hết hạn đúng lúc | Kiểm tra session ID đổi sau login | Phase 9 |
| 2.7 | Đổi quyền user giữa lúc đang có phiên đăng nhập | Session cũ có phản ánh quyền mới không, hay phải login lại | Phase 9 |

## 3. User, Role, Hourly Rate

| # | Test case | Kỳ vọng | Nguồn |
|---|---|---|---|
| 3.1 | User có nhiều role, có rate riêng | `resolveRate()` lấy đúng rate user | Phase 3 |
| 3.2 | User nhiều role, không có rate riêng | Lấy rate của **role chính** (`is_primary=1`), KHÔNG lấy role cao nhất | Quyết định đã chốt |
| 3.3 | User nhiều role, chưa có role chính | Trả fallback (rate 0) kèm cảnh báo, không tự chọn ngầm | Phase 3 |
| 3.4 | Rate hết hiệu lực (`effective_to` < ngày chấm công) | Không được chọn | Phase 3 |
| 3.5 | Tạo rate mới `effective_to = NULL` đè lên rate cũ có `effective_to` xác định | **Phải bắt được chồng lấn** (đây là ca đã từng bị bug ở công thức ban đầu — bắt buộc test lại) | Phase 3 (đã sửa) |
| 3.6 | 2 request ghi rate đồng thời cho cùng user/role | Không tạo ra 2 rate chồng lấn nhau (race condition) — xác nhận có `SELECT ... FOR UPDATE` | Phase 3 |
| 3.7 | `user_id` và `role_id` cùng có giá trị hoặc cùng NULL khi tạo rate | Bị từ chối ở application layer (`validateRateOwner`) | Phase 3 |
| 3.8 | Soft delete user | Không bị xóa cứng, không hiện trong danh sách mặc định | Phase 3 |
| 3.9 | Email trùng trong cùng `store_id` | Bị chặn | Phase 3 |
| 3.10 | Tìm kiếm/phân trang user | Theo tên/email/phòng ban/role, không lấy user đã soft-delete | Phase 3 |

## 4. Project & Task

| # | Test case | Kỳ vọng | Nguồn |
|---|---|---|---|
| 4.1 | PM A truy cập/sửa project của PM B qua đổi ID | Bị từ chối | Phase 4 |
| 4.2 | Gán task cho user không phải member dự án | Bị từ chối | Phase 4 |
| 4.3 | Employee chỉ thấy task của mình | Xác nhận danh sách lọc đúng | Phase 4 |
| 4.4 | Đổi trạng thái Kanban qua AJAX | Lưu đúng, audit ghi lại | Phase 4 |
| 4.5 | Soft delete project/task | Giữ dữ liệu lịch sử, không hiện mặc định | Phase 4 |

## 5. Timesheet, OT, Audit

| # | Test case | Kỳ vọng | Nguồn |
|---|---|---|---|
| 5.1 | Chấm công 8h trong ngày | regular=8, OT=0 | Phase 5 |
| 5.2 | Đã có 8h, thêm 2h nữa cùng ngày | Dòng mới regular=0, OT=2 | Phase 5 |
| 5.3 | Sửa 1 dòng cũ làm tổng giờ ngày giảm | **Toàn bộ các dòng còn lại trong ngày được tính lại** theo đúng thứ tự deterministic đã quy định | Phase 5 |
| 5.4 | Xóa 1 dòng | Các dòng còn lại tính lại đúng | Phase 5 |
| 5.5 | Đổi hourly rate sau khi đã có timesheet cũ | `cost` của timesheet cũ KHÔNG đổi (dựa `rate_snapshot`) | Phase 5 |
| 5.6 | Sửa/xóa timesheet sau 3 ngày kể từ `work_date` | Bị khóa, chỉ Admin sửa được (có audit riêng cho thao tác sau khóa) | Quyết định đã chốt (không workflow duyệt) |
| 5.7 | Mọi thao tác tạo/sửa/xóa timesheet | Có dòng audit log tương ứng, không chứa password/token | Phase 5 |

## 6. Cost & Chart

| # | Test case | Kỳ vọng | Nguồn |
|---|---|---|---|
| 6.1 | Actual cost = SUM(cost) từ timesheet, KHÔNG tính lại từ rate hiện tại | Xác nhận công thức | Phase 6 |
| 6.2 | Task soft-delete nhưng có timesheet cũ | Vẫn tính vào actual cost, có nhãn "lịch sử" | Phase 6 |
| 6.3 | Dữ liệu có nhiều `currency` khác nhau trong cùng tập hợp SUM | Trả cảnh báo mixed currency, không cộng dồn sai | Phase 6 |
| 6.4 | Estimate (`estimated_hours × rate`) hiển thị kèm nhãn "ước tính tại thời điểm xem" | Không bị hiểu nhầm là cam kết ngân sách | Phase 6 |
| 6.5 | HR/Employee truy cập endpoint costs (kể cả polling) | Bị từ chối (chưa mở phạm vi HR tài chính) | Phase 6 |
| 6.6 | Dữ liệu rỗng trong khoảng lọc | Ẩn canvas chart, hiện thông báo rõ — KHÔNG vẽ trục trống | Phase 9b bước 5 |
| 6.7 | JOIN qua bảng N-N (user-role / project-member) khi SUM chi phí | Không bị nhân đôi giá trị (double-counting) | Phase 6 |

## 7. Resource Allocation & Overbooking

| # | Test case | Kỳ vọng | Nguồn |
|---|---|---|---|
| 7.1 | Tổng allocation tuần = đúng `weekly_limit_hours` (vd 40/40) | KHÔNG cảnh báo (bằng ngưỡng không tính quá tải) | Phase 7 |
| 7.2 | Tổng allocation tuần > `weekly_limit_hours` | Cảnh báo overbooking | Phase 7 |
| 7.3 | Tổng allocation 1 ngày > giới hạn ngày | Cảnh báo riêng (daily overload), không gộp chung với weekly | Phase 7 |
| 7.4 | Timesheet thực tế trên 1 task > `estimated_hours` của task | Cảnh báo "task overload" — **phải là cảnh báo riêng biệt**, không trộn với overbooking | Phase 7 |
| 7.5 | Allocation 2 khoảng giờ liền kề [8:00-12:00) và [12:00-16:00) | KHÔNG coi là trùng (nửa mở) | Phase 7 |
| 7.6 | Allocation chồng lấn giờ một phần | Phát hiện đúng | Phase 7 |
| 7.7 | Gợi ý capacity còn trống | Chỉ là gợi ý, KHÔNG tự ghi/đổi assignee | Phase 7 |
| 7.8 | User có nhiều role/membership khi tính tổng tải | Không bị nhân đôi do JOIN | Phase 7 |
| 7.9 | PM xem tải nhân sự thuộc dự án ngoài phạm vi mình | Chỉ thấy tổng giờ/busy, KHÔNG lộ tên/task/chi phí dự án ngoài quyền | Phase 7 |

## 8. Reports & Excel

| # | Test case | Kỳ vọng | Nguồn |
|---|---|---|---|
| 8.1 | Export giờ cá nhân/nhóm | Số liệu khớp dữ liệu nguồn, không double-count | Phase 8 |
| 8.2 | Export > 5000 dòng | Yêu cầu thu hẹp filter, KHÔNG âm thầm cắt bớt | Phase 8 |
| 8.3 | Dữ liệu chứa ký tự bắt đầu bằng `=`, `+`, `-`, `@` | Export ra dạng text literal, không bị Excel hiểu thành formula | Phase 8 |
| 8.4 | Import file có cả dòng đúng và dòng sai (trùng email, role không tồn tại) | Dòng đúng vẫn được xử lý, dòng sai báo lỗi theo dòng cụ thể | Phase 8 |
| 8.5 | Import file chứa macro/formula/external link | Bị chặn | Phase 8 |
| 8.6 | Apply import bị gián đoạn giữa chừng (giả lập crash) | Resume lại an toàn nhờ UNIQUE constraint trên `dc_users.email`, không tạo trùng | Phase 8, 10 |
| 8.7 | 2 Admin cùng bấm Apply 1 file đồng thời | Bị chặn bởi cờ `in_progress`, không xử lý trùng | Phase 8 |
| 8.8 | HR/Employee export report | Chỉ được phạm vi giờ cá nhân, không có route tài chính | Phase 8 |

## 9. UI/UX, Performance, Security

| # | Test case | Kỳ vọng | Nguồn |
|---|---|---|---|
| 9.1 | N+1 batch role lookup trang Nhân sự | Kết quả role/role chính khớp chính xác với cách cũ (so sánh từng user, không chỉ "không lỗi") | Phase 9 |
| 9.2 | Desktop/mobile 360/390/768/1440px, zoom 200% | Không overflow, đọc được | Phase 9 |
| 9.3 | Keyboard-only navigation, focus-visible | Thao tác được toàn bộ không cần chuột | Phase 9 |
| 9.4 | Trạng thái polling khi tab ẩn | Dừng polling, tiếp tục khi tab hiện lại | Phase 6, 7, 9 |
| 9.5 | CSP sau khi siết | Không chặn Smarty/Chart.js/form hiện có | Phase 9 |

## 10. UI Theme (Phase 9b — token mới)

| # | Test case | Kỳ vọng | Nguồn |
|---|---|---|---|
| 10.1 | Nút hành động thường vs nút Khóa/Xóa | Màu khác biệt rõ rệt (`#18181b` vs `#dc2626`), không trùng | Phase 9b bước 1 |
| 10.2 | Contrast nút nguy hiểm | `#dc2626` nền + chữ `#ffffff` ≥ 4.5:1 (đã tính = 4.83:1) — KHÔNG dùng `#fef2f2` | Phase 9b bước 1 |
| 10.3 | `#d97706` dùng làm chữ nhỏ trên nền trắng | KHÔNG xuất hiện ở đâu (chỉ 3.19:1, dưới ngưỡng) — chỉ dùng làm nền/viền | Phase 9b |
| 10.4 | Khóa vs Mở khóa, Ẩn vs Khôi phục | Chỉ chiều "khóa/ẩn" dùng màu nguy hiểm, chiều ngược lại dùng màu thường | Phase 9b bước 1 |
| 10.5 | Toàn bộ trang PM dùng 1 file CSS chung (`css/pmui.css`) | Không có file CSS thứ 2 chồng chéo | Phase 9b |
| 10.6 | Lưới tuần trang Phân bổ | Hàng = nhân viên, cột = Thứ 2→CN, ô vượt ngưỡng tô `#d97706` | Phase 9b bước 4 |
| 10.7 | Grid card trang Dự án | Badge trạng thái đúng màu theo trạng thái, % tiến độ hiển thị đúng công thức đã duyệt riêng (không tự chế) | Phase 9b bước 6 |

## 11. Cross-cutting — chạy sau cùng, bao trùm nhiều phase

| # | Test case | Kỳ vọng | Nguồn |
|---|---|---|---|
| 11.1 | Regression toàn bộ Phase 2–9b sau mỗi thay đổi lớn | `./tests/pm_regression.ps1` pass | Toàn dự án |
| 11.2 | PHP 8.3 lint toàn bộ file đã đổi | Không lỗi syntax | Toàn dự án |
| 11.3 | Không có file config/secret/.env trong diff chuẩn bị commit | `git diff --name-only` kiểm tra thủ công | Phase 1, 10 |
| 11.4 | Log/audit không chứa password/hash/token/PII thô | Kiểm tra nội dung log mẫu | Phase 3, 5, 9 |
| 11.5 | Toàn bộ fixture test (user/project/task giả) bị dọn sau khi test xong | Không còn sót trong DB, đặc biệt `dc_users` (MyISAM, không rollback được) | Phase 8, 10 |

---

## Cách dùng

1. Agent chạy theo thứ tự nhóm 0 → 11 (nhóm 1 "Tenant" chặn trước tiên — nếu fail, dừng
   toàn bộ, vì mọi test sau đều dựa trên giả định scoping đúng).
2. Mỗi test case ghi PASS / FAIL / CHƯA CHẠY, kèm bằng chứng (lệnh đã chạy, ảnh chụp nếu
   là UI, hoặc link tới đoạn code xử lý).
3. Test FAIL → quay lại đúng phase ghi ở cột "Nguồn", sửa đúng phạm vi đó, không sửa lan.
4. Sau khi toàn bộ PASS, mới coi là sẵn sàng cho UAT thật (người dùng tự làm, không phải
   Agent tự công nhận) theo đúng tài liệu Phase 10 đã chuẩn bị.

**Prompt gửi Agent:**

```text
Chạy Quy trình kiểm thử tổng (QUY_TRINH_KIEM_THU_TONG.md) theo đúng thứ tự nhóm 0 → 11.
Dừng ngay nếu nhóm 1 (Tenant/store_id) fail. Với mỗi test case, ghi PASS/FAIL/CHƯA CHẠY
kèm bằng chứng cụ thể (lệnh đã chạy + output, hoặc mô tả cách kiểm tra UI). Không tự suy
luận PASS nếu chưa thực sự chạy. Test nào fail, nêu rõ cần quay lại sửa ở đâu (dựa cột
Nguồn) trước khi tiếp tục nhóm sau. Báo cáo kết quả đầy đủ vào docs/TEST_REPORT_FULL.md,
không merge bất kỳ nhánh nào cho tới khi nhóm 0–10 đều PASS hoặc có lý do CHƯA CHẠY rõ ràng.
```