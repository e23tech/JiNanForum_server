<?php

define('APPTYPEID', 3);
define('CURSCRIPT', 'group');

require './common.php';

//对缓冲区内容进行压缩
ob_start("ob_gzhandler");

require RPC_DIR . '/class/class_core.php';

$discuz = & discuz_core::instance();

$cachelist = array('grouptype', 'groupindex', 'diytemplatenamegroup');
$discuz->cachelist = $cachelist;
$discuz->init();

//群组功能是否关闭
if(!$_G['setting']['groupstatus']) {
	$msg = rpclang('forum', 'group_status_off');
	jsonexit("{\"message\":\"$msg\"}");
}

//定义允许访问的模块
$modarray = array('index', 'my', 'attentiongroup', 'ajax');

//定义默认模块
$mod = !in_array($_G['mod'], $modarray) ? 'index' : $_G['mod'];

define('CURMODULE', $mod);

//包含进来相应模块代码
require RPC_DIR . '/module/group/group_'.$mod.'.php';
?>