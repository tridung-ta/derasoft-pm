# P3 — điều kiện gói triển khai local

Chuẩn bị gói review local; chưa duyệt phát hành. Người dùng yêu cầu hoàn tất local trước; không push/merge/deploy.

1. Hoàn tất VERIFY P3, regression và review; ghi kết quả còn CHƯA CHẠY cụ thể, không gọi production-ready. Concurrency hai connection thật do người dùng nhận UAT thủ công; không tạo database test riêng, giữ CHƯA CHẠY.
2. Commit local một thay đổi P3 hoàn chỉnh, chỉ code/tests/tài liệu liên quan, loại config/backup/log/cache/upload. Manifest hiện tại05ca062 chưa bao gồm runtime P3; tạo lại theo commit P3 cuối rồi xác minh SHA256 từng blob.
3. Gói runtime riêng; migrationSQL/manualinstructions riêng, tuyệt đối không chép config local lên production. Backup.local20MB không nằm gói. Không tự chạySQL hay upload.
4. Thứ tự: đối chiếu schema đích và backup → chế độ bảo trì xuyên suốt mọi writer → migration002/007/009 theo bảng triển khai đã duyệt nếu thiếu →010 sau004 trước code P3 → uploadruntime/hash →smoke →người dùng quyết định mở bảo trì. Không coi010 cấp quyền chạy002/007/009.
5.010 chỉ ADD dc_pm_timesheet_days.max_total_hours DECIMAL(4,2) NOT NULL DEFAULT24.00; giữ data/engine; kiểm tra tồn tại. Có thể khóaDDL, số0.039s local nhỏ không dự đoán production. KhôngDROP rollback; quay lạicodecũ và giữ cột/dữ liệu, xem xét snapshot trước khi trở lạicodeP3.
6. SmokeP3: settings thường8/tổng12, lưu8 rồi2 =>8regular/2OT; total12allowed/12.01rejected; admin khôngbypass; chuyểnngàyquátrầnrollback; ngàycũgiữsnapshot. Dùng dữ liệu test được duyệt trên đích, không ngầm tạo/ẩn dữ liệu thật.
7. Các smoke khác vẫn giữ: KanbanAJAX/CSRF/audit; profile/passwordID-only; legacycount404; validatornhânsự; tenant/no-role; costchart/import/export. Chưa đánhcheck production test.
8. Ba preflightproduction vẫn treo: actualstore_id trước002, maintenance mọiwriters, benchmark007 trêncopy đại diện. Local migration010 không giải quyết ba việc này. UAT2.3/9.2/9.3 và kiểm chứng2.6/3.6/9.5/10.3 còn ghi đúng báo cáo.
