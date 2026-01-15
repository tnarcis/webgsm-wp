<?php
/**
 * WEBGSM DESIGN SYSTEM - VERSIUNE MINIMALĂ
 * Modifică DOAR: logo mobil, search mobil, badge stoc
 * NU modifică: butoane, badge discount, hover effects
 */

if (!defined('ABSPATH')) {
    exit;
}

// CSS minimal
add_action('wp_head', 'webgsm_minimal_css', 999);
function webgsm_minimal_css() {
?>
<style id="webgsm-minimal">
/* ============================================
   BUTOANE ALBASTRE (nu negre)
   ============================================ */
.woocommerce .button,
.woocommerce a.button,
.woocommerce button.button,
.woocommerce input.button {
    background-color: #3b82f6 !important;
    color: #fff !important;
}

.woocommerce .button:hover,
.woocommerce a.button:hover,
.woocommerce button.button:hover {
    background-color: #2563eb !important;
}

/* ============================================
   ASCUNDE BUTOANELE "Vezi Cos" DOAR din POPUP
   ============================================ */
/* Ascunde DOAR butoanele din popup-ul "Produs adăugat" */
.message-box .btn-button,
.message-box a.btn-button,
.message-box .button.wc-forward,
.message-box a.button[href*="cart"],
.mf-product-notification .btn-button,
.mf-product-notification .button.wc-forward {
    display: none !important;
}

/* NU ascunde butoanele din mini-cart (hover pe icon coș) */

/* Adaugă în coș - jumătate + rotunjit */
.woocommerce ul.products li.product .button,
.woocommerce ul.products li.product .add_to_cart_button,
.woocommerce ul.products li.product a.button,
.woocommerce ul.products li.product .product-inner .button,
.woocommerce ul.products li.product .product-inner .add_to_cart_button,
ul.products li.product .add_to_cart_button,
ul.products .product-inner .add_to_cart_button,
.products-list .product .add_to_cart_button,
.product-list .add_to_cart_button,
.mf-shop-content .add_to_cart_button,
li.product .add_to_cart_button,
.product .add_to_cart_button,
a.add_to_cart_button,
.add_to_cart_button {
    padding: 5px 12px !important;
    font-size: 11px !important;
    border-radius: 20px !important;
    min-height: auto !important;
    line-height: 1.4 !important;
    height: auto !important;
}

/* Buton "Adaugă în coș" pe PAGINA PRODUSULUI - vezi secțiunea Martfury */

/* ============================================
   PAGINA PRODUSULUI - Martfury specific
   ============================================ */
/* Container principal */
.entry-summary form.cart,
.product-summary form.cart,
.summary form.cart {
    display: flex !important;
    flex-direction: row !important;
    align-items: center !important;
    gap: 15px !important;
    flex-wrap: wrap !important;
}

/* Quantity wrapper - Martfury style - MINIMALIST */
.entry-summary .quantity,
.product-summary .quantity,
.summary .quantity,
.mf-product-content .quantity,
.woocommerce .quantity {
    display: inline-flex !important;
    align-items: center !important;
    vertical-align: middle !important;
    height: 44px !important;
    margin: 0 !important;
    padding: 0 !important;
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
    border-radius: 0 !important;
}

/* Elimină orice border/background pe wrapper */
.entry-summary .quantity *,
.product-summary .quantity * {
    border: none !important;
    box-shadow: none !important;
}

/* Butoanele +/- - minimalist */
.entry-summary .quantity .increase,
.entry-summary .quantity .decrease,
.entry-summary .quantity .plus,
.entry-summary .quantity .minus,
.product-summary .quantity .increase,
.product-summary .quantity .decrease,
.quantity .plus,
.quantity .minus {
    width: 30px !important;
    height: 44px !important;
    font-size: 18px !important;
    line-height: 44px !important;
    padding: 0 !important;
    margin: 0 !important;
    background: transparent !important;
    border: none !important;
    color: #333 !important;
    cursor: pointer !important;
}

.quantity .plus:hover,
.quantity .minus:hover,
.quantity .increase:hover,
.quantity .decrease:hover {
    color: #3b82f6 !important;
}

/* Input cantitate - minimalist */
.entry-summary .quantity input.qty,
.entry-summary .quantity .input-text.qty,
.product-summary .quantity input.qty,
.quantity input.qty,
input.qty {
    height: 44px !important;
    width: 50px !important;
    font-size: 16px !important;
    font-weight: 600 !important;
    text-align: center !important;
    background: transparent !important;
    border: none !important;
    border-bottom: 2px solid #ddd !important;
    padding: 0 !important;
    margin: 0 5px !important;
    outline: none !important;
    -moz-appearance: textfield !important;
}

.quantity input.qty:focus {
    border-bottom-color: #3b82f6 !important;
}

/* Ascunde săgețile din input number */
.quantity input.qty::-webkit-outer-spin-button,
.quantity input.qty::-webkit-inner-spin-button {
    -webkit-appearance: none !important;
    margin: 0 !important;
}

/* Buton "Adaugă în coș" - aceeași înălțime */
.entry-summary .single_add_to_cart_button,
.product-summary .single_add_to_cart_button,
.summary .single_add_to_cart_button {
    height: 44px !important;
    min-height: 44px !important;
    padding: 0 25px !important;
    font-size: 13px !important;
    border-radius: 25px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    vertical-align: middle !important;
    margin: 0 !important;
}

/* Wishlist - aliniat */
.entry-summary .yith-wcwl-add-to-wishlist,
.product-summary .yith-wcwl-add-to-wishlist,
.woocommerce div.product .yith-wcwl-add-to-wishlist {
    display: inline-flex !important;
    align-items: center !important;
    height: 44px !important;
    vertical-align: middle !important;
    margin: 0 !important;
}

.entry-summary .yith-wcwl-add-to-wishlist a,
.product-summary .yith-wcwl-add-to-wishlist a {
    display: inline-flex !important;
    align-items: center !important;
    height: 44px !important;
}

/* ASCUNDE COMPARE */
.entry-summary .compare-button,
.product-summary .compare-button,
.woocommerce div.product .compare-button,
.single-product .compare-button,
.compare-button {
    display: none !important;
}

/* ============================================
   CULORI UNIFORME LINK-URI
   ============================================ */
/* Breadcrumb - calea de sus */
.woocommerce-breadcrumb,
.woocommerce-breadcrumb a,
.breadcrumb a,
.breadcrumbs a,
nav.woocommerce-breadcrumb a {
    color: #6b7280 !important;
}

.woocommerce-breadcrumb a:hover,
.breadcrumb a:hover,
.breadcrumbs a:hover,
nav.woocommerce-breadcrumb a:hover {
    color: #3b82f6 !important;
}

/* Brand link */
.entry-summary .posted_in a,
.entry-summary .brand a,
.product_meta .brand a,
.product-brand a,
.entry-summary a[href*="product-brand"] {
    color: #3b82f6 !important;
}

.entry-summary .posted_in a:hover,
.entry-summary .brand a:hover,
.product_meta .brand a:hover,
.product-brand a:hover {
    color: #2563eb !important;
}

/* Categories link - jos */
.entry-summary .tagged_as a,
.entry-summary .posted_in a,
.product_meta a,
.product_meta .posted_in a,
.woocommerce-product-details__short-description a,
.entry-summary .sku_wrapper,
.entry-summary .product_meta a {
    color: #3b82f6 !important;
}

.entry-summary .tagged_as a:hover,
.entry-summary .posted_in a:hover,
.product_meta a:hover {
    color: #2563eb !important;
}

/* SKU text */
.entry-summary .sku_wrapper .sku,
.product_meta .sku {
    color: #374151 !important;
}

/* ============================================
   SEARCH DESKTOP - stil mobilesentrix
   ============================================ */
/* Container search rotunjit */
.header-search .products-search,
.products-search {
    border-radius: 25px !important;
    overflow: hidden !important;
    position: relative !important;
}

/* Input rotunjit */
.header-search .products-search input[type="text"],
.header-search .products-search .search-field,
.products-search input[type="text"],
.products-search .search-field {
    border-radius: 25px !important;
    padding-right: 50px !important;
    border: 1px solid #ddd !important;
}

/* Buton search - cerc integrat în dreapta */
.header-search .products-search .search-submit,
.products-search .search-submit,
.header-search .products-search button[type="submit"],
.products-search button[type="submit"] {
    position: absolute !important;
    right: 5px !important;
    top: 50% !important;
    transform: translateY(-50%) !important;
    width: 36px !important;
    height: 36px !important;
    min-width: 36px !important;
    max-width: 36px !important;
    border-radius: 50% !important;
    padding: 0 !important;
    background-color: #3b82f6 !important;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='8'%3E%3C/circle%3E%3Cline x1='21' y1='21' x2='16.65' y2='16.65'%3E%3C/line%3E%3C/svg%3E") !important;
    background-repeat: no-repeat !important;
    background-position: center center !important;
    background-size: 16px 16px !important;
    border: none !important;
    font-size: 0 !important;
    line-height: 0 !important;
    text-indent: -9999px !important;
    overflow: hidden !important;
}

/* Ascunde TOT din interior */
.header-search .products-search .search-submit *,
.products-search .search-submit *,
.header-search .products-search .search-submit span,
.products-search .search-submit span,
.header-search .products-search .search-submit i,
.products-search .search-submit i {
    display: none !important;
    visibility: hidden !important;
    opacity: 0 !important;
    width: 0 !important;
    height: 0 !important;
    font-size: 0 !important;
}

/* ============================================
   DOAR LOGO PE MOBIL
   ============================================ */
@media (max-width: 992px) {
    .site-header .logo img,
    .header-logo img,
    .site-branding img,
    .mobile-logo img,
    .header-mobile .logo img {
        max-width: 160px !important;
        max-height: 50px !important;
    }
}

@media (max-width: 480px) {
    .site-header .logo img,
    .header-logo img,
    .site-branding img,
    .mobile-logo img,
    .header-mobile .logo img {
        max-width: 160px !important;
        max-height: 45px !important;
    }
}

/* ============================================
   DOAR SEARCH PE MOBIL - Martfury specific
   ============================================ */
@media (max-width: 992px) {
    /* Form search rotunjit */
    .header-mobile .products-search,
    .mobile-header-v2 .products-search {
        border-radius: 25px !important;
        overflow: hidden !important;
    }
    
    /* Input rotunjit */
    .header-mobile .products-search .search-field,
    .mobile-header-v2 .products-search .search-field {
        border-radius: 25px !important;
        padding-right: 50px !important;
    }
    
    /* Buton search - cerc albastru */
    .header-mobile .products-search .search-submit,
    .mobile-header-v2 .products-search .search-submit {
        position: absolute !important;
        right: 5px !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        width: 36px !important;
        height: 36px !important;
        min-width: 36px !important;
        border-radius: 50% !important;
        background: #3b82f6 !important;
        border: none !important;
        padding: 0 !important;
        font-size: 0 !important;
    }
    
    /* Ascunde text "Cauta" */
    .header-mobile .products-search .search-submit span,
    .mobile-header-v2 .products-search .search-submit span {
        display: none !important;
    }
    
    /* Lupa vizibilă */
    .header-mobile .products-search .search-submit i,
    .mobile-header-v2 .products-search .search-submit i {
        font-size: 16px !important;
        color: #fff !important;
    }
}

/* ============================================
   DOAR BADGE STOC CUSTOM (nu discount!)
   ============================================ */
@keyframes wgsm-pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.6; transform: scale(1.1); }
}

