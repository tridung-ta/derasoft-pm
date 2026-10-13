# PLAN chuẩn hóa nhãn ca chấm công ngày13/10/2026

## Hiện trạng
Form nhập nhãn ca tự do; service chỉ yêu cầu không trống và tối đa30ký tự. Schema shift_label VARCHAR(30) NOT NULL ở migration004. Hệ thống ghi ngày và tổng giờ, không ghi giờ bắt đầu/kết thúc ca nên không đủ dữ liệu tự suy ca sáng/chiều. Chưa sửa code hoặc DB.

## Phương án đề xuất chờ duyệt
1. Thay input bằng select bắt buộc: Ca sáng, Ca chiều, Ca tối, Ca linh hoạt. Thứ tự lựa chọn cố định; không random, không đánh số dựa trên số dòng.
2. Tạo mới hoặc đổi nhãn: server chỉ chấp nhận bốn nhãn trên, từ chối rỗng/giá trị tự gõ và báo “Vui lòng chọn nhãn ca trong danh sách.” Giữ trần30ký tự và ràng buộc mô tả hiện có; thông báo riêng cho từng lỗi.
3. Nhãn không quyết định giờ thường/OT, không thay trần tổng giờ/ngày. Không có lựa chọn “Ca OT” vì OT do số giờ được tính.
4. Một ca được phép có nhiều dòng cho nhiều task; không thêm unique theo ngày/ca. Không suy nhãn từ thời điểm nhập.
5. Giữ nguyên nhãn và dữ liệu cũ. Khi sửa bản ghi cũ, cho giữ nhãn gốc nếu không đổi; nếu chọn lại thì phải thuộc danh sách. Giao diện ghi “Nhãn cũ” và cho xem giá trị gốc escaped trong chi tiết; không phân loại giả hay UPDATE hàng loạt.
6. Hiển thị ngày và badge ca rõ ràng; tên lựa chọn tiếng Việt đồng nhất. Không sửa task/ownership/quyền/CSRF/audit/snapshot/rate hoặc thứ tự khóa ngày. Không migration.

## Phạm vi
pmtimesheetservice: validation nhãn, bảo toàn nhãn gốc trong edit theo nguồn record.
pmtimesheets controller/Smarty: cung cấp choices, select và trạng thái cũ.
CSS chung: badge nhẹ rõ chữ, dùng theme hiện tại.
Tests/docs: validation khôngDB với fixture/stub và Smarty/browser. DB/host UAT giữ CHƯACHẠY theo yêu cầu không tạoDB.

## VERIFY cần chạy sau duyệt
Tạo mới đủ4choices; rỗng/tự gõ/quá30ký tự bị từ chối không ghi. Sửa nhãn cũ giữ nguyên; đổi sangnhãn hợp lệ; thay sanggiá trị lạ bị từ chối. Nhãn cũ cóHTML phải escaped. Không gộp ca trùng, giờ/OT/snapshot không phụ thuộc ca. Keyboard/mobile/select validation; date/day-limit và quyền giữ nguyên. Ghi lệnh và output thật.

PLAN → chờ người dùng duyệt → BUILD local → VERIFY và cập nhật tài liệu → commit local/gói; không tự truy cập production.
