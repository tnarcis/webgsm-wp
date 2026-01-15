<?php
/**
 * MODUL ÎNREGISTRARE ÎMBUNĂTĂȚITĂ
 * - Confirmare email obligatorie
 * - Alegere PF/PJ la înregistrare
 * - Câmpuri suplimentare (telefon, etc.)
 */

// =============================================
// CÂMPURI SUPLIMENTARE LA ÎNREGISTRARE
// =============================================

// Adaugă câmpuri noi în formularul de înregistrare cu LINE-ART design
add_action('woocommerce_register_form_start', function() {
    ?>
    <style>
    /* ====== TOGGLE PF/PJ LINE-ART ====== */
    .webgsm-account-toggle {
        display: flex;
        gap: 12px;
        margin: 24px 0;
        padding: 0;
    }
    .webgsm-account-toggle label {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 16px 20px;
        border: 2px solid #e0e0e0;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.3s ease;
        background: #fff;
        position: relative;
        overflow: hidden;
    }
    .webgsm-account-toggle label:hover {
        border-color: #2196F3;
        background: linear-gradient(135deg, rgba(33,150,243,0.03) 0%, rgba(33,150,243,0.08) 100%);
    }
    .webgsm-account-toggle label:hover .toggle-icon svg {
        stroke: #2196F3;
    }
    .webgsm-account-toggle label.active {
        border-color: #1976D2;
        background: linear-gradient(135deg, rgba(33,150,243,0.05) 0%, rgba(33,150,243,0.12) 100%);
        box-shadow: 0 4px 15px rgba(33,150,243,0.15);
    }
    .webgsm-account-toggle label.active .toggle-icon svg {
        stroke: #1976D2;
    }
    .webgsm-account-toggle input[type="radio"] {
        display: none;
    }
    .toggle-icon {
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .toggle-icon svg {
        width: 24px;
        height: 24px;
        stroke: #888;
        stroke-width: 1.5;
        fill: none;
        transition: stroke 0.3s ease;
    }
    .toggle-text {
        font-size: 14px;
        font-weight: 500;
        color: #444;
    }
    .webgsm-account-toggle label.active .toggle-text {
        color: #1976D2;
        font-weight: 600;
    }
    
    /* Badge B2B */
    .b2b-badge {
        position: absolute;
        top: -2px;
        right: -2px;
        background: linear-gradient(135deg, #1976D2 0%, #2196F3 100%);
        color: #fff;
        font-size: 9px;
        font-weight: 700;
        padding: 4px 8px;
        border-radius: 0 10px 0 8px;
        letter-spacing: 0.5px;
        opacity: 0;
        transform: translateY(-5px);
        transition: all 0.3s ease;
    }
    .webgsm-account-toggle label.active .b2b-badge,
    .webgsm-account-toggle label:hover .b2b-badge {
        opacity: 1;
        transform: translateY(0);
    }
    
    /* ====== FORMULARUL FIRMĂ - ALBASTRU ====== */
    #campuri-firma-register {
        background: linear-gradient(135deg, rgba(33,150,243,0.04) 0%, rgba(25,118,210,0.08) 100%);
        border: 2px solid rgba(33,150,243,0.2);
        border-radius: 16px;
        padding: 24px;
        margin: 20px 0;
        animation: fadeInDown 0.4s ease;
    }
    @keyframes fadeInDown {
        from { opacity: 0; transform: translateY(-15px); }
        to { opacity: 1; transform: translateY(0); }
    }
    #campuri-firma-register .firma-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid rgba(33,150,243,0.15);
    }
    #campuri-firma-register .firma-header svg {
        width: 28px;
        height: 28px;
        stroke: #1976D2;
        stroke-width: 1.5;
        fill: none;
    }
    #campuri-firma-register .firma-header h4 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
        color: #1565C0;
    }
    
    /* Buton ANAF Line-Art */
    .anaf-search-row {
        display: flex;
        gap: 12px;
        align-items: flex-end;
        margin-bottom: 18px;
    }
    .anaf-search-row .cui-field {
        flex: 1;
    }
    #btn_cauta_cui_register {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 12px 18px !important;
        background: linear-gradient(135deg, #1976D2 0%, #2196F3 100%) !important;
        color: #fff !important;
        border: none !important;
        border-radius: 10px !important;
        font-size: 13px !important;
        font-weight: 500 !important;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 3px 10px rgba(33,150,243,0.25);
        white-space: nowrap;
        height: auto;
        line-height: 1.2;
        text-align: center;
    }
    #btn_cauta_cui_register:hover {
        background: linear-gradient(135deg, #1565C0 0%, #1976D2 100%) !important;
        box-shadow: 0 5px 20px rgba(33,150,243,0.35);
        transform: translateY(-2px);
    }
    #btn_cauta_cui_register svg {
        width: 18px;
        height: 18px;
        stroke: #fff;
        stroke-width: 2;
        fill: none;
    }
    
    /* Rezultat ANAF */
    #anaf_result_register {
        padding: 14px 18px;
        border-radius: 10px;
        margin-bottom: 18px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    #anaf_result_register.success {
        background: linear-gradient(135deg, rgba(76,175,80,0.08) 0%, rgba(76,175,80,0.15) 100%);
        border: 1px solid rgba(76,175,80,0.3);
        color: #2E7D32;
    }
    #anaf_result_register.error {
        background: linear-gradient(135deg, rgba(244,67,54,0.08) 0%, rgba(244,67,54,0.15) 100%);
        border: 1px solid rgba(244,67,54,0.3);
        color: #C62828;
    }
    #anaf_result_register.loading {
        background: rgba(33,150,243,0.08);
        border: 1px solid rgba(33,150,243,0.2);
        color: #1976D2;
    }
    #anaf_result_register svg {
        width: 20px;
        height: 20px;
        stroke-width: 2;
        fill: none;
        flex-shrink: 0;
    }
    
    /* Câmpuri firmă */
    #campuri-firma-register input.input-text {
        border: 2px solid rgba(33,150,243,0.2) !important;
        transition: border-color 0.3s ease, box-shadow 0.3s ease;
    }
    #campuri-firma-register input.input-text:focus {
        border-color: #2196F3 !important;
        box-shadow: 0 0 0 3px rgba(33,150,243,0.1);
        outline: none;
    }
    #campuri-firma-register label {
        color: #1565C0 !important;
    }
    </style>
    
    <p class="form-row form-row-first">
        <label for="reg_billing_first_name"><?php esc_html_e('Prenume', 'flavor'); ?> <span class="required">*</span></label>
        <input type="text" class="input-text" name="billing_first_name" id="reg_billing_first_name" value="<?php echo isset($_POST['billing_first_name']) ? esc_attr($_POST['billing_first_name']) : ''; ?>" required />
    </p>
    
    <p class="form-row form-row-last">
        <label for="reg_billing_last_name"><?php esc_html_e('Nume', 'flavor'); ?> <span class="required">*</span></label>
        <input type="text" class="input-text" name="billing_last_name" id="reg_billing_last_name" value="<?php echo isset($_POST['billing_last_name']) ? esc_attr($_POST['billing_last_name']) : ''; ?>" required />
    </p>
    
    <p class="form-row form-row-wide">
        <label for="reg_billing_phone"><?php esc_html_e('Telefon', 'flavor'); ?> <span class="required">*</span></label>
        <input type="tel" class="input-text" name="billing_phone" id="reg_billing_phone" value="<?php echo isset($_POST['billing_phone']) ? esc_attr($_POST['billing_phone']) : ''; ?>" required />
    </p>
    
    <div class="clear"></div>
    
    <!-- TOGGLE PF/PJ cu LINE-ART -->
    <div class="webgsm-account-toggle">
        <label id="toggle-pf" class="active">
            <input type="radio" name="tip_facturare" value="pf" checked>
            <span class="toggle-icon">
                <svg viewBox="0 0 24 24">
                    <circle cx="12" cy="7" r="4"/>
                    <path d="M5.5 21a6.5 6.5 0 0 1 13 0"/>
                </svg>
            </span>
            <span class="toggle-text">Persoană Fizică</span>
        </label>
        <label id="toggle-pj">
            <input type="radio" name="tip_facturare" value="pj">
            <span class="toggle-icon">
                <svg viewBox="0 0 24 24">
                    <rect x="3" y="8" width="18" height="13" rx="2"/>
                    <path d="M7 8V6a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v2"/>
                    <line x1="12" y1="12" x2="12" y2="16"/>
                    <line x1="10" y1="14" x2="14" y2="14"/>
                </svg>
            </span>
            <span class="toggle-text">Persoană Juridică</span>
            <span class="b2b-badge">PREȚURI B2B</span>
        </label>
    </div>
    
    <!-- FORMULAR FIRMĂ cu DESIGN ALBASTRU -->
    <div id="campuri-firma-register" style="display:none;">
        <div class="firma-header">
            <svg viewBox="0 0 24 24">
                <rect x="3" y="8" width="18" height="13" rx="2"/>
                <path d="M7 8V6a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v2"/>
                <line x1="12" y1="12" x2="12" y2="16"/>
                <line x1="10" y1="14" x2="14" y2="14"/>
            </svg>
            <h4>Date Firmă</h4>
        </div>
        
        <div class="anaf-search-row">
            <div class="cui-field">
                <label for="reg_firma_cui">CUI / CIF <span class="required">*</span></label>
                <input type="text" class="input-text" name="firma_cui" id="reg_firma_cui" placeholder="ex: RO12345678">
            </div>
            <button type="button" id="btn_cauta_cui_register">
                <svg viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="7"/>
                    <line x1="16" y1="16" x2="21" y2="21"/>
                </svg>
                Autocompletare
            </button>
        </div>
        
        <div id="anaf_result_register" style="display:none;"></div>
        
        <p class="form-row form-row-wide">
            <label for="reg_firma_nume">Denumire Firmă <span class="required">*</span></label>
            <input type="text" class="input-text" name="firma_nume" id="reg_firma_nume">
        </p>
        
        <p class="form-row form-row-wide">
            <label for="reg_firma_reg_com">Nr. Reg. Comerțului</label>
            <input type="text" class="input-text" name="firma_reg_com" id="reg_firma_reg_com" placeholder="ex: J40/1234/2020">
        </p>
        
        <p class="form-row form-row-wide">
            <label for="reg_firma_adresa">Adresa Firmă</label>
            <input type="text" class="input-text" name="firma_adresa" id="reg_firma_adresa">
        </p>
        
        <p class="form-row form-row-first">
            <label for="reg_firma_judet">Județ</label>
            <input type="text" class="input-text" name="firma_judet" id="reg_firma_judet">
        </p>
        
        <p class="form-row form-row-last">
            <label for="reg_firma_oras">Localitate</label>
            <input type="text" class="input-text" name="firma_oras" id="reg_firma_oras">
        </p>
        
        <div class="clear"></div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Toggle PF/PJ cu animație
        $('input[name="tip_facturare"]').on('change', function() {
            $('.webgsm-account-toggle label').removeClass('active');
            $(this).closest('label').addClass('active');
            
            if($(this).val() === 'pj') {
                $('#campuri-firma-register').slideDown(300);
            } else {
                $('#campuri-firma-register').slideUp(300);
            }
        });
        
        // Căutare ANAF cu iconițe line-art
        $('#btn_cauta_cui_register').on('click', function() {
            var cui = $('#reg_firma_cui').val().trim().replace(/^RO/i, '');
            var $result = $('#anaf_result_register');
            
            if(!cui || cui.length < 2) {
                $result.removeClass('success loading').addClass('error')
                    .html('<svg viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg><span>Introdu un CUI valid</span>')
                    .show();
                return;
            }
            
            $result.removeClass('success error').addClass('loading')
                .html('<svg viewBox="0 0 24 24" stroke="currentColor" style="animation: spin 1s linear infinite;"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg><span>Se caută în baza de date ANAF...</span>')
                .show();
            
            $.ajax({
                url: '<?php echo admin_url("admin-ajax.php"); ?>',
                type: 'POST',
                data: { action: 'cauta_cui_anaf', cui: cui },
                success: function(response) {
                    if(response.success && response.data) {
                        var d = response.data;
                        $('#reg_firma_nume').val(d.denumire || '');
                        $('#reg_firma_reg_com').val(d.nrRegCom || '');
                        $('#reg_firma_adresa').val(d.adresa || '');
                        $('#reg_firma_judet').val(d.judet || '');
                        $('#reg_firma_oras').val(d.localitate || '');
                        $('#reg_firma_cui').val(d.tva ? 'RO' + cui : cui);
                        
                        var tva = d.tva ? 'Plătitor TVA' : 'Neplătitor TVA';
                        $result.removeClass('loading error').addClass('success')
                            .html('<svg viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="10"/><polyline points="9 12 11 14 15 10"/></svg><span><strong>' + d.denumire + '</strong> · ' + tva + '</span>')
                            .show();
                    } else {
                        $result.removeClass('loading success').addClass('error')
                            .html('<svg viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><circle cx="12" cy="16" r="0.5" fill="currentColor"/></svg><span>CUI negăsit în baza ANAF. Completează manual datele.</span>')
                            .show();
                    }
                },
                error: function() {
                    $result.removeClass('loading success').addClass('error')
                        .html('<svg viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><circle cx="12" cy="16" r="0.5" fill="currentColor"/></svg><span>Eroare de conexiune. Încearcă din nou.</span>')
                        .show();
                }
            });
        });
    });
    </script>
    
    <style>
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    </style>
    <?php
});

