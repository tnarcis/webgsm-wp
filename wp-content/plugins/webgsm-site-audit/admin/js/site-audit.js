(function($) {
    'use strict';

    var ajax = webgsmSiteAudit.ajaxurl;
    var nonce = webgsmSiteAudit.nonce;

    function post(action, extra) {
        var data = $.extend({ action: action, nonce: nonce }, extra || {});
        return $.post(ajax, data);
    }

    function setStatus(msg, isError, $target) {
        ($target || $('#wsa-status')).text(msg).css('color', isError ? '#d63638' : '#646970');
    }

    function escapeHtml(s) {
        var div = document.createElement('div');
        div.textContent = s || '';
        return div.innerHTML;
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
        setStatus('Se scanează linkurile...');
        post('webgsm_audit_scan_links')
            .done(function(res) {
                if (res.success) {
                    setStatus('Scan finalizat: ' + res.data.total + ' linkuri, ' + res.data.broken + ' rupte.');
                    updateOverview(res.data.broken, res.data.total - res.data.broken);
                    renderLinkTable(res.data.results);
                } else {
                    setStatus('Eroare: ' + (res.data || 'necunoscută'), true);
                }
            })
            .fail(function() { setStatus('Eroare de rețea.', true); })
            .always(function() { $btn.prop('disabled', false); });
    });

    function renderLinkTable(results) {
        var $tbody = $('#wsa-results-table tbody').empty();
        if (!results || !results.length) {
            $tbody.append('<tr><td colspan="3">Niciun rezultat.</td></tr>');
            return;
        }
        results.forEach(function(r) {
            var statusText = r.http_code ? 'HTTP ' + r.http_code : (r.error || r.status || '?');
            $tbody.append(
                '<tr data-status="' + (r.status || '') + '" data-source="' + (r.source || '') + '">' +
                '<td><a href="' + escapeHtml(r.url || '#') + '" target="_blank" rel="noopener">' + escapeHtml(r.url) + '</a></td>' +
                '<td><span class="wsa-badge wsa-badge--' + (r.status || 'unknown') + '">' + escapeHtml(statusText) + '</span></td>' +
                '<td>' + escapeHtml(r.source || '') + ' – ' + escapeHtml(r.source_title || '') + '</td></tr>'
            );
        });
    }

    // --- ROBOTS & SITEMAP ---
    $('#wsa-robots-scan').on('click', function() {
        var $btn = $(this).prop('disabled', true);
        setStatus('Se verifică robots.txt și sitemap...', false, $('#wsa-robots-status'));
        post('webgsm_audit_robots_sitemap')
            .done(function(res) {
                if (res.success) {
                    renderRobotsSitemap(res.data);
                    var totalIssues = (res.data.robots.issues || []).length + (res.data.sitemap.issues || []).length;
                    setStatus(totalIssues + ' probleme găsite.', false, $('#wsa-robots-status'));
                } else {
                    setStatus('Eroare', true, $('#wsa-robots-status'));
                }
            })
            .fail(function() { setStatus('Eroare de rețea.', true, $('#wsa-robots-status')); })
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
        if (data.robots.issues && data.robots.issues.length) {
            data.robots.issues.forEach(function(i) {
                html += renderIssueHtml(i);
            });
        }
        if (data.robots.info && data.robots.info.length) {
            data.robots.info.forEach(function(info) {
                html += '<div class="wsa-info">' + escapeHtml(info) + '</div>';
            });
        }
        if (data.robots.content) {
            html += '<details style="margin-top:12px;"><summary>Conținut robots.txt</summary>';
            html += '<pre class="wsa-robots-pre">' + escapeHtml(data.robots.content) + '</pre></details>';
        }
        html += '</div>';

        html += '<div class="wsa-section"><h3>Sitemap XML</h3>';
        if (data.sitemap.found) {
            html += '<div class="wsa-badge wsa-badge--ok" style="margin-bottom:12px;">Găsit: ' + escapeHtml(data.sitemap.url) + '</div>';
            if (data.sitemap.type) html += '<p>Tip: ' + escapeHtml(data.sitemap.type) + ' | URL-uri: ' + (data.sitemap.urls_count || 0) + '</p>';
        } else {
            html += '<div class="wsa-badge wsa-badge--broken" style="margin-bottom:12px;">Nu a fost găsit</div>';
        }
        if (data.sitemap.issues && data.sitemap.issues.length) {
            data.sitemap.issues.forEach(function(i) {
                html += renderIssueHtml(i);
            });
        }
        if (data.sitemap.info && data.sitemap.info.length) {
            data.sitemap.info.forEach(function(info) {
                html += '<div class="wsa-info">' + escapeHtml(info) + '</div>';
            });
        }
        html += '</div>';

        $el.html(html);
    }

    // --- CONFLICT DETECTOR ---
    $('#wsa-conflict-scan').on('click', function() {
        var $btn = $(this).prop('disabled', true);
        setStatus('Se detectează conflicte...', false, $('#wsa-conflict-status'));
        post('webgsm_audit_conflict_scan')
            .done(function(res) {
                if (res.success) {
                    setStatus(res.data.count + ' probleme găsite.', false, $('#wsa-conflict-status'));
                    renderIssues('#wsa-conflict-results', res.data.issues);
                } else {
                    setStatus('Eroare', true, $('#wsa-conflict-status'));
                }
            })
            .fail(function() { setStatus('Eroare de rețea.', true, $('#wsa-conflict-status')); })
            .always(function() { $btn.prop('disabled', false); });
    });

    // --- SECURITY ---
    $('#wsa-security-scan').on('click', function() {
        var $btn = $(this).prop('disabled', true);
        setStatus('Se scanează securitatea...', false, $('#wsa-security-status'));
        post('webgsm_audit_security_scan')
            .done(function(res) {
                if (res.success) {
                    setStatus(res.data.count + ' probleme găsite.', false, $('#wsa-security-status'));
                    renderIssues('#wsa-security-results', res.data.issues);
                } else {
                    setStatus('Eroare', true, $('#wsa-security-status'));
                }
            })
            .always(function() { $btn.prop('disabled', false); });
    });

    // --- PERFORMANCE ---
    $('#wsa-perf-scan').on('click', function() {
        var $btn = $(this).prop('disabled', true);
        setStatus('Se scanează performanța...', false, $('#wsa-perf-status'));
        post('webgsm_audit_performance_scan')
            .done(function(res) {
                if (res.success) {
                    setStatus(res.data.count + ' probleme găsite.', false, $('#wsa-perf-status'));
                    renderIssues('#wsa-perf-results', res.data.issues);
                } else {
                    setStatus('Eroare', true, $('#wsa-perf-status'));
                }
            })
            .always(function() { $btn.prop('disabled', false); });
    });

    // --- SEO ---
    $('#wsa-seo-scan').on('click', function() {
        var $btn = $(this).prop('disabled', true);
        setStatus('Se scanează SEO...', false, $('#wsa-seo-status'));
        post('webgsm_audit_seo_scan')
            .done(function(res) {
                if (res.success) {
                    setStatus(res.data.count + ' probleme găsite.', false, $('#wsa-seo-status'));
                    renderIssues('#wsa-seo-results', res.data.issues, true);
                } else {
                    setStatus('Eroare', true, $('#wsa-seo-status'));
                }
            })
            .always(function() { $btn.prop('disabled', false); });
    });

    // --- RENDER ISSUES (universal) ---
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
            $el.html('<div class="wsa-no-issues"><span class="dashicons dashicons-yes-alt"></span> Nicio problemă găsită. Bine!</div>');
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
        $out.text('Se încarcă...');
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
                        var cls = 'wsa-log-' + (e.severity || 'info');
                        return '<div class="' + cls + '">' + escapeHtml(e.raw) + '</div>';
                    }).join('');
                    $out.html(html);
                } else {
                    $out.text(res.data.exists ? 'Niciun rezultat cu filtrele actuale.' : 'debug.log nu există. Activează WP_DEBUG_LOG în wp-config.php');
                }
            } else {
                $out.text('Eroare: ' + (res.data || 'necunoscută'));
            }
        })
        .fail(function() { $out.text('Eroare de rețea.'); });
    }

    $('#wsa-debug-refresh').on('click', loadDebugLog);
    $('#wsa-debug-clear').on('click', function() {
        if (!confirm('Golești debug.log?')) return;
        post('webgsm_audit_clear_debug_log').done(function(res) {
            if (res.success) loadDebugLog();
        });
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
                    alert('Eroare: ' + (res.data || 'necunoscută'));
                }
            })
            .fail(function() { alert('Eroare de rețea.'); })
            .always(function() { $btn.prop('disabled', false); });
    });

    // --- FULL SCAN (overview) ---
    $('#wsa-full-scan-btn').on('click', function() {
        var $btn = $(this).prop('disabled', true);
        var $status = $('#wsa-full-scan-status');
        var $summary = $('#wsa-full-scan-summary').hide();
        var results = { links: null, security: null, performance: null, seo: null, robots: null, conflicts: null };
        var done = 0;
        var total = 6;

        function checkDone() {
            done++;
            $status.text('Progres: ' + done + '/' + total + ' module...');
            if (done >= total) {
                $btn.prop('disabled', false);
                $status.text('Scanare completă finalizată!').css('color', '#00a32a');
                renderFullSummary(results);
            }
        }

        $status.text('Se rulează 6 module...').css('color', '#646970');

        post('webgsm_audit_scan_links').done(function(r) {
            if (r.success) {
                results.links = r.data;
                updateOverview(r.data.broken, r.data.total - r.data.broken);
                renderLinkTable(r.data.results);
            }
        }).always(checkDone);

        post('webgsm_audit_security_scan').done(function(r) {
            if (r.success) { results.security = r.data; renderIssues('#wsa-security-results', r.data.issues); }
        }).always(checkDone);

        post('webgsm_audit_performance_scan').done(function(r) {
            if (r.success) { results.performance = r.data; renderIssues('#wsa-perf-results', r.data.issues); }
        }).always(checkDone);

        post('webgsm_audit_seo_scan').done(function(r) {
            if (r.success) { results.seo = r.data; renderIssues('#wsa-seo-results', r.data.issues, true); }
        }).always(checkDone);

        post('webgsm_audit_robots_sitemap').done(function(r) {
            if (r.success) { results.robots = r.data; renderRobotsSitemap(r.data); }
        }).always(checkDone);

        post('webgsm_audit_conflict_scan').done(function(r) {
            if (r.success) { results.conflicts = r.data; renderIssues('#wsa-conflict-results', r.data.issues); }
        }).always(checkDone);
    });

    function renderFullSummary(r) {
        var $el = $('#wsa-full-scan-summary').show();
        var html = '<div class="wsa-cards" style="margin-top:20px;">';

        var linksBroken = r.links ? r.links.broken : '?';
        var linksTotal = r.links ? r.links.total : '?';
        html += card('Linkuri', linksBroken + ' rupte / ' + linksTotal, linksBroken > 0 ? 'broken' : 'ok');

        var secCount = r.security ? r.security.count : '?';
        html += card('Securitate', secCount + ' probleme', secCount > 0 ? 'broken' : 'ok');

        var perfCount = r.performance ? r.performance.count : '?';
        html += card('Performanță', perfCount + ' probleme', perfCount > 0 ? 'broken' : 'ok');

        var seoCount = r.seo ? r.seo.count : '?';
        html += card('SEO', seoCount + ' probleme', seoCount > 0 ? 'broken' : 'ok');

        var robotsIssues = r.robots ? (r.robots.robots.issues || []).length + (r.robots.sitemap.issues || []).length : '?';
        html += card('Robots/Sitemap', robotsIssues + ' probleme', robotsIssues > 0 ? 'broken' : 'ok');

        var conflictCount = r.conflicts ? r.conflicts.count : '?';
        html += card('Conflicte', conflictCount + ' detectate', conflictCount > 0 ? 'broken' : 'ok');

        html += '</div>';
        $el.html(html);
    }

    function card(label, value, type) {
        return '<div class="wsa-card wsa-card--' + type + '"><span class="wsa-card-label">' + escapeHtml(label) + '</span><span class="wsa-card-value wsa-card-value--small">' + escapeHtml(String(value)) + '</span></div>';
    }

})(jQuery);
