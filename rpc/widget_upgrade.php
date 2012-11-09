<?php
/**
	appcan客户端升级
*/
require './common.php';
require RPC_DIR . '/class/class_core.php';

$discuz = & discuz_core::instance();
$discuz->init();

$version = $_G['gp_ver'];
$platform = $_G['gp_platform'];

if(empty($version) || !isset($platform)) return;

loadcache('zywxdata');
$config = unserialize($_G['cache']['zywxdata']);
$iphone_src = $config['iphone_src'];
$android_src = $config['android_src'];
$app_ver = $config['app_ver'];

if($app_ver > $version) {
		
	if($platform == '0') { //iphone
		$fileurl = $iphone_src;
	} elseif($platform == '1') { //android
		$fileurl = $android_src;
	}

	if(preg_match('/^http:\/\//', $fileurl)) {

		$dest = $GLOBALS['_G']['setting']['attachdir'].'./temp/'.urlencode($fileurl).'.attach';
		
		if(strpos($fileurl, $_G['siteurl']) === 0) {
			copy(str_replace($_G['siteurl'], DISCUZ_ROOT, $fileurl), $dest);
		} else {
			$data = get_url_contents($fileurl);
			file_put_contents($dest, $data);
		}
		
		$filesize = filesize($dest);
		if(file_exists($dest)) {
			unlink($dest);
		}
			
	} else {
		$filesize = filesize(DISCUZ_ROOT.'./source/plugin/zywx/'.$fileurl);
		$fileurl = $_G['siteurl'].'source/plugin/zywx/'.$fileurl;
	}
	
	if(empty($filesize)) exit;

	echo '<?xml version="1.0" encoding="utf-8" ?><results><updateFileName>discuz</updateFileName><updateFileUrl>'.$fileurl.'</updateFileUrl><fileSize>'.$filesize.'</fileSize><version>'.$app_ver.'</version></results>';
		
}

?>