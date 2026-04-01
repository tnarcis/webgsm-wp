<?php
if (!defined('ABSPATH')) {
    exit;
}

$has_carriers = !empty($checkout_carriers) && is_array($checkout_carriers);
$pickup_carriers_list = [];
$home_carriers_list = [];
if ($has_carriers) {
    foreach ($checkout_carriers as $c) {
        if (!empty($c['is_pickup'])) {
            $pickup_carriers_list[] = $c;
        } else {
            $home_carriers_list[] = $c;
        }
    }
}
$has_pickup = $pickup_carriers_list !== [];
$has_home = $home_carriers_list !== [];
$sender_label = isset($settings['eshop']) ? (string) $settings['eshop'] : '';
?>
<div class="webgsm-packeta-card webgsm-packeta-awb-intro">
    <h2>AWB nou</h2>
    <p class="webgsm-packeta-lead">Alege mai întâi tipul livrării. Pentru <strong>Box / punct fix</strong> este obligatoriu să selectezi punctul pe harta Packeta. Pentru <strong>livrare la adresă</strong>, harta nu se folosește.</p>
    <?php if (!$has_carriers) : ?>
        <p class="notice notice-warning inline" style="margin:12px 0;">
            Nu există curieri Packeta activați în WooCommerce.
            <a href="<?php echo esc_url(admin_url('admin.php?page=wc-settings&tab=shipping')); ?>">Configurează livrarea</a>.
        </p>
    <?php endif; ?>
</div>

<div class="webgsm-packeta-card webgsm-packeta-flow-card">
    <h2>1. Tip livrare</h2>
    <div class="webgsm-packeta-flow-options" role="radiogroup" aria-label="Tip livrare">
        <label class="webgsm-packeta-flow-option">
            <input type="radio" name="awb_flow_radio" id="awb_flow_pickup" value="pickup" class="awb-flow-radio" <?php echo $has_pickup ? 'checked' : 'disabled'; ?> />
            <span class="webgsm-packeta-flow-option-body">
                <strong>Punct fix / Box / Easybox</strong>
                <span class="webgsm-packeta-flow-desc">Selectezi curierul, apoi <strong>obligatoriu</strong> punctul pe harta Packeta.</span>
            </span>
        </label>
        <label class="webgsm-packeta-flow-option">
            <input type="radio" name="awb_flow_radio" id="awb_flow_home" value="home" class="awb-flow-radio" <?php echo !$has_pickup ? 'checked' : ''; ?> />
            <span class="webgsm-packeta-flow-option-body">
                <strong>Livrare la adresă</strong>
                <span class="webgsm-packeta-flow-desc">Fără hartă Packeta. Alegi curierul de livrare la adresă (ca la checkout), apoi adresa destinatarului.</span>
            </span>
        </label>
    </div>
    <?php if (!$has_pickup && $has_carriers) : ?>
        <p class="notice notice-warning inline" style="margin:10px 0 0;">Nu ai niciun curier de tip <strong>punct</strong> activ — poți folosi doar livrarea la adresă.</p>
    <?php endif; ?>
</div>

<div class="webgsm-packeta-card webgsm-packeta-sender-card">
    <h2>Expeditor</h2>
    <p class="webgsm-packeta-sender-line">
        <strong>Identificator magazin (sender):</strong>
        <?php if ($sender_label !== '') : ?>
            <code><?php echo esc_html($sender_label); ?></code>
        <?php else : ?>
            <em>— setează în WooCommerce → Packeta</em>
        <?php endif; ?>
    </p>
    <p class="webgsm-packeta-help" style="margin:0;">
        Adresa fizică de expediere (de unde pleacă coletul) este cea din <strong>contul Packeta</strong> în client.packeta.com; nu se poate schimba din acest formular. Aici se trimite doar codul magazinului în API, ca la checkout.
    </p>
</div>

