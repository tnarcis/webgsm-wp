<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Comenzi checkout WooCommerce → API Packeta: aceleași câmpuri ca la AWB manual (eshop per curier, province, houseNumber etc.).
 */
class WebGSM_Packeta_Checkout_Bridge {

    public function __construct() {
        add_filter('packeta_create_packet', [$this, 'enrich_create_packet'], 20, 1);
        add_action('woocommerce_checkout_update_order_meta', [$this, 'patch_packetery_order_address'], 20, 1);
        add_action('packetery_auto_submission_handle_event', [$this, 'after_auto_submission'], 30, 2);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function enrich_create_packet(array $data): array {
        $settings = WebGSM_Packeta_Config::get_effective_settings();
        $carrier_id = isset($data['addressId']) ? preg_replace('/\D/', '', (string) $data['addressId']) : '';
        $carrier_id = $carrier_id ?? '';

        if ($carrier_id !== '' && !empty($settings['sender_base'])) {
            $eshop = WebGSM_Packeta_Sender_Mapper::eshop_for_carrier((string) $settings['sender_base'], $carrier_id);
            if ($eshop !== '') {
                $data['eshop'] = $eshop;
            }
        }

        $order_number = isset($data['number']) ? (string) $data['number'] : '';
        $wc_order_id = WebGSM_Packeta_Awb_Repository::resolve_wc_order_id($order_number);
        if ($wc_order_id < 1) {
            $wc_order_id = (int) self::packetery_order_id_from_number($order_number);
        }

        $wc_order = $wc_order_id > 0 && function_exists('wc_get_order') ? wc_get_order($wc_order_id) : null;
        if (!$wc_order instanceof \WC_Order) {
            return $data;
        }

        $company = trim($wc_order->get_billing_company());
        if ($company !== '' && empty($data['company'])) {
            $data['company'] = $company;
        }

        if ($carrier_id !== '' && self::carrier_is_pickup($carrier_id)) {
            if (empty($data['carrierPickupPoint'])) {
                $point_id = self::get_packetery_point_id($wc_order_id);
                if ($point_id !== '') {
                    $data['carrierPickupPoint'] = $point_id;
                }
            }

            return $data;
        }

        return $this->apply_home_delivery_fields($data, $wc_order);
    }

    public function patch_packetery_order_address(int $order_id): void {
        if ($order_id < 1 || !function_exists('wc_get_order')) {
            return;
        }

        $wc_order = wc_get_order($order_id);
        if (!$wc_order instanceof \WC_Order || !self::order_uses_packeta($wc_order)) {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'packetery_order';
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) {
            return;
        }

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT carrier_id, address_validated, delivery_address FROM `{$table}` WHERE `id` = %d", $order_id),
            ARRAY_A
        );
        if (!is_array($row)) {
            return;
        }

        $carrier_id = preg_replace('/\D/', '', (string) ($row['carrier_id'] ?? '')) ?? '';
        if ($carrier_id === '' || self::carrier_is_pickup($carrier_id)) {
            return;
        }

        if (!empty($row['address_validated']) && !empty($row['delivery_address'])) {
            $decoded = json_decode((string) $row['delivery_address'], true);
            if (is_array($decoded) && !empty($decoded['county']) && !empty($decoded['houseNumber'])) {
                return;
            }
        }

        $delivery = self::delivery_address_from_wc_order($wc_order);
        if ($delivery['street'] === '' || $delivery['city'] === '') {
            return;
        }

        $wpdb->update(
            $table,
            [
                'delivery_address' => wp_json_encode($delivery),
                'address_validated' => 1,
            ],
            ['id' => $order_id],
            ['%s', '%d'],
            ['%d']
        );
    }

