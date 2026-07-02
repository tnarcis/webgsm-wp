<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Link-uri tracking curieri locali (fără Packeta).
 */
class WebGSM_Packeta_Carrier_Tracking {

    public static function slug_from_carrier_name(string $carrier_name): string {
        $name = strtolower($carrier_name);
        if (strpos($name, 'sameday') !== false) {
            return 'sameday';
        }
        if (strpos($name, 'fan') !== false) {
            return 'fan';
        }
        if (strpos($name, 'cargus') !== false) {
            return 'cargus';
        }

        return 'unknown';
    }

    public static function slug_from_carrier_id(string $carrier_id): string {
        $map = [
            '7397' => 'sameday',
            '7455' => 'sameday',
            '762' => 'fan',
            '590' => 'cargus',
            '4161' => 'unknown',
        ];

        return $map[$carrier_id] ?? 'unknown';
    }

    public static function display_name(string $slug): string {
        $map = [
            'sameday' => 'Sameday',
            'fan' => 'FAN Courier',
            'cargus' => 'Cargus',
            'unknown' => 'Curier',
        ];

        return $map[$slug] ?? 'Curier';
    }

    public static function tracking_url(string $slug, string $awb): string {
        $awb = trim($awb);
        if ($awb === '') {
            return '';
        }

        switch ($slug) {
            case 'sameday':
                return 'https://sameday.ro/#awb=' . rawurlencode($awb);
            case 'fan':
                return 'https://www.fancourier.ro/awb-tracking/?AWB=' . rawurlencode($awb);
            case 'cargus':
                return 'https://www.cargus.ro/personal/urmarire-awb/?awb=' . rawurlencode($awb);
            default:
                return '';
        }
    }
}
