<?php
	define('CS546', true);
	//This is the order all files must be included in each .PHP file
	include('includes/config.php');
	include('includes/functions.php');
	include('includes/settings.php');
	include('includes/sessions.php');
	include('includes/template.php');
	
	$SETTINGS["PAGE_TITLE"] = "Create New Project";
	
	//Init the templates
	$main_body = new Template($SETTINGS["TEMPLATE_DIR"]);
	$verify_body = new Template($SETTINGS["TEMPLATE_DIR"]);
	$account_success_body = new Template($SETTINGS["TEMPLATE_DIR"]);
	$main_body->set_filenames(array(
	 'create_project_body' => 'create_project_body.htm',
	 'simple_body' => 'simple_body.htm'
	));
	
	$message = '';
	$errors = '';
	//Prep vars to store everything in
	$form_project_name = '';
	$form_project_desc = '';
	
	//Start the database connection
	connect();
	
	//Check if the form was submitted, and build up the errors list if there is a problem
	if(!$_SESSION['is_logged_in'])
	{
		$message = 'You must login to create a new project.<br /><br /><a href="index.php">Return</a>';
		$main_body->assign_vars(array(
			'MESSAGE' => $message,
		));
		//Echo everything
		include('includes/header.php');
		$main_body->pparse('simple_body');
		include('includes/footer.php');
	} else
	{
		if(isset($_POST['formsubmit']) && isset($_POST['name']))
		{
			$form_project_name = addslashes(trim(preg_replace('/\s\s+/', ' ', $_POST['name'])));
			
			if(isset($_POST['desc']))
			{
				$form_project_desc = encode_string($_POST['desc']);
			}
			
			$row = mysql_fetch_assoc(mysql_query("SELECT * FROM " . $SETTINGS["TABLE_PREFIX"] . "projects WHERE project_name = '" . $form_project_name  . "' AND creator_user_id = " . $_SESSION['user_id']));
			if(!$row)
			{
				if(strlen($form_project_name) > 45)
				{
					$errors .= 'Error, Project name is too long, must contain 45 characters or less.<br />';
				}
				if(strlen($form_project_name) < 1)
				{
					$errors .= 'Error, Project name is too short. It must contain at least 1 character.<br />';
				}
				if(!preg_match('/^[A-Za-z0-9 _]+$/', $form_project_name))
				{
					$errors .= 'Error, Project name must only contain letters and numbers.<br />';
				}
			} else
			{
				$errors .= 'Error, you already created a project named "' . $form_project_name . '". Please use a different name.<br />';
			}
		}
		if(isset($_POST['formsubmit']) && isset($_POST['name']) && $errors == '')
		{
			//Add the project to the database
			$result = mysql_query("INSERT INTO ". $SETTINGS["TABLE_PREFIX"] . "projects (project_name, project_description, creation_date, creator_user_id) VALUES (" . values_list(array($form_project_name, $form_project_desc, time(), $_SESSION['user_id'])) . ")");
			
			$lastID = mysql_insert_id();
			
			//Don't forget the permissions info
			mysql_query("INSERT INTO ". $SETTINGS["TABLE_PREFIX"] . "belongs_to_project (user_id, project_id, user_status) VALUES (" . values_list(array($_SESSION['user_id'], $lastID, 1)) . ")");
			
			mkdir($SETTINGS["UPLOAD_DIR"] . '/icons/' . $lastID);
			
			$message = 'Project "' . $form_project_name . '" has been succesfully created! <br /><br /><a href="view_modules.php?pid=' . $lastID . '">Go to Project Page</a>';
					
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
				'NAME' => $form_project_name,
				'DESC' => decode_string($form_project_desc),
				'ERRORS' => $errors
			));
			//Echo everything
			include('includes/header.php');
			$main_body->pparse('create_project_body');
			include('includes/footer.php');
		}
		
	}
	xconnect();
?>