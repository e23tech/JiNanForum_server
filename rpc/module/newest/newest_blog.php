<?php
/**
 * 日志列表
 *
 * 日志列表数据返回
 *
 * @copyright  2011-2012 Bei Jing Zheng Yi Wireless
 * @since      File available since Release 1.0 -- 2011-8-16 下午04:29:12
 * @author     mengbing880814@yahoo.com.cn
 * 
 */

/*$blog_num 显示数目*/
$page = $_GET ['page'] ? getgpc ('page') : 1;
$blog_num = $_GET ['blog_num'] ? getgpc ('blog_num') : 10;

$start = $blog_num * ($page-1);

//调用一个月之内的日志
$day = 30;

$ctime =  $_G ['timestamp'] - (3600 * 24 * $day);

//最新日志总数
$num_total = DB::getOne ("SELECT count(uid) FROM " . DB::table ( 'home_blog' ) . " where status='0' LIMIT 1000");

if($num_total > $blog_num) {
	$pages = @ceil($num_total / $blog_num);
	$_G["zywy_totalpage"] = $pages;
}

/*MB add start*/
$jsonarr['zywy_curpage'] = $page;
$jsonarr['zywy_totalpage'] = $_G["zywy_totalpage"];
/*MB add end*/

$sql = "SELECT blogid,uid,username,subject,viewnum,replynum,dateline FROM " . DB::table ( 'home_blog' );
$sql .= " where status='0' ORDER BY blogid DESC LIMIT $start, $blog_num";

$newblog = array();
$query = DB::query($sql);

while ($result = DB::fetch($query)) {
	$result['subject'] = cutstr($result['subject'], 40, '..');
	$result['dateline'] = gmdate('Y-m-d H:i', $result['dateline'] + $_G['setting']['timeoffset'] * 3600);
	$newblog[] = $result;
}

    unset($sql, $query, $result);

if (!empty ( $newblog )){
	$next_page = $_G["zywy_totalpage"] < 5 ? $_G["zywy_totalpage"] : 5;
    if(count($newblog) < $blog_num || $page == $next_page){
        $jsonarr['page'] = 0;
    }else{
        $jsonarr['page'] = $page + 1;
    }
    $jsonarr['newblog']=$newblog;
	jsonexit ($jsonarr);
} else{
	jsonexit (null);
}
?>