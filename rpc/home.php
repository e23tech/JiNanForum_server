<?php
/**
 * home
 *
 * file_description
 *
 * LICENSE Aushi Copyright
 *
 * @copyright  2011-2012 Bei Jing Zheng Yi Wireless
 * @version    $Id: \$
 * @since      File available since Release 1.0 -- 2011-8-15 上午11:24:52
 * @author <yjt>yinjitao2001@163.com
 */

define('APPTYPEID', 1);
define('CURSCRIPT', 'home');

require './common.php';

//对缓冲区内容进行压缩
ob_start("ob_gzhandler");

require RPC_DIR . '/class/class_core.php';
require ROOT_DIR . '/source/function/function_home.php';

$discuz = & discuz_core::instance();

$cachelist = array('magic','userapp','usergroups', 'diytemplatenamehome');
$discuz->cachelist = $cachelist;
$discuz->init();
//jsonexit("{\"message\":\"".$_G['cookie']['auth']."\", \"nologin\":\"1\",\"url\":\"../login.html\"}");
$space = array();
//print_r($_G['cookie']['auth']);
$mod = getgpc('mod');
if(!in_array($mod, array('space', 'spacecp', 'misc', 'magic', 'editor', 'invite', 'task', 'medal', 'rss','cp'))) {
	$mod = 'space';
	$_GET['do'] = 'home';
}

if($mod == 'space' && ((empty($_GET['do']) || $_GET['do'] == 'index') && ($_G['inajax'] || !$_G['setting']['homestatus']))) {
	$_GET['do'] = 'profile';
}

define('CURMODULE', $mod);

require RPC_DIR . '/module/home/home_'.$mod.'.php';

?>