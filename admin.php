<?php
/*************************************************************************
Admin index page
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Last updated: 03/06/2025
**************************************************************************/

$time_start = microtime(true);
//error_reporting(9);
error_reporting(E_ALL);
if (!defined('ROOT_PATH')) {
	define('ROOT_PATH', dirname(__FILE__).'/');
}
include_once(ROOT_PATH.'classes/security/boot.class.php');
$boots = new Boot();
include_once(ROOT_PATH.'includes/config.inc.php');
$aops[] = 'editorial'; // V48 Editorial Control Center
include_once(ROOT_PATH.'includes/constant.inc.php');
include_once(ROOT_PATH.'classes/data/translator.class.php');
include_once(ROOT_PATH.'includes/admin/functions.inc.php');
include_once(ROOT_PATH.'classes/database/mysql.class.php');
include_once(ROOT_PATH.'classes/template/smarty.class.php');
include_once(ROOT_PATH.'classes/http/request.class.php');
include_once(ROOT_PATH.'classes/http/url.class.php');
include_once(ROOT_PATH.'classes/dao/users.class.php');
include_once(ROOT_PATH.'classes/dao/estores.class.php');
include_once(ROOT_PATH.'classes/dao/trackings.class.php');
include_once(ROOT_PATH.'classes/dao/storeusers.class.php');
include_once(ROOT_PATH.'classes/dao/addons.class.php');
require_once 'vendor/autoload.php'; // Đường dẫn đến autoload.php của Composer

# Setting time zone
if(function_exists('date_default_timezone_set')) date_default_timezone_set(TIME_ZONE);

# Database connection
$db = new DB();

# Template engine
$template = new Smarty;
$template->compile_check = true;
$template->debugging = false;

# HTTP Request manager
$request = new Request;
$op = $request->element('op');
$act = $request->element('act');
$mod = $request->element('mod');

// var_dump($op);
// var_dump($act);
// var_dump($mod);
// die;

$site = $request->element('site');

# Template configuration
$templateFolder = 'admin/';
$userTemplate = 'admin';
$templateFile = 'index.tpl.html';
	
# Language manager
$lang = $request->element('lang');
if(!$lang) $lang = DEFAULT_ADMIN_LANGUAGE;
include_once(ROOT_PATH.'languages/admin/'.$lang.'.php');
$template->assign('amessages',$amessages);
$template->assign('lang',$lang);

# Translate messages
$translator = new Translator($amessages);
$template->assign('locale',$translator);

# Bootstrap
#$bootstrap = Boot::checkBootstrapLoaded($sCode);
$sCode='estore';

# Action check
if(!$op || !in_array($op,$aops)) $op = 'login';
if($op=='admin' && !$userInfo->isAdmin()) $op = DEFAULT_ADMIN_OP;

$addons = new Addons(1);
# Session manager
include_once(ROOT_PATH.'includes/admin/sessions.inc.php');

# Load module
include_once(ROOT_PATH.'modules/admin/'.$op.'.module.php');

# Global variable
$template->assign('aScript',ADMIN_SCRIPT);
$template->assign('domain',DOMAIN);

# Operations
if(isset($op)) $template->assign('op',$op);
if(isset($act)) $template->assign('act',$act);
if(isset($mod)) $template->assign('mod',$mod);
if(isset($storeId)) $template->assign('storeId',$storeId);

# Navigation bar
if(isset($topNav)) $template->assign('topNav',$topNav);

#echo $templateFolder.$templateFile;
# Display the web page
$template->assign('templatePath',TEMPLATE_PATH);
$template->assign('userTemplate',$userTemplate);
$template->display($templateFolder.$templateFile);

# Close database connection
#$db->close();

if(DEBUG && $_SERVER['REMOTE_ADDR'] == DEBUG_IP) {	
	$debug_file = "debug/".DEBUG_IP.".txt";
	$debugText = "";
	$time_end = microtime(true);
	$time = $time_end - $time_start;
	if(!isset($plus))	$plus = '';
	$debugText .= "* op-act-plus-lang: $op-$act-$plus-$lang<br />\n";
	$debugText .= "* Templates: ".print_r($templateFolder,true)."<br />\n";
	$debugText .= "* Template file: ".$templateFile."<br />\n";
	$debugText .= "* Session: ".print_r($_SESSION,true)."<br />\n";
	$debugText .= "* Last errors: ".print_r(error_get_last(),true)."<br />\n";
	$debugText .= "* Queries: ".$query_count.'-'.memory_get_usage()." <br />\n";
	$debugText .= "* Execute time: ".$time."s <br />\n";
	if(DEBUG_DISPLAY) echo $debugText;
	
	# Write to debug file
	file_put_contents($debug_file, $debugText, FILE_APPEND);		
	file_put_contents($debug_file, "***** End runtime *****\n\n", FILE_APPEND);
}
$time_end = microtime(true);
$time = $time_end - $time_start;
echo '<center>'.$time.'</center>';
?>
