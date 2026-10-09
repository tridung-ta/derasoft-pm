# Lô 3 — bằng chứng một phần ngày 09/10/2026

Không sửa giao diện/runtime, không truy cập DB. Skill playwright-cli và impeccable: kiểm tra kỹ thuật trong phạm vi hai mục.

- Server: .tools/php83/php.exe -S 127.0.0.1:18767 -t . tests/pm_csp_preview_router.php. Router dùng pmResponseHeaders sản phẩm, chỉ đọc HTML preview loopback.
- Xác minh Invoke-WebRequest header: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; connect-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'; object-src 'none'.
- Từ .local: playwright-cli -s=batch3 run-code --filename=../tests/pm_phase9b_costs_browser.js → PASS: real Chart.js empty/mixed/zero/recovery, decimal strings, tab keyboard/state, polling errors/auth, responsive/text scaling/CSP, no-JS and missing-chart fallbacks. Log batch3-csp-costs-20261009.txt.
- playwright-cli -s=batch3 run-code --filename=../tests/pm_warning_color_browser.js → {result: PASS preview computed-color scan, fixtures: 37, bad: [], scope: Rendered fixture states only}. Log batch3-colors-final-20261009.txt.

9.5 giữ CHƯA CHẠY đầy đủ: chưa tương tác mọi form/module dưới CSP thật. 10.3 giữ CHƯA CHẠY đầy đủ: computed scan chỉ trạng thái rendered, chưa mọi cảnh báo động/hover/focus và pseudo-element. Không nâng PASS từ kiểm tra một phần.

Lượt router ban đầu return false khiến static response không giữ header; lượt đó không tính bằng chứng CSP. Đã sửa harness phục vụ HTML trực tiếp, xác minh header rồi chạy lại. Không sửa chính sách CSP sản phẩm. Browser và server thuộc phiên đã đóng.
