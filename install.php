<?php
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}


//插件编码没选正确，停止安装
if(CHARSET == 'gbk') {
	if(!preg_match('/GBK/', $_REQUEST['installtype'])) {
		plugin_uninstall();
		cpmsg('&#25265;&#27465;&#65292;&#32534;&#30721;&#36873;&#25321;&#38169;&#35823;&#65292;&#35831;&#37325;&#26032;&#36873;&#25321;&#27491;&#30830;&#30340;&#32534;&#30721;', '', 'error');
	}
} else if(CHARSET == 'utf-8') {
	if(!preg_match('/UTF8/', $_REQUEST['installtype'])) {
		plugin_uninstall();
		cpmsg('&#25265;&#27465;&#65292;&#32534;&#30721;&#36873;&#25321;&#38169;&#35823;&#65292;&#35831;&#37325;&#26032;&#36873;&#25321;&#27491;&#30830;&#30340;&#32534;&#30721;', '', 'error');
	}
}

if(is_intranet()) {
	plugin_uninstall();
	cpmsg('&#20869;&#32593;&#19981;&#33021;&#20351;&#29992;&#27492;&#25554;&#20214;', '', 'error');
}

$appkey = random(4).'enl3eHp5d3h6eXd4'.random(4);

include DISCUZ_ROOT.'./source/plugin/zywx/config.php';
$data = get_url_contents(ZYWX_PROXY.'/index.php?m=curl&plugin_name=discuz&a=guid&domain='.$_G['siteurl'].'&appkey='.$appkey);
$data = json_decode($data);
	
if($data->zywxid) {
	DB::query("replace into ".DB::table('common_setting')." set skey='zywxid' ,svalue='".$data->zywxid."'");
		
	if($data->zywxappkey) {
		DB::query("replace into ".DB::table('common_setting')." set skey='zywxappkey' ,svalue='".$data->zywxappkey."'");
	}
		
	if($data->zywxemail) {
		DB::query("replace into ".DB::table('common_setting')." set skey='zywxemail' ,svalue='".$data->zywxemail."'");
	}
	
	if($data->app_version) {
		DB::query("replace into ".DB::table('common_setting')." set skey='zywxversion' ,svalue='".$data->app_version."'");
	}
	
} elseif($data->msg) {
	plugin_uninstall();
	cpmsg(utf8togbk($data->msg), '', 'error');
} else {
	plugin_uninstall();
	cpmsg('
&#25265;&#27465;&#65292;&#36828;&#31243;&#26381;&#21153;&#22120;&#19981;&#21487;&#29992;&#65292;&#35831;&#32852;&#31995;&#27492;&#25554;&#20214;&#23448;&#26041;&#23458;&#26381;&#20154;&#21592;', '', 'error');
}


$sql = "
DROP TABLE IF EXISTS `".DB::table('zywx_useroperation')."`;
CREATE TABLE `".DB::table('zywx_useroperation')."` (
  `uid` INT( 11 ) NOT NULL ,
  `username` VARCHAR(100) NOT NULL,
  `phone_name` VARCHAR(100) NOT NULL,
  `latitude`   VARCHAR(30)  NOT NULL default '0',
  `longitude`   VARCHAR(30)  NOT NULL default '0',
  `allow_state` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `dateline` int(10) unsigned NOT NULL DEFAULT '0',
   KEY `uid` (`uid`),
   KEY `dateline` (`dateline`)
) ENGINE=MyISAM;

DROP TABLE IF EXISTS `".DB::table('zywx_useroperation_log')."`;
CREATE TABLE `".DB::table('zywx_useroperation_log')."` (
  `uid` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `phone_name` varchar(100) NOT NULL,
  `latitude` varchar(30) NOT NULL default '0',
  `longitude` varchar(30) NOT NULL default '0',
  `allow_state` tinyint(1) unsigned NOT NULL default '1',
  `dateline` int(10) unsigned NOT NULL default '0',
  KEY `uid` (`uid`),
  KEY `dateline` (`dateline`)
) ENGINE=MyISAM;

DROP TABLE IF EXISTS `".DB::table('zywx_forum_postfield')."`;
CREATE TABLE  `".DB::table('zywx_forum_postfield')."` (
 `pid` INT( 10 ) UNSIGNED NOT NULL DEFAULT  '0',
 `longitude` CHAR( 15 ) NOT NULL ,
 `latitude` CHAR( 15 ) NOT NULL ,
 `device` CHAR( 30 ) NOT NULL ,
 `address` VARCHAR( 255 ) NOT NULL,
 `dateline` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY ( `pid` ) 
) ENGINE = MYISAM;

DROP TABLE IF EXISTS `".DB::table('zywx_home_blogfield')."`;
CREATE TABLE  `".DB::table('zywx_home_blogfield')."` (
 `blogid` INT( 10 ) UNSIGNED NOT NULL DEFAULT  '0',
 `longitude` CHAR( 15 ) NOT NULL ,
 `latitude` CHAR( 15 ) NOT NULL ,
 `device` CHAR( 30 ) NOT NULL ,
 `address` VARCHAR( 255 ) NOT NULL,
 `dateline` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY ( `blogid` ) 
) ENGINE = MYISAM;
";

runquery($sql);

//###################相册表############################

if(!fieldexist("home_album", "isPhone")) {
	$sql = "ALTER TABLE  `".DB::table('home_album')."` 
			ADD  `isPhone` TINYINT( 1 ) NOT NULL DEFAULT  '0';";
	runquery($sql);		
}

$finish = TRUE;

function is_intranet() {
	return preg_match('/^(127|192|local)/', $_SERVER['HTTP_HOST']);
}

function fieldexist($table, $field) {
	$sql= "Describe ".DB::table($table)." $field";
	return DB::fetch(DB::query($sql));
}

function plugin_uninstall(){
	DB::query("DELETE FROM ".DB::table('common_plugin')." WHERE identifier='zywx'");
}

function utf8togbk($data) {
	if(CHARSET != 'utf-8') {
		$data = diconv($data, 'utf-8', CHARSET);
	}
	return $data;
}

?>