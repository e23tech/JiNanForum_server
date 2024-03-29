<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: function_feed.php 13398 2010-07-27 01:48:11Z wangjinbo $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

function mkfeed_new($feed, $actors=array()) {
	global $_G;

	$feed['title_data'] = empty($feed['title_data'])?array():(is_array($feed['title_data'])?$feed['title_data']:@unserialize($feed['title_data']));
	if(!is_array($feed['title_data'])) $feed['title_data'] = array();
	$feed['body_data'] = empty($feed['body_data'])?array():(is_array($feed['body_data'])?$feed['body_data']:@unserialize($feed['body_data']));
	if(!is_array($feed['body_data'])) $feed['body_data'] = array();
	$searchs = $replaces = array();
	if($feed['title_data']) {
		foreach (array_keys($feed['title_data']) as $key) {
			switch ($feed['icon']) {
				case 'activity':
					if ('subject' == $key) {
							$feed['title_data'][$key] = str_replace('forum.php','../forum/viewthread.html',$feed['title_data'][$key]);
						}
					break;
			    case 'doing':
					if ('message' == $key) {
							$feed['title_data'][$key] = preg_replace("[src=\"(?!http:)(\w+)]i", 'src="'.getsiteurl().'\\1', $feed['title_data'][$key]);
						}
					break;
			    case 'blog':
					if ('subject' == $key) {
							$feed['title_data'][$key] = str_replace('home.php','../home/blog_view.html',$feed['title_data'][$key]);
						}
					break;
				case 'click':
					if (is_numeric(stripos($feed['hash_data'], 'blog'))) {
						if ('subject' == $key) {
							$feed['title_data'][$key] = str_replace('home.php','../home/blog_view.html',$feed['title_data'][$key]);
						}
					}
					break;
				case 'comment':
					if (is_numeric(stripos($feed['hash_data'], 'blog'))) {
						if ('blog' == $key) {
							$feed['title_data'][$key] = str_replace('home.php','../home/blog_view.html',$feed['title_data'][$key]);
						}
					}
					break;
				case 'poll':
						if ('subject' == $key) {
							$feed['title_data'][$key] = str_replace('forum.php','../forum/viewthread.html',$feed['title_data'][$key]);
						}
					break;
				case 'post':
					if (is_numeric(stripos($feed['hash_data'], 'tid'))) {
						if ('subject' == $key) {
							$feed['title_data'][$key] = strip_tags($feed['title_data'][$key]);
						}
					}
					break;
			}
					
			if ('author' == $key ||'touser' == $key) {
				$feed['title_data'][$key] = str_replace('home.php','../my/profile.html',$feed['title_data'][$key]);
			} 
			$searchs[] = '{'.$key.'}';
			$replaces[] = $feed['title_data'][$key];
		}
	}

	$searchs[] = '{actor}';
	$replaces[] = empty($actors)?"<a href=\"../my/profile.html?mod=space&uid=$feed[uid]\">$feed[username]</a>":implode(lang('core', 'dot'), $actors);
	$feed['title_template'] = str_replace($searchs, $replaces, $feed['title_template']);
	//$feed['title_template'] = feed_mktarget_new($feed['title_template']);

	$searchs = $replaces = array();
	$searchs[] = '{actor}';
	$replaces[] = empty($actors)?"<a href=\"../my/profile.html?mod=space&uid=$feed[uid]\">$feed[username]</a>":implode(lang('core', 'dot'), $actors);
	if($feed['body_data'] && is_array($feed['body_data'])) {
		foreach (array_keys($feed['body_data']) as $key) {
			switch ($feed['icon']) {
				case 'blog':
					if ('subject' == $key) {
						$feed['body_data'][$key] = str_replace('home.php','../home/blog_view.html',$feed['body_data'][$key]);
					}
					if ($feed['image_1']) {
						$feed['image_1_link'] = str_replace('home.php','../home/blog_view.html',$feed['image_1_link']);
					}
					break;
				case 'share':
					if (is_numeric(stripos($feed['hash_data'], 'blog'))) {
						if ('subject' == $key) {
							$feed['body_data'][$key] = str_replace('home.php','../home/blog_view.html',$feed['body_data'][$key]);
						}
						if ($feed['image_1']) {
							$feed['image_1_link'] = str_replace('home.php','../home/blog_view.html',$feed['image_1_link']);
						}
					} else if (is_numeric(stripos($feed['hash_data'], 'album'))) {
						if ('albumname' == $key) {
							$feed['body_data'][$key] = str_replace('home.php','../home/album_view.html',$feed['body_data'][$key]);
						}
						if ($feed['image_1']) {
							$feed['image_1_link'] = str_replace('home.php','../home/album_view.html',$feed['image_1_link']);
						}
					} else if (is_numeric(stripos($feed['hash_data'], 'pic'))) {
						if ('albumname' == $key) {
							$feed['body_data'][$key] = str_replace('home.php','../home/album_view.html',$feed['body_data'][$key]);
						}
						if ($feed['image_1']) {
							$feed['image_1_link'] = str_replace('home.php','../home/album_pic.html',$feed['image_1_link']);
						}
					} else if (is_numeric(stripos($feed['hash_data'], 'tid'))) {
						if ('subject' == $key) {
							$feed['body_data'][$key] = str_replace('forum.php','../forum/viewthread.html',$feed['body_data'][$key]);
						}
					}
					break;
				case 'album':
						if ($feed['image_1']) {
							$feed['image_1_link'] = str_replace('home.php','../home/album_pic.html',$feed['image_1_link']);
						}
					break;
				case 'comment':
					if (is_numeric(stripos($feed['hash_data'], 'blog'))) {
						if ($feed['image_1']) {
							$feed['image_1_link'] = str_replace('home.php','../home/blog_view.html',$feed['image_1_link']);
						}
					} else if (is_numeric(stripos($feed['hash_data'], 'pic'))) {
						if ($feed['image_1']) {
							$feed['image_1_link'] = str_replace('home.php','../home/album_pic.html',$feed['image_1_link']);
						}
					}
					break;
				default:
					
					break;
			}
			if ('username' == $key || 'author' == $key || 'touser' == $key) {
				$feed['body_data'][$key] = str_replace('home.php','../my/profile.html',$feed['body_data'][$key]);
			} else if ('message' == $key) {
				$feed['body_data'][$key] = cutstr(strip_tags($feed['body_data'][$key]), 60);
			} else if ('summary' == $key) {
				$feed['body_data'][$key] = cutstr(strip_tags($feed['body_data'][$key]), 60);
			} else if ('link' == $key) {
				$feed['body_data'][$key] = strip_tags($feed['body_data'][$key]);
			}
			$searchs[] = '{'.$key.'}';
			$replaces[] = $feed['body_data'][$key];
		}
	}

	$feed['magic_class'] = '';
	if(!empty($feed['body_data']['magic_thunder'])) {
		$feed['magic_class'] = 'magicthunder';
	}

	$feed['body_template'] = str_replace($searchs, $replaces, $feed['body_template']);
	//$feed['body_template'] = feed_mktarget_new($feed['body_template']);

	//$feed['body_general'] = feed_mktarget_new($feed['body_general']);

	if(is_numeric($feed['icon'])) {
		$feed['icon_image'] = "http://appicon.manyou.com/icons/{$feed['icon']}";
	} else {
		$feed['icon_image'] = STATICURL."image/feed/{$feed['icon']}.gif";
	}

	$feed['new'] = 0;
	if($_G['cookie']['home_readfeed'] && $feed['dateline']+300 > $_G['cookie']['home_readfeed']) {
		$feed['new'] = 1;
	}

	if ($feed['image_1']) {
		if (! is_numeric(stripos($feed['image_1'],'http://'))) {
			$feed['image_1'] = getsiteurl().$feed['image_1'];
		}
	}
	$feed['dateline'] = dgmdate($feed['dateline'], 'u');
	return $feed;
}

