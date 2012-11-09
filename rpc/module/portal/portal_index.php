<?php
/**
	栏目列表
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

//栏目列表
$jsonarr = array();
$list = DB::getAll("SELECT catid, catname, articles FROM ".DB::table('portal_category')." 
						WHERE closed='0' AND upid='0' ORDER BY displayorder");
						
foreach($list as $category) {
	
	$jsonarr[] = $category; //一级栏目
	
	//二级栏目
	$branchlist = DB::getAll("SELECT catid, catname, articles FROM ".DB::table('portal_category')." 
						WHERE closed='0' AND upid='$category[catid]' ORDER BY displayorder");
	$jsonarr = array_merge($jsonarr, $branchlist);
	
	foreach($branchlist as $branchcat) {
	
		//三级栏目
		$leaflist = DB::getAll("SELECT catid, catname, articles FROM ".DB::table('portal_category')." 
						WHERE closed='0' AND upid='$branchcat[catid]' ORDER BY displayorder");
		$jsonarr = array_merge($jsonarr, $leaflist);				
	}
			
}

loadcache('zywxdata');
$config = unserialize($_G['cache']['zywxdata']);

//加载需要隐藏的版块
$hidelist = $config['hideportal'];

//剔除隐藏的栏目
foreach($jsonarr as $key=>$row) {
	if(in_array($row['catid'], $hidelist)) {
		unset($jsonarr[$key]);
	}
}

jsonexit($jsonarr);

?>