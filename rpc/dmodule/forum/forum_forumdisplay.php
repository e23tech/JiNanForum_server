<?php
/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: forum_forumdisplay.php 22941 2011-06-07 01:17:43Z monkey $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$_G['tpp'] = 10;

require_once libfile('function/forumlist');

$_G['action']['fid'] = $_G['fid'];

$_G['gp_specialtype'] = isset($_G['gp_specialtype']) ? $_G['gp_specialtype'] : '';
$_G['gp_dateline'] = isset($_G['gp_dateline']) ? intval($_G['gp_dateline']) : 0;
$_G['gp_digest'] = isset($_G['gp_digest']) ? 1 : '';
$_G['gp_archiveid'] = isset($_G['gp_archiveid']) ? intval($_G['gp_archiveid']) : 0;
$_G['forum']['name'] = strip_tags($_G['forum']['name']) ? strip_tags($_G['forum']['name']) : $_G['forum']['name'];
$_G['forum']['extra'] = unserialize($_G['forum']['extra']);

$threadtable_info = !empty($_G['cache']['threadtable_info']) ? $_G['cache']['threadtable_info'] : array();

$seotype = 'threadlist';
if($_G['forum']['status'] == 3) {
	/*$navtitle = get_title_page($_G['forum']['name'], $_G['page']).' - '.$_G['setting']['navs'][3]['navname'];
	$metakeywords = $_G['forum']['metakeywords'];
	$metadescription = $_G['forum']['description'];
	$_G['seokeywords'] = $_G['setting']['seokeywords']['group'];
	$_G['seodescription'] = $_G['setting']['seodescription']['group'];
	$action = getgpc('action') ? $_G['gp_action'] : 'list';*/
	require_once libfile('function/group');
	$status = groupperm($_G['forum'], $_G['uid']);
	if($status == -1) {
		$msg = rpclang('forum', 'forum_not_group');	
		jsonexit("{\"message\":\"$msg\", \"back\":\"1\"}");	
	} elseif($status == 1) {
		$msg = rpclang('forum', 'forum_group_status_off');	
		jsonexit("{\"message\":\"$msg\",\"back\":\"1\"}");	
	} elseif($status == 2) {
		$msg = rpclang('forum', 'forum_group_noallowed');	
		jsonexit("{\"message\":\"$msg\", \"back\":\"1\"}");	
	}elseif($status == 3) {
		$msg = rpclang('forum', 'forum_group_moderated');	
		jsonexit("{\"message\":\"$msg\",\"back\":\"1\"}");	
	}
	//$_G['forum']['icon'] = get_groupimg($_G['forum']['icon'], 'icon');
	//$_G['grouptypeid'] = $_G['forum']['fup'];
	$_G['forum']['dateline'] = dgmdate($_G['forum']['dateline'], 'd');

	/*$nav = get_groupnav($_G['forum']);
	$groupnav = $nav['nav'];
	$onlinemember = grouponline($_G['fid']);
	$groupmanagers = $_G['forum']['moderators'];
	$groupcache = getgroupcache($_G['fid'], array('replies', 'views', 'digest', 'lastpost', 'ranking', 'activityuser', 'newuserlist'));
	$seotype = 'grouppage';
	$seodata['first'] = $nav['first']['name'];
	$seodata['second'] = $nav['second']['name'];
	$seodata['gdes'] = $_G['forum']['description'];
	$forumseoset = array();*/
}

if($_G['forum']['viewperm'] && !forumperm($_G['forum']['viewperm']) && !$_G['forum']['allowview']) {
	$msg = rpclang('forum', 'viewperm_none_nopermission');	
	jsonexit("{\"message\":\"$msg\",\"back\":\"1\"}");
} elseif($_G['forum']['formulaperm']) {
	formulaperm($_G['forum']['formulaperm']);
}

//需输入密码进入版块
if($_G['forum']['password']) {
	if($_G['gp_act'] == 'pwverify') { 
		if($_G['gp_pw'] != $_G['forum']['password']) {
			$msg = rpclang('forum', 'forum_passwd_incorrect');	
			jsonexit("{\"message\":\"$msg\"}");
		} else {
			dsetcookie('fidpw'.$_G['fid'], $_G['gp_pw']);
		}
	} elseif($_G['forum']['password'] != $_G['cookie']['fidpw'.$_G['fid']]) {
		$msg = rpclang('forum', 'input_visit_password');	
		jsonexit("{\"input_visit_password\":\"$msg\"}");
	}
}

