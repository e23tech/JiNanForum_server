<?php

/**
 * 搜索用户
 *
 * file_description
 *
 * LICENSE Aushi Copyright
 *
 * @copyright  2011-2012 Bei Jing Zheng Yi Wireless
 * @version    $Id: \$
 * @since      File available since Release 1.0 -- 2011-8-18 下午02:52:04
 * @author 	LLX
 */

 if(!empty($_G['uid']))
 {
	if(CHARSET !='utf-8')
	{
		$_G[gp_kw]  = diconv($_G[gp_kw] ,'utf-8',CHARSET);
	}
 	$keywords =  !empty($_G[gp_kw]) && $_G[gp_kw] !='*'?$_G[gp_kw]:'';
 	//分页
 	$total	 = DB::result_first("SELECT count(uid) FROM ".DB::table('common_member')." WHERE username LIKE'%".$keywords."%'");
 	$perpage = 10;
 	$curpage = !empty($_G['page'])? $_G['page']:1;
 	$backkeyword = !empty($_G[gp_kw]) ?$_G[gp_kw] :'';
 	$mpurl = "user_search_result.html?kw=$backkeyword";
 	$multipage = multi($total, $perpage, $curpage, $mpurl);
 	
 	$offset = ($curpage-1) * $perpage;
	$query = DB::query("SELECT uid,username FROM ".DB::table('common_member')." WHERE username LIKE'%".$keywords."%' LIMIT $offset,$perpage");
 	$userlist = array();
 	while($row = DB::fetch($query))
 	{
 		$userComment = DB::result_first("SELECT message FROM ".DB::table('home_doing')." WHERE uid = ".$row['uid']." ORDER BY dateline DESC LIMIT 1");
 		//$userComment =  preg_replace("~src=\"(\w+)~i", 'src="'.$_G['setting']['discuzurl'].'/\\1', $userComment);
		$userComment = strip_tags($userComment);
 		$userlist[$row['uid']]['message']  = $userComment==false?'':$userComment;
 		$userlist[$row['uid']]['username'] = $row['username'];
 		$userlist[$row['uid']]['imgsrc'] = $_G['setting']['ucenterurl']."/avatar.php?uid=".$row['uid']."&size=small";
		$userlist[$row['uid']]['uid'] = $row['uid'];
 	}
 	$rs['list'] = $userlist;
 	$rs['mult'] = $total > $perpage? $multipage:'';
	
 	/*MB add start*/
 	$rs['zywy_curpage'] = $_G["zywy_curpage"];
	$rs['zywy_totalpage'] = $_G["zywy_totalpage"];
 	/*MB add end*/
	
	jsonexit($rs);
 }else 
 {
 	$msg = rpclang('search', 'user_unlogin');
 	jsonexit("{\"state\":\"$msg\"}");
 }