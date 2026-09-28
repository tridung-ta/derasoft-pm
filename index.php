<?php
/*************************************************************************
Index page
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd
Last updated: 30/05/2012
Coder: Mai Minh
Reviewed by: Mai Minh (03/06/2025)
**************************************************************************/

$time_start = microtime(true);
error_reporting(9);
if (!defined('ROOT_PATH')) {
	define('ROOT_PATH', dirname(__FILE__).'/');
	
}
include_once(ROOT_PATH.'includes/constant.inc.php');
include_once(ROOT_PATH.'classes/security/boot.class.php');
$boots = new Boot();

# Redirect to maintenance page
if(MAINTENANCE) {
	header("location: /maintenance.html");	
	exit;
}

# Setting time zone
ini_set('date.timezone',TIME_ZONE);

# Initialize query count variable
$query_count = 0;

# Set the debug options
if(DEBUG && $_SERVER['REMOTE_ADDR'] == DEBUG_IP) {
	$debug_file = ROOT_PATH.'debug/'.DEBUG_IP.'.txt';
	file_put_contents($debug_file, "***** Start runtime: ".date("Y-m-d H:i:s")." *****\n", DEBUG_FILE_APPEND);
	$debugText = '';
	$time_start = microtime(true);
	error_reporting(E_ALL);
	// Keep diagnostics in the server logs/debug file without exposing paths,
	// database names or stack traces in the public response.
	ini_set('log_errors', TRUE);
	ini_set('display_errors', FALSE);
	ini_set('display_startup_errors', FALSE);
} else {
	error_reporting(0);
	ini_set('display_errors', FALSE);
	ini_set('display_startup_errors', FALSE);
}

# include
include_once(ROOT_PATH.'includes/config.inc.php');
include_once(ROOT_PATH.'classes/data/translator.class.php');
include_once(ROOT_PATH.'includes/functions.inc.php');
include_once(ROOT_PATH.'classes/database/mysql.class.php');
include_once(ROOT_PATH.'classes/template/smarty.class.php');
include_once(ROOT_PATH.'classes/http/request.class.php');
include_once(ROOT_PATH.'classes/http/url.class.php');
include_once(ROOT_PATH.'classes/dao/users.class.php');
include_once(ROOT_PATH.'classes/dao/estores.class.php');
include_once(ROOT_PATH.'classes/dao/languages.class.php');
include_once(ROOT_PATH.'classes/dao/customers.class.php');
include_once(ROOT_PATH.'classes/dao/currencies.class.php');
// include_once(ROOT_PATH.'truycap.php');

# Setting time zone
if(function_exists('date_default_timezone_set')) date_default_timezone_set(TIME_ZONE);

# Database connection
$db = new DB();
$url = new Url();

# Template engine
// $template_dir = array(ROOT_PATH.TEMPLATE_PATH.'/default/');
$template_dir = array(ROOT_PATH.TEMPLATE_PATH.'/default/');
$template = new Smarty;
$template->compile_check = TEMPLATE_COMPILE;
$template->debugging = TEMPLATE_DEBUG;

# Initialize some variables
$sort_key = '';
$sort_direction = '';
// $pageTitle = '';
// $pageKeywords = '';
// $pageDescription = '';
$defaultlogo = ECOMMERCE_PROTOCOL.DOMAIN."/templates/digitrust/img/logo/logo-top.webp";		

# E-store configuration
$templateFolder = 'standard/';
$userTemplate = 'standard';
$templateFile = 'index.tpl.html';

