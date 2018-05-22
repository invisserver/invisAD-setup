<?php
# Dieses Script erzeugt einen neuen fetchmail-Account für den Zugriff auf ein externes
# Postfach. Alle Daten des Accounts werden im lokalem LDAP Verzeichnis unterhalb des
# zugehörigen Knotens additionalUserInformation abgelegt.

#Formularvariablen übernehmen
$mailserver=$_POST["mailserver"];
$extaddress=$_POST["extaddress"];
$protokoll=$_POST["protokoll"];
$kennung=$_POST["kennung"];
$extpasswd=$_POST["extpasswd"];
$luser="$corusername@$DOMAIN";

# SSL oder nicht
if ($protokoll == "pop3s" or $protokoll == "imaps") {
	$protokoll = substr($protokoll,0 ,4);
	$fspMailFetchOpts = "here ssl fetchall";
} else {
	$fspMailFetchOpts = "here fetchall";
}

// Am LDAP per SimpleBind anmelden
if ($bind) {
    // hier userknoten erstellen
    $r=ldap_search($ditcon,"cn=$corusername,$LDAP_SUFFIX_AUI","(cn=$corusername)");
    if ( $r == false) {
	$userinfo["cn"]="$corusername";
	$userinfo["name"]="$corusername";
	$userinfo["description"]="Email-Konten von $corusername";
	$userinfo["objectclass"]="top";
	$userinfo["objectclass"]="container";
	ldap_add($ditcon,"$coruserdn",$userinfo);
    }

    // Daten vorbereiten
    $account["fspExtMailAddress"]="$extaddress";
    $account["fspExtMailServer"]="$mailserver";
    $account["fspExtMailProto"]="$protokoll";
    $account["fspExtMailUserName"]="$kennung";
    $account["fspExtMailUserPW"]="$extpasswd";
    $account["fspMailFetchOpts"]="$fspMailFetchOpts";
    $account["fspLocalMailAddress"]="$luser";
    $account["objectclass"]="top";
    $account["objectclass"]="fspFetchMailAccount";
    $dn2 = ("cn=$extaddress,$coruserdn");
    // hinzufügen der Daten zum Verzeichnis
    $r=ldap_add($ditcon, $dn2, $account);

    $filter="(&(fspMainMailAddress=*)(fspLocalMailAddress=$corusername@*))";
    $entries=search($ditcon, "$coruserdn", $filter);
    if ($entries["count"] == 0) { 
	// Daten vorbereiten
	$account2["fspLocalMailAddress"]="$luser";
	$account2["fspLocalMailHost"]="$COR_LOCAL_IMAP_SERVER";
	$account2["fspMainMailAddress"]="$extaddress";
	$account2["objectclass"]="top";
	$account2["objectclass"]="fspLocalMailRecipient";
	$dn2 = ("cn=$luser,$coruserdn");
	// hinzufügen der neuen primär Adresse
	$r=ldap_add($ditcon, $dn2, $account2);
    }

    // Mail Attribut im Benutzerkonto anpassen
    $filter = "(samAccountName=$corusername)";
    $justthese = array("mail");
    $entries = search($ditcon,$BASE_DN_USER,$filter,$justthese);
    $mail = $entries[0]['mail'][0];
    // nur, wenn das Attribut bisher die interne Adresse enthaelt
    // oder leer ist.
    if ( "$mail" == "$luser" || empty("$mail") || "$mail" == "-" ) {
	$mailattr = array( 'mail' => "$extaddress" );
	$r = modify($ditcon, $aduserdn, $mailattr);
	$othermb = array('othermailbox' => "$luser");
	$r = ldap_mod_add($ditcon, $aduserdn, $othermb);
    } elseif ( "$mail" != "$extaddress" ) {
	$othermb = array('othermailbox' => "$extaddress");
	$r = ldap_mod_add($ditcon, $aduserdn, $othermb);
    }
    
//Status wechseln um neuen Account aufzunehmen
if ( $status == "Anwesend" ) {
    absent($corusername);
    // fetchcopy ausfuehren
    sudocmd('fetchcopy');

    $filter="(&(fspExtMailServer=*)(fspLocalMailAddress=$corusername@*))";
    $justthese = array( "fspExtMailAddress", "fspExtMailProto", "fspExtMailUsername", "fspExtMailServer", "fspExtMailUserPw", "fspMailfetchOpts");
    $entries=search($ditcon, $LDAP_SUFFIX_AUI, $filter, $justthese);

    // fetchmailrc schreiben.
    bfmrc($entries,$corusername);
    // fetchcopy ausfuehren
    sudocmd('fetchcopy');

    $ausgabe = "<b>Status:</b> Das regelmäßige Abrufen Ihrer eMails wurde für folgende Adressen aktiviert:<p>";
    $i=0;
    foreach ($entries as $zugangsdaten) {
	$Address = $entries[$i]["fspextmailaddress"][0];
	$ausgabe = "$ausgabe <b>$Address</b><p>";
	$i++;
    }
}
} else {
    echo "Verbindung zum LDAP Server nicht möglich!";
}

$margin = "Ihre Mailkonten";
$info = "<p></p><p><center><b>Ihr neuer Zugang wurde mit folgenden Daten angelegt:</b></center></p><p><center>Mail-Server: $mailserver</center></p><p><center>Protokoll: $protokoll</center></p><p><center>Benutzerkennung: $kennung</center></p><p><center>Passwort: $extpasswd</center></p><p><center>Lokale Adresse: $luser</center></p><p></p>";
site_info($margin, $info);
site_back();
?>
