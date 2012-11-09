<?php
/**
	显示文章内容
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$aid = max(0,intval($_GET['aid']));
if(empty($aid)) { //参数catid非数字
	$msg = rpclang('portal', 'portal_catid_formaterror');
	jsonexit("{\"message\":\"$msg\"}");
}

//获取标题等小字段
$article = DB::getRow("SELECT catid, uid, username, title, dateline, id, idtype FROM ".DB::table('portal_article_title')."  
					   WHERE aid='$aid'");

//取阅读数量
$article['viewnum'] = DB::getOne("SELECT viewnum FROM ".DB::table('portal_article_count')." 
					   WHERE aid='$aid'");

//取栏目名称
$article['catname'] = DB::getOne("SELECT catname FROM ".DB::table('portal_category')." 
					   WHERE catid='$article[catid]'");
					   
					   
//获取文章内容（可能有分页，所以在这里写）
$contents = DB::getAll("SELECT content FROM ".DB::table('portal_article_content')." 
					   WHERE aid='$aid'");
foreach($contents as $content) {
	$article['content'] .= $content['content'];
}					   

if(empty($article['idtype'])) {				   
	//取评论数
	$article['commentnum'] = DB::getOne("SELECT COUNT(cid) FROM ".DB::table('portal_comment') . "
						 WHERE id='$aid' AND idtype='aid'");
}
	
elseif($article['idtype'] == 'tid') {
	
	$article['commentnum'] = DB::getOne("SELECT COUNT(pid) FROM ".DB::table('forum_post')." 
						 WHERE first=0 AND tid='".$article['id']."'");
}

elseif($article['idtype'] == 'blogid') {

	$article['commentnum'] = DB::getOne("SELECT COUNT(cid) FROM ".DB::table('home_comment')." 
						 WHERE idtype='blogid' AND id='".$article['id']."'");
}


//无此文章则显示提示信息
if(empty($article)) {
	$msg = rpclang('portal', 'portal_article_nothave');
	jsonexit("{\"message\":\"$msg\"}");
}

$article['avatar'] = avatar($article['uid'], 'small');
$article['dateline'] = dgmdate($article['dateline'], 'u');
$article['login_uid'] = $_G['uid'];

//去除文章中格式
$article['content'] = preg_replace('#style="(.*?)"#', '', $article['content']);

//链接转到移动开放平台对应的页面
$article['content'] = preg_replace('/href=["|\'](?!http:)(.*?)["|\']/i', 'href="'.$_G['siteurl'].'\\1"', $article['content']);
$article['content'] = preg_replace('/<a.*?href=["|\'](.*?)["|\']/i', '<a href="http://gate.baidu.com/?src='.'$1"', $article['content']);

$imgarg = ($_G['gp_width']?'&width='.$_G['gp_width']:'').
	      ($_G['gp_height']?'&height='.$_G['gp_height']:'');

$article['content'] = preg_replace_callback('/<img.*?(src|file)=["\'](.*?)[&?"\'].*?>/i', "zy_parse_img", $article['content']);
$article['content'] = preg_replace('/style=["\'].*?["\']/', '', $article['content']);
$article['content'] = preg_replace('/<(?!(p|\/p|br|img|embed|\/embed|a|\/a))[^>]*?>/si', '', $article['content']);

//帖子图片附件解析
if( $article['idtype'] == 'tid' && preg_match("/\[attach\](\d+)\[\/attach\]/i", $article['content']) ) {
	$article['content'] = preg_replace_callback("/\[attach\](\d+)\[\/attach\]/i", "parse_attach", $article['content']);
}

$article['content'] = preg_replace('/\[.*?\]/', '', $article['content']);

//用户是否已登录
if($_G['uid']) {

	//此文章是否已收藏
	$article['favorite'] = DB::getOne("SELECT favid FROM ".DB::table('home_favorite')." 
				WHERE uid='".$_G['uid']."' AND idtype='aid' AND id='$aid'");
				
	//此文章是否已分享
	$article['share'] = DB::getOne("SELECT sid FROM ".DB::table('home_share')." 
				WHERE uid='".$_G['uid']."' AND type='article' AND itemid='$aid'");			
}


//更新浏览数量
DB::query("UPDATE ".DB::table('portal_article_count')." SET catid='$article[catid]', dateline='$article[dateline]', viewnum=viewnum+1 WHERE aid='$aid'");

jsonexit($article);

?>