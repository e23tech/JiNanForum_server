<?php
/**
 * 个人空间控制器
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$_POST = &$_GET;

//隐私信息
if($_G['gp_ac'] == 'privacy'){
    $_POST['privacy']['view'] = $_GET;
}

/*DZ源代码 begin*/
require_once libfile('function/spacecp');
require_once libfile('function/magic');

$acs = array('space', 'doing', 'upload', 'comment', 'blog', 'album', 'relatekw', 'common', 'class',
	'swfupload', 'poke', 'friend', 'eccredit', 'favorite',
	'avatar', 'profile', 'theme', 'feed', 'privacy', 'pm', 'share', 'invite','sendmail',
	'credit', 'usergroup', 'domain', 'click','magic', 'top', 'videophoto', 'index', 'plugin', 'search', 'promotion');

$ac = (empty($_GET['ac']) || !in_array($_GET['ac'], $acs))?'profile':$_GET['ac'];
$op = empty($_GET['op'])?'':$_GET['op'];

$_G['mnid'] = 'mn_common';

if(in_array($ac, array('privacy'))) {
	if(!$_G['setting']['homestatus']) {
		$msg = lang('message', 'home_status_off');
		
		jsonexit("{\"status\":\"0\",\"message\":\"$msg\",url:\"../login.html\"}");
	}
}

if(empty($_G['uid'])) {
	if($_SERVER['REQUEST_METHOD'] == 'GET') {
		dsetcookie('_refer', rawurlencode($_SERVER['REQUEST_URI']));
	} else {
		dsetcookie('_refer', rawurlencode('home.php?mod=spacecp&ac='.$ac));
	}
	
	$msg = lang('message', 'to_login');
	
	jsonexit("{\"status\":\"0\",\"message\":\"$msg\",\"url\":\"../login.html\"}");
}

$space = getspace($_G['uid']);
if(empty($space)) {
	$msg = lang('message', 'space_does_not_exist');
	
	jsonexit("{\"status\":\"0\",\"message\":\"$msg\"}");
}
space_merge($space, 'field_home');

if(($space['status'] == -1 || in_array($space['groupid'], array(4, 5, 6))) && $ac != 'usergroup') {
	$msg = lang('message', 'space_has_been_locked');
	
	jsonexit("{\"status\":\"0\",\"message\":\"$msg\"}");
}
$actives = array($ac => ' class="a"');

$seccodecheck = $_G['group']['seccode'] ? $_G['setting']['seccodestatus'] & 4 : 0;
$secqaacheck = $_G['group']['seccode'] ? $_G['setting']['secqaa']['status'] & 2 : 0;

$navtitle = lang('core', 'title_setup');
if(lang('core', 'title_memcp_'.$ac)) {
	$navtitle = lang('core', 'title_memcp_'.$ac);
}

/*插入数据前检查编码   start*/
$_POST['formhash'] = FORMHASH;
if($_G['gp_ac'] == 'doing' && $_GET['addsubmit']) {//发布记录
	if(!checkperm('allowdoing')) {
		$msg = rpclang('home', 'no_privilege_doing');
		
		jsonexit("{\"status\":\"0\",\"message\":\"$msg\"}");
	}
	
	$waittime = interval_check('post');
	if($waittime > 0) {
		$msg = lang('message', 'operating_too_fast', array('waittime' => $waittime));
		
		jsonexit("{\"status\":\"0\",\"message\":\"$msg\"}");
	}
	
//	$message = cutstr($_POST['message'], 200);
//	$message = preg_replace("/\<br.*?\>/i", ' ', $message);

	if(strlen($_POST['message']) < 1) {
		$msg = lang('message', 'should_write_that');
		
		jsonexit("{\"status\":\"0\",\"message\":\"$msg\"}");
	}
	
	if(CHARSET != 'utf-8') {
		$_POST['message'] = utf8togbk($_GET['message']);
	}
	
}else if($_G['gp_ac'] == 'pm' && $_GET['pmsubmit']) {//发送信息
	
//	$_POST['message'] = getstr($_GET['message'], 200, 1, 1, 2);
	
	if(CHARSET != 'utf-8') {
		$_POST['message'] = utf8togbk($_POST['message']);
	}
	
}else if($_G['gp_ac'] == 'comment' && $_GET['commentsubmit']) {//发送留言
	
	//$_POST['message'] = getstr($_POST['message'], 200, 1, 1, 2);
	//$_POST['message'] = preg_replace("/\<br.*?\>/i", ' ', $_POST['message']);
	
	if(CHARSET != 'utf-8') {
		$_POST['message'] = utf8togbk($_POST['message']);
	}
}
/*插入数据前检查编码   end*/