@keyframes wgsm-blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}

.wgsm-badge-stock,
.wgsm-badge-limited,
.wgsm-badge-outofstock {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 12px;
    font-size: 11px;
    font-weight: 600;
    border-radius: 6px;
    background: #fff;
    border: 1px solid #e5e7eb;
}

.wgsm-badge-stock { color: #1f2937; }
.wgsm-badge-limited { color: #1f2937; }
.wgsm-badge-outofstock { color: #6b7280; }

.wgsm-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.wgsm-badge-stock .wgsm-dot {
    background: #22c55e;
    animation: wgsm-pulse 1.5s ease-in-out infinite;
}

.wgsm-badge-limited .wgsm-dot {
    background: #f59e0b;
    animation: wgsm-blink 1s ease-in-out infinite;
}

.wgsm-badge-outofstock .wgsm-dot {
    background: #ef4444;
}

/* Ascunde stock default WooCommerce */
.woocommerce div.product p.stock.in-stock,
.woocommerce div.product p.stock.out-of-stock {
    display: none !important;
}

/* ============================================
   PAGINA COȘ - STILIZARE
   ============================================ */
/* Toate butoanele din coș - rotunjite albastre */
.woocommerce-cart .woocommerce .button,
.woocommerce-cart .button,
.woocommerce-cart a.button,
.woocommerce-cart input.button,
.woocommerce-cart .checkout-button,
.woocommerce-cart .wc-proceed-to-checkout .button,
.woocommerce-cart .actions .button,
.cart-collaterals .button,
.wc-proceed-to-checkout a.button {
    background-color: #3b82f6 !important;
    color: #fff !important;
    border-radius: 25px !important;
    padding: 10px 25px !important;
    font-size: 13px !important;
    border: none !important;
}

.woocommerce-cart .button:hover,
.woocommerce-cart a.button:hover {
    background-color: #2563eb !important;
}

/* Buton "Continua cumpărăturile" */
.woocommerce-cart .wc-continue-shopping,
.woocommerce-cart a.wc-continue-shopping,
a.wc-continue-shopping,
.continue-shopping,
a.continue-shopping {
    background-color: #3b82f6 !important;
    color: #fff !important;
    border-radius: 25px !important;
    padding: 10px 25px !important;
}

/* Quantity în pagina coș */
.woocommerce-cart .quantity,
.woocommerce-cart-form .quantity,
.cart_item .quantity {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    height: 40px !important;
    background: transparent !important;
    border: none !important;
}

/* Input cantitate în coș */
.woocommerce-cart .quantity input.qty,
.woocommerce-cart-form .quantity input.qty,
.cart_item .quantity input.qty {
    width: 50px !important;
    height: 36px !important;
    text-align: center !important;
    font-size: 14px !important;
    font-weight: 600 !important;
    border: none !important;
    border-bottom: 2px solid #ddd !important;
    background: transparent !important;
    margin: 0 5px !important;
    padding: 0 !important;
    -moz-appearance: textfield !important;
}

.woocommerce-cart .quantity input.qty::-webkit-outer-spin-button,
.woocommerce-cart .quantity input.qty::-webkit-inner-spin-button {
    -webkit-appearance: none !important;
    margin: 0 !important;
}

/* Butoanele +/- în coș */
.woocommerce-cart .quantity .plus,
.woocommerce-cart .quantity .minus,
.woocommerce-cart .quantity .increase,
.woocommerce-cart .quantity .decrease {
    width: 30px !important;
    height: 36px !important;
    font-size: 18px !important;
    line-height: 36px !important;
    background: transparent !important;
    border: none !important;
    color: #333 !important;
    padding: 0 !important;
    margin: 0 !important;
    cursor: pointer !important;
}

/* Update cart button */
.woocommerce-cart button[name="update_cart"],
.woocommerce-cart input[name="update_cart"] {
    background-color: #6b7280 !important;
    color: #fff !important;
    border-radius: 25px !important;
    padding: 10px 20px !important;
}

.woocommerce-cart button[name="update_cart"]:hover {
    background-color: #4b5563 !important;
}

/* Coupon button */
.woocommerce-cart .coupon .button {
    background-color: #3b82f6 !important;
    border-radius: 25px !important;
}

/* Checkout button mare */
.wc-proceed-to-checkout .checkout-button {
    width: 100% !important;
    padding: 15px 30px !important;
    font-size: 15px !important;
    font-weight: 600 !important;
}

/* Buton "Continua cumpărăturile" - negru dar rotunjit */
.woocommerce-cart .wc-continue-shopping,
.woocommerce-cart a.wc-continue-shopping,
a.wc-continue-shopping,
.continue-shopping,
a.continue-shopping,
.woocommerce .return-to-shop a,
a.wc-backward {
    background-color: #1f2937 !important;
    color: #fff !important;
    border-radius: 25px !important;
    padding: 10px 25px !important;
    font-size: 13px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    line-height: 1.4 !important;
    height: auto !important;
    min-height: 44px !important;
}

.woocommerce-cart .wc-continue-shopping:hover,
a.wc-continue-shopping:hover,
a.continue-shopping:hover {
    background-color: #374151 !important;
}

/* Buton "Abonează-te" - negru dar rotunjit */
.mc4wp-form input[type="submit"],
.newsletter-form input[type="submit"],
.widget_mc4wp_form_widget input[type="submit"],
input.mc4wp-submit,
.footer-newsletter input[type="submit"],
.newsletter input[type="submit"] {
    background-color: #1f2937 !important;
    color: #fff !important;
    border-radius: 25px !important;
    padding: 10px 25px !important;
    font-size: 13px !important;
    border: none !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    line-height: 1.4 !important;
    min-height: 44px !important;
    cursor: pointer !important;
}

.mc4wp-form input[type="submit"]:hover,
.newsletter-form input[type="submit"]:hover {
    background-color: #374151 !important;
}

/* Mini-cart (dropdown când treci peste coș) */
.mini-cart .widget_shopping_cart_content .button,
.mini-cart .buttons a,
.woocommerce-mini-cart__buttons a,
.widget_shopping_cart .buttons a,
.cart-panel .button,
.cart-panel a.button {
    border-radius: 25px !important;
    padding: 10px 20px !important;
    font-size: 12px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    line-height: 1.4 !important;
    text-align: center !important;
    min-height: 40px !important;
}

/* Vezi coșul - outline style */
.mini-cart .buttons a.wc-forward:first-child,
.woocommerce-mini-cart__buttons a:first-child,
.widget_shopping_cart .buttons a:first-child {
    background-color: transparent !important;
    color: #1f2937 !important;
    border: 2px solid #1f2937 !important;
}

.mini-cart .buttons a.wc-forward:first-child:hover,
.woocommerce-mini-cart__buttons a:first-child:hover {
    background-color: #1f2937 !important;
    color: #fff !important;
}

/* Finalizare comandă - albastru */
.mini-cart .buttons a.checkout,
.woocommerce-mini-cart__buttons a.checkout,
.widget_shopping_cart .buttons a.checkout {
    background-color: #3b82f6 !important;
    color: #fff !important;
    border: none !important;
}

.mini-cart .buttons a.checkout:hover,
.woocommerce-mini-cart__buttons a.checkout:hover {
    background-color: #2563eb !important;
}

/* FIX ALINIERE TEXT ÎN TOATE BUTOANELE */
.woocommerce .button,
.woocommerce a.button,
.woocommerce button.button,
.woocommerce input.button,
.button,
a.button,
button.button {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    line-height: 1.4 !important;
    vertical-align: middle !important;
}

/* Mobile cart fixes */
@media (max-width: 768px) {
    .woocommerce-cart .quantity {
        width: auto !important;
        min-width: 100px !important;
    }
    
    .woocommerce-cart .quantity input.qty {
        width: 40px !important;
    }
    
    .woocommerce-cart .quantity .plus,
    .woocommerce-cart .quantity .minus {
        width: 25px !important;
    }
}

/* ============================================
   FIX ZOOM PE MOBIL LA INPUT
   ============================================ */
/* Previne zoom când dai click pe input */
@media (max-width: 768px) {
    input[type="text"],
    input[type="email"],
    input[type="tel"],
    input[type="number"],
    input[type="password"],
    input[type="search"],
    select,
    textarea {
        font-size: 16px !important;
    }
    
    /* Checkout inputs */
    .woocommerce-checkout input,
    .woocommerce-checkout select,
    .woocommerce-checkout textarea,
    #billing_first_name,
    #billing_last_name,
    #billing_email,
    #billing_phone,
    #billing_address_1,
    #billing_city,
    #billing_postcode {
        font-size: 16px !important;
        -webkit-text-size-adjust: 100% !important;
    }
}
</style>
<?php
}

// Badge stoc cu punct animat
add_action('woocommerce_single_product_summary', 'webgsm_stock_badge', 15);
function webgsm_stock_badge() {
    global $product;
    
    if ($product->is_in_stock()) {
        $stock_qty = $product->get_stock_quantity();
        
        if ($stock_qty !== null && $stock_qty > 0 && $stock_qty <= 4) {
            echo '<span class="wgsm-badge-limited"><span class="wgsm-dot"></span> Stoc limitat</span>';
        } else {
            echo '<span class="wgsm-badge-stock"><span class="wgsm-dot"></span> În stoc</span>';
        }
    } else {
        echo '<span class="wgsm-badge-outofstock"><span class="wgsm-dot"></span> Stoc epuizat</span>';
    }
}

// Fix zoom pe mobil la checkout
add_action('wp_head', 'webgsm_mobile_zoom_fix');
function webgsm_mobile_zoom_fix() {
    ?>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <?php
}

// JavaScript pentru reset zoom
add_action('wp_footer', 'webgsm_zoom_reset_script');
function webgsm_zoom_reset_script() {
    ?>
    <script>
    (function() {
        // Detectează iOS
        var iOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
        if (!iOS) return;
        
        // La blur (când închizi tastatura), resetează zoom
        document.addEventListener('blur', function(e) {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') {
                // Mic delay pentru a lăsa tastatura să se închidă
                setTimeout(function() {
                    // Resetează viewport zoom
                    var viewport = document.querySelector('meta[name="viewport"]');
                    if (viewport) {
                        viewport.setAttribute('content', 'width=device-width, initial-scale=1, maximum-scale=1');
                    }
                    // Scroll mic pentru a forța redraw
                    window.scrollTo(0, window.scrollY + 1);
                    window.scrollTo(0, window.scrollY - 1);
                }, 100);
            }
        }, true);
    })();
    </script>
    <?php
}