// Validare câmpuri la înregistrare
add_filter('woocommerce_registration_errors', function($errors, $username, $email) {
    if(empty($_POST['billing_first_name'])) {
        $errors->add('billing_first_name_error', 'Te rugăm să completezi prenumele.');
    }
    
    if(empty($_POST['billing_last_name'])) {
        $errors->add('billing_last_name_error', 'Te rugăm să completezi numele.');
    }
    
    // ✅ SECURITATE: Validare telefon format corect
    if(empty($_POST['billing_phone'])) {
        $errors->add('billing_phone_error', 'Te rugăm să completezi telefonul.');
    } else {
        $phone = preg_replace('/[^0-9+]/', '', $_POST['billing_phone']);
        // Validare format RO: +4 sau 0 urmat de 9 cifre
        if(!preg_match('/^(\+4|0)[0-9]{9}$/', $phone)) {
            $errors->add('billing_phone_error', 'Telefon invalid. Format corect: 0712345678 sau +40712345678');
        }
    }
    
    // Validare câmpuri firmă dacă e PJ
    if(isset($_POST['tip_facturare']) && $_POST['tip_facturare'] === 'pj') {
        // ✅ SECURITATE: Validare CUI format corect
        if(empty($_POST['firma_cui'])) {
            $errors->add('firma_cui_error', 'Te rugăm să completezi CUI-ul firmei.');
        } else {
            $cui = preg_replace('/[^0-9]/', '', $_POST['firma_cui']);
            // Validare lungime CUI (6-10 cifre pentru România)
            if(strlen($cui) < 6 || strlen($cui) > 10) {
                $errors->add('firma_cui_error', 'CUI invalid. Trebuie să aibă între 6 și 10 cifre (ex: RO12345678).');
            }
        }
        
        if(empty($_POST['firma_nume'])) {
            $errors->add('firma_nume_error', 'Te rugăm să completezi denumirea firmei.');
        }
    }
    
    return $errors;
}, 10, 3);

