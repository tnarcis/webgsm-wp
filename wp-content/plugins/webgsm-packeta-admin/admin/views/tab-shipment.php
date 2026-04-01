<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="webgsm-packeta-card">
    <h2>Expediție (createShipment)</h2>
    <p class="webgsm-packeta-help">
        Introduceți ID-urile de pachet returnate de Packeta după crearea AWB-urilor (câte unul pe linie).
        API-ul grupează pachetele într-o expediție — folosit în fluxul de ridicare de către curier, conform contului și regulilor Packeta.
        Dacă aveți nevoie de alt tip de „cerere de ridicare” (ex. de la adresă), verificați în
        <a href="https://client.packeta.com/" target="_blank" rel="noopener noreferrer">client.packeta.com</a>
        sau contactați suportul — nu toate operațiunile sunt expuse în API-ul public.
    </p>

    <form method="post" action="">
        <?php wp_nonce_field('webgsm_packeta'); ?>
        <input type="hidden" name="webgsm_packeta_action" value="create_shipment" />
        <input type="hidden" name="tab" value="shipment" />

        <div class="webgsm-packeta-field">
            <label for="packet_ids">ID pachete (packetId)</label>
            <textarea name="packet_ids" id="packet_ids" class="large large-text" rows="8" placeholder="1234567890&#10;1234567891" required></textarea>
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
