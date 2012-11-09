<?php
/**
 * 
 *
 * function_description
 *
 * @copyright  2011-2012 Bei Jing Zheng Yi Wireless
 * @since      File available since Release 1.0 -- 2011-8-16 下午06:14:50
 * @author mengbing880814@yahoo.com.cn
 * 
 */
 
function custom_query($sql){
	
    $objarray = array();
    $query = DB::query($sql);
    while ($result = DB::fetch($query)) {
        //$result['subject'] = cutstr($result['subject'], 40, '..');
        $result['avatar'] = avatar($result['authorid'],'small');
        $result['name'] = strip_tags($result['name']);
        //$result['dateline'] = gmdate('Y-m-d/H:i', $result['dateline'] + $_G['setting']['timeoffset'] * 3600);
        //$result['lastpost'] = gmdate('Y-m-d/H:i', $result['lastpost'] + ($_G['setting']['timeoffset'] * 3600));
        $result['dateline'] = dgmdate($result['dateline']);
        $result['lastpost'] = dgmdate($result['lastpost']);
        //$result['authors'] = cutstr($result['author'], 10, '..');
        $result['lastposters'] = cutstr($result['lastposter'], 40, '..');
        $objarray[] = $result;
    }
    unset($sql, $query, $result);
    return $objarray;
}
?>