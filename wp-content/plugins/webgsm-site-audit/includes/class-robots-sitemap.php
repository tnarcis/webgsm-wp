<?php
if (!defined('ABSPATH')) exit;

class WebGSM_Site_Audit_Robots_Sitemap {

    public function __construct() {
        add_action('wp_ajax_webgsm_audit_robots_sitemap', [$this, 'ajax_scan']);
    }

    public function ajax_scan() {
        check_ajax_referer('webgsm_site_audit', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Forbidden');

        $site_url = home_url('/');
        $robots = $this->check_robots($site_url);
        $sitemap = $this->check_sitemap($site_url);

        wp_send_json_success([
            'robots' => $robots,
            'sitemap' => $sitemap,
        ]);
    }

    private function check_robots($site_url) {
        $result = [
            'exists' => false,
            'content' => '',
            'issues' => [],
            'info' => [],
        ];

        $robots_url = trailingslashit($site_url) . 'robots.txt';
        $response = wp_remote_get($robots_url, ['timeout' => 15, 'sslverify' => false]);

        if (is_wp_error($response)) {
            $result['issues'][] = [
                'severity' => 'high',
                'title' => 'robots.txt inaccesibil',
                'fix' => 'Verifică dacă robots.txt există în rădăcina site-ului. WordPress îl generează automat.',
            ];
            return $result;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            $result['issues'][] = [
                'severity' => 'high',
                'title' => "robots.txt returnează HTTP $code",
                'fix' => 'Fișierul trebuie să returneze 200. Verifică .htaccess sau configurația server.',
            ];
            return $result;
        }

        $body = wp_remote_retrieve_body($response);
        $result['exists'] = true;
        $result['content'] = substr($body, 0, 5000);

        if (stripos($body, 'Disallow: /') !== false) {
            $lines = explode("\n", $body);
            foreach ($lines as $line) {
                $line = trim($line);
                if (preg_match('/^Disallow:\s*\/$/i', $line)) {
                    $result['issues'][] = [
                        'severity' => 'high',
                        'title' => 'robots.txt blochează TOTUL (Disallow: /)',
                        'fix' => 'Această regulă blochează toți roboții. Elimină linia „Disallow: /" dacă site-ul trebuie indexat.',
                    ];
                }
            }
        }

        if (stripos($body, 'wp-admin') === false) {
            $result['info'][] = 'wp-admin nu e blocat explicit (OK – WordPress adaugă noindex pe admin).';
        }

        if (stripos($body, 'Sitemap:') !== false) {
            $result['info'][] = 'robots.txt conține referință către Sitemap ✓';
            preg_match_all('/Sitemap:\s*(.+)/i', $body, $m);
            if (!empty($m[1])) {
                foreach ($m[1] as $sm_url) {
                    $result['info'][] = 'Sitemap declarat: ' . trim($sm_url);
                }
            }
        } else {
            $result['issues'][] = [
                'severity' => 'medium',
                'title' => 'robots.txt nu conține referință Sitemap',
                'fix' => 'Adaugă „Sitemap: ' . trailingslashit($site_url) . 'sitemap_index.xml" la sfârșitul robots.txt',
            ];
        }

        if (stripos($body, 'Disallow: /wp-content/uploads') !== false) {
            $result['issues'][] = [
                'severity' => 'medium',
                'title' => 'Uploads blocate în robots.txt',
                'fix' => 'Imaginile din uploads nu vor fi indexate. Elimină regula dacă vrei imagini în Google Images.',
            ];
        }

        if (stripos($body, 'Disallow: /?') !== false || stripos($body, 'Disallow: /*?') !== false) {
            $result['info'][] = 'Parametrii URL blocați (bine – previne conținut duplicat).';
        }

        return $result;
    }

    private function check_sitemap($site_url) {
        $result = [
            'found' => false,
            'url' => '',
            'type' => '',
            'urls_count' => 0,
            'issues' => [],
            'info' => [],
        ];

        $candidates = [
            trailingslashit($site_url) . 'sitemap_index.xml',
            trailingslashit($site_url) . 'sitemap.xml',
            trailingslashit($site_url) . 'wp-sitemap.xml',
            trailingslashit($site_url) . 'sitemap_index.xml',
        ];

        $found_url = '';
        foreach (array_unique($candidates) as $url) {
            $resp = wp_remote_head($url, ['timeout' => 10, 'sslverify' => false]);
            if (!is_wp_error($resp) && wp_remote_retrieve_response_code($resp) === 200) {
                $found_url = $url;
                break;
            }
        }

        if (!$found_url) {
            $result['issues'][] = [
                'severity' => 'high',
                'title' => 'Niciun sitemap.xml găsit',
                'fix' => 'Instalează Yoast SEO sau Rank Math pentru sitemap automat. WordPress 5.5+ include wp-sitemap.xml nativ.',
            ];
            return $result;
        }

        $result['found'] = true;
        $result['url'] = $found_url;

        $resp = wp_remote_get($found_url, ['timeout' => 15, 'sslverify' => false]);
        if (is_wp_error($resp)) {
            $result['issues'][] = [
                'severity' => 'medium',
                'title' => 'Sitemap găsit dar nu poate fi citit',
                'fix' => 'Verifică permisiunile fișierului sau generarea dinamică.',
            ];
            return $result;
        }

        $body = wp_remote_retrieve_body($resp);
        $content_type = wp_remote_retrieve_header($resp, 'content-type');

        if (strpos($body, '<sitemapindex') !== false) {
            $result['type'] = 'index';
            preg_match_all('/<sitemap>/', $body, $m);
            $result['urls_count'] = count($m[0]);
            $result['info'][] = 'Tip: Sitemap Index cu ' . $result['urls_count'] . ' sub-sitemaps.';

            preg_match_all('/<loc>([^<]+)<\/loc>/', $body, $locs);
            if (!empty($locs[1])) {
                $checked = 0;
                foreach ($locs[1] as $sub_url) {
                    if (++$checked > 5) break;
                    $sub_resp = wp_remote_head(trim($sub_url), ['timeout' => 10, 'sslverify' => false]);
                    if (is_wp_error($sub_resp) || wp_remote_retrieve_response_code($sub_resp) !== 200) {
                        $result['issues'][] = [
                            'severity' => 'medium',
                            'title' => 'Sub-sitemap inaccesibil: ' . basename($sub_url),
                            'fix' => 'Verifică generarea sitemap-ului. Regenerează din setările pluginului SEO.',
                        ];
                    }
                }
            }
        } elseif (strpos($body, '<urlset') !== false) {
            $result['type'] = 'urlset';
            preg_match_all('/<url>/', $body, $m);
            $result['urls_count'] = count($m[0]);
            $result['info'][] = 'Tip: Sitemap standard cu ' . $result['urls_count'] . ' URL-uri.';
        } else {
            $result['issues'][] = [
                'severity' => 'high',
                'title' => 'Sitemap invalid – nu conține structură XML corectă',
                'fix' => 'Regenerează sitemap-ul din setările pluginului SEO.',
            ];
        }

        if (strpos($content_type, 'xml') === false && strpos($content_type, 'text') === false) {
            $result['issues'][] = [
                'severity' => 'low',
                'title' => 'Content-Type incorect: ' . $content_type,
                'fix' => 'Sitemap-ul ar trebui servit ca application/xml.',
            ];
        }

        $body_size = strlen($body);
        if ($body_size > 50 * 1024 * 1024) {
            $result['issues'][] = [
                'severity' => 'medium',
                'title' => 'Sitemap prea mare: ' . size_format($body_size),
                'fix' => 'Google acceptă max 50MB per sitemap. Împarte în mai multe fișiere.',
            ];
        }

        return $result;
    }
}
