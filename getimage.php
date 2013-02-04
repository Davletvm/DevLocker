<?php
	define('CS546', true);
	//This is the order all files must be included in each .PHP file
	include('includes/config.php');
	include('includes/functions.php');
	include('includes/settings.php');
	include('includes/sessions.php');
	include('includes/template.php');
	
	$SETTINGS["PAGE_TITLE"] = "Get Image";
	
	if(!$_SESSION['is_logged_in'])
		die("You must sign in.");
	
	if(!is_numeric($_GET['mid']))
		die("File not found.");
	
	connect();
	
	$module_id = $_GET['mid'];
	$project_id = -1;
	$project_name = '';
	$creator_name = '';
	$creator_id = '';
	
	//The information for the project entity
	$project_row = mysql_fetch_assoc(mysql_query("SELECT * FROM " . $SETTINGS["TABLE_PREFIX"] . "project_entities E WHERE E.entity_id = " . $module_id));
	
	if(!$project_row)
		die("File not found. [Err 2]");
		
	//At this point we have the project module info, and just need to see if they have permission to view it
	$project_id = $project_row["project_id"];
	
	//If they created the project or belong to it
	$permissions_row = mysql_fetch_assoc(mysql_query("SELECT * FROM " . $SETTINGS["TABLE_PREFIX"] . "projects P, " . $SETTINGS["TABLE_PREFIX"] . "users U, " . $SETTINGS["TABLE_PREFIX"] . "belongs_to_project B WHERE P.project_id = " . $project_id . " AND B.project_id = P.project_id AND B.user_id = " . $_SESSION['user_id']));
	
	if(!$permissions_row)
		die("Permission Denied");

	$path = $SETTINGS["UPLOAD_DIR"] . "/" . $project_id . "/" . $module_id . "/" . $module_id;
	
	if (!file_exists($path) || !is_readable($path))
	{
		//Return default image
		$path = 'images/module.png';
	}
	$size = getimagesize($path);
	$fp = fopen($path, "rb");
	
	header("Content-Type: " . $size['mime']);
	fpassthru($fp);
	
	xconnect();
	exit;
?>