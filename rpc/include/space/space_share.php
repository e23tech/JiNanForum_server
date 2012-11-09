<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: space_share.php 22285 2011-04-28 03:28:42Z zhengqingpeng $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$page = empty($_GET['page'])?1:intval($_GET['page']);
if($page<1) $page=1;
$id = empty($_GET['id'])?0:intval($_GET['id']);
$_GET['type'] = in_array($_GET['type'], array('all', 'link', 'video', 'music', 'flash', 'blog', 'album', 'pic', 'poll', 'space', 'thread', 'article'))? $_GET['type'] : 'all';
if($id) {

	if(!IS_ROBOT) {
		$query = DB::query("SELECT * FROM ".DB::table('home_share')." WHERE sid='$id' AND uid='$space[uid]'");
		$share = DB::fetch($query);
		if(empty($share)) {
			$msg = lang('message', 'share_does_not_exist');
			jsonexit("{\"status\":\"0\",\"message\":\"$msg\"}");
		}

		require_once libfile('function/share');
		$share = mkshare($share);

		$perpage = 20;
		$start = ($page-1)*$perpage;

		ckstart($start, $perpage);

		$list = array();
		$cid = empty($_GET['cid'])?0:intval($_GET['cid']);
		$csql = $cid?"cid='$cid' AND":'';

		$count = DB::result(DB::query("SELECT COUNT(*) FROM ".DB::table('home_comment')." WHERE $csql id='$id' AND idtype='sid'"),0);
		if($count) {
			$query = DB::query("SELECT * FROM ".DB::table('home_comment')." WHERE $csql id='$id' AND idtype='sid' ORDER BY dateline LIMIT $start,$perpage");
			while ($value = DB::fetch($query)) {
				$list[] = $value;
			}
			$multi = multi($count, $perpage, $page, "home.php?mod=space&uid=$share[uid]&do=share&id=$id", '', 'comment_ul');
		}
		$diymode = intval($_G['cookie']['home_diymode']);
	}

} else {

	$perpage = 10;

	$start = ($page-1)*$perpage;
	ckstart($start, $perpage);

	$gets = array(
		'mod' => 'space',
		'uid' => $space['uid'],
		'do' => 'share',
		'view' => $_GET['view'],
		'from' => $_GET['from']
	);
	$navtheurl = $theurl = 'share.html?'.url_implode($gets);
	$theurl .= '&type='.$_GET['type'];
	if(!IS_ROBOT) {
		$f_index = '';
		$need_count = true;

		if(empty($_GET['view'])) $_GET['view'] = 'friends';

		if($_GET['view'] == 'all') {
			$wheresql = "1 ";

		} elseif($_GET['view'] == 'friends') {
			
			space_merge($space, 'field_home');

			if($space['feedfriend']) {
				$wheresql = "uid IN ($space[feedfriend])";
				$f_index = 'USE INDEX(dateline)';
			} else { //没有好友
				$msg = lang('space', 'block_friend_no_content');
				
				jsonexit("{\"message\":\"$msg\"}");
			}

		} else {

			if($_GET['from'] == 'space') $diymode = 1;

			$wheresql = "uid='$_G[uid]'";

		}
		$actives = array($_GET['view'] => ' class="a"');

		if($_GET['type'] && $_GET['type'] != 'all') {
			$sub_actives = array('type_'.$_GET['type'] => ' class="a"');
			$wheresql .= " AND type='$_GET[type]'";
		} else {
			$sub_actives = array('type_all' => ' class="a"');
		}

		$list = array();
		$pricount = 0;

		$sid = empty($_GET['sid'])?0:intval($_GET['sid']);
		$sharesql = $sid?"sid='$sid' AND":'';

		if($need_count) {
			$count = DB::result(DB::query("SELECT COUNT(*) FROM ".DB::table('home_share')." WHERE $sharesql $wheresql"),0);
			if($count) {
				require_once libfile('function/share','plugin/zywx/rpc/');
				//sid, itemid, type, uid, username, fromuid, dateline, body_data, body_general, image
				$query = DB::query("SELECT * FROM ".DB::table('home_share')." $f_index
					WHERE $sharesql $wheresql
					ORDER BY dateline DESC
					LIMIT $start,$perpage");
				while ($value = DB::fetch($query)) {
					$value = mkshareformat($value);
					if($value['status'] == 0 || $value['uid'] == $_G['uid'] || $_G['adminid'] == 1) {
						$value['dateline'] = dgmdate($value['dateline'],'u');
						
						//如果分享的是帖子，取是否是精华、锁定、是否有附件
						if($value['type'] == 'thread') {
							$value['body_data']['subject'] = strip_tags($value['body_data']['subject']);
							$value['body_data']['author'] = strip_tags($value['body_data']['author']);
						
							$thread = DB::getRow("SELECT views, replies, digest, closed, attachment FROM ".DB::table('forum_thread')." 
												  WHERE tid='$value[itemid]'");
							$value['body_data'] = array_merge($value['body_data'], $thread);

						} 
						
						//如果分享的是相册
						elseif($value['type'] == 'album') {
							
							//去除a标签
							$value['body_data']['albumname'] = strip_tags($value['body_data']['albumname']);
							$value['body_data']['username'] = strip_tags($value['body_data']['username']);
							$value['image'] = $attach['url'].$value['image'];
						}
						
						//如果分享的是图片
						elseif($value['type'] == 'pic') {
							
							//去除a标签
							$value['body_data']['albumname'] = strip_tags($value['body_data']['albumname']);
							$value['body_data']['username'] = strip_tags($value['body_data']['username']);
							$value['image'] = $attach['url'].$value['image'];
						}
						
						//如果分享的是日志
						elseif($value['type'] == 'blog') {
							$value['body_data']['subject'] = strip_tags($value['body_data']['subject']);
							$value['body_data']['username'] = strip_tags($value['body_data']['username']);
						
							$blog = DB::getRow("SELECT viewnum, replynum FROM ".DB::table('home_blog')." 
												  WHERE blogid='$value[itemid]'");
							$value['body_data'] = array_merge($value['body_data'], $blog);
						}
						
						//如果分享的是门户文章
						elseif($value['type'] == 'article') {
							$value['body_data']['title'] = strip_tags($value['body_data']['title']);
							$value['body_data']['username'] = strip_tags($value['body_data']['username']);
						
							$blog = DB::getRow("SELECT viewnum, commentnum FROM ".DB::table('portal_article_count')." 
												  WHERE aid='$value[itemid]'");
							$value['body_data'] = array_merge($value['body_data'], $blog);
						} 
						
						$list[] = $value;
					} else {
						$pricount ++;
					}
					
					//LLX-过滤站长发布信息
					$value['body_template'] = preg_replace('/(【.*易站长插件.*】)+/','',$value['body_template']);
					//LLX-过滤经纬度信息
					$value['body_template'] = preg_replace("/\[jw:((-?\d+)(\.\d+)?)\|((-?\d+)(\.\d+)?)\]/i",'',$value['body_template']);
					
				}
		
				$multi = multi($count, $perpage, $page, $theurl);
				/*MB add start*/
	 			$jsonarr['zywy_curpage'] = $_G["zywy_curpage"];
				$jsonarr['zywy_totalpage'] = $_G["zywy_totalpage"];
	 			/*MB add end*/
			}
		}
	}
	dsetcookie('home_diymode', $diymode);
	$_G['gp_type'] = empty($_G['gp_type']) ? 'all' : $_G['gp_type'];
	$navtitle = lang('core', 'title_share_'.$_G['gp_type']);
	$navtitle .= lang('core', 'title_share');
	if($space['username']) {
		$navtitle = lang('space', 'sb_sharing', array('who' => $space['username']));
	}
	$metakeywords = $navtitle;
	$metadescription = $navtitle;

	$jsonarr['list'] = $list;
	$jsonarr['page'] = $multi;
	jsonexit($jsonarr);
}

