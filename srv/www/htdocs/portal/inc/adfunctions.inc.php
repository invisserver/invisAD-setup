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

?>