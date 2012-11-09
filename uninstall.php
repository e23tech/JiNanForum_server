<?php
/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: uninstall.php 20324 2011-02-21 09:35:00Z zhengqingpeng $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

include DISCUZ_ROOT.'./source/plugin/zywx/config.php';

//删除广告位
$config = DB::fetch(DB::query('SELECT data, dateline FROM '.DB::table("common_syscache")." WHERE cname='zywxdata' LIMIT 1"));
$config = unserialize($config['data']);

if($config['pullscreenid']) {
	runquery("DELETE FROM ".DB::table('common_advertisement')." 
		  	  WHERE advid='$config[pullscreenid]'");
}
updatecache('advs');

//删除内容设置保存值
runquery("DELETE FROM ".DB::table('common_syscache')." 
		  WHERE cname='zywxdata'");
session_start();		  
session_unset();

$sql = "
DROP TABLE IF EXISTS `".DB::table('zywx_useroperation')."`;
DROP TABLE IF EXISTS `".DB::table('zywx_useroperation_log')."`;
DROP TABLE IF EXISTS `".DB::table('zywx_forum_postfield')."`;
DROP TABLE IF EXISTS `".DB::table('zywx_home_blogfield')."`;
DELETE FROM `".DB::table('common_setting')."` WHERE skey IN('zywxversion', 'zywxemail', 'zywxid', 'zywxappkey', 'zywx_version', 'zywx_email');
";
runquery($sql);

//平台记录插件卸载状态
dfsockopen(ZYWX_APPCAN.'/plugin/installStatus.action?app_key='.$_G['setting']['zywxappkey'].'&status=0');

$finish = TRUE;


function fieldexist($table, $field) {
	$sql= "Describe ".DB::table($table)." $field";
	return DB::fetch(DB::query($sql));
}

?>