<?php
if (!defined('ABSPATH')) { exit; }

// Load on normal requests as well as cron/admin, for host update managers.
add_action('plugins_loaded', 'pcc_setup_github_updates', 5);
function pcc_setup_github_updates() {
    if (!defined('PCC_GITHUB_TOKEN') || !is_string(PCC_GITHUB_TOKEN) || trim(PCC_GITHUB_TOKEN) === '') {
        add_action('admin_notices', 'pcc_github_token_notice');
        return;
    }
    require_once __DIR__ . '/plugin-update-checker/plugin-update-checker.php';
    $checker = \YahnisElsts\PluginUpdateChecker\v6\PucFactory::buildUpdateChecker(
        'https://github.com/papayasearch/papaya-custom-code/',
        __DIR__ . '/papaya-custom-code.php',
        'papaya-custom-code'
    );
    $checker->setBranch('main');
    $checker->setAuthentication(trim(PCC_GITHUB_TOKEN));
    $api = $checker->getVcsApi();
    $api->enableReleaseAssets('/^papaya-custom-code\.zip$/',
        \YahnisElsts\PluginUpdateChecker\v6p0\Vcs\Api::REQUIRE_RELEASE_ASSETS);
    // Never offer branch snapshots, bare tags, or source-only releases.
    add_filter('puc_vcs_update_detection_strategies-papaya-custom-code', function ($strategies) use ($api) {
        return array(array($api, 'getLatestRelease'));
    });
}
function pcc_github_token_notice() {
    if (!current_user_can('update_plugins')) { return; }
    echo '<div class="notice notice-warning"><p>Papaya Custom Code: private GitHub updates need a read-only repository token configured as PCC_GITHUB_TOKEN in wp-config.php. The plugin remains usable without it. See the installation instructions.</p></div>';
}
