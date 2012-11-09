<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: space_home.php 22540 2011-05-12 02:51:25Z zhengqingpeng $
 */
 
if(!$_G['uid'] && $_G['setting']['privacy']['view']['home']) {
	$msg = lang('message', 'home_no_privilege');
	jsonexit("{\"status\":\"0\",\"message\":\"$msg\"}");
}
require_once libfile('function/feed');

if(empty($_G['setting']['feedhotday'])) {
	$_G['setting']['feedhotday'] = 2;
}

$minhot = $_G['setting']['feedhotmin']<1?3:$_G['setting']['feedhotmin'];

space_merge($space, 'count');

if(empty($_GET['view'])) {
	if($space['self']) {
		if($_G['setting']['showallfriendnum'] && $space['friends'] < $_G['setting']['showallfriendnum']) {
			$_GET['view'] = 'all';
		} else {
			$_GET['view'] = 'we';
		}
	} else {
		$_GET['view'] = 'all';
	}
}
if(empty($_GET['order'])) {
	$_GET['order'] = 'dateline';
}

$perpage = 10;
$perpage = mob_perpage($perpage);

if($_GET['view'] == 'all' && $_GET['order'] == 'hot') {
	$perpage = 10;
}

$page = intval($_GET['page']);
if($page < 1) $page = $page >= 5 ? 5 : 1;
$start = ($page-1)*$perpage;

ckstart($start, $perpage);

$_G['home_today'] = $_G['timestamp'] - ($_G['timestamp'] + $_G['setting']['timeoffset'] * 3600) % 86400;

