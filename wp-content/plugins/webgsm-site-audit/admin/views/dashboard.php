<?php
if (!defined('ABSPATH')) exit;

$broken = array_filter($results, fn($r) => ($r['status'] ?? '') !== 'ok');
$ok = array_filter($results, fn($r) => ($r['status'] ?? '') === 'ok');
?>
<div class="wrap webgsm-site-audit">
    <h1>Site Audit</h1>

    <div class="wsa-cards">
        <div class="wsa-card">
            <span class="wsa-card-label">Linkuri scanate</span>
            <span class="wsa-card-value" id="wsa-total"><?php echo count($results); ?></span>
        </div>
        <div class="wsa-card wsa-card--broken">
            <span class="wsa-card-label">Linkuri rupte</span>
            <span class="wsa-card-value" id="wsa-broken"><?php echo count($broken); ?></span>
        </div>
        <div class="wsa-card wsa-card--ok">
            <span class="wsa-card-label">Linkuri OK</span>
            <span class="wsa-card-value" id="wsa-ok"><?php echo count($ok); ?></span>
        </div>
        <div class="wsa-card">
            <span class="wsa-card-label">Ultimul scan</span>
            <span class="wsa-card-value wsa-card-value--small">
                <?php echo $last_scan ? date_i18n('d.m.Y H:i', $last_scan) : '—'; ?>
            </span>
        </div>
    </div>

    <div class="wsa-actions">
        <button type="button" class="button button-primary button-hero" id="wsa-scan-btn">
            <span class="dashicons dashicons-update"></span> Rulează scan
        </button>
        <span class="wsa-status" id="wsa-status"></span>
    </div>

    <div class="wsa-section">
        <h2>Google Search Console</h2>
        <p class="description">Importă date din GSC (Export → JSON) pentru a vedea problemele de indexare.</p>
        <textarea id="wsa-gsc-json" rows="6" placeholder='{"pages": [...], "issues": [...]}' style="width:100%;font-family:monospace;font-size:12px;"></textarea>
        <p>
            <button type="button" class="button" id="wsa-gsc-import">Importă JSON GSC</button>
        </p>
        <?php if (!empty($gsc_data['pages'])): ?>
        <div class="wsa-gsc-summary">
            <strong>Date importate:</strong> <?php echo count($gsc_data['pages']); ?> pagini,
            <?php echo $gsc_data['indexed'] ?? 0; ?> indexate,
            <?php echo count($gsc_data['issues'] ?? []); ?> probleme
        </div>
        <?php endif; ?>
    </div>

    <div class="wsa-section">
        <h2>Linkuri rupte</h2>
        <div class="wsa-filters">
            <select id="wsa-filter-status">
                <option value="">Toate statusurile</option>
                <option value="broken">Rupte</option>
                <option value="error">Erori</option>
                <option value="ok">OK</option>
            </select>
            <select id="wsa-filter-source">
                <option value="">Toate sursele</option>
                <option value="post">Posturi</option>
                <option value="page">Pagini</option>
                <option value="product">Produse</option>
                <option value="menu">Meniuri</option>
                <option value="widget">Widget-uri</option>
            </select>
        </div>
        <table class="wp-list-table widefat fixed striped" id="wsa-results-table">
            <thead>
                <tr>
                    <th>URL</th>
                    <th>Status</th>
                    <th>Sursă</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $r): ?>
                <tr data-status="<?php echo esc_attr($r['status'] ?? ''); ?>" data-source="<?php echo esc_attr($r['source'] ?? ''); ?>">
                    <td>
                        <a href="<?php echo esc_url($r['url']); ?>" target="_blank" rel="noopener"><?php echo esc_html($r['url']); ?></a>
                    </td>
                    <td>
                        <span class="wsa-badge wsa-badge--<?php echo esc_attr($r['status'] ?? 'unknown'); ?>">
                            <?php
                            $code = $r['http_code'] ?? 0;
                            $err = $r['error'] ?? '';
                            echo $code ? "HTTP $code" : $err ?: ($r['status'] ?? '?');
                            ?>
                        </span>
                    </td>
                    <td><?php echo esc_html($r['source'] ?? ''); ?> – <?php echo esc_html($r['source_title'] ?? ''); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($results)): ?>
                <tr><td colspan="3">Niciun rezultat. Rulează un scan.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
