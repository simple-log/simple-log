<?php

/**
 * : pengwenfei p@simple-log.com
 * : 2011-02-04
 * www.simple-log.com 
*/ 


define('PBBLOG_WS_INCLUDES', 'includes');

define('PBBLOG_WS_ADMIN', 'admin');

$db_port=SAE_MYSQL_PORT;
//主库的host
$dbhost=SAE_MYSQL_HOST_S.':'.$db_port;
$dbhost_m=SAE_MYSQL_HOST_M.':'.$db_port;
$dbname=SAE_MYSQL_DB;
$dbpw=SAE_MYSQL_PASS;
$dbuser=SAE_MYSQL_USER;
$dbprefix   = 'fb_';
//表前缀
$pconnect   = '';
//是否保持连接

/*会话、cookie设置*/ 
$cookie_path   = '/';
$cookie_domain   = '';
$session   = '1440';

/*网站编码，暂时只支持utf8*/ 
$charset   = 'utf8';

/*安全哈希密码*/ 
$hash_secret   = '5385a11c14d7f5a';
//此处与全站的md5相关

?>