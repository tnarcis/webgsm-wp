<?php
/**
 * Plugin Name: WebGSM B2B Pricing
 * Description: Sistem de prețuri diferențiate pentru clienți B2B (Persoane Juridice) cu discount pe produs/categorie, tiers și protecție preț minim.
 * Version: 2.0.0
 * Author: WebGSM
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * Text Domain: webgsm-b2b
 */

if (!defined('ABSPATH')) exit;

// Verifică dacă WooCommerce este activ
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', function() {
        echo '<div class="error"><p><strong>WebGSM B2B Pricing</strong> necesită WooCommerce activ.</p></div>';
    });
    return;
}

// Constante
define('WEBGSM_B2B_VERSION', '2.1.0');
define('WEBGSM_B2B_PATH', plugin_dir_path(__FILE__));
define('WEBGSM_B2B_URL', plugin_dir_url(__FILE__));

// =========================================
// INCLUDE APPROVAL SYSTEM CLASSES
// =========================================
require_once WEBGSM_B2B_PATH . 'includes/class-file-upload.php';
require_once WEBGSM_B2B_PATH . 'includes/class-approval-system.php';

// Initialize approval system
WebGSM_B2B_Approval_System::instance();

// =========================================
// BADGES CSS - ELEGANT LINE-ART STYLE
// =========================================

add_action('wp_head', 'webgsm_b2b_badges_css');
function webgsm_b2b_badges_css() {
    ?>
    <style>
    /* ========================================
       WebGSM B2B Tier Badges - Elegant Design
       ======================================== */
    
    .webgsm-tier-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }
    
    .webgsm-tier-badge::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        transition: left 0.5s ease;
    }
    
    .webgsm-tier-badge:hover::before {
        left: 100%;
    }
    
    .webgsm-tier-badge svg {
        width: 14px;
        height: 14px;
        flex-shrink: 0;
        transition: all 0.3s ease;
    }
    
    /* BRONZE - Arămiu elegant */
    .webgsm-tier-badge.tier-bronze {
        background: linear-gradient(135deg, #d4a574 0%, #b8956e 50%, #a67c52 100%);
        color: #4a3728;
        border: 1px solid #c9a077;
        box-shadow: 0 2px 8px rgba(180, 140, 100, 0.25);
    }
    
    .webgsm-tier-badge.tier-bronze:hover {
        box-shadow: 0 4px 16px rgba(180, 140, 100, 0.4);
        transform: translateY(-1px);
    }
    
    .webgsm-tier-badge.tier-bronze svg {
        stroke: #5d4532;
    }
    
    /* SILVER - Argintiu strălucitor */
    .webgsm-tier-badge.tier-silver {
        background: linear-gradient(135deg, #e8e8e8 0%, #c0c0c0 50%, #a8a8a8 100%);
        color: #3d3d3d;
        border: 1px solid #d0d0d0;
        box-shadow: 0 2px 8px rgba(160, 160, 160, 0.3);
    }
    
    .webgsm-tier-badge.tier-silver:hover {
        box-shadow: 0 4px 16px rgba(160, 160, 160, 0.5);
        transform: translateY(-1px);
    }
    
    .webgsm-tier-badge.tier-silver svg {
        stroke: #505050;
    }
    
    /* GOLD - Auriu Luxury */
    .webgsm-tier-badge.tier-gold {
        background: linear-gradient(135deg, #f7e199 0%, #d4af37 50%, #c5a028 100%);
        color: #5c4813;
        border: 1px solid #dbb840;
        box-shadow: 0 2px 8px rgba(212, 175, 55, 0.35);
    }
    
    .webgsm-tier-badge.tier-gold:hover {
        box-shadow: 0 4px 16px rgba(212, 175, 55, 0.5);
        transform: translateY(-1px);
    }
    
    .webgsm-tier-badge.tier-gold svg {
        stroke: #6b5518;
    }
    
    /* PLATINUM - Exclusivist Deep Blue/Perlat */
    .webgsm-tier-badge.tier-platinum {
        background: linear-gradient(135deg, #2c3e50 0%, #1a252f 50%, #0d1318 100%);
        color: #e5e5e5;
        border: 1px solid #4a6073;
        box-shadow: 0 2px 8px rgba(44, 62, 80, 0.4);
    }
    
    .webgsm-tier-badge.tier-platinum:hover {
        box-shadow: 0 4px 16px rgba(44, 62, 80, 0.6);
        transform: translateY(-1px);
    }
    
    .webgsm-tier-badge.tier-platinum svg {
        stroke: #bdc3c7;
    }
    
    /* Badge în header - mai mic */
    .webgsm-tier-badge.badge-header {
        padding: 2px 8px;
        font-size: 9px;
        border-radius: 12px;
    }
    
    .webgsm-tier-badge.badge-header svg {
        width: 10px;
        height: 10px;
    }
    
    /* Badge în dashboard - mai mare */
    .webgsm-tier-badge.badge-dashboard {
        padding: 6px 16px;
        font-size: 13px;
        border-radius: 25px;
    }
    
    .webgsm-tier-badge.badge-dashboard svg {
        width: 18px;
        height: 18px;
    }
    
    /* ========================================
       Progress Bar - Elegant Design
       ======================================== */
    
    .webgsm-tier-progress-wrapper {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    }
    
    .webgsm-tier-progress-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
    }
    
    .webgsm-tier-progress-header h3 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
        color: #1f2937;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .webgsm-tier-progress-bar-container {
        background: #f3f4f6;
        border-radius: 10px;
        height: 12px;
        overflow: visible;
        position: relative;
    }
    
    .webgsm-tier-progress-bar {
        height: 100%;
        border-radius: 10px;
        transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }
    
    .webgsm-tier-progress-bar::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        animation: shimmer 2s infinite;
    }
    
    @keyframes shimmer {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }
    
    /* Progress bar colors by tier target */
    .webgsm-tier-progress-bar.to-silver {
        background: linear-gradient(90deg, #d4a574, #c0c0c0);
    }
    
    .webgsm-tier-progress-bar.to-gold {
        background: linear-gradient(90deg, #c0c0c0, #d4af37);
    }
    
    .webgsm-tier-progress-bar.to-platinum {
        background: linear-gradient(90deg, #d4af37, #2c3e50);
    }
    
    .webgsm-tier-progress-bar.max-tier {
        background: linear-gradient(90deg, #2c3e50, #1a252f);
    }
    
    .webgsm-tier-progress-info {
        display: flex;
        justify-content: space-between;
        margin-top: 12px;
        font-size: 13px;
        color: #6b7280;
    }
    
    .webgsm-tier-progress-info .current-value {
        font-weight: 600;
        color: #3b82f6;
    }
    
    .webgsm-tier-progress-info .next-tier {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .webgsm-tier-benefits {
        margin-top: 16px;
        padding-top: 16px;
        border-top: 1px solid #e5e7eb;
    }
    
    .webgsm-tier-benefits h4 {
        margin: 0 0 10px 0;
        font-size: 13px;
        font-weight: 600;
        color: #374151;
    }
    
    .webgsm-tier-benefits ul {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
    
    .webgsm-tier-benefits li {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-radius: 20px;
        font-size: 12px;
        color: #166534;
    }
    
    .webgsm-tier-benefits li svg {
        width: 12px;
        height: 12px;
        stroke: #22c55e;
    }
    
    /* ========================================
       Upgrade Notification - Pop-up
       ======================================== */
    
    .webgsm-tier-upgrade-popup {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 999999;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }
    
    .webgsm-tier-upgrade-popup.active {
        opacity: 1;
        visibility: visible;
    }
    
    .webgsm-tier-upgrade-content {
        background: #fff;
        border-radius: 16px;
        padding: 40px;
        max-width: 420px;
        text-align: center;
        box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        transform: scale(0.9);
        transition: transform 0.3s ease;
    }
    
    .webgsm-tier-upgrade-popup.active .webgsm-tier-upgrade-content {
        transform: scale(1);
    }
    
    .webgsm-tier-upgrade-content .celebration-icon {
        width: 80px;
        height: 80px;
        margin: 0 auto 20px;
        background: linear-gradient(135deg, #fef3c7, #fde68a);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        animation: celebrate 0.6s ease;
    }
    
    @keyframes celebrate {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.1); }
    }
    
    .webgsm-tier-upgrade-content .celebration-icon svg {
        width: 40px;
        height: 40px;
        stroke: #d97706;
    }
    
    .webgsm-tier-upgrade-content h2 {
        margin: 0 0 10px;
        font-size: 24px;
        color: #1f2937;
    }
    
    .webgsm-tier-upgrade-content p {
        margin: 0 0 20px;
        color: #6b7280;
        line-height: 1.6;
    }
    
    .webgsm-tier-upgrade-content .new-badge {
        margin: 20px 0;
    }
    
    .webgsm-tier-upgrade-content .close-btn {
        background: #3b82f6;
        color: #fff;
        border: none;
        padding: 12px 32px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .webgsm-tier-upgrade-content .close-btn:hover {
        background: #2563eb;
        transform: translateY(-2px);
    }
    
    /* ========================================
       Cart Table Header - Gradient Gri Stilizat
       ======================================== */
    
    /* Header tabel cart cu gradient gri elegant și linii orizontale verzi */
    .woocommerce-cart-form table.shop_table thead,
    .woocommerce-cart table.shop_table thead {
        background: linear-gradient(135deg, #e8eaed 0%, #f8f9fa 50%, #e8eaed 100%) !important;
        border-top: 1px solid #2ecc71 !important;
        border-bottom: 2px solid #2ecc71 !important;
    }
    
    .woocommerce-cart-form table.shop_table thead th,
    .woocommerce-cart table.shop_table thead th {
        padding: 16px 18px !important;
        text-align: left !important;
        font-weight: 700 !important;
        color: #2c3e50 !important;
        font-size: 12px !important;
        text-transform: uppercase !important;
        letter-spacing: 0.8px !important;
        border-right: none !important;
        border-bottom: none !important;
        border-top: none !important;
        font-family: 'Segoe UI', 'Helvetica Neue', sans-serif !important;
        background: transparent !important;
    }
    
    /* Aliniere coloane pentru consistență */
    .woocommerce-cart-form table.shop_table thead th.product-name,
    .woocommerce-cart table.shop_table thead th.product-name {
        text-align: left !important;
        padding-left: 24px !important;
    }
    
    .woocommerce-cart-form table.shop_table thead th.product-price,
    .woocommerce-cart table.shop_table thead th.product-price {
        text-align: center !important;
    }
    
    .woocommerce-cart-form table.shop_table thead th.product-quantity,
    .woocommerce-cart table.shop_table thead th.product-quantity {
        text-align: center !important;
    }
    
    .woocommerce-cart-form table.shop_table thead th.product-subtotal,
    .woocommerce-cart table.shop_table thead th.product-subtotal {
        text-align: right !important;
        padding-right: 24px !important;
    }
    
    /* Aliniere conținut tabel (TD) cu header-ul (TH) */
    .woocommerce-cart-form table.shop_table tbody td.product-name,
    .woocommerce-cart table.shop_table tbody td.product-name {
        padding-left: 24px !important;
    }
    
    .woocommerce-cart-form table.shop_table tbody td.product-price,
    .woocommerce-cart table.shop_table tbody td.product-price {
        text-align: center !important;
    }
    
    .woocommerce-cart-form table.shop_table tbody td.product-quantity,
    .woocommerce-cart table.shop_table tbody td.product-quantity {
        text-align: center !important;
    }
    
    .woocommerce-cart-form table.shop_table tbody td.product-subtotal,
    .woocommerce-cart table.shop_table tbody td.product-subtotal {
        text-align: right !important;
        padding-right: 24px !important;
    }
    
    /* ========================================
       B2B Price Display - Cart/Checkout
       ======================================== */
    
    /* TOTAL în roșu, bold, mare - după rândurile B2B */
    .woocommerce-cart-form__contents .order-total th,
    .woocommerce-checkout-review-order-table .order-total th,
    .cart_totals .order-total th,
    .woocommerce-checkout #order_review .order-total th {
        color: #dc2626 !important;
        font-weight: 700 !important;
        font-size: 18px !important;
    }
    
    .woocommerce-cart-form__contents .order-total td,
    .woocommerce-checkout-review-order-table .order-total td,
    .cart_totals .order-total td,
    .woocommerce-checkout #order_review .order-total td {
        color: #dc2626 !important;
        font-weight: 700 !important;
        font-size: 20px !important;
    }
    
    .woocommerce-cart-form__contents .order-total .woocommerce-Price-amount,
    .woocommerce-checkout-review-order-table .order-total .woocommerce-Price-amount,
    .cart_totals .order-total .woocommerce-Price-amount,
    .woocommerce-checkout #order_review .order-total .woocommerce-Price-amount {
        color: #dc2626 !important;
    }
    
    /* ========================================
       B2B Badge - Animație Shadow per Tier
       ======================================== */
    
    .webgsm-b2b-badge {
        position: relative;
        overflow: hidden;
    }
    
    /* Animație shadow care trece peste badge la scroll/load */
    @keyframes tierShadowSweep {
        0% {
            transform: translateX(-100%) skewX(-15deg);
            opacity: 0;
        }
        50% {
            opacity: 0.6;
        }
        100% {
            transform: translateX(200%) skewX(-15deg);
            opacity: 0;
        }
    }
    
    .webgsm-b2b-badge::after {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 50%;
        height: 200%;
        background: linear-gradient(90deg, transparent, currentColor, transparent);
        opacity: 0;
    }
    
    /* Trigger animație la page load */
    .webgsm-b2b-badge.animate-tier::after {
        animation: tierShadowSweep 1.2s ease-out 0.3s;
    }
    
    /* Trigger animație la scroll în viewport */
    .webgsm-b2b-badge.in-view::after {
        animation: tierShadowSweep 1.2s ease-out;
    }
    
    /* Culori shadow per tier */
    .webgsm-b2b-badge.tier-bronze::after {
        color: #d4a574;
    }
    
    .webgsm-b2b-badge.tier-silver::after {
        color: #c0c0c0;
    }
    
    .webgsm-b2b-badge.tier-gold::after {
        color: #d4af37;
    }
    
    .webgsm-b2b-badge.tier-platinum::after {
        color: #4a6073;
    }
    
    /* ========================================
       Cart Mobile - Stilizare X și Aliniere
       ======================================== */
    
    @media (max-width: 768px) {
        /* X pentru ștergere produs - simplu, mic, roșu pal */
        .woocommerce-cart-form table.shop_table .product-remove a,
        .woocommerce-cart table.shop_table .product-remove a {
            display: inline-block !important;
            color: #f87171 !important;
            font-size: 18px !important;
            font-weight: 400 !important;
            text-decoration: none !important;
            transition: color 0.2s ease !important;
            line-height: 1 !important;
            background: none !important;
            border: none !important;
            padding: 0 !important;
            width: auto !important;
            height: auto !important;
        }
        
        .woocommerce-cart-form table.shop_table .product-remove a:hover,
        .woocommerce-cart table.shop_table .product-remove a:hover {
            color: #dc2626 !important;
        }
        
        /* Pune X pe aceeași linie cu cantitatea */
        .woocommerce-cart-form table.shop_table td.product-quantity,
        .woocommerce-cart table.shop_table td.product-quantity {
            display: flex !important;
            align-items: center !important;
            gap: 12px !important;
            justify-content: center !important;
        }
        
        .woocommerce-cart-form table.shop_table td.product-remove,
        .woocommerce-cart table.shop_table td.product-remove {
            display: none !important;
        }
        
        /* Mutăm butonul remove în quantity */
        .woocommerce-cart-form table.shop_table td.product-quantity::before,
        .woocommerce-cart table.shop_table td.product-quantity::before {
            content: '';
        }
        
        /* Aliniere căsuțe pe mobile */
        .woocommerce-cart-form table.shop_table td,
        .woocommerce-cart table.shop_table td {
            vertical-align: middle !important;
            padding: 12px 8px !important;
        }
        
        .woocommerce-cart-form table.shop_table td.product-name,
        .woocommerce-cart table.shop_table td.product-name {
            padding-left: 12px !important;
        }
        
        /* Imagine produs mai mică pe mobile */
        .woocommerce-cart-form table.shop_table td.product-thumbnail img,
        .woocommerce-cart table.shop_table td.product-thumbnail img {
            max-width: 60px !important;
            height: auto !important;
        }
        
        .woocommerce-cart-form table.shop_table td.product-thumbnail,
        .woocommerce-cart table.shop_table td.product-thumbnail {
            padding: 12px 8px !important;
        }
        
        /* Input cantitate mai mic pe mobile */
        .woocommerce-cart-form table.shop_table td.product-quantity input.qty,
        .woocommerce-cart table.shop_table td.product-quantity input.qty {
            max-width: 60px !important;
            padding: 6px !important;
            font-size: 14px !important;
        }
        
        /* Prețuri aliniate pe mobile */
        .woocommerce-cart-form table.shop_table td.product-price,
        .woocommerce-cart table.shop_table td.product-price,
        .woocommerce-cart-form table.shop_table td.product-subtotal,
        .woocommerce-cart table.shop_table td.product-subtotal {
            font-size: 14px !important;
            font-weight: 600 !important;
        }
    }
    </style>
    
    <script>
    // Animație shadow la scroll pentru badge-uri B2B
    document.addEventListener('DOMContentLoaded', function() {
        // Animație la page load
        const badges = document.querySelectorAll('.webgsm-b2b-badge');
        badges.forEach(badge => {
            badge.classList.add('animate-tier');
        });
        
        // Intersection Observer pentru animație la scroll
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !entry.target.classList.contains('animated')) {
                    entry.target.classList.add('in-view');
                    entry.target.classList.add('animated');
                    
                    // Reset după animație pentru a putea re-anima
                    setTimeout(() => {
                        entry.target.classList.remove('in-view');
                    }, 1200);
                }
            });
        }, {
            threshold: 0.5,
            rootMargin: '0px'
        });
        
        badges.forEach(badge => observer.observe(badge));
        
        // Traducere header cart în română fără diacritice
        jQuery(document).ready(function($) {
            // Traducere header
            var cartHeaderTranslations = {
                'Product': 'Produs',
                'Price': 'Pret',
                'Quantity': 'Cantitate',
                'Subtotal': 'Total'
            };
            
            $('.woocommerce-cart-form table.shop_table thead th, .woocommerce-cart table.shop_table thead th').each(function() {
                var $th = $(this);
                var originalText = $th.text().trim();
                
                if (cartHeaderTranslations[originalText]) {
                    $th.text(cartHeaderTranslations[originalText]);
                }
            });
            
            // Pe mobile: mută X-ul în coloana cantitate
            function moveRemoveButtonMobile() {
                if ($(window).width() <= 768) {
                    $('.woocommerce-cart-form table.shop_table tbody tr, .woocommerce-cart table.shop_table tbody tr').each(function() {
                        var $row = $(this);
                        var $removeBtn = $row.find('.product-remove a');
                        var $quantityCell = $row.find('.product-quantity');
                        
                        if ($removeBtn.length && $quantityCell.length && !$quantityCell.find('.product-remove a').length) {
                            var $removeBtnClone = $removeBtn.clone();
                            $quantityCell.prepend($removeBtnClone);
                        }
                    });
                }
            }
            
            moveRemoveButtonMobile();
            
            // Re-run după AJAX updates
            $(document.body).on('updated_cart_totals updated_checkout', function() {
                moveRemoveButtonMobile();
            });
        });
        
        // Auto-update cart când se modifică cantitatea
        jQuery(document).ready(function($) {
            // Pentru pagina Cart
            var cartTimeout;
            $(document.body).on('change input', 'input.qty, .cart input[type="number"]', function() {
                clearTimeout(cartTimeout);
                cartTimeout = setTimeout(function() {
                    $('[name="update_cart"]').prop('disabled', false);
                    $('[name="update_cart"]').trigger('click');
                }, 1000);
            });
            
            // Pentru mini-cart (dacă există input de cantitate)
            $(document.body).on('change', '.widget_shopping_cart input.qty', function() {
                var $input = $(this);
                var cartItemKey = $input.attr('name').replace(/cart\[(\w+)\]\[qty\]/g, "$1");
                var quantity = $input.val();
                
                $.ajax({
                    type: 'POST',
                    url: wc_cart_fragments_params.ajax_url,
                    data: {
                        action: 'webgsm_update_cart_quantity',
                        cart_item_key: cartItemKey,
                        quantity: quantity
                    },
                    success: function(response) {
                        $(document.body).trigger('wc_fragment_refresh');
                    }
                });
            });
            
            // Refresh fragments după update
            $(document.body).on('updated_cart_totals', function() {
                $(document.body).trigger('wc_fragment_refresh');
            });
        });
    });
    </script>
    <?php
}

