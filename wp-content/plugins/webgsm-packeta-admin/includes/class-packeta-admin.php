<?php
if (!defined('ABSPATH')) {
    exit;
}

class WebGSM_Packeta_Admin {

    public function __construct() {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_init', [$this, 'handle_post']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);
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
                'addressFieldsRequired' => 'Completează strada și orașul pentru livrarea la adresă.',
                'missingHomeCarrier' => 'Introdu addressId pentru transportatorul de livrare la adresă (din Packeta).',
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

            case 'create_packet':
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
                if (isset($_POST['validate_only'])) {
                    $res = $client->packet_attributes_valid($attrs);
                } else {
                    $res = $client->create_packet($attrs);
                }
                if (!empty($res['ok'])) {
                    set_transient(
                        'webgsm_packeta_last_' . get_current_user_id(),
                        ['type' => 'packet', 'data' => $res, 'attrs' => $attrs],
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
                    set_transient(
                        'webgsm_packeta_last_' . get_current_user_id(),
                        ['type' => 'shipment', 'data' => $res],
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

            case 'download_label':
                if ($settings['api_password'] === '') {
                    $this->redirect_with_notice('label', 'no_password');
                    break;
                }
                $pid = isset($_POST['label_packet_id']) ? preg_replace('/\D/', '', (string) wp_unslash($_POST['label_packet_id'])) : '';
                $format = isset($_POST['label_format']) ? sanitize_text_field(wp_unslash((string) $_POST['label_format'])) : 'A6 on A6';
                $allowed = ['A6 on A6', 'A7 on A7', 'A6 on A4', 'A7 on A4', '105x35mm on A4', 'A8 on A8'];
                if (!in_array($format, $allowed, true)) {
                    $format = 'A6 on A6';
                }
                $client = $this->make_client($settings);
                $res = $client->packet_label_pdf($pid, $format, 0);
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
                $this->redirect_with_notice('label', 'api_error');
                break;

            case 'packet_status':
                if ($settings['api_password'] === '') {
                    $this->redirect_with_notice('label', 'no_password');
                    break;
                }
                $pid = isset($_POST['status_packet_id']) ? preg_replace('/\D/', '', (string) wp_unslash($_POST['status_packet_id'])) : '';
                $client = $this->make_client($settings);
                $res = $client->packet_status($pid);
                if (!empty($res['ok'])) {
                    set_transient(
                        'webgsm_packeta_last_' . get_current_user_id(),
                        ['type' => 'status', 'data' => $res],
                        120
                    );
                    $this->redirect_with_notice('label', 'status_ok');
                } else {
                    set_transient(
                        'webgsm_packeta_last_' . get_current_user_id(),
                        ['type' => 'error', 'message' => $res['error'] ?? 'Eroare', 'raw' => $res['raw'] ?? ''],
                        120
                    );
                    $this->redirect_with_notice('label', 'api_error');
                }
                break;
        }
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
        $flow = isset($_POST['awb_flow']) ? sanitize_key((string) $_POST['awb_flow']) : '';
        if ($flow === 'home') {
            $aid = isset($_POST['address_id']) ? (int) $_POST['address_id'] : 0;
            if ($aid < 1) {
                return 'missing_home_carrier';
            }
            $street = isset($_POST['street']) ? sanitize_text_field(wp_unslash((string) $_POST['street'])) : '';
            $city = isset($_POST['city']) ? sanitize_text_field(wp_unslash((string) $_POST['city'])) : '';
            if ($street === '' || $city === '') {
                return 'missing_home_address';
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
        }

        if ($mode === 'carrier_pudo') {
            $cpp = isset($_POST['carrier_pickup_point']) ? sanitize_text_field(wp_unslash((string) $_POST['carrier_pickup_point'])) : '';
            if ($cpp !== '') {
                $attrs['carrierPickupPoint'] = $cpp;
            }
        }

        return $attrs;
    }

    public function render_page(): void {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('Nu aveți dreptul să accesați această pagină.', 'webgsm-packeta'));
        }

        $tab = isset($_GET['tab']) ? sanitize_key((string) $_GET['tab']) : 'settings';
        $allowed = ['settings', 'awb', 'shipment', 'label'];
        if (!in_array($tab, $allowed, true)) {
            $tab = 'settings';
        }

        $settings = self::get_settings();
        $packetery_option = get_option(WebGSM_Packeta_Config::PACKETA_OPTION, []);
        if (!is_array($packetery_option)) {
            $packetery_option = [];
        }
        $checkout_carriers = WebGSM_Packeta_Carriers::get_checkout_carriers();
        $last = get_transient('webgsm_packeta_last_' . get_current_user_id());
        if (is_array($last)) {
            delete_transient('webgsm_packeta_last_' . get_current_user_id());
        } else {
            $last = null;
        }

        include WEBGSM_PACKETA_PATH . 'admin/views/main.php';
    }
}
