# Bộ nhớ lỗi và cách phòng ngừa — DeraSoft PM

Cập nhật16/10/2026 theo yêu cầu người dùng sau khi chốt hoàn thành dự án theo phạm vi đã thống nhất. Mục tiêu là giảm lỗi lặp, không hứa không bao giờ có bug. Tài liệu này lưu tri thức kiểm chứng, không chứa dữ liệu phiên hay bí mật.

Áp dụng ECC adapter và tham khảo đầy đủ continuous-learning-v2 v2.1 từ nguồn ECC2.2.3 đã pin. Thay cơ chế Claude hooks/observer bằng ghi nhận thủ công trong repo để phù hợp Codex Windows; không cài plugin, hook, dịch vụ hay telemetry. Không promote global từ một dự án. `learning/instincts.json` là bản quy tắc project-scoped sinh từ tài liệu này, không phải ECC runtime đã import.

## Cách dùng

1. Tìm triệu chứng bằng `rg -n "từ khóa" docs/BUG_LESSONS.md`.
2. Đọc bằng chứng và kiểm tra code/version hiện tại; ưu tiên quyết định mới nhất trong DESIGN.md/PLAN_FINAL.md.
3. Tái hiện trước khi kết luận. Sửa tầng gây lỗi; chạy kiểm tra đích rồi regression cần thiết.
4. Ghi kết quả và giới hạn; không biến giả thuyết thành sự thật. Bài học đang mở không được ghi là đã sửa.

Chỉ mục: import/mật khẩu L001–L004; backend/identity/HTTP L005–L009; giờ/chi phí L010–L013; CSS/UI L014–L019; harness/dữ liệu L020–L024; đóng gói/quy trình L025–L028; cron/bootstrap L029–L031; encoding L032.

<a id="pm-l001"></a>

## PM-L001 — Preview làm mất tệp upload
- Loại: lỗi trải nghiệm đã sửa.
- Khi gặp: Preview xong phải chọn lại workbook trước Stage.
- Nguyên nhân: POST tải lại toàn trang; browser không cho khôi phục giá trị file input bằng mã.
- Hành động: giữ input trong DOM, gửi Preview multipart bất đồng bộ, Stage kiểm tra lại tệp ở backend; không lưu workbook/mật khẩu vào localStorage.
- Kiểm tra: tests/pm_import_tabs_browser.js, gồm giữ tệp, lỗi phiên/mạng, multipart và no-JS.
- Bằng chứng: docs/PM_IMPORT_UI_20261015.md. No-JS tải lại vẫn cần chọn tệp; không hứa giữ file qua reload.

<a id="pm-l002"></a>

## PM-L002 — Bấm cấp mật khẩu không thấy lỗi
- Loại: lỗi feedback đã sửa; nguyên nhân cụ thể trên tài khoản host thứ hai chưa được chứng minh.
- Khi gặp: bấm nút không có xác nhận hoặc form bị khóa sau validation.
- Nguyên nhân: native validity có thể chặn submit trước handler; UI cần phản hồi theo trường và giữ lỗi backend. Không có quy tắc cấm hai tài khoản dùng cùng mật khẩu trong code đã rà.
- Hành động: kiểm tra invalid/input/blur và response thật, hiển thị lỗi ở dòng tương ứng, chỉ báo thành công sau backend xác nhận; không suy đoán do trùng mật khẩu.
- Kiểm tra: tests/pm_import_password_feedback_browser.js và tests/pm_import_password_feedback_smoke.php.
- Bằng chứng: docs/PM_IMPORT_UI_20261015.md, docs/PM_UI_BE_ACTION_RETEST_20261016.md.

<a id="pm-l003"></a>

## PM-L003 — Giới hạn mật khẩu lệch giữa frontend và backend
- Loại: lỗi hợp đồng validation đã sửa; tối thiểu8 byte là yêu cầu mới đã chốt.
- Khi gặp: mật khẩu Unicode bị từ chối dù đủ byte, hoặc UI vẫn ghi12 sau khi BE đổi8.
- Nguyên nhân: PHP strlen đo byte UTF-8; minlength native đo độ dài theo quy tắc chuỗi trình duyệt, không tương đương byte.
- Hành động: đồng bộ service/copy/JS theo8–72 byte; đo TextEncoder ở client, strlen ở server, giữ kiểm NUL và không lộ mật khẩu. Kiểm tra lại con số trong code hiện tại vì tài liệu cũ từng ghi12.
- Kiểm tra: tests/pm_import_password_feedback_browser.js với7/8/>72 byte và Unicode; tests/pm_import_apply_smoke.php.
- Bằng chứng: commit eec12d0, docs/PM_UI_BE_ACTION_RETEST_20261016.md.

