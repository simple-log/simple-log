<?php

/**
 * 日志管理
 * $Author: pengwenfei p@simple-log.com
 * $Date: 2012-01-17
 * www.simple-log.com 
*/

if ($action=='blog_list')
{
	$pg=isset($_GET['pg'])?intval($_GET['pg']):1;
	$page_size=!empty($config['page_size'])?$config['page_size']:'15';
    $keywords=!empty($_POST['keywords'])?trim($_POST['keywords']):'';

	$where1=$where=' WHERE 1 ';

	$admin_privilege=$db->getone('SELECT admin_privilege FROM '.table('user_group')." WHERE group_id='".$_SESSION['group_id']."'");
	if ($admin_privilege!='all')
	{
		$where1.=" AND b.user_id='".$_SESSION['user_id']."'";
		$where.=" AND user_id='".$_SESSION['user_id']."'";
	}


	if (is_numeric($keywords))
	{
		$sql='SELECT * FROM '.table('blog')." WHERE blog_id='".$keywords."'";
		if ($row=$db->getrow($sql))
		{
			if ($row['user_id']!=$_SESSION['user_id']&&$admin_privilege!='all')
			{
				sys_message('您没有权限编辑他人日志',$referer_url);
			}
			else
			{
				header('location: admin.php?act=edit_blog&id='.$keywords);
				exit;
			}
		}
	}
	elseif (!empty($keywords))
	{
		$where.=" AND title LIKE '%".$db->safe($keywords)."%'";
		$where1.=" AND b.title LIKE '%".$db->safe($keywords)."%'";
	}

	$sql='SELECT count(*) FROM '.table('blog').$where;
	$page_count=intval(($db->getone($sql)-1)/$page_size)+1;
	$page_arr=create_page($page_count,$pg,0);

	$start=($pg-1)*$page_size;
	$sql='SELECT b.blog_id,b.title,b.description,b.add_time,b.views,b.comments,b.password,b.view_group,b.url_type,u.user_name,c.cat_name,c.cat_id ,c.url_type as cat_url_type FROM '.table('blog').
	' AS  b LEFT JOIN '.table('user').' AS u on b.user_id=u.user_id'.
	'  LEFT JOIN '.table('category').' AS c on b.cat_id=c.cat_id'.$where1.
	" ORDER BY b.blog_id DESC LIMIT ".$start.' , '.$page_size;

	$blog_list=$db->getall($sql);
	foreach ($blog_list as $key=>$val)
	{
		$blog_list[$key]['add_time']=pbtime($val['add_time']);
		//$blog_list[$key]['description']=unprocess_text($val['description']);
	}

	$smarty->assign('blog_list',$blog_list);
	$smarty->assign('page_arr',$page_arr);
	$smarty->assign('page_count',$page_count);
	$smarty->assign('pg',$pg);
	$smarty->assign('url','admin.php?act=blog_list&pg=');
	$smarty->display('blog_list.html');
}


elseif ($action=='del_blog')
{
	$blog_id=intval($_GET['id']);
	if ($blog_id>0)
	{
		$sql='DELETE FROM '.table('blog')." WHERE blog_id='".$blog_id."'";
		if ($master_db?$db2->query($sql):$db->query($sql))
		{
			$blog_id=intval($_GET['id']);
			$sql='DELETE FROM '.table('tags')." WHERE blog_id='".$blog_id."'";
			$master_db?$db2->query($sql):$db->query($sql);
			clear_tpl();
			sys_message('删除博客成功',$referer_url);
		}
		else
		{
			sys_message('删除博客失败，请重新删除',$referer_url);
		}
	}
	elseif (!empty($_POST['blog_arr']))
	{
		foreach ($_POST['blog_arr'] as $val)
		{
			if ($val>0)
			{
				$blog_id=intval($val);
				$sql='DELETE FROM '.table('blog')." WHERE blog_id='".$blog_id."'";
				if ($master_db?$db2->query($sql):$db->query($sql))
				{
					$sql='DELETE FROM '.table('tags')." WHERE blog_id='".$blog_id."'";
					$master_db?$db2->query($sql):$db->query($sql);
				}
			}
		}
		clear_tpl();
		sys_message('批量删除博客成功',$referer_url);
	}

}

