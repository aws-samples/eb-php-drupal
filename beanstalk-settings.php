<?php

// @codingStandardsIgnoreFile

$databases = array();

$config_directories = array();

$settings['hash_salt'] = $_SERVER['HASH_SALT'];

$settings['update_free_access'] = FALSE;

if ($settings['hash_salt']) {
  $prefix = 'drupal.' . hash('sha256', 'drupal.' . $settings['hash_salt']);
  $apc_loader = new \Symfony\Component\ClassLoader\ApcClassLoader($prefix, $class_loader);
  unset($prefix);
  $class_loader->unregister();
   $apc_loader->register();
  $class_loader = $apc_loader;
}

$settings['container_yamls'][] = $app_root . '/' . $site_path . '/services.yml';

$settings['file_scan_ignore_directories'] = [
  'node_modules',
  'bower_components',
];

$settings['entity_update_batch_size'] = 50;

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
