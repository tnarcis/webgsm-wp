<?php
if (!defined('ABSPATH')) exit;

class WebGSM_Site_Audit_Link_Checker {

    const RESULTS_KEY = 'webgsm_site_audit_scan_results';
    const LAST_SCAN_KEY = 'webgsm_site_audit_last_scan';

    public function __construct() {
        add_action('wp_ajax_webgsm_audit_scan_links', [$this, 'ajax_scan']);
        add_action('wp_ajax_webgsm_audit_get_results', [$this, 'ajax_get_results']);
    }

    public function ajax_scan() {
        check_ajax_referer('webgsm_site_audit', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Forbidden');

        $settings = WebGSM_Site_Audit_Settings::get();
        $links = $this->collect_links($settings);
        $results = $this->check_links($links, $settings);

        update_option(self::RESULTS_KEY, $results);
        update_option(self::LAST_SCAN_KEY, time());

        wp_send_json_success([
            'total' => count($links),
            'broken' => count(array_filter($results, function($r) { return isset($r['status']) && $r['status'] !== 'ok'; })),
            'results' => $results,
        ]);
    }

    public function ajax_get_results() {
        check_ajax_referer('webgsm_site_audit', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Forbidden');

        $results = get_option(self::RESULTS_KEY, []);
        $last = get_option(self::LAST_SCAN_KEY, 0);
        wp_send_json_success(['results' => $results, 'last_scan' => $last]);
    }

    public function collect_links($settings) {
        $links = [];
        $settings = is_array($settings) ? $settings : [];
        $site_url = home_url('/');
        $site_host = parse_url($site_url, PHP_URL_HOST);
        if (!$site_host) $site_host = '';

        if (!empty($settings['scan_posts'])) {
            $this->collect_from_posts('post', $links);
        }
        if (!empty($settings['scan_pages'])) {
            $this->collect_from_posts('page', $links);
        }
        if (!empty($settings['scan_products']) && post_type_exists('product')) {
            $this->collect_from_posts('product', $links);
        }
        if (!empty($settings['scan_menus'])) {
            $this->collect_from_menus($links);
        }
        if (!empty($settings['scan_widgets'])) {
            $this->collect_from_widgets($links);
        }
        if (!empty($settings['scan_options'])) {
            $this->collect_from_options($links);
        }

        $filtered = [];
        foreach ($links as $link) {
            $url = isset($link['url']) ? $link['url'] : '';
            if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) continue;
            if (strpos($url, 'mailto:') === 0 || strpos($url, 'tel:') === 0) continue;
            if (strpos($url, '#') === 0) continue;

            $is_internal = (strpos($url, $site_url) === 0) || (parse_url($url, PHP_URL_HOST) === $site_host);
            if ($is_internal && empty($settings['check_internal'])) continue;
            if (!$is_internal && empty($settings['check_external'])) continue;

            $link['internal'] = $is_internal;
            $filtered[] = $link;
        }

        $seen = [];
        $unique = [];
        foreach ($filtered as $l) {
            if (empty($l['url'])) continue;
            $key = $l['url'];
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $unique[] = $l;
        }
        return $unique;
    }

    private function collect_from_posts($post_type, &$links) {
        $posts = get_posts([
            'post_type' => $post_type,
            'post_status' => 'publish',
            'numberposts' => -1,
            'fields' => 'ids',
        ]);
        foreach ($posts as $id) {
            $content = get_post_field('post_content', $id);
            $this->extract_links_from_html($content, $links, [
                'source' => $post_type,
                'source_id' => $id,
                'source_title' => get_the_title($id),
            ]);
        }
    }

    private function collect_from_menus(&$links) {
        $menus = wp_get_nav_menus();
        foreach ($menus as $menu) {
            $items = wp_get_nav_menu_items($menu->term_id);
            if (!$items) continue;
            foreach ($items as $item) {
                if (!empty($item->url)) {
                    $links[] = [
                        'url' => $item->url,
                        'source' => 'menu',
                        'source_id' => $menu->term_id,
                        'source_title' => $menu->name . ' → ' . $item->title,
                    ];
                }
            }
        }
    }

    private function collect_from_widgets(&$links) {
        $sidebars = wp_get_sidebars_widgets();
        foreach ($sidebars as $sidebar_id => $widget_ids) {
            if ($sidebar_id === 'wp_inactive_widgets') continue;
            foreach ($widget_ids as $widget_id) {
                $widget = $this->get_widget_content($widget_id);
                if ($widget) {
                    $this->extract_links_from_html($widget, $links, [
                        'source' => 'widget',
                        'source_id' => $widget_id,
                        'source_title' => $widget_id,
                    ]);
                }
            }
        }
    }

    private function get_widget_content($widget_id) {
        $base = preg_replace('/-\d+$/', '', $widget_id);
        $opt = get_option('widget_' . $base);
        if (!is_array($opt)) return '';
        $content = '';
        foreach ($opt as $w) {
            if (is_array($w)) {
                $content .= implode(' ', array_map('strval', $w));
            }
        }
        return $content;
    }

    private function collect_from_options(&$links) {
        $opts = ['blogdescription', 'siteurl', 'home'];
        foreach ($opts as $opt) {
            $val = get_option($opt);
            if ($val && filter_var($val, FILTER_VALIDATE_URL)) {
                $links[] = [
                    'url' => $val,
                    'source' => 'option',
                    'source_id' => $opt,
                    'source_title' => $opt,
                ];
            }
        }
    }

    private function extract_links_from_html($html, &$links, $meta = []) {
        if (empty($html)) return;
        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $html, $m);
        if (!empty($m[1])) {
            foreach ($m[1] as $url) {
                $url = trim($url);
                if (empty($url) || strpos($url, 'mailto:') === 0 || strpos($url, 'tel:') === 0) continue;
                if (strpos($url, '/') === 0) $url = home_url($url);
                $links[] = array_merge($meta, ['url' => $url]);
            }
        }
        preg_match_all('/url\(["\']?([^"\')\s]+)["\']?\)/i', $html, $m2);
        if (!empty($m2[1])) {
            foreach ($m2[1] as $url) {
                $url = trim($url);
                if (empty($url) || strpos($url, 'data:') === 0) continue;
                if (strpos($url, '/') === 0) $url = home_url($url);
                $links[] = array_merge($meta, ['url' => $url]);
            }
        }
    }

