<?php
/**
 * WebGSM - Date Gestiune în tab Inventory
 * Preț achiziție: un singur câmp _pret_achizitie (scriptul / importul calculează valoarea).
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

/** Curs EUR → RON (ex: 5.1). */
function webgsm_eur_ron_rate() {
    return (float) apply_filters('webgsm_eur_ron_rate', get_option('webgsm_eur_ron_rate', 5.1));
}

/** Multiplicator TVA (ex: 1.21 = +21%). */
function webgsm_achizitie_tva_multiplier() {
    return (float) apply_filters('webgsm_achizitie_tva_multiplier', get_option('webgsm_achizitie_tva_multiplier', 1.21));
}

/** Din preț achiziție RON (cu TVA) → EUR fără TVA: ron / (curs × tva). */
function webgsm_ron_cu_tva_to_eur_fara_tva($ron) {
    $ron = (float) wc_format_decimal($ron);
    if ($ron <= 0) return 0;
    $curs = webgsm_eur_ron_rate();
    $tva = webgsm_achizitie_tva_multiplier();
    if ($curs <= 0 || $tva <= 0) return 0;
    return round($ron / ($curs * $tva), 2);
}

/**
 * Citește _pret_achizitie cu fallback la pret_achizitie (CSV/import salvează uneori fără underscore).
 * B2B și formularul folosesc _pret_achizitie; la citire, dacă e gol, returnăm pret_achizitie.
 */
add_filter('get_post_metadata', 'webgsm_pret_achizitie_fallback_for_import', 10, 4);
function webgsm_pret_achizitie_fallback_for_import($value, $object_id, $meta_key, $single) {
    if ($meta_key !== '_pret_achizitie') {
        return $value;
    }
    global $wpdb;
    $stored = $wpdb->get_var($wpdb->prepare(
        "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = '_pret_achizitie' LIMIT 1",
        (int) $object_id
    ));
    if ($stored !== null && $stored !== '' && is_numeric($stored)) {
        return $value;
    }
    $from_import = $wpdb->get_var($wpdb->prepare(
        "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = 'pret_achizitie' LIMIT 1",
        (int) $object_id
    ));
    if ($from_import !== null && $from_import !== '' && is_numeric($from_import)) {
        return $single ? $from_import : array($from_import);
    }
    return $value;
}

/**
 * Citește _source_url cu fallback la source_url (CSV: meta:source_url poate fi salvat fără underscore).
 */
add_filter('get_post_metadata', 'webgsm_source_url_fallback_for_import', 10, 4);
function webgsm_source_url_fallback_for_import($value, $object_id, $meta_key, $single) {
    if ($meta_key !== '_source_url') {
        return $value;
    }
    global $wpdb;
    $stored = $wpdb->get_var($wpdb->prepare(
        "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = '_source_url' LIMIT 1",
        (int) $object_id
    ));
    if ($stored !== null && $stored !== '') {
        return $value;
    }
    $from_import = $wpdb->get_var($wpdb->prepare(
        "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = 'source_url' LIMIT 1",
        (int) $object_id
    ));
    if ($from_import !== null && $from_import !== '') {
        return $single ? $from_import : array($from_import);
    }
    return $value;
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

    // Preț achiziție – un singur câmp; scriptul / importul îl calculează și îl scrie în _pret_achizitie (B2B îl folosește)
    $pret = get_post_meta($post_id, '_pret_achizitie', true);
    if ($pret === '' || $pret === null) {
        $pret = get_post_meta($post_id, 'pret_achizitie', true);
    }
    woocommerce_wp_text_input(array(
        'id'                => '_pret_achizitie',
        'value'             => $pret,
        'label'             => __('Preț achiziție', 'webgsm'),
        'desc_tip'          => true,
        'description'       => __('Cost achiziție (folosit de B2B pentru preț minim, profit). Poate fi setat din import/script.', 'webgsm'),
        'type'              => 'number',
        'custom_attributes' => array('step' => '0.01', 'min' => '0'),
        'placeholder'       => '',
    ));

    // Preț furnizor fără TVA (EUR) – doar informativ, calculat din preț achiziție RON cu TVA
    $ron_cu_tva = ($pret !== '' && $pret !== null && (float) wc_format_decimal($pret) > 0) ? (float) wc_format_decimal($pret) : 0;
    $eur_fara_tva = $ron_cu_tva > 0 ? webgsm_ron_cu_tva_to_eur_fara_tva($ron_cu_tva) : '';
    $curs = webgsm_eur_ron_rate();
    $tva_pct = round((webgsm_achizitie_tva_multiplier() - 1) * 100);
    echo '<p class="form-field _pret_furnizor_eur_info">';
    echo '<label>' . esc_html__('Preț furnizor fără TVA (EUR)', 'webgsm') . '</label>';
    echo '<span class="webgsm-eur-furnizor" style="display:inline-block;padding:8px 12px;background:#f0f0f1;border:1px solid #c3c4c7;border-radius:3px;min-width:100px;">';
    echo $eur_fara_tva !== '' ? esc_html(number_format((float) $eur_fara_tva, 2, ',', '.')) . ' EUR' : '—';
    echo '</span>';
    echo ' <span class="description">' . sprintf(
        esc_html__('Informativ: din preț achiziție (lei cu TVA), scăzut TVA %s%% și curs %s.', 'webgsm'),
        (string) $tva_pct,
        (string) $curs
    ) . '</span>';
    echo '</p>';

    // URL Sursă Furnizor – doar în admin, hiperlink pentru deschidere rapidă (nu e indexat de Google)
    $source_url = get_post_meta($post_id, '_source_url', true);
    if ($source_url === '' || $source_url === null) {
        $source_url = get_post_meta($post_id, 'source_url', true);
    }
    echo '<p class="form-field _source_url_field">';
    echo '<label>' . esc_html__('URL Sursă Furnizor (WebGSM)', 'webgsm') . '</label>';
    if (!empty($source_url) && filter_var($source_url, FILTER_VALIDATE_URL)) {
        echo '<span class="webgsm-source-url-wrap" style="display:inline-flex;align-items:center;gap:6px;flex-wrap:wrap;">';
        echo '<a href="' . esc_url($source_url) . '" target="_blank" rel="noopener noreferrer" class="button button-small" style="margin:0;">';
        echo '<span class="dashicons dashicons-admin-links" style="font-size:16px;width:16px;height:16px;margin-right:4px;"></span>';
        echo esc_html__('Deschide sursa', 'webgsm') . '</a>';
        echo ' <span class="description" style="margin-left:0;">' . esc_html__('Generat la import, folosit pentru audit. Linkul nu apare pe site.', 'webgsm') . '</span>';
        echo '</span>';
    } else {
        echo '<span style="color:#646970;">—</span>';
        echo ' <span class="description">' . esc_html__('Acest link este generat automat la import și este folosit pentru auditul AI.', 'webgsm') . '</span>';
    }
    echo '</p>';

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

    // Preț achiziție – salvat direct; scriptul poate scrie și el în _pret_achizitie
    if (isset($_POST['_pret_achizitie'])) {
        $val = sanitize_text_field(wp_unslash($_POST['_pret_achizitie']));
        update_post_meta($post_id, '_pret_achizitie', $val !== '' ? wc_format_decimal($val) : '');
    }

    // _source_url nu se salvează din formular (câmp readonly); este setat doar la import (update_post_meta).

    // Sincronizare EAN/GTIN: WC folosește _global_unique_id; copiem în gtin_ean pentru get_field('gtin_ean')
    $global_id = isset($_POST['_global_unique_id']) ? wc_clean(wp_unslash($_POST['_global_unique_id'])) : '';
    update_post_meta($post_id, 'gtin_ean', $global_id);
}

