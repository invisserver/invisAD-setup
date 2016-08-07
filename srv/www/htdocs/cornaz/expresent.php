<?php
// Anwesend
$vorgang = "Sie haben sich als <font color=\"#EE4000\"><b>Anwesend</b></font> eingetragen.";
if ($status == "Anwesend") {
	$ausgabe = "<b>Status:</b> Das Abrufen Ihrer Mails ist bereits aktiviert.";
} else {
	// Am LDAP per SimpleBind anmelden
	if ($bind) {
		$filter="(&(fspExtMailServer=*)(fspLocalMailAddress=$corusername*))";
		$justthese = array( "fspExtMailAddress", "fspExtMailProto", "fspExtMailUsername", "fspExtMailServer", "fspExtMailUserPw", "fspMailfetchOpts");
		$sr=ldap_search($ditcon, $LDAP_SUFFIX_AUI, $filter, $justthese);
		$entries = ldap_get_entries($ditcon, $sr);
	} else {
		echo "Verbindung zum LDAP Server nicht möglich!";
	}
	// fetchmailrc erzeugen.
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

// Info Zeile
$margin = "";
$info = "<p><hr size=\"1\" noshade width=\"300\" center><p>
<center>$vorgang</center><p><center>$ausgabe</center><p>
<hr size=\"1\" noshade width=\"300\" center><p>";
site_info($margin, $info);

?>