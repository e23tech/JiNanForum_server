<?php
/**
 * 发帖、回贴、编辑帖子处理
 * @copyright  2011-2012 Bei Jing Zheng Yi Wireless
 * @since      File available since Release 1.0 -- 2011-8-16 下午04:33:48
 * @author niuguangju
*/

//如请求来源不为手机端，虽返回
//if(preg_match('/MSIE|Firefox|Chrome|Safari|Opera/i', $_SERVER['HTTP_USER_AGENT'])) return;

/*入库前处理一下编码、判断空值或非法值等操作*/

//如果没有登录，则不能进行下列操作
if(empty($_G['uid'])) {
	$msg = lang('message', 'to_login');
	
	jsonexit("{\"message\":\"$msg\", \"nologin\":\"1\"}");
}

//两次操作时间时隔
if(check_flood()) {
	$msg = rpclang('forum', 'post_flood_ctrl');
	$msg = sprintf($msg, $_G['setting']['floodctrl']);
	jsonexit("{\"message\":\"$msg\", \"flood\":\"1\"}");
}

//回贴
if($_G['gp_action'] == 'reply' && $_G['gp_replysubmit']) { 
	
	if($_G['gp_attachnew']) {
		$arr = array(
			'description' => '',
			'price' => 0,
			'isimage' => 1,
		);
		
		$_G['gp_attachnew'] = explode(',', $_G['gp_attachnew']);
		$_G['gp_attachnew'] = array_combine($_G['gp_attachnew'], $_G['gp_attachnew']);
		$_G['gp_price'] = '';
		$_G['gp_uploadalbum'] = 1;
		$_G['gp_usesig'] = 1;
		$_G['gp_allownoticeauthor'] = 1;
		
		foreach($_G['gp_attachnew'] as $key=>$aid) {
			$_G['gp_attachnew'][$key] = $arr;		
		}
	}
	
	//回复内容空值检查
	if(empty($_G['gp_message'])) {
		$msg = rpclang('forum', 'forum_reply_empty');
		jsonexit("{\"message\":\"$msg\"}");
	}
	
	if(CHARSET != 'utf-8') {
		$_G['gp_message'] = utf8togbk($_G['gp_message']);
		if($_G['gp_noticeauthor']) 
			$_G['gp_noticeauthor'] = utf8togbk($_G['gp_noticeauthor']);
		if($_G['gp_noticetrimstr']) 
			$_G['gp_noticetrimstr'] = utf8togbk($_G['gp_noticetrimstr']);
		if($_G['gp_noticeauthormsg']) 
			$_G['gp_noticeauthormsg'] = utf8togbk($_G['gp_noticeauthormsg']);
	}	
} 

//发帖
elseif($_G['gp_action'] == 'newthread' && $_G['gp_topicsubmit']) { 
	
	if($_G['gp_attachnew']) {
		$arr = array(
			'description' => '',
			'price' => 0,
			'isimage' => 1,
		);
		
		$_G['gp_attachnew'] = explode(',', $_G['gp_attachnew']);
		$_G['gp_attachnew'] = array_combine($_G['gp_attachnew'], $_G['gp_attachnew']);
		$_G['gp_price'] = '';
		$_G['gp_uploadalbum'] = 1;
		$_G['gp_usesig'] = 1;
		$_G['gp_allownoticeauthor'] = 1;
		
		foreach($_G['gp_attachnew'] as $key=>$aid) {
			$_G['gp_attachnew'][$key] = $arr;		
		}
	}


	//帖子标题空值检查
	if(empty($_G['gp_subject'])) {
		$msg = rpclang('forum', 'forum_topic_empty');
		jsonexit("{\"message\":\"$msg\"}");
	}
	
	//帖子内容空值检查
	if(empty($_G['gp_message'])) {
		$msg = rpclang('forum', 'forum_topicmessage_empty');
		jsonexit("{\"message\":\"$msg\"}");
	}
	
	//加广告
	//$_G['gp_message'] = rpclang('forum', 'ad') . $_G['gp_message'];
	
	if(CHARSET != 'utf-8') {
		$_G['gp_subject'] = utf8togbk($_G['gp_subject']);
		$_G['gp_message'] = utf8togbk($_G['gp_message']);
	}
}

require RPC_DIR . '/dmodule/forum/forum_'.$mod.'.php';
ob_end_clean();

/*返回想要的json数据*/

//回帖
if($_G['gp_action'] == 'reply') {

	//返回回复的帖子id
	if($_G['gp_replysubmit']) {
		jsonexit("{\"pid\":\"$pid\"}");
	}
	
}

//发帖
elseif($_G['gp_action'] == 'newthread') { 
	
	if($_G['gp_topicsubmit']) {
		jsonexit("{\"tid\":\"$tid\"}");
	} else { //发帖页面初始化
		$forumname = gbktoutf8($_G['forum']['name']);
		$actiontitle = gbktoutf8(trim($actiontitle));
		
		jsonexit("{\"forumname\":\"$forumname\",\"actiontitle\":\"$actiontitle\", \"status\":\"$_G[forum][status]\"}");
	}
	
}

//编辑帖子
elseif($_G['gp_action'] == 'edit') { 
	
	if($_G['gp_editsubmit']) {
		jsonexit("{\"pid\":\"$pid\"}");
	} else { //编辑帖子页面初始化
		$jsonarr = array();
		$jsonarr[0] = $postinfo; //帖子信息
		$jsonarr[1] = array('forumname' => $_G['forum']['name'], 'status' => $_G['forum']['status']); //版块信息

		jsonexit($jsonarr);
	}
	
}

function check_flood() {
	global $_G;
	if(!$_G['group']['disablepostctrl'] && $_G['uid']) {
		$isflood = $_G['setting']['floodctrl'] && (TIMESTAMP - $_G['setting']['floodctrl'] <= getuserprofile('lastpost'));

		if(empty($isflood)) {
			return FALSE;
		} else {
			return TRUE;
		}
	}
	return FALSE;
}

?>