<?php
/**
 * WebGSM — Badge stoc + timp livrare (pagina produs)
 *
 * Badge: locatie_stoc + quantity + preorder_enabled
 * Termen livrare: derivat din locatie_stoc (toată țara, o singură linie)
 *   — override când Gestiune trimite timp_livrare: 3-5 zile | 5-7 zile | la comanda
 *
 * @package WebGSM
 */

if (!defined('ABSPATH')) {
    exit;
}

function webgsm_normalize_locatie_stoc($locatie) {
    $locatie = is_string($locatie) ? trim($locatie) : '';

    $map = array(
        'stoc'               => 'magazin_webgsm',
        'magazin'            => 'magazin_webgsm',
        'depozit-principal'  => 'depozit_central',
        'depozit-secundar'   => 'depozit_central',
        'in-tranzit'         => 'depozit_central',
        'indisponibil'       => 'furnizor_extern',
    );

    return isset($map[$locatie]) ? $map[$locatie] : $locatie;
}

function webgsm_normalize_timp_livrare($timp) {
    $timp = is_string($timp) ? trim($timp) : '';

    $map = array(
        '3-5zile'    => '3-5 zile',
        '5-7zile'    => '5-7 zile',
        'la_comanda' => 'la comanda',
    );

    return isset($map[$timp]) ? $map[$timp] : $timp;
}

/** Termene livrare setate explicit din Gestiune — override față de locatie_stoc. */
function webgsm_timp_livrare_override_text($timp_livrare) {
    $overrides = array(
        '3-5 zile'   => 'Livrare estimată: 3–5 zile lucrătoare',
        '5-7 zile'   => 'Livrare estimată: 5–7 zile lucrătoare',
        'la comanda' => 'Livrare la comandă',
    );

    $timp = webgsm_normalize_timp_livrare($timp_livrare);

    return isset($overrides[$timp]) ? $overrides[$timp] : '';
}

/**
 * Termen livrare afișat pe site — o linie, toată țara.
 *
 * Reguli:
 * - timp_livrare din Gestiune (3-5 zile | 5-7 zile | la comanda) → override
 * - magazin_webgsm / depozit_central → Livrare în 24–48h
 * - furnizor_extern → Livrare în 48–72h
 * - precomanda → Livrare estimată 3–5 zile lucrătoare
 */
function webgsm_get_delivery_lines($locatie_stoc, $timp_livrare_raw = '') {
    $icon_box = '<svg class="delivery-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>';

    if ($locatie_stoc === 'epuizat') {
        return array(
            array('icon' => '', 'text' => 'Momentan indisponibil'),
        );
    }

    $override_text = webgsm_timp_livrare_override_text($timp_livrare_raw);
    if ($override_text !== '') {
        $text = $override_text;
    } elseif ($locatie_stoc === 'furnizor_extern') {
        $text = 'Livrare în 48–72h';
    } elseif ($locatie_stoc === 'precomanda') {
        $text = 'Livrare estimată: 3–5 zile lucrătoare';
    } elseif ($locatie_stoc === 'magazin_webgsm' || $locatie_stoc === 'depozit_central') {
        $text = 'Livrare în 24–48h';
    } else {
        $text = 'Livrare în 24–48h';
    }

    return array(
        array('icon' => $icon_box, 'text' => $text),
    );
}

/**
 * Badge — locatie_stoc + quantity + preorder_enabled (neschimbat).
 */