elseif ($action=='add_blog')
{
	$sql='SELECT cat_id,cat_name,cat_desc,listorder FROM '.table('category')." WHERE parent_id=0 ORDER BY listorder ASC , cat_id ASC ";
	$cat_list=$db->getall($sql);

	//获取子分类，暂时只支持二级分类
	foreach ($cat_list as $key=>$val)
	{
		$sql='SELECT cat_id,cat_name,cat_desc,listorder FROM '.table('category').
		" WHERE parent_id=".$val['cat_id']." ORDER BY listorder ASC , cat_id ASC ";
		$cat_list[$key]['children']=$db->getall($sql);
	}

	$smarty->assign('cat_list',$cat_list);

	//读取用户组数据
	$sql='SELECT group_id,group_name FROM '.table('user_group');
	$group_list=$db->getall($sql);
	$smarty->assign('group_list',$group_list);

	if ($config['remoteImgSave'])
	{
		$smarty->assign('remoteImgSave',1);
	}
	else
	{
		$smarty->assign('remoteImgSave',0);
	}


	$smarty->assign('type','act_add_blog');
	$smarty->assign('rewrite',$GLOBALS['config']['rewrite']);
	$smarty->assign('url_type',1);
	$smarty->assign('u',str_replace(PBBLOG_WS_ADMIN, '', dirname($url)));
	$smarty->display('add_blog.html');
}

