<?php
/**
 * ajax调用
 * @copyright  2011-2012 Bei Jing Zheng Yi Wireless
 * @since      File available since Release 1.0 -- 2011-8-16 下午04:33:48
 * @author niuguangju
*/

//获取帖子内容
if($_G['gp_action'] == 'getpost') { 
	
	require_once libfile('function/discuzcode');
	
	//是否有帖子的阅读权限	
	if($_G['forum']['viewperm'] && !forumperm($_G['forum']['viewperm']) && !$_G['forum']['allowview']) {
		$msg = rpclang('forum', 'viewperm_none_nopermission');	
		jsonexit("{\"msg\":\"$msg\",\"back\":\"1\"}");
	} elseif($_G['forum']['formulaperm']) {
		formulaperm($_G['forum']['formulaperm']);
	}

	if($_G['gp_pid']) {
		$post = DB::getRow("SELECT p.*, pf.* FROM ".DB::table('forum_post')." AS p
						LEFT JOIN ".DB::table('zywx_forum_postfield')." AS pf 
						ON p.pid=pf.pid
						WHERE p.pid='$_G[gp_pid]'");
	} elseif($_G['gp_tid']) {

		$tid =  $_G['gp_tid'];

		$post = DB::getRow("SELECT p.* FROM ".DB::table('forum_post')." AS p 
								WHERE p.tid='".$_G['gp_tid']."' AND p.first=1");

		$info = DB::getRow("SELECT  pf.longitude, pf.latitude, pf.device, pf.address FROM ".DB::table('zywx_forum_postfield')." AS pf 
								WHERE pf.pid='".$post['pid']."' ");					
		
		if($info) {
			$post = array_merge($post, $info);
		}
					
		$thread = DB::getRow("SELECT sortid, price, views, replies, closed, special FROM ".DB::table('forum_thread')." 
						WHERE tid='$_G[gp_tid]'");
		
		//用户是否已登录
		if($_G['uid']) {
		
			//帖子是否已收藏
			$thread['favorite'] = DB::getOne("SELECT favid FROM ".DB::table('home_favorite')." 
						WHERE uid='".$_G['uid']."' AND idtype='tid' AND id='$tid'");
						
			//帖子是否已分享
			$thread['share'] = DB::getOne("SELECT sid FROM ".DB::table('home_share')." 
						WHERE uid='".$_G['uid']."' AND type='thread' AND itemid='$tid'");	
			
		}
						
		$post = array_merge($thread, $post);			
	}	

	
	//作者被禁止或删除 内容自动屏蔽
	if($post['authorid']) {
		
		$groupid = DB::getOne("SELECT groupid FROM ".DB::table('common_member')." 
							   WHERE uid='".$post['authorid']."'");
		
		//版主
		$moderators = DB::getOne("SELECT moderators FROM ".DB::table('forum_forumfield')." 
							   WHERE fid='".$post['fid']."'");

		if($_G['uid']) {
			$adminid = DB::getOne("SELECT adminid FROM ".DB::table('common_member')." 
							   WHERE uid='".$_G['uid']."'");
		}    
		
		$moderators = $moderators ? explode("\t", $moderators) : '';
		
		if( !($_G['username'] && in_array($_G['username'], $moderators)) && !$adminid && ($groupid == 4 || $groupid == 5 || $groupid == 6)) {
			$msg = rpclang('forum', 'message_banned');
			jsonexit("{\"msg\":\"$msg\", \"back\":\"1\"}");
		}
		
		//是否显示删除按钮	
		if( $groupid != 1 && (($_G['uid'] == $post['authorid'] && $_G['setting']['editperdel']) || in_array($_G['username'], $moderators)) || $adminid == 1) {
			$post['candelete'] = 1;
		} else {
			$post['candelete'] = 0;
		}		
	} else {
		$post['candelete'] = 0;
	}
	
	if($post['price'] && !$post['candelete']) {
		$msg = rpclang('forum', 'no_privilege');
		jsonexit("{\"message\":\"$msg\"}");
	}
	
	$post['dateline'] = dgmdate($post['dateline'], 'u');
	$post['avatar'] = avatar($post['authorid'], 'small');
	$post['login_uid'] = $_G['uid'];

	//用户的最新的一条记录
	$spacenote = DB::getOne("SELECT spacenote FROM ".DB::table('common_member_field_home')." WHERE uid=".$post['authorid']);
	$post['spacenote'] = preg_replace("~src=\"(?!http:)(\w+)~i", 'src="'.$_G['siteurl'].'\\1', $spacenote);

	//找出附件图片，插入帖子内
	if($post['attachment']) {
		if(preg_match_all("/\[attach\](\d+)\[\/attach\]/i", $post['message'], $matchaids)) {
			if($matchaids[1]) {
				$aids = DB::getCol("SELECT aid FROM ".DB::table('forum_attachment')." 
						WHERE pid='$post[pid]' AND aid NOT IN(".implode(',', $matchaids[1]).")");
			}
		} else {
			$aids = DB::getCol("SELECT aid FROM ".DB::table('forum_attachment')." 
						WHERE pid='$post[pid]'");
		}
		
		foreach($aids as $aid) {
			$post['message'] .= '[attach]'.$aid.'[/attach]';
		}
	}
	
	$imgarg = ($_G['gp_width']?'&width='.$_G['gp_width']:'').
			   ($_G['gp_height']?'&height='.$_G['gp_height']:'');

	$post['message'] = discuzcode($post['message'], 0, $post['bbcodeoff'], $post['htmlon'], 1, 1, ($_G['forum']['allowimgcode'] && $_G['setting']['showimages'] ? 1 : 0), $_G['forum']['allowhtml'], 0, 0, $post['authorid'], 0, $post['pid'], $_G['setting']['lazyload']);	

	$post['message'] = preg_replace_callback('/<img.*?(src|file)=["\'](.*?)[&?"\'].*?>/i', "zy_parse_img", $post['message']);
	$post['message'] = preg_replace_callback("/\[attach\](\d+)\[\/attach\]/i", "parse_attach", $post['message']);
	
	//链接转到移动开放平台对应的页面
	$post['message'] = preg_replace('/href=["|\'](?!http:)(.*?)["|\']/i', 'href="'.$_G['siteurl'].'\\1"', $post['message']);
	//$post['message'] = preg_replace('/<a.*?href=["|\'](.*?)["|\']/i', '<a href="http://gate.baidu.com/?src='.'$1"', $post['message']);
	
	//soso表情处理
	$post['message'] = preg_replace('/{:soso__(\d+)+_(\d):}/', '<img src="http://piccache\\2.soso.com/face/_\\1"/>', $post['message']);
	$post['message'] = preg_replace('/{:soso_e(\d+)+:}/', '<img src="http://cache.soso.com/img/img/e\\1.gif"/>', $post['message']);
		
	//部分ubb过滤
	$post['message'] = preg_replace('/\[.*?\]/', '', $post['message']);
	$post['message'] = preg_replace('/style=["\'].*?["\']/', '', $post['message']);
	$post['message'] = preg_replace('/<(?!(p|\/p|br|img|embed|\/embed|a|\/a))[^>]*?>/si', '', $post['message']);
	$post['ucurl'] = $_G["setting"]['ucenterurl'];
	
	$threadsort = $thread['sortid'] && isset($_G['forum']['threadsorts']['types'][$thread['sortid']]) ? 1 : 0;
	if($threadsort) {
		require_once libfile('function/threadsort');
		$threadsortshow = threadsortshow($thread['sortid'], $_G['tid']);
	}
	$sortstr = '';
	if(is_array($threadsortshow['optionlist'])) {
		foreach($threadsortshow['optionlist'] as $option) { 
			if($option['type'] != 'info') {
				$sortstr .= $option['title'].' : '.$option['value'].'<br/>';
			}
		}	
	}
	$post['message'] = $sortstr.$post['message'];
	
	if($post['special'] == 4) {
		include libfile('thread/activity', 'include');
	}

	jsonexit($post);

}

//获取帖子标题
elseif($_G['gp_action'] == 'getsubject') { 

	$row = DB::getRow("SELECT subject FROM ".DB::table('forum_thread')." as f 
						WHERE tid='$_G[gp_tid]'");
	jsonexit($row);
}

//删除主题帖
elseif($_G['gp_action'] == 'deletethread') {
	
	$tid = $_G['gp_tid'];
						
	$authorid = DB::getOne("SELECT authorid FROM ".DB::table('forum_thread')." 
						WHERE tid='$tid'");
	
	//版主
	$moderators = DB::getOne("SELECT moderators FROM ".DB::table('forum_forumfield')." 
							   WHERE fid='".$post['fid']."'");
		
	//管理员
	if($_G['uid']) {
		$adminid = DB::getOne("SELECT adminid FROM ".DB::table('common_member')." 
							   WHERE uid='".$_G['uid']."'");
	}
	
	if($_G['setting']['editperdel'] && ($_G['setting']['editperdel'] && $_G['uid'] == $authorid) || ($_G['username'] && in_array($_G['username'], $moderators)) || $adminid) {
		DB::query("UPDATE ".DB::table('forum_thread')." SET displayorder='-1' WHERE tid='$tid'");
		DB::query("UPDATE ".DB::table('forum_post')." SET invisible='-1' WHERE tid='$tid'");
		DB::insert('forum_threadmod', array(
			'tid' => $tid,
			'uid' => $_G['uid'],
			'username' => $_G['username'],
			'dateline' => TIMESTAMP,
			'action' => 'DEL',
			'status' => 1
		), 1, 1);
		jsonexit(1);
	}
	
}

//加载回复
elseif($_G['gp_action'] == 'loadreply') {
	
	$tid =  $_G['gp_tid'];
	
	$jsonarr = array();
	$perpage = 10;
	$page = $_GET['page'] ? max(intval($_GET['page']), 1) : 1;
	$start = ($page - 1) * $perpage;
	
	//回复总数
	$count = DB::getOne("SELECT COUNT(pid) FROM ".DB::table('forum_post')." 
							WHERE tid='$tid' AND first='0' AND invisible>='0'");
	
	//是否回复仅作者可见
	$thread = DB::getRow("SELECT fid, authorid, status FROM ".DB::table('forum_thread')." 
							WHERE tid='$tid'");
							
	$hiddenreplies = getstatus($thread['status'], 2);
					
	if($thread['authorid'] != $_G['uid'] && $hiddenreplies) {
		
		//版主
		$moderators = DB::getOne("SELECT moderators FROM ".DB::table('forum_forumfield')." 
							   WHERE fid='".$thread['fid']."'");
	
		//管理员
		if($_G['uid']) {
			$adminid = DB::getOne("SELECT adminid FROM ".DB::table('common_member')." 
							   WHERE uid='".$_G['uid']."'");
		}    
		
		if($moderators) {
			$moderators = explode("\t", $moderators);
		}
		
		//如果回复仅作者可见，则输出提示
		if(($_G['username'] && !in_array($_G['username'], $moderators)) || !$adminid) {
			$msg = rpclang('forum', 'hiddenreplies');
			$jsonarr['count'] = $count;
			$jsonarr['msg'] = $msg;
			jsonexit($jsonarr);
		}
		
	}
	
	require_once libfile('function/discuzcode');

	//回复列表
	$list = DB::getAll("SELECT pid, author, authorid, dateline, message 
						FROM ".DB::table('forum_post')." 
						WHERE tid='$tid' AND first=0 AND invisible>='0' ORDER BY pid ASC LIMIT ".$start.','.$perpage);
	
	$imgarg = ($_G['gp_width']?'&width='.$_G['gp_width']:'').
			  ($_G['gp_height']?'&height='.$_G['gp_height']:'');
			   
	foreach($list as $key=>$post) {
		$post['avatar'] = avatar($post['authorid'], 'small');
		$post['dateline'] = dgmdate($post['dateline'], 'u');
		
		//未插入到帖子内的附件图片附加到帖子尾部
		if(preg_match_all("/\[attach\](\d+)\[\/attach\]/i", $post['message'], $matchaids)) {
			if($matchaids[1]) {
				$aids = DB::getCol("SELECT aid FROM ".DB::table('forum_attachment')." 
						WHERE pid='$post[pid]' AND aid NOT IN(".implode(',', $matchaids[1]).")");
			}
		} else {
			$aids = DB::getCol("SELECT aid FROM ".DB::table('forum_attachment')." 
						WHERE pid='$post[pid]'");	
		}
		
		foreach($aids as $aid) {
			$post['message'] .= "\r\n".'[attach]'.$aid.'[/attach]';
		}
	
		$post['message'] = preg_replace_callback('/<img.*?(src|file)=["\'](.*?)[&?"\'].*?>/i', "zy_parse_img", $post['message']);	
		//soso表情处理
		$post['message'] = preg_replace('/{:soso__(\d+)+_(\d):}/', '<img src="http://piccache\\2.soso.com/face/_\\1"/>', $post['message']);
		$post['message'] = preg_replace('/{:soso_e(\d+)+:}/', '<img src="http://cache.soso.com/img/img/e\\1.gif"/>', $post['message']);
		//链接转到移动开放平台对应的页面
		$post['message'] = preg_replace('/href=["|\'](?!http:)(.*?)["|\']/i', 'href="'.$_G['siteurl'].'\\1"', $post['message']);
		//$post['message'] = preg_replace('/<a.*?href=["|\'](.*?)["|\']/i', '<a href="http://gate.baidu.com/?src='.'$1"', $post['message']);
		//贴子内附件图片ubb转成html形式
		$post['message'] = preg_replace_callback("/\[attach\](\d+)\[\/attach\]/i", "parse_attach", $post['message']);
		$post['message'] = discuzcode($post['message'], 0, $post['bbcodeoff'], 1, 1, 1, 1, 1, 0, 0, $post['authorid'], 0, $post['pid'], $_G['setting']['lazyload']);
		//部分ubb过滤
		$post['message'] = preg_replace('/\[.*?\]/', '', $post['message']);
		$post['message'] = preg_replace('/style=["\'].*?["\']/', '', $post['message']);
		$post['message'] = preg_replace('/<(?!(p|\/p|span|\/span|br|img|embed|\/embed|a|\/a))[^>]*?>/si', '', $post['message']);
		

		$list[$key] = $post;
	}

	$jsonarr[0] = $list; //版块列表
	$jsonarr['zywy_curpage'] = $page;
	$jsonarr['zywy_totalpage'] = max(1, ceil($count / $perpage));
	$jsonarr['ucurl'] = $_G["setting"]['ucenterurl'];
	$jsonarr['count'] = $count;
	
	jsonexit($jsonarr);
	
}

//删除帖子
elseif($_G['gp_action'] == 'delpost') {
	
	$pid = intval($_G['gp_pid']);
	if(empty($pid)) {
		$msg = rpclang('forum', 'arg_error');
		jsonexit("{\"msg\":\"$msg\"}");
	}
	
	$row = DB::getRow("SELECT fid, authorid FROM ".DB::table('forum_post')." 
						WHERE pid='$pid'");	
	$authorgroupid = DB::getOne("SELECT groupid FROM ".DB::table('common_member')." 
						WHERE uid='{$row['authorid']}'");	
	
	if($authorgroupid == 1 && $_G['groupid'] !=1) {
		jsonexit(0);
	}
	
	if($_G['uid']) {
		if($_G['uid'] != $row['authorid']) {
			if(!ismanager($row['fid'])) {
				jsonexit(0);
			}
		}
	} else {
		jsonexit(0);
	}
	
	DB::query("DELETE FROM ".DB::table('forum_post')." WHERE pid='$pid'");
	if(DB::affected_rows()) {
		jsonexit(1);
	} else {
		jsonexit(0);
	}
	
}

//注销登录
elseif($_G['gp_action'] == 'logout') {
	
	clearcookies();
	$_G['groupid'] = $_G['member']['groupid'] = 7;
	$_G['uid'] = $_G['member']['uid'] = 0;
	$_G['username'] = $_G['member']['username'] = $_G['member']['password'] = '';
}

//是否有新消息
elseif($_G['gp_action'] == 'pm_checknew') {
	
	loaducenter();

	//提醒数量
	$noticenum = DB::getOne("SELECT newprompt FROM ".DB::table('common_member')." 
						WHERE uid='$_G[uid]'");
	$friend_request_num = DB::getOne("SELECT COUNT(uid) FROM ".DB::table('home_friend_request')." WHERE uid='$_G[uid]' ");
	$newpm = uc_pm_checknew($_G['uid'], 1);
	$newpm = $newpm['newpm'];
	$jsonarr = array();
	$jsonarr['newpm'] = $newpm;
	$jsonarr['newprompt'] = $noticenum;
	$jsonarr['friend_request_num'] = $friend_request_num;
	
	jsonexit($jsonarr);
}

//获取全局置顶和本版块置顶的帖子
elseif($_G['gp_action'] == 'gettopthread') {

	loadcache('globalstick');
	$thisgid = $_G['forum']['type'] == 'forum' ? $_G['forum']['fup'] : (!empty($_G['cache']['forums'][$_G['forum']['fup']]['fup']) ? $_G['cache']['forums'][$_G['forum']['fup']]['fup'] : 0);
	$stickycount = $_G['cache']['globalstick']['global']['count'];
	$stickytids = $_G['cache']['globalstick']['global']['tids'];
	
	if(!empty($_G['cache']['globalstick']['categories'][$thisgid])) {
		$stickycount += $_G['cache']['globalstick']['categories'][$thisgid]['count'];
	}
	if(!empty($_G['cache']['globalstick']['categories'][$thisgid]['count'])) {
		$stickytids .= ($stickytids ? ',' : '').$_G['cache']['globalstick']['categories'][$thisgid]['tids'];
	}
	$stickylist = DB::getAll("SELECT t.tid, t.fid, t.author, t.authorid, t.displayorder,  t.subject,  t.lastpost, t.views, t.replies, t.digest, t.attachment
							  FROM ".DB::table('forum_thread')." t
							  WHERE t.tid IN ($stickytids) AND (t.displayorder IN (2, 3, 4))
							  ORDER BY displayorder DESC,  lastpost DESC
							  LIMIT 0,".$stickycount);
	$threadlist = DB::getAll("SELECT t.tid, t.fid, t.author, t.authorid, t.displayorder,  t.subject,  t.lastpost, t.views, t.replies, t.digest, t.attachment 
							  FROM pre_forum_thread t
							  WHERE  t.fid='".$_G['forum']['fid']."'  AND (t.displayorder IN (0, 1))
							  ORDER BY displayorder DESC, lastpost DESC
							  LIMIT 10");
	foreach($threadlist as $key => $value) {
		if($value['displayorder'] == 0) {
			unset($threadlist[$key]);
		}
	}
	if(is_array($stickylist)) {
		$threadlist = array_merge($stickylist, $threadlist);
	}	
	foreach($threadlist as &$thread) {
		$thread['lastpost'] = dgmdate($thread['lastpost'], 'u');
	}
	
	$jsonarr['list'] = $threadlist;
	$jsonarr['ucurl'] = $_G["setting"]['ucenterurl'];
	jsonexit($jsonarr);
}


//统计手机应用客户端安装量
elseif($_G['gp_action'] == 'installationstat') {
	$install = zy_loadcache('zyinstall');
	if(empty($install)) {
		$install = array(
			'phone'=>0,
			'android'=>0,
			'symbian'=>0
		);
	} else {
		if(isset($_G['gp_action'][$_G['gp_id']]))
			$install['phone'] = intval($install['phone']) + 1;
	}
	
	zy_savecache('zyinstall', $install);
}

//论坛上传图片
elseif($_G['gp_action'] == 'uploadimage') {

	//没有登录
	if(empty($_G['uid'])) {
		exit('-3');
	}

	require_once libfile('class/upload');
	$upload = new discuz_upload();

	if(!$upload->init($_FILES['Filedata'], 'forum')) {
		exit('0');
	}

	$attach = & $upload->attach;
	
	//图片加水印
	if (!$attach['isimage']) {
		exit('-1');
	}

	//图片大小限制
	if ($_G['group']['maxattachsize'] && $attach['size'] > $_G['group']['maxattachsize']) {
		exit('-2');
	}
	
	//上传成功
	if(!$upload->error()) {
		$upload->save();
	}
	
	$watermarkstatus =  unserialize($_G['setting']['watermarkstatus']);
	if($watermarkstatus['forum']) {	
		require_once libfile('class/image');
		$image = new image;
		$image->watermark($attach['target']);
	}
	
	$aid = getattachnewaid($_G['uid']);

	$setarr = array(
		'aid' => $aid,
		'uid' => $_G['uid'],
		'dateline' => TIMESTAMP,
		'filename' => $attach['name'],
		'filesize' => $attach['size'],
		'attachment' => $attach['attachment'],
		'remote' => 0,
		'isimage' => 1,
		'width' => $attach['imageinfo'][0],
		'thumb' => 0
	);
	$attachid = DB::insert("forum_attachment_unused", $setarr, true);

	echo '1|'.$aid;
}

//当前用户是否为超级管理员
elseif($_G['gp_action'] == 'isadmin') {

	if($_G['adminid'] == 1 && $_G['groupid'] == 1) {
		jsonexit(1);
	} else {
		jsonexit(0);
	}
}

//获取资讯头条广告栏图片
elseif($_G['gp_action'] == 'topimg') {
	
	$data = unserialize(DB::getOne('SELECT data FROM '.DB::table("common_syscache")." WHERE cname='zywxdata'"));

	$name = basename($data['topimg']);
	//$name = substr($name, 0, strpos($name, '.'));
	
	$jsonarr['src'] = $data['topimg'];
	$jsonarr['name'] = $name;
	jsonexit($jsonarr);
}

elseif($_G['gp_action'] == 'isdiscuz') {
	echo '1';
}

elseif($_G['gp_action'] == 'activityapplies') {
	
	$tid = intval($_G['gp_tid']);
	
	$exists = DB::getOne("SELECT COUNT(*) FROM ".DB::table('forum_activityapply')." WHERE tid='$tid' AND uid='$_G[uid]'");
	$authorid = DB::getOne("SELECT authorid FROM ".DB::table('forum_thread')." WHERE tid='$tid'");

	if(!$_G['uid'] || $exists || !$tid) {		
		jsonexit(0);
	}	
		
	$payment = -1;
	$message = cutstr(dhtmlspecialchars($_G['gp_message']), 200);
	$verified = $authorid == $_G['uid'] ? 1 : 0;
	
	$ufielddata = array(
		'userfield' => array(
			'realname' => htmlspecialchars($_G['gp_realname']),
			'mobile' => htmlspecialchars($_G['gp_mobile']),
			'qq' => htmlspecialchars($_G['gp_qq'])
		)
	);
	$ufielddata = serialize($ufielddata);

	DB::query("INSERT INTO ".DB::table('forum_activityapply').
	" (tid, username, uid, message, verified, dateline, payment, ufielddata) VALUES 
	  ('$tid', '$_G[username]', '$_G[uid]', '$message', '$verified', '$_G[timestamp]', '$payment', '$ufielddata')");
	 
	jsonexit(1);
}

//清除cookie
function clearcookies() {
	global $_G;
	foreach($_G['cookie'] as $k => $v) {
		if($k != 'widthauto') {
			dsetcookie($k);
		}
	}
	$_G['uid'] = $_G['adminid'] = 0;
	$_G['username'] = $_G['member']['password'] = '';
}





?>