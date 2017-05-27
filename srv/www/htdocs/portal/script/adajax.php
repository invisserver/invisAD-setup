<?php

/*
 * script/adajax.php v0.6
 * AJAX script, user/group/host administration functions
 * (C) 2009 Daniel T. Bender, invis-server.org
 * (C) 2009, 2010, 2011, 2012, 2014, 2015 Stefan Schaefer, invis-server.org
 * (C) 2013 Ingo Göppert, invis-server.org
 * License GPLv3
 * Questions: stefan@invis-server.org
 */

// Alte LDAP Klasse von Daniel einbinden, wird fuer die DHCP Geschichten benötigt
require_once('../inc/ldap.inc.php');
// Hinzugefügt nach Erweiterung der config.php (SMB_HOSTNAME) 21.07.2009 -- Stefan
require_once('../config.php');
// adLDAP Klasse einbinden und Objekt erzeugen
require_once('../inc/adLDAP.php');
require_once('../inc/functions.inc.php');
require_once('../inc/services.inc.php');

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
	$cookie_auth = json_decode($_COOKIE['invis'], true);

if (isset($_COOKIE['invis-request']))
	$cookie_data = json_decode($_COOKIE['invis-request'], true);

// unset request cookie
setcookie('invis-request', '', time() - 3600, '/');

//--------------------
// USER STUFF
//--------------------

// Namen für die einzelnen Eigenschaften
define ( "GAST_FLAG",    0b0000001);
define ( "MAIL_FLAG",    0b0000010);
define ( "WINDOWS_FLAG", 0b0000100);
define ( "UNIX_FLAG",    0b0001000);
define ( "GW_FLAG",      0b0010000);
define ( "ADMIN_FLAG",   0b0100000);
define ( "MASTER_FLAG",  0b1000000);

// Namen für die verschiedenen Typen
define ( "GAST_TYP",              0);
define ( "MAIL_TYP",              1);
define ( "WIN_TYP",               2);
define ( "WIN_UNIX_TYP",          3);
define ( "WIN_UNIX_GW_TYP",       4);
define ( "WIN_ADMIN_TYP",         5);
define ( "WIN_ADMIN_UNIX_TYP",    6);
define ( "WIN_ADMIN_UNIX_GW_TYP", 7);
define ( "MASTER_ADMIN_TYP",      8);

function userList() {
	global $cookie_data, $adldap;

	// Raw data array returned
	$result = $adldap->user()->all();
	$json = array();
	foreach ($result as $i => $value) {
	    $collection = $adldap->user()->infoCollection("$result[$i]", array("*") );
	    $rid = ridfromsid(bin_to_str_sid($collection->objectsid));
	    $pgid = $collection->primarygroupid;
	    $gidnr = $collection->gidnumber;
	    $shell = $collection->loginshell;
	    $gwaccount = $collection->zarafaaccount;
	    $admin = $adldap->user()->inGroup("$result[$i]","Domain Admins");
	    $maildummy = $adldap->user()->inGroup("$result[$i]","maildummies");
	    $enterpriseadmin = $adldap->user()->inGroup("$result[$i]","Enterprise Admins");

	    // Benutzertyp ermitteln
	    $typevalue = 0;

	    if ( $pgid == "514" ) {
		$typevalue |= GAST_FLAG; 
	    }
	    if ( $pgid == "513" ) {
		$typevalue |= WINDOWS_FLAG; 
	    }
	    if (( $pgid == "512" ) || ( $admin == true )) {
		$typevalue |= ADMIN_FLAG; 
	    }
	    if (( $gidnr == "9500" ) || ( $gidnr == "600" ) || ( $maildummy == "1"))  {
		$typevalue |= MAIL_FLAG; 
	    }
	    if ( $shell == "/bin/bash" ) {
		$typevalue |= UNIX_FLAG; 
	    }
	    if ( $gwaccount == true ) {
		$typevalue |= GW_FLAG; 
	    }
	    if ( $enterpriseadmin == "1" ) {
		$typevalue |= MASTER_FLAG;
	    }

	    switch ($typevalue) {
		case GAST_FLAG:
		    $type = GAST_TYP;
		    break;
		case MAIL_FLAG:
		case (MAIL_FLAG | GW_FLAG):
		case (WINDOWS_FLAG | GW_FLAG):
		    $type = MAIL_TYP;
		    break;
		case WINDOWS_FLAG:
		    $type = WIN_TYP;
		    break;
		case (WINDOWS_FLAG | UNIX_FLAG):
		    $type = WIN_UNIX_TYP;
		    break;
		case (WINDOWS_FLAG | UNIX_FLAG | GW_FLAG):
		    $type = WIN_UNIX_GW_TYP;
		    break;
		case (WINDOWS_FLAG | ADMIN_FLAG):
		    $type = WIN_ADMIN_TYP;
		    break;
		case (ADMIN_FLAG | UNIX_FLAG):
		case (WINDOWS_FLAG | ADMIN_FLAG | UNIX_FLAG):
		    $type = WIN_ADMIN_UNIX_TYP;
		    break;
		case (ADMIN_FLAG | UNIX_FLAG | GW_FLAG):
		case (WINDOWS_FLAG | ADMIN_FLAG | UNIX_FLAG | GW_FLAG):
		    $type = WIN_ADMIN_UNIX_GW_TYP;
		    break;
		case MASTER_FLAG:
		case (MASTER_FLAG | GW_FLAG):
		case (MASTER_FLAG | ADMIN_FLAG): 
		case (MASTER_FLAG | ADMIN_FLAG | GW_FLAG): 
		case (MASTER_FLAG | ADMIN_FLAG | WINDOWS_FLAG): 
		case (MASTER_FLAG | ADMIN_FLAG | WINDOWS_FLAG | GW_FLAG): 
		    $type = MASTER_ADMIN_TYP;
		    break;
		default: // Neuer, noch unbekannte Kombination
		    $type = GAST_TYP;
	    }

	    // Debug Ausgabe ins Apache Error Log
	    //error_log("Name: ".$collection->samaccountname.", Value: ".decbin($typevalue).", Type: ".$type.", Shell: ".$shell);

	    $entry = array("uidnumber" => "$rid","uid" => "$result[$i]", "TYPE" => "$type" );
	    // create JSON response
	    array_push($json, $entry);
	}
	return $json;
}

function userListShort() {
	global $cookie_data, $adldap;
	// Raw data array returned
	$result = $adldap->user()->all();
	//var_dump($result);
	$json = array();
	foreach ($result as $i => $value) {
	    // create JSON response
	    array_push($json, $result[$i]);
	}
	return $json;

}