elseif ($action=='act_add_blog')
{
	require_once(PBBLOG_ROOT.'/includes/base.function.php');

	$user_id=intval($_SESSION['user_id']);
	$url_type=intval($_POST['url_type']);
	$blog_title=$_POST['title'];
	if (empty($blog_title))
	{
		sys_message('博客标题不能为空',$referer_url);
	}
	$cat_id=intval($_POST['cat']);
	if (empty($blog_title))
	{
		sys_message('请选择分类',$referer_url);
	}

	//$desc=process_text($_POST['description']);
	$desc=htmlspecialchars($_POST['description']);
	$content=htmlspecialchars($_POST['editor']);
	$open_type=$_POST['blog_comment'];				//1表示关闭评论
	$is_top=$_POST['is_top'];						//1表示置顶


	//对自定义url处理
	if ($url_type==2)
	{
		$url_type=$_POST['url'];

		//对自定义url唯一性检查
		if (!empty($url_type))
		{
			if($db->getone('SELECT url_type FROM '.table('blog')." WHERE url_type='".$url_type."'"))
			{
				sys_message('您定义的URL已经存在于其他日志中，请返回重新定义',$referer_url);
			}
		}
	}

	$password=trim($_POST['password']);

	//将选取的用户组连接起来
	if (!empty($_POST['group']))
	{
		$ws='';
		$group='';
		foreach ($_POST['group'] as $key=>$val)
		{
			$group.=$ws.$val;
			$ws=',';
		}
	}
	else
	{
		$group='all';
	}

	$sql='INSERT INTO '.table('blog').
	" (`blog_id` ,`user_id` ,`cat_id` ,`title` ,`description` ,`content` ,`add_time` ,`edit_time` ,`comments` ,`views` ,`password`,`view_group`,`open_type`,`url_type`,`is_top` )
		VALUES (NULL , '".$user_id."', '".$cat_id."', '".$blog_title."', '".
	$desc."', '".$content."', '".$time."', '0', '0', '0', '".$password."', '".$group."','".$open_type."','".$url_type."','".$is_top."')";
	if ($master_db?$db2->query($sql):$db->query($sql))
	{
		$blog_id=$master_db?$db2->insert_id():$db->insert_id();
		if ($_POST['tags'])
		{
			$tags_array=array();
			$tags_array=str_replace('，',',',$_POST['tags']);
			if ($config['tag_explode_type']==0||empty($config['tag_explode_type']))
			{
				$tags_array=str_replace(' ',',',$tags_array);
			}

			$tags=explode(',',$tags_array);

			unset($tags_array);

			foreach ($tags as $val)
			{
				if (!empty($val))
				{
					$sql="INSERT INTO  ".table('tags')." (`tag_id` ,`tag_name` ,`blog_id`)VALUES (NULL ,  '$val',  '".$blog_id."')";
					$master_db?$db2->query($sql):$db->query($sql);
				}
			}
		}

		$sql='DELETE FROM '.table('page')." WHERE  relate_id='0' AND user_id='".$user_id."' AND type='-1'";
		$master_db?$db2->query($sql):$db->query($sql);
		clear_tpl();

		$blogurl=build_url('blog',$blog_id,$url_type);
		hook(5,array('blog_id'=>$blog_id,'blogurl'=>$blogurl));

		sys_message('添加日志成功','admin.php?act=blog_list');
	}
	else
	{
		sys_message('添加日志失败，请重新返回添加','admin.php?act=add_blog');
	}
}

elseif ($action=='edit_blog')
{
	$blog_id=intval($_GET['id']);
	if (empty($blog_id))
	{
		sys_message('日志id不能为空',$referer_url);
	}
	$sql='SELECT * FROM '.table('blog')." WHERE blog_id='".$blog_id."'";

	if ($row=$db->getrow($sql))
	{
		$row['description']=htmlspecialchars_decode($row['description']);
		$row['content']=htmlspecialchars_decode($row['content']);
		$smarty->assign('blog',$row);
		$admin_privilege=$db->getone('SELECT admin_privilege FROM '.table('user_group')." WHERE group_id='".$_SESSION['group_id']."'");
		if ($row['user_id']!=$_SESSION['user_id']&&$admin_privilege!='all')
		{
			sys_message('您没有权限编辑他人日志',$referer_url);
		}
	}
	else
	{
		sys_message('读取日志数据失败，请返回重新修改',$referer_url);
	}

	$sql='SELECT cat_id,cat_name,cat_desc,listorder FROM '.table('category')." WHERE parent_id=0 ORDER BY listorder ASC , cat_id ASC ";
	$cat_list=$db->getall($sql);

	//获取子分类，暂时只支持二级分类
	foreach ($cat_list as $key=>$val)
	{
		$sql='SELECT cat_id,cat_name,cat_desc,listorder FROM '.table('category').
		" WHERE parent_id=".$val['cat_id']." ORDER BY listorder ASC , cat_id ASC ";
		$cat_list[$key]['children']=$db->getall($sql);
	}

	$smarty->assign('cat_list',$cat_list);

	//读取用户组数据
	$sql='SELECT group_id,group_name FROM '.table('user_group');
	$group_list=$db->getall($sql);
	if ($row['view_group']!='all') {
		$view_group=explode(',',$row['view_group']);
		foreach ($group_list as $key=>$val)
		{
			//读出并标记已选择的用户组
			if (in_array($val['group_id'],$view_group))
			{
				$group_list[$key]['flag']=1;
			}
		}
	}
	$smarty->assign('group_list',$group_list);

	$sql='SELECT * FROM '.table('tags')." WHERE blog_id='".$blog_id."'";
	$tags_list=$db->getall($sql);
	$tag_num=count($tags_list);
	if ($tag_num>0)
	{
		$i=1;
		foreach ($tags_list as $val)
		{
			$tags.=$val['tag_name'].(($i<$tag_num)?',':'');
			$i++;
		}
	}
	$smarty->assign('tags',$tags);

	$smarty->assign('rewrite',$GLOBALS['config']['rewrite']);
	$smarty->assign('url_type',$row['url_type']);
	$smarty->assign('u',str_replace(PBBLOG_WS_ADMIN, '', dirname($url)));
	$smarty->assign('type','act_edit_blog&id='.$blog_id);
	$smarty->assign('id',$blog_id);
	$smarty->display('add_blog.html');
}

elseif ($action=='act_edit_blog')
{
	require_once(PBBLOG_ROOT.'/includes/base.function.php');

	$blog_id=intval($_GET['id']);

	if (empty($blog_id))
	{
		sys_message('日志id不能为空',$referer_url);
	}

	$sql='SELECT * FROM '.table('blog')." WHERE blog_id='".$blog_id."'";

	if ($row=$db->getrow($sql))
	{
		$admin_privilege=$db->getone('SELECT admin_privilege FROM '.table('user_group')." WHERE group_id='".$_SESSION['group_id']."'");
		if ($row['user_id']!=$_SESSION['user_id']&&$admin_privilege!='all')
		{
			sys_message('您没有权限编辑他人日志',$referer_url);
		}
	}
	else
	{
		sys_message('错误日志，请返回重新修改',$referer_url);
	}

	$blog_title=$_POST['title'];
	if (empty($blog_title))
	{
		sys_message('博客标题不能为空',$referer_url);
	}
	$cat_id=$_POST['cat'];
	if (empty($blog_title))
	{
		sys_message('请选择分类',$referer_url);
	}

	//$desc=process_text($_POST['description']);
	$desc=htmlspecialchars($_POST['description']);
	$content=htmlspecialchars($_POST['editor']);
	$open_type=$_POST['blog_comment'];				//1表示关闭评论
	$is_top=$_POST['is_top'];					//1表示置顶

	//对自定义url处理
	$url_type=intval($_POST['url_type']);
	if ($url_type==2)
	{
		$url_type=$_POST['url'];

		//对自定义url唯一性检查
		if (!empty($url_type))
		{
			if($db->getone('SELECT url_type FROM '.table('blog')." WHERE url_type='".$url_type."' AND blog_id!='".$blog_id."'"))
			{
				sys_message('您定义的URL已经存在于其他日志中，请返回重新定义',$referer_url);
			}
		}
	}

	$password=trim($_POST['password']);

	//将选取的用户组连接起来
	if (!empty($_POST['group'])) {
		$group_count=count($_POST['group']);
		$i=1;
		foreach ($_POST['group'] as $val)
		{
			$group.=$val.($group_count==$i?'':',');
			$i++;
		}
	}
	else {
		$group='all';
	}

	if ($_POST['tags'])
	{
		$sql='SELECT tag_name FROM '.table('tags')." WHERE blog_id='".$blog_id."'";
		$tags_list=$db->getall($sql);
		$tags_list_new=array();
		foreach ($tags_list as $val)
		{
			$tags_list_new[]=$val['tag_name'];
		}
		unset($tags_list);

		$tags_array=array();

		$tags_array=str_replace('，',',',$_POST['tags']);
		if ($config['tag_explode_type']==0||empty($config['tag_explode_type']))
		{
			$tags_array=str_replace(' ',',',$tags_array);
		}
		
		$tags=explode(',',$tags_array);
		unset($tags_array);
		$insert_tags=array_diff($tags,$tags_list_new);
		foreach ($insert_tags as $val)
		{
			if (!empty($val))
			{
				$sql="INSERT INTO  ".table('tags')." (`tag_id` ,`tag_name` ,`blog_id`)VALUES (NULL ,  '$val',  '".$blog_id."')";
				$master_db?$db2->query($sql):$db->query($sql);
			}
		}

		$del_tags=array_diff($tags_list_new,$tags);
		foreach ($del_tags as $val)
		{
			$sql='DELETE FROM '.table('tags')." WHERE blog_id='".$blog_id."' AND tag_name='$val'";
			$master_db?$db2->query($sql):$db->query($sql);
		}
	}
	else
	{
		$sql='DELETE FROM '.table('tags')." WHERE blog_id='".$blog_id."'";
		$master_db?$db2->query($sql):$db->query($sql);
	}

	$sql='UPDATE '.table('blog').
	"  SET `title` = '".$blog_title."',`description` = '".$desc."',`content` = '".$content.
	"' , `edit_time`='".$time."', `cat_id`='".$cat_id."' , `password`='".$password."' , `open_type`='".$open_type."' , `view_group`='".$group."' , `url_type`='".$url_type."' , `is_top`='".$is_top.
	"' WHERE blog_id='".$blog_id."'";

	if ($master_db?$db2->query($sql):$db->query($sql))
	{
		$sql=('DELETE FROM '.table('page')." WHERE relate_id='".$blog_id."'". " AND  user_id='".$user_id."' AND type='-1'");
		$master_db?$db2->query($sql):$db->query($sql);
                clear_tpl();
		sys_message('修改日志成功','admin.php?act=edit_blog&id='.$blog_id);
	}
	else
	{
		sys_message('修改日志失败，请重新返回添加','admin.php?act=edit_blog&id='.$blog_id);
	}
}

?>