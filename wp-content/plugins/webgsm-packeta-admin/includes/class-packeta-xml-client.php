<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Packeta REST/XML API (POST XML body).
 *
 * @see https://docs.packeta.com/docs/getting-started/packeta-api
 */
class WebGSM_Packeta_Xml_Client {

    private string $api_password;
    private string $rest_url;
    private int $timeout;

    public function __construct(string $api_password, string $rest_url, int $timeout = 90) {
        $this->api_password = $api_password;
        $this->rest_url = rtrim($rest_url, '/');
        $this->timeout = $timeout;
    }

    /**
     * @param array<string, scalar|array> $packet_attributes Key-value for packetAttributes children.
     * @return array{ok: bool, data?: mixed, error?: string, raw?: string}
     */
    public function create_packet(array $packet_attributes): array {
        $inner = $this->array_to_xml_elements('packetAttributes', $packet_attributes);
        $xml = '<createPacket>'
            . '<apiPassword>' . $this->escape_xml($this->api_password) . '</apiPassword>'
            . $inner
            . '</createPacket>';

        return $this->post_xml($xml);
    }

    /**
     * @param string[] $packet_ids Numeric packet IDs.
     */
    public function create_shipment(array $packet_ids, string $custom_barcode = ''): array {
        $ids = '';
        foreach ($packet_ids as $id) {
            $id = preg_replace('/\D/', '', (string) $id);
            if ($id !== '') {
                $ids .= '<id>' . $this->escape_xml($id) . '</id>';
            }
        }
        $tail = $custom_barcode !== ''
            ? '<customBarcode>' . $this->escape_xml($custom_barcode) . '</customBarcode>'
            : '';

        $xml = '<createShipment>'
            . '<apiPassword>' . $this->escape_xml($this->api_password) . '</apiPassword>'
            . '<packetIds>' . $ids . '</packetIds>'
            . $tail
            . '</createShipment>';

        return $this->post_xml($xml);
    }

    public function packet_label_pdf(string $packet_id, string $format = 'A6 on A6', int $offset = 0): array {
        $packet_id = self::normalize_packet_id($packet_id);
        if ($packet_id === '') {
            return ['ok' => false, 'error' => 'Packet ID invalid (ex. Z 383 2892 743 sau 3832892743).'];
        }

        $xml = '<packetLabelPdf>'
            . '<apiPassword>' . $this->escape_xml($this->api_password) . '</apiPassword>'
            . '<packetId>' . $this->escape_xml($packet_id) . '</packetId>'
            . '<format>' . $this->escape_xml($format) . '</format>'
            . '<offset>' . (int) $offset . '</offset>'
            . '</packetLabelPdf>';

        return $this->label_pdf_request($xml);
    }

    /**
     * Pentru Sameday / Fan / Cargus (RO): etichetă curier — obligatoriu înainte de packetLabelPdf.
     *
     * @return array{ok: bool, number?: string, error?: string, raw?: string}
     */
    public function packet_courier_number(string $packet_id): array {
        $packet_id = self::normalize_packet_id($packet_id);
        if ($packet_id === '') {
            return ['ok' => false, 'error' => 'Packet ID invalid.'];
        }

        $xml = '<packetCourierNumber>'
            . '<apiPassword>' . $this->escape_xml($this->api_password) . '</apiPassword>'
            . '<packetId>' . $this->escape_xml($packet_id) . '</packetId>'
            . '</packetCourierNumber>';

        $res = $this->post_xml($xml);
        if (empty($res['ok'])) {
            return $res;
        }

        $number = '';
        $data = $res['data'] ?? null;
        if ($data instanceof \SimpleXMLElement) {
            $number = trim((string) $data);
        } elseif (is_string($data)) {
            $number = trim($data);
        }

        if ($number === '') {
            return ['ok' => false, 'error' => 'API nu a returnat număr curier.', 'raw' => $res['raw'] ?? ''];
        }

        return ['ok' => true, 'number' => $number, 'raw' => $res['raw'] ?? ''];
    }

