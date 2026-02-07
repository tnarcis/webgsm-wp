/**
 * WebGSM Product Reviewer
 */
(function($) {
    'use strict';

    var products = [];
    var originalProducts = [];
    var currentEditIndex = -1;
    var newCategories = [];
    var newAttributes = {};
    var newTags = [];
    var config = window.webgsmReviewer || {};
    var currentImages = [];
    var currentImageIndex = 0;

    $(document).ready(function() {
        initUpload();
        initFilters();
        initModal();
        initExport();
    });

    function initUpload() {
        var $uploadArea = $('#upload-area');
        var $fileInput = $('#csv-file');

        $('#browse-file, #upload-area').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $fileInput.trigger('click');
        });
        $uploadArea.on('dragover', function(e) {
            e.preventDefault();
            $(this).addClass('dragover');
        });
        $uploadArea.on('dragleave', function() { $(this).removeClass('dragover'); });
        $uploadArea.on('drop', function(e) {
            e.preventDefault();
            $(this).removeClass('dragover');
            var files = e.originalEvent.dataTransfer.files;
            if (files.length > 0) handleFile(files[0]);
        });
        $fileInput.on('change', function() {
            if (this.files.length > 0) handleFile(this.files[0]);
        });
    }

    function handleFile(file) {
        if (!file.name.toLowerCase().endsWith('.csv')) {
            alert('Te rog selectează un fișier CSV.');
            return;
        }
        var reader = new FileReader();
        reader.onload = function(e) { parseCSV(e.target.result); };
        reader.readAsText(file, 'UTF-8');
    }

    function parseCSV(csvText) {
        if (csvText.charCodeAt(0) === 0xFEFF) csvText = csvText.slice(1);
        var lines = csvText.split(/\r?\n/);
        var headers = parseCSVLine(lines[0]);
        products = [];
        for (var i = 1; i < lines.length; i++) {
            if (lines[i].trim() === '') continue;
            var values = parseCSVLine(lines[i]);
            var product = {};
            headers.forEach(function(header, index) {
                product[header] = values[index] || '';
            });
            product._validation = validateProduct(product);
            product._index = i - 1;
            products.push(product);
        }
        originalProducts = JSON.parse(JSON.stringify(products));
        updateStats();
        renderProducts();
        showSections();
    }

    function parseCSVLine(line) {
        var result = [], current = '', inQuotes = false;
        for (var i = 0; i < line.length; i++) {
            var char = line[i];
            if (char === '"') {
                if (inQuotes && line[i + 1] === '"') { current += '"'; i++; }
                else inQuotes = !inQuotes;
            } else if (char === ',' && !inQuotes) {
                result.push(current.trim());
                current = '';
            } else current += char;
        }
        result.push(current.trim());
        return result;
    }

    function validateProduct(product) {
        var errors = [], warnings = [];
        var category = product['Categories'] || '';
        var parts = category.split('>').map(function(p) { return p.trim(); });
        var lastSlug = parts.length > 0 ? parts[parts.length - 1].toLowerCase().replace(/[^a-z0-9]+/g, '-') : '';

        if (config.invalidSlugs && config.invalidSlugs.indexOf(lastSlug) !== -1) {
            errors.push('Categorie invalidă: ' + lastSlug);
        }
        if (!category) errors.push('Categoria lipsește');

        var quality = product['Attribute 2 value(s)'] || '';
        if (!quality) warnings.push('Calitate nesetată');

        var seoTitle = product['meta:rank_math_title'] || '';
        var seoDesc = product['meta:rank_math_description'] || '';
        if (seoTitle.length > 60) warnings.push('SEO Title prea lung (' + seoTitle.length + '/60)');
        if (seoDesc.length > 160) warnings.push('SEO Description prea lungă (' + seoDesc.length + '/160)');

        var price = parseFloat(product['Regular price'] || 0);
        if (price <= 0) errors.push('Preț invalid');

        return {
            status: errors.length > 0 ? 'error' : (warnings.length > 0 ? 'warning' : 'ok'),
            errors: errors,
            warnings: warnings
        };
    }

    function updateStats() {
        var total = products.length;
        var ok = products.filter(function(p) { return p._validation.status === 'ok'; }).length;
        var warning = products.filter(function(p) { return p._validation.status === 'warning'; }).length;
        var error = products.filter(function(p) { return p._validation.status === 'error'; }).length;
        $('#stat-total').text(total);
        $('#stat-ok').text(ok);
        $('#stat-warning').text(warning);
        $('#stat-error').text(error);
    }

    function getSEOStatus(product) {
        var title = product['meta:rank_math_title'] || '';
        var desc = product['meta:rank_math_description'] || '';
        var keyword = product['meta:rank_math_focus_keyword'] || '';
        var score = 0;
        if (title && title.length >= 30 && title.length <= 60) score++;
        if (desc && desc.length >= 120 && desc.length <= 160) score++;
        if (keyword) score++;
        if (score === 3) return '<span class="seo-good">✅</span>';
        if (score >= 1) return '<span class="seo-ok">⚠️</span>';
        return '<span class="seo-bad">❌</span>';
    }

    function renderProducts(filter) {
        filter = filter || 'all';
        var $tbody = $('#products-tbody');
        $tbody.empty();
        var statusIcon = { 'ok': '✅', 'warning': '⚠️', 'error': '❌' };
        products.forEach(function(product, index) {
            if (filter !== 'all' && product._validation.status !== filter) return;
            var seoStatus = getSEOStatus(product);
            var imgUrl = (product['Images'] || '').split(',')[0].trim() || 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="50" height="50"><rect fill="%23ddd" width="50" height="50"/><text x="25" y="30" text-anchor="middle" fill="%23999" font-size="10">No img</text></svg>';
            var sku = product['SKU'] || '';
            var skuCell = sku ? sku : '<span class="missing">Lipsă</span>';
            var row = '<tr class="row-' + product._validation.status + '" data-index="' + index + '">' +
                '<td>' + (index + 1) + '</td>' +
                '<td><img src="' + imgUrl + '" alt="" class="thumb-img"></td>' +
                '<td class="sku-cell" title="' + (sku || '').replace(/"/g, '&quot;') + '">' + skuCell + '</td>' +
                '<td class="name-cell" title="' + (product['Name'] || '').replace(/"/g, '&quot;') + '">' + truncate(product['Name'] || '', 30) + '</td>' +
                '<td class="category-cell">' + formatCategory(product['Categories']) + '</td>' +
                '<td class="model-cell">' + truncate(product['Attribute 1 value(s)'] || '-', 15) + '</td>' +
                '<td>' + (product['Attribute 2 value(s)'] || '-') + '</td>' +
                '<td>' + truncate(product['Attribute 3 value(s)'] || '-', 10) + '</td>' +
                '<td class="price-cell">' + (product['Regular price'] || '-') + '</td>' +
                '<td>' + (product['Stock'] || '0') + '</td>' +
                '<td class="seo-cell">' + seoStatus + '</td>' +
                '<td class="status-' + product._validation.status + '">' + statusIcon[product._validation.status] + '</td>' +
                '<td><button type="button" class="btn-edit" title="Editează">✏️</button></td></tr>';
            var $row = $(row);
            $row.find('.btn-edit').on('click', function(e) { e.stopPropagation(); openEditor(index); });
            $row.on('dblclick', function() { openEditor(index); });
            $tbody.append($row);
        });
    }

    function formatCategory(category) {
        if (!category) return '<span class="category-invalid">Lipsește</span>';
        var parts = category.split('>').map(function(p) { return p.trim(); });
        var lastPart = parts[parts.length - 1];
        var slug = lastPart.toLowerCase().replace(/[^a-z0-9]+/g, '-');
        if (config.invalidSlugs && config.invalidSlugs.indexOf(slug) !== -1) {
            return '<span class="category-invalid">' + lastPart + '</span>';
        }
        return parts.length > 2 ? '...' + parts.slice(-2).join(' > ') : category;
    }

    function initFilters() {
        $('.filter-btn').on('click', function() {
            $('.filter-btn').removeClass('active');
            $(this).addClass('active');
            renderProducts($(this).data('filter'));
        });
    }

    function showImage(index) {
        if (currentImages.length === 0) {
            $('#editor-image').attr('src', '');
            $('#img-counter').text('0/0');
            return;
        }
        currentImageIndex = Math.max(0, Math.min(index, currentImages.length - 1));
        $('#editor-image').attr('src', currentImages[currentImageIndex]);
        $('#img-counter').text((currentImageIndex + 1) + '/' + currentImages.length);
    }

    function openEditor(index) {
        currentEditIndex = index;
        var product = products[index];

        $('.tab-btn').removeClass('active').first().addClass('active');
        $('.tab-content').removeClass('active').first().addClass('active');

        $('#editor-sku').val(product['SKU'] || '');
        $('#editor-ean').val(product['meta:gtin_ean'] || '');
        $('#editor-name').val(product['Name'] || '');
        $('#editor-price').val(product['Regular price'] || '');
        $('#editor-stock').val(product['Stock'] || '100');
        $('#editor-short-desc').val(product['Short description'] || '');
        $('#editor-tags').val(product['Tags'] || '');
        populateCategoryDropdown(product['Categories']);

        var images = (product['Images'] || '').split(',').map(function(i) { return i.trim(); }).filter(Boolean);
        currentImages = images;
        currentImageIndex = 0;
        showImage(0);

        populateAttributeDropdowns(product);
        $('#editor-tech').val(product['Attribute 4 value(s)'] || '');
        $('#editor-color').val(product['Attribute 5 value(s)'] || '');
        $('#editor-phone-brand').val(product['Attribute 6 value(s)'] || '');

        $('#editor-seo-title').val(product['meta:rank_math_title'] || '').trigger('input');
        $('#editor-seo-desc').val(product['meta:rank_math_description'] || '').trigger('input');
        $('#editor-seo-keyword').val(product['meta:rank_math_focus_keyword'] || '');
        var slug = product['Slug'] || (product['Name'] || '').toLowerCase()
            .replace(/[ăâ]/g, 'a').replace(/[îì]/g, 'i')
            .replace(/[șş]/g, 's').replace(/[țţ]/g, 't')
            .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').substring(0, 60);
        $('#editor-slug').val(slug || '').trigger('input');

        $('#editor-meta-gtin').val(product['meta:gtin_ean'] || '');
        $('#editor-meta-sku-furnizor').val(product['meta:sku_furnizor'] || '');
        $('#editor-meta-furnizor').val(product['meta:furnizor_activ'] || '');
        $('#editor-meta-cost').val(product['meta:pret_achizitie'] || '');
        $('#editor-meta-location').val(product['meta:locatie_stoc'] || 'indisponibil');
        $('#editor-meta-warranty').val(product['meta:garantie_luni'] || '12');
        $('#editor-meta-source-url').val(product['meta:source_url'] || '').trigger('change');
        $('#editor-meta-ic-movable').prop('checked', product['meta:ic_movable'] === '1' || product['meta:ic_movable'] === 'true');
        $('#editor-meta-truetone').prop('checked', product['meta:truetone_support'] === '1' || product['meta:truetone_support'] === 'true');
        $('#editor-meta-compatibility').val(product['meta:coduri_compatibilitate'] || '');

        $('#editor-modal').fadeIn(200);
    }

    function populateCategoryDropdown(currentValue) {
        var $select = $('#editor-category');
        $select.empty();
        function addOptions(categories, prefix) {
            prefix = prefix || '';
            $.each(categories || {}, function(id, cat) {
                var path = prefix ? prefix + ' > ' + cat.name : cat.name;
                $select.append($('<option></option>').val(path).text(path));
                if (cat.children && Object.keys(cat.children).length > 0) {
                    addOptions(cat.children, path);
                }
            });
        }
        $select.append($('<option></option>').val('').text('-- Selectează --'));
        addOptions(config.categories);
        if (currentValue) {
            if ($select.find('option[value="' + currentValue.replace(/"/g, '&quot;') + '"]').length) {
                $select.val(currentValue);
            } else {
                $select.append($('<option></option>').val(currentValue).text(currentValue + ' (nou)'));
                $select.val(currentValue);
            }
        }
    }

    function populateAttributeDropdowns(product) {
        var $model = $('#editor-model');
        $model.empty();
        var modelAttr = config.attributes['pa_model-compatibil'] || config.attributes['pa_model'] || {};
        (modelAttr.terms || []).forEach(function(term) {
            $model.append($('<option></option>').val(term).text(term));
        });
        var currentModel = product['Attribute 1 value(s)'] || '';
        if (currentModel) {
            currentModel.split(',').forEach(function(m) {
                m = m.trim();
                if (m && $model.find('option[value="' + m.replace(/"/g, '&quot;') + '"]').length === 0) {
                    $model.append($('<option></option>').val(m).text(m));
                }
            });
            $model.val(currentModel.split(',').map(function(x) { return x.trim(); }).filter(Boolean));
        }
        var $quality = $('#editor-quality');
        $quality.empty().append($('<option></option>').val('').text('-- Selectează --'));
        var qualityTerms = (config.attributes['pa_calitate'] && config.attributes['pa_calitate'].terms) ? config.attributes['pa_calitate'].terms : [];
        qualityTerms.forEach(function(term) {
            $quality.append($('<option></option>').val(term).text(term));
        });
        $quality.val(product['Attribute 2 value(s)'] || '');
        var $brand = $('#editor-brand');
        $brand.empty().append($('<option></option>').val('').text('-- Selectează --'));
        var brandTerms = (config.attributes['pa_brand-piesa'] && config.attributes['pa_brand-piesa'].terms) ? config.attributes['pa_brand-piesa'].terms : [];
        brandTerms.forEach(function(term) {
            $brand.append($('<option></option>').val(term).text(term));
        });
        var currentBrand = product['Attribute 3 value(s)'] || '';
        if (currentBrand && $brand.find('option[value="' + currentBrand.replace(/"/g, '&quot;') + '"]').length === 0) {
            $brand.append($('<option></option>').val(currentBrand).text(currentBrand + ' (nou)'));
        }
        $brand.val(currentBrand);
    }

    function updateCharCount(field, current, max) {
        var $count = $('#' + field + '-count');
        var $indicator = $('#' + field + '-indicator');
        $count.text(current + '/' + max);
        $count.removeClass('warning error');
        $indicator.removeClass('good ok bad');
        if (current === 0) {
            $indicator.addClass('bad');
        } else if (current < max * 0.7) {
            $count.addClass('warning');
            $indicator.addClass('ok');
        } else if (current <= max) {
            $indicator.addClass('good');
        } else {
            $count.addClass('error');
            $indicator.addClass('bad');
        }
    }

    function initModal() {
        $('.modal-close, #editor-modal').on('click', function(e) {
            if (e.target === this) $('#editor-modal').fadeOut(200);
        });
        $('.modal-content').on('click', function(e) { e.stopPropagation(); });
        $('#btn-save-product').on('click', saveProduct);
        $('#btn-reset-product').on('click', function() {
            if (confirm('Resetezi la valorile originale?')) {
                products[currentEditIndex] = JSON.parse(JSON.stringify(originalProducts[currentEditIndex]));
                products[currentEditIndex]._validation = validateProduct(products[currentEditIndex]);
                openEditor(currentEditIndex);
            }
        });
        $('#btn-delete-product').on('click', function() {
            if (confirm('Excluzi acest produs din import?')) {
                products.splice(currentEditIndex, 1);
                $('#editor-modal').fadeOut(200);
                updateStats();
                renderProducts($('.filter-btn.active').data('filter') || 'all');
                updateReport();
            }
        });

        $('.tab-btn').on('click', function() {
            var tabId = $(this).data('tab');
            $('.tab-btn').removeClass('active');
            $(this).addClass('active');
            $('.tab-content').removeClass('active');
            $('#tab-' + tabId).addClass('active');
        });

        $('#editor-seo-title').on('input', function() {
            $('#preview-seo-title').text($(this).val() || 'Titlu produs - WebGSM');
            updateCharCount('title', $(this).val().length, 60);
        });
        $('#editor-seo-desc').on('input', function() {
            $('#preview-seo-desc').text($(this).val() || 'Descrierea meta va apărea aici...');
            updateCharCount('desc', $(this).val().length, 160);
        });
        $('#editor-slug').on('input', function() {
            $('#preview-seo-slug').text($(this).val() || 'slug-produs');
        });

        $('#btn-regen-slug').on('click', function() {
            var name = $('#editor-name').val();
            var slug = name.toLowerCase()
                .replace(/[ăâ]/g, 'a').replace(/[îì]/g, 'i')
                .replace(/[șş]/g, 's').replace(/[țţ]/g, 't')
                .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').substring(0, 60);
            $('#editor-slug').val(slug).trigger('input');
        });

        $('#editor-meta-source-url').on('change input', function() {
            var url = $(this).val();
            if (url) $('#link-source-url').attr('href', url).show(); else $('#link-source-url').hide();
        });

        $('#btn-prev-img').on('click', function() { showImage(currentImageIndex - 1); });
        $('#btn-next-img').on('click', function() { showImage(currentImageIndex + 1); });

        $('#btn-regen-title').on('click', function() {
            var name = $('#editor-name').val();
            $('#editor-seo-title').val(name.length > 60 ? name.substring(0, 57) + '...' : name).trigger('input');
        });
        $('#btn-regen-desc').on('click', function() {
            var name = $('#editor-name').val();
            var desc = name;
            if (desc.length < 130) desc += ' Comandă acum cu livrare rapidă!';
            if (desc.length > 160) desc = desc.substring(0, 157) + '...';
            $('#editor-seo-desc').val(desc).trigger('input');
        });

        $('#btn-new-category').on('click', function(e) {
            e.preventDefault();
            var newCat = prompt('Introdu calea completă a categoriei noi:\n\nExemplu: Piese > Piese iPhone > Cipuri Reparare\n\nCategoria părinte trebuie să existe deja.');
            if (newCat && newCat.trim()) {
                var $select = $('#editor-category');
                if ($select.find('option[value="' + newCat.replace(/"/g, '&quot;') + '"]').length === 0) {
                    $select.append($('<option></option>').val(newCat).text(newCat + ' (NOU)'));
                }
                $select.val(newCat);
                if (newCategories.indexOf(newCat) === -1) newCategories.push(newCat);
                $('#category-hint').text('⚠️ Categorie nouă - trebuie creată în WooCommerce înainte de import').addClass('warning');
            }
        });

        $('#btn-new-brand').on('click', function(e) {
            e.preventDefault();
            var newBrand = prompt('Introdu numele brandului nou:\n\nExemplu: I2C, Qianli, MEGA-IDEA');
            if (newBrand && newBrand.trim()) {
                var $select = $('#editor-brand');
                if ($select.find('option[value="' + newBrand.replace(/"/g, '&quot;') + '"]').length === 0) {
                    $select.append($('<option></option>').val(newBrand).text(newBrand + ' (NOU)'));
                }
                $select.val(newBrand);
                if (!newAttributes['pa_brand-piesa']) newAttributes['pa_brand-piesa'] = [];
                if (newAttributes['pa_brand-piesa'].indexOf(newBrand) === -1) newAttributes['pa_brand-piesa'].push(newBrand);
            }
        });

        $('#btn-new-model').on('click', function(e) {
            e.preventDefault();
            var newModel = prompt('Introdu modelul nou:\n\nExemplu: iPhone 15 Pro Max, Galaxy S24 Ultra');
            if (newModel && newModel.trim()) {
                var $select = $('#editor-model');
                if ($select.find('option[value="' + newModel.replace(/"/g, '&quot;') + '"]').length === 0) {
                    $select.append($('<option></option>').val(newModel).text(newModel));
                }
                var currentVals = $select.val() || [];
                currentVals.push(newModel);
                $select.val(currentVals);
            }
        });
    }

    function showNotice(message, type) {
        type = type || 'info';
        var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        $('.webgsm-tools h1').after($notice);
        setTimeout(function() { $notice.fadeOut(function() { $notice.remove(); }); }, 3000);
    }

    function saveProduct() {
        var product = products[currentEditIndex];
        product['SKU'] = $('#editor-sku').val();
        product['Name'] = $('#editor-name').val();
        product['Categories'] = $('#editor-category').val();
        product['Regular price'] = $('#editor-price').val();
        product['Stock'] = $('#editor-stock').val();
        product['Short description'] = $('#editor-short-desc').val();
        product['Tags'] = $('#editor-tags').val();
        product['Attribute 1 value(s)'] = ($('#editor-model').val() || []).join(', ');
        product['Attribute 2 value(s)'] = $('#editor-quality').val();
        product['Attribute 3 value(s)'] = $('#editor-brand').val();
        product['Attribute 4 value(s)'] = $('#editor-tech').val();
        product['Attribute 5 value(s)'] = $('#editor-color').val();
        product['Attribute 6 value(s)'] = $('#editor-phone-brand').val();
        product['meta:rank_math_title'] = $('#editor-seo-title').val();
        product['meta:rank_math_description'] = $('#editor-seo-desc').val();
        product['meta:rank_math_focus_keyword'] = $('#editor-seo-keyword').val();
        var slugVal = $('#editor-slug').val();
        if (slugVal) product['Slug'] = slugVal;
        product['meta:gtin_ean'] = $('#editor-meta-gtin').val();
        product['meta:pret_achizitie'] = $('#editor-meta-cost').val();
        product['meta:locatie_stoc'] = $('#editor-meta-location').val();
        product['meta:garantie_luni'] = $('#editor-meta-warranty').val();
        product['meta:ic_movable'] = $('#editor-meta-ic-movable').is(':checked') ? '1' : '0';
        product['meta:truetone_support'] = $('#editor-meta-truetone').is(':checked') ? '1' : '0';
        product['meta:coduri_compatibilitate'] = $('#editor-meta-compatibility').val();
        product._validation = validateProduct(product);
        updateStats();
        renderProducts($('.filter-btn.active').data('filter') || 'all');
        updateReport();
        showNotice('Produs salvat!', 'success');
        $('#editor-modal').fadeOut(200);
    }

    function updateReport() {
        newCategories = [];
        newAttributes = {};
        newTags = [];
        var validSlugs = config.validSlugs || [];
        products.forEach(function(product) {
            var category = product['Categories'] || '';
            if (category) {
                var parts = category.split('>').map(function(p) { return p.trim(); });
                var lastSlug = parts.length > 0 ? parts[parts.length - 1].toLowerCase().replace(/[^a-z0-9]+/g, '-') : '';
                if (lastSlug && validSlugs.indexOf(lastSlug) === -1 && newCategories.indexOf(category) === -1) {
                    newCategories.push(category);
                }
            }
            var brand = product['Attribute 3 value(s)'] || '';
            if (brand && config.attributes && config.attributes['pa_brand-piesa']) {
                var terms = config.attributes['pa_brand-piesa'].terms || [];
                if (terms.indexOf(brand) === -1) {
                    if (!newAttributes['pa_brand-piesa']) newAttributes['pa_brand-piesa'] = [];
                    if (newAttributes['pa_brand-piesa'].indexOf(brand) === -1) newAttributes['pa_brand-piesa'].push(brand);
                }
            }
            (product['Tags'] || '').split(',').map(function(t) { return t.trim(); }).filter(Boolean).forEach(function(tag) {
                if (newTags.indexOf(tag) === -1) newTags.push(tag);
            });
        });
        if (newCategories.length > 0) {
            $('#new-categories-box').show();
            $('#new-categories-list').html(newCategories.map(function(c) { return '<li><code>' + c + '</code></li>'; }).join(''));
        } else $('#new-categories-box').hide();
        if (Object.keys(newAttributes).length > 0) {
            $('#new-attributes-box').show();
            var html = '';
            for (var attr in newAttributes) {
                html += '<li><strong>' + attr + '</strong>: ' + newAttributes[attr].map(function(v) { return '<code>' + v + '</code>'; }).join(', ') + '</li>';
            }
            $('#new-attributes-list').html(html);
        } else $('#new-attributes-box').hide();
        if (newTags.length > 0) {
            $('#new-tags-box').show();
            $('#new-tags-list').html(newTags.slice(0, 20).map(function(t) { return '<li>' + t + '</li>'; }).join(''));
        } else $('#new-tags-box').hide();
        $('#report-section').show();
    }

    function initExport() {
        $('#btn-export-csv').on('click', exportCSV);
        $('#btn-copy-instructions').on('click', function() {
            var text = $('#instructions-preview').text();
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(function() { alert('Instrucțiuni copiate!'); });
            } else alert('Copiază manual din zonă.');
        });
    }

    function exportCSV() {
        if (products.length === 0) return;
        var headers = Object.keys(products[0]).filter(function(h) { return h.indexOf('_') !== 0; });
        if (headers.length === 0) headers = Object.keys(products[0]);
        var csv = headers.map(function(h) { return '"' + (h + '').replace(/"/g, '""') + '"'; }).join(',') + '\n';
        products.forEach(function(product) {
            csv += headers.map(function(h) {
                var val = (product[h] || '') + '';
                return '"' + val.replace(/"/g, '""') + '"';
            }).join(',') + '\n';
        });
        var blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'webgsm_reviewed_' + new Date().toISOString().slice(0, 10) + '.csv';
        a.click();
        URL.revokeObjectURL(url);
    }

    function showSections() {
        $('#stats-section, #products-section, #export-section').fadeIn(300);
        updateReport();
    }

    function truncate(text, length) {
        return text.length > length ? text.substring(0, length) + '...' : text;
    }
})(jQuery);
