<?php
/**
 * Plugin Name: WebGSM Site Audit
 * Description: Analiză linkuri moarte, crawl-uri și probleme SEO. Integrare Google Search Console.
 * Version: 1.0.0
 * Author: WebGSM
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

if (!defined('ABSPATH')) exit;

define('WEBGSM_SITE_AUDIT_VERSION', '1.0.0');
define('WEBGSM_SITE_AUDIT_PATH', plugin_dir_path(__FILE__));
define('WEBGSM_SITE_AUDIT_URL', plugin_dir_url(__FILE__));

require_once WEBGSM_SITE_AUDIT_PATH . 'includes/class-settings.php';
require_once WEBGSM_SITE_AUDIT_PATH . 'includes/class-link-checker.php';
require_once WEBGSM_SITE_AUDIT_PATH . 'includes/class-admin.php';
require_once WEBGSM_SITE_AUDIT_PATH . 'includes/class-gsc.php';

add_action('plugins_loaded', function() {
    new WebGSM_Site_Audit_Settings();
    new WebGSM_Site_Audit_Admin();
    new WebGSM_Site_Audit_Link_Checker();
    new WebGSM_Site_Audit_GSC();
});

register_activation_hook(__FILE__, function() {
    add_option('webgsm_site_audit_scan_results', []);
    add_option('webgsm_site_audit_last_scan', 0);
    add_option('webgsm_site_audit_settings', WebGSM_Site_Audit_Settings::get_defaults());
});
