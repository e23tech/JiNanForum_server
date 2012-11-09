<?php
/**
 * 群组处理
 * @copyright  2011-2012 Bei Jing Zheng Yi Wireless
 * @since      File available since Release 1.0 -- 2011-8-16 下午04:33:48
 * @author niuguangju
*/

//入库前处理 - 创建群组
if($_G['gp_createsubmit']) {
	
	if(CHARSET != 'utf-8') {
		$_G['gp_name'] = utf8togbk($_G['gp_name']);
		$_G['gp_descriptionnew'] = utf8togbk($_G['gp_descriptionnew']);
	}
}

require RPC_DIR . '/dmodule/forum/forum_group.php';

//加入群组
/*
说明：
$_G['forum']['jointype']
0 自由加入
1 邀请才能加入
2 审核加入
-1 关闭
*/
if($_G['gp_action'] == 'join') { 

	jsonexit(1);
}

//创建群组
elseif($_G['gp_action'] == 'create') { 
	
	if($_G['gp_createsubmit']) {
		jsonexit("{\"newfid\":\"$newfid\"}");
	} else {
		jsonexit(array($groupselect['first']));
	}
	
}

//群组信息
elseif($_G['gp_action'] == 'info') { 
	
	$group['icon'] = $_G['siteurl'].$_G['forum']['icon'];	//群组图标
	$group['name'] = $_G['forum']['name']; //群组名称
	$group['description'] = $_G['forum']['description']; //群组描述
	$group['commoncredits'] = $_G['forum']['commoncredits']; //群组各人
	$group['groupmanagers'] = $groupmanagers; //群组管理员列表
	
	jsonexit($group);
}

//成员列表
elseif($_G['gp_action'] == 'memberlist') { 
	$jsonarr = array();
	$jsonarr[0] = $alluserlist;
	foreach($jsonarr[0] as &$member) {
		$member['message'] = DB::getOne('SELECT message FROM '.DB::table('home_doing')." 
								   WHERE uid='".$member['uid']."' ORDER BY doid DESC LIMIT 1 ");
		
		//成员最新留言
		$member['message'] = str_replace('src="static/', 'src="'.STATICURL, $member['message']);
	
		$member['avatar'] = avatar($member['uid'], 'small');
	}

	//$jsonarr[1] = $multipage; //分页
	/*MB add start*/
	$jsonarr['zywy_curpage'] = $_G["zywy_curpage"];
	$jsonarr['zywy_totalpage'] = $_G["zywy_totalpage"];
	/*MB add end*/
	
	jsonexit($jsonarr);

}

?>