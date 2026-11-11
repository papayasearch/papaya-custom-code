<?php
if (!defined('ABSPATH')) { exit; }

function pcc_can_edit_code() {
    return current_user_can('manage_options') && current_user_can('edit_plugins')
        && current_user_can('unfiltered_html')
        && (!is_multisite() || is_super_admin())
        && !(defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT)
        && !(defined('DISALLOW_FILE_MODS') && DISALLOW_FILE_MODS);
}

function pcc_asset_location($kind, $code) {
    $upload = wp_upload_dir();
    if (!empty($upload['error'])) { return new WP_Error('uploads', 'The uploads directory is unavailable.'); }
    $relative = '/papaya-custom-code/site-' . get_current_blog_id();
    $name = 'custom-' . hash('sha256', $code) . '.' . $kind;
    return array('dir' => $upload['basedir'] . $relative,
        'path' => $upload['basedir'] . $relative . '/' . $name,
        'url' => $upload['baseurl'] . $relative . '/' . $name);
}

function pcc_write_asset($kind, $code) {
    $location = pcc_asset_location($kind, $code);
    if (is_wp_error($location)) { return $location; }
    if (is_file($location['path']) && hash_file('sha256', $location['path']) === hash('sha256', $code)) {
        return $location;
    }
    if (!wp_mkdir_p($location['dir'])) { return new WP_Error('directory', 'Could not create the custom code directory.'); }
    // Create and check the entire file before making it available to readers.
    $temporary = @tempnam($location['dir'], '.pcc-');
    if ($temporary === false) { return new WP_Error('temporary', 'Could not create a temporary asset file.'); }
    $written = @file_put_contents($temporary, $code, LOCK_EX);
    if ($written !== strlen($code) || hash_file('sha256', $temporary) !== hash('sha256', $code)) {
        @unlink($temporary);
        return new WP_Error('write', 'Could not write the complete asset. Your previous saved code is unchanged.');
    }
    if (!@chmod($temporary, 0644) || !@rename($temporary, $location['path'])) {
        @unlink($temporary);
        return new WP_Error('publish', 'Could not publish the asset. Your previous saved code is unchanged.');
    }
    return $location;
}

function pcc_save_assets($css, $js) {
    if (!pcc_can_edit_code()) { return new WP_Error('permission', 'You do not have permission to edit code.'); }
    if (!is_string($css) || !is_string($js)) { return new WP_Error('input', 'CSS and JavaScript must be text.'); }
    // Stage both immutable files before switching the single database record.
    foreach (array('css' => $css, 'js' => $js) as $kind => $code) {
        $result = pcc_write_asset($kind, $code);
        if (is_wp_error($result)) { return $result; }
    }
    $state = array('css' => $css, 'js' => $js);
    $old = get_option('pcc_assets_v1', null);
    if ($old !== $state && !update_option('pcc_assets_v1', $state, false)) {
        return new WP_Error('database', 'Could not save the database record. Your previous saved code is unchanged.');
    }
    return true;
}

function pcc_legacy_assets() {
    $assets = array();
    foreach (array('css', 'js') as $kind) {
        $path = __DIR__ . '/custom-code/' . $kind . '/custom.' . $kind;
        if (!is_readable($path)) { return new WP_Error('legacy', 'Could not read legacy code. Restore your CSS and JS from a backup.'); }
        $assets[$kind] = file_get_contents($path);
        if ($assets[$kind] === false) { return new WP_Error('legacy', 'Could not read legacy code.'); }
    }
    return $assets;
}

function pcc_get_assets() {
    $state = get_option('pcc_assets_v1', null);
    if (is_array($state) && isset($state['css'], $state['js']) && is_string($state['css']) && is_string($state['js'])) {
        return $state;
    }
    return pcc_legacy_assets();
}
