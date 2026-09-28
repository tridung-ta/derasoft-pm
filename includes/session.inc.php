<?php
/*************************************************************************
Session manager
----------------------------------------------------------------
DeraCMS 3.0 Project
Company: Derasoft Co., Ltd                                  
Email: info@derasoft.com                                    
Last updated: 01/06/2012
Coder: Mai Minh (http://maiminh.vnweblogs.com)
**************************************************************************/
error_reporting(9);
if (!defined( "ROOT_PATH" )) {
	define("ROOT_PATH", dirname(__FILE__)."/");
}
#session_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 43200,
        'cookie_secure' => true,
        'cookie_httponly' => true
    ]);
}
$sessId = session_id();
$userInfo = '';
$customerInfo = '';

#Get Store Info
$storeId = 0;
if($sId) {
	$storeId = $sId;
	$stores = new EStores();
	if(!$storeId) die('Invalid estore ID.');
	$estore = $stores->getObject($storeId);
	$template->assign('storeId',$storeId);
}

# Users object
$users = new Users($storeId);
$customers = new Customers($storeId);
if(isset($_SESSION['userId']) && $_SESSION['userId']) {
	$userInfo = $users->getObject($_SESSION['userId'],'id');
	$template->assign("authUser",$userInfo);
}
if(isset($_SESSION['store_customerId']) && $_SESSION['store_customerId']) {	
	$customerInfo = $customers->getObject($_SESSION['store_customerId'],'id');
	$template->assign("authCustomer",$customerInfo);
}

# Language manager
$messages = array();
$languages = new Languages($storeId);
# Check request first, then check session
$language = strtolower($request->element('lang')?$request->element('lang'):(isset($_SESSION['lang'])?$_SESSION['lang']:''));
$languageInfo = $language?$languages->getObject($language,'prefix'):$languages->getPrimaryLanguage();
# If we got an invalid prefix, so use the primary language
if(!$languageInfo) $languageInfo = $languages->getPrimaryLanguage();
# Set session variable to handle language
$language = $languageInfo->getPrefix();
$_SESSION['lang'] = $languageInfo->getPrefix();
$lang = $languageInfo->getPrefix();
$template->assign("lang",$languageInfo->getPrefix());
$template->assign("languageInfo",$languageInfo);
$template->assign('languageCombo',$languages->generateCombo($languageInfo->getId()));
include_once(ROOT_PATH.'languages/'.$languageInfo->getPrefix().'.php');
$template->assign('messages',$messages);
# Translate messages
$translator = new Translator($messages);
$template->assign('locale',$translator);

# Currency manager
$currencies = new Currencies($storeId);
# If language changes, change the currency
if($request->element('lang') == $languageInfo->getPrefix()) {
	$currencyInfo = $currencies->getObject($languageInfo->getCurrency());
	if(!$currencyInfo) $currencyInfo = $currencies->getPrimaryCurrency();
} else {
# Check request first, then check session	
	$currency = strtoupper($request->element('currency')?$request->element('currency'):(isset($_SESSION['currency'])?$_SESSION['currency']:''));
	$currencyInfo = $currency?$currencies->getObject($currency,'name'):$currencies->getPrimaryCurrency();
	if(!$currencyInfo) $currencyInfo = $currencies->getPrimaryCurrency();
}
# Set session variable to handle currency
$currency = $currencyInfo->getName();
$_SESSION['currency'] = $currencyInfo->getName();
$template->assign("currency",$currencyInfo->getName());
$template->assign("currencyInfo",$currencyInfo);
$template->assign('currencyCombo',$currencies->generateCombo($currencyInfo->getId()));

#sesion template
if($request->element('template')) $_SESSION['template'] = $request->element('template');
?>