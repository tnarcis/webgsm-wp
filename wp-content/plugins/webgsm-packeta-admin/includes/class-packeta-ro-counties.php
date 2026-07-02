<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Județe România — aceleași coduri ca la checkout (shipping_state).
 */
class WebGSM_Packeta_Ro_Counties {

    /**
     * @return array<string, string> cod ISO → denumire
     */
    public static function get_options(): array {
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

    /**
     * Packeta API: câmpul este „province” (denumire județ, nu codul WC).
     */
    public static function province_for_api(string $value): string {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $map = self::get_options();
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

    public static function is_valid_code(string $code): bool {
        $code = trim($code);
        if ($code === '') {
            return false;
        }

        return isset(self::get_options()[$code]) && $code !== '';
    }
}
