<?php

/**
 * 搜索日志
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
 	$total	 = DB::result_first("SELECT count(blogid) FROM ".DB::table('home_blog')." WHERE subject LIKE'%".$keywords."%'");
 	$perpage = 10;
 	$curpage = !empty($_G['page'])? $_G['page']:1;
 	$backkeyword = !empty($_G[gp_kw]) ?$_G[gp_kw] :'';
 	$mpurl = "blog_serch_result.html?kw=$backkeyword";
 	$multipage = multi($total, $perpage, $curpage, $mpurl);
 	
 	$offset = ($curpage-1) * $perpage;
	$query = DB::query("SELECT a.blogid,a.uid,a.username,a.viewnum,a.replynum,a.subject, 
						a.dateline,a.picflag,b.message FROM ".DB::table('home_blog').
						" a LEFT JOIN ".DB::table('home_blogfield').
						" b ON a.blogid = b.blogid WHERE subject LIKE'%".
						$keywords."%' ORDER BY a.blogid DESC LIMIT $offset,$perpage");
	$userlist = array();
 	while($row = DB::fetch($query))
 	{
 			$userlist[$row['blogid']]['uid'] 	  = $row['uid'];
			$userlist[$row['blogid']]['username'] = $row['username'];
	 		$userlist[$row['blogid']]['userimg']  = $_G['setting']['ucenterurl']."/avatar.php?uid=".$row['uid']."&size=small";
	 		$userlist[$row['blogid']]['viewnum']  = $row['viewnum'];
	 		$userlist[$row['blogid']]['replynum'] = $row['replynum'];
			$userlist[$row['blogid']]['picflag'] = $row['picflag'];
	 		$userlist[$row['blogid']]['subject']  =  strlen($row['subject']) > 40 ? cutstr($row['subject'],40) : $row['subject'];//$row['subject'];
	 		$userlist[$row['blogid']]['dateline'] = dgmdate($row['dateline'],'u');//date('Y-m-d H:i',$row['dateline']);
			$userlist[$row['blogid']]['address'] = $row['address'];
	 		
			//LLX-过滤站长发布信息
			$pattern = '/(【.*易站长插件.*】)+/';
			if(preg_match($pattern,$row['message']))
			{
				$row['message'] = preg_replace($pattern,'',$row['message']);
			}
			//LLX-过滤经纬度信息
			$pattern = "/\[jw:((-?\d+)(\.\d+)?)\|((-?\d+)(\.\d+)?)\]/i";
			if(preg_match($pattern,$row['message']))
			{
				$row['message'] = preg_replace($pattern,'',$row['message']);
			}
			$row['message'] = strip_tags($row['message']);
			$row['message'] = preg_replace('/\[em:(\d)*:\]/','',$row['message']);
			$userlist[$row['blogid']]['message']  = cutstr($row['message'], 200);//mb_strimwidth($row['message'], 0, 30,"..>","UTF-8");
			$userlist[$row['blogid']]['blogid'] = $row['blogid'];
			
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