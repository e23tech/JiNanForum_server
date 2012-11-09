<?php
/**
 * 论坛版块列表
 * @copyright  2011-2012 Bei Jing Zheng Yi Wireless
 * @since      File available since Release 1.0 -- 2011-8-16 下午04:33:48
 * @author niuguangju
*/

if($_G['gp_type'] == 'sub') {

	$forumlist = DB::getAll("SELECT fid, name, threads, todayposts FROM ".DB::table('forum_forum')." WHERE type='sub' AND status='1'");
	
	foreach($forumlist as $forum) {
		if(in_array($forum['fid'], $hide_forum_ids)) {
			unset($forumlist[$id]);
		}
	}
	
	jsonexit($forumlist);
}

require RPC_DIR . '/dmodule/forum/forum_'.$mod.'.php';

loadcache('zywxdata');
$config = unserialize($_G['cache']['zywxdata']);

//加载需要隐藏的版块
$hide_forum_ids = $config['hideforum'];


//从分区中去除隐藏的版块
$partition = array();
foreach($catlist as $cat) {

	if($cat['forums']) {
		foreach($cat['forums'] as $key=>$fid) {
			if(in_array($fid, $hide_forum_ids)) {
				unset($cat['forums'][$key]);
			}
		}
		$partition[] = $cat;
	}
}

//从版块数据中去除隐藏的版块
foreach($hide_forum_ids as $id) {
	unset($forumlist[$id]);
}


$jsonarr = array();
$jsonarr[0] = $partition; //分区
$jsonarr[1] = $forumlist; //版块

jsonexit($jsonarr);

?>