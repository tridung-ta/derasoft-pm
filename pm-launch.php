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
<link rel="stylesheet" href="css/pmlaunch.css?v=20261016-midnight1">
<link rel="stylesheet" href="css/pmpolish.css?v=20261016-midnight1"><script src="js/pmmotion.js?v=20261015-motion1" defer></script><link rel="stylesheet" href="css/pmstudio.css?v=20261016-midnight1"></head>
<body class="pm-launch-page">
	<main>
		<section class="content" aria-labelledby="page-title">
			<div class="brand"><span>DeraSoft</span></div>
			<h1 id="page-title">Quản lý dự án rõ ràng hơn.</h1>
			<p class="lead">Quản lý dự án, phân công công việc, ghi nhận giờ làm và theo dõi chi phí trên một hệ thống thống nhất.</p>
		</section>
		<aside class="status" aria-label="Trạng thái dự án">
			<div>
				<span class="status-label">Không gian làm việc</span>
				<h2>Sẵn sàng làm việc</h2>
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
