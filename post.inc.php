<?php
/*
	提交数据处理
*/

/**
* 邮箱
*/
if(submitcheck('emailsubmit')) {

	$_G['gp_email'] = trim($_G['gp_email']);
	
	if(CHARSET != 'utf-8') {
		$_G['gp_email'] = iconv(CHARSET, "utf-8//IGNORE", $_G['gp_email']);
		$_G['gp_username'] = iconv(CHARSET, "utf-8//IGNORE", $_G['gp_username']);
	}
	
	//判断邮箱格式是否正确
	if(!isemail($_G['gp_email'])) {
		cpmsg('zywx:emailformat_error', $url, 'error');	
	}
	
	if(!$_G['gp_username'] || !$_G['gp_password']) {
		//信息不完整
		cpmsg('&#21442;&#25968;&#19981;&#23436;&#25972;', $url, 'error');	
	}
	
	if(strlen($_G['gp_password']) < 6) {
		//密码不能小于6位
		cpmsg('&#23494;&#30721;&#19981;&#33021;&#23567;&#20110;&#54;&#20301;', $url, 'error');	
	}
	
	if($_G['gp_password'] != $_G['gp_repassword']) {
		//两次密码输入不一致
		cpmsg('&#20004;&#27425;&#23494;&#30721;&#36755;&#20837;&#19981;&#19968;&#33268;', $url, 'error');	
	}
	
	$appcan_nickname = urlencode($_G['gp_username']);
	$appcan_password = urlencode($_G['gp_password']);

	$request_url = ZYWX_PROXY."/index.php?m=curl&domain=".$_G['siteurl'].
								  "&authcode=".trim($_G['setting']['zywxid']).
								  "&pluginversion=".$plugin['version'].
								  "&email=".$_G['gp_email']."&nickname=".$appcan_nickname."&password=".$appcan_password;

	//请求服务器端验证邮箱是否可用
	$result = trim(get_url_contents($request_url));
	$result = json_decode($result);
						  
	if($result->msg) {
		$message =  $result->msg;
		$message = utf8togbk($message);
		
		//未知错误
		if(empty($message)) {
			$message = '&#26410;&#30693;&#38169;&#35823;';
		}
		
		//输出错误消息
		cpmsg('zywx:emali_back_error', $url, 'error', '', $message);	
	}
	
	if($_G['gp_email']) {
		//邮箱插入到setting表中
		$id = DB::insert('common_setting', array('skey' => 'zywxemail', 'svalue' => $_G['gp_email']), 1, 1);
	}
	
	//如果保存成功
	if($id) {
		//更新setting缓存
		updatecache('setting');
		
		//跳转到下一页
		//dheader("location: $url&op=selectstyle");
		cpmsg('&#27880;&#20876;&#25104;&#21151;', $url.'&op=selectstyle', 'succeed');
	} else {
		//显示错误提示
		cpmsg('zywx:submit_error', $url, 'error');
	}
}

/**
* 风格设置
*/
elseif(submitcheck('stylesubmit') || $_G['gp_stt']=='stt') {

	if($_G['gp_colorradio'] == 1 && $_G['gp_colorrgb']) {
		$colorrgb = str_replace('#', '', $_G['gp_colorrgb']);
	} else {
		$colorrgb = '1081A6';
	}

	//样式保存到session	
	$_SESSION['style'] = $colorrgb;

	//保存设置到数据库
	zy_savecache('zywxdata', $_SESSION);
	
	$data = trim(get_url_contents(ZYWX_PROXY."/index.php?m=curl&a=create&plugin_name=discuz&version=$version&app_style=".$colorrgb."&authcode=".trim($_G['setting']['zywxid'])));
	$data = json_decode($data);
	
	if(empty($data)) {
		cpmsg('zywx:appkey_error', $url, 'error');
	}
	
	DB::insert('common_setting', array('skey' => 'zywxversion', 'svalue' => $version) , 1, 1);
	updatecache('setting');

	dheader("location: $url&op=setbuild");
}

/**
* 修改密码
*/
elseif(submitcheck('submitpwd')){
	
	$repwd = $_G['gp_repwd'];
	if(!empty($repwd)){
		$changepwd = json_decode(trim(get_url_contents(ZYWX_PROXY."/index.php?m=curl&a=updatePwd&newpass=".$repwd."&authcode=".trim($_G['setting']['zywxid'])."&email=".$_G['setting']['zywx_email'])));
		
	}
	
}

