<?php
if (!defined('ABSPATH')) exit;

class WebGSM_Site_Audit_Link_Checker {

    const RESULTS_KEY = 'webgsm_site_audit_scan_results';
    const LAST_SCAN_KEY = 'webgsm_site_audit_last_scan';

    public function __construct() {
        add_action('wp_ajax_webgsm_audit_scan_links', [$this, 'ajax_scan']);
        add_action('wp_ajax_webgsm_audit_get_results', [$this, 'ajax_get_results']);
        add_action('wp_ajax_webgsm_audit_clear_logs', [$this, 'ajax_clear_logs']);
    }

    public function ajax_scan() {
        check_ajax_referer('webgsm_site_audit', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Forbidden');

        @set_time_limit(300);

        $settings = WebGSM_Site_Audit_Settings::get();
        $links = $this->collect_links($settings);
        $results = $this->check_links($links, $settings);

        update_option(self::RESULTS_KEY, $results, false);
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
        wp_send_json_success(['results' => is_array($results) ? $results : [], 'last_scan' => $last]);
    }

    /**
     * Curăță rezultatele scanării linkuri și data ultimei scanări (pentru a reporni audit-ul „de la zero”).
     */
    public function ajax_clear_logs() {
        check_ajax_referer('webgsm_site_audit', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Forbidden');

        update_option(self::RESULTS_KEY, [], false);
        update_option(self::LAST_SCAN_KEY, 0);

        wp_send_json_success(['message' => 'Rezultatele auditului au fost șterse. Poți rula din nou scanarea.']);
    }

    public function collect_links($settings) {
        $links = [];
        $settings = is_array($settings) ? $settings : [];
        $site_url = home_url('/');
        $site_host = parse_url($site_url, PHP_URL_HOST);
        if (!$site_host) $site_host = '';

        if (!empty($settings['scan_posts'])) $this->collect_from_posts('post', $links);
        if (!empty($settings['scan_pages'])) $this->collect_from_posts('page', $links);
        if (!empty($settings['scan_products']) && post_type_exists('product')) $this->collect_from_posts('product', $links);
        if (!empty($settings['scan_menus'])) $this->collect_from_menus($links);
        if (!empty($settings['scan_widgets'])) $this->collect_from_widgets($links);
        if (!empty($settings['scan_options'])) $this->collect_from_options($links);

        $filtered = [];
        foreach ($links as $link) {
            $url = isset($link['url']) ? $link['url'] : '';
            if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) continue;
            if (strpos($url, 'mailto:') === 0 || strpos($url, 'tel:') === 0 || strpos($url, '#') === 0) continue;
            if (strpos($url, 'javascript:') === 0) continue;

            $link_host = parse_url($url, PHP_URL_HOST);
            $is_internal = ($link_host === $site_host) || (strpos($url, $site_url) === 0);
            if ($is_internal && empty($settings['check_internal'])) continue;
            if (!$is_internal && empty($settings['check_external'])) continue;

            $link['internal'] = $is_internal;
            $filtered[] = $link;
        }

        $seen = [];
        $unique = [];
        foreach ($filtered as $l) {
            if (empty($l['url'])) continue;
            $key = rtrim($l['url'], '/');
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
            'numberposts' => 500,
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
            if ($sidebar_id === 'wp_inactive_widgets' || !is_array($widget_ids)) continue;
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
            if (is_array($w)) $content .= implode(' ', array_map('strval', $w));
        }
        return $content;
    }

    private function collect_from_options(&$links) {
        $opts = ['blogdescription', 'siteurl', 'home'];
        foreach ($opts as $opt) {
            $val = get_option($opt);
            if ($val && filter_var($val, FILTER_VALIDATE_URL)) {
                $links[] = ['url' => $val, 'source' => 'option', 'source_id' => $opt, 'source_title' => $opt];
            }
        }
    }

    private function extract_links_from_html($html, &$links, $meta = []) {
        if (empty($html)) return;
        // Capturăm și textul anchor pentru a indica „unde” apare linkul.
        preg_match_all('/<a\\s[^>]*href=(["\'])(.*?)\\1[^>]*>(.*?)<\\/a>/is', $html, $m);
        if (!empty($m[2])) {
            foreach ($m[2] as $i => $url) {
                $url = trim((string) $url);
                if (empty($url) || strpos($url, 'mailto:') === 0 || strpos($url, 'tel:') === 0) continue;
                if (strpos($url, '/') === 0 && strpos($url, '//') !== 0) $url = home_url($url);

                $anchor_html = isset($m[3][$i]) ? (string) $m[3][$i] : '';
                $anchor_text = trim(wp_strip_all_tags(html_entity_decode($anchor_html, ENT_QUOTES, 'UTF-8')));
                if (mb_strlen($anchor_text) > 80) $anchor_text = mb_substr($anchor_text, 0, 77) . '...';

                $links[] = array_merge($meta, [
                    'url' => $url,
                    'anchor_text' => $anchor_text,
                ]);
            }
        }
    }

    private function check_links($links, $settings) {
        $timeout = min((int) $settings['timeout'], 5);
        $follow = !empty($settings['follow_redirects']);
        $max_redir = isset($settings['max_redirects']) ? (int) $settings['max_redirects'] : 5;
        $results = [];

        $site_url = home_url('/');
        $site_host = parse_url($site_url, PHP_URL_HOST);

        foreach ($links as $link) {
            $url = isset($link['url']) ? $link['url'] : '';
            if (empty($url)) continue;

            $link_host = parse_url($url, PHP_URL_HOST);
            $is_internal = ($link_host === $site_host);

            if ($is_internal) {
                $status = $this->check_internal_url($url);
            } else {
                $status = $this->check_external_url($url, $timeout, $follow, $max_redir);
            }

            $edit_url = $this->get_source_edit_url($link);

            $results[] = array_merge($link, [
                'status' => $status['status'],
                'http_code' => $status['code'],
                'error' => isset($status['error']) ? $status['error'] : '',
                'source_edit_url' => $edit_url,
            ]);
        }

        return $results;
    }

    private function get_source_edit_url($link) {
        $source = isset($link['source']) ? (string) $link['source'] : '';
        $source_id = isset($link['source_id']) ? $link['source_id'] : '';

        if (in_array($source, ['post', 'page', 'product'], true) && is_numeric($source_id)) {
            return admin_url('post.php?post=' . absint($source_id) . '&action=edit');
        }
        if ($source === 'menu' && is_numeric($source_id)) {
            return admin_url('nav-menus.php?menu=' . absint($source_id));
        }
        if ($source === 'widget') {
            return admin_url('widgets.php');
        }
        if ($source === 'option') {
            return admin_url('options-general.php');
        }
        return '';
    }

    private function check_internal_url($url) {
        $path = parse_url($url, PHP_URL_PATH);
        if (empty($path) || $path === '/') {
            return ['status' => 'ok', 'code' => 200];
        }

        $post_id = url_to_postid($url);
        if ($post_id > 0) {
            $post_status = get_post_status($post_id);
            if ($post_status === 'publish') {
                return ['status' => 'ok', 'code' => 200];
            }
            if ($post_status === 'trash' || $post_status === false) {
                return ['status' => 'broken', 'code' => 404, 'error' => 'Post in trash or deleted'];
            }
            return ['status' => 'ok', 'code' => 200];
        }

        $clean_path = trim($path, '/');

        if (preg_match('#^wp-content/uploads/(.+)$#', $clean_path, $m)) {
            $file = ABSPATH . 'wp-content/uploads/' . $m[1];
            if (file_exists($file)) return ['status' => 'ok', 'code' => 200];
            return ['status' => 'broken', 'code' => 404, 'error' => 'Upload file missing'];
        }

        if (preg_match('#^(product-category|category|tag)/(.+)$#', $clean_path)) {
            $term = get_term_by('slug', basename($clean_path), 'product_cat');
            if (!$term) $term = get_term_by('slug', basename($clean_path), 'category');
            if (!$term) $term = get_term_by('slug', basename($clean_path), 'post_tag');
            if ($term && !is_wp_error($term)) return ['status' => 'ok', 'code' => 200];
        }

        $page = get_page_by_path($clean_path);
        if ($page && $page->post_status === 'publish') {
            return ['status' => 'ok', 'code' => 200];
        }

        $static_files = ['robots.txt', 'sitemap.xml', 'sitemap_index.xml', 'wp-sitemap.xml', 'wp-login.php', 'wp-admin', 'feed', 'xmlrpc.php'];
        foreach ($static_files as $sf) {
            if (strpos($clean_path, $sf) === 0) return ['status' => 'ok', 'code' => 200];
        }

        if (file_exists(ABSPATH . $clean_path)) {
            return ['status' => 'ok', 'code' => 200];
        }

        return ['status' => 'broken', 'code' => 404, 'error' => 'URL not resolved internally'];
    }

    private function check_external_url($url, $timeout, $follow, $max_redirects) {
        $args = [
            'timeout' => $timeout,
            'redirection' => $follow ? $max_redirects : 0,
            'user-agent' => 'Mozilla/5.0 (compatible; WebGSM-Audit/3.0)',
            'sslverify' => false,
        ];

        $response = wp_remote_head($url, $args);
        if (is_wp_error($response)) {
            $msg = $response->get_error_message();
            if (stripos($msg, 'timed out') !== false || stripos($msg, 'timeout') !== false) {
                return ['status' => 'error', 'code' => 0, 'error' => 'Timeout'];
            }
            return ['status' => 'error', 'code' => 0, 'error' => $msg];
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code >= 200 && $code < 400) return ['status' => 'ok', 'code' => $code];
        if ($code === 404) return ['status' => 'broken', 'code' => 404, 'error' => 'Not Found'];
        if ($code === 403) return ['status' => 'ok', 'code' => $code, 'error' => 'Forbidden (may block bots)'];
        if ($code === 405) {
            $get = wp_remote_get($url, $args);
            if (!is_wp_error($get)) {
                $gc = wp_remote_retrieve_response_code($get);
                if ($gc >= 200 && $gc < 400) return ['status' => 'ok', 'code' => $gc];
            }
        }
        if ($code >= 400) return ['status' => 'broken', 'code' => $code, 'error' => "HTTP $code"];

        return ['status' => 'unknown', 'code' => $code];
    }
}
