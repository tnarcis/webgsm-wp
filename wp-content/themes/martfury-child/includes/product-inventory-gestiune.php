<?php
/**
 * WebGSM - Date Gestiune în tab Inventory
 * Integrează câmpurile Date Gestiune (EAN/GTIN, SKU Furnizor, Furnizor, Preț Achiziție)
 * în tab-ul Inventory al produsului. Sincronizează cu _global_unique_id (WC) ↔ gtin_ean (ACF).
 * Preț achiziție salvat în _pret_achizitie (folosit de B2B).
 *
 * @package WebGSM
 * @subpackage Martfury-Child
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('wc_get_product')) {
    return;
}

/**
 * Adaugă câmpurile Date Gestiune în tab-ul Inventory (după SKU/GTIN nativ WC).
 */
add_action('woocommerce_product_options_inventory_product_data', 'webgsm_inventory_gestiune_fields');

function webgsm_inventory_gestiune_fields() {
    global $post, $product_object;

    $post_id = $post ? $post->ID : 0;
    if (!$post_id) {
        return;
    }

    echo '<div class="options_group webgsm-date-gestiune" style="border-top: 1px solid #eee; margin-top: 12px; padding-top: 12px;">';
    echo '<h4 style="padding-left: 12px; margin: 0 0 12px 0;">' . esc_html__('Date Gestiune', 'webgsm') . '</h4>';

    // SKU Furnizor (același meta key ca ACF: sku_furnizor)
    woocommerce_wp_text_input(array(
        'id'          => 'sku_furnizor',
        'value'       => get_post_meta($post_id, 'sku_furnizor', true),
        'label'       => __('SKU Furnizor', 'webgsm'),
        'desc_tip'    => true,
        'description' => __('Codul produsului la furnizor (pentru comenzi).', 'webgsm'),
        'placeholder' => 'MSTX-12345',
    ));

    // Furnizor Activ (același meta key ca ACF: furnizor_activ)
    woocommerce_wp_select(array(
        'id'          => 'furnizor_activ',
        'value'       => get_post_meta($post_id, 'furnizor_activ', true) ?: 'stoc_propriu',
        'label'       => __('Furnizor Activ', 'webgsm'),
        'desc_tip'    => true,
        'description' => __('De la cine cumperi acest produs.', 'webgsm'),
        'options'     => array(
            'mobilesentrix' => 'Mobilesentrix',
            'mpsmobile'     => 'MPSmobile',
            'mobileparts'   => 'Mobileparts',
            'stoc_propriu'  => 'Stoc Propriu',
            'local_tm'     => 'Local Timișoara',
        ),
    ));

    // Preț Achiziție (EUR) – salvat în _pret_achizitie pentru compatibilitate B2B
    woocommerce_wp_text_input(array(
        'id'                => '_pret_achizitie',
        'value'             => get_post_meta($post_id, '_pret_achizitie', true),
        'label'             => __('Preț Achiziție (EUR)', 'webgsm'),
        'desc_tip'          => true,
        'description'       => __('Prețul de achiziție pentru calcul marjă și B2B.', 'webgsm'),
        'type'              => 'number',
        'custom_attributes' => array('step' => '0.01', 'min' => '0'),
        'placeholder'       => '45.00',
    ));

    echo '</div>';
}

/**
 * Salvează câmpurile Date Gestiune și sincronizează EAN/GTIN cu gtin_ean.
 */
add_action('woocommerce_process_product_meta', 'webgsm_save_inventory_gestiune_fields', 25, 2);

function webgsm_save_inventory_gestiune_fields($post_id, $post = null) {
    if (!isset($_POST['woocommerce_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['woocommerce_meta_nonce'])), 'woocommerce_save_data')) {
        return;
    }

    // SKU Furnizor (același key ca ACF – get_field('sku_furnizor') continuă să funcționeze)
    if (isset($_POST['sku_furnizor'])) {
        update_post_meta($post_id, 'sku_furnizor', sanitize_text_field(wp_unslash($_POST['sku_furnizor'])));
    }

    // Furnizor Activ (același key ca ACF)
    if (isset($_POST['furnizor_activ'])) {
        $val = sanitize_text_field(wp_unslash($_POST['furnizor_activ']));
        $allowed = array('mobilesentrix', 'mpsmobile', 'mobileparts', 'stoc_propriu', 'local_tm');
        if (in_array($val, $allowed, true)) {
            update_post_meta($post_id, 'furnizor_activ', $val);
        }
    }

    // Preț Achiziție – _pret_achizitie (folosit de plugin B2B)
    if (isset($_POST['_pret_achizitie'])) {
        $val = sanitize_text_field(wp_unslash($_POST['_pret_achizitie']));
        update_post_meta($post_id, '_pret_achizitie', $val !== '' ? wc_format_decimal($val) : '');
    }

    // Sincronizare EAN/GTIN: WC folosește _global_unique_id; copiem în gtin_ean pentru get_field('gtin_ean')
    $global_id = isset($_POST['_global_unique_id']) ? wc_clean(wp_unslash($_POST['_global_unique_id'])) : '';
    update_post_meta($post_id, 'gtin_ean', $global_id);
}
