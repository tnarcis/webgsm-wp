<?php
/**
 * MODUL FACTURI - SmartBill
 * Generează facturi automate și permite descărcare PDF din cont client
 * Cu pagină de setări în admin
 */

// =============================================
// PAGINĂ SETĂRI SMARTBILL ÎN ADMIN
// =============================================

add_action('admin_menu', function() {
    add_submenu_page(
        'woocommerce',
        'Setări SmartBill',
        'Setări SmartBill',
        'manage_woocommerce',
        'smartbill-settings',
        'render_smartbill_settings_page'
    );
});

function render_smartbill_settings_page() {
    // Salvare setări
    if(isset($_POST['save_smartbill_settings']) && wp_verify_nonce($_POST['smartbill_nonce'], 'save_smartbill')) {
        update_option('smartbill_api_active', isset($_POST['smartbill_api_active']) ? 1 : 0);
        update_option('smartbill_auto_generate', isset($_POST['smartbill_auto_generate']) ? 1 : 0);
        update_option('smartbill_username', sanitize_email($_POST['smartbill_username']));
        update_option('smartbill_token', sanitize_text_field($_POST['smartbill_token']));
        update_option('smartbill_cif', sanitize_text_field($_POST['smartbill_cif']));
        update_option('smartbill_serie', sanitize_text_field($_POST['smartbill_serie']));
        update_option('smartbill_tva', floatval($_POST['smartbill_tva']));
        echo '<div class="notice notice-success"><p>Setarile au fost salvate!</p></div>';
    }
    
    $api_active = get_option('smartbill_api_active', 0);
    $auto_generate = get_option('smartbill_auto_generate', 1);
    $username = get_option('smartbill_username', 'info@webgsm.ro');
    $token = get_option('smartbill_token', '003|5088be0e0850155eaa7713f3d324a63a');
    $cif = get_option('smartbill_cif', 'RO31902941');
    $serie = get_option('smartbill_serie', 'WEB');
    $tva = get_option('smartbill_tva', 19);
    ?>
    <div class="wrap">
        <h1>⚙️ Setări SmartBill</h1>
        
        <form method="post">
            <?php wp_nonce_field('save_smartbill', 'smartbill_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th>Status API</th>
                    <td>
                        <label style="display:inline-block; padding:10px 20px; background:<?php echo $api_active ? '#d4edda' : '#fff3cd'; ?>; border-radius:5px;">
                            <input type="checkbox" name="smartbill_api_active" value="1" <?php checked($api_active, 1); ?>>
                            <strong style="font-size:16px;">API Activ</strong>
                        </label>
                        <p class="description" style="margin-top:10px;">
                            <?php if($api_active): ?>
                                <span style="color:green; font-size:14px;">✓ API-ul este <strong>ACTIV</strong></span>
                            <?php else: ?>
                                <span style="color:orange; font-size:14px;">⏸ API-ul este <strong>OPRIT</strong> (mod test) – poți genera facturi manual din comenzi</span>
                            <?php endif; ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th>Generează factură automat</th>
                    <td>
                        <label style="display:inline-block; padding:8px 16px; background:#f0f6fc; border-radius:5px;">
                            <input type="checkbox" name="smartbill_auto_generate" value="1" <?php checked($auto_generate, 1); ?>>
                            <strong>La plată online / la livrare (ramburs)</strong>
                        </label>
                        <p class="description" style="margin-top:8px;">
                            <?php if($auto_generate): ?>
                                <span style="color:green;">✓ Factura se generează automat la Processing (card) sau Completed (ramburs).</span>
                            <?php else: ?>
                                <span style="color:#666;">Factura nu se generează automat. Folosește butonul <strong>„Generează factură”</strong> în fiecare comandă (lista Comenzi sau pagina comenzii).</span>
                            <?php endif; ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th>Email SmartBill</th>
                    <td><input type="email" name="smartbill_username" value="<?php echo esc_attr($username); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th>Token API</th>
                    <td><input type="text" name="smartbill_token" value="<?php echo esc_attr($token); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th>CIF Firmă</th>
                    <td><input type="text" name="smartbill_cif" value="<?php echo esc_attr($cif); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th>Serie Factură</th>
                    <td><input type="text" name="smartbill_serie" value="<?php echo esc_attr($serie); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th>Cotă TVA Fallback (%)</th>
                    <td>
                        <input type="number" name="smartbill_tva" value="<?php echo esc_attr($tva); ?>" class="small-text" step="1" min="0" max="100">
                        <p class="description">
                            TVA implicit: 19% (România)<br>
                            <strong>Notă:</strong> TVA-ul se ia automat din <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=tax'); ?>">WooCommerce → Setări → Taxe</a>. 
                            Această valoare e folosită doar dacă WooCommerce nu are taxe configurate.
                        </p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" name="save_smartbill_settings" class="button button-primary">Salveaza setarile</button>
            </p>
        </form>
        
        <hr>
        <h3>📋 Informații</h3>
        <ul>
            <li><strong>Generează automat (bifat):</strong> La plată online → factură la Processing; la ramburs → factură la Completed.</li>
            <li><strong>Generează automat (nebifat):</strong> Factura nu se generează singură; folosești butonul <strong>„Generează factură”</strong> în Comenzi (listă sau pagina comenzii).</li>
            <li><strong>Factura PF:</strong> Pe numele clientului; <strong>Factura PJ:</strong> Pe firma (din Date Facturare).</li>
            <li><strong>SKU:</strong> Produsele fără SKU primesc cod WEBGSM-{ID}.</li>
        </ul>
        
        <div style="background:#fff3cd; padding:15px; border-left:4px solid #ffc107; margin:20px 0;">
            <h4 style="margin-top:0;">⚙️ Setări SmartBill necesare:</h4>
            
            <p><strong>1. Pentru afișare SKU în facturi:</strong></p>
            <ol style="margin:10px 0; padding-left:20px;">
                <li>Loghează-te în <strong>SmartBill.ro</strong></li>
                <li>Mergi la <strong>Setări → Setări Generale → Setări Facturi</strong></li>
                <li>Secțiunea <strong>"Produse/Servicii"</strong></li>
                <li>Bifează: <strong>☑ Afișează codul produsului în facturi</strong></li>
                <li>Salvează setările</li>
            </ol>
            
            <p><strong>2. Pentru cotă TVA corectă:</strong></p>
            <ol style="margin:10px 0; padding-left:20px;">
                <li>Mergi la <strong><a href="<?php echo admin_url('admin.php?page=wc-settings&tab=tax'); ?>">WooCommerce → Setări → Taxe</a></strong></li>
                <li>Activează: <strong>☑ Activează taxele</strong></li>
                <li>Click pe <strong>"Taxe standard"</strong></li>
                <li>Adaugă rând: Țară <strong>RO</strong>, Cotă <strong>19.0000%</strong></li>
                <li>Salvează modificările</li>
            </ol>
            
            <p style="margin:5px 0 0 0; font-size:13px; color:#856404;">
                💡 <strong>Notă:</strong> TVA-ul se calculează automat din prețurile WooCommerce. Cota "Fallback" de mai sus e folosită doar dacă WooCommerce nu are taxe configurate.
            </p>
        </div>
        
        <hr>
        <h3>🔧 Instrumente</h3>
        <p>
            <a href="<?php echo admin_url('admin.php?page=smartbill-settings&action=generate_skus'); ?>" 
               class="button button-secondary"
               onclick="return confirm('Generează SKU pentru toate produsele fără SKU?');">
                🏷️ Generează SKU pentru toate produsele
            </a>
        </p>
        
        <?php
        // Procesare generare SKU-uri
        if (isset($_GET['action']) && $_GET['action'] === 'generate_skus') {
            $generated = webgsm_bulk_generate_skus();
            echo '<div class="notice notice-success"><p>✓ Au fost generate ' . $generated . ' SKU-uri!</p></div>';
        }
        ?>
    </div>
    <?php
}

// Funcție bulk pentru generare SKU-uri
function webgsm_bulk_generate_skus() {
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    );
    
    $products = get_posts($args);
    $generated = 0;
    
    foreach ($products as $post) {
        $product = wc_get_product($post->ID);
        if (!$product) continue;
        
        $current_sku = $product->get_sku();
        
        if (empty($current_sku)) {
            $auto_sku = 'WEBGSM-' . $product->get_id();
            $product->set_sku($auto_sku);
            $product->save();
            $generated++;
        }
    }
    
    return $generated;
}

