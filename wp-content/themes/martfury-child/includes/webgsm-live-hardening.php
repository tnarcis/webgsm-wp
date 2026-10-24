<?php
/**
 * Hardening live — XML-RPC, pingback, fără afișare erori pe frontend dacă wp-config e greșit.
 *
 * @package WebGSM
 */

if (!defined('ABSPATH')) {
    exit;
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
    if (function_exists('wp_get_environment_type')) {
        $env = wp_get_environment_type();
        if (in_array($env, array('local', 'development'), true)) {
            return;
        }
    } else {
        $host = isset($_SERVER['HTTP_HOST']) ? (string) $_SERVER['HTTP_HOST'] : '';
        if ($host !== '' && (strpos($host, '.local') !== false || $host === 'localhost')) {
            return;
        }
    }
    if (is_admin() || (defined('WP_CLI') && WP_CLI) || (defined('DOING_CRON') && DOING_CRON)) {
        return;
    }
    if (defined('WP_DEBUG_DISPLAY') && !WP_DEBUG_DISPLAY) {
        return;
    }
    @ini_set('display_errors', '0');
}, 0);
