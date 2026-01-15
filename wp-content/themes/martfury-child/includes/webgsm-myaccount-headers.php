<?php
/**
 * WebGSM My Account - Headere Grupuri
 * Adaugă structură pe categorii în meniu (ACHIZIȚIILE MELE, DATE SALVATE, etc.)
 * 
 * IMPORTANT: Păstrează layoutul existent, doar modifică meniul
 * 
 * @package WebGSM
 * @version 1.0.0
 */

if (!defined('ABSPATH')) exit;

// ==========================================
// MODIFICĂ ORDINEA MENIULUI + Elimină items
// ==========================================
add_filter('woocommerce_account_menu_items', function($items) {
    
    // Elimină items nedorite
    unset($items['downloads']); // Descarcari
    unset($items['edit-address']); // Adresa default WooCommerce
    unset($items['date-facturare']); // Date Facturare - inutil
    
    // Redenumeste items
    if (isset($items['dashboard'])) $items['dashboard'] = 'Panou control';
    if (isset($items['orders'])) $items['orders'] = 'Comenzi';
    if (isset($items['retururi'])) $items['retururi'] = 'Retururi';
    if (isset($items['garantie'])) $items['garantie'] = 'Garantie';
    if (isset($items['adrese-salvate'])) $items['adrese-salvate'] = 'Adrese';
    if (isset($items['date-facturare'])) $items['date-facturare'] = 'Date Facturare';
    if (isset($items['edit-account'])) $items['edit-account'] = 'Detalii cont';
    if (isset($items['customer-logout'])) $items['customer-logout'] = 'Iesire din cont';
    
    // Reordonează
    $order = [
        'dashboard',
        'orders',
        'retururi',
        'garantie',
        'adrese-salvate',
        'edit-account',
        'customer-logout'
    ];
    
    $sorted = [];
    foreach ($order as $key) {
        if (isset($items[$key])) {
            $sorted[$key] = $items[$key];
        }
    }
    
    return $sorted;
}, 999);

// ==========================================
// CSS + JAVASCRIPT pentru headere
// ==========================================
add_action('wp_footer', function() {
    if (!is_account_page()) return;
    ?>
    <style id="webgsm-menu-headers">
    /* Headere de grup - compact, fără spații suplimentare */
    .woocommerce-MyAccount-navigation li.menu-group-header {
        padding: 6px 12px !important;
        background: #f8f9fa !important;
        border-top: 0 !important;
        border-bottom: 1px solid #e8eaed !important;
        pointer-events: none !important;
        margin: 0 !important;
        line-height: 1.3 !important;
        min-height: 0 !important;
    }
    
    .woocommerce-MyAccount-navigation li.menu-group-header:first-child {
        margin: 0 !important;
        border-top: 0 !important;
    }
    
    .woocommerce-MyAccount-navigation li.menu-group-header span {
        font-size: 11px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.3px !important;
        color: #8a8f9c !important;
    }
    
    /* Items cu indentare */
    .woocommerce-MyAccount-navigation-link--orders a,
    .woocommerce-MyAccount-navigation-link--retururi a,
    .woocommerce-MyAccount-navigation-link--garantie a,
    .woocommerce-MyAccount-navigation-link--adrese-salvate a,
    .woocommerce-MyAccount-navigation-link--date-facturare a,
    .woocommerce-MyAccount-navigation-link--edit-account a {
        padding-left: 52px !important;
    }
    
    /* Elimină separatoarele vechi */
    .woocommerce-MyAccount-navigation-link--orders,
    .woocommerce-MyAccount-navigation-link--adrese-salvate,
    .woocommerce-MyAccount-navigation-link--edit-account,
    .woocommerce-MyAccount-navigation-link--customer-logout {
        border-top: none !important;
        margin-top: 0 !important;
    }
    </style>
    
    <script>
    (function() {
        // Așteaptă ca DOM-ul să fie complet încărcat
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', insertHeaders);
        } else {
            insertHeaders();
        }
        
        function insertHeaders() {
            var nav = document.querySelector('.woocommerce-MyAccount-navigation ul');
            if (!nav) {
                console.warn('Nav ul not found');
                return;
            }
            
            // Verifică dacă headerele au fost deja inserate
            if (nav.querySelector('.menu-group-header')) {
                console.log('Headers already inserted');
                return;
            }
            
            console.log('Inserting menu headers...');
            
            // ACHIZIȚIILE MELE - înainte de Comenzi
            var ordersLi = nav.querySelector('.woocommerce-MyAccount-navigation-link--orders');
            if (ordersLi) {
                var header1 = document.createElement('li');
                header1.className = 'menu-group-header';
                header1.innerHTML = '<span>ACHIZIȚIILE MELE</span>';
                ordersLi.parentNode.insertBefore(header1, ordersLi);
            }
            
            // DATE SALVATE - înainte de Adrese & Firme
            var adreseLi = nav.querySelector('.woocommerce-MyAccount-navigation-link--adrese-salvate');
            if (adreseLi) {
                var header2 = document.createElement('li');
                header2.className = 'menu-group-header';
                header2.innerHTML = '<span>DATE SALVATE</span>';
                adreseLi.parentNode.insertBefore(header2, adreseLi);
            }
            
            // SETĂRI - înainte de Detalii cont
            var editLi = nav.querySelector('.woocommerce-MyAccount-navigation-link--edit-account');
            if (editLi) {
                var header3 = document.createElement('li');
                header3.className = 'menu-group-header';
                header3.innerHTML = '<span>SETĂRI</span>';
                editLi.parentNode.insertBefore(header3, editLi);
            }
            
            // Adaugă simbolul ﹂ la items indentate
            var indentedSelectors = [
                '.woocommerce-MyAccount-navigation-link--orders',
                '.woocommerce-MyAccount-navigation-link--retururi',
                '.woocommerce-MyAccount-navigation-link--garantie',
                '.woocommerce-MyAccount-navigation-link--adrese-salvate',
                '.woocommerce-MyAccount-navigation-link--date-facturare',
                '.woocommerce-MyAccount-navigation-link--edit-account'
            ];
            
            indentedSelectors.forEach(function(selector) {
                var li = nav.querySelector(selector);
                if (li) {
                    var link = li.querySelector('a');
                    if (link && !link.textContent.trim().startsWith('﹂')) {
                        link.textContent = '﹂ ' + link.textContent.trim();
                    }
                }
            });
            
            console.log('✅ Menu headers inserted successfully!');
        }
    })();
    </script>
    <?php
}, 999);
