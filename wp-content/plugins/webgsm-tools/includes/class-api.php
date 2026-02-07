<?php
if (!defined('ABSPATH')) exit;

class WebGSM_Tools_API {

    public function __construct() {
        add_action('wp_ajax_webgsm_tools_export_csv', [$this, 'ajax_export_csv']);
        add_action('wp_ajax_webgsm_tools_process_image', [$this, 'ajax_process_image']);
    }

    public function ajax_export_csv() {
        check_ajax_referer('webgsm_tools', 'nonce');
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        $data = isset($_POST['data']) ? json_decode(stripslashes($_POST['data']), true) : [];
        if (empty($data)) {
            wp_send_json_error(['message' => 'No data']);
        }
        wp_send_json_success(['csv' => $this->build_csv($data)]);
    }

    private function build_csv($rows) {
        $headers = [];
        foreach (array_keys($rows[0]) as $h) {
            if (strpos($h, '_') !== 0) {
                $headers[] = $h;
            }
        }
        if (empty($headers)) {
            $headers = array_keys($rows[0]);
        }
        $csv = '';
        foreach ($headers as $h) {
            $csv .= '"' . str_replace('"', '""', $h) . '",';
        }
        $csv = rtrim($csv, ',') . "\n";
        foreach ($rows as $row) {
            foreach ($headers as $h) {
                $val = isset($row[$h]) ? (string) $row[$h] : '';
                $csv .= '"' . str_replace('"', '""', $val) . '",';
            }
            $csv = rtrim($csv, ',') . "\n";
        }
        return $csv;
    }

    public function ajax_process_image() {
        check_ajax_referer('webgsm_tools', 'nonce');
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        wp_send_json_success(['message' => 'Placeholder', 'url' => '']);
    }
}
