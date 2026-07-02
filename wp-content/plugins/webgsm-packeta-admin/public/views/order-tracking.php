<?php
if (!defined('ABSPATH')) {
    exit;
}
/** @var array<string, mixed> $tracking */
$steps = is_array($tracking['steps'] ?? null) ? $tracking['steps'] : WebGSM_Packeta_Status_Mapper::STEPS;
$step = (int) ($tracking['progress_step'] ?? 0);
$percent = (int) ($tracking['progress_percent'] ?? 0);
$is_problem = !empty($tracking['is_problem']);
$is_delivered = !empty($tracking['is_final']) && !$is_problem;
$fill_color = WebGSM_Packeta_Status_Mapper::step_color($step, $is_problem, $is_delivered);
$tracking_url = (string) ($tracking['tracking_url'] ?? '');
$carrier = (string) ($tracking['carrier_name'] ?? 'Curier');
$awb = (string) ($tracking['courier_number'] ?? '');
$status_label = (string) ($tracking['status_label'] ?? '');
$updated = (string) ($tracking['updated_at'] ?? '');
?>
<section class="webgsm-shipment-tracking" aria-label="Urmărire livrare">
    <h2 class="webgsm-shipment-tracking__title">Urmărire livrare</h2>
    <p class="webgsm-shipment-tracking__awb">
        AWB <strong><?php echo esc_html($carrier); ?></strong>:
        <code><?php echo esc_html($awb); ?></code>
        <?php if ($tracking_url !== '') : ?>
            — <a class="webgsm-shipment-tracking__link" href="<?php echo esc_url($tracking_url); ?>" target="_blank" rel="noopener noreferrer">
                Urmărește pe site-ul curierului
            </a>
        <?php endif; ?>
    </p>

    <div class="webgsm-shipment-tracking__track<?php echo $is_problem ? ' is-problem' : ''; ?><?php echo $is_delivered ? ' is-delivered' : ''; ?>">
        <div class="webgsm-shipment-tracking__steps">
            <?php foreach ($steps as $i => $s) :
                $cls = 'webgsm-shipment-tracking__step';
                $color = (string) ($s['color'] ?? '#64748b');
                if ($i < $step) {
                    $cls .= ' is-done';
                } elseif ($i === $step && !$is_delivered) {
                    $cls .= ' is-active';
                } elseif ($i === $step && $is_delivered) {
                    $cls .= ' is-done is-active';
                }
                ?>
                <span class="<?php echo esc_attr($cls); ?>" style="--step-color:<?php echo esc_attr($color); ?>">
                    <span class="dot"></span>
                    <span class="lbl"><?php echo esc_html((string) ($s['label'] ?? '')); ?></span>
                </span>
            <?php endforeach; ?>
        </div>
        <div class="webgsm-shipment-tracking__bar" aria-hidden="true">
            <div class="webgsm-shipment-tracking__fill" style="width:<?php echo esc_attr((string) $percent); ?>%;background:<?php echo esc_attr($fill_color); ?>"></div>
        </div>
        <p class="webgsm-shipment-tracking__status">
            <strong><?php echo esc_html($status_label); ?></strong>
            <?php if ($updated !== '') : ?>
                <span class="webgsm-shipment-tracking__updated">· actualizat <?php echo esc_html(wp_date('d.m.Y H:i', strtotime($updated))); ?></span>
            <?php endif; ?>
        </p>
    </div>
</section>
