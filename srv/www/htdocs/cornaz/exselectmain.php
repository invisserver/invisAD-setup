<?php

$mainaccount = $_POST["account"];
$localaccount = $_REQUEST["localaddress"];
$luser = "$corusername@$DOMAIN";
# Verbindung zum LDAP Server aufbauen
$ditcon=ldap_connect("$LDAP_SERVER");
# LDAP Protokoll auf Version 3 setzen
if (!ldap_set_option($ditcon, LDAP_OPT_PROTOCOL_VERSION, 3))
    echo "Kann das Protokoll nicht auf Version 3 setzen";
if ($LDAP_TLS = "yes")
    ldap_start_tls($ditcon);

# Am LDAP per SimpleBind anmelden
if ($ditcon) {
    $dn=("cn=$corusername,$COR_LDAP_SUFFIX");
    $r=ldap_bind($ditcon,$LDAP_BIND_DN, "$LDAP_BIND_PW");
    $filter="(&(fspMainMailAddress=*)(fspLocalMailAddress=$corusername*))";
    $sr=ldap_search($ditcon, $dn, $filter);
    $entries = ldap_get_entries($ditcon, $sr);
    if ($entries["count"] == 1) { 
	// Löschen der alten primär Adresse
	$dn2 = ("cn=$localaccount,$dn");
	ldap_delete($ditcon, $dn2);
    }
    $filter="(&(fspMainMailAddress=*)(fspLocalMailAddress=$corusername*))";
    $sr=ldap_search($ditcon, $dn, $filter);
    $entries = ldap_get_entries($ditcon, $sr);
    if ($entries["count"] == 0) { 
	// Daten vorbereiten
	$account2["fspLocalMailAddress"]="$luser";
	$account2["fspLocalMailHost"]="$COR_LOCAL_IMAP_SERVER";
	$account2["fspMainMailAddress"]="$mainaccount";
	$account2["objectclass"]="top";
	$account2["objectclass"]="fspLocalMailRecipient";
	$dn3 = ("cn=$luser,$dn");
	// hinzufügen der neuen primär Adresse
	$r=ldap_add($ditcon, $dn3, $account2);
    }

    ldap_close($ditcon);
} else {
    echo "Verbindung zum LDAP Server nicht möglich!";
}

// Info Zeile
$margin = "Hauptadresse";
$info = "<p><hr size=\"1\" noshade width=\"300\" center></p><p><center>Die Adresse <font color=\"#EE4000\"><b>$mainaccount</b></font> wurde als primäre Adresse für den Mailversand gewählt.</center></p><p><hr size=\"1\" noshade width=\"300\" center></p>";
site_info($margin, $info);
?>