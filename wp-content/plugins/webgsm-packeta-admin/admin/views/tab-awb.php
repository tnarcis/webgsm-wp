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
$sender_base = isset($settings['sender_base']) ? (string) $settings['sender_base'] : '';
$sender_label = $sender_base;
$ro_counties = WebGSM_Packeta_Config::get_ro_counties();
$awb_draft = isset($awb_draft) && is_array($awb_draft) ? $awb_draft : [];
$awb_v = static function (string $key, string $default = '') use ($awb_draft): string {
    if (isset($awb_draft[$key]) && (string) $awb_draft[$key] !== '') {
        return (string) $awb_draft[$key];
    }

    return $default;
};
$awb_flow_current = $awb_v('awb_flow', $has_pickup ? 'pickup' : 'home');
$awb_is_home = $awb_flow_current === 'home';
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
            <input type="radio" name="awb_flow_radio" id="awb_flow_pickup" value="pickup" class="awb-flow-radio" <?php echo $has_pickup ? checked(!$awb_is_home, true, false) : 'disabled'; ?> />
            <span class="webgsm-packeta-flow-option-body">
                <strong>Punct fix / Box / Easybox</strong>
                <span class="webgsm-packeta-flow-desc">Selectezi curierul, apoi <strong>obligatoriu</strong> punctul pe harta Packeta.</span>
            </span>
        </label>
        <label class="webgsm-packeta-flow-option">
            <input type="radio" name="awb_flow_radio" id="awb_flow_home" value="home" class="awb-flow-radio" <?php checked($awb_is_home); ?> />
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
    <?php if (empty($settings['sender_configured'])) : ?>
        <p class="notice notice-error inline" style="margin:0 0 12px;">
            <strong>Expeditor nesetat.</strong> Mergi la <a href="<?php echo esc_url(admin_url('admin.php?page=webgsm-packeta&tab=settings')); ?>">Setări</a>
            și completează <strong>No Limit Tech</strong> (exact ca în client.packeta.com).
        </p>
    <?php endif; ?>
    <p class="webgsm-packeta-sender-line">
        <strong>Bază expeditor:</strong>
        <?php if ($sender_label !== '') : ?>
            <code><?php echo esc_html($sender_label); ?></code>
        <?php else : ?>
            <em style="color:#d63638;">— nesetat</em>
        <?php endif; ?>
    </p>
    <p class="webgsm-packeta-help" style="margin:0 0 8px;">
        La trimitere, API primește automat expeditorul potrivit curierului, ca în Packeta:
        <code>No Limit Tech - Sameday</code> (HD 7397 / Box 7455),
        <code>No Limit Tech - FAN Courier</code> (762),
        <code>No Limit Tech - Cargus</code> (590).
    </p>
    <p class="webgsm-packeta-help" style="margin:0;">
        <strong>Transportator</strong> (<code>addressId</code>) = alegi mai jos: RO Sameday HD <code>7397</code>, Box <code>7455</code>, etc. — al doilea câmp din Packeta.
    </p>
</div>

<div id="webgsm_packeta_map_section" class="webgsm-packeta-card webgsm-packeta-map-card"<?php echo ($has_pickup && !$awb_is_home) ? '' : ' hidden'; ?>>
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

    <div class="webgsm-packeta-point-ok<?php echo $awb_v('point_summary') !== '' ? ' is-done' : ''; ?>" id="webgsm_packeta_point_status">
        <span class="webgsm-packeta-point-icon" aria-hidden="true">○</span>
        <span id="webgsm_packeta_point_summary"><?php echo esc_html($awb_v('point_summary', 'Niciun punct selectat — apasă „Deschide harta Packeta”.')); ?></span>
    </div>
</div>

