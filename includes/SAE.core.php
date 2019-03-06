<?php

/**
 * $Author: pengwenfei p@simple-log.com
 * $Date: 2012-02-01
 * www.simple-log.com 
*/
/*SAE环境下核心公共文件*/

if (!defined('IN_PBBLOG'))
{
	die('Access Denied');
}

error_reporting(E_ALL & ~E_NOTICE);

//获得系统运行开始时间
$mtime= explode(' ',microtime());
$start_time=$mtime[1]+$mtime[0];


require_once(PBBLOG_ROOT.'/includes/main.function.php');							//主要的函数文件
require_once(PBBLOG_ROOT.'/includes/base.function.php');							//一些基本的函数文件
require_once(PBBLOG_ROOT.'/includes/mysql.class.php');							    //数据库类文件
require_once(PBBLOG_ROOT.'/includes/Smarty/libs/Smarty.class.php');					//smarty模板


//初始化memcache
$mmc=memcache_init();
if ($mmc!=false)
{
	define('MCINIT',true);
}
require_once(PBBLOG_ROOT.'/home/data/sae_config.php');


//5.1以上的PHP版本设置时区，将默认时区设置成为东8区，也就是北京时间
if (version_compare(PHP_VERSION, 5.1, '>'))
{
	if (empty($timezone))
	{
		$timezone='Etc/GMT-8';
	}
	date_default_timezone_set($timezone);
}
$time=time();
$date=date('Y-m-d H:i:s',$time);

//关闭set_magic_quotes_runtime和设置错误输出信息
if (version_compare(PHP_VERSION, 5.3, '<'))
{
	set_magic_quotes_runtime(0);

}

// 对传入的变量过滤
if (!get_magic_quotes_gpc())
{
	$_GET  	  = empty($_GET)?'':input_filter($_GET);
	$_POST    = empty($_POST)?'':input_filter($_POST);
	$_COOKIE  = empty($_COOKIE)?'':input_filter($_COOKIE);
	$_FILES  = empty($_FILES)?'':input_filter($_FILES);
}


//开始获得客户端的参数
$ip=ip();
$referer_url=referer_url();
$url=url();


//初始化数据库
$db=new cls_mysql();
$db->connect($dbhost,$dbuser,$dbpw,$dbname,$charset,$pconnect);

$install_lock=$db->getrow("show tables like '".table('config')."'");
if (empty($install_lock)) 
{
	header('location: install/sae.php');
	exit;
}

//获取网站配置信息
$config=sl_config();
$page_size=$config['page_size'];



//开启主MySQL
if (defined('Need_Master_DB'))
{
	$db2=new cls_mysql();
	$db2->connect($dbhost_m,$dbuser,$dbpw,$dbname,$charset,$pconnect);
	unset($dbhost_m);
	$master_db=true;
}
unset($dbhost,$dbuser,$dbname,$charset,$pconnect);

//初始化模板
$smarty=new Smarty();
if (defined('IN_PBADMIN'))
{
	$smarty->template_dir=PBBLOG_ROOT.'/'.PBBLOG_WS_ADMIN.'/templates';
	$path='saemc://'.$_SERVER['HTTP_APPVERSION'].'smarty_admin_compiled';
	mkdir($path);
	$smarty->compile_dir = $path;
	$smarty->compile_locking = false;
	$smarty->caching = false;
}
elseif (!defined('IN_PBADMIN'))
{
	$smarty->template_dir=PBBLOG_ROOT.'/themes/'.$config['template_name'];
	if (MCINIT)
	{
		$path='saemc://'.$_SERVER['HTTP_APPVERSION'].'smarty_compiled';
		mkdir($path);
		$smarty->compile_dir = $path;
		if ($config['is_cache'])
		{
			$path='saemc://'.$_SERVER['HTTP_APPVERSION'].'smarty_cache';
			mkdir($path);
			$smarty->cache_dir      = $path;
		}
	}
	else
	{
		$smarty->compile_dir = SAE_TMP_PATH;
	}
	$smarty->compile_locking = false;
}


//获得会话信息，并初始化会员信息
session_start();
if (empty($_SESSION['user_id']))
{
	$_SESSION=array();
	//游客登陆设置
	$_SESSION['user_id']=0;
	$_SESSION['group_id']=0;
}
else
{
	$user_id=$_SESSION['user_id'];
	$group_id=$_SESSION['group_id'];
}

?>