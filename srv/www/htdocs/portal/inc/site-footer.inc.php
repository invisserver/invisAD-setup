<?php
/* 
 * inc/site-footer.php v1.0
 * portal footer w/ copyright & stuff
 * (C) 2009 Daniel T. Bender, invis-server.org
 * License GPLv3
 * Questions: daniel@invis-server.org
 */
require_once('default/default-config.php');
require_once('config.php');

if(! isset($PORTAL_FOOTER ))
  $PORTAL_FOOTER = '';

?>

<div id='footer_bg'>
	<div class='site'>
		<?php echo '<div id=\'footer\'>' . $PORTAL_FOOTER . '&nbsp;|&nbsp;<a style="color: #ff0000 "href="inc/ca.crt">Server-Stammzertifikat</a></div>'; ?>
	</div>
</div>
