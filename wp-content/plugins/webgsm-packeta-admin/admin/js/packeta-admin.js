(function ($) {
    'use strict';

    function msg(key) {
        var o = (typeof webgsmPacketaAdmin !== 'undefined' && webgsmPacketaAdmin.i18n) ? webgsmPacketaAdmin.i18n : {};
        return o[key] || key;
    }

    function getWeight() {
        var el = document.getElementById('weight');
        if (!el) return undefined;
        var w = parseFloat(String(el.value).replace(',', '.'));
        return isNaN(w) || w <= 0 ? undefined : w;
    }

    function buildOptions() {
        var opts = {
            country: 'ro',
            language: 'ro',
            webUrl: window.location.origin || undefined,
            appIdentity: 'webgsm-packeta-admin-1.4.4'
        };
        var w = getWeight();
        if (w !== undefined) {
            opts.weight = w;
        }

        var sel = document.getElementById('webgsm_packeta_carrier_filter');
        if (sel && sel.value) {
            try {
                var raw = sel.options[sel.selectedIndex].getAttribute('data-vendor');
                if (raw) {
                    opts.vendors = [JSON.parse(raw)];
                }
            } catch (e) {
                /* ignore */
            }
        }

        return opts;
    }

    function isPickupFlow() {
        var h = document.getElementById('awb_flow');
        return h && h.value === 'pickup';
    }

    function clearPoint() {
        $('#point_pickup_type').val('');
        $('#address_id').val('');
        $('#carrier_pickup_point').val('');
        $('#delivery_mode').val('pudo');
        $('#webgsm_packeta_point_summary').text(msg('noPointYet'));
        $('#webgsm_packeta_point_status').removeClass('is-done');
    }

    function applyPoint(point) {
        var $type = $('#point_pickup_type');
        var $mode = $('#delivery_mode');
        var $addr = $('#address_id');
        var $cpp = $('#carrier_pickup_point');

        if (!point) {
            $type.val('');
            $('#webgsm_packeta_point_summary').text(msg('cancelled'));
            $('#webgsm_packeta_point_status').removeClass('is-done');
            return;
        }

        var external =
            point.pickupPointType === 'external' ||
            (point.carrierPickupPointId && String(point.carrierPickupPointId).length > 0);

        if (external) {
            $type.val('external');
            $mode.val('carrier_pudo');
            $addr.val(point.carrierId != null ? String(point.carrierId) : '');
            $cpp.val(point.carrierPickupPointId != null ? String(point.carrierPickupPointId) : '');
            $('#carrier_cpp_wrap').show();
        } else {
            $type.val('internal');
            $mode.val('pudo');
            $addr.val(point.id != null ? String(point.id) : '');
            $cpp.val('');
            $('#carrier_cpp_wrap').hide();
        }

        var parts = [];
        if (point.name) parts.push(point.name);
        if (point.street) parts.push(point.street);
        if (point.city) parts.push(point.city);
        if (point.zip) parts.push(point.zip);
        $('#webgsm_packeta_point_summary').text(parts.join(', ') || msg('selectPoint'));
        $('#webgsm_packeta_point_status').addClass('is-done');
    }

    function openMap() {
        if (!isPickupFlow()) {
            return;
        }
        var cfg = typeof webgsmPacketaAdmin !== 'undefined' ? webgsmPacketaAdmin : {};
        if (!cfg.widgetApiKey) {
            window.alert(msg('needKey'));
            return;
        }
        if (typeof window.Packeta === 'undefined' || !window.Packeta.Widget || typeof window.Packeta.Widget.pick !== 'function') {
            window.alert(msg('needPacketaLib'));
            return;
        }

        window.Packeta.Widget.pick(cfg.widgetApiKey, function (point) {
            applyPoint(point);
        }, buildOptions());
    }

    function syncFlowFromUi() {
        var home = document.getElementById('awb_flow_home');
        var pickup = document.getElementById('awb_flow_pickup');
        var mapSection = document.getElementById('webgsm_packeta_map_section');
        var homeIntro = document.getElementById('webgsm_packeta_home_intro');
        var homeFields = document.getElementById('packeta-home-fields');
        var awbFlow = document.getElementById('awb_flow');
        var dm = document.getElementById('delivery_mode');
        var addrHelp = document.getElementById('address_id_help');
        var formTitle = document.getElementById('form-step-title');
        var homeCarrierSection = document.getElementById('webgsm_packeta_home_carrier_section');
        var homeCarrierSelect = document.getElementById('webgsm_packeta_home_carrier_select');

        if (!awbFlow || !dm) return;

        var useHome = home && home.checked;

        if (useHome) {
            awbFlow.value = 'home';
            dm.value = 'home';
            if (mapSection) mapSection.setAttribute('hidden', 'hidden');
            if (homeCarrierSection) homeCarrierSection.removeAttribute('hidden');
            if (homeIntro) homeIntro.removeAttribute('hidden');
            if (homeFields) homeFields.style.display = 'grid';
            clearPoint();
            $('#carrier_cpp_wrap').hide();
            $('#street, #city').prop('required', true);
            if (addrHelp) addrHelp.textContent = msg('addressIdHomeHelp');
            if (formTitle) formTitle.textContent = msg('formTitleHome');
        } else {
            awbFlow.value = 'pickup';
            if (dm.value === 'home' || !dm.value) {
                dm.value = 'pudo';
            }
            if (mapSection) mapSection.removeAttribute('hidden');
            if (homeCarrierSection) homeCarrierSection.setAttribute('hidden', 'hidden');
            if (homeCarrierSelect) homeCarrierSelect.value = '';
            if (homeIntro) homeIntro.setAttribute('hidden', 'hidden');
            if (homeFields) homeFields.style.display = 'none';
            $('#street, #city').prop('required', false);
            if (addrHelp) addrHelp.textContent = msg('addressIdPickupHelp');
            if (formTitle) formTitle.textContent = msg('formTitlePickup');
        }
    }

    function validateBeforeSubmit(e) {
        var rawVal = String($('#value').val() || '').replace(',', '.').trim();
        var parcelVal = parseFloat(rawVal);
        if (!parcelVal || parcelVal <= 0 || isNaN(parcelVal)) {
            e.preventDefault();
            window.alert(msg('parcelValueRequired'));
            return false;
        }

        var awbFlow = document.getElementById('awb_flow');
        if (!awbFlow) {
            return true;
        }
        if (awbFlow.value === 'home') {
            var st = ($('#street').val() || '').trim();
            var city = ($('#city').val() || '').trim();
            if (!st || !city) {
                e.preventDefault();
                window.alert(msg('addressFieldsRequired'));
                return false;
            }
            var hid = parseInt($('#address_id').val(), 10);
            if (!hid || hid < 1) {
                e.preventDefault();
                window.alert(msg('missingHomeCarrier'));
                return false;
            }
            return true;
        }
        if (awbFlow.value !== 'pickup') {
            return true;
        }
        var aid = parseInt($('#address_id').val(), 10);
        var pt = $('#point_pickup_type').val();
        if (!aid || aid < 1 || !pt) {
            e.preventDefault();
            window.alert(msg('mustSelectPoint'));
            return false;
        }
        if (pt === 'external') {
            var cpp = ($('#carrier_pickup_point').val() || '').trim();
            if (!cpp) {
                e.preventDefault();
                window.alert(msg('mustSelectPoint'));
                return false;
            }
        }
        return true;
    }

    $(function () {
        $(document).on('change', '.awb-flow-radio', function () {
            syncFlowFromUi();
        });

        $(document).on('change', '#webgsm_packeta_home_carrier_select', function () {
            var v = String($(this).val() || '').trim();
            if (v !== '') {
                $('#address_id').val(v);
            } else if (document.getElementById('awb_flow') && document.getElementById('awb_flow').value === 'home') {
                $('#address_id').val('');
            }
        });

        $(document).on('click', '#webgsm_packeta_open_map', function (e) {
            e.preventDefault();
            openMap();
        });

        $(document).on('click', '#webgsm_packeta_preset_acte', function (e) {
            e.preventDefault();
            $('#cod').val('0');
            $('#value').val('1');
            $('#weight').val('0,1');
            var $note = $('#note');
            if ($note.length && !String($note.val() || '').trim()) {
                $note.val('Acte');
            }
        });

        $(document).on('click', '#webgsm_packeta_preset_no_cod', function (e) {
            e.preventDefault();
            $('#cod').val('0');
        });

        $('#webgsm_packeta_awb_form').on('submit', validateBeforeSubmit);

        syncFlowFromUi();
        if (document.getElementById('awb_flow_pickup') && document.getElementById('awb_flow_pickup').checked) {
            $('#carrier_cpp_wrap').hide();
        }
    });
})(jQuery);
