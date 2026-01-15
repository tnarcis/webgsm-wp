/**
 * WebGSM Checkout Pro - JavaScript
 * Versiunea 5.0.0 - RESCRIS COMPLET
 * 
 * FIX-URI IMPLEMENTATE:
 * 1. DEBLOCARE BUTON - elimină required, injectează date forțat
 * 2. FĂRĂ POP-UP-URI AUTOMATE - popup doar la click pe buton
 * 3. PERSISTENȚĂ PJ - forțează tipul după salvare
 * 4. RE-INIT LA updated_checkout - pentru Martfury
 * 5. FOCUS/BACKDROP CLEANUP - UX îmbunătățit
 * 6. VALIDARE DOAR LA SUBMIT - nu întrerupe utilizatorul
 * 
 * @package WebGSM_Checkout_Pro
 */

(function($) {
    'use strict';
    
    // =========================================
    // VARIABILE GLOBALE
    // =========================================
    
    var WebGSM = {
        initialized: false,
        currentCustomerType: 'pf',
        debug: true
    };
    // Ultimul CUI căutat (pentru a evita duplicatele)
    var lastANAFcui = ''; 
    
    // =========================================
    // FUNCȚIE DE LOG
    // =========================================
    
    function log(message, data) {
        if (WebGSM.debug && window.console) {
            if (data !== undefined) {
                console.log('[WebGSM] ' + message, data);
            } else {
                console.log('[WebGSM] ' + message);
            }
        }
    }
    
    // =========================================
    // INIȚIALIZARE PRINCIPALĂ
    // =========================================
    
    function init() {
        log('========== INIT START v5.0.0 ==========');
        
        // Elimină required de pe toate inputurile (CRITIC)
        removeAllRequiredAttributes();
        
        // Mută payment methods
        movePaymentMethods();
        
        // Setează tipul inițial de client
        initCustomerType();
        
        // Populează din carduri la încărcare (fără popup!)
        silentInitFromCards();

        // Sincronizează shipping cu billing dacă "same as billing" e bifat (inițial)
        $('#same_as_billing').trigger('change');
        
        // Marchează ca inițializat
        WebGSM.initialized = true;
        
        log('========== INIT COMPLETE ==========');
    }
    
    // =========================================
    // ELIMINARE REQUIRED (CRITIC PENTRU DEBLOCARE)
    // =========================================
    
    function removeAllRequiredAttributes() {
        log('Eliminare atribute required...');
        
        // Lista completă de câmpuri
        var fields = [
            'billing_first_name', 'billing_last_name', 'billing_company',
            'billing_address_1', 'billing_address_2', 'billing_city',
            'billing_postcode', 'billing_country', 'billing_state',
            'billing_phone', 'billing_email',
            'billing_cui', 'billing_j', 'billing_cnp', 'billing_iban', 'billing_bank',
            'shipping_first_name', 'shipping_last_name',
            'shipping_address_1', 'shipping_city', 'shipping_state', 'shipping_postcode'
        ];
        
        fields.forEach(function(field) {
            var $el = $('#' + field);
            if ($el.length) {
                $el.removeAttr('required')
                   .removeAttr('aria-required')
                   .removeAttr('aria-invalid')
                   .removeClass('validate-required input-required');
            }
        });
        
        // Elimină și de pe wrapper-ele WooCommerce
        $('.woocommerce-billing-fields .validate-required').removeClass('validate-required');
        $('.woocommerce-shipping-fields .validate-required').removeClass('validate-required');
        $('form.checkout [required]').removeAttr('required');
        $('form.checkout [aria-required]').removeAttr('aria-required');
        
        // Elimină clasa de wrapper required
        $('.form-row').removeClass('validate-required');
        
        log('Required attributes eliminat complet');
    }
    
    // =========================================
    // MUTĂ PAYMENT METHODS
    // =========================================
    
    function movePaymentMethods() {
        var $payment = $('#payment');
        var $target = $('.webgsm-payment-methods');
        
        if ($payment.length && $target.length && !$target.find('#payment').length) {
            $payment.appendTo($target);
            log('Payment methods mutat');
        }
    }
    
    // =========================================
    // INIȚIALIZARE TIP CLIENT
    // =========================================
    
    function initCustomerType() {
        var $checked = $('input[name="billing_customer_type"]:checked');
        
        if ($checked.length) {
            WebGSM.currentCustomerType = $checked.val();
        } else {
            // Setează default PF și bifează-l
            WebGSM.currentCustomerType = 'pf';
            $('input[name="billing_customer_type"][value="pf"]').prop('checked', true);
        }
        
        log('Tip client inițial: ' + WebGSM.currentCustomerType);
        
        // Afișează secțiunea corectă
        toggleCustomerSections(WebGSM.currentCustomerType);
    }
    
    // =========================================
    // TOGGLE SECȚIUNI PF/PJ
    // =========================================
    
    function toggleCustomerSections(type) {
        log('Toggle secțiuni pentru: ' + type);
        
        if (type === 'pj') {
            $('#pf_section').hide();
            $('#pj_section').show();
        } else {
            $('#pf_section').show();
            $('#pj_section').hide();
        }
        
        WebGSM.currentCustomerType = type;
    }
    
    // =========================================
    // INIȚIALIZARE SILENȚIOASĂ DIN CARDURI (FĂRĂ POPUP!)
    // =========================================
    
    function silentInitFromCards() {
        log('Inițializare silențioasă din carduri...');
        
        // Doar injectează date, NU afișa popup-uri
        if (WebGSM.currentCustomerType === 'pf') {
            injectPersonDataSilent();
        } else {
            injectCompanyDataSilent();
        }
    }
    
    // =========================================
    // INJECTARE DATE PERSOANĂ (SILENT - FĂRĂ ALERT)
    // =========================================
    
    function injectPersonDataSilent() {
        var $selected = $('input[name="selected_person"]:checked');
        
        if (!$selected.length) {
            log('Niciun card persoană selectat - NU afișez popup');
            showInlineError('pf_section', 'Selectează sau adaugă o persoană fizică pentru facturare.');
            return false;
        }
        
        hideInlineError('pf_section');
        
        var data = extractCardData($selected);
        log('Date persoană extrase:', data);
        
        injectBillingData(data, 'pf');
        return true;
    }
    
    // =========================================
    // INJECTARE DATE FIRMĂ (SILENT - FĂRĂ ALERT)
    // =========================================
    
    function injectCompanyDataSilent() {
        var $selected = $('input[name="selected_company"]:checked');
        
        if (!$selected.length) {
            log('Niciun card firmă selectat - NU afișez popup');
            showInlineError('pj_section', 'Selectează sau adaugă o firmă pentru facturare.');
            return false;
        }
        
        hideInlineError('pj_section');
        
        var data = extractCardData($selected);
        log('Date firmă extrase:', data);
        
        injectBillingData(data, 'pj');
        return true;
    }

    // =========================================
    // INJECTARE DATE ADRESĂ (SILENT)
    // =========================================
    function injectShippingDataSilent() {
        var $selected = $('input[name="selected_address"]:checked');
        if (!$selected.length) {
            log('Niciun card adresă selectat - nu injectez shipping');
            return false;
        }

        var data = extractCardData($selected);
        log('Date adresă extrase:', data);

        var nameParts = (data.name || '').trim().split(' ');
        var firstName = nameParts[0] || '';
        var lastName = nameParts.slice(1).join(' ') || '';

        $('input[name="shipping_first_name"]').val(firstName);
        $('input[name="shipping_last_name"]').val(lastName);
        $('input[name="shipping_phone"]').val(data.phone || '');
        $('input[name="shipping_address_1"]').val(data.address || '');
        $('input[name="shipping_city"]').val(data.city || '');
        $('input[name="shipping_state"]').val(getStateCode(data.county || ''));
        $('input[name="shipping_postcode"]').val(data.postcode || '');
        $('input[name="shipping_country"]').val('RO');

        log('Shipping date injectate cu succes');
        $(document.body).trigger('update_checkout');
        return true;
    }
    
    // =========================================
    // EXTRAGE DATE DIN CARD (data-* attributes)
    // =========================================
    
    function extractCardData($card) {
        return {
            name: $card.data('name') || '',
            phone: normalizePhone($card.data('phone') || ''),
            email: $card.data('email') || '',
            cnp: $card.data('cnp') || '',
            address: $card.data('address') || '',
            city: $card.data('city') || '',
            county: $card.data('county') || '',
            postcode: $card.data('postcode') || '',
            company: $card.data('name') || '',
            cui: $card.data('cui') || '',
            reg: $card.data('reg') || '',
            iban: $card.data('iban') || '',
            bank: $card.data('bank') || '',
            // Contact person fields (for companies)
            contact_first: $card.data('contact-first') || '',
            contact_last: $card.data('contact-last') || ''
        };
    }
    
    // =========================================
    // INJECTARE ÎN HIDDEN INPUTS
    // =========================================
    
    function injectBillingData(data, type) {
        log('Injectare date în hidden inputs, tip: ' + type);
        
        // Split nume
        var nameParts = (data.name || '').trim().split(' ');
        var firstName = nameParts[0] || '';
        var lastName = nameParts.slice(1).join(' ') || '';

        // If we're injecting a company and contact person fields exist, prefer those for billing name
        if (type === 'pj' && (data.contact_first || data.contact_last)) {
            firstName = data.contact_first || firstName;
            lastName = data.contact_last || (lastName || 'SRL');
        }
        
        // POPULEAZĂ TOATE inputurile cu același name (rezolvă problema duplicatelor)
        $('input[name="billing_first_name"]').val(firstName);
        $('input[name="billing_last_name"]').val(lastName || (type === 'pj' ? 'SRL' : ''));
        $('input[name="billing_phone"]').val(data.phone);
        $('input[name="billing_email"]').val(data.email);
        $('input[name="billing_address_1"]').val(data.address);
        $('input[name="billing_city"]').val(data.city);
        $('input[name="billing_state"]').val(getStateCode(data.county));
        $('input[name="billing_postcode"]').val(data.postcode);
        $('input[name="billing_country"]').val('RO');
        
        // Pentru radio buttons - doar bifează, nu seta val()
        $('input[name="billing_customer_type"][value="' + type + '"]').prop('checked', true);
        // Setează și hidden input-ul separat dacă există
        $('input#billing_customer_type.input-hidden').val(type);
        
        if (type === 'pj') {
            $('input[name="billing_company"]').val(data.company || data.name);
            $('input[name="billing_cui"]').val(data.cui);
            $('input[name="billing_j"]').val(data.reg);
            $('input[name="billing_iban"]').val(data.iban);
            $('input[name="billing_bank"]').val(data.bank);
            $('input[name="billing_cnp"]').val('');
        } else {
            $('input[name="billing_company"]').val('');
            $('input[name="billing_cui"]').val('');
            $('input[name="billing_j"]').val('');
            $('input[name="billing_iban"]').val('');
            $('input[name="billing_bank"]').val('');
            $('input[name="billing_cnp"]').val(data.cnp);
        }
        
        log('Date injectate cu succes');
    }
    
    // =========================================
    // ERORI INLINE (NU POPUP!)
    // =========================================
    
    function showInlineError(sectionId, message) {
        var $section = $('#' + sectionId);
        var $error = $section.find('.webgsm-inline-error');
        
        if (!$error.length) {
            $error = $('<div class="webgsm-inline-error" style="background:#fff3e0;color:#e65100;padding:10px 15px;border-radius:4px;margin-bottom:15px;font-size:13px;border-left:3px solid #ff9800;"></div>');
            $section.find('.subsection-title').after($error);
        }
        
        $error.html('<strong>⚠</strong> ' + message).show();
    }
    
    function hideInlineError(sectionId) {
        $('#' + sectionId).find('.webgsm-inline-error').hide();
    }
    
    // =========================================
    // NORMALIZARE TELEFON
    // =========================================
    
    function normalizePhone(phone) {
        if (!phone) return '';
        return String(phone).replace(/[\s\-\.\(\)]/g, '');
    }
    
    // Convertește numele județului în cod WooCommerce
    function getStateCode(county) {
        if (!county) return '';
        
        // Normalizează diacriticele (vechi și noi)
        var normalized = county.toLowerCase().trim()
            .replace(/ţ/g, 't').replace(/ț/g, 't')
            .replace(/ş/g, 's').replace(/ș/g, 's')
            .replace(/ă/g, 'a').replace(/â/g, 'a').replace(/î/g, 'i');
        
        var map = {
            'alba': 'AB', 
            'arad': 'AR', 
            'arges': 'AG',
            'bacau': 'BC', 
            'bihor': 'BH', 
            'bistrita-nasaud': 'BN', 'bistrita nasaud': 'BN', 'bistrita': 'BN',
            'botosani': 'BT',
            'braila': 'BR',
            'brasov': 'BV',
            'bucuresti': 'B', 'bucharest': 'B',
            'buzau': 'BZ',
            'calarasi': 'CL',
            'caras-severin': 'CS', 'caras severin': 'CS',
            'cluj': 'CJ',
            'constanta': 'CT',
            'covasna': 'CV',
            'dambovita': 'DB',
            'dolj': 'DJ',
            'galati': 'GL',
            'giurgiu': 'GR',
            'gorj': 'GJ',
            'harghita': 'HR',
            'hunedoara': 'HD',
            'ialomita': 'IL',
            'iasi': 'IS',
            'ilfov': 'IF',
            'maramures': 'MM',
            'mehedinti': 'MH',
            'mures': 'MS',
            'neamt': 'NT',
            'olt': 'OT',
            'prahova': 'PH',
            'salaj': 'SJ',
            'satu mare': 'SM', 'satu-mare': 'SM',
            'sibiu': 'SB',
            'suceava': 'SV',
            'teleorman': 'TR',
            'timis': 'TM',
            'tulcea': 'TL',
            'valcea': 'VL',
            'vaslui': 'VS',
            'vrancea': 'VN'
        };
        
        // Dacă e deja cod (2 litere), returnează-l
        if (normalized.length === 2 || (normalized.length === 1 && normalized === 'b')) {
            return county.toUpperCase();
        }
        
        // Caută în map
        if (map[normalized]) {
            return map[normalized];
        }
        
        // Caută parțial
        for (var key in map) {
            if (normalized.indexOf(key) !== -1 || key.indexOf(normalized) !== -1) {
                return map[key];
            }
        }
        
        return county;
    }
    
    // =========================================
    // VALIDARE VIZUALĂ - câmpuri roșii
    // =========================================
    
    function showFieldError(selector, message) {
        var $field = $(selector);
        $field.addClass('error');
        
        // Adaugă mesaj dacă nu există
        var $error = $field.siblings('.field-error');
        if (!$error.length) {
            $field.after('<div class="field-error">' + message + '</div>');
        } else {
            $error.text(message);
        }
    }
    
    function clearFieldErrors(container) {
        $(container).find('.error').removeClass('error');
        $(container).find('.field-error').remove();
    }
    
    // =========================================
    // VALIDARE LA SUBMIT (DOAR AICI!)
    // =========================================
    
    function validateBeforeSubmit() {
        log('========== VALIDARE LA SUBMIT ==========');
        
        var type = WebGSM.currentCustomerType;
        var errors = [];
        
        // Elimină required înainte de validare
        removeAllRequiredAttributes();
        
        if (type === 'pf') {
            var $person = $('input[name="selected_person"]:checked');
            
            if (!$person.length) {
                errors.push('Selectează o persoană fizică pentru facturare.');
            } else {
                var pData = extractCardData($person);
                
                if (!pData.name) errors.push('Numele este obligatoriu.');
                if (!pData.phone) errors.push('Telefonul este obligatoriu.');
                if (!pData.email) errors.push('Email-ul este obligatoriu.');
                if (!pData.address) errors.push('Adresa este obligatorie.');
                
                // Injectează datele
                injectBillingData(pData, 'pf');
            }
        } else {
            var $company = $('input[name="selected_company"]:checked');
            
            if (!$company.length) {
                errors.push('Selectează o firmă pentru facturare.');
            } else {
                var cData = extractCardData($company);
                
                if (!cData.name) errors.push('Denumirea firmei este obligatorie.');
                if (!cData.cui) errors.push('CUI este obligatoriu.');
                if (!cData.phone) errors.push('Telefonul firmei este obligatoriu.');
                if (!cData.email) errors.push('Email-ul firmei este obligatoriu.');
                
                // Injectează datele
                injectBillingData(cData, 'pj');
            }
        }
        
        // Log valori finale
        log('=== VALORI FINALE HIDDEN INPUTS ===');
        log('billing_customer_type: ' + type);
        log('billing_first_name: ' + $('#billing_first_name').val());
        log('billing_phone: ' + $('#billing_phone').val());
        log('billing_email: ' + $('#billing_email').val());
        log('billing_company: ' + $('#billing_company').val());
        log('billing_cui: ' + $('#billing_cui').val());
        
        if (errors.length > 0) {
            log('ERORI GĂSITE:', errors);
            alert('Te rugăm să corectezi următoarele:\n\n• ' + errors.join('\n• '));
            return false;
        }
        
        log('VALIDARE OK - PERMITE SUBMIT');
        return true;
    }
    
    // =========================================
    // POPUP MANAGEMENT (FĂRĂ AUTO-OPEN!)
    // =========================================
    
    function openPopup(popupId) {
        log('Deschide popup: ' + popupId);
        
        var $popup = $('#' + popupId);
        
        // Curăță orice backdrop vechi
        cleanupBackdrops();
        
        // Afișează popup
        $popup.addClass('active');
        $('body').addClass('webgsm-popup-open').css('overflow', 'hidden');
        
        // Focus pe primul input
        setTimeout(function() {
            $popup.find('input:visible:first').focus();
        }, 100);
    }
    
    function closePopup() {
        log('Închide popup');
        
        $('.webgsm-popup').removeClass('active');
        $('body').removeClass('webgsm-popup-open').css('overflow', '');
        
        // Curăță orice backdrop rămas
        cleanupBackdrops();
        
        // Resetează formularele din popup
        clearPopupForms();
    }
    
    function cleanupBackdrops() {
        // Elimină orice backdrop/overlay rămas
        $('.webgsm-popup:not(.active) .popup-overlay').hide();
        $('body').css('overflow', '');
        
        // Elimină clase rămase
        if (!$('.webgsm-popup.active').length) {
            $('body').removeClass('webgsm-popup-open');
        }
    }
    
    function clearPopupForms() {
        // Curăță câmpurile din popup-uri
        $('#person_popup input').val('');
        $('#company_popup input').val('');
        $('#address_popup input').val('');
        $('#anaf_status').hide();
    }
    
    // =========================================
    // SALVARE PERSOANĂ (AJAX)
    // =========================================
    
    function savePerson() {
        log('Salvare persoană...');
        
        // Curăță erorile anterioare
        clearFieldErrors('#person_popup');
        
        var data = {
            action: 'webgsm_save_person',
            nonce: webgsm_checkout.nonce,
            name: $('#person_name').val().trim(),
            cnp: $('#person_cnp').val().trim(),
            phone: normalizePhone($('#person_phone').val()),
            email: $('#person_email').val().trim(),
            address: $('#person_address').val().trim(),
            county: $('#person_county').val(), // Acum e select
            city: $('#person_city').val().trim(),
            postcode: $('#person_postcode').val().trim()
        };
        
        // Validare locală cu evidențiere vizuală
        var hasError = false;
        
        if (!data.name || data.name.length < 3) {
            showFieldError('#person_name', 'Minim 3 caractere');
            hasError = true;
        }
        if (!data.phone) {
            showFieldError('#person_phone', 'Obligatoriu');
            hasError = true;
        }
        if (!data.email) {
            showFieldError('#person_email', 'Obligatoriu');
            hasError = true;
        }
        if (!data.address) {
            showFieldError('#person_address', 'Obligatoriu');
            hasError = true;
        }
        if (!data.county) {
            showFieldError('#person_county', 'Selectează județul');
            hasError = true;
        }
        if (!data.city) {
            showFieldError('#person_city', 'Obligatoriu');
            hasError = true;
        }
        
        if (hasError) {
            return;
        }
        
        log('Trimit AJAX save_person', data);
        
        $.post(webgsm_checkout.ajax_url, data, function(response) {
            log('Răspuns save_person:', response);
            
            if (response.success) {
                closePopup();
                
                if (response.data.saved_to_account) {
                    // Forțează PF înainte de reload
                    $('input[name="billing_customer_type"][value="pf"]').prop('checked', true);
                    location.reload();
                } else {
                    // Guest - injectează direct
                    injectBillingData(response.data.person, 'pf');
                    hideInlineError('pf_section');
                    
                    // Trigger update checkout
                    $(document.body).trigger('update_checkout');
                }
            } else {
                alert(response.data || 'Eroare la salvare.');
            }
        }).fail(function() {
            alert('Eroare de conexiune.');
        });
    }
    
    // =========================================
    // SALVARE FIRMĂ (AJAX) - FIX PERSISTENȚĂ PJ
    // =========================================
    
    function saveCompany() {
        log('Salvare firmă...');
        
        // Curăță erorile anterioare
        clearFieldErrors('#company_popup');
        
        var data = {
            action: 'webgsm_save_company',
            nonce: webgsm_checkout.nonce,
            // contact_first/contact_last removed
            phone: normalizePhone($('#company_phone').val()),
            email: (($('#company_email').val() || '').trim()),
            address: (($('#company_address').val() || '').trim()),
            county: ($('#company_county').val() || ''), // Acum e select
            city: (($('#company_city').val() || '').trim()),
            iban: (($('#company_iban').val() || '').trim()),
            bank: (($('#company_bank').val() || '').trim())
        };
        
        // Validare locală cu evidențiere vizuală
        var hasError = false;
        
        // contact_first/contact_last validation removed
        if (!data.name || data.name.length < 3) {
            showFieldError('#company_name', 'Minim 3 caractere');
            hasError = true;
        }
        if (!data.cui) {
            showFieldError('#company_cui', 'Obligatoriu');
            hasError = true;
        }
        if (!data.reg) {
            showFieldError('#company_reg', 'Obligatoriu');
            hasError = true;
        }
        if (!data.phone) {
            showFieldError('#company_phone', 'Obligatoriu');
            hasError = true;
        }
        if (!data.email) {
            showFieldError('#company_email', 'Obligatoriu');
            hasError = true;
        }
        if (!data.address) {
            showFieldError('#company_address', 'Obligatoriu');
            hasError = true;
        }
        if (!data.county) {
            showFieldError('#company_county', 'Selectează județul');
            hasError = true;
        }
        if (!data.city) {
            showFieldError('#company_city', 'Obligatoriu');
            hasError = true;
        }
        
        if (hasError) {
            return;
        }
        
        log('Trimit AJAX save_company', data);
        
        $.post(webgsm_checkout.ajax_url, data, function(response) {
            log('Răspuns save_company:', response);
            
            if (response.success) {
                closePopup();
                
                if (response.data.saved_to_account) {
                    // FIX PERSISTENȚĂ PJ: Forțează tipul PJ ÎNAINTE de reload
                    log('FORȚARE TIP PJ înainte de reload');
                    
                    // Setează în hidden input
                    $('input[name="billing_customer_type"][value="pj"]').prop('checked', true);
                    
                    // Salvează în sessionStorage pentru a persista după reload
                    if (window.sessionStorage) {
                        sessionStorage.setItem('webgsm_force_pj', 'yes');
                    }
                    
                    location.reload();
                } else {
                    // Guest - injectează direct
                    var companyData = {
                        contact_first: data.contact_first,
                        contact_last: data.contact_last,
                        name: data.name,
                        phone: data.phone,
                        email: data.email,
                        address: data.address,
                        city: data.city,
                        county: data.county,
                        cui: data.cui,
                        reg: data.reg,
                        iban: data.iban,
                        bank: data.bank
                    };
                    
                    injectBillingData(companyData, 'pj');
                    hideInlineError('pj_section');
                    
                    // Trigger update checkout
                    $(document.body).trigger('update_checkout');
                }
            } else {
                alert(response.data || 'Eroare la salvare.');
            }
        }).fail(function() {
            alert('Eroare de conexiune.');
        });
    }
    
    // =========================================
    // SALVARE ADRESĂ (AJAX)
    // =========================================
    
    function saveAddress() {
        log('Salvare adresă...');
        
        var data = {
            action: 'webgsm_save_address',
            nonce: webgsm_checkout.nonce,
            label: $('#addr_label').val().trim(),
            name: $('#addr_name').val().trim(),
            phone: normalizePhone($('#addr_phone').val()),
            address: $('#addr_address').val().trim(),
            city: $('#addr_city').val().trim(),
            county: $('#addr_county').val().trim(),
            postcode: $('#addr_postcode').val().trim()
        };
        
        if (!data.name || !data.phone || !data.address) {
            alert('Completează câmpurile obligatorii.');
            return;
        }
        
        $.post(webgsm_checkout.ajax_url, data, function(response) {
            if (response.success) {
                closePopup();
                location.reload();
            } else {
                alert(response.data || 'Eroare.');
            }
        });
    }
    
    // =========================================
    // ȘTERGERE CARDURI
    // =========================================
    
    function deleteCard(type, index) {
        if (!confirm('Ștergi această înregistrare?')) return;
        
        var action = 'webgsm_delete_' + type;
        
        $.post(webgsm_checkout.ajax_url, {
            action: action,
            nonce: webgsm_checkout.nonce,
            index: index
        }, function(response) {
            if (response.success) {
                log('Delete response success for ' + type + ' index ' + index);

                // If deleting an address from the checkout addresses list, update the UI immediately
                if (type === 'address') {
                    var $list = $('.webgsm-addresses-list');
                    var $item = $list.find('.address-item').filter(function() {
                        return $(this).find('input[name="selected_address"]').val() == index;
                    });

                    if ($item.length) {
                        var wasChecked = $item.find('input[name="selected_address"]').is(':checked');
                        $item.remove();

                        // Reindex remaining items so their data-index / values match server-side
                        $list.find('.address-item').each(function(i) {
                            var $this = $(this);
                            $this.find('input[name="selected_address"]').val(i);
                            $this.find('input[name="selected_address"]').attr('value', i);
                            $this.find('.delete-address').data('index', i).attr('data-index', i);
                        });

                        // If the deleted item was selected, select the first available address and trigger change
                        if (wasChecked) {
                            var $first = $list.find('input[name="selected_address"]').first();
                            if ($first.length) {
                                $first.prop('checked', true).trigger('change');
                            }
                        }

                        // If the list is now empty, hide it and show add button (server-side markup handles this on reload)
                        if ($list.find('.address-item').length === 0) {
                            $list.hide();
                        }

                        return;
                    }
                }

                // Fallback: reload the page for other types or unexpected cases
                location.reload();
            }
        });
    }
    
    function setDefaultAddress(addressIndex) {
        $.post(webgsm_checkout.ajax_url, {
            action: 'webgsm_set_default_address',
            nonce: webgsm_checkout.nonce,
            address_index: addressIndex
        }, function(response) {
            if (response.success) {
                // Elimină clasa active de la toate bullet-urile
                $('.webgsm-default-bullet').removeClass('active');
                // Adaugă clasa active la bullet-ul selectat
                $('.webgsm-default-bullet[data-address-index="' + addressIndex + '"]').addClass('active');
                log('Default address set to index: ' + addressIndex);
            } else {
                alert('Eroare la setarea adresei implicite');
            }
        });
    }
    
    // =========================================
    // ANAF SEARCH
    // =========================================
    
    function searchANAF() {
        var $btn = $('#search_anaf_btn');
        var $status = $('#anaf_status');
        var cui = $('#company_cui').val().replace(/[^0-9]/g, '');
        
        if (!cui || cui.length < 2) {
            $status.css({background: '#ffebee', color: '#c62828'}).text('Introdu un CUI valid').show();
            return;
        }
        
        $btn.addClass('loading').prop('disabled', true);
        // Mută statusul în partea de sus a popup-ului de firmă pentru vizibilitate
        $status.prependTo('#company_popup .popup-body');
        $status.css({display: 'block', background: '#fff3e0', color: '#e65100'}).text('Se caută...').show();
        
        $.post(webgsm_checkout.ajax_url, {
            action: 'webgsm_search_anaf',
            nonce: webgsm_checkout.nonce,
            cui: cui
        }, function(response) {
            $btn.removeClass('loading').prop('disabled', false);
            
            if (response.success) {
                var d = response.data;
                $('#company_name').val(d.name);
                $('#company_cui').val(d.cui);
                $('#company_reg').val(d.j);
                $('#company_address').val(d.address);
                // Setează select-ul cu codul județului
                var countyCode = getStateCode(d.county);
                $('#company_county').val(countyCode);
                $('#company_city').val(d.city);
                
                $status.css({background: '#e8f5e9', color: '#2e7d32'})
                       .html('✓ ' + d.name + (d.is_tva ? ' (Plătitor TVA)' : ''))
                       .show();
            } else {
                // Permite retry (sterge lastANAFcui astfel încât următoarea intrare să re-trigger-eze)
                lastANAFcui = '';
                $status.css({background: '#ffebee', color: '#c62828'}).text(response.data || 'Negăsit').show();
            }
        }).fail(function() {
            $btn.removeClass('loading').prop('disabled', false);
            // Permite retry
            lastANAFcui = '';
            $status.css({background: '#ffebee', color: '#c62828'}).text('Eroare conexiune ANAF').show();
        });
    }
    
    // =========================================
    // CART FUNCTIONS
    // =========================================
    
    function updateCartQuantity($select) {
        $.post(webgsm_checkout.ajax_url, {
            action: 'webgsm_update_cart_item',
            key: $select.data('key'),
            qty: $select.val()
        }, function(response) {
            if (response.success) location.reload();
        });
    }
    
    function removeCartItem(key) {
        $.post(webgsm_checkout.ajax_url, {
            action: 'webgsm_remove_cart_item',
            key: key
        }, function(response) {
            if (response.success) {
                if (response.data.cart_count === 0) {
                    location.href = '/cos';
                } else {
                    location.reload();
                }
            }
        });
    }
    
    function applyCoupon() {
        var coupon = $('#webgsm_coupon').val().trim();
        if (!coupon) {
            showCouponMessage('Te rugam sa introduci un cod cupon.', 'error');
            return;
        }
        
        var $btn = $('#apply_coupon_btn');
        $btn.prop('disabled', true).text('Se aplica...');
        
        $.ajax({
            url: webgsm_checkout.ajax_url,
            type: 'POST',
            data: {
                action: 'webgsm_apply_coupon',
                coupon_code: coupon
            },
            success: function(response) {
                if (response.success) {
                    showCouponMessage('Cupon aplicat cu succes! Reducerea a fost aplicata.', 'success');
                    $('#webgsm_coupon').val('');
                    // Reincarca pagina pentru a actualiza totalul
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    var errorMsg = response.data && response.data.message ? response.data.message : 'Cod cupon invalid sau expirat.';
                    showCouponMessage(errorMsg, 'error');
                    $btn.prop('disabled', false).text('Aplica');
                }
            },
            error: function() {
                showCouponMessage('Eroare la aplicarea cuponului. Te rugam sa incerci din nou.', 'error');
                $btn.prop('disabled', false).text('Aplica');
            }
        });
    }
    
    function showCouponMessage(message, type) {
        // Elimina mesajele existente
        $('.webgsm-coupon-message').remove();
        
        var bgColor = type === 'success' ? '#f0fdf4' : '#fef2f2';
        var borderColor = type === 'success' ? '#bbf7d0' : '#fecaca';
        var textColor = type === 'success' ? '#166534' : '#991b1b';
        
        var $message = $('<div class="webgsm-coupon-message" style="margin-top: 8px; padding: 10px 12px; background: ' + bgColor + '; border: 1px solid ' + borderColor + '; border-radius: 6px; color: ' + textColor + '; font-size: 13px;">' + message + '</div>');
        $('.webgsm-coupon-row').after($message);
        
        // Elimina mesajul dupa 5 secunde
        setTimeout(function() {
            $message.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    // =========================================
    // CHECK FORCED PJ (DUPĂ RELOAD)
    // =========================================
    
    function checkForcedPJ() {
        if (window.sessionStorage && sessionStorage.getItem('webgsm_force_pj') === 'yes') {
            log('FORȚARE PJ detectată din sessionStorage');
            
            // Setează PJ
            $('input[name="billing_customer_type"][value="pj"]').prop('checked', true);
            WebGSM.currentCustomerType = 'pj';
            toggleCustomerSections('pj');
            
            // Curăță flag-ul
            sessionStorage.removeItem('webgsm_force_pj');
            
            // Selectează primul card de firmă
            var $firstCompany = $('input[name="selected_company"]:first');
            if ($firstCompany.length && !$firstCompany.prop('checked')) {
                $firstCompany.prop('checked', true);
            }
            
            // Injectează datele
            injectCompanyDataSilent();
        }
    }
    
    // =========================================
    // BIND EVENIMENTE
    // =========================================
    
    function bindEvents() {
        log('Bind evenimente...');
        
        // ANAF: căutare automată la introducerea CUI (debounce) + feedback + blur fallback
        var anafTimer = null;
        $(document).on('input', '#company_cui', function() {
            clearTimeout(anafTimer);
            var $status = $('#anaf_status');
            var $el = $(this);
            var val = $el.val().replace(/[^0-9]/g, '');
            if (!val || val.length < 2) { $status.hide(); return; }
            // afișează feedback căutare pending
            $status.css({background: '#fff3e0', color: '#e65100'}).text('CUI detectat — căutare automată...').show();
            anafTimer = setTimeout(function() {
                if (val && val !== lastANAFcui) {
                    lastANAFcui = val;
                    searchANAF();
                }
            }, 700);
        });
        // fallback: la blur, dacă nu s-a căutat încă, declanșează imediat
        $(document).on('blur', '#company_cui', function() {
            var val = $(this).val().replace(/[^0-9]/g, '');
            if (val && val.length >= 2 && val !== lastANAFcui) {
                clearTimeout(anafTimer);
                lastANAFcui = val;
                searchANAF();
            }
        });
        
        // ==========================================
        // SUBMIT - VALIDARE DOAR AICI!
        // ==========================================
        
        // Metodă 1: WooCommerce event
        $(document.body).on('checkout_place_order', function() {
            log('========== checkout_place_order EVENT ==========');
            return validateBeforeSubmit();
        });
        
        // Metodă 2: Click pe buton - FORȚEAZĂ SUBMIT MANUAL
        $(document).on('click', '#place_order, #mobile_place_order', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            log('========== CLICK PLACE ORDER ==========');
            
            // Elimină required
            removeAllRequiredAttributes();
            
            // Validează
            var isValid = validateBeforeSubmit();
            
            if (!isValid) {
                return false;
            }
            
            // FORȚEAZĂ SUBMIT PE FORM (butonul e în afara formularului)
            log('TRIMITE FORM MANUAL - form.checkout.submit()');
            
            var $form = $('form.checkout');
            
            // Adaugă nonce în form dacă nu există
            if (!$form.find('input[name="woocommerce-process-checkout-nonce"]').length) {
                var nonceVal = $('input[name="woocommerce-process-checkout-nonce"]').val();
                if (nonceVal) {
                    $form.append('<input type="hidden" name="woocommerce-process-checkout-nonce" value="' + nonceVal + '">');
                    log('Nonce adăugat în form: ' + nonceVal);
                }
            }
            
            // Adaugă payment method în form dacă nu există
            if (!$form.find('input[name="payment_method"]').length) {
                var paymentMethod = $('input[name="payment_method"]:checked').val();
                if (paymentMethod) {
                    $form.append('<input type="hidden" name="payment_method" value="' + paymentMethod + '">');
                    log('Payment method adăugat în form: ' + paymentMethod);
                }
            }
            
            // Adaugă _wp_http_referer dacă nu există
            if (!$form.find('input[name="_wp_http_referer"]').length) {
                var referer = $('input[name="_wp_http_referer"]').val();
                if (referer) {
                    $form.append('<input type="hidden" name="_wp_http_referer" value="' + referer + '">');
                }
            }
            
            $form.submit();
            
            return false;
        });
        
        // Metodă 3: Form submit
        $('form.checkout').on('submit', function() {
            log('========== FORM SUBMIT ==========');
            // Debug log: show values being submitted
            console.log('[WebGSM] Submit values:', {
                ship_to_different_address: $('#ship_to_different_address').val(),
                same_as_billing_checked: $('#same_as_billing').is(':checked'),
                shipping_first_name: $('input[name="shipping_first_name"]').val(),
                shipping_address_1: $('input[name="shipping_address_1"]').val(),
                shipping_city: $('input[name="shipping_city"]').val(),
                shipping_state: $('input[name="shipping_state"]').val(),
                shipping_postcode: $('input[name="shipping_postcode"]').val()
            });

            // Set the ship_to_different_address flag so WooCommerce knows to use shipping fields
            if (!$('#same_as_billing').is(':checked')) {
                $('#ship_to_different_address').val('1');

                // Copy visible shipping inputs (which lack name attributes) into the hidden named inputs
                // so values typed by the user are included in the POST request.
                $('input[name="shipping_first_name"]').val($('#shipping_first_name').val() || $('input[name="shipping_first_name"]').val());
                $('input[name="shipping_last_name"]').val($('#shipping_last_name').val() || $('input[name="shipping_last_name"]').val());
                $('input[name="shipping_phone"]').val($('#shipping_phone').val() || $('input[name="shipping_phone"]').val());
                $('input[name="shipping_address_1"]').val($('#shipping_address_1').val() || $('input[name="shipping_address_1"]').val());
                $('input[name="shipping_city"]').val($('#shipping_city').val() || $('input[name="shipping_city"]').val());
                $('input[name="shipping_state"]').val($('#shipping_state').val() || $('input[name="shipping_state"]').val());
                $('input[name="shipping_postcode"]').val($('#shipping_postcode').val() || $('input[name="shipping_postcode"]').val());
                $('input[name="shipping_country"]').val($('#shipping_country').val() || $('input[name="shipping_country"]').val());

                // If a saved address was selected, inject it too (injectShippingDataSilent will overwrite where appropriate)
                injectShippingDataSilent();
            } else {
                $('#ship_to_different_address').val('0');
            }
            removeAllRequiredAttributes();
        });
        
        // ==========================================
        // SCHIMBARE TIP CLIENT
        // ==========================================
        
        $(document).on('change', 'input[name="billing_customer_type"]', function() {
            var type = $(this).val();
            log('Schimbare tip client: ' + type);
            
            toggleCustomerSections(type);
            
            // Re-injectează datele (fără popup!)
            if (type === 'pf') {
                injectPersonDataSilent();
            } else {
                injectCompanyDataSilent();
                // Pentru PJ, adresa de livrare trebuie să fie aceeași cu adresa firmei
                $('#same_as_billing').prop('checked', true).trigger('change');
            }
            
            $(document.body).trigger('update_checkout');
        });
        
        // ==========================================
        // SELECTARE CARDURI
        // ==========================================
        
        $(document).on('change', 'input[name="selected_person"]', function() {
            log('Card persoană selectat');
            injectPersonDataSilent();
        });
        
        $(document).on('change', 'input[name="selected_company"]', function() {
            log('Card firmă selectat');
            injectCompanyDataSilent();
            // Pentru PJ, adresa de livrare trebuie să fie aceeași cu adresa firmei
            $('#same_as_billing').prop('checked', true).trigger('change');
        });
        
        $(document).on('change', 'input[name="selected_address"]', function() {
            log('Card adresă selectat');
            // Mark that shipping is different
            $('#ship_to_different_address').val('1');
            injectShippingDataSilent();
        });
        
        // Same as billing
        $(document).on('change', '#same_as_billing', function() {
            if ($(this).is(':checked')) {
                $('#shipping_address_fields').hide();
                // Clear the 'different shipping' flag and copy billing values into shipping to keep them in sync
                $('#ship_to_different_address').val('0');
                $('input[name="shipping_first_name"]').val($('input[name="billing_first_name"]').val() || '');
                $('input[name="shipping_last_name"]').val($('input[name="billing_last_name"]').val() || '');
                $('input[name="shipping_address_1"]').val($('input[name="billing_address_1"]').val() || '');
                $('input[name="shipping_city"]').val($('input[name="billing_city"]').val() || '');
                $('input[name="shipping_state"]').val($('input[name="billing_state"]').val() || '');
                $('input[name="shipping_postcode"]').val($('input[name="billing_postcode"]').val() || '');
                $(document.body).trigger('update_checkout');
            } else {
                $('#shipping_address_fields').show();
                // Mark that shipping is different and, if user has selected a saved address, inject it into the shipping fields
                $('#ship_to_different_address').val('1');
                injectShippingDataSilent();
            }
        });
        
        // ==========================================
        // POPUP-URI - DOAR LA CLICK PE BUTON!
        // ==========================================
        
        $(document).on('click', '#add_person_btn', function(e) {
            e.preventDefault();
            openPopup('person_popup');
        });
        
        $(document).on('click', '#add_company_btn', function(e) {
            e.preventDefault();
            openPopup('company_popup');
        });
        
        $(document).on('click', '#add_address_btn', function(e) {
            e.preventDefault();
            openPopup('address_popup');
        });
        
        // Închidere popup
        $(document).on('click', '.popup-overlay, .popup-close, .popup-cancel', function(e) {
            e.preventDefault();
            closePopup();
        });
        
        // ESC pentru închidere
        $(document).on('keyup', function(e) {
            if (e.key === 'Escape' && $('.webgsm-popup.active').length) {
                closePopup();
            }
        });
        
        // ==========================================
        // SALVARE DIN POPUP-URI
        // ==========================================
        
        $(document).on('click', '#save_person_btn', function(e) {
            e.preventDefault();
            savePerson();
        });
        
        $(document).on('click', '#save_company_btn', function(e) {
            e.preventDefault();
            saveCompany();
        });
        
        $(document).on('click', '#save_address_btn', function(e) {
            e.preventDefault();
            saveAddress();
        });
        
        // ==========================================
        // ȘTERGERE CARDURI
        // ==========================================
        
        $(document).on('click', '.delete-person', function(e) {
            e.preventDefault();
            e.stopPropagation();
            deleteCard('person', $(this).data('index'));
        });
        
        $(document).on('click', '.delete-company', function(e) {
            e.preventDefault();
            e.stopPropagation();
            deleteCard('company', $(this).data('index'));
        });
        
        $(document).on('click', '.delete-address', function(e) {
            e.preventDefault();
            e.stopPropagation();
            deleteCard('address', $(this).data('index'));
        });
        
        // ==========================================
        // ANAF - buton eliminat, căutare automată la introducerea CUI
        // ==========================================

        
        // ==========================================
        // CART
        // ==========================================
        
        $(document).on('change', '.qty-select', function() {
            updateCartQuantity($(this));
        });
        
        $(document).on('click', '.remove-item', function(e) {
            e.preventDefault();
            removeCartItem($(this).data('key'));
        });
        
        $(document).on('click', '#apply_coupon_btn', function(e) {
            e.preventDefault();
            applyCoupon();
        });
        
        // ==========================================
        // WOOCOMMERCE / MARTFURY EVENTS - RE-INIT
        // ==========================================
        
        $(document.body).on('updated_checkout', function() {
            log('========== updated_checkout - RE-INIT ==========');
            
            // Re-aplică toate setările
            removeAllRequiredAttributes();
            movePaymentMethods();
            
            // Re-injectează datele
            if (WebGSM.currentCustomerType === 'pf') {
                injectPersonDataSilent();
            } else {
                injectCompanyDataSilent();
            }
        });
        
        $(document.body).on('init_checkout', function() {
            log('init_checkout event');
            removeAllRequiredAttributes();
        });
        
        log('Bind evenimente complet');
    }
    
    // =========================================
    // DOCUMENT READY
    // =========================================
    
    $(document).ready(function() {
        log('Document ready');
        
        // Verifică dacă trebuie forțat PJ (după reload)
        checkForcedPJ();
        
        // Inițializare
        init();
        
        // Bind evenimente
        bindEvents();
    });
    
    // =========================================
    // WINDOW LOAD (BACKUP)
    // =========================================
    
    $(window).on('load', function() {
        log('Window load - cleanup final');
        removeAllRequiredAttributes();
        cleanupBackdrops();
    });
    
})(jQuery);
