<?php
if (!defined('ABSPATH')) {
    exit;
}

$notice = isset($_GET['packeta_notice']) ? sanitize_key((string) $_GET['packeta_notice']) : '';

$notices = [
    'settings_saved' => ['class' => 'notice-success', 'text' => 'Setările au fost salvate.'],
    'prices_synced' => ['class' => 'notice-success', 'text' => 'Prețurile curierilor activi au fost actualizate din lista Packeta 2026-07-02 (cu TVA 21%).'],
    'prices_sync_partial' => ['class' => 'notice-warning', 'text' => 'Sincronizare prețuri parțială — vezi detaliile de mai jos.'],
    'no_password' => ['class' => 'notice-error', 'text' => 'Completează parola API în WooCommerce → Packeta (sau vezi Setări aici).'],
    'packet_ok' => ['class' => 'notice-success', 'text' => 'Pachet creat în Packeta.'],
    'validated' => ['class' => 'notice-success', 'text' => 'Atribute validate cu succes.'],
    'shipment_ok' => ['class' => 'notice-success', 'text' => 'Expediție creată (grupare AWB).'],
    'status_ok' => ['class' => 'notice-success', 'text' => 'Status citit.'],
    'courier_number_ok' => ['class' => 'notice-success', 'text' => 'Număr AWB curier (Sameday/Fan) obținut — vezi mai jos. Acesta se caută pe site-ul curierului, nu barcode-ul Z…'],
    'missing_packet_id' => ['class' => 'notice-error', 'text' => 'Introdu Packet ID sau barcode (ex. Z 383 2892 743).'],
    'api_error' => ['class' => 'notice-error', 'text' => 'Eroare API — vezi detaliile de mai jos.'],
    'missing_point' => ['class' => 'notice-error', 'text' => 'Pentru Box / punct fix selectează punctul pe harta Packeta.'],
    'missing_home_carrier' => ['class' => 'notice-error', 'text' => 'Pentru livrare la adresă completează addressId (transportator).'],
    'missing_home_address' => ['class' => 'notice-error', 'text' => 'Completează strada și orașul pentru livrarea la adresă.'],
    'missing_home_province' => ['class' => 'notice-error', 'text' => 'Selectează județul destinatarului (câmp province în API Packeta).'],
    'missing_home_zip' => ['class' => 'notice-error', 'text' => 'Completează codul poștal — obligatoriu la livrare la adresă.'],
    'missing_home_house' => ['class' => 'notice-error', 'text' => 'Completează numărul străzii — obligatoriu la livrare la adresă.'],
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

    <?php if ($tab === 'settings' && is_array($pricing_sync_result ?? null)) : ?>
        <div class="webgsm-packeta-card" style="margin-top:12px;">
            <h2>Rezultat sincronizare prețuri</h2>
            <?php if (!empty($pricing_sync_result['updated'])) : ?>
                <p><strong>Actualizați:</strong></p>
                <ul class="webgsm-packeta-ref-list">
                    <?php foreach ($pricing_sync_result['updated'] as $line) : ?>
                        <li><?php echo esc_html((string) $line); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <?php if (!empty($pricing_sync_result['skipped'])) : ?>
                <p><strong>Săriți:</strong></p>
                <ul class="webgsm-packeta-ref-list">
                    <?php foreach ($pricing_sync_result['skipped'] as $line) : ?>
                        <li><?php echo esc_html((string) $line); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <?php if (!empty($pricing_sync_result['errors'])) : ?>
                <p><strong>Erori:</strong></p>
                <ul class="webgsm-packeta-ref-list">
                    <?php foreach ($pricing_sync_result['errors'] as $line) : ?>
                        <li><?php echo esc_html((string) $line); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
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
            } elseif (($last['type'] ?? '') === 'packet' && isset($last['data']['data']) && $last['data']['data'] !== '' && $last['data']['data'] !== []) {
                $r = $last['data']['data'];
                if (is_array($r)) {
                    $id = isset($r['id']) && is_scalar($r['id']) ? (string) $r['id'] : '';
                    $barcode = isset($r['barcode']) && is_scalar($r['barcode']) ? (string) $r['barcode'] : '';
                    $btext = isset($r['barcodeText']) && is_scalar($r['barcodeText']) ? (string) $r['barcodeText'] : '';
                } elseif ($r instanceof \SimpleXMLElement) {
                    $id = isset($r->id) ? (string) $r->id : '';
                    $barcode = isset($r->barcode) ? (string) $r->barcode : '';
                    $btext = isset($r->barcodeText) ? (string) $r->barcodeText : '';
                } else {
                    $id = '';
                    $barcode = '';
                    $btext = '';
                }
                echo '<div class="webgsm-packeta-result"><p><strong>Packet ID:</strong> ' . esc_html($id) . '</p>';
                if ($barcode !== '') {
                    echo '<p><strong>Barcode:</strong> ' . esc_html($barcode) . '</p>';
                }
                if ($btext !== '') {
                    echo '<p><strong>Barcode text:</strong> ' . esc_html($btext) . '</p>';
                }
                echo '<p><a class="button" href="' . esc_url(admin_url('admin.php?page=webgsm-packeta&tab=label')) . '">Deschide Etichetă &amp; status</a></p>';
                if ($id !== '') {
                    $shipment_url = add_query_arg(
                        ['page' => 'webgsm-packeta', 'tab' => 'shipment', 'prefill_packet' => $id],
                        admin_url('admin.php')
                    );
                    echo '<p class="notice notice-warning inline" style="margin-top:12px;"><strong>Ridicare curier:</strong> AWB-ul există în Packeta, dar pentru ridicare trebuie să creezi expediția (grupare). '
                        . '<a class="button button-secondary" href="' . esc_url($shipment_url) . '">Expediție / ridicare</a></p>';
                }
                echo '</div>';
            } elseif (($last['type'] ?? '') === 'shipment' && isset($last['data']['data']) && $last['data']['data'] !== '' && $last['data']['data'] !== []) {
                $r = $last['data']['data'];
                if ($r instanceof \SimpleXMLElement) {
                    $out = $r->asXML();
                } elseif (is_array($r) || is_string($r)) {
                    $out = wp_json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                } else {
                    $out = '';
                }
                echo '<div class="webgsm-packeta-result"><pre>' . esc_html((string) $out) . '</pre></div>';
            } elseif (($last['type'] ?? '') === 'courier_number') {
                $pid = isset($last['packet_id']) ? (string) $last['packet_id'] : '';
                $cn = isset($last['courier_number']) ? (string) $last['courier_number'] : '';
                echo '<div class="webgsm-packeta-result"><p><strong>Packet ID Packeta:</strong> ' . esc_html($pid) . '</p>';
                echo '<p><strong>AWB curier (Sameday/Fan) — caută acest număr pe site-ul curierului:</strong></p>';
                echo '<p style="font-size:18px;"><code>' . esc_html($cn) . '</code></p>';
                echo '<p class="webgsm-packeta-help">Barcode-ul <code>Z …</code> nu funcționează pe sameday.ro — doar numărul de mai sus, după ce Packeta l-a înregistrat la curier.</p></div>';
            } elseif (($last['type'] ?? '') === 'status' && isset($last['data']['data']) && $last['data']['data'] !== '' && $last['data']['data'] !== []) {
                $r = $last['data']['data'];
                if ($r instanceof \SimpleXMLElement) {
                    $out = $r->asXML();
                } elseif (is_array($r) || is_string($r)) {
                    $out = wp_json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                } else {
                    $out = '';
                }
                echo '<div class="webgsm-packeta-result"><pre>' . esc_html((string) $out) . '</pre></div>';
            }
            ?>
        </div>
    <?php endif; ?>
</div>
