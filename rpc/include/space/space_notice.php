<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: space_notice.php 22054 2011-04-20 10:51:36Z congyushuai $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$perpage = 30;
$perpage = mob_perpage($perpage);

$page = empty($_GET['page'])?0:intval($_GET['page']);
if($page<1) $page = 1;
$start = ($page-1)*$perpage;

ckstart($start, $perpage);

$list = array();
$count = 0;
$multi = '';

$view = (!empty($_GET['view']) && in_array($_GET['view'], array('userapp')))?$_GET['view']:'notice';
$actives = array($view=>' class="a"');
$opactives['notice'] = 'class="a"';

if($view == 'userapp') {

	space_merge($space, 'status');

	if($_GET['op'] == 'del') {
		$appid = intval($_GET['appid']);
		DB::query("DELETE FROM ".DB::table('common_myinvite')." WHERE appid='$appid' AND touid='$_G[uid]'");

		$msg = lang('message', 'do_success');
		jsonexit("{\"status\":\"0\",\"message\":\"$msg\"}");
	}

	$filtrate = 0;
	$count = 0;
	$apparr = array();
	$type = intval($_GET['type']);
	$query = DB::query("SELECT * FROM ".DB::table('common_myinvite')." WHERE touid='$_G[uid]' ORDER BY dateline DESC");
	while ($value = DB::fetch($query)) {
		$count++;
		$key = md5($value['typename'].$value['type']);
		$apparr[$key][] = $value;
		if($filtrate) {
			$filtrate--;
		} else {
			if($count < $perpage) {
				if($type && $value['appid'] == $type) {
					$list[$key][] = $value;
				} elseif(!$type) {
					$list[$key][] = $value;
				}
			}
		}
	}

} else {

	if(!empty($_GET['ignore'])) {
		DB::update('home_notification', array('new'=>'0', 'from_num'=>0), array('new'=>'1', 'uid'=>$_G['uid']));
	}

	foreach (array('wall', 'piccomment', 'blogcomment', 'clickblog', 'clickpic', 'sharecomment', 'doing', 'friend', 'credit', 'bbs', 'system', 'thread', 'task', 'group') as $key) {
		$noticetypes[$key] = lang('notification', "type_$key");
	}

	$isread = in_array($_G['gp_isread'], array(0, 1)) ? intval($_G['gp_isread']) : 0;
	$type = trim($_GET['type']);
	$wherearr = array();
	if(!empty($type)) {
		$wherearr[] = "`type`='$type'";
	}
	$new = !$isread;
	$wherearr[] = "`new`='$new'";
	$wherearr[] = "`type`!='group'";

	$sql = ' AND '.implode(' AND ', $wherearr);


	$newnotify = false;
	$count = DB::result(DB::query("SELECT COUNT(*) FROM ".DB::table('home_notification')." WHERE uid='$_G[uid]' $sql"), 0);
	if($count) {
		$limitstr = $isread ? " LIMIT $start,$perpage" : '';
		$query = DB::query("SELECT * FROM ".DB::table('home_notification')." WHERE uid='$_G[uid]' $sql ORDER BY new DESC, dateline DESC $limitstr");
		while ($value = DB::fetch($query)) {
			if($value['new']) {
				$newnotify = true;
				$value['style'] = 'color:#000;font-weight:bold;';
			} else {
				$value['style'] = '';
			}
			$value['rowid'] = '';
			if(in_array($value['type'], array('friend', 'poke'))) {
				$value['rowid'] = ' id="'.($value['type'] == 'friend' ? 'pendingFriend_' : 'pokeQuery_').$value['authorid'].'" ';
			}
			if($value['from_num'] > 0) $value['from_num'] = $value['from_num'] - 1;
			$list[$value['id']] = $value;
		}

		$multi = '';
		if($isread) {
			$multi = multi($count, $perpage, $page, "home.php?mod=space&do=$do&isread=1");
		}
	}

	if($newnotify) {
		DB::query("UPDATE ".DB::table('home_notification')." SET new='0' WHERE uid='$_G[uid]' AND new='1'");
	}

	if($space['newprompt']) {
		DB::update('common_member', array('newprompt'=>0), array('uid'=>$_G['uid']));
	}

	$readtag = array($isread => ' class="a"');


}

//print_r($list); exit;

