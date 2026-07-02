<?php
if (!defined('ABSPATH')) {
    exit;
}

class WebGSM_Packeta_Admin {

    public function __construct() {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_init', [$this, 'handle_post']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);
        add_action('wp_ajax_webgsm_packeta_refresh_awb_status', [$this, 'ajax_refresh_awb_status']);
        add_action('wp_ajax_webgsm_packeta_refresh_all_awb_statuses', [$this, 'ajax_refresh_all_awb_statuses']);
        add_action('webgsm_packeta_sync_awb_cron', [$this, 'cron_sync_awb_statuses']);
        add_action('init', [$this, 'maybe_schedule_cron']);
    }

    public function maybe_schedule_cron(): void {
        $ver = get_option('webgsm_packeta_cron_schedule_ver', '');
        if ($ver !== WEBGSM_PACKETA_VERSION) {
            wp_clear_scheduled_hook('webgsm_packeta_sync_awb_cron');
            update_option('webgsm_packeta_cron_schedule_ver', WEBGSM_PACKETA_VERSION);
        }
        if (!wp_next_scheduled('webgsm_packeta_sync_awb_cron')) {
            wp_schedule_event(time() + 300, 'webgsm_packeta_2hours', 'webgsm_packeta_sync_awb_cron');
        }
    }

    public function cron_sync_awb_statuses(): void {
        $settings = self::get_settings();
        if ($settings['api_password'] === '') {
            return;
        }
        $client = $this->make_client($settings);
        foreach (WebGSM_Packeta_Awb_Repository::list_active_for_sync(30) as $row) {
            $pid = (string) ($row['packet_id'] ?? '');
            if ($pid !== '') {
                WebGSM_Packeta_Awb_Sync::sync_status($pid, $client);
            }
        }
    }

    public function register_menu(): void {
        add_menu_page(
            'Packeta',
            'Packeta',
            'manage_woocommerce',
            'webgsm-packeta',
            [$this, 'render_page'],
            'dashicons-airplane',
            58
        );
    }

    public function enqueue(string $hook): void {
        if ($hook !== 'toplevel_page_webgsm-packeta') {
            return;
        }
        wp_enqueue_style('webgsm-packeta-admin', WEBGSM_PACKETA_URL . 'admin/css/admin.css', [], WEBGSM_PACKETA_VERSION);

        $tab = isset($_GET['tab']) ? sanitize_key((string) $_GET['tab']) : 'settings';

        if ($tab === 'awb_list') {
            wp_enqueue_script(
                'webgsm-packeta-awb-list',
                WEBGSM_PACKETA_URL . 'admin/js/awb-list.js',
                ['jquery'],
                WEBGSM_PACKETA_VERSION,
                true
            );
            wp_localize_script('webgsm-packeta-awb-list', 'webgsmPacketaAwbList', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('webgsm_packeta_awb_list'),
                'pollInterval' => 180000,
            ]);

            return;
        }

        if ($tab !== 'awb') {
            return;
        }

        wp_enqueue_script(
            'packeta-widget-v6',
            'https://widget.packeta.com/v6/www/js/library.js',
            [],
            null,
            true
        );
        wp_enqueue_script(
            'webgsm-packeta-admin',
            WEBGSM_PACKETA_URL . 'admin/js/packeta-admin.js',
            ['jquery', 'packeta-widget-v6'],
            WEBGSM_PACKETA_VERSION,
            true
        );

        $settings = self::get_settings();

