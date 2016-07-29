<?php
// Script zur Auswahl der primären email-Adresse
// Dieses Script liest alle fetchmail-Accounts des Users $Username
// aus dem LDAP Verzeichnis und listet sie auf. Sie koennen
// dann die gewünschte Adresse auswählen.

// Verbindung zum LDAP Server aufbauen

if ($bind) {
	$filter="(&(fspExtMailServer=*)(fspLocalMailAddress=$corusername*))";
	$justthese = array( "fspExtMailAddress", "fspExtMailProto", "fspExtMailUsername", "fspExtMailServer", "fspExtMailUserPw", "fspMailfetchOpts");
	$sr=ldap_search($ditcon, $COR_LDAP_SUFFIX, $filter, $justthese);
	$entries = ldap_get_entries($ditcon, $sr);
} else {
	echo "Verbindung zum LDAP Server nicht möglich!";
}
// Warum auch immer, ich musste das erste Element des entries-Arrays löschen.
array_shift($entries);
//Info Zeile
$margin = "Ihre Mailkonten";
$info = "<font size=\"-1\">Die folgende Liste zeigt alle für Sie eingerichteten Mailkonten.<br>Über die Schaltfläche \"Löschen\" können Sie einzelne Konten wieder aus der Serverkonfiguration entfernen.<br>Es gehen dabei keine bereits empfangenen Mails verloren.<br><font color=\"red\"><b>Achtung: Es erfolgt keine weitere Nachfrage!</b></font></font>";
site_info($margin, $info);
$i=0;
foreach ($entries as $val) {
	//Formular oeffnen
	$script = "./base.php";
	open_form($script);
	echo "<input type=\"hidden\" name=\"file\" value=\"exdeleteacc.php\" />\n";
	$Adresse = $entries[$i]["fspextmailaddress"][0];
	$Server = $entries[$i]["fspextmailserver"][0];
	$extuser = $entries[$i]["fspextmailusername"][0];
	$margin = ("Löschen?");
	$inhalt_s1 = array("<input type=submit value=Löschen>","70");
	$inhalt_s2 = array("<input type=hidden name=account value=$Adresse>User: <b>$corusername</b>","100");
	$inhalt_s3 = array("Account: <b>$Adresse</b> - $Server - $extuser<p>","80%");
	$val_n = array($inhalt_s1, $inhalt_s2, $inhalt_s3);
	table_row_n($val_n, $margin);
	$i++;
	//Formular schliessen
	close_form();
}

?>