function userDetail($uid) {
	// adldap-Objekt muss in Funktionen als global-Variable genannt werden
	global $adldap;
	// Benutzerinformationen abfragen
	$result = $adldap->user()->infoCollection("$uid", array("*"));
	$userdetails = array(
	    'firstname' => $result->givenname,
	    'surname' => $result->sn,
	    'display_name' => $result->displayname,
	    'uid' => $result->samaccountname,
	    'description' => $result->description,
	    'department' => $result->department,
	    'office' => $result->physicaldeliveryofficename,
	    'email' => $result->mail,
	    'telephone' => $result->telephonenumber,
	// adtstamp2date($result->accountExpires)."<br>";
	    'rid' => ridfromsid(bin_to_str_sid($result->objectsid)));
	
	return $userdetails;
}

function userCreate($uid) {
	global $cookie_data, $adldap, $DOMAIN, $NISDOMAIN, $COMPANY, $mdrid, $SMB_HOSTNAME, $SMB_FILESERVER, $SFU_GUID_BASE, $GROUPWARE;
	// read user data from cookie
	//$attributes = $cookie_data;
	$ok = false;

	// account type, fetch from POST var
	// (default 0) 0: user, 1: admin, 2: guest, 3: mail, 4: zarafa-user
	if (isset($_POST['t'])) {
		//echo $_POST['t'];
		$accounttype = intval($_POST['t']); 
	}

	// GIDNumber der Gruppe Domain Users ermitteln
	try {
	    $collection = $adldap->group()->infoCollection("Domain Users",array('*'));
	} catch (adLDAPException $e) {
	    if (! empty($e)) {
		return $e;
		exit();
	    }
	}
	$dugidnumber = ($collection->gidnumber);

	// GIDNumber der Gruppe maildummies ermitteln
	$mdgroup = "maildummies";
	
	try {
	    $collection = $adldap->group()->infoCollection($mdgroup,array('*'));
	} catch (adLDAPException $e) {
	    if (! empty($e)) {
		return $e;
		exit();
	    }
	}
	$mdgidnumber = ($collection->gidnumber);
	
        // Profil- und Home-Pfad
        // Ist in der Konfiguration ein externer Fileserver genannt
        // wird das Home-Verzeichnisse dort angelegt.
        $profilepath = "\\\\$SMB_HOSTNAME\\profiles\\$uid";
        
        if ($SMB_FILESERVER == "NULL") {
            $smbhomepath = "\\\\$SMB_HOSTNAME\\$uid";
        } else {
            $smbhomepath = "\\\\$SMB_FILESERVER\\$uid";
        }

	// hier mit case weiter, Atribute werden je nach Accounttype gesetzt
	//$password = $cookie_data['userpassword'];
	$password = $cookie_data['adpassword'];
	//error_log ( $password,  0);
	
	// Wenn Groupware = roundcube, dann gibt es keine expliziten Groupware Konten
	// Attributtyp wir jeweils um 1 reduziert.
	
	if ($GROUPWARE == 'roundcube') {
	
	    if ($accounttype == '4') {
		$accounttype = '3';
	    } elseif ($accounttype == '7') {
		$accounttype = '6';
	    }
	    
	}
	
        // Email-Attribut erzeugen
        // Wenn beim Anlegen eines Benutzers eine Adresse angegeben wird, wir diese
        // dem Attribut "mail" zugeordnet und die automatisch generierte interne Adresse
        // dem Attribut "otherMailbox".
        // Wird keine Adresse angegeben wird das Attribut "mail" wie bisher mit der internen
        // Adresse gefuellt.
        if (empty($cookie_data['email']) || $cookie_data['email'] == "-") {
            $emailaddress = $cookie_data['uid']."@".$DOMAIN;
            $othermailbox = "";
        } else {
            $emailaddress = $cookie_data['email'];
            $othermailbox = $cookie_data['uid']."@".$DOMAIN;
        }

	// Anzeigename generieren, wenn nicht angegeben
	if (empty($cookie_data['display_name'])) {
	    $displayname = $cookie_data['firstname'] . " " . $cookie_data['surname'];
	} else {
	    $displayname = $cookie_data['display_name'];
	}

	// Standard-Attribute - unabhaengig vom Kontentyp
	$attributes=array(
	    "username"=>$uid,
	    "logon_name"=>$cookie_data['uid']."@".$DOMAIN,
	    "display_name"=>"$displayname",
	    "firstname"=>$cookie_data['firstname'],
	    "surname"=>$cookie_data['surname'],
	    "description"=>$cookie_data['description'],
	    "company"=>"$COMPANY",
	    "department"=>$cookie_data['department'],
	    "office"=>$cookie_data['office'],
	    "telephone"=>$cookie_data['telephone'],
	    "email"=>$emailaddress,
	    "othermailbox"=>$othermailbox,
	    "container"=>array("Users"),
	    "enabled"=>1,
	    "password"=>$password
	    );

	// Benutzer anlegen
	switch ($accounttype) {
	case GAST_TYP:
	    // Gastbenutzer
	    // Standard-Attribute - unabhaengig vom Kontentyp
	    // Benutzer anlegen
	    try {
		$result = $adldap->user()->create($attributes);
	    } catch (adLDAPException $e) {
		if (! empty($e)) {
		    return $e;
		    exit();
		}
	    }
	    if ($result !=  true) {
		return "Fehlermeldung: ".$adldap->getLastError();
	    }

	    // Benutzer der Gruppe "Domain Guests" hinzufuegen
	    try {
		$result = $adldap->group()->addUser("Domain Guests", "$uid");
	    } catch (adLDAPException $e) {
		if (! empty($e)) {
		    return $e;
		    exit();
		}
	    }
	    if ($result !=  true) {
		return "Fehlermeldung: ".$adldap->getLastError();
	    }

	    // Primaergruppe auf "Domain Guests" setzen
	    $attrmod=array("primarygroupid"=>"514");
	    try {
		$result = $adldap->user()->modify("$uid",$attrmod);
	    } catch (adLDAPException $e) {
		if (! empty($e)) {
    		    return $e;
    		    exit();
    		}
	    }
	    if ($result !=  true) {
		return "Fehlermeldung: ".$adldap->getLastError();
	    }

	    // Benutzer aus Gruppe "Domain Users" entfernen
	    try {
		$result = $adldap->group()->removeUser("Domain Users", "$uid");
	    } catch (adLDAPException $e) {
		if (! empty($e)) {
		    return $e;
		    exit();
		}
	    }
	    if ($result !=  true) {
		return "Fehlermeldung: ".$adldap->getLastError();
	    }
	    break;

	case MAIL_TYP:
	    // Maildummy Konto
	    // Benutzer anlegen
	    try {
		$ok = $adldap->user()->create($attributes);
	    } catch (adLDAPException $e) {
		if (! empty($e)) {
		    return $e;
		    exit();
		}
	    }
	    if ($ok !=  true) {
		return "Fehlermeldung: ".$adldap->getLastError();
	    }

	    // Benutzer der Gruppe "maildummies" hinzufuegen
	    try {
		$result = $adldap->group()->addUser("$mdgroup", "$uid");
	    } catch (adLDAPException $e) {
		if (! empty($e)) {
		    return $e;
		    exit();
		}
	    }
	    if ($result !=  true) {
		return "Fehlermeldung: ".$adldap->getLastError();
	    }

	    // RID ermitteln, wird zur Festlegung der uidNumber benoetigt.
	    try {
		$collection = $adldap->user()->infoCollection("$uid",array('*'));
	    } catch (adLDAPException $e) {
		if (! empty($e)) {
		    return $e;
		    exit();
		}
	    }
	    $userrid = ridfromsid(bin_to_str_sid($collection->objectsid));

	    // Attribute anpassen
	    $attrmod=array(
	        'mssfu30nisdomain' => "$NISDOMAIN",
		'mssfu30name' => $cookie_data['uid'],
		'primarygroupid' => $mdrid,
		'gidnumber' => $mdgidnumber,
		'loginshell' => '/bin/false',
		'unixhomedirectory' => "/home/".$cookie_data['uid'],
		'uidnumber' => ( $userrid + $SFU_GUID_BASE ),
		"zarafaaccount" => true,
		"zarafasharedstoreonly" => true
	    );
	    try {
		$result = $adldap->user()->modify("$uid",$attrmod);
	    } catch (adLDAPException $e) {
		if (! empty($e)) {
		    return $e;
		    exit();
		}
	    }
	    if ($result !=  true) {
		return "Fehlermeldung: ".$adldap->getLastError();
	    }

	    // Benutzer aus Gruppe "Domain Users" entfernen
	    try {
		$result = $adldap->group()->removeUser("Domain Users", "$uid");
	    } catch (adLDAPException $e) {
		if (! empty($e)) {
		    return $e;
		    exit();
		}
	    }
	    if ($result !=  true) {
		return "Fehlermeldung: ".$adldap->getLastError();
	    }
	    break;

	case WIN_TYP:
	    // reiner Windows User
	    return "Reiner Windows-User wird nicht l&auml;nger unterst&uuml;zt!";
	    break;

	case WIN_UNIX_TYP:
	    // Windows und UNIX Benutzer
	    // Attribute anpassen
	    // Standard-Attribute - erweitern
	    $attributes += ["home_drive"=>'u:'];
	    $attributes += ["home_directory"=>"$smbhomepath"];
	    $attributes += ["profile_path"=>"$profilepath"];
	    $attributes += ["script_path"=>"user.cmd"];
	    // Benutzer anlegen
	    try {
		$ok = $adldap->user()->create($attributes);
	    } catch (adLDAPException $e) {
		if (! empty($e)) {
		    return $e;
		    exit();
		}
	    }
	    if ($ok !=  true) {
		return "Fehlermeldung: ".$adldap->getLastError();
	    }

	    // RID ermitteln, wird zur Festlegung der uidNumber benoetigt.
	    try {
		$collection = $adldap->user()->infoCollection("$uid",array('*'));
	    } catch (adLDAPException $e) {
		if (! empty($e)) {
		    return $e;
		    exit();
		}
	    }
	    $userrid = ridfromsid(bin_to_str_sid($collection->objectsid));

	    // Attribute anpassen
	    $attrmod=array(
		"mssfu30nisdomain" => "$NISDOMAIN",
		"mssfu30name" => $cookie_data['uid'],
		"loginshell" => '/bin/bash',
		"unixhomedirectory" => "/home/".$cookie_data['uid'],
		'primarygroupid' => '513',
		'gidnumber' => $dugidnumber,
		'uidnumber' => ( $userrid + $SFU_GUID_BASE )
	    );
	    try {
		$result = $adldap->user()->modify("$uid",$attrmod);
	    } catch (adLDAPException $e) {
		if (! empty($e)) {
		    return $e;
		    exit();
		}
	    }
	    if ($result !=  true) {
		return "Fehlermeldung: ".$adldap->getLastError();
	    }
	    break;

	case WIN_UNIX_GW_TYP:
	    // Windows und UNIX Benutzer mit Groupware-Nutzung
	    // Attribute anpassen
	    // Standard-Attribute - erweitern
	    $attributes += ["home_drive"=>'u:'];
	    $attributes += ["home_directory"=>"$smbhomepath"];
	    $attributes += ["profile_path"=>"$profilepath"];
	    $attributes += ["script_path"=>"user.cmd"];
	    // Benutzer anlegen
	    try {
		$ok = $adldap->user()->create($attributes);
	    } catch (adLDAPException $e) {
		if (! empty($e)) {
		    return $e;
		    exit();
		}
	    }
	    if ($ok !=  true) {
		return "Fehlermeldung: ".$adldap->getLastError();
	    }

	    // RID ermitteln, wird zur Festlegung der uidNumber benoetigt.
	    try {
		$collection = $adldap->user()->infoCollection("$uid",array('*'));
	    } catch (adLDAPException $e) {
		if (! empty($e)) {
		    return $e;
		    exit();
		}
	    }
	    $userrid = ridfromsid(bin_to_str_sid($collection->objectsid));
	    // Attribute anpassen
	    // hier muss das nachfolgende Array in abhaengigkeit der Grouware
	    // definiert werden.
	    $attrmod=array(
		"mssfu30nisdomain" => "$NISDOMAIN",
		"mssfu30name" => $cookie_data['uid'],
		"loginshell" => '/bin/bash',
		"unixhomedirectory" => "/home/".$cookie_data['uid'],
		'primarygroupid' => '513',
		'gidnumber' => $dugidnumber,
		'uidnumber' => ( $userrid + $SFU_GUID_BASE ),
		"zarafaaccount" => true
	    );
	    try {
		$result = $adldap->user()->modify("$uid",$attrmod);
	    } catch (adLDAPException $e) {
		if (! empty($e)) {
		    return $e;
		    exit();
		}
	    }
	    if ($result !=  true) {
		return "Fehlermeldung: ".$adldap->getLastError();
	    }
	    break;
	    
	case WIN_ADMIN_TYP:
	    // Windows Admin ohne Zusatz-Attribute
	    return "Reiner Windows-Admin nicht l&auml;nger unterst&uuml;zt!";
	    break;

	case WIN_ADMIN_UNIX_TYP:
	    // Windows-Admin mit UNIX-Attributen
	    // keine UNIX Admin-Befugnisse
	    // Standard-Attribute - erweitern
	    $attributes += ["home_drive"=>'u:'];
	    $attributes += ["home_directory"=>"$smbhomepath"];
	    $attributes += ["profile_path"=>"$profilepath"];
	    $attributes += ["script_path"=>"user.cmd"];
	    // Benutzer anlegen
	    try {
		$ok = $adldap->user()->create($attributes);
	    } catch (adLDAPException $e) {
		if (! empty($e)) {
		    return $e;
		    exit();
		}
	    }
	    if ($ok !=  true) {
		return "Fehlermeldung: ".$adldap->getLastError();
	    }

	    // Benutzer der Gruppe "Domain Guests" hinzufuegen
	    try {
		$result = $adldap->group()->addUser("Domain Admins", "$uid");
	    } catch (adLDAPException $e) {
		if (! empty($e)) {
		    return $e;
		    exit();
		}
	    }
	    if ($result !=  true) {
		return "Fehlermeldung: ".$adldap->getLastError();
	    }

	    // RID ermitteln, wird zur Festlegung der uidNumber benoetigt.
	    try {
		$collection = $adldap->user()->infoCollection("$uid",array('*'));
	    } catch (adLDAPException $e) {
		if (! empty($e)) {
		    return $e;
		    exit();
		}
	    }
	    $userrid = ridfromsid(bin_to_str_sid($collection->objectsid));

	    // Primaergruppe auf "Domain Admins" setzen
	    // ist vermutlich nicht notwendig.
	    // und UNIX-Attribute hinzufuegen
	    $attrmod=array(
		"primarygroupid"=>"512",
		"mssfu30nisdomain" => "$NISDOMAIN",
		"mssfu30name" => $cookie_data['uid'],
		"loginshell" => '/bin/bash',
		"unixhomedirectory" => "/home/".$cookie_data['uid'],
		"gidnumber" => $dugidnumber,
		"uidnumber" => ( $userrid + $SFU_GUID_BASE )
		);
	    try {
		$result = $adldap->user()->modify("$uid",$attrmod);
	    } catch (adLDAPException $e) {
		if (! empty($e)) {
		    return $e;
		    exit();
		}
	    }
	    if ($result !=  true) {
		return "Fehlermeldung: ".$adldap->getLastError();
	    }
	    break;

	case WIN_ADMIN_UNIX_GW_TYP:
	    // Windows-Admin mit UNIX-Attributen und Groupware-Admin-Rechten
	    // Standard-Attribute - erweitern
	    $attributes += ["home_drive"=>'u:'];
	    $attributes += ["home_directory"=>"$smbhomepath"];
	    $attributes += ["profile_path"=>"$profilepath"];
	    $attributes += ["script_path"=>"user.cmd"];
	    $attributes += ["zarafaaccount" => true];
	    $attributes += ["zarafaadmin" => true];
	    // Benutzer anlegen
	    try {
		$ok = $adldap->user()->create($attributes);
	    } catch (adLDAPException $e) {
		if (! empty($e)) {
		    return $e;
		    exit();
		}
	    }
	    if ($ok !=  true) {
		return "Fehlermeldung: ".$adldap->getLastError();
	    }

	    // Benutzer der Gruppe "Domain Guests" hinzufuegen
	    try {
		$result = $adldap->group()->addUser("Domain Admins", "$uid");
	    } catch (adLDAPException $e) {
		if (! empty($e)) {
		    return $e;
		    exit();
		}
	    }
	    if ($result !=  true) {
		return "Fehlermeldung: ".$adldap->getLastError();
	    }

	    // RID ermitteln, wird zur Festlegung der uidNumber benoetigt.
	    try {
		$collection = $adldap->user()->infoCollection("$uid",array('*'));
	    } catch (adLDAPException $e) {
		if (! empty($e)) {
		    return $e;
		    exit();
		}
	    }
	    $userrid = ridfromsid(bin_to_str_sid($collection->objectsid));

	    // Primaergruppe auf "Domain Admins" setzen
	    // ist vermutlich nicht notwendig.
	    // und UNIX-Attribute hinzufuegen
	    $attrmod=array(
		"primarygroupid"=>"512",
		"mssfu30nisdomain" => "$NISDOMAIN",
		"mssfu30name" => $cookie_data['uid'],
		"loginshell" => '/bin/bash',
		"unixhomedirectory" => "/home/".$cookie_data['uid'],
		"gidnumber" => $dugidnumber,
		"uidnumber" => ( $userrid + $SFU_GUID_BASE ),
		"zarafaaccount" => true,
		"zarafaadmin" => true
		);
	    try {
		$result = $adldap->user()->modify("$uid",$attrmod);
	    } catch (adLDAPException $e) {
		if (! empty($e)) {
		    return $e;
		    exit();
		}
	    }
	    if ($result !=  true) {
		return "Fehlermeldung: ".$adldap->getLastError();
	    }
	    break;
	}

	if ($ok == true) {
	    $val = shell_exec("sudo /usr/bin/createhome $uid;");
	}

	if (empty($e)) {
	    return 0;
	}
	else {
	    return $e;
	}
}

