<?php
	define('CS546', true);
	//This is the order all files must be included in each .PHP file
	include('includes/config.php');
	include('includes/functions.php');
	include('includes/settings.php');
	include('includes/sessions.php');
	include('includes/template.php');
	
	$SETTINGS["PAGE_TITLE"] = "Contact Us";
	
	//Init the templates
	$main_body = new Template($SETTINGS["TEMPLATE_DIR"]);
	$verify_body = new Template($SETTINGS["TEMPLATE_DIR"]);
	$account_success_body = new Template($SETTINGS["TEMPLATE_DIR"]);
	$main_body->set_filenames(array(
	 'contact_us_body' => 'contact_us_body.htm',
	 'simple_body' => 'simple_body.htm'
	));
	
	
	$errors = '';
	//Prep vars to store everything in
	$form_user = '';
	$form_email = '';
	$form_help = '';
	$form_comment = '';

	//Check if the form was submitted, and build up the errors list if there is a problem
	//Basically if the errors string is not null when $_POST['formsubmit'] is set, then we'll display the same form with errors
	if(isset($_POST['formsubmit']) && isset($_POST['user']) && isset($_POST['email']) && isset($_POST['help']) && isset($_POST['comment']))
	{
		if(isset($_POST['user']))
		{
			$form_user = trim($_POST['user']);
		}

		$form_email = trim($_POST['email']);
		if(!preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/', $form_email))
		{
			$errors .= 'Error, invalid email address.<br />';
		}
		
		if(isset($_POST['help']))
		{
			$form_chelp = trim($_POST['help']);
		}
		
		if(isset($_POST['comment']))
		{
			$form_comment = trim($_POST['comment']);
		}
	}
	
	if(isset($_POST['formsubmit'])  && isset($_POST['user']) && isset($_POST['email']) && isset($_POST['help']) && isset($_POST['comment']) && $errors == '')
	{
		
		//No errors, success!!!
		$main_body->assign_vars(array('MESSAGE' => 'Thanks for your input, ' . $form_user . '! We\'ll be responding to your request soon.'
		));
		
	    //Echo everything
		include('includes/header.php');
		$main_body->pparse('simple_body');
		include('includes/footer.php');
		
		//Send mail
		sendMail($form_email, 'DEVLocked Contact Confirmation', 'Thank you for contacting us at http://' . $SETTINGS["DOMAIN"] . 'We\'ll respond to your needs soon.');
	} else
	{
		//Assign template variables
		$main_body->assign_vars(array(
			'ERRORS' => $errors,
			'FORM_USER' => $form_user,
			'FORM_EMAIL' => $form_email,
			'FORM_HELP' => $form_help,
			'FORM_COMMENT' => $form_comment,
		));
		//Echo everything
		include('includes/header.php');
		$main_body->pparse('contact_us_body');
		include('includes/footer.php');
	}
	
?>