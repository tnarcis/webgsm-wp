<?php
/**
 * Interogare ANAF PlatitorTvaRest v9 (sync) cu cache transient.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @param string $cui Digits only CUI
 * @return array|WP_Error
 */
function webgsm_query_anaf($cui) {
    $cui = preg_replace('/[^0-9]/', '', (string) $cui);
    if ($cui === '' || strlen($cui) < 2 || strlen($cui) > 10) {
        return new WP_Error('anaf_invalid', 'CUI invalid');
    }

    $cache_key = 'webgsm_anaf_v9_' . $cui;
    $cached = get_transient($cache_key);
    if (is_array($cached)) {
        return $cached;
    }

    $url = 'https://webservicesp.anaf.ro/api/PlatitorTvaRest/v9/tva';
    $body = wp_json_encode(array(
        array(
            'cui'  => (int) $cui,
            'data' => gmdate('Y-m-d'),
        ),
    ));

    $response = wp_remote_post($url, array(
        'timeout' => 20,
        'headers' => array('Content-Type' => 'application/json'),
        'body'    => $body,
    ));

    if (is_wp_error($response)) {
        return new WP_Error('anaf_error', 'Eroare la conectarea cu ANAF');
    }

    $code = wp_remote_retrieve_response_code($response);
    $data = json_decode(wp_remote_retrieve_body($response), true);

    if ($code < 200 || $code >= 300 || !is_array($data)) {
        return new WP_Error('anaf_error', 'Răspuns invalid de la ANAF');
    }

    // v9 sync: found[0] similar to async
    $entry = null;
    if (isset($data['found'][0]) && is_array($data['found'][0])) {
        $entry = $data['found'][0];
    } elseif (isset($data[0]['date_generale'])) {
        $entry = $data[0];
    }

    if (!$entry || empty($entry['date_generale'])) {
        return new WP_Error('anaf_not_found', 'CUI negăsit în baza ANAF');
    }

    $parsed = webgsm_parse_anaf_entry($entry, $cui);
    set_transient($cache_key, $parsed, 12 * HOUR_IN_SECONDS);
    return $parsed;
}

/**
 * @param array  $data ANAF entry
 * @param string $cui
 * @return array
 */
function webgsm_parse_anaf_entry($data, $cui) {
    $general = isset($data['date_generale']) ? $data['date_generale'] : array();
    $address = isset($data['adresa_sediu_social']) ? $data['adresa_sediu_social'] : array();
    $tva     = isset($data['inregistrare_scop_Tva']) ? $data['inregistrare_scop_Tva'] : array();

    $street = trim(($address['sdenumire_Strada'] ?? '') . ' ' . ($address['snumar_Strada'] ?? ''));
    $details = $address['sdetalii_Adresa'] ?? '';
    $city = $address['sdenumire_Localitate'] ?? '';
    $county = $address['sdenumire_Judet'] ?? '';

    $city = preg_replace('/^(Mun\.|Municipiul|Or\.|Oraș|Com\.|Comuna)\s*/iu', '', $city);

    $full_address = $street;
    if ($details !== '') {
        $full_address .= ', ' . $details;
    }

    $is_tva = isset($tva['scpTVA']) && ((int) $tva['scpTVA'] === 1 || $tva['scpTVA'] === true);

    return array(
        'name'       => $general['denumire'] ?? '',
        'cui'        => $is_tva ? ('RO' . $cui) : $cui,
        'cui_raw'    => $cui,
        'j'          => $general['nrRegCom'] ?? '',
        'address'    => $full_address,
        'city'       => $city,
        'county'     => $county,
        'is_tva'     => $is_tva,
    );
}
