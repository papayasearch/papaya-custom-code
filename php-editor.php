<?php
/** Optional, administrator-authored PHP. Never accept code from public requests. */
if (!defined('ABSPATH')) {
    exit;
}

function pcc_can_edit_php() {
    return pcc_can_edit_code();
}

function pcc_php_set_option($name, $value) {
    if (get_option($name, null) === $value) { return true; }
    return update_option($name, $value, false);
}

function pcc_php_disable($message) {
    update_option('pcc_php_enabled', false, false);
    update_option('pcc_php_error', $message, false);
}

add_action('plugins_loaded', 'pcc_run_custom_php', 20);
function pcc_run_custom_php() {
    if (!get_option('pcc_php_enabled', false)) {
        return;
    }
    if (defined('PCC_DISABLE_PHP') && PCC_DISABLE_PHP) {
        return;
    }
    // Only privileged users may bypass server-side behavior through a URL.
    if (pcc_can_edit_php() && (
        pcc_is_safe_mode() ||
        (is_admin() && isset($_GET['page']) && $_GET['page'] === 'papaya-custom-code')
    )) {
        return;
    }
    $code = get_option('pcc_php_code', '');
    if (!is_string($code) || trim($code) === '') {
        return;
    }
    register_shutdown_function(function () {
        $error = error_get_last();
        if ($error && in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR), true)
            && strpos($error['file'], __FILE__ . '(') === 0) {
            pcc_php_disable($error['message']);
        }
    });
    try {
        // Deliberately executes trusted code, like a plugin; this is not a sandbox.
        eval($code);
    } catch (Throwable $error) {
        pcc_php_disable($error->getMessage());
    }
}

function pcc_render_php_editor() {
    if (!pcc_can_edit_php()) {
        echo '<h2>Custom PHP</h2><p>PHP editing requires permission to edit plugins and must be allowed by your WordPress configuration.</p>';
        return;
    }
    $code = get_option('pcc_php_code', '');
    $enabled = (bool) get_option('pcc_php_enabled', false);
    $notice = '';
    $success = false;
    if (isset($_POST['pcc_php_save'])) {
        check_admin_referer('pcc_php_save_action', 'pcc_php_nonce');
        $submitted = isset($_POST['pcc_php_code']) && is_string($_POST['pcc_php_code'])
            ? wp_unslash($_POST['pcc_php_code']) : '';
        $requested = isset($_POST['pcc_php_enabled']);
        try {
            // Honor an explicit disable even when the submitted draft is invalid.
            if (!$requested && !pcc_php_set_option('pcc_php_enabled', false)) {
                throw new RuntimeException('Could not disable PHP in the database. Use PCC_DISABLE_PHP in wp-config.php for recovery.');
            }
            if (!isset($_POST['pcc_php_code']) || !is_string($_POST['pcc_php_code'])) {
                throw new RuntimeException('PHP must be submitted as text.');
            }
            if (!function_exists('token_get_all')) {
                throw new RuntimeException('PHP syntax validation is unavailable on this server.');
            }
            // Parse without executing. Store only PHP statements, without PHP tags.
            $tokens = token_get_all("<?php\n" . $submitted, TOKEN_PARSE);
            foreach ($tokens as $index => $token) {
                if (is_array($token) && ($token[0] === T_CLOSE_TAG || $token[0] === T_INLINE_HTML
                    || ($index > 0 && in_array($token[0], array(T_OPEN_TAG, T_OPEN_TAG_WITH_ECHO), true)))) {
                    throw new RuntimeException('Enter PHP statements without opening or closing PHP tags.');
                }
            }
            // Do not replace code unless disabling the previous snippet succeeded.
            if (!pcc_php_set_option('pcc_php_enabled', false)) {
                throw new RuntimeException('Could not disable the previous snippet; no new code was saved.');
            }
            if (!pcc_php_set_option('pcc_php_code', $submitted)) {
                throw new RuntimeException('Could not save PHP. The snippet remains disabled.');
            }
            $enabled = $requested && trim($submitted) !== '';
            if (!pcc_php_set_option('pcc_php_enabled', $enabled)) {
                throw new RuntimeException('PHP was saved, but enabling it failed.');
            }
            delete_option('pcc_php_error');
            $success = true;
            $notice = $enabled ? 'PHP saved and enabled. It will run on subsequent requests.' : 'PHP saved and disabled.';
        } catch (Throwable $error) {
            $notice = 'PHP save failed: ' . $error->getMessage();
            $enabled = (bool) get_option('pcc_php_enabled', false);
        }
        $code = $submitted;
    }
    $runtime_error = get_option('pcc_php_error', '');
    ?>
    <hr style="margin-top:30px;">
    <h2>Custom PHP</h2>
    <p>Enter trusted PHP without <code>&lt;?php</code> or <code>?&gt;</code> tags. Enabled code runs after plugins load, on both public and admin requests. Register actions and filters here; avoid printing content directly.</p>
    <p>PHP is skipped on this editor page. Syntax checking cannot detect every runtime problem or undo changes made by a snippet. PHP has full access to the site under the server’s permissions; syntax validation is not a security sandbox. Leave it disabled unless needed, and test trusted snippets on a staging site first.</p>
    <?php if ($notice !== '') : ?>
        <div class="notice <?php echo $success ? 'notice-success' : 'notice-error'; ?> inline"><p><?php echo esc_html($notice); ?></p></div>
    <?php endif; ?>
    <?php if ($runtime_error !== '') : ?>
        <div class="notice notice-error inline"><p>PHP was disabled after an error: <?php echo esc_html($runtime_error); ?></p></div>
    <?php endif; ?>
    <?php if (defined('PCC_DISABLE_PHP') && PCC_DISABLE_PHP) : ?>
        <div class="notice notice-warning inline"><p>The PCC_DISABLE_PHP recovery setting is active. PHP will not execute.</p></div>
    <?php endif; ?>
    <form method="post" action="">
        <?php wp_nonce_field('pcc_php_save_action', 'pcc_php_nonce'); ?>
        <label for="pcc_php_code" style="display:block;font-weight:bold;margin-bottom:8px;">Custom PHP (Server-side code)</label>
        <textarea id="pcc_php_code" name="pcc_php_code" rows="16" spellcheck="false" style="width:100%;font-family:monospace;background:#f6f7f7;padding:10px;border:1px solid #8c8f94;"><?php echo esc_textarea($code); ?></textarea>
        <p><label><input type="checkbox" name="pcc_php_enabled" value="1" <?php checked($enabled); ?>> Enable custom PHP</label></p>
        <?php submit_button('Save PHP', 'primary', 'pcc_php_save'); ?>
    </form>
    <p>Emergency recovery: add <code>define('PCC_DISABLE_PHP', true);</code> to <code>wp-config.php</code> before WordPress loads, then return here to fix or disable the snippet.</p>
    <?php
}
