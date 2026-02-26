<?php
if (!defined('ABSPATH')) exit;

class WebGSM_Site_Audit_Debug_Log {

    public function __construct() {
        add_action('wp_ajax_webgsm_audit_get_debug_log', [$this, 'ajax_get_log']);
        add_action('wp_ajax_webgsm_audit_clear_debug_log', [$this, 'ajax_clear_log']);
    }

    public function ajax_get_log() {
        check_ajax_referer('webgsm_site_audit', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Forbidden');

        $log_path = WP_CONTENT_DIR . '/debug.log';
        $lines = isset($_POST['lines']) ? min(5000, max(100, (int) $_POST['lines'])) : 500;
        $filter = isset($_POST['filter']) ? sanitize_text_field($_POST['filter']) : '';
        $severity = isset($_POST['severity']) ? sanitize_text_field($_POST['severity']) : '';

        $entries = $this->parse_log_tail($log_path, $lines, $filter, $severity);

        $size = 0;
        $exists = file_exists($log_path);
        if ($exists) $size = filesize($log_path);

        wp_send_json_success([
            'entries' => $entries,
            'exists' => $exists,
            'size' => $exists ? size_format($size) : '0 B',
            'size_bytes' => $size,
        ]);
    }

    public function ajax_clear_log() {
        check_ajax_referer('webgsm_site_audit', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Forbidden');

        $log_path = WP_CONTENT_DIR . '/debug.log';
        if (file_exists($log_path) && is_writable($log_path)) {
            file_put_contents($log_path, '');
            wp_send_json_success(['message' => 'Log golit.']);
        }
        wp_send_json_error('Nu se poate goli logul.');
    }

    private function parse_log_tail($path, $max_lines, $filter, $severity) {
        $entries = [];
        if (!file_exists($path) || !is_readable($path)) return $entries;

        $fsize = filesize($path);
        if ($fsize === 0) return $entries;

        $chunk = min($fsize, $max_lines * 300);
        $fh = fopen($path, 'r');
        if (!$fh) return $entries;

        fseek($fh, max(0, $fsize - $chunk));
        $content = fread($fh, $chunk);
        fclose($fh);

        $raw_lines = explode("\n", $content);
        $raw_lines = array_filter($raw_lines, function($l) { return trim($l) !== ''; });

        $grouped = [];
        $current = '';
        foreach ($raw_lines as $line) {
            if (preg_match('/^\[(\d{2}-[A-Za-z]{3}-\d{4} \d{2}:\d{2}:\d{2} [^\]]+)\]/', $line)) {
                if ($current !== '') $grouped[] = $current;
                $current = $line;
            } else {
                $current .= "\n" . $line;
            }
        }
        if ($current !== '') $grouped[] = $current;

        $grouped = array_reverse($grouped);

        foreach ($grouped as $text) {
            $e = $this->parse_entry($text);
            if ($this->match_entry($e, $filter, $severity)) {
                $entries[] = $e;
            }
            if (count($entries) >= $max_lines) break;
        }

        return $entries;
    }

    private function parse_entry($text) {
        $entry = ['raw' => $text, 'date' => '', 'severity' => 'info', 'message' => '', 'file' => '', 'line' => ''];
        if (preg_match('/^\[([^\]]+)\]/', $text, $m)) $entry['date'] = $m[1];
        if (preg_match('/PHP (Fatal error|Parse error|Warning|Notice|Deprecated):/', $text, $m)) {
            $entry['severity'] = strtolower(str_replace(' ', '_', $m[1]));
        }
        if (preg_match('/in ([^\s]+) on line (\d+)/', $text, $m)) {
            $entry['file'] = $m[1];
            $entry['line'] = $m[2];
        }
        $entry['message'] = preg_replace('/^\[[^\]]+\]\s*/', '', $text);
        return $entry;
    }

    private function match_entry($e, $filter, $severity) {
        if ($filter && stripos($e['message'], $filter) === false && stripos($e['file'], $filter) === false) return false;
        if ($severity && $e['severity'] !== $severity) return false;
        return true;
    }
}
