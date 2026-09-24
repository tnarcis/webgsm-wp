<?php
/**
 * WebGSM - Stilizare meniu primary + LED Glow (iconițe în span.led-icon, FontAwesome).
 * Meniul vertical și principal: lineart gri, hover = LED glow (#00f2ff sau culoare per categorie).
 *
 * @package WebGSM
 * @subpackage Martfury-Child
 */

if (!defined('ABSPATH')) exit;

// Adaugă clase pe itemi (doar meniul primary, nivel 0)
add_filter('nav_menu_css_class', 'webgsm_primary_menu_item_classes', 10, 4);
function webgsm_primary_menu_item_classes($classes, $item, $args, $depth) {
    if ($depth !== 0) return $classes;
    $loc = isset($args->theme_location) ? $args->theme_location : '';
    $allowed = ['primary', 'primary-menu', 'shop-department', 'shop_department', 'mobile'];
    if ($loc && !in_array($loc, $allowed, true)) return $classes;
    $map = [
        'piese'       => 'webgsm-nav-piese',
        'unelte'     => 'webgsm-nav-unelte',
        'accesorii'  => 'webgsm-nav-accesorii',
        'dispozitive' => 'webgsm-nav-dispozitive',
        'supraveghere' => 'webgsm-nav-supraveghere',
        'smart home' => 'webgsm-nav-supraveghere',
        'smart tech' => 'webgsm-nav-supraveghere',
        'security' => 'webgsm-nav-supraveghere',
        'securitate' => 'webgsm-nav-supraveghere',
        'servicii'   => 'webgsm-nav-servicii',
    ];
    $title_lower = mb_strtolower(trim($item->title));
    foreach ($map as $key => $css_class) {
        if (strpos($title_lower, $key) !== false) {
            $classes[] = $css_class;
            break;
        }
    }
    return $classes;
}

// Iconițe FontAwesome încapsulate în <span class="led-icon"> doar la nivel 0 (categorii principale)
// + clasă culoare LED per categorie: led-cyan, led-orange, led-magenta, led-blue, led-gold, led-green
add_filter('nav_menu_item_title', 'webgsm_primary_menu_led_icon_in_title', 10, 4);
function webgsm_primary_menu_led_icon_in_title($title, $item, $args, $depth) {
    if ($depth !== 0) return $title;
    $loc = isset($args->theme_location) ? $args->theme_location : '';
    $allowed = ['primary', 'primary-menu', 'shop-department', 'shop_department', 'mobile'];
    if ($loc && !in_array($loc, $allowed, true)) return $title;
    // fa = Font Awesome 4 (Martfury); fas = FA5 – folosim fa pentru compatibilitate
    $map = [
        'piese'       => ['icon' => 'fa fa-cog',           'color' => 'led-cyan'],
        'unelte'     => ['icon' => 'fa fa-wrench',         'color' => 'led-orange'],
        'accesorii'  => ['icon' => 'fa fa-cube',           'color' => 'led-magenta'],
        'dispozitive' => ['icon' => 'fa fa-mobile',        'color' => 'led-blue'],
        'supraveghere' => ['icon' => 'fa fa-video-camera', 'color' => 'led-gold'],
        'smart home' => ['icon' => 'fa fa-video-camera',   'color' => 'led-gold'],
        'smart tech' => ['icon' => 'fa fa-video-camera',   'color' => 'led-gold'],
        'security' => ['icon' => 'fa fa-shield',           'color' => 'led-gold'],
        'securitate' => ['icon' => 'fa fa-shield',         'color' => 'led-gold'],
        'servicii'   => ['icon' => 'fa fa-cogs',           'color' => 'led-green'],
    ];
    $title_lower = mb_strtolower(trim($item->title));
    foreach ($map as $key => $data) {
        if (strpos($title_lower, $key) !== false) {
            $class = esc_attr('led-icon ' . $data['color']);
            $icon  = esc_attr($data['icon']);
            return '<span class="' . $class . '"><i class="' . $icon . '" aria-hidden="true"></i></span> ' . $title;
        }
    }
    return $title;
}

// CSS meniu primary — fișier extern (cache browser)
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

