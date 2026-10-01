<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1>Site Ops — diagnostic WebGSM</h1>
    <p class="description">
        Probe din WordPress (mediu, autoload, Action Scheduler, query-uri reprezentative, LiteSpeed).
        Slow query-urile complete se văd doar în logul MySQL de la hosting — acest ecran arată ce putem controla din admin.
    </p>
    <p>
        <button type="button" class="button button-primary" id="webgsm-ops-run">Rulează diagnostic</button>
        <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=webgsm-litespeed')); ?>">⚡ LiteSpeed preset</a>
    </p>
    <div id="webgsm-ops-status" style="display:none;margin:12px 0;padding:10px;background:#f6f7f7;"></div>
    <div id="webgsm-ops-out"></div>
</div>
<script>
jQuery(function($) {
    var nonce = '<?php echo esc_js(wp_create_nonce('webgsm_tools')); ?>';
    function color(level) {
        if (level === 'error') return '#b91c1c';
        if (level === 'warn') return '#b45309';
        if (level === 'ok') return '#059669';
        return '#334155';
    }
    $('#webgsm-ops-run').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true);
        $('#webgsm-ops-status').show().text('Se rulează probele…');
        $.post(ajaxurl, { action: 'webgsm_site_ops_run', nonce: nonce }, function(res) {
            $btn.prop('disabled', false);
            if (!res.success) {
                $('#webgsm-ops-status').text(res.data && res.data.message ? res.data.message : 'Eroare');
                return;
            }
            var d = res.data;
            $('#webgsm-ops-status').text('Gata · ' + d.generated_at);
            var html = '';
            html += '<h2>Probleme / semnale</h2><ul>';
            (d.issues || []).forEach(function(it) {
                html += '<li style="color:' + color(it.level) + '"><strong>[' + it.level + ']</strong> ' + $('<div/>').text(it.message).html() + '</li>';
            });
            html += '</ul>';
            html += '<h2>Mediu</h2><pre style="background:#111;color:#eee;padding:12px;max-width:800px;">' + $('<div/>').text(JSON.stringify(d.environment, null, 2)).html() + '</pre>';
            html += '<h2>Probe (ms)</h2><table class="widefat" style="max-width:800px;"><thead><tr><th>Test</th><th>ms</th><th>Detaliu</th></tr></thead><tbody>';
            (d.probes || []).forEach(function(p) {
                html += '<tr><td>' + $('<div/>').text(p.label).html() + '</td><td>' + p.ms + '</td><td>' + $('<div/>').text(p.detail).html() + '</td></tr>';
            });
            html += '</tbody></table>';
            html += '<h2>Autoload options</h2><p>' + (d.autoload.rows || 0) + ' rânduri, ' + (d.autoload.bytes || 0) + ' bytes</p>';
            html += '<h2>Action Scheduler</h2><pre style="background:#111;color:#eee;padding:12px;max-width:800px;">' + $('<div/>').text(JSON.stringify(d.action_scheduler, null, 2)).html() + '</pre>';
            html += '<h2>Pași următori</h2><ol>';
            (d.next_steps || []).forEach(function(s) {
                html += '<li>' + $('<div/>').text(s).html() + '</li>';
            });
            html += '</ol>';
            $('#webgsm-ops-out').html(html);
        }).fail(function() {
            $btn.prop('disabled', false);
            $('#webgsm-ops-status').text('Eroare de conexiune');
        });
    });
});
</script>
