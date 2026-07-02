<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Rezolvă setările efective: pluginul oficial Packeta (WooCommerce) + opțional override doar pentru URL REST.
 *
 * Opțiunea Packeta: {@see https://github.com/Zasilkovna/WooCommerce} — `packetery` (array) cu api_password, api_key, sender.
 */
class WebGSM_Packeta_Config {

    public const PACKETA_OPTION = 'packetery';
    public const PACKETA_SETTINGS_PAGE = 'packeta-options';

    public static function default_rest_url(): string {
        return 'https://www.zasilkovna.cz/api/rest';
    }

    /**
     * @return array{
     *   api_password: string,
     *   widget_api_key: string,
     *   rest_url: string,
     *   eshop: string,
     *   default_currency: string,
     *   credentials_from_packeta_plugin: bool
     * }
     */
    public static function get_effective_settings(): array {
        $packetery = get_option(self::PACKETA_OPTION, []);
        if (!is_array($packetery)) {
            $packetery = [];
        }

        $stored = get_option(WEBGSM_PACKETA_OPTION, []);
        if (!is_array($stored)) {
            $stored = [];
        }

        $api_password = '';
        if (!empty($packetery['api_password'])) {
            $api_password = (string) $packetery['api_password'];
        } elseif (!empty($stored['api_password'])) {
            $api_password = (string) $stored['api_password'];
        }

        $widget_api_key = '';
        if (!empty($packetery['api_key'])) {
            $widget_api_key = (string) $packetery['api_key'];
        } elseif (!empty($stored['widget_api_key'])) {
            $widget_api_key = (string) $stored['widget_api_key'];
        }

        $eshop = 'WebGSM';
        if (!empty($packetery['sender'])) {
            $eshop = (string) $packetery['sender'];
        } elseif (!empty($stored['eshop'])) {
            $eshop = (string) $stored['eshop'];
        }

        $currency = 'RON';
        if (function_exists('get_woocommerce_currency')) {
            $currency = strtoupper((string) get_woocommerce_currency());
        } elseif (!empty($stored['default_currency'])) {
            $currency = (string) $stored['default_currency'];
        }
        if (strlen($currency) !== 3) {
            $currency = 'RON';
        }

        $rest_url = self::default_rest_url();
        if (!empty($stored['rest_url'])) {
            $rest_url = (string) $stored['rest_url'];
        }
        if ($rest_url === '') {
            $rest_url = self::default_rest_url();
        }

        return [
            'api_password' => $api_password,
            'widget_api_key' => $widget_api_key,
            'rest_url' => $rest_url,
            'eshop' => $eshop,
            'default_currency' => $currency,
            'credentials_from_packeta_plugin' => !empty($packetery['api_password']) || !empty($packetery['api_key']),
        ];
    }

    public static function packeta_plugin_settings_url(): string {
        return admin_url('admin.php?page=' . self::PACKETA_SETTINGS_PAGE);
    }

    public static function get_default_label_format(): string {
        $packetery = get_option(self::PACKETA_OPTION, []);
        if (!is_array($packetery)) {
            return 'A6 on A6';
        }
        foreach (['carrier_label_format', 'packeta_label_format'] as $key) {
            if (!empty($packetery[$key]) && is_string($packetery[$key])) {
                return (string) $packetery[$key];
            }
        }

        return 'A6 on A6';
    }

    /**
     * @return array{has_table: bool, pickup_count: int}
     */
    public static function packeta_plugin_status(): array {
        global $wpdb;
        $table = $wpdb->prefix . 'packetery_carrier';
        $has = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table;
        $count = 0;
        if ($has) {
            $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM `{$table}` WHERE `is_pickup_points` = 1");
        }

        return [
            'has_table' => $has,
            'pickup_count' => $count,
        ];
    }

    /**
     * Județe România — aceleași coduri ca la checkout (shipping_state).
     *
     * @return array<string, string> cod → denumire
     */
    public static function get_ro_counties(): array {
        return [
            '' => '-- Selectează județul --',
            'AB' => 'Alba',
            'AR' => 'Arad',
            'AG' => 'Argeș',
            'BC' => 'Bacău',
            'BH' => 'Bihor',
            'BN' => 'Bistrița-Năsăud',
            'BT' => 'Botoșani',
            'BR' => 'Brăila',
            'BV' => 'Brașov',
            'B' => 'București',
            'BZ' => 'Buzău',
            'CL' => 'Călărași',
            'CS' => 'Caraș-Severin',
            'CJ' => 'Cluj',
            'CT' => 'Constanța',
            'CV' => 'Covasna',
            'DB' => 'Dâmbovița',
            'DJ' => 'Dolj',
            'GL' => 'Galați',
            'GR' => 'Giurgiu',
            'GJ' => 'Gorj',
            'HR' => 'Harghita',
            'HD' => 'Hunedoara',
            'IL' => 'Ialomița',
            'IS' => 'Iași',
            'IF' => 'Ilfov',
            'MM' => 'Maramureș',
            'MH' => 'Mehedinți',
            'MS' => 'Mureș',
            'NT' => 'Neamț',
            'OT' => 'Olt',
            'PH' => 'Prahova',
            'SJ' => 'Sălaj',
            'SM' => 'Satu Mare',
            'SB' => 'Sibiu',
            'SV' => 'Suceava',
            'TR' => 'Teleorman',
            'TM' => 'Timiș',
            'TL' => 'Tulcea',
            'VL' => 'Vâlcea',
            'VS' => 'Vaslui',
            'VN' => 'Vrancea',
        ];
    }

    /** Packeta API: câmpul „province” (denumire județ, nu codul WC). */
    public static function ro_province_for_api(string $value): string {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $map = self::get_ro_counties();
        if (isset($map[$value]) && $map[$value] !== '') {
            return $map[$value];
        }

        foreach ($map as $code => $name) {
            if ($code === '') {
                continue;
            }
            if (strcasecmp($name, $value) === 0) {
                return $name;
            }
        }

        return sanitize_text_field($value);
    }

    public static function is_valid_ro_county_code(string $code): bool {
        $code = trim($code);
        if ($code === '') {
            return false;
        }

        return isset(self::get_ro_counties()[$code]) && $code !== '';
    }
}