// =========================================
// HELPER: Generează Badge HTML
// =========================================

function webgsm_get_tier_badge($tier, $size = 'default') {
    $tiers_config = array(
        'bronze' => array(
            'label' => 'Bronze',
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z"/></svg>'
        ),
        'silver' => array(
            'label' => 'Silver',
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/></svg>'
        ),
        'gold' => array(
            'label' => 'Gold',
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 01-.982-3.172M9.497 14.25a7.454 7.454 0 00.981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 007.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 002.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 012.916.52 6.003 6.003 0 01-5.395 4.972m0 0a6.726 6.726 0 01-2.749 1.35m0 0a6.772 6.772 0 01-3.044 0"/></svg>'
        ),
        'platinum' => array(
            'label' => 'Platinum',
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3l2.5 5.5L20 9.5l-4 4.5 1 6-5-3-5 3 1-6-4-4.5 5.5-1L12 3z"/></svg>'
        )
    );
    
    $tier = strtolower($tier);
    if (!isset($tiers_config[$tier])) {
        $tier = 'bronze';
    }
    
    $config = $tiers_config[$tier];
    $size_class = ($size === 'header') ? 'badge-header' : (($size === 'dashboard') ? 'badge-dashboard' : '');
    
    return sprintf(
        '<span class="webgsm-tier-badge tier-%s %s">%s %s</span>',
        esc_attr($tier),
        esc_attr($size_class),
        $config['icon'],
        esc_html($config['label'])
    );
}

// =========================================
// HELPER: Generează Progress Bar HTML
// =========================================

