<?php

/**
 * 会员登录入口文件
 *
 * file_description
 *
 * LICENSE Aushi Copyright
 *
 * @copyright  2011-2012 Bei Jing Zheng Yi Wireless
 * @version    $Id: \$
 * @since      File available since Release 1.0 -- 2011-8-15 下午03:46:00
 * @author     LLX
 */

define('APPTYPEID',0);
define('CURSCRIPT','member');

require './common.php';

require RPC_DIR . '/class/class_core.php';
$discuz = & discuz_core::instance();
$discuz->init();

$mod = getgpc('mod','G');
define('CURMODULE', $mod);

require libfile('function/member');
require libfile('class/member');

require RPC_DIR . '/module/member/member_'.$mod.'.php';

?>