# Quy tắc sử dụng skill trong DeraSoft PM

## Yêu cầu giao diện xuyên suốt — 07/10/2026

Với mọi công việc giao diện tiếp theo của dự án, áp dụng skill impeccable để
thiết kế/tinh chỉnh và kiểm soát chất lượng; dùng playwright-cli khi kiểm tra
trình duyệt. Giữ Inter, màu trung tính, viền rõ, nút dễ nhận biết và accessibility.
Đọc hướng dẫn trước lần áp dụng đầu, tái sử dụng khi bối cảnh không đổi. Không
bật các skill ngoài phạm vi. Mọi thay đổi hiện giữ local, chờ người dùng kiểm tra
toàn bộ rồi duyệt một gói production; không tự push/merge/deploy.

Đã chốt với người dùng ngày 07/10/2026. Đây là bộ nhớ trong repository cho các
lần làm việc tiếp theo, không thay thế hướng dẫn hiện hành của từng skill.

## Chọn đúng phạm vi

| Công việc | Skill phù hợp | Điều kiện áp dụng |
| --- | --- | --- |
| Bổ sung hoặc sửa chức năng đáng kể | feature-development | Hiểu luồng hiện có, triển khai và kiểm thử đến khi hoàn tất. |
| Cải tiến giao diện đang có | impeccable | Phân cấp, typography, nút, màu, responsive và accessibility; giữ theme đã duyệt. |
| Kiểm tra tương tác trình duyệt | playwright-cli | Kiểm tra hành vi thực tế và computed style, bàn phím, kích thước màn hình. |
| Review trước khi chốt thay đổi | code-review | Tìm lỗi, hồi quy, bảo mật và thiếu kiểm thử; không coi review là bằng chứng chạy test. |
| Migration hoặc thao tác có rủi ro dữ liệu | database-safety | Kiểm tra môi trường, quyền, phạm vi và rollback; không cần cho truy vấn chỉ đọc thông thường. |
| Commit thay đổi hoàn tất | git-feature-commit | Kiểm tra diff, danh tính/remote, stage đúng file, xác minh rồi commit local. |
| Điều khiển ứng dụng máy tính | computer-use:computer-use | Chỉ khi cần thao tác UI và công cụ chuyên dụng không phù hợp. |
| Tạo ảnh bitmap thật sự cần thiết | imagegen | Không dùng thay HTML/CSS, icon SVG hoặc để trang trí không cần thiết. |
| Dự án dùng shadcn/ui thực tế | shadcn | DeraSoft PHP/Smarty chỉ tham khảo phong cách shadcn không phải lý do đổi stack hay cài thư viện. |
| Công việc phức tạp nhiều bước | deepseek-harness | Chỉ thêm khi phương pháp tổ chức này giúp công việc; tránh lặp quy trình đã đủ. |

Chỉ kết hợp skill có vai trò riêng trong công việc. Chỉnh tài liệu nhỏ hoặc giải
thích đơn giản không cần kích hoạt quy trình phát triển tính năng đầy đủ. Tạo file
Excel độc lập có thể dùng spreadsheets; sửa chức năng xuất XLSX trong ứng dụng
vẫn là công việc phát triển và kiểm thử phần mềm.

## Kinh nghiệm đã xác minh

- **Login bị mờ:** kiểm tra computed style và độ ưu tiên CSS trước khi đổi màu.
  Rule card chung làm nền intro trắng trong khi CSS legacy giữ chữ trắng.
  Sửa bằng selector giới hạn trang login; kiểm tra cả nút submit legacy.
  Đo tương phản thật và xem desktop/mobile, focus, phóng đại chữ. Bản vá
  `e6f164f` cho brand/title/description đạt 18,10:1 trong kiểm thử local.
- **Local đúng, production khác:** đối chiếu file thực sự upload và phiên bản
  asset. Cache là khả năng cần kiểm tra, không phải nguyên nhân mặc định.
  Bản vá login thêm phiên bản CSS và gồm cả CSS lẫn template. Không kết luận
  toàn ứng dụng đã triển khai từ việc một trang đã sửa thành công.
- **Select mất mũi tên:** shorthand `background` có thể xóa background-image
  của Bootstrap. Dùng `background-color` khi chỉ đổi nền và kiểm tra computed
  background-image cùng khoảng trống phía phải.
- **Kiểm thử font:** đợi font tải, render glyph hiển thị và ổn định layout trước
  khi đọc kết quả. Không chấp nhận kết quả rỗng hoặc suy ra font từ tên CSS.
