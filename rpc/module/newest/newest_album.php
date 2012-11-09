<?php
/**
 * 相册列表
 *
 * 相册列表数据返回
 *
 * @copyright  2011-2012 Bei Jing Zheng Yi Wireless
 * @since      File available since Release 1.0 -- 2011-8-16 下午04:29:12
 * @author     mengbing880814@yahoo.com.cn
 * 
 */

/*$album_num 显示数目*/
$page = $_GET ['page'] ? getgpc ('page') : 1;
$album_num = $_GET ['album_num'] ? getgpc ('album_num') : 12;

$start = $album_num * ($page-1);

//调用一个月之内的相册
$day = 30;

$ctime =  $_G ['timestamp'] - (3600 * 24 * $day);

//最新相册总数
$num_total = DB::getOne ("SELECT count(albumid) FROM " . DB::table ( 'home_album ' ) . " where friend = 0 AND updatetime > $ctime");

if($num_total > $album_num) {
	$pages = @ceil($num_total / $album_num);
	$_G["zywy_totalpage"] = $pages;
}

/*MB add start*/
$jsonarr['zywy_curpage'] = $page;
$jsonarr['zywy_totalpage'] = $_G["zywy_totalpage"];
/*MB add end*/

$sql = "SELECT albumid,albumname,uid,username,pic,picnum,updatetime,dateline FROM " . DB::table ( 'home_album ' );
$sql .= " where friend = 0 AND updatetime > $ctime order by updatetime DESC LIMIT $start, $album_num";

$newalbum = array();
$query = DB::query($sql);

while ($result = DB::fetch($query)) {
	$result['albumname'] = cutstr($result['albumname'], 20, '...');
	$result['pic'] = $_G['siteurl'].'/'.$_G ['setting'] ['attachurl'] . 'album/' . $result['pic'];
	$result['updatetime'] = gmdate('Y-m-d H:i', $result['dateline'] + $_G['setting']['timeoffset'] * 3600);
	$newalbum[] = $result;
}

    unset($sql, $query, $result);

if (!empty ( $newalbum )){
	$next_page = $_G["zywy_totalpage"] < 5 ? $_G["zywy_totalpage"] : 5;
    if(count($newalbum) < $album_num || $page == $next_page){
        $jsonarr['page'] = 0;
    }else{
        $jsonarr['page'] = $page + 1;
    }
    $jsonarr['newalbum']=$newalbum;
	jsonexit ($jsonarr);
} else{
	jsonexit (null);
}
?>