$threadtableids = !empty($_G['cache']['threadtableids']) ? $_G['cache']['threadtableids'] : array();

$threadtable = $_G['gp_archiveid'] && in_array($_G['gp_archiveid'], $threadtableids) ? "forum_thread_{$_G['gp_archiveid']}" : 'forum_thread';

if($_G['setting']['allowmoderatingthread'] && $_G['uid']) {
	$threadmodcount = DB::result_first("SELECT COUNT(*) FROM ".DB::table($threadtable)." WHERE fid='{$_G['fid']}' AND displayorder='-2' AND authorid='{$_G['uid']}'");
}

$page = $_G['setting']['threadmaxpages'] && $page > $_G['setting']['threadmaxpages'] ? 1 : $_G['page'];
$start_limit = ($page - 1) * $_G['tpp'];

if(!empty($_G['gp_orderby']) && in_array($_G['gp_orderby'], array('lastpost', 'dateline', 'replies', 'views', 'recommends', 'heats'))) {
	$forumdisplayadd['orderby'] .= '&orderby='.$_G['gp_orderby'];
} else {
	$_G['gp_orderby'] = isset($_G['cache']['forums'][$_G['fid']]['orderby']) ? $_G['cache']['forums'][$_G['fid']]['orderby'] : 'lastpost';
}

$_G['gp_ascdesc'] = isset($_G['cache']['forums'][$_G['fid']]['ascdesc']) ? $_G['cache']['forums'][$_G['fid']]['ascdesc'] : 'DESC';

$check = array();

if($_G['forum']['threadsorts']['types'] && $sortoptionarray && ($_G['gp_searchoption'] || $_G['gp_searchsort'])) {
	$sortid = intval($_G['gp_sortid']);

	if($_G['gp_searchoption']){
		$forumdisplayadd['page'] = '&sortid='.$sortid;
		foreach($_G['gp_searchoption'] as $optionid => $option) {
			$identifier = $sortoptionarray[$sortid][$optionid]['identifier'];
			$forumdisplayadd['page'] .= $option['value'] ? "&searchoption[$optionid][value]=$option[value]&searchoption[$optionid][type]=$option[type]" : '';
		}
	}

	if($searchsorttids = sortsearch($_G['gp_sortid'], $sortoptionarray, $_G['gp_searchoption'], $selectadd, $_G['fid'])) {
		$filteradd .= "AND t.tid IN (".dimplode($searchsorttids).")";
	}
}

$fidsql = '';
if($_G['forum']['relatedgroup']) {
	$relatedgroup = explode(',', $_G['forum']['relatedgroup']);
	$relatedgroup[] = $_G['fid'];
	$fidsql = " t.fid IN(".dimplode($relatedgroup).")";
} else {
	$fidsql = " t.fid='{$_G['fid']}'";
}

if(!empty($_G['gp_typeid']) && !empty($_G['forum']['threadtypes']['types'][$_G['gp_typeid']])) {
	$filteradd = " AND typeid='$_G[gp_typeid]'";	
}

/*
if(empty($filter) && empty($_G['gp_sortid']) && empty($_G['gp_archiveid']) && empty($_G['forum']['archive']) && empty($_G['forum']['relatedgroup'])) {
	$_G['forum_threadcount'] = $_G['forum']['threads'];
} else {
	$indexadd = '';
	if(strexists($filteradd, "t.digest>'0'")) {
		$indexadd = " FORCE INDEX (digest) ";
	}*/
	$_G['forum_threadcount'] = DB::result_first("SELECT COUNT(*) FROM ".DB::table($threadtable)." t $indexadd WHERE $fidsql $filteradd AND t.displayorder>='0'");
//}

$_G['forum_threadcount'] += $filterbool ? 0 : $stickycount;
$forumdisplayadd['page'] = !empty($forumdisplayadd['page']) ? $forumdisplayadd['page'] : '';
$multipage = multi($_G['forum_threadcount'], $_G['tpp'], $page, "?fid=$_G[fid]".($multiadd ? '&'.implode('&', $multiadd) : '')."$multipage_archive", $_G['setting']['threadmaxpages']);

$separatepos = 0;
$_G['forum_threadlist'] = $threadids = array();
$_G['forum_colorarray'] = array('', '#EE1B2E', '#EE5023', '#996600', '#3C9D40', '#2897C5', '#2B65B7', '#8F2A90', '#EC1282');

