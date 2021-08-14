<?php
/* 
 * inc/mail.section.inc.php v1.0
 * portal drop-in, manage mailaccounts
 * (C) 2009 Daniel T. Bender, invis-server.org
 * (C) 2018 Stefan Schaefer, invis-server.org
 * (C) 2021 W.-Marcel Richter, invis-server.org
 * License GPLv3
 * Questions: daniel@invis-server.org
 */

if (!isset($CONF)) die;

// message to be sent if authorization fails
function unauthorized() {
	header("HTTP/1.0 401 Unauthorized");
}

// 0:guest, 1:user, 2:admin
$usertype = 0;
if (isset($USER_DATA)){ $usertype = 1; }
else { $USER_DATA=''; }

if(! isset($USER_IS_ADMIN))
    $USER_IS_ADMIN=false;
if ($USER_IS_ADMIN) $usertype = 2;

$conn = connect();
$bind = bind($conn);

$server = isset($_SERVER['HTTPS']) ? 'https://'.$_SERVER['HTTP_HOST'] : 'http://'.$_SERVER['HTTP_HOST'];

$result = search($conn, "$LDAP_SUFFIX_PORTAL", 'iportentryposition=mail');
if ($result) {

    // no cookie, no competition
    if (!isset($_COOKIE['invis'])) {
	unauthorized();
	error_log("Unauthorized access: User \"" . $data['uid'] . "\" has no cookie set (1, ../login.php).");
    } else {
		// pull JSON object from cookie
		if(! isset($CVE20207070)
		    $CVE20207070 = false;
		if ( $CVE20207070 == true ) {
			$data = json_decode(urldecode($_COOKIE['invis']), true);
		} else {
			$data = json_decode($_COOKIE['invis'], true);
		}

		// get user information
		$corusername = $data['uid'];
    }

    // Mit LDAP-Server verbinden
    $ditcon = connect();
    if ($ditcon) {
	$bind = bind($ditcon);
    }

    //Inhaltsdatei ermitteln oder festlegen
    if (!isset($_REQUEST['file'])) {
	$inhalt = "inc/cornaz/inhalt.php";
    } else {
	$inhalt = "inc/cornaz/".$_REQUEST['file'];
    }

    if(isset($corusername)) {
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

	// Inhalt einfügen
	include ("./$inhalt");
    }

    // Verbindung zum LDAP-Server trennen
    ldap_unbind($ditcon);
    } else {
	echo ldap_error($conn);
    }
unbind($conn);

