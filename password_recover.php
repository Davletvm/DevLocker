<?php
	define('CS546', true);
	//This is the order all files must be included in each .PHP file
	include('includes/config.php');
	include('includes/functions.php');
	include('includes/settings.php');
	include('includes/sessions.php');
	include('includes/template.php');
	
	$SETTINGS["PAGE_TITLE"] = "Password Recover";
	
	//Init the template (You can echo as many templates as listed in the filenames)
	$main_body = new Template($SETTINGS["TEMPLATE_DIR"]);
	$main_body->set_filenames(array(
	 'password_recover_body' => 'password_recover_body.htm'
	));
	
	connect();
	$my_text = "You are viewing the index page.<br />Your session ID is: " . session_id();
	xconnect();
	
	//Assign template variables
	$main_body->assign_vars(array(
		'EXAMPLE' => $my_text,
	));	 
			 
	//Echo everything
	include('includes/header.php');
	$main_body->pparse('password_recover_body');
	include('includes/footer.php');
?>