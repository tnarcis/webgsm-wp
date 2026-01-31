<?php
/**
 * WebGSM - Product Specifications Tab
 * Fișă tehnică completă (Specificații Tehnice) – similar GSMNet, Mobilesentrix
 *
 * @package WebGSM
 * @subpackage Martfury-Child
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Elimină tab-ul default "Additional Information"
 */
add_filter('woocommerce_product_tabs', 'webgsm_remove_additional_info_tab', 999);
function webgsm_remove_additional_info_tab($tabs) {
    if (isset($tabs['additional_information'])) {
        unset($tabs['additional_information']);
    }
    return $tabs;
}

add_filter('woocommerce_product_tabs', 'webgsm_disable_additional_info_tab', 1);
function webgsm_disable_additional_info_tab($tabs) {
    unset($tabs['additional_information']);
    return $tabs;
}

/**
 * Adaugă tab-ul "Specificații Tehnice"
 */
add_filter('woocommerce_product_tabs', 'webgsm_add_specs_tab', 15);
function webgsm_add_specs_tab($tabs) {
    global $product;
    if (!$product) {
        return $tabs;
    }
    $has_specs = webgsm_product_has_any_specs($product);
    if ($has_specs) {
        $tabs['specs_tehnice'] = array(
            'title'    => __('Specificații Tehnice', 'webgsm'),
            'priority' => 15,
            'callback' => 'webgsm_specs_tab_content',
        );
    }
    return $tabs;
}

/**
 * Verifică dacă produsul are cel puțin o specificație de afișat (SKU, atribute, ACF, categorii).
 */
function webgsm_product_has_any_specs($product) {
    if ($product->get_sku()) {
        return true;
    }
    if ($product->get_attribute('pa_model')) {
        return true;
    }
    if ($product->get_attribute('pa_tehnologie')) {
        return true;
    }
    if ($product->get_attribute('pa_calitate')) {
        return true;
    }
    $brand_piesa = $product->get_attribute('pa_brand_piesa');
    if (empty($brand_piesa)) {
        $brand_piesa = $product->get_attribute('pa_brand-piesa');
    }
    if ($brand_piesa) {
        return true;
    }
    if (function_exists('get_field')) {
        if (get_field('gtin_ean') !== null && get_field('gtin_ean') !== '') {
            return true;
        }
        if (get_field('coduri_compatibilitate')) {
            return true;
        }
        if (get_field('ic_movable') !== false && get_field('ic_movable') !== null) {
            return true;
        }
        if (get_field('truetone_support') !== false && get_field('truetone_support') !== null) {
            return true;
        }
        if (get_field('garantie_luni')) {
            return true;
        }
        $caracteristici = get_field('caracteristici_tehnice', $product->get_id());
        if (!empty($caracteristici) && is_string($caracteristici)) {
            return true;
        }
    }
    $terms = get_the_terms($product->get_id(), 'product_cat');
    if ($terms && !is_wp_error($terms)) {
        return true;
    }
    return false;
}

/**
 * Returnează "Brand Compatibil" detectat din numele modelului.
 */
function webgsm_specs_brand_from_model($model) {
    if (empty($model)) {
        return '';
    }
    $model_lower = mb_strtolower($model);
    if (strpos($model_lower, 'iphone') !== false || strpos($model_lower, 'ipad') !== false) {
        return 'Apple';
    }
    if (strpos($model_lower, 'galaxy') !== false || strpos($model_lower, 'samsung') !== false) {
        return 'Samsung';
    }
    if (strpos($model_lower, 'huawei') !== false || strpos($model_lower, 'honor') !== false) {
        return 'Huawei';
    }
    if (strpos($model_lower, 'xiaomi') !== false || strpos($model_lower, 'redmi') !== false || strpos($model_lower, 'poco') !== false) {
        return 'Xiaomi';
    }
    return 'Universal';
}

/**
 * Returnează "Tip Produs" (prima categorie părinte) cu etichete frumoase.
 */
