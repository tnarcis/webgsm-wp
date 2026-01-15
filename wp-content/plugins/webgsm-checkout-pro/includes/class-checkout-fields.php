<?php
/**
 * WebGSM Checkout Fields
 * Versiunea 5.0.0 - RESCRIS COMPLET
 * 
 * LOGICĂ:
 * - TOATE câmpurile billing sunt hidden (populate din carduri)
 * - NICIUN câmp nu are required (validarea e în JS și validate class)
 * - Priorități optimizate pentru WooCommerce
 * - Compatibilitate Martfury
 * 
 * @package WebGSM_Checkout_Pro
 */

if (!defined('ABSPATH')) exit;

class WebGSM_Checkout_Fields {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Modifică câmpurile checkout
        add_filter('woocommerce_checkout_fields', [$this, 'webgsm_customize_checkout_fields'], 9999);
        
        // Modifică câmpurile billing
        add_filter('woocommerce_billing_fields', [$this, 'webgsm_customize_billing_fields'], 9999);
        
        // Default country România
        add_filter('default_checkout_billing_country', [$this, 'webgsm_default_country']);
        add_filter('default_checkout_shipping_country', [$this, 'webgsm_default_country']);
        
        // Elimină validarea required WooCommerce
        add_filter('woocommerce_checkout_required_field_notice', '__return_empty_string');
        
        // Dezactivează validarea JavaScript WooCommerce
        add_action('wp_enqueue_scripts', [$this, 'webgsm_disable_wc_validation'], 100);
        
        // Elimină required din HTML
        add_filter('woocommerce_form_field_args', [$this, 'webgsm_remove_required_from_field'], 9999, 3);
    }
    
    /**
     * Default country România
     */
    public function webgsm_default_country() {
        return 'RO';
    }
    
    /**
     * Dezactivează validarea WooCommerce (lăsăm validarea noastră)
     */
    public function webgsm_disable_wc_validation() {
        if (is_checkout()) {
            // Elimină validarea WooCommerce pentru câmpurile noastre
            wp_add_inline_script('wc-checkout', '
                jQuery(function($) {
                    // Elimină required de pe toate câmpurile billing
                    $("[id^=billing_]").removeAttr("required aria-required").removeClass("validate-required");
                    $(".woocommerce-billing-fields .validate-required").removeClass("validate-required");
                });
            ');
        }
    }
    
    /**
     * Elimină required din field args
     */
    public function webgsm_remove_required_from_field($args, $key, $value) {
        // Pentru câmpurile billing, elimină required
        if (strpos($key, 'billing_') === 0) {
            $args['required'] = false;
            
            // Elimină clase validate-required
            if (isset($args['class']) && is_array($args['class'])) {
                $args['class'] = array_diff($args['class'], ['validate-required']);
            }
        }
        
        return $args;
    }
    
    /**
     * Customizează câmpurile checkout
     * Toate sunt hidden, fără required
     */
    public function webgsm_customize_checkout_fields($fields) {
        
        // =============================================
        // BILLING FIELDS - TOATE HIDDEN, FĂRĂ REQUIRED
        // =============================================
        
        // Customer type
        $fields['billing']['billing_customer_type'] = [
            'type'     => 'hidden',
            'default'  => 'pf',
            'required' => false,
            'priority' => 1,
        ];
        
        // First name
        if (isset($fields['billing']['billing_first_name'])) {
            $fields['billing']['billing_first_name']['type'] = 'hidden';
            $fields['billing']['billing_first_name']['required'] = false;
            $fields['billing']['billing_first_name']['priority'] = 10;
        }
        
        // Last name
        if (isset($fields['billing']['billing_last_name'])) {
            $fields['billing']['billing_last_name']['type'] = 'hidden';
            $fields['billing']['billing_last_name']['required'] = false;
            $fields['billing']['billing_last_name']['priority'] = 11;
        }
        
        // Company
        if (isset($fields['billing']['billing_company'])) {
            $fields['billing']['billing_company']['type'] = 'hidden';
            $fields['billing']['billing_company']['required'] = false;
            $fields['billing']['billing_company']['priority'] = 15;
        }
        
        // Country
        if (isset($fields['billing']['billing_country'])) {
            $fields['billing']['billing_country']['type'] = 'hidden';
            $fields['billing']['billing_country']['default'] = 'RO';
            $fields['billing']['billing_country']['required'] = false;
            $fields['billing']['billing_country']['priority'] = 20;
        }
        
        // Address 1
        if (isset($fields['billing']['billing_address_1'])) {
            $fields['billing']['billing_address_1']['type'] = 'hidden';
            $fields['billing']['billing_address_1']['required'] = false;
            $fields['billing']['billing_address_1']['priority'] = 30;
        }
        
        // Address 2
        if (isset($fields['billing']['billing_address_2'])) {
            $fields['billing']['billing_address_2']['type'] = 'hidden';
            $fields['billing']['billing_address_2']['required'] = false;
            $fields['billing']['billing_address_2']['priority'] = 31;
        }
        
        // City
        if (isset($fields['billing']['billing_city'])) {
            $fields['billing']['billing_city']['type'] = 'hidden';
            $fields['billing']['billing_city']['required'] = false;
            $fields['billing']['billing_city']['priority'] = 40;
        }
        
        // State
        if (isset($fields['billing']['billing_state'])) {
            $fields['billing']['billing_state']['type'] = 'hidden';
            $fields['billing']['billing_state']['required'] = false;
            $fields['billing']['billing_state']['priority'] = 41;
        }
        
        // Postcode
        if (isset($fields['billing']['billing_postcode'])) {
            $fields['billing']['billing_postcode']['type'] = 'hidden';
            $fields['billing']['billing_postcode']['required'] = false;
            $fields['billing']['billing_postcode']['priority'] = 42;
        }
        
        // Phone - IMPORTANT: hidden, fără required
        if (isset($fields['billing']['billing_phone'])) {
            $fields['billing']['billing_phone']['type'] = 'hidden';
            $fields['billing']['billing_phone']['required'] = false;
            $fields['billing']['billing_phone']['priority'] = 50;
        }
        
        // Email - IMPORTANT: hidden, fără required
        if (isset($fields['billing']['billing_email'])) {
            $fields['billing']['billing_email']['type'] = 'hidden';
            $fields['billing']['billing_email']['required'] = false;
            $fields['billing']['billing_email']['priority'] = 51;
        }
        
        // =============================================
        // CÂMPURI CUSTOM (PJ/PF)
        // =============================================
        
        $fields['billing']['billing_cui'] = [
            'type'     => 'hidden',
            'label'    => 'CUI',
            'required' => false,
            'priority' => 60,
        ];
        
        $fields['billing']['billing_j'] = [
            'type'     => 'hidden',
            'label'    => 'Nr. Reg. Comerțului',
            'required' => false,
            'priority' => 61,
        ];
        
        $fields['billing']['billing_iban'] = [
            'type'     => 'hidden',
            'label'    => 'IBAN',
            'required' => false,
            'priority' => 62,
        ];
        
        $fields['billing']['billing_bank'] = [
            'type'     => 'hidden',
            'label'    => 'Banca',
            'required' => false,
            'priority' => 63,
        ];
        
        $fields['billing']['billing_cnp'] = [
            'type'     => 'hidden',
            'label'    => 'CNP',
            'required' => false,
            'priority' => 64,
        ];
        
        // =============================================
        // SHIPPING FIELDS - LA FEL, HIDDEN
        // =============================================
        
        if (isset($fields['shipping'])) {
            foreach ($fields['shipping'] as $key => $field) {
                $fields['shipping'][$key]['required'] = false;
            }
        }
        
        return $fields;
    }
    
    /**
     * Customizează câmpurile billing separate
     */
    public function webgsm_customize_billing_fields($fields) {
        
        // Face toate câmpurile non-required
        foreach ($fields as $key => $field) {
            $fields[$key]['required'] = false;
            
            // Elimină clasa validate-required
            if (isset($fields[$key]['class']) && is_array($fields[$key]['class'])) {
                $fields[$key]['class'] = array_diff($fields[$key]['class'], ['validate-required']);
            }
        }
        
        // Adaugă câmpurile custom dacă nu există
        if (!isset($fields['billing_cui'])) {
            $fields['billing_cui'] = [
                'label'    => 'CUI',
                'required' => false,
                'class'    => ['form-row-wide'],
                'priority' => 60,
            ];
        }
        
        if (!isset($fields['billing_j'])) {
            $fields['billing_j'] = [
                'label'    => 'Nr. Reg. Comerțului',
                'required' => false,
                'class'    => ['form-row-wide'],
                'priority' => 61,
            ];
        }
        
        if (!isset($fields['billing_iban'])) {
            $fields['billing_iban'] = [
                'label'    => 'IBAN',
                'required' => false,
                'class'    => ['form-row-wide'],
                'priority' => 62,
            ];
        }
        
        if (!isset($fields['billing_bank'])) {
            $fields['billing_bank'] = [
                'label'    => 'Banca',
                'required' => false,
                'class'    => ['form-row-wide'],
                'priority' => 63,
            ];
        }
        
        if (!isset($fields['billing_cnp'])) {
            $fields['billing_cnp'] = [
                'label'    => 'CNP',
                'required' => false,
                'class'    => ['form-row-wide'],
                'priority' => 64,
            ];
        }
        
        return $fields;
    }
}

