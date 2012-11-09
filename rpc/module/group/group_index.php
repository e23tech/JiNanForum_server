<?php

/**
 * 群组首页
 * @copyright  2011-2012 Bei Jing Zheng Yi Wireless
 * @since      File available since Release 1.0 -- 2011-8-16 下午04:33:48
 * @author niuguangju
 */

//如果指定群组id
if($_G['gp_gid'] || $_G['gp_sgid']) {

	require RPC_DIR . '/dmodule/group/group_'.$mod.'.php';
	
	//图片使用绝对路径
	foreach($list as &$row) {
		$row['icon'] = $_G['siteurl'] . $row['icon'];
	}

	$jsonarr = array();
	$jsonarr[0] = $curtype; //群组关系id索引数组
	$jsonarr[1] = $typelist; //群组信息数组
	$jsonarr[2] = $list; //群组数组
	$jsonarr[3] = $fup; //当前分类id
	$jsonarr[4] = $sgid; //下级分类id

	jsonexit($jsonarr);
	
} 


//群组分类
if($_G['gp_tab'] == '1'){ 
	
	require RPC_DIR . '/dmodule/group/group_'.$mod.'.php';
	ob_end_clean();
	
	//如果群组没有分类，则显示提示信息
	if(empty($second)) {
		$msg = rpclang('group', 'group_not_category');
		jsonexit("{\"message\":\"$msg\"}");
	}

	$jsonarr = array();
	$jsonarr[0] = $first; //群组关系id索引数组
	$jsonarr[1] = $second; //群组信息数组

	jsonexit($jsonarr);
}

//全部群组
elseif($_G['gp_tab'] == '2'){
	
	$jsonarr = array();
	$perpage = 10;
	$page = $_GET['page'] ? max(intval($_GET['page']), 1) : 1;
	$start = ($page - 1) * $perpage;
		
	//个数
	$count = DB::getOne("SELECT COUNT(f.fid) FROM ".DB::table('forum_forum')." as f 
							 LEFT JOIN ".DB::table('forum_forumfield')." as ff 
							 ON ff.fid=f.fid 
							WHERE f.type='sub' AND f.status='3'");

	//群组列表
	$groups = DB::getAll("SELECT f.fid, f.name, ff.membernum FROM ".DB::table('forum_forum')." as f 
							 LEFT JOIN ".DB::table('forum_forumfield')." as ff 
							 ON ff.fid=f.fid 
							WHERE f.type='sub' AND f.status='3' LIMIT ".$start.','.$perpage);

	$jsonarr[0] = $groups; //群组列表
	$multipage = multi($count, $perpage, $page, "?mod=index&tab=2");
	/*MB add start*/
	$jsonarr['zywy_curpage'] = $_G["zywy_curpage"];
	$jsonarr['zywy_totalpage'] = $_G["zywy_totalpage"];
	/*MB add end*/
	//$jsonarr[1] = $multipage; //分页

	jsonexit($jsonarr);
}

//我加入的群组
elseif($_G['gp_tab'] == '3'){

	if(empty($_G['uid'])) {
		$msg = lang('message', 'to_login');
		
		jsonexit("{\"message\":\"$msg\", \"nologin\":\"1\"}");
	}
	
	$perpage = $_G['tpp'] ? $_G['tpp'] : 20;
	$page = $_GET['page'] ? max(intval($_GET['page']), 1) : 1;
	$start = ($page - 1) * $perpage;
	

	$jsonarr = array();
	
	//字段说明：level 0: 正在审核中 1:是群组创建者 4:群组成员	
	//已加入群组并审核通过
	$count = DB::getOne("SELECT COUNT(*) FROM ".DB::table('forum_groupuser')." 
							WHERE uid='".$_G['uid']."' AND level>0");
							
	$groups = DB::getAll("SELECT fid FROM ".DB::table('forum_groupuser')." 
							WHERE uid='".$_G['uid']."' AND level>0 LIMIT ".$start.','.$perpage);
		
	foreach($groups as $key=>$group) {
		$row = DB::getRow("SELECT f.name, ff.membernum FROM ".DB::table('forum_forum')." AS f
							LEFT JOIN ".DB::table('forum_forumfield')." as ff 
							ON ff.fid=f.fid
							WHERE f.fid='".$group['fid']."'");
		
		$groups[$key]['name'] = $row['name'];
		
		$groups[$key]['membernum'] = $row['membernum'];
		if(!$groups[$key]['name']) unset($groups[$key]);				
	}
	
	$jsonarr[0] = $groups; //群组列表

	$multipage = multi($count, $perpage, $page, "?mod=index&tab=3");
	/*MB add start*/
	$jsonarr['zywy_curpage'] = $_G["zywy_curpage"];
	$jsonarr['zywy_totalpage'] = $_G["zywy_totalpage"];
	/*MB add end*/
	//$jsonarr[1] = $multipage; //分页

	jsonexit($jsonarr);
}

?>