<a id="pm-l004"></a>

## PM-L004 — Apply trùng hoặc gián đoạn không được tạo lại user
- Loại: rủi ro nghiệp vụ có cơ chế kiểm soát và test; không coi mọi trạng thái inactive là lỗi.
- Khi gặp: batch partial/in_progress, duplicate email hoặc chạy lại Apply.
- Nguyên nhân: dc_users MyISAM không cung cấp atomic transaction toàn file; SELECT-check-then-INSERT có race.
- Hành động: giữ MyISAM, INSERT từng dòng dựa UNIQUE email, phân loại1062, khóa batch/identity và journal/provenance để resume; tài khoản mới inactive, mặc định EMPLOYEE. Không Apply lại batch cũ để thử giao diện.
- Kiểm tra: tests/pm_import_apply_smoke.php, tests/pm_import_native_duplicate.php và tests/pm_import_http_smoke.php.
- Bằng chứng: docs/PLAN_FINAL.md, docs/PM_UI_BE_ACTION_RETEST_20261016.md. Không đổi engine để làm test dễ hơn.

<a id="pm-l005"></a>

## PM-L005 — UPDATE không đổi dòng nhưng UI báo đã lưu sai
- Loại: lỗi sản phẩm đã sửa.
- Khi gặp: đối tượng missing/hidden/foreign vẫn có thông báo lưu hoặc tracking.
- Nguyên nhân: affected_rows=0 có thể là giá trị không đổi hoặc không có đối tượng; không phân biệt bằng con số này đơn lẻ.
- Hành động: khi0 dòng, kiểm tra tồn tại active trong cùng tenant; chỉ ghi notice/tracking sau kết quả hợp lệ, vẫn cho lưu form thực sự không đổi.
- Kiểm tra: tests/pm_users_crud_smoke.php và tests/pm_role_http_smoke.php.
- Bằng chứng: docs/SKILL_USAGE.md mục Zero-row UPDATE result.

<a id="pm-l006"></a>

## PM-L006 — Username và email va chạm chéo
- Loại: lỗi/ranh giới identity đã xử lý và kiểm thử.
- Khi gặp: username người này trùng email người khác, khác hoa/thường/dấu hoặc khoảng trắng DB collation.
- Nguyên nhân: hai định danh đăng nhập dùng chung namespace tra cứu; kiểm tra riêng từng cột hoặc chỉ bằng so sánh PHP là thiếu.
- Hành động: dùng quy tắc identity chung và lock theo tenant, kiểm cả va chạm chéo theo DB collation, không tính chính user đang sửa là trùng.
- Kiểm tra: tests/pm_user_identity_smoke.php, tests/pm_auth_identity_smoke.php, tests/pm_import_apply_smoke.php.
- Bằng chứng: docs/TEST_REPORT_FULL.md phần identity và docs/SKILL_USAGE.md. Email đúng cú pháp không chứng minh hộp thư tồn tại.

<a id="pm-l007"></a>

## PM-L007 — Lỗi scope bị trả HTTP200
- Loại: lỗi contract backend đã sửa.
- Khi gặp: POST ID foreign/missing không ghi dữ liệu nhưng response thành công200.
- Nguyên nhân: controller catch exception phạm vi rồi chỉ render lỗi, làm mất HTTP status; không phải mọi lỗi validation đều phải400.
- Hành động: phân loại exception scope và mapping403/404 theo contract đã duyệt; giữ validation nghiệp vụ200 khi thiết kế yêu cầu, không đoán quyền từ chuỗi thông báo.
- Kiểm tra: tests/pm_tenant_matrix_smoke.php, tests/pm_aggregate_store_input_smoke.php và tests/pm_role_http_smoke.php.
- Bằng chứng: docs/TENANT_SCOPE_FIX_PLAN_20261008.md, docs/TEST_REPORT_FULL.md các lượt xác nhận tenant sau sửa.

