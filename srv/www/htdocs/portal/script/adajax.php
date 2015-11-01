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
require_once('../ldap.php');
// Hinzugefügt nach Erweiterung der config.php (SMB_HOSTNAME) 21.07.2009 -- Stefan
require_once('../config.php');
// adLDAP Klasse einbinden und Objekt erzeugen
require_once('../inc/adLDAP.php');
require_once('../inc/adfunctions.inc.php');
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
function userList() {
	global $cookie_data, $adldap;
	// Raw data array returned
	$result = $adldap->user()->all();
	//var_dump($result);
	$json = array();
	foreach ($result as $i => $value) {
	    $collection = $adldap->user()->infoCollection("$result[$i]", array("*") );
	    $rid = ridfromsid(bin_to_str_sid($collection->objectsid));
	    $pgid = $collection->primarygroupid;
	    $shell = $collection->loginshell;
	    $gwaccount = $collection->zarafaaccount;
	    $admin = $adldap->user()->inGroup("$result[$i]","Domain Admins");
	    $maildummy = $adldap->user()->inGroup("$result[$i]","maildummies");
	    $enterpriseadmin = $adldap->user()->inGroup("$result[$i]","Enterprise Admins");
	    
	    // Benutzertyp ermitteln
	    $utval = array();
	    $typevalue = 0;
	    
	    if ( $pgid == "514" ) {
		$utval[0] = 1; 
	    }
	    if ( $pgid == "513" ) {
		$utval[1] = 2; 
	    }
	    if ( $pgid == "512" ) {
		$utval[1] = 4; 
	    }
	    if ( $pgid == "9500" ) {
		$utval[1] = 8; 
	    }
	    if ( $shell == "/bin/false" ) {
		$utval[2] = 16; 
	    }
	    if ( $shell == "/bin/bash" ) {
		$utval[3] = 32; 
	    }
	    if ( $maildummy == "1" ) {
		$utval[4] = 64; 
	    }
	    if ( $admin == true ) {
		$utval[5] = 128; 
	    }
	    if ( $gwaccount == true ) {
		$utval[6] = 256; 
	    }
	    if ( $enterpriseadmin == "1" ) {
		$utval[7] = 512;
	    }
	    foreach ($utval as $val => $value) {
		$typevalue = $typevalue + $value;
	    }
	    
	    switch ($typevalue) {
		case 1:
		    $type = 0;
		    break;
		case 24:
		    $type = 1;
		    break;
		case 2:
		    $type = 2;
		    break;
		case 34:
		    $type = 3;
		    break;
		case 290:
		    $type = 4;
		    break;
		case 132:
		    $type = 5;
		    break;
		case 164:
		    $type = 6;
		    break;
		case 420:
		    $type = 7;
		    break;
		case 642:
		case 674:
		    $type = 8;
		}
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
	    'mail' => $result->mail,
	    'telephone' => $result->telephonenumber,
	// adtstamp2date($result->accountExpires)."<br>";
	    'rid' => ridfromsid(bin_to_str_sid($result->objectsid)));
	
	return $userdetails;
}

