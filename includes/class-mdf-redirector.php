<?php

if (!defined('ABSPATH')) {
    exit;
}

class MDF_Redirector
{
    public static function init()
    {
        add_action('template_redirect', array(__CLASS__, 'maybe_redirect'), 1);
    }

    public static function maybe_redirect()
    {
        if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
            return;
        }

        if (isset($_SERVER['REQUEST_METHOD']) && in_array(strtoupper((string) $_SERVER['REQUEST_METHOD']), array('HEAD', 'OPTIONS'), true)) {
            return;
        }

        $enabled = (int) get_option('mdf_redirects_enabled', 1);
        if ($enabled !== 1) {
            return;
        }

        $current_uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
        $current_path = (string) wp_parse_url($current_uri, PHP_URL_PATH);
        $current_path = ltrim($current_path, '/');

        if (self::is_excluded_path($current_path)) {
            return;
        }

        $rules = MDF_DB::get_active_rules();
        if (empty($rules)) {
            return;
        }

        $wildcard_destination = '';
        foreach ($rules as $rule) {
            if (isset($rule['source']) && trim($rule['source']) === '*') {
                $wildcard_destination = trim((string) $rule['destination']);
                break;
            }
        }

        if ($wildcard_destination !== '') {
            self::safe_redirect($wildcard_destination);
        }

        $normalized_current = MDF_DB::normalize_source($current_uri);

        foreach ($rules as $rule) {
            $source = isset($rule['source']) ? trim((string) $rule['source']) : '';
            $destination = isset($rule['destination']) ? trim((string) $rule['destination']) : '';

            if ($source === '' || $source === '*' || $destination === '') {
                continue;
            }

            if (MDF_DB::normalize_source($source) === $normalized_current) {
                self::safe_redirect($destination);
            }
        }
    }

    private static function is_excluded_path($current_uri)
    {
        $excluded_paths = get_option('mdf_excluded_paths', array('wp-admin', 'wp-login.php'));
        if (!is_array($excluded_paths)) {
            $excluded_paths = array('wp-admin', 'wp-login.php');
        }

        $current_uri = trim((string) $current_uri, '/');

        foreach ($excluded_paths as $path) {
            $path = MDF_DB::sanitize_excluded_path($path);
            if ($path === '') {
                continue;
            }

            if (self::path_matches_exclusion($current_uri, $path)) {
                return true;
            }
        }

        return false;
    }

    private static function safe_redirect($destination)
    {
        $destination = MDF_DB::sanitize_destination($destination);
        if ($destination === '') {
            return;
        }

        if (self::is_same_url($destination)) {
            return;
        }

        // Evita que navegadores almacenen permanentemente redirecciones durante pruebas.
        nocache_headers();
        wp_safe_redirect($destination, 301);
        exit;
    }

    private static function is_same_url($destination)
    {
        $current_scheme = is_ssl() ? 'https://' : 'http://';
        $host = isset($_SERVER['HTTP_HOST']) ? (string) wp_unslash($_SERVER['HTTP_HOST']) : '';
        $uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
        $current_url = trailingslashit($current_scheme . $host) . ltrim($uri, '/');

        return untrailingslashit($current_url) === untrailingslashit($destination);
    }

    private static function path_matches_exclusion($current_path, $excluded_path)
    {
        $current_path = strtolower(trim((string) $current_path, '/'));
        $excluded_path = strtolower(trim((string) $excluded_path, '/'));

        if ($current_path === '' || $excluded_path === '') {
            return false;
        }

        if ($current_path === $excluded_path) {
            return true;
        }

        return strpos($current_path, $excluded_path . '/') === 0;
    }
}
