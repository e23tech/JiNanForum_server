<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: function_share.php 6741 2010-03-25 07:36:01Z cnteacher $
 */

function mkshareformat($share) {
	$share['body_data'] = unserialize($share['body_data']);

	$searchs = $replaces = array();
	if($share['body_data']) {
		foreach (array_keys($share['body_data']) as $key) {
			switch ($share['type']) {
				case 'blog':
					if ('subject' == $key) {
						$newValue = str_replace('home.php','blog_view.html',$share['body_data'][$key]);
					} else if ('message' == $key) {
						$newValue = cutstr(strip_tags($share['body_data'][$key]), 60);
					} else {
						$newValue = $share['body_data'][$key];
					}
					break;
				case 'space':
					if ('username' == $key) {
						$newValue = str_replace('home.php','../my/profile.html',$share['body_data'][$key]);
					} else {
						$newValue = $share['body_data'][$key];
					}
					break;
				case 'pic':
					if ('albumname' == $key) {
						$newValue = str_replace('home.php','album_pic.html',$share['body_data'][$key]);
					} else {
						$newValue = $share['body_data'][$key];
					}
					break;
				case 'album':
					if ('albumname' == $key) {
						$newValue = str_replace('home.php','album_view.html',$share['body_data'][$key]);
					} else {
						$newValue = $share['body_data'][$key];
					}
					break;
				case 'thread':
					if ('subject' == $key) {
						$newValue = str_replace('forum.php','../forum/viewthread.html',$share['body_data'][$key]);
					} else {
						$newValue = $share['body_data'][$key];
					}
					break;
				default:
					if ('link' == $key) {
						$newValue = strip_tags($share['body_data'][$key]);
					} else {
						$newValue = $share['body_data'][$key];
					}
					break;
			}
			if ('username' == $key || 'author' == $key) {
				$newValue = str_replace('home.php','../my/profile.html',$share['body_data'][$key]);
			}
			$searchs[] = '{'.$key.'}';
			$replaces[] = $newValue;
		}
		
	}
	$share['body_template'] = str_replace($searchs, $replaces, $share['body_template']);

	if ($share['image']) {
		if ('space' != $share['type']) {
			$share['image'] = getsiteurl().$share['image'];
		}
		switch ($share['type']) {
			case 'blog':
				$share['image_link'] = str_replace('home.php','blog_view.html',$share['image_link']);
				break;
			case 'pic':
				$share['image_link'] = str_replace('home.php','album_pic.html',$share['image_link']);
				break;
			case 'album':
				$share['image_link'] = str_replace('home.php','album_view.html',$share['image_link']);
				break;
			case 'space':
				$share['image_link'] = str_replace('home.php','../my/profile.html',$share['image_link']);
				break;
			default:
				break;
		}
		
	}
	
	return $share;
}
?>