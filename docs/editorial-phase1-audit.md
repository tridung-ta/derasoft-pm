# Editorial Phase 1 Audit

Ngày audit: 23/09/2026  
Repository: `D:\dung-derasoft`  
Branch: `feature/ten-chuc-nang`  
Commit nền: `1a7ca79 feat: complete editorial member experience and reading hub`

## 1. Phạm vi và nguyên tắc

- Phase 1 chỉ đọc source, kiểm tra tĩnh và lập baseline; không sửa code production, database hoặc cấu hình.
- `includes/config.inc.php` được loại khỏi nội dung audit vì chứa cấu hình nhạy cảm và đang có thay đổi cục bộ của người dùng.
- Các file `templates_c/`, `.vscode/`, `*.bak-*`, `*.bak-v*` không được xem là source chuẩn và không được đưa vào commit.
- Source hiện tại là nguồn sự thật; tính năng đã có sẽ không được tạo lại.

## 2. Baseline Git

- Nhánh hiện tại: `feature/ten-chuc-nang`.
- Working tree không sạch trước khi audit.
- Thay đổi có sẵn: `includes/config.inc.php`, bốn file cache Smarty được track và nhiều file backup/cache untracked.
- Không có thay đổi cũ nào bị xóa, restore hoặc stage trong Phase 1.
- Repository đang track một số file trong `templates_c/`; đây là rủi ro gây nhiễu commit và cần xử lý bằng một task Git hygiene riêng sau khi được duyệt.

## 3. Bản đồ source chính

| Khu vực | Module/controller | Template | CSS/JavaScript/DAO liên quan |
|---|---|---|---|
| Member gateway | `modules/estore/main.module.php` | `templates/mpx/header.tpl.html` | session customer trong bootstrap |
| Đăng nhập | `modules/estore/login.module.php`, `modules/ajax/login.module.php` | `templates/mpx/login.tpl.html` | `editorial-auth.css`, `editorial-auth.js`, `customers.class.php` |
| Đăng ký | `modules/estore/signin.module.php`, `modules/ajax/register.module.php` | `templates/mpx/signin.tpl.html` | `editorial-auth.css`, `editorial-auth.js`, `customers.class.php` |
| Đăng xuất | `modules/estore/logout.module.php` | header account controls | PHP session lifecycle |
| Trang chủ | `modules/estore/editorialhome.module.php` | `editorial-home.tpl.html` và partial editorial | `literature-redesign.css`, `editorial-edition.css` |
| Văn thơ / Nghệ thuật | `modules/estore/editorial_common.module.php`, module route chuyên mục | `editorial-list.tpl.html`, `listarticlesfromsearch.tpl.html` | `editorial-ui.js`, CSS editorial |
| Tin tức | `modules/estore/editorialnews.module.php` | danh sách editorial dùng chung | CSS editorial |
| Video | `modules/estore/video.module.php` | `editorial-video.tpl.html` | `editorial-video.js`, `editorial-library.js` |
| Chi tiết bài | `modules/estore/news_detail.module.php` | `news-detail.tpl.html` | `editorial-reader.js`, `editorial-reader.css` |
| Không gian đọc | `modules/estore/readinghub.module.php`, `modules/ajax/editorial_library.module.php` | `reading-hub.tpl.html` | `editorial-library.js`, `editorial-library.css`, `editorialuserlibrary.class.php` |
| Search & Discovery | `modules/estore/editorialsearchv49.module.php`, `modules/ajax/editorial_search_suggest.module.php` | `editorial-search.tpl.html` | CSS/JS editorial search trong asset chung |
| Admin Editorial | `modules/admin/editorial.module.php` | template admin editorial | migration V48 control center |

## 4. Trạng thái hệ thống hiện tại

### Xác thực và tài khoản

