<?php
/**
 * UI + AJAX pentru citirea wp-content/webgsm-perf-audit.log
 */
if (!defined('ABSPATH')) {
    exit;
}

class WebGSM_Site_Audit_Slow_Request_Log {

    const LOG_BASENAME = 'webgsm-perf-audit.log';

    public function __construct() {
        add_action('wp_ajax_webgsm_audit_get_slow_log', [$this, 'ajax_get_log']);
        add_action('wp_ajax_webgsm_audit_clear_slow_log', [$this, 'ajax_clear_log']);
    }

    public function ajax_get_log() {
        check_ajax_referer('webgsm_site_audit', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Forbidden');
        }

        $path = WP_CONTENT_DIR . '/' . self::LOG_BASENAME;
        $max_lines = isset($_POST['lines']) ? min(2000, max(50, (int) $_POST['lines'])) : 300;
        $filter    = isset($_POST['filter']) ? sanitize_text_field(wp_unslash($_POST['filter'])) : '';

        $settings = WebGSM_Site_Audit_Settings::get();
        $enabled  = !empty($settings['slow_request_log_enabled'])
            || (defined('WEBGSM_PERF_AUDIT') && WEBGSM_PERF_AUDIT);

        $exists = file_exists($path) && is_readable($path);
        $size   = $exists ? filesize($path) : 0;

        $lines_out = [];
        if ($exists) {
            $lines_out = $this->tail_lines_matching($path, $max_lines, $filter);
        }

        wp_send_json_success([
            'lines'   => $lines_out,
            'exists'  => $exists,
            'size'    => $exists ? size_format($size) : '0 B',
            'size_bytes' => $size,
            'enabled' => (bool) $enabled,
            'path'    => 'wp-content/' . self::LOG_BASENAME,
        ]);
    }

    public function ajax_clear_log() {
        check_ajax_referer('webgsm_site_audit', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Forbidden');
        }

        $path = WP_CONTENT_DIR . '/' . self::LOG_BASENAME;
        if (file_exists($path) && is_writable($path)) {
            file_put_contents($path, '');
            wp_send_json_success(['message' => 'Jurnal golit.']);
        }
        wp_send_json_error('Fișier inexistent sau nu se poate scrie.');
    }

    /**
     * Ultimele linii (cele mai noi primele), opțional filtru substring.
     *
     * @return string[]
     */
    private function tail_lines_matching($path, $max_lines, $filter) {
        $fsize = filesize($path);
        if ($fsize === 0) {
            return [];
        }

        $read = min($fsize, max(50000, $max_lines * 400));
        $fh   = fopen($path, 'rb');
        if (!$fh) {
            return [];
        }
        fseek($fh, max(0, $fsize - $read));
        $chunk = fread($fh, $read);
        fclose($fh);

        $raw = explode("\n", $chunk);
        $raw = array_values(array_filter($raw, function ($l) {
            return trim($l) !== '';
        }));

        $raw = array_reverse($raw);
        $out = [];
        $filter_lower = $filter !== '' ? strtolower($filter) : '';

        foreach ($raw as $line) {
            if ($filter_lower !== '' && strpos(strtolower($line), $filter_lower) === false) {
                continue;
            }
            $out[] = $line;
            if (count($out) >= $max_lines) {
                break;
            }
        }

        return $out;
    }
}
