<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap webgsm-lscwp-wrap">
    <h1>LiteSpeed — Audit &amp; preset WebGSM</h1>
    <p class="description">
        Scanează setările LiteSpeed Cache pentru magazin (Woo, B2B, sync gestiune Railway).
        Nu înlocuiește tab-urile LiteSpeed, dar poți aplica presetul recomandat dintr-un click.
    </p>

    <p>
        <button type="button" class="button button-primary" id="webgsm-lscwp-audit">Rulează audit</button>
        <button type="button" class="button button-secondary" id="webgsm-lscwp-apply">Aplică preset WebGSM</button>
        <button type="button" class="button" id="webgsm-lscwp-purge">Purge All</button>
    </p>

    <div id="webgsm-lscwp-status" style="margin:12px 0;padding:12px;background:#f6f7f7;border-left:4px solid #2271b1;display:none;"></div>

    <h2>Snapshot</h2>
    <pre id="webgsm-lscwp-snapshot" style="background:#1e1e1e;color:#d4d4d4;padding:12px;max-width:720px;overflow:auto;">Apasă «Rulează audit».</pre>

    <h2>Rezultate</h2>
    <ul id="webgsm-lscwp-issues" style="max-width:900px;line-height:1.5;"></ul>

    <h2>Ce face presetul «WebGSM»</h2>
    <ul style="max-width:900px;">
        <li>Cache ON, <strong>logged-in OFF</strong>, REST necache-uit (TTL 29s)</li>
        <li>TTL public / homepage <strong>3600s</strong> (gestiune stoc/preț)</li>
        <li>Excludes: coș, checkout, cont, wp-json, reparații</li>
        <li>ESI OFF, Serve Stale OFF, purge la upgrade ON</li>
        <li>Auto-purge: front, home, pages, categorii, arhive produs</li>
        <li>Vary Group toate rolurile <strong>0</strong></li>
        <li>După apply: <strong>purge la salvare produs</strong> (sync gestiune)</li>
    </ul>
    <p class="description">Scheduled Purge URLs rămân goale (opțional le setezi manual după ora sync-ului Railway).</p>
</div>
<script>
jQuery(function($) {
    var nonce = '<?php echo esc_js(wp_create_nonce('webgsm_tools')); ?>';
    var $status = $('#webgsm-lscwp-status');
    var $issues = $('#webgsm-lscwp-issues');
    var $snap = $('#webgsm-lscwp-snapshot');

    function showStatus(msg, ok) {
        $status.show().css('border-color', ok ? '#00a32a' : '#d63638').text(msg);
    }

    function render(data) {
        if (data.snapshot) {
            $snap.text(JSON.stringify(data.snapshot, null, 2));
        }
        $issues.empty();
        (data.issues || []).forEach(function(it) {
            var color = it.level === 'error' ? '#b91c1c' : (it.level === 'warn' ? '#b45309' : (it.level === 'ok' ? '#059669' : '#64748b'));
            var li = $('<li></li>').css('color', color);
            li.append($('<strong></strong>').text('[' + it.level.toUpperCase() + '] '));
            li.append(document.createTextNode(it.message));
            if (it.fix) {
                li.append($('<br><span></span>').css('font-size', '12px').text('→ ' + it.fix));
            }
            $issues.append(li);
        });
    }

    function post(action, btn) {
        var $btn = $(btn);
        $btn.prop('disabled', true);
        $.post(ajaxurl, { action: action, nonce: nonce }, function(res) {
            $btn.prop('disabled', false);
            if (res.success) {
                showStatus(res.data.message || 'OK', true);
                render(res.data);
            } else {
                showStatus(res.data && res.data.message ? res.data.message : 'Eroare', false);
            }
        }).fail(function() {
            $btn.prop('disabled', false);
            showStatus('Eroare AJAX / conexiune', false);
        });
    }

    $('#webgsm-lscwp-audit').on('click', function() {
        $.post(ajaxurl, { action: 'webgsm_lscwp_audit', nonce: nonce }, function(res) {
            if (res.success) {
                showStatus('Audit finalizat.', true);
                render(res.data);
            } else {
                showStatus(res.data && res.data.message ? res.data.message : 'Eroare audit', false);
            }
        });
    });
    $('#webgsm-lscwp-apply').on('click', function() {
        if (!confirm('Aplic preset WebGSM LiteSpeed și Purge All. Continui?')) return;
        post('webgsm_lscwp_apply_preset', this);
    });
    $('#webgsm-lscwp-purge').on('click', function() {
        post('webgsm_lscwp_purge_all', this);
    });
});
</script>
