<?php
	define('CS546', true);
	//This is the order all files must be included in each .PHP file
	include('includes/config.php');
	include('includes/functions.php');
	include('includes/settings.php');
	include('includes/sessions.php');
	include('includes/template.php');
	
	$SETTINGS["PAGE_TITLE"] = "Control Panel";
	
	//Init the template (You can echo as many templates as listed in the filenames)
	$main_body = new Template($SETTINGS["TEMPLATE_DIR"]);
	$main_body->set_filenames(array(
	 'control_panel_body' => 'control_panel_body.htm',
	 'simple_body' => 'simple_body.htm'
	));
	
	$my_text = '';
	$errors = '';
	
	connect();
	//First make sure logged in
	if(!$_SESSION['is_logged_in'])
	{
		$message = 'You must login to enter the control panel.<br /><br /><a href="index.php">Return</a>';
		$main_body->assign_vars(array(
			'MESSAGE' => $message,
		));
		//Echo everything
		include('includes/header.php');
		$main_body->pparse('simple_body');
		include('includes/footer.php');
	} else
	{
		if(isset($_POST['update']))
		{
			if(isset($_POST['newemail1']))
			{
				$form_email = trim($_POST['newemail1']);
				if($form_email != '')
				{
					if(!preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/', $form_email))
					{
						$errors .= 'Error, invalid email address.<br />';
					} else if(mysql_num_rows(mysql_query("SELECT * FROM " . $SETTINGS["TABLE_PREFIX"] . "users WHERE email = '" . $form_email . "' AND user_id != " . $_SESSION['user_id'])) > 0)
					{
						$errors .= 'Error, another user with that email address already exists.<br />';
					}
					if(isset($_POST['newemail2']))
					{
						$form_email_conf = trim($_POST['newemail2']);
						if($form_email_conf != '' && $form_email_conf != $form_email)
						{
							$form_email_conf = '';
							$errors .= 'Error, the supplied email addresses must match for verification.<br />';
						}
					} else
					{
						$errors .= 'Error, the supplied email addresses must match for verification.<br />';
					}
				}
			}
			if(isset($_POST['newpass1']))
			{
				$form_pass = trim($_POST['newpass1']);
				if($form_pass != '')
				{
					if(strlen($form_pass) < 6 /* || some other condition*/)
					{
						$form_pass = '';
						$errors .= 'Error, invalid password. Must contain >= 6 characters.<br />';
					} else
					{
						$form_pass_conf = trim($_POST['newpass2']);
						if($form_pass_conf != $form_pass)
						{
							$form_pass = '';
							$errors .= 'Error, passwords do not match.<br />';
						}
					}
				}
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
					$errors .= 'Error, last name must not exceed 60 characters.<br />';
				}
			}
			if(isset($_POST['pass']))
			{
				$form_pass = addslashes($_POST['pass']);
			} else
			{
				$errors .= 'No password was entered.<br />';
			}
		}
		if(isset($_POST['update']) && $errors == '')
		{			
			//No errors, success!!!
			  $row = mysql_fetch_assoc(mysql_query("SELECT * FROM " . $SETTINGS["TABLE_PREFIX"] . "users WHERE password = '" . md5($form_pass) . "' AND user_id = " . $_SESSION['user_id']));
			  if ($row)
			  {
				if ($form_pass != '' && isset($_POST['newpass1']) && isset($_POST['newpass2']))
				{
				  if ($_POST['newpass1'] == $_POST['newpass2'])
				  {
					$row = mysql_query("UPDATE ".$SETTINGS["TABLE_PREFIX"]."users SET password = '" . md5($form_pass) . "' WHERE user_id = " . $_SESSION['user_id']);
				  } else
				  {
					$errors .= 'Error, new passwords do not match.<br />';
				  }
				}
				
				if ($form_email != '' && $form_email_conf != '' &&  $errors == '' && isset($_POST['newemail1']) && isset($_POST['newemail2']))
				{
				  if ($form_email == $form_email_conf)
				  {
					$new_email = $_POST['newemail1'];
					$row = mysql_query("UPDATE ".$SETTINGS["TABLE_PREFIX"]."users SET email = '" . $new_email . "' WHERE user_id = " . $_SESSION['user_id']);
				  } else
				  {
					$errors .= 'Error, new emails do not match.<br />';
				  }
				}
				if($errors == '' && isset($_POST['fname']))
				{
					mysql_query("UPDATE ".$SETTINGS["TABLE_PREFIX"]."users SET first_name = '" . $form_fname . "' WHERE user_id = " . $_SESSION['user_id']);
				}
				if($errors == '' && isset($_POST['lname']))
				{
					mysql_query("UPDATE ".$SETTINGS["TABLE_PREFIX"]."users SET last_name = '" . $form_lname . "' WHERE user_id = " . $_SESSION['user_id']);
				}
				$my_text = 'Account updated.';
			  } else
			  {
				$errors .= 'Error, current password must be entered correctly.<br />';
			  }
		}
		
		$row = mysql_fetch_assoc(mysql_query("SELECT * FROM " . $SETTINGS["TABLE_PREFIX"] . "users WHERE user_id = " . $_SESSION['user_id']));
		
		//Assign template variables
		$main_body->assign_vars(array(
		'MESSAGE' => $my_text,
		'ERRORS' => $errors,
		'FORM_FNAME' => htmlspecialchars(decode_string($row['first_name'])),
		'FORM_LNAME' => htmlspecialchars(decode_string($row['last_name'])),
		'FORM_EMAIL' =>$row['email']
		));

		//Echo everything
		include('includes/header.php');
		$main_body->pparse('control_panel_body');
		include('includes/footer.php');
	}
    xconnect();
?>