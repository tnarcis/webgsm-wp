<?php
/**
 * Manoperă montaj — aliniat cu WebGSM backend (labor_pricing.py + woo_montaj sync).
 *
 * Meta sincronizate: _webgsm_montaj_price, _webgsm_montaj_includes_transport,
 * _webgsm_allow_microsoldering, _webgsm_microsoldering_price, _webgsm_montaj_service_code
 *
 * @package WebGSM
 */
if (!defined('ABSPATH')) {
    exit;
}

define('WEBGSM_MICROSOLDERING_DEFAULT', 150.0);

function webgsm_montaj_api_base() {
    return untrailingslashit((string) apply_filters(
        'webgsm_estimate_api_base',
        get_option('webgsm_estimate_api_base', 'https://server.webgsm.ro')
    ));
}

function webgsm_montaj_bool_meta($value) {
    if ($value === '' || $value === null) {
        return false;
    }
    if (is_bool($value)) {
        return $value;
    }
    $v = strtolower(trim((string) $value));
    return in_array($v, array('1', 'true', 'yes', 'da', 'on'), true);
}

function webgsm_montaj_float_meta($value) {
    if ($value === '' || $value === null || !is_numeric($value)) {
        return 0.0;
    }
    return max(0.0, (float) $value);
}

/**
 * @return array{price:float,includes_transport:bool,allow_microsoldering:bool,microsoldering_price:float,service_code:string,has_montaj:bool}
 */
function webgsm_montaj_get_product_meta($product_id) {
    $product_id = (int) $product_id;
    $price      = webgsm_montaj_float_meta(get_post_meta($product_id, '_webgsm_montaj_price', true));

    if ($price <= 0) {
        $price = webgsm_montaj_float_meta(get_post_meta($product_id, 'montaj_price_ron', true));
    }

    $includes_transport = webgsm_montaj_bool_meta(get_post_meta($product_id, '_webgsm_montaj_includes_transport', true));
    $allow_micro        = webgsm_montaj_bool_meta(get_post_meta($product_id, '_webgsm_allow_microsoldering', true));
    if (!$allow_micro) {
        $allow_micro = webgsm_montaj_bool_meta(get_post_meta($product_id, '_webgsm_allow_microsoldering_addon', true));
    }
    if (!$allow_micro) {
        $allow_micro = webgsm_montaj_bool_meta(get_post_meta($product_id, 'allow_microsoldering_addon', true));
    }

    $micro_price = webgsm_montaj_float_meta(get_post_meta($product_id, '_webgsm_microsoldering_price', true));
    if ($micro_price <= 0) {
        $micro_price = WEBGSM_MICROSOLDERING_DEFAULT;
    }

    $service_code = (string) get_post_meta($product_id, '_webgsm_montaj_service_code', true);
    if ($service_code === '') {
        $service_code = (string) get_post_meta($product_id, 'montaj_service_code', true);
    }

    return array(
        'price'                 => $price,
        'includes_transport'    => $includes_transport,
        'allow_microsoldering'  => $allow_micro,
        'microsoldering_price'  => $micro_price,
        'service_code'          => sanitize_key($service_code),
        'has_montaj'            => $price > 0,
    );
}

/**
 * Quote local — MAX montaj + SUM microsoldering (ca labor_pricing.py).
 *
 * @param array<int,array{product_id:int,microsoldering?:bool}> $items
 */