// Salvează datele suplimentare la înregistrare
add_action('woocommerce_created_customer', function($customer_id) {
    if(isset($_POST['billing_first_name'])) {
        update_user_meta($customer_id, 'billing_first_name', sanitize_text_field($_POST['billing_first_name']));
        update_user_meta($customer_id, 'first_name', sanitize_text_field($_POST['billing_first_name']));
    }
    
    if(isset($_POST['billing_last_name'])) {
        update_user_meta($customer_id, 'billing_last_name', sanitize_text_field($_POST['billing_last_name']));
        update_user_meta($customer_id, 'last_name', sanitize_text_field($_POST['billing_last_name']));
    }
    
    if(isset($_POST['billing_phone'])) {
        update_user_meta($customer_id, 'billing_phone', sanitize_text_field($_POST['billing_phone']));
    }
    
    // Salvează tipul de facturare
    $tip_facturare = isset($_POST['tip_facturare']) ? sanitize_text_field($_POST['tip_facturare']) : 'pf';
    update_user_meta($customer_id, '_tip_facturare', $tip_facturare);
    
    // Salvează datele firmei dacă e PJ
    if($tip_facturare === 'pj') {
        update_user_meta($customer_id, '_firma_cui', sanitize_text_field($_POST['firma_cui'] ?? ''));
        update_user_meta($customer_id, '_firma_nume', sanitize_text_field($_POST['firma_nume'] ?? ''));
        update_user_meta($customer_id, '_firma_reg_com', sanitize_text_field($_POST['firma_reg_com'] ?? ''));
        update_user_meta($customer_id, '_firma_adresa', sanitize_text_field($_POST['firma_adresa'] ?? ''));
        update_user_meta($customer_id, '_firma_judet', sanitize_text_field($_POST['firma_judet'] ?? ''));
        update_user_meta($customer_id, '_firma_oras', sanitize_text_field($_POST['firma_oras'] ?? ''));
    }
    
    // Marchează contul ca neconfirmat
    update_user_meta($customer_id, '_email_confirmed', 0);
    update_user_meta($customer_id, '_confirmation_token', wp_generate_password(32, false));
    
    // Trimite email de confirmare
    envoi_email_confirmare($customer_id);
});