# HTTP Request manager
$editorialRouterVersion = '20260925-dynamic-articles';
if (!headers_sent()) header('X-Dera-Editorial-Router: '.$editorialRouterVersion);
$publicPath = '/' . trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
$editorialRoutes = [
	'/' => ['act' => 'editorialhome', 'slug' => 'home'],
	'/dang-nhap' => ['act' => 'login', 'slug' => 'dang-nhap'],
	'/dang-ky' => ['act' => 'signin', 'slug' => 'dang-ky'],
	'/login' => ['act' => 'login', 'slug' => 'login'],
	'/register' => ['act' => 'signin', 'slug' => 'register'],
	'/verify-user' => ['act' => 'verifyuser', 'slug' => 'verify-user'],
	'/logout' => ['act' => 'logout', 'slug' => 'logout'],
	'/huy-dang-ky' => ['act' => 'newsletterunsubscribe', 'slug' => 'unsubscribe'],
	'/unsubscribe' => ['act' => 'newsletterunsubscribe', 'slug' => 'unsubscribe'],
	'/khong-gian-doc' => ['act' => 'readinghub', 'slug' => 'reading-space'],
	'/reading-space' => ['act' => 'readinghub', 'slug' => 'reading-space'],
	'/thanh-toan/vnpay' => ['act' => 'vnpaycheckout', 'slug' => 'vnpay-checkout'],
	'/thanh-toan/vnpay-return' => ['act' => 'vnpayreturn', 'slug' => 'vnpay-return'],
	'/thanh-toan/vnpay-ipn' => ['act' => 'vnpayipn', 'slug' => 'vnpay-ipn'],
	'/van-tho' => ['act' => 'vantho', 'slug' => 'van-tho'],
	'/van-tho/tho' => ['act' => 'vantho', 'slug' => 'van-tho', 'category' => 'tho'],
	'/van-tho/van-xuoi' => ['act' => 'vantho', 'slug' => 'van-tho', 'category' => 'van-xuoi'],
	'/nghe-thuat' => ['act' => 'nghethuat', 'slug' => 'nghe-thuat'],
	'/nghe-thuat/am-nhac' => ['act' => 'nghethuat', 'slug' => 'nghe-thuat', 'category' => 'am-nhac'],
	'/nghe-thuat/my-thuat' => ['act' => 'nghethuat', 'slug' => 'nghe-thuat', 'category' => 'my-thuat'],
	'/nghe-thuat/san-khau-nghe-thuat' => ['act' => 'nghethuat', 'slug' => 'nghe-thuat', 'category' => 'san-khau-nghe-thuat'],
	'/nghe-thuat/van-hoa' => ['act' => 'nghethuat', 'slug' => 'nghe-thuat', 'category' => 'van-hoa'],
	'/tin-tuc' => ['act' => 'editorialnews', 'slug' => 'tin-tuc-moi'],
	'/video' => ['act' => 'video', 'slug' => 'video'],
	'/tim-kiem' => ['act' => 'editorialsearchv49', 'slug' => 'search'],
	'/mot-khoang-troi-trong-trang-sach' => ['act' => 'news_detail', 'slug' => 'mot-khoang-troi-trong-trang-sach'],
	'/cau-chuyen-ben-hien-nha' => ['act' => 'news_detail', 'slug' => 'cau-chuyen-ben-hien-nha'],
	'/a-story-by-the-veranda' => ['act' => 'news_detail', 'slug' => 'a-story-by-the-veranda'],
	'/wu-yan-xia-de-gu-shi' => ['act' => 'news_detail', 'slug' => 'wu-yan-xia-de-gu-shi'],
	'/am-nhac-va-nhip-dieu-cuoc-song' => ['act' => 'news_detail', 'slug' => 'am-nhac-va-nhip-dieu-cuoc-song'],
	'/sac-mau-trong-doi-song-duong-dai' => ['act' => 'news_detail', 'slug' => 'sac-mau-trong-doi-song-duong-dai'],
	'/san-khau-noi-cau-chuyen-duoc-thap-sang' => ['act' => 'news_detail', 'slug' => 'san-khau-noi-cau-chuyen-duoc-thap-sang'],
	'/giu-gin-gia-tri-van-hoa-trong-doi-song-moi' => ['act' => 'news_detail', 'slug' => 'giu-gin-gia-tri-van-hoa-trong-doi-song-moi'],
	'/khong-gian-van-hoa-nghe-thuat-chinh-thuc-ra-mat' => ['act' => 'news_detail', 'slug' => 'khong-gian-van-hoa-nghe-thuat-chinh-thuc-ra-mat'],
	'/doi-thoai-ve-van-hoc-nghe-thuat-va-doi-song' => ['act' => 'news_detail', 'slug' => 'doi-thoai-ve-van-hoc-nghe-thuat-va-doi-song'],
	'/video-gioi-thieu-khong-gian-van-hoa-nghe-thuat' => ['act' => 'news_detail', 'slug' => 'video-gioi-thieu-khong-gian-van-hoa-nghe-thuat'],
];
$routeWithoutLang = preg_replace('#^/(en|zh)(?=/|$)#', '', $publicPath);
$publicQuery = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY);
if ($publicQuery) {
	$publicParams = [];
	parse_str($publicQuery, $publicParams);
	foreach (['category', 'page', 'q', 'type', 'from', 'to', 'payment_error'] as $allowedPublicParam) {
		if (isset($publicParams[$allowedPublicParam]) && is_scalar($publicParams[$allowedPublicParam])) {
			$_GET[$allowedPublicParam] = trim((string)$publicParams[$allowedPublicParam]);
		}
	}
}
if (isset($editorialRoutes[$routeWithoutLang])) {
	$_GET['act'] = $editorialRoutes[$routeWithoutLang]['act'];
	$_GET['slug'] = $editorialRoutes[$routeWithoutLang]['slug'];
	if (isset($editorialRoutes[$routeWithoutLang]['category'])) {
		$_GET['category'] = $editorialRoutes[$routeWithoutLang]['category'];
	}
} elseif (preg_match('#^/(?:tac-gia|(?:en|zh)/author)/(\d+)$#', $publicPath, $authorRouteMatch)) {
	$_GET['act'] = 'editorialauthor';
	$_GET['slug'] = 'author';
	$_GET['author_id'] = (int)$authorRouteMatch[1];
} elseif (preg_match('#^/(?:chu-de|(?:en|zh)/tag)/([a-z0-9][a-z0-9-]{0,190})$#', $publicPath, $tagRouteMatch)) {
	$_GET['act'] = 'editorialtag';
	$_GET['slug'] = $tagRouteMatch[1];
} elseif (preg_match('#^/(?:en/|zh/)?([a-z0-9][a-z0-9-]{0,190})$#', $publicPath, $articleRouteMatch)) {
	// Article slugs are created dynamically by the CMS and must not require
	// a hard-coded route entry for every newly published story.
	$_GET['act'] = 'news_detail';
	$_GET['slug'] = $articleRouteMatch[1];
	$_REQUEST['act'] = 'news_detail';
	$_REQUEST['slug'] = $articleRouteMatch[1];
}
$request = new Request;
$op = $request->element('op'); 
$act = $request->element('act');
if (isset($editorialRoutes[$routeWithoutLang])) {
	$op = 'estore';
	$act = $editorialRoutes[$routeWithoutLang]['act'];
} elseif (isset($authorRouteMatch)) {
	$op = 'estore';
	$act = 'editorialauthor';
} elseif (isset($tagRouteMatch)) {
	$op = 'estore';
	$act = 'editorialtag';
} elseif (isset($articleRouteMatch)) {
	$op = 'estore';
	$act = 'news_detail';
}

