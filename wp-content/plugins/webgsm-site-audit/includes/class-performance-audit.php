<?php
if (!defined('ABSPATH')) exit;

class WebGSM_Site_Audit_Performance {

    /** @var string */
    const SLOW_LOG_FILE = 'webgsm-perf-audit.log';

    public function __construct() {
        add_action('wp_ajax_webgsm_audit_performance_scan', [$this, 'ajax_scan']);
        add_action('wp_ajax_webgsm_audit_performance_repair', [$this, 'ajax_repair']);
        add_action('shutdown', [$this, 'maybe_log_slow_request'], 99999);
    }

    /**
     * Jurnal requesturi lente: activ din Setări Site Audit sau define('WEBGSM_PERF_AUDIT', true).
     */
    public function maybe_log_slow_request() {
        $settings = WebGSM_Site_Audit_Settings::get();
        $enabled    = !empty($settings['slow_request_log_enabled'])
            || (defined('WEBGSM_PERF_AUDIT') && WEBGSM_PERF_AUDIT);

        if (!$enabled) {
            return;
        }

        if (defined('DOING_AJAX') && DOING_AJAX && empty($settings['slow_request_log_ajax'])) {
            return;
        }

        if (defined('DOING_CRON') && DOING_CRON) {
            return;
        }

        if (php_sapi_name() === 'cli') {
            return;
        }

        $threshold = isset($settings['slow_request_threshold_seconds'])
            ? (float) $settings['slow_request_threshold_seconds']
            : 2.0;
        $threshold = max(0.5, min(30.0, $threshold));

        $duration = (float) timer_stop(0, 6);
        if ($duration < $threshold) {
            return;
        }

        $queries = function_exists('get_num_queries') ? (int) get_num_queries() : 0;
        $mem_mb  = function_exists('memory_get_peak_usage')
            ? round(memory_get_peak_usage(true) / 1048576, 2)
            : 0.0;

        $uri    = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
        $method = isset($_SERVER['REQUEST_METHOD']) ? (string) $_SERVER['REQUEST_METHOD'] : '';
        $host   = isset($_SERVER['HTTP_HOST']) ? (string) $_SERVER['HTTP_HOST'] : '';

        $ctx = $this->build_slow_request_context_line();

        $line = sprintf(
            "[%s] dur=%.3fs | queries=%d | mem=%sMB | %s %s%s | admin=%s | uid=%d | %s\n",
            gmdate('Y-m-d H:i:s'),
            $duration,
            $queries,
            $mem_mb,
            $method,
            $host,
            $uri,
            is_admin() ? '1' : '0',
            get_current_user_id(),
            $ctx
        );

        $path = WP_CONTENT_DIR . '/' . self::SLOW_LOG_FILE;

        if (file_exists($path) && filesize($path) > 5242880) {
            $chunk = file_get_contents($path, false, null, -2097152);
            if ($chunk !== false) {
                file_put_contents($path, $chunk, LOCK_EX);
            }
        }

        @file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
    }

