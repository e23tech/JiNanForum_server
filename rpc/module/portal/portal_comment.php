<?php
/**
	文章评论
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$aid = max(0,intval($_GET['aid']));
if(empty($aid)) { //参数aid非数字
	$msg = rpclang('portal', 'portal_parameter_error');
	jsonexit("{\"message\":\"$msg\"}");
}

$jsonarr = array();
$perpage = 10;
$page = $_GET['page'] ? max(intval($_GET['page']), 1) : 1;
$start = ($page - 1) * $perpage;

$article = DB::getRow("SELECT title, id, idtype, dateline FROM ".DB::table('portal_article_title')." 
					 WHERE aid='$aid'");
					 
$title = $article['title'];

if(empty($article['idtype'])) {
					 
	//文章评论总数
	$count = DB::getOne("SELECT COUNT(cid) FROM ".DB::table('portal_comment')." 
						 WHERE id='$aid'");
	
	//文章评论列表
	$list = DB::getAll("SELECT uid, username, message, dateline 
						FROM ".DB::table('portal_comment')." 
						WHERE id='$aid' ORDER BY cid DESC LIMIT ".$start.','.$perpage);

}

//由帖子回复生成评论
elseif($article['idtype'] == 'tid') {
	
	$count = DB::getOne("SELECT COUNT(pid) FROM ".DB::table('forum_post')." 
						 WHERE first=0 AND tid='".$article['id']."'");
	
	$list = DB::getAll("SELECT authorid AS uid, author AS username, message, dateline 
						FROM ".DB::table('forum_post')." 
						WHERE first=0 AND tid='".$article['id']."' 
						ORDER BY pid ASC LIMIT ".$start.','.$perpage);						 
}

//由日志评论生成评论
elseif($article['idtype'] == 'blogid') {
	
	//文章评论总数
	$count = DB::getOne("SELECT COUNT(cid) FROM ".DB::table('home_comment')." 
						 WHERE idtype='blogid' AND id='".$article['id']."'");
	
	//文章评论列表
	$list = DB::getAll("SELECT authorid AS uid, author AS username, message, dateline 
						FROM ".DB::table('home_comment')." 
						WHERE idtype='blogid' AND id='".$article['id']."' 
						ORDER BY cid ASC LIMIT ".$start.','.$perpage);						 
}

foreach($list as $key=>$row) {
	$row['avatar'] = avatar($row['uid'], 'small');
	$row['dateline'] = dgmdate($row['dateline'], 'u');
	$row['message'] = preg_replace('/<a.*?>|<\/a>/', '', $row['message']);
	
	$list[$key] = $row;
}

$jsonarr['list'] = $list; //栏目文章列表
$jsonarr['title'] = $title;
$jsonarr['dateline'] = dgmdate($article['dateline'], 'u');
$jsonarr['zywy_curpage'] = $page;
$jsonarr['zywy_totalpage'] = max(ceil($count / $perpage), 1);
$jsonarr['zywy_total'] = $count;
jsonexit($jsonarr);

?>