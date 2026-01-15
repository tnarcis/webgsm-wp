<?php
/**
 * SECURITATE PRODUCȚIE - Ascunde structura tehnică
 * 
 * Include acest fișier în functions.php DOAR pe site-ul LIVE
 * require_once get_stylesheet_directory() . '/includes/production-security.php';
 * 
 * @package WebGSM
 * @version 1.0
 */

if (!defined('ABSPATH')) exit;

// =============================================
// 1. ELIMINĂ CONSOLE.LOG DIN OUTPUT
// =============================================
add_action('wp_footer', function() {
    ?>
    <script>
    // Override console.log în producție
    if (typeof console !== 'undefined' && !window.location.hostname.includes('local')) {
        console.log = function() {};
        console.warn = function() {};
        console.info = function() {};
        // Păstrează doar console.error pentru erori critice (opțional)
    }
    </script>
    <?php
}, 999);

// =============================================
// 2. ELIMINĂ COMENTARII HTML DIN OUTPUT
// =============================================
add_action('template_redirect', function() {
    if (!is_admin() && !current_user_can('administrator')) {
        ob_start(function($html) {
            // Elimină comentarii HTML (păstrează conditional comments pentru IE)
            $html = preg_replace('/<!--(?!\[if\s)(?!<!)[^\[>].*?-->/s', '', $html);
            
            // Elimină linii goale multiple
            $html = preg_replace('/\n\s*\n\s*\n/', "\n\n", $html);
            
            // Elimină spații albe din fața tagurilor
            $html = preg_replace('/\n\s+</', "\n<", $html);
            
            return $html;
        });
    }
});

// =============================================
// 3. ASCUNDE VERSIUNEA WORDPRESS
// =============================================
remove_action('wp_head', 'wp_generator');
add_filter('the_generator', '__return_empty_string');

// =============================================
// 4. ASCUNDE VERSIUNILE PLUGINURILOR
// =============================================
add_filter('style_loader_src', 'webgsm_remove_version', 9999);
add_filter('script_loader_src', 'webgsm_remove_version', 9999);

function webgsm_remove_version($src) {
    if (strpos($src, 'ver=')) {
        $src = remove_query_arg('ver', $src);
    }
    return $src;
}

// =============================================
// 5. DEZACTIVEAZĂ XML-RPC (securitate)
// =============================================
add_filter('xmlrpc_enabled', '__return_false');

// =============================================
// 6. ELIMINĂ META TAGS EXPUSE
// =============================================
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wp_shortlink_wp_head');

// =============================================
// 7. ASCUNDE WooCommerce VERSION
// =============================================
add_filter('woocommerce_enqueue_styles', function($styles) {
    foreach ($styles as &$style) {
        if (isset($style['version'])) {
            $style['version'] = null;
        }
    }
    return $styles;
});

// =============================================
// 8. DISABLE REST API PENTRU UTILIZATORI NON-AUTENTIFICAȚI
// =============================================
add_filter('rest_authentication_errors', function($result) {
    if (!is_user_logged_in()) {
        return new WP_Error(
            'rest_disabled',
            'REST API disabled',
            ['status' => 401]
        );
    }
    return $result;
});

// =============================================
// 9. ASCUNDE ERORI PHP ÎN FRONTEND
// =============================================
if (!is_admin()) {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

// =============================================
// 10. SECURITY HEADERS
// =============================================
add_action('send_headers', function() {
    if (!is_admin()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }
});

// =============================================
// 11. MINIFICĂ INLINE CSS/JS (opțional)
// =============================================
add_action('wp_head', function() {
    ob_start(function($css) {
        // Minifică CSS inline
        $css = preg_replace('!/\*.*?\*/!s', '', $css); // Elimină comentarii CSS
        $css = preg_replace('/\s+/', ' ', $css); // Elimină spații multiple
        return $css;
    });
}, 1);

add_action('wp_footer', function() {
    ob_end_flush();
}, 999);

/**
 * NOTĂ: Pentru minificare completă, folosește:
 * - Autoptimize plugin
 * - WP Rocket
 * - Cloudflare (minify assets)
 */
