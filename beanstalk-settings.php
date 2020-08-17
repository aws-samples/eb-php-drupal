<?php

// @codingStandardsIgnoreFile

$databases = [];

$settings['hash_salt'] = $_SERVER['HASH_SALT'];

$settings['update_free_access'] = FALSE;

$settings['container_yamls'][] = $app_root . '/' . $site_path . '/services.yml';

$settings['file_scan_ignore_directories'] = [
  'node_modules',
  'bower_components',
];

$settings['entity_update_batch_size'] = 50;

$settings['entity_update_backup'] = TRUE;

$settings['migrate_node_migrate_type_classic'] = FALSE;

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

$settings['config_sync_directory'] = $_SERVER['SYNC_DIR'];
