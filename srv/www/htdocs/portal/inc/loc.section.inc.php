<?php
/* 
 * inc/loc.section.php v1.0
 * portal drop-in, local applications/welcome site
 * (C) 2009 Daniel T. Bender, invis-server.org
 * License GPLv3
 * Questions: daniel@invis-server.org
 */
 
if (!isset($CONF)) die;
require_once('ldap.php');

echo <<<TEST
<div style="text-align: justify; font-size: 0.9em;">Dieses Portal gewährt Ihnen auf einfache Weise Zugriff auf die Funktionen Ihres <b><i>invis Servers</b></i>. Wenn Sie sich über die Schaltfläche <font color="red">"Anmelden"</font> oben rechts am Portal anmelden werden, Ihre Möglichkeiten auf Funktionen des Servers zuzugreifen entsprechend Ihrem Benutzerstatus erweitert. <br>Hinter den Registern oben verbergen sich verschiedene Gruppen von Funktionen und Verknüpfungen:</div>
<hr>
<ul>
<li><div style="text-align: justify; font-size: 0.9em;"><b>Lokal</b> - Dienste die Ihr <b><i>invis Server</i></b> selbst anbietet.</div></li>
<li><div style="text-align: justify; font-size: 0.9em;"><b>Internet</b> - Nützliche Links ins Internet. Am Portal angemeldet können Sie hier eigene Links hinzufügen.</div></li>
<li><div style="text-align: justify; font-size: 0.9em;"><b>Status</b> - Überblick über den Status Ihres <b><i>invis Server</i></b>.</div></li>
<li><div style="text-align: justify; font-size: 0.9em;"><b>? (Helpdesk)</b> - Support-Formular und Dokumentationen zur Handhabung des Servers.</div></li>
</ul>
<hr>
TEST;

// 0:guest, 1:user, 2:admin
$usertype = 0;
if (isset($USER_DATA)) $usertype = 1;
if ($USER_IS_ADMIN) $usertype = 2;

$conn = connect();
$bind = bind($conn);

$server = isset($_SERVER['HTTPS']) ? 'https://'.$_SERVER['HTTP_HOST'] : 'http://'.$_SERVER['HTTP_HOST'];

$result = search($conn, "$LDAP_SUFFIX_PORTAL", 'iportentryposition=lokal');
if ($result) {
	echo '<table>';
	for($i = 0; $i < $result['count']; $i++) {
		$entry = cleanup($result[$i]);
		if ($entry['iportentryactive'] == 'FALSE') continue;
		$type = $entry['iportentrypriv'];
		if (strstr($entry['iportentryurl'], '[servername]') === false)
			if ($entry['iportentryssl'] == 'FALSE'){
				    $url = 'http://'.$entry['iportentryurl'];
				} else {
				    $url = 'https://'.$entry['iportentryurl'];
				}
		else
			$url = str_replace('[servername]', $server, $entry['iportentryurl']);
		
		if ($type == 'guest' || ($type == 'user' && $usertype > 0)) {
			echo '<tr>';
			echo '<td style="text-align: center; background-color: #e0e0e0; border: 1px solid #b0b0b0; padding: 3px;"><a style="text-decoration: none; color: #000000; font-weight: bold;" href="'. $url .'" target="_blank">' . $entry['iportentrybutton'] . '</a></td>';
			echo '<td width="1%"></td>';
			echo '<td style="font-size: 0.9em;">'. $entry['iportentrydescription'] .'</td>';
			echo '</tr>';
		}
	}
	echo '</table>';
} else {
	echo ldap_error($conn);
}

unbind($conn);
?>
