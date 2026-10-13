# Gói đồng bộ giao diện–backend — 09/10/2026

Đây là bản cập nhật cho host DeraSoft PM đã chạy các phase trước, không phải gói cài mới. Chuẩn bị local theo yêu cầu; chưa upload, chưa kiểm thử DB/phiên đăng nhập thật trên host. Mã nguồn và SHA256 chính xác nằm trong review/WORKSPACE_MANIFEST.json của gói.

## Thư mục trong ZIP

- runtime/: upload NỘI DUNG thư mục này vào web root có admin.php, pm_ajax.php, classes/, templates/. Giữ cây thư mục. Không upload nguyên thư mục runtime thành một đường dẫn mới.
- manual-test/: một workbook hướng dẫn/dữ liệu và ba file import. Chỉ mở trên máy và chọn file trong màn Import; không chép thư mục này vào web root.
- review/: hướng dẫn, bằng chứng local và truy vấn preflight CHỈ ĐỌC. Không upload vào web root.
- SHA256SUMS.txt: hash từng file trong gói để đối chiếu truyền file.

Không có config local, license, dump, dữ liệu thật, mật khẩu, log, cache, test router hay migration tự chạy trong runtime. Không có DB test để tạo. Các file .local HTML xem UI không phải ứng dụng đăng nhập, không dùng để lưu trạng thái trên host.

## Trước upload

1. Backup mã nguồn và DB đích; ghi đường dẫn web root, phiên bản PHP và phiên bản ứng dụng hiện tại. PHP 8.3 là phiên bản local đã lint/test; không tự suy phiên bản hosting tương thích.
2. Mở review/PREFLIGHT_SCHEMA.sql trong phpMyAdmin đúng DB và chạy từng truy vấn đọc. Không chạy lại 001–010 hàng loạt: lỗi duplicate password_hash trước đây là bằng chứng cột đã tồn tại, không phải lý do xóa cột.
3. Đối chiếu đủ bảng PM, cột metadata 009 (projects.client_name, project_members.project_role, tasks.start_date/completed_at) và timesheet_days.max_total_hours từ 010; email UNIQUE cùng store_id, engine dc_users giữ nguyên. Nếu thiếu, DỪNG upload để xử lý migration còn thiếu theo duyệt riêng. Gói này không thêm schema.
4. Ba preflight lịch sử: số store_id thật phải chốt trước chạy 002 nếu còn thiếu; giữ bảo trì xuyên suốt mọi writer trong lúc triển khai; ước lượng khóa 007 trên bản sao đại diện nếu cần thêm unique index. Nếu 002/007 đã xong, chỉ xác minh, không chạy lại.
5. Bật bảo trì cho PM, legacy/API/cron và writer khác. Chỉ đọc/ghi thử sau khi file đã đồng bộ xong; không trộn code mới/cũ giữa chừng.
6. PM_HIDE_LEGACY mặc định true, triển khai giữ true. Giữ config/license host; session storage phải ghi được, cookie admin và pm_ajax phải cùng host/path/HTTPS. Không thêm host giả hay bypass auth.

## Upload và kiểm tra

1. Upload nội dung runtime/ bằng FileZilla Binary; chờ queue hết, kiểm tra Failed transfers.
2. Đối chiếu checksum với manifest; PHP hosting dùng OPcache thì làm mới bằng cơ chế hosting cho phép. Làm mới compiled Smarty qua công cụ hosting, không xóa toàn bộ thư mục/cache hay dữ liệu.
3. Đăng nhập từ admin.php trên đúng host rồi mở Dự án. Network phải gọi pm_ajax.php?op=pmtaskstatus cùng host, gửi cookie và CSRF. Không kiểm tra nút trên .local HTML.
4. Dùng các tài khoản UAT dưới đây thử save/ẩn, nút status và kéo-thả; reload để chứng minh đã lưu. Nếu 401: kiểm tra login/cookie/session save_path, trạng thái user và store_id; nếu403: CSRF/quyền. Không tắt guards để chữa lỗi.
5. Kiểm tra dashboard bằng PM/Admin/Employee/HR, hồ sơ/mật khẩu audit ID-only, legacy ajax count404, validator nhân sự, giới hạn tổng giờ/OT và Excel.
6. Ghi kết quả thật và log server (không đưa password/cookie/hash/token vào báo cáo). Chỉ mở lại bảo trì khi bạn quyết định sau kiểm thử.
7. Nếu lỗi: khôi phục các file tương ứng từ backup, giữ nguyên schema/data/additive columns, không DELETE/DROP/TRUNCATE. Không ghi đè DB từ dữ liệu mẫu.