function webgsm_resolve_stock_display(WC_Product $product) {
    $product_id = $product->get_id();
    $stock_qty = $product->get_stock_quantity();
    $qty = $stock_qty === null ? null : (int) $stock_qty;
    $is_in_stock = $product->is_in_stock();
    $preorder_enabled = $product->backorders_allowed();

    $icon_green = '<svg class="stock-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/></svg>';
    $icon_yellow = '<svg class="stock-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/></svg>';
    $icon_red = '<svg class="stock-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/></svg>';

    $acf_active = function_exists('get_field');
    $locatie_raw = $acf_active ? get_field('locatie_stoc', $product_id) : '';
    $locatie_stoc = webgsm_normalize_locatie_stoc($locatie_raw);
    $timp_raw = $acf_active ? (string) get_field('timp_livrare', $product_id) : '';

    if (!$is_in_stock && !$preorder_enabled) {
        return array(
            'badge_class'   => 'wgsm-badge-outofstock',
            'badge_icon'    => $icon_red,
            'badge_text'    => 'Stoc epuizat',
            'delivery_info' => webgsm_get_delivery_lines('epuizat'),
        );
    }

    $badge_class = '';
    $badge_icon = '';
    $badge_text = '';

    if ($locatie_stoc === 'magazin_webgsm' && $qty !== null && $qty >= 1) {
        if ($qty === 1) {
            $badge_class = 'wgsm-badge-limited';
            $badge_icon = $icon_yellow;
            $badge_text = 'Stoc limitat';
        } else {
            $badge_class = 'wgsm-badge-stock';
            $badge_icon = $icon_green;
            $badge_text = 'În stoc';
        }
    } elseif ($locatie_stoc === 'depozit_central' && $is_in_stock) {
        $badge_class = 'wgsm-badge-stock';
        $badge_icon = $icon_green;
        $badge_text = 'În stoc depozit';
    } elseif ($locatie_stoc === 'precomanda' && $is_in_stock) {
        $badge_class = 'wgsm-badge-limited';
        $badge_icon = $icon_yellow;
        $badge_text = 'Disponibil la precomandă';
    } elseif ($locatie_stoc === 'furnizor_extern' && $is_in_stock) {
        $badge_class = 'wgsm-badge-limited';
        $badge_icon = $icon_yellow;
        $badge_text = 'Disponibil';
    } elseif ($locatie_stoc === 'magazin_webgsm' && $is_in_stock) {
        $badge_class = 'wgsm-badge-limited';
        $badge_icon = $icon_yellow;
        $badge_text = 'Disponibil la comandă';
    } else {
        if ($is_in_stock) {
            if ($qty !== null && $qty === 1) {
                $badge_class = 'wgsm-badge-limited';
                $badge_icon = $icon_yellow;
                $badge_text = 'Stoc limitat';
            } else {
                $badge_class = 'wgsm-badge-stock';
                $badge_icon = $icon_green;
                $badge_text = 'În stoc';
            }
        } else {
            return array(
                'badge_class'   => 'wgsm-badge-outofstock',
                'badge_icon'    => $icon_red,
                'badge_text'    => 'Stoc epuizat',
                'delivery_info' => webgsm_get_delivery_lines('epuizat'),
            );
        }
    }

    return array(
        'badge_class'   => $badge_class,
        'badge_icon'    => $badge_icon,
        'badge_text'    => $badge_text,
        'delivery_info' => webgsm_get_delivery_lines($locatie_stoc, $timp_raw),
    );
}

add_action('woocommerce_single_product_summary', 'webgsm_stock_badge', 15);

function webgsm_stock_badge() {
    global $product;

    if (!$product instanceof WC_Product) {
        return;
    }

    $display = webgsm_resolve_stock_display($product);

    if (empty($display['badge_class']) || empty($display['badge_text'])) {
        return;
    }
    ?>
    <div class="webgsm-stock-badge">
        <div class="webgsm-badge-header">
            <?php echo $display['badge_icon']; ?>
            <span class="badge-text"><?php echo esc_html($display['badge_text']); ?></span>
        </div>
        <?php if (!empty($display['delivery_info'])) : ?>
            <div class="webgsm-delivery-info">
                <?php foreach ($display['delivery_info'] as $info) : ?>
                    <div class="delivery-line">
                        <?php if (!empty($info['icon'])) : ?>
                            <span class="delivery-icon-wrapper"><?php echo $info['icon']; ?></span>
                        <?php endif; ?>
                        <span class="delivery-text"><?php echo esc_html($info['text']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
