<?php
/**
 * MODUL FACTURI - Oblio
 * Generează facturi automate (cu descărcare gestiune) și permite descărcare PDF din cont client
 */

// =============================================
// PAGINĂ SETĂRI OBLIO ÎN ADMIN
// =============================================

add_action('admin_menu', function() {
    add_submenu_page(
        'woocommerce',
        'Setări Oblio',
        'Setări Oblio',
        'manage_woocommerce',
        'oblio-settings',
        'render_oblio_settings_page'
    );
});

function render_oblio_settings_page() {
    if (isset($_POST['save_oblio_settings']) && wp_verify_nonce($_POST['oblio_nonce'], 'save_oblio')) {
        update_option('oblio_api_active', isset($_POST['oblio_api_active']) ? 1 : 0);
        update_option('oblio_auto_generate', isset($_POST['oblio_auto_generate']) ? 1 : 0);
        update_option('oblio_use_stock', isset($_POST['oblio_use_stock']) ? 1 : 0);
        update_option('oblio_email', sanitize_email($_POST['oblio_email']));
        update_option('oblio_secret', sanitize_text_field($_POST['oblio_secret']));
        update_option('oblio_cif', sanitize_text_field($_POST['oblio_cif']));
        update_option('oblio_serie', sanitize_text_field($_POST['oblio_serie']));
        update_option('oblio_management', sanitize_text_field($_POST['oblio_management']));
        update_option('oblio_workstation', sanitize_text_field($_POST['oblio_workstation']));
        update_option('oblio_tva', floatval($_POST['oblio_tva']));
        update_option('oblio_efactura_via_oblio', isset($_POST['oblio_efactura_via_oblio']) ? 1 : 0);
        delete_transient('oblio_access_token');
        echo '<div class="notice notice-success"><p>Setările au fost salvate!</p></div>';
    }

    $api_active = get_option('oblio_api_active', 0);
    $auto_generate = get_option('oblio_auto_generate', 1);
    $use_stock = get_option('oblio_use_stock', 1);
    $efactura_via_oblio = get_option('oblio_efactura_via_oblio', 1);
    $email = get_option('oblio_email', '');
    $secret = get_option('oblio_secret', '');
    $cif = get_option('oblio_cif', 'RO31902941');
    $serie = get_option('oblio_serie', 'WEB');
    $management = get_option('oblio_management', '');
    $workstation = get_option('oblio_workstation', 'Sediu');
    $tva = get_option('oblio_tva', 21);
    ?>
    <div class="wrap">
        <h1>Setări Oblio</h1>

        <form method="post">
            <?php wp_nonce_field('save_oblio', 'oblio_nonce'); ?>

            <table class="form-table">
                <tr>
                    <th>Status API</th>
                    <td>
                        <label style="display:inline-block; padding:10px 20px; background:<?php echo $api_active ? '#d4edda' : '#fff3cd'; ?>; border-radius:5px;">
                            <input type="checkbox" name="oblio_api_active" value="1" <?php checked($api_active, 1); ?>>
                            <strong style="font-size:16px;">API Activ</strong>
                        </label>
                        <p class="description" style="margin-top:10px;">
                            <?php if ($api_active): ?>
                                <span style="color:green;">API-ul este ACTIV</span>
                            <?php else: ?>
                                <span style="color:orange;">API-ul este OPRIT (mod test) – poți genera facturi manual din comenzi</span>
                            <?php endif; ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th>Generează factură automat</th>
                    <td>
                        <label style="display:inline-block; padding:8px 16px; background:#f0f6fc; border-radius:5px;">
                            <input type="checkbox" name="oblio_auto_generate" value="1" <?php checked($auto_generate, 1); ?>>
                            <strong>La plată online / la livrare (ramburs)</strong>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th>Descarcă stoc (gestiune)</th>
                    <td>
                        <label style="display:inline-block; padding:8px 16px; background:#f0f6fc; border-radius:5px;">
                            <input type="checkbox" name="oblio_use_stock" value="1" <?php checked($use_stock, 1); ?>>
                            <strong>useStock = 1 la emitere factură</strong>
                        </label>
                        <p class="description">Necesită gestiune activă în Oblio și produse cu același cod (SKU) ca în magazin.</p>
                    </td>
                </tr>
                <tr>
                    <th>e-Factura (SPV)</th>
                    <td>
                        <label style="display:inline-block; padding:8px 16px; background:#f0f6fc; border-radius:5px;">
                            <input type="checkbox" name="oblio_efactura_via_oblio" value="1" <?php checked($efactura_via_oblio, 1); ?>>
                            <strong>e-Factura gestionată de Oblio</strong>
                        </label>
                        <p class="description">
                            WebGSM <strong>nu</strong> trimite direct către ANAF SPV. Dacă e-Factura e activă în contul Oblio,
                            statusul SPV este stocat pe comandă (<code>_oblio_efactura_status</code>) după emitere.
                            Configurează e-Factura în Oblio → Setări → e-Factura.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th>Email Oblio (client_id)</th>
                    <td><input type="email" name="oblio_email" value="<?php echo esc_attr($email); ?>" class="regular-text" required></td>
                </tr>
                <tr>
                    <th>Token API (client_secret)</th>
                    <td>
                        <input type="text" name="oblio_secret" value="<?php echo esc_attr($secret); ?>" class="regular-text" autocomplete="off">
                        <p class="description">Din Oblio → Setări → Date Cont. Se regenerează la resetare parolă.</p>
                    </td>
                </tr>
                <tr>
                    <th>CIF Firmă</th>
                    <td><input type="text" name="oblio_cif" value="<?php echo esc_attr($cif); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th>Serie Factură</th>
                    <td><input type="text" name="oblio_serie" value="<?php echo esc_attr($serie); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th>Gestiune</th>
                    <td>
                        <input type="text" name="oblio_management" value="<?php echo esc_attr($management); ?>" class="regular-text" placeholder="ex: Magazin">
                        <p class="description">Numele exact al gestiunii din Oblio (nomenclator management).</p>
                    </td>
                </tr>
                <tr>
                    <th>Punct de lucru</th>
                    <td>
                        <input type="text" name="oblio_workstation" value="<?php echo esc_attr($workstation); ?>" class="regular-text" placeholder="Sediu">
                    </td>
                </tr>
                <tr>
                    <th>Cotă TVA Fallback (%)</th>
                    <td>
                        <input type="number" name="oblio_tva" value="<?php echo esc_attr($tva); ?>" class="small-text" step="1" min="0" max="100">
                        <p class="description">
                            TVA-ul se ia din WooCommerce. Fallback doar dacă nu există taxe pe linie.
                        </p>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" name="save_oblio_settings" class="button button-primary">Salvează setările</button>
            </p>
        </form>

        <hr>
        <h3>Informații</h3>
        <ul>
            <li><strong>Automat:</strong> card → Processing; ramburs/BACS → Completed.</li>
            <li><strong>Manual:</strong> buton „Generează” în listă / pagina comenzii.</li>
            <li><strong>PF / PJ:</strong> date din Date Facturare / billing.</li>
            <li><strong>SKU:</strong> trebuie să coincidă cu codul produsului din Oblio pentru descărcare stoc.</li>
        </ul>

        <hr>
        <h3>Instrumente</h3>
        <p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=oblio-settings&action=generate_skus')); ?>"
               class="button button-secondary"
               onclick="return confirm('Generează SKU pentru toate produsele fără SKU?');">
                Generează SKU pentru toate produsele
            </a>
        </p>

        <?php
        if (isset($_GET['action']) && $_GET['action'] === 'generate_skus') {
            $generated = webgsm_bulk_generate_skus();
            echo '<div class="notice notice-success"><p>Au fost generate ' . (int) $generated . ' SKU-uri!</p></div>';
        }
        ?>
    </div>
    <?php
}

