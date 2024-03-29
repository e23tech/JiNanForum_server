<?php
/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: function_core.php 22982 2011-06-13 01:52:33Z zhangguosheng $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

define('DISCUZ_CORE_FUNCTION', true);

function system_error($message, $show = true, $save = true, $halt = true) {
	require_once libfile('class/error');
	discuz_error::system_error($message, $show, $save, $halt);
}

function updatesession($force = false) {

	global $_G;
	static $updated = false;

	if(!$updated) {
		if($_G['uid']) {
			if($_G['cookie']['ulastactivity']) {
				$ulastactivity = authcode($_G['cookie']['ulastactivity'], 'DECODE');
			} else {
				$ulastactivity = getuserprofile('lastactivity');
				dsetcookie('ulastactivity', authcode($ulastactivity, 'ENCODE'), 31536000);
			}
		}
		$discuz = & discuz_core::instance();
		$oltimespan = $_G['setting']['oltimespan'];
		$lastolupdate = $discuz->session->var['lastolupdate'];
		if($_G['uid'] && $oltimespan && TIMESTAMP - ($lastolupdate ? $lastolupdate : $ulastactivity) > $oltimespan * 60) {
			DB::query("UPDATE ".DB::table('common_onlinetime')."
				SET total=total+'$oltimespan', thismonth=thismonth+'$oltimespan', lastupdate='" . TIMESTAMP . "'
				WHERE uid='{$_G['uid']}'");
			if(!DB::affected_rows()) {
				DB::insert('common_onlinetime', array(
					'uid' => $_G['uid'],
					'thismonth' => $oltimespan,
					'total' => $oltimespan,
					'lastupdate' => TIMESTAMP,
				));
			}
			$discuz->session->set('lastolupdate', TIMESTAMP);
		}
		foreach($discuz->session->var as $k => $v) {
			if(isset($_G['member'][$k]) && $k != 'lastactivity') {
				$discuz->session->set($k, $_G['member'][$k]);
			}
		}

		foreach($_G['action'] as $k => $v) {
			$discuz->session->set($k, $v);
		}

		$discuz->session->update();

		$updated = true;

		if($_G['uid'] && TIMESTAMP - $ulastactivity > 21600) {
			if($oltimespan && TIMESTAMP - $ulastactivity > 43200) {
				$total = DB::result_first("SELECT total FROM ".DB::table('common_onlinetime')." WHERE uid='$_G[uid]'");
				DB::update('common_member_count', array('oltime' => round(intval($total) / 60)), "uid='$_G[uid]'", 1);
			}
			dsetcookie('ulastactivity', authcode(TIMESTAMP, 'ENCODE'), 31536000);
			DB::update('common_member_status', array('lastip' => $_G['clientip'], 'lastactivity' => TIMESTAMP, 'lastvisit' => TIMESTAMP), "uid='$_G[uid]'", 1);
		}
	}
	return $updated;
}

function dmicrotime() {
	return array_sum(explode(' ', microtime()));
}

function setglobal($key , $value, $group = null) {
	global $_G;
	$k = explode('/', $group === null ? $key : $group.'/'.$key);
	switch (count($k)) {
		case 1: $_G[$k[0]] = $value; break;
		case 2: $_G[$k[0]][$k[1]] = $value; break;
		case 3: $_G[$k[0]][$k[1]][$k[2]] = $value; break;
		case 4: $_G[$k[0]][$k[1]][$k[2]][$k[3]] = $value; break;
		case 5: $_G[$k[0]][$k[1]][$k[2]][$k[3]][$k[4]] =$value; break;
	}
	return true;
}

function getglobal($key, $group = null) {
	global $_G;
	$k = explode('/', $group === null ? $key : $group.'/'.$key);
	
	switch (count($k)) {
		case 1: return isset($_G[$k[0]]) ? $_G[$k[0]] : null; break;
		case 2: return isset($_G[$k[0]][$k[1]]) ? $_G[$k[0]][$k[1]] : null; break;
		case 3: return isset($_G[$k[0]][$k[1]][$k[2]]) ? $_G[$k[0]][$k[1]][$k[2]] : null; break;
		case 4: return isset($_G[$k[0]][$k[1]][$k[2]][$k[3]]) ? $_G[$k[0]][$k[1]][$k[2]][$k[3]] : null; break;
		case 5: return isset($_G[$k[0]][$k[1]][$k[2]][$k[3]][$k[4]]) ? $_G[$k[0]][$k[1]][$k[2]][$k[3]][$k[4]] : null; break;
	}
	return null;
}

function getgpc($k, $type='GP') {
	$type = strtoupper($type);
	switch($type) {
		case 'G': $var = &$_GET; break;
		case 'P': $var = &$_POST; break;
		case 'C': $var = &$_COOKIE; break;
		default:
			if(isset($_GET[$k])) {
				$var = &$_GET;
			} else {
				$var = &$_POST;
			}
			break;
	}

	return isset($var[$k]) ? $var[$k] : NULL;

}

function getuserbyuid($uid) {
	static $users = array();
	if(empty($users[$uid])) {
		$users[$uid] = DB::fetch_first("SELECT * FROM ".DB::table('common_member')." WHERE uid='$uid'");
	}
	return $users[$uid];
}

function getuserprofile($field) {
	global $_G;
	if(isset($_G['member'][$field])) {
		return $_G['member'][$field];
	}
	static $tablefields = array(
		'count'		=> array('extcredits1','extcredits2','extcredits3','extcredits4','extcredits5','extcredits6','extcredits7','extcredits8','friends','posts','threads','digestposts','doings','blogs','albums','sharings','attachsize','views','oltime','todayattachs','todayattachsize'),
		'status'	=> array('regip','lastip','lastvisit','lastactivity','lastpost','lastsendmail','invisible','buyercredit','sellercredit','favtimes','sharetimes','profileprogress'),
		'field_forum'	=> array('publishfeed','customshow','customstatus','medals','sightml','groupterms','authstr','groups','attentiongroup'),
		'field_home'	=> array('videophoto','spacename','spacedescription','domain','addsize','addfriend','menunum','theme','spacecss','blockposition','recentnote','spacenote','privacy','feedfriend','acceptemail','magicgift','stickblogs'),
		'profile'	=> array('realname','gender','birthyear','birthmonth','birthday','constellation','zodiac','telephone','mobile','idcardtype','idcard','address','zipcode','nationality','birthprovince','birthcity','resideprovince','residecity','residedist','residecommunity','residesuite','graduateschool','company','education','occupation','position','revenue','affectivestatus','lookingfor','bloodtype','height','weight','alipay','icq','qq','yahoo','msn','taobao','site','bio','interest','field1','field2','field3','field4','field5','field6','field7','field8'),
		'verify'	=> array('verify1', 'verify2', 'verify3', 'verify4', 'verify5', 'verify6', 'verify7'),
	);
	$profiletable = '';
	foreach($tablefields as $table => $fields) {
		if(in_array($field, $fields)) {
			$profiletable = $table;
			break;
		}
	}
	if($profiletable) {
		$data = array();
		if($_G['uid']) {
			$data = DB::fetch_first("SELECT ".implode(', ', $tablefields[$profiletable])." FROM ".DB::table('common_member_'.$profiletable)." WHERE uid='$_G[uid]'");
		}
		if(!$data) {
			foreach($tablefields[$profiletable] as $k) {
				$data[$k] = '';
			}
		}
		$_G['member'] = array_merge(is_array($_G['member']) ? $_G['member'] : array(), $data);
		return $_G['member'][$field];
	}
}

function daddslashes($string, $force = 1) {
	if(is_array($string)) {
		$keys = array_keys($string);
		foreach($keys as $key) {
			$val = $string[$key];
			unset($string[$key]);
			$string[addslashes($key)] = daddslashes($val, $force);
		}
	} else {
		$string = addslashes($string);
	}
	return $string;
}

function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {
	$ckey_length = 4;
	$key = md5($key != '' ? $key : getglobal('authkey'));
	$keya = md5(substr($key, 0, 16));
	$keyb = md5(substr($key, 16, 16));
	$keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';

	$cryptkey = $keya.md5($keya.$keyc);
	$key_length = strlen($cryptkey);

	$string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
	$string_length = strlen($string);

	$result = '';
	$box = range(0, 255);

	$rndkey = array();
	for($i = 0; $i <= 255; $i++) {
		$rndkey[$i] = ord($cryptkey[$i % $key_length]);
	}

	for($j = $i = 0; $i < 256; $i++) {
		$j = ($j + $box[$i] + $rndkey[$i]) % 256;
		$tmp = $box[$i];
		$box[$i] = $box[$j];
		$box[$j] = $tmp;
	}

	for($a = $j = $i = 0; $i < $string_length; $i++) {
		$a = ($a + 1) % 256;
		$j = ($j + $box[$a]) % 256;
		$tmp = $box[$a];
		$box[$a] = $box[$j];
		$box[$j] = $tmp;
		$result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
	}

	if($operation == 'DECODE') {
		if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
			return substr($result, 26);
		} else {
			return '';
		}
	} else {
		return $keyc.str_replace('=', '', base64_encode($result));
	}

}

function dfsockopen($url, $limit = 0, $post = '', $cookie = '', $bysocket = FALSE, $ip = '', $timeout = 15, $block = TRUE) {
	require_once libfile('function/filesock');
	return _dfsockopen($url, $limit, $post, $cookie, $bysocket, $ip, $timeout, $block);
}

function dhtmlspecialchars($string) {
	if(is_array($string)) {
		foreach($string as $key => $val) {
			$string[$key] = dhtmlspecialchars($val);
		}
	} else {
		$string = str_replace(array('&', '"', '<', '>'), array('&amp;', '&quot;', '&lt;', '&gt;'), $string);
		if(strpos($string, '&amp;#') !== false) {
			$string = preg_replace('/&amp;((#(\d{3,5}|x[a-fA-F0-9]{4}));)/', '&\\1', $string);
		}
	}
	return $string;
}

function dexit($message = '') {
	echo $message;
	output();
	//exit();
}

function dheader($string, $replace = true, $http_response_code = 0) {
	$islocation = substr(strtolower(trim($string)), 0, 8) == 'location';
	if(defined('IN_MOBILE') && strpos($string, 'mobile') === false && $islocation) {
		if (strpos($string, '?') === false) {
			$string = $string.'?mobile=yes';
		} else {
			if(strpos($string, '#') === false) {
				$string = $string.'&mobile=yes';
			} else {
				$str_arr = explode('#', $string);
				$str_arr[0] = $str_arr[0].'&mobile=yes';
				$string = implode('#', $str_arr);
			}
		}
	}
	$string = str_replace(array("\r", "\n"), array('', ''), $string);
	if(empty($http_response_code) || PHP_VERSION < '4.3' ) {
		@header($string, $replace);
	} else {
		@header($string, $replace, $http_response_code);
	}
	if($islocation) {
		exit();
	}
}

function dsetcookie($var, $value = '', $life = 0, $prefix = 1, $httponly = false) {

	global $_G;

	$config = $_G['config']['cookie'];

	$_G['cookie'][$var] = $value;
	$var = ($prefix ? $config['cookiepre'] : '').$var;
	$_COOKIE[$var] = $var;

	if($value == '' || $life < 0) {
		$value = '';
		$life = -1;
	}

	if(defined('IN_MOBILE')) {
		$httponly = false;
	}

	$life = $life > 0 ? getglobal('timestamp') + $life : ($life < 0 ? getglobal('timestamp') - 31536000 : 0);
	$path = $httponly && PHP_VERSION < '5.2.0' ? $config['cookiepath'].'; HttpOnly' : $config['cookiepath'];

	$secure = $_SERVER['SERVER_PORT'] == 443 ? 1 : 0;
	if(PHP_VERSION < '5.2.0') {
		setcookie($var, $value, $life, $path, $config['cookiedomain'], $secure);
	} else {
		setcookie($var, $value, $life, $path, $config['cookiedomain'], $secure, $httponly);
	}
}

function getcookie($key) {
	global $_G;
	return isset($_G['cookie'][$key]) ? $_G['cookie'][$key] : '';
}

function fileext($filename) {
	return addslashes(trim(substr(strrchr($filename, '.'), 1, 10)));
}

function formhash($specialadd = '') {
	global $_G;
	$hashadd = defined('IN_ADMINCP') ? 'Only For Discuz! Admin Control Panel' : '';
	return substr(md5(substr($_G['timestamp'], 0, -7).$_G['username'].$_G['uid'].$_G['authkey'].$hashadd.$specialadd), 8, 8);
}

function checkrobot($useragent = '') {
	static $kw_spiders = array('bot', 'crawl', 'spider' ,'slurp', 'sohu-search', 'lycos', 'robozilla');
	static $kw_browsers = array('msie', 'netscape', 'opera', 'konqueror', 'mozilla');

	$useragent = strtolower(empty($useragent) ? $_SERVER['HTTP_USER_AGENT'] : $useragent);
	if(strpos($useragent, 'http://') === false && dstrpos($useragent, $kw_browsers)) return false;
	if(dstrpos($useragent, $kw_spiders)) return true;
	return false;
}
function checkmobile() {
	global $_G;
	$mobile = array();
	static $mobilebrowser_list =array('iphone', 'android', 'phone', 'mobile', 'wap', 'netfront', 'java', 'opera mobi', 'opera mini',
				'ucweb', 'windows ce', 'symbian', 'series', 'webos', 'sony', 'blackberry', 'dopod', 'nokia', 'samsung',
				'palmsource', 'xda', 'pieplus', 'meizu', 'midp', 'cldc', 'motorola', 'foma', 'docomo', 'up.browser',
				'up.link', 'blazer', 'helio', 'hosin', 'huawei', 'novarra', 'coolpad', 'webos', 'techfaith', 'palmsource',
				'alcatel', 'amoi', 'ktouch', 'nexian', 'ericsson', 'philips', 'sagem', 'wellcom', 'bunjalloo', 'maui', 'smartphone',
				'iemobile', 'spice', 'bird', 'zte-', 'longcos', 'pantech', 'gionee', 'portalmmm', 'jig browser', 'hiptop',
				'benq', 'haier', '^lct', '320x320', '240x320', '176x220');
	$useragent = strtolower($_SERVER['HTTP_USER_AGENT']);
	if(($v = dstrpos($useragent, $mobilebrowser_list, true))) {
		$_G['mobile'] = $v;
		return true;
	}
	$brower = array('mozilla', 'chrome', 'safari', 'opera', 'm3gate', 'winwap', 'openwave', 'myop');
	if(dstrpos($useragent, $brower)) return false;

	$_G['mobile'] = 'unknown';
	if($_GET['mobile'] === 'yes') {
		return true;
	} else {
		return false;
	}
}

