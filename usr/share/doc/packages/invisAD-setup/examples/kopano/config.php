<?php
/*
	config.php

	The config file for the webapp.
	All possible web client settings can be set in this file. Some settings
	(language) can also be set per user or logon.

*/
	// Comment next line to disable the config check (or set FALSE to log the config errors)
	define("CONFIG_CHECK", TRUE);

	// Use these options to optionally disable some PHP configuration checks.
	// WARNING: these checks will disable checks regarding the security of the WebApp site configuration,
	// only change them if you know the consequences - improper use will lead to an insecure installation!
	define("CONFIG_CHECK_COOKIES_HTTP", FALSE);
	define("CONFIG_CHECK_COOKIES_SSL", FALSE);

	// Default Zarafa server to connect to.
	#define("DEFAULT_SERVER","file://\\\\.\\pipe\\zarafa");
	#define("DEFAULT_SERVER","http://localhost:236/zarafa");
	define("DEFAULT_SERVER","file:///var/run/zarafad/server.sock");

	// When using a single-signon system on your webserver, but Zarafa is on another server
	// you can use https to access the zarafa server, and authenticate using an SSL certificate.
	define("SSLCERT_FILE", NULL);
	define("SSLCERT_PASS", NULL);

	// set to 'true' to strip domain from login name found from Single Signon webservers
	define("LOGINNAME_STRIP_DOMAIN", false);

	// Name of the cookie that is used for the session
	define("COOKIE_NAME", "ZARAFA_WEBAPP");

	// The timeout (in seconds) for the session. User will be logged out of WebApp
	// when he has not actively used the WebApp for this time.
	// Set to 0 (or remove) for no timeout during browser session.
	define('CLIENT_TIMEOUT', 0);

	// Defines the base url and end with a slash.
	$base_url = dirname($_SERVER["PHP_SELF"]);
	if(substr($base_url,-1)!="/") $base_url .="/";
	define("BASE_URL", $base_url);

	// Defines the base path on the server, terminated by a slash
	define('BASE_PATH', dirname($_SERVER['SCRIPT_FILENAME']) . "/");

	// Defines the temp path (absolute). Here uploaded attachments will be saved.
	// The web client doesn't work without this directory.
	define("TMP_PATH", "/var/lib/zarafa-webapp/tmp");

	// Define the server paths
	set_include_path(BASE_PATH. PATH_SEPARATOR .
	                 BASE_PATH."server/PEAR/" .  PATH_SEPARATOR .
	                 "/usr/share/php/");

	// Define the path to the plugin directory (No slash at the end)
	define("PATH_PLUGIN_DIR", "plugins");

	// Enable the plugins
	define("ENABLE_PLUGINS", true);

	// Define list of disabled plugins separated by semicolon
	define("DISABLED_PLUGINS_LIST", '');

	// Set addressbook for GAB not to show any users unless searching for a specific user
	define("DISABLE_FULL_GAB", false);

	// Set true to hide public contact folders in address-book folder list,
	// false will show public contact folders in address-book folder list.
	define("DISABLE_PUBLIC_CONTACT_FOLDERS", true);

	// Set the threshold for the addressnook to only show a full contactlist when the number of rows
	// do not exeed this threshold. Otherwise the user can only use the search. Enter any number above
	// zero to set the threshold or -1 to always show the list or 0 to always hide the full list.
	define("DISABLE_FULL_CONTACTLIST_THRESHOLD", -1);

	// Set true to show public folders in hierarchy, false will disable public folders in hierarchy.
	define('ENABLE_PUBLIC_FOLDERS', true);

	// Booking method (true = direct booking, false = send meeting request)
	define('ENABLE_DIRECT_BOOKING', true);

	// Enable GZIP compression for responses
	define('ENABLE_RESPONSE_COMPRESSION', true);

	// When set to true this disables the welcome screen to be shown for first time users.
	define('DISABLE_WELCOME_SCREEN', false);

	// When set to false it will disable showing of advanced settings.
	define('ENABLE_ADVANCED_SETTINGS', false);

	// Freebusy start offset that will be used to load freebusy data in appointments, number is subtracted from current time
	define('FREEBUSY_LOAD_START_OFFSET', 7);

	// Freebusy end offset that will be used to load freebusy data in appointments, number is added to current time
	define('FREEBUSY_LOAD_END_OFFSET', 90);

	// Maximum eml files to be included in a single ZIP archive
	define('MAX_EML_FILES_IN_ZIP', 50);

	// Standard password key for session password. We recommend to change the default value for security reasons 
	// and a length of 16 characters. Passwords are only encrypted when the openssl module is installed
	// IV vector should be 8 bits long
	define('PASSWORD_KEY','a75356b0d1b81b7');
	define('PASSWORD_IV','b3f5a483');
	
	// Additional color schemes for the calendars can be added by uncommenting and editing the following define.
	// The format is the same as the format of COLOR_SCHEMES which is defined in default.php
	// To change the default colors, COLOR_SCHEMES can also be defined here.
	// Note: Every color should have a unique name, because it is used to identify the color
	// define('ADDITIONAL_COLOR_SCHEMES', json_encode(array(
	// 		array(
	//			'name' => 'pink',
	//			'displayName' => _('Pink'),
	//			'base' => '#ff0099'
	//		)
	// )));

	// Additional Prefix for the Contact name can be added by uncommenting and editing the following define.
	// define('CONTACT_PREFIX', json_encode(array(
	//  	array(_('Er.')),
	//  	array(_('Gr.'))
	// )));

	// Additional Suffix for the Contact name can be added by uncommenting and editing the following define.
	// define('CONTACT_SUFFIX', json_encode(array(
	//  	array(_('A')),
	//  	array(_('B'))
	// )));


	/**************************************\
	* Memory usage and timeouts            *
	\**************************************/

	// This sets the maximum time in seconds that is allowed to run before it is terminated by the parser.
	ini_set('max_execution_time', 300); // 5 minutes

	// BLOCK_SIZE (in bytes) is used for attachments by mapi_stream_read/mapi_stream_write
	define('BLOCK_SIZE', 1048576);

	// Time that static files may exist in the client's cache (13 weeks)
	define('EXPIRES_TIME', 60*60*24*7*13);

	// Time that the state files are allowed to survive (in seconds)
	// For filesystems on which relatime is used, this value should be larger then the relatime_interval
	// for kernels 2.6.30 and above relatime is enabled by default, and the relatime_interval is set to
	// 24 hours.
	define('STATE_FILE_MAX_LIFETIME', 28*60*60);

	// Time that attachments are allowed to survive (in seconds)
	define('UPLOADED_ATTACHMENT_MAX_LIFETIME', 6*60*60);

	/**************************************\
	* Languages                            *
	\**************************************/

	// Location to the translations
	define("LANGUAGE_DIR", "server/language/");

	// Defines the default interface language. This can be overriden by the user.
	// This language is also used on the login page
	if (isset($_ENV['LANG']) && $_ENV['LANG']!="C"){
		define('LANG', $_ENV["LANG"]); // This means the server environment language determines the web client language.
	}else{
		define('LANG', 'de_DE.UTF-8'); // default fallback language
	}

	// List of languages that should be enabled in the logon
	// screen's language drop down.  Languages should be specified
	// using <languagecode>_<regioncode>[.UTF-8], and separated with
	// semicolon.  A list of available languages can be found in
	// the manual or by looking at the list of directories in
	// /usr/share/zarafa-webapp/server/language .
	define("ENABLED_LANGUAGES", "de_DE;en_EN;en_US;fr_FR;he_IL;it_IT;nl_NL;ru_RU;zh_CN;nb_NO");

	// Defines the default time zone, change e.g. to "Europe/London" when needed
	if(function_exists("date_default_timezone_set")) {
		if(!ini_get('date.timezone')) {
			date_default_timezone_set('Europe/Berlin');
		}
	}

	/**************************************\
	* Powerpaste                           *
	\**************************************/

	// Options for TinyMCE's powerpaste plugin, see http://docs.ephox.com/display/tinyMCEPlugins/Configuration+Options
	// for more details.
	define('POWERPASTE_WORD_IMPORT', 'merge');
	define('POWERPASTE_HTML_IMPORT', 'merge');
	define('POWERPASTE_ALLOW_LOCAL_IMAGES', true);

	/**************************************\
	* Debugging                            *
	\**************************************/

	ini_set("display_errors", false);
	error_reporting(0);

	if (file_exists("debug.php")){
		include("debug.php");
	}else{
		// define empty dump function in case we still use it somewhere
		function dump(){}
	}
?>
