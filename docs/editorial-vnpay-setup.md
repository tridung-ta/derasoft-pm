# VNPay Sandbox Setup

## Endpoints

- Return URL: `https://dung.derasoft.com/thanh-toan/vnpay-return`
- IPN URL: `https://dung.derasoft.com/thanh-toan/vnpay-ipn`
- Payment URL sandbox: `https://sandbox.vnpayment.vn/paymentv2/vpcpay.html`

Khai báo Return URL và IPN URL trên tài khoản VNPay sandbox. IPN phải truy cập công khai qua HTTPS; không đặt đăng nhập hoặc CSRF trước endpoint này.

## Cấu hình bí mật

Ưu tiên cấu hình bốn biến môi trường trên PHP-FPM/hosting:

```text
DERACMS_VNPAY_TMN_CODE
DERACMS_VNPAY_HASH_SECRET
DERACMS_VNPAY_RETURN_URL=https://dung.derasoft.com/thanh-toan/vnpay-return
DERACMS_VNPAY_PAYMENT_URL=https://sandbox.vnpayment.vn/paymentv2/vpcpay.html
```

Nếu hosting không hỗ trợ biến môi trường, có thể khai báo tương đương trong `includes/config.inc.php` của server:

```php
define('VNPAY_TMN_CODE', 'ma-website-do-vnpay-cap');
define('VNPAY_HASH_SECRET', 'secret-do-vnpay-cap');
define('VNPAY_RETURN_URL', 'https://dung.derasoft.com/thanh-toan/vnpay-return');
define('VNPAY_PAYMENT_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html');
```

Không commit `TmnCode`, `HashSecret` hoặc bản sao `config.inc.php`. Khi chuyển production, thay URL sandbox bằng URL production do VNPay cung cấp và dùng bộ credential production riêng.

## Kiểm thử sandbox

1. Tạo ít nhất một membership plan đang bật, giá lớn hơn `0`, tiền tệ `VND`.
2. Đăng nhập tài khoản người dùng và mở `/khong-gian-doc`.
3. Chọn gói và thanh toán qua VNPay.
4. Xác nhận Return URL chỉ hiển thị kết quả; quyền Premium chỉ xuất hiện sau IPN hợp lệ.
5. Kiểm tra `dc_editorial_payment_transactions`: `status = 1`, có `provider_transaction_no`, `subscription_id` và không chứa secret trong `response_snapshot`.
6. Gửi lại cùng IPN để xác nhận không tạo subscription thứ hai.