// =============================================
// CONFIRMARE EMAIL
// =============================================

function envoi_email_confirmare($customer_id) {
    $user = get_user_by('ID', $customer_id);
    if(!$user) return;
    
    $token = get_user_meta($customer_id, '_confirmation_token', true);
    $confirm_url = add_query_arg(array(
        'confirm_email' => '1',
        'user_id' => $customer_id,
        'token' => $token
    ), wc_get_page_permalink('myaccount'));
    
    $subject = 'Confirmă adresa de email - ' . get_bloginfo('name');
    
    $message = '
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
        <h2 style="color: #333;">Bine ai venit!</h2>
        <p>Mulțumim pentru înregistrare. Te rugăm să confirmi adresa de email făcând click pe butonul de mai jos:</p>
        <p style="text-align: center; margin: 30px 0;">
            <a href="' . esc_url($confirm_url) . '" style="background: #4CAF50; color: #fff; padding: 15px 30px; text-decoration: none; border-radius: 8px; display: inline-block; font-weight: bold;">
                ✓ Confirmă Email
            </a>
        </p>
        <p style="color: #666; font-size: 13px;">Sau copiază acest link în browser:<br>' . esc_url($confirm_url) . '</p>
        <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">
        <p style="color: #999; font-size: 12px;">Acest email a fost trimis de ' . get_bloginfo('name') . '</p>
    </div>';
    
    $headers = array('Content-Type: text/html; charset=UTF-8');
    
    wp_mail($user->user_email, $subject, $message, $headers);
}