function webgsm_bulk_generate_skus() {
    $products = get_posts(array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    ));
    $generated = 0;

    foreach ($products as $post) {
        $product = wc_get_product($post->ID);
        if (!$product) {
            continue;
        }
        if (empty($product->get_sku())) {
            $product->set_sku('WEBGSM-' . $product->get_id());
            $product->save();
            $generated++;
        }
    }

    return $generated;
}

// =============================================
// API OBLIO
// =============================================

/**
 * Obține Bearer token Oblio (cache transient).
 */
function oblio_get_access_token() {
    $cached = get_transient('oblio_access_token');
    if (is_string($cached) && $cached !== '') {
        return $cached;
    }

    $email = get_option('oblio_email', '');
    $secret = get_option('oblio_secret', '');
    if (!$email || !$secret) {
        return new WP_Error('oblio_auth', 'Lipsesc email / token Oblio din setări.');
    }

    $response = wp_remote_post('https://www.oblio.eu/api/authorize/token', array(
        'timeout' => 30,
        'headers' => array(
            'Content-Type' => 'application/x-www-form-urlencoded',
        ),
        'body' => array(
            'client_id' => $email,
            'client_secret' => $secret,
        ),
    ));

    if (is_wp_error($response)) {
        return $response;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (empty($body['access_token'])) {
        $msg = isset($body['statusMessage']) ? $body['statusMessage'] : 'Autorizare Oblio eșuată';
        return new WP_Error('oblio_auth', $msg);
    }

    $expires = isset($body['expires_in']) ? max(60, intval($body['expires_in']) - 60) : 3000;
    set_transient('oblio_access_token', $body['access_token'], $expires);

    return $body['access_token'];
}

/**
 * Request generic Oblio API.
 *
 * @param string     $path   Path relativ (ex: docs/invoice)
 * @param array|null $data   Body JSON / form
 * @param string     $method GET|POST|PUT|DELETE
 * @param string     $format json|form
 */
function oblio_request($path, $data = null, $method = 'POST', $format = 'json') {
    $token = oblio_get_access_token();
    if (is_wp_error($token)) {
        return array('error' => $token->get_error_message(), 'status' => 401);
    }

    $url = 'https://www.oblio.eu/api/' . ltrim($path, '/');
    $args = array(
        'method' => strtoupper($method),
        'timeout' => 45,
        'headers' => array(
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ),
    );

    if ($data !== null) {
        if ($format === 'form') {
            $args['headers']['Content-Type'] = 'application/x-www-form-urlencoded';
            $args['body'] = $data;
        } else {
            $args['headers']['Content-Type'] = 'application/json';
            $args['body'] = wp_json_encode($data);
        }
    }

    if (defined('WP_DEBUG') && WP_DEBUG && is_array($data) && isset($data['products'])) {
        error_log('=== Oblio API Request === ' . $path);
        foreach ($data['products'] as $product) {
            if (isset($product['name'])) {
                $code = isset($product['code']) ? $product['code'] : '';
                error_log('Product: ' . $product['name'] . ' | Code/SKU: ' . $code);
            }
        }
    }

    $response = wp_remote_request($url, $args);
    if (is_wp_error($response)) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Oblio API Error: ' . $response->get_error_message());
        }
        return array('error' => $response->get_error_message(), 'status' => 0);
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (!is_array($body)) {
        $body = array();
    }
    $body['http_status'] = $code;

    if ($code === 401) {
        delete_transient('oblio_access_token');
    }

    if ($code >= 400 || (isset($body['status']) && (int) $body['status'] >= 400)) {
        $msg = isset($body['statusMessage']) ? $body['statusMessage'] : 'Eroare Oblio';
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Oblio Error Response: ' . $msg);
        }
        $body['error'] = $msg;
    }

    return $body;
}