    /**
     * Context diagnostic pe o linie (grep / ticket hosting).
     * Nu înlocuiește Query Monitor pentru „care SQL exact”; arată pluginuri încărcate, Cloudflare, tip request.
     */
    private function build_slow_request_context_line() {
        global $wpdb;

        $parts = [];

        $parts[] = 'wp=' . (defined('WP_VERSION') ? WP_VERSION : '?');

        $oc = file_exists(WP_CONTENT_DIR . '/object-cache.php') ? '1' : '0';
        $oc_ext = (function_exists('wp_using_ext_object_cache') && wp_using_ext_object_cache()) ? '1' : '0';
        $parts[] = 'objcache_file=' . $oc . ' objcache_ext=' . $oc_ext;

        $cf_ray = isset($_SERVER['HTTP_CF_RAY']) ? (string) $_SERVER['HTTP_CF_RAY'] : '';
        $cf_country = isset($_SERVER['HTTP_CF_IPCOUNTRY']) ? (string) $_SERVER['HTTP_CF_IPCOUNTRY'] : '';
        if ($cf_ray !== '') {
            $parts[] = 'cf_ray=' . $cf_ray;
            if ($cf_country !== '') {
                $parts[] = 'cf_country=' . $cf_country;
            }
        } else {
            $parts[] = 'cf_proxy=0';
        }

        $ajax = (defined('DOING_AJAX') && DOING_AJAX) ? '1' : '0';
        $rest = (defined('REST_REQUEST') && REST_REQUEST) ? '1' : '0';
        $xmlrpc = (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) ? '1' : '0';
        $parts[] = 'ajax=' . $ajax . ' rest=' . $rest . ' xmlrpc=' . $xmlrpc;

        $action = isset($_REQUEST['action']) ? sanitize_key((string) wp_unslash($_REQUEST['action'])) : '';
        if ($action !== '') {
            $parts[] = 'req_action=' . $action;
        }

        if (isset($_SERVER['REMOTE_ADDR'])) {
            $parts[] = 'ip=' . preg_replace('/[^0-9a-fA-F:.,]/', '', (string) $_SERVER['REMOTE_ADDR']);
        }

        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
        if ($ua !== '') {
            $ua_short = substr(preg_replace('/\s+/', ' ', $ua), 0, 120);
            $parts[] = 'ua=' . $ua_short;
        }

        $active = (array) get_option('active_plugins', []);
        $mu_slugs = [];
        if (defined('WPMU_PLUGIN_DIR') && is_dir(WPMU_PLUGIN_DIR)) {
            $mu_files = glob(WPMU_PLUGIN_DIR . '/*.php');
            if (is_array($mu_files)) {
                foreach ($mu_files as $mu_file) {
                    $mu_slugs[] = basename((string) $mu_file, '.php');
                }
            }
        }
        sort($active);
        $slugs = [];
        foreach ($active as $p) {
            $p = (string) $p;
            if ($p === '') {
                continue;
            }
            $slugs[] = dirname($p) === '.' ? $p : dirname($p);
        }
        $slugs = array_values(array_unique($slugs));
        sort($slugs);
        $parts[] = 'plugins_n=' . count($slugs);

        $list = implode(',', $slugs);
        $max  = 1800;
        if (strlen($list) > $max) {
            $list = substr($list, 0, $max) . '...(trunc)';
        }
        $parts[] = 'plugins=' . $list;

        if (!empty($mu_slugs)) {
            $parts[] = 'mu=' . implode(',', array_slice($mu_slugs, 0, 20));
        }

        if (is_object($wpdb) && isset($wpdb->num_queries)) {
            $parts[] = 'wpdb_count=' . (int) $wpdb->num_queries;
        }

        if (class_exists('QM', false)) {
            $parts[] = 'query_monitor=1';
        }

        return implode(' | ', $parts);
    }

