<?php
if (!defined('ABSPATH')) {
    exit;
}

$default_label_format = WebGSM_Packeta_Config::get_default_label_format();
$label_formats = ['A6 on A6', 'A7 on A7', 'A6 on A4', 'A7 on A4', '105x35mm on A4', 'A8 on A8'];
?>
<div class="webgsm-packeta-card webgsm-packeta-tracking-note" style="margin-bottom:16px;">
    <h2>Tracking Sameday / Fan</h2>
    <p class="webgsm-packeta-help" style="margin-top:0;">
        <strong><code>Z 383 2892 743</code> nu e AWB Sameday</strong> — e cod Packeta. Pe
        <a href="https://sameday.ro/" target="_blank" rel="noopener noreferrer">sameday.ro</a>
        cauți <strong>numărul AWB al curierului</strong>, pe care Packeta îl primește de la Sameday (poate lipsi imediat după creare).
    </p>
    <p class="webgsm-packeta-help">Urmărire colet în contul tău: <a href="https://client.packeta.com/" target="_blank" rel="noopener noreferrer">client.packeta.com</a> cu Packet ID <code>3832892743</code>.</p>
</div>

<div class="webgsm-packeta-card">
    <h2>Descarcă etichetă PDF</h2>
    <p class="webgsm-packeta-help">
        Pentru <strong>Sameday / Fan / Cargus</strong> (România) se descarcă automat <strong>eticheta curierului</strong>
        (API: <code>packetCourierNumber</code> + <code>packetCourierLabelPdf</code>).
        Poți lipi <strong>Packet ID</strong> sau barcode-ul citibil, ex. <code>Z 383 2892 743</code>.
    </p>

    <form method="post" action="">
        <?php wp_nonce_field('webgsm_packeta'); ?>
        <input type="hidden" name="webgsm_packeta_action" value="download_label" />
        <input type="hidden" name="tab" value="label" />

        <div class="webgsm-packeta-grid">
            <div class="webgsm-packeta-field">
                <label for="label_packet_id">Packet ID sau barcode</label>
                <input type="text" name="label_packet_id" id="label_packet_id" value="" placeholder="Z 383 2892 743 sau 3832892743" required />
            </div>
            <div class="webgsm-packeta-field">
                <label for="label_format">Format (fallback Packeta)</label>
                <select name="label_format" id="label_format">
                    <?php foreach ($label_formats as $fmt) : ?>
                        <option value="<?php echo esc_attr($fmt); ?>" <?php selected($default_label_format, $fmt); ?>><?php echo esc_html($fmt); ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="webgsm-packeta-help">La curieri RO, formatul e de obicei A6 (1/4 A4), setat de API curier.</p>
            </div>
        </div>

        <div class="webgsm-packeta-actions">
            <?php submit_button('Descarcă PDF', 'primary', 'submit', false); ?>
        </div>
    </form>
</div>

<div class="webgsm-packeta-card" style="margin-top:16px;">
    <h2>Număr AWB curier (Sameday / Fan)</h2>
    <p class="webgsm-packeta-help">Obține numărul de urmărire de pe site-ul curierului (nu barcode-ul Z…).</p>
    <form method="post" action="">
        <?php wp_nonce_field('webgsm_packeta'); ?>
        <input type="hidden" name="webgsm_packeta_action" value="courier_number" />
        <input type="hidden" name="tab" value="label" />

        <div class="webgsm-packeta-field">
            <label for="courier_packet_id">Packet ID sau barcode Packeta</label>
            <input type="text" name="courier_packet_id" id="courier_packet_id" value="" placeholder="Z 383 2892 743" required />
        </div>

        <div class="webgsm-packeta-actions">
            <?php submit_button('Obține număr curier', 'secondary', 'submit', false); ?>
        </div>
    </form>
</div>

<div class="webgsm-packeta-card" style="margin-top:16px;">
    <h2>Status pachet</h2>
    <form method="post" action="">
        <?php wp_nonce_field('webgsm_packeta'); ?>
        <input type="hidden" name="webgsm_packeta_action" value="packet_status" />
        <input type="hidden" name="tab" value="label" />

        <div class="webgsm-packeta-field">
            <label for="status_packet_id">Packet ID sau barcode</label>
            <input type="text" name="status_packet_id" id="status_packet_id" value="" placeholder="Z 383 2892 743" required />
        </div>

        <div class="webgsm-packeta-actions">
            <?php submit_button('Citește status', 'secondary', 'submit', false); ?>
        </div>
    </form>
</div>
