<?php
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class plugin_zywx {
	
	function plugin_zywx() {
		global $_G;
		loadcache('zywxdata');
		$_G['cache']['zywxdata'] = unserialize($_G['cache']['zywxdata']);
	}
	
	//顶部宣传页链接	
	function global_cpnav_extra1(){
		global $_G;
		$link = "";
		
		//如果链接模式不为顶部导航，则返回空
		if($_G['cache']['zywxdata']['linklocation'] == '1'){
			$link = '<a href="'.$GLOBALS['_G']['siteurl'].'plugin.php?id=zywx:propagate" target="_blank">手机客户端</a>';
			if(CHARSET != 'utf-8') {
					$link = diconv($link, 'utf-8', CHARSET);
			}
		}
		return $link;	
	}
	
	//底部宣传页链接	
	function global_footerlink(){
		global $_G;
		$link = "";
		
		//如果链接模式不为底部导航，则返回空
		if($_G['cache']['zywxdata']['linklocation'] == '2'){
			
			$link = '<a href="'.$GLOBALS['_G']['siteurl'].'plugin.php?id=zywx:propagate" target="_blank">手机客户端</a>';
			if(CHARSET != 'utf-8') {
				$link = diconv($link, 'utf-8', CHARSET);
			}
		}
		
		return $link;	
	}
	
	//论坛首页右下角弹窗
	function global_header() {
		global $_G;

		if($_G['cache']['zywxdata']['ispopwin'] && !$_G['cookie']['zywx_nofocus']) {
			$lifetime = $_G['cache']['zywxdata']['popwintime'];
			$lifetime = $lifetime ? intval($lifetime) : 0;
			$str = file_get_contents(template('zywx:prompt'));
			
		}
		return $str;
   }
   
}


?>