function webgsm_montaj_quote_local(array $items) {
    $montaj_prices       = array();
    $transport_included  = false;
    $micro_total         = 0.0;
    $micro_price_used    = WEBGSM_MICROSOLDERING_DEFAULT;
    $lines               = array();

    foreach ($items as $row) {
        $pid  = (int) ($row['product_id'] ?? 0);
        if (!$pid) {
            continue;
        }
        $meta = webgsm_montaj_get_product_meta($pid);
        if ($meta['price'] > 0) {
            $montaj_prices[] = $meta['price'];
        }
        if ($meta['includes_transport']) {
            $transport_included = true;
        }
        $want_micro = !empty($row['microsoldering']) && $meta['allow_microsoldering'];
        if ($want_micro) {
            $micro_total += $meta['microsoldering_price'];
            $micro_price_used = $meta['microsoldering_price'];
        }
        $lines[] = array(
            'product_id'           => $pid,
            'montaj_price'         => $meta['price'],
            'includes_transport' => $meta['includes_transport'],
            'microsoldering'       => $want_micro,
            'microsoldering_price' => $want_micro ? $meta['microsoldering_price'] : 0,
        );
    }

    $montaj_max   = $montaj_prices ? max($montaj_prices) : 0.0;
    $labor_total  = $montaj_max + $micro_total;

    return array(
        'source'               => 'local',
        'montaj'               => $montaj_max,
        'montaj_html'          => $montaj_max > 0 ? wp_strip_all_tags(wc_price($montaj_max)) : '',
        'microsoldering'       => $micro_total,
        'microsoldering_html'  => $micro_total > 0 ? wp_strip_all_tags(wc_price($micro_total)) : '',
        'total_labor'          => $labor_total,
        'total_labor_html'     => $labor_total > 0 ? wp_strip_all_tags(wc_price($labor_total)) : '',
        'transport_included'   => $transport_included,
        'lines'                => $lines,
    );
}

/**
 * Încearcă POST /estimate/quote; fallback local.
 */
function webgsm_montaj_quote(array $items) {
    $payload_items = array();
    foreach ($items as $row) {
        $pid = (int) ($row['product_id'] ?? 0);
        if ($pid) {
            $payload_items[] = array(
                'product_id'     => $pid,
                'microsoldering' => !empty($row['microsoldering']),
            );
        }
    }
    if (!$payload_items) {
        return webgsm_montaj_quote_local(array());
    }

    $api = webgsm_montaj_api_quote($payload_items);
    if (is_array($api)) {
        return $api;
    }

    return webgsm_montaj_quote_local($items);
}

function webgsm_montaj_api_quote(array $items) {
    $url = webgsm_montaj_api_base() . '/estimate/quote';
    $body = wp_json_encode(array(
        'items'    => $items,
        'channel'  => 'timisoara',
    ));

    $response = wp_remote_post($url, array(
        'timeout' => 10,
        'headers' => array('Content-Type' => 'application/json'),
        'body'    => $body,
    ));

    if (is_wp_error($response)) {
        return null;
    }
    $code = (int) wp_remote_retrieve_response_code($response);
    if ($code < 200 || $code >= 300) {
        return null;
    }
    $json = json_decode(wp_remote_retrieve_body($response), true);
    if (!is_array($json)) {
        return null;
    }

    $montaj = isset($json['montaj']) ? (float) $json['montaj'] : (isset($json['montaj_ron']) ? (float) $json['montaj_ron'] : 0);
    $micro  = isset($json['microsoldering']) ? (float) $json['microsoldering'] : (isset($json['microsoldering_ron']) ? (float) $json['microsoldering_ron'] : 0);
    $total  = isset($json['total_labor']) ? (float) $json['total_labor'] : ($montaj + $micro);

    return array(
        'source'              => 'api',
        'montaj'              => $montaj,
        'montaj_html'         => $montaj > 0 ? wp_strip_all_tags(wc_price($montaj)) : '',
        'microsoldering'      => $micro,
        'microsoldering_html' => $micro > 0 ? wp_strip_all_tags(wc_price($micro)) : '',
        'total_labor'         => $total,
        'total_labor_html'    => $total > 0 ? wp_strip_all_tags(wc_price($total)) : '',
        'transport_included'  => !empty($json['transport_included']),
        'lines'               => isset($json['lines']) && is_array($json['lines']) ? $json['lines'] : array(),
    );
}

/** Manoperă pentru o singură piesă (Repair Reel / produs). */
function webgsm_montaj_for_product($product_id) {
    $meta = webgsm_montaj_get_product_meta($product_id);
    return array(
        'amount'               => $meta['price'] > 0 ? $meta['price'] : null,
        'includes_transport'   => $meta['includes_transport'],
        'allow_microsoldering' => $meta['allow_microsoldering'],
        'microsoldering_price' => $meta['microsoldering_price'],
        'service_code'         => $meta['service_code'],
        'source'               => $meta['price'] > 0 ? 'montaj_meta' : null,
    );
}

