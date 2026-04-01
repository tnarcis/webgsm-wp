<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Curieri Packeta afișați ca la checkout: doar metode activate în zone + indiciu preț din setările carrier din site.
 */
class WebGSM_Packeta_Carriers {

    /**
     * ID-uri carrier din metode WC activate (packeta_method_{id}).
     *
     * @return string[]
     */
    public static function get_enabled_carrier_ids_from_checkout(): array {
        if (!class_exists('WC_Shipping_Zone')) {
            return [];
        }

        $ids = [];
        $zones = WC_Shipping_Zones::get_zones();
        $zones[0] = (new WC_Shipping_Zone(0))->get_data();

        foreach ($zones as $zone_data) {
            $zone = new WC_Shipping_Zone($zone_data['id'] ?? 0);
            foreach ($zone->get_shipping_methods(true) as $method) {
                $wc_id = strtolower($method->id . ':' . $method->instance_id);
                if (preg_match('/^packeta_method_(\d+):/', $wc_id, $m)) {
                    $ids[$m[1]] = true;
                }
            }
        }

        return array_keys($ids);
    }

    /**
     * Indiciu preț din opțiunea Packeta (același lucru ca la calculul din magazin).
     */
    public static function get_pricing_hint_from_site(string $carrier_id): string {
        $data = get_option('packetery_carrier_' . $carrier_id, null);
        if (!is_array($data)) {
            return '';
        }

        $hints = [];

        if (isset($data['free_shipping_limit']) && is_numeric($data['free_shipping_limit']) && (float) $data['free_shipping_limit'] > 0) {
            $v = wc_format_decimal((string) $data['free_shipping_limit']);
            $hints[] = sprintf('Gratuit coș ≥ %s lei', $v);
        }

        $limits = $data['weight_limits'] ?? $data['weightLimits'] ?? null;
        if (is_array($limits)) {
            $min_price = null;
            foreach ($limits as $rule) {
                if (!is_array($rule)) {
                    continue;
                }
                if (!isset($rule['price'])) {
                    continue;
                }
                $p = (float) $rule['price'];
                if ($p >= 0 && ($min_price === null || $p < $min_price)) {
                    $min_price = $p;
                }
            }
            if ($min_price !== null && function_exists('wc_price')) {
                $hints[] = 'de la ' . wp_strip_all_tags(wc_price($min_price));
            } elseif ($min_price !== null) {
                $hints[] = 'de la ' . number_format($min_price, 2, ',', '') . ' lei';
            }
        }

        return implode(' · ', array_filter($hints));
    }

    /**
     * @return array<int, array{
     *   carrier_id: string,
     *   title: string,
     *   vendor: array<string, string>,
     *   is_pickup: bool,
     *   pricing_hint: string,
     *   wc_method_id: string
     * }>
     */
    public static function get_checkout_carriers(): array {
        global $wpdb;
        $table = $wpdb->prefix . 'packetery_carrier';

        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) {
            return [];
        }

        $enabled = self::get_enabled_carrier_ids_from_checkout();
        if ($enabled === []) {
            return [];
        }

        $titles = self::wc_titles_by_carrier_id();
        $out = [];

        foreach ($enabled as $cid) {
            $row = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM `{$table}` WHERE `id` = %d", $cid),
                ARRAY_A
            );
            if (!$row) {
                continue;
            }

            $name = '';
            foreach (['name', 'title', 'label'] as $col) {
                if (!empty($row[$col])) {
                    $name = (string) $row[$col];
                    break;
                }
            }

            $title = $titles[$cid] ?? $name;
            if ($title === '') {
                $title = sprintf('Carrier %s', $cid);
            }

            $is_pickup = !empty($row['is_pickup_points']);
            $vendor = self::build_widget_vendor($row, (string) $cid);
            $hint = self::get_pricing_hint_from_site((string) $cid);
            $wc_method_id = 'packeta_method_' . $cid;

            $out[] = [
                'carrier_id' => (string) $cid,
                'title' => $title,
                'vendor' => $vendor,
                'is_pickup' => $is_pickup,
                'pricing_hint' => $hint,
                'wc_method_id' => $wc_method_id,
            ];
        }

        usort(
            $out,
            static function ($a, $b) {
                return strcasecmp($a['title'], $b['title']);
            }
        );

        return apply_filters('webgsm_packeta_admin_checkout_carriers', $out);
    }

    /**
     * @deprecated Folosește get_checkout_carriers()
     * @return array<int, array{carrier_id: string, title: string, vendor: array<string, string>}>
     */
    public static function get_pickup_carriers(): array {
        $all = self::get_checkout_carriers();

        return array_values(
            array_filter(
                $all,
                static function ($c) {
                    return !empty($c['is_pickup']);
                }
            )
        );
    }

    /**
     * @return array<string, string> carrier_id => titlu afișat în checkout
     */
    private static function wc_titles_by_carrier_id(): array {
        if (!class_exists('WC_Shipping_Zone')) {
            return [];
        }

        $titles = [];
        $zones = WC_Shipping_Zones::get_zones();
        $zones[0] = (new WC_Shipping_Zone(0))->get_data();

        foreach ($zones as $zone_data) {
            $zone = new WC_Shipping_Zone($zone_data['id'] ?? 0);
            foreach ($zone->get_shipping_methods(true) as $method) {
                $wc_id = strtolower($method->id . ':' . $method->instance_id);
                if (preg_match('/^packeta_method_(\d+):/', $wc_id, $m)) {
                    $titles[$m[1]] = $method->get_title();
                }
            }
        }

        return $titles;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, string>
     */
    private static function build_widget_vendor(array $row, string $carrier_table_id): array {
        $filtered = apply_filters('webgsm_packeta_admin_widget_vendor', null, $row, $carrier_table_id);
        if (is_array($filtered) && $filtered !== []) {
            return $filtered;
        }

        if (!empty($row['carrier_id']) && (string) $row['carrier_id'] !== $carrier_table_id) {
            return ['carrierId' => (string) $row['carrier_id']];
        }

        return ['carrierId' => $carrier_table_id];
    }
}
