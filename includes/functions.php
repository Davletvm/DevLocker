<?php
if (!defined('CS546')) {
	die("Nice try, no hacking allowed.");
}

//Protect from SQL injection
function encode_string($str)
{
    addslashes(trim($str));
    return $str;
}
//Return the original string
function decode_string($str)
{
    stripslashes($str);
    return $str;
}
//Create values string (MySQL helper)
//This takes an array of strings, and returns it as a single string with each value surrounded by '' and separated by commas
function values_list($list)
{
	$output = '';
	foreach($list as $key => $value)
	{
		$list[$key] = "'" . $list[$key] . "'";
	}
	return implode(",", $list);
}
function getUID()
{
	return substr(str_replace("-", "", uniqid()), 0, 16);
}
function sendMail($to, $subject, $message)
{
	global $smtp_auth, $smtp_secure, $smtp_host, $smtp_port, $smtp_username, $smtp_username, $smtp_password, $smtp_from, $smtp_from_name;

	include_once("class.phpmailer.php"); // path to the PHPMailer class
 
	$mail = new PHPMailer();  
	 
	$mail = new PHPMailer();  // create a new object
	$mail->IsSMTP(); // enable SMTP
	$mail->SMTPDebug = 0;  // debugging: 1 = errors and messages, 2 = messages only
	$mail->SMTPAuth = $smtp_auth;  // authentication enabled
	$mail->SMTPSecure = $smtp_secure; // secure transfer enabled REQUIRED for GMail
	$mail->Host = $smtp_host;
	$mail->Port = $smtp_port; 
	$mail->Username = $smtp_username;  
	$mail->Password = $smtp_password;           
	$mail->SetFrom($smtp_from, $smtp_from_name);
	$mail->Subject = $subject;
	$mail->Body = $message;
	$mail->AddAddress($to);
	 
	
	if(!$mail->Send())
	{
		echo 'Mailer error: ' . $mail->ErrorInfo;
	} else
	{
		//echo 'Message has been sent.';
	}
}

function connect()
{
    global $dbhost, $dbusername, $dbpass, $dbname;
    $dbh = mysql_connect($dbhost, $dbusername, $dbpass) or die ("Could not connect");
    if (!mysql_select_db($dbname, $dbh)) {
        die ("Could not select database.");
    }
}
function xconnect()
{
    mysql_close();
}
function resetSessionData()
{
	global $SETTINGS;
	session_regenerate_id();
	$_SESSION['user_id'] = -1;
	$_SESSION['is_logged_in'] = 0;
	$_SESSION['timezone'] = -5;
	$_SESSION['session_time'] = time();
	$_SESSION['username'] = '';
}
function rrmdir($dir)
{
    foreach(glob($dir . '/*') as $file) {
        if(is_dir($file))
            rrmdir($file);
        else
            unlink($file);
    }
    rmdir($dir);
}
function fetchSessionData($sid)
{
	global $_SESSION;
	global $SETTINGS;
	$sid_query = mysql_query('SELECT * FROM ' . $SETTINGS["TABLE_PREFIX"] . 'sessions WHERE session_id = "' . $sid . '"');
	if(mysql_num_rows($sid_query) > 0)
	{
		$usr_query = mysql_query("SELECT * FROM " . $SETTINGS["TABLE_PREFIX"] . "users WHERE user_id = " . $_SESSION['user_id']);
		if(mysql_num_rows($usr_query) > 0)
		{
			$userData = mysql_fetch_assoc($usr_query);
			$sessData = mysql_fetch_assoc($sid_query);
			$_SESSION['user_id'] = $sessData['session_user_id'];
			$_SESSION['is_logged_in'] =  $sessData['session_logged_in'];
			$_SESSION['session_time'] =  time();
			$_SESSION['username'] =  $userData['username'];
			session_id($sessData['session_id']);
		} else
		{
			resetSessionData();
		}
	} else
	{
		resetSessionData();
	}
}
function redirect($r) {
	echo "<script type='text/javascript'>refresh('$r', 0)</script>";
}
function reload($r, $s) {
	echo "<script type='text/javascript'>refresh('$r', $s)</script>";
}
//Get latest auto_increment value
function mysql_next_id($table)
{
    $tablename 		= $table;
	$next_increment 	= 0;
	$qShowStatus 		= "SHOW TABLE STATUS LIKE '$tablename'";
	$qShowStatusResult 	= mysql_query($qShowStatus) or die ( "Query failed: " . mysql_error() . "<br/>" . $qShowStatus );

	$row = mysql_fetch_assoc($qShowStatusResult);
	$next_increment = $row['Auto_increment'];

	return $next_increment;
}

//IP encoding methods extracted from phpBB forums. Special thanks to them!
function encode_ip($dotquad_ip)
{
	$ip_sep = explode('.', $dotquad_ip);
	return sprintf('%02x%02x%02x%02x', $ip_sep[0], $ip_sep[1], $ip_sep[2], $ip_sep[3]);
}
function decode_ip($int_ip)
{
	$hexipbang = explode('.', chunk_split($int_ip, 2, '.'));
	return hexdec($hexipbang[0]). '.' . hexdec($hexipbang[1]) . '.' . hexdec($hexipbang[2]) . '.' . hexdec($hexipbang[3]);
}
?>