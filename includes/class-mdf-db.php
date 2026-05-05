<?php

if (!defined('ABSPATH')) {
    exit;
}

class MDF_DB
{
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

        foreach ($rules as $rule) {
            if (!isset($rule['source'], $rule['destination'])) {
                continue;
            }

            $source = trim((string) $rule['source']);
            $destination = trim((string) $rule['destination']);
            $is_active = !empty($rule['is_active']) ? 1 : 0;

            if ($source === '' || $destination === '') {
                continue;
            }

            $wpdb->insert(
                $table_name,
                array(
                    'source' => self::normalize_source($source),
                    'destination' => esc_url_raw($destination),
                    'is_active' => $is_active,
                    'created_at' => current_time('mysql'),
                ),
                array('%s', '%s', '%d', '%s')
            );
        }
    }

    public static function normalize_source($source)
    {
        $source = trim($source);

        if ($source === '*') {
            return '*';
        }

        $parsed = wp_parse_url($source);

        if (is_array($parsed) && isset($parsed['path'])) {
            $path = $parsed['path'];
            if (isset($parsed['query']) && $parsed['query'] !== '') {
                $path .= '?' . $parsed['query'];
            }
            return ltrim($path, '/');
        }

        return ltrim($source, '/');
    }
}
