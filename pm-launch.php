<?php
if (!defined('ROOT_PATH')) {
	http_response_code(403);
	exit('Forbidden');
}

header('Content-Type: text/html; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow, noarchive');
?>
<!doctype html>
<html lang="vi">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow, noarchive">
	<meta name="theme-color" content="#0f172a">
	<title>DeraSoft PM</title>
	<style>
		:root{color-scheme:light;--navy:#0f172a;--slate:#475569;--line:#e2e8f0;--soft:#f8fafc;--white:#fff;--orange:#c2410c}
		*{box-sizing:border-box}
		html{font-family:Arial,"Helvetica Neue",sans-serif;background:var(--soft);color:var(--navy)}
		body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;background:linear-gradient(135deg,#f8fafc 0%,#eef2f6 100%)}
		main{width:min(960px,100%);background:var(--white);border:1px solid var(--line);box-shadow:0 24px 64px rgba(15,23,42,.08);display:grid;grid-template-columns:1.25fr .75fr}
		.content{padding:clamp(36px,7vw,80px)}
		.brand{display:inline-flex;align-items:center;gap:12px;font-weight:700;letter-spacing:.02em}
		.mark{width:38px;height:38px;display:grid;place-items:center;background:var(--navy);color:#fff}
		.eyebrow{margin:68px 0 16px;color:var(--orange);font-size:.78rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase}
		h1{font-size:clamp(2.35rem,6vw,4.5rem);line-height:.96;letter-spacing:-.055em;margin:0;max-width:650px}
		.lead{font-size:clamp(1rem,2vw,1.2rem);line-height:1.7;color:var(--slate);max-width:600px;margin:28px 0 0}
		.status{background:var(--navy);color:#fff;padding:clamp(36px,5vw,56px);display:flex;flex-direction:column;justify-content:space-between;min-height:540px}
		.status-label{font-size:.76rem;letter-spacing:.14em;text-transform:uppercase;color:#cbd5e1}
		.status h2{font-size:1.45rem;line-height:1.3;margin:14px 0}
		.status p{color:#cbd5e1;line-height:1.65;margin:0}
		.admin-link{color:#fff;text-underline-offset:5px;font-size:.9rem}
		.admin-link:focus-visible{outline:3px solid #fb923c;outline-offset:6px}
		@media(max-width:760px){main{grid-template-columns:1fr}.eyebrow{margin-top:48px}.status{min-height:300px}.content{padding:40px 28px}.status{padding:36px 28px}}
		@media(prefers-reduced-motion:reduce){*{scroll-behavior:auto!important}}
	</style>
</head>
<body>
	<main>
		<section class="content" aria-labelledby="page-title">
			<div class="brand"><span class="mark" aria-hidden="true">D</span><span>DeraSoft PM</span></div>
			<p class="eyebrow">Nền tảng vận hành nội bộ</p>
			<h1 id="page-title">Quản lý dự án rõ ràng hơn.</h1>
			<p class="lead">Quản lý dự án, phân công công việc, ghi nhận giờ làm và theo dõi chi phí trên một hệ thống thống nhất.</p>
		</section>
		<aside class="status" aria-label="Trạng thái dự án">
			<div>
				<span class="status-label">Không gian làm việc</span>
				<h2>Hệ thống đã hoàn thành</h2>
				<p>Đăng nhập để quản lý dự án, chấm công, phân bổ nguồn lực và xem báo cáo theo quyền được cấp.</p>
			</div>
			<a class="admin-link" href="/admin.php">Đăng nhập quản trị</a>
		</aside>
	</main>
	<script>
		if ('serviceWorker' in navigator) {
			navigator.serviceWorker.getRegistrations().then(function (registrations) {
				registrations.forEach(function (registration) { registration.unregister(); });
			});
		}
	</script>
</body>
</html>
