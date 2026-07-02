<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Alias — logica e în WebGSM_Packeta_Config (fișier mereu încărcat la bootstrap).
 */
class WebGSM_Packeta_Ro_Counties {

    public static function get_options(): array {
        return WebGSM_Packeta_Config::get_ro_counties();
    }

    public static function province_for_api(string $value): string {
        return WebGSM_Packeta_Config::ro_province_for_api($value);
    }

    public static function is_valid_code(string $code): bool {
        return WebGSM_Packeta_Config::is_valid_ro_county_code($code);
    }
}
