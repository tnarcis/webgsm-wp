<?php
/**
 * Helper unificat pentru date fiscale pe comandă.
 * Citește întâi cheile checkout-pro (noi), apoi fallback pe cheile legacy (facturare-pj).
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @param WC_Order|int $order
 * @return array{
 *   customer_type: string,
 *   is_pj: bool,
 *   company: string,
 *   cui: string,
 *   reg_com: string,
 *   iban: string,
 *   bank: string,
 *   vat_payer: bool
 * }
 */
function webgsm_get_order_fiscal_data($order) {
    if (is_numeric($order)) {
        $order = wc_get_order($order);
    }

    $empty = array(
        'customer_type' => 'pf',
        'is_pj'         => false,
        'company'       => '',
        'cui'           => '',
        'reg_com'       => '',
        'iban'          => '',
        'bank'          => '',
        'vat_payer'     => false,
    );

    if (!$order || !is_a($order, 'WC_Order')) {
        return $empty;
    }

    $order_id = $order->get_id();

    $meta = function ($key) use ($order, $order_id) {
        $val = $order->get_meta($key);
        if ($val === '' || $val === null || $val === false) {
            $val = get_post_meta($order_id, $key, true);
        }
        return is_string($val) ? trim($val) : $val;
    };

    // Tip client: checkout-pro (_customer_type) sau legacy (_tip_facturare)
    $customer_type = $meta('_customer_type');
    if ($customer_type === '' || $customer_type === null || $customer_type === false) {
        $customer_type = $meta('_billing_customer_type');
    }
    if ($customer_type === '' || $customer_type === null || $customer_type === false) {
        $customer_type = $meta('_tip_facturare');
    }
    if ($customer_type !== 'pj' && $customer_type !== 'pf') {
        // Heuristică: CUI prezent => PJ
        $has_cui = $meta('_billing_cui') || $meta('_billing_cif');
        $customer_type = $has_cui ? 'pj' : 'pf';
    }

    $is_pj = ($customer_type === 'pj');

    $company = '';
    $cui     = '';
    $reg_com = '';
    $iban    = '';
    $bank    = '';

    if ($is_pj) {
        // Company name: checkout-pro billing_company, legacy _billing_company_name, _company_data
        $company_data = $meta('_company_data');
        if (is_array($company_data)) {
            $company = isset($company_data['name']) ? (string) $company_data['name'] : '';
            $cui     = isset($company_data['cui']) ? (string) $company_data['cui'] : '';
            $reg_com = isset($company_data['j']) ? (string) $company_data['j'] : (isset($company_data['reg']) ? (string) $company_data['reg'] : '');
            $iban    = isset($company_data['iban']) ? (string) $company_data['iban'] : '';
            $bank    = isset($company_data['bank']) ? (string) $company_data['bank'] : '';
        }

        if ($company === '') {
            $company = (string) $meta('_billing_company_name');
        }
        if ($company === '') {
            $company = (string) $order->get_billing_company();
        }

        // CUI: checkout-pro _billing_cui, legacy _billing_cif
        if ($cui === '') {
            $cui = (string) $meta('_billing_cui');
        }
        if ($cui === '') {
            $cui = (string) $meta('_billing_cif');
        }

        // Reg. Com.: checkout-pro _billing_j, legacy _billing_reg_com
        if ($reg_com === '') {
            $reg_com = (string) $meta('_billing_j');
        }
        if ($reg_com === '') {
            $reg_com = (string) $meta('_billing_reg_com');
        }

        if ($iban === '') {
            $iban = (string) $meta('_billing_iban');
        }
        if ($bank === '') {
            $bank = (string) $meta('_billing_bank');
        }
    } else {
        $company = (string) $order->get_billing_company();
    }

    // VAT payer: meta explicită > prefix RO pe CUI > false (nu deduce doar din prezența CUI)
    $vat_meta = $meta('_billing_vat_payer');
    if ($vat_meta === '1' || $vat_meta === 1 || $vat_meta === true || $vat_meta === 'yes') {
        $vat_payer = true;
    } elseif ($vat_meta === '0' || $vat_meta === 0 || $vat_meta === 'no') {
        $vat_payer = false;
    } else {
        $vat_payer = (bool) preg_match('/^RO/i', $cui);
    }

    return array(
        'customer_type' => $customer_type,
        'is_pj'         => $is_pj,
        'company'       => $company,
        'cui'           => $cui,
        'reg_com'       => $reg_com,
        'iban'          => $iban,
        'bank'          => $bank,
        'vat_payer'     => $vat_payer,
    );
}
