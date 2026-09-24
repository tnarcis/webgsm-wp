<?php
/**
 * WebGSM Repair Reel — fața publică (homepage + /r/{model}-{problema}).
 *
 * Citește produsele Woo deja sincronizate. Nu copiază stoc, nu cere API nou,
 * nu înlocuiește tema sau magazinul (/shop).
 *
 * @package WebGSM
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WEBGSM_RR_REWRITE_VER', '20260821');
define('WEBGSM_RR_REPAIR_SLUG', 'estimeaza-reparatia');

/**
 * Slug pagină reparații indexabilă (sub home, în sitemap Rank Math).
 */
function webgsm_rr_repair_page_slug() {
    return WEBGSM_RR_REPAIR_SLUG;
}

function webgsm_rr_is_repair_landing() {
    if (is_admin()) {
        return false;
    }
    return is_page(webgsm_rr_repair_page_slug());
}

function webgsm_rr_repair_landing_url() {
    $page = get_page_by_path(webgsm_rr_repair_page_slug());
    if ($page && $page->post_status === 'publish') {
        return get_permalink($page);
    }
    return home_url('/' . webgsm_rr_repair_page_slug() . '/');
}

function webgsm_rr_repair_seo_title() {
    return 'Estimează reparația telefonului | WebGSM Timișoara';
}

function webgsm_rr_repair_seo_description() {
    return 'Calculează costul reparației telefonului — ecran, baterie, carcasă. Preț live piesă + manoperă din gestiune, Timișoara. Comandă piesa sau trimite telefonul la service.';
}

/**
 * UI reparații activ (landing, /r/ sau filter explicit).
 */
function webgsm_rr_repair_ui_enabled() {
    if (webgsm_rr_is_repair_landing() || get_query_var('webgsm_repair_slug')) {
        return true;
    }
    return (bool) apply_filters('webgsm_rr_services_public', false);
}

/** @deprecated Folosește webgsm_rr_repair_ui_enabled() */
function webgsm_rr_services_public() {
    return webgsm_rr_repair_ui_enabled();
}

function webgsm_rr_home_seo_title() {
    return 'Piese telefoane Timișoara | WebGSM';
}

function webgsm_rr_home_seo_description() {
    return 'Magazin online piese GSM — ecrane, baterii, componente. Stoc live, prețuri clare, livrare rapidă din Timișoara.';
}

/**
 * Categorii Woo de top pentru linkuri pe homepage (exclude servicii).
 *
 * @return array<int, array{label: string, url: string}>
 */
function webgsm_rr_shop_nav_links($limit = 8) {
    $limit = max(1, min(12, (int) $limit));
    $cache = get_transient('webgsm_rr_shop_nav_' . $limit);
    if (is_array($cache)) {
        return $cache;
    }

    $links = array();
    if (!taxonomy_exists('product_cat')) {
        return $links;
    }

    $terms = get_terms(array(
        'taxonomy'   => 'product_cat',
        'parent'     => 0,
        'hide_empty' => true,
        'number'     => 24,
        'orderby'    => 'count',
        'order'      => 'DESC',
    ));

    if (!is_wp_error($terms)) {
        foreach ($terms as $term) {
            $slug = strtolower($term->slug);
            $name = function_exists('remove_accents')
                ? strtolower(remove_accents($term->name))
                : strtolower($term->name);
            if (strpos($slug, 'servici') !== false || strpos($name, 'servici') !== false) {
                continue;
            }
            if (strpos($slug, 'reparat') !== false || strpos($name, 'reparat') !== false) {
                continue;
            }
            $url = get_term_link($term);
            if (is_wp_error($url)) {
                continue;
            }
            $links[] = array(
                'label' => $term->name,
                'url'   => $url,
            );
            if (count($links) >= $limit) {
                break;
            }
        }
    }

    set_transient('webgsm_rr_shop_nav_' . $limit, $links, HOUR_IN_SECONDS);
    return $links;
}

/**
 * True pe landing reparații (/estimeaza-reparatia/) sau /r/...
 */
function webgsm_rr_is_reel_request() {
    if (is_admin() || (defined('REST_REQUEST') && REST_REQUEST && !wp_doing_ajax())) {
        return false;
    }
    if (get_query_var('webgsm_repair_slug')) {
        return true;
    }
    return webgsm_rr_is_repair_landing();
}

function webgsm_rr_brands() {
    return array(
        'apple'   => array('label' => 'Apple', 'needles' => array('iphone')),
        'samsung' => array('label' => 'Samsung', 'needles' => array('galaxy', 'samsung')),
        'xiaomi'  => array('label' => 'Xiaomi', 'needles' => array('xiaomi', 'redmi', 'poco')),
    );
}

function webgsm_rr_problems() {
    return array(
        'ecran'   => array(
            'label'      => 'Ecran',
            'hint'       => 'Spart, linii, negru',
            'cat_prefix' => 'ecrane',
        ),
        'baterie' => array(
            'label'      => 'Baterie',
            'hint'       => 'Se descarcă, se umflă',
            'cat_prefix' => 'baterii',
        ),
        'carcasa' => array(
            'label'      => 'Carcasă',
            'hint'       => 'Spartă, îndoită, capac',
            'cat_prefix' => 'carcase',
        ),
        'alte'    => array(
            'label'      => 'Alte piese',
            'hint'       => 'Cameră, mufă, flex, difuzor…',
            'cat_prefix' => 'alte',
        ),
    );
}

function webgsm_rr_normalize_problem($problem) {
    $problem = sanitize_key((string) $problem);
    $all     = webgsm_rr_problems();
    return isset($all[$problem]) ? $problem : 'ecran';
}

function webgsm_rr_problem_label($problem) {
    $all = webgsm_rr_problems();
    $key = webgsm_rr_normalize_problem($problem);
    return $all[$key]['label'];
}

/**
 * Rezolvă taxonomia Woo pentru un atribut logic.
 */
function webgsm_rr_taxonomy($logical) {
    $map = array(
        'model'      => array('pa_model-compatibil', 'pa_model_compatibil', 'pa_model'),
        'calitate'   => array('pa_calitate', 'pa_quality', 'pa_calitate-baterie'),
        'tehnologie' => array('pa_tehnologie', 'pa_technology', 'pa_tip-tehnologie'),
        'brand'      => array('pa_brand-piesa', 'pa_brand_piesa', 'pa_brand'),
    );
    if (!isset($map[$logical])) {
        return '';
    }
    foreach ($map[$logical] as $tax) {
        if (taxonomy_exists($tax)) {
            return $tax;
        }
    }
    return $map[$logical][0];
}

function webgsm_rr_whatsapp_number() {
    $raw = (string) get_option('webgsm_repair_whatsapp', '');
    if ($raw === '') {
        $raw = (string) get_option('woocommerce_store_phone', '');
    }
    $digits = preg_replace('/\D+/', '', $raw);
    if ($digits === '') {
        $digits = '';
    } elseif (strpos($digits, '0') === 0 && strlen($digits) === 10) {
        $digits = '4' . $digits;
    } elseif (strlen($digits) === 9) {
        $digits = '40' . $digits;
    }
    return apply_filters('webgsm_repair_whatsapp', $digits);
}

function webgsm_rr_shop_url() {
    $fallback = home_url('/shop/');
    if (!function_exists('wc_get_page_id')) {
        return $fallback;
    }
    $shop_id = (int) wc_get_page_id('shop');
    if ($shop_id <= 0) {
        return $fallback;
    }
    $url  = get_permalink($shop_id);
    $home = trailingslashit(home_url('/'));
    if (!$url || trailingslashit($url) === $home) {
        $post = get_post($shop_id);
        if ($post && $post->post_name && $post->post_name !== 'home') {
            return home_url('/' . $post->post_name . '/');
        }
        return $fallback;
    }
    return $url;
}

function webgsm_rr_cart_url() {
    return function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/');
}

function webgsm_rr_register_rewrites() {
    add_rewrite_tag('%webgsm_repair_slug%', '([^&]+)');
    add_rewrite_rule('^r/([^/]+)/?$', 'index.php?webgsm_repair_slug=$matches[1]', 'top');
}

add_action('init', 'webgsm_rr_register_rewrites', 11);

add_filter('query_vars', function ($vars) {
    $vars[] = 'webgsm_repair_slug';
    return $vars;
});