<div id="webgsm_packeta_map_section" class="webgsm-packeta-card webgsm-packeta-map-card"<?php echo $has_pickup ? '' : ' hidden'; ?>>
    <h2>2. Hartă — alegere punct ridicare</h2>
    <p class="webgsm-packeta-help">Pas obligatoriu: deschide harta și confirmă punctul. Fără punct selectat nu se poate trimite AWB-ul.</p>

    <div class="webgsm-packeta-map-row">
        <?php if ($has_pickup) : ?>
            <div class="webgsm-packeta-field webgsm-packeta-carrier-select-wrap">
                <label for="webgsm_packeta_carrier_filter">Curier (doar puncte, ca la checkout)</label>
                <select id="webgsm_packeta_carrier_filter" class="webgsm-packeta-carrier-select">
                    <option value="" data-is-pickup="1"><?php echo esc_html__('— Toți curierii pe hartă —', 'webgsm-packeta'); ?></option>
                    <?php foreach ($pickup_carriers_list as $c) : ?>
                        <?php
                        $label = $c['title'];
                        if (!empty($c['pricing_hint'])) {
                            $label .= ' — ' . $c['pricing_hint'];
                        }
                        ?>
                        <option
                            value="<?php echo esc_attr($c['carrier_id']); ?>"
                            data-vendor="<?php echo esc_attr(wp_json_encode($c['vendor'])); ?>"
                            data-is-pickup="1"
                        ><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div class="webgsm-packeta-field">
            <button type="button" class="button button-primary button-hero" id="webgsm_packeta_open_map">
                <?php echo esc_html__('Deschide harta Packeta', 'webgsm-packeta'); ?>
            </button>
        </div>
    </div>

    <div class="webgsm-packeta-point-ok" id="webgsm_packeta_point_status">
        <span class="webgsm-packeta-point-icon" aria-hidden="true">○</span>
        <span id="webgsm_packeta_point_summary">Niciun punct selectat — apasă „Deschide harta Packeta”.</span>
    </div>
</div>

<div id="webgsm_packeta_home_carrier_section" class="webgsm-packeta-card webgsm-packeta-home-carrier-card"<?php echo $has_pickup ? ' hidden' : ''; ?>>
    <h2>2. Curier — livrare la adresă</h2>
    <p class="webgsm-packeta-help">După ce ai ales <strong>livrare la adresă</strong>, selectează transportatorul (același ca la checkout). Acesta setează <code>addressId</code> pentru API.</p>
    <?php if ($has_home) : ?>
        <div class="webgsm-packeta-field webgsm-packeta-home-carrier-select-wrap">
            <label for="webgsm_packeta_home_carrier_select"><?php echo esc_html__('Curier livrare la adresă', 'webgsm-packeta'); ?> *</label>
            <select id="webgsm_packeta_home_carrier_select" class="webgsm-packeta-carrier-select" autocomplete="off">
                <option value=""><?php echo esc_html__('— Alege curierul —', 'webgsm-packeta'); ?></option>
                <?php foreach ($home_carriers_list as $c) : ?>
                    <?php
                    $label = $c['title'];
                    if (!empty($c['pricing_hint'])) {
                        $label .= ' — ' . $c['pricing_hint'];
                    }
                    ?>
                    <option value="<?php echo esc_attr($c['carrier_id']); ?>"><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php else : ?>
        <p class="notice notice-warning inline" style="margin:0;">
            <?php if ($has_carriers) : ?>
                Nu există curieri de <strong>livrare la adresă</strong> activați în WooCommerce (ai doar puncte / Box). Poți introduce manual <code>addressId</code> în câmpul de mai jos sau adaugă o metodă HD în
                <a href="<?php echo esc_url(admin_url('admin.php?page=wc-settings&tab=shipping')); ?>">livrare</a>.
            <?php else : ?>
                Nu există curieri Packeta activați.
            <?php endif; ?>
        </p>
    <?php endif; ?>
</div>

