<?php

/**
 * 备份恢复数据库
 * $Author: pengwenfei p@simple-log.com
 * $Date: 2011-02-05
 * www.simple-log.com 
*/


if ($action=='databak')
{
	$fb_tables = array($dbprefix.'blog',
	$dbprefix.'category',
	$dbprefix.'comment',
	$dbprefix.'link',
	$dbprefix.'config',
	$dbprefix.'plugins',
	$dbprefix.'modules',
	$dbprefix.'user',
	$dbprefix.'page',
	$dbprefix.'attachments',
	$dbprefix.'tags',
	$dbprefix.'user_group'
	);
	$tables = $db->getall("show tables;");
	$key=array_keys($tables[0]);
	foreach ($tables as $val)
	{
		$is_fbtable=0;
		if (in_array($val[$key[0]],$fb_tables))
		{
			$is_fbtable=1;
		}
		$table[]=array('value'=>$val[$key[0]],'is_fbtable'=>$is_fbtable);
	}


	$smarty->assign('tables',$table);
	$smarty->assign('type','act_backup');
	$smarty->display('databak.html');
}


elseif ($action=='act_backup')
{
	@ini_set('memory_limit', '50M');

	$tables=$_POST['table'];
	if (count(($tables))==0)
	{
		$tables = array($dbprefix.'blog',
			$dbprefix.'category',
			$dbprefix.'comment',
			$dbprefix.'link',
			$dbprefix.'config',
			$dbprefix.'plugins',
			$dbprefix.'modules',
			$dbprefix.'user',
			$dbprefix.'page',
			$dbprefix.'attachments',
			$dbprefix.'tags',
			$dbprefix.'user_group'
		);
	}



	$data_dump='';
	foreach ($tables as $table)
	{
		//获得表结构
		$data_dump .= "DROP TABLE IF EXISTS $table;\n\n";
		$create = $db->getrow("SHOW CREATE TABLE $table");
		$data_dump .= $create['Create Table'].";\n\n";

		//开始获取表数据
		$num = $db->getone("SELECT COUNT(*) FROM $table");
		if ($num<=0)
		{
			continue;
		}

		$table_data = $db->getall("SELECT * FROM $table");

		$data_dump .= "INSERT INTO $table (";

		$fields = array_keys($table_data[0]);
		$field_insert = "";
		foreach ($fields as $val)
		{
			$data_dump .= $field_insert.'`'.$val.'`';
			$field_insert = ",";
		}
		$data_dump .= " ) VALUES ( ";

		$fields_num=count($table_data[0]);

		$k=0;
		foreach ($table_data as $data)
		{

			$insert = "";
			for($i = 0; $i < $fields_num; $i++)
			{
				$data_dump .= $insert."'".$db->safe($data[$fields[$i]])."'";
				$insert = ",";
			}
			$k++;

			if ($k==$num)
			{
				$data_dump .= ")";
			}
			else
			{
				$data_dump .= "),(";
			}


		}
		$data_dump .= ";\n\n";
	}


	$filename =date('Y-m-d-H-i-s-').rand(10000,60000).'.sql';

	if(trim($data_dump))
	{

		$s = new SaeStorage(SAE_ACCESSKEY,SAE_SECRETKEY);
		$s->write( 'simplelogsql' , $filename , $data_dump);
		$file_url=$s->getUrl( 'simplelogsql' , $filename);
		sys_message('备份成功',$referer_url);
	}
	else
	{
		sys_message('备份失败',$referer_url);
	}

}

elseif ($action=='re_data')
{
	/* 获得备份文件 */
	$sql = array();
	$s = new SaeStorage(SAE_ACCESSKEY,SAE_SECRETKEY);
	$num = 0;
	while ( $ret = $s->getList("simplelogsql", "*", 100, $num ) )
	{
		foreach($ret as $file)
		{
			$sql[]=array('file_name'=>$file,'url'=>$s->getUrl( 'simplelogsql' , $file));
			$num ++;
		}
	}

	$smarty->assign('sql',$sql);
	$smarty->assign('type','act_re_data');
	$smarty->display('sql_list.html');
}


elseif ($action=='act_re_data')
{
	$file=$_POST['sql'];

	$s = new SaeStorage();

	$sql=$s->read('simplelogsql' , $file);

	$sql_list = explode(";\n\n", $sql);

	$sql_num=count($sql_list);

	for ($i=0;$i<$sql_num;$i++)
	{
		if (!empty($sql_list[$i]))
		{
			$master_db?$db2->query($sql_list[$i]):$db->query($sql_list[$i]);
		}
	}
	sys_message('数据恢复完成',$referer_url);
}

elseif ($action=='del_sql_file')
{
	$file=$_GET['file'];
	$s = new SaeStorage();
	$s->delete('simplelogsql' , $file);
	
	sys_message('sql备份文件删除成功',$referer_url);
}


?>