add_action('init', function () {
    if (get_option('webgsm_rr_cat_ids_v2') !== '1') {
        delete_transient('webgsm_rr_cat_ids');
        update_option('webgsm_rr_cat_ids_v2', '1');
    }
}, 5);

add_action('init', function () {
    if (get_option('webgsm_rr_rewrite_ver') !== WEBGSM_RR_REWRITE_VER) {
        webgsm_rr_register_rewrites();
        flush_rewrite_rules(false);
        update_option('webgsm_rr_rewrite_ver', WEBGSM_RR_REWRITE_VER);
    }
}, 20);

/**
 * Creează pagina indexabilă /estimeaza-reparatia/ (o singură dată).
 */
add_action('init', function () {
    if (get_option('webgsm_rr_repair_page_v1') === '1') {
        return;
    }
    if (!function_exists('wp_insert_post')) {
        return;
    }

    $slug     = webgsm_rr_repair_page_slug();
    $existing = get_page_by_path($slug);
    $page_id  = $existing ? (int) $existing->ID : 0;

    if (!$page_id) {
        $page_id = wp_insert_post(array(
            'post_title'   => 'Estimează reparația telefonului',
            'post_name'    => $slug,
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => '',
        ), true);
        if (is_wp_error($page_id)) {
            return;
        }
    }

    update_post_meta($page_id, '_wp_page_template', 'page-estimeaza-reparatia.php');
    update_post_meta($page_id, 'rank_math_title', webgsm_rr_repair_seo_title());
    update_post_meta($page_id, 'rank_math_description', webgsm_rr_repair_seo_description());
    update_post_meta($page_id, 'rank_math_focus_keyword', 'estimare reparatie telefon timisoara');
    update_post_meta($page_id, 'rank_math_robots', array('index' => 'index', 'follow' => 'follow'));

    $front_id = (int) get_option('page_on_front');
    if ($front_id === (int) $page_id) {
        $home = get_page_by_path('homepage');
        if (!$home) {
            $home = get_post(2534);
        }
        if ($home && $home->post_status === 'publish') {
            update_option('page_on_front', (int) $home->ID);
        } elseif ($shop_id = (int) (function_exists('wc_get_page_id') ? wc_get_page_id('shop') : 0)) {
            update_option('page_on_front', $shop_id);
        }
    }

    update_option('webgsm_rr_repair_page_v1', '1');
}, 30);

/**
 * Dacă pagina de reparații a ajuns setată ca homepage — revino la pagina HomePage.
 */
add_action('init', function () {
    if (get_option('webgsm_rr_front_guard_done') === '1') {
        return;
    }
    $repair = get_page_by_path(webgsm_rr_repair_page_slug());
    if (!$repair || $repair->post_status !== 'publish') {
        update_option('webgsm_rr_front_guard_done', '1', false);
        return;
    }
    if ((int) get_option('page_on_front') !== (int) $repair->ID) {
        update_option('webgsm_rr_front_guard_done', '1', false);
        return;
    }
    $home = get_page_by_path('homepage');
    if (!$home) {
        $home = get_post(2534);
    }
    if ($home && $home->post_status === 'publish') {
        update_option('page_on_front', (int) $home->ID);
    }
    update_option('webgsm_rr_front_guard_done', '1', false);
}, 31);

add_filter('redirect_canonical', function ($redirect, $requested) {
    if (get_query_var('webgsm_repair_slug')) {
        return false;
    }
    return $redirect;
}, 10, 2);

add_filter('pre_handle_404', function ($preempt, $wp_query) {
    if (get_query_var('webgsm_repair_slug')) {
        $wp_query->is_404 = false;
        return true;
    }
    return $preempt;
}, 10, 2);

add_action('template_redirect', function () {
    if (!get_query_var('webgsm_repair_slug')) {
        return;
    }
    global $wp_query;
    $wp_query->is_404 = false;
    status_header(200);
}, 0);

add_filter('template_include', function ($template) {
    $slug = get_query_var('webgsm_repair_slug');
    if ($slug) {
        $file = get_stylesheet_directory() . '/templates/repair-reel.php';
        if (file_exists($file)) {
            return $file;
        }
    }
    return $template;
}, 999);

add_filter('body_class', function ($classes) {
    if (webgsm_rr_is_reel_request()) {
        $classes[] = 'webgsm-reel';
        if (get_query_var('webgsm_repair_slug')) {
            $classes[] = 'webgsm-reel-deep';
        } elseif (webgsm_rr_is_repair_landing()) {
            $classes[] = 'webgsm-reel-repair-landing';
        } else {
            $classes[] = 'webgsm-reel-home';
        }
    }
    return $classes;
});

add_action('wp_enqueue_scripts', function () {
    if (!webgsm_rr_is_reel_request()) {
        return;
    }
    $dir = get_stylesheet_directory();
    $uri = get_stylesheet_directory_uri();
    $css = $dir . '/assets/css/repair-reel.css';
    $js  = $dir . '/assets/js/repair-reel.js';
    $css_ver = file_exists($css) ? (string) filemtime($css) : '1';
    $js_ver  = file_exists($js) ? (string) filemtime($js) : '1';

    wp_enqueue_style(
        'webgsm-repair-reel-fonts',
        'https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,400;0,500;0,700;1,400&family=Oswald:wght@500;700&display=swap',
        array(),
        null
    );
    wp_enqueue_style('webgsm-repair-reel', $uri . '/assets/css/repair-reel.css', array('webgsm-repair-reel-fonts'), $css_ver);
    wp_enqueue_style('webgsm-montaj', $uri . '/assets/css/webgsm-montaj.css', array('webgsm-repair-reel'), '1.0');
    wp_enqueue_script('webgsm-repair-reel', $uri . '/assets/js/repair-reel.js', array(), $js_ver, true);

    $boot = webgsm_rr_bootstrap_state();
    wp_localize_script('webgsm-repair-reel', 'webgsmReel', $boot);
}, 30);

add_filter('document_title_parts', function ($parts) {
    $parsed = webgsm_rr_current_route();
    if (!$parsed) {
        if (webgsm_rr_is_repair_landing()) {
            $parts['title'] = 'Estimează reparația telefonului';
            $parts['site']  = 'WebGSM Timișoara';
            return $parts;
        }
        if (is_front_page()) {
            $parts['title'] = 'Piese telefoane Timișoara';
            $parts['site']  = 'WebGSM';
        }
        return $parts;
    }
    $model_name = $parsed['model_name'] ? $parsed['model_name'] : $parsed['model'];
    $problem    = $parsed['problem'] === 'baterie' ? 'baterie' : 'ecran';
    $parts['title'] = sprintf('Schimbare %s %s Timișoara', $problem, $model_name);
    $parts['site']  = 'WebGSM';
    return $parts;
});

add_filter('rank_math/frontend/title', function ($title) {
    if (!webgsm_rr_is_reel_request()) {
        return $title;
    }
    if (webgsm_rr_is_repair_landing() && !webgsm_rr_current_route()) {
        return webgsm_rr_repair_seo_title();
    }
    if (!webgsm_rr_current_route() && is_front_page()) {
        return webgsm_rr_home_seo_title();
    }
    return $title;
}, 99);

add_filter('rank_math/frontend/description', function ($description) {
    if (!webgsm_rr_is_reel_request()) {
        return $description;
    }
    if (webgsm_rr_is_repair_landing() && !webgsm_rr_current_route()) {
        return webgsm_rr_repair_seo_description();
    }
    if (!webgsm_rr_current_route() && is_front_page()) {
        return webgsm_rr_home_seo_description();
    }
    return $description;
}, 99);

add_filter('rank_math/frontend/canonical', function ($url) {
    if (get_query_var('webgsm_repair_slug')) {
        return webgsm_rr_repair_landing_url();
    }
    return $url;
}, 99);

add_filter('rank_math/frontend/robots', function ($robots) {
    if (get_query_var('webgsm_repair_slug')) {
        return array('index' => 'noindex', 'follow' => 'follow');
    }
    return $robots;
}, 99);

