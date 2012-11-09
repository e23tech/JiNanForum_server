<?php
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$Plang = $scriptlang['fhb_spiderstat'];
if($_G['gp_op'] == 'deleteall') {
	$query = DB::query("TRUNCATE TABLE ".DB::table('fhb_spiderstat')."");
	ajaxshowheader();
	echo $Plang['sp_deleted'];
	ajaxshowfooter();
} elseif ($_G['gp_op'] == 'delete') {
	DB::delete('fhb_spiderstat',array('id'=>$_G['gp_spider_id']));
	ajaxshowheader();
	echo $Plang['sp_deleted'];
	ajaxshowfooter();
} else {
	$ppp = 15;
	$page = max(1, intval($_G['gp_page']));
	$i=1;
	$resultempty = FALSE;
	$srchadd = $searchtext = $extra = '';
	if(!empty($_G['gp_srchspider'])) {
		$srchspider = $_G['gp_srchspider'];
		$extra = '&srchspider='.$srchspide;
		$srchadd = "AND spider like '$srchspider%'";
		$searchtext = $Plang['sp_search'].' "'.$srchspider.'" ';
	} elseif(!empty($_G['gp_srchspip'])) {
		$srchspip = intval($_G['gp_srchspip']);
		$extra = '&srchspip='.$srchspip;
		$srchadd = "AND spider_ip like '$srchspip%'";
		$searchtext = $Plang['sp_search'].' "'.$srchspip.'" ';
	}

	if($searchtext) {
		$searchtext = '<a href="'.ADMINSCRIPT.'?action=plugins&operation=config&do='.$pluginid.'&identifier=fhb_spiderstat&pmod=admincp">'.$Plang['sp_viewall'].'</a>&nbsp;'.$searchtext;
	}


	showtableheader();
	showformheader('plugins&operation=config&do='.$pluginid.'&identifier=fhb_spiderstat&pmod=admincp', 'srchsubmit');
	showsubmit('srchsubmit', $Plang['sp_search'], $Plang['sp_type'].': <input name="srchspider" value="'.htmlspecialchars(stripslashes($_G['gp_srchspider'])).'" class="txt" />&nbsp;&nbsp;'.$Plang['sp_ip'].': <input name="srchspip" value="'.htmlspecialchars(stripslashes($_G['gp_srchspip'])).'" class="txt" />', $searchtext);
	showformfooter();
	if(!$resultempty) {
		$count = DB::result_first("SELECT COUNT(*) FROM ".DB::table('fhb_spiderstat')." WHERE 1 $srchadd");
		$query = DB::query("SELECT * FROM ".DB::table('fhb_spiderstat')." WHERE 1 $srchadd ORDER BY id DESC LIMIT ".(($page - 1) * $ppp).",$ppp");

		echo '<tr class="header"><th>'.$Plang['sp_type'].'</th><th>'.$Plang['sp_ip'].'</th><th>'.$Plang['sp_time'].'</th><th>'.$Plang['sp_url'].'</th><th><a id="p'.$i.'" onclick="ajaxget(this.href, this.id, \'\');return false" href="'.ADMINSCRIPT.'?action=plugins&operation=config&do='.$pluginid.'&identifier=fhb_spiderstat&pmod=admincp&op=deleteall">'.$Plang['sp_deleteall'].'</a></th><th></th></tr>';
		while($data = DB::fetch($query)) {
			$i++;
			$data['spider_time'] = $data['spider_time'] ? dgmdate($data['spider_time']) : '';
			echo '<tr><td>'.$data['spider'].'</td>'.
				'<td>'.$data['spider_ip'].'</td>'.
				'<td>'.$data['spider_time'].'</td>'.
				'<td><a href='.$data['spider_url'].' target="_blank">'.$data['spider_url'].'</a></td>';
			echo '<td><a id="p'.$i.'" onclick="ajaxget(this.href, this.id, \'\');return false" href="'.ADMINSCRIPT.'?action=plugins&operation=config&do='.$pluginid.'&identifier=fhb_spiderstat&pmod=admincp&op=delete&spider_id='.$data['id'].'">'.$Plang['sp_delete'].'</a></td></tr>';
		}	
	}
}
showtablefooter();
echo multi($count, $ppp, $page, ADMINSCRIPT."?action=plugins&operation=config&do=$pluginid&identifier=fhb_spiderstat&pmod=admincp$extra");
?>