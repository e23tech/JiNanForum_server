<?php

/**
 * 上传头像
 */

define('IN_DISCUZ', true);
require './common.php';
require RPC_DIR . '/class/class_core.php';
$discuz = & discuz_core::instance();
$discuz->init();

loaducenter();
$uc_input = uc_api_input("uid=$_G[uid]");

if(empty($_G['uid'])) {
	exit('Failed');
}

require_once libfile('class/upload');
$upload = new discuz_upload();

if($upload->init($_FILES['Filedata'], 'temp') && $upload->save()) {
	
	require_once libfile('class/image');
	$image = new image;
	
	$bigavatarfile = 'temp/avatar_big_'.$_G['uid'].'.jpg';
	$middleavatarfile = 'temp/avatar_middle_'.$_G['uid'].'.jpg';
	$smallavatarfile = 'temp/avatar_small_'.$_G['uid'].'.jpg';
	
	$image->thumb($upload->attach['target'], $bigavatarfile, 200, 200, 1, 1);
	$image->thumb($upload->attach['target'], $middleavatarfile, 120, 120, 1, 1);
	$image->thumb($upload->attach['target'], $smallavatarfile, 48, 48, 1, 1);

	if(!file_exists($_G['setting']['attachdir'].'./'.$bigavatarfile)) {
		$bigavatarfile = 'temp/'.$upload->attach['attachment'];
	}
	
	if(!file_exists($_G['setting']['attachdir'].'./'.$middleavatarfile)) {
		$middleavatarfile = 'temp/'.$upload->attach['attachment'];
	}
	
	if(!file_exists($_G['setting']['attachdir'].'./'.$smallavatarfile)) {
		$smallavatarfile = 'temp/'.$upload->attach['attachment'];
	}
	
} else {
	exit('Failed');
}

$uc_url = UC_API."/index.php?m=user&inajax=1&a=rectavatar&appid=".UC_APPID."&input=".$uc_input."&agent=".md5($_SERVER['HTTP_USER_AGENT'])."&avatartype=virtual";

$post_data = array (
	"avatar1" => flashdata_encode(file_get_contents($_G['setting']['attachdir'].'./'.$bigavatarfile)),
	"avatar2" => flashdata_encode(file_get_contents($_G['setting']['attachdir'].'./'.$middleavatarfile)),
	"avatar3" => flashdata_encode(file_get_contents($_G['setting']['attachdir'].'./'.$smallavatarfile)),
	"urlReaderTS" => TIEMSTAMP
);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $uc_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
$output = curl_exec($ch);
curl_close($ch);

@unlink($upload->attach['target']);
@unlink($_G['setting']['attachdir'].'./'.$bigavatarfile);
@unlink($_G['setting']['attachdir'].'./'.$middleavatarfile);
@unlink($_G['setting']['attachdir'].'./'.$smallavatarfile);

if(preg_match('/success="1"/', $output)) {
	exit("Success");
} else {
	exit('Failed');
}


/** 
 * 模拟UCENTER FLASH数据的编码过程 
 * @param string $s 加密前的字符串 
 * @return string 加密后的字符串 
 */ 
function flashdata_encode($s){ 	
	$_loc_2 = ""; 
	for($i = 0; $i < strlen($s); $i++){ 
		$_loc_3 = strtoupper(toHexNum(ord($s[$i])));//转换成ascii码，再转换成16进制数据，然后转换成大写 
		$_loc_2 .= $_loc_3;//字符串连接 
	} 
	return $_loc_2; 
} 

/** 
 * 转换成ascii码，再转换成16进制数据,假如不足两位0补足。 
 * @param integer $param1 10进制数据 
 * return string 
 */ 
function toHexNum($param1) {
	return ($param1 <= 15 ? ("0" . strval(dechex($param1))) :strval(dechex($param1))); 
}

?>