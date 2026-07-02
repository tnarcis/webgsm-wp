<?php
if (!defined('ABSPATH')) {
    exit;
}

class WebGSM_Packeta_Carrier_Pricing_Sync {

    /**
     * Actualizează weight_limits în opțiunile Packeta pentru curierii activi în WooCommerce.
     *
     * @return array{updated: string[], skipped: string[], errors: string[]}
     */
    public static function sync_active_carriers(bool $with_vat = true): array {
        $result = [
            'updated' => [],
            'skipped' => [],
            'errors' => [],
        ];

        $enabled = WebGSM_Packeta_Carriers::get_enabled_carrier_ids_from_checkout();
        if ($enabled === []) {
            $result['errors'][] = 'Nu există curieri Packeta activi în zonele de livrare WooCommerce.';

            return $result;
        }

        foreach ($enabled as $carrier_id) {
            $carrier_id = (string) $carrier_id;
            $grid_key = WebGSM_Packeta_Ro_Pricelist::grid_key_for_carrier_id($carrier_id);
            if ($grid_key === null) {
                $result['skipped'][] = sprintf('Carrier %s — fără grilă în pricelist 2026.', $carrier_id);
                continue;
            }

            $limits = WebGSM_Packeta_Ro_Pricelist::weight_limits_for_checkout($grid_key, $with_vat);
            if ($limits === []) {
                $result['skipped'][] = sprintf('Carrier %s — grilă goală.', $carrier_id);
                continue;
            }

            $option_key = 'packetery_carrier_' . $carrier_id;
            $data = get_option($option_key, null);
            if (!is_array($data)) {
                $data = [
                    'active' => true,
                    'id' => $carrier_id,
                    'pricing_type' => 'byWeight',
                ];
            }

            $data['pricing_type'] = 'byWeight';
            $data['weight_limits'] = $limits;
            if (empty($data['name'])) {
                $grids = WebGSM_Packeta_Ro_Pricelist::grids();
                $data['name'] = $grids[$grid_key]['label'] ?? ('Carrier ' . $carrier_id);
            }

            update_option($option_key, $data);
            $result['updated'][] = sprintf(
                '%s (ID %s) — %d intervale greutate, de la %s lei%s',
                $data['name'],
                $carrier_id,
                count($limits),
                number_format((float) $limits[0]['price'], 2, ',', ''),
                $with_vat ? ' cu TVA 21%' : ' fără TVA'
            );
        }

        update_option('webgsm_packeta_pricelist_synced_at', gmdate('c'));
        update_option('webgsm_packeta_pricelist_version', WebGSM_Packeta_Ro_Pricelist::EFFECTIVE_FROM);

        return $result;
    }
}
