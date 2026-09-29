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

/**
 * Icon line-art via CSS mask (data-wgsm-icon) — funcționează și pentru vizitatori / pagini cache LiteSpeed
 * (SVG inline din titlul meniului e uneori eliminat din HTML-ul servit oaspeților).
 */
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

add_filter('walker_nav_menu_start_el', 'webgsm_primary_menu_strip_legacy_title_icons', 5, 4);
function webgsm_primary_menu_strip_legacy_title_icons($item_output, $item, $depth, $args) {
    if (!webgsm_primary_menu_is_top_level_context($args, $depth)) {
        return $item_output;
    }
    if (strpos($item_output, 'data-wgsm-icon') === false) {
        return $item_output;
    }
    return preg_replace('#<span class="led-icon[^"]*">.*?</span>\s*#s', '', $item_output, 1);
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
