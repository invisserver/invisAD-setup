<?php

/* script/status.php v1.0
 * AJAX script, displaying several server status messages/numbers
 * (C) 2009 Daniel T. Bender, invis-server.org
 * (C) 2010,2015,2016 Stefan Schäfer, invis-server.org
 * License GPLv3
 * Questions: daniel@invis-server.org
 */

require_once('../inc/functions.inc.php');
require_once('../config.php');

// check if request comes from internal address
$EXTERNAL_ACCESS = !(ipinnet($_SERVER['REMOTE_ADDR'], $IP_NETBASE_ADDRESS, $DHCP_IP_MASK));

// Script not allowed from external without login!
if ($EXTERNAL_ACCESS && !isset($_COOKIE['invis'])) die();

$CMD = $_POST['c'];
	
if ($CMD == 'basic_info') {
	echo '<b>Servername:</b><br />' . shell_exec('hostname -f') . '<br />';
	echo '<span style="font-size: 0.7em;">(' . trim(shell_exec('uname -r')) .')</span><br /><br />';
	
	echo '<b>Serverzeit:</b><br />' . shell_exec('date +"%d.%m.%Y, %H:%M"') . 'Uhr<br /><br />';
	
	$uptime = intval(shell_exec('cat /proc/uptime | cut -d"." -f1'));
	
	// 60 = 1m
	// 3600 = 1h
	// 86400 = 1d
	
	$up_d = floor($uptime / 86400);
	$up_h = floor(($uptime - $up_d * 86400) / 3600);
	$up_m = floor(($uptime - $up_d * 86400 - $up_h * 3600) / 60);
	
	$uptime_string = "$up_d Tage, $up_h Stunden, $up_m Minuten";
	echo '<b>Uptime:</b><br />' . $uptime_string . '<br /><hr>';			// neu

}
elseif ($CMD == 'inet_info'){
	$file_inet = file('/var/spool/results/inetcheck/inetcheck');
	echo '<b>Internet:</b><br />';
	echo '<span style="font-size: 0.7em;">Zeit: ' . $file_inet[0] . ' Uhr</span><br/>';
	echo 'Status: ';
	switch(intval($file_inet[1])) {
		case 0: echo '<b style="color: green;">online</b>'; break;
		case 1: echo '<b style="color: orange;">kein DNS</span></b>'; break;
		case 2: echo '<b style="color: orange;">schlechte Verbindung</span></b>'; break;
		case 3: echo '<b style="color: red">offline</b>'; break;
	}
	echo '<br />';
	echo '<span style="font-size: 0.8em;">IP: <b>' . ((isset($file_inet[2]))?$file_inet[2]:'-') . '</b></span>';
	echo '<br /><br /><br /><br />';
}
elseif ($CMD == 'hd_info') {
	$file_raid = file('/var/spool/results/diskchecker/status');
	echo '<b>Festplatten:</b><br />';
	$raid_error = false;
	echo '<span style="font-size: 0.7em;">Zeit: ' . $file_raid[0] . ' Uhr</span><br />';
	for ($i = 1; $i < count($file_raid); $i++) {
		// RAID or HD
		$data = explode(' ', $file_raid[$i]);
		$tmp = substr($data[0], 0, 2);
		
		if ($tmp == 'md') {
			echo '<span style="font-size: 0.8em;">RAID-Verbund </span><b style="font-size: 0.8em;"><i>' . $data[0] . '</i></b>';
			if ($data[1] == 'nOK') {
				$raid_error = true;
				echo ': <b style="font-size: 0.75em; color: red;">' . $data[2] . '</b><br>';
			} else
				echo ': <b style="font-size: 0.8em; color: green;">' . $data[1] . '</b><br>';
		} else if ($tmp == 'sd') {
			echo '<span style="font-size: 0.8em;">Festplatte </span><b style="font-size: 0.8em;"><i>' . $data[0] . '</i></b>';
			if ($data[1] == 'OK') {
				echo ': <b style="font-size: 0.8em; color: green;">' . $data[1]. ' ' . $data[2] . '°C</b><br>';
			} else {
				$raid_error = true;
				echo ': <b style="font-size: 0.75em; color: red;">Smart-Fehler ' . $data[2] . '°C</b><br>';
			}
		} else if ($tmp == "pv") {
			echo '<hr>';
			echo '<b style="font-size: 0.75em;">Plattenplatz-Reserve: ' . $data[1] . $data[2] . '</b>';
			echo '<hr>';
		}
		echo '<span style="font-size: 0.3em;"> </span>';
	}
	if ($raid_error) {
		echo '<b style="font-size: 0.7em; color: red;">Ein Fehler ist aufgetreten, bitte wenden Sie sich umgehend an Ihren Administrator!</b><br />';
	}
}
elseif ($CMD == 'capacity_info') {
	echo '<hr>';
	echo '<b>Festplattenauslastung:</b>';
	echo '</td></tr><tr><td valign="top" align="center">';
	echo '<table border="0" style="font-size: 0.8em; border: 1px solid #e0e0e0;">';
	echo '<tr><th>Verzeichnis</th><th align="center">% belegt</th><th align="center">GB belegt</th><th align="center">GB gesamt</th></tr>';
	foreach ($STATUS_WATCH_DIRS as $dir) {
		echo '<tr>';
		$total = disk_total_space($dir) / 1024 / 1024 / 1024;
		$free = disk_free_space($dir) / 1024 / 1024 / 1024;
		$used = $total - $free;
		$used_factor = $used / $total;
		$used_percent = $used_factor * 100;
		
		$max = 620;
		
		$red = dechex(128 + 127 * $used_factor);
		$green = dechex(255 - 127 * $used_factor);
		
		echo "<th>$dir</th>" .'
			<td align="center">' . round($used_percent, 2) . '</td>
			<td align="center">' . round($used, 2) . '</td>
			<td align="center">' . round($total, 2) . '</td>';
		
		echo"<tr>
				<td colspan='4'>
					<div style='width: " . $max . "px; border: 1px solid #000000'>
						<div style='padding: 2px; width: ".($max * $used_factor)."px; border-right: 1px solid black; background-color: #".$red.$green."55;'>&nbsp;</div>
						</div>
					</td>
			</tr>";
	}
	echo "</table>";
}
elseif ($CMD == 'cert_info') {
//	echo '<hr>';
	echo '<b>Serverzertifikate </b><span style="font-size: 0.7em;"> (Verwendungszweck:Ablaufdatum:Status)</span><br>';
	// Status-Datei einlesen
	$file_certs = file('/var/spool/results/certs/certstatus');
	// Datei verarbeiten
	for ($i = 0; $i < count($file_certs); $i++) {
	    // Zeile zerlegen
		$data = explode(':', $file_certs[$i]);
		
		// Welches Zertifikat
		$type = substr($data[0], 0);
		
		//Ablaufdatum & Darstellungsfarbe
		$enddate = substr($data[1], 0);
		$enddatestamp = strtotime($enddate);
		$now = strtotime(date('d.m.Y', time()));
		//Differenz in Tagen
		$diff = ($enddatestamp - $now) / 86400;
		// Differenz <=0 -> rot, 1-7 -> orange, > 7 -> gruen
		if ( $diff > 7 ) {
		    $datestyle = '<b style="font-size: 0.6em; color: green;">';
		} if ( $diff <= 7) {
		    $datestyle = '<b style="font-size: 0.6em; color: orange;">';
		} if ( $diff <= 0) {
		    $datestyle = '<b style="font-size: 0.6em; color: red;">';
		}
		// Zertifikatsstatus & Darstellungsfarbe
		$certstate = substr($data[2], 0, 2);
		if (strcasecmp($certstate, 'OK') == 0) {
		    $statestyle = '<b style="font-size: 0.6em; color: green;">';
		} else {
		    $statestyle = '<b style="font-size: 0.6em; color: red;">';
		}
		echo '<b style="font-size: 0.6em;">' . $type . ': </b>';
		echo $datestyle . $enddate . ': </b>';
		echo $statestyle . $certstate . '</b>&nbsp;';
	}

}
elseif ($CMD == 'backup_info') {
	// Status-Datei einlesen
	$file_backup = file('/var/spool/results/backup/status');
	
	$now = time();
	$last = intval($file_backup[0]);
	$diff_days = floor(($now - $last) / (60 * 60 * 24));

	echo '<b>Datensicherung:</b><br>';
	echo '<span style="font-size: 0.7em;"> Zeit: ' . date('d.m.Y, H:i', $last) . '</span><br />';
	
	// Quick n dirty -> wenn die zweite Zeile der Datei genau ein Zeichen lang ist,
	// kann davon ausgegangen werden, dass es sich um die Anzahl der zu sichernden 
	// Volumes handelt. Dann wird die neue Ausgabe erzeugt.
	if (strlen(trim($file_backup[1])) == 1) {
	    // Anzahl der zu sichernden Volumes ermitteln
	    $buvolcount = intval($file_backup[1]);
	    // Stefan -- Multiline Results added.
	    // Jetzt Zeilen 3 bis X in der Status-Datei durchgehen.
	    $success = 0;
	    foreach($file_backup as $num => $line) {
		if ($num > 1) {
		    $line = explode(" ", $line);
		    // Achtung hierbei ist "0" ok, da wir ansonsten nicht die exit-codes von rsync ausgeben könnten.
		    $backup_state = ($line[1] == 0)? '<b style="color: green; font-size: 0.9em;">Erfolgreich</b>':'<b style="color: red; font-size: 0.9em;">Fehler (Nr: ' .  $line[1] . ')</b>';
		    if ($line[1] == 0) {
			$success = $success + 1;
		    }
		    echo '<span style="font-size: 0.9em;"> Status: '. $backup_state . ' Quelle: ' . $line[0] .'</span><br />';
		}
	    }
	    if ($success == $buvolcount) {
		echo '<span style="font-size: 0.7em;"> Anzahl erfolgreicher Sicherungen: <b style="color: green;">' . $success . "/" . $buvolcount . '</b></span><br />';
	    } else {
		echo '<span style="font-size: 0.7em;"> Anzahl erfolgreicher Sicherungen: <b style="color: red;">' . $success . "/" . $buvolcount . '</b></span><br />';
	    }
	} else {
	    foreach($file_backup as $num => $line) {
		if ($num > 0) {
		    $line = explode(" ", $line);
		    // Achtung hierbei ist "0" ok, da wir ansonsten nicht die exit-codes von rsync ausgeben könnten.
		    $backup_state = ($line[1] == 0)? '<b style="color: green; font-size: 0.9em;">Erfolgreich</b>':'<b style="color: red; font-size: 0.9em;">Fehler (Nr: ' .  $line[1] . ')</b>';
		    echo '<span style="font-size: 0.9em;"> Status: '. $backup_state . ' Quelle: ' . $line[0] .'</span><br />';
		}
	    }
	}
	
	// Nächstes Backup 
	if ($diff_days > $STATUS_BACKUP_TIMER) {
		$overdue = ($diff_days - $STATUS_BACKUP_TIMER);
		echo "<span style='font-size: 0.8em; color: red; font-weight: bold;'>Datensicherung $overdue Tage überfällig!</span>";
		}
	else
		echo "<span style='font-size: 0.8em;'>Nächste Datensicherung in <u>" . ($STATUS_BACKUP_TIMER - $diff_days) . "</u> Tagen</span>";
	echo "<br/>";

	// Ist die Dasiplatte voll?
	$file_diskfull = file('/var/spool/results/backup/full');
	$disk_state = ($file_diskfull[0] < 90)? '<b style="color: green; font-size: 0.8em;">'. $file_diskfull[0] .'</b>':'<b style="color: red; font-size: 0.8em;">'. $file_diskfull[0] .'</b>';
	echo '<span style="font-size: 0.8em;"> Datensicherungsplatte zu '. $disk_state . '% voll.</span><br />';
	echo "<br/>";

}
elseif ($CMD == 'usv_status') {
	// Status-Datei einlesen
	$file_usv = file('/var/spool/results/usv/usvstat');
	$lables = array('USV Typ', 'Status', 'Akku-Ladung', 'V-Spannung', 'Last', 'USV-Temp', 'Akku-Pufferzeit');

	echo '<b>USV Status:</b><br>';
	
	foreach($lables as $key => $lable) {
		$value = explode( ":", $file_usv[$key]);
		switch ($value[0]) {
		    case 'Empty':
			echo '<span style="font-size: 0.8em;"> '. $lable . ': </span><b style="font-size: 0.8em; color: black"> ' . $value[1] . '</b><br />';
			break;
		    case 'Fault':
			echo '<span style="font-size: 0.8em;"> '. $lable . ': </span><b style="font-size: 0.8em; color: red"> ' . $value[1] . '</b><br />';
			break;
		    case 'Label':
			echo '<span style="font-size: 0.8em;"> '. $lable . ': </span><b style="font-size: 0.8em; color: black"> ' . $value[1] . '</b><br />';
			break;
		    case 'Normal':
			echo '<span style="font-size: 0.8em;"> '. $lable . ': </span><b style="font-size: 0.8em; color: green"> ' . $value[1] . '</b><br />';
			break;
		    case 'Warning':
			echo '<span style="font-size: 0.8em;"> '. $lable . ': </span><b style="font-size: 0.8em; color: orange"> ' . $value[1] . '</b><br />';
			break;
		}
	}
}

?>
