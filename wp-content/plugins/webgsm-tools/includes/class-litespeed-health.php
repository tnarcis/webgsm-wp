<?php
/**
 * Audit LiteSpeed Cache + preset WebGSM (Woo, B2B, gestiune).
 *
 * @package WebGSM_Tools
 */

if (!defined('ABSPATH')) {
    exit;
}

class WebGSM_Tools_LiteSpeed_Health {

    public const OPTION_PURGE_PRODUCT = 'webgsm_lscwp_purge_on_product_save';

    /** URI-uri care nu trebuie cache-uite deloc (coș, API gestiune, etc.). */
    public const RECOMMENDED_EXC_URIS = [
        '/cart',
        '/checkout',
        '/my-account',
        '/wc-api/',
        '^/wp-json/',
        '/add-to-cart',
        '/admin-ajax.php',
        '/estimeaza-reparatia/',
        '/r/',
    ];

    public function __construct() {
        add_action('wp_ajax_webgsm_lscwp_audit', [$this, 'ajax_audit']);
        add_action('wp_ajax_webgsm_lscwp_apply_preset', [$this, 'ajax_apply_preset']);
        add_action('wp_ajax_webgsm_lscwp_purge_all', [$this, 'ajax_purge_all']);
        add_action('save_post_product', [$this, 'maybe_purge_product_cache'], 99, 1);
    }

    public static function is_lscwp_active(): bool {
        if (defined('LSCWP_V') || class_exists('LiteSpeed\Core')) {
            return true;
        }
        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        return is_plugin_active('litespeed-cache/litespeed-cache.php');
    }

