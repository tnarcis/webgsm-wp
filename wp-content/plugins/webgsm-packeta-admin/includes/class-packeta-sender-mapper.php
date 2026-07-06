<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Expeditori Packeta per curier (ex. „No Limit Tech - Sameday”) — ca în client.packeta.com.
 */
class WebGSM_Packeta_Sender_Mapper {

    /**
     * Sufix expeditor în contul Packeta, după carrier_id transportator.
     *
     * @return array<string, string>
     */
    public static function carrier_suffix_map(): array {
        return [
            '7397' => ' - Sameday',
            '7455' => ' - Sameday',
            '762' => ' - FAN Courier',
            '32428' => ' - FAN Courier',
            '590' => ' - Cargus',
        ];
    }

    public static function is_known_carrier(string $carrier_id): bool {
        $carrier_id = preg_replace('/\D/', '', $carrier_id) ?? '';

        return $carrier_id !== '' && isset(self::carrier_suffix_map()[$carrier_id]);
    }

    public static function eshop_for_carrier(string $sender_base, string $carrier_id): string {
        $sender_base = trim($sender_base);
        if ($sender_base === '') {
            return '';
        }

        $carrier_id = preg_replace('/\D/', '', $carrier_id) ?? '';
        $map = self::carrier_suffix_map();
        if ($carrier_id === '' || !isset($map[$carrier_id])) {
            return $sender_base;
        }

        $suffix = $map[$carrier_id];
        foreach ($map as $known_suffix) {
            if ($known_suffix !== '' && str_ends_with($sender_base, $known_suffix)) {
                return $sender_base;
            }
        }

        return $sender_base . $suffix;
    }

    /**
     * @return array{base: string, carrier_id: string, eshop: string}
     */
    public static function resolve_from_post(array $settings): array {
        $base = trim((string) ($settings['sender_base'] ?? ''));
        if ($base === '') {
            $base = trim((string) ($settings['eshop'] ?? ''));
        }

        $carrier_id = '';
        if (isset($_POST['carrier_filter'])) {
            $cf = preg_replace('/\D/', '', (string) wp_unslash($_POST['carrier_filter'])) ?? '';
            if (self::is_known_carrier($cf)) {
                $carrier_id = $cf;
            }
        }
        if ($carrier_id === '' && isset($_POST['address_id'])) {
            $aid = preg_replace('/\D/', '', (string) wp_unslash($_POST['address_id'])) ?? '';
            if (self::is_known_carrier($aid)) {
                $carrier_id = $aid;
            }
        }

        return [
            'base' => $base,
            'carrier_id' => $carrier_id,
            'eshop' => self::eshop_for_carrier($base, $carrier_id),
        ];
    }
}