/**
 * Helper: citește meta factură Oblio (cu fallback SmartBill test, dacă există).
 */
function webgsm_get_invoice_meta($order_id, $key) {
    $order = wc_get_order($order_id);
    $map = array(
        'number' => array('_oblio_invoice_number', '_smartbill_invoice_number'),
        'series' => array('_oblio_invoice_series', '_smartbill_invoice_series'),
        'date' => array('_oblio_invoice_date', '_smartbill_invoice_date'),
        'link' => array('_oblio_invoice_link'),
    );
    if (!isset($map[$key])) {
        return '';
    }
    foreach ($map[$key] as $meta_key) {
        $val = $order ? $order->get_meta($meta_key) : get_post_meta($order_id, $meta_key, true);
        if ($val !== '' && $val !== null) {
            return $val;
        }
    }
    return '';
}

function webgsm_save_invoice_meta($order, $number, $series, $link = '', $efactura_status = '') {
    $order->update_meta_data('_oblio_invoice_number', $number);
    $order->update_meta_data('_oblio_invoice_series', $series);
    $order->update_meta_data('_oblio_invoice_date', gmdate('Y-m-d'));
    if ($link) {
        $order->update_meta_data('_oblio_invoice_link', $link);
    }
    if ($efactura_status !== '') {
        $order->update_meta_data('_oblio_efactura_status', $efactura_status);
        $order->update_meta_data('_oblio_efactura_updated', gmdate('c'));
    }
    $order->save();
}

/**
 * Generează factura Oblio pentru o comandă.
 *
 * @param int  $order_id
 * @param bool $force    Ignoră toggle-ul API Activ (generare manuală)
 */
