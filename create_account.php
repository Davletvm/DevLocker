<?php
	define('CS546', true);
	//This is the order all files must be included in each .PHP file
	include('includes/config.php');
	include('includes/functions.php');
	include('includes/settings.php');
	include('includes/sessions.php');
	include('includes/template.php');
	
	$SETTINGS["PAGE_TITLE"] = "Create Account";
	
	//Init the templates
	$main_body = new Template($SETTINGS["TEMPLATE_DIR"]);
	$verify_body = new Template($SETTINGS["TEMPLATE_DIR"]);
	$account_success_body = new Template($SETTINGS["TEMPLATE_DIR"]);
	$main_body->set_filenames(array(
	 'create_account_body' => 'create_account_body.htm',
	 'simple_body' => 'simple_body.htm'
	));
	
	//Start the database connection
	connect();
	
	$errors = '';
	//Prep vars to store everything in
	$form_user = '';
	$form_email = '';
	$form_email_conf = '';
	$form_pass = '';
	$form_pass_conf = '';
	$form_company = '';
	$form_fname = '';
	$form_lname = '';
	
	//Check if the form was submitted, and build up the errors list if there is a problem
	//Basically if the errors string is not null when $_POST['formsubmit'] is set, then we'll display the same form with errors
	if(isset($_GET['key']))
	{
		$verify_key = addslashes($_GET['key']);
		$row = mysql_fetch_assoc(mysql_query("SELECT * FROM " . $SETTINGS["TABLE_PREFIX"] . "users WHERE verified_key = '" . $verify_key  . "'"));
		$message = '';
		if(!$row)
		{
			$message = "Error, invalid key.";
		} else
		{
			if($row['account_verified'] == 0)
			{
				$message = "Account for user " . $row['username'] . " has been activated. You may now <a href=\"index.php\">log in</a>.";
				mysql_query("UPDATE " . $SETTINGS["TABLE_PREFIX"] . "users SET account_verified = '1' WHERE user_id = " . $row['user_id']);
			} else
			{
				$message = "Your account has already been activated.  You may now <a href=\"index.php\">log in</a>.";
			}
		}
		$main_body->assign_vars(array(
			'MESSAGE' => $message
		));
		//Echo everything
		include('includes/header.php');
		$main_body->pparse('simple_body');
		include('includes/footer.php');
		
		xconnect();
		return;
	} else if(isset($_POST['formsubmit']) && isset($_POST['user']) && isset($_POST['email']) && isset($_POST['email_conf']) && isset($_POST['pass']) && isset($_POST['pass_conf']))
	{
		$form_user = trim($_POST['user']);
		if(strlen($form_user) < 6 || !preg_match('/^[A-Za-z0-9_]+$/', $form_user))
		{
			$errors .= 'Error, invalid username. Must contain >= 6 alpha-numeric characters and no spaces.<br />';
		} else if(mysql_num_rows(mysql_query("SELECT * FROM " . $SETTINGS["TABLE_PREFIX"] . "users WHERE username = '" . $form_user . "'")) > 0)
		{
			$errors .= 'Error, a user by that name already exists.<br />';
		}
		

		$form_email = trim($_POST['email']);
		if(!preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/', $form_email))
		{
			$errors .= 'Error, invalid email address.<br />';
		} else if(mysql_num_rows(mysql_query("SELECT * FROM " . $SETTINGS["TABLE_PREFIX"] . "users WHERE email = '" . $form_email . "'")) > 0)
		{
			$errors .= 'Error, a user with that email address already exists.<br />';
		}
		
		

		$form_email_conf = trim($_POST['email_conf']);
		if($form_email_conf != $form_email)
		{
			$form_email_conf = '';
			$errors .= 'Error, the supplied email addresses must match for verification.<br />';
		}
		
		

		$form_pass = trim($_POST['pass']);
		if(strlen($form_pass) < 6 /* || some other condition*/)
		{
			$form_pass = '';
			$errors .= 'Error, invalid password. Must contain >= 6 characters.<br />';
		}
		
		$form_pass_conf = trim($_POST['pass_conf']);
		if($form_pass_conf != $form_pass)
		{
			$form_pass = '';
			$errors .= 'Error, passwords do not match.<br />';
		}
		
		if(isset($_POST['fname']))
		{
			$form_fname = encode_string($_POST['fname']);
			if(strlen($form_fname) > 60)
			{
				$form_fname = '';
				$errors .= 'Error, first name must not exceed 60 characters.<br />';
			}
		}
		if(isset($_POST['lname']))
		{
			$form_lname =  encode_string($_POST['lname']);
			if(strlen($form_lname) > 60)
			{
				$form_lname = '';
				$errors .= 'Error, first name must not exceed 60 characters.<br />';
			}
		}
	}
	
	if(isset($_POST['formsubmit'])  && isset($_POST['user']) && isset($_POST['email']) && isset($_POST['email_conf']) && isset($_POST['pass']) && isset($_POST['pass_conf']) && $errors == '')
	{
		//Get a unique ID for the verification email
		$uid = $form_user . getUID();
		
		//No errors, success!!!
		$main_body->assign_vars(array(
			'MESSAGE' => 'Thanks for signing up, ' . $form_user . '! Please check your email for instructions on activating your account.'
		));
		
		mysql_query("INSERT INTO ". $SETTINGS["TABLE_PREFIX"] . "users (email, username, password, first_name, last_name, signup_date, verified_key) VALUES (" . values_list(array($form_email, $form_user, md5($form_pass), addslashes($form_fname), addslashes($form_lname), time(), $uid)) . ")");
		//Echo everything
		include('includes/header.php');
		$main_body->pparse('simple_body');
		include('includes/footer.php');
		
		//Send mail
		sendMail($form_email, 'Dev Locker Account Activation', 'Click here to activate your account: http://' . $SETTINGS["DOMAIN"] . '/create_account.php?key='. $uid);
	} else
	{
		//Assign template variables
		$main_body->assign_vars(array(
			'ERRORS' => $errors,
			'FORM_USER' => $form_user,
			'FORM_EMAIL' => $form_email,
			'FORM_EMAIL_CONF' => $form_email_conf,
			'FORM_PASS' => $form_pass,
			'FORM_PASS_CONF' => $form_pass_conf,
			'FORM_COMPANY' => $form_company,
			'FORM_FNAME' => $form_fname,
			'FORM_LNAME' => $form_lname
		));
		//Echo everything
		include('includes/header.php');
		$main_body->pparse('create_account_body');
		include('includes/footer.php');
	}
	
	//And now we can close the database connection
	xconnect();
			 
?>