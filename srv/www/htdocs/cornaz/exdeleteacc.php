<?php

$account = $_REQUEST["account"];

// Am LDAP per SimpleBind anmelden
if ($bind) {
    // Loeschen eines Mail-Accounts
    $dn2 = ("cn=$account,cn=$corusername,$COR_LDAP_SUFFIX");
    ldap_delete($ditcon, $dn2);
} else {
    echo "Verbindung zum LDAP Server nicht möglich!";
}

//Status wechseln um neuen Account aufzunehmen
if ( $status == "Anwesend" ) {
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
	exec ("sudo $COR_PATH/bin/fetchcopy");
	// Am LDAP per SimpleBind anmelden
	if ($bind) {
		$filter="(&(fspExtMailServer=*)(fspLocalMailAddress=$corusername*))";
		$justthese = array( "fspExtMailAddress", "fspExtMailProto", "fspExtMailUsername", "fspExtMailServer", "fspExtMailUserPw", "fspMailfetchOpts");
		$sr=ldap_search($ditcon, $COR_LDAP_SUFFIX, $filter, $justthese);
		$entries = ldap_get_entries($ditcon, $sr);
	} else {
		echo "Verbindung zum LDAP Server nicht möglich!";
	}
	// Warum auch immer, ich musste das erste Element des entries-Arrays loeschen.
	array_shift($entries);
	$i=0;
	foreach ($entries as $zugangsdaten) {
		$fh = fopen("$COR_FETCHMAILRC_BUILD","a");
		$Server = $entries[$i]["fspextmailserver"][0];
		$Proto = $entries[$i]["fspextmailproto"][0];
		$Extuser = $entries[$i]["fspextmailusername"][0];
		$Passwd = $entries[$i]["fspextmailuserpw"][0];
		$Opts = $entries[$i]["fspmailfetchopts"][0];
		$zeile = ("poll $Server proto $Proto user $Extuser pass $Passwd is $corusername $Opts\n");
		fwrite($fh, "$zeile");
		fclose($fh);
		$i++;
	}
	exec ("sudo $COR_PATH/bin/fetchcopy");
	$ausgabe = "<b>Status:</b> Das regelmäßige Abrufen Ihrer eMails wurde für folgende Adressen aktiviert:<p>";
	$i=0;
	foreach ($entries as $zugangsdaten) {
		$Address = $entries[$i]["fspextmailaddress"][0];
		$ausgabe = "$ausgabe <b>$Address</b><p>";
		$i++;
	}

}

// Info Zeile
$margin = "Mailkonten";
$info = "<p><hr size=\"1\" noshade width=\"300\" center></p>
<p><center>Der Account <b>$account</b> wurde gelöscht.</center></p>
<p><hr size=\"1\" noshade width=\"300\" center></p>";
site_info($margin, $info);
?>