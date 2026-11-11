<?php
if (!defined('ABSPATH')) { exit; }

// Load on normal requests as well as cron/admin, for host update managers.
add_action('plugins_loaded', 'pcc_setup_github_updates', 5);
function pcc_setup_github_updates() {
    require_once __DIR__ . '/plugin-update-checker/plugin-update-checker.php';
    $checker = \YahnisElsts\PluginUpdateChecker\v6\PucFactory::buildUpdateChecker(
        'https://github.com/papayasearch/papaya-custom-code/',
        __DIR__ . '/papaya-custom-code.php',
        'papaya-custom-code'
    );
    $checker->setBranch('main');
    // Public releases work without authentication; an existing token is optional.
    if (defined('PCC_GITHUB_TOKEN') && is_string(PCC_GITHUB_TOKEN) && trim(PCC_GITHUB_TOKEN) !== '') {
        $checker->setAuthentication(trim(PCC_GITHUB_TOKEN));
    }
    $api = $checker->getVcsApi();
    $api->enableReleaseAssets('/^papaya-custom-code\.zip$/',
        \YahnisElsts\PluginUpdateChecker\v6p0\Vcs\Api::REQUIRE_RELEASE_ASSETS);
    // Never offer branch snapshots, bare tags, or source-only releases.
    add_filter('puc_vcs_update_detection_strategies-papaya-custom-code', function ($strategies) use ($api) {
        return array(array($api, 'getLatestRelease'));
    });
}
