<?php
if (!defined('ABSPATH')) {
    exit;
}
/**
 * Rezumat tarife contract (Anexa 1 Packeta – referință internă RO).
 * Prețurile din magazin pot diferi; se calculează din setările carrier din WooCommerce.
 */
$webgsm_packeta_ro_vat = 0.21;
/** @return string "x,xx RON fără TVA → y,yy RON cu TVA 21%" */
$webgsm_packeta_ro_price_line = static function (float $net_ex_vat) use ($webgsm_packeta_ro_vat): string {
    $gross = round($net_ex_vat * (1 + $webgsm_packeta_ro_vat), 2);
    return sprintf(
        '%s RON fără TVA → %s RON cu TVA 21%%',
        number_format($net_ex_vat, 2, ',', ''),
        number_format($gross, 2, ',', '')
    );
};
?>
<div class="webgsm-packeta-card webgsm-packeta-pricing-ref" style="margin-top:16px;">
    <h2>Referință tarife contract (RO) — Sameday &amp; Fan</h2>
    <p class="webgsm-packeta-help">
        Informații din oferta contractuală (greutăți, intervale). <strong>Prețurile afișate la curier mai sus</strong> vin din
        setările Packeta din magazin (<code>packetery_carrier_*</code>). Aici: grilă orientativă din anexă (fără TVA), cu <strong>TVA 21%</strong> calculat pentru fiecare valoare.
    </p>

    <div class="webgsm-packeta-ref-grid">
        <div class="webgsm-packeta-ref-col">
            <h3>Sameday — livrare la adresă (HD), 24h</h3>
            <ul class="webgsm-packeta-ref-list">
                <li>0–2 kg — <?php echo esc_html($webgsm_packeta_ro_price_line(14.00)); ?></li>
                <li>2–5 kg — <?php echo esc_html($webgsm_packeta_ro_price_line(18.00)); ?></li>
                <li>5–10 kg — <?php echo esc_html($webgsm_packeta_ro_price_line(24.50)); ?></li>
                <li>10–15 kg — <?php echo esc_html($webgsm_packeta_ro_price_line(31.00)); ?></li>
                <li>15–20 kg — <?php echo esc_html($webgsm_packeta_ro_price_line(37.50)); ?></li>
                <li>20–25 kg — <?php echo esc_html($webgsm_packeta_ro_price_line(44.00)); ?></li>
                <li>25–30 kg — <?php echo esc_html($webgsm_packeta_ro_price_line(50.50)); ?></li>
            </ul>
            <p class="webgsm-packeta-help">Ramburs &lt; 3.500 RON: ~<?php echo esc_html($webgsm_packeta_ro_price_line(1.80)); ?> (estimare) · Fără km exteriori, fără taxă combustibil, fără volumetric (conform anexă).</p>

            <h4 style="margin:12px 0 6px;">Sameday Box (lockere)</h4>
            <ul class="webgsm-packeta-ref-list">
                <li>0–3 kg — <?php echo esc_html($webgsm_packeta_ro_price_line(10.50)); ?></li>
                <li>3–5 kg — <?php echo esc_html($webgsm_packeta_ro_price_line(12.50)); ?></li>
                <li>5–10 kg — <?php echo esc_html($webgsm_packeta_ro_price_line(18.00)); ?></li>
            </ul>
            <p class="webgsm-packeta-help">Ramburs &lt; 3.500 RON: 0,8% din valoare ramburs.</p>
        </div>

        <div class="webgsm-packeta-ref-col">
            <h3>Fan Courier — livrare la adresă (HD), 24h</h3>
            <ul class="webgsm-packeta-ref-list">
                <li>0–2 kg — <?php echo esc_html($webgsm_packeta_ro_price_line(13.00)); ?></li>
                <li>2–5 kg — <?php echo esc_html($webgsm_packeta_ro_price_line(15.00)); ?></li>
                <li>5–10 kg — <?php echo esc_html($webgsm_packeta_ro_price_line(18.50)); ?></li>
                <li>10–15 kg — <?php echo esc_html($webgsm_packeta_ro_price_line(22.50)); ?></li>
                <li>15–20 kg — <?php echo esc_html($webgsm_packeta_ro_price_line(28.50)); ?></li>
                <li>20–25 kg — <?php echo esc_html($webgsm_packeta_ro_price_line(33.50)); ?></li>
                <li>25–30 kg — <?php echo esc_html($webgsm_packeta_ro_price_line(38.50)); ?></li>
            </ul>
            <p class="webgsm-packeta-help">Ramburs &lt; 3.500 RON: ~<?php echo esc_html($webgsm_packeta_ro_price_line(2.60)); ?> (estimare) · Fără taxă combustibil, fără km exteriori, fără volumetric. Tarife anexă fără TVA; după săgeată: cu TVA 21%.</p>

            <h4 style="margin:12px 0 6px;">Fan Box</h4>
            <ul class="webgsm-packeta-ref-list">
                <li>0–5 kg — <?php echo esc_html($webgsm_packeta_ro_price_line(8.50)); ?></li>
                <li>5–10 kg — <?php echo esc_html($webgsm_packeta_ro_price_line(12.50)); ?></li>
            </ul>
            <p class="webgsm-packeta-help">Ramburs &lt; 3.500 RON: 0,8% din valoare ramburs.</p>
        </div>
    </div>
</div>
