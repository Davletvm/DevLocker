<?php
if (!defined('CS546')) {
	die("Nice try, no hacking allowed.");
}

//DATABASE INFORMATION
//Change this to toggle between local and remote DB
$remote_db = true;

if($remote_db)
{
	//Remote 
	//PhpMyAdmin: Use the below dbusername and dbpass to log in to the remote server when it prompts you
	$dbname = "cs546final";
	$dbhost = "50.7.16.10";
	$dbusername = "test_user";
	$dbpass = "mawea387";
} else
{
	//Local (You may want to dump the remote database and work locally when you can for faster response times)
	$dbname = "cs546final";
	$dbhost = "localhost";
	$dbusername = "testuser";
	$dbpass = "password";
}

//SMTP INFORMATION For the mailer
$smtp_auth = true;  // authentication enabled
$smtp_secure = 'ssl'; // secure transfer enabled REQUIRED for GMail, make sure php_openssl is enabled
$smtp_host = 'smtp.gmail.com';
$smtp_port = 465; 
$smtp_username = 'devlocker.testing@gmail.com';  
$smtp_password = 'modiz117';           
$smtp_from ='devlocker.testing@gmail.com';
$smtp_from_name = 'Dev Locker';

?>