add_filter('rank_math/frontend/breadcrumb/items', function ($items, $class) {
    unset($class);
    if (!webgsm_rr_is_repair_landing() || webgsm_rr_current_route()) {
        return $items;
    }
    return array(
        array(
            0 => home_url('/'),
            1 => 'Acasă',
            'hide_in_schema' => false,
        ),
        array(
            0 => webgsm_rr_repair_landing_url(),
            1 => 'Estimează reparația',
            'hide_in_schema' => false,
        ),
    );
}, 99, 2);

add_filter('wpseo_title', function ($title) {
    if (!webgsm_rr_is_reel_request()) {
        return $title;
    }
    if (webgsm_rr_is_repair_landing() && !webgsm_rr_current_route()) {
        return webgsm_rr_repair_seo_title();
    }
    if (!webgsm_rr_current_route() && is_front_page()) {
        return webgsm_rr_home_seo_title();
    }
    return $title;
}, 99);

add_filter('wpseo_metadesc', function ($description) {
    if (!webgsm_rr_is_reel_request()) {
        return $description;
    }
    if (webgsm_rr_is_repair_landing() && !webgsm_rr_current_route()) {
        return webgsm_rr_repair_seo_description();
    }
    if (!webgsm_rr_current_route() && is_front_page()) {
        return webgsm_rr_home_seo_description();
    }
    return $description;
}, 99);

add_filter('wpseo_robots', function ($robots) {
    if (get_query_var('webgsm_repair_slug')) {
        return 'noindex, follow';
    }
    return $robots;
}, 99);

function webgsm_rr_sanitize_price_option($value) {
    $value = is_string($value) ? trim(str_replace(',', '.', $value)) : $value;
    if ($value === '' || $value === null) {
        return '';
    }
    if (!is_numeric($value)) {
        return '';
    }
    $num = (float) $value;
    return $num > 0 ? (string) $num : '';
}

add_action('admin_init', function () {
    register_setting('general', 'webgsm_repair_whatsapp', array(
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => '',
    ));
    register_setting('general', 'webgsm_labor_default_ecran', array(
        'type'              => 'string',
        'sanitize_callback' => 'webgsm_rr_sanitize_price_option',
        'default'           => '',
    ));
    register_setting('general', 'webgsm_labor_default_baterie', array(
        'type'              => 'string',
        'sanitize_callback' => 'webgsm_rr_sanitize_price_option',
        'default'           => '',
    ));
    add_settings_field(
        'webgsm_repair_whatsapp',
        'WhatsApp Repair Reel',
        function () {
            $val = esc_attr((string) get_option('webgsm_repair_whatsapp', ''));
            echo '<input type="text" id="webgsm_repair_whatsapp" name="webgsm_repair_whatsapp" value="' . $val . '" class="regular-text" placeholder="407xxxxxxxx" />';
            echo '<p class="description">Numărul pentru „Vreau să-l repar” (internațional, fără +). Dacă e gol, se folosește telefonul magazinului Woo.</p>';
        },
        'general'
    );
    add_settings_field(
        'webgsm_labor_default_ecran',
        'Manoperă implicită — ecran (RON)',
        function () {
            $val = esc_attr((string) get_option('webgsm_labor_default_ecran', ''));
            echo '<input type="number" step="0.01" min="0" id="webgsm_labor_default_ecran" name="webgsm_labor_default_ecran" value="' . $val . '" class="small-text" placeholder="150" />';
            echo '<p class="description">Fallback dacă piesa nu are manoperă și nu există produs în <strong>Servicii → Reparații</strong> pentru model.</p>';
        },
        'general'
    );
    add_settings_field(
        'webgsm_labor_default_baterie',
        'Manoperă implicită — baterie (RON)',
        function () {
            $val = esc_attr((string) get_option('webgsm_labor_default_baterie', ''));
            echo '<input type="number" step="0.01" min="0" id="webgsm_labor_default_baterie" name="webgsm_labor_default_baterie" value="' . $val . '" class="small-text" placeholder="80" />';
            echo '<p class="description">La fel ca mai sus, pentru schimb baterie. Poți crea produse serviciu în categoria Reparații (cu model + tip reparație) — au prioritate față de valorile de aici.</p>';
        },
        'general'
    );
});

add_action('save_post_product', 'webgsm_rr_bust_cache');
add_action('woocommerce_update_product', 'webgsm_rr_bust_cache');
add_action('edited_terms', 'webgsm_rr_bust_cache');

function webgsm_rr_bust_cache() {
    foreach (array_keys(webgsm_rr_brands()) as $brand) {
        delete_transient('webgsm_rr_models_' . $brand);
    }
    delete_transient('webgsm_rr_cat_ids');
}

function webgsm_rr_parse_slug($slug) {
    $slug = sanitize_title((string) $slug);
    if ($slug === '') {
        return null;
    }
    if (!preg_match('/^(.*)-(ecran|baterie)$/', $slug, $m)) {
        return null;
    }
    $model = trim($m[1], '-');
    if ($model === '') {
        return null;
    }
    return array(
        'model'   => $model,
        'problem' => $m[2],
    );
}

function webgsm_rr_current_route() {
    $slug = get_query_var('webgsm_repair_slug');
    if (!$slug) {
        return null;
    }
    $parsed = webgsm_rr_parse_slug($slug);
    if (!$parsed) {
        return null;
    }
    $term = webgsm_rr_find_model_term($parsed['model']);
    $parsed['model_name'] = $term ? $term->name : webgsm_rr_humanize_slug($parsed['model']);
    $parsed['model_slug'] = $term ? $term->slug : $parsed['model'];
    $parsed['brand']      = webgsm_rr_brand_from_model($parsed['model_slug'] . ' ' . $parsed['model_name']);
    $sku = isset($_GET['sku']) ? sanitize_text_field(wp_unslash($_GET['sku'])) : '';
    $parsed['sku'] = $sku;
    return $parsed;
}

function webgsm_rr_humanize_slug($slug) {
    $slug = str_replace('-', ' ', (string) $slug);
    return ucwords($slug);
}

function webgsm_rr_brand_from_model($haystack) {
    $hay = strtolower((string) $haystack);
    foreach (webgsm_rr_brands() as $key => $meta) {
        foreach ($meta['needles'] as $needle) {
            if (strpos($hay, $needle) !== false) {
                return $key;
            }
        }
    }
    return '';
}

function webgsm_rr_find_model_term($model_slug) {
    $tax = webgsm_rr_taxonomy('model');
    if (!$tax || !taxonomy_exists($tax)) {
        return null;
    }
    $term = get_term_by('slug', $model_slug, $tax);
    if ($term && !is_wp_error($term)) {
        return $term;
    }
    $terms = get_terms(array(
        'taxonomy'   => $tax,
        'hide_empty' => false,
        'number'     => 400,
    ));
    if (is_wp_error($terms) || empty($terms)) {
        return null;
    }
    foreach ($terms as $candidate) {
        if (sanitize_title($candidate->name) === $model_slug) {
            return $candidate;
        }
    }
    return null;
}

/**
 * ID-uri product_cat pentru ecrane sau baterii (fără „Baterii Piese”).
 */
function webgsm_rr_problem_cat_ids($problem) {
    $problem = webgsm_rr_normalize_problem($problem);
    $cache   = get_transient('webgsm_rr_cat_ids');
    if (!is_array($cache)) {
        $cache = array(
            'ecran'   => array(),
            'baterie' => array(),
            'carcasa' => array(),
            'alte'    => array(),
        );
        $terms = get_terms(array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'number'     => 500,
        ));
        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                $slug = strtolower($term->slug);
                $name = function_exists('remove_accents')
                    ? strtolower(remove_accents($term->name))
                    : strtolower($term->name);
                $hay  = $slug . ' ' . $name;

                $is_piese_baterie = (strpos($slug, 'baterii-piese') !== false || strpos($name, 'baterii piese') !== false);
                if ($is_piese_baterie) {
                    continue;
                }

                if (strpos($hay, 'ecran') !== false) {
                    $cache['ecran'][] = (int) $term->term_id;
                    continue;
                }
                if (strpos($hay, 'bater') !== false) {
                    $cache['baterie'][] = (int) $term->term_id;
                    continue;
                }
                if (strpos($hay, 'carcas') !== false) {
                    $cache['carcasa'][] = (int) $term->term_id;
                    continue;
                }
                if (
                    strpos($hay, 'flex') !== false
                    || strpos($hay, 'camer') !== false
                    || strpos($hay, 'muf') !== false
                    || strpos($hay, 'incarc') !== false
                    || strpos($hay, 'difuz') !== false
                    || strpos($hay, 'anten') !== false
                    || strpos($hay, 'senzor') !== false
                    || strpos($hay, 'buton') !== false
                    || strpos($hay, 'vibrator') !== false
                ) {
                    $cache['alte'][] = (int) $term->term_id;
                }
            }
        }
        set_transient('webgsm_rr_cat_ids', $cache, 12 * HOUR_IN_SECONDS);
    }
    return isset($cache[$problem]) ? $cache[$problem] : array();
}

