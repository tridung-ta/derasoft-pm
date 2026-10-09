# Handoff local — 09/10/2026

**CHƯA DUYỆT TRIỂN KHAI.** Không push/merge/deploy. Nội dung hiện hành thay handoff07/10; lịch sử giữ trong Git. Tenant1.3/1.4/0.2/2.7 PASS. 0.4 đã BUILD theo PLAN duyệt, 30/30 PASS. Lô 2 hoàn tất; tổng 71 PASS, 0 FAIL, 9 CHƯA CHẠY. P3 chờA/B/C.

## Manifest từ commit đã kiểm thử

Nguồn manifest/gói review hiện tại: fb67557476fb6635a63645fe1a8eae7aa9396675; feature/pm-phase11-launch-copy. P3 BUILD local đã chốt theo quyết định người dùng giao concurrency DB cho UAT. Regression60scripts exit0 đã chạy trên cùng nội dung trước commit; EXPLAIN108 và fingerprint95tables PASS. Tổng73PASS/0FAIL/7CHƯA CHẠY.

Lệnh thực chạy: `node docs/generate_manifest_20261008.cjs`, exit0, stderr rỗng. Output: `PASS documentation manifest: 319 classified paths; 95 immutable runtime SHA256 verified; source=fb67557476fb6635a63645fe1a8eae7aa9396675; no upload/SQL/package.`

DEPLOY_MANIFEST.json/DEPLOY_FILES.txt/DEPLOY_DIFF_ALL.txt lấy bytes blob Git, gồm font binary, không working-tree CRLF. Baseline3df33493: delta baseline, chưa đối chiếu delta host. Bản phát hành tương lai phải xuất đúng bytes commit và kiểm SHA256. Không upload workingtree đang sửa dở. SQL/config/cache/upload/tests/docs bị loại; file xóa chỉ review, không tự xóa host. Không tạoZIP. Tool cũ tests/pm_deploy_manifest.py dùng workingtree/metadata cũ, không dùng thay manifest này. Commit runtime mới cần tạo lại manifest và duyệt lại.

## Ba việc còn treo

| Việc | Trạng thái | Bằng chứng cần có |
|---|---|---|
|Store_id thật trước migration002|CHƯA XÁC NHẬN|Đối chiếu read-only DISTINCT store_id users/roles và permission mapping trên bản sao DB đích.002 có MIN(store_id), UPDATE và thay index; không chạy lại theo tên file, không tự gán mọi permission về tenant nhỏ nhất. Cần inventory, đối chiếu chính sách hiện hành và duyệt riêng.|
|Bảo trì xuyên suốt|CHƯA XÁC NHẬN|Chặn mọi entry ghi admin.php/pm_ajax.php/legacy/job trước backup, giữ đến hết upload/migration/smoke; cần phương án host và chứng cứ request ghi bị chặn. PM_HIDE_LEGACY không thay chế độ bảo trì.|
|Ước lượng khóa007 trên DBcopy|CHƯA CHẠY|Bản sao đại diện dc_users MyISAM cùng kích thước/index/server, kiểm duplicate và đo ALTER ADD UNIQUE cùng thời gian request bị chặn. Chưa có bản sao đại diện nên không báo số giây. Có thể rebuild/khóa toàn users; không đổi engine/tự sửa duplicate.|

Không chạy migration nào. Không dùng local thay số liệu production.009 và migration khác phải đối chiếu inventory đích; ảnh cũ không xác nhận toàn schema hiện tại.

## Smoke sau deploy — tất cả CHƯA CHẠY

Chỉ sau nghiệm thu/duyệt triển khai, dùng dữ liệu thử được cho phép, giữ dữ liệu cũ. Bằng chứng che PII, không lưu credential/hash.

