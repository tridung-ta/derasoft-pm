# PLAN sửa 0.4 — thông báo trường bắt buộc, 09/10/2026

**ĐÃ ĐƯỢC NGƯỜI DÙNG DUYỆT VÀ BUILD LOCAL — VERIFY PASS.** Kết quả thật: [BATCH2_REQUIRED_FIELDS_VERIFY_20261009.md](BATCH2_REQUIRED_FIELDS_VERIFY_20261009.md). 21 case:7 PASS/14 FAIL, 21 bảng thật không đổi. Lỗi là thông báo gộp; backend vẫn chặn dữ liệu trống.

## Phạm vi BUILD đề xuất

1. Phase3 `modules/admin/pmusers.module.php`: dùng validator/`PmUserInputException` hiện có để nhận diện riêng trường bắt buộc username,fullname,email ở form tạo/sửa phù hợp. Tránh các check gộp trước DAO nuốt thông báo theo trường; chuyển lỗi có key vào `newPersonErrors` cho tạo và error hiện có cho sửa. Giữ thông báo password/phone đã có, email format/length/control, username30/fullname50/email50/tel policy hiện tại, duplicate/collision hai chiều, ID/CSRF/quyền và khóa hiện có. Không thêm OTP/DNS/SMTP, không đánh giá tên vô nghĩa.
2. Phase4 `classes/services/pmprojectservice.class.php`: trước validator gộp trong saveProject, tách trường tên/mã/ngân sách nhập rỗng với thông báo gọi đúng tên trường (ví dụ “Vui lòng nhập tên dự án”, “Vui lòng nhập mã dự án”, “Vui lòng nhập ngân sách dự án”). Trong saveTask, tách tên/giờ ước tính rỗng tương tự. Dùng InvalidArgumentException như hiện tại. Không đổi logic số/DECIMAL/độ dài hoặc cho phép thiếu trường bắt buộc; không biến blank estimate thành0.
3. Giữ Admin manager_id trống bị từ chối như test đã PASS; PM manager tự gán actor giữ nguyên. Giữ estimated_hours=0 là “Chưa có ước tính”; không thay schema/migration/dữ liệu cũ. Không đổi default của input key không truyền ngoài những check trống nêu trên.
4. Không đổi bố cục, CSS hoặc template trong phạm vi này; UI error hiện có hiển thị message escaped. Nếu phát hiện cần chỉnh giao diện, lập phạm vi riêng và áp dụng impeccable theo AGENTS.

## Contract không được đổi

- InvalidArgumentException/PmUserInputException tại form PM giữ HTTP200 và lỗi hiển thị; không chuyển400.
- Scope403, missing404 và message chung của Tenant1.3 giữ nguyên; không nuốt subclass.
- PmProjectDateRangeException và Lưu/Ẩn redirect303 giữ nguyên; task cùng ngày được phép.
- Không thêm/xóa dữ liệu thật, không đổi audit/tracking assertions, không sửa OT/P3, không push/merge/deploy.

## VERIFY sau duyệt BUILD

- Chạy lại `tests/pm_required_fields_http_smoke.php`: cả20 case field trống phải200, message rõ đúng trường, không ghi/audit/tracking; case0.3 hợp lệ vẫn tạo đúng fixture. Toàn21case phải PASS; bắt buộc hash21 bảng thật không đổi và cleanup.
- Không nới regex bằng thông báo gộp, không bỏ assertion. Xem thêm lỗi nhiều trường trống, trim whitespace, giá trị0 của budget/estimate hợp lệ và nonempty-invalid giữ behavior đã duyệt.
- Các suite liên quan: pm_user_input_smoke, pm_user_identity_smoke, pm_users_crud_smoke, pm_projects_smoke, pm_task_status_http_smoke; matrix đầy đủ80cặp/11controls/35behavior.
- Chỉ thêm probe vào regression sau targeted PASS; chạy toàn regression trên commit local sau review. Chỉ nâng0.4PASS sau toàn case/proof đạt; giữ các mục khác. Cập nhật TEST_REPORT_FULL/PROGRESS và tiếp tục lô2 đúng thứ tự 2.2,3.10,5.5,8.3; lô3 9.5/10.3. UAT2.3/9.2/9.3 và P3 5.1/5.2 giữ chờ.

Đây là PLAN thông báo validation; không mở rộng nghiệp vụ ngoài quyết định đã duyệt. Người dùng đã duyệt PLAN; BUILD chỉ đúng phạm vi trên. Targeted30/30 và regression59scripts PASS, xem BATCH2_REQUIRED_FIELDS_VERIFY_20261009.md. Không push/merge/deploy.
