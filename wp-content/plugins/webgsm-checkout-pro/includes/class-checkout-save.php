<?php
/**
 * WebGSM Checkout Save
 * Salvează datele comenzii și sincronizează cu profilul WordPress
 * 
 * @package WebGSM_Checkout_Pro
 * @version 4.1.0
 * 
 * LOGICĂ:
 * - Preia date din $_POST (populate de JS din carduri)
 * - Fallback: Preia din carduri (session/user_meta) dacă $_POST e gol
 * - Salvează în comandă (order meta)
 * - Salvează în user_meta pentru carduri (webgsm_companies, webgsm_persons)
 * - SINCRONIZARE: Actualizează billing_phone, billing_address_1 etc. în profilul WordPress
 */

if (!defined('ABSPATH')) exit;

class WebGSM_Checkout_Save {
    
    /**
     * Constructor - Înregistrează hook-urile
     */
    public function __construct() {
        // Populează câmpurile din carduri ÎNAINTE de validare (prioritate 5)
        add_action('woocommerce_checkout_process', [$this, 'webgsm_populate_fields_from_cards'], 5);
        
        // Salvează în comandă DUPĂ validare
        add_action('woocommerce_checkout_update_order_meta', [$this, 'webgsm_save_order_meta'], 10, 2);
        add_action('woocommerce_checkout_create_order', [$this, 'webgsm_save_order_meta_hpos'], 10, 2);
        
        // Sincronizează cu profilul WordPress DUPĂ plasarea comenzii
        add_action('woocommerce_checkout_order_processed', [$this, 'webgsm_sync_to_user_profile'], 10, 3);
    }
    
    // =========================================
    // POPULARE DIN CARDURI (înainte de validare)
    // =========================================
    
    /**
     * Populează câmpurile din cardurile selectate ÎNAINTE de validare
     * Rulează cu prioritate 5 (înainte de validate care e pe 10)
     */
    public function webgsm_populate_fields_from_cards() {
        $customer_type = isset($_POST['billing_customer_type']) ? sanitize_text_field($_POST['billing_customer_type']) : 'pf';
        
        // Preia date din cardul de persoană (PF)
        if ($customer_type === 'pf') {
            $this->webgsm_populate_from_person_card();
        }
        
        // Preia date din cardul de firmă (PJ)
        if ($customer_type === 'pj') {
            $this->webgsm_populate_from_company_card();
        }
        
        // Preia adresa de livrare din card (dacă "same as billing" e debifat)
        $same_as_billing = isset($_POST['same_as_billing']) && $_POST['same_as_billing'] == '1';
        if (!$same_as_billing) {
            $this->webgsm_populate_from_address_card();
        }
    }
    
    /**
     * Populează din cardul de persoană fizică selectat
     */
    private function webgsm_populate_from_person_card() {
        $user_id = get_current_user_id();
        
        // Verifică dacă avem date goale în POST
        $billing_address = isset($_POST['billing_address_1']) ? trim($_POST['billing_address_1']) : '';
        $billing_phone = isset($_POST['billing_phone']) ? trim($_POST['billing_phone']) : '';
        
        // Dacă adresa sau telefonul sunt goale, încearcă să preiei din card
        if (empty($billing_address) || empty($billing_phone)) {
            
            // Pentru utilizatori logați
            if ($user_id) {
                $selected_index = isset($_POST['selected_person']) ? intval($_POST['selected_person']) : 0;
                $persons = get_user_meta($user_id, 'webgsm_persons', true);
                
                if (is_array($persons) && isset($persons[$selected_index])) {
                    $this->webgsm_fill_post_from_person($persons[$selected_index]);
                }
            }
            // Pentru guest - preia din session
            elseif (isset($_SESSION['webgsm_guest_person'])) {
                $this->webgsm_fill_post_from_person($_SESSION['webgsm_guest_person']);
            }
        }
    }
    
