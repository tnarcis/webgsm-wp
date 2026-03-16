<?php
/**
 * FIX: Buton duplicat "Adaugă în coș" în view LIST (catalog/categorie)
 *
 * Martfury adaugă butonul de 2 ori când product_loop_hover este 3 sau 4:
 * - product_loop_footer_buttons (prioritate 10)
 * - product_loop_hover (prioritate 90)
 *
 * Soluție: Înlocuim product_loop_footer_buttons cu o versiune care NU afișează
 * add_to_cart când product_loop_hover este 3 sau 4 (cel de la 90 rămâne).
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('template_redirect', 'webgsm_fix_catalog_duplicate_add_to_cart', 99);

function webgsm_fix_catalog_duplicate_add_to_cart() {
    if ((!function_exists('martfury_is_catalog') || !martfury_is_catalog()) &&
        (!function_exists('martfury_is_vendor_page') || !martfury_is_vendor_page())) {
        return;
    }
    if (!function_exists('martfury_get_option')) {
        return;
    }

    $loop_hover = martfury_get_option('product_loop_hover');
    // Când e 3 sau 4, product_loop_hover adaugă deja add_to_cart la prioritate 90
    if (!in_array($loop_hover, array('3', '4', 3, 4))) {
        return;
    }

    global $martfury_woocommerce;
    if (!is_object($martfury_woocommerce)) {
        return;
    }

    remove_action('woocommerce_after_shop_loop_item_title', array($martfury_woocommerce, 'product_loop_footer_buttons'), 10);
    add_action('woocommerce_after_shop_loop_item_title', 'webgsm_product_loop_footer_buttons_no_duplicate_cart', 10);
}

function webgsm_product_loop_footer_buttons_no_duplicate_cart() {
    global $martfury_woocommerce;

    echo '<div class="footer-button">';
    // NU afișăm add_to_cart aici - e deja la prioritate 90 din product_loop_hover
    echo '<div class="action-button">';

    if (shortcode_exists('wcboost_wishlist_button')) {
        echo do_shortcode('[wcboost_wishlist_button]');
    } elseif (shortcode_exists('yith_wcwl_add_to_wishlist')) {
        echo do_shortcode('[yith_wcwl_add_to_wishlist]');
    }

    if (is_object($martfury_woocommerce) && method_exists($martfury_woocommerce, 'product_compare')) {
        $martfury_woocommerce->product_compare();
    }

    echo '</div>';
    echo '</div>';
}