// =============================================
// AUTO-GENERARE SKU pentru produse fără SKU
// =============================================
add_action('save_post_product', 'webgsm_auto_generate_sku', 10, 1);
function webgsm_auto_generate_sku($product_id) {
    $product = wc_get_product($product_id);
    if (!$product) return;
    
    // Verifică dacă produsul are SKU
    $current_sku = $product->get_sku();
    
    if (empty($current_sku)) {
        // Generează SKU automat: WEBGSM-{ID}
        $auto_sku = 'WEBGSM-' . $product_id;
        $product->set_sku($auto_sku);
        $product->save();
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Auto-generated SKU for product #' . $product_id . ': ' . $auto_sku);
        }
    }
}

// =============================================
// FUNCȚII SMARTBILL
// =============================================

// Funcție pentru a face request la SmartBill API
function smartbill_request($endpoint, $data = null, $method = 'POST') {
    $username = get_option('smartbill_username', 'info@webgsm.ro');
    $token = get_option('smartbill_token', '003|5088be0e0850155eaa7713f3d324a63a');
    
    $url = 'https://ws.smartbill.ro/SBORO/api/' . $endpoint;
    
    $args = array(
        'method' => $method,
        'timeout' => 30,
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode($username . ':' . $token),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        )
    );
    
    if($data && $method === 'POST') {
        $args['body'] = json_encode($data);
        
        // Log SKU-uri trimise (pentru debugging)
        if (defined('WP_DEBUG') && WP_DEBUG && isset($data['products'])) {
            error_log('=== SmartBill API Request ===');
            error_log('Endpoint: ' . $endpoint);
            foreach ($data['products'] as $product) {
                error_log('Product: ' . $product['name'] . ' | Code/SKU: ' . $product['code']);
            }
        }
    }
    
    $response = wp_remote_request($url, $args);
    
    if(is_wp_error($response)) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('SmartBill API Error: ' . $response->get_error_message());
        }
        return array('error' => $response->get_error_message());
    }
    
    $body = wp_remote_retrieve_body($response);
    $result = json_decode($body, true);
    
    // Log răspuns (pentru debugging)
    if (defined('WP_DEBUG') && WP_DEBUG && isset($result['errorText'])) {
        error_log('SmartBill Error Response: ' . $result['errorText']);
    }
    
    return $result;
}