function webgsm_rr_all_part_cat_ids() {
    $ids = array();
    foreach (array_keys(webgsm_rr_problems()) as $problem) {
        $ids = array_merge($ids, webgsm_rr_problem_cat_ids($problem));
    }
    return array_values(array_unique(array_filter($ids)));
}

function webgsm_rr_get_models($brand) {
    $brand = sanitize_key($brand);
    $brands = webgsm_rr_brands();
    if (!isset($brands[$brand])) {
        return array();
    }

    $cached = get_transient('webgsm_rr_models_' . $brand);
    if (is_array($cached)) {
        return $cached;
    }

    $tax = webgsm_rr_taxonomy('model');
    if (!$tax || !taxonomy_exists($tax) || !class_exists('WooCommerce')) {
        return array();
    }

    $cat_ids = webgsm_rr_all_part_cat_ids();
    if (empty($cat_ids)) {
        $models = webgsm_rr_models_from_terms($brand, $tax);
        set_transient('webgsm_rr_models_' . $brand, $models, 15 * MINUTE_IN_SECONDS);
        return $models;
    }

    $q = new WP_Query(array(
        'post_type'              => 'product',
        'post_status'            => 'publish',
        'posts_per_page'         => 800,
        'fields'                 => 'ids',
        'no_found_rows'          => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => true,
        'tax_query'              => array(
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => $cat_ids,
            ),
        ),
    ));

    $needles = $brands[$brand]['needles'];
    $seen    = array();
    $models  = array();
    foreach ($q->posts as $pid) {
        $terms = get_the_terms((int) $pid, $tax);
        if (empty($terms) || is_wp_error($terms)) {
            continue;
        }
        foreach ($terms as $term) {
            if (isset($seen[$term->term_id])) {
                continue;
            }
            $hay = strtolower($term->slug . ' ' . $term->name);
            $ok  = false;
            foreach ($needles as $needle) {
                if (strpos($hay, $needle) !== false) {
                    $ok = true;
                    break;
                }
            }
            if (!$ok) {
                continue;
            }
            $seen[$term->term_id] = true;
            $models[] = array(
                'slug' => $term->slug,
                'name' => $term->name,
            );
        }
    }

    $models = webgsm_rr_sort_models($models);
    set_transient('webgsm_rr_models_' . $brand, $models, 15 * MINUTE_IN_SECONDS);
    return $models;
}

function webgsm_rr_models_from_terms($brand, $tax) {
    $brands  = webgsm_rr_brands();
    $needles = $brands[$brand]['needles'];
    $terms   = get_terms(array(
        'taxonomy'   => $tax,
        'hide_empty' => true,
        'number'     => 400,
    ));
    if (is_wp_error($terms) || empty($terms)) {
        return array();
    }
    $models = array();
    foreach ($terms as $term) {
        $hay = strtolower($term->slug . ' ' . $term->name);
        foreach ($needles as $needle) {
            if (strpos($hay, $needle) !== false) {
                $models[] = array('slug' => $term->slug, 'name' => $term->name);
                break;
            }
        }
    }
    return webgsm_rr_sort_models($models);
}

function webgsm_rr_sort_models($models) {
    usort($models, function ($a, $b) {
        $na = webgsm_rr_model_sort_key($a['name']);
        $nb = webgsm_rr_model_sort_key($b['name']);
        if ($na === $nb) {
            return strcasecmp($a['name'], $b['name']);
        }
        return $nb <=> $na;
    });
    return array_values($models);
}

function webgsm_rr_model_sort_key($name) {
    if (preg_match('/(\d{2})/', $name, $m)) {
        return (int) $m[1];
    }
    if (preg_match('/(\d+)/', $name, $m)) {
        return (int) $m[1];
    }
    return 0;
}

function webgsm_rr_quality_label(WC_Product $product, $problem) {
    $tech_tax = webgsm_rr_taxonomy('tehnologie');
    $qual_tax = webgsm_rr_taxonomy('calitate');
    $tech     = $tech_tax ? strtolower(trim($product->get_attribute($tech_tax))) : '';
    $qual     = $qual_tax ? trim($product->get_attribute($qual_tax)) : '';
    $qual_l   = strtolower($qual);

    $tech_compact = str_replace(array(' ', '-', '_'), '', $tech);

    if ($problem === 'ecran') {
        if (strpos($tech_compact, 'incell') !== false) {
            return 'In-Cell';
        }
        if (strpos($tech_compact, 'hardoled') !== false || (strpos($tech, 'hard') !== false && strpos($tech, 'oled') !== false)) {
            return 'Hard OLED';
        }
        if (strpos($tech_compact, 'softoled') !== false || (strpos($tech, 'soft') !== false && strpos($tech, 'oled') !== false)) {
            return 'Soft OLED';
        }
        if (strpos($tech, 'original') !== false || strpos($qual_l, 'original') !== false || strpos($qual_l, 'service pack') !== false) {
            return 'Original';
        }
        if ($tech !== '') {
            return $product->get_attribute($tech_tax);
        }
        if ($qual !== '') {
            return $qual;
        }
    } else {
        if ($qual !== '') {
            if (strpos($qual_l, 'original') !== false || strpos($qual_l, 'service pack') !== false) {
                return 'Original';
            }
            return $qual;
        }
        if (strpos($tech, 'original') !== false) {
            return 'Original';
        }
        if ($tech !== '') {
            return $product->get_attribute($tech_tax);
        }
    }

    $brand_tax = webgsm_rr_taxonomy('brand');
    $brand     = $brand_tax ? trim($product->get_attribute($brand_tax)) : '';
    if ($brand !== '') {
        return $brand;
    }
    if ($problem === 'carcasa' || $problem === 'alte') {
        $name = trim($product->get_name());
        if ($name !== '') {
            return wp_trim_words($name, 5, '…');
        }
    }
    return '';
}

function webgsm_rr_quality_rank($label) {
    $order = array(
        'In-Cell'    => 1,
        'Hard OLED'  => 2,
        'Soft OLED'  => 3,
        'Original'   => 4,
    );
    return isset($order[$label]) ? $order[$label] : 50;
}

function webgsm_rr_stock_status(WC_Product $product) {
    if (!$product->is_in_stock()) {
        return array('key' => 'nu', 'label' => 'Indisponibil');
    }
    $qty = $product->get_stock_quantity();
    if ($qty !== null && (int) $qty > 0 && (int) $qty <= 3) {
        return array('key' => 'redus', 'label' => 'Stoc redus');
    }
    $locatie = '';
    if (function_exists('get_field')) {
        $locatie = (string) get_field('locatie_stoc', $product->get_id());
    }
    if ($locatie === '') {
        $locatie = (string) get_post_meta($product->get_id(), 'locatie_stoc', true);
    }
    if (function_exists('webgsm_normalize_locatie_stoc')) {
        $locatie = webgsm_normalize_locatie_stoc($locatie);
    }
    if ($locatie === 'magazin_webgsm') {
        return array('key' => 'da', 'label' => 'În Timișoara');
    }
    return array('key' => 'da', 'label' => 'Disponibil');
}

function webgsm_rr_labor_from_part(WC_Product $product) {
    $id = $product->get_id();

    if (function_exists('get_field')) {
        $acf = get_field('manopera', $id);
        if ($acf !== '' && $acf !== null && is_numeric($acf) && (float) $acf > 0) {
            return (float) $acf;
        }
    }

    $keys = array('_webgsm_labor', 'webgsm_labor', 'manopera', '_manopera', 'labor_price');
    foreach ($keys as $key) {
        $val = get_post_meta($id, $key, true);
        if ($val === '' || $val === null) {
            continue;
        }
        if (is_numeric($val) && (float) $val > 0) {
            return (float) $val;
        }
    }
    return null;
}

