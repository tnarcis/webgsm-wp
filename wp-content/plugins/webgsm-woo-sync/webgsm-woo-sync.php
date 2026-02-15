<?php
/**
 * Plugin Name: WebGSM WooCommerce Sync
 * Description: Trimite evenimente (schimbare status comandă) către backend-ul WebGSM. Conform SPEC-plugin-woocommerce-webgsm.
 * Version: 1.0.0
 * Author: WebGSM
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 7.0
 */

if (!defined('ABSPATH')) exit;

define('WEBGSM_WOO_SYNC_VERSION', '1.0.0');
define('WEBGSM_WOO_SYNC_PATH', plugin_dir_path(__FILE__));

require_once WEBGSM_WOO_SYNC_PATH . 'includes/class-webgsm-webhook-sender.php';

add_action('plugins_loaded', function() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-warning"><p>WebGSM Woo Sync necesită WooCommerce activ.</p></div>';
        });
        return;
    }
    WebGSM_Woo_Sync\Webhook_Sender::instance();
});

// Pagină setări în admin
add_action('admin_menu', function() {
    add_options_page(
        'WebGSM Woo Sync',
        'WebGSM Woo Sync',
        'manage_options',
        'webgsm-woo-sync',
        'webgsm_woo_sync_render_settings_page'
    );
});

add_action('admin_init', function() {
    register_setting('webgsm_woo_sync', 'webgsm_woo_sync_endpoint_url', [
        'type' => 'string',
        'sanitize_callback' => 'esc_url_raw',
    ]);
    register_setting('webgsm_woo_sync', 'webgsm_woo_sync_secret', [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    register_setting('webgsm_woo_sync', 'webgsm_woo_sync_status_completed', [
        'type' => 'integer',
        'default' => 1,
    ]);
    register_setting('webgsm_woo_sync', 'webgsm_woo_sync_status_cancelled', [
        'type' => 'integer',
        'default' => 1,
    ]);
    register_setting('webgsm_woo_sync', 'webgsm_woo_sync_status_refunded', [
        'type' => 'integer',
        'default' => 1,
    ]);
    register_setting('webgsm_woo_sync', 'webgsm_woo_sync_log_requests', [
        'type' => 'integer',
        'default' => 0,
    ]);
});

function webgsm_woo_sync_render_settings_page() {
    if (!current_user_can('manage_options')) return;
    $url = get_option('webgsm_woo_sync_endpoint_url', '');
    $secret = get_option('webgsm_woo_sync_secret', '');
    $status_completed = (int) get_option('webgsm_woo_sync_status_completed', 1);
    $status_cancelled = (int) get_option('webgsm_woo_sync_status_cancelled', 1);
    $status_refunded = (int) get_option('webgsm_woo_sync_status_refunded', 1);
    $log_requests = (int) get_option('webgsm_woo_sync_log_requests', 0);
    ?>
    <div class="wrap">
        <h1>WebGSM WooCommerce Sync</h1>
        <p class="description">Trimite evenimente la schimbarea statusului comenzii către backend-ul WebGSM (stoc, facturare).</p>
        <form method="post" action="options.php">
            <?php settings_fields('webgsm_woo_sync'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="webgsm_woo_sync_endpoint_url">URL endpoint WebGSM</label></th>
                    <td>
                        <input type="url" name="webgsm_woo_sync_endpoint_url" id="webgsm_woo_sync_endpoint_url"
                               value="<?php echo esc_attr($url); ?>" class="regular-text"
                               placeholder="https://api.webgsm.ro/webhook/woo/order">
                        <p class="description">Ex: https://api.webgsm.ro/webhook/woo/order</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="webgsm_woo_sync_secret">Secret (HMAC)</label></th>
                    <td>
                        <input type="password" name="webgsm_woo_sync_secret" id="webgsm_woo_sync_secret"
                               value="<?php echo esc_attr($secret); ?>" class="regular-text" autocomplete="off">
                        <p class="description">Secret partajat cu backend-ul WebGSM pentru semnătura X-WebGSM-Signature.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Statusuri pentru care se trimite webhook</th>
                    <td>
                        <label><input type="checkbox" name="webgsm_woo_sync_status_completed" value="1" <?php checked($status_completed, 1); ?>> Completed</label> (scădere stoc + factură pe WebGSM)<br>
                        <label><input type="checkbox" name="webgsm_woo_sync_status_cancelled" value="1" <?php checked($status_cancelled, 1); ?>> Cancelled</label> (reintrare stoc)<br>
                        <label><input type="checkbox" name="webgsm_woo_sync_status_refunded" value="1" <?php checked($status_refunded, 1); ?>> Refunded</label> (reintrare stoc)
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="webgsm_woo_sync_log_requests">Log requests</label></th>
                    <td>
                        <label><input type="checkbox" name="webgsm_woo_sync_log_requests" value="1" <?php checked($log_requests, 1); ?>> Activează log (debug.log)</label>
                    </td>
                </tr>
            </table>
            <?php submit_button('Salvează setările'); ?>
        </form>
        <hr>
        <p><strong>Facturare SmartBill:</strong> Generarea facturilor este implementată în tema (Setări SmartBill). Acest plugin doar notifică WebGSM la schimbare status; backend-ul poate gestiona stocul și eventual facturarea.</p>
    </div>
    <?php
}