function genereaza_factura_oblio($order_id, $force = false) {
    if (!$force && !get_option('oblio_api_active', 0)) {
        $order = wc_get_order($order_id);
        if ($order) {
            $order->add_order_note('Oblio: API dezactivat (mod test) - factura nu a fost generată');
        }
        return false;
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        return false;
    }

    $existing_number = webgsm_get_invoice_meta($order_id, 'number');
    if ($existing_number) {
        return array(
            'number' => $existing_number,
            'series' => webgsm_get_invoice_meta($order_id, 'series'),
            'seriesName' => webgsm_get_invoice_meta($order_id, 'series'),
        );
    }

    $lock_key = 'webgsm_oblio_invoice_lock_' . (int) $order_id;
    $lock_group = 'webgsm_oblio_locks';
    $lock_acquired = false;
    if (function_exists('wp_cache_add')) {
        $lock_acquired = wp_cache_add($lock_key, 1, $lock_group, 300);
    }
    if (!$lock_acquired) {
        if (!get_transient($lock_key)) {
            set_transient($lock_key, 1, 300);
            $lock_acquired = true;
        }
    }
    if (!$lock_acquired) {
        $num = webgsm_get_invoice_meta($order_id, 'number');
        if ($num) {
            return array(
                'number' => $num,
                'series' => webgsm_get_invoice_meta($order_id, 'series'),
                'seriesName' => webgsm_get_invoice_meta($order_id, 'series'),
            );
        }
        return false;
    }

    $cif = get_option('oblio_cif', '');
    $serie = get_option('oblio_serie', 'WEB');
    $tva = (float) get_option('oblio_tva', 21);
    $management = get_option('oblio_management', '');
    $workstation = get_option('oblio_workstation', 'Sediu');
    $use_stock = (int) get_option('oblio_use_stock', 1);

    $fiscal = function_exists('webgsm_get_order_fiscal_data')
        ? webgsm_get_order_fiscal_data($order)
        : array('is_pj' => false, 'company' => '', 'cui' => '', 'reg_com' => '', 'iban' => '', 'bank' => '', 'vat_payer' => false);

    $billing_company = !empty($fiscal['company']) ? $fiscal['company'] : $order->get_billing_company();
    $billing_cif     = !empty($fiscal['cui']) ? $fiscal['cui'] : '';
    $billing_reg_com = !empty($fiscal['reg_com']) ? $fiscal['reg_com'] : '';

    $client = array(
        'name' => $billing_company ? $billing_company : trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()),
        'cif' => $billing_cif ? $billing_cif : '',
        'rc' => $billing_reg_com ? $billing_reg_com : '',
        'address' => trim($order->get_billing_address_1() . ' ' . $order->get_billing_address_2()),
        'city' => $order->get_billing_city(),
        'state' => $order->get_billing_state(),
        'country' => $order->get_billing_country(),
        'email' => $order->get_billing_email(),
        'phone' => $order->get_billing_phone(),
        'vatPayer' => !empty($fiscal['vat_payer']) ? 1 : 0,
        'save' => 0,
    );

    if (!empty($fiscal['iban'])) {
        $client['iban'] = $fiscal['iban'];
    }
    if (!empty($fiscal['bank'])) {
        $client['bank'] = $fiscal['bank'];
    }

    $products = array();
    foreach ($order->get_items() as $item) {
        $product = $item->get_product();
        $sku = '';
        if ($product) {
            $sku = $product->get_sku();
            if (empty($sku)) {
                $sku = 'PROD-' . $product->get_id();
            }
        }

        $item_total = (float) $item->get_total();
        $item_total_tax = (float) $item->get_total_tax();
        $item_quantity = (float) $item->get_quantity();
        if ($item_quantity <= 0) {
            continue;
        }

        $item_tva_percentage = $tva;
        if ($item_total > 0 && $item_total_tax > 0) {
            $item_tva_percentage = round(($item_total_tax / $item_total) * 100, 2);
        }

        $line = array(
            'name' => $item->get_name(),
            'code' => $sku,
            'measuringUnit' => 'buc',
            'currency' => $order->get_currency(),
            'quantity' => $item_quantity,
            'price' => $item_total / $item_quantity,
            'vatIncluded' => 0,
            'vatPercentage' => $item_tva_percentage,
            'productType' => 'Marfa',
            'save' => 0,
        );
        if ($use_stock && $management !== '') {
            $line['management'] = $management;
        }

        $products[] = $line;

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Oblio Product: ' . $item->get_name() . ' | SKU: ' . $sku . ' | TVA: ' . $item_tva_percentage . '%');
        }
    }

    $shipping_total = (float) $order->get_shipping_total();
    $shipping_tax = (float) $order->get_shipping_tax();
    if ($shipping_total > 0) {
        $ship_tva = $tva;
        if ($shipping_total > 0 && $shipping_tax > 0) {
            $ship_tva = round(($shipping_tax / $shipping_total) * 100, 2);
        }
        $products[] = array(
            'name' => 'Transport',
            'code' => 'TRANSPORT',
            'measuringUnit' => 'buc',
            'currency' => $order->get_currency(),
            'quantity' => 1,
            'price' => $shipping_total,
            'vatIncluded' => 0,
            'vatPercentage' => $ship_tva,
            'productType' => 'Serviciu',
            'save' => 0,
        );
    }

    if (empty($products)) {
        $order->add_order_note('Eroare Oblio: comanda nu are linii de facturat');
        return false;
    }

    $invoice_data = array(
        'cif' => $cif,
        'client' => $client,
        'seriesName' => $serie,
        'issueDate' => gmdate('Y-m-d'),
        'dueDate' => gmdate('Y-m-d', strtotime('+15 days')),
        'currency' => $order->get_currency(),
        'language' => 'RO',
        'precision' => 2,
        'products' => $products,
        'mentions' => 'Comandă online #' . $order->get_order_number(),
        'orderNumber' => (string) $order->get_order_number(),
        'idempotencyKey' => 'webgsm-order-' . $order_id,
        'useStock' => $use_stock ? 1 : 0,
        'workStation' => $workstation ? $workstation : 'Sediu',
    );

    $response = oblio_request('docs/invoice', $invoice_data, 'POST', 'json');

    if (!empty($response['error'])) {
        $order->add_order_note('Eroare Oblio: ' . $response['error']);
        return false;
    }

    $data = isset($response['data']) && is_array($response['data']) ? $response['data'] : $response;
    $number = isset($data['number']) ? $data['number'] : '';
    $series = isset($data['seriesName']) ? $data['seriesName'] : (isset($data['series']) ? $data['series'] : $serie);
    $link = isset($data['link']) ? $data['link'] : '';

    if ($number === '' || $number === null) {
        $order->add_order_note('Eroare Oblio: răspuns fără număr factură');
        return false;
    }

    webgsm_save_invoice_meta($order, $number, $series, $link);
    $order->add_order_note('Factură Oblio generată: ' . $series . $number);

    // e-Factura: site-ul nu apelează SPV direct; Oblio o trimite dacă e activă în contul lor.
    if (get_option('oblio_efactura_via_oblio', 1)) {
        $ef_status = '';
        if (isset($data['eFacturaStatus'])) {
            $ef_status = (string) $data['eFacturaStatus'];
        } elseif (isset($data['efacturaStatus'])) {
            $ef_status = (string) $data['efacturaStatus'];
        } elseif (isset($data['eInvoiceStatus'])) {
            $ef_status = (string) $data['eInvoiceStatus'];
        } else {
            $ef_status = 'delegated_to_oblio';
        }
        $order->update_meta_data('_oblio_efactura_status', sanitize_text_field($ef_status));
        $order->update_meta_data('_oblio_efactura_updated', gmdate('c'));
        $order->save();
        $order->add_order_note('e-Factura (via Oblio): status=' . $ef_status);
    }

    return array(
        'number' => $number,
        'series' => $series,
        'seriesName' => $series,
        'link' => $link,
    );
}

