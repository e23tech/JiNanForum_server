<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: thread_activity.php 20005 2011-01-27 10:10:01Z zhangguosheng $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}


$applylist = array();
$activity = DB::fetch_first("SELECT * FROM ".DB::table('forum_activity')." WHERE tid='$_G[tid]'");
$activityclose = $activity['expiration'] ? ($activity['expiration'] > TIMESTAMP ? 0 : 1) : 0;
$activity['starttimefrom'] = dgmdate($activity['starttimefrom']);
$activity['starttimeto'] = $activity['starttimeto'] ? dgmdate($activity['starttimeto']) : 0;
$activity['expiration'] = $activity['expiration'] ? dgmdate($activity['expiration']) : 0;
$activity['activityclose'] = $activityclose;
$activity['cls'] = $activity['class'];
unset($activity['class']);

if($_G['uid']) {
	$activity['applyed'] = DB::getOne("SELECT applyid FROM ".DB::table('forum_activityapply')." 
						WHERE tid='$post[tid]' AND uid='$_G[uid]'") ? 1 : 0;		
}

$activity['allapplynum'] = DB::getOne("SELECT COUNT(applyid) FROM ".DB::table('forum_activityapply')." 
						WHERE tid='$post[tid]'");

//$activity['attachurl'] = $activity['thumb'] = '';
if($activity['ufield']) {
	$activity['ufield'] = unserialize($activity['ufield']);
	if($activity['ufield']['userfield']) {
		$htmls = $settings = array();
		require_once libfile('function/profile');
		foreach($activity['ufield']['userfield'] as $fieldid) {
			if(empty($ufielddata['userfield'])) {
				$query = DB::query("SELECT ".implode(',', $activity['ufield']['userfield'])." FROM ".DB::table('common_member_profile')." WHERE uid='$_G[uid]'");
				$ufielddata['userfield'] = DB::fetch($query);
			}
			$html = profile_setting($fieldid, $ufielddata['userfield'], false, true);
			if($html) {
				$settings[$fieldid] = $_G['cache']['profilesetting'][$fieldid];
				$htmls[$fieldid] = $html;
			}
		}
	}
} else {
	$activity['ufield'] = '';
}

if($activity['aid']) {
	$attach = DB::fetch_first("SELECT * FROM ".DB::table(getattachtablebytid($_G['tid']))." WHERE aid='$activity[aid]'");
	if($attach['isimage']) {
		$activity['attachurl'] = ($attach['remote'] ? $_G['setting']['ftp']['attachurl'] : $_G['setting']['attachurl']).'forum/'.$attach['attachment'];
		if(!preg_match('/^http:\/\//', $activity['attachurl'])) {
			$activity['attachurl'] = $_G['siteurl'].$activity['attachurl'];
		}
	}
}

$post['activity'] = $activity;

?>