function userModify($uid) {
	global $cookie_data, $adldap;
	// read user data from cookie
	$attributes = $cookie_data;
	
	// wenn das Passwort Attribut zurueck geliefert wird,
	// passwort aendern und attribut aus array löschen
	if ( ! empty($cookie_data['adpassword'])) {
	    $password = $cookie_data['adpassword'];
	    try {
		$result = $adldap->user()->password("$uid","$password");
		//var_dump($result);
	    }
	    catch (adLDAPException $e) {
		return "Fehlermeldung: ".$adldap->getLastError();
	    }
	unset($attributes->adpassword);
	unset($attributes->userpassword);
	unset($attributes->sambantpassword);
	}
	
	//echo $attributes['office'];
	$result = $adldap->user()->modify("$uid",$attributes);
	return $adldap->getLastError();
	//return $e;
}

function userDelete($uid) {
	global $adldap;
	
	$flag = intval($_POST['t']);
	//Benutzer loeschen 
	try {
	    $ok = $adldap->user()->delete($uid);
	} catch (adLDAPException $e) {
		echo $e;
	}
	if ($ok) {
		if ($flag == 1) shell_exec("sudo /usr/bin/deletehome $uid;");
	//	removeAdmin($uid);
	}
	if (empty($e)) {
		return 0;
	}

}

