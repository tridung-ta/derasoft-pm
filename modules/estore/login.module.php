<?php
$templateFile = 'login.tpl.html';
if (session_status() === PHP_SESSION_NONE) session_start();

$defaultHome = isset($publicHome) ? $publicHome : '/';
$next = isset($_GET['next']) && is_scalar($_GET['next']) ? trim((string)$_GET['next']) : $defaultHome;
if ($next === '' || $next[0] !== '/' || strpos($next, '//') === 0 || preg_match('#^[a-z][a-z0-9+.-]*:#i', $next)) $next = $defaultHome;

if (!empty($_SESSION['store_customerId'])) {
    header('Location: '.$next); exit;
}
$loginPath = $lang === 'en' ? '/en/login' : ($lang === 'zh' ? '/zh/login' : '/dang-nhap');
$registerPath = $lang === 'en' ? '/en/register' : ($lang === 'zh' ? '/zh/register' : '/dang-ky');
$template->assign('loginPath',$loginPath);
$template->assign('registerPath',$registerPath);
$template->assign('authNext',$next);
$template->assign('urlVi','/dang-nhap?next='.rawurlencode($next));
$template->assign('urlEn','/en/login?next='.rawurlencode($next));
$template->assign('urlZh','/zh/login?next='.rawurlencode($next));
$pageTitle = $lang === 'en' ? 'Sign in' : ($lang === 'zh' ? '登录' : 'Đăng nhập');
$template->assign('pageTitle',$pageTitle.' | Văn học & Nghệ thuật');
$template->assign('pageDescription',$pageTitle);
$template->assign('slugActive','account-login');
