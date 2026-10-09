# Gate duyệt trước BUILD nhóm

Ngày 08/10/2026. **Chỉ rà soát code; chờ duyệt bốn bảng trước BUILD tiếp.** HEAD `26ca279095e26c30960cf82294842fefe1f548b0`; working tree đã có sửa local theo lượt duyệt trước, không commit/push/merge/deploy. Các vị trí dòng bên dưới theo working tree hiện tại, không giả định đây là code đã phát hành.

## Bốn tài liệu gửi duyệt

1. [Throw/catch và phân loại](TENANT_EXCEPTION_INVENTORY_20261008.md)
2. [Endpoint/op/ID so80 cặp](TENANT_ENDPOINT_COVERAGE_20261008.md)
3. [SQL ghi/store_id](TENANT_SQL_WRITE_AUDIT_20261008.md)
4. [Status/message, chấm công, preflight và009](TENANT_STATUS_CONTRACT_20261008.md)

## Trạng thái cần giữ trung thực

Code local đã sửa theo lượt duyệt trước trước khi nhận gate mới. Không hoàn nguyên/xóa/thay thế; giữ để review, chưa commit. Không coi1.3PASS dưới điều kiện mới: message còn loại tài nguyên, local ownership/malformed/endpoint gaps chưa verify; regressionFAIL. Không chạy lô1.4trở đi. P3 chờA/B/C. Không push/merge/deploy/migration.

Sau duyệt bốn bảng: BUILD từng nhóm và commit riêng (a)pmprojects, (b)pmusers, (c)pmtimesheets+pmallocations+pmreports. Mỗi nhóm verify submatrix/suite liên quan trước nhóm kế. Không stagehunk nhóm khác; dùnggitadd-p khi cần. Giữ mọiassertion dữ liệu/audit/tracking; chỉ đổi exception/status đúng contract duyệt.

Phát hiện ngoài ba nhóm: pmauditDAOcatchThrowable, profileactorstale, cost filter chỉ dùng qua reports (không mở rộng API), importmixedeligibility và các quyền/phạm vi chưa convert. Cần chốt phạm vi khi duyệt bảng; không mở rộng runtime tự động.


## Cập nhật sau duyệt và BUILD — 08/10/2026

Bốn bảng đã được người dùng duyệt. BUILD/VERIFY theo từng nhóm, commit A b70b441, B a3b17e3, C c850158. Matrix cuối trên commit C: 80/80 cặp, 11 control, 35 ca hành vi PASS; regression 56 script PASS; 21 bảng thật không đổi. Các phát hiện/lỗi baseline ở trên được giữ làm lịch sử, không phải trạng thái hiện tại. Xem TEST_REPORT_FULL.md và TENANT_GROUP_A_VERIFY_20261008.md, TENANT_GROUP_B_VERIFY_20261008.md, TENANT_GROUP_C_VERIFY_20261008.md. P3 vẫn chờ A/B/C. Không push/merge/deploy/migration.
