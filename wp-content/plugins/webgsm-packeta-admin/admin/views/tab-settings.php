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
                <td><?php echo $has_sender ? esc_html((string) $pk['sender']) : '—'; ?> <span class="description">(din Packeta)</span></td>
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
