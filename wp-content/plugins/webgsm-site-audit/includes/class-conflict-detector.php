<?php
if (!defined('ABSPATH')) exit;

class WebGSM_Site_Audit_Conflict_Detector {

    public function __construct() {
        add_action('wp_ajax_webgsm_audit_conflict_scan', [$this, 'ajax_scan']);
    }

    public function ajax_scan() {
        check_ajax_referer('webgsm_site_audit', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Forbidden');

        $issues = [];

        $this->check_duplicate_scripts($issues);
        $this->check_duplicate_styles($issues);
        $this->check_plugin_conflicts($issues);
        $this->check_jquery_issues($issues);
        $this->check_broken_hooks($issues);
        $this->check_cron_health($issues);
        $this->check_rest_api($issues);

        wp_send_json_success(['issues' => $issues, 'count' => count($issues)]);
    }

    private function check_duplicate_scripts(&$issues) {
        $scripts = wp_scripts();
        if (!$scripts || !isset($scripts->registered)) return;

        $by_src = [];
        foreach ($scripts->registered as $handle => $dep) {
            if (empty($dep->src)) continue;
            $src = $dep->src;
            if (strpos($src, '?') !== false) $src = strtok($src, '?');
            $key = basename($src);
            if (!isset($by_src[$key])) $by_src[$key] = [];
            $by_src[$key][] = $handle;
        }

        foreach ($by_src as $file => $handles) {
            if (count($handles) > 1) {
                $issues[] = [
                    'type' => 'duplicate_script',
                    'severity' => 'medium',
                    'title' => "Script duplicat: $file",
                    'detail' => 'Handle-uri: ' . implode(', ', $handles),
                    'fix' => 'Dezactivează unul din plugin-urile care încarcă același script. Poate cauza conflicte JS.',
                ];
            }
        }
    }

    private function check_duplicate_styles(&$issues) {
        $styles = wp_styles();
        if (!$styles || !isset($styles->registered)) return;

        $by_src = [];
        foreach ($styles->registered as $handle => $dep) {
            if (empty($dep->src)) continue;
            $src = $dep->src;
            if (strpos($src, '?') !== false) $src = strtok($src, '?');
            $key = basename($src);
            if (!isset($by_src[$key])) $by_src[$key] = [];
            $by_src[$key][] = $handle;
        }

        foreach ($by_src as $file => $handles) {
            if (count($handles) > 1) {
                $issues[] = [
                    'type' => 'duplicate_style',
                    'severity' => 'low',
                    'title' => "CSS duplicat: $file",
                    'detail' => 'Handle-uri: ' . implode(', ', $handles),
                    'fix' => 'Două plugin-uri încarcă același CSS. Dezactivează handle-ul duplicat.',
                ];
            }
        }
    }

    private function check_plugin_conflicts(&$issues) {
        $known_conflicts = [
            ['yoast' => 'wordpress-seo/wp-seo.php', 'rankmath' => 'seo-by-rank-math/rank-math.php', 'msg' => 'Yoast SEO + Rank Math active simultan – conflicte SEO severe'],
            ['w3tc' => 'w3-total-cache/w3-total-cache.php', 'wpsc' => 'wp-super-cache/wp-cache.php', 'msg' => 'W3 Total Cache + WP Super Cache – conflicte de caching'],
            ['wordfence' => 'wordfence/wordfence.php', 'sucuri' => 'sucuri-scanner/sucuri.php', 'msg' => 'Wordfence + Sucuri – două firewall-uri active pot cauza probleme'],
            ['classic' => 'classic-editor/classic-editor.php', 'disable_gutenberg' => 'disable-gutenberg/disable-gutenberg.php', 'msg' => 'Classic Editor + Disable Gutenberg – redundanță'],
            ['elementor' => 'elementor/elementor.php', 'beaver' => 'beaver-builder-lite-version/fl-builder.php', 'msg' => 'Elementor + Beaver Builder – două page builders pot cauza conflicte'],
        ];

        $active = get_option('active_plugins', []);

        foreach ($known_conflicts as $pair) {
            $msg = $pair['msg'];
            unset($pair['msg']);
            $found = [];
            foreach ($pair as $key => $file) {
                if (in_array($file, $active)) $found[] = $key;
            }
            if (count($found) >= 2) {
                $issues[] = [
                    'type' => 'plugin_conflict',
                    'severity' => 'high',
                    'title' => $msg,
                    'detail' => 'Plugin-uri în conflict: ' . implode(', ', $found),
                    'fix' => 'Dezactivează unul din cele două plugin-uri. Folosirea simultană cauzează conflicte.',
                ];
            }
        }

        $seo_plugins = ['wordpress-seo/wp-seo.php', 'seo-by-rank-math/rank-math.php', 'all-in-one-seo-pack/all_in_one_seo_pack.php', 'flavor/flavor.php'];
        $active_seo = array_filter($seo_plugins, function($p) use ($active) { return in_array($p, $active); });
        if (count($active_seo) > 1) {
            $issues[] = [
                'type' => 'multi_seo',
                'severity' => 'high',
                'title' => count($active_seo) . ' plugin-uri SEO active simultan',
                'detail' => 'Mai multe plugin-uri SEO creează meta tag-uri duplicat.',
                'fix' => 'Păstrează un singur plugin SEO și dezactivează restul.',
            ];
        }
    }

    private function check_jquery_issues(&$issues) {
        $scripts = wp_scripts();
        if (!$scripts || !isset($scripts->registered)) return;

        $jquery_versions = [];
        foreach ($scripts->registered as $handle => $dep) {
            if (empty($dep->src)) continue;
            $src = is_string($dep->src) ? $dep->src : '';
            if (strpos($src, 'jquery') !== false && strpos($src, 'jquery-migrate') === false) {
                $jquery_versions[] = $handle . ' → ' . $src;
            }
        }

        if (count($jquery_versions) > 2) {
            $issues[] = [
                'type' => 'multi_jquery',
                'severity' => 'high',
                'title' => 'Multiple versiuni jQuery detectate',
                'detail' => implode("\n", $jquery_versions),
                'fix' => 'Folosește o singură versiune jQuery. Elimină încărcarea manuală din teme/pluginuri.',
            ];
        }
    }

    private function check_broken_hooks(&$issues) {
        global $wp_filter;
        $broken = [];

        $critical_hooks = ['wp_head', 'wp_footer', 'init', 'wp_enqueue_scripts', 'template_redirect'];
        foreach ($critical_hooks as $hook) {
            if (!isset($wp_filter[$hook]) || empty($wp_filter[$hook]->callbacks)) {
                $broken[] = $hook;
            }
        }

        if (!empty($broken)) {
            $issues[] = [
                'type' => 'missing_hooks',
                'severity' => 'high',
                'title' => 'Hookuri critice fără callback-uri: ' . implode(', ', $broken),
                'detail' => 'Hookurile critice WordPress nu au niciun callback atașat.',
                'fix' => 'Un plugin/temă poate elimina hookuri importante. Verifică funcțiile remove_action/remove_all_actions.',
            ];
        }

        $removed_defaults = [];
        if (!has_action('wp_head', 'wp_enqueue_scripts')) {
            // This is normal, but check for others
        }
        if (!has_action('wp_head', 'wp_generator')) {
            $issues[] = [
                'type' => 'removed_generator',
                'severity' => 'info',
                'title' => 'wp_generator eliminat din wp_head',
                'detail' => 'Bine – versiunea WP nu mai e vizibilă public.',
                'fix' => 'Nicio acțiune necesară – aceasta e o practică de securitate bună.',
            ];
        }
    }

    private function check_cron_health(&$issues) {
        $crons = _get_cron_array();
        if (!is_array($crons)) {
            $issues[] = [
                'type' => 'cron_broken',
                'severity' => 'high',
                'title' => 'WP-Cron nu funcționează',
                'detail' => 'Tabelul cron e gol sau corupt.',
                'fix' => "Verifică dacă DISABLE_WP_CRON e setat în wp-config.php. Dacă da, configurează un cron real pe server.",
            ];
            return;
        }

        $overdue = 0;
        $now = time();
        foreach ($crons as $ts => $hooks) {
            if (!is_numeric($ts)) continue;
            if ($ts < $now - 3600) $overdue++;
        }

        if ($overdue > 10) {
            $issues[] = [
                'type' => 'cron_overdue',
                'severity' => 'medium',
                'title' => "$overdue task-uri cron întârziate (>1h)",
                'detail' => 'Task-urile cron nu se execută la timp.',
                'fix' => 'Configurează un cron real pe server sau instalează WP Crontrol pentru management.',
            ];
        }

        if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) {
            $issues[] = [
                'type' => 'cron_disabled',
                'severity' => 'low',
                'title' => 'WP-Cron dezactivat (DISABLE_WP_CRON = true)',
                'detail' => 'Asigură-te că un cron de sistem e configurat.',
                'fix' => 'Adaugă în crontab: */5 * * * * wget -q -O - ' . home_url('/wp-cron.php') . ' > /dev/null 2>&1',
            ];
        }
    }

    private function check_rest_api(&$issues) {
        $rest_url = rest_url('wp/v2/');
        $response = wp_remote_get($rest_url, ['timeout' => 10, 'sslverify' => false]);

        if (is_wp_error($response)) {
            $issues[] = [
                'type' => 'rest_broken',
                'severity' => 'high',
                'title' => 'REST API inaccesibil',
                'detail' => $response->get_error_message(),
                'fix' => 'REST API e necesar pentru editor, plugin-uri moderne. Verifică .htaccess și plugin-uri de securitate.',
            ];
            return;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            $issues[] = [
                'type' => 'rest_error',
                'severity' => 'medium',
                'title' => "REST API returnează HTTP $code",
                'detail' => 'REST API nu funcționează corect.',
                'fix' => 'Verifică permalink-urile (salvează din nou) și .htaccess.',
            ];
        }
    }
}
