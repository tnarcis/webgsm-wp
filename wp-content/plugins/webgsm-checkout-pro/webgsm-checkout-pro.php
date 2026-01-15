<?php
/**
 * Plugin Name: WebGSM Checkout Pro
 * Description: Checkout personalizat pentru România - PF/PJ, ANAF, adrese salvate
 * Version: 5.1.0
 * Author: WebGSM
 */

if (!defined('ABSPATH')) exit;

define('WEBGSM_CHECKOUT_VERSION', '5.0.0');
define('WEBGSM_CHECKOUT_PATH', plugin_dir_path(__FILE__));
define('WEBGSM_CHECKOUT_URL', plugin_dir_url(__FILE__));

class WebGSM_Checkout_Pro {
    
    private static $instance = null;
    
    private $counties = [
        '' => '-- Selectează județul --',
        'AB' => 'Alba', 'AR' => 'Arad', 'AG' => 'Argeș', 'BC' => 'Bacău',
        'BH' => 'Bihor', 'BN' => 'Bistrița-Năsăud', 'BT' => 'Botoșani',
        'BR' => 'Brăila', 'BV' => 'Brașov', 'B' => 'București', 'BZ' => 'Buzău',
        'CL' => 'Călărași', 'CS' => 'Caraș-Severin', 'CJ' => 'Cluj',
        'CT' => 'Constanța', 'CV' => 'Covasna', 'DB' => 'Dâmbovița',
        'DJ' => 'Dolj', 'GL' => 'Galați', 'GR' => 'Giurgiu', 'GJ' => 'Gorj',
        'HR' => 'Harghita', 'HD' => 'Hunedoara', 'IL' => 'Ialomița',
        'IS' => 'Iași', 'IF' => 'Ilfov', 'MM' => 'Maramureș', 'MH' => 'Mehedinți',
        'MS' => 'Mureș', 'NT' => 'Neamț', 'OT' => 'Olt', 'PH' => 'Prahova',
        'SJ' => 'Sălaj', 'SM' => 'Satu Mare', 'SB' => 'Sibiu', 'SV' => 'Suceava',
        'TR' => 'Teleorman', 'TM' => 'Timiș', 'TL' => 'Tulcea', 'VL' => 'Vâlcea',
        'VS' => 'Vaslui', 'VN' => 'Vrancea',
    ];
    
    public static function instance() {
        if (null === self::$instance) self::$instance = new self();
        return self::$instance;
    }
    
    public function __construct() {
        add_action('plugins_loaded', [$this, 'init']);
    }
    