<a id="pm-l008"></a>

## PM-L008 — Input bị chuẩn hóa trước khi validate
- Loại: lỗi validation đã sửa.
- Khi gặp: điện thoại chứa whitespace trái phép lọt qua hoặc regex làm Smarty lỗi compile.
- Nguyên nhân: Request::element trim làm mất dữ liệu cần từ chối; dấu ngoặc regex xung đột cú pháp Smarty.
- Hành động: kiểm raw input trước normalize theo trường; giữ validation server, dùng ldelim/rdelim phù hợp trong Smarty; trả lỗi theo trường và không echo mật khẩu.
- Kiểm tra: tests/pm_user_input_smoke.php, tests/pm_user_input_browser.js, tests/pm_required_fields_http_smoke.php.
- Bằng chứng: docs/SKILL_USAGE.md các mục Personnel create validation/field errors.

<a id="pm-l009"></a>

## PM-L009 — Kanban đổi trạng thái làm mất trường khác hoặc sai số lượng
- Loại: lỗi tương tác/ranh giới mutation đã sửa và bảo vệ.
- Khi gặp: task đã chuyển cột nhưng count/empty/filter không khớp, timeout vẫn để UI báo thành công.
- Nguyên nhân: chỉ sửa DOM card mà bỏ cập nhật trạng thái liên quan; dùng full-edit cho status-only có thể ghi đè field khác.
- Hành động: status-only lock và bảo toàn trường khác, audit/completed_at cùng quy tắc; chỉ cập nhật cột/count sau response xác nhận, khôi phục pending khi lỗi.
- Kiểm tra: tests/pm_kanban_browser.js và tests/pm_task_status_http_smoke.php.
- Bằng chứng: docs/SKILL_USAGE.md các mục Kanban và docs/PM_UI_BE_ACTION_RETEST_20261016.md.

<a id="pm-l010"></a>

## PM-L010 — 12:00 SA bị hiểu thành12 giờ trưa
- Loại: nhập liệu/locale, không phải bug đã xác nhận của ứng dụng.
- Khi gặp: khoảng08:00 SA–12:00 SA bị từ chối vì kết thúc trước bắt đầu.
- Nguyên nhân: SA làAM;12:00AM là00:00. Ảnh ban đầu và ảnh lưu08:00–12:00 sau sửa cho thấy sự khác biệt này.
- Hành động: đối chiếu giá trị time24h thật và thời lượng;12 giờ trưa là12:00PM/CH. Không bỏ chặn qua đêm hoặc nới validation để nhận khoảng sai.
- Kiểm tra: tests/pm_allocations_smoke.php, đối chiếu UI value và khoảng lưu; không lấy phản hồi người dùng làm test tự động đã chạy.
- Bằng chứng: cuộc trao đổi UAT15/10/2026 và docs/PM_FULL_HOST_UAT_20261014B.md.

<a id="pm-l011"></a>

## PM-L011 — Chấm công trước ngày hiệu lực có chi phí0
- Loại: đúng nghiệp vụ, đã được người dùng giải thích/xác nhận.
- Khi gặp: ngày14 thiếu đơn giá trong khi rate chỉ hiệu lực15–31.
- Nguyên nhân: rate áp theo work_date và snapshot đã lưu, không theo ngày xem hay rate hiện tại.
- Hành động: kiểm ngày hiệu lực, rate_source/rate_warning và snapshot; giữ cảnh báo thiếu rate, không tính lại dữ liệu cũ theo rate mới để làm báo cáo đẹp.
- Kiểm tra: tests/pm_rates_smoke.php, tests/pm_timesheets_smoke.php, tests/pm_costs_missing_rate_browser.js.
- Bằng chứng: docs/PLAN_FINAL.md, cuộc trao đổi UAT15/10/2026.

<a id="pm-l012"></a>

