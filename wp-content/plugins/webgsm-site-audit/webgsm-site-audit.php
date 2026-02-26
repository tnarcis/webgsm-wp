<?php
/**
 * Plugin Name: WebGSM Site Audit – Super Tool
 * Description: Audit complet: linkuri moarte, SEO, securitate, performanță, conflicte CSS/JS, robots.txt, sitemap, debug log, Google Search Console.
 * Version: 3.0.0
 * Author: WebGSM
 * Requires at least: 6.0
 * Requires PHP: 7.2
 */

if (!defined('ABSPATH')) exit;

if (version_compare(PHP_VERSION, '7.2', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="error"><p>WebGSM Site Audit necesită PHP 7.2+. Versiunea curentă: ' . esc_html(PHP_VERSION) . '</p></div>';
    });
    return;
}

define('WEBGSM_SITE_AUDIT_VERSION', '3.0.0');
define('WEBGSM_SITE_AUDIT_PATH', plugin_dir_path(__FILE__));
define('WEBGSM_SITE_AUDIT_URL', plugin_dir_url(__FILE__));

require_once WEBGSM_SITE_AUDIT_PATH . 'includes/class-settings.php';
require_once WEBGSM_SITE_AUDIT_PATH . 'includes/class-link-checker.php';
require_once WEBGSM_SITE_AUDIT_PATH . 'includes/class-admin.php';
require_once WEBGSM_SITE_AUDIT_PATH . 'includes/class-gsc.php';
require_once WEBGSM_SITE_AUDIT_PATH . 'includes/class-debug-log.php';
require_once WEBGSM_SITE_AUDIT_PATH . 'includes/class-security-audit.php';
require_once WEBGSM_SITE_AUDIT_PATH . 'includes/class-performance-audit.php';
require_once WEBGSM_SITE_AUDIT_PATH . 'includes/class-seo-audit.php';
require_once WEBGSM_SITE_AUDIT_PATH . 'includes/class-robots-sitemap.php';
require_once WEBGSM_SITE_AUDIT_PATH . 'includes/class-conflict-detector.php';

add_action('plugins_loaded', function() {
    new WebGSM_Site_Audit_Settings();
    new WebGSM_Site_Audit_Admin();
    new WebGSM_Site_Audit_Link_Checker();
    new WebGSM_Site_Audit_GSC();
    new WebGSM_Site_Audit_Debug_Log();
    new WebGSM_Site_Audit_Security();
    new WebGSM_Site_Audit_Performance();
    new WebGSM_Site_Audit_SEO();
    new WebGSM_Site_Audit_Robots_Sitemap();
    new WebGSM_Site_Audit_Conflict_Detector();
});

register_activation_hook(__FILE__, function() {
    if (!class_exists('WebGSM_Site_Audit_Settings')) {
        require_once plugin_dir_path(__FILE__) . 'includes/class-settings.php';
    }
    add_option('webgsm_site_audit_scan_results', []);
    add_option('webgsm_site_audit_last_scan', 0);
    add_option('webgsm_site_audit_settings', WebGSM_Site_Audit_Settings::get_defaults());
});