/**
* 内容设置
*/
elseif(submitcheck('contentsubmit')) {
	
	require_once libfile('class/upload');
	
	$_SESSION['hideportal'] = $_G['gp_hideportal'];
	$_SESSION['hideforum'] = $_G['gp_hideforum'];
	
	$_SESSION['weibo_sina'] = $_G['gp_weibo_sina'];
	$_SESSION['weibo_tencent'] = $_G['gp_weibo_tencent'];
	$_SESSION['weibo_sina_callback'] = $_G['gp_weibo_sina_callback'];
	$_SESSION['weibo_tencent_callback'] = $_G['gp_weibo_tencent_callback'];
	
	$_SESSION['index_pic_status'] = $_G['gp_index_pic_status'];
	$_SESSION['hot_post_pic_status'] = $_G['gp_hot_post_pic_status'];
	$_SESSION['portalstatus'] = $_G['gp_portalstatus'];
	
	$_SESSION['newest_post'] = $_G['gp_newest_post'];
	$_SESSION['host_post'] = $_G['gp_host_post'];
	$_SESSION['article'] = $_G['gp_article'];
	
	/* 首页图片栏*/
	if($_FILES['index_pic1']) {
		$upload = new discuz_upload();
		if($upload->init($_FILES['index_pic1'], 'forum') && $upload->attach['isimage'] && $upload->save()) {
			$_SESSION['index_pic1'] = $_G['siteurl'].$_G['setting']['attachurl'].'forum/'.$upload->attach['attachment'];
		}
	}
	if($_FILES['index_pic2']) {
		$upload = new discuz_upload();
		if($upload->init($_FILES['index_pic2'], 'forum') && $upload->attach['isimage'] && $upload->save()) {
			$_SESSION['index_pic2'] = $_G['siteurl'].$_G['setting']['attachurl'].'forum/'.$upload->attach['attachment'];
		}
	}
	if($_FILES['index_pic3']) {
		$upload = new discuz_upload();
		if($upload->init($_FILES['index_pic3'], 'forum') && $upload->attach['isimage'] && $upload->save()) {
			$_SESSION['index_pic3'] = $_G['siteurl'].$_G['setting']['attachurl'].'forum/'.$upload->attach['attachment'];
		}
	}
	
	$_SESSION['index_pic1_subject'] = $_REQUEST['index_pic1_subject'];
	$_SESSION['index_pic2_subject'] = $_REQUEST['index_pic2_subject'];
	$_SESSION['index_pic3_subject'] = $_REQUEST['index_pic3_subject'];
	$_SESSION['index_pic1_url'] = $_REQUEST['index_pic1_url'];
	$_SESSION['index_pic2_url'] = $_REQUEST['index_pic2_url'];
	$_SESSION['index_pic3_url'] = $_REQUEST['index_pic3_url'];
	
	/* 热帖内广告栏*/
	if($_FILES['hot_post_pic1']) {
		$upload = new discuz_upload();
		if($upload->init($_FILES['hot_post_pic1'], 'forum') && $upload->attach['isimage'] && $upload->save()) {
			$_SESSION['hot_post_pic1'] = $_G['siteurl'].$_G['setting']['attachurl'].'forum/'.$upload->attach['attachment'];
		}
	}
	if($_FILES['hot_post_pic2']) {
		$upload = new discuz_upload();
		if($upload->init($_FILES['hot_post_pic2'], 'forum') && $upload->attach['isimage'] && $upload->save()) {
			$_SESSION['hot_post_pic2'] = $_G['siteurl'].$_G['setting']['attachurl'].'forum/'.$upload->attach['attachment'];
		}
	}
	if($_FILES['hot_post_pic3']) {
		$upload = new discuz_upload();
		if($upload->init($_FILES['hot_post_pic3'], 'forum') && $upload->attach['isimage'] && $upload->save()) {
			$_SESSION['hot_post_pic3'] = $_G['siteurl'].$_G['setting']['attachurl'].'forum/'.$upload->attach['attachment'];
		}
	}
	$_SESSION['hot_post_pic1_url'] = $_REQUEST['hot_post_pic1_url'];
	$_SESSION['hot_post_pic2_url'] = $_REQUEST['hot_post_pic2_url'];
	$_SESSION['hot_post_pic3_url'] = $_REQUEST['hot_post_pic3_url'];
	
	//保存设置到数据库
	zy_savecache('zywxdata', $_SESSION);
	
	cpmsg('&#20869;&#23481;&#35774;&#32622;&#20445;&#23384;&#25104;&#21151;', "$url&op=setcontent", 'succeed');
	
}

