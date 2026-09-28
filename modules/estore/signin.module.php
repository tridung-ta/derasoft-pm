<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$templateFile = 'signin.tpl.html';
$defaultHome = isset($publicHome) ? $publicHome : '/';
$next = isset($_GET['next']) && is_scalar($_GET['next']) ? trim((string)$_GET['next']) : $defaultHome;
if ($next === '' || $next[0] !== '/' || strpos($next, '//') === 0 || preg_match('#^[a-z][a-z0-9+.-]*:#i', $next)) $next = $defaultHome;
if (!empty($_SESSION['store_customerId'])) {
    header('Location: '.$next); exit;
}
$_SESSION['editorial_register_started'] = time();
$loginPath = $lang === 'en' ? '/en/login' : ($lang === 'zh' ? '/zh/login' : '/dang-nhap');
$registerPath = $lang === 'en' ? '/en/register' : ($lang === 'zh' ? '/zh/register' : '/dang-ky');
$template->assign('loginPath',$loginPath);
$template->assign('registerPath',$registerPath);
$template->assign('authNext',$next);
$template->assign('urlVi','/dang-ky?next='.rawurlencode($next));
$template->assign('urlEn','/en/register?next='.rawurlencode($next));
$template->assign('urlZh','/zh/register?next='.rawurlencode($next));
$pageTitle = $lang === 'en' ? 'Create account' : ($lang === 'zh' ? '创建账户' : 'Tạo tài khoản');
$template->assign('pageTitle',$pageTitle.' | Văn học & Nghệ thuật');
$template->assign('pageDescription',$pageTitle);
$template->assign('slugActive','account-register');
