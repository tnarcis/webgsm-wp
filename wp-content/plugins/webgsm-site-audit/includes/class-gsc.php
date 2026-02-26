<?php
if (!defined('ABSPATH')) exit;

class WebGSM_Site_Audit_GSC {

    const GSC_DATA_KEY = 'webgsm_site_audit_gsc_data';

    public function __construct() {
        add_action('wp_ajax_webgsm_audit_import_gsc', [$this, 'ajax_import_gsc']);
        add_action('wp_ajax_webgsm_audit_get_gsc', [$this, 'ajax_get_gsc']);
    }

    public function ajax_import_gsc() {
        check_ajax_referer('webgsm_site_audit', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Forbidden');

        $json = isset($_POST['gsc_json']) ? wp_unslash($_POST['gsc_json']) : '';
        if (empty($json)) {
            wp_send_json_error('JSON gol');
        }

        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error('JSON invalid: ' . json_last_error_msg());
        }

        $parsed = $this->parse_gsc_export($data);
        update_option(self::GSC_DATA_KEY, $parsed);

        wp_send_json_success([
            'message' => 'Date GSC importate',
            'pages' => count($parsed['pages'] ?? []),
            'issues' => count($parsed['issues'] ?? []),
        ]);
    }

    public function ajax_get_gsc() {
        check_ajax_referer('webgsm_site_audit', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Forbidden');

        $data = get_option(self::GSC_DATA_KEY, []);
        wp_send_json_success($data);
    }

    private function parse_gsc_export($data) {
        $out = [
            'pages' => [],
            'issues' => [],
            'indexed' => 0,
            'excluded' => 0,
            'errors' => [],
        ];

        if (isset($data['pages'])) {
            foreach ($data['pages'] as $p) {
                $out['pages'][] = [
                    'url' => $p['url'] ?? '',
                    'status' => $p['status'] ?? 'unknown',
                    'last_crawl' => $p['lastCrawl'] ?? '',
                    'coverage' => $p['coverageState'] ?? '',
                ];
                if (($p['coverageState'] ?? '') === 'Indexed') $out['indexed']++;
                else $out['excluded']++;
            }
        }

        if (isset($data['issues'])) {
            foreach ($data['issues'] as $i) {
                $out['issues'][] = [
                    'type' => $i['type'] ?? '',
                    'url' => $i['url'] ?? '',
                    'message' => $i['message'] ?? '',
                ];
            }
        }

        if (isset($data['errors'])) {
            $out['errors'] = $data['errors'];
        }

        return $out;
    }

    public static function get_stored_data() {
        return get_option(self::GSC_DATA_KEY, []);
    }
}
