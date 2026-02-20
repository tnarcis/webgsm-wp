<?php
// Înarcă stilurile temei părinte
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('martfury-parent', get_template_directory_uri() . '/style.css');
});

// PRIORITATE: Încarcă header-account-menu.php ÎNAINTE de tema părinte
require_once get_stylesheet_directory() . '/includes/header-account-menu.php';

// Evită "Undefined array key taxonomy-product_brand" în WC Admin Brands (coloana există doar dacă taxonomia e înregistrată)
add_filter('manage_product_posts_columns', function($columns) {
    if (is_array($columns) && !isset($columns['taxonomy-product_brand'])) {
        $columns['taxonomy-product_brand'] = _x('Brands', 'taxonomy singular name', 'woocommerce');
    }
    return $columns;
}, 5);

// Remove eleganticons preload - loaded via CSS instead
add_action('wp_head', function() {
    echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            var preloadLinks = document.querySelectorAll("link[rel=preload][href*=eleganticons]");
            preloadLinks.forEach(function(link) {
                link.remove();
            });
        });
    </script>';
}, 1);

// Suppress font loading errors in console (non-critical)
add_action('wp_footer', function() {
    ?>
    <script>
    // Suppress 404 errors for font files (non-critical)
    window.addEventListener('error', function(e) {
        if (e.target && e.target.tagName === 'LINK' && e.target.href && e.target.href.includes('.woff2')) {
            e.preventDefault();
            return false;
        }
    }, true);
    </script>
    <?php
}, 1);

// Ascunde butonul mare "Vezi cos" din popup "Adăugat în coș"
add_action('wp_footer', function() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Funcție pentru a elimina butoanele "View Cart" DOAR din popup-ul "Adăugat în coș"
        function hideViewCartButton() {
            // Țintire PRECISĂ: DOAR butoane din .message-box (popup-ul "Produs adăugat")
            $('.message-box .btn-button, .message-box .button.wc-forward, .message-box a.button[href*="cart"]').hide();
            
            // Backup: verifică doar în .message-box
            $('.message-box a, .message-box button').each(function() {
                var $el = $(this);
                var text = $el.text().toLowerCase().trim();
                var href = $el.attr('href') || '';
                
                // Dacă conține "vezi", "view", "cart", "coș" SAU link-ul duce la cart
                if (text.includes('vezi') || text.includes('view') || 
                    text.includes('cart') || text.includes('coș') || 
                    text.includes('cos') || href.includes('cart')) {
                    $el.hide();
                }
            });
            
            // NU ascunde din mini-cart (.woocommerce-mini-cart__buttons)
        }
        
        // Rulează la pornire
        hideViewCartButton();
        
        // Rulează când se adaugă produs în coș
        $(document.body).on('added_to_cart', function() {
            setTimeout(hideViewCartButton, 50);
            setTimeout(hideViewCartButton, 200);
        });
        
        // Observer pentru popup-uri noi
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length) {
                    hideViewCartButton();
                }
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    });
    </script>
    <?php
}, 999);

// ============================================
// LAZY LOAD - My Account files (doar pe pagina My Account)
// ============================================
add_action('wp', function() {
    if (is_account_page()) {
        require_once get_stylesheet_directory() . '/includes/my-account-styling.php';
        require_once get_stylesheet_directory() . '/includes/webgsm-myaccount-headers.php';
        require_once get_stylesheet_directory() . '/includes/webgsm-myaccount-modals.php';
    }
});

// ============================================
// LAZY LOAD - Admin files (doar în admin)
// ============================================
if (is_admin()) {
    require_once get_stylesheet_directory() . '/includes/admin-tools.php';
}

// Previne eroarea ACF "nonce failed verification" la salvare (pagina editare deschisă mult timp)
add_filter('nonce_life', function($seconds) {
    if (is_admin() && !defined('DOING_AJAX')) {
        return 24 * HOUR_IN_SECONDS; // 24h în admin, ca ACF/WooCommerce să nu expire nonce-ul
    }
    return $seconds;
});

// ============================================
// ÎNCARCĂ NORMAL - Fișiere cu hook-uri globale sau multiple contexte
// ============================================
require_once get_stylesheet_directory() . '/includes/retururi.php';
require_once get_stylesheet_directory() . '/includes/garantie.php';
require_once get_stylesheet_directory() . '/includes/awb-tracking.php';
require_once get_stylesheet_directory() . '/includes/facturi.php';
require_once get_stylesheet_directory() . '/includes/notificari.php';
require_once get_stylesheet_directory() . '/includes/n8n-webhooks.php';
require_once get_stylesheet_directory() . '/includes/facturare-pj.php';
require_once get_stylesheet_directory() . '/includes/registration-enhanced.php';
require_once get_stylesheet_directory() . '/includes/webgsm-design-system.php';
require_once get_stylesheet_directory() . '/includes/webgsm-header-primary-menu.php';
require_once get_stylesheet_directory() . '/includes/webgsm-myaccount.php';
require_once get_stylesheet_directory() . '/includes/setup-categories.php';
require_once get_stylesheet_directory() . '/includes/setup-attributes.php';
require_once get_stylesheet_directory() . '/includes/setup-acf-fields.php';
require_once get_stylesheet_directory() . '/includes/product-specs-tab.php';
require_once get_stylesheet_directory() . '/includes/product-inventory-gestiune.php';
require_once get_stylesheet_directory() . '/includes/romanian-strings.php';

