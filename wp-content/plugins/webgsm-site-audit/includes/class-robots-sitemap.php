<?php
if (!defined('ABSPATH')) exit;

class WebGSM_Site_Audit_Robots_Sitemap {

    public function __construct() {
        add_action('wp_ajax_webgsm_audit_robots_sitemap', [$this, 'ajax_scan']);
    }

    public function ajax_scan() {
        check_ajax_referer('webgsm_site_audit', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Forbidden');

        $robots = $this->check_robots();
        $sitemap = $this->check_sitemap();

        wp_send_json_success([
            'robots' => $robots,
            'sitemap' => $sitemap,
        ]);
    }

    private function check_robots() {
        $result = [
            'exists' => false,
            'content' => '',
            'issues' => [],
            'info' => [],
        ];

        $robots_file = ABSPATH . 'robots.txt';
        $body = '';

        if (file_exists($robots_file) && is_readable($robots_file)) {
            $body = file_get_contents($robots_file);
            $result['exists'] = true;
            $result['info'][] = 'robots.txt fizic găsit în rădăcina site-ului.';
        } else {
            ob_start();
            do_action('do_robots');
            $body = ob_get_clean();
            if (!empty(trim($body))) {
                $result['exists'] = true;
                $result['info'][] = 'robots.txt generat dinamic de WordPress (virtual).';
            }
        }

        if (!$result['exists']) {
            $result['issues'][] = [
                'severity' => 'medium',
                'title' => 'robots.txt nu există',
                'fix' => 'WordPress generează unul virtual automat. Dacă ai nevoie de reguli custom, creează fișierul manual sau folosește un plugin SEO.',
            ];
            return $result;
        }

        $result['content'] = substr($body, 0, 5000);

        $lines = explode("\n", $body);
        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/^Disallow:\s*\/$/i', $line)) {
                $result['issues'][] = [
                    'severity' => 'high',
                    'title' => 'robots.txt blochează TOTUL (Disallow: /)',
                    'fix' => 'Această regulă blochează toți roboții complet. Elimină linia „Disallow: /" dacă site-ul trebuie indexat.',
                ];
            }
        }

        if (stripos($body, 'Sitemap:') !== false) {
            $result['info'][] = 'robots.txt conține referință către Sitemap.';
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
                'fix' => 'Adaugă „Sitemap: ' . home_url('/sitemap_index.xml') . '" la sfârșitul robots.txt. Plugin-urile SEO fac asta automat.',
            ];
        }

        if (stripos($body, 'Disallow: /wp-content/uploads') !== false) {
            $result['issues'][] = [
                'severity' => 'medium',
                'title' => 'Uploads blocate în robots.txt',
                'fix' => 'Imaginile din uploads nu vor fi indexate în Google Images. Elimină regula dacă nu e intenționat.',
            ];
        }

        if (stripos($body, 'Disallow: /?') !== false || stripos($body, 'Disallow: /*?') !== false) {
            $result['info'][] = 'Parametrii URL blocați (bine – previne conținut duplicat).';
        }

        return $result;
    }

    private function check_sitemap() {
        $result = [
            'found' => false,
            'url' => '',
            'type' => '',
            'urls_count' => 0,
            'issues' => [],
            'info' => [],
        ];

        $found_url = '';
        $found_body = '';

        $sitemap_paths = [
            'sitemap_index.xml',
            'sitemap.xml',
            'wp-sitemap.xml',
        ];

        foreach ($sitemap_paths as $path) {
            $file = ABSPATH . $path;
            if (file_exists($file) && is_readable($file)) {
                $found_url = home_url('/' . $path);
                $found_body = file_get_contents($file);
                break;
            }
        }

        if (empty($found_body)) {
            $seo_plugins = [
                'wordpress-seo/wp-seo.php' => 'Yoast SEO',
                'seo-by-rank-math/rank-math.php' => 'Rank Math',
                'all-in-one-seo-pack/all_in_one_seo_pack.php' => 'All in One SEO',
            ];
            $active = get_option('active_plugins', []);
            $has_seo_plugin = false;
            foreach ($seo_plugins as $file => $name) {
                if (in_array($file, $active)) {
                    $has_seo_plugin = true;
                    $result['info'][] = "$name activ – generează sitemap dinamic.";
                    $found_url = home_url('/sitemap_index.xml');
                    $result['found'] = true;
                    break;
                }
            }

            if (!$has_seo_plugin) {
                if (function_exists('wp_sitemaps_get_server')) {
                    $server = wp_sitemaps_get_server();
                    if ($server) {
                        $result['info'][] = 'WordPress 5.5+ sitemap nativ activ (wp-sitemap.xml).';
                        $found_url = home_url('/wp-sitemap.xml');
                        $result['found'] = true;
                    }
                }
            }
        }

        if (!empty($found_body)) {
            $result['found'] = true;
            $result['url'] = $found_url;

            if (strpos($found_body, '<sitemapindex') !== false) {
                $result['type'] = 'Sitemap Index';
                preg_match_all('/<sitemap>/', $found_body, $m);
                $result['urls_count'] = count($m[0]);
                $result['info'][] = 'Tip: Sitemap Index cu ' . $result['urls_count'] . ' sub-sitemaps.';
            } elseif (strpos($found_body, '<urlset') !== false) {
                $result['type'] = 'Sitemap standard';
                preg_match_all('/<url>/', $found_body, $m);
                $result['urls_count'] = count($m[0]);
                $result['info'][] = 'Tip: Sitemap standard cu ' . $result['urls_count'] . ' URL-uri.';
            } else {
                $result['issues'][] = [
                    'severity' => 'high',
                    'title' => 'Sitemap invalid – nu conține structură XML corectă',
                    'fix' => 'Regenerează sitemap-ul din setările pluginului SEO (Yoast/Rank Math).',
                ];
            }

            $body_size = strlen($found_body);
            if ($body_size > 50 * 1024 * 1024) {
                $result['issues'][] = [
                    'severity' => 'medium',
                    'title' => 'Sitemap prea mare: ' . size_format($body_size),
                    'fix' => 'Google acceptă max 50MB/50.000 URL-uri per sitemap. Împarte în mai multe fișiere.',
                ];
            }
        } elseif (!$result['found']) {
            $result['issues'][] = [
                'severity' => 'high',
                'title' => 'Niciun sitemap.xml găsit',
                'fix' => 'Instalează Yoast SEO sau Rank Math pentru sitemap automat. WordPress 5.5+ include wp-sitemap.xml nativ.',
            ];
        } else {
            $result['info'][] = 'Sitemap URL: ' . $found_url;
            $result['info'][] = 'Sitemap-ul e generat dinamic – nu poate fi citit din fișier. Verifică URL-ul manual.';
        }

        return $result;
    }
}
