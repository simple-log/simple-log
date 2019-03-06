<?php

/**
 * $Author: pengwenfei p@simple-log.com
 * $Date: 2019-02-27
 * www.simple-log.com 
*/

if (!defined('IN_PBBLOG')) 
{
	die('Access Denied');
}

class cls_mysql{


    protected static $_instance;

    public static $prefix = '';

    protected $_mysqli;

    protected $_query;

    protected $_lastQuery;

    protected $_queryOptions = array();

    protected $_join = array();

    protected $_where = array();

    protected $_joinAnd = array();

    protected $_having = array();

    protected $_orderBy = array();

    protected $_groupBy = array();

    protected $_tableLocks = array();

    protected $_tableLockMethod = "READ";

    protected $_bindParams = array(''); // Create the empty 0 index

    public $count = 0;

    public $totalCount = 0;

    protected $_stmtError;

    protected $_stmtErrno;

    protected $host;
    protected $_username;
    protected $_password;
    protected $db;
    protected $port;
    protected $charset;

    private $mysqli;
	private $con_id;
	private $query_num;
	
	function connect($dbhost,$dbuser,$dbpw,$dbname = '',$charset='utf8')
	{

        $this->con_id = new mysqli($dbhost, $dbuser, $dbpw, $dbname);
        if ($this->con_id->connect_error)
        {
            $this->err_msg('Connect Error ' . $this->con_id->connect_errno . ': ' . $this->con_id->connect_error, $this->mysqli->connect_errno);
        }
        if ($charset)
        {
            $this->con_id->set_charset($charset);
        }
	}

    public function query( $sql )
    {
        $querysql = $this->con_id->query( $sql );

        if( $this->con_id->error )
        {
            $this->err_msg($this->con_id->error.'there has a wrong when query sql:'.$sql);
            return false;
        }
        else
        {
            return true;
        }
    }

    public function query2( $sql )
    {
        $querysql = $this->con_id->query( $sql );
        if( $this->con_id->error )
        {
            $this->err_msg($this->con_id->error.'there has a wrong when query sql:'.$sql);
            return false;
        }
        else
        {
            return $querysql;
        }
    }


    function fetch_array($query,$result_type=MYSQLI_ASSOC)
    {
        $result=$this->query2($query);
        return $result->fetch_array($result_type);
    }
    
    function affected_rows($query)
    {
        $result=$this->query2($query);
        return  $result->affected_rows;
    }

    function num_rows($query)
    {
        $result=$this->query2($query);
        return  $result->num_rows;
    }

    function insert_id()
    {
    	return $this->con_id->insert_id;
    }
    
    function fetchRow($query)
    {
        $result=$this->query2($query);
        return $result->fetch_assoc();
    }
    

	function close()
    {
        $this->con_id->close();
    }

    public function version()
    {
        return $this->con_id->server_version;
    }
    
    //获取单个返回结果集中的单个数据
    function getone($sql)
    {
            $result=$this->query2($sql);
            $row=$result->fetch_array(MYSQLI_NUM);
    		if ($row!==false) 
    		{
    			return $row['0'];
    		}
    		else 
    		{
    			return '';
    		}
    }
    
    //获取一条结果集中的一条数组
    function getrow($sql)
    {
            $result=$this->query2($sql);
            $row=$result->fetch_array();
    		if ($row!==false) 
    		{
    			return $row;
    		}
    		else 
    		{
    			return '';
    		}
    }
    
    //获取所有结果
    function getall($sql,$type=MYSQLI_BOTH)
    {
            $result=$this->query2($sql);
    		$arr=array();
    		while ($row=$result->fetch_array($type))
    		{
    			$arr[]=$row;
    		}
    		return $arr;
    }

    //过滤攻击字符
    function safe($val)
    {
        return mysqli_real_escape_string($this->con_id,$val);
    }


	function err_msg($msg)
	{
		if ($msg) 
		{
			die($msg);
		}
		else 
		{
			die('mysql error');
		}
	}
}
?>