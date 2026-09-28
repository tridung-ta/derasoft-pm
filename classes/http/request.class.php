<?php
/*************************************************************************
Class Request
----------------------------------------------------------------
DeraCMS 4.0 Project
Company: Derasoft Co., Ltd                                  
Last updated: 03/06/2025
Coder: Mai Minh
**************************************************************************/
class Request
{
	function __construct() {
	}    
    function element($name, $default = '')
    {
      $TEMP1_ = '';
      if(isset($_SERVER['REQUEST_METHOD']) and $_SERVER['REQUEST_METHOD']=='GET')
      {
         $TEMP1_ = (isset($_GET[$name])?$_GET[$name]:'');
      }
      else
      {
         $TEMP1_ = (isset($_POST[$name])?$_POST[$name]:'');
      }
      if($TEMP1_ == '') $TEMP1_ = $default;
      if(is_array($TEMP1_)) return $TEMP1_;
	  return trim($TEMP1_);
    }
    
    function script_name()
    {
      $TEMP1_ = '';
      $TEMP2_ = '';
      $TEMP1_ = $_SERVER['REQUEST_URI'];
      $TEMP2_ = $_SERVER['QUERY_STRING'];
      $TEMP1_ = str_replace($TEMP2_,'',$TEMP1_);
      $TEMP1_ = split ('[/-]', $TEMP1_);
      return $TEMP1_[count($TEMP1_)-1];
    }

    function script_name_()
    {
      $TEMP1_ = '';
      $TEMP1_= $_SERVER['SCRIPT_FILENAME'];
      $TEMP1_=split ('[/-]', $TEMP1_);
      return $TEMP1_[count($TEMP1_)-1];
    }
    
    function query()
    {
      $TEMP1_ = '';
      $TEMP2_ = '';

      if(isset($_SERVER['REQUEST_METHOD']) and $_SERVER['REQUEST_METHOD']=='GET')
      {
        for($i=0;$i<count(array_keys($_GET));$i++)
        {
             $TEMP1_ = array_keys($_GET);
             $TEMP1_= $TEMP1_[$i];
             global ${$TEMP1_};
             $this->{$TEMP1_}=$_GET[$TEMP1_];
             if($i<count(array_keys($_GET))-1)
               $TEMP2_=$TEMP2_.$TEMP1_.'='.$_GET[$TEMP1_].'&';
             else
               $TEMP2_=$TEMP2_.$TEMP1_.'='.$_GET[$TEMP1_];
        }
      }
      else
      {
        for($i=0;$i<count(array_keys($_POST));$i++)
        {
             $TEMP1_ = array_keys($_POST);
             $TEMP1_= $TEMP1_[$i];
             global ${$TEMP1_};
             $this->{$TEMP1_}=$_POST[$TEMP1_];
             if($i<count(array_keys($_POST))-1)
               $TEMP2_=$TEMP2_.$TEMP1_.'='.$_POST[$TEMP1_].'&';
             else
               $TEMP2_=$TEMP2_.$TEMP1_.'='.$_POST[$TEMP1_];

        }
      }
	  return $TEMP2_;
    }
	function subDomain(&$sCode)
	{
		global $db;
		$sCode = '';		
		$split = split("\.",$_SERVER['HTTP_HOST']);
		$subdomain = $split[0];
		if($subdomain == 'www') return 0;
		if(count($split) < 3) return 0;
		$result = $db->query("SELECT `subdomain` FROM ".DB_PREFIX."estores WHERE `subdomain`='".$subdomain."'");
		if(mysql_num_rows($result)) {
			$row = mysql_fetch_row($result);
			mysql_free_result($result);
			$sCode = $row[0];
			return $subdomain;
		}		
		return 0;	
	}	
	function customDomain(&$sCode)
	{
		global $db;
		$sCode = '';
		$host = $_SERVER['HTTP_HOST'];
		if(preg_match('/'.DOMAIN.'/',$host)) return ''; # Cannot be the domain of BiDo
		$clean_host = preg_replace('/^www\./','',$host);
		$result = $db->query("SELECT `subdomain` FROM ".DB_PREFIX."estores WHERE `domain`='".$host."' OR `domain`='".$clean_host."'");
		if(mysql_num_rows($result)) {
			$row = mysql_fetch_row($result);
			mysql_free_result($result);
			$sCode = $row[0];
			return $host;
		}
		return '';
	}
	

}
?>
