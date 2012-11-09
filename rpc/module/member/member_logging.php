<?php
/**
 * 会员登录
 *
 *
 * file_description
 *
 * LICENSE Aushi Copyright
 *
 * @copyright  2011-2012 Bei Jing Zheng Yi Wireless
 * @since      File available since Release 1.0 -- 2011-8-16 上午09:29:57
 * @author 	LLX
 */

$pluginid = DB::getOne("SELECT pluginid FROM ".DB::table('common_plugin')." WHERE identifier='zywx'");

if(empty($pluginid)) 
{
	$msg = rpclang('member', 'plugin_not_install');
	jsonexit("{\"status\":\"$msg\"}");
}

if(!in_array($_G['gp_action'], array('login', 'logout','location')))
{
	jsonexit("{\"status\":\"Exception\"}");
}

if($_G['gp_action'] == 'login')
{
	//参数初始化 
	$_GET['loginsubmit'] = 'yes';	
		
	//用户名空值检查
	if(empty($_GET['username'])) 
	{
		$msg = rpclang('member', 'username_empty');
		jsonexit("{\"status\":\"$msg\"}");
	}
	//密码空值检查
	if(empty($_GET['password']))
	{
		$msg = rpclang('member', 'password_empty');
		jsonexit("{\"status\":\"$msg\"}");
	}
 
	if(CHARSET !='utf-8')
	{
		$_GET['username']  = diconv($_GET['username'],'utf-8',CHARSET);
		$_G['gp_username'] = $_GET['username'];
	}

	include_once ROOT_DIR . '/source/module/member/member_'.$_G['mod'].'.php';
	ob_end_clean();

	if($_G['uid']) 
	{
		
		updatesession();
		
		    
		//if(!empty($_GET['longitude']) && !empty($_GET['latitude']))
		//{
			$sql = "INSERT INTO ".DB::table('zywx_useroperation')."(uid, username, dateline) VALUES('".$_G['uid']."','".$_G['username']."', '".TIMESTAMP."');";
			DB::query($sql);
				
			//随机清除大于7天的用户经纬度信息
			if(mt_rand(0, 10) == 1) {
				DB::query("INSERT INTO ".DB::table('zywx_useroperation_log').
				" SELECT * FROM ".DB::table('zywx_useroperation')." WHERE dateline<".(TIMESTAMP-604800), 'SILENT');
				
				if(DB::affected_rows()) {
					$sql = "DELETE FROM ".DB::table('zywx_useroperation')." WHERE dateline<".(TIMESTAMP-604800);
					DB::query($sql);
				}
			}
				
		//}
			
		$setting = DB::getOne("SELECT privacy FROM ".DB::table('common_member_field_home')." WHERE uid='$_G[uid]'");
		$setting = unserialize($setting);
		
		/**
		 *begin 
		 *@todo		增加 每户开启字段，兼容2.1版本
		 *@since	File available since Release 1.0 -- 2012-3-10 下午02:01:01 
		 *@author	yjt<yinjitao2001@163.com> 
		 *
		 */
		
		$articlecount = DB::getOne("SELECT COUNT(*) FROM ".DB::table('portal_article_title'));
		$portalstatus = ($_G['setting']['portalstatus'] && $articlecount) ? 1 : 0;
		
		loadcache('zywxdata');
		$config = unserialize($_G['cache']['zywxdata']);
	
		$hideids =	$config['hideportal'];
	 	$hideids = implode(',', $hideids);
		if(!$hideids) $hideids = 0;
		$cids = DB::getCol("SELECT catid FROM ".DB::table('portal_category')." WHERE catid NOT IN(".$hideids.")");
		if(empty($cids)) {
			$portalstatus = 0;
		}
		/**
		 *end 
		 * */
		if($setting['appcan']['imagemode']) {
			$imagemode =1;
		} else {
			$imagemode = 0;
		}
			
		jsonexit("{\"status\":\"LoginSuccess\", \"uid\":\"$_G[uid]\", \"imagemode\":\"".$imagemode."\",\"portalstatus\":\"".$portalstatus."\"}");
		
	}else
	{ //登录失败
		$msg = 'password_error';//rpclang('member', 'password_error');
		jsonexit("{\"status\":\"$msg\"}");
	}

}elseif($_G['gp_action'] == 'location'){
			
			if(empty($_G['uid'])){
				$msg = rpclang('member', 'to_login');
				jsonexit("{\"status\":\"$msg\"}");
			}
			$phonename = empty($_GET['phonename'])?"unknown":$_GET['phonename'];
			if(!empty($_GET['longitude']) && !empty($_GET['latitude']))
			{
				$dateline = DB::getOne("SELECT dateline FROM ".DB::table('zywx_useroperation')." WHERE uid='".$_G['uid']."' ORDER BY dateline DESC");
				
				$sql = "UPDATE ".DB::table('zywx_useroperation')." SET uid='".$_G['uid']."', 
				username='".$_G['username']."', phone_name='".$phonename."',
				latitude='".$_GET['latitude']."', longitude='".$_GET['longitude']."', dateline='".TIMESTAMP."' WHERE uid='".$_G['uid']."' AND dateline='".$dateline."'";
				$flag = DB::query($sql);
				
				//随机清除大于7天的用户经纬度信息
				if(mt_rand(0, 10) == 1) {
					DB::query("INSERT INTO ".DB::table('zywx_useroperation_log').
					" SELECT * FROM ".DB::table('zywx_useroperation')." WHERE dateline<".(TIMESTAMP-604800), 'SILENT');
				
					if(DB::affected_rows()) {
						$sql = "DELETE FROM ".DB::table('zywx_useroperation')." WHERE dateline<".(TIMESTAMP-604800);
						DB::query($sql);
					}
				}
				
				if($flag){
					jsonexit("{\"status\":\"1\"}");
				}else{
					jsonexit("{\"status\":\"0\"}");
				}
			}
}
else 
{

	require ROOT_DIR . '/source/module/member/member_'.$_G['mod'].'.php';
	updatesession();
	//include_once libfile('function/member');
	//clearcookies();
	dsetcookie('authcode', '');
	ob_end_clean();
	jsonexit("{\"status\":\"LogoutSuceess\"}");
}

?>