//--------------------
// GROUP STUFF
//--------------------
function groupList() {
	global $cookie_data, $adldap;
	// Raw data array returned
	$result = $adldap->group()->all();
	//var_dump($result);
	$json = array();
	foreach ($result as $i => $value) {
	    $collection = $adldap->group()->infoCollection("$result[$i]", array("*") );
	    //print_r($collection->member);
	    //print_r($collection->description);
	    $rid = ridfromsid(bin_to_str_sid($collection->objectsid));
	    //echo "$result[$i] - $rid <br>";
	    $gtype=dechex(trim(($collection->grouptype)));
	    $zaccount=($collection->zarafaaccount);
	    $gidnumber=($collection->gidnumber);
 
	    // Benutzerty ermitteln
	    $gtval = array();
	    $typevalue = 0;

	    if ( $gtype == "2" ) {
		$gtval[0] = 1;
	    }

	    if ( $zaccount == "1" ) {
		$gtval[1] = 2;
	    }

	    if ( isset($gidnumber) ) {
		$gtval[2] = 4;
	    }

	    foreach ($gtval as $val => $value) {
	    //echo "$value <br>";
		$typevalue = $typevalue + $value;
	    }

	    switch ($typevalue) {
		case 4:
		    $type = 0;
		    break;
		case 6:
		    $type = 1;
		    break;
		case 3:
		    $type = 2;
		    break;
		case 0:
		    $type = 3;
		}

	    $entry = array("rid" => "$rid","cn" => "$result[$i]","gidnumber" => $collection->gidNumber, "TYPE" => $type );
	    // create JSON response
	    array_push($json, $entry);
	}
	return $json;
}

