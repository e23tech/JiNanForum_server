<?php 
/*
	检查是否有新版本
*/
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

include DISCUZ_ROOT.'./source/plugin/zywx/config.php';
$plugin_dir = str_replace("\\", '/', DISCUZ_ROOT).'source/plugin/';

if($_GET['operation'] == 'upgrade') {
	
	//检查插件目录是否可写
	if(!dir_writeable($plugin_dir) || !dir_writeable($plugin_dir.'zywx/')) {
		cpmsg('&#25265;&#27465;&#65292;&#27492;&#25554;&#20214;&#30446;&#24405;&#19981;&#21487;&#20889;&#65292;&#35831;&#37325;&#26032;&#35774;&#32622;&#30446;&#24405;&#26435;&#38480;','','error');
	}
	
	//最新版本号接口url
	$upgrade_url = ZYWX_PROXY."/index.php?m=curl&plugin_name=discuz&a=getNewestVersion";	
	
	
	//获取插件最新版本号
	$data = get_url_contents($upgrade_url);
	$data = json_decode($data);
	$newver = $data->version;
	
	if(empty($newver)) { //获取最新版本号出错
		cpmsg('&#25265;&#27465;&#65292;&#36828;&#31243;&#26381;&#21153;&#27809;&#26377;&#21709;&#24212;&#65292;&#35831;&#32852;&#31995;&#27492;&#25554;&#20214;&#24320;&#21457;&#21830;','','error');
	}
	
	//判断是否有新的版本
	if($newver <= $plugin['version']) {
		cpmsg('&#24744;&#20351;&#29992;&#30340;&#29256;&#26412;&#24050;&#32463;&#26159;&#26368;&#26032;&#29256;&#26412;&#65292;&#26080;&#38656;&#21319;&#32423;','','error');
	}
	
	$pluginarray['plugin']['version'] = $newver;
	$upgrade = 1;
}

$finish = TRUE;

?>