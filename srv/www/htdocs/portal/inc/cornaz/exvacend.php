<?php
//Anwesend
		$datei = "$COR_PATH/vacation/$corusername.binda";
		$vorgang = "Willkommen zurück <font color=\"#EE4000\"><b>$corusername</b></font>. Sie hatten hoffentlich einen erholsamen Urlaub.";
		$fp = fopen ($datei, "w");
		fputs ($fp, " ");
		fclose($fp);
		sudocmd('backhome');

#Info Zeile
$margin = "Status";
$info = "</center><p><p><center>$vorgang</center><p><center>$ausgabe</center><p><p>";
site_info($margin, $info);

?>