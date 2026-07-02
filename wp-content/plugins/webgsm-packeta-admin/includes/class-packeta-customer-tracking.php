<?php
if (!defined('ABSPATH')) {
    exit;
}

class WebGSM_Packeta_Customer_Tracking {

    public function __construct() {
        add_action('woocommerce_order_details_after_order_table', [$this, 'render_order_tracking'], 15);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function enqueue_assets(): void {
        if (!function_exists('is_account_page') || !is_account_page()) {
            return;
        }
        wp_enqueue_style(
            'webgsm-packeta-customer-tracking',
            WEBGSM_PACKETA_URL . 'public/css/customer-tracking.css',
            [],
            WEBGSM_PACKETA_VERSION
        );
    }

  /**
     * @param int|\WC_Order $order
     */
    public function render_order_tracking($order): void {
        if (!is_user_logged_in()) {
            return;
        }
        $wc_order = $order instanceof \WC_Order ? $order : wc_get_order($order);
        if (!$wc_order instanceof \WC_Order) {
            return;
        }
        if ((int) get_current_user_id() !== (int) $wc_order->get_customer_id()) {
            return;
        }

        $tracking = self::get_tracking_for_order((int) $wc_order->get_id(), true);
        if ($tracking === null) {
            return;
        }

        self::render_tracking_box($tracking);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function get_tracking_for_order(int $order_id, bool $allow_sync = false): ?array {
        $row = WebGSM_Packeta_Awb_Repository::get_by_wc_order_id($order_id);
        if (!$row) {
            $row = self::import_from_packetery_order($order_id);
        }

        if (!$row) {
            return null;
        }

        $courier = trim((string) ($row['courier_number'] ?? ''));
        if ($courier === '') {
            return null;
        }

        if ($allow_sync && empty($row['is_final']) && self::should_refresh_row($row)) {
            self::refresh_row_status((string) ($row['packet_id'] ?? ''));
            $fresh = WebGSM_Packeta_Awb_Repository::get_by_packet_id((string) ($row['packet_id'] ?? ''));
            if (is_array($fresh)) {
                $row = $fresh;
            }
        }

        return self::present_row($row);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function present_row(array $row): array {
        $step = (int) ($row['progress_step'] ?? 0);
        $is_problem = !empty($row['is_problem']);
        $is_final = !empty($row['is_final']);
        $code_text = (string) ($row['status_code_text'] ?? '');
        $carrier_name = (string) ($row['carrier_name'] ?? '');
        $courier = trim((string) ($row['courier_number'] ?? ''));
        $slug = WebGSM_Packeta_Carrier_Tracking::slug_from_carrier_name($carrier_name);
        if ($slug === 'unknown' && !empty($row['carrier_slug'])) {
            $slug = (string) $row['carrier_slug'];
        }

        $label = (string) ($row['status_text'] ?? '');
        if ($label === '' || stripos($label, 'packeta') !== false || stripos($label, 'packet has') !== false) {
            $label = WebGSM_Packeta_Status_Mapper::step_label_ro($step, $code_text);
        }

        return [
            'courier_number' => $courier,
            'carrier_name' => $carrier_name !== '' ? $carrier_name : WebGSM_Packeta_Carrier_Tracking::display_name($slug),
            'carrier_slug' => $slug,
            'tracking_url' => WebGSM_Packeta_Carrier_Tracking::tracking_url($slug, $courier),
            'status_label' => $label,
            'progress_step' => $step,
            'progress_percent' => (int) ($row['progress_percent'] ?? 0),
            'is_final' => $is_final,
            'is_problem' => $is_problem,
            'updated_at' => (string) ($row['updated_at'] ?? ''),
            'steps' => WebGSM_Packeta_Status_Mapper::STEPS,
        ];
    }

    /**
     * @param array<string, mixed> $tracking
     */
    public static function render_tracking_box(array $tracking): void {
        $steps = $tracking['steps'] ?? WebGSM_Packeta_Status_Mapper::STEPS;
        $step = (int) ($tracking['progress_step'] ?? 0);
        $percent = (int) ($tracking['progress_percent'] ?? 0);
        $is_problem = !empty($tracking['is_problem']);
        $is_delivered = !empty($tracking['is_final']) && !$is_problem;
        $fill_color = WebGSM_Packeta_Status_Mapper::step_color($step, $is_problem, $is_delivered);
        $tracking_url = (string) ($tracking['tracking_url'] ?? '');
        $carrier = (string) ($tracking['carrier_name'] ?? 'Curier');
        $awb = (string) ($tracking['courier_number'] ?? '');
        $updated = (string) ($tracking['updated_at'] ?? '');

        include WEBGSM_PACKETA_PATH . 'public/views/order-tracking.php';
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function import_from_packetery_order(int $order_id): ?array {
        global $wpdb;
        $table = $wpdb->prefix . 'packetery_order';
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) {
            return null;
        }

        $data = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM `{$table}` WHERE `id` = %d", $order_id),
            ARRAY_A
        );
        if (!is_array($data)) {
            return null;
        }

        $packet_id = trim((string) ($data['packet_id'] ?? ''));
        $courier = trim((string) ($data['carrier_number'] ?? ''));
        if ($packet_id === '' || $courier === '') {
            return null;
        }

        $carrier_id = (string) ($data['carrier_id'] ?? '');
        $carrier_name = self::carrier_title_from_id($carrier_id);
        $slug = WebGSM_Packeta_Carrier_Tracking::slug_from_carrier_id($carrier_id);
        $code_text = strtolower(str_replace(' ', '_', (string) ($data['packet_status'] ?? 'received data')));
        $code_text = str_replace('_', ' ', $code_text);
        $progress = WebGSM_Packeta_Status_Mapper::from_api_status(0, $code_text, '', true);

        WebGSM_Packeta_Awb_Repository::upsert([
            'packet_id' => preg_replace('/\D/', '', $packet_id) ?: $packet_id,
            'wc_order_id' => $order_id,
            'order_ref' => (string) $order_id,
            'carrier_name' => $carrier_name,
            'courier_number' => $courier,
            'status_code_text' => $code_text,
            'status_text' => $progress['label'],
            'progress_step' => (int) $progress['step'],
            'progress_percent' => (int) $progress['percent'],
            'is_final' => !empty($progress['is_final']),
            'is_problem' => !empty($progress['is_problem']),
        ]);

        return WebGSM_Packeta_Awb_Repository::get_by_wc_order_id($order_id);
    }

    private static function carrier_title_from_id(string $carrier_id): string {
        foreach (WebGSM_Packeta_Carriers::get_checkout_carriers() as $carrier) {
            if ((string) ($carrier['carrier_id'] ?? '') === $carrier_id) {
                return (string) ($carrier['title'] ?? '');
            }
        }

        $slug = WebGSM_Packeta_Carrier_Tracking::slug_from_carrier_id($carrier_id);

        return WebGSM_Packeta_Carrier_Tracking::display_name($slug);
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function should_refresh_row(array $row): bool {
        $updated = strtotime((string) ($row['updated_at'] ?? ''));
        if ($updated === false) {
            return true;
        }

        return (time() - $updated) > 2 * HOUR_IN_SECONDS;
    }

    private static function refresh_row_status(string $packet_id): void {
        if ($packet_id === '') {
            return;
        }
        $settings = WebGSM_Packeta_Config::get_effective_settings();
        if (($settings['api_password'] ?? '') === '') {
            return;
        }

        $client = new WebGSM_Packeta_Xml_Client(
            $settings['api_password'],
            $settings['rest_url'] !== '' ? $settings['rest_url'] : WebGSM_Packeta_Config::default_rest_url()
        );
        WebGSM_Packeta_Awb_Sync::sync_status($packet_id, $client);
    }
}
