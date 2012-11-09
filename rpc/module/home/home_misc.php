<?php
/*
	杂项接口
*/
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$ac = empty($_G['gp_ac']) ? '' : $_G['gp_ac'];

if ('inputpwd' == $ac) {
	if(submitcheck('pwdsubmit')) {
	
		$blogid = empty($_GET['blogid'])?0:intval($_GET['blogid']);
		$albumid = empty($_GET['albumid'])?0:intval($_GET['albumid']);
	
		$itemarr = array();
		if($blogid) {
			$query = DB::query("SELECT * FROM ".DB::table('home_blog')." WHERE blogid='$blogid'");
			$itemarr = DB::fetch($query);
			$itemurl = "home.php?mod=space&uid=$itemarr[uid]&do=blog&id=$itemarr[blogid]";
			$itemurl = str_replace('home.php', 'blog_view.html', $itemurl);
			$cookiename = 'view_pwd_blog_'.$blogid;
		} elseif($albumid) {
			$query = DB::query("SELECT * FROM ".DB::table('home_album')." WHERE albumid='$albumid'");
			$itemarr = DB::fetch($query);
			$itemurl = "home.php?mod=space&uid=$itemarr[uid]&do=album&id=$itemarr[albumid]";
			$itemurl = str_replace('home.php', 'album_view.html', $itemurl);
			$cookiename = 'view_pwd_album_'.$albumid;
		}
	
		if(empty($itemarr)) {
			$message = lang('message', 'news_does_not_exist');
			jsonexit("{\"status\":\"-2\",\"message\":\"$message\"}");
		}
	
		if($itemarr['password'] && $_GET['viewpwd'] == $itemarr['password']) {
			dsetcookie($cookiename, md5(md5($itemarr['password'])));
			
			$message = lang('message', 'proved_to_be_successful');
			jsonexit("{\"status\":\"1\",\"message\":\"$message\",\"url\":\"$itemurl\"}");
		} else {
			$message = rpclang('home', 'enter_password');
			jsonexit("{\"status\":\"0\",\"message\":\"$message\"}");
		}
	}
} else if ('ajax' == $ac) {
	
	$op = empty($_GET['op'])?'':$_GET['op'];
	
	if($op == 'district') {
		$container = $_GET['container'];
		$showlevel = intval($_GET['level']);
		$showlevel = $showlevel >= 1 && $showlevel <= 4 ? $showlevel : 4;
		$values = array(intval($_GET['pid']), intval($_GET['cid']), intval($_GET['did']), intval($_GET['coid']));
		$level = 1;
		if($values[0]) {
			$level++;
		} else if($_G['uid'] && !empty($_GET['showdefault'])) {
	
			space_merge($_G['member'], 'profile');
			$containertype = substr($container, 0, 5);
			$district = array();
			if($containertype == 'birth') {
				if(!empty($_G['member']['birthprovince'])) {
					$district[] = $_G['member']['birthprovince'];
					if(!empty($_G['member']['birthcity'])) {
						$district[] = $_G['member']['birthcity'];
					}
					if(!empty($_G['member']['birthdist'])) {
						$district[] = $_G['member']['birthdist'];
					}
					if(!empty($_G['member']['birthcommunity'])) {
						$district[] = $_G['member']['birthcommunity'];
					}
				}
			} else {
				if(!empty($_G['member']['resideprovince'])) {
					$district[] = $_G['member']['resideprovince'];
					if(!empty($_G['member']['residecity'])) {
						$district[] = $_G['member']['residecity'];
					}
					if(!empty($_G['member']['residedist'])) {
						$district[] = $_G['member']['residedist'];
					}
					if(!empty($_G['member']['residecommunity'])) {
						$district[] = $_G['member']['residecommunity'];
					}
				}
			}
			if(!empty($district)) {
				$query = DB::query('SELECT * FROM '.DB::table('common_district')." WHERE name IN (".dimplode(daddslashes($district)).')');
				while($value = DB::fetch($query)) {
					$key = $value['level'] - 1;
					$values[$key] = $value['id'];
				}
				$level++;
			}
		}
		if($values[1]) {
			$level++;
		}
		if($values[2]) {
			$level++;
		}
		if($values[3]) {
			$level++;
		}
		$showlevel = $level;
		$elems = array();
		if($_GET['province']) {
			$elems = array($_GET['province'], $_GET['city'], $_GET['district'], $_GET['community']);
		}
	
		include_once libfile('function/profile','plugin/zywx/rpc/');
		$html = showdistrict($values, $elems, $container, $showlevel);
		$jsonarr['html'] = $html;
		jsonexit($jsonarr);
	}
} 

