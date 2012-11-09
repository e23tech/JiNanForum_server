<?php
/**
 * 论坛帖子列表
 * @copyright  2011-2012 Bei Jing Zheng Yi Wireless
 * @since      File available since Release 1.0 -- 2011-8-16 下午04:33:48
 * @author niuguangju
*/
require RPC_DIR . '/dmodule/forum/forum_'.$mod.'.php';

$jsonarr = array();
$jsonarr[0] = $_G['forum']['name']; //版块名称
$jsonarr[1] = $jsonarr[2] = '';
$jsonarr[3] = $sublist; //子版块

//获取群组信息
if($_G['forum']['status'] == 3) {
		
	//群组信息
	$groupinfo = DB::getRow("SELECT f.threads, ff.membernum FROM ".DB::table('forum_forum')." as f 
							 LEFT JOIN ".DB::table('forum_forumfield')." as ff 
							 ON ff.fid=f.fid 
							 WHERE f.fid='$_G[gp_fid]'");
								 
	$groupinfo['name'] = $_G['forum']['name']; //群组名称
}

$formulaperm = unserialize($_G['forum']['formulaperm']);
$forummedals = $formulaperm['medal'];

//如果版块设置需特定勋章进入，则进行判断
if($forummedals) {
	$medals = explode("\t", DB::result_first("SELECT medals FROM ".DB::table('common_member_field_forum')." WHERE uid='$_G[uid]'"));
	$allow =0;
	foreach($forummedals as $medal) {
		if(in_array($medal, $medals)) {
			$allow = 1;
		}
	}
	
	//没有权限进入版块
	if(empty($allow)) {
		$msg = rpclang('forum', 'viewperm_none_nopermission');
		jsonexit("{\"message\":\"$msg\", \"back\":\"1\"}");
	}
}

//版块帖子数为0
if($_G['forum_threadcount'] == 0) {
	$msg = rpclang('forum', 'forum_nothreads');
	$msg = utf8togbk($msg);

	$jsonarr[6] = $msg; //提示
	
} 

//版块有贴子
else { 
	
	require_once libfile('function/post');

	//获取帖子内容简介
	foreach($_G['forum_threadlist'] as $key=>$thread) {
			$tid = $thread['tid'];
			$post = DB::getRow("SELECT p.anonymous, p.message, pf.longitude FROM ".DB::table('forum_post')." as p 
						LEFT JOIN ".DB::table('zywx_forum_postfield')." AS pf 
						ON p.pid=pf.pid	 
						WHERE p.tid='$tid' AND p.first=1");
			if($post['longitude']) {
				$thread['jw'] = 1;
			}
			
			$post['message'] = preg_replace('/<.*?>|\[.*?\]/', '', $post['message']);
			$post['message'] =  cutstr($post['message'], 110);
			$_G['forum_threadlist'][$key]['message'] = $post['message'];
			$_G['forum_threadlist'][$key]['anonymous'] = $post['anonymous'];
	}
	
	$jsonarr[1] = $_G['forum_threadlist']; //帖子数组
	//$jsonarr[2] = $multipage; //帖子分页	
}

$jsonarr['zywy_curpage'] = $_G["zywy_curpage"];
$jsonarr['zywy_totalpage'] = $_G["zywy_totalpage"];
$jsonarr['ucurl'] = $_G["setting"]['ucenterurl'];

jsonexit($jsonarr);

?>