    /**
     * Completează $_POST din datele persoanei
     */
    private function webgsm_fill_post_from_person($person) {
        // Adresă
        if (empty($_POST['billing_address_1']) && !empty($person['address'])) {
            $_POST['billing_address_1'] = sanitize_text_field($person['address']);
        }
        if (empty($_POST['billing_city']) && !empty($person['city'])) {
            $_POST['billing_city'] = sanitize_text_field($person['city']);
        }
        if (empty($_POST['billing_state']) && !empty($person['county'])) {
            $_POST['billing_state'] = sanitize_text_field($person['county']);
        }
        if (empty($_POST['billing_postcode']) && !empty($person['postcode'])) {
            $_POST['billing_postcode'] = sanitize_text_field($person['postcode']);
        }
        
        // Contact
        if (empty($_POST['billing_phone']) && !empty($person['phone'])) {
            $_POST['billing_phone'] = $this->webgsm_normalize_phone($person['phone']);
        }
        if (empty($_POST['billing_email']) && !empty($person['email'])) {
            $_POST['billing_email'] = sanitize_email($person['email']);
        }
        
        // CNP
        if (empty($_POST['billing_cnp']) && !empty($person['cnp'])) {
            $_POST['billing_cnp'] = sanitize_text_field($person['cnp']);
        }
        
        // Nume
        if (!empty($person['name'])) {
            $parts = explode(' ', trim($person['name']), 2);
            if (empty($_POST['billing_first_name'])) {
                $_POST['billing_first_name'] = sanitize_text_field($parts[0]);
            }
            if (empty($_POST['billing_last_name']) && isset($parts[1])) {
                $_POST['billing_last_name'] = sanitize_text_field($parts[1]);
            }
        }
    }
    
    /**
     * Populează din cardul de firmă selectat
     */
    private function webgsm_populate_from_company_card() {
        $user_id = get_current_user_id();
        
        // Verifică dacă avem CUI gol
        $billing_cui = isset($_POST['billing_cui']) ? trim($_POST['billing_cui']) : '';
        
        if (empty($billing_cui)) {
            
            // Pentru utilizatori logați
            if ($user_id) {
                $selected_index = isset($_POST['selected_company']) ? intval($_POST['selected_company']) : 0;
                $companies = get_user_meta($user_id, 'webgsm_companies', true);
                
                if (is_array($companies) && isset($companies[$selected_index])) {
                    $this->webgsm_fill_post_from_company($companies[$selected_index]);
                }
            }
            // Pentru guest
            elseif (isset($_SESSION['webgsm_guest_company'])) {
                $this->webgsm_fill_post_from_company($_SESSION['webgsm_guest_company']);
            }
        }
    }
    
    /**
     * Completează $_POST din datele firmei
     */
    private function webgsm_fill_post_from_company($company) {
        // Date firmă
        if (empty($_POST['billing_company']) && !empty($company['name'])) {
            $_POST['billing_company'] = sanitize_text_field($company['name']);
        }
        if (empty($_POST['billing_cui']) && !empty($company['cui'])) {
            $_POST['billing_cui'] = sanitize_text_field($company['cui']);
        }
        if (empty($_POST['billing_j']) && !empty($company['reg'])) {
            $_POST['billing_j'] = sanitize_text_field($company['reg']);
        }
        
        // Adresă
        if (empty($_POST['billing_address_1']) && !empty($company['address'])) {
            $_POST['billing_address_1'] = sanitize_text_field($company['address']);
        }
        if (empty($_POST['billing_city']) && !empty($company['city'])) {
            $_POST['billing_city'] = sanitize_text_field($company['city']);
        }
        if (empty($_POST['billing_state']) && !empty($company['county'])) {
            $_POST['billing_state'] = sanitize_text_field($company['county']);
        }
        
        // Contact (dacă salvat în firmă)
        if (empty($_POST['billing_phone']) && !empty($company['phone'])) {
            $_POST['billing_phone'] = $this->webgsm_normalize_phone($company['phone']);
        }
        if (empty($_POST['billing_email']) && !empty($company['email'])) {
            $_POST['billing_email'] = sanitize_email($company['email']);
        }
        
        // IBAN și Banca
        if (empty($_POST['billing_iban']) && !empty($company['iban'])) {
            $_POST['billing_iban'] = sanitize_text_field($company['iban']);
        }
        if (empty($_POST['billing_bank']) && !empty($company['bank'])) {
            $_POST['billing_bank'] = sanitize_text_field($company['bank']);
        }
        
        // Pentru PJ, folosește numele persoanei de contact dacă există, altfel folosește numele firmei
        if (!empty($company['name'])) {
            $parts = explode(' ', $company['name'], 2);
            if (empty($_POST['billing_first_name'])) {
                $_POST['billing_first_name'] = sanitize_text_field($parts[0]);
            }
            if (empty($_POST['billing_last_name'])) {
                $_POST['billing_last_name'] = isset($parts[1]) ? sanitize_text_field($parts[1]) : 'SRL';
            }
        }
    }
    