function groupDetail($conn, $cn) {
	global $cookie_data, $adldap, $BASE_DN_USERS;
	$collection = $adldap->group()->infoCollection("$cn", array("*"));
	$groupdetails = array(
	    'cn' => $collection->cn,
	    'rid' => ridfromsid(bin_to_str_sid($collection->objectsid)),
	    'description' => $collection->description);
	
	//Gruppenmitglieder ermitteln
	$groupmember = $collection->member;
	//hier muss noch gearbeitet werden. $collection->member liefert 
	// vollstaendige DNs zurueck, fuer die Anzeige sollte es aber das
	// Attribut sAMAccountName sein. adLDAP verfuegt so wie es aus-
	// sieht bietet adLDAP dafuer keine Funktion, unser eigenes ldap.inc.php
	// aber vermutlich schon.
	$member = array();
	
	if (is_array($groupmember)) {
	    foreach($groupmember as $memberdn) {
		$result = search($conn, $memberdn, 'objectclass=*', array('samaccountname'));
		$entry = cleanup($result[0]);
		array_push($member, $entry['samaccountname']);
	    }
	} else {
		$result = search($conn, $groupmember, 'objectclass=*', array('samaccountname'));
		$entry = cleanup($result[0]);
		array_push($member, $entry['samaccountname']);
	}

	
	// build list of non-group users
	$tmp = userListShort($conn);
	$nonmember = array();
	foreach($tmp as $user) {
	    $ix = array_search($user, $member);
		if ($ix === false)
		    array_push($nonmember, $user);
	}
	// Drei arrays zurueck
	return array($groupdetails, $member, $nonmember);
}


function groupCreate() {
	global $cookie_data, $adldap, $NISDOMAIN, $SFU_GUID_BASE, $DOMAIN;
	$attributes=array(
		"group_name"=>$cookie_data['cn'],
		"description"=>$cookie_data['description'],
		"mssfu30nisdomain"=>$NISDOMAIN,
		"mssfu30name"=>$cookie_data['cn'],
		// Container Auswahl evtl spaeter.
		"container"=>array("Users")
	);

	// group type, fetch from POST var
	// (default 0)
	if (isset($_POST['t'])) {
		//echo $_POST['t'];
		$grouptype = intval($_POST['t']); 
	}

	$cn = $cookie_data['cn'];
	$ok = $adldap->group()->create($attributes);
	
	// Grundsaetzlich legt das Portal nur Gruppen an,
	// die auch fuer Unix-Clients zur Verfuegung stehen.
	// jetzt wirds lustig -> GID muss erzeugt werden.
	// rid ermitteln
	$result = $adldap->group()->infoCollection($cookie_data['cn'],array('*'));
	$rid = ridfromsid(bin_to_str_sid($result->objectsid));
	$gidnumber = $SFU_GUID_BASE + $rid;
	
	// Gruppe, je nach Gruppentyp modifizieren
	switch ($grouptype) {

	case 0:
		// Typ Windows+Unix
		$attributes = array(
		    "gidNumber"=>$gidnumber
		);
		break;
	case 1:
		// Typ Windows+Unix+Groupware
		$attributes = array(
		    "gidNumber"=>$gidnumber,
		    "zarafaAccount"=>1
		);
		break;
	case 2:
		// Typ Verteiler
		$email = $cn."@".$DOMAIN;
		$attributes = array(
		    "zarafaAccount"=>1,
		    "groupType"=>2,
		    "mail"=>$email
		);
		break;
	}
	
	$resultmod = $adldap->group()->modify($cn,$attributes);

	// Gruppenverzeichnis anlegen
	if ($ok) {
		shell_exec("sudo /usr/bin/creategroupshare $cn;");
	}
	
	$members = $cookie_data['memberuid'];
	//var_dump($members);
	// Mitglieder hinzufuegen
	foreach ($members as $i => $member) {
		//echo $member;
		$result = $adldap->group()->addUser($cn, "$member");
	}
	if ($ok) {
	    return 0;
	}
}

function groupModify($conn, $cn) {
	global $cookie_data, $adldap, $NISDOMAIN, $SFU_GUID_BASE;
	$attributes=array(
		"description"=>$cookie_data['description'],
	);
	// Description aendern
	$result = $adldap->group()->modify($cn,$attributes);

	if ( empty($result) || $result == 1 ) {
	    $ok = 1;
	}

	// erst mal alle Gruppenmitgliedeer ermitteln
	$collection = $adldap->group()->infoCollection("$cn", array("*"));
	$groupmember = $collection->member;

	// DNs in samaccountnames umwandeln
	$membersingroup = array();
	
	if (is_array($groupmember)) {
	    foreach($groupmember as $memberdn) {
		$result = search($conn, $memberdn, 'objectclass=*', array('samaccountname'));
		$entry = cleanup($result[0]);
		array_push($membersingroup, $entry['samaccountname']);
	    }
	} else {
		$result = search($conn, $groupmember, 'objectclass=*', array('samaccountname'));
		$entry = cleanup($result[0]);
		array_push($membersingroup, $entry['samaccountname']);
	}

	// neue Memberliste aus cookie extrahieren
	$members = $cookie_data['memberuid'];

	// Differenz-Array bilden.
	$memberstoremove = array_diff($membersingroup, $members);

	// Mitglieder hinzufuegen
	foreach ($members as $i => $member) {
		//echo $member;
	    if (! $adldap->user()->inGroup("$member","$cn")) {
		$result = $adldap->group()->addUser($cn, "$member");
		if ( ! $result == 1 ) {
		    unset($ok);
		}
	    }
	}
	// Mitglieder entfernen
	foreach ($memberstoremove as $i => $member) {
		//echo $member;
	    if ($adldap->user()->inGroup("$member","$cn")) {
		$result = $adldap->group()->removeUser($cn, "$member");
		if ( ! $result == 1 ) {
		    unset($ok);
		}
	    }
	}
	//echo "<br>$ok<br>";
	if ($ok == 1 ) {
	    return 0;
	}
}

