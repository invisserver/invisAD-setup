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

// search LDAP server
function search($conn, $basedn, $filter, $justthese = array("*")) {
    if ($search = ldap_search($conn, $basedn, $filter, $justthese)) {
	return ldap_get_entries($conn, $search);
    } else {
	return false;
    }
}

// modify an entry in the LDAP server
function modify($conn, $basedn, $entry_array) {
    return ldap_modify($conn, $basedn, $entry_array);
}


//--------------------
// CorNAz FUNCTIONS
//--------------------

// "mainMailAddress" mit "mail" synchronisieren
function syncmailattr($mainmaliaddr) {
    global $BASE_DN_USER;
}

// verbleibende Adressen in othermailbox einfuegen
function setothermailbox($otheraddresses) {
    global $BASE_DN_USER;
}

//--------------------
// fetchmailrc FUNCTIONS
//--------------------

// Status ermitteln
function getstate($corusername) {
    global $COR_FETCHMAILRC_BUILD, $COR_PATH;
    // Aktuellen Status ermitteln
    $un = strlen($corusername);
    $unx = 0;
    // Einlesen der Datei .fetchmailrc in ein Array
    $fetchmailrc_b = file ("$COR_FETCHMAILRC_BUILD");
    $stat = 0;
    // Statusüberprüfung
    foreach ($fetchmailrc_b as $zeile) {
	$unx = strlen(strstr($zeile, "$corusername"))-1;
	$n = strlen(chop($zeile)) - $unx;
	if (substr(chop($zeile), $n, $un) == $corusername) {
	    $stat = $stat + 1;
	}
    }
    if ($stat >= 1) {
	$status="Anwesend";
    } else {
	$status="Abwesend";
    }

    // Anwesend aber trotzdem im Urlaub
    if ($status == "Anwesend") {
	if (file_exists ("$COR_PATH/vacation/$corusername.binweg")) {
	    $status="Urlaub";
	}}
    return $status;
}

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

// Konto auf Abwesend setzen
// Switch-Funktion macht keinen Sinn, da Anwesenheit ueber 
// die Funktion bfmrc eingestellt wird.
function absent($corusername) {
    global $COR_FETCHMAILRC_BUILD;
    $fetchmailrc_b = file("$COR_FETCHMAILRC_BUILD");
    $un = strlen($corusername);
    $n = count($fetchmailrc_b);
    $i = 0;
    foreach ($fetchmailrc_b as $key){
	    $unx = strlen(strstr($key, "$corusername"))-1;
	    $nx = strlen(chop($key)) - $unx;
	    if (substr(chop($key), $nx, $un) == $corusername) {
	    unset ($fetchmailrc_b[$i]);
	}
	    $i++;
    }
	$fh = fopen("$COR_FETCHMAILRC_BUILD","w+");
	foreach ($fetchmailrc_b as $zeile) {
	fwrite ($fh, "$zeile");
    }
    fclose($fh);
}

?>