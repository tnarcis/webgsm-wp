<?php
if (!defined('ABSPATH')) exit;

$broken = array_filter($results, function($r) { return (isset($r['status']) ? $r['status'] : '') !== 'ok'; });
$ok = array_filter($results, function($r) { return (isset($r['status']) ? $r['status'] : '') === 'ok'; });
?>
<div class="wrap webgsm-site-audit">
    <h1>
        <span class="dashicons dashicons-chart-area" style="font-size:28px;margin-right:8px;vertical-align:middle;color:#2271b1;"></span>
        WebGSM Site Audit – Super Tool
        <span class="wsa-version">v<?php echo esc_html(WEBGSM_SITE_AUDIT_VERSION); ?></span>
    </h1>
    <p class="description"><a href="<?php echo esc_url(admin_url('admin.php?page=webgsm-site-audit-settings')); ?>">⚙ Setări</a></p>

    <nav class="wsa-nav-tabs">
        <a href="#tab-overview" class="wsa-tab active" data-tab="tab-overview">Overview</a>
        <a href="#tab-links" class="wsa-tab" data-tab="tab-links">Linkuri</a>
        <a href="#tab-robots" class="wsa-tab" data-tab="tab-robots">Robots & Sitemap</a>
        <a href="#tab-conflicts" class="wsa-tab" data-tab="tab-conflicts">Conflicte</a>
        <a href="#tab-security" class="wsa-tab" data-tab="tab-security">Securitate</a>
        <a href="#tab-performance" class="wsa-tab" data-tab="tab-performance">Performanță</a>
        <a href="#tab-seo" class="wsa-tab" data-tab="tab-seo">SEO</a>
        <a href="#tab-debug" class="wsa-tab" data-tab="tab-debug">Debug Log</a>
        <a href="#tab-gsc" class="wsa-tab" data-tab="tab-gsc">GSC</a>
    </nav>

    <!-- OVERVIEW -->
    <div class="wsa-tab-content" id="tab-overview">
        <div class="wsa-cards">
            <div class="wsa-card wsa-card--broken">
                <span class="wsa-card-label">Linkuri rupte</span>
                <span class="wsa-card-value" id="wsa-overview-broken"><?php echo count($broken); ?></span>
            </div>
            <div class="wsa-card wsa-card--ok">
                <span class="wsa-card-label">Linkuri OK</span>
                <span class="wsa-card-value" id="wsa-overview-ok"><?php echo count($ok); ?></span>
            </div>
            <div class="wsa-card">
                <span class="wsa-card-label">PHP</span>
                <span class="wsa-card-value wsa-card-value--small"><?php echo esc_html(PHP_VERSION); ?></span>
            </div>
            <div class="wsa-card">
                <span class="wsa-card-label">WordPress</span>
                <span class="wsa-card-value wsa-card-value--small"><?php echo esc_html(get_bloginfo('version')); ?></span>
            </div>
            <div class="wsa-card">
                <span class="wsa-card-label">Pluginuri active</span>
                <span class="wsa-card-value wsa-card-value--small"><?php echo count(get_option('active_plugins', [])); ?></span>
            </div>
            <div class="wsa-card">
                <span class="wsa-card-label">Ultima scanare</span>
                <span class="wsa-card-value wsa-card-value--small"><?php echo $last_scan ? date_i18n('d M Y H:i', $last_scan) : 'Niciodată'; ?></span>
            </div>
        </div>
        <div class="wsa-overview-actions">
            <button type="button" class="button button-primary button-hero" id="wsa-full-scan-btn">
                <span class="dashicons dashicons-superhero-alt"></span> Scanare completă
            </button>
            <span class="wsa-status" id="wsa-full-scan-status"></span>
        </div>
        <div class="wsa-overview-summary" id="wsa-full-scan-summary" style="display:none;"></div>
        <p class="description" style="margin-top:16px;">Folosește tab-urile de mai sus pentru analize detaliate sau butonul „Scanare completă" pentru toate verificările.</p>
    </div>

    <!-- LINKURI -->
    <div class="wsa-tab-content" id="tab-links" style="display:none">
        <div class="wsa-actions">
            <button type="button" class="button button-primary button-hero" id="wsa-scan-btn">
                <span class="dashicons dashicons-update"></span> Rulează scan linkuri
            </button>
            <span class="wsa-status" id="wsa-status"></span>
        </div>
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
                <tr><th>URL</th><th>Status</th><th>Sursă</th></tr>
            </thead>
            <tbody>
                <?php foreach ($results as $r): ?>
                <tr data-status="<?php echo esc_attr(isset($r['status']) ? $r['status'] : ''); ?>" data-source="<?php echo esc_attr(isset($r['source']) ? $r['source'] : ''); ?>">
                    <td><a href="<?php echo esc_url(isset($r['url']) ? $r['url'] : '#'); ?>" target="_blank" rel="noopener"><?php echo esc_html(isset($r['url']) ? $r['url'] : ''); ?></a></td>
                    <td>
                        <span class="wsa-badge wsa-badge--<?php echo esc_attr(isset($r['status']) ? $r['status'] : 'unknown'); ?>">
                            <?php
                            $code = isset($r['http_code']) ? $r['http_code'] : 0;
                            $err = isset($r['error']) ? $r['error'] : '';
                            $st = isset($r['status']) ? $r['status'] : '?';
                            echo $code ? 'HTTP ' . $code : ($err ? $err : $st);
                            ?>
                        </span>
                    </td>
                    <td><?php echo esc_html(isset($r['source']) ? $r['source'] : ''); ?> – <?php echo esc_html(isset($r['source_title']) ? $r['source_title'] : ''); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($results)): ?>
                <tr><td colspan="3">Niciun rezultat. Rulează un scan.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- ROBOTS & SITEMAP -->
    <div class="wsa-tab-content" id="tab-robots" style="display:none">
        <div class="wsa-actions">
            <button type="button" class="button button-primary" id="wsa-robots-scan">
                <span class="dashicons dashicons-admin-site-alt3"></span> Verifică robots.txt & sitemap
            </button>
            <span class="wsa-status" id="wsa-robots-status"></span>
        </div>
        <div class="wsa-robots-results" id="wsa-robots-results"></div>
    </div>

    <!-- CONFLICTE -->
    <div class="wsa-tab-content" id="tab-conflicts" style="display:none">
        <div class="wsa-actions">
            <button type="button" class="button button-primary" id="wsa-conflict-scan">
                <span class="dashicons dashicons-warning"></span> Detectare conflicte CSS/JS/Plugin
            </button>
            <span class="wsa-status" id="wsa-conflict-status"></span>
        </div>
        <div class="wsa-issues-list" id="wsa-conflict-results"></div>
    </div>

    <!-- SECURITATE -->
    <div class="wsa-tab-content" id="tab-security" style="display:none">
        <div class="wsa-actions">
            <button type="button" class="button button-primary" id="wsa-security-scan">
                <span class="dashicons dashicons-shield"></span> Rulează scan securitate
            </button>
            <span class="wsa-status" id="wsa-security-status"></span>
        </div>
        <div class="wsa-issues-list" id="wsa-security-results"></div>
    </div>

    <!-- PERFORMANȚĂ -->
    <div class="wsa-tab-content" id="tab-performance" style="display:none">
        <div class="wsa-actions">
            <button type="button" class="button button-primary" id="wsa-perf-scan">
                <span class="dashicons dashicons-performance"></span> Rulează scan performanță
            </button>
            <span class="wsa-status" id="wsa-perf-status"></span>
        </div>
        <div class="wsa-issues-list" id="wsa-perf-results"></div>
    </div>

    <!-- SEO -->
    <div class="wsa-tab-content" id="tab-seo" style="display:none">
        <div class="wsa-actions">
            <button type="button" class="button button-primary" id="wsa-seo-scan">
                <span class="dashicons dashicons-google"></span> Rulează scan SEO
            </button>
            <span class="wsa-status" id="wsa-seo-status"></span>
        </div>
        <div class="wsa-issues-list" id="wsa-seo-results"></div>
    </div>

    <!-- DEBUG LOG -->
    <div class="wsa-tab-content" id="tab-debug" style="display:none">
        <div class="wsa-actions">
            <select id="wsa-debug-lines"><option value="200">200 linii</option><option value="500" selected>500</option><option value="1000">1000</option></select>
            <select id="wsa-debug-severity"><option value="">Toate</option><option value="fatal_error">Fatal</option><option value="parse_error">Parse</option><option value="warning">Warning</option><option value="notice">Notice</option><option value="deprecated">Deprecated</option></select>
            <input type="text" id="wsa-debug-filter" placeholder="Filtrează..." style="width:200px">
            <button type="button" class="button" id="wsa-debug-refresh">Reîncarcă</button>
            <button type="button" class="button" id="wsa-debug-clear" style="color:#d63638;">Golește log</button>
            <span class="wsa-status" id="wsa-debug-size"></span>
        </div>
        <div class="wsa-debug-log" id="wsa-debug-output"><pre style="max-height:500px;overflow:auto;background:#1e1e1e;color:#d4d4d4;padding:15px;font-size:12px;border-radius:4px;"></pre></div>
    </div>

    <!-- GSC -->
    <div class="wsa-tab-content" id="tab-gsc" style="display:none">
        <h2>Google Search Console</h2>
        <p class="description">Importă date din GSC (Export → JSON) pentru a vedea problemele de indexare.</p>
        <textarea id="wsa-gsc-json" rows="6" placeholder='{"pages": [...], "issues": [...]}' style="width:100%;font-family:monospace;font-size:12px;"></textarea>
        <p><button type="button" class="button" id="wsa-gsc-import">Importă JSON GSC</button></p>
        <?php if (!empty($gsc_data['pages'])): ?>
        <div class="wsa-gsc-summary">
            <strong>Date importate:</strong> <?php echo count($gsc_data['pages']); ?> pagini,
            <?php echo isset($gsc_data['indexed']) ? $gsc_data['indexed'] : 0; ?> indexate,
            <?php echo count(isset($gsc_data['issues']) ? $gsc_data['issues'] : []); ?> probleme
        </div>
        <?php endif; ?>
    </div>
</div>
