<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap webgsm-tools">
    <h1>📦 Product Reviewer</h1>
    <p class="description">Verifică și corectează produsele înainte de import în WooCommerce</p>

    <div class="webgsm-card">
        <h2>📁 Încarcă CSV</h2>
        <div class="upload-area" id="upload-area">
            <input type="file" id="csv-file" accept=".csv" style="display:none">
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
                        <th width="40">#</th>
                        <th width="60">Img</th>
                        <th width="250">Nume</th>
                        <th width="200">Categorie</th>
                        <th width="120">Model</th>
                        <th width="100">Calitate</th>
                        <th width="80">Preț</th>
                        <th width="80">Status</th>
                        <th width="60">Acțiuni</th>
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
                <div class="editor-grid">
                    <div class="editor-preview">
                        <img id="editor-image" src="" alt="Preview">
                        <div class="preview-badges" id="preview-badges"></div>
                    </div>
                    <div class="editor-fields">
                        <div class="field-group">
                            <label>Nume Produs</label>
                            <input type="text" id="editor-name" class="regular-text">
                            <button type="button" class="btn-small" id="btn-regen-name">🔄 Regenerează</button>
                        </div>
                        <div class="field-group">
                            <label>Categorie</label>
                            <select id="editor-category" class="regular-text"></select>
                            <div class="field-hint" id="category-hint"></div>
                            <button type="button" class="btn-small btn-new-category" id="btn-new-category">+ Categorie Nouă</button>
                        </div>
                        <div class="field-group">
                            <label>Model Compatibil</label>
                            <select id="editor-model" class="regular-text" multiple></select>
                        </div>
                        <div class="field-group">
                            <label>Calitate</label>
                            <select id="editor-quality" class="regular-text"></select>
                        </div>
                        <div class="field-group">
                            <label>Brand Piesă</label>
                            <select id="editor-brand" class="regular-text"></select>
                            <button type="button" class="btn-small" id="btn-new-brand">+ Brand Nou</button>
                        </div>
                        <div class="field-group">
                            <label>Tag-uri</label>
                            <input type="text" id="editor-tags" class="regular-text" placeholder="tag1, tag2, tag3">
                        </div>
                        <hr>
                        <h4>🔍 SEO</h4>
                        <div class="field-group">
                            <label>Meta Title <span class="char-count" id="title-count">0/60</span></label>
                            <input type="text" id="editor-seo-title" class="regular-text" maxlength="60">
                            <button type="button" class="btn-small" id="btn-regen-title">🔄 Regenerează</button>
                        </div>
                        <div class="field-group">
                            <label>Meta Description <span class="char-count" id="desc-count">0/160</span></label>
                            <textarea id="editor-seo-desc" class="regular-text" rows="3" maxlength="160"></textarea>
                            <button type="button" class="btn-small" id="btn-regen-desc">🔄 Regenerează</button>
                        </div>
                        <div class="field-group">
                            <label>Focus Keyword</label>
                            <input type="text" id="editor-seo-keyword" class="regular-text">
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