    /**
     * @param string|mixed $event
     * @param int|mixed $order_id
     */
    public function after_auto_submission($event, $order_id): void {
        unset($event);
        if (!is_int($order_id) || $order_id < 1) {
            return;
        }

        self::sync_awb_from_packetery_order($order_id);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function apply_home_delivery_fields(array $data, \WC_Order $wc_order): array {
        $state = $wc_order->get_shipping_state() ?: $wc_order->get_billing_state();
        $province = WebGSM_Packeta_Config::ro_province_for_api((string) $state);
        if ($province !== '' && empty($data['province'])) {
            $data['province'] = $province;
        }

        $zip = trim($wc_order->get_shipping_postcode() ?: $wc_order->get_billing_postcode());
        if ($zip !== '' && empty($data['zip'])) {
            $data['zip'] = $zip;
        }

        $city = trim($wc_order->get_shipping_city() ?: $wc_order->get_billing_city());
        if ($city !== '' && empty($data['city'])) {
            $data['city'] = $city;
        }

        $address_1 = trim($wc_order->get_shipping_address_1() ?: $wc_order->get_billing_address_1());
        $street_source = !empty($data['street']) ? trim((string) $data['street']) : $address_1;
        $parsed = self::parse_ro_address($street_source);

        if (empty($data['street'])) {
            $data['street'] = $parsed['street'] !== '' ? $parsed['street'] : $street_source;
        }
        if (empty($data['houseNumber']) && $parsed['houseNumber'] !== '') {
            $data['houseNumber'] = $parsed['houseNumber'];
        }

        return $data;
    }

    /**
     * @return array{street: string, city: string, zip: string, houseNumber: string|null, county: string|null, longitude: null, latitude: null}
     */
    public static function delivery_address_from_wc_order(\WC_Order $wc_order): array {
        $address_1 = trim($wc_order->get_shipping_address_1() ?: $wc_order->get_billing_address_1());
        $parsed = self::parse_ro_address($address_1);
        $state = $wc_order->get_shipping_state() ?: $wc_order->get_billing_state();
        $county = WebGSM_Packeta_Config::ro_province_for_api((string) $state);

        return [
            'street' => $parsed['street'],
            'city' => trim($wc_order->get_shipping_city() ?: $wc_order->get_billing_city()),
            'zip' => trim($wc_order->get_shipping_postcode() ?: $wc_order->get_billing_postcode()),
            'houseNumber' => $parsed['houseNumber'] !== '' ? $parsed['houseNumber'] : null,
            'county' => $county !== '' ? $county : null,
            'longitude' => null,
            'latitude' => null,
        ];
    }

    /**
     * @return array{street: string, houseNumber: string}
     */
    public static function parse_ro_address(string $address_1): array {
        $address_1 = trim(preg_replace('/\s+/u', ' ', $address_1) ?? '');
        if ($address_1 === '') {
            return ['street' => '', 'houseNumber' => ''];
        }

        if (preg_match('/^(.*?)(?:,?\s*(?:nr\.?|număr|numar|nro?\.?|#)\s*([\w\-\/]+))$/iu', $address_1, $matches)) {
            return [
                'street' => trim($matches[1]),
                'houseNumber' => trim($matches[2]),
            ];
        }

        if (preg_match('/^(.*?)\s+(\d+[\w\-\/]*)$/u', $address_1, $matches)) {
            return [
                'street' => trim($matches[1]),
                'houseNumber' => trim($matches[2]),
            ];
        }

        return ['street' => $address_1, 'houseNumber' => ''];
    }

    public static function sync_awb_from_packetery_order(int $order_id): void {
        global $wpdb;
        $table = $wpdb->prefix . 'packetery_order';
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) {
            return;
        }

        $data = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM `{$table}` WHERE `id` = %d", $order_id),
            ARRAY_A
        );
        if (!is_array($data)) {
            return;
        }

        $packet_id = trim((string) ($data['packet_id'] ?? ''));
        if ($packet_id === '') {
            return;
        }

        $courier = trim((string) ($data['carrier_number'] ?? ''));
        $carrier_id = (string) ($data['carrier_id'] ?? '');
        $carrier_name = '';
        foreach (WebGSM_Packeta_Carriers::get_checkout_carriers() as $carrier) {
            if ((string) ($carrier['carrier_id'] ?? '') === $carrier_id) {
                $carrier_name = (string) ($carrier['title'] ?? '');
                break;
            }
        }
        if ($carrier_name === '') {
            $slug = WebGSM_Packeta_Carrier_Tracking::slug_from_carrier_id($carrier_id);
            $carrier_name = WebGSM_Packeta_Carrier_Tracking::display_name($slug);
        }