| Hạng mục | Trạng thái | Bằng chứng / ghi chú |
|---|---|---|
| Đăng ký | DONE | AJAX có CSRF, validate email, `password_hash`, rate-limit theo session và tạo customer. |
| Đăng nhập | DONE | Có CSRF, `password_verify`, regenerate session ID và redirect theo `next`. |
| Đăng xuất | DONE | Xóa session/cookie và gửi header chống cache. |
| Member Gateway | DONE | `main.module.php` chuyển khách chưa đăng nhập về route login theo VI/EN/中文. |
| Xác thực email | BROKEN | Logic token và trạng thái có sẵn, nhưng vận chuyển mail tới Gmail chưa ổn định; hiện đang chạy chế độ email optional theo quyết định trước đó. Không sửa mail/config trong Phase 1. |
| Quên/đặt lại mật khẩu | PARTIAL | Có route/module, chưa có bằng chứng kiểm thử end-to-end trong audit này. |

### Editorial và nội dung

| Hạng mục | Trạng thái | Bằng chứng / ghi chú |
|---|---|---|
| Cover Story / Featured | DONE | Trang chủ đọc cấu hình curate và có fallback bài thật. |
| Trending dữ liệu thật | DONE | Dùng override, thống kê `article_view_daily` và fallback `viewed`. |
| Search & Discovery | DONE | Có trang riêng, suggestion, bộ lọc và kết quả bài/Video đa ngôn ngữ. |
| Related Content | DONE | Trang chi tiết render tối đa ba bài liên quan. |
| Reading time | DONE | JS tính theo ngôn ngữ và số lượng từ/ký tự. |
| Mục lục tự động | DONE | JS sinh mục lục từ `h2/h3`, ẩn công cụ khi không có heading. |
| Chia sẻ/copy link | DONE | Web Share API, fallback Facebook và copy link. Zalo riêng chưa thấy trong source chuẩn. |
| Reading progress giao diện | DONE | Có thanh tiến trình theo scroll. |
| Breadcrumb/schema | DONE | Breadcrumb UI và JSON-LD được gán từ module. |
| Video nổi bật / Xem sau | DONE | Video có player, metadata, external link và nút `watch_later`. |
| Admin Editorial Analytics/Control Center | DONE | Module admin và migration V48 hiện diện. |

### Không gian đọc

| Hạng mục | Trạng thái | Bằng chứng / ghi chú |
|---|---|---|
| Lưu bài | DONE | Endpoint yêu cầu đăng nhập + CSRF; DAO upsert theo customer/type/key. |
| Video xem sau | DONE | Dùng cùng endpoint với type `watch_later`. |
| Lịch sử đọc | DONE | Type `history` được DAO và Reading Hub hỗ trợ. |
| Đồng bộ tài khoản | DONE | Dữ liệu gắn `customer_id`, không chỉ lưu localStorage. |
| Resume vị trí chính xác | PARTIAL | Có trường progress và luồng lưu, cần kiểm thử thực tế cơ chế cập nhật định kỳ/khôi phục vị trí. |

## 5. Đối chiếu roadmap 22 tính năng

| # | Tính năng | Trạng thái |
|---:|---|---|
| 1 | Đếm lượt xem | DONE |
| 2 | Thời gian đọc | DONE |
| 3 | Chia sẻ | DONE |
| 4 | Breadcrumb | DONE |
| 5 | Dark/Light mode | MISSING |
| 6 | Lên đầu trang | DONE |
| 7 | Loading skeleton | MISSING |
| 8 | Bài liên quan | DONE |
| 9 | Bình luận editorial | PARTIAL — có hệ bình luận legacy/admin, chưa xác nhận tích hợp UX mới |
| 10 | Tags | MISSING |
| 11 | Không gian đọc | DONE |
| 12 | Tìm kiếm nâng cao | DONE |
| 13 | Newsletter | MISSING |
| 14 | Rating/reactions | MISSING |
| 15 | Trang tác giả | PARTIAL — có metadata tác giả, chưa thấy trang hồ sơ/list riêng hoàn chỉnh |
| 16 | Gợi ý cá nhân hóa | MISSING |
| 17 | Đồng bộ tiến độ đa thiết bị | PARTIAL |
| 18 | Paywall/thành viên trả phí | MISSING |
| 19 | Đa ngôn ngữ nội dung | PARTIAL — route/UI và field nội dung đa ngôn ngữ có sẵn, chưa có workflow dịch đầy đủ |
| 20 | PWA | MISSING |
| 21 | Analytics Dashboard | DONE |
| 22 | Text-to-Speech | MISSING |