    /**
     * Populează adresa de shipping din cardul selectat
     */
    private function webgsm_populate_from_address_card() {
        $user_id = get_current_user_id();
        
        $shipping_address = isset($_POST['shipping_address_1']) ? trim($_POST['shipping_address_1']) : '';
        
        if (empty($shipping_address) && $user_id) {
            $selected = isset($_POST['selected_address']) ? $_POST['selected_address'] : 'default';
            
            if ($selected === 'default') {
                // Preia adresa default din profil
                $_POST['shipping_first_name'] = get_user_meta($user_id, 'billing_first_name', true);
                $_POST['shipping_last_name'] = get_user_meta($user_id, 'billing_last_name', true);
                $_POST['shipping_address_1'] = get_user_meta($user_id, 'billing_address_1', true);
                $_POST['shipping_city'] = get_user_meta($user_id, 'billing_city', true);
                $_POST['shipping_state'] = get_user_meta($user_id, 'billing_state', true);
                $_POST['shipping_postcode'] = get_user_meta($user_id, 'billing_postcode', true);
                $_POST['shipping_phone'] = get_user_meta($user_id, 'billing_phone', true);
            } else {
                // Preia din adresele salvate
                $addresses = get_user_meta($user_id, 'webgsm_addresses', true);
                $index = intval($selected);
                
                if (is_array($addresses) && isset($addresses[$index])) {
                    $addr = $addresses[$index];
                    $parts = explode(' ', $addr['name'] ?? '', 2);
                    
                    $_POST['shipping_first_name'] = sanitize_text_field($parts[0] ?? '');
                    $_POST['shipping_last_name'] = sanitize_text_field($parts[1] ?? '');
                    $_POST['shipping_address_1'] = sanitize_text_field($addr['address'] ?? '');
                    $_POST['shipping_city'] = sanitize_text_field($addr['city'] ?? '');
                    $_POST['shipping_state'] = sanitize_text_field($addr['county'] ?? '');
                    $_POST['shipping_postcode'] = sanitize_text_field($addr['postcode'] ?? '');
                    $_POST['shipping_phone'] = $this->webgsm_normalize_phone($addr['phone'] ?? '');
                }
            }
        }
    }
    
    /**
     * Normalizează numărul de telefon
     */
    private function webgsm_normalize_phone($phone) {
        return preg_replace('/[\s\-\.\(\)]/', '', $phone);
    }
    
    // =========================================
    // SALVARE ÎN COMANDĂ
    // =========================================
    
    /**
     * Salvare meta pentru comenzi clasice (CPT)
     */
    public function webgsm_save_order_meta($order_id, $data) {
        $order = wc_get_order($order_id);
        if (!$order) return;
        $this->webgsm_save_fields_to_order($order);
    }
    
    /**
     * Salvare meta pentru HPOS (High-Performance Order Storage)
     */
    public function webgsm_save_order_meta_hpos($order, $data) {
        $this->webgsm_save_fields_to_order($order);
    }
    
