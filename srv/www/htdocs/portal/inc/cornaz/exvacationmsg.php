<?php

$mysubject = $_POST['mysubject'];
$mymessage = $_POST['mymessage'];
// Strings bereinigen
$subject = stripslashes($mysubject);
$message = stripslashes($mymessage);

// Erzeugen einer privaten Urlaubsantwort
$margin = "Ihre<br>Abwesen-<br>heitsnach-<br>richt";
$vorgang = "Sie treten Ihren Urlaub <b>an</b>. Wirklich?";
$info = "<p>$vorgang</p><center><b>Ihr Betreff lautet:</b><p>$subject<p></center><center><b>Ihre Nachricht lautet:</b><p>$message<p></center>";
site_info($margin, $info);
site_back();
// Erzeugen der Datei
$datei = "$COR_PATH/vacation/$corusername.msg";

// nachfolgender Code nicht schoen aber selten...
if (!file_exists ("$datei")) {
	$fp = fopen ($datei, "w+");
	$inhalt = utf8_decode("Subject: $mysubject\n$mymessage");
	fputs ($fp, "$inhalt");
	fclose($fp);

} else {
	if (!is_writeable($datei)){
	echo "pech gehabt - Datei in Bearbeitung<p>";
} else {
	$fp = fopen ($datei, "w+");
	$inhalt = utf8_decode("Subject: $mysubject\n$mymessage");
	fputs ($fp, "$inhalt");
	fclose($fp);
}}

$datei = "$COR_PATH/vacation/$corusername.forward";
$fp = fopen ($datei, "w");
$inhalt = "\\$corusername, \"|/usr/bin/vacation $corusername\"";
fputs ($fp, "$inhalt");
fclose($fp);

sudocmd('holiday');

?>