## Dữ liệu kiểm thử đã chuẩn bị

Mở manual-test/BO_DU_LIEU_UAT.xlsx, làm theo thứ tự sheet Hướng dẫn → Nhân sự → Dự án → Công việc → Chấm công/Phân bổ → Kịch bản. Sheet Chi phí có công thức theo setting/snapshot thực, mẫu mặc định8giờ thường/24giờ tổng/OT1.00/rate100000VND.

- Phòng ban UAT261009; 2 PM, 1 HR, 2 Employee và 1 no-role (nếu UI cho phép bỏ toàn bộ role).
- 2 dự án, 6 task; đã có giá trị nhập và lịch phù hợp cửa sổ chấm công ngày09/10/2026.
- Tự đặt mật khẩu riêng và ghi lại ID host. Không dùng Admin mới. Email example.com là dữ liệu giả chỉ kiểm tra định dạng, không chứng minh mailbox tồn tại; tel để trống.
- Nếu chạy sau09/10, dịch D về ngày chạy, giữ offset: projectD-7..D+14, taskD-3, deadlineD-1/D+1, timesheetD/D-1, allocationD. Không nhập ngày quá cửa sổ3ngày bằng Employee.
- Không thay setting tenant chung để đạt kỳ vọng. Rate/capacity chỉ tạo cho accountUAT. Đọc snapshot thật; công thức workbook là kỳ vọng theo input, không chứng minh dữ liệu host.
- Chạy ca12–13 trước ca14/17 để chi phí ban đầu A là500000/2000000=25% khi snapshot thường8 và rate100000. D2 actual5h/estimate4h phải xuất hiện riêng; estimate0 không cảnh báo.
- 01_import_valid.xlsx: 1sheet/6cột, 2user EMPLOYEE. Tạo phòng ban UAT261009 trước; nhập bằng Import, Stage/Apply. User mới inactive; cấp password tại kết quả rồi kích hoạt.
- 02_import_invalid.xlsx: email sai, username31 ký tự, roleADMIN; phải từ chối cả file, báo theo dòng.
- 03_import_collision.xlsx: hai email trùng khác hoa/thường; kiểm tra theo collationDB, phải từ chối nếu collation đang dùng coi bằng nhau.
- BO_DU_LIEU_UAT.xlsx có8sheet, chỉ để tham khảo/ghi kết quả, KHÔNG nhập vào màn Import.
- Nếu prefix UAT261009 đã tồn tại, không nhập lại hoặc sửa user cũ; dùng bộ đã có và ghi rõ, hoặc đổi prefix đồng bộ trong mọi file trước tạo. Nếu có bất kỳ dữ liệu thật trùng, dừng để quyết định.
- Mỗi ca trong sheet Kịch bản đang CHƯA CHẠY. Chỉ đổi PASS/FAIL sau khi bạn chạy thật và thêm ảnh/network/audit/ID. Tenant thật thứ2 cần quyền và tenant đã tồn tại; nếu không có giữ CHƯA CHẠY, không tạo DB/store.
- Khi kết thúc, chỉ ẩn/khóa bộ UAT bằng UI nếu muốn, giữ lịch sử. Không xóa hay thay dữ liệu hiện có.

## Đã kiểm chứng local và giới hạn

