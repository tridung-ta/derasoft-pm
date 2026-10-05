# UAT cuối bản local — người dùng thực hiện

UAT chưa thực hiện. Automated PASS không điền thay các ô dưới đây.
Version candidate: 0.10.0-local-rc1; nhánh feature/pm-phase10-test-release.
Chỉ dùng local và dữ liệu giả; không chạy trên production.

| Nhóm | Thao tác / kết quả mong đợi | Kết quả người dùng |
| --- | --- | --- |
| Auth / 4 role | Đăng nhập Admin/PM/HR/Employee; menu đúng quyền, đổi mật khẩu, logout bằng POST, refresh sau logout không còn dữ liệu private | Chưa test |
| Nhân sự / rates | Admin quản lý phòng ban, user, 2–3 role có đúng primary; user/role rate theo thời gian, overlap bị từ chối | Chưa test |
| Project / task | Admin tạo project và PM/members; PM tạo/sửa task own project, Kanban; Employee không quản lý, foreign project bị từ chối | Chưa test |
| Timesheet / OT | Employee ghi/sửa/xóa trong cửa sổ 3 ngày, total OT cả ngày và cost snapshot; quá 3 ngày khóa; Admin sửa sau khóa có audit riêng | Chưa test |
| Audit | Admin/PM/HR đúng scope; HR không thấy trường tiền bị redacted, Employee không xem audit; filter/pagination giữ dữ liệu | Chưa test |
| Costs | Admin/PM thấy đúng project/date, charts + bảng; mixed currency có cảnh báo không cộng sai; estimate có nhãn và missing rate/snapshot | Chưa test |
| Allocations | PM/Admin phân bổ tuần, exact/exceeded capacity, overlap/adjacent, time-bound overrides; Employee read-only own, HR không manage | Chưa test |
| Reports / XLSX | Giờ own/team đúng scope; HR/Employee không cost; XLSX mở và formula-like strings giữ dạng text | Chưa test |
| Import | Admin XLSX template/preview/errors, Apply/resume/duplicate + log, account inactive và cấp password; non-Admin bị chặn | Chưa test |
| Responsive / accessibility | 360/390/768/1440px, bàn phím/labels/focus, browser zoom 200%, empty/error, chart không tải vẫn đọc bảng | Chưa test |
| Session / polling | Poll 60s dừng khi tab ẩn; transient error giữ dữ liệu; 401/403 dừng polling, reload/login; permission change có hiệu lực | Chưa test |

Ghi lỗi bằng route, role, thao tác, kết quả mong đợi/thực tế; screenshot chỉ dữ liệu giả,
không gửi password/hash/token hoặc dump. Sau sửa, chạy lại ca liên quan và regression.

Nghiệm thu local: **chưa xác nhận**. Người kiểm tra: ______; ngày: ______;
version/commit: ______; lỗi còn mở: ______; quyết định: ______.
Sau nghiệm thu, người dùng xem DEPLOY_LOG.md rồi tự quyết định deployment thủ công.
Post-deploy smoke trong DEPLOY_LOG.md là checklist khác và chỉ được tick sau deploy.
