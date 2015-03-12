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
    echo "Verbindung: " . $options["domain_controllers"][0] ." " .  $options["account_suffix"] . " " .  $options["base_dn"] . " " .  $options["admin_username"] . " " . $options["admin_password"];
    echo "<br>Verbindungstatus: " . $adldap->getLdapBind();
}
catch (adLDAPException $e) {
    echo $e;
    exit();   
}
//var_dump($ldap);

echo ("<pre>\n");

// authenticate a username/password
if (0) {
	$result = $adldap->authenticate("stefan", 'P@$$w0rd');
	if ($result == true) {
	    var_dump($result);
	}
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
		"container"=>array("Groups","A Container"),
	);
	$result = $adldap->group()->create($attributes);
	var_dump($result);
}

// retrieve information about a group
if (0) {
    // Raw data array returned
	$result = $adldap->group()->info("Guests");
	var_dump($result);
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
	    print_r($collection->grouptype);
	    $rid = ridfromsid(bin_to_str_sid($collection->objectsid));
	    echo "$result[$i] - $rid <br>";
	    $entry = array("$result[$i]",$rid);
	    // create JSON response
	    array_push($json, $entry);
	}
//	return $json;
}

if (0) {
	$maxuidnr = 0;
	$users = $adldap->user()->all();
	foreach ($users as $i => $user) {
	    $collection = $adldap->user()->infoCollection("$result[$i]", array("*") );
	    $uidnumber = $collection->uidnumber;
	    if ( $uidnumber > $maxuidnumber ) {
		$maxuidnumber = $uidnumber;
	    }
	}
	$nextuid = $maxuidnumber + 1;
	echo $nextuid;
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
		    $type = 8;
		    break;
		case 674:
		    $type = 8;
		    break;
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
		"username"=>"bpink",
		"logon_name"=>"bpink@orr2014-net.loc",
		"firstname"=>"Babsi",
		"surname"=>"Pink",
		"company"=>"ORR Inc",
		"department"=>"Smokers Lounge",
		"email"=>"bpink@orr2014-net.loc",
		"container"=>array("Users"),
		"enabled"=>1,
		"password"=>"Password123@!",
		"mssfu30nisdomain"=>"orr2014-net",
		"mssfu30name"=>"bpink",
		"gidnumber"=>"10001",
		"homedirectory"=>"/home/bpink",
		"loginshell"=>"/bin/bash"
	);
	
    try {
    	$result = $adldap->user()->create($attributes);
	    var_dump($result);
    }
    catch (adLDAPException $e) {
        echo $e;
        exit();   
    }
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
	$result = $adldap->user()->inGroup("stefan","mobilusers");
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