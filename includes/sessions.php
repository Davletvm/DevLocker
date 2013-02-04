<?php
if (!defined('CS546')) {
	die("Nice try, no hacking allowed.");
}
//Get IP and check session data and decide what to do
$ip = encode_ip($_SERVER['REMOTE_ADDR']);
session_name();
session_start();
//Prune for old sessions
connect();
mysql_query("DELETE FROM ". $SETTINGS["TABLE_PREFIX"] . "sessions WHERE session_user_id <= 0 AND " . time() . " - session_time > " . $SETTINGS["SESSION_LENGTH_OUT"]);
mysql_query("DELETE FROM ". $SETTINGS["TABLE_PREFIX"] . "sessions WHERE session_user_id > 0 AND " . time() . " - session_time > " . $SETTINGS["SESSION_LENGTH_IN"]);

//Get session data
fetchSessionData(session_id());

//Attempt to get session data
$sql = mysql_query("SELECT * FROM ". $SETTINGS["TABLE_PREFIX"] . "sessions WHERE session_id = '" . session_id() . "'");

//Makes a new session if session does not exist, the id is null, or the session time length exceeds $SETTINGS["SESSION_LENGTH"] vars
if(mysql_num_rows($sql) == 0 || session_id() == '' || (time() - $_SESSION['session_time'] > $SETTINGS["SESSION_LENGTH_IN"] && $_SESSION['is_logged_in']) || ((time() - $_SESSION['session_time'] > $SETTINGS["SESSION_LENGTH_OUT"] && !$_SESSION['is_logged_in'])))
{
	//Create session data
	resetSessionData();
	//Add session data to the database
	mysql_query("INSERT INTO ". $SETTINGS["TABLE_PREFIX"] . "sessions (session_id, session_user_id, session_start, session_time, session_ip,  session_logged_in) VALUES ('" . session_id() . "', " . $_SESSION['user_id'] . ", " . time() . ", " . time() . ", '" . $ip . "', " . $_SESSION['is_logged_in'] . ")");
} else
{
	//Session is ok, just update the time
	$_SESSION['session_time'] = time();
}
if ((bool) $_SESSION['is_logged_in'])
{
	$sessData = mysql_fetch_assoc(mysql_query("SELECT * FROM ". $SETTINGS["TABLE_PREFIX"] . "sessions WHERE session_id = '" . session_id() . "'"));
	$userData = mysql_fetch_assoc(mysql_query("SELECT * FROM ". $SETTINGS["TABLE_PREFIX"] . "users WHERE user_id = " . $_SESSION['user_id']));
	//Make sure they didn't hack the session data
	if($_SESSION['user_id'] != $sessData["session_user_id"] || !$sessData["session_logged_in"] || $userData["account_verified"] == 0)
	{
		mysql_query("DELETE FROM ". $SETTINGS["TABLE_PREFIX"] . "sessions WHERE session_id = " . session_id());
		mysql_query("DELETE FROM ". $SETTINGS["TABLE_PREFIX"] . "sessions WHERE session_user_id = " . $userData["user_id"]);
		resetSessionData();
		//Re-create the session
		mysql_query("INSERT INTO ". $SETTINGS["TABLE_PREFIX"] . "sessions (session_id, session_user_id, session_start, session_time, session_ip,  session_logged_in) VALUES ('" . session_id() . "', " . $_SESSION['user_id'] . ", " . time() . ", " . time() . ", '" . $ip . "', " . $_SESSION['is_logged_in'] . ")");
	} else
	{
		//Update their last active time
		//mysql_query("UPDATE ". $SETTINGS["TABLE_PREFIX"] . "users SET user_session_time = " . time() . " WHERE user_id = " . $_SESSION['user_id']);
	}
}
xconnect();
?>