## PM-L012 — OT hoặc task actual bị cộng sai khi có nhiều bản ghi
- Loại: quy tắc tính và chống regression.
- Khi gặp: sửa một timesheet không tính lại cả ngày; task actual bị nhân theo số allocation hoặc cộng OT thêm lần nữa.
- Nguyên nhân: OT phụ thuộc tổng giờ ngày; JOIN nhiều-nhiều có thể nhân SUM; hours đã bao gồm OT.
- Hành động: recalculate cả user/day sau ghi/sửa/ẩn; aggregate task theo đúng grain trước join, không cộng ot_hours vào hours lần nữa.
- Kiểm tra: tests/pm_timesheets_smoke.php, tests/pm_task_estimate_smoke.php và tests/pm_task_estimate_http_smoke.php.
- Bằng chứng: docs/D2_VERIFY_20261008.md, docs/PM_UI_BE_ACTION_RETEST_20261016.md.

<a id="pm-l013"></a>

## PM-L013 — Không có chi tiết không đồng nghĩa tải bằng0
- Loại: ngữ nghĩa quyền/dữ liệu cần giữ.
- Khi gặp: bảng phân bổ chỉ thấy một phần dự án nhưng tổng tải cao hơn chi tiết, estimate0 bị báo vượt sai.
- Nguyên nhân: tổng tải có thể gồm dự án ngoài phạm vi chi tiết; schema estimate0 không phân biệt chưa ước tính với cố tình0.
- Hành động: giữ tổng tải scoped theo policy, ẩn tên ngoài quyền; ghi Chưa có chi tiết thay vì0; estimate0 ghi Chưa có ước tính theo quyết định đã duyệt.
- Kiểm tra: tests/pm_allocation_summary_contract_smoke.php, tests/pm_allocations_http_smoke.php và tests/pm_task_estimate_smoke.php.
- Bằng chứng: docs/PLAN_FINAL.md, docs/D2_VERIFY_20261008.md.

<a id="pm-l014"></a>

## PM-L014 — Chữ trắng trên panel trắng hoặc tiêu đề tối trên nền tối
- Loại: lỗi cascade thực tế đã sửa.
- Khi gặp: login mờ, heading biến mất sau đổi theme, nền khai báo khác nền computed.
- Nguyên nhân: selector cũ specificity cao hơn, token --card/--muted còn sáng hoặc heading kế thừa Bootstrap.
- Hành động: truy computed foreground/background và rule thắng; sửa semantic token đúng role, scope body PM, kiểm cả login/card/form/error/hover. Không chỉ thêm một rule body yếu hơn.
- Kiểm tra: tests/pm_login_contrast_browser.js, tests/pm_midnight_contrast_browser.js, tests/pm_phase10_contrast_browser.js.
- Bằng chứng: docs/PM_MIDNIGHT_GOLD_20261016.md, docs/PM_LACQUER_PALETTE_20261015.md.

<a id="pm-l015"></a>

## PM-L015 — Select mất mũi tên, focus hoặc nút disabled khó đọc
- Loại: lỗi CSS/control đã sửa và có regression.
- Khi gặp: select trông như input, text đè arrow hoặc disabled bị mờ.
- Nguyên nhân: background shorthand xóa background-image của Bootstrap; opacity/transition làm kết quả màu khác token.
- Hành động: đổi background-color khi chỉ đổi màu, giữ padding/arrow/focus; chờ transition kết thúc trước đo, kiểm đủ disabled/hover/active/focus.
- Kiểm tra: tests/pm_bootstrap_controls_browser.js và tests/pm_phase9b_buttons_browser.js.
- Bằng chứng: docs/SKILL_USAGE.md, docs/PM_UI_BE_ACTION_RETEST_20261016.md.

<a id="pm-l016"></a>

## PM-L016 — Login mobile vẫn hai cột, menu tràn viewport
- Loại: lỗi responsive đã sửa.
- Khi gặp: media query có vẻ đúng nhưng form/menu vẫn bị cắt hoặc che nội dung.
- Nguyên nhân: rule desktop mạnh hơn; left:0/right kế thừa; menu fixed không chừa vùng nút.
- Hành động: kiểm bounding box ở320/390/1440 và DOM order, override đúng specificity; mobile xếp cột, canh menu trong viewport, giữ nội dung tương tác.
- Kiểm tra: tests/pm_login_contrast_browser.js, tests/pm_header_layout_browser.js, tests/pm_studio_layout_browser.js.
- Bằng chứng: docs/PM_MIDNIGHT_GOLD_20261016.md, docs/HEADER_NAV_VERIFY_20261013.md.