function groupDelete($cn) {
	global $adldap;
	// Gruppe loeschen (und Gruppenverzeichnis archivieren?)
	// Es duerfen per Portal keine Gruppen geloescht werden
	// die zum AD-Standardumfang gehoeren.

	// RID ermitteln
	$collection = $adldap->group()->infoCollection("$cn", array("*"));
	$rid = ridfromsid(bin_to_str_sid($collection->objectsid));
	// RID 1105 basiert auf Beobachtung,, nicht auf Fakten....
	if ($rid >= 1105) {
	    try {
		$ok = $adldap->group()->delete($cn);

	    } catch (adLDAPException $e) {
		echo $e;
	    }
	
	    if ($ok) {
		shell_exec("sudo /usr/bin/deletehome $cn;");
	    }

	    if (empty($e)) {
		return 0;
	    }
	} else {
	    return 3;
	}
}

//--------------------
// HOST HELPERS
//--------------------

// Macht aus der IP "1.2" (z.B. bei einer /16 Netzmaske) die Zahl 258 (1 * 256) + 2
function hostIpCombine($ipstring) {
	if (strpos($ipstring,'.') == true) {
	    $ip_array = explode('.', $ipstring);
	    $ip = (intval($ip_array[0]) * 256) + intval($ip_array[1]);
	} else {
	    $ip = $ipstring;
	}
	return $ip;
}

// Die Daten aus der Config konvertieren
$DHCP_RANGE_SERVER[0] = hostIpCombine($DHCP_RANGE_SERVER[0]);
$DHCP_RANGE_SERVER[1] = hostIpCombine($DHCP_RANGE_SERVER[1]);
$DHCP_RANGE_IPDEV[0] = hostIpCombine($DHCP_RANGE_IPDEV[0]);
$DHCP_RANGE_IPDEV[1] = hostIpCombine($DHCP_RANGE_IPDEV[1]);
$DHCP_RANGE_PRINTER[0] = hostIpCombine($DHCP_RANGE_PRINTER[0]);
$DHCP_RANGE_PRINTER[1] = hostIpCombine($DHCP_RANGE_PRINTER[1]);
$DHCP_RANGE_CLIENT[0] = hostIpCombine($DHCP_RANGE_CLIENT[0]);
$DHCP_RANGE_CLIENT[1] = hostIpCombine($DHCP_RANGE_CLIENT[1]);

// Zum Debuggen, schreibt ins Apache Error Log:
//error_log("Server from: " . $DHCP_RANGE_SERVER[0] . " Server to: " . $DHCP_RANGE_SERVER[1]);
//error_log("IPDev from: " . $DHCP_RANGE_IPDEV[0] . " IPDev to: " . $DHCP_RANGE_IPDEV[1]);
//error_log("Printer from: " . $DHCP_RANGE_PRINTER[0] . " Printer to: " . $DHCP_RANGE_PRINTER[1]);
//error_log("Client from: " . $DHCP_RANGE_CLIENT[0] . " Client to: " . $DHCP_RANGE_CLIENT[1]);

// Konvertiert "fixed-address 10.255.255.39" in Zahl (255 * 256) + 39
function hostStatementToNumber($statement) {
	global $DHCP_IP_MASK;
	$statement_array = explode(' ', $statement);
	$ip_array = explode('.', $statement_array[1]);
	if ($DHCP_IP_MASK == '16') {
	    $ip = (intval($ip_array[2]) * 256) + intval($ip_array[3]);
	} else {
	    $ip = intval($ip_array[3]);
	}
	return $ip;
}

// Zum Debuggen, schreibt ins Apache Error Log:
//error_log("IP: " . hostStatementToNumber("fixed-address 10.255.255.39"));

// Konvertiert "fixed-address 10.255.255.39" in String "10.255.255.39"
function hostStatementToFullIp($statement) {
	$statement_array = explode(' ', $statement);
	return $statement_array[1];
}

// Zum Debuggen, schreibt ins Apache Error Log:
//error_log("IPString: " . hostStatementToFullIp("fixed-address 10.255.255.39"));

// Konvertiert Zahl (255 * 256) + 39 = 65319 in "255.39" 
function hostNumberToIPString($number) {
	global $DHCP_IP_MASK;
	if ($DHCP_IP_MASK == '16') {
	    $ip_1 = (int)($number / 256);
	    $ip_2 = $number - ($ip_1 * 256);
	    $ip_string = "" . $ip_1 . "." . $ip_2;
	} else {
	    $ip_string = $number;
	}
	return $ip_string;
}

// Zum Debuggen, schreibt ins Apache Error Log:
//error_log("IPString from number: " . hostNumberToIPString(65319));

//--------------------
// HOST STUFF
//--------------------

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

// host listing (long)
function hostList($conn) {
	global $BASE_DN_DHCP, $DHCP_RANGE_SERVER, $DHCP_RANGE_PRINTER, $DHCP_RANGE_CLIENT, $DHCP_RANGE_IPDEV, $DHCP_IP_BASE;
// dhcphost-Array um Attribut "dhcpcomments" erweitert.
	$result = search($conn, $BASE_DN_DHCP, "objectclass=iscdhcphost", array('cn', 'iscdhcphwaddress', 'iscdhcpstatements', 'iscdhcpcomments'));
	if ($result) {
		// create JSON response
		$json = array();
		for ($i=0; $i < $result['count']; $i++) {
			$entry = cleanup($result[$i]);
			unset($entry['dn']);
			$ip = hostStatementToNumber($entry['iscdhcpstatements'], '.');
			
			// 0: client, 1: printer, 2: server, 3: ip-device
			switch(true) {
				case ($ip >= $DHCP_RANGE_SERVER[0] && $ip <= $DHCP_RANGE_SERVER[1]): $type = 'Server'; break;
				case ($ip >= $DHCP_RANGE_IPDEV[0] && $ip <= $DHCP_RANGE_IPDEV[1]): $type = 'IP-Gerät'; break;
				case ($ip >= $DHCP_RANGE_PRINTER[0] && $ip <= $DHCP_RANGE_PRINTER[1]): $type = 'Drucker'; break;
				default:
					$type = 'Client';
			}
			$entry['TYPE'] = $type;
			
			array_push($json, $entry);
		}
		return $json;
	}
}