$gets = array(
	'mod' => 'space',
	'uid' => $space['uid'],
	'do' => 'home',
	'view' => $_GET['view'],
	'order' => $_GET['order'],
	'appid' => $_GET['appid'],
	'type' => $_GET['type'],
	'icon' => $_GET['icon']
);
$theurl = 'home.html?'.url_implode($gets);
$hotlist = array();
if(!IS_ROBOT) {
	$feed_users = $feed_list = $user_list = $filter_list  = $list = $magic = array();
	if($_GET['view'] != 'app') {
		if($space['self'] && empty($start) && $_G['setting']['feedhotnum'] > 0 && ($_GET['view'] == 'we' || $_GET['view'] == 'all')) {
			$hotlist_all = array();
			$hotstarttime = $_G['timestamp'] - $_G['setting']['feedhotday']*3600*24;
			$query = DB::query("SELECT * FROM ".DB::table('home_feed')." USE INDEX(hot) WHERE dateline>='$hotstarttime' ORDER BY hot DESC LIMIT 0,10");
			while ($value = DB::fetch($query)) {
				if($value['hot']>0 && ckfriend($value['uid'], $value['friend'], $value['target_ids'])) {
					if(empty($hotlist)) {
						$hotlist[$value['feedid']] = $value;
					} else {
						$hotlist_all[$value['feedid']] = $value;
					}
				}
			}
			$nexthotnum = $_G['setting']['feedhotnum'] - 1;
			if($nexthotnum > 0) {
				if(count($hotlist_all)> $nexthotnum) {
					$hotlist_key = array_rand($hotlist_all, $nexthotnum);
					if($nexthotnum == 1) {
						$hotlist[$hotlist_key] = $hotlist_all[$hotlist_key];
					} else {
						foreach ($hotlist_key as $key) {
							$hotlist[$key] = $hotlist_all[$key];
						}
					}
				} else {
					$hotlist = array_merge($hotlist, $hotlist_all);
				}
			}
		}
	}

	$need_count = true;
	$wheresql = array('1');

	if($_GET['view'] == 'all') {

		if($_GET['order'] == 'dateline') {
			$ordersql = "dateline DESC";
			$f_index = '';
			$orderactives = array('dateline' => ' class="a"');
		} else {
			$wheresql['hot'] = "hot>='$minhot'";
			$ordersql = "dateline DESC";
			$f_index = '';
			$orderactives = array('hot' => ' class="a"');
		}

	} elseif($_GET['view'] == 'me') {

		$wheresql['uid'] = "uid='$space[uid]'";
		$ordersql = "dateline DESC";
		$f_index = '';

		$diymode = 1;
		if($space['self'] && $_GET['from'] != 'space') $diymode = 0;

	} else {

		space_merge($space, 'field_home');

		if(empty($space['feedfriend'])) {
			$need_count = false;
		} else {
			$wheresql['uid'] = "uid IN ('0',$space[feedfriend])";
			$ordersql = "dateline DESC";
			$f_index = 'USE INDEX(dateline)';
		}
	}

	$appid = empty($_GET['appid'])?0:intval($_GET['appid']);
	if($appid) {
		$wheresql['appid'] = "appid='$appid'";
	}
	$icon = empty($_GET['icon'])?'':trim($_GET['icon']);
	if($icon) {
		$wheresql['icon'] = "icon='$icon'";
	}
	$gid = !isset($_GET['gid'])?'-1':intval($_GET['gid']);
	if($gid>=0) {
		$fuids = array();
		$query = DB::query("SELECT * FROM ".DB::table('home_friend')." WHERE uid='$_G[uid]' AND gid='$gid' ORDER BY num DESC LIMIT 0,100");
		
		while ($value = DB::fetch($query)) {
			$fuids[] = $value['fuid'];
		}
		
		if(empty($fuids)) {
			$need_count = false;
		} else {
			$wheresql['uid'] = "uid IN (".dimplode($fuids).")";
		}
	}
	$gidactives[$gid] = ' class="a"';

	$count = $filtercount = 0;
	$multi = '';

	if($need_count) {
		$count = DB::result(DB::query("SELECT COUNT(*) FROM ".DB::table('home_feed')." WHERE ".implode(' AND ', $wheresql)." "),0);
		$query = DB::query("SELECT * FROM ".DB::table('home_feed')." $f_index
			WHERE ".implode(' AND ', $wheresql)."
			ORDER BY $ordersql
			LIMIT $start,$perpage");

		//if($_GET['view'] != 'me') {
			$hash_datas = array();
			$more_list = array();
			$uid_feedcount = array();
			require_once libfile('function/feed','plugin/zywx/rpc/');
			while ($value = DB::fetch($query)) {
				if(!isset($hotlist[$value['feedid']]) && !isset($hotlist_all[$value['feedid']]) && ckfriend($value['uid'], $value['friend'], $value['target_ids'])) {
					$value = mkfeed_new($value);
					if(ckicon_uid($value)) {


						if($value['dateline']>=$_G['home_today']) {
							$dkey = 'today';
						} elseif ($value['dateline']>=$_G['home_today']-3600*24) {
							$dkey = 'yesterday';
						} else {
							$dkey = dgmdate($value['dateline'], 'Y-m-d');
						}

						$maxshownum = 3;
						if(empty($value['uid'])) $maxshownum = 10;

						if(empty($value['hash_data'])) {
							if(empty($feed_users[$dkey][$value['uid']])) $feed_users[$dkey][$value['uid']] = $value;
							if(empty($uid_feedcount[$dkey][$value['uid']])) $uid_feedcount[$dkey][$value['uid']] = 0;

							$uid_feedcount[$dkey][$value['uid']]++;

							if($uid_feedcount[$dkey][$value['uid']]>$maxshownum) {
								$more_list[$dkey][$value['uid']][] = $value;
							} else {
								$feed_list[$dkey][$value['uid']][] = $value;
							}

						} elseif(empty($hash_datas[$value['hash_data']])) {
							$hash_datas[$value['hash_data']] = 1;
							if(empty($feed_users[$dkey][$value['uid']])) $feed_users[$dkey][$value['uid']] = $value;
							if(empty($uid_feedcount[$dkey][$value['uid']])) $uid_feedcount[$dkey][$value['uid']] = 0;


							$uid_feedcount[$dkey][$value['uid']] ++;

							if($uid_feedcount[$dkey][$value['uid']]>$maxshownum) {
								$more_list[$dkey][$value['uid']][] = $value;
							} else {
								$feed_list[$dkey][$value['uid']][$value['hash_data']] = $value;
							}

						} else {
							$user_list[$value['hash_data']][] = "<a href=\"home.php?mod=space&uid=$value[uid]\">$value[username]</a>";
						}


					} else {
						$filtercount++;
						$filter_list[] = $value;
					}
				}
	
				if ($user_list[$value['hash_data']])
				{
					$value['thread_participants'] = implode(', ', str_replace('home.php','../my/profile.html',$user_list[$value['hash_data']]));
				}

				//LLX-过滤站长发布信息	  
				$value['body_template'] = preg_replace('/(【.*易站长插件.*】)+/','',$value['body_template']);
				//LLX-过滤经纬度信息
				$value['body_template'] = preg_replace("/\[jw:((-?\d+)(\.\d+)?)\|((-?\d+)(\.\d+)?)\]/i",'',$value['body_template']);
				
				//网址
				if(isset($value['body_data']['link'])) {
					$value['type'] = 'link';
				} 
				
				//分享文章
				else if($value['icon'] == 'share' && preg_match('#articleid#', $value['hash_data'])) {
					$value['type'] = 'article';
					$value['itemid'] =  str_replace('articleid', '', $value['hash_data']);
					$value['body_data']['title'] = strip_tags($value['body_data']['title']);
					$value['body_data']['username'] = strip_tags($value['body_data']['username']);
				}

				//分享相册
				else if($value['icon'] == 'share' && preg_match('#albumid#', $value['hash_data'])) {
					$value['type'] = 'album';
					$value['itemid'] =  str_replace('albumid', '', $value['hash_data']);
					$value['fromuid'] = preg_parse('uid=(\d+)', $value['body_data']['albumname']);
					$value['body_data']['albumname'] = strip_tags($value['body_data']['albumname']);
					$value['body_data']['username'] = strip_tags($value['body_data']['username']);
					
					
				}
				
				//分享图片
				else if($value['icon'] == 'share' && preg_match('#picid#', $value['hash_data'])) {
					$value['type'] = 'pic';
					$value['itemid'] =  str_replace('picid', '', $value['hash_data']);
					$value['fromuid'] = preg_parse('uid=(\d+)', $value['body_data']['albumname']);
					$value['body_data']['albumname'] = strip_tags($value['body_data']['albumname']);
					$value['body_data']['username'] = strip_tags($value['body_data']['username']);
				}
				
				//上传了新图片
				else if($value['idtype'] == 'picid') {
					$value['type'] = 'newpic';
					$value['itemid'] =  str_replace('picid', '', $value['title_data']['hash_data']);
					$value['body_data']['title'] = strip_tags($value['body_data']['title']);
				}
				
				//分享日志
				else if($value['icon'] == 'share' && preg_match('#blogid#', $value['hash_data'])) {
					$value['type'] = 'blog';
					$value['itemid'] =  str_replace('blogid', '', $value['hash_data']); 
					$value['body_data']['subject'] = strip_tags($value['body_data']['subject']);
					$value['body_data']['username'] = strip_tags($value['body_data']['username']);
				}
				
				//发表日志
				else if($value['icon'] == 'blog') {
					$value['type'] = 'newblog';
					$value['itemid'] =  str_replace('blogid', '', $value['title_data']['hash_data']); 
					$value['body_data']['subject'] = strip_tags($value['body_data']['subject']);
				}
				
				//分享帖子
				else if($value['icon'] == 'share' && preg_match('#tid#', $value['hash_data'])) {
					$value['type'] = 'thread';
					$value['itemid'] =  str_replace('tid', '', $value['hash_data']); 
					$value['body_data']['subject'] = strip_tags($value['body_data']['subject']);
					$value['body_data']['username'] = strip_tags($value['body_data']['username']);
				}
				
				//记录
				else if( $value['icon'] == 'doing') {
					$value['type'] = 'doing';
				}
				
				//留言
				else if(preg_match('#uid#', $value['hash_data'])) {
					$value['type'] = 'wall';
					$value['itemid'] =  str_replace('uid', '', $value['hash_data']);
					$value['title_template'] = strip_tags($value['title_template']);
				}
				
				//日志评论
				else if($value['icon'] == 'comment' && preg_match('#blogid#', $value['hash_data'])) {
					$value['type'] = 'blogcomment';
					$value['itemid'] =  str_replace('blogid', '', $value['hash_data']); 
					$value['fromuid'] = preg_parse('uid=(\d+)', $value['title_data']['touser']);
					$value['title_data']['blog'] = strip_tags($value['title_data']['blog']);
					$value['title_data']['touser'] = strip_tags($value['title_data']['touser']);
				}
				
				//评论了图片
				else if($value['icon'] == 'comment' && preg_match('#picid#', $value['hash_data'])) {
					$value['type'] = 'piccomment';
					$value['itemid'] =  str_replace('picid', '', $value['hash_data']); 
					$value['fromuid'] = preg_parse('uid=(\d+)', $value['title_data']['touser']);
					$value['title_data']['touser'] = strip_tags($value['title_data']['touser']);
				}
				
				//更新了相册
				else if($value['icon'] == 'album' && $value['idtype'] == 'albumid') {
					$value['type'] = 'updatealbum';
					
					if($value['image_1']) $value['image_1'] = '<img src="'.$value['image_1'].'"/>';
					if($value['image_2']) $value['image_1'] .= '<img src="'.$_G['siteurl'].$value['image_2'].'"/>';
					if($value['image_3']) $value['image_1'] .= '<img src="'.$_G['siteurl'].$value['image_3'].'"/>';
					if($value['image_4']) $value['image_1'] .= '<img src="'.$_G['siteurl'].$value['image_4'].'"/>';
					
				}
				
				//成为好友
				else if($value['icon'] == 'friend') {
					$value['type'] = 'friend';
					$value['fromuid'] = preg_parse('uid=(\d+)', $value['title_data']['touser']);
					$value['title_data']['touser'] = strip_tags($value['title_data']['touser']);
				}

				$list[] = $value;
				
			}
		//}

		$multi = multi($count, $perpage, $page, $theurl);
	
		/*MB add start*/
	 	$jsonarr['zywy_curpage'] = $_G["zywy_curpage"];
		$jsonarr['zywy_totalpage'] = $_G["zywy_totalpage"];
	 	/*MB add end*/
				
		if ($count > $page*$perpage && $page < 6) {
			$jsonarr['page'] = $page + 1;
		}
	}
}

$olfriendlist = $visitorlist = $task = $ols = $birthlist = $guidelist = array();
$oluids = array();
$groups = array();
$defaultusers = $newusers = $showusers = array();

if($space['self'] && empty($start)) {

	space_merge($space, 'field_home');
	if($_GET['view'] == 'we') {
		require_once libfile('function/friend');
		$groups = friend_group_list();
	}

	$isnewer = ($_G['timestamp']-$space['regdate'] > 3600*24*7) ?0:1;
	if($isnewer) {

		$friendlist = array();
		$query = DB::query("SELECT * FROM ".DB::table('home_friend')." WHERE uid='$space[uid]'");
		while ($value = DB::fetch($query)) {
			$friendlist[$value['fuid']] = 1;
		}

		$query = DB::query("SELECT * FROM ".DB::table('home_specialuser')." WHERE status='1' ORDER BY displayorder");
		while ($value = DB::fetch($query)) {
			if(empty($friendlist[$value['uid']])) {
				$defaultusers[] = $value;
				$oluids[] = $value['uid'];
			}
		}
	}

	if($space['newprompt']) {
		space_merge($space, 'status');
	}

	$query = DB::query("SELECT * FROM ".DB::table('home_visitor')." WHERE uid='$space[uid]' ORDER BY dateline DESC LIMIT 0,12");
	while ($value = DB::fetch($query)) {
		$visitorlist[$value['vuid']] = $value;
		$oluids[] = $value['vuid'];
	}

	if($oluids) {
		$query = DB::query("SELECT * FROM ".DB::table('common_session')." WHERE uid IN (".dimplode($oluids).")");
		while ($value = DB::fetch($query)) {
			if(!$value['invisible']) {
				$ols[$value['uid']] = 1;
			} elseif ($visitorlist[$value['uid']]) {
				unset($visitorlist[$value['uid']]);
			}
		}
	}

	$oluids = array();
	$olfcount = 0;
	if($space['feedfriend']) {
		$query = DB::query("SELECT * FROM ".DB::table('common_session')." WHERE uid IN ($space[feedfriend]) ORDER BY lastactivity DESC LIMIT 15");
		while ($value = DB::fetch($query)) {
			if($olfcount < 15 && !$value['invisible']) {
				$olfriendlist[$value['uid']] = $value;
				$ols[$value['uid']] = 1;
				$oluids[$value['uid']] = $value['uid'];
				$olfcount++;
			}
		}
	}
	if($olfcount < 15) {
		$query = DB::query("SELECT fuid AS uid, fusername AS username, num FROM ".DB::table('home_friend')." WHERE uid='$space[uid]' ORDER BY num DESC, dateline DESC LIMIT 0,32");
		while ($value = DB::fetch($query)) {
			if(empty($oluids[$value['uid']])) {
				$olfriendlist[$value['uid']] = $value;
				$olfcount++;
				if($olfcount == 15) break;
			}
		}
	}

	if($space['feedfriend']) {
		$birthdaycache = DB::fetch_first("SELECT variable, value, expiration FROM ".DB::table('forum_spacecache')." WHERE uid='$_G[uid]' AND variable='birthday'");
		if(empty($birthdaycache) || TIMESTAMP > $birthdaycache['expiration']) {
			list($s_month, $s_day) = explode('-', dgmdate($_G['timestamp']-3600*24*3, 'n-j'));
			list($n_month, $n_day) = explode('-', dgmdate($_G['timestamp'], 'n-j'));
			list($e_month, $e_day) = explode('-', dgmdate($_G['timestamp']+3600*24*7, 'n-j'));
			if($e_month == $s_month) {
				$wheresql = "sf.birthmonth='$s_month' AND sf.birthday>='$s_day' AND sf.birthday<='$e_day'";
			} else {
				$wheresql = "(sf.birthmonth='$s_month' AND sf.birthday>='$s_day') OR (sf.birthmonth='$e_month' AND sf.birthday<='$e_day' AND sf.birthday>'0')";
			}

			$query = DB::query("SELECT sf.uid,sf.birthyear,sf.birthmonth,sf.birthday,s.username
				FROM ".DB::table('common_member_profile')." sf
				LEFT JOIN ".DB::table('common_member')." s USING(uid)
				WHERE (sf.uid IN ($space[feedfriend])) AND ($wheresql)");
			while ($value = DB::fetch($query)) {
				$value['istoday'] = 0;
				if($value['birthmonth'] == $n_month && $value['birthday'] == $n_day) {
					$value['istoday'] = 1;
				}
				$key = sprintf("%02d", $value['birthmonth']).sprintf("%02d", $value['birthday']);
				$birthlist[$key][] = $value;
				ksort($birthlist);
			}

			DB::query("REPLACE INTO ".DB::table('forum_spacecache')." (uid, variable, value, expiration) VALUES ('$_G[uid]', 'birthday', '".addslashes(serialize($birthlist))."', '".getexpiration()."')");
		} else {
			$birthlist = unserialize($birthdaycache['value']);
		}
	}

	if($_G['setting']['taskon']) {
		require_once libfile('class/task');
		$tasklib = & task::instance();
		$taskarr = $tasklib->tasklist('canapply');
		$task = $taskarr[array_rand($taskarr)];
	}
	if($_G['setting']['magicstatus']) {
		loadcache('magics');
		if(!empty($_G['cache']['magics'])) {
			$magic = $_G['cache']['magics'][array_rand($_G['cache']['magics'])];
			$magic['description'] = cutstr($magic['description'], 34, '');
			$magic['pic'] = strtolower($magic['identifier']).'.gif';
		}
	}
} elseif(empty($_G['uid'])) {
	$query = DB::query("SELECT * FROM ".DB::table('home_specialuser')." WHERE status='1' ORDER BY displayorder LIMIT 0,12");
	while ($value = DB::fetch($query)) {
		$defaultusers[] = $value;
	}

	$query = DB::query("SELECT * FROM ".DB::table('home_show')." ORDER BY credit DESC LIMIT 0,12");
	while ($value = DB::fetch($query)) {
		$showusers[] = $value;
	}

	$time = TIMESTAMP - (7 * 86400);
	$query = DB::query("SELECT * FROM ".DB::table('common_member')." WHERE regdate>'$time' ORDER BY uid DESC LIMIT 0,12");
	while ($value = DB::fetch($query)) {
		$value['regdate'] = dgmdate($value['regdate'], 'u', 9999, 'm-d');
		$newusers[] = $value;
	}
}

dsetcookie('home_readfeed', $_G['timestamp'], 365*24*3600);
if($_G['uid']) {
	$defaultstr = getdefaultdoing();
	space_merge($space, 'status');
	if(!$space['profileprogress']) {
		include_once libfile('function/profile');
		$space['profileprogress'] = countprofileprogress();
	}
}
$actives = array($_GET['view'] => ' class="a"');
if($_G['gp_from'] == 'space') {
	if($_G['gp_do'] == 'home') {
		$navtitle = lang('space', 'sb_feed', array('who' => $space['username']));
		$metakeywords = lang('space', 'sb_feed', array('who' => $space['username']));
		$metadescription = lang('space', 'sb_feed', array('who' => $space['username']));
	}
} else {
	list($navtitle, $metadescription, $metakeywords) = get_seosetting('home');
	if(!$navtitle) {
		$navtitle = $_G['setting']['navs'][4]['navname'];
		$nobbname = false;
	} else {
		$nobbname = true;
	}

	if(!$metakeywords) {
		$metakeywords = $_G['setting']['navs'][4]['navname'];
	}

	if(!$metadescription) {
		$metadescription = $_G['setting']['navs'][4]['navname'];
	}
}

$jsonarr['list'] = $list;

jsonexit($jsonarr);

?>