- Service dashboard bằng stub không DB: permission/provider gates, prepared tenant/PM ownership contracts, dates/EXISTS, điều kiện currency/rate/budget, lỗi từng family; không gọi SQL thật.
- Smarty, JavaScript/browser bằng dữ liệu giả và mock; guard task chưa đăng nhập gọi thật trả401 trước DB. Save đăng nhập thật, audit transaction, MySQL DECIMAL/query plans/concurrency/import Apply trên DB CHƯA CHẠY theo yêu cầu không dùng DBtest.
- Chi phí giữ giao diện đã được chốt; dashboard dùng service Chi phí hiện có, hiển thị tối đa20dự án trang đầu và link phần còn lại, tối đa8late project/cảnh báo allocation. Nguồn dashboard allocation chỉ tải100dòng đầu trong tuần; khi cắt có nhãn và link, không coi khôngcảnhbáo trên phần đã tải là khôngcảnhbáo toàn bộ.
- Full pm_regression.ps1 và EXPLAIN không chạy lại vì có thao tác DB/fixtures; test mới đã đăng ký để chạy khi sau này có môi trường phù hợp. Không dùng kết quả cũ để gọi gói này production PASS.

## Bổ sung gói thông báo ngày 12/10/2026
Gói ngày 12/10 thay cho gói workspace ngày 09/10, gồm runtime cập nhật đầy đủ. Không cần chạy thêm SQL/migration nếu schema hiện có đã đạt preflight. Upload nội dung runtime như hướng dẫn trên; giữ config, license, uploads và dữ liệu.

optional/maintenance.html chỉ dùng khi host đang phục vụ đúng file maintenance.html: backup file host rồi thay nội dung nếu cần. Nếu thông báo do hosting/proxy sinh ra thì file này không điều khiển nó. Không tự đổi MAINTENANCE hay chế độ bảo trì.

Asset JS/CSS có query version 20261012-vi. Sau upload, làm mới compiled Smarty/OPcache qua công cụ hosting và Ctrl+F5. Thử required/email/tel/đơn giá, thông báo thành công/lỗi, session hết hạn và mất mạng. Nhãn “Cần kiểm tra”/“Đã hoàn tất” phải hiện, lỗi dài xuống dòng, lỗi không bị toast che.

Dữ liệu Excel vẫn là bộ mẫu 09/10: nếu kiểm thử ngày khác, dịch các ngày theo hướng dẫn; 30 ca chưa tự thực hiện. Không upload review/manual-test/optional cả thư mục vào web root.

## Bản cuối thông báo giữa màn hình ngày13/10
Gói centered thay gói compact trước. Kiểu thông báo đặt giữa màn hình theo yêu cầu mới; asset CSS version20261013-centered. Review/CENTERED_NAV_VERIFY.md có kiểm tra menu12trang và hướng dẫn đối chiếu host. Upload nội dung runtime một lần, giữ config/DB; chưa có bằng chứng nguyên nhân menu cũ trên host. Không migration mới.

## Bản cuối top10s ngày13/10
Quyết định mới: thông báo thành công căn giữa ngang nhưng nằm phía trên dưới thanh tài khoản, không nằm giữa chiều dọc; tự ẩn sau10giây. Lỗi vẫn hiện. Khung480px, chữ16/15px; asset version20261013-top10s. Dùng ZIP cuối chứa review/TOP_TOAST_VERIFY.md, thay bản centered trước; không thêmmigration.

## 13/10 — banner refined
Thông báo trắng/viền xanh nhẹ, dấu kiểmSVG, chữ15px,440px rộng; top-center10s giữ nguyên. Browser2suites và16no-DBscripts PASS. Asset20261013-refined; khôngBE/DB/production. Xem TOAST_VISUAL_VERIFY_20261013.md, gói mới thay bản trước.

## Gói cuối navshift ngày13/10
Gói mới gom thông báo refined top10s, nút Điều hướng hiện desktop/mobile và nhãn ca chuẩn. Asset20261013-navshift. Upload toàn bộ runtime gồm helper pmshiftlabelservice.class.php; không migration mới. Review/NAV_SHIFT_VERIFY.md có bằng chứng và hostUAT. Workbook mẫu khi ghi công chọn Ca linh hoạt hoặc ca phù hợp trong4choices; nhãn cũ giữ nguyên nếu không đổi. Làm mới Smarty/OPcache vàCtrl+F5.
