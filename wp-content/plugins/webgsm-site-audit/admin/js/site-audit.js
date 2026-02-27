(function($) {
    'use strict';

    var ajax = webgsmSiteAudit.ajaxurl;
    var nonce = webgsmSiteAudit.nonce;

    function post(action, extra, timeoutMs) {
        var data = $.extend({ action: action, nonce: nonce }, extra || {});
        return $.ajax({
            url: ajax,
            type: 'POST',
            data: data,
            timeout: timeoutMs || 120000
        });
    }

    function setStatus(msg, isError, $target) {
        ($target || $('#wsa-status')).html(msg).css('color', isError ? '#d63638' : '#646970');
    }

    function escapeHtml(s) {
        var div = document.createElement('div');
        div.textContent = s || '';
        return div.innerHTML;
    }

    function spinner() {
        return '<span class="spinner is-active" style="float:none;margin:0 6px;"></span>';
    }

    // --- TABS ---
    $('.wsa-tab').on('click', function(e) {
        e.preventDefault();
        var target = $(this).attr('href');
        $('.wsa-tab').removeClass('active');
        $(this).addClass('active');
        $('.wsa-tab-content').hide();
        $(target).show();
        if (target === '#tab-debug') loadDebugLog();
    });

    // --- LINK FILTERS ---
    $('#wsa-filter-status, #wsa-filter-source').on('change', function() {
        var status = $('#wsa-filter-status').val();
        var source = $('#wsa-filter-source').val();
        $('#wsa-results-table tbody tr').each(function() {
            var $row = $(this);
            $row.toggle(
                (!status || $row.data('status') === status) &&
                (!source || $row.data('source') === source)
            );
        });
    });

    function updateOverview(broken, ok) {
        $('#wsa-overview-broken').text(broken);
        $('#wsa-overview-ok').text(ok);
    }

    // --- LINK SCAN ---
    $('#wsa-scan-btn').on('click', function() {
        var $btn = $(this).prop('disabled', true);
        var $tbody = $('#wsa-results-table tbody').empty();
        $tbody.append('<tr><td colspan="4">' + spinner() + ' Se scanează linkurile... poate dura 1-2 minute.</td></tr>');
        setStatus(spinner() + ' Se scanează...');

        post('webgsm_audit_scan_links', {}, 300000)
            .done(function(res) {
                if (res.success) {
                    setStatus('Scan finalizat: ' + res.data.total + ' linkuri, ' + res.data.broken + ' rupte.');
                    updateOverview(res.data.broken, res.data.total - res.data.broken);
                    renderLinkTable(res.data.results);
                } else {
                    setStatus('Eroare: ' + (res.data || 'necunoscută'), true);
                    $tbody.empty().append('<tr><td colspan="4">Eroare la scanare.</td></tr>');
                }
            })
            .fail(function(xhr, status) {
                if (status === 'timeout') {
                    setStatus('Timeout – prea multe linkuri. Reduce scope-ul din Setări.', true);
                } else {
                    setStatus('Eroare de rețea.', true);
                }
                $tbody.empty().append('<tr><td colspan="4">Scanarea nu s-a finalizat.</td></tr>');
            })
            .always(function() { $btn.prop('disabled', false); });
    });

    function renderLinkTable(results) {
        var $tbody = $('#wsa-results-table tbody').empty();
        if (!results || !results.length) {
            $tbody.append('<tr><td colspan="4">Niciun rezultat.</td></tr>');
            return;
        }
        results.forEach(function(r) {
            var statusText = r.http_code ? 'HTTP ' + r.http_code : (r.error || r.status || '?');
            var anchor = r.anchor_text ? ('<div style="color:#646970;font-size:12px;margin-top:4px;">Anchor: ' + escapeHtml(r.anchor_text) + '</div>') : '';
            var actions = r.source_edit_url ? ('<a class="button button-small" href="' + escapeHtml(r.source_edit_url) + '" target="_blank" rel="noopener">Deschide sursa</a>') : '—';
            $tbody.append(
                '<tr data-status="' + escapeHtml(r.status || '') + '" data-source="' + escapeHtml(r.source || '') + '">' +
                '<td><a href="' + escapeHtml(r.url || '#') + '" target="_blank" rel="noopener">' + escapeHtml(r.url) + '</a>' + anchor + '</td>' +
                '<td><span class="wsa-badge wsa-badge--' + escapeHtml(r.status || 'unknown') + '">' + escapeHtml(statusText) + '</span></td>' +
                '<td>' + escapeHtml(r.source || '') + ' – ' + escapeHtml(r.source_title || '') + '</td>' +
                '<td>' + actions + '</td></tr>'
            );
        });
    }

    // --- ROBOTS & SITEMAP ---
    $('#wsa-robots-scan').on('click', function() {
        var $btn = $(this).prop('disabled', true);
        $('#wsa-robots-results').html('<p>' + spinner() + ' Se verifică...</p>');
        setStatus(spinner() + ' Se verifică...', false, $('#wsa-robots-status'));

        post('webgsm_audit_robots_sitemap')
            .done(function(res) {
                if (res.success) {
                    renderRobotsSitemap(res.data);
                    var total = (res.data.robots.issues || []).length + (res.data.sitemap.issues || []).length;
                    setStatus(total + ' probleme, ' + ((res.data.robots.info || []).length + (res.data.sitemap.info || []).length) + ' info.', false, $('#wsa-robots-status'));
                } else {
                    setStatus('Eroare: ' + (res.data || ''), true, $('#wsa-robots-status'));
                    $('#wsa-robots-results').html('<p class="wsa-err">Eroare la verificare.</p>');
                }
            })
            .fail(function() {
                setStatus('Eroare de rețea.', true, $('#wsa-robots-status'));
                $('#wsa-robots-results').html('<p class="wsa-err">Eroare de rețea.</p>');
            })
            .always(function() { $btn.prop('disabled', false); });
    });

    function renderRobotsSitemap(data) {
        var $el = $('#wsa-robots-results');
        var html = '';

        html += '<div class="wsa-section"><h3>robots.txt</h3>';
        if (data.robots.exists) {
            html += '<div class="wsa-badge wsa-badge--ok" style="margin-bottom:12px;">Există</div>';
        } else {
            html += '<div class="wsa-badge wsa-badge--broken" style="margin-bottom:12px;">Nu există / inaccesibil</div>';
        }
        (data.robots.issues || []).forEach(function(i) { html += renderIssueHtml(i); });
        (data.robots.info || []).forEach(function(info) { html += '<div class="wsa-info">' + escapeHtml(info) + '</div>'; });
        if (data.robots.content) {
            html += '<details style="margin-top:12px;"><summary style="cursor:pointer;color:#2271b1;">Conținut robots.txt</summary>';
            html += '<pre class="wsa-robots-pre">' + escapeHtml(data.robots.content) + '</pre></details>';
        }
        html += '</div>';

        html += '<div class="wsa-section"><h3>Sitemap XML</h3>';
        if (data.sitemap.found) {
            html += '<div class="wsa-badge wsa-badge--ok" style="margin-bottom:12px;">Găsit</div>';
            if (data.sitemap.url) html += '<p><a href="' + escapeHtml(data.sitemap.url) + '" target="_blank">' + escapeHtml(data.sitemap.url) + '</a></p>';
            if (data.sitemap.type) html += '<p>Tip: ' + escapeHtml(data.sitemap.type) + ' | Elemente: ' + (data.sitemap.urls_count || 0) + '</p>';
        } else {
            html += '<div class="wsa-badge wsa-badge--broken" style="margin-bottom:12px;">Nu a fost găsit</div>';
        }
        (data.sitemap.issues || []).forEach(function(i) { html += renderIssueHtml(i); });
        (data.sitemap.info || []).forEach(function(info) { html += '<div class="wsa-info">' + escapeHtml(info) + '</div>'; });
        html += '</div>';

        $el.html(html);
    }

    // --- GENERIC SCAN HANDLER ---
    function scanModule(btnId, statusId, resultsId, action, showUrl) {
        $(btnId).on('click', function() {
            var $btn = $(this).prop('disabled', true);
            $(resultsId).html('<p>' + spinner() + ' Se scanează...</p>');
            setStatus(spinner() + ' Se scanează...', false, $(statusId));

            post(action)
                .done(function(res) {
                    if (res.success) {
                        setStatus(res.data.count + ' probleme găsite.', false, $(statusId));
                        renderIssues(resultsId, res.data.issues, showUrl);
                    } else {
                        setStatus('Eroare', true, $(statusId));
                        $(resultsId).html('<p class="wsa-err">Eroare la scanare.</p>');
                    }
                })
                .fail(function() {
                    setStatus('Eroare de rețea.', true, $(statusId));
                    $(resultsId).html('<p class="wsa-err">Eroare de rețea.</p>');
                })
                .always(function() { $btn.prop('disabled', false); });
        });
    }

    scanModule('#wsa-conflict-scan', '#wsa-conflict-status', '#wsa-conflict-results', 'webgsm_audit_conflict_scan');
    scanModule('#wsa-security-scan', '#wsa-security-status', '#wsa-security-results', 'webgsm_audit_security_scan');
    scanModule('#wsa-perf-scan', '#wsa-perf-status', '#wsa-perf-results', 'webgsm_audit_performance_scan');
    scanModule('#wsa-seo-scan', '#wsa-seo-status', '#wsa-seo-results', 'webgsm_audit_seo_scan', true);

    // --- RENDER ISSUES ---
    function renderIssueHtml(i) {
        var sev = i.severity || 'info';
        var sevLabel = { high: 'CRITIC', medium: 'MEDIU', low: 'MINOR', info: 'INFO' };
        var html = '<div class="wsa-issue wsa-issue--' + sev + '">';
        html += '<span class="wsa-sev-badge wsa-sev-badge--' + sev + '">' + (sevLabel[sev] || sev) + '</span> ';
        html += '<strong>' + escapeHtml(i.title || '') + '</strong>';
        if (i.detail) html += '<div class="wsa-issue-detail">' + escapeHtml(i.detail) + '</div>';
        if (i.path) html += ' <code>' + escapeHtml(i.path) + '</code>';
        if (i.fix) html += '<div class="wsa-fix"><span class="dashicons dashicons-lightbulb"></span> ' + escapeHtml(i.fix) + '</div>';
        html += '</div>';
        return html;
    }

    function renderIssues(selector, issues, showUrl) {
        var $el = $(selector);
        if (!issues || !issues.length) {
            $el.html('<div class="wsa-no-issues"><span class="dashicons dashicons-yes-alt"></span> Nicio problemă găsită. Totul e în regulă!</div>');
            return;
        }
        var html = '';
        issues.forEach(function(i) {
            var extra = '';
            if (showUrl && i.url) extra = ' <a href="' + escapeHtml(i.url) + '" target="_blank">' + escapeHtml(i.page || i.url) + '</a>';
            var sev = i.severity || 'info';
            var sevLabel = { high: 'CRITIC', medium: 'MEDIU', low: 'MINOR', info: 'INFO' };
            html += '<div class="wsa-issue wsa-issue--' + sev + '">';
            html += '<span class="wsa-sev-badge wsa-sev-badge--' + sev + '">' + (sevLabel[sev] || sev) + '</span> ';
            html += '<strong>' + escapeHtml(i.title || '') + '</strong>' + extra;
            if (i.detail) html += '<div class="wsa-issue-detail">' + escapeHtml(i.detail) + '</div>';
            if (i.path) html += ' <code>' + escapeHtml(i.path) + '</code>';
            if (i.value) html += ' <span class="wsa-val">(' + escapeHtml(String(i.value)) + ')</span>';
            if (i.fix) html += '<div class="wsa-fix"><span class="dashicons dashicons-lightbulb"></span> ' + escapeHtml(i.fix) + '</div>';
            html += '</div>';
        });
        $el.html(html);
    }

    // --- DEBUG LOG ---
    function loadDebugLog() {
        var $out = $('#wsa-debug-output pre');
        $out.html(spinner() + ' Se încarcă...');
        post('webgsm_audit_get_debug_log', {
            lines: $('#wsa-debug-lines').val(),
            filter: $('#wsa-debug-filter').val(),
            severity: $('#wsa-debug-severity').val()
        })
        .done(function(res) {
            if (res.success) {
                if (res.data.size) $('#wsa-debug-size').text('Dimensiune: ' + res.data.size);
                if (res.data.entries && res.data.entries.length) {
                    var html = res.data.entries.map(function(e) {
                        return '<div class="wsa-log-' + (e.severity || 'info') + '">' + escapeHtml(e.raw) + '</div>';
                    }).join('');
                    $out.html(html);
                } else {
                    $out.text(res.data.exists ? 'Niciun rezultat cu filtrele actuale.' : 'debug.log nu există. Activează WP_DEBUG_LOG în wp-config.php');
                }
            } else {
                $out.text('Eroare: ' + (res.data || ''));
            }
        })
        .fail(function() { $out.text('Eroare de rețea.'); });
    }

    $('#wsa-debug-refresh').on('click', loadDebugLog);
    $('#wsa-debug-clear').on('click', function() {
        if (!confirm('Golești debug.log?')) return;
        post('webgsm_audit_clear_debug_log').done(function(r) { if (r.success) loadDebugLog(); });
    });

    // --- CLEAR AUDIT LOGS (rezultate linkuri + ultima scanare) ---
    $('#wsa-clear-logs-btn').on('click', function() {
        if (!confirm('Ștergi toate rezultatele auditului (linkuri)? Poți rula din nou scanarea.')) return;
        var $btn = $(this).prop('disabled', true);
        var $status = $('#wsa-full-scan-status');
        post('webgsm_audit_clear_logs')
            .done(function(r) {
                if (r.success) {
                    updateOverview(0, 0);
                    renderLinkTable([]);
                    $('#wsa-last-scan').text('Niciodată');
                    $status.text(r.data && r.data.message ? r.data.message : 'Rezultatele au fost șterse.').css('color', '#00a32a');
                } else {
                    $status.text('Eroare la ștergere.').css('color', '#d63638');
                }
            })
            .fail(function() {
                $status.text('Eroare de rețea.').css('color', '#d63638');
            })
            .always(function() { $btn.prop('disabled', false); });
    });

    // --- GSC IMPORT ---
    $('#wsa-gsc-import').on('click', function() {
        var json = $('#wsa-gsc-json').val().trim();
        if (!json) { alert('Lipește JSON-ul din GSC.'); return; }
        var $btn = $(this).prop('disabled', true);
        post('webgsm_audit_import_gsc', { gsc_json: json })
            .done(function(res) {
                if (res.success) {
                    alert('Import reușit: ' + (res.data.pages || 0) + ' pagini, ' + (res.data.issues || 0) + ' probleme.');
                    location.reload();
                } else {
                    alert('Eroare: ' + (res.data || ''));
                }
            })
            .fail(function() { alert('Eroare de rețea.'); })
            .always(function() { $btn.prop('disabled', false); });
    });

    // --- FULL SCAN ---
    $('#wsa-full-scan-btn').on('click', function() {
        var $btn = $(this).prop('disabled', true);
        var $status = $('#wsa-full-scan-status');
        var $summary = $('#wsa-full-scan-summary').hide();
        var results = {};
        var done = 0;
        var total = 6;

        function tick() {
            done++;
            $status.html(spinner() + ' Progres: ' + done + '/' + total + ' module...');
            if (done >= total) {
                $btn.prop('disabled', false);
                $status.text('Scanare completă finalizată!').css('color', '#00a32a');
                renderFullSummary(results);
            }
        }

        $status.html(spinner() + ' Se rulează ' + total + ' module...').css('color', '#646970');

        post('webgsm_audit_scan_links', {}, 300000).done(function(r) {
            if (r.success) { results.links = r.data; updateOverview(r.data.broken, r.data.total - r.data.broken); renderLinkTable(r.data.results); }
        }).always(tick);

        post('webgsm_audit_security_scan').done(function(r) {
            if (r.success) { results.security = r.data; renderIssues('#wsa-security-results', r.data.issues); }
        }).always(tick);

        post('webgsm_audit_performance_scan').done(function(r) {
            if (r.success) { results.performance = r.data; renderIssues('#wsa-perf-results', r.data.issues); }
        }).always(tick);

        post('webgsm_audit_seo_scan').done(function(r) {
            if (r.success) { results.seo = r.data; renderIssues('#wsa-seo-results', r.data.issues, true); }
        }).always(tick);

        post('webgsm_audit_robots_sitemap').done(function(r) {
            if (r.success) { results.robots = r.data; renderRobotsSitemap(r.data); }
        }).always(tick);

        post('webgsm_audit_conflict_scan').done(function(r) {
            if (r.success) { results.conflicts = r.data; renderIssues('#wsa-conflict-results', r.data.issues); }
        }).always(tick);
    });

    function renderFullSummary(r) {
        var $el = $('#wsa-full-scan-summary').show();
        var html = '<div class="wsa-cards" style="margin-top:20px;">';
        var lb = r.links ? r.links.broken : '?';
        var lt = r.links ? r.links.total : '?';
        html += card('Linkuri', lb + ' rupte / ' + lt, lb > 0 ? 'broken' : 'ok');
        html += card('Securitate', (r.security ? r.security.count : '?') + ' probleme', r.security && r.security.count > 0 ? 'broken' : 'ok');
        html += card('Performanță', (r.performance ? r.performance.count : '?') + ' probleme', r.performance && r.performance.count > 0 ? 'broken' : 'ok');
        html += card('SEO', (r.seo ? r.seo.count : '?') + ' probleme', r.seo && r.seo.count > 0 ? 'broken' : 'ok');
        var ri = r.robots ? (r.robots.robots.issues || []).length + (r.robots.sitemap.issues || []).length : '?';
        html += card('Robots/Sitemap', ri + ' probleme', ri > 0 ? 'broken' : 'ok');
        html += card('Conflicte', (r.conflicts ? r.conflicts.count : '?') + ' detectate', r.conflicts && r.conflicts.count > 0 ? 'broken' : 'ok');
        html += '</div>';
        $el.html(html);
    }

    function card(label, value, type) {
        return '<div class="wsa-card wsa-card--' + type + '"><span class="wsa-card-label">' + escapeHtml(label) + '</span><span class="wsa-card-value wsa-card-value--small">' + escapeHtml(String(value)) + '</span></div>';
    }

})(jQuery);
