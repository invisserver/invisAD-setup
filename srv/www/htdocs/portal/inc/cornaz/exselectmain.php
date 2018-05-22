<?php

$mainaccount = $_POST["account"];
$localaccount = $_REQUEST["localaddress"];

# Am LDAP per SimpleBind anmelden
if ($bind) {
    //$dn=("cn=$corusername,$COR_LDAP_SUFFIX");
    $filter="(&(fspMainMailAddress=*)(fspLocalMailAddress=$corusername@*))";
    $entries=search($ditcon, $coruserdn, $filter);
    if ($entries["count"] == 1) { 
	// Löschen der alten primär Adresse
	$dn2 = ("cn=$localaccount,$coruserdn");
	ldap_delete($ditcon, $dn2);
    }
    if ($entries["count"] == 0) { 
	// Daten vorbereiten
	$account2["fspLocalMailAddress"]="$luser";
	$account2["fspLocalMailHost"]="$COR_LOCAL_IMAP_SERVER";
	$account2["fspMainMailAddress"]="$mainaccount";
	$account2["objectclass"]="top";
	$account2["objectclass"]="fspLocalMailRecipient";
	$dn3 = ("cn=$luser,$coruserdn");
	// hinzufügen der neuen primär Adresse
	$r=ldap_add($ditcon, $dn3, $account2);
    }

    // Alles folgende muss evtl. ins vorherige if.
    // Attribut otherMailBox anpassen
    // bisherige Versandadresse in omb hinzufuegen
    $filter = "(samAccountName=$corusername)";
    $justthese = array("mail");
    $entries = search($ditcon,$BASE_DN_USER,$filter,$justthese);
    $mail = $entries[0]['mail'][0];
    $othermb = array('othermailbox' => "$mail");
    $r = ldap_mod_add($ditcon, $aduserdn, $othermb);

    // neue Versandadresse aus omb loeschen
    $othermb = array('othermailbox' => "$mainaccount");
    $r = ldap_mod_del($ditcon, $aduserdn, $othermb);

    // Mail Attribut im Benutzerkonto anpassen
    $mailattr = array( 'mail' => "$mainaccount" );
    $r = modify($ditcon, $aduserdn, $mailattr);

} else {
    echo "Verbindung zum LDAP Server nicht möglich!";
}

// Info Zeile
$margin = "Hauptadresse";
$info = "<p></p><p><center>Die Adresse <font color=\"#EE4000\"><b>$mainaccount</b></font> wurde als primäre Adresse für den Mailversand gewählt.</center></p><p></p>";
site_info($margin, $info);
site_back();
?>