<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: space_album.php 20784 2011-03-03 10:27:32Z svn_project_zhangjie $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$minhot = $_G['setting']['feedhotmin']<1?3:$_G['setting']['feedhotmin'];
$id = empty($_GET['id'])?0:intval($_GET['id']);
$picid = empty($_GET['picid'])?0:intval($_GET['picid']);

$page = empty($_GET['page'])?1:intval($_GET['page']);
if($page<1) $page=1;

if($id) {
	$perpage = 9;
	$perpage = mob_perpage($perpage);

	$start = ($page-1)*$perpage;

	ckstart($start, $perpage);

	if($id > 0) {
		$query = DB::query("SELECT * FROM ".DB::table('home_album')." WHERE albumid='$id' AND uid='$space[uid]' LIMIT 1");
		$album = DB::fetch($query);
	
		if(empty($album)) {
			$msg = lang('message', 'to_view_the_photo_does_not_exist');
			jsonexit("{\"status\":\"-2\",\"message\":\"$msg\"}");
		}
	
		//是否需要输入密码才能浏览
		/*if($album['password'] && $_G['uid'] != $album['uid'] ) {
			$jsonarr['password'] = 1;
		}*/

		ckfriend_album($album);

		$wheresql = "albumid='$id'";

		$album['picnum'] = $count = DB::result_first("SELECT COUNT(*) FROM ".DB::table('home_pic')." WHERE albumid='$id'");

		if(empty($count) && !$space['self']) {
			DB::query("DELETE FROM ".DB::table('home_album')." WHERE albumid='$id'");
			$msg = lang('message', 'to_view_the_photo_does_not_exist');
			jsonexit("{\"status\":\"-2\",\"message\":\"$msg\"}");
			
		}

		if($album['catid']) {
			$album['catname'] = DB::result(DB::query("SELECT catname FROM ".DB::table('home_album_category')." WHERE catid='$album[catid]'"), 0);
			$album['catname'] = dhtmlspecialchars($album['catname']);
		}

	} else {
		$wheresql = "albumid='0' AND uid='$space[uid]'";
		$count = getcount('home_pic', array('albumid'=>0, 'uid'=>$space['uid']));

		$album = array(
			'uid' => $space['uid'],
			'albumid' => -1,
			'albumname' => lang('space', 'default_albumname'),
			'picnum' => $count
		);
	}

	$albumlist = array();
	$maxalbum = $nowalbum = $key = 0;
	$query = DB::query("SELECT * FROM ".DB::table('home_album')." WHERE uid='$space[uid]' ORDER BY updatetime DESC LIMIT 0, 100");
	while ($value = DB::fetch($query)) {
		if($value['friend'] != 4 && ckfriend($value['uid'], $value['friend'], $value['target_ids'])) {
			$value['pic'] = pic_cover_get($value['pic'], $value['picflag']);
		} elseif ($value['picnum']) {
			$value['pic'] = STATICURL.'image/common/nopublish.gif';
		} else {
			$value['pic'] = '';
		}
		$albumlist[$key][$value['albumid']] = $value;
		$key = count($albumlist[$key]) == 5 ? ++$key : $key;
	}
	$maxalbum = count($albumlist);

	$list = array();
	$pricount = 0;
	if($count) {
		$query = DB::query("SELECT * FROM ".DB::table('home_pic')." WHERE $wheresql ORDER BY dateline DESC LIMIT $start,$perpage");
		while ($value = DB::fetch($query)) {
			if($value['status'] == 0 || $value['uid'] == $_G['uid'] || $_G['adminid'] == 1) {
			
				if(!preg_match('/^http:\/\//', $value['pic'])) {
					$value['pic'] = pic_get($value['filepath'], 'album', $value['thumb'], $value['remote']);
				}
				
				if(!preg_match('/^http:\/\//', $value['pic'])) {
					if($value['remote'] == '0') {
						$value['pic'] = getsiteurl().$value['pic'];
					}
				}
				
				if(!preg_match('/^http:\/\//', $value['pic'])) {
					$value['pic'] = $_G['siteurl'].$value['pic'];
				}
				
				$list[] = $value;
			} else {
				$pricount++;
			}
		}
	}

	$multi = multi($count, $perpage, $page, "album_view.html?mod=space&uid=$album[uid]&do=$do&id=$id");
	/*MB add start*/
	$jsonarr['zywy_curpage'] = $_G["zywy_curpage"];
	$jsonarr['zywy_totalpage'] = $_G["zywy_totalpage"];
	/*MB add end*/
	
	$actives = array('me' =>' class="a"');

	$_G['home_css'] = 'album';

	$diymode = intval($_G['cookie']['home_diymode']);

	$seodata = array('album' => $album['albumname'], 'user' => $album['username'], 'depict' => $album['depict']);
	list($navtitle, $metadescription, $metakeywords) = get_seosetting('album', $seodata);
	if(empty($navtitle)) {
		$navtitle = $album['albumname'].' - '.lang('space', 'sb_album', array('who' => $album['username']));
		$nobbname = false;
	} else {
		$nobbname = true;
	}
	if(empty($metakeywords)) {
		$metakeywords = $album['albumname'];
	}
	if(empty($metadescription)) {
		$metadescription = $album['albumname'];
	}
	
	//检索是否收藏
	if($favorite_id = DB::getOne('SELECT favid FROM '.DB::table('home_favorite')." WHERE uid='$_G[uid]' AND idtype='albumid' AND id='$id'")) {
		$jsonarr['is_favorite'] = true;
		$jsonarr['favorite_id'] = $favorite_id;
	}
	
	//检索是否分享
	if(DB::result_first("SELECT COUNT(*) FROM ".DB::table('home_share')." WHERE uid='$_G[uid]' AND itemid='$id' AND type='album'")) {
		$jsonarr['is_share'] = true;
	}
	
	//unset($album['password']);
	if($album['password']) $album['password'] = 1;
	
	$jsonarr['title'] = cutstr(strip_tags($album['albumname']), 40);
	$jsonarr['album'] = $album;

} elseif ($picid) {

	$query = DB::query("SELECT * FROM ".DB::table('home_pic')." WHERE picid='$picid' AND uid='$space[uid]' LIMIT 1");
	$pic = DB::fetch($query);
	if(!$pic || ($pic['status'] == 1 && $pic['uid'] != $_G['uid'] && $_G['adminid'] != 1 && $_G['gp_modpickey'] != modauthkey($pic['picid']))) {
		$msg = lang('message', 'view_images_do_not_exist');
		jsonexit("{\"status\":\"-2\",\"message\":\"$msg\"}");
	}

	$picid = $pic['picid'];
	$theurl = "album_pic.html?mod=space&uid=$pic[uid]&do=$do&picid=$picid";

	$album = array();
	if($pic['albumid']) {
		$query = DB::query("SELECT * FROM ".DB::table('home_album')." WHERE albumid='$pic[albumid]'");
		if(!$album = DB::fetch($query)) {
			DB::update('home_pic', array('albumid'=>0), array('albumid'=>$pic['albumid']));
		}
	}

	if($album) {
		ckfriend_album($album);
		$wheresql = "albumid='$pic[albumid]'";
	} else {
		$album['picnum'] = getcount('home_pic', array('uid'=>$pic['uid'], 'albumid'=>0));
		$album['albumid'] = $pic['albumid'] = '-1';
		$wheresql = "uid='$space[uid]' AND albumid='0'";
	}

	$piclist = $list = $keys = array();
	$keycount = 0;
	$query = DB::query("SELECT * FROM ".DB::table('home_pic')." WHERE $wheresql ORDER BY dateline DESC");
	while ($value = DB::fetch($query)) {
		if($value['status'] == 0 || $value['uid'] == $_G['uid'] || $_G['adminid'] == 1) {
			$keys[$value['picid']] = $keycount;
			$list[$keycount] = $value;
			$keycount++;
		}
	}

	$upid = $nextid = 0;
	$nowkey = $keys[$picid];
	$endkey = $keycount - 1;
	if($endkey>4) {
		$newkeys = array($nowkey-2, $nowkey-1, $nowkey, $nowkey+1, $nowkey+2);
		if($newkeys[1] < 0) {
			$newkeys[0] = $endkey-1;
			$newkeys[1] = $endkey;
		} elseif($newkeys[0] < 0) {
			$newkeys[0] = $endkey;
		}
		if($newkeys[3] > $endkey) {
			$newkeys[3] = 0;
			$newkeys[4] = 1;
		} elseif($newkeys[4] > $endkey) {
			$newkeys[4] = 0;
		}
		$upid = $list[$newkeys[1]]['picid'];
		$nextid = $list[$newkeys[3]]['picid'];

		foreach ($newkeys as $nkey) {
			$piclist[$nkey] = $list[$nkey];
		}
	} else {
		$newkeys = array($nowkey-1, $nowkey, $nowkey+1);
		if($newkeys[0] < 0) {
			$newkeys[0] = $endkey;
		}
		if($newkeys[2] > $endkey) {
			$newkeys[2] = 0;
		}
		$upid = $list[$newkeys[0]]['picid'];
		$nextid = $list[$newkeys[2]]['picid'];

		$piclist = $list;
	}
	foreach ($piclist as $key => $value) {
		$value['pic'] = pic_get($value['filepath'], 'album', $value['thumb'], $value['remote']);
		$piclist[$key] = $value;
	}

	$pic['pic'] = pic_get($pic['filepath'], 'album', $pic['thumb'], $pic['remote'], 0);
	$pic['size'] = formatsize($pic['size']);

	$exifs = array();
	$allowexif = function_exists('exif_read_data');
	if(isset($_GET['exif']) && $allowexif) {
		require_once libfile('function/exif');
		$exifs = getexif($pic['pic']);
	}

	if(!$_GET['goto']){
		$perpage = 10;
		$perpage = mob_perpage($perpage);

		$start = ($page-1)*$perpage;

		ckstart($start, $perpage);

		$cid = empty($_GET['cid'])?0:intval($_GET['cid']);
		$csql = $cid?"cid='$cid' AND":'';

		$siteurl = getsiteurl();
		$list = array();
		$count = DB::result(DB::query("SELECT COUNT(*) FROM ".DB::table('home_comment')." WHERE $csql id='$pic[picid]' AND idtype='picid'"),0);
		if($count) {
			$query = DB::query("SELECT * FROM ".DB::table('home_comment')." WHERE $csql id='$pic[picid]' AND idtype='picid' ORDER BY dateline LIMIT $start,$perpage");
			while ($value = DB::fetch($query)) {
				if ($value['author']) {
					$photo = avatar($value['authorid'],'small');
					$author = $value['author'];
				} else {
					$photo = '<img src="'.getsiteurl().STATICURL.'image/magic/hidden.gif" alt="hidden" />';
					$author = $_G['setting']['anonymoustext'];
				}
				$value['dateline'] = dgmdate($value['dateline']);
				$value['photo'] = $photo;
				$value['author'] = $author;
			
				if (! $cid) {
					if(strlen(strip_tags($value['message'])) > 100) {
						$value['message'] = cutstr(strip_tags($value['message']), 100);
					}
				}
				$value['message'] = preg_replace("~src=\"(?!http:)(\w+)~i", 'src="'.getsiteurl().'\\1', $value['message']);
				$list[] = $value;
			}
		}
		$multi = multi($count, $perpage, $page, $theurl);
		/*MB add start*/
 		$jsonarr['zywy_curpage'] = $_G["zywy_curpage"];
		$jsonarr['zywy_totalpage'] = $_G["zywy_totalpage"];
		$jsonarr['ucurl'] = $_G["setting"]['ucenterurl'];
		/*MB add end*/

	}else{
		$list = array();
	}
	
	if(empty($album['albumname'])) $album['albumname'] = lang('space', 'default_albumname');

	$pic_url = $pic['pic'];
	
	if(!preg_match("/^http\:\/\/.+?/i", $pic['pic'])) {
		$pic_url = $_G['siteurl'].'source/plugin/zywx/rpc/forum.php?mod=image&src='.getsiteurl().$pic['pic'];
	} else {
		$pic_url = $_G['siteurl'].'source/plugin/zywx/rpc/forum.php?mod=image&src='.$pic['pic'];
	}
	
	$pic_url .= 
			($_G['gp_width']?'&width='.$_G['gp_width']:'').
			($_G['gp_height']?'&height='.$_G['gp_height']:'');
	
	$pic_url2 = rawurlencode($pic['pic']);

	$hash = md5($pic['uid']."\t".$pic['dateline']);
	$id = $pic['picid'];
	$idtype = 'picid';

	$maxclicknum = 0;
	loadcache('click');
	$clicks = empty($_G['cache']['click']['picid'])?array():$_G['cache']['click']['picid'];
	foreach ($clicks as $key => $value) {
		$value['clicknum'] = $pic["click{$key}"];
		$value['classid'] = mt_rand(1, 4);
		if($value['clicknum'] > $maxclicknum) $maxclicknum = $value['clicknum'];
		$clicks[$key] = $value;
	}

	$clickuserlist = array();
	$query = DB::query("SELECT * FROM ".DB::table('home_clickuser')."
		WHERE id='$id' AND idtype='$idtype'
		ORDER BY dateline DESC
		LIMIT 0,20");
	while ($value = DB::fetch($query)) {
		$value['clickname'] = $clicks[$value['clickid']]['name'];
		$clickuserlist[] = $value;
	}

	$actives = array('me' =>' class="a"');

	if($album['picnum']) {
		$sequence = $nowkey + 1;
	}

	$diymode = intval($_G['cookie']['home_diymode']);
	
	$navtitle = $album['albumname'];
	if($pic['title']) {
		$navtitle = $pic['title'].' - '.$navtitle;
	}
	$metakeywords = $pic['title'] ? $pic['title'] : $album['albumname'];
	$metadescription = $pic['title'] ? $pic['title'] : $albumname['albumname'];

	if (!$pic['title']) {
		$pic['title'] = substr($pic['filename'], 0, strrpos($pic['filename'], '.'));
	}
	//检索是否分享
	if(DB::result_first("SELECT COUNT(*) FROM ".DB::table('home_share')." WHERE uid='$_G[uid]' AND itemid='$picid' AND type='pic'")) {
		$jsonarr['is_share'] = true;
	}
	
//	//LLX-过滤站长发布信息
//	$pattern = '/(【.*易站长插件.*】)+/';
//	if(preg_match($pattern,$pic['title']))
//	{
//		$pic['publisher'] = true;
//		$pic['title'] = preg_replace($pattern,'',$pic['title']);
//	}
	
	$pic['replynum'] = $count;
	$pic['pic'] = $pic_url;
	$jsonarr['title'] = cutstr(strip_tags(substr($pic['filename'], 0, strrpos($pic['filename'], '.'))), 40);
	$jsonarr['back_url'] = $theurl;
	$pic['dateline'] = date('y-m-d H:i:s', $pic['dateline']);
	$jsonarr['pic'] = $pic;
		
	//上一张图片
	$jsonarr['upid'] = $upid;
	//下一张图片
	$jsonarr['nextid'] = $nextid;
	
} else {

	loadcache('albumcategory');
	$category = $_G['cache']['albumcategory'];

	$perpage = 9;
	$perpage = mob_perpage($perpage);

	$start = ($page-1)*$perpage;

	ckstart($start, $perpage);

	$_GET['friend'] = intval($_GET['friend']);

	$default = array();
	$f_index = '';
	$list = array();
	$pricount = 0;
	$picmode = 0;

	if(empty($_GET['view'])) {
		$_GET['view'] = 'we';
	}

	$gets = array(
		'mod' => 'space',
		'uid' => $space['uid'],
		'do' => 'album',
		'view' => $_GET['view'],
		'catid' => $_GET['catid'],
		'order' => $_GET['order'],
		'fuid' => $_GET['fuid'],
		'searchkey' => $_GET['searchkey'],
		'from' => $_GET['from']
	);
	$theurl = 'album.html?'.url_implode($gets);
	$actives = array($_GET['view'] =>' class="a"');

	$need_count = true;

	if($_GET['view'] == 'all') {

		$wheresql = '1';

		/*if($_GET['order'] == 'hot') {
			$orderactives = array('hot' => ' class="a"');
			$picmode = 1;
			$need_count = false;

			$ordersql = 'p.dateline';
			$wheresql = "p.hot>='$minhot'";

			$count = DB::result(DB::query("SELECT COUNT(*) FROM ".DB::table('home_pic')." p WHERE $wheresql"),0);
			if($count) {
				$query = DB::query("SELECT p.*, a.albumname, a.friend, a.target_ids FROM ".DB::table('home_pic')." p
					LEFT JOIN ".DB::table('home_album')." a ON a.albumid=p.albumid
					WHERE $wheresql
					ORDER BY $ordersql DESC
					LIMIT $start,$perpage");
				while ($value = DB::fetch($query)) {
					if($value['friend'] != 4 && ckfriend($value['uid'], $value['friend'], $value['target_ids']) && ($value['status'] == 0 || $value['uid'] == $_G['uid'] || $_G['adminid'] == 1)) {
						$value['pic'] = pic_get($value['filepath'], 'album', $value['thumb'], $value['remote']);
						$list[] = $value;
					} else {
						$pricount++;
					}
				}
			}

		} else {
			$orderactives = array('dateline' => ' class="a"');
		}*/

	} elseif($_GET['view'] == 'we') {

		space_merge($space, 'field_home');

		if($space['feedfriend']) {

			$wheresql = "uid IN ($space[feedfriend])";
			$f_index = 'USE INDEX(updatetime)';

			$fuid_actives = array();

			require_once libfile('function/friend');
			$fuid = intval($_GET['fuid']);
			if($fuid && friend_check($fuid)) {
				$wheresql = "uid='$fuid'";
				$f_index = '';
				$fuid_actives = array($fuid=>' selected');
			}

			$query = DB::query("SELECT * FROM ".DB::table('home_friend')." WHERE uid='$space[uid]' ORDER BY num DESC, dateline DESC LIMIT 0,500");
			while ($value = DB::fetch($query)) {
				$userlist[] = $value;
			}
		} else {
			$need_count = false;
		}

	} else {

		if($_GET['from'] == 'space') $diymode = 1;

		$wheresql = "uid='$space[uid]'";
	}

	if($need_count) {

		if($searchkey = stripsearchkey($_GET['searchkey'])) {
			$wheresql .= " AND albumname LIKE '%$searchkey%'";
			$searchkey = dhtmlspecialchars($searchkey);
		}

		$catid = empty($_GET['catid'])?0:intval($_GET['catid']);
		if($catid) {
			$wheresql .= " AND catid='$catid'";
		}

		$count = DB::result(DB::query("SELECT COUNT(*) FROM ".DB::table('home_album')." WHERE $wheresql"),0);

		if($count) {
			$query = DB::query("SELECT * FROM ".DB::table('home_album')." $f_index WHERE $wheresql ORDER BY updatetime DESC LIMIT $start,$perpage");
			while ($value = DB::fetch($query)) {
				/*if($value['friend'] != 4 && ckfriend($value['uid'], $value['friend'], $value['target_ids'])) {
					$value['pic'] = pic_cover_get($value['pic'], $value['picflag']);
				} elseif ($value['picnum']) {
					$value['pic'] = STATICURL.'image/common/nopublish.gif';
				} else {
					$value['pic'] = '';
				}
				$list[] = $value;*/
				
				if($value['friend'] != 4 && ckfriend($value['uid'], $value['friend'], $value['target_ids'])) {
					$value['pic'] = pic_cover_get($value['pic'], $value['picflag']);
				} elseif ($value['picnum']) {
					$value['pic'] = STATICURL.'image/common/nopublish.gif';
				} else {
					$value['pic'] = '';
				}
				if ($value['pic']) {
					if (!is_numeric(stripos($value['pic'],'http://'))) {
						$value['pic'] = getsiteurl().$value['pic'];
					}
				}
				
				unset($value['password']);
				
				$value['updatetime'] = dgmdate($value['updatetime'], 'n-j H:i');
				//$value['pwd'] =  $_G['cookie']['view_pwd_album_'.$value['albumid']];
				$value['albumname'] = cutstr(strip_tags($value['albumname']), 40);
				$list[] = $value;
				
			}
		}
	}

	$multi = multi($count, $perpage, $page, $theurl);
	/*MB add start*/
	$jsonarr['zywy_curpage'] = $_G["zywy_curpage"];
	$jsonarr['zywy_totalpage'] = $_G["zywy_totalpage"];
	/*MB add end*/
		
	$jsonarr['default_album'] = getsiteurl().'static/image/common/nophoto.gif';
	
	dsetcookie('home_diymode', $diymode);

	if($_G['uid']) {
		if($_G['gp_view'] == 'all') {
			$navtitle = lang('core', 'title_view_all').lang('core', 'title_album');
		} elseif($_G['gp_view'] == 'me') {
			$navtitle = lang('core', 'title_my_album');
		} else {
			$navtitle = lang('core', 'title_friend_album');
		}
	} else {
		if($_G['gp_order'] == 'hot') {
			$navtitle = lang('core', 'title_hot_pic_recommend');
		} else {
			$navtitle = lang('core', 'title_newest_update_album');
		}
	}
	if($space['username']) {
		$navtitle = lang('space', 'sb_album', array('who' => $space['username']));
	}

	$metakeywords = $navtitle;
	$metadescription = $navtitle;
}

function ckfriend_album($album) {
	global $_G, $space;

	if($_G['adminid'] != 1) {
		if(!ckfriend($album['uid'], $album['friend'], $album['target_ids'])) {
			if(empty($_G['uid'])) {
				$msg = lang('message', 'to_login');
				jsonexit("{\"status\":\"-1\",\"message\":\"$msg\"}");
			}
			require_once libfile('function/friend');
			$isfriend = friend_check($album['uid']);
			space_merge($space, 'count');
			space_merge($space, 'profile');
			$_G['privacy'] = 1;
			$msg = rpclang('home', 'no_privilege_look');
			$msg = str_replace('%x', $space['username'], $msg);
			
			jsonexit("{\"message\":\"$msg\",\"status\":\"-2\", \"back\":\"1\"}");
			exit();
		} elseif(!$space['self'] && $album['friend'] == 4) {
			$cookiename = "view_pwd_album_$album[albumid]";
			$cookievalue = empty($_G['cookie'][$cookiename])?'':$_G['cookie'][$cookiename];
			if($cookievalue != md5(md5($album['password']))) {
				$invalue = $album;
				//$msg = rpclang('home', 'enter_password');
				jsonexit("{\"status\":\"-3\",\"message\":\"enter_password\"}");
			}
		}
	}
}

$spacenote = DB::getOne("SELECT spacenote FROM ".DB::table('common_member_field_home')." WHERE uid=".$space['uid']);
$jsonarr['spacenote'] = preg_replace("~src=\"(?!http:)(\w+)~i", 'src="'.getsiteurl().'\\1', $spacenote);
$jsonarr['list'] = $list;
//$jsonarr['page'] = $multi;
//print_r($jsonarr); exit;
jsonexit($jsonarr);

?>