- **Xác nhận kết quả:** automated PASS, nghiệm thu người dùng và bằng chứng
  production là ba loại khác nhau. Ghi môi trường, lệnh và phạm vi kiểm tra.

## Cập nhật sau mỗi trường hợp mới

Ghi ngắn gọn: yêu cầu → skill đã dùng và lý do → nguyên nhân xác minh → cách sửa
→ kiểm tra thực tế → giới hạn còn lại. Không lưu mật khẩu, secret hoặc dữ liệu
người dùng. Chỉ tái sử dụng cách sửa sau khi kiểm tra bối cảnh tương đương;
không cần hỏi lại những lựa chọn thường lệ đã được chốt.

## HTTP role fixture coverage - 2026-10-07

Used feature-development for controller verification, code-review for test safety
and git-feature-commit for the completed local change. Missing real PM/HR actors
must not become silent coverage skips: supplement with explicitly labeled HTTP
fixtures, without changing persistent account roles. MySQL temporary tables
cannot carry foreign keys; fixture copies retain columns/indexes and omit FK
constraints only in the connection-local role table. Verified 30 HTTP cases,
47 regression scripts, unchanged persistent roles/projects/user engine. These
fixtures do not constitute password-login or production UAT evidence.

## HTTP mutation isolation - 2026-10-07

Before allowing test POST actions, trace every write target, including legacy
tracking, and shadow all targets in connection-local temporary tables. Capture
request-local state before connection teardown, and compare persistent state
before/after. Verified project/task POST through unchanged controllers with
40 HTTP cases and 47 regression scripts PASS. This verifies isolated request
writes; it does not establish cross-request persistence or browser form E2E.

## Fixture route and write isolation - 2026-10-07

Test routers must require GET/POST route agreement before selecting shadow
write tables. Route mismatch is rejected before controller/DB execution. Scope
fixture tables to the route under test; other controllers may issue queries
that temporary-table limitations cannot support. Verified 52 HTTP cases with
persistent business state unchanged. This is test harness behavior, not a
production middleware change or an application defect claim.

## Zero-row UPDATE result - 2026-10-07

Personnel HTTP test reproduced false saved notice/tracking for invalid targets.
Zero affected rows may mean unchanged form or no matching target. DAO now checks
visible same-tenant existence when UPDATE changes zero rows; controller checks
return before success/tracking. Verify unchanged, missing, hidden and cross-tenant
cases together. 73 combined HTTP cases and 47 regression scripts PASS; actual
users unchanged. Applied existing development/review/commit workflow skills.

## New log identity policy - 2026-10-07

Applied feature-development, code-review and git-feature-commit to the approved
ID-only policy for new PM logs. Preserve historical records and legacy behavior;
avoid rewriting existing last_ip while incrementing failed-login counters. Actual
auth module execution with recording collaborators verifies emitted fields without
DB access; HTTP fixtures verify project/personnel logs and persistent-state safety.
Ten module fixtures, 73 HTTP cases and final 48-script regression PASS. First
regression failed an abandoned import-lock assertion, then standalone and full
rerun passed; root cause remains unconfirmed. These results do not certify all
audit payloads or constitute real-account login UAT.

## Kanban status-only mutations - 2026-10-07

Applied feature-development for the endpoint/service, impeccable for the existing
interaction, playwright-cli for browser behavior, code-review and git-feature-commit.
Status-only writes must lock and preserve non-status task fields, share completion
rules with full edits and audit atomically. Guard AJAX modules against direct access
as well as validating the entry point; legacy dynamic includes must not bypass CSRF.
Verified 33 endpoint HTTP and 81 role HTTP cases, service rollback/no-op, browser
pending/duplicate/timeout/focus/responsive/no-JS and 49-script regression. Test HR
must expect the actual permission denial; script elements are attached, not visible.
Synthetic browser responses plus isolated backend HTTP are not persistence E2E/UAT.

## Project save/hide navigation - 2026-10-07

Used feature-development for the controller interaction and existing review/commit
workflow; preserved UI appearance and native confirmation. Use a fixed 303 target
after successful POST, server-only one-shot flash, and keep errors on the current
form. Test redirect status/Location without automatically following, then assert
the GET landing and flash consumption. Request-local fixture capture belongs in
shutdown when controller exits after redirect. Verified 90 HTTP cases and 49
regression scripts, no persistent changes; fixture GET does not prove cross-request
data persistence or manual UAT.

## Project versus task date boundaries - 2026-10-07