    /**
     * Salvează toate câmpurile în comandă
     */
    private function webgsm_save_fields_to_order($order) {
        // Tip client
        $customer_type = isset($_POST['billing_customer_type']) ? sanitize_text_field($_POST['billing_customer_type']) : 'pf';
        $order->update_meta_data('_customer_type', $customer_type);
        $order->update_meta_data('_billing_customer_type', $customer_type);
        
        // Salvează billing fields în comandă
        $billing_fields = [
            'billing_first_name',
            'billing_last_name',
            'billing_company',
            'billing_address_1',
            'billing_address_2',
            'billing_city',
            'billing_state',
            'billing_postcode',
            'billing_country',
            'billing_email',
            'billing_phone',
        ];
        
        foreach ($billing_fields as $field) {
            if (isset($_POST[$field])) {
                $value = sanitize_text_field($_POST[$field]);
                
                // Normalizează telefonul
                if ($field === 'billing_phone') {
                    $value = $this->webgsm_normalize_phone($value);
                }
                
                $method = 'set_' . $field;
                if (method_exists($order, $method)) {
                    $order->$method($value);
                }
            }
        }
        
        // Salvează câmpuri custom ca meta
        $custom_fields = [
            'billing_cui',
            'billing_j',
            'billing_iban',
            'billing_bank',
            'billing_cnp',
        ];
        
        foreach ($custom_fields as $field) {
            if (isset($_POST[$field])) {
                $value = sanitize_text_field($_POST[$field]);
                $order->update_meta_data('_' . $field, $value);
            }
        }
        
        // Salvează obiect agregat _company_data (pentru PJ)
        if ($customer_type === 'pj') {
            $company_data = [
                'name'    => sanitize_text_field($_POST['billing_company'] ?? ''),
                'cui'     => sanitize_text_field($_POST['billing_cui'] ?? ''),
                'j'       => sanitize_text_field($_POST['billing_j'] ?? ''),
                'iban'    => sanitize_text_field($_POST['billing_iban'] ?? ''),
                'bank'    => sanitize_text_field($_POST['billing_bank'] ?? ''),
                'address' => sanitize_text_field($_POST['billing_address_1'] ?? ''),
                'city'    => sanitize_text_field($_POST['billing_city'] ?? ''),
                'state'   => sanitize_text_field($_POST['billing_state'] ?? ''),
                'phone'   => $this->webgsm_normalize_phone($_POST['billing_phone'] ?? ''),
                'email'   => sanitize_email($_POST['billing_email'] ?? ''),
            ];
            $order->update_meta_data('_company_data', $company_data);
        }
        
        // Salvează obiect agregat _person_data (pentru PF)
        if ($customer_type === 'pf') {
            $person_data = [
                'name'    => sanitize_text_field($_POST['billing_first_name'] ?? '') . ' ' . sanitize_text_field($_POST['billing_last_name'] ?? ''),
                'cnp'     => sanitize_text_field($_POST['billing_cnp'] ?? ''),
                'phone'   => $this->webgsm_normalize_phone($_POST['billing_phone'] ?? ''),
                'email'   => sanitize_email($_POST['billing_email'] ?? ''),
                'address' => sanitize_text_field($_POST['billing_address_1'] ?? ''),
                'city'    => sanitize_text_field($_POST['billing_city'] ?? ''),
                'state'   => sanitize_text_field($_POST['billing_state'] ?? ''),
            ];
            $order->update_meta_data('_person_data', $person_data);
        }
        
        // Procesează shipping
        $same_as_billing = isset($_POST['same_as_billing']) && $_POST['same_as_billing'] == '1';
        $order->update_meta_data('_same_as_billing', $same_as_billing ? '1' : '0');
        
        if ($same_as_billing) {
            // Copiază billing în shipping
            $order->set_shipping_first_name($order->get_billing_first_name());
            $order->set_shipping_last_name($order->get_billing_last_name());
            $order->set_shipping_company($order->get_billing_company());
            $order->set_shipping_address_1($order->get_billing_address_1());
            $order->set_shipping_address_2($order->get_billing_address_2());
            $order->set_shipping_city($order->get_billing_city());
            $order->set_shipping_state($order->get_billing_state());
            $order->set_shipping_postcode($order->get_billing_postcode());
            $order->set_shipping_country($order->get_billing_country());
        } else {
            // Preia shipping din POST
            $shipping_fields = [
                'shipping_first_name',
                'shipping_last_name',
                'shipping_company',
                'shipping_address_1',
                'shipping_address_2',
                'shipping_city',
                'shipping_state',
                'shipping_postcode',
                'shipping_country',
            ];
            
            foreach ($shipping_fields as $field) {
                if (isset($_POST[$field])) {
                    $method = 'set_' . $field;
                    if (method_exists($order, $method)) {
                        $order->$method(sanitize_text_field($_POST[$field]));
                    }
                }
            }
        }
    }
    
