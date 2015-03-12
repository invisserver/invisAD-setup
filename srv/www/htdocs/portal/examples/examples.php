<?php
/*
Examples file

To test any of the functions, just change the 0 to a 1.
*/
require_once('../config.php');
require_once('../ldap.php');
require_once('../inc/adfunctions.inc.php');

// Array mit Globalvariablen bilden
$options = array(
		    'domain_controllers' => array("$FQDN"),
		    'account_suffix' => "@$DOMAIN",
		    'base_dn' => "$LDAP_SUFFIX",
		    'admin_username' => "$LDAP_ADMIN",
		    'admin_password' => "$LDAP_BIND_PW");

//error_reporting(E_ALL ^ E_NOTICE);

include (dirname(__FILE__) . "/../inc/adLDAP.php");
try {
    $adldap = new adLDAP($options);
}
catch (adLDAPException $e) {
    echo $e;
    exit();   
}
//var_dump($ldap);

//--------------------
// main functionality
//--------------------

$conn = connect();
$bind = bind($conn);


echo ("<pre>\n");

// authenticate a username/password
if (0) {
	$result = $adldap->authenticate("stefan", 'P@$$w0rd');
	if ($result == true) {
	    var_dump($result);
	}
}

function groupCN($conn, $dn) {
	global $BASE_DN_USERS;
	$result = search($conn, $dn, 'objectclass=*', array('samaccountname'));
	$entry = cleanup($result[0]);
	//$result = ldap_get_attributes($conn, $dn);
	return $entry;
}


if (1) {
  $collection = $adldap->group()->infoCollection("Domain Computers",array('*'));

  $gtype=dechex(trim(($collection->grouptype)));
  $zaccount=($collection->zarafaaccount);
  $gidnumber=($collection->gidnumber);
  print_r("GID: $gidnumber <br>");
  
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

    print_r($typevalue);
/*  echo $collection->member[0];
  $result = groupCN($conn, $collection->member[0]);
  var_dump($result);
  echo $result['samaccountname'];
*/
  

}

// add a group to a group
if (0) {
	$result = $adldap->group()->addGroup("Parent Group Name", "Child Group Name");
	var_dump($result);
}

// add a user to a group
if (0) {
	$result = $adldap->group()->addUser("Group Name", "username");
	var_dump($result);
}

// create a group
if (0) {
	$attributes=array(
		"group_name"=>"Test Group",
		"description"=>"Just Testing",
		"container"=>array("Users"),
	);
	$result = $adldap->group()->create($attributes);
	var_dump($result);
}

// retrieve information about a group
if (0) {
    // Raw data array returned
	$result = $adldap->group()->infoCollection("ffff",array('*'));
	$rid = ridfromsid(bin_to_str_sid($result->objectsid));
	echo $rid;
}

if (0) {
	// Raw data array returned
	$result = $adldap->group()->all();
	//var_dump($result);
	$json = array();
	foreach ($result as $i => $value) {
	    $collection = $adldap->group()->infoCollection("$result[$i]", array("*") );
	    //print_r($collection->member);
	    //print_r($collection->description);
	    $rid = ridfromsid(bin_to_str_sid($collection->objectsid));
	    echo "$result[$i] - $rid <br>";
	    $entry = array("$result[$i]",$rid);
	    // create JSON response
	    array_push($json, $entry);
	}
	return $json;
}

if (0) {
	// Raw data array returned
	$result = $adldap->user()->all();
	//var_dump($result);
	$json = array();
	foreach ($result as $i => $value) {
	    $collection = $adldap->user()->infoCollection("$result[$i]", array("*") );
	    //print_r($collection->member);
	    //print_r($collection->description);
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
		//echo "$value <br>";
		$typevalue = $typevalue + $value;
	    }

	    echo "$result[$i] - $rid - $typevalue <br>";

	    $entry = array("$result[$i]",$rid);
	    // create JSON response
	    array_push($json, $entry);
	}
	return $json;
}

// create a user account
if (0) {
	$attributes=array(
		"username"=>"iarmbrust",
		"logon_name"=>"iarmbrust@invis-ad.loc",
		"firstname"=>"Ines",
		"surname"=>"Armbrust",
		"company"=>"FSP",
		"department"=>"Geld",
		"email"=>"iarmbrust@invis-ad.loc",
		"container"=>array("Users"),
		"enabled"=>1,
		"password"=>"Passw#rd123",
//		"primarygroupid"=>"Domain Users",
//		"mssfu30nisdomain"=>"orr2014-net",
//		"mssfu30name"=>"bpink"
	);
	
    try {
	$result = $adldap->user()->create($attributes);
//	    var_dump($result);
    }
    catch (adLDAPException $e) {
        echo $e;
        exit();   
    }

    $result = $adldap->group()->addUser("Domain Guests", "iarmbrust");

    $attrmod=array("primarygroupid"=>"514");
    var_dump($attrmod);
    try {
	$result = $adldap->user()->modify("iarmbrust",$attrmod);
    }
    catch (adLDAPException $e) {
        echo $e;
        exit();   
    }
    $result = $adldap->group()->removeUser("Domain Users", "iarmbrust");

}

// retrieve the group membership for a user
if (0) {
	$result = $adldap->user()->groups("username");
	print_r($result);
}

// retrieve information about a user
if (0) {
    // Raw data array returned
	$result = $adldap->user()->infoCollection("administrator", array("*"));
	//print_r($result);
	echo $result->givenname."<br>";
	echo $result->sn."<br>";
	echo $result->displayname."<br>";
	echo $result->samaccountname."<br>";
	echo adtstamp2date($result->accountExpires)."<br>";
	echo ridfromsid(bin_to_str_sid($result->objectsid))."<br>";

}

// check if a user is a member of a group
if (0) {
	$result = $adldap->user()->inGroup("bpink","gr578");
	var_dump($result);
}

// modify a user account (this example will set "user must change password at next logon")
if (0) {
	$attributes=array(
		"change_password"=>1,
	);
	$result = $adldap->user()->modify("username",$attributes);
	var_dump($result);
}

// change the password of a user. It must meet your domain's password policy
if (0) {
    try {
        $result = $adldap->user()->password("username","Password123");
        var_dump($result);
    }
    catch (adLDAPException $e) {
        echo $e; 
        exit();   
    }
}

// see a user's last logon time
if (0) {
    try {
        $result = $adldap->user()->getLastLogon("username");
        var_dump(date('Y-m-d H:i:s', $result));
    }
    catch (adLDAPException $e) {
        echo $e; 
        exit();   
    }
}

// list the contents of the Users OU
if (0) {
    $result=$adldap->folder()->listing(array('Users'), adLDAP::ADLDAP_FOLDER, false);
    var_dump ($result);   
}
?>