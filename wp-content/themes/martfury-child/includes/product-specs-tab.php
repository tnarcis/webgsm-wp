<?php
/**
 * WebGSM - Product Specifications Tab
 * Adaugă tab "Specificații Tehnice" pe pagina de produs cu date ACF
 * 
 * @package WebGSM
 * @subpackage Martfury-Child
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit; // Exit dacă accesat direct

/**
 * Verifică dacă ACF este activ
 */
if (!function_exists('get_field')) {
    return; // ACF nu este activ
}

/**
 * Elimină tab-ul default "Additional Information" (atribute WooCommerce)
 * pentru a evita suprapunerea cu tab-ul nostru "Specificații Tehnice"
 * Folosim prioritate 999 pentru a rula după toate celelalte filtre (inclusiv Martfury)
 */
add_filter('woocommerce_product_tabs', 'webgsm_remove_additional_info_tab', 999);
function webgsm_remove_additional_info_tab($tabs) {
    // Elimină tab-ul default "Additional Information" dacă există
    if (isset($tabs['additional_information'])) {
        unset($tabs['additional_information']);
    }
    return $tabs;
}

/**
 * Dezactivează complet tab-ul "Additional Information" din WooCommerce
 * Folosim hook-ul specific WooCommerce pentru a preveni adăugarea tab-ului
 */
add_filter('woocommerce_product_tabs', 'webgsm_disable_additional_info_tab', 1);
function webgsm_disable_additional_info_tab($tabs) {
    // Elimină tab-ul înainte ca alte filtre să-l modifice
    unset($tabs['additional_information']);
    return $tabs;
}

/**
 * Adaugă tab-ul "Specificații Tehnice"
 */
add_filter('woocommerce_product_tabs', 'webgsm_add_specs_tab', 15);
function webgsm_add_specs_tab($tabs) {
    global $product;
    
    // Verifică dacă există cel puțin un câmp ACF completat
    $has_specs = false;
    $spec_fields = array(
        'coduri_compatibilitate',
        'ic_movable',
        'truetone_support',
        'garantie_luni'
    );
    
    foreach ($spec_fields as $field) {
        $value = get_field($field);
        if (!empty($value) || ($field === 'ic_movable' && $value !== false && $value !== null) || ($field === 'truetone_support' && $value !== false && $value !== null)) {
            $has_specs = true;
            break;
        }
    }
    
    // Adaugă tab-ul doar dacă există specificații sau SKU
    // Folosim key 'specs_tehnice' pentru a evita conflictele
    if ($has_specs || $product->get_sku()) {
        $tabs['specs_tehnice'] = array(
            'title'    => __('Specificații Tehnice', 'webgsm'),
            'priority' => 15,
            'callback' => 'webgsm_specs_tab_content'
        );
    }
    
    return $tabs;
}

/**
 * Conținutul tab-ului "Specificații Tehnice"
 */
function webgsm_specs_tab_content() {
    global $product;
    
    // Colectează datele
    $specs = array();
    
    // SKU (WooCommerce standard)
    $sku = $product->get_sku();
    if (!empty($sku)) {
        $specs[] = array(
            'label' => 'SKU',
            'value' => esc_html($sku)
        );
    }
    
    // Coduri Compatibilitate
    $coduri = get_field('coduri_compatibilitate');
    if (!empty($coduri)) {
        $specs[] = array(
            'label' => 'Compatibilitate',
            'value' => esc_html($coduri)
        );
    }
    
    // IC Transferabil (IC Movable)
    $ic_movable = get_field('ic_movable');
    if ($ic_movable !== false && $ic_movable !== null) {
        $specs[] = array(
            'label' => 'IC Transferabil',
            'value' => $ic_movable ? 'Da ✓' : 'Nu ✗'
        );
    }
    
    // TrueTone Support
    $truetone = get_field('truetone_support');
    if ($truetone !== false && $truetone !== null) {
        $specs[] = array(
            'label' => 'TrueTone',
            'value' => $truetone ? 'Suportat ✓' : 'Nu ✗'
        );
    }
    
    // Garanție
    $garantie = get_field('garantie_luni');
    if (!empty($garantie)) {
        // Dacă este "lifetime", afișează "Lifetime"
        if ($garantie === 'lifetime' || $garantie === 'lifetime') {
            $garantie_text = 'Lifetime (pe viață)';
        } elseif (is_numeric($garantie)) {
            $garantie_text = esc_html($garantie) . ' luni';
        } else {
            $garantie_text = esc_html($garantie);
        }
        
        $specs[] = array(
            'label' => 'Garanție',
            'value' => $garantie_text
        );
    }
    
    // Afișează tabelul doar dacă există specificații
    if (!empty($specs)) {
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
        
        /* Responsive */
        @media (max-width: 768px) {
            .webgsm-specs-table {
                font-size: 13px;
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
}
