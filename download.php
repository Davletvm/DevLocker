<?php
	define('CS546', true);
	//This is the order all files must be included in each .PHP file
	include('includes/config.php');
	include('includes/functions.php');
	include('includes/settings.php');
	include('includes/sessions.php');
	include('includes/template.php');
	
	$SETTINGS["PAGE_TITLE"] = "Download File";
	
	if(!$_SESSION['is_logged_in'])
		die("You must sign in.");
	
	if(!is_numeric($_GET['fid']))
		die("File not found.");
	
	connect();
	$file_id = $_GET['fid'];
	$file_row = mysql_fetch_assoc(mysql_query("SELECT * FROM " . $SETTINGS["TABLE_PREFIX"] . "file_reference F WHERE F.file_id = " . $file_id));
	
	if(!$file_row)
		die("File not found.");
	
	$module_id = $file_row['entity_id'];
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

	$browserFilename = $file_row['file_name'];
	$path = $SETTINGS["UPLOAD_DIR"] . "/" . $file_row['file_id'];
	
	if (!file_exists($path) || !is_readable($path))
		die("Error downloading file");
	
	header("Content-Type: application/octet-stream");
	header("Content-Disposition: attachment; filename=\"". $browserFilename . "\"");
	header('Expires: ' . gmdate('D, d M Y H:i:s', gmmktime() - 3600) . ' GMT');
	header("Content-Length: " . filesize($path));
	// If you wish you can add some code here to track or log the download

	// Special headers for IE 6
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	$fp = fopen($path, "r");
	fpassthru($fp);
	
	xconnect();
?>