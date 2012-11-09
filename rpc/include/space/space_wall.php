<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: space_wall.php 16680 2010-09-13 03:01:08Z wangjinbo $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$act = $_G['gp_act'] ? $_G['gp_act'] : 'list';

if($act == 'list') {

	$perpage = 10;
	$perpage = mob_perpage($perpage);

	$page = empty($_GET['page'])?1:intval($_GET['page']);
	if($page<1) $page=1;
	$start = ($page-1)*$perpage;

	$cid = empty($_GET['cid'])?0:intval($_GET['cid']);
	$csql = $cid?"cid='$cid' AND":'';

	$list = array();
	$count = DB::result(DB::query("SELECT COUNT(*) FROM ".DB::table('home_comment')." WHERE $csql id='$space[uid]' AND idtype='uid'"),0);
	if($count) {
		$query = DB::query("SELECT * FROM ".DB::table('home_comment')." WHERE $csql id='$space[uid]' AND idtype='uid' ORDER BY dateline DESC LIMIT $start,$perpage");
		while ($value = DB::fetch($query)) {
			$value['dateline'] = dgmdate($value['dateline'],'u');
			$value['message'] = preg_replace("~src=\"(?!http:)(\w+)~i", 'src="'.getsiteurl().'\\1', $value['message']);
			$list[] = $value;
		}
	}

	$jsonarr['list'] = $list;
	$jsonarr['page'] = $multi;

	/*MB add start*/
	$jsonarr['zywy_curpage'] = $page;
	$jsonarr['zywy_totalpage'] = max(1, ceil($count / $perpage));
	$jsonarr['ucurl'] = $_G["setting"]['ucenterurl'];
	/*MB add end*/
	jsonexit($jsonarr);

} elseif($act == 'del') {
	
	$cid = intval($_GET['cid']);
	if(empty($cid)) {
		$msg = rpclang('forum', 'arg_error');
		jsonexit("{'msg':'$msg'}");
	}
	
	$row = DB::getRow("SELECT uid, authorid FROM ".DB::table('home_comment')." 
						WHERE cid='$cid'");
	if(in_array($_G['uid'], $row)) {
		DB::query("DELETE FROM ".DB::table('home_comment')." WHERE cid='$cid'");
		if(DB::affected_rows()) {
			jsonexit("{'status':'1'}");
		} else {
			jsonexit("{'status':'0'}");
		}
	} else {
		$msg = rpclang('forum', 'no_privilege');
		jsonexit("{'msg':'$msg'}");
	}
}

?>