<?php
/*
 * default-config.php v1.3
 * general configuration file, partially autogenerated via install-script
 * (C) 2009 Daniel T. Bender, invis-server.org
 * (C) 2009 - 2017 Stefan Schaefer, invis-server.org
 * (C) 2013 Ingo Göppert, invis-server.org
 * License GPLv3
 * Help? http://wiki.invis-server.org
 */

//--------------------
// PORTAL CONFIGURATION
//--------------------

// GENERAL
$COMAPNY = 'MyCompany';
$FQDN = 'invis.invis-net.loc';
$DOMAIN_PARTS = explode('.', $FQDN);
$DOMAIN = $DOMAIN_PARTS[1].".".$DOMAIN_PARTS[2];
$NISDOMAIN = $DOMAIN_PARTS[1];
$HOSTNAME = $DOMAIN_PARTS[0];
$GROUPWARE = 'usedgroupware';
$INVISVERSION = 'invisversion';
$OPENSUSEVERSION = 'opensuseversion';

// DHCP
$IP_NETBASE_ADDRESS = 'ipnetbase';
$DHCP_IP_MASK = 'ipnetmask';
$DHCP_IP_BASE = '192.168.220';
$DHCP_IP_REV = '220.168.192';
$DHCP_RANGE_SERVER = array(11, 19);
$DHCP_RANGE_PRINTER = array(20, 50);
$DHCP_RANGE_IPDEV = array(60, 90);
$DHCP_RANGE_CLIENT = array(120, 199);

// Benutzervorgaben
$USER_ADD_MAIL_SUB = 'Neuer Benutzer';
$USER_ADD_MAIL_TXT = 'Hallo!';
$USER_UMASK = 'umask=002';
$USER_PW_MIN_LENGTH = '7'; // 0 = Check disabled
$USER_PW_COMPLEX = 'on';
$SFU_GUID_BASE = '20000';

// LDAP
$LDAP_TLS = "yes"; // ab 11.0 per default ON wegen neuer Policy bei Samba
$LDAP_SERVER = 'ldap://localhost';
$LDAP_SUFFIX = "dc=".$DOMAIN_PARTS[1] .",dc=". $DOMAIN_PARTS[2];
$LDAP_INVIS_SUFFIX = "CN=invis-Server,$LDAP_SUFFIX";
$LDAP_SUFFIX_PORTAL = "CN=invis-Portal,CN=Informationen,$LDAP_INVIS_SUFFIX";
$LDAP_SUFFIX_MAILPROVIDERS = "CN=Mailproviders,CN=Informationen,$LDAP_INVIS_SUFFIX";
$LDAP_SUFFIX_AUI = "CN=AdditionalUserInformation,$LDAP_INVIS_SUFFIX"; 

$LDAP_ADMIN = "ldap.admin";
$LDAP_BIND_DN = "$LDAP_ADMIN@$DOMAIN";
$LDAP_BIND_PW = 'admin-secret';

// RDN Attribut fuer Benutzer festlegen
// erlaubt sind "samAccountName", "userPrincipalName" und "displayName"
// Es ist unbedingt auf die korrekte Schreibweise zu achten!
$AD_CN_ATTRIBUTE = "userPrincipalName";

$BASE_DN_USER = "cn=Users,$LDAP_SUFFIX";
$BASE_DN_GROUP = "cn=Groups,$LDAP_SUFFIX";
$BASE_DN_DHCP = "cn=DHCP Config,cn=DHCP-Server,$LDAP_INVIS_SUFFIX";

// SAMBA
$SMB_DOMAIN = strtoupper($DOMAIN_PARTS[1]);
$SMB_HOSTNAME = strtoupper($DOMAIN_PARTS[0]);
$SMB_GROUPSTOEXTEND = array("Domain Users", "Domain Admins", "Domain Guests", "Archiv", "Verwaltung");
$SMB_FILESERVER = strtoupper("null");
$SMB_DEFAULT_LOGON_SCRIPT = ("user.cmd");

// Services
// Dienste muessen als Array in das globale Array eingetragen werden.
// Jedes Service-Array besteht aus 2 Feldern, von denen das erste der korrekte Daemon-Name
// sein muss und das zweite eine kurze Bemerkung zur Funktion des Dienstes.
// BITTE neue Dienste alphabetisch einsortieren!!!

$SERVER_SERVICES = array(
	array('amavis', 'Spamfilter'),
	array('clamd', 'Virenscanner'),
	array('cups', 'Druckserver'),
	array('dhcpd','IP Adressvergabe'),
	array('dovecot','IMAP/POP3-Mailserver'),
	array('fetchmail','Emails abholen'),
	array('freshclam', 'Virenscanner Updater'),
	array('mysql', 'MariaDB Datenbank'),
	array('named','DNS Namensauflösung'),
	array('ntop', 'Netzwerkanalyse'),
	array('ntpd', 'Zeitserver'),
	array('postfix','Email-Versand'),
	array('postgresql', 'PostgreSQL Datenbank'),
	array('samba', 'Active Directory'),
	array('kopano-dagent', 'Kopano Empfang'),
	array('kopano-gateway', 'Kopano Postfach'),
	array('kopano-ical', 'Kopano Kalender'),
	array('kopano-monitor', 'Kopano Monitor'),
	array('kopano-search', 'Kopano Suche'),
	array('kopano-server', 'Kopano Server'),
	array('kopano-spooler', 'Kopano Versand'),
	array('kopano-presence', 'Kopano Anwesenheitsprüfung'),
	array('sogod', 'SOGo-Groupwareserver')
	);

// WEBSITE SPECIFIC
$PORTAL_LOGO_PATH = 'images/invis-logo.png';
$PORTAL_SUPPORT_MAIL = 'hilfe@fsproductions.de';
$PORTAL_FOOTER = 'Bei Fragen und Problemen: <a style="color: #ff0000" href="mailto:hilfe@fsproductions.de">hilfe@fsproductions.de</a> | FSP Computer &amp; Netzwerke | Vogelsbergstr. 118 | 63679 Schotten | Tel.: 06044/989 0000';

$PORTAL_UPLOAD_DIR = '/srv/uploads';
$PORTAL_DOWNLOAD_DIR = '/srv/shares/portal/downloads';
$STATUS_WATCH_DIRS = array('/home', '/srv', '/var');

// Bitte folgende Zeile von den Kommentarzeichen befreien, wenn udevsync zur Datensicherung verwendet wird.
//$STATUS_BACKUP_TIMER = 3;

// Aktivieren der APCUPS Daemon Abfrage
$STATUS_APCUPSD = false;

//CorNAz
$COR_MY_LOGO = '/srv/www/htdocs/portal/images/invis-logo.png';
$COR_BG_COLOR = '#FAFAFA';
$COR_WEBSERVER = '/';
$COR_PATH = '/var/lib/cornaz';
$COR_LOCAL_IMAP_SERVER = "localhost";
$COR_FETCHMAILRC_BUILD = "$COR_PATH/build/.fetchmailrc";
?>