/**
* 宣传推广
*/
elseif(submitcheck('publicitysubmit')) {
	
	$_SESSION['ispopwin'] = intval($_G['gp_ispopwin']);
	if($_SESSION['ispopwin']) setcookie('zyprompt', '0'); //更新弹窗时间时隔	
	$_SESSION['linklocation'] = intval($_G['gp_linklocation']);
	$_SESSION['popwintime'] = intval($_G['gp_popwintime']);

	//平台记录推广状态
	if($_G['gp_pullscreen'] || $_G['gp_linklocation'] || $_G['gp_ispopwin']) {
		$result = get_url_contents(ZYWX_APPCAN.'/plugin/promotionStatus.action?app_key='.$_G['setting']['zywxappkey'].'&status=1');
	} else {
		$result = get_url_contents(ZYWX_APPCAN.'/plugin/promotionStatus.action?app_key='.$_G['setting']['zywxappkey'].'&status=0');
	}
	$result = json_decode($result);	
	if($result->status == 'fail') {
		cpmsg(utf8togbk($result->errMsg), '', 'error');
	}

	//头部广告开启与关闭
	$_SESSION['pullscreen'] = intval($_G['gp_pullscreen']);
	
	$_SESSION['pullscreenid'] = DB::fetch_first("SELECT advid FROM ".DB::table('common_advertisement')." 
							WHERE title='".$scriptlang['zywx']['widget_adv']."' order by advid desc");
	$_SESSION['pullscreenid'] = $_SESSION['pullscreenid']['advid'];		
	//如果开启广告，则插入广告表
	if($_SESSION['pullscreen'] && empty($_SESSION['pullscreenid'])) {
		
		$param = array();
		$param['style'] = 'image';
		$param['link'] = $_G['siteurl'].'plugin.php?id=zywx:propagate';
		$param['url'] = $_G['siteurl'].'source/plugin/zywx/propagate/images/indextop.jpg';
		$param['html'] = '<a href="'.$param['link'].'" target="_blank"><img src="'.$param['url'].'" border="0"></a>';
		$param['alt'] = '';
		$param['width'] = '';
		$param['height'] = '';
		
		$_SESSION['pullscreenid'] = DB::insert('common_advertisement', array(
			'available' => '1', 
			'type' => 'headerbanner',
			'title' => $scriptlang['zywx']['widget_adv'],
			'targets' => 'portal	forum',
			'parameters' => serialize($param),
			'code' => $param['html'],
			), true);
	} else if($_SESSION['pullscreenid']) { //设置广告是否可用
		$available = $_SESSION['pullscreen'] ? 1 : 0;
		DB::update('common_advertisement', array('available' => $available), array('advid' => $_SESSION['pullscreenid']));	
	}

	zy_savecache('zywxdata', $_SESSION);
	updatecache('advs');
	updatecache('setting');
	
	cpmsg('&#25512;&#24191;&#35774;&#32622;&#20445;&#23384;&#25104;&#21151;', "$url&op=publicity", 'succeed');
}

/**
* 邀请管理
*/
elseif(submitcheck('invitesubmit')){
	
	if( !preg_match('/http:\/\//', $_G['gp_domainName'])) {
		$_G['gp_domainName'] = 'http://'.$_G['gp_domainName'];
	}
	
	//格式不正确
	if( !preg_match('/http:\/\/[\w\-]+\.(\w)+/', $_G['gp_domainName'])) {
		cpmsg('&#25265;&#27465;&#65292;&#85;&#82;&#76;&#26684;&#24335;&#19981;&#27491;&#30830;&#65292;&#35831;&#37325;&#20889;&#22635;&#20889;','','error');
	}

	$url = ZYWX_APPCAN."/plugin/inviteReport.action?".
						"domainName=".trim($_G['gp_domainName']).
						"&pluginName=discuz".
						"&app_key=".$_G['setting']['zywxappkey'];
	$data = json_decode(get_url_contents($url));

	if($data->status == 'error') {
		if(CHARSET != 'utf-8') {
			$data->errMsg = diconv($data->errMsg, 'utf-8', CHARSET);
		}
		
		if($data->url) {
			$_SESSION['inviteurl'] = $data->url;
			zy_savecache('zywxdata', $_SESSION);
		}
		
		cpmsg($data->errMsg, '' ,'error');
	} elseif($data->status == 'ok') {
		
		$_SESSION['inviteurl'] = $_G['gp_domainName'];
		zy_savecache('zywxdata', $_SESSION);
	
		cpmsg('&#36992;&#35831;&#25104;&#21151;', '', 'succeed');
	} else {
		cpmsg('&#25265;&#27465;&#65292;&#26381;&#21153;&#22120;&#27809;&#26377;&#21709;&#24212;&#65292;&#35831;&#37325;&#35797;', '' ,'error');
	}
}

/**
* 宣传推广
*/
elseif(submitcheck('appupgradesubmit')) {
	
	$_SESSION['iphone_src'] = $_G['gp_iphone_src'];
	$_SESSION['android_src'] = $_G['gp_android_src'];
	$_SESSION['app_ver'] = $_G['gp_app_ver'];
	zy_savecache('zywxdata', $_SESSION);
	
	cpmsg('&#24212;&#29992;&#21319;&#32423;&#35774;&#32622;&#20445;&#23384;&#25104;&#21151;', "$url&op=setappupgrade", 'succeed');
}



?>