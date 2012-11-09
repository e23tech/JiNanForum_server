<?php
/**
 * 群组列表
 *
 * 群组列表数据返回
 *
 * @copyright  2011-2012 Bei Jing Zheng Yi Wireless
 * @since      File available since Release 1.0 -- 2011-8-16 下午04:29:12
 * @author     mengbing880814@yahoo.com.cn
 * 
 */

/*$group_num 显示数目*/
$page = $_GET ['page'] ? getgpc ('page') : 1;
$group_num = $_GET ['group_num'] ? getgpc ('group_num') : 10;

$start = $group_num * ($page-1);

//调用一个月之内的群组
$day = 30;

$ctime =  $_G ['timestamp'] - (3600 * 24 * $day);

//最新群组总数
$num_total = DB::getOne ("SELECT count(f.name) FROM ".DB::table('forum_forum')." as f LEFT JOIN ".DB::table('forum_forumfield')." as ff ON ff.fid=f.fid WHERE f.type='sub' AND f.status='3'");

if($num_total > $group_num) {
	$pages = @ceil($num_total / $group_num);
	$_G["zywy_totalpage"] = $pages;
}

/*MB add start*/
$jsonarr['zywy_curpage'] = $page;
$jsonarr['zywy_totalpage'] = $_G["zywy_totalpage"];
/*MB add end*/

$sql = "SELECT f.fid, f.name,f.threads,ff.membernum,ff.dateline FROM ".DB::table('forum_forum')." as f ";
$sql .= " LEFT JOIN ".DB::table('forum_forumfield')." as ff ON ff.fid=f.fid";
$sql .= " WHERE f.type='sub' AND f.status='3' order by ff.dateline DESC LIMIT $start,$group_num";

$newgroup = array();
$query = DB::query($sql);

while ($result = DB::fetch($query)) {
	$result['name'] = cutstr($result['name'], 40, '..');
	$result['dateline'] = gmdate('Y-m-d H:i', $result['dateline'] + $_G['setting']['timeoffset'] * 3600);
	$newgroup[] = $result;
}

    unset($sql, $query, $result);

if (!empty ( $newgroup )){
	$next_page = $_G["zywy_totalpage"] < 5 ? $_G["zywy_totalpage"] : 5;
    if(count($newgroup) < $group_num || $page == $next_page){
        $jsonarr['page'] = 0;
    }else{
        $jsonarr['page'] = $page + 1;
    }
    $jsonarr['newgroup']=$newgroup;
	jsonexit ($jsonarr);
} else{
	jsonexit (null);
}
?>