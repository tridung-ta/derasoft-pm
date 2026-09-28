<?php
header('Content-Type: text/plain');
/*************************************************************************
Ajax processing
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Email: info@derasoft.com                                    
Last updated: 15/07/2008
Coder: Mai Minh
**************************************************************************/
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(0);
if (!defined( "ROOT_PATH" )) {
	define("ROOT_PATH", dirname(__FILE__)."/");
}



include_once(ROOT_PATH.'includes/config.inc.php');
include_once(ROOT_PATH.'includes/constant.inc.php');
include_once(ROOT_PATH.'classes/database/mysql.class.php');
include_once(ROOT_PATH.'classes/http/request.class.php');

# Database connection
$db = new DB();

# HTTP Request manager
$request = new Request;
$op = $request->element("op");

# Template configuration
$templateFolder = "admin/";
$userTemplate = "admin";
$templateFile = "xml.html";
	
# Language manager
$lang = $request->element("lang");
if(!$lang) $lang = DEFAULT_LANGUAGE;
include_once(ROOT_PATH."languages/".$lang.".php");
include_once(ROOT_PATH."languages/admin/".$lang.".php");


# Action check
if(!$op) die("Error!");
include_once(ROOT_PATH."modules/ajax/".strtolower($op).".module.php");



# Close database connection
$db->close();
?>