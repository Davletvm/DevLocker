<?php
	define('CS546', true);
	//This is the order all files must be included in each .PHP file
	include('includes/config.php');
	include('includes/functions.php');
	include('includes/settings.php');
	include('includes/sessions.php');
	include('includes/template.php');
	
	$SETTINGS["PAGE_TITLE"] = "Index Page";
	
	//Init the template (You can echo as many templates as listed in the filenames)
	$main_body = new Template($SETTINGS["TEMPLATE_DIR"]);
	$main_body->set_filenames(array(
	 'index_body' => 'index_body.htm',
	 'simple_body' => 'simple_body.htm'
	));
	
	if(isset($_POST['login']))
	{
		//Default error message
		$my_text = 'Invalid login information, please go back and try again.<br /><br /><a href="index.php">Return</a>';
		if(isset($_POST['user']) && isset($_POST['pass']))
		{
			//Get the user/pass from the POST data
			$username = addslashes($_POST['user']);
			$password = addslashes($_POST['pass']);
			
			//Connect to the DB and find the user
			connect();
			$row = mysql_fetch_assoc(mysql_query("SELECT * FROM " . $SETTINGS["TABLE_PREFIX"] . "users WHERE username = '" . $username . "' AND password = '" . md5($password) . "'"));
			if($row)
			{
				//If the row exists, update the DB session log to match this user
				$_SESSION['user_id'] = $row["user_id"];
				mysql_query("UPDATE ". $SETTINGS["TABLE_PREFIX"] . "sessions SET session_user_id  = " . $_SESSION['user_id'] . " WHERE session_id = '" . session_id() . "'");
				mysql_query("UPDATE ". $SETTINGS["TABLE_PREFIX"] . "sessions SET session_logged_in = " . 1 . " WHERE session_id = '" . session_id() . "'");
				//Update the actual session data
				fetchSessionData(session_id());
				$my_text = 'Welcome, ' . $_SESSION["username"] . "!";
			}
			xconnect();
		}
	
		header("Location: view_projects.php");
	} else if (isset($_GET['logout']))
	{
		connect();
		mysql_query("DELETE FROM ". $SETTINGS["TABLE_PREFIX"] . "sessions WHERE session_id = " . session_id());
		mysql_query("DELETE FROM ". $SETTINGS["TABLE_PREFIX"] . "sessions WHERE session_user_id = " . $_SESSION["user_id"]);
		resetSessionData();
		xconnect();
		$my_text = 'Logout successful.<br /><br /><a href="index.php">Return</a>';
		$main_body->assign_vars(array(
			'MESSAGE' => $my_text,
		));	 
		include('includes/header.php');
		$main_body->pparse('simple_body');
		include('includes/footer.php');
	} else if ((bool) $_SESSION['is_logged_in'])
	{
		header("Location: view_projects.php");
	} else
	{
		if (isset($_GET["forgotPass"])){
			$main_body->assign_block_vars('forgot_password', Array());
		}
		if (isset($_GET["user"])){
			$main_body->assign_block_vars('recovered_password', Array());
		}
				 
		//Echo everything
		include('includes/header.php');
		$main_body->pparse('index_body');
		include('includes/footer.php');
	}
?>