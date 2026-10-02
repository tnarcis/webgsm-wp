<?php
/**
 * Helper: verifică dacă modul debug WebGSM este activ (URL conține webgsm_b2b_debug=1).
 * Folosit pentru a afișa console.log doar când e necesar debugging.
 */
if (!function_exists('webgsm_is_debug_mode')) {
    function webgsm_is_debug_mode() {
        return isset($_GET['webgsm_b2b_debug']) && sanitize_text_field($_GET['webgsm_b2b_debug']) === '1';
    }
}

// Înarcă stilurile temei părinte
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('martfury-parent', get_template_directory_uri() . '/style.css');
});

// Header account menu — nu pe archive catalog (comportament ca tema părinte, fără override în loop).
add_action(
    'after_setup_theme',
    static function () {
        if (webgsm_child_should_skip_heavy_child_on_catalog()) {
            return;
        }
        require_once get_stylesheet_directory() . '/includes/header-account-menu.php';
    },
    4
);

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

// Ascunde butonul mare "Vezi cos" din popup "Adăugat în coș" — doar unde există add-to-cart
add_action('wp_footer', function() {
    if (is_admin() || !function_exists('is_woocommerce')) {
        return;
    }
    ?>
    <script>
    jQuery(function($) {
        function hideViewCartButton() {
            $('.message-box .btn-button, .message-box .button.wc-forward, .message-box a.button[href*="cart"]').hide();
        }
        $(document.body).on('added_to_cart', function() {
            setTimeout(hideViewCartButton, 50);
            setTimeout(hideViewCartButton, 200);
        });
    });
    </script>
    <?php
}, 999);

/**
 * Stele în loop catalog — oprit implicit (parent merge; activare: define WEBGSM_CHILD_CATALOG_RATINGS true).
 */
add_action(
    'wp',
    static function () {
        if (!defined('WEBGSM_CHILD_CATALOG_RATINGS') || !WEBGSM_CHILD_CATALOG_RATINGS) {
            return;
        }
        if (is_admin() || !function_exists('is_shop')) {
            return;
        }
        if (!is_shop() && !is_product_category() && !is_product_tag() && !is_product_taxonomy()) {
            return;
        }
        require_once get_stylesheet_directory() . '/includes/webgsm-catalog-ratings.php';
    },
    1
);

/**
 * Repair Reel — ~65KB PHP; încarcă doar pe rutele /r/ și /estimeaza-reparatia/ (+ flush rewrite).
 */
add_action('after_setup_theme', function () {
    $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
    $need = (strpos($uri, '/r/') !== false || strpos($uri, 'estimeaza-reparatia') !== false);
    if (!$need && get_option('webgsm_rr_rewrite_ver') !== '20260821') {
        $need = true;
    }
    if (!$need) {
        return;
    }
    require_once get_stylesheet_directory() . '/includes/repair-reel.php';
}, 6);

// ============================================
// LAZY LOAD - My Account + înregistrare (slug Woo poate fi /contul-meu/, nu doar my-account)
// ============================================
function webgsm_child_request_is_account_context() {
    if (is_admin() && !(defined('DOING_AJAX') && DOING_AJAX)) {
        return false;
    }

    $uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
    if ($uri === '') {
        return false;
    }

    if (stripos($uri, 'wp-login.php') !== false || stripos($uri, 'my-account') !== false) {
        return true;
    }

    $request_path = wp_parse_url($uri, PHP_URL_PATH);
    if (!is_string($request_path) || $request_path === '') {
        return false;
    }
    $request_path = untrailingslashit(strtolower($request_path)) ?: '/';

    foreach (array('woocommerce_myaccount_page_id', 'woocommerce_checkout_page_id', 'woocommerce_cart_page_id') as $option) {
        $page_id = (int) get_option($option);
        if ($page_id <= 0) {
            continue;
        }
        $permalink = get_permalink($page_id);
        if (!$permalink) {
            continue;
        }
        $page_path = wp_parse_url($permalink, PHP_URL_PATH);
        if (!is_string($page_path)) {
            continue;
        }
        $page_path = untrailingslashit(strtolower($page_path)) ?: '/';
        if ($request_path === $page_path || strpos($request_path, $page_path . '/') === 0) {
            return true;
        }
    }

    if (defined('DOING_AJAX') && DOING_AJAX) {
        $action = isset($_REQUEST['action']) ? (string) $_REQUEST['action'] : '';
        if (strpos($action, 'webgsm_') === 0) {
            return true;
        }
        if (in_array($action, array('resend_confirmation', 'admin_confirm_email', 'woocommerce_register'), true)) {
            return true;
        }
    }

    return false;
}

/** POST register (formular Woo) — trebuie încărcat înainte de wp_loaded (WC process_registration). */
function webgsm_child_is_registration_post_request() {
    if (empty($_POST['register'])) {
        return false;
    }
    return isset($_POST['email']) || isset($_POST['woocommerce-register-nonce']);
}

function webgsm_child_should_load_registration_modules() {
    if (webgsm_child_is_registration_post_request()) {
        return true;
    }
    return webgsm_child_request_is_account_context();
}

function webgsm_child_load_account_registration_modules() {
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    webgsm_child_require_include('webgsm-myaccount.php');
    webgsm_child_require_include('registration-enhanced.php');
}

function webgsm_child_load_myaccount_presentation_modules() {
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    webgsm_child_load_account_registration_modules();
    webgsm_child_require_include('my-account-styling.php');
    webgsm_child_require_include('webgsm-myaccount-headers.php');
    webgsm_child_require_include('webgsm-myaccount-modals.php');
}