    public function ajax_scan() {
        check_ajax_referer('webgsm_site_audit', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Forbidden');

        $issues = [];
        $upload_dir = wp_upload_dir();
        $upload_path = $upload_dir['basedir'];

        $large_images = $this->find_large_images($upload_path, 500);
        foreach ($large_images as $img) {
            $issues[] = [
                'type' => 'large_image',
                'severity' => 'medium',
                'title' => 'Imagine mare: ' . $img['name'] . ' (' . $img['size_formatted'] . ')',
                'path' => $img['path'],
                'fix' => 'Comprimă imaginea sau convertește în WebP. Plugin recomandat: ShortPixel sau Imagify.',
            ];
        }

        $transients = $this->count_transients();
        if ($transients > 500) {
            $issues[] = [
                'type' => 'transients',
                'severity' => 'low',
                'title' => "Multe transiente în DB: $transients",
                'path' => 'options',
                'fix' => 'Golește transientele expirate. Poți folosi WP-CLI: wp transient delete --expired',
            ];
        }

        global $wpdb;
        $rev_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'revision'");
        if ($rev_count > 500) {
            $issues[] = [
                'type' => 'revisions',
                'severity' => 'low',
                'title' => "Multe revizii: $rev_count",
                'path' => 'posts',
                'fix' => "Adaugă define('WP_POST_REVISIONS', 5); în wp-config.php pentru limită.",
            ];
        }

        $autoload_size = $this->get_autoload_size();
        if ($autoload_size > 800000) {
            $issues[] = [
                'type' => 'autoload',
                'severity' => 'medium',
                'title' => 'Autoload options mare: ' . size_format($autoload_size),
                'path' => 'options',
                'fix' => 'Verifică opțiunile autoloaded mari și dezactivează autoload unde nu e nevoie.',
            ];
        }

        $active_plugins = get_option('active_plugins', []);
        if (count($active_plugins) > 30) {
            $issues[] = [
                'type' => 'plugins',
                'severity' => 'low',
                'title' => 'Multe plugin-uri active: ' . count($active_plugins),
                'path' => '',
                'fix' => 'Dezactivează plugin-urile nefolosite. Fiecare plugin adaugă overhead.',
            ];
        }

        $db_size = $this->get_db_size();
        if ($db_size > 500 * 1024 * 1024) {
            $issues[] = [
                'type' => 'db_size',
                'severity' => 'medium',
                'title' => 'Baza de date mare: ' . size_format($db_size),
                'path' => 'database',
                'fix' => 'Curăță revizii, spam, transiente. Optimizează tabelele DB.',
            ];
        }

        if (!$this->has_object_cache()) {
            $issues[] = [
                'type' => 'no_cache',
                'severity' => 'low',
                'title' => 'Lipsă object cache (Redis/Memcached)',
                'path' => '',
                'fix' => 'Pe LiteSpeed: Object Cache ON. Altfel Redis, dacă hostingul îl oferă.',
            ];
        }

        $issues = array_merge($issues, $this->litespeed_and_probe_issues());

        wp_send_json_success(['issues' => $issues, 'count' => count($issues)]);
    }

    /**
     * Probleme LiteSpeed + probe DB, cu acțiune de reparare unde e sigur.
     *
     * @return array<int, array<string, string>>
     */
    private function litespeed_and_probe_issues(): array {
        $issues = [];
        $health = $this->litespeed_health();
        if ($health) {
            foreach ($health->run_audit() as $row) {
                $level = $row['level'] ?? 'info';
                if ($level === 'ok' || $level === 'info') {
                    continue;
                }
                $repair = '';
                if (in_array($row['id'] ?? '', ['cache_off', 'cache_priv_on', 'cache_rest_on', 'ttl_pub_high', 'serve_stale', 'esi_on', 'cache_exc', 'vary_administrator', 'vary_b2b_customer', 'vary_customer', 'vary_client_b2b'], true)
                    || strpos((string) ($row['id'] ?? ''), 'vary_') === 0
                    || in_array($row['id'] ?? '', ['cache_off', 'cache_exc'], true)) {
                    $repair = 'litespeed_preset';
                }
                $issues[] = [
                    'type'     => 'litespeed',
                    'severity' => $level === 'error' ? 'high' : 'medium',
                    'title'    => $row['message'],
                    'path'     => 'litespeed',
                    'fix'      => $row['fix'] ?? 'Aplică presetul WebGSM din butonul de mai sus.',
                    'repair'   => $repair,
                ];
            }
        } else {
            $issues[] = [
                'type'     => 'litespeed',
                'severity' => 'info',
                'title'    => 'Modulul LiteSpeed WebGSM nu e încărcat (plugin webgsm-tools).',
                'fix'      => 'Activează WebGSM Tools ca să poți aplica presetul de cache din acest tab.',
            ];
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            $issues[] = [
                'type'     => 'wp_debug',
                'severity' => 'high',
                'title'    => 'WP_DEBUG este ON',
                'fix'      => 'În wp-config.php pe live: define(\'WP_DEBUG\', false); — nu se poate opri din admin în siguranță.',
            ];
        }

        return $issues;
    }

    /**
     * @return WebGSM_Tools_LiteSpeed_Health|null
     */
    private function litespeed_health() {
        if (!class_exists('WebGSM_Tools_LiteSpeed_Health', false)) {
            $file = WP_PLUGIN_DIR . '/webgsm-tools/includes/class-litespeed-health.php';
            if (is_readable($file)) {
                require_once $file;
            }
        }
        if (!class_exists('WebGSM_Tools_LiteSpeed_Health', false)) {
            return null;
        }

        return new WebGSM_Tools_LiteSpeed_Health();
    }

    public function ajax_repair(): void {
        check_ajax_referer('webgsm_site_audit', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Nu ai permisiuni.']);
        }
        $action = isset($_POST['repair']) ? sanitize_key(wp_unslash($_POST['repair'])) : '';

        if ($action === 'litespeed_preset') {
            $health = $this->litespeed_health();
            if (!$health) {
                wp_send_json_error(['message' => 'Lipsește webgsm-tools (clasa LiteSpeed).']);
            }
            $result = $health->apply_preset($health->get_webgsm_preset());
            if (empty($result['ok'])) {
                wp_send_json_error(['message' => $result['message'] ?? 'Preset eșuat.']);
            }
            wp_send_json_success(['message' => $result['message']]);
        }

        if ($action === 'litespeed_purge') {
            $health = $this->litespeed_health();
            if (!$health) {
                wp_send_json_error(['message' => 'Lipsește webgsm-tools.']);
            }
            $ok = $health->purge_all();
            wp_send_json_success([
                'message' => $ok ? 'Purge All trimis către LiteSpeed.' : 'Hook purge indisponibil — folosește meniul LiteSpeed → Purge.',
            ]);
        }

        if ($action === 'expired_transients') {
            $deleted = 0;
            if (function_exists('delete_expired_transients')) {
                delete_expired_transients(true);
                $deleted = 1;
            }
            wp_send_json_success([
                'message' => $deleted ? 'Transientele expirate au fost șterse.' : 'Funcția delete_expired_transients nu e disponibilă.',
            ]);
        }

        wp_send_json_error(['message' => 'Acțiune de reparare necunoscută.']);
    }

    private function find_large_images($path, $max_kb, $limit = 20) {
        $found = [];
        if (!is_dir($path)) return $found;
        $checked = 0;
        $max_check = 2000;
        try {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS));
            $ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            foreach ($iterator as $file) {
                if (++$checked > $max_check) break;
                if (!$file->isFile()) continue;
                $ext_lower = strtolower($file->getExtension());
                if (!in_array($ext_lower, $ext)) continue;
                $size = $file->getSize();
                if ($size > $max_kb * 1024) {
                    $found[] = [
                        'path' => str_replace(ABSPATH, '', $file->getPathname()),
                        'name' => $file->getFilename(),
                        'size' => $size,
                        'size_formatted' => size_format($size),
                    ];
                    if (count($found) >= $limit) break;
                }
            }
        } catch (Exception $e) {
            return $found;
        }
        return $found;
    }

    private function count_transients() {
        global $wpdb;
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'");
    }

    private function get_autoload_size() {
        global $wpdb;
        return (int) $wpdb->get_var("SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} WHERE autoload = 'yes'");
    }

    private function get_db_size() {
        global $wpdb;
        $result = $wpdb->get_var("SELECT SUM(data_length + index_length) FROM information_schema.tables WHERE table_schema = DATABASE()");
        return (int) $result;
    }

    private function has_object_cache() {
        return file_exists(WP_CONTENT_DIR . '/object-cache.php');
    }
}