/*function feed_mktarget_new($html) {
	global $_G;

	if($html && $_G['setting']['feedtargetblank']) {
		$html = preg_replace("/target\=([\'\"]?)[\w]+([\'\"]?)/i", '', $html);
		$html = preg_replace("/<a(.+?)href=([\'\"]?)([^>\s]+)\\2([^>]*)>/i", '<a href="\\3" target="_blank" \\1 \\4>', $html);
	}
	return $html;
}*/

function feed_add($icon, $title_template='', $title_data=array(), $body_template='', $body_data=array(), $body_general='', $images=array(), $image_links=array(), $target_ids='', $friend='', $appid='', $returnid=0, $id=0, $idtype='', $uid=0, $username='') {
	global $_G;

	$title_template = $title_template?lang('feed', $title_template):'';
	$body_template = $body_template?lang('feed', $body_template):'';
	$body_general = $body_general?lang('feed', $body_general):'';
	if(empty($uid) || empty($username)) {
		$uid = $username = '';
	}

	$feedarr = array(
		'appid' => $appid,
		'icon' => $icon,
		'uid' => $uid ? intval($uid) : $_G['uid'],
		'username' => $username ? $username : $_G['username'],
		'dateline' => $_G['timestamp'],
		'title_template' => $title_template,
		'body_template' => $body_template,
		'body_general' => $body_general,
		'image_1' => empty($images[0])?'':$images[0],
		'image_1_link' => empty($image_links[0])?'':$image_links[0],
		'image_2' => empty($images[1])?'':$images[1],
		'image_2_link' => empty($image_links[1])?'':$image_links[1],
		'image_3' => empty($images[2])?'':$images[2],
		'image_3_link' => empty($image_links[2])?'':$image_links[2],
		'image_4' => empty($images[3])?'':$images[3],
		'image_4_link' => empty($image_links[3])?'':$image_links[3],
		'target_ids' => $target_ids,
		'friend' => $friend,
		'id' => $id,
		'idtype' => $idtype
	);

	$feedarr = dstripslashes($feedarr);
	$feedarr['title_data'] = serialize(dstripslashes($title_data));
	$feedarr['body_data'] = serialize(dstripslashes($body_data));
	$feedarr['hash_data'] = empty($title_data['hash_data'])?'':$title_data['hash_data'];
	$feedarr = daddslashes($feedarr);

	if(is_numeric($icon)) {
		$feed_table = 'home_feed_app';
		unset($feedarr['id'], $feedarr['idtype']);
	} else {
		if($feedarr['hash_data']) {
			$query = DB::query("SELECT feedid FROM ".DB::table('home_feed')." WHERE uid='$feedarr[uid]' AND hash_data='$feedarr[hash_data]' LIMIT 0,1");
			if($oldfeed = DB::fetch($query)) {
				return 0;
			}
		}
		$feed_table = 'home_feed';
	}

	if($returnid) {
		return DB::insert($feed_table, $feedarr, $returnid);
	} else {
		DB::insert($feed_table, $feedarr);
		return 1;
	}
}

