# AGENTS.md — DeraSoft PM

## Chọn skill theo công việc — yêu cầu ngày 07/10/2026

- Đọc `docs/SKILL_USAGE.md` khi chọn skill; dùng skill theo phạm vi thực tế, không theo từ khóa hoặc bật tất cả cùng lúc.
- Thông báo skill áp dụng lần đầu, đọc hướng dẫn trước khi dùng. Quyết định đã ghi nhận được tái sử dụng nếu bối cảnh vẫn phù hợp; kiểm tra lại khi yêu cầu hoặc hướng dẫn thay đổi.
- Sau trường hợp mới, ghi nguyên nhân, cách xử lý và bằng chứng kiểm tra vào tài liệu; không ghi suy đoán thành kết luận hoặc PASS chưa chạy.

## Bối cảnh

Dự án xây dựng hệ thống quản lý dự án và chấm công trên nền DeraSoft hiện có: PHP thuần, Smarty, MySQL và kiến trúc MVC tùy chỉnh. Không viết lại hệ thống từ đầu. Người dùng chính gồm Admin, Project Manager, HR và Nhân viên.

## Kiến trúc bắt buộc

- Model/DAO: `classes/dao/`, theo cặp lớp Objects và ObjectInfo khi phù hợp.
- Controller Admin: `modules/admin/`; AJAX nội bộ: `modules/ajax/`.
- View: `templates/admin/` bằng Smarty và `$template->assign()`.
- Module mới dùng tiền tố `pm` để tránh đụng mã legacy.
- Tên biến/hàm camelCase, class PascalCase, hằng UPPER_SNAKE_CASE, API key snake_case, file lowercase.
- Trước khi tạo module mới phải đọc module tương tự đang có và giữ convention DeraSoft.

## Quy tắc tuyệt đối

1. Không xóa chức năng legacy. Chức năng chưa dùng chỉ được ẩn khỏi giao diện/router PM và phải có thể khôi phục.
2. Không rewrite framework, module, route, DAO hoặc logic cũ ngoài phạm vi phase.
3. Thay đổi database chỉ được `CREATE TABLE` mới hoặc `ALTER TABLE ... ADD COLUMN`; không `DROP`, `RENAME`, `TRUNCATE` hay xóa dữ liệu cũ.
4. Mọi migration phải là file versioned trong `database/migrations/`, có kiểm tra tồn tại và hướng dẫn rollback an toàn.
5. Không truy cập hay thay đổi production trong quá trình phát triển. Chỉ dùng database local/staging đã sao lưu.
6. Không commit secret, mật khẩu, khóa, dump database, log, cache, file cấu hình production hoặc dữ liệu upload.
7. Không thêm framework/thư viện lớn nếu chưa được duyệt.
8. Mỗi phase dùng nhánh `feature/pm-phaseN-*`, làm đúng phạm vi phase và dừng chờ duyệt tại các điểm yêu cầu.
9. Không dùng `git add .`; chỉ stage đúng file liên quan. Cấm `reset --hard`, `clean -fd`, force push và xóa branch cưỡng bức.
10. Không tuyên bố đã test nếu chưa chạy; luôn ghi rõ lệnh và kết quả kiểm tra.

## Bảo mật mặc định

- Query mới phải dùng prepared statement; không ghép input vào SQL.
- Escape output HTML, kiểm tra CSRF cho mọi thao tác ghi, kiểm tra permission và ownership chống IDOR.
- Mật khẩu mới dùng `password_hash()`/`password_verify()` và giữ lộ trình nâng cấp tương thích tài khoản MD5 cũ.
- Regenerate session ID sau đăng nhập; cookie HttpOnly, Secure trên HTTPS và cấu hình SameSite phù hợp.
- Upload phải whitelist loại file, giới hạn dung lượng và không cho thực thi.
- Production không hiển thị lỗi/debug hoặc dữ liệu session.

## Quy trình mỗi phase

PLAN → chờ duyệt → BUILD → VERIFY → security review → cập nhật `docs/PROGRESS.md` → commit local một lần cho phase hoàn tất → báo cáo. Sau Phase 0 chỉ nộp audit và dừng chờ duyệt.

## Phạm vi sản phẩm

Auth/RBAC; người dùng, vai trò và đơn giá; dự án và task/Kanban; chấm công và OT; audit log; chi phí và biểu đồ; phân bổ nguồn lực/overbooking; báo cáo và Excel; giao diện responsive.

## Quyết định đã chốt

- Lưu `rate_snapshot` và `cost` trên từng timesheet.
- OT tính theo tổng giờ/ngày; sửa hoặc xóa phải tính lại cả ngày.
- Project, task và user dùng soft delete.
- Realtime dùng truy vấn khi tải và polling 30–60 giây, không dùng WebSocket.
- Biểu đồ dự kiến dùng Chart.js sau khi được duyệt ở đúng phase.

## Tài liệu chuẩn

`docs/PROJECT_BRIEF.md`, `docs/PLAN_FINAL.md`, `docs/AUDIT.md`, `docs/DB_SCHEMA.md`, `docs/PROGRESS.md`.

## GitHub & portfolio — yêu cầu người dùng

### Contribution và tính chuyên nghiệp — yêu cầu ngày 07/10/2026