Tổng roadmap: **9 DONE, 4 PARTIAL, 0 BROKEN, 9 MISSING**.  
Ngoài roadmap, luồng email verification được ghi nhận là **BROKEN** do phụ thuộc SMTP/hosting.

## 6. Chất lượng code và rủi ro

1. **Design token phân mảnh:** `base.css`, `literature-redesign.css` và `editorial-auth.css` dùng ba nhóm biến khác nhau (`--color/...`, `--ed-*`, `--auth-*`). Chưa có một design system toàn site.
2. **CSS specificity/legacy:** `custom.css`, `base.css`, `responsive.css` cùng tồn tại với lớp editorial mới; nguy cơ selector toàn cục ghi đè header/heading vẫn cao.
3. **File CSS lớn/minified:** nhiều rule editorial nằm trên dòng rất dài, khó review và dễ phát sinh xung đột khi sửa từng phần.
4. **Cache Smarty bị track:** thay đổi runtime xuất hiện trong Git, gây working tree bẩn và có thể bị commit nhầm.
5. **Nhiều backup trong source tree:** `*.bak-*`, `*.bak-v*` làm nhiễu tìm kiếm và đóng gói deploy.
6. **Store ID hard-code:** endpoint library khởi tạo DAO với store `1`; cần audit thêm nếu hệ thống multi-store.
7. **DAO escaping thủ công:** `editorialuserlibrary.class.php` ghép điều kiện SQL và dùng `addslashes`; cần refactor an toàn riêng, không sửa trong Phase 1.
8. **Email verification:** code ứng dụng có logic nhưng lỗi giao nhận nằm ở cấu hình DNS/SPF/DKIM/SMTP/hosting; không được debug credential ra UI.
9. **Route tĩnh bài mẫu:** `index.php` chứa nhiều slug chi tiết được map trực tiếp; cần kiểm tra khả năng mở rộng và fallback router.
10. **Kiểm thử tự động:** chưa thấy test suite rõ ràng cho auth, route, library và đa ngôn ngữ.

## 7. Kiểm thử đã thực hiện

- `php -l`: PASS cho 16 file trọng yếu gồm router, auth, member gateway, home, news, video, search, article detail, Reading Hub, DAO và admin editorial.
- Kiểm tra source: PASS cho sự tồn tại của CSRF, password hashing/verification, session regeneration, library persistence và route đa ngôn ngữ.
- Desktop 1440 / Tablet 768 / Mobile 375: **CHƯA ĐO TRỰC QUAN**. Môi trường audit không có browser automation khả dụng; source có breakpoint tương ứng nhưng đó không thay thế visual test.
- Lighthouse Homepage / Article Detail: **CHƯA ĐO**; không ghi điểm giả. Member Gateway cũng yêu cầu phiên đăng nhập để đo đầy đủ các trang nội dung.
- Production end-to-end: **CHƯA THỰC HIỆN** trong Phase 1 vì không dùng credential người dùng và không vượt cảnh báo SSL.

## 8. Đề xuất cho Phase 2

Phase 2 nên chuẩn hóa design system bằng các commit nguyên tử:

1. `style(design-system): add global editorial color tokens`
2. `style(design-system): standardize editorial typography scale`
3. `style(design-system): add editorial spacing scale`
4. `style(components): standardize editorial buttons`
5. `style(components): standardize article cards`
6. `style(components): standardize category badges`

Trước khi code Phase 2 cần người dùng duyệt audit và xác nhận cách xử lý working tree bẩn. Không được đưa config/cache/backup vào bất kỳ commit nào.
