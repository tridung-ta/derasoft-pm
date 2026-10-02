<?php
include_once(ROOT_PATH.'classes/security/pmaccess.class.php');

$pmAccess = new PmAccess($db, (int) $storeId, (int) $userInfo->getId());
$templateFile = 'pm.tpl.html';
$allowedSections = array('dashboard', 'profile', 'password', 'team');
$section = strtolower((string) $request->element('act'));
if (!in_array($section, $allowedSections, true)) {
	$section = 'dashboard';
}

$sectionPermissions = array(
	'dashboard' => 'pm.dashboard.view',
	'profile' => $_SERVER['REQUEST_METHOD'] === 'POST' ? 'pm.profile.update' : 'pm.profile.view',
	'password' => 'pm.password.update',
	'team' => 'pm.team.view',
);
requirePermission($sectionPermissions[$section]);

if (empty($_SESSION['pm_csrf_token'])) {
	$_SESSION['pm_csrf_token'] = bin2hex(random_bytes(32));
}

$notice = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$submittedToken = (string) $request->element('csrf_token');
	if (!hash_equals($_SESSION['pm_csrf_token'], $submittedToken)) {
		$error = 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang và thử lại.';
	} elseif ($section === 'profile') {
		$fullname = trim((string) $request->element('fullname'));
		$email = trim((string) $request->element('email'));
		$address = trim((string) $request->element('address'));
		$telephone = trim((string) $request->element('telephone'));

		if ($fullname === '') {
			$error = 'Vui lòng nhập họ và tên.';
		} elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$error = 'Địa chỉ email chưa đúng định dạng.';
		} elseif ($email !== '' && $users->checkDuplicate($email, 'email', "`id` <> '".(int) $userInfo->getId()."'")) {
			$error = 'Địa chỉ email đã được sử dụng bởi tài khoản khác.';
		} else {
			$updated = $users->updateData(array(
				'fullname' => $fullname,
				'email' => $email,
				'address' => $address,
				'tel' => $telephone,
			), $userInfo->getId());
			if ($updated) {
				$notice = 'Thông tin cá nhân đã được cập nhật.';
				$userInfo = $users->getObject($userInfo->getId(), 'id');
				$template->assign('authUser', $userInfo);
			} else {
				$error = 'Không thể cập nhật thông tin. Vui lòng thử lại.';
			}
		}
	} elseif ($section === 'password') {
		$currentPassword = (string) $request->element('current_password');
		$newPassword = (string) $request->element('new_password');
		$confirmPassword = (string) $request->element('confirm_password');

		if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
			$error = 'Vui lòng nhập đầy đủ các trường mật khẩu.';
		} elseif ($users->authenticateUser($userInfo->getUsername(), $currentPassword) != $userInfo->getId()) {
			$error = 'Mật khẩu hiện tại không đúng.';
		} elseif (strlen($newPassword) < 8) {
			$error = 'Mật khẩu mới phải có ít nhất 8 ký tự.';
		} elseif ($newPassword !== $confirmPassword) {
			$error = 'Mật khẩu xác nhận không khớp.';
		} elseif (hash_equals($currentPassword, $newPassword)) {
			$error = 'Mật khẩu mới phải khác mật khẩu hiện tại.';
		} elseif ($users->changePasswordSecure($userInfo->getId(), $newPassword)) {
			$notice = 'Mật khẩu đã được thay đổi.';
		} else {
			$error = 'Không thể đổi mật khẩu. Vui lòng thử lại.';
		}
	}
}

$teamMembers = array();
if ($pmAccess->hasPermission('pm.team.view')) {
	$teamMembers = $users->getObjects(1, '`status` <> 2', array('username' => 'ASC'), 200);
	if (!$teamMembers) {
		$teamMembers = array();
	}
}

$template->assign('pageTitle', 'DeraSoft PM');
$template->assign('section', $section);
$template->assign('csrfToken', $_SESSION['pm_csrf_token']);
$template->assign('notice', $notice);
$template->assign('error', $error);
$template->assign('teamMembers', $teamMembers);
$template->assign('teamCount', count($teamMembers));
$template->assign('canViewTeam', $pmAccess->hasPermission('pm.team.view'));
$template->assign('pmRoleCodes', $pmAccess->getRoleCodes());
$template->assign('profileUser', $userInfo);
$displayName = trim((string) $userInfo->getFullName());
$template->assign('displayName', $displayName !== '' ? $displayName : $userInfo->getUsername());
