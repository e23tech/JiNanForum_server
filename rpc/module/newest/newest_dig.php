<?php
/**
 * 最新精华
 *
 * 最新精华数据返回
 *
 * @copyright  2011-2012 Bei Jing Zheng Yi Wireless
 * @since      File available since Release 1.0 -- 2011-8-16 下午04:29:12
 * @author mengbing880814@yahoo.com.cn
 * 
 */
$page = $_GET ['page'] ? getgpc ('page') : 1;
$thread_num = $_GET ['thread_num'] ? getgpc ('thread_num') : 10;

$start = $thread_num * ($page-1);

//精华级别1,2,3  区分图标
$dig_rank = "1,2,3";

$digest = 'AND t.digest IN(' . $dig_rank . ')';

//最新精华总数
$num_total = DB::getOne ("SELECT count(f.name) FROM " . DB::table ( 'forum_thread' ) . " t, " . DB::table ( 'forum_forum' ) . " f WHERE f.status<>'3' AND f.fid=t.fid $digest LIMIT 1000");

if($num_total > $thread_num) {
	$pages = @ceil($num_total / $thread_num);
	$_G["zywy_totalpage"] = $pages;
}

/*MB add start*/
$jsonarr['zywy_curpage'] = $page;
$jsonarr['zywy_totalpage'] = $_G["zywy_totalpage"];
/*MB add end*/

/**
 * 缓存时间为10分钟
 */
$digThread = zy_loadcache('zywx_digthread_'.$page, 600);

if(!$digThread) {
	$sql = "SELECT t.*, f.name, ff.formulaperm FROM " . DB::table ( 'forum_thread' ) . " t, " . DB::table ( 'forum_forum' ) . " f, " . DB::table ( 'forum_forumfield' ) . " ff";
	$sql .= " WHERE f.status<>'3' AND f.fid=t.fid AND f.fid=ff.fid AND ff.password='' $digest ";
	$sql .= " ORDER BY t.tid DESC LIMIT $start, $thread_num";
	$digThread = custom_query($sql);
	zy_savecache('zywx_digthread_'.$page, $digThread);
}

if ($digThread){
	$next_page = $_G["zywy_totalpage"] < 5 ? $_G["zywy_totalpage"] : 5;
    if(count($digThread) < $thread_num || $page == $next_page){
        $jsonarr['page'] = 0;
    }else{
        $jsonarr['page'] = $page + 1;
    }
	
	foreach($digThread as $key => $thread) {
		$digThread[$key]['formulaperm'] = unserialize($thread['formulaperm']);
		if($digThread[$key]['formulaperm']['medal'] || $digThread[$key]['formulaperm']['users']) {
			unset($digThread[$key]);
		}
	}
	
    $jsonarr['digThread']=$digThread;
	jsonexit($jsonarr);
} else{
	jsonexit(null);
}

?>