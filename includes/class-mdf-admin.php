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
        ?>
        <div class="wrap mdf-redirect-wrap">
            <h1><?php esc_html_e('MDF 301 Redirects', 'mdf-redirect-301'); ?></h1>
            <p><?php esc_html_e('Configura redirecciones 301 de forma simple.', 'mdf-redirect-301'); ?></p>

            <div class="mdf-card">
                <h2><?php esc_html_e('¿Qué es una redirección 301?', 'mdf-redirect-301'); ?></h2>
                <p><?php esc_html_e('Una redirección 301 indica que una URL se movió de forma permanente a otra dirección. Cuando alguien entra a la URL anterior, se envía automáticamente a la nueva.', 'mdf-redirect-301'); ?></p>
                <p><?php esc_html_e('Casos comunes de uso:', 'mdf-redirect-301'); ?></p>
                <ul>
                    <li><?php esc_html_e('Cuando cambias la URL de una página o entrada.', 'mdf-redirect-301'); ?></li>
                    <li><?php esc_html_e('Cuando eliminas contenido y quieres enviar a los usuarios a una página relacionada.', 'mdf-redirect-301'); ?></li>
                    <li><?php esc_html_e('Cuando migras partes de tu sitio a una nueva estructura de enlaces.', 'mdf-redirect-301'); ?></li>
                    <li><?php esc_html_e('Cuando unificas varias URLs antiguas en una URL principal.', 'mdf-redirect-301'); ?></li>
                </ul>
                <p><strong><?php esc_html_e('Ejemplo:', 'mdf-redirect-301'); ?></strong> <?php esc_html_e('si antes usabas /servicios-viejo y ahora la URL correcta es /servicios, puedes crear una regla para enviar automáticamente a los visitantes a la nueva dirección.', 'mdf-redirect-301'); ?></p>
            </div>

            <form method="post">
                <?php wp_nonce_field('mdf_redirect_save_settings', 'mdf_redirect_nonce'); ?>

                <div class="mdf-card">
                    <h2><?php esc_html_e('Estado global', 'mdf-redirect-301'); ?></h2>
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

                <div class="mdf-card">
                    <h2><?php esc_html_e('Reglas de redirección', 'mdf-redirect-301'); ?></h2>
                    <p><?php esc_html_e('Usa * en Origen para redirigir todo el sitio (excepto rutas excluidas).', 'mdf-redirect-301'); ?></p>
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
                                        <input type="text" name="mdf_rules[<?php echo esc_attr($index); ?>][source]" value="<?php echo esc_attr($rule['source']); ?>" class="regular-text" placeholder="* o ruta/origen" />
                                    </td>
                                    <td>
                                        <input type="url" name="mdf_rules[<?php echo esc_attr($index); ?>][destination]" value="<?php echo esc_attr($rule['destination']); ?>" class="large-text" placeholder="https://destino.com" />
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
                    <p>
                        <button type="button" class="button" id="mdf-add-rule"><?php esc_html_e('Agregar regla', 'mdf-redirect-301'); ?></button>
                    </p>
                </div>

                <?php submit_button(__('Guardar cambios', 'mdf-redirect-301')); ?>
            </form>

            <p class="description" style="margin-top: 20px;">
                <?php esc_html_e('Plugin realizado por José Marin de la Fuente', 'mdf-redirect-301'); ?>
                -
                <a href="https://www.marindelafuente.com.ar" target="_blank" rel="noopener noreferrer">www.marindelafuente.com.ar</a>
            </p>
        </div>
        <?php
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

        $enabled = isset($_POST['mdf_redirects_enabled']) ? 1 : 0;
        update_option('mdf_redirects_enabled', $enabled);

        $excluded_raw = isset($_POST['mdf_excluded_paths']) ? (string) wp_unslash($_POST['mdf_excluded_paths']) : '';
        $excluded_lines = preg_split('/\r\n|\r|\n/', $excluded_raw);
        $excluded_paths = array();

        if (is_array($excluded_lines)) {
            foreach ($excluded_lines as $line) {
                $line = trim(sanitize_text_field($line));
                if ($line !== '') {
                    $excluded_paths[] = $line;
                }
            }
        }

        if (empty($excluded_paths)) {
            $excluded_paths = array('wp-admin', 'wp-login.php');
        }

        update_option('mdf_excluded_paths', array_values(array_unique($excluded_paths)));

        $raw_rules = isset($_POST['mdf_rules']) && is_array($_POST['mdf_rules']) ? wp_unslash($_POST['mdf_rules']) : array();
        $rules = array();

        foreach ($raw_rules as $rule) {
            if (!is_array($rule)) {
                continue;
            }

            $source = isset($rule['source']) ? sanitize_text_field($rule['source']) : '';
            $destination = isset($rule['destination']) ? esc_url_raw($rule['destination']) : '';
            $is_active = !empty($rule['is_active']) ? 1 : 0;

            if ($source === '' || $destination === '') {
                continue;
            }

            $rules[] = array(
                'source' => $source,
                'destination' => $destination,
                'is_active' => $is_active,
            );
        }

        MDF_DB::replace_all_rules($rules);

        add_settings_error(
            'mdf_redirect_notices',
            'mdf_redirect_saved',
            __('Configuración guardada correctamente.', 'mdf-redirect-301'),
            'updated'
        );

        settings_errors('mdf_redirect_notices');
    }
}
