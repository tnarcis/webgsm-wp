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
        '%s RON cu TVA 21%% (listă: %s fără TVA)',
        number_format($gross, 2, ',', ''),
        number_format($net_ex_vat, 2, ',', '')
    );
};

$ref_grids = [
    'cargus_hd',
    'fan_hd',
    'fan_box',
    'sameday_hd',
    'sameday_box',
    'packeta_hd',
    'ro_post_hd',
    'ro_post_pp',
    'z_box',
    'z_point_pp',
    'de_home_hd',
    'de_hermes_hd',
    'de_hermes_pp',
    'fr_colis_prive_hd',
    'fr_colissimo_hd',
    'fr_mondial_relay_pp',
    'nl_dhl_hd',
    'nl_dutch_post',
];
$all_grids = WebGSM_Packeta_Ro_Pricelist::grids();
$synced_at = get_option('webgsm_packeta_pricelist_synced_at', '');
?>
<div class="webgsm-packeta-card webgsm-packeta-pricing-ref" style="margin-top:16px;">
    <h2>Referință tarife contract webgsm — valabil din <?php echo esc_html(WebGSM_Packeta_Ro_Pricelist::EFFECTIVE_FROM); ?></h2>
    <p class="webgsm-packeta-help">
        Sursă: lista Packeta România pentru clientul <strong>webgsm</strong> (expediere din RO).
        În listă prețurile sunt <strong>fără TVA</strong>. Mai jos: întâi <strong>cu TVA 21%</strong>, apoi fără TVA.
        Ordine: RO (HD + Box/PP) → DE → FR → NL. <strong>Italia nu e pe listă</strong>.
        La sincronizare în magazin se salvează <strong>fără TVA</strong>.
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
