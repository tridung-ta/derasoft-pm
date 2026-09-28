# Editorial Development Progress

Cập nhật gần nhất: 24/09/2026  
Nhánh làm việc: `feature/ten-chuc-nang`

## Phase 1 — Audit & Baseline

- [x] Kiểm tra Git baseline
- [x] Lập bản đồ module/template/CSS/JS/DAO
- [x] Đối chiếu roadmap với source hiện tại
- [x] Kiểm tra PHP syntax cho các file trọng yếu
- [x] Ghi nhận rủi ro auth, library, CSS, Git và database
- [ ] Responsive visual test tại 375px / 768px / 1440px — blocker: chưa có browser automation khả dụng
- [ ] Lighthouse Homepage / Article Detail — blocker: chưa có browser/Lighthouse và trang nội dung yêu cầu phiên đăng nhập
- [x] Commit tài liệu baseline

Tài liệu: `docs/editorial-phase1-audit.md`

## Phase 2 — Design System

Chưa bắt đầu. Chỉ thực hiện sau khi người dùng duyệt Phase 1.

- [x] Global editorial color tokens
- [x] Typography scale
- [x] Spacing scale
- [x] Buttons
- [x] Article cards
- [x] Category badges

## Phase 3 — Page Improvements

Chưa bắt đầu.

### Authentication

- [x] Audit lại UI trước khi sửa
- [x] Inline validation
- [x] Loading state
- [x] Feedback UI
- [x] Mobile polish

### Homepage

- [x] Skeleton loading
- [x] Card image hover đã tồn tại trong source
- [x] Normalize spacing theo token Phase 2
- [x] Loại nội dung trùng giữa Cover/Featured và Trending

### Category

- [x] Masonry view cho Nghệ thuật & Văn hóa
- [x] Lưu bài dùng thư viện theo tài khoản
- [x] Kiểm thử Grid/Compact responsive

### Article Detail

- [x] Estimated reading time
- [x] Automatic table of contents
- [x] Share/copy actions
- [x] Related content
- [x] Hoàn thiện và kiểm thử resume reading progress

## Phase 4 — Polish

Đã hoàn thành và đã kiểm tra trên production.

- [x] Page transitions
- [x] Global toast system
- [x] Back-to-top đã tồn tại trên giao diện editorial
- [x] Dark mode
- [x] Reduced-motion hoàn chỉnh cho các tương tác editorial mới

## Feature Roadmap — Nhóm trung bình

### Đánh giá bài viết

- [x] Tạo bảng đánh giá editorial riêng, không trộn dữ liệu comment/sản phẩm legacy
- [x] Một tài khoản chỉ có một đánh giá cho mỗi bài và được phép cập nhật mức sao
- [x] Bảo vệ endpoint bằng đăng nhập, CSRF và giới hạn tần suất
- [x] Giao diện 5 sao responsive, hỗ trợ VI/EN/中文
- [x] Migration và rollback riêng
- [x] Chạy migration và kiểm tra hoạt động trên production

Commits:

- `37f2b84` — `feat(rating): add secure editorial rating data layer`
- `4633f77` — `feat(article): add member rating interface`
- `c1e4495` — `fix(smarty): escape dark mode bootstrap script`
- `7a7202f` — `refactor(header): remove redundant global search`

### Bình luận bài viết

- [x] Audit hệ bình luận legacy và luồng kiểm duyệt hiện có
- [x] Lập kế hoạch dữ liệu/API/giao diện an toàn
- [x] Tạo migration và rollback cho bảng bình luận editorial riêng
- [x] Hoàn thiện API yêu cầu đăng nhập, CSRF, validation và rate limit
- [x] Hoàn thiện giao diện bình luận đa ngôn ngữ trên trang bài viết
- [x] Hoàn thiện màn hình kiểm duyệt bình luận trong Admin
- [x] Chạy migration và kiểm tra hoạt động trên production

Tài liệu: `docs/editorial-comments-audit.md`

Commits:

- `e75c4a1` — `feat(comments): add editorial comment persistence layer`
- `4fc0a10` — `feat(comments): add secure article comment API`
- `297bc09` — `feat(article): add member comment interface`
- `c683e62` — `feat(admin): add editorial comment moderation`

### Trang tác giả

- [x] Audit dữ liệu tác giả, route và giao diện hiện có
- [x] Lập kế hoạch triển khai không thay đổi database
- [x] Thêm route VI/EN/中文 và trang danh sách bài viết theo tác giả
- [x] Chỉ công khai tên hiển thị, avatar và giới thiệu đã lọc
- [x] Liên kết tên tác giả trên trang chi tiết bài viết
- [x] Kiểm tra PHP syntax và Git diff
- [ ] Kiểm tra giao diện và route trên production

