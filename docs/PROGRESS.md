# DeraSoft PM — Progress

## Phase 0 — Discovery & Audit

### Status

COMPLETED — chờ merge theo quyết định của người dùng.

### Completed

- Audit kiến trúc, auth/session, DAO, Smarty, pagination, Tracking Data và Excel.
- Ghi project brief, plan chính thức và quy tắc làm việc.
- Chốt bảy quyết định kỹ thuật; ghi ba quyết định nghiệp vụ chờ mentor cùng mặc định an toàn.

### Database

Không tạo hoặc chạy migration. Không kết nối production.

### Git

- Branch: `feature/pm-phase0-audit`
- Commit: `3df3349 docs(pm-phase0): record audit and delivery plan`

## Phase 1 — Môi trường & Baseline

### Status

PARTIAL — baseline PHP 8.3 không cần DB đã hoàn tất; runtime baseline đang chờ DB local.

### Environment

- PHP 8.3.35 portable đã được chuẩn bị trong `.tools/` bị Git ignore; PHP mặc định của máy vẫn là 8.5.10.
- MySQL client 8.4.11 và service `MySQL84` có sẵn; chưa có credential/DB copy local để test ứng dụng.
- Smarty 4.5.5 được nhúng trong source.
- PHP 8.3 portable đã bật `mysqli`, `mbstring`, `openssl`, `fileinfo`, `zip`, `gd`, `intl`.
- Chưa phát hiện XAMPP/Laragon hoặc local web root đã cấu hình.

### Completed

- Tạo `develop` và `feature/pm-phase1-baseline` tại checkpoint Phase 0.
- Xác nhận file cấu hình và license thật đang được Git ignore.
- Chuẩn hóa thư mục và quy tắc migration tại `database/migrations/README.md`.
- Lập hồ sơ môi trường và ma trận baseline tại `docs/BASELINE.md`.
- PM template smoke test đạt cho dashboard, profile, password và team.
- Smarty 4.5.5 và PhpSpreadsheet smoke test ghi XLSX tạm thời thành công trên PHP 8.3, không có warning.
- Lint 1.401 file PHP bằng PHP 8.3: 1.396 đạt, 5 file mail legacy/third-party không tương thích và đã ghi rõ trong `docs/BASELINE.md`.

### Database

Không có thay đổi database. Chưa có migration SQL. Local DB chưa sẵn sàng để test runtime.

### Known Issues

- PHP local khác phiên bản production.
- Chưa có local DB đã ẩn dữ liệu và web server/virtual host.
- `vendor/autoload.php` không có; các thư viện nhúng cần được kiểm tra riêng.
- Năm file PHPMailer/mail legacy không parse trên PHP 8.3; không nằm trong PM hiện tại nhưng phải xử lý có chọn lọc trước khi dùng email.
- Auth legacy dùng MD5 và DAO legacy ghép chuỗi SQL; dự kiến xử lý Phase 2, chưa sửa Phase 1.

### Remaining Before Phase 2

- Khi có DB local: kiểm tra schema, login/logout, CRUD mẫu, tracking và Excel runtime.
- Chốt ba quyết định nghiệp vụ với mentor trước phase liên quan; không cần đóng cứng chúng trong Phase 2.

### Git

- Branch: `feature/pm-phase1-baseline`
- Commit: chưa tạo.

### Next Step

Cung cấp DB local đã ẩn dữ liệu để hoàn tất login/logout/CRUD/tracking runtime. Không dùng production làm môi trường phát triển.
