# Editorial Tags — Audit & Implementation Plan

Ngày audit: 24/09/2026  
Nhánh: `feature/ten-chuc-nang`

## Kết luận

Không cần tạo bảng `tags` và `article_tags` mới. Source hiện tại đã có một hệ thống tương đương tag dưới tên “Nhóm bài viết”, gồm bảng `dc_article_groups` và trường `dc_articles.article_group_ids`. Admin đã có chức năng tạo/sửa/kích hoạt nhóm và form bài viết đã hỗ trợ chọn nhiều nhóm.

Giải pháp ít rủi ro nhất là tái sử dụng nhóm bài viết làm tag editorial ở public, giữ nguyên schema và toàn bộ chức năng cũ.

## Thành phần hiện có

- DAO: `classes/dao/articlegroups.class.php`.
- Data object: `classes/dao/articlegroupinfo.class.php`.
- Bảng nhóm có tên VI/EN/中文, slug, trạng thái và properties.
- Bài viết lưu danh sách ID nhóm trong `article_group_ids` dạng phân tách bằng dấu phẩy.
- Admin quản lý nhóm: `articleaddgroup`, `articleeditgroup`, `articlelistgroup`.
- Form thêm/sửa bài đã cho phép chọn nhiều `article_group_ids`.
- Một số trang dịch vụ cũ đã lọc bài bằng `FIND_IN_SET(group_id, article_group_ids)`.
- Endpoint legacy `modules/ajax/article_group.module.php` trả danh sách bài theo nhóm.

## Khoảng trống và rủi ro

### Public UI

- Trang chi tiết editorial chưa hiển thị tag của bài viết.
- Chưa có trang public tập hợp bài theo tag.
- Chưa có route tag đa ngôn ngữ hoặc empty state/phân trang chuẩn editorial.

### Endpoint legacy

- Hard-code store ID và template `mpx`.
- Chỉ hỗ trợ VI/EN, chưa hỗ trợ 中文.
- Không kiểm tra đầy đủ trạng thái nhóm và phạm vi category trước khi trả dữ liệu.
- Dùng cho khối dịch vụ cũ; sửa trực tiếp có thể ảnh hưởng chức năng production đang chạy.

### Dữ liệu

- Quan hệ nhiều-nhiều đang lưu dạng CSV thay vì bảng nối, nên không có khóa ngoại và truy vấn dùng `FIND_IN_SET`.
- Không được tự động chuyển schema trong feature này vì dữ liệu và Admin hiện tại đang phụ thuộc cấu trúc cũ.
- Một bài có thể chứa ID nhóm không còn hoạt động; giao diện public phải lọc chỉ nhóm `status = 1`.

### Đa ngôn ngữ

- `getNameByLang()` hiện trả trực tiếp tên bản dịch; nếu bản dịch trống cần fallback về tên tiếng Việt.
- Bài EN/中文 phải tiếp tục tuân theo điều kiện slug và `lang` đang dùng trong các trang editorial khác.

## Kế hoạch đề xuất

### 1. Chuẩn hóa dữ liệu public trong DAO

- Bổ sung phương thức lấy các nhóm đang hoạt động theo danh sách ID đã được ép kiểu số.
- Bổ sung fallback tên VI khi `name_en` hoặc `name_zh` trống.
- Không thay đổi phương thức cũ để tránh ảnh hưởng Admin và trang dịch vụ.

### 2. Hiển thị tag trên bài viết

- Module chi tiết bài lấy tag từ `article_group_ids`, chỉ nhận ID nguyên dương và nhóm đang hoạt động.
- Template hiển thị chip tag dưới metadata hoặc cuối nội dung.
- Mỗi chip liên kết đến trang tag đúng ngôn ngữ.

### 3. Trang danh sách theo tag

- Route VI: `/chu-de/{slug}`.
- Route EN/中文: `/en/tag/{slug}` và `/zh/tag/{slug}`.
- Tạo module và template editorial riêng, không dùng endpoint AJAX legacy.
- Chỉ lấy bài đang xuất bản có `FIND_IN_SET(tag_id, article_group_ids)`.
- Hỗ trợ phân trang, card dùng chung, breadcrumb, metadata, empty state và VI/EN/中文.

### 4. An toàn và tương thích

- Validate slug theo danh sách ký tự URL an toàn và tra cứu qua DAO.
- Chỉ công khai nhóm đang hoạt động thuộc đúng `store_id`.
- Không sửa/xóa endpoint legacy, schema, dữ liệu hoặc màn hình Admin hiện có.
- Không nhận ID SQL trực tiếp từ request; ID sử dụng trong điều kiện phải lấy từ object đã tra cứu.

### 5. Kiểm thử

- PHP syntax cho router/module/DAO.
- Tag hợp lệ, slug không tồn tại và slug không hợp lệ.
- Tag ngừng hoạt động không xuất hiện và trả 404 ở trang public.
- Bài không có tag vẫn hiển thị bình thường.
- Lọc đúng bài, store, trạng thái và ngôn ngữ.
- Responsive 375/768/1440px; kiểm tra VI/EN/中文 trên production.

## Commit dự kiến

1. `refactor(tags): expose safe editorial article groups`
2. `feat(article): display editorial tags on article detail`
3. `feat(tags): add public editorial tag archive`
4. `style(tags): refine responsive tag experience` — chỉ tạo nếu có thay đổi giao diện độc lập sau kiểm thử.

## Thay đổi cần phê duyệt trước khi code

- Dùng “Nhóm bài viết” hiện tại làm tag hiển thị ngoài public.
- Thêm route và trang lưu trữ bài viết theo tag.

Không có thay đổi database hoặc migration trong kế hoạch này.