Tài liệu: `docs/editorial-author-page-audit.md`

Commits:

- `414e128` — `feat(author): add public editorial author page`
- `dad2612` — `feat(article): link article byline to author profile`
- `131dc29` — `fix(author): use existing localized biography fields`

### Hệ thống tag

- [x] Audit cơ chế nhóm bài viết và dữ liệu gắn bài hiện có
- [x] Lập kế hoạch tái sử dụng `article_groups`, không thay đổi database
- [x] Chuẩn hóa cách lấy nhóm bài đang hoạt động và fallback đa ngôn ngữ
- [x] Hiển thị tag và liên kết chủ đề trên trang chi tiết bài viết
- [x] Thêm trang lưu trữ tag VI/EN/中文, phân trang và 404
- [x] Kiểm tra PHP syntax, Smarty compile và Git diff
- [ ] Kiểm tra giao diện và route trên production

Tài liệu: `docs/editorial-tags-audit.md`

Commits:

- `c1aed0d` — `refactor(tags): expose safe editorial article groups`
- `78a09dd` — `feat(article): display editorial tags on article detail`
- `8998637` — `feat(tags): add public editorial tag archive`

### Newsletter

- [x] Audit dữ liệu email, footer, mail transport và luồng unsubscribe hiện có
- [x] Lập kế hoạch tách subscriber có consent khỏi dữ liệu comment/customer
- [x] Tạo migration, rollback và DAO subscriber riêng
- [x] Chạy migration trên production
- [x] Thêm API đăng ký có CSRF, validation, honeypot và rate limit
- [x] Thêm form footer VI/EN/中文 với loading và feedback
- [x] Thêm luồng hủy đăng ký bằng token hash và POST xác nhận
- [x] Thêm quản lý subscriber trong Editorial Control Center
- [x] Kiểm tra PHP syntax, JavaScript syntax và Smarty compile
- [ ] Kiểm tra form đăng ký, dữ liệu pending, Admin và responsive trên production
- [ ] Double opt-in và gửi chiến dịch — chờ SMTP/SPF/DKIM hoạt động ổn định

Tài liệu: `docs/editorial-newsletter-audit.md`

Commits:

- `893d428` — `feat(newsletter): add subscriber persistence layer`
- `fcfac04` — `feat(newsletter): add secure subscription API`
- `232b458` — `feat(footer): add multilingual newsletter form`
- `2ee4ef7` — `feat(newsletter): add secure unsubscribe flow`
- `5478892` — `feat(admin): add newsletter subscriber management`

## Feature Roadmap — Nhóm khó

### Gợi ý cá nhân hóa

- [x] Audit lịch sử đọc, bài đã lưu và dữ liệu chuyên mục hiện có
- [x] Lập kế hoạch content-based không dùng ML và không đổi database
- [x] Xây dựng dịch vụ xếp hạng theo chuyên mục/chủ đề từ bài đã lưu và lịch sử đọc
- [x] Loại nội dung đã tương tác, lọc trạng thái/store/ngôn ngữ và bổ sung fallback phổ biến
- [x] Thêm khối “Dành cho bạn” tối đa 6 bài trong Không gian đọc
- [x] Kiểm tra PHP syntax, Smarty compile và Git diff
- [ ] Kiểm tra dữ liệu gợi ý và giao diện responsive trên production

Tài liệu: `docs/editorial-recommendations-audit.md`

Commits:

- `bbd9e9c` — `feat(recommendations): add content-based recommendation service`
- `029df88` — `feat(reading-hub): add personalized article recommendations`

### Đồng bộ tiến độ đọc đa thiết bị

- [x] Audit luồng lưu/khôi phục progress hiện có
- [x] Lập kế hoạch hoàn thiện không dùng WebSocket và không đổi database
- [x] Chống request cũ ghi lùi progress đã đồng bộ
- [x] Thêm API lấy một history item và giảm chu kỳ ghi xuống tối đa mỗi 5 giây
- [x] Gửi lần đồng bộ cuối bằng `keepalive` khi tab bị ẩn hoặc đóng
- [x] Thêm CTA đọc tiếp, phần trăm và thời điểm đồng bộ trong Không gian đọc
- [x] Kiểm tra PHP syntax, JavaScript syntax, Smarty compile và Git diff
- [ ] Kiểm tra luồng hai thiết bị và giao diện responsive trên production

