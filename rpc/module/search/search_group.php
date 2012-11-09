<?php

/**
 * 搜索群组
 *
 * file_description
 *
 * LICENSE Aushi Copyright
 *
 * @copyright  2011-2012 Bei Jing Zheng Yi Wireless
 * @version    $Id: \$
 * @since      File available since Release 1.0 -- 2011-8-19 下午02:46:45
 * @author     LLX
 */

	if(CHARSET !='utf-8')
	{
		$_G[gp_kw]  = diconv($_G[gp_kw],'utf-8',CHARSET);
	}
 	$keywords =  !empty($_G[gp_kw]) && $_G[gp_kw] !='*'?$_G[gp_kw]:'';
 	
 	//分页
 	$total	 = DB::result_first("SELECT count(fid) FROM ".DB::table('forum_forum')." WHERE type='sub' &&  name LIKE'%".$keywords."%'");
 	$perpage = 10;
 	$curpage = !empty($_G['page'])? $_G['page']:1;
 	$backkeyword = !empty($_G[gp_kw]) ?$_G[gp_kw] :'';
 	$mpurl = "g_serch_result.html?kw=$backkeyword";
 	$multipage = multi($total, $perpage, $curpage, $mpurl);
 	
 	$offset = ($curpage-1) * $perpage;
	$query = DB::query("SELECT a.fid,a.name,b.icon FROM ".DB::table('forum_forum')." a LEFT JOIN ".DB::table('forum_forumfield')." b ON a.fid = b.fid   WHERE a.type='sub' && a.name LIKE'%".$keywords."%' LIMIT $offset,$perpage");
 	$userlist = array();
 	while($row = DB::fetch($query))
 	{
 		$usernum = DB::result_first("SELECT count(fid) FROM ".DB::table('forum_groupuser')." WHERE fid=".$row['fid']);
 		$userlist[$row['fid']]['num'] =  $usernum;
 		$userlist[$row['fid']]['name'] = $row['name'];
 		$userlist[$row['fid']]['icon'] = $row['icon']==''?'':$_G['siteurl'].'data/attachment/group/'.$row['icon'];
 	}
 	$rs['list'] = $userlist;
	$rs['mult'] = $total > $perpage? $multipage:'';
	
 	/*MB add start*/
 	$rs['zywy_curpage'] = $_G["zywy_curpage"];
	$rs['zywy_totalpage'] = $_G["zywy_totalpage"];
 	/*MB add end*/

 	jsonexit($rs);