function userCreate($uid) {
	global $cookie_data, $adldap, $DOMAIN, $NISDOMAIN, $COMPANY, $mdrid, $SMB_HOSTNAME, $SFU_GUID_BASE, $GROUPWARE;
	// read user data from cookie
	//$attributes = $cookie_data;

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
	
	// Profilpfad
	$profilepath = "\\\\$SMB_HOSTNAME\\profiles\\$uid";
	$smbhomepath = "\\\\$SMB_HOSTNAME\\$uid";

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
	
	// Benutzer anlegen
	switch ($accounttype) {
	case 0:
	    // Gastbenutzer
	    // Standard-Attribute - unabhaengig vom Kontentyp
	    $attributes=array(
		"username"=>$uid,
		"logon_name"=>$cookie_data['uid']."@".$DOMAIN,
		"firstname"=>$cookie_data['firstname'],
		"surname"=>$cookie_data['surname'],
		"description"=>$cookie_data['description'],
		"company"=>"$COMPANY",
		"department"=>$cookie_data['department'],
		"office"=>$cookie_data['office'],
		"telephone"=>$cookie_data['telephone'],
		"email"=>$cookie_data['uid']."@".$DOMAIN,
		"container"=>array("Users"),
		"enabled"=>1,
		"password"=>$password
	    );
	    // Benutzer anlegen
	    try {
		$result = $adldap->user()->create($attributes);
	    } catch (adLDAPException $e) {
		if (! empty($e)) {
		    return $e;
		    exit();
		}
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
	    // Benutzer aus Gruppe "Domain Users" entfernen
	    try {
		$result = $adldap->group()->removeUser("Domain Users", "$uid");
	    } catch (adLDAPException $e) {
		if (! empty($e)) {
		    return $e;
		    exit();
		}
	    }
	    break;
	case 1:
	    // Maildummy Konto
	    // Standard-Attribute - unabhaengig vom Kontentyp
	    $attributes=array(
		"username"=>$uid,
		"logon_name"=>$cookie_data['uid']."@".$DOMAIN,
		"firstname"=>$cookie_data['firstname'],
		"surname"=>$cookie_data['surname'],
		"description"=>$cookie_data['description'],
		"company"=>"$COMPANY",
		"department"=>$cookie_data['department'],
		"office"=>$cookie_data['office'],
		"telephone"=>$cookie_data['telephone'],
		"email"=>$cookie_data['uid']."@".$DOMAIN,
		"container"=>array("Users"),
		"enabled"=>1,
		"password"=>$password
	    );
	    // Benutzer anlegen
	    try {
		$ok = $adldap->user()->create($attributes);
	    } catch (adLDAPException $e) {
		if (! empty($e)) {
		    return $e;
		    exit();
		}
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
	    // Attribute anpassen
	    $attrmod=array(
	        'mssfu30nisdomain' => "$NISDOMAIN",
		'mssfu30name' => $cookie_data['uid'],
		'primarygroupid' => $mdrid,
		'gidnumber' => $mdgidnumber,
		'loginshell' => '/bin/false',
		'unixhomedirectory' => "/home/".$cookie_data['uid']
	    );
	    try {
		$result = $adldap->user()->modify("$uid",$attrmod);
	    } catch (adLDAPException $e) {
		if (! empty($e)) {
    		    return $e;
    		    exit();
    		}
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
	    break;
	case 2:
	    // reiner Windows User

	//$firstname = iconv("UTF-8","UTF-16",$cookie_data['firstname']);


	    // Standard-Attribute - unabhaengig vom Kontentyp
	    $attributes=array(
		"username"=>$uid,
		"logon_name"=>$cookie_data['uid']."@".$DOMAIN,
		// Test Charset Konvertierung => klappt nicht mit UCS-4
//		"firstname"=>$firstname,
		"firstname"=>$cookie_data['firstname'],
		"surname"=>$cookie_data['surname'],
		"description"=>$cookie_data['description'],
		"company"=>"$COMPANY",
		"department"=>$cookie_data['department'],
		"office"=>$cookie_data['office'],
		"telephone"=>$cookie_data['telephone'],
		"email"=>$cookie_data['uid']."@".$DOMAIN,
		"container"=>array("Users"),
		"enabled"=>1,
		"home_drive"=>'u:',
		"home_directory"=>$smbhomepath,
		"profile_path"=>$profilepath,
		"script_path"=>"user.cmd",
		"password"=>$password
	    );
	    // Benutzer anlegen
	    try {
		$ok = $adldap->user()->create($attributes);
	    } catch (adLDAPException $e) {
		if (! empty($e)) {
		    return $e;
		    exit();
		}
	    }
		break;
	case 3:
	    // Windows und UNIX Benutzer
	    // Attribute anpassen
	    // Standard-Attribute - unabhaengig vom Kontentyp
	    $attributes=array(
		"username"=>$uid,
		"logon_name"=>$cookie_data['uid']."@".$DOMAIN,
		"firstname"=>$cookie_data['firstname'],
		"surname"=>$cookie_data['surname'],
		"description"=>$cookie_data['description'],
		"company"=>"$COMPANY",
		"department"=>$cookie_data['department'],
		"office"=>$cookie_data['office'],
		"telephone"=>$cookie_data['telephone'],
		"email"=>$cookie_data['uid']."@".$DOMAIN,
		"container"=>array("Users"),
		"enabled"=>1,
		"home_drive"=>'u:',
		"home_directory"=>$smbhomepath,
		"profile_path"=>$profilepath,
		"script_path"=>"user.cmd",
		"password"=>$password
	    );
	    // Benutzer anlegen
	    try {
		$ok = $adldap->user()->create($attributes);
	    } catch (adLDAPException $e) {
		if (! empty($e)) {
		    return $e;
		    exit();
		}
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
	    break;
	case 4:
	    // Windows und UNIX Benutzer mit Groupware-Nutzung
	    // Attribute anpassen
	    // Standard-Attribute - unabhaengig vom Kontentyp
	    $attributes=array(
		"username"=>$uid,
		"logon_name"=>$cookie_data['uid']."@".$DOMAIN,
		"firstname"=>$cookie_data['firstname'],
		"surname"=>$cookie_data['surname'],
		"description"=>$cookie_data['description'],
		"company"=>"$COMPANY",
		"department"=>$cookie_data['department'],
		"office"=>$cookie_data['office'],
		"telephone"=>$cookie_data['telephone'],
		"email"=>$cookie_data['uid']."@".$DOMAIN,
		"container"=>array("Users"),
		"enabled"=>1,
		"home_drive"=>'u:',
		"home_directory"=>$smbhomepath,
		"profile_path"=>$profilepath,
		"script_path"=>"user.cmd",
		"password"=>$password
	    );
	    // Benutzer anlegen
	    try {
		$ok = $adldap->user()->create($attributes);
	    } catch (adLDAPException $e) {
		if (! empty($e)) {
		    return $e;
		    exit();
		}
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
	    break;
	case 5:
	    // Windows Admin ohne Zusatz-Attribute
	    // Standard-Attribute - unabhaengig vom Kontentyp
	    $attributes=array(
		"username"=>$uid,
		"logon_name"=>$cookie_data['uid']."@".$DOMAIN,
		"firstname"=>$cookie_data['firstname'],
		"surname"=>$cookie_data['surname'],
		"description"=>$cookie_data['description'],
		"company"=>"$COMPANY",
		"department"=>$cookie_data['department'],
		"office"=>$cookie_data['office'],
		"telephone"=>$cookie_data['telephone'],
		"email"=>$cookie_data['uid']."@".$DOMAIN,
		"container"=>array("Users"),
		"enabled"=>1,
		"home_drive"=>'u:',
		"home_directory"=>$smbhomepath,
		"profile_path"=>$profilepath,
		"script_path"=>"admin.cmd",
		"password"=>$password
	    );
	    // Benutzer anlegen
	    try {
		$ok = $adldap->user()->create($attributes);
	    } catch (adLDAPException $e) {
		if (! empty($e)) {
		    return $e;
		    exit();
		}
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
	    // Primaergruppe auf "Domain Admins" setzen
	    // ist vermutlich nicht notwendig.
	    $attrmod=array("primarygroupid"=>"512");
	    try {
		$result = $adldap->user()->modify("$uid",$attrmod);
	    } catch (adLDAPException $e) {
		if (! empty($e)) {
    		    return $e;
    		    exit();
    		}
	    }
		break;
	case 6:
	    // Windows-Admin mit UNIX-Attributen
	    // keine UNIX Admin-Befugnisse
	    // Standard-Attribute - unabhaengig vom Kontentyp
	    $attributes=array(
		"username"=>$uid,
		"logon_name"=>$cookie_data['uid']."@".$DOMAIN,
		"firstname"=>$cookie_data['firstname'],
		"surname"=>$cookie_data['surname'],
		"description"=>$cookie_data['description'],
		"company"=>"$COMPANY",
		"department"=>$cookie_data['department'],
		"office"=>$cookie_data['office'],
		"telephone"=>$cookie_data['telephone'],
		"email"=>$cookie_data['uid']."@".$DOMAIN,
		"container"=>array("Users"),
		"enabled"=>1,
		"home_drive"=>'u:',
		"home_directory"=>$smbhomepath,
		"profile_path"=>$profilepath,
		"script_path"=>"admin.cmd",
		"password"=>$password
	    );
	    // Benutzer anlegen
	    try {
		$ok = $adldap->user()->create($attributes);
	    } catch (adLDAPException $e) {
		if (! empty($e)) {
		    return $e;
		    exit();
		}
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
	    break;
	case 7:
	    // Windows-Admin mit UNIX-Attributen und Groupware-Admin-Rechten
	    // Standard-Attribute - unabhaengig vom Kontentyp
	    $attributes=array(
		"username"=>$uid,
		"logon_name"=>$cookie_data['uid']."@".$DOMAIN,
		"firstname"=>$cookie_data['firstname'],
		"surname"=>$cookie_data['surname'],
		"description"=>$cookie_data['description'],
		"company"=>"$COMPANY",
		"department"=>$cookie_data['department'],
		"office"=>$cookie_data['office'],
		"telephone"=>$cookie_data['telephone'],
		"email"=>$cookie_data['uid']."@".$DOMAIN,
		"container"=>array("Users"),
		"enabled"=>1,
		"home_drive"=>'u:',
		"home_directory"=>$smbhomepath,
		"profile_path"=>$profilepath,
		"script_path"=>"admin.cmd",
		"password"=>$password,
		"zarafaaccount" => true,
		"zarafaadmin" => true
	    );
	    // Benutzer anlegen
	    try {
		$ok = $adldap->user()->create($attributes);
	    } catch (adLDAPException $e) {
		if (! empty($e)) {
		    return $e;
		    exit();
		}
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
	    break;
	}
	
	if ($ok) {
	    $val = shell_exec("sudo /usr/bin/createhome $uid;");
	}
	
	if (empty($e)) {
	    return 0;
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
		return $e; 
		//exit();   
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
	// sieht bietet adLDAP dafuer keine Funktion, unser eigenes ldap.php
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
			$ip = strrchr($entry['iscdhcpstatements'], '.');
			$ip = intval(substr($ip, 1));
			
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
			$ip = strrchr($entry['iscdhcpstatements'], '.');
			$ip = intval(substr($ip, 1));
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
		
		$next = $free[0];
		$mac = $cookie_data['iscdhcphwaddress'];
		// Location uebernehmen
		$location = $cookie_data['location'];
		
		// create DHCP entry
		$attributes = array(
			'iscdhcphwaddress' => $mac,
			'iscdhcpstatements' => "fixed-address $DHCP_IP_BASE.$next",
			'iscdhcpcomments' => "$location",
			'objectclass' => array('top', 'iscdhcphost')
		);
		$ok1 = add($conn, "cn=$cn,$BASE_DN_DHCP", $attributes);
		// Workaround
		// DNS Eintraege muessen mit "exec samba-tool dns" erzeugt werden.
		// moddnsrecords ist ein bash Frontend fuer samba-tool
		$ipaddress = "$DHCP_IP_BASE.$next";
		$fqdn = "$cn.$DOMAIN";
		shell_exec("sudo /usr/bin/moddnsrecords a A $cn $ipaddress;");
		shell_exec("sudo /usr/bin/moddnsrecords a PTR $ipaddress $fqdn;");
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
		echo $attributes['location'];

		// DNS-Eintraege aendern
		$result = search($conn, $BASE_DN_DHCP, "cn=$cn", array('iscdhcpstatements'));
		if ($result) {
			$result = cleanup($result[0]);
			$ip = strrchr($result['iscdhcpstatements'], '.');
			$ip = substr($ip, 1);
			// Workaround!
			// DNS Eintraege muessen mit "exec samba-tool dns" geloescht werden.
			// moddnsrecords ist ein bash Frontend fuer samba-tool
			$fullip = "$DHCP_IP_BASE.$ip";
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
	if (isset($newcn)) {
	    $ok1 = modify($conn, "cn=$newcn,$BASE_DN_DHCP", $attributes);
	} else {
	    $ok1 = modify($conn, "cn=$cn,$BASE_DN_DHCP", $attributes);
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
		$ip = strrchr($result['iscdhcpstatements'], '.');
		$ip = substr($ip, 1);
		// Workaround
		// DNS Eintraege muessen mit "exec samba-tool dns" geloescht werden.
		// moddnsrecords ist ein bash Frontend fuer samba-tool
		$fqdn = "$cn.$DOMAIN";
		shell_exec("sudo /usr/bin/moddnsrecords r PTR $ip $fqdn;");
		$fullip = "$DHCP_IP_BASE.$ip";
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
// main functionality
//--------------------

$conn = connect();
$bind = bind($conn);

// Gruppe maildummies mit UNIX-Attributen erweitern, wenn noch nicht geschehen
// Informationen zur Gruppe "maildummies" sammeln

$mdgroup = "maildummies";
$collection = $adldap->group()->infoCollection($mdgroup,array('*'));
$mdrid = ridfromsid(bin_to_str_sid($collection->objectsid));
//echo $mdrid;

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