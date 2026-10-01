<?php
/**
 * WebGSM - Meniu primary: iconițe SVG line-art + LED glow la hover.
 *
 * @package WebGSM
 * @subpackage Martfury-Child
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @param object $args wp_nav_menu args
 * @param int    $depth
 */
function webgsm_primary_menu_is_top_level_context($args, $depth) {
    if ($depth !== 0) {
        return false;
    }
    $loc = isset($args->theme_location) ? (string) $args->theme_location : '';
    $allowed = array(
        'primary',
        'primary-menu',
        'shop-department',
        'shop_department',
        'mobile',
        'category_mobile',
    );
    if ($loc !== '' && !in_array($loc, $allowed, true)) {
        return false;
    }
    return true;
}

/**
 * Cheie categorie rădăcină (piese, unelte, …) din product_cat sau titlu.
 *
 * @param WP_Post $item
 */
function webgsm_primary_menu_resolve_root_key($item) {
    static $cache = array();
    $id = isset($item->ID) ? (int) $item->ID : 0;
    if ($id && isset($cache[$id])) {
        return $cache[$id];
    }

    $key = '';
    if (isset($item->type, $item->object, $item->object_id)
        && $item->type === 'taxonomy'
        && $item->object === 'product_cat'
        && (int) $item->object_id > 0
    ) {
        $term = get_term((int) $item->object_id, 'product_cat');
        if ($term && !is_wp_error($term)) {
            $root = $term;
            if ((int) $term->parent > 0) {
                $ancestors = get_ancestors($term->term_id, 'product_cat', 'taxonomy');
                if (!empty($ancestors)) {
                    $root_id = (int) end($ancestors);
                    $root_term = get_term($root_id, 'product_cat');
                    if ($root_term && !is_wp_error($root_term)) {
                        $root = $root_term;
                    }
                }
            }
            $key = (string) $root->slug;
        }
    }

    if ($key === '') {
        $title_lower = mb_strtolower(trim((string) $item->title));
        $title_map = array(
            'piese'        => 'piese',
            'unelte'       => 'unelte',
            'accesorii'    => 'accesorii',
            'dispozitive'  => 'dispozitive',
            'supraveghere' => 'supraveghere',
            'smart home'   => 'supraveghere',
            'smart tech'   => 'supraveghere',
            'security'     => 'supraveghere',
            'securitate'   => 'supraveghere',
            'servicii'     => 'servicii',
        );
        foreach ($title_map as $needle => $slug) {
            if (strpos($title_lower, $needle) !== false) {
                $key = $slug;
                break;
            }
        }
    }

    if ($id) {
        $cache[$id] = $key;
    }
    return $key;
}

/**
 * @return array<string, array{css: string, color: string}>
 */
function webgsm_primary_menu_category_map() {
    return array(
        'piese'        => array('css' => 'webgsm-nav-piese', 'color' => 'led-cyan'),
        'unelte'       => array('css' => 'webgsm-nav-unelte', 'color' => 'led-orange'),
        'accesorii'    => array('css' => 'webgsm-nav-accesorii', 'color' => 'led-magenta'),
        'dispozitive'  => array('css' => 'webgsm-nav-dispozitive', 'color' => 'led-blue'),
        'supraveghere' => array('css' => 'webgsm-nav-supraveghere', 'color' => 'led-gold'),
        'servicii'     => array('css' => 'webgsm-nav-servicii', 'color' => 'led-green'),
    );
}

/**
 * SVG line-art în meniu (stroke currentColor).
 *
 * @param string $key
 */
function webgsm_primary_menu_lineart_svg($key) {
    $svg_attr = 'xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"';

    switch ($key) {
        case 'piese':
            return '<svg ' . $svg_attr . '><rect x="7" y="2.5" width="10" height="19" rx="2"/><path d="M10 6h4M10 18h4"/><circle cx="12" cy="15" r="1.2" fill="currentColor" stroke="none"/></svg>';
        case 'unelte':
            return '<svg ' . $svg_attr . '><path d="M14.7 6.3a4 4 0 00-5.4 5.4L3 18v3h3l6.3-6.3a4 4 0 005.4-5.4l-2.1 2.1-1.4-1.4 2.1-2.1z"/></svg>';
        case 'accesorii':
            return '<svg ' . $svg_attr . '><path d="M12 3l7 4v10l-7 4-7-4V7l7-4z"/><path d="M12 11v10M5 7l7 4 7-4"/></svg>';
        case 'dispozitive':
            return '<svg ' . $svg_attr . '><rect x="5" y="2" width="14" height="20" rx="2.5"/><circle cx="12" cy="18" r="1" fill="currentColor" stroke="none"/></svg>';
        case 'supraveghere':
            return '<svg ' . $svg_attr . '><path d="M4 8V6a2 2 0 012-2h12a2 2 0 012 2v2"/><rect x="3" y="8" width="18" height="10" rx="2"/><circle cx="12" cy="13" r="2.5"/><path d="M9 18h6"/></svg>';
        case 'servicii':
            return '<svg ' . $svg_attr . '><path d="M12 3l1.8 3.6L18 7.5l-3 2.9.7 4.1L12 12.8 8.3 14.5l.7-4.1-3-2.9 4.2-.9L12 3z"/><path d="M5 20h14"/></svg>';
        default:
            return '';
    }
}

