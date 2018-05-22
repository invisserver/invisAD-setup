<?php
if ($status == "Urlaub") {
	$margin = "Abwesend";
	$info = "<p>Hallo <font color=\"#EE4000\"><b>$corusername</b></font>, Sie möchten sich als <font color=\"#EE4000\"><b>Abwesend</b></font> eintragen, haben allerdings eine Urlaubsbenachrichtigung aktiviert.<br>
	Auch wenn es paradox erscheinen mag, ergibt diese Konstellation keinen Sinn.<br>
	Wenn Sie möchten, dass eingehende Mails automatisch beantwortet werden, müssen Sie als <font color=\"#EE4000\"><b>Anwesend</b></font> geführt sein. Nur wenn Ihre eMails abgeholt werden, können sie auch automatisch beantwortet werden.<br>
	Um das automatische Abholen Ihrer Mails zu stoppen (Status: \"Abwesend\"), beenden Sie zunächst die aktivierte Urlaubsbenachrichtigung über die Schaltfläche <b>\"Urlaubsende\"</b> auf der CorNAz-Hauptseite.</p>";
	site_info($margin, $info);
} else {
	//Auf abwesend setzen
	absent($corusername);
	// fetchcoppy ausfuehren
	sudocmd('fetchcopy');

	$vorgang = "Sie haben sich als <font color=\"#EE4000\"><b>\"Abwesend\"</b></font> eingetragen.";
	$ausgabe = "<b>Status: </b> Ihre eMails werden vorübergehend nicht abgeholt.";
	//Info Zeile
	$margin = "Status";
	$info = "<p><p><center>$vorgang</center><p><center>$ausgabe</center><p><p>";
	site_info($margin, $info);
	site_back();;
}

?>