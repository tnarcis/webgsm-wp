<?php
if (!defined('ABSPATH')) {
    exit;
}
/**
 * Rezumat tarife contract Packeta RO — pricelist din class-packeta-ro-pricelist.php.
 * Prețurile din checkout se sincronizează din Setări → „Actualizează prețuri curieri”.
 */
$webgsm_packeta_ro_vat = WebGSM_Packeta_Ro_Pricelist::VAT_RATE;
/** @return string */
$webgsm_packeta_ro_price_line = static function (float $net_ex_vat) use ($webgsm_packeta_ro_vat): string {
    $gross = WebGSM_Packeta_Ro_Pricelist::net_to_gross($net_ex_vat);

    return sprintf(
        '%s RON fără TVA → %s RON cu TVA 21%%',
        number_format($net_ex_vat, 2, ',', ''),
        number_format($gross, 2, ',', '')
    );
};

$ref_grids = [
    'sameday_hd',
    'sameday_box',
    'fan_hd',
    'fan_box',
];
$all_grids = WebGSM_Packeta_Ro_Pricelist::grids();
$synced_at = get_option('webgsm_packeta_pricelist_synced_at', '');
?>
<div class="webgsm-packeta-card webgsm-packeta-pricing-ref" style="margin-top:16px;">
    <h2>Referință tarife contract (RO) — valabil din <?php echo esc_html(WebGSM_Packeta_Ro_Pricelist::EFFECTIVE_FROM); ?></h2>
    <p class="webgsm-packeta-help">
        Sursă: lista de prețuri Packeta pentru <strong>webgsm</strong> (expediere din România).
        <strong>Prețurile la checkout</strong> se iau din setările carrier Packeta — le poți actualiza din
        <a href="<?php echo esc_url(admin_url('admin.php?page=webgsm-packeta&tab=settings')); ?>">Packeta → Setări → Actualizează prețuri curieri</a>.
        <?php if (is_string($synced_at) && $synced_at !== '') : ?>
            Ultima sincronizare în magazin: <code><?php echo esc_html($synced_at); ?></code>.
        <?php endif; ?>
    </p>
    <p class="webgsm-packeta-help">Suprataxă non-depozit (toți curierii): <?php echo esc_html($webgsm_packeta_ro_price_line(1.80)); ?> · Return din RO: preț transport × 2.</p>

    <div class="webgsm-packeta-ref-grid">
        <?php foreach ($ref_grids as $grid_key) : ?>
            <?php
            if (!isset($all_grids[$grid_key])) {
                continue;
            }
            $grid = $all_grids[$grid_key];
            ?>
            <div class="webgsm-packeta-ref-col">
                <h3><?php echo esc_html($grid['label']); ?></h3>
                <ul class="webgsm-packeta-ref-list">
                    <?php foreach ($grid['tiers'] as $tier) : ?>
                        <li><?php echo esc_html(($tier['label'] ?? '') . ' — ' . $webgsm_packeta_ro_price_line((float) $tier['net'])); ?></li>
                    <?php endforeach; ?>
                </ul>
                <p class="webgsm-packeta-help"><?php echo esc_html($grid['cod_note']); ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</div>
