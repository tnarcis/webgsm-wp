<?php
/**
 * WebGSM My Account - Popup Modals pentru Adrese, Firme, Persoane
 * DEPRECATED: Modalurile au fost mutate INSIDE secțiuni în addresses_page_content()
 * @version 2.0 - DISABLED
 */
if (!defined('ABSPATH')) exit;

// DEPRECATED: Modalurile sunt acum renderizate direct în fiecare secțiune
// pentru a permite poziționare absolută relativă la secțiune
// Vezi: webgsm-checkout-pro.php -> addresses_page_content()

/*
// Render popupurile și flag-ul doar pe My Account page cu endpoint-ul "adrese-salvate"
add_action('woocommerce_account_adrese-salvate_endpoint', function() {
    // Set flag for JavaScript - popupurile sunt pe pagina
    echo '<script>window.webgsm_is_myaccount = true; console.log("[WebGSM] My Account page detected");</script>';
    
    // Get counties helper function
    $get_counties = function() {
        $counties_full = [
            'AB' => 'Alba', 'AG' => 'Argeș', 'AR' => 'Arad', 'B' => 'București',
            'BC' => 'Bacău', 'BH' => 'Bihor', 'BN' => 'Bistrița-Năsăud', 'BR' => 'Brăila',
            'BT' => 'Botoșani', 'BV' => 'Brașov', 'C' => 'Constanța', 'CJ' => 'Cluj',
            'CV' => 'Covasna', 'DB' => 'Dâmbovița', 'DJ' => 'Dolj', 'GJ' => 'Gorj',
            'GL' => 'Galați', 'GR' => 'Giurgiu', 'HD' => 'Hunedoara', 'HR' => 'Harghita',
            'IF' => 'Ilfov', 'IL' => 'Ialomița', 'IS' => 'Iași', 'JN' => 'Neamț',
            'JS' => 'Sibiu', 'MH' => 'Mehedinți', 'MS' => 'Mureș', 'MT' => 'Maramureș',
            'OT' => 'Olt', 'PH' => 'Prahova', 'SB' => 'Sibiu', 'SJ' => 'Sălaj',
            'SM' => 'Satu Mare', 'SV' => 'Suceava', 'TL' => 'Tulcea', 'TM' => 'Timiș',
            'TR' => 'Teleorman', 'VL' => 'Vâlcea', 'VS' => 'Vaslui', 'VN' => 'Vrancea'
        ];
        
        $html = '';
        foreach ($counties_full as $code => $name) {
            $html .= '<option value="' . esc_attr($code) . '">' . esc_html($name) . '</option>';
        }
        return $html;
    };
    
    $counties_options = $get_counties();
    
    // =========================================
    // Address popup modal for My Account
    // =========================================
    echo '
    <div class="webgsm-popup" id="address_modal_saved" style="display:none;">
        <div class="popup-overlay"></div>
        <div class="popup-content">
            <div class="popup-header"><h3 id="modal_title">Adauga adresa</h3><button type="button" class="popup-close modal-close-btn">×</button></div>
            <div class="popup-body">
                <input type="hidden" id="edit_address_index" value="">
                <div class="form-row"><div class="form-col"><label>Eticheta</label><input type="text" id="modal_label" placeholder="Acasa, Birou..."></div></div>
                <div class="form-row">
                    <div class="form-col"><label>Nume *</label><input type="text" id="modal_name"></div>
                    <div class="form-col"><label>Telefon *</label><input type="tel" id="modal_phone"></div>
                </div>
                <div class="form-row"><div class="form-col full"><label>Adresa *</label><input type="text" id="modal_address"></div></div>
                <div class="form-row">
                    <div class="form-col"><label>Localitate *</label><input type="text" id="modal_city"></div>
                    <div class="form-col"><label>Judet *</label>
                    <select id="modal_county">' . $counties_options . '</select>
                    </div>
                    <div class="form-col"><label>Cod postal</label><input type="text" id="modal_postcode"></div>
                </div>
            </div>
            <div class="popup-footer">
                <button type="button" class="btn-secondary modal-cancel-btn">Anuleaza</button>
                <button type="button" class="btn-primary" id="save_address_modal_btn">Salveaza</button>
            </div>
        </div>
    </div>
    ';
    
    // =========================================
    // Company popup modal for My Account
    // =========================================
    echo '
    <div class="webgsm-popup" id="company_modal_saved" style="display:none;">
        <div class="popup-overlay"></div>
        <div class="popup-content" style="max-width:550px;">
            <div class="popup-header"><h3 id="company_modal_title">Adauga firma</h3><button type="button" class="popup-close modal-close-btn">×</button></div>
            <div class="popup-body">
                <input type="hidden" id="edit_company_index" value="">
                <div class="form-row">
                    <div class="form-col"><label>CUI *</label><input type="text" id="company_cui_modal" placeholder="12345678"></div>
                    <div class="form-col" style="display:flex;align-items:flex-end;">
                        <small class="anaf-hint" style="color:#3b82f6;font-size:12px;font-weight:500;">🔍 Autocompletare automată din ANAF</small>
                    </div>
                </div>
                <div id="anaf_status_modal" style="display:none;padding:8px 12px;border-radius:6px;margin:10px 0;font-size:13px;"></div>
                <div class="form-row"><div class="form-col full"><label>Denumire *</label><input type="text" id="company_name_modal"></div></div>
                <div class="form-row">
                    <div class="form-col"><label>Nr. Reg. Com. *</label><input type="text" id="company_reg_modal" placeholder="J40/1234/2020"></div>
                    <div class="form-col"><label>Telefon *</label><input type="tel" id="company_phone_modal" placeholder="07xxxxxxxx"></div>
                </div>
                <div class="form-row"><div class="form-col full"><label>Email *</label><input type="email" id="company_email_modal" placeholder="contact@firma.ro"></div></div>
                <div style="border-top:1px solid #eee;margin:15px 0;padding-top:15px;"><strong>Adresa sediu:</strong></div>
                <div class="form-row"><div class="form-col full"><label>Adresa *</label><input type="text" id="company_address_modal"></div></div>
                <div class="form-row">
                    <div class="form-col"><label>Judet *</label>
                    <select id="company_county_modal">' . $counties_options . '</select>
                    </div>
                    <div class="form-col"><label>Localitate *</label><input type="text" id="company_city_modal"></div>
                </div>
            </div>
            <div class="popup-footer">
                <button type="button" class="btn-secondary modal-cancel-btn">Anuleaza</button>
                <button type="button" class="btn-primary" id="save_company_modal_btn">Salveaza</button>
            </div>
        </div>
    </div>
    ';
    
    // =========================================
    // Person popup modal for My Account
    // =========================================
    echo '
    <div class="webgsm-popup" id="person_modal_saved" style="display:none;">
        <div class="popup-overlay"></div>
        <div class="popup-content" style="max-width:550px;">
            <div class="popup-header"><h3 id="person_modal_title">Adauga persoana</h3><button type="button" class="popup-close modal-close-btn">×</button></div>
            <div class="popup-body">
                <input type="hidden" id="edit_person_index" value="">
                <div class="form-row">
                    <div class="form-col"><label>Nume complet *</label><input type="text" id="person_name_modal"></div>
                    <div class="form-col"><label>CNP (optional)</label><input type="text" id="person_cnp_modal" maxlength="13"></div>
                </div>
                <div class="form-row">
                    <div class="form-col"><label>Telefon *</label><input type="tel" id="person_phone_modal" placeholder="07xxxxxxxx"></div>
                    <div class="form-col"><label>Email *</label><input type="email" id="person_email_modal"></div>
                </div>
                <div style="border-top:1px solid #eee;margin:15px 0;padding-top:15px;"><strong>Adresa facturare:</strong></div>
                <div class="form-row"><div class="form-col full"><label>Adresa *</label><input type="text" id="person_address_modal"></div></div>
                <div class="form-row">
                    <div class="form-col"><label>Judet *</label>
                    <select id="person_county_modal">' . $counties_options . '</select>
                    </div>
                    <div class="form-col"><label>Localitate *</label><input type="text" id="person_city_modal"></div>
                </div>
                <div class="form-row"><div class="form-col"><label>Cod postal</label><input type="text" id="person_postcode_modal" maxlength="6"></div></div>
            </div>
            <div class="popup-footer">
                <button type="button" class="btn-secondary modal-cancel-btn">Anuleaza</button>
                <button type="button" class="btn-primary" id="save_person_modal_btn">Salveaza</button>
            </div>
        </div>
    </div>
    ';
    
}, 1);
*/
