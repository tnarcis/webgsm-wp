<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap webgsm-tools">
    <h1>📦 Product Reviewer</h1>
    <p class="description">Verifică și corectează produsele înainte de import în WooCommerce</p>

    <div class="webgsm-card">
        <h2>📁 Încarcă CSV</h2>
        <div class="upload-area" id="upload-area">
            <input type="file" id="csv-file" accept=".csv" class="hidden-input">
            <div class="upload-placeholder">
                <span class="dashicons dashicons-upload"></span>
                <p>Trage fișierul CSV aici sau <a href="#" id="browse-file">click pentru a selecta</a></p>
                <small>Format acceptat: CSV generat de scriptul Python</small>
            </div>
        </div>
    </div>

    <div class="webgsm-card" id="stats-section" style="display:none">
        <h2>📊 Sumar</h2>
        <div class="stats-grid">
            <div class="stat-box stat-total">
                <span class="stat-number" id="stat-total">0</span>
                <span class="stat-label">Total Produse</span>
            </div>
            <div class="stat-box stat-ok">
                <span class="stat-number" id="stat-ok">0</span>
                <span class="stat-label">✅ OK</span>
            </div>
            <div class="stat-box stat-warning">
                <span class="stat-number" id="stat-warning">0</span>
                <span class="stat-label">⚠️ Atenție</span>
            </div>
            <div class="stat-box stat-error">
                <span class="stat-number" id="stat-error">0</span>
                <span class="stat-label">❌ Erori</span>
            </div>
        </div>
        <div class="filter-bar">
            <button type="button" class="filter-btn active" data-filter="all">Toate</button>
            <button type="button" class="filter-btn" data-filter="ok">✅ OK</button>
            <button type="button" class="filter-btn" data-filter="warning">⚠️ Atenție</button>
            <button type="button" class="filter-btn" data-filter="error">❌ Erori</button>
        </div>
    </div>

    <div class="webgsm-card" id="products-section" style="display:none">
        <h2>📋 Produse</h2>
        <div class="table-responsive">
            <table class="wp-list-table widefat fixed striped" id="products-table">
                <thead>
                    <tr>
                        <th width="30">#</th>
                        <th width="50">Img</th>
                        <th width="120">SKU</th>
                        <th width="200">Nume</th>
                        <th width="150">Categorie</th>
                        <th width="100">Model</th>
                        <th width="80">Calitate</th>
                        <th width="80">Brand</th>
                        <th width="70">Preț</th>
                        <th width="50">Stoc</th>
                        <th width="60">SEO</th>
                        <th width="50">Status</th>
                        <th width="50">Act.</th>
                    </tr>
                </thead>
                <tbody id="products-tbody"></tbody>
            </table>
        </div>
    </div>

    <div class="webgsm-modal" id="editor-modal" style="display:none">
        <div class="modal-content">
            <div class="modal-header">
                <h3>✏️ Editare Produs</h3>
                <button type="button" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="editor-tabs">
                    <button type="button" class="tab-btn active" data-tab="general">📦 General</button>
                    <button type="button" class="tab-btn" data-tab="attributes">🏷️ Atribute</button>
                    <button type="button" class="tab-btn" data-tab="seo">🔍 SEO</button>
                    <button type="button" class="tab-btn" data-tab="meta">⚙️ Meta</button>
                </div>
                <div class="editor-content">
                    <div class="tab-content active" id="tab-general">
                        <div class="editor-grid">
                            <div class="editor-preview">
                                <img id="editor-image" src="" alt="Preview">
                                <div class="image-nav">
                                    <button type="button" id="btn-prev-img">◀</button>
                                    <span id="img-counter">1/1</span>
                                    <button type="button" id="btn-next-img">▶</button>
                                </div>
                            </div>
                            <div class="editor-fields">
                                <div class="field-row">
                                    <div class="field-group field-half">
                                        <label>SKU (Cod Intern) <span class="required">*</span></label>
                                        <input type="text" id="editor-sku" class="regular-text">
                                        <small class="field-hint">Format: WG-TIP-MODEL-BRAND-NR</small>
                                    </div>
                                    <div class="field-group field-half">
                                        <label>EAN/GTIN</label>
                                        <input type="text" id="editor-ean" class="regular-text" readonly>
                                        <small class="field-hint">Din furnizor (needitabil)</small>
                                    </div>
                                </div>
                                <div class="field-group">
                                    <label>Nume Produs <span class="required">*</span></label>
                                    <input type="text" id="editor-name" class="regular-text">
                                    <button type="button" class="btn-inline" id="btn-regen-name">🔄 Regenerează</button>
                                </div>
                                <div class="field-group">
                                    <label>Categorie <span class="required">*</span></label>
                                    <select id="editor-category" class="regular-text"></select>
                                    <div id="category-hint" class="field-hint"></div>
                                    <button type="button" class="btn-inline" id="btn-new-category">+ Categorie Nouă</button>
                                </div>
                                <div class="field-row">
                                    <div class="field-group field-half">
                                        <label>Preț Regular (RON)</label>
                                        <input type="number" id="editor-price" class="regular-text" step="0.01">
                                    </div>
                                    <div class="field-group field-half">
                                        <label>Stoc</label>
                                        <input type="number" id="editor-stock" class="regular-text">
                                    </div>
                                </div>
                                <div class="field-group">
                                    <label>Descriere Scurtă</label>
                                    <textarea id="editor-short-desc" rows="2" class="regular-text"></textarea>
                                </div>
                                <div class="field-group">
                                    <label>Tag-uri</label>
                                    <input type="text" id="editor-tags" class="regular-text" placeholder="tag1, tag2, tag3">
                                    <small class="field-hint">Separate prin virgulă</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-content" id="tab-attributes">
                        <div class="attributes-grid">
                            <div class="field-group">
                                <label>Model Compatibil</label>
                                <select id="editor-model" class="regular-text" multiple size="6"></select>
                                <button type="button" class="btn-inline" id="btn-new-model">+ Model Nou</button>
                                <small class="field-hint">Ctrl+Click pentru selecție multiplă</small>
                            </div>
                            <div class="field-group">
                                <label>Calitate <span class="required">*</span></label>
                                <select id="editor-quality" class="regular-text"></select>
                            </div>
                            <div class="field-group">
                                <label>Brand Piesă</label>
                                <select id="editor-brand" class="regular-text"></select>
                                <button type="button" class="btn-inline" id="btn-new-brand">+ Brand Nou</button>
                            </div>
                            <div class="field-group">
                                <label>Tehnologie Display</label>
                                <select id="editor-tech" class="regular-text">
                                    <option value="">-- Selectează (doar pentru ecrane) --</option>
                                    <option value="Soft OLED">Soft OLED</option>
                                    <option value="Hard OLED">Hard OLED</option>
                                    <option value="AMOLED">AMOLED</option>
                                    <option value="Super AMOLED">Super AMOLED</option>
                                    <option value="In-Cell">In-Cell</option>
                                    <option value="Incell LCD">Incell LCD</option>
                                    <option value="LCD IPS">LCD IPS</option>
                                    <option value="LCD TFT">LCD TFT</option>
                                    <option value="Retina">Retina</option>
                                </select>
                            </div>
                            <div class="field-group">
                                <label>Culoare</label>
                                <select id="editor-color" class="regular-text">
                                    <option value="">-- Selectează --</option>
                                    <option value="Negru">Negru</option>
                                    <option value="Alb">Alb</option>
                                    <option value="Auriu">Auriu</option>
                                    <option value="Argintiu">Argintiu</option>
                                    <option value="Albastru">Albastru</option>
                                    <option value="Roșu">Roșu</option>
                                    <option value="Verde">Verde</option>
                                    <option value="Mov">Mov</option>
                                    <option value="Roz">Roz</option>
                                    <option value="Gri">Gri</option>
                                </select>
                            </div>
                            <div class="field-group">
                                <label>Brand Telefon</label>
                                <select id="editor-phone-brand" class="regular-text">
                                    <option value="">-- Selectează --</option>
                                    <option value="Apple">Apple</option>
                                    <option value="Samsung">Samsung</option>
                                    <option value="Huawei">Huawei</option>
                                    <option value="Xiaomi">Xiaomi</option>
                                    <option value="Google">Google</option>
                                    <option value="OnePlus">OnePlus</option>
                                    <option value="Oppo">Oppo</option>
                                    <option value="Sony">Sony</option>
                                    <option value="LG">LG</option>
                                    <option value="Motorola">Motorola</option>
                                    <option value="Nokia">Nokia</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="tab-content" id="tab-seo">
                        <div class="seo-preview">
                            <div class="google-preview">
                                <p class="preview-title" id="preview-seo-title">Titlu produs - WebGSM</p>
                                <p class="preview-url">webgsm.ro/produs/<span id="preview-seo-slug">slug-produs</span>/</p>
                                <p class="preview-desc" id="preview-seo-desc">Descrierea meta va apărea aici...</p>
                            </div>
                        </div>
                        <div class="seo-fields">
                            <div class="field-group">
                                <label>Meta Title <span class="char-count" id="title-count">0/60</span><span class="seo-indicator" id="title-indicator"></span></label>
                                <input type="text" id="editor-seo-title" class="regular-text" maxlength="70">
                                <button type="button" class="btn-inline" id="btn-regen-title">🔄 Auto-generează</button>
                                <div class="seo-tips"><small>✅ Ideal: 50-60 caractere | Include: model + tip + brand</small></div>
                            </div>
                            <div class="field-group">
                                <label>Meta Description <span class="char-count" id="desc-count">0/160</span><span class="seo-indicator" id="desc-indicator"></span></label>
                                <textarea id="editor-seo-desc" class="regular-text" rows="3" maxlength="170"></textarea>
                                <button type="button" class="btn-inline" id="btn-regen-desc">🔄 Auto-generează</button>
                                <div class="seo-tips"><small>✅ Ideal: 140-160 caractere | Include: beneficii + CTA</small></div>
                            </div>
                            <div class="field-group">
                                <label>Focus Keyword (pentru Rank Math)</label>
                                <input type="text" id="editor-seo-keyword" class="regular-text">
                            </div>
                            <div class="field-group">
                                <label>URL Slug</label>
                                <input type="text" id="editor-slug" class="regular-text">
                                <button type="button" class="btn-inline" id="btn-regen-slug">🔄 Din nume</button>
                            </div>
                        </div>
                    </div>
                    <div class="tab-content" id="tab-meta">
                        <p class="tab-description">Câmpuri meta pentru gestiune internă, SmartBill și indexare Google.</p>
                        <div class="meta-grid">
                            <div class="field-group">
                                <label>GTIN/EAN (pentru Google Shopping)</label>
                                <input type="text" id="editor-meta-gtin" class="regular-text">
                            </div>
                            <div class="field-group">
                                <label>SKU Furnizor</label>
                                <input type="text" id="editor-meta-sku-furnizor" class="regular-text" readonly>
                            </div>
                            <div class="field-group">
                                <label>Furnizor Activ</label>
                                <input type="text" id="editor-meta-furnizor" class="regular-text" readonly>
                            </div>
                            <div class="field-group">
                                <label>Preț Achiziție (cost)</label>
                                <input type="number" id="editor-meta-cost" class="regular-text" step="0.01">
                            </div>
                            <div class="field-group">
                                <label>Locație Stoc</label>
                                <select id="editor-meta-location" class="regular-text">
                                    <option value="indisponibil">Indisponibil (comandă la furnizor)</option>
                                    <option value="depozit-principal">Depozit Principal</option>
                                    <option value="depozit-secundar">Depozit Secundar</option>
                                    <option value="magazin">Magazin</option>
                                    <option value="in-tranzit">În Tranzit</option>
                                </select>
                            </div>
                            <div class="field-group">
                                <label>Garanție (luni)</label>
                                <select id="editor-meta-warranty" class="regular-text">
                                    <option value="1">1 lună</option>
                                    <option value="3">3 luni</option>
                                    <option value="6">6 luni</option>
                                    <option value="12" selected>12 luni</option>
                                    <option value="24">24 luni</option>
                                </select>
                            </div>
                            <div class="field-group">
                                <label>URL Sursă</label>
                                <input type="text" id="editor-meta-source-url" class="regular-text" readonly>
                                <a href="#" id="link-source-url" target="_blank" class="btn-inline" style="display:none;">🔗 Deschide</a>
                            </div>
                            <div class="field-group">
                                <label>Funcționalități Speciale</label>
                                <div class="checkbox-group">
                                    <label><input type="checkbox" id="editor-meta-ic-movable"> IC Movable (transfer cip)</label>
                                    <label><input type="checkbox" id="editor-meta-truetone"> TrueTone Support</label>
                                </div>
                            </div>
                            <div class="field-group">
                                <label>Coduri Compatibilitate (opțional)</label>
                                <textarea id="editor-meta-compatibility" class="regular-text" rows="2" placeholder="Coduri alternative, part numbers..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="button" id="btn-reset-product">↩️ Resetează</button>
                <button type="button" class="button" id="btn-delete-product">🗑️ Exclude din Import</button>
                <button type="button" class="button button-primary" id="btn-save-product">💾 Salvează</button>
            </div>
        </div>
    </div>

    <div class="webgsm-card" id="report-section" style="display:none">
        <h2>📋 Raport Final</h2>
        <div class="report-box" id="new-categories-box" style="display:none">
            <h3>⚠️ Categorii Noi Necesare</h3>
            <ul id="new-categories-list"></ul>
            <div class="report-actions">
                <p><strong>Opțiuni:</strong></p>
                <ol>
                    <li>Creează manual în WooCommerce → Produse → Categorii</li>
                    <li>Sau folosește Setup Wizard → Actualizează Categorii</li>
                </ol>
                <button type="button" class="button" id="btn-copy-category-prompt">📋 Copiază Prompt Cursor</button>
            </div>
        </div>
        <div class="report-box" id="new-attributes-box" style="display:none">
            <h3>⚠️ Valori Atribute Noi</h3>
            <ul id="new-attributes-list"></ul>
        </div>
        <div class="report-box" id="new-tags-box" style="display:none">
            <h3>ℹ️ Tag-uri Noi</h3>
            <ul id="new-tags-list"></ul>
        </div>
    </div>

    <div class="webgsm-card" id="export-section" style="display:none">
        <h2>📥 Export</h2>
        <div class="export-buttons">
            <button type="button" class="button button-primary button-hero" id="btn-export-csv">📥 Descarcă CSV Corectat</button>
            <button type="button" class="button" id="btn-copy-instructions">📋 Copiază Instrucțiuni</button>
        </div>
        <div class="instructions-preview" id="instructions-preview">
            <h4>Pași după export:</h4>
            <ol>
                <li>Descarcă CSV-ul corectat</li>
                <li>Creează categoriile/atributele noi (dacă există)</li>
                <li>WooCommerce → Produse → Import</li>
                <li>Mapare coloane și rulează importul</li>
            </ol>
        </div>
    </div>
</div>
