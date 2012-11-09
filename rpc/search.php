<?php

/**
 * 搜索入口
 *
 * file_description
 *
 * LICENSE Aushi Copyright
 *
 * @copyright  2011-2012 Bei Jing Zheng Yi Wireless
 * @version    $Id: \$
 * @since      File available since Release 1.0 -- 2011-8-17 下午02:27:35
 * @author 	LLX
 */
define('APPTYPEID', 0);
define('CURSCRIPT', 'search');

require './common.php';

//对缓冲区内容进行压缩
ob_start("ob_gzhandler");

require RPC_DIR . '/class/class_core.php';
$discuz = & discuz_core::instance();
$discuz->init();

getgpc('uid') && $_G['uid'] = getgpc('uid');

$mod = getgpc('mod','G');
define('CURMODULE', $mod);

require libfile('function/search');
runhooks();

if(getgpc('category','G') == 'title')
{
	$searchtypers = DB::result_first("SELECT svalue FROM ".DB::table('common_setting')." WHERE skey='search'");
	$searchtypers = unserialize($searchtypers);
	$searchtypes = array();
	if(!empty($searchtypers) && is_array($searchtypers))
	{
		foreach($searchtypers as $k=>$v)
		{
			if(isset($v['status']) && $v['status'] ==1 && $k !='portal')
			{
				$searchtypes[$k] = rpclang("search", $k);
			}
		}
	}
	$rsArray['type'] = $searchtypes;
	$rsArray['hotkeywords'] = $_G['setting']['srchhotkeywords']?$_G['setting']['srchhotkeywords']:array();
	jsonexit($rsArray);
}else 
{
	require RPC_DIR . '/module/search/search_'.$mod.'.php';
}