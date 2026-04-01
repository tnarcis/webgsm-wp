<?php
/**
 * Plugin Name: WebGSM Anunță-mă la stoc
 * Description: Formular „Anunță-mă” pe produsele fără stoc; trimite email când produsul revine în stoc (wp_mail). Opțional integrare Mailchimp.
 * Version: 1.0.0
 * Author: WebGSM
 * Requires at least: 5.0
 * Requires PHP: 7.2
 */

if (!defined('ABSPATH')) exit;

define('WEBGSM_BIS_VERSION', '1.0.0');
define('WEBGSM_BIS_PATH', plugin_dir_path(__FILE__));
define('WEBGSM_BIS_URL', plugin_dir_url(__FILE__));

define('WEBGSM_BIS_META_KEY', '_webgsm_back_in_stock_emails');

add_action('plugins_loaded', function() {
    if (!class_exists('WooCommerce')) return;

    add_action('woocommerce_single_product_summary', 'webgsm_bis_render_form', 35);
    add_action('wp_enqueue_scripts', 'webgsm_bis_assets');
    add_action('wp_ajax_webgsm_bis_subscribe', 'webgsm_bis_ajax_subscribe');
    add_action('wp_ajax_nopriv_webgsm_bis_subscribe', 'webgsm_bis_ajax_subscribe');
    add_action('woocommerce_product_set_stock_status', 'webgsm_bis_on_stock_status_change', 10, 3);

    if (is_admin()) {
        add_action('admin_menu', 'webgsm_bis_admin_menu');
    }
});

function webgsm_bis_assets() {
    if (!is_product()) {
        return;
    }

    global $product, $post;

    // Asigură-te că avem un obiect produs valid (nu string/ID simplu).
    if (!$product instanceof WC_Product) {
        $product_id = 0;
        if (!empty($post) && isset($post->ID)) {
            $product_id = (int) $post->ID;
        } else {
            $product_id = (int) get_queried_object_id();
        }

        if ($product_id) {
            $product = wc_get_product($product_id);
        }
    }

    if (!$product instanceof WC_Product || $product->is_in_stock()) {
        return;
    }

    wp_enqueue_style('webgsm-bis', WEBGSM_BIS_URL . 'assets/bis.css', [], WEBGSM_BIS_VERSION);
    wp_enqueue_script('webgsm-bis', WEBGSM_BIS_URL . 'assets/bis.js', ['jquery'], WEBGSM_BIS_VERSION, true);
    wp_localize_script('webgsm-bis', 'webgsmBis', [
        'ajaxurl'    => admin_url('admin-ajax.php'),
        'nonce'      => wp_create_nonce('webgsm_bis_subscribe'),
        'product_id' => $product->get_id(),
    ]);
}

function webgsm_bis_render_form() {
    global $product;
    if (!$product || $product->is_in_stock()) return;
    ?>
    <div class="webgsm-bis-block">
        <p class="webgsm-bis-title">Stoc epuizat temporar.</p>
        <p class="webgsm-bis-desc">Introdu emailul tău pentru notificare.</p>
        <form class="webgsm-bis-form" data-product-id="<?php echo absint($product->get_id()); ?>">
            <input type="email" name="email" class="webgsm-bis-email" placeholder="Email" required />
            <button type="submit" class="button webgsm-bis-btn">Anunță-mă</button>
            <p class="webgsm-bis-msg" aria-live="polite"></p>
        </form>
    </div>
    <?php
}

function webgsm_bis_ajax_subscribe() {
    check_ajax_referer('webgsm_bis_subscribe', 'nonce');
    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;

    if (!is_email($email) || !$product_id) {
        wp_send_json_error(['message' => 'Introdu o adresă de email validă.']);
    }

    $product = wc_get_product($product_id);
    if (!$product) {
        wp_send_json_error(['message' => 'Produs invalid.']);
    }
    if ($product->is_in_stock()) {
        wp_send_json_error(['message' => 'Produsul este deja în stoc.']);
    }

    $list = get_post_meta($product_id, WEBGSM_BIS_META_KEY, true);
    if (!is_array($list)) $list = [];
    $email_lower = strtolower($email);
    if (in_array($email_lower, $list, true)) {
        wp_send_json_success(['message' => 'Ești deja înscris. Te vom anunța la revenirea în stoc.']);
    }

    $list[] = $email_lower;
    update_post_meta($product_id, WEBGSM_BIS_META_KEY, $list);

    wp_send_json_success(['message' => 'Te vom anunța când produsul revine în stoc.']);
}

function webgsm_bis_on_stock_status_change($product_id, $status, $product = null) {
    if ($status !== 'instock') return;

    $list = get_post_meta($product_id, WEBGSM_BIS_META_KEY, true);
    if (!is_array($list) || empty($list)) return;

    $product = $product ? $product : wc_get_product($product_id);
    if (!$product) return;

    $title = $product->get_name();
    $url = $product->get_permalink();
    $subject = sprintf('[%s] Produsul „%s” este din nou în stoc', get_bloginfo('name'), $title);
    $body = sprintf(
        "Bună ziua,\n\nTe anunțăm că produsul „%s” este din nou disponibil.\n\nPoți comanda aici: %s\n\n— %s",
        $title,
        $url,
        get_bloginfo('name')
    );
    $headers = ['Content-Type: text/plain; charset=UTF-8'];

    foreach ($list as $email) {
        if (!is_email($email)) continue;
        wp_mail($email, $subject, $body, $headers);
    }

    delete_post_meta($product_id, WEBGSM_BIS_META_KEY);
}

function webgsm_bis_admin_menu() {
    add_submenu_page(
        'woocommerce',
        'Anunță-mă la stoc',
        'Anunță-mă la stoc',
        'manage_woocommerce',
        'webgsm-back-in-stock',
        'webgsm_bis_settings_page'
    );
}

function webgsm_bis_settings_page() {
    ?>
    <div class="wrap">
        <h1>WebGSM Anunță-mă la stoc</h1>
        <p>Pluginul afișează pe produsele fără stoc un formular „Anunță-mă”. La revenirea în stoc, abonații primesc un email prin <code>wp_mail</code>.</p>
        <p><strong>Integrare Mailchimp:</strong> în versiuni viitoare se va putea opți pentru trimiterea listei către Mailchimp în loc de (sau în plus față de) wp_mail.</p>
    </div>
    <?php
}
