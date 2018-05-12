<?php
return array(
    'host' => 'ldaps://invis.invis-net.loc',
    'enhancedIdentityPrivacy' => 'false',
    'bindDN' => 'CN=Admin LDAP,CN=Users,DC=invis-net,DC=loc',
    'bindPW' => 'ldapadminpw',
    'searchBase' => 'CN=Users,DC=invis-net,DC=loc',
    'groupFilter' => 'member=%2$s',
    'userFilter' => 'sAMAccountName=%1$s',
    'usernameAttribute' => 'sAMAccountName',
    'commonNameAttribute' => 'displayName',
    'groupidAttribute' => 'cn',
    'mailAttribute' => 'mail',
    'allowedGroupIds' => array('kimai')
);
?>