/** @deprecated Use webgsm_rr_labor_from_part(). */
function webgsm_rr_labor(WC_Product $product) {
    return webgsm_rr_labor_from_part($product);
}

function webgsm_rr_labor_default($problem) {
    $problem = $problem === 'baterie' ? 'baterie' : 'ecran';
    $key     = $problem === 'baterie' ? 'webgsm_labor_default_baterie' : 'webgsm_labor_default_ecran';
    $val     = get_option($key, '');
    if ($val !== '' && is_numeric($val) && (float) $val > 0) {
        return (float) $val;
    }
    return null;
}

function webgsm_rr_service_cat_ids() {
    static $ids = null;
    if ($ids !== null) {
        return $ids;
    }
    $ids = array();
    foreach (array('reparatii', 'reparatii-service', 'servicii-reparatii') as $slug) {
        $term = get_term_by('slug', $slug, 'product_cat');
        if ($term && !is_wp_error($term)) {
            $ids[] = (int) $term->term_id;
        }
    }
    if (empty($ids)) {
        $parent = get_term_by('slug', 'servicii', 'product_cat');
        if ($parent && !is_wp_error($parent)) {
            $children = get_terms(array(
                'taxonomy'   => 'product_cat',
                'parent'     => (int) $parent->term_id,
                'hide_empty' => false,
            ));
            if (!is_wp_error($children)) {
                foreach ($children as $child) {
                    $hay = strtolower($child->slug . ' ' . $child->name);
                    if (strpos($hay, 'repar') !== false) {
                        $ids[] = (int) $child->term_id;
                    }
                }
            }
        }
    }
    $ids = array_values(array_unique(array_filter($ids)));
    return $ids;
}

function webgsm_rr_product_repair_type(WC_Product $product) {
    $id = (int) $product->get_id();

    if (function_exists('get_field')) {
        $acf = get_field('tip_reparatie', $id);
        if ($acf === 'ecran' || $acf === 'baterie') {
            return $acf;
        }
    }

    foreach (array('_webgsm_repair_type', 'webgsm_repair_type', 'tip_reparatie') as $key) {
        $val = get_post_meta($id, $key, true);
        if ($val === 'ecran' || $val === 'baterie') {
            return $val;
        }
    }

    $hay = strtolower($product->get_slug() . ' ' . $product->get_name());
    if (strpos($hay, 'bater') !== false) {
        return 'baterie';
    }
    if (strpos($hay, 'ecran') !== false || strpos($hay, 'display') !== false) {
        return 'ecran';
    }
    return '';
}

function webgsm_rr_get_service_labor($model_slug, $problem) {
    static $cache = array();
    $problem = $problem === 'baterie' ? 'baterie' : 'ecran';
    $key     = $model_slug . '|' . $problem;
    if (isset($cache[$key])) {
        return $cache[$key];
    }

    $empty = array(
        'amount'  => null,
        'id'      => 0,
        'sku'     => '',
        'label'   => '',
        'source'  => null,
    );

    if (!class_exists('WooCommerce')) {
        $cache[$key] = $empty;
        return $empty;
    }

    $cat_ids = webgsm_rr_service_cat_ids();
    $term    = webgsm_rr_find_model_term($model_slug);
    $tax     = webgsm_rr_taxonomy('model');
    if (empty($cat_ids) || !$term || !$tax || !taxonomy_exists($tax)) {
        $cache[$key] = $empty;
        return $empty;
    }

    $q = new WP_Query(array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => 20,
        'fields'         => 'ids',
        'no_found_rows'  => true,
        'tax_query'      => array(
            'relation' => 'AND',
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => $cat_ids,
            ),
            array(
                'taxonomy' => $tax,
                'field'    => 'term_id',
                'terms'    => array((int) $term->term_id),
            ),
        ),
    ));

    $match = $empty;
    foreach ($q->posts as $pid) {
        $product = wc_get_product($pid);
        if (!$product) {
            continue;
        }
        $ptype = webgsm_rr_product_repair_type($product);
        if ($ptype !== '' && $ptype !== $problem) {
            continue;
        }
        $amount = (float) wc_get_price_to_display($product);
        if ($amount <= 0) {
            continue;
        }
        $match = array(
            'amount' => $amount,
            'id'     => (int) $pid,
            'sku'    => (string) $product->get_sku(),
            'label'  => $product->get_name(),
            'source' => 'service',
        );
        if ($ptype === $problem) {
            break;
        }
    }

    $cache[$key] = apply_filters('webgsm_rr_service_labor', $match, $model_slug, $problem);
    return $cache[$key];
}

function webgsm_rr_resolve_labor(WC_Product $part, $model_slug, $problem) {
    $problem = webgsm_rr_normalize_problem($problem);

    if (function_exists('webgsm_montaj_for_product')) {
        $m = webgsm_montaj_for_product($part->get_id());
        if ($m['amount'] !== null && (float) $m['amount'] > 0) {
            $transport = function_exists('webgsm_montaj_transport_label')
                ? webgsm_montaj_transport_label($m['includes_transport'])
                : '';
            $label = 'Manoperă montaj';
            if ($transport !== '') {
                $label .= ' (' . $transport . ')';
            }
            return array(
                'amount'               => (float) $m['amount'],
                'id'                   => 0,
                'sku'                  => '',
                'label'                => $label,
                'source'               => 'montaj_meta',
                'includes_transport'   => $m['includes_transport'],
                'allow_microsoldering' => $m['allow_microsoldering'],
                'microsoldering_price' => $m['microsoldering_price'],
            );
        }
    }

    $amount = webgsm_rr_labor_from_part($part);
    if ($amount !== null) {
        return array(
            'amount'               => $amount,
            'id'                   => 0,
            'sku'                  => '',
            'label'                => 'Manoperă (piesă)',
            'source'               => 'part',
            'includes_transport'   => false,
            'allow_microsoldering' => false,
            'microsoldering_price' => 0,
        );
    }

    $service = webgsm_rr_get_service_labor($model_slug, $problem);
    if (!empty($service['amount'])) {
        $service['includes_transport']   = false;
        $service['allow_microsoldering'] = false;
        $service['microsoldering_price'] = 0;
        return $service;
    }

    $default = webgsm_rr_labor_default($problem);
    if ($default === null && in_array($problem, array('carcasa', 'alte'), true)) {
        $default = webgsm_rr_labor_default('ecran');
    }
    if ($default !== null) {
        $labels = array(
            'baterie' => 'Manoperă baterie',
            'ecran'   => 'Manoperă ecran',
            'carcasa' => 'Manoperă carcasă',
            'alte'    => 'Manoperă montaj',
        );
        return array(
            'amount'               => $default,
            'id'                   => 0,
            'sku'                  => '',
            'label'                => $labels[$problem] ?? 'Manoperă',
            'source'               => 'default',
            'includes_transport'   => $problem === 'baterie',
            'allow_microsoldering' => $problem === 'ecran',
            'microsoldering_price' => defined('WEBGSM_MICROSOLDERING_DEFAULT') ? WEBGSM_MICROSOLDERING_DEFAULT : 150,
        );
    }

    return array(
        'amount'               => null,
        'id'                   => 0,
        'sku'                  => '',
        'label'                => '',
        'source'               => null,
        'includes_transport'   => false,
        'allow_microsoldering' => false,
        'microsoldering_price' => 0,
    );
}

