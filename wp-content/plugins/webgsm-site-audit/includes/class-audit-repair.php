<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Reparații one-click din Site Audit (doar ce e sigur din wp-admin).
 */
class WebGSM_Site_Audit_Repair {

    /**
     * @return array{ok:bool,message:string}
     */
    public static function run(string $action): array {
        switch ($action) {
            case 'security_wp_fingerprint':
                return self::delete_wp_fingerprint_files();
            case 'security_debug_log':
                return self::clear_debug_log();
            case 'security_htaccess_hardening':
                return self::append_htaccess_hardening();
            case 'seo_enable_indexing':
                return self::enable_search_indexing();
            case 'deactivate_query_monitor':
                return self::deactivate_query_monitor();
            default:
                return array('ok' => false, 'message' => 'Acțiune de reparare necunoscută.');
        }
    }

    /**
     * @return array{ok:bool,message:string}
     */
    private static function delete_wp_fingerprint_files(): array {
        $names = array('readme.html', 'license.txt');
        $removed = array();
        $failed = array();
        foreach ($names as $name) {
            $path = ABSPATH . $name;
            if (!file_exists($path)) {
                continue;
            }
            if (@unlink($path)) {
                $removed[] = $name;
            } else {
                $failed[] = $name;
            }
        }
        if ($failed) {
            return array(
                'ok'      => false,
                'message' => 'Nu am putut șterge: ' . implode(', ', $failed) . ' (permisiuni FTP/cPanel).',
            );
        }
        if (!$removed) {
            return array('ok' => true, 'message' => 'readme.html / license.txt — deja absente.');
        }

        return array('ok' => true, 'message' => 'Șters: ' . implode(', ', $removed) . '.');
    }

    /**
     * @return array{ok:bool,message:string}
     */
    private static function clear_debug_log(): array {
        $path = WP_CONTENT_DIR . '/debug.log';
        $parts = array();
        if (file_exists($path)) {
            if (@unlink($path) || @file_put_contents($path, '') !== false) {
                $parts[] = 'debug.log golit/șters';
            } else {
                return array('ok' => false, 'message' => 'Nu am putut șterge wp-content/debug.log — șterge manual sau via FTP.');
            }
        } else {
            $parts[] = 'debug.log absent';
        }
        $ht = self::append_htaccess_hardening(true);
        if ($ht['ok'] && strpos($ht['message'], 'adăugat') !== false) {
            $parts[] = 'blocare .htaccess pentru debug.log';
        }

        return array('ok' => true, 'message' => implode('; ', $parts) . '.');
    }

    /**
     * @return array{ok:bool,message:string}
     */
    private static function append_htaccess_hardening(bool $debug_only = false): array {
        $file = ABSPATH . '.htaccess';
        if (!file_exists($file) || !is_writable($file)) {
            return array(
                'ok'      => false,
                'message' => '.htaccess lipsește sau nu e inscriptibil — adaugă regulile manual (Site Audit → Securitate).',
            );
        }
        $content = (string) file_get_contents($file);
        $marker = '# BEGIN WebGSM hardening';
        if (
            stripos($content, 'X-Frame-Options') !== false
            || strpos($content, $marker) !== false
            || strpos($content, '# BEGIN WebGSM Hardening') !== false
        ) {
            return array('ok' => true, 'message' => 'Reguli WebGSM hardening — deja prezente în .htaccess.');
        }

        $block = "\n{$marker}\n";
        if ($debug_only) {
            $block .= "<Files debug.log>\n  Require all denied\n</Files>\n";
        } else {
            $block .= "<Files debug.log>\n  Require all denied\n</Files>\n";
            $block .= "<IfModule mod_headers.c>\n";
            $block .= "  Header set X-Frame-Options \"SAMEORIGIN\"\n";
            $block .= "  Header set X-Content-Type-Options \"nosniff\"\n";
            $block .= "  Header set Referrer-Policy \"strict-origin-when-cross-origin\"\n";
            $block .= "</IfModule>\n";
        }
        $block .= "# END WebGSM hardening\n";

        if (@file_put_contents($file, $content . $block) === false) {
            return array('ok' => false, 'message' => 'Nu am putut scrie în .htaccess.');
        }

        return array('ok' => true, 'message' => 'Reguli WebGSM hardening adăugate în .htaccess.');
    }

    /**
     * @return array{ok:bool,message:string}
     */
    private static function enable_search_indexing(): array {
        update_option('blog_public', '1');

        return array(
            'ok'      => true,
            'message' => 'Indexare activată (blog_public=1). Verifică Setări → Citire.',
        );
    }

    /**
     * @return array{ok:bool,message:string}
     */
    private static function deactivate_query_monitor(): array {
        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $plugin = 'query-monitor/query-monitor.php';
        if (!is_plugin_active($plugin)) {
            return array('ok' => true, 'message' => 'Query Monitor nu e activ.');
        }
        deactivate_plugins($plugin, true);

        return array('ok' => true, 'message' => 'Query Monitor dezactivat pe acest site.');
    }
}
