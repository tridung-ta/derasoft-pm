# Phase 11 — nghiệm thu local

BUILD đã kiểm tra tự động; chưa tick UAT thay người dùng. Nhánh local
feature/pm-phase11-requirements, runtime b57e7ba, schema 009 chỉ chạy local.

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

Kết quả: **CHƯA THỰC HIỆN**. Người dùng ghi lỗi/kết quả và duyệt merge/push/
deploy riêng. Không dùng file ZIP UI b0eca4c cho Phase 11.
