<?php

if (!defined('ABSPATH')) {
    exit;
}

class MDF_Admin
{
    const PAGE_SLUG = 'mdf-redirect-301';

    public static function init()
    {
        add_action('admin_menu', array(__CLASS__, 'register_menu'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
    }

    public static function register_menu()
    {
        add_menu_page(
            __('MDF Redirects', 'mdf-redirect-301'),
            __('MDF Redirects', 'mdf-redirect-301'),
            'manage_options',
            self::PAGE_SLUG,
            array(__CLASS__, 'render_page'),
            'dashicons-randomize',
            81
        );
    }

    public static function enqueue_assets($hook_suffix)
    {
        if ($hook_suffix !== 'toplevel_page_' . self::PAGE_SLUG) {
            return;
        }

        wp_enqueue_style(
            'mdf-redirect-301-admin',
            MDF_REDIRECT_301_URL . 'assets/admin.css',
            array(),
            MDF_REDIRECT_301_VERSION
        );

        wp_enqueue_script(
            'mdf-redirect-301-admin',
            MDF_REDIRECT_301_URL . 'assets/admin.js',
            array(),
            MDF_REDIRECT_301_VERSION,
            true
        );
    }

    public static function render_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        self::handle_post();

        $enabled = (int) get_option('mdf_redirects_enabled', 1);
        $excluded_paths = get_option('mdf_excluded_paths', array('wp-admin', 'wp-login.php'));
        $rules = MDF_DB::get_all();

        if (!is_array($excluded_paths)) {
            $excluded_paths = array('wp-admin', 'wp-login.php');
        }

        $excluded_text = implode("\n", array_filter(array_map('trim', $excluded_paths)));

        $allowed_hosts_raw = get_option('mdf_allowed_destination_hosts', '');
        if (!is_string($allowed_hosts_raw)) {
            $allowed_hosts_raw = '';
        }
        $allowed_hosts_display = implode("\n", array_filter(array_map('trim', explode(',', $allowed_hosts_raw))));
        $current_tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'configuracion';
        if (!in_array($current_tab, array('configuracion', 'reglas', 'ayuda', 'por-que-301'), true)) {
            $current_tab = 'configuracion';
        }

        $tab_base_url = admin_url('admin.php?page=' . self::PAGE_SLUG);
        ?>
        <div class="wrap mdf-redirect-wrap">
            <h1><?php esc_html_e('MDF 301 Redirects', 'mdf-redirect-301'); ?></h1>
            <p><?php esc_html_e('Configura redirecciones 301 de forma simple.', 'mdf-redirect-301'); ?></p>
            <?php settings_errors('mdf_redirect_notices'); ?>

            <h2 class="nav-tab-wrapper">
                <a href="<?php echo esc_url(add_query_arg('tab', 'configuracion', $tab_base_url)); ?>" class="nav-tab <?php echo ($current_tab === 'configuracion') ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Configuración', 'mdf-redirect-301'); ?>
                </a>
                <a href="<?php echo esc_url(add_query_arg('tab', 'reglas', $tab_base_url)); ?>" class="nav-tab <?php echo ($current_tab === 'reglas') ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Reglas de redirección', 'mdf-redirect-301'); ?>
                </a>
                <a href="<?php echo esc_url(add_query_arg('tab', 'ayuda', $tab_base_url)); ?>" class="nav-tab <?php echo ($current_tab === 'ayuda') ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Cómo funciona', 'mdf-redirect-301'); ?>
                </a>
                <a href="<?php echo esc_url(add_query_arg('tab', 'por-que-301', $tab_base_url)); ?>" class="nav-tab <?php echo ($current_tab === 'por-que-301') ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Por qué usar 301', 'mdf-redirect-301'); ?>
                </a>
            </h2>

            <?php if ($current_tab === 'configuracion') : ?>
                <form method="post">
                    <?php wp_nonce_field('mdf_redirect_save_settings', 'mdf_redirect_nonce'); ?>
                    <input type="hidden" name="mdf_form_action" value="save_config" />

                    <div class="mdf-card mdf-card-important mdf-card-domains">
                        <h2><?php esc_html_e('Estado global', 'mdf-redirect-301'); ?></h2>
                        <p class="mdf-card-kicker"><?php esc_html_e('Configuración crítica', 'mdf-redirect-301'); ?></p>
                        <p class="mdf-status-line">
                            <span class="mdf-status-pill <?php echo $enabled === 1 ? 'is-active' : 'is-inactive'; ?>">
                                <?php echo $enabled === 1 ? esc_html__('Activo', 'mdf-redirect-301') : esc_html__('Inactivo', 'mdf-redirect-301'); ?>
                            </span>
                        </p>
                        <label>
                            <input type="checkbox" name="mdf_redirects_enabled" value="1" <?php checked($enabled, 1); ?> />
                            <?php esc_html_e('Activar redirecciones del plugin', 'mdf-redirect-301'); ?>
                        </label>
                    </div>

                    <div class="mdf-card">
                        <h2><?php esc_html_e('Rutas excluidas', 'mdf-redirect-301'); ?></h2>
                        <p><?php esc_html_e('Una por línea. Estas rutas nunca se redirigen.', 'mdf-redirect-301'); ?></p>
                        <textarea name="mdf_excluded_paths" rows="5" class="large-text code"><?php echo esc_textarea($excluded_text); ?></textarea>
                    </div>

                    <div class="mdf-card mdf-card-important">
                        <h2><?php esc_html_e('Dominios externos permitidos', 'mdf-redirect-301'); ?></h2>
                        <p class="mdf-card-kicker"><?php esc_html_e('Configuración crítica', 'mdf-redirect-301'); ?></p>
                        <p><?php esc_html_e('Para redirigir hacia otro sitio (por ejemplo con origen *), indica aquí el dominio de destino. Una entrada por línea o separadas por coma. Puedes pegar la URL completa; solo se guardará el dominio.', 'mdf-redirect-301'); ?></p>
                        <textarea name="mdf_allowed_destination_hosts_input" rows="4" class="large-text code" placeholder="<?php echo esc_attr('marindelafuente.com.ar'); ?>"><?php echo esc_textarea($allowed_hosts_display); ?></textarea>
                    </div>

                    <?php submit_button(__('Guardar configuración', 'mdf-redirect-301')); ?>
                </form>
            <?php elseif ($current_tab === 'reglas') : ?>
                <form method="post">
                    <?php wp_nonce_field('mdf_redirect_save_settings', 'mdf_redirect_nonce'); ?>
                    <input type="hidden" name="mdf_form_action" value="save_rules" />

                    <div class="mdf-card mdf-rules-card">
                        <h2><?php esc_html_e('Reglas de redirección', 'mdf-redirect-301'); ?></h2>
                        <p><?php esc_html_e('Usa * en Origen para redirigir todo el sitio (excepto rutas excluidas). Si el destino es otro dominio, debe estar listado en la pestaña «Configuración».', 'mdf-redirect-301'); ?></p>
                        <table class="widefat fixed striped mdf-rules-table" id="mdf-rules-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Origen', 'mdf-redirect-301'); ?></th>
                                    <th><?php esc_html_e('Destino', 'mdf-redirect-301'); ?></th>
                                    <th><?php esc_html_e('Activa', 'mdf-redirect-301'); ?></th>
                                    <th><?php esc_html_e('Eliminar', 'mdf-redirect-301'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="mdf-rules-body">
                            <?php if (!empty($rules)) : ?>
                                <?php foreach ($rules as $index => $rule) : ?>
                                    <tr>
                                        <td>
                                            <input type="hidden" name="mdf_rules[<?php echo esc_attr($index); ?>][id]" value="<?php echo esc_attr(isset($rule['id']) ? $rule['id'] : ''); ?>" />
                                            <input type="text" name="mdf_rules[<?php echo esc_attr($index); ?>][source]" value="<?php echo esc_attr($rule['source']); ?>" class="regular-text" placeholder="* o ruta/origen" />
                                        </td>
                                        <td>
                                            <input type="text" name="mdf_rules[<?php echo esc_attr($index); ?>][destination]" value="<?php echo esc_attr($rule['destination']); ?>" class="large-text mdf-rule-destination" placeholder="<?php echo esc_attr(__('https://tu-sitio.com/pagina o /pagina', 'mdf-redirect-301')); ?>" autocomplete="off" spellcheck="false" />
                                        </td>
                                        <td class="mdf-checkbox-cell">
                                            <input type="checkbox" name="mdf_rules[<?php echo esc_attr($index); ?>][is_active]" value="1" <?php checked((int) $rule['is_active'], 1); ?> />
                                        </td>
                                        <td class="mdf-remove-cell">
                                            <button type="button" class="button mdf-remove-rule"><?php esc_html_e('Quitar', 'mdf-redirect-301'); ?></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                        <div class="mdf-rules-actions">
                            <button type="button" class="button button-secondary" id="mdf-add-rule"><?php esc_html_e('Agregar regla', 'mdf-redirect-301'); ?></button>
                        </div>
                    </div>

                    <?php submit_button(__('Guardar reglas', 'mdf-redirect-301')); ?>
                </form>
            <?php elseif ($current_tab === 'ayuda') : ?>
                <div class="mdf-card">
                    <p>
                        <img src="<?php echo esc_url(MDF_REDIRECT_301_URL . 'assets/images/logo-mdf-301-redirects.png'); ?>" alt="<?php echo esc_attr__('Logo MDF 301 Redirects', 'mdf-redirect-301'); ?>" style="max-width: 260px; height: auto;" />
                    </p>
                    <h2><?php esc_html_e('¿Cómo funciona MDF 301 Redirects?', 'mdf-redirect-301'); ?></h2>
                    <p><?php esc_html_e('Este plugin te permite crear redirecciones 301 permanentes para enviar visitantes desde URLs antiguas a nuevas ubicaciones.', 'mdf-redirect-301'); ?></p>
                    <p><strong><?php esc_html_e('Pasos rápidos:', 'mdf-redirect-301'); ?></strong></p>
                    <ol>
                        <li><?php esc_html_e('En la pestaña Configuración, activa el plugin y define dominios externos permitidos si redirigirás fuera del sitio.', 'mdf-redirect-301'); ?></li>
                        <li><?php esc_html_e('En la pestaña Reglas, agrega Origen y Destino. Puedes usar * para redirigir todo el sitio.', 'mdf-redirect-301'); ?></li>
                        <li><?php esc_html_e('Guarda y prueba las URLs en una ventana privada para evitar caché de redirecciones 301.', 'mdf-redirect-301'); ?></li>
                    </ol>
                    <p><?php esc_html_e('Consejo: mantén excluidas las rutas de administración para no bloquear el acceso a WordPress.', 'mdf-redirect-301'); ?></p>
                </div>
            <?php else : ?>
                <div class="mdf-card">
                    <h2><?php esc_html_e('¿Por qué podrías necesitar una redirección 301?', 'mdf-redirect-301'); ?></h2>
                    <p><?php esc_html_e('Una redirección 301 se usa cuando una URL cambia de forma permanente y no quieres que los usuarios o buscadores se queden en una dirección rota.', 'mdf-redirect-301'); ?></p>
                    <ul>
                        <li><?php esc_html_e('Evitar errores 404 cuando cambias el enlace de una página o entrada.', 'mdf-redirect-301'); ?></li>
                        <li><?php esc_html_e('Conservar tráfico al mover contenido desde URLs antiguas a nuevas rutas.', 'mdf-redirect-301'); ?></li>
                        <li><?php esc_html_e('Unificar varias URLs antiguas en una sola URL principal.', 'mdf-redirect-301'); ?></li>
                        <li><?php esc_html_e('Redirigir una sección completa durante migraciones o rediseños del sitio.', 'mdf-redirect-301'); ?></li>
                        <li><?php esc_html_e('Mejorar la experiencia de usuario evitando enlaces rotos compartidos en buscadores o redes sociales.', 'mdf-redirect-301'); ?></li>
                    </ul>
                    <p><?php esc_html_e('En resumen: si una URL vieja ya no debe usarse, una 301 ayuda a enviar automáticamente al destino correcto.', 'mdf-redirect-301'); ?></p>
                </div>
            <?php endif; ?>

            <p class="description" style="margin-top: 20px;">
                <?php esc_html_e('Plugin realizado por José Marin de la Fuente', 'mdf-redirect-301'); ?>
                -
                <a href="https://www.marindelafuente.com.ar" target="_blank" rel="noopener noreferrer">www.marindelafuente.com.ar</a>
            </p>
        </div>
        <?php
    }

    /**
     * Interpreta el checkbox Activa (PHP puede entregar escalar o array si hay claves duplicadas en el POST).
     *
     * @param mixed $value Valor recibido desde $_POST para is_active.
     * @return bool
     */
    private static function is_rule_checkbox_checked($value)
    {
        if ($value === null) {
            return false;
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                if ((string) $item === '1') {
                    return true;
                }
            }

            return false;
        }

        return ($value === true || $value === 1 || $value === '1');
    }

    private static function handle_post()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        if (!isset($_POST['mdf_redirect_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mdf_redirect_nonce'])), 'mdf_redirect_save_settings')) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $form_action = isset($_POST['mdf_form_action']) ? sanitize_key(wp_unslash($_POST['mdf_form_action'])) : '';

        if ($form_action === 'save_config') {
            $enabled = isset($_POST['mdf_redirects_enabled']) ? 1 : 0;
            update_option('mdf_redirects_enabled', $enabled);

            $excluded_raw = isset($_POST['mdf_excluded_paths']) ? (string) wp_unslash($_POST['mdf_excluded_paths']) : '';
            $excluded_lines = preg_split('/\r\n|\r|\n/', $excluded_raw);
            $excluded_paths = array();

            if (is_array($excluded_lines)) {
                foreach ($excluded_lines as $line) {
                    $line = MDF_DB::sanitize_excluded_path($line);
                    if ($line !== '') {
                        $excluded_paths[] = $line;
                    }
                }
            }

            if (empty($excluded_paths)) {
                $excluded_paths = array('wp-admin', 'wp-login.php');
            }

            update_option('mdf_excluded_paths', array_values(array_unique($excluded_paths)));

            $hosts_raw_input = isset($_POST['mdf_allowed_destination_hosts_input']) ? wp_unslash($_POST['mdf_allowed_destination_hosts_input']) : '';
            update_option('mdf_allowed_destination_hosts', MDF_DB::sanitize_allowed_hosts_input((string) $hosts_raw_input));

            add_settings_error(
                'mdf_redirect_notices',
                'mdf_redirect_saved_config',
                __('Configuración guardada correctamente.', 'mdf-redirect-301'),
                'updated'
            );

            return;
        }

        if ($form_action !== 'save_rules') {
            return;
        }

        $existing_by_id = array();
        foreach (MDF_DB::get_all() as $existing_row) {
            if (isset($existing_row['id'])) {
                $existing_by_id[(int) $existing_row['id']] = $existing_row;
            }
        }

        $raw_rules = isset($_POST['mdf_rules']) && is_array($_POST['mdf_rules']) ? wp_unslash($_POST['mdf_rules']) : array();
        $rules = array();
        $active_wildcard_count = 0;
        $discarded_rules_count = 0;
        $raw_rules = array_slice($raw_rules, 0, MDF_DB::MAX_RULES);

        $form_row_number = 0;

        foreach ($raw_rules as $rule) {
            $form_row_number++;

            if (!is_array($rule)) {
                continue;
            }

            $post_id = isset($rule['id']) ? absint($rule['id']) : 0;

            $raw_source = isset($rule['source']) ? trim((string) $rule['source']) : '';
            $raw_destination = isset($rule['destination']) ? trim((string) $rule['destination']) : '';

            $is_active = self::is_rule_checkbox_checked(isset($rule['is_active']) ? $rule['is_active'] : null) ? 1 : 0;

            $source = $raw_source !== '' ? MDF_DB::normalize_source($raw_source) : '';
            $destination = $raw_destination !== '' ? MDF_DB::sanitize_destination($raw_destination) : '';

            if ($source === '' && $raw_source !== '' && $post_id > 0 && isset($existing_by_id[$post_id])) {
                $source = $existing_by_id[$post_id]['source'];
            }

            if ($destination === '' && $raw_destination !== '' && $post_id > 0 && isset($existing_by_id[$post_id])) {
                $destination = $existing_by_id[$post_id]['destination'];
            }

            if ($source === '' || $destination === '') {
                if ($raw_source !== '' || $raw_destination !== '') {
                    $discarded_rules_count++;
                    if ($discarded_rules_count <= 5) {
                        $parts = array();
                        if ($raw_source !== '' && $source === '') {
                            $parts[] = __('el origen (revisa caracteres y formato de la ruta)', 'mdf-redirect-301');
                        }
                        if ($raw_destination !== '' && $destination === '') {
                            $parts[] = __('el destino (añade el dominio en «Dominios externos permitidos» o usa una URL de este mismo sitio)', 'mdf-redirect-301');
                        }
                        add_settings_error(
                            'mdf_redirect_notices',
                            'mdf_redirect_discard_' . $discarded_rules_count,
                            sprintf(
                                /* translators: 1: row number (1-based), 2: details */
                                __('No se pudo guardar la regla en la fila %1$d: revisa %2$s.', 'mdf-redirect-301'),
                                $form_row_number,
                                implode(__(' y ', 'mdf-redirect-301'), $parts)
                            ),
                            'error'
                        );
                    }
                }
                continue;
            }

            if ($source === '*' && $is_active === 1) {
                $active_wildcard_count++;
            }

            $rules[] = array(
                'source' => $source,
                'destination' => $destination,
                'is_active' => $is_active,
            );
        }

        if ($active_wildcard_count > 1) {
            add_settings_error(
                'mdf_redirect_notices',
                'mdf_redirect_multiple_wildcards',
                __('Solo se permite una regla wildcard (*) activa.', 'mdf-redirect-301'),
                'error'
            );
        }

        if (self::has_rule_cycles($rules)) {
            add_settings_error(
                'mdf_redirect_notices',
                'mdf_redirect_cycle_detected',
                __('Se detectó un ciclo entre reglas de redirección. Corrige las rutas para evitar bucles.', 'mdf-redirect-301'),
                'error'
            );
        }

        if ($discarded_rules_count > 5) {
            add_settings_error(
                'mdf_redirect_notices',
                'mdf_redirect_discard_more',
                sprintf(
                    /* translators: %d: number of additional rows not detailed */
                    __('Hay %d reglas más con problemas (solo se listaron las primeras 5).', 'mdf-redirect-301'),
                    $discarded_rules_count - 5
                ),
                'warning'
            );
        }

        MDF_DB::replace_all_rules($rules);

        add_settings_error(
            'mdf_redirect_notices',
            'mdf_redirect_saved_rules',
            __('Reglas guardadas correctamente.', 'mdf-redirect-301'),
            'updated'
        );
    }

    private static function has_rule_cycles($rules)
    {
        $map = array();

        foreach ($rules as $rule) {
            if (!is_array($rule) || empty($rule['is_active'])) {
                continue;
            }

            $source = MDF_DB::normalize_source((string) $rule['source']);
            if ($source === '' || $source === '*') {
                continue;
            }

            $dest_path = self::destination_to_internal_source((string) $rule['destination']);
            if ($dest_path === '') {
                continue;
            }

            $map[$source] = $dest_path;
        }

        foreach ($map as $start => $next) {
            $visited = array($start => true);
            $current = $next;

            while ($current !== '' && isset($map[$current])) {
                if (isset($visited[$current])) {
                    return true;
                }
                $visited[$current] = true;
                $current = $map[$current];
            }
        }

        return false;
    }

    private static function destination_to_internal_source($destination)
    {
        $destination = MDF_DB::sanitize_destination((string) $destination);
        if ($destination === '') {
            return '';
        }

        $site_host = strtolower((string) wp_parse_url(home_url(), PHP_URL_HOST));
        $dest_host = strtolower((string) wp_parse_url($destination, PHP_URL_HOST));

        if ($site_host === '' || $dest_host === '' || $site_host !== $dest_host) {
            return '';
        }

        return MDF_DB::normalize_source($destination);
    }
}
