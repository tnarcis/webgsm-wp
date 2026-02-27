<?php
if (!defined('ABSPATH')) exit;

class WebGSM_Site_Audit_SEO {

    public function __construct() {
        add_action('wp_ajax_webgsm_audit_seo_scan', [$this, 'ajax_scan']);
    }

    public function ajax_scan() {
        check_ajax_referer('webgsm_site_audit', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Forbidden');

        $issues = [];

        $has_rankmath = defined('RANK_MATH_VERSION') || function_exists('rank_math');
        $has_acf = function_exists('acf_get_field_groups') && function_exists('acf_get_fields') && function_exists('get_field');

        $pages = get_posts([
            'post_type' => ['post', 'page', 'product'],
            'post_status' => 'publish',
            'numberposts' => 300,
            'fields' => 'ids',
        ]);

        foreach ($pages as $id) {
            $title = get_the_title($id);
            $url = get_permalink($id);
            $content = get_post_field('post_content', $id);

            $meta_desc = get_post_meta($id, '_yoast_wpseo_metadesc', true);
            if (empty($meta_desc)) {
                $meta_desc = get_post_meta($id, 'rank_math_description', true);
            }

            $title_len = mb_strlen($title);
            if ($title_len < 30) {
                $issues[] = ['type' => 'title_short', 'severity' => 'medium', 'title' => "Titlu prea scurt ($title_len car.)", 'url' => $url, 'page' => $title, 'fix' => 'Titlul ideal SEO: 30-60 caractere. Include cuvinte cheie relevante.'];
            } elseif ($title_len > 70) {
                $issues[] = ['type' => 'title_long', 'severity' => 'low', 'title' => "Titlu prea lung ($title_len car.)", 'url' => $url, 'page' => $title, 'fix' => 'Google trunchiază la ~60 caractere. Scurtează titlul.'];
            }

            if (empty($meta_desc)) {
                $issues[] = ['type' => 'no_meta_desc', 'severity' => 'medium', 'title' => 'Lipsește meta description', 'url' => $url, 'page' => $title, 'fix' => 'Adaugă meta description 120-160 caractere cu cuvinte cheie.'];
            } else {
                $desc_len = mb_strlen($meta_desc);
                if ($desc_len < 120 || $desc_len > 160) {
                    $issues[] = ['type' => 'meta_desc_length', 'severity' => 'low', 'title' => "Meta description: $desc_len car.", 'url' => $url, 'page' => $title, 'fix' => 'Meta description ideal: 120-160 caractere.'];
                }
            }

            if (!empty($content)) {
                $h1_count = substr_count(strtolower($content), '<h1');
                if ($h1_count > 1) {
                    $issues[] = ['type' => 'multiple_h1', 'severity' => 'medium', 'title' => "Multiple H1 ($h1_count)", 'url' => $url, 'page' => $title, 'fix' => 'Folosește un singur H1 per pagină.'];
                }

                $img_no_alt = preg_match_all('/<img[^>]+(?:alt\s*=\s*["\']["\']|(?!alt\s*=))[^>]*>/i', $content);
                if ($img_no_alt > 0) {
                    $issues[] = ['type' => 'img_no_alt', 'severity' => 'medium', 'title' => "$img_no_alt imagini fără alt text", 'url' => $url, 'page' => $title, 'fix' => 'Adaugă atribute alt descriptive pe toate imaginile.'];
                }

                $word_count = str_word_count(wp_strip_all_tags($content));
                $post_type = get_post_type($id);
                if ($post_type !== 'product' && $word_count < 300 && $word_count > 0) {
                    $issues[] = ['type' => 'thin_content', 'severity' => 'low', 'title' => "Conținut subțire ($word_count cuvinte)", 'url' => $url, 'page' => $title, 'fix' => 'Conținutul ideal are 300+ cuvinte. Adaugă text relevant.'];
                }
            }

            // --- Rank Math specific (dacă este activ) ---
            if ($has_rankmath) {
                // Focus keyword
                $focus = get_post_meta($id, 'rank_math_focus_keyword', true);
                if (empty($focus)) {
                    $issues[] = [
                        'type' => 'rankmath_no_focus',
                        'severity' => 'low',
                        'title' => 'Rank Math: lipsește Focus Keyword',
                        'url' => $url,
                        'page' => $title,
                        'fix' => 'În editor, tab-ul Rank Math → setează un cuvânt cheie principal (Focus Keyword) pentru această pagină.',
                    ];
                }

                // Titlu / descriere personalizate în Rank Math (opțional, doar info)
                $rm_title = get_post_meta($id, 'rank_math_title', true);
                $rm_desc  = get_post_meta($id, 'rank_math_description', true);
                if (empty($rm_title) && empty($rm_desc) && empty($focus)) {
                    // Evităm să spamăm prea mult – deja avem alte issues de titlu/descriere
                }
            }

            // --- ACF: câmpuri OBLIGATORII necompletate pe această pagină ---
            if ($has_acf) {
                $missing_required = 0;
                $groups = acf_get_field_groups(['post_id' => $id]);
                if (is_array($groups)) {
                    foreach ($groups as $group) {
                        $fields = acf_get_fields($group);
                        if (!is_array($fields)) continue;
                        foreach ($fields as $field) {
                            if (empty($field['required'])) continue;
                            // Ignorăm field-uri non-editabile (tab, message etc.)
                            if (!empty($field['type']) && in_array($field['type'], ['tab', 'message'], true)) continue;
                            $val = get_field($field['name'], $id);
                            if ($val === null || $val === '' || $val === false) {
                                $missing_required++;
                            }
                        }
                    }
                }

                if ($missing_required > 0) {
                    $issues[] = [
                        'type' => 'acf_missing_required',
                        'severity' => 'low',
                        'title' => "ACF: $missing_required câmpuri obligatorii necompletate",
                        'url' => $url,
                        'page' => $title,
                        'value' => $missing_required,
                        'fix' => 'Deschide această pagină în editor și verifică grupurile de câmpuri ACF – câmpurile marcate cu * (obligatorii) trebuie completate.',
                    ];
                }
            }
        }

        if (!get_option('blog_public')) {
            $issues[] = ['type' => 'search_engines', 'severity' => 'high', 'title' => 'Motorii de căutare sunt blocați (noindex)', 'url' => admin_url('options-reading.php'), 'page' => 'Setări', 'fix' => 'Debifează „Descurajează motoarele de căutare" în Setări → Citire.'];
        }

        $home_id = get_option('page_on_front');
        if (!$home_id && get_option('show_on_front') === 'page') {
            $issues[] = ['type' => 'no_homepage', 'severity' => 'high', 'title' => 'Pagină de start neconfigurată', 'url' => admin_url('options-reading.php'), 'page' => '', 'fix' => 'Setează o pagină de start din Setări → Citire.'];
        }

        $permalink = get_option('permalink_structure');
        if (empty($permalink)) {
            $issues[] = ['type' => 'plain_permalinks', 'severity' => 'high', 'title' => 'Permalink-uri implicite (nu sunt SEO-friendly)', 'url' => admin_url('options-permalink.php'), 'page' => 'Permalinks', 'fix' => 'Schimbă la „Post name" din Setări → Permalinks.'];
        }

        wp_send_json_success(['issues' => $issues, 'count' => count($issues)]);
    }
}
