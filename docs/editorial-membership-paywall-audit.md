# Editorial Membership & Paywall — Audit and Staged Plan

Ngày audit: 24/09/2026
Nhánh: `feature/ten-chuc-nang`

## Kết luận

Giai đoạn A của tính năng thành viên trả phí/paywall đã được triển khai: hệ thống có gói hội viên, subscription có thời hạn, entitlement service, quản trị cấp/thu hồi quyền, cờ bài premium và paywall phía backend. Giai đoạn B về giao dịch thanh toán và callback/webhook của VNPay/MoMo chưa triển khai.

Không nên gắn paywall trực tiếp vào `orders` legacy. Luồng này được thiết kế cho sản phẩm vật lý, chứa trạng thái giao hàng và một số query cũ có alias không hợp lệ. Tái sử dụng nó cho quyền đọc sẽ làm tăng rủi ro cấp quyền sai khi thanh toán.

## Thành phần có thể tái sử dụng

- Session `store_customerId` và `Customers` xác định thành viên đang đăng nhập.
- `customer_groups` có thể dùng cho phân nhóm CRM nhưng không có ngày bắt đầu/kết thúc quyền đọc, vì vậy không đủ làm subscription.
- `ArticleInfo::properties` có thể chứa cờ `custom_editorial_access = premium`; không cần thêm cột vào `articles`.
- Admin Editorial Control Center có thể mở rộng để quản lý gói và quyền truy cập.
- `payment_methods` chỉ lưu metadata phương thức; chưa có code tạo yêu cầu thanh toán hoặc xác minh chữ ký callback.

## Phạm vi an toàn đề xuất

Triển khai theo hai giai đoạn độc lập. Giai đoạn nền tảng phải hoàn thành và được kiểm thử trước khi kết nối tiền thật.

### Giai đoạn A — Membership foundation, chưa thu tiền

1. Tạo bảng `dc_editorial_membership_plans` để quản lý tên gói, giá hiển thị, thời hạn và trạng thái.
2. Tạo bảng `dc_editorial_subscriptions` để lưu quyền của customer, thời gian hiệu lực, trạng thái và nguồn cấp quyền.
3. Tạo DAO và entitlement service kiểm tra subscription đang hiệu lực theo `store_id` và `customer_id`.
4. Thêm cờ bài premium vào `articles.properties`, mặc định mọi bài hiện tại vẫn miễn phí.
5. Chặn nội dung premium ở backend; template chỉ nhận phần preview khi tài khoản không có quyền.
6. Thêm paywall UI VI/EN/中文 và trang quản lý quyền thủ công trong Admin để kiểm thử.

Commit dự kiến:

- `feat(membership): add plans and subscriptions schema`
- `feat(membership): add subscription persistence and entitlement service`
- `feat(admin): add editorial membership management`
- `feat(article): enforce premium content access`
- `style(paywall): add multilingual membership gate`

### Giai đoạn B — Tích hợp cổng thanh toán

Chỉ bắt đầu sau khi chọn nhà cung cấp và có tài khoản sandbox.

1. Tạo bảng `dc_editorial_payment_transactions` với mã giao dịch nội bộ duy nhất, amount/currency/status/provider reference và payload đã lọc.
2. Tạo request thanh toán từ server; không tin giá hoặc plan gửi từ trình duyệt.
3. Xác minh chữ ký callback/webhook, amount, currency, transaction status và chống xử lý lặp.
4. Chỉ kích hoạt subscription sau khi callback hợp lệ được ghi transactionally.
5. Không log secret, chữ ký, session hoặc toàn bộ payload nhạy cảm.

Commit phụ thuộc nhà cung cấp:

- `feat(payments): add membership transaction ledger`
- `feat(payments): integrate <provider> sandbox checkout`
- `feat(payments): verify idempotent payment callbacks`
- `feat(membership): activate subscription after verified payment`

## Migration dự kiến và an toàn dữ liệu

Migration Giai đoạn A chỉ **CREATE hai bảng mới**, không sửa hoặc xóa bảng hiện tại:

- `dc_editorial_membership_plans`
- `dc_editorial_subscriptions`

Các foreign key logic dùng index thay vì constraint cứng để tương thích DeraCMS legacy. Cần unique/index cho plan code và tra quyền theo `(store_id, customer_id, status, starts_at, ends_at)`.

Tác động production:

- Không backfill dữ liệu và không thay quyền của bài hiện tại.
- CREATE TABLE chỉ giữ metadata lock ngắn; nên chạy lúc lưu lượng thấp.
- Deploy migration trước code DAO; code paywall chỉ bật sau khi xác nhận schema.
- Rollback sẽ DROP đúng hai bảng mới và làm mất dữ liệu membership mới, nên chỉ chạy khi chưa đưa vào sử dụng hoặc đã sao lưu.
- Codex không tự chạy migration production.

## Rủi ro bắt buộc kiểm soát

- Paywall phải kiểm tra ở PHP, không chỉ ẩn HTML/CSS.
- Không tin trạng thái “success” từ redirect trình duyệt.
- Callback phải idempotent và chỉ cấp quyền một lần.
- Giá thanh toán phải lấy từ plan trong DB, không lấy từ request client.
- Bài hiện tại phải mặc định miễn phí để tránh khóa nhầm toàn site.
- Tài khoản hết hạn hoặc bị thu hồi phải mất quyền ngay ở request kế tiếp.
- Không dùng `customer_groups` thay cho lịch sử subscription có thời hạn.

## Tiêu chí hoàn thành Giai đoạn A

- Admin tạo/tắt gói và cấp/thu hồi quyền thủ công.
- Bài miễn phí không thay đổi hành vi.
- Bài premium chỉ trả nội dung đầy đủ cho subscription đang hiệu lực.
- Khách không đủ quyền thấy preview và CTA rõ ràng bằng VI/EN/中文.
- Không lộ nội dung premium trong HTML, structured data hoặc meta description.
- Có migration/rollback riêng và kiểm thử desktop/mobile.

## Quyết định cần phê duyệt

1. Có triển khai Giai đoạn A trước, chưa kết nối thanh toán thật hay không.
2. Khi sang Giai đoạn B, chọn **VNPay** hay **MoMo** và cung cấp tài khoản sandbox qua cấu hình server, không gửi secret trong chat hoặc commit.
