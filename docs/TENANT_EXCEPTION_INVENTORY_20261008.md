# Bảng 1 — Throw/catch PM

Ngày 08/10/2026. **Chỉ rà soát code; chờ duyệt bốn bảng trước BUILD tiếp.** HEAD `26ca279095e26c30960cf82294842fefe1f548b0`; working tree đã có sửa local theo lượt duyệt trước, không commit/push/merge/deploy. Các vị trí dòng bên dưới theo working tree hiện tại, không giả định đây là code đã phát hành.

Lệnh đọc: `rg -n -g pm*.php "throw new (DomainException|PmScopeDeniedException)|catch.*DomainException" classes modules`; sau đó đọc từng file để phân loại. Đây là static review, không phải PASS runtime.

| Nguồn | Thông báo/nhánh throw | Phân loại dự kiến | Lớp hiện tại |
|---|---|---|---|
| [modules/admin/pm.module.php:51](../modules/admin/pm.module.php#L51) | Không tìm thấy tài khoản trong đơn vị. | Quyền/phạm vi — PmScopeDeniedException | DomainException |
| [modules/admin/pmusers.module.php:28](../modules/admin/pmusers.module.php#L28) | Mã thuộc phòng ban đã ẩn. Hãy khôi phục trong mục Phòng ban đã ẩn. | Nghiệp vụ — giữ DomainException | DomainException |
| [modules/admin/pmusers.module.php:29](../modules/admin/pmusers.module.php#L29) | Mã phòng ban đã tồn tại. | Nghiệp vụ — giữ DomainException | DomainException |
| [modules/admin/pmusers.module.php:43](../modules/admin/pmusers.module.php#L43) | Không thể khóa hoặc xóa tài khoản đang đăng nhập. | Nghiệp vụ — giữ DomainException | DomainException |
| [modules/admin/pmusers.module.php:67](../modules/admin/pmusers.module.php#L67) | Email đã được sử dụng. | Nghiệp vụ — giữ DomainException | DomainException |
| [modules/admin/pmusers.module.php:70](../modules/admin/pmusers.module.php#L70) | Phòng ban không thuộc tenant hiện tại. | Hỗn hợp — tách lookup/quyền khỏi trạng thái nghiệp vụ | DomainException |
| [classes/services/pmuseridentityservice.class.php:27](../classes/services/pmuseridentityservice.class.php#L27) | Tên đăng nhập hoặc email trùng định danh của tài khoản khác trong đơn vị. | Nghiệp vụ — giữ DomainException | DomainException |
| [classes/services/pmtimesheetservice.class.php:9](../classes/services/pmtimesheetservice.class.php#L9) | Bạn không có quyền chấm công. | Quyền/phạm vi — PmScopeDeniedException | DomainException |
| [classes/services/pmtimesheetservice.class.php:16](../classes/services/pmtimesheetservice.class.php#L16) | Chỉ Admin được thay đổi quy tắc OT. | Quyền/phạm vi — PmScopeDeniedException | DomainException |
| [classes/services/pmtimesheetservice.class.php:40](../classes/services/pmtimesheetservice.class.php#L40) | Chấm công đã khóa sau 3 ngày. Chỉ Admin được sửa hoặc xóa. | Nghiệp vụ — giữ DomainException | DomainException |
| [classes/services/pmtimesheetservice.class.php:45](../classes/services/pmtimesheetservice.class.php#L45) | Không có quyền truy cập nhân sự này. | Quyền/phạm vi — PmScopeDeniedException | DomainException |
| [classes/services/pmtimesheetservice.class.php:74](../classes/services/pmtimesheetservice.class.php#L74) | Không có quyền truy cập nhân sự này. | Quyền/phạm vi — PmScopeDeniedException | DomainException |
| [classes/services/pmtimesheetservice.class.php:99](../classes/services/pmtimesheetservice.class.php#L99) | Bản ghi vừa thay đổi. Vui lòng tải lại trang. | Nghiệp vụ — giữ DomainException | DomainException |
| [classes/services/pmtimesheetservice.class.php:102](../classes/services/pmtimesheetservice.class.php#L102) | Chỉ có thể ghi công cho task được giao cho bạn trong dự án đang hoạt động. | Quyền/phạm vi — PmScopeDeniedException | PmScopeDeniedException |
| [classes/services/pmtimesheetservice.class.php:104](../classes/services/pmtimesheetservice.class.php#L104) | Chấm công hiện chỉ hỗ trợ đơn giá VND. | Nghiệp vụ — giữ DomainException | DomainException |
| [classes/services/pmtimesheetservice.class.php:115](../classes/services/pmtimesheetservice.class.php#L115) | Bản ghi vừa thay đổi. Vui lòng tải lại trang. | Nghiệp vụ — giữ DomainException | DomainException |
| [classes/services/pmtimesheetservice.class.php:123](../classes/services/pmtimesheetservice.class.php#L123) | Tổng giờ trong một ngày không được vượt quá 24. | Nghiệp vụ — giữ DomainException | DomainException |
| [classes/services/pmreportservice.class.php:9](../classes/services/pmreportservice.class.php#L9) | Bạn không có quyền báo cáo. | Quyền/phạm vi — PmScopeDeniedException | DomainException |
| [classes/services/pmreportservice.class.php:26](../classes/services/pmreportservice.class.php#L26) | Không có quyền xuất danh sách. | Quyền/phạm vi — PmScopeDeniedException | DomainException |
| [classes/services/pmreportservice.class.php:39](../classes/services/pmreportservice.class.php#L39) | Chỉ xem báo cáo cá nhân. | Quyền/phạm vi — PmScopeDeniedException | DomainException |
| [classes/services/pmreportservice.class.php:43](../classes/services/pmreportservice.class.php#L43) | Dự án ngoài phạm vi. | Quyền/phạm vi — PmScopeDeniedException | DomainException |
| [classes/services/pmrateservice.class.php:52](../classes/services/pmrateservice.class.php#L52) | Hourly rate effective period overlaps an existing rate. | Nghiệp vụ — giữ DomainException | DomainException |
| [classes/services/pmprojectservice.class.php:17](../classes/services/pmprojectservice.class.php#L17) | Bạn không có quyền thực hiện thao tác này. | Quyền/phạm vi — PmScopeDeniedException | PmScopeDeniedException |
| [classes/services/pmprojectservice.class.php:23](../classes/services/pmprojectservice.class.php#L23) | Bạn không thuộc dự án này. | Quyền/phạm vi — PmScopeDeniedException | PmScopeDeniedException |
| [classes/services/pmprojectservice.class.php:30](../classes/services/pmprojectservice.class.php#L30) | Bạn chỉ có thể quản lý dự án mình phụ trách. | Quyền/phạm vi — PmScopeDeniedException | PmScopeDeniedException |
| [classes/services/pmprojectservice.class.php:77](../classes/services/pmprojectservice.class.php#L77) | Chỉ Admin hoặc PM được tạo dự án. | Quyền/phạm vi — PmScopeDeniedException | PmScopeDeniedException |
| [classes/services/pmprojectservice.class.php:90](../classes/services/pmprojectservice.class.php#L90) | Mã dự án đã tồn tại. | Nghiệp vụ — giữ DomainException | DomainException |
| [classes/services/pmprojectservice.class.php:112](../classes/services/pmprojectservice.class.php#L112) | Không thể gỡ người phụ trách dự án. | Nghiệp vụ — giữ DomainException | DomainException |
| [classes/services/pmprojectservice.class.php:112](../classes/services/pmprojectservice.class.php#L112) | Cần giao lại hoặc bỏ người được giao của các task trước khi gỡ thành viên. | Nghiệp vụ — giữ DomainException | DomainException |
| [classes/services/pmimportservice.class.php:11](../classes/services/pmimportservice.class.php#L11) | Chỉ Admin được kiểm tra import. | Quyền/phạm vi — PmScopeDeniedException | DomainException |
| [classes/services/pmimportservice.class.php:83](../classes/services/pmimportservice.class.php#L83) | Không tìm thấy batch trong tenant. | Quyền/phạm vi — PmScopeDeniedException | DomainException |
| [classes/services/pmimportservice.class.php:95](../classes/services/pmimportservice.class.php#L95) | Không tìm thấy batch trong tenant. | Quyền/phạm vi — PmScopeDeniedException | DomainException |
| [classes/services/pmimportservice.class.php:141](../classes/services/pmimportservice.class.php#L141) | Chỉ cấp mật khẩu cho tài khoản import của dòng này đang chưa kích hoạt. | Quyền/phạm vi — PmScopeDeniedException | DomainException |
| [classes/services/pmimportservice.class.php:143](../classes/services/pmimportservice.class.php#L143) | Trạng thái tài khoản đã thay đổi. | Nghiệp vụ — giữ DomainException | DomainException |
| [classes/services/pmcostservice.class.php:16](../classes/services/pmcostservice.class.php#L16) | Forbidden | Quyền/phạm vi — PmScopeDeniedException | DomainException |
| [classes/services/pmcostservice.class.php:37](../classes/services/pmcostservice.class.php#L37) | Bạn không có quyền xem chi phí. | Quyền/phạm vi — PmScopeDeniedException | DomainException |
| [classes/services/pmcostservice.class.php:50](../classes/services/pmcostservice.class.php#L50) | Dự án không tồn tại hoặc ngoài phạm vi của bạn. | Quyền/phạm vi — PmScopeDeniedException | DomainException |
| [classes/services/pmallocationservice.class.php:16](../classes/services/pmallocationservice.class.php#L16) | Bạn không có quyền phân bổ nguồn lực. | Quyền/phạm vi — PmScopeDeniedException | DomainException |
| [classes/services/pmallocationservice.class.php:18](../classes/services/pmallocationservice.class.php#L18) | Chỉ Admin quản lý ngưỡng phân bổ. | Quyền/phạm vi — PmScopeDeniedException | DomainException |
| [classes/services/pmallocationservice.class.php:33](../classes/services/pmallocationservice.class.php#L33) | Dự án ngoài phạm vi hoặc đã ẩn. | Hỗn hợp — tách lookup/quyền khỏi trạng thái nghiệp vụ | DomainException |
| [classes/services/pmallocationservice.class.php:38](../classes/services/pmallocationservice.class.php#L38) | Phân bổ ngoài phạm vi. | Quyền/phạm vi — PmScopeDeniedException | DomainException |
| [classes/services/pmallocationservice.class.php:57](../classes/services/pmallocationservice.class.php#L57) | Phân bổ đã thay đổi, vui lòng tải lại. | Nghiệp vụ — giữ DomainException | DomainException |
| [classes/services/pmallocationservice.class.php:64](../classes/services/pmallocationservice.class.php#L64) | Task, nhân viên hoặc membership không active/không phù hợp. | Hỗn hợp — tách lookup/quyền khỏi trạng thái nghiệp vụ | DomainException |
| [classes/services/pmallocationservice.class.php:77](../classes/services/pmallocationservice.class.php#L77) | Phân bổ đã thay đổi, vui lòng tải lại. | Nghiệp vụ — giữ DomainException | DomainException |
| [classes/services/pmallocationservice.class.php:92](../classes/services/pmallocationservice.class.php#L92) | Nhân viên không active trong tenant. | Hỗn hợp — tách lookup/quyền khỏi trạng thái nghiệp vụ | DomainException |
| [classes/services/pmallocationservice.class.php:94](../classes/services/pmallocationservice.class.php#L94) | Không thể đổi chủ sở hữu capacity. | Hỗn hợp — tách lookup/quyền khỏi trạng thái nghiệp vụ | DomainException |
| [classes/services/pmallocationservice.class.php:95](../classes/services/pmallocationservice.class.php#L95) | Khoảng capacity chồng lấn. | Nghiệp vụ — giữ DomainException | DomainException |
| [classes/services/pmallocationservice.class.php:105](../classes/services/pmallocationservice.class.php#L105) | Capacity đã thay đổi. | Nghiệp vụ — giữ DomainException | DomainException |
| [classes/services/pmallocationservice.class.php:139](../classes/services/pmallocationservice.class.php#L139) | Nhân viên chỉ đọc lịch của mình. | Quyền/phạm vi — PmScopeDeniedException | DomainException |
| [classes/services/pmallocationservice.class.php:140](../classes/services/pmallocationservice.class.php#L140) | Dự án ngoài phạm vi. | Quyền/phạm vi — PmScopeDeniedException | DomainException |
| [classes/dao/pmdepartments.class.php:50](../classes/dao/pmdepartments.class.php#L50) | Không thể ẩn phòng ban đang có nhân sự. | Nghiệp vụ — giữ DomainException | DomainException |
| [classes/dao/pmauditlogs.class.php:16](../classes/dao/pmauditlogs.class.php#L16) | Bạn không có quyền xem nhật ký kiểm toán. | Quyền/phạm vi — PmScopeDeniedException | DomainException |

| Nguồn catch | Code hiện tại | Thứ tự subtype/parent |
|---|---|---|
| [modules/admin/pmimports.module.php:27](../modules/admin/pmimports.module.php#L27) | `catch(DomainException $e){http_response_code(403);$error=$e->getMessage();}` | Domain bắt cả subtype; chưa có nhánh subtype riêng |
| [modules/admin/pmcosts.module.php:10](../modules/admin/pmcosts.module.php#L10) | `catch(DomainException $e){http_response_code(403);$error=$e->getMessage();}` | Domain bắt cả subtype; chưa có nhánh subtype riêng |
| [modules/admin/pmallocations.module.php:24](../modules/admin/pmallocations.module.php#L24) | `catch(DomainException\|OutOfBoundsException $e){http_response_code(403);$error=$e->getMessage();}` | Domain bắt cả subtype; chưa có nhánh subtype riêng |
| [modules/admin/pmallocations.module.php:28](../modules/admin/pmallocations.module.php#L28) | `catch(DomainException $e){http_response_code(403);$error=$e->getMessage();$allocationData=null;}` | Domain bắt cả subtype; chưa có nhánh subtype riêng |
| [modules/admin/pm.module.php:61](../modules/admin/pm.module.php#L61) | `} catch (InvalidArgumentException\|DomainException\|OverflowException $e) {` | Domain bắt cả subtype; chưa có nhánh subtype riêng |
| [modules/admin/pmusers.module.php:80](../modules/admin/pmusers.module.php#L80) | `catch(InvalidArgumentException\|DomainException\|OverflowException $e){$error=$e->getMessage();}` | Domain bắt cả subtype; chưa có nhánh subtype riêng |
| [modules/admin/pmtimesheets.module.php:20](../modules/admin/pmtimesheets.module.php#L20) | `}catch(PmScopeDeniedException $e){http_response_code(403);$error=$e->getMessage();}` | Subtype trước catch Domain phía sau |
| [modules/admin/pmtimesheets.module.php:22](../modules/admin/pmtimesheets.module.php#L22) | `catch(InvalidArgumentException\|DomainException $e){$error=$e->getMessage();}` | Domain bắt cả subtype; chưa có nhánh subtype riêng |
| [modules/admin/pmreports.module.php:16](../modules/admin/pmreports.module.php#L16) | `catch(DomainException $e){http_response_code(403);$error=$e->getMessage();}` | Domain bắt cả subtype; chưa có nhánh subtype riêng |
| [modules/admin/pmprojects.module.php:37](../modules/admin/pmprojects.module.php#L37) | `}catch(PmScopeDeniedException $e){http_response_code(403);$error=$e->getMessage();}` | Subtype trước catch Domain phía sau |
| [modules/admin/pmprojects.module.php:39](../modules/admin/pmprojects.module.php#L39) | `catch(InvalidArgumentException\|DomainException $e){$error=$e->getMessage();}` | Domain bắt cả subtype; chưa có nhánh subtype riêng |
| [modules/admin/pmprojects.module.php:44](../modules/admin/pmprojects.module.php#L44) | `if($projectId){try{$project=$service->getProject($projectId);$members=$service->members($projectId);if($pmAccess->hasPermission('pm.tasks.view'))$tasks=$service->tasks($projectId);$canManage=$service->canManage($project);}catch(OutOfBoundsException $e){http_response_code(404);$error=$e->getMessage();$projectId=0;}catch(DomainException $e){http_response_code(403);$error=$e->getMessage();$projectId=0;}}` | Domain bắt cả subtype; chưa có nhánh subtype riêng |
| [modules/ajax/pmtaskstatus.module.php:17](../modules/ajax/pmtaskstatus.module.php#L17) | `catch(DomainException $e){http_response_code(403);echo json_encode(['error'=>'Bạn không có quyền đổi trạng thái công việc này.']);}` | Domain bắt cả subtype; chưa có nhánh subtype riêng |
| [modules/ajax/pmcosts.module.php:7](../modules/ajax/pmcosts.module.php#L7) | `catch(DomainException $e){http_response_code(403);echo json_encode(['error'=>$e->getMessage()]);}` | Domain bắt cả subtype; chưa có nhánh subtype riêng |
| [modules/ajax/pmallocations.module.php:16](../modules/ajax/pmallocations.module.php#L16) | `catch(DomainException $e){http_response_code(403);echo json_encode(['error'=>$e->getMessage()]);}` | Domain bắt cả subtype; chưa có nhánh subtype riêng |

## Xác nhận không nuốt ngoại lệ

- `pmprojects` POST và `pmtimesheets` POST hiện bắt PmScopeDeniedException trước DomainException, trước Throwable; lớp con không bị parent nuốt tại hai nhánh này. `PmProjectDateRangeException` vẫn bắt trước InvalidArgumentException.
- Không thể xác nhận cho toàn bộ PM: các catch Domain phía bảng trên chưa có subtype riêng. Catch Domain hiện vẫn bắt được lớp con nhưng không phân biệt nghiệp vụ/quyền. Cần thêm subtype trước parent ở BUILD theo nhóm, không đổi business Domain thành403.
- `pmaudit.module.php` chỉ bắt InvalidArgument rồi Throwable: Domain từ DAO có thể bị đổi thành lỗi chung HTTP200; phải nêu ngoài coverage80, không đánh dấu đạt. `pm.module.php` profile cũng bắt Domain chung HTTP200.
- `pm_ajax.php` ngoài cùng catch Throwable→500; subtype chỉ không rơi vào500 khi module con xử lý đúng. Các service begin/commit catch Throwable rollback rồi rethrow; chúng không nuốt ngoại lệ.
- Nhánh chấm công khóa ngày/24h/currency vẫn nghiệp vụ200, không sửa OT. `assignRoles` validation vai trò inactive/primary không selected vẫn InvalidArgument 200; role foreign/missing là OutOfBounds404.
- Các nhánh hỗn hợp phải split điều kiện bằng query scoped và kiểu ngoại lệ, không so chuỗi thông báo. Đặc biệt active/ẩn không đồng nghĩa không có quyền.