/** Alias vechi – compatibilitate dacă ceva apelează încă numele SmartBill. */
function genereaza_factura_smartbill($order_id) {
    return genereaza_factura_oblio($order_id);
}

/**
 * Descarcă PDF factură Oblio (prin link document).
 */
function get_factura_pdf_oblio($order_id) {
    $series = webgsm_get_invoice_meta($order_id, 'series');
    $number = webgsm_get_invoice_meta($order_id, 'number');
    if (!$series || $number === '' || $number === null) {
        return false;
    }

    $link = webgsm_get_invoice_meta($order_id, 'link');
    if (!$link) {
        $cif = get_option('oblio_cif', '');
        $response = oblio_request(
            'docs/invoice?cif=' . rawurlencode($cif) . '&seriesName=' . rawurlencode($series) . '&number=' . rawurlencode($number),
            null,
            'GET'
        );
        if (!empty($response['data']['link'])) {
            $link = $response['data']['link'];
            $order = wc_get_order($order_id);
            if ($order) {
                $order->update_meta_data('_oblio_invoice_link', $link);
                $order->save();
            }
        }
    }

    if (!$link) {
        return false;
    }

    $response = wp_remote_get($link, array(
        'timeout' => 45,
        'redirection' => 5,
    ));
    if (is_wp_error($response)) {
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    $ctype = wp_remote_retrieve_header($response, 'content-type');
    if ($body && (strpos((string) $ctype, 'pdf') !== false || substr($body, 0, 4) === '%PDF')) {
        return $body;
    }

    // Link-ul Oblio poate fi HTML viewer – returnează URL pentru redirect
    return array('redirect' => $link);
}

function get_factura_pdf_smartbill($order_id) {
    return get_factura_pdf_oblio($order_id);
}

// =============================================
// GENERARE AUTOMATĂ FACTURI (asincron)
// =============================================

/**
 * Programează generarea facturii Oblio fără a bloca schimbarea de status.
 */
function webgsm_schedule_oblio_invoice($order_id) {
    $order_id = (int) $order_id;
    if ($order_id <= 0) {
        return;
    }
    $args = array($order_id);
    if (function_exists('as_enqueue_async_action')) {
        as_enqueue_async_action('webgsm_oblio_generate_invoice', $args, 'webgsm-oblio');
    } elseif (function_exists('as_schedule_single_action')) {
        as_schedule_single_action(time() + 5, 'webgsm_oblio_generate_invoice', $args, 'webgsm-oblio');
    } else {
        if (!wp_next_scheduled('webgsm_oblio_generate_invoice', $args)) {
            wp_schedule_single_event(time() + 5, 'webgsm_oblio_generate_invoice', $args);
        }
    }
}

add_action('webgsm_oblio_generate_invoice', function($order_id) {
    if (function_exists('genereaza_factura_oblio')) {
        genereaza_factura_oblio((int) $order_id);
    }
});

add_action('woocommerce_order_status_processing', function($order_id) {
    if (!get_option('oblio_auto_generate', 1)) {
        return;
    }
    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }
    $metode_online = array('stripe', 'paypal', 'netopia', 'mobilpay', 'euplatesc', 'twispay', 'payu', 'revolut', 'revolut_pay');
    if (in_array($order->get_payment_method(), $metode_online, true)) {
        webgsm_schedule_oblio_invoice($order_id);
    }
});

