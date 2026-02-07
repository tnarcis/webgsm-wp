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

    $(document).ready(function() {
        initUpload();
        initFilters();
        initModal();
        initExport();
    });

    function initUpload() {
        var $uploadArea = $('#upload-area');
        var $fileInput = $('#csv-file');

        $('#browse-file').on('click', function(e) {
            e.preventDefault();
            $fileInput.click();
        });
        $uploadArea.on('click', function() { $fileInput.click(); });
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

    function renderProducts(filter) {
        filter = filter || 'all';
        var $tbody = $('#products-tbody');
        $tbody.empty();
        var statusIcon = { 'ok': '✅', 'warning': '⚠️', 'error': '❌' };
        products.forEach(function(product, index) {
            if (filter !== 'all' && product._validation.status !== filter) return;
            var imgUrl = (product['Images'] || '').split(',')[0].trim() || 'data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="50" height="50"><rect fill="%23ddd" width="50" height="50"/></svg>';
            var row = '<tr class="row-' + product._validation.status + '" data-index="' + index + '">' +
                '<td>' + (index + 1) + '</td>' +
                '<td><img src="' + imgUrl + '" alt=""></td>' +
                '<td title="' + (product['Name'] || '').replace(/"/g, '&quot;') + '">' + truncate(product['Name'] || '', 40) + '</td>' +
                '<td class="category-cell">' + formatCategory(product['Categories']) + '</td>' +
                '<td>' + (product['Attribute 1 value(s)'] || '-') + '</td>' +
                '<td>' + (product['Attribute 2 value(s)'] || '-') + '</td>' +
                '<td>' + (product['Regular price'] || '-') + ' RON</td>' +
                '<td class="status-' + product._validation.status + '">' + statusIcon[product._validation.status] + '</td>' +
                '<td><button type="button" class="btn-edit" title="Editează">✏️</button></td></tr>';
            var $row = $(row);
            $row.find('.btn-edit').on('click', function() { openEditor(index); });
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

    function openEditor(index) {
        currentEditIndex = index;
        var product = products[index];
        $('#editor-name').val(product['Name'] || '');
        $('#editor-tags').val(product['Tags'] || '');
        $('#editor-seo-title').val(product['meta:rank_math_title'] || '');
        $('#editor-seo-desc').val(product['meta:rank_math_description'] || '');
        $('#editor-seo-keyword').val(product['meta:rank_math_focus_keyword'] || '');
        populateCategoryDropdown(product['Categories']);
        populateAttributeDropdowns(product);
        var imageUrl = (product['Images'] || '').split(',')[0].trim();
        $('#editor-image').attr('src', imageUrl || '');
        updateCharCounts();
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
            $model.val(currentModel.split(',').map(function(x) { return x.trim(); }));
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
                renderProducts();
                updateReport();
            }
        });
        $('#editor-seo-title, #editor-seo-desc').on('input', updateCharCounts);
        $('#btn-regen-title').on('click', function() {
            var name = $('#editor-name').val();
            $('#editor-seo-title').val(name.length > 60 ? name.substring(0, 57) + '...' : name);
            updateCharCounts();
        });
        $('#btn-regen-desc').on('click', function() {
            var name = $('#editor-name').val();
            var desc = name;
            if (desc.length < 130) desc += ' Comandă acum cu livrare rapidă!';
            if (desc.length > 160) desc = desc.substring(0, 157) + '...';
            $('#editor-seo-desc').val(desc);
            updateCharCounts();
        });
    }

    function saveProduct() {
        var product = products[currentEditIndex];
        product['Name'] = $('#editor-name').val();
        product['Categories'] = $('#editor-category').val();
        product['Tags'] = $('#editor-tags').val();
        product['Attribute 1 value(s)'] = ($('#editor-model').val() || []).join(', ');
        product['Attribute 2 value(s)'] = $('#editor-quality').val();
        product['Attribute 3 value(s)'] = $('#editor-brand').val();
        product['meta:rank_math_title'] = $('#editor-seo-title').val();
        product['meta:rank_math_description'] = $('#editor-seo-desc').val();
        product['meta:rank_math_focus_keyword'] = $('#editor-seo-keyword').val();
        product._validation = validateProduct(product);
        updateStats();
        renderProducts($('.filter-btn.active').data('filter'));
        updateReport();
        $('#editor-modal').fadeOut(200);
    }

    function updateCharCounts() {
        var titleLen = $('#editor-seo-title').val().length;
        var descLen = $('#editor-seo-desc').val().length;
        $('#title-count').text(titleLen + '/60').removeClass('warning error').addClass(titleLen > 60 ? 'error' : (titleLen > 50 ? 'warning' : ''));
        $('#desc-count').text(descLen + '/160').removeClass('warning error').addClass(descLen > 160 ? 'error' : (descLen > 140 ? 'warning' : ''));
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