// ============================================
// Protecție SEO & Front-end: _source_url invizibil pentru clienți și motoare
// ============================================

/** Exclude _source_url / source_url din atributele produsului afișate în front-end (tab Informații suplimentare etc.). */
add_filter('woocommerce_product_get_attributes', 'webgsm_hide_source_url_from_product_attributes', 10, 2);
function webgsm_hide_source_url_from_product_attributes($attributes, $product) {
    unset($attributes['_source_url'], $attributes['source_url']);
    return $attributes;
}

/** Exclude _source_url și source_url din răspunsul REST API (nu expune linkul în JSON / headless). */
add_filter('woocommerce_rest_prepare_product_object', 'webgsm_remove_source_url_from_rest', 10, 3);
function webgsm_remove_source_url_from_rest($response, $product, $request) {
    $hidden_keys = array('_source_url', 'source_url');
    if (isset($response->data['meta_data'])) {
        $response->data['meta_data'] = array_values(array_filter($response->data['meta_data'], function ($m) use ($hidden_keys) {
            return isset($m['key']) && !in_array($m['key'], $hidden_keys, true);
        }));
    }
    return $response;
}

/** Exclude _source_url și source_url din meta indexate de căutare (Relevanssi etc.). */
add_filter('relevanssi_index_custom_fields', 'webgsm_exclude_source_url_from_search_index', 10, 1);
function webgsm_exclude_source_url_from_search_index($custom_fields) {
    if (!is_array($custom_fields)) {
        return $custom_fields;
    }
    return array_values(array_diff($custom_fields, array('_source_url', 'source_url')));
}

/** Nu expune _source_url / source_url pe front-end (invizibil pentru clienți/Google). */
add_filter('get_post_metadata', 'webgsm_hide_source_url_on_frontend_get', 10, 4);
function webgsm_hide_source_url_on_frontend_get($value, $object_id, $meta_key, $single) {
    if ($meta_key !== '_source_url' && $meta_key !== 'source_url') {
        return $value;
    }
    if (is_admin()) {
        return $value;
    }
    return $single ? '' : array();
}

// ============================================
// Coloană „Sursă” în lista de produse (Admin)
// ============================================

add_filter('manage_edit-product_columns', 'webgsm_add_source_column_to_products', 20);
function webgsm_add_source_column_to_products($columns) {
    $new = array();
    foreach ($columns as $key => $label) {
        $new[$key] = $label;
        if ($key === 'name') {
            $new['webgsm_source'] = __('Sursă', 'webgsm');
        }
    }
    if (!isset($new['webgsm_source'])) {
        $new['webgsm_source'] = __('Sursă', 'webgsm');
    }
    return $new;
}

add_action('manage_product_posts_custom_column', 'webgsm_render_source_column', 10, 2);
function webgsm_render_source_column($column, $post_id) {
    if ($column !== 'webgsm_source') {
        return;
    }
    $url = get_post_meta($post_id, '_source_url', true);
    if (!empty($url) && filter_var($url, FILTER_VALIDATE_URL)) {
        echo '<a href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer" title="' . esc_attr__('Deschide sursa furnizor', 'webgsm') . '" style="text-decoration:none;">';
        echo '<span class="dashicons dashicons-admin-links" style="font-size:18px;width:18px;height:18px;"></span>';
        echo '</a>';
    } else {
        echo '—';
    }
}