add_action('woocommerce_order_status_completed', function($order_id) {
    if (!get_option('oblio_auto_generate', 1)) {
        return;
    }
    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }
    $metode_offline = array('cod', 'bacs', 'cheque', 'sameday_easybox', 'easybox');
    if (in_array($order->get_payment_method(), $metode_offline, true)) {
        webgsm_schedule_oblio_invoice($order_id);
    }
});

// =============================================
// DESCĂRCARE PDF
// =============================================

add_action('wp_ajax_download_factura_pdf', function() {
    if (!is_user_logged_in()) {
        wp_die('Neautorizat');
    }

    $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
    $order = wc_get_order($order_id);
    if (!$order || ($order->get_customer_id() !== get_current_user_id() && !current_user_can('manage_woocommerce'))) {
        wp_die('Acces interzis');
    }

    $pdf = get_factura_pdf_oblio($order_id);
    if (!$pdf) {
        wp_die('Factura nu a putut fi descărcată');
    }

    if (is_array($pdf) && !empty($pdf['redirect'])) {
        wp_redirect($pdf['redirect']);
        exit;
    }

    $series = webgsm_get_invoice_meta($order_id, 'series');
    $number = webgsm_get_invoice_meta($order_id, 'number');

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="Factura_' . $series . $number . '.pdf"');
    header('Content-Length: ' . strlen($pdf));
    echo $pdf;
    exit;
});

// =============================================
// AFIȘARE ÎN CONT CLIENT
// =============================================

add_action('woocommerce_account_orders_actions', function($actions, $order) {
    $invoice_number = webgsm_get_invoice_meta($order->get_id(), 'number');
    if ($invoice_number) {
        $invoice_series = webgsm_get_invoice_meta($order->get_id(), 'series');
        $actions['factura'] = array(
            'url' => admin_url('admin-ajax.php?action=download_factura_pdf&order_id=' . $order->get_id()),
            'name' => 'Factură ' . $invoice_series . $invoice_number,
        );
    }
    return $actions;
}, 10, 2);

add_action('woocommerce_order_details_after_order_table', function($order) {
    $invoice_number = webgsm_get_invoice_meta($order->get_id(), 'number');
    if (!$invoice_number) {
        return;
    }
    $series = webgsm_get_invoice_meta($order->get_id(), 'series');
    echo '<p><a href="' . esc_url(admin_url('admin-ajax.php?action=download_factura_pdf&order_id=' . $order->get_id())) . '" class="button button-download-invoice" target="_blank">Factura ' . esc_html($series . $invoice_number) . '</a></p>';
});

