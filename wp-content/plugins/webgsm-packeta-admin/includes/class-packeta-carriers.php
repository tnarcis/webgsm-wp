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
        $hints = [];

        // Preferă grila contract (fără TVA) → afișează și cu TVA 21%.
        $grid_key = WebGSM_Packeta_Ro_Pricelist::grid_key_for_carrier_id($carrier_id);
        if ($grid_key !== null) {
            $grids = WebGSM_Packeta_Ro_Pricelist::grids();
            $tiers = $grids[$grid_key]['tiers'] ?? [];
            $min_net = null;
            $weight_note = '1 kg';
            foreach ($tiers as $tier) {
                if (!is_array($tier) || !isset($tier['net'])) {
                    continue;
                }
                $net = (float) $tier['net'];
                if ($min_net === null || $net < $min_net) {
                    $min_net = $net;
                    $weight_note = (string) ($tier['label'] ?? '1 kg');
                }
            }
            if ($min_net !== null) {
                $hints[] = WebGSM_Packeta_Ro_Pricelist::format_price_hint($min_net, $weight_note);
            }
        }

        $data = get_option('packetery_carrier_' . $carrier_id, null);
        if (is_array($data)
            && isset($data['free_shipping_limit'])
            && is_numeric($data['free_shipping_limit'])
            && (float) $data['free_shipping_limit'] > 0
        ) {
            $v = wc_format_decimal((string) $data['free_shipping_limit']);
            $hints[] = sprintf('Gratuit coș ≥ %s lei', $v);
        }

        // Fallback: prețuri din setările Packeta (salvate fără TVA).
        if ($hints === [] || ($hints !== [] && $grid_key === null)) {
            if (is_array($data)) {
                $limits = $data['weight_limits'] ?? $data['weightLimits'] ?? null;
                if (is_array($limits)) {
                    $min_price = null;
                    foreach ($limits as $rule) {
                        if (!is_array($rule) || !isset($rule['price'])) {
                            continue;
                        }
                        $p = (float) $rule['price'];
                        if ($p >= 0 && ($min_price === null || $p < $min_price)) {
                            $min_price = $p;
                        }
                    }
                    if ($min_price !== null) {
                        $hints[] = WebGSM_Packeta_Ro_Pricelist::format_price_hint($min_price);
                    }
                }
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
     * Ordine grupuri în dropdown AWB: RO → DE → FR → NL.
     *
     * @return array<string, int>
     */
    public static function destination_sort_order(): array {
        return [
            'RO' => 10,
            'DE' => 20,
            'FR' => 30,
            'NL' => 40,
        ];
    }

    /**
     * Curieri HD din contract (RO + internațional) — mereu în AWB, indiferent de checkout WC.
     *
     * @return array<int, array{
     *   carrier_id: string,
     *   title: string,
     *   vendor: array<string, string>,
     *   is_pickup: bool,
     *   pricing_hint: string,
     *   wc_method_id: string,
     *   destination_country: string,
     *   cod_allowed: bool,
     *   province_required: bool,
     *   manual: bool
     * }>
     */
    public static function get_manual_home_carriers(): array {
        // HD din contract Packeta webgsm (24.10.2026). Fără Italia. Fără DE DHL 39103.
        $out = [
            [
                'carrier_id' => '590',
                'title' => 'RO Cargus HD',
                'vendor' => ['carrierId' => '590'],
                'is_pickup' => false,
                'pricing_hint' => WebGSM_Packeta_Ro_Pricelist::format_price_hint(13.50, '0–3 kg'),
                'wc_method_id' => '',
                'destination_country' => 'RO',
                'cod_allowed' => true,
                'province_required' => true,
                'manual' => true,
            ],
            [
                'carrier_id' => '762',
                'title' => 'RO FAN Courier HD',
                'vendor' => ['carrierId' => '762'],
                'is_pickup' => false,
                'pricing_hint' => WebGSM_Packeta_Ro_Pricelist::format_price_hint(14.00, '0–3 kg'),
                'wc_method_id' => '',
                'destination_country' => 'RO',
                'cod_allowed' => true,
                'province_required' => true,
                'manual' => true,
            ],
            [
                'carrier_id' => '4161',
                'title' => 'RO Packeta Home Delivery HD',
                'vendor' => ['carrierId' => '4161'],
                'is_pickup' => false,
                'pricing_hint' => WebGSM_Packeta_Ro_Pricelist::format_price_hint(17.00, '0–1 kg'),
                'wc_method_id' => '',
                'destination_country' => 'RO',
                'cod_allowed' => true,
                'province_required' => true,
                'manual' => true,
            ],
            [
                'carrier_id' => '7397',
                'title' => 'RO Sameday HD',
                'vendor' => ['carrierId' => '7397'],
                'is_pickup' => false,
                'pricing_hint' => WebGSM_Packeta_Ro_Pricelist::format_price_hint(17.85, '0–2 kg'),
                'wc_method_id' => '',
                'destination_country' => 'RO',
                'cod_allowed' => true,
                'province_required' => true,
                'manual' => true,
            ],
            [
                'carrier_id' => '13613',
                'title' => 'DE Home Delivery HD',
                'vendor' => ['carrierId' => '13613'],
                'is_pickup' => false,
                'pricing_hint' => WebGSM_Packeta_Ro_Pricelist::format_price_hint(32.00),
                'wc_method_id' => '',
                'destination_country' => 'DE',
                'cod_allowed' => false,
                'province_required' => true,
                'manual' => true,
            ],
            [
                'carrier_id' => '6373',
                'title' => 'DE Hermes HD',
                'vendor' => ['carrierId' => '6373'],
                'is_pickup' => false,
                'pricing_hint' => WebGSM_Packeta_Ro_Pricelist::format_price_hint(36.00),
                'wc_method_id' => '',
                'destination_country' => 'DE',
                'cod_allowed' => false,
                'province_required' => true,
                'manual' => true,
            ],
            [
                'carrier_id' => '16080',
                'title' => 'FR Colis Privé HD',
                'vendor' => ['carrierId' => '16080'],
                'is_pickup' => false,
                'pricing_hint' => WebGSM_Packeta_Ro_Pricelist::format_price_hint(46.00),
                'wc_method_id' => '',
                'destination_country' => 'FR',
                'cod_allowed' => false,
                'province_required' => false,
                'manual' => true,
            ],
            [
                'carrier_id' => '4309',
                'title' => 'FR Colissimo HD',
                'vendor' => ['carrierId' => '4309'],
                'is_pickup' => false,
                'pricing_hint' => WebGSM_Packeta_Ro_Pricelist::format_price_hint(51.00),
                'wc_method_id' => '',
                'destination_country' => 'FR',
                'cod_allowed' => false,
                'province_required' => false,
                'manual' => true,
            ],
            [
                'carrier_id' => '8000',
                'title' => 'NL DHL HD',
                'vendor' => ['carrierId' => '8000'],
                'is_pickup' => false,
                'pricing_hint' => WebGSM_Packeta_Ro_Pricelist::format_price_hint(55.00),
                'wc_method_id' => '',
                'destination_country' => 'NL',
                'cod_allowed' => false,
                'province_required' => false,
                'manual' => true,
            ],
            [
                'carrier_id' => '4329',
                'title' => 'NL Dutch Post HD (PostNL)',
                'vendor' => ['carrierId' => '4329'],
                'is_pickup' => false,
                'pricing_hint' => WebGSM_Packeta_Ro_Pricelist::format_price_hint(57.00),
                'wc_method_id' => '',
                'destination_country' => 'NL',
                'cod_allowed' => false,
                'province_required' => false,
                'manual' => true,
            ],
        ];

        $out = apply_filters('webgsm_packeta_admin_manual_home_carriers', $out);

        return apply_filters('webgsm_packeta_admin_manual_international_home_carriers', $out);
    }

    /**
     * @deprecated Folosește get_manual_home_carriers()
     * @return array<int, array<string, mixed>>
     */
    public static function get_manual_international_home_carriers(): array {
        return self::get_manual_home_carriers();
    }

    /**
     * Lista curieri HD pentru tab AWB: contract RO/DE/FR/NL + eventual alții din checkout.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function get_awb_home_carriers(): array {
        $home = [];
        $seen = [];

        foreach (self::get_manual_home_carriers() as $c) {
            if (!empty($c['is_pickup'])) {
                continue;
            }
            $cid = (string) ($c['carrier_id'] ?? '');
            if ($cid === '' || isset($seen[$cid])) {
                continue;
            }
            $home[] = $c;
            $seen[$cid] = true;
        }

        foreach (self::get_checkout_carriers() as $c) {
            if (!empty($c['is_pickup'])) {
                continue;
            }
            $cid = (string) ($c['carrier_id'] ?? '');
            if ($cid === '' || isset($seen[$cid])) {
                continue;
            }
            $dest = strtoupper((string) ($c['destination_country'] ?? 'RO'));
            $title = (string) ($c['title'] ?? '');
            if ($dest === 'RO' && $title !== '' && !preg_match('/^RO\b/i', $title)) {
                $c['title'] = 'RO ' . $title;
            }
            $c['destination_country'] = $dest;
            $c['cod_allowed'] = $c['cod_allowed'] ?? true;
            $c['province_required'] = $c['province_required'] ?? true;
            $c['manual'] = false;
            $home[] = $c;
            $seen[$cid] = true;
        }

        $order = self::destination_sort_order();
        usort(
            $home,
            static function ($a, $b) use ($order) {
                $ca = strtoupper((string) ($a['destination_country'] ?? 'RO'));
                $cb = strtoupper((string) ($b['destination_country'] ?? 'RO'));
                $oa = $order[$ca] ?? 90;
                $ob = $order[$cb] ?? 90;
                if ($oa !== $ob) {
                    return $oa <=> $ob;
                }

                return strcasecmp((string) ($a['title'] ?? ''), (string) ($b['title'] ?? ''));
            }
        );

        return $home;
    }

    public static function destination_country_for_carrier(string $carrier_id): string {
        $carrier_id = preg_replace('/\D/', '', $carrier_id) ?? '';
        foreach (self::get_manual_home_carriers() as $c) {
            if ((string) ($c['carrier_id'] ?? '') === $carrier_id) {
                return strtoupper((string) ($c['destination_country'] ?? 'RO'));
            }
        }

        return 'RO';
    }

    public static function carrier_allows_cod(string $carrier_id): bool {
        $carrier_id = preg_replace('/\D/', '', $carrier_id) ?? '';
        foreach (self::get_manual_home_carriers() as $c) {
            if ((string) ($c['carrier_id'] ?? '') === $carrier_id) {
                return !empty($c['cod_allowed']);
            }
        }

        return true;
    }

    public static function carrier_requires_province(string $carrier_id): bool {
        $carrier_id = preg_replace('/\D/', '', $carrier_id) ?? '';
        foreach (self::get_manual_home_carriers() as $c) {
            if ((string) ($c['carrier_id'] ?? '') === $carrier_id) {
                return !empty($c['province_required']);
            }
        }

        return true;
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
