<?php
/**
	公共文件
*/
error_reporting(E_ERROR);
//DISCUZ根目录
define('ROOT_DIR', str_replace("\\", '/', substr(dirname(__FILE__), 0, -23)) );
define('RPC_DIR', str_replace("\\", '/', dirname(__FILE__)) );

define('DISCUZ_OUTPUTED', 1);

if (!function_exists('json_decode')) { 
	include RPC_DIR.'/class/json.class.php';
	
	function json_encode($string) {
		$json = new Services_JSON();
		return $json->encode($string);
	}
	
	function json_decode($string) {
		$json = new Services_JSON();
		return $json->decode($string);
	}

}

?>