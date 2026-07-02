<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="webgsm-packeta-card">
    <h2>Expediție (createShipment)</h2>
    <p class="webgsm-packeta-help">
        <strong>Pas obligatoriu pentru ridicare curier.</strong> După ce ai creat AWB-ul (tab „AWB nou”), Packeta returnează un <code>packetId</code>.
        Aici grupezi unul sau mai multe ID-uri într-o expediție — fără acest pas, coletul poate rămâne neprogramat pentru ridicare.
    </p>
    <p class="webgsm-packeta-help">
        Introdu câte un <code>packetId</code> pe linie. API-ul apelează <code>createShipment</code> conform contului Packeta.
        Dacă ridicarea nu apare în cont, verifică și în
        <a href="https://client.packeta.com/" target="_blank" rel="noopener noreferrer">client.packeta.com</a>.
    </p>
    <?php
    $prefill_packet = isset($_GET['prefill_packet']) ? preg_replace('/\D/', '', (string) $_GET['prefill_packet']) : '';
    ?>

    <form method="post" action="">
        <?php wp_nonce_field('webgsm_packeta'); ?>
        <input type="hidden" name="webgsm_packeta_action" value="create_shipment" />
        <input type="hidden" name="tab" value="shipment" />

        <div class="webgsm-packeta-field">
            <label for="packet_ids">ID pachete (packetId)</label>
            <textarea name="packet_ids" id="packet_ids" class="large large-text" rows="8" placeholder="1234567890&#10;1234567891" required><?php echo $prefill_packet !== '' ? esc_textarea($prefill_packet) : ''; ?></textarea>
        </div>

        <div class="webgsm-packeta-field">
            <label for="custom_barcode">Barcode personalizat (opțional, dacă e activat în cont)</label>
            <input type="text" name="custom_barcode" id="custom_barcode" value="" />
        </div>

        <div class="webgsm-packeta-actions">
            <?php submit_button('Creează expediția'); ?>
        </div>
    </form>
</div>