<a id="pm-l017"></a>

## PM-L017 — Đổi màu/viền chưa đáp ứng yêu cầu nâng cấp UI
- Loại: bài học thiết kế từ phản hồi người dùng, không phải bug backend.
- Khi gặp: nhiều vòng màu xanh/tím bị từ chối hoặc giao diện vẫn bị nhận xét cơ bản/rối.
- Nguyên nhân: thay token đơn lẻ không giải quyết bố cục, mật độ và phân cấp; không được coi gu màu là sự thật phổ quát.
- Hành động: bám mẫu người dùng và DESIGN.md hiện tại, thiết kế composition thực; nhóm control/data, thu gọn giải thích dài nhưng giữ lỗi quan trọng. Hiện theme đã chọn là Midnight Gold, không tự quay về xanh/tím.
- Kiểm tra: tests/pm_studio_layout_browser.js, tests/pm_ui_review_browser.js và người dùng duyệt.
- Bằng chứng: docs/PM_STUDIO_LAYOUT_20261015.md, DESIGN.md.

<a id="pm-l018"></a>

## PM-L018 — Animation gây cản thao tác hoặc chạy khi reduced motion
- Loại: quy tắc phòng ngừa đã kiểm thử trên bản hiện tại.
- Khi gặp: overlay chặn click, hover nâng cả bảng, hiệu ứng lặp/đợi lâu.
- Nguyên nhân: dùng motion không giới hạn hoặc pseudo-element nhận pointer.
- Hành động: sweep một lần khi hover, pointer-events:none, chỉ lift khối tương tác; reduced motion tắt sweep/lift, animation menu có hủy khi đổi preference/đóng nhanh.
- Kiểm tra: tests/pm_midnight_motion_browser.js và tests/pm_motion_browser.js.
- Bằng chứng: docs/PM_MIDNIGHT_GOLD_20261016.md.

<a id="pm-l019"></a>

## PM-L019 — Font hoặc asset local đúng nhưng host vẫn cũ
- Loại: chẩn đoán/cache, không mặc định mọi lỗi host do cache.
- Khi gặp: CSS/template mới chưa hiện hoặc glyph Việt fallback.
- Nguyên nhân: cần xác minh file upload/version/cache; font test quá sớm có thể đọc khi font chưa render.
- Hành động: đối chiếu đường dẫn/hash/asset version, chờ document.fonts.ready và node render trước đo; Ctrl+F5 và cache Smarty theo công cụ host sau upload đúng. Không kết luận trước khi có bằng chứng.
- Kiểm tra: tests/pm_phase9b_theme_browser.js, tests/pm_deploy_manifest_test.py.
- Bằng chứng: docs/SKILL_USAGE.md, docs/PM_UI_BE_ACTION_RETEST_20261016.md.

<a id="pm-l020"></a>

## PM-L020 — Test cũ báo lỗi dù luồng hiện tại đúng
- Loại: lỗi harness đã sửa.
- Khi gặp: test đòi logoD/màu sáng/chuỗi tiếngAnh; thao tác control đang trong panel đóng; cấm mọi script hợp lệ.
- Nguyên nhân: fixture/kỳ vọng không theo contract hiện tại.
- Hành động: tái hiện, so sánh source/render mới, mở tab/details như người dùng; cập nhật chính xác kỳ vọng đã đổi, giữ quyền/CSRF/payload/XSS và contrast. Không sửa sản phẩm để chiều test cũ.
- Kiểm tra: 47 suite trong docs/PM_UI_BE_ACTION_RETEST_20261016.md; dùng context riêng và kiểm cả Result/Error, không chỉ process exit0.
- Bằng chứng: docs/PM_UI_BE_ACTION_RETEST_20261016.md.

<a id="pm-l021"></a>