function dstrpos($string, &$arr, $returnvalue = false) {
	if(empty($string)) return false;
	foreach((array)$arr as $v) {
		if(strpos($string, $v) !== false) {
			$return = $returnvalue ? $v : true;
			return $return;
		}
	}
	return false;
}

function isemail($email) {
	return strlen($email) > 6 && preg_match("/^[\w\-\.]+@[\w\-\.]+(\.\w+)+$/", $email);
}

function quescrypt($questionid, $answer) {
	return $questionid > 0 && $answer != '' ? substr(md5($answer.md5($questionid)), 16, 8) : '';
}

function random($length, $numeric = 0) {
	$seed = base_convert(md5(microtime().$_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
	$seed = $numeric ? (str_replace('0', '', $seed).'012340567890') : ($seed.'zZ'.strtoupper($seed));
	$hash = '';
	$max = strlen($seed) - 1;
	for($i = 0; $i < $length; $i++) {
		$hash .= $seed{mt_rand(0, $max)};
	}
	return $hash;
}

function strexists($string, $find) {
	return !(strpos($string, $find) === FALSE);
}

function avatar($uid, $size = 'middle', $returnsrc = FALSE, $real = FALSE, $static = FALSE, $ucenterurl = '') {
	global $_G;
	static $staticavatar;
	if($staticavatar === null) {
		$staticavatar = $_G['setting']['avatarmethod'];
	}

	$ucenterurl = empty($ucenterurl) ? $_G['setting']['ucenterurl'] : $ucenterurl;
	$size = in_array($size, array('big', 'middle', 'small')) ? $size : 'middle';
	$uid = abs(intval($uid));
	if(!$staticavatar && !$static) {
		return $returnsrc ? $ucenterurl.'/avatar.php?uid='.$uid.'&size='.$size : '<img src="'.$ucenterurl.'/avatar.php?uid='.$uid.'&size='.$size.($real ? '&type=real' : '').'" />';
	} else {
		$uid = sprintf("%09d", $uid);
		$dir1 = substr($uid, 0, 3);
		$dir2 = substr($uid, 3, 2);
		$dir3 = substr($uid, 5, 2);
		$file = $ucenterurl.'/data/avatar/'.$dir1.'/'.$dir2.'/'.$dir3.'/'.substr($uid, -2).($real ? '_real' : '').'_avatar_'.$size.'.jpg';
		return $returnsrc ? $file : '<img src="'.$file.'" onerror="this.onerror=null;this.src=\''.$ucenterurl.'/images/noavatar_'.$size.'.gif\'" />';
	}
}

function lang($file, $langvar = null, $vars = array(), $default = null) {
	global $_G;
	list($path, $file) = explode('/', $file);
	if(!$file) {
		$file = $path;
		$path = '';
	}

	if($path != 'plugin') {
		$key = $path == '' ? $file : $path.'_'.$file;
		if(!isset($_G['lang'][$key])) {
			include DISCUZ_ROOT.'./source/language/'.($path == '' ? '' : $path.'/').'lang_'.$file.'.php';
			$_G['lang'][$key] = $lang;
		}
		if(defined('IN_MOBILE') && !defined('TPL_DEFAULT')) {
			include DISCUZ_ROOT.'./source/language/mobile/lang_template.php';
			$_G['lang'][$key] = array_merge($_G['lang'][$key], $lang);
		}
		$returnvalue = &$_G['lang'];
	} else {
		if(empty($_G['config']['plugindeveloper'])) {
			loadcache('pluginlanguage_script');
		} elseif(!isset($_G['cache']['pluginlanguage_script'][$file]) && preg_match("/^[a-z]+[a-z0-9_]*$/i", $file)) {
			if(@include(DISCUZ_ROOT.'./data/plugindata/'.$file.'.lang.php')) {
				$_G['cache']['pluginlanguage_script'][$file] = $scriptlang[$file];
			} else {
				loadcache('pluginlanguage_script');
			}
		}
		$returnvalue = & $_G['cache']['pluginlanguage_script'];
		$key = &$file;
	}
	$return = $langvar !== null ? (isset($returnvalue[$key][$langvar]) ? $returnvalue[$key][$langvar] : null) : $returnvalue[$key];
	$return = $return === null ? ($default !== null ? $default : $langvar) : $return;
	$searchs = $replaces = array();
	if($vars && is_array($vars)) {
		foreach($vars as $k => $v) {
			$searchs[] = '{'.$k.'}';
			$replaces[] = $v;
		}
	}
	if(is_string($return) && strpos($return, '{_G/') !== false) {
		preg_match_all('/\{_G\/(.+?)\}/', $return, $gvar);
		foreach($gvar[0] as $k => $v) {
			$searchs[] = $v;
			$replaces[] = getglobal($gvar[1][$k]);
		}
	}
	$return = str_replace($searchs, $replaces, $return);

	return $return;
}

function rpclang($file, $key) {
	global $_G;
	if($_G['lang'][$key]) {
		return $_G['lang'][$key];
	} else {
		include RPC_DIR.'/language/'.'lang_'.$file.'.php';
		
		if(CHARSET == 'gbk') {
			$lang[$key] = diconv($lang[$key], 'utf-8', 'gbk');
		}
		
		$_G['lang'][$key] = $lang[$key];
		return $lang[$key];
	}
}

/*
* 功能：打印$_GET['jsoncallback']函数 + JSON格式数据并结束程序
* 参数：JSON字符串如{'state':'massges'}或PHP数组，如是数据则会转为JSON字符串
* 返回值：无
*/
function jsonexit($string) {
	global $_G;
	
	if(is_array($string)) {
		if(CHARSET != 'utf-8') $string = gbktoutf8($string);
		
		
		//是否开启无图模式
		if($_G['uid']) {
			$setting = DB::getOne("SELECT privacy FROM ".DB::table('common_member_field_home')." WHERE uid='$_G[uid]'");
			$setting = unserialize($setting);
		}
		
		if($setting['appcan']['imagemode']) {
			$string = filterpic($string);
		}
		
		
		$string = json_encode($string);
		
	} else {
		if(CHARSET != 'utf-8') $string = gbktoutf8($string);
	}
	
    echo htmlspecialchars($_GET['jsoncallback']),"($string)";
	exit();
}

/*
* 功能：对字符串或数组进行utf8编码
* 参数：字符串或数组
* 返回值：编码后的字符串或数组
*/
function gbktoutf8($string) {
	if(is_array($string)) {
		foreach($string as $key => $value) {
			$string[$key] = gbktoutf8($value);
		}
	} else {
			$string = diconv($string, CHARSET, "utf-8");
	}
	return $string;
}

/*
* 功能：对字符串或数组进行本地化编码
* 参数：字符串或数组
* 返回值：编码后的字符串或数组
*/
function utf8togbk($string) {
	if(is_array($string)) {
		foreach($string as $key => $value) {
			$string[$key] = utf8togbk($value);
		}
	} else {
		$string = diconv($string, "utf-8", CHARSET);
	}
	return $string;
}

/*
* 功能：通过字段名称获取插件的字段值
* 参数：字段名称
* 返回值：反序列化后的字符串或数组
*/
function getplugindata($var) {
	global $_G;
	loadcache('plugin');
	
	if($value = unserialize($_G['cache']['plugin']['zywx'][$var])) {
		return $value;
	} else {
		return $_G['cache']['plugin']['zywx'][$var];
	}
}

function filterpic($string) {
	global $_G;
	$default = '<img src="'.$_G['siteurl'].'source/plugin/zywx/template/images/default.png"/>';
	if(is_array($string)) {
		foreach($string as $key => $value) {
			$string[$key] = filterpic($value);
		}
	} else {
		$string = preg_replace('/<img.*?(src|file)=["\'](.*?)["\'].*?[^h]>/i', $default, $string);
	}
	return $string;
}


function checktplrefresh($maintpl, $subtpl, $timecompare, $templateid, $cachefile, $tpldir, $file) {
	static $tplrefresh, $timestamp, $targettplname;
	if($tplrefresh === null) {
		$tplrefresh = getglobal('config/output/tplrefresh');
		$timestamp = getglobal('timestamp');
	}

	if(empty($timecompare) || $tplrefresh == 1 || ($tplrefresh > 1 && !($timestamp % $tplrefresh))) {
		if(empty($timecompare) || @filemtime(DISCUZ_ROOT.$subtpl) > $timecompare) {
			require_once DISCUZ_ROOT.'/source/class/class_template.php';
			$template = new template();
			$template->parse_template($maintpl, $templateid, $tpldir, $file, $cachefile);
			if($targettplname === null) {
				$targettplname = getglobal('style/tplfile');
				if(!empty($targettplname)) {
					$targettplname = strtr($targettplname, ':', '_');
					update_template_block($targettplname, $template->blocks);
				}
				$targettplname = true;
			}
			return TRUE;
		}
	}
	return FALSE;
}

function template($file, $templateid = 0, $tpldir = '', $gettplfile = 0, $primaltpl='') {
	global $_G;
	return;
	static $_init_style = false;
	if($_init_style === false) {
		$discuz = & discuz_core::instance();
		$discuz->_init_style();
		$_init_style = true;
	}
	$oldfile = $file;
	if(strpos($file, ':') !== false) {
		$clonefile = '';
		list($templateid, $file, $clonefile) = explode(':', $file);
		$oldfile = $file;
		$file = empty($clonefile) || STYLEID != $_G['cache']['style_default']['styleid'] ? $file : $file.'_'.$clonefile;
		if($templateid == 'diy' && STYLEID == $_G['cache']['style_default']['styleid']) {
			$_G['style']['prefile'] = '';
			$diypath = DISCUZ_ROOT.'./data/diy/'; //DIY????????
			$preend = '_diy_preview';
			$_G['gp_preview'] = !empty($_G['gp_preview']) ? $_G['gp_preview'] : '';
			$curtplname = $oldfile;
			if(isset($_G['cache']['diytemplatename'.$_G['basescript']])) {
				$diytemplatename = &$_G['cache']['diytemplatename'.$_G['basescript']];
			} else {
				$diytemplatename = &$_G['cache']['diytemplatename'];
			}
			$tplsavemod = 0;
			if(isset($diytemplatename[$file]) && file_exists($diypath.$file.'.htm') && ($tplsavemod = 1) || ($file = $primaltpl ? $primaltpl : $oldfile) && isset($diytemplatename[$file]) && file_exists($diypath.$file.'.htm')) {
				$tpldir = 'data/diy';
				!$gettplfile && $_G['style']['tplsavemod'] = $tplsavemod;
				$curtplname = $file;
				if($_G['gp_diy'] == 'yes' || $_G['gp_preview'] == 'yes') { //DIY??????????????????ж?
					$flag = file_exists($diypath.$file.$preend.'.htm');
					if($_G['gp_preview'] == 'yes') {
						$file .= $flag ? $preend : '';
					} else {
						$_G['style']['prefile'] = $flag ? 1 : '';
					}
				}
			} else {
				$file = $primaltpl ? $primaltpl : $oldfile;
			}
			$tplrefresh = $_G['config']['output']['tplrefresh'];
			if($tpldir == 'data/diy' && ($tplrefresh ==1 || ($tplrefresh > 1 && !($_G['timestamp'] % $tplrefresh))) && filemtime($diypath.$file.'.htm') < filemtime(DISCUZ_ROOT.TPLDIR.'/'.($primaltpl ? $primaltpl : $oldfile).'.htm')) {
				if (!updatediytemplate($file)) {
					unlink($diypath.$file.'.htm');
					$tpldir = '';
				}
			}

			if (!$gettplfile && empty($_G['style']['tplfile'])) {
				$_G['style']['tplfile'] = empty($clonefile) ? $curtplname : $oldfile.':'.$clonefile;
			}

			$_G['style']['prefile'] = !empty($_G['gp_preview']) && $_G['gp_preview'] == 'yes' ? '' : $_G['style']['prefile'];

		} else {
			$tpldir = './source/plugin/'.$templateid.'/template';
		}
	}

	$file .= !empty($_G['inajax']) && ($file == 'common/header' || $file == 'common/footer') ? '_ajax' : '';
	$tpldir = $tpldir ? $tpldir : (defined('TPLDIR') ? TPLDIR : '');
	$templateid = $templateid ? $templateid : (defined('TEMPLATEID') ? TEMPLATEID : '');
	$filebak = $file;

	if(defined('IN_MOBILE') && !defined('TPL_DEFAULT') && strpos($file, 'mobile/') === false || $_G['forcemobilemessage']) {
		$file = 'mobile/'.$oldfile;
	}

	$tplfile = ($tpldir ? $tpldir.'/' : './template/').$file.'.htm';

	$file == 'common/header' && defined('CURMODULE') && CURMODULE && $file = 'common/header_'.$_G['basescript'].'_'.CURMODULE;

	if(defined('IN_MOBILE') && !defined('TPL_DEFAULT')) {
		if(strpos($tpldir, 'plugin')) {
			if(!file_exists(DISCUZ_ROOT.$tpldir.'/'.$file.'.htm')) {
				require_once libfile('class/error');
				discuz_error::template_error('template_notfound', $tpldir.'/'.$file.'.htm');
			} else {
				$mobiletplfile = $tpldir.'/'.$file.'.htm';
			}
		}
		!$mobiletplfile && $mobiletplfile = $file.'.htm';
		if(strpos($tpldir, 'plugin') && file_exists(DISCUZ_ROOT.$mobiletplfile)) {
			$tplfile = $mobiletplfile;
		} elseif(!file_exists(DISCUZ_ROOT.TPLDIR.'/'.$mobiletplfile)) {
			$mobiletplfile = './template/default/'.$mobiletplfile;
			if(!file_exists(DISCUZ_ROOT.$mobiletplfile) && !$_G['forcemobilemessage']) {
				$tplfile = str_replace('mobile/', '', $tplfile);
				$file = str_replace('mobile/', '', $file);
				define('TPL_DEFAULT', true);
			} else {
				$tplfile = $mobiletplfile;
			}
		} else {
			$tplfile = TPLDIR.'/'.$mobiletplfile;
		}
	}

	$cachefile = './data/template/'.(defined('STYLEID') ? STYLEID.'_' : '_').$templateid.'_'.str_replace('/', '_', $file).'.tpl.php';

	if($templateid != 1 && !file_exists(DISCUZ_ROOT.$tplfile)) {
		$tplfile = './template/default/'.$filebak.'.htm';
	}

	if($gettplfile) {
		return $tplfile;
	}
	checktplrefresh($tplfile, $tplfile, @filemtime(DISCUZ_ROOT.$cachefile), $templateid, $cachefile, $tpldir, $file);
	return DISCUZ_ROOT.$cachefile;
}

function modauthkey($id) {
	global $_G;
	return md5($_G['username'].$_G['uid'].$_G['authkey'].substr(TIMESTAMP, 0, -7).$id);
}

function getcurrentnav() {
	global $_G;
	if(!empty($_G['mnid'])) {
		return $_G['mnid'];
	}
	$mnid = '';
	$_G['basefilename'] = $_G['basefilename'] == $_G['basescript'] ? $_G['basefilename'] : $_G['basescript'].'.php';
	if(isset($_G['setting']['navmns'][$_G['basefilename']])) {
		foreach($_G['setting']['navmns'][$_G['basefilename']] as $navmn) {
			if($navmn[0] == array_intersect_assoc($navmn[0], $_GET)) {
				$mnid = $navmn[1];
			}
		}
	}
	if(!$mnid && isset($_G['setting']['navdms'])) {
		foreach($_G['setting']['navdms'] as $navdm => $navid) {
			if(strpos(strtolower($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']), $navdm) !== false) {
				$mnid = $navid;
				break;
			}
		}
	}
	if(!$mnid && isset($_G['setting']['navmn'][$_G['basefilename']])) {
		$mnid = $_G['setting']['navmn'][$_G['basefilename']];
	}
	return $mnid;
}

function loaducenter() {
	require_once DISCUZ_ROOT.'./config/config_ucenter.php';
	require_once DISCUZ_ROOT.'./uc_client/client.php';
}

function loadcache($cachenames, $force = false) {
	global $_G;
	static $loadedcache = array();
	$cachenames = is_array($cachenames) ? $cachenames : array($cachenames);
	$caches = array();
	foreach ($cachenames as $k) {
		if(!isset($loadedcache[$k]) || $force) {
			$caches[] = $k;
			$loadedcache[$k] = true;
		}
	}

	if(!empty($caches)) {
		$cachedata = cachedata($caches);
		foreach($cachedata as $cname => $data) {
			if($cname == 'setting') {
				$_G['setting'] = $data;
			} elseif(strpos($cname, 'usergroup_'.$_G['groupid']) !== false) {
				$_G['cache'][$cname] = $_G['group'] = $data;
			} elseif($cname == 'style_default') {
				$_G['cache'][$cname] = $_G['style'] = $data;
			} elseif($cname == 'grouplevels') {
				$_G['grouplevels'] = $data;
			} else {
				$_G['cache'][$cname] = $data;
			}
		}
	}
	return true;
}

function cachedata($cachenames) {
	global $_G;
	static $isfilecache, $allowmem;

	if(!isset($isfilecache)) {
		$isfilecache = getglobal('config/cache/type') == 'file';
		$allowmem = memory('check');
	}

	$data = array();
	$cachenames = is_array($cachenames) ? $cachenames : array($cachenames);
	if($allowmem) {
		$newarray = array();
		foreach ($cachenames as $name) {
			$data[$name] = memory('get', $name);
			if($data[$name] === null) {
				$data[$name] = null;
				$newarray[] = $name;
			}
		}
		if(empty($newarray)) {
			return $data;
		} else {
			$cachenames = $newarray;
		}
	}

	if($isfilecache) {
		$lostcaches = array();
		foreach($cachenames as $cachename) {
			if(!@include_once(DISCUZ_ROOT.'./data/cache/cache_'.$cachename.'.php')) {
				$lostcaches[] = $cachename;
			}
		}
		if(!$lostcaches) {
			return $data;
		}
		$cachenames = $lostcaches;
		unset($lostcaches);
	}
	$query = DB::query("SELECT * FROM ".DB::table('common_syscache')." WHERE cname IN ('".implode("','", $cachenames)."')");
	while($syscache = DB::fetch($query)) {
		$data[$syscache['cname']] = $syscache['ctype'] ? unserialize($syscache['data']) : $syscache['data'];
		$allowmem && (memory('set', $syscache['cname'], $data[$syscache['cname']]));
		if($isfilecache) {
			$cachedata = '$data[\''.$syscache['cname'].'\'] = '.var_export($data[$syscache['cname']], true).";\n\n";
			if($fp = @fopen(DISCUZ_ROOT.'./data/cache/cache_'.$syscache['cname'].'.php', 'wb')) {
				fwrite($fp, "<?php\n//Discuz! cache file, DO NOT modify me!\n//Identify: ".md5($syscache['cname'].$cachedata.$_G['config']['security']['authkey'])."\n\n$cachedata?>");
				fclose($fp);
			}
		}
	}

	foreach($cachenames as $name) {
		if($data[$name] === null) {
			$data[$name] = null;
			$allowmem && (memory('set', $name, array()));
		}
	}

	return $data;
}

function dgmdate($timestamp, $format = 'dt', $timeoffset = '9999', $uformat = '') {
	global $_G;
	$format == 'u' && !$_G['setting']['dateconvert'] && $format = 'dt';
	static $dformat, $tformat, $dtformat, $offset, $lang;
	if($dformat === null) {
		$dformat = getglobal('setting/dateformat');
		$tformat = getglobal('setting/timeformat');
		$dtformat = $dformat.' '.$tformat;
		$offset = getglobal('member/timeoffset');
		$lang = lang('core', 'date');
	}
	$timeoffset = $timeoffset == 9999 ? $offset : $timeoffset;
	$timestamp += $timeoffset * 3600;
	$format = empty($format) || $format == 'dt' ? $dtformat : ($format == 'd' ? $dformat : ($format == 't' ? $tformat : $format));
	if($format == 'u') {
		$todaytimestamp = TIMESTAMP - (TIMESTAMP + $timeoffset * 3600) % 86400 + $timeoffset * 3600;
		$s = gmdate('y-m-d', $timestamp);
		$time = TIMESTAMP + $timeoffset * 3600 - $timestamp;
		if($timestamp >= $todaytimestamp) {
			if($time > 3600) {
				return '<span title="'.$s.'">'.intval($time / 3600).'&nbsp;'.$lang['hour'].$lang['before'].'</span>';
			} elseif($time > 1800) {
				return '<span title="'.$s.'">'.$lang['half'].$lang['hour'].$lang['before'].'</span>';
			} elseif($time > 60) {
				return '<span title="'.$s.'">'.intval($time / 60).'&nbsp;'.$lang['min'].$lang['before'].'</span>';
			} elseif($time > 0) {
				return '<span title="'.$s.'">'.$time.'&nbsp;'.$lang['sec'].$lang['before'].'</span>';
			} elseif($time == 0) {
				return '<span title="'.$s.'">'.$lang['now'].'</span>';
			} else {
				return $s;
			}
		} elseif(($days = intval(($todaytimestamp - $timestamp) / 86400)) >= 0 && $days < 7) {
			if($days == 0) {
				return '<span title="'.$s.'">'.$lang['yday'].'&nbsp;'.gmdate($tformat, $timestamp).'</span>';
			} elseif($days == 1) {
				return '<span title="'.$s.'">'.$lang['byday'].'&nbsp;'.gmdate($tformat, $timestamp).'</span>';
			} else {
				return '<span title="'.$s.'">'.($days + 1).'&nbsp;'.$lang['day'].$lang['before'].'</span>';
			}
		} else {
			return $s;
		}
	} else {
		return substr(gmdate($format, $timestamp),2);
	}
}

function dmktime($date) {
	if(strpos($date, '-')) {
		$time = explode('-', $date);
		return mktime(0, 0, 0, $time[1], $time[2], $time[0]);
	}
	return 0;
}

function save_syscache($cachename, $data) {
	static $isfilecache, $allowmem;
	if(!isset($isfilecache)) {
		$isfilecache = getglobal('config/cache/type') == 'file';
		$allowmem = memory('check');
	}

	if(is_array($data)) {
		$ctype = 1;
		$data = addslashes(serialize($data));
	} else {
		$ctype = 0;
	}

	DB::query("REPLACE INTO ".DB::table('common_syscache')." (cname, ctype, dateline, data) VALUES ('$cachename', '$ctype', '".TIMESTAMP."', '$data')");

	$allowmem && memory('rm', $cachename);
	$isfilecache && @unlink(DISCUZ_ROOT.'./data/cache/cache_'.$cachename.'.php');
}

function block_get($parameter) {
	global $_G;
	static $allowmem;
	if($allowmem === null) {
		include_once libfile('function/block');
		$allowmem = getglobal('setting/memory/diyblock/enable') && memory('check');
	}
	if(!$allowmem) {
		block_get_batch($parameter);
		return true;
	}
	$blockids = explode(',', $parameter);
	$lostbids = array();
	foreach ($blockids as $bid) {
		$bid = intval($bid);
		if($bid) {
			$_G['block'][$bid] = memory('get', 'blockcache_'.$bid);
			if($_G['block'][$bid] === null) {
				$lostbids[] = $bid;
			} else {
				$styleid = $_G['block'][$bid]['styleid'];
				if($styleid && !isset($_G['blockstyle_'.$styleid])) {
					$_G['blockstyle_'.$styleid] = memory('get', 'blockstylecache_'.$styleid);
				}
			}
		}
	}

	if($lostbids) {
		block_get_batch(implode(',', $lostbids));
		foreach ($lostbids as $bid) {
			if(isset($_G['block'][$bid])) {
				memory('set', 'blockcache_'.$bid, $_G['block'][$bid], getglobal('setting/memory/diyblock/ttl'));
				$styleid = $_G['block'][$bid]['styleid'];
				if($styleid && $_G['blockstyle_'.$styleid]) {
					memory('set', 'blockstylecache_'.$styleid, $_G['blockstyle_'.$styleid], getglobal('setting/memory/diyblock/ttl'));
				}
			}
		}
	}
}

function block_display($bid) {
	include_once libfile('function/block');
	block_display_batch($bid);
}

function dimplode($array) {
	if(!empty($array)) {
		return "'".implode("','", is_array($array) ? $array : array($array))."'";
	} else {
		return 0;
	}
}

function libfile($libname, $folder = '') {
	$libpath = DISCUZ_ROOT.'/source/'.$folder;
	if(strstr($libname, '/')) {
		list($pre, $name) = explode('/', $libname);
		
		$file = RPC_DIR.($folder?'/'.$folder:'')."/{$pre}/{$pre}_{$name}.php";
		
		if(file_exists($file)) {
			return $file;
		}
		
		return realpath("{$libpath}/{$pre}/{$pre}_{$name}.php");
	} else {
		return realpath("{$libpath}/{$libname}.php");
	}
}

function dstrlen($str) {
	if(strtolower(CHARSET) != 'utf-8') {
		return strlen($str);
	}
	$count = 0;
	for($i = 0; $i < strlen($str); $i++){
		$value = ord($str[$i]);
		if($value > 127) {
			$count++;
			if($value >= 192 && $value <= 223) $i++;
			elseif($value >= 224 && $value <= 239) $i = $i + 2;
			elseif($value >= 240 && $value <= 247) $i = $i + 3;
	    	}
    		$count++;
	}
	return $count;
}

function cutstr($string, $length, $dot = '') {
	if(strlen($string) <= $length) {
		return $string;
	}

	$pre = chr(1);
	$end = chr(1);
	$string = str_replace(array('&amp;', '&quot;', '&lt;', '&gt;'), array($pre.'&'.$end, $pre.'"'.$end, $pre.'<'.$end, $pre.'>'.$end), $string);

	$strcut = '';
	if(strtolower(CHARSET) == 'utf-8') {

		$n = $tn = $noc = 0;
		while($n < strlen($string)) {

			$t = ord($string[$n]);
			if($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
				$tn = 1; $n++; $noc++;
			} elseif(194 <= $t && $t <= 223) {
				$tn = 2; $n += 2; $noc += 2;
			} elseif(224 <= $t && $t <= 239) {
				$tn = 3; $n += 3; $noc += 2;
			} elseif(240 <= $t && $t <= 247) {
				$tn = 4; $n += 4; $noc += 2;
			} elseif(248 <= $t && $t <= 251) {
				$tn = 5; $n += 5; $noc += 2;
			} elseif($t == 252 || $t == 253) {
				$tn = 6; $n += 6; $noc += 2;
			} else {
				$n++;
			}

			if($noc >= $length) {
				break;
			}

		}
		if($noc > $length) {
			$n -= $tn;
		}

		$strcut = substr($string, 0, $n);

	} else {
		for($i = 0; $i < $length; $i++) {
			$strcut .= ord($string[$i]) > 127 ? $string[$i].$string[++$i] : $string[$i];
		}
	}

	$strcut = str_replace(array($pre.'&'.$end, $pre.'"'.$end, $pre.'<'.$end, $pre.'>'.$end), array('&amp;', '&quot;', '&lt;', '&gt;'), $strcut);

	$pos = strrpos($strcut, chr(1));
	if($pos !== false) {
		$strcut = substr($strcut,0,$pos);
	}
	return $strcut.$dot;
}

function dstripslashes($string) {
	if(empty($string)) return $string;
	if(is_array($string)) {
		foreach($string as $key => $val) {
			$string[$key] = dstripslashes($val);
		}
	} else {
		$string = stripslashes($string);
	}
	return $string;
}

function aidencode($aid, $type = 0, $tid = 0) {
	global $_G;
	$s = !$type ? $aid.'|'.substr(md5($aid.md5($_G['config']['security']['authkey']).TIMESTAMP.$_G['uid']), 0, 8).'|'.TIMESTAMP.'|'.$_G['uid'].'|'.$tid : $aid.'|'.md5($aid.md5($_G['config']['security']['authkey']).TIMESTAMP).'|'.TIMESTAMP;
	return rawurlencode(base64_encode($s));
}

function getforumimg($aid, $nocache = 0, $w = 200, $h = 200, $type = '') {
	global $_G;
	$key = md5($aid.'|'.$w.'|'.$h);
	return 'forum.php?mod=image&aid='.$aid.'&size='.$w.'x'.$h.'&key='.rawurlencode($key).($nocache ? '&nocache=yes' : '').($type ? '&type='.$type : '');
}

function rewriteoutput($type, $returntype, $host) {
	global $_G;
	$fextra = '';
	if($type == 'forum_forumdisplay') {
		list(,,, $fid, $page, $extra) = func_get_args();
		$r = array(
			'{fid}' => empty($_G['setting']['forumkeys'][$fid]) ? $fid : $_G['setting']['forumkeys'][$fid],
			'{page}' => $page ? $page : 1,
		);
	} elseif($type == 'forum_viewthread') {
		list(,,, $tid, $page, $prevpage, $extra) = func_get_args();
		$r = array(
			'{tid}' => $tid,
			'{page}' => $page ? $page : 1,
			'{prevpage}' => $prevpage && !IS_ROBOT ? $prevpage : 1,
		);
	} elseif($type == 'home_space') {
		list(,,, $uid, $username, $extra) = func_get_args();
		$_G['setting']['rewritecompatible'] && $username = rawurlencode($username);
		$r = array(
			'{user}' => $uid ? 'uid' : 'username',
			'{value}' => $uid ? $uid : $username,
		);
	} elseif($type == 'home_blog') {
		list(,,, $uid, $blogid, $extra) = func_get_args();
		$r = array(
			'{uid}' => $uid,
			'{blogid}' => $blogid,
		);
	} elseif($type == 'group_group') {
		list(,,, $fid, $page, $extra) = func_get_args();
		$r = array(
			'{fid}' => $fid,
			'{page}' => $page ? $page : 1,
		);
	} elseif($type == 'portal_topic') {
		list(,,, $name, $extra) = func_get_args();
		$r = array(
			'{name}' => $name,
		);
	} elseif($type == 'portal_article') {
		list(,,, $id, $page, $extra) = func_get_args();
		$r = array(
			'{id}' => $id,
			'{page}' => $page ? $page : 1,
		);
	} elseif($type == 'forum_archiver') {
		list(,, $action, $value, $page, $extra) = func_get_args();
		$host = '';
		$r = array(
			'{action}' => $action,
			'{value}' => $value,
		);
		if($page) {
			$fextra = '?page='.$page;
		}
	}
	$href = str_replace(array_keys($r), $r, $_G['setting']['rewriterule'][$type]).$fextra;
	if(!$returntype) {
		return '<a href="'.$host.$href.'"'.(!empty($extra) ? stripslashes($extra) : '').'>';
	} else {
		return $host.$href;
	}
}

function mobilereplace($file, $replace) {
	global $_G;
	/*if(strpos($replace, 'mobile=') === false) {
		if(strpos($replace, '?') === false) {
			$replace = 'href="'.$file.$replace.'?mobile=yes"';
		} else {
			$replace = 'href="'.$file.$replace.'&mobile=yes"';
		}
		return $replace;
	} else {*/
		return 'href="'.$file.$replace.'"';
	//}
}

function mobileoutput() {
	global $_G;
	if(!defined('TPL_DEFAULT')) {
		$content = ob_get_contents();
		ob_end_clean();
		$content = preg_replace("/href=\"(\w+\.php)(.*?)\"/e", "mobilereplace('\\1', '\\2')", $content);

		ob_start();
		$content = '<?xml version="1.0" encoding="utf-8"?>'.$content;
		if('utf-8' != CHARSET) {
			@header('Content-Type: text/html; charset=utf-8');
			$content = diconv($content, CHARSET, 'utf-8');
		}
		echo $content;
		exit();

	} elseif (defined('TPL_DEFAULT') && !$_G['cookie']['dismobilemessage'] && $_G['mobile']) {
		ob_end_clean();
		ob_start();
		$_G['forcemobilemessage'] = true;
		$query_sting_tmp = str_replace(array('&mobile=yes', 'mobile=yes'), array(''), $_SERVER['QUERY_STRING']);
		$_G['setting']['mobile']['pageurl'] = $_G['siteurl'].substr($_G['PHP_SELF'], 1).($query_sting_tmp ? '?'.$query_sting_tmp.'&mobile=no' : '?mobile=no' );
		unset($query_sting_tmp);
		dsetcookie('dismobilemessage', '1', 3600);
		showmessage('not_in_mobile');
		exit;
	}
}

function output() {

	global $_G;


	if(defined('DISCUZ_OUTPUTED')) {
		return;
	} else {
		define('DISCUZ_OUTPUTED', 1);
	}

	if(!empty($_G['blockupdate'])) {
		block_updatecache($_G['blockupdate']['bid']);
	}

	if(defined('IN_MOBILE')) {
		mobileoutput();
	}
	$havedomain = implode('', $_G['setting']['domain']['app']);
	if($_G['setting']['rewritestatus'] || !empty($havedomain)) {
		$content = ob_get_contents();
		$content = output_replace($content);


		ob_end_clean();
		//$_G['gzipcompress'] ? ob_start('ob_gzhandler') : ob_start();

		echo $content;
	}
	if($_G['setting']['ftp']['connid']) {
		@ftp_close($_G['setting']['ftp']['connid']);
	}
	$_G['setting']['ftp'] = array();

	if(defined('CACHE_FILE') && CACHE_FILE && !defined('CACHE_FORBIDDEN') && !defined('IN_MOBILE')) {
		if(diskfreespace(DISCUZ_ROOT.'./'.$_G['setting']['cachethreaddir']) > 1000000) {
			if($fp = @fopen(CACHE_FILE, 'w')) {
				flock($fp, LOCK_EX);
				fwrite($fp, empty($content) ? ob_get_contents() : $content);
			}
			@fclose($fp);
			chmod(CACHE_FILE, 0777);
		}
	}

	if(defined('DISCUZ_DEBUG') && DISCUZ_DEBUG && @include(libfile('function/debug'))) {
		function_exists('debugmessage') && debugmessage();
	}
}

function output_replace($content) {
	global $_G;
	if(defined('IN_MODCP') || defined('IN_ADMINCP')) return $content;
	if(!empty($_G['setting']['output']['str']['search'])) {
		if(empty($_G['setting']['domain']['app']['default'])) {
			$_G['setting']['output']['str']['replace'] = str_replace('{CURHOST}', $_G['siteurl'], $_G['setting']['output']['str']['replace']);
		}
		$content = str_replace($_G['setting']['output']['str']['search'], $_G['setting']['output']['str']['replace'], $content);
	}
	if(!empty($_G['setting']['output']['preg']['search'])) {
		if(empty($_G['setting']['domain']['app']['default'])) {
			$_G['setting']['output']['preg']['search'] = str_replace('\{CURHOST\}', preg_quote($_G['siteurl'], '/'), $_G['setting']['output']['preg']['search']);
			$_G['setting']['output']['preg']['replace'] = str_replace('{CURHOST}', $_G['siteurl'], $_G['setting']['output']['preg']['replace']);
		}

		$content = preg_replace($_G['setting']['output']['preg']['search'], $_G['setting']['output']['preg']['replace'], $content);
	}

	return $content;
}

function output_ajax() {
	global $_G;
	$s = ob_get_contents();
	ob_end_clean();
	$s = preg_replace("/([\\x01-\\x08\\x0b-\\x0c\\x0e-\\x1f])+/", ' ', $s);
	$s = str_replace(array(chr(0), ']]>'), array(' ', ']]&gt;'), $s);
	if(defined('DISCUZ_DEBUG') && DISCUZ_DEBUG && @include(libfile('function/debug'))) {
		function_exists('debugmessage') && $s .= debugmessage(1);
	}
	$havedomain = implode('', $_G['setting']['domain']['app']);
	if($_G['setting']['rewritestatus'] || !empty($havedomain)) {
        $s = output_replace($s);
	}
	return $s;
}

function runhooks() {
	/*if(!defined('HOOKTYPE')) {
		define('HOOKTYPE', !defined('IN_MOBILE') ? 'hookscript' : 'hookscriptmobile');
	}
	if(defined('CURMODULE')) {
		global $_G;
		if($_G['setting']['plugins'][HOOKTYPE.'_common']) {
			hookscript('common', 'global', 'funcs', array(), 'common');
		}
		hookscript(CURMODULE, $_G['basescript']);
	}*/
}

function hookscript($script, $hscript, $type = 'funcs', $param = array(), $func = '') {
	global $_G;

	static $pluginclasses;
	if($hscript == 'home') {
		if($script != 'spacecp') {
			$script = 'space_'.(!empty($_G['gp_do']) ? $_G['gp_do'] : (!empty($_GET['do']) ? $_GET['do'] : ''));
		} else {
			$script .= !empty($_G['gp_ac']) ? '_'.$_G['gp_ac'] : (!empty($_GET['ac']) ? '_'.$_GET['ac'] : '');
		}
	}
	if(!isset($_G['setting'][HOOKTYPE][$hscript][$script][$type])) {
		return;
	}
	if(!isset($_G['cache']['plugin'])) {
		loadcache('plugin');
	}
	foreach((array)$_G['setting'][HOOKTYPE][$hscript][$script]['module'] as $identifier => $include) {
		$hooksadminid[$identifier] = !$_G['setting'][HOOKTYPE][$hscript][$script]['adminid'][$identifier] || ($_G['setting'][HOOKTYPE][$hscript][$script]['adminid'][$identifier] && $_G['adminid'] > 0 && $_G['setting']['hookscript'][$hscript][$script]['adminid'][$identifier] >= $_G['adminid']);
		if($hooksadminid[$identifier]) {
			@include_once DISCUZ_ROOT.'./source/plugin/'.$include.'.class.php';
		}
	}
	if(@is_array($_G['setting'][HOOKTYPE][$hscript][$script][$type])) {
		$_G['inhookscript'] = true;
		$funcs = !$func ? $_G['setting'][HOOKTYPE][$hscript][$script][$type] : array($func => $_G['setting'][HOOKTYPE][$hscript][$script][$type][$func]);
		foreach($funcs as $hookkey => $hookfuncs) {
			foreach($hookfuncs as $hookfunc) {
				if($hooksadminid[$hookfunc[0]]) {
					$classkey = (HOOKTYPE != 'hookscriptmobile' ? '' : 'mobile').'plugin_'.($hookfunc[0].($hscript != 'global' ? '_'.$hscript : ''));
					if(!class_exists($classkey)) {
						continue;
					}
					if(!isset($pluginclasses[$classkey])) {
						$pluginclasses[$classkey] = new $classkey;
					}
					if(!method_exists($pluginclasses[$classkey], $hookfunc[1])) {
						continue;
					}
					$return = $pluginclasses[$classkey]->$hookfunc[1]($param);

					if(is_array($return)) {
						if(!isset($_G['setting']['pluginhooks'][$hookkey]) || is_array($_G['setting']['pluginhooks'][$hookkey])) {
							foreach($return as $k => $v) {
								$_G['setting']['pluginhooks'][$hookkey][$k] .= $v;
							}
						}
					} else {
						if(!is_array($_G['setting']['pluginhooks'][$hookkey])) {
							$_G['setting']['pluginhooks'][$hookkey] .= $return;
						} else {
							foreach($_G['setting']['pluginhooks'][$hookkey] as $k => $v) {
								$_G['setting']['pluginhooks'][$hookkey][$k] .= $return;
							}
						}
					}
				}
			}
		}
	}

	$_G['inhookscript'] = false;
}

function hookscriptoutput($tplfile) {
	global $_G;
	if(!empty($_G['hookscriptoutput'])) {
		return;
	}
	if(!empty($_G['gp_mobiledata'])) {
		require_once libfile('class/mobiledata');
		$mobiledata = new mobiledata();
		if($mobiledata->validator()) {
			$mobiledata->outputvariables();
		}
	}
	hookscript('global', 'global');
	if(defined('CURMODULE')) {
		$param = array('template' => $tplfile, 'message' => $_G['hookscriptmessage'], 'values' => $_G['hookscriptvalues']);
		hookscript(CURMODULE, $_G['basescript'], 'outputfuncs', $param);
	}
	$_G['hookscriptoutput'] = true;
}

function pluginmodule($pluginid, $type) {
	global $_G;
	if(!isset($_G['cache']['plugin'])) {
		loadcache('plugin');
	}
	list($identifier, $module) = explode(':', $pluginid);
	if(!is_array($_G['setting']['plugins'][$type]) || !array_key_exists($pluginid, $_G['setting']['plugins'][$type])) {
		showmessage('plugin_nonexistence');
	}
	if(!empty($_G['setting']['plugins'][$type][$pluginid]['url'])) {
		dheader('location: '.$_G['setting']['plugins'][$type][$pluginid]['url']);
	}
	$directory = $_G['setting']['plugins'][$type][$pluginid]['directory'];
	if(empty($identifier) || !preg_match("/^[a-z]+[a-z0-9_]*\/$/i", $directory) || !preg_match("/^[a-z0-9_\-]+$/i", $module)) {
		showmessage('undefined_action');
	}
	if(@!file_exists(DISCUZ_ROOT.($modfile = './source/plugin/'.$directory.$module.'.inc.php'))) {
		showmessage('plugin_module_nonexistence', '', array('mod' => $modfile));
	}
	return DISCUZ_ROOT.$modfile;
}
function updatecreditbyaction($action, $uid = 0, $extrasql = array(), $needle = '', $coef = 1, $update = 1, $fid = 0) {

	include_once libfile('class/credit');
	$credit = & credit::instance();
	if($extrasql) {
		$credit->extrasql = $extrasql;
	}
	return $credit->execrule($action, $uid, $needle, $coef, $update, $fid);
}

function checklowerlimit($action, $uid = 0, $coef = 1, $fid = 0, $returnonly = 0) {
	require_once libfile('function/credit');
	return _checklowerlimit($action, $uid, $coef, $fid, $returnonly);
}

function batchupdatecredit($action, $uids = 0, $extrasql = array(), $coef = 1, $fid = 0) {

	include_once libfile('class/credit');
	$credit = & credit::instance();
	if($extrasql) {
		$credit->extrasql = $extrasql;
	}
	return $credit->updatecreditbyrule($action, $uids, $coef, $fid);
}


function updatemembercount($uids, $dataarr = array(), $checkgroup = true, $operation = '', $relatedid = 0, $ruletxt = '') {
	if(!empty($uids) && (is_array($dataarr) && $dataarr)) {
		require_once libfile('function/credit');
		return _updatemembercount($uids, $dataarr, $checkgroup, $operation, $relatedid, $ruletxt);
	}
	return true;
}

function checkusergroup($uid = 0) {
	require_once libfile('class/credit');
	$credit = & credit::instance();
	$credit->checkusergroup($uid);
}

function checkformulasyntax($formula, $operators, $tokens) {
	$var = implode('|', $tokens);
	$operator = implode('', $operators);

	$operator = str_replace(
		array('+', '-', '*', '/', '(', ')', '{', '}', '\''),
		array('\+', '\-', '\*', '\/', '\(', '\)', '\{', '\}', '\\\''),
		$operator
	);

	if(!empty($formula)) {
		if(!preg_match("/^([$operator\.\d\(\)]|(($var)([$operator\(\)]|$)+))+$/", $formula) || !is_null(eval(preg_replace("/($var)/", "\$\\1", $formula).';'))){
			return false;
		}
	}
	return true;
}

function checkformulacredits($formula) {
	return checkformulasyntax(
		$formula,
		array('+', '-', '*', '/', ' '),
		array('extcredits[1-8]', 'digestposts', 'posts', 'threads', 'oltime', 'friends', 'doings', 'polls', 'blogs', 'albums', 'sharings')
	);
}

function debug($var = null, $vardump = false) {
	echo '<pre>';
	if($var === null) {
	
	} else {
		if($vardump) {
			var_dump($var);
		} else {
		}
	}
	exit();
}

function debuginfo() {
	global $_G;
	if(getglobal('setting/debug')) {
		$db = & DB::object();
		$_G['debuginfo'] = array(
		    'time' => number_format((dmicrotime() - $_G['starttime']), 6),
		    'queries' => $db->querynum,
		    'memory' => ucwords($_G['memory'])
		    );
		if($db->slaveid) {
			$_G['debuginfo']['queries'] = 'Total '.$db->querynum.', Slave '.$db->slavequery;
		}
		return TRUE;
	} else {
		return FALSE;
	}
}

function getfocus_rand($module) {
	global $_G;

	if(empty($_G['setting']['focus']) || !array_key_exists($module, $_G['setting']['focus'])) {
		return null;
	}
	do {
		$focusid = $_G['setting']['focus'][$module][array_rand($_G['setting']['focus'][$module])];
		if(!empty($_G['cookie']['nofocus_'.$focusid])) {
			unset($_G['setting']['focus'][$module][$focusid]);
			$continue = 1;
		} else {
			$continue = 0;
		}
	} while(!empty($_G['setting']['focus'][$module]) && $continue);
	if(!$_G['setting']['focus'][$module]) {
		return null;
	}
	loadcache('focus');
	if(empty($_G['cache']['focus']['data']) || !is_array($_G['cache']['focus']['data'])) {
		return null;
	}
	return $focusid;
}

function check_seccode($value, $idhash) {
	global $_G;
	if(!$_G['setting']['seccodestatus']) {
		return true;
	}
	if(!isset($_G['cookie']['seccode'.$idhash])) {
		return false;
	}
	list($checkvalue, $checktime, $checkidhash, $checkformhash) = explode("\t", authcode($_G['cookie']['seccode'.$idhash], 'DECODE', $_G['config']['security']['authkey']));
	return $checkvalue == strtoupper($value) && TIMESTAMP - 180 > $checktime && $checkidhash == $idhash && FORMHASH == $checkformhash;
}

function check_secqaa($value, $idhash) {
	global $_G;
	if(!$_G['setting']['secqaa']) {
		return true;
	}
	if(!isset($_G['cookie']['secqaa'.$idhash])) {
		return false;
	}
	loadcache('secqaa');
	list($checkvalue, $checktime, $checkidhash, $checkformhash) = explode("\t", authcode($_G['cookie']['secqaa'.$idhash], 'DECODE', $_G['config']['security']['authkey']));
	return $checkvalue == md5($value) && TIMESTAMP - 180 > $checktime && $checkidhash == $idhash && FORMHASH == $checkformhash;
}

function adshow($parameter) {
	global $_G;
	if($_G['inajax']) {
		return;
	}
	$params = explode('/', $parameter);
	$customid = 0;
	$customc = explode('_', $params[0]);
	if($customc[0] == 'custom') {
		$params[0] = $customc[0];
		$customid = $customc[1];
	}
	$adcontent = null;
	if(empty($_G['setting']['advtype']) || !in_array($params[0], $_G['setting']['advtype'])) {
		$adcontent = '';
	}
	if($adcontent === null) {
		loadcache('advs');
		$adids = array();
		$evalcode = &$_G['cache']['advs']['evalcode'][$params[0]];
		$parameters = &$_G['cache']['advs']['parameters'][$params[0]];
		$codes = &$_G['cache']['advs']['code'][$_G['basescript']][$params[0]];
		if(!empty($codes)) {
			foreach($codes as $adid => $code) {
				$parameter = &$parameters[$adid];
				$checked = true;
				@eval($evalcode['check']);
				if($checked) {
					$adids[] = $adid;
				}
			}
			if(!empty($adids)) {
				$adcode = $extra = '';
				@eval($evalcode['create']);
				if(empty($notag)) {
					$adcontent = '<div'.($params[1] != '' ? ' class="'.$params[1].'"' : '').$extra.'>'.$adcode.'</div>';
				} else {
					$adcontent = $adcode;
				}
			}
		}
	}
	$adfunc = 'ad_'.$params[0];
	$_G['setting']['pluginhooks'][$adfunc] = null;
	hookscript('ad', 'global', 'funcs', array('params' => $params, 'content' => $adcontent), $adfunc);
	hookscript('ad', $_G['basescript'], 'funcs', array('params' => $params, 'content' => $adcontent), $adfunc);
	return $_G['setting']['pluginhooks'][$adfunc] === null ? $adcontent : $_G['setting']['pluginhooks'][$adfunc];
}

function showmessage($key, $url_forward = '', $values = array(), $extraparam = array(), $custom = 0) {
	
	/*$vars = explode(':', $key);
	if(count($vars) == 2) {
		$message = lang('plugin/'.$vars[0], $vars[1], $values);
	} else {
		$message = lang('message', $key, $values);
	}
	
	if(CHARSET != 'utf-8') {
		$message = gbktoutf8($message);
	}
	
	jsonexit("{'status':'$message'}");*/
}

function submitcheck($var, $allowget = 0, $seccodecheck = 0, $secqaacheck = 0) {

	if(!getgpc($var)) {
		return FALSE;
	} else {
		global $_G;
		if(!empty($_G['gp_mobiledata'])) {
			require_once libfile('class/mobiledata');
			$mobiledata = new mobiledata();
			if($mobiledata->validator()) {
				return TRUE;
			}
		}
		
		if( RPC_DIR || $allowget || ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_G['gp_formhash']) && $_G['gp_formhash'] == formhash() && empty($_SERVER['HTTP_X_FLASH_VERSION']) && (empty($_SERVER['HTTP_REFERER']) ||
		preg_replace("/https?:\/\/([^\:\/]+).*/i", "\\1", $_SERVER['HTTP_REFERER']) == preg_replace("/([^\:]+).*/", "\\1", $_SERVER['HTTP_HOST'])))) {
			if(checkperm('seccode')) {
				if($secqaacheck && !check_secqaa($_G['gp_secanswer'], $_G['gp_sechash'])) {
					showmessage('submit_secqaa_invalid');
				}
				if($seccodecheck && !check_seccode($_G['gp_seccodeverify'], $_G['gp_sechash'])) {
					showmessage('submit_seccode_invalid');
				}
			}
			return TRUE;
		} else {
			showmessage('submit_invalid');
		}
	}
}

function multi($num, $perpage, $curpage, $mpurl, $maxpages = 0, $page = 10, $autogoto = FALSE, $simple = FALSE) {
	global $_G;
	$ajaxtarget = !empty($_G['gp_ajaxtarget']) ? " ajaxtarget=\"".htmlspecialchars($_G['gp_ajaxtarget'])."\" " : '';

	$a_name = '';
	if(strpos($mpurl, '#') !== FALSE) {
		$a_strs = explode('#', $mpurl);
		$mpurl = $a_strs[0];
		$a_name = '#'.$a_strs[1];
	}

	if(defined('IN_ADMINCP')) {
		$shownum = $showkbd = TRUE;
		$lang['prev'] = '&lsaquo;&lsaquo;';
		$lang['next'] = '&rsaquo;&rsaquo;';
	} else {
		$shownum = $showkbd = FALSE;
		if(defined('IN_MOBILE') && !defined('TPL_DEFAULT')) {
			$lang['prev'] = lang('core', 'prevpage');
			$lang['next'] = lang('core', 'nextpage');
		} else {
			$lang['prev'] = '&nbsp;&nbsp;';
			$lang['next'] = lang('core', 'nextpage');
		}
	}
	if(defined('IN_MOBILE') && !defined('TPL_DEFAULT')) {
		$dot = '..';
		$page = intval($page) < 10 && intval($page) > 0 ? $page : 4 ;
	} else {
		$dot = '...';
	}
	$multipage = '';
	
	$mpurl .= strpos($mpurl, '?') !== FALSE ? '&amp;' : '?';

	$realpages = 1;
	$_G['page_next'] = 0;
	$page -= strlen($curpage) - 1;
	if($page <= 0) {
		$page = 1;
	}
	
	$_G["zywy_total"] = $num;
	$_G["zywy_curpage"] = 1;
	$_G["zywy_totalpage"] = 1;
	
	if($num > $perpage) {

		$offset = floor($page * 0.5);

		$realpages = @ceil($num / $perpage);
		$pages = $maxpages && $maxpages < $realpages ? $maxpages : $realpages;
		
		//????
		$first = $mpurl.'page=1';
		//????
		$prev = $mpurl.'page='. (($curpage > 1) ? ($curpage -1) : 1);
		//????
		$next = $mpurl.'page='. (($pages > $curpage) ? ($curpage+1) : $curpage);
		//??????
		$last = $mpurl.'page='.$pages;
		
		/*MB add start*/
 		$_G["zywy_curpage"] = $curpage;
		$_G["zywy_totalpage"] = $pages;
 		/*MB add end*/
		
		$multipage = '<div class="daohang">
		<a class="left1" href="'.$first.'"></a>
		<a class="left2" href="'.$prev.'"></a>
		<span>'.$curpage.'/'.$pages.'</span>
		<a class="right1" href="'.$next.'"></a>
		<a class="right2" href="'.$last.'"></a>
		<div class="clear"></div>
		</div>';
	}

	return $multipage;
}

function simplepage($num, $perpage, $curpage, $mpurl) {
	/*MB add start*/
	global $_G;
 	$_G["zywy_curpage"] = $curpage;
	$_G["zywy_totalpage"] = 1;
 	/*MB add end*/
	$return = '';
	$lang['next'] = lang('core', 'nextpage');
	$lang['prev'] = lang('core', 'prevpage');
	$next = $num == $perpage ? '<a href="'.$mpurl.'&amp;page='.($curpage + 1).'" class="nxt">'.$lang['next'].'</a>' : '';
	$prev = $curpage > 1 ? '<span class="pgb"><a href="'.$mpurl.'&amp;page='.($curpage - 1).'">'.$lang['prev'].'</a></span>' : '';
	if($next || $prev) {
		$return = '<div class="pg">'.$prev.$next.'</div>';
	}
	return $return;
}

//?ж??????????
function censor($message, $modword = NULL, $return = FALSE) {
	global $_G;
	require_once libfile('class/censor');
	$censor = discuz_censor::instance();
	$censor->check($message, $modword);
	if($censor->modbanned()) {
		$wordbanned = implode(', ', $censor->words_found);
		if($return) {
			return array('message' => lang('message', 'word_banned', array('wordbanned' => $wordbanned)));
		}
			
		$msg = lang('message', 'word_banned');
		
		//?ж???????????utf8???????
		if( !preg_match("/([u4e00-u9fa5])/", $msg) ) {
			$msg = gbktoutf8($msg);
		}
		
		jsonexit("{\"message\":\"$msg\"}");
	}

	return $message;
}

function censormod($message) {
	global $_G;
	if($_G['group']['ignorecensor']) {
		return false;
	}
	$modposturl = false;
	if($_G['group']['allowposturl'] == 1) {
		$urllist = get_url_list($message);
		if(is_array($urllist[1])) foreach($urllist[1] as $key => $val) {
			if(!$val = trim($val)) continue;
			if(!iswhitelist($val)) {
				$modposturl = true;
			}
		}
	}
	if($modposturl) {
		return true;
	}

	require_once libfile('class/censor');
	$censor = discuz_censor::instance();
	$censor->check($message);
	return $censor->modmoderated();
}

function space_merge(&$values, $tablename) {
	global $_G;

	$uid = empty($values['uid'])?$_G['uid']:$values['uid'];
	$var = "member_{$uid}_{$tablename}";
	if($uid) {
		if(!isset($_G[$var])) {
			$query = DB::query("SELECT * FROM ".DB::table('common_member_'.$tablename)." WHERE uid='$uid'");
			if($_G[$var] = DB::fetch($query)) {
				if($tablename == 'field_home') {
					$_G['setting']['privacy'] = empty($_G['setting']['privacy']) ? array() : (is_array($_G['setting']['privacy']) ? $_G['setting']['privacy'] : unserialize($_G['setting']['privacy']));
					$_G[$var]['privacy'] = empty($_G[$var]['privacy'])? array() : is_array($_G[$var]['privacy']) ? $_G[$var]['privacy'] : unserialize($_G[$var]['privacy']);
					foreach (array('feed','view','profile') as $pkey) {
						if(empty($_G[$var]['privacy'][$pkey]) && !isset($_G[$var]['privacy'][$pkey])) {
							$_G[$var]['privacy'][$pkey] = isset($_G['setting']['privacy'][$pkey]) ? $_G['setting']['privacy'][$pkey] : array();
						}
					}
					$_G[$var]['acceptemail'] = empty($_G[$var]['acceptemail'])? array() : unserialize($_G[$var]['acceptemail']);
					if(empty($_G[$var]['acceptemail'])) {
						$_G[$var]['acceptemail'] = empty($_G['setting']['acceptemail'])?array():unserialize($_G['setting']['acceptemail']);
					}
				}
			} else {
				DB::insert('common_member_'.$tablename, array('uid'=>$uid));
				$_G[$var] = array();
			}
		}
		$values = array_merge($values, $_G[$var]);
	}
}

function runlog($file, $message, $halt=0) {
	global $_G;

	$nowurl = $_SERVER['REQUEST_URI']?$_SERVER['REQUEST_URI']:($_SERVER['PHP_SELF']?$_SERVER['PHP_SELF']:$_SERVER['SCRIPT_NAME']);
	$log = dgmdate($_G['timestamp'], 'Y-m-d H:i:s')."\t".$_G['clientip']."\t$_G[uid]\t{$nowurl}\t".str_replace(array("\r", "\n"), array(' ', ' '), trim($message))."\n";
	writelog($file, $log);
	if($halt) {
		exit();
	}
}

function stripsearchkey($string) {
	$string = trim($string);
	$string = str_replace('*', '%', addcslashes($string, '%_'));
	$string = str_replace('_', '\_', $string);
	return $string;
}

function dmkdir($dir, $mode = 0777, $makeindex = TRUE){
	if(!is_dir($dir)) {
		dmkdir(dirname($dir));
		@mkdir($dir, $mode);
		if(!empty($makeindex)) {
			@touch($dir.'/index.html'); @chmod($dir.'/index.html', 0777);
		}
	}
	return true;
}

function dreferer($default = '') {
	global $_G;

	$default = empty($default) ? $GLOBALS['_t_curapp'] : '';
	$_G['referer'] = !empty($_G['gp_referer']) ? $_G['gp_referer'] : $_SERVER['HTTP_REFERER'];
	$_G['referer'] = substr($_G['referer'], -1) == '?' ? substr($_G['referer'], 0, -1) : $_G['referer'];

	if(strpos($_G['referer'], 'member.php?mod=logging')) {
		$_G['referer'] = $default;
	}
	$_G['referer'] = htmlspecialchars($_G['referer']);
	$_G['referer'] = str_replace('&amp;', '&', $_G['referer']);
	return strip_tags($_G['referer']);
}

function ftpcmd($cmd, $arg1 = '') {
	static $ftp;
	$ftpon = getglobal('setting/ftp/on');
	if(!$ftpon) {
		return $cmd == 'error' ? -101 : 0;
	} elseif($ftp == null) {
		require_once libfile('class/ftp');
		$ftp = & discuz_ftp::instance();
	}
	if(!$ftp->enabled) {
		return $ftp->error();
	} elseif($ftp->enabled && !$ftp->connectid) {
		$ftp->connect();
	}
	switch ($cmd) {
		case 'upload' : return $ftp->upload(getglobal('setting/attachdir').'/'.$arg1, $arg1); break;
		case 'delete' : return $ftp->ftp_delete($arg1); break;
		case 'close'  : return $ftp->ftp_close(); break;
		case 'error'  : return $ftp->error(); break;
		case 'object' : return $ftp; break;
		default       : return false;
	}

}

function diconv($str, $in_charset, $out_charset = CHARSET, $ForceTable = FALSE) {
	global $_G;

	$in_charset = strtoupper($in_charset);
	$out_charset = strtoupper($out_charset);

	if(empty($str) || $in_charset == $out_charset) {
		return $str;
	}

	$out = '';

	if(!$ForceTable) {
		if(function_exists('iconv')) {
			$out = iconv($in_charset, $out_charset.'//IGNORE', $str);
		} elseif(function_exists('mb_convert_encoding')) {
			$out = mb_convert_encoding($str, $out_charset, $in_charset);
		}
	}

	if($out == '') {
		require_once libfile('class/chinese');
		$chinese = new Chinese($in_charset, $out_charset, true);
		$out = $chinese->Convert($str);
	}

	return $out;
}

function renum($array) {
	$newnums = $nums = array();
	foreach ($array as $id => $num) {
		$newnums[$num][] = $id;
		$nums[$num] = $num;
	}
	return array($nums, $newnums);
}

function getonlinenum($fid = 0, $tid = 0) {
	if($fid) {
		$sql = " AND fid='$fid'";
	}
	if($tid) {
		$sql = " AND tid='$tid'";
	}
	return DB::result_first('SELECT count(*) FROM '.DB::table("common_session")." WHERE 1 $sql");
}

function sizecount($size) {
	if($size >= 1073741824) {
		$size = round($size / 1073741824 * 100) / 100 . ' GB';
	} elseif($size >= 1048576) {
		$size = round($size / 1048576 * 100) / 100 . ' MB';
	} elseif($size >= 1024) {
		$size = round($size / 1024 * 100) / 100 . ' KB';
	} else {
		$size = $size . ' Bytes';
	}
	return $size;
}

function swapclass($class1, $class2 = '') {
	static $swapc = null;
	$swapc = isset($swapc) && $swapc != $class1 ? $class1 : $class2;
	return $swapc;
}

function writelog($file, $log) {
	global $_G;
	$yearmonth = dgmdate(TIMESTAMP, 'Ym', $_G['setting']['timeoffset']);
	$logdir = DISCUZ_ROOT.'./data/log/';
	$logfile = $logdir.$yearmonth.'_'.$file.'.php';
	if(@filesize($logfile) > 2048000) {
		$dir = opendir($logdir);
		$length = strlen($file);
		$maxid = $id = 0;
		while($entry = readdir($dir)) {
			if(strpos($entry, $yearmonth.'_'.$file) !== false) {
				$id = intval(substr($entry, $length + 8, -4));
				$id > $maxid && $maxid = $id;
			}
		}
		closedir($dir);

		$logfilebak = $logdir.$yearmonth.'_'.$file.'_'.($maxid + 1).'.php';
		@rename($logfile, $logfilebak);
	}
	if($fp = @fopen($logfile, 'a')) {
		@flock($fp, 2);
		$log = is_array($log) ? $log : array($log);
		foreach($log as $tmp) {
			fwrite($fp, "<?PHP exit;?>\t".str_replace(array('<?', '?>'), '', $tmp)."\n");
		}
		fclose($fp);
	}
}
function getcolorpalette($colorid, $id, $background, $fun = '') {
	return "<input id=\"c$colorid\" onclick=\"c{$colorid}_frame.location='static/image/admincp/getcolor.htm?c{$colorid}|{$id}|{$fun}';showMenu({'ctrlid':'c$colorid'})\" type=\"button\" class=\"colorwd\" value=\"\" style=\"background: $background\"><span id=\"c{$colorid}_menu\" style=\"display: none\"><iframe name=\"c{$colorid}_frame\" src=\"\" frameborder=\"0\" width=\"210\" height=\"148\" scrolling=\"no\"></iframe></span>";
}

function getstatus($status, $position) {
	$t = $status & pow(2, $position - 1) ? 1 : 0;
	return $t;
}

function setstatus($position, $value, $baseon = null) {
	$t = pow(2, $position - 1);
	if($value) {
		$t = $baseon | $t;
	} elseif ($baseon !== null) {
		$t = $baseon & ~$t;
	} else {
		$t = ~$t;
	}
	return $t & 0xFFFF;
}

function notification_add($touid, $type, $note, $notevars = array(), $system = 0) {
	global $_G;

	$tospace = array('uid'=>$touid);
	space_merge($tospace, 'field_home');
	$filter = empty($tospace['privacy']['filter_note'])?array():array_keys($tospace['privacy']['filter_note']);

	if($filter && (in_array($type.'|0', $filter) || in_array($type.'|'.$_G['uid'], $filter))) {
		return false;
	}

	$notevars['actor'] = "<a href=\"home.php?mod=space&uid=$_G[uid]\">".$_G['member']['username']."</a>";
	if(!is_numeric($type)) {
		$vars = explode(':', $note);
		if(count($vars) == 2) {
			$notestring = lang('plugin/'.$vars[0], $vars[1], $notevars);
		} else {
			$notestring = lang('notification', $note, $notevars);
		}
		$frommyapp = false;
	} else {
		$frommyapp = true;
		$notestring = $note;
	}

	$oldnote = array();
	if($notevars['from_id'] && $notevars['from_idtype']) {
		$oldnote = DB::fetch_first("SELECT * FROM ".DB::table('home_notification')."
			WHERE from_id='$notevars[from_id]' AND from_idtype='$notevars[from_idtype]' AND uid='$touid'");
	}
	if(empty($oldnote['from_num'])) $oldnote['from_num'] = 0;
	$notevars['from_num'] = $notevars['from_num'] ? $notevars['from_num'] : 1;
	$setarr = array(
		'uid' => $touid,
		'type' => $type,
		'new' => 1,
		'authorid' => $_G['uid'],
		'author' => $_G['username'],
		'note' => addslashes($notestring),
		'dateline' => $_G['timestamp'],
		'from_id' => $notevars['from_id'],
		'from_idtype' => $notevars['from_idtype'],
		'from_num' => ($oldnote['from_num']+$notevars['from_num'])
	);
	if($system) {
		$setarr['authorid'] = 0;
		$setarr['author'] = '';
	}

	if($oldnote['id']) {
		DB::update('home_notification', $setarr, array('id'=>$oldnote['id']));
	} else {
		$oldnote['new'] = 0;
		DB::insert('home_notification', $setarr);
	}

	if(empty($oldnote['new'])) {
		DB::query("UPDATE ".DB::table('common_member')." SET newprompt=newprompt+1 WHERE uid='$touid'");

		require_once libfile('function/mail');
		$mail_subject = lang('notification', 'mail_to_user');
		sendmail_touser($touid, $mail_subject, $notestring, $frommyapp ? 'myapp' : $type);
	}

	if(!$system && $_G['uid'] && $touid != $_G['uid']) {
		DB::query("UPDATE ".DB::table('home_friend')." SET num=num+1 WHERE uid='$_G[uid]' AND fuid='$touid'");
	}
}

function manage_addnotify($type, $from_num = 0, $langvar = array()) {
	global $_G;
	$notifyusers = unserialize($_G['setting']['notifyusers']);
	$notifytypes = explode(',', $_G['setting']['adminnotifytypes']);
	$notifytypes = array_flip($notifytypes);
	$notearr = array('from_id' => 1,'from_idtype' => $type, 'from_num' => $from_num);
	if($langvar) {
		$langkey = $langvar['langkey'];
		$notearr = array_merge($notearr, $langvar);
	} else {
		$langkey = 'manage_'.$type;
	}
	foreach($notifyusers as $uid => $user) {
		if($user['types'][$notifytypes[$type]]) {
			notification_add($uid, $type, $langkey, $notearr, 1);
		}
	}
}

function sendpm($toid, $subject, $message, $fromid = '', $replypmid = 0, $isusername = 0, $type = 0) {
	global $_G;
	if($fromid === '') {
		$fromid = $_G['uid'];
	}
	loaducenter();
	return uc_pm_send($fromid, $toid, $subject, $message, 1, $replypmid, $isusername, $type);
}

function g_icon($groupid, $return = 0) {
	global $_G;
	if(empty($_G['cache']['usergroups'][$groupid]['icon'])) {
		$s =  '';
	} else {
		if(substr($_G['cache']['usergroups'][$groupid]['icon'], 0, 5) == 'http:') {
			$s = '<img src="'.$_G['cache']['usergroups'][$groupid]['icon'].'" alt="" class="vm" />';
		} else {
			$s = '<img src="'.$_G['setting']['attachurl'].'common/'.$_G['cache']['usergroups'][$groupid]['icon'].'" alt="" class="vm" />';
		}
	}
	if($return) {
		return $s;
	} else {
		echo $s;
	}
}
function updatediytemplate($targettplname = '') {
	global $_G;
	$r = false;
	$where = empty($targettplname) ? '' : " WHERE targettplname='$targettplname'";
	$query = DB::query("SELECT * FROM ".DB::table('common_diy_data')."$where");
	require_once libfile('function/portalcp');
	while($value = DB::fetch($query)) {
		$r = save_diy_data($value['primaltplname'], $value['targettplname'], unserialize($value['diycontent']));
	}
	return $r;
}

function space_key($uid, $appid=0) {
	global $_G;

	$siteuniqueid = DB::result_first("SELECT svalue FROM ".DB::table('common_setting')." WHERE skey='siteuniqueid'");
	return substr(md5($siteuniqueid.'|'.$uid.(empty($appid)?'':'|'.$appid)), 8, 16);
}


function getposttablebytid($tids, $primary = 0) {
	global $_G;

	$isstring = false;
	if(!is_array($tids)) {
		$tids = array(intval($tids));
		$isstring = true;
	}
	$tids = array_unique($tids);
	$tids = array_flip($tids);
	if(!$primary) {
		loadcache('threadtableids');
		$threadtableids = !empty($_G['cache']['threadtableids']) ? $_G['cache']['threadtableids'] : array();
		if(!in_array(0, $threadtableids)) {
			$threadtableids = array_merge(array(0), $threadtableids);
		}
	} else {
		$threadtableids = array(0);
	}
	$tables = array();
	$posttable = '';
	$singletable = count($tids) > 1 ? false : true;
	foreach($threadtableids as $tableid) {
		$threadtable = $tableid ? "forum_thread_$tableid" : 'forum_thread';
		$query = DB::query("SELECT tid, posttableid FROM ".DB::table($threadtable)." WHERE tid IN(".dimplode(array_keys($tids)).")");
		while ($value = DB::fetch($query)) {
			$posttable = 'forum_post'.($value['posttableid'] ? "_$value[posttableid]" : '');
			$tables[$posttable][$value['tid']] = $value['tid'];
			unset($tids[$value['tid']]);
		}
		if(!count($tids)) {
			break;
		}
	}
	if(empty($posttable)) {
		$posttable = 'forum_post';
		$tables[$posttable] = array_flip($tids);
	}
	return $isstring ? $posttable : $tables;
}

function getposttable($tableid = 0, $prefix = false) {
	global $_G;
	$tableid = intval($tableid);
	if($tableid) {
		loadcache('posttableids');
		$tableid = $_G['cache']['posttableids'] && in_array($tableid, $_G['cache']['posttableids']) ? $tableid : 0;
		$tablename = 'forum_post'.($tableid ? "_$tableid" : '');
	} else {
		$tablename = 'forum_post';
	}
	if($prefix) {
		$tablename = DB::table($tablename);
	}
	return $tablename;
}

function memory($cmd, $key='', $value='', $ttl = 0) {
	$discuz = & discuz_core::instance();
	if($cmd == 'check') {
		return  $discuz->mem->enable ? $discuz->mem->type : '';
	} elseif($discuz->mem->enable && in_array($cmd, array('set', 'get', 'rm'))) {
		switch ($cmd) {
			case 'set': return $discuz->mem->set($key, $value, $ttl); break;
			case 'get': return $discuz->mem->get($key); break;
			case 'rm': return $discuz->mem->rm($key); break;
		}
	}
	return null;
}

function ipaccess($ip, $accesslist) {
	return preg_match("/^(".str_replace(array("\r\n", ' '), array('|', ''), preg_quote($accesslist, '/')).")/", $ip);
}

function ipbanned($onlineip) {
	global $_G;

	if($_G['setting']['ipaccess'] && !ipaccess($onlineip, $_G['setting']['ipaccess'])) {
		return TRUE;
	}

	loadcache('ipbanned');
	if(empty($_G['cache']['ipbanned'])) {
		return FALSE;
	} else {
		if($_G['cache']['ipbanned']['expiration'] < TIMESTAMP) {
			require_once libfile('function/cache');
			updatecache('ipbanned');
		}
		return preg_match("/^(".$_G['cache']['ipbanned']['regexp'].")$/", $onlineip);
	}
}

function getcount($tablename, $condition) {
	if(empty($condition)) {
		$where = '1';
	} elseif(is_array($condition)) {
		$where = DB::implode_field_value($condition, ' AND ');
	} else {
		$where = $condition;
	}
	$ret = intval(DB::result_first("SELECT COUNT(*) AS num FROM ".DB::table($tablename)." WHERE $where"));
	return $ret;
}

function sysmessage($message) {
	require libfile('function/sysmessage');
	show_system_message($message);
}

function forumperm($permstr, $groupid = 0) {
	global $_G;

	$groupidarray = array($_G['groupid']);
	if($groupid) {
		return preg_match("/(^|\t)(".$groupid.")(\t|$)/", $permstr);
	}
	foreach(explode("\t", $_G['member']['extgroupids']) as $extgroupid) {
		if($extgroupid = intval(trim($extgroupid))) {
			$groupidarray[] = $extgroupid;
		}
	}
	if($_G['setting']['verify']['enabled']) {
		getuserprofile('verify1');
		foreach($_G['setting']['verify'] as $vid => $verify) {
			if($verify['available'] && $_G['member']['verify'.$vid] == 1) {
				$groupidarray[] = 'v'.$vid;
			}
		}
	}
	return preg_match("/(^|\t)(".implode('|', $groupidarray).")(\t|$)/", $permstr);
}

if(!function_exists('file_put_contents')) {
	if(!defined('FILE_APPEND')) define('FILE_APPEND', 8);
	function file_put_contents($filename, $data, $flag = 0) {
		$return = false;
		if($fp = @fopen($filename, $flag != FILE_APPEND ? 'w' : 'a')) {
			if($flag == LOCK_EX) @flock($fp, LOCK_EX);
			$return = fwrite($fp, is_array($data) ? implode('', $data) : $data);
			fclose($fp);
		}
		return $return;
	}
}

function checkperm($perm) {
	global $_G;

	return (empty($_G['group'][$perm])?'':$_G['group'][$perm]);
}

function periodscheck($periods, $showmessage = 1) {
	global $_G;

	if($_G['setting'][$periods]) {
		$now = date('G.i', TIMESTAMP);

		foreach(explode("\r\n", str_replace(':', '.', $_G['setting'][$periods])) as $period) {
			list($periodbegin, $periodend) = explode('-', $period);			
			if(($periodbegin > $periodend && ($now >= $periodbegin || $now < $periodend)) || ($periodbegin < $periodend && $now >= $periodbegin && $now < $periodend)) {
				$banperiods = str_replace("\r\n", ', ', $_G['setting'][$periods]);
				
				if($showmessage) { 
					$message =  lang('message', 'period_nopermission');
					$message = str_replace('{banperiods}', $banperiods, $message);
					jsonexit("{\"message\":\"$message\"}");
				} else {
					return TRUE;
				}
			}
		}
	}
	return FALSE;
}

function cknewuser($return=0) {
	global $_G;

	$result = true;

	if(!$_G['uid']) return true;

	if(checkperm('disablepostctrl')) {
		return $result;
	}
	$ckuser = $_G['member'];

	if($_G['setting']['newbiespan'] && $_G['timestamp']-$ckuser['regdate']<$_G['setting']['newbiespan']*60) {
		/*if(empty($return)) showmessage('no_privilege_newbiespan', '', array('newbiespan' => $_G['setting']['newbiespan']), array('return' => true));*/
		$msg = preg_replace('/<.*?>.*?<\/.*?>/', '', lang('message', 'no_privilege_newbiespan', array('newbiespan' => $_G['setting']['newbiespan'])));
		
		jsonexit("{\"status\":\"0\",\"message\":\"$msg\"}");
		$result = false;
	}
	/*if($_G['setting']['need_avatar'] && empty($ckuser['avatarstatus'])) {
		if(empty($return)) showmessage('no_privilege_avatar', '', array(), array('return' => true));
		$msg = preg_replace('/<.*?>.*?<\/.*?>|，/', '', lang('message', 'no_privilege_avatar'));
		
		jsonexit("{\"status\":\"0\",\"message\":\"$msg\"}");
		$result = false;
	}*/
	if($_G['setting']['need_email'] && empty($ckuser['emailstatus'])) {
		/*if(empty($return)) showmessage('no_privilege_email', '', array(), array('return' => true));*/
		$msg = preg_replace('/<.*?>.*?<\/.*?>|，/', '', lang('message', 'no_privilege_email'));
		
		jsonexit("{\"status\":\"0\",\"message\":\"$msg\"}");
		$result = false;
	}
	if($_G['setting']['need_friendnum']) {
		space_merge($ckuser, 'count');
		if($ckuser['friends'] < $_G['setting']['need_friendnum']) {
			/*if(empty($return)) showmessage('no_privilege_friendnum', '', array('friendnum' => $_G['setting']['need_friendnum']), array('return' => true));*/
			$msg = preg_replace('/<.*?>.*?<\/.*?>|，/', '', lang('message', 
			'no_privilege_friendnum',array('friendnum' => $_G['setting']['need_friendnum'])));
			
			jsonexit("{\"status\":\"0\",\"message\":\"$msg\"}");
			$result = false;
		}
	}
	return $result;
}

function manyoulog($logtype, $uids, $action, $fid = '') {
	global $_G;

	if($_G['setting']['my_app_status'] && $logtype == 'user') {
		$action = daddslashes($action);
		$values = array();
		$uids = is_array($uids) ? $uids : array($uids);
		foreach($uids as $uid) {
			$uid = intval($uid);
			$values[$uid] = "('$uid', '$action', '".TIMESTAMP."')";
		}
		if($values) {
			DB::query("REPLACE INTO ".DB::table('common_member_log')." (`uid`, `action`, `dateline`) VALUES ".implode(',', $values));
		}
	}
}

function useractionlog($uid, $action) {
	$uid = intval($uid);
	if(empty($uid) || empty($action)) {
		return false;
	}
	$action = getuseraction($action);
	$timestamp = TIMESTAMP;
	DB::query("INSERT INTO ".DB::table('common_member_action_log')." (`uid`, `action`, `dateline`) VALUES ('$uid', '$action', '$timestamp')");
	return true;
}

function getuseraction($var) {
	$value = false;
	$ops = array('tid', 'pid', 'blogid', 'picid', 'doid', 'sid', 'aid', 'uid_cid', 'blogid_cid', 'sid_cid', 'picid_cid', 'aid_cid', 'topicid_cid');
	if(is_numeric($var)) {
		$value = isset($ops[$var]) ? $ops[$var] : false;
	} else {
		$value = array_search($var, $ops);
	}
	return $value;
}

function getuserapp($panel = 0) {
	require_once libfile('function/manyou');
	manyou_getuserapp($panel);
	return true;
}

function getmyappiconpath($appid, $iconstatus=0) {
	if($iconstatus > 0) {
		return getglobal('setting/attachurl').'./'.'myapp/icon/'.$appid.'.jpg';
	}
	return 'http://appicon.manyou.com/icons/'.$appid;
}

function getexpiration() {
	global $_G;
	$date = getdate($_G['timestamp']);
	return mktime(0, 0, 0, $date['mon'], $date['mday'], $date['year']) + 86400;
}

function return_bytes($val) {
    $val = trim($val);
    $last = strtolower($val{strlen($val)-1});
    switch($last) {
        case 'g': $val *= 1024;
        case 'm': $val *= 1024;
        case 'k': $val *= 1024;
    }
    return $val;
}

function get_url_list($message) {
	$return = array();

	(strpos($message, '[/img]') || strpos($message, '[/flash]')) && $message = preg_replace("/\[img[^\]]*\].+?\[\/img\]|\[flash[^\]]*\].+?\[\/flash\]/is", '', $message);
	if(preg_match_all("/((https?|ftp|gopher|news|telnet|rtsp|mms|callto):\/\/|www\.)([a-z0-9\/\-_+=.~!%@?#%&;:$\\()|]+\s*)/i", $message, $urllist)) {
		foreach($urllist[0] as $key => $val) {
			$val = trim($val);
			$return[0][$key] = $val;
			if(!preg_match('/^http:\/\//is', $val)) $val = 'http://'.$val;
			$tmp = parse_url($val);
			$return[1][$key] = $tmp['host'];
			if($tmp['port']){
				$return[1][$key] .= ":$tmp[port]";
			}
		}
	}

	return $return;
}

function iswhitelist($host) {
	global $_G;
	static $iswhitelist = array();

	if(isset($iswhitelist[$host])) {
		return $iswhitelist[$host];
	}
	$hostlen = strlen($host);
	$iswhitelist[$host] = false;
	if(is_array($_G['cache']['domainwhitelist'])) foreach($_G['cache']['domainwhitelist'] as $val) {
		$domainlen = strlen($val);
		if($domainlen > $hostlen) {
			continue;
		}
		if(substr($host, -$domainlen) == $val) {
			$iswhitelist[$host] = true;
			break;
		}
	}
	if($iswhitelist[$host] == false) {
		$iswhitelist[$host] = $host == $_SERVER['HTTP_HOST'];
	}
	return $iswhitelist[$host];
}

function update_template_block($targettplname, $blocks) {
	if(!empty($blocks) && !empty($targettplname)) {
		$oldbids = array();
		$query = DB::query('SELECT bid FROM '.DB::table('common_template_block')." WHERE targettplname='$targettplname'");
		while($value = DB::fetch($query)) {
			$oldbids[] = $value['bid'];
		}
		$newaddbids = array_diff($blocks, $oldbids);
		DB::delete('common_template_block', array('targettplname'=>$targettplname));
		$values = array();
		foreach ($blocks as $bid) {
			$values[] = "('$targettplname','$bid')";
		}
		if (!empty($values)) {
			DB::query("INSERT INTO ".DB::table('common_template_block')." (targettplname,bid) VALUES ".implode(',', $values));
		}
		if(!empty($newaddbids)) {
			require_once libfile('class/blockpermission');
			$tplpermission = & template_permission::instance();
			$tplpermission->add_blocks($targettplname, $newaddbids);
		}
	}
}

if(!function_exists('http_build_query')) {
	function http_build_query($data, $numeric_prefix='', $arg_separator='', $prefix='') {
		$render = array();
		if (empty($arg_separator)) {
			$arg_separator = ini_get('arg_separator.output');
			empty($arg_separator) && $arg_separator = '&';
		}
		foreach ((array) $data as $key => $val) {
			if (is_array($val) || is_object($val)) {
				$_key = empty($prefix) ? "{$key}[%s]" : sprintf($prefix, $key) . "[%s]";
				$_render = http_build_query($val, '', $arg_separator, $_key);
				if (!empty($_render)) {
					$render[] = $_render;
				}
			} else {
				if (is_numeric($key) && empty($prefix)) {
					$render[] = urlencode("{$numeric_prefix}{$key}") . "=" . urlencode($val);
				} else {
					if (!empty($prefix)) {
						$_key = sprintf($prefix, $key);
						$render[] = urlencode($_key) . "=" . urlencode($val);
					} else {
						$render[] = urlencode($key) . "=" . urlencode($val);
					}
				}
			}
		}
		$render = implode($arg_separator, $render);
		if (empty($render)) {
			$render = '';
		}
		return $render;
	}
}

function getrelatedlink($extent) {
	global $_G;
	loadcache('relatedlink');
	$allextent = array('article' => 0, 'forum' => 1, 'group' => 2, 'blog' => 3);
	$links = array();
	if($_G['cache']['relatedlink'] && isset($allextent[$extent])) {
		foreach($_G['cache']['relatedlink'] as $link) {
			$link['extent'] = sprintf('%04b', $link['extent']);
			if($link['extent'][$allextent[$extent]] && $link['name'] && $link['url']) {
				$links[] = daddslashes($link);
			}
		}
	}
	return $links;
}

function getattachtablebyaid($aid) {
	$tableid = DB::result_first("SELECT tableid FROM ".DB::table('forum_attachment')." WHERE aid='$aid'");
	return 'forum_attachment_'.($tableid >= 0 && $tableid < 10 ? intval($tableid) : 'unused');
}

function getattachtableid($tid) {
	$tid = (string)$tid;
	return intval($tid{strlen($tid)-1});
}

function getattachtablebytid($tid) {
	return 'forum_attachment_'.getattachtableid($tid);
}

function getattachtablebypid($pid) {
	$tableid = DB::result_first("SELECT tableid FROM ".DB::table('forum_attachment')." WHERE pid='$pid' LIMIT 1");
	return 'forum_attachment_'.($tableid >= 0 && $tableid < 10 ? intval($tableid) : 'unused');
}

function getattachnewaid($uid = 0) {
	global $_G;
	$uid = !$uid ? $_G['uid'] : $uid;
	return DB::insert('forum_attachment', array('tid' => 0, 'pid' => 0, 'uid' => $uid, 'tableid' => 127), true);
}

function get_seosetting($page, $data = array(), $defset = array()) {
	global $_G;
	$searchs = array('{bbname}');
	$replaces = array($_G['setting']['bbname']);

	$seotitle = $seodescription = $seokeywords = '';
	$titletext = $defset['seotitle'] ? $defset['seotitle'] : $_G['setting']['seotitle'][$page];
	$descriptiontext = $defset['seodescription'] ? $defset['seodescription'] : $_G['setting']['seodescription'][$page];
	$keywordstext = $defset['seokeywords'] ? $defset['seokeywords'] : $_G['setting']['seokeywords'][$page];
	preg_match_all("/\{([a-z0-9_-]+?)\}/", $titletext.$descriptiontext.$keywordstext, $pageparams);
	if($pageparams) {
		foreach($pageparams[1] as $var) {
			$searchs[] = '{'.$var.'}';
			if($var == 'page') {
				$data['page'] = $data['page'] > 1 ? lang('core', 'page', array('page' => $data['page'])) : '';
			}
			$replaces[] = $data[$var] ? strip_tags($data[$var]) : '';
		}
		if($titletext) {
			$seotitle = strreplace_strip_split($searchs, $replaces, $titletext);
		}
		if($descriptiontext && (CURSCRIPT == 'forum' || IS_ROBOT || $_G['adminid'] == 1)) {
			$seodescription = strreplace_strip_split($searchs, $replaces, $descriptiontext);
		}
		if($keywordstext && (CURSCRIPT == 'forum' || IS_ROBOT || $_G['adminid'] == 1)) {
			$seokeywords = strreplace_strip_split($searchs, $replaces, $keywordstext);
		}
	}
	return array($seotitle, $seodescription, $seokeywords);
}


function strreplace_strip_split($searchs, $replaces, $str) {
	$searchspace = array('((\s*\-\s*)+)', '((\s*\,\s*)+)', '((\s*\|\s*)+)', '((\s*\t\s*)+)', '((\s*_\s*)+)');
	$replacespace = array('-', ',', '|', ' ', '_');
	return trim(preg_replace($searchspace, $replacespace, str_replace($searchs, $replaces, $str)), ' ,-|_');
}

function get_title_page($navtitle, $page){
	if($page > 1) {
		$navtitle .= ' - '.lang('core', 'page', array('page' => $page));
	}
	return $navtitle;
}
function getimgthumbname($fileStr, $extend='.thumb.jpg', $holdOldExt=true) {
	if(empty($fileStr)) {
		return '';
	}
	if(!$holdOldExt) {
		$fileStr = substr($fileStr, 0, strrpos($fileStr, '.'));
	}
	$extend = strstr($extend, '.') ? $extend : '.'.$extend;
	return $fileStr.$extend;
}

function updatemoderate($idtype, $ids, $status = 0) {
	global $_G;
	$ids = is_array($ids) ? $ids : array($ids);
	if(!$ids) {
		return;
	}
	if(!$status) {
		foreach($ids as $id) {
			DB::insert('common_moderate', array('id' => $id, 'idtype' => $idtype, 'status' => 0, 'dateline' => $_G['timestamp']), false, true);
		}
	} elseif($status == 1) {
		DB::update('common_moderate', array('status' => 1), "id IN (".dimplode($ids).") AND idtype='$idtype'");
	} elseif($status == 2) {
		DB::delete('common_moderate', "id IN (".dimplode($ids).") AND idtype='$idtype'");
	}
}

function userappprompt() {
	global $_G;

	if($_G['setting']['my_app_status'] && $_G['setting']['my_openappprompt'] && empty($_G['cookie']['userappprompt'])) {
		$sid = $_G['setting']['my_siteid'];
		$ts = $_G['timestamp'];
		$key = md5($sid.$ts.$_G['setting']['my_sitekey']);
		$uchId = $_G['uid'] ? $_G['uid'] : 0;
		echo '<script type="text/javascript" src="http://notice.uchome.manyou.com/notice/userNotice?sId='.$sid.'&ts='.$ts.'&key='.$key.'&uchId='.$uchId.'" charset="UTF-8"></script>';
	}
}

//附件处理
if(!function_exists('attachinpost')) {
	function attachinpost($attach) {
		global $_G;

		//如果不为图片
		if(!$attach['isimage']) {	
			return '<a href="'.$_G['siteurl'].'forum.php?mod=attachment&aid='.aidencode($attach['aid']).'">'
			.$attach['filename'].'</a>';
		}

		//如果为远程FTP图片
		if($attach['remote']) {
			$imgurl = $_G['siteurl'].'source/plugin/zywx/rpc/forum.php?mod=image&src='.$_G['setting']['ftp']['attachurl'].'forum/'.$attach['attachment'].
				($_G['gp_width']?'&width='.$_G['gp_width']:'').
				($_G['gp_height']?'&height='.$_G['gp_height']:'');

			
			return '<p><img onclick="viewimage(this);" src="'.$imgurl.'" /></p>';
		} 
		//不为远程
		else {
			if(preg_match('/^(?!http:)/', $attach['url'])) {
				$attach['url'] = $_G['siteurl'].$attach['url'];
			}
			$imgurl = $_G['siteurl'].'source/plugin/zywx/rpc/forum.php?mod=image&src='.$attach['url'].$attach['attachment'].
				($_G['gp_width']?'&width='.$_G['gp_width']:'').
				($_G['gp_height']?'&height='.$_G['gp_height']:'');
			
			return '<p><img onclick="viewimage(this);" src="'.$imgurl.'" /></p>';
			
			
		}
	}
}

//ubb代码转换成附件
function parse_attach($matches,$tableID = '',$isArray = TRUE) {
	global $_G;
	if($isArray){
		$aid = $matches[1];
	}else{
		$aid = $matches;
	}
	if(empty($tableID)){
		$tableid = DB::getOne("SELECT tableid FROM ".DB::table('forum_attachment')." WHERE aid='$aid'");
	}else{
		$tableid = $tableID;
	}
	$attach = DB::getRow("SELECT filename, attachment, isimage, remote, width FROM ".DB::table('forum_attachment').'_'.$tableid." WHERE aid='$aid'");
	$attach['aid'] = $aid;
	$attach['url'] = ($attach['remote'] ? $_G['setting']['ftp']['attachurl'] : $_G['setting']['attachurl']).'forum/';

	return attachinpost($attach);
}

function imagelist($attach, $firstpost = 0) {
	return attachinpost($attach);
}

//通过正则匹配，返回匹配值
function  preg_parse($pattern, $string) {
	preg_match("#$pattern#", $string, $matches);
	return $matches[1];
}

//跟据大图URL返回缩略图图片标签
function  zy_parse_img($matches) {
	global $_G;
	$img = urldecode($matches[2]);
	$imgcount = preg_match_all('/http:\/\//', $img, $m);

	if($imgcount > 1) {
		$str = array_pop(explode('http://', $img));
		if($str) $img = 'http://'.$str;
	} elseif($imgcount == 0) {
		$img = $_G['siteurl'].$img;
	}
	/**
	 * Update for 针对表情不压缩
	 * @copyright  2011-2012 Bei Jing Zheng Yi Wireless
	 * @since      File available since Release 1.0 -- 2012-3-16 上午11:58:54
	 * @author author
	 * 
	 */
	 
	if(preg_match('/static\/image/', $img)) {
		return '<img src="'.$img.'">';
	} else {
		return '<p><img src="'.$_G['siteurl']."source/plugin/zywx/rpc/forum.php?mod=image{$imgarg}&src=".$img.'" onload="if(this.width>100){this.width=100}" onclick="viewimage(this);"\><p/>';
	}

}

function ismanager($fid) {
	
	$uid = $GLOBALS['_G']['uid'];
	
	//版主
	$moderators = DB::getOne("SELECT moderators FROM ".DB::table('forum_forumfield')." 
							   WHERE fid='$fid'");
	
	//管理员
	$adminid = DB::getOne("SELECT adminid FROM ".DB::table('common_member')." 
						   WHERE uid='$uid'");
		
	if($moderators) {
		$moderators = explode("\t", $moderators);
	}
		
	if((in_array($GLOBALS['_G']['username'], $moderators)) || $adminid==1) {
		return true;
	} else {
		return false;
	}
}

/**
 * 根据经纬度反向解析地址(By Google Map API)
 * 
 * @param string $x lng
 * @param string $y lat
 * @param int $level
*/
function get_address_by_location($x,$y){
    $res = json_decode(file_get_contents("http://ditu.google.cn/maps/geo?q=$y,$x&output=json"));
    if($res->Status->code==200) {
        $res_address = $res->Placemark;
		if(CHARSET == 'gbk') {
			$res_address[0]->address = diconv($res_address[0]->address, 'utf-8', 'gbk');
		}
		
		$over = strpos($res_address[0]->address, ' ');
		if($over) {
			$res_address[0]->address = substr($res_address[0]->address, 0, $over);
		}
		
        return $res_address[0]->address;
    } else {
        return;
    }
}

/**
 * 根据键名加载缓存
 * @param   string  $name    键名
 * @param   int     $expiry  缓存过期时间，默认为一小时
 * @param   mixed   $limit   是否受过期限制
 */
function zy_loadcache($name, $expiry=3600, $limit=true) {
	$cache = DB::getRow('SELECT cachevalue, dateline FROM '.DB::table("common_cache")." WHERE cachekey='$name'");
	if(empty($cache) || ($limit && (TIMESTAMP-$expiry) > $cache['dateline'])) return;
	return unserialize($cache['cachevalue']);
}

/**
 * 缓存保存到数据库
 * @param   string  $name    键名
 * @param   mixed   $data    缓存内容
 */
function zy_savecache($name, $data) {
	DB::insert('common_cache', array(
		'cachekey' => $name,
		'cachevalue' => serialize($data),
		'dateline' => TIMESTAMP
	), false, true);
}

function is_image_ext($ext) {
	static $imgext  = array('jpg', 'jpeg', 'gif', 'png', 'bmp');
	return in_array($ext, $imgext) ? 1 : 0;
}


if(!function_exists('tpl_quote')) {
    function tpl_quote($matches) {
		
		$quote = strip_tags($matches[0]);
		$quote = preg_replace('/\[img\].*?\[\/img\]/', '', $quote);
		$quote = preg_replace('/\[.*?\]/', '', $quote);
		
		$quote = '<p class="quote"><span class="q1"></span>'.$quote.'<span class="q2"></span></p>';
		
		return $quote;
	}
}

if(!function_exists('tpl_codedisp')) {
    function tpl_codedisp() {
		return '';
	}
}

if(!function_exists('tpl_free')) {
    function tpl_free() {
		return '\\1';
	}
}

if(!function_exists('tpl_hide_reply')) {
    function tpl_hide_reply() {
		return '';
	}
}

if(!function_exists('tpl_hide_reply_hidden')) {
    function tpl_hide_reply_hidden() {
		return '';
	}
}

if(!function_exists('tpl_hide_credits_hidden')) {
    function tpl_hide_credits_hidden() {
		return '';
	}
}

//获取地理位置 distance
function geo_distance($s, $e) {
	//earth's mean radius in KM
	$r = 6378.137;
	$s[0] = deg2rad($s[0]);
	$s[1] = deg2rad($s[1]);
	$e[0] = deg2rad($e[0]);
	$e[1] = deg2rad($e[1]);

	$d0 = abs($s[0] - $e[0]);
	$d1 = abs($s[1] - $e[1]);

	$p = pow(sin($d0/2), 2) + cos($s[0]) * cos($e[0]) * pow(sin($d1/2), 2);

	$ds = $r * 2 * asin(sqrt($p));

	return $ds*1000;
}

function get_url_contents($url) {
	if(ini_get('allow_url_fopen')) {
		return file_get_contents($url);
	} elseif(function_exists('curl_init')) {
		$ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($ch);
        curl_close($ch);
		return($res);
	} else {
		return dfsockopen($url);
	}	
}

?>