    /**
     * @return mixed|null
     */
    private function conf(string $key) {
        if (has_filter('litespeed_conf')) {
            $val = apply_filters('litespeed_conf', $key);
            if ($val !== null && $val !== false) {
                return $val;
            }
        }
        $raw = get_option('litespeed.conf.' . $key, null);
        if ($raw === null) {
            return null;
        }
        if (is_string($raw) && $raw !== '' && ($raw[0] === '[' || $raw[0] === '{')) {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return $raw;
    }

    private function conf_bool(string $key): ?bool {
        $v = $this->conf($key);
        if ($v === null) {
            return null;
        }
        if (is_bool($v)) {
            return $v;
        }

        return in_array((string) $v, ['1', 'true', 'on'], true);
    }

    private function conf_int(string $key): ?int {
        $v = $this->conf($key);
        if ($v === null || $v === '') {
            return null;
        }

        return (int) $v;
    }

    /** @return string[] */
    private function conf_string_list(string $key): array {
        $v = $this->conf($key);
        if (!is_array($v)) {
            return [];
        }

        return array_values(array_filter(array_map('strval', $v)));
    }

    /**
     * Preset recomandat WebGSM (gestiune Railway, B2B, Woo).
     *
     * @return array<string, mixed>
     */
    public function get_webgsm_preset(): array {
        $vary = [
            'administrator'     => '0',
            'author'            => '0',
            'contributor'       => '0',
            'customer'          => '0',
            'editor'            => '0',
            'subscriber'        => '0',
            'shop_manager'      => '0',
            'translator'        => '0',
            'b2b_customer'      => '0',
            'pending_approval'  => '0',
            'client_b2b'        => '0',
            'aprobare_b2b'      => '0',
        ];
        foreach (wp_roles()->roles as $slug => $role) {
            if (!isset($vary[$slug])) {
                $vary[$slug] = '0';
            }
        }

        return [
            'cache'               => '1',
            'cache-priv'          => '',
            'cache-commenter'     => '',
            'cache-rest'          => '',
            'cache-page_login'    => '1',
            'cache-mobile'        => '',
            'guest'               => '1',
            'cache-ttl_pub'       => '3600',
            'cache-ttl_frontpage' => '3600',
            'cache-ttl_feed'      => '3600',
            'cache-ttl_rest'      => '29',
            'cache-ttl_priv'      => '1800',
            'cache-drop_qs'       => ['fbclid', 'gclid', 'utm*', '_ga'],
            'cache-exc'           => self::RECOMMENDED_EXC_URIS,
            'esi'                 => '',
            'esi-cache_admbar'    => '',
            'esi-cache_commform'  => '',
            'purge-upgrade'       => '1',
            'purge-stale'         => '',
            'purge-post_f'        => '1',
            'purge-post_h'        => '1',
            'purge-post_p'        => '1',
            'purge-post_t'        => '1',
            'purge-post_pt'       => '1',
            'purge-post_m'        => '1',
            'cache-vary_group'    => $vary,
        ];
    }

    /**
     * @return array<int, array{level: string, id: string, message: string, fix?: string}>
     */
    public function run_audit(): array {
        $issues = [];

        if (!self::is_lscwp_active()) {
            $issues[] = [
                'level'   => 'error',
                'id'      => 'lscwp_missing',
                'message' => 'LiteSpeed Cache nu e activ pe acest site.',
                'fix'     => 'Activează pluginul litespeed-cache pe live (Local poate să nu-l aibă).',
            ];

            return $issues;
        }

        $env = function_exists('wp_get_environment_type') ? wp_get_environment_type() : 'production';
        if (defined('WP_DEBUG') && WP_DEBUG && !in_array($env, ['local', 'development'], true)) {
            $issues[] = [
                'level'   => 'warn',
                'id'      => 'wp_debug',
                'message' => 'WP_DEBUG este ON — pe producție încetinește și umple loguri.',
                'fix'     => 'wp-config: WP_DEBUG false pe live.',
            ];
        }

        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        if (is_plugin_active('query-monitor/query-monitor.php') && !in_array($env, ['local', 'development'], true)) {
            $issues[] = [
                'level'   => 'error',
                'id'      => 'query_monitor_live',
                'message' => 'Query Monitor activ pe live — contribuie la timeout (ClassLoader, memorie).',
                'fix'     => 'Plugins → dezactivează Query Monitor pe producție.',
            ];
        }

        if ($this->conf_bool('cache') !== true) {
            $issues[] = [
                'level'   => 'error',
                'id'      => 'cache_off',
                'message' => 'Enable Cache este OFF.',
                'fix'     => 'Rulează preset WebGSM sau activează manual cache.',
            ];
        }

        if ($this->conf_bool('cache-priv') === true) {
            $issues[] = [
                'level'   => 'error',
                'id'      => 'cache_priv_on',
                'message' => 'Cache logged-in users ON — risc preț B2B / coș greșit.',
                'fix'     => 'Preset WebGSM îl oprește.',
            ];
        }

        if ($this->conf_bool('cache-rest') === true) {
            $issues[] = [
                'level'   => 'warn',
                'id'      => 'cache_rest_on',
                'message' => 'Cache REST API ON — problematic dacă gestiunea scrie/citește prin /wp-json/.',
                'fix'     => 'Oprește cache REST (TTL < 30 sau OFF).',
            ];
        }

        $ttl_pub = $this->conf_int('cache-ttl_pub');
        if ($ttl_pub !== null && $ttl_pub > 86400) {
            $issues[] = [
                'level'   => 'warn',
                'id'      => 'ttl_pub_high',
                'message' => sprintf('TTL public %d s (> 24h) — stoc/preț din gestiune pot părea vechi.', $ttl_pub),
                'fix'     => 'Preset: 3600 s (1h) sau purge la sync.',
            ];
        }

        if ($this->conf_bool('purge-stale') === true) {
            $issues[] = [
                'level'   => 'warn',
                'id'      => 'serve_stale',
                'message' => 'Serve Stale ON — poate arăta stoc/preț vechi.',
                'fix'     => 'Oprește Serve Stale.',
            ];
        }

        if ($this->conf_bool('esi') === true) {
            $issues[] = [
                'level'   => 'warn',
                'id'      => 'esi_on',
                'message' => 'ESI ON — complex pentru Woo + B2B; de obicei OFF.',
                'fix'     => 'Preset oprește ESI.',
            ];
        }

        $exc = $this->conf_string_list('cache-exc');
        $missing_exc = [];
        foreach (['/cart', '/checkout', '/my-account'] as $need) {
            $found = false;
            foreach ($exc as $line) {
                if (strpos($line, trim($need, '/')) !== false || $line === $need) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $missing_exc[] = $need;
            }
        }
        if (!empty($missing_exc)) {
            $issues[] = [
                'level'   => 'error',
                'id'      => 'cache_exc',
                'message' => 'Excludes incomplete: lipsesc ' . implode(', ', $missing_exc) . '.',
                'fix'     => 'Preset completează lista Do Not Cache URIs.',
            ];
        }

        if (class_exists('WebGSM_B2B_Pricing', false) || is_plugin_active('webgsm-b2b-pricing/webgsm-b2b-pricing.php')) {
            $issues[] = [
                'level'   => 'info',
                'id'      => 'b2b',
                'message' => 'WebGSM B2B activ — utilizatorii logați nu trebuie serviți din cache public.',
            ];
        }

        if (is_plugin_active('webgsm-woo-sync/webgsm-woo-sync.php')) {
            $issues[] = [
                'level'   => 'info',
                'id'      => 'woo_sync',
                'message' => 'webgsm-woo-sync activ — comenzi spre gestiune; catalogul tot depinde de purge/TTL.',
            ];
        }

        $vary = $this->conf('cache-vary_group');
        if (is_array($vary)) {
            foreach ($vary as $role => $weight) {
                if ((int) $weight > 0 && in_array($role, ['administrator', 'client_b2b', 'b2b_customer', 'customer'], true)) {
                    $issues[] = [
                        'level'   => 'warn',
                        'id'      => 'vary_' . $role,
                        'message' => sprintf('Vary Group rol «%s» = %s — cache public diferit per rol.', $role, $weight),
                        'fix'     => 'Preset pune toate rolurile la 0.',
                    ];
                }
            }
        }

        if (empty($issues)) {
            $issues[] = [
                'level'   => 'ok',
                'id'      => 'all_good',
                'message' => 'Setările LiteSpeed par aliniate cu presetul WebGSM.',
            ];
        }

        return $issues;
    }

    /**
     * @return array<string, mixed>
     */
    public function get_status_snapshot(): array {
        return [
            'lscwp'        => self::is_lscwp_active(),
            'lscwp_version' => defined('LSCWP_V') ? LSCWP_V : null,
            'cache'        => $this->conf_bool('cache'),
            'cache_priv'   => $this->conf_bool('cache-priv'),
            'cache_rest'   => $this->conf_bool('cache-rest'),
            'ttl_pub'      => $this->conf_int('cache-ttl_pub'),
            'ttl_rest'     => $this->conf_int('cache-ttl_rest'),
            'esi'          => $this->conf_bool('esi'),
            'purge_stale'  => $this->conf_bool('purge-stale'),
            'exc_count'    => count($this->conf_string_list('cache-exc')),
            'guest_mode'   => $this->conf_bool('guest'),
            'object_cache' => wp_using_ext_object_cache(),
            'plugins'      => count(get_option('active_plugins', [])),
        ];
    }

    /**
     * @param array<string, mixed> $preset
     */
    public function apply_preset(array $preset): array {
        if (!self::is_lscwp_active()) {
            return ['ok' => false, 'message' => 'LiteSpeed Cache nu e activ.'];
        }
        if (!current_user_can('manage_options')) {
            return ['ok' => false, 'message' => 'Permisiuni insuficiente.'];
        }

        $for_api = [];
        foreach ($preset as $key => $value) {
            if ($value === true) {
                $for_api[$key] = '1';
            } elseif ($value === false) {
                $for_api[$key] = '';
            } else {
                $for_api[$key] = $value;
            }
        }

        if (has_action('litespeed_update_confs')) {
            do_action('litespeed_update_confs', $for_api);
        } else {
            foreach ($for_api as $key => $value) {
                if (is_array($value)) {
                    $value = wp_json_encode($value);
                }
                update_option('litespeed.conf.' . $key, $value, false);
            }
        }

        update_option(self::OPTION_PURGE_PRODUCT, '1', false);

        $this->purge_all();

        return [
            'ok'      => true,
            'message' => 'Preset WebGSM aplicat (' . count($for_api) . ' setări). Cache golit. Purge la salvare produs activat.',
        ];
    }

    public function purge_all(): bool {
        if (has_action('litespeed_purge_all')) {
            do_action('litespeed_purge_all');

            return true;
        }
        if (class_exists('LiteSpeed\Purge')) {
            do_action('litespeed_purge_all');

            return true;
        }

        return false;
    }

    public function maybe_purge_product_cache(int $post_id): void {
        if (get_option(self::OPTION_PURGE_PRODUCT, '') !== '1') {
            return;
        }
        if (get_post_type($post_id) !== 'product') {
            return;
        }
        if (has_action('litespeed_purge_post')) {
            do_action('litespeed_purge_post', $post_id);
        }
    }

    public function ajax_audit(): void {
        check_ajax_referer('webgsm_tools', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        wp_send_json_success([
            'issues'   => $this->run_audit(),
            'snapshot' => $this->get_status_snapshot(),
        ]);
    }

    public function ajax_apply_preset(): void {
        check_ajax_referer('webgsm_tools', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        $result = $this->apply_preset($this->get_webgsm_preset());
        if (!$result['ok']) {
            wp_send_json_error(['message' => $result['message']]);
        }
        wp_send_json_success([
            'message' => $result['message'],
            'issues'  => $this->run_audit(),
            'snapshot' => $this->get_status_snapshot(),
        ]);
    }

    public function ajax_purge_all(): void {
        check_ajax_referer('webgsm_tools', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        $ok = $this->purge_all();
        wp_send_json_success([
            'message' => $ok ? 'Purge All trimis către LiteSpeed.' : 'Hook litespeed_purge_all indisponibil — folosește Purge din meniul LiteSpeed.',
        ]);
    }
}