// =============================================
// ADMIN - COLOANĂ FACTURĂ
// =============================================

function oblio_add_factura_column($columns) {
    $new_columns = array();
    $added = false;
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        if ($key === 'order_total' || $key === 'total') {
            $new_columns['factura'] = 'Factură';
            $added = true;
        }
    }
    if (!$added) {
        $new_columns['factura'] = 'Factură';
    }
    return $new_columns;
}

add_filter('manage_edit-shop_order_columns', 'oblio_add_factura_column');
add_filter('manage_woocommerce_page_wc-orders_columns', 'oblio_add_factura_column');

function oblio_render_factura_column_legacy($column) {
    global $post;
    if ($column !== 'factura' || !$post) {
        return;
    }
    $order = wc_get_order($post->ID);
    if (!$order) {
        return;
    }
    oblio_render_factura_cell($order->get_id(), $order);
}

function oblio_render_factura_column_hpos($column, $order) {
    if ($column !== 'factura' || !$order) {
        return;
    }
    oblio_render_factura_cell($order->get_id(), $order);
}

function oblio_render_factura_cell($order_id, $order = null) {
    if (!$order) {
        $order = wc_get_order($order_id);
    }
    if (!$order) {
        return;
    }
    $invoice_number = webgsm_get_invoice_meta($order_id, 'number');
    if ($invoice_number) {
        $series = webgsm_get_invoice_meta($order_id, 'series');
        echo '<a href="' . esc_url(admin_url('admin-ajax.php?action=download_factura_pdf&order_id=' . $order_id)) . '" target="_blank">' . esc_html($series . $invoice_number) . '</a>';
    } else {
        $api_active = get_option('oblio_api_active', 0);
        if ($api_active) {
            echo '<button type="button" class="button genereaza-factura" data-order="' . esc_attr($order_id) . '" title="Generează factură Oblio">Generează</button>';
        } else {
            echo '<span style="color:#999;">API oprit</span>';
        }
    }
}

add_action('manage_shop_order_posts_custom_column', 'oblio_render_factura_column_legacy');
add_action('manage_woocommerce_page_wc-orders_custom_column', 'oblio_render_factura_column_hpos', 10, 2);

add_action('woocommerce_admin_order_data_after_billing_address', function($order) {
    if (!is_a($order, 'WC_Order')) {
        return;
    }
    $ef = $order->get_meta('_oblio_efactura_status');
    if ($ef === '' || $ef === null || $ef === false) {
        return;
    }
    $updated = $order->get_meta('_oblio_efactura_updated');
    echo '<div style="margin-top:12px;padding:10px;border-radius:6px;background:#f3e8ff;border:1px solid #c4b5fd;">';
    echo '<strong>e-Factura (Oblio / SPV)</strong><br>';
    echo 'Status: <code>' . esc_html($ef) . '</code>';
    if ($updated) {
        echo '<br><small>Actualizat: ' . esc_html($updated) . '</small>';
    }
    echo '<p style="margin:8px 0 0;font-size:12px;color:#6b7280;">Transmiterea către ANAF SPV este gestionată de Oblio, nu de site.</p>';
    echo '</div>';
}, 25);

add_action('wp_ajax_genereaza_factura_manual', function() {
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error('Neautorizat');
    }
    check_ajax_referer('webgsm_oblio_manual', 'nonce');

    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $order = wc_get_order($order_id);
    if (!$order) {
        wp_send_json_error('Comanda nu există');
    }

    $result = genereaza_factura_oblio($order_id, true);

    if ($result && isset($result['number'])) {
        wp_send_json_success(array(
            'series' => $result['series'],
            'number' => $result['number'],
        ));
    }

    $error_msg = 'Eroare la generarea facturii. Vezi notele comenzii.';
    if (function_exists('wc_get_order_notes')) {
        $notes = wc_get_order_notes(array('order_id' => $order_id, 'limit' => 5, 'orderby' => 'date_created', 'order' => 'DESC'));
        foreach ($notes as $note) {
            if (!empty($note->content) && strpos($note->content, 'Eroare Oblio') !== false) {
                $error_msg = $note->content;
                break;
            }
        }
    }

    wp_send_json_error($error_msg);
});