// ============================================
// WebGSM B2B Teaser - mesaj simplu, fără preț/discount (performanță)
// ============================================

// A. BANNER SUB PREȚ PE PAGINA PRODUSULUI
add_action('woocommerce_single_product_summary', 'webgsm_b2b_teaser_single_product', 11);

function webgsm_b2b_teaser_single_product() {
    // Nu afișa pentru PJ (deja au prețuri B2B)
    if (is_user_logged_in()) {
        if (class_exists('WebGSM_B2B_Pricing')) {
            $b2b_plugin = WebGSM_B2B_Pricing::instance();
            if ($b2b_plugin->is_user_pj()) return;
        }
    }
    global $product;
    if (!$product) return;

    $is_logged_in = is_user_logged_in();
    if ($is_logged_in) {
        $user_id = get_current_user_id();
        $user = wp_get_current_user();
        $user_email = $user->user_email;
        $user_name = trim($user->first_name . ' ' . $user->last_name);
        if (empty($user_name)) $user_name = $user->display_name;
        $user_phone = get_user_meta($user_id, 'billing_phone', true);
        $email_subject = 'Solicitare cont B2B - ' . ($user_name ?: 'Client WebGSM');
        $email_body = 'Bună ziua,' . "\n\n" . 'Doresc să solicit un cont B2B pentru a beneficia de prețurile pentru parteneri.' . "\n\n";
        if ($user_email || $user_name || $user_phone) {
            $email_body .= 'Date cont existent:' . "\n";
            if ($user_email) $email_body .= '- Email: ' . $user_email . "\n";
            if ($user_name) $email_body .= '- Nume: ' . $user_name . "\n";
            if ($user_phone) $email_body .= '- Telefon: ' . $user_phone . "\n";
            $email_body .= "\n";
        }
        $email_body .= 'Vă rog să-mi aprobați contul B2B. Atașez certificatul CUI sau documentul necesar.' . "\n\n" . 'Mulțumesc!';
    }
    ?>
    <div class="webgsm-b2b-teaser" style="margin:10px 0;padding:8px 12px;background:#f8fafc;border:1px solid #e2e8f0;border-left:2px solid #94a3b8;border-radius:4px;font-size:12px;color:#64748b;">
        <div style="color:#475569;line-height:1.5;">
            <span style="color:#64748b;">Ești service GSM?</span>
            <strong style="color:#334155;font-weight:500;"> Beneficiezi de prețuri B2B și discounturi permanente pentru parteneri.</strong>
        </div>
        <?php if ($is_logged_in) : ?>
            <div style="margin-top:8px;">
                <a href="mailto:info@webgsm.ro?subject=<?php echo rawurlencode($email_subject); ?>&body=<?php echo rawurlencode($email_body); ?>" style="display:inline-block;padding:5px 10px;background:#f1f5f9;color:#475569;font-size:11px;font-weight:500;border:1px solid #cbd5e1;border-radius:3px;text-decoration:none;" onmouseover="this.style.background='#e2e8f0';this.style.borderColor='#94a3b8';" onmouseout="this.style.background='#f1f5f9';this.style.borderColor='#cbd5e1';">Solicită cont B2B</a>
            </div>
        <?php else : ?>
            <div style="margin-top:8px;">
                <a href="<?php echo esc_url(add_query_arg('tip_client', 'pj', wc_get_page_permalink('myaccount'))); ?>" style="display:inline-block;padding:5px 10px;color:#475569;font-size:11px;text-decoration:underline;" onmouseover="this.style.color='#334155';" onmouseout="this.style.color='#475569';">Cont gratuit</a>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

// B. ALERT ÎN CART (subtotal > 5.000 lei) – mesaj simplu, fără preț/discount
add_action('woocommerce_cart_totals_after_order_total', 'webgsm_b2b_teaser_cart', 10);
add_action('woocommerce_review_order_after_order_total', 'webgsm_b2b_teaser_cart', 10);

function webgsm_b2b_teaser_cart() {
    if (is_user_logged_in()) {
        if (class_exists('WebGSM_B2B_Pricing')) {
            $b2b_plugin = WebGSM_B2B_Pricing::instance();
            if ($b2b_plugin->is_user_pj()) return;
        }
    }
    $cart = WC()->cart;
    if (!$cart) return;
    if ($cart->get_subtotal() < 5000) return;
    ?>
    <tr class="webgsm-b2b-cart-alert">
        <th colspan="2" style="border-top:2px dashed #bfdbfe !important;padding-top:15px !important;padding-bottom:15px !important;">
            <div style="background:linear-gradient(135deg,#eff6ff 0%,#dbeafe 100%);border:1px solid #bfdbfe;border-radius:8px;padding:15px;text-align:center;">
                <div style="color:#1e40af;font-weight:600;font-size:15px;margin-bottom:12px;">Beneficiezi de prețuri B2B și discounturi permanente pentru parteneri.</div>
                <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
                    <a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>" class="button alt" style="background:#2563eb !important;color:#fff !important;padding:10px 20px !important;border-radius:6px !important;text-decoration:none !important;">Cont gratuit</a>
                    <a href="<?php echo esc_url(home_url('/despre-b2b/')); ?>" class="button" style="background:transparent !important;color:#2563eb !important;border:1px solid #2563eb !important;padding:10px 20px !important;border-radius:6px !important;text-decoration:none !important;">Află mai multe</a>
                </div>
            </div>
        </th>
    </tr>
    <?php
}
