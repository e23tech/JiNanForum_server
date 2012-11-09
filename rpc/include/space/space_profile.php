<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: space_profile.php 22256 2011-04-27 01:52:25Z monkey $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

require_once libfile('function/spacecp');

space_merge($space, 'count');
space_merge($space, 'field_home');
space_merge($space, 'field_forum');
space_merge($space, 'profile');
space_merge($space, 'status');
getonlinemember(array($space['uid']));

if($space['videophoto'] && ckvideophoto($space, 1)) {
	$space['videophoto'] = getvideophoto($space['videophoto']);
} else {
	$space['videophoto'] = '';
}

$space['admingroup'] = $_G['cache']['usergroups'][$space['adminid']];
$space['admingroup']['icon'] = g_icon($space['adminid'], 1);

$space['group'] = $_G['cache']['usergroups'][$space['groupid']];
$space['group']['icon'] = g_icon($space['groupid'], 1);

if($space['extgroupids']) {
	$newgroup = array();
	$e_ids = explode(',', $space['extgroupids']);
	foreach ($e_ids as $e_id) {
		$newgroup[] = $_G['usergroups'][$e_id]['grouptitle'];
	}
	$space['extgroupids'] = implode(',', $newgroup);
}

$space['regdate'] = dgmdate($space['regdate']);
if($space['lastvisit']) $space['lastvisit'] = dgmdate($space['lastvisit']);
if($space['lastactivity']) {
	$space['lastactivitydb'] = $space['lastactivity'];
	$space['lastactivity'] = dgmdate($space['lastactivity']);
}
if($space['lastpost']) $space['lastpost'] = dgmdate($space['lastpost']);
if($space['lastsendmail']) $space['lastsendmail'] = dgmdate($space['lastsendmail']);


if($_G['uid'] == $space['uid'] || $_G['group']['allowviewip']) {
	require_once libfile('function/misc');
	$space['regip_loc'] = convertip($space['regip']);
	$space['lastip_loc'] = convertip($space['lastip']);
}

$space['buyerrank'] = 0;
if($space['buyercredit']){
	foreach($_G['setting']['ec_credit']['rank'] AS $level => $credit) {
		if($space['buyercredit'] <= $credit) {
			$space['buyerrank'] = $level;
			break;
		}
	}
}

$space['sellerrank'] = 0;
if($space['sellercredit']){
	foreach($_G['setting']['ec_credit']['rank'] AS $level => $credit) {
		if($space['sellercredit'] <= $credit) {
			$space['sellerrank'] = $level;
			break;
		}
	}
}

$space['attachsize'] = formatsize($space['attachsize']);

$space['timeoffset'] = empty($space['timeoffset']) ? '9999' : $space['timeoffset'];

require_once libfile('function/friend');
$isfriend = friend_check($space['uid'], 1);

loadcache('profilesetting');
include_once libfile('function/profile');
$profiles = array();
$privacy = $space['privacy']['profile'] ? $space['privacy']['profile'] : array();

if($_G['setting']['verify']['enabled']) {
	space_merge($space, 'verify');
}
foreach($_G['cache']['profilesetting'] as $fieldid => $field) {
	if(!$field['available'] || in_array($fieldid, array('birthprovince', 'birthdist', 'birthcommunity', 'resideprovince', 'residedist', 'residecommunity'))) {
			continue;
	}
	if(
		$field['available'] && strlen($space[$fieldid]) > 0 &&
		(
			$field['showinthread'] ||
			$field['showincard'] ||
			(
				$space['self'] || empty($privacy[$fieldid]) || ($isfriend && $privacy[$fieldid] == 1)
			)
		) &&
		(!$_G['inajax'] && $field['invisible'] != '1' || $_G['inajax'] && $field['showincard'])
	) {
		$val = profile_show($fieldid, $space);
		if($val !== false) {
			if($fieldid == 'realname' && $_G['uid'] != $space['uid'] && !ckrealname(1)) {
				continue;
			}
			if($val == '')  $val = '-';
			$profiles[$fieldid] = array('title'=>$field['title'], 'value'=>$val);
		}
	}
}

$count = DB::result(DB::query("SELECT COUNT(*) FROM ".DB::table('forum_moderator')." WHERE uid = '$space[uid]'"), 0);
if($count) {
	$query = DB::query("SELECT f.name,f.fid AS fid FROM ".DB::table('forum_moderator').
		" m LEFT JOIN ".DB::table('forum_forum')." f USING(fid) WHERE  uid = '$space[uid]'");
	while($result = DB::fetch($query)) {
		$manage_forum[$result['fid']] = $result['name'];
	}
}

if(!$_G['inajax'] && $_G['setting']['groupstatus']) {
	$groupcount = DB::result_first("SELECT COUNT(*) FROM ".DB::table('forum_groupuser')." WHERE uid = '{$space['uid']}'");
	if($groupcount > 0) {
		$query = DB::query("SELECT fg.fid, ff.name FROM ".DB::table('forum_groupuser')." fg LEFT JOIN ".DB::table('forum_forum')." ff USING(fid) WHERE fg.uid = '{$space['uid']}' LIMIT $groupcount");
		while ($result = DB::fetch($query)) {
			$usergrouplist[] = $result;
		}
	}
}