    private function check_links($links, $settings) {
        $timeout = (int) $settings['timeout'];
        $follow = !empty($settings['follow_redirects']);
        $max_redir = (int) ($settings['max_redirects'] ?? 5);
        $results = [];

        foreach ($links as $link) {
            $url = isset($link['url']) ? $link['url'] : '';
            if (empty($url)) continue;
            $status = $this->check_url($url, $timeout, $follow, $max_redir);
            $results[] = array_merge($link, [
                'status' => $status['status'],
                'http_code' => $status['code'],
                'error' => $status['error'] ?? '',
            ]);
        }

        return $results;
    }

    private function check_url($url, $timeout, $follow, $max_redirects) {
        $args = [
            'timeout' => $timeout,
            'redirection' => $follow ? $max_redirects : 0,
            'user-agent' => 'WebGSM-Site-Audit/1.0',
            'sslverify' => true,
        ];

        $response = wp_remote_head($url, $args);
        if (is_wp_error($response)) {
            return [
                'status' => 'error',
                'code' => 0,
                'error' => $response->get_error_message(),
            ];
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code >= 200 && $code < 400) {
            return ['status' => 'ok', 'code' => $code];
        }
        if ($code === 404) {
            return ['status' => 'broken', 'code' => 404, 'error' => 'Not Found'];
        }
        if ($code >= 400) {
            return ['status' => 'broken', 'code' => $code, 'error' => "HTTP $code"];
        }

        return ['status' => 'unknown', 'code' => $code];
    }
}
