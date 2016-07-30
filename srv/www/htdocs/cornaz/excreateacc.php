<?php
# Dieses Script erzeugt einen neuen fetchmail-Account für den Zugriff auf ein externes
# Postfach. Alle Daten des Accounts werden im lokalem LDAP Verzeichnis unterhalb des
# zugehörigen Users abgelegt.

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
    //if userknoten nicht da, dann
    $r=ldap_search($ditcon,"cn=$corusername,$COR_LDAP_SUFFIX","(cn=$corusername)") or die;
    if ( $r == false) {
	$userinfo["cn"]="$corusername";
	$userinfo["name"]="$corusername";
	$userinfo["description"]="Email-Konten von $corusername";
	$userinfo["objectclass"]="top";
	$userinfo["objectclass"]="container";
	ldap_add($ditcon,"cn=$corusername,$COR_LDAP_SUFFIX",$userinfo);
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
    $dn2 = ("cn=$extaddress,cn=$corusername,$COR_LDAP_SUFFIX");
    // hinzufügen der Daten zum Verzeichnis
    $r=ldap_add($ditcon, $dn2, $account);

    $filter="(&(fspMainMailAddress=*)(fspLocalMailAddress=$corusername*))";
    $sr=ldap_search($ditcon, "cn=$corusername,$COR_LDAP_SUFFIX", $filter);
    $entries = ldap_get_entries($ditcon, $sr);
    if ($entries["count"] == 0) { 
	// Daten vorbereiten
	$account2["fspLocalMailAddress"]="$luser";
	$account2["fspLocalMailHost"]="$COR_LOCAL_IMAP_SERVER";
	$account2["fspMainMailAddress"]="$extaddress";
	$account2["objectclass"]="top";
	$account2["objectclass"]="fspLocalMailRecipient";
	$dn2 = ("cn=$luser,cn=$corusername,$COR_LDAP_SUFFIX");
	// hinzufügen der neuen primär Adresse
	$r=ldap_add($ditcon, $dn2, $account2);
    }
} else {
    echo "Verbindung zum LDAP Server nicht möglich!";
}

//Status wechseln um neuen Account aufzunehmen
if ( $status == "Anwesend" ) {
	absent($corusername);
	// fetchcopy ausfuehren
	sudocmd('fetchcopy');
	
	// Am LDAP per SimpleBind anmelden
	if ($bind) {
		$filter="(&(fspExtMailServer=*)(fspLocalMailAddress=$corusername*))";
		$justthese = array( "fspExtMailAddress", "fspExtMailProto", "fspExtMailUsername", "fspExtMailServer", "fspExtMailUserPw", "fspMailfetchOpts");
		$sr=ldap_search($ditcon, $COR_LDAP_SUFFIX, $filter, $justthese);
		$entries = ldap_get_entries($ditcon, $sr);
		#	print $entries["count"]." Einträge gefunden<p>";
	} else {
		echo "Verbindung zum LDAP Server nicht möglich!";
	}
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

$margin = "Ihre Mailkonten";
$info = "<p><hr size=\"1\" noshade width=\"300\" center></p><p><center><b>Ihr neuer Zugang wurde mit folgenden Daten angelegt:</b></center></p><p><center>Mail-Server: $mailserver</center></p><p><center>Protokoll: $protokoll</center></p><p><center>Benutzerkennung: $kennung</center></p><p><center>Passwort: $extpasswd</center></p><p><center>Lokale Adresse: $luser</center></p><p><hr size=\"1\" noshade width=\"300\" center></p>";
site_info($margin, $info);

?>
