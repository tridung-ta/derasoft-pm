# UAT cuối bản local — người dùng thực hiện

Ngày 06/10/2026, người dùng xác nhận: “tôi đã nghiệm thu xong”. Ghi nhận nghiệm thu
tổng thể Phase 9b trên candidate đã bàn giao; chưa có bảng kết quả từng ca/role hoặc
ảnh UAT mới. Không suy ra từng ca dưới đây đã được chạy chỉ từ kiểm tra tự động.
Version candidate: 0.10.0-phase9b-local-rc1; nhánh feature/pm-phase9b-ui-theme.
Runtime đã BUILD: `d4cde62`; ghi thêm commit chuẩn bị phát hành khi bắt đầu UAT.
Chỉ dùng local và dữ liệu giả; không chạy trên production.

## Lượt nghiệm thu bổ sung Phase 9b — 06/10/2026

Chín bước BUILD và kiểm tra tự động đã đạt; nghiệm thu tổng thể được người dùng
xác nhận 06/10/2026. Các ô dưới đây chờ ghi kết quả chi tiết của người kiểm tra.
Mở app thật tại `http://127.0.0.1:18770/admin.php` sau khi chạy từ repository:

```powershell
.tools/php83/php.exe -S 127.0.0.1:18770 -t .
```

Chỉ khởi chạy khi cấu hình đang trỏ DB local đã sao lưu. Các file `.local/*.html`
là fixture giả lập, không dùng chúng để lưu dữ liệu hoặc nghiệm thu nghiệp vụ.
Đăng nhập bằng tài khoản local của bạn; không đưa mật khẩu vào tài liệu/ảnh.

| Thứ tự / trang | Luồng cần kiểm tra | Kết quả người dùng |
| --- | --- | --- |
| 1. Tổng quan | Đăng nhập; sidebar ổn định giữa các trang; KPI không lộ số ngoài quyền; lối tắt mở đúng chức năng | Chưa gh kết quả ca |
| 2. Nhân sự | Thêm/sửa nhân sự giả qua modal; Tab/Escape/trả focus; Khóa khác Mở khóa, Xóa khác Khôi phục; xác nhận hiện có còn hoạt động | Chưa gh kết quả ca |
| 3. Dự án & công việc | Mở form thu gọn; tạo dự án/task giả; card mở đúng dự án; tiến độ thay đổi theo task hoàn thành; dự án không task không giả 0% | Chưa gh kết quả ca |
| 4. Chấm công | Ghi 9 giờ rồi sửa còn 3 giờ; dải tuần phản ánh giờ đã lưu, ngày/nhân sự đúng; Admin sửa người khác không trộn tổng; lịch sử/OT/snapshot giữ đúng | Chưa gh kết quả ca |
| 5. Nhật ký | Thay đổi vừa làm có trong scope; filter/pager; mở trước/sau ngay trong bảng bằng chuột/bàn phím; trường tiền bị che đúng quyền | Chưa gh kết quả ca |
| 6. Chi phí | Dữ liệu vừa ghi phản ánh đúng tiền; thiếu rate có cảnh báo; tab dự án/nhóm; không dữ liệu có thông báo thay chart trục trống; polling không mất tab | Chưa gh kết quả ca |
| 7. Phân bổ | Lưới tuần đúng người/ngày; thêm/sửa/ẩn allocation giả; vượt tải/trùng giờ có chữ cảnh báo; Employee chỉ thấy phạm vi mình | Chưa gh kết quả ca |
| 8. Báo cáo | Lọc dữ liệu vừa tạo; XLSX cạnh tiêu đề tải/mở đúng nội dung và filter; tài khoản không có quyền export/cost không thấy thao tác tương ứng | Chưa gh kết quả ca |
| 9. Import | Tải mẫu → Preview/staging → mở lịch sử → Apply/resume với dữ liệu giả; kết quả còn hiển thị; duplicate không sửa tài khoản cũ; chỉ inactive được cấp password | Chưa gh kết quả ca |

Trên cả 9 trang: resize 360/390/768/1440px, browser zoom thật 200%, Tab/Shift+Tab,
focus/labels, không tràn trang; nút thường tối và nút nguy hiểm đỏ; Inter tải local,
chữ Việt đầy đủ; giờ/tiền căn phải và tabular. Bảng rộng được cuộn trong vùng riêng.
Mở Console kiểm tra lỗi mới trong phiên **đã đăng nhập**, không suy từ fixture polling.
Kết thúc bằng logout rồi refresh, không còn truy cập dữ liệu riêng.

Automated HTTP hiện có Admin/Employee; PM/HR được kiểm bằng service/permission fixture,
chưa thay cho UAT bằng tài khoản thật. Nếu chưa có tài khoản local phù hợp, ghi “chưa
kiểm” và thống nhất việc chuẩn bị tài khoản trước khi đánh dấu toàn bộ 4 role đạt.
Không tự tạo/thay đổi tài khoản production để phục vụ UAT.

Ảnh sau giả lập: [screenshots/after/README.md](screenshots/after/README.md).
Ảnh trước gốc còn thiếu: [screenshots/before/README.md](screenshots/before/README.md).
Ảnh giả lập không thay thế xác nhận thị giác/nghiệp vụ của người dùng.

**Gate chốt Phase 9b:** người dùng xác nhận UAT và quyết định xử lý ảnh trước còn thiếu;
sau đó mới đề nghị duyệt merge vào develop. Push và deploy là quyết định riêng.

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

Nghiệm thu Phase 9b: **người dùng đã xác nhận 06/10/2026**; candidate `cf21eff`,
runtime `d4cde62`. Không có lỗi mới được báo trong xác nhận này; không coi đây là
chứng minh mọi tổ hợp ca/role đều đã chạy. Chi tiết người kiểm tra/ca còn để bổ sung.
Merge local đã được người dùng duyệt và thực hiện bằng --ff-only ngày 06/10/2026.
Ảnh trước gốc vẫn ghi thiếu; push/deploy chưa được duyệt hoặc thực hiện.
Người kiểm tra: người dùng; ngày: 06/10/2026;
version/commit: ______; lỗi còn mở: ______; quyết định: ______.
Sau nghiệm thu, người dùng xem DEPLOY_LOG.md rồi tự quyết định deployment thủ công.
Post-deploy smoke trong DEPLOY_LOG.md là checklist khác và chỉ được tick sau deploy.
# Screenshot supplement — 2026-10-06

User authorized synthetic illustrations: [nine-page before/after comparison](PHASE9B_UI_COMPARISON.md).
Before is reconstructed from 1dc761f; after is 471950d. These do not replace user
acceptance, missing original images or unrecorded per-case UAT results.
