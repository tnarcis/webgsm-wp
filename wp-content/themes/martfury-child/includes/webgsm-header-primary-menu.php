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
// + clasă culoare LED per categorie: led-cyan, led-orange, led-magenta, led-blue, led-green
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

// CSS: meniu primary + LED Glow (vertical-menu + main-navigation)
add_action('wp_head', 'webgsm_primary_menu_styles', 50);
function webgsm_primary_menu_styles() {
    ?>
    <style id="webgsm-primary-menu-css">
    /* ========== Martfury – Meniu vertical + principal: layout ========== */
    .vertical-menu li a,
    .main-navigation li a,
    .site-header .primary-nav.nav > ul.menu > li > a,
    .site-header.header-department-top .main-menu .primary-nav.nav > ul.menu > li > a {
        display: inline-flex !important;
        align-items: center !important;
        transition: all 0.3s ease !important;
    }
    .site-header.header-department-top .main-menu .primary-nav.nav > ul.menu,
    .site-header .col-header-menu .primary-nav.nav > ul.menu {
        display: flex !important;
        flex-wrap: wrap !important;
        justify-content: space-between !important;
        align-items: center !important;
        margin: 0 !important;
        padding: 0 !important;
        list-style: none !important;
        gap: 4px 8px !important;
        width: 100% !important;
        box-sizing: border-box !important;
    }
    .site-header.header-department-top .main-menu .primary-nav.nav > ul.menu > li,
    .site-header .col-header-menu .primary-nav.nav > ul.menu > li {
        margin: 0 !important;
        display: flex !important;
        align-items: center !important;
    }
    .site-header.header-department-top .main-menu .primary-nav.nav > ul.menu > li > a,
    .site-header .col-header-menu .primary-nav.nav > ul.menu > li > a {
        gap: 6px !important;
        padding: 8px 13px !important;
        font-weight: 700 !important;
        font-size: 15px !important;
        letter-spacing: 0.01em !important;
        color: #ffffff !important;
        text-shadow:
            0 1px 2px rgba(0, 0, 0, 0.45),
            0 0 1px rgba(0, 0, 0, 0.35) !important;
        -webkit-font-smoothing: antialiased !important;
        text-decoration: none !important;
        border-radius: 6px !important;
        transition: color 0.25s ease, box-shadow 0.25s ease, background 0.25s ease, font-weight 0.25s ease !important;
    }

    /* ========== LED Glow – Stare inițială: lineart gri, subțire ========== */
    .vertical-menu .led-icon i,
    .main-navigation .led-icon i,
    .site-header .primary-nav.nav .led-icon i {
        color: #444 !important;
        font-weight: 300 !important;
        margin-right: 10px !important;
        transition: color 0.3s ease, filter 0.3s ease, transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) !important;
        filter: drop-shadow(0 0 0 transparent) !important;
    }
    .site-header .primary-nav.nav > ul.menu > li > a .led-icon { margin-right: 0; }
    .site-header .primary-nav.nav > ul.menu > li > a .led-icon i { margin-right: 0 !important; }
    /* Bandă albastră: iconițe albe + umbră pentru contrast pe gradient închis */
    .site-header .col-header-menu .primary-nav.nav .led-icon i,
    .site-header.header-department-top .main-menu .primary-nav.nav .led-icon i {
        color: #ffffff !important;
        filter: drop-shadow(0 1px 1px rgba(0, 0, 0, 0.4)) !important;
    }

    /* ========== LED Glow – Hover: iconița rămâne vizibilă, glow foarte subtil ========== */
    .vertical-menu li:hover .led-icon i,
    .main-navigation li:hover .led-icon i,
    .site-header .primary-nav.nav > ul.menu > li:hover .led-icon i,
    .site-header .primary-nav.nav > ul.menu > li.active .led-icon i {
        filter: drop-shadow(0 0 2px currentColor) drop-shadow(0 1px 1px rgba(0, 0, 0, 0.35)) !important;
        transform: scale(1.1) !important;
    }
    /* Culori LED per categorie – doar culoare icon + glow minim (lineart vizibil); include .col-header-menu pentru banda albastră */
    .vertical-menu li:hover .led-cyan i,
    .main-navigation li:hover .led-cyan i,
    .site-header .primary-nav.nav .webgsm-nav-piese:hover .led-icon i,
    .site-header .primary-nav.nav .webgsm-nav-piese.active .led-icon i,
    .site-header .col-header-menu .primary-nav.nav .webgsm-nav-piese:hover .led-icon i,
    .site-header .col-header-menu .primary-nav.nav .webgsm-nav-piese.active .led-icon i,
    .site-header.header-department-top .main-menu .primary-nav.nav .webgsm-nav-piese:hover .led-icon i,
    .site-header.header-department-top .main-menu .primary-nav.nav .webgsm-nav-piese.active .led-icon i { color: #00e5ff !important; filter: drop-shadow(0 0 2px rgba(0, 229, 255, 0.95)) drop-shadow(0 0 10px rgba(0, 229, 255, 0.55)) !important; }
    .vertical-menu li:hover .led-orange i,
    .main-navigation li:hover .led-orange i,
    .site-header .primary-nav.nav .webgsm-nav-unelte:hover .led-icon i,
    .site-header .primary-nav.nav .webgsm-nav-unelte.active .led-icon i,
    .site-header .col-header-menu .primary-nav.nav .webgsm-nav-unelte:hover .led-icon i,
    .site-header .col-header-menu .primary-nav.nav .webgsm-nav-unelte.active .led-icon i,
    .site-header.header-department-top .main-menu .primary-nav.nav .webgsm-nav-unelte:hover .led-icon i,
    .site-header.header-department-top .main-menu .primary-nav.nav .webgsm-nav-unelte.active .led-icon i { color: #ff9100 !important; filter: drop-shadow(0 0 2px rgba(255, 171, 0, 0.95)) drop-shadow(0 0 10px rgba(255, 145, 0, 0.55)) !important; }
    .vertical-menu li:hover .led-magenta i,
    .main-navigation li:hover .led-magenta i,
    .site-header .primary-nav.nav .webgsm-nav-accesorii:hover .led-icon i,
    .site-header .primary-nav.nav .webgsm-nav-accesorii.active .led-icon i,
    .site-header .col-header-menu .primary-nav.nav .webgsm-nav-accesorii:hover .led-icon i,
    .site-header .col-header-menu .primary-nav.nav .webgsm-nav-accesorii.active .led-icon i,
    .site-header.header-department-top .main-menu .primary-nav.nav .webgsm-nav-accesorii:hover .led-icon i,
    .site-header.header-department-top .main-menu .primary-nav.nav .webgsm-nav-accesorii.active .led-icon i { color: #ff38f0 !important; filter: drop-shadow(0 0 2px rgba(255, 80, 240, 0.95)) drop-shadow(0 0 10px rgba(255, 56, 240, 0.5)) !important; }
    .vertical-menu li:hover .led-blue i,
    .main-navigation li:hover .led-blue i,
    .site-header .primary-nav.nav .webgsm-nav-dispozitive:hover .led-icon i,
    .site-header .primary-nav.nav .webgsm-nav-dispozitive.active .led-icon i,
    .site-header .col-header-menu .primary-nav.nav .webgsm-nav-dispozitive:hover .led-icon i,
    .site-header .col-header-menu .primary-nav.nav .webgsm-nav-dispozitive.active .led-icon i,
    .site-header.header-department-top .main-menu .primary-nav.nav .webgsm-nav-dispozitive:hover .led-icon i,
    .site-header.header-department-top .main-menu .primary-nav.nav .webgsm-nav-dispozitive.active .led-icon i { color: #7dd3fc !important; filter: drop-shadow(0 0 2px rgba(125, 211, 252, 0.95)) drop-shadow(0 0 10px rgba(103, 232, 249, 0.55)) !important; }
    .vertical-menu li:hover .led-green i,
    .main-navigation li:hover .led-green i,
    .site-header .primary-nav.nav .webgsm-nav-servicii:hover .led-icon i,
    .site-header .primary-nav.nav .webgsm-nav-servicii.active .led-icon i,
    .site-header .col-header-menu .primary-nav.nav .webgsm-nav-servicii:hover .led-icon i,
    .site-header .col-header-menu .primary-nav.nav .webgsm-nav-servicii.active .led-icon i,
    .site-header.header-department-top .main-menu .primary-nav.nav .webgsm-nav-servicii:hover .led-icon i,
    .site-header.header-department-top .main-menu .primary-nav.nav .webgsm-nav-servicii.active .led-icon i { color: #4ade80 !important; filter: drop-shadow(0 0 2px rgba(74, 222, 128, 0.95)) drop-shadow(0 0 10px rgba(52, 211, 153, 0.55)) !important; }
    /* Fallback când .led-icon nu are clasă de culoare */
    .vertical-menu li:hover .led-icon:not(.led-cyan):not(.led-orange):not(.led-magenta):not(.led-blue):not(.led-green) i,
    .main-navigation li:hover .led-icon:not(.led-cyan):not(.led-orange):not(.led-magenta):not(.led-blue):not(.led-green) i,
    .site-header .col-header-menu .primary-nav.nav li:hover .led-icon:not(.led-cyan):not(.led-orange):not(.led-magenta):not(.led-blue):not(.led-green) i,
    .site-header.header-department-top .main-menu .primary-nav.nav li:hover .led-icon:not(.led-cyan):not(.led-orange):not(.led-magenta):not(.led-blue):not(.led-green) i { color: #00e5ff !important; filter: drop-shadow(0 0 2px rgba(0, 229, 255, 0.95)) drop-shadow(0 0 10px rgba(0, 229, 255, 0.55)) !important; }

    /* Text meniu – font-weight mai mare la hover (aspect premium) */
    .vertical-menu li:hover > a,
    .main-navigation li:hover > a,
    .site-header .primary-nav.nav > ul.menu > li:hover > a,
    .site-header .primary-nav.nav > ul.menu > li.active > a {
        font-weight: 700 !important;
        letter-spacing: 0.02em !important;
    }
    .site-header.header-department-top .main-menu .primary-nav.nav > ul.menu > li:hover > a,
    .site-header.header-department-top .main-menu .primary-nav.nav > ul.menu > li.active > a,
    .site-header .col-header-menu .primary-nav.nav > ul.menu > li:hover > a,
    .site-header .col-header-menu .primary-nav.nav > ul.menu > li.active > a {
        color: #ffffff !important;
        background: rgba(255, 255, 255, 0.28) !important;
        box-shadow:
            0 0 0 1px rgba(255, 255, 255, 0.55),
            0 1px 3px rgba(0, 0, 0, 0.2) !important;
        text-shadow:
            0 1px 2px rgba(0, 0, 0, 0.5),
            0 0 1px rgba(0, 0, 0, 0.4) !important;
    }
    /* Pe banda albastră, culorile LED la hover rămân cele per categorie (nu forțăm alb pe icon) */

    /* ========== Meniu mobil lateral (hamburger / primary-mobile-nav) – același design LED Glow ========== */
    .primary-mobile-nav ul.menu li > a {
        display: inline-flex !important;
        align-items: center !important;
        transition: all 0.3s ease !important;
    }
    /* Spațiu pentru +/- ca să nu se suprapună cu textul (categorii principale și subcategorii) */
    .primary-mobile-nav ul.menu li.menu-item-has-children > a {
        padding-right: 48px !important;
        position: relative !important;
    }
    .primary-mobile-nav ul.menu li.menu-item-has-children .toggle-menu-children {
        right: 12px !important;
        left: auto !important;
        width: 28px !important;
        height: 28px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        z-index: 2 !important;
        pointer-events: auto !important;
    }
    .primary-mobile-nav ul.menu ul li.menu-item-has-children > a {
        padding-right: 44px !important;
    }
    .primary-mobile-nav ul.menu ul li.menu-item-has-children .toggle-menu-children {
        right: 10px !important;
    }
    .primary-mobile-nav .led-icon i {
        color: #444 !important;
        font-weight: 300 !important;
        margin-right: 10px !important;
        transition: color 0.3s ease, filter 0.3s ease, transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) !important;
        filter: drop-shadow(0 0 0 transparent) !important;
    }
    .primary-mobile-nav ul.menu > li > a .led-icon { margin-right: 0; }
    .primary-mobile-nav ul.menu > li > a .led-icon i { margin-right: 0 !important; }
    .primary-mobile-nav li:hover .led-icon i,
    .primary-mobile-nav li.active .led-icon i {
        filter: drop-shadow(0 0 2px currentColor) !important;
        transform: scale(1.1) !important;
    }
    .primary-mobile-nav li:hover .led-cyan i,
    .primary-mobile-nav .webgsm-nav-piese:hover .led-icon i,
    .primary-mobile-nav .webgsm-nav-piese.active .led-icon i { color: #00e5ff !important; filter: drop-shadow(0 0 2px rgba(0, 229, 255, 0.95)) drop-shadow(0 0 10px rgba(0, 229, 255, 0.55)) !important; }
    .primary-mobile-nav li:hover .led-orange i,
    .primary-mobile-nav .webgsm-nav-unelte:hover .led-icon i,
    .primary-mobile-nav .webgsm-nav-unelte.active .led-icon i { color: #ff9100 !important; filter: drop-shadow(0 0 2px rgba(255, 171, 0, 0.95)) drop-shadow(0 0 10px rgba(255, 145, 0, 0.55)) !important; }
    .primary-mobile-nav li:hover .led-magenta i,
    .primary-mobile-nav .webgsm-nav-accesorii:hover .led-icon i,
    .primary-mobile-nav .webgsm-nav-accesorii.active .led-icon i { color: #ff38f0 !important; filter: drop-shadow(0 0 2px rgba(255, 80, 240, 0.95)) drop-shadow(0 0 10px rgba(255, 56, 240, 0.5)) !important; }
    .primary-mobile-nav li:hover .led-blue i,
    .primary-mobile-nav .webgsm-nav-dispozitive:hover .led-icon i,
    .primary-mobile-nav .webgsm-nav-dispozitive.active .led-icon i { color: #7dd3fc !important; filter: drop-shadow(0 0 2px rgba(125, 211, 252, 0.95)) drop-shadow(0 0 10px rgba(103, 232, 249, 0.55)) !important; }
    .primary-mobile-nav li:hover .led-green i,
    .primary-mobile-nav .webgsm-nav-servicii:hover .led-icon i,
    .primary-mobile-nav .webgsm-nav-servicii.active .led-icon i { color: #4ade80 !important; filter: drop-shadow(0 0 2px rgba(74, 222, 128, 0.95)) drop-shadow(0 0 10px rgba(52, 211, 153, 0.55)) !important; }
    .primary-mobile-nav li:hover .led-icon:not(.led-cyan):not(.led-orange):not(.led-magenta):not(.led-blue):not(.led-green) i { color: #00e5ff !important; filter: drop-shadow(0 0 2px rgba(0, 229, 255, 0.95)) drop-shadow(0 0 10px rgba(0, 229, 255, 0.55)) !important; }
    .primary-mobile-nav li:hover > a,
    .primary-mobile-nav li.active > a {
        font-weight: 700 !important;
        letter-spacing: 0.02em !important;
    }
    .primary-mobile-nav ul.menu > li > a {
        padding: 14px 16px !important;
        gap: 10px !important;
        border-radius: 8px !important;
        min-height: 44px !important;
        box-sizing: border-box !important;
    }
    /* Conținutul linkului (icon + text) nu depășește zona rezervată pentru +/- */
    .primary-mobile-nav ul.menu li.menu-item-has-children > a {
        max-width: 100% !important;
    }
    .primary-mobile-nav ul.menu li.menu-item-has-children > a .led-icon {
        flex-shrink: 0 !important;
    }
    .primary-mobile-nav ul.menu > li:hover > a,
    .primary-mobile-nav ul.menu > li.active > a {
        background: rgba(59, 130, 246, 0.08) !important;
        color: #1d4ed8 !important;
        box-shadow: 0 0 0 1px rgba(59, 130, 246, 0.2) !important;
    }
    /* Submeniu mobil – fără LED pe icon (doar pe itemii de nivel 0) */
    .primary-mobile-nav ul.menu ul .led-icon i { color: inherit !important; filter: none !important; }

    @media (max-width: 991px) {
        .site-header.header-department-top .main-menu .primary-nav.nav > ul.menu > li > a,
        .site-header .col-header-menu .primary-nav.nav > ul.menu > li > a {
            padding: 7px 11px !important;
            font-size: 14px !important;
            touch-action: manipulation !important;
            -webkit-tap-highlight-color: rgba(37, 99, 235, 0.12) !important;
        }
        .site-header.header-department-top .main-menu .primary-nav.nav > ul.menu,
        .site-header .col-header-menu .primary-nav.nav > ul.menu {
            justify-content: flex-start !important;
        }
    }
    </style>
    <?php
}
