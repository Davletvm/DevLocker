<?php
	define('CS546', true);
	//This is the order all files must be included in each .PHP file
	include('includes/config.php');
	include('includes/functions.php');
	include('includes/settings.php');
	include('includes/sessions.php');
	include('includes/template.php');
	
	$SETTINGS["PAGE_TITLE"] = "Manage Users";
	
	//Init the template (You can echo as many templates as listed in the filenames)
	$main_body = new Template($SETTINGS["TEMPLATE_DIR"]);
	$main_body->set_filenames(array(
	 'permission_body' => 'permissions.htm',
	 'simple_body' => 'simple_body.htm'
	));
	
	if(!$_SESSION['is_logged_in'])
	{
		$message = 'You must login to view Project Modules.<br /><br /><a href="index.php">Return</a>';
		$main_body->assign_vars(array(
			'MESSAGE' => $message,
		));
		//Echo everything
		include('includes/header.php');
		$main_body->pparse('simple_body');
		include('includes/footer.php');
	}
	else
	{
		connect();
		$project_id = (isset($_GET['pid']) && is_numeric($_GET['pid'])) ? $_GET['pid'] : -1;
		$project_name = '';
		$project_desc = '';
		$creator_name = '';
		$creator_id = '';
		
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
		} 
		else
		{
			//At this point we have the project info, and just need to see if they have permission to view it
			$project_name = $project_row["project_name"];
			$creator_id = $project_row["creator_user_id"];
			$creator_name = $project_row["username"];
			$project_desc = $project_row["project_description"];
			
			//If they created the project or belong to it
			$permissions_row = mysql_fetch_assoc(mysql_query("SELECT * FROM " . $SETTINGS["TABLE_PREFIX"] . "projects P, " . $SETTINGS["TABLE_PREFIX"] . "users U, " . $SETTINGS["TABLE_PREFIX"] . "belongs_to_project B WHERE P.project_id = " . $project_id . " AND B.project_id = P.project_id AND B.user_id = " . $_SESSION['user_id']." AND B.user_status = 1"));
			
			if(!$permissions_row)
			{
				$message = 'Access Denied.<br /><br /><a href="index.php">Return</a>';
				$main_body->assign_vars(array(
					'MESSAGE' => $message,
				));
				//Echo everything
				include('includes/header.php');
				$main_body->pparse('simple_body');
				include('includes/footer.php');
			} else
			{
				if (isset($_POST['permission'])&& isset($_POST['per']) && isset($_POST['id']))
				{
					connect();
					for($i = 0; $i < count($_POST['per']); $i ++)
					{
						if($_POST['per'][$i] != 2)
						{
							$query = "UPDATE ".$SETTINGS['TABLE_PREFIX']."belongs_to_project SET user_status = ".$_POST['per'][$i]." WHERE project_id = ".$_GET['pid']." AND user_id = ".$_POST['id'][$i];
							mysql_query($query);
						}
						else
						{
							$query = "DELETE FROM ".$SETTINGS['TABLE_PREFIX']."belongs_to_project WHERE project_id = ".$_GET['pid']." AND user_id = ".$_POST['id'][$i];
							mysql_query($query);
						}
					}
					xconnect();
					$message = "Permissions Updated Successfully<br /><br /><a href='view_modules.php?pid=".$_GET['pid']."'>Return to View Projects Page</a>";
					$main_body->assign_vars(array(
						'MESSAGE' => $message,
					));
					//Echo everything
					include('includes/header.php');
					$main_body->pparse('simple_body');
					include('includes/footer.php');
					
					
				}
				else
				{
					connect();
					//information of who the has permissions and what permissions they have on the project
					$query = "SELECT u.user_id, u.username, b.project_id, b.user_status FROM ".$SETTINGS['TABLE_PREFIX']."belongs_to_project  b, ".$SETTINGS['TABLE_PREFIX']."users u,".$SETTINGS['TABLE_PREFIX']."projects p WHERE u.user_id = b.user_id AND b.project_id = ".$_GET['pid']." AND p.project_id = ".$_GET['pid']." AND p.creator_user_id <> u.user_id AND u.user_id <> ".$_SESSION['user_id'];
					$result = mysql_query($query) or die(mysql_error());
					
					//The case of invalid results and no permissions to edit
					if(mysql_num_rows($result) <= 0)
					{
						xconnect();
						$message = "No Permissions to Edit<br /><br /><a href='view_modules.php?pid=".$_GET['pid']."'>Return to View Projects Page</a>";
						$main_body->assign_vars(array(
							'MESSAGE' => $message,
						));
						//Echo everything
						include('includes/header.php');
						$main_body->pparse('simple_body');
						include('includes/footer.php');
					}
					else
					{	
						//loops through all the names to display them
						while($row = mysql_fetch_assoc($result))
						{
							$permission = "<select name ='per[]'>";
												if($row['user_status']==0)
												{
													$permission .= "<option value = '0'selected = 'selected'>Basic User</option>
															  <option value = '1'>Administrator</option>";
															  
												}
												else
												{
													$permission .= "<option value = '0'>Basic User</option>
															  <option value = '1' selected = 'selected'>Administrator</option>";
												}
												if($_SESSION['user_id'] == $creator_id)
												{
													$permission .= "<option value = '2'>Delete User</option>";										
												}
												$permission .= " </select>";
							$id = "<input type = 'hidden' value = ".$row['user_id']." name = 'id[]'/>";
							$main_body->assign_block_vars('per_row', Array(
												'NAME' => $row['username'],
												'PERMISSION' => $permission,
												'ID' => $id
										));
																		
						}
						xconnect();
						//Echo everything
						include('includes/header.php');
						$main_body->pparse('permission_body');
						include('includes/footer.php');
					}
				}
			}
		}
	}
	
	
		
?>