<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="webgsm-packeta-card">
    <h2>Expediție / borderou (createShipment)</h2>
    <p class="webgsm-packeta-help">
        <strong>Nu alegi tu o dată de ridicare aici.</strong> Packeta nu are în API un calendar „vino marți”.
        AWB-ul doar înregistrează coletul; <code>createShipment</code> creează un <strong>borderou</strong> (grupare)
        ca să marchezi coletele gata de predare — curierul le scanează la ridicare sau la depozit.
    </p>
    <p class="webgsm-packeta-help">
        <strong>Când vine curierul la sediu?</strong> Doar dacă în contractul Packeta ai <em>ridicare de la sediu</em>
        (adresa din Expeditori / sender). Frecvența (zilnic / la cerere) o stabilește Packeta, nu magazinul.
        Alternativ: du coletul la un punct Packeta / depozit cu eticheta lipită.
    </p>
    <p class="webgsm-packeta-help">
        Introdu câte un <code>packetId</code> pe linie. Verifică statusul în
        <a href="https://client.packeta.com/" target="_blank" rel="noopener noreferrer">client.packeta.com</a>
        → colete postate / expediții. Dacă AWB există dar nimeni nu vine: sună Packeta și confirmă că ai serviciu de ridicare la adresa sender.
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
