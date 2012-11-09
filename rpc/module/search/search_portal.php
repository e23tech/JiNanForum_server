<?php

/**
 * 搜索文章
 *
 * file_description
 *
 * LICENSE Aushi Copyright
 *
 * @copyright  2011-2012 Bei Jing Zheng Yi Wireless
 * @version    $Id: \$
 * @since      File available since Release 1.0 -- 2011-8-19 下午05:49:51
 * @author     LLX
 */
 
 
 	$keywords =  !empty($_G[gp_kw]) && $_G[gp_kw] !='*'?$_G[gp_kw]:'';
 	
 	//分页
 	$total	 = DB::result_first("SELECT count(aid) FROM ".DB::table('portal_article_title')." WHERE title LIKE'%".$keywords."%'");
 	$perpage = 10;
 	$curpage = !empty($_G['page'])? $_G['page']:1;
 	$backkeyword = !empty($_G[gp_kw]) ?$_G[gp_kw] :'';
 	$mpurl = "search.html?kw=$backkeyword&mod={$mod}";
 	$multipage = multi($total, $perpage, $curpage, $mpurl);
 	
 	$offset = ($curpage-1) * $perpage;
	$query = DB::query("SELECT aid,username,title,dateline FROM ".DB::table('portal_article_title') ." WHERE title LIKE'%".$keywords."%'  order by dateline desc LIMIT $offset,$perpage");
 	$userlist = array();
 	while($row = DB::fetch($query))
 	{
 		$userlist[$row['aid']]['username']  = $row['username'];
 		$userlist[$row['aid']]['title']    = $row['title'];
 		$userlist[$row['aid']]['dateline'] = date('Y-m-d H:i:s',$row['dateline']);
		$userlist[$row['aid']]['aid']    = $row['aid'];
		$userlist[$row['aid']]['viewnum']    = DB::getOne("SELECT viewnum FROM ".DB::table('portal_article_count') ." WHERE aid='".$row['aid']."'");
		$userlist[$row['aid']]['commentnum']    = DB::getOne("SELECT COUNT(cid) FROM ".DB::table('portal_comment') ." WHERE id='".$row['aid']."'");
		
 	}
 	$rs['list'] = $userlist;
	$rs['mult'] = $total > $perpage? $multipage:'';
	
 	/*MB add start*/
 	$rs['zywy_curpage'] = $_G["zywy_curpage"];
	$rs['zywy_totalpage'] = $_G["zywy_totalpage"];
 	/*MB add end*/
	
 	jsonexit($rs);