Tài liệu: `docs/editorial-reading-sync-audit.md`

Commits:

- `ffbcf9f` — `fix(reading): prevent stale progress regression`
- `6e51bac` — `feat(reading): make cross-device progress sync resilient`
- `572e690` — `feat(reading-hub): improve continue-reading experience`

### Thành viên trả phí và paywall

- [x] Audit tài khoản, customer group, đơn hàng và payment method legacy
- [x] Xác định kiến trúc membership tách khỏi đơn hàng sản phẩm
- [x] Lập kế hoạch migration cộng thêm và rollout hai giai đoạn
- [x] Duyệt Giai đoạn A: membership foundation chưa kết nối thanh toán thật
- [x] Tạo migration/rollback cho membership plan và subscription
- [x] Chạy migration và xác nhận schema trên production
- [x] Triển khai DAO và entitlement service
- [x] Thêm quản lý gói/quyền thủ công trong Editorial Control Center
- [x] Thêm cờ bài premium và thực thi paywall ở backend
- [x] Bảo đảm nội dung premium không xuất hiện trong HTML khi chưa đủ quyền
- [x] Hoàn thiện paywall responsive bằng VI/EN/中文
- [x] Kiểm tra PHP syntax và 63 lượt Smarty smoke test
- [ ] Kiểm tra trực quan bài miễn phí/premium trên production bằng tài khoản có và chưa có quyền
- [x] Tạo payment transaction ledger và migration rollback riêng
- [x] Tích hợp adapter VNPay 2.1.0 dùng HMAC-SHA512
- [x] Thêm checkout lấy giá trực tiếp từ membership plan trong database
- [x] Thêm Return URL chỉ hiển thị kết quả và IPN kích hoạt quyền theo transaction
- [x] Chống IPN lặp bằng row lock và xử lý subscription/payment trong cùng transaction
- [x] Thêm giao diện mua/gia hạn gói responsive bằng VI/EN/中文
- [x] Kiểm tra 69 lượt Smarty smoke test và payment smoke test
- [ ] Cấu hình credential/Return URL/IPN URL trên VNPay sandbox và hosting
- [ ] Chạy giao dịch sandbox end-to-end trên production

Tài liệu: `docs/editorial-membership-paywall-audit.md`

Commits:

- `68b2c13` — `feat(membership): add plans and subscriptions schema`
- `9141e5a` — `feat(membership): add subscription persistence and entitlement service`
- `6bb21f6` — `feat(admin): add editorial membership management`
- `7e22a17` — `feat(article): enforce premium content access`
- `293aca1` — `style(paywall): add multilingual membership gate`
- `0d70e63` — `feat(payments): add membership transaction ledger`
- `f9ede61` — `feat(payments): add secure VNPay gateway adapter`
- `8ed0293` — `fix(payments): snapshot purchased plan duration`
- `da086ff` — `feat(payments): process idempotent VNPay callbacks`
- `7b75cd5` — `feat(payments): add VNPay checkout return and IPN routes`
- `e3ddff9` — `style(membership): add responsive VNPay checkout interface`

### Đa ngôn ngữ nội dung

- [x] Audit cơ chế ngôn ngữ, slug và dữ liệu bài viết hiện có
- [x] Tái sử dụng `properties`, không tạo bảng hoặc cột database mới
- [x] Thêm trình soạn thảo English/中文 vào form tạo và sửa bài
- [x] Lưu tiêu đề, từ khóa, mô tả và nội dung theo từng ngôn ngữ
- [x] Chỉ công khai ngôn ngữ đã chọn khi slug và nội dung bắt buộc hợp lệ
- [x] Luôn giữ Tiếng Việt làm nội dung gốc và fallback an toàn
- [x] Kiểm tra PHP syntax, Smarty Admin, fallback và template public VI/EN/中文
- [ ] Kiểm tra trực quan thao tác tạo/sửa bản dịch trên production

Không thay đổi database.

Commits:

- `5b16347` — `feat(admin): add multilingual article editor`
- `b656415` — `fix(article): validate translated article availability`

## Quy tắc commit

- Một chức năng hoàn chỉnh = test + một commit riêng.
- Chỉ stage đúng file thuộc task; không dùng `git add .`.
- Không commit `includes/config.inc.php`, `templates_c/`, `.vscode/`, database dump hoặc file backup.
- Bug phát hiện trong test phải có commit `fix(...)` riêng.
- Không empty commit, không commit giả, không squash nếu người dùng chưa yêu cầu.
