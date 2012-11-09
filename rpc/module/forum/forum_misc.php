<?php
/**
 * 杂项处理
 * @copyright  2011-2012 Bei Jing Zheng Yi Wireless
 * @since      File available since Release 1.0 -- 2011-8-16 下午04:33:48
 * @author niuguangju
*/

if($_G['gp_action'] == 'recommend') {
	
	//已经顶踩过
	if(getcookie('recommend'.$_G['gp_tid'])) {
		$msg = rpclang('forum', 'recommend');
		jsonexit("{\"message\":\"$msg\"}");
	}
}

require ROOT_DIR . '/source/module/forum/forum_misc.php';

//顶踩
if($_G['gp_action'] == 'recommend') {
	

	dsetcookie('recommend'.$_G['gp_tid'], '1', 3600);
	
	jsonexit("{\"recommendv\":\"$recommendv\"}");
}

?>