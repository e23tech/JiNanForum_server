<?php
/**
 * 图片列表
 *
 * 图片列表数据返回
 *
 * @copyright  2011-2012 Bei Jing Zheng Yi Wireless
 * @since      File available since Release 1.0 -- 2011-8-16 下午04:33:48
 * @author author
 * 
 */

//显示图片数量
$page = $_GET ['page'] ? getgpc ('page') : 1;

$picNums = 9;

//最新图片总数
$num_total = DB::getOne ("SELECT count(img.attachment) FROM " . DB::table ( 'forum_threadimage' ) . " img INNER JOIN " . DB::table ( 'forum_attachment' ) . " att ON att.tid=img.tid");

if($num_total > $picNums) {
	$pages = @ceil($num_total / $picNums);
	$_G["zywy_totalpage"] = $pages;
}

/*MB add start*/
$jsonarr['zywy_curpage'] = $page;
$jsonarr['zywy_totalpage'] = $_G["zywy_totalpage"];
/*MB add end*/

$start = $picNums * ($page-1);

//日期先后顺序
$orderby = 'tid';
/*
$sql = "SELECT attach.attachment,t.tid, t.fid, t.subject FROM " . DB::table ( 'forum_threadimage' ) . " attach ";
$sql .= " INNER JOIN " . DB::table ( 'forum_thread' ) . " t ON t.tid=attach.tid";
$sql .= " WHERE t.isgroup=0 AND t.displayorder>=0 GROUP BY attach.tid ORDER BY $orderby DESC LIMIT $start,$picNums";
*/
$sql = "SELECT img.attachment,att.tid,att.aid,att.tableid FROM " . DB::table ( 'forum_threadimage' ) . " img ";
$sql .= " INNER JOIN " . DB::table ( 'forum_attachment' ) . " att ON att.tid=img.tid";
$sql .= " ORDER BY $orderby DESC LIMIT $start,$picNums";

$result = DB::query ( $sql );
while ( $pic = DB::fetch ( $result ) ){
	//$pics ['pics'] = $_G['siteurl'].'/'.$_G ['setting'] ['attachurl'] . 'forum/' . $pic ['attachment'];
		
	//if (is_file(ROOT_DIR.'/'.$_G ['setting'] ['attachurl'].'forum/'.$pic ['attachment']) == 'false') {
		$img = parse_attach($pic['aid'],$pic['tableid'],FALSE);
		preg_match('/src="(.*?)"/', $img , $match);
		$pics ['pics'] = $match[1];
		$pics ['subject'] = str_replace ( '\'', ' ', $pic ['subject'] );
		$pics ['aid'] = $pic ['aid'];
		$pics ['tid'] = $pic ['tid'];
		$imgs [] = $pics;
	//}
}

if (!empty ( $imgs )){
	$next_page = $_G["zywy_totalpage"] < 5 ? $_G["zywy_totalpage"] : 5;
    if(count($imgs ) < $picNums || $page == $next_page){
        $jsonarr['page'] = 0;
    }else{
        $jsonarr['page'] = $page + 1;
    }
    $jsonarr['imgs']=$imgs;
	jsonexit ($jsonarr);
} else{
	jsonexit (null);
}

?>