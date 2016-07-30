<?php

# Klassendefinitionen für CorNAz

# Klasse Mailprovider

class mailprovider {

    // Attribute
    public $mpvendor;
    public $mpdescription;
    public $mpusername;
    public $mppopserver;
    public $mpimapserver;
    public $mpsmtpserver;
    public $mppopssl;
    public $mpimapssl;
    public $mpsmtpport;
    public $mpsmtptls;
	
	public function __construct($mpvendor = "", $mpdescription = "", $mpusername = "", $mppopserver = "", $mpimapserver = "", $mpsmtpserver = "", $mppopssl = "", $mpimapssl = "", $mpsmtpport = "", $mpsmtptls = "") {
	$this->mpvendor = $mpvendor;
        $this->mpdescription = $mpdescription;
	$this->mpusername = $mpusername;
	$this->mppopserver = $mppopserver;
	$this->mpimapserver = $mpimapserver;
	$this->mpsmtpserver = $mpsmtpserver;
	$this->mppopssl = $mppopssl;
	$this->mpimapssl = $mpimapssl;
	$this->mpsmtport = $mpsmtpport;
	$this->mpsmtptls = $mpsmtptls;
	}

	// Einen vorhandenen Account auslesen	
	function readmailprovider($mpvendor,$ldapbinddn,$password,$LDAP_SUFFIX_MAILPROVIDERS,$LDAP_SERVER) {
	global $bind, $ditcon;
	// Am LDAP per SimpleBind anmelden
	if ($bind) {
		$filter="(CN=$mpvendor)";
		$justthese = array("CN", "fspMailProviderVendor", "fspMailProviderDescription", "fspMailProviderUserName", "fspMailProviderPOP", "fspMailProviderIMAP", "fspMailProviderPOPSSL", "fspMailProviderIMAPSSL" );
		$sr=ldap_search($ditcon, $LDAP_SUFFIX_MAILPROVIDERS, $filter, $justthese);
		$entries = ldap_get_entries($ditcon, $sr);
		if ( $mpvendor == "*" ) {
			return $entries;
		} else {
			if (isset($entries[0])) {
			// Zuordnung der Ergebniswerte zu den Objekteigenschaften
			$this->mpvendor = $mpvendor;
			$this->mpdescription = $entries[0]["fspmailproviderdescription"][0];
			$this->mpusername = $entries[0]["fspmailproviderusername"][0];
			$this->mppopserver = $entries[0]["fspmailproviderpop"][0];
			$this->mpimapserver = $entries[0]["fspmailproviderimap"][0];
			$this->mppopssl = $entries[0]["fspmailproviderpopssl"][0];
			$this->mpimapssl = $entries[0]["fspmailproviderimapssl"][0];
		}}
	} else {
		echo "Verbindung zum LDAP Server nicht möglich!";
	}}
}
?>