Used feature-development and existing review/commit workflow for the explicit
business constraint: project end must be strictly after start, while tasks may
finish the same day. Use a typed validation exception for the specific project
rule so its redirect/error flash cannot change task or unrelated validation flows.
Verify create/edit, equality, reversed dates and the next-day boundary; assert
invalid writes leave both row data and row counts unchanged. Service, 103 HTTP
cases and 49 regression scripts PASS. Preserve existing rows and optional-date
behavior; do not infer global data stability from checksums across user activity.

## Personnel create validation — 2026-10-07

Used feature-development for server rules, impeccable for the existing form's
input affordances, playwright-cli for rendered validity/viewport checks and
code-review/git-feature-commit for completion. Request::element trims strings;
validate the raw phone before normalization so illegal whitespace cannot vanish.
Escape regex quantifier braces for Smarty with ldelim/rdelim. Do not run two HTTP
suites sharing port 18770 concurrently. Render fresh preview fixtures before
browser checks; an old fixture is not evidence about the current template.
Final evidence: 13 input cases, 116 HTTP cases, 50 regression scripts and browser
phone/password/desktop/mobile checks PASS. Email syntax does not prove mailbox
existence; no OTP, external lookup, or false verified label.

## Personnel field errors — 2026-10-07

Used feature-development/impeccable for explicit field-level feedback and
playwright-cli to verify the existing create form. Return a typed exception with
field errors, escape server messages, reopen the details form and never echo
passwords. JS validates input/blur/invalid, uses textContent and clears error and
aria-invalid when corrected. Match Unicode character counts rather than UTF-8
byte lengths; reject all non-digit phone characters, including final newline.
Verified 21 input cases, 119 HTTP cases, 50 regression scripts and browser exact
messages/correction/blocked submit/desktop/mobile. No external verification claim.

## Actual cost completeness — 2026-10-07

Used feature-development/impeccable for missing-rate semantics/labels and
playwright-cli for polling/chart/fallback verification. A stored zero is not
proof of a configured zero rate: count saved fallback/warning provenance in the
existing scoped aggregate. Preserve authoritative SUM(cost) and annotate it;
do not resolve today's rate to repair historical snapshots. Keep filtered actual
and lifetime completeness separate, and block misleading complete-cost chart
comparisons while keeping legitimate zero charts. SSR partial and JS textContent
share visible labels. Cost service/HTTP/template checks, 50 regression scripts
and two focused/existing browser suites PASS; 19 pending checklist items retained.

## BƯỚC 2 schema và identity — 07/10/2026

Áp dụng feature-development cho BUILD đã duyệt, code-review cho kiểm tra query/lock/fixture, git-feature-commit cho commit local hoàn tất. Không thêm workflow UI khi không đổi thiết kế giao diện. Nguyên nhân xác minh: import nhận username 50/fullname 100 trong khi cột thật 30/50; checks riêng hai cột bỏ collision chéo. Dùng validator chung và so theo collation DB, không lowercase PHP để giả lập accent equality. Apply vẫn INSERT + native 1062; kiểm tra collision khác trước MyISAM write, named lock database+store_id và finally release; legacy ngoài PM không được lock này bảo vệ.

Fixture CRUD/Apply lấy schema thật, không dùng cột rộng giả tạo PASS. Khi thêm HTTP profile route vào test router phải thêm cả guard, user mirror và shutdown observer; thiếu observer gây test fail dù controller chạy. Đã sửa harness và chạy lại: 43 identity case, 139 HTTP case, 51 regression script, PHP lint 14 file PASS. Quét read-only toàn bộ 10 user local: 0 cặp, có positive two-direction fixture scan để kiểm chứng query; không sửa user cũ. Giữ 19 case chưa chạy và dừng trước bước OT theo yêu cầu người dùng.

Một lượt regression có HTTP connection timeout sau case lock, chưa xác định nguyên nhân. Suite HTTP riêng và regression đầy đủ chạy lại đều đạt; giữ lần fail trong TEST_REPORT_FULL, không giấu bằng retry request tự động và không gán nguyên nhân chưa chứng minh. Runner trả session_id phải chờ exit code cuối trước chốt kết quả của lượt đó.

## P0 điều tra flaky — 07/10/2026

