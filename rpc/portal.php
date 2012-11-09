<?php
/*
	门户接口
*/
define('APPTYPEID', 4);
define('CURSCRIPT', 'portal');

require './common.php';

//对缓冲区内容进行压缩
ob_start("ob_gzhandler");

require RPC_DIR . '/class/class_core.php';

$discuz = & discuz_core::instance();

$cachelist = array('comment', 'article', 'category');
$discuz->cachelist = $cachelist;
$discuz->init();

//定义允许访问的模块
$modarray = array('index', 'list', 'view', 'comment', 'ajax');

//定义默认模块
$mod = !in_array($_G['mod'], $modarray) ? 'index' : $_G['mod'];

define('CURMODULE', $_GET['mod']);

//检查门户功能是否开启
if(!$_G['setting']['portalstatus']) {
	$msg = rpclang('portal', 'portal_status_off');
	jsonexit("{\"message\":\"$msg\"}");
}

//包含进来相应模块代码
require RPC_DIR . '/module/portal/portal_'.$mod.'.php';

?>