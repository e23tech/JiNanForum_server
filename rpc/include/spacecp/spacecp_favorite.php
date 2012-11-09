<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: spacecp_share.php 7910 2010-04-15 01:55:08Z liguode $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

if($_GET['op'] == 'delete') {

	if($_G['gp_checkall']) {
//		if($_G['gp_favorite']) {
//			DB::query('DELETE FROM '.DB::table('home_favorite')." WHERE uid='$_G[uid]' AND favid IN (".dimplode($_G['gp_favorite']).")");
//		}
		$idtype = $_GET['idtype'];
		DB::query('DELETE FROM '.DB::table('home_favorite')." WHERE uid='$_G[uid]'AND idtype='$idtype'");
		
		$msg = lang('message', 'favorite_delete_succeed');
		jsonexit("{\"status\":\"1\",\"message\":\"$msg\"}");
	} else {
		$favid = intval($_GET['favid']);
		$thevalue = DB::fetch_first('SELECT * FROM '.DB::table('home_favorite')." WHERE favid='$favid'");
		if(empty($thevalue) || $thevalue['uid'] != $_G['uid']) {
			$msg = lang('message', 'favorite_does_not_exist');
			jsonexit("{\"status\":\"0\",\"message\":\"$msg\"}");
		}

		if(submitcheck('deletesubmit')) {
			DB::query('DELETE FROM '.DB::table('home_favorite')." WHERE favid='$favid'");
			$msg = lang('message', 'do_success');
			jsonexit("{\"status\":\"1\",\"message\":\"$msg\"}");
		}
	}

} else {


	cknewuser();

	$type = empty($_GET['type']) ? '' : $_GET['type'];
	$id = empty($_GET['id']) ? 0 : intval($_GET['id']);
	$spaceuid = empty($_GET['spaceuid']) ? 0 : intval($_GET['spaceuid']);
	$idtype = $title = $icon = '';
	switch($type) {
		case 'thread':
			$idtype = 'tid';
			$title = DB::result_first('SELECT subject FROM '.DB::table('forum_thread')." WHERE tid='$id'");
			$icon = '<img src="static/image/feed/thread.gif" alt="thread" class="vm" /> ';
			break;
		case 'forum':
			$idtype = 'fid';
			$title = DB::result_first('SELECT `name` FROM '.DB::table('forum_forum')." WHERE fid='$id' AND status !='3'");
			$icon = '<img src="static/image/feed/discuz.gif" alt="forum" class="vm" /> ';
			break;
		case 'blog';
			$idtype = 'blogid';
			$title = DB::result_first('SELECT subject FROM '.DB::table('home_blog')." WHERE blogid='$id' AND uid='$spaceuid'");
			$icon = '<img src="static/image/feed/blog.gif" alt="blog" class="vm" /> ';
			break;
		case 'group';
			$idtype = 'gid';
			$title = DB::result_first('SELECT `name` FROM '.DB::table('forum_forum')." WHERE fid='$id' AND status ='3'");
			$icon = '<img src="static/image/feed/group.gif" alt="group" class="vm" /> ';
			break;
		case 'album';
			$idtype = 'albumid';
			$title = DB::result_first('SELECT albumname FROM '.DB::table('home_album')." WHERE albumid='$id' AND uid='$spaceuid'");
			$icon = '<img src="static/image/feed/album.gif" alt="album" class="vm" /> ';
			break;
		case 'space';
			$idtype = 'uid';
			$title = DB::result_first('SELECT username FROM '.DB::table('common_member')." WHERE uid='$id'");
			$icon = '<img src="static/image/feed/profile.gif" alt="space" class="vm" /> ';
			break;
		case 'article';
			$idtype = 'aid';
			$title = DB::result_first('SELECT title FROM '.DB::table('portal_article_title')." WHERE aid='$id'");
			$icon = '<img src="static/image/feed/article.gif" alt="article" class="vm" /> ';
			break;
	}
	if(empty($idtype) || empty($title)) {
		$msg = lang('message', 'favorite_cannot_favorite');
		jsonexit("{\"status\":\"0\",\"message\":\"$msg\"}");
	}

	if(DB::result_first('SELECT * FROM '.DB::table('home_favorite')." WHERE uid='$_G[uid]' AND idtype='$idtype' AND id='$id'")) {
		$msg = lang('message', 'favorite_repeat');
		jsonexit("{\"status\":\"0\",\"message\":\"$msg\"}");
	}
	$description = '';
	$description_show = nl2br($description);

	$fav_count = DB::result_first("SELECT COUNT(*) FROM ".DB::table('home_favorite')." WHERE id='$id' AND idtype='$idtype'");
	if(submitcheck('favoritesubmit') || $type == 'forum' || $type == 'group') {
		$arr = array(
			'uid' => intval($_G['uid']),
			'idtype' => $idtype,
			'id' => $id,
			'spaceuid' => $spaceuid,
			'title' => getstr($title, 255, 0, 1),
			'description' => getstr($_POST['description'], '', 1, 1, 1),
			'dateline' => TIMESTAMP
		);
		DB::insert('home_favorite', $arr);
		$favorite_id = DB::insert_id();
		switch($type) {
			case 'thread':
				DB::query("UPDATE ".DB::table('forum_thread')." SET favtimes=favtimes+1 WHERE tid='$id'");
				if($_G['setting']['heatthread']['type'] == 2) {
					require_once libfile('function/forum');
					update_threadpartake($id);
				}
				break;
			case 'forum':
				DB::query("UPDATE ".DB::table('forum_forum')." SET favtimes=favtimes+1 WHERE fid='$id'");
				break;
			case 'blog':
				DB::query("UPDATE ".DB::table('home_blog')." SET favtimes=favtimes+1 WHERE blogid='$id' AND uid='$spaceuid'");
				dsetcookie('fblog'.$id, '1', 3600);
				break;
			case 'group':
				DB::query("UPDATE ".DB::table('forum_forum')." SET favtimes=favtimes+1 WHERE fid='$id' AND status='3'");
				break;
			case 'album':
				DB::query("UPDATE ".DB::table('home_album')." SET favtimes=favtimes+1 WHERE albumid='$id' AND uid='$spaceuid'");
				break;
			case 'space':
				DB::query("UPDATE ".DB::table('common_member_status')." SET favtimes=favtimes+1 WHERE uid='$id'");
				break;
			case 'article':
				DB::query("UPDATE ".DB::table('portal_article_count')." SET favtimes=favtimes+1 WHERE aid='$id'");
				break;
		}
		$msg = lang('message', 'favorite_do_success');
		jsonexit("{\"status\":\"1\",\"message\":\"$msg\",\"favorite_id\":\"$favorite_id\"}");
	}
}
