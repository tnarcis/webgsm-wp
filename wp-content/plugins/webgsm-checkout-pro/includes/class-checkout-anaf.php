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

        // Rate limit (shared with theme handler)
        $user_ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown';
        $transient_key = 'anaf_rate_limit_' . md5($user_ip);
        $request_count = (int) get_transient($transient_key);
        if ($request_count >= 10) {
            wp_send_json_error('Prea multe cereri. Te rugăm să aștepți 1 minut.');
        }
        set_transient($transient_key, $request_count + 1, 60);
        
        $cui = isset($_POST['cui']) ? sanitize_text_field(wp_unslash($_POST['cui'])) : '';
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
        if (function_exists('webgsm_query_anaf')) {
            $result = webgsm_query_anaf($cui);
            if (is_wp_error($result)) {
                return $result;
            }
            $result['state_code'] = $this->get_state_code($result['county'] ?? '');
            return $result;
        }

        return new WP_Error('anaf_error', 'Serviciul ANAF nu este disponibil');
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
