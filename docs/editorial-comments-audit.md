# Editorial Article Comments — Audit & Implementation Plan

Ngày audit: 24/09/2026  
Nhánh: `feature/ten-chuc-nang`

## Kết luận

Không nên dùng trực tiếp luồng `dc_comment` cũ cho bình luận editorial. Luồng này được thiết kế chung cho đánh giá sản phẩm/bài viết, thu thập nhiều thông tin cá nhân và đang có các điểm không phù hợp với Member Gateway.

## Thành phần hiện có

- DAO legacy: `classes/dao/comments.class.php`
- Endpoint gửi: `modules/ajax/comment_form.module.php`
- Endpoint tải danh sách: `modules/ajax/get_reviews.module.php`
- Template đánh giá/bình luận: `templates/mpx/component/comment*.tpl.html`
- Popup nhập đánh giá: `templates/mpx/popup-comment.tpl.html`
- Admin kiểm duyệt legacy: `modules/admin/manage/comment*.module.php`

## Vấn đề tìm thấy

### Bảo mật

- Endpoint cũ bật `display_errors` trên luồng public.
- Secret reCAPTCHA được đặt trực tiếp trong source.
- Không kiểm tra CSRF.
- Không bắt buộc tài khoản đăng nhập.
- Không có giới hạn tần suất đủ chặt theo tài khoản.
- Endpoint có một khối xử lý bị lặp sau `exit`.

### Dữ liệu

- Bảng `dc_comment` dùng chung `pid` cho nhiều loại nội dung; truy vấn danh sách có nơi không lọc `type`.
- ID sản phẩm và ID bài viết có thể trùng, dẫn đến lấy nhầm dữ liệu.
- Schema cũ không liên kết bình luận với `customer_id`.
- DAO hiện tại có phương thức đếm bình luận không lọc loại nội dung.

### Giao diện và đa ngôn ngữ

- Popup cũ yêu cầu họ tên, email, số điện thoại và địa chỉ dù người dùng đã đăng nhập.
- Nội dung giao diện mang ngữ cảnh báo giá/sản phẩm, không phù hợp bài editorial.
- Endpoint tải bình luận chỉ chấp nhận VI/EN, chưa hỗ trợ 中文.
- Nội dung bình luận đang được render chưa có quy ước escape rõ ràng tại template.

### Admin

- Admin hiện có thể bật/tắt/xóa mềm dữ liệu legacy.
- Danh sách chưa tách rõ sản phẩm và bài viết.
- Một số URL dùng `act=comments` trong khi module chính dùng `act=comment`.

## Kế hoạch đề xuất

### 1. Persistence layer

Tạo bảng mới `dc_editorial_article_comments`, không sửa hoặc xóa bảng cũ:

- `id`
- `store_id`
- `article_id`
- `customer_id`
- `content`
- `status` (`0` chờ duyệt, `1` đã duyệt, `2` từ chối, `3` đã xóa)
- `date_created`
- `date_updated`

Kèm migration và rollback riêng, index theo bài viết/trạng thái và tài khoản/thời gian.

### 2. Public API

- Endpoint editorial riêng, không sửa endpoint sản phẩm cũ.
- Bắt buộc đăng nhập.
- Kiểm tra CSRF.
- Giới hạn tần suất theo tài khoản.
- Nội dung 10–2000 ký tự, loại bỏ HTML và ký tự điều khiển.
- Chỉ nhận bài viết đang xuất bản.
- Bình luận mới mặc định chờ duyệt.
- JSON VI/EN/中文 và không hiển thị lỗi nội bộ.

### 3. Public UI

- Form gọn chỉ gồm nội dung bình luận vì thông tin thành viên đã có trong tài khoản.
- Hiển thị trạng thái gửi và thông báo “đang chờ duyệt”.
- Chỉ hiển thị bình luận đã duyệt.
- Phân trang hoặc nút “Xem thêm”; escape toàn bộ nội dung do người dùng nhập.
- Responsive và hỗ trợ VI/EN/中文.

### 4. Admin moderation

- Tạo màn hình kiểm duyệt editorial riêng hoặc bổ sung tab tách biệt trong khu vực Đánh giá hiện có.
- Cho phép duyệt, từ chối và xóa mềm.
- Giữ kiểm tra permission và tracking log của DeraCMS.
- Không trộn dữ liệu với đánh giá sản phẩm legacy.

## Commit dự kiến

1. `feat(comments): add editorial comment persistence layer`
2. `feat(comments): add secure article comment API`
3. `feat(article): add member comment interface`
4. `feat(admin): add editorial comment moderation`
5. `style(comments): refine multilingual comment experience`

## Thay đổi cần phê duyệt trước khi code

- Thêm bảng database mới.
- Bổ sung chức năng kiểm duyệt trong Admin.

Không cần reCAPTCHA cho luồng mới vì website đã bắt buộc đăng nhập; bảo vệ bằng session, CSRF, rate limit, validation và kiểm duyệt.
