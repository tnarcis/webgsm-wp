<?php
if (!defined('ABSPATH')) exit;

class WebGSM_Checkout_ANAF {
    
    public function __construct() {
        add_action('wp_ajax_webgsm_search_anaf', [$this, 'ajax_search_anaf']);
        add_action('wp_ajax_nopriv_webgsm_search_anaf', [$this, 'ajax_search_anaf']);
    }
    
    public function ajax_search_anaf() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'webgsm_nonce')) {
            wp_send_json_error('Sesiune expirată');
        }
        
        $cui = isset($_POST['cui']) ? sanitize_text_field($_POST['cui']) : '';
        $cui = preg_replace('/[^0-9]/', '', $cui);
        
        if (empty($cui) || strlen($cui) < 2 || strlen($cui) > 10) {
            wp_send_json_error('CUI invalid');
        }
        
        $result = $this->query_anaf($cui);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success($result);
    }
    
    private function query_anaf($cui) {
        $url = 'https://webservicesp.anaf.ro/AsynchWebService/api/v8/ws/tva';
        
        $body = json_encode([
            ['cui' => intval($cui), 'data' => date('Y-m-d')]
        ]);
        
        $response = wp_remote_post($url, [
            'timeout' => 30,
            'headers' => ['Content-Type' => 'application/json'],
            'body' => $body,
        ]);
        
        if (is_wp_error($response)) {
            return new WP_Error('anaf_error', 'Eroare la conectarea cu ANAF');
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!isset($data['correlationId'])) {
            return new WP_Error('anaf_error', 'ANAF nu a returnat un ID valid');
        }
        
        sleep(2);
        
        $result_url = 'https://webservicesp.anaf.ro/AsynchWebService/api/v8/ws/tva?id=' . $data['correlationId'];
        $result_response = wp_remote_get($result_url, ['timeout' => 30]);
        
        if (is_wp_error($result_response)) {
            return new WP_Error('anaf_error', 'Eroare la preluarea rezultatului');
        }
        
        $result = json_decode(wp_remote_retrieve_body($result_response), true);
        
        if (isset($result['found'][0]['date_generale'])) {
            return $this->parse_anaf_result($result['found'][0], $cui);
        }
        
        return new WP_Error('anaf_not_found', 'CUI negăsit în baza ANAF');
    }
    
    private function parse_anaf_result($data, $cui) {
        $general = $data['date_generale'] ?? [];
        $address = $data['adresa_sediu_social'] ?? [];
        $tva = $data['inregistrare_scop_Tva'] ?? [];
        
        $street = trim(($address['sdenumire_Strada'] ?? '') . ' ' . ($address['snumar_Strada'] ?? ''));
        $details = $address['sdetalii_Adresa'] ?? '';
        $city = $address['sdenumire_Localitate'] ?? '';
        $county = $address['sdenumire_Judet'] ?? '';
        
        $city = preg_replace('/^(Mun\.|Municipiul|Or\.|Oraș|Com\.|Comuna)\s*/i', '', $city);
        
        $full_address = $street;
        if (!empty($details)) {
            $full_address .= ', ' . $details;
        }
        
        $is_tva = isset($tva['scpTVA']) && $tva['scpTVA'] == 1;
        
        return [
            'name' => $general['denumire'] ?? '',
            'cui' => $is_tva ? 'RO' . $cui : $cui,
            'cui_raw' => $cui,
            'j' => $general['nrRegCom'] ?? '',
            'address' => $full_address,
            'city' => $city,
            'county' => $county,
            'state_code' => $this->get_state_code($county),
            'is_tva' => $is_tva,
        ];
    }
    
    private function get_state_code($county) {
        $county = strtoupper($this->remove_diacritics($county));
        
        $map = [
            'ALBA' => 'AB', 'ARAD' => 'AR', 'ARGES' => 'AG', 'BACAU' => 'BC',
            'BIHOR' => 'BH', 'BISTRITA-NASAUD' => 'BN', 'BOTOSANI' => 'BT',
            'BRASOV' => 'BV', 'BRAILA' => 'BR', 'BUCURESTI' => 'B', 'BUZAU' => 'BZ',
            'CARAS-SEVERIN' => 'CS', 'CALARASI' => 'CL', 'CLUJ' => 'CJ',
            'CONSTANTA' => 'CT', 'COVASNA' => 'CV', 'DAMBOVITA' => 'DB',
            'DOLJ' => 'DJ', 'GALATI' => 'GL', 'GIURGIU' => 'GR', 'GORJ' => 'GJ',
            'HARGHITA' => 'HR', 'HUNEDOARA' => 'HD', 'IALOMITA' => 'IL',
            'IASI' => 'IS', 'ILFOV' => 'IF', 'MARAMURES' => 'MM', 'MEHEDINTI' => 'MH',
            'MURES' => 'MS', 'NEAMT' => 'NT', 'OLT' => 'OT', 'PRAHOVA' => 'PH',
            'SATU MARE' => 'SM', 'SALAJ' => 'SJ', 'SIBIU' => 'SB', 'SUCEAVA' => 'SV',
            'TELEORMAN' => 'TR', 'TIMIS' => 'TM', 'TULCEA' => 'TL', 'VASLUI' => 'VS',
            'VALCEA' => 'VL', 'VRANCEA' => 'VN',
        ];
        
        foreach ($map as $name => $code) {
            if (strpos($county, $name) !== false) {
                return $code;
            }
        }
        
        return '';
    }
    
    private function remove_diacritics($string) {
        $search = ['ă', 'â', 'î', 'ș', 'ț', 'Ă', 'Â', 'Î', 'Ș', 'Ț', 'ş', 'ţ', 'Ş', 'Ţ'];
        $replace = ['a', 'a', 'i', 's', 't', 'A', 'A', 'I', 'S', 'T', 's', 't', 'S', 'T'];
        return str_replace($search, $replace, $string);
    }
}

new WebGSM_Checkout_ANAF();