function webgsm_rr_get_offer($model_slug, $problem, $highlight_sku = '') {
    if (!class_exists('WooCommerce')) {
        return array('lines' => array(), 'model' => $model_slug, 'problem' => $problem);
    }

    $problem = webgsm_rr_normalize_problem($problem);
    $term    = webgsm_rr_find_model_term($model_slug);
    $tax     = webgsm_rr_taxonomy('model');
    $cat_ids = webgsm_rr_problem_cat_ids($problem);

    $tax_query = array('relation' => 'AND');
    if ($term && $tax && taxonomy_exists($tax)) {
        $tax_query[] = array(
            'taxonomy' => $tax,
            'field'    => 'term_id',
            'terms'    => array((int) $term->term_id),
        );
    } else {
        return array(
            'lines'      => array(),
            'model'      => $model_slug,
            'model_name' => webgsm_rr_humanize_slug($model_slug),
            'problem'    => $problem,
        );
    }
    if (!empty($cat_ids)) {
        $tax_query[] = array(
            'taxonomy' => 'product_cat',
            'field'    => 'term_id',
            'terms'    => $cat_ids,
        );
    }

    $q = new WP_Query(array(
        'post_type'              => 'product',
        'post_status'            => 'publish',
        'posts_per_page'         => 40,
        'fields'                 => 'ids',
        'no_found_rows'          => true,
        'tax_query'              => $tax_query,
    ));

    $highlight_sku = strtoupper(trim((string) $highlight_sku));
    $lines         = array();
    $label_counts  = array();

    foreach ($q->posts as $pid) {
        $product = wc_get_product($pid);
        if (!$product) {
            continue;
        }
        $label = webgsm_rr_quality_label($product, $problem);
        if ($label === '') {
            continue;
        }
        $sku   = (string) $product->get_sku();
        $price = (float) wc_get_price_to_display($product);
        $stock = webgsm_rr_stock_status($product);
        $labor = webgsm_rr_resolve_labor($product, $model_slug, $problem);
        $labor_amount = $labor['amount'];
        $micro_price  = !empty($labor['allow_microsoldering']) ? (float) ($labor['microsoldering_price'] ?? 0) : 0;
        $total = $labor_amount !== null ? $price + $labor_amount : null;

        if (!isset($label_counts[$label])) {
            $label_counts[$label] = 0;
        }
        $label_counts[$label]++;

        $lines[] = array(
            'problem'              => $problem,
            'id'                   => (int) $product->get_id(),
            'sku'                  => $sku,
            'label'                => $label,
            'price'                => $price,
            'price_html'           => wp_strip_all_tags(wc_price($price)),
            'labor'                => $labor_amount,
            'labor_html'           => $labor_amount !== null ? wp_strip_all_tags(wc_price($labor_amount)) : '',
            'labor_id'             => (int) ($labor['id'] ?? 0),
            'labor_sku'            => (string) ($labor['sku'] ?? ''),
            'labor_label'          => (string) ($labor['label'] ?? ''),
            'labor_source'         => (string) ($labor['source'] ?? ''),
            'includes_transport'   => !empty($labor['includes_transport']),
            'allow_microsoldering' => !empty($labor['allow_microsoldering']),
            'microsoldering_price' => $micro_price,
            'total'                => $total,
            'total_html'           => $total !== null ? wp_strip_all_tags(wc_price($total)) : '',
            'stock'                => $stock['key'],
            'stock_label'          => $stock['label'],
            'highlighted'          => $highlight_sku !== '' && strtoupper($sku) === $highlight_sku,
            'purchasable'          => $product->is_purchasable() && $product->is_in_stock(),
            'url'                  => get_permalink($product->get_id()),
        );
    }

    foreach ($lines as &$line) {
        if ($label_counts[$line['label']] > 1 && $line['sku'] !== '') {
            $line['label'] = $line['label'] . ' · ' . $line['sku'];
        }
    }
    unset($line);

    usort($lines, function ($a, $b) {
        $ra = webgsm_rr_quality_rank(preg_replace('/\s·\s.*$/', '', $a['label']));
        $rb = webgsm_rr_quality_rank(preg_replace('/\s·\s.*$/', '', $b['label']));
        if ($ra !== $rb) {
            return $ra <=> $rb;
        }
        return $a['price'] <=> $b['price'];
    });

    return array(
        'lines'      => array_values($lines),
        'model'      => $term ? $term->slug : $model_slug,
        'model_name' => $term ? $term->name : webgsm_rr_humanize_slug($model_slug),
        'problem'    => $problem,
    );
}

function webgsm_rr_get_multi_offer($model_slug, array $problems, $highlight_sku = '') {
    $problems = array_values(array_unique(array_filter(array_map('webgsm_rr_normalize_problem', $problems))));
    if (!$problems) {
        return array('sections' => array(), 'model' => $model_slug, 'model_name' => webgsm_rr_humanize_slug($model_slug));
    }

    $sections   = array();
    $model_name = '';
    $model      = $model_slug;

    foreach ($problems as $problem) {
        $offer = webgsm_rr_get_offer($model_slug, $problem, $highlight_sku);
        if ($model_name === '' && !empty($offer['model_name'])) {
            $model_name = $offer['model_name'];
        }
        if (!empty($offer['model'])) {
            $model = $offer['model'];
        }
        $meta = webgsm_rr_problems();
        $sections[$problem] = array(
            'problem'      => $problem,
            'label'        => $meta[$problem]['label'] ?? $problem,
            'hint'         => $meta[$problem]['hint'] ?? '',
            'lines'        => $offer['lines'],
        );
    }

    return array(
        'sections'   => $sections,
        'model'      => $model,
        'model_name' => $model_name !== '' ? $model_name : webgsm_rr_humanize_slug($model_slug),
        'problems'   => $problems,
    );
}

/**
 * Total piese + manoperă MAX (+ micro) pentru selecții multiple.
 *
 * @param array<int,array<string,mixed>> $lines
 */
function webgsm_rr_quote_from_lines(array $lines, $microsoldering = false) {
    $parts_total = 0.0;
    $montaj_items = array();
    $has_ecran_micro = false;

    foreach ($lines as $line) {
        $parts_total += (float) ($line['price'] ?? 0);
        $pid = (int) ($line['id'] ?? 0);
        if ($pid) {
            $montaj_items[] = array(
                'product_id'     => $pid,
                'microsoldering' => !empty($line['microsoldering']),
            );
        }
        if (!empty($line['allow_microsoldering']) && !empty($line['microsoldering'])) {
            $has_ecran_micro = true;
        }
    }

    if ($microsoldering && !$has_ecran_micro) {
        foreach ($montaj_items as &$item) {
            $meta = webgsm_montaj_get_product_meta($item['product_id']);
            if (!empty($meta['allow_microsoldering'])) {
                $item['microsoldering'] = true;
                break;
            }
        }
        unset($item);
    }

    $quote = function_exists('webgsm_montaj_quote')
        ? webgsm_montaj_quote($montaj_items)
        : array('total_labor' => 0, 'montaj' => 0, 'microsoldering' => 0, 'transport_included' => false);

    $labor_total = (float) ($quote['total_labor'] ?? 0);
    $grand       = $parts_total + $labor_total;

    return array(
        'parts_total'        => $parts_total,
        'parts_total_html'   => wp_strip_all_tags(wc_price($parts_total)),
        'montaj'             => (float) ($quote['montaj'] ?? 0),
        'montaj_html'        => !empty($quote['montaj_html']) ? $quote['montaj_html'] : '',
        'microsoldering'     => (float) ($quote['microsoldering'] ?? 0),
        'microsoldering_html'=> !empty($quote['microsoldering_html']) ? $quote['microsoldering_html'] : '',
        'labor_total'        => $labor_total,
        'labor_total_html'   => $labor_total > 0 ? wp_strip_all_tags(wc_price($labor_total)) : '',
        'grand_total'        => $grand,
        'grand_total_html'   => wp_strip_all_tags(wc_price($grand)),
        'transport_included' => !empty($quote['transport_included']),
        'montaj_rule'        => 'max',
    );
}

function webgsm_rr_bootstrap_state() {
    $route  = webgsm_rr_current_route();
    $sku    = $route && $route['sku'] ? $route['sku'] : '';
    $state  = array(
        'ajax'     => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('webgsm_rr'),
        'shop'     => webgsm_rr_shop_url(),
        'cart'     => webgsm_rr_cart_url(),
        'whatsapp' => webgsm_rr_whatsapp_number(),
        'home'     => home_url('/'),
        'brands'   => array(),
        'screen'   => 1,
        'brand'    => '',
        'model'    => '',
        'modelName'=> '',
        'problem'  => '',
        'problems' => array(),
        'problemMeta' => webgsm_rr_problems(),
        'sku'      => $sku,
        'offer'    => null,
        'multiOffer' => null,
        'models'   => array(),
        'issues'   => webgsm_rr_intake_issues(),
        'montajNonce' => wp_create_nonce('webgsm_montaj'),
        'servicesPublic' => webgsm_rr_services_public(),
    );
    foreach (webgsm_rr_brands() as $key => $meta) {
        $state['brands'][] = array('id' => $key, 'label' => $meta['label']);
    }

    if ($route) {
        $state['screen']    = 5;
        $state['brand']     = $route['brand'];
        $state['model']     = $route['model_slug'];
        $state['modelName'] = $route['model_name'];
        $state['problem']   = $route['problem'];
        $state['problems']  = array(webgsm_rr_normalize_problem($route['problem']));
        $state['multiOffer'] = webgsm_rr_get_multi_offer($route['model_slug'], $state['problems'], $sku);
        $state['offer']     = webgsm_rr_get_offer($route['model_slug'], $route['problem'], $sku);
        if ($state['brand']) {
            $state['models'] = webgsm_rr_get_models($state['brand']);
        }
    }

    return $state;
}

