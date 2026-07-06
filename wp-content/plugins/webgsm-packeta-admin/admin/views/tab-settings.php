<?php
if (!defined('ABSPATH')) {
    exit;
}

$pk = isset($packetery_option) && is_array($packetery_option) ? $packetery_option : [];
$has_api = !empty($pk['api_password']);
$has_widget = !empty($pk['api_key']);
$has_sender = !empty($pk['sender']);
$packeta_url = WebGSM_Packeta_Config::packeta_plugin_settings_url();
?>
<div class="webgsm-packeta-card">
    <h2>Expeditor — ca în client.packeta.com</h2>
    <p class="webgsm-packeta-help" style="margin-top:0;">
        În Packeta ai <strong>două câmpuri</strong> la crearea AWB (vezi capturile):
    </p>
    <ol class="webgsm-packeta-help" style="margin:8px 0 16px 20px;">
        <li><strong>Expeditor</strong> → API <code>eshop</code> — ex. <code>No Limit Tech - Sameday</code> (pluginul adaugă sufixul curierului automat)</li>
        <li><strong>Punct pick-up / Transportator</strong> → API <code>addressId</code> — ex. RO Sameday HD <code>7397</code> (alegi la AWB nou)</li>
    </ol>

    <?php
    $stored_opt = get_option(WEBGSM_PACKETA_OPTION, []);
    $sender_base = is_array($stored_opt) && !empty($stored_opt['sender_base'])
        ? (string) $stored_opt['sender_base']
        : (string) ($settings['sender_base'] ?? '');
    $sender_ok = !empty($settings['sender_configured']);
    $suffix_map = WebGSM_Packeta_Sender_Mapper::carrier_suffix_map();
    ?>

    <table class="widefat striped" style="max-width:720px;margin-bottom:16px;">
        <tbody>
            <tr>
                <th scope="row">Bază expeditor</th>
                <td><?php echo $sender_ok ? '<code>' . esc_html($sender_base) . '</code>' : '<span style="color:#d63638;">nesetat</span>'; ?></td>
            </tr>
            <tr>
                <th scope="row">Exemple auto la AWB</th>
                <td>
                    <?php foreach ($suffix_map as $cid => $suffix) : ?>
                        <code><?php echo esc_html($sender_base . $suffix); ?></code> ← <?php echo esc_html($cid); ?><br>
                    <?php endforeach; ?>
                </td>
            </tr>
        </tbody>
    </table>

    <form method="post" action="">
        <?php wp_nonce_field('webgsm_packeta'); ?>
        <input type="hidden" name="webgsm_packeta_action" value="save_settings" />
        <input type="hidden" name="tab" value="settings" />

        <div class="webgsm-packeta-field">
            <label for="sender_base">Denumire bază expeditor *</label>
            <input type="text" name="sender_base" id="sender_base" class="regular-text" value="<?php echo esc_attr($sender_base); ?>" placeholder="No Limit Tech" required />
            <p class="webgsm-packeta-help">Doar partea comună. La trimitere AWB, pluginul completează automat <code> - Sameday</code>, <code> - FAN Courier</code> etc., după curierul ales (7397, 7455…).</p>
        </div>

        <div class="webgsm-packeta-actions">
            <?php submit_button('Salvează expeditor'); ?>
            <a class="button button-secondary" href="<?php echo esc_url($packeta_url); ?>">Validare sender în Packeta</a>
        </div>
    </form>
</div>

<div class="webgsm-packeta-card" style="margin-top:16px;">
    <h2>Conexiune API</h2>
    <p class="webgsm-packeta-help" style="margin-top:0;">
        Parola API, cheia pentru hartă (widget) și expeditorul se citesc automat din
        <strong>pluginul oficial Packeta</strong> pentru WooCommerce (opțiunea <code>packetery</code>).
        Le configurezi o singură dată în
        <a href="<?php echo esc_url($packeta_url); ?>">WooCommerce → Packeta → Setări</a>.
    </p>

    <table class="widefat striped" style="max-width:640px;">
        <tbody>
            <tr>
                <th scope="row">Parolă API (REST)</th>
                <td><?php echo $has_api ? '<span style="color:#00a32a;">✓ setată în Packeta</span>' : '<span style="color:#d63638;">lipsește — completează în Packeta</span>'; ?></td>
            </tr>
            <tr>
                <th scope="row">Cheie API (hartă / widget)</th>
                <td><?php echo $has_widget ? '<span style="color:#00a32a;">✓ setată în Packeta</span>' : '<span style="color:#d63638;">lipsește — completează în Packeta</span>'; ?></td>
            </tr>
            <tr>
                <th scope="row">Expeditor (sender)</th>
                <td><?php echo $has_sender ? esc_html((string) $pk['sender']) : '—'; ?> <span class="description">(vezi secțiunea de sus)</span></td>
            </tr>
            <tr>
                <th scope="row">Monedă magazin</th>
                <td><code><?php echo esc_html($settings['default_currency']); ?></code> <span class="description">(WooCommerce)</span></td>
            </tr>
        </tbody>
    </table>

    <?php if (!$has_api) : ?>
        <div class="notice notice-warning inline" style="margin:16px 0;">
            <p>Deschide <a href="<?php echo esc_url($packeta_url); ?>">setările Packeta</a> și introdu <strong>API password</strong> și <strong>API key</strong> (cheia pentru widget).</p>
        </div>
    <?php endif; ?>

    <p style="margin-top:16px;">
        <a class="button button-primary" href="<?php echo esc_url($packeta_url); ?>">Deschide setările Packeta</a>
    </p>
