<?php
/* 
 * script/scriptexecuter.php v1.0
 * AJAX script, Execute Shell-commands and respond
 * (C) 2019 Stefan Schaefer, invis-server.org
 * License GPLv3
 * Questions: stefan@invis-server.org
 */
if (!isset($_COOKIE['invis'])) die();

// returncode file
$rtcfile = '../tmp/iportal.tmp';

// siwtch GET/POST
// !!REMOVE!!
if (isset($_GET['c'])) {
	$CMD = $_GET['c'];
	if (isset($_GET['u']))
		$USR = $_GET['u'];
	else
		$USR = null;
} else {
	$CMD = $_POST['c'];
	if (isset($_POST['u']))
		$USR = $_POST['u'];
	else
		$USR = null;
}

//--------------------
// COOKIE STUFF
//--------------------

if (isset($_COOKIE['invis']))
	$cookie_auth = json_decode($_COOKIE['invis'], true);

if (isset($_COOKIE['invis-request']))
	$cookie_data = json_decode($_COOKIE['invis-request'], true);

	error_log("Cookie-Data: $cookie_data");
	
// unset request cookie
setcookie('invis-request', '', time() - 3600, '/');

// execute commands
if ( $CMD == "membermod" )
    $val = shell_exec("sudo /usr/bin/mmall");

if ( $CMD == "fixgsacls" )
    $val = shell_exec("sudo /usr/bin/fixgsacls");

if ( $CMD == "check-istate" )
    $val = shell_exec("sudo /usr/bin/check-istate");

if ( $CMD == "inhume" )
    $val = shell_exec("sudo /usr/bin/inhume $cookie_data 1");

// read return code of the executed command
$returncode = file_get_contents("$rtcfile");
// and delete tmp-file
unlink("$rtcfile");

//error_log("Shell-Rueckgabe: $CMD - $returncode");

echo json_encode($returncode);
?>
