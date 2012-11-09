<?php

/**
 * 搜索帖子
 *
 * file_description
 *
 * LICENSE Aushi Copyright
 *
 * @copyright  2011-2012 Bei Jing Zheng Yi Wireless
 * @version    $Id: \$
 * @since      File available since Release 1.0 -- 2011-8-19 下午05:03:37
 * @author     LLX
 */
	if(CHARSET !='utf-8')
	{
		$_G[gp_kw]  = diconv($_G[gp_kw],'utf-8',CHARSET);
	}
 	$keywords =  !empty($_G[gp_kw]) && $_G[gp_kw] !='*'?$_G[gp_kw]:'';

	$condition = " 1 AND ";
	
 	//分页
 	$total	 = DB::result_first("SELECT count(tid) FROM ".DB::table('forum_thread')." a WHERE $condition subject LIKE '%".$keywords."%' LIMIT 1000");

 	$perpage = 10;
 	$curpage = !empty($_G['page'])? $_G['page']:1;
 	$backkeyword = !empty($_G[gp_kw]) ?$_G[gp_kw] :'';
 	$mpurl = "forum_search_result.html?kw=$backkeyword";
 	$multipage = multi($total, $perpage, $curpage, $mpurl);
 	
 	$offset = ($curpage-1) * $perpage;
	$query = DB::query("SELECT a.tid,a.subject,a.dateline,a.views,a.replies,a.attachment,a.authorid,a.author,a.closed,a.digest,b.message FROM ".DB::table('forum_thread') ." a LEFT JOIN ".DB::table('forum_post')." b  ON a.tid=b.tid AND b.first=1 WHERE $condition a.subject LIKE '%".$keywords."%'  order by a.lastpost desc LIMIT $offset,$perpage");

 	$userlist = array();
 	while($row = DB::fetch($query))
 	{
 		$userlist[$row['tid']]['subject']  = strlen($row['subject']) > 40 ? cutstr($row['subject'],40) : $row['subject'];///$row['subject'];
 		$userlist[$row['tid']]['dateline'] = dgmdate($row['dateline'],'u');//date('Y-m-d H:i',$row['dateline']);
 		$userlist[$row['tid']]['views']    = $row['views'];
 		$userlist[$row['tid']]['replies']  = $row['replies'];
		$userlist[$row['tid']]['author']  = $row['author'];
		$userlist[$row['tid']]['authorid']  = $row['authorid'];
		$userlist[$row['tid']]['attachment']  = $row['attachment'];
		$userlist[$row['tid']]['closed']  = $row['closed'];
		$userlist[$row['tid']]['digest']  = $row['digest'];
		$userlist[$row['tid']]['address']  = $row['address'];

		require_once libfile('function/discuzcode');
		//$row['message'] = discuzcode($row['message'], $row['smileyoff'], $row['bbcodeoff'], 1, $_G['forum']['allowsmilies'], 1, ($_G['forum']['allowimgcode'] && $_G['setting']['showimages'] ? 1 : 0), 1, ($_G['forum']['jammer'] && $row['authorid'] != $_G['uid'] ? 1 : 0), 0, $row['authorid'], $_G['cache']['usergroups'][$row['groupid']]['allowmediacode'] && $_G['forum']['allowmediacode'], $row['pid'], $_G['setting']['lazyload']);
		 
	    require_once libfile('function/post');
		$row['message'] = messagecutstr($row['message'],400);

		$row['message'] = preg_replace("/\[hide\]\[\/hide\]/", '****本内容被作者隐藏****', $row['message']);
		$row['message'] = preg_replace("/\[attach\](.+?)\[\/attach\]/", '', $row['message']);
		$row['message'] = preg_replace('/(【.*易站长插件.*】)+/','',$row['message']);
		$row['message'] = preg_replace("/\[jw:((-?\d+)(\.\d+)?)\|((-?\d+)(\.\d+)?)\]/i",'',$row['message']);

		$userlist[$row['tid']]['message']  = cutstr($row['message'], 200);//mb_strimwidth($row['message'], 0, 200,"...","UTF-8");
		$userlist[$row['tid']]['tid']  = $row['tid'];
 	}
		
 	$rs['list'] = $userlist;
 	$rs['mult'] = $total > $perpage? $multipage:'';
 	
 	/*MB add start*/
 	$rs['zywy_curpage'] = $_G["zywy_curpage"];
	$rs['zywy_totalpage'] = $_G["zywy_totalpage"];
	$rs['ucurl'] = $_G["setting"]['ucenterurl'];
 	/*MB add end*/
	
 	jsonexit($rs);