add_action('wp_ajax_webgsm_rr_models', 'webgsm_rr_ajax_models');
add_action('wp_ajax_nopriv_webgsm_rr_models', 'webgsm_rr_ajax_models');
add_action('wp_ajax_webgsm_rr_offer', 'webgsm_rr_ajax_offer');
add_action('wp_ajax_nopriv_webgsm_rr_offer', 'webgsm_rr_ajax_offer');
add_action('wp_ajax_webgsm_rr_add_to_cart', 'webgsm_rr_ajax_add_to_cart');
add_action('wp_ajax_nopriv_webgsm_rr_add_to_cart', 'webgsm_rr_ajax_add_to_cart');
add_action('wp_ajax_webgsm_rr_multi_offer', 'webgsm_rr_ajax_multi_offer');
add_action('wp_ajax_nopriv_webgsm_rr_multi_offer', 'webgsm_rr_ajax_multi_offer');
add_action('wp_ajax_webgsm_rr_quote', 'webgsm_rr_ajax_quote');
add_action('wp_ajax_nopriv_webgsm_rr_quote', 'webgsm_rr_ajax_quote');
add_action('wp_ajax_webgsm_rr_intake', 'webgsm_rr_ajax_intake');
add_action('wp_ajax_nopriv_webgsm_rr_intake', 'webgsm_rr_ajax_intake');

function webgsm_rr_intake_issues() {
    return array(
        'restart'         => 'Se restartează random',
        'faceid'          => 'Face ID nefuncțional',
        'ecran_fisurat'   => 'Ecran fisurat / spart',
        'baterie_umflata' => 'Baterie umflată',
        'touch'           => 'Touch / display parțial nefuncțional',
        'incarcare'       => 'Probleme la încărcare',
        'camera'          => 'Cameră nefuncțională',
        'speaker'         => 'Difuzor / microfon nefuncțional',
    );
}

function webgsm_rr_waiver_text() {
    return 'Declar că informațiile de mai sus reflectă starea actuală a telefonului și că pozele față/spate sunt actuale. '
        . 'Accept că WebGSM nu poate fi tras la răspundere, în limitele legii, pentru defecte preexistente, pierderi de date, '
        . 'funcții compromise (Face ID, Touch ID, senzori), avarii survenite în transport sau manipulare, ori limitări apărute '
        . 'din cauza stării inițiale a dispozitivului. Înțeleg că demontajul pentru reparație poate evidenția alte probleme.';
}

function webgsm_rr_handle_intake_upload($file_key) {
    if (empty($_FILES[$file_key]) || !is_array($_FILES[$file_key])) {
        return new WP_Error('no_file', 'Lipsește fișierul.');
    }
    $file = $_FILES[$file_key];
    if (!empty($file['error']) && (int) $file['error'] !== UPLOAD_ERR_OK) {
        return new WP_Error('upload_error', 'Nu am putut încărca poza.');
    }
    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return new WP_Error('invalid_upload', 'Fișier invalid.');
    }

    $allowed = array(
        'jpg|jpeg|jpe' => 'image/jpeg',
        'png'          => 'image/png',
        'webp'         => 'image/webp',
        'heic|heif'    => 'image/heic',
    );

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    $overrides = array(
        'test_form' => false,
        'mimes'     => $allowed,
    );
    $upload = wp_handle_upload($file, $overrides);
    if (!empty($upload['error'])) {
        return new WP_Error('upload_failed', $upload['error']);
    }

    $attachment = array(
        'post_mime_type' => $upload['type'],
        'post_title'     => sanitize_file_name(pathinfo($upload['file'], PATHINFO_FILENAME)),
        'post_content'   => '',
        'post_status'    => 'inherit',
    );
    $attach_id = wp_insert_attachment($attachment, $upload['file']);
    if (!$attach_id) {
        return new WP_Error('attachment_failed', 'Nu am putut salva poza.');
    }
    $meta = wp_generate_attachment_metadata($attach_id, $upload['file']);
    if (!is_wp_error($meta) && $meta) {
        wp_update_attachment_metadata($attach_id, $meta);
    }

    return array(
        'id'  => (int) $attach_id,
        'url' => wp_get_attachment_url($attach_id),
    );
}

function webgsm_rr_ajax_intake() {
    webgsm_rr_ajax_verify();

    if (empty($_POST['waiver']) || $_POST['waiver'] !== '1') {
        wp_send_json_error(array('message' => 'Trebuie să accepți termenii pentru a continua.'), 400);
    }

    $issues_map = webgsm_rr_intake_issues();
    $issues_in  = isset($_POST['issues']) ? (array) wp_unslash($_POST['issues']) : array();
    $issues     = array();
    foreach ($issues_in as $key) {
        $key = sanitize_key($key);
        if (isset($issues_map[$key])) {
            $issues[] = array('key' => $key, 'label' => $issues_map[$key]);
        }
    }

    $notes = isset($_POST['notes']) ? sanitize_textarea_field(wp_unslash($_POST['notes'])) : '';
    $model = isset($_POST['model']) ? sanitize_title(wp_unslash($_POST['model'])) : '';
    $model_name = isset($_POST['model_name']) ? sanitize_text_field(wp_unslash($_POST['model_name'])) : '';
    $problem = isset($_POST['problem']) ? sanitize_key(wp_unslash($_POST['problem'])) : '';
    $sku = isset($_POST['sku']) ? sanitize_text_field(wp_unslash($_POST['sku'])) : '';
    $label = isset($_POST['label']) ? sanitize_text_field(wp_unslash($_POST['label'])) : '';

    $front = webgsm_rr_handle_intake_upload('photo_front');
    if (is_wp_error($front)) {
        wp_send_json_error(array('message' => 'Poză față: ' . $front->get_error_message()), 400);
    }
    $back = webgsm_rr_handle_intake_upload('photo_back');
    if (is_wp_error($back)) {
        wp_send_json_error(array('message' => 'Poză spate: ' . $back->get_error_message()), 400);
    }

    $payload = array(
        'created'    => current_time('mysql'),
        'model'      => $model,
        'model_name' => $model_name,
        'problem'    => $problem,
        'sku'        => $sku,
        'label'      => $label,
        'microsoldering' => !empty($_POST['microsoldering']) && $_POST['microsoldering'] === '1',
        'issues'     => $issues,
        'notes'      => $notes,
        'photos'     => array(
            'front' => $front,
            'back'  => $back,
        ),
        'waiver'     => webgsm_rr_waiver_text(),
        'ip'         => isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '',
    );

    $log_id = wp_insert_post(array(
        'post_type'   => 'webgsm_rr_intake',
        'post_status' => 'private',
        'post_title'  => sprintf('Intake %s %s', $model_name ?: $model, current_time('Y-m-d H:i')),
        'post_content'=> wp_json_encode($payload, JSON_UNESCAPED_UNICODE),
    ), true);

    if (!is_wp_error($log_id) && $log_id) {
        update_post_meta($log_id, '_webgsm_intake_photos', array($front['id'], $back['id']));
    }

    wp_send_json_success(array(
        'issues'      => $issues,
        'notes'       => $notes,
        'photo_front' => $front['url'],
        'photo_back'  => $back['url'],
        'intake_id'   => is_wp_error($log_id) ? 0 : (int) $log_id,
    ));
}

add_action('init', function () {
    register_post_type('webgsm_rr_intake', array(
        'labels'              => array(
            'name'          => 'Intake Repair Reel',
            'singular_name' => 'Intake Repair Reel',
        ),
        'public'              => false,
        'show_ui'             => true,
        'show_in_menu'        => 'edit.php?post_type=product',
        'capability_type'     => 'post',
        'map_meta_cap'        => true,
        'supports'            => array('title'),
        'has_archive'         => false,
    ));
}, 12);