function webgsm_montaj_transport_label($includes_transport) {
    if ($includes_transport) {
        return 'incl. transport dus-întors';
    }
    return '';
}

/* ─── Pagină produs ─── */

add_action('woocommerce_single_product_summary', 'webgsm_montaj_single_product_block', 12);
add_action('woocommerce_before_add_to_cart_button', 'webgsm_montaj_add_to_cart_fields', 5);

function webgsm_montaj_single_product_block() {
    global $product;
    if (!$product || !is_a($product, 'WC_Product')) {
        return;
    }

    $meta = webgsm_montaj_get_product_meta($product->get_id());
    if (!$meta['has_montaj']) {
        return;
    }

    $part_price = (float) wc_get_price_to_display($product);
    $total      = $part_price + $meta['price'];
    $transport  = webgsm_montaj_transport_label($meta['includes_transport']);
    ?>
    <div class="webgsm-montaj-box" id="webgsm-montaj-box" data-product-id="<?php echo esc_attr((string) $product->get_id()); ?>" data-montaj="<?php echo esc_attr((string) $meta['price']); ?>" data-micro="<?php echo esc_attr((string) $meta['microsoldering_price']); ?>">
        <div class="webgsm-montaj-rows">
            <div class="webgsm-montaj-row">
                <span>Preț piesă</span>
                <strong><?php echo wp_kses_post(wc_price($part_price)); ?></strong>
            </div>
            <div class="webgsm-montaj-row">
                <span>Manoperă<?php echo $transport ? ' <em>(' . esc_html($transport) . ')</em>' : ''; ?></span>
                <strong class="webgsm-montaj-labor"><?php echo wp_kses_post(wc_price($meta['price'])); ?></strong>
            </div>
            <div class="webgsm-montaj-row webgsm-montaj-total">
                <span>Total cu montaj</span>
                <strong id="webgsm-montaj-total"><?php echo wp_kses_post(wc_price($total)); ?></strong>
            </div>
        </div>
        <p class="webgsm-montaj-note">Bifează montajul la adăugare în coș (formularul de mai jos). Manoperă MAX per comandă. Timișoara — programare WhatsApp.</p>
    </div>
    <?php
    webgsm_montaj_enqueue_assets();
}

function webgsm_montaj_add_to_cart_fields() {
    global $product;
    if (!$product || !is_a($product, 'WC_Product')) {
        return;
    }
    $meta = webgsm_montaj_get_product_meta($product->get_id());
    if (!$meta['has_montaj']) {
        return;
    }
    webgsm_montaj_enqueue_assets();
    ?>
    <div class="webgsm-montaj-cart-fields">
        <label class="webgsm-montaj-check webgsm-montaj-check-main">
            <input type="checkbox" name="webgsm_add_montaj" id="webgsm_add_montaj" value="1">
            <span>Adaugă montaj la comandă</span>
        </label>
        <?php if ($meta['allow_microsoldering']) : ?>
        <label class="webgsm-montaj-check">
            <input type="checkbox" name="webgsm_microsoldering" id="webgsm_microsoldering" value="1">
            <span>Mutare cip original (+<?php echo wp_kses_post(wc_price($meta['microsoldering_price'])); ?>)</span>
        </label>
        <?php endif; ?>
    </div>
    <?php
}

function webgsm_montaj_enqueue_assets() {
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    wp_register_style('webgsm-montaj', get_stylesheet_directory_uri() . '/assets/css/webgsm-montaj.css', array(), '1.0');
    wp_enqueue_style('webgsm-montaj');
    wp_register_script('webgsm-montaj', get_stylesheet_directory_uri() . '/assets/js/webgsm-montaj.js', array(), '1.0', true);
    wp_enqueue_script('webgsm-montaj');
    wp_localize_script('webgsm-montaj', 'webgsmMontaj', array(
        'ajax'  => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('webgsm_montaj'),
    ));
}

/* ─── Coș: meta la add-to-cart ─── */

