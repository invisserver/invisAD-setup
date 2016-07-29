<?php
# CorNAz
# Script zur Manipulation der Fetchmail Steuerdatei .fetchmailrc
# Author Stefan Schaefer email: stefan@invis-Server.org
# (c) 2008,2014,2016 Stefan Schaefer - invis-server.org
# (c) 2012 Ingo Goeppert - invis-server.org
# License: GPLv3

//Includes einbinden
require ("./inc/html.inc.php");
require ("/etc/invis/portal/config.php");
require ("./inc/functions.inc.php");
require ("./inc/classes.inc.php");

//Session
session_start();
session_name("cornaz");

// Session und Umgebungsvariablen übernehmen
$corprogram = $_SESSION["corprogram"];

// Formularvariablen übernehmen
$corusername = $_SESSION["corusername"];
$corpassword = $_SESSION["corpassword"];

// Mit LDAP-Server verbinden
$ditcon = connect();
if ($ditcon) {
    $bind = bind($ditcon);
}

//Inhaltsdatei ermitteln oder festlegen
if (!isset($_REQUEST['file'])) {
	$inhalt = "inhalt.php";
} else {
	$inhalt = $_REQUEST['file'];
}

if(isset($corpassword)) {
	// Aktuellen Status ermitteln
	$un = strlen($corusername);
	$unx = 0;
	// echo "$un<br>";
	// Einlesen der Datei .fetchmailrc in ein Array
	$fetchmailrc_b = file ("$COR_FETCHMAILRC_BUILD");
	$stat = 0;
	// Statusüberprüfung
	foreach ($fetchmailrc_b as $zeile) {
		$unx = strlen(strstr($zeile, "$corusername"))-1;
		$n = strlen(chop($zeile)) - $unx;
		if (substr(chop($zeile), $n, $un) == $corusername) {
		$stat = $stat + 1;
		}
	}
	if ($stat >= 1) {
		$status="Anwesend";
	} else {
		$status="Abwesend";
	}
	
	// Anwesend aber trotzdem im Urlaub
	if ($status == "Anwesend") {
		if (file_exists ("$COR_PATH/vacation/$corusername.binweg")) {
			$status="Urlaub";
		}}

	// Oeffnen der neuen Seite
	$sitename = "eMail Accounts verwalten";

	site_head($corprogram, $sitename, $COR_BG_COLOR);

	// Inhalt einfügen
	include ("./$inhalt");
	
	// Seite schliessen
	$cormainpage = "<a href=\"$COR_WEBSERVER" . "cornaz/base.php\">Hauptmenü</a>";

	site_end($cormainpage, $PORTAL_FOOTER, "&nbsp;" );
} else {
	header("Location: $COR_WEBSERVER" . "cornaz/");
}

// Verbindung zum LDAP-Server trennen
ldap_unbind($ditcon);
?>