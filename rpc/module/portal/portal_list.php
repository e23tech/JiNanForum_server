<?php
/**
	文章列表
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$jsonarr = array();
$perpage = $_G['gp_perpage'] ? intval($_G['gp_perpage']) : 10;
$catid = max(1,intval($_G['gp_catid']));
$page = $_G['gp_page'] ? max(intval($_G['gp_page']), 1) : 1;
$start = ($page - 1) * $perpage;

$cids = getcatchildid($catid);
$cids[] = $catid;
$cids = implode(',', $cids);

//文章总数
$count = DB::getOne("SELECT COUNT(aid) FROM ".DB::table('portal_article_title')." 
					 WHERE ". " catid IN($cids)");

//栏目文章列表
$list = DB::getAll("SELECT t.aid, t.username, t.pic, t.title, t.dateline, c.content, ct.viewnum, ct.commentnum 
					FROM ".DB::table('portal_article_title')." AS t
					LEFT JOIN ".DB::table('portal_article_content')." AS c 
					ON t.aid=c.aid AND c.pageorder=1 
					LEFT JOIN ".DB::table('portal_article_count')." AS ct 
					ON t.aid=ct.aid 
					WHERE ". ($catid ? "t.catid IN($cids)" : "1") . " ORDER BY t.aid DESC LIMIT ".$start.','.$perpage);
										
foreach($list as $key=>$row) {
	$row['content'] = preg_replace('/<.*?>|\[.*?\]|\r\n/', '', $row['content']);
	$row['content'] = cutstr($row['content'], 100);
	$row['dateline'] = dgmdate($row['dateline'], 'u');
	$row['commentnum'] = DB::getOne("SELECT COUNT(cid) FROM ".DB::table('portal_comment') . "
						WHERE id='$row[aid]' AND idtype='aid'");
						
	$list[$key] = $row;					
}

$jsonarr['0'] = $list; //栏目文章列表
$jsonarr['catid'] = $_GET['catid'];
$jsonarr['zywy_curpage'] = $page;
$jsonarr['zywy_totalpage'] = max(ceil($count / $perpage), 1);

jsonexit($jsonarr);

//获取二级栏目和三级栏目ID
function getcatchildid($catid) {
	$list = $ids = DB::getCol("SELECT catid FROM ".DB::table('portal_category')." 
					 WHERE upid='$catid'");
	
	foreach($list as $catid) {
		$id = DB::getOne("SELECT catid FROM ".DB::table('portal_category')." 
					 WHERE upid='$catid'");
		if($id) {
			$ids[] = $id;
		}			 
	}
	return $ids;
}

?>