## PM-L021 — Chart thiếu dữ liệu bị đọc sau khi hủy
- Loại: lỗi test đã sửa; sản phẩm chủ động giữ fallback.
- Khi gặp: test TypeError đọc Chart.getChart(...).data khi tiền tệ/đơn giá không đủ.
- Nguyên nhân: chart bị destroy đúng thiết kế thay vì vẽ tổng sai.
- Hành động: xác nhận chart không tồn tại và fallback/bảng còn đọc được; không ép tạo chart rỗng để qua test.
- Kiểm tra: tests/pm_costs_browser.js, tests/pm_phase9b_costs_browser.js, tests/pm_costs_missing_rate_browser.js.
- Bằng chứng: docs/PM_UI_BE_ACTION_RETEST_20261016.md.

<a id="pm-l022"></a>

## PM-L022 — Test gọi nhầm server do trùng port
- Loại: lỗi harness đã xác minh.
- Khi gặp: expected200 nhận404 hoặc timeout khi nhiều suite/server cùng chạy.
- Nguyên nhân: HTTP fixture dùng18769 trùng preview read-only; trước đó các suite cùng18770 cũng va chạm.
- Hành động: dành port riêng, preflight từ chối port đã bị chiếm, chỉ dừng process do test sở hữu; chạy tuần tự các suite chia sẻ port/DB, không bỏ guard preview để làm test PASS.
- Kiểm tra: tests/pm_ui_http_smoke.php dùng18849 có occupancy guard; tests/pm_regression.ps1.
- Bằng chứng: docs/PM_UI_BE_ACTION_RETEST_20261016.md, docs/SKILL_USAGE.md.

<a id="pm-l023"></a>

## PM-L023 — Test preload lớp che mất bootstrap thiếu include
- Loại: lỗi sản phẩm đã tái hiện/sửa; host500 chưa có log chứng minh đây là nguyên nhân duy nhất.
- Khi gặp: trang thông báo500 nhưng HTTP fixture cũ vẫn PASS.
- Nguyên nhân: controller khởi tạo PmAccess thiếu include, harness đã preload nên che lỗi.
- Hành động: kiểm controller qua tiến trình sạch/bootstrap thật, nạp dependency tại đúng tầng; không mock/preload chính lớp đang cần xác minh.
- Kiểm tra: tests/pm_notification_bootstrap_smoke.php.
- Bằng chứng: docs/PM_NOTIFICATION_BOOTSTRAP_FIX_20261014.md.

<a id="pm-l024"></a>

## PM-L024 — Rollback test không đủ bảo vệ bảng MyISAM
- Loại: bài học an toàn dữ liệu/harness.
- Khi gặp: test DAO ghi user hoặc tracking ngoài transaction; checksum đổi trong khi có phiên đăng nhập khác.
- Nguyên nhân: MyISAM không rollback như InnoDB; writer phụ và request ngoài fixture có thể thay persistent state.
- Hành động: guard đúng DB local và backup, shadow mọi writer bằng temporary mirror exact-schema, bỏ FK chỉ ở mirror khi cần; so sánh bảng/engine từ connection độc lập. Không đổi engine thật hay tự restore dữ liệu biến động chưa rõ nguồn.
- Kiểm tra: tests/pm_users_crud_smoke.php, tests/pm_import_apply_smoke.php, tests/pm_role_http_smoke.php.
- Bằng chứng: docs/SKILL_USAGE.md, docs/PM_UI_BE_ACTION_RETEST_20261016.md. Row hash không chứng minh AUTO_INCREMENT không tăng.

<a id="pm-l025"></a>

## PM-L025 — Tải mẫu bị khóa nút hoặc test không thấy POST
- Loại: lỗi interaction/test đã sửa.
- Khi gặp: tải file lần hai không được, Apply không submit trong test dù nút bấm được.
- Nguyên nhân: busy guard áp nhầm download không rời trang; named submitter/action cần được giữ; confirm mặc định bị browser test dismiss hoặc chưa chờ response.
- Hành động: tách download khỏi busy guard ghi; giữ action/CSRF của submitter, kiểm hủy/chấp nhận confirm và đợi đúng request/response.
- Kiểm tra: tests/pm_import_tabs_browser.js, tests/pm_import_browser.js, tests/pm_confirmation_browser.js.
- Bằng chứng: docs/SKILL_USAGE.md, docs/PM_UI_BE_ACTION_RETEST_20261016.md.

