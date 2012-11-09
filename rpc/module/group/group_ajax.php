<?php
/**
 * ajax调用
 * @copyright  2011-2012 Bei Jing Zheng Yi Wireless
 * @since      File available since Release 1.0 -- 2011-8-16 下午04:33:48
 * @author niuguangju
*/


//获取群组信息
if($_G['gp_action'] == 'getgroupinfo') {
	
	require ROOT_DIR . '/source/function/function_forum.php';
	loadforum();
	
	$groupinfo = array();
	
	$groupinfo['name'] = $_G['forum']['name'];
	$groupinfo['description'] = $_G['forum']['description'];
	$groupinfo['dateline'] = date('Y-m-d H:i:s', $_G['forum']['dateline']);
	$groupinfo['threads'] = $_G['forum']['threads']; 
	$groupinfo['membernum'] = $_G['forum']['membernum'];
	$groupinfo['moderators'] = $_G['forum']['moderators'];
	$groupinfo['jointype'] = $_G['forum']['jointype'];
	
	$groupinfo['isjoined'] = DB::getOne("SELECT COUNT(fid) FROM ".DB::table('forum_groupuser')." 
							WHERE uid='".$_G['uid']."'");

	foreach($_G['forum']['moderators'] as $row) {
		$groupinfo['admin'] .= "<a href='../my/profile.html?uid=$row[uid]'>$row[username]</a> ";
	}
						
	jsonexit($groupinfo);
}

//获取某分类下全部群组
if($_G['gp_action'] == 'showgroup') {
	
	$jsonarr = array();
	$perpage = 10;
	$page = $_GET['page'] ? max(intval($_GET['page']), 1) : 1;
	$start = ($page - 1) * $perpage;
	
	//总数
	$count = DB::getOne("SELECT COUNT(fid) FROM ".DB::table('forum_forum')." 
							WHERE fup='".$_G['gp_fid']."'");

	//列表
	$list = DB::getAll("SELECT f.fid, f.type, f.name, fi.groupnum, fi.membernum  FROM ".DB::table('forum_forum')." AS f
						LEFT JOIN ".DB::table('forum_forumfield')." AS  fi
						ON f.fid=fi.fid 
						WHERE f.fup='".$_G['gp_fid']."' LIMIT ".$start.','.$perpage);
	
	$jsonarr[0] = $list; //列表
	$jsonarr['zywy_curpage'] = $page;
	$jsonarr['zywy_totalpage'] = @ceil($count / $perpage);
								
	jsonexit($jsonarr);
}

?>