    public function packet_courier_label_pdf(string $packet_id, string $courier_number): array {
        $packet_id = self::normalize_packet_id($packet_id);
        $courier_number = trim($courier_number);
        if ($packet_id === '' || $courier_number === '') {
            return ['ok' => false, 'error' => 'Lipsesc packetId sau courierNumber.'];
        }

        $xml = '<packetCourierLabelPdf>'
            . '<apiPassword>' . $this->escape_xml($this->api_password) . '</apiPassword>'
            . '<packetId>' . $this->escape_xml($packet_id) . '</packetId>'
            . '<courierNumber>' . $this->escape_xml($courier_number) . '</courierNumber>'
            . '</packetCourierLabelPdf>';

        return $this->label_pdf_request($xml);
    }

    /**
     * Încearcă etichetă curier (RO), apoi etichetă Packeta.
     *
     * @return array{ok: bool, pdf?: string, label_type?: string, error?: string, raw?: string}
     */
    public function download_label_pdf(string $packet_id, string $format = 'A6 on A6', int $offset = 0): array {
        $packet_id = self::normalize_packet_id($packet_id);
        if ($packet_id === '') {
            return ['ok' => false, 'error' => 'Packet ID invalid. Poți lipi „Z 383 2892 743” sau doar cifrele.'];
        }

        $courier_err = '';
        $courier = $this->packet_courier_number($packet_id);
        if (!empty($courier['ok']) && !empty($courier['number'])) {
            $carrier = $this->packet_courier_label_pdf($packet_id, (string) $courier['number']);
            if (!empty($carrier['ok']) && !empty($carrier['pdf'])) {
                $carrier['label_type'] = 'courier';

                return $carrier;
            }
            $courier_err = $carrier['error'] ?? 'Etichetă curier indisponibilă.';
        } else {
            $courier_err = $courier['error'] ?? '';
        }

        $packeta = $this->packet_label_pdf($packet_id, $format, $offset);
        if (!empty($packeta['ok']) && !empty($packeta['pdf'])) {
            $packeta['label_type'] = 'packeta';

            return $packeta;
        }

        $parts = array_filter([
            $packeta['error'] ?? '',
            $courier_err !== '' ? 'Curier: ' . $courier_err : '',
        ]);

        return [
            'ok' => false,
            'error' => $parts !== [] ? implode(' ', $parts) : 'Nu s-a putut genera PDF.',
            'raw' => $packeta['raw'] ?? ($courier['raw'] ?? ''),
        ];
    }

    /**
     * @return array{ok: bool, pdf?: string, error?: string, raw?: string}
     */
    private function label_pdf_request(string $xml): array {
        $res = $this->post_xml($xml, true);
        if (!empty($res['ok'])) {
            $res['pdf'] = self::extract_pdf_from_api_result($res['data'] ?? null, $res['raw'] ?? '');
            if (($res['pdf'] ?? '') === '') {
                return [
                    'ok' => false,
                    'error' => 'Răspuns OK dar PDF lipsă (decodare base64 eșuată).',
                    'raw' => $res['raw'] ?? '',
                ];
            }
        }

        return $res;
    }

    public function packet_status(string $packet_id): array {
        $packet_id = self::normalize_packet_id($packet_id);
        $xml = '<packetStatus>'
            . '<apiPassword>' . $this->escape_xml($this->api_password) . '</apiPassword>'
            . '<packetId>' . $this->escape_xml($packet_id) . '</packetId>'
            . '</packetStatus>';

        return $this->post_xml($xml);
    }

    public function packet_attributes_valid(array $packet_attributes): array {
        $inner = $this->array_to_xml_elements('packetAttributes', $packet_attributes);
        $xml = '<packetAttributesValid>'
            . '<apiPassword>' . $this->escape_xml($this->api_password) . '</apiPassword>'
            . $inner
            . '</packetAttributesValid>';

        return $this->post_xml($xml);
    }

