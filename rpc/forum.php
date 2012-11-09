<?php
/**
	论坛接口
*/

define('APPTYPEID', 2);
define('CURSCRIPT', 'forum');

require './common.php';

//对缓冲区内容进行压缩
ob_start("ob_gzhandler");

require RPC_DIR . '/class/class_core.php';

$discuz = & discuz_core::instance();

$modarray = array('ajax','announcement','attachment','forumdisplay',
	'group','image','index','medal','misc','modcp','notice','post','redirect',
	'relatekw','relatethread','rss','topicadmin','trade','viewthread','tag'
);

//定义允许访问的模块
$mod = !in_array($discuz->var['mod'], $modarray) ? 'index' : $discuz->var['mod'];

define('CURMODULE', $mod);
$cachelist = array();
if($discuz->var['mod'] == 'group') {
	$_G['basescript'] = 'group';
}

$discuz->init();

require_once libfile('function/forum');
loadforum();

require RPC_DIR . '/module/forum/forum_'.$mod.'.php';

?>