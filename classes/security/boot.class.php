<?php
/*************************************************************************
Class Boot
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Last updated:03/06/2025 
Coder: Mai Minh 
**************************************************************************/
define('BOOTSTRAP', 'sCode');
define('DEFAULT_KEY', 'CodeFromDerasoftVN');
include_once(ROOT_PATH.'license/license.inc.php');

class Boot
{
	function __construct() {
	
	}
	private function getLocalStoreId()
	{
		$host = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
		if(!in_array($host, array('localhost', '127.0.0.1', '::1'), true)) return 0;

		$storeId = getenv('DERACMS_LOCAL_STORE_ID');
		return ($storeId !== false && ctype_digit($storeId) && (int)$storeId > 0)
			? (int)$storeId
			: 0;
	}
	private function isLicensedHost($host)
	{
		global $license_domain;
		return isset($license_domain)
			&& is_array($license_domain)
			&& in_array($host, $license_domain, true);
	}
	function checkBootstrapLoaded()
	{
		global $db;
		
		$host = $_SERVER['HTTP_HOST'];
		$clean_host = preg_replace('/^www\./','',$host);
		$localStoreId = $this->getLocalStoreId();
		if($localStoreId) {
			$result = $db->query("SELECT `subdomain` FROM ".DB_PREFIX."estores WHERE `id`='".$localStoreId."'");
			if($db->numRows($result)) {
				$row = $db->fetchRow($result);
				$db->freeResult($result);
				return $row[0];
			}
			return '';
		}
		if(!$this->isLicensedHost($host)) {
			header('location: http://derasoft.com/license.html');
			return '';
		}
		$result = $db->query("SELECT `subdomain` FROM ".DB_PREFIX."estores WHERE `domain`='".$host."' OR `domain`='".$clean_host."'");
		if($db->numRows($result)) {
			$row = $db->fetchRow($result);
			$db->freeResult($result);
			return $row[0];
		}
		return '';
	}
	function checkBootstrap()
	{
		global $db;
		$sCode = '';
		$host = $_SERVER['HTTP_HOST'];
		$clean_host = preg_replace('/^www\./','',$host);
		$localStoreId = $this->getLocalStoreId();
		if($localStoreId) {
			$result = $db->query("SELECT `id` FROM ".DB_PREFIX."estores WHERE `id`='".$localStoreId."'");
			if($db->numRows($result)) {
				$row = $db->fetchRow($result);
				$db->freeResult($result);
				return $row[0];
			}
			return '';
		}
		if(!$this->isLicensedHost($host)) {
			header('location: http://derasoft.com/license.html');
			return '';
		}
		$result = $db->query("SELECT `id` FROM ".DB_PREFIX."estores WHERE `domain`='".$host."' OR `domain`='".$clean_host."'");
		if($db->numRows($result)) {
			$row = $db->fetchRow($result);
			$db->freeResult($result);
			return $row[0];
		}
		return '';
	}
	function encrypt($string, $key = DEFAULT_KEY) { 
		$result = ''; 
		for($i=0; $i<strlen($string); $i++) { 
			$char = substr($string, $i, 1); 
			$keychar = substr($key, ($i % strlen($key))-1, 1); 
			$char = chr(ord($char)+ord($keychar)); 
			$result.=$char; 
		}
		return base64_encode($result); 
	}
	function decrypt($string, $key = DEFAULT_KEY) { 
		$result = ''; 
		$string = base64_decode($string);
		for($i=0; $i<strlen($string); $i++) { 
			$char = substr($string, $i, 1); 
			$keychar = substr($key, ($i % strlen($key))-1, 1); 
			$char = chr(ord($char)-ord($keychar)); 
			$result.=$char; 
		}	
		return $result; 
	}
}
?>