// host create
function hostCreate($conn, $cn) {
	global $DOMAIN, $DHCP_IP_BASE, $BASE_DN_DHCP, $cookie_data, $DHCP_RANGE_SERVER, $DHCP_RANGE_PRINTER, $DHCP_RANGE_CLIENT, $DHCP_RANGE_IPDEV;
	
	// 0: client, 1: printer, 2: server, 3: ipdevice
	if (isset($_POST['t']))
		$type = intval($_POST['t']);
	else $type = 0;
	
	$free_server = range($DHCP_RANGE_SERVER[0], $DHCP_RANGE_SERVER[1], 1);
	$free_printer = range($DHCP_RANGE_PRINTER[0], $DHCP_RANGE_PRINTER[1], 1);
	$free_ipdev = range($DHCP_RANGE_IPDEV[0], $DHCP_RANGE_IPDEV[1], 1);
	$free_client = range($DHCP_RANGE_CLIENT[0], $DHCP_RANGE_CLIENT[1], 1);
	
	// list all current hosts
	$result = search($conn, $BASE_DN_DHCP, 'objectclass=iscdhcphost', array('iscdhcpstatements'));
	if ($result) {
		$occ_client = array();
		$occ_printer = array();
		$occ_ipdev = array();
		$occ_server = array();
		
		// build list with all used IPs
		for ($i=0; $i < $result["count"]; $i++) {
			$entry = cleanup($result[$i]);
			$ip = hostStatementToNumber($entry['iscdhcpstatements'], '.');
			switch(true) {
				case ($ip >= $DHCP_RANGE_SERVER[0] && $ip <= $DHCP_RANGE_SERVER[1]):
					array_push($occ_server, $ip); break;
				case ($ip >= $DHCP_RANGE_PRINTER[0] && $ip <= $DHCP_RANGE_PRINTER[1]):
					array_push($occ_printer, $ip); break;
				case ($ip >= $DHCP_RANGE_IPDEV[0] && $ip <= $DHCP_RANGE_IPDEV[1]):
					array_push($occ_ipdev, $ip); break;
				default:
					array_push($occ_client, $ip); break;
			}
		}

		// remove used client IPs
		foreach ($occ_client as $k => $v) {
			unset($free_client[$v - $DHCP_RANGE_CLIENT[0]]);
		}
		// remove used printer IPs
		foreach ($occ_printer as $k => $v) {
			unset($free_printer[$v - $DHCP_RANGE_PRINTER[0]]);
		}
		// remove used ipdev IPs
		foreach ($occ_ipdev as $k => $v) {
			unset($free_ipdev[$v - $DHCP_RANGE_IPDEV[0]]);
		}
		// remove used server IPs
		foreach ($occ_server as $k => $v) {
			unset($free_server[$v - $DHCP_RANGE_SERVER[0]]);
		}
		
		// next free ip
		switch($type) {
			case 1:
				$free = array_values($free_printer); break;
			case 2:
				$free = array_values($free_server); break;
			case 3:
				$free = array_values($free_ipdev); break;
			default:
				$free = array_values($free_client); break;
		}
		
		$next = hostNumberToIPString($free[0]);
		$mac = $cookie_data['iscdhcphwaddress'];
		// Location uebernehmen
		$location = $cookie_data['location'];
		
		if ($location == "")
		    $location = "-";
		
		// create DHCP entry
		$attributes = array(
			'iscdhcphwaddress' => $mac,
			'iscdhcpstatements' => "fixed-address $DHCP_IP_BASE.$next",
			'iscdhcpcomments' => "$location",
			'objectclass' => array('top', 'iscdhcphost')
		);
		$ok1 = add($conn, "cn=$cn,$BASE_DN_DHCP", $attributes);
		if ($ok1)
		{
		    // Workaround
		    // DNS Eintraege muessen mit "exec samba-tool dns" erzeugt werden.
		    // moddnsrecords ist ein bash Frontend fuer samba-tool
		    $ipaddress = "$DHCP_IP_BASE.$next";
		    $fqdn = "$cn.$DOMAIN";
		    shell_exec("sudo /usr/bin/moddnsrecords a A $cn $ipaddress;");
		    shell_exec("sudo /usr/bin/moddnsrecords a PTR $ipaddress $fqdn;");
		}
		else
		    return array(ldap_errno($conn) => ldap_error($conn));
	}
}

// host mod
function hostModify($conn, $cn) {
	global $BASE_DN_DHCP, $cookie_data, $DOMAIN, $DHCP_IP_BASE;
	$attributes = $cookie_data;

	// if cn has changed, rename DHCP&DNS-forward, change DNS-reverse
	if (isset($attributes['cn'])) {
		$newcn = $attributes['cn'];
		unset($attributes['cn']);

		// DNS-Eintraege aendern
		$result = search($conn, $BASE_DN_DHCP, "cn=$cn", array('iscdhcpstatements'));
		if ($result) {
			$result = cleanup($result[0]);
			// Workaround!
			// DNS Eintraege muessen mit "exec samba-tool dns" geloescht werden.
			// moddnsrecords ist ein bash Frontend fuer samba-tool
			$fullip = hostStatementToFullIp($result['iscdhcpstatements']);
			// Name kann nicht geaendert werden, geht nur ueber loeschen und neu anlegen
			// Nur IP koennte geandert werden
			shell_exec("sudo /usr/bin/moddnsrecords r A $cn $fullip;");
			shell_exec("sudo /usr/bin/moddnsrecords a A $newcn $fullip;");

			//$oldfqdn = "$cn.$DOMAIN";
			$newfqdn = "$newcn.$DOMAIN";
			shell_exec("sudo /usr/bin/moddnsrecords u PTR $fullip $newfqdn;");
		}
		// rename DHCP
		rename_ldap($conn, "cn=$cn,$BASE_DN_DHCP", "cn=$newcn");
	}

	// modify DHCP Location
	if (empty($attributes)) {
	    $ok1 = 1;
	} else {
	    if (isset($newcn)) {
		$ok1 = modify($conn, "cn=$newcn,$BASE_DN_DHCP", $attributes);
	    } else {
		$ok1 = modify($conn, "cn=$cn,$BASE_DN_DHCP", $attributes);
	    }
	}
	return ($ok1)?0:array(ldap_errno($conn) => ldap_error($conn));
}

// host delete
function hostDelete($conn, $cn) {
	global $BASE_DN_DHCP, $DOMAIN, $DHCP_IP_BASE;
	// fetch associated IP for cn
	$result = search($conn, $BASE_DN_DHCP, "cn=$cn", array('iscdhcpstatements'));
	if ($result) {
		$result = cleanup($result[0]);
		// Workaround
		// DNS Eintraege muessen mit "exec samba-tool dns" geloescht werden.
		// moddnsrecords ist ein bash Frontend fuer samba-tool
		$fqdn = "$cn.$DOMAIN";
		$fullip = hostStatementToFullIp($result['iscdhcpstatements']);
		shell_exec("sudo /usr/bin/moddnsrecords r PTR $fullip $fqdn;");
		shell_exec("sudo /usr/bin/moddnsrecords r A $cn $fullip;");
		
		// DHCP entry
		$ok1 = delete($conn, "cn=$cn,$BASE_DN_DHCP");

		return ($ok1)?0:array(ldap_errno($conn) => ldap_error($conn));
	}
}

