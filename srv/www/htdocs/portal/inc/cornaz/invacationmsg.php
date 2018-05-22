<?php

# Vorgabetexte
//$mysubject = stripslashes($_SESSION['mailsubject']);
//$mymessage = stripslashes($_SESSION['mailbody']);


if ($status == "Abwesend") {
	$margin = "Urlaub";
	$info = "<p>Hallo <font color=\"#EE4000\"><b>$corusername</b></font>, Sie möchten Urlaubsbenachrichtigungen verwalten, sind allerdings als <font color=\"#EE4000\"><b>\"Abwesend\"</b></font> geführt.<br>
	Auch wenn es paradox erscheinen mag, ergibt diese Konstellation keinen Sinn.<br>
	Solange Sie als <font color=\"#EE4000\"><b>\"Abwesend\"</b></font> geführt sind, werden Ihre eMails nicht abgeholt<br>
	und können somit auch nicht automatisch beantwortet werden.<br>
	Wechseln Sie auf der CorNAz-Hauptseite zunächst Ihren Status auf \"Anwesend\".</p>";
	site_info($margin, $info);
	site_back();
} elseif ($status == "Urlaub"){
	$margin = "Urlaub";
	$info = "<p>Hallo <font color=\"#EE4000\"><b>$corusername</b></font>, Ihre Urlaubsbenachrichtigung ist bereits aktiviert.<br>
	Wenn Sie das ändern möchten ändern Sie auf der CorNAz-Hauptseite zunächst Ihren Status auf \"Urlaubsende\".</p>";
	site_info($margin, $info);
	site_back();
} else {
	// Info Zeile
	$margin = "Urlaub";
	$info = "Hier können Sie eine Abwesenheitsnachricht für <font color=\"#EE4000\">$corusername@$DOMAIN</font> neu oder aus einer vorhandenen Vorlage erstellen. <p>
	<font size=\"-1\"><b>Tipp:</b><br>
	Wenn Sie im Text Ihrer Antwort \"\$SUBJECT\" eingeben, wird an
	dieser Stelle automatisch die Betreffzeile der zu beantwortenden Mail eingefügt.
	 Bei der Verwendung von \"\$SUBJECT\" ist unbedingt darauf zu achten, dass
	\$SUBJECT vollständig in Großbuchstaben geschrieben und es in Anführungszeichen gesetzt ist!<br><b>Beispiel:</b> <font color=\"#EE4000\">Ich habe Ihre Mail betreffend \"\$SUBJECT\" erhalten....</font></font>";
	site_info($margin, $info);
	//Formular oeffnen
	$script = "/?sn=mail";
	open_form($script);

	// Eingabezeilen
	$margin = ("Betreff");
	$inhalt_s1 = array("<b>Betreff:</b> <br> <input type=\"text\" size=\"106\" name=\"mysubject\" value=\"$mysubject\">","100");
	$val_n = array($inhalt_s1);
	table_row_n($val_n, $margin);

	// Eingabezeilen
	$margin = ("Text");
	$inhalt_s1 = array("<b>Nachrichtentext:</b> <br> <Textarea name=\"mymessage\" cols=\"80\" rows=\"8\">$mymessage</Textarea>","100");
	$val_n = array($inhalt_s1);
	table_row_n($val_n, $margin);

	echo "<input type=\"hidden\" name=\"file\" value=\"exvacationmsg.php\" />\n";

	// Submit und Reset
	$val = "Urlaubsbeginn";
	submit_row($val);

	// Formular schliessen
	close_form();
	site_back();
}
?>