<?php
/**
	文章相关操作
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$act = $_G['gp_act'];

//如果没有登录，则不能进行下列操作
if(empty($_G['uid']) && $act != 'newarticle' ) {
	$msg = lang('message', 'to_login');
	
	jsonexit("{\"message\":\"$msg\", \"nologin\":\"1\"}");
}

//收藏文章
if($act == 'favorite') { 

	$aid = max(0,intval($_G['gp_aid']));
	if(empty($aid)) { //参数aid非数字
		$msg = rpclang('portal', 'portal_parameter_error');
		jsonexit("{\"message\":\"$msg\"}");
	}
	
	$favorited = DB::getOne("SELECT favid FROM ".DB::table('home_favorite')." 
				WHERE uid='".$_G['uid']."' AND idtype='aid' AND id='$aid'");
	
	//已经收藏过了
	if($favorited) {
		$msg = rpclang('portal', 'portal_article_favorited');
		jsonexit("{\"message\":\"$msg\"}");
	}
	
	//取出文章标题
	$title = DB::getOne("SELECT title FROM ".DB::table('portal_article_title')." 
				WHERE aid='$aid'");
	
	$setarr = array(
		'uid' => $_G['uid'],
		'id' => $aid,
		'idtype' => 'aid',
		'title' => $title,
		'dateline' => TIMESTAMP
		
	);
	
	$favid = DB::insert('home_favorite', $setarr);

	jsonexit($favid);
}

//分享文章
elseif($act == 'share') { 

	$aid = max(0,intval($_G['gp_aid']));
	if(empty($aid)) { //参数aid非数字
		$msg = rpclang('portal', 'portal_parameter_error');
		jsonexit("{\"message\":\"$msg\"}");
	}
	
	$shared = DB::getOne("SELECT sid FROM ".DB::table('home_share')." 
				WHERE uid='".$_G['uid']."' AND type='article' AND itemid='$aid'");
	
	//已经分享过了
	if($shared) {
		$msg = rpclang('portal', 'portal_article_shared');
		jsonexit("{\"message\":\"$msg\"}");
	}
	
	//取出文章标题
	$row = DB::getOne("SELECT title, summary FROM ".DB::table('portal_article_title')." 
				WHERE aid='$aid'");
	$row['username'] = '<a href="portal.php?mod=view&aid='.$aid.'">'.$row['title'].'</a>';
	
	$setarr = array(
		'uid' => $_G['uid'],
		'itemid' => $aid,
		'type' => 'article',
		'title_template' => '分享了一篇文章',
		'body_template' => '<b>{title}</b><br>{username}<br>{summary}',
		'body_data' => serialize($row),
		'dateline' => TIMESTAMP
	);
	
	$favid = DB::insert('home_share', $setarr);

	jsonexit($favid);
}

//添加文章评论
elseif($act == 'postcomment') {
	
	$aid = intval($_G['gp_aid']);
	$message = $_G['gp_message'];
	
	if(CHARSET != 'utf-8') {
		$message = utf8togbk($message);
	}	
	
	if(empty($aid) || empty($message)) jsonexit();
	
	//判断是否允许评论
	$catid = DB::getOne("SELECT c.catid FROM ".DB::table('portal_article_title')." AS c
				WHERE aid='$aid'");
				
	if($catid) {
		$allowcomment = DB::getOne("SELECT allowcomment FROM ".DB::table('portal_category')." 
				WHERE catid='$catid'");
				
		if(!$allowcomment) {
			$msg = rpclang('portal', 'portal_notallow_comment');
			jsonexit("{\"message\":\"$msg\"}");
		}		
	}			
	
	
	$setarr = array(
		'uid' => $_G['uid'],
		'username' => $_G['username'],
		'id' => $aid,
		'idtype' => 'aid',
		'status' => '0',
		'message' => $message,
		'dateline' => TIMESTAMP
	);
	
	$cid = DB::insert('portal_comment', $setarr);
	
	if($cid) {
		DB::query("UPDATE ".DB::table('portal_article_count')." 
				   SET commentnum=commentnum+1 
				   WHERE aid='$aid'");
	}
	
	jsonexit("{\"cid\":\"$cid\"}");
}

//取门户文章前20条
elseif($act == 'newarticle') {
	
	loadcache('zywxdata');
	$config = unserialize($_G['cache']['zywxdata']);

	if($config['article']) {
		$config['article'] =  str_replace("\r\n", ',', $config['article']);
		$config['article'] =  str_replace(array(',,', ' '), ',', $config['article']);
		
		$custom_list = DB::getAll("SELECT t.aid, t.title, t.pic, t.remote, c.content 
						FROM ".DB::table('portal_article_title')." AS t
						LEFT JOIN ".DB::table('portal_article_content')." AS c 
						ON t.aid=c.aid AND c.pageorder=1 
						WHERE t.aid IN($config[article])
						ORDER BY t.aid");
		
	}
	
	$list = DB::getAll("SELECT t.aid, t.title, t.pic, t.remote, c.content 
					FROM ".DB::table('portal_article_title')." AS t
					LEFT JOIN ".DB::table('portal_article_content')." AS c 
					ON t.aid=c.aid AND c.pageorder=1 
					ORDER BY t.aid DESC LIMIT 20");

	if(is_array($custom_list)) {
		$list = array_merge($custom_list, $list);
	}

	foreach($list as &$row) {
		$row['content'] = cutstr(strip_tags($row['content']), 110);
		$row['content'] = preg_replace("/\[\w+\].*?\[\/\w+\]/", '', $row['content']);
	
		if($row['pic']) {		
			if($row['remote']) {
				$row['pic'] = $_G['setting']['ftp']['attachurl'].$row['pic'];
			} else {			
				$row['pic'] = $_G['siteurl'].$_G['setting']['attachurl'].$row['pic'];
			}			
		}
	}

	jsonexit($list);
}

?>