add_action('admin_footer', function() {
    global $pagenow, $post;
    $is_order_list_legacy = ($pagenow === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'shop_order');
    $is_order_list_hpos = ($pagenow === 'admin.php' && isset($_GET['page']) && $_GET['page'] === 'wc-orders');
    $is_order_edit = ($pagenow === 'post.php' && $post && get_post_type($post) === 'shop_order');
    if (!$is_order_list_legacy && !$is_order_list_hpos && !$is_order_edit) {
        return;
    }
    $nonce = wp_create_nonce('webgsm_oblio_manual');
    ?>
    <script>
    jQuery(document).ready(function($) {
        $(document).on('click', '.genereaza-factura', function(e) {
            e.preventDefault();
            var btn = $(this);
            var orderId = btn.data('order');
            btn.prop('disabled', true).text('...');
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: { action: 'genereaza_factura_manual', order_id: orderId, nonce: '<?php echo esc_js($nonce); ?>' },
                success: function(response) {
                    if (response.success) {
                        if (window.location.href.indexOf('post.php') !== -1 || window.location.href.indexOf('wc-orders') !== -1 && window.location.href.indexOf('action=edit') !== -1) {
                            window.location.reload();
                        } else {
                            var link = '<a href="' + ajaxurl + '?action=download_factura_pdf&order_id=' + orderId + '" target="_blank">' + response.data.series + response.data.number + '</a>';
                            btn.replaceWith(link);
                        }
                    } else {
                        btn.prop('disabled', false).text('Generează');
                        alert('Eroare: ' + (response.data || 'Nu s-a putut genera factura'));
                    }
                },
                error: function() {
                    btn.prop('disabled', false).text('Generează');
                    alert('Eroare la comunicarea cu serverul.');
                }
            });
        });
    });
    </script>
    <?php
});

add_action('admin_head', function() {
    global $pagenow, $post;
    $is_orders = ($pagenow === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'shop_order')
        || ($pagenow === 'admin.php' && isset($_GET['page']) && $_GET['page'] === 'wc-orders')
        || ($pagenow === 'post.php' && $post && get_post_type($post) === 'shop_order');
    if (!$is_orders) {
        return;
    }
    echo '<style>
    .genereaza-factura {
        font-size: 11px !important;
        padding: 5px 6px !important;
        min-height: 0 !important;
        height: auto !important;
        line-height: 1.2 !important;
        background-color: #93c5fd !important;
        border-color: #93c5fd !important;
        color: #0f172a !important;
    }
    .genereaza-factura:hover {
        background-color: #7dd3fc !important;
        border-color: #7dd3fc !important;
        color: #020617 !important;
    }
    </style>';
});

add_action('add_meta_boxes', function() {
    $screen = 'shop_order';
    if (class_exists('\Automattic\WooCommerce\Utilities\OrderUtil') && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() && function_exists('wc_get_page_screen_id')) {
        $screen = wc_get_page_screen_id('shop-order');
    }
    add_meta_box(
        'oblio_factura',
        'Factură Oblio',
        'render_oblio_order_metabox',
        $screen,
        'side',
        'high'
    );
});

function render_oblio_order_metabox($order_or_post) {
    $order = is_a($order_or_post, 'WP_Post') ? wc_get_order($order_or_post->ID) : $order_or_post;
    if (!$order) {
        return;
    }
    $order_id = $order->get_id();
    $invoice_number = webgsm_get_invoice_meta($order_id, 'number');
    $invoice_series = webgsm_get_invoice_meta($order_id, 'series');
    $invoice_date = webgsm_get_invoice_meta($order_id, 'date');
    $api_active = get_option('oblio_api_active', 0);

    if ($invoice_number) {
        echo '<p><strong>Factură:</strong> ' . esc_html($invoice_series . $invoice_number) . '</p>';
        if ($invoice_date) {
            echo '<p><strong>Data:</strong> ' . esc_html(date_i18n('d.m.Y', strtotime($invoice_date))) . '</p>';
        }
        echo '<p><a href="' . esc_url(admin_url('admin-ajax.php?action=download_factura_pdf&order_id=' . $order_id)) . '" class="button" target="_blank">Descarcă PDF</a></p>';
    } else {
        echo '<p>Factura nu a fost generată.</p>';
        if ($api_active) {
            echo '<button type="button" class="button button-primary genereaza-factura" data-order="' . esc_attr($order_id) . '">Generează</button>';
        } else {
            echo '<p style="color:orange;">API Oblio dezactivat</p>';
            echo '<p><a href="' . esc_url(admin_url('admin.php?page=oblio-settings')) . '">Activează API</a></p>';
            echo '<p><button type="button" class="button genereaza-factura" data-order="' . esc_attr($order_id) . '">Generează oricum</button></p>';
        }
    }
}
