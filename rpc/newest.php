<?php
/**
 * 最新
 * LICENSE Aushi Copyright
 *
 * @copyright  2011-2012 Bei Jing Zheng Yi Wireless
 * @version    $Id: \$
 * @since      File available since Release 1.0 -- 2011-8-16 ����10:21:32
 * @author 	mengbing880814@yahoo.com.cn
 */

define('APPTYPEID', 0);
define('CURSCRIPT', 'newest');

require './common.php';

//对缓冲区内容进行压缩
ob_start("ob_gzhandler");

require RPC_DIR . '/class/class_core.php';
require RPC_DIR . '/function/function_newest.php';

$discuz = & discuz_core::instance();

$modarray = array('index', 'img', 'thread', 'reply', 'dig', 'hot', 'top','blog','album','group');

$mod = ! in_array($discuz->var['mod'], $modarray) ? 'index' : $discuz->var['mod'];

define('CURMODULE', $mod);
$cachelist = array();
$discuz->cachelist = $cachelist;
$discuz->init();

$_G["zywy_curpage"] = 1;
$_G["zywy_totalpage"] = 1;

require RPC_DIR . '/module/newest/newest_' . $mod . '.php';
?>