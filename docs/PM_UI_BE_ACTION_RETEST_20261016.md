# Kiểm thử giao diện Midnight Gold với backend — 16/10/2026

## Kết luận và phạm vi

Không tìm thấy lỗi tác vụ hoặc sai hợp đồng frontend/backend trong phạm vi kiểm thử đã chạy. Không sửa code nghiệp vụ. Cập nhật các bài test lỗi thời để phản ánh giao diện và thông báo hiện tại; không bỏ kiểm tra quyền, CSRF, validation hoặc payload. Dùng code-review để đối chiếu, impeccable/playwright-cli để kiểm tra giao diện và database-safety cho kiểm thử ghi local.

## Môi trường và dữ liệu

- Guard xác nhận `derasoft_pm_local`, host localhost/127.0.0.1; MySQL 8.4.11.
- Đã sao lưu 96 bảng local trước khi chạy; backup nằm trong `.local/`, không đưa vào Git.
- So sánh nội dung 96 bảng trước/sau: không thay đổi. `dc_users` vẫn MyISAM. Không so sánh bộ đếm AUTO_INCREMENT; bài duplicate INSERT có thể tạo khoảng trống ID.
- Không production, không áp dụng migration, không đổi engine.

## Kết quả đã chạy

| Kiểm tra | Kết quả |
| --- | --- |
| `powershell -ExecutionPolicy Bypass -File tests/pm_workspace_verify.ps1` | PASS 23 script không DB, 30 PHP lint, 6 manifest tests |
| `python tests/pm_ui_review_contract.py` | PASS 14 template giữ form contracts và PHP gate |
| `powershell -ExecutionPolicy Bypass -File tests/pm_regression.ps1` | PASS 66 script; bao gồm service/DAO/HTTP thực với bảng tạm hoặc rollback |
| `pm_role_http_smoke.php` trong regression | PASS 157 ca PM/HR qua controller HTTP với fixture tạm |
| 47 suite `tests/*browser.js` hiện dùng | PASS, context riêng từng suite; desktop/mobile, bàn phím, no-JS, lỗi mạng/phiên, nội dung rỗng, confirm và POST payload |
| `pm_ui_review_browser.js` trong 47 suite | PASS 29 trạng thái × 1440/390px, không overflow/thiếu label/pageerror |

Các suite browser chạy bằng `playwright-cli -s=workspace run-code --filename <wrapper-local>`. Wrapper chỉ tạo context mới rồi gọi nguyên hàm từ file test; không sửa kết quả hoặc ứng dụng. Kết quả từng suite được lưu trong `.local/action-audit-browser/final-results.json` và log cùng thư mục.

## Luồng tác vụ được bao phủ

| Khu vực | Tác vụ/ranh giới kiểm tra |
| --- | --- |
| Tài khoản | Hồ sơ, đổi mật khẩu, validation, login/logout CSRF, session guard, menu và bàn phím |
| Nhân sự | Thêm/sửa, khóa/mở/ẩn, phòng ban/role/đơn giá, validation, không sửa đối tượng ngoài phạm vi |
| Dự án/công việc | Thêm/sửa/ẩn, thành viên, trạng thái, Kanban kéo thả và nút cập nhật, rollback khi lỗi |
| Chấm công | Ghi/sửa/ẩn, giới hạn ngày/cửa sổ sửa, tính lại OT và cost/snapshot, quyền/ownership |
| Phân bổ | Tạo/sửa/ẩn, capacity, gợi ý, trùng giờ/vượt tải, task assignee, polling, CSRF sau refresh |
| Chi phí | Lọc/tab, số tiền decimal, thiếu đơn giá/khác tiền tệ, chart fallback, polling và lỗi quyền |
| Báo cáo/nhật ký | Lọc/phân trang, scope, detail và XLSX thực qua HTTP; escaping/formula strings |
| Import | Tải mẫu, Preview/staging, giữ tệp, Apply/tiếp tục/kết quả/cấp mật khẩu, duplicate/native1062, lock/khôi phục gián đoạn, role/tenant/CSRF |
| Thông báo | Menu/inbox, cập nhật, empty/error, thao tác và điều hướng; cron checks trong regression |
| UI chung | Header, focus, touch targets, confirmation hủy/chấp nhận, responsive/text scaling, motion/reduced motion và tương phản |

## Các lỗi bộ test đã xử lý

1. Test Apply còn kỳ vọng thông báo tiếng Anh; đổi sang chính xác thông báo tiếng Việt đang có, giữ yêu cầu ném exception và guard UNIQUE/immutable.
2. Phân bổ/import đã có panel đóng: mở bằng summary/tab trước khi thao tác; Apply chấp nhận confirm thật và chờ response POST.
3. Test còn đòi màu sáng hoặc biểu tượng D: đổi kỳ vọng theo Midnight Gold và logo chữ giữa header; giữ contrast/focus/CSRF.
4. Chart bị hủy đúng khi dữ liệu không cộng được: xác nhận chart không tồn tại và fallback hiển thị, thay vì đọc thuộc tính của chart đã hủy.
5. Import có script ngoài hợp lệ: kiểm tra không có inline executable script và payload XSS, thay vì cấm mọi thẻ script.
6. Fixture navigation thiếu stylesheet hiện dùng: bổ sung review/polish/studio để kiểm tra cùng cascade với sản phẩm.
7. UI HTTP test dùng trùng port18769 của preview an toàn, nên nhận404 đúng của preview. Chuyển fixture HTTP sang18849 và thêm guard từ chối port đã có server.
8. Runner regression chạy hết danh sách rồi tổng hợp lỗi, không dừng sau lỗi đầu tiên; vẫn trả thất bại nếu bất kỳ script nào lỗi.

## Giới hạn

Không chạy `pm_phase10_browser.js`: bài lưu trữ này bắt buộc database riêng `derasoft_pm_phase10_20261004` và tài khoản cũ đã được cleanup, không thuộc 47 suite hiện dùng. Không tạo lại hoặc đổi database để làm đẹp kết quả. Phiên Admin/Employee local có18 ca route thực; chưa có active local PM/HR để login bằng mật khẩu, bù bằng157 ca HTTP PM/HR fixture tạm, không gọi đó là real-account login UAT. Browser mutation được intercept; backend mutation được kiểm tra riêng qua service/DAO/controller HTTP. Vì vậy kết quả chưa phải browser E2E lưu dữ liệu xuyên nhiều request hoặc UAT host. Chưa push/merge/deploy.