# Bootstrap
$sId = $boots->checkBootstrap();
$sId = 1;

# Checking if this is an e-store.
if($sId) $op = 'estore';
if(!$op) $op = DEFAULT_OP;
if(!$act) $act = DEFAULT_ACT;


# Put this code any where in the modules
#foreach($addons->getAddonFromEvent('ORDER_NEW') as $addon) {include_once(ROOT_PATH."addons/$addon/addon.php");}

# Session manager
include_once(ROOT_PATH.'includes/session.inc.php');
# CSRF TOKEN
if(empty($_SESSION['csrf_token'])){
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$template->assign('csrf_token', $_SESSION['csrf_token']);
# Include action module
// echo 12345;
if (in_array($op,$ops)) include_once(ROOT_PATH.'modules/'.strtolower($op).'/main.module.php');
$full_header = 1;

# Some global variables
$template->assign('templatePath',TEMPLATE_PATH);
$template->assign('domain',DOMAIN);
$template->assign('host',$_SERVER['HTTP_HOST']);
// $template->assign("count", $count);
$template->assign('op',$op);
$template->assign('act',$act);
if($sort_key) $template->assign('sk',$sort_key);
if($sort_direction) $template->assign('sd',$sort_direction);
// $template->assign('pageTitle',$pageTitle);
// $template->assign('pageKeywords',$pageKeywords);
// $template->assign('pageDescription',$pageDescription);
$template->assign('defaultlogo',$defaultlogo);
$template->assign('full_header',$full_header);
if(isset($storeId)) $template->assign('storeId',$storeId);

# Display the web page
$template->template_dir = $template_dir;
$template->display($templateFile);
//var_dump(ROOT_PATH.'modules/'.strtolower($op).'/main.module.php');
# User log

$userId = 0;
$usertype = 0;
$username = 'Guest';
if(isset($_SESSION['userId']) && $_SESSION['userId'] && $userInfo) {$userId = $_SESSION['userId']; $username = $userInfo->getUsername(); $usertype = $userInfo->getType();}
if(isset($_SESSION['store_customerId']) && $_SESSION['store_customerId'] && $customerInfo) {$userId = $_SESSION['store_customerId']; $usertype = 4; $username = $customerInfo->getUsername();}
if($act != "logout") userLog($storeId,$userId,$username,$usertype,$_SERVER['REQUEST_URI']);
increaseHit($storeId);

if(DEBUG && $_SERVER['REMOTE_ADDR'] == DEBUG_IP) {	
	$debug_file = "debug/".DEBUG_IP.".txt";
	$time_end = microtime(true);
	$time = $time_end - $time_start;
	if(!isset($plus))	$plus = '';
	$debugText .= "* op-act-plus-slug-email: $op-$act-$plus-$slug<br />\n";
	$debugText .= "* Templates: ".print_r($template_dir,true)."<br />\n";
	$debugText .= "* Template file: ".$templateFile."<br />\n";
	$debugText .= "* Session: ".print_r($_SESSION,true)."<br />\n";
	// $debugText .= "* Last errors: ".print_r(error_get_last(),true)."<br />\n";
	$debugText .= "* Last errors: <br />\n";
	$debugText .= "* Queries: ".$query_count.'-'.memory_get_usage()." <br />\n";
	$debugText .= "* Execute time: ".$time."s <br />\n";
	// Security: keep diagnostics in the server-side debug log only.
	// Never render filesystem paths or session data in public HTML.
	
	# Write to debug file
	file_put_contents($debug_file, $debugText, FILE_APPEND);		
	file_put_contents($debug_file, "***** End runtime *****\n\n", FILE_APPEND);
}
?>
