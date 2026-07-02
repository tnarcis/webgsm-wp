(function ($) {
    'use strict';

    var cfg = window.webgsmPacketaAwbList || {};
    var pollMs = parseInt(cfg.pollInterval || 60000, 10);
    var timer = null;

    function setHint(text, isError) {
        var $h = $('#webgsm-packeta-sync-hint');
        $h.text(text || '');
        $h.toggleClass('is-error', !!isError);
    }

    function updateRow($row, data) {
        if (!data || !$row.length) {
            return;
        }
        var step = parseInt(data.progress_step, 10) || 0;
        var percent = parseInt(data.progress_percent, 10) || 0;
        var isProblem = !!data.is_problem;
        var isFinal = !!data.is_final;

        $row.toggleClass('is-problem', isProblem);
        $row.toggleClass('is-delivered', isFinal && !isProblem);
        $row.attr('data-final', isFinal ? '1' : '0');

        var $track = $row.find('.webgsm-packeta-track').first();
        $track.toggleClass('is-problem', isProblem);
        $track.find('.webgsm-packeta-track-fill').css('width', percent + '%');
        $track.find('.webgsm-packeta-track-step').each(function (i) {
            var $s = $(this);
            $s.removeClass('is-done is-active');
            if (i < step) {
                $s.addClass('is-done');
            } else if (i === step && !isFinal) {
                $s.addClass('is-active');
            } else if (i === step && isFinal && !isProblem) {
                $s.addClass('is-done is-active');
            }
        });

        if (data.status_label) {
            $track.find('.webgsm-packeta-status-label').text(data.status_label);
        }
        if (data.updated_human) {
            $track.find('.webgsm-packeta-status-updated').text('· ' + data.updated_human);
        }
        if (data.courier_number) {
            var $courier = $row.find('.webgsm-packeta-courier-inline');
            if (!$courier.length) {
                $row.find('td').eq(1).append(
                    '<br><small class="webgsm-packeta-courier-inline">AWB curier: <code>' +
                        $('<span>').text(data.courier_number).html() +
                        '</code></small>'
                );
            } else {
                $courier.find('code').text(data.courier_number);
            }
        }
    }

    function refreshOne(packetId, silent) {
        return $.post(cfg.ajaxUrl, {
            action: 'webgsm_packeta_refresh_awb_status',
            nonce: cfg.nonce,
            packet_id: packetId
        }).done(function (res) {
            if (!res || !res.success) {
                if (!silent) {
                    setHint((res && res.data && res.data.message) || 'Eroare la actualizare.', true);
                }
                return;
            }
            var $row = $('.webgsm-packeta-awb-row[data-packet-id="' + packetId + '"]');
            updateRow($row, res.data);
            if (!silent) {
                setHint('Status actualizat pentru ' + packetId + '.', false);
            }
        }).fail(function () {
            if (!silent) {
                setHint('Eroare de rețea la actualizare status.', true);
            }
        });
    }

    function refreshActiveSilent() {
        var ids = [];
        $('.webgsm-packeta-awb-row[data-final="0"]').each(function () {
            ids.push($(this).data('packet-id'));
        });
        if (!ids.length) {
            return;
        }
        setHint('Sincronizare automată…', false);
        var chain = $.Deferred().resolve();
        ids.forEach(function (id) {
            chain = chain.then(function () {
                return refreshOne(id, true);
            });
        });
        chain.done(function () {
            setHint('Ultima sincronizare: ' + new Date().toLocaleTimeString('ro-RO'), false);
        });
    }

    $(function () {
        $('#webgsm-packeta-refresh-all').on('click', function () {
            var $btn = $(this);
            $btn.prop('disabled', true);
            setHint('Actualizare în curs…', false);
            $.post(cfg.ajaxUrl, {
                action: 'webgsm_packeta_refresh_all_awb_statuses',
                nonce: cfg.nonce
            }).done(function (res) {
                if (res && res.success && res.data && res.data.rows) {
                    res.data.rows.forEach(function (row) {
                        updateRow($('.webgsm-packeta-awb-row[data-packet-id="' + row.packet_id + '"]'), row);
                    });
                    setHint('Actualizate ' + (res.data.updated || 0) + ' AWB-uri.', false);
                } else {
                    setHint((res && res.data && res.data.message) || 'Eroare.', true);
                }
            }).fail(function () {
                setHint('Eroare de rețea.', true);
            }).always(function () {
                $btn.prop('disabled', false);
            });
        });

        $(document).on('click', '.webgsm-packeta-refresh-one', function () {
            var id = $(this).data('packet-id');
            refreshOne(id, false);
        });

        if (pollMs > 0 && $('.webgsm-packeta-awb-row[data-final="0"]').length) {
            timer = setInterval(refreshActiveSilent, pollMs);
        }
    });
})(jQuery);