// Funcție pentru a genera factura în SmartBill
function genereaza_factura_smartbill($order_id) {
    // Verifică dacă API-ul e activ
    if(!get_option('smartbill_api_active', 0)) {
        $order = wc_get_order($order_id);
        if($order) {
            $order->add_order_note('SmartBill: API dezactivat (mod test) - factura nu a fost generată');
        }
        return false;
    }
    
    $order = wc_get_order($order_id);
    if(!$order) return false;
    
    // Verifică dacă factura există deja
    $factura_existenta = get_post_meta($order_id, '_smartbill_invoice_number', true);
    if($factura_existenta) {
        return array('number' => $factura_existenta);
    }
    
    $cif = get_option('smartbill_cif', 'RO31902941');
    $serie = get_option('smartbill_serie', 'WEB');
    $tva = get_option('smartbill_tva', 19);
    
    // Verifică dacă e factură PJ
    $tip_facturare = get_post_meta($order_id, '_tip_facturare', true);
    $billing_company = '';
    $billing_cif = '';
    $billing_reg_com = '';
    
    if($tip_facturare === 'pj') {
        $billing_company = get_post_meta($order_id, '_billing_company_name', true);
        $billing_cif = get_post_meta($order_id, '_billing_cif', true);
        $billing_reg_com = get_post_meta($order_id, '_billing_reg_com', true);
    }
    
    if(empty($billing_company)) {
        $billing_company = $order->get_billing_company();
    }
    
    $client = array(
        'name' => $billing_company ? $billing_company : $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
        'vatCode' => $billing_cif ? $billing_cif : '',
        'regCom' => $billing_reg_com ? $billing_reg_com : '',
        'address' => $order->get_billing_address_1() . ' ' . $order->get_billing_address_2(),
        'city' => $order->get_billing_city(),
        'county' => $order->get_billing_state(),
        'country' => $order->get_billing_country(),
        'email' => $order->get_billing_email(),
        'phone' => $order->get_billing_phone(),
        'isTaxPayer' => !empty($billing_cif)
    );
    
    // Pregătește produsele cu SKU
    $products = array();
    foreach($order->get_items() as $item) {
        $product = $item->get_product();
        
        // Obține SKU - cu fallback la product ID dacă nu există
        $sku = '';
        if ($product) {
            $sku = $product->get_sku();
            // Dacă nu are SKU, folosește Product ID
            if (empty($sku)) {
                $sku = 'PROD-' . $product->get_id();
            }
        }
        
        // Calculează TVA din prețurile WooCommerce (mai precis)
        $item_total = $item->get_total(); // Preț fără taxe
        $item_total_tax = $item->get_total_tax(); // Taxe
        $item_quantity = $item->get_quantity();
        
        // Calculează cota TVA efectivă
        $item_tva_percentage = $tva; // Default din setări
        if ($item_total > 0 && $item_total_tax > 0) {
            // Calculează TVA efectiv: (tax / total_fara_tax) * 100
            $item_tva_percentage = round(($item_total_tax / $item_total) * 100, 2);
        }
        
        $products[] = array(
            'name' => $item->get_name(),
            'code' => $sku, // SKU sau PROD-{ID}
            'measuringUnitName' => 'buc',
            'currency' => $order->get_currency(),
            'quantity' => $item_quantity,
            'price' => $item_total / $item_quantity,
            'isTaxIncluded' => false, // Preț FĂRĂ TVA
            'taxPercentage' => $item_tva_percentage,
            'saveToDb' => false
        );
        
        // Log pentru debugging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('SmartBill Product: ' . $item->get_name() . ' | SKU: ' . $sku . ' | TVA: ' . $item_tva_percentage . '%');
        }
    }
    
    // Adaugă transport dacă există
    $shipping_total = $order->get_shipping_total();
    if($shipping_total > 0) {
        $products[] = array(
            'name' => 'Transport',
            'code' => 'TRANSPORT',
            'measuringUnitName' => 'buc',
            'currency' => $order->get_currency(),
            'quantity' => 1,
            'price' => $shipping_total,
            'isTaxIncluded' => true,
            'taxPercentage' => $tva,
            'saveToDb' => false
        );
    }
    
    // Datele facturii
    $invoice_data = array(
        'companyVatCode' => $cif,
        'seriesName' => $serie,
        'client' => $client,
        'products' => $products,
        'issueDate' => date('Y-m-d'),
        'dueDate' => date('Y-m-d', strtotime('+15 days')),
        'currency' => $order->get_currency(),
        'language' => 'RO',
        'observations' => 'Comandă online #' . $order->get_order_number()
    );
    
    // Trimite la SmartBill
    $response = smartbill_request('invoice', $invoice_data);
    
    if(isset($response['errorText']) && !empty($response['errorText'])) {
        $order->add_order_note('Eroare SmartBill: ' . $response['errorText']);
        return false;
    }
    
    if(isset($response['number'])) {
        // Salvează numărul facturii
        update_post_meta($order_id, '_smartbill_invoice_number', $response['number']);
        update_post_meta($order_id, '_smartbill_invoice_series', $response['series']);
        update_post_meta($order_id, '_smartbill_invoice_date', date('Y-m-d'));
        
        // Adaugă notă la comandă
        $order->add_order_note('Factură SmartBill generată: ' . $response['series'] . $response['number']);
        
        return $response;
    }
    
    return false;
}