    /**
     * @param bool $binary_result If true, successful result may be raw PDF bytes in XML.
     */
    private function post_xml(string $xml_body, bool $binary_result = false): array {
        $response = wp_remote_post(
            $this->rest_url,
            [
                'timeout' => $this->timeout,
                'headers' => [
                    'Content-Type' => 'application/xml; charset=UTF-8',
                ],
                'body' => $xml_body,
            ]
        );

        if (is_wp_error($response)) {
            return ['ok' => false, 'error' => $response->get_error_message()];
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        $raw = (string) wp_remote_retrieve_body($response);

        if ($code < 200 || $code >= 300) {
            return ['ok' => false, 'error' => 'HTTP ' . $code, 'raw' => $raw];
        }

        if ($binary_result && strpos($raw, '<?xml') !== 0 && strpos($raw, '<response') === false) {
            return ['ok' => true, 'data' => ['binary' => $raw]];
        }

        libxml_use_internal_errors(true);
        $sx = simplexml_load_string($raw);
        if ($sx === false) {
            if ($binary_result && $raw !== '') {
                return ['ok' => true, 'data' => ['binary' => $raw]];
            }
            return ['ok' => false, 'error' => 'Răspuns invalid / non-XML.', 'raw' => $raw];
        }

        $status = (string) $sx->status;
        if ($status === 'fault') {
            $fault = self::format_fault($sx);
            return ['ok' => false, 'error' => $fault !== '' ? $fault : 'Eroare API (fault)', 'raw' => $raw];
        }

        if ($status !== 'ok') {
            return ['ok' => false, 'error' => $status !== '' ? $status : 'Răspuns necunoscut', 'raw' => $raw];
        }

        return ['ok' => true, 'data' => $sx->result, 'raw' => $raw];
    }

    /**
     * @param \SimpleXMLElement|string|null $data
     */
    private static function extract_pdf_from_api_result($data, string $raw): ?string {
        if (is_array($data) && !empty($data['binary']) && is_string($data['binary'])) {
            $bin = $data['binary'];
            if (str_starts_with(ltrim($bin), '%PDF')) {
                return $bin;
            }
        }

        if ($raw !== '' && str_starts_with(ltrim($raw), '%PDF')) {
            return $raw;
        }
        if (is_string($data) && str_starts_with(trim($data), '%PDF')) {
            return $data;
        }
        if ($data instanceof \SimpleXMLElement) {
            $str = trim((string) $data);
            $pdf = self::decode_pdf_payload($str);
            if ($pdf !== null) {
                return $pdf;
            }
        }
        if ($raw !== '' && preg_match('/<result[^>]*>([\s\S]*?)<\/result>/', $raw, $m)) {
            $pdf = self::decode_pdf_payload(trim($m[1]));
            if ($pdf !== null) {
                return $pdf;
            }
        }

        return null;
    }

    private static function decode_pdf_payload(string $payload): ?string {
        if ($payload === '') {
            return null;
        }
        if (str_starts_with($payload, '%PDF')) {
            return $payload;
        }
        $b64 = preg_replace('/\s+/', '', $payload) ?? $payload;
        $decoded = base64_decode($b64, true);
        if ($decoded !== false && str_starts_with($decoded, '%PDF')) {
            return $decoded;
        }

        return null;
    }

    public static function normalize_packet_id(string $value): string {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        return preg_replace('/\D/', '', $value) ?? '';
    }

    private function array_to_xml_elements(string $wrapper, array $data): string {
        $out = '<' . $wrapper . '>';
        foreach ($data as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            if (is_array($value)) {
                foreach ($value as $item) {
                    $out .= '<' . $key . '>' . $this->escape_xml((string) $item) . '</' . $key . '>';
                }
                continue;
            }
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }
            $out .= '<' . $key . '>' . $this->escape_xml((string) $value) . '</' . $key . '>';
        }
        $out .= '</' . $wrapper . '>';

        return $out;
    }

    private function escape_xml(string $s): string {
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private static function format_fault(\SimpleXMLElement $sx): string {
        $fault = isset($sx->fault) ? trim((string) $sx->fault) : '';
        $detail = isset($sx->string) ? trim((string) $sx->string) : '';

        if ($fault !== '' && $detail !== '') {
            return $fault . ': ' . $detail;
        }
        if ($detail !== '') {
            return $detail;
        }

        if (!isset($sx->fault)) {
            return '';
        }
        $parts = [];
        foreach ($sx->fault->children() as $child) {
            $parts[] = $child->getName() . ': ' . trim((string) $child);
        }
        $plain = trim((string) $sx->fault);
        if ($plain !== '' && $parts === []) {
            return $plain;
        }

        return implode(' ', $parts);
    }

    public static function is_packet_id_fault_message(string $message): bool {
        return stripos($message, 'PacketIdFault') !== false
            || stripos($message, 'Incorrect packet ID') !== false;
    }
}
