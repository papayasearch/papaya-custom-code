<?php
/**
 * Plugin Name: Papaya Custom Code
 * Description: Custom CSS, JavaScript, and optional PHP via an integrated dashboard.
 * Version: 2.6.1
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Update URI: https://github.com/papayasearch/papaya-custom-code/
 * Plugin URI: https://github.com/papayasearch/papaya-custom-code/
 * Author: Papaya Support
 */

if (!defined('ABSPATH')) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/github-updates.php';
require_once __DIR__ . '/php-editor.php';
require_once __DIR__ . '/code-editors.php';

function pcc_is_safe_mode() {
    return pcc_can_edit_code() && isset($_GET['pcc_safe_mode']) && $_GET['pcc_safe_mode'] === '1';
}

add_action('wp_enqueue_scripts', 'pcc_enqueue_custom_assets', 999);
function pcc_enqueue_custom_assets() {
    if (pcc_is_safe_mode()) return;

    $state = get_option('pcc_assets_v1', null);
    foreach (array('css', 'js') as $kind) {
        if (is_array($state) && isset($state[$kind]) && is_string($state[$kind])) {
            if (trim($state[$kind]) === '') { continue; }
            $location = pcc_asset_location($kind, $state[$kind]);
            if (is_wp_error($location) || !is_readable($location['path'])) { continue; }
            $url = $location['url'];
            $version = null; // Content-hashed filename changes on every edit.
        } else {
            $path = __DIR__ . '/custom-code/' . $kind . '/custom.' . $kind;
            if (!is_readable($path) || filesize($path) === 0) { continue; }
            $url = plugin_dir_url(__FILE__) . 'custom-code/' . $kind . '/custom.' . $kind;
            $version = filemtime($path);
        }
        if ($kind === 'css') {
            wp_enqueue_style('pcc-custom-style', $url, array(), $version);
        } else {
            wp_enqueue_script('pcc-custom-script', $url, array('jquery'), $version, true);
        }
    }
}

add_action('admin_menu', 'pcc_create_dashboard_menu');
function pcc_create_dashboard_menu() {
    add_management_page(
        'Papaya Custom Code',       
        'Papaya Custom Code',       
        'manage_options',           
        'papaya-custom-code',       
        'pcc_render_dashboard_gui'  
    );
}

function pcc_render_dashboard_gui() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (!pcc_can_edit_code()) {
        echo '<div class="wrap"><h1>Papaya Custom Code</h1><p>Code editing requires plugin-editing and unfiltered HTML permissions. It is unavailable when file editing is disabled, and is limited to super administrators on multisite.</p></div>';
        return;
    }
    $assets = pcc_get_assets();
    $error = is_wp_error($assets) ? $assets->get_error_message() : '';
    $current_css = is_wp_error($assets) ? '' : $assets['css'];
    $current_js = is_wp_error($assets) ? '' : $assets['js'];
    $message = '';
    // Rebuild missing published files from the database only on authorized admin visits.
    if (is_array(get_option('pcc_assets_v1', null)) && !is_wp_error($assets)) {
        foreach ($assets as $kind => $code) {
            if (!in_array($kind, array('css', 'js'), true)) { continue; }
            $published = pcc_write_asset($kind, $code);
            if (is_wp_error($published)) { $error = $published->get_error_message(); }
        }
    }
    if (isset($_POST['pcc_save_nonce'])) {
        check_admin_referer('pcc_save_action', 'pcc_save_nonce');
        if (!isset($_POST['pcc_css_code'], $_POST['pcc_js_code'])
            || !is_string($_POST['pcc_css_code']) || !is_string($_POST['pcc_js_code'])) {
            $error = 'Nothing saved: both CSS and JavaScript fields must contain text.';
        } else {
            $current_css = wp_unslash($_POST['pcc_css_code']);
            $current_js = wp_unslash($_POST['pcc_js_code']);
            $saved = pcc_save_assets($current_css, $current_js);
            if (is_wp_error($saved)) {
                $error = $saved->get_error_message();
            } else {
                $error = '';
                $message = '<div class="notice notice-success"><p>CSS and JavaScript saved. Clear your page and optimization caches.</p></div>';
            }
        }
    }
    if ($error !== '') {
        $message = '<div class="notice notice-error"><p>' . esc_html($error) . '</p></div>';
    }
    ?>
    <div class="wrap pcc-dashboard">
        <h1>Papaya Custom Code Dashboard</h1>
        <p class="description">Manage trusted custom code. Saved CSS and JavaScript are stored per site in the database and published as external files in uploads.</p>
        
        <?php echo $message; ?>
        <?php if (get_option('pcc_assets_v1', null) === null) : ?>
            <div class="notice notice-warning inline"><p>First upgrade from 2.3/2.4: paste your backed-up CSS and JavaScript below, then save. Replacing the old plugin may have overwritten its files. Check both editors before saving.</p></div>
        <?php endif; ?>

        <form method="post" action="" style="margin-top: 20px;">
            <?php wp_nonce_field('pcc_save_action', 'pcc_save_nonce'); ?>
            
            <div style="margin-bottom: 25px;">
                <label for="pcc_css_code" style="display:block; font-weight:bold; margin-bottom:8px; font-size:14px;">Custom CSS (Styles)</label>
                <textarea id="pcc_css_code" name="pcc_css_code" rows="12" style="width:100%; font-family:monospace; background:#f6f7f7; padding:10px; border:1px solid #8c8f94;"><?php echo esc_textarea($current_css); ?></textarea>
            </div>

            <div style="margin-bottom: 25px;">
                <label for="pcc_js_code" style="display:block; font-weight:bold; margin-bottom:8px; font-size:14px;">Custom JavaScript (Scripts)</label>
                <textarea id="pcc_js_code" name="pcc_js_code" rows="12" style="width:100%; font-family:monospace; background:#f6f7f7; padding:10px; border:1px solid #8c8f94;"><?php echo esc_textarea($current_js); ?></textarea>
            </div>

            <?php submit_button('Save Changes', 'primary', 'submit'); ?>
        </form>
        <?php pcc_render_php_editor(); ?>
    </div>
    <?php
}

register_activation_hook(__FILE__, 'pcc_setup_folders');
function pcc_setup_folders() {
    $base = plugin_dir_path(__FILE__) . 'custom-code/';
    $folders = array('css', 'js');

    foreach ($folders as $folder) {
        $path = $base . $folder . '/';
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        if (!file_exists($path . 'index.php')) {
            file_put_contents($path . 'index.php', '<?php // Silence is golden');
        }
    }
}