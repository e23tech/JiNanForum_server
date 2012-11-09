<?php
/**
 * 热门主题
 */
 
$page = $_GET ['page'] ? getgpc ('page') : 1;
$thread_num = $_GET ['thread_num'] ? getgpc ('thread_num') : 20;
$start = $thread_num * ($page-1);

/**
 * 缓存时间为1小时
 */
//$hotThread = zy_loadcache('zywx_hotthread_'.$page, 3600);

loadcache('zywxdata');
$config = unserialize($_G['cache']['zywxdata']);
$hideids =	$config['hideforum'];

if($config['host_post'] && $page == 1) {
	$config['host_post'] =  str_replace("\r\n", ',', $config['host_post']);
	$config['host_post'] =  str_replace(array(',,', ' '), ',', $config['host_post']);
	
	$list = DB::getAll("SELECT * FROM " . DB::table('forum_thread')." WHERE tid IN($config[host_post])");
	foreach($list as $key => $value) {
		$value['dateline'] = dgmdate($value['dateline'], 'Y-n-j H:i');
		$value['lastpost'] = dgmdate($value['lastpost'], 'Y-n-j H:i');
		$value['name'] = DB::getOne("SELECT name FROM " . DB::table('forum_forum')." WHERE fid='$value[fid]'");
		unset($value['fid']);
		$list[$key] = $value;
	}
}

//热门主题总数
$num_total = 60;

if($num_total > $thread_num) {
	$pages = @ceil($num_total / $thread_num);
	$_G["zywy_totalpage"] = $pages;
}

$jsonarr['zywy_curpage'] = $page;
$jsonarr['zywy_totalpage'] = $_G["zywy_totalpage"];

$timestamp = TIMESTAMP - 604800;
$dateline = "t.dateline>='$timestamp' AND";

$hotThread = DB::getAll("SELECT t.*, f.name 
		FROM ".DB::table('forum_thread')." t
		LEFT JOIN ".DB::table('forum_forum')." f USING(fid)
		WHERE $dateline t.displayorder>='0'
		ORDER BY t.replies DESC
		LIMIT 0, $thread_num");
	
foreach($hotThread as $key => $value) {
	$value['dateline'] = dgmdate($value['dateline'], 'Y-n-j H:i');
	$value['lastpost'] = dgmdate($value['lastpost'], 'Y-n-j H:i');
	$hotThread[$key] = $value;
	if($hideids && in_array($value['fid'], $hideids)) {
		unset($hotThread[$key]);
	}
}

if(is_array($list)) {
	$hotThread = array_merge($list, $hotThread);
}

if ($hotThread){

	$jsonarr['ucurl'] = $_G["setting"]['ucenterurl'];
    $jsonarr['hotThread']=$hotThread;

	jsonexit($jsonarr);
} else{
	jsonexit();
}
?>