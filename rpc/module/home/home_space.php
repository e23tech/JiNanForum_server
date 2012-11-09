<?php
/**
 * 家园入口文件
 *
 * file_description
 *
 * LICENSE Aushi Copyright
 *
 * @copyright  2011-2012 Bei Jing Zheng Yi Wireless
 * @version    $Id: \$
 * @since      File available since Release 1.0 -- 2011-8-25 下午02:28:25
 * @author mxg<xiangguo302@gmail.com>
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

//如果没有登录，则不能进行下列操作
if(empty($_G['uid'])) {
	$msg = rpclang('member', 'to_login');
	jsonexit("{\"message\":\"$msg\", \"nologin\":\"1\",\"url\":\"../login.html\"}");
}

$uid = empty($_GET['uid']) ? 0 : intval($_GET['uid']);

if($_GET['username']) {
	$member = DB::fetch_first("SELECT uid FROM ".DB::table('common_member')." WHERE username='$_GET[username]' LIMIT 1");
	if(empty($member)) {
		$msg = lang('message', 'space_does_not_exist');
		jsonexit("{\"status\":\"0\",\"message\":\"$msg\"}");
		
	}
	$uid = $member['uid'];
}

$dos = array('index', 'doing', 'blog', 'album', 'friend', 'wall','home_dynamic',
	'notice', 'share', 'home', 'pm', 'videophoto', 'favorite',
	'thread', 'trade', 'poll', 'activity', 'debate', 'reward', 'profile', 'plugin');

$do = (!empty($_GET['do']) && in_array($_GET['do'], $dos))?$_GET['do']:'index';

if(in_array($do, array('home', 'doing', 'blog', 'album', 'share', 'wall'))) {
	if(!$_G['setting']['homestatus']) {
		$msg = lang('message', 'home_status_off');
		jsonexit("{\"status\":\"0\",\"message\":\"$msg\"}");
	}
} else {
	$_G['mnid'] = 'mn_common';
}

if(empty($uid) || in_array($do, array('notice', 'pm'))) $uid = $_G['uid'];

if($uid) {
	$space = getspace($uid);
	 unset($space['password']);
	if(empty($space)) {
		$msg = lang('message', 'space_does_not_exist');
		jsonexit("{\"status\":\"0\",\"message\":\"$msg\"}");
	}
}

if(empty($space)) {
	if(in_array($do, array('doing', 'blog', 'album', 'share', 'home', 'thread', 'trade', 'poll', 'activity', 'debate', 'reward', 'group'))) {
		$_GET['view'] = 'all';
		$space['uid'] = 0;
	} else {
		$msg = lang('message', 'login_before_enter_home');
		jsonexit("{\"status\":\"0\",\"message\":\"$msg\"}");
	}
} else {

	$navtitle = $space['username'];

	if($space['status'] == -1 && $_G['adminid'] != 1) {
		$msg = lang('message', 'space_has_been_locked');
		jsonexit("{\"status\":\"0\",\"message\":\"$msg\"}");
	}

	if(in_array($space['groupid'], array(4, 5, 6)) && ($_G['adminid'] != 1 && $space['uid'] != $_G['uid'])) {
		$_GET['do'] = $do = 'profile';
	}

	if($do != 'profile' && $do != 'index' && !ckprivacy($do, 'view')) {
		$_G['privacy'] = 1;
		$msg = rpclang('home', 'no_privilege_look');
		$msg = str_replace('%x', $space['username'], $msg);
		jsonexit("{\"status\":\"-2\",\"message\":\"$msg\",\"back\":\"1\", \"authorName\":\"$space[username]\"}");
		exit();
	}

	if(!$space['self'] && $_GET['view'] != 'eccredit') $_GET['view'] = 'me';

}

$diymode = 0;

$seccodecheck = $_G['setting']['seccodestatus'] & 4;
$secqaacheck = $_G['setting']['secqaa']['status'] & 2;

$jsonarr['uid'] = $_G['uid'];
if(!empty($_GET['uid'])){
	$jsonarr['authorName'] = DB::getOne("SELECT username FROM ".DB::table('common_member')." 
										 WHERE uid='$_G[gp_uid]'");
}
$jsonarr['space_uid'] = $space['uid'];

if(in_array($do, array('doing', 'blog', 'album', 'share', 'home','home_dynamic', 'thread', 'favorite','friend','notice','pm','profile','wall'))) 
{
	require_once RPC_DIR . '/include/space/space_'.$do.'.php';
} else {
	require_once libfile('space/'.$do, 'include');
}