    // =========================================
    // SINCRONIZARE CU PROFILUL WORDPRESS
    // =========================================
    
    /**
     * Sincronizează datele de facturare cu profilul WordPress
     * Rulează DUPĂ plasarea comenzii
     */
    public function webgsm_sync_to_user_profile($order_id, $posted_data, $order) {
        $user_id = $order->get_customer_id();
        
        // Nu sincroniza pentru guest
        if (!$user_id) return;
        
        $customer_type = isset($_POST['billing_customer_type']) ? sanitize_text_field($_POST['billing_customer_type']) : 'pf';
        
        // Sincronizează câmpurile standard WooCommerce
        $this->webgsm_sync_billing_fields($user_id);
        
        // Sincronizează câmpurile custom
        $this->webgsm_sync_custom_fields($user_id, $customer_type);
        
        // Actualizează lista de carduri (dacă e cazul)
        $this->webgsm_update_card_lists($user_id, $customer_type);
    }
    
    /**
     * Sincronizează câmpurile billing cu profilul utilizatorului
     */
    private function webgsm_sync_billing_fields($user_id) {
        $fields_to_sync = [
            'billing_first_name',
            'billing_last_name',
            'billing_company',
            'billing_address_1',
            'billing_address_2',
            'billing_city',
            'billing_state',
            'billing_postcode',
            'billing_country',
            'billing_email',
            'billing_phone',
        ];
        
        foreach ($fields_to_sync as $field) {
            if (isset($_POST[$field]) && !empty($_POST[$field])) {
                $value = sanitize_text_field($_POST[$field]);
                
                // Normalizează telefonul
                if ($field === 'billing_phone') {
                    $value = $this->webgsm_normalize_phone($value);
                }
                
                update_user_meta($user_id, $field, $value);
            }
        }
    }
    
    /**
     * Sincronizează câmpurile custom cu profilul utilizatorului
     */
    private function webgsm_sync_custom_fields($user_id, $customer_type) {
        // Tip client
        update_user_meta($user_id, 'webgsm_customer_type', $customer_type);
        
        // Câmpuri PJ
        if ($customer_type === 'pj') {
            $pj_fields = ['billing_cui', 'billing_j', 'billing_iban', 'billing_bank'];
            foreach ($pj_fields as $field) {
                if (isset($_POST[$field])) {
                    update_user_meta($user_id, $field, sanitize_text_field($_POST[$field]));
                }
            }
            // Setează userul ca PJ pentru compatibilitate B2B
            update_user_meta($user_id, '_is_pj', 'yes');
            update_user_meta($user_id, '_tip_client', 'pj');
        }
        
        // Câmpuri PF
        if ($customer_type === 'pf') {
            if (isset($_POST['billing_cnp']) && !empty($_POST['billing_cnp'])) {
                update_user_meta($user_id, 'billing_cnp', sanitize_text_field($_POST['billing_cnp']));
            }
        }
    }
    