function webgsm_get_tier_progress_bar($user_id = null) {
    if (is_null($user_id)) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return '';
    }
    
    $b2b = WebGSM_B2B_Pricing::instance();
    
    // Verifică dacă e PJ
    if (!$b2b->is_user_pj($user_id)) {
        return '';
    }
    
    $current_tier = $b2b->get_user_tier($user_id);
    $total_value = $b2b->get_user_total_value($user_id);
    $tiers = get_option('webgsm_b2b_tiers', $b2b->get_default_tiers());
    
    // Găsește next tier
    $next_tier = null;
    $next_tier_value = 0;
    $current_tier_value = 0;
    $tier_order = array('bronze', 'silver', 'gold', 'platinum');
    $current_index = array_search($current_tier, $tier_order);
    
    foreach ($tiers as $slug => $tier_data) {
        if ($slug === $current_tier) {
            $current_tier_value = isset($tier_data['min_value']) ? (float)$tier_data['min_value'] : 0;
        }
    }
    
    if ($current_index !== false && $current_index < count($tier_order) - 1) {
        $next_tier_slug = $tier_order[$current_index + 1];
        if (isset($tiers[$next_tier_slug])) {
            $next_tier = $tiers[$next_tier_slug];
            $next_tier_value = isset($next_tier['min_value']) ? (float)$next_tier['min_value'] : 0;
        }
    }
    
    // Calculează progresul
    $progress = 100;
    $remaining = 0;
    $progress_class = 'max-tier';
    
    if ($next_tier && $next_tier_value > 0) {
        $range = $next_tier_value - $current_tier_value;
        $progress_in_range = $total_value - $current_tier_value;
        $progress = min(100, max(0, ($progress_in_range / $range) * 100));
        $remaining = max(0, $next_tier_value - $total_value);
        $progress_class = 'to-' . $tier_order[$current_index + 1];
    }
    
    // Discount curent
    $discount_extra = isset($tiers[$current_tier]['discount_extra']) ? $tiers[$current_tier]['discount_extra'] : 0;
    
    ob_start();
    ?>
    <div class="webgsm-tier-progress-wrapper">
        <div class="webgsm-tier-progress-header">
            <h3>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941"/>
                </svg>
                Nivelul tău de Partener
            </h3>
            <?php echo webgsm_get_tier_badge($current_tier, 'dashboard'); ?>
        </div>
        
        <div class="webgsm-tier-progress-bar-container" style="position: relative; margin-bottom: 30px;">
            <div class="webgsm-tier-progress-bar <?php echo esc_attr($progress_class); ?>" style="width: <?php echo esc_attr($progress); ?>%;"></div>
            
            <?php if ($next_tier): 
                $next_discount = isset($next_tier['discount_extra']) ? $next_tier['discount_extra'] : 0;
                // Culoare tier pentru border
                $next_tier_slug = $tier_order[$current_index + 1];
                $tier_colors = array(
                    'bronze' => '#d4a574',
                    'silver' => '#c0c0c0',
                    'gold' => '#d4af37',
                    'platinum' => '#4a6073'
                );
                $next_tier_color = isset($tier_colors[$next_tier_slug]) ? $tier_colors[$next_tier_slug] : '#3b82f6';
            ?>
            <!-- Etichetă Recompensă - Line Art Elegant, Vizibil -->
            <div style="position: absolute; top: -24px; right: 0; background: #fff; color: <?php echo esc_attr($next_tier_color); ?>; padding: 3px 8px; border: 1.5px solid <?php echo esc_attr($next_tier_color); ?>; border-radius: 12px; font-size: 10px; font-weight: 600; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; white-space: nowrap; box-shadow: 0 2px 6px rgba(0,0,0,0.12); letter-spacing: 0.3px;">
                EXTRA <?php echo esc_html($next_discount); ?>%
            </div>
            <?php endif; ?>
        </div>
        
        <div class="webgsm-tier-progress-info">
            <span>
                Total comenzi: <span class="current-value"><?php echo number_format($total_value, 0, ',', '.'); ?> RON</span>
            </span>
            <?php if ($next_tier): ?>
            <span class="next-tier">
                <?php echo webgsm_get_tier_badge($tier_order[$current_index + 1], 'header'); ?>
                <span>Mai ai nevoie de <strong><?php echo number_format($remaining, 0, ',', '.'); ?> RON</strong></span>
            </span>
            <?php else: ?>
            <span class="next-tier">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <strong>Nivel maxim atins!</strong>
            </span>
            <?php endif; ?>
        </div>
        
        <div class="webgsm-tier-benefits">
            <h4>Beneficiile tale active:</h4>
            <ul>
                <?php if ($discount_extra > 0): ?>
                <li>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Discount extra <?php echo esc_html($discount_extra); ?>%
                </li>
                <?php endif; ?>
                <li>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Prețuri B2B exclusive
                </li>
                <?php if (in_array($current_tier, array('gold', 'platinum'))): ?>
                <li>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Prioritate suport
                </li>
                <?php endif; ?>
                <?php if ($current_tier === 'platinum'): ?>
                <li>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Account Manager dedicat
                </li>
                <?php endif; ?>
            </ul>
        </div>
        
        <?php if ($next_tier): 
            $next_discount = isset($next_tier['discount_extra']) ? $next_tier['discount_extra'] : 0;
            $next_tier_slug = $tier_order[$current_index + 1];
            $next_tier_label = isset($next_tier['label']) ? $next_tier['label'] : ucfirst($next_tier_slug);
        ?>
        <!-- Text Explicativ - Transparență -->
        <div style="margin-top: 16px; padding-top: 12px; border-top: 1px solid #e5e7eb;">
            <p style="margin: 0; font-size: 11px; color: #6b7280; line-height: 1.5; font-style: italic;">
                La nivelul <strong style="color: #374151;"><?php echo esc_html($next_tier_label); ?></strong>, primești un discount extra de <strong style="color: #374151;"><?php echo esc_html($next_discount); ?>%</strong> aplicat direct peste prețurile tale exclusive B2B. 
                <span style="color: #9ca3af;">Discount-urile per nivel nu se cumulează - la fiecare nivel atins, procentul se actualizează la valoarea corespunzătoare.</span>
            </p>
        </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

// =========================================
// UPGRADE NOTIFICATION - POP-UP
// =========================================

add_action('wp_footer', 'webgsm_show_tier_upgrade_popup');
function webgsm_show_tier_upgrade_popup() {
    if (!is_user_logged_in()) return;
    
    $user_id = get_current_user_id();
    $show_popup = get_user_meta($user_id, '_webgsm_show_tier_upgrade', true);
    $new_tier = get_user_meta($user_id, '_webgsm_new_tier', true);
    
    if ($show_popup !== 'yes' || empty($new_tier)) return;
    
    // Marchează ca văzut
    delete_user_meta($user_id, '_webgsm_show_tier_upgrade');
    delete_user_meta($user_id, '_webgsm_new_tier');
    ?>
    <div class="webgsm-tier-upgrade-popup active" id="webgsm-upgrade-popup">
        <div class="webgsm-tier-upgrade-content">
            <div class="celebration-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/>
                </svg>
            </div>
            <h2>Felicitări! 🎉</h2>
            <p>Ai fost promovat la un nivel superior de parteneriat!</p>
            <div class="new-badge">
                <?php echo webgsm_get_tier_badge($new_tier, 'dashboard'); ?>
            </div>
            <p>Beneficiile tale au fost actualizate automat.</p>
            <button class="close-btn" onclick="document.getElementById('webgsm-upgrade-popup').classList.remove('active');">
                Mulțumesc!
            </button>
        </div>
    </div>
    <script>
    setTimeout(function() {
        var popup = document.getElementById('webgsm-upgrade-popup');
        if (popup) popup.classList.remove('active');
    }, 10000);
    </script>
    <?php
}

/**
 * Clasa principală WebGSM B2B Pricing
 */
class WebGSM_B2B_Pricing {
    
    private static $instance = null;
    
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        $this->init_hooks();
        
