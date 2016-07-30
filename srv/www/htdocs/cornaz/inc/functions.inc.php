<?php
/* ldap.php v1.1
 * LDAP utility functions and ldap_xxx wrapper
 * (C) 2009 Daniel T. Bender, invis-server.org
 * (C) 2011,2016 Stefan Schaefer, invis-server.org
 * License GPLv3
 * Questions: daniel@invis-server.org
 */

require_once('/etc/invis/portal/config.php');

//--------------------
// LDAP FUNCTIONS
//--------------------

// connect to LDAP server
function connect() {
    global $LDAP_SERVER;
    $conn = ldap_connect($LDAP_SERVER);
    ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
    if ($LDAP_TLS = "yes")
	ldap_start_tls($conn);
    return $conn;
}

// bind to LDAP server
function bind($conn) {
    global $LDAP_BIND_DN, $LDAP_BIND_PW;
    return ldap_bind($conn, $LDAP_BIND_DN, $LDAP_BIND_PW);
}


//--------------------
// fetchmailrc FUNCTIONS
//--------------------

// fetchmailrc-Datei erzeugen
function bfmrc($account,$corusername) {
	global $COR_FETCHMAILRC_BUILD;
	// Warum auch immer, ich musste das erste Element des entries-Arrays löschen.
	array_shift($account);
	$i=0;
	foreach ($account as $zugangsdaten) {
		$fh = fopen("$COR_FETCHMAILRC_BUILD","a");
		$server = $account[$i]["fspextmailserver"][0];
		$proto = $account[$i]["fspextmailproto"][0];
		$extuser = $account[$i]["fspextmailusername"][0];
		$passwd = $account[$i]["fspextmailuserpw"][0];
		$opts = $account[$i]["fspmailfetchopts"][0];
		$zeile = ("poll $server proto $proto user $extuser pass $passwd is $corusername $opts\n");
		fwrite($fh, "$zeile");
		fclose($fh);
		$i++;
	}
}

// Shellkommando mit sudo ausfuehren
function sudocmd($cmd) {
    global $COR_PATH;
    exec ("sudo $COR_PATH/bin/$cmd");
}

?>