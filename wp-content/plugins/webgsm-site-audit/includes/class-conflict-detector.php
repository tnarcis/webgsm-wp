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

        $this->check_plugin_conflicts($issues);
        $this->check_seo_conflicts($issues);
        $this->check_jquery_issues($issues);
        $this->check_enqueued_assets($issues);
        $this->check_broken_hooks($issues);
        $this->check_cron_health($issues);
        $this->check_rest_api($issues);
        $this->check_php_errors_in_theme($issues);

        wp_send_json_success(['issues' => $issues, 'count' => count($issues)]);
    }

    private function check_plugin_conflicts(&$issues) {
        $known_conflicts = [
            [
                'plugins' => ['w3-total-cache/w3-total-cache.php', 'wp-super-cache/wp-cache.php'],
                'msg' => 'W3 Total Cache + WP Super Cache – conflicte de caching',
            ],
            [
                'plugins' => ['wordfence/wordfence.php', 'sucuri-scanner/sucuri.php'],
                'msg' => 'Wordfence + Sucuri – două firewall-uri pot cauza probleme',
            ],
            [
                'plugins' => ['classic-editor/classic-editor.php', 'disable-gutenberg/disable-gutenberg.php'],
                'msg' => 'Classic Editor + Disable Gutenberg – redundanță',
            ],
            [
                'plugins' => ['elementor/elementor.php', 'beaver-builder-lite-version/fl-builder.php'],
                'msg' => 'Elementor + Beaver Builder – două page builders cauzează conflicte',
            ],
            [
                'plugins' => ['jetpack/jetpack.php', 'flavor/flavor.php'],
                'msg' => 'Jetpack + Flavor – funcționalități duplicate',
            ],
        ];

        $active = get_option('active_plugins', []);

        foreach ($known_conflicts as $pair) {
            $found = [];
            foreach ($pair['plugins'] as $file) {
                if (in_array($file, $active)) $found[] = $file;
            }
            if (count($found) >= 2) {
                $issues[] = [
                    'type' => 'plugin_conflict',
                    'severity' => 'high',
                    'title' => $pair['msg'],
                    'detail' => 'Ambele active simultan.',
                    'fix' => 'Dezactivează unul din cele două. Folosirea simultană cauzează conflicte.',
                ];
            }
        }
    }

    private function check_seo_conflicts(&$issues) {
        $seo_plugins = [
            'wordpress-seo/wp-seo.php' => 'Yoast SEO',
            'seo-by-rank-math/rank-math.php' => 'Rank Math',
            'all-in-one-seo-pack/all_in_one_seo_pack.php' => 'All in One SEO',
            'flavor/flavor.php' => 'Flavor',
            'the-seo-framework/the-seo-framework.php' => 'The SEO Framework',
        ];

        $active = get_option('active_plugins', []);
        $active_seo = [];
        foreach ($seo_plugins as $file => $name) {
            if (in_array($file, $active)) $active_seo[$file] = $name;
        }

        if (count($active_seo) > 1) {
            $issues[] = [
                'type' => 'multi_seo',
                'severity' => 'high',
                'title' => count($active_seo) . ' plugin-uri SEO active simultan: ' . implode(', ', $active_seo),
                'detail' => 'Mai multe plugin-uri SEO creează meta tag-uri duplicat (title, description, canonical).',
                'fix' => 'Păstrează un singur plugin SEO și dezactivează restul.',
            ];
        }
    }

    private function check_jquery_issues(&$issues) {
        global $wp_scripts;
        if (!is_object($wp_scripts) || empty($wp_scripts->registered)) return;

        $jquery_handles = [];
        foreach ($wp_scripts->registered as $handle => $dep) {
            if (empty($dep->src) || !is_string($dep->src)) continue;
            if (strpos(strtolower($dep->src), 'jquery') !== false && strpos(strtolower($dep->src), 'jquery-migrate') === false && strpos(strtolower($dep->src), 'jquery-ui') === false) {
                $jquery_handles[] = $handle;
            }
        }

        if (count($jquery_handles) > 2) {
            $issues[] = [
                'type' => 'multi_jquery',
                'severity' => 'high',
                'title' => 'Multiple încărcări jQuery detectate (' . count($jquery_handles) . ')',
                'detail' => 'Handle-uri: ' . implode(', ', $jquery_handles),
                'fix' => 'Folosește o singură versiune jQuery. Verifică tema și plugin-urile care încarcă jQuery manual.',
            ];
        }
    }

    private function check_enqueued_assets(&$issues) {
        global $wp_scripts, $wp_styles;

        $script_count = 0;
        $style_count = 0;

        if (is_object($wp_scripts) && !empty($wp_scripts->registered)) {
            $script_count = count($wp_scripts->registered);

            $by_src = [];
            foreach ($wp_scripts->registered as $handle => $dep) {
                if (empty($dep->src) || !is_string($dep->src)) continue;
                $base = basename(strtok($dep->src, '?'));
                if (strlen($base) < 3) continue;
                if (!isset($by_src[$base])) $by_src[$base] = [];
                $by_src[$base][] = $handle;
            }
            foreach ($by_src as $file => $handles) {
                if (count($handles) > 1) {
                    $issues[] = [
                        'type' => 'duplicate_script',
                        'severity' => 'medium',
                        'title' => "JS duplicat: $file",
                        'detail' => 'Handle-uri: ' . implode(', ', $handles),
                        'fix' => 'Două plugin-uri încarcă același script. Dezactivează duplicatul.',
                    ];
                }
            }
        }

        if (is_object($wp_styles) && !empty($wp_styles->registered)) {
            $style_count = count($wp_styles->registered);

            $by_src = [];
            foreach ($wp_styles->registered as $handle => $dep) {
                if (empty($dep->src) || !is_string($dep->src)) continue;
                $base = basename(strtok($dep->src, '?'));
                if (strlen($base) < 3) continue;
                if (!isset($by_src[$base])) $by_src[$base] = [];
                $by_src[$base][] = $handle;
            }
            foreach ($by_src as $file => $handles) {
                if (count($handles) > 1) {
                    $issues[] = [
                        'type' => 'duplicate_style',
                        'severity' => 'low',
                        'title' => "CSS duplicat: $file",
                        'detail' => 'Handle-uri: ' . implode(', ', $handles),
                        'fix' => 'Două plugin-uri încarcă același CSS.',
                    ];
                }
            }
        }

        $issues[] = [
            'type' => 'asset_count',
            'severity' => 'info',
            'title' => "Total assets înregistrate: $script_count JS, $style_count CSS",
            'detail' => 'Aceste numere sunt din contextul admin. Frontend-ul poate avea mai multe.',
            'fix' => 'Dacă sunt peste 30 JS + 30 CSS, ia în considerare optimizarea cu un plugin de minificare/combinare.',
        ];
    }

    private function check_broken_hooks(&$issues) {
        global $wp_filter;

        $critical_hooks = ['wp_head', 'wp_footer', 'init', 'wp_enqueue_scripts'];
        $missing = [];
        foreach ($critical_hooks as $hook) {
            if (!isset($wp_filter[$hook]) || empty($wp_filter[$hook]->callbacks)) {
                $missing[] = $hook;
            }
        }

        if (!empty($missing)) {
            $issues[] = [
                'type' => 'missing_hooks',
                'severity' => 'high',
                'title' => 'Hookuri critice fără callback-uri: ' . implode(', ', $missing),
                'detail' => 'Un plugin/temă poate elimina hookuri importante cu remove_all_actions().',
                'fix' => 'Verifică tema și plugin-urile pentru remove_action/remove_all_actions pe aceste hookuri.',
            ];
        }

        $removed_important = [];
        if (!has_action('wp_head', 'wp_resource_hints')) {
            $removed_important[] = 'wp_resource_hints (preload/prefetch)';
        }

        if (!empty($removed_important)) {
            $issues[] = [
                'type' => 'removed_hooks',
                'severity' => 'low',
                'title' => 'Funcții WordPress eliminate: ' . implode(', ', $removed_important),
                'detail' => 'Aceste funcții au fost eliminate din hookuri.',
                'fix' => 'Verifică dacă eliminarea a fost intenționată.',
            ];
        }
    }

    private function check_cron_health(&$issues) {
        $crons = _get_cron_array();
        if (!is_array($crons) || empty($crons)) {
            $issues[] = [
                'type' => 'cron_broken',
                'severity' => 'high',
                'title' => 'WP-Cron nu funcționează sau e gol',
                'detail' => 'Tabelul cron e gol sau corupt.',
                'fix' => "Verifică dacă DISABLE_WP_CRON e setat. Dacă da, configurează un cron real pe server.",
            ];
            return;
        }

        $overdue = 0;
        $now = time();
        foreach ($crons as $ts => $hooks) {
            if (!is_numeric($ts)) continue;
            if ((int) $ts < $now - 3600) $overdue++;
        }

        if ($overdue > 10) {
            $issues[] = [
                'type' => 'cron_overdue',
                'severity' => 'medium',
                'title' => "$overdue task-uri cron întârziate (>1h)",
                'detail' => 'Task-urile cron nu se execută la timp.',
                'fix' => 'Pe Local/dev e normal. Pe producție, configurează un cron real.',
            ];
        }

        if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) {
            $issues[] = [
                'type' => 'cron_disabled',
                'severity' => 'low',
                'title' => 'WP-Cron dezactivat (DISABLE_WP_CRON = true)',
                'detail' => 'Asigură-te că un cron de sistem e configurat pe producție.',
                'fix' => 'Adaugă în crontab: */5 * * * * wget -q -O - ' . home_url('/wp-cron.php') . ' > /dev/null 2>&1',
            ];
        }
    }

    private function check_rest_api(&$issues) {
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return;
        }

        $rest_url = rest_url();
        if (empty($rest_url)) {
            $issues[] = [
                'type' => 'rest_broken',
                'severity' => 'high',
                'title' => 'REST API nu e configurat',
                'detail' => 'rest_url() returnează gol.',
                'fix' => 'Verifică permalink-urile (salvează din nou din Setări → Permalinks).',
            ];
            return;
        }

        $permalink = get_option('permalink_structure');
        if (empty($permalink)) {
            $issues[] = [
                'type' => 'rest_plain',
                'severity' => 'medium',
                'title' => 'Permalink-uri implicite – REST API folosește ?rest_route=',
                'detail' => 'REST API funcționează dar fără pretty permalinks.',
                'fix' => 'Schimbă la „Post name" din Setări → Permalinks.',
            ];
        }

        if (has_filter('rest_authentication_errors')) {
            $issues[] = [
                'type' => 'rest_restricted',
                'severity' => 'low',
                'title' => 'REST API are filtre de autentificare active',
                'detail' => 'Un plugin poate restricționa accesul la REST API.',
                'fix' => 'E OK dacă e intenționat (securitate). Verifică dacă editorul Gutenberg funcționează.',
            ];
        }
    }

    private function check_php_errors_in_theme(&$issues) {
        $theme_dir = get_stylesheet_directory();
        $functions = $theme_dir . '/functions.php';

        if (!file_exists($functions)) return;

        $content = file_get_contents($functions);
        if (empty($content)) return;

        if (preg_match('/\?\>\s*$/', $content)) {
            $issues[] = [
                'type' => 'php_closing_tag',
                'severity' => 'low',
                'title' => 'functions.php conține tag de închidere ?>',
                'detail' => 'Poate cauza „headers already sent" errors.',
                'fix' => 'Șterge ?> de la sfârșitul fișierului functions.php.',
            ];
        }

        if (substr_count($content, 'error_reporting(0)') > 0 || substr_count($content, '@ini_set') > 0) {
            $issues[] = [
                'type' => 'error_suppression',
                'severity' => 'medium',
                'title' => 'Tema suprimă erorile PHP',
                'detail' => 'error_reporting(0) sau @ini_set găsite în functions.php.',
                'fix' => 'Elimină suprimarea erorilor. Ascunde-le cu WP_DEBUG_DISPLAY false.',
            ];
        }
    }
}
