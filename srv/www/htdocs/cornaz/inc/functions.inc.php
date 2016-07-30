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

function sudocmd($cmd) {
    global $COR_PATH;
    exec ("sudo $COR_PATH/bin/$cmd");
}

?>