// Inițializare
new WebGSM_Checkout_Fields();

// =============================================
// FUNCȚII HELPER GLOBALE
// =============================================

/**
 * Lista județelor din România
 */
function webgsm_get_romania_states() {
    return [
        'AB' => 'Alba',
        'AR' => 'Arad',
        'AG' => 'Argeș',
        'BC' => 'Bacău',
        'BH' => 'Bihor',
        'BN' => 'Bistrița-Năsăud',
        'BT' => 'Botoșani',
        'BV' => 'Brașov',
        'BR' => 'Brăila',
        'B'  => 'București',
        'BZ' => 'Buzău',
        'CS' => 'Caraș-Severin',
        'CL' => 'Călărași',
        'CJ' => 'Cluj',
        'CT' => 'Constanța',
        'CV' => 'Covasna',
        'DB' => 'Dâmbovița',
        'DJ' => 'Dolj',
        'GL' => 'Galați',
        'GR' => 'Giurgiu',
        'GJ' => 'Gorj',
        'HR' => 'Harghita',
        'HD' => 'Hunedoara',
        'IL' => 'Ialomița',
        'IS' => 'Iași',
        'IF' => 'Ilfov',
        'MM' => 'Maramureș',
        'MH' => 'Mehedinți',
        'MS' => 'Mureș',
        'NT' => 'Neamț',
        'OT' => 'Olt',
        'PH' => 'Prahova',
        'SM' => 'Satu Mare',
        'SJ' => 'Sălaj',
        'SB' => 'Sibiu',
        'SV' => 'Suceava',
        'TR' => 'Teleorman',
        'TM' => 'Timiș',
        'TL' => 'Tulcea',
        'VS' => 'Vaslui',
        'VL' => 'Vâlcea',
        'VN' => 'Vrancea',
    ];
}
