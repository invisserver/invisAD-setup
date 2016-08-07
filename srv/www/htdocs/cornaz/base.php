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
	// Benutzer-DNs und Knotennamen erzeugen
	// CorNAz-Knoten = lokale Email-Adresse
	$luser = "$corusername@$DOMAIN";

	// AD-Benutzer-DN
	$filter = "(samAccountName=$corusername)";
	$justthese = array("cn", "mail");
	$entries = search($ditcon,$BASE_DN_USER,$filter,$justthese);
	$adusercn = $entries[0]['cn'][0];
	$aduserdn = "CN=$adusercn,$BASE_DN_USER";

	// CorNAz Benutzerdn
	$coruserdn = "cn=$corusername,$LDAP_SUFFIX_AUI";

	// Status ermitteln
	$status = getstate($corusername);
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