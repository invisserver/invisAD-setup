<?php

$mainaccount = $_POST["account"];
$localaccount = $_REQUEST["localaddress"];
$luser = "$corusername@$DOMAIN";

# Am LDAP per SimpleBind anmelden
if ($bind) {
    $dn=("cn=$corusername,$COR_LDAP_SUFFIX");
    $filter="(&(fspMainMailAddress=*)(fspLocalMailAddress=$corusername*))";
    $entries=search($ditcon, $dn, $filter);
    if ($entries["count"] == 1) { 
	// Löschen der alten primär Adresse
	$dn2 = ("cn=$localaccount,$dn");
	ldap_delete($ditcon, $dn2);
    }
    $filter="(&(fspMainMailAddress=*)(fspLocalMailAddress=$corusername*))";
    $entries=search($ditcon, $dn, $filter);
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
} else {
    echo "Verbindung zum LDAP Server nicht möglich!";
}

// Info Zeile
$margin = "Hauptadresse";
$info = "<p><hr size=\"1\" noshade width=\"300\" center></p><p><center>Die Adresse <font color=\"#EE4000\"><b>$mainaccount</b></font> wurde als primäre Adresse für den Mailversand gewählt.</center></p><p><hr size=\"1\" noshade width=\"300\" center></p>";
site_info($margin, $info);
?>