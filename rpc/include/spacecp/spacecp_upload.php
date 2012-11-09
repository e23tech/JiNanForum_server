<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: spacecp_upload.php 22318 2011-04-29 09:34:15Z zhengqingpeng $
 */
//	ini_set('error_log', '/var/www/workspace/discuz/error.log');
//	ini_set('log_errors',3);
//	error_log("----------++++++++++++----------");
	
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}
$albumid = empty($_GET['albumid'])?0:intval($_GET['albumid']);
if($_GET['op'] == 'recount') {
	$newsize = DB::result(DB::query("SELECT SUM(size) FROM ".DB::table('home_pic')." WHERE uid='$_G[uid]'"), 0);
	DB::update('common_member_count', array('attachsize'=>$newsize), array('uid'=>$_G['uid']));
	//showmessage('do_success', 'home.php?mod=spacecp&ac=upload');
	$msg = lang('message', 'do_success');
	jsonexit("{\"status\":\"1\",\"message\":\"$msg\"}");
}

if(submitcheck('albumsubmit')) {
	
	if($_POST['albumop'] == 'creatalbum') {
		$_POST['albumname'] = empty($_POST['albumname'])?'':getstr($_POST['albumname'], 50, 1, 1);
		$_POST['albumname'] = censor($_POST['albumname'], NULL, TRUE);

		/*if(is_array($_POST['albumname']) && $_POST['albumname']['message']) {
			echo "<script>";
			echo "parent.showDialog('{$_POST['albumname']['message']}');";
			echo "</script>";
			exit();
		}*/

		if(empty($_POST['albumname'])) $_POST['albumname'] = gmdate('Ymd');

		$_POST['friend'] = intval($_POST['friend']);

		$_POST['target_ids'] = '';
		if($_POST['friend'] == 2) {
			$uids = array();
			$names = empty($_POST['target_names'])?array():explode(' ', str_replace(array(lang('spacecp', 'tab_space'), "\r\n", "\n", "\r"), ' ', $_POST['target_names']));
			if($names) {
				$query = DB::query("SELECT uid FROM ".DB::table('common_member')." WHERE username IN (".dimplode($names).")");
				while ($value = DB::fetch($query)) {
					$uids[] = $value['uid'];
				}
			}
			if(empty($uids)) {
				$_POST['friend'] = 3;
			} else {
				$_POST['target_ids'] = implode(',', $uids);
			}
		} elseif($_POST['friend'] == 4) {
			$_POST['password'] = trim($_POST['password']);
			if($_POST['password'] == '') $_POST['friend'] = 0;
		}
		if($_POST['friend'] !== 2) {
			$_POST['target_ids'] = '';
		}
		if($_POST['friend'] !== 4) {
			$_POST['password'] = '';
		}

		$setarr = array();
		$setarr['albumname'] = $_POST['albumname'];
		$setarr['catid'] = intval($_POST['catid']);
		$setarr['uid'] = $_G['uid'];
		$setarr['username'] = $_G['username'];
		$setarr['dateline'] = $setarr['updatetime'] = $_G['timestamp'];
		$setarr['friend'] = $_POST['friend'];
		$setarr['password'] = $_POST['password'];
		$setarr['target_ids'] = $_POST['target_ids'];
		$setarr['depict'] = dhtmlspecialchars($_POST['depict']);

		$albumid = DB::insert('home_album', $setarr, 1);

		if($setarr['catid']) {
			DB::query("UPDATE ".DB::table('home_album_category')." SET num=num+1 WHERE catid='$setarr[catid]'");
		}

		if(empty($space['albumnum'])) {
			$space['albums'] = getcount('home_album', array('uid'=>$space['uid']));
			$albumnumsql = "albums=".$space['albums'];
		} else {
			$albumnumsql = 'albums=albums+1';
		}
		DB::query("UPDATE ".DB::table('common_member_count')." SET {$albumnumsql} WHERE uid='$_G[uid]'");

	} else {
		$albumid = intval($_POST['albumid']);
	}

	/*if($_G['mobile']) {
		//showmessage('do_success', 'home.php?mod=spacecp&ac=upload');
		$msg = lang('message', 'do_success');
		jsonexit("{'status':'1','message':'$msg'}");
	} else {
		echo "<script>";
		echo "parent.no_insert = 1;";
		echo "parent.albumid = $albumid;";
		echo "parent.start_upload();";
		echo "</script>";
	}
	exit();*/
	
	$jsonarr['albumid'] = $albumid;
	jsonexit($jsonarr);
	

} elseif(submitcheck('uploadsubmit')) {
	$albumid = $picid = 0;
	if(!checkperm('allowupload')) {
		exit('-1');
	}
	
	if($_GET['type'] == 'phone_album'){
		//创建手机相册
		$query = DB::fetch_first("SELECT albumid FROM ".DB::table('home_album')." WHERE isPhone = '1' AND uid= ".$_G['uid']);
		if($query){
			$albumid = $query['albumid'];
		}else{
			$setarr = array();
			$setarr['albumname'] = rpclang('home','phone_albumname');
			if(CHARSET != 'utf-8') {
				$setarr['albumname'] = utf8togbk($setarr['albumname']);
			}
			$setarr['catid'] = 0;
			$setarr['uid'] = $_G['uid'];
			$setarr['username'] = $_G['username'];
			$setarr['dateline'] = $setarr['updatetime'] = $_G['timestamp'];
			$setarr['friend'] = 0;
			$setarr['depict'] = rpclang('home','phone_depict');
			if(CHARSET != 'utf-8') {
				$setarr['depict'] = utf8togbk($setarr['depict']);
			}
			$setarr['isPhone'] = 1;

			$albumid = DB::insert('home_album', $setarr, 1);
		
			if(empty($space['albumnum'])) {
				$space['albums'] = getcount('home_album', array('uid'=>$space['uid']));
				$albumnumsql = "albums=".$space['albums'];
			} else {
				$albumnumsql = 'albums=albums+1';
			}
			DB::query("UPDATE ".DB::table('common_member_count')." SET {$albumnumsql} WHERE uid='$_G[uid]'");
		}
		$_POST['albumid'] = $albumid;
	}

	$_POST['pic_title'] = $_POST['pic_title'];
	$uploadfiles = pic_save($_FILES['attach'], $_POST['albumid'], $_POST['pic_title']);
	
	if($uploadfiles && is_array($uploadfiles)) {
		$albumid = $uploadfiles['albumid'];
		$picid = $uploadfiles['picid'];
		$uploadStat = 1;
		if($albumid > 0) {
			album_update_pic($albumid);
		}
	} else {
		$uploadStat = $uploadfiles;
	}

	/*if($_G['mobile']) {
		if($picid) {
			if(ckprivacy('upload', 'feed')) {
				require_once libfile('function/feed');
				feed_publish($albumid, 'albumid');
			}
			//showmessage('do_success', "home.php?mod=space&uid=$_G[uid]&do=album&picid=$picid");
			$msg = lang('message', 'do_success');
			jsonexit("{'status':'0','message':'$msg'}");
		} else {
			//showmessage($uploadStat, 'home.php?mod=spacecp&ac=upload');
			$msg = lang('message', $uploadStat);
			jsonexit("{'status':'0','message':'$msg'}");
		}
	} else {
		echo "<script>";
		echo "parent.albumid = $albumid;";
		echo "parent.uploadStat = '$uploadStat';";
		echo "parent.picid = $picid;";
		echo "parent.upload();";
		echo "</script>";
	}
	exit();*/
	
	if($picid) {
		if(ckprivacy('upload', 'feed')) {
			require_once libfile('function/feed');
			feed_publish($albumid, 'albumid');
		}
		echo $picid;
		exit;
		//showmessage('do_success', "home.php?mod=space&uid=$_G[uid]&do=album&picid=$picid");
		/*$msg = lang('message', 'do_success');
		jsonexit("{'status':'1','message':'$msg'}");*/
	} else {
		//showmessage($uploadStat, 'home.php?mod=spacecp&ac=upload');
//		$msg = lang('message', $uploadStat);
//		jsonexit("{'status':'0','message':'$msg'}");
		echo 0;
		exit;
	}

} elseif(submitcheck('viewAlbumid')) {

	if($_POST['opalbumid'] > 0) {
		album_update_pic($_POST['opalbumid']);
	}

	if(ckprivacy('upload', 'feed')) {
		require_once libfile('function/feed');
		feed_publish($_POST['opalbumid'], 'albumid');
	}

	$url = "home.php?mod=space&uid=$_G[uid]&do=album&quickforward=1&id=".(empty($_POST['opalbumid'])?-1:$_POST['opalbumid']);

	//showmessage('upload_images_completed', $url);
	$msg = lang('message', upload_images_completed);
	jsonexit("{\"status\":\"1\",\"message\":\"$msg\"}");

} else {
	if(!checkperm('allowupload')) {
		//showmessage('no_privilege_upload', '', array(), array('return' => true));
		$msg = lang('spacecp', 'not_allow_upload');
		
		jsonexit("{\"status\":\"0\",\"message\":\"$msg\"}");
	}

	cknewuser();

	$config = urlencode($_G['siteroot'].'home.php?mod=misc&ac=swfupload&op=config'.($_GET['op'] == 'cam'? '&cam=1' : ''));

	//获取相册列表
//	$albums = getalbums($_G['uid']);

	$actives = ($_GET['op'] == 'flash' || $_GET['op'] == 'cam')?array($_GET['op']=>' class="a"'):array('js'=>' class="a"');

	$maxspacesize = checkperm('maxspacesize');
	if(!empty($maxspacesize)) {

		space_merge($space, 'count');
		space_merge($space, 'field_home');
		$maxspacesize = $maxspacesize + $space['addsize'] * 1024 * 1024;
		$haveattachsize = ($maxspacesize < $space['attachsize'] ? '-':'').formatsize($maxspacesize - $space['attachsize']);
	} else {
		$haveattachsize = 0;
	}

	require_once libfile('function/friend');
	$groups = friend_group_list();

	loadcache('albumcategory');
	$category = $_G['cache']['albumcategory'];

	$categoryselect = '';
	if($category) {
		include_once libfile('function/portalcp');
		$categoryselect = category_showselect('album', 'catid', !$_G['setting']['albumcategoryrequired'] ? true : false, $_GET['catid']);
	}
}

if(!$_G['gp_op']) {
	$_G['gp_op'] = 'normal';
}
$navtitle = lang('core', 'title_'.$_G['gp_op'].'_upload');