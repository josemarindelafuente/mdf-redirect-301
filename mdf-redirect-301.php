<?php
/**
 * Plugin Name: MDF 301 Redirects
 * Description: Plugin simple para gestionar redirecciones 301 con soporte de wildcard.
 * Version: 1.0.0
 * Author: MDF
 * License: GPL2+
 * Text Domain: mdf-redirect-301
 */

if (!defined('ABSPATH')) {
    exit;
}

define('MDF_REDIRECT_301_VERSION', '1.0.0');
define('MDF_REDIRECT_301_FILE', __FILE__);
define('MDF_REDIRECT_301_PATH', plugin_dir_path(__FILE__));
define('MDF_REDIRECT_301_URL', plugin_dir_url(__FILE__));

require_once MDF_REDIRECT_301_PATH . 'includes/class-mdf-db.php';
require_once MDF_REDIRECT_301_PATH . 'includes/class-mdf-redirector.php';
require_once MDF_REDIRECT_301_PATH . 'includes/class-mdf-admin.php';

function mdf_redirect_301_activate()
{
    MDF_DB::create_table();

    if (get_option('mdf_redirects_enabled', null) === null) {
        add_option('mdf_redirects_enabled', 1);
    }

    if (get_option('mdf_excluded_paths', null) === null) {
        add_option('mdf_excluded_paths', array('wp-admin', 'wp-login.php'));
    }
}

function mdf_redirect_301_deactivate()
{
    // Mantener datos al desactivar para no perder configuraciones.
}

register_activation_hook(__FILE__, 'mdf_redirect_301_activate');
register_deactivation_hook(__FILE__, 'mdf_redirect_301_deactivate');

function mdf_redirect_301_bootstrap()
{
    MDF_Redirector::init();

    if (is_admin()) {
        MDF_Admin::init();
    }
}

add_action('plugins_loaded', 'mdf_redirect_301_bootstrap');
