<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Mapare status API → pași vizuali (RO, fără branding Packeta pentru client).
 */
class WebGSM_Packeta_Status_Mapper {

    public const STEPS = [
        ['key' => 'created', 'label' => 'AWB creat', 'color' => '#64748b'],
        ['key' => 'pickup', 'label' => 'Așteaptă ridicare curier', 'color' => '#d97706'],
        ['key' => 'carrier', 'label' => 'La curier', 'color' => '#2563eb'],
        ['key' => 'out', 'label' => 'În livrare', 'color' => '#ea580c'],
        ['key' => 'done', 'label' => 'Livrat', 'color' => '#16a34a'],
    ];

    /**
     * @return array{step: int, percent: int, label: string, code_text: string, is_final: bool, is_problem: bool}
     */
    public static function from_api_status(int $status_code, string $code_text, string $status_text = '', bool $has_courier_awb = false): array {
        $code_text = strtolower(trim($code_text));

        $problem_codes = ['cancelled', 'returned', 'rejected by recipient', 'posted back'];
        if (in_array($code_text, $problem_codes, true)) {
            return [
                'step' => 0,
                'percent' => 100,
                'label' => self::problem_label_ro($code_text),
                'code_text' => $code_text,
                'is_final' => true,
                'is_problem' => true,
            ];
        }

        if ($code_text === 'delivered' || $status_code === 7) {
            return self::step_result(4, $code_text, true, false);
        }

        if (in_array($code_text, ['delivery attempt', 'departed'], true) || $status_code === 4) {
            return self::step_result(3, $code_text, false, false);
        }

        if ($code_text === 'ready for pickup' || $status_code === 5) {
            return self::step_result(3, $code_text, false, false);
        }

        if (in_array($code_text, ['handed to carrier', 'arrived', 'collected'], true) || in_array($status_code, [2, 6, 12], true)) {
            return self::step_result(2, $code_text, false, false);
        }

        if (in_array($code_text, ['prepared for departure'], true) || $status_code === 3) {
            return self::step_result($has_courier_awb ? 1 : 1, $code_text, false, false);
        }

        if ($code_text === 'received data' || $status_code === 1) {
            return self::step_result($has_courier_awb ? 1 : 0, $code_text, false, false);
        }

        return self::step_result(0, $code_text, false, false);
    }

    /**
     * @return array{step: int, percent: int, label: string, code_text: string, is_final: bool, is_problem: bool}
     */
    private static function step_result(int $step, string $code_text, bool $is_final, bool $is_problem): array {
        $max = count(self::STEPS) - 1;
        $step = max(0, min($max, $step));
        $percent = $is_final && !$is_problem ? 100 : (int) round(($step / $max) * 100);

        return [
            'step' => $step,
            'percent' => $percent,
            'label' => self::step_label_ro($step, $code_text),
            'code_text' => $code_text,
            'is_final' => $is_final,
            'is_problem' => $is_problem,
        ];
    }

    public static function step_label_ro(int $step, string $code_text = ''): string {
        $code_text = strtolower(trim($code_text));
        if ($step === 2 && in_array($code_text, ['arrived', 'collected'], true)) {
            return 'În depozit curier';
        }
        if ($step === 1 && $code_text === 'received data') {
            return 'Așteaptă ridicare curier';
        }

        return self::STEPS[$step]['label'] ?? self::STEPS[0]['label'];
    }

    public static function step_color(int $step, bool $is_problem = false, bool $is_delivered = false): string {
        if ($is_problem) {
            return '#dc2626';
        }
        if ($is_delivered) {
            return self::STEPS[4]['color'];
        }

        return self::STEPS[$step]['color'] ?? self::STEPS[0]['color'];
    }

    public static function problem_label_ro(string $code_text): string {
        $map = [
            'cancelled' => 'Expediție anulată',
            'returned' => 'Returnat',
            'rejected by recipient' => 'Refuzat la livrare',
            'posted back' => 'Returnat expeditorului',
        ];

        return $map[$code_text] ?? 'Problemă la livrare';
    }

    /** @deprecated Folosește step_label_ro */
    public static function label_for_code_text(string $code_text): string {
        $map = [
            'received data' => 'AWB creat',
            'arrived' => 'În depozit curier',
            'collected' => 'În depozit curier',
            'prepared for departure' => 'Așteaptă ridicare curier',
            'departed' => 'În livrare',
            'handed to carrier' => 'La curier',
            'ready for pickup' => 'În livrare',
            'delivery attempt' => 'În livrare',
            'delivered' => 'Livrat',
            'returned' => 'Returnat',
            'cancelled' => 'Anulat',
            'rejected by recipient' => 'Refuzat la livrare',
            'unknown' => 'Status necunoscut',
        ];

        return $map[$code_text] ?? self::step_label_ro(0, $code_text);
    }
}
