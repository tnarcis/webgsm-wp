<?php
/**
 * WEBGSM DESIGN SYSTEM – fundal pagină gri, carduri albe, CTA retail etc.
 * Ajustează --wgsm-page-bg dacă vrei exact culoarea din Customizer (header).
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * CSS pentru fundal gri în secțiunile Elementor cu grid / WooCommerce („Nou în stoc”).
 * Include :has(.woocommerce) – multe template-uri nu au ul.products în DOM la fel ca în catalog.
 */
function webgsm_get_product_section_elementor_bg_css() {
    return '
.elementor-section:has(ul.products),
.elementor-section:has(div.products),
.elementor-inner-section:has(ul.products),
.elementor-inner-section:has(div.products),
.elementor-section:has(.wc-block-grid__products),
.elementor-section:has(.woocommerce),
.elementor-inner-section:has(.woocommerce),
.e-con:has(ul.products),
.e-con:has(div.products),
.e-con:has(.woocommerce) {
    background-color: var(--wgsm-page-bg, #f5f6f8) !important;
    background-image: none !important;
    box-shadow: none !important;
}
.elementor-column:has(ul.products),
.elementor-column:has(div.products),
.elementor-column:has(.woocommerce),
.elementor-widget-wrap:has(ul.products),
.elementor-widget-wrap:has(.woocommerce),
.elementor-widget-container:has(ul.products),
.elementor-widget-container:has(.woocommerce) {
    background-color: var(--wgsm-page-bg, #f5f6f8) !important;
    background-image: none !important;
    box-shadow: none !important;
}
.elementor-section:has(ul.products) .elementor-background-overlay,
.elementor-section:has(div.products) .elementor-background-overlay,
.elementor-section:has(.woocommerce) .elementor-background-overlay,
.elementor-inner-section:has(ul.products) .elementor-background-overlay,
.elementor-inner-section:has(.woocommerce) .elementor-background-overlay,
.elementor-column:has(ul.products) .elementor-background-overlay,
.elementor-column:has(.woocommerce) .elementor-background-overlay {
    opacity: 0 !important;
    background: transparent !important;
    display: none !important;
}
body.home .elementor-section:has(.woocommerce),
body.home .elementor-inner-section:has(.woocommerce) {
    background-color: var(--wgsm-page-bg, #f5f6f8) !important;
    background-image: none !important;
}
body.home .elementor-column:has(.woocommerce) {
    background-color: var(--wgsm-page-bg, #f5f6f8) !important;
}
body.home .elementor-widget-woocommerce,
body.home .elementor-widget-wc-products,
body.home .elementor-widget-woocommerce-products {
    background-color: var(--wgsm-page-bg, #f5f6f8) !important;
    background-image: none !important;
}
.e-con:has(ul.products),
.e-con:has(div.products),
.e-con:has(.wc-block-grid__products) {
    background-color: var(--wgsm-page-bg, #f5f6f8) !important;
    background-image: none !important;
}
.wgsm-section-has-products.elementor-section,
.wgsm-section-has-products.elementor-inner-section {
    background-color: var(--wgsm-page-bg, #f5f6f8) !important;
    background-image: none !important;
    box-shadow: none !important;
}
.wgsm-section-has-products.elementor-column,
.wgsm-section-has-products.elementor-widget-wrap,
.wgsm-section-has-products.elementor-widget-container {
    background-color: var(--wgsm-page-bg, #f5f6f8) !important;
    background-image: none !important;
}
.wgsm-section-has-products .elementor-background-overlay {
    opacity: 0 !important;
    background: transparent !important;
    display: none !important;
}
.wgsm-section-has-products.vc_row,
.wgsm-section-has-products.wpb_row,
.wgsm-section-has-products.e-con {
    background-color: var(--wgsm-page-bg, #f5f6f8) !important;
    background-image: none !important;
}
/* Martfury – „Nou în stoc”: .cat-header (titlu + .extra-links) + wrapper + listă dedesubt */
div:has(> .cat-header),
div:has(> .cat-header):has(ul.products),
div:has(> .cat-header):has(.woocommerce),
.mf-section:has(.cat-header),
[class*="mf-"]:has(.cat-header) {
    background-color: var(--wgsm-page-bg, #f5f6f8) !important;
    background-image: none !important;
}
.cat-header {
    background-color: var(--wgsm-page-bg, #f5f6f8) !important;
    background-image: none !important;
    padding: 0.95rem 1rem 1rem !important;
    box-sizing: border-box !important;
}
/* Titlu lizibil – mărime decentă, fără h2 uriaș din temă */
.cat-header .cat-title,
.cat-header h2.cat-title {
    font-size: 1.875rem !important;
    font-weight: 600 !important;
    color: #3a3f4d !important;
    letter-spacing: -0.01em !important;
    margin: 0 !important;
    line-height: 1.28 !important;
    text-transform: none !important;
    background-color: transparent !important;
}
.cat-header .extra-links {
    background-color: transparent !important;
}
.cat-header .extra-links a {
    color: #4b5563 !important;
    font-weight: 600 !important;
    font-size: 1.125rem !important;
}
/* Zona de „body” (grid) imediat sub bara de titlu – frați ai lui .cat-header */
.cat-header ~ ul.products,
.cat-header ~ .woocommerce,
.cat-header ~ .mf-products,
.cat-header ~ div.woocommerce {
    background-color: var(--wgsm-page-bg, #f5f6f8) !important;
    background-image: none !important;
}
/* Mobil / live: gri hex direct (cache, variabile, Elementor după JS) */
@media (max-width: 768px) {
    body .cat-header,
    body .mf-section .cat-header,
    .cat-header ~ ul.products,
    .cat-header ~ .woocommerce,
    .cat-header ~ .mf-products,
    .cat-header ~ div.woocommerce {
        background-color: #f5f6f8 !important;
        background-image: none !important;
    }
}
';
}

/**
 * Inline la ultimul stylesheet Elementor din coadă (include elementor-post-XXX de pe home).
 */
add_action('wp_enqueue_scripts', 'webgsm_inline_product_section_after_elementor', 99999);
function webgsm_inline_product_section_after_elementor() {
    if (is_admin()) {
        return;
    }
    global $wp_styles;
    if (!$wp_styles instanceof WP_Styles || empty($wp_styles->queue)) {
        return;
    }
    $css = webgsm_get_product_section_elementor_bg_css();
    $last_elementor = null;
    foreach ($wp_styles->queue as $handle) {
        if (stripos($handle, 'elementor') !== false) {
            $last_elementor = $handle;
        }
    }
    if ($last_elementor) {
        wp_add_inline_style($last_elementor, $css);
    }
}

/**
 * Ultimul <style> din head – bate orice CSS Elementor încărcat înainte.
 */
add_action('wp_head', 'webgsm_product_section_bg_head_override', 9999999);
function webgsm_product_section_bg_head_override() {
    if (is_admin()) {
        return;
    }
    echo '<style id="webgsm-elementor-bg-override">' . webgsm_get_product_section_elementor_bg_css() . '</style>';
}

/**
 * Fallback JS: Elementor poate scrie fundal în style="" (inclusiv !important) – doar JS îl suprascrie.
 */
add_action('wp_footer', 'webgsm_product_section_bg_force_script', 1);
function webgsm_product_section_bg_force_script() {
    if (is_admin()) {
        return;
    }
    ?>
    <script data-cfasync="false">
    (function() {
        var BG = '#f5f6f8';
        var LAYOUT = ['elementor-section', 'elementor-inner-section', 'elementor-column', 'elementor-widget-wrap', 'elementor-widget-container', 'e-con', 'elementor-container', 'elementor-element', 'mf-section'];
        function isLayout(el) {
            if (!el || !el.classList) return false;
            for (var i = 0; i < LAYOUT.length; i++) {
                if (el.classList.contains(LAYOUT[i])) return true;
            }
            return false;
        }
        /** Elementor folosește shorthand background – trebuie șters înainte de background-color */
        function forceBg(el) {
            if (!el || !el.style) return;
            ['background', 'background-color', 'background-image', 'background-size', 'background-repeat', 'background-position'].forEach(function(p) {
                try { el.style.removeProperty(p); } catch (e) {}
            });
            el.style.setProperty('background-color', BG, 'important');
            el.style.setProperty('background-image', 'none', 'important');
        }
        function paintAncestors(node) {
            var el = node;
            var depth = 0;
            while (el && el !== document.body && depth < 56) {
                if (isLayout(el)) {
                    forceBg(el);
                }
                el = el.parentElement;
                depth++;
            }
        }
        function hasProductGridContext(sec) {
            if (!sec || !sec.querySelector) return false;
            return !!sec.querySelector('ul.products, div.products, .wc-block-grid__products, li.product, .woocommerce .products, .cat-header');
        }
        function paintSectionsFromDom() {
            var scope = document.querySelector('#primary, .site-content, .elementor-location-content') || document.body;
            scope.querySelectorAll('.elementor-section, .e-con').forEach(function(sec) {
                if (sec.closest('.woocommerce-mini-cart, .widget_shopping_cart')) return;
                if (!hasProductGridContext(sec)) return;
                forceBg(sec);
                sec.querySelectorAll('.elementor-inner-section, .elementor-column, .elementor-widget-wrap, .elementor-widget-container').forEach(function(inner) {
                    if (hasProductGridContext(inner)) forceBg(inner);
                });
            });
        }
        function hideOverlays() {
            document.querySelectorAll('.elementor-background-overlay').forEach(function(ov) {
                var sec = ov.closest('.elementor-section, .elementor-inner-section, .e-con');
                if (!sec) return;
                if (hasProductGridContext(sec)) {
                    ov.style.setProperty('opacity', '0', 'important');
                    ov.style.setProperty('display', 'none', 'important');
                }
            });
        }
        function roots() {
            var out = [];
            var seen = new Set();
            function add(n) {
                if (n && !seen.has(n)) { seen.add(n); out.push(n); }
            }
            document.querySelectorAll('ul.products').forEach(function(ul) {
                if (ul.closest('.woocommerce-mini-cart')) return;
                add(ul);
            });
            document.querySelectorAll('div.products').forEach(function(d) {
                if (d.closest('.woocommerce-mini-cart')) return;
                if (d.querySelector('.product')) add(d);
            });
            document.querySelectorAll('.wc-block-grid__products').forEach(add);
            document.querySelectorAll('[class*="elementor-widget-woo"], [class*="elementor-widget-wc"]').forEach(function(w) {
                if (w.querySelector('.product, ul.products, .woocommerce')) add(w);
            });
            document.querySelectorAll('[class*="mf-product"]').forEach(function(el) {
                if (el.querySelector && el.querySelector('.product, li.product')) add(el);
            });
            document.querySelectorAll('.woocommerce .products').forEach(function(el) {
                if (el.closest('.woocommerce-mini-cart')) return;
                if (el.querySelector('.product')) add(el);
            });
            return out;
        }
        /** Martfury: .cat-header (Nou în stoc) – nu e structură Elementor clasică */
        function paintMartfuryCatHeader() {
            document.querySelectorAll('.mf-section').forEach(function(sec) {
                if (!sec.querySelector || !sec.querySelector('.cat-header')) return;
                if (sec.closest('.woocommerce-mini-cart')) return;
                forceBg(sec);
            });
            document.querySelectorAll('.cat-header').forEach(function(h) {
                if (h.closest('.woocommerce-mini-cart')) return;
                forceBg(h);
                var s = h.nextElementSibling;
                var g = 0;
                while (s && g < 10) {
                    if (s.matches && (s.matches('ul.products') || s.classList.contains('woocommerce') || s.classList.contains('products') || s.classList.contains('mf-products') || s.querySelector('ul.products'))) {
                        forceBg(s);
                    }
                    s = s.nextElementSibling;
                    g++;
                }
                var el = h.parentElement;
                var depth = 0;
                while (el && el !== document.body && depth < 30) {
                    if (el.tagName === 'HEADER' || (el.classList && el.classList.contains('site-header'))) break;
                    forceBg(el);
                    el = el.parentElement;
                    depth++;
                }
            });
        }
        function run() {
            paintMartfuryCatHeader();
            roots().forEach(paintAncestors);
            paintSectionsFromDom();
            hideOverlays();
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', run);
        } else {
            run();
        }
        [0, 30, 100, 300, 800, 2000, 4000, 6500].forEach(function(t) { setTimeout(run, t); });
        var n = 0;
        var iv = setInterval(function() {
            run();
            n++;
            if (n >= 12) clearInterval(iv);
        }, 400);
        window.addEventListener('pageshow', function() { run(); });
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) run();
        });
        document.addEventListener('touchstart', function once() { run(); document.removeEventListener('touchstart', once); }, { passive: true });
        var moTimer;
        function scheduleRun() {
            clearTimeout(moTimer);
            moTimer = setTimeout(run, 80);
        }
        if (typeof MutationObserver !== 'undefined' && document.body) {
            var mo = new MutationObserver(function() { scheduleRun(); });
            mo.observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['style', 'class'] });
        }
        if (window.jQuery) {
            jQuery(window).on('elementor/frontend/init', function() { setTimeout(run, 0); });
        }
    })();
    </script>
    <?php
}

// CSS minimal – foarte târziu în head; fundal secțiuni: CSS + JS mai sus (inline Elementor)
add_action('wp_head', 'webgsm_minimal_css', 999999);
function webgsm_minimal_css() {
?>
<style id="webgsm-minimal">
/* Token-uri – albastru vibrant (retail / site), nu slate mort */
:root {
    --wgsm-cta: #2563eb;
    --wgsm-cta-hover: #1d4ed8;
    --wgsm-cta-active: #1e40af;
    --wgsm-cta-glow: rgba(37, 99, 235, 0.28);
    --wgsm-radius: 10px;
    --wgsm-radius-pill: 999px;
    /* Fundal pagină – gri foarte deschis (low contrast vs. carduri albe, gen eMAG) */
    --wgsm-page-bg: #f5f6f8;
    /* Bandă meniu: sus→jos; sus = aceeași intrare ca butoanele Woo (#3b82f6 → --wgsm-cta), apoi ușor mai închis */
    --wgsm-header-nav-strip-shine: linear-gradient(180deg, rgba(255, 255, 255, 0.18) 0%, rgba(255, 255, 255, 0) 42%);
    --wgsm-header-nav-strip: linear-gradient(
        180deg,
        #3b82f6 0%,
        var(--wgsm-cta) 38%,
        #1d4ed8 72%,
        #1e40af 100%
    );
}
/* ============================================
   FUNDAL SITE – gri murdar; cardurile de produs rămân albe (mai jos)
   Header: aliniat la --wgsm-page-bg; footer rămâne tema (Customizer)
   ============================================ */
body {
    background-color: var(--wgsm-page-bg, #f5f6f8) !important;
}
#page,
.site,
.site-main,
.site-content,
#content,
#primary,
.content-area,
.martfury-container,
.woocommerce-page #primary,
.woocommerce-page .content-area,
.tax-product_cat #primary,
.tax-product_tag #primary,
.post-type-archive-product #primary,
.search #primary,
.blog #primary,
.page #primary {
    background-color: var(--wgsm-page-bg, #f5f6f8) !important;
}
/* Pagină produs (PDP): fundal alb – detalii/specificații mai lizibile */
body.single-product,
body.single-product #page,
body.single-product .site,
body.single-product .site-main,
body.single-product .site-content,
body.single-product #content,
body.single-product #primary,
body.single-product .content-area,
body.single-product .martfury-container {
    background-color: #fff !important;
}
/* Sidebar / widget-uri: același fundal (fără „fâșie albă”) */
#secondary,
.widget-area,
.mf-catalog-sidebar,
.woocommerce-sidebar {
    background-color: transparent !important;
}
/* PDP: sidebar alb (după regula transparent, ca să nu rămână gri) */
body.single-product #secondary,
body.single-product .widget-area {
    background-color: #fff !important;
}
/* Header + linie sub header: același gri ca pagina */
.site-header,
#masthead,
.header-sticky,
.site-header .header-main,
.site-header .topbar,
.topbar,
.mobile-header-v2,
.header-mobile {
    background-color: var(--wgsm-page-bg, #f5f6f8) !important;
}
.site-header,
#masthead,
.site-header .header-main {
    border-bottom: 1px solid var(--wgsm-page-bg, #f5f6f8) !important;
    box-shadow: none !important;
}
/* Bandă meniu: overflow visible doar desktop (full-bleed 100vw); mobil evită scroll lateral */
@media (min-width: 992px) {
    .site-header,
    #masthead,
    .site-header .header-main,
    .site-header .header-main .container,
    .site-header .header-main .martfury-container,
    .site-header .header-main .row {
        overflow-x: visible !important;
    }
}
.site-header .header-main .row:has(.col-header-menu) {
    flex-wrap: wrap !important;
    align-items: center !important;
}
.site-header .col-header-menu {
    position: relative;
    z-index: 1;
    background: transparent !important;
    padding: 0.28rem 0.5rem 0.32rem;
    flex: 0 0 100% !important;
    max-width: 100% !important;
    width: 100% !important;
    box-sizing: border-box !important;
}
.site-header .col-header-menu::before {
    content: "";
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    width: 100vw;
    top: 0;
    bottom: 0;
    background: var(--wgsm-header-nav-strip-shine), var(--wgsm-header-nav-strip);
    box-shadow:
        0 2px 12px rgba(15, 23, 42, 0.2),
        inset 0 1px 0 rgba(255, 255, 255, 0.12);
    z-index: 0;
    pointer-events: none;
}
.site-header .col-header-menu > * {
    position: relative;
    z-index: 1;
}
/* Mobil: fără 100vw pe bandă (evită scroll orizontal / iOS); coloana e deja full-width */
@media (max-width: 991px) {
    .site-header .col-header-menu {
        padding: 0.22rem 0.4rem 0.26rem;
    }
    .site-header .col-header-menu::before {
        width: 100%;
        left: 0;
        right: 0;
        transform: none;
    }
}
/* Grid Elementor „Nou în stoc”: webgsm_get_product_section_elementor_bg_css() → inline ultimul handle Elementor + wp_head 9999999 */

/* ============================================
   BUTOANE WOOCOMMERCE – CTA principal (vibrant, retail)
   ============================================ */
.woocommerce .button,
.woocommerce a.button,
.woocommerce button.button,
.woocommerce input.button {
    background: linear-gradient(180deg, #3b82f6 0%, var(--wgsm-cta) 100%) !important;
    color: #fff !important;
    border-radius: var(--wgsm-radius) !important;
    padding: 10px 18px !important;
    font-size: 13px !important;
    font-weight: 600 !important;
    letter-spacing: 0.01em !important;
    border: 1px solid rgba(29, 78, 216, 0.45) !important;
    box-shadow: 0 2px 8px var(--wgsm-cta-glow), inset 0 1px 0 rgba(255, 255, 255, 0.12) !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    line-height: 1.35 !important;
    transition: background 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease, transform 0.15s ease !important;
}

.woocommerce .button:hover,
.woocommerce a.button:hover,
.woocommerce button.button:hover,
.woocommerce input.button:hover {
    background: linear-gradient(180deg, var(--wgsm-cta) 0%, var(--wgsm-cta-hover) 100%) !important;
    border-color: rgba(29, 78, 216, 0.65) !important;
    color: #fff !important;
    box-shadow: 0 6px 20px var(--wgsm-cta-glow) !important;
    transform: translateY(-1px) !important;
}
.woocommerce .button:active,
.woocommerce a.button:active,
.woocommerce button.button:active {
    transform: translateY(0) !important;
}

/* Icon coș (siluetă clară, line-art feel) – mask + culoare buton */
.woocommerce a.add_to_cart_button::before,
.woocommerce button.add_to_cart_button::before,
button.single_add_to_cart_button::before {
    content: "" !important;
    display: inline-block !important;
    width: 18px !important;
    height: 18px !important;
    margin-right: 8px !important;
    flex-shrink: 0 !important;
    background: currentColor !important;
    opacity: 0.92 !important;
    -webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12L8.1 13h7.45c.75 0 1.41-.41 1.75-1.03L21.7 4H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z'/%3E%3C/svg%3E") center / contain no-repeat !important;
    mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12L8.1 13h7.45c.75 0 1.41-.41 1.75-1.03L21.7 4H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z'/%3E%3C/svg%3E") center / contain no-repeat !important;
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

/* ============================================
   CATALOG - ALINIERE PRODUSE (înălțime egală, butoane pe aceeași linie)
   ============================================ */
/* Asigură că cardurile se întind pe înălțime (compatibil cu grid/flex) */
/* Fără gap pe listă: gap-ul flex/grid rupe 4 coloane (25%×4 + spații > 100%) */
ul.products {
    align-items: stretch !important;
}
ul.products li.product {
    display: flex !important;
    flex-direction: column !important;
    /* Spațiu vizual între carduri – doar padding în celulă (nu schimbă numărul de coloane) */
    padding: 6px !important;
    box-sizing: border-box !important;
}
/* product-inner ocupă tot spațiul și folosește flex */
ul.products li.product .product-inner {
    display: flex !important;
    flex-direction: column !important;
    flex: 1 !important;
    width: 100% !important;
    height: 100% !important;
    /* Chenar discret – delimitare între produse în listă */
    border: 1px solid #e5e7eb !important;
    border-radius: 10px !important;
    background: #fff !important;
    padding: 12px !important;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04) !important;
    box-sizing: border-box !important;
}
ul.products li.product .product-inner:hover {
    border-color: #d1d5db !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06) !important;
}
/* mf-product-details: flex column, ocupă spațiul rămas */
ul.products li.product .mf-product-details {
    display: flex !important;
    flex-direction: column !important;
    flex: 1 !important;
    min-height: 0 !important;
}
/* mf-product-content: crește și împinge butoanele jos */
ul.products li.product .mf-product-content {
    flex: 1 !important;
    min-height: 0 !important;
}
/* Titlul: limită înălțime (2-3 linii) pentru consistență */
ul.products li.product .mf-product-content .woocommerce-loop-product__title,
ul.products li.product .mf-product-content .product-title,
ul.products li.product .mf-product-details .woocommerce-loop-product__title,
ul.products li.product .mf-product-details .product-title {
    display: -webkit-box !important;
    -webkit-line-clamp: 3 !important;
    -webkit-box-orient: vertical !important;
    overflow: hidden !important;
    line-height: 1.35 !important;
    min-height: 3.05em !important;
}
/* footer-button și add_to_cart: fixate jos */
ul.products li.product .footer-button,
ul.products li.product .mf-product-details > .button,
ul.products li.product .mf-product-details > a.button,
ul.products li.product .mf-product-details > .add_to_cart_button {
    margin-top: auto !important;
    flex-shrink: 0 !important;
}
/* ============================================
   CATALOG – Adaugă în coș: clar, pe lățimea cardului (layout clasic Martfury)
   ============================================ */
ul.products .product .add_to_cart_button,
ul.products .product .footer-button .button.add_to_cart_button,
ul.products .product .mf-product-details-hover a.add_to_cart_button,
ul.products .product .mf-product-details-hover .button.add_to_cart_button,
ul.products .product .footer-button .add_to_cart_button {
    width: 100% !important;
    max-width: 100% !important;
    box-sizing: border-box !important;
    text-align: center !important;
}
ul.products .product .button,
ul.products .product a.button,
ul.products .product .add_to_cart_button,
ul.products .product .footer-button .button,
ul.products .product .footer-button a,
ul.products .product .action-button a,
ul.products .product .mf-product-details-hover .button,
ul.products .product .mf-product-details-hover a.add_to_cart_button,
ul.products .product .mf-compare-button a,
ul.products .product .compare-button a,
ul.products .product .yith-wcwl-add-to-wishlist a,
ul.products .product a.add_to_wishlist {
    border-radius: var(--wgsm-radius) !important;
    padding: 10px 14px !important;
    font-size: 12px !important;
    font-weight: 600 !important;
    letter-spacing: 0.01em !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    min-height: 40px !important;
    box-sizing: border-box !important;
}
/* Wishlist / compare: outline, accent discret albastru */
ul.products .product .mf-compare-button a,
ul.products .product .compare-button a,
ul.products .product .yith-wcwl-add-to-wishlist a,
ul.products .product a.add_to_wishlist {
    background: #fff !important;
    color: #475569 !important;
    border: 1px solid #bfdbfe !important;
    box-shadow: none !important;
    background-image: none !important;
}
ul.products .product .mf-compare-button a:hover,
ul.products .product .compare-button a:hover,
ul.products .product .yith-wcwl-add-to-wishlist a:hover,
ul.products .product a.add_to_wishlist:hover {
    background: #eff6ff !important;
    color: #1d4ed8 !important;
    border-color: #93c5fd !important;
}
/* Wishlist: fără icon coș fals */
ul.products .product .yith-wcwl-add-to-wishlist a::before,
ul.products .product a.add_to_wishlist::before {
    content: none !important;
    display: none !important;
}

/* Mobil: zone de atingere ≥44px */
@media (max-width: 768px) {
    ul.products .product .add_to_cart_button,
    ul.products .product .footer-button .button.add_to_cart_button,
    ul.products .product .mf-product-details-hover a.add_to_cart_button {
        min-height: 44px !important;
        padding: 12px 14px !important;
        font-size: 13px !important;
    }
    .entry-summary form.cart,
    .product-summary form.cart,
    .summary form.cart {
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 12px !important;
    }
    .entry-summary .single_add_to_cart_button,
    .product-summary .single_add_to_cart_button,
    .summary .single_add_to_cart_button {
        width: 100% !important;
        max-width: 100% !important;
        justify-content: center !important;
    }
    .entry-summary .quantity,
    .product-summary .quantity,
    .summary .quantity {
        justify-content: center !important;
    }
}

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
    color: var(--wgsm-cta) !important;
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
    border-bottom-color: var(--wgsm-cta) !important;
}

/* Ascunde săgețile din input number */
.quantity input.qty::-webkit-outer-spin-button,
.quantity input.qty::-webkit-inner-spin-button {
    -webkit-appearance: none !important;
    margin: 0 !important;
}

/* Buton "Adaugă în coș" – pagina produs */
.entry-summary .single_add_to_cart_button,
.product-summary .single_add_to_cart_button,
.summary .single_add_to_cart_button {
    height: 48px !important;
    min-height: 48px !important;
    padding: 0 24px !important;
    font-size: 15px !important;
    font-weight: 600 !important;
    letter-spacing: 0.01em !important;
    border-radius: var(--wgsm-radius) !important;
    background: linear-gradient(180deg, #3b82f6 0%, var(--wgsm-cta) 100%) !important;
    color: #fff !important;
    border: 1px solid rgba(29, 78, 216, 0.45) !important;
    box-shadow: 0 2px 10px var(--wgsm-cta-glow), inset 0 1px 0 rgba(255, 255, 255, 0.12) !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    vertical-align: middle !important;
    margin: 0 !important;
    transition: background 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease, transform 0.15s ease !important;
}
.entry-summary .single_add_to_cart_button:hover,
.product-summary .single_add_to_cart_button:hover,
.summary .single_add_to_cart_button:hover {
    background: linear-gradient(180deg, var(--wgsm-cta) 0%, var(--wgsm-cta-hover) 100%) !important;
    border-color: rgba(29, 78, 216, 0.65) !important;
    color: #fff !important;
    box-shadow: 0 6px 22px var(--wgsm-cta-glow) !important;
    transform: translateY(-1px) !important;
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
/* Breadcrumb – full-bleed desktop; mobil = 100% (fără 100vw) */
@media (min-width: 992px) {
    #page:has(ul.breadcrumbs),
    .site:has(ul.breadcrumbs),
    #primary:has(ul.breadcrumbs),
    .site-content:has(ul.breadcrumbs),
    .content-area:has(ul.breadcrumbs),
    .martfury-container:has(ul.breadcrumbs),
    .page-header:has(ul.breadcrumbs) {
        overflow-x: visible !important;
    }
}
.page-header:has(ul.breadcrumbs),
.page-header:has(.woocommerce-breadcrumb),
body.woocommerce .page-header:has(ul.breadcrumbs),
body.tax-product_cat .page-header:has(ul.breadcrumbs),
body.tax-product_tag .page-header:has(ul.breadcrumbs) {
    background: transparent !important;
    background-image: none !important;
}
ul.breadcrumbs,
.site-content ul.breadcrumbs,
#primary ul.breadcrumbs {
    position: relative !important;
    z-index: 0 !important;
    background: transparent !important;
    background-image: none !important;
    box-shadow: none !important;
}
ul.breadcrumbs::before {
    content: "";
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    width: 100vw;
    top: 0;
    bottom: 0;
    background-color: var(--wgsm-page-bg, #f5f6f8);
    z-index: -1;
    pointer-events: none;
}
ul.breadcrumbs > li,
ul.breadcrumbs > .sep {
    position: relative;
    z-index: 1;
}
/* WooCommerce nav breadcrumb (fără ul.breadcrumbs) */
nav.woocommerce-breadcrumb,
.woocommerce-breadcrumb:not(ul) {
    position: relative !important;
    z-index: 0 !important;
    background: transparent !important;
    background-image: none !important;
    box-shadow: none !important;
}
nav.woocommerce-breadcrumb::before,
.woocommerce-breadcrumb:not(ul)::before {
    content: "";
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    width: 100vw;
    top: 0;
    bottom: 0;
    background-color: var(--wgsm-page-bg, #f5f6f8);
    z-index: -1;
    pointer-events: none;
}
nav.woocommerce-breadcrumb > *,
.woocommerce-breadcrumb:not(ul) > * {
    position: relative;
    z-index: 1;
}
.breadcrumb:not(ul):not(nav),
.breadcrumb-trail {
    background-color: var(--wgsm-page-bg, #f5f6f8) !important;
    background-image: none !important;
}
.mf-breadcrumb,
.mf-breadcrumb-wrap {
    position: relative !important;
    background: transparent !important;
}
.mf-breadcrumb::before,
.mf-breadcrumb-wrap::before {
    content: "";
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    width: 100vw;
    top: 0;
    bottom: 0;
    background-color: var(--wgsm-page-bg, #f5f6f8);
    z-index: -1;
    pointer-events: none;
}
@media (max-width: 991px) {
    ul.breadcrumbs::before,
    nav.woocommerce-breadcrumb::before,
    .woocommerce-breadcrumb:not(ul)::before,
    .mf-breadcrumb::before,
    .mf-breadcrumb-wrap::before {
        width: 100%;
        left: 0;
        right: 0;
        transform: none;
    }
}
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
   SEARCH PRODUSE - FĂRĂ META DE BLOG
   ============================================ */
/* În rezultatele de căutare pentru produse, ascunde "posted by"/data
   care vine din template-ul de post și nu are sens pentru catalog. */
body.search .products .entry-meta,
body.search .products .posted-on,
body.search .products .byline,
body.search .products .author,
body.search .products .meta-author,
body.search .products .meta-date,
body.search .products .cat-links,
body.search .products .tags-links {
    display: none !important;
}

/* Fallback pentru markup-uri alternative din temă (desktop + mobil). */
body.search .product .entry-meta,
body.search .product .posted-on,
body.search .product .byline {
    display: none !important;
}

/* SKU din entry-meta: doar „SKU:” în etichetă albastru umplut; codul text normal, cu spațiu */
.entry-meta li.meta-sku {
    display: inline-flex !important;
    align-items: center;
    gap: 10px;
    margin: 6px 0 0;
    list-style: none;
}
.entry-meta li.meta-sku .webgsm-sku-label {
    display: inline-block;
    padding: 4px 10px;
    font-size: 10px;
    font-weight: 600;
    color: #fff;
    background: #3b82f6;
    border-radius: 6px;
    letter-spacing: 0.02em;
    line-height: 1.3;
}
.entry-meta li.meta-sku .meta-value {
    font-family: ui-monospace, monospace;
    font-size: 13px;
    font-weight: 500;
    color: inherit;
    margin-left: 2px;
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

/* Container badge stoc cu informații livrare - REFĂCUT */
.webgsm-stock-badge {
    margin: 15px 0;
    padding: 12px 16px;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.webgsm-badge-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
}

.webgsm-badge-header .stock-icon {
    display: inline-block;
    vertical-align: middle;
    flex-shrink: 0;
}

.webgsm-badge-header .badge-text {
    font-size: 14px;
    font-weight: 600;
    color: #1f2937;
}

.webgsm-delivery-info {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #f0f0f0;
}

.webgsm-delivery-info .delivery-line {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 6px;
    font-size: 13px;
    color: #666;
    line-height: 1.5;
}

.webgsm-delivery-info .delivery-line:last-child {
    margin-bottom: 0;
}

.webgsm-delivery-info .delivery-icon-wrapper {
    display: inline-flex;
    align-items: center;
    flex-shrink: 0;
}

.webgsm-delivery-info .delivery-icon {
    display: inline-block;
    vertical-align: middle;
    color: #666;
}

.webgsm-delivery-info .delivery-text {
    flex: 1;
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
/* Tabel coș: centrat pe pagină, coloane Preț / Cantitate / Total mai late, pe mijloc */
.woocommerce-cart table.shop_table.cart {
    margin-left: auto !important;
    margin-right: auto !important;
}
.woocommerce-cart table.shop_table.cart td.product-price,
.woocommerce-cart table.shop_table.cart td.product-quantity,
.woocommerce-cart table.shop_table.cart td.product-subtotal {
    text-align: center !important;
    min-width: 110px !important;
    padding-left: 16px !important;
    padding-right: 16px !important;
}
.woocommerce-cart table.shop_table.cart th.product-price,
.woocommerce-cart table.shop_table.cart th.product-quantity,
.woocommerce-cart table.shop_table.cart th.product-subtotal {
    text-align: center !important;
    min-width: 110px !important;
    padding-left: 16px !important;
    padding-right: 16px !important;
}
.woocommerce-cart table.shop_table.cart .quantity {
    justify-content: center !important;
}
/* Butoane coș – gradient CTA + touch-friendly */
.woocommerce-cart .woocommerce .button,
.woocommerce-cart .button,
.woocommerce-cart a.button,
.woocommerce-cart input.button,
.woocommerce-cart .checkout-button,
.woocommerce-cart .wc-proceed-to-checkout .button,
.woocommerce-cart .actions .button,
.cart-collaterals .button,
.wc-proceed-to-checkout a.button {
    background: linear-gradient(180deg, #3b82f6 0%, var(--wgsm-cta) 100%) !important;
    color: #fff !important;
    border-radius: var(--wgsm-radius) !important;
    padding: 12px 20px !important;
    font-size: 14px !important;
    font-weight: 600 !important;
    border: 1px solid rgba(29, 78, 216, 0.45) !important;
    box-shadow: 0 2px 10px var(--wgsm-cta-glow), inset 0 1px 0 rgba(255, 255, 255, 0.1) !important;
    min-height: 44px !important;
}

.woocommerce-cart .button:hover,
.woocommerce-cart a.button:hover {
    background: linear-gradient(180deg, var(--wgsm-cta) 0%, var(--wgsm-cta-hover) 100%) !important;
    border-color: rgba(29, 78, 216, 0.65) !important;
}

/* Buton "Continua cumpărăturile" – secundar, outline */
.woocommerce-cart .wc-continue-shopping,
.woocommerce-cart a.wc-continue-shopping,
a.wc-continue-shopping,
.continue-shopping,
a.continue-shopping {
    background: #fff !important;
    color: var(--wgsm-cta) !important;
    border-radius: var(--wgsm-radius) !important;
    padding: 12px 20px !important;
    border: 2px solid #bfdbfe !important;
    box-shadow: none !important;
    background-image: none !important;
}
.woocommerce-cart .wc-continue-shopping:hover,
.woocommerce-cart a.wc-continue-shopping:hover,
a.wc-continue-shopping:hover,
.continue-shopping:hover,
a.continue-shopping:hover {
    background: #eff6ff !important;
    border-color: var(--wgsm-cta) !important;
    color: var(--wgsm-cta-hover) !important;
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
    background: #64748b !important;
    color: #fff !important;
    border-radius: var(--wgsm-radius) !important;
    padding: 10px 18px !important;
    border: 1px solid #64748b !important;
    box-shadow: none !important;
    background-image: none !important;
}

.woocommerce-cart button[name="update_cart"]:hover {
    background-color: #4b5563 !important;
}

/* Coupon button */
.woocommerce-cart .coupon .button {
    background: linear-gradient(180deg, #3b82f6 0%, var(--wgsm-cta) 100%) !important;
    border-radius: var(--wgsm-radius) !important;
    border: 1px solid rgba(29, 78, 216, 0.45) !important;
}

/* Checkout button mare */
.wc-proceed-to-checkout .checkout-button {
    width: 100% !important;
    padding: 15px 30px !important;
    font-size: 15px !important;
    font-weight: 600 !important;
}

/* Return to shop / înapoi – outline (nu dublează regulile de mai sus pe același selector) */
.woocommerce .return-to-shop a,
a.wc-backward {
    background: #fff !important;
    color: var(--wgsm-cta) !important;
    border-radius: var(--wgsm-radius) !important;
    padding: 12px 20px !important;
    font-size: 14px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    line-height: 1.4 !important;
    height: auto !important;
    min-height: 44px !important;
    border: 2px solid #bfdbfe !important;
    box-shadow: none !important;
    background-image: none !important;
}

.woocommerce .return-to-shop a:hover,
a.wc-backward:hover {
    background: #eff6ff !important;
    border-color: var(--wgsm-cta) !important;
    color: var(--wgsm-cta-hover) !important;
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
    border-radius: 8px !important;
    padding: 10px 20px !important;
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
    border-radius: 8px !important;
    padding: 10px 20px !important;
    font-size: 12px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    line-height: 1.4 !important;
    text-align: center !important;
    min-height: 40px !important;
}

/* Vezi coșul - outline albastru */
.mini-cart .buttons a.wc-forward:first-child,
.woocommerce-mini-cart__buttons a:first-child,
.widget_shopping_cart .buttons a:first-child {
    background-color: #fff !important;
    color: var(--wgsm-cta) !important;
    border: 2px solid #bfdbfe !important;
}

.mini-cart .buttons a.wc-forward:first-child:hover,
.woocommerce-mini-cart__buttons a:first-child:hover {
    background-color: #eff6ff !important;
    color: var(--wgsm-cta-hover) !important;
    -webkit-text-fill-color: var(--wgsm-cta-hover) !important;
    border-color: var(--wgsm-cta) !important;
}

/* Finalizare comandă – CTA vibrant */
.mini-cart .buttons a.checkout,
.woocommerce-mini-cart__buttons a.checkout,
.widget_shopping_cart .buttons a.checkout {
    background: linear-gradient(180deg, #3b82f6 0%, var(--wgsm-cta) 100%) !important;
    color: #fff !important;
    -webkit-text-fill-color: #ffffff !important;
    border: 1px solid rgba(29, 78, 216, 0.45) !important;
    box-shadow: 0 2px 10px var(--wgsm-cta-glow) !important;
}

.mini-cart .buttons a.checkout:hover,
.woocommerce-mini-cart__buttons a.checkout:hover {
    background: linear-gradient(180deg, var(--wgsm-cta) 0%, var(--wgsm-cta-hover) 100%) !important;
    color: #fff !important;
    -webkit-text-fill-color: #ffffff !important;
    border-color: rgba(29, 78, 216, 0.65) !important;
}

/* „Vezi coșul” (wc-forward) pe gradient albastru: text alb – tema poate lăsa negru */
.woocommerce-mini-cart__buttons a.wc-forward,
.mini-cart .buttons a.wc-forward,
.widget_shopping_cart .buttons a.wc-forward,
.cart-panel .woocommerce-mini-cart__buttons a.wc-forward {
    color: #ffffff !important;
    -webkit-text-fill-color: #ffffff !important;
}
/* Când „Vezi coșul” e primul buton = outline (text albastru pe alb), nu gradient */
.woocommerce-mini-cart__buttons a.wc-forward:first-child,
.mini-cart .buttons a.wc-forward:first-child,
.widget_shopping_cart .buttons a.wc-forward:first-child {
    color: var(--wgsm-cta) !important;
    -webkit-text-fill-color: var(--wgsm-cta) !important;
    background-color: #ffffff !important;
    background-image: none !important;
    border: 2px solid #bfdbfe !important;
    box-shadow: none !important;
}
.woocommerce-mini-cart__buttons a.wc-forward:first-child:hover,
.mini-cart .buttons a.wc-forward:first-child:hover,
.widget_shopping_cart .buttons a.wc-forward:first-child:hover {
    color: var(--wgsm-cta-hover) !important;
    -webkit-text-fill-color: var(--wgsm-cta-hover) !important;
    background-color: #eff6ff !important;
    border-color: var(--wgsm-cta) !important;
}
.woocommerce-mini-cart__buttons a.wc-forward:hover,
.mini-cart .buttons a.wc-forward:hover,
.widget_shopping_cart .buttons a.wc-forward:hover {
    color: #ffffff !important;
    -webkit-text-fill-color: #ffffff !important;
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

/* Mobile cart – fix complet bazat pe date runtime */
@media (max-width: 768px) {
    /* Mărește gutter-ul stâng al rândului pentru poza mai mare */
    .woocommerce-cart table.cart tr.cart_item {
        padding-left: 120px !important;
    }
    /* FIX ROOT CAUSE: thumbnail absolut în gutter stâng + poză fără clipping */
    .woocommerce-cart table.cart tr.cart_item td.product-thumbnail {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 114px !important;
        padding: 4px 2px !important;
        display: block !important;
        overflow: visible !important;
    }
    .woocommerce-cart table.cart tr.cart_item td.product-thumbnail > a {
        display: block !important;
    }
    .woocommerce-cart table.cart tr.cart_item td.product-thumbnail > a img {
        width: 110px !important;
        max-width: none !important;
        height: 110px !important;
        object-fit: cover !important;
        display: block !important;
        border-radius: 6px !important;
    }
    /* Nume produs – albastru, bold */
    .woocommerce-cart table.cart tr.cart_item td.product-name {
        padding-top: 8px !important;
        padding-bottom: 4px !important;
    }
    .woocommerce-cart table.cart tr.cart_item td.product-name a {
        font-weight: 600 !important;
        font-size: 14px !important;
        color: #3b82f6 !important;
        line-height: 1.3 !important;
    }
    /* Preț unitar */
    .woocommerce-cart table.cart tr.cart_item td.product-price {
        font-size: 14px !important;
        padding-top: 2px !important;
        padding-bottom: 4px !important;
    }
    /* Total produs – mai mare, mai vizibil */
    .woocommerce-cart table.cart tr.cart_item td.product-subtotal {
        font-size: 16px !important;
        padding-top: 2px !important;
        padding-bottom: 8px !important;
    }
    .woocommerce-cart table.cart tr.cart_item td.product-subtotal .amount {
        font-weight: 800 !important;
        color: #1e293b !important;
        font-size: 17px !important;
    }
    /* Cantitate row */
    .woocommerce-cart table.cart tr.cart_item td.product-quantity {
        padding-top: 4px !important;
        padding-bottom: 6px !important;
        align-items: center !important;
    }
    /* X (mf-remove) – stil clar */
    .woocommerce-cart table.cart tr.cart_item td.product-quantity .product-remove .mf-remove {
        background-color: #fee2e2 !important;
        color: #dc2626 !important;
        border-radius: 6px !important;
        font-size: 18px !important;
        width: 32px !important;
        height: 32px !important;
        line-height: 32px !important;
        display: inline-block !important;
        text-align: center !important;
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

// Inconjoara „SKU:” în span pentru etichetă albastru (doar pe pagina produs)
add_action('wp_footer', function() {
    if (!function_exists('is_product') || !is_product()) return;
    ?>
    <script>
    (function() {
        var li = document.querySelector('.entry-meta li.meta-sku');
        if (!li) return;
        var child = li.firstChild;
        while (child) {
            if (child.nodeType === 3 && child.textContent.replace(/\s/g, '').length > 0) {
                var span = document.createElement('span');
                span.className = 'webgsm-sku-label';
                span.textContent = child.textContent.trim().replace(/:?\s*$/, '') + ' :';
                li.insertBefore(span, child);
                li.removeChild(child);
                break;
            }
            child = child.nextSibling;
        }
    })();
    </script>
    <?php
}, 5);

// Badge stoc cu logică stoc virtual - REFĂCUT COMPLET
add_action('woocommerce_single_product_summary', 'webgsm_stock_badge', 15);
function webgsm_stock_badge() {
    global $product;
    
    $product_id = $product->get_id();
    $stock_qty = $product->get_stock_quantity();
    $is_in_stock = $product->is_in_stock();
    $backorders_allowed = $product->backorders_allowed();
    
    // Verifică dacă ACF este activ
    $acf_active = function_exists('get_field');
    $locatie_stoc = $acf_active ? get_field('locatie_stoc', $product_id) : '';
    
    // SVG Icons - Line Art Style
    $icon_green = '<svg class="stock-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/></svg>';
    $icon_yellow = '<svg class="stock-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/></svg>';
    $icon_red = '<svg class="stock-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/></svg>';
    $icon_truck = '<svg class="delivery-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.28a1 1 0 0 0-.684-.948l-1.923-.641a1 1 0 0 1-.578-.757l-.176-1.066"/><path d="M8 8v4"/><path d="M9 18h6"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg>';
    $icon_box = '<svg class="delivery-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>';
    
    // LOGICA COMPLETĂ STOC VIRTUAL
    $badge_class = '';
    $badge_icon = '';
    $badge_text = '';
    $delivery_info = array();
    
    // Scenariul 1: Stoc > 0 ȘI locatie_stoc = "magazin_webgsm"
    if ($stock_qty > 0 && $locatie_stoc === 'magazin_webgsm') {
        $delivery_info[] = array('icon' => $icon_truck, 'text' => 'Timișoara: Livrare azi până în ora 19');
        $delivery_info[] = array('icon' => $icon_box, 'text' => 'Restul țării: Livrare 24h');
        if ($stock_qty == 1) {
            $badge_class = 'wgsm-badge-limited';
            $badge_icon = $icon_yellow;
            $badge_text = 'Stoc limitat';
        } else {
            $badge_class = 'wgsm-badge-stock';
            $badge_icon = $icon_green;
            $badge_text = 'În Stoc';
        }
    }
    // Scenariul 2: Stoc = 0 ȘI locatie_stoc = "depozit_central"
    elseif ($stock_qty == 0 && $locatie_stoc === 'depozit_central') {
        $badge_class = 'wgsm-badge-stock';
        $badge_icon = $icon_green;
        $badge_text = 'În Stoc Depozit';
        $delivery_info[] = array('icon' => $icon_truck, 'text' => 'Timișoara: Livrare azi sau mâine');
        $delivery_info[] = array('icon' => $icon_box, 'text' => 'Restul țării: Livrare 24h');
    }
    // Scenariul 3: Stoc = 0 ȘI locatie_stoc = "furnizor_extern"
    elseif ($stock_qty == 0 && $locatie_stoc === 'furnizor_extern') {
        $badge_class = 'wgsm-badge-limited';
        $badge_icon = $icon_yellow;
        $badge_text = 'Disponibil';
        $delivery_info[] = array('icon' => $icon_truck, 'text' => 'Timișoara: Livrare 24-48h');
        $delivery_info[] = array('icon' => $icon_box, 'text' => 'Restul țării: Livrare 48-72h');
    }
    // Scenariul 4: Stoc = 0 ȘI locatie_stoc = "magazin_webgsm" (sau gol)
    elseif ($stock_qty == 0 && ($locatie_stoc === 'magazin_webgsm' || empty($locatie_stoc))) {
        $badge_class = 'wgsm-badge-limited';
        $badge_icon = $icon_yellow;
        $badge_text = 'Disponibil la Comandă';
        $delivery_info[] = array('icon' => $icon_box, 'text' => 'Livrare estimată: 3-5 zile');
    }
    // Scenariul 5: Stoc = 0 ȘI backorder = OFF
    elseif ($stock_qty == 0 && !$backorders_allowed && !$is_in_stock) {
        $badge_class = 'wgsm-badge-outofstock';
        $badge_icon = $icon_red;
        $badge_text = 'Stoc Epuizat';
        $delivery_info[] = array('icon' => '', 'text' => 'Momentan indisponibil');
    }
    // Fallback: Logica WooCommerce standard (dacă ACF nu e activ sau alte cazuri)
    else {
        if ($is_in_stock) {
            if ($stock_qty !== null && $stock_qty == 1) {
                $badge_class = 'wgsm-badge-limited';
                $badge_icon = $icon_yellow;
                $badge_text = 'Stoc limitat';
                $delivery_info[] = array('icon' => $icon_truck, 'text' => 'Timișoara: Livrare azi până în ora 19');
                $delivery_info[] = array('icon' => $icon_box, 'text' => 'Restul țării: Livrare 24h');
            } else {
                $badge_class = 'wgsm-badge-stock';
                $badge_icon = $icon_green;
                $badge_text = 'În stoc';
                $delivery_info[] = array('icon' => $icon_truck, 'text' => 'Timișoara: Livrare azi până în ora 19');
                $delivery_info[] = array('icon' => $icon_box, 'text' => 'Restul țării: Livrare 24h');
            }
        } else {
            $badge_class = 'wgsm-badge-outofstock';
            $badge_icon = $icon_red;
            $badge_text = 'Stoc epuizat';
        }
    }
    
    // Afișează badge-ul și informațiile de livrare
    if (!empty($badge_class) && !empty($badge_text)) {
        ?>
        <div class="webgsm-stock-badge">
            <div class="webgsm-badge-header">
                <?php echo $badge_icon; ?>
                <span class="badge-text"><?php echo esc_html($badge_text); ?></span>
            </div>
            <?php if (!empty($delivery_info)) : ?>
                <div class="webgsm-delivery-info">
                    <?php foreach ($delivery_info as $info) : ?>
                        <div class="delivery-line">
                            <?php if (!empty($info['icon'])) : ?>
                                <span class="delivery-icon-wrapper"><?php echo $info['icon']; ?></span>
                            <?php endif; ?>
                            <span class="delivery-text"><?php echo esc_html($info['text']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
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
