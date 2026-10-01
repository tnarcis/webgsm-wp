<?php
/**
 * WebGSM Site Ops — diagnostic producție (DB, cron, cache, debug).
 * Nu înlocuiește MySQL slow log / APM de hosting; dă semnale acționabile din wp-admin.
 *
 * @package WebGSM_Tools
 */

if (!defined('ABSPATH')) {
    exit;
}

class WebGSM_Tools_Site_Ops {

    public function __construct() {
        add_action('wp_ajax_webgsm_site_ops_run', [$this, 'ajax_run']);
    }

    public function ajax_run(): void {
        check_ajax_referer('webgsm_tools', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        wp_send_json_success($this->collect());
    }

    /**
     * @return array<string, mixed>
     */
    public function collect(): array {
        $issues = [];
        $probes = $this->timed_probes();
        $autoload = $this->autoload_stats();
        $as = $this->action_scheduler_stats();
        $env = $this->environment();

        if (!empty($env['wp_debug'])) {
            $issues[] = $this->issue('error', 'WP_DEBUG este ON pe acest mediu. Pe live oprește-l (wp-config).');
        }
        if (!empty($env['wp_debug_log'])) {
            $issues[] = $this->issue('warn', 'WP_DEBUG_LOG ON — logul crește (B2B, compare, DB). Oprește pe producție după debug.');
        }
        if (empty($env['object_cache'])) {
            $issues[] = $this->issue('warn', 'Object cache extern OFF (Redis/Memcached). LiteSpeed Object Cache ON ajută dacă e configurat pe server.');
        }
        if ($autoload['bytes'] > 800000) {
            $issues[] = $this->issue(
                'warn',
                sprintf('Opțiuni autoload ~%s — peste ~800KB încetinește fiecare request.', size_format($autoload['bytes']))
            );
        }
        if ($as['pending'] > 500) {
            $issues[] = $this->issue('error', sprintf('Action Scheduler: %d acțiuni pending — coadă Woo/cron aglomerată.', $as['pending']));
        } elseif ($as['pending'] > 100) {
            $issues[] = $this->issue('warn', sprintf('Action Scheduler: %d pending — verifică Woo → Status → Scheduled Actions.', $as['pending']));
        }
        if ($as['failed'] > 20) {
            $issues[] = $this->issue('warn', sprintf('Action Scheduler: %d failed.', $as['failed']));
        }
        foreach ($probes as $probe) {
            if ($probe['ms'] >= 400) {
                $issues[] = $this->issue('error', sprintf('Query lent: %s — %d ms.', $probe['label'], $probe['ms']));
            } elseif ($probe['ms'] >= 150) {
                $issues[] = $this->issue('warn', sprintf('Query moderat: %s — %d ms.', $probe['label'], $probe['ms']));
            }
        }
        if (class_exists('WebGSM_Tools_LiteSpeed_Health')) {
            $ls = new WebGSM_Tools_LiteSpeed_Health();
            foreach ($ls->run_audit() as $row) {
                if (($row['level'] ?? '') === 'ok') {
                    continue;
                }
                $issues[] = $this->issue($row['level'], '[LiteSpeed] ' . $row['message']);
            }
        }

        $plugins = get_option('active_plugins', []);
        if (count($plugins) > 40) {
            $issues[] = $this->issue('warn', sprintf('%d pluginuri active — fiecare adaugă hook-uri pe frontend.', count($plugins)));
        }

        if (empty($issues)) {
            $issues[] = $this->issue('ok', 'Nu am găsit semnale critice în probele rapide. Verifică totuși PageSpeed pe live (cache hit).');
        }

        return [
            'generated_at' => gmdate('c'),
            'environment'  => $env,
            'autoload'     => $autoload,
            'action_scheduler' => $as,
            'probes'       => $probes,
            'issues'       => $issues,
            'next_steps'   => $this->next_steps(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function environment(): array {
        return [
            'php'            => PHP_VERSION,
            'memory_limit'   => ini_get('memory_limit'),
            'max_execution'  => ini_get('max_execution_time'),
            'wp_version'     => get_bloginfo('version'),
            'wp_debug'       => defined('WP_DEBUG') && WP_DEBUG,
            'wp_debug_log'   => defined('WP_DEBUG_LOG') && WP_DEBUG_LOG,
            'object_cache'   => wp_using_ext_object_cache(),
            'litespeed'      => class_exists('WebGSM_Tools_LiteSpeed_Health') && WebGSM_Tools_LiteSpeed_Health::is_lscwp_active(),
            'active_plugins' => count(get_option('active_plugins', [])),
            'products'       => $this->count_products(),
        ];
    }

    private function count_products(): int {
        $counts = wp_count_posts('product');
        if (!$counts || !isset($counts->publish)) {
            return 0;
        }

        return (int) $counts->publish;
    }

    /**
     * @return array{bytes: int, rows: int, top: array<int, array{name: string, bytes: int}>}
     */
    private function autoload_stats(): array {
        global $wpdb;
        $bytes = (int) $wpdb->get_var(
            "SELECT SUM(LENGTH(option_value)) FROM {$wpdb->options} WHERE autoload IN ('yes','on','auto')"
        );
        $rows = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->options} WHERE autoload IN ('yes','on','auto')"
        );
        $top = $wpdb->get_results(
            "SELECT option_name AS name, LENGTH(option_value) AS bytes
             FROM {$wpdb->options}
             WHERE autoload IN ('yes','on','auto')
             ORDER BY bytes DESC
             LIMIT 8",
            ARRAY_A
        );

        return [
            'bytes' => $bytes,
            'rows'  => $rows,
            'top'   => is_array($top) ? $top : [],
        ];
    }

    /**
     * @return array{pending: int, failed: int, complete: int, table: bool}
     */
    private function action_scheduler_stats(): array {
        global $wpdb;
        $table = $wpdb->prefix . 'actionscheduler_actions';
        $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table;
        if (!$exists) {
            return ['pending' => 0, 'failed' => 0, 'complete' => 0, 'table' => false];
        }
        $counts = $wpdb->get_results(
            "SELECT status, COUNT(*) AS c FROM {$table} GROUP BY status",
            ARRAY_A
        );
        $out = ['pending' => 0, 'failed' => 0, 'complete' => 0, 'in-progress' => 0, 'table' => true];
        foreach ((array) $counts as $row) {
            $status = (string) ($row['status'] ?? '');
            $out[$status] = (int) ($row['c'] ?? 0);
        }

        return $out;
    }

    /**
     * @return array<int, array{label: string, ms: int, detail: string}>
     */
    private function timed_probes(): array {
        global $wpdb;
        $probes = [];

        $probes[] = $this->time_probe('Număr produse publicate', function () {
            return (string) wp_count_posts('product')->publish;
        });

        $probes[] = $this->time_probe('Categorii product_cat (get_terms)', function () {
            $terms = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false, 'fields' => 'ids']);

            return is_wp_error($terms) ? 'error' : (string) count($terms);
        });

        $probes[] = $this->time_probe('Lookup meta _sku (sample)', function () use ($wpdb) {
            $n = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_sku'"
            );

            return (string) $n;
        });

