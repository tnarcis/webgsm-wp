<?php

/**
 * WebGSM My Account - Meniu restructurat (fără a strica endpoint-uri existente)
 * @version 1.1
 */
if (!defined('ABSPATH')) exit;

// ==========================================
// MODIFICĂ MENIUL (nu îl înlocuiește complet)
// ==========================================
add_filter('woocommerce_account_menu_items', function($items) {
    
    // Redenumește items existente
    if (isset($items['dashboard'])) $items['dashboard'] = 'Panou control';
    if (isset($items['orders'])) $items['orders'] = 'Comenzi';
    if (isset($items['downloads'])) $items['downloads'] = 'Descarcari';
    if (isset($items['edit-account'])) $items['edit-account'] = 'Detalii cont';
    if (isset($items['customer-logout'])) $items['customer-logout'] = 'Iesire din cont';
    
    // Reordonează
    $order = [
        'dashboard',
        'orders',
        'retururi',
        'garantie',
        'downloads',
        'edit-address',
        'adrese-salvate',
        'date-facturare',
        'edit-account',
        'customer-logout'
    ];
    
    $sorted = [];
    foreach ($order as $key) {
        if (isset($items[$key])) {
            $sorted[$key] = $items[$key];
        }
    }
    
    // Adaugă items rămase
    foreach ($items as $key => $val) {
        if (!isset($sorted[$key])) {
            $sorted[$key] = $val;
        }
    }
    
    return $sorted;
}, 99);


// ==========================================
// Endpoint: adrese-salvate - fără secțiune suplimentară jos
// ==========================================
// (Secțiunea de jos a fost eliminată la cererea utilizatorului)


// ===============================
// AJAX Stergere Adresa Salvata
// ===============================
// Încarc nonce-ul cu wp_localize_script pe pagina My Account
add_action('wp_enqueue_scripts', function() {
    if (!is_account_page()) return;
    wp_enqueue_script('jquery');
    $nonce = wp_create_nonce('webgsm_nonce');
    wp_localize_script('jquery', 'webgsm_myaccount', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => $nonce
    ]);
}, 10);

