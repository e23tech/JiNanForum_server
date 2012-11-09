<?php
/*
	AppCan后台设置
*/

set_time_limit(0);
include DISCUZ_ROOT.'./source/plugin/zywx/config.php';

/**
 * 如果SESSION为空则从数据库
 * 中取出此配置信息并放入SESSION
 */
$config = zy_loadcache('zywxdata', false);		
if(is_array($config)) {
	$_SESSION = $config;
}

/**
* 当前脚本路径
*/
$url = $_G['siteurl']."admin.php?action=plugins&operation=config&do=$pluginid&identifier=zywx&pmod=main";

/**
* 从setting表中取出应用的版本号
* 默认版本号是0.1
*/
$version = DB::fetch_first("SELECT svalue FROM ".DB::table('common_setting')." 	
							WHERE skey='zywxversion'");
$version = $version['svalue'];						
$appkey = $_G['setting']['zywxappkey'];				
$version = $version ? $version : '0.1';

/**
 * 表单POST过来的数据，会由post.inc.php
 * 专门对POST数据处理
 */
if($_POST) {
	require ZYWX_PATH.'/post.inc.php';		
}

/**
 * 重新打包时，会传过来version参数
 * 如果参数中的应用 版本号大于当前版本号
 * 则把参数中的版本号保存到setting中
 * 并更新setting缓存
 */
if($_G['gp_version']) {
	if($version < $_G['gp_version']) {				
		$version = $_G['gp_version'];				
		$_SESSION['step'] = $_G['gp_op'] = 'selectstyle';		
		DB::insert('common_setting', array('skey' => 'zywxversion', 'svalue' => $_G['gp_version']) , true, true);
		updatecache('setting');	
	}
}

/**
* op参数为当前的操作
* 如果gp中没有此参数并且SESSION中保
*存操作步骤的话则使用SESSOIN中保存的操作步骤
*/
if(!$_G['gp_op'] && $_SESSION['step'] ) {
	$_G['gp_op'] = $_SESSION['step'];
}

/**
 * 如果gp中没有op参数的话当前操作页面为"风格选择"
 */
if(!$_G['gp_op']) {
	$_G['gp_op'] = 'selectstyle';
}

if($_G['gp_op'] == 'login') {

	if(!$_G['gp_email']) {
		ob_start();
		include template('zywx:login');
		$body = ob_get_contents();
		ob_end_clean();
		$body = utf8togbk($body);		
		echo $body;
		exit;
	} else {
		DB::insert('common_setting', array('skey' => 'zywxemail', 'svalue' => $_G['gp_email']), 0, 1);
		updatecache('setting');
		$_G['gp_op'] = 'selectstyle';
	}
}

/**
 * 首次进入“个性设置”时会显示输入邮箱页面
 * 输入邮箱模板是utf-8版本，如果站点为gbk版本的话
 * 会进行编码转换
 */
if(!$_G['setting']['zywxemail']) {
	ob_start();
	include template('zywx:setemail');	
	$body = ob_get_contents();				
	ob_clean();
	$body = utf8togbk($body);
	echo $body;								
	exit;							
}

/**
 * 推广
 */
$invited = zy_loadcache('zywx_invited', false);
$channel_invited = zy_loadcache('zywx_channel_invited', false);	

if(!$invited && !$channel_invited) {
	if(!$invited && file_exists(DISCUZ_ROOT.'./source/plugin/zywx/channel.html')) {
		zy_savecache('zywx_invited', '1');
		$domain = dreadfile(DISCUZ_ROOT.'./source/plugin/zywx/channel.html');
		$invite_url = ZYWX_APPCAN."/plugin/inviteReport.action?".
							"domainName=".$domain.
							"&pluginName=discuz".
							"&app_key=".$_G['setting']['zywxappkey'];
							
		$data = json_decode(trim(get_url_contents($invite_url)));
		if($data->status == 'ok') {
			zy_savecache('zywx_invited', '1');
		} else {
			zy_savecache('zywx_invited', '0');
		}
	} else {
		$result = trim(get_url_contents("http://wgb.tx100.com/plugin/pluginInviteReg.action?channelCode={$channelCode}&siteUrl={$_G['siteurl']}"));
		$result = json_decode($result);
		if($result->status == 'ok') {
			if($result->msg) {
				zy_savecache('zywx_channel_invite_url', $result->msg);
			}
			zy_savecache('zywx_channel_invited', '1');
		} else {
			zy_savecache('zywx_channel_invited', '0');
		}	
	}
}
	
/**
 * 如果setting表中没有应用appkey的话
 * 会执行下面的IF从服务器获取appkey
 * 并保存在setting表中
 * 提示信息：请不要重复请求，服务器正在创建用户，请稍后
 */
$reg_lock = zy_loadcache('zywx_reg_lock');
if(!$_G['setting']['zywxappkey']) {
	if($reg_lock) {	
		cpmsg('&#35831;&#19981;&#35201;&#37325;&#22797;&#35831;&#27714;&#65292;&#26381;&#21153;&#22120;&#27491;&#22312;&#21019;&#24314;&#29992;&#25143;&#65292;&#35831;&#31245;&#21518;','','error');
	}
	
	cpmsg('&#26381;&#21153;&#22120;&#27491;&#22312;&#21019;&#24314;&#29992;&#25143;&#65292;&#35831;&#31245;&#21518;','','loading', '' , '' , '', TRUE);
	ob_flush(); 
	flush();
	
	zy_savecache('zywx_reg_lock', '1', 1800);
	$data = trim(get_url_contents(ZYWX_PROXY."/index.php?m=curl&a=registeApp&pluginName=discuz".
								   "&domain=".$_G['siteurl'].
								   "&authcode=".trim($_G['setting']['zywxid'])));
	
	$data = json_decode($data);	
	echo "<script>$('cpcontainer').innerHTML='';</script><br/><br/>";
	ob_flush(); 
	flush();
	if($data->msg) {						
		$message = $data->msg;				
		$message = utf8togbk($message);
		zy_savecache('zywx_reg_lock', '0');
		cpmsg($message, $url, 'error', 1);		
	} elseif($data->appkey) {				
		$appkey = $data->appkey;			
		DB::insert('common_setting', array('skey' => 'zywxappkey', 'svalue' => $appkey), true, true);
		updatecache('setting');
		zy_savecache('zywx_reg_lock', '0');
	} else {
		zy_savecache('zywx_reg_lock', '0');
		cpmsg('&#25265;&#27465;&#65292;&#36828;&#31243;&#26381;&#21153;&#22120;&#21709;&#24212;&#36229;&#26102;&#65292;&#35831;&#32852;&#31995;&#25554;&#20214;&#25552;&#20379;&#21830;', '' ,'error', 1);
	}
}

/**
 * 风格设置页面
 */
if($_G['gp_op'] == 'selectstyle') {			
	$_SESSION['step'] = 'selectstyle';			
	zy_savecache('zywxdata', $_SESSION);		
	ob_start();									
	include template('zywx:selectstyle');		
	$body = ob_get_contents();					
	ob_clean();									
	$body = utf8togbk($body);					
	echo $body;									
}

/**
* 内容设置页面
*/
elseif($_G['gp_op'] == 'setcontent') {			
			
	zy_savecache('zywxdata', $_SESSION);		
	//论坛版块select
	require_once libfile('function/forumlist');
	$forumselect = '<select name="hideforum[]" size="10" multiple="multiple">
					<option value="">'.cplang('plugins_empty').'</option>'.
				    forumselect(FALSE, 0, $_SESSION['hideforum'], TRUE).
				    '</select>';
	//门户栏目select				
	$portalselect = portalselect($_SESSION['hideportal']);
	
	$forumselect = gbktoutf8($forumselect, true);
	$portalselect = gbktoutf8($portalselect, true);
	$_SESSION = gbktoutf8($_SESSION, true);
	
	ob_start();	
	include template('zywx:setcontent');
	$body = ob_get_contents();
	ob_clean();									
	$body = utf8togbk($body);					
	echo $body;	
}

/**
* 应用设置页面
*/
elseif($_G['gp_op'] == 'setbuild') {			
	$_SESSION['step'] = 'setbuild';				
	zy_savecache('zywxdata', $_SESSION);		
	$iframeurl  = $_G['gp_url'] ? $_G['gp_url'] : ZYWX_APPCAN.'/plugin/create_app_plugin.action';
	$upgrade_url = ZYWX_PROXY."/index.php?m=curl&plugin_name=discuz&a=getNewestVersion";	
	$newver = floatval(file_get_contents($upgrade_url));		
	if($newver > $plugin['version']) {			
		$upgrade = 1;											
	}
	
	ob_start();	
	include template('zywx:setbuild');
	$body = ob_get_contents();
	ob_clean();									
	$body = utf8togbk($body);					
	echo $body;		
}

/**
* 管理应用页面
*/
elseif($_G['gp_op'] == 'applist') {				
	$iframeurl  = ZYWX_APPCAN.'/plugin/plugin_app_detail.action';
	
	ob_start();
	include template('zywx:setbuild');
	$body = ob_get_contents();
	ob_clean();									
	$body = utf8togbk($body);					
	echo $body;		
}

/**
* 行为统计页面
*/
elseif($_G['gp_op'] == 'stat') {			
		
	$perpage = 10;							
	$page = $_GET['page'] ? max(intval($_GET['page']), 1) : 1;		
	$start = ($page - 1) * $perpage;		
	$wherearr = array();			
	if($_G['gp_start_time'] && $_G['gp_end_time']) {		
		$start_time = strtotime($_G['gp_start_time']);		
		$end_time = strtotime($_G['gp_end_time']);			
		$wherearr[] = "dateline > $start_time AND dateline < $end_time";		
		$postwhere = "pf.dateline > $start_time AND pf.dateline < $end_time";	
	}
	if($_G['gp_username']) {			
		$wherearr[] = "username='$_G[gp_username]'";		
	}
	$postwhere = ' 1 ';		
	if($wherearr) $where = ' AND '.implode(' AND ', $wherearr);	
	$data = DB::fetch_first("SELECT COUNT(uid) AS count FROM ".DB::table('zywx_useroperation_log'));	
	$log_count = $data['count'];		
	if($log_count) {
		$lbstable = 'zywx_useroperation_log';
	} else {
		$lbstable = 'zywx_useroperation';
	}
	$count = DB::num_rows(DB::query("SELECT distinct username FROM ".DB::table($lbstable)." WHERE 1 $where group by username"));
	$query = DB::query("SELECT *, count(distinct username)  FROM ".DB::table($lbstable)." 
						WHERE 1 $where group by username ORDER BY dateline DESC LIMIT ".$start.','.$perpage);
	$arr = array();
	while($row = DB::fetch($query)) {
		$row['dateline'] = date('Y-m-d', $row['dateline']);
		if(CHARSET == 'gbk') {
			$row['username'] = iconv('gbk', 'utf-8', $row['username']);
		}
		$data = DB::fetch_first("SELECT COUNT(uid) AS count FROM ".DB::table($lbstable)." WHERE uid='$row[uid]' $where");
		$row['login_num'] = $data['count'];
		$sql = "SELECT COUNT(p.pid) AS count FROM ".DB::table('forum_post')." AS p WHERE p.authorid=$row[uid] 
				AND p.pid IN( 
					SELECT pf.pid FROM ".DB::table('zywx_forum_postfield')." AS pf
				);";
		 $result = mysql_query($sql);
		 $data = mysql_fetch_array($result);
		$row['post_num'] = $data['count'];
		$arr[] = $row;
	}

	//合计登录帐号
	$total_loginname_num = DB::num_rows(DB::query("SELECT uid AS count FROM ".DB::table($lbstable)." WHERE 1 $where GROUP BY uid"));
	//合计登录次数
	$data = DB::fetch_first("SELECT COUNT(uid) AS count FROM ".DB::table($lbstable)." WHERE  1 $where");
	$total_login_num = $data['count'];
	//合计发帖数
	$data = DB::fetch_first("SELECT COUNT(pid) AS count FROM ".DB::table('zywx_forum_postfield')." WHERE $postwhere");
	$total_post_num = $data['count'];
	
	if($_G['gp_export']) {
		
		ob_start();
		
		header("Content-type:application/vnd.ms-excel");
		header("Content-Disposition:filename=appcan_stat_page_$page.xls");
		
		echo "<table>";
		echo "	<tr>
					<td>最后登录时间</td>
				  	<td>登录帐号</td>
				 	<td>登录次数</td>
				  	<td>发帖数</td>
			 	</tr>"; 
		foreach($arr as $row) { 

			echo "	<tr>
					<td>".$row['dateline']."</td>
				  	<td>".$row['username']."</td>
				 	<td>".$row['login_num']."</td>
				  	<td>".$row['post_num']."</td>
			 	</tr>";  	 
		}
		
		echo "	<tr>
					<td>合计</td>
					<td>$total_loginname_num</td>
					<td>$total_login_num</td>
					<td>$total_post_num</td>
          		</tr>";
		echo "</table>";
		
		$body = ob_get_contents();
		ob_clean();
		$body = utf8togbk($body);
		echo $body;
		exit;
	}
	$paramstr = "&username=".$_G['gp_username']."&start_time=".$_G['gp_start_time']."&end_time=".$_G['gp_end_time'];
	$multipage = multi($count, $perpage, $page, $url."&op=stat".$paramstr, 1000);
	ob_start();
	include template('zywx:stat');
	$body = ob_get_contents();
	ob_end_clean();
	$body = utf8togbk($body);
	echo $body;
}

/**
* 宣传推广页面
*/
elseif($_G['gp_op'] == 'publicity') {

	ob_start();
	include template('zywx:publicity');
	$body = ob_get_contents();
	ob_end_clean();
	$body = utf8togbk($body);
	echo $body;
}

/**
 * 手拉手页面
 * 
 * @copyright  2011-2012 Bei Jing Zheng Yi Wireless
 * @since      File available since Release 1.0 -- 2012-3-16 下午02:55:50
 * @author author
 * 
 */
elseif($_G['gp_op'] == 'hand'){
	$getChannelPkgurl = ZYWX_PROXY."/index.php?m=curl&a=getChannelPkg&authcode=".trim($_G['setting']['zywxid']);
	$getgetGuideDocurl= ZYWX_PROXY."/index.php?m=curl&a=getGuideDoc&authcode=".trim($_G['setting']['zywxid']);
	ob_start();
	include template('zywx:index_hand');
	$body = ob_get_contents();
	ob_end_clean();
	$body = utf8togbk($body);		
	echo $body;
} 

/**
 * 邀请管理页面
 * 
 * @copyright  2011-2012 Bei Jing Zheng Yi Wireless
 * @since      File available since Release 1.0 -- 2012-3-16 下午02:55:50
 * @author author
 * 
 */
elseif($_G['gp_op'] == 'invite') {
	$channel_url = zy_loadcache('zywx_channel_invite_url', false);
	if($channel_url) {
		$inviteurl = $channel_url;
	} else {
		$inviteurl = get_url_contents(ZYWX_APPCAN."/plugin/invitedUrl.action?app_key=".$appkey);
	}
	include template('zywx:invited');
}

/**
* 应用升级设置
*/
elseif($_G['gp_op'] == 'setappupgrade') {

	ob_start();
	include template('zywx:setappupgrade');
	$body = ob_get_contents();
	ob_clean();
	$body = utf8togbk($body);		
	echo $body;
}

/**
 * 根据键名加载缓存
 * @param   string  $name    键名
 * @param   mixed   $life    是否受过期限制
 */
function zy_loadcache($name, $limit=true) {
	$cache = DB::fetch(DB::query('SELECT data, dateline FROM '.DB::table("common_syscache")." WHERE cname='$name' LIMIT 1"));
	if(empty($cache) || ($limit && TIMESTAMP > $cache['dateline'])) return;
	return unserialize($cache['data']);
}

/**
 * 缓存保存到数据库
 * @param   string  $name    键名
 * @param   mixed   $data    缓存内容
 * @param   int     $life    缓存存活时间，默认为一周
 */
function zy_savecache($name, $data, $life=604800) {
	DB::insert('common_syscache', array(
		'cname' => $name,
		'data' => serialize($data),
		'dateline' => (TIMESTAMP+$life)
	), false, true);
}

/**
 * 获取门户下拉列表
 * @param   string  $hidelist    列表选中项的id数组
 */
function portalselect($hidelist='') {	
	$select = '';
	
	$query = DB::query("SELECT catid, catname, articles FROM ".DB::table('portal_category')." 
						WHERE closed='0' AND upid='0'");

	while($cat = DB::fetch($query)) {
	
		$select .= '<option value="'.$cat['catid'].'" class="bold" '.(in_array($cat['catid'], $hidelist) ? ' selected' : '').'>'.$cat['catname'].'</option>';
		
		//二级栏目
		$branchquery = DB::query("SELECT catid, catname, articles FROM ".DB::table('portal_category')." 
							WHERE closed='0' AND upid='$cat[catid]'");
		while($branchcat = DB::fetch($branchquery)) {	
			
			$select .= '<option value="'.$branchcat['catid'].'" '.(in_array($branchcat['catid'], $hidelist) ? ' selected' : '').'>&nbsp;&nbsp;'.$branchcat['catname'].'</option>';

			//三级栏目			
			$leafquery = DB::query("SELECT catid, catname, articles FROM ".DB::table('portal_category')." 
								WHERE closed='0' AND upid='$branchcat[catid]'");
			while($leaf = DB::fetch($leafquery)) {	
				$select .= '<option value="'.$leaf['catid'].'" '.(in_array($leaf['catid'], $hidelist) ? ' selected' : '').'>&nbsp; &nbsp; &nbsp; '.$leaf['catname'].'</option>';
			}
		}
				
	}

	return '<select name="hideportal[]" size="10" multiple="multiple">
		   		<option value="">'.cplang('plugins_empty').'</option>'.
				$select.
		   '</select>';
}

function utf8togbk($data, $force = 0) {
	if(is_array($data)) {
		$keys = array_keys($data);
		foreach($keys as $key) {
			$val = $data[$key];
			unset($data[$key]);
			$data[$key] = utf8togbk($val, $force);
		}
	} else {
		if(CHARSET != 'utf-8' ||  $force) {
			$data = iconv('utf-8', CHARSET."//IGNORE", $data);
		}
	}
	return $data;
}

function gbktoutf8($data, $force = 0) {
	if(is_array($data)) {
		$keys = array_keys($data);
		foreach($keys as $key) {
			$val = $data[$key];
			unset($data[$key]);
			$data[$key] = gbktoutf8($val, $force);
		}
	} else {
		if(CHARSET != 'gbk' ||  $force) {
			$data = iconv(CHARSET, "utf-8//IGNORE", $data);
		}
	}
	return $data;
}

function dreadfile($filename) {
	$content = '';
	if(function_exists('file_get_contents')) {
		@$content = file_get_contents($filename);
	} else {
		if(@$fp = fopen($filename, 'r')) {
			@$content = fread($fp, filesize($filename));
			@fclose($fp);
		}
	}
	return $content;
}

?>