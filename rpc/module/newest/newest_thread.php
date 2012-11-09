<?php
/**
 * 主题列表
 * @copyright  2011-2012 Bei Jing Zheng Yi Wireless
 * @since      File available since Release 1.0 -- 2011-8-16 下午04:29:12
 * @author mengbing880814@yahoo.com.cn
 * 
 */

/*$thread_num 显示数目*/
$page = $_GET ['page'] ? getgpc ('page') : 1;
$thread_num = $_GET ['thread_num'] ? getgpc ('thread_num') : 20;

loadcache('zywxdata');
$config = unserialize($_G['cache']['zywxdata']);
$hideids =	$config['hideforum'];

if($config['newest_post'] && $page == 1) {
	$config['newest_post'] =  str_replace("\r\n", ',', $config['newest_post']);
	$config['newest_post'] =  str_replace(array(',,', ' '), ',', $config['newest_post']);
	
	$list = DB::getAll("SELECT * FROM " . DB::table('forum_thread')." WHERE tid IN($config[newest_post])");
	foreach($list as $key => $value) {
		$value['dateline'] = dgmdate($value['dateline'], 'Y-n-j H:i');
		$value['lastpost'] = dgmdate($value['lastpost'], 'Y-n-j H:i');
		$value['name'] = DB::getOne("SELECT name FROM " . DB::table('forum_forum')." WHERE fid='$value[fid]'");
		unset($value['fid']);
		$list[$key] = $value;
	}
}

//最新主题总数
$num_total = 60;

if($num_total > $thread_num) {
	$pages = @ceil($num_total / $thread_num);
	$_G["zywy_totalpage"] = $pages;
}

/*MB add start*/
$jsonarr['zywy_curpage'] = $page;
$jsonarr['zywy_totalpage'] = $_G["zywy_totalpage"];
/*MB add end*/

$start = $thread_num * ($page-1);

$sql = "SELECT t.*, f.name FROM " . DB::table ('forum_thread') . " AS t 	
			LEFT JOIN " . DB::table ('forum_forum') . " AS f 
			ON f.fid=t.fid 	
			WHERE t.displayorder>=0			
			ORDER BY t.tid DESC LIMIT $start, $thread_num";
	
$newThread = DB::getAll($sql);
foreach($newThread as $key => $value) {
	$value['dateline'] = dgmdate($value['dateline'], 'Y-n-j H:i');
	$value['lastpost'] = dgmdate($value['lastpost'], 'Y-n-j H:i');
	$newThread[$key] = $value;
	
	if($hideids && in_array($value['fid'], $hideids)) {
		unset($newThread[$key]);
	}
}

if(is_array($list)) {
	$newThread = array_merge($list, $newThread);
}

if($newThread){
	
	$jsonarr['ucurl'] = $_G["setting"]['ucenterurl'];
    $jsonarr['newThread'] = $newThread;

	jsonexit($jsonarr);
} else{
	jsonexit();
}
?>