function webgsm_specs_tip_produs($product_id) {
    $terms = get_the_terms($product_id, 'product_cat');
    if (!$terms || is_wp_error($terms)) {
        return '';
    }
    $labels = array(
        'ecrane'   => 'Display / Ecran',
        'baterii'  => 'Baterie',
        'camere'   => 'Cameră',
        'flex-uri' => 'Flex / Conector',
        'flexuri'  => 'Flex / Conector',
        'carcase'  => 'Carcasă',
        'accesorii' => 'Accesorii',
    );
    foreach ($terms as $term) {
        $parent_id = (int) $term->parent;
        if ($parent_id === 0) {
            $slug = $term->slug;
            return isset($labels[$slug]) ? $labels[$slug] : $term->name;
        }
    }
    foreach ($terms as $term) {
        if ($term->parent) {
            $parent = get_term($term->parent, 'product_cat');
            if ($parent && !is_wp_error($parent)) {
                $slug = $parent->slug;
                return isset($labels[$slug]) ? $labels[$slug] : $parent->name;
            }
        }
    }
    return isset($terms[0]) ? $terms[0]->name : '';
}

/**
 * Conținutul tab-ului – fișă tehnică în ordinea cerută. Nu afișează rânduri goale.
 */
function webgsm_specs_tab_content() {
    global $product;
    if (!$product) {
        return;
    }

    $specs = array();
    $product_id = $product->get_id();

    // Cod Produs
    $sku = $product->get_sku();
    if ($sku !== null && $sku !== '') {
        $specs[] = array('label' => 'Cod Produs', 'value' => esc_html($sku));
    }

    // EAN (ACF gtin_ean sau WC global_unique_id)
    $ean = '';
    if (function_exists('get_field')) {
        $ean = get_field('gtin_ean', $product_id);
    }
    if (($ean === null || $ean === '') && method_exists($product, 'get_global_unique_id')) {
        $ean = $product->get_global_unique_id();
    }
    if ($ean !== null && $ean !== '') {
        $specs[] = array('label' => 'EAN', 'value' => esc_html($ean));
    }

    // Model Compatibil (atribut WooCommerce)
    $model = $product->get_attribute('pa_model');
    if ($model) {
        $specs[] = array('label' => 'Model Compatibil', 'value' => esc_html($model));
    }

    // Tip Produs (prima categorie părinte)
    $tip = webgsm_specs_tip_produs($product_id);
    if ($tip !== '') {
        $specs[] = array('label' => 'Tip Produs', 'value' => esc_html($tip));
    }

    // Brand Compatibil (detectat din Model) – afișat doar dacă avem model
    if ($model !== '') {
        $brand_compatibil = webgsm_specs_brand_from_model($model);
        $specs[] = array('label' => 'Brand Compatibil', 'value' => esc_html($brand_compatibil));
    }

    // Tehnologie (atribut WooCommerce)
    $tehnologie = $product->get_attribute('pa_tehnologie');
    if ($tehnologie) {
        $specs[] = array('label' => 'Tehnologie', 'value' => esc_html($tehnologie));
    }

    // Brand Piesă (atribut WooCommerce – pa_brand_piesa sau pa_brand-piesa)
    $brand_piesa = $product->get_attribute('pa_brand_piesa');
    if (empty($brand_piesa)) {
        $brand_piesa = $product->get_attribute('pa_brand-piesa');
    }
    if ($brand_piesa) {
        $specs[] = array('label' => 'Brand Piesă', 'value' => esc_html($brand_piesa));
    }

    // Calitate (atribut WooCommerce)
    $calitate = $product->get_attribute('pa_calitate');
    if ($calitate) {
        $specs[] = array('label' => 'Calitate', 'value' => esc_html($calitate));
    }

    // Compatibilitate (ACF)
    if (function_exists('get_field')) {
        $coduri = get_field('coduri_compatibilitate', $product_id);
        if (!empty($coduri)) {
            $specs[] = array('label' => 'Compatibilitate', 'value' => esc_html($coduri));
        }
    }

    // IC Transferabil (ACF)
    if (function_exists('get_field')) {
        $ic_movable = get_field('ic_movable', $product_id);
        if ($ic_movable !== false && $ic_movable !== null) {
            $specs[] = array('label' => 'IC Transferabil', 'value' => $ic_movable ? 'Da ✓' : 'Nu ✗');
        }
    }

    // TrueTone (ACF)
    if (function_exists('get_field')) {
        $truetone = get_field('truetone_support', $product_id);
        if ($truetone !== false && $truetone !== null) {
            $specs[] = array('label' => 'TrueTone', 'value' => $truetone ? 'Suportat ✓' : 'Nu ✗');
        }
    }

    // Garanție (ACF)
    if (function_exists('get_field')) {
        $garantie = get_field('garantie_luni', $product_id);
        if (!empty($garantie)) {
            if ($garantie === 'lifetime') {
                $garantie_text = 'Lifetime (pe viață)';
            } elseif (is_numeric($garantie)) {
                $garantie_text = esc_html($garantie) . ' luni';
            } else {
                $garantie_text = esc_html($garantie);
            }
            $specs[] = array('label' => 'Garanție', 'value' => $garantie_text);
        }
    }

    // Caracteristici Tehnice Adiționale (ACF textarea: "Nume | Valoare" per linie)
    if (function_exists('get_field')) {
        $caracteristici_raw = get_field('caracteristici_tehnice', $product_id);
        if (!empty($caracteristici_raw) && is_string($caracteristici_raw)) {
            $lines = array_filter(array_map('trim', explode("\n", $caracteristici_raw)));
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                $sep = strpos($line, ' | ') !== false ? ' | ' : (strpos($line, '|') !== false ? '|' : null);
                if ($sep !== null) {
                    $parts = array_map('trim', explode($sep, $line, 2));
                    $nume = isset($parts[0]) ? $parts[0] : '';
                    $valoare = isset($parts[1]) ? $parts[1] : '';
                } else {
                    $nume = $line;
                    $valoare = '';
                }
                if ($nume !== '' || $valoare !== '') {
                    $specs[] = array(
                        'label' => $nume !== '' ? esc_html($nume) : '—',
                        'value' => $valoare !== '' ? esc_html($valoare) : '—',
                    );
                }
            }
        }
    }

    if (empty($specs)) {
        return;
    }
    ?>
    <div class="webgsm-specs-tab">
        <table class="webgsm-specs-table widefat">
            <thead>
                <tr>
                    <th>Specificație</th>
                    <th>Valoare</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($specs as $spec) : ?>
                    <tr>
                        <td class="spec-label"><strong><?php echo esc_html($spec['label']); ?></strong></td>
                        <td class="spec-value"><?php echo $spec['value']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <style>
    .webgsm-specs-tab {
        margin: 20px 0;
    }

    .webgsm-specs-table {
        width: 100%;
        border-collapse: collapse;
        margin: 0;
        background: #fff;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        border: 1px solid #e0e0e0;
    }

    .webgsm-specs-table thead {
        background: #f8f9fa;
    }

    .webgsm-specs-table th {
        padding: 12px 15px;
        text-align: left;
        font-weight: 600;
        color: #333;
        border-bottom: 2px solid #e0e0e0;
        font-size: 14px;
    }

    .webgsm-specs-table td {
        padding: 12px 15px;
        border-bottom: 1px solid #f0f0f0;
        font-size: 14px;
    }

    .webgsm-specs-table tbody tr:last-child td {
        border-bottom: none;
    }

    .webgsm-specs-table tbody tr:hover {
        background: #f8f9fa;
    }

    .webgsm-specs-table .spec-label {
        width: 40%;
        color: #666;
    }

    .webgsm-specs-table .spec-value {
        color: #333;
        font-weight: 500;
    }

    @media (max-width: 768px) {
        .webgsm-specs-tab {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .webgsm-specs-table {
            font-size: 13px;
            min-width: 280px;
        }

        .webgsm-specs-table th,
        .webgsm-specs-table td {
            padding: 10px 12px;
        }

        .webgsm-specs-table .spec-label {
            width: 35%;
        }
    }
    </style>
    <?php
}
