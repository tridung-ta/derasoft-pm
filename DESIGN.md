# Giao diện vận hành

Inter tự host, nền sage sáng, mặt làm việc trắng và teal rừng đậm. Topbar cố định: Điều hướng trái, DeraSoft giữa, thông báo/tài khoản phải; không sidebar điều hướng cố định. Người dùng đã yêu cầu thiết kế lại bố cục toàn PM trên local ngày 15/10/2026 sau khi bản đổi viền/màu chưa đủ khác biệt.

Desktop tổ chức theo workbench: vùng thao tác/lọc hẹp bên trái, dữ liệu rộng bên phải; mobile xếp dọc đúng thứ tự DOM. Tổng quan có luồng việc cần xử lý và rail lối truy cập chức năng. Danh sách dự án dùng hàng ngang cho tên, tiến độ và thông tin đối chiếu; chi tiết dự án tách team khỏi Kanban. Chấm công đặt lịch tuần trên cùng, form cạnh lịch sử. Phân bổ đặt bộ lọc cạnh lịch. Chi phí/báo cáo/nhân sự/nhật ký có contextual filter rail.

Khung dữ liệu phân cấp bằng bố cục và typography, không dùng các card giống nhau dàn đều. Đăng nhập có panel nhận diện teal và form sáng; số liệu luôn lấy từ backend hiện hữu. Không gradient, kính, số liệu giả hoặc tính năng giả. Focus rõ, thao tác tối thiểu 44px, native details/form/no-JS và CSP được giữ.

Motion theo tương tác (menu, tab, dialog, toast), không delay thao tác, không scroll reveal hay vòng lặp. Reduced motion bỏ chuyển động không gian. Bản local chờ người dùng duyệt; không tự triển khai production.
