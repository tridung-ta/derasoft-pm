# Phase 11 — nghiệm thu local

## Production update from operator - 2026-10-07

User confirmed the full Phase 11 package was uploaded, is running stably and
has shown no errors so far. The earlier question about full upload versus the
login patch is resolved. This is an operator report, not agent production
verification or a per-case UAT result. Pending role/Excel/zoom cases below are
not automatically marked PASS. No new runtime changes or tests in this update.

## Xác nhận mới nhất — 07/10/2026

- Người dùng xác nhận lỗi giao diện login đã xử lý xong sau bản vá 2 file
  `e6f164f`. Chỉ ghi nhận mục giao diện đăng nhập, không suy ra toàn bộ app đạt UAT.
- Ảnh phpMyAdmin đã xác nhận bốn cột của migration 009 trong `dung_pm` đúng kiểu
  và nullable; không cần chạy lại ALTER trên cơ sở bằng chứng này. Đây là bằng
  chứng schema hiện có, không xác nhận agent đã chạy migration hoặc có backup.
- Đang xác nhận operator đã upload gói đầy đủ 86 file hay chỉ bản vá login để
  chọn bước triển khai/nghiệm thu tiếp theo. Agent chưa push/upload production.

**Quy trình cập nhật 06/10/2026:** người dùng dừng kiểm thử tay local, yêu cầu
agent kiểm thử lại toàn bộ phạm vi PM trước và sẽ kiểm thử tay sau triển khai.
Các mục chưa xác nhận bên dưới chuyển sang chờ kiểm thử thủ công sau triển khai,
không chặn agent hoàn tất VERIFY local và không tự đổi thành UAT PASS.
Kết quả agent: [LOCAL_VERIFY_PHASE11.md](LOCAL_VERIFY_PHASE11.md), 46 regression
scripts và 14 browser suites PASS. Chưa merge/push/deploy; migration 009 cần duyệt riêng.

BUILD đã kiểm tra tự động; chưa tick UAT thay người dùng. Nhánh local
feature/pm-phase11-requirements, runtime 1cad212 (gồm sửa mật khẩu 7227906,
Bootstrap form controls và cải tiến nút/form), schema 009 chỉ chạy local.

1. Đăng nhập Admin: Nhân sự, Dự án, Chấm công, Báo cáo, Import, sidebar D và
   đăng xuất giữ giao diện Inter; desktop/mobile, zoom trình duyệt 200%.
2. Dự án: nhập khách hàng; thêm thành viên với vai trò Developer/QA; role này
   không thay quyền RBAC. Task nhập ngày bắt đầu/hạn, chặn ngày đảo ngược.
3. Task todo → done: ngày hoàn thành tự ghi. Sửa task done giữ ngày; mở lại
   todo xóa ngày hiện tại. Nhật ký có actor/time và trước/sau; PM chỉ dự án mình.
4. Báo cáo giờ: giờ thường/OT theo tuần, lọc ngày/user/project/role. Báo cáo task:
   task hoàn thành theo tuần, trễ bao nhiêu ngày, quá hạn và ngày chưa xác định;
   không có tỷ lệ hiệu suất. Kiểm tra trạng thái/phân loại hiện tại được ghi rõ.
5. Chi phí: user/role filters, actual/estimate, phòng ban/role/task; tiền khác
   currency không được cộng. Ngân sách toàn dự án không đổi vì lọc nhân viên.
6. Export các loại users/projects/hours/tasks/costs; mở XLSX bằng Excel,
   chuỗi bắt đầu =/@ vẫn là text. Users không có password/hash; projects không
   có budget/rate. Thu hẹp bộ lọc khi vượt 5000 dòng.
7. Tài khoản PM/HR/Employee thật: xác nhận phạm vi được xem/xuất, không thấy
   dữ liệu ngoài quyền. Kiểm tra import preview với dữ liệu giả, không Apply
   lên production khi chưa có kế hoạch test-data được duyệt.
8. Mật khẩu: trên cùng một tài khoản local, đăng nhập bằng username và email
   với cùng mật khẩu; đổi mật khẩu trong PM, đăng xuất, thử lại cả hai định danh.
   Mật khẩu mới phải dùng được với cả hai; mật khẩu cũ phải bị từ chối với cả hai.
   Tài khoản bị khóa không đăng nhập được. Không dùng tài khoản production để test.
9. Bootstrap: mở form thêm/sửa nhân sự, dự án/task, chấm công, báo cáo và import.
   Nút thường đen, Khóa/Xóa đỏ, Mở khóa/Khôi phục không đỏ; tab bàn phím có focus.
   Kiểm tra mobile và zoom thật 200%; chọn file, select, modal và confirmation
   vẫn hoạt động. Không có lỗi JS mới. Text scaling tự động không thay kiểm tra zoom thật.