/**
 * @param object $item
 * @param array  $args
 * @param int    $depth
 */
function webgsm_primary_menu_build_icon_html($item, $args, $depth) {
    if (!webgsm_primary_menu_is_top_level_context($args, $depth)) {
        return '';
    }
    $key = webgsm_primary_menu_resolve_root_key($item);
    $map = webgsm_primary_menu_category_map();
    if ($key === '' || !isset($map[$key])) {
        return '';
    }
    $svg = webgsm_primary_menu_lineart_svg($key);
    if ($svg === '') {
        return '';
    }
    $class = esc_attr('led-icon ' . $map[$key]['color']);
    return '<span class="' . $class . '">' . $svg . '</span>';
}

add_filter('nav_menu_css_class', 'webgsm_primary_menu_item_classes', 10, 4);
function webgsm_primary_menu_item_classes($classes, $item, $args, $depth) {
    if (!webgsm_primary_menu_is_top_level_context($args, $depth)) {
        return $classes;
    }
    $key = webgsm_primary_menu_resolve_root_key($item);
    $map = webgsm_primary_menu_category_map();
    if ($key !== '' && isset($map[$key])) {
        $classes[] = $map[$key]['css'];
    }
    return $classes;
}

add_filter('nav_menu_item_title', 'webgsm_primary_menu_led_icon_in_title', 10, 4);
function webgsm_primary_menu_led_icon_in_title($title, $item, $args, $depth) {
    if (strpos((string) $title, 'led-icon') !== false) {
        return $title;
    }
    $icon = webgsm_primary_menu_build_icon_html($item, $args, $depth);
    if ($icon === '') {
        return $title;
    }
    return $icon . ' ' . $title;
}

/** data-wgsm-icon = fallback CSS (::before) dacă SVG e eliminat din cache/HTML */
add_filter('nav_menu_link_attributes', 'webgsm_primary_menu_link_attributes', 10, 4);
function webgsm_primary_menu_link_attributes($atts, $item, $args, $depth) {
    if (!webgsm_primary_menu_is_top_level_context($args, $depth)) {
        return $atts;
    }
    if (!is_array($atts)) {
        $atts = array();
    }
    $key = webgsm_primary_menu_resolve_root_key($item);
    $map = webgsm_primary_menu_category_map();
    if ($key === '' || !isset($map[$key])) {
        return $atts;
    }
    $atts['data-wgsm-icon'] = $key;
    if (empty($atts['class'])) {
        $atts['class'] = 'webgsm-menu-link-has-icon';
    } elseif (strpos((string) $atts['class'], 'webgsm-menu-link-has-icon') === false) {
        $atts['class'] .= ' webgsm-menu-link-has-icon';
    }
    return $atts;
}

add_filter('walker_nav_menu_start_el', 'webgsm_primary_menu_ensure_icon_in_output', 15, 4);
function webgsm_primary_menu_ensure_icon_in_output($item_output, $item, $depth, $args) {
    if (!webgsm_primary_menu_is_top_level_context($args, $depth)) {
        return $item_output;
    }
    if (strpos($item_output, 'led-icon') !== false) {
        return $item_output;
    }
    $icon = webgsm_primary_menu_build_icon_html($item, $args, $depth);
    if ($icon === '') {
        return $item_output;
    }
    return preg_replace('#(<a\s[^>]*>)#', '$1' . $icon . ' ', $item_output, 1);
}

add_action('wp_enqueue_scripts', 'webgsm_primary_menu_styles', 50);
function webgsm_primary_menu_styles() {
    if (is_admin()) {
        return;
    }
    $file = get_stylesheet_directory() . '/assets/css/webgsm-primary-menu.css';
    if (!file_exists($file)) {
        return;
    }
    wp_enqueue_style(
        'webgsm-primary-menu',
        get_stylesheet_directory_uri() . '/assets/css/webgsm-primary-menu.css',
        array(),
        (string) filemtime($file)
    );
}