Code-review và ignored probes: 10 lần liên tiếp mỗi suite nguyên trạng đều PASS không đủ chứng minh nguyên nhân. Probe 300 close/acquire tái hiện 8 lần GET_LOCK timeout=0 chưa lấy được ngay sau mysqli close nhưng bounded wait lấy được dưới 0,4 ms; assertion timing của harness cần bounded deadline thay kỳ vọng đồng bộ phía server. Không đổi runtime lock từ kết luận test này. HTTP diagnostic chỉ log run/case/thread/timing, không credential/payload; 2 lượt đạt 139 case, lock waits 2 giây ở case cố tình tranh chấp và release nhanh. HTTP timeout lịch sử vẫn chưa có nguyên nhân, giữ P0 mở; không dùng sự vắng mặt lỗi thay evidence. metadata_locks denied phải ghi rõ chưa kiểm tra, không tự nâng quyền hoặc dùng fallback IS_USED_LOCK một số tên để suy ra toàn lock graph.

- **P0 ngày 08/10/2026:** sửa test assertion với chờ GET_LOCK(1) có giới hạn, kiểm tra ownership và release; 10/10 test import PASS. Process CIM bị từ chối nên không coi danh sách rỗng là bằng chứng; dùng Get-Process/netstat, giữ server local 8088 và dừng khi thấy process chưa xác định. 20 lượt HTTP có preflight hợp lệ PASS nhưng timeout cũ vẫn chưa tái hiện, không kết luận nguyên nhân. Chi tiết docs/P0_VERIFY_20261008.md. Dùng feature-development để lập PLAN D2; không BUILD trước duyệt.


## D2 — 08/10/2026

Áp dụng feature-development cho BUILD; impeccable cho cảnh báo trên UI hiện có; playwright-cli được đọc và thử nhưng cache ngoài workspace bị chặn, dùng Playwright API trực tiếp để kiểm tra browser; code-review cho query/quyền/escaping; git-feature-commit để chuẩn bị commit local riêng. Browser thật xác nhận Inter, viền rõ, văn bản cảnh báo, desktop/mobile, no-JS và polling. Detector chạy một lần nhưng không resolve đúng stylesheet tương đối nên warning typography chỉ là giới hạn detector; không coi đó là detector sạch. Temporary mirror ban đầu lỗi lọc nhiều foreign key và fixture hours=100 vượt schema; sửa harness, chạy lại PASS, không thay schema hay dữ liệu thật. Chi tiết docs/D2_VERIFY_20261008.md.


## P4 audit — 08/10/2026

feature-development cho account audit được duyệt, code-review cho actor scope/PII/metadata và git-feature-commit cho commit local hoàn tất. Không skill UI vì không đổi thiết kế. Assertions phải nằm trước rollback/close và chỉ quét event fixture mới, không áp lịch sử actor khác. Các suite DB cùng bảng không độc lập để chạy song song; VERIFY cuối chạy regression tuần tự. Invalid rate overlap/role QA là fixture sai, sửa fixture, giữ validator sản phẩm. Bằng chứng docs/P4_VERIFY_20261008.md.


## Collecting Tenant matrix — 08/10/2026

feature-development cho test/truy vết theo duyệt; code-review cho fixture và PLAN chung. Không skillUI vì không đổi giao diện. Mapping HTTP phải dựa loại lỗi phạm vi, không đổi validation200 hoặc dùng thông báo để đoán quyền. Probe service thật cho OutOfBoundsException; runtime giữ nguyên.

Collect80cặp, không dừng tại caseFAIL nhưng giữ cổng nhóm1 trước nhóm sau. Temporary mirrors không hỗ trợ composite-id giả định hoặc reopen nhiều alias; hash sort rows theo serialization và dùng realAdmin/read-only rate+role assignments ở cost routes, vẫn hash bảng thật. JSON thông báo phải decode trước kiểm Unicode. Không gán PMmanager bị bỏ qua thành IDOR; dùng Admin cho tham chiếu đó. Một lượt checksum thật thay đổi trong lúc có login; giữFAIL và điều tra read-only, không tự restore hay giấu bằng retry. Lượt cuối39PASS/41FAIL/11controlsPASS,cleanup21bảngPASS; PLAN chờ duyệt, không sửa runtime. Bằng chứng TENANT_MATRIX_VERIFY_20261008.md.


## HTTP lô1 — 09/10/2026

Tái sử dụng feature-development để hoàn thiện test, code-review cho middleware denial/CSRF/exact-schema mirrors và git-feature-commit cho local commit đã verify. Không áp dụng skill UI vì không chỉnh thiết kế. Excel compare chỉ chuẩn hóa metadata created/modified; kiểm tra worksheet và leak trên XML gốc giữ nguyên. Auth fixture thêm login_times mirror, assertions không xuất password/hash/cookie/token. 98 HTTP checks và36 forged-store comparisons PASS; expiry/rate concurrency vẫn CHƯA CHẠY. Chi tiết BATCH1_HTTP_VERIFY_20261009.md.