</div>

<div class="webgsm-packeta-card" style="margin-top:16px;">
    <h2>Tarife curieri în magazin (checkout)</h2>
    <p class="webgsm-packeta-help">
        Actualizează grila de greutate/preț din contractul Packeta valabil din
        <strong><?php echo esc_html(WebGSM_Packeta_Ro_Pricelist::EFFECTIVE_FROM); ?></strong>
        pentru <strong>curierii Packeta activi</strong> în WooCommerce (ex. Sameday HD, Sameday Easybox).
        Prețurile se salvează cu <strong>TVA 21%</strong> (ce vede clientul la checkout, dacă magazinul afișează prețuri cu TVA).
    </p>
    <?php
    $enabled_ids = WebGSM_Packeta_Carriers::get_enabled_carrier_ids_from_checkout();
    if ($enabled_ids === []) :
        ?>
        <p class="notice notice-warning inline">Nu ai curieri Packeta activi în <a href="<?php echo esc_url(admin_url('admin.php?page=wc-settings&tab=shipping')); ?>">Livrare WooCommerce</a>.</p>
    <?php else : ?>
        <ul class="webgsm-packeta-ref-list">
            <?php foreach ($enabled_ids as $cid) : ?>
                <?php
                $gk = WebGSM_Packeta_Ro_Pricelist::grid_key_for_carrier_id((string) $cid);
                $label = $gk !== null && isset(WebGSM_Packeta_Ro_Pricelist::grids()[$gk])
                    ? WebGSM_Packeta_Ro_Pricelist::grids()[$gk]['label']
                    : ('Carrier ID ' . $cid);
                ?>
                <li><code><?php echo esc_html((string) $cid); ?></code> — <?php echo esc_html($label); ?><?php echo $gk === null ? ' <em>(fără grilă automată)</em>' : ''; ?></li>
            <?php endforeach; ?>
        </ul>
        <form method="post" action="" style="margin-top:12px;">
            <?php wp_nonce_field('webgsm_packeta'); ?>
            <input type="hidden" name="webgsm_packeta_action" value="sync_carrier_prices" />
            <input type="hidden" name="tab" value="settings" />
            <?php submit_button('Actualizează prețuri curieri (din contract 2026)', 'primary', 'submit', false); ?>
        </form>
    <?php endif; ?>
</div>

<div class="webgsm-packeta-card" style="margin-top:16px;">
    <h2>Opțional: URL REST/XML</h2>
    <p class="webgsm-packeta-help">Doar dacă suportul Packeta îți dă alt endpoint. În mod normal lasă implicit.</p>
    <form method="post" action="">
        <?php wp_nonce_field('webgsm_packeta'); ?>
        <input type="hidden" name="webgsm_packeta_action" value="save_settings" />
        <input type="hidden" name="tab" value="settings" />

        <div class="webgsm-packeta-field">
            <label for="rest_url">URL REST/XML</label>
            <input type="url" name="rest_url" id="rest_url" class="large-text" value="<?php echo esc_attr($settings['rest_url']); ?>" />
            <p class="webgsm-packeta-help">Implicit: <code><?php echo esc_html(WebGSM_Packeta_Config::default_rest_url()); ?></code></p>
        </div>

        <div class="webgsm-packeta-actions">
            <?php submit_button('Salvează URL'); ?>
        </div>
    </form>
</div>

<div class="webgsm-packeta-card" style="margin-top:16px;">
    <h2>Notă</h2>
    <p class="webgsm-packeta-help">
        Dacă ai completat cândva credențiale și în acest plugin (înainte de versiunea 1.2), ele sunt folosite doar ca rezervă dacă în Packeta nu e setată parola / cheia.
        Totul se editează din pluginul Packeta.
    </p>
</div>
