<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Tarife contract Packeta RO — client webgsm, listă 24.10.2026 (fără TVA).
 *
 * Sursă: pricelist Packeta România pentru webgsm. În UI afișăm și cu TVA 21%.
 */
class WebGSM_Packeta_Ro_Pricelist {

    public const EFFECTIVE_FROM = '2026-10-24';
    public const VAT_RATE = 0.21;

    /**
     * carrier_id Packeta => cheie internă grilă (doar curieri din contractul RO).
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
            'rozbox' => 'z_box',
            'rozpoint' => 'z_point_pp',
            '39233' => 'ro_post_hd',
            '39234' => 'ro_post_pp',
            '4329' => 'nl_dutch_post',
            '8000' => 'nl_dhl_hd',
            '8001' => 'nl_dhl_pp',
            '13613' => 'de_home_hd',
            '6373' => 'de_hermes_hd',
            '6828' => 'de_hermes_pp',
            '16080' => 'fr_colis_prive_hd',
            '4309' => 'fr_colissimo_hd',
            '4307' => 'fr_colissimo_pp',
            '12889' => 'fr_mondial_relay_pp',
        ];
    }

    /** Hint UI: întâi cu TVA, apoi fără (ca în listă). */
    public static function format_price_hint(float $net_ex_vat, string $weight_note = '1 kg'): string {
        return sprintf(
            'de la ~%s lei cu TVA (%s fără TVA, %s)',
            number_format(self::net_to_gross($net_ex_vat), 2, ',', ''),
            number_format($net_ex_vat, 2, ',', ''),
            $weight_note
        );
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
                'label' => 'RO Sameday HD',
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
                'cod_note' => 'Contract 24.10.2026 (fără TVA). Ramburs ≤ 3.500 RON: 2,00 RON. Asigurare max. 3.500 RON.',
            ],
            'sameday_box' => [
                'label' => 'RO Sameday Box / Easybox',
                'tiers' => [
                    ['max_kg' => 3, 'net' => 12.00, 'label' => '0–3 kg'],
                    ['max_kg' => 5, 'net' => 14.00, 'label' => '3–5 kg'],
                    ['max_kg' => 10, 'net' => 18.00, 'label' => '5–10 kg'],
                    ['max_kg' => 15, 'net' => 20.00, 'label' => '10–15 kg'],
                ],
                'cod_note' => 'Contract 24.10.2026 (fără TVA). Ramburs: gratuit. Asigurare max. 3.500 RON.',
            ],
            'fan_hd' => [
                'label' => 'RO FAN Courier HD',
                'tiers' => [
                    ['max_kg' => 3, 'net' => 14.00, 'label' => '0–3 kg'],
                    ['max_kg' => 5, 'net' => 16.50, 'label' => '3–5 kg'],
                    ['max_kg' => 10, 'net' => 23.05, 'label' => '5–10 kg'],
                    ['max_kg' => 15, 'net' => 29.50, 'label' => '10–15 kg'],
                    ['max_kg' => 20, 'net' => 36.05, 'label' => '15–20 kg'],
                    ['max_kg' => 25, 'net' => 42.50, 'label' => '20–25 kg'],
                    ['max_kg' => 30, 'net' => 49.10, 'label' => '25–30 kg'],
                ],
                'cod_note' => 'Contract 24.10.2026 (fără TVA). Ramburs ≤ 3.500 RON: 2,60 RON.',
            ],
            'fan_box' => [
                'label' => 'RO FAN Courier Box',
                'tiers' => [
                    ['max_kg' => 5, 'net' => 8.50, 'label' => '0–5 kg'],
                    ['max_kg' => 10, 'net' => 11.50, 'label' => '5–10 kg'],
                    ['max_kg' => 15, 'net' => 18.00, 'label' => '10–15 kg'],
                ],
                'cod_note' => 'Contract 24.10.2026 (fără TVA). Ramburs: 0,8% din valoare.',
            ],
            'cargus_hd' => [
                'label' => 'RO Cargus HD',
                'tiers' => [
                    ['max_kg' => 3, 'net' => 13.50, 'label' => '0–3 kg'],
                    ['max_kg' => 5, 'net' => 15.00, 'label' => '3–5 kg'],
                    ['max_kg' => 10, 'net' => 18.00, 'label' => '5–10 kg'],
                    ['max_kg' => 15, 'net' => 22.00, 'label' => '10–15 kg'],
                    ['max_kg' => 20, 'net' => 28.00, 'label' => '15–20 kg'],
                    ['max_kg' => 25, 'net' => 33.00, 'label' => '20–25 kg'],
                    ['max_kg' => 30, 'net' => 38.00, 'label' => '25–30 kg'],
                ],
                'cod_note' => 'Contract 24.10.2026 (fără TVA). Ramburs ≤ 3.500 RON: 1,60 RON.',
            ],
            'packeta_hd' => [
                'label' => 'RO Packeta Home Delivery HD',
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
                'cod_note' => 'Contract 24.10.2026 (fără TVA). Ramburs ≤ 3.500 RON: 3,00 RON.',
            ],
            'ro_post_hd' => [
                'label' => 'RO Romanian Post HD',
                'tiers' => [
                    ['max_kg' => 3, 'net' => 15.64, 'label' => '0–3 kg'],
                    ['max_kg' => 4, 'net' => 16.62, 'label' => '3–4 kg'],
                    ['max_kg' => 5, 'net' => 17.59, 'label' => '4–5 kg'],
                    ['max_kg' => 6, 'net' => 18.57, 'label' => '5–6 kg'],
                    ['max_kg' => 7, 'net' => 19.54, 'label' => '6–7 kg'],
                    ['max_kg' => 8, 'net' => 20.52, 'label' => '7–8 kg'],
                    ['max_kg' => 9, 'net' => 21.49, 'label' => '8–9 kg'],
                    ['max_kg' => 10, 'net' => 22.47, 'label' => '9–10 kg'],
                    ['max_kg' => 15, 'net' => 27.34, 'label' => '10–15 kg'],
                    ['max_kg' => 20, 'net' => 32.22, 'label' => '15–20 kg'],
                    ['max_kg' => 25, 'net' => 37.09, 'label' => '20–25 kg'],
                    ['max_kg' => 30, 'net' => 41.97, 'label' => '25–30 kg'],
                ],
                'cod_note' => 'Contract 24.10.2026 (fără TVA). Ramburs ≤ 3.500 RON: 4,00 RON.',
            ],
            'ro_post_pp' => [
                'label' => 'RO Romanian Post PP',
                'tiers' => [
                    ['max_kg' => 5, 'net' => 12.64, 'label' => '0–5 kg'],
                    ['max_kg' => 6, 'net' => 13.54, 'label' => '5–6 kg'],
                    ['max_kg' => 7, 'net' => 14.44, 'label' => '6–7 kg'],
                    ['max_kg' => 8, 'net' => 15.34, 'label' => '7–8 kg'],
                    ['max_kg' => 9, 'net' => 16.24, 'label' => '8–9 kg'],
                    ['max_kg' => 10, 'net' => 17.14, 'label' => '9–10 kg'],
                    ['max_kg' => 15, 'net' => 21.64, 'label' => '10–15 kg'],
                    ['max_kg' => 20, 'net' => 26.14, 'label' => '15–20 kg'],
                    ['max_kg' => 25, 'net' => 30.64, 'label' => '20–25 kg'],
                    ['max_kg' => 30, 'net' => 35.14, 'label' => '25–30 kg'],
                ],
                'cod_note' => 'Contract 24.10.2026 (fără TVA). Ramburs ≤ 3.500 RON: 4,00 RON.',
            ],
            'z_point_pp' => [
                'label' => 'RO Packeta Z-Point PP',
                'tiers' => [
                    ['max_kg' => 5, 'net' => 7.50, 'label' => '0–5 kg'],
                    ['max_kg' => 10, 'net' => 15.81, 'label' => '5–10 kg'],
                    ['max_kg' => 15, 'net' => 16.87, 'label' => '10–15 kg'],
                ],
                'cod_note' => 'Contract 24.10.2026 (fără TVA). Ramburs ≤ 3.500 RON: 1,00 RON · ≤ 8.000 RON: 10,54 RON.',
            ],
            'z_box' => [
                'label' => 'RO Packeta Z-Box',
                'tiers' => [
                    ['max_kg' => 5, 'net' => 7.50, 'label' => '0–5 kg'],
                    ['max_kg' => 10, 'net' => 15.81, 'label' => '5–10 kg'],
                    ['max_kg' => 15, 'net' => 16.87, 'label' => '10–15 kg'],
                ],
                'cod_note' => 'Contract 24.10.2026 (fără TVA). Ramburs ≤ 3.500 RON: 1,00 RON · ≤ 8.000 RON: 10,54 RON.',
            ],
            'nl_dutch_post' => [
                'label' => 'Olanda — NL Dutch Post HD (PostNL)',
                'tiers' => [
                    ['max_kg' => 0.5, 'net' => 55.00, 'label' => '0–0,5 kg'],
                    ['max_kg' => 1, 'net' => 57.00, 'label' => '0,5–1 kg'],
                    ['max_kg' => 2, 'net' => 60.00, 'label' => '1–2 kg'],
                    ['max_kg' => 5, 'net' => 73.00, 'label' => '2–5 kg'],
                    ['max_kg' => 10, 'net' => 95.00, 'label' => '5–10 kg'],
                    ['max_kg' => 15, 'net' => 115.00, 'label' => '10–15 kg'],
                ],
                'cod_note' => 'Contract webgsm 24.10.2026 (fără TVA). Ramburs indisponibil.',
            ],
            'nl_dhl_hd' => [
                'label' => 'Olanda — NL DHL HD',
                'tiers' => [
                    ['max_kg' => 1, 'net' => 55.00, 'label' => '0–1 kg'],
                    ['max_kg' => 2, 'net' => 56.00, 'label' => '1–2 kg'],
                    ['max_kg' => 5, 'net' => 61.00, 'label' => '2–5 kg'],
                    ['max_kg' => 10, 'net' => 63.00, 'label' => '5–10 kg'],
                    ['max_kg' => 15, 'net' => 75.00, 'label' => '10–15 kg'],
                ],
                'cod_note' => 'Contract webgsm 24.10.2026 (fără TVA). Ramburs indisponibil.',
            ],
            'nl_dhl_pp' => [
                'label' => 'Olanda — NL DHL PP',
                'tiers' => [
                    ['max_kg' => 1, 'net' => 52.00, 'label' => '0–1 kg'],
                    ['max_kg' => 2, 'net' => 54.00, 'label' => '1–2 kg'],
                    ['max_kg' => 5, 'net' => 56.00, 'label' => '2–5 kg'],
                    ['max_kg' => 10, 'net' => 60.00, 'label' => '5–10 kg'],
                    ['max_kg' => 15, 'net' => 75.00, 'label' => '10–15 kg'],
                ],
                'cod_note' => 'Contract webgsm 24.10.2026 (fără TVA). Ramburs indisponibil.',
            ],
            'de_home_hd' => [
                'label' => 'Germania — DE Home Delivery HD',
                'tiers' => [
                    ['max_kg' => 1, 'net' => 32.00, 'label' => '0–1 kg'],
                    ['max_kg' => 2, 'net' => 35.00, 'label' => '1–2 kg'],
                    ['max_kg' => 5, 'net' => 39.00, 'label' => '2–5 kg'],
                    ['max_kg' => 10, 'net' => 44.00, 'label' => '5–10 kg'],
                    ['max_kg' => 15, 'net' => 55.00, 'label' => '10–15 kg'],
                ],
                'cod_note' => 'Contract webgsm 24.10.2026 (fără TVA). Ramburs listă ≤700 EUR: 32 RON — la creare AWB lăsăm COD 0 dacă API respinge.',
            ],
            'de_hermes_hd' => [
                'label' => 'Germania — DE Hermes HD',
                'tiers' => [
                    ['max_kg' => 1, 'net' => 36.00, 'label' => '0–1 kg'],
                    ['max_kg' => 2, 'net' => 37.50, 'label' => '1–2 kg'],
                    ['max_kg' => 5, 'net' => 41.00, 'label' => '2–5 kg'],
                    ['max_kg' => 10, 'net' => 45.50, 'label' => '5–10 kg'],
                    ['max_kg' => 15, 'net' => 70.00, 'label' => '10–15 kg'],
                ],
                'cod_note' => 'Contract webgsm 24.10.2026 (fără TVA). Ramburs listă ≤700 EUR: 39 RON.',
            ],
            'de_hermes_pp' => [
                'label' => 'Germania — DE Hermes PP',
                'tiers' => [
                    ['max_kg' => 1, 'net' => 33.00, 'label' => '0–1 kg'],
                    ['max_kg' => 2, 'net' => 36.00, 'label' => '1–2 kg'],
                    ['max_kg' => 5, 'net' => 40.00, 'label' => '2–5 kg'],
                    ['max_kg' => 10, 'net' => 45.00, 'label' => '5–10 kg'],
                    ['max_kg' => 15, 'net' => 68.00, 'label' => '10–15 kg'],
                ],
                'cod_note' => 'Contract webgsm 24.10.2026 (fără TVA). Ramburs: indisponibil. Puncte PP (nu HD).',
            ],
            'fr_colis_prive_hd' => [
                'label' => 'Franța — FR Colis Privé HD',
                'tiers' => [
                    ['max_kg' => 0.5, 'net' => 46.00, 'label' => '0–0,5 kg'],
                    ['max_kg' => 1, 'net' => 46.00, 'label' => '0,5–1 kg'],
                    ['max_kg' => 2, 'net' => 51.50, 'label' => '1–2 kg'],
                    ['max_kg' => 5, 'net' => 56.00, 'label' => '2–5 kg'],
                    ['max_kg' => 10, 'net' => 65.00, 'label' => '5–10 kg'],
                    ['max_kg' => 15, 'net' => 79.00, 'label' => '10–15 kg'],
                ],
                'cod_note' => 'Contract webgsm 24.10.2026 (fără TVA). Ramburs: de obicei indisponibil la FR.',
            ],
            'fr_colissimo_hd' => [
                'label' => 'Franța — FR Colissimo HD',
                'tiers' => [
                    ['max_kg' => 0.25, 'net' => 45.00, 'label' => '0–0,25 kg'],
                    ['max_kg' => 0.5, 'net' => 50.00, 'label' => '0,25–0,5 kg'],
                    ['max_kg' => 0.75, 'net' => 51.00, 'label' => '0,5–0,75 kg'],
                    ['max_kg' => 1, 'net' => 51.00, 'label' => '0,75–1 kg'],
                    ['max_kg' => 2, 'net' => 55.00, 'label' => '1–2 kg'],
                    ['max_kg' => 5, 'net' => 69.00, 'label' => '2–5 kg'],
                    ['max_kg' => 10, 'net' => 93.00, 'label' => '5–10 kg'],
                    ['max_kg' => 15, 'net' => 115.00, 'label' => '10–15 kg'],
                ],
                'cod_note' => 'Contract webgsm 24.10.2026 (fără TVA). Ramburs: indisponibil.',
            ],
            'fr_colissimo_pp' => [
                'label' => 'Franța — FR Colissimo PP',
                'tiers' => [
                    ['max_kg' => 0.25, 'net' => 40.00, 'label' => '0–0,25 kg'],
                    ['max_kg' => 0.5, 'net' => 45.00, 'label' => '0,25–0,5 kg'],
                    ['max_kg' => 0.75, 'net' => 50.00, 'label' => '0,5–0,75 kg'],
                    ['max_kg' => 1, 'net' => 55.00, 'label' => '0,75–1 kg'],
                    ['max_kg' => 2, 'net' => 60.00, 'label' => '1–2 kg'],
                    ['max_kg' => 5, 'net' => 78.00, 'label' => '2–5 kg'],
                    ['max_kg' => 10, 'net' => 105.00, 'label' => '5–10 kg'],
                    ['max_kg' => 15, 'net' => 130.00, 'label' => '10–15 kg'],
                ],
                'cod_note' => 'Contract webgsm 24.10.2026 (fără TVA). Puncte PP. Ramburs: indisponibil.',
            ],
            'fr_mondial_relay_pp' => [
                'label' => 'Franța — FR Mondial Relay PP',
                'tiers' => [
                    ['max_kg' => 1, 'net' => 34.00, 'label' => '0–1 kg'],
                    ['max_kg' => 2, 'net' => 36.00, 'label' => '1–2 kg'],
                    ['max_kg' => 5, 'net' => 44.00, 'label' => '2–5 kg'],
                    ['max_kg' => 10, 'net' => 60.50, 'label' => '5–10 kg'],
                    ['max_kg' => 15, 'net' => 85.00, 'label' => '10–15 kg'],
                ],
                'cod_note' => 'Contract webgsm 24.10.2026 (fără TVA). Puncte PP.',
            ],
        ];
    }

    public static function net_to_gross(float $net): float {
        return round($net * (1 + self::VAT_RATE), 2);
    }

    /**
     * @return array<int, array{weight: float, price: float}>
     */
    public static function weight_limits_for_checkout(string $grid_key, bool $with_vat = false): array {
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
        $carrier_id = strtolower(trim($carrier_id));
        $map = self::carrier_map();

        return $map[$carrier_id] ?? null;
    }
}