/*DZ源代码 end*/

$rpcAcs = array('profile','comment','share','favorite','search','blog','upload');

if (('friend' == $ac && 'add' == $op) || in_array($ac,$rpcAcs)){
    //require RPC_DIR . '/include/spacecp/spacecp_'.$ac.'.php';
	require_once libfile('spacecp/'.$ac, 'include');
}else{
    require_once libfile('spacecp/'.$ac, 'include');
    ob_end_clean();
}

$jsonarr['uid'] = $_G['uid'];

if ('doing' == $_G['gp_ac']) {// 发布记录
	
	if ($_GET['addsubmit']) {
		$jsonarr['newdoid']= $newdoid;
	}
	
	jsonexit($jsonarr);

} else if ('friend' == $_G['gp_ac']) {//好友
	
	if($op == 'request') {//好友请求
		$maxfriendnum = checkperm('maxfriendnum');
		if($maxfriendnum) {
			$maxfriendnum = $maxfriendnum + $space['addfriend'];
		}
	
		$perpage = 20;
		$perpage = mob_perpage($perpage);
	
		$page = empty($_GET['page'])?0:intval($_GET['page']);
		if($page<1) $page = 1;
		$start = ($page-1)*$perpage;
	
		$widgetPage = "friend_request.html";
		$theurl = str_replace('home.php', $widgetPage, $theurl);
		
		$list = array();
		$count = getcount('home_friend_request', array('uid'=>$space['uid']));
		if($count) {
			$fuids = array();
			$query = DB::query("SELECT uid, fuid, fusername, dateline,note FROM ".DB::table('home_friend_request')." WHERE uid='$space[uid]' ORDER BY dateline DESC LIMIT $start, $perpage");
			while ($value = DB::fetch($query)) {
				$fuids[$value['fuid']] = $value['fuid'];
				$value['photo'] = avatar($value['fuid'],'small');
				$value['dateline'] = dgmdate($value['dateline'],'dt');
				$list[$value['fuid']] = $value;
			}
		} else {
			dsetcookie('promptstate_'.$space['uid'], $newprompt, 31536000);
		}
		$multi = multi($count, $perpage, $page, $theurl);
		
        $jsonarr['list'] = $list;
	    $jsonarr['page'] = $multi;
	    
	    /*MB add start*/
 		$jsonarr['zywy_curpage'] = $_G["zywy_curpage"];
		$jsonarr['zywy_totalpage'] = $_G["zywy_totalpage"];
 		/*MB add end*/
	
	    jsonexit($jsonarr);
	    
	}else if('ignore' == $op){//解除好友
        if(submitcheck('friendsubmit')) {
            $msg = rpclang('home', 'ignore_do_success');
		    jsonexit("{\"status\":\"$msg\"}");
        }
    }else{//添加，批准好友
        $jsonarr['op'] = $op;
        $jsonarr['tospace'] = $tospace;
        $jsonarr['groups'] = $groups;
        if($space['privacy']['groupname']){
           $jsonarr['groupname'] = $space['privacy']['groupname'];
        }
        if('add2' == $op){
            $jsonarr['groupselect'] = $groupselect;
        }
        jsonexit($jsonarr);
    }
	
} else if ('search' == $_G['gp_ac']) {//好友搜索
	if (!empty($_GET['searchsubmit']) || !empty($_GET['searchmode'])) {
		
		$perpage = 10;
		$perpage = mob_perpage($perpage);
		$page = intval($_GET['page']);
		if($page < 1) $page = 1;
		$start = ($page-1)*$perpage;
		
		ckstart($start, $perpage);
		if(! empty($_GET['username']) && empty($_GET['precision'])) {
			$_GET['username'] = str_replace('%', '*',$_GET['username']);
			$_GET['username'] = str_replace('\_', '_', $_GET['username']);
		}
		$gets = array(
			'mod' => 'spacecp',
			'ac' => 'search',
			'username'=> $_GET['username'],
			'uid'=> $_GET['uid'],
			'gender'=> $_GET['gender'],
			'startage'=> $_GET['startage'],
			'endage'=> $_GET['endage'],
			'resideprovince'=> $_GET['resideprovince'],
			'birthprovince'=> $_GET['birthprovince'],
			'birthyear'=> $_GET['birthyear'],
			'birthmonth'=> $_GET['birthmonth'],
			'birthday'=> $_GET['birthday'],
			'field_realname'=> $_GET['field_realname'],
			'searchsubmit'=>true,
			'type'=>'all'
		);
		
		$theurl = 'home.php?'.url_implode($gets);
		
		$widgetPage = "friend_search_list.html";
		
		$theurl = str_replace('home.php', $widgetPage, $theurl);
		
		$count = 0;
		
		$list = array();
		require_once libfile('function/post');
		if($wherearr) {
			$count =  DB::result(DB::query("SELECT COUNT(*) FROM ".implode(',', $fromarr)." WHERE ".implode(' AND ', $wherearr)." "),0);
			$space['friends'] = array();
			$query = DB::query("SELECT fuid, fusername FROM ".DB::table('home_friend')." WHERE uid='$_G[uid]'");
			while ($value = DB::fetch($query)) {
				$space['friends'][$value['fuid']] = $value['fuid'];
			}
	
			$query = DB::query("SELECT s.* $fsql FROM ".implode(',', $fromarr)." WHERE ".implode(' AND ', $wherearr)." LIMIT $start,$perpage");
			while ($value = DB::fetch($query)) {
				$value['isfriend'] = ($value['uid']==$space['uid'] || $space['friends'][$value['uid']])?1:0;
				$value['doing'] = DB::result_first("SELECT message FROM ".DB::table('home_doing')." WHERE uid='$value[uid]' ORDER BY dateline DESC LIMIT 0,1");
//				$value['doing'] = preg_replace("~src=\"(\w+)~i", 'src="'.getsiteurl().'\\1', $value['doing']);
				$value['doing'] = messagecutstr($value['doing'],30);
				$value['photo'] = avatar($value['uid'],'small');
				
				$list[$value['uid']] = $value;
			}
		}
		$multi = multi($count, $perpage, $page, $theurl);
		
		$jsonarr['list'] = $list;
		$jsonarr['page'] = $multi;
		
		/*MB add start*/
 		$jsonarr['zywy_curpage'] = $_G["zywy_curpage"];
		$jsonarr['zywy_totalpage'] = $_G["zywy_totalpage"];
 		/*MB add end*/
	
		jsonexit($jsonarr);
	} 
	
}elseif($_G['gp_ac'] == 'pm') {//发送消息
	if('delete' == $_GET['op']){
		if($flag) {
			$msg = lang('message', 'delete_pm_success');
			
			jsonexit("{\"status\":\"1\",\"message\":\"$msg\"}");
		} else {
			$msg = lang('message', 'this_message_could_note_be_option');
			
			jsonexit("{\"status\":\"0\",\"message\":\"$msg\"}");
		}
	}else if('showmsg' == $_GET['op']){
        $jsonarr['msgonly'] = $msgonly;
        $jsonarr['msguser'] = $msguser;
        $jsonarr['online'] = $online;
        $jsonarr['touid'] = $touid;
        $jsonarr['messageappend'] = $messageappend;
        foreach ($msglist as $one => $oneVal){
            foreach ($msglist[$one] as $two => $twoVal){
                $msglist[$one][$two]['dateline'] = strip_tags(dgmdate($twoVal['dateline'],'u'));
//				$message = str_replace('\"','',getstr($twoVal['message'], 200, 1, 1, 2));
//				$msglist[$one][$two]['message'] = preg_replace("~src=\"(?!http:)(\w+)~i", 'src="'.getsiteurl().'\\1', $message);
            }
        }
        $jsonarr['msglist'] = $msglist;
        jsonexit($jsonarr);
        exit();

    }elseif ('send' == $_GET['op']){
          
    	if(!checkperm('allowsendpm')) {
		    $msg = rpclang('home', 'no_privilege_sendpm');
		    jsonexit("{\"status\":\"0\",\"message\":\"$msg\"}");
		    exit();
	    }
        if($touid) {
		    if(isblacklist($touid)) {
			    $msg = rpclang('home', 'is_blacklist');
		        jsonexit("{\"status\":\"0\",\"message\":\"$msg\"}");
		        exit();
		    }
	    }
        if(empty($message)) {
			$msg = rpclang('home', 'unable_to_send_air_news');
		    jsonexit("{\"status\":\"0\",\"message\":\"$msg\"}");
		    exit();
		}
        if($return > 0) {
            $unme = $_G[username];
            $message = str_replace('\"','',getstr($message, 200, 1, 1, 2));
			$message = preg_replace("~src=\"(?!http:)(\w+)~i", 'src="'.getsiteurl().'\\1', $message);
			jsonexit("{\"status\":\"1\"}");
        } else {
			if(in_array($return, range(-15, -1))) {
				$msg = rpclang('home', 'message_can_not_send_'.abs($return));
		        jsonexit("{\"status\":\"0\",\"message\":\"$msg\"}");
		        exit();
			} else {
				$msg = rpclang('home', 'message_can_not_send');
		        jsonexit("{\"status\":\"0\",\"message\":\"$msg\"}");
		        exit();
			}
		}
    }
	
}elseif($_G['gp_ac'] == 'profile'){//修改个人资料
    
    if (isset($message)){
        $message = !empty($message) ? $message : '1';
        jsonexit("{\"status\":\"$message\"}");
        exit();
    }else{
        if ($space){
            jsonexit($space);
            exit();   
        }
    }
}elseif($_G['gp_ac'] == 'privacy'){//隐私设置

    if(submitcheck('privacysubmit')){
    	$address = $_G['gp_address'];
    	if($address == '0' || $address == '1'){
			if(DB::getOne("SELECT count(uid) FROM ".DB::table('zywx_useroperation'))) {
				DB::query("UPDATE ".DB::table('zywx_useroperation')." SET allow_state = $address WHERE uid = ".$_G['uid']);
			} else {
				DB::insert('zywx_useroperation', array(
					'uid' => $_G['uid'],
					'username' => $_G['username'],
					'allow_state' => $address,
					'dateline' => TIMESTAMP
				));
			}    		
    	}
    	$msg = rpclang('home', 'privacy_do_success');
		jsonexit("{\"status\":\"$msg\"}");
        exit();
    }else{
        if ($space){
        	$space['privacy']['view']['allow_state'] = DB::getOne("SELECT allow_state FROM ".DB::table('zywx_useroperation')." WHERE uid = ".$_G['uid']." 
				ORDER BY dateline DESC LIMIT 1");
        	jsonexit($space);
            exit();
        }
    }
}elseif ($_G['gp_ac'] == 'avatar'){//显示头像

    if($uc_avatarflash){
        $arr['avatar'] =  avatar($space[uid],big);
        $arr['uc'] =  strstr($uc_avatarflash[7],'inajax');
        jsonexit($arr);
        exit();
    }
}