if($space['medals']) {
        loadcache('medals');
        foreach($space['medals'] = explode("\t", $space['medals']) as $key => $medalid) {
                list($medalid, $medalexpiration) = explode("|", $medalid);
                if(isset($_G['cache']['medals'][$medalid]) && (!$medalexpiration || $medalexpiration > TIMESTAMP)) {
                        $space['medals'][$key] = $_G['cache']['medals'][$medalid];
                        $space['medals'][$key]['medalid'] = $medalid;
                } else {
                        unset($space['medals'][$key]);
                }
        }
}
$upgradecredit = $space['uid'] && $space['group']['type'] == 'member' && $space['group']['creditslower'] != 9999999 ? $space['group']['creditslower'] - $space['credits'] : false;
$allowupdatedoing = $space['uid'] == $_G['uid'] && checkperm('allowdoing');

dsetcookie('home_diymode', 1);

$navtitle = lang('space', 'sb_profile', array('who' => $space['username']));
$metakeywords = lang('space', 'sb_profile', array('who' => $space['username']));
$metadescription = lang('space', 'sb_profile', array('who' => $space['username']));

$showvideophoto = true;
if($space['videophotostatus'] > 0 && $_G['uid'] != $space['uid'] && !ckvideophoto($space, 1)) {
	$showvideophoto = false;
}

/*插入浏览记录  start*/
if(!$space['self'] && $_G['uid']) {
	$query = DB::query("SELECT dateline FROM ".DB::table('home_visitor')." WHERE uid='$space[uid]' AND vuid='$_G[uid]'");
	$visitor = DB::fetch($query);
	$is_anonymous = empty($_G['cookie']['anonymous_visit_'.$_G['uid'].'_'.$space['uid']]) ? 0 : 1;
	if(empty($visitor['dateline'])) {
		$setarr = array(
			'uid' => $space['uid'],
			'vuid' => $_G['uid'],
			'vusername' => $is_anonymous ? '' : $_G['username'],
			'dateline' => $_G['timestamp']
		);
		DB::insert('home_visitor', $setarr, 0, true);
		show_credit();
	} else {
		if($_G['timestamp'] - $visitor['dateline'] >= 300) {
			DB::update('home_visitor', array('dateline'=>$_G['timestamp'], 'vusername'=>$is_anonymous ? '' : $_G['username']), array('uid'=>$space['uid'], 'vuid'=>$_G['uid']));
		}
		if($_G['timestamp'] - $visitor['dateline'] >= 3600) {
			show_credit();
		}
	}
	updatecreditbyaction('visit', 0, array(), $space['uid']);
}
/*插入浏览记录  end*/

if ($space) {
	/*获取地理位置 */
	if (! $space ['self']) {
			/*
    		//当前用户经纬度
    		$longitude = floatval($_GET['longitude']);
    		$latitude = floatval($_GET['latitude']);
    		*/
								
		$row[0] = DB::getRow("SELECT longitude,latitude FROM ".DB::table('zywx_useroperation')." WHERE uid='".$space['uid']. "' 
								ORDER BY dateline DESC LIMIT 1" );
								
		$row[1] = DB::getRow("SELECT longitude,latitude FROM ".DB::table('zywx_useroperation')." WHERE uid='".$_G['uid']. "' 
								ORDER BY dateline DESC LIMIT 1" );													
								
		if(is_array($row[0]) && is_array($row[1])){
			$distance = geo_distance ( array ($row[0] ['latitude'], $row[0] ['longitude'] ), array ($row[1] ['latitude'], $row[1] ['longitude'] ) );		
			
			if ($distance/1000 > 1) {
				$space ['distance'] = number_format($distance/1000,1,'.','');
				$space ['lenUnit'] = 1;
			} else {
				$space ['distance'] = round($distance);
			}
		}
	}
	
	/*average    start*/
	$start = strtotime ( $space ['regdate'] );
	$end = strtotime ( $space ['lastactivity'] );
	$days = round ( ($end - $start) / 3600 / 24 );
	if ($days > 0) {
		$space ['postsavg'] = sprintf ( "%0.1f", $space ['posts'] / $days );
	} else {
		$space ['postsavg'] = 0;
	}
	/*average    end*/
	
	$space ['avatarimg'] = avatar ( $space ['uid'], middle );
	
	$info = array ('realname', 'gender', 'birthday', 'birthcity', 'residecity' );
	$new_profiles = array ();
	foreach ( $profiles as $key => $value ) {
		if (empty ( $value ) || ! in_array ( $key, $info )) {
			unset ( $profiles [$key] );
		}
	}

	if (! empty ( $profiles )) {
		$space ['profiles'] = $profiles;
	}
	if (! $space ['self']) {
		if ($isfriend == 1) {
			$space ['button'] = array ('title' => 'ignore', 'value' => rpclang ( 'home', 'ignore_friend' ) );
		} else {
			$space ['button'] = array ('title' => 'add', 'value' => rpclang ( 'home', 'add_friend' ) );
		}
	}
}

//年龄
if($space['birthyear']) {
	$space['age'] = date('Y') - $space['birthyear'];
}

jsonexit($space);
    
//插入浏览记录 profile
function show_credit() {
	global $_G, $space;

	$showinfo = DB::fetch_first("SELECT credit, unitprice FROM ".DB::table('home_show')." WHERE uid='$space[uid]'");
	if($showinfo['credit'] > 0) {
		$showinfo['unitprice'] = intval($showinfo['unitprice']);
		if($showinfo['credit'] <= $showinfo['unitprice']) {
//			notification_add($space['uid'], 'show', 'show_out');
			DB::delete('home_show', array('uid' => $space['uid']));
		} else {
			DB::query("UPDATE ".DB::table('home_show')." SET credit=credit-'$showinfo[unitprice]' WHERE uid='{$space[uid]}' AND credit>0");
		}
	}
}