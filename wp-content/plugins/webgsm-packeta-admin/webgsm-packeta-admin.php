<?php
/**
 * Plugin Name: WebGSM Packeta Admin
 * Description: AWB Packeta, istoric livrări și urmărire curier pentru admin și clienți.
 * Version: 1.7.3
 * Author: WebGSM
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WEBGSM_PACKETA_VERSION', '1.7.3');
define('WEBGSM_PACKETA_DB_VERSION_OPTION', 'webgsm_packeta_db_version');
define('WEBGSM_PACKETA_PATH', plugin_dir_path(__FILE__));
define('WEBGSM_PACKETA_URL', plugin_dir_url(__FILE__));
define('WEBGSM_PACKETA_OPTION', 'webgsm_packeta_settings');

require_once WEBGSM_PACKETA_PATH . 'includes/class-packeta-xml-client.php';
require_once WEBGSM_PACKETA_PATH . 'includes/class-packeta-config.php';
$ro_counties_file = WEBGSM_PACKETA_PATH . 'includes/class-packeta-ro-counties.php';
if (is_readable($ro_counties_file)) {
    require_once $ro_counties_file;
}
require_once WEBGSM_PACKETA_PATH . 'includes/class-packeta-ro-pricelist.php';
require_once WEBGSM_PACKETA_PATH . 'includes/class-packeta-carrier-pricing-sync.php';
require_once WEBGSM_PACKETA_PATH . 'includes/class-packeta-carriers.php';
require_once WEBGSM_PACKETA_PATH . 'includes/class-packeta-status-mapper.php';
require_once WEBGSM_PACKETA_PATH . 'includes/class-packeta-carrier-tracking.php';
require_once WEBGSM_PACKETA_PATH . 'includes/class-packeta-sender-mapper.php';
require_once WEBGSM_PACKETA_PATH . 'includes/class-packeta-awb-repository.php';
require_once WEBGSM_PACKETA_PATH . 'includes/class-packeta-awb-sync.php';
require_once WEBGSM_PACKETA_PATH . 'includes/class-packeta-admin.php';
require_once WEBGSM_PACKETA_PATH . 'includes/class-packeta-customer-tracking.php';

register_activation_hook(__FILE__, function () {
    WebGSM_Packeta_Awb_Repository::install();
    update_option(WEBGSM_PACKETA_DB_VERSION_OPTION, WEBGSM_PACKETA_VERSION);
});

add_filter('cron_schedules', function (array $schedules): array {
    $schedules['webgsm_packeta_2hours'] = [
        'interval' => 2 * HOUR_IN_SECONDS,
        'display' => 'WebGSM Packeta — la 2 ore',
    ];

    return $schedules;
});

add_action('plugins_loaded', function () {
    if (get_option(WEBGSM_PACKETA_DB_VERSION_OPTION) !== WEBGSM_PACKETA_VERSION) {
        WebGSM_Packeta_Awb_Repository::install();
        update_option(WEBGSM_PACKETA_DB_VERSION_OPTION, WEBGSM_PACKETA_VERSION);
    }
    new WebGSM_Packeta_Admin();
    new WebGSM_Packeta_Customer_Tracking();
});
