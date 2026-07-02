<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Tarife contract Packeta RO — pricelist 2026-07-02 (fără TVA).
 *
 * @see Lista de prețuri webgsm, valabilă din 2.7.2026
 */
class WebGSM_Packeta_Ro_Pricelist {

    public const EFFECTIVE_FROM = '2026-07-02';
    public const VAT_RATE = 0.21;

    /**
     * carrier_id Packeta => cheie internă grilă.
     *
     * @return array<string, string>
     */
    public static function carrier_map(): array {
        return [
            '7397' => 'sameday_hd',
            '7455' => 'sameday_box',
            '762' => 'fan_hd',
            '32428' => 'fan_box',
            '590' => 'cargus_hd',
            '4161' => 'packeta_hd',
        ];
    }

    /**
     * @return array<string, array{
     *   label: string,
     *   tiers: array<int, array{max_kg: float, net: float, label?: string}>,
     *   cod_note: string
     * }>
     */
    public static function grids(): array {
        return [
            'sameday_hd' => [
                'label' => 'Sameday — livrare la adresă (HD)',
                'tiers' => [
                    ['max_kg' => 2, 'net' => 17.85, 'label' => '0–2 kg'],
                    ['max_kg' => 3, 'net' => 22.50, 'label' => '2–3 kg'],
                    ['max_kg' => 5, 'net' => 22.90, 'label' => '3–5 kg'],
                    ['max_kg' => 10, 'net' => 31.50, 'label' => '5–10 kg'],
                    ['max_kg' => 15, 'net' => 39.80, 'label' => '10–15 kg'],
                    ['max_kg' => 20, 'net' => 48.00, 'label' => '15–20 kg'],
                    ['max_kg' => 25, 'net' => 53.00, 'label' => '20–25 kg'],
                    ['max_kg' => 30, 'net' => 58.00, 'label' => '25–30 kg'],
                ],
                'cod_note' => 'Ramburs ≤ 3.500 RON: 2,00 RON (fără TVA). Asigurare max. 3.500 RON.',
            ],
            'sameday_box' => [
                'label' => 'Sameday Box / Easybox',
                'tiers' => [
                    ['max_kg' => 3, 'net' => 12.00, 'label' => '0–3 kg'],
                    ['max_kg' => 5, 'net' => 14.00, 'label' => '3–5 kg'],
                    ['max_kg' => 10, 'net' => 18.00, 'label' => '5–10 kg'],
                    ['max_kg' => 15, 'net' => 20.00, 'label' => '10–15 kg'],
                ],
                'cod_note' => 'Ramburs: gratuit (0%). Asigurare max. 3.500 RON.',
            ],
            'fan_hd' => [
                'label' => 'Fan Courier — livrare la adresă (HD)',
                'tiers' => [
                    ['max_kg' => 3, 'net' => 14.00, 'label' => '0–3 kg'],
                    ['max_kg' => 5, 'net' => 16.50, 'label' => '3–5 kg'],
                    ['max_kg' => 10, 'net' => 23.05, 'label' => '5–10 kg'],
                    ['max_kg' => 15, 'net' => 29.50, 'label' => '10–15 kg'],
                    ['max_kg' => 20, 'net' => 36.05, 'label' => '15–20 kg'],
                    ['max_kg' => 25, 'net' => 42.50, 'label' => '20–25 kg'],
                    ['max_kg' => 30, 'net' => 49.10, 'label' => '25–30 kg'],
                ],
                'cod_note' => 'Ramburs ≤ 3.500 RON: 2,60 RON (fără TVA).',
            ],
            'fan_box' => [
                'label' => 'Fan Box',
                'tiers' => [
                    ['max_kg' => 5, 'net' => 8.50, 'label' => '0–5 kg'],
                    ['max_kg' => 10, 'net' => 11.50, 'label' => '5–10 kg'],
                    ['max_kg' => 15, 'net' => 18.00, 'label' => '10–15 kg'],
                ],
                'cod_note' => 'Ramburs: 0,8% din valoare.',
            ],
            'cargus_hd' => [
                'label' => 'Cargus — livrare la adresă (HD)',
                'tiers' => [
                    ['max_kg' => 3, 'net' => 13.50, 'label' => '0–3 kg'],
                    ['max_kg' => 5, 'net' => 15.00, 'label' => '3–5 kg'],
                    ['max_kg' => 10, 'net' => 18.00, 'label' => '5–10 kg'],
                    ['max_kg' => 15, 'net' => 22.00, 'label' => '10–15 kg'],
                    ['max_kg' => 20, 'net' => 28.00, 'label' => '15–20 kg'],
                    ['max_kg' => 25, 'net' => 33.00, 'label' => '20–25 kg'],
                    ['max_kg' => 30, 'net' => 38.00, 'label' => '25–30 kg'],
                ],
                'cod_note' => 'Ramburs ≤ 3.500 RON: 1,60 RON (fără TVA).',
            ],
            'packeta_hd' => [
                'label' => 'Packeta — livrare la adresă (HD)',
                'tiers' => [
                    ['max_kg' => 1, 'net' => 17.00, 'label' => '0–1 kg'],
                    ['max_kg' => 2, 'net' => 18.50, 'label' => '1–2 kg'],
                    ['max_kg' => 3, 'net' => 19.50, 'label' => '2–3 kg'],
                    ['max_kg' => 4, 'net' => 20.50, 'label' => '3–4 kg'],
                    ['max_kg' => 5, 'net' => 24.00, 'label' => '4–5 kg'],
                    ['max_kg' => 10, 'net' => 33.00, 'label' => '5–10 kg'],
                    ['max_kg' => 15, 'net' => 41.00, 'label' => '10–15 kg'],
                    ['max_kg' => 20, 'net' => 46.00, 'label' => '15–20 kg'],
                    ['max_kg' => 25, 'net' => 51.00, 'label' => '20–25 kg'],
                    ['max_kg' => 30, 'net' => 56.00, 'label' => '25–30 kg'],
                ],
                'cod_note' => 'Ramburs ≤ 3.500 RON: 3,00 RON (fără TVA).',
            ],
            'z_point_pp' => [
                'label' => 'Packeta Z-Point (puncte)',
                'tiers' => [
                    ['max_kg' => 5, 'net' => 7.50, 'label' => '0–5 kg'],
                    ['max_kg' => 10, 'net' => 15.81, 'label' => '5–10 kg'],
                    ['max_kg' => 15, 'net' => 16.87, 'label' => '10–15 kg'],
                ],
                'cod_note' => 'Ramburs ≤ 3.500 RON: 1,00 RON · ≤ 8.000 RON: 10,54 RON (fără TVA).',
            ],
            'z_box' => [
                'label' => 'Packeta Z-Box',
                'tiers' => [
                    ['max_kg' => 5, 'net' => 7.50, 'label' => '0–5 kg'],
                    ['max_kg' => 10, 'net' => 15.81, 'label' => '5–10 kg'],
                    ['max_kg' => 15, 'net' => 16.87, 'label' => '10–15 kg'],
                ],
                'cod_note' => 'Ramburs ≤ 3.500 RON: 1,00 RON · ≤ 8.000 RON: 10,54 RON (fără TVA).',
            ],
        ];
    }

    public static function net_to_gross(float $net): float {
        return round($net * (1 + self::VAT_RATE), 2);
    }

    /**
     * @return array<int, array{weight: float, price: float}>
     */
    public static function weight_limits_for_checkout(string $grid_key, bool $with_vat = true): array {
        $grids = self::grids();
        if (!isset($grids[$grid_key])) {
            return [];
        }

        $out = [];
        foreach ($grids[$grid_key]['tiers'] as $tier) {
            $price = $with_vat ? self::net_to_gross((float) $tier['net']) : (float) $tier['net'];
            $out[] = [
                'weight' => (float) $tier['max_kg'],
                'price' => $price,
            ];
        }

        return $out;
    }

    public static function grid_key_for_carrier_id(string $carrier_id): ?string {
        $map = self::carrier_map();

        return $map[$carrier_id] ?? null;
    }
}