<div id="webgsm_packeta_home_carrier_section" class="webgsm-packeta-card webgsm-packeta-home-carrier-card"<?php echo ($has_pickup && !$awb_is_home) ? ' hidden' : ''; ?>>
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
                    <option value="<?php echo esc_attr($c['carrier_id']); ?>" <?php selected($awb_v('address_id'), (string) $c['carrier_id']); ?>><?php echo esc_html($label); ?></option>
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
        <input type="hidden" name="awb_flow" id="awb_flow" value="<?php echo esc_attr($awb_flow_current); ?>" />
        <input type="hidden" name="delivery_mode" id="delivery_mode" value="<?php echo esc_attr($awb_v('delivery_mode', $awb_is_home ? 'home' : 'pudo')); ?>" />
        <input type="hidden" name="point_pickup_type" id="point_pickup_type" value="<?php echo esc_attr($awb_v('point_pickup_type')); ?>" />
        <input type="hidden" name="point_summary" id="point_summary" value="<?php echo esc_attr($awb_v('point_summary')); ?>" />
        <input type="hidden" name="carrier_filter" id="carrier_filter" value="<?php echo esc_attr($awb_v('carrier_filter')); ?>" />

        <div id="webgsm_packeta_home_intro" class="webgsm-packeta-home-intro"<?php echo $awb_is_home ? '' : ' hidden'; ?>>
            <p class="webgsm-packeta-help">Completează adresa destinatarului: stradă, număr, oraș, <strong>județ</strong> și cod poștal — câmpuri cerute de API Packeta la livrare la adresă (Fan, Sameday etc.). <code>addressId</code> = ID-ul curierului HD.</p>
            <p class="notice notice-info inline" style="margin:10px 0 0;">
                <strong>După crearea AWB:</strong> mergi la tabul
                <a href="<?php echo esc_url(admin_url('admin.php?page=webgsm-packeta&tab=shipment')); ?>">Expediție / ridicare</a>
                și grupează <code>packetId</code>-ul (createShipment). Fără acest pas, curierul poate să nu vină la ridicare.
            </p>
        </div>

        <div class="webgsm-packeta-grid webgsm-packeta-grid-3">
            <div class="webgsm-packeta-field">
                <label for="address_id">addressId *</label>
                <input type="number" name="address_id" id="address_id" value="<?php echo esc_attr($awb_v('address_id')); ?>" min="1" step="1" />
                <p class="webgsm-packeta-help" id="address_id_help">Setat din hartă pentru punct fix.</p>
            </div>
            <div class="webgsm-packeta-field" id="carrier_cpp_wrap"<?php echo $awb_v('point_pickup_type') === 'external' ? '' : ' style="display:none;"'; ?>>
                <label for="carrier_pickup_point">Cod punct (carrierPickupPoint)</label>
                <input type="text" name="carrier_pickup_point" id="carrier_pickup_point" value="<?php echo esc_attr($awb_v('carrier_pickup_point')); ?>" autocomplete="off" />
                <p class="webgsm-packeta-help">La puncte externe, din hartă.</p>
            </div>
            <div class="webgsm-packeta-field">
                <label for="order_number">Referință comandă</label>
                <input type="text" name="order_number" id="order_number" value="<?php echo esc_attr($awb_v('order_number')); ?>" placeholder="auto dacă gol" />
            </div>
        </div>

        <div class="webgsm-packeta-grid webgsm-packeta-grid-4" id="packeta-home-fields" style="<?php echo $awb_is_home ? 'display:grid;' : 'display:none;'; ?>">
            <div class="webgsm-packeta-field">
                <label for="street">Stradă *</label>
                <input type="text" name="street" id="street" value="<?php echo esc_attr($awb_v('street')); ?>" autocomplete="address-line1" />
            </div>
            <div class="webgsm-packeta-field">
                <label for="house_number">Număr *</label>
                <input type="text" name="house_number" id="house_number" value="<?php echo esc_attr($awb_v('house_number')); ?>" autocomplete="address-line2" />
            </div>
            <div class="webgsm-packeta-field">
                <label for="city">Oraș *</label>
                <input type="text" name="city" id="city" value="<?php echo esc_attr($awb_v('city')); ?>" autocomplete="address-level2" />
            </div>
            <div class="webgsm-packeta-field">
                <label for="province">Județ *</label>
                <select name="province" id="province" autocomplete="address-level1">
                    <?php foreach ($ro_counties as $code => $label) : ?>
                        <option value="<?php echo esc_attr($code); ?>" <?php selected($awb_v('province'), (string) $code); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="webgsm-packeta-field">
                <label for="zip">Cod poștal *</label>
                <input type="text" name="zip" id="zip" value="<?php echo esc_attr($awb_v('zip')); ?>" inputmode="numeric" autocomplete="postal-code" maxlength="6" />
            </div>
        </div>

        <h3 class="webgsm-packeta-sub">Destinatar</h3>
        <div class="webgsm-packeta-grid webgsm-packeta-grid-4">
            <div class="webgsm-packeta-field">
                <label for="recipient_name">Prenume *</label>
                <input type="text" name="recipient_name" id="recipient_name" value="<?php echo esc_attr($awb_v('recipient_name')); ?>" required />
            </div>
            <div class="webgsm-packeta-field">
                <label for="recipient_surname">Nume *</label>
                <input type="text" name="recipient_surname" id="recipient_surname" value="<?php echo esc_attr($awb_v('recipient_surname')); ?>" required />
            </div>
            <div class="webgsm-packeta-field">
                <label for="recipient_email">Email *</label>
                <input type="email" name="recipient_email" id="recipient_email" value="<?php echo esc_attr($awb_v('recipient_email')); ?>" required />
            </div>
            <div class="webgsm-packeta-field">
                <label for="recipient_phone">Telefon *</label>
                <input type="text" name="recipient_phone" id="recipient_phone" value="<?php echo esc_attr($awb_v('recipient_phone')); ?>" required />
            </div>
        </div>

        <div class="webgsm-packeta-field">
            <label for="company">Firmă (opțional)</label>
            <input type="text" name="company" id="company" value="<?php echo esc_attr($awb_v('company')); ?>" class="large-text" />
        </div>

        <div class="webgsm-packeta-grid webgsm-packeta-grid-4">
            <div class="webgsm-packeta-field">
                <label for="value">Valoare declarată colet (asigurare Packeta) *</label>
                <input type="number" name="value" id="value" value="<?php echo esc_attr($awb_v('value', '1')); ?>" step="0.01" min="0.01" inputmode="decimal" required />
                <p class="webgsm-packeta-help">Nu e rambursul la livrare. Packeta cere un număr &gt; 0 (asigurare). Pentru <strong>acte</strong> poți pune o valoare simbolică (ex. 1) dacă reflectă ce declară coletul; greutatea rămâne reală.</p>
            </div>
            <div class="webgsm-packeta-field">
                <label for="currency">Monedă</label>
                <input type="text" name="currency" id="currency" value="<?php echo esc_attr($awb_v('currency', $settings['default_currency'])); ?>" maxlength="3" />
            </div>
            <div class="webgsm-packeta-field">
                <label for="weight">Greutate (kg)</label>
                <input type="text" name="weight" id="weight" value="<?php echo esc_attr($awb_v('weight', '1')); ?>" inputmode="decimal" />
            </div>
            <div class="webgsm-packeta-field">
                <label for="cod">Ramburs la livrare (COD)</label>
                <input type="text" name="cod" id="cod" value="<?php echo esc_attr($awb_v('cod', '0')); ?>" inputmode="decimal" />
                <p class="webgsm-packeta-help"><strong>Fără ramburs:</strong> lasă 0 dacă curierul nu încasează nimic la livrare (indiferent dacă în magazin transportul a fost plătit sau <strong>gratuit</strong>).</p>
            </div>
        </div>

        <div class="webgsm-packeta-presets" style="margin:0 0 12px;">
            <p style="margin:0 0 8px;display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
                <button type="button" class="button" id="webgsm_packeta_preset_acte"><?php esc_html_e('Preset: acte (fără ramburs)', 'webgsm-packeta'); ?></button>
                <button type="button" class="button" id="webgsm_packeta_preset_no_cod"><?php esc_html_e('Preset: colet fără ramburs (doar COD 0)', 'webgsm-packeta'); ?></button>
            </p>
            <p class="webgsm-packeta-help" style="margin:0;">
                <?php esc_html_e('„Transport gratuit” în WooCommerce = clientul nu plătește livrarea la comandă; nu înseamnă automat altceva în Packeta. Aici, „Ramburs (COD)” = bani încasați de curier la ușă. Dacă nu se încasează nimic la livrare, pune COD 0. Valoarea declarată a coletului rămâne pentru asigurare (conținutul expediat), nu este taxa de transport.', 'webgsm-packeta'); ?>
            </p>
            <p class="webgsm-packeta-help" style="margin:8px 0 0;">
                <?php esc_html_e('Preset „acte”: COD 0, valoare 1, greutate 0,1 kg. Preset „colet fără ramburs”: doar COD 0; completezi tu valoarea și greutatea produselor.', 'webgsm-packeta'); ?>
            </p>
        </div>

        <div class="webgsm-packeta-field">
            <label for="note">Observații livrare</label>
            <textarea name="note" id="note" rows="3" class="large-text"><?php echo esc_textarea($awb_v('note')); ?></textarea>
        </div>

        <div class="webgsm-packeta-actions">
            <?php submit_button('Trimite AWB către Packeta', 'primary', 'submit', false); ?>
            <?php submit_button('Doar validare (packetAttributesValid)', 'secondary', 'validate_only', false); ?>
        </div>
    </form>
</div>

<?php include WEBGSM_PACKETA_PATH . 'admin/views/part-pricing-reference-ro.php'; ?>
