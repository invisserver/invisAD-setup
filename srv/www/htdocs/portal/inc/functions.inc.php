<?php

//--------------------
// AD Functions
//--------------------
// both functions are copied from php.net
    // Converts a little-endian hex-number to one, that 'hexdec' can convert
    
function little_endian($hex) {
	$result = "";
        for ($x = strlen($hex) - 2; $x >= 0; $x = $x - 2) {
            $result .= substr($hex, $x, 2);
        }
        return $result;
} 

function bin_to_str_sid($binsid) {

    $hex_sid = bin2hex($binsid);
    $rev = hexdec(substr($hex_sid, 0, 2));
    $subcount = hexdec(substr($hex_sid, 2, 2));
    $auth = hexdec(substr($hex_sid, 4, 12));
    $result    = "$rev-$auth";
    
    for ($x=0;$x < $subcount; $x++) {
        $subauth[$x] =
        hexdec(little_endian(substr($hex_sid, 16 + ($x * 8), 8)));
        $result .= "-" . $subauth[$x];
    }
    // Cheat by tacking on the S-
    return 'S-' . $result;
}

function adtstamp2date($adtstamp) {
    $korrektur = ((1970-1601) * 365.242190) * 86400;
    $unixtstamp = ($adtstamp / 10000000) - $korrektur;   //11644473600;
    $date = date("d.m.Y H:i", $unixtstamp);
    return $date;
}

function ridfromsid($sid) {
    $sidparts = explode("-", $sid);
    $anzelemente = count($sidparts);
    if ($anzelemente > 5) {
	$rid = $sidparts['7'];
    } else {
	$rid = "BI".$sidparts['4'];
    }
    return $rid;
}

//----------------------
// other stuff
//----------------------

// Pruefen, ob eine IP-Adresse in einem gegebenen Netz ist.
// IP-Adressen muessen in Dezimalpunkschreibweise vorliegen
// Netzmask = Anzahl der Bits fuer den Netzanteil
function ipinnet($ip, $net, $shortmask) {
    $bIp = ip2long($ip);                       // Zu pruefende IP in int wandeln
    $bNet = ip2long($net);                     // Eigenes Netz in int wandeln 
    $bMask = 0xffffffff << (32 - $shortmask);  // (32 - $shortmaks) Bits nach links schieben (von rechts mit 0-Bits fuer Hostanteil auffuellen)
    $bNetOfIp = $bIp & $bMask;                 // Netz der gelieferten IP-Adresse bestimmen

    // Debug-Output ins error-log
    //error_log("bIp: ".long2ip($bIp)." bNet: ".long2ip($bNet)." bNetOfIp: ".long2ip($bNetOfIp)." bMask: ".long2ip($bMask));

    // Wenn die Netz-IP identisch ist, sind wir im selben Netz
    return ( $bNet == $bNetOfIp );
}

//-----------------------
// fetchmailrc FUNCTIONS
//-----------------------

// Status ermitteln
function getstate($corusername) {
    global $COR_FETCHMAILRC_BUILD, $COR_PATH;
    // Aktuellen Status ermitteln
    $un = strlen($corusername);
    $unx = 0;
    // Einlesen der Datei .fetchmailrc in ein Array
    $fetchmailrc_b = file ("$COR_FETCHMAILRC_BUILD");
    $stat = 0;
    // Statusüberprüfung
    foreach ($fetchmailrc_b as $zeile) {
	$unx = strlen(strstr($zeile, "$corusername"))-1;
	$n = strlen(chop($zeile)) - $unx;
	if (substr(chop($zeile), $n, $un) == $corusername) {
	    $stat = $stat + 1;
	}
    }
    if ($stat >= 1) {
	$status="Anwesend";
    } else {
	$status="Abwesend";
    }

    // Anwesend aber trotzdem im Urlaub
    if ($status == "Anwesend") {
	if (file_exists ("$COR_PATH/vacation/$corusername.binweg")) {
	    $status="Urlaub";
	}}
    return $status;
}

// fetchmailrc-Datei erzeugen
function bfmrc($account,$corusername) {
    global $COR_FETCHMAILRC_BUILD;
    // Warum auch immer, ich musste das erste Element des entries-Arrays löschen.
    array_shift($account);
    $i=0;
    foreach ($account as $zugangsdaten) {
	$fh = fopen("$COR_FETCHMAILRC_BUILD","a");
	$server = $account[$i]["fspextmailserver"][0];
	$proto = $account[$i]["fspextmailproto"][0];
	$extuser = $account[$i]["fspextmailusername"][0];
	$passwd = $account[$i]["fspextmailuserpw"][0];
	$opts = $account[$i]["fspmailfetchopts"][0];
	$zeile = ("poll $server proto $proto user $extuser pass '" . $passwd . "' is $corusername $opts\n");
	fwrite($fh, "$zeile");
	fclose($fh);
	$i++;
    }
}

// Shellkommando mit sudo ausfuehren
function sudocmd($cmd) {
    global $COR_PATH;
    exec ("sudo $COR_PATH/bin/$cmd");
}

// Konto auf Abwesend setzen
// Switch-Funktion macht keinen Sinn, da Anwesenheit ueber 
// die Funktion bfmrc eingestellt wird.
function absent($corusername) {
    global $COR_FETCHMAILRC_BUILD;
    $fetchmailrc_b = file("$COR_FETCHMAILRC_BUILD");
    $un = strlen($corusername);
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
}



?>