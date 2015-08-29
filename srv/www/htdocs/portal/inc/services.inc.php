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
	    
	    // farblich Darstellung des Dienststatus festlegen
	    // Runlevelintegration
	    if ( $status[0] == 'enabled' ) {
		$rlstatus = '<b style="color: green;">' . $status[0] . '</b>';
	    } else {
		$rlstatus = '<b style="orange;">' . $status[0] . '</b>';
	    }
	
	    // Dienststatus
	    $srvstatus = '<b style="color: orange;">' . $status[1] . '</b>';
	    if ( trim($status[1]) == 'active' ) {
		$srvstatus = '<b style="color: green;">' . $status[1] . '</b>';
	    } elseif ( trim($status[1]) == 'inactive' ) {
		$srvstatus = '<b style="color: red;">' . $status[1] . '</b>';
	    }
	    
	    $entry = array(
		    "service" => $service[0],
		    "name" => '<b>' . $service[0] . '</b>',
		    "info" => '<span>' . $service[1] . '</span>',
		    "enabled" => $rlstatus,
		    "status" => $srvstatus);

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