        $probes[] = $this->time_probe('Sesiuni WooCommerce (rânduri)', function () use ($wpdb) {
            $table = $wpdb->prefix . 'woocommerce_sessions';
            if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) {
                return 'n/a';
            }

            return (string) (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        });

        return $probes;
    }

    /**
     * @param callable(): string $fn
     * @return array{label: string, ms: int, detail: string}
     */
    private function time_probe(string $label, callable $fn): array {
        $start = microtime(true);
        try {
            $detail = (string) $fn();
        } catch (Throwable $e) {
            $detail = $e->getMessage();
        }
        $ms = (int) round((microtime(true) - $start) * 1000);

        return [
            'label'  => $label,
            'ms'     => $ms,
            'detail' => $detail,
        ];
    }

    /**
     * @return array{level: string, message: string}
     */
    private function issue(string $level, string $message): array {
        return ['level' => $level, 'message' => $message];
    }

    /**
     * @return string[]
     */
    private function next_steps(): array {
        return [
            'Live: LiteSpeed preset WebGSM + Purge All (meniul ⚡ LiteSpeed).',
            'Live: WP_DEBUG și WP_DEBUG_LOG false după ce termini debug-ul.',
            'WooCommerce → Status → Scheduled Actions: șterge failed vechi, lasă cron să ruleze.',
            'Hosting: activează MySQL slow query log (prag 1s) — singura sursă completă de slow queries, nu doar probele de aici.',
            'Nu instala zeci de pluginuri „optimizer”; un cache (LiteSpeed) + excludes Woo e suficient.',
            'Gestiune Railway: după sync stoc, produsul trebuie salvat în Woo ca să se purge cache-ul (opțiune activată de preset).',
        ];
    }
}
