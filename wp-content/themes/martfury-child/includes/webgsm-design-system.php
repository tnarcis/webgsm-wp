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
 * Inline pe ultimul stylesheet Elementor (fără duplicat în wp_head).
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
 * Fallback JS dezactivat — CSS-ul Elementor inline e suficient; JS pe homepage încetinea browserul.
 */
add_action('wp_footer', 'webgsm_product_section_bg_force_script', 1);
function webgsm_product_section_bg_force_script() {
    return;
}

// CSS design system — fișier extern (cache browser), nu inline în HTML
add_action('wp_enqueue_scripts', 'webgsm_enqueue_design_system_css', 999999);
function webgsm_enqueue_design_system_css() {
    if (is_admin()) {
        return;
    }
    $file = get_stylesheet_directory() . '/assets/css/webgsm-design-system.css';
    if (!file_exists($file)) {
        return;
    }
    wp_enqueue_style(
        'webgsm-design-system',
        get_stylesheet_directory_uri() . '/assets/css/webgsm-design-system.css',
        array(),
        (string) filemtime($file)
    );
}

/**
 * FiboSearch (Ajax Search): forțează culorile de hover/highlight pe brandul WebGSM.
 * Setarea din plugin avea #00c3ff (cyan) — diferit de --wgsm-brand (#0078AD).
 */
add_filter('dgwt/wcas/settings/load_value/key=sug_hover_color', function ($value) {
    return '#E6F4FA'; // fundal hover = tint brand
});
add_filter('dgwt/wcas/settings/load_value/key=sug_highlight_color', function ($value) {
    return '#0078AD';
});
add_filter('dgwt/wcas/settings/load_value/key=text_submit_color', function ($value) {
    return '#ffffff';
});
add_filter('dgwt/wcas/settings/load_value/key=bg_submit_color', function ($value) {
    return '#0078AD';
});
add_filter('dgwt/wcas/settings/load_value/key=search_icon_color', function ($value) {
    return '#0078AD';
});

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

// Fix zoom pe mobil la checkout
add_action('wp_head', 'webgsm_mobile_zoom_fix');
function webgsm_mobile_zoom_fix() {
    if (!function_exists('is_checkout') || !is_checkout()) {
        return;
    }
    ?>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <?php
}

// JavaScript pentru reset zoom
add_action('wp_footer', 'webgsm_zoom_reset_script');
function webgsm_zoom_reset_script() {
    if (!function_exists('is_checkout') || !is_checkout()) {
        return;
    }
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
