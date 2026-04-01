<?php
/**
 * Plugin Name: WebGSM Packeta Admin
 * Description: Interfață simplă în admin pentru AWB Packeta și grupare expediție (ridicare curier).
 * Version: 1.4.1
 * Author: WebGSM
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WEBGSM_PACKETA_VERSION', '1.4.1');
define('WEBGSM_PACKETA_PATH', plugin_dir_path(__FILE__));
define('WEBGSM_PACKETA_URL', plugin_dir_url(__FILE__));
define('WEBGSM_PACKETA_OPTION', 'webgsm_packeta_settings');

require_once WEBGSM_PACKETA_PATH . 'includes/class-packeta-xml-client.php';
require_once WEBGSM_PACKETA_PATH . 'includes/class-packeta-config.php';
require_once WEBGSM_PACKETA_PATH . 'includes/class-packeta-carriers.php';
require_once WEBGSM_PACKETA_PATH . 'includes/class-packeta-admin.php';

add_action('plugins_loaded', function () {
    new WebGSM_Packeta_Admin();
});
