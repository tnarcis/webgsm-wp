<?php
/**
 * Plugin Name: WebGSM Tools
 * Description: Product Reviewer & Image Studio pentru verificare și procesare produse
 * Version: 1.0.0
 * Author: WebGSM
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

if (!defined('ABSPATH')) exit;

define('WEBGSM_TOOLS_VERSION', '1.0.0');
define('WEBGSM_TOOLS_PATH', plugin_dir_path(__FILE__));
define('WEBGSM_TOOLS_URL', plugin_dir_url(__FILE__));

require_once WEBGSM_TOOLS_PATH . 'includes/class-helpers.php';
require_once WEBGSM_TOOLS_PATH . 'includes/class-admin-menu.php';
require_once WEBGSM_TOOLS_PATH . 'includes/class-reviewer.php';
require_once WEBGSM_TOOLS_PATH . 'includes/class-studio.php';
require_once WEBGSM_TOOLS_PATH . 'includes/class-api.php';

add_action('plugins_loaded', function() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p>WebGSM Tools necesită WooCommerce activ.</p></div>';
        });
        return;
    }
    new WebGSM_Tools_Admin_Menu();
    new WebGSM_Tools_API();
});

register_activation_hook(__FILE__, function() {
    $upload_dir = wp_upload_dir();
    $dirs = [
        $upload_dir['basedir'] . '/webgsm-tools',
        $upload_dir['basedir'] . '/webgsm-tools/processed-images',
        $upload_dir['basedir'] . '/webgsm-tools/temp'
    ];
    foreach ($dirs as $dir) {
        if (!file_exists($dir)) {
            wp_mkdir_p($dir);
        }
    }
});
