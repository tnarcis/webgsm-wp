<?php
/**
 * WebGSM Checkout Validate
 * Versiunea 4.2.0 - VALIDARE PERMISIVĂ
 * 
 * LOGICĂ:
 * - NU blochează submit-ul pentru câmpuri hidden
 * - Pentru PF: verifică doar date esențiale (nume, telefon, email)
 * - Pentru PJ: verifică doar CUI și nume firmă
 * - Fallback din carduri dacă $_POST e gol
 * - Debug logging pentru troubleshooting
 * 
 * @package WebGSM_Checkout_Pro
 */

if (!defined('ABSPATH')) exit;

class WebGSM_Checkout_Validate {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Validare cu prioritate 20 (după populate din carduri care e pe 5)
        add_action('woocommerce_checkout_process', [$this, 'webgsm_validate_checkout'], 20);
        
        // Debug: log toate datele POST
        add_action('woocommerce_checkout_process', [$this, 'webgsm_debug_log_post'], 1);
    }
    
    /**
     * Debug: Loghează datele POST pentru troubleshooting
     */
    public function webgsm_debug_log_post() {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[WebGSM] ========== CHECKOUT PROCESS START ==========');
            error_log('[WebGSM] billing_customer_type: ' . ($_POST['billing_customer_type'] ?? 'NESETAT'));
            error_log('[WebGSM] billing_first_name: ' . ($_POST['billing_first_name'] ?? 'NESETAT'));
            error_log('[WebGSM] billing_last_name: ' . ($_POST['billing_last_name'] ?? 'NESETAT'));
            error_log('[WebGSM] billing_phone: ' . ($_POST['billing_phone'] ?? 'NESETAT'));
            error_log('[WebGSM] billing_email: ' . ($_POST['billing_email'] ?? 'NESETAT'));
            error_log('[WebGSM] billing_address_1: ' . ($_POST['billing_address_1'] ?? 'NESETAT'));
            error_log('[WebGSM] billing_city: ' . ($_POST['billing_city'] ?? 'NESETAT'));
            error_log('[WebGSM] billing_state: ' . ($_POST['billing_state'] ?? 'NESETAT'));
            error_log('[WebGSM] billing_company: ' . ($_POST['billing_company'] ?? 'NESETAT'));
            error_log('[WebGSM] billing_cui: ' . ($_POST['billing_cui'] ?? 'NESETAT'));
            error_log('[WebGSM] billing_j: ' . ($_POST['billing_j'] ?? 'NESETAT'));
            error_log('[WebGSM] selected_person: ' . ($_POST['selected_person'] ?? 'NESETAT'));
            error_log('[WebGSM] selected_company: ' . ($_POST['selected_company'] ?? 'NESETAT'));

            // Packeta debug: shipping method + pickup point data
            $shipping_method = $_POST['shipping_method'][0] ?? ($_POST['shipping_method'] ?? 'NESETAT');
            error_log('[WebGSM] shipping_method: ' . (is_array($shipping_method) ? implode(',', $shipping_method) : $shipping_method));

            $chosen = WC()->session ? WC()->session->get('chosen_shipping_methods') : [];
            error_log('[WebGSM] session chosen_shipping_methods: ' . print_r($chosen, true));

            $packetery_fields = [];
            foreach ($_POST as $k => $v) {
                if (is_string($k) && stripos($k, 'packetery') !== false) {
                    $packetery_fields[$k] = $v;
                }
            }
            if (!empty($packetery_fields)) {
                error_log('[WebGSM] Packeta POST fields: ' . print_r($packetery_fields, true));
            } else {
                error_log('[WebGSM] Packeta POST fields: NICIUNA');
            }

            if (class_exists('WebGSM_Checkout_Pro')) {
                $is_pickup = WebGSM_Checkout_Pro::is_packeta_pickup_point_method();
                error_log('[WebGSM] is_packeta_pickup_point: ' . ($is_pickup ? 'DA' : 'NU'));
            }
        }
    }
    
    /**
     * Validare principală - PERMISIVĂ
     */
    public function webgsm_validate_checkout() {
        
        // Termeni și condiții – obligatoriu
        if (empty($_POST['terms'])) {
            wc_add_notice(
                __('Te rugăm să bifezi că ai citit și ești de acord cu Termenii și Condițiile, Politica de Confidențialitate și condițiile de garanție.', 'webgsm-checkout-pro'),
                'error'
            );
        }
        
        // Punct ridicare – când Box e selectat, trebuie ales locker-ul
        if (class_exists('WebGSM_Checkout_Pro') && WebGSM_Checkout_Pro::is_packeta_pickup_point_method()) {
            $has_point = false;
            foreach (array_keys($_POST) as $key) {
                if (is_string($key) && (stripos($key, 'packetery') !== false || stripos($key, 'packeta') !== false) && (stripos($key, 'point') !== false || stripos($key, 'branch') !== false)) {
                    $val = $_POST[$key] ?? '';
                    if (!empty(trim((string) $val))) {
                        $has_point = true;
                        break;
                    }
                }
            }
            if (!$has_point) {
                wc_add_notice(
                    __('Selectează punctul de ridicare (Easybox/Fanbox/Sameday Box) înainte de a finaliza comanda.', 'webgsm-checkout-pro'),
                    'error'
                );
            }
        }
        
        // Determină tipul de client
        $customer_type = $this->webgsm_get_customer_type();
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[WebGSM] Validare pentru tip client: ' . $customer_type);
        }
        
        // Preia datele (din POST sau fallback din carduri)
        $data = $this->webgsm_get_checkout_data($customer_type);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[WebGSM] Date pentru validare: ' . print_r($data, true));
        }
        
        // =============================================
        // VALIDĂRI PENTRU PERSOANĂ FIZICĂ (PF)
        // =============================================
        
        if ($customer_type === 'pf') {
            $this->webgsm_validate_pf($data);
        }
        
        // =============================================
        // VALIDĂRI PENTRU PERSOANĂ JURIDICĂ (PJ)
        // =============================================
        
        if ($customer_type === 'pj') {
            $this->webgsm_validate_pj($data);
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[WebGSM] ========== VALIDARE COMPLETĂ ==========');
        }
    }
    
    /**
     * Validare pentru Persoană Fizică - MINIMALĂ
     */
    private function webgsm_validate_pf($data) {
        
        // Nume - obligatoriu dar permisiv
        if (empty($data['first_name']) && empty($data['last_name']) && empty($data['name'])) {
            wc_add_notice(
                __('Te rugăm să selectezi sau să adaugi o persoană fizică pentru facturare.', 'webgsm-checkout-pro'),
                'error'
            );
            return;
        }
        
        // Telefon - obligatoriu
        if (empty($data['phone'])) {
            wc_add_notice(
                __('Telefonul este obligatoriu.', 'webgsm-checkout-pro'),
                'error'
            );
            return;
        }
        
        // Email - obligatoriu
        if (empty($data['email'])) {
            wc_add_notice(
                __('Email-ul este obligatoriu.', 'webgsm-checkout-pro'),
                'error'
            );
            return;
        }
        
        // Validare format email
        if (!empty($data['email']) && !is_email($data['email'])) {
            wc_add_notice(
                __('Adresa de email nu este validă.', 'webgsm-checkout-pro'),
                'error'
            );
            return;
        }
        
        // Adresă - obligatorie dar cu mesaj prietenos
        if (empty($data['address'])) {
            wc_add_notice(
                __('Adresa este obligatorie. Te rugăm să completezi datele de facturare.', 'webgsm-checkout-pro'),
                'error'
            );
            return;
        }
        
        // CNP - opțional, validare doar dacă e completat
        if (!empty($data['cnp'])) {
            $cnp_clean = preg_replace('/[^0-9]/', '', $data['cnp']);
            if (strlen($cnp_clean) !== 13) {
                wc_add_notice(
                    __('CNP-ul trebuie să aibă 13 cifre.', 'webgsm-checkout-pro'),
                    'error'
                );
            }
        }
    }
    
    /**
     * Validare pentru Persoană Juridică - DOAR CUI și NUME FIRMĂ
     */
    private function webgsm_validate_pj($data) {
        
        // Nume firmă - obligatoriu
        if (empty($data['company'])) {
            wc_add_notice(
                __('Te rugăm să selectezi sau să adaugi o firmă pentru facturare.', 'webgsm-checkout-pro'),
                'error'
            );
            return;
        }
        
        // CUI - obligatoriu pentru PJ
        if (empty($data['cui'])) {
            wc_add_notice(
                __('CUI este obligatoriu pentru persoană juridică.', 'webgsm-checkout-pro'),
                'error'
            );
            return;
        }
        
        // Validare format CUI (2-10 cifre, poate avea prefix RO)
        $cui_numeric = preg_replace('/^RO/i', '', $data['cui']);
        $cui_numeric = preg_replace('/[^0-9]/', '', $cui_numeric);
        if (strlen($cui_numeric) < 2 || strlen($cui_numeric) > 10) {
            wc_add_notice(
                __('CUI-ul nu are un format valid.', 'webgsm-checkout-pro'),
                'error'
            );
            return;
        }
        
        // Telefon - obligatoriu pentru PJ
        if (empty($data['phone'])) {
            wc_add_notice(
                __('Telefonul firmei este obligatoriu.', 'webgsm-checkout-pro'),
                'error'
            );
            return;
        }
        
        // Email - obligatoriu pentru PJ
        if (empty($data['email'])) {
            wc_add_notice(
                __('Email-ul firmei este obligatoriu.', 'webgsm-checkout-pro'),
                'error'
            );
            return;
        }
        
        // Nr. Reg. Com. - opțional (nu blochăm)
        // IBAN - opțional (nu blochăm)
    }
    
    /**
     * Determină tipul de client
     */
    private function webgsm_get_customer_type() {
        if (isset($_POST['billing_customer_type']) && !empty($_POST['billing_customer_type'])) {
            return sanitize_text_field($_POST['billing_customer_type']);
        }
        return 'pf'; // default
    }
    
    /**
     * Preia datele de checkout din POST și fallback din carduri
     */
    private function webgsm_get_checkout_data($customer_type) {
        
        // Inițializează cu date din POST
        $data = [
            'first_name' => $this->webgsm_get_post_value('billing_first_name'),
            'last_name'  => $this->webgsm_get_post_value('billing_last_name'),
            'name'       => '', // va fi calculat
            'phone'      => $this->webgsm_normalize_phone($this->webgsm_get_post_value('billing_phone')),
            'email'      => sanitize_email($this->webgsm_get_post_value('billing_email')),
            'address'    => $this->webgsm_get_post_value('billing_address_1'),
            'city'       => $this->webgsm_get_post_value('billing_city'),
            'state'      => $this->webgsm_get_post_value('billing_state'),
            'postcode'   => $this->webgsm_get_post_value('billing_postcode'),
            'company'    => $this->webgsm_get_post_value('billing_company'),
            'cui'        => $this->webgsm_get_post_value('billing_cui'),
            'j'          => $this->webgsm_get_post_value('billing_j'),
            'cnp'        => $this->webgsm_get_post_value('billing_cnp'),
            'iban'       => $this->webgsm_get_post_value('billing_iban'),
            'bank'       => $this->webgsm_get_post_value('billing_bank'),
        ];
        
        // Calculează numele complet
        $data['name'] = trim($data['first_name'] . ' ' . $data['last_name']);
        
        // Dacă lipsesc date critice, încearcă fallback din carduri
        if ($this->webgsm_data_needs_fallback($data, $customer_type)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[WebGSM] Date incomplete în POST, încerc fallback din carduri');
            }
            $data = $this->webgsm_merge_from_cards($data, $customer_type);
        }
        
        return $data;
    }
    
    /**
     * Verifică dacă datele necesită fallback
     */
    private function webgsm_data_needs_fallback($data, $customer_type) {
        if ($customer_type === 'pf') {
            return empty($data['phone']) || empty($data['email']) || empty($data['address']);
        } else {
            return empty($data['cui']) || empty($data['company']);
        }
    }
    
    /**
     * Merge date din carduri
     */
    private function webgsm_merge_from_cards($data, $customer_type) {
        $user_id = get_current_user_id();
        
        if ($customer_type === 'pf') {
            // Preia din cardul de persoană
            $person = $this->webgsm_get_selected_person($user_id);
            if ($person) {
                $data = $this->webgsm_fill_from_person($data, $person);
            }
        } else {
            // Preia din cardul de firmă
            $company = $this->webgsm_get_selected_company($user_id);
            if ($company) {
                $data = $this->webgsm_fill_from_company($data, $company);
            }
        }
        
        return $data;
    }
    
    /**
     * Preia persoana selectată
     */
    private function webgsm_get_selected_person($user_id) {
        $selected_index = isset($_POST['selected_person']) ? intval($_POST['selected_person']) : 0;
        
        // Pentru utilizatori logați
        if ($user_id) {
            $persons = get_user_meta($user_id, 'webgsm_persons', true);
            if (is_array($persons) && isset($persons[$selected_index])) {
                return $persons[$selected_index];
            }
        }
        
        // Pentru guest - din session (PHP + WooCommerce)
        if ( class_exists( 'WebGSM_Checkout_Pro' ) ) {
            $gp = WebGSM_Checkout_Pro::get_guest_person_from_storage();
            if ( is_array( $gp ) ) {
                return $gp;
            }
        }

        return null;
    }
    
    /**
     * Preia firma selectată
     */
    private function webgsm_get_selected_company($user_id) {
        $selected_index = isset($_POST['selected_company']) ? intval($_POST['selected_company']) : 0;
        
        // Pentru utilizatori logați
        if ($user_id) {
            $companies = get_user_meta($user_id, 'webgsm_companies', true);
            if (is_array($companies) && isset($companies[$selected_index])) {
                return $companies[$selected_index];
            }
        }
        
        // Pentru guest - din session (PHP + WooCommerce)
        if ( class_exists( 'WebGSM_Checkout_Pro' ) ) {
            $gc = WebGSM_Checkout_Pro::get_guest_company_from_storage();
            if ( is_array( $gc ) ) {
                return $gc;
            }
        }

        return null;
    }
    
    /**
     * Completează date din persoană
     */
    private function webgsm_fill_from_person($data, $person) {
        if (empty($data['name']) && !empty($person['name'])) {
            $parts = explode(' ', $person['name'], 2);
            $data['first_name'] = $parts[0] ?? '';
            $data['last_name'] = $parts[1] ?? '';
            $data['name'] = $person['name'];
        }
        
        if (empty($data['phone']) && !empty($person['phone'])) {
            $data['phone'] = $this->webgsm_normalize_phone($person['phone']);
        }
        
        if (empty($data['email']) && !empty($person['email'])) {
            $data['email'] = $person['email'];
        }
        
        if (empty($data['address']) && !empty($person['address'])) {
            $data['address'] = $person['address'];
        }
        
        if (empty($data['city']) && !empty($person['city'])) {
            $data['city'] = $person['city'];
        }
        
        if (empty($data['state']) && !empty($person['county'])) {
            $data['state'] = $person['county'];
        }
        
        if (empty($data['cnp']) && !empty($person['cnp'])) {
            $data['cnp'] = $person['cnp'];
        }
        
        return $data;
    }
    
    /**
     * Completează date din firmă
     */
    private function webgsm_fill_from_company($data, $company) {
        if (empty($data['company']) && !empty($company['name'])) {
            $data['company'] = $company['name'];
            
            // Și pentru billing name
            $parts = explode(' ', $company['name'], 2);
            $data['first_name'] = $parts[0] ?? $company['name'];
            $data['last_name'] = $parts[1] ?? 'SRL';
            $data['name'] = $company['name'];
        }
        
        if (empty($data['cui']) && !empty($company['cui'])) {
            $data['cui'] = $company['cui'];
        }
        
        if (empty($data['j']) && !empty($company['reg'])) {
            $data['j'] = $company['reg'];
        }
        
        if (empty($data['phone']) && !empty($company['phone'])) {
            $data['phone'] = $this->webgsm_normalize_phone($company['phone']);
        }
        
        if (empty($data['email']) && !empty($company['email'])) {
            $data['email'] = $company['email'];
        }
        
        if (empty($data['address']) && !empty($company['address'])) {
            $data['address'] = $company['address'];
        }
        
        if (empty($data['city']) && !empty($company['city'])) {
            $data['city'] = $company['city'];
        }
        
        if (empty($data['state']) && !empty($company['county'])) {
            $data['state'] = $company['county'];
        }
        
        return $data;
    }
    
    /**
     * Preia valoare din POST
     */
    private function webgsm_get_post_value($key) {
        return isset($_POST[$key]) ? trim(sanitize_text_field($_POST[$key])) : '';
    }
    
    /**
     * Normalizează telefonul
     */
    private function webgsm_normalize_phone($phone) {
        return preg_replace('/[\s\-\.\(\)]/', '', $phone);
    }
}

// Inițializare
new WebGSM_Checkout_Validate();
