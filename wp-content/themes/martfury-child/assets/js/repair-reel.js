(function () {
    'use strict';

    var cfg = window.webgsmReel || {};
    var root = document.getElementById('webgsm-reel');
    if (!root) return;

    var state = {
        screen: cfg.screen || 1,
        brand: cfg.brand || '',
        model: cfg.model || '',
        modelName: cfg.modelName || '',
        problem: cfg.problem || '',
        problems: cfg.problems && cfg.problems.length ? cfg.problems.slice() : [],
        sku: cfg.sku || '',
        models: cfg.models || [],
        offer: cfg.offer || null,
        multiOffer: cfg.multiOffer || null,
        selections: {},
        quote: null,
        multiMode: !!(cfg.problems && cfg.problems.length),
        microsoldering: false,
        intake: {
            issues: [],
            notes: '',
            photoFront: null,
            photoBack: null,
            photoFrontUrl: '',
            photoBackUrl: ''
        }
    };

    var modelsEl = document.getElementById('rr-models');
    var modelsEmpty = document.getElementById('rr-models-empty');
    var searchEl = document.getElementById('rr-model-search');
    var menuEl = document.getElementById('rr-menu');
    var menuStack = document.getElementById('rr-menu-stack');
    var menuEmpty = document.getElementById('rr-menu-empty');
    var menuTitle = document.getElementById('rr-menu-title');
    var menuKicker = document.getElementById('rr-menu-kicker');
    var pickedModel = document.getElementById('rr-picked-model');
    var act = document.getElementById('rr-act');
    var actSummary = document.getElementById('rr-act-summary');
    var laborNote = document.getElementById('rr-labor-note');
    var intakeForm = document.getElementById('rr-intake-form');
    var intakeError = document.getElementById('rr-form-error');
    var intakeSubmit = document.getElementById('rr-intake-submit');
    var photoFront = document.getElementById('rr-photo-front');
    var photoBack = document.getElementById('rr-photo-back');
    var previewFront = document.getElementById('rr-preview-front');
    var previewBack = document.getElementById('rr-preview-back');
    var microWrap = document.getElementById('rr-micro-wrap');
    var microCb = document.getElementById('rr-microsoldering');
    var microLabel = document.getElementById('rr-micro-label');
    var montajNote = document.getElementById('rr-montaj-note');
    var problemsRoot = document.getElementById('rr-problems');
    var problemsContinue = document.getElementById('rr-problems-continue');

    function problemLabel(p) {
        var meta = cfg.problemMeta || {};
        if (meta[p] && meta[p].label) return meta[p].label;
        return p === 'baterie' ? 'Baterie' : (p === 'ecran' ? 'Ecran' : p);
    }

    function showScreen(n) {
        state.screen = n;
        root.setAttribute('data-step', String(n));
        root.querySelectorAll('.rr-screen').forEach(function (el) {
            var on = Number(el.getAttribute('data-screen')) === n;
            el.classList.toggle('is-active', on);
            if (on) el.removeAttribute('hidden');
            else el.setAttribute('hidden', '');
        });
        var steps = root.querySelector('.rr-steps');
        if (steps) {
            if (n === 1) {
                steps.hidden = true;
            } else {
                steps.hidden = false;
                steps.querySelectorAll('i').forEach(function (dot) {
                    var step = Number(dot.getAttribute('data-dot'));
                    dot.classList.toggle('is-on', step === n);
                });
            }
        }
        window.scrollTo(0, 0);
    }

    function post(action, data) {
        var body = new URLSearchParams();
        body.set('action', action);
        body.set('nonce', cfg.nonce || '');
        Object.keys(data || {}).forEach(function (k) {
            if (data[k] !== undefined && data[k] !== null) {
                if (Array.isArray(data[k])) {
                    data[k].forEach(function (v) { body.append(k + '[]', v); });
                } else {
                    body.set(k, data[k]);
                }
            }
        });
        return fetch(cfg.ajax, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: body.toString()
        }).then(function (res) { return res.json(); });
    }

    function postForm(action, formData) {
        formData.append('action', action);
        formData.append('nonce', cfg.nonce || '');
        return fetch(cfg.ajax, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData
        }).then(function (res) { return res.json(); });
    }

    function waUrl(text) {
        var phone = (cfg.whatsapp || '').replace(/\D+/g, '');
        var q = 'text=' + encodeURIComponent(text);
        if (phone) return 'https://wa.me/' + phone + '?' + q;
        return 'https://api.whatsapp.com/send?' + q;
    }

    function openWhatsApp(text) {
        window.location.href = waUrl(text);
    }

    function formatMoney(amount) {
        if (amount === null || amount === undefined || isNaN(amount)) return '';
        return Number(amount).toFixed(2).replace('.', ',') + ' lei';
    }

    function selectedLines() {
        return Object.keys(state.selections).map(function (k) {
            return state.selections[k];
        }).filter(Boolean);
    }

    function allSectionsPicked() {
        if (!state.problems.length) return false;
        return state.problems.every(function (p) {
            return !!state.selections[p];
        });
    }

    function hasMicroEligible() {
        return selectedLines().some(function (line) {
            return line.allow_microsoldering && line.microsoldering_price;
        });
    }

    function contactText(extra) {
        var bits = ['Bună! Vin de pe webgsm.ro.'];
        if (state.modelName) bits.push('Telefon: ' + state.modelName);
        if (state.problems.length) {
            bits.push('Reparații: ' + state.problems.map(problemLabel).join(', '));
        } else if (state.problem) {
            bits.push('Problemă: ' + problemLabel(state.problem));
        }
        if (extra) bits.push(extra);
        bits.push('Nu știu ce calitate mi se potrivește. Mă ajutați?');
        bits.push('Montaj Timișoara, Samuil Micu 27.');
        return bits.join('\n');
    }

    function repairText(intakeExtra) {
        var bits = ['Bună! Vreau să trimit telefonul pentru reparație.'];
        bits.push('Model: ' + (state.modelName || state.model));
        var lines = selectedLines();
        if (lines.length) {
            bits.push('');
            bits.push('Piese selectate:');
            lines.forEach(function (line) {
                var row = '- ' + problemLabel(line.problem) + ': ' + line.label;
                if (line.sku) row += ' (SKU ' + line.sku + ')';
                if (line.price_html) row += ' — piesă ' + line.price_html;
                bits.push(row);
            });
        } else if (state.selected) {
            bits.push('Reparație: ' + problemLabel(state.problem) + ' — ' + state.selected.label);
        }
        if (state.quote) {
            bits.push('');
            bits.push('Total piese: ' + (state.quote.parts_total_html || formatMoney(state.quote.parts_total)));
            if (state.quote.labor_total_html) {
                bits.push('Manoperă (MAX): ' + state.quote.labor_total_html + (state.quote.transport_included ? ' incl. transport' : ''));
            }
            if (state.microsoldering && state.quote.microsoldering_html) {
                bits.push('Microsoldering: ' + state.quote.microsoldering_html);
            }
            bits.push('TOTAL estimativ: ' + (state.quote.grand_total_html || formatMoney(state.quote.grand_total)));
        }
        if (intakeExtra && intakeExtra.issues && intakeExtra.issues.length) {
            bits.push('');
            bits.push('Probleme raportate:');
            intakeExtra.issues.forEach(function (item) {
                bits.push('- ' + (item.label || item));
            });
        }
        if (intakeExtra && intakeExtra.notes) {
            bits.push('');
            bits.push('Alte detalii: ' + intakeExtra.notes);
        }
        if (intakeExtra && (intakeExtra.photo_front || intakeExtra.photo_back)) {
            bits.push('');
            bits.push('Poze telefon:');
            if (intakeExtra.photo_front) bits.push('Față: ' + intakeExtra.photo_front);
            if (intakeExtra.photo_back) bits.push('Spate: ' + intakeExtra.photo_back);
        }
        bits.push('');
        bits.push('Accept termeni intake: Da');
        bits.push('Atelier: Timișoara, Samuil Micu 27.');
        return bits.join('\n');
    }

    function prefillIntakeIssues() {
        if (!intakeForm) return;
        intakeForm.querySelectorAll('input[name="issues[]"]').forEach(function (input) {
            input.checked = false;
        });
        var map = {
            ecran: 'ecran_fisurat',
            baterie: 'baterie_umflata',
            carcasa: 'ecran_fisurat'
        };
        state.problems.forEach(function (p) {
            var key = map[p];
            if (!key) return;
            var el = intakeForm.querySelector('input[value="' + key + '"]');
            if (el) el.checked = true;
        });
    }

    function bindPhotoPreview(input, previewEl) {
        if (!input || !previewEl) return;
        input.addEventListener('change', function () {
            previewEl.innerHTML = '';
            if (!input.files || !input.files[0]) {
                previewEl.hidden = true;
                return;
            }
            var img = document.createElement('img');
            img.alt = '';
            img.src = URL.createObjectURL(input.files[0]);
            previewEl.appendChild(img);
            previewEl.hidden = false;
        });
    }

    function setIntakeLoading(on) {
        if (!intakeSubmit) return;
        intakeSubmit.disabled = on;
        intakeSubmit.textContent = on ? 'Se încarcă pozele…' : 'Continuă pe WhatsApp';
    }

    function showIntakeError(msg) {
        if (!intakeError) return;
        if (!msg) {
            intakeError.hidden = true;
            intakeError.textContent = '';
            return;
        }
        intakeError.hidden = false;
        intakeError.textContent = msg;
    }

    function renderModels(filter) {
        var q = (filter || '').trim().toLowerCase();
        var list = (state.models || []).filter(function (m) {
            if (!q) return true;
            return (m.name + ' ' + m.slug).toLowerCase().indexOf(q) !== -1;
        });
        modelsEl.innerHTML = '';
        if (!list.length) {
            modelsEmpty.hidden = false;
            return;
        }
        modelsEmpty.hidden = true;
        list.forEach(function (m) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'rr-row';
            btn.setAttribute('role', 'listitem');
            btn.innerHTML = '<span class="rr-row-label"></span>';
            btn.querySelector('.rr-row-label').textContent = m.name;
            btn.addEventListener('click', function () {
                state.model = m.slug;
                state.modelName = m.name;
                state.problems = [];
                state.selections = {};
                state.quote = null;
                pickedModel.textContent = m.name;
                if (problemsRoot) {
                    problemsRoot.querySelectorAll('input[type="checkbox"]').forEach(function (cb) {
                        cb.checked = false;
                    });
                    if (problemsContinue) problemsContinue.disabled = true;
                }
                showScreen(4);
            });
            modelsEl.appendChild(btn);
        });
    }

    function loadModels(brand) {
        modelsEl.innerHTML = '<p class="rr-empty">Căutăm modelele…</p>';
        modelsEmpty.hidden = true;
        return post('webgsm_rr_models', { brand: brand }).then(function (json) {
            state.models = (json && json.success && json.data && json.data.models) ? json.data.models : [];
            renderModels(searchEl.value);
        }).catch(function () {
            state.models = [];
            renderModels('');
        });
    }

    function buildRow(line, onPick) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'rr-row';
        btn.setAttribute('role', 'listitem');
        btn.setAttribute('data-sku', line.sku || '');
        if (line.highlighted) btn.classList.add('is-highlight');
        btn.innerHTML =
            '<span class="rr-row-label"></span>' +
            '<span class="rr-row-price"></span>' +
            '<span class="rr-row-breakdown"></span>' +
            '<span class="rr-row-stock"></span>';
        btn.querySelector('.rr-row-label').textContent = line.label;
        var priceEl = btn.querySelector('.rr-row-price');
        priceEl.textContent = line.price_html || '';
        var breakdownEl = btn.querySelector('.rr-row-breakdown');
        if (line.labor_html) {
            breakdownEl.textContent = '+ manoperă ' + line.labor_html + (line.includes_transport ? ' (transport incl.)' : '');
        } else {
            breakdownEl.textContent = '';
        }
        btn.querySelector('.rr-row-stock').textContent = line.stock_label || '';
        btn.addEventListener('click', onPick);
        return btn;
    }

    function updateMicroUi() {
        state.microsoldering = false;
        if (microCb) microCb.checked = false;
        if (!microWrap) return;
        if (hasMicroEligible()) {
            microWrap.hidden = false;
            var line = selectedLines().filter(function (l) { return l.allow_microsoldering; })[0];
            if (microLabel && line) {
                microLabel.textContent = 'Mutare cip original (+' + formatMoney(line.microsoldering_price) + ')';
            }
        } else {
            microWrap.hidden = true;
        }
        if (montajNote) montajNote.hidden = !state.problems.length;
    }

    function renderActSummary() {
        actSummary.innerHTML = '';
        var title = document.createElement('span');
        title.className = 'rr-act-title';
        title.textContent = [state.modelName, state.problems.map(problemLabel).join(' + ')].filter(Boolean).join(' · ');
        actSummary.appendChild(title);

        if (state.quote) {
            var box = document.createElement('div');
            box.className = 'rr-quote-box';
            box.innerHTML =
                '<div class="rr-quote-row"><span>Total piese</span><span>' + (state.quote.parts_total_html || '') + '</span></div>' +
                (state.quote.labor_total_html ? '<div class="rr-quote-row"><span>Manoperă (MAX)</span><span>' + state.quote.labor_total_html + '</span></div>' : '') +
                (state.quote.microsoldering_html ? '<div class="rr-quote-row"><span>Microsoldering</span><span>' + state.quote.microsoldering_html + '</span></div>' : '') +
                '<div class="rr-quote-row is-grand"><span>Total estimativ</span><span>' + (state.quote.grand_total_html || '') + '</span></div>';
            actSummary.appendChild(box);
        }

        laborNote.textContent = state.quote && state.quote.labor_total
            ? 'Manoperă calculată cu regula MAX — o singură manoperă per comandă. Confirmăm pe WhatsApp.'
            : 'Manoperă de confirmat pe WhatsApp. Atelier: Timișoara, Samuil Micu 27.';
    }

    function refreshQuote() {
        var lines = selectedLines();
        if (!lines.length) {
            state.quote = null;
            act.hidden = true;
            return;
        }
        if (!allSectionsPicked()) {
            act.hidden = true;
            return;
        }
        var payload = lines.map(function (line) {
            return {
                id: line.id,
                price: line.price,
                allow_microsoldering: line.allow_microsoldering,
                microsoldering: state.microsoldering && line.allow_microsoldering
            };
        });
        var body = new URLSearchParams();
        body.set('action', 'webgsm_rr_quote');
        body.set('nonce', cfg.nonce || '');
        body.set('selections', JSON.stringify(payload));
        body.set('microsoldering', state.microsoldering ? '1' : '0');
        return fetch(cfg.ajax, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: body.toString()
        }).then(function (res) { return res.json(); }).then(function (json) {
            state.quote = (json && json.success) ? json.data : null;
            updateMicroUi();
            renderActSummary();
            act.hidden = !state.quote;
        });
    }

    function pickLine(problem, line, sectionEl) {
        state.selections[problem] = line;
        if (sectionEl) {
            sectionEl.querySelectorAll('.rr-row').forEach(function (row) {
                row.classList.toggle('is-on', row.getAttribute('data-sku') === String(line.sku || ''));
            });
            var picked = sectionEl.querySelector('.rr-menu-section-picked');
            if (picked) picked.textContent = line.label;
        }
        refreshQuote();
    }

    function renderMultiOffer() {
        var data = state.multiOffer || { sections: {} };
        var sections = data.sections || {};
        menuTitle.textContent = (state.modelName || 'Modelul tău') + ' — estimare';
        menuKicker.textContent = state.problems.length > 1 ? 'Mai multe piese' : 'Estimare';
        if (menuEl) menuEl.hidden = true;
        if (!menuStack) return;
        menuStack.innerHTML = '';

        var anyLines = false;
        state.problems.forEach(function (problem) {
            var sec = sections[problem] || { lines: [], label: problemLabel(problem) };
            var lines = sec.lines || [];
            if (lines.length) anyLines = true;

            var wrap = document.createElement('div');
            wrap.className = 'rr-menu-section';
            wrap.setAttribute('data-problem', problem);
            wrap.innerHTML =
                '<div class="rr-menu-section-head">' +
                '<h3 class="rr-menu-section-title">' + (sec.label || problemLabel(problem)) + '</h3>' +
                '<span class="rr-menu-section-picked"></span></div>' +
                '<div class="rr-menu" role="list"></div>';
            var list = wrap.querySelector('.rr-menu');
            if (!lines.length) {
                var empty = document.createElement('p');
                empty.className = 'rr-empty';
                empty.textContent = 'Nu avem oferte live — confirmăm pe WhatsApp.';
                list.appendChild(empty);
            } else {
                lines.forEach(function (line) {
                    line.problem = problem;
                    list.appendChild(buildRow(line, function () {
                        pickLine(problem, line, wrap);
                    }));
                });
            }
            menuStack.appendChild(wrap);

            if (state.selections[problem]) {
                pickLine(problem, state.selections[problem], wrap);
            } else if (lines.length === 1) {
                pickLine(problem, lines[0], wrap);
            }
        });

        menuEmpty.hidden = anyLines;
        act.hidden = !allSectionsPicked() || !state.quote;
    }

    function loadMultiOffer() {
        if (menuStack) menuStack.innerHTML = '<p class="rr-empty">Luăm prețurile din magazin…</p>';
        act.hidden = true;
        return post('webgsm_rr_multi_offer', {
            model: state.model,
            problems: state.problems,
            sku: state.sku || ''
        }).then(function (json) {
            state.multiOffer = (json && json.success) ? json.data : { sections: {} };
            if (state.multiOffer && state.multiOffer.model_name) {
                state.modelName = state.multiOffer.model_name;
            }
            state.multiMode = true;
            renderMultiOffer();
        }).catch(function () {
            state.multiOffer = { sections: {} };
            renderMultiOffer();
        });
    }

    if (microCb) {
        microCb.addEventListener('change', function () {
            state.microsoldering = microCb.checked;
            refreshQuote();
        });
    }

    if (problemsRoot) {
        problemsRoot.addEventListener('change', function () {
            var picked = [];
            problemsRoot.querySelectorAll('input[type="checkbox"]:checked').forEach(function (cb) {
                picked.push(cb.value);
            });
            if (problemsContinue) problemsContinue.disabled = !picked.length;
        });
    }

    if (problemsContinue) {
        problemsContinue.addEventListener('click', function () {
            var picked = [];
            problemsRoot.querySelectorAll('input[type="checkbox"]:checked').forEach(function (cb) {
                picked.push(cb.value);
            });
            if (!picked.length) return;
            state.problems = picked;
            state.problem = picked[0];
            state.selections = {};
            state.quote = null;
            showScreen(5);
            loadMultiOffer();
        });
    }

    var goBtn = root.querySelector('[data-go="2"]');
    if (goBtn) goBtn.addEventListener('click', function () { showScreen(2); });

    root.querySelectorAll('[data-back]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var current = state.screen;
            if (current <= 1) return;
            if (current === 6) { showScreen(5); return; }
            if (current === 5 && cfg.screen === 5 && state.problems.length <= 1) {
                showScreen(4);
                return;
            }
            showScreen(current - 1);
        });
    });

    document.getElementById('rr-brands').addEventListener('click', function (e) {
        var btn = e.target.closest('[data-brand]');
        if (!btn) return;
        state.brand = btn.getAttribute('data-brand');
        document.querySelectorAll('#rr-brands .rr-choice').forEach(function (el) {
            el.classList.toggle('is-on', el === btn);
        });
        showScreen(3);
        loadModels(state.brand);
    });

    searchEl.addEventListener('input', function () {
        renderModels(searchEl.value);
    });

    root.querySelector('[data-contact]').addEventListener('click', function () {
        openWhatsApp(contactText());
    });

    root.querySelector('[data-act="repair"]').addEventListener('click', function () {
        if (!allSectionsPicked()) {
            openWhatsApp(contactText());
            return;
        }
        prefillIntakeIssues();
        showIntakeError('');
        showScreen(6);
    });

    bindPhotoPreview(photoFront, previewFront);
    bindPhotoPreview(photoBack, previewBack);

    if (intakeForm) {
        intakeForm.addEventListener('submit', function (e) {
            e.preventDefault();
            showIntakeError('');
            if (!intakeForm.checkValidity()) {
                intakeForm.reportValidity();
                return;
            }
            if (!photoFront.files[0] || !photoBack.files[0]) {
                showIntakeError('Adaugă ambele poze — față și spate.');
                return;
            }
            var fd = new FormData(intakeForm);
            fd.set('model', state.model || '');
            fd.set('model_name', state.modelName || '');
            fd.set('problem', state.problems.join(',') || state.problem || '');
            fd.set('microsoldering', state.microsoldering ? '1' : '0');
            fd.set('selections', JSON.stringify(selectedLines()));
            if (state.selections.ecran) {
                fd.set('sku', state.selections.ecran.sku || '');
                fd.set('label', state.selections.ecran.label || '');
            }
            setIntakeLoading(true);
            postForm('webgsm_rr_intake', fd).then(function (json) {
                setIntakeLoading(false);
                if (!json || !json.success) {
                    showIntakeError((json && json.data && json.data.message) ? json.data.message : 'Nu am putut trimite formularul.');
                    return;
                }
                openWhatsApp(repairText(json.data));
            }).catch(function () {
                setIntakeLoading(false);
                showIntakeError('Eroare la încărcare. Verifică conexiunea.');
            });
        });
    }

    root.querySelector('[data-act="part"]').addEventListener('click', function () {
        var lines = selectedLines();
        if (lines.length === 1 && lines[0].url && lines[0].id) {
            var url = lines[0].url + (lines[0].url.indexOf('?') >= 0 ? '&' : '?') + 'add-to-cart=' + encodeURIComponent(lines[0].id);
            window.location.href = url;
            return;
        }
        window.location.href = cfg.shop || '/shop/';
    });

    if (state.screen === 5 && state.model && (state.problems.length || state.problem)) {
        if (!state.problems.length && state.problem) {
            state.problems = [state.problem];
        }
        if (state.multiOffer && state.multiOffer.sections) {
            renderMultiOffer();
            refreshQuote();
        } else {
            loadMultiOffer();
        }
        showScreen(5);
        if (state.modelName) pickedModel.textContent = state.modelName;
    } else {
        showScreen(1);
    }
})();
