<?php
if (!defined('ABSPATH')) {
    exit;
}

class WebGSM_Packeta_Awb_Sync {

    /**
     * @param array<string, mixed>|null $api_response
     * @return array<string, mixed>
     */
    public static function sync_status(string $packet_id, WebGSM_Packeta_Xml_Client $client, ?array $api_response = null): array {
        $packet_id = WebGSM_Packeta_Xml_Client::normalize_packet_id($packet_id);
        if ($packet_id === '') {
            return ['ok' => false, 'message' => 'Packet ID invalid.'];
        }

        $res = $api_response ?? $client->packet_status($packet_id);
        if (empty($res['ok'])) {
            $message = $res['error'] ?? 'Eroare API';
            $is_fault = WebGSM_Packeta_Xml_Client::is_packet_id_fault_message((string) $message);
            if ($is_fault && WebGSM_Packeta_Awb_Repository::get_by_packet_id($packet_id) !== null) {
                WebGSM_Packeta_Awb_Repository::mark_invalid_packet(
                    $packet_id,
                    'AWB invalid — verifică numărul'
                );
            }

            return [
                'ok' => false,
                'message' => $is_fault ? self::packet_id_fault_help($packet_id, (string) $message) : $message,
                'raw' => $res['raw'] ?? '',
                'packet_id_fault' => $is_fault,
            ];
        }

        $parsed = self::parse_status_response($res['data'] ?? null);
        if ($parsed === null) {
            return ['ok' => false, 'message' => 'Răspuns status invalid.'];
        }

        $existing = WebGSM_Packeta_Awb_Repository::get_by_packet_id($packet_id);
        $courier = (string) ($parsed['external_tracking_code'] ?? '');
        if ($courier === '' && is_array($existing) && !empty($existing['courier_number'])) {
            $courier = (string) $existing['courier_number'];
        } elseif ($courier === '') {
            $cn = $client->packet_courier_number($packet_id);
            if (!empty($cn['ok']) && !empty($cn['number'])) {
                $courier = (string) $cn['number'];
            }
        }

        $has_courier = $courier !== '';
        $progress = WebGSM_Packeta_Status_Mapper::from_api_status(
            (int) $parsed['status_code'],
            (string) $parsed['code_text'],
            '',
            $has_courier
        );

        $ro_label = WebGSM_Packeta_Status_Mapper::step_label_ro(
            (int) $progress['step'],
            (string) $parsed['code_text']
        );

        WebGSM_Packeta_Awb_Repository::update_status($packet_id, [
            'status_code' => (int) $parsed['status_code'],
            'status_code_text' => (string) $parsed['code_text'],
            'status_text' => $ro_label,
            'status_datetime' => (string) ($parsed['date_time'] ?? ''),
            'progress_step' => (int) $progress['step'],
            'progress_percent' => (int) $progress['percent'],
            'is_final' => !empty($progress['is_final']),
            'is_problem' => !empty($progress['is_problem']),
            'courier_number' => $courier,
        ]);

        if (is_array($existing) && (int) ($existing['wc_order_id'] ?? 0) < 1 && !empty($existing['order_ref'])) {
            $wc_id = WebGSM_Packeta_Awb_Repository::resolve_wc_order_id((string) $existing['order_ref']);
            if ($wc_id > 0) {
                WebGSM_Packeta_Awb_Repository::upsert([
                    'packet_id' => $packet_id,
                    'wc_order_id' => $wc_id,
                ]);
            }
        }

        $updated_at = current_time('mysql');

        return [
            'ok' => true,
            'packet_id' => $packet_id,
            'status_label' => $ro_label,
            'progress_step' => (int) $progress['step'],
            'progress_percent' => (int) $progress['percent'],
            'is_final' => !empty($progress['is_final']),
            'is_problem' => !empty($progress['is_problem']),
            'courier_number' => $courier,
            'updated_human' => wp_date('d.m. H:i', strtotime($updated_at)),
        ];
    }

    /**
     * @param mixed $data
     * @return array<string, string>|null
     */
    public static function parse_status_response($data): ?array {
        if ($data === null || $data === '') {
            return null;
        }

        $status_code = self::api_field($data, 'statusCode');
        $code_text = self::api_field($data, 'codeText');
        if ($status_code === '' && $code_text === '') {
            return null;
        }

        return [
            'status_code' => $status_code !== '' ? $status_code : '0',
            'code_text' => $code_text,
            'status_text' => self::api_field($data, 'statusText'),
            'date_time' => self::api_field($data, 'dateTime'),
            'external_tracking_code' => self::api_field($data, 'externalTrackingCode'),
        ];
    }

    /**
     * @param mixed $data
     */
    public static function api_field($data, string $name): string {
        if ($data instanceof \SimpleXMLElement && isset($data->{$name})) {
            return trim((string) $data->{$name});
        }
        if (is_array($data) && isset($data[$name]) && is_scalar($data[$name])) {
            return trim((string) $data[$name]);
        }

        return '';
    }

    public static function packet_id_fault_help(string $packet_id, string $api_message): string {
        return 'Packet ID invalid (' . $packet_id . '). '
            . 'Folosește Packet ID-ul de la „Trimite AWB” (ex. 3832892743) sau barcode Z. '
            . 'Nu folosi AWB-ul Sameday/Fan. Detaliu API: ' . $api_message;
    }
}
