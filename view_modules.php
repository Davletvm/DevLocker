<?php
	define('CS546', true);
	//This is the order all files must be included in each .PHP file
	include('includes/config.php');
	include('includes/functions.php');
	include('includes/settings.php');
	include('includes/sessions.php');
	include('includes/template.php');
	
	$SETTINGS["PAGE_TITLE"] = "Viewing Modules";
	
	//Init the template (You can echo as many templates as listed in the filenames)
	$main_body = new Template($SETTINGS["TEMPLATE_DIR"]);
	$main_body->set_filenames(array(
	 'view_modules_body' => 'view_modules_body.htm',
	 'create_module_body' => 'create_module_body.htm',
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
	} else
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
		} else
		{
			//At this point we have the project info, and just need to see if they have permission to view it
			$project_name = $project_row["project_name"];
			$creator_id = $project_row["creator_user_id"];
			$creator_name = $project_row["username"];
			$project_desc = $project_row["project_description"];
			
			//If they created the project or belong to it
			$permissions_row = mysql_fetch_assoc(mysql_query("SELECT * FROM " . $SETTINGS["TABLE_PREFIX"] . "projects P, " . $SETTINGS["TABLE_PREFIX"] . "users U, " . $SETTINGS["TABLE_PREFIX"] . "belongs_to_project B WHERE P.project_id = " . $project_id . " AND B.project_id = P.project_id AND B.user_id = " . $_SESSION['user_id']));
			
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
				//Success in loading the project details. Now let's check to see if there is some defined action
				
				if(isset($_GET['action']) && $_GET['action'] == "add" && $permissions_row['user_status'] == 1)
				{
					//Show the add new project module form
					
					$message = '';
					$errors = '';
					//Prep vars to store everything in
					$form_module_name = '';
					$form_module_desc = '';
					
					if(isset($_POST['formsubmit']) && isset($_POST['name']))
					{
						$form_module_name = addslashes(trim(preg_replace('/\s\s+/', ' ', $_POST['name'])));
						
						if(isset($_POST['desc']))
						{
							$form_module_desc = encode_string($_POST['desc']);
						}
						
						$row = mysql_fetch_assoc(mysql_query("SELECT * FROM " . $SETTINGS["TABLE_PREFIX"] . "projects WHERE project_name = '" . $form_module_name  . "' AND creator_user_id = " . $_SESSION['user_id']));
						if(!$row)
						{
							if(strlen($form_module_name) > 45)
							{
								$errors .= 'Error, Project name is too long, must contain 45 characters or less.<br />';
							}
							if(strlen($form_module_name) < 1)
							{
								$errors .= 'Error, Project name is too short. It must contain at least 1 character.<br />';
							}
							if(!preg_match('/^[A-Za-z0-9 _]+$/', $form_module_name))
							{
								$errors .= 'Error, Project name must only contain letters and numbers.<br />';
							}
						} else
						{
							$errors .= 'Error, you already created a project named "' . $form_module_name . '". Please use a different name.<br />';
						}
					}
					if(isset($_POST['formsubmit']) && isset($_POST['name']) && $errors == '')
					{
						//Add the project module to the database
						$result = mysql_query("INSERT INTO ". $SETTINGS["TABLE_PREFIX"] . "project_entities (entity_name, entity_description, project_id) VALUES (" . values_list(array($form_module_name, $form_module_desc, $project_id)) . ")");
						
						$lastID = mysql_insert_id();
			
						$message = 'Module "' . $form_module_name . '" has been succesfully created!  <br /><br /><a href="view_files.php?mid=' . $lastID . '">Go to Module Page</a><br /><a href="view_modules.php?pid=' . $project_id . '">Back to Project Page</a>';
						
						mkdir($SETTINGS["UPLOAD_DIR"] . '/icons/' . $project_id . '/' . $lastID);
						
						$main_body->assign_vars(array(
							'MESSAGE' => $message
						));
						//Echo everything
						include('includes/header.php');
						$main_body->pparse('simple_body');
						include('includes/footer.php');
					} else
					{
						$main_body->assign_vars(array(
							'MESSAGE' => $message,
							'ENTITY_DESC' =>  htmlspecialchars(decode_string($project_desc)),
							'PROJECT_ID' => $project_id,
							'NAME' => $form_module_name,
							'DESC' => decode_string($form_module_desc),
							'ERRORS' => $errors
						));
						//Echo everything
						include('includes/header.php');
						$main_body->pparse('create_module_body');
						include('includes/footer.php');
					}
				} else if(isset($_GET['action']) && $_GET['action'] == "delete")
				{
					if($permissions_row['user_status'] == 1)
					{
						$module_id = (isset($_GET['mid']) && is_numeric($_GET['mid'])) ? $_GET['mid'] : -1;
						
						$result = mysql_query("SELECT * FROM ". $SETTINGS["TABLE_PREFIX"] . "file_reference F, ". $SETTINGS["TABLE_PREFIX"] . "project_entities E WHERE F.entity_id = E.entity_id AND F.entity_id = " . $module_id . " AND E.project_id = " . $project_id);
						
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
						mysql_query("DELETE FROM " . $SETTINGS["TABLE_PREFIX"] . "project_entities WHERE entity_id = " . $module_id);
						
						rrmdir($SETTINGS["UPLOAD_DIR"] . '/icons/' . $project_id . '/' . $module_id);
						
						$message = 'Module "' . $row['entity_name'] . '" has been succesfully deleted.<br /><br /><a href="view_modules.php?pid="' . $project_id . '">Return</a>';
						$main_body->assign_vars(array(
							'MESSAGE' => $message,
						));
						//Echo everything
						include('includes/header.php');
						$main_body->pparse('simple_body');
						include('includes/footer.php');
					} else
					{
						$message = 'Nice try, you don\'t have admin permissions on this Project.<br /><br /><a href="index.php">Return</a>';
						$main_body->assign_vars(array(
							'MESSAGE' => $message,
					));
					//Echo everything
					include('includes/header.php');
					$main_body->pparse('simple_body');
					include('includes/footer.php');
					}
				} else
				{
					//If posting a comment...
					if (isset($_GET['action']) && $_GET['action'] == "comment")
					{
						if (isset($_POST['text']))
						{
						  $result = mysql_query("INSERT INTO " . $SETTINGS["TABLE_PREFIX"] . "project_comments (project_id, user_id, comment_text, comment_date) VALUES (" . $project_id . ", " . $_SESSION['user_id'] . ", '" . encode_string($_POST['text']) . "', " . time() . ")");
						  
						  //$lastID = mysql_insert_id();
						}
					}
					
					//Locking and unlocking
					if (isset($_GET['action']) && $_GET['action'] == "lock")
					{
						//Lock the entity if we have permission
						$module_id = (isset($_GET['mid']) && is_numeric($_GET['mid'])) ? $_GET['mid'] : -1;
						
						$module_row = mysql_fetch_assoc(mysql_query("SELECT * FROM " . $SETTINGS["TABLE_PREFIX"] . "projects P, " . $SETTINGS["TABLE_PREFIX"] . "project_entities E WHERE P.project_id = E.project_id AND E.entity_id = " . $module_id));
						
						if($module_row)
						{
							//The lock can go through if they are an admin of the project, or no one has the lock
							if($permissions_row['user_status'] == 1 || $module_row['locked_by_id'] === NULL)
							{
								mysql_query("UPDATE ". $SETTINGS["TABLE_PREFIX"] . "project_entities SET locked_by_id = " . $_SESSION['user_id'] . " WHERE entity_id = " . $module_id);
							}
						}
					} else if (isset($_GET['action']) && $_GET['action'] == "unlock")
					{
						//Unlock the entity if we have permission
						$module_id = (isset($_GET['mid']) && is_numeric($_GET['mid'])) ? $_GET['mid'] : -1;
						
						$module_row = mysql_fetch_assoc(mysql_query("SELECT * FROM " . $SETTINGS["TABLE_PREFIX"] . "projects P, " . $SETTINGS["TABLE_PREFIX"] . "project_entities E WHERE P.project_id = E.project_id AND E.entity_id = " . $module_id));
						
						if($module_row)
						{
							//The unlock can go through if they are an admin of the project, or they already have the lock
							if($permissions_row['user_status'] == 1 || $module_row['locked_by_id'] == $_SESSION['user_id'])
							{
								mysql_query("UPDATE ". $SETTINGS["TABLE_PREFIX"] . "project_entities SET locked_by_id = NULL WHERE entity_id = " . $module_id);
							}
						}
					}
					
				
					//Now just display the normal page
				
					//Assign template variables
					$main_body->assign_vars(array(
						'PROJECT_ID' => $project_id,
						'PROJECT_NAME' => $project_name,
						'CREATOR_ID' => $creator_id,
						'CREATOR_NAME' => $creator_name 
					));
					
					//Display links to just the modules that I own
					$result = mysql_query("SELECT * FROM " . $SETTINGS["TABLE_PREFIX"] . "projects P, " . $SETTINGS["TABLE_PREFIX"] . "project_entities E WHERE P.project_id = E.project_id AND P.project_id = " . $project_id . " AND E.locked_by_id = " . $_SESSION['user_id']);
					$turnOn = true;
					while ($row = mysql_fetch_assoc($result))
					{
						if($turnOn)
						{
							$main_body->assign_block_vars('have_module', Array());
							$turnOn = false;
						}
						$main_body->assign_block_vars('have_module.my_module_row', Array(
								'MODULE_ID' => $row['entity_id'],
								'MODULE_NAME' => $row['entity_name']
						));
						if($permissions_row['user_status'] == 1)
						{
							$main_body->assign_block_vars('have_module.my_module_row.my_switch_admin', Array());
						}
					}
					
					//Display links to all of the project modules
					$result = mysql_query("SELECT * FROM " . $SETTINGS["TABLE_PREFIX"] . "projects P, " . $SETTINGS["TABLE_PREFIX"] . "project_entities E WHERE P.project_id = E.project_id AND P.project_id = " . $project_id . " AND (E.locked_by_id IS NULL OR E.locked_by_id != " . $_SESSION['user_id'] . ")");
							
					while ($row = mysql_fetch_assoc($result))
					{
						$is_locked = '';
						$locked_by = '-';
						$locked_id = $row['locked_by_id'];
						$locked_txt = '';
						$other_locked_txt = '';
						$lock_action = '';
						if($locked_id === NULL)
						{
							$locked_txt = 'Lock';
							$lock_action = 'lock';
							$is_locked = 'No';
						} else
						{
							//Get the user name of the person who locked this
							$locked_result = mysql_fetch_assoc(mysql_query("SELECT * FROM " . $SETTINGS["TABLE_PREFIX"] . "users U WHERE U.user_id = " . $locked_id));
							$locked_by = '<a href="view_profile.php?uid=' . $locked_result['user_id'] . '">' . $locked_result['username'] . '</a>';
							
							//And the unlock options depending on status
							$is_locked = 'Yes';
							$lock_action = 'lock';
							if($locked_id == $_SESSION['user_id'])
							{
								$locked_txt = 'Unlock';
							} else if($permissions_row['user_status'] == 1)
							{
								$locked_txt = 'Force Unlock';
							} else 
							{
								//Can't unlock
								$other_locked_txt = '-';
							}
							$lock_action = 'unlock';
						}
						$main_body->assign_block_vars('module_row', Array(
									'MODULE_ID' => $row['entity_id'],
									'MODULE_NAME' => $row['entity_name'],
									'LOCKED' => $is_locked,
									'LOCKED_BY' => $locked_by,
									'LOCK_TXT' => $locked_txt,
									'LOCK_ACTION' => $lock_action,
									'OTHER_LOCK_TXT' =>$other_locked_txt
							));
						if($permissions_row['user_status'] == 1)
						{
							$main_body->assign_block_vars('module_row.switch_admin', Array());
						} else
						{
							$main_body->assign_block_vars('module_row.switch_normal', Array());
						}
					}
					
					//Display links to all of the users
					$result = mysql_query("SELECT * FROM " . $SETTINGS["TABLE_PREFIX"] . "users U, " . $SETTINGS["TABLE_PREFIX"] . "belongs_to_project B WHERE U.user_id = B.user_id AND B.project_id = " . $project_id);
					
					while ($row = mysql_fetch_assoc($result))
					{
						$main_body->assign_block_vars('user_row', Array(
							  'USERNAME' => $row['username'],
							  'UID' => $row['user_id']
						));
					}
					
					if($permissions_row['user_status'] == 1)
					{
						$main_body->assign_block_vars('switch_add_module', Array());
					}
					
					//This checks to see if the user is the owner of the project. When 'admins' are enabled, we should make it so that this happens whenever the user is an admin of the project.
					if ($_SESSION['user_id'] == $creator_id || $permissions_row['user_status'] == 1){
            $main_body->assign_block_vars('user_is_owner', Array());
					}
					
					//Set comment stuff
					$result = mysql_query("SELECT C.comment_text, C.comment_date, C.user_id, U.username FROM " . $SETTINGS["TABLE_PREFIX"] . "projects P, " . $SETTINGS["TABLE_PREFIX"] . "project_comments C, " . $SETTINGS["TABLE_PREFIX"] . "users U WHERE P.project_id = C.project_id AND P.project_id = " . $project_id . " AND U.user_id = C.user_id");
					while ($comment = mysql_fetch_assoc($result)){
            $main_body->assign_block_vars('comment', Array(
                'COMMENTER' => $comment['username'],
                'COMMENTER_ID' => $comment['user_id'],
                'TEXT' => htmlspecialchars(decode_string($comment['comment_text'])),
                'DATE' =>  date("M j, Y @ g:ia", $comment['comment_date'])
              ));
					}
					
					//Echo everything
					include('includes/header.php');
					$main_body->pparse('view_modules_body');
					include('includes/footer.php');
				}
			} 
		}
		xconnect();
	}
?>