<a id="pm-l026"></a>

## PM-L026 — Gói upload thiếu dependency hoặc đặt sai thư mục
- Loại: rủi ro bàn giao đã kiểm soát; việc người dùng từng không mở được gói chưa có nguyên nhân xác định.
- Khi gặp: CSS/JS mới thiếu, tạo nhầm public_html/runtime hoặc đề nghị upload toàn repo.
- Nguyên nhân: gói delta không gom include/font/vendor, không ghi rõ root; không được suy lỗi ZIP nếu chưa kiểm CRC.
- Hành động: đóng gói từ commit cố định, xác minh dependency closure/CRC/SHA256/inventory, chỉ runtime cần thiết; backup, assets trước/template sau, gộp không xóa thư mục. Loại config/dump/test/session/secret, kèm rollback.
- Kiểm tra: tests/pm_deploy_manifest_test.py và kiểm archive/hash từng entry; không gọi việc tạo gói là deploy.
- Bằng chứng: tests/pm_workspace_package.py, docs/PM_UI_BE_ACTION_RETEST_20261016.md, gói UI16/10 có manifest54 runtime.

<a id="pm-l027"></a>

## PM-L027 — PASS tự động bị đánh đồng nghiệm thu100%
- Loại: bài học báo cáo/phạm vi.
- Khi gặp: đủ suite PASS nhưng chưa login PM/HR thật, chưa persistence browserE2E hoặc user chỉ xác nhận một màn hình.
- Nguyên nhân: trộn unit/fixture/HTTP/service/hostUAT và số lượng suite với số tiêu chí đề bài.
- Hành động: ghi đúng môi trường, version, lệnh, kết quả và giới hạn; chỉ đóng tiêu chí có bằng chứng đúng phạm vi. Người dùng đã chốt dự án hoàn thành theo phạm vi thống nhất; các giới hạn kỹ thuật còn mở ghi follow-up, không tự đổi thành testPASS.
- Kiểm tra: đối chiếu docs/PM_TEN_REQUIREMENTS_STATUS_20261016.md và docs/PM_UI_BE_ACTION_RETEST_20261016.md trước báo cáo.
- Bằng chứng: docs/TEST_REPORT_FULL.md; cron tách ngoài10 yêu cầu.

<a id="pm-l028"></a>

## PM-L028 — Timeout gián đoạn bị gán nguyên nhân quá sớm
- Loại: nguyên nhân còn mở, không ghi là bug đã sửa.
- Khi gặp: HTTP không tái hiện ổn định hoặc connection-close chưa nhả lock ngay.
- Nguyên nhân: timeout HTTP lịch sử chưa xác định; server có thể xử lý đóng connection chậm, nhưng không suy đó là nguyên nhân của mọi timeout.
- Hành động: giữ output lầnFAIL, ghi timing/request/port/lockowner, kiểm sequential và bounded wait phíaDB khi test lock-release; không retry vô hạn hoặc gọi PASS lần sau là xác định root cause.
- Kiểm tra: tests/pm_import_apply_smoke.php có bounded GET_LOCK; đối chiếu docs/P0_VERIFY_20261008.md và docs/P0_FLAKY_INVESTIGATION_20261007.md.
- Bằng chứng: docs/SKILL_USAGE.md và docs/TEST_REPORT_FULL.md. Rate concurrency/session expiry vẫn cần bằng chứng riêng.

<a id="pm-l029"></a>

## PM-L029 — URL chẩn đoán dừng ở đăng nhập hoặc nhầm thư mục cron
- Loại: vấn đề entrypoint đã có giải pháp được duyệt, không khái quát thành phải chuyển mọi file cron ra webroot.
- Khi gặp: mở file CLI như trang web hoặc thấy login thay kết quả smoke.
- Nguyên nhân: phải kiểm routing/session/guard; việc đặt file cùng admin không tự chứng minh quyền và bootstrap đúng.
- Hành động: giữ job trong cron; chỉ dùng entry webroot GET/Admin cố định đã duyệt include đúng smoke, chặn query tùy ý và direct module; không mở công khai cron hay tắt auth để test.
- Kiểm tra: tests/pm_cron_check_smoke.php, tests/pm_cron_check_failure_smoke.php, tests/pm_cron_check_http_browser.js.
- Bằng chứng: docs/PM_CATEGORY_SCOPE_WEB_CHECK_20261014.md. Smoke dữ liệu mẫu không kiểm toàn chức năng production.

