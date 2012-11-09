<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: forum_index.php 22868 2011-05-27 07:09:50Z monkey $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

require_once libfile('function/forumlist');

$catlist = $forumlist = $sublist = $forumname = $collapseimg = $collapse = array();
$threads = $posts = $todayposts = $fids = $announcepm = 0;


$sql = "SELECT f.fid, f.fup, f.type, f.name, f.threads, f.posts, f.todayposts, 
			ff.viewperm, ff.redirect, ff.threadtypes 
			FROM ".DB::table('forum_forum')." f
			LEFT JOIN ".DB::table('forum_forumfield')." ff USING(fid)
			WHERE f.status='1' ORDER BY f.type, f.displayorder";

$query = DB::query($sql);

while($forum = DB::fetch($query)) {
	$forumname[$forum['fid']] = strip_tags($forum['name']);

	if($forum['type'] != 'group') {

		$threads += $forum['threads'];
		$posts += $forum['posts'];
		$todayposts += $forum['todayposts'];

		if($forum['type'] == 'forum' && isset($catlist[$forum['fup']])) {
			if(forum($forum)) {
				$catlist[$forum['fup']]['forums'][] = $forum['fid'];
				$forum['orderid'] = $catlist[$forum['fup']]['forumscount']++;
				$forum['subforums'] = '';
				$forum['threadtypes'] = unserialize($forum['threadtypes']);
				$forumlist[$forum['fid']] = $forum;
			}

		} elseif(isset($forumlist[$forum['fup']])) {

			$forumlist[$forum['fup']]['threads'] += $forum['threads'];
			$forumlist[$forum['fup']]['posts'] += $forum['posts'];
			$forumlist[$forum['fup']]['todayposts'] += $forum['todayposts'];

		}

	} else {

		$forum['forumscount'] 	= 0;
		$catlist[$forum['fid']] = $forum;

	}
}

unset($catid, $category);

if(isset($catlist[0]) && $catlist[0]['forumscount']) {
	$catlist[0]['fid'] = 0;
	$catlist[0]['type'] = 'group';
	$catlist[0]['name'] = $_G['setting']['bbname'];
	$catlist[0]['collapseimg'] = 'collapsed_no.gif';
} else {
	unset($catlist[0]);
}



?>