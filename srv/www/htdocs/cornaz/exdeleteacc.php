<?php

$account = $_REQUEST["account"];

// Am LDAP per SimpleBind anmelden
if ($bind) {
    // Loeschen eines Mail-Accounts
    $dn2 = ("cn=$account,$coruserdn");
    ldap_delete($ditcon, $dn2);

    // USER DNs bilden
    $dn3 = "cn=$luser,$coruserdn";
    // Versandadresse ermitteln
    $filter = "(cn=$luser)";
    $justthese = array("fspMainMailAddress");
    $entries = search($ditcon, $LDAP_SUFFIX_AUI, $filter, $justthese);
    // lokalen Adress-Translation Eintrag loeschen, wenn die Hauptadresse dem zu
    // loeschenden Konto entspricht....
    // Das kann nur eintreten, wenn zuvor alle anderen Konten geloescht wurden.
    // => siehe indeleteaccount.php
    if ( $entries[0]["fspmainmailaddress"][0] == $account ) {
	ldap_delete($ditcon, $dn3);
	// ToDo
	// lokale Adresse aus otherMailbox entfernen
	$attribute = array( 'othermailbox' => "$luser" );
	ldap_mod_del($ditcon, $aduserdn, $attribute);
	// lokale Adresse nach "mail"
	$attribute = array( 'mail' => "$luser" );
	modify($ditcon, $aduserdn, $attribute);
    } else {
	// ...andernfalls Adresse aus othermailbox im Useraccount loeschen
	$attribute = array( 'othermailbox' => "$account" );
	ldap_mod_del($ditcon, $aduserdn, $attribute);
    }

    //Status wechseln um Account zu loeschen
    if ( $status == "Anwesend" ) {
	//voruebergehend auf abwesend setzen
	absent($corusername);
	// fetchcopy ausfuehren
	sudocmd('fetchcopy');
	// Am LDAP per SimpleBind anmelden
	$filter="(&(fspExtMailServer=*)(fspLocalMailAddress=$corusername@*))";
	$justthese = array( "fspExtMailAddress", "fspExtMailProto", "fspExtMailUsername", "fspExtMailServer", "fspExtMailUserPw", "fspMailfetchOpts");
	$entries=search($ditcon, $LDAP_SUFFIX_AUI, $filter, $justthese);
	// fetchmailrc erzeugen.
	bfmrc($entries,$corusername);
	// fetchcopy ausfuehren
	sudocmd('fetchcopy');

	$ausgabe = "<b>Status:</b> Das regelmäßige Abrufen Ihrer eMails wurde für folgende Adressen aktiviert:<p>";
	$i=0;
	foreach ($entries as $zugangsdaten) {
		$address = $entries[$i]["fspextmailaddress"][0];
		$ausgabe = "$ausgabe <b>$address</b><p>";
		$i++;
	}
    }
} else {
    echo "Verbindung zum LDAP Server nicht möglich!";
}

// Info Zeile
$margin = "Mailkonten";
$info = "<p><hr size=\"1\" noshade width=\"300\" center></p>
<p><center>Der Account <b>$account</b> wurde gelöscht.</center></p>
<p><hr size=\"1\" noshade width=\"300\" center></p>";
site_info($margin, $info);
?>