// Funcție pentru a descărca PDF-ul facturii
function get_factura_pdf_smartbill($order_id) {
    $series = get_post_meta($order_id, '_smartbill_invoice_series', true);
    $number = get_post_meta($order_id, '_smartbill_invoice_number', true);
    
    if(!$series || !$number) return false;
    
    $username = get_option('smartbill_username', 'info@webgsm.ro');
    $token = get_option('smartbill_token', '003|5088be0e0850155eaa7713f3d324a63a');
    $cif = get_option('smartbill_cif', 'RO31902941');
    
    $url = 'https://ws.smartbill.ro/SBORO/api/invoice/pdf?cif=' . $cif . '&seriesname=' . $series . '&number=' . $number;
    
    $args = array(
        'method' => 'GET',
        'timeout' => 30,
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode($username . ':' . $token),
            'Accept' => 'application/octet-stream'
        )
    );
    
    $response = wp_remote_get($url, $args);
    
    if(is_wp_error($response)) {
        return false;
    }
    
    return wp_remote_retrieve_body($response);
}

// =============================================
// GENERARE AUTOMATĂ FACTURI
// =============================================

// Plată online (card) → la procesare (doar dacă „Generează factură automat” e bifat)
add_action('woocommerce_order_status_processing', function($order_id) {
    if (!get_option('smartbill_auto_generate', 1)) {
        return;
    }
    $order = wc_get_order($order_id);
    $payment_method = $order->get_payment_method();
    
    $metode_online = array('stripe', 'paypal', 'netopia', 'mobilpay', 'euplatesc', 'twispay', 'payu', 'revolut', 'revolut_pay');
    
    if(in_array($payment_method, $metode_online)) {
        genereaza_factura_smartbill($order_id);
    }
});

