<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_enqueue_scripts', 'pcc_enqueue_code_editors');
function pcc_enqueue_code_editors($hook_suffix) {
    if ($hook_suffix !== 'tools_page_papaya-custom-code' || !pcc_can_edit_code()) {
        return;
    }
    $languages = array('pcc_css_code' => 'text/css', 'pcc_js_code' => 'text/javascript');
    if (pcc_can_edit_php()) {
        $languages['pcc_php_code'] = 'application/x-httpd-php';
    }
    $editors = array();
    foreach ($languages as $id => $type) {
        $options = array(
            'lineNumbers' => true,
            'indentUnit' => 4,
            'tabSize' => 4,
            'indentWithTabs' => false,
            'smartIndent' => true,
            'matchBrackets' => true,
            'autoCloseBrackets' => true,
            'lineWrapping' => false,
            'lint' => false,
        );
        if ($id === 'pcc_php_code') {
            // This editor accepts PHP statements without opening PHP tags.
            $options['mode'] = 'text/x-php';
        }
        $settings = wp_enqueue_code_editor(array('type' => $type, 'codemirror' => $options));
        if ($settings !== false) {
            $editors[$id] = $settings;
        }
    }
    wp_enqueue_style('pcc-code-editors', plugin_dir_url(__FILE__) . 'assets/code-editors.css', array(), '2.6');
    if (empty($editors)) {
        add_action('admin_notices', 'pcc_code_editor_preference_notice');
        return;
    }
    wp_enqueue_script('pcc-code-editors', plugin_dir_url(__FILE__) . 'assets/code-editors.js', array('jquery', 'code-editor'), '2.6', true);
    wp_add_inline_script('pcc-code-editors', 'window.pccCodeEditors = ' . wp_json_encode($editors) . ';', 'before');
}

function pcc_code_editor_preference_notice() {
    echo '<div class="notice notice-info"><p>Code highlighting is unavailable. If disabled in your profile, enable it under Users → Profile → Syntax Highlighting, then reload this page.</p></div>';
}