<a id="pm-l030"></a>

## PM-L030 — Cron chỉ xét userID cố định
- Loại: cải tiến scope ngoài10 yêu cầu, không phải phải tạo cronOS cho từng user.
- Khi gặp: user mới có việc nhưng command vẫn chỉ chứa ID cũ.
- Nguyên nhân: command chọn scope một user thay vì all-users đã được duyệt.
- Hành động: dùng một lịch với scope all-users/store hợp lệ, kiểm active/quyền/membership mỗi lượt; không tự thêm command cho mỗi tài khoản hoặc bỏ scoping.
- Kiểm tra: tests/pm_cron_throttle_smoke.php, tests/pm_reminders_smoke.php; host cần đối chiếu command thực.
- Bằng chứng: docs/PM_FULL_HOST_UAT_20261014B.md, docs/PM_CRON_THROTTLE_BUILD_VERIFY_20261014.md.

<a id="pm-l031"></a>

## PM-L031 — Thông báo không khớp tên task hoặc cron tạo trùng
- Loại: quy tắc nghiệp vụ/cải tiến; không coi tên task mô tả là deadline thật.
- Khi gặp: task tên Quá hạnD7 nhưng nhập due ngày mai nên thông báo ngày mai; nhắc chấm công chưa tới giờ; duplicate hoặc deadline đổi.
- Nguyên nhân: thông báo xét ngày/trạng thái/scope/cửa sổ chạy thật và khóa chống trùng; policy012 có thêm snapshot ngày hạn.
- Hành động: kiểm due_date/work_date, timezone và clock window thực, trạng thái và khóa UNIQUE; phân loại duplicate1062 đúng constraint, lỗi SQL khác phải failed. Không dùng --date giả cùng commit để làm host test đạt.
- Kiểm tra: tests/pm_reminders_smoke.php, tests/pm_cron_throttle_smoke.php; native SQL/concurrency012 dùng quy trình riêng sau backup, không tự nâng PASS.
- Bằng chứng: docs/PM_CRON_THROTTLE_BUILD_VERIFY_20261014.md, docs/PM_FULL_HOST_UAT_20261014B.md và phản hồi UAT15/10.

<a id="pm-l032"></a>

## PM-L032 — Tiếng Việt bị hỏng hoặc lệnh Python lỗi console encoding
- Loại: lỗi công cụ/encoding, không tự gán thành lỗi dữ liệu sản phẩm.
- Khi gặp: shell chèn dấu thành?, chuỗi match không sửa được, catalog ECC --show UnicodeEncodeError ở cp1252.
- Nguyên nhân: encoding console/pipe khác UTF-8; PowerShell redirection và Set-Content có thể khác encoding theo phiên bản.
- Hành động: đọc/ghi explicit UTF-8, dùng apply_patch cho Unicode source hoặc escape Unicode trong script; đọc log theo BOM thay vì đoán. Với catalog --show lỗi in console, đọc pinned reference bằng Get-Content -Encoding UTF8 hoặc đặt UTF-8 cho riêng tiến trình; không sửa global locale.
- Kiểm tra: so sánh UTF-8 source/render và chạy parser/lint thực; không coi exit0 của replacement là đã tìm/thay đúng.
- Bằng chứng: docs/SKILL_USAGE.md các trường hợp encoding; lỗi catalog --show được quan sát trực tiếp16/10 và đã đọc đầy đủ pinned skill qua Get-Content.

## Bảo trì bộ nhớ

Mỗi mục có trigger/hành động/kiểm tra/bằng chứng; khi thay contract cần cập nhật mục tương ứng và JSON. Không append toàn bộ transcript, log hoặc mật khẩu. Không gọi các mục nguy cơ chưa tái hiện là lỗi đã xảy ra. Confidence trong JSON chỉ là mức đánh giá thủ công về quy tắc, không phải xác suất bug hay chứng nhận môi trường khác. Không tự global hóa pattern DeraSoft cho dự án khác.
