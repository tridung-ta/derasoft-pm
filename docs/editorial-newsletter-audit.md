# Editorial Newsletter — Audit & Implementation Plan

Ngày audit: 24/09/2026  
Nhánh: `feature/ten-chuc-nang`

## Kết luận

Source chưa có hệ thống newsletter public hoàn chỉnh. Bảng `email_templates` chỉ quản lý mẫu email; dữ liệu email trong comment/customer không thể được coi là đồng ý nhận newsletter. Cần một bảng subscriber riêng để lưu consent, trạng thái và token hủy đăng ký.

Hạ tầng `sendMail()` và PHPMailer đã tồn tại, nhưng lịch sử production cho thấy Gmail từng từ chối thư do SPF/DKIM và cấu hình SMTP chưa hoàn chỉnh. Vì vậy nên triển khai theo hai giai đoạn:

1. Thu thập và quản lý đăng ký an toàn, chưa tự động gửi hàng loạt.
2. Chỉ bật xác nhận/gửi chiến dịch sau khi SMTP và DNS mail được kiểm tra độc lập.

## Thành phần hiện có

- `includes/functions.inc.php::sendMail()` hỗ trợ SMTP, relay legacy và fallback `mail()`.
- `includes/editorial_mail.inc.php` chứa helper email xác thực tài khoản, không chứa credential.
- `classes/dao/emails.class.php` quản lý `dc_email_templates`, không phải danh sách subscriber.
- `classes/dao/comments.class.php::getSubscriberEmails()` lấy email từ comment legacy; dữ liệu này không chứng minh consent newsletter.
- Một số trường `mail_status` và `unsubscribe_token` tồn tại trong comment legacy nhưng không phải mô hình newsletter độc lập.
- Footer editorial hiện chưa có form đăng ký nhận tin.
- Chưa tìm thấy endpoint subscribe/unsubscribe, trang quản trị subscriber hoặc cron newsletter production.

## Rủi ro

### Consent và quyền riêng tư

- Không được tự động đưa email thành viên, khách hàng hoặc người bình luận vào newsletter.
- Phải ghi nhận thời điểm đồng ý, nguồn đăng ký và trạng thái hủy.
- Không công khai subscriber hoặc token qua log/debug/UI.

### Mail transport

- Gửi trực tiếp từ hosting tới Gmail từng bị chặn do xác thực domain không đạt SPF/DKIM.
- Không được đưa mật khẩu SMTP vào source hoặc tự sửa `config.inc.php`.
- Gửi hàng loạt đồng bộ trong request web có nguy cơ timeout và bị giới hạn hosting.

### Bảo mật endpoint

- Form public cần CSRF, honeypot/rate limit, validation email và phản hồi không tiết lộ email đã tồn tại.
- Token unsubscribe phải đủ ngẫu nhiên, lưu an toàn và không thể đoán.
- Subscribe lặp lại phải idempotent, không tạo nhiều bản ghi cùng email/store.

## Schema đề xuất

Tạo bảng mới `dc_editorial_newsletter_subscribers`:

- `id` bigint unsigned, primary key, auto increment
- `store_id` int unsigned
- `email` varchar(191)
- `language` varchar(5), mặc định `vn`
- `status` tinyint unsigned: `0` pending, `1` active, `2` unsubscribed, `3` blocked
- `source` varchar(50), ví dụ `footer`
- `confirmation_token_hash` char(64), nullable
- `unsubscribe_token_hash` char(64)
- `consented_at` datetime, nullable
- `confirmed_at` datetime, nullable
- `unsubscribed_at` datetime, nullable
- `date_created` datetime
- `date_updated` datetime

Unique index `(store_id, email)` và index `(store_id, status)`.

Token thô chỉ xuất hiện trong URL gửi cho người dùng; database lưu SHA-256 của token.

## Kế hoạch đề xuất

### Giai đoạn 1 — Subscription capture

- Migration và rollback riêng.
- DAO subscriber mới, không dùng bảng comment/customer.
- Endpoint đăng ký CSRF + rate limit + honeypot + email normalization.
- Form footer VI/EN/中文 với loading, thông báo thành công/lỗi và accessibility.
- Nếu mail verification đang tắt hoặc transport chưa sẵn sàng: lưu trạng thái `pending`, không báo gửi thành công giả.
- Endpoint hủy đăng ký bằng token riêng.
- Màn hình Admin chỉ xem/lọc trạng thái và block/unblock; không gửi chiến dịch.

### Giai đoạn 2 — Confirmation and delivery

- Chỉ triển khai sau khi xác nhận SMTP/SPF/DKIM hoạt động.
- Double opt-in: gửi link xác nhận và chuyển `pending` sang `active` khi token hợp lệ.
- Hàng đợi/cron gửi theo batch nhỏ; không gửi hàng loạt trong request web.
- Mỗi email có link unsubscribe và header phù hợp.
- Theo dõi trạng thái gửi mà không log credential hoặc nội dung nhạy cảm.

## Commit dự kiến cho giai đoạn 1

1. `feat(newsletter): add subscriber persistence layer`
2. `feat(newsletter): add secure subscription API`
3. `feat(footer): add multilingual newsletter form`
4. `feat(newsletter): add secure unsubscribe flow`
5. `feat(admin): add newsletter subscriber management`

## Thay đổi cần phê duyệt trước khi code

- Thêm bảng `dc_editorial_newsletter_subscribers` bằng migration và rollback.
- Giai đoạn đầu chỉ thu thập subscriber ở trạng thái pending và quản lý consent; chưa gửi newsletter hàng loạt.
- Việc bật double opt-in và gửi chiến dịch sẽ chờ SMTP/SPF/DKIM ổn định.

Không cần sửa credential hoặc `includes/config.inc.php` trong giai đoạn 1.
