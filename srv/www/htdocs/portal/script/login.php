<?php

/* script/login.php v1.1
 * AJAX login-script, checking given password against ldap-stored ssha hash
 * (C) 2009 Daniel T. Bender, invis-server.org
 * (C) 2013 Ingo Göppert, invis-server.org
 * (C) 2013 Stefan Schäfer, invis-server.org
 * License GPLv3
 * Questions: invis-user@ml.invis-server.org
 */

	// include important ldap and stuff
	require_once('../ldap.php');
	require_once('../inc/adLDAP.php');
	require_once('../config.php');
	
	
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
	
	// message to be sent if authorization fails
	function unauthorized() {
		header("HTTP/1.0 401 Unauthorized");
	}
	
	// no cookie, no competition
	if (!isset($_COOKIE['invis-login'])) {
		unauthorized();
		error_log("Unauthorized access: User \"" . $data['uid'] . "\" has no cookie set (1, login.php).");
	} else {
		// pull JSON object from cookie
		$data = json_decode($_COOKIE['invis-login'], true);

		// get user information
		$response = $adldap->user()->infoCollection($data['uid'], array("*"));
		
		// check if request comes from internal address
		$EXTERNAL_ACCESS = (substr($_SERVER['REMOTE_ADDR'], 0, strripos($_SERVER['REMOTE_ADDR'], '.')) != $DHCP_IP_BASE);
		$USER_IS_ALLOWED = true;
		//$EXTERNAL_ACCESS = true;
		// Ermitteln, ob der User Mitglied der Gruppe mobiluser ist und sich somit auch von extern anmelden darf.
		if ($EXTERNAL_ACCESS == true) {
			//$USER_IS_ALLOWED = array_search($data['uid'], mobilUsers($conn)); //Anpassen auf adLDAP
			$USER_IS_ALLOWED = $adldap->user()->inGroup($data['uid'],"mobilusers");
			$pwdexpiry = $adldap->user()->passwordExpiry($data['uid']);

			if ($USER_IS_ALLOWED === false)
			{
			    error_log("Unauthorized access: User \"" . $data['uid'] . "\" is not a mobiluser (2, login.php).");
			}
		}
		// Prüfung ob $response != false ist herausgenommen.
		if ($USER_IS_ALLOWED !== false) {
			$result = array('uid' => "$response->samaccountname", 
					'displayname' => "$response->displayname", 
					'sn' => "$response->sn", 
					'cn' => "$response->givenname", 
					'PWD_EXPIRE' => $pwdexpiry['expiryformat'],
					'uidnumber' => ridfromsid(bin_to_str_sid("$response->objectsid")));
			$challenge = $adldap->authenticate($data['uid'], $data['pwd']);
			error_log($challenge);
			// test given password against
			if ($adldap->authenticate($data['uid'], $data['pwd'])) {
				// Restlaufzeit bis Ablauf des Kontos ermitteln
				echo json_encode($result); //Rueckgabe an cookie?
				error_log("Authorized access: User \"" . $data['uid'] . "\" login successful (3, login.php).");
			}
			else
			{
			    unauthorized();
			    error_log("Unauthorized access: User \"" . $data['uid'] . "\" password check failed (4, login.php).");
			}
		} else {
			// no entry found OR general connection problems 
			//echo ldap_error($conn);
			unauthorized();
			error_log("Unauthorized access: User \"" . $data['uid'] . "\" general error. LDAP error: \"" . "\" (5, login.php)");
		}
	}
?>
