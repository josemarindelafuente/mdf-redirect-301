<?php

if (!defined('ABSPATH')) {
    exit;
}

class MDF_DB
{
    const MAX_RULES = 300;
    const MAX_SOURCE_LENGTH = 500;
    const MAX_DESTINATION_LENGTH = 500;

    public static function table_name()
    {
        global $wpdb;
        return $wpdb->prefix . 'mdf_redirects';
    }

    public static function create_table()
    {
        global $wpdb;

        $table_name = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            source VARCHAR(500) NOT NULL,
            destination VARCHAR(500) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY source_idx (source(191)),
            KEY is_active_idx (is_active)
        ) {$charset_collate};";

        dbDelta($sql);
    }

    public static function get_all()
    {
        global $wpdb;
        $table_name = self::table_name();

        return $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY id DESC", ARRAY_A);
    }

    public static function get_active_rules()
    {
        global $wpdb;
        $table_name = self::table_name();

        $query = $wpdb->prepare(
            "SELECT source, destination FROM {$table_name} WHERE is_active = %d ORDER BY id DESC",
            1
        );

        return $wpdb->get_results($query, ARRAY_A);
    }

    public static function replace_all_rules($rules)
    {
        global $wpdb;
        $table_name = self::table_name();

        // Asegura que la tabla exista incluso si el plugin se cargó ya activo.
        self::create_table();

        // DELETE es más compatible que TRUNCATE en hostings con permisos restringidos.
        $wpdb->query("DELETE FROM {$table_name}");

        if (empty($rules) || !is_array($rules)) {
            return;
        }

        $rules = array_slice($rules, 0, self::MAX_RULES);

        foreach ($rules as $rule) {
            if (!isset($rule['source'], $rule['destination'])) {
                continue;
            }

            $source = self::normalize_source((string) $rule['source']);
            $destination = self::sanitize_destination((string) $rule['destination']);
            $is_active = (isset($rule['is_active']) && (int) $rule['is_active'] === 1) ? 1 : 0;

            if ($source === '' || $destination === '') {
                continue;
            }

            $wpdb->insert(
                $table_name,
                array(
                    'source' => $source,
                    'destination' => $destination,
                    'is_active' => $is_active,
                    'created_at' => current_time('mysql'),
                ),
                array('%s', '%s', '%d', '%s')
            );
        }
    }

    public static function normalize_source($source)
    {
        $source = trim((string) $source);
        if ($source === '') {
            return '';
        }

        if ($source === '*') {
            return '*';
        }

        $parsed = wp_parse_url($source);

        if (is_array($parsed) && (isset($parsed['host']) || isset($parsed['scheme']))) {
            return '';
        }

        if (is_array($parsed) && isset($parsed['path'])) {
            $path = $parsed['path'];
            if (isset($parsed['query']) && $parsed['query'] !== '') {
                $path .= '?' . $parsed['query'];
            }
            return self::sanitize_source_path($path);
        }

        return self::sanitize_source_path($source);
    }

    public static function sanitize_destination($destination)
    {
        $destination = trim((string) $destination);
        $destination = substr($destination, 0, self::MAX_DESTINATION_LENGTH);
        if ($destination === '') {
            return '';
        }

        if (preg_match('#^(javascript|data|vbscript):#iu', $destination)) {
            return '';
        }

        // Ruta absoluta del mismo sitio (/pagina)
        if (strpos($destination, '/') === 0 && strpos($destination, '//') !== 0) {
            $destination = home_url($destination);
        } elseif (strpos($destination, '?') === 0) {
            // Solo cadena de consulta (?ver=1)
            $destination = home_url('/') . $destination;
        } elseif (strpos($destination, '//') === 0) {
            // Protocol-relative (//ejemplo.com/...)
            $destination = (is_ssl() ? 'https:' : 'http:') . $destination;
        } elseif (!preg_match('#^[a-z][a-z0-9+\-.]*:#iu', $destination)) {
            // Sin esquema: dominio con TLD (ejemplo.com/ruta) o slug interno (pagina/hija)
            if (preg_match('#^[a-z0-9][a-z0-9.-]*\.[a-z]{2,}(/|\?|$)#iu', $destination)) {
                $scheme = wp_parse_url(home_url(), PHP_URL_SCHEME);
                if (!is_string($scheme) || !in_array(strtolower($scheme), array('http', 'https'), true)) {
                    $scheme = is_ssl() ? 'https' : 'http';
                } else {
                    $scheme = strtolower($scheme);
                }
                $destination = $scheme . '://' . ltrim($destination, '/');
            } elseif ($destination !== '') {
                $destination = home_url('/' . ltrim($destination, '/'));
            }
        }

        $destination = esc_url_raw($destination, array('http', 'https'));
        if ($destination === '') {
            return '';
        }

        $parts = wp_parse_url($destination);
        if (!is_array($parts)) {
            return '';
        }

        $host = isset($parts['host']) ? strtolower((string) $parts['host']) : '';
        $host = trim($host, '[]');
        if ($host === '') {
            return '';
        }

        $allowed_hosts = self::get_allowed_destination_hosts();
        if (!in_array($host, $allowed_hosts, true)) {
            return '';
        }

        return $destination;
    }

    public static function get_allowed_destination_hosts()
    {
        $hosts = array();

        foreach (array(home_url(), site_url()) as $base_url) {
            $site_host = wp_parse_url($base_url, PHP_URL_HOST);
            if (is_string($site_host) && $site_host !== '') {
                $hosts[] = strtolower($site_host);
            }
        }

        $custom_hosts = get_option('mdf_allowed_destination_hosts', '');
        if (is_string($custom_hosts) && $custom_hosts !== '') {
            $items = explode(',', $custom_hosts);
            foreach ($items as $item) {
                $item = strtolower(trim($item));
                if ($item !== '') {
                    $hosts[] = $item;
                }
            }
        }

        $hosts = self::expand_host_variants($hosts);

        return array_values(array_unique(array_filter($hosts)));
    }

    /**
     * Convierte el texto del panel (dominios o URLs, uno por línea o separados por coma) en opción guardada.
     *
     * @param string $raw Contenido del textarea.
     * @return string Hosts separados por comas.
     */
    public static function sanitize_allowed_hosts_input($raw)
    {
        $raw = (string) $raw;
        $segments = preg_split('/[\r\n,]+/', $raw);
        $hosts = array();

        foreach ($segments as $segment) {
            $segment = trim($segment);
            if ($segment === '') {
                continue;
            }

            if (preg_match('#^[a-z][a-z0-9+\-.]*:#iu', $segment)) {
                $parsed = wp_parse_url($segment);
                if (is_array($parsed) && !empty($parsed['host'])) {
                    $hosts[] = strtolower(trim($parsed['host'], '[]'));
                }
                continue;
            }

            $segment = preg_replace('#/.*$#', '', $segment);
            $segment = strtolower(trim($segment));

            if ($segment !== '' && preg_match('#^\d{1,3}(\.\d{1,3}){3}$#', $segment)) {
                $hosts[] = $segment;
                continue;
            }

            if ($segment !== '' && preg_match('#^[a-z0-9]([a-z0-9.-]*[a-z0-9])?$#iu', $segment)) {
                $hosts[] = $segment;
            }
        }

        return implode(',', array_unique(array_filter($hosts)));
    }

    /**
     * Añade variante con/sin prefijo www para reducir rechazos al guardar redirecciones al mismo sitio.
     *
     * @param array $hosts
     * @return array
     */
    private static function expand_host_variants($hosts)
    {
        $out = array();

        foreach ($hosts as $h) {
            $h = strtolower(trim((string) $h));
            if ($h === '') {
                continue;
            }

            $out[] = $h;

            if (strpos($h, 'www.') === 0) {
                $out[] = substr($h, 4);
            } else {
                $out[] = 'www.' . $h;
            }
        }

        $extras = array();
        foreach ($out as $h) {
            if ($h === 'localhost') {
                $extras[] = '127.0.0.1';
                $extras[] = '::1';
            }
            if ($h === '127.0.0.1') {
                $extras[] = 'localhost';
                $extras[] = '::1';
            }
            if ($h === '::1') {
                $extras[] = 'localhost';
                $extras[] = '127.0.0.1';
            }
        }

        return array_values(array_unique(array_filter(array_merge($out, $extras))));
    }

    public static function sanitize_excluded_path($path)
    {
        $path = trim((string) $path);
        $path = trim($path, "/ \t\n\r\0\x0B");
        $path = sanitize_text_field($path);
        return preg_replace('/[^\p{L}\p{N}\-_\/\.]/u', '', $path);
    }

    private static function sanitize_source_path($source)
    {
        $source = trim((string) $source);
        $source = substr($source, 0, self::MAX_SOURCE_LENGTH);
        $source = ltrim($source, '/');
        if ($source === '') {
            return '';
        }

        $parts = explode('?', $source, 2);
        $path = isset($parts[0]) ? $parts[0] : '';
        $query = isset($parts[1]) ? $parts[1] : '';

        $path = preg_replace('/[^\p{L}\p{N}\-_\/\.]/u', '', $path);
        if ($path === '') {
            return '';
        }

        if ($query !== '') {
            $query = preg_replace('/[^\p{L}\p{N}\-\._~&=%]/u', '', $query);
            return $path . '?' . $query;
        }

        return $path;
    }
}