// Procesează confirmarea email
add_action('init', function() {
    if(isset($_GET['confirm_email']) && isset($_GET['user_id']) && isset($_GET['token'])) {
        $user_id = intval($_GET['user_id']);
        $token = sanitize_text_field($_GET['token']);
        
        $saved_token = get_user_meta($user_id, '_confirmation_token', true);
        
        if($saved_token && $saved_token === $token) {
            update_user_meta($user_id, '_email_confirmed', 1);
            delete_user_meta($user_id, '_confirmation_token');
            
            // Setează mesaj de succes
            wc_add_notice('Email confirmat cu succes! Acum te poți autentifica.', 'success');
            
            wp_redirect(wc_get_page_permalink('myaccount'));
            exit;
        } else {
            wc_add_notice('Link de confirmare invalid sau expirat.', 'error');
            wp_redirect(wc_get_page_permalink('myaccount'));
            exit;
        }
    }
});

// Blochează autentificarea dacă email-ul nu e confirmat
add_filter('wp_authenticate_user', function($user, $password) {
    if(is_wp_error($user)) {
        return $user;
    }
    
    // Verifică dacă e admin - adminii nu au nevoie de confirmare
    if(user_can($user, 'manage_options')) {
        return $user;
    }
    
    $email_confirmed = get_user_meta($user->ID, '_email_confirmed', true);
    
    // Dacă nu există meta (cont vechi), consideră confirmat
    if($email_confirmed === '') {
        return $user;
    }
    
    if($email_confirmed != 1) {
        return new WP_Error(
            'email_not_confirmed',
            '<strong>Email neconfirmat!</strong> Te rugăm să verifici inbox-ul și să confirmi adresa de email. <a href="#" class="resend-confirmation" data-user="' . $user->ID . '">Retrimite email de confirmare</a>'
        );
    }
    
    return $user;
}, 10, 2);

