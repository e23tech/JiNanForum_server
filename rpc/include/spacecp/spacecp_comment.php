<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: spacecp_comment.php 20083 2011-02-14 02:48:58Z monkey $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}
$tospace = $pic = $blog = $album = $share = $poll = array();

include_once libfile('class/bbcode');
$bbcode = & bbcode::instance();

if($_POST['idtype'] == 'uid' && ($seccodecheck || $secqaacheck)) {
	$seccodecheck = 0;
	$secqaacheck = 0;
}

if(submitcheck('commentsubmit', 0, $seccodecheck, $secqaacheck)) {

	if(!checkperm('allowcomment')) {
		$msg = rpclang('home', 'no_privilege_comment');
		
		jsonexit("{\"status\":\"0\",\"message\":\"$msg\"}");
	}

	cknewuser();

	$waittime = interval_check('post');
	if($waittime > 0) {
		$msg = lang('message', 'operating_too_fast',array('waittime' => $waittime));
		
		jsonexit("{\"status\":\"0\",\"message\":\"$msg\"}");
	}

	$id = intval($_POST['id']);
	$idtype = $_POST['idtype'];
	$message = getstr($_POST['message'], 0, 1, 1, 2);
	$cid = empty($_POST['cid'])?0:intval($_POST['cid']);

	if(strlen($message) < 2) {
		$msg = lang('message', 'content_is_too_short');
		
		jsonexit("{\"status\":\"0\",\"message\":\"$msg\"}");
	}

	require_once libfile('function/comment','plugin/zywx/rpc/');
	$cidarr = add_comment($message, $id, $idtype, $cid);
	if($cidarr['cid'] != 0) {
		$msg = lang('message', $cidarr['msg'],$cidarr['magvalues']);
		
		jsonexit("{\"status\":\"1\",\"message\":\"$msg\"}");
	} else {
		$msg = lang('message', 'no_privilege_comment');
		
		jsonexit("{\"status\":\"0\",\"message\":\"$msg\"}");
	}
}

$cid = empty($_GET['cid'])?0:intval($_GET['cid']);

if($_GET['op'] == 'edit') {
	if($_G['adminid'] != 1 && $_G['gp_modcommentkey'] != modauthkey($_G['gp_cid'])) {
		$sqladd = "AND authorid='$_G[uid]'";
	} else {
		$sqladd = '';
	}
	$query = DB::query("SELECT * FROM ".DB::table('home_comment')." WHERE cid='$cid' $sqladd");
	if(!$comment = DB::fetch($query)) {
		$msg = lang('message', 'no_privilege_comment_edit');
		
		jsonexit("{\"status\":\"0\",\"message\":\"$msg\"}");
	}

	if(submitcheck('editsubmit')) {

		$message = getstr($_POST['message'], 0, 1, 1, 2);
		if(strlen($message) < 2) showmessage('content_is_too_short');
		$message = censor($message);
		if(censormod($message)) {
			$comment_status = 1;
		} else {
			$comment_status = 0;
		}
		if($comment_status == 1) {
			manage_addnotify('verifycommontes');
		}
		DB::update('home_comment', array('message'=>$message, 'status'=>$comment_status), array('cid' => $comment['cid']));
		$msg = lang('message', 'do_success');
		
		jsonexit("{\"status\":\"1\",\"message\":\"$msg\"}");
	}

	$comment['message'] = $bbcode->html2bbcode($comment['message']);

} elseif($_GET['op'] == 'delete') {

	if(submitcheck('deletesubmit')) {
		/*MB add delete wall start*/
		if($_GET['del_wall'] == 'del_wall'){
			$queryStr = DB::getCol("SELECT cid FROM ".DB::table('home_comment')." WHERE id='$space[uid]' AND idtype='uid'");
			$walls = dimplode($queryStr);
			if (preg_match("/^\'/",$walls)){
            	$cid = substr($walls, 1, strlen($walls) - 2);
        	}
		}
		/*MB add delete wall end*/
		require_once libfile('function/delete');
		if(deletecomments(array($cid))) {
			$msg = lang('message', 'do_success');
			
			jsonexit("{\"status\":\"1\",\"message\":\"$msg\"}");
		} else {
			$msg = lang('message', 'no_privilege_comment_del');
			
			jsonexit("{\"status\":\"0\",\"message\":\"$msg\"}");
		}
	}

} elseif($_GET['op'] == 'reply') {

	$query = DB::query("SELECT * FROM ".DB::table('home_comment')." WHERE cid='$cid'");
	if(!$comment = DB::fetch($query)) {
		$msg = lang('message', 'comments_do_not_exist');
		
		jsonexit("{\"status\":\"0\",\"message\":\"$msg\"}");
	}
	if($comment['idtype'] == 'uid' && ($seccodecheck || $secqaacheck)) {
		$seccodecheck = 0;
		$secqaacheck = 0;
	}
	$config = urlencode(getsiteurl().'home.php?mod=misc&ac=swfupload&op=config&doodle=1');
} else {
	$msg = lang('message', 'undefined_action');
	
	jsonexit("{\"status\":\"0\",\"message\":\"$msg\"}");
}
?>