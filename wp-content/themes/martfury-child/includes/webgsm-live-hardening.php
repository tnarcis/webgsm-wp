<?php
/**
 * Hardening live — XML-RPC, fingerprint, autori, headere (fără CSP strict).
 *
 * @package WebGSM
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @return bool True pe producție (nu local/dev).
 */
function webgsm_hardening_is_public_site(): bool {
    if (function_exists('wp_get_environment_type')) {
        $env = wp_get_environment_type();
        if (in_array($env, array('local', 'development'), true)) {
            return false;
        }
    }
    $host = isset($_SERVER['HTTP_HOST']) ? (string) $_SERVER['HTTP_HOST'] : '';
    if ($host !== '' && (strpos($host, '.local') !== false || $host === 'localhost')) {
        return false;
    }

    return true;
}

add_filter('xmlrpc_enabled', '__return_false');

add_filter('wp_headers', static function ($headers) {
    if (isset($headers['X-Pingback'])) {
        unset($headers['X-Pingback']);
    }
    return $headers;
});

add_filter('xmlrpc_methods', static function ($methods) {
    return array();
});

/**
 * Pe producție: nu afișa erori PHP în browser (WP_DEBUG_DISPLAY uitat ON).
 */
add_action('init', static function () {
    if (!webgsm_hardening_is_public_site()) {
        return;
    }
    if (is_admin() || (defined('WP_CLI') && WP_CLI) || (defined('DOING_CRON') && DOING_CRON)) {
        return;
    }
    if (defined('WP_DEBUG_DISPLAY') && !WP_DEBUG_DISPLAY) {
        return;
    }
    @ini_set('display_errors', '0');
}, 0);

// --- Fingerprint (generator meta) ---
remove_action('wp_head', 'wp_generator');
add_filter('the_generator', '__return_empty_string');
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wp_shortlink_wp_head');

add_filter('pre_option_elementor_meta_generator_tag', static function () {
    return '1';
});

add_action('wp_loaded', static function () {
    if (class_exists('RevSliderFront')) {
        remove_action('wp_head', array('RevSliderFront', 'add_meta_generator'));
    }
});

// --- Enumerare autori (REST + ?author= / /author/slug/) ---
add_filter('rest_endpoints', static function ($endpoints) {
    if (!webgsm_hardening_is_public_site() || is_user_logged_in()) {
        return $endpoints;
    }
    unset($endpoints['/wp/v2/users']);
    unset($endpoints['/wp/v2/users/(?P<id>[\d]+)']);

    return $endpoints;
});

add_action('template_redirect', static function () {
    if (!webgsm_hardening_is_public_site()) {
        return;
    }
    if (is_user_logged_in() && current_user_can('list_users')) {
        return;
    }
    if (is_author() || isset($_GET['author'])) {
        wp_safe_redirect(home_url('/'), 301);
        exit;
    }
}, 1);

// --- Headere (fallback dacă mod_headers lipsește; pe LiteSpeed .htaccess le setează) ---
add_action('send_headers', static function () {
    if (is_admin() || headers_sent()) {
        return;
    }
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
});
