<?php
if (!defined('CS546'))
{
	die("Nice try, no hacking allowed.");
} else if(!defined('INSTALL') && file_exists('install.php') && false)
{
	die('After installation via install.php, please remove or rename install.php before using the site.');
}

//Header File. Creates the top part of the site up to <body>

//Init the template
$header = new Template($SETTINGS["TEMPLATE_DIR"]);
$header->set_filenames(array(
         'header' => 'header.htm'
));


$u_name = '';	
	
//Only show the side bar if they are logged in
if(isset($_SESSION) && (bool) $_SESSION['is_logged_in'])
{
	$header->assign_block_vars('logout_btn', Array());
	$header->assign_block_vars('user_controls', Array());
	$u_name = $_SESSION['username'];
} else
{
	$header->assign_block_vars('account_nav', Array());
}

//Assign template variables
$header->assign_vars(array(
	'TITLE' => $SETTINGS["PAGE_TITLE"],
	'USERNAME' => $u_name,
	'TEMPLATE_DIR' => $SETTINGS["TEMPLATE_DIR"]
));
	 
//Parse, and display.
$header->pparse('header');
?>