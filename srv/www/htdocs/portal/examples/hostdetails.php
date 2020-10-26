<?php

/*
 * script/adajax.php v0.1
 * AJAX script, user/group/host administration functions
 * (C) 2009 Daniel T. Bender, invis-server.org
 * (C) 2009, 2010, 2011, 2012, 2014 Stefan Schaefer, invis-server.org
 * (C) 2013 Ingo Göppert, invis-server.org
 * License GPLv3
 * Questions: stefan@invis-server.org
 */

// Alte LDAP Klasse von Daniel einbinden, wird fuer die DHCP Geschichten benötigt
require_once('../ldap.php');
// Hinzugefügt nach Erweiterung der config.php (SMB_HOSTNAME) 21.07.2009 -- Stefan
require_once('../default/default-config.php');
require_once('../config.php');
// adLDAP Klasse einbinden und Objekt erzeugen
require_once('../inc/adLDAP.php');
require_once('../inc/functions.inc.php');

// Array mit Globalvariablen bilden
$options = array(
        'domain_controllers' => array("$FQDN"),
        'account_suffix' => "@$DOMAIN",
        'base_dn' => "$LDAP_SUFFIX",
        'admin_username' => "$LDAP_ADMIN",
        'admin_password' => "$LDAP_BIND_PW");

//adLDAP Klassenobjekt initialisieren
try {
	$adldap = new adLDAP($options);
	}
	catch (adLDAPException $e) {
	    echo $e;
	    exit();   
}

//--------------------
// AJAX HELPERS
//--------------------

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
	if ( $CVE20207070 == true ) {
	    $cookie_auth = json_decode(urldecode($_COOKIE['invis']), true);
	} else {
	    $cookie_auth = json_decode($_COOKIE['invis'], true);
	}

if (isset($_COOKIE['invis-request']))
	if ( $CVE20207070 == true ) {
	    $cookie_data = json_decode(urldecode($_COOKIE['invis-request']), true);
	} else {
	    $cookie_data = json_decode($_COOKIE['invis-request'], true);
	}

// unset request cookie
setcookie('invis-request', '', time() - 3600, '/');

// host details
function hostDetail($conn, $cn) {
	global $BASE_DN_DHCP;
	$result = search($conn, $BASE_DN_DHCP, "cn=$cn");
	if ($result) {
		$entry = cleanup($result[0]);
		unset($entry['dn']);
		unset($entry['objectguid']);
		return $entry;
	}
}

//--------------------
// main functionality
//--------------------

$conn = connect();
$bind = bind($conn);

$ergebnis = hostDetail($conn, 'laserdings');
var_dump($ergebnis);
echo "<br>Hallo<br>";
// Die Funktion json_encode stolpert ueber zurueck gelieferte
// Binaerwerte, daher muss das Attribut 'objectguid' aus dem
// Ergebnis-Array geloescht werden.
echo json_encode($ergebnis);

unbind($conn);
?>