## Validation0.4 — 09/10/2026

feature-development BUILD đúng PLAN được duyệt; code-review rà phạm vi thông báo/typed exception/status và git-feature-commit chuẩn bị commitlocal sau59suite PASS. Không sửa giao diện nên không áp skillUI; sử dụng UI error escaped đã có. Temp observer chỉ keys/booleans, prepared SQL. Giữ baseline14FAIL và harness redirect expectation lỗi riêng; không nớiassertion hay sửaproduct theo harness. 30cases PASS,21 bảng thật không đổi, regression59PASS.

## Workspace UI review — 09/10/2026
Impeccable cho UI, code-review cho kiểm tra lỗi trước chốt. Lọc theo tên gây gộp tài khoản trùng fullname: dùng ID hiện có làm value, giữ tên làm nhãn. JSON roundtrip không phù hợp với bằng chứng audit có số lớn: giữ chuỗi escaped nguyên văn. Playwright fixture xác nhận hai identity riêng và số 12345678901234567890.01 không đổi; không dùng PASS frontend để suy luận DB/production PASS.

## Hoàn thiện workspace — cascade và download
Impeccable + Playwright: module CSS cũ có selector mạnh hơn .pm-app nên ghi đè token theme; tăng scope root chỉ body PM. Chờ transition kết thúc khi đo computed style của nút để không ghi PASS từ màu cũ. Download với named submitter action=template giữ trang nên không dùng busy guard của POST lưu; browser tải hai lần và kiểm tra action/CSRF thực tế bằng mock. Các assertion size/AA/font/permission vẫn giữ, chỉ đổi kỳ vọng token thiết kế cũ.

Kanban UI: dùng impeccable + Playwright; đếm DOM theo cột sau phản hồi xác nhận, không cập nhật lạc quan. Bổ sung assertion số lượng/empty/filter, phát hiện refresh bị bỏ sót rồi sửa và chạy lại PASS. Giữ cả lần FAIL trong tài liệu, không suy từ test AJAX cũ thành tính năng đếm đã PASS.

## Đồng bộ workspace và host UAT
feature-development cho BUILDBE đãđược yêu cầu; impeccable/playwright-cli cho template/browser; code-review cho scope/taskpermission/prepared/decimal/truncation; spreadsheets để tạo4XLSX bằngartifact-tool cómarker vàrender11views, appparserverify; git-feature-commit cho localcommit theoAGENTS. Khôngdatabase-safety vì khôngmigration/seedSQL/DBwrite, chỉread-onlypreflightfile vàdữliệu mẫu nhậpUIbởingườidùng. Lỗi bootstrap làtestnhầmheader/head, sửaregexkhôngbỏassertion; workbooktotalRows parsertrảstring, castint vàgiữcounts. No-DBstubs khôngthay thếEXPLAIN/SQLexecution, giữCHƯACHẠY.

## 12/10/2026 — thông báo Việt
Impeccable clarify cho câu chữ/feedback, code-review cho đường exception/controller và playwright-cli cho browser. Giữ theme Inter theo AGENTS. Quét UTF8 không phát hiện mojibake nhưng xác nhận gỡ thành viên có dấu hỏi hỏng, sửa đúng chuỗi. Chờ clock harness sửa API; ảnh mobile phát hiện toast che lỗi, sửa inline + assertion. No-DB không thay thế host UAT.\n
## 13/10/2026 — refinement toast
Tái sử dụng impeccable và playwright-cli, giữ Inter/theme. SVG close thay glyph, test44px và keyboard. Harness gọi include trực tiếp bị403: sửa test qua index, giữ guard. Không áp skill backend hoặc thay DB. Code review giữ errors persistent và escaping.

## 13/10 — centered và rà điều hướng
Tái sử dụng impeccable/playwright-cli/code-review. Kiểm tra12trang2viewport; phát hiện fixture thiếu dữ liệu menu và fileImportcũ, sửa test setup bằng nguồn PmUiService, không sửa quyền runtime.20browser và16no-DBscripts PASS. Ảnh host đã có sidebar; chưa suy cache là nguyên nhân. Xem CENTERED_NOTIFICATION_NAV_VERIFY_20261013.md.

## 13/10 — top-center10s theo yêu cầu mới
Toast phía trên căn giữa ngang,480px/chữ16/15px; success tự ẩn10s, error giữ nguyên.4suitebrowser liên quan và16no-DBscripts PASS; clockharness pauseAt để assert9999/10000ms chính xác. KhôngDB/production. Xem TOP_TOAST_10S_VERIFY_20261013.md.