    /**
     * Actualizează listele de carduri (adaugă noile date dacă nu există)
     */
    private function webgsm_update_card_lists($user_id, $customer_type) {
        // Verifică dacă userul a bifat "salvează pentru viitor" (opțional)
        $save_for_future = isset($_POST['webgsm_save_data']) && $_POST['webgsm_save_data'] == '1';
        
        // Pentru PF - actualizează lista de persoane
        if ($customer_type === 'pf') {
            $this->webgsm_maybe_add_person_card($user_id);
        }
        
        // Pentru PJ - actualizează lista de firme
        if ($customer_type === 'pj') {
            $this->webgsm_maybe_add_company_card($user_id);
        }
    }
    
    /**
     * Adaugă persoană nouă în lista de carduri (dacă nu există)
     */
    private function webgsm_maybe_add_person_card($user_id) {
        $phone = $this->webgsm_normalize_phone($_POST['billing_phone'] ?? '');
        $email = sanitize_email($_POST['billing_email'] ?? '');
        
        // Nu adăuga dacă lipsesc date esențiale
        if (empty($phone) && empty($email)) return;
        
        $persons = get_user_meta($user_id, 'webgsm_persons', true);
        if (!is_array($persons)) $persons = [];
        
        // Verifică dacă persoana există deja (după telefon sau email)
        foreach ($persons as $person) {
            if ((!empty($phone) && $person['phone'] === $phone) ||
                (!empty($email) && $person['email'] === $email)) {
                return; // Există deja
            }
        }
        
        // Adaugă persoana nouă
        $new_person = [
            'name'     => sanitize_text_field($_POST['billing_first_name'] ?? '') . ' ' . sanitize_text_field($_POST['billing_last_name'] ?? ''),
            'phone'    => $phone,
            'email'    => $email,
            'cnp'      => sanitize_text_field($_POST['billing_cnp'] ?? ''),
            'address'  => sanitize_text_field($_POST['billing_address_1'] ?? ''),
            'city'     => sanitize_text_field($_POST['billing_city'] ?? ''),
            'county'   => sanitize_text_field($_POST['billing_state'] ?? ''),
            'postcode' => sanitize_text_field($_POST['billing_postcode'] ?? ''),
        ];
        
        $persons[] = $new_person;
        update_user_meta($user_id, 'webgsm_persons', $persons);
    }
    
    /**
     * Adaugă firmă nouă în lista de carduri (dacă nu există)
     */
    private function webgsm_maybe_add_company_card($user_id) {
        $cui = sanitize_text_field($_POST['billing_cui'] ?? '');
        
        // Nu adăuga dacă lipsește CUI
        if (empty($cui)) return;
        
        $companies = get_user_meta($user_id, 'webgsm_companies', true);
        if (!is_array($companies)) $companies = [];
        
        // Verifică dacă firma există deja (după CUI)
        foreach ($companies as $company) {
            if ($company['cui'] === $cui) {
                return; // Există deja
            }
        }
        
        // Adaugă firma nouă
        $new_company = [
            'name'    => sanitize_text_field($_POST['billing_company'] ?? ''),
            'cui'     => $cui,
            'reg'     => sanitize_text_field($_POST['billing_j'] ?? ''),
            'iban'    => sanitize_text_field($_POST['billing_iban'] ?? ''),
            'bank'    => sanitize_text_field($_POST['billing_bank'] ?? ''),
            'address' => sanitize_text_field($_POST['billing_address_1'] ?? ''),
            'city'    => sanitize_text_field($_POST['billing_city'] ?? ''),
            'county'  => sanitize_text_field($_POST['billing_state'] ?? ''),
            'phone'   => $this->webgsm_normalize_phone($_POST['billing_phone'] ?? ''),
            'email'   => sanitize_email($_POST['billing_email'] ?? ''),
        ];
        
        $companies[] = $new_company;
        update_user_meta($user_id, 'webgsm_companies', $companies);
    }
}

// Inițializare
new WebGSM_Checkout_Save();