if($_G['uid']) {
	//版主
	$moderators = DB::getOne("SELECT moderators FROM ".DB::table('forum_forumfield')." 
							  WHERE fid='".$_G['fid']."'");
	if($moderators) {
		$moderators = explode("\t", $moderators);
	}						  
	
	//管理员

	$adminid = DB::getOne("SELECT adminid FROM ".DB::table('common_member')." 
						   WHERE uid='".$_G['uid']."'");
	  
	if(($_G['username'] && in_array($_G['username'], $moderators)) || $adminid) {
		$displayorderadd = 't.displayorder IN (0, -2)';
	} else {
		$displayorderadd = 't.displayorder=0';
	}					  
} else {
	$displayorderadd = 't.displayorder=0';
}

/*
3，全局置顶的帖子
2，区域置顶的帖子
1，本版块置顶的帖子
0，表示正常帖子
-1，被删除的帖子
-2，待审核的帖子
*/

if(($start_limit && $start_limit > $stickycount) || !$stickycount || $filterbool) {
	$indexadd = '';
	if(strexists($filteradd, "t.digest>'0'")) {
		$indexadd = " FORCE INDEX (digest) ";
	}
	$querysticky = '';
	$query = DB::query("SELECT tid, fid, author, authorid, displayorder,  subject,  lastpost, views, replies, digest, attachment, closed  FROM ".DB::table($threadtable)." t $indexadd
		WHERE $fidsql $filteradd AND ($displayorderadd)
		ORDER BY t.displayorder DESC, t.$_G[gp_orderby] $_G[gp_ascdesc]
		LIMIT ".($filterbool ? $start_limit : $start_limit - $stickycount).", $_G[tpp]");

}

$_G['ppp'] = $_G['forum']['threadcaches'] && !$_G['uid'] ? $_G['setting']['postperpage'] : $_G['ppp'];
$page = $_G['page'];
$todaytime = strtotime(dgmdate(TIMESTAMP, 'Ymd'));

$verify = $verifyuids = $grouptids = array();
while(($querysticky && $thread = DB::fetch($querysticky)) || ($query && $thread = DB::fetch($query))) {
	$thread['lastpost'] = dgmdate($thread['lastpost'], 'u');
	$threadids[] = $thread['tid'];
	$_G['forum_threadlist'][] = $thread;
}


//子版块

if(!isset($_G['cache']['forums'])) {
	loadcache('forums');
}

foreach($_G['cache']['forums'] as $sub) {
	if($sub['type'] == 'sub' && $sub['fup'] == $_G['fid'] && (!$_G['setting']['hideprivate'] || !$sub['viewperm'] || forumperm($sub['viewperm']) || strstr($sub['users'], "\t$_G[uid]\t"))) {
		if(!$sub['status']) {
			continue;
		}
		$subexists = 1;
		$sublist = array();
		$sql = !empty($_G['member']['accessmasks']) ? "SELECT f.fid, f.fup, f.type, f.name, f.threads, f.posts, f.todayposts, f.lastpost, f.domain, ff.description, ff.moderators, ff.icon, ff.viewperm, ff.extra, ff.redirect, a.allowview FROM ".DB::table('forum_forum')." f
						LEFT JOIN ".DB::table('forum_forumfield')." ff ON ff.fid=f.fid
						LEFT JOIN ".DB::table('forum_access')." a ON a.uid='$_G[uid]' AND a.fid=f.fid
						WHERE fup='$_G[fid]' AND status>'0' AND type='sub' ORDER BY f.displayorder"
					: "SELECT f.fid, f.fup, f.type, f.name, f.threads, f.posts, f.todayposts, f.lastpost, f.domain, ff.description, ff.moderators, ff.icon, ff.viewperm, ff.extra, ff.redirect FROM ".DB::table('forum_forum')." f
						LEFT JOIN ".DB::table('forum_forumfield')." ff USING(fid)
						WHERE f.fup='$_G[fid]' AND f.status>'0' AND f.type='sub' ORDER BY f.displayorder";
		$query = DB::query($sql);
		while($sub = DB::fetch($query)) {
			$sub['extra'] = unserialize($sub['extra']);
			if(!is_array($sub['extra'])) {
				$sub['extra'] = array();
			}
			if(forum($sub)) {
				$sub['orderid'] = count($sublist);
				$sublist[] = $sub;
			}
		}
		break;
	}
}

?>