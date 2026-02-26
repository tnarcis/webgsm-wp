<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap webgsm-site-audit">
    <h1>Setări Site Audit</h1>

    <form method="post" action="options.php">
        <?php settings_fields('webgsm_site_audit'); ?>
        <input type="hidden" name="<?php echo WebGSM_Site_Audit_Settings::OPTION_KEY; ?>[dummy]" value="1">

        <table class="form-table">
            <tr>
                <th scope="row">Surse de scanat</th>
                <td>
                    <fieldset>
                        <label><input type="checkbox" name="<?php echo WebGSM_Site_Audit_Settings::OPTION_KEY; ?>[scan_posts]" value="1" <?php checked(!empty($settings['scan_posts'])); ?> /> Posturi</label><br>
                        <label><input type="checkbox" name="<?php echo WebGSM_Site_Audit_Settings::OPTION_KEY; ?>[scan_pages]" value="1" <?php checked(!empty($settings['scan_pages'])); ?> /> Pagini</label><br>
                        <label><input type="checkbox" name="<?php echo WebGSM_Site_Audit_Settings::OPTION_KEY; ?>[scan_products]" value="1" <?php checked(!empty($settings['scan_products'])); ?> /> Produse WooCommerce</label><br>
                        <label><input type="checkbox" name="<?php echo WebGSM_Site_Audit_Settings::OPTION_KEY; ?>[scan_menus]" value="1" <?php checked(!empty($settings['scan_menus'])); ?> /> Meniuri</label><br>
                        <label><input type="checkbox" name="<?php echo WebGSM_Site_Audit_Settings::OPTION_KEY; ?>[scan_widgets]" value="1" <?php checked(!empty($settings['scan_widgets'])); ?> /> Widget-uri</label><br>
                        <label><input type="checkbox" name="<?php echo WebGSM_Site_Audit_Settings::OPTION_KEY; ?>[scan_options]" value="1" <?php checked(!empty($settings['scan_options'])); ?> /> Opțiuni site</label><br>
                        <label><input type="checkbox" name="<?php echo WebGSM_Site_Audit_Settings::OPTION_KEY; ?>[scan_theme_files]" value="1" <?php checked(!empty($settings['scan_theme_files'])); ?> /> Fișiere temă (experimental)</label>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row">Tipuri de linkuri</th>
                <td>
                    <label><input type="checkbox" name="<?php echo WebGSM_Site_Audit_Settings::OPTION_KEY; ?>[check_internal]" value="1" <?php checked(!empty($settings['check_internal'])); ?> /> Linkuri interne</label><br>
                    <label><input type="checkbox" name="<?php echo WebGSM_Site_Audit_Settings::OPTION_KEY; ?>[check_external]" value="1" <?php checked(!empty($settings['check_external'])); ?> /> Linkuri externe</label>
                </td>
            </tr>
            <tr>
                <th scope="row">Timeout (secunde)</th>
                <td>
                    <input type="number" name="<?php echo WebGSM_Site_Audit_Settings::OPTION_KEY; ?>[timeout]" value="<?php echo esc_attr($settings['timeout']); ?>" min="5" max="60" />
                    <p class="description">Timp maxim de așteptare per link (5–60 sec)</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Redirect-uri</th>
                <td>
                    <label><input type="checkbox" name="<?php echo WebGSM_Site_Audit_Settings::OPTION_KEY; ?>[follow_redirects]" value="1" <?php checked(!empty($settings['follow_redirects'])); ?> /> Urmează redirect-urile</label><br>
                    <input type="number" name="<?php echo WebGSM_Site_Audit_Settings::OPTION_KEY; ?>[max_redirects]" value="<?php echo esc_attr($settings['max_redirects']); ?>" min="1" max="10" /> max redirect-uri
                </td>
            </tr>
        </table>

        <?php submit_button('Salvează setările'); ?>
    </form>

    <hr>
    <h2>Google Search Console</h2>
    <p>Pentru integrare completă cu GSC (API), este nevoie de:</p>
    <ul style="list-style:disc;margin-left:20px;">
        <li>Proiect în <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a></li>
        <li>Search Console API activat</li>
        <li>Credențiale OAuth 2.0 sau Service Account</li>
    </ul>
    <p>Până atunci, poți exporta datele din GSC manual și le poți importa în Dashboard (JSON).</p>
</div>
