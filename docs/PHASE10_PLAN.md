# Phase 10 — Test, UAT & Release Preparation

## Status — 2026-10-04

PLAN — chờ duyệt BUILD. Phase 9 automated VERIFY đã hoàn tất; giới hạn thực tế ghi ở
PHASE9_VERIFY.md. Nhánh đề xuất `feature/pm-phase10-test-release`, kế thừa checkpoint
Phase 9. Không production access, không push/merge/deploy.

## Phạm vi đề xuất

- Lập test matrix cuối cho toàn bộ PM: Auth/RBAC, nhân sự/role/rate/phòng ban,
  projects/tasks/Kanban, timesheets/OT/khóa/audit, costs, allocations, reports/import.
- Bổ sung authenticated browser coverage Admin/PM/HR/Employee và các CRUD flows còn thiếu
  bằng môi trường DB local cô lập có dữ liệu giả; không tạo nhân sự test tồn tại trong
  dc_users hiện hành. Thiết kế fixture/cleanup và backup trước mọi thay đổi DB.
- Kiểm tra login/session regeneration/logout thành công, quyền thay đổi giữa phiên,
  CSRF/IDOR/tenant, keyboard forms, screen-reader checks, contrast, zoom browser thật
  200%, desktop/mobile và browser engine thứ hai khi công cụ local hỗ trợ.
- Kiểm tra cửa sổ crash import sau successful INSERT bằng bản DB cô lập giữ đúng MyISAM
  và UNIQUE email; kill/restart worker rồi resume, kiểm tra provenance/row result/lock.
  Không đổi ENGINE, không gọi mirror InnoDB là native MyISAM verification.
- Chạy regression toàn PM, PHP/JS lint, code/security review; sửa defect đúng phạm vi,
  lưu bằng chứng và coverage chưa chạy. Không mở HR tài chính hoặc workflow duyệt.
- Chuẩn bị hướng dẫn cài đặt local/release checklist, backup, versioned migrations đã
  duyệt, kiểm tra secrets và rollback ứng dụng không DROP/xóa dữ liệu. Không tự thêm index.
- Chuẩn bị UAT script ngắn cho người dùng cuối, dữ liệu demo không nhạy cảm và portfolio
  theo kết quả thật. Người dùng tự nghiệm thu toàn local sau khi kiểm thử tự động xong.

## Acceptance / delivery

1. Test matrix phân biệt automated service/HTTP, authenticated browser, synthetic fixture
   và manual UAT; mọi PASS có lệnh/bằng chứng, thiếu công cụ ghi rõ chưa chạy.
2. Regression/security checks đạt, defect được sửa và chạy lại tests liên quan; mọi
   fixture business/personnel chỉ ở DB cô lập và có kiểm tra cleanup.
3. Native MyISAM crash/resume được kiểm tra hoặc giữ nguyên giới hạn release rõ ràng;
   không sửa core ENGINE/constraint ngoài phê duyệt đã có.
4. Cập nhật PROGRESS/README/PORTFOLIO và tạo một commit local cho phase hoàn tất theo
   AGENTS.md. UAT PASS chỉ khi người dùng xác nhận; production cần ủy quyền riêng.

Phase 10 không bao gồm thao tác push/merge/deploy hoặc kiểm tra trên production.