// War gedacht um host mit dynamisch vergebenen IPs mit festen leases zu versehen
// derzeit noch unbenutzt.
function hostDiscover($conn) {
	
}


//--------------------
// helpers
//--------------------

function supportMail($conn, $from) {
	global $cookie_data, $PORTAL_SUPPORT_MAIL, $DOMAIN;
	include('Mail.php');
	
	$recipients = $PORTAL_SUPPORT_MAIL;
	
	$headers['From']    = "$from@$DOMAIN";
	$headers['To']      = $PORTAL_SUPPORT_MAIL;
	$headers['Subject'] = $cookie_data['subject'];
	
	$body = $cookie_data['msg'];
	
	$params['sendmail_path'] = '/usr/sbin/sendmail';
	
	// Create the mail object using the Mail::factory method
	$mail_object =& Mail::factory('sendmail', $params);
	$flag = $mail_object -> send($recipients, $headers, $body);
	return ($flag === true)?0:'Ihre Email konnte nicht gesendet werden!';
}

function fileUploadProgress($conn, $id) {
	$status = apc_fetch(ini_get('apc.rfc1867_prefix') . $id);
	//return array('id' => $id, 'current' => $status['current'], 'total' => $status['total']);
	return array($id, $status);
}

//--------------------
// other stuff
//--------------------

// links listing for user
function linksList($conn, $uid) {
	global $BASE_DN_USER;
	$result = search($conn, $BASE_DN_USER, "CN=$uid", array('labeledURI'));
	if ($result) {
		$result = cleanup($result[0]);
		unset($result['dn']);
		return $result;
	}
}

//--------------------
// main functionality
//--------------------

$conn = connect();
$bind = bind($conn);

// Gruppe maildummies mit UNIX-Attributen erweitern, wenn noch nicht geschehen
// Informationen zur Gruppe "maildummies" sammeln

$mdgroup = "maildummies";
$collection = $adldap->group()->infoCollection($mdgroup,array('*'));
$mdrid = ridfromsid(bin_to_str_sid($collection->objectsid));

if (empty($collection->mssfu30nisdomain)) {

    // Klappt nur mit Zarafa, sollte auch mit anderen Mailsystemen funktionieren
    if ( $GROUPWARE == "zarafa" ) {
	$attributes = array(
	    "mssfu30nisdomain" => $NISDOMAIN,
	    "mssfu30name" => $mdgroup,
	    "gidnumber" => 9500,
	    "zarafaaccount" => true
	);
    } else {
	$attributes = array(
	    "mssfu30nisdomain" => $NISDOMAIN,
	    "mssfu30name" => $mdgroup,
	    "gidnumber" => 9500
	);
    }


    try {
	$result = $adldap->group()->modify($mdgroup,$attributes);
    }
    catch (adLDAPException $e) {
	echo $e;
	exit();   
    }
}



foreach ( $SMB_GROUPSTOEXTEND as $extgroup ) {
    $collection = $adldap->group()->infoCollection($extgroup,array('*'));
    $grouprid = ridfromsid(bin_to_str_sid($collection->objectsid));
    if (empty($collection->mssfu30nisdomain)) {
	$attributes = array(
	    "mssfu30nisdomain" => $NISDOMAIN,
	    "mssfu30name" => $extgroup,
	    "gidnumber" => ( $grouprid + $SFU_GUID_BASE )
	);

	try {
	    $result = $adldap->group()->modify($extgroup,$attributes);
	}
	catch (adLDAPException $e) {
	    echo $e;
	    exit();   
        }
    }
}



//--------------------
// commands allowed for users
$ALLOWED_CMDS = array('user_detail', 'user_mod', 'links_list', 'support_mail', 'upload_progress', 'download');

if (($cookie_auth['uid'] == $USR && (array_search($CMD, $ALLOWED_CMDS) !== false)) || ($adldap->user()->inGroup($cookie_auth['uid'],"Domain Admins") !== false)) {
//if (true) {
	if ($CMD == 'user_list') {
		echo json_encode(userList($conn));
	}
	elseif ($CMD == 'user_list_short') {
		echo json_encode(userListShort($conn));
	}
	elseif ($CMD == 'group_list') {
		echo json_encode(groupList($conn));
	}
	elseif ($CMD == 'host_list') {
		echo json_encode(hostList($conn));
	}
	elseif ($CMD == 'service_list') {
		echo json_encode(serviceList($conn));
	}
	elseif($CMD == 'links_list') {
		echo json_encode(linksList($conn, $USR));
	}
	elseif ($CMD == 'user_detail') {
		echo json_encode(userDetail($USR));
	}
	elseif ($CMD == 'group_detail') {
		echo json_encode(groupDetail($conn, $USR));
	}
	elseif ($CMD == 'host_detail') {
		echo json_encode(hostDetail($conn, $USR));
	}
	elseif ($CMD == 'user_delete') {
		echo json_encode(userDelete($USR));
	}
	elseif ($CMD == 'group_delete') {
		echo json_encode(groupDelete($USR));
	}
	elseif ($CMD == 'host_delete') {
		echo json_encode(hostDelete($conn, $USR));
	}
	elseif ($CMD == 'hostDiscover') {
		echo json_encode(hostDiscover($conn, $USR));
	}
	elseif ($CMD == 'user_create') {
		echo json_encode(userCreate($USR));
	}
	elseif ($CMD == 'group_create') {
		echo json_encode(groupCreate($conn, $USR));
	}
	elseif ($CMD == 'host_create') {
		echo json_encode(hostCreate($conn, $USR));
	}
	elseif ($CMD == 'user_mod') {
		echo json_encode(userModify($USR));
	}
	elseif ($CMD == 'group_mod') {
		echo json_encode(groupModify($conn, $USR));
	}
	elseif ($CMD == 'host_mod') {
		echo json_encode(hostModify($conn, $USR));
	}
	elseif ($CMD == 'service_start') {
		echo json_encode(serviceControl('start', $USR));
	}
	elseif ($CMD == 'service_stopp') {
		echo json_encode(serviceControl('stop', $USR));
	}
	elseif ($CMD == 'service_restart') {
		echo json_encode(serviceControl('restart', $USR));
	}
	elseif ($CMD == 'service_reload') {
		echo json_encode(serviceControl('reload', $USR));
	}
	elseif ($CMD == 'support_mail') {
		echo json_encode(supportMail($conn, $USR));
	}
	elseif ($CMD == 'upload_progress') {
		echo json_encode(fileUploadProgress($conn, $_POST['id']));
	}
} else {
	header("HTTP/1.0 401 Unauthorized");
	die();
}

//--------------------

unbind($conn);

?>