/**
 * Înregistrarea e în child (registration-enhanced.php), nu într-un plugin WebGSM.
 * B2B doar reacționează la woocommerce_created_customer.
 * Încărcare la init — înainte de WC::process_registration (wp_loaded:20).
 */
add_action(
    'init',
    static function () {
        if (!webgsm_child_should_load_registration_modules()) {
            return;
        }
        webgsm_child_require_include('login-register-ux.php');
        webgsm_child_load_account_registration_modules();
    },
    1
);

add_action(
    'wp',
    static function () {
        if (function_exists('is_account_page') && is_account_page()) {
            webgsm_child_load_myaccount_presentation_modules();
        }
    },
    0
);

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
// Încărcare module child — pe catalog nu tragem comenzi/facturi/checkout (memorie + timeout).
// repair-reel.php — lazy load (after_setup_theme) doar pe /r/ și /estimeaza-reparatia/
// ============================================
function webgsm_child_require_include($basename) {
    $path = get_stylesheet_directory() . '/includes/' . $basename;
    if (is_readable($path)) {
        require_once $path;
    }
}

function webgsm_child_is_likely_catalog_request() {
    if (is_admin() && !(defined('DOING_AJAX') && DOING_AJAX)) {
        return false;
    }
    $uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
    if ($uri === '') {
        return false;
    }
    if (strpos($uri, '/categorie-produs/') !== false) {
        return true;
    }
    return (bool) preg_match('#/(shop|magazin)(/|\?|$)#', $uri);
}

/** Archive shop/categorii: child minimal ≈ parent + CSS brand. */
function webgsm_child_is_catalog_archive() {
    if (!function_exists('is_shop')) {
        return false;
    }
    return is_shop() || is_product_category() || is_product_tag() || is_product_taxonomy();
}

function webgsm_child_should_skip_heavy_child_on_catalog() {
    if (defined('WEBGSM_CHILD_FULL_CATALOG') && WEBGSM_CHILD_FULL_CATALOG) {
        return false;
    }
    if (webgsm_child_is_catalog_archive()) {
        return true;
    }
    return webgsm_child_is_likely_catalog_request();
}

function webgsm_child_load_shared_modules($context = 'full') {
    static $loaded = array();
    if (isset($loaded[$context])) {
        return;
    }
    $loaded[$context] = true;

    if ($context === 'catalog') {
        webgsm_child_require_include('login-register-ux.php');
        webgsm_child_require_include('webgsm-design-system.php');
        webgsm_child_require_include('webgsm-header-primary-menu.php');
        return;
    }

    webgsm_child_require_include('login-register-ux.php');
    webgsm_child_require_include('webgsm-design-system.php');
    webgsm_child_require_include('webgsm-header-primary-menu.php');
    webgsm_child_require_include('setup-categories.php');
    webgsm_child_require_include('setup-attributes.php');
    webgsm_child_require_include('setup-acf-fields.php');
    webgsm_child_require_include('product-inventory-gestiune.php');
}

function webgsm_child_load_catalog_modules() {
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    webgsm_child_load_shared_modules('catalog');
    if (defined('WEBGSM_CHILD_FIX_CATALOG_LOOP') && WEBGSM_CHILD_FIX_CATALOG_LOOP) {
        webgsm_child_require_include('fix-catalog-duplicate-add-to-cart.php');
    }
}

function webgsm_child_load_full_modules() {
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    webgsm_child_load_catalog_modules();
    webgsm_child_require_include('webgsm-order-fiscal.php');
    webgsm_child_require_include('webgsm-anaf.php');
    webgsm_child_require_include('retururi.php');
    webgsm_child_require_include('garantie.php');
    webgsm_child_require_include('awb-tracking.php');
    webgsm_child_require_include('facturi.php');
    webgsm_child_require_include('notificari.php');
    webgsm_child_require_include('n8n-webhooks.php');
    webgsm_child_require_include('facturare-pj.php');
    webgsm_child_require_include('product-specs-tab.php');
    webgsm_child_require_include('webgsm-stock-display.php');
    webgsm_child_require_include('checkout-persist-selections.php');
    webgsm_child_require_include('webgsm-montaj.php');
}

if (webgsm_child_is_likely_catalog_request()) {
    webgsm_child_load_catalog_modules();
    add_action(
        'wp',
        static function () {
            if (!function_exists('is_shop')) {
                webgsm_child_load_full_modules();
                return;
            }
            if (is_shop() || is_product_category() || is_product_tag() || is_product_taxonomy()) {
                return;
            }
            webgsm_child_load_full_modules();
        },
        0
    );
} else {
    webgsm_child_load_full_modules();
}

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
                <div style="color:#009ADA;font-weight:600;font-size:15px;margin-bottom:12px;">Beneficiezi de prețuri B2B și discounturi permanente pentru parteneri.</div>
                <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
                    <a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>" class="button alt" style="background:#0078AD !important;color:#fff !important;padding:10px 20px !important;border-radius:6px !important;text-decoration:none !important;text-shadow:0 1px 2px rgba(0,30,50,0.5) !important;font-weight:700 !important;">Cont gratuit</a>
                    <a href="<?php echo esc_url(home_url('/despre-b2b/')); ?>" class="button" style="background:transparent !important;color:#0078AD !important;border:1px solid #0078AD !important;padding:10px 20px !important;border-radius:6px !important;text-decoration:none !important;">Află mai multe</a>
                </div>
            </div>
        </th>
    </tr>
    <?php
}
