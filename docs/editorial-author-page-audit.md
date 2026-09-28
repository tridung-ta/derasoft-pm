# Editorial Author Page — Audit & Implementation Plan

Ngày audit: 24/09/2026  
Nhánh: `feature/ten-chuc-nang`

## Kết luận

Trang tác giả có thể triển khai bằng dữ liệu hiện có, không cần migration database. Bảng bài viết đã lưu `poster_id`; DAO bài viết đã JOIN bảng `dc_users` và trả về tên người đăng. Phương án an toàn là dùng ID nội bộ trong URL công khai, chỉ hiển thị tên, ảnh đại diện và phần giới thiệu được cho phép, tuyệt đối không công khai username, email, số điện thoại hoặc quyền quản trị.

## Thành phần hiện có

- `dc_articles.poster_id` liên kết bài viết với người đăng.
- `Articles::getObject()` và `Articles::getObjects()` đã trả về `poster_id`, `poster_fullname`, `poster_username`.
- `news_detail.module.php` đã tải `UserInfo` của người đăng và truyền vào template.
- `UserInfo` đã hỗ trợ họ tên, avatar và `properties` mở rộng.
- `news-detail.tpl.html` hiện chưa tạo liên kết đến hồ sơ tác giả và vẫn dùng nhãn tác giả chung.
- Router trong `index.php` chủ yếu là danh sách route tĩnh, chưa có route động cho tác giả.

## Khoảng trống và rủi ro

### Route

- Chưa có URL công khai cho trang tác giả.
- Cần xử lý route động mà không làm ảnh hưởng các route bài viết hiện có.
- Cần giữ tiền tố `/en` và `/zh` cho giao diện đa ngôn ngữ.

### Dữ liệu và quyền riêng tư

- `dc_users` là bảng tài khoản quản trị, chứa nhiều trường riêng tư và phân quyền.
- Không được truyền trực tiếp toàn bộ đối tượng người dùng sang dữ liệu công khai nếu template có thể vô tình hiển thị trường nhạy cảm.
- Chỉ tác giả đang hoạt động và có ít nhất một bài đã xuất bản mới nên có trang công khai.
- Không dùng username làm URL vì đây là định danh đăng nhập; dùng ID số và tên hiển thị làm nội dung trang.

### Nội dung

- Chưa có truy vấn chuyên biệt để lấy bài đã xuất bản theo `poster_id` và ngôn ngữ.
- Cần phân trang, sắp xếp mới nhất và bảo đảm điều kiện `store_id`.
- Bài thiếu bản dịch không được xuất hiện ở trang EN/ZH.

### Giao diện và SEO

- Chưa có template hồ sơ tác giả, breadcrumb, trạng thái rỗng và metadata riêng.
- Tên tác giả trong trang chi tiết chưa liên kết tới trang tác giả.
- Cần fallback ảnh đại diện và tên “Ban biên tập” khi dữ liệu hiển thị không đầy đủ.

## Kế hoạch đề xuất

### 1. Route công khai

- VI: `/tac-gia/{id}`
- EN: `/en/author/{id}`
- 中文: `/zh/author/{id}`
- Chỉ chấp nhận ID nguyên dương; ID không hợp lệ hoặc không đủ điều kiện công khai trả về trang 404.

### 2. Module và truy vấn

- Tạo `modules/estore/editorialauthor.module.php`.
- Tải tác giả theo `store_id`, trạng thái hoạt động và ID hợp lệ.
- Tạo điều kiện lấy bài đã xuất bản theo `poster_id`, ngôn ngữ và trạng thái hiện hành.
- Phân trang với giới hạn cố định; giữ cấu trúc DAO hiện có, không thêm SQL ghép trực tiếp từ input.
- Chỉ tạo một mảng hồ sơ công khai gồm ID, tên hiển thị, avatar và giới thiệu đã lọc.

### 3. Giao diện

- Tạo `templates/mpx/editorial-author.tpl.html` theo design system editorial.
- Hiển thị hồ sơ ngắn, tổng số bài, lưới bài viết và phân trang.
- Có empty state, responsive 375/768/1440px và VI/EN/中文.
- Dùng component card hiện có để tránh sai khác giao diện.

### 4. Liên kết từ bài viết

- Thay nhãn tác giả chung trên trang chi tiết bằng tên hiển thị thực tế khi hợp lệ.
- Liên kết tên tác giả đến route đúng ngôn ngữ.
- Khi không có tác giả hợp lệ, giữ fallback “Ban biên tập” nhưng không tạo liên kết hỏng.

### 5. Kiểm thử

- Kiểm tra route hợp lệ, ID không tồn tại và ID không hợp lệ.
- Kiểm tra lọc đúng tác giả, trạng thái bài, store và ngôn ngữ.
- Kiểm tra không rò rỉ username/email/điện thoại/quyền quản trị trong HTML.
- Chạy PHP syntax check và kiểm tra Smarty template.
- Kiểm tra liên kết qua lại giữa bài viết và trang tác giả trên production.

## Commit dự kiến

1. `feat(author): add public editorial author page`
2. `feat(article): link article byline to author profile`
3. `style(author): refine responsive author experience` — chỉ tạo nếu có thay đổi giao diện độc lập sau kiểm thử.

## Thay đổi cần phê duyệt trước khi code

- Thêm route công khai mới và module/template trang tác giả.
- Công khai tên hiển thị, avatar và phần giới thiệu của tài khoản người đăng; không công khai thông tin đăng nhập hoặc liên hệ.

Không có thay đổi database trong kế hoạch này.
