<?php
/**
 * 最新置顶
 *
 * 最新置顶数据返回
 *
 * @copyright  2011-2012 Bei Jing Zheng Yi Wireless
 * @since      File available since Release 1.0 -- 2011-8-16 下午04:29:12
 * @author mengbing880814@yahoo.com.cn
 * 
 */

$page = $_GET ['page'] ? getgpc ('page') : 1;
$thread_num = $_GET ['thread_num'] ? getgpc ('thread_num') : 10;

$start = $thread_num * ($page-1);

//$sortway = array ('replies', 'views', 'dateline', 'lastpost' );
//按最新置顶排序
$top = "dateline";

//调用一个月之内的置顶
$day = 30;

$ctime = $_G ['timestamp'] - (3600 * 24 * $day);

//置顶级别1：本版置顶 2：分类置顶 3：全局置顶
$top_rank = "1,2,3";

$topest = 'AND t.displayorder IN(' . $top_rank . ')';

//最新置顶总数
$num_total = 60;

if($num_total > $thread_num) {
	$pages = @ceil($num_total / $thread_num);
	$_G["zywy_totalpage"] = $pages;
}

/*MB add start*/
$jsonarr['zywy_curpage'] = $page;
$jsonarr['zywy_totalpage'] = $_G["zywy_totalpage"];
/*MB add end*/

//回复次数 /*AND t.replies!=0*/
$sql = "SELECT t.*, f.name, ff.formulaperm FROM " . DB::table ( 'forum_thread' ) . " t, " . DB::table ( 'forum_forum' ) . " f, " . DB::table ( 'forum_forumfield' ) . " ff";
$sql .= " WHERE f.status<>'3' AND f.fid=t.fid AND f.fid=ff.fid AND ff.password='' AND f.status IN(1,2) AND f.displayorder='0' $topest";
$sql .= " ORDER BY t.$top DESC LIMIT $start, $thread_num";

$topThread = custom_query ( $sql );

if (!empty ( $topThread )){
	$next_page = $_G["zywy_totalpage"] < 5 ? $_G["zywy_totalpage"] : 5;
    if(count($topThread) < $thread_num || $page == $next_page){
        $jsonarr['page'] = 0;
    }else{
        $jsonarr['page'] = $page + 1;
    }
	
	foreach($topThread as $key => $thread) {
		$topThread[$key]['formulaperm'] = unserialize($thread['formulaperm']);
		if($topThread[$key]['formulaperm']['medal'] || $topThread[$key]['formulaperm']['users']) {
			unset($topThread[$key]);
		}
	}
	
    $jsonarr['topThread']=$topThread;
	jsonexit ($jsonarr);
} else{
	jsonexit (null);
}
?>