dsetcookie('promptstate_'.$_G['uid'], $newprompt, 31536000);
foreach ($list as $key => $value){
	if($value['type'] == 'system'){//系统消息
    	//$list[$key]['note'] = cutstr($value['note'],40);
    	$list[$key]['note'] = strip_tags($value['note']);
    }else if($value['type'] ==  'friend' && $value['from_idtype'] ==  'friendrequest'){//好友
    	$notes = explode("</a>",$value['note']);
        $note = strip_tags($notes[1]);
//      $list[$key]['note'] = '<a href="../my/profile.html?uid='.$value['authorid'].'">'.$note.'</a>';
		$list[$key]['note'] = $note;
        $list[$key]['param'] = "?uid=".$value['authorid'];
    }else if($value['type'] ==  'post'){//帖子回复
        $notes = explode("</a>",$value['note']);
        $note = strip_tags($notes[1]);
//      $list[$key]['note'] = '<a href="../forum/viewthread.html?tid='.$value['from_id'].'">'.$note.'</a>';
		if(preg_match('/ptid=(\d+?)&/', $list[$key]['note'], $m)) {
			if($m[1]) {
				$list[$key]['tid'] = $m[1];
			}
		}
		
		$list[$key]['note'] = $note;
        $list[$key]['param'] = "?tid=".$value['from_id'];
    }else if($value['type'] ==  'piccomment' && $value['from_idtype'] == 'picid'){//相片评论
        $notes = explode("</a>",$value['note']);
        $note = strip_tags($notes[1]);
//      $list[$key]['note'] = '<a href="../home/album_pic.html?uid='.$value['uid'].'&'.$value['from_idtype'].'='.$value['from_id'].'">'.$note.'</a>';
    	$list[$key]['note'] = $note;
        $list[$key]['param'] = "?uid=".$value['uid']."&".$value['from_idtype']."=".$value['from_id'];
    }else if($value['type'] ==  'sharenotice' && $value['from_idtype'] == 'picid'){//相册分享
        $notes = explode("</a>",$value['note']);
        $note = strip_tags($notes[1]);
//      $list[$key]['note'] = '<a href="../home/blog_view.html?do=blog&uid='.$value['uid'].'&id='.$value['from_id'].'">'.$note.'</a>';
    	$list[$key]['note'] = $note;
        $list[$key]['param'] = "?do=album&uid=".$value['uid']."&".$value['from_idtype']."=".$value['from_id'];
    }else if($value['type'] ==  'blogcomment' && $value['from_idtype'] == 'blogid'){//日志评论
        $notes = explode("</a>",$value['note']);
        $note = strip_tags($notes[1]);
//      $list[$key]['note'] = '<a href="../home/blog_view.html?do=blog&uid='.$value['uid'].'&id='.$value['from_id'].'">'.$note.'</a>';
    	$list[$key]['note'] = $note;
        $list[$key]['param'] = "?do=blog&uid=".$value['uid']."&id=".$value['from_id'];
    }else if($value['type'] ==  'sharenotice' && $value['from_idtype'] == 'blogid'){//日志分享
        $notes = explode("</a>",$value['note']);
        $note = strip_tags($notes[1]);
//      $list[$key]['note'] = '<a href="../home/blog_view.html?do=blog&uid='.$value['uid'].'&id='.$value['from_id'].'">'.$note.'</a>';
    	$list[$key]['note'] = $note;
        $list[$key]['param'] = "?do=blog&uid=".$value['uid']."&id=".$value['from_id'];
    }else if($value['type'] ==  'sharenotice' && $value['from_idtype'] == 'tid'){//帖子分享
        $notes = explode("</a>",$value['note']);
        $note = strip_tags($notes[1]);
//      $list[$key]['note'] = '<a href="../forum/viewthread.html?'.$value['from_idtype'].'='.$value['from_id'].'">'.$note.'</a>';
    	$list[$key]['note'] = $note;
        $list[$key]['param'] = "?".$value['from_idtype']."=".$value['from_id'];
    }else if($value['type'] ==  'sharenotice' && $value['from_idtype'] == 'aid'){//文章分享
        $notes = explode("</a>",$value['note']);
        $note = strip_tags($notes[1]);
//      $list[$key]['note'] = '<a href="../home/blog_view.html?do=blog&uid='.$value['uid'].'&id='.$value['from_id'].'">'.$note.'</a>';
    	$list[$key]['note'] = $note;
        $list[$key]['param'] = "?".$value['from_idtype']."=".$value['from_id'];
    }else if($value['type'] ==  'wall'){//留言板
        $notes = explode("</a>",$value['note']);
        $note = strip_tags($notes[1]);
//      $list[$key]['note'] = '<a href="../my/wall.html?'.$value['from_idtype'].'='.$value['from_id'].'">'.$note.'</a>';
    	$list[$key]['note'] = $note;
        $list[$key]['param'] = "?".$value['from_idtype']."=".$value['from_id'];
    }else{
    	$notes = explode("</a>",$value['note']);
        $list[$key]['note'] = strip_tags($notes[1]);
    }
    $list[$key]['dateline'] = strip_tags(dgmdate($value['dateline'], 'u'));
}

$jsonarr['list'] = $list;
$multi = str_replace('home.php?mod=space&do=notice&','notice.html?',$multi);
/*MB add start*/
$jsonarr['zywy_curpage'] = $_G["zywy_curpage"];
$jsonarr['zywy_totalpage'] = $_G["zywy_totalpage"];
$jsonarr['ucurl'] = $_G["setting"]['ucenterurl'];
/*MB add end*/
//$jsonarr['page'] = $multi;

jsonexit($jsonarr);