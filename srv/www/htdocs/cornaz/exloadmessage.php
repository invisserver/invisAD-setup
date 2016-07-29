<?php
# Oeffnet eine vorhandene Nachrichtendatei und uebergibt diese in Formular zur Nachrichtenbearbeitung.

//Konfiguration einbinden
include ("./inc/config.inc.php");

// Dateinamen einlesen
$myfile = $_FILES['myfile']['tmp_name'];

session_start();
session_name("cornaz");

$filehandle = fopen($myfile, "r");
$mailsubject = fgets($filehandle,10000);
$mailsubject = str_replace("Subject: ","",$mailsubject);
$mailbody = fread($filehandle, 4096);
fclose($filehandle);

$_SESSION['mailsubject'] = $mailsubject;
$_SESSION['mailbody'] = $mailbody;

header ("Location: $COR_WEBSERVER" . "cornaz/base.php?file=invacationmsg.php");
?>