add_filter('upload_mimes', function ($mimes) {
    $mimes['heic'] = 'image/heic';
    $mimes['heif'] = 'image/heif';
    return $mimes;
});

function webgsm_rr_ajax_verify() {
    $nonce = isset($_REQUEST['nonce']) ? sanitize_text_field(wp_unslash($_REQUEST['nonce'])) : '';
    if (!wp_verify_nonce($nonce, 'webgsm_rr')) {
        wp_send_json_error(array('message' => 'Sesiune expirată. Reîncarcă pagina.'), 403);
    }
}

function webgsm_rr_ajax_models() {
    webgsm_rr_ajax_verify();
    $brand  = isset($_REQUEST['brand']) ? sanitize_key(wp_unslash($_REQUEST['brand'])) : '';
    $models = webgsm_rr_get_models($brand);
    wp_send_json_success(array('models' => $models, 'brand' => $brand));
}

function webgsm_rr_ajax_offer() {
    webgsm_rr_ajax_verify();
    $model   = isset($_REQUEST['model']) ? sanitize_title(wp_unslash($_REQUEST['model'])) : '';
    $problem = isset($_REQUEST['problem']) ? sanitize_key(wp_unslash($_REQUEST['problem'])) : '';
    $sku     = isset($_REQUEST['sku']) ? sanitize_text_field(wp_unslash($_REQUEST['sku'])) : '';
    $offer   = webgsm_rr_get_offer($model, $problem, $sku);
    wp_send_json_success($offer);
}

function webgsm_rr_ajax_multi_offer() {
    webgsm_rr_ajax_verify();
    $model = isset($_REQUEST['model']) ? sanitize_title(wp_unslash($_REQUEST['model'])) : '';
    $sku   = isset($_REQUEST['sku']) ? sanitize_text_field(wp_unslash($_REQUEST['sku'])) : '';
    $problems = isset($_REQUEST['problems']) ? (array) wp_unslash($_REQUEST['problems']) : array();
    $offer = webgsm_rr_get_multi_offer($model, $problems, $sku);
    wp_send_json_success($offer);
}

function webgsm_rr_ajax_quote() {
    webgsm_rr_ajax_verify();
    $raw = isset($_POST['selections']) ? wp_unslash($_POST['selections']) : '';
    $selections = json_decode(is_string($raw) ? $raw : '', true);
    if (!is_array($selections)) {
        wp_send_json_error(array('message' => 'Selecții invalide.'), 400);
    }
    $lines = array();
    foreach ($selections as $sel) {
        if (!is_array($sel)) {
            continue;
        }
        $lines[] = array(
            'id'                   => (int) ($sel['id'] ?? 0),
            'price'                => (float) ($sel['price'] ?? 0),
            'allow_microsoldering' => !empty($sel['allow_microsoldering']),
            'microsoldering'       => !empty($sel['microsoldering']),
        );
    }
    $micro = !empty($_POST['microsoldering']) && $_POST['microsoldering'] === '1';
    wp_send_json_success(webgsm_rr_quote_from_lines($lines, $micro));
}

function webgsm_rr_ajax_add_to_cart() {
    webgsm_rr_ajax_verify();
    if (!function_exists('WC') || !WC()->cart) {
        wp_send_json_error(array('message' => 'Coșul nu este disponibil.'), 400);
    }
    $product_id = isset($_REQUEST['product_id']) ? absint($_REQUEST['product_id']) : 0;
    $sku        = isset($_REQUEST['sku']) ? sanitize_text_field(wp_unslash($_REQUEST['sku'])) : '';
    if (!$product_id && $sku !== '' && function_exists('wc_get_product_id_by_sku')) {
        $product_id = (int) wc_get_product_id_by_sku($sku);
    }
    if (!$product_id) {
        wp_send_json_error(array('message' => 'Produsul nu a fost găsit.'), 404);
    }
    $product = wc_get_product($product_id);
    if (!$product || !$product->is_purchasable()) {
        wp_send_json_error(array('message' => 'Piesa nu poate fi adăugată în coș.'), 400);
    }
    $added = WC()->cart->add_to_cart($product_id, 1);
    if (!$added) {
        wp_send_json_error(array('message' => 'Nu am putut adăuga piesa. Verifică stocul.'), 400);
    }
    wp_send_json_success(array(
        'cart' => webgsm_rr_cart_url(),
        'sku'  => (string) $product->get_sku(),
    ));
}

function webgsm_rr_json_ld($route, $offer) {
    $address = array(
        '@type'           => 'PostalAddress',
        'streetAddress'   => 'Samuil Micu 27',
        'addressLocality' => 'Timișoara',
        'addressCountry'  => 'RO',
    );

    if (!$route) {
        $shop = webgsm_rr_shop_url();
        if (webgsm_rr_is_repair_landing()) {
            $landing = webgsm_rr_repair_landing_url();
            return array(
                '@context' => 'https://schema.org',
                '@graph'   => array(
                    array(
                        '@type'       => 'WebPage',
                        '@id'         => $landing . '#webpage',
                        'url'         => $landing,
                        'name'        => 'Estimează reparația telefonului',
                        'description' => webgsm_rr_repair_seo_description(),
                        'isPartOf'    => array(
                            '@type' => 'WebSite',
                            'name'  => 'WebGSM',
                            'url'   => home_url('/'),
                        ),
                    ),
                    array(
                        '@type'        => 'BreadcrumbList',
                        'itemListElement' => array(
                            array(
                                '@type'    => 'ListItem',
                                'position' => 1,
                                'name'     => 'Acasă',
                                'item'     => home_url('/'),
                            ),
                            array(
                                '@type'    => 'ListItem',
                                'position' => 2,
                                'name'     => 'Estimează reparația',
                                'item'     => $landing,
                            ),
                        ),
                    ),
                    array(
                        '@type'       => 'Service',
                        'name'        => 'Estimare reparație telefon Timișoara',
                        'description' => webgsm_rr_repair_seo_description(),
                        'url'         => $landing,
                        'provider'    => array(
                            '@type'   => 'LocalBusiness',
                            'name'    => 'WebGSM',
                            'address' => $address,
                        ),
                        'areaServed'  => 'Timișoara',
                    ),
                ),
            );
        }
        return array(
            '@context'    => 'https://schema.org',
            '@type'       => 'Store',
            'name'        => 'WebGSM — Piese telefoane Timișoara',
            'description' => webgsm_rr_home_seo_description(),
            'url'         => home_url('/'),
            'hasOfferCatalog' => array(
                '@type' => 'OfferCatalog',
                'name'  => 'Catalog piese GSM',
                'url'   => $shop,
            ),
            'address'     => $address,
        );
    }
    $problem_label = $route['problem'] === 'baterie' ? 'baterie' : 'ecran';
    $name          = sprintf('Schimbare %s %s', $problem_label, $route['model_name']);
    $offers        = array();
    if (!empty($offer['lines'])) {
        foreach ($offer['lines'] as $line) {
            if ($line['price'] <= 0) {
                continue;
            }
            $offers[] = array(
                '@type'         => 'Offer',
                'name'          => $line['label'],
                'sku'           => $line['sku'],
                'price'         => number_format((float) $line['price'], 2, '.', ''),
                'priceCurrency' => 'RON',
                'availability'  => $line['stock'] === 'nu' ? 'https://schema.org/OutOfStock' : 'https://schema.org/InStock',
            );
        }
    }
    $data = array(
        '@context'    => 'https://schema.org',
        '@type'       => 'Service',
        'name'        => $name,
        'provider'    => array(
            '@type' => 'LocalBusiness',
            'name'  => 'WebGSM',
            'address' => array(
                '@type'           => 'PostalAddress',
                'streetAddress'   => 'Samuil Micu 27',
                'addressLocality' => 'Timișoara',
                'addressCountry'  => 'RO',
            ),
        ),
        'areaServed'  => 'Timișoara',
        'serviceType' => $name,
        'url'         => webgsm_rr_repair_landing_url(),
    );
    if ($offers) {
        $data['offers'] = $offers;
    }
    return $data;
}
