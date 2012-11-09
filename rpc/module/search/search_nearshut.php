<?php

/**
 * 关闭附近用户
 *
 * file_description
 *
 * LICENSE Aushi Copyright
 *
 * @copyright  2011-2012 Bei Jing Zheng Yi Wireless
 * @version    $Id: \$
 * @since      File available since Release 1.0 -- 2011-09-16 pm 15:05
 * @author 	   <yjt>yinjitao2001@163.com
 * 
 */


 if(!empty($_G['uid']))
 {
 	$allow_state = (int)$_G[gp_allow_state];

// 	$allow_state = ($allow_state+1)%2;
 	if($allow_state == '2'){
 		$query = DB::getOne("SELECT allow_state FROM ".DB::table('zywx_useroperation')." WHERE uid = ".$_G['uid']."
								ORDER BY dateline DESC LIMIT 1");
 		jsonexit("{\"allow_state\":\"$query\"}");
 	}else{
 		$query = DB::query(" UPDATE ".DB::table('zywx_useroperation')." SET allow_state = ".$allow_state." WHERE uid= ".$_G['uid']." ");
 		if($query){
 			jsonexit("{\"allow_state\":\"$allow_state\"}");
 		}else{
 			$allow_state = ($allow_state+1)%2;
 			jsonexit("{\"allow_state\":\"$allow_state\"}");
 		}
 	} 	
 }else 
 {
 	$msg = rpclang('search', 'user_unlogin');
 	jsonexit("{\"state\":\"$msg\"}");
 }