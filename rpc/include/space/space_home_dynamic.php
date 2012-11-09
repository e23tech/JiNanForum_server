<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: space_home.php 22540 2011-11-16 16:42:25Z lilixing $
 */



$feed_users = $feed_list = $user_list = $filter_list  = $list = $magic = array();

$wheresql = array('1');

if(isset($_GET['uid']))
{
	$wheresql['uid'] = "uid='".$_GET['uid']."'";
	$ordersql = "dateline DESC";
	$f_index = '';
	
	$count = DB::result(DB::query("SELECT COUNT(*) FROM ".DB::table('home_feed')." WHERE ".implode(' AND ', $wheresql)." "),0);
	$query = DB::query("SELECT * FROM ".DB::table('home_feed')." $f_index
		WHERE ".implode(' AND ', $wheresql)."
		ORDER BY $ordersql
		LIMIT 0,1");
	
	$value = DB::fetch($query);

	require_once libfile('function/feed','plugin/zywx/rpc/');
	
	$row = mkfeed_new($value);

	$list = substr($row['title_template'], strpos($row['title_template'],'</a>')+4);

	$list = trim($list,'：, ');

	/*
	$hash_datas = array();
	$more_list = array();
	$uid_feedcount = array();
	require_once libfile('function/feed','plugin/zywx/rpc/');
	while ($value = DB::fetch($query))
	{
		
		if(!isset($hotlist[$value['feedid']]) && !isset($hotlist_all[$value['feedid']]) && ckfriend($value['uid'], $value['friend'], $value['target_ids'])) 
		{
			$value = mkfeed_new($value);
			
			if(ckicon_uid($value)) 
			{
				if($value['dateline']>=$_G['home_today'])
				{
					$dkey = 'today';
				} elseif ($value['dateline']>=$_G['home_today']-3600*24) 
				{
					$dkey = 'yesterday';
				} else 
				{
					$dkey = dgmdate($value['dateline'], 'Y-m-d');
				}
				$maxshownum = 3;
				if(empty($value['uid'])) $maxshownum = 10;

				if(empty($value['hash_data']))
				{
					if(empty($feed_users[$dkey][$value['uid']])) $feed_users[$dkey][$value['uid']] = $value;
					if(empty($uid_feedcount[$dkey][$value['uid']])) $uid_feedcount[$dkey][$value['uid']] = 0;

					$uid_feedcount[$dkey][$value['uid']]++;

					if($uid_feedcount[$dkey][$value['uid']]>$maxshownum) {
						$more_list[$dkey][$value['uid']][] = $value;
					} else {
						$feed_list[$dkey][$value['uid']][] = $value;
					}
				} elseif(empty($hash_datas[$value['hash_data']]))
				{
					$hash_datas[$value['hash_data']] = 1;
					if(empty($feed_users[$dkey][$value['uid']])) $feed_users[$dkey][$value['uid']] = $value;
					if(empty($uid_feedcount[$dkey][$value['uid']])) $uid_feedcount[$dkey][$value['uid']] = 0;
					$uid_feedcount[$dkey][$value['uid']] ++;
					if($uid_feedcount[$dkey][$value['uid']]>$maxshownum) {
						$more_list[$dkey][$value['uid']][] = $value;
					} else {
						$feed_list[$dkey][$value['uid']][$value['hash_data']] = $value;
					}
				} else 
				{
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
		if(isset($value['body_data']['link'])) 
		{
			$value['type'] = 'link';
		} 
		
		//分享文章
		else if($value['icon'] == 'share' && preg_match('#articleid#', $value['hash_data'])) 
		{
			$value['type'] = 'article';
			$value['itemid'] =  str_replace('articleid', '', $value['hash_data']);
			$value['body_data']['title'] = strip_tags($value['body_data']['title']);
			$value['body_data']['username'] = strip_tags($value['body_data']['username']);
		}

		//分享相册
		else if($value['icon'] == 'share' && preg_match('#albumid#', $value['hash_data'])) 
		{
			$value['type'] = 'album';
			$value['itemid'] =  str_replace('albumid', '', $value['hash_data']);
			$value['fromuid'] = preg_parse('uid=(\d+)', $value['body_data']['albumname']);
			$value['body_data']['albumname'] = strip_tags($value['body_data']['albumname']);
			$value['body_data']['username'] = strip_tags($value['body_data']['username']);
			
			
		}
		
		//分享图片
		else if($value['icon'] == 'share' && preg_match('#picid#', $value['hash_data'])) 
		{
			$value['type'] = 'pic';
			$value['itemid'] =  str_replace('picid', '', $value['hash_data']);
			$value['fromuid'] = preg_parse('uid=(\d+)', $value['body_data']['albumname']);
			$value['body_data']['albumname'] = strip_tags($value['body_data']['albumname']);
			$value['body_data']['username'] = strip_tags($value['body_data']['username']);
		}
		
		//上传了新图片
		else if($value['idtype'] == 'picid') 
		{
			$value['type'] = 'newpic';
			$value['itemid'] =  str_replace('picid', '', $value['title_data']['hash_data']);
			$value['body_data']['title'] = strip_tags($value['body_data']['title']);
		}
		
		//分享日志
		else if($value['icon'] == 'share' && preg_match('#blogid#', $value['hash_data'])) 
		{
			$value['type'] = 'blog';
			$value['itemid'] =  str_replace('blogid', '', $value['hash_data']); 
			$value['body_data']['subject'] = strip_tags($value['body_data']['subject']);
			$value['body_data']['username'] = strip_tags($value['body_data']['username']);
		}
		
		//发表日志
		else if($value['icon'] == 'blog') 
		{
			$value['type'] = 'newblog';
			$value['itemid'] =  str_replace('blogid', '', $value['title_data']['hash_data']); 
			$value['body_data']['subject'] = strip_tags($value['body_data']['subject']);
		}
		
		//分享帖子
		else if($value['icon'] == 'share' && preg_match('#tid#', $value['hash_data']))
		{
			$value['type'] = 'thread';
			$value['itemid'] =  str_replace('tid', '', $value['hash_data']); 
			$value['body_data']['subject'] = strip_tags($value['body_data']['subject']);
			$value['body_data']['username'] = strip_tags($value['body_data']['username']);
		}
		
		//记录
		else if( $value['icon'] == 'doing') 
		{
			$value['type'] = 'doing';
		}
		
		//留言
		else if(preg_match('#uid#', $value['hash_data'])) 
		{
			$value['type'] = 'wall';
			$value['itemid'] =  str_replace('uid', '', $value['hash_data']);
			$value['title_template'] = strip_tags($value['title_template']);
		}
		
		//日志评论
		else if($value['icon'] == 'comment' && preg_match('#blogid#', $value['hash_data']))
		{
			$value['type'] = 'blogcomment';
			$value['itemid'] =  str_replace('blogid', '', $value['hash_data']); 
			$value['fromuid'] = preg_parse('uid=(\d+)', $value['title_data']['touser']);
			$value['title_data']['blog'] = strip_tags($value['title_data']['blog']);
			$value['title_data']['touser'] = strip_tags($value['title_data']['touser']);
		}
		
		//评论了图片
		else if($value['icon'] == 'comment' && preg_match('#picid#', $value['hash_data'])) 
		{
			$value['type'] = 'piccomment';
			$value['itemid'] =  str_replace('picid', '', $value['hash_data']); 
			$value['fromuid'] = preg_parse('uid=(\d+)', $value['title_data']['touser']);
			$value['title_data']['touser'] = strip_tags($value['title_data']['touser']);
		}
		
		//更新了相册
		else if($value['icon'] == 'album' && $value['idtype'] == 'albumid') 
		{
			$value['type'] = 'updatealbum';
			
			if($value['image_1']) $value['image_1'] = '<img src="'.$value['image_1'].'"/>';
			if($value['image_2']) $value['image_1'] .= '<img src="'.$_G['siteurl'].$value['image_2'].'"/>';
			if($value['image_3']) $value['image_1'] .= '<img src="'.$_G['siteurl'].$value['image_3'].'"/>';
			if($value['image_4']) $value['image_1'] .= '<img src="'.$_G['siteurl'].$value['image_4'].'"/>';
			
		}
		
		//成为好友
		else if($value['icon'] == 'friend') 
		{
			$value['type'] = 'friend';
			$value['fromuid'] = preg_parse('uid=(\d+)', $value['title_data']['touser']);
			$value['title_data']['touser'] = strip_tags($value['title_data']['touser']);
		}
		
		$list = $value['title_template'];
		
	}
	*/
}


$jsonarr['title_template'] = $list;

jsonexit($jsonarr);