//获取频道列表
else if ('channel_setting' == $ac) {
	loadcache('zywxdata');
	$config = unserialize($_G['cache']['zywxdata']);

	
	$jsonarr = array();
	$jsonarr['hidehome'] = $config['hidehome'];
	$jsonarr['name'] = $config['name'];
	$jsonarr['index'] = $config['index'];

	$jsonarr['sort'] = $config['sort'];
	asort($jsonarr['sort']);
	$jsonarr['sort'] = array_keys($jsonarr['sort']);

	jsonexit($jsonarr);
}

//查询相册是否被收藏
else if ('album_favorited' == $ac) {
	
	$favid = '';
	$_G['gp_albumid'] = intval($_G['gp_albumid']);
	
	if($_G['uid'] && $_G['gp_albumid']) {
		$favid = DB::getOne("SELECT favid FROM ".DB::table('home_favorite')." 
							  WHERE uid='$_G[uid]' AND id='$_G[gp_albumid]' AND idtype='albumid'");
	}
	
	jsonexit($favid);					
}

//查询相册是否被收藏
else if ('album_pic_delete' == $ac) {
	
	$flag = 0;
	$picid = intval($_G['gp_picid']);
	
	$album = DB::getRow("SELECT albumid, uid, filepath FROM ".DB::table('home_pic')." 
						 WHERE picid='$picid'");

	if($album && $album['uid'] == $_G['uid']) {
		DB::query("DELETE FROM ".DB::table('home_pic')." WHERE picid ='$picid'");
		@unlink($_G['setting']['attachdir'].'album/'.$album['filepath']);
		DB::query("UPDATE ".DB::table('home_album')." SET picnum=picnum-1 WHERE albumid='$album[albumid]'");
		
		

		for($i = 0;$i < 10;$i++) {
			DB::query("UPDATE ".DB::table('forum_attachment_'.$i)." SET picid='0' WHERE picid='$picid'");
		}

		DB::delete('home_comment', "id='$picid' AND idtype='picid'");
		DB::delete('home_feed', "id='$picid' AND idtype='picid'");
		DB::delete('home_clickuser', "id='$picid' AND idtype='picid'");
		DB::delete('common_moderate', "id='$picid' AND idtype='picid'");
		
		$flag = 1;
	}
	
	jsonexit($flag);						  
}

//获取主题，日志，相册，记录，留言，提醒，收藏个数
else if ('misc_stat' == $ac) {
	
	$_G['uid'] = $_G['gp_uid'] ? $_G['gp_uid'] : $_G['uid'];
	if(!$_G['uid']) jsonexit(); //如果没登录，则返回空

	require_once libfile('function/friend');
	$count = getcount('home_friend_request', array('uid'=>$_G['uid']));
	
	$jsonarr = array();

	$jsonarr['thread_num'] = DB::getOne("SELECT COUNT(tid) FROM ".DB::table('forum_thread')." WHERE authorid='$_G[uid]'");
	$jsonarr['blog_num'] = DB::getOne("SELECT COUNT(blogid) FROM ".DB::table('home_blog')." WHERE uid='$_G[uid]'");
	$jsonarr['album_num'] = DB::getOne("SELECT COUNT(albumid) FROM ".DB::table('home_album')." WHERE uid='$_G[uid]'");
	$jsonarr['doing_num'] = DB::getOne("SELECT COUNT(doid) FROM ".DB::table('home_doing')." WHERE uid='$_G[uid]'");
	$jsonarr['pm_num'] = DB::getOne("SELECT COUNT(plid) FROM ".DB::table('ucenter_pm_indexes')." WHERE plid='$_G[uid]'");
	$jsonarr['newprompt_num'] = DB::getOne("SELECT newprompt FROM ".DB::table('common_member')." WHERE uid='$_G[uid]'");
	$jsonarr['newpm'] = $count;
	$jsonarr['favorite_num'] = DB::getOne("SELECT COUNT(favid) FROM ".DB::table('home_favorite')." WHERE uid='$_G[uid]'");
	$jsonarr['wall_num'] = DB::getOne("SELECT COUNT(uid) FROM ".DB::table('home_comment')." WHERE uid='$_G[uid]' AND idtype='uid'");
	$jsonarr['album_num']++;
	
	jsonexit($jsonarr);
}

//获取用户个性设置
elseif ('user_setting' == $ac) {
	
	if($_G['gp_setting_submit']) {
		
		$imagemode = $_G['gp_imagemode'] ? 1 : 0;
		
		if($_G['uid']) {
			$setting = DB::getOne("SELECT privacy FROM ".DB::table('common_member_field_home')." WHERE uid='$_G[uid]'");
			$setting = unserialize($setting);
			$setting['appcan']['imagemode'] = $imagemode;
			$setting = serialize($setting);
			DB::query("UPDATE ".DB::table('common_member_field_home')." SET privacy='$setting' WHERE uid='".$_G['uid']."'");
			jsonexit(1);
		} else {
			jsonexit(0);
		}
		
	} else {
		
		/*************门户频道是否开启*********************/
		$articlecount = DB::getOne("SELECT COUNT(*) FROM ".DB::table('portal_article_title'));
		
		loadcache('zywxdata');
		$config = unserialize($_G['cache']['zywxdata']);
	
		$hideids =	$config['hideportal'];
	 	$hideids = implode(',', $hideids);
		if(!$hideids) $hideids = 0;
		$cids = DB::getCol("SELECT catid FROM ".DB::table('portal_category')." WHERE catid NOT IN(".$hideids.")");

		$setting['appcan']['pluginstatus'] = 1;
		
		/*************插件是否开启*********************/
		$pluginid = DB::getOne("SELECT pluginid FROM ".DB::table('common_plugin')." WHERE identifier='zywx'");

		if(empty($pluginid)) 
		{
			$setting['appcan']['pluginstatus'] = 0;
		}
		
		/*************站点是否开启*********************/
		$setting['appcan']['sitestatus'] = $_G['setting']['bbclosed'] ? 0 : 1;
		
		/*************插件配置信息*********************/
		$plugindata = DB::getOne('SELECT data FROM '.DB::table("common_syscache")." WHERE cname='zywxdata' LIMIT 1");
		$plugindata = unserialize($plugindata);
		
		$setting['appcan']['weibo_sina'] = $plugindata['weibo_sina'];
		$setting['appcan']['weibo_sina_callback'] = $plugindata['weibo_sina_callback'];
		$setting['appcan']['weibo_tencent'] = $plugindata['weibo_tencent'];
		$setting['appcan']['weibo_tencent_callback'] = $plugindata['weibo_tencent_callback'];
		
		$setting['appcan']['index_pic1'] = $plugindata['index_pic1'];
		$setting['appcan']['index_pic1_subject'] = $plugindata['index_pic1_subject'];
		$setting['appcan']['index_pic1_url'] = $plugindata['index_pic1_url'];		
		$setting['appcan']['index_pic2'] = $plugindata['index_pic2'];
		$setting['appcan']['index_pic2_subject'] = $plugindata['index_pic2_subject'];
		$setting['appcan']['index_pic2_url'] = $plugindata['index_pic2_url'];		
		$setting['appcan']['index_pic3'] = $plugindata['index_pic3'];
		$setting['appcan']['index_pic3_subject'] = $plugindata['index_pic3_subject'];
		$setting['appcan']['index_pic3_url'] = $plugindata['index_pic3_url'];
		
		$setting['appcan']['hot_post_pic1'] = $plugindata['hot_post_pic1'];		
		$setting['appcan']['hot_post_pic1_url'] = $plugindata['hot_post_pic1_url'];
		$setting['appcan']['hot_post_pic2'] = $plugindata['hot_post_pic2'];		
		$setting['appcan']['hot_post_pic2_url'] = $plugindata['hot_post_pic2_url'];
		$setting['appcan']['hot_post_pic3'] = $plugindata['hot_post_pic3'];		
		$setting['appcan']['hot_post_pic3_url'] = $plugindata['hot_post_pic3_url'];
		
		$setting['appcan']['index_pic_status'] = $plugindata['index_pic_status'];
		$setting['appcan']['hot_post_pic_status'] = $plugindata['hot_post_pic_status'];
		
		$setting['appcan']['portalstatus'] = $plugindata['portalstatus'];
		if(empty($cids) || !$articlecount) {
			$setting['appcan']['portalstatus'] = 0;
		}			
		
		jsonexit($setting['appcan']);
	}

}











?>