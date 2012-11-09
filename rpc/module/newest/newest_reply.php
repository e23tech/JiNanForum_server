<?php
/**
 * 最新回复
 * @copyright  2011-2012 Bei Jing Zheng Yi Wireless
 * @since      File available since Release 1.0 -- 2011-8-16 下午04:29:12
 * @author mengbing880814@yahoo.com.cn
 * 
 */

$page = $_GET ['page'] ? getgpc ('page') : 1;
$reply_num = $_GET ['reply_num'] ? getgpc ('reply_num') : 20;

loadcache('zywxdata');
$config = unserialize($_G['cache']['zywxdata']);
$hideids =	$config['hideforum'];

//最新回复总数
$num_total = 60;

if($num_total > $reply_num) {
	$pages = @ceil($num_total / $reply_num);
	$_G["zywy_totalpage"] = $pages;
}

/*MB add start*/
$jsonarr['zywy_curpage'] = $page;
$jsonarr['zywy_totalpage'] = $_G["zywy_totalpage"];
/*MB add end*/

$start = $reply_num * ($page-1);

$sql = "SELECT t.*, f.name FROM " . DB::table ('forum_thread') . " AS t 	
			LEFT JOIN " . DB::table ('forum_forum') . " AS f 
			ON f.fid=t.fid 	
			WHERE t.isgroup =0 AND t.displayorder>=0			
			ORDER BY t.lastpost DESC LIMIT $start, $reply_num";
	
$newReply = DB::getAll($sql);

foreach($newReply as $key => $value) {
	$value['dateline'] = dgmdate($value['dateline'], 'Y-n-j H:i');
	$value['lastpost'] = dgmdate($value['lastpost'], 'Y-n-j H:i');
	$newReply[$key] = $value;	
	if($hideids && in_array($value['fid'], $hideids)) {
		unset($newReply[$key]);
	}
}

if ($newReply){

	$jsonarr['ucurl'] = $_G["setting"]['ucenterurl'];
    $jsonarr['newReply']=$newReply;
	
	jsonexit ($jsonarr);
} else{
	jsonexit();
}
?>