| Hạng mục | Kết quả người dùng | Ghi chú/lỗi |
| --- | --- | --- |
| Giao diện đăng nhập | Đạt — người dùng xác nhận 07/10/2026 | Lỗi chữ mờ đã xử lý; không bao gồm nghiệm thu các module khác |
| Giao diện/Bootstrap/sidebar/logout | Chưa nghiệm thu | |
| Nhân sự: tìm kiếm/sửa/khóa/mở khóa | Chờ người dùng thực hiện | Dùng nhân sự thử trên local; không thao tác tài khoản đang đăng nhập |
| Email/username/đổi mật khẩu | Đạt — người dùng xác nhận 06/10/2026 | Cả hai dùng mật khẩu mới; mật khẩu cũ bị từ chối |
| Dự án/task/metadata/audit | Chưa nghiệm thu | |
| Chấm công/OT/chi phí | Chưa nghiệm thu | |
| Báo cáo/XLSX | Chưa nghiệm thu | |
| PM/HR/Employee và import | Chưa nghiệm thu | |

## Lượt nghiệm thu tiếp theo: Nhân sự và Bootstrap

Thực hiện trên local bằng Admin, dùng một nhân sự thử riêng:

1. Mở Nhân sự (`admin.php?op=pmusers`), tìm nhân sự thử theo tên/email.
2. Bấm Sửa: kiểm tra đúng người, sửa số điện thoại, Lưu; tải lại và xác nhận
   dữ liệu đã giữ. Đóng form và kiểm tra focus trở về nút Sửa.
3. Khóa nhân sự thử: nút Khóa đỏ, trạng thái chuyển thành đã khóa; Mở khóa
   là hành động thường, không đỏ. Mở khóa lại sau khi kiểm tra.
4. Thu màn hình về mobile, thử zoom trình duyệt 200% và dùng Tab:
   form/nút không bị cắt, focus rõ, bảng có thể cuộn trong vùng bảng.
5. Kiểm tra sidebar có biểu tượng D, nút Đăng xuất dễ thấy; Console không
   có lỗi JS mới. Đăng xuất sau khi đã lưu và mở khóa lại nhân sự thử.

Phản hồi từng mục **Đạt / Lỗi / Chưa thử**, kèm thao tác và ảnh nếu có lỗi.
Chỉ cập nhật trạng thái tương ứng sau phản hồi; chưa có kết quả mới cho lượt này.

Xem local: `http://localhost/derasoft-pm/admin.php?op=login` nếu Apache ánh xạ
workspace tại `/derasoft-pm`; nếu chạy workspace ở document root, dùng
`http://localhost/admin.php?op=login`. Route sau đăng nhập: `op=pm`, `op=pmusers`,
`op=pmprojects`, `op=pmtimesheets`, `op=pmreports`, `op=pmimports`.
Đây là ví dụ ánh xạ local; không khẳng định Apache đang chạy ở URL này.

## Kiểm tra hỗ trợ nghiệm thu — 06/10/2026

- Runtime 2bb9665, branch feature/pm-phase11-requirements; schema 009 local.
- Chạy lại `powershell -NoProfile -ExecutionPolicy Bypass -File tests/pm_regression.ps1`:
  45 scripts PASS, gồm 20 shared-password checks, HTTP/service/Excel round-trip
  và 101 prepared query shapes EXPLAIN. Không áp migration bởi runner.
- Refresh 9 Smarty preview fixtures từ template hiện hành; chạy 9 browser suites
  Phase 9b bằng `.local/run-phase11-ui.js`: PASS. Bootstrap controls browser: PASS.
  Fixture ảnh/dữ liệu giả; không thay nghiệm thu nội dung bằng mắt hoặc zoom thật.
- Sửa race trong test font: chờ fonts/render và đưa glyph probe vào viewport
  trước CDP font inspection; sau đó xác nhận glyph tiếng Việt dùng Inter.
- Người dùng xác nhận mục mật khẩu: email/username đều dùng mật khẩu mới;
  mật khẩu cũ bị từ chối. Không suy ra nghiệm thu tài khoản khóa/collision hay các mục khác.
- PM/HR HTTP bằng tài khoản thật chưa có bằng chứng; service permission tests
  không thay phần này. XLSX đã round-trip tự động nhưng chưa mở trực tiếp trong Excel.

Kết quả: **VERIFY LOCAL ĐẠT TRONG PHẠM VI ĐÃ CHẠY; MẬT KHẨU ĐƯỢC NGƯỜI DÙNG
XÁC NHẬN; CÁC MỤC KIỂM THỬ TAY CÒN LẠI HOÃN ĐẾN SAU TRIỂN KHAI**.
Người dùng ghi lỗi/kết quả và duyệt merge/push/
deploy riêng. Không dùng file ZIP UI b0eca4c cho Phase 11.
