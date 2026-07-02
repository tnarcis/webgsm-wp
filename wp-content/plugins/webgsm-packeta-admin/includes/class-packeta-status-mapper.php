<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Mapare status Packeta → pași vizuali (bară progres, stil eMAG).
 */
class WebGSM_Packeta_Status_Mapper {

    public const STEPS = [
        ['key' => 'created', 'label' => 'AWB creat'],
        ['key' => 'warehouse', 'label' => 'În rețea Packeta'],
        ['key' => 'carrier', 'label' => 'La curier'],
        ['key' => 'out', 'label' => 'În livrare'],
        ['key' => 'done', 'label' => 'Livrat'],
    ];

    /**
     * @return array{step: int, percent: int, label: string, code_text: string, is_final: bool, is_problem: bool}
     */
    public static function from_api_status(int $status_code, string $code_text, string $status_text = ''): array {
        $code_text = strtolower(trim($code_text));
        $display = $status_text !== '' ? $status_text : self::label_for_code_text($code_text);

        $problem_codes = ['cancelled', 'returned', 'rejected by recipient', 'posted back'];
        if (in_array($code_text, $problem_codes, true)) {
            return [
                'step' => 0,
                'percent' => 100,
                'label' => $display,
                'code_text' => $code_text,
                'is_final' => true,
                'is_problem' => true,
            ];
        }

        if ($code_text === 'delivered' || $status_code === 7) {
            return self::step_result(4, $display, $code_text, true, false);
        }

        if (in_array($code_text, ['delivery attempt', 'ready for pickup'], true) || $status_code === 5) {
            return self::step_result(3, $display, $code_text, false, false);
        }

        if (in_array($code_text, ['handed to carrier', 'departed'], true) || in_array($status_code, [4, 6], true)) {
            return self::step_result(2, $display, $code_text, false, false);
        }

        if (in_array($code_text, ['arrived', 'collected', 'prepared for departure'], true) || in_array($status_code, [2, 3, 12], true)) {
            return self::step_result(1, $display, $code_text, false, false);
        }

        return self::step_result(0, $display, $code_text, false, false);
    }

    /**
     * @return array{step: int, percent: int, label: string, code_text: string, is_final: bool, is_problem: bool}
     */
    private static function step_result(int $step, string $label, string $code_text, bool $is_final, bool $is_problem): array {
        $max = count(self::STEPS) - 1;
        $step = max(0, min($max, $step));
        $percent = $is_final && !$is_problem ? 100 : (int) round(($step / $max) * 100);

        return [
            'step' => $step,
            'percent' => $percent,
            'label' => $label,
            'code_text' => $code_text,
            'is_final' => $is_final,
            'is_problem' => $is_problem,
        ];
    }

    public static function label_for_code_text(string $code_text): string {
        $map = [
            'received data' => 'Date primite / AWB creat',
            'arrived' => 'Sosit în rețea',
            'collected' => 'Colectat',
            'prepared for departure' => 'Pregătit pentru plecare',
            'departed' => 'În tranzit',
            'handed to carrier' => 'Predat curierului (Sameday/Fan)',
            'ready for pickup' => 'Gata de ridicare',
            'delivery attempt' => 'Încercare livrare',
            'delivered' => 'Livrat',
            'returned' => 'Returnat',
            'cancelled' => 'Anulat',
            'rejected by recipient' => 'Refuzat de destinatar',
            'unknown' => 'Status necunoscut',
        ];

        return $map[$code_text] ?? ucfirst($code_text);
    }
}