function mkfeed($feed, $actors=array()) {
	global $_G;

	$feed['title_data'] = empty($feed['title_data'])?array():(is_array($feed['title_data'])?$feed['title_data']:@unserialize($feed['title_data']));
	if(!is_array($feed['title_data'])) $feed['title_data'] = array();
	$feed['body_data'] = empty($feed['body_data'])?array():(is_array($feed['body_data'])?$feed['body_data']:@unserialize($feed['body_data']));
	if(!is_array($feed['body_data'])) $feed['body_data'] = array();

	$searchs = $replaces = array();
	if($feed['title_data']) {
		foreach (array_keys($feed['title_data']) as $key) {
			$searchs[] = '{'.$key.'}';
			$replaces[] = $feed['title_data'][$key];
		}
	}

	$searchs[] = '{actor}';
	$replaces[] = empty($actors)?"<a href=\"home.php?mod=space&uid=$feed[uid]\" target=\"_blank\">$feed[username]</a>":implode(lang('core', 'dot'), $actors);
	$feed['title_template'] = str_replace($searchs, $replaces, $feed['title_template']);
	$feed['title_template'] = feed_mktarget($feed['title_template']);

	$searchs = $replaces = array();
	$searchs[] = '{actor}';
	$replaces[] = empty($actors)?"<a href=\"home.php?mod=space&uid=$feed[uid]\" target=\"_blank\">$feed[username]</a>":implode(lang('core', 'dot'), $actors);
	if($feed['body_data'] && is_array($feed['body_data'])) {
		foreach (array_keys($feed['body_data']) as $key) {
			$searchs[] = '{'.$key.'}';
			$replaces[] = $feed['body_data'][$key];
		}
	}

	$feed['magic_class'] = '';
	if(!empty($feed['body_data']['magic_thunder'])) {
		$feed['magic_class'] = 'magicthunder';
	}

	$feed['body_template'] = str_replace($searchs, $replaces, $feed['body_template']);
	$feed['body_template'] = feed_mktarget($feed['body_template']);

	$feed['body_general'] = feed_mktarget($feed['body_general']);

	if(is_numeric($feed['icon'])) {
		$feed['icon_image'] = "http://appicon.manyou.com/icons/{$feed['icon']}";
	} else {
		$feed['icon_image'] = STATICURL."image/feed/{$feed['icon']}.gif";
	}

	$feed['new'] = 0;
	if($_G['cookie']['home_readfeed'] && $feed['dateline']+300 > $_G['cookie']['home_readfeed']) {
		$feed['new'] = 1;
	}

	return $feed;
}

