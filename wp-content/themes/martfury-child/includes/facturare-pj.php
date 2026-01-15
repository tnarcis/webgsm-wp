<?php
/**
 * MODUL FACTURARE PERSOANĂ JURIDICĂ - VERSIUNEA 2
 * - Checkout curat cu toggle PF/PJ
 * - Autocompletare date firmă din ANAF
 * - Câmpuri dinamice în funcție de selecție
 */

// =============================================
// TAB "DATE FACTURARE" ÎN MY ACCOUNT
// =============================================

add_filter('woocommerce_account_menu_items', function($items) {
    $new_items = array();
    foreach($items as $key => $value) {
        $new_items[$key] = $value;
        if($key === 'edit-account') {
            $new_items['date-facturare'] = 'Date Facturare';
        }
    }
    return $new_items;
}, 25);

add_action('init', function() {
    add_rewrite_endpoint('date-facturare', EP_ROOT | EP_PAGES);
});

// Conținutul paginii Date Facturare în My Account
add_action('woocommerce_account_date-facturare_endpoint', function() {
    $customer_id = get_current_user_id();
    
    // Salvare date
    if(isset($_POST['save_date_facturare']) && wp_verify_nonce($_POST['facturare_nonce'], 'save_facturare')) {
        $tip_facturare = sanitize_text_field($_POST['tip_facturare']);
        update_user_meta($customer_id, '_tip_facturare', $tip_facturare);
        
        if($tip_facturare === 'pj') {
            update_user_meta($customer_id, '_firma_cui', sanitize_text_field($_POST['firma_cui']));
            update_user_meta($customer_id, '_firma_nume', sanitize_text_field($_POST['firma_nume']));
            update_user_meta($customer_id, '_firma_reg_com', sanitize_text_field($_POST['firma_reg_com']));
            update_user_meta($customer_id, '_firma_adresa', sanitize_text_field($_POST['firma_adresa']));
            update_user_meta($customer_id, '_firma_judet', sanitize_text_field($_POST['firma_judet']));
            update_user_meta($customer_id, '_firma_oras', sanitize_text_field($_POST['firma_oras']));
        }
        
        echo '<div class="woocommerce-message">Datele de facturare au fost salvate!</div>';
    }
    
    $tip_facturare = get_user_meta($customer_id, '_tip_facturare', true) ?: 'pf';
    $firma_cui = get_user_meta($customer_id, '_firma_cui', true);
    $firma_nume = get_user_meta($customer_id, '_firma_nume', true);
    $firma_reg_com = get_user_meta($customer_id, '_firma_reg_com', true);
    $firma_adresa = get_user_meta($customer_id, '_firma_adresa', true);
    $firma_judet = get_user_meta($customer_id, '_firma_judet', true);
    $firma_oras = get_user_meta($customer_id, '_firma_oras', true);
    ?>
    
    <style>
        .facturare-toggle { display: flex; gap: 15px; margin-bottom: 25px; }
        .facturare-toggle label { 
            flex: 1; padding: 20px; border-radius: 12px; cursor: pointer; text-align: center;
            border: 2px solid #e0e0e0; background: #fff; transition: all 0.3s ease;
        }
        .facturare-toggle label:hover { border-color: #4CAF50; }
        .facturare-toggle input:checked + span { color: #2e7d32; }
        .facturare-toggle label.active { border-color: #4CAF50; background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%); }
        .facturare-toggle input { display: none; }
        .facturare-toggle .toggle-icon { font-size: 32px; display: block; margin-bottom: 8px; }
        .facturare-toggle .toggle-title { font-weight: 600; font-size: 16px; }
        
        .firma-box { background: #fff; border: 2px solid #e0e0e0; border-radius: 12px; padding: 25px; margin-top: 20px; }
        .firma-box h4 { margin: 0 0 20px 0; color: #1976D2; }
        .firma-box .form-row { margin-bottom: 15px; }
        .firma-box label { display: block; margin-bottom: 5px; font-weight: 500; color: #555; }
        .firma-box input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 15px; }
        .firma-box input:focus { border-color: #4CAF50; outline: none; box-shadow: 0 0 0 3px rgba(76,175,80,0.1); }
        
        .btn-cauta-cui { 
            background: linear-gradient(135deg, #1976D2 0%, #1565C0 100%); color: #fff; 
            border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-cauta-cui:hover { background: linear-gradient(135deg, #1565C0 0%, #0D47A1 100%); transform: translateY(-1px); }
        
        .anaf-result { padding: 15px; border-radius: 8px; margin: 15px 0; }
        .anaf-result.success { background: #e8f5e9; border: 1px solid #a5d6a7; color: #2e7d32; }
        .anaf-result.error { background: #ffebee; border: 1px solid #ef9a9a; color: #c62828; }
        .anaf-result.loading { background: #e3f2fd; border: 1px solid #90caf9; color: #1565c0; }
    </style>
    
    <h3>Date Facturare</h3>
    <p style="color:#666;">Alege tipul de factură și completează datele necesare.</p>
    
    <form method="post" id="form-facturare">
        <?php wp_nonce_field('save_facturare', 'facturare_nonce'); ?>
        
        <div class="facturare-toggle">
            <label class="<?php echo $tip_facturare === 'pf' ? 'active' : ''; ?>">
                <input type="radio" name="tip_facturare" value="pf" <?php checked($tip_facturare, 'pf'); ?>>
                <span class="toggle-icon">👤</span>
                <span class="toggle-title">Persoană Fizică</span>
            </label>
            <label class="<?php echo $tip_facturare === 'pj' ? 'active' : ''; ?>">
                <input type="radio" name="tip_facturare" value="pj" <?php checked($tip_facturare, 'pj'); ?>>
                <span class="toggle-icon">🏢</span>
                <span class="toggle-title">Persoană Juridică</span>
            </label>
        </div>
        
        <div id="date-firma" class="firma-box" style="<?php echo $tip_facturare === 'pj' ? '' : 'display:none;'; ?>">
            <h4>🏢 Date Firmă</h4>
            
            <div class="form-row" style="display:flex; gap:10px; align-items:flex-end;">
                <div style="flex:1;">
                    <label>CUI / CIF *</label>
                    <input type="text" name="firma_cui" id="firma_cui" value="<?php echo esc_attr($firma_cui); ?>" placeholder="ex: RO12345678">
                </div>
                <button type="button" id="btn_cauta_cui" class="btn-cauta-cui">🔍 Caută în ANAF</button>
            </div>
            
            <div id="anaf_result"></div>
            
            <div class="form-row">
                <label>Denumire Firmă *</label>
                <input type="text" name="firma_nume" id="firma_nume" value="<?php echo esc_attr($firma_nume); ?>">
            </div>
            
            <div class="form-row">
                <label>Nr. Reg. Comerțului</label>
                <input type="text" name="firma_reg_com" id="firma_reg_com" value="<?php echo esc_attr($firma_reg_com); ?>" placeholder="ex: J40/1234/2020">
            </div>
            
            <div class="form-row">
                <label>Adresa *</label>
                <input type="text" name="firma_adresa" id="firma_adresa" value="<?php echo esc_attr($firma_adresa); ?>">
            </div>
            
            <div style="display:flex; gap:15px;">
                <div class="form-row" style="flex:1;">
                    <label>Județ *</label>
                    <input type="text" name="firma_judet" id="firma_judet" value="<?php echo esc_attr($firma_judet); ?>">
                </div>
                <div class="form-row" style="flex:1;">
                    <label>Localitate *</label>
                    <input type="text" name="firma_oras" id="firma_oras" value="<?php echo esc_attr($firma_oras); ?>">
                </div>
            </div>
        </div>
        
        <p style="margin-top:25px;">
            <button type="submit" name="save_date_facturare" class="button" style="background:#4CAF50; color:#fff; border:none; padding:15px 30px; border-radius:8px; font-size:16px; cursor:pointer;">
                💾 Salvează datele
            </button>
        </p>
    </form>
    
    <script>
    jQuery(document).ready(function($) {
        // Toggle PF/PJ
        $('input[name="tip_facturare"]').on('change', function() {
            $('.facturare-toggle label').removeClass('active');
            $(this).closest('label').addClass('active');
            
            if($(this).val() === 'pj') {
                $('#date-firma').slideDown();
            } else {
                $('#date-firma').slideUp();
            }
        });
        
        // Căutare ANAF
        $('#btn_cauta_cui').on('click', function() {
            var cui = $('#firma_cui').val().trim().replace(/^RO/i, '');
            
            if(!cui || cui.length < 2) {
                $('#anaf_result').html('<div class="anaf-result error">Te rugăm să introduci un CUI valid.</div>');
                return;
            }
            
            $('#anaf_result').html('<div class="anaf-result loading">⏳ Se caută în baza ANAF...</div>');
            
            $.ajax({
                url: '<?php echo admin_url("admin-ajax.php"); ?>',
                type: 'POST',
                data: { action: 'cauta_cui_anaf', cui: cui },
                success: function(response) {
                    if(response.success && response.data) {
                        var d = response.data;
                        
                        $('#firma_nume').val(d.denumire || '');
                        $('#firma_reg_com').val(d.nrRegCom || '');
                        $('#firma_adresa').val(d.adresa || '');
                        $('#firma_judet').val(d.judet || '');
                        $('#firma_oras').val(d.localitate || '');
                        $('#firma_cui').val(d.tva ? 'RO' + cui : cui);
                        
                        var tvaStatus = d.tva ? '✓ Plătitor TVA' : '✗ Neplătitor TVA';
                        $('#anaf_result').html('<div class="anaf-result success">✓ <strong>' + d.denumire + '</strong> - ' + tvaStatus + '</div>');
                    } else {
                        $('#anaf_result').html('<div class="anaf-result error">CUI negăsit. Verifică sau completează manual.</div>');
                    }
                },
                error: function() {
                    $('#anaf_result').html('<div class="anaf-result error">Eroare conexiune. Încearcă din nou.</div>');
                }
            });
        });
    });
    </script>
    <?php
});

// =============================================
// CHECKOUT - ALEGERE PF/PJ
// =============================================

// Adaugă CSS pentru checkout
add_action('wp_head', function() {
    if(!is_checkout()) return;
    ?>
    <style>
        .tip-factura-checkout {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8ec 100%);
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            border: 1px solid #ddd;
        }
        .tip-factura-checkout h4 {
            margin: 0 0 15px 0;
            font-size: 18px;
            color: #333;
        }
        .tip-factura-toggle {
            display: flex;
            gap: 12px;
        }
        .tip-factura-toggle label {
            flex: 1;
            padding: 18px 15px;
            border-radius: 10px;
            cursor: pointer;
            text-align: center;
            border: 2px solid #ddd;
            background: #fff;
            transition: all 0.3s ease;
        }
        .tip-factura-toggle label:hover {
            border-color: #66bb6a;
        }
        .tip-factura-toggle label.active {
            border-color: #43a047;
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
        }
        .tip-factura-toggle input {
            display: none;
        }
        .tip-factura-toggle .icon {
            font-size: 28px;
            display: block;
            margin-bottom: 6px;
        }
        .tip-factura-toggle .text {
            font-weight: 600;
            color: #333;
        }
        
        .campuri-pj-checkout {
            background: #fff;
            border: 2px solid #42a5f5;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
        }
        .campuri-pj-checkout h4 {
            margin: 0 0 20px 0;
            color: #1976d2;
            font-size: 17px;
        }
        .campuri-pj-checkout .form-row {
            margin-bottom: 15px;
        }
        .campuri-pj-checkout label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #555;
        }
        .campuri-pj-checkout input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            box-sizing: border-box;
        }
        .campuri-pj-checkout input:focus {
            border-color: #42a5f5;
            outline: none;
            box-shadow: 0 0 0 3px rgba(66,165,245,0.15);
        }
        .campuri-pj-checkout .btn-cauta {
            background: linear-gradient(135deg, #42a5f5 0%, #1976d2 100%);
            color: #fff;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            white-space: nowrap;
        }
        .campuri-pj-checkout .btn-cauta:hover {
            background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
        }
        .campuri-pj-checkout .firma-salvata {
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 15px;
            border: 1px solid #a5d6a7;
        }
        .campuri-pj-checkout .firma-salvata strong {
            color: #2e7d32;
        }
        .campuri-pj-checkout .anaf-msg {
            padding: 12px 15px;
            border-radius: 8px;
            margin: 10px 0;
            font-size: 14px;
        }
        .campuri-pj-checkout .anaf-msg.loading {
            background: #e3f2fd;
            color: #1565c0;
        }
        .campuri-pj-checkout .anaf-msg.success {
            background: #e8f5e9;
            color: #2e7d32;
        }
        .campuri-pj-checkout .anaf-msg.error {
            background: #ffebee;
            color: #c62828;
        }
        
        /* Ascunde câmpuri când e PJ */
        body.facturare-pj #billing_first_name_field,
        body.facturare-pj #billing_last_name_field,
        body.facturare-pj #billing_company_field {
            display: none !important;
        }
    </style>
    <?php
});

// Afișează alegerea PF/PJ la începutul checkout-ului
add_action('woocommerce_before_checkout_billing_form', function() {
    $customer_id = get_current_user_id();
    $tip_facturare = get_user_meta($customer_id, '_tip_facturare', true) ?: 'pf';
    
    // Date firmă salvate
    $firma_cui = get_user_meta($customer_id, '_firma_cui', true);
    $firma_nume = get_user_meta($customer_id, '_firma_nume', true);
    $firma_reg_com = get_user_meta($customer_id, '_firma_reg_com', true);
    ?>
    
    <div class="tip-factura-checkout">
        <h4>📋 Tip Factură</h4>
        <div class="tip-factura-toggle">
            <label class="<?php echo $tip_facturare === 'pf' ? 'active' : ''; ?>">
                <input type="radio" name="tip_facturare_checkout" value="pf" <?php checked($tip_facturare, 'pf'); ?>>
                <span class="icon">👤</span>
                <span class="text">Persoană Fizică</span>
            </label>
            <label class="<?php echo $tip_facturare === 'pj' ? 'active' : ''; ?>">
                <input type="radio" name="tip_facturare_checkout" value="pj" <?php checked($tip_facturare, 'pj'); ?>>
                <span class="icon">🏢</span>
                <span class="text">Persoană Juridică</span>
            </label>
        </div>
    </div>
    
    <div class="campuri-pj-checkout" id="campuri-pj-checkout" style="<?php echo $tip_facturare === 'pj' ? '' : 'display:none;'; ?>">
        <h4>🏢 Date Firmă pentru Factură</h4>
        
        <?php if($firma_cui && $firma_nume): ?>
        <div class="firma-salvata" id="firma-salvata">
            <strong>Firmă salvată:</strong> <?php echo esc_html($firma_nume); ?> (<?php echo esc_html($firma_cui); ?>)
            <br><a href="#" id="schimba-firma" style="color:#1976d2; font-size:13px;">Folosește altă firmă</a>
            <input type="hidden" name="foloseste_firma_salvata" id="foloseste_firma_salvata" value="1">
            <input type="hidden" name="billing_cui" value="<?php echo esc_attr($firma_cui); ?>">
            <input type="hidden" name="billing_firma" value="<?php echo esc_attr($firma_nume); ?>">
            <input type="hidden" name="billing_reg_com" value="<?php echo esc_attr($firma_reg_com); ?>">
        </div>
        <?php endif; ?>
        
        <div id="campuri-firma-noi" style="<?php echo ($firma_cui && $firma_nume) ? 'display:none;' : ''; ?>">
            <div class="form-row" style="display:flex; gap:10px; align-items:flex-end;">
                <div style="flex:1;">
                    <label>CUI / CIF *</label>
                    <input type="text" name="billing_cui_nou" id="billing_cui_nou" placeholder="ex: RO12345678">
                </div>
                <button type="button" id="btn_cauta_cui_checkout" class="btn-cauta">🔍 Caută</button>
            </div>
            
            <div id="anaf_msg_checkout"></div>
            
            <div class="form-row">
                <label>Denumire Firmă *</label>
                <input type="text" name="billing_firma_nou" id="billing_firma_nou">
            </div>
            
            <div class="form-row">
                <label>Nr. Reg. Comerțului</label>
                <input type="text" name="billing_reg_com_nou" id="billing_reg_com_nou" placeholder="ex: J35/1234/2020">
            </div>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        function updateBodyClass() {
            if($('input[name="tip_facturare_checkout"]:checked').val() === 'pj') {
                $('body').addClass('facturare-pj');
            } else {
                $('body').removeClass('facturare-pj');
            }
        }
        
        // La încărcare
        updateBodyClass();
        
        // Toggle PF/PJ
        $('input[name="tip_facturare_checkout"]').on('change', function() {
            $('.tip-factura-toggle label').removeClass('active');
            $(this).closest('label').addClass('active');
            
            if($(this).val() === 'pj') {
                $('#campuri-pj-checkout').slideDown();
                $('body').addClass('facturare-pj');
            } else {
                $('#campuri-pj-checkout').slideUp();
                $('body').removeClass('facturare-pj');
            }
        });
        
        // Schimbă firma
        $('#schimba-firma').on('click', function(e) {
            e.preventDefault();
            $('#firma-salvata').slideUp();
            $('#campuri-firma-noi').slideDown();
            $('#foloseste_firma_salvata').val('0');
        });
        
        // Căutare ANAF
        $('#btn_cauta_cui_checkout').on('click', function() {
            var cui = $('#billing_cui_nou').val().trim().replace(/^RO/i, '');
            
            if(!cui || cui.length < 2) {
                $('#anaf_msg_checkout').html('<div class="anaf-msg error">Introdu un CUI valid.</div>');
                return;
            }
            
            $('#anaf_msg_checkout').html('<div class="anaf-msg loading">⏳ Se caută...</div>');
            
            $.ajax({
                url: '<?php echo admin_url("admin-ajax.php"); ?>',
                type: 'POST',
                data: { action: 'cauta_cui_anaf', cui: cui },
                success: function(response) {
                    if(response.success && response.data) {
                        var d = response.data;
                        $('#billing_firma_nou').val(d.denumire || '');
                        $('#billing_reg_com_nou').val(d.nrRegCom || '');
                        $('#billing_cui_nou').val(d.tva ? 'RO' + cui : cui);
                        
                        var tva = d.tva ? '✓ Plătitor TVA' : '✗ Neplătitor TVA';
                        $('#anaf_msg_checkout').html('<div class="anaf-msg success">✓ ' + d.denumire + ' - ' + tva + '</div>');
                    } else {
                        $('#anaf_msg_checkout').html('<div class="anaf-msg error">CUI negăsit. Completează manual.</div>');
                    }
                },
                error: function() {
                    $('#anaf_msg_checkout').html('<div class="anaf-msg error">Eroare conexiune.</div>');
                }
            });
        });
    });
    </script>
    <?php
});

// Salvează datele la comandă
add_action('woocommerce_checkout_update_order_meta', function($order_id) {
    $tip_facturare = isset($_POST['tip_facturare_checkout']) ? sanitize_text_field($_POST['tip_facturare_checkout']) : 'pf';
    update_post_meta($order_id, '_tip_facturare', $tip_facturare);
    
    // Debug - salvează ce primim
    $order = wc_get_order($order_id);
    $order->add_order_note('Tip facturare: ' . $tip_facturare);
    
    if($tip_facturare === 'pj') {
        $foloseste_salvata = isset($_POST['foloseste_firma_salvata']) ? $_POST['foloseste_firma_salvata'] : '0';
        
        if($foloseste_salvata == '1' && !empty($_POST['billing_cui'])) {
            $cui = sanitize_text_field($_POST['billing_cui']);
            $firma = sanitize_text_field($_POST['billing_firma']);
            $reg_com = sanitize_text_field($_POST['billing_reg_com']);
        } else {
            $cui = sanitize_text_field($_POST['billing_cui_nou'] ?? '');
            $firma = sanitize_text_field($_POST['billing_firma_nou'] ?? '');
            $reg_com = sanitize_text_field($_POST['billing_reg_com_nou'] ?? '');
        }
        
        update_post_meta($order_id, '_billing_cif', $cui);
        update_post_meta($order_id, '_billing_company_name', $firma);
        update_post_meta($order_id, '_billing_reg_com', $reg_com);
        
        // Debug
        $order->add_order_note('Firmă salvată: ' . $firma . ' | CUI: ' . $cui);
    }
});

// Afișează datele firmei în admin
add_action('woocommerce_admin_order_data_after_billing_address', function($order) {
    $tip_facturare = get_post_meta($order->get_id(), '_tip_facturare', true);
    
    if($tip_facturare === 'pj') {
        $cui = get_post_meta($order->get_id(), '_billing_cif', true);
        $firma = get_post_meta($order->get_id(), '_billing_company_name', true);
        $reg_com = get_post_meta($order->get_id(), '_billing_reg_com', true);
        
        echo '<div style="background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%); padding:15px; margin-top:15px; border-radius:8px; border:1px solid #90caf9;">';
        echo '<strong style="color:#1565c0;">🏢 Factură Persoană Juridică</strong><br><br>';
        echo '<strong>Firmă:</strong> ' . esc_html($firma) . '<br>';
        echo '<strong>CUI:</strong> ' . esc_html($cui) . '<br>';
        if($reg_com) echo '<strong>Reg. Com.:</strong> ' . esc_html($reg_com);
        echo '</div>';
    }
});

// =============================================
// AJAX: CĂUTARE CUI ÎN ANAF
// =============================================

add_action('wp_ajax_cauta_cui_anaf', 'cauta_cui_anaf_callback');
add_action('wp_ajax_nopriv_cauta_cui_anaf', 'cauta_cui_anaf_callback');

function cauta_cui_anaf_callback() {
    // ✅ SECURITATE: Rate limiting ANAF API (10 requests/min per IP)
    $user_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $transient_key = 'anaf_rate_limit_' . md5($user_ip);
    $request_count = get_transient($transient_key) ?: 0;
    
    if ($request_count >= 10) {
        wp_send_json_error('Prea multe cereri. Te rugăm să aștepți 1 minut.');
    }
    
    // Incrementează counter
    set_transient($transient_key, $request_count + 1, 60); // 60 secunde
    
    // ✅ SECURITATE: Validare CUI format corect
    $cui = preg_replace('/[^0-9]/', '', $_POST['cui']);
    
    if(empty($cui)) {
        wp_send_json_error('CUI invalid');
    }
    
    // Validare lungime CUI (6-10 cifre pentru România)
    if(strlen($cui) < 6 || strlen($cui) > 10) {
        wp_send_json_error('CUI invalid. Trebuie să aibă între 6 și 10 cifre.');
    }
    
    // Pas 1: Trimite cererea la ANAF
    $url = 'https://webservicesp.anaf.ro/AsynchWebService/api/v8/ws/tva';
    
    $body = json_encode(array(
        array(
            'cui' => intval($cui),
            'data' => date('Y-m-d')
        )
    ));
    
    $response = wp_remote_post($url, array(
        'timeout' => 30,
        'headers' => array(
            'Content-Type' => 'application/json'
        ),
        'body' => $body
    ));
    
    if(is_wp_error($response)) {
        wp_send_json_error('Eroare conexiune ANAF');
    }
    
    $data = json_decode(wp_remote_retrieve_body($response), true);
    
    if(!isset($data['correlationId'])) {
        wp_send_json_error('Eroare ANAF - nu s-a primit ID');
    }
    
    // Pas 2: Așteptăm și cerem rezultatul
    sleep(2);
    
    $url_result = 'https://webservicesp.anaf.ro/AsynchWebService/api/v8/ws/tva?id=' . $data['correlationId'];
    
    $response2 = wp_remote_get($url_result, array(
        'timeout' => 30
    ));
    
    if(is_wp_error($response2)) {
        wp_send_json_error('Eroare la preluare rezultat');
    }
    
    $result = json_decode(wp_remote_retrieve_body($response2), true);
    
    if(isset($result['found'][0]['date_generale'])) {
        $firma = $result['found'][0]['date_generale'];
        $adresa_sediu = $result['found'][0]['adresa_sediu_social'] ?? array();
        $tva_info = $result['found'][0]['inregistrare_scop_Tva'] ?? array();
        
        $judet = $adresa_sediu['sdenumire_Judet'] ?? '';
        $localitate = $adresa_sediu['sdenumire_Localitate'] ?? '';
        $strada = ($adresa_sediu['sdenumire_Strada'] ?? '') . ' ' . ($adresa_sediu['snumar_Strada'] ?? '');
        $detalii = $adresa_sediu['sdetalii_Adresa'] ?? '';
        
        $adresa_completa = trim($strada);
        if($detalii) $adresa_completa .= ', ' . $detalii;
        
        wp_send_json_success(array(
            'denumire' => $firma['denumire'] ?? '',
            'cui' => $cui,
            'nrRegCom' => $firma['nrRegCom'] ?? '',
            'adresa' => $adresa_completa,
            'judet' => $judet,
            'localitate' => str_replace(array('Mun. ', 'Or. ', 'Com. '), '', $localitate),
            'tva' => ($tva_info['scpTVA'] ?? 0) == 1
        ));
    }
    
    wp_send_json_error('CUI negăsit în baza ANAF');
}
