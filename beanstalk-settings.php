<?php

 $databases = array();

$config_directories = array();

$settings['hash_salt'] = $_SERVER['HASH_SALT'];

$settings['update_free_access'] = FALSE;

$settings['container_yamls'][] = __DIR__ . '/services.yml';

$settings['file_scan_ignore_directories'] = [
  'node_modules',
  'bower_components',
];

$databases['default']['default'] = array (
  'database' => $_SERVER['RDS_DB_NAME'],
  'username' => $_SERVER['RDS_USERNAME'],
  'password' => $_SERVER['RDS_PASSWORD'],
  'prefix' => '',
  'host' => $_SERVER['RDS_HOSTNAME'],
  'port' => $_SERVER['RDS_PORT'],
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql',
);
$settings['install_profile'] = 'standard';
$config_directories['sync'] = $_SERVER['SYNC_DIR'];
