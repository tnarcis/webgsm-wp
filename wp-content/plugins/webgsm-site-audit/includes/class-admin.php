<?php
if (!defined('ABSPATH')) exit;

class WebGSM_Site_Audit_Admin {

    const SLUG = 'webgsm-site-audit';

    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);
    }

    public function add_menu() {
        add_menu_page(
            'Site Audit',
            'Site Audit',
            'manage_options',
            self::SLUG,
            [$this, 'render_dashboard'],
            'dashicons-chart-area',
            80
        );
        add_submenu_page(
            self::SLUG,
            'Dashboard',
            'Dashboard',
            'manage_options',
            self::SLUG,
            [$this, 'render_dashboard']
        );
        add_submenu_page(
            self::SLUG,
            'Setări',
            'Setări',
            'manage_options',
            self::SLUG . '-settings',
            [$this, 'render_settings']
        );
    }

    public function enqueue($hook) {
        if (strpos($hook, self::SLUG) === false) return;

        wp_enqueue_style('webgsm-site-audit', WEBGSM_SITE_AUDIT_URL . 'admin/css/site-audit.css', [], WEBGSM_SITE_AUDIT_VERSION);
        wp_enqueue_script('webgsm-site-audit', WEBGSM_SITE_AUDIT_URL . 'admin/js/site-audit.js', ['jquery'], WEBGSM_SITE_AUDIT_VERSION, true);
        wp_localize_script('webgsm-site-audit', 'webgsmSiteAudit', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('webgsm_site_audit'),
        ]);
    }

    public function render_dashboard() {
        $results = get_option('webgsm_site_audit_scan_results', []);
        $last_scan = get_option('webgsm_site_audit_last_scan', 0);
        $gsc_data = WebGSM_Site_Audit_GSC::get_stored_data();
        include WEBGSM_SITE_AUDIT_PATH . 'admin/views/dashboard.php';
    }

    public function render_settings() {
        $settings = WebGSM_Site_Audit_Settings::get();
        include WEBGSM_SITE_AUDIT_PATH . 'admin/views/settings.php';
    }
}
