<?php

/**
 * 搜索相册
 *
 * file_description
 *
 * LICENSE Aushi Copyright
 *
 * @copyright  2011-2012 Bei Jing Zheng Yi Wireless
 * @version    $Id: \$
 * @since      File available since Release 1.0 -- 2011-8-19 下午04:15:17
 * @author     LLX
 */
 

 if(!empty($_G['uid']))
 {
	if(CHARSET !='utf-8')
	{
		$_G[gp_kw]  = diconv($_G[gp_kw],'utf-8',CHARSET);
	}
 	$keywords =  !empty($_G[gp_kw]) && $_G[gp_kw] !='*'?$_G[gp_kw]:'';

 	//分页
 	$total	 = DB::result_first("SELECT count(albumid) FROM ".DB::table('home_album')." WHERE albumname LIKE'%".$keywords."%'");
 	$perpage = 10;
 	$curpage = !empty($_G['page'])? $_G['page']:1;
 	$backkeyword = !empty($_G[gp_kw]) ?$_G[gp_kw] :'';
 	$mpurl = "ablum_serch_result.html?kw=$backkeyword";
 	$multipage = multi($total, $perpage, $curpage, $mpurl);
 	
 	$offset = ($curpage-1) * $perpage;
	$query = DB::query("SELECT albumid,uid,albumname,username,updatetime,pic,picnum FROM ".DB::table('home_album')." WHERE albumname LIKE'%".$keywords."%' ORDER BY updatetime DESC LIMIT $offset,$perpage");
 	$userlist = array();
 	while($row = DB::fetch($query))
 	{
 		$userlist[$row['albumid']]['uid']   = $row['uid'];
 		$userlist[$row['albumid']]['albumname']   = $row['albumname'];
 		$userlist[$row['albumid']]['username']    = $row['username'];
 		$userlist[$row['albumid']]['updatetime']  = date('m-d H:i',$row['updatetime']);
 		$userlist[$row['albumid']]['pic'] 		= $row['pic']==''?'':$_G['siteurl'].'data/attachment/album/'.$row['pic'];
 		$userlist[$row['albumid']]['picnum']    = $row['picnum'];
		$userlist[$row['albumid']]['albumid']   = $row['albumid'];
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