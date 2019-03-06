<?php

/**
 * $Author: pengwenfei p@simple-log.com
 * $Date: 2012-02-01
 * www.simple-log.com 
*/
/*核心公共文件*/

if (!defined('IN_PBBLOG')) 
{
	die('Access Denied');
}

define('PBBLOG_ROOT', str_replace('includes','',str_replace("\\", '/', dirname(__FILE__))));
//定义博客系统的根路径

define('SLSYS',defined('SAE_TMP_PATH')?'SAE':'NORMAL');
//定义博客系统的运行环境

require(PBBLOG_ROOT.'/includes/'.SLSYS.'.core.php');

?>