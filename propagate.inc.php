<?php
include DISCUZ_ROOT.'./source/plugin/zywx/config.php';
$config = zy_loadcache('zywxdata', false);
$appkey = $_G['setting']['zywxappkey'];

if($_GET['download']) {
	download_package();
	exit();
} else {
	include template('common/header');
}

$appid = zy_loadcache('zywx_appid', false);
if(!$appid) {
	$i = get_url_contents(ZYWX_APPCAN."/plugin/getDownload.action?app_key=$appkey&pt=iPhone");
	$i = explode('|', $i);
	preg_match('/\/(\d+)\//s', $i[1], $match);

	if($match[1]) {
		$appid = $match[1];
		zy_savecache('zywx_appid', $appid);
	}
}

$downloadurl = "http://app.tx100.com/$appid/index.html";
$imagesrc = urlencode(ZYWX_PROXY.'/index.php?m=qrcode&d='.$downloadurl.'&e=H&s=6');
$thumbsrc = $_G['siteurl'].'source/plugin/zywx/rpc/forum.php?mod=image&src='.$imagesrc;

if($_GET['dl']) {
	dheader('Location:'.$downloadurl);
}

$iphone_src = get_local_package('iPhone');
if($iphone_src) {	
	$iphone_thumbsrc = urlencode(ZYWX_PROXY.'/index.php?m=qrcode&d='.$iphone_src.'&e=H&s=6');
	$iphone_thumbsrc = $_G['siteurl'].'source/plugin/zywx/rpc/forum.php?mod=image&src='.$iphone_thumbsrc;
} else {
	$iphone_thumbsrc = $thumbsrc;
}

$android_src = get_local_package('Android');
if($android_src) {	
	$android_thumbsrc = urlencode(ZYWX_PROXY.'/index.php?m=qrcode&d='.$android_src.'&e=H&s=6');
	$android_thumbsrc = $_G['siteurl'].'source/plugin/zywx/rpc/forum.php?mod=image&src='.$android_thumbsrc;
} else {
	$android_thumbsrc = $thumbsrc;
}

ob_start();
include template('zywx:propagate');
$body = ob_get_contents();		
ob_clean();						
$body = utf8togbk($body);
echo $body;

include template('common/footer');

function download_package() {
	global $_G, $config, $appkey;
	$key = array('iPhone' => 'iphone_src', 'Android' => 'android_src');
	$local_src = get_local_package($_GET['os']);

	if($local_src) {
		dheader('Location:'.$local_src);
	}

	$data = get_url_contents(ZYWX_APPCAN."/plugin/getDownload.action?app_key=$appkey&pt=$_GET[os]");
	$data = explode('|', $data);

	if($data[0]) {
		dheader('Location:'.$data[0]);
	} else {
		showmessage('&#25265;&#27465;&#65292;&#27809;&#26377;&#21253;&#19979;&#36733;');
	}
}

function get_local_package($os) {
	global $_G, $config;
	$key = array('iPhone' => 'iphone_src', 'Android' => 'android_src');
	$local_src = $config[$key[$os]];

	if($local_src) {
		if(preg_match('/^http:\/\//', $local_src)) {
			return $local_src;
		} else {
			if(file_exists(DISCUZ_ROOT.'source/plugin/zywx/'.$local_src)) {
				return $_G['siteurl'].'source/plugin/zywx/'.$local_src;
			}
		}
	}
}

function utf8togbk($data) {
	if(CHARSET != 'utf-8') {
		$data = iconv('utf-8', CHARSET."//IGNORE", $data);
	}
	return $data;
}

function zy_loadcache($name, $limit=true) {
	$cache = DB::fetch(DB::query('SELECT data, dateline FROM '.DB::table("common_syscache")." WHERE cname='$name' LIMIT 1"));
	if(empty($cache) || ($limit && TIMESTAMP > $cache['dateline'])) return;
	return unserialize($cache['data']);
}

function zy_savecache($name, $data, $life=604800) {
	DB::insert('common_syscache', array(
		'cname' => $name,
		'data' => serialize($data),
		'dateline' => (TIMESTAMP+$life)
	), false, true);
}

?>