function feed_mktarget($html) {
	global $_G;

	if($html && $_G['setting']['feedtargetblank']) {
		$html = preg_replace("/target\=([\'\"]?)[\w]+([\'\"]?)/i", '', $html);
		$html = preg_replace("/<a(.+?)href=([\'\"]?)([^>\s]+)\\2([^>]*)>/i", '<a href="\\3" target="_blank" \\1 \\4>', $html);
	}
	return $html;
}


function feed_publish($id, $idtype, $add=0) {
	global $_G;

	$setarr = array();
	switch ($idtype) {
		case 'blogid':
			$query = DB::query("SELECT b.*, bf.* FROM ".DB::table('home_blog')." b
				LEFT JOIN ".DB::table('home_blogfield')." bf ON bf.blogid=b.blogid
				WHERE b.blogid='$id'");
			if($value = DB::fetch($query)) {
				if($value['friend'] != 3) {
					$setarr['icon'] = 'blog';
					$setarr['id'] = $value['blogid'];
					$setarr['idtype'] = $idtype;
					$setarr['uid'] = $value['uid'];
					$setarr['username'] = $value['username'];
					$setarr['dateline'] = $value['dateline'];
					$setarr['target_ids'] = $value['target_ids'];
					$setarr['friend'] = $value['friend'];
					$setarr['hot'] = $value['hot'];
					$status = $value['status'];

					$url = "home.php?mod=space&uid=$value[uid]&do=blog&id=$value[blogid]";
					if($value['friend'] == 4) {
						$setarr['title_template'] = 'feed_blog_password';
						$setarr['title_data'] = array('subject' => "<a href=\"$url\">$value[subject]</a>");
					} else {
						if($value['pic']) {
							$setarr['image_1'] = pic_cover_get($value['pic'], $value['picflag']);
							$setarr['image_1_link'] = $url;
						}
						$setarr['title_template'] = 'feed_blog_title';
						$setarr['body_template'] = 'feed_blog_body';
						$value['message'] = preg_replace("/&[a-z]+\;/i", '', $value['message']);
						$setarr['body_data'] = array(
							'subject' => "<a href=\"$url\">$value[subject]</a>",
							'summary' => getstr($value['message'], 150, 1, 1, 0, -1)
						);
					}
				}
			}
			break;
		case 'albumid':
			$key = 1;
			if($id > 0) {
				$query = DB::query("SELECT a.username, a.albumname, a.picnum, a.friend, a.target_ids, p.* FROM ".DB::table('home_pic')." p
					LEFT JOIN ".DB::table('home_album')." a ON a.albumid=p.albumid
					WHERE p.albumid='$id' ORDER BY dateline DESC LIMIT 0,4");
				while ($value = DB::fetch($query)) {
					if($value['friend'] <= 2) {
						if(empty($setarr['icon'])) {
							$setarr['icon'] = 'album';
							$setarr['id'] = $value['albumid'];
							$setarr['idtype'] = $idtype;
							$setarr['uid'] = $value['uid'];
							$setarr['username'] = $value['username'];
							$setarr['dateline'] = $value['dateline'];
							$setarr['target_ids'] = $value['target_ids'];
							$setarr['friend'] = $value['friend'];
							$status = $value['status'];
							$setarr['title_template'] = 'feed_album_title';
							$setarr['body_template'] = 'feed_album_body';
							$setarr['body_data'] = array(
								'album' => "<a href=\"home.php?mod=space&uid=$value[uid]&do=album&id=$value[albumid]\">$value[albumname]</a>",
								'picnum' => $value['picnum']
							);
						}
						$setarr['image_'.$key] = pic_get($value['filepath'], 'album', $value['thumb'], $value['remote']);
						$setarr['image_'.$key.'_link'] = "home.php?mod=space&uid=$value[uid]&do=album&picid=$value[picid]";
						$key++;
					} else {
						break;
					}
				}
			}
			break;
		case 'picid':
			$plussql = $id>0?"p.picid='$id'":"p.uid='$_G[uid]' ORDER BY dateline DESC LIMIT 1";
			$query = DB::query("SELECT p.*, a.friend, a.target_ids FROM ".DB::table('home_pic')." p
				LEFT JOIN ".DB::table('home_album')." a ON a.albumid=p.albumid WHERE $plussql");
			if($value = DB::fetch($query)) {
				if(empty($value['friend'])) {
					$setarr['icon'] = 'album';
					$setarr['id'] = $value['picid'];
					$setarr['idtype'] = $idtype;
					$setarr['uid'] = $value['uid'];
					$setarr['username'] = $value['username'];
					$setarr['dateline'] = $value['dateline'];
					$setarr['target_ids'] = $value['target_ids'];
					$setarr['friend'] = $value['friend'];
					$setarr['hot'] = $value['hot'];
					$status = $value['status'];
					$url = "home.php?mod=space&uid=$value[uid]&do=album&picid=$value[picid]";
					$setarr['image_1'] = pic_get($value['filepath'], 'album', $value['thumb'], $value['remote']);
					$setarr['image_1_link'] = $url;
					$setarr['title_template'] = 'feed_pic_title';
					$setarr['body_template'] = 'feed_pic_body';
					$setarr['body_data'] = array('title' => $value['title']);
				}
			}
			break;
	}

	if($setarr['icon']) {
		$setarr['title_template'] = $setarr['title_template']?lang('feed', $setarr['title_template']):'';
		$setarr['body_template'] = $setarr['body_template']?lang('feed', $setarr['body_template']):'';
		$setarr['body_general'] = $setarr['body_general']?lang('feed', $setarr['body_general']):'';

		$setarr['title_data']['hash_data'] = "{$idtype}{$id}";
		$setarr['title_data'] = serialize($setarr['title_data']);
		$setarr['body_data'] = serialize($setarr['body_data']);
		$setarr = daddslashes($setarr);

		$feedid = 0;
		if(!$add && $setarr['id']) {
			$query = DB::query("SELECT feedid FROM ".DB::table('home_feed')." WHERE id='$id' AND idtype='$idtype'");
			$feedid = DB::result($query, 0);
		}
		if($status == 0) {
			if($feedid) {
				DB::update('home_feed', $setarr, array('feedid'=>$feedid));
			} else {
				DB::insert('home_feed', $setarr);
			}
		}
	}

}

?>