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
        $packet_id = preg_replace('/\D/', '', $packet_id);
        $xml = '<packetLabelPdf>'
            . '<apiPassword>' . $this->escape_xml($this->api_password) . '</apiPassword>'
            . '<packetId>' . $this->escape_xml($packet_id) . '</packetId>'
            . '<format>' . $this->escape_xml($format) . '</format>'
            . '<offset>' . (int) $offset . '</offset>'
            . '</packetLabelPdf>';

        $res = $this->post_xml($xml, true);
        if (!empty($res['ok'])) {
            $res['pdf'] = self::extract_pdf_from_api_result($res['data'] ?? null, $res['raw'] ?? '');
        }

        return $res;
    }

    public function packet_status(string $packet_id): array {
        $packet_id = preg_replace('/\D/', '', $packet_id);
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
        if ($raw !== '' && str_starts_with(ltrim($raw), '%PDF')) {
            return $raw;
        }
        if (is_string($data) && str_starts_with(trim($data), '%PDF')) {
            return $data;
        }
        if ($data instanceof \SimpleXMLElement) {
            $str = (string) $data;
            if ($str !== '' && str_starts_with(trim($str), '%PDF')) {
                return $str;
            }
            if ($str !== '') {
                $decoded = base64_decode($str, true);
                if ($decoded !== false && str_starts_with($decoded, '%PDF')) {
                    return $decoded;
                }
            }
        }
        if ($raw !== '' && preg_match('/<result[^>]*>([\s\S]*?)<\/result>/', $raw, $m)) {
            $inner = trim($m[1]);
            $decoded = base64_decode($inner, true);
            if ($decoded !== false && str_starts_with($decoded, '%PDF')) {
                return $decoded;
            }
            if (str_starts_with($inner, '%PDF')) {
                return $inner;
            }
        }

        return null;
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
}
