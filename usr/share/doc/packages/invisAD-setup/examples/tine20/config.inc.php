<?php
return array (
  'database' =>
  array (
    'host' => 'localhost',
    'dbname' => 'tine20',
    'username' => 'tine20',
    'password' => 'tinedbpw',
    'adapter' => 'pdo_mysql',
    'tableprefix' => 'tine20_',
    'port' => '3306',
  ),
  'setupuser' =>
  array (
    'username' => 'tine20setup',
    'password' => 'setup',
  ),
  'logger' =>
  array (
    'active' => false,
    'filename' => '/var/log/apache2/tine20.log',
    'priority' => 6,
  ),
  'caching' =>
  array (
    'active' => true,
    'path' => '/var/lib/tine20/tmp',
    'lifetime' => 120,
  ),
  'session' =>
  array (
    'path' => '/var/lib/tine20/session',
    'lifetime' => 86400,
    'backend' => 'File',
    'host' => 'localhost',
    'port' => 6379,
  ),
  'tmpdir' => '/var/lib/tine20/tmp',
  'filesdir' => '/var/lib/tine20/files',
  'mapPanel' => 1,
);