// Plată ramburs/offline → la finalizare (doar dacă „Generează factură automat” e bifat)
add_action('woocommerce_order_status_completed', function($order_id) {
    if (!get_option('smartbill_auto_generate', 1)) {
        return;
    }
    $order = wc_get_order($order_id);
    $payment_method = $order->get_payment_method();
    
    $metode_offline = array('cod', 'bacs', 'cheque', 'sameday_easybox', 'easybox');
    
    if(in_array($payment_method, $metode_offline)) {
        genereaza_factura_smartbill($order_id);
    }
});

// =============================================
// DESCĂRCARE PDF
// =============================================

add_action('wp_ajax_download_factura_pdf', function() {
    if(!is_user_logged_in()) {
        wp_die('Neautorizat');
    }
    
    $order_id = intval($_GET['order_id']);
    $order = wc_get_order($order_id);
    
    // Verifică dacă comanda aparține userului curent sau e admin
    if(!$order || ($order->get_customer_id() !== get_current_user_id() && !current_user_can('manage_woocommerce'))) {
        wp_die('Acces interzis');
    }
    
    $pdf = get_factura_pdf_smartbill($order_id);
    
    if(!$pdf) {
        wp_die('Factura nu a putut fi descărcată');
    }
    
    $series = get_post_meta($order_id, '_smartbill_invoice_series', true);
    $number = get_post_meta($order_id, '_smartbill_invoice_number', true);
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="Factura_' . $series . $number . '.pdf"');
    header('Content-Length: ' . strlen($pdf));
    
    echo $pdf;
    exit;
});

// =============================================
// AFIȘARE ÎN CONT CLIENT - COMENZI
// =============================================

