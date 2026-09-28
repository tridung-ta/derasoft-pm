# Editorial Personal Recommendations — Audit & Implementation Plan

Ngày audit: 24/09/2026  
Nhánh: `feature/ten-chuc-nang`

## Kết luận

Có thể triển khai gợi ý cá nhân hóa phiên bản đầu bằng dữ liệu hiện có, không cần ML và không cần thay đổi database. `dc_editorial_user_library` đã lưu lịch sử đọc/bài đã lưu theo `customer_id`; `item_key` của bài viết là ID bài và bảng `articles` đã có `category_id`, nhóm bài và lượt xem.

Phiên bản đầu nên đặt trong Không gian đọc, tính theo yêu cầu và chỉ dùng hành vi của chính tài khoản đang đăng nhập.

## Dữ liệu hiện có

- `EditorialUserLibrary` hỗ trợ `saved`, `history`, `watch_later`.
- Lịch sử đọc lưu ID bài, thời điểm hoạt động, progress và payload hiển thị.
- Bài viết có category, nhóm bài, trạng thái, ngôn ngữ và lượt xem.
- Reading Hub đã được bảo vệ bằng session thành viên.
- Chưa có bảng recommendation, điểm sở thích hoặc tác vụ nền.

## Kế hoạch đề xuất

1. Lấy tối đa 50 bài `history` và `saved` gần nhất của thành viên.
2. Tra category/tag của các ID bài hợp lệ, tăng trọng số cho bài đã lưu và bài đọc có progress cao.
3. Chọn tối đa 6 bài đang xuất bản thuộc category/tag có điểm cao nhất.
4. Loại trừ bài đã đọc/đã lưu, lọc đúng store và ngôn ngữ.
5. Nếu chưa đủ dữ liệu, fallback sang bài phổ biến/mới nhất nhưng vẫn loại trừ nội dung đã tương tác.
6. Hiển thị khối “Dành cho bạn” trong Không gian đọc, giải thích ngắn lý do gợi ý.
7. Không lưu hồ sơ suy luận mới, không chia sẻ dữ liệu giữa tài khoản và không dùng dịch vụ ngoài.

## Rủi ro cần kiểm soát

- `item_key` là chuỗi: chỉ chấp nhận ID nguyên dương trước khi đưa vào query.
- Tránh query N+1 bằng truy vấn gom nhóm.
- Không gợi ý bài tắt, bài thiếu bản dịch hoặc bài ngoài store.
- Không để khối gợi ý làm hỏng Reading Hub nếu dữ liệu lịch sử cũ có payload lỗi.
- Cần giới hạn số bản ghi đầu vào và cache trong request để giữ hiệu năng.

## Commit dự kiến

1. `feat(recommendations): add content-based recommendation service`
2. `feat(reading-hub): add personalized article recommendations`
3. `style(reading-hub): refine recommendation cards` — chỉ khi cần thay đổi độc lập sau kiểm thử.

## Thay đổi cần phê duyệt trước khi code

- Dùng lịch sử đọc và bài đã lưu của thành viên để suy ra category/tag quan tâm.
- Hiển thị tối đa 6 gợi ý trong Không gian đọc.

Không thay đổi database, không dùng API ngoài và không triển khai ML.
