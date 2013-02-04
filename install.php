<?php
	define('CS546', true);
	define('INSTALL', true);
	//This is the order all files must be included in each .PHP file
	include('includes/config.php');
	include('includes/functions.php');
	include('includes/settings.php');
	include('includes/template.php');
	
	$SETTINGS["PAGE_TITLE"] = "Installer Page";
	
	//Please use $SETTINGS["TABLE_PREFIX"] for your table references as used below
	
	include('includes/header.php');
	
	echo 'Connecting to database...<br/>';
	connect();

	echo 'Creating users table...<br/>';
	mysql_query(
	"CREATE TABLE IF NOT EXISTS " . $SETTINGS["TABLE_PREFIX"] . "users (
	  user_id int(11) NOT NULL auto_increment,
	  email varchar(60) NOT NULL,
	  username varchar(60) NOT NULL,
	  password varchar(60) NOT NULL,
	  avatar varchar(60) NOT NULL,
	  first_name varchar(60) NOT NULL,
	  last_name varchar(60) NOT NULL,
	  signup_date int(11) NOT NULL,
	  account_verified tinyint(1) NOT NULL default '0',
	  verified_key varchar(60) default NULL,
	  PRIMARY KEY  (user_id))"
	);
	
	echo 'Creating projects table...<br/>';
	mysql_query(
	"CREATE TABLE IF NOT EXISTS " . $SETTINGS["TABLE_PREFIX"] . "projects (
	  project_id int(11) NOT NULL auto_increment,
	  project_name varchar(45) NOT NULL,
	  project_description TEXT NOT NULL,
	  creation_date int(11) NOT NULL,
	  creator_user_id int(11) NOT NULL,
	  PRIMARY KEY  (project_id),
	  FOREIGN KEY (creator_user_id) REFERENCES " . $SETTINGS["TABLE_PREFIX"] . "users(user_id))"
	);
	
	echo 'Creating project comments table...<br/>';
	mysql_query(
	"CREATE TABLE IF NOT EXISTS " . $SETTINGS["TABLE_PREFIX"] . "project_comments (
	  comment_id int(11) NOT NULL auto_increment,
	  project_id int(11) NOT NULL,
	  user_id int(11) NOT NULL,
	  comment_text TEXT NOT NULL,
	  comment_date int(11) NOT NULL,
	  PRIMARY KEY  (comment_id),
	  FOREIGN KEY (user_id) REFERENCES " . $SETTINGS["TABLE_PREFIX"] . "users(user_id) ON DELETE CASCADE )"
	);
	
	echo 'Creating relational tables...<br/>';
	mysql_query(
	"CREATE TABLE IF NOT EXISTS " . $SETTINGS["TABLE_PREFIX"] . "belongs_to_project (
	  user_id int(11) NOT NULL,
	  project_id int(11) NOT NULL,
	  user_status int(11) NOT NULL,
	  PRIMARY KEY  (user_id, project_id),
	  FOREIGN KEY (project_id) REFERENCES " . $SETTINGS["TABLE_PREFIX"] . "projects(project_id) ON DELETE CASCADE,
	  FOREIGN KEY (user_id) REFERENCES " . $SETTINGS["TABLE_PREFIX"] . "users(user_id) ON DELETE CASCADE )"
	);
	mysql_query(
	"CREATE TABLE IF NOT EXISTS " . $SETTINGS["TABLE_PREFIX"] . "invited_to_project (
	  project_id int(11) NOT NULL,
	  user_id int(11) NOT NULL,
	  FOREIGN KEY (user_id) REFERENCES " . $SETTINGS["TABLE_PREFIX"] . "users(user_id) ON DELETE CASCADE,
	  FOREIGN KEY (project_id) REFERENCES " . $SETTINGS["TABLE_PREFIX"] . "projects(project_id) ON DELETE CASCADE )"
	);
	mysql_query(
	"CREATE TABLE IF NOT EXISTS " . $SETTINGS["TABLE_PREFIX"] . "file_checked (
	  user_id int(11) NOT NULL,
	  file_id int(11) NOT NULL,
	  FOREIGN KEY (user_id) REFERENCES " . $SETTINGS["TABLE_PREFIX"] . "users(user_id),
	  FOREIGN KEY (file_id) REFERENCES " . $SETTINGS["TABLE_PREFIX"] . "file_reference(file_id))"
	);
	
	echo 'Creating project entities table...<br/>';
	mysql_query(
	"CREATE TABLE IF NOT EXISTS " . $SETTINGS["TABLE_PREFIX"] . "project_entities (
	  entity_id int(11) NOT NULL auto_increment,
	  entity_name varchar(45) NOT NULL,
	  entity_description TEXT NOT NULL,
	  project_id int(11) NOT NULL,
	  locked_by_id int(11) default NULL,
	  PRIMARY KEY (entity_id),
	  FOREIGN KEY (locked_by_id) REFERENCES " . $SETTINGS["TABLE_PREFIX"] . "users(user_id),
	  FOREIGN KEY (project_id) REFERENCES " . $SETTINGS["TABLE_PREFIX"] . "projects(project_id) ON DELETE CASCADE)"
	);
	
	echo 'Creating project entity comments table...<br/>';
	mysql_query(
	"CREATE TABLE IF NOT EXISTS " . $SETTINGS["TABLE_PREFIX"] . "project_entity_comments (
	  comment_id int(11) NOT NULL auto_increment,
	  entity_id int(11) NOT NULL,
	  user_id int(11) NOT NULL,
	  comment_text TEXT NOT NULL,
	  comment_date int(11) NOT NULL,
	  PRIMARY KEY  (comment_id),
	  FOREIGN KEY (user_id) REFERENCES " . $SETTINGS["TABLE_PREFIX"] . "users(user_id) ON DELETE CASCADE,
	  FOREIGN KEY (entity_id) REFERENCES " . $SETTINGS["TABLE_PREFIX"] . "project_entities(entity_id) ON DELETE CASCADE)"
	);
	
	echo 'Creating file reference table...<br/>';
	mysql_query(
	"CREATE TABLE IF NOT EXISTS " . $SETTINGS["TABLE_PREFIX"] . "file_reference (
	  file_id int(11) NOT NULL auto_increment,
	  origin_id int(11) NULL,
	  file_name varchar(255) NOT NULL,
	  file_size int(11) NOT NULL,
	  creation_date int(11) NOT NULL,
	  uploader_id int(11) NOT NULL,
	  entity_id int(11) NOT NULL,
	  overwritten tinyint(1) NOT NULL default '0',
	  PRIMARY KEY  (file_id),
	  FOREIGN KEY (uploader_id) REFERENCES " . $SETTINGS["TABLE_PREFIX"] . "users(user_id),
	  FOREIGN KEY (entity_id) REFERENCES " . $SETTINGS["TABLE_PREFIX"] . "project_entities(entity_id) ON DELETE CASCADE)"
	);
	
	echo 'Creating sessions table...<br/>';
	mysql_query(
		"CREATE TABLE IF NOT EXISTS " . $SETTINGS["TABLE_PREFIX"] . "sessions (
		session_id varchar(255) NOT NULL,
		session_user_id int(11) NOT NULL default '0',
		session_start int(11) NOT NULL default '0',
		session_time int(11) NOT NULL default '0',
		session_ip tinytext NOT NULL,
		session_logged_in tinyint(4) NOT NULL default '0',
		UNIQUE (session_id))
	");
	
	echo 'Creating files directory...<br/>';
	if(!file_exists($SETTINGS["UPLOAD_DIR"]))
	{
		mkdir($SETTINGS["UPLOAD_DIR"]);
	}
	if(!file_exists($SETTINGS["UPLOAD_DIR"] . '/icons'))
	{
		mkdir($SETTINGS["UPLOAD_DIR"] . '/icons');
	}
	echo '<b>Done!</b><br/>';
	
	xconnect();
	
	include('includes/footer.php');
?>