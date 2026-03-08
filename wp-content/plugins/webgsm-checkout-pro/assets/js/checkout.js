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
        
        // Restructurează DOM-ul: form.checkout wrappează tot layout-ul
        integrateFormWithLayout();
        // Mută payment/shipping din WC-original în containerele vizuale (tot inside form)
        movePaymentMethods();
        moveShippingSection();
        replacePacketaLogosForCarriers();
        addEmojiToMethods();
        togglePacketaWidgetVisibility();
        ensurePickupPointInfoContainer();
        updatePickupPointDisplay();

        // La Box selectat inițial, widget-ul Packeta poate popula inputurile asincron – re-check după delay
        var chosenInit = $('input[name^="shipping_method"]:checked').val() || '';
        if (isPacketaPickupMethod(chosenInit)) {
            [400, 800, 1200].forEach(function(delay) {
                setTimeout(function() {
                    if ($('input[name^="shipping_method"]:checked').val() === chosenInit) {
                        updatePickupPointDisplay();
                    }
                }, delay);
            });
        }
        
        // Setează tipul inițial de client
        initCustomerType();
        
        // Populează din carduri la încărcare (fără popup!)
        silentInitFromCards();
        // Pentru utilizatori noi: afișează datele din cont (pre-populate din PHP) când nu au persoane salvate
        showAccountBillingIfEmpty();

        // Sincronizează shipping cu billing dacă "same as billing" e bifat (inițial)
        $('#same_as_billing').trigger('change');
        
        // Marchează ca inițializat
        WebGSM.initialized = true;

        // #region agent log
        (function(){
            var sc=$('#webgsm-shipping-container')[0];
            var pm=$('.webgsm-payment-methods')[0];
            var sr=$('.webgsm-native-shipping-sr')[0];
            var pay=$('#payment')[0];
            var form=$('form.checkout')[0];
            function vis(el){if(!el)return'NOT_IN_DOM';var s=window.getComputedStyle(el);return{display:s.display,visibility:s.visibility,opacity:s.opacity,h:el.offsetHeight,w:el.offsetWidth,overflow:s.overflow};}
            var shippingRadioHTML='';
            if(sr){var radios=$(sr).find('input[name^="shipping_method"]');shippingRadioHTML=radios.length+'_radios';radios.each(function(){shippingRadioHTML+=' | '+this.value+'(checked:'+this.checked+')';});}
            var payRadioHTML='';
            if(pay){var pradios=$(pay).find('input[name="payment_method"]');payRadioHTML=pradios.length+'_radios';pradios.each(function(){payRadioHTML+=' | '+this.value+'(checked:'+this.checked+')';});}
            fetch('http://127.0.0.1:7737/ingest/d4671e02-eb27-4a13-9c43-eddfef593936',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'d841f7'},body:JSON.stringify({sessionId:'d841f7',location:'checkout.js:initComplete',message:'Visual state after init',data:{shippingContainer:vis(sc),paymentContainer:vis(pm),shippingSR:vis(sr),paymentDiv:vis(pay),formEl:vis(form),shippingRadios:shippingRadioHTML,paymentRadios:payRadioHTML,formClasses:form?form.className:'NONE',wcOrigHidden:$('.webgsm-wc-original').css('display')},timestamp:Date.now(),hypothesisId:'VISUAL'})}).catch(function(){});
        })();
        // #endregion

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
        // Exclude terms checkbox – termenii trebuie acceptați obligatoriu
        $('form.checkout [required]').not('#terms, input[name="terms"]').removeAttr('required');
        $('form.checkout [aria-required]').not('#terms, input[name="terms"]').removeAttr('aria-required');
        
        // Elimină clasa de wrapper required
        $('.form-row').removeClass('validate-required');
        
        log('Required attributes eliminat complet');
    }
    
    // =========================================
    // MUTĂ PAYMENT METHODS
    // =========================================
    
    /**
     * Restructurează DOM-ul: mută form.checkout să învelească tot conținutul custom.
     *
     * Problema: wc_checkout_form (variabilă locală în closure WC) deleghează
     * TOATE evenimentele de pe form.checkout. Dacă mutăm #payment/shipping
     * ÎN AFARA formularului, WC nu le mai vede → payment_method nu se trimite,
     * selecția sare pe Bank Transfer, update_checkout nu se triggeruiește.
     *
     * Soluția: în loc să mutăm elemente DIN form, mutăm FORM-ul să le conțină pe TOATE.
     * Astfel #payment + shipping rămân INSIDE form, WC funcționează nativ.
     */
    function integrateFormWithLayout() {
        var $form = $('form.checkout');
        var $wrapper = $('.webgsm-checkout-wrapper');

        if (!$form.length || !$wrapper.length) return;
        if ($form.hasClass('webgsm-form-integrated')) return;

        // 1. Salvăm conținutul original al formularului WC (billing, shipping, #payment...)
        var $wcOriginal = $('<div class="webgsm-wc-original"></div>');
        $form.children().appendTo($wcOriginal);

        // 2. Detașăm formularul din wrapper
        $form.detach();

        // 3. Mutăm tot conținutul wrapper-ului în formular
        $wrapper.children().appendTo($form);

        // 4. Adăugăm conținutul original WC (ascuns, dar accesibil pt fragment replacement)
        $form.append($wcOriginal);

        // 5. Formularul ia locul wrapper-ului
        $wrapper.replaceWith($form);
        $form.addClass('webgsm-checkout-wrapper webgsm-form-integrated');

        // Ascundem tot conținutul WC original. Elementele care trebuie vizibile
        // (#payment, .webgsm-native-shipping-sr) vor fi mutate de
        // movePaymentMethods() / moveShippingSection() în containere vizibile.
        $wcOriginal.hide();

        // #region agent log
        fetch('http://127.0.0.1:7737/ingest/d4671e02-eb27-4a13-9c43-eddfef593936',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'d841f7'},body:JSON.stringify({sessionId:'d841f7',location:'checkout.js:integrateFormWithLayout',message:'DOM integration done',data:{formInDOM:$form.closest('body').length>0,wcOriginalChildren:$wcOriginal.children().length,paymentInWcOrig:$wcOriginal.find('#payment').length,shippingSrInWcOrig:$wcOriginal.find('.webgsm-native-shipping-sr').length,paymentMethodsContainer:$('.webgsm-payment-methods').length,shippingContainer:$('#webgsm-shipping-container').length,formHasClass:$form.hasClass('webgsm-form-integrated')},timestamp:Date.now(),hypothesisId:'A'})}).catch(function(){});
        // #endregion

        log('DOM restructurat: form.checkout wrappează tot layout-ul custom');
    }

    function movePaymentMethods() {
        var $payment = $('#payment');
        var $target = $('.webgsm-payment-methods');

        // #region agent log
        fetch('http://127.0.0.1:7737/ingest/d4671e02-eb27-4a13-9c43-eddfef593936',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'d841f7'},body:JSON.stringify({sessionId:'d841f7',location:'checkout.js:movePaymentMethods',message:'movePayment check',data:{paymentFound:$payment.length,targetFound:$target.length,alreadyInTarget:$target.find('#payment').length,packeteryInputsInPayment:$payment.find('input[name^="packetery_"]').length,paymentIsInsideForm:$payment.closest('form.checkout').length>0},timestamp:Date.now(),hypothesisId:'A,C'})}).catch(function(){});
        // #endregion

        if ($payment.length && $target.length && !$target.find('#payment').length) {
            $payment.appendTo($target);
            $payment.show();
            log('Payment methods mutat în .webgsm-payment-methods (inside form)');
        }
    }

    function moveShippingSection() {
        var $container = $('#webgsm-shipping-container');
        var $shipping = $('.webgsm-native-shipping-sr');

        // #region agent log
        fetch('http://127.0.0.1:7737/ingest/d4671e02-eb27-4a13-9c43-eddfef593936',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'d841f7'},body:JSON.stringify({sessionId:'d841f7',location:'checkout.js:moveShippingSection',message:'moveShipping check',data:{containerFound:$container.length,shippingFound:$shipping.length,alreadyInContainer:$container.find('.webgsm-native-shipping-sr').length,shippingRadiosCount:$shipping.find('input[name^="shipping_method"]').length,shippingVisible:$shipping.is(':visible'),containerInsideForm:$container.closest('form.checkout').length>0},timestamp:Date.now(),hypothesisId:'A,E'})}).catch(function(){});
        // #endregion

        if ($container.length && $shipping.length && !$container.find('.webgsm-native-shipping-sr').length) {
            $shipping.appendTo($container);
            $shipping.show();
            log('Shipping section mutată în #webgsm-shipping-container (inside form)');
        }
    }

    /**
     * Salvează starea Packeta (hidden inputs) înainte de update_checkout.
     * WC's fragment replacement înlocuiește #payment complet → inputurile
     * packetery_* se resetează. Le salvăm și le restaurăm după.
     */
    var _packetaSavedState = {};

    function savePacketaState() {
        _packetaSavedState = {};
        var allPacketaInputs = $('input[name^="packetery_"], input[id^="packetery_"]');
        allPacketaInputs.each(function() {
            var name = $(this).attr('name') || $(this).attr('id');
            var val = $(this).val();
            if (name && val) {
                _packetaSavedState[name] = val;
            }
        });

        // #region agent log
        fetch('http://127.0.0.1:7737/ingest/d4671e02-eb27-4a13-9c43-eddfef593936',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'d841f7'},body:JSON.stringify({sessionId:'d841f7',location:'checkout.js:savePacketaState',message:'Packeta save',data:{totalInputsFound:allPacketaInputs.length,savedKeys:Object.keys(_packetaSavedState),savedValues:_packetaSavedState},timestamp:Date.now(),hypothesisId:'B'})}).catch(function(){});
        // #endregion

        if (Object.keys(_packetaSavedState).length > 0) {
            log('Packeta state salvat:', _packetaSavedState);
        }
    }

    function restorePacketaState() {
        var keysToRestore = Object.keys(_packetaSavedState);

        // #region agent log
        fetch('http://127.0.0.1:7737/ingest/d4671e02-eb27-4a13-9c43-eddfef593936',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'d841f7'},body:JSON.stringify({sessionId:'d841f7',location:'checkout.js:restorePacketaState',message:'Packeta restore',data:{keysToRestore:keysToRestore,packeteryInputsInDomNow:$('input[name^="packetery_"]').length,packeteryIdsInDomNow:$('input[id^="packetery_"]').length},timestamp:Date.now(),hypothesisId:'B'})}).catch(function(){});
        // #endregion

        if (!keysToRestore.length) return;

        $.each(_packetaSavedState, function(name, val) {
            var $el = $('input[name="' + name + '"]');
            if (!$el.length) {
                $el = $('input#' + name);
            }
            if ($el.length && !$el.val()) {
                $el.val(val);
            }
        });
        log('Packeta state restaurat');

        updatePickupPointDisplay();
    }
    
    /**
     * Verifică dacă method ID-ul este un pickup point Packeta (Box/Easybox/Locker).
     * Sursa primară: lista exactă generată de PHP din DB-ul Packeta (packeta_pickup_method_ids).
     * Fallback: detecție bazată pe string pentru formate non-standard.
     */
    function isPacketaPickupMethod(id) {
        if (!id) return false;
        var s = String(id).toLowerCase();
        // Sursa primară: lista exactă din PHP (generată din DB Packeta is_pickup_points)
        var pickupList = (typeof webgsm_checkout !== 'undefined' && webgsm_checkout.packeta_pickup_method_ids) ? webgsm_checkout.packeta_pickup_method_ids : [];
        if (pickupList.length > 0) {
            return pickupList.indexOf(s) !== -1;
        }
        // Fallback dacă lista PHP nu e disponibilă
        if (s.indexOf('sameday') !== -1 && s.indexOf('box') === -1) return false;
        if (s.indexOf('fan') !== -1 && s.indexOf('fanbox') === -1) return false;
        return s.indexOf('easybox') !== -1 || s.indexOf('fanbox') !== -1 || (s.indexOf('sameday') !== -1 && s.indexOf('box') !== -1);
    }
    
    /** Afișează adresa punctului de ridicare selectat (Easybox/Fanbox) */
    function updatePickupPointDisplay() {
        var chosen = $('input[name^="shipping_method"]:checked').val() || '';
        var $info = $('#webgsm-pickup-point-info');

        if (!isPacketaPickupMethod(chosen)) {
            $info.hide().empty();
            return;
        }

        // Citim datele din câmpurile ascunse Packeta
        var place  = ($('#packetery_point_place').val()  || '').trim();
        var street = ($('#packetery_point_street').val() || '').trim();
        var city   = ($('#packetery_point_city').val()   || '').trim();
        var zip    = ($('#packetery_point_zip').val()    || '').trim();

        if (!place && !street) {
            $info.hide().empty();
            return;
        }

        // Determinăm tipul curierului din ID-ul metodei
        var carrierLabel = 'Box';
        var carrierClass = 'webgsm-pui-generic';
        var s = chosen.toLowerCase();
        if (s.indexOf('sameday') !== -1 || s.indexOf('easybox') !== -1) {
            carrierLabel = 'Sameday Easybox';
            carrierClass = 'webgsm-pui-sameday';
        } else if (s.indexOf('fanbox') !== -1 || s.indexOf('fan') !== -1) {
            carrierLabel = 'Fan Courier FanBox';
            carrierClass = 'webgsm-pui-fanbox';
        }

        var addrParts = [street, (city + (zip ? ' ' + zip : ''))].filter(Boolean);
        var addrStr = addrParts.join(', ');
        $info.removeClass('webgsm-pui-sameday webgsm-pui-fanbox webgsm-pui-generic')
             .addClass(carrierClass)
             .html(
                '<span class="webgsm-pui-icon">📍</span>' +
                '<span class="webgsm-pui-body">' +
                    '<strong class="webgsm-pui-name">' + place + '</strong>' +
                    '<span class="webgsm-pui-carrier">' + carrierLabel + '</span>' +
                    (addrStr ? '<span class="webgsm-pui-addr">' + addrStr + '</span>' : '') +
                '</span>'
             )
             .show();
    }

    /** Injectează containerul pentru info punct ridicare după secțiunea de livrare */
    function ensurePickupPointInfoContainer() {
        if ($('#webgsm-pickup-point-info').length) return;
        var $container = $('<div id="webgsm-pickup-point-info" style="display:none"></div>');
        var $target = $('#webgsm-shipping-container, .webgsm-native-shipping-sr').last();
        if ($target.length) {
            $target.after($container);
        } else {
            $('.webgsm-checkout-main').append($container);
        }
    }

    /** Ascunde widgetul Packeta când e selectat door-to-door – evită eroarea scriptului Packeta */
    function togglePacketaWidgetVisibility() {
        // Prioritate: radio-ul vizibil din containerul nostru > orice radio din pagină
        var chosen = $('#webgsm-shipping-container input[name^="shipping_method"]:checked').val()
                  || $('input[name^="shipping_method"]:checked').first().val()
                  || '';
        var isPacketa = !!chosen && isPacketaPickupMethod(chosen);

        var $scope = $('.webgsm-native-shipping-sr, #webgsm-shipping-container');
        var $rows = $scope.find('tr.packetery-widget-button-table-row, .packeta-widget, .packetery-widget-button-wrapper');
        if (isPacketa) {
            $rows.removeClass('webgsm-packeta-hidden').show();
        } else {
            $rows.addClass('webgsm-packeta-hidden').hide();
        }
        // Buton "Selectează punct ridicare" – doar pentru Box, ascuns la door-to-door
        var $btnRow = $('.webgsm-packeta-btn-row');
        if ($btnRow.length) {
            $btnRow.toggle(isPacketa);
        }
        // Secțiunea "Adresa de livrare" – vizibilă la door-to-door, ascunsă la Box (adresa = locker)
        var $shippingSection = $('#webgsm-shipping-address-section');
        if ($shippingSection.length) {
            if (isPacketa) {
                $shippingSection.hide();
                $('#same_as_billing').prop('checked', true);
                $('#ship_to_different_address').val('0');
                $('#shipping_address_fields').hide();
            } else {
                // Door-to-door sau niciun curier selectat: arată secțiunea
                $shippingSection.show();
            }
        }
    }
    
    /** Adaugă emoji reprezentativ (fin) la metode livrare și plată */
    function addEmojiToMethods() {
        var shipEmoji = { box: '\u{1F4E6}', door: '\u{1F69A}' };
        var payEmoji = { cod: '\u{1F4B0}', bacs: '\u{1F3E6}', default: '\u{1F4B3}' };
        var $shipScope = $('.webgsm-native-shipping-sr, #webgsm-shipping-container');
        $shipScope.find('tr.shipping, li:has(input[name^="shipping_method"])').each(function() {
            var $row = $(this);
            if ($row.find('.webgsm-method-emoji').length) return;
            var $label = $row.find('label').first();
            if (!$label.length) $label = $row.find('td').first();
            if (!$label.length) $label = $row;
            var val = $row.find('input[name^="shipping_method"]').val() || '';
            var em = isPacketaPickupMethod(val) ? shipEmoji.box : shipEmoji.door;
            $label.prepend('<span class="webgsm-method-emoji" aria-hidden="true">' + em + '</span> ');
        });
        $('.wc_payment_method').each(function() {
            var $li = $(this);
            if ($li.find('.webgsm-method-emoji').length) return;
            var $label = $li.find('label').first();
            if (!$label.length) return;
            var cls = $li.attr('class') || '';
            var id = (cls.match(/payment_method_(\w+)/) || [])[1] || cls;
            var em = id.indexOf('cod') !== -1 ? payEmoji.cod : (id.indexOf('bacs') !== -1 ? payEmoji.bacs : payEmoji.default);
            $label.prepend('<span class="webgsm-method-emoji" aria-hidden="true">' + em + '</span> ');
        });
        $('.summary-shipping-item').each(function() {
            var $li = $(this);
            if ($li.find('.webgsm-method-emoji').length) return;
            var rid = $li.data('rateId') || '';
            var em = isPacketaPickupMethod(rid) ? shipEmoji.box : shipEmoji.door;
            $li.prepend('<span class="webgsm-method-emoji" aria-hidden="true">' + em + '</span> ');
        });
    }

    /** Înlocuiește logo Packeta cu Sameday/Fan când metoda e Box sau Door */
    function replacePacketaLogosForCarriers() {
        var $scope = $('.webgsm-native-shipping-sr, #webgsm-shipping-container');
        var $packetaRadios = $scope.find('input[name^="shipping_method"]').filter(function() {
            var v = String((this.value || '')).toLowerCase();
            return v.indexOf('packeta') === 0 || v.indexOf('easybox') !== -1 || v.indexOf('sameday') !== -1 || v.indexOf('fanbox') !== -1 || v.indexOf('fan') !== -1;
        });
        var $logos = $scope.find('img.packetery-widget-button-logo, tr.packetery-widget-button-table-row img');
        var samedayLogo = (typeof webgsm_checkout !== 'undefined' && webgsm_checkout.sameday_logo_url) ? webgsm_checkout.sameday_logo_url : 'https://www.sameday.ro/app/themes/samedaytwo/public/images/logo/sameday_logo_big.webp';
        var fanLogo = (typeof webgsm_checkout !== 'undefined' && webgsm_checkout.fan_logo_url) ? webgsm_checkout.fan_logo_url : 'https://www.fancourier.ro/wp-content/uploads/2023/03/logo.svg';
        $packetaRadios.each(function(i) {
            var methodId = String((this.value || '')).toLowerCase();
            var $img = $logos.eq(i);
            if (!$img.length) return;
            if (methodId.indexOf('sameday') !== -1) {
                $img.attr('src', samedayLogo).attr('alt', 'Sameday').addClass('webgsm-carrier-logo-sameday');
            } else if (methodId.indexOf('fan') !== -1) {
                $img.attr('src', fanLogo).attr('alt', 'Fan Courier').addClass('webgsm-carrier-logo-fan');
            }
        });
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

    /** Afișează „Date din cont” când userul nu are persoane salvate dar hidden-urile au date (utilizator nou). */
    function showAccountBillingIfEmpty() {
        var hasPersons = $('input[name="selected_person"]').length > 0;
        var hasCompanies = $('input[name="selected_company"]').length > 0;
        var fn = ($('#billing_first_name').val() || '').trim();
        var ln = ($('#billing_last_name').val() || '').trim();
        var em = ($('#billing_email').val() || '').trim();
        var hasAccountData = fn || ln || em;

        $('#webgsm-account-billing-hint').remove();
        if (hasPersons || hasCompanies) return;

        if (hasAccountData && WebGSM.currentCustomerType === 'pf') {
            var text = [fn, ln].filter(Boolean).join(' ');
            if (em) text += (text ? ', ' : '') + em;
            var $hint = $('<p id="webgsm-account-billing-hint" class="no-items" style="background:#ecfdf5;color:#065f46;padding:10px 12px;border-radius:6px;margin-bottom:10px;font-size:13px;"></p>');
            $hint.text('Date facturare din cont: ' + (text || '—'));
            $hint.prependTo('#pf_section .persons-list');
        }
        if (hasAccountData && WebGSM.currentCustomerType === 'pj') {
            var comp = ($('#billing_company').val() || '').trim();
            var cui = ($('#billing_cui').val() || '').trim();
            var text = comp || cui || em || '—';
            var $hint = $('<p id="webgsm-account-billing-hint" class="no-items" style="background:#ecfdf5;color:#065f46;padding:10px 12px;border-radius:6px;margin-bottom:10px;font-size:13px;"></p>');
            $hint.text('Date facturare din cont: ' + text);
            $hint.prependTo('#pj_section .companies-list');
        }
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
        
        // Validare termeni și condiții – obligatoriu
        if (!$('#terms').is(':checked')) {
            errors.push('Trebuie să accepți termenii și condițiile.');
        }
        
        // Validare punct ridicare – când Box e selectat, utilizatorul trebuie să aleagă locker-ul
        var chosenShipping = $('input[name^="shipping_method"]:checked').val();
        if (isPacketaPickupMethod(chosenShipping)) {
            var $allPacketeryInputs = $('input[name*="packetery_point"], input[name*="packeta_point"]');
            var $allPacketeryById = $('input[id*="packetery_point"]');
            var $branchInput = $('.packeta-selector-branch-id, input[name*="packetery_point"], input[name*="packeta_point"], input[id*="packeta_branch"], input[id*="branch_id"]').filter(function() {
                return $(this).val() && $(this).val().toString().trim().length > 0;
            });
            var widgetAddrText = ($('.packeta-widget-selected-address').text() || '').trim();
            var hasSelectedPoint = $branchInput.length > 0 || widgetAddrText.length > 0;

            // #region agent log
            var inputDetails = {};
            $allPacketeryInputs.each(function(){inputDetails[$(this).attr('name')]=$(this).val();});
            $allPacketeryById.each(function(){inputDetails['#'+$(this).attr('id')]=$(this).val();});
            fetch('http://127.0.0.1:7737/ingest/d4671e02-eb27-4a13-9c43-eddfef593936',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'d841f7'},body:JSON.stringify({sessionId:'d841f7',location:'checkout.js:validatePickupPoint',message:'Pickup validation',data:{chosenShipping:chosenShipping,isPickup:true,packeteryByName:$allPacketeryInputs.length,packeteryById:$allPacketeryById.length,branchInputsWithValue:$branchInput.length,widgetAddrText:widgetAddrText,hasSelectedPoint:hasSelectedPoint,inputDetails:inputDetails,savedState:_packetaSavedState},timestamp:Date.now(),hypothesisId:'D,C'})}).catch(function(){});
            // #endregion

            if (!hasSelectedPoint) {
                errors.push('Selectează punctul de ridicare (click pe „Selectează punct ridicare").');
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
            name: (($('#company_name').val() || '').trim()),
            cui: (($('#company_cui').val() || '').trim()),
            reg: (($('#company_reg').val() || '').trim()),
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
                // FIX: Închide popup și reload întotdeauna pentru a actualiza lista
                log('Firmă salvată cu succes - reload pagină');
                closePopup();
                
                // Setează în hidden input
                $('input[name="billing_customer_type"][value="pj"]').prop('checked', true);
                
                // Salvează în sessionStorage pentru a persista după reload
                if (window.sessionStorage) {
                    sessionStorage.setItem('webgsm_force_pj', 'yes');
                }
                
                // Reload pentru a actualiza lista de firme
                location.reload();
                
                // Branch pentru guest (nu se mai execută din cauza reload)
                if (false && !response.data.saved_to_account) {
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
                console.log('ANAF Response:', d);
                
                // Completează câmpurile
                $('#company_name').val(d.name || '');
                $('#company_cui').val(d.cui || '');
                $('#company_reg').val(d.j || '');
                $('#company_address').val(d.address || '');
                
                // Setează select-ul cu codul județului
                var countyCode = getStateCode(d.county);
                console.log('County Code:', countyCode);
                $('#company_county').val(countyCode || '');
                $('#company_city').val(d.city || '');
                
                // Trigger events pentru validare
                $('#company_name, #company_cui, #company_reg, #company_address, #company_city').trigger('input');
                $('#company_county').trigger('change');
                
                // Curăță erorile de validare
                if (typeof clearFieldErrors === 'function') {
                    clearFieldErrors('#company_popup');
                }
                
                $status.css({background: '#e8f5e9', color: '#2e7d32'})
                       .html('✓ ' + d.name + (d.is_tva ? ' (Plătitor TVA)' : ''))
                       .show();
                       
                console.log('Câmpuri completate cu succes');
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
        // SUBMIT - VALIDARE + INJECTARE CÂMPURI
        // ==========================================

        /**
         * Injectează toate câmpurile necesare în form.checkout înainte de submit.
         * Câmpuri care sunt în afara formularului (termeni, shipping, payment, Packeta)
         * trebuie copiate ca hidden inputs în form pentru a fi incluse în POST.
         */
        function injectFieldsIntoForm() {
            var $form = $('form.checkout');

            // Termeni — checkbox-ul #terms e acum INSIDE form (integrateFormWithLayout).
            // Asigurăm doar că valoarea ajunge la server (checkbox unchecked nu se serializează).
            $form.find('input[name="terms"][type="hidden"]').remove();
            if ($('#terms').is(':checked')) {
                $form.append('<input type="hidden" name="terms" value="1">');
            }

            // Nonce
            if (!$form.find('input[name="woocommerce-process-checkout-nonce"]').length) {
                var nonceVal = $('input[name="woocommerce-process-checkout-nonce"]').val();
                if (nonceVal) {
                    $form.append('<input type="hidden" name="woocommerce-process-checkout-nonce" value="' + nonceVal + '">');
                }
            }

            // Payment method + Shipping method sunt acum INSIDE form.checkout
            // (datorită integrateFormWithLayout), nu mai trebuie injectate separat.

            // _wp_http_referer
            if (!$form.find('input[name="_wp_http_referer"]').length) {
                var referer = $('input[name="_wp_http_referer"]').val();
                if (referer) {
                    $form.append('<input type="hidden" name="_wp_http_referer" value="' + referer + '">');
                }
            }

            var chosenShippingMethod = $('input[name^="shipping_method"][type="radio"]:checked').val()
                                    || $('input[name^="shipping_method"][type="hidden"]').first().val() || '';

            // Packeta – doar dacă e pickup point (Box/Easybox/Locker)
            if (isPacketaPickupMethod(chosenShippingMethod)) {
                var packetaFields = {};
                var requiredKeys = ['packetery_point_id', 'packetery_point_name', 'packetery_point_city',
                 'packetery_point_zip', 'packetery_point_street', 'packetery_point_place',
                 'packetery_point_url', 'packetery_point_type', 'packetery_carrier_id'];

                $('input[name^="packetery_"], input[id^="packetery_"]').each(function() {
                    var n = $(this).attr('name') || $(this).attr('id');
                    var v = $(this).val();
                    if (n && v) {
                        packetaFields[n] = v;
                    }
                });

                requiredKeys.forEach(function(fieldName) {
                    if (!packetaFields[fieldName]) {
                        var $el = $('#' + fieldName);
                        if ($el.length && $el.val()) {
                            packetaFields[fieldName] = $el.val();
                        }
                    }
                    if (!packetaFields[fieldName] && _packetaSavedState[fieldName]) {
                        packetaFields[fieldName] = _packetaSavedState[fieldName];
                    }
                });

                $form.find('input.webgsm-packetery-injected').remove();

                for (var fieldName in packetaFields) {
                    if (packetaFields.hasOwnProperty(fieldName)) {
                        $form.append('<input type="hidden" class="webgsm-packetery-injected" name="' + fieldName + '" value="' + (packetaFields[fieldName] || '') + '">');
                    }
                }

                // #region agent log
                fetch('http://127.0.0.1:7737/ingest/d4671e02-eb27-4a13-9c43-eddfef593936',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'d841f7'},body:JSON.stringify({sessionId:'d841f7',location:'checkout.js:injectPacketaFields',message:'Packeta inject result',data:{packetaFields:packetaFields,savedStateFallback:_packetaSavedState,formPacketaCount:$form.find('input[name^="packetery_"]').length,shippingMethod:chosenShippingMethod},timestamp:Date.now(),hypothesisId:'INJECT'})}).catch(function(){});
                // #endregion

                log('Packeta fields injected:', packetaFields);
                if (!packetaFields['packetery_point_id']) {
                    log('WARNING: packetery_point_id LIPSEȘTE! Packeta nu va putea genera AWB.');
                }
            }

            return chosenShippingMethod;
        }

        // Handler comun pentru plasare comandă – desktop (click) și mobile (touchend)
        function handlePlaceOrder(e) {
            if (e.type === 'touchend') {
                e.preventDefault();
            }
            e.stopPropagation();
            var now = Date.now();
            if (handlePlaceOrder._lastRun && (now - handlePlaceOrder._lastRun) < 600) {
                return false;
            }
            handlePlaceOrder._lastRun = now;

            log('========== CLICK/TOUCH PLACE ORDER ==========');
            removeAllRequiredAttributes();

            var isValid = validateBeforeSubmit();
            if (!isValid) {
                return false;
            }

            injectFieldsIntoForm();

            $('form.checkout').removeClass('processing');

            log('TRIMITE FORM – form.checkout.submit()');

            // #region agent log
            var formData=$('form.checkout').serialize();
            var packetaInForm=formData.match(/packetery_[^&]*/g)||[];
            var paymentInForm=formData.match(/payment_method=[^&]*/g)||[];
            var shippingInForm=formData.match(/shipping_method[^&]*/g)||[];
            fetch('http://127.0.0.1:7737/ingest/d4671e02-eb27-4a13-9c43-eddfef593936',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'d841f7'},body:JSON.stringify({sessionId:'d841f7',location:'checkout.js:formSubmit',message:'Form data at submit',data:{packetaFields:packetaInForm,paymentFields:paymentInForm,shippingFields:shippingInForm,totalFormLength:formData.length},timestamp:Date.now(),hypothesisId:'SUBMIT'})}).catch(function(){});
            // #endregion

            $('form.checkout').submit();
            return false;
        }
        $(document).on('click', '#place_order, #mobile_place_order', handlePlaceOrder);
        $(document).on('touchend', '#place_order, #mobile_place_order', handlePlaceOrder);

        // WooCommerce checkout_place_order – rulează INSIDE submit handler-ul WC
        $(document.body).on('checkout_place_order', function() {
            removeAllRequiredAttributes();
            injectFieldsIntoForm();
            return true;
        });
        
        // ==========================================
        // Selectare curier din sumar (lista de shipping din dreapta)
        // ==========================================
        function syncSummaryShippingSelection() {
            var chosen = $('input[name^="shipping_method"]:checked').val();
            $('.summary-shipping-list .summary-shipping-item').each(function() {
                var $li = $(this);
                var id = $li.data('rateId');
                if (id === chosen) {
                    $li.addClass('is-selected');
                } else {
                    $li.removeClass('is-selected');
                }
            });
        }
        // La click pe elementul din sumar, bifează radio-ul WooCommerce și declanșează update_checkout
        $(document).on('click', '.summary-shipping-list .summary-shipping-item', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var id = $(this).data('rateId');
            if (!id) return;
            var $radio = $('input[name^="shipping_method"][value="' + id + '"]');
            if ($radio.length) {
                $radio.prop('checked', true).trigger('change');
                $(document.body).trigger('update_checkout');
                syncSummaryShippingSelection();
                // NU deschide automat – packeta_sender_XXX e folosit pentru toți curierii, nu putem distinge Box de door-to-door
                window._webgsm_open_packeta_after_update = false;
            }
        });
        // Sincronizează când se schimbă curierul în blocul standard WooCommerce
        $(document).on('change', 'input[name^="shipping_method"]', function() {
            var id = $(this).val();
            if (!isPacketaPickupMethod(id)) window._webgsm_open_packeta_after_update = false;
            togglePacketaWidgetVisibility();
            updatePickupPointDisplay();
            syncSummaryShippingSelection();
            // Forțăm recalcularea totalului și re-randarea sumarului când metoda de livrare se schimbă din containerul „Metoda de livrare”
            $(document.body).trigger('update_checkout');
            // La schimbare pe Box, widget-ul poate avea date cu întârziere – re-check după ce update_checkout se finalizează
            if (isPacketaPickupMethod(id)) {
                setTimeout(function() { updatePickupPointDisplay(); }, 350);
                setTimeout(function() { updatePickupPointDisplay(); }, 700);
            }
        });
        // Buton "Selectează punct ridicare" – deschide harta DOAR dacă Packeta/Easybox/Fanbox e selectat
        $(document).on('click', '.webgsm-packeta-open-btn', function(e) {
            e.preventDefault();
            var chosen = $('input[name^="shipping_method"]:checked').val();
            if (!isPacketaPickupMethod(chosen)) return; // door-to-door selectat – nu deschide Packeta
            var $btn = $('.packeta-selector-open, .packeta-widget-button, [class*="packeta"][class*="open"], a[href*="packeta"], .wc-packeta-select-point');
            if ($btn.length) {
                $btn.first().trigger('click');
                log('Packeta: deschis selector punct ridicare (manual)');
            } else if (typeof Packeta !== 'undefined' && Packeta.Widget && typeof Packeta.Widget.pick === 'function') {
                Packeta.Widget.pick();
                log('Packeta: apelat Widget.pick() (manual)');
            } else {
                $('input[name^="shipping_method"]:checked').closest('tr').find('a, button, [role="button"]').first().trigger('click');
            }
        });
        // Prima sincronizare la inițializare
        syncSummaryShippingSelection();

        // Form submit – sincronizare shipping + termeni (handler dedicat, fără duplicare cu click handler)
        $('form.checkout').on('submit', function() {
            log('========== FORM SUBMIT ==========');

            // Sincronizează shipping address dacă e altă adresă decât billing
            if (!$('#same_as_billing').is(':checked')) {
                $('#ship_to_different_address').val('1');
                $('input[name="shipping_first_name"]').val($('#shipping_first_name').val() || $('input[name="shipping_first_name"]').val());
                $('input[name="shipping_last_name"]').val($('#shipping_last_name').val() || $('input[name="shipping_last_name"]').val());
                $('input[name="shipping_phone"]').val($('#shipping_phone').val() || $('input[name="shipping_phone"]').val());
                $('input[name="shipping_address_1"]').val($('#shipping_address_1').val() || $('input[name="shipping_address_1"]').val());
                $('input[name="shipping_city"]').val($('#shipping_city').val() || $('input[name="shipping_city"]').val());
                $('input[name="shipping_state"]').val($('#shipping_state').val() || $('input[name="shipping_state"]').val());
                $('input[name="shipping_postcode"]').val($('#shipping_postcode').val() || $('input[name="shipping_postcode"]').val());
                $('input[name="shipping_country"]').val($('#shipping_country').val() || $('input[name="shipping_country"]').val());
                injectShippingDataSilent();
            } else {
                $('#ship_to_different_address').val('0');
            }

            // Re-injectează câmpurile din afara formularului (safety net)
            injectFieldsIntoForm();
            removeAllRequiredAttributes();
        });
        
        // ==========================================
        // SCHIMBARE TIP CLIENT
        // ==========================================
        
        $(document).on('change', 'input[name="billing_customer_type"]', function() {
            var type = $(this).val();
            WebGSM.currentCustomerType = type;
            log('Schimbare tip client: ' + type);
            
            toggleCustomerSections(type);
            
            // Re-injectează datele (fără popup!)
            if (type === 'pf') {
                injectPersonDataSilent();
                $(document.body).trigger('update_checkout');
            } else {
                injectCompanyDataSilent();
                // Pentru PJ, adresa de livrare trebuie să fie aceeași cu adresa firmei (change declanșează update_checkout)
                $('#same_as_billing').prop('checked', true).trigger('change');
            }
            showAccountBillingIfEmpty();
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
        
        // Sincronizează custom terms checkbox → native WC terms (care e în form și ajunge la POST)
        $(document).on('change', '#terms', function() {
            var isChecked = $(this).is(':checked');
            $('[name="terms"]').prop('checked', isChecked);
            $('#terms_mobile').prop('checked', isChecked);
        });
        // Sincronizează terms mobile ↔ terms (pentru mobile)
        $(document).on('change', '#terms_mobile', function() {
            var isChecked = $(this).is(':checked');
            $('#terms').prop('checked', isChecked);
            $('[name="terms"]').prop('checked', isChecked);
        });

        // Payment/shipping sunt acum INSIDE form.checkout (integrateFormWithLayout),
        // WC le gestionează nativ — nu mai e nevoie de sync manual.

        // Actualizare adresă când Packeta schimbă punctul – event delegation (funcționează și după fragment replace)
        $(document).on('change input', 'input[name^="packetery_point"], input[id^="packetery_point"]', function() {
            if (isPacketaPickupMethod($('input[name^="shipping_method"]:checked').val())) {
                updatePickupPointDisplay();
            }
        });
        // MutationObserver pe câmpurile Packeta (unele widget-uri setează value fără event)
        function attachPacketaObservers() {
            ['packetery_point_place','packetery_point_street','packetery_point_city','packetery_point_zip','packetery_point_id'].forEach(function(id) {
                var el = document.getElementById(id);
                if (!el || el._webgsmObserved) return;
                el._webgsmObserved = true;
                var obs = new MutationObserver(function() { updatePickupPointDisplay(); });
                obs.observe(el, { attributes: true, attributeFilter: ['value'] });
            });
            var selEl = document.querySelector('.packeta-widget-selected-address');
            if (selEl && !selEl._webgsmObserved) {
                selEl._webgsmObserved = true;
                var selObs = new MutationObserver(function() { updatePickupPointDisplay(); });
                selObs.observe(selEl, { childList: true, subtree: true, characterData: true });
            }
        }
        setTimeout(attachPacketaObservers, 400);

        $(document.body).on('checkout_error', function() {});

        // Salvăm starea Packeta ÎNAINTE de AJAX (fragment replacement resetează #payment)
        $(document.body).on('update_checkout', function() {
            savePacketaState();
        });

        $(document.body).on('updated_checkout', function() {
            log('========== updated_checkout - RE-INIT ==========');

            // Re-run init dacă formularul a fost înlocuit de fragmente (ex. mobile/AJAX)
            if (!$('form.checkout').hasClass('webgsm-form-integrated')) {
                init();
            }

            togglePacketaWidgetVisibility();
            ensurePickupPointInfoContainer();

            removeAllRequiredAttributes();
            movePaymentMethods();
            moveShippingSection();

            // Restaurăm starea Packeta (inputuri resetate de fragment replacement)
            restorePacketaState();

            setTimeout(function() {
                replacePacketaLogosForCarriers();
                addEmojiToMethods();
                togglePacketaWidgetVisibility();
                updatePickupPointDisplay();
                attachPacketaObservers();
            }, 100);

            if (WebGSM.currentCustomerType === 'pf') {
                injectPersonDataSilent();
            } else {
                injectCompanyDataSilent();
            }

            syncSummaryShippingSelection();

            // Sincronizează terms mobile cu #terms după fragment replace
            if ($('#terms').is(':checked')) {
                $('#terms_mobile').prop('checked', true);
            }

            // #region agent log
            (function(){
                var sc=$('#webgsm-shipping-container')[0];
                var pm=$('.webgsm-payment-methods')[0];
                var sr=$('.webgsm-native-shipping-sr')[0];
                var pay=$('#payment')[0];
                var form=$('form.checkout')[0];
                function vis(el){if(!el)return'NOT_IN_DOM';var s=window.getComputedStyle(el);return{display:s.display,visibility:s.visibility,opacity:s.opacity,h:el.offsetHeight,w:el.offsetWidth,overflow:s.overflow};}
                var shippingHTML='';
                if(sr){shippingHTML=$(sr).html().substring(0,500);}
                var payHTML='';
                if(pay){payHTML=$(pay).find('.wc_payment_methods').html();if(payHTML)payHTML=payHTML.substring(0,500);else payHTML='NO_.wc_payment_methods';}
                var shippingInContainer=$('#webgsm-shipping-container .webgsm-native-shipping-sr').length;
                var paymentInContainer=$('.webgsm-payment-methods #payment').length;
                fetch('http://127.0.0.1:7737/ingest/d4671e02-eb27-4a13-9c43-eddfef593936',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'d841f7'},body:JSON.stringify({sessionId:'d841f7',location:'checkout.js:updatedCheckout',message:'Visual state after updated_checkout',data:{shippingContainer:vis(sc),shippingSR:vis(sr),paymentDiv:vis(pay),shippingInContainer:shippingInContainer,paymentInContainer:paymentInContainer,shippingHTMLsnippet:shippingHTML,paymentHTMLsnippet:payHTML,wcOrigDisplay:$('.webgsm-wc-original').css('display'),allShippingRadios:$('input[name^="shipping_method"]').length,checkedShipping:$('input[name^="shipping_method"]:checked').val()||'NONE'},timestamp:Date.now(),hypothesisId:'VISUAL_AFTER_UPDATE'})}).catch(function(){});
            })();
            // #endregion

            window._webgsm_open_packeta_after_update = false;
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

        // #region agent log
        $(document).on('click', 'input[name="payment_method"], input[name^="shipping_method"], label[for^="payment_method_"], label[for^="shipping_method_"]', function(e) {
            fetch('http://127.0.0.1:7737/ingest/d4671e02-eb27-4a13-9c43-eddfef593936',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'d841f7'},body:JSON.stringify({sessionId:'d841f7',location:'checkout.js:radioClick',message:'Radio/label clicked',data:{tagName:e.target.tagName,type:e.target.type,name:e.target.name||'label',id:e.target.id,value:e.target.value||$(e.target).attr('for'),checked:e.target.checked,insideForm:$(e.target).closest('form.checkout').length>0,parentClasses:$(e.target).parent().attr('class')||'',pointerEvents:window.getComputedStyle(e.target).pointerEvents,closestHidden:$(e.target).closest(':hidden').length>0},timestamp:Date.now(),hypothesisId:'INTERACTION'})}).catch(function(){});
        });
        $(document).on('change', 'input[name="payment_method"]', function() {
            fetch('http://127.0.0.1:7737/ingest/d4671e02-eb27-4a13-9c43-eddfef593936',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'d841f7'},body:JSON.stringify({sessionId:'d841f7',location:'checkout.js:paymentChange',message:'Payment method changed',data:{newValue:$(this).val(),allPaymentRadios:$('input[name="payment_method"]').length,checkedPayment:$('input[name="payment_method"]:checked').val()||'NONE'},timestamp:Date.now(),hypothesisId:'INTERACTION'})}).catch(function(){});
        });
        // #endregion
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
