<?php
/**
 * Catalog: stele pe carduri (shop + categorii). Nu pe homepage — prea multe widget-uri Elementor.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('woocommerce_template_loop_rating')) {
    return;
}

// O singură dată: rating în mf-product-content (înainte de close la priority 9).
remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5);
remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 7);
add_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 7);

/**
 * Stele goale când produsul nu are recenzii (Martfury returnează HTML gol).
 */
add_filter('woocommerce_product_get_rating_html', 'webgsm_catalog_rating_html_always', 5, 3);
function webgsm_catalog_rating_html_always($html, $rating, $count) {
    if ($html !== '') {
        return $html;
    }
    if (!function_exists('woocommerce_product_loop') || !woocommerce_product_loop()) {
        return $html;
    }

    $rating = is_numeric($rating) ? (float) $rating : 0.0;
    $count  = is_numeric($count) ? (int) $count : 0;
    $label  = sprintf(__('Rated %s out of 5', 'woocommerce'), $rating);

    return '<div class="star-rating" role="img" aria-label="' . esc_attr($label) . '">'
        . wc_get_star_rating_html($rating, $count)
        . '</div>';
}
