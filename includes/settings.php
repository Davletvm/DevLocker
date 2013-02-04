<?php
if (!defined('CS546')) {
	die("Nice try, no hacking allowed.");
}
//Make the config info
$SETTINGS = array();
$SETTINGS["TIMEZONE"] = -4;
$SETTINGS["TEMPLATE_DIR"] = "templates/default/";
$SETTINGS["PAGE_TITLE"] = ""; //Set this variable in each PHP file
$SETTINGS["SESSION_NAME"] = "CS546";
$SETTINGS["SESSION_LENGTH_IN"] = 60*60*24; //Session timeout for logged in users
$SETTINGS["SESSION_LENGTH_OUT"] = 300; //Session timeout for logged out users
$SETTINGS["TABLE_PREFIX"] = "cs546_";
$SETTINGS["UPLOAD_DIR"] = "devlocker_files";
$SETTINGS["UPLOAD_LIMIT"] = "2"; //Measured in MB
$SETTINGS["DOMAIN"] = "50.7.16.10/cs546";
?>