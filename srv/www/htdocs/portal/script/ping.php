<?php
/* script/ping.php v1.1
 * AJAX script, pinging a host an returning status as images (HTML-tag)
 * (C) 2009 Daniel T. Bender, invis-server.org
 * (C) 2013 Ingo Göppert, invis-server.org
 * (C) 2021 W.-Marcel Richter, invis-server.org
 * License GPLv3
 * Questions: daniel@invis-server.org
 */
if (!isset($_COOKIE['invis'])) die();

$IP='';
if (isset($_POST['ip'])) $IP = $_POST['ip'];
if (!empty($web)){
    /* 0: online, 1: unreachable, 2: error */
    exec("ping -a -c3 -n -q $IP;", $null, $return);
} else { $return = 2; }
echo ($return === 0)?"<img title='IP: $IP' src='images/ok.png' />":"<img title='IP: $IP' src='images/not_ok.png' />";
