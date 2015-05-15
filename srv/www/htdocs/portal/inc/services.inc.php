<?php

/*
 * inc/services.inc.php v0.1
 * AJAX script, administration functions
 * (C) 2009 Daniel T. Bender, invis-server.org
 * (C) 2009, 2010, 2011, 2012, 2014, 2015 Stefan Schaefer, invis-server.org
 * (C) 2015 Ingo Göppert, invis-server.org

 * License GPLv3
 * Questions: stefan@invis-server.org
 */

//--------------------
// Serices
//--------------------

// service listing
function serviceList($conn) {
	global $SERVER_SERVICES;
	$json = array();
	
	foreach ( $SERVER_SERVICES as $service ) {
	    $result = shell_exec("sudo /usr/bin/ipservicestate $service[0]");
	    
	    $status = explode( " ", $result);
	    
	    $entry = array(
		    "name" => $service[0],
		    "info" => $service[1],
		    "enabled" => $status[0],
		    "status" => $status[1]);

	    array_push($json, $entry);
	}

	return $json;

}

// service control
function serviceControl($cmd, $name) {

    // Stefan: Hier Dienst steuern
    // name = das was in der Spalte name steht
    // cmd = start, stop, restart, reload
    $result = shell_exec("sudo /usr/bin/ipservicecontrol $cmd $name");

    if ($result == "Success" )
	return 0;
    else 
	return "$result"; // Hier kann man Text angeben, der dem user angezeigt wird.
}

?>