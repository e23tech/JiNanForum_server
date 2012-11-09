<?php
 
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$nocache = !empty($_G['gp_nocache']) ? 1 : 0;
$type = !empty($_G['gp_type']) ? $_G['gp_type'] : '1';
$width = $_G['gp_width'] ? intval($_G['gp_width']) : 100;
$height = $_G['gp_height'] ? intval($_G['gp_height']) : 100;
$src = str_replace('\\', '', stripslashes($_G['gp_src']));
$name = $width.'-'.$height.'-'.urlencode($_GET['src']);

if(mt_rand(0, 1000) == 20) {
	clean_attach_cachedir($_G['setting']['attachdir'].'temp');
}

if(preg_match('/gif$/', $src)){
	dheader('location: '.$src);
}

$thumbfile = 'temp/'.$name;
$parse = parse_url($_G['setting']['attachurl']);
$attachurl = !isset($parse['host']) ? $_G['siteurl'].$_G['setting']['attachurl'] : $_G['setting']['attachurl'];

header("Content-type: image/jpeg");

if(!preg_match("#".ROOT_DIR."#", $_G['setting']['attachdir'])) {
	$dest_file = ROOT_DIR.'/'.$_G['setting']['attachdir'].$thumbfile;
} else {
	$dest_file = $_G['setting']['attachdir'].$thumbfile;
}

if(!$nocache) {
	if(file_exists($_G['setting']['attachdir'].$thumbfile)) {	
		readfile($dest_file);
		exit();
	}
}

include RPC_DIR . '/class/class_imagetool.php';
$img = new imagetool;

if($img->thumb($src, $dest_file, $width, $height)) {
	readfile($dest_file);
} else {
	if($img->mime) {
		header('location: '.$src);
	} else {
		readfile($_G['siteurl'].'static/image/common/none.gif');
	}
}

function clean_attach_cachedir($dir) {
	if (is_dir($dir)) {
 		$dh=opendir($dir);
	    while (false !== ( $file = readdir ($dh))) {
			if($file!="." && $file!="..") { 
				  $fullpath=$dir."/".$file;
				  if(!is_dir($fullpath)) {
					  unlink($fullpath);
				  } else {
					  clean_attach_cachedir($fullpath);
				  }
			}  
		}
   		closedir($dh);
	}
}

?>