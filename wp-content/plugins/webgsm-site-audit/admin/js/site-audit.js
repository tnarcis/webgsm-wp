(function($) {
    'use strict';

    const $scanBtn = $('#wsa-scan-btn');
    const $status = $('#wsa-status');
    const $total = $('#wsa-total');
    const $broken = $('#wsa-broken');
    const $ok = $('#wsa-ok');
    const $table = $('#wsa-results-table tbody');
    const $filterStatus = $('#wsa-filter-status');
    const $filterSource = $('#wsa-filter-source');
    const $gscImport = $('#wsa-gsc-import');
    const $gscJson = $('#wsa-gsc-json');

    function setStatus(msg, isError) {
        $status.text(msg).css('color', isError ? '#d63638' : '#646970');
    }

    function filterRows() {
        const status = $filterStatus.val();
        const source = $filterSource.val();
        $('#wsa-results-table tbody tr').each(function() {
            const $row = $(this);
            const rowStatus = $row.data('status');
            const rowSource = $row.data('source');
            const matchStatus = !status || rowStatus === status;
            const matchSource = !source || rowSource === source;
            $row.toggle(matchStatus && matchSource);
        });
    }

    $filterStatus.add($filterSource).on('change', filterRows);

    $scanBtn.on('click', function() {
        const $btn = $(this);
        $btn.prop('disabled', true);
        setStatus('Se scanează...');

        $.post(webgsmSiteAudit.ajaxurl, {
            action: 'webgsm_audit_scan_links',
            nonce: webgsmSiteAudit.nonce
        })
        .done(function(res) {
            if (res.success) {
                setStatus('Scan finalizat: ' + res.data.total + ' linkuri, ' + res.data.broken + ' rupte.');
                $total.text(res.data.total);
                $broken.text(res.data.broken);
                $ok.text(res.data.total - res.data.broken);

                $table.empty();
                if (res.data.results && res.data.results.length) {
                    res.data.results.forEach(function(r) {
                        const statusText = r.http_code ? 'HTTP ' + r.http_code : (r.error || r.status || '?');
                        $table.append(
                            '<tr data-status="' + (r.status || '') + '" data-source="' + (r.source || '') + '">' +
                            '<td><a href="' + r.url + '" target="_blank" rel="noopener">' + r.url + '</a></td>' +
                            '<td><span class="wsa-badge wsa-badge--' + (r.status || 'unknown') + '">' + statusText + '</span></td>' +
                            '<td>' + (r.source || '') + ' – ' + (r.source_title || '') + '</td>' +
                            '</tr>'
                        );
                    });
                } else {
                    $table.append('<tr><td colspan="3">Niciun rezultat.</td></tr>');
                }
            } else {
                setStatus('Eroare: ' + (res.data || 'necunoscută'), true);
            }
        })
        .fail(function() {
            setStatus('Eroare de rețea.', true);
        })
        .always(function() {
            $btn.prop('disabled', false);
        });
    });

    $gscImport.on('click', function() {
        const json = $gscJson.val().trim();
        if (!json) {
            alert('Lipește JSON-ul din GSC.');
            return;
        }

        $gscImport.prop('disabled', true);

        $.post(webgsmSiteAudit.ajaxurl, {
            action: 'webgsm_audit_import_gsc',
            nonce: webgsmSiteAudit.nonce,
            gsc_json: json
        })
        .done(function(res) {
            if (res.success) {
                alert('Import reușit: ' + (res.data.pages || 0) + ' pagini, ' + (res.data.issues || 0) + ' probleme.');
                location.reload();
            } else {
                alert('Eroare: ' + (res.data || 'necunoscută'));
            }
        })
        .fail(function() {
            alert('Eroare de rețea.');
        })
        .always(function() {
            $gscImport.prop('disabled', false);
        });
    });
})(jQuery);
