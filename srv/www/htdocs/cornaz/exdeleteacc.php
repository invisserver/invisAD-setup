<?php

$account = $_REQUEST["account"];

// Am LDAP per SimpleBind anmelden
if ($bind) {
    // Loeschen eines Mail-Accounts
    $dn2 = ("cn=$account,cn=$corusername,$COR_LDAP_SUFFIX");
    ldap_delete($ditcon, $dn2);

    //Status wechseln um neuen Account aufzunehmen
    if ( $status == "Anwesend" ) {
	//voruebergehend auf abwesend setzen
	absent($corusername);
	// fetchcopy ausfuehren
	sudocmd('fetchcopy');
	// Am LDAP per SimpleBind anmelden
	$filter="(&(fspExtMailServer=*)(fspLocalMailAddress=$corusername*))";
	$justthese = array( "fspExtMailAddress", "fspExtMailProto", "fspExtMailUsername", "fspExtMailServer", "fspExtMailUserPw", "fspMailfetchOpts");
	$entries=search($ditcon, $COR_LDAP_SUFFIX, $filter, $justthese);
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