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
require_once('../config.php');
// adLDAP Klasse einbinden und Objekt erzeugen
require_once('../inc/adLDAP.php');
require_once('../inc/adfunctions.inc.php');

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
	    
	    // Benutzerty ermitteln
	    $utval = array();
	    $typevalue = 0;
	    
	    if ( $pgid == "514" ) {
		$utval[0] = 1; 
	    }
	    if ( $pgid == "513" ) {
		$utval[1] = 2; 
	    }
	    if ( $shell == "/bin/false" ) {
		$utval[2] = 4; 
	    }
	    if ( $shell == "/bin/bash" ) {
		$utval[3] = 8; 
	    }
	    if ( $maildummy == "1" ) {
		$utval[4] = 16; 
	    }
	    if ( $admin == true ) {
		$utval[5] = 32; 
	    }
	    if ( $gwaccount == true ) {
		$utval[6] = 64; 
	    }
	    foreach ($utval as $val => $value) {
		$typevalue = $typevalue + $value;
	    }
	    
	    switch ($typevalue) {
		case 1:
		    $type = 0;
		    break;
		case 2:
		    $type = 2;
		    break;
		case 10:
		    $type = 3;
		    break;
		case 34:
		    $type = 5;
		    break;
		case 42:
		    $type = 6;
		    break;
		case 74:
		    $type = 4;
		    break;
		case 84:
		    $type = 1;
		    break;
		case 170:
		    $type = 7;
		    break;
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
	    'mail' => $result->mail,
	    'office' => $result->physicaldeliveryofficename,
	    'telephone' => $result->telephonenumber,
	// adtstamp2date($result->accountExpires)."<br>";
	    'rid' => ridfromsid(bin_to_str_sid($result->objectsid)));
	
	return $userdetails;
}

function userCreate() {
	global $cookie_data, $adldap;
	// read user data from cookie
	$attributes = $cookie_data;

}

function userModify($uid) {
	global $cookie_data, $adldap;
	// read user data from cookie
	$attributes = $cookie_data;
	
	// wenn das Passwort Attribut zurueck geliefert wird
	// passwort aendern und attribut aus array löschen
	
	if ( ! empty($attributes->userpassword)) {
	    try {
		$result = $adldap->user()->password("$uid","$attributes->userpassword");
		//var_dump($result);
	    }
	    catch (adLDAPException $e) {
		return $e; 
		//exit();   
	    }
	unset($attributes->userpassword);
	}
	
	//echo $attributes['office'];
	$result = $adldap->user()->modify("$uid",$attributes);
	return $adldap->getLastError();
	//return $e;
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
	    $entry = array("rid" => "$rid","cn" => "$result[$i]","gidnumber" => $collection->gidNumber );
	    // create JSON response
	    array_push($json, $entry);
	}
	return $json;
}

function groupCreate() {
	global $cookie_data, $adldap, $NISDOMAIN, $SFU_GUID_BASE;
	$attributes=array(
		"group_name"=>$cookie_data['cn'],
		"description"=>$cookie_data['description'],
		"mssfu30nisdomain"=>$NISDOMAIN,
		"mssfu30name"=>$cookie_data['cn'],
		// Container Auswahl evtl spaeter.
		"container"=>array("Users")
	);
	
	$ok = $adldap->group()->create($attributes);
	
	// jetzt wirds lustig -> GID muss erzeugt werden.
	// rid ermitteln
	$result = $adldap->group()->infoCollection($cookie_data['cn'],array('*'));
	$rid = ridfromsid(bin_to_str_sid($result->objectsid));
	$gidnumber = $SFU_GUID_BASE + $rid;
	$attributes = array(
		"gidNumber"=>$gidnumber
	);
	$resultmod = $adldap->group()->modify($cookie_data['cn'],$attributes);
	
	// Gruppenverzeichnis anlegen
	if ($ok) {
		shell_exec("sudo /usr/bin/creategroupshare $cn;");
	}
	
	$members = $cookie_data['memberuid'];
	// Mitglieder hinzufuegen
	foreach ($members as $i => $member) {
		$result = $adldap->group()->addUser($cookie_data['cn'], "$member");
	}
	if ($ok) {
	    return 0;
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
		
		// DNS Eintraege muessen mit "exec samba-tool dns" erzeugt werden.
	}
}

// host mod
function hostModify($conn, $cn) {
	global $BASE_DN_DHCP, $cookie_data, $DOMAIN;
	$attributes = $cookie_data;
	
	// if cn has changed, rename DHCP&DNS-forward, change DNS-reverse
	if (isset($attributes['cn'])) {
		$newcn = $attributes['cn'];
		unset($attributes['cn']);
		
		// Test fuer neues Atttribut
		echo $attributes['location'];
		
		// rename DHCP
		rename_ldap($conn, "cn=$cn,$BASE_DN_DHCP", "cn=$newcn");
	}
	// modify DHCP
	// Hier scheint es mit MS AD LDAP ein Problem zu geben
	// aus irgend einem Grund gibt er Fehlercode 32 "no such object"
	// zurueck, fuehrt die gewuenschte Aenderung aber trotzdem aus.
	// Daher Quick and Dirty -> Fehercode 32 wird als Erfolg zurueck
	// geliefert.
	$ok1 = modify($conn, "cn=$cn,$BASE_DN_DHCP", $attributes);
	if ( ldap_errno($conn) == '32'  ){
	    return 0;
	} else {
	    return ($ok1)?0:array(ldap_errno($conn) => ldap_error($conn));
	}
}

// host delete
function hostDelete($conn, $cn) {
	global $BASE_DN_DHCP;
	// fetch associated IP for cn
	$result = search($conn, $BASE_DN_DHCP, "cn=$cn", array('iscdhcpstatements'));
	if ($result) {
		$result = cleanup($result[0]);
		$ip = strrchr($result['iscdhcpstatements'], '.');
		$ip = substr($ip, 1);
		
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
		echo json_encode(userDelete($conn, $USR));
	}
	elseif ($CMD == 'group_delete') {
		echo json_encode(groupDelete($conn, $USR));
	}
	elseif ($CMD == 'host_delete') {
		echo json_encode(hostDelete($conn, $USR));
	}
	elseif ($CMD == 'hostDiscover') {
		echo json_encode(hostDiscover($conn, $USR));
	}
	elseif ($CMD == 'user_create') {
		echo json_encode(userCreate($conn, $USR));
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