add_filter('woocommerce_add_cart_item_data', function ($cart_item_data, $product_id) {
    if (!empty($_POST['webgsm_add_montaj'])) {
        $cart_item_data['webgsm_add_montaj'] = 1;
    }
    if (!empty($_POST['webgsm_microsoldering'])) {
        $cart_item_data['webgsm_microsoldering'] = 1;
    }
    return $cart_item_data;
}, 10, 2);

add_filter('woocommerce_get_item_data', function ($item_data, $cart_item) {
    if (!empty($cart_item['webgsm_add_montaj'])) {
        $item_data[] = array(
            'key'   => 'Montaj',
            'value' => 'Da',
        );
    }
    if (!empty($cart_item['webgsm_microsoldering'])) {
        $item_data[] = array(
            'key'   => 'Microsoldering',
            'value' => 'Mutare cip original',
        );
    }
    return $item_data;
}, 10, 2);

add_action('woocommerce_checkout_create_order_line_item', function ($item, $cart_item_key, $values) {
    if (!empty($values['webgsm_add_montaj'])) {
        $item->add_meta_data('_webgsm_add_montaj', '1', true);
    }
    if (!empty($values['webgsm_microsoldering'])) {
        $item->add_meta_data('_webgsm_microsoldering', '1', true);
    }
}, 10, 3);

add_action('woocommerce_cart_calculate_fees', function ($cart) {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }
    if (!$cart || $cart->is_empty()) {
        return;
    }

    $items = array();
    foreach ($cart->get_cart() as $cart_item) {
        if (empty($cart_item['webgsm_add_montaj'])) {
            continue;
        }
        $items[] = array(
            'product_id'     => (int) $cart_item['product_id'],
            'microsoldering' => !empty($cart_item['webgsm_microsoldering']),
        );
    }
    if (!$items) {
        return;
    }

    $quote = webgsm_montaj_quote($items);
    if (empty($quote['total_labor']) || (float) $quote['total_labor'] <= 0) {
        return;
    }

    $label = 'Manoperă montaj';
    if (!empty($quote['transport_included'])) {
        $label .= ' (incl. transport)';
    }
    if (!empty($quote['microsoldering']) && (float) $quote['microsoldering'] > 0) {
        $label .= ' + microsoldering';
    }

    $cart->add_fee($label, (float) $quote['total_labor'], false);
}, 20);

/* ─── AJAX quote (coș / reel) ─── */

add_action('wp_ajax_webgsm_montaj_quote', 'webgsm_montaj_ajax_quote');
add_action('wp_ajax_nopriv_webgsm_montaj_quote', 'webgsm_montaj_ajax_quote');

function webgsm_montaj_ajax_quote() {
    check_ajax_referer('webgsm_montaj', 'nonce');

    $raw   = isset($_POST['items']) ? wp_unslash($_POST['items']) : '';
    $items = json_decode(is_string($raw) ? $raw : '', true);
    if (!is_array($items)) {
        wp_send_json_error(array('message' => 'Date invalide.'), 400);
    }

    $clean = array();
    foreach ($items as $row) {
        if (!is_array($row)) {
            continue;
        }
        $pid = (int) ($row['product_id'] ?? 0);
        if ($pid) {
            $clean[] = array(
                'product_id'     => $pid,
                'microsoldering' => !empty($row['microsoldering']),
            );
        }
    }

    wp_send_json_success(webgsm_montaj_quote($clean));
}

/* ─── Admin: URL API estimator ─── */

add_action('admin_init', function () {
    register_setting('general', 'webgsm_estimate_api_base', array(
        'type'              => 'string',
        'sanitize_callback' => 'esc_url_raw',
        'default'           => 'https://server.webgsm.ro',
    ));
    add_settings_field(
        'webgsm_estimate_api_base',
        'WebGSM Estimate API base',
        function () {
            $val = esc_attr((string) get_option('webgsm_estimate_api_base', 'https://server.webgsm.ro'));
            echo '<input type="url" id="webgsm_estimate_api_base" name="webgsm_estimate_api_base" value="' . $val . '" class="regular-text" />';
            echo '<p class="description">Baza pentru <code>/estimate/quote</code>. Dacă API-ul nu răspunde, site-ul calculează local (MAX montaj + addon).</p>';
        },
        'general'
    );
});
