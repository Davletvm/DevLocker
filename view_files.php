<?php
	define('CS546', true);
	//This is the order all files must be included in each .PHP file
	include('includes/config.php');
	include('includes/functions.php');
	include('includes/settings.php');
	include('includes/sessions.php');
	include('includes/template.php');
	
	$SETTINGS["PAGE_TITLE"] = "Viewing Project Module Files";
	
	//Init the template (You can echo as many templates as listed in the filenames)
	$main_body = new Template($SETTINGS["TEMPLATE_DIR"]);
	$main_body->set_filenames(array(
	 'view_files_body' => 'view_files_body.htm',
	 'view_file_history_body' => 'view_file_history_body.htm',
	 'simple_body' => 'simple_body.htm'
	));
	
	
	if(!$_SESSION['is_logged_in'])
	{
		$message = 'You must login to view the files of a Project Module.<br /><br /><a href="index.php">Return</a>';
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
		$module_id = (isset($_GET['mid']) && is_numeric($_GET['mid'])) ? $_GET['mid'] : -1;
		$module_desc = '';
		$module_name = '';
		$project_id = -1;
		$project_name = '';
		$creator_name = '';
		$creator_id = '';
		
		//The information for the project entity
		$module_row = mysql_fetch_assoc(mysql_query("SELECT * FROM " . $SETTINGS["TABLE_PREFIX"] . "projects P, " . $SETTINGS["TABLE_PREFIX"] . "project_entities E WHERE P.project_id = E.project_id AND E.entity_id = " . $module_id));
		
		if(!$module_row)
		{
			$message = 'Module ID not found.<br /><br /><a href="index.php">Return</a>';
			$main_body->assign_vars(array(
				'MESSAGE' => $message,
			));
			//Echo everything
			include('includes/header.php');
			$main_body->pparse('simple_body');
			include('includes/footer.php');
		} else
		{
			//At this point we have the project module info, and just need to see if they have permission to view it
			$project_id = $module_row["project_id"];
			$project_name = $module_row["project_name"];
			$module_desc = $module_row["entity_description"];
			$module_name = $module_row["entity_name"];
			
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
				
				if(isset($_GET['action']) && ($_GET['action'] == "upload" || $_GET['action'] == "replace") && isset($_POST['formsubmit']))
				{
					$errors = '';
					
					$locked_row = mysql_fetch_assoc(mysql_query("SELECT * FROM " . $SETTINGS["TABLE_PREFIX"] . "project_entities E WHERE E.entity_id = " . $module_id . " AND E.locked_by_id = " . $_SESSION['user_id']));
					
					if(!$locked_row)
					{
						$message = 'Error, you must lock this module in order to upload files.<br /><br /><a href="view_files.php?mid=' . $module_id .'">Return</a>';
						$main_body->assign_vars(array(
							'MESSAGE' => $message,
						));
						//Echo everything
						include('includes/header.php');
						$main_body->pparse('simple_body');
						include('includes/footer.php');
					} else
					{
						//Handle the uploading of a new file
						if (isset($_FILES['ufile']['error']))
						{
							foreach ($_FILES["ufile"]["error"] as $key => $error)
							{
								//We only want to handle the first file in this case
								if ($key == 0)
								{
									if ($error == UPLOAD_ERR_OK)
									{
										if($_FILES["ufile"]["size"][$key] > $SETTINGS["UPLOAD_LIMIT"] * 1024 * 1024)
										{
											$message = 'Error, file size too large. (Limit = ' . $SETTINGS["UPLOAD_LIMIT"] . ' MB per file). <br /><br /><a href="view_files.php?mid=' . $module_id .'">Return</a>';
											$main_body->assign_vars(array(
												'MESSAGE' => $message,
											));
											//Echo everything
											include('includes/header.php');
											$main_body->pparse('simple_body');
											include('includes/footer.php');
											return;
										}
										$tmp_name = $_FILES["ufile"]["tmp_name"][$key];
										$name = $_FILES["ufile"]["name"][$key];
										
										//Downgrade the old file to be overwritten if we are performing an update
										if($_GET['action'] == "replace")
										{
											$file_id = (isset($_GET['fid']) && is_numeric($_GET['fid'])) ? $_GET['fid'] : -1;
											$file_row = mysql_fetch_assoc(mysql_query("SELECT * FROM " . $SETTINGS["TABLE_PREFIX"] . "file_reference F WHERE F.file_id = " . $file_id));
											if(!$file_row)
												die('No file to replace');
											
											//Make sure the file extensions match
											$ext1 = pathinfo($file_row['file_name']);
											$ext2 = pathinfo($name);
											if($ext1['extension'] != $ext2['extension'])
												die('Error, the updated file must match its original file extension. Please try again.');
											
											mysql_query("UPDATE ". $SETTINGS["TABLE_PREFIX"] . "file_reference SET overwritten = " . 1 . ", locked_by_id = 0 WHERE file_id = " . $file_id);
										}
										
										//Upload successfull, add file info to the database
										mysql_query("INSERT INTO ". $SETTINGS["TABLE_PREFIX"] . "file_reference (file_name, file_size, entity_id, creation_date, uploader_id) VALUES (" . values_list(array(addslashes($name), $_FILES["ufile"]["size"][$key], $module_id, time(), $_SESSION['user_id'])) . ")");
										$lastID = mysql_insert_id();
										
										//Update the origin file ID (Use the original origin ID if we are doing a replacement, otherwise make a new one since it would mean the user is uploading a new file)
										mysql_query("UPDATE ". $SETTINGS["TABLE_PREFIX"] . "file_reference SET origin_id = " . (($_GET['action'] == "replace") ? $file_row['origin_id'] : $lastID) . " WHERE file_id = " . $lastID);
										
										$uploaddir = "devlocker_files/" . $lastID;
										move_uploaded_file($tmp_name, $uploaddir);
									} else
									{
										//if ($_FILES['ufile']['error'][$key ] != 4)
										//{
											die("Error uploading files. Check file size and connection.");
										//}
									}
								}
							}
							//Now we need to refresh the file checked table
							$origin_id = (($_GET['action'] == "replace") ? $file_row['origin_id'] : $lastID);
							
							mysql_query("DELETE FROM ". $SETTINGS["TABLE_PREFIX"] . "file_checked WHERE file_id = " . $origin_id);
							
							$result = mysql_query("SELECT * FROM " . $SETTINGS["TABLE_PREFIX"] . "users U, " . $SETTINGS["TABLE_PREFIX"] . "belongs_to_project B WHERE U.user_id = B.user_id AND B.project_id = " . $project_id . ' AND U.user_id != ' . $_SESSION['user_id']);
							
							while ($user_row = mysql_fetch_assoc($result))
							{
								//Give them a new flag that must be flushed
								mysql_query("INSERT INTO ". $SETTINGS["TABLE_PREFIX"] . "file_checked (user_id, file_id) VALUES (" . values_list(array($user_row['user_id'], $origin_id)) . ")");
							}
							
							//Done
							if($_GET['action'] == "replace")
								$message = $name . ' was successfully updated.<br /><br /><a href="view_files.php?mid=' .$module_id . '">Return</a>';
							else
								$message = $name . ' was successfully uploaded.<br /><br /><a href="view_files.php?mid=' .$module_id . '">Return</a>';
							$main_body->assign_vars(array(
								'MESSAGE' => $message,
							));
							//Echo everything
							include('includes/header.php');
							$main_body->pparse('simple_body');
							include('includes/footer.php');
						} else
						{
							$message = 'No file uploaded.';
							$main_body->assign_vars(array(
								'MESSAGE' => $message,
							));
							//Echo everything
							include('includes/header.php');
							$main_body->pparse('simple_body');
							include('includes/footer.php');
						}
					}
				} else if(isset($_GET['action']) && $_GET['action'] == "delete")
				{
					$file_id = (isset($_GET['fid']) && is_numeric($_GET['fid'])) ? $_GET['fid'] : -1;
					$file_row = mysql_fetch_assoc(mysql_query("SELECT * FROM " . $SETTINGS["TABLE_PREFIX"] . "file_reference F WHERE F.file_id = " . $file_id));
					if(!$file_row)
					{
						$message = 'File does not exist.';
						$main_body->assign_vars(array(
							'MESSAGE' => $message,
						));
						//Echo everything
						include('includes/header.php');
						$main_body->pparse('simple_body');
						include('includes/footer.php');
					} else
					{
						//First delete all physical files on the disk
						$result = mysql_query("SELECT * FROM " . $SETTINGS["TABLE_PREFIX"] . "file_reference F WHERE F.origin_id = " . $file_row['origin_id']);
						
						while ($row = mysql_fetch_assoc($result))
							if(!(unlink($SETTINGS["UPLOAD_DIR"] . "/" . $row['file_id'])))
								die("Write permission error");
						
						//Remove table information
						mysql_query("DELETE FROM " . $SETTINGS["TABLE_PREFIX"] . "file_reference WHERE origin_id = " . $file_row['origin_id']);
						mysql_query("DELETE FROM ". $SETTINGS["TABLE_PREFIX"] . "file_checked WHERE file_id = " . $file_row['origin_id']);
						
						$message = 'File "' . $file_row['file_name'] . '" has been successfully deleted<br /><br /><a href="view_files.php?mid=' .$module_id . '">Return</a>';
						$main_body->assign_vars(array(
							'MESSAGE' => $message,
						));
						//Echo everything
						include('includes/header.php');
						$main_body->pparse('simple_body');
						include('includes/footer.php');
					}
				} else if(isset($_GET['action']) && $_GET['action'] == "history")
				{
					//Show the file history 
					$file_id = (isset($_GET['fid']) && is_numeric($_GET['fid'])) ? $_GET['fid'] : -1;
					$file_row = mysql_fetch_assoc(mysql_query("SELECT * FROM " . $SETTINGS["TABLE_PREFIX"] . "file_reference F WHERE F.file_id = " . $file_id));
					
					if(!$file_row)
					{
						$message = 'File does not exist.';
						$main_body->assign_vars(array(
							'MESSAGE' => $message,
						));
						//Echo everything
						include('includes/header.php');
						$main_body->pparse('simple_body');
						include('includes/footer.php');
					} else
					{
						//Assign template variables
						$main_body->assign_vars(array(
							'ENTITY_ID' => $module_id,
							'ENTITY_NAME' => $module_name,
							'ENTITY_DESC' => htmlspecialchars(decode_string($module_desc)),
							'PROJECT_ID' => $project_id,
							'PROJECT_NAME' => $project_name,
							'CREATOR_ID' => $creator_id,
							'CREATOR_NAME' => $creator_name 
						));
						
						//Display all the history of the files up to the latest
						
						$result = mysql_query("SELECT * FROM " . $SETTINGS["TABLE_PREFIX"] . "file_reference F WHERE F.origin_id = " . $file_row['origin_id'] . " ORDER BY F.creation_date DESC");
						$rev_num = mysql_num_rows(mysql_query("SELECT * FROM " . $SETTINGS["TABLE_PREFIX"] . "file_reference F WHERE F.origin_id = " . $file_row['origin_id']));
						
						while ($row = mysql_fetch_assoc($result))
						{
							//Get editor info
							$editor_row = mysql_fetch_assoc(mysql_query("SELECT * FROM " . $SETTINGS["TABLE_PREFIX"] . "users U, " . $SETTINGS["TABLE_PREFIX"] . "file_reference F WHERE F.uploader_id = U.user_id AND U.user_id = " . $row['uploader_id']));
							
							$main_body->assign_block_vars('file_row', Array(
										'FILE_ID' => $row['file_id'],
										'REV_NUM' => $rev_num--,
										'FILE_NAME' => stripslashes($row['file_name']),
										'FILE_SIZE' => round($row['file_size'] / 1000, 1) . ' KB',
										'EDIT_DATE' => date("m/d/Y g:ia", $row['creation_date']),
										'EDITOR_ID' => $editor_row['user_id'],
										'EDITOR_NAME' => $editor_row['username']
								));
						}
						
						//Echo everything
						include('includes/header.php');
						$main_body->pparse('view_file_history_body');
						include('includes/footer.php');
					}
				} else
				{
				  //If posting a comment...
					if (isset($_GET['action']) && $_GET['action'] == "comment")
					{
						if (isset($_POST['text']))
						{
						  $result = mysql_query("INSERT INTO " . $SETTINGS["TABLE_PREFIX"] . "project_entity_comments (entity_id, user_id, comment_text, comment_date) VALUES (" . $module_id . ", " . $_SESSION['user_id'] . ", '" . encode_string($_POST['text']) . "', " . time() . ")");
						  
						  //$lastID = mysql_insert_id();
						}
					}
					
					//Locking and unlocking
					if (isset($_GET['action']) && $_GET['action'] == "lock")
					{
						//Lock the entity if we have permission
						
						//The lock can go through if they are an admin of the project, or no one has the lock
						if($permissions_row['user_status'] == 1 || $module_row['locked_by_id'] === NULL)
						{
							mysql_query("UPDATE ". $SETTINGS["TABLE_PREFIX"] . "project_entities SET locked_by_id = " . $_SESSION['user_id'] . " WHERE entity_id = " . $module_id);
						}
					} else if (isset($_GET['action']) && $_GET['action'] == "unlock")
					{
						//Unlock the entity if we have permission
						
						//The unlock can go through if they are an admin of the project, or they already have the lock
						if($permissions_row['user_status'] == 1 || $module_row['locked_by_id'] == $_SESSION['user_id'])
						{
							mysql_query("UPDATE ". $SETTINGS["TABLE_PREFIX"] . "project_entities SET locked_by_id = NULL WHERE entity_id = " . $module_id);
						}
					}
					
					//Marking as checked
					if (isset($_GET['mark']) && $_GET['mark'] == "all")
					{
						$checked_result = mysql_query("SELECT * FROM " . $SETTINGS["TABLE_PREFIX"] . "project_entities E, " . $SETTINGS["TABLE_PREFIX"] . "file_reference F WHERE E.entity_id = F.entity_id AND E.entity_id = " . $module_id . " AND F.overwritten = " . 0);
						
						while ($checked_row = mysql_fetch_assoc($checked_result))
						{
							mysql_query("DELETE FROM ". $SETTINGS["TABLE_PREFIX"] . "file_checked WHERE user_id = " .  $_SESSION['user_id'] . " AND file_id = " . $checked_row['origin_id']);
						}
					} else if(isset($_GET['mark']) && is_numeric($_GET['mark']))
					{
						mysql_query("DELETE FROM ". $SETTINGS["TABLE_PREFIX"] . "file_checked WHERE user_id = " .  $_SESSION['user_id'] . " AND file_id = " . $_GET['mark']);
					}
					
					//Just display the normal page
					$locked_row = mysql_fetch_assoc(mysql_query("SELECT * FROM " . $SETTINGS["TABLE_PREFIX"] . "project_entities E WHERE E.entity_id = " . $module_id . " AND E.locked_by_id = " . $_SESSION['user_id']));
					
					//First prep the lock text
					$other_upload_txt = 'Requires lock';
					$other_upload_txt2 = 'You must have the lock to upload files';
					$lock_status = '';
					$lock_link = '';
					//We need to update the module row
					$module_row = mysql_fetch_assoc(mysql_query("SELECT * FROM " . $SETTINGS["TABLE_PREFIX"] . "projects P, " . $SETTINGS["TABLE_PREFIX"] . "project_entities E WHERE P.project_id = E.project_id AND E.entity_id = " . $module_id));
					if($module_row['locked_by_id'] === NULL)
					{
						$lock_status = 'Free';
						$lock_link = '(<a href="view_files.php?mid=' . $module_id . '&action=lock">Lock Module</a> )';
					} else
					{
						$lock_status = 'Locked';
						if($module_row['locked_by_id'] == $_SESSION['user_id'])
						{
							$other_upload_txt = '';
							$other_upload_txt2 = '';
							$main_body->assign_block_vars('can_add_file', Array());
							//If claimed show them unlock
							$lock_link = '( <a href="view_files.php?mid=' . $module_id . '&action=unlock">Unlock Module</a> )';
						} else if($permissions_row['user_status'] == 1 && $module_row['locked_by_id'] != $_SESSION['user_id'])
						{
							//If admin show them force-unlock
							$lock_link = '( <a href="view_files.php?mid=' . $module_id . '&action=unlock">Force Unlock Module</a> )';
						}
					}
					
					//Display all of the files associated with this entity
					
					$mark_all_btn = '';
					$result = mysql_query("SELECT * FROM " . $SETTINGS["TABLE_PREFIX"] . "project_entities E, " . $SETTINGS["TABLE_PREFIX"] . "file_reference F WHERE E.entity_id = F.entity_id AND E.entity_id = " . $module_id . " AND F.overwritten = " . 0);
					$no_files = true;
					while ($row = mysql_fetch_assoc($result))
					{
						$no_files = false;
						$other_upload_txt = 'Requires lock';
						if($locked_row)
						{
							$other_upload_txt = '';
						}
						//Get editor info 
						$editor_row = mysql_fetch_assoc(mysql_query("SELECT * FROM " . $SETTINGS["TABLE_PREFIX"] . "users U, " . $SETTINGS["TABLE_PREFIX"] . "file_reference F WHERE F.uploader_id = U.user_id AND U.user_id = " . $row['uploader_id']));
						
						//Get last-checked info
						$checked_status = '';
						$checked_row = mysql_fetch_assoc(mysql_query("SELECT * FROM " . $SETTINGS["TABLE_PREFIX"] . "file_checked C WHERE C.user_id = " . $_SESSION['user_id'] . " AND C.file_id = " . $row['origin_id']));
						if($checked_row)
						{
							$checked_status = '<a href="view_files.php?mid=' . $module_id . '&mark=' . $row['origin_id'] . '" onclick="return confirm(\'Mark &quot;' . $row['file_name'] . '&quot; as checked?\')"><img src="images/warning.png" alt="This file was recently updated" /></a>';
							$mark_all_btn = '<a href="view_files.php?mid=' . $module_id . '&mark=all" onclick="return confirm(\'Mark all files as checked?\')">Mark all files as checked</a><br /><br />';
						}
						$rev_count = mysql_num_rows(mysql_query("SELECT * FROM " . $SETTINGS["TABLE_PREFIX"] . "file_reference F WHERE F.origin_id = " . $row['origin_id']));
						
						$main_body->assign_block_vars('file_row', Array(
									'CHECKED_STATUS' => $checked_status,
									'FILE_ID' => $row['file_id'],
									'FILE_NAME' => $row['file_name'],
									'FILE_SIZE' => round($row['file_size'] / 1000, 1) . ' KB',
									'FILE_REVISIONS' => $rev_count,
									'EDIT_DATE' => date("m/d/Y g:ia", $row['creation_date']),
									'EDITOR_ID' => $editor_row['user_id'],
									'EDITOR_NAME' => $editor_row['username'],
									'LOCKED_BY' => '?',
									'OTHER_UPLOAD_TXT' => $other_upload_txt
							));
						if($locked_row)
						{
							$main_body->assign_block_vars('file_row.upload_row', Array());
						}
					}
					
					if($no_files)
						$main_body->assign_block_vars('no_files', Array());
					
					//For the icon changing
					$icon_error = '';
					if($permissions_row['user_status'] == 1)
					{
						$main_body->assign_block_vars('can_change_icon', Array());
						if(isset($_GET['action']) && $_GET['action'] == "icon" && isset($_POST['formsubmit']))
						{
							foreach ($_FILES["ifile"]["error"] as $key => $error)
							{
								//We only want to handle the first file in this case
								if ($key == 0)
								{
									if ($error == UPLOAD_ERR_OK)
									{
										$tmp_name = $_FILES["ifile"]["tmp_name"][$key];
										$name = $_FILES["ifile"]["name"][$key];
										$uploaddir = "devlocker_files/icons/" . $project_id . "/" . $module_id;
										
										//Make sure the file extensions are images
										$ext1 = pathinfo($name);
										
										if($ext1['extension'] != 'jpg' && $ext1['extension'] != 'gif' && $ext1['extension'] != 'png')
										{
											$icon_error = '<span class="error">Image must be .jpg, .gif, or .png only.</span>';
											break;
										}
										
										$size = getimagesize($tmp_name);
										if($size[0] > 100 || $size[1] > 100)
										{
											$icon_error = '<span class="error">Image size must not exceed 100x100 pixels.</span>';
											break;
										}
										
										if(file_exists($uploaddir))
										{
											unlink($uploaddir);
										}
										move_uploaded_file($tmp_name, $uploaddir);
									} else
									{
										$icon_error = '<span class="error">No icon was uploaded</span>';
										break;
									}
								}
							}
						}
					}
					
					//Assign template variables
					$main_body->assign_vars(array(
						'ENTITY_ID' => $module_id,
						'ENTITY_NAME' => $module_name,
						'ENTITY_DESC' => htmlspecialchars(decode_string($module_desc)),
						'PROJECT_ID' => $project_id,
						'PROJECT_NAME' => $project_name,
						'CREATOR_ID' => $creator_id,
						'CREATOR_NAME' => $creator_name,
						'OTHER_UPLOAD_TXT' => $other_upload_txt,
						'MARK_ALL_BTN' => $mark_all_btn,
						'LOCK_STATUS' => $lock_status,
						'LOCK_LINK' => $lock_link,
						'ICON_ERROR' => $icon_error
					));
					
					
					//Set comment stuff
					$result = mysql_query("SELECT C.comment_text, C.comment_date, C.user_id, U.username FROM " . $SETTINGS["TABLE_PREFIX"] . "project_entities M, " . $SETTINGS["TABLE_PREFIX"] . "project_entity_comments C, " . $SETTINGS["TABLE_PREFIX"] . "users U WHERE M.entity_id = C.entity_id AND M.entity_id = " . $module_id . " AND U.user_id = C.user_id");
					while ($comment = mysql_fetch_assoc($result)){
            $main_body->assign_block_vars('comment', Array(
                'COMMENTER' => $comment['username'],
                'COMMENTER_ID' => $comment['user_id'],
                'TEXT' => $comment['comment_text'],
                'DATE' =>  date("M j, Y @ g:ia", $comment['comment_date'])
              ));
					}
					
					//Echo everything
					include('includes/header.php');
					$main_body->pparse('view_files_body');
					include('includes/footer.php');
				}
			} 
		}
	}
?>