<div class="webgsm-packeta-card webgsm-packeta-form-card">
    <h2 id="form-step-title">3. Detalii expediție</h2>

    <form method="post" action="" id="webgsm_packeta_awb_form">
        <?php wp_nonce_field('webgsm_packeta'); ?>
        <input type="hidden" name="webgsm_packeta_action" value="create_packet" />
        <input type="hidden" name="tab" value="awb" />
        <input type="hidden" name="awb_flow" id="awb_flow" value="pickup" />
        <input type="hidden" name="delivery_mode" id="delivery_mode" value="pudo" />
        <input type="hidden" name="point_pickup_type" id="point_pickup_type" value="" />

        <div id="webgsm_packeta_home_intro" class="webgsm-packeta-home-intro" hidden>
            <p class="webgsm-packeta-help">Completează <code>addressId</code> = ID-ul transportatorului pentru livrare la adresă (din Packeta), plus adresa destinatarului.</p>
        </div>

        <div class="webgsm-packeta-grid webgsm-packeta-grid-3">
            <div class="webgsm-packeta-field">
                <label for="address_id">addressId *</label>
                <input type="number" name="address_id" id="address_id" value="" min="1" step="1" />
                <p class="webgsm-packeta-help" id="address_id_help">Setat din hartă pentru punct fix.</p>
            </div>
            <div class="webgsm-packeta-field" id="carrier_cpp_wrap">
                <label for="carrier_pickup_point">Cod punct (carrierPickupPoint)</label>
                <input type="text" name="carrier_pickup_point" id="carrier_pickup_point" value="" autocomplete="off" />
                <p class="webgsm-packeta-help">La puncte externe, din hartă.</p>
            </div>
            <div class="webgsm-packeta-field">
                <label for="order_number">Referință comandă</label>
                <input type="text" name="order_number" id="order_number" value="" placeholder="auto dacă gol" />
            </div>
        </div>

        <div class="webgsm-packeta-grid webgsm-packeta-grid-4" id="packeta-home-fields" style="display:none;">
            <div class="webgsm-packeta-field">
                <label for="street">Stradă *</label>
                <input type="text" name="street" id="street" value="" />
            </div>
            <div class="webgsm-packeta-field">
                <label for="house_number">Număr</label>
                <input type="text" name="house_number" id="house_number" value="" />
            </div>
            <div class="webgsm-packeta-field">
                <label for="city">Oraș *</label>
                <input type="text" name="city" id="city" value="" />
            </div>
            <div class="webgsm-packeta-field">
                <label for="zip">Cod poștal</label>
                <input type="text" name="zip" id="zip" value="" />
            </div>
        </div>

        <h3 class="webgsm-packeta-sub">Destinatar</h3>
        <div class="webgsm-packeta-grid webgsm-packeta-grid-4">
            <div class="webgsm-packeta-field">
                <label for="recipient_name">Prenume *</label>
                <input type="text" name="recipient_name" id="recipient_name" value="" required />
            </div>
            <div class="webgsm-packeta-field">
                <label for="recipient_surname">Nume *</label>
                <input type="text" name="recipient_surname" id="recipient_surname" value="" required />
            </div>
            <div class="webgsm-packeta-field">
                <label for="recipient_email">Email *</label>
                <input type="email" name="recipient_email" id="recipient_email" value="" required />
            </div>
            <div class="webgsm-packeta-field">
                <label for="recipient_phone">Telefon *</label>
                <input type="text" name="recipient_phone" id="recipient_phone" value="" required />
            </div>
        </div>

        <div class="webgsm-packeta-field">
            <label for="company">Firmă (opțional)</label>
            <input type="text" name="company" id="company" value="" class="large-text" />
        </div>

        <div class="webgsm-packeta-grid webgsm-packeta-grid-4">
            <div class="webgsm-packeta-field">
                <label for="value">Valoare colet</label>
                <input type="text" name="value" id="value" value="0" inputmode="decimal" />
            </div>
            <div class="webgsm-packeta-field">
                <label for="currency">Monedă</label>
                <input type="text" name="currency" id="currency" value="<?php echo esc_attr($settings['default_currency']); ?>" maxlength="3" />
            </div>
            <div class="webgsm-packeta-field">
                <label for="weight">Greutate (kg)</label>
                <input type="text" name="weight" id="weight" value="1" inputmode="decimal" />
            </div>
            <div class="webgsm-packeta-field">
                <label for="cod">Ramburs (COD)</label>
                <input type="text" name="cod" id="cod" value="0" inputmode="decimal" />
            </div>
        </div>

        <div class="webgsm-packeta-field">
            <label for="note">Observații livrare</label>
            <textarea name="note" id="note" rows="3" class="large-text"></textarea>
        </div>

        <div class="webgsm-packeta-actions">
            <?php submit_button('Trimite AWB către Packeta', 'primary', 'submit', false); ?>
            <?php submit_button('Doar validare (packetAttributesValid)', 'secondary', 'validate_only', false); ?>
        </div>
    </form>
</div>

<?php include WEBGSM_PACKETA_PATH . 'admin/views/part-pricing-reference-ro.php'; ?>
