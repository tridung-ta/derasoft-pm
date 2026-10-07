# Quy tắc sử dụng skill trong DeraSoft PM

Đã chốt với người dùng ngày 07/10/2026. Đây là bộ nhớ trong repository cho các
lần làm việc tiếp theo, không thay thế hướng dẫn hiện hành của từng skill.

## Chọn đúng phạm vi

| Công việc | Skill phù hợp | Điều kiện áp dụng |
| --- | --- | --- |
| Bổ sung hoặc sửa chức năng đáng kể | feature-development | Hiểu luồng hiện có, triển khai và kiểm thử đến khi hoàn tất. |
| Cải tiến giao diện đang có | impeccable | Phân cấp, typography, nút, màu, responsive và accessibility; giữ theme đã duyệt. |
| Kiểm tra tương tác trình duyệt | playwright-cli | Kiểm tra hành vi thực tế và computed style, bàn phím, kích thước màn hình. |
| Review trước khi chốt thay đổi | code-review | Tìm lỗi, hồi quy, bảo mật và thiếu kiểm thử; không coi review là bằng chứng chạy test. |
| Migration hoặc thao tác có rủi ro dữ liệu | database-safety | Kiểm tra môi trường, quyền, phạm vi và rollback; không cần cho truy vấn chỉ đọc thông thường. |
| Commit thay đổi hoàn tất | git-feature-commit | Kiểm tra diff, danh tính/remote, stage đúng file, xác minh rồi commit local. |
| Điều khiển ứng dụng máy tính | computer-use:computer-use | Chỉ khi cần thao tác UI và công cụ chuyên dụng không phù hợp. |
| Tạo ảnh bitmap thật sự cần thiết | imagegen | Không dùng thay HTML/CSS, icon SVG hoặc để trang trí không cần thiết. |
| Dự án dùng shadcn/ui thực tế | shadcn | DeraSoft PHP/Smarty chỉ tham khảo phong cách shadcn không phải lý do đổi stack hay cài thư viện. |
| Công việc phức tạp nhiều bước | deepseek-harness | Chỉ thêm khi phương pháp tổ chức này giúp công việc; tránh lặp quy trình đã đủ. |

Chỉ kết hợp skill có vai trò riêng trong công việc. Chỉnh tài liệu nhỏ hoặc giải
thích đơn giản không cần kích hoạt quy trình phát triển tính năng đầy đủ. Tạo file
Excel độc lập có thể dùng spreadsheets; sửa chức năng xuất XLSX trong ứng dụng
vẫn là công việc phát triển và kiểm thử phần mềm.

## Kinh nghiệm đã xác minh

- **Login bị mờ:** kiểm tra computed style và độ ưu tiên CSS trước khi đổi màu.
  Rule card chung làm nền intro trắng trong khi CSS legacy giữ chữ trắng.
  Sửa bằng selector giới hạn trang login; kiểm tra cả nút submit legacy.
  Đo tương phản thật và xem desktop/mobile, focus, phóng đại chữ. Bản vá
  `e6f164f` cho brand/title/description đạt 18,10:1 trong kiểm thử local.
- **Local đúng, production khác:** đối chiếu file thực sự upload và phiên bản
  asset. Cache là khả năng cần kiểm tra, không phải nguyên nhân mặc định.
  Bản vá login thêm phiên bản CSS và gồm cả CSS lẫn template. Không kết luận
  toàn ứng dụng đã triển khai từ việc một trang đã sửa thành công.
- **Select mất mũi tên:** shorthand `background` có thể xóa background-image
  của Bootstrap. Dùng `background-color` khi chỉ đổi nền và kiểm tra computed
  background-image cùng khoảng trống phía phải.
- **Kiểm thử font:** đợi font tải, render glyph hiển thị và ổn định layout trước
  khi đọc kết quả. Không chấp nhận kết quả rỗng hoặc suy ra font từ tên CSS.
- **Xác nhận kết quả:** automated PASS, nghiệm thu người dùng và bằng chứng
  production là ba loại khác nhau. Ghi môi trường, lệnh và phạm vi kiểm tra.

## Cập nhật sau mỗi trường hợp mới

Ghi ngắn gọn: yêu cầu → skill đã dùng và lý do → nguyên nhân xác minh → cách sửa
→ kiểm tra thực tế → giới hạn còn lại. Không lưu mật khẩu, secret hoặc dữ liệu
người dùng. Chỉ tái sử dụng cách sửa sau khi kiểm tra bối cảnh tương đương;
không cần hỏi lại những lựa chọn thường lệ đã được chốt.

## HTTP role fixture coverage - 2026-10-07

Used feature-development for controller verification, code-review for test safety
and git-feature-commit for the completed local change. Missing real PM/HR actors
must not become silent coverage skips: supplement with explicitly labeled HTTP
fixtures, without changing persistent account roles. MySQL temporary tables
cannot carry foreign keys; fixture copies retain columns/indexes and omit FK
constraints only in the connection-local role table. Verified 30 HTTP cases,
47 regression scripts, unchanged persistent roles/projects/user engine. These
fixtures do not constitute password-login or production UAT evidence.

## HTTP mutation isolation - 2026-10-07

Before allowing test POST actions, trace every write target, including legacy
tracking, and shadow all targets in connection-local temporary tables. Capture
request-local state before connection teardown, and compare persistent state
before/after. Verified project/task POST through unchanged controllers with
40 HTTP cases and 47 regression scripts PASS. This verifies isolated request
writes; it does not establish cross-request persistence or browser form E2E.

## Fixture route and write isolation - 2026-10-07

Test routers must require GET/POST route agreement before selecting shadow
write tables. Route mismatch is rejected before controller/DB execution. Scope
fixture tables to the route under test; other controllers may issue queries
that temporary-table limitations cannot support. Verified 52 HTTP cases with
persistent business state unchanged. This is test harness behavior, not a
production middleware change or an application defect claim.

## Zero-row UPDATE result - 2026-10-07

Personnel HTTP test reproduced false saved notice/tracking for invalid targets.
Zero affected rows may mean unchanged form or no matching target. DAO now checks
visible same-tenant existence when UPDATE changes zero rows; controller checks
return before success/tracking. Verify unchanged, missing, hidden and cross-tenant
cases together. 73 combined HTTP cases and 47 regression scripts PASS; actual
users unchanged. Applied existing development/review/commit workflow skills.
