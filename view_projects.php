<?php
	define('CS546', true);
	//This is the order all files must be included in each .PHP file
	include_once('includes/config.php');
	include_once('includes/functions.php');
	include_once('includes/settings.php');
	include_once('includes/sessions.php');
	include_once('includes/template.php');
	
	$SETTINGS["PAGE_TITLE"] = "Viewing Projects";
	
	//Init the template (You can echo as many templates as listed in the filenames)
	$main_body = new Template($SETTINGS["TEMPLATE_DIR"]);
	$main_body->set_filenames(array(
	 'view_projects_body' => 'view_projects_body.htm',
	 'simple_body' => 'simple_body.htm'
	));
	
	connect();
	if(!$_SESSION['is_logged_in'])
	{
		$message = 'You must login to view a Project.<br /><br /><a href="index.php">Return</a>';
		$main_body->assign_vars(array(
			'MESSAGE' => $message,
		));
		//Echo everything
		include('includes/header.php');
		$main_body->pparse('simple_body');
		include('includes/footer.php');
	} else if(isset($_GET['action']) && isset($_GET['pid']) && $_GET['action'] == "delete")
	{
		$project_id = (isset($_GET['pid']) && is_numeric($_GET['pid'])) ? $_GET['pid'] : -1;
		
		//The information for the project itself (Who created it, project name, etc)
		$project_row = mysql_fetch_assoc(mysql_query("SELECT * FROM " . $SETTINGS["TABLE_PREFIX"] . "projects P, " . $SETTINGS["TABLE_PREFIX"] . "users U WHERE  P.creator_user_id = U.user_id AND P.project_id = " . $project_id));
		
		if(!$project_row)
		{
			$message = 'Project ID not found.<br /><br /><a href="index.php">Return</a>';
			$main_body->assign_vars(array(
				'MESSAGE' => $message,
			));
			//Echo everything
			include('includes/header.php');
			$main_body->pparse('simple_body');
			include('includes/footer.php');
		} else
		{
			if($project_row["creator_user_id"] == $_SESSION['user_id'])
			{
				//For loop to destroy all modules within project
				$module_results = mysql_query("SELECT * FROM ". $SETTINGS["TABLE_PREFIX"] . "project_entities E WHERE E.project_id = " . $project_id);
				while ($module_row = mysql_fetch_assoc($module_results))
				{
					//Get the files in the module
					$module_id = $module_row['entity_id'];
					$result = mysql_query("SELECT * FROM ". $SETTINGS["TABLE_PREFIX"] . "file_reference F, ". $SETTINGS["TABLE_PREFIX"] . "project_entities E WHERE F.entity_id = E.entity_id AND F.entity_id = " . $module_id . " AND E.project_id = " . $project_id);
					
					//Unlink each file within module and then remove info from database
					while ($row = mysql_fetch_assoc($result))
					{
						//Each row contains the file ID of the file we need to delete
						if(!(unlink($SETTINGS["UPLOAD_DIR"] . "/" . $row['file_id'])))
							die("Write permission error");
						//Remove table information
						mysql_query("DELETE FROM " . $SETTINGS["TABLE_PREFIX"] . "file_reference WHERE file_id = " . $row['file_id']);
						mysql_query("DELETE FROM ". $SETTINGS["TABLE_PREFIX"] . "file_checked WHERE origin_id = " . $row['origin_id']);
					}
					//And now we can kill the module
					$row = mysql_fetch_assoc(mysql_query("SELECT * FROM " . $SETTINGS["TABLE_PREFIX"] . "project_entities WHERE entity_id = " . $module_id));
					mysql_query("DELETE FROM " . $SETTINGS["TABLE_PREFIX"] . "project_entity_comments WHERE project_id = " . $project_id);
					mysql_query("DELETE FROM " . $SETTINGS["TABLE_PREFIX"] . "project_entities WHERE entity_id = " . $module_id);
				}
				
				//Now we can delete the project
				mysql_query("DELETE FROM " . $SETTINGS["TABLE_PREFIX"] . "belongs_to_project WHERE project_id = " . $project_id);
				mysql_query("DELETE FROM " . $SETTINGS["TABLE_PREFIX"] . "invited_to_project WHERE project_id = " . $project_id);
				mysql_query("DELETE FROM " . $SETTINGS["TABLE_PREFIX"] . "projects WHERE project_id = " . $project_id);
				mysql_query("DELETE FROM " . $SETTINGS["TABLE_PREFIX"] . "project_comments WHERE project_id = " . $project_id);
				
				rrmdir($SETTINGS["UPLOAD_DIR"] . '/icons/' . $project_id);
				
				$message = 'Project "' . $project_row["project_name"] . '" has been succesfully deleted.<br /><br /><a href="view_projects.php">Return</a>';
				$main_body->assign_vars(array(
					'MESSAGE' => $message,
				));
				//Echo everything
				include('includes/header.php');
				$main_body->pparse('simple_body');
				include('includes/footer.php');
			} else
			{
				$message = 'Nice try, you are not the creator of this Project.<br /><br /><a href="index.php">Return</a>';
				$main_body->assign_vars(array(
					'MESSAGE' => $message,
				));
				//Echo everything
				include('includes/header.php');
				$main_body->pparse('simple_body');
				include('includes/footer.php');
			}
		}
	} else
	{
		if (isset($_GET['action']) && isset($_GET['pid']) && $_GET['action']=="accept"){
      $result = mysql_query("INSERT INTO " . $SETTINGS["TABLE_PREFIX"] . "belongs_to_project (user_id, project_id, user_status) VALUES (" . $_SESSION['user_id'] . ", " . $_GET['pid'] . ", 0)");
      $result = mysql_query("DELETE FROM " . $SETTINGS["TABLE_PREFIX"] . "invited_to_project WHERE user_id = " . $_SESSION['user_id'] . " AND project_id = " . $_GET['pid']);
		}else if (isset($_GET['action']) && isset($_GET['pid']) && $_GET['action']=="reject"){
      $result = mysql_query("DELETE FROM " . $SETTINGS["TABLE_PREFIX"] . "invited_to_project WHERE user_id = " . $_SESSION['user_id'] . " AND project_id = " . $_GET['pid']);
		}
		
		$result = mysql_query("SELECT * FROM " . $SETTINGS["TABLE_PREFIX"] . "projects P, " . $SETTINGS["TABLE_PREFIX"] . "users U WHERE P.creator_user_id = U.user_id AND P.creator_user_id = " . $_SESSION['user_id']);
		
		while ($row = mysql_fetch_assoc($result))
		{
			$main_body->assign_block_vars('project_row', Array(
						'PROJECT_ID' => $row['project_id'],
						'PROJECT_NAME' => $row['project_name'],
						'USER_ID' => $row['creator_user_id'],
						'USERNAME' => $row['username'],
						'CREATION_DATE' => date("m/d/Y", $row['creation_date'])
				));
		}
		$result = mysql_query("SELECT * FROM " . $SETTINGS["TABLE_PREFIX"] . "projects P, " . $SETTINGS["TABLE_PREFIX"] . "belongs_to_project B, " . $SETTINGS["TABLE_PREFIX"] . "users U WHERE P.creator_user_id != " . $_SESSION['user_id'] . " AND B.project_id = P.project_id AND B.user_id = U.user_id AND B.user_id = " . $_SESSION['user_id']);
		
		while ($row = mysql_fetch_assoc($result))
		{
			//To get the creator user name
			$row2 = mysql_fetch_assoc(mysql_query("SELECT * FROM " . $SETTINGS["TABLE_PREFIX"] . "projects P, " . $SETTINGS["TABLE_PREFIX"] . "users U WHERE project_id = " . $row['project_id'] . " AND P.creator_user_id = U.user_id"));
			
			$main_body->assign_block_vars('project_other_row', Array(
						'PROJECT_ID' => $row['project_id'],
						'PROJECT_NAME' => $row['project_name'],
						'USER_ID' => $row['creator_user_id'],
						'USERNAME' => $row2['username'],
						'CREATION_DATE' => date("m/d/Y", $row['creation_date'])
				));
		}
		
		//For pending invites to projects
		$result = mysql_query("SELECT * FROM " . $SETTINGS["TABLE_PREFIX"] . "projects P, " . $SETTINGS["TABLE_PREFIX"] . "invited_to_project I WHERE I.user_id = " . $_SESSION['user_id'] . " AND P.project_id = I.project_id");
		
		while ($row = mysql_fetch_assoc($result))
		{
			//To get the creator user name
			$row2 = mysql_fetch_assoc(mysql_query("SELECT * FROM " . $SETTINGS["TABLE_PREFIX"] . "projects P, " . $SETTINGS["TABLE_PREFIX"] . "users U WHERE project_id = " . $row['project_id'] . " AND P.creator_user_id = U.user_id"));
			
			$main_body->assign_block_vars('invited_row', Array(
						'PROJECT_ID' => $row['project_id'],
						'PROJECT_NAME' => $row['project_name'],
						'USER_ID' => $row['creator_user_id'],
						'USERNAME' => $row2['username'],
						'CREATION_DATE' => date("m/d/Y", $row['creation_date'])
				));
		}
		
		//Assign template variables
		$main_body->assign_vars(array(
			'EXAMPLE' => '',
			'VIEWING' => '',
		));	 
				 
		//Echo everything
		include('includes/header.php');
		$main_body->pparse('view_projects_body');
		include('includes/footer.php');
	}
	xconnect();
?>