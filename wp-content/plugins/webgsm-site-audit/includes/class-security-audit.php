<?php
if (!defined('ABSPATH')) exit;

class WebGSM_Site_Audit_Security {

    public function __construct() {
        add_action('wp_ajax_webgsm_audit_security_scan', [$this, 'ajax_scan']);
    }

    public function ajax_scan() {
        check_ajax_referer('webgsm_site_audit', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Forbidden');

        $issues = [];

        $sensitive_files = [
            [ABSPATH . '.env', '.env', 'Poate conține parole, chei API. Șterge sau blochează acces.'],
            [ABSPATH . '.git/config', '.git/config', 'Expune structura repo-ului Git. Blochează .git/ în .htaccess.'],
            [ABSPATH . 'wp-config.php.bak', 'wp-config.php.bak', 'Backup la wp-config expus public. Șterge fișierul.'],
            [ABSPATH . 'wp-config.php.old', 'wp-config.php.old', 'Backup vechi la wp-config. Șterge.'],
            [ABSPATH . 'readme.html', 'readme.html', 'Arată versiunea WP. Șterge fișierul.'],
            [ABSPATH . 'license.txt', 'license.txt', 'Arată versiunea WP. Șterge fișierul.'],
            [WP_CONTENT_DIR . '/debug.log', 'wp-content/debug.log', 'Loguri PHP accesibile public. Blochează în .htaccess.'],
            [ABSPATH . '.htaccess.bak', '.htaccess.bak', 'Backup .htaccess expus. Șterge.'],
            [ABSPATH . 'phpinfo.php', 'phpinfo.php', 'Afișează info PHP! Șterge imediat.'],
            [ABSPATH . 'info.php', 'info.php', 'Posibil phpinfo(). Verifică și șterge.'],
        ];
        foreach ($sensitive_files as $item) {
            if (file_exists($item[0]) && is_readable($item[0])) {
                $issues[] = [
                    'type' => 'exposed_file',
                    'severity' => 'high',
                    'title' => 'Fișier sensibil: ' . $item[1],
                    'path' => $item[1],
                    'fix' => $item[2],
                ];
            }
        }

        if (defined('WP_DEBUG') && WP_DEBUG && (!defined('WP_DEBUG_DISPLAY') || WP_DEBUG_DISPLAY)) {
            $issues[] = [
                'type' => 'debug_display',
                'severity' => 'high',
                'title' => 'WP_DEBUG_DISPLAY activ – erorile PHP sunt vizibile public',
                'path' => 'wp-config.php',
                'fix' => "Adaugă define('WP_DEBUG_DISPLAY', false); în wp-config.php",
            ];
        }

        $admin_users = get_users(['role' => 'administrator', 'number' => 10]);
        foreach ($admin_users as $u) {
            if ($u->user_login === 'admin') {
                $issues[] = [
                    'type' => 'admin_username',
                    'severity' => 'high',
                    'title' => 'Username „admin" – ușor de ghicit pentru brute-force',
                    'path' => 'Users',
                    'fix' => 'Creează alt cont administrator cu alt username și șterge „admin".',
                ];
            }
        }

        if (!is_ssl() && !defined('WP_LOCAL_DEV')) {
            $issues[] = [
                'type' => 'no_ssl',
                'severity' => 'high',
                'title' => 'Site-ul nu folosește HTTPS',
                'path' => '',
                'fix' => 'Instalează certificat SSL (Let\'s Encrypt e gratuit). Forțează HTTPS în .htaccess.',
            ];
        }

        $plugins = get_plugins();
        $updates = get_site_transient('update_plugins');
        if ($updates && !empty($updates->response)) {
            foreach ($updates->response as $file => $data) {
                if (isset($plugins[$file])) {
                    $issues[] = [
                        'type' => 'plugin_update',
                        'severity' => 'medium',
                        'title' => 'Plugin neactualizat: ' . $plugins[$file]['Name'],
                        'path' => $file,
                        'fix' => 'Actualizează din Plugins → Updates.',
                    ];
                }
            }
        }

        $themes = wp_get_themes();
        $theme_updates = get_site_transient('update_themes');
        if ($theme_updates && !empty($theme_updates->response)) {
            foreach ($theme_updates->response as $slug => $data) {
                if (isset($themes[$slug])) {
                    $issues[] = [
                        'type' => 'theme_update',
                        'severity' => 'medium',
                        'title' => 'Temă neactualizată: ' . $themes[$slug]->get('Name'),
                        'path' => $slug,
                        'fix' => 'Actualizează din Appearance → Themes → Update.',
                    ];
                }
            }
        }

        if (file_exists(ABSPATH . 'xmlrpc.php') && !has_filter('xmlrpc_enabled', '__return_false')) {
            $issues[] = [
                'type' => 'xmlrpc',
                'severity' => 'low',
                'title' => 'XML-RPC activ (țintă brute-force)',
                'path' => 'xmlrpc.php',
                'fix' => "Dezactivează: add_filter('xmlrpc_enabled', '__return_false'); în functions.php",
            ];
        }

        $table_prefix = $GLOBALS['table_prefix'];
        if ($table_prefix === 'wp_') {
            $issues[] = [
                'type' => 'default_prefix',
                'severity' => 'low',
                'title' => 'Prefix tabele implicit: wp_',
                'path' => 'wp-config.php',
                'fix' => 'Folosirea prefixului implicit facilitează atacuri SQL injection. Schimbă cu plugin precum Brozzme.',
            ];
        }

        if (!$this->has_security_headers()) {
            $issues[] = [
                'type' => 'no_headers',
                'severity' => 'medium',
                'title' => 'Lipsă headere de securitate (X-Frame-Options, CSP)',
                'path' => '.htaccess',
                'fix' => 'Adaugă în .htaccess: Header set X-Frame-Options "SAMEORIGIN" și Header set X-Content-Type-Options "nosniff"',
            ];
        }

        $inactive = get_plugins();
        $active = get_option('active_plugins', []);
        $inactive_count = 0;
        foreach ($inactive as $file => $p) {
            if (!in_array($file, $active)) $inactive_count++;
        }
        if ($inactive_count > 3) {
            $issues[] = [
                'type' => 'inactive_plugins',
                'severity' => 'low',
                'title' => "$inactive_count plugin-uri inactive – risc de securitate",
                'path' => 'plugins',
                'fix' => 'Șterge plugin-urile inactive. Ele pot fi exploatate chiar dezactivate.',
            ];
        }

        wp_send_json_success(['issues' => $issues, 'count' => count($issues)]);
    }

    private function has_security_headers() {
        $htaccess = ABSPATH . '.htaccess';
        if (!file_exists($htaccess)) return false;
        $content = file_get_contents($htaccess);
        return (stripos($content, 'X-Frame-Options') !== false || stripos($content, 'Content-Security-Policy') !== false);
    }
}
