<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: space_blog.php 21922 2011-04-18 02:41:54Z zhengqingpeng $
 */

$minhot = $_G['setting']['feedhotmin']<1?3:$_G['setting']['feedhotmin'];
$page = empty($_GET['page'])?1:intval($_GET['page']);
if($page<1) $page=1;
$id = empty($_GET['id'])?0:intval($_GET['id']);
$_G['colorarray'] = array('', '#EE1B2E', '#EE5023', '#996600', '#3C9D40', '#2897C5', '#2B65B7', '#8F2A90', '#EC1282');

if($id) {
	$query = DB::query("SELECT bf.message, hbf.longitude, hbf.latitude, hbf.device, hbf.address, b.blogid, b.uid, b.username, b.subject, b.viewnum, b.replynum, b.password, b.picflag, b.status, b.dateline FROM ".DB::table('home_blog')." b 
				LEFT JOIN ".DB::table('home_blogfield')." bf 
				ON bf.blogid=b.blogid 
				LEFT JOIN ".DB::table('zywx_home_blogfield')." hbf 
				ON hbf.blogid=b.blogid 
				WHERE b.blogid='$id' AND b.uid='$space[uid]'");
	$blog = DB::fetch($query);
	
	if(!(!empty($blog) && ($blog['status'] == 0 || $blog['uid'] == $_G['uid'] || $_G['adminid'] == 1 || $_G['gp_modblogkey'] == modauthkey($blog['blogid'])))) {
		$msg = lang('message', 'view_to_info_did_not_exist');
		jsonexit("{\"status\":\"0\",\"message\":\"$msg\"}");
	}
	
	if(!ckfriend($blog['uid'], $blog['friend'], $blog['target_ids'])) {
		require_once libfile('function/friend');
		$isfriend = friend_check($blog['uid']);
		space_merge($space, 'count');
		space_merge($space, 'profile');
		$_G['privacy'] = 1;
		$msg = rpclang('home', 'no_privilege_look');
		$msg = str_replace('%x', $space['username'], $msg);
		
		jsonexit("{\"status\":\"0\",\"message\":\"$msg\", \"status\":\"-1\",\"back\":\"1\"}");
		
	} elseif(!$space['self'] && $blog['friend'] == 4 && $_G['adminid'] != 1) {
		$cookiename = "view_pwd_blog_$blog[blogid]";
		$cookievalue = empty($_G['cookie'][$cookiename])?'':$_G['cookie'][$cookiename];
		if($cookievalue != md5(md5($blog['password']))) {
			$invalue = $blog;
			$msg = rpclang('home', 'enter_password');
			
			//是否需要输入密码才能浏览
			if($blog['password'] && $_G['uid'] != $album['uid'] ) {
				$jsonarr['password'] = $blog['password'];
			}
		}
	}

	$query = DB::query("SELECT classid, classname FROM ".DB::table('home_class')." WHERE classid='$blog[classid]'");
	$classarr = DB::fetch($query);

	if($blog['catid']) {
		$blog['catname'] = DB::result(DB::query("SELECT catname FROM ".DB::table('home_blog_category')." WHERE catid='$blog[catid]'"), 0);
		$blog['catname'] = dhtmlspecialchars($blog['catname']);
	}

	require_once libfile('function/blog');
	$blog['message'] = blog_bbcode($blog['message']);
	
	//链接转到移动开放平台对应的页面
	$blog['message'] = preg_replace('/href=["|\'](?!http:)(.*?)["|\']/i', 'href="'.$_G['siteurl'].'\\1"', $blog['message']);
	$blog['message'] = preg_replace('/<a.*?href=["|\'](.*?)["|\']/i', '<a href="http://gate.baidu.com/?src='.'$1"', $blog['message']);
	$imgarg = ($_G['gp_width']?'&width='.$_G['gp_width']:'').($_G['gp_height']?'&height='.$_G['gp_height']:'');	
	$blog['message'] = preg_replace_callback('/<img.*?(src|file)=["\'](.*?)[&?"\'].*?>/i', "zy_parse_img", $blog['message']);
	$blog['message'] = preg_replace('/<(?!(p|\/p|br|img|embed|\/embed|a|\/a))[^>]*?>/si', '', $blog['message']);
	$otherlist = $newlist = array();

	$otherlist = array();
	$query = DB::query("SELECT * FROM ".DB::table('home_blog')." WHERE uid='$space[uid]' ORDER BY dateline DESC LIMIT 0,6");
	while ($value = DB::fetch($query)) {
		if($value['blogid'] != $blog['blogid'] && empty($value['friend'])) {
			$otherlist[] = $value;
		}
	}

	$newlist = array();
	$query = DB::query("SELECT * FROM ".DB::table('home_blog')." WHERE hot>='$minhot' ORDER BY dateline DESC LIMIT 0,6");
	while ($value = DB::fetch($query)) {
		if($value['blogid'] != $blog['blogid'] && empty($value['friend'])) {
			$newlist[] = $value;
		}
	}

	$perpage = 10;
	$perpage = mob_perpage($perpage);

	$start = ($page-1)*$perpage;

	ckstart($start, $perpage);

	$count = $blog['replynum'];

	$list = array();
	if($count) {
		$csql = '';
		if($_GET['goto']) {
			$page = ceil($count/$perpage);
			$start = ($page-1)*$perpage;
		} else {
			$cid = empty($_GET['cid'])?0:intval($_GET['cid']);
			$csql = $cid?"cid='$cid' AND":'';
		}

		$query = DB::query("SELECT * FROM ".DB::table('home_comment')." WHERE $csql id='$id' AND idtype='blogid' ORDER BY dateline LIMIT $start,$perpage");
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
			$value['message'] =  preg_replace("~src=\"(?!http:)(\w+)~i", 'src="'.getsiteurl().'\\1', $value['message']);
			$list[] = $value;
		}

		if(empty($list) && empty($cid)) {
			$count = getcount('home_comment', array('id'=>$id, 'idtype'=>'blogid'));
			DB::update('home_blog', array('replynum'=>$count), array('blogid'=>$blog['blogid']));
		}
	}

	$multi = multi($count, $perpage, $page, "blog_view.html?mod=space&uid=$blog[uid]&do=$do&id=$id");
	
	/*MB add start*/
	$jsonarr['zywy_curpage'] = $_G["zywy_curpage"];
	$jsonarr['zywy_totalpage'] = $_G["zywy_totalpage"];
	/*MB add end*/

	if(!$_G['setting']['preventrefresh'] || (!$space['self'] && $_G['cookie']['view_blogid'] != $blog['blogid'])) {
		DB::query("UPDATE ".DB::table('home_blog')." SET viewnum=viewnum+1 WHERE blogid='$blog[blogid]'");
		dsetcookie('view_blogid', $blog['blogid']);
	}

	$hash = md5($blog['uid']."\t".$blog['dateline']);
	$id = $blog['blogid'];
	$idtype = 'blogid';

	$maxclicknum = 0;
	loadcache('click');
	$clicks = empty($_G['cache']['click']['blogid'])?array():$_G['cache']['click']['blogid'];

	foreach ($clicks as $key => $value) {
		$value['clicknum'] = $blog["click{$key}"];
		$value['classid'] = mt_rand(1, 4);
		if($value['clicknum'] > $maxclicknum) $maxclicknum = $value['clicknum'];
		$clicks[$key] = $value;
	}

	$clickuserlist = array();
	$query = DB::query("SELECT * FROM ".DB::table('home_clickuser')."
		WHERE id='$id' AND idtype='$idtype'
		ORDER BY dateline DESC
		LIMIT 0,24");
	while ($value = DB::fetch($query)) {
		$value['clickname'] = $clicks[$value['clickid']]['name'];
		$clickuserlist[] = $value;
	}
	$actives = array('me' =>' class="a"');

	$diymode = intval($_G['cookie']['home_diymode']);

	$tagarray_all = $array_temp = $blogtag_array = $blogmetatag_array = array();
	$blogmeta_tag = '';
	$tagarray_all = explode("\t", $blog['tag']);
	if($tagarray_all) {
		foreach($tagarray_all as $var) {
			if($var) {
				$array_temp = explode(',', $var);
				$blogtag_array[] = $array_temp;
				$blogmetatag_array[] = $array_temp['1'];
			}
		}
	}
	$blog['tag'] = $blogtag_array;
	$blogmeta_tag = implode(',', $blogmetatag_array);

	$summary = cutstr(strip_tags($blog['message']), 140);
	$seodata = array('subject' => $blog['subject'], 'user' => $blog['username'], 'summary' => $summary, 'tags' => $blogmeta_tag);
	list($navtitle, $metadescription, $metakeywords) = get_seosetting('blog', $seodata);
	if(empty($navtitle)) {
		$navtitle = $blog['subject'].' - '.lang('space', 'sb_blog', array('who' => $blog['username']));
		$nobbname = false;
	} else {
		$nobbname = true;
	}
	if(empty($metakeywords)) {
		$metakeywords = $blogmeta_tag ? $blogmeta_tag : $blog['subject'];
	}
	if(empty($metadescription)) {
		$metadescription = $summary;
	}

	$_G['relatedlinks'] = getrelatedlink('blog');
	/******************mxg*************/
	$blog['photo'] = avatar($blog['uid'],'small');
	$blog['dateline'] = dgmdate($blog['dateline']);
	
	/*
	if (! $_GET['type']) {
		if(strlen(strip_tags($blog['message'])) > 400) {
			$blog['message'] = cutstr(strip_tags($blog['message']), 400);
		}
	}
	*/
	$blog['message'] = preg_replace("[src=\"(?!http:)(\w+)]i", 'src="'.getsiteurl().'\\1', $blog['message']);
	
//	//LLX-过滤站长发布信息	  
//	$pattern = '/(【.*易站长插件.*】)+/';
//	if(preg_match($pattern,$blog['message']))
//	{
//		$blog['publisher'] = true;
//		$blog['message'] = preg_replace($pattern,'',$blog['message']);
//	}
//	//LLX-判断是否有经纬度信息
//	$pattern = "/\[jw:((-?\d+)(\.\d+)?)\|((-?\d+)(\.\d+)?)\]/i";
//	if(preg_match($pattern,$blog['message'],$matches))
//	{
//		$blog['latlng'] =  $matches[4].','.$matches[1];
//		$blog['message'] = preg_replace($pattern,'',$blog['message']);
//	}
	
	//检索是否收藏
	if($favorite_id = DB::getOne('SELECT favid FROM '.DB::table('home_favorite')." WHERE uid='$_G[uid]' AND idtype='blogid' AND id='$id'")) {
		$jsonarr['is_favorite'] = true;
		$jsonarr['favorite_id'] = $favorite_id;
	}
	
   //检索是否分享
	if(DB::result_first("SELECT COUNT(*) FROM ".DB::table('home_share')." WHERE uid='$_G[uid]' AND itemid='$id' AND type='blog'")) {
		$jsonarr['is_share'] = true;
	}
	$jsonarr['back_url'] = "blog_view.html?mod=space&uid=$blog[uid]&do=$do&id=$id";
	$jsonarr['title'] = cutstr(strip_tags($blog['subject']), 40);
	
	$jsonarr['blog'] = $blog;
} else {

	loadcache('blogcategory');
	$category = $_G['cache']['blogcategory'];

	if(empty($_G['gp_view'])) $_G['gp_view'] = 'we';

	$perpage = 10;
	$perpage = mob_perpage($perpage);
	$start = ($page-1)*$perpage;
	ckstart($start, $perpage);

	$summarylen = 300;

	$classarr = array();
	$list = array();
	$userlist = array();
	$stickblogs = array();
	$count = $pricount = 0;

	$gets = array(
		'mod' => 'space',
		'uid' => $space['uid'],
		'do' => 'blog',
		'view' => $_G['gp_view'],
		'order' => $_GET['order'],
		'classid' => $_GET['classid'],
		'catid' => $_GET['catid'],
		'clickid' => $_GET['clickid'],
		'fuid' => $_GET['fuid'],
		'searchkey' => $_GET['searchkey'],
		'from' => $_GET['from'],
		'friend' => $_GET['friend']
	);
	$theurl = 'blog.html?'.url_implode($gets);
	$multi = '';

	$wheresql = '1';
	$f_index = '';
	$ordersql = 'b.dateline DESC';
	$need_count = true;

	if($_G['gp_view'] == 'all') {
		if($_GET['order'] == 'hot') {
			$wheresql .= " AND b.hot>='$minhot'";

			$orderactives = array('hot' => ' class="a"');
		} else {
			$orderactives = array('dateline' => ' class="a"');
		}

	} elseif($_G['gp_view'] == 'me') {

		space_merge($space, 'field_home');
		$stickblogs = explode(',', $space['stickblogs']);
		$stickblogs = array_filter($stickblogs);
		$wheresql .= " AND b.uid='$space[uid]'";

		$classid = empty($_GET['classid'])?0:intval($_GET['classid']);
		if($classid) {
			$wheresql .= " AND b.classid='$classid'";
		}

		$privacyfriend = empty($_G['gp_friend'])?0:intval($_G['gp_friend']);
		if($privacyfriend) {
			$wheresql .= " AND b.friend='$privacyfriend'";
		}
		$query = DB::query("SELECT classid, classname FROM ".DB::table('home_class')." WHERE uid='$space[uid]'");
		while ($value = DB::fetch($query)) {
			$classarr[$value['classid']] = $value['classname'];
		}

		if($_GET['from'] == 'space') $diymode = 1;

	} else {

		space_merge($space, 'field_home');

		if($space['feedfriend']) {

			$fuid_actives = array();

			require_once libfile('function/friend');
			$fuid = intval($_G['gp_uid']);
			//if($fuid && friend_check($fuid, $space['uid'])) {
			if($fuid) {
				$wheresql = "b.uid='$fuid'";
				$fuid_actives = array($fuid=>' selected');
			} else {
				$wheresql = "b.uid IN ($space[feedfriend])";
				$theurl = "home.php?mod=space&uid=$space[uid]&do=$do&view=we";
				$f_index = 'USE INDEX(dateline)';
			}

			$query = DB::query("SELECT * FROM ".DB::table('home_friend')." WHERE uid='$space[uid]' ORDER BY num DESC LIMIT 0,100");
			while ($value = DB::fetch($query)) {
				$userlist[] = $value;
			}
		} else {
			//$need_count = false;
			if($_G['gp_uid']) {
				$wheresql = "b.uid='".$_G['gp_uid']."'";
			}
			
		}
	}

	if($need_count) {
		if($searchkey = stripsearchkey($_GET['searchkey'])) {
			$wheresql .= " AND b.subject LIKE '%$searchkey%'";
			$searchkey = dhtmlspecialchars($searchkey);
		}

		$catid = empty($_GET['catid'])?0:intval($_GET['catid']);
		if($catid) {
			$wheresql .= " AND b.catid='$catid'";
		}

		$count = DB::result(DB::query("SELECT COUNT(*) FROM ".DB::table('home_blog')." b WHERE $wheresql"),0);
		if($count) {
			$query = DB::query("SELECT bf.message, hbf.longitude, hbf.latitude, hbf.device, hbf.address, b.blogid, b.uid, b.username, b.subject, b.viewnum, b.replynum, b.password, b.status, b.picflag, b.dateline FROM ".
				DB::table('home_blog')." b $f_index
				LEFT JOIN ".DB::table('home_blogfield')." bf 
				ON bf.blogid=b.blogid
				LEFT JOIN ".DB::table('zywx_home_blogfield')." hbf 
				ON hbf.blogid=b.blogid 
				WHERE $wheresql
				ORDER BY $ordersql LIMIT $start,$perpage");
		}
	}
	
	if($count) {
		while ($value = DB::fetch($query)) {
			if(ckfriend($value['uid'], $value['friend'], $value['target_ids']) && ($value['status'] == 0 || $value['uid'] == $_G['uid'] || $_G['adminid'] == 1)) {
				if(!empty($stickblogs) && in_array($value['blogid'], $stickblogs)) {
					continue;
				}
				
//				//LLX-过滤站长发布信息
//				$value['message'] = preg_replace('/(【.*易站长插件.*】)+/','',$value['message']);
//				//LLX-判断是否有经纬度信息
//				if(preg_match("/\[jw:((-?\d+)(\.\d+)?)\|((-?\d+)(\.\d+)?)\]/i",$value['message']))
//				{
//					$value['latlng'] = true;
//				}
				
				if($value['friend'] == 4) {
					$value['message'] = $value['pic'] = '';
				} else {
					$value['message'] = getstr($value['message'], $summarylen, 0, 0, 0, -1);
				}
				
				$value['message'] = preg_replace("/&[a-z]+\;/i", '', $value['message']);
				if($value['pic']) $value['pic'] = pic_cover_get($value['pic'], $value['picflag']);
				$value['dateline'] = dgmdate($value['dateline']);
				$value['subject'] = cutstr(strip_tags($value['subject']), 40);
				$value['message'] = cutstr(strip_tags($value['message']), 100);
				$value['photo'] = avatar($value['uid'],'small');
				$value['pwd'] =  $_G['cookie']['view_pwd_blog_'.$value['blogid']];
				$list[] = $value;
			} else {
				$pricount++;
			}
		}
		
		$multi = multi($count, $perpage, $page, $theurl);
		if(!empty($stickblogs)) {
			$list = array_merge(blog_get_stick($space['uid'], $stickblogs, $summarylen), $list);
		}
	}


	/*MB add start*/
	$jsonarr['zywy_curpage'] = $_G["zywy_curpage"];
	$jsonarr['zywy_totalpage'] = $_G["zywy_totalpage"];
	/*MB add end*/
	
	dsetcookie('home_diymode', $diymode);

	space_merge($space, 'field_home');

}

function blog_get_stick($uid, $stickblogs, $summarylen) {
	$list = array_flip($stickblogs);
	$stickblogs = dimplode($stickblogs);
	if($stickblogs) {
		$query = DB::query("SELECT bf.message, hbf.longitude, hbf.latitude, hbf.device, hbf.address, b.blogid, b.uid, b.username, b.subject, b.viewnum, b.replynum, b.password, b.picflag, b.dateline FROM ".DB::table('home_blog')." b $f_index
			LEFT JOIN ".DB::table('home_blogfield')." bf 
			ON bf.blogid=b.blogid
			LEFT JOIN ".DB::table('zywx_home_blogfield')." hbf 
			ON hbf.blogid=b.blogid
			WHERE b.blogid IN ($stickblogs)");
		while ($value = DB::fetch($query)) {
			$value['message'] = getstr($value['message'], $summarylen, 0, 0, 0, -1);
			$value['message'] = preg_replace("/&[a-z]+\;/i", '', $value['message']);
			if($value['pic']) $value['pic'] = pic_cover_get($value['pic'], $value['picflag']);
			$value['dateline'] = dgmdate($value['dateline']);
			$value['stickflag'] = true;
			$list[$value['blogid']] = $value;
		}
	}
	return $list;
}
$spacenote = DB::getOne("SELECT spacenote FROM ".DB::table('common_member_field_home')." WHERE uid=".$space['uid']);
$jsonarr['spacenote'] = preg_replace("~src=\"(?!http:)(\w+)~i", 'src="'.getsiteurl().'\\1', $spacenote);
$jsonarr['pricount'] = $pricount;
$jsonarr['list'] = $list;
$jsonarr['classarr'] = $classarr;
$jsonarr['ucurl'] = $_G["setting"]['ucenterurl'];

jsonexit($jsonarr);

?>