        $wc_order = function_exists('wc_get_order') ? wc_get_order($order_id) : null;
        $recipient = '';
        $phone = '';
        if ($wc_order instanceof \WC_Order) {
            $recipient = trim($wc_order->get_shipping_first_name() . ' ' . $wc_order->get_shipping_last_name());
            if ($recipient === '') {
                $recipient = trim($wc_order->get_billing_first_name() . ' ' . $wc_order->get_billing_last_name());
            }
            $phone = trim($wc_order->get_billing_phone());
        }

        $flow_type = self::carrier_is_pickup($carrier_id) ? 'box' : 'home';
        $code_text = strtolower(str_replace(' ', '_', (string) ($data['packet_status'] ?? 'received data')));
        $code_text = str_replace('_', ' ', $code_text);
        $progress = WebGSM_Packeta_Status_Mapper::from_api_status(0, $code_text, '', $courier !== '');

        WebGSM_Packeta_Awb_Repository::upsert([
            'packet_id' => preg_replace('/\D/', '', $packet_id) ?: $packet_id,
            'wc_order_id' => $order_id,
            'order_ref' => (string) $order_id,
            'carrier_name' => $carrier_name,
            'recipient_name' => $recipient,
            'recipient_phone' => $phone,
            'flow_type' => $flow_type,
            'courier_number' => $courier,
            'status_code_text' => $code_text,
            'status_text' => $progress['label'],
            'progress_step' => (int) $progress['step'],
            'progress_percent' => (int) $progress['percent'],
            'is_final' => !empty($progress['is_final']),
            'is_problem' => !empty($progress['is_problem']),
        ]);

        if ($courier === '' && ($settings = WebGSM_Packeta_Config::get_effective_settings()) && !empty($settings['api_password'])) {
            $client = new WebGSM_Packeta_Xml_Client(
                $settings['api_password'],
                $settings['rest_url'] !== '' ? $settings['rest_url'] : WebGSM_Packeta_Config::default_rest_url()
            );
            WebGSM_Packeta_Awb_Sync::sync_status(preg_replace('/\D/', '', $packet_id) ?: $packet_id, $client);
        }
    }

    private static function order_uses_packeta(\WC_Order $order): bool {
        foreach ($order->get_shipping_methods() as $method) {
            $method_id = strtolower((string) $method->get_method_id());
            if (strpos($method_id, 'packeta') !== false) {
                return true;
            }
        }

        return false;
    }

    private static function carrier_is_pickup(string $carrier_id): bool {
        global $wpdb;
        $carrier_id = preg_replace('/\D/', '', $carrier_id) ?? '';
        if ($carrier_id === '') {
            return false;
        }

        $table = $wpdb->prefix . 'packetery_carrier';
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) {
            return false;
        }

        $is_pickup = $wpdb->get_var(
            $wpdb->prepare("SELECT `is_pickup_points` FROM `{$table}` WHERE `id` = %d", (int) $carrier_id)
        );

        return (int) $is_pickup === 1;
    }

    private static function get_packetery_point_id(int $order_id): string {
        global $wpdb;
        $table = $wpdb->prefix . 'packetery_order';
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) {
            return '';
        }

        $point_id = $wpdb->get_var(
            $wpdb->prepare("SELECT `point_id` FROM `{$table}` WHERE `id` = %d", $order_id)
        );

        return trim((string) $point_id);
    }

    private static function packetery_order_id_from_number(string $number): int {
        $number = trim($number);
        if ($number === '' || !ctype_digit($number)) {
            return 0;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'packetery_order';
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) {
            return 0;
        }

        $exists = $wpdb->get_var(
            $wpdb->prepare("SELECT `id` FROM `{$table}` WHERE `id` = %d", (int) $number)
        );

        return $exists ? (int) $exists : 0;
    }
}