// JavaScript pentru butonul de ștergere - handler complet
add_action('wp_footer', function() {
    if (!is_account_page()) return;
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        console.log('[WebGSM] Card handlers initialized');
        
        // =========================================
        // DESCHIDE POPUP PENTRU ADĂUGARE
        // =========================================
        
        // Adrese - buton + și buton empty state
        $(document).on('click', '#btn-add-address, #btn-add-address-empty', function(e) {
            e.preventDefault();
            console.log('[WebGSM] Opening address modal for ADD');
            
            // Reset form
            $('#edit_address_index').val('');
            $('#modal_title').text('Adaugă adresă livrare');
            $('#modal_label').val('');
            $('#modal_name').val('');
            $('#modal_phone').val('');
            $('#modal_address').val('');
            $('#modal_city').val('');
            $('#modal_county').val('');
            $('#modal_postcode').val('');
            
            // Show modal centrat
            $('#address_modal_saved').css('display', 'flex').hide().fadeIn(200);
        });
        
        // Firme - buton + și buton empty state
        $(document).on('click', '#btn-add-company, #btn-add-company-empty', function(e) {
            e.preventDefault();
            console.log('[WebGSM] Opening company modal for ADD');
            
            // Reset form
            $('#edit_company_index').val('');
            $('#company_modal_title').text('Adaugă firmă');
            $('#company_cui_modal').val('');
            $('#company_name_modal').val('');
            $('#company_reg_modal').val('');
            $('#company_phone_modal').val('');
            $('#company_email_modal').val('');
            $('#company_address_modal').val('');
            $('#company_city_modal').val('');
            $('#company_county_modal').val('');
            $('#anaf_status_modal').hide();
            
            // Show modal centrat
            $('#company_modal_saved').css('display', 'flex').hide().fadeIn(200);
        });
        
        // Persoane - buton + și buton empty state
        $(document).on('click', '#btn-add-person, #btn-add-person-empty', function(e) {
            e.preventDefault();
            console.log('[WebGSM] Opening person modal for ADD');
            
            // Reset form
            $('#edit_person_index').val('');
            $('#person_modal_title').text('Adaugă persoană fizică');
            $('#person_name_modal').val('');
            $('#person_cnp_modal').val('');
            $('#person_phone_modal').val('');
            $('#person_email_modal').val('');
            $('#person_address_modal').val('');
            $('#person_city_modal').val('');
            $('#person_county_modal').val('');
            $('#person_postcode_modal').val('');
            
            // Show modal centrat
            $('#person_modal_saved').css('display', 'flex').hide().fadeIn(200);
        });
        
        // =========================================
        // DESCHIDE POPUP PENTRU EDITARE
        // =========================================
        
        $(document).on('click', '.btn-edit-item', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var $btn = $(this);
            var type = $btn.data('type');
            var index = $btn.data('index');
            
            console.log('[WebGSM] Edit clicked - Type:', type, 'Index:', index);
            
            // Disable button temporar
            $btn.prop('disabled', true).text('Se incarca...');
            
            var ajaxUrl = (typeof webgsm_myaccount !== 'undefined') ? webgsm_myaccount.ajax_url : '/wp-admin/admin-ajax.php';
            var nonce = (typeof webgsm_myaccount !== 'undefined') ? webgsm_myaccount.nonce : '';
            
            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'webgsm_get_' + type + '_for_edit',
                    nonce: nonce,
                    index: index
                },
                success: function(response) {
                    console.log('[WebGSM] Edit response:', response);
                    
                    if (response.success && response.data) {
                        var data = response.data;
                        
                        if (type === 'address') {
                            $('#edit_address_index').val(index);
                            $('#modal_title').text('Editează adresa');
                            $('#modal_label').val(data.label || '');
                            $('#modal_name').val(data.name || '');
                            $('#modal_phone').val(data.phone || '');
                            $('#modal_address').val(data.address || '');
                            $('#modal_city').val(data.city || '');
                            $('#modal_county').val(data.county || '');
                            $('#modal_postcode').val(data.postcode || '');
                            $('#address_modal_saved').css('display', 'flex').hide().fadeIn(200);
                            
                        } else if (type === 'company') {
                            $('#edit_company_index').val(index);
                            $('#company_modal_title').text('Editează firma');
                            $('#company_cui_modal').val(data.cui || '');
                            $('#company_name_modal').val(data.name || '');
                            $('#company_reg_modal').val(data.reg || '');
                            $('#company_phone_modal').val(data.phone || '');
                            $('#company_email_modal').val(data.email || '');
                            $('#company_address_modal').val(data.address || '');
                            $('#company_city_modal').val(data.city || '');
                            $('#company_county_modal').val(data.county || '');
                            $('#company_modal_saved').css('display', 'flex').hide().fadeIn(200);
                            
                        } else if (type === 'person') {
                            $('#edit_person_index').val(index);
                            $('#person_modal_title').text('Editează persoana');
                            $('#person_name_modal').val(data.name || '');
                            $('#person_cnp_modal').val(data.cnp || '');
                            $('#person_phone_modal').val(data.phone || '');
                            $('#person_email_modal').val(data.email || '');
                            $('#person_address_modal').val(data.address || '');
                            $('#person_city_modal').val(data.city || '');
                            $('#person_county_modal').val(data.county || '');
                            $('#person_postcode_modal').val(data.postcode || '');
                            $('#person_modal_saved').css('display', 'flex').hide().fadeIn(200);
                        }
                    } else {
                        alert('Eroare la încărcarea datelor: ' + (response.data || 'Necunoscută'));
                    }
                    
                    // Restore button
                    $btn.html('✏️ Editează').prop('disabled', false);
                },
                error: function(xhr, status, error) {
                    console.error('[WebGSM] Edit AJAX error:', error);
                    alert('Eroare de conexiune: ' + error);
                    $btn.text('Editează').prop('disabled', false);
                }
            });
        });
        
        // =========================================
        // ȘTERGERE
        // =========================================
        
        $(document).on('click', '.btn-delete-item', function(e) {
            e.preventDefault();
            var type = $(this).data('type');
            var index = $(this).data('index');
            var $card = $(this).closest('.data-card');
            
            var typeLabels = {
                'address': 'adresa',
                'company': 'firma', 
                'person': 'persoana'
            };
            
            if (!confirm('Sigur vrei să ștergi această ' + typeLabels[type] + '?')) {
                return;
            }
            
            console.log('[WebGSM] Delete - Type:', type, 'Index:', index);
            
            $.ajax({
                url: webgsm_myaccount.ajax_url,
                type: 'POST',
                data: {
                    action: 'webgsm_delete_' + type,
                    nonce: webgsm_myaccount.nonce,
                    index: index
                },
                success: function(response) {
                    if (response.success) {
                        // Animație de ștergere și reload
                        $card.fadeOut(300, function() {
                            location.reload();
                        });
                    } else {
                        alert(response.data || 'Eroare la ștergere.');
                    }
                },
                error: function() {
                    alert('Eroare de conexiune.');
                }
            });
        });
        
        // =========================================
        // ÎNCHIDE POPUP-URI
        // =========================================
        
        $(document).on('click', '.modal-close-btn, .modal-cancel-btn, .popup-overlay', function(e) {
            e.preventDefault();
            $('.webgsm-popup').fadeOut(200);
        });
        
        // ESC pentru închidere
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                $('.webgsm-popup').fadeOut(200);
            }
        });
        
        // Căutare automată ANAF când utilizatorul introduce CUI
        var anafTimeout;
        $(document).on('input', '#company_cui_modal', function() {
            clearTimeout(anafTimeout);
            var $input = $(this);
            var cui = $input.val().trim().replace(/^RO/i, '');
            
            // Resetează status
            $('#anaf_status_modal').hide();
            
            // Caută doar dacă are minim 6 cifre
            if (cui.length >= 6) {
                $('#anaf_status_modal').show().html('🔍 Se caută automat...').css({background: '#eff6ff', color: '#1e40af', border: '1px solid #bfdbfe'});
                
                anafTimeout = setTimeout(function() {
                    $.ajax({
                        url: (typeof webgsm_myaccount !== 'undefined') ? webgsm_myaccount.ajax_url : ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'cauta_cui_anaf',
                            cui: cui
                        },
                        success: function(response) {
                            if (response.success && response.data) {
                                var data = response.data;
                                $('#company_name_modal').val(data.denumire || '');
                                $('#company_reg_modal').val(data.nrRegCom || '');
                                $('#company_address_modal').val(data.adresa || '');
                                $('#company_city_modal').val(data.localitate || '');
                                
                                // Setează județul dacă există în lista de opțiuni
                                if (data.judet) {
                                    $('#company_county_modal option').each(function() {
                                        if ($(this).text().indexOf(data.judet) > -1) {
                                            $('#company_county_modal').val($(this).val());
                                            return false;
                                        }
                                    });
                                }
                                
                                $('#anaf_status_modal').html('✓ Date completate automat').css({background: '#f0fdf4', color: '#166534', border: '1px solid #bbf7d0'});
                                setTimeout(function() { $('#anaf_status_modal').fadeOut(); }, 2500);
                            } else {
                                $('#anaf_status_modal').html('✗ CUI negăsit în ANAF').css({background: '#fef2f2', color: '#991b1b', border: '1px solid #fecaca'});
                                setTimeout(function() { $('#anaf_status_modal').fadeOut(); }, 3000);
                            }
                        },
                        error: function() {
                            $('#anaf_status_modal').html('✗ Eroare la verificare').css({background: '#fef2f2', color: '#991b1b', border: '1px solid #fecaca'});
                            setTimeout(function() { $('#anaf_status_modal').fadeOut(); }, 3000);
                        }
                    });
                }, 800); // Delay de 800ms după ce utilizatorul termină de scris
            }
        });
        
        // Handler pentru închidere modale
        $(document).on('click', '.popup-close, .modal-close-btn, .modal-cancel-btn, .popup-overlay', function(e) {
            $(this).closest('.webgsm-popup').fadeOut(200);
        });
        
        // Handler pentru salvare adresă
        $(document).on('click', '#save_address_modal_btn', function(e) {
            e.preventDefault();
            var $btn = $(this);
            
            var data = {
                action: 'webgsm_save_address',
                nonce: (typeof webgsm_myaccount !== 'undefined') ? webgsm_myaccount.nonce : webgsm_checkout.nonce,
                index: $('#edit_address_index').val(),
                label: $('#modal_label').val(),
                name: $('#modal_name').val(),
                phone: $('#modal_phone').val(),
                address: $('#modal_address').val(),
                city: $('#modal_city').val(),
                county: $('#modal_county').val(),
                postcode: $('#modal_postcode').val()
            };
            
            $btn.prop('disabled', true).text('Se salvează...');
            
            $.ajax({
                url: (typeof webgsm_myaccount !== 'undefined') ? webgsm_myaccount.ajax_url : webgsm_checkout.ajax_url,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        alert('Adresa salvata cu succes!');
                        location.reload();
                    } else {
                        alert('Eroare: ' + (response.data || 'Nu s-a putut salva'));
                        $btn.prop('disabled', false).text('Salveaza');
                    }
                },
                error: function() {
                    alert('Eroare la comunicare cu serverul');
                    $btn.prop('disabled', false).text('Salveaza');
                }
            });
        });
        
        // Handler pentru salvare companie
        $(document).on('click', '#save_company_modal_btn', function(e) {
            e.preventDefault();
            var $btn = $(this);
            
            var data = {
                action: 'webgsm_save_company',
                nonce: (typeof webgsm_myaccount !== 'undefined') ? webgsm_myaccount.nonce : webgsm_checkout.nonce,
                index: $('#edit_company_index').val(),
                cui: $('#company_cui_modal').val(),
                name: $('#company_name_modal').val(),
                reg: $('#company_reg_modal').val(),
                phone: $('#company_phone_modal').val(),
                email: $('#company_email_modal').val(),
                address: $('#company_address_modal').val(),
                county: $('#company_county_modal').val(),
                city: $('#company_city_modal').val()
            };
            
            $btn.prop('disabled', true).text('Se salvează...');
            
            $.ajax({
                url: (typeof webgsm_myaccount !== 'undefined') ? webgsm_myaccount.ajax_url : webgsm_checkout.ajax_url,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        alert('Compania salvata cu succes!');
                        location.reload();
                    } else {
                        alert('Eroare: ' + (response.data || 'Nu s-a putut salva'));
                        $btn.prop('disabled', false).text('Salveaza');
                    }
                },
                error: function() {
                    alert('Eroare la comunicare cu serverul');
                    $btn.prop('disabled', false).text('Salveaza');
                }
            });
        });
        
        // Handler pentru salvare persoană
        $(document).on('click', '#save_person_modal_btn', function(e) {
            e.preventDefault();
            var $btn = $(this);
            
            var data = {
                action: 'webgsm_save_person',
                nonce: (typeof webgsm_myaccount !== 'undefined') ? webgsm_myaccount.nonce : webgsm_checkout.nonce,
                index: $('#edit_person_index').val(),
                name: $('#person_name_modal').val(),
                cnp: $('#person_cnp_modal').val(),
                phone: $('#person_phone_modal').val(),
                email: $('#person_email_modal').val(),
                address: $('#person_address_modal').val(),
                county: $('#person_county_modal').val(),
                city: $('#person_city_modal').val(),
                postcode: $('#person_postcode_modal').val()
            };
            
            $btn.prop('disabled', true).text('Se salvează...');
            
            $.ajax({
                url: (typeof webgsm_myaccount !== 'undefined') ? webgsm_myaccount.ajax_url : webgsm_checkout.ajax_url,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        alert('Persoana salvata cu succes!');
                        location.reload();
                    } else {
                        alert('Eroare: ' + (response.data || 'Nu s-a putut salva'));
                        $btn.prop('disabled', false).text('Salveaza');
                    }
                },
                error: function() {
                    alert('Eroare la comunicare cu serverul');
                    $btn.prop('disabled', false).text('Salveaza');
                }
            });
        });
        
        // Funcție generică pentru ștergere
        function deleteItem($btn, action, confirmMsg, itemType) {
            var index = $btn.data('index');
            
            console.log('[WebGSM] Delete ' + itemType + ', index:', index);
            console.log('[WebGSM] Button element:', $btn[0]);
            console.log('[WebGSM] Button HTML:', $btn[0].outerHTML);
            
            if (index === undefined || index === null) {
                console.error('[WebGSM] No index found on button');
                alert('Eroare: butonul nu are index valid. Verifică că butonul are atributul data-index.');
                return false;
            }
            
            if (!confirm(confirmMsg)) {
                return false;
            }
            
            // Determină nonce și ajax_url
            var nonce = '';
            var ajax_url = '';
            
            if (typeof webgsm_checkout !== 'undefined') {
                nonce = webgsm_checkout.nonce;
                ajax_url = webgsm_checkout.ajax_url;
            } else if (typeof webgsm_myaccount !== 'undefined') {
                nonce = webgsm_myaccount.nonce;
                ajax_url = webgsm_myaccount.ajax_url;
            }
            
            if (!nonce || !ajax_url) {
                alert('Eroare: configurație AJAX lipsă');
                console.error('[WebGSM] Missing nonce or ajax_url');
                return false;
            }
            
            $btn.prop('disabled', true).css('opacity', '0.5');
            
            $.ajax({
                url: ajax_url,
                type: 'POST',
                data: {
                    action: action,
                    index: index,
                    nonce: nonce
                },
                success: function(response) {
                    console.log('[WebGSM] Response:', response);
                    if (response.success) {
                        var $row = $btn.closest('tr');
                        if ($row.length) {
                            $row.fadeOut(300, function() { $(this).remove(); });
                        } else {
                            $btn.closest('.webgsm-radio, .address-item, .company-item, .person-item').fadeOut(300, function() { $(this).remove(); });
                        }
                    } else {
                        alert('Eroare: ' + (response.data || 'Operațiune eșuată'));
                        $btn.prop('disabled', false).css('opacity', '1');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('[WebGSM] AJAX error:', status, error);
                    alert('Eroare la comunicarea cu serverul');
                    $btn.prop('disabled', false).css('opacity', '1');
                }
            });
        }
    });
    </script>
    <?php
}, 999);