- Áp dụng cho mọi lần chuẩn bị commit/push của dự án: ưu tiên lịch sử công việc thật,
  đều đặn, có thay đổi hoàn chỉnh và bằng chứng kiểm tra. Không tạo commit rỗng,
  sửa ngày tác giả, chia nhỏ giả tạo hoặc rewrite lịch sử để tô contribution graph.
- Giữ đúng email tác giả đã chốt. Khi push, xác nhận hash local/remote và báo nhánh
  nhận commit; không gọi push feature branch là đã cập nhật contribution trên main.
- Khi phase đã đủ điều kiện phát hành, chuẩn bị review/PR và đưa về develop/main
  theo nghiệm thu, phê duyệt hiện hành; không bỏ quên merge, nhưng không merge chỉ
  để làm đẹp biểu đồ. Yêu cầu này không tự cấp quyền push/merge/deploy mọi lần sau.
- Tài liệu portfolio/README phải theo kịp chức năng đã kiểm chứng, có hướng dẫn chạy,
  kiểm thử và ảnh dữ liệu giả. Chấm xanh là kết quả công việc, không thay chất lượng.
- GitHub có thể cập nhật chậm và ghi contribution theo ngày commit; không hứa số
  chấm xanh hoặc ngày hiển thị. Đối chiếu quy định GitHub khi cần giải thích.

- Repository chính: `https://github.com/tridung-ta/derasoft-pm`. Kiểm tra remote và danh tính trước mỗi commit/push.
- Commit mới dùng Git config local: `Tạ Trí Dũng`, `213818008+tridung-ta@users.noreply.github.com`. Không dùng danh tính QUANLYTHUVIEN của dự án cũ; không sửa global config hoặc rewrite lịch sử đã push.
- Mỗi commit là một thay đổi hoàn chỉnh, dễ review; message theo `feat`, `fix`, `test`, `docs` với scope PM cụ thể, mô tả chức năng thực tế. Không tạo commit rỗng để tăng contribution.
- Sau mỗi phase hoàn tất BUILD, kiểm thử chức năng/regression, code/security review và cập nhật tài liệu, tự tạo một commit local trên nhánh phase, không cần hỏi lại quyền commit. Commit gồm code, tests và tài liệu liên quan đã kiểm chứng; chỉ stage file cụ thể, kiểm tra staged diff và loại toàn bộ secret/config/dump/cache/log/upload. Phase còn ở PLAN hoặc BUILD dở chưa tạo commit hoàn tất; không chờ UAT từng phase để commit local. Báo cáo hash, branch, lệnh và kết quả kiểm thử thật.
- Trước push: kiểm tra staged diff, chỉ stage file liên quan, loại secret/config/dump/cache/log/upload, ghi lệnh kiểm thử và kết quả thật. Commit/push khi được người dùng hoặc workflow hiện tại cho phép.
- GitHub phải rõ ràng với nhà tuyển dụng: README mô tả sản phẩm, phần đã làm, kiến trúc, stack, cách kiểm thử và roadmap; description/topics đúng phạm vi; cập nhật link nhánh triển khai khi phase đổi.
- Main phải có tài liệu portfolio hiện hành; code phase chỉ merge sau nghiệm thu/phê duyệt. Không merge chỉ để làm đẹp contribution graph. Không ghi UAT/production-ready/performance khi chưa có bằng chứng.
- Giữ mỗi repo cho một sản phẩm; phase dùng branch. Không tự đổi tên/xóa/archive repo khác. Screenshot portfolio dùng dữ liệu giả hoặc đã ẩn thông tin nhạy cảm, phân biệt với banner minh họa.
- Nội dung CV tham khảo `docs/PORTFOLIO.md`; cập nhật khi có chức năng đã kiểm chứng, trình bày rõ phần mở rộng nền DeraSoft có sẵn.

## Kiểm thử và tiếp tục local — yêu cầu ngày 03/10/2026

- Sau mỗi phase, tự kiểm thử chức năng đã xây dựng, regression toàn bộ chức năng PM,
  rà soát code và bảo mật; sửa lỗi và kiểm thử lại trước khi tiếp tục.
- Người dùng chỉ UAT thủ công khi toàn bộ bản local hoàn tất; không dừng từng phase để chờ UAT.
- Sau kiểm thử đạt, tiếp tục công việc local theo phạm vi được duyệt; lưu plan và kết quả thật.
  Những quyết định ngoài quy tắc đã duyệt, như đổi engine legacy, vẫn cần hỏi riêng.
- Automated PASS không đồng nghĩa UAT PASS hoặc bảo đảm không còn lỗi. Production chỉ sau
  người dùng nghiệm thu và cho phép triển khai. Commit local một lần sau mỗi phase hoàn tất
  theo quy định trên; không tự push/merge/deploy khi chưa có ủy quyền riêng.

## Quyết định import chính thức

- GIỮ ENGINE dc_users; không đổi sang InnoDB, kể cả local. Không đề xuất lại ngoại lệ engine.
- Apply chỉ code sau xác nhận UNIQUE email ở DB; nếu thiếu phải đề xuất ADD UNIQUE INDEX
  và chờ duyệt riêng, không tự chạy migration này.
- Apply INSERT từng dòng, xử lý duplicate key 1062 thay cho SELECT-check-then-INSERT;
  kết quả từng dòng applied/skipped_duplicate/failed, có khả năng tiếp tục sau gián đoạn.
- import_logs có trạng thái in_progress và cơ chế chống hai Admin Apply cùng batch.