// Adaugă buton descărcare factură în lista de comenzi
add_action('woocommerce_my_account_my_orders_actions', function($actions, $order) {
    $invoice_number = get_post_meta($order->get_id(), '_smartbill_invoice_number', true);
    
    if($invoice_number) {
        $invoice_series = get_post_meta($order->get_id(), '_smartbill_invoice_series', true);
        $actions['factura'] = array(
            'url' => admin_url('admin-ajax.php?action=download_factura_pdf&order_id=' . $order->get_id()),
            'name' => '📄 Factură ' . $invoice_series . $invoice_number
        );
    }
    
    return $actions;
}, 10, 2);

// Adaugă buton și în pagina de detalii comandă
add_action('woocommerce_order_details_after_order_table', function($order) {
    $invoice_number = get_post_meta($order->get_id(), '_smartbill_invoice_number', true);
    
    if($invoice_number) {
        $series = get_post_meta($order->get_id(), '_smartbill_invoice_series', true);
        echo '<p><a href="' . admin_url('admin-ajax.php?action=download_factura_pdf&order_id=' . $order->get_id()) . '" class="button button-download-invoice" target="_blank"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:inline-block;vertical-align:middle;margin-right:6px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>Factura ' . $series . $invoice_number . '</a></p>';
    }
});

// =============================================
// ADMIN - COLOANĂ FACTURĂ ÎN COMENZI (legacy + HPOS)
// =============================================

function smartbill_add_factura_column($columns) {
    $new_columns = array();
    $added = false;
    foreach($columns as $key => $value) {
        $new_columns[$key] = $value;
        if($key === 'order_total' || $key === 'total') {
            $new_columns['factura'] = 'Factură';
            $added = true;
        }
    }
    if(!$added) {
        $new_columns['factura'] = 'Factură';
    }
    return $new_columns;
}

add_filter('manage_edit-shop_order_columns', 'smartbill_add_factura_column');
add_filter('manage_woocommerce_page_wc-orders_columns', 'smartbill_add_factura_column');

function smartbill_render_factura_column_legacy($column) {
    global $post;
    if($column !== 'factura' || !$post) return;
    $order = wc_get_order($post->ID);
    if(!$order) return;
    smartbill_render_factura_cell($order->get_id(), $order);
}

function smartbill_render_factura_column_hpos($column, $order) {
    if($column !== 'factura' || !$order) return;
    smartbill_render_factura_cell($order->get_id(), $order);
}

function smartbill_render_factura_cell($order_id, $order = null) {
    if(!$order) $order = wc_get_order($order_id);
    if(!$order) return;
    $invoice_number = $order->get_meta('_smartbill_invoice_number');
    if($invoice_number) {
        $series = $order->get_meta('_smartbill_invoice_series');
        echo '<a href="' . esc_url(admin_url('admin-ajax.php?action=download_factura_pdf&order_id=' . $order_id)) . '" target="_blank">' . esc_html($series . $invoice_number) . '</a>';
    } else {
        $api_active = get_option('smartbill_api_active', 0);
        if($api_active) {
            echo '<button type="button" class="button genereaza-factura" data-order="' . esc_attr($order_id) . '" title="Generează factură SmartBill">Generează</button>';
        } else {
            echo '<span style="color:#999;">API oprit</span>';
        }
    }
}

add_action('manage_shop_order_posts_custom_column', 'smartbill_render_factura_column_legacy');
add_action('manage_woocommerce_page_wc-orders_custom_column', 'smartbill_render_factura_column_hpos', 10, 2);