// AJAX pentru retrimitere email confirmare
add_action('wp_ajax_nopriv_resend_confirmation', function() {
    $user_id = intval($_POST['user_id']);
    
    if($user_id) {
        // Generează token nou
        update_user_meta($user_id, '_confirmation_token', wp_generate_password(32, false));
        envoi_email_confirmare($user_id);
        wp_send_json_success('Email retrimis!');
    }
    
    wp_send_json_error('Eroare');
});

// Script pentru retrimitere
add_action('wp_footer', function() {
    if(!is_account_page()) return;
    ?>
    <script>
    jQuery(document).ready(function($) {
        $(document).on('click', '.resend-confirmation', function(e) {
            e.preventDefault();
            var userId = $(this).data('user');
            
            $.ajax({
                url: '<?php echo admin_url("admin-ajax.php"); ?>',
                type: 'POST',
                data: { action: 'resend_confirmation', user_id: userId },
                success: function(response) {
                    if(response.success) {
                        alert('Email de confirmare retrimis! Verifică inbox-ul.');
                    }
                }
            });
        });
    });
    </script>
    <?php
});

// =============================================
// AFIȘARE STATUS CONFIRMARE ÎN ADMIN
// =============================================

// Coloană în lista de useri
add_filter('manage_users_columns', function($columns) {
    $columns['email_confirmed'] = 'Email Confirmat';
    return $columns;
});

add_action('manage_users_custom_column', function($value, $column_name, $user_id) {
    if($column_name === 'email_confirmed') {
        $confirmed = get_user_meta($user_id, '_email_confirmed', true);
        
        if($confirmed === '' || $confirmed == 1) {
            return '<span style="color:green;">✓ Da</span>';
        } else {
            return '<span style="color:red;">✗ Nu</span> <a href="#" class="confirm-user-email" data-user="' . $user_id . '" style="font-size:11px;">Confirmă manual</a>';
        }
    }
    return $value;
}, 10, 3);

