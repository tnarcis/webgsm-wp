<?php
if (!defined('ABSPATH')) exit;

class WebGSM_Site_Audit_Performance {

    public function __construct() {
        add_action('wp_ajax_webgsm_audit_performance_scan', [$this, 'ajax_scan']);
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
                'fix' => 'Instalează Redis Object Cache pentru performanță mai bună.',
            ];
        }

        wp_send_json_success(['issues' => $issues, 'count' => count($issues)]);
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