- [ ]Kanban AJAX: task trong quyền đổi trạng thái, UI/tải lại đúng; thiếuCSRF/foreign task từ chối và không ghi; audit task_status actorID.
- [ ]Đổi mật khẩu: email/username dùng cùng mật khẩu mới, mật khẩu cũ bị từ chối; audit account/password_change actorID/action, không contact/credential/IP. Fault injection chỉ local, không upload router/hook test.
- [ ]Hồ sơ: audit profile_update chỉ changed_fields tên trường, không giá trị liên hệ/địa chỉ; không đổi không tạo event giả.
- [ ]PM_HIDE_LEGACY=true (mặc định/dự kiến): GET/POST legacy ajax count404, không lộ count/dữ liệu. Giữ legacy và lịch sử.
- [ ]Validator nhân sự: username30/fullname50/email50; thêm user bắt buộc password, báo lỗi rõ; tel tùy chọn, nhập phải đúng quy tắcVN10số; collision username/email2chiều cùng store bị từ chối. Không tuyên bố xác minh hộp thư tồn tại. ImportStage sai một dòng từ chối cảfile, lỗi theo dòng; Apply giữ1062.
- [ ]Tenant gate: foreignID403/404, không ghi/no leak; local1.3 đãPASS; kiểm lại trên đích sau khi được duyệt triển khai, không coi local thay production evidence.

UAT2.3/9.2/9.3 và P3 5.1/5.2 còn chờ; checklist này không phải productionPASS.


## Migration009 — điều kiện schema bắt buộc

| Cột | Tính năng sử dụng |
|---|---|
|dc_pm_projects.client_name|Thông tin khách hàng khi tạo/sửa/hiển thị dự án|
|dc_pm_project_members.project_role|Vai trò thành viên dự án|
|dc_pm_tasks.start_date|Ngày bắt đầu task, kiểm tra khoảng ngày|
|dc_pm_tasks.completed_at|Ghi ngày hoàn thành và thống kê task hoàn thành/trễ|

Phụ thuộc003 (và001/002 nền tảng). DDL chỉ4ADD COLUMN nullable có kiểm tra tồn tại, không CREATE table/UPDATE/MODIFY/DROP/engine change. Runtime hiện tại **không an toàn đầy đủ nếu thiếu các cột**: các đường ghi/query báo cáo dùng trực tiếp, có thể SQL unknown-column; không tự coi optional chỉ vì template có fallback. Kiểm kê schema thực trước khi upload/migration, không chạy lại ADD nếu đã có. Duyệt/backup/bảo trì riêng; rollback code và giữ cột/data. Không thực hiện migration trong lượt này.

## P3 chưa đóng gói —09/10/2026
Runtime P3 đang VERIFY, manifest05ca062 cũ chưa chứa P3. Migration010 đãbackup/chạy local, không production. Không dùng gói cũ như bản P3 hoàn chỉnh. Xem P3_RELEASE_CHECKLIST_20261009.md; chỉ tạo manifest/archive cuối sau VERIFY/commitlocal.

## Gói review local đã tạo09/10/2026
.local/releases/DeraSoft_PM_local_review_20261009_fb67557.zip;95runtimefiles,112entries; ZIPCRC/allentrySHA256PASS. ArchiveSHA256619b7d50d2ae4ea6de24a4419cf298df7a7717a8073d582eeb478ad884149bb9. Đây là gói cập nhật từbaseline3df33493da81f53e2fdc67d826998850812dbd2f cho hệ thốngDeraSoftcó sẵn, không phải fullfreshinstall và chưaproduction-approved. Tài liệu chờ/manifestcũ ở trên là lịch sử; gói hiện tại cóP3.
manual-migrations nằm riêng, khôngautorun;010 trướcP3code,002/007preflight vẫn cầnduyệt. Không cóconfig/backup/log/cache/upload; giữ config/uploads/legacy trênđích. ConcurrencyP3/3.6DB do người dùng UAT;2.6/9.5/10.3 vẫn thiếu bằngchứngđầyđủ,2.3/9.2/9.3UAT. Khôngpush/merge/deploy.