// AJAX confirmare manuală din admin
add_action('wp_ajax_admin_confirm_email', function() {
    if(!current_user_can('edit_users')) {
        wp_send_json_error('Neautorizat');
    }
    
    $user_id = intval($_POST['user_id']);
    update_user_meta($user_id, '_email_confirmed', 1);
    delete_user_meta($user_id, '_confirmation_token');
    
    wp_send_json_success('Confirmat');
});

add_action('admin_footer', function() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $(document).on('click', '.confirm-user-email', function(e) {
            e.preventDefault();
            var btn = $(this);
            var userId = btn.data('user');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: { action: 'admin_confirm_email', user_id: userId },
                success: function(response) {
                    if(response.success) {
                        btn.closest('td').html('<span style="color:green;">✓ Da</span>');
                    }
                }
            });
        });
    });
    </script>
    <?php
});


// =============================================
// STYLING FORMULAR ÎNREGISTRARE
// =============================================

add_action('wp_head', function() {
    if(!is_account_page()) return;
    ?>
    <style>
    /* ====== CONTAINER FORMULAR ====== */
    .woocommerce-form-register {
        max-width: 480px !important;
    }
    
    /* Toate form-row pe full width */
    .woocommerce-form-register .form-row,
    .woocommerce-form-register .form-row-first,
    .woocommerce-form-register .form-row-last,
    .woocommerce-form-register .form-row-wide {
        width: 100% !important;
        display: block !important;
        float: none !important;
        margin-bottom: 18px !important;
        margin-right: 0 !important;
    }
    
    /* Labels cu line-art feel */
    .woocommerce-form-register label {
        display: block !important;
        margin-bottom: 8px !important;
        font-weight: 500 !important;
        color: #444 !important;
        font-size: 14px !important;
    }
    
    /* Toate input-urile cu border albastru la focus */
    .woocommerce-form-register input[type="text"],
    .woocommerce-form-register input[type="email"],
    .woocommerce-form-register input[type="tel"],
    .woocommerce-form-register input[type="password"],
    .woocommerce-form-register input.input-text,
    .woocommerce-form-register .input-text {
        width: 100% !important;
        padding: 14px 16px !important;
        border: 2px solid #e0e0e0 !important;
        border-radius: 10px !important;
        font-size: 15px !important;
        background: #fff !important;
        box-sizing: border-box !important;
        display: block !important;
        transition: border-color 0.3s ease, box-shadow 0.3s ease;
    }
    
    .woocommerce-form-register input:focus,
    .woocommerce-form-register .input-text:focus {
        border-color: #2196F3 !important;
        box-shadow: 0 0 0 3px rgba(33,150,243,0.1) !important;
        outline: none !important;
    }
    
    /* Buton submit stilizat */
    .woocommerce-form-register button[type="submit"],
    .woocommerce-form-register .woocommerce-form-register__submit {
        background: linear-gradient(135deg, #1976D2 0%, #2196F3 100%) !important;
        border: none !important;
        border-radius: 12px !important;
        padding: 16px 32px !important;
        font-size: 15px !important;
        font-weight: 600 !important;
        color: #fff !important;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(33,150,243,0.25);
        width: 100%;
        margin-top: 10px;
    }
    
    .woocommerce-form-register button[type="submit"]:hover,
    .woocommerce-form-register .woocommerce-form-register__submit:hover {
        background: linear-gradient(135deg, #1565C0 0%, #1976D2 100%) !important;
        box-shadow: 0 6px 20px rgba(33,150,243,0.35);
        transform: translateY(-2px);
    }
    
    /* Clear floats */
    .woocommerce-form-register .clear {
        clear: both !important;
        display: block !important;
    }
    
    /* Required asterisk */
    .woocommerce-form-register .required {
        color: #e53935;
    }
    </style>
    <?php
});