// AJAX handlers pentru ștergere
add_action('wp_ajax_webgsm_delete_address', function() {
    check_ajax_referer('webgsm_nonce', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error('Neautorizat');
    
    $user_id = get_current_user_id();
    $index = isset($_POST['index']) ? intval($_POST['index']) : -1;
    $addresses = get_user_meta($user_id, 'webgsm_addresses', true);
    
    if (!is_array($addresses)) $addresses = [];
    if ($index < 0 || $index >= count($addresses)) wp_send_json_error('Index invalid');
    
    array_splice($addresses, $index, 1);
    update_user_meta($user_id, 'webgsm_addresses', $addresses);
    
    wp_send_json_success(['message' => 'Adresa stearsa cu succes', 'addresses' => $addresses]);
}, 5);

add_action('wp_ajax_webgsm_delete_company', function() {
    error_log('[WebGSM] Delete company handler called');
    error_log('[WebGSM] POST data: ' . print_r($_POST, true));
    
    check_ajax_referer('webgsm_nonce', 'nonce');
    if (!is_user_logged_in()) {
        error_log('[WebGSM] User not logged in');
        wp_send_json_error('Neautorizat');
    }
    
    $user_id = get_current_user_id();
    $index = isset($_POST['index']) ? intval($_POST['index']) : -1;
    error_log('[WebGSM] Delete company - User ID: ' . $user_id . ', Index: ' . $index);
    
    $companies = get_user_meta($user_id, 'webgsm_companies', true);
    error_log('[WebGSM] Companies count: ' . (is_array($companies) ? count($companies) : 0));
    error_log('[WebGSM] Companies data: ' . print_r($companies, true));
    
    if (!is_array($companies)) $companies = [];
    if ($index < 0 || $index >= count($companies)) {
        error_log('[WebGSM] Invalid index - index: ' . $index . ', count: ' . count($companies));
        wp_send_json_error('Index invalid - primit: ' . $index . ', total: ' . count($companies));
    }
    
    array_splice($companies, $index, 1);
    update_user_meta($user_id, 'webgsm_companies', $companies);
    error_log('[WebGSM] Company deleted successfully, remaining: ' . count($companies));
    
    wp_send_json_success(['message' => 'Firma stearsa cu succes', 'companies' => $companies]);
}, 5);

add_action('wp_ajax_webgsm_delete_person', function() {
    error_log('[WebGSM] Delete person handler called');
    check_ajax_referer('webgsm_nonce', 'nonce');
    if (!is_user_logged_in()) {
        error_log('[WebGSM] User not logged in');
        wp_send_json_error('Neautorizat');
    }
    
    $user_id = get_current_user_id();
    $index = isset($_POST['index']) ? intval($_POST['index']) : -1;
    error_log('[WebGSM] Delete person index: ' . $index);
    $persons = get_user_meta($user_id, 'webgsm_persons', true);
    
    if (!is_array($persons)) $persons = [];
    if ($index < 0 || $index >= count($persons)) {
        error_log('[WebGSM] Invalid person index');
        wp_send_json_error('Index invalid');
    }
    
    array_splice($persons, $index, 1);
    update_user_meta($user_id, 'webgsm_persons', $persons);
    error_log('[WebGSM] Person deleted successfully');
    
    wp_send_json_success(['message' => 'Persoana stearsa cu succes', 'persons' => $persons]);
}, 5);

// AJAX handlers pentru salvare (adăugare/editare)
add_action('wp_ajax_webgsm_save_address', function() {
    check_ajax_referer('webgsm_nonce', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error('Neautorizat');
    
    $user_id = get_current_user_id();
    $addresses = get_user_meta($user_id, 'webgsm_addresses', true);
    if (!is_array($addresses)) $addresses = [];
    
    $address = [
        'label' => sanitize_text_field($_POST['label'] ?? ''),
        'name' => sanitize_text_field($_POST['name'] ?? ''),
        'phone' => sanitize_text_field($_POST['phone'] ?? ''),
        'address' => sanitize_text_field($_POST['address'] ?? ''),
        'city' => sanitize_text_field($_POST['city'] ?? ''),
        'county' => sanitize_text_field($_POST['county'] ?? ''),
        'postcode' => sanitize_text_field($_POST['postcode'] ?? '')
    ];
    
    // Validare câmpuri obligatorii
    if (empty($address['name']) || empty($address['phone']) || empty($address['address']) || empty($address['city'])) {
        wp_send_json_error('Campurile marcate cu * sunt obligatorii');
    }
    
    $index = isset($_POST['index']) && $_POST['index'] !== '' ? intval($_POST['index']) : -1;
    
    if ($index >= 0 && $index < count($addresses)) {
        // Editare
        $addresses[$index] = $address;
    } else {
        // Adăugare nouă
        $addresses[] = $address;
    }
    
    update_user_meta($user_id, 'webgsm_addresses', $addresses);
    wp_send_json_success(['message' => 'Adresa salvata cu succes', 'addresses' => $addresses]);
}, 5);

add_action('wp_ajax_webgsm_save_company', function() {
    check_ajax_referer('webgsm_nonce', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error('Neautorizat');
    
    $user_id = get_current_user_id();
    $companies = get_user_meta($user_id, 'webgsm_companies', true);
    if (!is_array($companies)) $companies = [];
    
    $company = [
        'cui' => sanitize_text_field($_POST['cui'] ?? ''),
        'name' => sanitize_text_field($_POST['name'] ?? ''),
        'reg' => sanitize_text_field($_POST['reg'] ?? ''),
        'phone' => sanitize_text_field($_POST['phone'] ?? ''),
        'email' => sanitize_email($_POST['email'] ?? ''),
        'address' => sanitize_text_field($_POST['address'] ?? ''),
        'county' => sanitize_text_field($_POST['county'] ?? ''),
        'city' => sanitize_text_field($_POST['city'] ?? '')
    ];
    
    // Validare câmpuri obligatorii
    if (empty($company['cui']) || empty($company['name']) || empty($company['reg']) || empty($company['phone']) || empty($company['email'])) {
        wp_send_json_error('Campurile marcate cu * sunt obligatorii');
    }
    
    $index = isset($_POST['index']) && $_POST['index'] !== '' ? intval($_POST['index']) : -1;
    
    if ($index >= 0 && $index < count($companies)) {
        // Editare
        $companies[$index] = $company;
    } else {
        // Adăugare nouă
        $companies[] = $company;
    }
    
    update_user_meta($user_id, 'webgsm_companies', $companies);
    wp_send_json_success(['message' => 'Compania salvata cu succes', 'companies' => $companies]);
}, 5);

add_action('wp_ajax_webgsm_save_person', function() {
    check_ajax_referer('webgsm_nonce', 'nonce');
    if (!is_user_logged_in()) wp_send_json_error('Neautorizat');
    
    $user_id = get_current_user_id();
    $persons = get_user_meta($user_id, 'webgsm_persons', true);
    if (!is_array($persons)) $persons = [];
    
    $person = [
        'name' => sanitize_text_field($_POST['name'] ?? ''),
        'cnp' => sanitize_text_field($_POST['cnp'] ?? ''),
        'phone' => sanitize_text_field($_POST['phone'] ?? ''),
        'email' => sanitize_email($_POST['email'] ?? ''),
        'address' => sanitize_text_field($_POST['address'] ?? ''),
        'county' => sanitize_text_field($_POST['county'] ?? ''),
        'city' => sanitize_text_field($_POST['city'] ?? ''),
        'postcode' => sanitize_text_field($_POST['postcode'] ?? '')
    ];
    
    // Validare câmpuri obligatorii
    if (empty($person['name']) || empty($person['phone']) || empty($person['email']) || empty($person['address'])) {
        wp_send_json_error('Campurile marcate cu * sunt obligatorii');
    }
    
    $index = isset($_POST['index']) && $_POST['index'] !== '' ? intval($_POST['index']) : -1;
    
    if ($index >= 0 && $index < count($persons)) {
        // Editare
        $persons[$index] = $person;
    } else {
        // Adăugare nouă
        $persons[] = $person;
    }
    
    update_user_meta($user_id, 'webgsm_persons', $persons);
    wp_send_json_success(['message' => 'Persoana salvata cu succes', 'persons' => $persons]);
}, 5);

// ==========================================
// STILIZARE ASTERISK ROȘU PENTRU CÂMPURI OBLIGATORII
// ==========================================
add_action('wp_footer', function() {
    if (!is_account_page()) return;
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Stilizează toate label-urile cu asterisk
        $('.webgsm-popup label').each(function() {
            var text = $(this).html();
            if (text.indexOf('*') !== -1) {
                $(this).html(text.replace(/\*/g, '<span style="color: #ef4444; font-weight: 600;">*</span>'));
            }
        });
    });
    </script>
    <?php
}, 999);
