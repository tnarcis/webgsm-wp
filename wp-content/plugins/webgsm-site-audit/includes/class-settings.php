<?php
if (!defined('ABSPATH')) exit;

class WebGSM_Site_Audit_Settings {

    const OPTION_KEY = 'webgsm_site_audit_settings';

    public static function get_defaults() {
        return [
            'scan_posts' => true,
            'scan_pages' => true,
            'scan_products' => true,
            'scan_menus' => true,
            'scan_widgets' => true,
            'scan_options' => true,
            'scan_theme_files' => false,
            'timeout' => 10,
            'batch_size' => 20,
            'check_external' => true,
            'check_internal' => true,
            'follow_redirects' => true,
            'max_redirects' => 5,
            'gsc_enabled' => false,
            'gsc_json' => '',
            'schedule_enabled' => false,
            'schedule_frequency' => 'weekly',
        ];
    }

    public static function get() {
        $saved = get_option(self::OPTION_KEY, []);
        return wp_parse_args($saved, self::get_defaults());
    }

    public function __construct() {
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function register_settings() {
        register_setting('webgsm_site_audit', self::OPTION_KEY, [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitize'],
        ]);
    }

    public function sanitize($input) {
        $defaults = self::get_defaults();
        $out = [];
        foreach ($defaults as $key => $default) {
            if (isset($input[$key])) {
                if (is_bool($default)) {
                    $out[$key] = !empty($input[$key]);
                } elseif ($key === 'timeout') {
                    $out[$key] = max(5, min(60, (int) $input[$key]));
                } elseif ($key === 'max_redirects') {
                    $out[$key] = max(1, min(10, (int) $input[$key]));
                } elseif ($key === 'gsc_json') {
                    $out[$key] = wp_kses_post($input[$key]);
                } elseif ($key === 'batch_size') {
                    $out[$key] = max(5, min(100, (int) $input[$key]));
                } else {
                    $out[$key] = is_int($default) ? (int) $input[$key] : sanitize_text_field($input[$key]);
                }
            } else {
                $out[$key] = $default;
            }
        }
        return $out;
    }
}