    public function init() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', function() {
                echo '<div class="error"><p><strong>WebGSM Checkout Pro</strong> necesită WooCommerce.</p></div>';
            });
            return;
        }
        $this->load_classes();
        $this->init_hooks();
    }
    
    private function load_classes() {
        require_once WEBGSM_CHECKOUT_PATH . 'includes/class-checkout-fields.php';
        require_once WEBGSM_CHECKOUT_PATH . 'includes/class-checkout-validate.php';
        require_once WEBGSM_CHECKOUT_PATH . 'includes/class-checkout-save.php';
        require_once WEBGSM_CHECKOUT_PATH . 'includes/class-checkout-anaf.php';
        require_once WEBGSM_CHECKOUT_PATH . 'includes/class-checkout-display.php';
    }
    
    private function init_hooks() {
        add_action('init', [$this, 'start_session'], 1);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('woocommerce_before_checkout_form', [$this, 'checkout_start'], 1);
        add_action('woocommerce_after_checkout_form', [$this, 'checkout_end'], 999);
        add_action('init', [$this, 'remove_default_checkout']);
        
        // AJAX
        add_action('wp_ajax_webgsm_save_address', [$this, 'ajax_save_address']);
        add_action('wp_ajax_webgsm_delete_address', [$this, 'ajax_delete_address']);
        add_action('wp_ajax_webgsm_get_address_for_edit', [$this, 'ajax_get_address_for_edit']);
        add_action('wp_ajax_webgsm_save_company', [$this, 'ajax_save_company']);
        add_action('wp_ajax_nopriv_webgsm_save_company', [$this, 'ajax_save_company']);
        add_action('wp_ajax_webgsm_delete_company', [$this, 'ajax_delete_company']);
        add_action('wp_ajax_webgsm_get_company_for_edit', [$this, 'ajax_get_company_for_edit']);
        add_action('wp_ajax_webgsm_save_person', [$this, 'ajax_save_person']);
        add_action('wp_ajax_nopriv_webgsm_save_person', [$this, 'ajax_save_person']);
        add_action('wp_ajax_webgsm_delete_person', [$this, 'ajax_delete_person']);
        add_action('wp_ajax_webgsm_get_person_for_edit', [$this, 'ajax_get_person_for_edit']);
        add_action('wp_ajax_webgsm_update_cart_item', [$this, 'ajax_update_cart_item']);
        add_action('wp_ajax_nopriv_webgsm_update_cart_item', [$this, 'ajax_update_cart_item']);
        add_action('wp_ajax_webgsm_remove_cart_item', [$this, 'ajax_remove_cart_item']);
        add_action('wp_ajax_nopriv_webgsm_remove_cart_item', [$this, 'ajax_remove_cart_item']);
        add_action('wp_ajax_webgsm_apply_coupon', [$this, 'ajax_apply_coupon']);
        add_action('wp_ajax_nopriv_webgsm_apply_coupon', [$this, 'ajax_apply_coupon']);
        
        add_filter('woocommerce_account_menu_items', [$this, 'add_addresses_menu']);
        add_action('woocommerce_account_adrese-salvate_endpoint', [$this, 'addresses_page_content']);
        add_action('init', [$this, 'add_endpoints']);
        add_action('wp_head', [$this, 'cart_page_css']);
        add_action('woocommerce_thankyou', [$this, 'custom_thankyou_content'], 999);
        // Output hidden billing/shipping inputs inside the checkout <form> so they get included in POST
        add_action('woocommerce_checkout_before_customer_details', [$this, 'render_hidden_form_fields']);
        // Apply our shipping fields on order creation if provided by our form (run late so WooCommerce core doesn't overwrite)
        add_action('woocommerce_checkout_create_order', [$this, 'apply_custom_shipping_fields'], 999, 2);
    }
    
    public function start_session() {
        if (!session_id() && !headers_sent()) session_start();
    }
    
    public function enqueue_assets() {
        if (!is_checkout()) return;
        wp_enqueue_style('webgsm-checkout', WEBGSM_CHECKOUT_URL . 'assets/css/checkout.css', [], WEBGSM_CHECKOUT_VERSION);
        wp_enqueue_script('webgsm-checkout', WEBGSM_CHECKOUT_URL . 'assets/js/checkout.js', ['jquery'], WEBGSM_CHECKOUT_VERSION, true);
        wp_localize_script('webgsm-checkout', 'webgsm_checkout', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('webgsm_nonce'),
            'is_logged_in' => is_user_logged_in(),
        ]);
    }
    
    public function remove_default_checkout() {
        remove_action('woocommerce_checkout_order_review', 'woocommerce_order_review', 10);
    }
    
    public function add_endpoints() {
        add_rewrite_endpoint('adrese-salvate', EP_ROOT | EP_PAGES);
    }
    
    public function add_addresses_menu($items) {
        $new = [];
        foreach ($items as $key => $val) {
            $new[$key] = $val;
            if ($key === 'edit-address') $new['adrese-salvate'] = 'Adrese & Firme Salvate';
        }
        return $new;
    }
    
    private function render_county_dropdown($id, $selected = '') {
        echo '<select id="' . esc_attr($id) . '" required>';
        foreach ($this->counties as $code => $name) {
            $sel = ($selected === $code) ? ' selected' : '';
            echo '<option value="' . esc_attr($code) . '"' . $sel . '>' . esc_html($name) . '</option>';
        }
        echo '</select>';
    }
    
    public function checkout_start() {
        if (!is_checkout()) return;
        
        echo '<div class="webgsm-checkout-wrapper">';
        echo '<div class="webgsm-checkout-main">';
        
        $this->render_products_section();
        $this->render_coupon_section();
        $this->render_invoice_type_section();
        $this->render_addresses_section();
        
        echo '<div class="webgsm-section"><div class="webgsm-section-header">Metoda de plată</div>';
        echo '<div class="webgsm-section-body webgsm-payment-methods"></div></div>';
        
        $this->render_notes_section();
        echo '</div>';
        
        echo '<div class="webgsm-checkout-sidebar">';
        $this->render_summary_section();
        echo '</div>';
        
        $total = WC()->cart->get_total();
        ?>
        <div class="webgsm-mobile-submit">
            <div class="mobile-total"><span>Total:</span><strong><?php echo $total; ?></strong></div>
            <button type="button" class="btn-submit-mobile" id="mobile_place_order">Trimite comanda</button>
        </div>
        <?php
        $this->render_address_popup();
        $this->render_company_popup();
        $this->render_person_popup();
    }
    
    public function checkout_end() { echo '</div>'; }
    
    private function render_products_section() {
        ?>
        <div class="webgsm-section">
            <div class="webgsm-section-header">Produse comandă</div>
            <div class="webgsm-section-body">
                <table class="webgsm-products-table">
                    <thead><tr><th style="width:50%">Produs</th><th style="width:15%">Preț</th><th style="width:15%">Cant.</th><th style="width:15%">Total</th><th style="width:5%"></th></tr></thead>
                    <tbody>
                    <?php foreach (WC()->cart->get_cart() as $key => $item) :
                        $product = $item['data']; $qty = $item['quantity'];
                    ?>
                    <tr data-key="<?php echo esc_attr($key); ?>">
                        <td><div class="product-info"><?php echo $product->get_image('thumbnail'); ?><span class="product-name"><?php echo esc_html($product->get_name()); ?></span></div></td>
                        <td class="price-col"><?php echo WC()->cart->get_product_price($product); ?></td>
                        <td class="qty-col"><select class="qty-select" data-key="<?php echo esc_attr($key); ?>"><?php for ($i=1;$i<=10;$i++): ?><option value="<?php echo $i; ?>" <?php selected($qty,$i); ?>><?php echo $i; ?></option><?php endfor; ?></select></td>
                        <td class="subtotal-col"><?php echo WC()->cart->get_product_subtotal($product, $qty); ?></td>
                        <td class="remove-col"><button type="button" class="remove-item" data-key="<?php echo esc_attr($key); ?>">×</button></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
    
    private function render_coupon_section() {
        ?>
        <div class="webgsm-section webgsm-section-small">
            <div class="webgsm-coupon-row">
                <span class="coupon-label">Cupon:</span>
                <input type="text" id="webgsm_coupon" placeholder="Cod cupon">
                <button type="button" id="apply_coupon_btn" class="btn-secondary">Aplica</button>
                <?php foreach (WC()->cart->get_applied_coupons() as $c): ?>
                <span class="applied-coupon"><?php echo esc_html($c); ?> <a href="<?php echo esc_url(wc_get_cart_url().'?remove_coupon='.$c); ?>">×</a></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
    
    private function render_invoice_type_section() {
        $user_id = get_current_user_id();
        $persons = $user_id ? get_user_meta($user_id, 'webgsm_persons', true) : [];
        $companies = $user_id ? get_user_meta($user_id, 'webgsm_companies', true) : [];
        if (!is_array($persons)) $persons = [];
        if (!is_array($companies)) $companies = [];
        ?>
        <div class="webgsm-section">
            <div class="webgsm-section-header">Tip factură</div>
            <div class="webgsm-section-body">
                <div class="webgsm-radio-group">
                    <label class="webgsm-radio"><input type="radio" name="billing_customer_type" value="pf" checked><span class="radio-mark"></span><span class="radio-label">Persoană fizică</span></label>
                    <label class="webgsm-radio"><input type="radio" name="billing_customer_type" value="pj"><span class="radio-mark"></span><span class="radio-label">Persoană juridică</span></label>
                </div>
                
                <div class="webgsm-persons-list" id="pf_section">
                    <div class="subsection-title">Date facturare PF:</div>
                    <div class="persons-list">
                    <?php if (!empty($persons)) : foreach ($persons as $i => $p) : ?>
                        <label class="webgsm-radio person-item">
                            <input type="radio" name="selected_person" value="<?php echo $i; ?>"
                                data-name="<?php echo esc_attr($p['name']); ?>"
                                data-cnp="<?php echo esc_attr($p['cnp'] ?? ''); ?>"
                                data-phone="<?php echo esc_attr($p['phone'] ?? ''); ?>"
                                data-email="<?php echo esc_attr($p['email'] ?? ''); ?>"
                                data-address="<?php echo esc_attr($p['address'] ?? ''); ?>"
                                data-county="<?php echo esc_attr($p['county'] ?? ''); ?>"
                                data-city="<?php echo esc_attr($p['city'] ?? ''); ?>"
                                data-postcode="<?php echo esc_attr($p['postcode'] ?? ''); ?>"
                                <?php if ($i===0) echo 'checked'; ?>>
                            <span class="radio-mark"></span>
                            <span class="radio-label">
                                <strong><?php echo esc_html($p['name']); ?></strong>
                                <?php if (!empty($p['phone'])): ?><small>Tel: <?php echo esc_html($p['phone']); ?></small><?php endif; ?>
                                <?php if (!empty($p['address'])): ?><small><?php echo esc_html($p['address'].', '.$p['city']); ?></small><?php endif; ?>
                            </span>
                            <button type="button" class="delete-person" data-index="<?php echo $i; ?>">×</button>
                        </label>
                    <?php endforeach; else: ?>
                        <p class="no-items">Nu ai persoane salvate.</p>
                    <?php endif; ?>
                    </div>
                    <button type="button" class="btn-add" id="add_person_btn">+ Adaugă persoană</button>
                </div>
                
                <div class="webgsm-companies-list" id="pj_section" style="display:none;">
                    <div class="subsection-title">Selectează firma:</div>
                    <div class="companies-list">
                    <?php if (!empty($companies)) : foreach ($companies as $i => $c) : ?>
                        <label class="webgsm-radio company-item">
                            <input type="radio" name="selected_company" value="<?php echo $i; ?>"
                                                data-name="<?php echo esc_attr($c['name']); ?>"
                                data-cui="<?php echo esc_attr($c['cui']); ?>"
                                data-reg="<?php echo esc_attr($c['reg'] ?? ''); ?>"
                                data-phone="<?php echo esc_attr($c['phone'] ?? ''); ?>"
                                data-email="<?php echo esc_attr($c['email'] ?? ''); ?>"
                                data-address="<?php echo esc_attr($c['address']); ?>"
                                data-county="<?php echo esc_attr($c['county'] ?? ''); ?>"
                                data-city="<?php echo esc_attr($c['city'] ?? ''); ?>"
                                
                                data-iban="<?php echo esc_attr($c['iban'] ?? ''); ?>"
                                data-bank="<?php echo esc_attr($c['bank'] ?? ''); ?>"
                                <?php if ($i===0) echo 'checked'; ?>>
                            <span class="radio-mark"></span>
                            <span class="radio-label">
                                <strong><?php echo esc_html($c['name']); ?></strong>
                                <small>CUI: <?php echo esc_html($c['cui']); ?><?php if(!empty($c['phone'])) echo ' | '.esc_html($c['phone']); ?></small>
                                <small><?php echo esc_html($c['address'].', '.$c['city']); ?></small>
                            </span>
                            <button type="button" class="delete-company" data-index="<?php echo $i; ?>">×</button>
                        </label>
                    <?php endforeach; else: ?>
                        <p class="no-items">Nu ai firme salvate.</p>
                    <?php endif; ?>
                    </div>
                    <button type="button" class="btn-add" id="add_company_btn">+ Adaugă firmă</button>
                </div>
                

            </div>
        </div>
        <?php
    }
    
    private function render_addresses_section() {
        $user_id = get_current_user_id();
        $addresses = $user_id ? get_user_meta($user_id, 'webgsm_addresses', true) : [];
        if (!is_array($addresses)) $addresses = [];
        ?>
        <div class="webgsm-section">
            <div class="webgsm-section-header">Adresa de livrare</div>
            <div class="webgsm-section-body">
                <div class="same-address-check" style="margin-bottom:15px;padding:12px 15px;background:#f5f5f5;border-radius:4px;">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-weight:500;">
                        <input type="checkbox" id="same_as_billing" name="same_as_billing" value="1" checked style="width:18px;height:18px;">
                        Aceeași cu adresa de facturare
                    </label>
                </div>
                <div id="shipping_address_fields" style="display:none;">
                <?php if ($user_id) : ?>
                    <div class="webgsm-addresses-list">
                    <?php foreach ($addresses as $i => $a) : ?>
                        <label class="webgsm-radio address-item">
                            <input type="radio" name="selected_address" value="<?php echo $i; ?>"
                                data-name="<?php echo esc_attr($a['name'] ?? ''); ?>"
                                data-phone="<?php echo esc_attr($a['phone'] ?? ''); ?>"
                                data-address="<?php echo esc_attr($a['address'] ?? ''); ?>"
                                data-city="<?php echo esc_attr($a['city'] ?? ''); ?>"
                                data-county="<?php echo esc_attr($a['county'] ?? ''); ?>"
                                data-postcode="<?php echo esc_attr($a['postcode'] ?? ''); ?>"
                                <?php if ($i===0) echo 'checked'; ?>>
                            <span class="radio-mark"></span>
                            <span class="radio-label">
                                <strong><?php echo esc_html($a['label'] ?? ($a['name'] ?? '')); ?></strong>
                                <small><?php echo esc_html( ($a['address'] ?? '') . ( (!empty($a['address'] ?? '')) && (!empty($a['city'] ?? '')) ? ', ' : '' ) . ($a['city'] ?? '') ); ?></small>
                            </span>
                            <button type="button" class="delete-address" data-index="<?php echo $i; ?>">×</button>
                        </label>
                    <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn-add" id="add_address_btn">+ Adaugă adresă</button>


                <?php else : ?>
                    <div class="webgsm-guest-form">
                        <div class="form-row">
                            <div class="form-col"><label>Nume *</label><input type="text" id="shipping_first_name"></div>
                            <div class="form-col"><label>Telefon *</label><input type="tel" id="shipping_phone"></div>
                        </div>
                        <div class="form-row"><div class="form-col full"><label>Adresă *</label><input type="text" id="shipping_address_1"></div></div>
                        <div class="form-row">
                            <div class="form-col"><label>Județ *</label><?php $this->render_county_dropdown('shipping_state'); ?></div>
                            <div class="form-col"><label>Localitate *</label><input type="text" id="shipping_city"></div>
                            <div class="form-col"><label>Cod poștal</label><input type="text" id="shipping_postcode"></div>
                        </div>
                    </div>
                <?php endif; ?>
                </div>
                <input type="hidden" name="shipping_country" id="shipping_country" value="RO">
            </div>
        </div>
        <?php
    }
    
    private function render_notes_section() {
        echo '<div class="webgsm-section"><div class="webgsm-section-header">Observații</div>';
        echo '<div class="webgsm-section-body"><textarea name="order_comments" placeholder="Note pentru livrare..."></textarea></div></div>';
    }

    /**
     * Render hidden billing & shipping inputs inside the real checkout <form> so they're submitted.
     */
    public function render_hidden_form_fields() {
        // Billing hidden fields (populated from our UI)
        echo '<input type="hidden" name="billing_company" id="billing_company" value="">';
        echo '<input type="hidden" name="billing_cui" id="billing_cui" value="">';
        echo '<input type="hidden" name="billing_j" id="billing_j" value="">';
        echo '<input type="hidden" name="billing_iban" id="billing_iban" value="">';
        echo '<input type="hidden" name="billing_bank" id="billing_bank" value="">';
        echo '<input type="hidden" name="billing_cnp" id="billing_cnp" value="">';
        echo '<input type="hidden" name="billing_first_name" id="billing_first_name" value="">';
        echo '<input type="hidden" name="billing_last_name" id="billing_last_name" value="">';
        echo '<input type="hidden" name="billing_address_1" id="billing_address_1" value="">';
        echo '<input type="hidden" name="billing_city" id="billing_city" value="">';
        echo '<input type="hidden" name="billing_state" id="billing_state" value="">';
        echo '<input type="hidden" name="billing_postcode" id="billing_postcode" value="">';
        echo '<input type="hidden" name="billing_phone" id="billing_phone" value="">';
        echo '<input type="hidden" name="billing_email" id="billing_email" value="'.esc_attr(WC()->checkout->get_value('billing_email')).'">';
        echo '<input type="hidden" name="billing_country" id="billing_country" value="RO">';

        // Shipping hidden fields (these are used when user chooses a saved address or unchecks same_as_billing)
        echo '<input type="hidden" name="ship_to_different_address" id="ship_to_different_address" value="0">';
        echo '<input type="hidden" name="shipping_first_name" id="shipping_first_name" value="">';
        echo '<input type="hidden" name="shipping_last_name" id="shipping_last_name" value="">';
        echo '<input type="hidden" name="shipping_phone" id="shipping_phone" value="">';
        echo '<input type="hidden" name="shipping_address_1" id="shipping_address_1" value="">';
        echo '<input type="hidden" name="shipping_city" id="shipping_city" value="">';
        echo '<input type="hidden" name="shipping_state" id="shipping_state" value="">';
        echo '<input type="hidden" name="shipping_postcode" id="shipping_postcode" value="">';
        echo '<input type="hidden" name="shipping_country" id="shipping_country" value="RO">';
    }
    
    private function render_summary_section() {
        $subtotal = WC()->cart->get_subtotal();
        $shipping = WC()->cart->get_shipping_total();
        $total = WC()->cart->get_total('');
        $remaining = 250 - $subtotal;
        $applied_coupons = WC()->cart->get_applied_coupons();
        $total_discount = 0;
        
        // Calculeaza discount-ul total (doar pentru subtotal, nu pentru transport)
        if (!empty($applied_coupons)) {
            foreach ($applied_coupons as $coupon_code) {
                $coupon = new WC_Coupon($coupon_code);
                if ($coupon && $coupon->get_id()) {
                    // Verifica daca cuponul este pentru transport gratuit
                    $free_shipping = $coupon->get_free_shipping();
                    
                    // Daca nu este pentru transport gratuit, adauga discount-ul la subtotal
                    if (!$free_shipping) {
                        $discount_amount = WC()->cart->get_coupon_discount_amount($coupon_code);
                        $total_discount += $discount_amount;
                    }
                }
            }
        }
        
        ?>
        <div class="webgsm-summary-box">
            <div class="summary-header">Sumar comandă</div>
            <div class="summary-row"><span>Subtotal:</span><span class="summary-value"><?php echo wc_price($subtotal); ?></span></div>
            
            <?php if ($total_discount > 0) : ?>
                <div class="summary-row summary-row-discount">
                    <span style="font-size: 12px; color: #666;">
                        <?php 
                        $coupon_names = array();
                        foreach ($applied_coupons as $coupon_code) {
                            $coupon = new WC_Coupon($coupon_code);
                            if ($coupon && $coupon->get_id() && !$coupon->get_free_shipping()) {
                                $coupon_names[] = strtoupper($coupon_code);
                            }
                        }
                        if (!empty($coupon_names)) {
                            echo 'Cupon ' . implode(', ', $coupon_names);
                        }
                        ?>
                    </span>
                    <span class="summary-value" style="color: #10b981; font-size: 12px; font-weight: 500;">
                        -<?php echo wc_price($total_discount); ?>
                    </span>
                </div>
            <?php endif; ?>
            
            <div class="summary-row"><span>Transport:</span><span class="summary-value"><?php echo $shipping > 0 ? wc_price($shipping) : 'GRATUIT'; ?></span></div>
            
            <?php 
            // Verifica daca exista cupon pentru transport gratuit
            $has_free_shipping_coupon = false;
            if (!empty($applied_coupons)) {
                foreach ($applied_coupons as $coupon_code) {
                    $coupon = new WC_Coupon($coupon_code);
                    if ($coupon && $coupon->get_id() && $coupon->get_free_shipping()) {
                        $has_free_shipping_coupon = true;
                        break;
                    }
                }
            }
            
            if ($has_free_shipping_coupon) : ?>
                <div class="shipping-notice success">✓ Transport gratuit (cupon aplicat)</div>
            <?php elseif ($remaining > 0) : ?>
                <div class="shipping-notice warning">Mai adaugă <strong><?php echo wc_price($remaining); ?></strong> pentru transport gratuit</div>
            <?php else : ?>
                <div class="shipping-notice success">✓ Transport gratuit!</div>
            <?php endif; ?>
            
            <div class="summary-total"><span>TOTAL:</span><span class="total-value"><?php echo wc_price($total); ?></span></div>
            <button type="submit" class="btn-submit" id="place_order">Trimite comanda</button>
            <p class="terms-note">Prin plasarea comenzii, esti de acord cu <a href="/termeni-si-conditii" target="_blank">T&C</a></p>
        </div>
        <?php
    }
    
    private function render_address_popup() {
        ?>
        <div class="webgsm-popup" id="address_popup">
            <div class="popup-overlay"></div>
            <div class="popup-content">
                <div class="popup-header"><h3>Adaugă adresă</h3><button type="button" class="popup-close">×</button></div>
                <div class="popup-body">
                    <div class="form-row"><div class="form-col"><label>Etichetă</label><input type="text" id="addr_label" placeholder="Acasă, Birou..."></div></div>
                    <div class="form-row">
                        <div class="form-col"><label>Nume *</label><input type="text" id="addr_name"></div>
                        <div class="form-col"><label>Telefon *</label><input type="tel" id="addr_phone"></div>
                    </div>
                    <div class="form-row"><div class="form-col full"><label>Adresă *</label><input type="text" id="addr_address"></div></div>
                    <div class="form-row">
                        <div class="form-col"><label>Localitate *</label><input type="text" id="addr_city"></div>
                        <div class="form-col"><label>Județ *</label><?php $this->render_county_dropdown('addr_county'); ?></div>
                        <div class="form-col"><label>Cod poștal</label><input type="text" id="addr_postcode"></div>
                    </div>
                </div>
                <div class="popup-footer">
                    <button type="button" class="btn-secondary popup-cancel">Anulează</button>
                    <button type="button" class="btn-primary" id="save_address_btn">Salvează</button>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function render_company_popup() {
        ?>
        <div class="webgsm-popup" id="company_popup">
            <div class="popup-overlay"></div>
            <div class="popup-content">
                <div class="popup-header"><h3>Adaugă firmă</h3><button type="button" class="popup-close">×</button></div>
                <div class="popup-body">
                    <?php if (!is_user_logged_in()): ?>
                    <div style="background:#fff3e0;padding:10px 15px;border-radius:4px;margin-bottom:15px;font-size:13px;border-left:3px solid #ff9800;">
                        <strong>Notă:</strong> <a href="<?php echo wc_get_page_permalink('myaccount'); ?>">Autentifică-te</a> pentru a salva firma.
                    </div>
                    <?php endif; ?>
                    <div class="form-row">
                        <div class="form-col"><label>CUI *</label><input type="text" id="company_cui" placeholder="12345678"></div>
                        <div class="form-col" style="display:flex;align-items:flex-end;">
                            <small class="anaf-hint" style="color:#666;font-size:13px;">Căutare automată după CUI</small>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-col full"><label>Denumire *</label><input type="text" id="company_name"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-col"><label>Nr. Reg. Com. *</label><input type="text" id="company_reg" placeholder="J40/1234/2020"></div>
                        <div class="form-col"><label>Telefon *</label><input type="tel" id="company_phone" placeholder="07xxxxxxxx"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-col full"><label>Email *</label><input type="email" id="company_email" placeholder="contact@firma.ro"></div>
                    </div>
                    <div style="border-top:1px solid #eee;margin:15px 0;padding-top:15px;"><strong>Adresa sediu:</strong></div>
                    <div class="form-row">
                        <div class="form-col full"><label>Adresă *</label><input type="text" id="company_address"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-col"><label>Județ *</label><?php $this->render_county_dropdown('company_county'); ?></div>
                        <div class="form-col"><label>Localitate *</label><input type="text" id="company_city"></div>
                    </div>
                    <div id="anaf_status" style="display:none;padding:10px;border-radius:4px;margin-top:10px;"></div>
                </div>
                <div class="popup-footer">
                    <button type="button" class="btn-secondary popup-cancel">Anulează</button>
                    <button type="button" class="btn-primary" id="save_company_btn">Salvează</button>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function render_person_popup() {
        ?>
        <div class="webgsm-popup" id="person_popup">
            <div class="popup-overlay"></div>
            <div class="popup-content" style="max-width:550px;">
                <div class="popup-header"><h3>Adaugă persoană</h3><button type="button" class="popup-close">×</button></div>
                <div class="popup-body">
                    <?php if (!is_user_logged_in()): ?>
                    <div style="background:#fff3e0;padding:10px 15px;border-radius:4px;margin-bottom:15px;font-size:13px;border-left:3px solid #ff9800;">
                        <strong>Notă:</strong> <a href="<?php echo wc_get_page_permalink('myaccount'); ?>">Autentifică-te</a> pentru a salva.
                    </div>
                    <?php endif; ?>
                    <div class="form-row">
                        <div class="form-col"><label>Nume complet *</label><input type="text" id="person_name"></div>
                        <div class="form-col"><label>CNP (opțional)</label><input type="text" id="person_cnp" maxlength="13"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-col"><label>Telefon *</label><input type="tel" id="person_phone" placeholder="07xxxxxxxx"></div>
                        <div class="form-col"><label>Email *</label><input type="email" id="person_email"></div>
                    </div>
                    <div style="border-top:1px solid #eee;margin:15px 0;padding-top:15px;"><strong>Adresă facturare:</strong></div>
                    <div class="form-row"><div class="form-col full"><label>Adresă *</label><input type="text" id="person_address"></div></div>
                    <div class="form-row">
                        <div class="form-col"><label>Județ *</label><?php $this->render_county_dropdown('person_county'); ?></div>
                        <div class="form-col"><label>Localitate *</label><input type="text" id="person_city"></div>
                    </div>
                    <div class="form-row"><div class="form-col"><label>Cod poștal</label><input type="text" id="person_postcode" maxlength="6"></div></div>
                </div>
                <div class="popup-footer">
                    <button type="button" class="btn-secondary popup-cancel">Anulează</button>
                    <button type="button" class="btn-primary" id="save_person_btn">Salvează</button>
                </div>
            </div>
        </div>
        <?php
    }
    
    // AJAX Handlers
    public function ajax_save_address() {
        check_ajax_referer('webgsm_nonce', 'nonce');
        if (!is_user_logged_in()) wp_send_json_error('Not logged in');
        $user_id = get_current_user_id();
        $addresses = get_user_meta($user_id, 'webgsm_addresses', true);
        if (!is_array($addresses)) $addresses = [];
        $new = [
            'label' => sanitize_text_field($_POST['label'] ?? ''),
            'name' => sanitize_text_field($_POST['name']),
            'phone' => sanitize_text_field($_POST['phone']),
            'address' => sanitize_text_field($_POST['address']),
            'city' => sanitize_text_field($_POST['city']),
            'county' => sanitize_text_field($_POST['county']),
            'postcode' => sanitize_text_field($_POST['postcode'] ?? '')
        ];
        $addresses[] = $new;
        update_user_meta($user_id, 'webgsm_addresses', $addresses);
        wp_send_json_success(['index' => count($addresses)-1, 'address' => $new]);
    }
    
    public function ajax_delete_address() {
        check_ajax_referer('webgsm_nonce', 'nonce');
        if (!is_user_logged_in()) wp_send_json_error('Not logged in');
        $user_id = get_current_user_id();
        $idx = intval($_POST['index']);
        $addresses = get_user_meta($user_id, 'webgsm_addresses', true);
        if (is_array($addresses) && isset($addresses[$idx])) {
            array_splice($addresses, $idx, 1);
            update_user_meta($user_id, 'webgsm_addresses', $addresses);
            wp_send_json_success();
        }
        wp_send_json_error('Not found');
    }
    
    public function ajax_save_company() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'webgsm_nonce')) wp_send_json_error('Sesiune expirată');
        $new = [
            'name' => sanitize_text_field($_POST['name'] ?? ''),
            'cui' => sanitize_text_field($_POST['cui'] ?? ''),
            'reg' => sanitize_text_field($_POST['reg'] ?? ''),
            'phone' => sanitize_text_field($_POST['phone'] ?? ''),
            'email' => sanitize_email($_POST['email'] ?? ''),
            'address' => sanitize_text_field($_POST['address'] ?? ''),
            'county' => sanitize_text_field($_POST['county'] ?? ''),
            'city' => sanitize_text_field($_POST['city'] ?? ''),
            'iban' => sanitize_text_field($_POST['iban'] ?? ''),
            'bank' => sanitize_text_field($_POST['bank'] ?? '')
        ];
        // Server-side validation: contact fields required
        // contact_first/contact_last validation removed

        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            $companies = get_user_meta($user_id, 'webgsm_companies', true);
            if (!is_array($companies)) $companies = [];
            $companies[] = $new;
            update_user_meta($user_id, 'webgsm_companies', $companies);
            wp_send_json_success(['index' => count($companies)-1, 'company' => $new, 'saved_to_account' => true]);
        } else {
            $_SESSION['webgsm_guest_company'] = $new;
            wp_send_json_success(['company' => $new, 'saved_to_account' => false]);
        }
    }
    
    public function ajax_delete_company() {
        check_ajax_referer('webgsm_nonce', 'nonce');
        if (!is_user_logged_in()) wp_send_json_error('Not logged in');
        $user_id = get_current_user_id();
        $idx = intval($_POST['index']);
        $companies = get_user_meta($user_id, 'webgsm_companies', true);
        if (is_array($companies) && isset($companies[$idx])) {
            array_splice($companies, $idx, 1);
            update_user_meta($user_id, 'webgsm_companies', $companies);
            wp_send_json_success();
        }
        wp_send_json_error('Not found');
    }
    
    public function ajax_save_person() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'webgsm_nonce')) wp_send_json_error('Sesiune expirată');
        $new = [
            'name' => sanitize_text_field($_POST['name'] ?? ''),
            'cnp' => sanitize_text_field($_POST['cnp'] ?? ''),
            'phone' => sanitize_text_field($_POST['phone'] ?? ''),
            'email' => sanitize_email($_POST['email'] ?? ''),
            'address' => sanitize_text_field($_POST['address'] ?? ''),
            'county' => sanitize_text_field($_POST['county'] ?? ''),
            'city' => sanitize_text_field($_POST['city'] ?? ''),
            'postcode' => sanitize_text_field($_POST['postcode'] ?? '')
        ];
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            $persons = get_user_meta($user_id, 'webgsm_persons', true);
            if (!is_array($persons)) $persons = [];
            $persons[] = $new;
            update_user_meta($user_id, 'webgsm_persons', $persons);
            wp_send_json_success(['index' => count($persons)-1, 'person' => $new, 'saved_to_account' => true]);
        } else {
            $_SESSION['webgsm_guest_person'] = $new;
            wp_send_json_success(['person' => $new, 'saved_to_account' => false]);
        }
    }
    
    public function ajax_delete_person() {
        check_ajax_referer('webgsm_nonce', 'nonce');
        if (!is_user_logged_in()) wp_send_json_error('Not logged in');
        $user_id = get_current_user_id();
        $idx = intval($_POST['index']);
        $persons = get_user_meta($user_id, 'webgsm_persons', true);
        if (is_array($persons) && isset($persons[$idx])) {
            array_splice($persons, $idx, 1);
            update_user_meta($user_id, 'webgsm_persons', $persons);
            wp_send_json_success();
        }
        wp_send_json_error('Not found');
    }
    
    public function ajax_get_address_for_edit() {
        check_ajax_referer('webgsm_nonce', 'nonce');
        if (!is_user_logged_in()) wp_send_json_error('Not logged in');
        
        $user_id = get_current_user_id();
        $idx = intval($_POST['index']);
        $addresses = get_user_meta($user_id, 'webgsm_addresses', true);
        
        if (is_array($addresses) && isset($addresses[$idx])) {
            wp_send_json_success($addresses[$idx]);
        }
        wp_send_json_error('Not found');
    }
    
    public function ajax_get_company_for_edit() {
        check_ajax_referer('webgsm_nonce', 'nonce');
        if (!is_user_logged_in()) wp_send_json_error('Not logged in');
        
        $user_id = get_current_user_id();
        $idx = intval($_POST['index']);
        $companies = get_user_meta($user_id, 'webgsm_companies', true);
        
        if (is_array($companies) && isset($companies[$idx])) {
            wp_send_json_success($companies[$idx]);
        }
        wp_send_json_error('Not found');
    }
    
    public function ajax_get_person_for_edit() {
        check_ajax_referer('webgsm_nonce', 'nonce');
        if (!is_user_logged_in()) wp_send_json_error('Not logged in');
        
        $user_id = get_current_user_id();
        $idx = intval($_POST['index']);
        $persons = get_user_meta($user_id, 'webgsm_persons', true);
        
        if (is_array($persons) && isset($persons[$idx])) {
            wp_send_json_success($persons[$idx]);
        }
        wp_send_json_error('Not found');
    }
    
    public function ajax_update_cart_item() {
        WC()->cart->set_quantity(sanitize_text_field($_POST['key']), intval($_POST['qty']));
        wp_send_json_success(['subtotal' => WC()->cart->get_cart_subtotal(), 'total' => WC()->cart->get_total()]);
    }
    
    public function ajax_remove_cart_item() {
        WC()->cart->remove_cart_item(sanitize_text_field($_POST['key']));
        wp_send_json_success(['cart_count' => WC()->cart->get_cart_contents_count()]);
    }
    
    public function ajax_apply_coupon() {
        if (!WC()->cart || WC()->cart->is_empty()) {
            wp_send_json_error(['message' => 'Cosul este gol.']);
            return;
        }
        
        $coupon_code = isset($_POST['coupon_code']) ? sanitize_text_field($_POST['coupon_code']) : '';
        
        if (empty($coupon_code)) {
            wp_send_json_error(['message' => 'Te rugam sa introduci un cod cupon.']);
            return;
        }
        
        // Verifica daca cuponul este deja aplicat
        if (WC()->cart->has_discount($coupon_code)) {
            wp_send_json_error(['message' => 'Acest cupon este deja aplicat.']);
            return;
        }
        
        // Aplica cuponul
        $result = WC()->cart->apply_coupon($coupon_code);
        
        if (is_wp_error($result)) {
            $error_message = $result->get_error_message();
            wp_send_json_error(['message' => $error_message]);
            return;
        }
        
        // Verifica daca cuponul a fost aplicat cu succes
        if (WC()->cart->has_discount($coupon_code)) {
            $coupon = new WC_Coupon($coupon_code);
            $discount_amount = WC()->cart->get_coupon_discount_amount($coupon_code);
            $discount_type = $coupon->get_discount_type();
            
            $message = 'Cupon aplicat cu succes!';
            if ($discount_type === 'percent') {
                $message .= ' Reducere ' . $coupon->get_amount() . '% aplicata.';
            } else {
                $message .= ' Reducere ' . wc_price($discount_amount) . ' aplicata.';
            }
            
            wp_send_json_success(['message' => $message, 'discount' => $discount_amount]);
        } else {
            wp_send_json_error(['message' => 'Cod cupon invalid sau expirat.']);
        }
    }
    
    public function addresses_page_content() {
        if (!is_user_logged_in()) {
            echo '<p>Trebuie să fii autentificat pentru a vedea această pagină.</p>';
            return;
        }
        
        // CSS inline pentru butoane My Account
        echo '<style>
        #save_address_modal_btn,
        #save_company_modal_btn,
        #save_person_modal_btn {
            padding: 2px 14px !important;
            min-height: 20px !important;
            height: auto !important;
            line-height: 1 !important;
            font-size: 11px !important;
            border-radius: 16px !important;
            font-weight: 500 !important;
            background: #3b82f6 !important;
            color: #fff !important;
            border: none !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        </style>';
        
        $user_id = get_current_user_id();
        $addresses = get_user_meta($user_id, 'webgsm_addresses', true) ?: [];
        $companies = get_user_meta($user_id, 'webgsm_companies', true) ?: [];
        $persons = get_user_meta($user_id, 'webgsm_persons', true) ?: [];
        
        // SVG Icons
        $icon_location = '<svg class="section-icon" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>';
        
        $icon_building = '<svg class="section-icon" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z"/></svg>';
        
        $icon_user = '<svg class="section-icon" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>';
        
        $icon_plus = '<svg class="plus-icon" viewBox="0 0 24 24" stroke="#ffffff" stroke-width="2.5" fill="none"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>';
        
        $icon_phone = '<svg class="icon-small" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg>';
        
        $icon_email = '<svg class="icon-small" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>';
        
        $icon_edit = '<svg class="icon-action" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>';
        
        $icon_delete = '<svg class="icon-action" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>';
        
        $icon_plus_small = '<svg class="icon-plus-small" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>';
        
        ?>
        <div class="webgsm-saved-data-page">
            
            <!-- SECȚIUNEA ADRESE LIVRARE -->
            <div class="webgsm-data-section">
                <div class="section-header">
                    <h3><?php echo $icon_location; ?> Adrese livrare</h3>
                    <button type="button" class="btn-add-new" id="btn-add-address" title="Adaugă adresă">
                        <?php echo $icon_plus; ?>
                    </button>
                </div>
                <div class="section-content">
                    <?php if (!empty($addresses)): ?>
                        <div class="cards-grid">
                            <?php foreach ($addresses as $i => $a): 
                                $label = !empty($a['label']) ? $a['label'] : 'Adresa '.($i+1);
                            ?>
                            <div class="data-card" data-type="address" data-index="<?php echo $i; ?>">
                                <div class="card-header">
                                    <span class="card-label"><?php echo esc_html($label); ?></span>
                                </div>
                                <div class="card-body">
                                    <p class="card-name"><?php echo esc_html($a['name'] ?? ''); ?></p>
                                    <p class="card-detail"><?php echo esc_html($a['address'] ?? ''); ?></p>
                                    <p class="card-detail"><?php echo esc_html(($a['city'] ?? '') . ', ' . ($a['county'] ?? '')); ?></p>
                                    <p class="card-phone"><?php echo $icon_phone; ?> <?php echo esc_html($a['phone'] ?? ''); ?></p>
                                </div>
                                <div class="card-actions">
                                    <button type="button" class="btn-edit-item" data-type="address" data-index="<?php echo $i; ?>"><?php echo $icon_edit; ?> Editează</button>
                                    <button type="button" class="btn-delete-item" data-type="address" data-index="<?php echo $i; ?>"><?php echo $icon_delete; ?> Șterge</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>Nu ai adrese salvate.</p>
                            <button type="button" class="btn-add-first" id="btn-add-address-empty"><?php echo $icon_plus_small; ?> Adaugă prima adresă</button>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- MODAL ADDRESS - Inside section for relative positioning -->
                <div class="webgsm-popup" id="address_modal_saved" style="display:none;">
                    <div class="popup-overlay"></div>
                    <div class="popup-content">
                        <div class="popup-header"><h3 id="modal_title">Adaugă adresă</h3><button type="button" class="popup-close modal-close-btn">×</button></div>
                        <div class="popup-body">
                            <input type="hidden" id="edit_address_index" value="">
                            <div class="form-row"><div class="form-col"><label>Etichetă</label><input type="text" id="modal_label" placeholder="Acasă, Birou..."></div></div>
                            <div class="form-row">
                                <div class="form-col"><label>Nume *</label><input type="text" id="modal_name"></div>
                                <div class="form-col"><label>Telefon *</label><input type="tel" id="modal_phone"></div>
                            </div>
                            <div class="form-row"><div class="form-col full"><label>Adresă *</label><input type="text" id="modal_address"></div></div>
                            <div class="form-row">
                                <div class="form-col"><label>Localitate *</label><input type="text" id="modal_city"></div>
                                <div class="form-col"><label>Județ *</label>
                                <select id="modal_county">
                                    <?php 
                                    $counties_full = [
                                        'AB' => 'Alba', 'AG' => 'Argeș', 'AR' => 'Arad', 'B' => 'București',
                                        'BC' => 'Bacău', 'BH' => 'Bihor', 'BN' => 'Bistrița-Năsăud', 'BR' => 'Brăila',
                                        'BT' => 'Botoșani', 'BV' => 'Brașov', 'C' => 'Constanța', 'CJ' => 'Cluj',
                                        'CV' => 'Covasna', 'DB' => 'Dâmbovița', 'DJ' => 'Dolj', 'GJ' => 'Gorj',
                                        'GL' => 'Galați', 'GR' => 'Giurgiu', 'HD' => 'Hunedoara', 'HR' => 'Harghita',
                                        'IF' => 'Ilfov', 'IL' => 'Ialomița', 'IS' => 'Iași', 'JN' => 'Neamț',
                                        'JS' => 'Sibiu', 'MH' => 'Mehedinți', 'MS' => 'Mureș', 'MT' => 'Maramureș',
                                        'OT' => 'Olt', 'PH' => 'Prahova', 'SB' => 'Sibiu', 'SJ' => 'Sălaj',
                                        'SM' => 'Satu Mare', 'SV' => 'Suceava', 'TL' => 'Tulcea', 'TM' => 'Timiș',
                                        'TR' => 'Teleorman', 'VL' => 'Vâlcea', 'VS' => 'Vaslui', 'VN' => 'Vrancea'
                                    ];
                                    foreach ($counties_full as $code => $name) {
                                        echo '<option value="' . esc_attr($code) . '">' . esc_html($name) . '</option>';
                                    }
                                    ?>
                                </select>
                                </div>
                                <div class="form-col"><label>Cod poștal</label><input type="text" id="modal_postcode"></div>
                            </div>
                        </div>
                        <div class="popup-footer">
                            <button type="button" class="btn-secondary modal-cancel-btn">Anuleaza</button>
                            <button type="button" class="btn-primary" id="save_address_modal_btn">Salveaza</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- SECȚIUNEA FIRME -->
            <div class="webgsm-data-section">
                <div class="section-header">
                    <h3><?php echo $icon_building; ?> Firme (PJ)</h3>
                    <button type="button" class="btn-add-new" id="btn-add-company" title="Adaugă firmă">
                        <?php echo $icon_plus; ?>
                    </button>
                </div>
                <div class="section-content">
                    <?php if (!empty($companies)): ?>
                        <div class="cards-grid">
                            <?php foreach ($companies as $i => $c): ?>
                            <div class="data-card" data-type="company" data-index="<?php echo $i; ?>">
                                <div class="card-header">
                                    <span class="card-label"><?php echo esc_html($c['name'] ?? 'Firma '.($i+1)); ?></span>
                                    <span class="card-badge">CUI: <?php echo esc_html($c['cui'] ?? ''); ?></span>
                                </div>
                                <div class="card-body">
                                    <p class="card-detail">Reg. Com: <?php echo esc_html($c['reg'] ?? '-'); ?></p>
                                    <p class="card-detail"><?php echo esc_html($c['address'] ?? ''); ?></p>
                                    <p class="card-detail"><?php echo esc_html(($c['city'] ?? '') . ', ' . ($c['county'] ?? '')); ?></p>
                                    <p class="card-phone"><?php echo $icon_phone; ?> <?php echo esc_html($c['phone'] ?? ''); ?></p>
                                    <p class="card-email"><?php echo $icon_email; ?> <?php echo esc_html($c['email'] ?? ''); ?></p>
                                </div>
                                <div class="card-actions">
                                    <button type="button" class="btn-edit-item" data-type="company" data-index="<?php echo $i; ?>"><?php echo $icon_edit; ?> Editează</button>
                                    <button type="button" class="btn-delete-item" data-type="company" data-index="<?php echo $i; ?>"><?php echo $icon_delete; ?> Șterge</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>Nu ai firme salvate.</p>
                            <button type="button" class="btn-add-first" id="btn-add-company-empty"><?php echo $icon_plus_small; ?> Adaugă prima firmă</button>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- MODAL COMPANY - Inside section for relative positioning -->
                <div class="webgsm-popup" id="company_modal_saved" style="display:none;">
                    <div class="popup-overlay"></div>
                    <div class="popup-content" style="max-width:550px;">
                        <div class="popup-header"><h3 id="company_modal_title">Adaugă firmă</h3><button type="button" class="popup-close modal-close-btn">×</button></div>
                        <div class="popup-body">
                            <input type="hidden" id="edit_company_index" value="">
                            <div class="form-row">
                                <div class="form-col"><label>CUI *</label><input type="text" id="company_cui_modal" placeholder="12345678"></div>
                                <div class="form-col" style="display:flex;align-items:flex-end;">
                                    <small class="anaf-hint" style="color:#3b82f6;font-size:12px;font-weight:500;">🔍 Autocompletare din ANAF</small>
                                </div>
                            </div>
                            <div id="anaf_status_modal" style="display:none;padding:8px 12px;border-radius:6px;margin:10px 0;font-size:13px;"></div>
                            <div class="form-row"><div class="form-col full"><label>Denumire *</label><input type="text" id="company_name_modal"></div></div>
                            <div class="form-row">
                                <div class="form-col"><label>Nr. Reg. Com. *</label><input type="text" id="company_reg_modal" placeholder="J40/1234/2020"></div>
                                <div class="form-col"><label>Telefon *</label><input type="tel" id="company_phone_modal" placeholder="07xxxxxxxx"></div>
                            </div>
                            <div class="form-row"><div class="form-col full"><label>Email *</label><input type="email" id="company_email_modal" placeholder="contact@firma.ro"></div></div>
                            <div style="border-top:1px solid #eee;margin:15px 0;padding-top:15px;"><strong>Adresa sediu:</strong></div>
                            <div class="form-row"><div class="form-col full"><label>Adresă *</label><input type="text" id="company_address_modal"></div></div>
                            <div class="form-row">
                                <div class="form-col"><label>Județ *</label>
                                <select id="company_county_modal">
                                    <?php 
                                    $counties_full = [
                                        'AB' => 'Alba', 'AG' => 'Argeș', 'AR' => 'Arad', 'B' => 'București',
                                        'BC' => 'Bacău', 'BH' => 'Bihor', 'BN' => 'Bistrița-Năsăud', 'BR' => 'Brăila',
                                        'BT' => 'Botoșani', 'BV' => 'Brașov', 'C' => 'Constanța', 'CJ' => 'Cluj',
                                        'CV' => 'Covasna', 'DB' => 'Dâmbovița', 'DJ' => 'Dolj', 'GJ' => 'Gorj',
                                        'GL' => 'Galați', 'GR' => 'Giurgiu', 'HD' => 'Hunedoara', 'HR' => 'Harghita',
                                        'IF' => 'Ilfov', 'IL' => 'Ialomița', 'IS' => 'Iași', 'JN' => 'Neamț',
                                        'JS' => 'Sibiu', 'MH' => 'Mehedinți', 'MS' => 'Mureș', 'MT' => 'Maramureș',
                                        'OT' => 'Olt', 'PH' => 'Prahova', 'SB' => 'Sibiu', 'SJ' => 'Sălaj',
                                        'SM' => 'Satu Mare', 'SV' => 'Suceava', 'TL' => 'Tulcea', 'TM' => 'Timiș',
                                        'TR' => 'Teleorman', 'VL' => 'Vâlcea', 'VS' => 'Vaslui', 'VN' => 'Vrancea'
                                    ];
                                    foreach ($counties_full as $code => $name) {
                                        echo '<option value="' . esc_attr($code) . '">' . esc_html($name) . '</option>';
                                    }
                                    ?>
                                </select>
                                </div>
                                <div class="form-col"><label>Localitate *</label><input type="text" id="company_city_modal"></div>
                            </div>
                        </div>
                        <div class="popup-footer">
                            <button type="button" class="btn-secondary modal-cancel-btn">Anuleaza</button>
                            <button type="button" class="btn-primary" id="save_company_modal_btn">Salveaza</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- SECȚIUNEA PERSOANE FIZICE -->
            <div class="webgsm-data-section">
                <div class="section-header">
                    <h3><?php echo $icon_user; ?> Persoane fizice (PF)</h3>
                    <button type="button" class="btn-add-new" id="btn-add-person" title="Adaugă persoană">
                        <?php echo $icon_plus; ?>
                    </button>
                </div>
                <div class="section-content">
                    <?php if (!empty($persons)): ?>
                        <div class="cards-grid">
                            <?php foreach ($persons as $i => $p): ?>
                            <div class="data-card" data-type="person" data-index="<?php echo $i; ?>">
                                <div class="card-header">
                                    <span class="card-label"><?php echo esc_html($p['name'] ?? 'Persoana '.($i+1)); ?></span>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($p['cnp'])): ?>
                                    <p class="card-detail">CNP: <?php echo esc_html($p['cnp']); ?></p>
                                    <?php endif; ?>
                                    <p class="card-detail"><?php echo esc_html($p['address'] ?? ''); ?></p>
                                    <p class="card-detail"><?php echo esc_html(($p['city'] ?? '') . ', ' . ($p['county'] ?? '')); ?></p>
                                    <p class="card-phone"><?php echo $icon_phone; ?> <?php echo esc_html($p['phone'] ?? ''); ?></p>
                                    <p class="card-email"><?php echo $icon_email; ?> <?php echo esc_html($p['email'] ?? ''); ?></p>
                                </div>
                                <div class="card-actions">
                                    <button type="button" class="btn-edit-item" data-type="person" data-index="<?php echo $i; ?>"><?php echo $icon_edit; ?> Editează</button>
                                    <button type="button" class="btn-delete-item" data-type="person" data-index="<?php echo $i; ?>"><?php echo $icon_delete; ?> Șterge</button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>Nu ai persoane salvate.</p>
                            <button type="button" class="btn-add-first" id="btn-add-person-empty"><?php echo $icon_plus_small; ?> Adaugă prima persoană</button>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- MODAL PERSON - Inside section for relative positioning -->
                <div class="webgsm-popup" id="person_modal_saved" style="display:none;">
                    <div class="popup-overlay"></div>
                    <div class="popup-content" style="max-width:550px;">
                        <div class="popup-header"><h3 id="person_modal_title">Adaugă persoană</h3><button type="button" class="popup-close modal-close-btn">×</button></div>
                        <div class="popup-body">
                            <input type="hidden" id="edit_person_index" value="">
                            <div class="form-row">
                                <div class="form-col"><label>Nume complet *</label><input type="text" id="person_name_modal"></div>
                                <div class="form-col"><label>CNP (opțional)</label><input type="text" id="person_cnp_modal" maxlength="13"></div>
                            </div>
                            <div class="form-row">
                                <div class="form-col"><label>Telefon *</label><input type="tel" id="person_phone_modal" placeholder="07xxxxxxxx"></div>
                                <div class="form-col"><label>Email *</label><input type="email" id="person_email_modal"></div>
                            </div>
                            <div style="border-top:1px solid #eee;margin:15px 0;padding-top:15px;"><strong>Adresă facturare:</strong></div>
                            <div class="form-row"><div class="form-col full"><label>Adresă *</label><input type="text" id="person_address_modal"></div></div>
                            <div class="form-row">
                                <div class="form-col"><label>Județ *</label>
                                <select id="person_county_modal">
                                    <?php 
                                    $counties_full = [
                                        'AB' => 'Alba', 'AG' => 'Argeș', 'AR' => 'Arad', 'B' => 'București',
                                        'BC' => 'Bacău', 'BH' => 'Bihor', 'BN' => 'Bistrița-Năsăud', 'BR' => 'Brăila',
                                        'BT' => 'Botoșani', 'BV' => 'Brașov', 'C' => 'Constanța', 'CJ' => 'Cluj',
                                        'CV' => 'Covasna', 'DB' => 'Dâmbovița', 'DJ' => 'Dolj', 'GJ' => 'Gorj',
                                        'GL' => 'Galați', 'GR' => 'Giurgiu', 'HD' => 'Hunedoara', 'HR' => 'Harghita',
                                        'IF' => 'Ilfov', 'IL' => 'Ialomița', 'IS' => 'Iași', 'JN' => 'Neamț',
                                        'JS' => 'Sibiu', 'MH' => 'Mehedinți', 'MS' => 'Mureș', 'MT' => 'Maramureș',
                                        'OT' => 'Olt', 'PH' => 'Prahova', 'SB' => 'Sibiu', 'SJ' => 'Sălaj',
                                        'SM' => 'Satu Mare', 'SV' => 'Suceava', 'TL' => 'Tulcea', 'TM' => 'Timiș',
                                        'TR' => 'Teleorman', 'VL' => 'Vâlcea', 'VS' => 'Vaslui', 'VN' => 'Vrancea'
                                    ];
                                    foreach ($counties_full as $code => $name) {
                                        echo '<option value="' . esc_attr($code) . '">' . esc_html($name) . '</option>';
                                    }
                                    ?>
                                </select>
                                </div>
                                <div class="form-col"><label>Localitate *</label><input type="text" id="person_city_modal"></div>
                            </div>
                            <div class="form-row"><div class="form-col"><label>Cod poștal</label><input type="text" id="person_postcode_modal" maxlength="6"></div></div>
                        </div>
                        <div class="popup-footer">
                            <button type="button" class="btn-secondary modal-cancel-btn">Anuleaza</button>
                            <button type="button" class="btn-primary" id="save_person_modal_btn">Salveaza</button>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
        <?php
    }
    
    /**
     * Apply shipping fields from our custom checkout into the WC_Order object.
     * Ensures server-side the shipping address selected/filled in the form is used.
     */
    public function apply_custom_shipping_fields( $order, $data ) {
        // $_POST is expected to contain our hidden fields
        $ship_flag = isset($_POST['ship_to_different_address']) ? sanitize_text_field($_POST['ship_to_different_address']) : '0';

        if ($ship_flag === '1') {
            $shipping_first = isset($_POST['shipping_first_name']) ? sanitize_text_field($_POST['shipping_first_name']) : '';
            $shipping_last = isset($_POST['shipping_last_name']) ? sanitize_text_field($_POST['shipping_last_name']) : '';
            $shipping_address_1 = isset($_POST['shipping_address_1']) ? sanitize_text_field($_POST['shipping_address_1']) : '';
            $shipping_city = isset($_POST['shipping_city']) ? sanitize_text_field($_POST['shipping_city']) : '';
            $shipping_state = isset($_POST['shipping_state']) ? sanitize_text_field($_POST['shipping_state']) : '';
            $shipping_postcode = isset($_POST['shipping_postcode']) ? sanitize_text_field($_POST['shipping_postcode']) : '';
            $shipping_country = isset($_POST['shipping_country']) ? sanitize_text_field($_POST['shipping_country']) : 'RO';
            $shipping_phone = isset($_POST['shipping_phone']) ? sanitize_text_field($_POST['shipping_phone']) : '';

            // Debugging: log posted shipping values so we can verify what's received server-side
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[WebGSM] apply_custom_shipping_fields: order_id=' . (is_object($order) && method_exists($order, 'get_id') ? $order->get_id() : '') . ' ship_flag=' . $ship_flag . ' shipping_first=' . $shipping_first . ' shipping_last=' . $shipping_last . ' shipping_address=' . $shipping_address_1 . ' shipping_city=' . $shipping_city . ' shipping_state=' . $shipping_state . ' shipping_postcode=' . $shipping_postcode);
            }

            // WC_Order methods
            if (is_callable([$order, 'set_shipping_first_name'])) $order->set_shipping_first_name($shipping_first);
            if (is_callable([$order, 'set_shipping_last_name'])) $order->set_shipping_last_name($shipping_last);
            if (is_callable([$order, 'set_shipping_address_1'])) $order->set_shipping_address_1($shipping_address_1);
            if (is_callable([$order, 'set_shipping_city'])) $order->set_shipping_city($shipping_city);
            if (is_callable([$order, 'set_shipping_state'])) $order->set_shipping_state($shipping_state);
            if (is_callable([$order, 'set_shipping_postcode'])) $order->set_shipping_postcode($shipping_postcode);
            if (is_callable([$order, 'set_shipping_country'])) $order->set_shipping_country($shipping_country);

            // Store shipping data as order meta pentru a fi sigur ca sunt salvate corect
            if (!empty($shipping_first)) $order->update_meta_data('_shipping_first_name', $shipping_first);
            if (!empty($shipping_last)) $order->update_meta_data('_shipping_last_name', $shipping_last);
            if (!empty($shipping_address_1)) $order->update_meta_data('_shipping_address_1', $shipping_address_1);
            if (!empty($shipping_city)) $order->update_meta_data('_shipping_city', $shipping_city);
            if (!empty($shipping_state)) $order->update_meta_data('_shipping_state', $shipping_state);
            if (!empty($shipping_postcode)) $order->update_meta_data('_shipping_postcode', $shipping_postcode);
            if (!empty($shipping_phone)) $order->update_meta_data('_shipping_phone', $shipping_phone);

            $order->update_meta_data('_same_as_billing', '0');
        } else {
            $order->update_meta_data('_same_as_billing', '1');
        }

        $order->save();
    }
    
    public function cart_page_css() {
        if (!is_cart()) return;
        ?>
        <style>
        .woocommerce-cart .woocommerce{max-width:900px;margin:0 auto;padding:20px}
        .woocommerce-cart .coupon,.woocommerce-cart .cart_totals,.woocommerce-cart .woocommerce-shipping-calculator,.woocommerce-cart .shipping,.woocommerce-cart .cart-subtotal,.woocommerce-cart .order-total,.woocommerce-cart .cross-sells,.woocommerce-cart .cart-collaterals,.woocommerce-cart .return-to-shop,.woocommerce-cart .wc-proceed-to-checkout,.woocommerce-cart .btn-shop,.woocommerce-cart button[name="update_cart"],.woocommerce-cart td.actions{display:none!important}
        .webgsm-cart-buttons{display:flex;justify-content:space-between;align-items:center;padding:20px 0;margin-top:20px;border-top:1px solid #e0e0e0}
        .webgsm-cart-buttons .btn-continue{display:inline-flex;align-items:center;gap:8px;background:#333;color:#fff;padding:10px 18px;border-radius:25px;text-decoration:none;font-size:13px}
        .webgsm-cart-buttons .btn-checkout{background:#4caf50;color:#fff;padding:12px 30px;border-radius:25px;font-size:14px;font-weight:600;text-decoration:none}
        </style>
        <script>
        jQuery(function($){
            var shop='<?php echo esc_url(wc_get_page_permalink('shop')); ?>',chk='<?php echo esc_url(wc_get_checkout_url()); ?>';
            function add(){$('.webgsm-cart-buttons').remove();$('.woocommerce-cart .shop_table.cart').after('<div class="webgsm-cart-buttons"><a href="'+shop+'" class="btn-continue">← Continuă</a><a href="'+chk+'" class="btn-checkout">Finalizare</a></div>');}
            add();$(document.body).on('updated_wc_div',add);
        });
        </script>
        <?php
    }
    
    public function custom_thankyou_content($order_id) {
        if (!$order_id) return;
        $order = wc_get_order($order_id);
        if (!$order) return;
        $type = $order->get_meta('_customer_type');
        $same = $order->get_meta('_same_as_billing');
        ?>
        <style>
        .webgsm-thankyou{background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:25px;margin:20px 0}
        .webgsm-thankyou h3{margin:0 0 20px;padding-bottom:10px;border-bottom:1px solid #eee;font-size:16px}
        .webgsm-thankyou-grid{display:grid;grid-template-columns:1fr 1fr;gap:25px}
        @media(max-width:768px){.webgsm-thankyou-grid{grid-template-columns:1fr}}
        .webgsm-thankyou-box{background:#fafafa;padding:15px;border-radius:6px}
        .webgsm-thankyou-box h4{margin:0 0 10px;font-size:13px;color:#666;text-transform:uppercase}
        .webgsm-thankyou-box p{margin:0;font-size:14px;line-height:1.6}
        .company-info{background:#e3f2fd;padding:10px;border-radius:4px;margin-bottom:10px}
        .company-info strong{color:#1565c0}
        .company-details{font-size:12px;color:#666;margin-top:5px}
        .webgsm-same-address{background:#e8f5e9;color:#2e7d32;padding:10px 15px;border-radius:4px;font-size:13px}
        .webgsm-back-btn{display:inline-flex;align-items:center;gap:8px;background:#333;color:#fff;padding:12px 25px;border-radius:25px;text-decoration:none;margin-top:20px}
        </style>
        <div class="webgsm-thankyou">
            <h3>📋 Detalii comandă #<?php echo $order->get_order_number(); ?></h3>
            <div class="webgsm-thankyou-grid">
                <div class="webgsm-thankyou-box">
                    <h4>🧾 Facturare</h4>
                    <?php if ($type==='pj' && $order->get_billing_company()): ?>
                    <div class="company-info">
                        <strong><?php echo esc_html($order->get_billing_company()); ?></strong>
                        <div class="company-details">CUI: <?php echo esc_html($order->get_meta('_billing_cui')); ?> | J: <?php echo esc_html($order->get_meta('_billing_j')); ?></div>
                    </div>
                    <?php else: ?>
                    <p><strong><?php echo esc_html($order->get_billing_first_name().' '.$order->get_billing_last_name()); ?></strong></p>
                    <?php endif; ?>
                    <p><?php echo esc_html($order->get_billing_address_1()); ?><br><?php echo esc_html($order->get_billing_city().', '.$order->get_billing_state()); ?><br>Tel: <?php echo esc_html($order->get_billing_phone()); ?></p>
                </div>
                <div class="webgsm-thankyou-box">
                    <h4>📦 Livrare</h4>
                    <?php if ($same==='1'): ?>
                    <div class="webgsm-same-address">✓ La aceeași adresă</div>
                    <?php else: 
                        // Foloseste datele din order meta pentru a fi sigur ca sunt corecte
                        $shipping_first = $order->get_meta('_shipping_first_name') ?: $order->get_shipping_first_name();
                        $shipping_last = $order->get_meta('_shipping_last_name') ?: $order->get_shipping_last_name();
                        $shipping_address = $order->get_meta('_shipping_address_1') ?: $order->get_shipping_address_1();
                        $shipping_city = $order->get_meta('_shipping_city') ?: $order->get_shipping_city();
                        $shipping_state = $order->get_meta('_shipping_state') ?: $order->get_shipping_state();
                        $shipping_phone = $order->get_meta('_shipping_phone') ?: '';
                    ?>
                    <p>
                        <?php if ($shipping_first || $shipping_last): ?>
                            <strong><?php echo esc_html(trim($shipping_first . ' ' . $shipping_last)); ?></strong><br>
                        <?php endif; ?>
                        <?php if ($shipping_phone): ?>
                            Tel: <?php echo esc_html($shipping_phone); ?><br>
                        <?php endif; ?>
                        <?php echo esc_html($shipping_address); ?><br>
                        <?php echo esc_html($shipping_city . ', ' . $shipping_state); ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
            <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="webgsm-back-btn">← Înapoi la magazin</a>
        </div>
        <?php
    }
}

WebGSM_Checkout_Pro::instance();

// Declarație compatibilitate HPOS (High-Performance Order Storage)
add_action('before_woocommerce_init', function() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

// Ascunde secțiunile default WooCommerce de pe thank you page (doar adresele duplicate)
add_action('woocommerce_thankyou', function() {
    ?>
    <style>
    /* Ascunde DOAR adresele duplicate - păstrăm detaliile comenzii */
    .woocommerce-customer-details,
    .woocommerce-columns--addresses,
    .woocommerce-column--billing-address,
    .woocommerce-column--shipping-address,
    section.woocommerce-customer-details,
    .woocommerce-bacs-bank-details,
    /* Ascunde secțiunea "Date firmă" veche */
    section.woocommerce-company-details,
    .woocommerce-company-details {
        display: none !important;
    }
    
    /* STILIZARE SECȚIUNE DETALII COMANDĂ (sus) */
    .woocommerce-order-overview {
        list-style: none !important;
        padding: 20px !important;
        margin: 20px 0 !important;
        background: #fff !important;
        border: 1px solid #e0e0e0 !important;
        border-radius: 8px !important;
        display: grid !important;
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 15px !important;
    }
    
    .woocommerce-order-overview li {
        padding: 12px 15px !important;
        background: #f9f9f9 !important;
        border-radius: 6px !important;
        margin: 0 !important;
        display: flex !important;
        flex-direction: column !important;
        gap: 5px !important;
    }
    
    .woocommerce-order-overview li strong {
        font-size: 15px !important;
        color: #333 !important;
    }
    
    /* Metodă de plată - full width */
    .woocommerce-order-overview li.woocommerce-order-overview__payment-method {
        grid-column: 1 / -1 !important;
    }
    
    /* Mobile - o coloană */
    @media (max-width: 600px) {
        .woocommerce-order-overview {
            grid-template-columns: 1fr !important;
            gap: 10px !important;
            padding: 15px !important;
        }
        
        .woocommerce-order-overview li {
            padding: 10px 12px !important;
        }
        
        .woocommerce-order-overview li strong {
            font-size: 14px !important;
        }
    }
    
    /* Mesaj confirmare */
    .woocommerce-thankyou-order-received {
        background: #e8f5e9 !important;
        color: #2e7d32 !important;
        padding: 15px 20px !important;
        border-radius: 8px !important;
        font-size: 16px !important;
        font-weight: 500 !important;
        margin-bottom: 20px !important;
        text-align: center !important;
    }
    </style>
    <?php
}, 1);

register_activation_hook(__FILE__, function(){ flush_rewrite_rules(); });
register_deactivation_hook(__FILE__, function(){ flush_rewrite_rules(); });