        wp_localize_script('webgsm-packeta-admin', 'webgsmPacketaAdmin', [
            'widgetApiKey' => $settings['widget_api_key'],
            'awbDraft' => self::get_awb_form_draft(),
            'i18n' => [
                'needKey' => 'Configurează în Packeta: API password și API key (hartă) în WooCommerce → Packeta.',
                'needPacketaLib' => 'Biblioteca Packeta nu s-a încărcat. Reîncarcă pagina.',
                'selectPoint' => 'Selectează punctul pe hartă.',
                'cancelled' => 'Selecție anulată.',
                'noPointYet' => 'Niciun punct selectat — apasă „Deschide harta Packeta”.',
                'addressIdPickupHelp' => 'Completat automat după selectarea punctului pe hartă.',
                'addressIdHomeHelp' => 'Setat din lista „Curier — livrare la adresă” sau introdus manual (ID Packeta).',
                'formTitlePickup' => '3. Detalii expediție',
                'formTitleHome' => '3. Detalii expediție',
                'mustSelectPoint' => 'Pentru punct fix / Box trebuie să selectezi punctul pe harta Packeta înainte de trimitere.',
                'addressFieldsRequired' => 'Completează strada, orașul, județul, numărul și codul poștal pentru livrarea la adresă.',
                'missingHomeCarrier' => 'Introdu addressId pentru transportatorul de livrare la adresă (din Packeta).',
                'missingHomeProvince' => 'Selectează județul destinatarului.',
                'missingHomeZip' => 'Completează codul poștal (obligatoriu la livrare la adresă în Packeta).',
                'missingHomeHouse' => 'Completează numărul străzii (obligatoriu la livrare la adresă).',
                'parcelValueRequired' => 'Completează valoarea coletului (mai mare ca 0) — obligatoriu pentru asigurare în Packeta.',
            ],
        ]);
    }

    public function handle_post(): void {
        if (!isset($_POST['webgsm_packeta_action']) || !current_user_can('manage_woocommerce')) {
            return;
        }

        check_admin_referer('webgsm_packeta');

        $tab = isset($_POST['tab']) ? sanitize_key((string) $_POST['tab']) : 'settings';
        $settings = self::get_settings();

        switch ($_POST['webgsm_packeta_action']) {
            case 'save_settings':
                $stored = get_option(WEBGSM_PACKETA_OPTION, []);
                if (!is_array($stored)) {
                    $stored = [];
                }
                $rest_url = isset($_POST['rest_url']) ? esc_url_raw(wp_unslash((string) $_POST['rest_url'])) : '';
                if ($rest_url === '') {
                    $rest_url = WebGSM_Packeta_Config::default_rest_url();
                }
                $stored['rest_url'] = $rest_url;
                update_option(WEBGSM_PACKETA_OPTION, $stored);
                $this->redirect_with_notice($tab, 'settings_saved');
                break;

            case 'sync_carrier_prices':
                $sync = WebGSM_Packeta_Carrier_Pricing_Sync::sync_active_carriers(true);
                set_transient(
                    'webgsm_packeta_pricing_sync_' . get_current_user_id(),
                    $sync,
                    300
                );
                $this->redirect_with_notice('settings', empty($sync['errors']) ? 'prices_synced' : 'prices_sync_partial');
                break;

            case 'create_packet':
                $this->store_awb_form_draft_from_post();
                if ($settings['api_password'] === '') {
                    $this->redirect_with_notice($tab, 'no_password');
                    break;
                }
                $v = $this->validate_awb_post_before_api();
                if ($v !== null) {
                    $this->redirect_with_notice($tab, $v);
                    break;
                }
                $client = $this->make_client($settings);
                $attrs = $this->collect_packet_attributes_from_post($settings);
                $is_validate_only = isset($_POST['validate_only']);
                if ($is_validate_only) {
                    $res = $client->packet_attributes_valid($attrs);
                } else {
                    $res = $client->create_packet($attrs);
                }
                if (!empty($res['ok'])) {
                    if (!$is_validate_only) {
                        self::clear_awb_form_draft();
                        $this->save_awb_from_packet_response($res, $attrs);
                    }
                    set_transient(
                        'webgsm_packeta_last_' . get_current_user_id(),
                        [
                            'type' => 'packet',
                            'data' => self::packeta_api_response_for_transient($res),
                            'attrs' => $attrs,
                        ],
                        120
                    );
                    $this->redirect_with_notice($tab, isset($_POST['validate_only']) ? 'validated' : 'packet_ok');
                } else {
                    set_transient(
                        'webgsm_packeta_last_' . get_current_user_id(),
                        ['type' => 'error', 'message' => $res['error'] ?? 'Eroare', 'raw' => $res['raw'] ?? ''],
                        120
                    );
                    $this->redirect_with_notice($tab, 'api_error');
                }
                break;

            case 'create_shipment':
                if ($settings['api_password'] === '') {
                    $this->redirect_with_notice('shipment', 'no_password');
                    break;
                }
                $raw = isset($_POST['packet_ids']) ? wp_unslash((string) $_POST['packet_ids']) : '';
                $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
                $ids = [];
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line !== '') {
                        $ids[] = $line;
                    }
                }
                $custom = isset($_POST['custom_barcode']) ? sanitize_text_field(wp_unslash((string) $_POST['custom_barcode'])) : '';
                $client = $this->make_client($settings);
                $res = $client->create_shipment($ids, $custom);
                if (!empty($res['ok'])) {
                    $this->attach_shipment_to_awb_records($ids, $res);
                    set_transient(
                        'webgsm_packeta_last_' . get_current_user_id(),
                        ['type' => 'shipment', 'data' => self::packeta_api_response_for_transient($res)],
                        120
                    );
                    $this->redirect_with_notice('shipment', 'shipment_ok');
                } else {
                    set_transient(
                        'webgsm_packeta_last_' . get_current_user_id(),
                        ['type' => 'error', 'message' => $res['error'] ?? 'Eroare', 'raw' => $res['raw'] ?? ''],
                        120
                    );
                    $this->redirect_with_notice('shipment', 'api_error');
                }
                break;

            case 'register_awb':
                if ($settings['api_password'] === '') {
                    $this->redirect_with_notice('awb_list', 'no_password');
                    break;
                }
                $pid = isset($_POST['register_packet_id']) ? WebGSM_Packeta_Xml_Client::normalize_packet_id((string) wp_unslash($_POST['register_packet_id'])) : '';
                if ($pid === '') {
                    $this->redirect_with_notice('awb_list', 'missing_packet_id');
                    break;
                }
                $order_ref = isset($_POST['register_order_ref']) ? sanitize_text_field(wp_unslash((string) $_POST['register_order_ref'])) : '';
                $client = $this->make_client($settings);
                $sync = WebGSM_Packeta_Awb_Sync::sync_status($pid, $client);
                if (empty($sync['ok'])) {
                    $message = (string) ($sync['message'] ?? 'Statusul nu a putut fi citit.');
                    if (!empty($sync['packet_id_fault'])) {
                        WebGSM_Packeta_Awb_Repository::delete_by_packet_id($pid);
                        $message = WebGSM_Packeta_Awb_Sync::packet_id_fault_help($pid, $message);
                    }
                    set_transient(
                        'webgsm_packeta_last_' . get_current_user_id(),
                        ['type' => 'error', 'message' => $message, 'raw' => $sync['raw'] ?? ''],
                        120
                    );
                    $this->redirect_with_notice('awb_list', !empty($sync['packet_id_fault']) ? 'packet_id_invalid' : 'api_error');
                    break;
                }
                if ($order_ref !== '') {
                    $wc_order_id = WebGSM_Packeta_Awb_Repository::resolve_wc_order_id($order_ref);
                    $patch = ['packet_id' => $pid, 'order_ref' => $order_ref];
                    if ($wc_order_id > 0) {
                        $patch['wc_order_id'] = $wc_order_id;
                    }
                    WebGSM_Packeta_Awb_Repository::upsert($patch);
                }
                $this->redirect_with_notice('awb_list', 'awb_registered');
                break;

            case 'delete_awb':
                $pid = isset($_POST['delete_packet_id']) ? WebGSM_Packeta_Xml_Client::normalize_packet_id((string) wp_unslash($_POST['delete_packet_id'])) : '';
                if ($pid !== '') {
                    WebGSM_Packeta_Awb_Repository::delete_by_packet_id($pid);
                }
                $this->redirect_with_notice('awb_list', 'awb_deleted');
                break;

            case 'download_label':
                $return_tab = $tab !== '' ? $tab : 'label';
                if ($settings['api_password'] === '') {
                    $this->redirect_with_notice($return_tab, 'no_password');
                    break;
                }
                $pid = isset($_POST['label_packet_id']) ? (string) wp_unslash($_POST['label_packet_id']) : '';
                $pid = WebGSM_Packeta_Xml_Client::normalize_packet_id($pid);
                if ($pid === '') {
                    set_transient(
                        'webgsm_packeta_last_' . get_current_user_id(),
                        ['type' => 'error', 'message' => 'Packet ID invalid. Exemplu: Z 383 2892 743 sau 3832892743.', 'raw' => ''],
                        120
                    );
                    $this->redirect_with_notice($return_tab, 'api_error');
                    break;
                }
                $format = isset($_POST['label_format']) ? sanitize_text_field(wp_unslash((string) $_POST['label_format'])) : WebGSM_Packeta_Config::get_default_label_format();
                $allowed = ['A6 on A6', 'A7 on A7', 'A6 on A4', 'A7 on A4', '105x35mm on A4', 'A8 on A8'];
                if (!in_array($format, $allowed, true)) {
                    $format = 'A6 on A6';
                }
                $client = $this->make_client($settings);
                $res = $client->download_label_pdf($pid, $format, 0);
                $pdf = $res['pdf'] ?? null;
                if (!empty($res['ok']) && $pdf !== null && $pdf !== '') {
                    nocache_headers();
                    header('Content-Type: application/pdf');
                    header('Content-Disposition: attachment; filename="packeta-' . $pid . '.pdf"');
                    echo $pdf;
                    exit;
                }
                set_transient(
                    'webgsm_packeta_last_' . get_current_user_id(),
                    ['type' => 'error', 'message' => $res['error'] ?? 'Nu s-a putut genera PDF.', 'raw' => $res['raw'] ?? ''],
                    120
                );
                $this->redirect_with_notice($return_tab, 'api_error');
                break;

            case 'packet_status':
                $return_tab = $tab !== '' ? $tab : 'label';
                if ($settings['api_password'] === '') {
                    $this->redirect_with_notice($return_tab, 'no_password');
                    break;
                }
                $pid = isset($_POST['status_packet_id']) ? WebGSM_Packeta_Xml_Client::normalize_packet_id((string) wp_unslash($_POST['status_packet_id'])) : '';
                $client = $this->make_client($settings);
                $res = $client->packet_status($pid);
                if (!empty($res['ok'])) {
                    WebGSM_Packeta_Awb_Sync::sync_status($pid, $client, $res);
                    set_transient(
                        'webgsm_packeta_last_' . get_current_user_id(),
                        ['type' => 'status', 'data' => self::packeta_api_response_for_transient($res)],
                        120
                    );
                    $this->redirect_with_notice($return_tab, 'status_ok');
                } else {
                    set_transient(
                        'webgsm_packeta_last_' . get_current_user_id(),
                        ['type' => 'error', 'message' => $res['error'] ?? 'Eroare', 'raw' => $res['raw'] ?? ''],
                        120
                    );
                    $this->redirect_with_notice($return_tab, 'api_error');
                }
                break;

            case 'courier_number':
                $return_tab = $tab !== '' ? $tab : 'label';
                if ($settings['api_password'] === '') {
                    $this->redirect_with_notice($return_tab, 'no_password');
                    break;
                }
                $pid = isset($_POST['courier_packet_id']) ? WebGSM_Packeta_Xml_Client::normalize_packet_id((string) wp_unslash($_POST['courier_packet_id'])) : '';
                if ($pid === '') {
                    $this->redirect_with_notice($return_tab, 'missing_packet_id');
                    break;
                }
                $client = $this->make_client($settings);
                $res = $client->packet_courier_number($pid);
                if (!empty($res['ok']) && !empty($res['number'])) {
                    WebGSM_Packeta_Awb_Repository::upsert([
                        'packet_id' => $pid,
                        'courier_number' => (string) $res['number'],
                    ]);
                    set_transient(
                        'webgsm_packeta_last_' . get_current_user_id(),
                        [
                            'type' => 'courier_number',
                            'packet_id' => $pid,
                            'courier_number' => (string) $res['number'],
                        ],
                        300
                    );
                    $this->redirect_with_notice($return_tab, 'courier_number_ok');
                } else {
                    set_transient(
                        'webgsm_packeta_last_' . get_current_user_id(),
                        ['type' => 'error', 'message' => $res['error'] ?? 'Număr curier indisponibil încă.', 'raw' => $res['raw'] ?? ''],
                        120
                    );
                    $this->redirect_with_notice($return_tab, 'api_error');
                }
                break;
        }
    }

    public function ajax_refresh_awb_status(): void {
        check_ajax_referer('webgsm_packeta_awb_list', 'nonce');
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => 'Acces interzis.'], 403);
        }
        $pid = isset($_POST['packet_id']) ? WebGSM_Packeta_Xml_Client::normalize_packet_id((string) wp_unslash($_POST['packet_id'])) : '';
        if ($pid === '') {
            wp_send_json_error(['message' => 'Packet ID lipsă.']);
        }
        $settings = self::get_settings();
        if ($settings['api_password'] === '') {
            wp_send_json_error(['message' => 'Parolă API lipsă.']);
        }
        $result = WebGSM_Packeta_Awb_Sync::sync_status($pid, $this->make_client($settings));
        if (empty($result['ok'])) {
            wp_send_json_error($result);
        }
        wp_send_json_success($result);
    }

    public function ajax_refresh_all_awb_statuses(): void {
        check_ajax_referer('webgsm_packeta_awb_list', 'nonce');
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => 'Acces interzis.'], 403);
        }
        $settings = self::get_settings();
        if ($settings['api_password'] === '') {
            wp_send_json_error(['message' => 'Parolă API lipsă.']);
        }
        $client = $this->make_client($settings);
        $rows = [];
        $updated = 0;
        foreach (WebGSM_Packeta_Awb_Repository::list_active_for_sync(80) as $row) {
            $pid = (string) ($row['packet_id'] ?? '');
            if ($pid === '') {
                continue;
            }
            $sync = WebGSM_Packeta_Awb_Sync::sync_status($pid, $client);
            if (!empty($sync['ok'])) {
                $updated++;
                $rows[] = $sync;
            }
        }
        wp_send_json_success(['updated' => $updated, 'rows' => $rows]);
    }

    /**
     * @param array<string, mixed> $res
     * @param array<string, scalar> $attrs
     */
    private function save_awb_from_packet_response(array $res, array $attrs): void {
        $data = $res['data'] ?? null;
        $packet_id = WebGSM_Packeta_Awb_Sync::api_field($data, 'id');
        if ($packet_id === '') {
            return;
        }

        $flow = isset($_POST['awb_flow']) ? sanitize_key((string) $_POST['awb_flow']) : '';
        $carrier = isset($_POST['carrier_filter']) ? sanitize_text_field(wp_unslash((string) $_POST['carrier_filter'])) : '';
        if ($carrier === '') {
            $carrier = $this->carrier_label_for_address_id((int) ($attrs['addressId'] ?? 0));
        }

        $name = trim((string) ($attrs['name'] ?? '') . ' ' . (string) ($attrs['surname'] ?? ''));

        $order_ref = (string) ($attrs['number'] ?? '');
        $wc_order_id = WebGSM_Packeta_Awb_Repository::resolve_wc_order_id($order_ref);

        WebGSM_Packeta_Awb_Repository::upsert([
            'packet_id' => $packet_id,
            'barcode' => WebGSM_Packeta_Awb_Sync::api_field($data, 'barcode'),
            'barcode_text' => WebGSM_Packeta_Awb_Sync::api_field($data, 'barcodeText'),
            'wc_order_id' => $wc_order_id,
            'order_ref' => $order_ref,
            'carrier_name' => $carrier,
            'recipient_name' => $name,
            'recipient_phone' => (string) ($attrs['phone'] ?? ''),
            'flow_type' => $flow,
            'status_code_text' => 'received data',
            'status_text' => 'AWB creat',
            'progress_step' => 0,
            'progress_percent' => 0,
            'is_final' => false,
            'is_problem' => false,
        ]);

        $settings = self::get_settings();
        if ($settings['api_password'] !== '') {
            WebGSM_Packeta_Awb_Sync::sync_status($packet_id, $this->make_client($settings));
        }
    }

    /**
     * @param string[] $packet_ids
     * @param array<string, mixed> $res
     */
    private function attach_shipment_to_awb_records(array $packet_ids, array $res): void {
        $shipment_id = WebGSM_Packeta_Awb_Sync::api_field($res['data'] ?? null, 'id');
        foreach ($packet_ids as $raw_id) {
            $pid = WebGSM_Packeta_Xml_Client::normalize_packet_id((string) $raw_id);
            if ($pid === '') {
                continue;
            }
            WebGSM_Packeta_Awb_Repository::upsert([
                'packet_id' => $pid,
                'shipment_id' => $shipment_id,
            ]);
        }
    }

    private function carrier_label_for_address_id(int $address_id): string {
        if ($address_id < 1) {
            return '';
        }
        $id = (string) $address_id;
        foreach (WebGSM_Packeta_Carriers::get_checkout_carriers() as $carrier) {
            if ((string) ($carrier['carrier_id'] ?? '') === $id) {
                return (string) ($carrier['title'] ?? '');
            }
        }

        return 'ID ' . $address_id;
    }

    private function redirect_with_notice(string $tab, string $notice): void {
        wp_safe_redirect(
            add_query_arg(
                ['page' => 'webgsm-packeta', 'tab' => $tab, 'packeta_notice' => $notice],
                admin_url('admin.php')
            )
        );
        exit;
    }

    /**
     * Object cache (ex. LiteSpeed) serializează transientele — SimpleXMLElement nu poate fi serializat.
     *
     * @param array<string, mixed> $res
     * @return array<string, mixed>
     */
    private static function packeta_api_response_for_transient(array $res): array {
        return self::deep_replace_simplexml_for_transient($res);
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private static function deep_replace_simplexml_for_transient($value) {
        if ($value === null) {
            return null;
        }
        if ($value instanceof \SimpleXMLElement) {
            return self::simplexml_to_export($value);
        }
        if (is_array($value)) {
            $out = [];
            foreach ($value as $k => $v) {
                $out[$k] = self::deep_replace_simplexml_for_transient($v);
            }

            return $out;
        }

        return $value;
    }

    /**
     * @return array<string, mixed>|string
     */
    private static function simplexml_to_export(\SimpleXMLElement $sx) {
        $out = [];
        $children = $sx->children();
        if ($children instanceof \SimpleXMLElement) {
            foreach ($children as $child) {
                $out[$child->getName()] = self::simplexml_to_export($child);
            }
        }

        if ($out === []) {
            return trim((string) $sx);
        }

        return $out;
    }

    /**
     * @return array{api_password: string, rest_url: string, eshop: string, default_currency: string, widget_api_key: string, credentials_from_packeta_plugin: bool}
     */
    public static function get_settings(): array {
        return WebGSM_Packeta_Config::get_effective_settings();
    }

    private function make_client(array $settings): WebGSM_Packeta_Xml_Client {
        return new WebGSM_Packeta_Xml_Client(
            $settings['api_password'],
            $settings['rest_url'] !== '' ? $settings['rest_url'] : WebGSM_Packeta_Config::default_rest_url()
        );
    }

    /**
     * Validare înainte de createPacket / packetAttributesValid.
     */
    private function validate_awb_post_before_api(): ?string {
        $raw_val = isset($_POST['value']) ? str_replace(',', '.', (string) wp_unslash($_POST['value'])) : '';
        $parcel_value = $raw_val === '' ? 0.0 : (float) $raw_val;
        if ($parcel_value <= 0) {
            return 'missing_parcel_value';
        }

        $flow = isset($_POST['awb_flow']) ? sanitize_key((string) $_POST['awb_flow']) : '';
        if ($flow === 'home') {
            $aid = isset($_POST['address_id']) ? (int) $_POST['address_id'] : 0;
            if ($aid < 1) {
                return 'missing_home_carrier';
            }
            $street = isset($_POST['street']) ? sanitize_text_field(wp_unslash((string) $_POST['street'])) : '';
            $city = isset($_POST['city']) ? sanitize_text_field(wp_unslash((string) $_POST['city'])) : '';
            $province_code = isset($_POST['province']) ? sanitize_text_field(wp_unslash((string) $_POST['province'])) : '';
            $zip = isset($_POST['zip']) ? sanitize_text_field(wp_unslash((string) $_POST['zip'])) : '';
            $house = isset($_POST['house_number']) ? sanitize_text_field(wp_unslash((string) $_POST['house_number'])) : '';
            if ($street === '' || $city === '') {
                return 'missing_home_address';
            }
            if (!WebGSM_Packeta_Config::is_valid_ro_county_code($province_code)) {
                return 'missing_home_province';
            }
            if ($zip === '') {
                return 'missing_home_zip';
            }
            if ($house === '') {
                return 'missing_home_house';
            }

            return null;
        }

        $mode = isset($_POST['delivery_mode']) ? sanitize_key((string) $_POST['delivery_mode']) : 'pudo';
        $pickup_type = isset($_POST['point_pickup_type']) ? sanitize_key((string) $_POST['point_pickup_type']) : '';
        if ($pickup_type === 'internal') {
            $mode = 'pudo';
        } elseif ($pickup_type === 'external') {
            $mode = 'carrier_pudo';
        }

        if ($mode === 'home') {
            return null;
        }

        if (in_array($mode, ['pudo', 'carrier_pudo'], true)) {
            $aid = isset($_POST['address_id']) ? (int) $_POST['address_id'] : 0;
            if ($aid < 1) {
                return 'missing_point';
            }
            if ($pickup_type === '') {
                return 'missing_point';
            }
            if ($mode === 'carrier_pudo') {
                $cpp = isset($_POST['carrier_pickup_point']) ? sanitize_text_field(wp_unslash((string) $_POST['carrier_pickup_point'])) : '';
                if ($cpp === '') {
                    return 'missing_point';
                }
            }
        }

        return null;
    }

    /**
     * @return array<string, scalar>
     */
    private function collect_packet_attributes_from_post(array $settings): array {
        $flow = isset($_POST['awb_flow']) ? sanitize_key((string) $_POST['awb_flow']) : '';
        $pickup_type = '';
        if ($flow === 'home') {
            $mode = 'home';
        } else {
            $mode = isset($_POST['delivery_mode']) ? sanitize_key((string) $_POST['delivery_mode']) : 'pudo';
            $pickup_type = isset($_POST['point_pickup_type']) ? sanitize_key((string) $_POST['point_pickup_type']) : '';
            if ($pickup_type === 'internal') {
                $mode = 'pudo';
            } elseif ($pickup_type === 'external') {
                $mode = 'carrier_pudo';
            }
        }

        $number = isset($_POST['order_number']) ? sanitize_text_field(wp_unslash((string) $_POST['order_number'])) : '';
        if ($number === '') {
            $number = 'WG-' . gmdate('Ymd-His');
        }

        $attrs = [
            'number' => $number,
            'name' => isset($_POST['recipient_name']) ? sanitize_text_field(wp_unslash((string) $_POST['recipient_name'])) : '',
            'surname' => isset($_POST['recipient_surname']) ? sanitize_text_field(wp_unslash((string) $_POST['recipient_surname'])) : '',
            'email' => isset($_POST['recipient_email']) ? sanitize_email(wp_unslash((string) $_POST['recipient_email'])) : '',
            'phone' => isset($_POST['recipient_phone']) ? sanitize_text_field(wp_unslash((string) $_POST['recipient_phone'])) : '',
            'addressId' => isset($_POST['address_id']) ? (int) $_POST['address_id'] : 0,
            'value' => isset($_POST['value']) ? (float) str_replace(',', '.', (string) wp_unslash($_POST['value'])) : 0,
            'weight' => isset($_POST['weight']) ? (float) str_replace(',', '.', (string) wp_unslash($_POST['weight'])) : 1,
            'currency' => isset($_POST['currency']) ? strtoupper(sanitize_text_field(wp_unslash((string) $_POST['currency']))) : $settings['default_currency'],
            'eshop' => $settings['eshop'],
        ];

        $cod = isset($_POST['cod']) ? (float) str_replace(',', '.', (string) wp_unslash($_POST['cod'])) : 0;
        if ($cod > 0) {
            $attrs['cod'] = $cod;
        }

        $company = isset($_POST['company']) ? sanitize_text_field(wp_unslash((string) $_POST['company'])) : '';
        if ($company !== '') {
            $attrs['company'] = $company;
        }

        $note = isset($_POST['note']) ? sanitize_textarea_field(wp_unslash((string) $_POST['note'])) : '';
        if ($note !== '') {
            $attrs['note'] = $note;
        }

        if ($mode === 'home') {
            $street = isset($_POST['street']) ? sanitize_text_field(wp_unslash((string) $_POST['street'])) : '';
            $house = isset($_POST['house_number']) ? sanitize_text_field(wp_unslash((string) $_POST['house_number'])) : '';
            $city = isset($_POST['city']) ? sanitize_text_field(wp_unslash((string) $_POST['city'])) : '';
            $zip = isset($_POST['zip']) ? sanitize_text_field(wp_unslash((string) $_POST['zip'])) : '';
            $province_code = isset($_POST['province']) ? sanitize_text_field(wp_unslash((string) $_POST['province'])) : '';
            $province = WebGSM_Packeta_Config::ro_province_for_api($province_code);
            if ($street !== '') {
                $attrs['street'] = $street;
            }
            if ($house !== '') {
                $attrs['houseNumber'] = $house;
            }
            if ($city !== '') {
                $attrs['city'] = $city;
            }
            if ($zip !== '') {
                $attrs['zip'] = $zip;
            }
            if ($province !== '') {
                $attrs['province'] = $province;
            }
        }

        if ($mode === 'carrier_pudo') {
            $cpp = isset($_POST['carrier_pickup_point']) ? sanitize_text_field(wp_unslash((string) $_POST['carrier_pickup_point'])) : '';
            if ($cpp !== '') {
                $attrs['carrierPickupPoint'] = $cpp;
            }
        }

        return $attrs;
    }

    private static function awb_draft_transient_key(): string {
        return 'webgsm_packeta_awb_draft_' . get_current_user_id();
    }

    /**
     * @return array<string, string>
     */
    private function collect_awb_form_draft_from_post(): array {
        $fields = [
            'awb_flow',
            'delivery_mode',
            'point_pickup_type',
            'address_id',
            'carrier_pickup_point',
            'order_number',
            'street',
            'house_number',
            'city',
            'province',
            'zip',
            'recipient_name',
            'recipient_surname',
            'recipient_phone',
            'company',
            'value',
            'currency',
            'weight',
            'cod',
            'point_summary',
            'carrier_filter',
        ];

        $draft = [];
        foreach ($fields as $field) {
            if (!isset($_POST[$field])) {
                continue;
            }
            $draft[$field] = sanitize_text_field(wp_unslash((string) $_POST[$field]));
        }

        if (isset($_POST['recipient_email'])) {
            $draft['recipient_email'] = sanitize_email(wp_unslash((string) $_POST['recipient_email']));
        }
        if (isset($_POST['note'])) {
            $draft['note'] = sanitize_textarea_field(wp_unslash((string) $_POST['note']));
        }

        return $draft;
    }

    private function store_awb_form_draft_from_post(): void {
        set_transient(self::awb_draft_transient_key(), $this->collect_awb_form_draft_from_post(), DAY_IN_SECONDS);
    }

    /**
     * @return array<string, string>
     */
    public static function get_awb_form_draft(): array {
        $draft = get_transient(self::awb_draft_transient_key());

        return is_array($draft) ? $draft : [];
    }

    public static function clear_awb_form_draft(): void {
        delete_transient(self::awb_draft_transient_key());
    }

    public function render_page(): void {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('Nu aveți dreptul să accesați această pagină.', 'webgsm-packeta'));
        }

        $tab = isset($_GET['tab']) ? sanitize_key((string) $_GET['tab']) : 'settings';
        $allowed = ['settings', 'awb', 'awb_list', 'shipment', 'label'];
        if (!in_array($tab, $allowed, true)) {
            $tab = 'settings';
        }

        $settings = self::get_settings();
        $packetery_option = get_option(WebGSM_Packeta_Config::PACKETA_OPTION, []);
        if (!is_array($packetery_option)) {
            $packetery_option = [];
        }
        $checkout_carriers = WebGSM_Packeta_Carriers::get_checkout_carriers();
        $awb_draft = $tab === 'awb' ? self::get_awb_form_draft() : [];
        $pricing_sync_result = null;
        if ($tab === 'settings') {
            $pricing_sync_result = get_transient('webgsm_packeta_pricing_sync_' . get_current_user_id());
            if (is_array($pricing_sync_result)) {
                delete_transient('webgsm_packeta_pricing_sync_' . get_current_user_id());
            }
        }
        $last = get_transient('webgsm_packeta_last_' . get_current_user_id());
        if (is_array($last)) {
            delete_transient('webgsm_packeta_last_' . get_current_user_id());
        } else {
            $last = null;
        }

        include WEBGSM_PACKETA_PATH . 'admin/views/main.php';
    }
}