        // Debug buttons doar pentru admin
        if (current_user_can('manage_options')) {
            add_action('wp_footer', array($this, 'debug_show_pj_status'));
            add_action('wp_footer', array($this, 'debug_set_pj_button'));
        }
    }
    
    private function init_hooks() {
        // Admin hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'admin_assets'));
        
        // Product meta fields
        add_action('woocommerce_product_options_pricing', array($this, 'add_product_pricing_fields'));
        add_action('woocommerce_process_product_meta', array($this, 'save_product_pricing_fields'));
        add_action('admin_footer', array($this, 'sync_pret_achizitie_fields_script'));
        
        // Category meta fields
        add_action('product_cat_add_form_fields', array($this, 'add_category_fields'));
        add_action('product_cat_edit_form_fields', array($this, 'edit_category_fields'));
        add_action('created_product_cat', array($this, 'save_category_fields'));
        add_action('edited_product_cat', array($this, 'save_category_fields'));
        
        // Price filters - NUCLEUL SISTEMULUI
        add_filter('woocommerce_product_get_price', array($this, 'apply_b2b_price'), 99, 2);
        add_filter('woocommerce_product_get_regular_price', array($this, 'apply_b2b_price'), 99, 2);
        add_filter('woocommerce_product_variation_get_price', array($this, 'apply_b2b_price'), 99, 2);
        add_filter('woocommerce_product_variation_get_regular_price', array($this, 'apply_b2b_price'), 99, 2);
        
        // Price HTML display
        add_filter('woocommerce_get_price_html', array($this, 'modify_price_html'), 9999, 2);
        
        // Display discount info în cart
        add_filter('woocommerce_cart_item_price', array($this, 'display_cart_item_tier_price'), 10, 3);
        
        // Display B2B discount în cart (checkout e custom în webgsm-checkout-pro)
        add_action('woocommerce_cart_totals_after_order_total', array($this, 'display_b2b_savings_row'), 10);
        
        // Update user tier after order completed
        add_action('woocommerce_order_status_completed', array($this, 'update_user_tier_on_order'));
        
        // INVALIDARE CACHE la ANULARE comenzi
        add_action('woocommerce_order_status_cancelled', array($this, 'invalidate_user_tier_cache'));
        add_action('woocommerce_order_status_refunded', array($this, 'invalidate_user_tier_cache'));
        add_action('woocommerce_order_status_failed', array($this, 'invalidate_user_tier_cache'));
        
        // INVALIDARE CACHE la ȘTERGERE comandă
        add_action('before_delete_post', array($this, 'invalidate_tier_on_delete'));
        add_action('trashed_post', array($this, 'invalidate_tier_on_delete'));
        
        // INVALIDARE CACHE la SCHIMBARE STATUS (din completed în altceva)
        add_action('woocommerce_order_status_changed', array($this, 'invalidate_tier_on_status_change'), 10, 4);
        
        // Admin columns for orders
        add_filter('manage_edit-shop_order_columns', array($this, 'add_order_profit_column'));
        add_action('manage_shop_order_posts_custom_column', array($this, 'render_order_profit_column'), 10, 2);
        
        // ÎNREGISTRARE CONT - Doar detectare PJ
        add_action('woocommerce_created_customer', array($this, 'detect_pj_on_registration'), 20);
        
        // Show pending approval message on My Account
        add_action('woocommerce_account_dashboard', array($this, 'show_pending_approval_message'), 5);
        
        // Debugging în footer (doar admin)
        if (current_user_can('manage_options')) {
            add_action('wp_footer', array($this, 'add_console_debugging'));
        }
        
        // AJAX pentru update cantitate în cart
        add_action('wp_ajax_webgsm_update_cart_quantity', array($this, 'ajax_update_cart_quantity'));
        add_action('wp_ajax_nopriv_webgsm_update_cart_quantity', array($this, 'ajax_update_cart_quantity'));
        
        // AJAX Debug (TEMPORAR)
        add_action('wp_ajax_webgsm_debug_tier', array($this, 'ajax_debug_tier'));
        
        // Pe live: evită cache FPC pentru utilizatori logați (prețuri B2B / preț verde)
        add_action('send_headers', array($this, 'no_cache_for_logged_in_users'), 1);
        
        // Diagnostic pe live: ?webgsm_b2b_debug=1 (doar pentru utilizatori logați)
        add_action('wp_footer', array($this, 'maybe_show_b2b_debug'), 5);
    }
    
    /**
     * Trimite headere anti-cache când utilizatorul e logat, ca pe live (FPC/LiteSpeed etc.)
     * să nu servească o pagină cache-uită fără preț B2B / preț verde.
     */
    public function no_cache_for_logged_in_users() {
        if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
            return;
        }
        if (!is_user_logged_in()) {
            return;
        }
        if (headers_sent()) {
            return;
        }
        header('Cache-Control: private, no-cache, no-store, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        // LiteSpeed Server: semnal să nu cache-uiască (preț B2B diferit per user)
        @header('X-LiteSpeed-Cache-Control: no-cache', false);
    }
    
    /**
     * Afișează diagnostic B2B în footer când URL conține webgsm_b2b_debug=1 (utilizator logat).
     * Folosește pe live pentru a vedea de ce nu apar prețul verde / B2B.
     */
    public function maybe_show_b2b_debug() {
        if (!isset($_GET['webgsm_b2b_debug']) || $_GET['webgsm_b2b_debug'] !== '1') {
            return;
        }
        if (!is_user_logged_in()) {
            return;
        }
        $user_id = get_current_user_id();
        $b2b_status = get_user_meta($user_id, '_b2b_status', true);
        $is_pj_meta = get_user_meta($user_id, '_is_pj', true);
        $billing_cui = get_user_meta($user_id, 'billing_cui', true);
        $tip_client = get_user_meta($user_id, '_tip_client', true);
        $user = get_userdata($user_id);
        $roles = $user ? (array) $user->roles : array();
        $is_pj = $this->is_user_pj($user_id);
        $tier = $this->get_user_tier();
        $reasons = array();
        if ($b2b_status === 'pending') {
            $reasons[] = 'Cont B2B în așteptare (_b2b_status=pending) – trebuie aprobat.';
        }
        if (!$is_pj && empty($billing_cui) && $is_pj_meta !== 'yes' && $is_pj_meta !== '1' && strtolower($tip_client) !== 'pj' && strtolower($tip_client) !== 'juridica' && !in_array('b2b_customer', $roles) && !in_array('wholesale_customer', $roles)) {
            $reasons[] = 'Utilizatorul nu e marcat ca PJ (lipsește CUI, _is_pj, _tip_client sau rol B2B).';
        }
        if ($is_pj && empty($tier)) {
            $reasons[] = 'Tier necalculat (se actualizează după prima comandă completă).';
        }
        $reasons[] = 'Dacă totul e OK aici dar pe pagină nu vezi B2B, dezactivează cache-ul full-page (LiteSpeed / Cloudflare etc.) pentru utilizatori logați.';
        
        // Valorile din DB pe care le folosește codul – ca să vezi diferența local vs live
        $opt_implicit_raw = get_option('webgsm_b2b_discount_implicit', 'NOT_SET');
        $tiers_config = get_option('webgsm_b2b_tiers', array());
        $tier_extra = $tier && isset($tiers_config[$tier]['discount_extra']) ? $tiers_config[$tier]['discount_extra'] : '—';
        
        echo '<div id="webgsm-b2b-debug" style="position:fixed;bottom:0;left:0;right:0;background:#1e293b;color:#e2e8f0;padding:12px 16px;font-size:12px;font-family:monospace;z-index:99999;max-height:280px;overflow:auto;border-top:2px solid #22c55e;">';
        echo '<strong style="color:#22c55e;">[WebGSM B2B Debug]</strong> ';
        echo 'user_id=' . (int) $user_id . ' | is_pj=' . ($is_pj ? 'DA' : 'NU') . ' | tier=' . esc_html($tier ?: '—') . ' | _b2b_status=' . esc_html($b2b_status ?: '—') . ' | _is_pj=' . esc_html($is_pj_meta ?: '—') . ' | billing_cui=' . (strlen((string)$billing_cui) ? 'set' : '—') . ' | _tip_client=' . esc_html($tip_client ?: '—') . ' | roles=' . esc_html(implode(',', $roles)) . '<br>';
        echo '<strong style="color:#93c5fd;">Setări (din DB):</strong> discount_implicit_option=' . esc_html(var_export($opt_implicit_raw, true)) . ' | tier_' . esc_html($tier ?: '') . '_extra=' . esc_html($tier_extra) . '<br>';
        
        // Pe pagina de produs: afișează cum se calculează discountul pentru produsul curent
        global $product;
        if ($is_pj && $product && is_a($product, 'WC_Product')) {
            $pj_info = $this->get_discount_pj($product, true);
            $discount_pj_val = is_array($pj_info) ? $pj_info['discount'] : $pj_info;
            $source = is_array($pj_info) ? $pj_info['source'] : '—';
            $tier_extra_float = $tier && isset($tiers_config[$tier]['discount_extra']) ? (float) $tiers_config[$tier]['discount_extra'] : 0;
            $total_pct = $discount_pj_val + $tier_extra_float;
            echo '<strong style="color:#93c5fd;">Acest produs (#' . (int) $product->get_id() . '):</strong> discount_pj=' . esc_html((string) $discount_pj_val) . '% (sursă: ' . esc_html($source) . ') + tier_extra=' . esc_html((string) $tier_extra_float) . '% → total=' . esc_html((string) $total_pct) . '%<br>';
        }
        
        echo '<span style="color:#fbbf24;">' . esc_html(implode(' ', $reasons)) . '</span>';
        echo '</div>';
    }
    
    // =========================================
    // DEBUGGING
    // =========================================
    
    public function add_console_debugging() {
        if (!current_user_can('manage_options')) return;
        
        $user_id = get_current_user_id();
        $is_pj = $this->is_user_pj() ? 'true' : 'false';
        $tier = $this->get_user_tier() ?: 'none';
        $total_value = $this->get_user_total_value($user_id);
        $discount_implicit = get_option('webgsm_b2b_discount_implicit', 0);
        $tiers = get_option('webgsm_b2b_tiers', $this->get_default_tiers());
        
        // Verifică invalidări recente
        $last_invalidation = get_user_meta($user_id, '_pj_last_invalidation', true);
        
        // Notificare informativă: prețurile și nivelul au fost actualizate (nu e eroare)
        if ($last_invalidation && (time() - $last_invalidation) < 300) {
            ?>
            <div id="webgsm-cache-notice" style="position:fixed;top:60px;right:20px;background:#f0fdf4;border:2px solid #22c55e;color:#166534;padding:12px 18px;border-radius:8px;z-index:9999;font-size:13px;max-width:320px;box-shadow:0 4px 12px rgba(34,197,94,0.25);transition:opacity 0.5s ease-out;">
                <strong>✓ Prețuri actualizate</strong><br>
                <span style="font-size:11px;color:#15803d;margin-top:4px;display:block;">Nivelul tău și prețurile B2B au fost recalculate. Dacă nu vezi discount-urile, reîncarcă pagina.</span>
            </div>
            <script>
            (function() {
                var notice = document.getElementById('webgsm-cache-notice');
                if (notice) {
                    setTimeout(function() {
                        notice.style.opacity = '0';
                        setTimeout(function() {
                            notice.remove();
                        }, 500);
                    }, 5000);
                }
            })();
            </script>
            <?php
        }
        
        ?>
        <script>
        console.group('🔧 WebGSM B2B Pricing v2.0 - DEBUG');
        console.log('📌 User ID:', <?php echo $user_id; ?>);
        console.log('🏢 Is PJ:', <?php echo $is_pj; ?>);
        console.log('⭐ Tier:', '<?php echo $tier; ?>');
        console.log('💰 Total Value:', '<?php echo number_format($total_value, 2); ?> RON');
        console.log('📊 Discount Implicit:', '<?php echo $discount_implicit; ?>%');
        console.log('🏆 Tiers Config:', <?php echo json_encode($tiers); ?>);
        console.groupEnd();
        </script>
        <?php
    }
    
    public function debug_set_pj_button() {
        if (!current_user_can('manage_options')) return;
        if (isset($_GET['set_pj']) && $_GET['set_pj'] === '1') {
            update_user_meta(get_current_user_id(), '_is_pj', 'yes');
            update_user_meta(get_current_user_id(), 'billing_cui', 'RO12345678');
            update_user_meta(get_current_user_id(), '_tip_client', 'pj');
            echo '<div style="position:fixed;bottom:60px;right:20px;z-index:9999;background:#22c55e;color:#fff;padding:10px 18px;border-radius:8px;font-size:15px;box-shadow:0 2px 8px rgba(0,0,0,0.12);">Userul tău a fost setat ca PJ! <a href="'.esc_url(remove_query_arg('set_pj')).'" style="color:#fff;text-decoration:underline;">Reîncarcă</a></div>';
        } else {
            echo '<a href="'.esc_url(add_query_arg('set_pj','1')).'" style="position:fixed;bottom:60px;right:20px;z-index:9999;background:#2563eb;color:#fff;padding:10px 18px;border-radius:8px;font-size:15px;box-shadow:0 2px 8px rgba(0,0,0,0.12);text-decoration:none;">Setează userul ca PJ (test)</a>';
        }
    }
    
    public function debug_show_pj_status() {
        if (!current_user_can('manage_options')) return;
        $is_pj = $this->is_user_pj() ? 'DA (PJ)' : 'NU (PF)';
        $tier = $this->get_user_tier() ?: 'bronze';
        $total_value = number_format($this->get_user_total_value(get_current_user_id()), 0, ',', '.');
        $discount = get_option('webgsm_b2b_discount_implicit', 0);
        echo '<div style="position:fixed;bottom:20px;right:20px;z-index:9999;background:#2563eb;color:#fff;padding:10px 18px;border-radius:8px;font-size:15px;box-shadow:0 2px 8px rgba(0,0,0,0.12);">'
            .'<strong>PJ:</strong> '.$is_pj.' | <strong>Tier:</strong> '.$tier.' | <strong>Total:</strong> '.$total_value.' RON</div>';
    }
    
    // =========================================
    // CACHE MANAGEMENT
    // =========================================
    
    public function clear_all_price_cache() {
        global $wpdb;
        wp_cache_flush();
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_wc_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_transient_timeout_wc_%'");
        
        if (function_exists('WC') && WC()->session) {
            WC()->session->set('cart_totals', null);
        }
        if (function_exists('WC') && WC()->cart) {
            WC()->cart->calculate_totals();
        }
    }
    
    // =========================================
    // VERIFICARE USER PJ
    // =========================================
    
    public function is_user_pj($user_id = null) {
        if (is_null($user_id)) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false;
        }
        
        // Check if user is approved - pending users don't get B2B prices
        $b2b_status = get_user_meta($user_id, '_b2b_status', true);
        if ($b2b_status === 'pending') {
            return false; // Pending users don't get B2B prices
        }
        
        $is_pj = get_user_meta($user_id, '_is_pj', true);
        if ($is_pj === 'yes' || $is_pj === '1' || $is_pj === true) {
            return true;
        }
        
        $user = get_userdata($user_id);
        if ($user && (in_array('b2b_customer', (array) $user->roles) || in_array('wholesale_customer', (array) $user->roles))) {
            return true;
        }
        
        $cui = get_user_meta($user_id, 'billing_cui', true);
        if (!empty($cui)) {
            return true;
        }
        
        $tip_client = get_user_meta($user_id, '_tip_client', true);
        if (strtolower($tip_client) === 'pj' || strtolower($tip_client) === 'juridica') {
            return true;
        }
        
        return false;
    }
    
    /**
     * Show pending approval message on My Account dashboard
     */
    public function show_pending_approval_message() {
        if (!is_user_logged_in()) return;
        
        $user_id = get_current_user_id();
        $b2b_status = get_user_meta($user_id, '_b2b_status', true);
        
        if ($b2b_status === 'pending') {
            $file_upload = new WebGSM_B2B_File_Upload();
            $cert_path = get_user_meta($user_id, '_b2b_certificate_path', true);
            $has_cert = !empty($cert_path) && file_exists($cert_path);
            $company = get_user_meta($user_id, 'billing_company', true) ?: 'Firma';
            ?>
            <div style="background:linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);border:1px solid #f59e0b;border-radius:8px;padding:16px;margin:16px 0;box-shadow:0 2px 4px rgba(0,0,0,0.04);">
                <h3 style="color:#92400e;margin:0 0 10px;text-align:center;display:flex;align-items:center;justify-content:center;gap:6px;font-size:16px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#92400e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    Cont în preaprobare
                </h3>
                <p style="color:#78350f;margin:0 0 12px;font-size:13px;text-align:center;">
                    Contul dumneavoastră B2B așteaptă validare. Veți beneficia de prețurile pentru parteneri după aprobare.
                </p>
                
                <?php if (!$has_cert): ?>
                <div style="background:linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);border:1px solid #ef4444;border-radius:6px;padding:12px;margin:12px 0;box-shadow:0 1px 3px rgba(239,68,68,0.08);">
                    <p style="color:#dc2626;margin:0 0 8px;font-size:13px;font-weight:600;text-align:center;display:flex;align-items:center;justify-content:center;gap:6px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                            <line x1="12" y1="9" x2="12" y2="13"></line>
                            <line x1="12" y1="17" x2="12.01" y2="17"></line>
                        </svg>
                        Certificatul CUI nu a fost încărcat!
                    </p>
                    <p style="color:#991b1b;margin:0 0 12px;font-size:12px;text-align:center;">
                        Pentru aprobare rapidă, vă rugăm să trimiteți certificatul de înregistrare CUI prin una din opțiunile de mai jos:
                    </p>
                    
                    <div style="display:flex;flex-direction:column;gap:8px;max-width:450px;margin:0 auto;">
                        <!-- Upload din cont -->
                        <div id="cert-upload-section" style="background:#fff;border:1px solid #e5e7eb;border-radius:6px;padding:12px;box-shadow:0 1px 2px rgba(0,0,0,0.04);">
                            <label style="display:block;margin-bottom:6px;font-weight:600;color:#374151;font-size:12px;">1. Încarcă certificatul aici:</label>
                            <input type="file" id="pending_cert_upload" accept=".pdf,.jpg,.jpeg,.png" style="width:100%;padding:8px;border:1px solid #d1d5db;border-radius:4px;margin-bottom:8px;font-size:13px;">
                            <button type="button" id="upload_cert_btn" style="background:#22c55e;color:#fff;border:none;padding:8px 16px;border-radius:4px;cursor:pointer;font-weight:600;width:100%;font-size:13px;display:flex;align-items:center;justify-content:center;gap:6px;transition:background 0.2s;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="17 8 12 3 7 8"></polyline>
                                    <line x1="12" y1="3" x2="12" y2="15"></line>
                                </svg>
                                Încarcă Certificat
                            </button>
                            <div id="cert_upload_status" style="margin-top:8px;font-size:12px;"></div>
                        </div>
                        
                        <!-- Email -->
                        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:6px;padding:12px;text-align:center;box-shadow:0 1px 2px rgba(0,0,0,0.04);">
                            <label style="display:block;margin-bottom:6px;font-weight:600;color:#374151;font-size:12px;">2. Trimite pe email:</label>
                            <?php 
                            $email_subject = 'Certificat CUI - ' . esc_html($company);
                            $email_body = 'Bună ziua,' . "\n\n" . 'Vă trimit certificatul de înregistrare CUI pentru aprobarea contului B2B.' . "\n\n" . 'Mulțumesc!';
                            // Use rawurlencode to convert spaces to %20 instead of +
                            $email_subject_encoded = rawurlencode($email_subject);
                            $email_body_encoded = rawurlencode($email_body);
                            ?>
                            <a href="mailto:info@webgsm.ro?subject=<?php echo $email_subject_encoded; ?>&body=<?php echo $email_body_encoded; ?>" 
                               style="display:inline-flex;align-items:center;justify-content:center;gap:6px;background:#3b82f6;color:#fff;padding:8px 16px;border-radius:4px;text-decoration:none;font-weight:600;font-size:13px;transition:background 0.2s;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                    <polyline points="22,6 12,13 2,6"></polyline>
                                </svg>
                                Trimite pe Email
                            </a>
                            <p style="margin:8px 0 0;font-size:11px;color:#6b7280;">info@webgsm.ro</p>
                        </div>
                        
                        <!-- WhatsApp -->
                        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:6px;padding:12px;text-align:center;box-shadow:0 1px 2px rgba(0,0,0,0.04);">
                            <label style="display:block;margin-bottom:6px;font-weight:600;color:#374151;font-size:12px;">3. Trimite pe WhatsApp:</label>
                            <?php 
                            // WhatsApp number - leave empty for now, will be added later
                            $whatsapp_number = ''; // Will be configured later
                            $whatsapp_text = 'Bună ziua, aș dori să trimit certificatul de înregistrare CUI pentru aprobarea contului B2B - ' . esc_html($company);
                            ?>
                            <?php if (!empty($whatsapp_number)): ?>
                            <a href="https://wa.me/<?php echo esc_attr($whatsapp_number); ?>?text=<?php echo rawurlencode($whatsapp_text); ?>" 
                               target="_blank"
                               style="display:inline-flex;align-items:center;justify-content:center;gap:6px;background:#25D366;color:#fff;padding:8px 16px;border-radius:4px;text-decoration:none;font-weight:600;font-size:13px;transition:background 0.2s;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                                </svg>
                                Trimite pe WhatsApp
                            </a>
                            <?php else: ?>
                            <p style="color:#6b7280;font-size:12px;margin:0;">Disponibil în curând</p>
                            <?php endif; ?>
                            <p style="margin:8px 0 0;font-size:11px;color:#6b7280;">Contact direct</p>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div style="background:linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);border:1px solid #22c55e;border-radius:6px;padding:10px;margin:12px 0;text-align:center;box-shadow:0 1px 3px rgba(34,197,94,0.08);">
                    <p style="color:#15803d;margin:0;font-size:13px;font-weight:600;display:flex;align-items:center;justify-content:center;gap:6px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#15803d" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        Certificatul CUI a fost încărcat cu succes!
                    </p>
                </div>
                <?php endif; ?>
                
                <p style="color:#78350f;margin:12px 0 0;font-size:12px;text-align:center;display:flex;align-items:center;justify-content:center;gap:6px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#78350f" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>
                    <strong>Important:</strong> Verificați și completați adresa de facturare în secțiunea "Adrese" dacă lipsesc detalii.
                </p>
            </div>
            
            <style>
            #upload_cert_btn:hover {
                background: #16a34a !important;
            }
            #cert-upload-section a:hover,
            #cert-upload-section a[href^="mailto"]:hover {
                background: #2563eb !important;
            }
            #cert-upload-section a[href^="https://wa.me"]:hover {
                background: #20ba5a !important;
            }
            </style>
            
            <script>
            jQuery(document).ready(function($) {
                $('#upload_cert_btn').on('click', function() {
                    var fileInput = $('#pending_cert_upload')[0];
                    var $btn = $(this);
                    var $status = $('#cert_upload_status');
                    
                    if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                        $status.html('<span style="color:#ef4444;display:flex;align-items:center;gap:6px;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>Selectează un fișier mai întâi!</span>');
                        return;
                    }
                    
                    var file = fileInput.files[0];
                    var fileSize = file.size / 1024 / 1024;
                    
                    if (fileSize > 5) {
                        $status.html('<span style="color:#ef4444;display:flex;align-items:center;gap:6px;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>Fișierul este prea mare! Max 5MB.</span>');
                        return;
                    }
                    
                    var formData = new FormData();
                    formData.append('action', 'webgsm_upload_pending_cert');
                    formData.append('cert_file', file);
                    formData.append('nonce', '<?php echo wp_create_nonce('webgsm_upload_pending_cert'); ?>');
                    
                    $btn.prop('disabled', true).html('<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg> Se încarcă...');
                    $status.html('<span style="color:#3b82f6;display:flex;align-items:center;gap:6px;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>Se încarcă...</span>');
                    
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                $status.html('<span style="color:#22c55e;display:flex;align-items:center;gap:6px;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>Certificat încărcat cu succes! Pagina se va reîmprospăta...</span>');
                                setTimeout(function() {
                                    location.reload();
                                }, 1500);
                            } else {
                                $status.html('<span style="color:#ef4444;display:flex;align-items:center;gap:6px;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>Eroare: ' + (response.data || 'Nu s-a putut încărca') + '</span>');
                                $btn.prop('disabled', false).html('<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg> Încarcă Certificat');
                            }
                        },
                        error: function() {
                            $status.html('<span style="color:#ef4444;display:flex;align-items:center;gap:6px;"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>Eroare la comunicarea cu serverul.</span>');
                            $btn.prop('disabled', false).html('<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg> Încarcă Certificat');
                        }
                    });
                });
            });
            </script>
            <?php
        }
    }
    
    // =========================================
    // SISTEM TIERS - BAZAT PE SUMĂ (V2.0)
    // =========================================
    
    public function get_user_tier($user_id = null) {
        if (is_null($user_id)) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id || !$this->is_user_pj($user_id)) {
            return false;
        }
        
        $tier = get_user_meta($user_id, '_pj_tier', true);
        
        if (empty($tier)) {
            $tier = $this->calculate_user_tier($user_id);
            update_user_meta($user_id, '_pj_tier', $tier);
            update_user_meta($user_id, '_pj_tier_achieved_date', current_time('mysql'));
        }
        
        return $tier;
    }
    
    public function calculate_user_tier($user_id) {
        $total_value = $this->get_user_total_value($user_id);
        $tiers = get_option('webgsm_b2b_tiers', $this->get_default_tiers());
        
        $current_tier = 'bronze';
        
        $sorted_tiers = array();
        foreach ($tiers as $tier_name => $tier_data) {
            $min_value = isset($tier_data['min_value']) ? (float)$tier_data['min_value'] : 0;
            $sorted_tiers[$tier_name] = $min_value;
        }
        arsort($sorted_tiers);
        
        foreach ($sorted_tiers as $tier_name => $min_value) {
            if ($total_value >= $min_value) {
                $current_tier = $tier_name;
                break;
            }
        }
        
        return $current_tier;
    }
    
    public function get_user_total_value($user_id) {
        $cached = get_user_meta($user_id, '_pj_total_value', true);
        $last_calc = get_user_meta($user_id, '_pj_value_calculated', true);
        
        if ($cached !== '' && $last_calc && (time() - $last_calc) < 3600) {
            return (float) $cached;
        }
        
        $orders = wc_get_orders(array(
            'customer_id' => $user_id,
            'status' => array('completed'),
            'limit' => -1
        ));
        
        $total = 0;
        foreach ($orders as $order) {
            $total += $order->get_total();
        }
        
        update_user_meta($user_id, '_pj_total_value', $total);
        update_user_meta($user_id, '_pj_value_calculated', time());
        
        return $total;
    }
    
    public function get_user_total_orders($user_id) {
        $orders = wc_get_orders(array(
            'customer_id' => $user_id,
            'status' => array('completed'),
            'limit' => -1,
            'return' => 'ids'
        ));
        return count($orders);
    }
    
    public function get_default_tiers() {
        return array(
            'bronze' => array(
                'min_value' => 0,
                'discount_extra' => 0,
                'label' => 'Bronze'
            ),
            'silver' => array(
                'min_value' => 5000,
                'discount_extra' => 3,
                'label' => 'Silver'
            ),
            'gold' => array(
                'min_value' => 25000,
                'discount_extra' => 5,
                'label' => 'Gold'
            ),
            'platinum' => array(
                'min_value' => 100000,
                'discount_extra' => 8,
                'label' => 'Platinum'
            )
        );
    }
    
    public function update_user_tier_on_order($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return;
        
        $user_id = $order->get_customer_id();
        if (!$user_id || !$this->is_user_pj($user_id)) return;
        
        delete_user_meta($user_id, '_pj_value_calculated');
        
        $old_tier = get_user_meta($user_id, '_pj_tier', true);
        $new_tier = $this->calculate_user_tier($user_id);
        
        if ($old_tier && $new_tier !== $old_tier) {
            $tier_order = array('bronze' => 1, 'silver' => 2, 'gold' => 3, 'platinum' => 4);
            $old_level = isset($tier_order[$old_tier]) ? $tier_order[$old_tier] : 0;
            $new_level = isset($tier_order[$new_tier]) ? $tier_order[$new_tier] : 0;
            
            if ($new_level > $old_level) {
                update_user_meta($user_id, '_pj_tier', $new_tier);
                update_user_meta($user_id, '_pj_tier_achieved_date', current_time('mysql'));
                update_user_meta($user_id, '_webgsm_show_tier_upgrade', 'yes');
                update_user_meta($user_id, '_webgsm_new_tier', $new_tier);
                $this->send_tier_upgrade_email($user_id, $old_tier, $new_tier);
                do_action('webgsm_b2b_tier_upgraded', $user_id, $old_tier, $new_tier);
            }
        } else if (empty($old_tier)) {
            update_user_meta($user_id, '_pj_tier', $new_tier);
            update_user_meta($user_id, '_pj_tier_achieved_date', current_time('mysql'));
        }
    }
    
    /**
     * Invalidează cache-ul tier-ului la anulare/refund
     */
    public function invalidate_user_tier_cache($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return;
        
        $user_id = $order->get_customer_id();
        if (!$user_id) return;
        
        // Șterge TOATE cache-urile tier
        delete_user_meta($user_id, '_pj_total_orders');
        delete_user_meta($user_id, '_pj_total_value');
        delete_user_meta($user_id, '_pj_orders_calculated');
        delete_user_meta($user_id, '_pj_value_calculated');
        delete_user_meta($user_id, '_pj_tier');
        
        // Recalculează tier-ul
        $new_tier = $this->calculate_user_tier($user_id);
        update_user_meta($user_id, '_pj_tier', $new_tier);
        
        // Marchează invalidare
        update_user_meta($user_id, '_pj_last_invalidation', time());
        
        // Forțează reîmprospătare prețuri (cache WooCommerce)
        $this->clear_all_price_cache();
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('WebGSM: Cache invalidat pentru user ' . $user_id . ' - Comandă anulată #' . $order_id);
        }
    }
    
    /**
     * Invalidează cache la ștergere comandă
     */
    public function invalidate_tier_on_delete($post_id) {
        // Verifică dacă este comandă WooCommerce
        if (get_post_type($post_id) !== 'shop_order') return;
        
        $order = wc_get_order($post_id);
        if (!$order) return;
        
        $user_id = $order->get_customer_id();
        if (!$user_id) return;
        
        // Șterge cache
        delete_user_meta($user_id, '_pj_total_orders');
        delete_user_meta($user_id, '_pj_total_value');
        delete_user_meta($user_id, '_pj_orders_calculated');
        delete_user_meta($user_id, '_pj_value_calculated');
        delete_user_meta($user_id, '_pj_tier');
        update_user_meta($user_id, '_pj_last_invalidation', time());
        $this->clear_all_price_cache();
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('WebGSM: Cache invalidat pentru user ' . $user_id . ' - Comandă ștearsă #' . $post_id);
        }
    }
    
    /**
     * Invalidează cache la schimbare status (ex: completed → cancelled)
     */
    public function invalidate_tier_on_status_change($order_id, $old_status, $new_status, $order) {
        // Invalidează DOAR dacă se schimbă DIN completed ÎN altceva
        $valid_old = in_array($old_status, array('completed'));
        $valid_new = in_array($new_status, array('completed'));
        
        // Dacă status-ul era valid și acum NU mai e → invalidează
        if ($valid_old && !$valid_new) {
            $user_id = $order->get_customer_id();
            if (!$user_id) return;
            
            delete_user_meta($user_id, '_pj_total_orders');
            delete_user_meta($user_id, '_pj_total_value');
            delete_user_meta($user_id, '_pj_orders_calculated');
            delete_user_meta($user_id, '_pj_value_calculated');
            delete_user_meta($user_id, '_pj_tier');
            
            $new_tier = $this->calculate_user_tier($user_id);
            update_user_meta($user_id, '_pj_tier', $new_tier);
            update_user_meta($user_id, '_pj_last_invalidation', time());
            $this->clear_all_price_cache();
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('WebGSM: Cache invalidat pentru user ' . $user_id . ' - Status schimbat: ' . $old_status . ' → ' . $new_status);
            }
        }
    }
    
    private function send_tier_upgrade_email($user_id, $old_tier, $new_tier) {
        $user = get_userdata($user_id);
        if (!$user) return;
        
        $tiers = get_option('webgsm_b2b_tiers', $this->get_default_tiers());
        $new_tier_data = isset($tiers[$new_tier]) ? $tiers[$new_tier] : array();
        $discount = isset($new_tier_data['discount_extra']) ? $new_tier_data['discount_extra'] : 0;
        $label = isset($new_tier_data['label']) ? $new_tier_data['label'] : ucfirst($new_tier);
        
        $to = $user->user_email;
        $subject = 'Felicitări! Ai fost promovat la nivel ' . $label . ' - WebGSM';
        
        $badge_style = '';
        switch ($new_tier) {
            case 'silver':
                $badge_style = 'background: linear-gradient(135deg, #e8e8e8, #c0c0c0); color: #3d3d3d;';
                break;
            case 'gold':
                $badge_style = 'background: linear-gradient(135deg, #f7e199, #d4af37); color: #5c4813;';
                break;
            case 'platinum':
                $badge_style = 'background: linear-gradient(135deg, #2c3e50, #1a252f); color: #e5e5e5;';
                break;
            default:
                $badge_style = 'background: linear-gradient(135deg, #d4a574, #a67c52); color: #4a3728;';
        }
        
        $message = '
        <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <div style="text-align: center; margin-bottom: 30px;">
                <h1 style="color: #1f2937; margin: 0;">🎉 Felicitări!</h1>
            </div>
            <p style="color: #374151; font-size: 16px; line-height: 1.6;">
                Dragă ' . esc_html($user->display_name) . ',
            </p>
            <p style="color: #374151; font-size: 16px; line-height: 1.6;">
                Mulțumim pentru încrederea acordată! Ai fost promovat la nivelul:
            </p>
            <div style="text-align: center; margin: 30px 0;">
                <span style="display: inline-block; padding: 10px 25px; border-radius: 25px; font-size: 18px; font-weight: bold; text-transform: uppercase; ' . $badge_style . '">' . esc_html($label) . '</span>
            </div>
            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 20px; margin: 20px 0;">
                <h3 style="color: #166534; margin: 0 0 15px 0;">✅ Beneficiile tale noi:</h3>
                <ul style="color: #166534; margin: 0; padding-left: 20px;">
                    <li style="margin-bottom: 8px;">Discount suplimentar de <strong>' . $discount . '%</strong></li>
                    <li style="margin-bottom: 8px;">Prețuri B2B exclusive</li>
                </ul>
            </div>
            <div style="text-align: center; margin-top: 30px;">
                <a href="' . esc_url(wc_get_account_endpoint_url('dashboard')) . '" style="display: inline-block; background: #3b82f6; color: #fff; padding: 14px 32px; text-decoration: none; border-radius: 8px; font-weight: 600;">Vezi contul tău</a>
            </div>
        </div>';
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($to, $subject, $message, $headers);
    }
    
    // =========================================
    // CALCUL PREȚ B2B
    // =========================================
    
    public function apply_b2b_price($price, $product) {
        if (is_admin() && !wp_doing_ajax()) {
            return $price;
        }
        
        if (!$this->is_user_pj()) {
            return $price;
        }
        
        return $this->calculate_b2b_price($price, $product);
    }
    
    public function calculate_b2b_price($price, $product) {
        $product_id = $product->get_id();
        
        // ========================================
        // PROTECȚIE 1: Ia prețul ORIGINAL din meta (NICIODATĂ din $price!)
        // ========================================
        $original_price = get_post_meta($product_id, '_regular_price', true);
        
        // Fallback pentru variații
        if (empty($original_price) && $product->is_type('variation')) {
            $original_price = get_post_meta($product_id, '_regular_price', true);
            if (empty($original_price)) {
                $parent_id = $product->get_parent_id();
                $original_price = get_post_meta($parent_id, '_regular_price', true);
            }
        }
        
        // SECURITY: Dacă nu avem preț original valid, NU aplica discount!
        if (empty($original_price) || $original_price <= 0) {
            // Altfel, dacă $price e deja redus de alt plugin, pierdem bani
            error_log('[WebGSM B2B] EROARE: Preț original lipsă pentru produs #' . $product_id . ' - discount NU aplicat');
            return $price; // Returnează prețul așa cum e, fără discount
        }
        
        $original_price = (float) $original_price;
        
        // ========================================
        // PROTECȚIE 2: HARD LIMIT - preț minim
        // ========================================
        $pret_minim = $this->get_pret_minim($product);
        
        // ========================================
        // PROTECȚIE 3: Verifică promoție (sale price)
        // ========================================
        $sale_price = get_post_meta($product_id, '_sale_price', true);
        $is_on_sale = !empty($sale_price) && (float)$sale_price < $original_price;
        
        // ========================================
        // CALCUL DISCOUNT
        // ========================================
        $discount_pj = $this->get_discount_pj($product);
        $tier = $this->get_user_tier();
        $tiers = get_option('webgsm_b2b_tiers', $this->get_default_tiers());
        if (empty($tier) || !isset($tiers[$tier])) {
            $tier = 'bronze';
        }
        $discount_tier = isset($tiers[$tier]['discount_extra']) ? (float) $tiers[$tier]['discount_extra'] : 0;
        
        $discount_total = $discount_pj + $discount_tier;
        
        // Aplică discount pe ORIGINAL, nu pe $price
        $pret_pj = $original_price - ($original_price * $discount_total / 100);
        
        // ========================================
        // REGULA CONFLICT: cel mai mic preț câștigă
        // ========================================
        if ($is_on_sale) {
            $pret_final = min($pret_pj, (float) $sale_price);
        } else {
            $pret_final = $pret_pj;
        }
        
        // ========================================
        // HARD LIMIT FINAL – NICIODATĂ sub preț achiziție + marjă (indiferent de discount/tier)
        // get_pret_minim() returnează întotdeauna >= cost + marjă%; aici forțăm respectarea.
        // ========================================
        if ($pret_minim > 0 && $pret_final < $pret_minim) {
            $pret_final = $pret_minim;
            // Log doar în debug ca să nu umple log-ul pe live
            if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                error_log("[WebGSM B2B] Preț corectat la minim pentru produs #{$product_id}: {$pret_final}");
            }
        }
        
        return round($pret_final, 2);
    }
    
    /**
     * Preț minim de vânzare: NICIODATĂ sub preț achiziție + marjă setată.
     * Dacă există _pret_minim_vanzare (hard limit per produs), se folosește doar dacă e >= cost+marjă.
     */
    public function get_pret_minim($product) {
        $product_id = $product->get_id();
        $pret_achizitie = get_post_meta($product_id, '_pret_achizitie', true);
        $marja_minima = (float) get_option('webgsm_b2b_marja_minima', 5);

        // Floor obligatoriu: preț achiziție + marjă (%) – indiferent de discount/tier
        $floor_cost_marja = 0;
        if (!empty($pret_achizitie) && (float) $pret_achizitie > 0) {
            $floor_cost_marja = (float) $pret_achizitie * (1 + $marja_minima / 100);
        }

        $pret_minim_setat = get_post_meta($product_id, '_pret_minim_vanzare', true);
        if (!empty($pret_minim_setat) && (float) $pret_minim_setat > 0) {
            $explicit = (float) $pret_minim_setat;
            // Nu permitem niciodată sub cost+marjă: folosim max(floor, explicit)
            return $floor_cost_marja > 0 ? max($floor_cost_marja, $explicit) : $explicit;
        }

        return $floor_cost_marja;
    }
    
    public function get_discount_pj($product, $return_source = false) {
        $product_id = $product->get_id();
        
        // Prioritate 1: discount pe produs – folosim doar dacă e setat și > 0 (0/gol = trecem la categorie/implicit)
        $discount_produs = get_post_meta($product_id, '_discount_pj', true);
        if ($discount_produs !== '' && $discount_produs !== false && $discount_produs !== null) {
            $val = (float) $discount_produs;
            if ($val > 0) {
                if ($return_source) return array('discount' => $val, 'source' => 'produs');
                return $val;
            }
        }
        
        // Prioritate 2: discount pe categorie – cel mai mare din categorii
        $categories = $product->get_category_ids();
        $discount_categorie = 0;
        $cat_name = '';
        foreach ($categories as $cat_id) {
            $cat_discount = get_term_meta($cat_id, '_discount_pj_categorie', true);
            if ($cat_discount !== '' && $cat_discount !== false && $cat_discount !== null) {
                $val = (float) $cat_discount;
                if ($val > $discount_categorie) {
                    $discount_categorie = $val;
                    $term = get_term($cat_id);
                    $cat_name = $term ? $term->name : '';
                }
            }
        }
        if ($discount_categorie > 0) {
            if ($return_source) return array('discount' => $discount_categorie, 'source' => 'categorie: ' . $cat_name);
            return $discount_categorie;
        }
        
        // Prioritate 3: discount implicit PJ (global) – pe live dacă opțiunea lipsește/gol, folosim 5%
        $raw_implicit = get_option('webgsm_b2b_discount_implicit', 5);
        if ($raw_implicit === '' || $raw_implicit === false || $raw_implicit === null) {
            $discount_implicit = 5.0;
        } else {
            $discount_implicit = (float) $raw_implicit;
        }
        if ($return_source) return array('discount' => $discount_implicit, 'source' => 'implicit');
        return $discount_implicit;
    }
    
    // =========================================
    // ADMIN MENU & SETTINGS
    // =========================================
    
    public function add_admin_menu() {
        add_menu_page('B2B Pricing', 'B2B Pricing', 'manage_options', 'webgsm-b2b-pricing', array($this, 'render_admin_page'), 'dashicons-chart-line', 56);
        add_submenu_page('webgsm-b2b-pricing', 'Setări', 'Setări', 'manage_options', 'webgsm-b2b-pricing', array($this, 'render_admin_page'));
        add_submenu_page('webgsm-b2b-pricing', 'Conturi Pending', 'Conturi Pending', 'manage_options', 'webgsm-b2b-pending', array($this, 'render_pending_page'));
        add_submenu_page('webgsm-b2b-pricing', 'Clienți B2B', 'Clienți B2B', 'manage_options', 'webgsm-b2b-customers', array($this, 'render_customers_page'));
        add_submenu_page('webgsm-b2b-pricing', 'Rapoarte', 'Rapoarte', 'manage_options', 'webgsm-b2b-reports', array($this, 'render_reports_page'));
    }
    
    public function register_settings() {
        register_setting('webgsm_b2b_settings', 'webgsm_b2b_discount_implicit');
        register_setting('webgsm_b2b_settings', 'webgsm_b2b_marja_minima');
        register_setting('webgsm_b2b_settings', 'webgsm_b2b_tiers');
        register_setting('webgsm_b2b_settings', 'webgsm_b2b_show_badge');
        register_setting('webgsm_b2b_settings', 'webgsm_b2b_badge_text');
        register_setting('webgsm_b2b_settings', 'webgsm_b2b_tier_retention_months');
        
        add_action('update_option_webgsm_b2b_discount_implicit', array($this, 'clear_all_price_cache'));
        add_action('update_option_webgsm_b2b_tiers', array($this, 'clear_all_price_cache'));
    }
    
    public function admin_assets($hook) {
        if (strpos($hook, 'webgsm-b2b') !== false) {
            wp_enqueue_style('webgsm-b2b-admin', WEBGSM_B2B_URL . 'assets/admin.css', array(), WEBGSM_B2B_VERSION);
            wp_enqueue_script('webgsm-b2b-admin', WEBGSM_B2B_URL . 'assets/admin.js', array('jquery'), WEBGSM_B2B_VERSION, true);
        }
    }
    
    public function render_admin_page() {
        include WEBGSM_B2B_PATH . 'admin/settings-page.php';
    }
    
    public function render_pending_page() {
        include WEBGSM_B2B_PATH . 'admin/pending-accounts.php';
    }
    
    public function render_customers_page() {
        include WEBGSM_B2B_PATH . 'admin/customers-page.php';
    }
    
    public function render_reports_page() {
        include WEBGSM_B2B_PATH . 'admin/reports-page.php';
    }
    
    // =========================================
    // PRODUCT META FIELDS
    // =========================================
    
    public function add_product_pricing_fields() {
        global $post;
        $post_id = $post ? $post->ID : 0;
        $pret_ron = $post_id ? get_post_meta($post_id, '_pret_achizitie', true) : '';

        echo '<div class="options_group webgsm-b2b-fields">';
        echo '<h4 style="padding-left: 12px; margin-top: 15px; color: #2563eb; border-top: 1px solid #e5e7eb; padding-top: 15px;"><span class="dashicons dashicons-building" style="margin-right: 5px;"></span>Prețuri B2B</h4>';

        // Preț achiziție – editabil, sincronizat cu Inventory → Date Gestiune (același _pret_achizitie)
        woocommerce_wp_text_input(array(
            'id'                => '_pret_achizitie',
            'value'             => $pret_ron,
            'label'             => 'Preț achiziție',
            'desc_tip'          => true,
            'description'       => 'Cost achiziție (preț minim, profit). Același câmp ca în Inventory → Date Gestiune.',
            'type'              => 'number',
            'custom_attributes' => array('step' => '0.01', 'min' => '0'),
        ));

        woocommerce_wp_text_input(array('id' => '_pret_minim_vanzare', 'label' => 'Preț minim vânzare', 'desc_tip' => true, 'description' => 'HARD LIMIT: Niciun discount nu va coborî prețul sub această valoare.', 'type' => 'number', 'custom_attributes' => array('step' => '0.01', 'min' => '0')));
        woocommerce_wp_text_input(array('id' => '_discount_pj', 'label' => 'Discount PJ (%)', 'desc_tip' => true, 'description' => '🎯 PRIORITATE 1: Discount specific pentru ACEST produs. Lasă gol pentru a moșteni din categorie (prioritate 2) sau din setări globale (prioritate 3). Discount-ul tier se ADAUGĂ peste acesta.', 'type' => 'number', 'custom_attributes' => array('step' => '0.1', 'min' => '0', 'max' => '100'), 'placeholder' => 'Din categorie'));

        echo '</div>';
    }
    
    public function save_product_pricing_fields($post_id) {
        if (isset($_POST['_pret_achizitie'])) {
            $val = sanitize_text_field(wp_unslash($_POST['_pret_achizitie']));
            update_post_meta($post_id, '_pret_achizitie', $val !== '' ? wc_format_decimal($val) : '');
        }
        if (isset($_POST['_pret_minim_vanzare'])) update_post_meta($post_id, '_pret_minim_vanzare', sanitize_text_field($_POST['_pret_minim_vanzare']));
        if (isset($_POST['_discount_pj'])) {
            $discount = sanitize_text_field($_POST['_discount_pj']);
            if ($discount !== '' && ($discount < 0 || $discount > 100)) $discount = max(0, min(100, $discount));
            update_post_meta($post_id, '_discount_pj', $discount);
        }
        $this->clear_all_price_cache();
    }

    /** Sincronizează cele două câmpuri Preț achiziție (Inventory + B2B) la schimbare, ca la submit să aibă aceeași valoare. */
    public function sync_pret_achizitie_fields_script() {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->id !== 'product') return;
        ?>
        <script type="text/javascript">
        jQuery(function($) {
            $(document.body).on('input change', 'input[name="_pret_achizitie"]', function() {
                var v = $(this).val();
                $('input[name="_pret_achizitie"]').not(this).val(v);
            });
        });
        </script>
        <?php
    }
    
    // =========================================
    // CATEGORY META FIELDS
    // =========================================
    
    public function add_category_fields() {
        echo '<div class="form-field"><label for="_discount_pj_categorie">Discount PJ (%)</label><input type="number" name="_discount_pj_categorie" step="0.1" min="0" max="100" value=""><p class="description">🎯 PRIORITATE 2: Discount pentru TOATE produsele din această categorie (dacă produsul nu are discount specific). Lasă gol pentru a folosi discountul global din setări.</p></div>';
    }
    
    public function edit_category_fields($term) {
        $discount = get_term_meta($term->term_id, '_discount_pj_categorie', true);
        echo '<tr class="form-field"><th scope="row"><label for="_discount_pj_categorie">Discount PJ (%)</label></th><td><input type="number" name="_discount_pj_categorie" step="0.1" min="0" max="100" value="' . esc_attr($discount) . '"><p class="description">🎯 PRIORITATE 2: Discount pentru TOATE produsele din această categorie (dacă produsul nu are discount specific). Lasă gol pentru a folosi discountul global din setări.</p></td></tr>';
    }
    
    public function save_category_fields($term_id) {
        if (isset($_POST['_discount_pj_categorie'])) {
            update_term_meta($term_id, '_discount_pj_categorie', sanitize_text_field($_POST['_discount_pj_categorie']));
        }
        $this->clear_all_price_cache();
    }
    
    // =========================================
    // DISPLAY PREȚ
    // =========================================
    
    public function modify_price_html($price_html, $product) {
        // În admin, afișează ÎNTOTDEAUNA (nu doar pentru PJ)
        $is_admin = is_admin();
        
        // Frontend: doar pentru PJ
        if (!$is_admin && !$this->is_user_pj()) {
            return $price_html;
        }
        
        // ADMIN: Afișare pe 3 linii în coloana Price
        if ($is_admin) {
            $product_id = $product->get_id();
            $original_price = get_post_meta($product_id, '_regular_price', true);
            $b2b_price = $product->get_price();
            $pret_minim = get_post_meta($product_id, '_pret_minim_vanzare', true);
            
            $output = '<div style="line-height:1.5; font-size:10px;">';
            
            // Linia 1: Preț setat (NEGRU)
            if ($original_price > 0) {
                $output .= '<div style="color:#000; font-weight:500; display:flex; align-items:center; gap:3px; font-size:10px; white-space:nowrap;">';
                $output .= '<span style="color:#000; font-size:7px; line-height:1;">●</span>';
                $output .= '<span style="font-size:10px;">' . wc_price($original_price) . '</span>';
                $output .= '</div>';
            }
            
            // Linia 2: Preț B2B (ALBASTRU)
            if ($b2b_price > 0) {
                if ((float)$b2b_price < (float)$original_price) {
                    $output .= '<div style="color:#2196F3; font-weight:600; display:flex; align-items:center; gap:3px; font-size:10px; white-space:nowrap;">';
                    $output .= '<span style="color:#2196F3; font-size:7px; line-height:1;">●</span>';
                    $output .= '<span style="font-size:10px;">' . wc_price($b2b_price) . '</span>';
                    $output .= '</div>';
                } elseif ((float)$b2b_price == (float)$original_price) {
                    $output .= '<div style="color:#999; font-size:9px; display:flex; align-items:center; gap:3px; white-space:nowrap;">';
                    $output .= '<span style="color:#999; font-size:7px; line-height:1;">●</span>';
                    $output .= 'Fără discount B2B';
                    $output .= '</div>';
                }
            }
            
            // Linia 3: Preț minim (ROȘU)
            if ($pret_minim > 0) {
                $output .= '<div style="color:#f44336; font-size:9px; display:flex; align-items:center; gap:3px; white-space:nowrap;">';
                $output .= '<span style="color:#f44336; font-size:7px; line-height:1;">●</span>';
                $output .= '<span style="font-size:9px;">' . wc_price($pret_minim) . '</span>';
                $output .= '</div>';
            }
            
            $output .= '</div>';
            return $output;
        }
        
        // FRONTEND: Design nou pentru prețuri B2B (toate tier-urile, inclusiv Bronze fără discount extra)
        if (!$this->is_user_pj()) return $price_html;
        
        $product_id = $product->get_id();
        $original_price = (float) get_post_meta($product_id, '_regular_price', true);
        $b2b_price = (float) $product->get_price();
        
        if ($original_price <= 0) {
            return $price_html;
        }
        
        // Obține tier-ul pentru border color; fallback bronze dacă lipsește (ex. pe live)
        $tier = $this->get_user_tier();
        if (empty($tier) || !is_string($tier)) {
            $tier = 'bronze';
        }
        $tier_borders = array(
            'bronze' => '#d4a574',
            'silver' => '#c0c0c0',
            'gold' => '#d4af37',
            'platinum' => '#4a6073'
        );
        $border_color = isset($tier_borders[$tier]) ? $tier_borders[$tier] : '#3b82f6';
        $tier_labels = array(
            'bronze' => 'Bronze',
            'silver' => 'Silver',
            'gold' => 'Gold',
            'platinum' => 'Platinum'
        );
        $tier_label = isset($tier_labels[$tier]) ? $tier_labels[$tier] : ucfirst($tier);
        
        $savings = $original_price - $b2b_price;
        $savings_percent = $original_price > 0 ? round(($savings / $original_price) * 100, 1) : 0;
        
        // Același layout pentru TOATE tier-urile (inclusiv Bronze): RRC tăiat, preț verde, icon B2B, discount albastru
        $output = '<div class="webgsm-b2b-price-display" style="display: flex; flex-direction: column; gap: 4px; margin: 8px 0;">';
        
        // 1. RRC tăiat (gri)
        $output .= '<div style="font-size: 12px; color: #9ca3af; text-decoration: line-through; text-decoration-thickness: 0.5px; font-weight: 400;">';
        $output .= 'RRC: ' . wc_price($original_price);
        $output .= '</div>';
        
        // 2. Preț B2B VERDE + iconița B2B (toate tier-urile, inclusiv Bronze)
        $output .= '<div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">';
        $output .= '<span style="font-size: 20px; font-weight: 700; color: #15803d; line-height: 1.2;">' . wc_price($b2b_price) . '</span>';
        $output .= '<span class="webgsm-b2b-badge tier-' . esc_attr($tier) . '" style="display: inline-flex; align-items: center; justify-content: center; padding: 0 8px; height: 18px; font-size: 9px; font-weight: 600; color: #475569; background: rgba(241, 245, 249, 0.9); border: 1px solid ' . esc_attr($border_color) . '; border-radius: 8px; text-transform: uppercase; letter-spacing: 0.5px; line-height: 18px; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif; opacity: 0.9;">B2B</span>';
        $output .= '</div>';
        
        // 3. Discountul tău [Tier]: economie lei (%) – albastru; la Bronze poate fi 0 lei (0%)
        $output .= '<div style="font-size: 12px; line-height: 1.4;">';
        $output .= '<span style="color: ' . esc_attr($border_color) . ';">Discountul tau ' . esc_html($tier_label) . ' :</span> ';
        $output .= '<span style="font-weight: 600; color: #15803d;">' . wc_price($savings) . '</span> ';
        $output .= '<span style="font-weight: 600; color: #3b82f6;">(' . number_format($savings_percent, 1) . '%)</span>';
        $output .= '</div>';
        
        $output .= '</div>';
        
        return $output;
    }
    
    public function display_cart_item_tier_price($price_html, $cart_item, $cart_item_key) {
        if (!$this->is_user_pj()) return $price_html;
        
        $product = $cart_item['data'];
        $discount_pj = $this->get_discount_pj($product);
        $tier = $this->get_user_tier();
        $tiers = get_option('webgsm_b2b_tiers', $this->get_default_tiers());
        if (empty($tier) || !isset($tiers[$tier])) {
            $tier = 'bronze';
        }
        $discount_tier = isset($tiers[$tier]['discount_extra']) ? (float) $tiers[$tier]['discount_extra'] : 0;
        $discount_total = $discount_pj + $discount_tier;
        
        // Badge B2B pentru toate tier-urile (inclusiv Bronze fără discount)
        $tier_borders = array(
            'bronze' => '#d4a574',
            'silver' => '#c0c0c0',
            'gold' => '#d4af37',
            'platinum' => '#4a6073'
        );
        $border_color = isset($tier_borders[$tier]) ? $tier_borders[$tier] : '#3b82f6';
        $price_html .= ' <span class="webgsm-b2b-badge tier-' . esc_attr($tier) . '" style="display: inline-flex; align-items: center; justify-content: center; padding: 1px 6px; height: 14px; font-size: 8px; font-weight: 600; color: #ffffff; background: #3b82f6; border: 1px solid ' . esc_attr($border_color) . '; border-radius: 6px; text-transform: uppercase; letter-spacing: 0.4px; line-height: 1; vertical-align: middle; margin-left: 2px;">B2B</span>';
        
        return $price_html;
    }
    
    public function display_b2b_savings_row() {
        if (!$this->is_user_pj()) return;
        
        $cart = WC()->cart;
        if (!$cart) return;
        
        $total_discount = 0;
        $total_original = 0;
        
        foreach ($cart->get_cart() as $cart_item) {
            $product = $cart_item['data'];
            $product_id = $product->get_id();
            $quantity = $cart_item['quantity'];
            
            $original_price = get_post_meta($product_id, '_regular_price', true);
            
            $discount_pj = $this->get_discount_pj($product);
            $tier = $this->get_user_tier();
            $tiers = get_option('webgsm_b2b_tiers', $this->get_default_tiers());
            if (empty($tier) || !isset($tiers[$tier])) {
                $tier = 'bronze';
            }
            $discount_tier = isset($tiers[$tier]['discount_extra']) ? (float) $tiers[$tier]['discount_extra'] : 0;
            $total_discount_percent = $discount_pj + $discount_tier;
            
            if ($original_price > 0 && $total_discount_percent > 0) {
                $discount_amount = ((float)$original_price * $total_discount_percent / 100) * $quantity;
                $total_discount += $discount_amount;
                $total_original += (float)$original_price * $quantity;
            }
        }
        
        if ($total_discount > 0) {
            // Obține tier-ul pentru culoare și label
            $tier = $this->get_user_tier();
            $tier_borders = array(
                'bronze' => '#d4a574',
                'silver' => '#c0c0c0',
                'gold' => '#d4af37',
                'platinum' => '#4a6073'
            );
            $tier_color = isset($tier_borders[$tier]) ? $tier_borders[$tier] : '#3b82f6';
            
            $tier_labels = array(
                'bronze' => 'Bronze',
                'silver' => 'Silver',
                'gold' => 'Gold',
                'platinum' => 'Platinum'
            );
            $tier_label = isset($tier_labels[$tier]) ? $tier_labels[$tier] : ucfirst($tier);
            
            // 1. Linie: Total RRC - tăiat, gri, font mic
            echo '<tr class="webgsm-b2b-rrp-total">';
            echo '<th style="color: #9ca3af; font-size: 12px; font-weight: 400; text-decoration: line-through; padding: 6px 12px !important; border: none !important;">Total RRC:</th>';
            echo '<td style="color: #9ca3af; font-size: 12px; text-decoration: line-through; text-align: right; padding: 6px 12px !important; border: none !important;"><span class="woocommerce-Price-amount amount">' . wc_price($total_original) . '</span></td>';
            echo '</tr>';
            
            // 2. Linie: Discountul tau [Tier] - simplu, fără animație
            echo '<tr class="webgsm-b2b-savings-highlight">';
            echo '<th style="color: ' . esc_attr($tier_color) . '; background: #f0fdf4; padding: 10px 12px !important; border: 1px solid #bbf7d0 !important; border-right: none !important; font-size: 13px;">💚 Discountul tau ' . esc_html($tier_label) . ' :</th>';
            echo '<td style="color: #15803d; font-weight: 700; font-size: 16px; background: #f0fdf4; padding: 10px 12px !important; text-align: right; border: 1px solid #bbf7d0 !important; border-left: none !important;"><span class="woocommerce-Price-amount amount">-' . wc_price($total_discount) . '</span></td>';
            echo '</tr>';
        }
    }
    
    // =========================================
    // ADMIN COLUMNS
    // =========================================
    
    public function add_order_profit_column($columns) {
        $new_columns = array();
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'order_total') $new_columns['order_profit'] = 'Profit';
        }
        return $new_columns;
    }
    
    public function render_order_profit_column($column, $post_id) {
        if ($column !== 'order_profit') return;
        
        $order = wc_get_order($post_id);
        if (!$order) { echo '-'; return; }
        
        $profit = 0;
        foreach ($order->get_items() as $item) {
            $pret_achizitie = get_post_meta($item->get_product_id(), '_pret_achizitie', true);
            if ($pret_achizitie) {
                $profit += $item->get_total() - ((float)$pret_achizitie * $item->get_quantity());
            }
        }
        
        echo $profit > 0 ? '<span style="color: #15803d;">' . wc_price($profit) . '</span>' : ($profit < 0 ? '<span style="color: #dc2626;">' . wc_price($profit) . '</span>' : '-');
    }
    
    // =========================================
    // AJAX - UPDATE CANTITATE ÎN CART
    // =========================================
    
    public function ajax_update_cart_quantity() {
        check_ajax_referer('woocommerce-cart', 'security', false);
        
        if (!isset($_POST['cart_item_key']) || !isset($_POST['quantity'])) {
            wp_send_json_error('Invalid data');
            return;
        }
        
        $cart_item_key = sanitize_text_field($_POST['cart_item_key']);
        $quantity = intval($_POST['quantity']);
        
        if ($quantity <= 0) {
            $quantity = 1;
        }
        
        $cart = WC()->cart;
        if ($cart) {
            $cart->set_quantity($cart_item_key, $quantity, true);
            $cart->calculate_totals();
            
            wp_send_json_success(array(
                'message' => 'Cart updated',
                'fragments' => apply_filters('woocommerce_add_to_cart_fragments', array())
            ));
        } else {
            wp_send_json_error('Cart not found');
        }
    }
    
    // =========================================
    // DETECTARE PJ LA ÎNREGISTRARE
    // =========================================
    
    public function detect_pj_on_registration($customer_id) {
        $tip = '';
        foreach (array('tip_facturare', 'tip_client', '_tip_facturare', '_tip_client') as $field) {
            if (!empty($_POST[$field])) { $tip = sanitize_text_field($_POST[$field]); break; }
        }
        
        $cui = '';
        foreach (array('firma_cui', 'billing_cui', '_firma_cui', 'cui', 'cif') as $field) {
            if (!empty($_POST[$field])) { $cui = sanitize_text_field($_POST[$field]); break; }
        }
        if (empty($cui)) $cui = get_user_meta($customer_id, '_firma_cui', true) ?: get_user_meta($customer_id, 'billing_cui', true);
        
        $company = '';
        foreach (array('firma_nume', 'billing_company', '_firma_nume') as $field) {
            if (!empty($_POST[$field])) { $company = sanitize_text_field($_POST[$field]); break; }
        }
        
        $is_pj = ($tip === 'pj' || !empty($cui) || !empty($company));
        
        if ($is_pj) {
            // Handle certificate upload
            if (isset($_FILES['certificat_cui']) && !empty($_FILES['certificat_cui']['name'])) {
                $file_upload = new WebGSM_B2B_File_Upload();
                $result = $file_upload->handle_certificate_upload($_FILES['certificat_cui'], $customer_id);
                
                if (is_wp_error($result)) {
                    error_log('[WebGSM B2B] Upload certificat eșuat pentru user ' . $customer_id . ': ' . $result->get_error_message());
                } else {
                    // Save path to user meta
                    update_user_meta($customer_id, '_b2b_certificate_path', $result);
                }
            }
            
            // Set user to pending approval
            $approval_system = WebGSM_B2B_Approval_System::instance();
            $approval_system->set_pending_on_registration($customer_id);
            
            // Save basic info
            update_user_meta($customer_id, '_tip_client', 'pj');
            if (!empty($cui)) update_user_meta($customer_id, 'billing_cui', $cui);
            if (!empty($company)) update_user_meta($customer_id, 'billing_company', $company);
            
            // Get address fields from POST
            $adresa = isset($_POST['firma_adresa']) ? sanitize_text_field($_POST['firma_adresa']) : '';
            $judet = isset($_POST['firma_judet']) ? sanitize_text_field($_POST['firma_judet']) : '';
            $oras = isset($_POST['firma_oras']) ? sanitize_text_field($_POST['firma_oras']) : '';
            
            // Save to addresses array for easy access
            if (!empty($company)) {
                $addresses = get_user_meta($customer_id, 'webgsm_addresses', true);
                if (!is_array($addresses)) $addresses = [];
                
                $addresses[] = [
                    'label' => 'Adresa firmă (din ANAF)',
                    'name' => $company,
                    'phone' => get_user_meta($customer_id, 'billing_phone', true) ?: '',
                    'address' => !empty($adresa) ? $adresa : '',
                    'city' => !empty($oras) ? $oras : '',
                    'county' => !empty($judet) ? $judet : '',
                    'postcode' => ''
                ];
                
                update_user_meta($customer_id, 'webgsm_addresses', $addresses);
            }
        } else {
            update_user_meta($customer_id, '_tip_client', 'pf');
        }
        
        if (empty(get_user_meta($customer_id, 'billing_country', true))) {
            update_user_meta($customer_id, 'billing_country', 'RO');
            update_user_meta($customer_id, 'shipping_country', 'RO');
        }
    }
    
    /**
     * AJAX Debug pentru tier-uri (TEMPORAR)
     */
    public function ajax_debug_tier() {
        // Only for logged-in users
        if (!is_user_logged_in()) {
            wp_send_json_error('Utilizator neautentificat.');
            return;
        }
        
        check_ajax_referer('webgsm_debug', 'nonce', false);
        
        $user_id = get_current_user_id();
        
        // Comenzi WooCommerce
        $orders = wc_get_orders(array(
            'customer_id' => $user_id,
            'status' => 'any',
            'limit' => -1
        ));
        
        $orders_data = array();
        $total_value = 0;
        $valid_count = 0;
        
        foreach ($orders as $order) {
            $is_valid = in_array($order->get_status(), array('completed'));
            
            $orders_data[] = array(
                'ID' => $order->get_id(),
                'Status' => $order->get_status(),
                'Total' => number_format($order->get_total(), 2) . ' RON',
                'Date' => $order->get_date_created()->format('Y-m-d H:i:s'),
                'Valid' => $is_valid ? 'YES' : 'NO'
            );
            
            if ($is_valid) {
                $total_value += $order->get_total();
                $valid_count++;
            }
        }
        
        wp_send_json_success(array(
            'user_id' => $user_id,
            'is_pj' => $this->is_user_pj($user_id) ? 'YES' : 'NO',
            'cached_orders' => get_user_meta($user_id, '_pj_total_orders', true),
            'cached_value' => get_user_meta($user_id, '_pj_total_value', true),
            'cached_tier' => get_user_meta($user_id, '_pj_tier', true),
            'wc_orders_total' => count($orders),
            'wc_orders_valid' => $valid_count,
            'wc_total_value' => number_format($total_value, 2) . ' RON',
            'orders_detail' => $orders_data
        ));
    }
}

// Inițializare
function webgsm_b2b_pricing() {
    return WebGSM_B2B_Pricing::instance();
}
add_action('plugins_loaded', 'webgsm_b2b_pricing');

// Activare
register_activation_hook(__FILE__, function() {
    if (!get_option('webgsm_b2b_discount_implicit')) update_option('webgsm_b2b_discount_implicit', 5);
    if (!get_option('webgsm_b2b_marja_minima')) update_option('webgsm_b2b_marja_minima', 5);
    if (!get_option('webgsm_b2b_tiers')) {
        update_option('webgsm_b2b_tiers', array(
            'bronze' => array('min_value' => 0, 'discount_extra' => 0, 'label' => 'Bronze'),
            'silver' => array('min_value' => 5000, 'discount_extra' => 3, 'label' => 'Silver'),
            'gold' => array('min_value' => 25000, 'discount_extra' => 5, 'label' => 'Gold'),
            'platinum' => array('min_value' => 100000, 'discount_extra' => 8, 'label' => 'Platinum')
        ));
    }
    if (!get_option('webgsm_b2b_tier_retention_months')) update_option('webgsm_b2b_tier_retention_months', 3);
    flush_rewrite_rules();
});
