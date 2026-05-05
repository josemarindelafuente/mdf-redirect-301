<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

$table_name = $wpdb->prefix . 'mdf_redirects';
$wpdb->query("DROP TABLE IF EXISTS {$table_name}");

delete_option('mdf_redirects_enabled');
delete_option('mdf_excluded_paths');
delete_option('mdf_allowed_destination_hosts');