// AJAX pentru generare manuală factură din admin
add_action('wp_ajax_genereaza_factura_manual', function() {
    if(!current_user_can('manage_woocommerce')) {
        wp_send_json_error('Neautorizat');
    }
    
    // Forțează generarea chiar dacă API-ul e oprit
    $order_id = intval($_POST['order_id']);
    $order = wc_get_order($order_id);
    if(!$order) {
        wp_send_json_error('Comanda nu există');
    }
    
    // Activează temporar API-ul pentru generare manuală
    $original_status = get_option('smartbill_api_active', 0);
    update_option('smartbill_api_active', 1);
    
    $result = genereaza_factura_smartbill($order_id);
    
    // Restaurează statusul original
    update_option('smartbill_api_active', $original_status);
    
    if($result && isset($result['number'])) {
        wp_send_json_success(array(
            'series' => $result['series'],
            'number' => $result['number']
        ));
    } else {
        wp_send_json_error('Eroare la generarea facturii');
    }
});

// Script pentru butonul de generare manuală (listă comenzi + pagina unei comenzi) — legacy și HPOS
add_action('admin_footer', function() {
    global $pagenow, $post;
    $is_order_list_legacy = ($pagenow === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'shop_order');
    $is_order_list_hpos  = ($pagenow === 'admin.php' && isset($_GET['page']) && $_GET['page'] === 'wc-orders');
    $is_order_edit = ($pagenow === 'post.php' && $post && get_post_type($post) === 'shop_order');
    if (!$is_order_list_legacy && !$is_order_list_hpos && !$is_order_edit) {
        return;
    }
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
                data: {
                    action: 'genereaza_factura_manual',
                    order_id: orderId
                },
                success: function(response) {
                    if(response.success) {
                        if (window.location.href.indexOf('post.php') !== -1) {
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

// Stil buton „Generează” compact (~40% mărime)
add_action('admin_head', function() {
    global $pagenow, $post;
    $is_orders = ($pagenow === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'shop_order')
        || ($pagenow === 'admin.php' && isset($_GET['page']) && $_GET['page'] === 'wc-orders')
        || ($pagenow === 'post.php' && $post && get_post_type($post) === 'shop_order');
    if (!$is_orders) return;
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

// Meta box în pagina comenzii pentru factură (legacy + HPOS)
add_action('add_meta_boxes', function() {
    $screen = 'shop_order';
    if (class_exists('\Automattic\WooCommerce\Utilities\OrderUtil') && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() && function_exists('wc_get_page_screen_id')) {
        $screen = wc_get_page_screen_id('shop-order');
    }
    add_meta_box(
        'smartbill_factura',
        'Factură SmartBill',
        'render_smartbill_order_metabox',
        $screen,
        'side',
        'high'
    );
});

function render_smartbill_order_metabox($order_or_post) {
    $order = is_a($order_or_post, 'WP_Post') ? wc_get_order($order_or_post->ID) : $order_or_post;
    if (!$order) return;
    $order_id = $order->get_id();

    $invoice_number = $order->get_meta('_smartbill_invoice_number');
    $invoice_series = $order->get_meta('_smartbill_invoice_series');
    $invoice_date = $order->get_meta('_smartbill_invoice_date');
    $api_active = get_option('smartbill_api_active', 0);

    if ($invoice_number) {
        echo '<p><strong>Factură:</strong> ' . esc_html($invoice_series . $invoice_number) . '</p>';
        if ($invoice_date) {
            echo '<p><strong>Data:</strong> ' . esc_html(date('d.m.Y', strtotime($invoice_date))) . '</p>';
        }
        echo '<p><a href="' . esc_url(admin_url('admin-ajax.php?action=download_factura_pdf&order_id=' . $order_id)) . '" class="button" target="_blank">📄 Descarcă PDF</a></p>';
    } else {
        if ($api_active) {
            echo '<p>Factura nu a fost generată.</p>';
            echo '<button type="button" class="button button-primary genereaza-factura" data-order="' . esc_attr($order_id) . '">Generează</button>';
        } else {
            echo '<p>Factura nu a fost generată.</p>';
            echo '<p style="color:orange;">⏸ API SmartBill dezactivat</p>';
            echo '<p><a href="' . esc_url(admin_url('admin.php?page=smartbill-settings')) . '">Activează API</a></p>';
        }
    }
}
