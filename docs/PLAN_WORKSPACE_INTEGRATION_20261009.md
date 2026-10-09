# Đồng bộ frontend–backend và chuẩn bị gói local

Người dùng 09/10/2026 yêu cầu bắt đầu đồng bộ frontend với backend, chuẩn bị để tự triển khai production. Đây là ủy quyền BUILD phần tích hợp đọc dữ liệu dashboard đã trình bày; không ủy quyền truy cập production/push/merge hay thay schema.

- Di chuyển query tổng quan cá nhân vào DAO prepared, scope store_id; EXISTS membership tránh nhân task.
- PM/Admin: dự án task trễ (tối đa 8), ngân sách theo chi phí toàn đời của 20 dự án trang đầu service Chi phí; VND/đủ đơn giá/ngân sách dương mới tính DECIMAL, không float. Các trang khác xem qua link Chi phí.
- Cảnh báo allocation từ service tuần hiện tại, dedup user/ngày; tổng task vượt ước tính tách riêng, giữ nhãn lịch sử. Mỗi family permission-gated; HR/Employee/no-role không gọi provider quản lý.
- Lỗi từng family hiển thị không khả dụng; không fake zero/nuốt lỗi thành PASS, không log chi tiết.
- Kiểm tra wiring Kanban đăng nhập/CSRF hiện có, không bypass auth cho preview. Giữ native POST và audit/ownership.
- VERIFY không DB: service fixture/SQL-contract, PHP lint, Smarty/browser/mocked HTTP, package CRC/hash/allowlist. Test DB, session thật, EXPLAIN và UAT production ghi CHƯA CHẠY để người dùng test thủ công theo yêu cầu trước.
- Review security, cập nhật tài liệu; commit local thay đổi hoàn chỉnh theo AGENTS; package immutable source + manifest, SQL không thực thi.
- Không sửa giao diện Chi phí hoặc nghiệp vụ OT, không migration mới, không xóa legacy/dữ liệu.
