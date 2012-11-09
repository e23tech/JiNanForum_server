<?php

error_reporting(E_ERROR);

if(!function_exists('json_decode')) { 
	include DISCUZ_ROOT.'./source/plugin/zywx/rpc/class/json.class.php';
	
	function json_encode($string) {
		$json = new Services_JSON();
		return $json->encode($string);
	}
	
	function json_decode($string) {
		$json = new Services_JSON();
		return $json->decode($string);
	}
}

function get_url_contents($url) {
	if(ini_get('allow_url_fopen')) {
		return file_get_contents($url);
	} elseif(function_exists('curl_init')) {
		$ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($ch);
        curl_close($ch);
		return($res);
	} else {
		return dfsockopen($url);
	}	
}
$channelCode = '044';
define('ZYWX_PATH', str_replace("\\", '/', dirname(__FILE__)));
define('ZYWX_URL', str_replace("\\", '/', $_G['siteurl']).'/source/plugin/zywx');
define('ZYWX_PROXY', 'http://te.tx100.com/proxyserver');
define('ZYWX_APPCAN', 'http://www.appcan.cn');
define("ZYWX_SPREAD", 'http://discuz.appcan.cn/tuiguang.html');
?>