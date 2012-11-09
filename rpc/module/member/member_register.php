<?php

/**
 * 会员注册
 *
 * file_description
 *
 * LICENSE Aushi Copyright
 *
 * @copyright  2011-2012 Bei Jing Zheng Yi Wireless
 * @version    $Id: \$
 * @since      File available since Release 1.0 -- 2011-8-16 下午02:59:39
 * @author     LLX
 */

//普通注册功能是否关闭
if($_G['setting']['regstatus'] != 1 && $_G['setting']['regstatus'] != 3) {
	$msg = rpclang('member', 'register_closed');
	jsonexit("{\"status\":\"$msg\"}");
}

//加载UCENTER文件
loaducenter();

//参数初始化
$_GET['regsubmit'] = 'yes';
if(CHARSET != 'utf-8') 
{
	if($_GET['username'] != '')  $_GET['username'] = utf8togbk($_GET['username']);
	if($_GET['password'] != '')  $_GET['password'] = utf8togbk($_GET['password']);
	if($_GET['password2'] != '') $_GET['password2'] = utf8togbk($_GET['password2']);
	if($_GET['email'] != '') 	 $_GET['email'] = utf8togbk($_GET['email']);
}

$_G['gp_'.$_G['setting']['reginput']['username']]  = $_GET['username'];
$_G['gp_'.$_G['setting']['reginput']['password']]  = $_GET['password'];
$_G['gp_'.$_G['setting']['reginput']['password2']] = $_GET['password2'];
$_G['gp_'.$_G['setting']['reginput']['email']] 	   = $_GET['email'];


$cookie_seccode = authcode($_G['cookie']['seccode'], 'DECODE');
if(empty($_G['gp_seccode']) || $cookie_seccode != $_G['gp_seccode']) {
	$msg = rpclang('member', 'seccode_error');
	jsonexit("{\"status\":\"$msg\"}");
}

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
//确认密码空值检查
if(empty($_GET['password2']))
{
	$msg = rpclang('member', 'password2_empty');
	jsonexit("{\"status\":\"$msg\"}");
}
//两次密码输入一致检查
if($_GET['password'] != $_GET['password2']) 
{
	$msg = rpclang('member', 'confirm_password_error');
	jsonexit("{\"status\":\"$msg\"}");
}
//邮箱空值检查
if(empty($_GET['email'])) 
{
	$msg = rpclang('member', 'email_empty');
	jsonexit("{\"status\":\"$msg\"}");
}elseif(!isemail($_GET['email'])) 
{ //邮箱格式检查
	$msg = rpclang('member', 'email_format_error');
	jsonexit("{\"status\":\"$msg\"}");
}

//检查用户是否已被注册
if(uc_get_user($_GET['username']) || DB::result_first("SELECT uid FROM ".DB::table('common_member')." WHERE username='$_GET[username]'")) 
{
	$msg = rpclang('member', 'username_duplicate');
	jsonexit("{\"status\":\"$msg\"}");
}

$_G['uid'] = 0;
$_G['username'] = '';
$_G['cookie']['authcode'] = '';


//jsonexit("{\"status\":\"".$_G['uid']."\"}");

if($_G['uid'])
{ //已经登录
	$msg = rpclang('member', 'has_logged');
	jsonexit("{\"status\":\"$msg\"}");
}else 
{
	require ROOT_DIR . '/source/module/member/member_'.$_G['mod'].'.php';
	ob_end_clean();

	if($_G['uid'] > 0) 
	{ //注册成功
		$msg = rpclang('member', 'register_success');
		jsonexit("{\"status\":\"$msg\",\"uid\":\"".$_G['uid']."\"}");
	} 
	
	elseif($_G['uid'] == '-1') { 
		$msg = rpclang('member', 'username_illegal');
		jsonexit("{\"status\":\"$msg\",\"uid\":\"".$_G['uid']."\"}");
	} 
	
	elseif($_G['uid'] == '-6') { 
		$msg = rpclang('member', 'email_duplicate');
		jsonexit("{\"status\":\"$msg\",\"uid\":\"".$_G['uid']."\"}");
	} 
	
	else {
		$msg = rpclang('member', 'register_fail');
		jsonexit("{\"status\":\"$msg\"}");
	}
	
}