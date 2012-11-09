<?php

/**
 * 我的群组信息
 * @copyright  2011-2012 Bei Jing Zheng Yi Wireless
 * @since      File available since Release 1.0 -- 2011-8-16 下午04:33:48
 * @author niuguangju
 */

//我的主题
if($_G['gp_view'] == 'mythread') {
	
	require_once libfile('function/post');
	
	//群组id
	$fid = $_G['gp_fid'] ? intval($_G['gp_fid']) : 0;
	if(!$fid) {
		$msg = rpclang('group', 'group_empty_id');
		jsonexit("{\"message\":\"$msg\"}");
	}
	
	$jsonarr = array();
	$perpage = $_G['tpp'] ? $_G['tpp'] : 20;
	$page = $_GET['page'] ? max(intval($_GET['page']), 1) : 1;
	$start = ($page - 1) * $perpage;
	
	//记录个数
	$count = DB::getOne("SELECT COUNT(tid) FROM ".DB::table('forum_thread')." WHERE authorid='$_G[uid]' AND fid ='".$fid."'");

	//我的话题列表
	$mythreadlist = DB::getAll("SELECT fid, tid, author, subject, lastpost, lastposter, views, replies, closed, attachment FROM ".DB::table('forum_thread')." WHERE authorid='$_G[uid]' AND fid ='".$fid."' ORDER BY lastpost DESC LIMIT $start, $perpage");
	
	//时间格式化
	foreach($mythreadlist as &$thread) {
		$tid = $thread['tid'];
		$thread['subject'] = cutstr($thread['subject'], '30');
		$thread['lastpost'] = dgmdate($thread['lastpost'], 'u');
		$message = DB::getOne("SELECT message FROM ".DB::table('forum_post')." as f 
						WHERE tid='$tid'");
			
		$thread['message'] = messagecutstr(strip_tags($message), 80);
	}
	
	$jsonarr[0] = $mythreadlist; //我的话题列表
	$multipage = multi($count, $perpage, $page, "?fid=$fid&mod=my&view=mythread");
	
	/*MB add start*/
	$jsonarr['zywy_curpage'] = $_G["zywy_curpage"];
	$jsonarr['zywy_totalpage'] = $_G["zywy_totalpage"];
	/*MB add end*/
	
	//$jsonarr[1] = $multipage; //分页
	jsonexit($jsonarr);
}

?>