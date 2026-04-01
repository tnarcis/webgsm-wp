<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="webgsm-packeta-card">
    <h2>Descarcă etichetă PDF</h2>
    <form method="post" action="">
        <?php wp_nonce_field('webgsm_packeta'); ?>
        <input type="hidden" name="webgsm_packeta_action" value="download_label" />
        <input type="hidden" name="tab" value="label" />

        <div class="webgsm-packeta-grid">
            <div class="webgsm-packeta-field">
                <label for="label_packet_id">Packet ID</label>
                <input type="text" name="label_packet_id" id="label_packet_id" value="" required />
            </div>
            <div class="webgsm-packeta-field">
                <label for="label_format">Format</label>
                <select name="label_format" id="label_format">
                    <option value="A6 on A6">A6 on A6</option>
                    <option value="A7 on A7">A7 on A7</option>
                    <option value="A6 on A4">A6 on A4</option>
                    <option value="A7 on A4">A7 on A4</option>
                    <option value="105x35mm on A4">105x35mm on A4</option>
                    <option value="A8 on A8">A8 on A8</option>
                </select>
            </div>
        </div>

        <div class="webgsm-packeta-actions">
            <?php submit_button('Descarcă PDF', 'primary', 'submit', false); ?>
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
            <label for="status_packet_id">Packet ID</label>
            <input type="text" name="status_packet_id" id="status_packet_id" value="" required />
        </div>

        <div class="webgsm-packeta-actions">
            <?php submit_button('Citește status', 'secondary', 'submit', false); ?>
        </div>
    </form>
</div>
