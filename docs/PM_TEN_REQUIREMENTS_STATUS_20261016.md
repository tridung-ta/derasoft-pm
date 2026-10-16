# Đối chiếu 10 nhóm yêu cầu — 16/10/2026

## Căn cứ và kết luận

Dùng bảng “Đối chiếu 10 nhóm yêu cầu” trong PHASE11_REQUIREMENTS.md, PROJECT_BRIEF.md và các quyết định sửa đổi đã chốt. Không tính cron vào 10 nhóm. Cả 10 nhóm đã có chức năng và được kiểm thử trong phạm vi hiện hành; không có nhóm nào chưa xây dựng. Không đồng nghĩa 100% tiêu chí nghiệm thu đã được xác nhận.

Người dùng xác nhận ngày16/10 đã upload thành công gói giao diện Midnight Gold lấy từ bef2d87. Đây là xác nhận của người dùng; agent không trực tiếp truy cập host hoặc chứng minh mọi chức năng trên production.

## Bảng chức năng

| STT | Nhóm yêu cầu | Phần đã xây dựng | Căn cứ |
| --- | --- | --- | --- |
| 1 | Đăng nhập/phân quyền | Username/email, mật khẩu hash, nhiều role, quyền/ownership/tenant, session và logout POST | Auth/identity/session/role HTTP và tenant matrix trong regression |
| 2 | Nhân sự/phòng ban/vai trò/đơn giá | Thêm/sửa/tìm kiếm/phân trang, khóa/ẩn/khôi phục, role và đơn giá theo ngày hiệu lực | Users CRUD, rates, departments, role batch và HTTP |
| 3 | Dự án/công việc | Dự án, khách hàng, thành viên/vai trò dự án, ngày/ưu tiên/trạng thái, Kanban và xóa mềm | Projects, task status HTTP, Kanban/browser |
| 4 | Chấm công/OT/nhật ký | Ghi/sửa/ẩn, cửa sổ sửa, tổng giờ ngày và OT, snapshot đơn giá/cost, audit actor/time/trước-sau theo phạm vi | Timesheets/window/daily limit/audit/account audit tests |
| 5 | Chi phí/biểu đồ | Actual/estimate/budget, nhóm task/dự án/phòng ban/role; cảnh báo thiếu đơn giá/khác tiền tệ, polling | Costs service/HTTP/browser/missing-rate |
| 6 | Phân bổ nguồn lực | Lịch tuần, capacity, tạo/sửa/ẩn, vượt tải/trùng giờ, gợi ý người còn trống | Allocations/concurrency/HTTP/task estimate/browser |
| 7 | Báo cáo | Giờ/OT theo tuần, task hoàn thành/trễ/quá hạn, bộ lọc và phân nhóm | Reports HTTP/service và Phase11 browser |
| 8 | Excel/import/export | XLSX các loại báo cáo/nhân sự/dự án, Preview/staging/Apply tiếp tục, xử lý trùng/lỗi từng dòng, cấp mật khẩu tài khoản inactive | Import Apply/native1062/HTTP, Excel round-trip, tabs/password/browser |
| 9 | Bootstrap/responsive/UI | Bootstrap và Inter tự host, desktop/mobile, form/tab/menu/toast, Midnight Gold, focus và reduced motion | 47 browser suites, 58 review renders và form contracts |
| 10 | SQL/bảo mật/tối ưu | Prepared queries, CSRF/escape/tenant/IDOR, giới hạn upload/phân trang, EXPLAIN, giữ engine và dữ liệu legacy | Tenant/required fields/identity/upload/EXPLAIN và regression |

Báo cáo thống kê thay vì tỷ lệ hiệu suất theo quyết định đã chốt. Role/phòng ban/assignment dùng phân loại hiện tại; không tự tái dựng lịch sử chưa lưu. Task legacy không có completed_at không được gán ngày hoàn thành giả. Import giữ MyISAM, Apply từng dòng có khả năng tiếp tục; không hứa atomic toàn file.

## Bằng chứng mới nhất

Lượt kiểm thử16/10 được ghi trong PM_UI_BE_ACTION_RETEST_20261016.md:66 regression scripts PASS,47 browser suites PASS,157 ca PM/HR controller HTTP fixture PASS,14 template contracts PASS. Đối chiếu96 bảng local không thay đổi dữ liệu; dc_users giữ MyISAM. Không dùng số suite để thay thế số tiêu chí checklist.

## Các mục nghiệm thu chi tiết còn mở

Checklist80 mục trong TEST_REPORT_FULL.md có trạng thái ghi nhận73 PASS /0 FAIL /7 CHƯA CHẠY. Lượt kiểm thử mới chưa ánh xạ đầy đủ để tự nâng7 mục sau:

- 2.3: đủ menu/quyền qua đăng nhập tài khoản thật cả4 role; PM/HR HTTP fixture không thay login UAT.
- 2.6: hết hạn phiên thực theo thời gian/server-side expiration.
- 3.6: hai request đơn giá đồng thời thật; concurrency phân bổ không thay concurrency đơn giá.
- 9.2: browser zoom thật200%; text scaling không tương đương.
- 9.3: keyboard-only mọi hành động trên toàn ứng dụng.
- 9.5: thao tác đầy đủ mọi form/module trong phiên thật dưới CSP; fixture không thay toàn bộ.
- 10.3: toàn bộ cảnh báo động/state/pseudo-element; các mẫu tương phản đạt không đủ chứng nhận mọi trạng thái.

Người dùng đã xác nhận nhiều tình huống host trong cuộc trò chuyện, bao gồm import, phân bổ, OT/đơn giá, phạm vi tài khoản và upload UI. Giữ các xác nhận đúng phạm vi; chưa có bản kết quả từng mục để đóng toàn bộ7 tiêu chí. Không ghi một lỗi cũ đã sửa thành lỗi hiện tại, cũng không tự xóa các giới hạn kiểm chứng.

## Cron — cải tiến ngoài 10 nhóm

Nhắc hạn/quá hạn/thiếu chấm công, thông báo trong web, phạm vi all-users để user mới tự được xét, tránh command theo ID cố định và chống thông báo trùng là phần bổ sung. Command lịch được cấu hình một lần cho scope đã chọn; không tạo cron hệ điều hành riêng cho mỗi user.

Tách nghiệm thu cron cơ bản khỏi policy throttle012. Bản policy012 có unit/contract tests; tài liệu PM_CRON_THROTTLE_BUILD_VERIFY_20261014.md vẫn để native SQL/concurrency riêng của phiên bản này chưa đủ bằng chứng. Không lấy kiểm thử cron bản cũ hoặc phản hồi thông báo đúng để tự kết luận toàn bộ policy012 PASS. Các mục này không làm thay đổi mẫu số10 yêu cầu gốc.

Không chạy lại test hoặc truy cập production trong lượt đối chiếu tài liệu này. Không thay code runtime, database, cron hay tự push/merge/deploy.
