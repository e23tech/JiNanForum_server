<?php

/**
 * 附近用户
 *
 * file_description
 *
 * LICENSE Aushi Copyright
 *
 * @copyright  2011-2012 Bei Jing Zheng Yi Wireless
 * @version    $Id: \$
 * @since      File available since Release 1.0 -- 2011-09-16 pm 15:05
 * @author 	   <yjt>yinjitao2001@163.com
 * 
 */
$perpage = 10;
$page = $_GET['page'] ? max(intval($_GET['page']), 1) : 1;
$start = ($page - 1) * $perpage;
	 
 if(!empty($_G['uid']))
 {
 	$distance  = $_G['gp_distance'] ? $_G['gp_distance'] : 'all';
 	$sqlOne = "SELECT latitude,longitude FROM ".DB::table('zywx_useroperation')." WHERE uid ='".$_G['uid']."' ORDER BY dateline DESC LIMIT 1";
 	$one = DB::fetch_first($sqlOne);
 	$longitude = $one['longitude'];
 	$latitude = $one['latitude'];

							
 	$sqlAll = "SELECT DISTINCT(zywx.uid),zywx.username,zywx.dateline,zywx.phone_name,zywx.latitude,zywx.longitude, m.recentnote FROM ".
				DB::table('zywx_useroperation')." zywx 
				LEFT JOIN ".DB::table('common_member_field_home')." m
				ON  zywx.uid=m.uid 
				WHERE  zywx.allow_state = 1 AND zywx.uid <>".$_G['uid']." ORDER BY zywx.dateline ASC";
	
 	$query = DB::query($sqlAll);
	
 	$list = array();
 	$curr = array();

 	while($row = DB::fetch($query)) {
		if($row['latitude'] && $row['longitude']) {
			$nearDis = geo_distance(array($row['latitude'],$row['longitude']),array($latitude,$longitude));	
			if( $nearDis < $distance || $distance == "all") {
				$list[$row['uid']]['distance'] = round($nearDis);
				$list[$row['uid']]['nickname'] = $row['username'];
				$list[$row['uid']]['phone_name'] = $row['phone_name'];
				$list[$row['uid']]['uid'] = $row['uid'];
				$list[$row['uid']]['dateline'] = $row['dateline'];
				$list[$row['uid']]['recentnote'] = $row['recentnote'];
				$list[$row['uid']]['imgsrc'] = $_G['setting']['ucenterurl']."/avatar.php?uid=".$row['uid']."&size=small";
			}
		}
 	}

	$rs['count'] = count($list);
	$list = array_slice($list, $start, $perpage);
	
	//排序
	foreach($list as $row) {
		$sortaux[] = $row['distance'];
	}
    
	array_multisort($sortaux, SORT_ASC, $list);
	
 	$rs['list'] = $list;
	$rs['zywy_curpage'] = $page;
	$rs['zywy_totalpage'] = max(1, ceil($jsonarr['count'] / $perpage));
	
 	jsonexit($rs);
 	
 }else 
 {
 	$msg = rpclang('search', 'user_unlogin');
 	jsonexit("{\"state\":\"$msg\"}");
 }