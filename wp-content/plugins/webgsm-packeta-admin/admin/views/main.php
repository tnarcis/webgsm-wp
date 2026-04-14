<?php
if (!defined('ABSPATH')) {
    exit;
}

$notice = isset($_GET['packeta_notice']) ? sanitize_key((string) $_GET['packeta_notice']) : '';

$notices = [
    'settings_saved' => ['class' => 'notice-success', 'text' => 'Setările au fost salvate.'],
    'no_password' => ['class' => 'notice-error', 'text' => 'Completează parola API în WooCommerce → Packeta (sau vezi Setări aici).'],
    'packet_ok' => ['class' => 'notice-success', 'text' => 'Pachet creat în Packeta.'],
    'validated' => ['class' => 'notice-success', 'text' => 'Atribute validate cu succes.'],
    'shipment_ok' => ['class' => 'notice-success', 'text' => 'Expediție creată (grupare AWB).'],
    'status_ok' => ['class' => 'notice-success', 'text' => 'Status citit.'],
    'api_error' => ['class' => 'notice-error', 'text' => 'Eroare API — vezi detaliile de mai jos.'],
    'missing_point' => ['class' => 'notice-error', 'text' => 'Pentru Box / punct fix selectează punctul pe harta Packeta.'],
    'missing_home_carrier' => ['class' => 'notice-error', 'text' => 'Pentru livrare la adresă completează addressId (transportator).'],
    'missing_home_address' => ['class' => 'notice-error', 'text' => 'Completează strada și orașul pentru livrarea la adresă.'],
    'missing_parcel_value' => ['class' => 'notice-error', 'text' => 'Valoarea declarată a coletului trebuie să fie mai mare ca 0 (asigurare Packeta). Nu e același lucru cu rambursul: la acte fără COD poți folosi o valoare simbolică (ex. 1) dacă e cazul.'],
];

?>
<div class="wrap webgsm-packeta-wrap<?php echo isset($tab) && $tab === 'awb' ? ' webgsm-packeta-layout-wide' : ''; ?>">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <p class="description">
        Creare manuală AWB și grupare expediție, prin
        <a href="https://docs.packeta.com/docs/getting-started/packeta-api" target="_blank" rel="noopener noreferrer">API Packeta</a>.
        Parola API și cheia hărții sunt cele din <strong>WooCommerce → Packeta</strong> (același cont ca la magazin).
    </p>

    <?php if ($notice !== '' && isset($notices[$notice])) : ?>
        <div class="notice <?php echo esc_attr($notices[$notice]['class']); ?> is-dismissible">
            <p><?php echo esc_html($notices[$notice]['text']); ?></p>
        </div>
    <?php endif; ?>

    <h2 class="nav-tab-wrapper">
        <a href="<?php echo esc_url(admin_url('admin.php?page=webgsm-packeta&tab=settings')); ?>" class="nav-tab <?php echo $tab === 'settings' ? 'nav-tab-active' : ''; ?>">Setări</a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=webgsm-packeta&tab=awb')); ?>" class="nav-tab <?php echo $tab === 'awb' ? 'nav-tab-active' : ''; ?>">AWB nou</a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=webgsm-packeta&tab=shipment')); ?>" class="nav-tab <?php echo $tab === 'shipment' ? 'nav-tab-active' : ''; ?>">Expediție / ridicare</a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=webgsm-packeta&tab=label')); ?>" class="nav-tab <?php echo $tab === 'label' ? 'nav-tab-active' : ''; ?>">Etichetă &amp; status</a>
    </h2>

    <?php if ($tab === 'settings') : ?>
        <?php include WEBGSM_PACKETA_PATH . 'admin/views/tab-settings.php'; ?>
    <?php elseif ($tab === 'awb') : ?>
        <?php include WEBGSM_PACKETA_PATH . 'admin/views/tab-awb.php'; ?>
    <?php elseif ($tab === 'shipment') : ?>
        <?php include WEBGSM_PACKETA_PATH . 'admin/views/tab-shipment.php'; ?>
    <?php else : ?>
        <?php include WEBGSM_PACKETA_PATH . 'admin/views/tab-label.php'; ?>
    <?php endif; ?>

    <?php if (is_array($last)) : ?>
        <div class="webgsm-packeta-card" style="margin-top:20px;">
            <h2>Ultimul răspuns</h2>
            <?php
            if (($last['type'] ?? '') === 'error') {
                echo '<div class="webgsm-packeta-result error"><p><strong>' . esc_html((string) ($last['message'] ?? 'Eroare')) . '</strong></p>';
                if (!empty($last['raw'])) {
                    $raw = (string) $last['raw'];
                    echo '<pre>' . esc_html(strlen($raw) > 8000 ? substr($raw, 0, 8000) . '…' : $raw) . '</pre>';
                }
                echo '</div>';
            } elseif (($last['type'] ?? '') === 'packet' && !empty($last['data']['data'])) {
                $r = $last['data']['data'];
                $id = isset($r->id) ? (string) $r->id : '';
                $barcode = isset($r->barcode) ? (string) $r->barcode : '';
                $btext = isset($r->barcodeText) ? (string) $r->barcodeText : '';
                echo '<div class="webgsm-packeta-result"><p><strong>Packet ID:</strong> ' . esc_html($id) . '</p>';
                if ($barcode !== '') {
                    echo '<p><strong>Barcode:</strong> ' . esc_html($barcode) . '</p>';
                }
                if ($btext !== '') {
                    echo '<p><strong>Barcode text:</strong> ' . esc_html($btext) . '</p>';
                }
                echo '<p><a class="button" href="' . esc_url(admin_url('admin.php?page=webgsm-packeta&tab=label')) . '">Deschide Etichetă &amp; status</a></p></div>';
            } elseif (($last['type'] ?? '') === 'shipment' && !empty($last['data']['data'])) {
                $r = $last['data']['data'];
                $out = $r instanceof \SimpleXMLElement ? $r->asXML() : wp_json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                echo '<div class="webgsm-packeta-result"><pre>' . esc_html((string) $out) . '</pre></div>';
            } elseif (($last['type'] ?? '') === 'status' && !empty($last['data']['data'])) {
                $r = $last['data']['data'];
                $out = $r instanceof \SimpleXMLElement ? $r->asXML() : wp_json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                echo '<div class="webgsm-packeta-result"><pre>' . esc_html((string) $out) . '</pre></div>';
            }
            ?>
        </div>
    <?php endif; ?>
</div>
