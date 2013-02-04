<?php
	define('CS546', true);
	//This is the order all files must be included in each .PHP file
	include('includes/config.php');
	include('includes/functions.php');
	include('includes/settings.php');
	include('includes/sessions.php');
	include('includes/template.php');
	
	$SETTINGS["PAGE_TITLE"] = "View User Profile";
	
	//Init the template (You can echo as many templates as listed in the filenames)
	$main_body = new Template($SETTINGS["TEMPLATE_DIR"]);
	$main_body->set_filenames(array(
	 'view_profile_body' => 'view_profile_body.htm',
	 'simple_body' => 'simple_body.htm'
	));
	
	connect();
	$my_text = "You are viewing the index page.<br />Your session ID is: " . session_id();
	xconnect();
	
	if (isset($_GET['uid'])){
    $uid = $_GET['uid'];
	}else{
    $uid = $_SESSION['user_id'];
	}
	
	connect();
	$result = mysql_query("SELECT * FROM " . $SETTINGS["TABLE_PREFIX"] . "belongs_to_project A, " . $SETTINGS["TABLE_PREFIX"] . "belongs_to_project B WHERE A.project_id = B.project_id AND A.user_id = " . $uid . " AND B.user_id = " . $_SESSION['user_id']);
	if ($row = mysql_fetch_assoc($result)){
    $result = mysql_query("SELECT * FROM " . $SETTINGS["TABLE_PREFIX"] . "users WHERE user_id = " . $uid);
    $row = mysql_fetch_assoc($result);
    $main_body->assign_vars(array(
        'USERNAME' => $row['username'],
        'FNAME' => htmlspecialchars(decode_string($row['first_name'])),
        'LNAME' => htmlspecialchars($row['last_name']),
        'EMAIL' => $row['email'],
        'SIGNUPDATE' => date("m/d/Y", $row['signup_date'])
    ));
    xconnect();
			 
    //Echo everything
    include('includes/header.php');
    $main_body->pparse('view_profile_body');
    include('includes/footer.php');
  }else{
    xconnect();
    $my_text = "You do not have permission to view this user's profile.";
    $main_body->assign_vars(array(
      'MESSAGE' => $my_text
    ));
    
    //Echo everything
    include('includes/header.php');
    $main_body->pparse('simple_body');
    include('includes/footer.php');
  }
?>