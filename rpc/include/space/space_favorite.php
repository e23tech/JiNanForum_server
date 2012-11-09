<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: space_favorite.php 22215 2011-04-26 06:51:46Z monkey $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$space = getspace($_G['uid']);

$page = empty($_GET['page'])?1:intval($_GET['page']);
if($page<1) $page=1;
$id = empty($_GET['id'])?0:intval($_GET['id']);

$perpage = 10;

$start = ($page-1)*$perpage;
ckstart($start, $perpage);

$idtypes = array('thread'=>'tid', 'forum'=>'fid', 'blog'=>'blogid', 'group'=>'gid', 'album'=>'albumid', 'space'=>'uid', 'article'=>'aid');
$_GET['type'] = isset($idtypes[$_GET['type']]) ? $_GET['type'] : 'all';
$actives[$_GET['type']] = ' class="a"';

$gets = array(
	'mod' => 'space',
	'uid' => $space['uid'],
	'do' => 'favorite',
	'type' => $_GET['type'],
	'from' => $_GET['from']
);
$theurl = 'home.php?'.url_implode($gets);


$wherearr = $list = array();
$favid = empty($_GET['favid'])?0:intval($_GET['favid']);
if($favid) {
	$wherearr[] = "favid='$favid'";
}
$wherearr[] = "uid='$_G[uid]'";
$idtype = isset($idtypes[$_GET['type']]) ? $idtypes[$_GET['type']] : '';
if($idtype) {
	$wherearr[] = "idtype='$idtype'";
}
$wheresql = implode(' AND ', $wherearr);

$count = DB::result(DB::query("SELECT COUNT(*) FROM ".DB::table('home_favorite')." WHERE $wheresql"),0);

if($count) {
	$query = DB::query("SELECT * FROM ".DB::table('home_favorite')."
		WHERE $wheresql
		ORDER BY dateline DESC
		LIMIT $start,$perpage");
	$icons = array(
		'tid'=>'<img src="static/image/feed/thread.gif" alt="thread" class="t" /> ',
		'fid'=>'<img src="static/image/feed/discuz.gif" alt="forum" class="t" /> ',
		'blogid'=>'<img src="static/image/feed/blog.gif" alt="blog" class="t" /> ',
		'gid'=>'<img src="static/image/feed/group.gif" alt="group" class="t" /> ',
		'uid'=>'<img src="static/image/feed/profile.gif" alt="space" class="t" /> ',
		'albumid'=>'<img src="static/image/feed/album.gif" alt="album" class="t" /> ',
		'aid'=>'<img src="static/image/feed/article.gif" alt="article" class="t" /> ',
	);
	while ($value = DB::fetch($query)) {
		//$value['icon'] = isset($icons[$value['idtype']]) ? $icons[$value['idtype']] : '';
		//$value['url'] = makeurl($value['id'], $value['idtype'], $value['spaceuid']);
		$value['description'] = !empty($value['description']) ? nl2br($value['description']) : '';
		
		if($value['idtype'] == 'albumid' && $value['id']) {
			
			$album = DB::getRow("SELECT username, picnum, pic, picflag FROM ".DB::table('home_album')." WHERE albumid='".$value['id']."'");
			
			if($album['picflag'] == 1) {
				$album['pic'] = $_G['siteurl'].$_G['setting']['attachurl'].'album/'.$album['pic'];	
			} elseif ($album['picflag'] == 2) {
				$album['pic'] = $_G['setting']['ftp']['attachurl'].'forum/'.$album['pic'];
			} 
			
			unset($album['picflag']);
			$value = array_merge($value, $album);
		}
		
		$list[$value['favid']] = $value;
	}
}

$multi = multi($count, $perpage, $page, $theurl);

dsetcookie('home_diymode', $diymode);

if(!$_GET['type']) {
	$_GET['type'] = 'all';
}
if($_GET['type'] == 'group') {
	$navtitle = lang('core', 'title_group_favorite', array('gorup' => $_G['setting']['navs'][3]['navname']));
} else {
	$navtitle = lang('core', 'title_'.$_GET['type'].'_favorite');
}


function makeurl($id, $idtype, $spaceuid=0) {
	$url = '';
	switch($idtype) {
		case 'tid':
			$url = 'forum.php?mod=viewthread&tid='.$id;
			break;
		case 'fid':
			$url = 'forum.php?mod=forumdisplay&fid='.$id;
			break;
		case 'blogid':
			$url = 'home.php?mod=space&uid='.$spaceuid.'&do=blog&id='.$id;
			break;
		case 'gid':
			$url = 'forum.php?mod=group&fid='.$id;
			break;
		case 'uid':
			$url = 'home.php?mod=space&uid='.$id;
			break;
		case 'albumid':
			$url = 'home.php?mod=space&uid='.$spaceuid.'&do=album&id='.$id;
			break;
		case 'aid':
			$url = 'portal.php?mod=view&aid='.$id;
			break;
	}
	return $url;
}


if(empty($list)){
	$msg = rpclang('home', 'no_favorite_yet');
	jsonexit("{\"status\":\"1\",\"message\":\"$msg\"}");
}else if('count' == $_GET['fav_type']){
	$idtypes = array('thread'=>'tid', 'blog'=>'blogid', 'group'=>'gid', 'album'=>'albumid');
	$total = count($idtypes);
	foreach ($idtypes as $key => $value){
		$wherearr = array();
		$wherearr[] = "uid='$_G[uid]'";
		$wherearr[] = "idtype='$value'";
		$wheresql = implode(' AND ', $wherearr);
		$jsonarr[$key] = DB::result(DB::query("SELECT COUNT(*) FROM ".DB::table('home_favorite')." WHERE $wheresql"),0);
	}
	
	jsonexit($jsonarr);
}else{
		foreach($list as $key => $value){
           $list[$key]['dateline'] = dgmdate($value['dateline'],'u');
           $list[$key]['url'] = creurl($value['id'], $value['idtype'], $value['spaceuid']);
           unset($list[$key]['icon']);
        }
        $jsonarr['list'] = $list;
        $jsonarr['page'] = $multi;
        /*MB add start*/
 		$jsonarr['zywy_curpage'] = $_G["zywy_curpage"];
		$jsonarr['zywy_totalpage'] = $_G["zywy_totalpage"];
 		/*MB add end*/
		
        jsonexit($jsonarr);
}

//收藏记录链接地址
function creurl($id, $idtype, $spaceuid=0) {
    $url = '';
    switch($idtype) {
	    case 'tid':
		    $url = '../forum/viewthread.html?tid='.$id;
		    break;
	    case 'fid':
		    $url = '../forum/forumdisplay.html?fid='.$id;
		    break;
	    case 'blogid':
		    $url = '../home/blog_view.html?uid='.$spaceuid.'&do=blog&id='.$id;
		    break;
	    case 'gid':
		    $url = '../group/group.html?fid='.$id;
		    break;
	    case 'uid':
		    $url = 'profile.html?uid='.$id;
		    break;
	    case 'albumid':
		    $url = '../home/album_view.html?uid='.$spaceuid.'&do=album&id='.$id;
		    break;
	    case 'aid':
		    $url = 'portal.php?mod=view&aid='.$id;
		    break;
    }
    return $url;
}