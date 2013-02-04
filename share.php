<?PHP
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
	 'share_body' => 'share.htm',
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
				if (isset($_GET['pid']) && isset($_POST['share']) && $permissions_row['user_status'] == 1)
				{
					
					$users = explode(",", $_POST['emails']);
					for($i = 0; $i < count($users);$i++)
					{	
						$email = trim($users[$i]);
						if(!preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/', $email))
						{
							$message = 'Error, invalid email address.<br />';
							$main_body->assign_vars(array(
								'PROJECT' => $project_name,
								'ID' => $_GET['pid'],
								'MESSAGE' => $message,
								'EMAILS' => $_POST['emails']
								));
							//Echo everything
							include('includes/header.php');
							$main_body->pparse('share_body');
							include('includes/footer.php');
							die();
						} else if(mysql_num_rows($result = mysql_query("SELECT * FROM " . $SETTINGS["TABLE_PREFIX"] . "users WHERE email = '" . $email . "'")) > 0)
						{
							
							$result = mysql_fetch_assoc($result);
							if($result['user_id'] != $_SESSION['user_id'])
							{
								$query = "INSERT INTO ".$SETTINGS["TABLE_PREFIX"]."invited_to_project VALUES (".$_GET['pid'].", ".$result['user_id'].") ";
								mysql_query($query);
								$subject = "You have been invited to a project";
								$message = $_SESSION['username']." has invited you to share with the project: ".$project_name." \n Please Sign in to accept invitation. \n Thank you! \n DEVLocker";
								sendMail($email, $subject, $message);
								$message = 'Emails have been sent to requested People.<br /><br /><a href="index.php">Return</a>';
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
								$message = 'Sorry you can\'t invite yourself to this project<br /><br />';
									$main_body->assign_vars(array(
									'PROJECT' => $project_name,
									'ID' => $_GET['pid'],
									'MESSAGE' => $message,
									'EMAILS' => $_POST['emails']
									));
								//Echo everything
								include('includes/header.php');
								$main_body->pparse('share_body');
								include('includes/footer.php');
								die();
							}
						}
						else
						{
							$message = 'Sorry one of the email address you provided does not have an account<br /><br />';
								$main_body->assign_vars(array(
								'PROJECT' => $project_name,
								'ID' => $_GET['pid'],
								'MESSAGE' => $message,
								'EMAILS' => $_POST['emails']
								));
							//Echo everything
							include('includes/header.php');
							$main_body->pparse('share_body');
							include('includes/footer.php');
							die();
						}
						
					}
					
					
				}
				else if (isset($_GET['pid']) && $permissions_row['user_status'] == 1)
				{
					$main_body->assign_vars(array(
					'PROJECT' => $project_name,
					'ID' => $_GET['pid']
					));
					//Echo everything
					include('includes/header.php');
					$main_body->pparse('share_body');
					include('includes/footer.php');
				} 
				else
				{
					$message = 'Access Denied.<br /><br /><a href="index.php">Return</a>';
					$main_body->assign_vars(array(
						'MESSAGE' => $message,
					));
					//Echo everything
					include('includes/header.php');
					$main_body->pparse('simple_body');
					include('includes/footer.php');
				}
				
			}
		}
	}
	
?>